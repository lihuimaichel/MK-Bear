<?php
/**
 * @desc paytm订单控制器
 * @since 2017-02-28
 *
 */
class PaytmorderController extends UebController {

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
					'getorders',
					'checkgetorders',
					'getorderinfo',
					'syncorder',
				)
			),
		);
    }

	/**
	 * @desc 正常拉单
	 * @link /paytm/paytmorder/getorders/debug/1/account_id/1
	 */
	public function actionGetorders() {
        set_time_limit(3600);
        ini_set("display_errors", true);
        error_reporting(E_ALL & ~E_STRICT);
         
        $accountID = trim(Yii::app()->request->getParam('account_id',''));
        if( $accountID ){//根据账号抓取订单信息
            $logModel = new PaytmLog;
            $eventName = PaytmLog::EVENT_GETORDER;//getordersnew事件
            $logID = $logModel->prepareLog($accountID,$eventName);
            if( $logID ){
                //1.检查账号是否可拉取订单
                $checkRunning = $logModel->checkRunning($accountID, $eventName);
                if( !$checkRunning ){
                    $logModel->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
                    echo "There Exists An Active Event";
                }else{
                    //2.准备拉取日志信息
                    $timeSince = PaytmOrderMain::model()->getTimeSince($accountID);

                    //插入本次log参数日志(用来记录请求的参数)
                    $eventLog = $logModel->saveEventLog($eventName, array(
                        'log_id'        => $logID,
                        'account_id'    => $accountID,
                        'since_time'    => date('Y-m-d H:i:s',strtotime($timeSince)),//北京时间
                        'complete_time' => date('Y-m-d H:i:s')//下次拉取时间可以从当前时间点进行,这是北京时间
                    ));

                    //设置日志为正在运行
                    $logModel->setRunning($logID);

                    //3.拉取订单
                    $paytmOrderMain = new PaytmOrderMain();
                    $paytmOrderMain->setAccountID($accountID);//设置账号
                    $paytmOrderMain->setLogID($logID);//设置日志编号
                    $startTime = strtotime($timeSince) - 8*3600;//UTC
                    $endTime = time();
                    $flag = $paytmOrderMain->getOrders($startTime.'000',$endTime.'000');//拉单

                    //4.更新日志信息
                    if( $flag ){
                        $logModel->setSuccess($logID);
                        $logModel->saveEventStatus($eventName, $eventLog, PaytmLog::STATUS_SUCCESS);
                    }else{
                        $errMsg = $paytmOrderMain->getExceptionMessage();
                        if (mb_strlen($errMsg)> 500 ) {
                            $errMsg = mb_substr($errMsg,0,500);
                        }                     
                        $logModel->setFailure($logID, $errMsg);
                        $logModel->saveEventStatus($eventName, $eventLog, PaytmLog::STATUS_FAILURE);
                    }
                    echo json_encode($_REQUEST).($flag ? ' Success ' : ' Failure ').$paytmOrderMain->getExceptionMessage()."<br>";                    
                }
            }
        }else{//循环可用账号，多线程抓取
            $paytmAccounts = PaytmAccount::model()->getAbleAccountList();
            foreach($paytmAccounts as $account){
                $url = Yii::app()->request->hostInfo.'/'.$this->route
                        .'/account_id/'.$account['id'];
                echo $url." <br>\r\n";
                MHelper::runThreadBySocket($url);
                sleep(60);
            }
        }
        Yii::app()->end('finish');
	}

	/**
	 * @desc 补拉单
	 * @link /paytm/paytmorder/checkgetorders/debug/1/account_id/1/since_time/2017-03-01
	 */
	public function actionCheckgetorders() {
		set_time_limit(3600);
        ini_set("display_errors", true);
        error_reporting(E_ALL & ~E_STRICT);
        
        $accountID = trim(Yii::app()->request->getParam('account_id',''));
        $timeSince = trim(Yii::app()->request->getParam('since_time',''));//2016-11-12T10:30:00
        $day = trim(Yii::app()->request->getParam('day',3));
        $day == '' && $day = 3;//默认3天
        if($timeSince != '') {
        	$sinceTime = strtotime($timeSince);
        } else if($day > 0) {
        	$sinceTime = time()-$day*86400;
        }
        if( $accountID ){//根据账号抓取订单信息
			$logModel = new PaytmLog;
            $eventName = PaytmLog::EVENT_CHECK_GETORDER;//补拉订单事件
            $logID = $logModel->prepareLog($accountID,$eventName);
            if( $logID ){
                //1.检查账号是否可拉取订单
                $checkRunning = $logModel->checkRunning($accountID, $eventName);
                if( !$checkRunning ){
                    $logModel->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
                    echo "There Exists An Active Event";
                }else{       
	                //设置日志为正在运行
	                $logModel->setRunning($logID);
	                //拉取订单
	                $interval = 3600;//秒
	        		$times = ceil((time() - $sinceTime)/$interval);//每小时抓一次,每次最多返回500个单
	        		$errMsg = '';
	        		$isOk = true;
                    echo 'sinceTime: '. date('Y-m-d H:i:s',$sinceTime)."<br>";
                    echo 'times: '. $times."<br>";
	        		for($i=0; $i<$times; $i++) {
                        $startTime = time() - $interval * ($times-$i);
                        $endTime = $startTime + $interval;
                        $startTime = $startTime - 2*60;//每次多拉2分钟
                        echo 'startTime: '.date('Y-m-d H:i:s',$startTime). ' '. $startTime ."<br>";
                        echo 'endTime: '.date('Y-m-d H:i:s',$endTime). ' '. $endTime . "<br>";
		                $paytmOrderMain = new PaytmOrderMain();
		                $paytmOrderMain->setAccountID($accountID);//设置账号
		                $paytmOrderMain->setLogID($logID);//设置日志编号
		                $flag = $paytmOrderMain->getOrders($startTime.'000',$endTime.'000');//拉单
		                $isOk &= $flag;
		                if( !$flag ){
		                	$errMsg .= $paytmOrderMain->getExceptionMessage();
		                }
		            }
					//更新日志信息
                    $errMsg = mb_strlen($errMsg)> 500 ? mb_substr($errMsg,0,500) : $errMsg;   
	                if( $isOk ){
	                    $logModel->setSuccess($logID);
	                }else{        
	                    $logModel->setFailure($logID, $errMsg);
	                }
	                echo json_encode($_REQUEST).($isOk ? ' Success ' : ' Failure ').$errMsg."<br>";  
		        }
        	}
        }else{//循环可用账号，多线程抓取
            $paytmAccounts = PaytmAccount::model()->getAbleAccountList();
            foreach($paytmAccounts as $account){
                $url = Yii::app()->request->hostInfo.'/'.$this->route
                        .'/account_id/'.$account['id']
                        .'/since_time/'.$timeSince
                        .'/day/'.$day;
                echo $url." <br>\r\n";
                MHelper::runThreadBySocket($url);
                sleep(120);
            }
        }
        Yii::app()->end('finish');
	}

	/**
	 * @desc 单个拉单
	 * @link /paytm/paytmorder/getorderinfo/debug/1/account_id/1/order_id/##
	 */
	public function actionGetorderinfo() {
		ini_set("display_errors", true);
        error_reporting(E_ALL & ~E_STRICT);

		$orderIds = Yii::app()->request->getParam("order_id");
    	$accountId = Yii::app()->request->getParam("account_id");
    	if(!$orderIds){
    		exit("no orderID");
    	}
    	if(!$accountId){
    		exit("no accountID");
    	}
    	$orderIds = explode(",", $orderIds);
    	//拉单
		$request = new GetOrdersRequest();
        $request->setOrderIDs($orderIds);
        $response = $request->setAccount($accountId)->setRequest()->sendRequest()->getResponse();
        $this->print_r($response);
		if($request->getIfSuccess() && !empty($response)){
			$flag = true;
            $errorMsg = "";
            foreach($response as $order) {//循环订单信息
                $paytmOrderModel = new PaytmOrderMain();
				$paytmOrderModel->setLogID(10000);
				$paytmOrderModel->setAccountID($accountId);//设置账号
                $res = $paytmOrderModel->savePaytmOrder($order);
                $flag &= $res;
                if(!$res){
                    $errorMsg .= $paytmOrderModel->getExceptionMessage()."<br/>";
                }
            }         
	        echo "<br>".$errorMsg."<br>";
		}	
		echo "finish";	
	}

	/**
	 * @desc 同步订单
	 * @link /paytm/paytmorder/syncorder/debug/1/account_id/1/order_id/##
	 */
	public function actionSyncorder() {
        set_time_limit(1800);
        ini_set("display_errors", true);
        error_reporting(E_ALL & ~E_STRICT);
        
        $limit = Yii::app()->request->getParam("limit", 1000);
        $accountID = trim(Yii::app()->request->getParam("account_id",''));
        $platformOrderID = trim(Yii::app()->request->getParam("order_id",''));
        
        $syncTotal = 0;
        $logID = 1000;
        //@todo 增加日志控制
        $logModel = new PaytmLog();
        $virAccountID = 90000;
        $eventName = PaytmLog::EVENT_SYNC_ORDER;
        $logID = $logModel->prepareLog($virAccountID, $eventName);
        if($logID){
            $checkRunning = $logModel->checkRunning($virAccountID, $eventName);
            if(!$checkRunning){
                $logModel->setFailure($logID, "Have a active event");
                exit("Have a active event");
            }
            //设置日志为正在运行
            $logModel->setRunning($logID);
            $paytmAccounts = PaytmAccount::model()->getAbleAccountList();
            foreach($paytmAccounts as $account){
                if(!empty($accountID) && $account['id'] != $accountID){
                    continue;
                }
                $paytmOrderMain = new PaytmOrderMain();
                $paytmOrderMain->setAccountID($account['id']);
                $paytmOrderMain->setLogID($logID);
                $syncCount = $paytmOrderMain->syncOrderToOmsByAccountID($account['id'], $limit, $platformOrderID);
                $syncTotal += $syncCount;
                echo "<br>======={$account['account_name']}: {$syncCount}======".$paytmOrderMain->getExceptionMessage()."<br/>";
            }
            $logModel->setSuccess($logID, 'Total:'.$syncTotal);
            echo "finish";
        }else{
            exit("Create Log Id Failure!!!");
        }
	}

    /**
     * @desc 手工确认订单
     * @link /paytm/paytmorder/ackOrder/debug/1/account_id/1/platform_order_id/##
     */
    public function actionAckOrder() {
        set_time_limit(600);
        ini_set("display_errors", true);
        error_reporting(E_ALL & ~E_STRICT);

        $accountID = trim(Yii::app()->request->getParam("account_id",''));
        $platformOrderID = trim(Yii::app()->request->getParam("platform_order_id",''));
        if($accountID == '') {
            die('account_id is empty');
        }
        if($platformOrderID == '') {
            die('platform_order_id is empty');
        }        
        $paytmOrderInfo = PaytmOrderMain::model()->getOneByCondition('id,platform_order_id',"account_id='{$accountID}' and platform_order_id='{$platformOrderID}' and is_to_oms=1 " );
        if(empty($paytmOrderInfo)) {
            die('paytmOrderInfo is empty');
        }
        $orderDetailInfos = PaytmOrderDetail::model()->getListByCondition('*',"order_id='{$paytmOrderInfo['id']}' and status=2 " );
        if(empty($orderDetailInfos)) {
            continue;
        }
        $request = new AcknowledgeOrderRequest($platformOrderID);
        $orderItemIdArr = array();
        foreach ($orderDetailInfos as $detail) {
            $orderItemIdArr[] = $detail['order_item_id'];
        }
        for($i=0;$i<3;$i++) {
            $request->setOrderItemIDs($orderItemIdArr);
            $request->setAccount($accountID);
            $request->setStatus(AcknowledgeOrderRequest::ACK_ACCEPT);
            $response = $request->setRequest()->sendRequest()->getResponse();
            if(!empty($_REQUEST['debug'])){
                MHelper::printvar($response,false);
            }
            if(!$request->getIfSuccess()) {//失败请求3次
                $i++;
                sleep(3);
                continue;
            }
            echo $request->getIfSuccess() && $response ? true : false;
            break;//一次成功就退出
        }  
        die('ok'); 
    }

}