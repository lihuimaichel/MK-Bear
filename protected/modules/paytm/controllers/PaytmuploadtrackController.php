<?php
/**
 * @desc Paytm跟踪号上传
 * @author yangsh
 * @since 2017-03-30
 */
class PaytmuploadtrackController extends UebController{
    
    /**
     * @desc 访问过滤配置
     * @see CController::accessRules()
     */
    public function accessRules() {
        return array(
			array(
				'allow',
				'users' => array('*'),
				'actions' => array('uploadtracknum','uploadOne')
			),
		);
    }

    /**
     * @desc 上传跟踪号 
     * @desc 1.上传已生成包裹、已有跟踪号的订单。    
     * @author yangsh
     * @link /paytm/paytmuploadtrack/uploadtracknum/limit/10/bug/1
     *    /paytm/paytmuploadtrack/uploadtracknum/limit/10/package_id/PK151202036308/bug/1
     */
    public function actionUploadtracknum() {
    	set_time_limit(3600);
        ini_set("display_errors", true);
    	error_reporting(E_ALL & ~E_STRICT);

        $pkCreateDate = date('Y-m-d',strtotime('-15 days')); //下单时间
        $packageId    = Yii::app()->request->getParam('package_id', '');
        $accountID    = Yii::app()->request->getParam('account_id');
        $limit        = Yii::app()->request->getParam('limit', '');
        $bug          = Yii::app()->request->getParam('bug',0);
    	//获取要上传的包裹 
    	$packageInfos = PaytmShipment::model()->getPaytmWaitingUploadPackages($pkCreateDate,$packageId,$limit,$accountID);
    	if($bug){
    		$this->printbugmsg($packageInfos, "packageInfos");
    	}

        if(empty($packageInfos)) {
            die('packageInfos is empty');
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

    	//列表字符串有限制，每次查询限制在500以内
    	$ordArr = $this->splitByn($tmpOrderIds,500);
    	if($bug){
    		$this->printbugmsg($ordArr, "ordArr");
    	}
    	unset($tmpOrderIds);

    	//循环查出订单,item相关信息，并采集accountid
    	$data = array();
    	$orderArray = array();
    	foreach($ordArr as $val){
    		$orderIdStr = "'".implode("','",$val)."'";
    		$orderList = Order::model()->getInfoListByOrderIds($orderIdStr,'o.order_id,o.account_id,o.platform_order_id,o.paytime',Platform::CODE_PAYTM);
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
    	
    	//按照每个账号来整理数据
    	foreach($packageInfos as $key => $val){
    		$orderInfo = $orderArray[$val['order_id']];
    		$shipCode = !empty($val['real_ship_type'])?$val['real_ship_type']:$val['ship_code'];
    		$carrierCode = LogisticsPlatformCarrier::model()->getCarrierByShipCode($shipCode ,Platform::CODE_PAYTM );
            if(!$carrierCode) {
            	if($bug){
            		$this->printbugmsg($val['order_id'], "orderID");
            		$this->printbugmsg($shipCode, "shipCode");
            		$this->printbugmsg("continue it", "no carrierCode");
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
    		$data[$orderInfo['account_id']][$val['package_id']][] = $tmp;//账号-包裹-多订单
    	}
    	if($bug){
    		$this->printbugmsg($data, "foreach data");
    	}

        //循环账号开始上传跟踪号
    	foreach( $data as $accountID => $val ){
    		if( !$val ) continue;
    		$paytmLog = new PaytmLog();
    		$logID = $paytmLog->prepareLog($accountID,PaytmShipment::EVENT_UPLOAD_TRACK);
    		if( !$logID ) {
    			echo "create log id failure<br/>";
                continue;
            }
    		
            // //1.检查账号是否上传跟踪号
            $checkRunning = $paytmLog->checkRunning($accountID, PaytmShipment::EVENT_UPLOAD_TRACK);
            if( !$checkRunning ){
                $paytmLog->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
                if($bug){
                    echo  Yii::t('systems', 'There Exists An Active Event'), "<br>";
                }
                continue;
            }

            // //2.设置日志为正在运行
            $paytmLog->setRunning($logID);
            
            //循环包裹,一个包裹多个订单
            $shippedDatas = $orderPakageIds = $tmpMarkIdArr = array();
            $isSuccess = true;//标记事件是否上传成功
            foreach( $val as $pkId=>$vv ){ 
                if($bug){
                    $this->printbugmsg($pkId, "PKID");
                }
                $isUploadOk = true;//标记包裹上传跟踪号是否成功
                foreach( $vv as $vvItem ){ //循环订单
                    if($vvItem['carrier_name'] != 'Gati Air') {
                        $isUploadOk = false;
                        continue;
                    }
                    //检测是否之前有上传过或失败次数超过3次
                    $checkAdvanceShiped = PaytmOrderMarkShippedLog::model()->getInfoRowByOrderId( $vvItem['order_id'],'*' );

                    //标识包裹对应的订单是否上传成功
                    $orderPakageIds[$pkId][$vvItem['platform_order_id']] = !empty($checkAdvanceShiped) && $checkAdvanceShiped['status'] == PaytmOrderMarkShippedLog::STATUS_SUCCESS ? 1 : 0;

                    //是否需要上传跟踪号
                    if( $checkAdvanceShiped['status'] == PaytmOrderMarkShippedLog::STATUS_SUCCESS 
                         || ($checkAdvanceShiped['status'] == PaytmOrderMarkShippedLog::STATUS_FAILURE && $checkAdvanceShiped['error_count'] > PaytmOrderMarkShippedLog::UPLOAD_ERROR_MAX_COUNT ) ){ //不满足条件
                        if($bug){
                            $this->printbugmsg($checkAdvanceShiped, "checkAdvanceShiped");
                        }
                        continue;
                    }
                    
                    // //添加详细日志
                    $eventLog = $paytmLog->saveEventLog(PaytmShipment::EVENT_UPLOAD_TRACK, array(
                        'log_id'            => $logID,
                        'account_id'        => $accountID,
                        'platform_order_id' => $vvItem['platform_order_id'],
                        'order_id'          => $vvItem['order_id'],
                        'package_id'        => $pkId,
                        'track_number'      => $vvItem['tracking_number'],
                        'carrier_name'      => $vvItem['carrier_name'],
                        'start_time'        => date('Y-m-d H:i:s'),
                    ));
                    
                    //保存订单上传记录
                    if( empty($checkAdvanceShiped['order_id']) ){
                        $markOrderData = array(
                            'account_id'        => $accountID,
                            'platform_order_id' => $vvItem['platform_order_id'],
                            'order_id'          => $vvItem['order_id'],
                            'package_id'        => $pkId,
                            'track_num'         => $vvItem['tracking_number'],
                            'carrier_code'      => $vvItem['carrier_name'],
                            'paytime'           => $vvItem['paytime'],
                            'status'            => PaytmOrderMarkShippedLog::STATUS_DEFAULT,
                            'type'              => PaytmOrderMarkShippedLog::TYPE_TRUE,
                        );
                        $markModel = new PaytmOrderMarkShippedLog();
                        $tmpMarkId = $markModel->saveNewData($markOrderData);
                    }else{
                        $tmpMarkId = $checkAdvanceShiped['id'];
                    }

                    //上传跟踪号
                    $result = PaytmShipment::model()->uploadTrackingNumber($accountID,$pkId,$vvItem['order_id'],$vvItem['platform_order_id'],$vvItem['tracking_number']);
                    if($bug){
                        $this->printbugmsg($result, "uploadTrackingNumber-Result");
                    }

                    //标记订单上传是否成功
                    $tmpModel = PaytmOrderMarkShippedLog::model()->findByPk($tmpMarkId);
                    $errorCount = (int)$checkAdvanceShiped['error_count'];
                    PaytmOrderMarkShippedLog::model()->updateData( $tmpModel,array(
                        'id'            => $tmpMarkId,
                        'status'        => $result['flag'] ? PaytmOrderMarkShippedLog::STATUS_SUCCESS : PaytmOrderMarkShippedLog::STATUS_FAILURE,
                        'error_count'   => $result['flag'] ? $errorCount : $errorCount+1,
                        'upload_time'   => date('Y-m-d H:i:s'),
                    ) );
                    if( $result['flag'] ){
                        $paytmLog->saveEventStatus(PaytmShipment::EVENT_UPLOAD_TRACK, $eventLog, PaytmLog::STATUS_SUCCESS);
                    }else{
                        $errorMessage = $result['errMsg'];
                        $paytmLog->saveEventStatus(PaytmShipment::EVENT_UPLOAD_TRACK, $eventLog, PaytmLog::STATUS_FAILURE,$errorMessage);
                    }
                    $orderPakageIds[$pkId][$vvItem['platform_order_id']] = $result['flag'] ? 1 : 0;
                    $isUploadOk &= $result['flag'];
                }
                //标记事件是否成功
                $isSuccess &= $isUploadOk;
            }

            if($bug){
                $this->printbugmsg($orderPakageIds, "orderPakageIds");
            }

            //循环包裹，更新包裹状态
            $packageCount = 0;
            foreach( $val as $pkId=>$vv ){ //循环包裹,一个包裹多个订单
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
                $paytmLog->setSuccess($logID,'Total:'.$packageCount);
            }else{
                $errorMessage = mb_substr($errorMessage, 0, 500);
                $paytmLog->setFailure($logID, 'Total:'.$packageCount.' @@ '.$errorMessage);
            }
    	}
    	$this->printbugmsg("", " foreach data end ");
   	}

    /**
     * @desc 测试上传跟踪号
     * @link /paytm/paytmuploadtrack/uploadone/bug/1/order_id/1111
     *       /paytm/paytmuploadtrack/uploadone/bug/1/account_id/2/platform_order_id/1111
     */
    public function actionUploadone() {
        set_time_limit(3600);
        ini_set("display_errors", true);
        error_reporting(E_ALL & ~E_STRICT);

        $orderId = Yii::app()->request->getParam('order_id');
        if(!empty($orderId)) {
            $orderInfo = Order::model()->getInfoByOrderId($orderId,'platform_order_id,account_id');
        }
        $platformOrderID = Yii::app()->request->getParam('platform_order_id');
        $accountID = Yii::app()->request->getParam('account_id');
        if($platformOrderID && $accountID) {
            $platformOrderID = $accountID.'-'.$platformOrderID;
            $orderInfo = Order::model()->getOrderInfoByCondition($platformOrderID, Platform::CODE_PAYTM, $accountID);
            $orderId = empty($orderInfo) ? $orderId : $orderInfo['order_id'];
        }
        if(empty($orderInfo)) {
            die('orderInfo is empty');
        }
        $this->printbugmsg($orderInfo,'orderInfo');
        $accountID = $orderInfo['account_id'];
        $platformOrderID = $orderInfo['platform_order_id'];
        $packageInfos = OrderPackage::model()->getPackageInfoByOrderID($orderId);
        $this->printbugmsg($packageInfos,'packageInfos');
        if(empty($packageInfos)) {
            die('packageInfos is empty');
        }
        foreach ($packageInfos as $v) {
            $trackNos[$v['package_id']] = $v['track_num'];
        }
        $this->printbugmsg($trackNos,'trackNos');
        foreach ($trackNos as $packageId => $trackingNumber) {
            $isok = PaytmShipment::model()->uploadTrackingNumber($accountID,$packageId,$orderId,$platformOrderID,$trackingNumber);
            echo $orderId."<br>".var_export($isok,true);
        }        
        die('ok');
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