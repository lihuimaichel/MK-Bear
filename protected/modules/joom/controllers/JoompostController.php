<?php
/**
 * Joom 邮　
 * @author	Rex
 * @since	2015-10-10
 */

class JoompostController extends UebController {
	
	/**
	 * Step1: 运单信息接收 
	 * @link	/joom/joompost/createpostorder
	 * @author	Rex
	 * @since	2015-10-10
	 */
	public function actionCreatepostorder() {
		echo '<pre>';
		$packageID = $_GET['packageID'];
		$model = new JoomShipment();
		$orderList = $model->getJoomPostOrderList($packageID);
		var_dump(count($orderList));//exit();
		
		foreach ($orderList as $value) {			
			//$logID = JoomLog::model()->prepareLog($value['account_id'], JoomShipment::EVNET_POSTORDER);
			$logID = 1;
			
			$packageDetailList = OrderPackageDetail::model()->getPackageDetailListByOrderID($value['order_id']);
			$detailInfo = array();
			foreach ($packageDetailList as $value2) {
				$detailInfo['package_id'] = $value2['package_id'];
				$detailInfo['sku'] = $value2['sku'];
				$detailInfo['quantity'] = $value2['quantity'];
				$detailInfo['info'] = UebModel::model('Product')->getBySku($value2['sku']);
				$detailInfo['sku_desc_en'] = UebModel::model('Productdesc')->getDescriptionInfoBySkuAndLanguageCode($value2['sku'], 'english');
				$detailInfo['sku_desc_en'] = str_replace('&', '&amp;', $detailInfo['sku_desc_en']);
				break;
			}
			
			$packageInfo = OrderPackage::model()->getPackageInfoByPackageID($value['package_id']);
			
			$request = new PostOrderRequest();
			$isGh = 0;
			if ($packageInfo['ship_code'] == 'ghxb_joom') {
				$isGh = 1;
			}
			
			$recipientPhone = 0;
			if (!empty($packageInfo['ship_phone'])) {
				$recipientPhone = $packageInfo['ship_phone'];
			}
			$recipientPhone = str_replace(',', '', $recipientPhone);
			strlen($recipientPhone) > 15 && $recipientPhone = substr($recipientPhone, 15);
			
			//组合数据
			$postOrderInfo = array(
					'guid'				=> $value['order_id'],
					'otype'				=> $isGh,		//是否挂号件
					'from'				=> '',		
					'sender_province'	=> '',		
					'sender_city'		=> '',		
					'sender_addres'		=> '',
					'sender_phone'		=> '',
					'to'				=> $packageInfo['ship_name'],
					'recipient_country'	=> $packageInfo['ship_country_name'],
					'recipient_province'=> empty($packageInfo['ship_stateorprovince']) ? $packageInfo['ship_city_name'] : $packageInfo['ship_stateorprovince'],
					'recipient_city'	=> $packageInfo['ship_city_name'],
					'recipient_addres'	=> $packageInfo['ship_street1'].' '.$packageInfo['ship_street2'],
					'recipient_postcode'=> $packageInfo['ship_zip'],
					'recipient_phone'	=> $recipientPhone,
					'to_local'			=> '',
					'recipient_country_local' => '',
					'recipient_province_local'=> '',
					'recipient_city_local'	=> '',
					'recipient_addres_local'=> '',
					'type_no'			=> 4,	//内件类型错码　1礼品，2文件，3商品货样，4其他
					'from_country'		=> 'China',	//货物原产国(英文)
					'content'			=> $detailInfo['sku_desc_en']['title'],	//内件物品详细名称(英文)
					'num'				=> $detailInfo['quantity'],
					'weight'			=> $detailInfo['info']->product_weight / 1000,
					'single_price'		=> $value['total_price'],	//货品申报价值
					'user_desc'			=> $detailInfo['sku_desc_en']['title'],
					'trande_no'			=> $value['platform_order_id'],	//平台订单号
					'trade_amount'		=> $value['total_price'],
			);
			
			//荷兰国家处理  15.11.30 Rex
			if ($postOrderInfo['recipient_country'] == 'Netherlands') {
				$postOrderInfo['recipient_country'] = 'Netherland';
			}
			
			$data2 = array(
					'mark'		=> '',
					'bid'		=> '',
					'order'		=> $postOrderInfo
			);
			
			//保存日志
			$eventLog = JoomLog::model()->saveEventLog(JoomShipment::EVNET_POSTORDER, array(
					'log_id'        	=> $logID,
					'account_id'   	 	=> $value['account_id'],
					'order_id'    		=> $value['order_id'],
					'otype'				=> $postOrderInfo['otype'],
					'to'				=> $postOrderInfo['to'],
					'recipient_country'	=> $postOrderInfo['recipient_country'],
					'recipient_province'=> $postOrderInfo['recipient_province'],
					'recipient_city'	=> $postOrderInfo['recipient_city'],
					'recipient_addres'	=> $postOrderInfo['recipient_addres'],
					'recipient_postcode'=> $postOrderInfo['recipient_postcode'],
					'recipient_phone'	=> $postOrderInfo['recipient_phone'],
					'to_local'			=> $postOrderInfo['to_local'],
					'recipient_country_local' => $postOrderInfo['recipient_country_local'],
					'recipient_province_local'=> $postOrderInfo['recipient_province_local'],
					'recipient_city_local'	=> $postOrderInfo['recipient_city_local'],
					'recipient_addres_local'  => $postOrderInfo['recipient_addres_local'],
					'type_no'			=> $postOrderInfo['type_no'],
					'from_country'		=> $postOrderInfo['from_country'],
					'content'			=> $postOrderInfo['content'],
					'num'				=> $postOrderInfo['num'],
					'weight'			=> $postOrderInfo['weight'],
					'single_price'		=> $postOrderInfo['single_price'],
					'trande_no'			=> $postOrderInfo['trande_no'],
					'trade_amount'		=> $postOrderInfo['trade_amount'],
					'create_time'		=> date('Y-m-d H:i:s'),
					'create_user_id'	=> Yii::app()->user->id
			));
			
			$trackLogId = UebModel::model('OrderPackageTrackLog')->addNewData(array('package_id'=>$packageInfo['package_id'],'ship_code'=>$packageInfo['ship_code'],'note'=>'prepare upload'));	
			
			$data = $request->getXmlData($data2, 'orders');
			$retXml = $request->_curlPost($data);
			
			$xmlContent = simplexml_load_string($retXml);
			 
			if ($_GET['isTest']) {
				var_dump($data2,$data,$xmlContent);
				exit();
			}
			
			if ($xmlContent->status == 0) {
				if ($xmlContent->barcode['guid'] == $value['order_id']) {
					UebModel::model('OrderPackageTrackLog')->updateByPk($trackLogId, array('response_time'=>date('Y-m-d H:i:s'), 'return_result'=>serialize(trim($xmlContent->barcode)), 'note'=>'response OK'));
					$platfromTrackNo = trim($xmlContent->barcode);
					//保存创建日志
					$createInfo = array(
							'order_id'		=> $value['order_id'],
							'platform_code'	=> $value['platform_code'],
							'account_id'	=> $value['account_id'],
							'track_num'		=> '',
							'ship_code'		=> $packageInfo['ship_code'],
							'package_id'	=> $packageInfo['package_id'],
							'platform_track_num'	=> $platfromTrackNo,
							'create_time'	=> date('Y-m-d H:i:s'),
							'create_user_id'=> empty(Yii::app()->user->id) ? 0 : Yii::app()->user->id
					);
					OrderCreateOnline::model()->saveCreateInfo($createInfo);
					//更新包裹跟踪号
					$ret = OrderPackage::model()->updateByPk($packageInfo['package_id'], array('track_num'=>$platfromTrackNo,'upload_ship'=>1,'upload_time'=>date('Y-m-d H:i:s')));
					
					if ($ret) {
						UebModel::model('OrderPackageTrackLog')->updateByPk($trackLogId, array('status'=>OrderPackageTrackLog::STATUS_OK));
					}
					//JoomLog::model()->saveEventStatus(JoomShipment::EVNET_POSTORDER, $logID, JoomLog::STATUS_SUCCESS);
				}elseif ($xmlContent->error_message['guid'] == $value['order_id']) {
					//JoomLog::model()->updateLogData(JoomShipment::EVNET_POSTORDER, $eventLog, array('response_msg'=>$xmlContent->error_message));
				}
			}elseif ($xmlContent->status == 2) {
				$message = trim($xmlContent->error_message);
				var_dump($packageInfo['package_id'],$message);
				UebModel::model('OrderPackageTrackLog')->updateByPk($trackLogId, array('status'=>OrderPackageTrackLog::STATUS_FAIL,'return_result'=>$message));
			}
			
		}
		
		echo 'OK';
		
	}
	
	/**
	 * 查询运单跟踪信息
	 * @link	/joom/joompost/posttrackinfo
	 * @author	Rex
	 * @since	2015-10-14
	 */
	public function actionPosttrackinfo() {
		$trackNo = '01623365385';
		
		$data2 = array(
				'language'	=> 'cn',
				'track'	=> array('barcode'=>$trackNo)			
		);
		
		$request = new PostTrackRequest();
		$data = $request->getXmlData($data2, 'tracks');
		$ret = $request->_curlPost($data);
		var_dump($ret);
	}
	
}