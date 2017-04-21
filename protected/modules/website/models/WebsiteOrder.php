<?php
/**
 * @desc 网站平台订单模型
 * @author zhangF
 *
 */
class WebsiteOrder extends WebsiteModel {
	
	const EVENT_NAME = 'getorder';
	
	/** @var object 拉单返回信息*/
	public $orderResponse = null;
	
	/** @var int 账号ID*/
	public $_accountID = null;
	
	/** @var string 异常信息*/
	public $exception = null;
	
	/** @var int 日志编号*/
	public $_logID = 0;	
	
	/**
	 * @desc 获取模型
	 * @param system $className
	 * @return Ambigous <CActiveRecord, unknown, multitype:>
	 */
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see WebsiteModel::getDbKey()
	 */
	public function getDbKey() {
		return 'db_oms_order';
	}
	
	/**
	 * (non-PHPdoc)
	 * @see CActiveRecord::tableName()
	 */
	public function tableName() {
		return 'ueb_order';
	}
	
	/**
	 * @desc 获取拉单时间段
	 */
	public function getTimeArr($accountID){
		$lastLog = WebsiteLog::model()->getLastLogByCondition(array(
				'account_id'    => $accountID,
				'event'         => self::EVENT_NAME,
				'status'        => WebsiteLog::STATUS_SUCCESS,
		));
		return array(
				'start_time'    => !empty($lastLog) ? date('Y-m-d\TH:i:s',strtotime($lastLog['end_time']) - 30 * 24*3600) : date('Y-m-d\TH:i:s',time() - 30 * 24 * 3600),	//每次拉单往前覆盖3天
				'end_time'      => date('Y-m-d\TH:i:s',time()),
		);
	}
	
	/**
	 * @desc 设置账号ID
	 */
	public function setAccountID($accountID){
		$this->_accountID = $accountID;
	}
	
	/**
	 * @desc 设置日志编号
	 * @param int $logID
	 */
	public function setLogID($logID){
		$this->_logID = $logID;
	}

	/**
	 * @desc 拉取订单
	 * @throws Exception
	 * @return boolean
	 */
	public function getOrders() {
		set_time_limit(0);
		$timeArr = $this->getTimeArr($this->_accountID);
		$dbTransaction = self::model()->getDbConnection()->beginTransaction();
		try {
			$salesOrderListRequest = new SalesOrderListRequest();
			$salesOrderListRequest->setStartTime($timeArr['start_time']);
			$salesOrderListRequest->setEndTime($timeArr['end_time']);
			$salesOrderListRequest->setOrderStatus(array(SalesOrderListRequest::ORDER_STATUS_PENDING, SalesOrderListRequest::ORDER_STATUS_PROCESSING));
			$orderDatas = $salesOrderListRequest->setAccount($this->_accountID)->setRequest()->sendRequest()->getResponse();
			if ($salesOrderListRequest->getIfSuccess()) {
				foreach ($orderDatas as $orderData) {
					$websiteOrderId = $orderData['increment_id'];
					$orderInfo = Order::model()->getOrderInfoByPlatformOrderID($websiteOrderId, Platform::CODE_NEWFROG);
					//过滤已经完成的订单
					if(!empty($orderInfo) && $orderInfo['payment_status'] == Order::PAYMENT_STATUS_END)
						continue;
					$orderID = !empty($orderInfo) ? $orderInfo['order_id'] : AutoCode::getCode('order');//获取订单号
					//获取订单商品信息						
					$salesOrderInfoRequest = new SalesOrderInfoRequest();
					$salesOrderInfoRequest->setOrderID($websiteOrderId);
					$orderDataInfo = $salesOrderInfoRequest->setAccount($this->_accountID)->setRequest()->sendRequest()->getResponse();
					if (!$salesOrderInfoRequest->getIfSuccess()) {
						throw new Exception($salesOrderInfoRequest->getErrorMsg());
					}
					/** 1.保存订单主数据*/
					if (!($orderID = $this->saveOrderInfo($orderID, $orderDataInfo))) {
						$dbTransaction->rollback();
						return false;
					}
					/** 2.保存订单详情信息*/
					if (!$this->saveOrderItems($orderID, $orderDataInfo)) {
						$dbTransaction->rollback();
						return false;
					}
					/** 3.保存交易信息*/
					if (!$this->saveTransaction($orderID, $orderDataInfo)) {
						$dbTransaction->rollback();
						return false;
					}
				}
				$dbTransaction->commit();
				return true;
			} else {
				$this->setExceptionMessage($salesOrderListRequest->getErrorMsg());
				$dbTransaction->rollback();
				return false;
			}
		} catch (Exception $e) {
			$dbTransaction->rollback();
			$this->setExceptionMessage($e->getMessage());
			return false;
		}
	}	

	/**
	 * @desc 保存订单主表信息
	 * @param integer $orderId
	 * @param array $orderData
	 * @return integer
	 */
	public function saveOrderInfo($orderId, $orderData) {
		//订单主表数据
		$shippingMethod = $orderData['shipping_method'];
		if($shippingMethod=="flatrate_flatrate"){//判断是否走挂号
			$shipType = ShipType::CODE_GHXB;
		}
		if($shippingMethod=="ems_ems"){//判断是否走EMS
			$shippingMethod = ShipType::CODE_EMS;
		}
		$flag = Order::model()->saveOrderRecord(array(
				'order_id'              => $orderId,
				'platform_code'         => Platform::CODE_NEWFROG,
				'platform_order_id'     => trim($orderData['increment_id']),
				'account_id'            => $this->_accountID,
				'log_id'                => $this->_logID,
				'order_status'          => trim($orderData['status']),
				'buyer_id'              => trim($orderData['customer_id']),
				'email'                 => trim($orderData['customer_email']),
				'timestamp'             => date('Y-m-d H:i:s'),
				'created_time'          => $orderData['created_at'],
				'last_update_time'      => $orderData['updated_at'],
				'ship_cost'             => floatval($orderData['shipping_amount']),
				'subtotal_price'        => floatval($orderData['subtotal']),
				'total_price'           => floatval($orderData['grand_total']),
				'currency'              => $orderData['order_currency_code'],
				'ship_country'          => trim($orderData['shipping_address']['country_id']),
				'ship_country_name'     => Country::model()->getEnNameByAbbr(trim($orderData['shipping_address']['country_id'])),
				'payment_status'        => Order::PAYMENT_STATUS_NOT,
				'ship_code' 			=> $shippingMethod,
				'ship_phone'            => isset($orderData['shipping_address']['telephone']) ? $orderData['shipping_address']['telephone'] : '',
				'ship_name'             => $orderData['shipping_address']['firstname'] . ' ' . $orderData['shipping_address']['lastname'],
				'ship_street1'          => $orderData['shipping_address']['street'],
				'ship_street2'          => '',
				'ship_zip'              => $orderData['shipping_address']['postcode'],
				'ship_city_name'        => $orderData['shipping_address']['city'],
				'ship_stateorprovince'  => isset($orderData['shipping_address']['region']) ? $orderData['shipping_address']['region'] : '',
		));
		return $orderId;
	}
	
	/**
	 * @desc 保存订单商品信息
	 * @param integer $orderId
	 * @param array $itemsDatas
	 * @return boolean
	 */
	public function saveOrderItems($orderId, $orderInfo) {
		//删除原有订单详情
		OrderDetail::model()->deleteOrderDetailByOrderID($orderId);
		$orderItems = isset($orderInfo['items']) ? $orderInfo['items'] : array();
		$shipPrice = floatval($orderInfo['shipping_amount']);
		$totalWeight = 0.00;	//商品总重量
		$weightArr = array();
		foreach ($orderItems as $orderItem) {
			//过滤不需要添加的产品类型
			if (!($orderItem['product_type'] == 'configurable' || ($orderItem['product_type'] == 'simple' && $orderItem['parent_item_id'] == null))) {
				continue;
			}
			$totalWeight += $orderItem['row_weight'];
			$sku = trim($orderItem['sku']);
			$qty = (int)$orderItem['qty_ordered'];
			$realProduct = Product::model()->getRealSkuList($sku, $qty);
			//保存
			$flag = OrderDetail::model()->addOrderDetail(array(
					'transaction_id'          => '',
					'order_id'                => $orderId,
					'platform_code'           => Platform::CODE_NEWFROG,
					'item_id'                 => $orderItem['product_id'],
					'order_item_id'			  => $orderItem['item_id'],
					'title'                   => trim($orderItem['name']),
					'sku_old'                 => $sku,
					'sku'                     => $realProduct['sku'],
					'site'                    => '',
					'quantity_old'            => $qty,
					'quantity'                => $realProduct['quantity'],
					'sale_price'              => $orderItem['price'],
					'total_price'             => $orderItem['row_total'],
					'currency'                => $orderInfo['order_currency_code'],
			));
			if (empty($flag)) {
				$this->setExceptionMessage(Yii::t('website','Save Order Details Error'));
				return false;
			}
			$orderDetailId = OrderDetail::model()->getDbConnection()->getLastInsertID();
			$weightArr[$orderDetailId] = $orderItem['row_weight'];
		}

		//将运费平摊到每个SKU上
		$shipping_method=$orderInfo['shipping_method'];
		if ($shipping_method != "freeshipping_freeshipping")
		{
			if(count($weightArr)==1 && $orderDetailId)
			{//一个产品
	
				if (!OrderDetail::model()->updateAll(array('ship_price' => $shipPrice), 'id = :id', array(':id' => $orderDetailId))) {
					$this->setExceptionMessage(Yii::t('website','Save Order Details Error'));
					return false;
				}
	
			}else
			{
				//根据重量比例算收到的运费
				foreach ($weightArr as $orderDetailId => $weight){
					$itemShipPirce = 0.00;
					$rate = $weight / $totalWeight;
					$itemShipPirce = floor($shipPrice * $rate * 100) / 100; //两位小数,向下取
					if (!OrderDetail::model()->updateAll(array('ship_price' => $itemShipPirce), 'id = :id', array(':id' => $orderDetailId)))
					{
						$this->setExceptionMessage(Yii::t('website','Save Order Details Error'));
						return false;
					}
				}
			}
		}
		return true;
	}	

	/**
	 * 保存订单交易信息
	 * @param string $orderID
	 * @param array $orderInfo
	 * @return boolean
	 */
	public function saveTransaction($orderID, $orderInfo) {
		$statusHistory=$orderInfo['status_history'];
		$paymentMethod = isset($orderInfo['payment']['method']) ? $orderInfo['payment']['method'] : '';
		$transactions = array();
		$first = true;
		$paymentTime = isset($orderInfo['payment']['created_at']) ? $orderInfo['payment']['created_at'] : '';
		$orderData = array();
		$paymentStatus = Order::PAYMENT_STATUS_NOT;
		$completeStatus = Order::COMPLETE_STATUS_DEFAULT;
		$paymentAmount = isset($orderInfo['payment']['amount_paid']) ? $orderInfo['payment']['amount_paid'] : 0.00;
		$currencyCode = $orderInfo['order_currency_code'];
		$feeAmount = 0.00;
		//从状态历史里面抓取交易号
		foreach ($statusHistory as $history)
		{
			$comment=$history['comment'];
			if (preg_match('/"([A-Z0-9]+)"/',$comment,$matches)) {
				$transactions[] = $matches[1];
			}
			if(strpos($comment,'Invoice ')>=0 && strpos($comment,' was created')>0){
				$s001=str_replace('Invoice ','',$comment);
				$s002=str_replace(' was created','',$s001);
				$transactions[]=$s002;
			}
		}
		foreach ($transactions as $transaction) {
			if (empty($transaction)) continue;
			$first && $first = false;
			//检查交易是否完成
			if (strpos($paymentMethod, 'paypal') !== false) {
				//通过paypal接口查询交易是否完成
				$transactionDetail = PaypalAccount::model()->getPaypalTransactionByTransactionID($transaction, Platform::CODE_NEWFROG);
				//如果交易不存在或者存在,但是交易未完成则不保存订单
				//if (0) {
					if (empty($transactionDetail)) {
						$this->setExceptionMessage(Yii::t('website', 'Paypal Transaction ' . $transaction . ' Not Exists'));
						return false;
					} else if ($transactionDetail['PAYMENTSTATUS'] != 'Completed') {
						$this->setExceptionMessage(Yii::t('website', 'Paypal Transaction ' . $transaction . ' Have\'t Completed'));
						return false;
					}
				//}
				if ($first) {
					$paymentTime = $transactionDetail['ORDERTIME'];
					$paymentStatus = Order::PAYMENT_STATUS_END;
					//产生订单note处理表
					if(trim($transactionDetail['NOTE'])){
						$note_data = array(
								'orderid' => $orderID,
								'note' => trim($transactionDetail['NOTE']),
								'create_date' => date('Y-m-d H:i:s'),
						);
						OrderNote::model()->saveAttributes($note_data);
						$completeStatus = Order::COMPLETE_STATUS_PENGDING;	//若有NOTE,则先放进待处理
					}
				}
			} else if (strpos($paymentMethod, 'globalcollect') !== false) {
				$paymentAdditional = isset($orderInfo['payment']['additional_information']) ? $orderInfo['payment']['additional_information']: array();
				if ($paymentAdditional['STATUSID'] != '600') {
					$this->setExceptionMessage(Yii::t('website', 'GlobalCollect Transaction ' . $transaction , ' Have\'t Completed'));
					return false;
				}
				if ($first) {
					if (intval((strtotime("now")-strtotime($paymentTime))/86400)>2){
						$paymentTime=date("Y-m-d m:i:s");
					}
				}
			}
			OrderTransaction::model()->saveTransactionRecord($orderInfo['increment_id'], $orderID, array(
			'order_id'              => $orderID,
			'first'                 => ($first ? 1 : 0),
			'is_first_transaction'  => ($first ? 1 : 0),
			'receive_type'          => OrderTransaction::RECEIVE_TYPE_YES,
			'account_id'            => $this->_accountID,
			'parent_transaction_id' => '',
			'order_pay_time'        => $paymentTime,
			'amt'                   => $paymentAmount,
			'fee_amt'               => 0,
			'currency'              => $currencyCode,
			'payment_status'        => 'Completed',
			));//保存交易信息
		}
		$orderData['paytime'] = $paymentTime;
		$orderData['complete_status'] = $completeStatus;
		$orderData['payment_status'] = $paymentStatus;
		Order::model()->updateColumnByOrderID($orderID, $orderData);//保存订单主表更新数据
		return true;
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
}