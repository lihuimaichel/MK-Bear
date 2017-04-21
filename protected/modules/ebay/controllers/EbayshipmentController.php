<?php
/**
 * @desc Ebay物流相关
 * @author wx
 * @since 2016-08-30
 */
class EbayshipmentController extends UebController{
    
    /**
     * @desc 访问过滤配置
     * @see CController::accessRules()
     */
    public function accessRules() {
        return array(
			array(
				'allow',
				'users' => array('*'),
				'actions' => array('confirmshipped','uploadtracknum','wxtest','uploadtracknumpretrack','confirmshippedthread')
			),
		);
    }
    
    /**
     * @desc 设置订单已发货 http://erp_market.com/ebay/ebayshipment/confirmshipped [辅助程序，防止漏提交]
     * @desc 针对Ebay平台下单后，未发货的订单声明发货 
     * @author wx
     */
    public function actionConfirmShipped() {
    	//exit('CLOSED');
    	set_time_limit(3*3600);
    	$accountIdParam = Yii::app()->request->getParam('account_id','');
    	$orderId = Yii::app()->request->getParam('order_id','');
    	$limit = Yii::app()->request->getParam('limit', '');
    	$gmtime = gmdate('Y-m-d H:i:s'); //当前UTC时间
    	
    	if( empty($orderId) ){
    		$orderId = 'CO'.substr(date('Ymd',strtotime('-1 days')),2);
    	}
    	
    	//排除没有跟踪号的快递
    	//$excludeShipCode = array( strtoupper(Logistics::CODE_DHTD_DHL),strtoupper(Logistics::CODE_DHTD_IP),strtoupper(Logistics::CODE_DHTD_IE),strtoupper(Logistics::CODE_FEDEX_IE),strtoupper(Logistics::CODE_KD_TOLL),strtoupper(Logistics::CODE_EMS),strtoupper(Logistics::OODE_CM_ALI_DGYZ) );
    	 
    	$ebayAccounts = EbayAccount::model()->getAbleAccountList();
    	foreach($ebayAccounts as $account){
    		$accountID = $account['id'];	//ebay账号表自增id
    		if( !empty($accountIdParam) && $accountID != $accountIdParam ) continue; //test模式下有效
    		if( $accountID ){
    			//付款时间超过3天的订单
    			$orderInfos = Order::model()->getEbayWaitingConfirmOrders1( $accountID,$gmtime,$orderId,$limit );
    			//var_dump($orderInfos);
    			$tmpOrderIds = array();
    			$tmpOrderInfos = array();
    			foreach($orderInfos as $key => $val){
    				$tmpOrderIds[] = $val['order_id'];
    				$tmpOrderInfos[$val['order_id']] = $val;
    			}
    			//var_dump($tmpOrderInfos);
    			$orderIdArray = $this->splitByn($tmpOrderIds,200);
    			$tmpMarkOrdIds = array();
    			$needMarkOrderIds = array();
    			//var_dump($orderIdArray);
    			foreach( $orderIdArray as $key => $val ){
    				//查出有包裹未上传跟踪号的订单
    				//$unUploadTrackOrders = OrderPackage::model()->getAliUnUploadTrackOrders( MHelper::simplode($val) );
    				//var_dump($unUploadTrackOrders);
    				//查出还没有生成包裹的订单
    				//$unPackageOrders = OrderPackage::model()->getAliUnCreatePackageOrders( MHelper::simplode($val) );
    				//var_dump($unPackageOrders);
    				$needMarkOrderIds = array_merge($needMarkOrderIds,$val);
    				//var_dump($needMarkOrderIds);
    				//查询订单是否确认发货过
    				$tmpRet = EbayOrderMarkedShippedLog::model()->getInfoByOrderIds( MHelper::simplode( $val ),'order_id' );
    				foreach( $tmpRet as $v ){
    					$tmpMarkOrdIds[] = $v['order_id'];
    				}
    				 
    			}
    			//var_dump($needMarkOrderIds);
    			//var_dump($tmpMarkOrdIds);
    			//记录此次需要提前上传跟踪号的订单
    			foreach( $needMarkOrderIds as $key => $val ){
    				if( in_array($val,$tmpMarkOrdIds) ){
    					unset($orderInfos[$key]);
    					continue;
    				}
    				$markOrderData = array(
    						'account_id' => $accountID,
    						'platform_order_id' => $tmpOrderInfos[$val]['platform_order_id'],
    						'order_id'   => $val,
    						'paytime'    => $tmpOrderInfos[$val]['paytime'],
    						'status'     => EbayOrderMarkedShippedLog::STATUS_DEFAULT,
    						'type'	     => EbayOrderMarkedShippedLog::TYPE_FAKE,
    				);
    				$markModel = new EbayOrderMarkedShippedLog();
    				$markModel->saveNewData($markOrderData);
    			}
    
    			$ebayLog = new EbayLog();
    			$logID = $ebayLog->prepareLog($accountID,EbayShipment::EVENT_ADVANCE_SHIPPED);
    			if( $logID ){
    				//1.检查账号是否在提交发货确认
    				$checkRunning = $ebayLog->checkRunning($accountID, EbayShipment::EVENT_ADVANCE_SHIPPED);
    				if( !$checkRunning ){
    					$ebayLog->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
    				}else{
    					//设置日志为正在运行
    					$ebayLog->setRunning($logID);
    					//查询要上传的订单 开始上传
    					$waitingMarkOrders = EbayOrderMarkedShippedLog::model()->getWaitingMarkShipOrderAssit( $accountID );
    					//var_dump($waitingMarkOrders);exit;
    						
    					$isSuccess = true;
    					$errorMessage = '';
    					 
    					foreach( $waitingMarkOrders as $key => $val ){
    
    						$tmpModel = EbayOrderMarkedShippedLog::model()->findByPk($val['id']);
    						//有gift的不上传
    						$retGift = OrderGiftLog::model ()->checkGiftIsOrNot ( $val ['order_id'] );
    						if ($retGift) {
    							$updateData = array (
    									'id' => $val ['id'],
    									'status' => EbayOrderMarkedShippedLog::STATUS_HAS_GIFT,
    									'errormsg' => 'There exists a gift package, need not upload',
    									'upload_time' => date ( 'Y-m-d H:i:s' ),
    									'error_type' => $this->errorTypeMap( 'There exists a gift package, need not upload' )
    							);
    								
    							EbayOrderMarkedShippedLog::model ()->updateData1 ( $tmpModel, $updateData );
    							continue;
    						} else {
    							// 有eub包裹的不上传
    							$eubPackages = OrderPackage::model ()->getEbayEubPackages ( $val ['order_id'], 't.package_id' );
    							if ($eubPackages) {
    								$updateData = array (
    										'id' => $val ['id'],
    										'status' => EbayOrderMarkedShippedLog::STATUS_HAS_GIFT,
    										'errormsg' => 'There exists the EUB package, need not upload',
    										'upload_time' => date ( 'Y-m-d H:i:s' ),
    										'error_type' => $this->errorTypeMap( 'There exists the EUB package, need not upload' )
    								);
    								EbayOrderMarkedShippedLog::model ()->updateData1 ( $tmpModel, $updateData );
    								continue;
    							}
    						}
    						 
    						//2.插入本次log参数日志(用来记录请求的参数)
    						$eventLog = $ebayLog->saveEventLog(EbayShipment::EVENT_ADVANCE_SHIPPED, array(
    								'log_id'        => $logID,
    								'account_id'    => $accountID,
    								'platform_order_id' => $val['platform_order_id'],
    								'order_id'      => $val['order_id'],
    								'track_number'  => '',
    								'carrier_name'  => '',
    								'start_time'    => date('Y-m-d H:i:s'),
    						));
    						 
    						//3.提前确认发货
    						$shippedData = array (
    								'platform_order_id' => $val ['platform_order_id']
    						);
    
    						$ebayShipmentModel = new EbayShipment();
    						$ebayShipmentModel->setAccountID($accountID);//设置账号
    						$flag = $ebayShipmentModel->uploadSellerShipment( $shippedData );//上传
    
    						//4.更新日志信息
    						if( $flag ){
    							//5.上传成功更新记录表
    							$updateData = array(
    									'id' => $val['id'],
    									'status' => EbayOrderMarkedShippedLog::STATUS_SUCCESS,
    									'upload_time' => date('Y-m-d H:i:s'),
    							);
    							EbayOrderMarkedShippedLog::model()->updateData1( $tmpModel,$updateData );
    							$ebayLog->saveEventStatus(EbayShipment::EVENT_ADVANCE_SHIPPED, $eventLog, $ebayLog::STATUS_SUCCESS);
    								
    						}else{
    							$updateData = array(
    									'id' => $val['id'],
    									'status' => EbayOrderMarkedShippedLog::STATUS_FAILURE,
    									'errormsg' => $ebayShipmentModel->getExceptionMessage(),
    									'upload_time' => date('Y-m-d H:i:s'),
    									'error_type' => $this->errorTypeMap( $ebayShipmentModel->getExceptionMessage() ),
    							);
    							EbayOrderMarkedShippedLog::model()->updateData1( $tmpModel,$updateData );
    							$ebayLog->saveEventStatus( $ebayShipmentModel::EVENT_ADVANCE_SHIPPED, $eventLog, $ebayLog::STATUS_FAILURE,$ebayShipmentModel->getExceptionMessage() );
    							$errorMessage .= $ebayShipmentModel->getExceptionMessage();
    								
    						}
    						$isSuccess = $isSuccess && $flag;
    					}
    					if( $isSuccess ){
    						$ebayLog->setSuccess($logID);
    					}else{
    						if( strlen($errorMessage)>1000 ) $errorMessage = substr($errorMessage,0,1000);
    						$ebayLog->setFailure($logID, $errorMessage);
    					}
    				}
    					
    			}
    		}
    	}
    }
    
    /**
     * @desc 上传跟踪号 http://erp_market.com/ebay/ebayshipment/uploadtracknum
     * @desc 多线程跑 http://erp_market.com/ebay/ebayshipment/uploadtracknum/type/1
     * @desc 1.上传ebay非eub包裹，待上传跟踪号的挂号包裹。  2.之前提前标记的，付款时间不超过15天的，如果有真实tn则上传tn。 
     * @author wx
     */
   	public function actionUploadTrackNum() {
   		set_time_limit(3*3600);
   		/* AliexpressOrderMarkShippedLog::model()->updateByPk('104801', array('errormsg'=>'','error_type'=>0));
   		 exit; */
   		$limit = Yii::app()->request->getParam('limit', '');
   		$type = Yii::app()->request->getParam('type', '');
   		$packageId = Yii::app()->request->getParam('package_id', '');
   		$day = Yii::app()->request->getParam('day', '15');
   		$pkCreateDate = date('Y-m-d',strtotime('-'.$day.' days'));
   		$totalCount = Yii::app()->request->getParam('total');
   		/* $hand = Yii::app()->request->getParam('hand');
   		
   		if( !$hand ) exit('调试中...,稍后开启'); */
   		
   		if($type == 1){ //多线程
   			$id = Yii::app()->request->getParam('id',-1);
   			$threadNum = 5; //线程个数
   			if(is_null($totalCount) && $id < 0){
   				//MHelper::writefilelog("ebayuploadtracknumber.log", "first:\r\n");
   				$packageCount = OrderPackage::model()->getEbayWaitingUploadPackagesCount($pkCreateDate,$packageId);
   				$totalCount = $packageCount['total'];
   			}
   			$count = ceil($totalCount/$threadNum);	//每条线程跑的个数
   			//MHelper::writefilelog("ebayuploadtracknumber.log", $id."-".$totalCount."-".$count."-".$id*$count."\r\n");
   			if( $id >= 0 ){
   				$this->executeUploadData($pkCreateDate, $packageId, $count, $id*$count, 1);//0,20, 20,20, 40,20
   			}else{
   				for($i=0;$i<$threadNum;$i++){
   					$url = Yii::app()->request->hostInfo.'/' . $this->route . '/type/1/id/' . $i . "/total/".$totalCount;
   					//echo $url,"<br/>";
   					MHelper::runThreadBySocket ( $url );
   					//sleep(2);
   				}
   			}
   		}else{ //单线程
   			$this->executeUploadData($pkCreateDate, $packageId, '', '', 1);
   		}
   	}
   	
   	
   	/**
   	 * @desc 上传跟踪号 http://erp_market.com/ebay/ebayshipment/uploadtracknumpretrack
   	 * @desc [只上传预匹配的渠道的跟踪号]
   	 * @author wx
   	 */
   	public function actionUploadTrackNumPreTrack() {
   		exit('CLOSED');
   		set_time_limit(3*3600);
   		/* AliexpressOrderMarkShippedLog::model()->updateByPk('104801', array('errormsg'=>'','error_type'=>0));
   		 exit; */
   		$limit = Yii::app()->request->getParam('limit', '');
   		$packageId = Yii::app()->request->getParam('package_id', '');
   		$pkCreateDate = date('Y-m-d',strtotime('-20 days'));
   		 
   		/* $hand = Yii::app()->request->getParam('hand');
   			
   		if( !$hand ) exit('调试中...,稍后开启'); */
   	
   		$this->executeUploadData($pkCreateDate, $packageId, '', '', 2);
   	}
   	
   	/**
   	 * @desc 新上传追踪号
   	 * @link /ebay/ebayshipment/uploadtracknumnew/package_id/xx/account_id/xx/bug/1
   	 */
   	public function actionUploadtracknumnew(){
   		set_time_limit(2*3600);
   		error_reporting(E_ALL);
   		ini_set("display_errors", true);
   		
   		$packageId = Yii::app()->request->getParam('package_id', '');
   		$pkCreateDate = date('Y-m-d',strtotime('-15 days'));
   		$accountIDs = Yii::app()->request->getParam('account_id');
   		if($accountIDs){
   			$accountIDArr = explode(",", $accountIDs);
   			foreach ($accountIDArr as $accountID){
   				$this->executeUploadTrackNumByAccountID($accountID, $pkCreateDate, $packageId, 1);
   			}
   		}else{
   			$ebayAccounts = EbayAccount::model()->getAbleAccountList();
   			$accountGroupIDs = array();
   			foreach($ebayAccounts as $account){
   				$accountID = $account['id'];
   				if(!$accountID) continue;
   				$modID = $accountID%5;
   				$accountGroupIDs[$modID][] = $accountID;
   			}
   			if($accountGroupIDs){
   				foreach ($accountGroupIDs as $accountIDArr){
   					$accountStr = implode(",", $accountIDArr);
   					$url = Yii::app()->request->hostInfo.'/' . $this->route . '/account_id/'.$accountStr;
   					echo $url,"<br/>";
   					MHelper::runThreadBySocket ( $url );
   				}
   			}
   		}
   		
   	}
   	/**
   	 * @desc 执行上传操作，根据账号ID
   	 * @param unknown $accountID
   	 * @param unknown $pkCreateDate
   	 * @param unknown $packageId
   	 * @param unknown $isPreTrack
   	 * @throws Exception
   	 * @return boolean
   	 */
   	public function executeUploadTrackNumByAccountID($accountID, $pkCreateDate, $packageId, $isPreTrack){
   		try{
   			$bug = Yii::app()->request->getParam('bug');
   			$ebayLog = new EbayLog();
   			$logID = $ebayLog->prepareLog($accountID,EbayShipment::EVENT_UPLOAD_TRACK);
   			if(!$logID) {
   				throw new Exception("Create Log ID Fail");
   			}
   			
   			//1.检查账号是否上传跟踪号
   			$checkRunning = $ebayLog->checkRunning($accountID, EbayShipment::EVENT_UPLOAD_TRACK);
   			if( !$checkRunning ){
   				throw new Exception('There Exists An Active Event');
   			}
   				//设置日志为正在运行
   				$ebayLog->setRunning($logID);
   				//==== 业务开始=====
   				$limit = 200;
   				$offset = 0;
   				$isSuccess = true;
   				$errorMessage = '';
   				do{
	   				if( $isPreTrack == 1 ){	//获取要上传的包裹 包含之前提前发货的订单。
	   					$packageInfos = OrderPackage::model()->getEbayWaitingUploadPackages1($pkCreateDate,$packageId,$limit,$offset);
	   				}else{ //获取要上传的预匹配渠道包裹 包含之前提前发货的订单。
	   					$packageInfos = OrderPackage::model()->getEbayWaitingUploadSpecial($pkCreateDate,$packageId,$limit,$offset);
	   				}
	   				$offset += $limit;
	   				if($bug){
	   					echo "<br/>========PackageInfos=======<br/>";
	   					echo "<pre>";
	   					print_r($packageInfos);
	   				}
	   				if($packageInfos){
	   					$isContinue = true;
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
	   					if($bug){
	   						echo "<br/>===========ordArr========<br/>";
	   						print_r($ordArr);
	   					}
	   					//循环查出订单,item相关信息，并采集accountid
	   					$data = array();
	   					$orderArray = array();
	   					foreach($ordArr as $val){
	   						$orderIdStr = "'" . implode ( "','", $val ) . "'";
	   						$orderList = Order::model ()->getInfoListByOrderIds ( $orderIdStr, 'o.order_id,o.account_id,o.platform_order_id,o.paytime,o.currency,d.item_id,d.id as order_detail_id', Platform::CODE_EBAY );
	   						if($bug){
	   							echo "<br/>=======OrderList=======<br/>";
	   							print_r($orderList);
	   						}
	   						foreach ( $orderList as $k => $v ) {
	   							if (! in_array ( $v ['account_id'], array_keys ( $data ) )) {
	   								$data [$v ['account_id']] = array ();
	   							}
	   							$orderArray [$v ['order_id']] [$v ['order_detail_id']] ['account_id'] = $v ['account_id'];
	   							$orderArray [$v ['order_id']] [$v ['order_detail_id']] ['platform_order_id'] = $v ['platform_order_id'];
	   							$orderArray [$v ['order_id']] [$v ['order_detail_id']] ['paytime'] = $v ['paytime'];
	   							$orderArray [$v ['order_id']] [$v ['order_detail_id']] ['item_id'] = $v ['item_id'];
	   						}
	   					
	   					}
	   					if($bug){
	   						echo "<br/>==========orderArray========<br/>";
	   						print_r($orderArray);
	   					}
	   					
	   					 
	   					//按照每个账号来整理数据
	   					foreach($packageInfos as $key => $val){
	   						$orderDetail = $orderArray [$val ['order_id']] [$val ['order_detail_id']];
	   					
	   						$currShipCode = !empty($val['real_ship_type'])?strtolower($val['real_ship_type']):strtolower($val['ship_code']);
	   						 
	   						$carrierCode = LogisticsPlatformCarrier::model()->getCarrierByShipCode( $currShipCode,Platform::CODE_EBAY );
	   						if( $currShipCode == 'cm_zx_syb' && $isPreTrack == 2 ){
	   							$carrierCode = 'Malaysia Post';
	   						}
	   						if(!$carrierCode) {
	   							$errorMessage .= "{$val ['order_id']}:No carrierCode<br/>";
	   							if($bug){
	   								echo "<br/>======={$val ['order_id']}:No carrierCode<br/>===========<br/>";
	   							}
	   							continue;
	   						}
	   						if($bug){
	   							echo "<br/>======={$val ['order_id']} carrierCode:{$carrierCode}<br/>===========<br/>";
	   						}
	   					
	   						$tmp = array (
	   								'order_id' 				=> $val ['order_id'],
	   								'platform_order_id' 	=> $orderDetail ['platform_order_id'],
	   								'order_detail_id' 		=> $val ['order_detail_id'],
	   								'package_id' 			=> $val ['package_id'],
	   								'carrier_name' 			=> $carrierCode,
	   								'real_ship_type' 		=> $currShipCode,
	   								'tracking_number' 		=> $val ['track_num'],
	   								'item_id' 				=> $orderDetail ['item_id'],
	   								'paytime' 				=> $orderDetail ['paytime']
	   						);
	   						$data [$orderDetail ['account_id']] [$val ['package_id']] [] = $tmp;
	   					}
	   					if($bug){
	   						echo "<br/>=========data foreach begin========<br/>";
	   					}
	   					foreach( $data as $key => $val ){ //循环账号
	   						if( !$val ) continue;
	   						$accountID = $key;
	   					
   								foreach( $val as $pkId=>$vv ){ //循环包裹
   									if($bug){
   										echo "<br/>==========pkID:{$pkId}=========<br/>";
   										print_r($vv);
   									}
   									$isResult = true;
   									foreach( $vv as $vvItem ){ //循环订单明细
   										//检测是否之前有上传过
   										$checkAdvanceShiped = EbayOrderMarkedShippedLog::model()->getInfoRowByOrderId( $vvItem['order_id'],'*' );
   										if($checkAdvanceShiped['order_id'] && (!in_array($checkAdvanceShiped['error_type'], array(0,5)) || !in_array($checkAdvanceShiped['update_error_type'], array(0,5))) ){ //不满足条件
   											$isResult = false;
   											$errorMessage .= $checkAdvanceShiped['order_id'].":[ERROR_TYPE:{$checkAdvanceShiped['error_type']}],[update_error_type:{$checkAdvanceShiped['update_error_type']}]<br/>";
   											if($bug){
   												echo "<br/>==Continue==:{$checkAdvanceShiped['order_id']}:[ERROR_TYPE:{$checkAdvanceShiped['error_type']}],[update_error_type:{$checkAdvanceShiped['update_error_type']}]<br/>";
   											}
   											continue;
   										}
   										//添加详细日志
   										$eventLog = $ebayLog->saveEventLog(EbayShipment::EVENT_UPLOAD_TRACK, array(
   												'log_id'        => $logID,
   												'account_id'    => $accountID,
   												'platform_order_id'  => $vvItem['platform_order_id'],
   												'order_id'      => $vvItem['order_id'],
   												'package_id'	=> $pkId,
   												'track_number'     => $vvItem['tracking_number'],
   												'carrier_name'  => $vvItem['carrier_name'],
   												'start_time'    => date('Y-m-d H:i:s'),
   												'item_id'		=> $vvItem['item_id']
   										));
   					
   										// 获取订单详情
   										$currOrderDetail = UebModel::model('OrderDetail')->getOrderDetailByOrderDetailId ( $vvItem ['order_detail_id'], 'item_id,transaction_id' );
   										if (! $currOrderDetail ['item_id']) {
   											$errorMessage .= $vvItem['order_id'].":NO item_id<br/>";
   											if($bug){
   												echo "<br/>==Continue==:{$vvItem['order_id']}:NO item_id<br/>";
   											}
   											continue;
   										}
   										// 获取订单信息
   										$orderInfo = UebModel::model('Order')->getInfoByOrderId ( $vvItem ['order_id'], 'account_id' );
   										if (empty ( $orderInfo ))
   										{
   											$errorMessage .= $vvItem['order_id'].":NO orderinfo <br/>";
   											if($bug){
   												echo "<br/>",$vvItem['order_id'].":NO orderinfo <br/>";
   											}
   											continue;
   										}
   					
   										// 查出是否存在另外一个包裹用的eub方式
   										$check = OrderPackage::model ()->getEbayEubPackagesByOrderId ( $vvItem ['order_id'], $pkId );
   										// 有gift
   										$retGift = UebModel::model('OrderGiftLog')->checkGiftIsOrNot ( $vvItem ['order_id'] );
   										if ($check && $retGift) { // 存在另外一个包裹用的eub方式,并且订单有礼品记录，则不上传跟踪号，防止上传的挂号tn覆盖掉eub的tn。
   											$errorMessage .= $vvItem['order_id'].": have Gift <br/>";
   											if($bug){
   												echo "<br>",$vvItem['order_id'].": have Gift <br/>";
   											}
   											continue;
   										}
   										
   										if ($vvItem ['real_ship_type'] == 'other') {
   											$vvItem ['tracking_number'] = $vvItem ['order_detail_id'];
   										}
   										 
   										//设置账号信息
   										$ebayShipmentModel = new EbayShipment();
   										$ebayShipmentModel->setAccountID($accountID);//设置账号
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
   													'status' => EbayOrderMarkedShippedLog::STATUS_DEFAULT,
   													'type'	=> EbayOrderMarkedShippedLog::TYPE_TRUE, //上传真实单号
   											);
   											$markModel = new EbayOrderMarkedShippedLog();
   											$tmpMarkId = $markModel->saveNewData($markOrderData);
   										}else{
   											$tmpMarkId = $checkAdvanceShiped['id'];
   										}
   										 
   										//初始化单个订单上传是否成功的标记
   										$flag = false;
   										 
   										//开始上传
   										$tmpModel = EbayOrderMarkedShippedLog::model()->findByPk($tmpMarkId);
   					
   										if( $checkAdvanceShiped['order_id'] && $checkAdvanceShiped['status'] == EbayOrderMarkedShippedLog::STATUS_SUCCESS ){ //之前有提前发货
   											if($bug){
   												echo "<br>","Update Tracer number<br/>";
   											}
   											//准备上传数据-modify
   											$shippedData = array (
   													'item_id' => $currOrderDetail ['item_id'],
   													'transaction_id' => $currOrderDetail ['transaction_id'],
   													'track_number' => $vvItem ['tracking_number'],
   													'shipped_carrier' => $vvItem ['carrier_name']
   											);
   											if($bug){
   												echo "<br>", "=========shippedData========<br/>";
   												print_r($shippedData);
   											}
   											$flag = $ebayShipmentModel->uploadSellerShipment( $shippedData );//上传
   											$errorMessageSub = $ebayShipmentModel->getExceptionMessage();
   											if($bug){
   												echo "<br/>", "result:".var_dump($flag),"Message:",$errorMessageSub,"<br>";
   											}
   											if($flag){ //更新成功
   												$updateData = array(
   														'id' => $tmpMarkId,
   														'update_status' => EbayOrderMarkedShippedLog::UPDATE_STATUS_SUCCESS,
   														'update_time' => date('Y-m-d H:i:s'),
   														'package_id'	=> $pkId,
   														'update_errormsg' => $errorMessageSub,
   												);
   											}else{
   												$updateData = array(
   														'id' => $tmpMarkId,
   														'update_status' => EbayOrderMarkedShippedLog::UPDATE_STATUS_FAILURE,
   														'update_time' => date('Y-m-d H:i:s'),
   														'package_id'	=> $pkId,
   														'update_errormsg' => $errorMessageSub,
   														'update_error_type' => $this->errorTypeMap( trim($ebayShipmentModel->getExceptionMessage()) ),
   												);
   											}
   											EbayOrderMarkedShippedLog::model()->updateData1( $tmpModel,$updateData );
   										}else{ //1.之前未提前声明发货
   											if( !$checkAdvanceShiped['order_id'] || (in_array($checkAdvanceShiped['error_type'], array(0,5)) || in_array($checkAdvanceShiped['update_error_type'], array(0,5))) ){ //之前未提前声明发货或者失败，则直接上传真实跟踪号
   												//准备上传数据-first upload
   												if($bug){
   													echo "<br>","first upload Tracer number<br/>";
   												}
   												$shippedData = array (
   														'item_id' => $currOrderDetail ['item_id'],
   														'transaction_id' => $currOrderDetail ['transaction_id'],
   														'track_number' => $vvItem ['tracking_number'],
   														'shipped_carrier' => $vvItem ['carrier_name']
   												);
   												if($bug){
   													echo "<br>", "=========shippedData========<br/>";
   													print_r($shippedData);
   												}
   												$flag = $ebayShipmentModel->uploadSellerShipment( $shippedData );//上传
   												$errorMessageSub = $ebayShipmentModel->getExceptionMessage();
   												if($bug){
   													echo "<br/>", "result:".var_dump($flag),"Message:",$errorMessageSub,"<br>";
   												}
   												if($flag){ //上传成功
   													$updateData = array(
   															'id' => $tmpMarkId,
   															'status' => EbayOrderMarkedShippedLog::STATUS_SUCCESS,
   															'upload_time' => date('Y-m-d H:i:s'),
   													);
   												}else{ //上传失败
   													//不满足更新条件
   													$updateData = array(
   															'id' => $tmpMarkId,
   															'status' => EbayOrderMarkedShippedLog::STATUS_FAILURE,
   															'upload_time' => date('Y-m-d H:i:s'),
   															'errormsg' => $errorMessageSub,
   															'error_type' => $this->errorTypeMap(trim($ebayShipmentModel->getExceptionMessage())),
   													);
   												}
   												EbayOrderMarkedShippedLog::model()->updateData1( $tmpModel,$updateData );
   											}else{
   												if($bug){
   													echo "<br/> == first upload not match!!! <br/>";
   												}
   											}
   					
   										}
   										 
   										if( $flag ){
   											//5.上传成功更新记录表
   											$ebayLog->saveEventStatus(EbayShipment::EVENT_UPLOAD_TRACK, $eventLog, $ebayLog::STATUS_SUCCESS);
   										}else{
   											$ebayLog->saveEventStatus(EbayShipment::EVENT_UPLOAD_TRACK, $eventLog, $ebayLog::STATUS_FAILURE,$errorMessageSub);
   											$errorMessage .= $errorMessageSub;
   										}
   										$isResult = $isResult && $flag;
   									}
   									if( $isResult ){
   										UebModel::model('OrderPackage')->updateAll(array('is_confirm_shiped'=>1),' is_confirm_shiped=0 and package_id in("'.$pkId.'")');
   										if( $isPreTrack == 2 ){
   											UebModel::model('OrderPackageQhPreTrack')->updateByPk( $pkId, array('confirm_shiped_status'=>1,'confirm_shiped_time'=>date('Y-m-d H:i:s')) );
   										}
   									}
   									$isSuccess = $isSuccess && $isResult;
   								}
   						}
   						if($bug){
   							echo "<br/>=========data foreach end========<br/>";
   						}
	   				}else{
	   					$isContinue = false;
	   				}
   				}while ($isContinue);
   				//==== 业务结束=====
   				if(! $isSuccess ){
   					//if( strlen($errorMessage)>1000 ) $errorMessage = substr($errorMessage,0,1000);
   					throw new Exception($errorMessage);
   				}
   				$ebayLog->setSuccess($logID);
   				return true;
   		}catch (Exception $e){
   			if(isset($logID) && $logID){
   				$ebayLog->setFailure($logID, $e->getMessage());
   			}
   			return false;
   		}
   	}
   	
   	
   	
   	public function executeUploadData($pkCreateDate,$packageId,$limit,$offset,$isPreTrack){
   		//$excludeShipCode = array( strtolower(Logistics::CODE_CM_HK),strtolower(Logistics::CODE_CM_DHL),strtolower(Logistics::CODE_CM_SGXB),strtolower(Logistics::CODE_CM_FU),strtolower(Logistics::CODE_CM_GYHL),strtolower(Logistics::CODE_CM_PUTIAN),strtolower(Logistics::CODE_CM_PUTIAN_E),strtolower(Logistics::CODE_CM_FU),strtolower(Logistics::CODE_CM_DHL),strtolower(Logistics::CODE_DHL_XB_DE),strtolower(Logistics::CODE_CM_HK),strtolower(Logistics::CODE_CM_SGXB),strtolower(Logistics::CODE_CM_PTXB),strtolower(Logistics::CODE_CM_PTXB_E),strtolower(Logistics::CODE_CM_ZXYZ),strtolower(Logistics::CODE_CM_DEYZ),strtolower(Logistics::OODE_SWYH_ALI_PING) ); //排除的发货方式（有些物流方式只上传假单号）
   		//$addPreSuffFixShipCode = array( strtolower(Logistics::CODE_CM_DGYZ) ); //跟踪号需要添加前后缀的物流方式
   		//$needTrackNumShipCode = array( strtolower(Logistics::CODE_GHXB_SF),strtolower(Logistics::CODE_GHXB_SF_E) );  //需要上传track_num2的物流方式
   		
   		if( $isPreTrack == 1 ){	//获取要上传的包裹 包含之前提前发货的订单。
   			$packageInfos = OrderPackage::model()->getEbayWaitingUploadPackages1($pkCreateDate,$packageId,$limit,$offset);
   		}else{ //获取要上传的预匹配渠道包裹 包含之前提前发货的订单。
   			$packageInfos = OrderPackage::model()->getEbayWaitingUploadSpecial($pkCreateDate,$packageId,$limit,$offset);
   		}
   		
   		//print_r($packageInfos);
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
   			$orderIdStr = "'" . implode ( "','", $val ) . "'";
   			$orderList = Order::model ()->getInfoListByOrderIds ( $orderIdStr, 'o.order_id,o.account_id,o.platform_order_id,o.paytime,o.currency,d.item_id,d.id as order_detail_id', Platform::CODE_EBAY );
   			foreach ( $orderList as $k => $v ) {
   				if (! in_array ( $v ['account_id'], array_keys ( $data ) )) {
   					$data [$v ['account_id']] = array ();
   				}
   				$orderArray [$v ['order_id']] [$v ['order_detail_id']] ['account_id'] = $v ['account_id'];
   				$orderArray [$v ['order_id']] [$v ['order_detail_id']] ['platform_order_id'] = $v ['platform_order_id'];
   				$orderArray [$v ['order_id']] [$v ['order_detail_id']] ['paytime'] = $v ['paytime'];
   				$orderArray [$v ['order_id']] [$v ['order_detail_id']] ['item_id'] = $v ['item_id'];
   			}
   			
   		}
   		//print_r($orderArray);
   		
   		//按照每个账号来整理数据
   		foreach($packageInfos as $key => $val){
   			$orderDetail = $orderArray [$val ['order_id']] [$val ['order_detail_id']];
   			
   			$currShipCode = !empty($val['real_ship_type'])?strtolower($val['real_ship_type']):strtolower($val['ship_code']);
   		
   			$carrierCode = LogisticsPlatformCarrier::model()->getCarrierByShipCode( $currShipCode,Platform::CODE_EBAY );
   			if( $currShipCode == 'cm_zx_syb' && $isPreTrack == 2 ){
   				$carrierCode = 'Malaysia Post';
   			}
   			if(!$carrierCode) continue;
   			
   			$tmp = array (
   					'order_id' 				=> $val ['order_id'],
   					'platform_order_id' 	=> $orderDetail ['platform_order_id'],
   					'order_detail_id' 		=> $val ['order_detail_id'],
   					'package_id' 			=> $val ['package_id'],
   					'carrier_name' 			=> $carrierCode,
   					'real_ship_type' 		=> $currShipCode,
   					'tracking_number' 		=> $val ['track_num'],
   					'item_id' 				=> $orderDetail ['item_id'],
   					'paytime' 				=> $orderDetail ['paytime']
   			);
   			$data [$orderDetail ['account_id']] [$val ['package_id']] [] = $tmp;
   			
   		}
   		 
   		//print_r($data);
   		foreach( $data as $key => $val ){ //循环账号
   			if( !$val ) continue;
   			$accountID = $key;
   			$ebayLog = new EbayLog();
   			$logID = $ebayLog->prepareLog($accountID,EbayShipment::EVENT_UPLOAD_TRACK);
   			if( $logID ){
   				//1.检查账号是否上传跟踪号
   				$checkRunning = $ebayLog->checkRunning($accountID, EbayShipment::EVENT_UPLOAD_TRACK);
   				if( !$checkRunning ){
   					$ebayLog->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
   				}else{
   					//设置日志为正在运行
   					$ebayLog->setRunning($logID);
   					$isSuccess = true;
   					$errorMessage = '';
   					foreach( $val as $pkId=>$vv ){ //循环包裹
   						$isResult = true;
   						foreach( $vv as $vvItem ){ //循环订单明细
   							//检测是否之前有上传过
   							$checkAdvanceShiped = EbayOrderMarkedShippedLog::model()->getInfoRowByOrderId( $vvItem['order_id'],'*' );
   							if($checkAdvanceShiped['order_id'] && (!in_array($checkAdvanceShiped['error_type'], array(0,5)) || !in_array($checkAdvanceShiped['update_error_type'], array(0,5))) ){ //不满足条件
   								$isResult = false;
   								$errorMessage .= $checkAdvanceShiped['order_id'].":[ERROR_TYPE:{$checkAdvanceShiped['error_type']}],[update_error_type:{$checkAdvanceShiped['update_error_type']}]<br/>";
   								continue;
   							}
   							//添加详细日志
   							$eventLog = $ebayLog->saveEventLog(EbayShipment::EVENT_UPLOAD_TRACK, array(
   									'log_id'        => $logID,
   									'account_id'    => $accountID,
   									'platform_order_id'  => $vvItem['platform_order_id'],
   									'order_id'      => $vvItem['order_id'],
   									'package_id'	=> $pkId,
   									'track_number'     => $vvItem['tracking_number'],
   									'carrier_name'  => $vvItem['carrier_name'],
   									'start_time'    => date('Y-m-d H:i:s'),
   									'item_id'		=> $vvItem['item_id']
   							));
   							
   							// 获取订单详情
   							$currOrderDetail = UebModel::model('OrderDetail')->getOrderDetailByOrderDetailId ( $vvItem ['order_detail_id'], 'item_id,transaction_id' );
   							if (! $currOrderDetail ['item_id']) {
   								$errorMessage .= $vvItem['order_id'].":NO item_id<br/>";
   								continue;
   							}
   							// 获取订单信息
   							$orderInfo = UebModel::model('Order')->getInfoByOrderId ( $vvItem ['order_id'], 'account_id' );
   							if (empty ( $orderInfo ))
   							{
   								$errorMessage .= $vvItem['order_id'].":NO orderinfo <br/>";
   								continue;
   							}
   							
   							// 查出是否存在另外一个包裹用的eub方式
   							$check = OrderPackage::model ()->getEbayEubPackagesByOrderId ( $vvItem ['order_id'], $pkId );
   							// 有gift
   							$retGift = UebModel::model('OrderGiftLog')->checkGiftIsOrNot ( $vvItem ['order_id'] );
   							if ($check && $retGift) { // 存在另外一个包裹用的eub方式,并且订单有礼品记录，则不上传跟踪号，防止上传的挂号tn覆盖掉eub的tn。
   								$errorMessage .= $vvItem['order_id'].": have Gift <br/>";
   								continue;
   							}
   								
   							if ($vvItem ['real_ship_type'] == 'other') {
   								$vvItem ['tracking_number'] = $vvItem ['order_detail_id'];
   							}
   		
   							//设置账号信息
   							$ebayShipmentModel = new EbayShipment();
   							$ebayShipmentModel->setAccountID($accountID);//设置账号
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
   										'status' => EbayOrderMarkedShippedLog::STATUS_DEFAULT,
   										'type'	=> EbayOrderMarkedShippedLog::TYPE_TRUE, //上传真实单号
   								);
   								$markModel = new EbayOrderMarkedShippedLog();
   								$tmpMarkId = $markModel->saveNewData($markOrderData);
   							}else{
   								$tmpMarkId = $checkAdvanceShiped['id'];
   							}
   		
   							//初始化单个订单上传是否成功的标记
   							$flag = false;
   		
   							//开始上传
   							$tmpModel = EbayOrderMarkedShippedLog::model()->findByPk($tmpMarkId);
   							
   							if( $checkAdvanceShiped['order_id'] && $checkAdvanceShiped['status'] == EbayOrderMarkedShippedLog::STATUS_SUCCESS ){ //之前有提前发货
   								//准备上传数据-modify
   								$shippedData = array (
   										'item_id' => $currOrderDetail ['item_id'],
   										'transaction_id' => $currOrderDetail ['transaction_id'],
   										'track_number' => $vvItem ['tracking_number'],
   										'shipped_carrier' => $vvItem ['carrier_name']
   								);
   								
   								$flag = $ebayShipmentModel->uploadSellerShipment( $shippedData );//上传
   								$errorMessageSub = $ebayShipmentModel->getExceptionMessage();
   									
   								if($flag){ //更新成功
   									$updateData = array(
   											'id' => $tmpMarkId,
   											'update_status' => EbayOrderMarkedShippedLog::UPDATE_STATUS_SUCCESS,
   											'update_time' => date('Y-m-d H:i:s'),
   											'package_id'	=> $pkId,
   											'update_errormsg' => $errorMessageSub,
   									);
   								}else{
   									$updateData = array(
   											'id' => $tmpMarkId,
   											'update_status' => EbayOrderMarkedShippedLog::UPDATE_STATUS_FAILURE,
   											'update_time' => date('Y-m-d H:i:s'),
   											'package_id'	=> $pkId,
   											'update_errormsg' => $errorMessageSub,
   											'update_error_type' => $this->errorTypeMap( trim($ebayShipmentModel->getExceptionMessage()) ),
   									);
   								}
   								EbayOrderMarkedShippedLog::model()->updateData1( $tmpModel,$updateData );
   							}else{ //1.之前未提前声明发货
   								if( !$checkAdvanceShiped['order_id'] || (in_array($checkAdvanceShiped['error_type'], array(0,5)) || in_array($checkAdvanceShiped['update_error_type'], array(0,5))) ){ //之前未提前声明发货或者失败，则直接上传真实跟踪号
   									//准备上传数据-first upload
   									$shippedData = array (
   										'item_id' => $currOrderDetail ['item_id'],
   										'transaction_id' => $currOrderDetail ['transaction_id'],
   										'track_number' => $vvItem ['tracking_number'],
   										'shipped_carrier' => $vvItem ['carrier_name']
   									);
   								
   									$flag = $ebayShipmentModel->uploadSellerShipment( $shippedData );//上传
   									$errorMessageSub = $ebayShipmentModel->getExceptionMessage();
   									if($flag){ //上传成功
   										$updateData = array(
   												'id' => $tmpMarkId,
   												'status' => EbayOrderMarkedShippedLog::STATUS_SUCCESS,
   												'upload_time' => date('Y-m-d H:i:s'),
   										);
   									}else{ //上传失败
   										//不满足更新条件
   										$updateData = array(
   												'id' => $tmpMarkId,
   												'status' => EbayOrderMarkedShippedLog::STATUS_FAILURE,
   												'upload_time' => date('Y-m-d H:i:s'),
   												'errormsg' => $errorMessageSub,
   												'error_type' => $this->errorTypeMap(trim($ebayShipmentModel->getExceptionMessage())),
   										);
   									}
   									EbayOrderMarkedShippedLog::model()->updateData1( $tmpModel,$updateData );
   									
   								}
   									
   							}
   		
   							if( $flag ){
   								//5.上传成功更新记录表
   								$ebayLog->saveEventStatus(EbayShipment::EVENT_UPLOAD_TRACK, $eventLog, $ebayLog::STATUS_SUCCESS);
   							}else{
   								$ebayLog->saveEventStatus(EbayShipment::EVENT_UPLOAD_TRACK, $eventLog, $ebayLog::STATUS_FAILURE,$errorMessageSub);
   								$errorMessage .= $errorMessageSub;
   							}
   							$isResult = $isResult && $flag;
   						}
   						if( $isResult ){
   							UebModel::model('OrderPackage')->updateAll(array('is_confirm_shiped'=>1),' is_confirm_shiped=0 and package_id in("'.$pkId.'")');
   							if( $isPreTrack == 2 ){
   								UebModel::model('OrderPackageQhPreTrack')->updateByPk( $pkId, array('confirm_shiped_status'=>1,'confirm_shiped_time'=>date('Y-m-d H:i:s')) );
   							}
   						}
   						$isSuccess = $isSuccess && $isResult;
   					}
   					if( $isSuccess ){
   						$ebayLog->setSuccess($logID);
   					}else{
   						if( strlen($errorMessage)>1000 ) $errorMessage = substr($errorMessage,0,1000);
   						$ebayLog->setFailure($logID, $errorMessage);
   					}
   				}
   		
   			}
   		}
   		
   	}
   	
   	
   	/**
   	 * @desc 上传跟踪号 http://erp_market.com/ebay/ebayshipment/confirmshippedthread
   	 * @author wx
   	 */
   	public function actionConfirmShippedThread() {
   		set_time_limit(3*3600);
   		$orderId = Yii::app()->request->getParam('order_id', '');
   		$day = Yii::app()->request->getParam('day', '');
   		$bug = Yii::app()->request->getParam('bug', ''); //是否开启bug调试模式 默认不开启 ,1开启
   		
   		if( !empty($day) ){
   			$days = array($day);
   		}else{
   			$days = array(3,2,1);
   		}
   		
   		$accountIDs = Yii::app()->request->getParam('account_id');
   		if($accountIDs){
   			$accountIDArr = explode(",", $accountIDs);
   			foreach ($accountIDArr as $accountID){ //按帐号
   				foreach($days as $currDay){ //按日期
   					$errorMsg = '';
   					try{
   						$this->excuteConfirmShipped($accountID, $orderId, $currDay);
   					}catch(Exception $ex){
   						$errorMsg = $ex->getMessage();
   					}
   					if($bug) MHelper::writefilelog("ebayuploadtracknumber.log", "AccountID：【".$accountID."】,DAYS：【".$currDay."】, ErrorMsg：".$errorMsg."\r\n");
   				}
   			}
   		}else{
   			$ebayAccounts = EbayAccount::model()->getAbleAccountList();
   			$accountGroupIDs = array();
   			foreach($ebayAccounts as $account){
   				$accountID = $account['id'];
   				if(!$accountID) continue;
   				$modID = $accountID%5;
   				$accountGroupIDs[$modID][] = $accountID;
   			}
   			//var_dump($accountGroupIDs);exit;
   			if($accountGroupIDs){
   				foreach ($accountGroupIDs as $accountIDArr){
   					$accountStr = implode(",", $accountIDArr);
   					$url = Yii::app()->request->hostInfo.'/' . $this->route . '/account_id/'.$accountStr.'/bug/'.$bug.'/day/'.$day;
   					echo $url,"<br/>";
   					MHelper::runThreadBySocket ( $url );
   				}
   			}
   		}
   	}
   	
   	/**
   	 * @desc 针对Ebay平台下单后，未发货的订单声明发货
   	 * @author wx
   	 */
   	public function excuteConfirmShipped( $accountID, $orderId, $currDay ) {
   		 
   		$orderInfos = Order::model()->getEbayWaitingConfirmOrdersNew1( $accountID, $orderId, $currDay );
   		//print_r($orderInfos);exit;
   		$tmpOrderIds = array();
   		$tmpOrderInfos = array();
   		foreach($orderInfos as $key => $val){
   			if( $val['complete_status'] == 4 && $val['order_status'] == 'CustomCode' ){
   				continue;
   			}
   			$tmpOrderIds[] = $val['order_id'];
   			$tmpOrderInfos[$val['order_id']] = $val;
   		}
   		if( empty($tmpOrderIds) ){
   			return false;
   		}
   		//var_dump($tmpOrderInfos);
   		$orderIdArray = $this->splitByn($tmpOrderIds,200);
   		$tmpMarkOrdIds = array();
   		$needMarkOrderIds = array();
   		//var_dump($orderIdArray);
   		
   		foreach( $orderIdArray as $key => $val ){
   	
   			$needMarkOrderIds = array_merge($needMarkOrderIds,$val);
   			//var_dump($needMarkOrderIds);
   			//查询订单是否确认发货过
   			$tmpRet = EbayOrderMarkedShippedLog::model()->getInfoByOrderIds( MHelper::simplode( $val ),'order_id' );
   			foreach( $tmpRet as $v ){
   				$tmpMarkOrdIds[] = $v['order_id'];
   			}
   		}
   		//print_r($needMarkOrderIds);
   		//var_dump($tmpMarkOrdIds);
   		 
	   		//记录此次需要提前上传跟踪号的订单
	   		foreach( $needMarkOrderIds as $key => $val ){
	   			if( in_array($val,$tmpMarkOrdIds) ){
	   				unset($orderInfos[$key]);
	   				continue;
	   			}
	   			try{
	   				$markOrderData = array(
	   						'account_id' => $tmpOrderInfos[$val]['account_id'],
	   						'platform_order_id' => $tmpOrderInfos[$val]['platform_order_id'],
	   						'order_id'   => $val,
	   						'paytime'    => $tmpOrderInfos[$val]['paytime'],
	   						'status'     => EbayOrderMarkedShippedLog::STATUS_DEFAULT,
	   						'type'	     => EbayOrderMarkedShippedLog::TYPE_FAKE,
	   				);
	   				$markModel = new EbayOrderMarkedShippedLog();
	   				$markModel->saveNewData($markOrderData);
	   			}catch (Exception $e) {
	   				echo 'Message: ' . $e->getMessage();
	    		}
	   		}
   		
   			$ebayLog = new EbayLog();
   			$logID = $ebayLog->prepareLog($accountID,EbayShipment::EVENT_ADVANCE_SHIPPED);
   			if( $logID ){
   				//1.检查账号是否在提交发货确认
   				$checkRunning = $ebayLog->checkRunning($accountID, EbayShipment::EVENT_ADVANCE_SHIPPED);
   				if( !$checkRunning ){
   					$ebayLog->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
   				}else{
   					//设置日志为正在运行
   					$ebayLog->setRunning($logID);
   					//查询要上传的订单 开始上传
   					$waitingMarkOrders = EbayOrderMarkedShippedLog::model()->getWaitingMarkShipOrder1( $accountID );
   					//var_dump($waitingMarkOrders);exit;
   						
   					$isSuccess = true;
   					$errorMessage = '';
   						
   					foreach( $waitingMarkOrders as $key => $val ){
   						try{
   							$tmpModel = EbayOrderMarkedShippedLog::model()->findByPk($val['id']);
   							//有gift的不上传
   							$retGift = OrderGiftLog::model ()->checkGiftIsOrNot ( $val ['order_id'] );
   							if ($retGift) {
   								$updateData = array (
   										'id' => $val ['id'],
   										'status' => EbayOrderMarkedShippedLog::STATUS_HAS_GIFT,
   										'errormsg' => 'There exists a gift package, need not upload',
   										'upload_time' => date ( 'Y-m-d H:i:s' ),
   										'error_type' => $this->errorTypeMap( 'There exists a gift package, need not upload' )
   								);
   									
   								EbayOrderMarkedShippedLog::model ()->updateData1 ( $tmpModel, $updateData );
   								continue;
   							} else {
   								// 有eub包裹的不上传
   								$eubPackages = OrderPackage::model ()->getEbayEubPackages ( $val ['order_id'], 't.package_id' );
   								if ($eubPackages) {
   									$updateData = array (
   											'id' => $val ['id'],
   											'status' => EbayOrderMarkedShippedLog::STATUS_HAS_GIFT,
   											'errormsg' => 'There exists the EUB package, need not upload',
   											'upload_time' => date ( 'Y-m-d H:i:s' ),
   											'error_type' => $this->errorTypeMap( 'There exists the EUB package, need not upload' )
   									);
   									EbayOrderMarkedShippedLog::model ()->updateData1 ( $tmpModel, $updateData );
   									continue;
   								}
   							}
   							
   							//2.插入本次log参数日志(用来记录请求的参数)
   							$eventLog = $ebayLog->saveEventLog(EbayShipment::EVENT_ADVANCE_SHIPPED, array(
   									'log_id'        => $logID,
   									'account_id'    => $accountID,
   									'platform_order_id' => $val['platform_order_id'],
   									'order_id'      => $val['order_id'],
   									'track_number'  => '',
   									'carrier_name'  => '',
   									'start_time'    => date('Y-m-d H:i:s'),
   							));
   							
   							//3.提前确认发货
   							$shippedData = array (
   									'platform_order_id' => $val ['platform_order_id']
   							);
   							
   							$ebayShipmentModel = new EbayShipment();
   							$ebayShipmentModel->setAccountID($accountID);//设置账号
   							$flag = $ebayShipmentModel->uploadSellerShipment( $shippedData );//上传
   							
   							//4.更新日志信息
   							if( $flag ){
   								//5.上传成功更新记录表
   								$updateData = array(
   										'id' => $val['id'],
   										'status' => EbayOrderMarkedShippedLog::STATUS_SUCCESS,
   										'upload_time' => date('Y-m-d H:i:s'),
   								);
   								EbayOrderMarkedShippedLog::model()->updateData1( $tmpModel,$updateData );
   								$ebayLog->saveEventStatus(EbayShipment::EVENT_ADVANCE_SHIPPED, $eventLog, $ebayLog::STATUS_SUCCESS);
   									
   							}else{
   								$updateData = array(
   										'id' => $val['id'],
   										'status' => EbayOrderMarkedShippedLog::STATUS_FAILURE,
   										'errormsg' => $ebayShipmentModel->getExceptionMessage(),
   										'upload_time' => date('Y-m-d H:i:s'),
   										'error_type' => $this->errorTypeMap( $ebayShipmentModel->getExceptionMessage() ),
   								);
   								EbayOrderMarkedShippedLog::model()->updateData1( $tmpModel,$updateData );
   								$ebayLog->saveEventStatus( $ebayShipmentModel::EVENT_ADVANCE_SHIPPED, $eventLog, $ebayLog::STATUS_FAILURE,$ebayShipmentModel->getExceptionMessage() );
   								$errorMessage .= $ebayShipmentModel->getExceptionMessage();
   									
   							}
   							$isSuccess = $isSuccess && $flag;
   						}catch(Exception $e){
   							$isSuccess = false;
   							$errorMessage .= 'Message: '.$e->getMessage();
   						}
   					}
   					if( $isSuccess ){
   						$ebayLog->setSuccess($logID);
   					}else{
   						if( strlen($errorMessage)>1000 ) $errorMessage = substr($errorMessage,0,1000);
   						$ebayLog->setFailure($logID, $errorMessage);
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
     * @desc ali返回的错误映射
     */
    public function errorTypeMap( $errorMsg = '' ){
    	$errorType = 0;
    	if( stripos($errorMsg, 'WAIT_BUYER_ACCEPT_GOODS') !== false ){
    		$errorType = 1; //订单状态为已发货，等待买家收货
    	}elseif( $errorMsg == 'error in validate:oldLogisticsNo cannot be modified' ){
    		$errorType = 2; //订单已经发货超过5天，不能修改发货信息
    	}elseif( stripos($errorMsg, 'status IN_CANCEL') !== false ){
    		$errorType = 3; //订单取消状态，不能确认发货
    	}elseif( stripos($errorMsg, 'There exists the EUB package, need not upload') !== false ){
    		$errorType = 4; //订单已生成eub包裹，不需要上传
    	}elseif( stripos($errorMsg, 'Request need user authorized') !== false ){
    		$errorType = 5; //请求需要用户验证
    	}elseif( stripos($errorMsg, 'There exists a gift package, need not upload') !== false ){
    		$errorType = 6; //gift订单不需要上传...
    	}
    	
    	return empty($errorType)?999:$errorType;
    }
    
}