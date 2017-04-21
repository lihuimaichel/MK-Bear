<?php
/**
 * @desc amazon 订单模型
 * @author yangsh
 *
 */
class AmazonOrderMain extends AmazonModel {

	/** @var array 订单数据*/
	public $orderResponse = null;
	
	/** @var int 账号ID*/
	public $_accountID = null;

	/** @var string 账号对应站点 */
	public $_accountSite = '';

	/** @var string 异常信息*/
	public $exception = null;
	
	/** @var int 日志编号*/
	public $_logID = 0;	

	public $shipCountry = "";
	public $shipCountryName = "";

	/** @var 拉单状态*/
	private $_orderStatus = null;

	private $_fulfillmentChannel = null;

	private $_mode = 2;//1:CreatedAfter 2:LastUpdatedAfter

	private $_fromCode = '';

    /** @var int is_downdetail*/
    const IS_DOWNDETAIL_YES = 1;
    const IS_DOWNDETAIL_NO  = 0;	

    const TO_OMS_YES        = 1; #已成功
    const TO_OMS_NO         = 0; #未同步    

    const GET_ORDERNO_ERRNO = 1000;//获取订单号异常编号
	
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}
	
	/**
	 * @desc 数据库表名
	 * @see CActiveRecord::tableName()
	 */
	public function tableName() {
		return 'ueb_amazon_order_main';
	}

	/**
	 * @desc 设置账号ID
	 */
	public function setAccountID($accountID){
		$accountInfo = AmazonAccount::model()->findByPk($accountID);
		if($accountInfo){
			$this->_accountSite = strtoupper($accountInfo->country_code);
		}
		$this->_accountID = $accountID;
	}	

	/**
	 * @desc 设置TimeArr
	 * @param string $eventName
	 * @return array
	 */
	public function getTimeArr($eventName) {
		$lastLog = AmazonLog::model()->getLastLogByCondition(array(
				'account_id' => $this->_accountID,
				'event'      => $eventName,
				'status'     => AmazonLog::STATUS_SUCCESS,
		));
		//事件记录UTC时间
		$eventLog = null;
		if($lastLog){
			$eventLog = AmazonLog::model()->getEventLogListByLogID($eventName, $lastLog['id']);
		}
		//参数时间不能比提交时间晚，且必须是提交时间前两分钟
		if (!empty($eventLog)) {
			if ( time() - strtotime($eventLog['end_time'])  > 3*86400 ) {//超过3天，默认拉3天
				$startTime = time() - 3 * 86400;
			} else {
				$startTime = strtotime($eventLog['end_time']) - 15*60;
			}
		} else {//第一次默认拉3天
			$startTime = time() - 3 * 86400;
		}
		$timeArr = array(
			'start_time' => date("Y-m-d H:i:s", $startTime),//UTC
			'end_time'   => date('Y-m-d H:i:s', time() - 8*3600 - 5*60 ),//转utc时间
		);
		return $timeArr;
	}

	/**
	 * @desc 设置日志编号
	 * @param int $logID
	 */
	public function setLogID($logID){
		$this->_logID = $logID;
	}	

	public function setFromCode($fromCode) {
		$this->_fromCode = $fromCode;
	}

	public function setMode($mode) {
		$this->_mode = $mode;
	}	

    /**
	 * @desc 获取异常信息
	 * @return string
	 */
	public function getExceptionMessage(){
		return $this->exception;
	}
	
	/**
	 * @desc 设置异常信息
	 * @param string $message
	 */
	public function setExceptionMessage($message){
		$this->exception = $message;
	}	

	/**
	 * @desc 设置拉单类型（补拉pull_up_order，正常getorder）
	 * @param string $type
	 */
	public function setPullOrderType($type = 'getorder'){
		$this->_pullOrderType = $type;
	}

	/**
	 * @desc 设置订单状态
	 * @param array $orderStatus
	 */
	public function setOrderStatus($orderStatus = array()){
		$this->_orderStatus = $orderStatus;
	}

	/**
	 * @desc 设置配送方式
	 * @param string $fillmentChannel
	 */
	public function setFulfillmentChannel($fillmentChannel){
		$this->_fulfillmentChannel = $fillmentChannel;
	}	

	/**
	 * @desc 获取amazon订单方法
	 */
	public function getOrders($eventName,$timeArr) {
		try {
			$path = 'amazon/getOrders/'.date("Ymd").'/'.$this->_accountID.'/'.date("H");
			$accountID = $this->_accountID;
			$request = new ListOrdersRequest();

			//时间条件
			$startTime = date('Y-m-d\TH:i:s\Z', strtotime($timeArr['start_time']));
			if ($this->_mode == 2) {//按更新时间
				$request->setStartUpdateTime($startTime);
				if(!empty($timeArr['end_time'])) {
					$request->setEndUpdateTime(date('Y-m-d\TH:i:s\Z', strtotime($timeArr['end_time'])) );
				}
			} else {//按创建时间
				$request->setStartTime($startTime);
				if(!empty($timeArr['end_time'])){
					$request->setEndTime(date('Y-m-d\TH:i:s\Z', strtotime($timeArr['end_time'])) );
				}
			}

			//配货渠道
			if($this->_fulfillmentChannel != ''){
				$request->setFulfillmentChannel( strtoupper($this->_fulfillmentChannel) );
			}

			//订单状态
			if($this->_orderStatus){
				$request->setOrderStatus($this->_orderStatus);
			}

			$request->setCaller($eventName);
			$response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
			//MHelper::writefilelog($path.'/response.log', print_r($response,true)."\r\n");// fortest
			if (empty($response)) {
				$this->setExceptionMessage($request->getErrorMsg());
				return false;
			}
			return $this->saveOrderMain($response);
		} catch (Exception $e) {
			$this->setExceptionMessage($e->getMessage());
			return false;
		}	
	}

	/**
	 * @desc 获取Amazon订单商品信息
	 * @param string $eventName
	 * @param string $amazonOrderID
	 * @return boolean|multitype:multitype: unknown
	 */
	public function getOrderItems($eventName,$amazonOrderID) {
		try {
			$path = 'amazon/getOrderItems/'.date("Ymd").'/'.$this->_accountID.'/'.date("H");
			$request = new ListOrderItemsRequest();
			$request->setCaller($eventName);
			$request->setOrderId($amazonOrderID);
			$response = $request->setAccount($this->_accountID)->setRequest()->sendRequest()->getResponse();
			//MHelper::writefilelog($path.'/response.log', print_r($response,true)."\r\n");// fortest
			if (empty($response) || empty($response[0])) {
				$this->setExceptionMessage($request->getErrorMsg().": {$amazonOrderID}");
				return false;
			}
			//@desc 识别到部分发货会有二维数组出现  
			//@notice add by lihy  in 2016-01-11
			$newResponse = array();
			foreach ($response as $k=>$res){
				if(empty($res['SellerSKU']) && is_array($res[0])){
					$newResponse = array_merge($newResponse, $res);
				}else{
					$newResponse[] = $res;
				}
			}
			if(!empty($_REQUEST['debug'])) {
				MHelper::printvar($newResponse,false);
			}
			unset($response, $res);
			return $this->saveOrderDetailData($amazonOrderID,$newResponse);
		} catch (Exception $e) {
			$this->setExceptionMessage($e->getMessage());
			return false;
		}		
	}

	/**
	 * @desc 保存拉单信息
	 * @param array $orderDatas
	 * @return boolean
	 */
	public function saveOrderMain($orderDatas) {
		$errorMsg = '';
		foreach ($orderDatas as $orderData) {		
			try{
				if(!is_array($orderData) || !isset($orderData['AmazonOrderId']) || trim($orderData['AmazonOrderId']) == '') {
					throw new Exception("AmazonOrderId is empty");
				}
				$platformOrderID = trim($orderData['AmazonOrderId']);
				if (isset($orderData['ShippingAddress'])) {
					//mysql编码格式utf-8格式，不支持带四字节的字符串插入
			        $shipName = isset($orderData['ShippingAddress']['Name']) ? trim($orderData['ShippingAddress']['Name']) : '';
			        if ($shipName != '' && preg_match('/[\x{10000}-\x{10FFFF}]/u',$shipName) ) {
			            $shipName = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $shipName);
			        }

			        $address1 = isset($orderData['ShippingAddress']['AddressLine1']) ? trim($orderData['ShippingAddress']['AddressLine1']) : '';
			        if ($address1 != '' && preg_match('/[\x{10000}-\x{10FFFF}]/u',$address1) ) {
			            $address1 = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $address1);
			        }  

			        $address2 = isset($orderData['ShippingAddress']['AddressLine2']) ? trim($orderData['ShippingAddress']['AddressLine2']) : '';
			        if ($address2 != '' && preg_match('/[\x{10000}-\x{10FFFF}]/u',$address2) ) {
			            $address2 = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $address2);
			        }

			        $address3 = isset($orderData['ShippingAddress']['AddressLine3']) ? trim($orderData['ShippingAddress']['AddressLine3']) : '';
			        if ($address3 != '' && preg_match('/[\x{10000}-\x{10FFFF}]/u',$address3) ) {
			            $address3 = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $address3);
			        }

			        $phone = isset($orderData['ShippingAddress']['Phone']) ? trim($orderData['ShippingAddress']['Phone']) : '';
			        
			        $postalCode = isset($orderData['ShippingAddress']['PostalCode']) ? trim($orderData['ShippingAddress']['PostalCode']) : '';
			        
			        $city = isset($orderData['ShippingAddress']['City']) ? trim($orderData['ShippingAddress']['City']) : '';
			        
			        $county = isset($orderData['ShippingAddress']['County']) ? trim($orderData['ShippingAddress']['County']) : '';
			        
			        $district = isset($orderData['ShippingAddress']['District']) ? trim($orderData['ShippingAddress']['District']) : '';
			        
			        $stateOrRegion = isset($orderData['ShippingAddress']['StateOrRegion']) ? trim($orderData['ShippingAddress']['StateOrRegion']) : '';
			        
			        $countryCode = isset($orderData['ShippingAddress']['CountryCode']) ? trim($orderData['ShippingAddress']['CountryCode']) : '';
				} else {
					$shipName = $address1 = $address2 = $address3 = $phone = $postalCode = '';
					$city = $county = $district = $stateOrRegion = $countryCode = '';
				}

		        //组装数据
				$formatData = array(
					'amazon_order_id'                         => $platformOrderID,
					'log_id'                                  => $this->_logID,
					'account_id'                              => $this->_accountID,
					'seller_order_id'                         => isset($orderData['SellerOrderId']) 
																	? trim($orderData['SellerOrderId']) : '',
					'purchase_date'                           => self::transformUTCTimeFormat($orderData['PurchaseDate']),
					'last_update_date'                        => self::transformUTCTimeFormat($orderData['LastUpdateDate']),
					'order_status'                            => trim($orderData['OrderStatus']),
					'fulfillment_channel'                     => trim($orderData['FulfillmentChannel']),
					'sales_channel'                           => isset($orderData['SalesChannel']) 
																	? trim($orderData['SalesChannel']) : '',
					'order_channel'                           => isset($orderData['OrderChannel']) 
																	? trim($orderData['OrderChannel']) : '',
					'ship_service_level'                      => isset($orderData['ShipServiceLevel']) 
																	? trim($orderData['ShipServiceLevel']) : '',
					'shipping_address_name'                   => $shipName,
					'shipping_address_address_line1'          => $address1,
					'shipping_address_address_line2'          => $address2,
					'shipping_address_address_line3'          => $address3,
					'shipping_address_city'                   => $city,
					'shipping_address_county'                 => $county,
					'shipping_address_district'               => $district,
					'shipping_address_state_or_region'        => $stateOrRegion,
					'shipping_address_postal_code'            => $postalCode,
					'shipping_address_country_code'           => $countryCode,
					'shipping_address_phone'                  => $phone,
					'order_total_currency_code'               => isset($orderData['OrderTotal'])
																	? trim($orderData['OrderTotal']['CurrencyCode']) : '',
					'order_total_amount'                      => isset($orderData['OrderTotal']) 
																	?floatval($orderData['OrderTotal']['Amount']) : 0,
					'number_of_items_shipped'                 => intval($orderData['NumberOfItemsShipped']),
					'number_of_items_unshipped'               => intval($orderData['NumberOfItemsUnshipped']),
					'payment_execution_detail'        		  => isset($orderData['PaymentExecutionDetail'])	
																	 ? json_encode($orderData['PaymentExecutionDetail']):'[]',
					'payment_method'                          => isset($orderData['PaymentMethod']) 
																	? trim($orderData['PaymentMethod']) : '',
					'marketplace_id'                          => $orderData['MarketplaceId'],
					'buyer_email'                             => isset($orderData['BuyerEmail']) 
																	? trim($orderData['BuyerEmail']) : '',
					'buyer_name'                              => isset($orderData['BuyerName']) 
																	? trim($orderData['BuyerName']) : '',
					'shipment_serviceLevel_category'          => isset($orderData['ShipmentServiceLevelCategory']) 
																	? $orderData['ShipmentServiceLevelCategory'] : '',
					'shipped_by_amazon_tfm'                   => isset($orderData['ShippedByAmazonTFM']) 
																	? intval($orderData['ShippedByAmazonTFM']) : -1,
					'tfm_shipment_status'                     => isset($orderData['TFMShipmentStatus']) 											? $orderData['TFMShipmentStatus'] : '',
					'cba_displayable_shipping_label'          => isset($orderData['CbaDisplayableShippingLabel']) 
																	? $orderData['CbaDisplayableShippingLabel'] : '',
					'ordertype'                               => $orderData['OrderType'],
					'earliest_ship_date'                      => isset($orderData['EarliestShipDate']) 
																	? self::transformUTCTimeFormat($orderData['EarliestShipDate']) : '0000-00-00 00:00:00',
					'latest_ship_date'                        => isset($orderData['LatestShipDate']) 
																	? self::transformUTCTimeFormat($orderData['LatestShipDate']): '0000-00-00 00:00:00',
					'earliest_delivery_date'                  => isset($orderData['EarliestDeliveryDate']) 
																	? self::transformUTCTimeFormat($orderData['EarliestDeliveryDate']): '0000-00-00 00:00:00',
					'latest_delivery_date'                    => isset($orderData['LatestDeliveryDate']) 
																	? self::transformUTCTimeFormat($orderData['LatestDeliveryDate']): '0000-00-00 00:00:00',
					'is_business_order'                       => isset($orderData['IsBusinessOrder']) 
																	? intval($orderData['IsBusinessOrder']) : -1,
					'is_prime'                                => isset($orderData['IsPrime']) 
																	? intval($orderData['IsPrime']) : -1,
					'is_premium_order'                        => isset($orderData['IsPremiumOrder']) 
																	? intval($orderData['IsPremiumOrder']) : -1,
				);
				
				//平台订单号+账号id 取记录 add in 20170213
				$info = $this->getOneByCondition('id,order_status,order_total_amount',"amazon_order_id='{$platformOrderID}' and account_id={$this->_accountID} ");
				if (!empty($info)) {
					if ($info['order_status'] != $formatData['order_status']
					 || $info['order_total_amount'] != $formatData['order_total_amount'] ) {
						$formatData['is_downdetail'] = 0;
						$formatData['from_code'] = $this->_fromCode;
					}

					$formatData['updated_time'] = date('Y-m-d H:i:s');
					$this->getDbConnection()->createCommand()->update($this->tableName(), $formatData, "id={$info['id']}");
				} else {
					$formatData['created_time'] = date('Y-m-d H:i:s');
					$formatData['is_downdetail'] = 0;
					$formatData['from_code'] = $this->_fromCode;
					$this->getDbConnection()->createCommand()->insert($this->tableName(), $formatData);
				}
			}catch (Exception $e){
				$errorMsg .= $platformOrderID.' @@ '.$e->getMessage().' ## ';
			}
		}
		if ($errorMsg != '') {
			throw new Exception($errorMsg);
		}
		return true;
	}

	/**
	 * @desc 保存拉单明细信息
	 * @param string $amazonOrderID
	 * @param array $detailDatas
	 * @return boolean
	 */
	public function saveOrderDetailData($amazonOrderID,$detailDatas) {
		$model = new AmazonOrderDetail();
		$dbTransaction = $model->dbConnection->beginTransaction();//开启事务
		try{
			//平台订单号+账号id 删除记录 add in 20170213
			$model->deleteAll("amazon_order_id='{$amazonOrderID}' and account_id={$this->_accountID} ");
			foreach ($detailDatas as $detail) {
				//mysql编码格式utf-8格式，不支持带四字节的字符串插入
				$title = isset($detail['Title']) ? trim($detail['Title']) : '';
		        if ($title != '' && preg_match('/[\x{10000}-\x{10FFFF}]/u',$title) ) {
		            $title = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $title);
		        }				
				$formatData = array(
					'account_id'                       => $this->_accountID,
					'amazon_order_id'                  => $amazonOrderID,
					'asin'                             => $detail['ASIN'],
					'seller_sku'                       => $detail['SellerSKU'],
					'order_item_id'                    => $detail['OrderItemId'],
					'title'                            => $title,
					'quantity_ordered'                 => $detail['QuantityOrdered'],
					'quantity_shipped'                 => isset($detail['QuantityShipped']) ? $detail['QuantityShipped'] : 0,
					'item_price_currency_code'         => isset($detail['ItemPrice']['CurrencyCode']) ? $detail['ItemPrice']['CurrencyCode'] : '',
					'item_price_amount'                => isset($detail['ItemPrice']['Amount']) ? $detail['ItemPrice']['Amount'] : 0,
					'shipping_price_currency_code'     => isset($detail['ShippingPrice']['CurrencyCode']) ?$detail['ShippingPrice']['CurrencyCode'] : '',
					'shipping_price_amount'            => isset($detail['ShippingPrice']['Amount']) ? $detail['ShippingPrice']['Amount'] : 0,
					'gift_wrap_price_currency_code'    => isset($detail['GiftWrapPrice']['CurrencyCode']) ? $detail['GiftWrapPrice']['CurrencyCode'] : '',
					'gift_wrap_price_amount'           => isset($detail['GiftWrapPrice']['Amount']) ? $detail['GiftWrapPrice']['Amount'] : 0,
					'item_tax_currency_code'           => isset($detail['ItemTax']['CurrencyCode']) ? $detail['ItemTax']['CurrencyCode'] : '',
					'item_tax_amount'                  => isset($detail['ItemTax']['Amount']) ? $detail['ItemTax']['Amount'] : 0,
					'shipping_tax_currency_code'       => isset($detail['ShippingTax']['CurrencyCode']) ? $detail['ShippingTax']['CurrencyCode'] : '',
					'shipping_tax_amount'              => isset($detail['ShippingTax']['Amount']) ? $detail['ShippingTax']['Amount'] : 0,
					'gift_wrap_tax_currency_code'      => isset($detail['GiftWrapTax']['CurrencyCode']) ? $detail['GiftWrapTax']['CurrencyCode'] : '',
					'gift_wrap_tax_amount'             => isset($detail['GiftWrapTax']['Amount']) ? $detail['GiftWrapTax']['Amount'] : 0,
					'shipping_discount_currency_code'  => isset($detail['ShippingDiscount']['CurrencyCode']) ? $detail['ShippingDiscount']['CurrencyCode'] : '',
					'shipping_discount_amount'         => isset($detail['ShippingDiscount']['Amount']) ? $detail['ShippingDiscount']['Amount'] : 0,
					'promotion_discount_currency_code' => isset($detail['PromotionDiscount']['CurrencyCode']) ? $detail['PromotionDiscount']['CurrencyCode'] : '',
					'promotion_discount_amount'        => isset($detail['PromotionDiscount']['Amount']) ? $detail['PromotionDiscount']['Amount'] : 0,
					'promotion_ids'                    => isset($detail['PromotionIds']) ? json_encode($detail['PromotionIds']) : '',
					'cod_fee_currency_code'            => isset($detail['CODFee']['CurrencyCode']) ? $detail['CODFee']['CurrencyCode'] : '',
					'cod_fee_amount'                   => isset($detail['CODFee']['Amount']) ? $detail['CODFee']['Amount'] : 0,
					'cod_fee_discount_currency_code'   => isset($detail['CODFeeDiscount']['CurrencyCode']) ? $detail['CODFeeDiscount']['CurrencyCode'] : '',
					'cod_fee_discount_amount'          => isset($detail['CODFeeDiscount']['Amount']) ? $detail['CODFeeDiscount']['Amount'] : 0,
					'gift_message_text'                => isset($detail['GiftMessageText']) ? $detail['GiftMessageText'] : '',
					'gift_wrap_level'                  => isset($detail['GiftWrapLevel']) ? $detail['GiftWrapLevel'] : '',
					'invoice_data'                     => isset($detail['InvoiceData']) ? json_encode($detail['InvoiceData']):'',
					'condition_note'                   => isset($detail['ConditionNote']) ? $detail['ConditionNote'] : '',
					'condition_id'                     => isset($detail['ConditionId']) ? $detail['ConditionId'] : '',
					'condition_subtype_id'             => isset($detail['ConditionSubtypeId']) ? $detail['ConditionSubtypeId'] : '',
					'scheduled_delivery_start_date'    => isset($detail['ScheduledDeliveryStartDate']) 
															? self::transformUTCTimeFormat($detail['ScheduledDeliveryStartDate']) : '0000-00-00 00:00:00',
					'scheduled_delivery_end_date'      => isset($detail['ScheduledDeliveryEndDate']) 
															? self::transformUTCTimeFormat($detail['ScheduledDeliveryEndDate']) : '0000-00-00 00:00:00',
					'created_time'                     => date('Y-m-d H:i:s'),
				);
				//插入记录
				$model->getDbConnection()->createCommand()->insert($model->tableName(), $formatData);
			}	

			//更新主表标识已下载, 按平台订单号+账号id 更新记录 add in 20170213
			$this->getDbConnection()->createCommand()->update($this->tableName(), 
					array('is_downdetail'=>1), "amazon_order_id='{$amazonOrderID}' and account_id={$this->_accountID} ");

			$dbTransaction->commit();
			return true;
		} catch(Exception $e) {
			$dbTransaction->rollback();
			throw new Exception($e->getMessage());
		}
	}	

	/**
	 * @desc 获取可同步OMS系统的订单状态
	 * @return array
	 */
	public static function getAbleSyncOrderStatus() {
		return array(
			ListOrdersRequest::ORDER_STATUS_UNSHIPPED, 
			ListOrdersRequest::ORDER_STATUS_PARTIALLY_SHIPPED,
			ListOrdersRequest::ORDER_STATUS_SHIPPED,
		);
	}

    /**
     * @desc 同步订单到oms系统
     * @param unknown $accountID
     * @param number $limit
     * @return number
     */
    public function syncOrderToOmsByAccountID($accountID, $limit = 500, $platformOrderID=null){
        $ctime = date('Y-m-d',strtotime('-14 days'));
    	$cmd = $this->getDbConnection()->createCommand()
					->from($this->tableName())
					->where("account_id=:account_id", array(":account_id"=>$accountID))
					->andWhere("is_to_oms=".self::TO_OMS_NO)
	                ->andWhere(array('in','order_status',self::getAbleSyncOrderStatus()) )
	                ->andWhere("is_downdetail=:is_downdetail",array(':is_downdetail'=>self::IS_DOWNDETAIL_YES))
	                ->andWhere("purchase_date>'{$ctime}'")
					->limit($limit);

        if (!empty($platformOrderID) ) {
            $cmd->andWhere("amazon_order_id like '%{$platformOrderID}'");
        }
		//echo $cmd->getText();
    	$orderList = $cmd->queryAll();
    	if($orderList){
    		$getOrderNoFailureFlag = array();//获取订单号失败超过10次则退出程序
    		$syncTotal = 0;
    		$errorMsg = "";
    		foreach ($orderList as $order){
    			try{
	    			$this->saveOrderData($order);
	    			$syncTotal++;

	    			//更改本地状态
	    			$this->getDbConnection()->createCommand()
	    									->update($this->tableName(), array(
		    										'is_to_oms'=>self::TO_OMS_YES, 
		    										'to_oms_time'=>date("Y-m-d H:i:s")
	    										), "id={$order['id']}");
    			}catch (Exception $e){
    				if (self::GET_ORDERNO_ERRNO == $e->getCode() ) {//获取订单号异常
                        $getOrderNoFailureFlag[] = $e->getMessage();
                    }
    				$errorMsg .= $e->getMessage()."<br/>";
    			}

                if (!empty($getOrderNoFailureFlag) && count($getOrderNoFailureFlag)>10) {
                    $errLogMessage = '获取订单号失败:'.implode(',',$getOrderNoFailureFlag);
                    echo $errLogMessage."<br>";
                    $this->addAmazonLog($accountID,AmazonLog::STATUS_FAILURE,$errLogMessage);
                    return $syncTotal;
                }
    		}
    		if($errorMsg){
    			$this->setExceptionMessage($errorMsg);
    		}
    		return $syncTotal;
    	}else{
    		return 0;
    	}
    } 

    /**
     * @desc 保存订单数据
     * @param array $order
     * @throws Exception
     */
    public function saveOrderData($order){
    	$dbTransactionModel = new Order();
    	$dbTransaction = $dbTransactionModel->dbConnection->getCurrentTransaction();
    	if( !$dbTransaction ){
    		$dbTransaction = $dbTransactionModel->dbConnection->beginTransaction();//开启事务
    	}
    	try{
    		$this->orderResponse = $order;
    		/** 1.保存订单主数据*/
    		$orderID = $this->saveOrderInfo();
    		if($orderID){//保存失败已生成异常，这里主要会有不需要进行再次操作的情况下返回false
    			/** 2.保存订单详情信息*/
    			$this->saveOrderDetail($orderID);

    			/** 3.保存交易信息*/
    			$this->saveTransaction($orderID);

    			/** 4.保存付款信息*/
    			$this->saveOrderPaypalTransactionRecord($orderID);

				/** 5.保存发货时间*/
				$this->saveShipDate($orderID);    			
    		}
    		$dbTransaction->commit();
    	}catch (Exception $e){
    		$dbTransaction->rollback();
    		throw new Exception($order['amazon_order_id'].' @@ '.$e->getMessage(), $e->getCode());
    	}
    }

	/**
	 * @desc 保存订单信息
	 * @throws Exception
	 * @return boolean
	 */
	public function saveOrderInfo(){
		$platformCode = Platform::CODE_AMAZON;
		$order = $this->orderResponse;
        $order_id = AutoCode::getCodeNew('order'); // 获取订单号
        if ( empty($order_id) ) {
            throw new Exception("getCodeNew Error",self::GET_ORDERNO_ERRNO);//指定code
        } else {
            $order_id = $order_id . 'AZ';
        }
        
        $platformOrderID = trim($order['amazon_order_id']);

        //防止平台订单号重复, 同步到OMS时平台订单号增加账号id, add in 20170213
        $platformOrderIDNew = $platformOrderID;
        if (ListOrdersRequest::ORDER_FULLFILLMENTCHANNEL_FBA == $order['fulfillment_channel'] ) {
			$platformOrderIDNew = $order['account_id'].'-'.$platformOrderID;
		}

		$orderInfo = Order::model()->getOrderInfoByCondition($platformOrderIDNew, $platformCode, $order['account_id']);

		//未出货的订单可以取消
		if( ListOrdersRequest::ORDER_STATUS_CANCELED == $order['order_status'] ){//退款订单
			if( !empty($orderInfo) && Order::SHIP_STATUS_NOT == $orderInfo['ship_status'] ){
				Order::model()->cancelOrders($orderInfo['order_id']);
			}
			return false;
		}

		//存在已付款的订单，不更新
		if( !empty($orderInfo) && Order::PAYMENT_STATUS_END == $orderInfo['payment_status'] ){
			echo '订单:'. $platformOrderID .', OMS已存在....<br>';
			return false;
		}
		
		$orderID = !empty($orderInfo) ? $orderInfo['order_id'] : $order_id;//获取订单号

        //订单号重复检查
        $tmpOrder = Order::model()->getInfoByOrderId($orderID,'order_id');
        if (!empty($tmpOrder)) {
            throw new Exception($orderID.'订单号重复!');
        }	

		$shipType       = "";//运费方式
		$warehouse_id   = 1;
		$shipStatus     = Order::SHIP_STATUS_NOT;//未发货
		$completeStatus = Order::COMPLETE_STATUS_DEFAULT;//刚导入
		$isASS          = 0;	#是否平台发货 Rex 16.07.04 加
		//如果是FBA的订单，查找对应账号走的哪个FBA仓库
		if (ListOrdersRequest::ORDER_FULLFILLMENTCHANNEL_FBA == $order['fulfillment_channel'] ) {
			$accountWarehouse = include CONF_PATH . 'amazonAccountWarehouse.php';
			if (!empty($accountWarehouse) && array_key_exists($order['account_id'], $accountWarehouse)) {
				$warehouse_id = $accountWarehouse[$order['account_id']];
			}
			$shipType       = "amazon";
			$shipStatus     = Order::SHIP_STATUS_YES;
			$completeStatus = Order::COMPLETE_STATUS_END;
			$isASS          = 1;
		}

		$UKisland = array('Isle of Man', 'Jersey', 'Wales', 'Scotland', 'Northern Ireland', 'Guernsey');
		$shipCountry = trim($order['shipping_address_country_code']);
		$stateOrRegion = trim($order['shipping_address_state_or_region']);
		// ================ S:英国国家所属岛屿的处理 ================
		if($shipCountry != "GB" && in_array($stateOrRegion, $UKisland)){
			$this->shipCountry = $shipCountry = "GB";
		}
		if ($shipCountry == 'JE') {
			$this->shipCountry = $shipCountry = "GB";
		}
		if($shipCountry == "IM" || $shipCountry == "GG" ){
			$this->shipCountry = $shipCountry = "GB";
			if($shipCountry == "IM"){
				$order['shipping_address_state_or_region'] = "Isle of Man";
			}
		}
		// ================ E:英国国家所属岛屿的处理================
		$shipCountryName = Country::model()->getEnNameByAbbr($shipCountry);
		$this->shipCountryName = $shipCountryName;

		//收件人地址处理
		$ship_street1 = trim($order['shipping_address_address_line1']);
		$ship_street2 = trim($order['shipping_address_address_line2']);
		$ship_street3 = trim($order['shipping_address_address_line3']);
		//2016-12-15目前发现jp站Address3有值
		if ($ship_street3 != '') {
			$ship_street2 = $ship_street2 == '' ? $ship_street3 : $ship_street2.' ,'.$ship_street3;
		}

		//组装数据
    	$insert_order = array(
			'order_id'              => $orderID,
			'platform_code'         => $platformCode,
			'platform_order_id'     => $platformOrderIDNew,
			'account_id'            => $order['account_id'],
			'log_id'                => $order['log_id'],
			'order_status'          => $order['order_status'],
			'buyer_id'              => trim($order['buyer_name']),
			'email'                 => trim($order['buyer_email']),
			'timestamp'             => date('Y-m-d H:i:s'),
			'created_time'          => $order['purchase_date'],//UTC
			'last_update_time'      => $order['last_update_date'],//UTC
			'ship_cost'             => 0,//订单运费(含成交费),在订单明细中计算更新
			'subtotal_price'        => 0,//产品总金额(含成交费),在订单明细中计算更新
			'total_price'           => floatval($order['order_total_amount']),//订单交易金额(含成交费)
            'final_value_fee'       => 0,//成交费,目前没有计算公式
            'insurance_amount'      => 0,//运费险(无)
			'currency'              => $order['order_total_currency_code'],
			'ship_country'          => $shipCountry,
			'ship_country_name'     => $shipCountryName,
			'paytime'               => $order['purchase_date'],//UTC
			'payment_status'        => Order::PAYMENT_STATUS_NOT,//由保存交易记录成功时更新
			'ship_phone'            => trim($order['shipping_address_phone']),
			'ship_name'             => trim($order['shipping_address_name']),
			'ship_street1'          => $ship_street1,
			'ship_street2'          => $ship_street2,
			'ship_zip'              => trim($order['shipping_address_postal_code']),
			'ship_city_name'        => trim($order['shipping_address_city']),
			'ship_stateorprovince'  => trim($order['shipping_address_state_or_region']),
			'is_multi_warehouse'	=> $warehouse_id,
			'ship_code'				=> $shipType,
			'ship_status'			=> $shipStatus,
			'complete_status'		=> $completeStatus,
			'ori_create_time'       => self::transformToLocal($order['purchase_date']),//PRC
			'ori_update_time'       => self::transformToLocal($order['last_update_date']),//PRC
			'ori_pay_time'          => self::transformToLocal($order['purchase_date']),//PRC
			'is_ASS'			    => $isASS,
		);
    
    	$flag = Order::model()->saveOrderRecord($insert_order);
    	if(!$flag) throw new Exception("save order failure");
    	return $orderID;        				
	}

	/**
	 * @desc 保存订单商品信息
	 * @param string $orderID
	 * @throws Exception
	 * @return boolean
	 */
	public function saveOrderDetail($orderID) {
        $order = $this->orderResponse;
        $platformCode = Platform::CODE_AMAZON;
        $platformOrderID = trim($order['amazon_order_id']);
        $currency = $order['order_total_currency_code'];

		//获取订单明细， 按平台订单号+账号id 获取  add in 20170213
		$itemsDatas = AmazonOrderDetail::model()->getListByCondition('*',"amazon_order_id='{$platformOrderID}' 
			and account_id={$order['account_id']} and quantity_ordered>0 ");
		if (empty($itemsDatas)) {
			throw new Exception("订单明细为空，待补拉");
		}

		//有效性检测
		$totalNumbers = $order['number_of_items_shipped'] + $order['number_of_items_unshipped'];
		$itemNumbers = 0;
		foreach ($itemsDatas as $itemsData) {
			if ( $itemsData['item_price_amount'] <= 0 || $itemsData['item_price_currency_code'] == '' ) {
				throw new Exception("item_price or item_amount invalid");
			}
			$itemNumbers += (int)$itemsData['quantity_ordered'];
		}
		if ( $totalNumbers != $itemNumbers ) {
			throw new Exception("item数量不一致，待补拉");
		}

        //防止平台订单号重复, 同步到OMS时平台订单号增加账号id, add in 20170213
        $platformOrderIDNew = $platformOrderID;
        if (ListOrdersRequest::ORDER_FULLFILLMENTCHANNEL_FBA == $order['fulfillment_channel'] ) {
        	$platformOrderIDNew = $order['account_id'].'-'.$platformOrderID;
        }		

		//删除原有订单详情
		OrderDetail::model()->deleteOrderDetailByOrderID($orderID);		

		$encryptSku         = new encryptSku();
		$shipCoutryName     = $this->shipCountryName;

		//费用计算
		$listCount 			= count($itemsDatas);//item数量
		$totalWeight        = 0;//订单sku总重量

		$subtotalPrice      = 0;//订单商品总金额
		$totalShippingPrice = 0;//订单总运费
		$totalTaxFee 		= 0;//总税费
		$totalDiscount 		= 0;//折扣总金额

		foreach ($itemsDatas as $itemsData) {
			//计算产品总金额
			$quantity  = (int)$itemsData['quantity_ordered'];
			$itemPrice = floatval($itemsData['item_price_amount']);	//商品小计金额
			$subtotalPrice += $itemPrice;

			//运费
			$shipPrice = floatval($itemsData['shipping_price_amount']);
			$totalShippingPrice += $shipPrice;

			//计算税费
			if (isset($itemsData['item_tax_amount'])) {
				$totalTaxFee += $itemsData['item_tax_amount'];
			}
			if (isset($itemsData['shipping_tax_amount'])) {
				$totalTaxFee += $itemsData['shipping_tax_amount'];
			}
			if (isset($itemsData['gift_wrap_tax_amount'])) {
				$totalTaxFee += $itemsData['gift_wrap_tax_amount'];
			}

			//计算折扣金额
			if (isset($itemsData['shipping_discount_amount'])) {
				$totalDiscount += $itemsData['shipping_discount_amount'];
			}
			if (isset($itemsData['promotion_discount_amount'])) {
				$totalDiscount += $itemsData['promotion_discount_amount'];
			}			
		}

		//平摊费用
		$itemSkus = array();
		$partDetailInfos = array();		
		$orderSkuExceptionMsg = "";
		$tmpShipFee = $tmpDiscount = $tmpTaxFee = 0;
		//$tmpFvf = $tmpFeeAmt = $tmpInsuranceFee = 0;
		$tmpItemSalePriceAllot = 0;
		$index = 1;//计数
		foreach ($itemsDatas as $itemsData) {
			$asin 	   = $itemsData['asin'];
			$skuOnline = $itemsData['seller_sku'];
			$sku       = $encryptSku->getAmazonRealSku2($skuOnline);
			$skuInfo   = $sku == '' ? '' : Product::model()->getProductInfoBySku($sku);
			$quantity  = (int)$itemsData['quantity_ordered'];
			$currency  = trim($itemsData['item_price_currency_code']);

			//商品总金额
			$itemPrice = floatval($itemsData['item_price_amount']);	//商品小计金额
			$salePrice = round($itemPrice / $quantity, 2);	//商品单价

			$skuInfo2  = array();//发货sku信息
			$pending_status  = OrderDetail::PEDNDING_STATUS_ABLE;
			if( !empty($skuInfo) ){//可以查到对应产品
				$realProduct = Product::model()->getRealSkuListNew($skuInfo['sku'], $quantity, $skuInfo);
				$itemSkus[]['sku'] = $realProduct['sku'];

				//检查发货sku是否主sku
				if ($skuInfo['sku'] == $realProduct['sku']) {
					$skuInfo2 = $skuInfo;
				} else {
					$skuInfo2 = Product::model()->getProductInfoBySku($realProduct['sku']);
				}
			}

			//解密后sku和拆分后sku有为空
			if (empty($skuInfo) || empty($skuInfo2) ) {
				$realProduct = array(
					'sku'       => 'unknown',
					'quantity'  => $quantity,
				);
				$pending_status = OrderDetail::PEDNDING_STATUS_KF;
				$orderSkuExceptionMsg .= "sku信息不存在;";		
			}

			//检查发货sku是否主sku
			if($skuInfo2 && Product::PRODUCT_MULTIPLE_MAIN == $skuInfo2['product_is_multi'] ){
				$childSku = ProductSelectAttribute::model()->getChildSKUListByProductID($skuInfo2['id']);
	            if(!empty($childSku)){
	            	$pending_status = OrderDetail::PEDNDING_STATUS_KF;
	                $orderSkuExceptionMsg .= "{$skuInfo2['sku']}为主sku;";
	            }
			}

			//发货sku重量统计 
			if($skuInfo2){
				$totalWeight += floatval($skuInfo2['product_weight'])*intval($realProduct['quantity']);
			}

			//平摊开始
			if ( $index == $listCount ) {
				$shipFee            = round($totalShippingPrice - $tmpShipFee,2);//平摊后的运费
				$discount           = round($totalDiscount - $tmpDiscount,2);//平摊后的优惠金额
				$taxFee             = round($totalTaxFee - $tmpTaxFee,2);//平摊后的税费
				$itemSalePriceAllot = round($subtotalPrice - $totalDiscount - $tmpItemSalePriceAllot, 2);//平摊后的item售价
				$unitSalePriceAllot = round($itemSalePriceAllot/$quantity, 3);//平摊后的销售单价
            } else {
				$itemRate           = $itemPrice/$subtotalPrice;
				$shipFee            = round($itemRate * $totalShippingPrice, 2);//平摊后的运费
				$discount           = round($itemRate * $totalDiscount, 2);//平摊后的运费
				$taxFee             = round($itemRate * $totalTaxFee, 2);//平摊后的税费
				$itemSalePriceAllot = round($itemPrice - $discount, 2);//平摊后的item售价
				$unitSalePriceAllot = round($itemSalePriceAllot/$quantity, 3);//平摊后的销售单价

				$tmpShipFee            += $shipFee;
				$tmpDiscount           += $discount;
				$tmpTaxFee             += $taxFee;
				$tmpItemSalePriceAllot += $itemSalePriceAllot;
            }
            $index++;

            $title = trim($itemsData['title']);
            if (mb_strlen($title)>100) {//title超长,截取OMS定义的长度值
                $title = mb_substr($title,0,100);
            } 	

			//保存订单明细
			$orderItemRow = array(
				'transaction_id'          => $platformOrderIDNew,
				'order_id'                => $orderID,
				'platform_code'           => $platformCode,
				'item_id'                 => $itemsData['order_item_id'],
				'title'                   => $title,
				'sku_old'                 => $sku == ''? 'unknown' : $sku,
				'sku'                     => $realProduct['sku'],
				'site'                    => $this->_accountSite,
				'quantity_old'            => $quantity,
				'quantity'                => $realProduct['quantity'],
				'sale_price'              => $salePrice,//销售单价
				'ship_price'			  => $shipFee,//平摊后的运费
				'total_price'             => round($itemPrice+$shipFee,2),//产品销售金额+平摊后的运费
				'final_value_fee'         => 0,//平摊后的成交费
				'currency'                => $currency,
				'pending_status'		  => $pending_status,
				'create_time' 			  => date('Y-m-d H:i:s')
			);
			
			//通过amazon海外仓和asin映射表(ueb_amazon_overseas_warehouse_asin_map)，把对应的海外仓的asin+在线SKU订单添加海外仓ID
			if (!empty($asin) && ListOrdersRequest::ORDER_FULLFILLMENTCHANNEL_FBA != $order['fulfillment_channel']){
				$amazonAsinWarehouseModel = new AmazonAsinWarehouse();
				$wret = $amazonAsinWarehouseModel->getWarehouseInfoByAsin($asin,$skuOnline);
				if ($wret){
					$orderItemRow['warehouse_id'] = (int)$wret['overseas_warehouse_id'];
				}				
			}
			$orderItemID = OrderDetail::model()->addOrderDetail($orderItemRow);	
			if(!$orderItemID){
				throw new Exception($orderID .":save order item failure");
			}

	        //保存订单明细扩展表
	        $orderItemExtendRow = array(
	            'detail_id'              => $orderItemID,
	            'item_sale_price' 		 => $itemPrice,//产品金额(含成交费)
	            'item_sale_price_allot'  => $itemSalePriceAllot,//平摊后的产品金额(含成交费，减优惠金额)
	            'unit_sale_price_allot'  => $unitSalePriceAllot,//平摊后的单价(原销售单价-平摊后的优惠金额)
	            'coupon_price_allot'     => $discount,//平摊后的优惠金额
	            'tax_fee_allot'          => $taxFee,//平摊后的税费
	            'insurance_amount_allot' => 0,//平摊后的运费险
	            'fee_amt_allot'          => 0,//平摊后的手续费
	        );
	        $flag = OrderDetailExtend::model()->addOrderDetailExtend($orderItemExtendRow);
	        if(!$flag) throw new Exception("save order detailExtend failure");

			//保存amazon线上sku
			$sellerFlag = OrderSellerSkus::model()->saveSellerSku(array(
				'order_id'			=> $orderID,
				'detail_id'			=> $orderItemID,
				'platform_code'		=> $platformCode,
				'seller_sku'		=> $skuOnline
			));		
			if(!$sellerFlag){
				throw new Exception($platformOrderID .": save order item seller sku failure");
			}	
			
			//判断是否需要添加插头数据
			$flag = OrderDetail::model()->addOrderAdapter(array(
					'order_id'          => $orderID,
					'ship_country_name' => $shipCoutryName,
					'platform_code'     => $platformCode,
					'currency'          => $currency
			),	$realProduct);
			if(!$flag) throw new Exception($platformOrderID . ": Save order adapter failure");

	        //保存订单sku与销售关系数据
	        $part_data = array(
	            'platform_code'         => $platformCode,//平台code
	            'platform_order_id'     => $platformOrderIDNew,//平台订单号
	            'online_sku'            => $skuOnline==''?'unknown':$skuOnline,//在线sku
	            'account_id'            => $order['account_id'],//账号id
	            'site'                  => $orderItemRow['site'],//站点
	            'sku'                   => $orderItemRow['sku_old'],//系统sku
	            'item_id'               => $asin,//主产品id
	            'order_id'              => $orderID,//系统订单号
	        );
	        $partDetailInfos[] = $part_data;
		}
		
		//判断是否有异常存在
		if($orderSkuExceptionMsg != ''){
			$res = Order::model()->setExceptionOrder($orderID,OrderExceptionCheck::EXCEPTION_SKU_UNKNOWN,$orderSkuExceptionMsg);
			if(! $res){
				throw new Exception ( 'Set order Exception Failure: '.$platformOrderID);
			}
		}
		
		$ship_country_name = Country::model()->getEnNameByAbbr(trim($order['shipping_address_country_code']));
		$oderInfo = array(
			'ship_country_code' 	=> trim($order['shipping_address_country_code']),
			'order_total'       	=> floatval($order['order_total_amount']),
			'ship_country_name' 	=> $ship_country_name,
			'total_weight'      	=> $totalWeight,
			'ship_free'             => $totalShippingPrice
	 	);
		
		//更新订单运费和物流方式
		$updateOrderData = array(
			'ship_cost' 	 => $totalShippingPrice, 		
			'subtotal_price' => $subtotalPrice,
		);

		//获取物流方式
		if ( ListOrdersRequest::ORDER_FULLFILLMENTCHANNEL_FBA != $order['fulfillment_channel'] ) {
			$shipType = $this->getShipType($oderInfo, $itemSkus);
			$updateOrderData['ship_code'] = $shipType;
		}

		$flag = Order::model()->updateByPk($orderID, $updateOrderData);
		if(!$flag){
			throw new Exception("save order datas failure");
		}

        //保存订单扩展表数据        
        $orderExtend = new OrderExtend();
        $orderExtend->getDbConnection()->createCommand()->delete($orderExtend->tableName(),"platform_order_id='{$platformOrderIDNew}' and platform_code='". $platformCode ."'");
        $orderExtend->getDbConnection()->createCommand()->insert($orderExtend->tableName(),array(
			'order_id'          => $orderID,
			'platform_code'     => $platformCode,
			'platform_order_id' => $platformOrderIDNew,//平台订单号
			'account_id'        => $order['account_id'],//账号id
			'tax_fee'           => $totalTaxFee,//总税费
			'coupon_price'      => $totalDiscount,//总优惠
			'currency' 			=> $currency,
            'create_time'       => date('Y-m-d H:i:s'),
            'payment_type'      => $order['payment_method'],
            'logistics_type'    => $order['ship_service_level'],  
        ));

		//保存订单sku与销售关系
        $flag = true;
        if (!empty($partDetailInfos)) {
            foreach ($partDetailInfos as $orderSkuOwnerInfo) {
                $addRes = OrderSkuOwner::model()->addRow($orderSkuOwnerInfo);
                if( $addRes['errorCode'] != '0' ){
                    $flag = false;
                }
            }
        }
        if (!$flag) {
            throw new Exception("Save OrderSkuOwnerInfo Failure");
        }
       
		return $flag;		
	}
	
	/**
	 * @desc 保存订单交易信息
	 * @param string $orderID
	 * @throws Exception
	 * @return boolean
	 */
	public function saveTransaction($orderID) {
		$order = $this->orderResponse;
        $platformOrderID = trim($order['amazon_order_id']);

        //防止平台订单号重复, 同步到OMS时平台订单号增加账号id, add in 20170213
        $platformOrderIDNew = $platformOrderID;
        if (ListOrdersRequest::ORDER_FULLFILLMENTCHANNEL_FBA == $order['fulfillment_channel'] ) {
        	$platformOrderIDNew = $order['account_id'].'-'.$platformOrderID;
        }

        if(trim($order['order_total_currency_code']) == '' || floatval($order['order_total_amount'])<=0 ) {
        	throw new Exception("Transaction Infos Invalid");
        }
		$flag = OrderTransaction::model()->saveTransactionRecord($platformOrderIDNew, $orderID, array(
    		'order_id'              => $orderID,
    		'first'                 => 1,
    		'is_first_transaction'  => 1,
    		'receive_type'          => OrderTransaction::RECEIVE_TYPE_YES,
    		'account_id'            => $order['account_id'],
    		'parent_transaction_id' => '',
			'order_pay_time'        => $order['purchase_date'],
    		'amt'                   => floatval($order['order_total_amount']),//订单交易金额(含成交费)
    		'fee_amt'               => 0,//手续费
    		'currency'              => trim($order['order_total_currency_code']),
    		'payment_status'        => 'Completed',
		    'platform_code'         => Platform::CODE_AMAZON,
		));//保存交易信息
    	if($flag){
    		$flag = Order::model()->updateColumnByOrderID($orderID, array('payment_status' => Order::PAYMENT_STATUS_END));//保存为已付款
    		if($flag){
    			return $flag;
    		}
    	}
		throw new Exception("save order transaction failure");
	}
	
	/**
	 * @desc 保存付款信息
	 * @param string $orderID
	 * @throws Exception
	 * @return boolean
	 */
	public function saveOrderPaypalTransactionRecord($orderID) {
		$order = $this->orderResponse;
        $platformOrderID = trim($order['amazon_order_id']);

        //防止平台订单号重复, 同步到OMS时平台订单号增加账号id, add in 20170213
        $platformOrderIDNew = $platformOrderID;
        if (ListOrdersRequest::ORDER_FULLFILLMENTCHANNEL_FBA == $order['fulfillment_channel'] ) {
        	$platformOrderIDNew = $order['account_id'].'-'.$platformOrderID;
        }

        if(trim($order['order_total_currency_code']) == '' || floatval($order['order_total_amount'])<=0 || trim($order['buyer_email']) == '' ) {//	|| trim($order['buyer_name']) == '' 
        	throw new Exception("Transaction Infos Invalid");
        }        
		$flag = OrderPaypalTransactionRecord::model()->savePaypalRecord($platformOrderIDNew, $orderID, array(
				'order_id'              => 	$orderID,
				'receive_type'          => 	OrderPaypalTransactionRecord::RECEIVE_TYPE_YES,
				'receiver_business'		=> 	'',
				'receiver_email' 		=> 	'unknown@vakind.com',
				'receiver_id' 			=> 	'',
				'payer_id' 				=> 	'',
				'payer_name' 			=> 	trim($order['buyer_name']),
				'payer_email' 			=> 	trim($order['buyer_email']),
				'payer_status' 			=> 	'',
				'parent_transaction_id'	=>	'',
				'transaction_type'		=>	'',
				'payment_type'			=>	trim($order['payment_method']),
				'order_time'			=>	$order['purchase_date'],
				'amt'					=>	floatval($order['order_total_amount']),//订单交易金额(含成交费)
				'fee_amt'				=>	0,//手续费
				'tax_amt'				=>	0,//税费
				'currency'				=>	trim($order['order_total_currency_code']),
				'payment_status' 		=> 	'Completed',
				'note'					=>	'',
				'modify_time'			=>	''
		));//保存交易付款信息
		if($flag){
			return true;
		}
		throw new Exception("save order trans paypal info failure");
	}
	/**
	 * @desc 保存发货日期数据
	 * @param string $orderID
	 * @throws Exception
	 * @return boolean
	 */
	public function saveShipDate($orderID){
		$order = $this->orderResponse;
		$platformCode = Platform::CODE_AMAZON;
		OrderShipDate::model()->deleteAll("order_id=:order_id AND platform_code=:platform_code", array(
			':order_id'=>$orderID, ':platform_code'=>$platformCode
		));
		$flag = OrderShipDate::model()->addData($orderID, array(
			'platform_code'      =>	$platformCode,
			'order_id'           =>	$orderID,
			'earliest_ship_date' =>	$order['earliest_ship_date'],
			'latest_ship_date'   =>	$order['latest_ship_date']
		));
	}	

	/**
     * @desc UTC时间格式转换
     * @param string $UTCTime
     * @return string
     */
    public static function transformUTCTimeFormat($UTCTime){
    	$newUTCTime = '';
    	if (!empty($UTCTime)){
	    	$UTCTime 	= strtoupper($UTCTime);
	    	$newUTCTime = str_replace("T", " ", $UTCTime);
	    	$newUTCTime = str_replace("Z", "", $UTCTime);
    	}
		return $newUTCTime;
    }

    /**
     * @desc 转换为北京时间
     * @param string $UTCTime
     * @return string
     */
    public static function transformToLocal($UTCTime){
    	return date("Y-m-d H:i:s", strtotime($UTCTime)+8*3600);
    }    	
	
	/**
	 * @desc 获取物流方式
	 * @param  array $oderInfo   array(
	 *								'ship_country_code'=>'',
	 *								'order_total'=>'',
	 *								'ship_country_name'=>'',
	 *								'ship_free'	=>	0,
	 *								'total_weight'=>0
	 *							);
	 * @param array $items[]  array(
	 * 									'sku'=>''
	 * 								)
	 */
	public function  getShipType($oderInfo, $items){
		$ship_type = '';
		$ship_types = array();
		$ukAccount = array(4, 8, 9);//vakind_uk、vakind_uk_a3、vakind_uk_b
		/*获取每个条目的属性*/
		$attributes = array();
		foreach($items as $item){
			$attribute = self::model('ProductSelectAttribute')->getProductAttributeIds($item['sku']);
			if(isset($attribute[$item['sku']]))
				$attributes[$item['sku']] = $attribute[$item['sku']];
		}
		unset($attribute);
		//特殊属性系统选择物流
		if($this->_accountID == 4||$this->_accountID==8||$this->_accountID==9){//uk
			if(in_array(strtoupper($oderInfo['ship_country_code']),array('GB','GG','JE'))){//UK,Jersey,Guernsey
				if($oderInfo['order_total']<18){ //order_total
					$ship_types = array(Logistics::CODE_DHL_XB,Logistics::CODE_ZE,Logistics::CODE_CM_CESL);
				}else{
					$ship_types = array(Logistics::CODE_DHL_XB,Logistics::CODE_ZE,Logistics::CODE_CM_CESL,Logistics::CODE_FEDEX_IE);
				}
			}else{
				$ship_type = Logistics::CODE_FEDEX_IE;
			}
			//2014-6-21 penny的特殊要求，Fox add  uk
			if($this->_accountID == 4){
				$szsku = self::getSpecialSkuList('AMAZON_SZSKU_UK');
				$v = true;
				foreach($items as $item){
					if (!in_array($item['sku'],$szsku)){
						$v = false;
						continue 1;
					}
				}
				if($v==true){
					$ship_type = Logistics::CODE_CM_GZ;//广州小包
				}
			}

			if($this->_accountID == 8){//uk_a3
				//检测产品属性
				$diff = false;
				foreach($items as $item){
					if(!isset($attributes[$item['sku']])) continue;
					$attrs = $attributes[$item['sku']];
					$diff = array_intersect(self::model('ProductAttribute')->amazon_special_attribute, $attrs);//有交集，则为带电池产品
					if(!empty($diff)){
						$diff = true;
						continue 1;
					}
				}
				if(empty($diff)){
					if($oderInfo['order_total'] > 10){
						$ship_types = array(Logistics::CODE_DHL_XB,Logistics::CODE_ZE,Logistics::CODE_CM_CESL);
					}else{
						$ship_types = array(Logistics::CODE_CM_GZ);
					}
					//$ship_type = Logistics::CODE_CM_CNXB;//深圳小包
				}
			}
			
			if($this->_accountID==8 && $shipfree > 0){
				$ship_types = array(Logistics::CODE_FEDEX_IE,Logistics::CODE_FEDEX_IE_HK);
			}
				
		}elseif($this->_accountID == 16){
			if(!in_array(strtoupper($oderInfo['ship_country_code']),array('GB','UK')) || $oderInfo['total_weight'] > 2000){//付运费的和重量超过2kg的用fedexie
				$ship_type = Logistics::CODE_FEDEX_IE;
			}else{
				$ship_types = array(Logistics::CODE_DHL_XB,Logistics::CODE_CM_GZ,Logistics::CODE_ZE,Logistics::CODE_CM_CESL); //其它系统自动选走深圳或DHL小包
			}
		}elseif($this->_accountID == 5 || $this->_accountID==11 || $this->_accountID==12 || $this->_accountID==17){//de站点
			if(strtoupper($oderInfo['ship_country_code']) == 'DE'){ //如果是德国买家
				if($oderInfo['order_total']>20){//20欧以上发中欧专线?
					$ship_type = Logistics::CODE_DHL_XB_DE;
				}else{
					$ship_type = Logistics::CODE_DHL_XB_DE; //走DHL清关小包
				}
			}else{ //其他欧洲国家走中欧洲专线
				$ship_type = Logistics::CODE_DHL_XB;
			}
			if($this->_accountID == 5 || $this->_accountID==17){//指定走深圳小包  de
				$tsku = array();
				$tsku = self::getSpecialSkuList('AMAZON_SZSKU_DE');
				$v = true;
				foreach($items as $item){
					if (!in_array($item['sku'],$tsku)){
						$v = false;
						continue 1;
					}
				}
				if($v==true){
					$ship_type = Logistics::CODE_CM_GZ;//深圳小包
				}
				if($this->_accountID == 5 && strtoupper($oderInfo['ship_country_code']) == 'AUT'){
					$ship_type = Logistics::CODE_DHL_XB;//奥地利的改为中欧专线   cm_cesl
				}
				$szghsku = array();
				$szghsku = self::getSpecialSkuList('AMAZON_SZGHSKU_DE');
				$gh = true;
				foreach($items as $item){
					if (!in_array($item['sku'],$szghsku)){
						$gh = false;
						continue 1;
					}
				}
				if($gh==true){
					$ship_type = Logistics::CODE_GHXB_CN;
				}
			}
		}elseif($this->_accountID==7 || $this->_accountID==15 || $this->_accountID==19 || $this->_accountID==20){//fr  es西班牙走DHL小包/中欧专线 西班牙以外的走fedex-ie
			$ship_types = array(Logistics::CODE_DHL_XB,Logistics::CODE_FEDEX_IE,Logistics::CODE_ZE,Logistics::CODE_CM_CESL);
			if(strtoupper($oderInfo['ship_country_code']) != 'FR' && ($this->_accountID==7 || $this->_accountID==19 || $this->_accountID==20)){
				$ship_types = array(Logistics::CODE_FEDEX_IE,Logistics::CODE_DHL_XB,Logistics::CODE_ZE,Logistics::CODE_CM_CESL);
			}
			if(strtoupper($oderInfo['ship_country_code']) != 'ES' && $this->_accountID==15){
				$ship_types = array(Logistics::CODE_FEDEX_IE);
			}
				
			if($this->_accountID==7 || $this->_accountID==19 || $this->_accountID==20){
				$ship_types = array(Logistics::CODE_ZE,Logistics::CODE_CM_CESL,Logistics::CODE_DHL_XB);
			}
				
			if($this->_accountID==7 && $oderInfo['order_total']<7){
				$ship_types = array(Logistics::CODE_DHL_XB);
			}
				
			if($this->_accountID==7){
				//54365 54365.01
				$szsku = array('54365','54365.01');
				$v = true;
				foreach($items as $item){
					if (!in_array($item['sku'],$szsku)){
						$v = false;
						continue 1;
					}
				}
				if($v==true){
					$ship_type = Logistics::CODE_DHL_XB;
				}else{
					$ship_types = array(Logistics::CODE_CM_CESL);
				}
		
			}
				
			/*if($val['order_total']>22){
			 unset($ship_types[0]);//删除cm_dhl
			}*/
			if(($this->_accountID==7 || $this->_accountID==19 || $this->_accountID==20) && (strtoupper($oderInfo['ship_country_code']) == 'BE' || strtoupper($oderInfo['ship_country_code']) == 'CH' || strtoupper($oderInfo['ship_country_code']) == 'MCO')){
				$ship_types = array(Logistics::CODE_FEDEX_IE);
			}
			if(($this->_accountID==7 || $this->_accountID==19 || $this->_accountID==20) && strtoupper($oderInfo['ship_country_code']) == 'FR' && $oderInfo['order_total']>=20){
				$ship_types = array(Logistics::CODE_GHXB_CN,Logistics::CODE_GHXB_GZ);
			}
				
				
			if($this->_accountID==15){
				$tsku = array();
				$tsku = self::getSpecialSkuList('AMAZON_FEDSKU_ES');
				$v = true;
				foreach($items as $item){
					if (!in_array($item['sku'],$tsku)){
						$v = false;
						continue 1;
					}
				}
				if($v==true){
					$ship_type = Logistics::CODE_FEDEX_IE;//
				}
		
				$hksku = array();
				$hksku = self::getSpecialSkuList('AMAZON_FEDHKSKU_ES');
				$hk = true;
				foreach($items as $item){
					if (!in_array($item['sku'],$hksku)){
						$hk = false;
						continue 1;
					}
				}
				if($hk==true){
					$ship_type = Logistics::CODE_FEDEX_IE_HK;
				}
		
		
			}
			if($this->_accountID==15 && $oderInfo['order_total']>=22){
				$ship_type = Logistics::CODE_CM_CESL;//
			}
				
				
			if($this->_accountID==7){
				$szghsku = array();
				$szghsku = self::getSpecialSkuList('AMAZON_SZGHSKU_FR');
				$gh = true;
				foreach($items as $item){
					if (!in_array($item['sku'],$szghsku)){
						$gh = false;
						continue 1;
					}
				}
				foreach($items as $item){
					if(!isset($attributes[$item['sku']])) continue;
					$attrs = $attributes[$item['sku']];
					$diff = array_intersect(self::model('ProductAttribute')->amazon_special_attribute, $attrs);//有交集，则为带电池产品
					if(!empty($diff)){
						$diff = true;
						continue 1;
					}
				}
		
				if($gh==true){
					if(!$diff){
						$ship_type = Logistics::CODE_GHXB_CN;
					}else{
						$ship_type = Logistics::CODE_DHL_XB;
					}
				}
			}
				
		}elseif($this->_accountID==14){//jp
			$ship_types = array(Logistics::CODE_GHXB_CN,Logistics::CODE_GHXB_HK);
		}elseif($this->_accountID == 3 || $this->_accountID==6 || $this->_accountID==10 || $this->_accountID==13){//us
			if(strtoupper($oderInfo['ship_country_code']) == 'US'){
				//超过一百美金走联邦快递  2014-05-30
				if($oderInfo['order_total']>100 && $this->_accountID!=13){
					$ship_type = Logistics::CODE_FEDEX_IE;
				}else{
					// by tom 2014-05-29
					$ship_types = array(
							Logistics::CODE_EUB,Logistics::CODE_GHXB_HK,Logistics::CODE_FEDEX_IE
					);
				}
				if($this->_accountID==13){
					if($oderInfo['order_total']>120 || $oderInfo['ship_free'] > 0){
						$ship_type = Logistics::CODE_FEDEX_IE;
					}elseif($oderInfo['order_total']<=8){
						$ship_type = Logistics::CODE_CM_GZ;
					}elseif($oderInfo['order_total']>8){
						$ship_types = array(
								Logistics::CODE_EUB,Logistics::CODE_GHXB_HK,Logistics::CODE_FEDEX_IE
						);
					}
				}
			}else{
				$ship_type = Logistics::CODE_FEDEX_IE;
			}

			//2014-6-21 penny的特殊要求，Fox add  us
			if($this->_accountID == 3 && $oderInfo['order_total']<15){
				$szsku = array();
				$szsku = self::getSpecialSkuList('AMAZON_SZSKU_US');
				$v = true;
				foreach($items as $item){
					if (!in_array($item['sku'],$szsku)){
						$v = false;
						continue 1;
					}
				}
				if($v==true){
					$ship_type = Logistics::CODE_CM_GZ;//广州小包
					//$ship_types = array(Logistics::CODE_CM_CNXB);//深圳小包
				}
		
				$skudhl = array();
				$skudhl = self::getSpecialSkuList('AMAZON_DHLSKU_US');
				$x = true;
				foreach($items as $item){
					if (!in_array($item['sku'],$skudhl)){
						$x = false;
						continue 1;
					}
				}
				if($x==true){
					//$ship_type = Logistics::CODE_DHL_XB;
					$ship_types = array(Logistics::CODE_DHL_XB,Logistics::CODE_ZE,Logistics::CODE_CM_CESL);//DHL
				}
		
				if($oderInfo['order_total']<10){
					$ship_types = array(Logistics::CODE_CM_GZ,Logistics::CODE_DHL_XB,Logistics::CODE_ZE,Logistics::CODE_CM_CESL);
				}
		
			}
			if($this->_accountID == 6){
				if($oderInfo['order_total'] > 10){
					$ship_type = Logistics::CODE_EUB;
					//$ship_types = (Logistics::CODE_EUB);
				}else{
					$ship_type = Logistics::CODE_CM_GZ;
				}
			}
				
			//检测产品属性
			$diff = false;
			foreach($items as $item){
				if(!isset($attributes[$item['sku']])) continue;
				$attrs = $attributes[$item['sku']];
				$diff = array_intersect(self::model('ProductAttribute')->amazon_special_attribute, $attrs);//有交集，则为带电池产品
				if(!empty($diff)){
					$diff = true;
					continue 1;
				}
			}
			if($diff==true){
				$ship_type = Logistics::CODE_DHL_XB;
			}
				
			if($this->_accountID==6 && $shipfree > 0){
				$ship_types = array(Logistics::CODE_FEDEX_IE,Logistics::CODE_FEDEX_IE_HK);
			}
				
		}
		if($this->_accountID == 3 || $this->_accountID==4 || $this->_accountID==6 || $this->_accountID==8){
			$dhlsku = array();
			$dhlsku = self::getSpecialSkuList('AMAZON_DHLSKU_TS');
			$v = true;
			foreach($items as $item){
				if (!in_array($item['sku'],$dhlsku)){
					$v = false;
					continue 1;
				}
			}
			if($v==true){
				$ship_type = Logistics::CODE_DHL_XB;//DHL小包
			}
		}
		//美国新帐号缺少ship_type的判断
		if($ship_type==''){
			$min_ship_cost = 0;
			$proAttributes = array();
			if($attributes){
				foreach ($attributes as $attri){
					$proAttributes = array_merge($proAttributes, $attri);
				}
			}
			foreach ($ship_types as $code){
				$ship_cost = self::model("Logistics")->getShipFee($code, $oderInfo['total_weight'], array(
						'attributeid'		=>	array_unique($proAttributes),
						'country'			=> $oderInfo['ship_country_name'],
						'discount'			=> 1,
						'volume'			=> 0,
						'warehouse'			=> '',
				));
				
				if($ship_type=='' || $ship_cost>0 && ($ship_cost<$min_ship_cost || $min_ship_cost<=0)){
					$min_ship_cost = $ship_cost;
					$ship_type = $code;
					break;
				}
			}
		}
		return $ship_type;
	}

	/**
	 * @desc 获取特殊标记SKU配置
	 * @param  string $key
	 * @return array
	 */
	public static function getSpecialSkuList($key='') {
		$conf = array(
			'AMAZON_SZSKU_UK' => array('57459.01','57459.02','57459.03','57459.04','57459.05','57459.06','57459.07','57459.08','57999.01','57999.02','57999.03','57999.04','46782.01','30890','27581','52995','55384.02','55384.05','57263','57263.01','57263.03','54995','54995.01','52555','61242','61242.01','61242.02','61242.03','61242.04','61242.05','29890','29890.01','54170.01','54170.02','27574','1176','58085.01','58085.02','58085.03','52916','16491','54365','52806','51286','36865','31503','16474','58711.01','58711.02','55385','55385.01','55385.02','55385.03','19532','60576.02','54655','54655.01','51290','51290.01','51290.02','51290.03','51290.04','51290.05','51290.06','51290.07','51290.08','3288','57524','62842','58710.01','58710.02','64132.01','64132.02','64132.03','31505','56741','56741.01','51328','54376','54376.01','54376.02','54376.03','54376.04','54376.05','54376.06','54376.07','54376.08','54376.09','54376.10','54376.11','54376.12','54376.13','54376.14','54376.15','54376.16','54376.17','54376.18','54376.19','54376.20','54376.21','54376.22','54376.23','54376.24','54376.25','54376.26','26750','17095','60105','17565','55435','54697.01','53692','52909','54833','0267','6233','44140','43809','18166','55434.02','55434.01','27025','56368','55394','54851','2225','53356','7621','17569','23520','4158','27574','1243','56964','43992','2012','53896','27025.01','20718','54947','54947.01','54949.03','54949.01','43993','51884','58392.04','58392.1','58392.03','58392.08','58392.09','70406.02','70406.01','70406.06','0046','54696','60639','55435.01','55435.02','55129','22131.06','58012.01','58012.02','56845','56845.01','56845.02','61942.01','61942.02','61942.03','61942.04','61942.05','55384.03','55384.04','55384.06','57405','60400.01','60400.02','60400.03','6035','45363','55117','55573','5295','60105.01','60105.02','60105.03','60105.04 ','61681.02','61681.04','61681.05','68977','68978','55271','35443','72326.01','72326.02','72326.03','72326.04','4122A','52807','61899','20697','55523','76678','55673','20684','23516','23154','56730','54639','51532','56519','55053','55053.01','62512.01','62512.03','61790.06','61790.04','61790.02','64408.01','64408.02','64408.03','64408.04','53953','56838','53953.06','54507','54507.01','54507.02','54507.03','54509','54509.01','54509.02','54509.03','54510','54510.01','54510.02','54510.03','54511','54511.01','54511.02','54511.03','54512','54512.02','54512.03','53953.05','53953.02','54505','54505.01','54505.02','54505.03','54506','54506.01','54506.02','54506.03','54850','54364','52809','52330','67015','54168','6302','59713.01','59713.04','5486','52533','56279','55404.01','31658','20651.02','20626','47820'),

			'AMAZON_SZSKU_DE' => array('61563.06','61563.05','1207','61561.01','55434.01','61790','67082','57621','54646','58714','62747','69425','4396','5295','53573.02','1243','52668','54718','61567.05','54949.02','54949','54949.01','54949.03','58714.01','58714.02','58714.02','61911.02','61911.04','34402','58085.01','58085.02','62512.01'),

			'AMAZON_SZGHSKU_DE' => array('65458','56838','56837','54509','54509.01','54509.02','54509.03','54510','54510.02','54510.01','54510.03','54511','54511.01','54511.02','54511.03','54512','54513.02','54513.01','54513','54513.03','53575'),

			'AMAZON_FEDSKU_ES'   => array('32653','62133.01','62565.06','75987.01','72431','75317','75295','75315','74337','71079.01','62133.02','62133.03','71079.02','69058','73235.01','73235.02','76169','72953.01','72953.02','73527.04','73527.01','73527.02','73527.03','69773.01','69773.02','77374.02','77374.03','77374.04','77374.05','77374.06','65724.01','65724.02','65724.03','65724.04','77607','73276','52664.05','52664.04','52664.02','52664.03','73269','20857','73276','72540.01','72540.02','72540.03','59336'),

			'AMAZON_FEDHKSKU_ES' => array('75987.05','75987.02','75987.01','75987.03','75987.04','75331.01','75331.02','62455.02','69539','37993','68001','69058','69043.02','69043.01','71181','74236','73938.01','73938.03','73938.02','71121','71090.03','71090.01','71090.02','76180.01','76180.02','76180.03','76180.04','74474.01','74474.02','74293.02','74293.01','62892','78158.01','78158.02','76075.04','76075.06','76075.05','74684.01','74684.02','74640.01','74640.02','74640.03','74684.03','52337.01','52337.02','52337.03','52337.04','80028.01','80028.02','80028.03'),

			'AMAZON_SZGHSKU_FR'  => array('53953','54505.02','54505.03','54505','54505.01','54509.03','54509','54509.01','54509.02','56837'),

			'AMAZON_SZSKU_US'    => array('54679','52999','26499','61899','53381','31658','55662','55662.01','56622','56622.01','54851','54916','54443','54124','54376','54376.01','54376.02','54376.03','54376.04','54376.05','54376.06','54376.07','54376.08','54376.09','54376.1','54376.11','54376.12','54376.13','54376.14','54376.15','54376.16','54376.17','54376.18','54376.19','54376.2','54376.21','54376.22','54376.23','54376.24','54376.25','50600.01','27046.02','27046.01','70272','7105','54717','55271','0347','1071','55173','60565','55069','17295','52618','1093.01','54949','54949.01','54949.02','54949.03','4396','55394','19994','55384','55384.01','55384.02','55384.03','55384.04','57459.01','57459.02','57459.03','57459.04','57459.05','57459.06','57459.07','57459.08','36421','52807','59966','1093.02','61859.01','61859.02','61859.03','53201','50600.02','16395','54443.02','27022','66816.01','66816.02','6048','50600','66232','58392.09','58392.03','58392.05','58392.04','58392.10','52229','20366','57405','44057','1123','50679','50679.01','57524','53571','0266','54718','54718.01','54718.02','54718.03','54718.04','54718.05','54642','53272','0046','59799','61726.01','61726.02','54948','54948.01','55187.02','23154','54851','0267','54124','20493','55434','55434.02','55435','55435.01','55435.02','54947','54947.01','51823','17510','3083','5509','1243','50600.03','5295','51824','70406.01','60311','57248','57248.02','52330','58557.01','31004','69747','69763','71542','54121','55995','53573','70406.07','61273.01','61273.02','61273.03','61273.04','55673','55673.01','61829.01','61829.02','61829.03','61829.04','61829.05','61829.06','61829.07','57448','55396','56837','58008.01','58008.02','44055','31651','55120','6317A','55211','55559','52908','60608','52715.01','52715.02','52715.03','52715.04','52715.05','52715.01','52715.02','52715.03','52715.04','52715.05 ','72687.01','72687.02','72687.03','72687.04','72687.05','72687.06','72687.07','72687.08','72687.09','72687.10','72687.11','72687.12 ','65715','69490','6538E','2034A','1253A','54634','6183E','58065.06','58065.05','58065.02','58065.03','45357','6183A','20701 ','71379.01','71379.02','71379.03','56730','62149','1253A','70055','63551.01','63551.02','5432','51372','51372.01','51372.02','51372.03','55053.01','55384.05','55732','52783','56368','59397.01','59399.01','61147.02','66737.01','66737.02','66737.03','62991.01','62991.02','62991.03','27574','55833.01','54170.01','54170.02','71762','59776','62935','4158','71395','72573','61440.01','61440.02','61440.03','61440.04','61440.05','61440.06','61440.07','61440.08','72573','59405','27030','1124','51279','57998.01','57998.02','57998.03','57998.06','29183'),

			'AMAZON_DHLSKU_US'   => array('55594','62149','69713.01','69713.02','69713.01','69713.02','60558.01','60558.02','60558.03','56524','56524.01','56524.02','56524.03','56524.04','68037.01','68037.02 ','68037.03','68037.04','68037.05','68037.06','68037.07','68037.08','68037.09','60895'),

			'AMAZON_DHLSKU_TS'   => array('0046','0490','22131.06','27025','27025','27025.01','27025.01','31004','31503','31505','35443','35443','46782.01','46782.01','46782.01','51328','52555','52995','53394','54347','54347.01','54347.02','54347.03','54365','55594','56964','57405','57748.01','58557.01','58710.01','58710.01','58710.02','58710.02','58711.01','58711.01','58711.02','58711.02','58714','58714.01','58714.02','59799','61242','61726.01','61726.02','64132.01','64132.01','64132.02','64132.02','64132.03','64132.03','68001','69043.01','69043.01','69043.02','69043.02','69058','69058','69713.01','69713.02','71079.01','71079.01','71079.02','71079.02','71090.01','71090.01','71090.02','71090.02','71090.03','71090.03','71181','71181','71538','71542','71640.01','71640.02','72528','73938.01','73938.02','73938.03','74104','74236','74293.01','74293.01','74293.02','74293.02','74474.01','74474.01','74474.02','74474.02','75331.01','75331.02','75987.01','75987.01','75987.02','75987.02','75987.03','75987.03','75987.04','75987.04','75987.05','75987.05','76180.01','76180.01','76180.02','76180.02','76180.03','76180.03','76180.04','76180.04'),
		);
		return isset($conf[$key]) ? $conf[$key] : $conf;
	}

	/**
	 * @desc 获取需求下载订单明细的记录的账号和平台订单号
	 * @return array
	 */
	public function getNeedDownDetailOrders($accountID) {
        $where = 'is_downdetail='.self::IS_DOWNDETAIL_NO;
        if ($accountID) {
            $where .= " and account_id='{$accountID}'";
        }
        $where .= " and order_status in('".implode("','", self::getAbleSyncOrderStatus() )."')";
        $order = "created_time asc";
        $limit = 5000;
        $res = $this->getListByCondition('account_id,amazon_order_id',$where,$order,$limit);
        $rtn = array();
        if (!empty($res)) {
            foreach ($res as $v) {
                $rtn[$v['account_id']][] = $v['amazon_order_id'];
            }
        }
        return $rtn;
	}	

    /**
     * [getOneByCondition description]
     * @param  string $fields [description]
     * @param  string $where  [description]
     * @param  mixed $order  [description]
     * @return array
     * @author yangsh
     */
    public function getOneByCondition($fields='*', $where='1',$order='') {
        $cmd = $this->dbConnection->createCommand();
        $cmd->select($fields)
            ->from($this->tableName())
            ->where($where);
        $order != '' && $cmd->order($order);
        $cmd->limit(1);
        return $cmd->queryRow();
    }

    /**
     * [getListByCondition description]
     * @param  string $fields [description]
     * @param  string $where  [description]
     * @param  mixed $order  [description]
     * @return array
     * @author yangsh
     */
    public function getListByCondition($fields='*', $where='1',$order='') {
        $cmd = $this->dbConnection->createCommand();
        $cmd->select($fields)
            ->from($this->tableName())
            ->where($where);
        $order != '' && $cmd->order($order);
        return $cmd->queryAll();
    }

    /**
     * [addAmazonLog description]
     * @param [type] $accountID [description]
     * @param [type] $status    [description]
     * @param [type] $message   [description]
     * @param [type] $eventName [description]
     */
    public function addAmazonLog($accountID,$status,$message,$eventName=AmazonLog::EVENT_SYNC_ORDER) {
        $logModel = new AmazonLog();
        return $logModel->getDbConnection()->createCommand()->insert(
            $logModel->tableName(), array(
                'account_id'    => $accountID,
                'event'         => $eventName,
                'start_time'    => date('Y-m-d H:i:s'),                         
                'status'        => $status ,
                'message'       => $message,
                'response_time' => date('Y-m-d H:i:s'),
                'end_time'      => date('Y-m-d H:i:s'),
                'create_user_id'=> intval(Yii::app()->user->id),
            )
        );
    }    

}