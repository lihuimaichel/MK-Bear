<?php
/**
 * @desc Lazada物流
 * @author Gordon
 * @since 2015-09-04
 */
class LazadashipmentController extends UebController{
	
    const LGS_PROVIDER = 'LGS-FM40';//统一面单后

	/**
	 * 定义站点与渠道
	 */
	private $_siteShipCode = array(//LGS-FM40
        /****** old ****-start-**/
        'ph' => array('provider'=>'LGS-PH1', 'ship_code'=>array('KD_PH_LGS')),
        'my' => array('provider'=>'LGS-MY05', 'ship_code'=>array('KD_MY_LGS')),      //AS-Poslaju 切换
        'id' => array('provider'=>'LGS-LEX-ID', 'ship_code'=>array('KD_ID_LGS')),
        'sg' => array('provider'=>'LGS-SG3', 'ship_code'=>array('KD_SG_LGS')),		//SG走sg2  4.14改sg3
        'th' => array('provider'=>'LGS-TH1', 'ship_code'=>array('KD_TH_LGS')),		//TH走th1
		'vn' => array('provider'=>'LGS-VN', 'ship_code'=>array('KD_VN_LGS'))		//VN走vn
    );
    
    /**
     * @desc	Step1: 自动生成跟踪号
     * @link	172.16.2.21/lazada/lazadashipment/batchupload?site=ph	  [MY-1-马来][SG-2-新加坡][ID-3-印尼][TH-4-泰国][PH-5-菲律宾]
     * @link	172.16.2.21/lazada/lazadashipment/batchupload?site=my&packageID=PK160107099869
     * @link	172.16.2.21/lazada/lazadashipment/batchupload?site=th
     * @link	172.16.2.21/lazada/lazadashipment/batchupload?site=id&packageID=PK160301024450
     * @link	172.16.2.21/lazada/lazadashipment/batchupload?site=sg
	 * @link	172.16.2.21/lazada/lazadashipment/batchupload?site=vn
     */
    public function actionBatchupload(){
        set_time_limit(3600);
        ini_set ( 'display_errors', true );
        error_reporting( E_ALL & ~E_STRICT );

    	$site = Yii::app()->request->getParam('site');//自传站点，保证账号一致性
    	$packageID = Yii::app()->request->getParam('packageID');
    	$arrSite = $this->_siteShipCode;
    	if (!array_key_exists($site, $arrSite)) {
    		exit('site is invalid');
    	}

    	$packages = LazadaShipment::model()->getBatchPackageReadyToShip($site, $arrSite[$site]['ship_code'], $packageID);//根据站点获取需要上传跟踪号的包裹
        if(empty($packages)) {
            die('packages is empty');
        }

    	foreach($packages as $info){
            $provider       = self::LGS_PROVIDER;
    		$packageID 		= $info['package_id'];
    		$orders 		= Order::model()->getOrderByPackageID($packageID);//根据包裹号查订单信息
    		$logID 			= 1;
    		
            //超过3条记录，不管sku是否相同，付款时间不超过12小时不自动RTS
            if(count($orders)>3 &&  floor((time()-strtotime($orders[0]['ori_pay_time']))/3600) < 12 ) {
                continue;
            }

    		$orderArr = $logArr = array();
    		foreach($orders as $order){
    			$orderArr[] = $order['item_id'];//销售ID
    			$accountID  = $order['account_id'];
    			$logArr		= $order;
    		}
    		
    		$accountInfo = LazadaAccount::model()->getAccountByOldAccountID($accountID);//根据老系统账号ID获取市场业务系统账号信息
    		if (empty($accountInfo)) continue;
    		$request = new SetStatusToReadyToShipRequest();
    		$request->setOrderItemIds(array_unique($orderArr));
    		$request->setShippingProvider($provider);
    		$request->setTrackingNumber('');
    		$request->setSiteID($accountInfo['site_id']);
    		
    		//保存日志
    		$eventLog = LazadaLog::model()->saveEventLog(LazadaShipment::EVENT_NAME, array(
    				'log_id'        	=> $logID,
    				'order_id'    		=> $logArr['order_id'],
    				'package_id'   	 	=> $packageID,
    				'ship_code'			=> $info['ship_code'],
    				'platform_code'		=> $logArr['platform_code'],
    				'platform_order_id'	=> $logArr['platform_order_id'],
    				'account_id'		=> $logArr['account_id'],
    				'ship_country'		=> $logArr['ship_country'],
    				'create_time'		=> date('Y-m-d H:i:s'),
    				'ship_status'		=> $logArr['ship_status'],
    				'create_user_id'	=> Yii::app()->user->id,
    				'item_id'			=> $logArr['item_id'],
    				'upload_time'		=> date('Y-m-d H:i:s', time()),
    				'ship_status'		=> LazadaShipment::UPLOAD_SHIP_NO
    		));
    		
    		$trackLogId = UebModel::model('OrderPackageTrackLog')->addNewData(array('package_id'=>$packageID,'ship_code'=>$info['ship_code'],'note'=>'prepare upload'));
    		
            //准备日志
    		// $logID = LazadaLog::model()->prepareLog($accountInfo['account_id'],LazadaShipment::EVENT_NAME);
    		if( $logID ){
    			//设置日志为正在运行
    			//LazadaLog::model()->setRunning($logID);
    			$response = $request->setAccount($accountInfo['account_id'])->setRequest()->sendRequest()->getResponse();
    			if ($_GET['isTest']) {
    				var_dump($response,$orderArr);
    			}
    			// echo '<pre>';
    			//4.更新日志信息
    			$errMessage = '';
    			$time = date('Y-m-d H:i:s');
    			if($request->getIfSuccess()){
    				OrderPackage::model()->updateByPk($packageID, array('upload_ship'=>OrderPackage::UPLOAD_SHIP_YES,'upload_time'=>$time));
    				LazadaLog::model()->updateLogData(LazadaShipment::EVENT_NAME, $eventLog, array('ship_status'=>LazadaShipment::UPLOAD_SHIP_YES, 'upload_time'=>$time));
    				//LazadaLog::model()->setSuccess($logID);
    				UebModel::model('OrderPackageTrackLog')->updateByPk($trackLogId, array('response_time'=>$time, 'return_result'=>'SUCCESS', 'note'=>'response OK'));
    			}else{
    				$errMessage = trim($response->Head->ErrorMessage);
    				if (in_array($response->Head->ErrorCode, array('73'))) {	//失败情况
    					//OrderPackage::model()->updateByPk($packageID, array('upload_time'=>$time));
    				}elseif (in_array($response->Head->ErrorCode, array('21'))) {	//不一定失败情况，可尝试取跟踪号
    					OrderPackage::model()->updateByPk($packageID, array('upload_time'=>$time));
    				}
    				
    				UebModel::model('OrderPackageTrackLog')->updateByPk($trackLogId, array('status'=>OrderPackageTrackLog::STATUS_FAIL,'return_result'=>$errMessage));
    				LazadaLog::model()->updateLogData(LazadaShipment::EVENT_NAME, $eventLog, array('response_msg'=>$response->error_message));
    				//LazadaLog::model()->setFailure($logID, $request->getErrorMsg());
    			}
    		}
    		
    	}
    	
    	echo '<br/>OK';
    }

    /**
     * @link    172.16.2.21/lazada/lazadashipment/gettracknumts?packageID=PK160801034864
     */
    public function actionGettracknumts() {
        echo '<pre>';
        $packageID = Yii::app()->request->getParam('packageID');
        $orders = Order::model()->getOrderByPackageID($packageID);
        $orderArr = $logArr = $itemIds = array();
        foreach($orders as $order){
            $platformOrderId = explode('-', $order['platform_order_id']);
            $orderArr[]      = end($platformOrderId);
            $accountID       = $order['account_id'];
            $logArr          = $order;
            $itemIds[]       = $order['item_id'];
        }

        $accountInfo = LazadaAccount::model()->getAccountByOldAccountID($accountID);//根据老系统账号ID获取市场业务系统账号信息
        if (empty($accountInfo)) return;
        $request  = new GetMultipleOrderItemsRequest();
        $request->setOrderIdList(array_unique($orderArr));
        $request->setSiteID($accountInfo['site_id']);

        $response = $request->setAccount($accountInfo['account_id'])->setRequest()->sendRequest()->getResponse();
        var_dump($response,$itemIds,$orderArr);
    }

    /**
     * @desc 抓取返回的跟踪号<TrackingCode>和包裹号<PackageId>
     * @link	172.16.2.21/lazada/lazadashipment/gettracknum?site=ph  [MY-1-马来][SG-2-新加坡][ID-3-印尼][TH-4-泰国][PH-5-菲律宾]
     * @link	172.16.2.21/lazada/lazadashipment/gettracknum?site=my	
     * @link	172.16.2.21/lazada/lazadashipment/gettracknum?site=th&packageID=PK1703250026710&isTest=1
     */
    public function actionGettracknum() {
        set_time_limit(3600);
        ini_set ( 'display_errors', true );
        error_reporting( E_ALL & ~E_STRICT );

    	$site = Yii::app()->request->getParam('site');//自传站点，保证账号一致性
    	$packageID = Yii::app()->request->getParam('packageID');
    	$arrSite = $this->_siteShipCode;
    	if (!array_key_exists($site, $arrSite)) {
    		exit('site is invalid');
    	}
    	echo '<pre>';
    	$packages = LazadaShipment::model()->getMultipleOrderItems($site, $arrSite[$site]['ship_code'], $packageID);//根据站点获取需要上传跟踪号的包裹
    	var_dump(count($packages));//exit;
    	
    	foreach($packages as $info){
    		$packageID 		= $info['package_id'];
    		$orders 		= Order::model()->getOrderByPackageID($packageID);//根据包裹号查订单信息 'PK151126049278'
    		
    		$orderArr = $logArr = $itemIds = array();
    		foreach($orders as $order){
                if(trim($order['item_id']) == '') {
                    continue;
                }
    			$platformOrderId = explode('-', $order['platform_order_id']);
    			$orderArr[]		 = end($platformOrderId);
    			$accountID  	 = $order['account_id'];
    			$logArr			 = $order;
    			$itemIds[]		 = $order['item_id'];
    		}
    		
    		$accountInfo = LazadaAccount::model()->getAccountByOldAccountID($accountID);//根据老系统账号ID获取市场业务系统账号信息
    		if (empty($accountInfo)) continue;
    		$request  = new GetMultipleOrderItemsRequest();
    		$request->setOrderIdList(array_unique($orderArr));
    		$request->setSiteID($accountInfo['site_id']);
    		
    		//准备日志
    		//$logID = LazadaLog::model()->prepareLog($accountInfo['account_id'],LazadaShipment::EVENT_NAME);
    		$logID = 1;
    		
    		//保存日志
    		$eventLog = LazadaLog::model()->saveEventLog(LazadaShipment::EVENT_TRACK, array(
    				'log_id'        	=> $logID,
    				'order_id'    		=> $logArr['order_id'],
    				'package_id'   	 	=> $packageID,
    				'ship_code'			=> $info['ship_code'],
    				'platform_code'		=> $logArr['platform_code'],
    				'platform_order_id'	=> $logArr['platform_order_id'],
    				'account_id'		=> $logArr['account_id'],
    				'ship_country'		=> $logArr['ship_country'],
    				'create_time'		=> date('Y-m-d H:i:s'),
    				'create_user_id'	=> Yii::app()->user->id,
    				'item_id'			=> $logArr['item_id'],
    				'upload_time'		=> date('Y-m-d H:i:s', time()),
    				'upload_ship'		=> LazadaShipment::GET_TRACK_NO,
    		));
    		
    		if( $logID ){
    			//设置日志为正在运行
    			//LazadaLog::model()->setRunning($logID);
    			$response = $request->setAccount($accountInfo['account_id'])->setRequest()->sendRequest()->getResponse();
    		    if ($_GET['isTest']) {
    				var_dump($response,$itemIds);
    			}

    			//4.更新日志信息
    			if($request->getIfSuccess()){
    				$trackNums = $trackNums2 = array();
                    $otherTrackNums = $otherTrackNums2 = array();
    				//更新跟踪号和包裹号
    				foreach ($response->Body->Orders->Order as $akey => $aval) {
    					//返回的orderId必须和平台销售的订单ID相符,否则不允许插入跟踪号
    					if (!in_array(trim($aval->OrderId), $orderArr)) continue;
    					foreach ($aval->OrderItems->OrderItem as $val) {
                            //item 状态为ready_to_ship后才有跟踪号
                            if ( trim($val->Status) != 'ready_to_ship' ) {
                                continue;
                            }
    						$itemId = trim($val->OrderItemId);
    						$trackNum = trim($val->TrackingCode);
    						$trackNum2 = trim($val->PackageId);
    						if (in_array($itemId, $itemIds)) {
    							!in_array($trackNum, $trackNums) && $trackNums[$itemId] = $trackNum;
    							!in_array($trackNum2, $trackNums2) && $trackNums2[$itemId] = $trackNum2;
    						}else {
                                !in_array($trackNum, $otherTrackNums) && $otherTrackNums[$itemId] = $trackNum;
                                !in_array($trackNum2, $otherTrackNums2) && $otherTrackNums2[$itemId] = $trackNum2;
                            }
    					}
    				}
    				
    				if ($_GET['isTest']) {
    					var_dump($trackNums, $trackNums2, $otherTrackNums, $otherTrackNums2);
    				}
    				
                    //同一包裹只有一个TrackingCode 和 PackageId
                    $toTrackNum = $toTrackNum2 = '';
                    if ($info['is_repeat'] != 1) {
        				//如果线上结合与线下组合不一致[有多个跟踪号出来]
                        foreach ($trackNums as $key => $value) {
                            $packageInfoCheck = UebModel::model('OrderPackage')->getPackageInfoByTrackNum($value);
                            if (!$packageInfoCheck) {
                                $toTrackNum = $value;
                                $toTrackNum2 = $trackNums2[$key];
                                break;
                            }
                        }
                        if (empty($toTrackNum)) {
                            foreach ($otherTrackNums as $key => $value) {
                                $packageInfoCheck = UebModel::model('OrderPackage')->getPackageInfoByTrackNum($value);
                                if (!$packageInfoCheck) {
                                    $toTrackNum = $value;
                                    $toTrackNum2 = $otherTrackNums2[$key];
                                    break;
                                }
                            }
                        }
                    } else if(!empty($trackNums) && !empty($trackNums2)) { //重寄时任取其中一个
                        foreach ($trackNums as $key => $value) {
                            $toTrackNum = $value;
                            $toTrackNum2 = $trackNums2[$key]; 
                            break;                           
                        }
                    }

    				$updateData = array();
                    if (empty($toTrackNum) || empty($toTrackNum2)) {
                        if ($_GET['isTest']) {
                            echo 'No track num';
                        }
                        continue;
                    }

                    $updateData2['track_num'] = $updateData['track_num'] = $toTrackNum;
                    $updateData2['track_num2'] = $updateData['track_num2'] = $toTrackNum2;
                    $updateData2['upload_ship'] = LazadaShipment::GET_TRACK_NO;
                    if (!empty($updateData['track_num']) && !empty($updateData['track_num2']) ) {
                        $updateData['track_update_time'] = date('Y-m-d H:i:s');//更新跟踪号
                        OrderPackage::model()->updateByPk($packageID,$updateData);
                        $updateData2['upload_ship'] = LazadaShipment::GET_TRACK_YES;
                    }
                    LazadaLog::model()->updateLogData(LazadaShipment::EVENT_TRACK, $eventLog, $updateData2);
    				//LazadaLog::model()->setSuccess($logID);
    			}else{
    				LazadaLog::model()->updateLogData(LazadaShipment::EVENT_TRACK, $eventLog, array('response_msg'=>$response->error_message));
    				//LazadaLog::model()->setFailure($logID, $request->getErrorMsg());
    			}
    		}
    	}
    	
    	echo '<br/>OK';
    }
    
} 