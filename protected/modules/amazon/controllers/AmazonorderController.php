<?php
/**
 * @desc 亚马逊拉单处理控制器
 * @author zhangF
 *
 */
class AmazonorderController extends UebController {
    /**
     * @desc 访问过滤配置
     * @see CController::accessRules()
     */
    public function accessRules() {
        return array(
			array(
				'allow',
				'users' => array('*'),
				'actions' => array(
                    'getordersnew', //new
                    'checkgetorders', //new
                    'Getorderinfo',//new
                    'getorderitems', //new
                    'listorderitems',//new
                    'syncorder', //new
                    'restoreRequestCount',
                )
			),
		);
    }
	
    /**
     * @desc 获取订单主信息,6-1m-1
     * @link /amazon/amazonorder/getordersnew/account_id/10
     * @since 2016-12-08
     * @author yangsh
     */
    public function actionGetordersnew() {
        set_time_limit(3*3600);
        ini_set("display_errors", true);
        error_reporting(E_ALL & ~E_STRICT);

        $accountID = trim(Yii::app()->request->getParam('account_id',''));
        if( $accountID ){
            $eventName = AmazonLog::EVENT_GETORDER;
            $amazonLogModel = new AmazonLog;
            $logID = $amazonLogModel->prepareLog($accountID, $eventName);
            if( empty($logID) ){
                echo $accountID." prepareLog failure";
                Yii::app()->end();
            }
            //1.检查账号是否可拉取订单
            $checkRunning = $amazonLogModel->checkRunning($accountID, $eventName);
            if( !$checkRunning ){
                $amazonLogModel->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
                echo $accountID." There Exists An Active Event";
            }else{
                $amazonOrderModel = new AmazonOrderMain();
                //2.准备拉取日志信息
                $amazonOrderModel->setAccountID($accountID);//设置账号
                $timeArr = $amazonOrderModel->getTimeArr($eventName);//返回北京时间
                MHelper::printvar($timeArr,false);                
                
                //插入本次log参数日志(用来记录请求的参数)
                $eventLog = $amazonLogModel->saveEventLog($eventName, array(
                    'log_id'        => $logID,
                    'account_id'    => $accountID,
                    'start_time'    => $timeArr['start_time'],//UTC时间
                    'end_time'      => $timeArr['end_time'],//UTC时间
                ));

                //设置日志为正在运行
                $amazonLogModel->setRunning($logID);

                //3.拉取订单
                $amazonOrderModel->setLogID($logID);//设置日志编号
                $amazonOrderModel->setFromCode('getorders');
                $amazonOrderModel->setMode(2);//设置时间条件,按更新时间
                $flag1 = $amazonOrderModel->getOrders($eventName,$timeArr);//拉单

                //4.更新日志信息
                if( $flag1){
                    $amazonLogModel->setSuccess($logID);
                    $amazonLogModel->saveEventStatus($eventName, $eventLog, AmazonLog::STATUS_SUCCESS);
                }else{
                    $errMessage = $amazonOrderModel->getExceptionMessage();
                    if (mb_strlen($errMessage)>500) {
                        $errMessage = mb_substr($errMessage,0,500);
                    }
                    $amazonLogModel->setFailure($logID, $errMessage);
                    $amazonLogModel->saveEventStatus($eventName, $eventLog, AmazonLog::STATUS_FAILURE);
                }
                
                $flag = $flag1 ? 'Success' : 'Failure';
                $result = date("Y-m-d H:i:s").'###'.json_encode($_REQUEST).'==='.$flag.'==='.$amazonOrderModel->getExceptionMessage();
                echo $result."\r\n<br>";
            }
        }else{//循环可用账号，多线程抓取
            $amazonAccounts = AmazonAccount::model()->getAbleAccountList();
            foreach($amazonAccounts as $account){
                MHelper::runThreadSOCKET('/'.$this->route.'/account_id/'.$account['id'] );
                sleep(60);
            }
        }
        exit('DONE');
    }
 
    /**
     * @desc 补拉单
     * @link /amazon/amazonorder/checkgetorders/account_id/10/day/3
     * @since 2016-12-08
     * @author yangsh
     */
    public function actionCheckgetorders() {
        set_time_limit(5*3600);
        ini_set("display_errors", true);
        error_reporting(E_ALL & ~E_STRICT);

        $accountID   = trim(Yii::app()->request->getParam('account_id',''));
        $mode        = trim(Yii::app()->request->getParam('mode',2));//1:CreatedAfter 2:LastUpdatedAfter
        $day         = trim(Yii::app()->request->getParam('day',3));//3天
        $startTime   = trim(Yii::app()->request->getParam('start_time',''));
        $endTime     = trim(Yii::app()->request->getParam('end_time',''));
        $orderStatus = trim(Yii::app()->request->getParam('order_status',''));//默认所有状态
        $channel     = trim(Yii::app()->request->getParam('channel',''));//可选
        $offTime     = 8 * 3600;//UTC与北京时间相差8小时间

        if( $accountID ){
            $eventName = AmazonLog::EVENT_CHECK_GETORDER;
            $amazonLogModel = new AmazonLog;
            $logID = $amazonLogModel->prepareLog($accountID, $eventName);
            if( empty($logID) ){
                echo $accountID." prepareLog failure";
                Yii::app()->end();
            }
            //1.检查账号是否可拉取订单
            $checkRunning = $amazonLogModel->checkRunning($accountID, $eventName);
            if( !$checkRunning ){
                $amazonLogModel->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
                exit("There Exists An Active Event");
            }else{
                $amazonOrderModel = new AmazonOrderMain();
                //2.准备拉取日志信息
                $amazonOrderModel->setAccountID($accountID);//设置账号
                if($startTime != '' && $endTime != ''){ 
                    $timeArr = array(
                        'start_time'=> date("Y-m-d H:i", strtotime($startTime) - $offTime ),
                        'end_time'  => date("Y-m-d H:i", strtotime($endTime) - $offTime )
                    );
                    $amazonOrderModel->setFromCode('checkgetorders_'. ceil( (strtotime($endTime) - strtotime($startTime))/60 ) );
                } else {
                    $timeArr = array(
                        'start_time'=>  date("Y-m-d 00:00:00", time()- $day*86400 - $offTime ),
                        'end_time'  =>  date("Y-m-d H:i:s", time() - $offTime - 5*60 )
                    );
                    $amazonOrderModel->setFromCode('checkgetorders_'.$day);
                }
                MHelper::printvar($timeArr,false);

                //设置日志为正在运行
                $amazonLogModel->setRunning($logID);

                //3.拉取订单
                $amazonOrderModel->setLogID($logID);//设置日志编号
                if ($orderStatus != '') {
                    $amazonOrderModel->setOrderStatus(explode(',',$orderStatus));
                }
                if($channel != '') {
                    $amazonOrderModel->setFulfillmentChannel($channel);
                }
                $amazonOrderModel->setMode($mode);//设置时间条件
                $flag1 = $amazonOrderModel->getOrders($eventName,$timeArr);//拉单
                
                //4.更新日志信息
                if( $flag1){
                    $amazonLogModel->setSuccess($logID);
                }else{
                    $errMessage = $amazonOrderModel->getExceptionMessage();
                    if (mb_strlen($errMessage)>500) {
                        $errMessage = mb_substr($errMessage,0,500);
                    }
                    $amazonLogModel->setFailure($logID, $errMessage);
                }

                $flag = $flag1 ? 'Success' : 'Failure';
                $result = date("Y-m-d H:i:s").'###'.json_encode($_REQUEST).'==='.$flag.'==='.$amazonOrderModel->getExceptionMessage();
                echo $result."\r\n<br>";
            }
        }else{//循环可用账号，多线程抓取
            $amazonAccounts = AmazonAccount::model()->getAbleAccountList();
            foreach($amazonAccounts as $account){
                $url = '/'.$this->route.'/account_id/'.$account['id']
                    . '/mode/'.$mode . '/day/'.$day. '/start_time/'.$startTime.'/end_time/'.$endTime
                    . '/order_status/'.$orderStatus
                    . '/channel/'.$channel;
                MHelper::runThreadSOCKET($url);
                sleep(120);
            }
        }
        exit('DONE');
    }

    /**
     * @desc 获取指定订单
     * @link /amazon/amazonorder/Getorderinfo/account_id/44/order_id/116-7092367-5269805
     */
    public function actionGetorderinfo(){
        set_time_limit(2*3600);
        ini_set("display_errors", true);
        error_reporting(E_ALL & ~E_STRICT);

        $orderIds = Yii::app()->request->getParam('order_id');
        $accountID = Yii::app()->request->getParam('account_id');
        if(!$orderIds) exit("Not Specify Order ID");
        if(!$accountID) exit("Not Specify Account ID");
        $orderIdArr = explode(",", $orderIds);
        foreach ($orderIdArr as $orderId){
            $getOrderRequest = new GetOrderRequest;
            $getOrderRequest->setAccount($accountID);
            $getOrderRequest->setCaller("getorder");
            $getOrderRequest->setOrderId($orderId);
            $response = $getOrderRequest->setRequest()->sendRequest()->getResponse();
            $this->print_r($response);
            echo $getOrderRequest->getErrorMsg();
            $amazonOrder = new AmazonOrderMain();
            $amazonOrder->setAccountID($accountID);
            $amazonOrder->setLogID(10000);
            $amazonOrder->setFromCode('getorderinfo');
            $res = false;
            if($getOrderRequest->getIfSuccess() && $response){
                $res = $amazonOrder->saveOrderMain($response);
            }
            echo $res ? "success" : "failure:".$amazonOrder->getExceptionMessage();     
        }
    }    

    /**
     * @desc 获取订单明细,30-2s-1
     * @link /amazon/amazonorder/getorderitems/account_id/10/order_id/109-0928589-3300255/debug/1
     * @since 2016-12-08
     * @author yangsh
     */
    public function actionGetorderitems() {
        set_time_limit(3*3600);
        ini_set ( 'display_errors', true );
        error_reporting( E_ALL & ~E_STRICT);
        
        $accountID = trim(Yii::app()->request->getParam("account_id",''));
        $orderIds  = trim(Yii::app ()->request->getParam ( 'order_id', ''));
        
        $eventName = AmazonLog::EVENT_ORDERITEMS;//拉取订单明细事件
        if ($accountID != '' && $orderIds != '') {
            $path  = 'amazon/getorderitems/'.date("Ymd").'/'.$accountID;
            $model = new AmazonOrderMain();
            $model->setAccountID($accountID);
            foreach (explode(',',$orderIds) as $orderId) {
                if ($orderId == '') continue;
                $isOk = $model->getOrderItems($eventName,$orderId);
                $flag = $isOk ? 'Success' : 'Failure';
                $result = date("Y-m-d H:i:s").'###'.json_encode($_REQUEST).'==='.$flag.'==='.$model->getExceptionMessage();
                echo $result."\r\n<br>";                
            }
        } else {
            $dataList = AmazonOrderMain::model()->getNeedDownDetailOrders($accountID);
            if (empty($dataList)) {
                Yii::app ()->end('没有可抓取的订单数据');
            }
            $total = 0;
            $accountIDs = array_keys($dataList);
            $accountIdGroupData = MHelper::getGroupData($accountIDs,5);
            foreach ($accountIdGroupData as $accountIdGroup) {
                foreach ($accountIdGroup as $account_id) {
                    $sucCount = 0;
                    $logModel = new AmazonLog();      
                    $orderIdGroupData = MHelper::getGroupData($dataList[$account_id],15);
                    foreach ($orderIdGroupData as $orderIdArr) {
                        $total += count($orderIdArr);
                        foreach ($orderIdArr as $orderId) {
                            $model = new AmazonOrderMain();
                            $model->setAccountID($account_id);
                            $isOk = $model->getOrderItems($eventName,$orderId);
                            if($isOk) {
                                $sucCount++;
                            }
                            $flag = $isOk ? 'Success' : 'Failure';
                            echo 'amazonOrderId: '.$orderId.'==='.$model->getExceptionMessage()."<br>";
                        }
                    }
                    //add log
                    $logModel->getDbConnection()->createCommand()->insert($logModel->tableName(), 
                        array(
                            'account_id'    => $account_id,
                            'event'         => $eventName,
                            'start_time'    => date('Y-m-d H:i:s'),                         
                            'status'        => AmazonLog::STATUS_SUCCESS,
                            'message'       => 'Total:'.$sucCount,
                            'response_time' => date('Y-m-d H:i:s'),
                            'end_time'      => date('Y-m-d H:i:s'),
                            'create_user_id'=> intval(Yii::app()->user->id),
                        )
                    );
                    $result = date("Y-m-d H:i:s").'###'.$account_id.'=='.json_encode($_REQUEST).'==='.$flag.'==='.count($dataList[$account_id]).'sucCount ==='.$sucCount;
                    echo $result."\r\n<br>";                    
                }
            }            
            echo $total. " OrderIds sent!<br>\r\n";
        }
        Yii::app ()->end('finish');
    }

    /**
     * @desc 获取订单明细 -- fortest
     * @link /amazon/amazonorder/listorderitems/account_id/62/order_id/250-8668780-5147853
     */
    public function actionListorderitems(){
        $orderId = Yii::app()->request->getParam("order_id",'');
        $accountId = Yii::app()->request->getParam("account_id",'');
        if($orderId=='' || $accountId ==''){
            die('order_id or account_id is empty');
        }
        $request = new ListOrderItemsRequest();
        $request->setOrderId($orderId);
        $request->setCaller("ss");
        $response = $request->setAccount($accountId)->setRequest()->sendRequest()->getResponse();
        $this->print_r($response);
        if (empty($response)) {
            echo $request->getErrorMsg();               
        }
        exit('finish');        
    }


    /**
     * @desc 同步订单到OMS系统
     * @link /amazon/amazonorder/syncorder/platform_order_id/3-7-7
     * @since 2016-12-08
     * @author yangsh
     */
    public function actionSyncorder() {
        set_time_limit(3600);
        ini_set("display_errors", true);
        error_reporting(E_ALL & ~E_STRICT);

        $accountID = trim(Yii::app()->request->getParam("account_id",''));
        $platformOrderID = trim(Yii::app()->request->getParam("platform_order_id",''));
        $limit = intval(Yii::app()->request->getParam("limit", 500));
        
        $syncTotal = 0;
        $logID = 1000;
        //@todo 增加日志控制
        $logModel = new AmazonLog();
        $virAccountID = 90000;
        $eventName = AmazonLog::EVENT_SYNC_ORDER;
        $logID = $logModel->prepareLog($virAccountID, $eventName);
        if($logID){
            $checkRunning = $logModel->checkRunning($virAccountID, $eventName);
            if(!$checkRunning){
                $logModel->setFailure($logID, "Have a active event");
                exit("Have a active event");
            }
            //设置日志为正在运行
            $logModel->setRunning($logID);
            $amazonAccounts = AmazonAccount::model()->getAbleAccountList();
            $errMessage = '';
            foreach($amazonAccounts as $account){
                if(!empty($accountID) && $account['id'] != $accountID){
                    continue;
                }
                $amazonOrderModel = new AmazonOrderMain();
                $amazonOrderModel->setAccountID($account['id']);
                $amazonOrderModel->setLogID($logID);
                $syncCount = $amazonOrderModel->syncOrderToOmsByAccountID($account['id'], $limit, $platformOrderID);
                $syncTotal += $syncCount;
                $errMessage .= $amazonOrderModel->getExceptionMessage();
                echo "<br>======={$account['account_name']}: {$syncCount}======".$amazonOrderModel->getExceptionMessage()."<br/>";
                if ($platformOrderID && $syncCount>0 ) {//指定订单号同步，如果已找到则退出
                    break;
                }
            }
            if (mb_strlen($errMessage)>1000) {
                $errMessage = mb_substr($errMessage,0,1000);
            }
            $logModel->setSuccess($logID, 'Total:'.$syncTotal);
            exit("finish");
        }else{
            exit("Create Log Id Failure!!!");
        }
    }

    /**
     * @DESC 恢复请求数量
     * @link /amazon/amazonorder/restoreRequestCount
     */
    public function actionRestoreRequestCount(){
        set_time_limit(3600);
        ini_set("display_errors", true);
        error_reporting(E_ALL);
        $accountID = "10";//test
        $eventName = "restore_request_count";
        $logID = AmazonLog::model()->prepareLog($accountID, $eventName);
        $starttime = time();
        $endtime = $starttime+3600;
        if( $logID ){
            //1.检查账号是否可拉取订单
            $checkRunning = AmazonLog::model()->checkRunning($accountID, $eventName);
            if( !$checkRunning ){
                AmazonLog::model()->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
                exit("There Exists An Active Event");
            }else{
                AmazonLog::model()->setRunning($logID);
                while(time() < $endtime){
                    $amazonApiRequestPool = new AmazonAPIRequestPool();
                    $amazonApiRequestPool->restoreRequestCount();
                    sleep(2);
                }
                AmazonLog::model()->setSuccess($logID);
            }
        }
        echo "done";
    }

}