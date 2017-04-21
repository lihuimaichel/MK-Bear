<?php

/**
 * @desc Wish订单相关
 * @author Gordon
 * @since 2015-06-02
 */
class WishorderController extends UebController
{

    /**
     * @desc 访问过滤配置
     * @see CController::accessRules()
     */
    public function accessRules()
    {
        return array(
            array(
                'allow',
                'users' => array('*'),
                'actions' => array(
                    'getordersnew',//new
                    'checkgetordersnew',//new
                    'getorderinfo',//new
                    'getchangeorders',//new
                    'syncorder',//new
                )
            ),
        );
    }

    /**
     * @desc 下载订单到中间库  -- new
     * @link /wish/wishorder/getordersnew/account_id/1
     */
    public function actionGetordersnew()
    {
        set_time_limit(3600);
        ini_set("display_errors", true);
        error_reporting(E_ALL & ~E_STRICT);

        $accountID = trim(Yii::app()->request->getParam('account_id', ''));

        if ($accountID) {//根据账号抓取订单信息
            $logModel = new WishLog;
            $eventName = WishLog::EVENT_GETORDER;//getordersnew事件
            $logID = $logModel->prepareLog($accountID, $eventName);
            if ($logID) {
                //1.检查账号是否可拉取订单
                $checkRunning = $logModel->checkRunning($accountID, $eventName);
                if (!$checkRunning) {
                    $logModel->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
                    echo "There Exists An Active Event";
                } else {
                    //2.准备拉取日志信息
                    $timeSince = WishOrderMain::model()->getTimeSince($accountID);

                    //插入本次log参数日志(用来记录请求的参数)
                    $eventLog = $logModel->saveEventLog($eventName, array(
                        'log_id' => $logID,
                        'account_id' => $accountID,
                        'since_time' => str_replace('T', ' ', $timeSince),
                        'complete_time' => date('Y-m-d H:i:s', time())//下次拉取时间可以从当前时间点进行,这是北京时间
                    ));

                    //设置日志为正在运行
                    $logModel->setRunning($logID);

                    //3.拉取订单
                    $wishOrderMain = new WishOrderMain();
                    $wishOrderMain->setAccountID($accountID);//设置账号
                    $wishOrderMain->setLogID($logID);//设置日志编号
                    $flag = $wishOrderMain->getOrders($timeSince);//拉单

                    //4.更新日志信息
                    if ($flag) {
                        $logModel->setSuccess($logID);
                        $logModel->saveEventStatus($eventName, $eventLog, WishLog::STATUS_SUCCESS);
                    } else {
                        $errMsg = $wishOrderMain->getExceptionMessage();
                        if (mb_strlen($errMsg) > 500) {
                            $errMsg = mb_substr($errMsg, 0, 500);
                        }
                        $logModel->setFailure($logID, $errMsg);
                        $logModel->saveEventStatus($eventName, $eventLog, WishLog::STATUS_FAILURE);
                    }
                    echo json_encode($_REQUEST) . ($flag ? ' Success ' : ' Failure ') . $wishOrderMain->getExceptionMessage() . "<br>";
                }
            }
        } else {//循环可用账号，多线程抓取
            $wishAccounts = WishAccount::model()->getAbleAccountList();
            foreach ($wishAccounts as $account) {
                $url = Yii::app()->request->hostInfo . '/' . $this->route . '/account_id/' . $account['id'];
                echo $url . " <br>\r\n";
                MHelper::runThreadBySocket($url);
                sleep(10);
            }
        }
        Yii::app()->end('finish');
    }

    /**
     * @desc 补拉订单到中间库 -- new
     * @link /wish/wishorder/checkgetordersnew/account_id/1
     */
    public function actionCheckgetordersnew()
    {
        set_time_limit(5 * 3600);
        ini_set("display_errors", true);
        error_reporting(E_ALL & ~E_STRICT);

        $accountID = trim(Yii::app()->request->getParam('account_id', ''));
        $timeSince = trim(Yii::app()->request->getParam('since_time', ''));//2016-11-12T10:30:00
        $day = trim(Yii::app()->request->getParam('day', 3));
        $day == '' && $day = 3;//默认3天

        if ($accountID) {//根据账号抓取订单信息
            $logModel = new WishLog;
            $eventName = WishLog::EVENT_CHECK_GETORDER;//checkgetordersnew事件
            $logID = $logModel->prepareLog($accountID, $eventName);
            if ($logID) {
                //1.检查账号是否可拉取订单
                $checkRunning = $logModel->checkRunning($accountID, $eventName);
                if (!$checkRunning) {
                    $logModel->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
                    echo "There Exists An Active Event";
                } else {
                    //2.准备拉取日志信息
                    if ($timeSince == '') {
                        $timeSince = str_replace(" ", "T", date("Y-m-d H:i:s", time() - 8 * 3600 - $day * 86400));
                    }

                    //设置日志为正在运行
                    $logModel->setRunning($logID);

                    //3.拉取订单
                    $wishOrderMain = new WishOrderMain();
                    $wishOrderMain->setAccountID($accountID);//设置账号
                    $wishOrderMain->setLogID($logID);//设置日志编号
                    $flag = $wishOrderMain->getOrders($timeSince);//拉单

                    //4.更新日志信息
                    if ($flag) {
                        $logModel->setSuccess($logID);
                    } else {
                        $errMsg = $wishOrderMain->getExceptionMessage();
                        if (mb_strlen($errMsg) > 500) {
                            $errMsg = mb_substr($errMsg, 0, 500);
                        }
                        $logModel->setFailure($logID, $errMsg);
                    }
                    echo json_encode($_REQUEST) . ($flag ? ' Success ' : ' Failure ') . $wishOrderMain->getExceptionMessage() . "<br>";
                }
            }
        } else {//循环可用账号，多线程抓取
            $wishAccounts = WishAccount::model()->getAbleAccountList();
            foreach ($wishAccounts as $account) {
                $url = Yii::app()->request->hostInfo . '/' . $this->route
                    . '/account_id/' . $account['id']
                    . "/day/" . $day
                    . "/since_time/" . $timeSince;
                echo $url . " <br>\r\n";
                MHelper::runThreadBySocket($url);
                sleep(10);
            }
        }
        Yii::app()->end('finish');
    }

    /**
     * @desc 拉取单个订单处理
     * @link /wish/wishorder/getorderinfo/order_id/xx/account_id/1
     * @throws Exception
     */
    public function actionGetorderinfo()
    {
        $orderIds = Yii::app()->request->getParam("order_id");
        $accountId = Yii::app()->request->getParam("account_id");
        if (!$orderIds) {
            echo "no orderID";
            exit();
        }
        if (!$accountId) {
            echo "no accountID";
            exit();
        }
        $orderIds = explode(",", $orderIds);
        if ($orderIds) {
            foreach ($orderIds as $orderId) {
                $orderModel = new RetrieveOrdersRequest;
                $orderModel->setAccount($accountId);
                $orderModel->setOrderId($orderId);
                $response = $orderModel->setRequest()->sendRequest()->getResponse();
                $wishOrderModel = new WishOrderMain();
                $wishOrderModel->setLogID(10000);
                $wishOrderModel->setAccountID($accountId);//设置账号
                $this->print_r($response);

                //循环订单信息
                $dbTransaction = $wishOrderModel->dbConnection->getCurrentTransaction();
                if (!$dbTransaction) {
                    $dbTransaction = $wishOrderModel->dbConnection->beginTransaction();//开启事务
                }
                try {
                    if (!isset($response->data->Order)) {
                        throw new Exception('no data');
                    }

                    // 只抓取审核通过的订单
                    //APPROVED REQUIRE_REVIEW
                    if (trim($response->data->Order->state) != "APPROVED") {
                        //throw new Exception('state is'. trim($response->data->Order->state));
                    }

                    $res = $wishOrderModel->saveOrderMainData($response->data->Order);
                    var_dump($res);
                    echo "<br>" . $wishOrderModel->getExceptionMessage() . "<br>";
                    $dbTransaction->commit();
                    echo "finish";
                } catch (Exception $e) {
                    $dbTransaction->rollback();
                    $msg = Yii::t('ebay', 'Save Order Infomation Failed');
                    var_dump($e->getMessage());
                }
            }
        }
    }

    /**
     * @desc 抓取最近变动的订单
     * @link /wish/wishorder/getchangeorders/day/0.1/account_id/1
     */
    public function actionGetchangeorders()
    {
        set_time_limit(5 * 3600);
        ini_set("display_errors", true);
        error_reporting(E_ALL & ~E_STRICT);

        $accountID = trim(Yii::app()->request->getParam('account_id', ''));
        $timeSince = trim(Yii::app()->request->getParam('since_time', ''));//2016-11-12T10:30:00
        $day = trim(Yii::app()->request->getParam('day', 0.1));
        $day == '' && $day = 0.1;//默认o.1天

        if ($accountID) {//根据账号抓取订单信息
            $logModel = new WishLog;
            $eventName = WishLog::EVENT_GETCHANGEORDERS;//getchangeorders事件
            $logID = $logModel->prepareLog($accountID, $eventName);
            if ($logID) {
                //1.检查账号是否可拉取订单
                $checkRunning = $logModel->checkRunning($accountID, $eventName);
                if (!$checkRunning) {
                    $logModel->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
                    echo "There Exists An Active Event";
                } else {
                    //2.准备拉取日志信息
                    if ($timeSince == '') {
                        $timeSince = str_replace(" ", "T", date("Y-m-d H:i:s", time() - 8 * 3600 - $day * 86400));
                    }
                    echo $timeSince . "<br>";
                    //设置日志为正在运行
                    $logModel->setRunning($logID);

                    //3.拉取订单
                    $wishOrderMain = new WishOrderMain();
                    $wishOrderMain->setAccountID($accountID);//设置账号
                    $wishOrderMain->setLogID($logID);//设置日志编号
                    $flag = $wishOrderMain->getChangeOrders($timeSince);//拉单

                    //4.更新日志信息
                    if ($flag) {
                        $logModel->setSuccess($logID);
                    } else {
                        $errMsg = $wishOrderMain->getExceptionMessage();
                        if (mb_strlen($errMsg) > 500) {
                            $errMsg = mb_substr($errMsg, 0, 500);
                        }
                        $logModel->setFailure($logID, $errMsg);
                    }
                    echo json_encode($_REQUEST) . ($flag ? ' Success ' : ' Failure ') . $wishOrderMain->getExceptionMessage() . "<br>";
                }
            }
        } else {//循环可用账号，多线程抓取
            $wishAccounts = WishAccount::model()->getAbleAccountList();
            foreach ($wishAccounts as $account) {
                $url = Yii::app()->request->hostInfo . '/' . $this->route
                    . '/account_id/' . $account['id']
                    . "/day/" . $day
                    . "/since_time/" . $timeSince;
                echo $url . " <br>\r\n";
                MHelper::runThreadBySocket($url);
                sleep(10);
            }
        }
        Yii::app()->end('finish');
    }

    /**
     * @desc 同步订单到oms -- new
     * @link /wish/wishorder/syncorder/account_id/xx/order_id/1111
     */
    public function actionSyncorder()
    {
        set_time_limit(3600);
        ini_set("display_errors", true);
        error_reporting(E_ALL & ~E_STRICT);

        $limit = Yii::app()->request->getParam("limit", 1000);
        $accountID = trim(Yii::app()->request->getParam("account_id", ''));
        $platformOrderID = trim(Yii::app()->request->getParam("order_id", ''));

        $syncTotal = 0;
        $logID = 1000;
        //@todo 增加日志控制
        $logModel = new WishLog();
        $virAccountID = 90000;
        $eventName = WishLog::EVENT_SYNC_ORDER;
        $logID = $logModel->prepareLog($virAccountID, $eventName);
        if ($logID) {
            $checkRunning = $logModel->checkRunning($virAccountID, $eventName);
            if (!$checkRunning) {
                $logModel->setFailure($logID, "Have a active event");
                exit("Have a active event");
            }
            //设置日志为正在运行
            $logModel->setRunning($logID);
            $wishAccounts = WishAccount::model()->getAbleAccountList();
            foreach ($wishAccounts as $account) {
                if (!empty($accountID) && $account['id'] != $accountID) {
                    continue;
                }
                $wishOrderMain = new WishOrderMain();
                $wishOrderMain->setAccountID($account['id']);
                $wishOrderMain->setLogID($logID);
                $syncCount = $wishOrderMain->syncOrderToOmsByAccountID($account['id'], $limit, $platformOrderID);
                $syncTotal += $syncCount;
                echo "<br>======={$account['account_name']}: {$syncCount}======" . $wishOrderMain->getExceptionMessage() . "<br/>";
            }
            $logModel->setSuccess($logID, 'Total:' . $syncTotal);
            echo "finish";
        } else {
            exit("Create Log Id Failure!!!");
        }
    }

    //手动拉单
    public function actionManualGetOrder()
    {

        $this->render("manualgetorder", array(
            'datePeriod' => array(
                7,
                8,
                9,
                10,
                15,
                20
            )
        ));
    }

    /**
     * 手动拉单
     */
    public function actionManualSaveOrder()
    {
        set_time_limit(3600);
        $accountID = Yii::app()->request->getParam('account_id');
        $orderIDs = Yii::app()->request->getParam('order_id');
        $typeID = Yii::app()->request->getParam('type_id');//1拉取，2拉取同步

        $datePeriod = Yii::app()->request->getParam('datePeriod');// 时间间隔
        if (!$datePeriod) {
            $datePeriod = 7;
        }
        $orderDatePeriod = null;

        $orderDatePeriod = new \DateTime();
        $orderDatePeriod->modify('-' . $datePeriod . ' day');
        //$dateTime->format('Y-m-d H:i:s');

        try {
            if (!$accountID) {
                throw new Exception('指定账号');
            }
            if (!$typeID) {
                throw new Exception("未知类型，错误");
            }

            /*  if(!$orderIDs){
                  throw new Exception("指定订单ID");
              }
              */
            if ($orderIDs) {
                $orderIDs = trim($orderIDs);
                $orderIDs = explode(",", rtrim($orderIDs, ','));

                foreach ($orderIDs as $platformOrderId) {
                    $this->actionManualDealOrder($accountID, $platformOrderId, $typeID);
                }
            } else {
                $logModel = new WishLog;
                $eventName = WishLog::EVENT_GETORDER;
                $logId = $logModel->prepareLog($accountID, $eventName);
                if (!$logId) {
                    throw new \Exception(Yii::t('wish', 'System error'));
                }
                //设置日志为正在运行
                $logModel->setRunning($logId);

                $request = new GetOrdersRequest();
                $request->setSinceTime($orderDatePeriod->format('Y-m-d\TH:i:s'));
                $index = 0;
                $wishOrderMain = new WishOrderMain();
                try {
                    while (true) {
                        $request->setStartIndex($index);
                        $response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();

                        if (!$request->getIfSuccess()) {
                            //break;
                            throw new \Exception($request->getErrorMsg());
                        }

                        foreach ($response->data as $order) {//循环订单信息
                            $wishOrderMain->setAccountID($accountID);
                            //add log id
                            $wishOrderMain->setLogID($logId);
                            $success = $wishOrderMain->saveOrderMainData($order->Order);
                            if (!$success) {
                                throw new \Exception($wishOrderMain->getExceptionMessage());

                            }
                            if ($typeID == 2) {//同步到oms
                                $wishOrderMain->syncOrderToOmsByAccountID($accountID, 1,
                                    $order->Order->order_id, 30);
                            }
                        }
                        if (count($response->data) < $request->_limit) {//抓取数量小于每页数量，说明抓完了
                            break;
                        }
                        $index++;
                    }
                    $logModel->setSuccess($logId, 'success');
                }catch (\Exception $e) {
                    $logModel->setFailure($logId, $request->getErrorMsg());
                    throw $e;
                }
            }
            echo $this->successJson(array('message' => '拉取成功'));
        } catch (Exception $e) {
            echo $this->failureJson(array('message' => $e->getMessage()));
        }
        Yii::app()->end();
    }

    /**
     * 拉取单个订单
     * /wish/wishorder/manualdealorder/account_id/73/platform_order_id/586305463318686504759ee0
     */
    public function actionManualDealOrder($accountID = null, $platformOrderId = null, $typeID = 1)
    {

        if (is_null($accountID)) {
            $accountID = Yii::app()->request->getParam('account_id');
        }
        if (is_null($platformOrderId)) {
            $platformOrderId = Yii::app()->request->getParam('platform_order_id');
        }
        if (empty($accountID) || empty($platformOrderId)) {
            exit('No account or no order_id!');
        }

        $logModel = new WishLog;
        $eventName = WishLog::EVENT_GETORDER;
        $logID = $logModel->prepareLog($accountID, $eventName);
        if ($logID) {
            //1.检查账号是否可拉取订单
            $checkRunning = $logModel->checkRunning($accountID, $eventName);
            if (!$checkRunning) {
                $logModel->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
                echo "There Exists An Active Event";
                Yii::app()->end();
            } else {
                //2.准备拉取日志信息
                $timeSince = WishOrderMain::model()->getTimeSince($accountID);

                //插入本次log参数日志(用来记录请求的参数)
                $eventLog = $logModel->saveEventLog($eventName, array(
                    'log_id' => $logID,
                    'account_id' => $accountID,
                    'since_time' => $timeSince,
                    'complete_time' => date('Y-m-d H:i:s', time())//下次拉取时间可以从当前时间点进行,这是北京时间
                ));

                //设置日志为正在运行
                $logModel->setRunning($logID);

                //3.拉取订单
                $orderModel = new RetrieveOrdersRequest;
                $orderModel->setAccount($accountID);
                $orderModel->setOrderId($platformOrderId);
                $response = $orderModel->setRequest()->sendRequest()->getResponse();

                $wishOrderMain = new WishOrderMain();
                $flag = false;
                if (isset($response->data->Order)) {
                    //存数据库
                    $wishOrderMain->setAccountID($accountID);
                    $wishOrderMain->setLogID($logID);
                    $flag = $wishOrderMain->saveOrderMainData($response->data->Order);
                    if ($typeID == 2) {//同步到oms
                        $syncCount = $wishOrderMain->syncOrderToOmsByAccountID($accountID, 1, $platformOrderId, 30);
                    }
                }

                //4.更新日志信息
                if ($flag) {
                    $logModel->setSuccess($logID);
                    $logModel->saveEventStatus($eventName, $eventLog, WishLog::STATUS_SUCCESS);
                } else {
                    $errMsg = $wishOrderMain->getExceptionMessage();
                    if (mb_strlen($errMsg) > 500) {
                        $errMsg = mb_substr($errMsg, 0, 500);
                    }
                    $logModel->setFailure($logID, $errMsg);
                    $logModel->saveEventStatus($eventName, $eventLog, WishLog::STATUS_FAILURE);
                }
            }
        }
    }

    /**
     * @desc 每账号每SKU最近N天销量和总金额统计：
     * 1.该账号该SKU过去7天销量≥25个的
     * 2.该账号该SKU过去9天订单总金额（(单个运费+单价)*数量）≥$500的（包括佣金的500美金）
     * 3.定时任务，每4小时执行一次，每次统计前全清表数据
     * @author Liz
     * @since 2017-01-19
     * @link /wish/wishorder/orderstatistics/
     */
    public function actionOrderstatistics()
    {
        set_time_limit(2 * 3600);
        ini_set("display_errors", true);
        error_reporting(E_ALL);

        $wishOrderStatisticModel = new WishOrderStatistics();
        $delRet = $wishOrderStatisticModel->delAllData();   //清除表所有数据
        if ($delRet) {
            $ret = $wishOrderStatisticModel->addLastOrderStatistics();
            if ($ret) {
                echo('Finish!');
            } else {
                echo('Failure!');
            }
        } else {
            echo('Clear Data Failure!');
        }
        Yii::app()->end();
    }

}