<?php
/**
 * @Shopee order Controller
 * @author yangsh
 * @since 2016-10-24
 */
class ShopeeorderController extends UebController{
	
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
                    'checkorders',
                    'updateomstrans',
                    'updateorderfeesext',
                	'getordertracknum',
                )
            ),
        );
    }

    /**
     * @desc 正常拉取订单
     * @link /shopee/shopeeorder/getorders/account_id/9/show_result/1
     * @author yangsh
     * @since 2016-10-24
     */
	public function actionGetorders(){
        set_time_limit ( 900 );
		ini_set ( 'display_errors', true );
		error_reporting( E_ALL & ~E_STRICT );

		$accountID = trim(Yii::app()->request->getParam('account_id',''));

        if($accountID){
            $logModel = new ShopeeLog();
            $eventName = ShopeeLog::EVENT_GETORDER;
            $logID = $logModel->prepareLog($accountID,$eventName);
            if( empty($logID )) {
                die('prepareLog is failure');
            }
            //1.检查账号是否可拉取订单
            $checkRunning = $logModel->checkRunning($accountID, $eventName);
            if( !$checkRunning ){
                $logModel->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
                die('There Exists An Active Event');
            }

            $model = new ShopeeOrder();
            $model->setAccountID($accountID);//设置账号id
            //$timeArr = $model->setTimeArr()->getTimeArr();
            $timeArr = array(
                'start_time'    => date('Y-m-d H:i:s',time() - 3*3600),//最近3小时内订单
                'end_time'      => date('Y-m-d H:i:s'),
            );
            $model->setTimeArr($timeArr);

            // // 2.插入本次log参数日志(用来记录请求的参数)
            $eventLog = $logModel->saveEventLog ( $eventName, array (
                'log_id'     => $logID,
                'account_id' => $accountID,
                'start_time' => date('Y-m-d H:i:s',strtotime($timeArr['start_time']) ),
                'end_time'   => date('Y-m-d H:i:s',strtotime($timeArr['end_time']) ), 
            ) );            

            //设置日志为正在运行
            $logModel->setRunning($logID);

            //3.拉取订单, mode:1-createTime 2-updateTime
			$flag = $model->setMode(2)
                          ->setLogID($logID)
						  ->getOrders();

            // //4.更新日志信息
            if( $flag ){
                $logModel->setSuccess($logID,'Done');
                $logModel->saveEventStatus ( $eventName, $eventLog, ShopeeLog::STATUS_SUCCESS);
            }else{
                echo $model->getExceptionMessage()."<br>";
                $logModel->setFailure($logID, 'failure');
                $logModel->saveEventStatus ( $eventName, $eventLog, ShopeeLog::STATUS_FAILURE);
            }
            $result = date("Y-m-d H:i:s").'###'.json_encode($_REQUEST).'==='.$flag.'==='.$model->getExceptionMessage();
            echo $result."\r\n<br>";            
        } else {//循环可用账号，多线程抓取
            $accounts = ShopeeAccount::model()->getAbleAccountList();
            foreach($accounts as $account){
                $url = Yii::app()->request->hostInfo.'/' . $this->route . '/account_id/' . $account['id'] ;
                echo $url." <br>\r\n";
                MHelper::runThreadBySocket($url);   
                sleep(6);
            }
        }
        Yii::app()->end('finish');
	}

    /**
     * @desc 补拉订单
     * @link /shopee/shopeeorder/checkorders/account_id/9/show_result/1
     * @author yangsh
     * @since 2016-10-24
     */
	public function actionCheckorders(){
        set_time_limit ( 1800 );
        ini_set ( 'display_errors', true );
        error_reporting( E_ALL & ~E_STRICT );

        $accountID      = trim(Yii::app()->request->getParam('account_id',''));
        $day            = trim(Yii::app ()->request->getParam ( 'day', 7 )); //默认补拉7天
        $startTime      = trim(Yii::app ()->request->getParam ( 'start_time', ''));//2016-06-03
        $endTime        = trim(Yii::app ()->request->getParam ( 'end_time', ''));//2016-06-06
        $mode           = trim(Yii::app ()->request->getParam ( 'mode', 1));//1:createTime 2:updateTime
        $orderStatus    = trim(Yii::app ()->request->getParam ( 'order_status','')); //默认ALL

        if($accountID){
            //准备拉取日志信息
            if ($startTime != '' && $endTime != '') {
                $timeArr['start_time']  = date('Y-m-d H:i:s',strtotime($startTime));
                $timeArr['end_time']    = date('Y-m-d H:i:s',strtotime($endTime));
            } else if ($day > 0) {
                $timeArr['start_time']  = date('Y-m-d H:i:s', time()- $day * 86400 );
                $timeArr['end_time']    = date('Y-m-d H:i:s', time() );
            } else {
                die('time is empty');
            }            
            $logModel = new ShopeeLog();
            $eventName = ShopeeLog::EVENT_CHECK_GETORDER;
            $logID = $logModel->prepareLog($accountID,$eventName);
            if( empty($logID )) {
                die('prepareLog is failure');
            }
            //1.检查账号是否可拉取订单
            $checkRunning = $logModel->checkRunning($accountID, $eventName);
            if( !$checkRunning ){
                $logModel->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
                die('There Exists An Active Event');
            }

            $model = new ShopeeOrder();
            $model->setAccountID($accountID);//设置账号id   
            $model->setOrderStatus($orderStatus);         
            $timeArr = $model->setTimeArr($timeArr)->getTimeArr();

            //设置日志为正在运行
            $logModel->setRunning($logID);

            //3.拉取订单
            $flag = $model->setMode($mode)->setLogID($logID)->getOrders($timeArr);     

            //4.更新日志信息
            if( $flag ){
                $logModel->setSuccess($logID,'Done');
            }else{
                echo $model->getExceptionMessage()."<br>";
                $logModel->setFailure($logID, 'failure');
            }
        } else {//循环可用账号，多线程抓取
            $accounts = ShopeeAccount::model()->getAbleAccountList();
            foreach($accounts as $account){
                $url = Yii::app()->request->hostInfo.'/' . $this->route 
                    . '/account_id/' . $account['id'] 
                    .'/mode/'.$mode
                    . '/day/' . $day
                    . '/order_status/' . $orderStatus
                    . "/start_time/" . $startTime
                    . '/end_time/' . $endTime;
                echo $url." <br>\r\n";
                MHelper::runThreadBySocket($url);   
                sleep(6);
            }
        }
        Yii::app()->end('finish');
	}

    /**
     * @desc 拉单测试
     * @link /shopee/shopeeorder/getordertest/account_id/9/ordersn/16120209385U0S5
     */
    public function actionGetordertest() {
        set_time_limit(3600);

        $accountID = trim($_REQUEST['account_id']);
        $ordersn = trim($_REQUEST['ordersn']);
        if ($ordersn == '' || $accountID == '') {
            echo 'ordersn or account_id is empty';
            exit;
        }
        $request = new GetOrderDetailsRequest();
        $request->setOrdersnList(explode(',',$ordersn));
        $response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
        MHelper::printvar($response,false);
        die('test');        
    }   

    /**
     * @desc 拉取放款信息测试
     * @link /shopee/shopeeorder/getescrowdetailstest/account_id/9/ordersn/16120209385U0S5
     */
    public function actionGetescrowdetailstest() {
        $accountID = trim($_REQUEST['account_id']);
        $ordersn = trim($_REQUEST['ordersn']);
        if ($ordersn == '' || $accountID == '') {
            echo 'ordersn or account_id is empty';
            exit;
        }
        $request = new GetEscrowDetailsRequest();
        $request->setOrdersn($ordersn);
        $response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
        MHelper::printvar($response,false);
        die('test');  
    }   

    /**
     * @desc 更新交易状态
     * @author yangsh
     * @since 2016-12-01
     * @link /shopee/shopeeorder/updateomstrans
     *       /shopee/shopeeorder/updateomstrans/mode/2
     */
    public function actionUpdateomstrans() {
        set_time_limit(1800);
        ini_set("display_errors", true);
        error_reporting( E_ALL & ~E_STRICT);

        $day  = trim(Yii::app ()->request->getParam ( 'day', 30));
        $mode = trim(Yii::app ()->request->getParam ( 'mode', 1));//1. createtime 2.updatetime

        //订单同步
        $orderCount = 0;
        $logModel = new ShopeeLog();
        $eventName = ShopeeLog::EVENT_UPDATE_TRANS_STATUS;
        $virAccountID = 90000;//虚拟账号id
        $logID = $logModel->prepareLog($virAccountID,$eventName);
        if (!$logID) {
            exit("Create LogID Failure!!!");
        }
        $checkRunning = $logModel->checkRunning($virAccountID, $eventName);
        if (!$checkRunning) {
            echo 'Exists An Active Event.<br>';
            $logModel->setFailure($logID, Yii::t('systems', 'Exists An Active Event'));
        }else {
            //设置日志为正在运行
            $logModel->setRunning($logID);
            //开始同步
            $count = ShopeeOrder::model()->updateCODTransactionStatus($day,$mode);
            $orderCount += $count;
            //标识事件成功
            $logModel->setSuccess($logID,'Total:'.$orderCount);
        }
        echo 'orderCount:'.$orderCount."<br>";
        Yii::app()->end('finish');
    }

    /**
     * @desc 更新订单费用扩展表
     * @author yangsh
     * @since 2016-12-01
     * @link /shopee/shopeeorder/updateorderfeesext
     *       /shopee/shopeeorder/updateorderfeesext/mode/2/day/5
     */
    public function actionUpdateorderfeesext() {
        set_time_limit(1800);
        ini_set("display_errors", true);
        error_reporting( E_ALL & ~E_STRICT);

        $day  = trim(Yii::app ()->request->getParam ( 'day'));
        $mode = trim(Yii::app ()->request->getParam ( 'mode', 1));//1. createtime 2.updatetime
        if(empty($day)) {
            $day = 1;
        }

        //订单同步
        $orderCount = 0;
        $logModel = new ShopeeLog();
        $eventName = ShopeeLog::EVENT_UPDATE_ORDERFEESEXT;
        $virAccountID = 60000;//虚拟账号id
        $logID = $logModel->prepareLog($virAccountID,$eventName);
        if (!$logID) {
            exit("Create LogID Failure!!!");
        }
        $checkRunning = $logModel->checkRunning($virAccountID, $eventName);
        if (!$checkRunning) {
            echo 'Exists An Active Event.<br>';
            $logModel->setFailure($logID, Yii::t('systems', 'Exists An Active Event'));
        }else {
            //设置日志为正在运行
            $logModel->setRunning($logID);
            //开始同步
            $count = ShopeeOrder::model()->updateOrderFeesExt($day,$mode);
            $orderCount += $count;
            //标识事件成功
            $logModel->setSuccess($logID,'Total:'.$orderCount);
        }
        echo 'orderCount:'.$orderCount."<br>";
        Yii::app()->end('finish');
    }    

    /**
     * @desc 按日期导出货到付款订单的数据
     * @link /shopee/shopeeorder/allorders
     * @author hanxy
     * @since 2017-02-17
     */
    public function actionAllorders(){
        set_time_limit ( 3600 );
        ini_set ( 'display_errors', true );
        error_reporting( E_ALL & ~E_STRICT );

        $getParams = Yii::app()->request->getParam('updated_at');
        if($getParams){
            $timeArr['start_time']  = date('Y-m-d H:i:s',strtotime($getParams[0]));
            $timeArr['end_time']    = date('Y-m-d H:i:s',strtotime($getParams[1]));
        } else {
            $timeArr['start_time']  = date('Y-m-01 00:00:00', strtotime('-1 month'));
            $timeArr['end_time']    = date('Y-m-d 00:00:00');
        }

        $str = "平台订单号,CO订单号,交易金额,交易币种,交易时间,订单状态,更新时间\n";   

        $accounts = ShopeeAccount::model()->getAbleAccountList();
        foreach($accounts as $account){
            $accountID = $account['id'];
            //$accountInfo = ShopeeAccount::getAccountInfoById($accountID);
            $startTime   = date('YmdHis', strtotime($timeArr['start_time']));
            $endTime     = date('YmdHis', strtotime($timeArr['end_time']));
            $request     = new GetOrdersListFromLocalRequest();
            $request->setPlatformUpdateTimeFrom($startTime);
            $request->setPlatformUpdateTimeTo($endTime);    
            //抓取订单信息
            $page = 1;
            while( $page <= ceil($request->pageCount/$request->pageSize) ){
                $request->setPageNum($page);
                $response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
                if (!$request->getIfSuccess()) {
                    break;
                }
                if ( !isset($response->result) || !isset($response->result->total) ) {
                    break;
                }
                if ( intval($response->result->total) <= 0 || empty($response->result->list) ) {
                    break;
                }
                $request->pageCount = $response->result->total;
                $page++;

                foreach ($response->result->list as $order){
                    if($order->paymentMethod != 'PAY_COD'){
                        continue;
                    }

                    $orderCreateTime = str_replace(array('T','Z'), ' ', $order->orderCreateTime);
                    $orderUpdateTime = str_replace(array('T','Z'), ' ', $order->orderUpdateTime);
                    
                    $orders = Order::model()->getOrderInfoByPlatformOrderID($order->ordersn,Platform::CODE_SHOPEE);
                    $coOrderID = isset($orders['order_id'])?$orders['order_id']:'';
                    $str .= "\t".$order->ordersn.",".$coOrderID.",".$order->totalAmount.",".$order->currency.",\t".$orderCreateTime.",".$order->orderStatus.",\t".$orderUpdateTime."\n";
                }
            }
        }
        
        //导出文档名称
        $exportName = 'shopee_货到付款订单明细_导出表'.date('Y-m-dHis').'.csv';

        $this->export_csv($exportName,$str);

        Yii::app()->end();
    }	
    
    /**
     * @author cxy
     * @since  2017-2-28
     * 获取订单的跟踪号
     * /shopee/shopeeorder/getordertracknum
     */
    public function actionGetordertracknum() {
    	set_time_limit(1800);
    	ini_set("display_errors", true);
    	error_reporting( E_ALL & ~E_STRICT);
    
    	$packageId = trim(Yii::app ()->request->getParam ('package_id'));
    	$test      = trim(Yii::app ()->request->getParam ('test'));
    	$shipCodeArr = array('kd_e_ytdp'=>'圓通-711超取','kd_ytdp'=>'圓通-711超取','kd_ytzp'=>'圓通-黑貓宅配','kd_e_ytzp'=>'圓通-黑貓宅配');
    	$packages =	ShopeeOrder::model()->getMultipleOrderItems('tw',array_keys($shipCodeArr),$packageId);
    	if($test) var_dump($packages);
    	if($packages){
    		$orderCount = 0;
    		$logModel = new ShopeeLog();
    		$eventName = ShopeeLog::EVENT_GET_ORDER_TRACK_NUM;
    		$virAccountID = 900012;//虚拟账号id
    		$logID = $logModel->prepareLog($virAccountID,$eventName);
    		if (!$logID) {
    			exit("Create LogID Failure!!!");
    		}
    		$checkRunning = $logModel->checkRunning($virAccountID, $eventName);
    		if (!$checkRunning) {
    			echo 'Exists An Active Event.<br>';
    			$logModel->setFailure($logID, Yii::t('systems', 'Exists An Active Event'));
    		}else {
    			//设置日志为正在运行
    			$logModel->setRunning($logID);
    			$orderArr = array();
    			$packageToPlatformOrder = array();
    			foreach ($packages as $list){
    				$orderArr[$list['account_id']][]= $list['platform_order_id'];
    				$packageToPlatformOrder[$list['platform_order_id']]['package_id']     = $list['package_id'];
    				$packageToPlatformOrder[$list['platform_order_id']]['ship_code']      = $list['ship_code'];
    			}
    			
    			//$url = 'http://172.16.6.161:8091/order/trackingno';
    			//开始获取订单跟踪号，分账号获取
    		//	echo '<pre>';print_r($orderArr);die;
    			foreach ($orderArr as $accountId=>$orders){
    				
    				$data = array('platform'=>'SHOPEE','account'=>$accountId,'orderNos'=>$orders);
    				//$count = ShopeeOrder::model()->getTracknumByOrderIdCurlPost($data,$url);
    				$count = ShopeeOrder::model()->getTracknumByOrderId($data,$accountId,$packageToPlatformOrder,$shipCodeArr);
    				$orderCount += $count;
    				//var_dump($count);die;
    			}
    			
    			
    			//标识事件成功
    			$logModel->setSuccess($logID,'Total:'.$orderCount);
    		}
    		echo 'orderCount:'.$orderCount."<br>";
    		Yii::app()->end('finish');
    	}else{
    		echo '暂无数据';
    	}
    }

}