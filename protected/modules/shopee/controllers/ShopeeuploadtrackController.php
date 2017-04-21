<?php
/**
 * @desc Shopee���ٺ��ϴ�
 * @author wx
 * @since 2015-12-28
 */
class ShopeeuploadtrackController extends UebController{
    
    /**
     * @desc ���ʹ�������
     * @see CController::accessRules()
     */
    public function accessRules() {
        return array(
			array(
				'allow',
				'users' => array('*'),
				'actions' => array('uploadtracknum')
			),
		);
    }

    /**
     * @desc �ϴ����ٺ�
     * @desc 1.�ϴ������ɰ��������и��ٺŵĶ�����    
     * @link /shopee/shopeeuploadtrack/uploadtracknum/limit/10/bug/1
     *    /shopee/shopeeuploadtrack/uploadtracknum/limit/10/package_id/PK151202036308/bug/1
     */
    public function actionUploadtracknum() {
    	set_time_limit(3600);
        ini_set("display_errors", true);
    	error_reporting(E_ALL & ~E_STRICT);

        $limit        = Yii::app()->request->getParam('limit', '');
        $packageId    = Yii::app()->request->getParam('package_id', '');
        $accountID    = Yii::app()->request->getParam('account_id');
        $bug          = Yii::app()->request->getParam('bug',0);
        $norun        = Yii::app()->request->getParam('norun');
        $pkCreateDate = date('Y-m-d',strtotime('-3 days')); //����wmsʱ��

    	//��ȡҪ�ϴ��İ��� 
    	$packageInfos = OrderPackage::model()->getShopeeWaitingUploadPackages($pkCreateDate,$packageId,$limit,$accountID);
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

    	//�б��ַ��������ƣ�ÿ�β�ѯ������500����
    	$ordArr = $this->splitByn($tmpOrderIds,500);
    	if($bug){
    		$this->printbugmsg($ordArr, "ordArr");
    	}
    	unset($tmpOrderIds);

    	//ѭ���������,item�����Ϣ�����ɼ�accountid
    	$data = array();
    	$orderArray = array();
    	foreach($ordArr as $val){
    		$orderIdStr = "'".implode("','",$val)."'";
    		$orderList = Order::model()->getInfoListByOrderIds($orderIdStr,'o.order_id,o.account_id,o.platform_order_id,o.paytime',Platform::CODE_SHOPEE);
    		foreach( $orderList as $k => $v ){
    			if( !in_array($v['account_id'],array_keys($data)) ) {$data[$v['account_id']] = array();}
    			$orderArray[$v['order_id']]['account_id']			= $v['account_id'];
    			$orderArray[$v['order_id']]['platform_order_id']	= $v['platform_order_id'];
    			$orderArray[$v['order_id']]['paytime']				= $v['paytime'];
    		}
    	}

    	if($bug){
    		$this->printbugmsg($orderArray, "orderArray");
    	}
    	
    	//����ÿ���˺�����������
    	foreach($packageInfos as $key => $val){
    		$orderInfo = $orderArray[$val['order_id']];
    		$shipCode = !empty($val['real_ship_type'])?$val['real_ship_type']:$val['ship_code'];
    		$carrierCode = LogisticsPlatformCarrier::model()->getCarrierByShipCode($shipCode ,Platform::CODE_SHOPEE );
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
    	
    	if($bug){
    		$this->printbugmsg($data, "foreach data");
    	}

    	foreach( $data as $key => $val ){ //ѭ���˺�
    		if( !$val ) continue;
    		$accountInfo = ShopeeAccount::model()->getAccountInfoById( $key );          
    		$accountID = $accountInfo['id'];
    		$shopeeLog = new ShopeeLog();
    		$logID = $shopeeLog->prepareLog($accountID,ShopeeShipment::EVENT_UPLOAD_TRACK);
    		if( !$logID ) {
    			echo "create log id failure<br/>";
                continue;
            }
    		
            //1.����˺��Ƿ��ϴ����ٺ�
            $checkRunning = $shopeeLog->checkRunning($accountID, ShopeeShipment::EVENT_UPLOAD_TRACK);
            if( !$checkRunning ){
                $shopeeLog->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
                if($bug){
                    echo  Yii::t('systems', 'There Exists An Active Event'), "<br>";
                }
                continue;
            }

            //2.������־Ϊ��������
            $shopeeLog->setRunning($logID);

            $shippedDatas = $orderPakageIds = $tmpMarkIdArr = array();
            $isSuccess = true;//����¼��Ƿ��ϴ��ɹ�
            foreach( $val as $pkId=>$vv ){ //ѭ������,һ�������������
                //�����TWվ������ֱ�ӱ�ʶ�������ٺ����ϴ�
                if ( strtolower($accountInfo['site']) == 'tw') {
                    foreach( $vv as $vvItem ){ //ѭ������
                        $orderPakageIds[$pkId][$vvItem['platform_order_id']] = 1;
                    }
                    UebModel::model('OrderPackage')->updateAll(array('is_confirm_shiped'=>1),"is_confirm_shiped=0 and package_id='{$pkId}' ");
                    continue;
                }
                if($bug){
                    $this->printbugmsg($pkId, "PKID");
                }
                $isUploadOk = true;//��ǰ����ϴ����ٺ��Ƿ�ɹ�
                foreach( $vv as $vvItem ){ //ѭ������
                    //����Ƿ�֮ǰ���ϴ�����ʧ�ܴ�������3��
                    $checkAdvanceShiped = ShopeeOrderMarkShippedLog::model()->getInfoRowByOrderId( $vvItem['order_id'],'*' );

                    $orderPakageIds[$pkId][$vvItem['platform_order_id']] = !empty($checkAdvanceShiped) && $checkAdvanceShiped['status'] == ShopeeOrderMarkShippedLog::STATUS_SUCCESS ? 1 : 0;

                    if( $checkAdvanceShiped['status'] == ShopeeOrderMarkShippedLog::STATUS_SUCCESS 
                         || ($checkAdvanceShiped['status'] == ShopeeOrderMarkShippedLog::STATUS_FAILURE && $checkAdvanceShiped['error_count'] > ShopeeOrderMarkShippedLog::UPLOAD_ERROR_MAX_COUNT ) ){ //����������
                        if($bug){
                            $this->printbugmsg($checkAdvanceShiped, "checkAdvanceShiped");
                        }
                        continue;
                    }
                    
                    //�����ϸ��־
                    $eventLog = $shopeeLog->saveEventLog(ShopeeShipment::EVENT_UPLOAD_TRACK, array(
                        'log_id'            => $logID,
                        'account_id'        => $accountID,
                        'platform_order_id' => $vvItem['platform_order_id'],
                        'order_id'          => $vvItem['order_id'],
                        'package_id'        => $pkId,
                        'track_number'      => $vvItem['tracking_number'],
                        'carrier_name'      => $vvItem['carrier_name'],
                        'start_time'        => date('Y-m-d H:i:s'),
                    ));
                    
                    //���涩���ϴ���¼
                    if( empty($checkAdvanceShiped['order_id']) ){
                        $markOrderData = array(
                            'account_id'        => $accountID,
                            'platform_order_id' => $vvItem['platform_order_id'],
                            'order_id'          => $vvItem['order_id'],
                            'package_id'        => $pkId,
                            'track_num'         => $vvItem['tracking_number'],
                            'carrier_code'      => $vvItem['carrier_name'],
                            'paytime'           => $vvItem['paytime'],
                            'status'            => ShopeeOrderMarkShippedLog::STATUS_DEFAULT,
                            'type'              => ShopeeOrderMarkShippedLog::TYPE_TRUE,
                        );
                        $markModel = new ShopeeOrderMarkShippedLog();
                        $tmpMarkId = $markModel->saveNewData($markOrderData);
                    }else{
                        $tmpMarkId = $checkAdvanceShiped['id'];
                    }

                    //�ϴ����ٺ�
                    $shippedData = array(
                        'ordersn'           => $vvItem['platform_order_id'],
                        'tracking_number'   => $vvItem['tracking_number'],
                    );
                    $shopeeShipmentModel = new ShopeeShipment();
                    $shopeeShipmentModel->setAccountID($accountID);
                    $result =  $shopeeShipmentModel->uploadTrackingNumber( array($shippedData) );//�ϴ� 
                    $flag = !empty($result) && $result->result->success_count == 1;
                    if($bug){
                        $this->printbugmsg($result, "uploadTrackingNumber-Result");
                    }

                    //��Ƕ����ϴ��Ƿ�ɹ�
                    $tmpModel = ShopeeOrderMarkShippedLog::model()->findByPk($tmpMarkId);
                    $errorCount = (int)$checkAdvanceShiped['error_count'];
                    $status = $flag ? ShopeeOrderMarkShippedLog::STATUS_SUCCESS : ShopeeOrderMarkShippedLog::STATUS_FAILURE;
                    ShopeeOrderMarkShippedLog::model()->updateData( $tmpModel,array(
                        'id'            => $tmpMarkId,
                        'status'        => $status,
                        'error_count'   => $flag ? $errorCount : $errorCount+1,
                        'upload_time'   => date('Y-m-d H:i:s'),
                    ) );
                    if( $flag ){
                        $shopeeLog->saveEventStatus(ShopeeShipment::EVENT_UPLOAD_TRACK, $eventLog, ShopeeLog::STATUS_SUCCESS);
                    }else{
                        $shopeeLog->saveEventStatus(ShopeeShipment::EVENT_UPLOAD_TRACK, $eventLog, ShopeeLog::STATUS_FAILURE,'upload failure');
                    }
                    $orderPakageIds[$pkId][$vvItem['platform_order_id']] = $flag ? 1 : 0;
                    $isUploadOk &= $flag;
                }
            }

            if($bug){
                $this->printbugmsg($orderPakageIds, "orderPakageIds");              
            }

            //ѭ�����������°���״̬
            $packageCount = 0;
            foreach( $val as $pkId=>$vv ){ //ѭ������,һ�������������
                $isAbleUpdatePackage = true;
                if (count($vv)>1) {
                    foreach ($orderPakageIds[$pkId] as $platformOrderID => $val2) {
                        if ( $orderPakageIds[$pkId][$platformOrderID] == 0 ) {
                            $isAbleUpdatePackage = false;
                            break;
                        }
                    }
                } else if ( $orderPakageIds[$pkId][$vv[0]['platform_order_id']] == 0 ) {
                    $isAbleUpdatePackage = false;
                }
                if ($isAbleUpdatePackage) {
                    $res = UebModel::model('OrderPackage')->updateAll(array('is_confirm_shiped'=>1),"is_confirm_shiped=0 and package_id='{$pkId}' ");
                    if($bug){
                        $this->printbugmsg($res ? "success" : "failure", "update order package # ".$pkId);
                    }
                    $packageCount++;  
                }
            }

            if( $isSuccess ){
                $shopeeLog->setSuccess($logID,'Total:'.$packageCount);
            }else{
                $errorMessage = mb_substr($errorMessage, 0, 500);
                $shopeeLog->setFailure($logID, 'Total:'.$packageCount.' @@ '.$errorMessage);
            }
    	}
    	$this->printbugmsg("", " foreach data end ");
   	}

    /**
     * @desc ��ָ����С$n ��ȡ����
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
    
}