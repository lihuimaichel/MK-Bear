<?php
/**
 * @desc Joom订单相关
 * @author Gordon
 * @since 2015-06-02
 */
class JoomorderController extends UebController{
    
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
                    'getordersnew',//new
                    'checkgetordersnew',//new
                    'syncorder',//new                    
                )
			),
		);
    }
    
    /**
     * @desc 抓取最近变动的订单 --- test
     */
    public function actionGetchangeorders(){
    	$changeOrder = new GetChangeOrdersRequest;
    	set_time_limit(3600);
    	$accountID = Yii::app()->request->getParam('account_id');
    	$sinceTime = Yii::app()->request->getParam('since_time');
    	if( $accountID ){//根据账号抓取订单信息
    		$changeOrder->setAccount($accountID);
    		$changeOrder->setStartIndex(0);
    		if($sinceTime)
    			$changeOrder->setSinceTime($sinceTime);
    		$response = $changeOrder->setRequest()->sendRequest()->getResponse();
    		echo $changeOrder->getErrorMsg();
    		echo "<pre>";
    		print_r($response);
    		echo "</pre>";
    	}else{//循环可用账号，多线程抓取
    		$joomAccounts = JoomAccount::model()->getAbleAccountList();
    		foreach($joomAccounts as $account){
    			MHelper::runThreadSOCKET('/'.$this->route.'/account_id/'.$account['id']);
    			sleep(1);
    		}
    	}
    }
    
    /**
     * @desc 上传joom订单追踪号
     */
    public function actionSetordershipped() {
    	$limit = Yii::app()->request->getParam('limit', '');
    	$packageInfos = OrderPackage::model()->getJoomWaitingUploadPackages($limit);
    	foreach ($packageInfos as $packageInfo) {
    		$shipedData = array();
    		if (!empty($packageInfo['track_num2']))
    			$tracknumber = $packageInfo['track_num2'];
    		else
    			$tracknumber = $packageInfo['track_num'];
    		//获取订单
    		$orderInfo = Order::model()->findByPk($packageInfo['order_id']);
    		if (empty($orderInfo)) continue;
    		$accountID = $orderInfo->account_id;
    		$shippedCode = $packageInfo['ship_code'];
    		//查处发货方式对应的Carrier
    		$shippCarrier = UebModel::model('LogisticsPlatformCarrier')->getCarrierByShipCode($shippedCode, Platform::CODE_JOOM);
    		$shipedData = array(
    			'order_id' => $orderInfo->platform_order_id,
				'shipped_carrier' => $shippCarrier,
    			'tracking_number' => $tracknumber,
    		);
    		$joomLog = new JoomLog();
    		$logID = $joomLog->prepareLog($accountID, 'fulfill_order');
    		if ($logID) {
    			//插入本次log参数日志(用来记录请求的参数)
    			$eventLog = $joomLog->saveEventLog('fulfill_order', array(
    					'log_id'    => $logID,
    					'account_id'=> $accountID,
    					'since_time'=> date('Y-m-d H:i:s'),
    			));
    			//设置日志为正在运行
    			$joomLog->setRunning($logID);
    			$joomOrder = new JoomOrder();
    			$joomOrder->setAccountID($accountID);
    			$flag = $joomOrder->setOrderShipped($shipedData);
    			if( $flag ){
    				//上传成功将该包裹详情标记为已经上传
    				UebModel::model('OrderPackage')->setPackageUploadedToPlatform($packageInfo['package_id'], date('Y-m-d H:i:s'));
    				$joomLog->setSuccess($logID, $tracknumber);
    				$joomLog->saveEventStatus('fulfill_order', $eventLog, JoomLog::STATUS_SUCCESS);
    			}else{
    				$joomLog->setFailure($logID, $joomOrder->getExceptionMessage());
    				$joomLog->saveEventStatus('fulfill_order', $eventLog, JoomLog::STATUS_FAILURE);
    			}
    		}
    	}   	
    }
    
    /**
     * @desc 拉取单个订单处理
     * @link /joom/joomorder/getorderinfo/account_id/1/order_id/1111
     * @throws Exception
     */
    public function actionGetorderinfo(){
    	$orderId = Yii::app()->request->getParam("order_id");
    	$accountId = Yii::app()->request->getParam("account_id");
    	if(!$orderId){
    		echo "no orderID";
    		exit();
    	}
    	if(!$accountId){
    		echo "no accountID";
    		exit();
    	}
    	$orderModel = new RetrieveOrdersRequest;
    	$orderModel->setAccount($accountId);
    	$orderModel->setOrderId($orderId);
    	$response = $orderModel->setRequest()->sendRequest()->getResponse();
    	$joomOrderModel = new JoomOrderMain();
    	$joomOrderModel->setLogID(10000);
    	$joomOrderModel->setAccountID($accountId);//设置账号
    	$this->print_r($response);
    	//循环订单信息
    	$dbTransaction = $joomOrderModel->dbConnection->getCurrentTransaction();
    	if( !$dbTransaction ){
    		$dbTransaction = $joomOrderModel->dbConnection->beginTransaction();//开启事务
    	}
    	try{
    		if(!isset($response->data->Order)){
    			throw new Exception('no data');
    		}

    		$joomOrderModel->orderResponse = $response->data->Order;
                    
            // 只抓取审核通过的订单
            //APPROVED REQUIRE_REVIEW
            if(trim($response->data->Order->state) != "APPROVED"){
                //throw new Exception('state is'. trim($response->data->Order->state));
            }
            
            $res = $joomOrderModel->saveOrderMainData($response->data->Order);
            var_dump($res);
            echo "<br>".$joomOrderModel->getExceptionMessage()."<br>";
    		$dbTransaction->commit();
    		echo "finish";
    	}catch (Exception $e){
    		$dbTransaction->rollback();
    		$msg = Yii::t('ebay', 'Save Order Infomation Failed');
    		var_dump($e->getMessage());
    	}
    }
    
    /**
     * @desc 下载订单到中间库 -- new
     * @author yangsh
     * @since 2016-11-11
     * @link /joom/joomorder/getordersnew/account_id/1
     */
    public function actionGetordersnew(){
    	set_time_limit(3600);
    	ini_set("display_errors", true);
    	error_reporting(E_ALL & ~E_STRICT);
    
    	$accountID = Yii::app()->request->getParam('account_id');
    
    	if( $accountID ){//根据账号抓取订单信息
    		$logModel = new JoomLog;
            $eventName = JoomLog::EVENT_GETORDER;
    		$logID = $logModel->prepareLog($accountID,$eventName);
    		if( $logID ){
    			//1.检查账号是否可拉取订单
    			$checkRunning = $logModel->checkRunning($accountID, $eventName);
    			if( !$checkRunning ){
    				$logModel->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
    				echo "There Exists An Active Event";
    			}else{
    				//2.准备拉取日志信息
                    $timeSince = JoomOrderMain::model()->getTimeSince($accountID);
                    //echo $timeSince.'--'.str_replace('T',' ',$timeSince)."<br>";
    				//插入本次log参数日志(用来记录请求的参数)
    				$eventLog = $logModel->saveEventLog($eventName, array(
    						'log_id'        => $logID,
    						'account_id'    => $accountID,
    						'since_time'    => str_replace('T',' ',$timeSince),
    						'complete_time'	=> date('Y-m-d H:i:s',time())//下次拉取时间可以从当前时间点进行,这是北京时间
    				));

    				//设置日志为正在运行
    				$logModel->setRunning($logID);

    				//3.拉取订单
    				$joomOrderMain = new JoomOrderMain();
    				$joomOrderMain->setAccountID($accountID);//设置账号
    				$joomOrderMain->setLogID($logID);//设置日志编号
    				$flag = $joomOrderMain->getOrders($timeSince);//拉单

    				//4.更新日志信息
    				if( $flag ){
    					$logModel->setSuccess($logID);
    					$logModel->saveEventStatus($eventName, $eventLog, JoomLog::STATUS_SUCCESS);
    				}else{
                        $errMsg = $joomOrderMain->getExceptionMessage();
                        if (mb_strlen($errMsg)> 500 ) {
                            $errMsg = mb_substr($errMsg,0,500);
                        } 
    					$logModel->setFailure($logID, $errMsg);
    					$logModel->saveEventStatus($eventName, $eventLog, JoomLog::STATUS_FAILURE);
    				}
                    echo json_encode($_REQUEST).($flag ? ' Success ' : ' Failure ').$joomOrderMain->getExceptionMessage()."<br>";                    
    			}
    		}
    	}else{//循环可用账号，多线程抓取
    		$joomAccounts = JoomAccount::model()->getAbleAccountList();
    		foreach($joomAccounts as $account){
                $url = Yii::app()->request->hostInfo.'/'.$this->route.'/account_id/'.$account['id'];
                echo $url." <br>\r\n";
                MHelper::runThreadBySocket($url);
    		}
    	}
    	Yii::app()->end('finish');
    }

    /**
     * @desc 补拉订单到中间库  -- new
     * @author yangsh
     * @since 2016-11-11
     * @link /joom/joomorder/checkgetordersnew/account_id/1
     */
    public function actionCheckgetordersnew(){
        set_time_limit(3600);
        ini_set("display_errors", true);
        error_reporting(E_ALL & ~E_STRICT);
    
        $accountID = trim(Yii::app()->request->getParam('account_id',''));
        $timeSince = trim(Yii::app()->request->getParam('since_time',''));//2016-11-12T10:30:00
        $day = trim(Yii::app()->request->getParam('day',3));
        $day == '' && $day = 3;//默认3天

        if( $accountID ){//根据账号抓取订单信息
            $logModel = new JoomLog;
            $eventName = JoomLog::EVENT_CHECK_GETORDER;//checkgetordersnew事件
            $logID = $logModel->prepareLog($accountID,$eventName);
            if( $logID ){
                //1.检查账号是否可拉取订单
                $checkRunning = $logModel->checkRunning($accountID, $eventName);
                if( !$checkRunning ){
                    $logModel->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
                    echo "There Exists An Active Event";
                }else{
                    //2.准备拉取日志信息
                    if($timeSince == ''){
                        $timeSince = str_replace(" ", "T", date("Y-m-d H:i:s", time()-$day*86400));
                    }

                    //设置日志为正在运行
                    $logModel->setRunning($logID);

                    //3.拉取订单
                    $joomOrderMain = new JoomOrderMain();
                    $joomOrderMain->setAccountID($accountID);//设置账号
                    $joomOrderMain->setLogID($logID);//设置日志编号
                    $flag = $joomOrderMain->getOrders($timeSince);//拉单

                    //4.更新日志信息
                    if( $flag ){
                        $logModel->setSuccess($logID);
                    }else{
                        $errMsg = $joomOrderMain->getExceptionMessage();
                        if (mb_strlen($errMsg)> 500 ) {
                            $errMsg = mb_substr($errMsg,0,500);
                        }                         
                        $logModel->setFailure($logID, $errMsg);
                    }
                    echo json_encode($_REQUEST).($flag ? ' Success ' : ' Failure ').$joomOrderMain->getExceptionMessage()."<br>";
                }
            }
        }else{//循环可用账号，多线程抓取
            $joomAccounts = JoomAccount::model()->getAbleAccountList();
            foreach($joomAccounts as $account){
                $url = Yii::app()->request->hostInfo.'/'.$this->route.'/account_id/'.$account['id']."/day/".$day."/since_time/".$timeSince;
                echo $url." <br>\r\n";
                MHelper::runThreadBySocket($url);
                sleep(10);
            }
        }
        Yii::app()->end('finish');
    }

    /**
     * @desc 同步订单到oms  -new
     * @link /joom/joomorder/syncorder/account_id/xx/order_id/1111
     */
    public function actionSyncorder(){
    	set_time_limit(3600);
    	ini_set("display_errors", true);
    	error_reporting(E_ALL & ~E_STRICT);

    	$limit = Yii::app()->request->getParam("limit", 1000);
    	$accountID = trim(Yii::app()->request->getParam("account_id",''));
        $platformOrderID = trim(Yii::app()->request->getParam("order_id",''));
    	
    	$syncTotal = 0;
    	$logID = 1000;
    	//@todo 增加日志控制
    	$logModel = new JoomLog();
    	$virAccountID = 90000;
    	$eventName = JoomLog::EVENT_SYNC_ORDER;
    	$logID = $logModel->prepareLog($virAccountID, $eventName);
    	if($logID){
    		$checkRunning = $logModel->checkRunning($virAccountID, $eventName);
    		if(!$checkRunning){
    			$logModel->setFailure($logID, "Have a active event");
    			exit("Have a active event");
    		}
    		//设置日志为正在运行
    		$logModel->setRunning($logID);
            $joomAccounts = JoomAccount::model()->getAbleAccountList();
	    	foreach($joomAccounts as $account){
	    		if(!empty($accountID) && $account['id'] != $accountID){
	    			continue;
	    		}
	    		$joomOrderMain = new JoomOrderMain();
	    		$joomOrderMain->setAccountID($account['id']);
	    		$joomOrderMain->setLogID($logID);
	    		$syncCount = $joomOrderMain->syncOrderToOmsByAccountID($account['id'], $limit, $platformOrderID);
	    		$syncTotal += $syncCount;
	    		echo "<br>======={$account['account_name']}: {$syncCount}======".$joomOrderMain->getExceptionMessage()."<br/>";
	    	}
	    	$logModel->setSuccess($logID, 'Total:'.$syncTotal);
	    	echo "finish";
    	}else{
    		exit("Create Log Id Failure!!!");
    	}
    }


    //手动拉单
    public function actionManualgetorder(){
        $this->render("manualgetorder");
    }

    /**
     * 手动拉单
     */
    public function actionManualSaveOrder(){
        set_time_limit(3600);
        ini_set("display_errors", true);
        error_reporting(E_ALL);

        $accountID = Yii::app()->request->getParam('account_id');
        $orderIDs = Yii::app()->request->getParam('order_id');
        $typeID = Yii::app()->request->getParam('type_id');//1拉取，2拉取同步

        if(!$accountID){
            throw new Exception('指定账号');
        }
        if(!$orderIDs){
            throw new Exception("指定订单ID");
        }
        if(!$typeID){
            throw new Exception("未知类型，错误");
        }

        $orderIDs = trim($orderIDs);
        $orderIDs = explode(",", rtrim($orderIDs,','));

        if(count($orderIDs) > 20){
            throw new Exception("指定的订单ID，不能超过20个");
        }

        $logModel = new JoomLog();
        //创建运行日志        
        $logId = $logModel->prepareLog($accountID, JoomLog::EVENT_MANUAL_GETORDER);
        if(!$logId) {
            echo Yii::t('wish_listing', 'Log create failure');
            Yii::app()->end();
        }
        //检查账号是可以提交请求报告
        $checkRunning = $logModel->checkRunning($accountID, JoomLog::EVENT_MANUAL_GETORDER);
        if(!$checkRunning){
            $logModel->setFailure($logId, Yii::t('systems', 'There Exists An Active Event'));
            echo Yii::t('systems', 'There Exists An Active Event');
            Yii::app()->end();
        }
        //设置日志为正在运行
        $logModel->setRunning($logId);
        foreach ($orderIDs as $platformOrderId) {
            $orderId = $platformOrderId;
            $accountId = $accountID;
            $orderModel = new RetrieveOrdersRequest;
            $orderModel->setAccount($accountId);
            $orderModel->setOrderId($orderId);
            $response = $orderModel->setRequest()->sendRequest()->getResponse();
            $joomOrderModel = new JoomOrderMain();
            $joomOrderModel->setLogID(10000);
            $joomOrderModel->setAccountID($accountId);//设置账号

            //循环订单信息
            $dbTransaction = $joomOrderModel->dbConnection->getCurrentTransaction();
            if( !$dbTransaction ){
                $dbTransaction = $joomOrderModel->dbConnection->beginTransaction();//开启事务
            }
            try{
                if(!isset($response->data->Order)){
                    $logModel->setFailure($logId, 'no data');
                    throw new Exception('no data');
                }

                $joomOrderModel->orderResponse = $response->data->Order;                
                $res = $joomOrderModel->saveOrderMainData($response->data->Order);
                if($res && $typeID == 2){
                    $joomOrderModel->syncOrderToOmsByAccountID($accountId, 1, $orderId);
                }
                // echo "<br>".$joomOrderModel->getExceptionMessage()."<br>";
                $dbTransaction->commit();
            }catch (Exception $e){
                $dbTransaction->rollback();
                $logModel->setFailure($logId, $e->getMessage());
                throw new Exception($e->getMessage());
            }
        }

        $logModel->setSuccess($logId);
        echo $this->successJson(array('message'=>'拉取成功'));
        Yii::app()->end();
    }
    
 }