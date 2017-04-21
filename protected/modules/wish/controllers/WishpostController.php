<?php
/**
 * Wish 邮　
 * @author	Rex
 * @since	2015-10-10
 */

class WishpostController extends UebController {
	
	/**
	 * Step1: 运单信息接收 
	 * @link	/wish/wishpost/createpostorder
	 * @author	Rex
	 * @since	2015-10-10
	 */
	public function actionCreatepostorder() {

		exit('Close');

/*		echo '<pre>';
		$packageID = $_GET['packageID'];
		$model = new WishShipment();
		$orderList = $model->getWishPostOrderList($packageID);
		var_dump(count($orderList));//exit();
		
		foreach ($orderList as $value) {			
			//$logID = WishLog::model()->prepareLog($value['account_id'], WishShipment::EVNET_POSTORDER);
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
			//处理来路
			$type = -1;
			if (in_array($packageInfo['ship_code'], array('ghxb_wish','ghxb_gz_wish')) ) {
				$type = 1;	# wish邮挂号
			}elseif (in_array($packageInfo['ship_code'], array('cm_wish','cm_gz_wish')) ) {
				$type = 0;	# wish邮平邮
			}
			if ($type < 0) {
				//跳过
				continue;
			}
			//处理分仓
			$warehouseCode = 0;
			if (in_array($packageInfo['ship_code'], array('ghxb_wish','cm_wish')) ) {
				$warehouseCode = 1;		#上海仓
			}elseif (in_array($packageInfo['ship_code'], array('cm_gz_wish','ghxb_gz_wish')) ) {
				$warehouseCode = 2;		#广州仓
			}
			if ($warehouseCode <= 0) {
				//跳过
				continue;
			}

			$recipientPhone = 0;
			if (!empty($packageInfo['ship_phone'])) {
				$recipientPhone = $packageInfo['ship_phone'];
			}
			$recipientPhone = str_replace(',', '', $recipientPhone);
			
			//组合数据
			$postOrderInfo = array(
					'guid'				=> $value['order_id'],
					'otype'				=> $type,
					'from'				=> '',		#Dragon.long
					'sender_province'	=> '',		#Guangdong
					'sender_city'		=> '',		#Shenzhen
					'sender_addres'		=> '',	#5th Floor B Buliding,DiGuang Digital Science And Technology Park
					'sender_phone'		=> '',	#13151613679
					'to'				=> $packageInfo['ship_name'],
					'recipient_country'	=> $packageInfo['ship_country_name'],
					'recipient_country_short' => $packageInfo['ship_country'],
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
					'user_desc'			=> $value['package_id'],
					'trande_no'			=> $value['platform_order_id'],	//平台订单号
					'trade_amount'		=> $value['total_price'],
					'warehouse_code'	=> $warehouseCode,	//分仓代码
					'doorpickup'		=> '0',
			);
			
			//荷兰国家处理  15.11.30 Rex
			if ($postOrderInfo['recipient_country'] == 'Netherlands') {
				$postOrderInfo['recipient_country'] = 'Netherland';
			}
			//俄罗斯国家处理	16.5.20 Rex
			if ($postOrderInfo['recipient_country'] == 'Russian Federation') {
				$postOrderInfo['recipient_country'] = 'Russia';
			}

			//var_dump($postOrderInfo);//exit();
			
			$data2 = array(
					'mark'		=> '',
					'bid'		=> '',
					'order'		=> $postOrderInfo
			);
			
			//保存日志
			$eventLog = WishLog::model()->saveEventLog(WishShipment::EVNET_POSTORDER, array(
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
					//WishLog::model()->saveEventStatus(WishShipment::EVNET_POSTORDER, $logID, WishLog::STATUS_SUCCESS);
				}elseif ($xmlContent->error_message['guid'] == $value['order_id']) {
					//WishLog::model()->updateLogData(WishShipment::EVNET_POSTORDER, $eventLog, array('response_msg'=>$xmlContent->error_message));
				}
			}elseif ($xmlContent->status == 2) {
				$message = $xmlContent->xpath('error-message');
				$message = trim($message[0]);
				var_dump($packageInfo['package_id'],$message);
				UebModel::model('OrderPackageTrackLog')->updateByPk($trackLogId, array('status'=>OrderPackageTrackLog::STATUS_FAIL,'return_result'=>$message));
			}
			
		}
		
		echo 'OK';*/
		
	}
	
	/**
	 * 查询运单跟踪信息
	 * @link	/wish/wishpost/posttrackinfo
	 * @author	Rex
	 * @since	2015-10-14
	 */
/*	public function actionPosttrackinfo() {
		$trackNo = '01623365385';
		
		$data2 = array(
				'language'	=> 'cn',
				'track'	=> array('barcode'=>$trackNo)			
		);
		
		$request = new PostTrackRequest();
		$data = $request->getXmlData($data2, 'tracks');
		$ret = $request->_curlPost($data);
		var_dump($ret);
	}*/
	
}