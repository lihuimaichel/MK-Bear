<?php
/**
 * @desc Amazon上传跟踪号
 * @author wx
 *
 */
class AmazonuploadtrackController extends UebController {
    /**
     * @desc 访问过滤配置
     * @see CController::accessRules()
     */
    public function accessRules() {
        return array(
			array(
				'allow',
				'users' => array('*'),
				'actions' => array('uploadtracknum','confirmshipping')
			),
		);
    }
    
    /**
     * @desc 确认发货
     * http://erp_market.com/amazon/amazonuploadtrack/confirmshipping
     */
    public function actionConfirmShipping(){
    	set_time_limit(5*3600);
    	$limit = Yii::app()->request->getParam('limit', '');
    	$orderId = Yii::app()->request->getParam('order_id', '');
    	$bug = Yii::app()->request->getParam('bug');
    	$gmtime = gmdate('Y-m-d H:i:s'); //当前UTC时间
    	//2017-02-03 修改增加排除账号 lihy; 设置excludeAccount为空2017-03-08 21：00
    	$excludeAccount = array();//80:r-in
    	$orderInfos = Order::model()->getAmazonWaitingConfirmPackages($orderId,$gmtime,$limit, $excludeAccount);
    	//var_dump($orderInfos);exit;
    	if($bug){
    		echo "<br/>==========orderInfos=========<br/>";
    		var_dump($orderInfos);
    	}
    	//按照每个账号来整理数据
    	$data = array();
    	$shipDateTime = '';
    	if($bug){
    		echo "<br/>========StartTime:=========<br/>";
    		echo date("Y-m-d H:i:s");
    	}
    	foreach($orderInfos as $key => $val){
    		if( ($val['item_id'] == '') || ($val['item_id'] == null) ){
    			continue;
    		}
    		$isOldType = false;
    		$existUploadLog = AmazonUploadTnLog::model()->getInfoByPlatformOrderId($val['platform_order_id']);
    		if($bug){
    			echo "<br/>============existUploadLog=============<br/>";
    			var_dump($existUploadLog);
    		}
    		if( $existUploadLog && (empty($existUploadLog['ship_date']) || $existUploadLog['ship_date'] == '0000-00-00 00:00:00') ){ //ship_date为空则先走以前方式
    			$shipDate = date('Y-m-d H:i:s',strtotime($val['paytime'])+3600*30);
    			if( strtotime($shipDate) > strtotime(gmdate('Y-m-d H:i:s')) ){
    				continue;
    			}
    			$isOldType = true;
    		}else{
    			$amazonUploadTnlog = AmazonUploadTnLog::model()->getInfosByPlatformOrderId($val['platform_order_id']);
    			if($bug){
    				echo "<br/>============amazonUploadTnlog=============<br/>";
    				var_dump($amazonUploadTnlog);
    			}
    			if( $amazonUploadTnlog && $amazonUploadTnlog['ship_date'] != '0000-00-00 00:00:00' ){
    				$shipDate = $amazonUploadTnlog['ship_date'];
    			}else{
    				$minshipDate = strtotime($val['paytime'])+24*3600;
    				$maxshipDate = strtotime($val['paytime'])+38*3600;
    				
    				$shipDateTimeCurr = rand($minshipDate, $maxshipDate);
    				if( $shipDateTimeCurr > strtotime(gmdate('Y-m-d H:i:s')) ){
    					if($maxshipDate - strtotime(gmdate('Y-m-d H:i:s')) <= 3*3600){
    						$shipDateTimeCurr = strtotime(gmdate('Y-m-d H:i:s'))-1*3600;
    					}else{
    						continue;
    					}
    				}
    				//$shipDateTime = $shipDateTime ? $shipDateTime : $shipDateTimeCurr;
    				$shipDate = date("Y-m-d H:i:s", $shipDateTimeCurr);
    			}
    		}
    		
    		$tmp = array(
    				'order_id'			=> $val['order_id'],
    				'amazon_order_id'	=> $val['platform_order_id'],
    				'item_id'		 	=> $val['item_id'],
    				'carrier_code' 		=> '',
    				'carrier_name'		=> '',
    				'shipping_method' 	=> 'First Class',
    				'tracking_number' 	=> '',
    				'ship_date'			=> $shipDate,
    				'qty'				=> $val['quantity'],
    				'is_old_type'		=> $isOldType
    		);
    		if( !in_array($val['account_id'],array_keys($data)) ) {$data[$val['account_id']] = array();}
    		array_push($data[$val['account_id']],$tmp);
    	}
    	//var_dump($data);exit;
    	foreach( $data as $key => $val ){
    		$accountID = $key;
    		$amazonLog = new AmazonLog();
    		$logID = $amazonLog->prepareLog($accountID,AmazonUploadTrack::EVENT_NAME);
    		if( $logID ){
    			//1.检查账号是否上传跟踪号
    			$checkRunning = $amazonLog->checkRunning($accountID, AmazonUploadTrack::EVENT_NAME);
    			if( !$checkRunning ){
    				$amazonLog->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
    			}else{
    				//2.准备拉取日志信息
    				//插入本次log参数日志(用来记录请求的参数)
    				$eventLog = $amazonLog->saveEventLog(AmazonUploadTrack::EVENT_NAME, array(
    						'log_id'        => $logID,
    						'account_id'    => $accountID,
    						'start_time'    => date('Y-m-d H:i:s'),
    						'end_time'      => date('Y-m-d H:i:s'),
    				));
    				//设置日志为正在运行
    				$amazonLog->setRunning($logID);
    				//3.拉取订单
    				$amazonUploadTrackModel = new AmazonUploadTrack();
    				$amazonUploadTrackModel->setAccountID($accountID);//设置账号
    				//$amazonOrderModel->setLogID($logID);//设置日志编号
    				$flag = $amazonUploadTrackModel->uploadTrackNum( $val );//上传
    				//4.更新日志信息
    				if( $flag ){
    					$amazonLog->setSuccess($logID);
    					$amazonLog->saveEventStatus(AmazonUploadTrack::EVENT_NAME, $eventLog, AmazonLog::STATUS_SUCCESS);
    				}else{
    					$errorMessage = $amazonUploadTrackModel->getExceptionMessage();
    					if( strlen($errorMessage)>200 ) $errorMessage = substr($errorMessage,0,150);
    					$amazonLog->setFailure($logID,$errorMessage);
    					$amazonLog->saveEventStatus(AmazonUploadTrack::EVENT_NAME, $eventLog, AmazonLog::STATUS_FAILURE);
    				}
    			}
    		}
    	}
    	
    	if($bug){
    		echo "<br/>========EndTime:=========<br/>";
    		echo date("Y-m-d H:i:s");
    	}
    	 
    }
    
    /**
     * @desc 上传跟踪号
     * http://erp_market.com/amazon/amazonuploadtrack/uploadtracknum
     */
    public function actionUploadTrackNum(){
    	set_time_limit(7200);
    	error_reporting(E_ALL);
    	$bug = Yii::app()->request->getParam('bug');
    	$accountID = Yii::app()->request->getParam('account_id');
    	$limit = Yii::app()->request->getParam('limit', '');
    	$packageId = Yii::app()->request->getParam('package_id', '');
    	$kdArray = array(strtolower(Logistics::CODE_FEDEX_IE),strtolower(Logistics::CODE_DHTD_IP),strtolower(Logistics::CODE_DHTD_IE),strtolower(Logistics::CODE_DHTD_UPS),strtolower(Logistics::CODE_DHTD_DHL),strtolower(Logistics::CODE_EMS));
    	$fakeCarrierArr = array(strtolower(Logistics::CODE_CM_DHL),strtolower(Logistics::CODE_DHL_XB_DE)); //只确认发货，不上传真实跟踪号的渠道。
    	//@todo 改为按照账号可能速度快点
    	//2017-02-03 修改增加排除账号 lihy; 设置excludeAccount为空2017-03-08 21：00
    	$excludeAccount = array();//80:r-in 
    	//获取要上传的包裹
    	$packageInfos = OrderPackage::model()->getAmazonWaitingUploadPackages($packageId,$limit, $accountID, $excludeAccount);
    	//print_r($packageInfos);exit;
    	if($bug){
    		echo "<br/>========StartTime:=========<br/>";
    		echo date("Y-m-d H:i:s");
    	}
    	if($bug){
    		echo "<br/>=========fakeCarrierArr======<br/>";
    		var_dump($fakeCarrierArr);
    	}
    	if($bug){
    		echo "<br/>=========kdArray======<br/>";
    		var_dump($kdArray);
    	}
    	if($bug){
    		echo "<br/>=========packageInfos======<br/>";
    		var_dump($packageInfos);
    	}
    	$tmpOrderIds = array();
    	foreach( $packageInfos as $key => $val ){
    		if( !in_array($val['order_id'],$tmpOrderIds) ){
	    		$tmpOrderIds[] = $val['order_id'];
    		}
    	}
    	//列表字符串有限制，每次查询限制在500以内
    	$ordArr = $this->splitByn($tmpOrderIds,500);
    	if($bug){
    		echo "<br/>=========ordArr======<br/>";
    		var_dump($ordArr);
    	}
    	unset($tmpOrderIds);
    	
    	//循环查出订单,item相关信息，并采集accountid
    	$data = array();
    	$orderArray = array();
    	$eubGiftInfos = array(); //保存eub礼品订单
    	foreach($ordArr as $val){
    		$orderIdStr = "'".implode("','",$val)."'";
    		$orderList = Order::model()->getInfoListByOrderIds($orderIdStr,'o.order_id,o.account_id,o.platform_order_id,o.paytime,o.currency,d.item_id,d.id as order_detail_id,d.sku,d.quantity',Platform::CODE_AMAZON);
    		if($bug){
    			echo "<br/>=========orderList======<br/>";
    			var_dump($orderList);
    		}
    		//检测是否包含eub礼品
    		$eubGiftOrders = OrderGiftLogAmazon::model()->checkGiftIsOrNotBatch($orderIdStr);
    		$eubGiftInfos = array_merge($eubGiftInfos,$eubGiftOrders);
    		if($bug){
    			echo "<br/>=========eubGiftOrders======<br/>";
    			var_dump($eubGiftOrders);
    		}
    		foreach( $orderList as $k => $v ){
    			if( !in_array($v['account_id'],array_keys($data)) ) {$data[$v['account_id']] = array();}
    			$orderArray[$v['order_id']][$v['order_detail_id']]['account_id']		= $v['account_id'];
    			$orderArray[$v['order_id']][$v['order_detail_id']]['platform_order_id']	= $v['platform_order_id'];
    			$orderArray[$v['order_id']][$v['order_detail_id']]['item_id']			= $v['item_id'];
    			$orderArray[$v['order_id']][$v['order_detail_id']]['paytime']			= $v['paytime'];
    			$orderArray[$v['order_id']][$v['order_detail_id']]['currency']			= $v['currency'];
    			$orderArray[$v['order_id']][$v['order_detail_id']]['quantity']			= $v['quantity'];
    		}
    	}
    	if($bug){
    		echo "<br/>=========orderArray======<br/>";
    		var_dump($orderArray);
    	}
    	
    	if($bug){
    		echo "<br/>=========eubGiftInfos======<br/>";
    		var_dump($eubGiftInfos);
    	}
    	//print_r($eubGiftInfos);
    	//print_r($orderArray);
    	//exit;
    	//按照每个账号来整理数据
    	$shipDateArray = array();
    	foreach($packageInfos as $key => $val){
    		if( $shipDateArray[$val['order_id']] == $val['order_id'] ){
    			continue;
    		}
    		$order_detail = $orderArray[$val['order_id']][$val['order_detail_id']];
    		if($bug){
    			echo "<br/>===========order_detail===========<br/>";
    			var_dump($order_detail);
    		}
    		//var_dump($order_detail);
    		$isOldType = false;
    		$existUploadLog = AmazonUploadTnLog::model()->getInfoByPlatformOrderId($order_detail['platform_order_id']);
    		if($bug){
    			echo "<br/>=========existUploadLog========<br/>";
    			var_dump($existUploadLog);
    		}
    		if( $existUploadLog && (empty($existUploadLog['ship_date']) || $existUploadLog['ship_date'] == '0000-00-00 00:00:00') ){
	    		$shipDate = date('Y-m-d H:i:s',strtotime($order_detail['paytime'])+3600*30);
	    		if( strtotime($shipDate) > strtotime(gmdate('Y-m-d H:i:s')) ){ //发货时间大于当前utc时间
	    			continue;
	    		}
	    		$isOldType = true;
    		}else{
    			$amazonUploadTnlog = AmazonUploadTnLog::model()->getInfosByPlatformOrderId($order_detail['platform_order_id']);
    			if($bug){
    				echo "<br/>========amazonUploadTnlog======<br/>";
    				var_dump($amazonUploadTnlog);
    			}
    			if( $amazonUploadTnlog ){
    				$shipDate = $amazonUploadTnlog['ship_date'];
    			}else{
    				$minshipDate = strtotime($order_detail['paytime'])+24*3600;
    				$maxshipDate = strtotime($order_detail['paytime'])+38*3600;
    				
    				if( !$shipDateArray[$val['order_id']] ){
    					$shipDateTimeCurr = rand($minshipDate, $maxshipDate);
    					if( $shipDateTimeCurr > strtotime(gmdate('Y-m-d H:i:s')) ){
    						if($maxshipDate - strtotime(gmdate('Y-m-d H:i:s')) <= 3*3600){
    							$shipDateTimeCurr = strtotime(gmdate('Y-m-d H:i:s'))-1*3600;
    						}else{
    							$shipDateArray[$val['order_id']] = $val['order_id'];
    							continue;
    						}
    					}
    					$shipDateArray[$val['order_id']] =  $shipDateTimeCurr;
    					//$shipDateArray[$val['order_id']] = $shipDateArray[$val['order_id']] ? $shipDateArray[$val['order_id']] : $shipDateTimeCurr;
    				}
    				$shipDate = date("Y-m-d H:i:s", $shipDateArray[$val['order_id']]);
    			}
    		}
    		if( ($order_detail['item_id'] == '' || $order_detail['item_id'] == null || (time()-strtotime($order_detail['paytime'])>=800*3600)) && !in_array($val['order_id'], $eubGiftInfos) ){
    			continue;
    		}
    		$trackNumber = $val['track_num'];
    		$packageId = $val['package_id'];
    		//存在eub礼品，则用礼品包裹的carrier和tracknumber
    		if( in_array($val['order_id'], $eubGiftInfos) ){
    			//获取礼品包裹
    			$retGiftPk = OrderPackage::model()->getAmazonEubGiftPackage($val['order_id']);
    			if($bug){
    				echo "<br/>=============retGiftPk===========<br/>";
    				var_dump($retGiftPk);
    			}
    			if( !$retGiftPk || $retGiftPk['is_confirm_shiped'] == 1 ) continue;
    			$carrierCode = LogisticsPlatformCarrier::model()->getCarrierByShipCode( $retGiftPk['ship_code'],Platform::CODE_AMAZON );
    			if($bug){
    				echo "<br/>============carrierCode===========<br/>";
    				var_dump($carrierCode);
    			}
    			if(!$carrierCode) continue;
    			
    			if($bug){
    				echo "<br/>============orderArray[val['order_id']]===========<br/>";
    				var_dump($orderArray[$val['order_id']]);
    			}
    			foreach( $orderArray[$val['order_id']] as $kk => $vv ){
    				if(empty($vv['item_id'])) continue;
    				$tmp = array(
    						'order_id'			=> $val['order_id'],
    						'amazon_order_id'	=> $vv['platform_order_id'],
    						'order_detail_id' 	=> $kk,
    						'item_id'		 	=> $vv['item_id'],
    						'package_id' 		=> $retGiftPk['package_id'],
    						'paytime'			=> $vv['paytime'],
    						'currency'		    => $vv['currency'],
    						'carrier_code' 		=> $carrierCode,
    						'carrier_name'		=> $carrierCode,
    						'shipping_method' 	=> 'First Class',
    						'tracking_number' 	=> $retGiftPk['track_num'],
    						'ship_date'			=> $shipDate,
    						'qty'				=> $vv['quantity'],
    						'is_old_type'		=> $isOldType,
    						'ship_code'			=> $retGiftPk['ship_code'],
    				);
    				//array_push($data[$order_detail['account_id']],$tmp);
    				if( in_array(strtolower($tmp['ship_code']),$fakeCarrierArr) || (in_array(strtolower($tmp['ship_code']), $kdArray) && stripos($retGiftPk['track_num'],'PK') === 0) || (in_array(strtolower($tmp['ship_code']), $kdArray) && empty($retGiftPk['track_num'])) ){ //不需要上传真实单号的渠道 和快递单号是"PK"开头的订单，只确认发货，不上传真实单号
    					$tmp['carrier_code'] = '';
    					$tmp['carrier_name'] = '';
    					$tmp['tracking_number'] = '';
    				}
    				$data[$order_detail['account_id']][$val['order_id'].'_'.$vv['item_id']] = $tmp;
    			}
    			
    		}else{
    			$carrierCode = LogisticsPlatformCarrier::model()->getCarrierByShipCode( !empty($val['real_ship_type'])?$val['real_ship_type']:$val['ship_code'],Platform::CODE_AMAZON );
    			if($bug){
    				echo "<br/>============carrierCode===========<br/>";
    				var_dump($carrierCode);
    			}
    			if(!$carrierCode) continue;
    			$tmp = array(
    			 		'order_id'			=> $val['order_id'],
    					'amazon_order_id'	=> $order_detail['platform_order_id'],
    					'order_detail_id' 	=> $val['order_detail_id'],
    					'item_id'		 	=> $order_detail['item_id'],
    					'package_id' 		=> $packageId,
    					'paytime'			=> $order_detail['paytime'],
    					'currency'		    => $order_detail['currency'],
    					'carrier_code' 		=> $carrierCode,
    					'carrier_name'		=> $carrierCode,
    					'shipping_method' 	=> 'First Class',
    					'tracking_number' 	=> $trackNumber,
    					'ship_date'			=> $shipDate,
    					'qty'				=> $val['quantity'],
    					'is_old_type'		=> $isOldType,
    					'ship_code'			=> !empty($val['real_ship_type'])?$val['real_ship_type']:$val['ship_code'],
    			);
    			
    			if( in_array(strtolower($tmp['ship_code']),$fakeCarrierArr) || (in_array(strtolower($tmp['ship_code']), $kdArray) && stripos($trackNumber,'PK') === 0) || (in_array(strtolower($tmp['ship_code']), $kdArray) && empty($trackNumber)) ){ //不需要上传真实单号的渠道 和快递单号是"PK"开头的订单，只确认发货，不上传真实单号
    				$tmp['carrier_code'] = '';
    				$tmp['carrier_name'] = '';
    				$tmp['tracking_number'] = '';
    			}
    			array_push($data[$order_detail['account_id']],$tmp);
    		}
    		
    		
    		
    		/* $tmp = array(
    				'order_id'			=> $val['order_id'],
    				'amazon_order_id'	=> $order_detail['platform_order_id'],
    				'order_detail_id' 	=> $val['order_detail_id'],
    				'item_id'		 	=> $order_detail['item_id'],
    				'package_id' 		=> $packageId,
    				'paytime'			=> $order_detail['paytime'],
    				'currency'		    => $order_detail['currency'],
    				'carrier_code' 		=> $carrierCode,
    				'carrier_name'		=> $carrierCode,
    				'shipping_method' 	=> 'First Class',
    				'tracking_number' 	=> $trackNumber,
    				'ship_date'			=> $shipDate,
    				'qty'				=> $val['quantity'],
    		);
    		array_push($data[$order_detail['account_id']],$tmp); */
    	}
    	//print_r($data);
    	if($bug){
    		echo "<br/>===========data=========<br/>";
    		print_r($data);
    	}
    	foreach( $data as $key => $val ){
    		$accountID = $key;
    		$amazonLog = new AmazonLog();
    		$logID = $amazonLog->prepareLog($accountID,AmazonUploadTrack::EVENT_NAME);
    		if( $logID ){
    			//1.检查账号是否上传跟踪号
    			$checkRunning = $amazonLog->checkRunning($accountID, AmazonUploadTrack::EVENT_NAME);
    			if( !$checkRunning ){
    				$amazonLog->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
    			}else{
    				//2.准备拉取日志信息
    				//插入本次log参数日志(用来记录请求的参数)
    				$eventLog = $amazonLog->saveEventLog(AmazonUploadTrack::EVENT_NAME, array(
    						'log_id'        => $logID,
    						'account_id'    => $accountID,
    						'start_time'    => date('Y-m-d H:i:s'),
    						'end_time'      => date('Y-m-d H:i:s'),
    				));
    				//设置日志为正在运行
    				$amazonLog->setRunning($logID);
    				//3.拉取订单
    				$amazonUploadTrackModel = new AmazonUploadTrack();
    				$amazonUploadTrackModel->setAccountID($accountID);//设置账号
    				//$amazonOrderModel->setLogID($logID);//设置日志编号
    				$flag = $amazonUploadTrackModel->uploadTrackNum( $val );//上传
    				if($bug){
    					echo "<br/>==============upload data =============<br/>";
    					var_dump($val);
    					echo "<br/>===============flag================<br/>";
    					var_dump($flag);
    					echo "<br/>============upload msg===============<br/>";
    					var_dump($errorMessage = $amazonUploadTrackModel->getExceptionMessage());
    					
    				}
    				//4.更新日志信息
    				if( $flag ){
    					$amazonLog->setSuccess($logID);
    					$amazonLog->saveEventStatus(AmazonUploadTrack::EVENT_NAME, $eventLog, AmazonLog::STATUS_SUCCESS);
    				}else{
    					$errorMessage = $amazonUploadTrackModel->getExceptionMessage();
    					if( strlen($errorMessage)>200 ) $errorMessage = substr($errorMessage,0,150);
    					$amazonLog->setFailure($logID, $errorMessage);
    					$amazonLog->saveEventStatus(AmazonUploadTrack::EVENT_NAME, $eventLog, AmazonLog::STATUS_FAILURE);
    				}
    			}
    		}
    	}
    	if($bug){
    		echo "<br/>========EndTime:=========<br/>";
    		echo date("Y-m-d H:i:s");
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
     * @desc 按照指定大小分隔数组
     * @param unknown $ordArr
     * @param unknown $n
     * @return multitype:unknown multitype:
     */
    public function splitArrayByn($dataArray,$n){
    	$newArr = array();
    	$count = ceil(count($dataArray)/$n);
    	for($i=0;$i<=$count-1;$i++){
    		if($i == ($count-1)){
    			$newArr[] = $ordArr;
    		}else{
    			$newArr[] = array_splice($ordArr,0,$n);
    		}
    	}
    	return $newArr;
    }
    
}