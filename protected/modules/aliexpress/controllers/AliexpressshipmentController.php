<?php
/**
 * @desc Aliexpress物流相关
 * @author Gordon
 * @since 2015-08-03
 */
class AliexpressshipmentController extends UebController{
    
    /**
     * @desc 访问过滤配置
     * @see CController::accessRules()
     */
    public function accessRules() {
      return array(
        array(
          'allow',
				  'users' => array('*'),
				  'actions' => array('createwaitshipped','confirmshipped','uploadtracknuma','handlefailureupload','wxtest','uploadtracknumpretrack','syncdatatooms')
        ),
		  );
    }

    /****************************************************************************************************************
     * 提前标记发货  start
     ****************************************************************************************************************/

    /**
     * 1、获取数据   
     * @author  Rex
     * @link    /aliexpress/aliexpressshipment/createwaitshipped/limit/10/account_id/150
     */
    public function actionCreatewaitshipped() {
      set_time_limit(600);
      $accountId2 = Yii::app()->request->getParam('account_id','150');
      $orderId2 = Yii::app()->request->getParam('order_id');
      $limit = Yii::app()->request->getParam('limit', '');
      $gmtime = gmdate('Y-m-d H:i:s'); //当前UTC时间
      echo '<pre>';

      //exit('Close');

      if ($accountId2 != 150) {
        exit('END');
      }

      $aliAccounts = AliexpressAccount::model()->getAbleAccountList();
      foreach ($aliAccounts as $accountInfo) {
        if ($accountId2 && $accountId2 != $accountInfo['id']) {
          continue;
        }


        $accountId = $accountInfo['id'];
        $aliAccountId = $accountInfo['account'];
        //var_dump($accountId, $aliAccountId);//exit;
        UebModel::model('AliexpressShipment')->createWaitShippedData($accountId,$aliAccountId,$gmtime,$orderId2,$limit);
        //UebModel::model('AliexpressShipment')->matchTrackToWaitShippedData($accountId,$aliAccountId,$orderId2,$limit);

        sleep(1);

        //exit;
      }

    }
    
    /**
     * 2、标记发货
     * @desc 针对ALI平台下单后，三天内未发货的订单提前声明发货
     * @link /aliexpress/aliexpressshipment/confirmshipped/limit/10/account_id/150/
     * @author wx
     */
    public function actionConfirmShipped() {
    	set_time_limit(600);
    	$accountIdParam = Yii::app()->request->getParam('account_id','');
    	$orderId = Yii::app()->request->getParam('order_id');
    	$limit = Yii::app()->request->getParam('limit', '');
    	$gmtime = gmdate('Y-m-d H:i:s'); //当前UTC时间
      echo '<pre>';

      //exit('CLOSE2');

      if (empty($accountIdParam)) {
        //exit('AAA');
      }

    	//排除没有跟踪号的快递
/*    	$excludeShipCode = array( strtoupper(Logistics::CODE_DHTD_DHL),strtoupper(Logistics::CODE_DHTD_IP),strtoupper(Logistics::CODE_DHTD_IE),strtoupper(Logistics::CODE_FEDEX_IE),strtoupper(Logistics::CODE_KD_TOLL),strtoupper(Logistics::CODE_EMS),strtoupper(Logistics::OODE_CM_ALI_DGYZ) );*/

    	$aliAccounts = AliexpressAccount::model()->getAbleAccountList();
    	foreach($aliAccounts as $account){
    		$accountID = $account['id'];	//ali账号表自增id
    		$aliAccountId = $account['account']; //ali账号id
    		if( !empty($accountIdParam) && $accountID != $accountIdParam ) continue; //test模式下有效
    		if( $accountID && $aliAccountId != '1698546146' ){ //排除Ali-a账号，此账号特殊，系统出货后才能上传跟踪号
    			 
    			$aliLog = new AliexpressLog();
    			$logID = $aliLog->prepareLog($accountID,AliexpressShipment::EVENT_ADVANCE_SHIPPED);
    			if( $logID ){
    				//1.检查账号是否在提交发货确认
    				$checkRunning = $aliLog->checkRunning($accountID, AliexpressShipment::EVENT_ADVANCE_SHIPPED);
            //$checkRunning = true;
    				if( !$checkRunning ){
    					$aliLog->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
    				}else{
    					//设置日志为正在运行
    					$aliLog->setRunning($logID);
    					//查询要上传的订单 开始上传
    					$waitingMarkOrders = AliexpressOrderMarkShippedLog::model()->getWaitingMarkShipOrder( $accountID );
    					//var_dump(count($waitingMarkOrders));//exit;
    					$isSuccess = true; //测试
    					$errorMessage = '';
    					//$carrierCode = 'CPAM';
    	
    					foreach( $waitingMarkOrders as $key => $val ){
    						//获取假tn单号
/*    						$retRandTn = $this->getRandTrackNum(3);//取3次随机跟踪号，直到成功
    						if( !$retRandTn['ret'] ) continue;
    						$trackVirtual = $retRandTn['trackVirtual'];
    						$tmpModel = AliexpressOrderMarkShippedLog::model()->findByPk($val['id']);
    						$updateData = array(
    								'id' => $val['id'],
    								'track_num' => $trackVirtual,
    								'carrier_code' => $carrierCode
    						);
    						AliexpressOrderMarkShippedLog::model()->updateData( $tmpModel,$updateData );*/

                $markId = $val['id'];
                $trackNum = trim($val['track_num']);
                $carrierCode =  trim($val['carrier_code']);

                if (empty($trackNum) || empty($carrierCode)) {
                  continue;
                }

                $tmpModel = AliexpressOrderMarkShippedLog::model()->findByPk($markId);
    	
    						//2.插入本次log参数日志(用来记录请求的参数)
/*    						$eventLog = $aliLog->saveEventLog(AliexpressShipment::EVENT_ADVANCE_SHIPPED, array(
    								'log_id'        => $logID,
    								'account_id'    => $accountID,
    								'platform_order_id' => $val['platform_order_id'],
    								'order_id'      => $val['order_id'],
    								'track_number'  => $trackNum,
    								'carrier_name'  => $carrierCode,
    								'start_time'    => date('Y-m-d H:i:s'),
    						));*/
    	
    						//3.提前确认发货
    						$shippedData = array(
    								'outRef' => $val['platform_order_id'],
    								'serviceName' => $carrierCode,
    								'logisticsNo' => $trackNum,
    						);
                if ($carrierCode == 'Other') {
                  $trackingWebsite = AliexpressShipment::getSellerShipmentInfoByServerName($val['ship_code']);
                  $shippedData['trackingWebsite'] = $trackingWebsite['website'];
                }

                //var_dump($shippedData);

    						$aliShipmentModel = new AliexpressShipment();
    						$aliShipmentModel->setAccountID($accountID);//设置账号
    						$flag = $aliShipmentModel->uploadSellerShipment( $shippedData );//上传
    						//4.更新日志信息
    						if( $flag ){
    							//5.上传成功更新记录表
    							$updateData = array(
    									'id' => $val['id'],
    									'status' => AliexpressOrderMarkShippedLog::STATUS_SUCCESS,
    									'upload_time' => date('Y-m-d H:i:s'),
    							);
    							AliexpressOrderMarkShippedLog::model()->updateData( $tmpModel,$updateData );
    							//$aliLog->saveEventStatus(AliexpressShipment::EVENT_ADVANCE_SHIPPED, $eventLog, $aliLog::STATUS_SUCCESS);
    						}else{
    							$updateData = array(
    									'id' => $val['id'],
    									'status' => AliexpressOrderMarkShippedLog::STATUS_FAILURE,
    									'errormsg' => $aliShipmentModel->getExceptionMessage(),
    									'upload_time' => date('Y-m-d H:i:s'),
    									'error_type' => $this->errorTypeMap( trim($aliShipmentModel->getErrorcode()),$aliShipmentModel->getExceptionMessage() ),
    							);
    							AliexpressOrderMarkShippedLog::model()->updateData( $tmpModel,$updateData );
    							//$aliLog->saveEventStatus(AliexpressShipment::EVENT_ADVANCE_SHIPPED, $eventLog, $aliLog::STATUS_FAILURE,$aliShipmentModel->getExceptionMessage());
    							$errorMessage .= $aliShipmentModel->getExceptionMessage();
    						}
    						$isSuccess = $isSuccess && $flag;
    					}
    					//if( $isSuccess ){
    						$aliLog->setSuccess($logID, 'Total: '.count($waitingMarkOrders));
    					//}else{
    					//	if( strlen($errorMessage)>1000 ) $errorMessage = substr($errorMessage,0,1000);
    					//	$aliLog->setFailure($logID, $errorMessage);
    					//}
    				}
    					
    			}
    		}
    		sleep(2);
    	}
      echo '<br/>ok';
    }


    /****************************************************************************************************************
     * 出货后正常上传跟踪号  start
     ****************************************************************************************************************/

    /**
     * 上传跟踪号
     * @link    /aliexpress/aliexpressshipment/uploadtracknum
     *          /aliexpress/aliexpressshipment/uploadtracknuma/type/1  线上渠道
     *          /aliexpress/aliexpressshipment/uploadtracknuma/type/2  其他渠道
     * @author  Rex
     */
    public function actionUploadtracknuma() {
      set_time_limit(3600);

      $type = Yii::app()->request->getParam('type', '');
      $platformOrderId = Yii::app()->request->getParam('platform_order_id', '');
      $accountId2 = Yii::app()->request->getParam('account_id', '');
      echo '<pre>';

      $aliAccounts = AliexpressAccount::model()->getAbleAccountList();
      if ($accountId2) {
        foreach ($aliAccounts as $key => $value) {
          if ($accountId2 == $value['id']) {
            $aliAccounts = array();
            $aliAccounts[] = $value;
          }
        }
      }

      if (empty($platformOrderId) && empty($accountId2)) {
        //exit('END');
      }

      if ($type == '1') {
        //echo '111';
        $this->startUploadTrakNum1($aliAccounts,$platformOrderId);
      }elseif ($type == '2') {
        //echo '222';
        $this->startUploadTrakNum2($aliAccounts,$platformOrderId);
      }
      echo '<br/>END';
    }

    private function startUploadTrakNum1($aliAccounts,$platformOrderId) {
      $model = AliexpressShipment::model();
      foreach ($aliAccounts as $accountInfo) {
        $accountID = $accountInfo['id'];
        $aliLog = new AliexpressLog();

        $logID = $aliLog->prepareLog($accountID,AliexpressShipment::EVENT_UPLOAD_TRACK.'_1');
        if (!$logID) continue;
        $checkRunning = $aliLog->checkRunning($accountID, AliexpressShipment::EVENT_UPLOAD_TRACK.'_1');
        //var_dump($checkRunning);
        if (!$checkRunning) {
          echo 'There Exists An Active Event<br/>';
          $aliLog->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
          continue;
        }

        $aliLog->setRunning($logID);

        $waitUploadOrderList = $model->getAliWaitUploadOrderList1($accountInfo['account'],$platformOrderId);

        //var_dump(count($waitUploadOrderList));

        foreach ($waitUploadOrderList as $key => $uploadOrderInfo) {

          $isResult = true;
          $flagSetConfirm = false;
          $flagUpload = flase;
          $flag = false;

          //var_dump($uploadOrderInfo);//exit;

          $markShippedInfo = AliexpressOrderMarkShippedLog::model()->getInfoRowByOrderId($uploadOrderInfo['order_id'],'*');
          if ($markShippedInfo) {
            if ($markShippedInfo['status'] != AliexpressOrderMarkShippedLog::STATUS_SUCCESS) {
              $flagUpload = true;
            }else{
              $flagSetConfirm = true;
            }
          } else {
            $flagUpload = true;
          }

          $packageId = $uploadOrderInfo['package_id'];
          $shipCodeNew = !empty($uploadOrderInfo['real_ship_type']) ? $uploadOrderInfo['real_ship_type'] : $uploadOrderInfo['ship_code'];
          $shipCodeNew = strtolower($shipCodeNew);
          $carrierCode = LogisticsPlatformCarrier::model()->getCarrierByShipCode($shipCodeNew, Platform::CODE_ALIEXPRESS);
          $trackingWebSite = AliexpressShipment::getSellerShipmentInfoByServerName($shipCodeNew);

          $tmpMarkId = 0;
          if( empty($markShippedInfo['order_id']) ){
            $markOrderData = array(
                'account_id'        => $accountID,
                'platform_order_id' => $uploadOrderInfo['platform_order_id'],
                'order_id'          => $uploadOrderInfo['order_id'],
                'package_id'        => $uploadOrderInfo['package_id'],
                'track_num'         => $uploadOrderInfo['track_num'],
                'carrier_code'      => $carrierCode,
                'ship_code'         => $shipCodeNew,
                'paytime'           => $uploadOrderInfo['paytime'],
                'status'            => AliexpressOrderMarkShippedLog::STATUS_DEFAULT,
                'type'              => AliexpressOrderMarkShippedLog::TYPE_TRUE, //上传真实单号
            );
            $markModel = new AliexpressOrderMarkShippedLog();
            $tmpMarkId = $markModel->saveNewData($markOrderData);
          }else{
            $tmpMarkId = $markShippedInfo['id'];
          }

          if (!$carrierCode) continue;

          //添加详细日志
/*          $eventLog = $aliLog->saveEventLog(AliexpressShipment::EVENT_UPLOAD_TRACK, array(
              'log_id'        => $logID,
              'account_id'    => $accountID,
              'platform_order_id'  => $uploadOrderInfo['platform_order_id'],
              'order_id'      => $uploadOrderInfo['order_id'],
              'package_id'    => $packageId,
              'track_number'  => $trackNumNew,
              'carrier_name'  => $carrierCode,
              'start_time'    => date('Y-m-d H:i:s'),
          ));*/

          //设置账号信息
          $aliShipmentModel = new AliexpressShipment();
          $aliShipmentModel->setAccountID($accountID);
          $tmpModel = AliexpressOrderMarkShippedLog::model()->findByPk($tmpMarkId);

          if ($flagUpload === true) {
            $shippedData = array(
              'serviceName'     => $carrierCode,
              'logisticsNo'     => $uploadOrderInfo['track_num'],
              'outRef'          => $uploadOrderInfo['platform_order_id'],
              'trackingWebsite' => $trackingWebSite['website'],
            );
            //var_dump($shippedData);

            $flag = $aliShipmentModel->uploadSellerShipment($shippedData);//上传
            $errorMessageSub = $aliShipmentModel->getExceptionMessage();
            //var_dump($flag);
            if ($flag) {
              $updateData = array(
                  'id'          => $tmpMarkId,
                  'package_id'  => $packageId,
                  'carrier_code' => $carrierCode,
                  'ship_code'   => $shipCodeNew,
                  'status'      => AliexpressOrderMarkShippedLog::STATUS_SUCCESS,
                  'upload_time' => date('Y-m-d H:i:s'),
              );
            }else {
              $updateData = array(
                  'id'          => $tmpMarkId,
                  'status'      => AliexpressOrderMarkShippedLog::STATUS_FAILURE,
                  'upload_time' => date('Y-m-d H:i:s'),
                  'errormsg'    => $errorMessageSub,
                  'error_type'  => $this->errorTypeMap(trim($aliShipmentModel->getErrorcode()),trim($aliShipmentModel->getExceptionMessage())),
              );

              if (in_array($updateData['error_type'], $this->canNotDealType())) {
                $flagSetConfirm = true;
              }
            }

            $ret = AliexpressOrderMarkShippedLog::model()->updateData($tmpModel,$updateData);

            $isResult = $isResult && $flag;

            $isSuccess = $isSuccess && $isResult;

          }

/*          if($flag){
            $aliLog->saveEventStatus(AliexpressShipment::EVENT_UPLOAD_TRACK, $eventLog, $aliLog::STATUS_SUCCESS);
          }else{
            $aliLog->saveEventStatus(AliexpressShipment::EVENT_UPLOAD_TRACK, $eventLog, $aliLog::STATUS_FAILURE,$errorMessageSub);
          }*/

          if($isResult){
            UebModel::model('OrderPackage')->updateAll(array('is_confirm_shiped'=>1)," is_confirm_shiped=0 and package_id = '{$packageId}' ");
          }

          if ($flagSetConfirm === true) {
            UebModel::model('OrderPackage')->updateAll(array('is_confirm_shiped'=>99),"is_confirm_shiped=0 and package_id = '{$packageId}' ");
          }

          //var_dump($uploadOrderInfo['platform_order_id'],$packageId,$flagSetConfirm,$flagUpload);
          

        } //endforeach

        sleep(1);
        $aliLog->setSuccess($logID, 'total: '.count($waitUploadOrderList));
        
      }

      echo 'ok<br/>';

    }

    private function startUploadTrakNum2($aliAccounts,$platformOrderId) {
      //跟踪号需要添加前后缀的物流方式
      $addPreSuffFixShipCode = MHelper::getNewArray(array(Logistics::CODE_CM_DGYZ), 2);
      //需要上传track_num2的物流方式 2017-01-09 cxy #2558 更正深圳顺丰立陶宛带电挂号上传跟踪号格式  Logistics::CODE_GHXB_SF_E
      $needTrackNumShipCode = MHelper::getNewArray(array(Logistics::CODE_CM_YW_TEQXB), 2); 

      $notCanShipLotistics = AliexpressShipment::getAliNotCanShipLogistics();

      $model = AliexpressShipment::model();
      foreach ($aliAccounts as $accountInfo) {
        $accountID = $accountInfo['id'];
        $aliLog = new AliexpressLog();

        //$aliLog->setFailure(2305397);

        $logID = $aliLog->prepareLog($accountID,AliexpressShipment::EVENT_UPLOAD_TRACK.'_2');
        if (!$logID) continue;
        $checkRunning = $aliLog->checkRunning($accountID, AliexpressShipment::EVENT_UPLOAD_TRACK.'_2');
        //var_dump($checkRunning);
        if (!$checkRunning) {
          echo 'There Exists An Active Event<br/>';
          $aliLog->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
          continue;
        }

        $aliLog->setRunning($logID);

        $waitUploadOrderList = $model->getAliWaitUploadOrderList2($accountInfo['account'],$platformOrderId);

        //var_dump(count($waitUploadOrderList));//exit;

        foreach ($waitUploadOrderList as $key => $uploadOrderInfo) {

          $isResult = true;
          $flagSetConfirm = false;
          $flagUpload = false;
          $flagModify = false;
          $flagSpe = false;
          $flagCanShip = true;
          $flag = false;

          //var_dump($uploadOrderInfo['order_id']);//exit;

          $markShippedInfo = AliexpressOrderMarkShippedLog::model()->getInfoRowByOrderId($uploadOrderInfo['order_id'],'*');
          if ($markShippedInfo) {
            if ($markShippedInfo['status'] == AliexpressOrderMarkShippedLog::STATUS_SUCCESS) {
              $flagModify = true;
            }elseif ($markShippedInfo['status'] == AliexpressOrderMarkShippedLog::STATUS_FAILURE) {
              if ($markShippedInfo['type'] != aliexpressshipment::SERVICE_UPLOAD_TRACK) {
                AliexpressOrderMarkShippedLog::model()->deleteAll("id={$markShippedInfo['id']} and order_id='{$markShippedInfo['order_id']}'");
                $markShippedInfo = false;
                $flagUpload = true;
              } else {
                if (!in_array($markShippedInfo['error_type'], $this->canNotDealType())
                  || !in_array($markShippedInfo['update_error_type'], $this->canNotDealType()) ) {
                  $flagUpload = true;
                }
                if (in_array($markShippedInfo['error_type'], $this->canNotDealType())
                  || in_array($markShippedInfo['update_error_type'], $this->canNotDealType()) ) {
                  //$flagSetConfirm = true;
                }
              }
            }else {
              $flagUpload = true;
            }
          } else {
            $flagUpload = true;
          }

          $packageId = $uploadOrderInfo['package_id'];
          $shipCodeNew = !empty($uploadOrderInfo['real_ship_type']) ? $uploadOrderInfo['real_ship_type'] : $uploadOrderInfo['ship_code'];
          $shipCodeNew = strtolower($shipCodeNew);
          $trackNumNew = $uploadOrderInfo['track_num'];
          $carrierCode = LogisticsPlatformCarrier::model()->getCarrierByShipCode($shipCodeNew, Platform::CODE_ALIEXPRESS);
          $trackingWebSite = AliexpressShipment::getSellerShipmentInfoByServerName($shipCodeNew);

          //处理跟踪号
          if(in_array($shipCodeNew, $addPreSuffFixShipCode)){
            $trackNumNew = 'HNKYB'.trim($uploadOrderInfo['track_num']).'YQ';
          }
          if( in_array($shipCodeNew ,$needTrackNumShipCode ) ){
            $trackNumNew = $uploadOrderInfo['track_num2'];
          }

          if (in_array($shipCodeNew, $notCanShipLotistics)) {
/*            $carrierCode = 'Other';
            empty($uploadOrderInfo['track_num']) && $uploadOrderInfo['track_num'] = str_replace('PK', '', $uploadOrderInfo['package_id']);
            $trackNumNew = 'HJ'.trim($uploadOrderInfo['track_num']);*/
            //var_dump($trackNumNew);

            $flagCanShip = false;
            $shipMatchInfo = UebModel::model('OrderAdvanceShipMatch')->getInfoByOrderId($uploadOrderInfo['order_id']);
            if ($shipMatchInfo['track_num']) {
              $shipCodeNew = trim($shipMatchInfo['ship_code']);
              $carrierCode = LogisticsPlatformCarrier::model()->getCarrierByShipCode($shipCodeNew, Platform::CODE_ALIEXPRESS);
              $trackNumNew = $shipMatchInfo['track_num'];

              $flagSpe = true;
            }
          }

          $tmpMarkId = 0;
          if( empty($markShippedInfo['order_id']) ){
            $markOrderData = array(
                'account_id'        => $accountID,
                'platform_order_id' => $uploadOrderInfo['platform_order_id'],
                'order_id'          => $uploadOrderInfo['order_id'],
                'package_id'        => $packageId,
                'track_num'         => $trackNumNew,
                'carrier_code'      => $carrierCode,
                'ship_code'         => $shipCodeNew,
                'paytime'           => $uploadOrderInfo['paytime'],
                'status'            => AliexpressOrderMarkShippedLog::STATUS_DEFAULT,
                'type'              => AliexpressOrderMarkShippedLog::TYPE_TRUE, //上传真实单号
            );
            $markModel = new AliexpressOrderMarkShippedLog();
            $tmpMarkId = $markModel->saveNewData($markOrderData);
          }else{
            $tmpMarkId = $markShippedInfo['id'];
          }

          //设置账号信息
          $aliShipmentModel = new AliexpressShipment();
          $aliShipmentModel->setAccountID($accountID);
          $tmpModel = AliexpressOrderMarkShippedLog::model()->findByPk($tmpMarkId);

          if (!$carrierCode) {
            $ret = AliexpressOrderMarkShippedLog::model()->updateData($tmpModel,array('errormsg'=>'carrierCode not find！'));
            continue;
          }

          $isCanUpload = AliexpressShipment::model()->checkCarrierIsCanUpload($shipCodeNew);
          //var_dump($isCanUpload, $flagSpe);
          if (!$isCanUpload && !$flagSpe) {
            $ret = AliexpressOrderMarkShippedLog::model()->updateData($tmpModel,array('errormsg'=>'Logistics not upload！'));
            continue;
          }

          //添加详细日志
/*          $eventLog = $aliLog->saveEventLog(AliexpressShipment::EVENT_UPLOAD_TRACK, array(
              'log_id'        => $logID,
              'account_id'    => $accountID,
              'platform_order_id'  => $uploadOrderInfo['platform_order_id'],
              'order_id'      => $uploadOrderInfo['order_id'],
              'package_id'    => $packageId,
              'track_number'  => $trackNumNew,
              'carrier_name'  => $carrierCode,
              'start_time'    => date('Y-m-d H:i:s'),
          ));*/

          if ($flagUpload === true) {
            $shippedData = array(
              'serviceName'     => $carrierCode,
              'logisticsNo'     => $trackNumNew,
              'outRef'          => $uploadOrderInfo['platform_order_id'],
              'trackingWebsite' => $trackingWebSite['website'],
            );
            //var_dump($shippedData);
            //$aliLog->setSuccess($logID);
            //exit();

            $flag = $aliShipmentModel->uploadSellerShipment($shippedData);//上传
            $errorMessageSub = $aliShipmentModel->getExceptionMessage();
            //var_dump($flag);
            if ($flag) {
              $updateData = array(
                  'id'          => $tmpMarkId,
                  'package_id'  => $packageId,
                  'track_num'   => $trackNumNew,
                  'carrier_code' => $carrierCode,
                  'ship_code'   => $shipCodeNew,
                  'status'      => AliexpressOrderMarkShippedLog::STATUS_SUCCESS,
                  'upload_time' => date('Y-m-d H:i:s'),
              );
              if ($flagSpe) {
                $updateData['errormsg'] = '渠道不支持，换其他平台上传';
              }
            }else {
              $updateData = array(
                  'id'          => $tmpMarkId,
                  'status'      => AliexpressOrderMarkShippedLog::STATUS_FAILURE,
                  'upload_time' => date('Y-m-d H:i:s'),
                  'errormsg'    => $errorMessageSub,
                  'error_type'  => $this->errorTypeMap(trim($aliShipmentModel->getErrorcode()),trim($aliShipmentModel->getExceptionMessage())),
              );

              if (in_array($updateData['error_type'], $this->canNotDealType())) {
                $flagSetConfirm = true;
              }
            }

            $ret = AliexpressOrderMarkShippedLog::model()->updateData($tmpModel,$updateData);

            $isResult = $isResult && $flag;

            $isSuccess = $isSuccess && $isResult;

          }

          if ($flagUpload == false && $flagModify == true) {
            $shippedData = array(
              'oldServiceName' => $markShippedInfo['carrier_code'],
              'oldLogisticsNo' => $markShippedInfo['track_num'],
              'newServiceName' => $carrierCode,
              'newLogisticsNo' => $trackNumNew,
              'outRef'         => $uploadOrderInfo['platform_order_id'],
              'trackingWebsite' => $trackingWebSite['website'],
            );
            //var_dump($shippedData,$flag);
            $flag = $aliShipmentModel->modifySellerShipment( $shippedData );//上传
            $errorMessageSub = $aliShipmentModel->getExceptionMessage();
            
            if($flag){ //更新成功
              $updateData = array(
                'id'              => $tmpMarkId,
                'update_status'   => AliexpressOrderMarkShippedLog::UPDATE_STATUS_SUCCESS,
                'update_time'     => date('Y-m-d H:i:s'),
                'package_id'      => $pkId,
                'update_errormsg' => $errorMessageSub,
              );
            }else{
              $updateData = array(
                'id' => $tmpMarkId,
                'update_status' => AliexpressOrderMarkShippedLog::UPDATE_STATUS_FAILURE,
                'update_time' => date('Y-m-d H:i:s'),
                'package_id'  => $pkId,
                'update_errormsg' => $errorMessageSub,
                'update_error_type' => $this->errorTypeMap(trim($aliShipmentModel->getErrorcode()),trim($aliShipmentModel->getExceptionMessage())),
              );
              
              if (in_array($updateData['update_error_type'], $this->canNotDealType())) {
                $flagSetConfirm = true;
              }

            }

            $ret = AliexpressOrderMarkShippedLog::model()->updateData($tmpModel,$updateData);

            $isResult = $isResult && $flag;

          }

/*          if($flag){
            $aliLog->saveEventStatus(AliexpressShipment::EVENT_UPLOAD_TRACK, $eventLog, $aliLog::STATUS_SUCCESS);
          }else{
            $aliLog->saveEventStatus(AliexpressShipment::EVENT_UPLOAD_TRACK, $eventLog, $aliLog::STATUS_FAILURE,$errorMessageSub);
          }*/

          if($isResult){
            UebModel::model('OrderPackage')->updateAll(array('is_confirm_shiped'=>1)," is_confirm_shiped=0 and package_id = '{$packageId}' ");
          }

          if ($flagSetConfirm === true) {
            UebModel::model('OrderPackage')->updateAll(array('is_confirm_shiped'=>99),"is_confirm_shiped=0 and package_id = '{$packageId}' ");
          }

          //var_dump($uploadOrderInfo['platform_order_id'],$packageId,$flagSetConfirm,$flagUpload,$flagModify,$flag);
        } // end foreach

        sleep(1);
        $aliLog->setSuccess($logID, 'total: '.count($waitUploadOrderList));
        
      }

      echo 'ok<br/>';

    }
    
    /**
     * @desc 上传跟踪号 http://erp_market.com/aliexpress/aliexpressshipment/uploadtracknum
     * @desc 多线程跑 http://erp_market.com/aliexpress/aliexpressshipment/uploadtracknum/type/1
     * @desc 1.上传已生成包裹、已有跟踪号的订单。  2.之前发过假单号的，付款时间不超过15天的，如果有真实tn则修改tn。 
     * @author wx
     */
   	public function actionUploadTrackNumDel() {
   		set_time_limit(1800);
   		/* AliexpressOrderMarkShippedLog::model()->updateByPk('104801', array('errormsg'=>'','error_type'=>0));
   		 exit; */
   		$limit = Yii::app()->request->getParam('limit', '');
   		$type = Yii::app()->request->getParam('type', '');
   		$packageId = Yii::app()->request->getParam('package_id', '');
   		$pkCreateDate = date('Y-m-d',strtotime('-20 days'));

      echo '<pre>';

      exit('CLOSE3');
   		
   		/* $hand = Yii::app()->request->getParam('hand');
   		
   		if( !$hand ) exit('调试中...,稍后开启'); */
   		
   		if($type == 1){ //多线程
        exit('AAA');
   			$id = Yii::app()->request->getParam('id',-1);
   			$threadNum = 10; //线程个数
   			$totalCount = OrderPackage::model()->getAliWaitingUploadPackagesCount($pkCreateDate,$packageId);
   			$count = ceil($totalCount['total']/$threadNum);	//每条线程跑的个数
   			if( $id >= 0 ){
   				$this->executeUploadData($pkCreateDate, $packageId, $count, $id*$count, 1);
   				$fileUrl = 'ali_shipped_log.txt';
   				$fd = @fopen($fileUrl,'a+');
   				@fwrite($fd,date('Y-m-d H:i:s')."线程:{$id},limit:{$count},offset:".$id*$count."\r\n");
   				@fclose($fd);
   			}else{
   				for($i=0;$i<$threadNum;$i++){
   					MHelper::runThreadSOCKET("/aliexpress/aliexpressshipment/uploadtracknum/type/1/id/".$i);
   					echo '<br/>i='.$i;
   					sleep(2);
   				}
   			}
   		}else{ //单线程
   			$this->executeUploadData($pkCreateDate, $packageId, '', '', 1);
   		}
   	}

    /**
     * 跟踪号上传失败分情况重置重新上传
     * @link  /aliexpress/aliexpressshipment/handlefailureupload/package_id/PK161001004255
     * @author Rex
     * @since  2016-10-06 16:01
     */
    public function actionHandlefailureupload() {
      $packageId = Yii::app()->request->getParam('package_id', '');
      $orderList = UebModel::model('AliexpressOrderMarkShippedLog')->getUploadFailureOrder($packageId);
      echo '<pre>';

      if (empty($packageId)) {
        exit('Close');
      }

      var_dump($orderList);

      $aliShipmentModel = new AliexpressShipment();
      foreach ($orderList as $key => $value) {
        $accountInfo = UebModel::model('AliexpressAccount')->getAccountInfoById($value['account_id']);
        $accountID = $accountInfo['account'];
        //var_dump($accountID);exit;
        $aliShipmentModel->setAccountID($accountID);
        $shippedData = array(
          'serviceName' => $value['carrier_code'],
          'logisticsNo' => $value['track_num'],
          'outRef'      => $value['platform_order_id'],
          //'trackingWebsite' => $trackingWebsite['website'],
        );

        if (isset($_GET['isTest']) && $_GET['isTest'] == 1) {
          $flag = $aliShipmentModel->uploadSellerShipment( $shippedData );//上传
          $errorMessageSub = $aliShipmentModel->getExceptionMessage();
          $errorCode = $aliShipmentModel->getErrorcode();
          $errType = $this->errorTypeMap( trim($errorCode),$errorMessageSub );
          var_dump($flag,$errorMessageSub,$errorCode,$errType);
        }

        if (in_array($errType, array('7','97'))) {
          //重置
        }

      }
    }
   	
   	private function executeUploadData($pkCreateDate,$packageId,$limit,$offset,$isPreTrack){

      exit();

/*   		$excludeShipCode = array( strtolower(Logistics::CODE_CM_HK),strtolower(Logistics::CODE_CM_DHL),strtolower(Logistics::CODE_CM_SGXB),strtolower(Logistics::CODE_CM_FU),strtolower(Logistics::CODE_CM_GYHL),strtolower(Logistics::CODE_CM_PUTIAN),strtolower(Logistics::CODE_CM_PUTIAN_E),strtolower(Logistics::CODE_CM_FU),strtolower(Logistics::CODE_CM_DHL),strtolower(Logistics::CODE_DHL_XB_DE),strtolower(Logistics::CODE_CM_HK),strtolower(Logistics::CODE_CM_SGXB),strtolower(Logistics::CODE_CM_PTXB),strtolower(Logistics::CODE_CM_PTXB_E),strtolower(Logistics::CODE_CM_ZXYZ),strtolower(Logistics::CODE_CM_DEYZ),strtolower(Logistics::OODE_SWYH_ALI_PING),strtolower(Logistics::CODE_CM_QZYZ),strtolower(Logistics::CODE_CM_QZ_DDXB) ); */

      //排除的发货方式（有些物流方式只上传假单号）
      $excludeShipCode = MHelper::getNewArray(array(Logistics::CODE_CM_HK,Logistics::CODE_CM_DHL,Logistics::CODE_CM_SGXB,Logistics::CODE_CM_FU,Logistics::CODE_CM_GYHL,Logistics::CODE_CM_PUTIAN,Logistics::CODE_CM_PUTIAN_E,Logistics::CODE_CM_FU,Logistics::CODE_CM_DHL,Logistics::CODE_DHL_XB_DE,Logistics::CODE_CM_HK,Logistics::CODE_CM_SGXB,Logistics::CODE_CM_PTXB,Logistics::CODE_CM_PTXB_E,Logistics::CODE_CM_ZXYZ,Logistics::CODE_CM_DEYZ,Logistics::OODE_SWYH_ALI_PING,Logistics::CODE_CM_QZYZ,Logistics::CODE_CM_QZ_DDXB ), 2);   
      //跟踪号需要添加前后缀的物流方式
   		$addPreSuffFixShipCode = MHelper::getNewArray(array(Logistics::CODE_CM_DGYZ), 2);  
      //需要上传track_num2的物流方式
   		$needTrackNumShipCode = MHelper::getNewArray(array(Logistics::CODE_GHXB_SF,Logistics::CODE_GHXB_SF_E,Logistics::CODE_CM_YW_TEQXB), 2);  

      //$limit = 500;  //测试
   		
   		if( $isPreTrack == 1 ){ ////获取要上传的包裹 包含之前提前发货的订单。
   			$packageInfos = OrderPackage::model()->getAliWaitingUploadPackages($pkCreateDate,$packageId,$limit,$offset);
   		}else{ //获取要上传的预匹配渠道包裹 包含之前提前发货的订单。
        exit('END');
   			//$packageInfos = OrderPackage::model()->getAliWaitingUploadSpecial($pkCreateDate,$packageId,$limit,$offset);
   		}

      var_dump(count($packageInfos));//exit;

      if (count($packageInfos) < 1) {
        return false;
      }
   		
   		$tmpOrderIds = array();
   		foreach( $packageInfos as $key => $val ){
   			if( !in_array($val['order_id'],$tmpOrderIds) ){
   				$tmpOrderIds[] = $val['order_id'];
   			}
   		}
   		//var_dump($tmpOrderIds);exit;
   		//列表字符串有限制，每次查询限制在500以内
   		$ordArr = UebModel::model('AliexpressShipment')->splitByn($tmpOrderIds,500);
   		//var_dump($ordArr);exit;
   		unset($tmpOrderIds);
   		 
   		//循环查出订单,item相关信息，并采集accountid
   		$data = array();
   		$orderArray = array();
   		foreach($ordArr as $val){
   			$orderIdStr = "'".implode("','",$val)."'";
   			$orderList = Order::model()->getInfoListByOrderIds($orderIdStr,'o.order_id,o.account_id,o.platform_order_id,o.paytime',Platform::CODE_ALIEXPRESS);
   			//var_dump($orderList);exit;
   			foreach( $orderList as $k => $v ){
   				if( !in_array($v['account_id'],array_keys($data)) ) {$data[$v['account_id']] = array();}
   				$orderArray[$v['order_id']]['account_id']        = $v['account_id'];
   				$orderArray[$v['order_id']]['platform_order_id'] = $v['platform_order_id'];
   				$orderArray[$v['order_id']]['paytime']				   = $v['paytime'];
   			}
   		}
   		//print_r($orderArray);exit;
   		
   		//按照每个账号来整理数据
   		foreach($packageInfos as $key => $val){
   			$orderInfo = $orderArray[$val['order_id']];
   			$currShipCode = !empty($val['real_ship_type'])?strtolower($val['real_ship_type']):strtolower($val['ship_code']);
   			$carrierCode = LogisticsPlatformCarrier::model()->getCarrierByShipCode( $currShipCode,Platform::CODE_ALIEXPRESS );
   			if(!$carrierCode) continue;

   			$packageTrackNum = $val['track_num'];
   			
   			if(in_array($currShipCode,$addPreSuffFixShipCode)){
   				$packageTrackNum = 'HNKYB'.$packageTrackNum.'YQ';
   			}
   			
   			if( in_array( $currShipCode ,$needTrackNumShipCode ) ){
   				$packageTrackNum = $val['track_num2'];
   			}
   			
   			$tmp = array(
   					'order_id'			=> $val['order_id'],
   					'platform_order_id'	=> $orderInfo['platform_order_id'],
   					'package_id' 		=> $val['package_id'],
   					'carrier_name'		=> $carrierCode,
            'ship_code'       => $val['ship_code'],
   					'tracking_number' 	=> $packageTrackNum,
   					'paytime'			=> $orderInfo['paytime'],
   					'real_ship_type'	=> $currShipCode,
   			);
   			$data[$orderInfo['account_id']][$val['package_id']][] = $tmp;
   		}
   
   		//var_dump($data);exit;

   		foreach( $data as $key => $val ){ //循环账号
   			if( !$val ) continue;
   			$accountInfo = AliexpressAccount::model()->getAccountInfoById( $key );
   			$accountID = $accountInfo['id']; //ali账号表自增id
   			$aliLog = new AliexpressLog();
   			$logID = $aliLog->prepareLog($accountID,AliexpressShipment::EVENT_UPLOAD_TRACK);
   			if( $logID ){
   				//1.检查账号是否上传跟踪号
   				$checkRunning = $aliLog->checkRunning($accountID, AliexpressShipment::EVENT_UPLOAD_TRACK);
          $checkRunning = true;
   				if( !$checkRunning ){
   					$aliLog->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
   				}else{
   					//设置日志为正在运行
   					$aliLog->setRunning($logID);
   					$isSuccess = true;
   					$errorMessage = '';
   					foreach( $val as $pkId=>$vv ){ //循环包裹
   						$isResult = true;
   						foreach( $vv as $vvItem ){ //循环订单明细
   							//检测是否之前有上传过
   							$checkAdvanceShiped = AliexpressOrderMarkShippedLog::model()->getInfoRowByOrderId( $vvItem['order_id'],'*' );
   							if($checkAdvanceShiped['order_id'] && (!in_array($checkAdvanceShiped['error_type'], array(0,96,97,5,127,999)) || !in_array($checkAdvanceShiped['update_error_type'], array(0,96,97,5,127,999))) ){ //不满足条件
   								$isResult = false;
                  //UebModel::model('OrderPackage')->updateAll(array('is_confirm_shiped'=>99),"is_confirm_shiped=0 and package_id = '{$pkId}' ");
   								continue;
   							}

                //var_dump($vvItem['order_id'],$vvItem['tracking_number']);

   							//添加详细日志
   							$eventLog = $aliLog->saveEventLog(AliexpressShipment::EVENT_UPLOAD_TRACK, array(
   									'log_id'        => $logID,
   									'account_id'    => $accountID,
   									'platform_order_id'  => $vvItem['platform_order_id'],
   									'order_id'      => $vvItem['order_id'],
   									'package_id'	=> $pkId,
   									'track_number'     => $vvItem['tracking_number'],
   									'carrier_name'  => $vvItem['carrier_name'],
   									'start_time'    => date('Y-m-d H:i:s'),
   							));
   		
   							//设置账号信息
   							$aliShipmentModel = new AliexpressShipment();
   							$aliShipmentModel->setAccountID($accountID);
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
                      'ship_code'     => $vvItem['ship_code'],
   										'paytime' => $vvItem['paytime'],
   										'status' => AliexpressOrderMarkShippedLog::STATUS_DEFAULT,
   										'type'	=> AliexpressOrderMarkShippedLog::TYPE_TRUE, //上传真实单号
   								);
   								$markModel = new AliexpressOrderMarkShippedLog();
   								$tmpMarkId = $markModel->saveNewData($markOrderData);
   							}else{
   								$tmpMarkId = $checkAdvanceShiped['id'];
   							}
   		
   							//初始化单个订单上传是否成功的标记
   							$flag = false;
   		
   							//获取当carrier为other时的追踪网址
   							$trackingWebsite = AliexpressShipment::getSellerShipmentInfoByServerName($vvItem['real_ship_type']);
   							//开始上传
   							$tmpModel = AliexpressOrderMarkShippedLog::model()->findByPk($tmpMarkId);
   							//if( $checkAdvanceShiped['order_id'] && $checkAdvanceShiped['status'] == AliexpressOrderMarkShippedLog::STATUS_SUCCESS && !in_array($vvItem['real_ship_type'],$excludeShipCode) && (time()-strtotime($checkAdvanceShiped['upload_time'])) <= 432000 ){ //之前有提前发货，并且提前发货时间距离现在不超过5日的，则调用修改声明发货接口
   								//准备上传数据-modify

                $flagSetConfirm = false; 
                $flagUpload = false;        #是否上传标记
                $flagModify = false;        #是否更新标记

                //1、未提前上传过，直接上传
                if( !$checkAdvanceShiped['order_id'] || $checkAdvanceShiped['status'] != AliexpressOrderMarkShippedLog::STATUS_SUCCESS) {
                  //echo 'AA<br/>';
                  //如无跟踪号，传假单号，其他情况直接上传真实跟踪号
                  if( in_array($checkAdvanceShiped['error_type'], array(0,96,97,5,127,999) ) 
                    || in_array($checkAdvanceShiped['update_error_type'], array(0,96,97,5,127,999) ) ){ //之前未提前声明发货或者失败，则直接上传真实跟踪号
                    //准备上传数据-first upload
                    //echo 'A1<br/>';
                    if( in_array($vvItem['real_ship_type'],$excludeShipCode) || empty($vvItem['tracking_number']) ){ 
                      break;
                      //获取假tn单号
/*                      $retRandTn = $this->getRandTrackNum(3);//取3次随机跟踪号，直到成功
                      if( !$retRandTn['ret'] )break;
                      $shippedData = array(
                          'serviceName' => 'CPAM',
                          'logisticsNo' => $retRandTn['trackVirtual'],
                          'outRef' => $vvItem['platform_order_id'],
                      );*/
                    }else{

                       $flagUpload = true;

                    }

                  }
                  else {
                    $flagSetConfirm = true;
                    echo 'DDD<br/>';
                  }

   							} else { 
                  //2、提前上传过，如未超时，需修改
                  //echo 'BB<br/>';
                  //成功上传且不在排除渠道且不超过5日
                  if ($checkAdvanceShiped['status'] == AliexpressOrderMarkShippedLog::STATUS_SUCCESS && !in_array($vvItem['real_ship_type'],$excludeShipCode) && (time()-strtotime($checkAdvanceShiped['upload_time'])) <= 864000) {
                    // echo 'A1<br/>';

                    $flagModify = true;

                  }else {
                    //$flagSetConfirm = true;
                  }
   									
   							}

                var_dump($vvItem['order_id'],$flagUpload,$flagModify,$flagSetConfirm);
                //echo '<br/>';

                if ($flagUpload == true) {
                  $shippedData = array(
                        'serviceName' => $vvItem['carrier_name'],
                        'logisticsNo' => $vvItem['tracking_number'],
                        'outRef' => $vvItem['platform_order_id'],
                        'trackingWebsite' => $trackingWebsite['website'],
                  );
    
                  $flag = $aliShipmentModel->uploadSellerShipment( $shippedData );//上传
                  $errorMessageSub = $aliShipmentModel->getExceptionMessage();
                  if($flag){ //上传成功
                    $updateData = array(
                        'id' => $tmpMarkId,
                        'status' => AliexpressOrderMarkShippedLog::STATUS_SUCCESS,
                        'upload_time' => date('Y-m-d H:i:s'),
                    );
                  }else{ //上传失败
                    //不满足更新条件
                    $updateData = array(
                        'id' => $tmpMarkId,
                        'status' => AliexpressOrderMarkShippedLog::STATUS_FAILURE,
                        'upload_time' => date('Y-m-d H:i:s'),
                        'errormsg' => $errorMessageSub,
                        'error_type' => $this->errorTypeMap(trim($aliShipmentModel->getErrorcode()),trim($aliShipmentModel->getExceptionMessage())),
                    );
                    $flagSetConfirm = true;
                  }
                  AliexpressOrderMarkShippedLog::model()->updateData( $tmpModel,$updateData );
                }

                if ($flagUpload == false && $flagModify == true) {
                  $shippedData = array(
                    'oldServiceName' => $checkAdvanceShiped['carrier_code'],
                    'oldLogisticsNo' => $checkAdvanceShiped['track_num'],
                    'newServiceName' => $vvItem['carrier_name'],
                    'newLogisticsNo' => $vvItem['tracking_number'],
                    'outRef' => $vvItem['platform_order_id'],
                    'trackingWebsite' => $trackingWebsite['website'],
                  );
                  $flag = $aliShipmentModel->modifySellerShipment( $shippedData );//上传
                  $errorMessageSub = $aliShipmentModel->getExceptionMessage();
                  
                  if($flag){ //更新成功
                    $updateData = array(
                      'id' => $tmpMarkId,
                      'track_num' => $shippedData['newLogisticsNo'],
                      'carrier_code'  => $shippedData['newServiceName'],
                      'update_status' => AliexpressOrderMarkShippedLog::UPDATE_STATUS_SUCCESS,
                      'update_time' => date('Y-m-d H:i:s'),
                      'package_id'  => $pkId,
                      'update_errormsg' => $errorMessageSub,
                    );
                  }else{
                    $updateData = array(
                      'id' => $tmpMarkId,
                      'track_num' => $shippedData['newLogisticsNo'],
                      'carrier_code'  => $shippedData['newServiceName'],
                      'update_status' => AliexpressOrderMarkShippedLog::UPDATE_STATUS_FAILURE,
                      'update_time' => date('Y-m-d H:i:s'),
                      'package_id'  => $pkId,
                      'update_errormsg' => $errorMessageSub,
                      'update_error_type' => $this->errorTypeMap(trim($aliShipmentModel->getErrorcode()),trim($aliShipmentModel->getExceptionMessage())),
                    );
                    $flagSetConfirm = true;
                  }
                  AliexpressOrderMarkShippedLog::model()->updateData( $tmpModel,$updateData );
                }

                if ($flagSetConfirm == true) {
                  //UebModel::model('OrderPackage')->updateAll(array('is_confirm_shiped'=>99),"is_confirm_shiped=0 and package_id = '{$pkId}' ");
                }
   		
   							if( $flag ){
   								//5.上传成功更新记录表
   								$aliLog->saveEventStatus(AliexpressShipment::EVENT_UPLOAD_TRACK, $eventLog, $aliLog::STATUS_SUCCESS);
   							}else{
   								$aliLog->saveEventStatus(AliexpressShipment::EVENT_UPLOAD_TRACK, $eventLog, $aliLog::STATUS_FAILURE,$errorMessageSub);
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
   						$aliLog->setSuccess($logID);
   					}else{
   						if( strlen($errorMessage)>1000 ) $errorMessage = substr($errorMessage,0,1000);
   						$aliLog->setFailure($logID, $errorMessage);
   					}
   				}
   		
   			}
   		}
   		
   	}

    /****************************************************************************************************************
     * 上传跟踪号异常处理  start
     ****************************************************************************************************************/

    /**
     * 同步数据至 oms   解决不能连库问题
     * @link  /aliexpress/aliexpressshipment/syncdatatooms
     */
    public function actionSyncdatatooms() {
    	ini_set('memory_limit','2000M');
    	set_time_limit('3600');
    	if($_REQUEST['id']){//初始化数据
    		$result = UebModel::model('AliexpressOrderMarkShippedLog')->getOrderLogById($_REQUEST['id']);
    	}else{
    		$result = UebModel::model('AliexpressOrderMarkShippedLog')->getOrderLog();

    	}
    	echo count($result);
    	if($result){
    		foreach($result as $list){
    			$newModel = UebModel::model('AliexpressOrderMarkShippedLogToOms')->findByPk($list['id']);
    			if(!$omsInfo){
    				$newModel = new AliexpressOrderMarkShippedLogToOms();
    			}
    			foreach($list as $key => $value){
    				$newModel->setAttribute($key,$value);
    			}
    			$flag = $newModel->save();
    			if($flag){
    				UebModel::model('AliexpressOrderMarkShippedLog')->updateByPk($list['id'], array('is_to_oms'=>1,'to_oms_time'=>date('Y-m-d H:i:s',time())));
    			}
    			var_dump($flag);	echo $list['id'].'<br>';
    		}
    		unset($result);
    	}else{
    		echo '暂时没有需要同步的数据';
    	}
    	
    	//echo '<pre>';print_r($result);
    	die;
    }

    /**
     * 可以不再处理的 errorType
     */
    private function canNotDealType() {
      return array(1,2,4,6,96);
    }

    /**
     * @desc ali返回的错误映射
     */
    public function errorTypeMap( $errorCode = '',$errorMsg = '' ){
    	$errorType = 0;
    	if( stripos($errorMsg, 'WAIT_BUYER_ACCEPT_GOODS') !== false ){
    		$errorType = 1; //订单状态为已发货，等待买家收货
    	}elseif( $errorMsg == 'error in validate:oldLogisticsNo cannot be modified' ){
    		$errorType = 2; //订单已经发货超过5天，不能修改发货信息
    	}elseif( stripos($errorMsg, 'status IN_CANCEL') !== false ){
    		$errorType = 3; //订单取消状态，不能确认发货
    	}elseif( stripos($errorMsg, 'in status FINISH') !== false ){
    		$errorType = 4; //订单已完成，不能确认发货
    	}elseif( stripos($errorMsg, 'Request need user authorized') !== false ){
    		$errorType = 5; //请求需要用户验证
    	}elseif( stripos($errorMsg, 'in status FUND_PROCESSING') !== false ){
    		$errorType = 6; //订单放款处理中...
    	}elseif(stripos($errorMsg, 'The number you entered is not a valid Tracking Number') !== false) {
        $errorType = 7; //跟踪号不正确
      }
    	
    	$errorCodeMapArr = array(
    			'15-2001' => 99, //运单号校验错误
    			'15-200'  => 98, //是后台报异常了 如订单不合法、传入的物流方式不合法、后台服务报异常、调用交易的发货接口异常等等
    			'15-1001' => 97, //物流服务名校验不通过
    			'15-1002' => 96, //老的运单号错误不符合修改规则
    			'15-1003' => 95, //该out_ref 订单号不是改用户的订单,修改订单号后重新调用
    	);
    	$errorType = empty($errorType)?$errorCodeMapArr[$errorCode]:$errorType;
    	return empty($errorType)?999:$errorType;
    }
    
    /**
     * @desc 取n次随机跟踪号，直到成功
     * @param $n integer 最多取几次
     */
/*    public function getRandTrackNum( $n=0 ){
    	$rs = false;
    	while(!$rs){//取3次随机跟踪号，直到成功
    		$n--;
    		$trackVirtual = AutoCode::getCode('fake_track_num');
    		$check = AliexpressOrderMarkShippedLog::model()->getInfoByTrackNum( $trackVirtual,'id' );
    		if(!$check['id']){
    			$rs = true;
    		}
    		if($n<=0) break;
    	}
    	return array('ret'=>$rs,'trackVirtual'=>$trackVirtual);
    }*/

    /**
     * @desc 上传跟踪号 http://erp_market.com/aliexpress/aliexpressshipment/uploadtracknumpretrack
     * @desc 多线程跑 http://erp_market.com/aliexpress/aliexpressshipment/uploadtracknumpretrack/type/1
     * @desc 1.上传已生成包裹、已有跟踪号的订单。  2.之前发过假单号的，付款时间不超过15天的，如果有真实tn则修改tn。 [只上传预匹配的渠道的跟踪号]
     * @author wx
     */
    public function actionUploadTrackNumPreTrack() {

      exit('CLOSE2');   #合并到现有里面

      //set_time_limit(3600);
      /* AliexpressOrderMarkShippedLog::model()->updateByPk('104801', array('errormsg'=>'','error_type'=>0));
       exit; */
/*      $limit = Yii::app()->request->getParam('limit', '');
      $type = Yii::app()->request->getParam('type', '');
      $packageId = Yii::app()->request->getParam('package_id', '');
      $pkCreateDate = date('Y-m-d',strtotime('-20 days'));*/
       
      /* $hand = Yii::app()->request->getParam('hand');
        
      if( !$hand ) exit('调试中...,稍后开启'); */
    
/*      if($type == 1){ //多线程
        $id = Yii::app()->request->getParam('id',-1);
        $threadNum = 10; //线程个数
        $totalCount = OrderPackage::model()->getAliWaitingUploadSpecialCount($pkCreateDate,$packageId);
        $count = ceil($totalCount['total']/$threadNum); //每条线程跑的个数
        if( $id >= 0 ){
          $this->executeUploadData($pkCreateDate, $packageId, $count, $id*$count, 2);
          $fileUrl = 'ali_shipped_log.txt';
          $fd = @fopen($fileUrl,'a+');
          @fwrite($fd,date('Y-m-d H:i:s')."线程:{$id},limit:{$count},offset:".$id*$count."\r\n");
          @fclose($fd);
        }else{
          for($i=0;$i<$threadNum;$i++){
            MHelper::runThreadSOCKET("/aliexpress/aliexpressshipment/uploadtracknumpretrack/type/1/id/".$i);
            echo '<br/>i='.$i;
            sleep(2);
          }
        }
      }else{ //单线程
        $this->executeUploadData($pkCreateDate, $packageId, '', '', 2);
      }*/
    }
    
}