<?php
/**
 * @desc Joom确认发货
 * @author wx
 * @since 2015-12-28
 */
class JoomuploadtrackController extends UebController{
    
    /**
     * @desc 访问过滤配置
     * @see CController::accessRules()
     */
    public function accessRules() {
        return array(
			array(
				'allow',
				'users' => array('*'),
				'actions' => array('confirmshipped','uploadtracknum')
			),
		);
    }
    
    /**
     * @desc 设置订单已发货 http://erp_market.com/joom/joomuploadtrack/confirmshipped/limit/10/account_id/247/account/1976899053
     * @desc 针对Joom平台下单后，超过3.5天还未发货的订单提前声明发货
     * @author wx
     */
    public function actionConfirmshipped() {
    	set_time_limit(4600);
    	error_reporting(E_ALL);
    	ini_set("display_errors", true);
    	$accountIdParam = Yii::app()->request->getParam('account_id','');
    	$orderId = Yii::app()->request->getParam('order_id');
    	$limit = Yii::app()->request->getParam('limit', '');
    	$bug = Yii::app()->request->getParam('bug');
    	$gmtime = gmdate('Y-m-d H:i:s'); //当前UTC时间
    	
    	$joomAccounts = JoomAccount::model()->getAbleAccountList();
    	foreach($joomAccounts as $account){
    		$accountID = $account['id'];
    		if( !empty($accountIdParam) && $accountID != $accountIdParam ) continue;
    		if( $accountID ){
    			//付款时间超过5天的订单
    			$orderInfos = Order::model()->getJoomWaitingConfirmOrders($accountID,$gmtime,$orderId,$limit);
    			if($bug){
    				$this->printbugmsg($orderInfos, "orderInfos");
       			}
    			//var_dump($orderInfos);exit;
    			$tmpOrderIds = array();
    			$tmpOrderInfos = array();
    			foreach($orderInfos as $key => $val){
    				$tmpOrderIds[] = $val['order_id'];
    				$tmpOrderInfos[$val['order_id']] = $val;
    			}
    			//var_dump($tmpOrderInfos);exit;
    			$orderIdArray = $this->splitByn($tmpOrderIds,500);
    			$tmpMarkOrdIds = array();
    			$needMarkOrderIds = array();
    			//var_dump($orderIdArray);
    			if($bug){
    				$this->printbugmsg($orderIdArray, "orderIdArray");
    			}
    			
    			foreach( $orderIdArray as $key => $val ){
    				//查出有包裹未上传跟踪号的订单
    				$unUploadTrackOrders = OrderPackage::model()->getJoomUnUploadTrackOrders( MHelper::simplode($val) );
    				if($bug){
    					$this->printbugmsg($unUploadTrackOrders, "unUploadTrackOrders");
    				}
    				//var_dump($unUploadTrackOrders);
    				//查出还没有生成包裹的订单
    				$unPackageOrders = OrderPackage::model()->getJoomUnCreatePackageOrders( MHelper::simplode($val) );
    				//var_dump($unPackageOrders);
    				$needMarkOrderIds = array_merge($needMarkOrderIds,$unUploadTrackOrders,$unPackageOrders);
    				//var_dump($needMarkOrderIds);
    				
    				$tmpRet = JoomOrderMarkShippedLog::model()->getInfoByOrderIds( MHelper::simplode(array_merge($unUploadTrackOrders,$unPackageOrders)),'order_id' );
    				foreach( $tmpRet as $v ){
    					$tmpMarkOrdIds[] = $v['order_id'];
    				}
    				
    			}
    			if($bug){
    				$this->printbugmsg($needMarkOrderIds, "needMarkOrderIds");
    			}
    			//var_dump($needMarkOrderIds);exit;
    			if($bug){
    				$this->printbugmsg($tmpMarkOrdIds, "tmpMarkOrdIds");
    			}
    			
    			//记录此次需要提前上传跟踪号的订单
    			foreach( $needMarkOrderIds as $key => $val ){
    				if( in_array($val,$tmpMarkOrdIds) ){
    					unset($orderInfos[$key]);
    					continue;
    				}
    				$markOrderData = array(
    						'account_id' => $accountID,
    						'platform_order_id' => $tmpOrderInfos[$val]['platform_order_id'],
    						'order_id' => $val,
    						'paytime' => $tmpOrderInfos[$val]['paytime'],
    						'status' => JoomOrderMarkShippedLog::STATUS_DEFAULT,
    						'type'	=> JoomOrderMarkShippedLog::TYPE_FAKE,
    				);
    				$markModel = new JoomOrderMarkShippedLog();
    				$markModel->saveNewData($markOrderData);
    			}
    			
    			$joomLog = new JoomLog();
    			$logID = $joomLog->prepareLog($accountID,JoomShipment::EVENT_ADVANCE_SHIPPED);
    			if( $logID ){
    				//1.检查账号是否在提交发货确认
    				$checkRunning = $joomLog->checkRunning($accountID, JoomShipment::EVENT_ADVANCE_SHIPPED);
    				if( !$checkRunning ){
    					$joomLog->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
    				}else{
    					//设置日志为正在运行
    					$joomLog->setRunning($logID);
    					//查询要上传的订单 开始上传
    					$waitingMarkOrders = JoomOrderMarkShippedLog::model()->getWaitingMarkShipOrder( $accountID );
    					//var_dump($waitingMarkOrders);exit;
    					$isSuccess = true;
    					$errorMessage = '';
    					$carrierCode = 'Ruston';
    					if($bug){
    						$this->printbugmsg($waitingMarkOrders, "waitingMarkOrders");
    						$this->printbugmsg("", "waitingMarkOrders foreach begin");
    					}
    					foreach( $waitingMarkOrders as $key => $val ){
    						$this->printbugmsg($val, "every order");
    						//获取假tn单号
    						$trackVirtual = '';
    						/* $rs = false;
    						$count = 3;
    						while(!$rs){//取3次随机跟踪号，直到成功
    							$count--;
    							$trackVirtual = AutoCode::getCode('fake_track_num');
    							$check = JoomOrderMarkShippedLog::model()->getInfoByTrackNum( $trackVirtual,'id' );
    							if(!$check['id']){
    								$rs = true;
    							}
    							if($count<=0) break;
    						}
    						if($bug){
    							$this->printbugmsg($rs ? "success": "failure", "getTrackNum");
    							$this->printbugmsg($carrierCode, "carrierCode");
    							$this->printbugmsg($trackVirtual, "trackVirtual");
    						}
    						if( !$rs ) continue; */
    						$tmpModel = JoomOrderMarkShippedLog::model()->findByPk($val['id']);
    						$updateData = array(
    								'id' => $val['id'],
    								'track_num' => $trackVirtual,
    								'carrier_code' => $carrierCode
    						);
    						JoomOrderMarkShippedLog::model()->updateData( $tmpModel,$updateData );
    						
    						//2.插入本次log参数日志(用来记录请求的参数)
    						$eventLog = $joomLog->saveEventLog(JoomShipment::EVENT_ADVANCE_SHIPPED, array(
    								'log_id'        => $logID,
    								'account_id'    => $accountID,
    								'platform_order_id' => $val['platform_order_id'],
    								'order_id'      => $val['order_id'],
    								'track_number'  => $trackVirtual,
    								'carrier_name'  => $carrierCode,
    								'start_time'    => date('Y-m-d H:i:s'),
    						));
    						
    						//3.提前确认发货
    						$shippedData = array(
    								'id' => $val['platform_order_id'],
    								'tracking_provider' => $carrierCode,
    								'tracking_number' => $trackVirtual,
    						);
    						if($bug){
    							$this->printbugmsg($shippedData, "shippedData");
    						}
    						$joomShipmentModel = new JoomShipment();
    						$joomShipmentModel->setAccountID($accountID);//设置账号
    						$flag = $joomShipmentModel->uploadSellerShipment( $shippedData );//上传
    						if($bug){
    							$this->printbugmsg($flag ? "success":"failure", "flag");
    							$this->printbugmsg($joomShipmentModel->getExceptionMessage(), "getExceptionMessage");
    						}
    						//4.更新日志信息
    						if( $flag ){
    							//5.上传成功更新记录表
    							$updateData = array(
    									'id' => $val['id'],
    									'status' => JoomOrderMarkShippedLog::STATUS_SUCCESS,
    									'upload_time' => date('Y-m-d H:i:s'),
    							);
    							JoomOrderMarkShippedLog::model()->updateData( $tmpModel,$updateData );
    							$joomLog->saveEventStatus(JoomShipment::EVENT_ADVANCE_SHIPPED, $eventLog, $joomLog::STATUS_SUCCESS);
    						}else{
    							$updateData = array(
    									'id' => $val['id'],
    									'status' => JoomOrderMarkShippedLog::STATUS_FAILURE,
    									'errormsg' => $joomShipmentModel->getExceptionMessage(),
    									'upload_time' => date('Y-m-d H:i:s'),
    									'error_type' => $this->errorTypeMap(trim($joomShipmentModel->getExceptionMessage())),
    							);
    							JoomOrderMarkShippedLog::model()->updateData( $tmpModel,$updateData );
    							$joomLog->saveEventStatus(JoomShipment::EVENT_ADVANCE_SHIPPED, $eventLog, $joomLog::STATUS_FAILURE,$joomShipmentModel->getExceptionMessage());
    							$errorMessage .= json_encode($shippedData).": ".$joomShipmentModel->getExceptionMessage();
    						}
    						$isSuccess = $isSuccess && $flag;
    					}
    					if($bug){
    						$this->printbugmsg("", "waitingMarkOrders foreach end");
    					}
    					if( $isSuccess ){
    						$joomLog->setSuccess($logID);
    					}else{
    						$errorMessage = mb_substr($errorMessage, 0, 10000);
    						$joomLog->setFailure($logID, $errorMessage);
    					}
    				}
    				 
    			}
    		}
    		sleep(10);
    	}
    	
    	
    }
    
    /**
     * @desc 
     * @param unknown $var
     * @param string $name
     */
    private function printbugmsg($var, $name = ""){
    	echo "<br>========={$name}=========<br/>";
    	echo "<pre>";
    	print_r($var);
    	echo "</pre>";
    }
    
    /**
     * @desc 上传跟踪号 http://erp_market.com/joom/joomuploadtrack/uploadtracknum/limit/10/package_id/PK151202036308
     * @desc 1.上传已生成包裹、已有跟踪号的订单。  2.之前发过假单号的，付款时间不超过15天的，如果有真实tn则修改tn。 
     * @author wx
     */
    public function actionUploadtracknum() {
    	set_time_limit(4600);
    	error_reporting(E_ALL);
    	ini_set("display_errors", true);
    	$limit = Yii::app()->request->getParam('limit', '');
    	$packageId = Yii::app()->request->getParam('package_id', '');
    	$accountID = Yii::app()->request->getParam('account_id');
    	$bug = Yii::app()->request->getParam('bug');
    	$norun = Yii::app()->request->getParam('norun');
    	$pkCreateDate = date('Y-m-d',strtotime('-8 days')); //推送wms时间
    	//获取要上传的包裹 包含之前提前发货的订单。
    	$packageInfos = OrderPackage::model()->getJoomWaitingUploadPackages($pkCreateDate,$packageId,$limit,$accountID);
    	if($bug){
    		$this->printbugmsg($packageInfos, "packageInfos");
    	}
    	$tmpOrderIds = array();
    	foreach( $packageInfos as $key => $val ){
    		if( !in_array($val['order_id'],$tmpOrderIds) ){
    			$tmpOrderIds[] = $val['order_id'];
    		}
    	}
    	if($bug){
    		$this->printbugmsg($tmpOrderIds, "tmpOrderIds");
    	}
    	//var_dump($tmpOrderIds);exit;
    	//列表字符串有限制，每次查询限制在500以内
    	$ordArr = $this->splitByn($tmpOrderIds,500);
    	if($bug){
    		$this->printbugmsg($ordArr, "ordArr");
    	}
    	//var_dump($ordArr);exit;
    	unset($tmpOrderIds);
    	 
    	//循环查出订单,item相关信息，并采集accountid
    	$data = array();
    	$orderArray = array();
    	foreach($ordArr as $val){
    		$orderIdStr = "'".implode("','",$val)."'";
    		$orderList = Order::model()->getInfoListByOrderIds($orderIdStr,'o.order_id,o.account_id,o.platform_order_id,o.paytime',Platform::CODE_JOOM);
    		//var_dump($orderList);exit;
    		foreach( $orderList as $k => $v ){
    			if( !in_array($v['account_id'],array_keys($data)) ) {$data[$v['account_id']] = array();}
    			$orderArray[$v['order_id']]['account_id']			= $v['account_id'];
    			$orderArray[$v['order_id']]['platform_order_id']	= $v['platform_order_id'];
    			$orderArray[$v['order_id']]['paytime']				= $v['paytime'];
    		}
    	}
    	//var_dump($orderArray);exit;
    	if($bug){
    		$this->printbugmsg($orderArray, "orderArray");
    	}
    	
    	//临时处理不对应平台的物流商
    	$logististicCarriedMap = array(
    								'PostNL'=>'Holland Post',
    								'SFExpressl'=>'SFExpress'
    						);
    	if($bug){
    		$this->printbugmsg($logististicCarriedMap, "logististicCarriedMap");
    	}
    	//按照每个账号来整理数据
    	foreach($packageInfos as $key => $val){
    		$orderInfo = $orderArray[$val['order_id']];
    		/* $shipDate = date('Y-m-d H:i:s',strtotime($orderInfo['paytime'])+3600*1);
    		if( strtotime($shipDate) > strtotime(gmdate('Y-m-d H:i:s')) ){ //付款时间+1小时 ,若大于当前utc时间
    			continue;
    		} */
    		$shipCode = !empty($val['real_ship_type'])?$val['real_ship_type']:$val['ship_code'];
    		$carrierCode = LogisticsPlatformCarrier::model()->getCarrierByShipCode($shipCode ,Platform::CODE_JOOM );
            if(!$carrierCode) 
            {
            	if($bug){
            		$this->printbugmsg($val['order_id'], "orderID");
            		$this->printbugmsg($shipCode, "shipCode");
            		$this->printbugmsg("continue it", "nocarrierCode");
            	}
            	continue;
            }
            $carrierCode = trim($carrierCode);
            if($bug){
            	$this->printbugmsg($carrierCode, "carrierCode");
            	
            }
            if(isset($logististicCarriedMap[$carrierCode])){
            	$carrierCode = $logististicCarriedMap[$carrierCode];
            }
    		$tmp = array(
    				'order_id'			=> $val['order_id'],
    				'platform_order_id'	=> $orderInfo['platform_order_id'],
    				'package_id' 		=> $val['package_id'],
    				'carrier_name'		=> $carrierCode,
    				'tracking_number' 	=> $val['track_num'],
    				'paytime'			=> $orderInfo['paytime'],
    		);
    		$data[$orderInfo['account_id']][$val['package_id']][] = $tmp;
    	}
    	
    	//print_r($data);exit;
    	if($bug){
    		$this->printbugmsg($data, "foreac data");
    	}
    	foreach( $data as $key => $val ){ //循环账号
    		if( !$val ) continue;
    		$accountInfo = JoomAccount::model()->getAccountInfoById( $key );
    		$accountID = $accountInfo['id'];
    		$joomLog = new JoomLog();
    		$logID = $joomLog->prepareLog($accountID,JoomShipment::EVENT_UPLOAD_TRACK);
    		if( $logID ){
    			//1.检查账号是否上传跟踪号
    			$checkRunning = $joomLog->checkRunning($accountID, JoomShipment::EVENT_UPLOAD_TRACK);
    			if( !$checkRunning ){
    				$joomLog->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
    				if($bug){
    					echo  Yii::t('systems', 'There Exists An Active Event'), "<br>";
    				}
    			}else{
    				//设置日志为正在运行
    				$joomLog->setRunning($logID);
    				$isSuccess = true;
    				$errorMessage = '';
    				foreach( $val as $pkId=>$vv ){ //循环包裹
    					$isResult = true;
    					if($bug){
    						$this->printbugmsg($pkId, "PKID");
    					}
    					foreach( $vv as $vvItem ){ //循环订单明细
    						
    						//检测是否之前有上传过
    						$checkAdvanceShiped = JoomOrderMarkShippedLog::model()->getInfoRowByOrderId( $vvItem['order_id'],'*' );
    						if($checkAdvanceShiped['order_id'] && (!in_array($checkAdvanceShiped['error_type'], array(0,1)) || !in_array($checkAdvanceShiped['update_error_type'], array(0,1))) ){ //不满足条件
    							if($bug){
    								$this->printbugmsg($checkAdvanceShiped, "checkAdvanceShiped");
    							}
    							//continue;
    						}
    						
    						//添加详细日志
    						$eventLog = $joomLog->saveEventLog(JoomShipment::EVENT_UPLOAD_TRACK, array(
    								'log_id'        => $logID,
    								'account_id'    => $accountID,
    								'platform_order_id'  => $vvItem['platform_order_id'],
    								'order_id'      => $vvItem['order_id'],
    								'package_id'	=> $pkId,
    								'track_number'     => $vvItem['tracking_number'],
    								'carrier_name'  => $vvItem['carrier_name'],
    								'start_time'    => date('Y-m-d H:i:s'),
    						));
    						
    						//准备上传数据
    						$shippedData = array(
    								'id' => $vvItem['platform_order_id'],
    								'tracking_number' => $vvItem['tracking_number'],
    								'tracking_provider' => $vvItem['carrier_name'],
    						);
    						if($bug){
    							$this->printbugmsg($shippedData, "shippedData");
    						}
    						//设置账号信息
    						$joomShipmentModel = new JoomShipment();
    						$joomShipmentModel->setAccountID($accountID);
    						$errorMessageSub = '';
    						
    						//保存订单上传记录
    						$tmpMarkId = 0;
    						if( empty($checkAdvanceShiped['order_id']) ){
    							$markOrderData = array(
    									'account_id' => $accountID,
    									'platform_order_id' => $vvItem['platform_order_id'],
    									'order_id' 		=> $vvItem['order_id'],
    									'package_id' 	=> $pkId,
    									'track_num'     => $vvItem['tracking_number'],
    									'carrier_code'     => $vvItem['carrier_name'],
    									'paytime' => $vvItem['paytime'],
    									'status' => JoomOrderMarkShippedLog::STATUS_DEFAULT,
    									'type'	=> JoomOrderMarkShippedLog::TYPE_TRUE,
    							);
    							$markModel = new JoomOrderMarkShippedLog();
    							$tmpMarkId = $markModel->saveNewData($markOrderData);
    						}else{
    							$tmpMarkId = $checkAdvanceShiped['id'];
    						}
    						
    						//初始化单个订单上传是否成功的标记
    						$flag = false;
    						
    						//开始上传
    						$tmpModel = JoomOrderMarkShippedLog::model()->findByPk($tmpMarkId);
    						if( $checkAdvanceShiped['order_id'] && $checkAdvanceShiped['status'] == JoomOrderMarkShippedLog::STATUS_SUCCESS && 
    								(time()-strtotime($checkAdvanceShiped['upload_time'])) < 432000 ){ //之前有提前发货，并且提前发货时间距离现在不超过15日的，则调用修改声明发货接口
    							if($bug){
    								$this->printbugmsg("", "advanced confirmed");
    							}
    							$flag = $joomShipmentModel->modifySellerShipment( $shippedData );//上传
    							$errorMessageSub = $joomShipmentModel->getExceptionMessage();
    							if($bug){
    								$this->printbugmsg($flag, "flag");
    								$this->printbugmsg($errorMessageSub, "errorMessageSub");
    							}
    							if($flag){ 
    								//更新成功
    								$updateData = array(
    										'id' => $tmpMarkId,
    										'update_status' => JoomOrderMarkShippedLog::UPDATE_STATUS_SUCCESS,
    										'update_time' => date('Y-m-d H:i:s'),
    										'package_id'	=> $pkId,
    										'update_errormsg' => $errorMessageSub,
    								);
    							}else{
    								$updateData = array(
    										'id' => $tmpMarkId,
    										'update_status' => JoomOrderMarkShippedLog::UPDATE_STATUS_FAILURE,
    										'update_time' => date('Y-m-d H:i:s'),
    										'package_id'	=> $pkId,
    										'update_errormsg' => $errorMessageSub,
    										'update_error_type' => $this->errorTypeMap(trim($errorMessageSub)),
    								);
    							}
    							JoomOrderMarkShippedLog::model()->updateData( $tmpModel,$updateData );
    						}else{ //之前未提前声明发货，则直接上传真实跟踪号
    							if( !$checkAdvanceShiped['order_id'] || in_array($checkAdvanceShiped['error_type'], array(0,1)) 
    										|| in_array($checkAdvanceShiped['update_error_type'], array(0,1)) ){
    								if($bug){
    									$this->printbugmsg("", "upload new tracenumber");
    								}
    								$flag = $joomShipmentModel->uploadSellerShipment( $shippedData );//上传
    								$errorMessageSub = $joomShipmentModel->getExceptionMessage();
    								if($bug){
    									$this->printbugmsg($flag, "flag");
    									$this->printbugmsg($errorMessageSub, "errorMessageSub");
    								}
    								if($flag){ //上传成功
    									$updateData = array(
    											'id' => $tmpMarkId,
    											'status' => JoomOrderMarkShippedLog::STATUS_SUCCESS,
    											'upload_time' => date('Y-m-d H:i:s'),
    									);
    								}else{ //上传失败
    									//if( $errorMessageSub == 'This order has been fulfilled already' ){ //满足更新条件
    									$errorType = $this->errorTypeMap(trim($errorMessageSub));
    									if( $errorMessageSub == 'api.not_found' || $errorType == 1 ){ //满足更新条件
    										if($bug){
    											$this->printbugmsg("", "modify track number");
    										}
    										$joomShipmentModel1 = new JoomShipment();
    										$joomShipmentModel1->setAccountID($accountID);
    										$flag = $joomShipmentModel1->modifySellerShipment( $shippedData );//修改跟踪号
    										if($flag){ //更新成功
    											$updateData = array(
    													'id' => $tmpMarkId,
    													'update_status' => JoomOrderMarkShippedLog::UPDATE_STATUS_SUCCESS,
    													'update_time' => date('Y-m-d H:i:s'),
    													'package_id'	=> $pkId,
    											);
    										}else{ //更新失败
    											$errorMessageSub = $joomShipmentModel1->getExceptionMessage();
    											$updateData = array(
    													'id' => $tmpMarkId,
    													'update_status' => JoomOrderMarkShippedLog::UPDATE_STATUS_FAILURE,
    													'update_time' => date('Y-m-d H:i:s'),
    													'package_id'	=> $pkId,
    													'update_errormsg' => $joomShipmentModel1->getExceptionMessage(),
    													'update_error_type' => $errorType,
    											);
    											
    										}
    										if($bug){
    											$this->printbugmsg($flag, "second modify");
    											if(!$flag){
    												$this->printbugmsg($errorMessageSub, "second errorMessageSub");
    											}
    										}
    									}elseif($errorType == 5 ){
    										$flag = true;
    										$updateData = array(
    												'id' => $tmpMarkId,
    												'update_status' => JoomOrderMarkShippedLog::UPDATE_STATUS_SUCCESS,
    												'update_time' => date('Y-m-d H:i:s'),
    												'package_id'	=> $pkId,
    										);
    									}else{ //不满足更新条件
    										$updateData = array(
    												'id' => $tmpMarkId,
    												'status' => JoomOrderMarkShippedLog::STATUS_FAILURE,
    												'upload_time' => date('Y-m-d H:i:s'),
    												'errormsg' => $errorMessageSub,
    												'error_type' => $this->errorTypeMap(trim($errorMessageSub)),
    										);
    									}
    								}
    								JoomOrderMarkShippedLog::model()->updateData( $tmpModel,$updateData );
    							}else{
    								$this->printbugmsg("", "NOT MATCH UPLOAD TRACK");
    							}
    							
    						}
    						
    						if( $flag ){
    							//5.上传成功更新记录表
    							$joomLog->saveEventStatus(JoomShipment::EVENT_UPLOAD_TRACK, $eventLog, $joomLog::STATUS_SUCCESS);
    						}else{
    							$joomLog->saveEventStatus(JoomShipment::EVENT_UPLOAD_TRACK, $eventLog, $joomLog::STATUS_FAILURE,$errorMessageSub);
    							$errorMessage .= $errorMessageSub;
    						}
    						$isResult = $isResult && $flag;
    					}
    					if( $isResult ){
    						$res = UebModel::model('OrderPackage')->updateAll(array('is_confirm_shiped'=>1),' is_confirm_shiped=0 and package_id in("'.$pkId.'")');
    						if($bug){
    							$this->printbugmsg($res ? "success" : "failure", "update order package");
    						}
    					}
    					$isSuccess = $isSuccess && $isResult;
    				}
    				if( $isSuccess ){
    					$joomLog->setSuccess($logID);
    				}else{
    					$errorMessage = mb_substr($errorMessage, 0, 10000);
    					$joomLog->setFailure($logID, $errorMessage);
    				}
    			}
    		}else{
    			echo "create log id failure<br/>";
    		}
    	}
    	$this->printbugmsg("", "foreac data");
   	}
   	
   	
    /**
     * @desc 按指定大小$n 截取数组
     * @param unknown $n
     * @return multitype:unknown multitype:
     */
    public function splitByn($ordArr,$n){
    	$newArr = array();
    	$count = ceil(count($ordArr)/$n);
    	for($i=0;$i<=$count-1;$i++){
    		if($i == ($count-1)){
    			$newArr[] = $ordArr;
    		}else{
    			$newArr[] = array_splice($ordArr,0,$n);
    		}
    	}
    	return $newArr;
    }
    
    /**
     * @desc joom返回的错误映射
     */
    public function errorTypeMap( $errorMsg = '' ){
    	$errorCodeMapArr = array(
    			'has been shipped already' => 1,
    			'The tracking number for this order is confirmed already, it cannot be changed.' => 2,
    			'This tracking number has been used before and cannot be reused' => 3,
    			'Order not in SHIPPED state' => 4,
    	);
        
        //has been shipped already
        if(strripos($errorMsg, 'has been shipped already')){
            return 1;
        }
        //$errorMsg = "logic.order.invalid_status_transition: order '1477559781667847272-215-56-629-1654639508' can't go from 'shipped' to 'shipped'";
        //logic.order.invalid_status_transition: order '1477559781667847272-215-56-629-1654639508' can't go from 'shipped' to 'shipped'
        //logic.order.invalid_status_transition: order '1473100273998649770-201-56-26341-73904825' can't go from 'cancelledByJoom' to 'shipped'"
        //logic.order.invalid_status_transition: order '1474423345087180730-184-56-26341-1824417615' can't go from 'notDelivered' to 'shipped'
        //logic.order.invalid_status_transition: order '1478538175776698229-91-56-629-1832380609' can't go from 'delivered' to 'shipped'
        //api.bad_request: invalid tracking provider, see https://merchant.joom.it/documentation/api/v2#tracking-providers
        $preg = "/logic\.order\.invalid_status_transition\: order \'([0-9-]*?)\' can\'t go from \'([a-zA-Z].*?)\' to \'shipped\'/ie";
        $match = array();
        $res = preg_match($preg, $errorMsg, $match);
        if($res){
        	$shippepMap = array('shipped', 'cancelledByJoom', 'delivered'/* , 'notDelivered' */);
        	if(in_array($match[2], $shippepMap))
        		return 5;//不需要更改
        }

    	return empty($errorCodeMapArr[$errorMsg])?99:$errorCodeMapArr[$errorMsg];
    }
    
    
}