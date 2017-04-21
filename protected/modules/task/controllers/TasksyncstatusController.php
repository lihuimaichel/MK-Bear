<?php
/**
 * Created by PhpStorm.
 * User: wuyk
 * Date: 2017/2/24
 * Time: 14:37
 */

class TasksyncstatusController extends TaskBaseController
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
     * 同步刊登池中的状态
     */
    public function actionSyncwaitlistingstatus()
    {
        set_time_limit(3600);
        ini_set('memory_limit', '2000M');
        ini_set("display_errors", true);
        error_reporting(E_ALL);
        echo 'Start:'.date('Y-m-d H:i:s').'<br />';

        $execute_start = date('Y-m-d H:i:s');
        $message = '';
        try {
            $platform = strtoupper(Yii::app()->request->getParam('platform', Platform::CODE_EBAY));
            $date_time = Yii::app()->request->getParam('date_time', '');
            $seller_user_id = Yii::app()->request->getParam('user_id', 0);
            $where = (0 < $seller_user_id) ? "AND seller_user_id = '{$seller_user_id}'" : " ";
            $debug = Yii::app()->request->getParam('debug', false);
            $current_day = date('j');
            $start_date = ('' == $date_time) ? (($current_day < 6) ? date('Y-m-d', strtotime("-7 days")) : date('Y-m-01')) : $date_time; //默认为当月所有
            $end_date = ('' == $date_time) ? date('Y-m-d') : $date_time; //默认到当前时间

            $history_model = $this->model('history', $platform);
            //获取待刊登中的数据
            $wait_model = $this->model('wait', $platform);
            $rows = $wait_model->getDataByCondition("seller_user_id, platform_code, account_id, site_id, site_name, sku, warehouse_id", "status = 1 {$where} AND date_time BETWEEN '{$start_date}' AND '{$end_date}'");
            if ($debug) {
                echo "Wait Total:".count($rows)." <br />";
            }

            //查询数据，把不是多属性的主sku排除掉
            $multi_data = array();
            if (!empty($rows)) {
                foreach ($rows as $k => $v) {
                    $data = Product::model()->getProductBySku($v['sku'], 'id, product_is_multi');
                    if(!empty($data)) {
                        if (Product::PRODUCT_MULTIPLE_MAIN == $data['product_is_multi'] || Product::PRODUCT_MULTIPLE_NORMAL == $data['product_is_multi']) {
                            $multi_data[] = array_merge($v, array('product_id' => $data['id'], 'product_is_multi' => $data['product_is_multi']));
                        }
                    };
                }
            }

            //所有余下的多属性主sku，获取相应的子SKU（在原来的数组结构中添加新的键值）
            if (!empty($multi_data)) {
                foreach ($multi_data as $mk => $mv) {
                    //判断是否是单品
                    if($mv['product_is_multi'] == Product::PRODUCT_MULTIPLE_NORMAL){
                        $multi_data[$mk]['sub_sku'] = $mv['sku'];
                    }else{
                        $sub_row = ProductSelectAttribute::model()->getSubSkuListing($mv['product_id']);
                        $multi_data[$mk]['sub_sku'] = $sub_row;
                    }  
                }
            }

            if ($debug) {
                echo "Multi Data Total:".count($multi_data)."<br />Multi Data List:<pre>";
                print_r($multi_data);
                echo "</pre>";
            }

            //取得部门id
            if (!empty($multi_data)) {
                foreach ($multi_data as $key => $val) {
                    $dept_id = User::model()->getDepIdById($val['seller_user_id']);
                    if (in_array($dept_id, array(19)) && $val['product_is_multi'] != Product::PRODUCT_MULTIPLE_NORMAL) {
                        //把没有绑定的子sku删除
                        $check_sub_sku = array();
                        if (!empty($val['sub_sku'])) {
                            foreach ($val['sub_sku'] as $sk => $sv) {
                                $acc_seller_platform_data = ProductToAccountSellerPlatform::model()->getSKUSellerRelation($sv, $val['seller_user_id'], $val['account_id'], $val['platform_code'], $val['site_name']);
                                if (!empty($acc_seller_platform_data)) {
                                    $check_sub_sku[] = $sv;
                                }
                            }
                        }
                    } else {
                        $check_sub_sku = $val['sub_sku'];
                    }


                    if (!empty($check_sub_sku)) {
                        //从子刊登表中查询是否已刊登完了，如果是，则更新状态
                        $seller_user_id = $val['seller_user_id'];
                        $account_id = $val['account_id'];
                        $site_id = $val['site_id'];

                        /**-------------------韩翔宇 2017-03-28 添加 开始----------------------------**/
                        //判断是否是数组
                        $in_sku = $check_sub_sku;
                        if(is_array($check_sub_sku)){
                            $in_sku = join("','", $check_sub_sku);
                        }
                        /**-------------------韩翔宇 2017-03-28 添加 开始----------------------------**/

                        // $in_sku = join("','", $check_sub_sku);
                        $params['create_user_id'] = $val['seller_user_id'];
                        $params['account_id'] = $val['account_id'];
                        $params['site_id'] = $val['site_id'];
                        $params['sku'] = $val['sku'];
                        $params['warehouse_id'] = $val['warehouse_id'];
                        if (in_array($platform, array(Platform::CODE_AMAZON))) {
                            $params['country_code'] = $val['site_name'];
                        }

                        //把负责人去掉，只要是账号+站点+sku一致就算是这刊登了，跟销售人没有关系
                        $history_row = $history_model->getOneByCondition("COUNT(id) AS total", " account_id = '{$account_id}' AND site_id = '{$site_id}' AND sku IN('{$in_sku}')");
                        $history_total = $history_row['total'];
                        if ($debug) {
                            echo "Params <pre>";
                            print_r($params);
                            echo "</pre>";
                            echo "History total: {$history_total}, Sub Sku total: ".count($check_sub_sku)."<br />";
                        }
                        if ($history_total == count($check_sub_sku)) {
                            //修改状态
                            $wait_model->updateWaitingListingStatus($params, $wait_model::STATUS_SCUCESS);
                            //通知接口修改状态，下次不要返回此sku到此账号，站点，销售
                            $arr[] = array(
                                'platformCode' => $val['platform_code'],
                                'staffId' => $val['seller_user_id'],
                                'accountId' => $val['account_id'],
                                'sku' => $val['sku'],
                                'site' => $val['site_name'],
                                'status' => $wait_model::STATUS_SCUCESS,
                                'updater' => 'system_client_sync'
                            );
                            $result = $this->postApi($arr);
                        } else {
                            //根据子sku从刊登列表中找到是否已经全部刊登，全部刊登了把状态修改为已刊登
                            $total = ProductPlatformListingSlave::model()->getSkuNumNew($val['platform_code'], $check_sub_sku, $val['site_name'], $val['account_id']);
                            echo "Listing Total: {$total}, Sku: ".$val['sku']." Sub Sku total: ".count($check_sub_sku)."<br />";
                            if ($total == count($check_sub_sku)) {
                                //修改状态
                                $wait_model->updateWaitingListingStatus($params, $wait_model::STATUS_SCUCESS);

                                //通知接口修改状态，下次不要返回此sku到此账号，站点，销售
                                $arr[] = array(
                                    'platformCode' => $val['platform_code'],
                                    'staffId' => $val['seller_user_id'],
                                    'accountId' => $val['account_id'],
                                    'sku' => $val['sku'],
                                    'site' => $val['site_name'],
                                    'status' => $wait_model::STATUS_SCUCESS,
                                    'updater' => 'system_client_sync'
                                );
                                $result = $this->postApi($arr);
                            }
                        }

                        if ($debug) {
                            echo "Change Sku List:";
                            print_r($val['sku']);
                            echo "<br />";
                        }
                    }

                }
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
            echo $e->getMessage();
        }
        echo 'End  :'.date('Y-m-d H:i:s').'<br />';

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
}
