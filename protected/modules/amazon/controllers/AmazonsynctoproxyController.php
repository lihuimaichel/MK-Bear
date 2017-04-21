<?php
/**
 * @desc Amazon上传跟踪号 同步到中转服务器
 * @author wx
 *
 */
class AmazonsynctoproxyController extends UebController {
    /**
     * @desc 访问过滤配置
     * @see CController::accessRules()
     */
    public function accessRules() {
        return array(
			array(
				'allow',
				'users' => array('*'),
				'actions' => array('uploadtracknum','confirmshipping','syncfeed','syncfeedstatus')
			),
		);
    }
    
    /**
     * @desc 确认发货
     * http://erp_market.com/amazon/amazonsynctoproxy/confirmshipping
     */
    public function actionConfirmShipping(){
    	set_time_limit(5*3600);
    	$limit = Yii::app()->request->getParam('limit', '');
    	$orderId = Yii::app()->request->getParam('order_id', '');
    	$orderInfos = Order::model()->getAmazonWaitingConfirmPackages($orderId,$limit);

    	//var_dump($orderInfos);exit;
    	 
    	//按照每个账号来整理数据
    	$data = array();
    	foreach($orderInfos as $key => $val){
    		if( ($val['item_id'] == '') || ($val['item_id'] == null) ){
    			continue;
    		}
    		$shipDate = date('Y-m-d H:i:s',strtotime($val['paytime'])+3600*22);
    		if( strtotime($shipDate) > strtotime(gmdate('Y-m-d H:i:s')) ){
    			continue;
    		}
    		//if( !in_array($val['account_id'],array_keys($data)) ) {$data[$val['account_id']] = array();}
    		//array_push($data[$val['account_id']],$tmp);
    		$saveData = array(
    				'order_id' 			=> $val['order_id'],
    				'amazon_order_id' 	=> $val['platform_order_id'],
    				'item_id' 			=> $val['item_id'],
    				'package_id' 		=> '',
    				'paytime' 			=> $val['paytime'],
    				'carrier_code' 		=> '',
    				'carrier_name' 		=> '',
    				'tracking_number' 	=> '',
    				'ship_date' 		=> $shipDate,
    				'qty' 				=> $val['quantity'],
    				'account_id' 		=> $val['account_id'],
    				'feed_type' 		=> AmazonUploadFeed::FEED_TYPE_CONFIRM,
    		);
    		$isExist = AmazonUploadFeed::model()->getInfoByOrderId($val['order_id'],$val['item_id'],AmazonUploadFeed::FEED_TYPE_CONFIRM,'order_id');
    		if( !$isExist['order_id'] ){
    			AmazonUploadFeed::model()->saveNewData($saveData);
    		}
    	}
    	//var_dump($data);exit;
    	
    	 
    }
    
    /**
     * @desc 上传跟踪号
     * http://erp_market.com/amazon/amazonsynctoproxy/uploadtracknum/limit/10
     */
    public function actionUploadTrackNum(){
    	set_time_limit(2*3600);
    	$accountID = Yii::app()->request->getParam('account_id');
    	$limit = Yii::app()->request->getParam('limit', '');
    	$packageId = Yii::app()->request->getParam('package_id', '');
    	//获取要上传的包裹
    	$packageInfos = OrderPackage::model()->getAmazonWaitingUploadPackages($packageId,$limit);
    	//var_dump($packageInfos);exit;
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
    		$orderList = Order::model()->getInfoListByOrderIds($orderIdStr,'o.order_id,o.account_id,o.platform_order_id,o.paytime,o.currency,d.item_id,d.id as order_detail_id',Platform::CODE_AMAZON);
    		//var_dump($orderList);exit;
    		foreach( $orderList as $k => $v ){
    			if( !in_array($v['account_id'],array_keys($data)) ) {$data[$v['account_id']] = array();}
    			$orderArray[$v['order_id']][$v['order_detail_id']]['account_id']		= $v['account_id'];
    			$orderArray[$v['order_id']][$v['order_detail_id']]['platform_order_id']	= $v['platform_order_id'];
    			$orderArray[$v['order_id']][$v['order_detail_id']]['item_id']			= $v['item_id'];
    			$orderArray[$v['order_id']][$v['order_detail_id']]['paytime']			= $v['paytime'];
    			$orderArray[$v['order_id']][$v['order_detail_id']]['currency']			= $v['currency'];
    		}
    	}
    	//var_dump($orderArray);exit;
    	
    	//按照每个账号来整理数据
    	foreach($packageInfos as $key => $val){
    		$order_detail = $orderArray[$val['order_id']][$val['order_detail_id']];
    		$shipDate = date('Y-m-d H:i:s',strtotime($order_detail['paytime'])+3600*22);
    		if( strtotime($shipDate) > strtotime(gmdate('Y-m-d H:i:s')) ){ //发货时间大于当前utc时间
    			continue;
    		}
    		if(($order_detail['item_id'] == '') || ($order_detail['item_id'] == null) || (time()-strtotime($order_detail['paytime'])>=1000*3600)){
    			continue;
    		}
    		$carrierCode = LogisticsPlatformCarrier::model()->getCarrierByShipCode( !empty($val['real_ship_type'])?$val['real_ship_type']:$val['ship_code'],Platform::CODE_AMAZON );
    		if(!$carrierCode) continue;
    		
    		$saveData = array(
    				'order_id' 			=> $val['order_id'],
    				'amazon_order_id' 	=> $order_detail['platform_order_id'],
    				'item_id' 			=> $order_detail['item_id'],
    				'package_id' 		=> $val['package_id'],
    				'paytime' 			=> $order_detail['paytime'],
    				'carrier_code' 		=> $carrierCode,
    				'carrier_name' 		=> $carrierCode,
    				'tracking_number' 	=> $val['track_num'],
    				'ship_date' 		=> $shipDate,
    				'qty' 				=> $val['quantity'],
    				'account_id' 		=> $order_detail['account_id'],
    				'feed_type' 		=> AmazonUploadFeed::FEED_TYPE_UPLOAD_TN,
    		);
    		$isExist = AmazonUploadFeed::model()->getInfoByOrderId($val['order_id'],$val['item_id'],AmazonUploadFeed::FEED_TYPE_UPLOAD_TN,'order_id');
    		if( !$isExist['order_id'] ){
    			AmazonUploadFeed::model()->saveNewData($saveData);
    		}
    	}
    	//var_dump($data);exit;
    }
    
    /**
     * @desc 同步feed到中转服务器
     * http://erp_market.com/amazon/amazonsynctoproxy/syncfeed/limit/10
     */
    public function actionSyncFeed(){
    	set_time_limit(10000);
    	//获取待同步的feed
    	$limit = Yii::app()->request->getParam('limit', '');
    	$feeds = AmazonUploadFeed::model()->getWaitSyncFeed($limit);
    	//var_dump($feeds);exit;
    	$url = 'http://47.88.19.103/amazon/amazonreceivefeed/receivefeed';
    	$response = Yii::app()->curl->post($url,json_encode($feeds));
    	//print_r($response);exit;
    	$responseData = json_decode($response,true);
    	//print_r($responseData);exit;
    	if( $responseData['ack'] == 'success'  ){
    		foreach( $responseData['retdata'] as $key => $val ){
    			$updateData = array(
    				'is_sync' => AmazonUploadFeed::IS_SYNC_SUCCESS,
    				'sync_time' => date('Y-m-d H:i:s'),
    			);
    			AmazonUploadFeed::model()->updateByPk($val, $updateData);
    		}
    	}else{
    		echo 'sync data failure';
    	}
    	
    	exit;
    }
    
    /**
     * @desc 同步feed上传amazon的状态。
     * http://erp_market.com/amazon/amazonsynctoproxy/syncfeedstatus
     */
    public function actionSyncFeedStatus(){
    	set_time_limit(10000);
    	$accountId = Yii::app()->request->getParam('account_id', '');
    	$limit = Yii::app()->request->getParam('limit', '');
    	$url = 'http://47.88.19.103/amazon/amazonreceivefeed/getfeedsstatus';
    	$requestData = AmazonUploadFeed::model()->getWaitSyncStatusFeed($accountId,$limit);
    	//var_dump($requestData);exit;
    	$response = Yii::app()->curl->post($url,json_encode( $requestData ));
    	//print_r($response);exit;
    	$responseData = json_decode($response,true);
    	//print_r($responseData);exit;
    	if( $responseData['ack'] == 'success'  ){
    		foreach( $responseData['retdata'] as $key => $val ){
    			$updateData = array(
    					'status' => $val['status'],
    					'sync_status_time' => date('Y-m-d H:i:s'),
    			);
    			AmazonUploadFeed::model()->updateAll($updateData,'order_id="'.$val['order_id'].'" and feed_type='.$val['feed_type'].' and item_id="'.$val['item_id'].'"');
    			if($val['status'] == AmazonUploadFeed::STATUS_SUCCESS){
    				$packageId = AmazonUploadFeed::model()->getInfoByOrderId( $val['order_id'],$val['item_id'],AmazonUploadFeed::FEED_TYPE_UPLOAD_TN,'package_id' );
    				if($packageId) UebModel::model('OrderPackage')->updateAll(array('is_confirm_shiped'=>1),' is_confirm_shiped=0 and package_id in("'.$packageId['package_id'].'")');
    			}
    		}
    	}else{
    		echo 'sync data failure';
    	}
    	
    	exit;
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
    
}