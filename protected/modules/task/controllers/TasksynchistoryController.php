<?php

/**
 * Created by PhpStorm.
 * User: wuyk
 * Date: 2016/12/16
 * Time: 12:01
 */
class TasksynchistoryController extends TaskBaseController
{
    public function accessRules()
    {
        return array(
            array(
                'allow',
                'users' => array(
                    '*'
                ),
                'actions' => array()
            )
        );
    }

    /**
     * 从刊登列表更新数据到刊登历史记录表
     */
    public function actionListing()
    {
        echo 'Start:' . date('Y-m-d H:i:s');
        $execute_start = date('Y-m-d H:i:s');
        $message = '';
        try {
            set_time_limit(0);
            $platform = strtoupper(Yii::app()->request->getParam('platform', Platform::CODE_EBAY));
            $listingOptimizationModel = $this->model('task', $platform);

            //取出用户
            $users = $listingOptimizationModel->distinct_seller_user();
            $debug = Yii::app()->request->getParam('debug', false);
            $current = Yii::app()->request->getParam('date_time', date('Y-m-d'));
            $start_time = date('Y-m-d 00:00:00', strtotime($current));
            $end_time = date('Y-m-d 23:59:59', strtotime($current));
            if ($debug) {
                echo '==============================Date Time======================================<br />';
                echo 'Current: ' . $current;
                echo '<br />Start Time: ' . $start_time;
                echo '<br />End Time: ' . $end_time;
            }

            $siteList = $this->siteList($platform);
            if ($debug) {
                echo '<br />================================Site List============<br />';
                print_r($siteList);
            }

            $accountList = $this->accountList($platform);
            if ($debug) {
                echo '<br />================================Account List =============<br />';
                print_r($accountList);
            }

            if ($debug) {
                echo '============================= Fetch User List $users ========================';
                echo '<pre />';
                print_r($users);
            }

            //刊登历史记录表
            $model = $this->model('history', $platform);
            //产品表
            $productsModel = $this->productsModel($platform);

            if (!empty($users)) {
                foreach ($users as $k => $v) {
                    $row = array(
                        'seller_user_id' => $v['seller_user_id'],
                        'current' => $current,
                        'start_time' => $start_time,
                        'end_time' => $end_time,
                        'platform' => $platform,
                        'accountList' => $accountList,
                        'siteList' => $siteList,
                    );
                    $this->syncData($row, $model, $productsModel, $debug);
                }//end foreach $users
            } //end if users

            //更新最新增加销售当月的数据
            $new_users = $listingOptimizationModel->distinct_seller_user(true);
            if (!empty($new_users)) {
                $start = date('Y-m-01 00:00:00');
                $end = date('Y-m-d H:i:s');
                foreach ($new_users as $nk => $nv) {
                    $new_row = array(
                        'seller_user_id' => $nv['seller_user_id'],
                        'current' => $current,
                        'start_time' => $start,
                        'end_time' => $end,
                        'platform' => $platform,
                        'accountList' => $accountList,
                        'siteList' => $siteList,
                    );
                    $this->syncData($new_row, $model, $productsModel, $debug);
                }
            }

            //如果是 Ebay， 则把批量刊登，复制的加入到历史记录，因为 seller_user_id不会有重复，所以用为键值
            if (Platform::CODE_EBAY == $platform) {
                $otherWhereCondition = ('' != $this->whereField($platform)) ? $this->whereField($platform) : 1;
                $account_site_data = $productsModel->productAddAccountSiteData($start_time, $end_time);
                if (!empty($account_site_data)) {
                    $findData = array();
                    foreach ($account_site_data as $akey => $aval) {
                        $site_name = $siteList[$aval['site_id']];
                        //平台，账号，站点确定是哪一个销售的id
                        $account_site_user_arr = SellerUserToAccountSite::model()->getsiterByCondition($platform, $aval['account_id'], $site_name);
                        $findData[$aval['account_id']][$aval['site_id']] = $account_site_user_arr['seller_user_id'];
                    }

                    if (!empty($findData)) {
                        //用账号+站点+seller_user为0+开始时间+结束时间获取没有绑定销售id的数据
                        $field_arr = $this->platformField($platform);
                        $field = join(",", $field_arr);
                        foreach ($findData as $find_account_id => $findArr) {
                            foreach ($findArr as $find_site_id => $seller_user_id) {
                                /**---------------------韩翔宇 2017-04-01 开始--------------------------------**/

                                //加判断seller_user_id是否为空
                                if (!$seller_user_id) {
                                    continue;
                                }

                                /**---------------------韩翔宇 2017-04-01 结束--------------------------------**/

                                $field = $field . ", {$seller_user_id} AS create_user_id";
                                $find_data_arr = $productsModel->getAllByCondition($field, "{$otherWhereCondition} AND account_id = '{$find_account_id}' 
                                                 AND site_id = '{$find_site_id}' AND create_user_id = 0 AND create_time BETWEEN '{$start_time}' AND '{$end_time}' ");

                                $row_data = array(
                                    'seller_user_id' => $seller_user_id,
                                    'current' => $current,
                                    'platform' => $platform,
                                    'accountList' => $accountList,
                                    'siteList' => $siteList,
                                );

                                $this->processData($find_data_arr, $row_data, $model, $debug);
                            }
                        }
                    }
                }
            }

            //把系统分配的任务标识更新为系统
            $model->syncSystemListing($current, $platform);
        } catch (Exception $e) {
            $message = $e->getMessage();
            echo $e->getMessage();
        }
        echo '<br />End:' . date('Y-m-d H:i:s');
        $execute_end = date('Y-m-d H:i:s');
        //记录执行的情况
        TaskLogModel::model()->saveData(
            array(
                'file_name' => $_SERVER['REQUEST_URI'],
                'start_time' => $execute_start,
                'end_time' => $execute_end,
                'message' => $message,
            )
        );
    }


    /**
     * 从刊登历史记录表中把已刊登的状态修改掉（通知Api接口）
     */
    public function actionSyncApiListing()
    {
        set_time_limit(3600);
        ini_set("display_errors", true);
        error_reporting(E_ALL);
        echo 'Start:' . date('Y-m-d H:i:s');
        $execute_start = date('Y-m-d H:i:s');
        $message = '';
        try {
            $platform = strtoupper(Yii::app()->request->getParam('platform', Platform::CODE_EBAY));
            $date_time = Yii::app()->request->getParam('date_time', date('Y-m-d'));
            $debug = Yii::app()->request->getParam('debug', false);

            //从历史记录中更新未同步的数据（只更新上传成功的）
            $task_keys = ConfigFactory::getConfig('taskKeys');
            $model = $this->model('history', $platform);
            $rows = $model->findAllByAttributes(
                array(
                    //'date_time' => date('Y-m-d', strtotime($date_time)),
                    'is_sync' => 0,
                    'status' => array($model::STATUS_SCUCESS),
                )
            );

            if (!empty($rows)) {
                $total = count($rows);
                if (0 < $total) {
                    $page_size = isset($task_keys['page_size']) ? $task_keys['page_size'] : 50;
                    $total_pages = ceil($total / $page_size);
                    $flag = true;
                    do {
                        for ($page = 1; $page <= $total_pages; $page++) {
                            $min = ($page - 1) * $page_size;
                            $max = ($page * $page_size - 1);
                            $arr = array();
                            for ($k = $min; $k <= $max; $k++) {
                                if ($total <= $k) {
                                    $flag = false;
                                    break;
                                }
                                $arr[] = array(
                                    'id' => $rows[$k]['id'],
                                    'platformCode' => $platform,
                                    'staffId' => $rows[$k]['seller_user_id'],
                                    'accountId' => $rows[$k]['account_id'],
                                    'sku' => $rows[$k]['sku'],
                                    'site' => $rows[$k]['site_name'],
                                    'warehouseId' => $rows[$k]['warehouse_id'],
                                    'status' => $rows[$k]['status'],
                                    'updater' => 'system_client_sync'
                                );
                            }
                            if ($debug) {
                                echo "<br />============= fetch page: {$page} data: ================<br /><pre>";
                                print_r($arr);
                                echo "</pre>";
                            }

                            //发送数据给 Api更新接口状态
                            $apiResult = json_decode($this->postApi($arr), true);
                            //经跟Java组沟通，他那边是事务处理的，如果成功则全部成功，失败则全部失败，所以成功的，状态修改为已更新
                            if ('succ' == $apiResult['status']) {
                                foreach ($arr as $ak => $av) {
                                    if ($debug) {
                                        echo '===== Update Id:' . $av['id'] . "===============<br />";
                                    }
                                    $model->updateDataByID(array('is_sync' => 1), $av['id']);
                                }
                            }
                        }
                    } while ($flag);
                }
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
            echo $e->getMessage();
        }
        echo '<br />End:' . date('Y-m-d H:i:s');
        $execute_end = date('Y-m-d H:i:s');
        //记录执行的情况
        TaskLogModel::model()->saveData(
            array(
                'file_name' => $_SERVER['REQUEST_URI'],
                'start_time' => $execute_start,
                'end_time' => $execute_end,
                'message' => $message,
            )
        );
    }


    //每月记录信息，每天跑一次，查询更新时间放宽至一星期
    public function actionSyncrecords()
    {
        set_time_limit(0);
        echo 'Start:' . date('Y-m-d H:i:s');
        $execute_start = date('Y-m-d H:i:s');
        $message = '';
        try {
            //$date_time = Yii::app()->request->getParam('date_time', date('Y-m-d', strtotime("-1 days")));
            $platform = strtoupper(Yii::app()->request->getParam('platform', Platform::CODE_EBAY));
            $debug = Yii::app()->request->getParam('debug', false);
            $days = Yii::app()->request->getParam('days', 7);

            $listingOptimizationModel = $this->model('task', $platform);
            $model = $this->model('record', $platform);
            $users = $listingOptimizationModel->distinct_seller_user();
            for ($i = 1; $i < $days; $i++) {
                $date_time = date('Y-m-d', strtotime("-{$i} days"));
                if (!empty($users)) {
                    if ($debug) {
                        echo "=========================================================<pre>";
                        print_r($users);
                        echo "=========================================================</pre>";
                    }

                    foreach ($users as $k => $v) {
                        $seller_user_id = $v['seller_user_id'];
                        $data = $model->getOneByCondition(
                            'id',
                            'seller_user_id =:user_id AND date_time =:date_time',
                            array(':user_id' => $seller_user_id, ':date_time' => $date_time)
                        );
                        $row = $listingOptimizationModel->getListOptimizationNum(array($seller_user_id));
                        if (empty($data)) {
                            //计算分数
                            $listing_num = $row['listing_num'];
                            $optimization_num = $row['optimization_num'];
                            $finish_listing_num = $this->sellerFinishListing($seller_user_id, $date_time, $platform);
                            $finish_optimization_num = $this->sellerFinishOptimization($seller_user_id, $date_time, $platform);
                            $score = $this->calScore($seller_user_id, $listing_num, $optimization_num, $finish_listing_num, $finish_optimization_num, $date_time);
                            //没有数据，则插入
                            $newData = array(
                                'seller_user_id' => $seller_user_id,
                                'listing_num' => $row['listing_num'],
                                'optimization_num' => $row['optimization_num'],
                                'finish_listing_num' => $finish_listing_num,
                                'finish_optimization_num' => $finish_optimization_num,
                                'score' => $score,
                                'date_time' => $date_time
                            );
                            $model->saveData($newData);
                        } else {
                            //计算分数
                            $listing_num = $row['listing_num'];
                            $optimization_num = $row['optimization_num'];
                            $finish_listing_num = $this->sellerFinishListing($seller_user_id, $date_time, $platform);
                            $finish_optimization_num = $this->sellerFinishOptimization($seller_user_id, $date_time, $platform);
                            $score = $this->calScore($seller_user_id, $listing_num, $optimization_num, $finish_listing_num, $finish_optimization_num, $date_time);
                            //已存在数据，则更新
                            $newData = array(
                                'listing_num' => $listing_num,
                                'optimization_num' => $optimization_num,
                                'finish_listing_num' => $finish_listing_num,
                                'finish_optimization_num' => $finish_optimization_num,
                                'score' => $score,
                            );
                            $model->updateDataByID($newData, array($data['id']));
                        }
                    }
                }
            }

        } catch (Exception $e) {
            $message = $e->getMessage();
            echo $e->getMessage();
        }
        echo '<br />End:' . date('Y-m-d H:i:s');

        $execute_end = date('Y-m-d H:i:s');
        TaskLogModel::model()->saveData(
            array(
                'file_name' => $_SERVER['REQUEST_URI'],
                'start_time' => $execute_start,
                'end_time' => $execute_end,
                'message' => $message,
            )
        );
    }


    /**
     * 汇总每月的数据
     **/
    public function actionSyncmonthrecords()
    {
        $platform = Yii::app()->request->getParam('platform', Platform::CODE_EBAY);
        $platform = strtoupper($platform);
        $date_time = Yii::app()->request->getParam('date_time', date('Y-m-d', strtotime('-1 days')));
        $start_time = date("Y-m-01", strtotime($date_time));
        $end_time = date('Y-m-t', strtotime($date_time));
        set_time_limit(0);
        echo 'Start:' . date('Y-m-d H:i:s');
        $execute_start = date('Y-m-d H:i:s');
        $message = '';
        try {
            //当前月的天数
            $days = date('t', strtotime($start_time));
            $list_optimization_model = $this->model('task', $platform);
            $month_record = $this->model('month_record', $platform);
            $record = $this->model('record', $platform);

            $rows = $record->getAllByCondition("
            seller_user_id,
            DATE_FORMAT(date_time,'%Y-%m-01') AS date_time,
            SUM(finish_listing_num) AS finish_listing_num,
            SUM(finish_optimization_num) AS finish_optimization_num,
            AVG(score) AS score",
                "date_time BETWEEN '{$start_time}' AND '{$end_time}'",
                '',
                'GROUP BY seller_user_id'
            );

            if (!empty($rows)) {
                foreach ($rows as $k => $v) {
                    //取得当月需刊登，需优化的数量
                    $list_optimization_data = $list_optimization_model->getListOptimizationNum(array($v['seller_user_id']));
                    $list_num = isset($list_optimization_data) ? $list_optimization_data['listing_num'] : 0;
                    $optimization_num = isset($list_optimization_data) ? $list_optimization_data['optimization_num'] : 0;
                    $v['listing_num'] = $list_num * $days;
                    $v['optimization_num'] = $optimization_num * $days;
                    //检查是否已经有数据了，没有则新增，有则更新
                    $data = $month_record->getOneByCondition("id", "seller_user_id = '" . $v['seller_user_id'] . "' AND date_time='" . $v['date_time'] . "'");
                    if (!empty($data)) {
                        $month_record->updateDataByID($v, $data['id']);
                    } else {
                        $month_record->saveData($v);
                    }
                }
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
            echo $e->getMessage();
        }
        echo '<br />End:' . date('Y-m-d H:i:s');

        $execute_end = date('Y-m-d H:i:s');
        TaskLogModel::model()->saveData(
            array(
                'file_name' => $_SERVER['REQUEST_URI'],
                'start_time' => $execute_start,
                'end_time' => $execute_end,
                'message' => $message,
            )
        );
    }



    /**
     * 汇总每年的数据
     **/
    public function actionSyncyearrecords()
    {
        $platform = strtoupper(Yii::app()->request->getParam('platform', Platform::CODE_EBAY));
        $date_time = Yii::app()->request->getParam('date_time', date('Y-m-d', strtotime('-1 days')));
        $start_time = date("Y-01-01", strtotime($date_time));
        $end_time = date('Y-12-01', strtotime($date_time));
        set_time_limit(0);

        echo 'Start:' . date('Y-m-d H:i:s');
        $execute_start = date('Y-m-d H:i:s');
        $message = '';
        try {
            //当前月的天数
            $l = date('L', strtotime($start_time));
            $days = (true == $l) ? 366 : 365;

            $list_optimization_model = $this->model('task', $platform);
            $year_record = $this->model('year_record', $platform);
            $month_record = $this->model('month_record', $platform);

            $rows = $month_record->getAllByCondition("
            seller_user_id,
            DATE_FORMAT(date_time,'%Y') AS year,
            SUM(finish_listing_num) AS finish_listing_num,
            SUM(finish_optimization_num) AS finish_optimization_num
            ",
                "date_time BETWEEN '{$start_time}' AND '{$end_time}'",
                '',
                'GROUP BY seller_user_id'
            );

            if (!empty($rows)) {
                foreach ($rows as $k => $v) {
                    //取得当月需刊登，需优化的数量
                    $list_optimization_data = $list_optimization_model->getListOptimizationNum(array($v['seller_user_id']));
                    $list_num = isset($list_optimization_data) ? $list_optimization_data['listing_num'] : 0;
                    $optimization_num = isset($list_optimization_data) ? $list_optimization_data['optimization_num'] : 0;
                    $v['listing_num'] = $list_num * $days;
                    $v['optimization_num'] = $optimization_num * $days;
                    //检查是否已经有数据了，没有则新增，有则更新
                    $data = $year_record->getOneByCondition("id", "seller_user_id = '" . $v['seller_user_id'] . "' AND year='" . $v['year'] . "'");
                    if (!empty($data)) {
                        $year_record->updateDataByID($v, $data['id']);
                    } else {
                        $year_record->saveData($v);
                    }
                }
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
            echo $e->getMessage();
        }
        echo '<br />End:' . date('Y-m-d H:i:s');

        $execute_end = date('Y-m-d H:i:s');
        TaskLogModel::model()->saveData(
            array(
                'file_name' => $_SERVER['REQUEST_URI'],
                'start_time' => $execute_start,
                'end_time' => $execute_end,
                'message' => $message,
            )
        );
    }


    /**
     *
     * 修正数据
     */
    public function actionRepair()
    {
        set_time_limit(0);
        try {
            $platform = strtoupper(Yii::app()->request->getParam('platform', Platform::CODE_EBAY));
            $listingOptimizationModel = $this->model('task', $platform);
            $model = $this->model('history', $platform);

            $seller_user_id = Yii::app()->request->getParam('seller_user_id', 0);
            $debug = Yii::app()->request->getParam('debug', false);
            $users = (0 < $seller_user_id) ? array(array('seller_user_id' => $seller_user_id)) : $listingOptimizationModel->distinct_seller_user();
            if (!empty($users)) {
                if ($debug) {
                    echo "=========================================================<pre>";
                    print_r($users);
                    echo "=========================================================</pre>";
                }

                foreach ($users as $k => $v) {
                    $seller_user_id = $v['seller_user_id'];
                    $model->syncHistoryStatus($seller_user_id);
                }
            }

        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }


    public function actionRepairdata()
    {
        $platform = strtoupper(Yii::app()->request->getParam('platform', Platform::CODE_EBAY));
        $date_time = Yii::app()->request->getParam('date_time', date('Y-m-d'));
        $model = $this->model('history', $platform);
        $model->deleteAllByAttributes(
            array(
                'date_time' => $date_time,
            )
        );
        echo 'Done!!!';
    }


    /**
     * @param $row 参数
     * @param $model history model
     * @param $productsModel product add model
     * @param bool $debug debug
     *
     * 同步数据
     */
    private function syncData($row, $model, $productsModel, $debug = false)
    {
        $seller_user_id = $row['seller_user_id'];
        $start_time = $row['start_time'];
        $end_time = $row['end_time'];
        $field_arr = $this->platformField($row['platform']);
        $field = join(",", $field_arr);
        $otherWhere = ('' != $this->whereField($row['platform'])) ? $this->whereField($row['platform']) : 1;
        $data = $productsModel->getAllByCondition($field, " {$otherWhere} AND create_user_id = '{$seller_user_id}' AND '{$start_time}' <= create_time AND create_time <= '{$end_time}'");
        if ($debug) {
            echo '<br />================================ seller user id =' . $seller_user_id . ' Listing data <br />';
            print_r($data);
        }

        $this->processData($data, $row, $model, $debug);
    }

    /**
     * @param $data
     * @param $row
     * @param $model
     * @param bool $debug
     *
     * 处理数据
     */
    private function processData($data, $row, $model, $debug = false)
    {
        $seller_user_id = $row['seller_user_id'];
        $accountList = $row['accountList'];
        $siteList = ($row['platform'] == Platform::CODE_AMAZON) ? array_flip($row['siteList']) : $row['siteList'];
        $array = array();
        if (!empty($data)) {
            foreach ($data as $k => $v) {
                switch ($v['status']) {
                    case 4:
                        $status = 3;
                        break;
                    case 3:
                        $status = 2;
                        break;
                    default:
                        $status = 2;
                        break;
                }

                if (in_array($row['platform'], array(Platform::CODE_AMAZON))) {
                    if (Platform::CODE_AMAZON == $row['platform']) {
                        $site_arr = array_flip(AmazonSite::getSiteList());
                        $site_id = $site_arr[$v['site_id']];
                        $site_name = $v['site_id'];
                    } else {
                        $site_id = 0;
                        $site_name = 'us';
                    }
                } else {
                    $site_id = $v['site_id'];
                    $site_name = $siteList[$v['site_id']];
                }

                //把时间检查的去掉，一个账号+sku+销售只允许刊登一次，如果不同日期都有那就不算了
                if (Platform::CODE_WISH != $row['platform']) {
                    //如果数据库中为空，则插入，否则更新(model 为 history)
                    $result = $model->getOneByCondition(
                        'id',
                        'seller_user_id = :seller_user_id 
								 AND account_id = :account_id 
								 AND sku = :sku 
								 AND site_id = :site_id
								 AND date_time >=:start_time
								 AND date_time <=:end_time
								 ',
                        array(
                            ':seller_user_id' => $seller_user_id,
                            ':account_id' => $v['account_id'],
                            ':sku' => $v['sku'],
                            ':site_id' => $site_id,
                            ':start_time' => date('Y-m-01'),
                            ':end_time' => date('Y-m-d')
                        )
                    );
                } else {
                    //如果为Wish
                    $result = $model->getOneByCondition(
                        'id',
                        'seller_user_id = :seller_user_id 
								 AND account_id = :account_id 
								 AND sku = :sku 
								 AND warehouse_id = :warehouse_id
							     AND date_time >=:start_time
								 AND date_time <=:end_time
								 ',
                        array(
                            ':seller_user_id' => $seller_user_id,
                            ':account_id' => $v['account_id'],
                            ':sku' => $v['sku'],
                            ':warehouse_id' => $v['warehouse_id'],
                            ':start_time' => date('Y-m-01'),
                            ':end_time' => date('Y-m-d')
                        )
                    );
                }

                if ($debug) {
                    echo '<br />============================== Check Result =======================<br />';
                    print_r($result);
                }

                if (empty($result)) {
                    $arr = array(
                        $v['id'],
                        $seller_user_id,
                        $v['account_id'],
                        $accountList[$v['account_id']],
                        $site_name,
                        $site_id,
                        $v['warehouse_id'],
                        $row['platform'],
                        $v['sku'],
                        $v['category_id'],
                        $v['category_id'],
                        addslashes($v['title']),
                        $v['currency'],
                        $status,
                        date('Y-m-d H:i:s'),
                        $v['last_response_time'],
                        0,
                        0,
                        $v['status'],
                        $row['current']
                    );
                    //同一天内相同的账号+sku只算作一个，aliexpress的site_id为0
                    if (Platform::CODE_WISH != $row['platform']) {
                        $array[$v['account_id']][$site_id][$v['sku']] = "'" . implode("','", $arr) . "'";
                    } else {
                        //wish 使用账号+仓库+sku做为唯一标识
                        $array[$v['account_id']][$v['warehouse_id']][$v['sku']] = "'" . implode("','", $arr) . "'";
                    }
                } else {
                    //更新
                    $updateArr = array(
                        'status' => $status,
                        'product_status' => $v['status'],
                        'sku_title' => addslashes($v['title']),
                    );
                    $upResult = $model->updateDataByFields($updateArr, 'id', $result['id']);
                    if ($debug) {
                        echo '<br />=============update add_id = ' . $result['id'] . '===========<br />Result: ';
                        print_r($upResult);
                    }
                }
                //把刊登成功的，但是还处于待刊登的记录设置为已刊登
                $wait_model = $this->model('wait', $row['platform']);
                $wait_model->updateWaitingListingStatus($v, $status);
            }
        }//end $data

        //如果不为空，则插入
        if (!empty($array)) {
            //循环数据插入
            foreach ($array as $inkey => $inval) {
                foreach ($inval as $ikey => $ival) {
                    $inResult = $model->saveData($ival);
                }
            }
            if ($debug) {
                echo '<br />================Inser data ============Result:<br />';
                print_r($inResult);
            }
        }
    }

    /**
     * @param string $platform
     * @return EbayListingOptimization|EbaySalesTarget
     */
    private function productsModel($platform = Platform::CODE_EBAY)
    {
        return $this->model('product_add', $platform);
    }

    /**
     * @param $seller_user_id
     * @param $date_time
     * @param string $platform
     * @return mixed
     *
     * 获取已优化数据的总数
     */
    private function sellerFinishListing($seller_user_id, $date_time, $platform = Platform::CODE_EBAY)
    {
        $model = $this->model('history', $platform);
        $num = $model->fetchListingNum($seller_user_id, $date_time);
        return $num;
    }


    /**
     * @param $seller_user_id
     * @param $date_time
     * @param string $platform
     * @return mixed
     *
     * 返回已优化的数量
     */
    private function sellerFinishOptimization($seller_user_id, $date_time, $platform = Platform::CODE_EBAY)
    {
        $model = $this->model('optimization', $platform);
        $row = $model->getTotalByCondition(" seller_user_id = '{$seller_user_id}' AND date_time = '{$date_time}' AND status = 3");
        return $row['total'];
    }

    /**
     * @param string $platform
     * @return array
     *
     * 返回相应平台可查询到的字段信息
     */
    private function platformField($platform = Platform::CODE_EBAY)
    {
        $task_keys = ConfigFactory::getConfig('taskKeys');
        $default_warehouse_id = $task_keys['default_warehouse_id'];
        switch ($platform) {
            case Platform::CODE_EBAY:
                $fields = array(
                    'id',
                    'account_id',
                    'create_user_id',
                    'site_id',
                    "'us' AS country_code",
                    "$default_warehouse_id AS warehouse_id",
                    'sku',
                    'title',
                    'category_id',
                    'currency',
                    'status',
                    'create_time',
                    'last_response_time'
                );
                break;
            case Platform::CODE_ALIEXPRESS:
                $fields = array(
                    'id',
                    'account_id',
                    'create_user_id',
                    '0 AS site_id',
                    "'us' AS country_code",
                    "$default_warehouse_id AS warehouse_id",
                    'sku',
                    'subject AS title',
                    'category_id',
                    'currency',
                    'status',
                    'create_time',
                    'upload_time AS last_response_time'
                );
                break;
            case Platform::CODE_WISH:
                $currency = WishProductAdd::PRODUCT_PUBLISH_CURRENCY;
                $fields = array(
                    'id',
                    'account_id',
                    'create_user_id',
                    '0 AS site_id',
                    "'us' AS country_code",
                    "warehouse_id",
                    'parent_sku AS sku',
                    'name AS title',
                    '0 AS category_id',
                    "'{$currency}' AS currency",
                    'upload_status AS status',
                    'create_time',
                    'last_upload_time AS last_response_time'
                );
                break;
            case Platform::CODE_AMAZON:
                $fields = array(
                    'id',
                    'account_id',
                    'create_user_id',
                    'country_code AS site_id',
                    'country_code',
                    "$default_warehouse_id AS warehouse_id",
                    'sku',
                    'title',
                    'category_id',
                    "currency",
                    'status',
                    'create_time',
                    'upload_finish_time AS last_response_time'
                );
                break;
            case Platform::CODE_LAZADA:
                $fields = array(
                    'id',
                    'account_id',
                    'create_user_id',
                    'site_id',
                    "'us' AS country_code",
                    "$default_warehouse_id AS warehouse_id",
                    'sku',
                    'title',
                    'category_id',
                    "currency",
                    'status',
                    'create_time',
                    'upload_time AS last_response_time'
                );
                break;
            default:
                $fields = array(
                    'id',
                    'account_id',
                    'create_user_id',
                    'site_id',
                    "'us' AS country_code",
                    "$default_warehouse_id AS warehouse_id",
                    'sku',
                    'title',
                    'category_id',
                    'currency',
                    'status',
                    'create_time',
                    'last_response_time'
                );
                break;
        }
        return $fields;
    }


    private function whereField($platform = Platform::CODE_EBAY)
    {
        switch ($platform) {
            case Platform::CODE_EBAY:
                $condition = " listing_type IN(2,3)"; //1、拍卖，2、一口价，3、多属性
                break;
            case Platform::CODE_ALIEXPRESS:
                $condition = ''; //
                break;
            default:
                $condition = '';
                break;
        }
        return $condition;
    }


    /**
     * 计算昨日分数
     */
    private function calScore($seller_user_id, $listing_num, $optimization_num, $finish_listing_num, $finish_optimization_num, $date_time)
    {
        //根据销售ID获取7天动销率与7天优化动销率
        $dep_id = User::model()->getDepIdById($seller_user_id);
        $row = TaskPaneMovingRateUser::model()->find("sales_id = '{$seller_user_id}' AND department_id = '{$dep_id}' AND cal_date='{$date_time}'");
        $listing_rate = !empty($row) ? $row['moving_rate_7'] : 0;

        $row_optimization = WaitOptSale::model()->find("sale_id = '{$seller_user_id}' AND department_id = '{$dep_id}' AND stc_date = '{$date_time}'");
        $optimization_rate = !empty($row_optimization) ? $row_optimization['mov_rate'] : 0;

        $listing_score = (0 < $listing_num) ? $finish_listing_num / $listing_num : 1;
        $optimization_score = (0 < $optimization_num) ? $finish_optimization_num / $optimization_num : 1;

        $score = ($listing_score * $listing_rate + $optimization_score * $optimization_rate) * 10000 / 2;
        return $score;
    }
}