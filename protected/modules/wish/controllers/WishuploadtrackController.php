<?php
/**
 * @desc Wish确认发货
 * @author wx
 * @since 2015-12-28
 */
class WishuploadtrackController extends UebController{
    
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
     * @desc 设置订单已发货 http://erp_market.com/wish/wishuploadtrack/confirmshipped/limit/10/account_id/247/account/1976899053
     * @desc 针对Wish平台下单后，超过3天还未发货的订单提前声明发货
     * @author wx
     */
    public function actionConfirmshipped() {
    	exit("已停止");
    	set_time_limit(5*3600);
    	$accountIdParam = Yii::app()->request->getParam('account_id','');
    	$orderId = Yii::app()->request->getParam('order_id');
    	$limit = Yii::app()->request->getParam('limit', '');
    	$gmtime = gmdate('Y-m-d H:i:s'); //当前UTC时间
    	$testAccountIds = $this->getTestAccountIds();
    	$wishAccounts = WishAccount::model()->getAbleAccountList();
    	foreach($wishAccounts as $account){
    		$accountID = $account['id'];
    		//@todo 测试帐号 
    		if(in_array($accountID, $testAccountIds)) continue;
    		
    		if( !empty($accountIdParam) && $accountID != $accountIdParam ) continue;
    		if( $accountID ){
    			//付款时间超过5天的订单
    			$orderInfos = Order::model()->getWishWaitingConfirmOrders($accountID,$gmtime,$orderId,$limit);
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
    			foreach( $orderIdArray as $key => $val ){
    				//查出有包裹未上传跟踪号的订单
    				$unUploadTrackOrders = OrderPackage::model()->getWishUnUploadTrackOrders( MHelper::simplode($val) );
    				//var_dump($unUploadTrackOrders);
    				//查出还没有生成包裹的订单
    				$unPackageOrders = OrderPackage::model()->getWishUnCreatePackageOrders( MHelper::simplode($val) );
    				//var_dump($unPackageOrders);
    				$needMarkOrderIds = array_merge($needMarkOrderIds,$unUploadTrackOrders,$unPackageOrders);
    				//var_dump($needMarkOrderIds);
    				$tmpRet = WishOrderMarkShippedLog::model()->getInfoByOrderIds( MHelper::simplode(array_merge($unUploadTrackOrders,$unPackageOrders)),'order_id' );
    				foreach( $tmpRet as $v ){
    					$tmpMarkOrdIds[] = $v['order_id'];
    				}
    				
    			}
    			//var_dump($needMarkOrderIds);exit;
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
    						'status' => WishOrderMarkShippedLog::STATUS_DEFAULT,
    						'type'	=> WishOrderMarkShippedLog::TYPE_FAKE,
    				);
    				$markModel = new WishOrderMarkShippedLog();
    				$markModel->saveNewData($markOrderData);
    			}
    			
    			$wishLog = new WishLog();
    			$logID = $wishLog->prepareLog($accountID,WishShipment::EVENT_ADVANCE_SHIPPED);
    			if( $logID ){
    				//1.检查账号是否在提交发货确认
    				$checkRunning = $wishLog->checkRunning($accountID, WishShipment::EVENT_ADVANCE_SHIPPED);
    				if( !$checkRunning ){
    					$wishLog->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
    				}else{
    					//设置日志为正在运行
    					$wishLog->setRunning($logID);
    					//查询要上传的订单 开始上传
    					$waitingMarkOrders = WishOrderMarkShippedLog::model()->getWaitingMarkShipOrder( $accountID );
    					//var_dump($waitingMarkOrders);exit;
    					$isSuccess = true;
    					$errorMessage = '';
    					$carrierCode = 'ChinaAirPost';
    		
    					foreach( $waitingMarkOrders as $key => $val ){
    						//获取假tn单号
    						$trackVirtual = '';
    						$rs = false;
    						$count = 3;
    						while(!$rs){//取3次随机跟踪号，直到成功
    							$count--;
    							$trackVirtual = AutoCode::getCode('fake_track_num');
    							$check = WishOrderMarkShippedLog::model()->getInfoByTrackNum( $trackVirtual,'id' );
    							if(!$check['id']){
    								$rs = true;
    							}
    							if($count<=0) break;
    						}
    						if( !$rs ) continue;
    						$tmpModel = WishOrderMarkShippedLog::model()->findByPk($val['id']);
    						$updateData = array(
    								'id' => $val['id'],
    								'track_num' => $trackVirtual,
    								'carrier_code' => $carrierCode
    						);
    						WishOrderMarkShippedLog::model()->updateData( $tmpModel,$updateData );
    						
    						//2.插入本次log参数日志(用来记录请求的参数)
    						$eventLog = $wishLog->saveEventLog(WishShipment::EVENT_ADVANCE_SHIPPED, array(
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
    						$wishShipmentModel = new WishShipment();
    						$wishShipmentModel->setAccountID($accountID);//设置账号
    						$flag = $wishShipmentModel->uploadSellerShipment( $shippedData );//上传
    						//4.更新日志信息
    						if( $flag ){
    							//5.上传成功更新记录表
    							$updateData = array(
    									'id' => $val['id'],
    									'status' => WishOrderMarkShippedLog::STATUS_SUCCESS,
    									'upload_time' => date('Y-m-d H:i:s'),
    							);
    							WishOrderMarkShippedLog::model()->updateData( $tmpModel,$updateData );
    							$wishLog->saveEventStatus(WishShipment::EVENT_ADVANCE_SHIPPED, $eventLog, $wishLog::STATUS_SUCCESS);
    						}else{
    							$updateData = array(
    									'id' => $val['id'],
    									'status' => WishOrderMarkShippedLog::STATUS_FAILURE,
    									'errormsg' => $wishShipmentModel->getExceptionMessage(),
    									'upload_time' => date('Y-m-d H:i:s'),
    									'error_type' => $this->errorTypeMap(trim($wishShipmentModel->getExceptionMessage())),
    							);
    							WishOrderMarkShippedLog::model()->updateData( $tmpModel,$updateData );
    							$wishLog->saveEventStatus(WishShipment::EVENT_ADVANCE_SHIPPED, $eventLog, $wishLog::STATUS_FAILURE,$wishShipmentModel->getExceptionMessage());
    							$errorMessage .= $wishShipmentModel->getExceptionMessage();
    						}
    						$isSuccess = $isSuccess && $flag;
    					}
    					if( $isSuccess ){
    						$wishLog->setSuccess($logID);
    					}else{
    						if( strlen($errorMessage)>1000 ) $errorMessage = substr($errorMessage,0,900);
    						$wishLog->setFailure($logID, $errorMessage);
    					}
    				}
    				 
    			}
    		}
    		sleep(10);
    	}
    	
    	
    }
    
    /**
     * @desc 上传跟踪号 http://erp_market.com/wish/wishuploadtrack/uploadtracknum/type/1/limit/10/package_id/PK151202036308
     * @desc 1.上传已生成包裹、已有跟踪号的订单。  2.之前发过假单号的，付款时间不超过15天的，如果有真实tn则修改tn。 
     * @author wx
     */
    public function actionUploadtracknum() {
    	exit("已停止");
    	set_time_limit(5*3600);
    	$limit = Yii::app()->request->getParam('limit', '');
    	$type = Yii::app()->request->getParam('type', 1);	//1上传真实单号 2上传预匹配渠道单号
    	$packageId = Yii::app()->request->getParam('package_id', '');
    	$pkCreateDate = date('Y-m-d',strtotime('-20 days')); //推送wms时间
    	
    	/* $hand = Yii::app()->request->getParam('hand');
    	  
    	if( !$hand ) exit('调试中...,稍后开启'); */
    	
    	if( $type == 1 ){
    		//获取要上传的包裹 包含之前提前发货的订单。
    		$packageInfos = OrderPackage::model()->getWishWaitingUploadPackages($pkCreateDate,$packageId,$limit);
    	}else{
    		//获取要上传的预匹配渠道包裹 包含之前提前发货的订单。
    		$packageInfos = OrderPackage::model()->getWishWaitingUploadSpecial($pkCreateDate,$packageId,$limit);
    	}
    	
    	$this->excuteUploadTrack( $packageInfos,$type );
    	
   	}
   	
   	public function excuteUploadTrack( $packageInfos = array(),$type ){
   		exit("已停止");
   		
   		if( empty($packageInfos) ){
   			exit('no data');
   		}
   		
   		//存放部分渠道，用包裹号当跟踪号
   		$specialShipCode = array( strtolower(Logistics::CODE_CM_ZXYZ),strtolower(Logistics::CODE_CM_DEYZ) );
   		
   		$tmpOrderIds = array();
   		foreach( $packageInfos as $key => $val ){
   			if( !in_array($val['order_id'],$tmpOrderIds) ){
   				$tmpOrderIds[] = $val['order_id'];
   			}
   		}
   		//var_dump($tmpOrderIds);exit;
   		//列表字符串有限制，每次查询限制在500以内
   		$ordArr = $this->splitByn($tmpOrderIds,500);
   		//var_dump($ordArr);exit;
   		unset($tmpOrderIds);
   		
   		//循环查出订单,item相关信息，并采集accountid
   		$data = array();
   		$orderArray = array();
   		foreach($ordArr as $val){
   			$orderIdStr = "'".implode("','",$val)."'";
   			$orderList = Order::model()->getInfoListByOrderIds($orderIdStr,'o.order_id,o.account_id,o.platform_order_id,o.paytime',Platform::CODE_WISH);
   			//var_dump($orderList);exit;
   			foreach( $orderList as $k => $v ){
   				if( !in_array($v['account_id'],array_keys($data)) ) {$data[$v['account_id']] = array();}
   				$orderArray[$v['order_id']]['account_id']			= $v['account_id'];
   				$orderArray[$v['order_id']]['platform_order_id']	= $v['platform_order_id'];
   				$orderArray[$v['order_id']]['paytime']				= $v['paytime'];
   			}
   		}
   		//var_dump($orderArray);exit;
   		
   		//按照每个账号来整理数据
   		foreach($packageInfos as $key => $val){
   			$orderInfo = $orderArray[$val['order_id']];
   			/* $shipDate = date('Y-m-d H:i:s',strtotime($orderInfo['paytime'])+3600*1);
   			 if( strtotime($shipDate) > strtotime(gmdate('Y-m-d H:i:s')) ){ //付款时间+1小时 ,若大于当前utc时间
   			continue;
   			} */
   			$currShipCode = !empty($val['real_ship_type'])?$val['real_ship_type']:$val['ship_code'];
   			$carrierCode = LogisticsPlatformCarrier::model()->getCarrierByShipCode( $currShipCode,Platform::CODE_WISH );
   			if(!$carrierCode) continue;
   			$tmp = array(
   					'order_id'			=> $val['order_id'],
   					'platform_order_id'	=> $orderInfo['platform_order_id'],
   					'package_id' 		=> $val['package_id'],
   					'carrier_name'		=> $carrierCode,
   					'tracking_number' 	=> $val['track_num'],
   					'paytime'			=> $orderInfo['paytime'],
   					'ship_code'			=> $currShipCode,
   			);
   			$data[$orderInfo['account_id']][$val['package_id']][] = $tmp;
   		}
   		 
   		//print_r($data);exit;
   		$testAccountIds = $this->getTestAccountIds();
   		foreach( $data as $key => $val ){ //循环账号
   			if( !$val ) continue;
   			$accountInfo = WishAccount::model()->getAccountInfoById( $key );
   			$accountID = $accountInfo['id'];
   			
   			//@todo 测试帐号
   			if(in_array($accountID, $testAccountIds)) continue;
   			
   			$wishLog = new WishLog();
   			$logID = $wishLog->prepareLog($accountID,WishShipment::EVENT_UPLOAD_TRACK);
   			if( $logID ){
   				//1.检查账号是否上传跟踪号
   				$checkRunning = $wishLog->checkRunning($accountID, WishShipment::EVENT_UPLOAD_TRACK);
   				if( !$checkRunning ){
   					$wishLog->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
   				}else{
   					//设置日志为正在运行
   					$wishLog->setRunning($logID);
   					$isSuccess = true;
   					$errorMessage = '';
   					foreach( $val as $pkId=>$vv ){ //循环包裹
   						$isResult = true;
   						foreach( $vv as $vvItem ){ //循环订单明细
   		
   							//检测是否之前有上传过
   							$checkAdvanceShiped = WishOrderMarkShippedLog::model()->getInfoRowByOrderId( $vvItem['order_id'],'*' );
   							if($checkAdvanceShiped['order_id'] && (!in_array($checkAdvanceShiped['error_type'], array(0,1)) || !in_array($checkAdvanceShiped['update_error_type'], array(0,1))) ){ //不满足条件
   								continue;
   							}
   		
   							//准备上传数据
   							if( in_array( $vvItem['ship_code'], $specialShipCode) ){
   								$vvItem['tracking_number'] = $pkId;
   							}
   		
   							//添加详细日志
   							$eventLog = $wishLog->saveEventLog(WishShipment::EVENT_UPLOAD_TRACK, array(
   									'log_id'        => $logID,
   									'account_id'    => $accountID,
   									'platform_order_id'  => $vvItem['platform_order_id'],
   									'order_id'      => $vvItem['order_id'],
   									'package_id'	=> $pkId,
   									'track_number'     => $vvItem['tracking_number'],
   									'carrier_name'  => $vvItem['carrier_name'],
   									'start_time'    => date('Y-m-d H:i:s'),
   							));
   		
   							$shippedData = array(
   									'id' => $vvItem['platform_order_id'],
   									'tracking_number' => $vvItem['tracking_number'],
   									'tracking_provider' => $vvItem['carrier_name'],
   							);
   		
   							//设置账号信息
   							$wishShipmentModel = new WishShipment();
   							$wishShipmentModel->setAccountID($accountID);
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
   										'status' => WishOrderMarkShippedLog::STATUS_DEFAULT,
   										'type'	=> WishOrderMarkShippedLog::TYPE_TRUE,
   								);
   								$markModel = new WishOrderMarkShippedLog();
   								$tmpMarkId = $markModel->saveNewData($markOrderData);
   							}else{
   								$tmpMarkId = $checkAdvanceShiped['id'];
   							}
   		
   							//初始化单个订单上传是否成功的标记
   							$flag = false;
   		
   							//开始上传
   							$tmpModel = WishOrderMarkShippedLog::model()->findByPk($tmpMarkId);
   							if( $checkAdvanceShiped['order_id'] && $checkAdvanceShiped['status'] == WishOrderMarkShippedLog::STATUS_SUCCESS && (time()-strtotime($checkAdvanceShiped['upload_time'])) < 432000 ){ //之前有提前发货，并且提前发货时间距离现在不超过15日的，则调用修改声明发货接口
   								$flag = $wishShipmentModel->modifySellerShipment( $shippedData );//上传
   								$errorMessageSub = $wishShipmentModel->getExceptionMessage();
   									
   								if($flag){ //更新成功
   									$updateData = array(
   											'id' => $tmpMarkId,
   											'update_status' => WishOrderMarkShippedLog::UPDATE_STATUS_SUCCESS,
   											'update_time' => date('Y-m-d H:i:s'),
   											'package_id'	=> $pkId,
   											'update_errormsg' => $errorMessageSub,
   									);
   								}else{
   									$updateData = array(
   											'id' => $tmpMarkId,
   											'update_status' => WishOrderMarkShippedLog::UPDATE_STATUS_FAILURE,
   											'update_time' => date('Y-m-d H:i:s'),
   											'package_id'	=> $pkId,
   											'update_errormsg' => $errorMessageSub,
   											'update_error_type' => $this->errorTypeMap(trim($errorMessageSub)),
   									);
   								}
   								WishOrderMarkShippedLog::model()->updateData( $tmpModel,$updateData );
   							}else{ //之前未提前声明发货，则直接上传真实跟踪号
   								if( !$checkAdvanceShiped['order_id'] || in_array($checkAdvanceShiped['error_type'], array(0,1)) || in_array($checkAdvanceShiped['update_error_type'], array(0,1)) ){
   		
   									$flag = $wishShipmentModel->uploadSellerShipment( $shippedData );//上传
   									$errorMessageSub = $wishShipmentModel->getExceptionMessage();
   									if($flag){ //上传成功
   										$updateData = array(
   												'id' => $tmpMarkId,
   												'status' => WishOrderMarkShippedLog::STATUS_SUCCESS,
   												'upload_time' => date('Y-m-d H:i:s'),
   										);
   									}else{ //上传失败
   										if( $errorMessageSub == 'This order has been fulfilled already' ){ //满足更新条件
   											$wishShipmentModel1 = new WishShipment();
   											$wishShipmentModel1->setAccountID($accountID);
   											$flag = $wishShipmentModel1->modifySellerShipment( $shippedData );//修改跟踪号
   											if($flag){ //更新成功
   												$updateData = array(
   														'id' => $tmpMarkId,
   														'update_status' => WishOrderMarkShippedLog::UPDATE_STATUS_SUCCESS,
   														'update_time' => date('Y-m-d H:i:s'),
   														'package_id'	=> $pkId,
   												);
   											}else{ //更新失败
   												$errorMessageSub = $wishShipmentModel1->getExceptionMessage();
   												$updateData = array(
   														'id' => $tmpMarkId,
   														'update_status' => WishOrderMarkShippedLog::UPDATE_STATUS_FAILURE,
   														'update_time' => date('Y-m-d H:i:s'),
   														'package_id'	=> $pkId,
   														'update_errormsg' => $wishShipmentModel1->getExceptionMessage(),
   														'update_error_type' => $this->errorTypeMap(trim($errorMessageSub)),
   												);
   													
   											}
   										}else{ //不满足更新条件
   											$updateData = array(
   													'id' => $tmpMarkId,
   													'status' => WishOrderMarkShippedLog::STATUS_FAILURE,
   													'upload_time' => date('Y-m-d H:i:s'),
   													'errormsg' => $errorMessageSub,
   													'error_type' => $this->errorTypeMap(trim($errorMessageSub)),
   											);
   										}
   									}
   									WishOrderMarkShippedLog::model()->updateData( $tmpModel,$updateData );
   								}
   									
   							}
   		
   							if( $flag ){
   								//5.上传成功更新记录表
   								$wishLog->saveEventStatus(WishShipment::EVENT_UPLOAD_TRACK, $eventLog, $wishLog::STATUS_SUCCESS);
   							}else{
   								$wishLog->saveEventStatus(WishShipment::EVENT_UPLOAD_TRACK, $eventLog, $wishLog::STATUS_FAILURE,$errorMessageSub);
   								$errorMessage .= $errorMessageSub;
   							}
   							$isResult = $isResult && $flag;
   						}
   						if( $isResult ){
   							UebModel::model('OrderPackage')->updateAll(array('is_confirm_shiped'=>1),' is_confirm_shiped=0 and package_id in("'.$pkId.'")');
   							if( $type == 2 ){
   								UebModel::model('OrderPackageQhPreTrack')->updateByPk( $pkId, array('confirm_shiped_status'=>1,'confirm_shiped_time'=>date('Y-m-d H:i:s')) );
   							}
   						}
   						$isSuccess = $isSuccess && $isResult;
   					}
   					if( $isSuccess ){
   						$wishLog->setSuccess($logID);
   					}else{
   						if( strlen($errorMessage)>1000 ) $errorMessage = substr($errorMessage,0,900);
   						$wishLog->setFailure($logID, $errorMessage);
   					}
   				}
   		
   			}
   		}
   		
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
     * @desc wish返回的错误映射
     */
    public function errorTypeMap( $errorMsg = '' ){
    	$errorCodeMapArr = array(
    			'This order has been fulfilled already' => 1,
    			'The tracking number for this order is confirmed already, it cannot be changed.' => 2,
    			'This tracking number has been used before and cannot be reused' => 3,
    			'Order not in SHIPPED state' => 4,
    	);
    	return empty($errorMsg) || !isset($errorCodeMapArr[$errorMsg]) ?99:$errorCodeMapArr[$errorMsg];
    }
    
    // ==================================== S:新规则追踪号上传  ================================= //
    /**
     * @link wish/wishuploadtrack/uploadordertracenumber/order_id/xx/account_id/xx/limit/1/type/
     */
    public function actionUploadordertracenumber(){
    	/* $traceNumber = OrderPackage::model()->getShunYouTrackNum("CO1701200105920KF", false);
    	var_dump($traceNumber);
    	exit; */
//     	if(!isset($_REQUEST['noexit'])){
//     		exit('xxxxxx');
//     	}
    	set_time_limit(2*3600);
    	ini_set("display_errors", true);
    	error_reporting(E_ALL);
    	//类型数组
    	$types = array(1, 2, 3, 4);
    	//type:
    	//1、取出所有处于备货中订单并且物流方式除了（DHL小包：香港A2B德邮英国小包、香港A2B泽西邮局小包、A2B香港DHL小包 HongKong小包：深圳京华达香港小包）之外的订单包裹追踪号，直接上传
    	//2、取出含备货且物流方式 DHL小包：香港A2B德邮英国小包、香港A2B泽西邮局小包、A2B香港DHL小包 ，HongKong小包：深圳京华达香港小包;
    	//3、欠货待处理 
    	//4、取出待取消 订单
    	//10、指定orderID不管是否已经标记过了
    	//--- 2和3、4这部分订单追踪号获取规则由下：
    	//--- 最先匹配：顺邮宝取到对应的追踪号，
    	//--- 如果匹配不到：优选东莞小包-》挂号-》EUB渠道跟踪号上传（1.订单目的地国家一致  2.订单时间晚于wish订单8-48小时的订单跟踪号） 
    	//--- 最后匹配不到记录数据
    	/**2017-03-31
		新规则：
		1、不欠货包裹原来匹配的云途不适用新YT,欠货部分取YT
		2、所有中邮平邮、外邮平邮取YT
		3、不欠货的包裹，原来是wish邮、挂号的，不取YT
		4、欠货包裹部分（含原来匹配wish邮、挂号但不限于此， 排除掉快递部分）取YT
    	 */
    	//type:
    	//1、取出所有处于备货中订单并且物流方式除了（DHL小包：香港A2B德邮英国小包、香港A2B泽西邮局小包、A2B香港DHL小包 HongKong小包：深圳京华达香港小包、中邮平邮、外邮平邮）之外的订单包裹追踪号，直接上传
    	//2、取出含备货且物流方式 DHL小包：香港A2B德邮英国小包、香港A2B泽西邮局小包、A2B香港DHL小包 ，HongKong小包：深圳京华达香港小包，中邮平邮，外邮平邮;
    	//3、欠货待处理，排除掉快递部分
    	//4、待取消订单
    	//10、指定orderID不管是否已经标记过了
    	//--- 2和3、4这部分订单追踪号获取规则由下：
    	//--- 最先匹配：云途取到对应的追踪号
    	//--- 最后匹配不到记录数据
    	
    	//$testYuntuAccountID = Yii::app()->request->getParam('yuntuaccount_id', 63);//fixturenote
    	$accountId = Yii::app()->request->getParam('account_id');
    	$type = Yii::app()->request->getParam('type');
    	$limit = Yii::app()->request->getParam('limit', 2000);
    	$orderId = Yii::app()->request->getParam('order_id');
    	$pkCreateDate = date('Y-m-d',strtotime('-20 days')); //推送wms时间
    	$bug = Yii::app()->request->getParam('bug');
    	$norun = Yii::app()->request->getParam('norun');
    	$orderModel = new Order();
    	if($orderId){
    		$orderIds = explode(",", $orderId);
    	}else{
    		$orderIds = null;
    	}
    	
    	if($accountId){
    		if(empty($type)) $type = 1;
    		$shipCodeArr = array(
    				Logistics::CODE_CM_DGYZ,
    				Logistics::CODE_EUB
    		);
    		$wishLogModel = new WishLog();
    		$eventName = "upload_track2_".$type;
    		$logID = $wishLogModel->prepareLog($accountId, $eventName);
    		$wishOverseasWarehouseModel = new WishOverseasWarehouse;
    		if( $logID ){
    			//1.检查账号是否上传跟踪号
    			$checkRunning = $wishLogModel->checkRunning($accountId, $eventName);
    			if( !$checkRunning ){
    				$wishLogModel->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
    				exit('There Exists An Active Event');
    			}else{
    				//设置日志为正在运行
    				$wishLogModel->setRunning($logID);
    				// ========== 获取订单 ===========
    				$orderIdArr = array();
    				$gmtime = gmdate('Y-m-d H:i:s'); //当前UTC时间
    				$pendingUploadOrderList = $orderModel->getWishUnshippingTracenumberOrders($accountId, $gmtime, $type, $orderIds, $limit);
    				if($bug){
    					
    					//echo "===========accountId:{$accountId}   testYuntuAccountID:{$testYuntuAccountID}============<br/>";
    					
    					echo "<pre>";
    					echo "===========pendingUploadOrderList==========<br/>";
    					print_r($pendingUploadOrderList);
    				}
    				
    				$errorMessage = "";
    				$isSuccess = true;//为真
    				try{
	    				if($pendingUploadOrderList){
	    					$pendingUploadOrderPackageList = array();
	    					if($type == 4){
	    						$pendingUploadOrderPackageList[0] = $pendingUploadOrderList;
	    					}else{
	    						foreach ($pendingUploadOrderList as $orderPackage){
	    							$pendingUploadOrderPackageList[$orderPackage['package_id']][] = $orderPackage;
	    						}
	    					}
	    					
	    					foreach ($pendingUploadOrderPackageList as $pkId=>$pendingUploadOrderList){
		    					$orderIDArr = array();
		    					$orderDatas = array();
		    					//处理包裹
		    					$isQhPreTrack = false;//是否预上传的顺邮宝
		    					$isSybTrack = false;//是否顺邮宝获取的追踪号
		    					$isYuntuTrack = false;//是否云图获取的追踪号
		    					$isResult = true;//为真
		    					
		    					
		    					foreach ($pendingUploadOrderList as $order){

		    						//初始化单个订单上传是否成功的标记
		    						$flag = false;
		    						$isSybTrack = false;//初始化
		    						$isYuntuTrack = false;
		    						//检测是否之前有上传过
		    						$checkAdvanceShiped = WishOrderMarkShippedLog::model()->getInfoRowByOrderId( $order['order_id'],'*' );
		    						if($checkAdvanceShiped['order_id'] && $checkAdvanceShiped['status'] == WishOrderMarkShippedLog::STATUS_SUCCESS && $checkAdvanceShiped['type'] == WishOrderMarkShippedLog::TYPE_TRUE){ //不满足条件
		    							if($type != 10)
		    								continue;
		    						}
		    						//准备上传数据
		    						if($type == 2 or $type == 3 or $type == 4){
		    							//@todo 过滤掉海外仓发货
		    							//根据订单详情中的item_id与海外仓产品
		    							$orderDetails = OrderDetail::model()->find("order_id='{$order['order_id']}'");
		    							if($bug){
		    								echo "orderDetails<br/>";
		    								//print_r($orderDetails);
		    							}
		    							if(empty($orderDetails)) continue;
		    							$warehouseProduct = $wishOverseasWarehouseModel->find("product_id='{$orderDetails['item_id']}'");
		    							if($bug){
		    								echo "warehouseProduct<br>";
		    								//print_r($warehouseProduct);
		    							}
		    							//if($warehouseProduct) continue;
		    							
		    							//@todo 获取其他方式的追踪号
		    							//a.获取顺邮宝追踪号
		    							$shipTraceNumList = array();
		    							//2017-03-31去掉
		    							/* if($pkId){
		    								$shipTraceNumList = OrderPackage::model()->getWishQhPreTrackPackage($pkId);
		    							} */
		    							
		    							
		    							if(empty($shipTraceNumList)){
		    								//@todo 云途test 2017-03-29  0330全账号使用
		    								//直接云图接口取
		    								//if($accountId == $testYuntuAccountID){//测试账号
		    									$yuntutraceNumber = OrderPackage::model()->getYunTuNum($order['order_id'], false);
		    									if($bug){
		    										echo "===========getYunTuNum============<br/>";
		    										var_dump($yuntutraceNumber);
		    										echo "<br/>";
		    									}
		    									if($yuntutraceNumber){
		    										$shipTraceNumList = array();
		    										$shipTraceNumList[] = array(
		    												'ship_code'		=>	Logistics::CODE_CM_HNXB_YT,
		    												'track_num'		=>	$yuntutraceNumber
		    										);
		    										$isYuntuTrack = true;
		    									}
		    								/* }else{
		    									//直接顺邮宝接口取
		    									$sybtraceNumber = OrderPackage::model()->getShunYouTrackNum($order['order_id'], false);
		    									if($bug){
		    										echo "===========getShunYouTrackNum============<br/>";
		    										var_dump($sybtraceNumber);
		    										echo "<br/>";
		    									}
		    									if($sybtraceNumber){
		    										$shipTraceNumList = array();
		    										$shipTraceNumList[] = array(
		    												'ship_code'		=>	Logistics::CODE_CM_ZX_SYB,
		    												'track_num'		=>	$sybtraceNumber
		    										);
		    										$isSybTrack = true;
		    									}
		    								} */
		    								
		    							}else{
		    								if($bug){
		    									echo "===============QH PRE TRACK============<br/>";
		    								}
		    								$isQhPreTrack = true;
		    							}
		    							
		    							// 2017-03-31注释
		    							/* if(empty($shipTraceNumList)){
		    								//8-48小时内
		    								$timestamp = date("Y-m-d H:i:s", strtotime($order['ori_pay_time']) + 8*3600);
		    								$maxShipDate = date("Y-m-d H:i:s", strtotime($order['ori_pay_time']) + 48*3600);
		    								$shipTraceNumList = (array)OrderPackage::model()->getSpecialPackageList2($timestamp, $order['ship_country'], 10, '', $shipCodeArr, array(), $maxShipDate);
		    								if($bug){
		    									echo "==========shipTraceNumList 1 =======<br/>";
		    									print_r($shipTraceNumList);
		    								}
		    								
	    									//取挂号
	    									$shipTraceNumList2 = (array)OrderPackage::model()->getSpecialPackageListWithGH($timestamp, $order['ship_country'], 10, '', array(), array(), $maxShipDate);
	    									if($bug){
	    										echo "==========shipTraceNumList 2 =======<br/>";
	    										print_r($shipTraceNumList2);
	    									}
	    									if(empty($shipTraceNumList) && empty($shipTraceNumList2)){
	    										if(!WishOrderNotMatchList::model()->checkTraceNumberExists($order['order_id'])){
	    											WishOrderNotMatchList::model()->saveOrderTraceNumberRecord(array(
	    													'order_id'			=>	$order['order_id'],
	    													'ship_country'		=>	$order['ship_country'],
	    													'platform_order_id'	=>	$order['platform_order_id'],
	    													'paytime'			=>	$order['paytime'],
	    													'create_time'		=>	date("Y-m-d H:i:s"),
	    													'account_id'		=>	$accountId
	    											));
	    										}
	    										if($bug){
	    											echo "no shipTraceNumList<br/>";
	    										}
	    										$isResult = $isResult & $flag;
	    										continue;
	    									}
	    									$shipTraceNumList = array_merge($shipTraceNumList, $shipTraceNumList2);
		    								
		    							} */
		    							if(empty($shipTraceNumList)){
		    								if(!WishOrderNotMatchList::model()->checkTraceNumberExists($order['order_id'])){
		    									WishOrderNotMatchList::model()->saveOrderTraceNumberRecord(array(
		    									'order_id'			=>	$order['order_id'],
		    									'ship_country'		=>	$order['ship_country'],
		    									'platform_order_id'	=>	$order['platform_order_id'],
		    									'paytime'			=>	$order['paytime'],
		    									'create_time'		=>	date("Y-m-d H:i:s"),
		    									'account_id'		=>	$accountId
		    									));
		    								}
		    								if($bug){
		    									echo "no shipTraceNumList<br/>";
		    								}
		    								$isResult = $isResult & $flag;
		    								continue;
		    							}
		    						}else{
		    							$shipTraceNumList = array();
		    							//@todo 云途test 2017-03-29 0330全账号使用
		    							//直接云图接口取
		    							// ==============start============= //
		    							//增加一层,可以不需要
		    							$filterShipCode = array(
		    									Logistics::CODE_CM_ZXYZ,
		    									Logistics::CODE_CM_DEYZ,
		    									Logistics::CODE_CM_DHL,
		    									Logistics::CODE_CM_HK,
		    									// === 2017 03 31 ===
		    									// === 中邮 ===
		    									Logistics::CODE_CM_DGYZ,//cm_dgyz
		    									Logistics::CODE_CM_PTXB,//cm_ptxb
		    									// === 外邮 ===
		    									Logistics::CODE_CM_BYYD,//cm_byxb_yd
		    									Logistics::CODE_CM_YW_TEQXB,//cm_yw_teqxb
		    									Logistics::CODE_CM_PLUS_SGXB//cm_plus_sgxb
		    							    
		    							);
		    							if(in_array($order['ship_code'], $filterShipCode)  || ($type == 10 && isset($order['complete_status']) && $order['complete_status'] == Order::COMPLETE_STATUS_PENGDING)){//0330全账号使用
		    								$yuntutraceNumber = OrderPackage::model()->getYunTuNum($order['order_id'], false);
		    								if($bug){
		    									echo "===========getYunTuNum============<br/>";
		    									var_dump($yuntutraceNumber);
		    									echo "<br/>";
		    								}
		    								if($yuntutraceNumber){
		    									$shipTraceNumList[] = array(
		    											'ship_code'		=>	Logistics::CODE_CM_HNXB_YT,
		    											'track_num'		=>	$yuntutraceNumber
		    									);
		    									$isYuntuTrack = true;
		    								}
		    							}
		    							// ================end================== //
		    							
		    							if(!$shipTraceNumList){
		    								$shipTraceNumList[] = array(
		    										'ship_code'		=>	$order['ship_code'],
		    										'track_num'		=>	$order['track_num']
		    								);
		    							}
		    						}
		    						
		    						if($shipTraceNumList){
		    							$subErrorMsg = '';
		    							foreach ($shipTraceNumList as $traceNumber){
		    								$currShipCode = $traceNumber['ship_code'];
		    								$order['track_num'] = $traceNumber['track_num'];
		    								if($bug){
		    									echo "==========currShipCode=======<br/>";
		    									print_r($currShipCode);
		    									echo "<br/>";
		    								}
		    								$carrierCode = LogisticsPlatformCarrier::model()->getCarrierByShipCode( $currShipCode, Platform::CODE_WISH );
		    								if(!$carrierCode){
		    									if($bug){
		    										echo "no carrierCode<br/>";
		    									}
		    									continue;
		    								}
		    									
		    								$shippedData = array(
		    										'id' => $order['platform_order_id'],
		    										'tracking_number' => $order['track_num'],
		    										'tracking_provider' => $carrierCode,
		    								);
		    								if($bug){
		    									echo "===============shippedData=======<br/>";
		    									print_r($shippedData);
		    								}
		    								if($norun){
		    									echo "not run<br/>";
		    									$flag = false;
		    									continue;
		    								}
		    									
		    								//添加详细日志
		    								$eventLog = $wishLogModel->saveEventLog(WishShipment::EVENT_UPLOAD_TRACK, array(
		    										'log_id'        => $logID,
		    										'account_id'    => $accountId,
		    										'platform_order_id'  => $order['platform_order_id'],
		    										'order_id'      => $order['order_id'],
		    										'package_id'	=> $pkId,
		    										'track_number'     => $order['track_num'],
		    										'carrier_name'  => $carrierCode,
		    										'start_time'    => date('Y-m-d H:i:s'),
		    								));
		    								
		    									
		    								//设置账号信息
		    								$wishShipmentModel = new WishShipment();
		    								$wishShipmentModel->setAccountID($accountId);
		    								$errorMessageSub = '';
		    								
		    								//保存订单上传记录
		    								$tmpMarkId = 0;
		    								if( empty($checkAdvanceShiped['order_id']) ){
		    									$markOrderData = array(
		    											'account_id' => $accountId,
		    											'platform_order_id' => $order['platform_order_id'],
		    											'order_id' 		=> $order['order_id'],
		    											'package_id' 	=> $pkId,
		    											'track_num'     => $order['track_num'],
		    											'carrier_code'     => $carrierCode,
		    											'paytime' => $order['paytime'],
		    											'status' => WishOrderMarkShippedLog::STATUS_DEFAULT,
		    											'type'	=> WishOrderMarkShippedLog::TYPE_TRUE,
		    									);
		    									$markModel = new WishOrderMarkShippedLog();
		    									$tmpMarkId = $markModel->saveNewData($markOrderData);
		    								}else{
		    									$tmpMarkId = $checkAdvanceShiped['id'];
		    								}
		    								
		    								
		    								//开始上传
		    								$tmpModel = WishOrderMarkShippedLog::model()->findByPk($tmpMarkId);
		    								if($type != 10 && $checkAdvanceShiped['order_id'] && $checkAdvanceShiped['status'] == WishOrderMarkShippedLog::STATUS_SUCCESS ){ //之前有提前发货，并且提前发货时间距离现在不超过15日的，则调用修改声明发货接口
		    									$flag = true;
		    									if($bug){
		    										echo "==========no upload track==========<br/>";
		    									}
		    									
		    								}else{ //之前未提前声明发货，则直接上传真实跟踪号
		    									
		    										$flag = $wishShipmentModel->uploadSellerShipment( $shippedData );//上传
		    										$errorMessageSub = $wishShipmentModel->getExceptionMessage();
		    										if($bug){
			    										echo "===========flag ======================<br/>";
			    										var_dump($flag);
			    										echo "<br/>";
			    										echo "===========errorMessageSub===========<br/>";
			    										echo $errorMessageSub;
		    										}
		    										$errType = $this->errorTypeMap(trim($errorMessageSub));
		    										if($bug){
		    											echo "================ errType ===============<br/>";
		    											var_dump($errType);
		    											echo "<br/>";
		    										}
		    										if($flag || $errType == 1 || $errType == 2){ //上传成功
		    											$updateData = array(
		    													'id' => $tmpMarkId,
		    													'status' => WishOrderMarkShippedLog::STATUS_SUCCESS,
		    													'upload_time' => date('Y-m-d H:i:s'),
		    											);
		    											$flag = true;
		    											//如果是顺邮宝接口直接获取的记录日志
		    											if($isSybTrack){
		    												OrderPackage::model()->saveSybTrackNumberRecordWithOrderID($order['track_num'], $order['order_id'], false);
		    											}
		    											//如果是云图接口直接获取的记录日志
		    											if($isYuntuTrack){
		    												OrderPackage::model()->saveYuntuTrackNumberRecordWithOrderID($order['track_num'], $order['order_id'], false);
		    											}
		    										}else{ //上传失败
	    												$updateData = array(
	    														'id' => $tmpMarkId,
	    														'status' => WishOrderMarkShippedLog::STATUS_FAILURE,
	    														'upload_time' => date('Y-m-d H:i:s'),
	    														'errormsg' => $errorMessageSub,
	    														'error_type' => $errType,
	    												);
		    										}
		    										WishOrderMarkShippedLog::model()->updateData( $tmpModel,$updateData );
		    								}
		    								if($bug){
												echo "===========flag2=========<br/>";
												var_dump($flag);
		    								}
		    								if( $flag ){
		    									//5.上传成功更新记录表
		    									$wishLogModel->saveEventStatus(WishShipment::EVENT_UPLOAD_TRACK, $eventLog, $wishLogModel::STATUS_SUCCESS);
		    									$subErrorMsg = "";
		    									break;//跳出循环
		    								}else{
		    									$wishLogModel->saveEventStatus(WishShipment::EVENT_UPLOAD_TRACK, $eventLog, $wishLogModel::STATUS_FAILURE, $errorMessageSub);
		    									$subErrorMsg = "pk:".$pkId. ",ORDER_ID:{$order['order_id']} " . $errorMessageSub;
		    								}
		    							}
		    							$errorMessage .= $subErrorMsg;
		    						}
		    						$isResult = $isResult && $flag;
		    						
		    					}
		    					
		    					if($bug){
		    						echo "=========== isResult && pkId =============<br/>";
		    						var_dump($isResult);
		    						echo "   ", $pkId , "<br/>";
		    					}
		    					if(!$norun){
		    						if( $isResult && $pkId){
		    							if($pkId){
		    								UebModel::model('OrderPackage')->updateAll(array('is_confirm_shiped'=>1),' is_confirm_shiped=0 and package_id in("'.$pkId.'")');
		    								if( $isQhPreTrack ){
		    									UebModel::model('OrderPackageQhPreTrack')->updateByPk( $pkId, array('confirm_shiped_status'=>1,'confirm_shiped_time'=>date('Y-m-d H:i:s')) );
		    								}
		    							}
		    						}
		    					}
		    					
		    					$isSuccess = $isSuccess && $isResult;
	    					}
	    				}
    				}catch (Exception $e){
    					$isSuccess = false;
    					$errorMessage.="<br/>".$e->getMessage();
    				}
    				//@todo 
    				if($isSuccess){
    					$wishLogModel->setSuccess($logID, "done");
    				}else{
    					$wishLogModel->setFailure($logID, $errorMessage);
    				}
    				if($bug){
    					echo "==================msg============<br/>";
    					echo $errorMessage;
    				}
    				
    			}
    		}
    	}else{
    		
    		/* $accountIds = $this->getTestAccountIds();
    		foreach ($accountIds as $accountID){
    			if(empty($type)){
    				foreach ($types as $_type){
    					$url = Yii::app()->request->hostInfo.'/' . $this->route . '/account_id/' . $accountID."/type/".$_type."/limit/".$limit."/order_id/".$orderId;
    					echo $url,"<br/>";
    					MHelper::runThreadBySocket ( $url );
    					sleep(15);
    				}
    			}else{
    				$url = Yii::app()->request->hostInfo.'/' . $this->route . '/account_id/' . $accountID."/type/".$type."/limit/".$limit."/order_id/".$orderId;
    				echo $url,"<br/>";
    				MHelper::runThreadBySocket ( $url );
    			}
    			sleep(60);
    		}
    		
    		
    		exit("指定账号ID"); */
    		
    		
    		//@todo 
    		//循环每个账号发送一个拉listing的请求
    		$accountList = WishAccount::model()->getCronGroupAccounts();
    		foreach ($accountList as $accountID) {
    			if(empty($type)){
	    			foreach ($types as $_type){
	    				$url = Yii::app()->request->hostInfo.'/' . $this->route . '/account_id/' . $accountID."/type/".$_type."/limit/".$limit."/order_id/".$orderId;
	    				echo $url,"<br/>";
	    				MHelper::runThreadBySocket ( $url );
	    				sleep(15);
	    			}
	    		}else{
	    			$url = Yii::app()->request->hostInfo.'/' . $this->route . '/account_id/' . $accountID."/type/".$type."/limit/".$limit."/order_id/".$orderId;
	    			echo $url,"<br/>";
	    			MHelper::runThreadBySocket ( $url );
	    		}
	    		sleep(60);
    		}
    	}
    }
    
    /**
     * @desc 海外仓订单上传和标记追踪号
     * @link /wish/wishuploadtrack/uploadhwordertracernumber/account_id/1/bug/1
     */
    public function actionUploadhwordertracernumber(){
    	set_time_limit(2*3600);
    	ini_set("display_errors", true);
    	error_reporting(E_ALL);
    	//类型数组
    	$types = array(-1, 1);
    	//type:
    	//1、取出所有处于备货中订单并且物流方式除了（DHL小包：香港A2B德邮英国小包、香港A2B泽西邮局小包、A2B香港DHL小包 HongKong小包：深圳京华达香港小包）之外的订单包裹追踪号，直接上传
    	//-1、 在1基础上取出未生成追踪号的部分
    	// 10 指定orderID,不管是否已经标记过，强制重新上传标记
    	
    	$accountId = Yii::app()->request->getParam('account_id');
    	$type = Yii::app()->request->getParam('type');
    	$limit = Yii::app()->request->getParam('limit', 2000);
    	$orderId = Yii::app()->request->getParam('order_id');
    	$bug = Yii::app()->request->getParam('bug');
    	$norun = Yii::app()->request->getParam('norun');
    	$pkCreateDate = date('Y-m-d',strtotime('-20 days')); //推送wms时间
    	$orderModel = new Order();
    	if($orderId){
    		$orderIds = explode(",", $orderId);
    	}else{
    		$orderIds = null;
    	}
    	if($accountId){
    		if(empty($type)) $type = 1;
    		if(! in_array($type, array(-1, 1, 10))){
    			$type = 1;
    		}
    		$shipCodeArr = array(
    				Logistics::CODE_CM_DGYZ,
    				Logistics::CODE_EUB
    		);
    		$wishLogModel = new WishLog();
    		$eventName = "upload_track3_".$type;
    		$logID = $wishLogModel->prepareLog($accountId, $eventName);
    		$wishOverseasWarehouseModel = new WishOverseasWarehouse;
    		if( $logID ){
    			//1.检查账号是否上传跟踪号
    			$checkRunning = $wishLogModel->checkRunning($accountId, $eventName);
    			if( !$checkRunning ){
    				$wishLogModel->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
    				exit('There Exists An Active Event');
    			}else{
    				//设置日志为正在运行
    				$wishLogModel->setRunning($logID);
    				// ========== 获取订单 ===========
    				$orderIdArr = array();
    				$gmtime = gmdate('Y-m-d H:i:s'); //当前UTC时间
    				$isLocalWarehouse = false;//海外仓
    				$pendingUploadOrderList = $orderModel->getWishUnshippingTracenumberOrders($accountId, $gmtime, $type, $orderIds, $limit, $isLocalWarehouse);
    				if($bug){
    					echo "<pre>";
    					echo "===========pendingUploadOrderList==========<br/>";
    					print_r($pendingUploadOrderList);
    				}
    				$errorMessage = "";
    				$isSuccess = true;//为真
    				try{
    					if($pendingUploadOrderList){
    						$pendingUploadOrderPackageList = array();
    						foreach ($pendingUploadOrderList as $orderPackage){
    							$pendingUploadOrderPackageList[$orderPackage['package_id']][] = $orderPackage;
    						}
    						foreach ($pendingUploadOrderPackageList as $pkId=>$pendingUploadOrderList){
    							$orderIDArr = array();
    							$orderDatas = array();
    							//处理包裹
    							$isResult = true;//为真
    				    
    							foreach ($pendingUploadOrderList as $order){
    								//初始化单个订单上传是否成功的标记
    								$flag = false;
    								$isSybTrack = false;//初始化
    								//检测是否之前有上传过,并且成功了的
    								$checkAdvanceShiped = WishOrderMarkShippedLog::model()->getInfoRowByOrderId( $order['order_id'],'*' );
    								if($checkAdvanceShiped['order_id'] && $checkAdvanceShiped['status'] == WishOrderMarkShippedLog::STATUS_SUCCESS && $checkAdvanceShiped['type'] == 2){ //不满足条件
    									if($type != 10)
    										continue;
    								}
    								if(empty($order['track_num'])){
    									//检查是否已经标记过
    									if($checkAdvanceShiped['order_id'] && $checkAdvanceShiped['status'] == WishOrderMarkShippedLog::STATUS_SUCCESS && $checkAdvanceShiped['type'] == 1){ //不满足条件
    										if($type != 10){
	    										if($bug){
	    											echo "<br/>has already marked：{$order['order_id'] }<br/>";
	    										}
	    										continue;
    										}
    									}
    								}
    								//物流商
    								$currShipCode = $order['ship_code'];
    								$carrierCode = LogisticsPlatformCarrier::model()->getCarrierByShipCode( $currShipCode, Platform::CODE_WISH );
    								if(!$carrierCode){
    									if($bug){
    										echo "no carrierCode<br/>";
    									}
    									continue;
    								}
    									
    								$shippedData = array(
    										'id' => $order['platform_order_id'],
    										'tracking_number' => empty($order['track_num']) ?  '' : $order['track_num'],
    										'tracking_provider' => $carrierCode,
    								);
    								if($bug){
    									echo "===============shippedData=======<br/>";
    									print_r($shippedData);
    								}
    								if($norun){
    									echo "not run<br/>";
    									$flag = false;
    									continue;
    								}
    									
    								//添加详细日志
    								$eventLog = $wishLogModel->saveEventLog(WishShipment::EVENT_UPLOAD_TRACK, array(
    										'log_id'        => $logID,
    										'account_id'    => $accountId,
    										'platform_order_id'  => $order['platform_order_id'],
    										'order_id'      => $order['order_id'],
    										'package_id'	=> $pkId,
    										'track_number'     => $order['track_num'],
    										'carrier_name'  => $carrierCode,
    										'start_time'    => date('Y-m-d H:i:s'),
    								));
    								
    									
    								//设置账号信息
    								$wishShipmentModel = new WishShipment();
    								$wishShipmentModel->setAccountID($accountId);
    								$errorMessageSub = '';
    								
    								//保存订单上传记录
    								$tmpMarkId = 0;
    								$isUpdate = false;
    								$markLogType = ($type == -1 ? WishOrderMarkShippedLog::TYPE_FAKE : WishOrderMarkShippedLog::TYPE_TRUE);
    								if( empty($checkAdvanceShiped['order_id']) ){
    									//如果没有记录则创建一条新的
    									$markOrderData = array(
    											'account_id' => $accountId,
    											'platform_order_id' => $order['platform_order_id'],
    											'order_id' 		=> $order['order_id'],
    											'package_id' 	=> $pkId,
    											'track_num'     => $order['track_num'],
    											'carrier_code'     => $carrierCode,
    											'paytime' => $order['paytime'],
    											'status' => WishOrderMarkShippedLog::STATUS_DEFAULT,
    											'type'	=> $markLogType,
    									);
    									$markModel = new WishOrderMarkShippedLog();
    									$tmpMarkId = $markModel->saveNewData($markOrderData);
    									$isUpdate = true;
    								}else{
    									$tmpMarkId = $checkAdvanceShiped['id'];
    								}
    								
    								//开始上传
    								$tmpModel = WishOrderMarkShippedLog::model()->findByPk($tmpMarkId);
    								
    								if($checkAdvanceShiped['order_id'] && $checkAdvanceShiped['status'] == WishOrderMarkShippedLog::STATUS_SUCCESS && $checkAdvanceShiped['type'] == 1){ //不满足条件
    									//以前标记过成功，现在就是修改
    									$flag = $wishShipmentModel->modifySellerShipment($shippedData);
    								}else{
    									//直接上传
    									$flag = $wishShipmentModel->uploadSellerShipment($shippedData);
    								}
    								$errorMessageSub = $wishShipmentModel->getExceptionMessage();
    								$errType = $this->errorTypeMap(trim($errorMessageSub));
    								if($bug){
    									echo "================ errorMessageSub : {$errorMessageSub}===============<br/>";
    									echo "================ errType ===============<br/>";
    									var_dump($errType);
    									echo "<br/>";
    								}
    								if($flag || $errType == 1 || $errType == 2){ //上传成功
    									$updateData = array(
    											'id' => $tmpMarkId,
    											'status' => WishOrderMarkShippedLog::STATUS_SUCCESS,
    											'upload_time' => date('Y-m-d H:i:s'),
    											'type'	=> $markLogType,
    									);
    									if($flag){
    										$updateData['track_num'] =  $order['track_num'];
    										$updateData['carrier_code']  = $carrierCode;
    									}
    									$flag = true;
    								}else{ //上传失败
    									$updateData = array(
    											'id' => $tmpMarkId,
    											'status' => WishOrderMarkShippedLog::STATUS_FAILURE,
    											'upload_time' => date('Y-m-d H:i:s'),
    											'errormsg' => $errorMessageSub,
    											'error_type' => $errType,
    											'type'	=> $markLogType,
    									);
    								}
    								WishOrderMarkShippedLog::model()->updateData( $tmpModel, $updateData);
    								if($bug){
    									echo "===========flag2=========<br/>";
    									var_dump($flag);
    								}
    								if( $flag ){
    									//5.上传成功更新记录表
    									$wishLogModel->saveEventStatus(WishShipment::EVENT_UPLOAD_TRACK, $eventLog, $wishLogModel::STATUS_SUCCESS);
    									$subErrorMsg = "";
    									break;//跳出循环
    								}else{
    									$wishLogModel->saveEventStatus(WishShipment::EVENT_UPLOAD_TRACK, $eventLog, $wishLogModel::STATUS_FAILURE, $errorMessageSub);
    									$subErrorMsg = "pk:".$pkId. ",ORDER_ID:{$order['order_id']} " . $errorMessageSub;
    								}
    								$errorMessage .= $subErrorMsg;
    							}
    							
    							
    							$isResult = $isResult && $flag;
    							if($bug){
    								echo "=========== isResult && pkId =============<br/>";
    								var_dump($isResult);
    								echo "   ", $pkId , "<br/>";
    							}
    							if(!$norun){
    								if( $isResult && $pkId && $type == 1){
    									UebModel::model('OrderPackage')->updateAll(array('is_confirm_shiped'=>1),' is_confirm_shiped=0 and package_id in("'.$pkId.'")');
    								}
    							}
    							$isSuccess = $isSuccess && $isResult;
    						}
    					}
    				}catch (Exception $e){
    					$isSuccess = false;
    					$errorMessage.="<br/>".$e->getMessage();
    				}
    				//@todo 
    				if($isSuccess){
    					$wishLogModel->setSuccess($logID, "done");
    				}else{
    					$wishLogModel->setFailure($logID, $errorMessage);
    				}
    				if($bug){
    					echo "==================msg============<br/>";
    					echo $errorMessage;
    				}
    			}
    		}
    	}else{
    		//循环每个账号发送一个拉listing的请求
    		$accountList = WishAccount::model()->getCronGroupAccounts();
    		foreach ($accountList as $accountID) {
    			if(empty($type)){
    				foreach ($types as $_type){
    					$url = Yii::app()->request->hostInfo.'/' . $this->route . '/account_id/' . $accountID."/type/".$_type."/limit/".$limit."/order_id/".$orderId;
    					echo $url,"<br/>";
    					//@todo test
    					MHelper::runThreadBySocket ( $url );
    					sleep(15);
    				}
    			}else{
    				$url = Yii::app()->request->hostInfo.'/' . $this->route . '/account_id/' . $accountID."/type/".$type."/limit/".$limit."/order_id/".$orderId;
    				echo $url,"<br/>";
    				//@todo test
    				MHelper::runThreadBySocket ( $url );
    			}
    			sleep(10);
    		}
    	}
    	
    }
    
    
    private function getTestAccountIds(){
    	return array(
    		22,//fashionqueens
    		2,//uebfashion 
    		1,//newfrog 
    	);
    }
  
    // ==================================== E:新规则追踪号上传  ================================= //
    
    
    
    // ================  临时特殊追踪号上传，请不要随便调用 ====================================== //
    
    public function actionUploadspecialtracknum(){
    	set_time_limit(2*3600);
    	error_reporting(E_ALL);
    	ini_set("display_errors", true);
    	$accountIdParam = Yii::app()->request->getParam('account_id','');
    	$orderId = Yii::app()->request->getParam('order_id');
    	$limit = Yii::app()->request->getParam('limit', '1000');
    	$type = Yii::app()->request->getParam('type');//1为顺邮宝取号 2为云途取号
    	$gmtime = gmdate('Y-m-d H:i:s', time()-5*3600); //当前UTC时间
    	$wishAccounts = WishAccount::model()->getAbleAccountList();
    	//$shipCodeArr = array('cm_dgyz', 'cm_jrxb');
    	$shipCodeArr = WishSpecialOrderShipCode::model()->getAvailableShipCodes();
    	/* if($type == 1){
    		exit("暂停运行");
    	} */
    	if(empty($shipCodeArr)){
    		exit('Ship Code Empty');
    	}
    	$carrierCodeArr = array();
    	foreach ($shipCodeArr as $code){
    		$carrierCodeArr[$code] = LogisticsPlatformCarrier::model()->getCarrierByShipCode($code , Platform::CODE_WISH );
    	}
    	$carrierCodeArr[Logistics::CODE_CM_ZX_SYB] = LogisticsPlatformCarrier::model()->getCarrierByShipCode(Logistics::CODE_CM_ZX_SYB , Platform::CODE_WISH );
    	$minShipDate = WishSpecialOrderTraceNumber::model()->getMaxShipDateField(); //@todo 取出最大的出货时间
    	foreach($wishAccounts as $account){
    		$accountID = $account['id'];
    		if( !empty($accountIdParam) && $accountID != $accountIdParam ) continue;
    		if( $accountID ){
    			//加日志
    			$wishLog = new WishLog();
    			$logID = $wishLog->prepareLog($accountID,WishShipment::EVENT_UPLOAD_SPECIAL_TRACK);
    			if( $logID ){
    				//1.检查账号是否上传跟踪号
    				$checkRunning = $wishLog->checkRunning($accountID, WishShipment::EVENT_UPLOAD_SPECIAL_TRACK);
    				if( !$checkRunning ){
    					$wishLog->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
    				}else{
    					//设置日志为正在运行
    					$wishLog->setRunning($logID);
    					$errorMessage = "";
    					$offset = 0;
    					//@note 取1000条足够了
		    			$orderInfos = WishSpecialOrder::model()->getWishWaitingConfirmOrders($accountID, $gmtime, $orderId, $limit, 0, $type);
		    			if(isset($_REQUEST['bug'])){
		    				echo "=====0000=====<br/>";
		    				echo "<pre>";
		    				print_r($orderInfos);
		    			}
		    			foreach ($orderInfos as $order){
		    				$shipTraceNumList = array();
		    				$traceType = 0;
		    				if($type == 1){
		    					$sybTraceNum = OrderPackage::model()->getShunYouTrackNum($order['order_id'], true);
		    					if(!$sybTraceNum) continue;
		    					$shipTraceNumList[] = array(
			    							'ship_code'		=>	Logistics::CODE_CM_ZX_SYB,
			    							'ship_date'		=>	date("Y-m-d H:i:s"),
			    							'track_num'		=>	$sybTraceNum,
		    								'is_syb'			=>	1
		    							);
		    				}elseif($type == 2){
		    					$yuntuTraceNum = OrderPackage::model()->getYunTuNum($order['order_id'], true);
		    					if(!$yuntuTraceNum) continue;
		    					$shipTraceNumList[] = array(
			    							'ship_code'		=>	Logistics::CODE_CM_HNXB_YT,
			    							'ship_date'		=>	date("Y-m-d H:i:s"),
			    							'track_num'		=>	$yuntuTraceNum,
		    								'is_yuntu'		=>	1
		    							);
		    				}else{
		    					//@todo 获取大于当前订单时间已存在的追踪号
		    					$timestamp = date("Y-m-d H:i:s", strtotime($order['ori_pay_time']) + 8*3600);
		    					$filterTraceNumberList = WishSpecialOrderTraceNumber::model()->getTraceNumberListByOrderDownTime($timestamp, $order['ship_country']);
		    					 
		    					$shipTraceNumList = (array)OrderPackage::model()->getSpecialPackageList2($timestamp, $order['ship_country'], 10, '', $shipCodeArr, $filterTraceNumberList);
		    					if(isset($_REQUEST['bug'])){
		    						echo "===== 111====<br/>";
		    						print_r($order);
		    						 
		    						print_r($shipTraceNumList);
		    					}
		    					
		    					//循环判断
		    					//if(empty($shipTraceNumList)){
		    					$shipTraceNumList2 = (array)WishSpecialOrderImportTraceNumber::model()->getValideTraceNumberList($shipCodeArr, $order['ship_country'], $timestamp, 5);
		    					//$traceType = 1;
		    					if(isset($_REQUEST['bug'])){
		    						echo "<pre>";
		    						echo "===== 2222 ====<br/>";
		    						print_r($shipTraceNumList2);
		    					}
		    					 
		    					//}
		    					$shipTraceNumList = array_merge($shipTraceNumList, $shipTraceNumList2);
		    					if(empty($shipTraceNumList)){
		    						continue;
		    					}
		    				}
		    				
		    				
		    				if(isset($_REQUEST['bug'])){
		    					echo "<pre>";
		    					echo "===== 33333 ====<br/>";
		    					print_r($shipTraceNumList);
		    				}
		    				$tempMessage = "";
		    				foreach ($shipTraceNumList as $traceNum){
		    					if(isset($traceNum['id']) && $traceNum['id']){
		    						$traceType = 1;
		    					}else{
		    						$traceType = 0;
		    					}
		    					$minShipDate = $traceNum['ship_date'];
		    					//查找是否已经存在
		    					if(WishSpecialOrderTraceNumber::model()->checkTraceNumberExists($traceNum['track_num'], $traceNum['ship_code'])){
		    						if(isset($_REQUEST['bug'])){
		    							echo $traceNum['track_num'], $traceNum['ship_code'], "has Exists <br/>";
		    						}
		    						if($traceType == 1){
		    							WishSpecialOrderImportTraceNumber::model()->updateDataByID($traceNum['id'], array('status'=>1));
		    						}
		    						continue;
		    					}
		    					//组装接口数据
		    					$shippedData = array(
		    								'id' => $order['platform_order_id'],
		    								'tracking_number' => $traceNum['track_num'],
		    								'tracking_provider' => $carrierCodeArr[$traceNum['ship_code']],
		    						);
		    					
		    					$wishShipmentModel = new WishShipment();
		    					$flag = false;
		    					
		    					
		    					$wishShipmentModel->setAccountID($accountID);//设置账号
		    					$flag = $wishShipmentModel->uploadSellerShipment( $shippedData );//上传 
		    					if(isset($_REQUEST['bug'])){
		    						if($flag){
		    							echo $order['platform_order_id'], " success <br/>";
		    						}else {
		    							echo $order['platform_order_id'], " failure " . $wishShipmentModel->getExceptionMessage(). " <br/>";		    							
		    						}
		    					}
		    					if($flag){
		    						if(isset($traceNum['is_syb']) && $traceNum['is_syb']){
		    							OrderPackage::model()->saveSybTrackNumberRecordWithOrderID($traceNum['track_num'], $order['order_id'], true);
		    						}
		    						if(isset($traceNum['is_yuntu']) && $traceNum['is_yuntu']){
		    							OrderPackage::model()->saveYuntuTrackNumberRecordWithOrderID($traceNum['track_num'], $order['order_id'], true);
		    						}
		    						// ship_status complete_status
		    						WishSpecialOrder::model()->updateColumnByOrderID($order['order_id'], array('ship_status'=>WishSpecialOrder::SHIP_STATUS_YES, 'complete_status'=>WishSpecialOrder::COMPLETE_STATUS_END));
		    						//入库记录
		    						WishSpecialOrderTraceNumber::model()->saveOrderTraceNumberRecord(array(
		    																							'order_id'			=>	$order['order_id'],
		    																							'platform_order_id'	=>	$order['platform_order_id'],
		    																							'ship_code'			=>	$traceNum['ship_code'],
		    																							'ship_date'			=>	$traceNum['ship_date'],
		    																							'trace_number'		=>	$traceNum['track_num'],
		    																							'create_time'		=>	date("Y-m-d H:i:s"),
		    																							'message'			=>	''
		    																						));
		    						$tempMessage = "";
		    						if($traceType == 1){
		    							WishSpecialOrderImportTraceNumber::model()->updateDataByID($traceNum['id'], array('status'=>1));
		    						}
		    						break;
		    					}else{
		    						$msg = $wishShipmentModel->getExceptionMessage();
		    						if(trim($msg) == "This tracking number has been used before and cannot be reused" && $traceType == 1){
		    							WishSpecialOrderImportTraceNumber::model()->updateDataByID($traceNum['id'], array('status'=>1));
		    						}elseif(trim($msg) == "The tracking number for this order is confirmed already, it cannot be changed."){
		    							WishSpecialOrder::model()->updateColumnByOrderID($order['order_id'], array('ship_status'=>WishSpecialOrder::SHIP_STATUS_YES, 'complete_status'=>WishSpecialOrder::COMPLETE_STATUS_END));
		    							break;
		    						}
		    						$tempMessage = "OrderID:{$order['order_id']} fail:{$msg}\r\n";
		    					}

		    				}
		    				$errorMessage .= $tempMessage;
		    			}
		    			if( empty($errorMessage) ){
		    				$wishLog->setSuccess($logID);
		    			}else{
		    				if( strlen($errorMessage)>1000 ) $errorMessage = substr($errorMessage,0,900);
		    				$wishLog->setFailure($logID, $errorMessage);
		    			}
    				}
    			}
    		}
    		//sleep(10);
    	}
    }
    
    /**
     * @desc 自动导出顺邮宝追踪号csv文件 
     * @link /wish/wishuploadtrack/autoexportsybtrackcsv  #每天14:20分钟运行一次 2016-11-04 14:00:00 2016-11-05 14:00:00 2016-11-06 14:00:00
     */
    public function actionAutoexportsybtrackcsv(){
    	set_time_limit(2*3600);
    	error_reporting(E_ALL);
    	ini_set("display_errors", true);
    	//
    	$beginTime = Yii::app()->request->getParam("begin");
    	$endTime = Yii::app()->request->getParam("end");
    	//加日志
    	$accountID = 10000;//虚拟
    	$wishLog = new WishLog();
    	$eventName = "exportsybtrack";
    	$logID = $wishLog->prepareLog($accountID, $eventName);
    	if( $logID ){
    		//1.检查账号是否上传跟踪号
    		$checkRunning = $wishLog->checkRunning($accountID, $eventName);
    		if( !$checkRunning ){
    			$wishLog->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
    			exit('There Exists An Active Event');
    		}else{
    			//设置日志为正在运行
    			$wishLog->setRunning($logID);
    			//导出csv
    			$wishsybModel = WishSybTrackList::model();
    			$res = WishSybTrackList::model()->getExportData($beginTime, $endTime);
    			if($res){
    				$wishLog->setSuccess($logID, "done");
    			}else{
    				$wishLog->setFailure($logID, $wishsybModel->getExceptionMsg());
    			}
    			
    		}
    	}else{
    		exit("Failure Log Id!!!");
    	}
    }
    
    
    /**
     * @desc 自动导出云图追踪号csv文件
     * @link /wish/wishuploadtrack/autoexportyuntutrackcsv  #每天14:20分钟运行一次 2016-11-04 14:00:00 2016-11-05 14:00:00 2016-11-06 14:00:00
     */
    public function actionAutoexportyuntutrackcsv(){
    	set_time_limit(2*3600);
    	error_reporting(E_ALL);
    	ini_set("display_errors", true);
    	//
    	$beginTime = Yii::app()->request->getParam("begin");
    	$endTime = Yii::app()->request->getParam("end");
    	//加日志
    	$accountID = 10000;//虚拟
    	$wishLog = new WishLog();
    	$eventName = "exportyuntutrack";
    	$logID = $wishLog->prepareLog($accountID, $eventName);
    	if( $logID ){
    		//1.检查账号是否上传跟踪号
    		$checkRunning = $wishLog->checkRunning($accountID, $eventName);
    		if( !$checkRunning ){
    			$wishLog->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
    			exit('There Exists An Active Event');
    		}else{
    			//设置日志为正在运行
    			$wishLog->setRunning($logID);
    			//导出csv
    			$wishytModel = WishYuntuTrackList::model();
    			$res = WishYuntuTrackList::model()->getExportData($beginTime, $endTime);
    			if($res){
    				$wishLog->setSuccess($logID, "done");
    			}else{
    				$wishLog->setFailure($logID, $wishytModel->getExceptionMsg());
    			}
    			 
    		}
    	}else{
    		exit("Failure Log Id!!!");
    	}
    }
    
    /**
     * @desc 自动导出没有匹配到国家订单csv文件
     * @link /wish/wishuploadtrack/autoexportnomatchcsv  #每天15:10分钟运行一次
     */
    public function actionAutoexportnomatchcsv(){
    	set_time_limit(2*3600);
    	error_reporting(E_ALL);
    	ini_set("display_errors", true);
    	//
    	$beginTime = Yii::app()->request->getParam("begin");
    	$endTime = Yii::app()->request->getParam("end");
    	//加日志
    	$accountID = 10000;//虚拟
    	$wishLog = new WishLog();
    	$eventName = "exportnotmatch";
    	$logID = $wishLog->prepareLog($accountID, $eventName);
    	if( $logID ){
    		//1.检查账号是否上传跟踪号
    		$checkRunning = $wishLog->checkRunning($accountID, $eventName);
    		if( !$checkRunning ){
    			$wishLog->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
    			exit('There Exists An Active Event');
    		}else{
    			//设置日志为正在运行
    			$wishLog->setRunning($logID);
    			
    			$model = new WishOrderNotMatchList;
    			$res = $model->getExportCsv($beginTime, $endTime);
    			if($res){
    				$wishLog->setSuccess($logID, "done");
    			}else{
    				$wishLog->setFailure($logID, $model->getExceptionMsg());
    			}
    		}
    	}else{
    		exit("Failure Log Id!!!");
    	}
    }
    
    // ================  临时特殊追踪号上传，请不要随便调用 ====================================== //
    
    /**
     * @desc
     * @link /wish/wishapirequest/test/pkid/xx/isTest/1
     */
    public function actionTst(){
    	$pkId = Yii::app()->request->getParam("pkid");
    	/* $ret = UebModel::model("OrderPackage")->getYunTuNum($pkId);
    	OrderPackage::model()->saveYuntuTrackNumberRecordWithOrderID($ret, $pkId, false);
    	var_dump($ret); */
    	if($pkId){
    		$pkIds = explode(",", $pkId);
    		OrderPackage::model()->getDbConnection()->createCommand()->update("ueb_order_package", array('is_confirm_shiped'=>0), array("IN", "package_id", $pkIds));
    	}
    }
    
    
    public function actionSavesybtracknum(){
    	/* set_time_limit(120);
    	error_reporting(E_ALL);
    	ini_set("display_errors", true);
    	$model = WishSpecialOrderTraceNumber::model();
    	$list = $model->findAll("ship_code='cm_zx_syb'");
    	if($list){
    		foreach ($list as $o){
    			OrderPackage::model()->saveSybTrackNumberRecordWithOrderID($o['trace_number'], $o['order_id'], true, $o['ship_date']);
    		}
    	}
    	echo "done"; */
    }
}