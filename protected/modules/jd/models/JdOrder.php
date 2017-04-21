<?php
class JdOrder extends JdModel {
	
	/** @var integer 账号ID **/
	protected $_accountID = null;
	
	/** @var array 账号信息  **/
	protected $_accountInfo = null;
	
	protected $_errorMsg = null;
	
	/** @var int 日志编号*/
	public $_logID = 0;	
	
	/** @var string 调试日志 **/
	protected $_debugLog = null;
	
	/** @var array 客户黑名单 **/
	protected $_customerBlackList = array('Малетиков Максим Александр');
	
	const EVENT_NAME = 'get_order';
	const EVENT_NAME_UPLOAD_TN = 'upload_tracknumber';
	
	const ORDER_CURRENCY = 'USD';
	const ORDER_FEES_RATE = 0.08;
	
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}
	
	/**
	 * @desc 切换数据库连接
	 * @see LazadaModel::getDbKey()
	 */
	public function getDbKey() {
		return 'db_oms_order';
	}
	
	/**
	 * @desc 数据库表名
	 * @see CActiveRecord::tableName()
	 */
	public function tableName() {
		return 'ueb_order';
	}
	
	/**
	 * @desc 设置账号ID
	 */
	public function setAccountID($accountID){
		$this->_accountID = $accountID;
	
		$accmodel = JdAccount::model();
		$this->_accountInfo = $accmodel->getAccountInfoByID($accountID);
	}	
	
	
	/**
	 * @desc 获取拉单时间段
	 */
	public function getTimeArr($accountID, $eventName = null){
		if(empty($eventName))
			$eventName = self::EVENT_NAME;
		$lastLog = JdLog::model()->getLastLogByCondition(array(
				'account_id'    => $accountID,
				'event'         => $eventName,
				'status'        => JdLog::STATUS_SUCCESS,
		));
		$lastEventLog = array();
		if( !empty($lastLog) ){
			$lastEventLog = JdLog::model()->getEventLogByLogID($eventName, $lastLog['id']);
		}
		//每次拉当前时间前推七天到现在的单
		return array(
				'start_time'    => !empty($lastEventLog) ? date('Y-m-d H:i:s',strtotime($lastEventLog['end_time']) - 1800) : date('Y-m-d H:i:s',time() - 7 * 24 * 3600),
				'end_time'      => date('Y-m-d H:i:s',time()),
		);
	}
	
	/**
	 * @desc 设置日志编号
	 * @param int $logID
	 */
	public function setLogID($logID){
		$this->_logID = $logID;
	}
	
	/**
	 * @desc 根据条件获取订单
	 * @param int $accountID
	 * @param array $params
	 */
	public function getOrders($timeArr){
		$accountID = $this->_accountID;
		$request = new OrderSearchRequest();
		$request->setStartDate($timeArr['start_time']);
		$request->setEndDate($timeArr['end_time']);
		// $request->setOrderStatus(OrderSearchRequest::ORDER_STATUS_SHIPPED);
		$request->setOrderStatus(OrderSearchRequest::ORDER_STATUS_WAIT_DELIVERY);
		$page = $totalPage = 1;
		$this->_debugLog = '======================开始拉取订单(' . date('Y-m-d H:i:s') . ')==================' . "\n";
		$this->_debugLog .= '拉取时间段：' . $timeArr['start_time'] . '--------' . $timeArr['end_time'] . "\n";
		$orderInfoRequest = new GetOrderInfoByIDRequest();
		$defaultTotal = 0;
		$currentTotal = $total = $defaultTotal;
		try {
			while( $currentTotal <= $total ){
				$this->_debugLog .= '第' . ($page + 1) . '页' . "\n";
				$request->setStartRow($currentTotal);	//设置从多少条开始取
				$this->_debugLog .= '1.拉取订单列表' . "\n";
				$response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
				//$this->_debugLog .= '响应数据：' . var_export($response, true) . "\n";
				if(!empty($_GET['bug'])) {
					MHelper::printvar($response,false);
				}
				$currentTotal += OrderSearchRequest::ORDER_NUMBER_PER_PAGE;
				if ($request->getIfSuccess()) {
					if(empty($response->jingdong_ept_order_getorderIdsbyquery_responce->getorderidsbyquery_result)){
						return true;
					}
					$orderIDs =	array();
					if(isset($response->jingdong_ept_order_getorderIdsbyquery_responce->getorderidsbyquery_result->orderIds))
						$orderIDs = $response->jingdong_ept_order_getorderIdsbyquery_responce->getorderidsbyquery_result->orderIds;
					//$this->_debugLog .= '订单列表：' . var_export($orderIDs, true) . "\n";
					if($total == $defaultTotal)
						$total = $response->jingdong_ept_order_getorderIdsbyquery_responce->getorderidsbyquery_result->totalItem;
					
					if (empty($orderIDs)) {
						$this->_errorMsg = Yii::t('aliexpress', 'No Order!');
						return true;
					}
					foreach ($orderIDs as $key=>$oid){
						$orderInfo = Order::model()->getOrderInfoByPlatformOrderID($oid, Platform::CODE_JD);
						//过滤已经存在的单
						if(!empty($orderInfo))
							unset($orderIDs[$key]);
					}
					if(empty($orderIDs)){
						continue;
					}
					//获取订单详细
					$this->_debugLog .= '2.拉取订单详情' . "\n";
					$orderDetails = $this->getOrderDetialByOrderIDs($orderIDs);
					//$this->_debugLog .= '订单详情数据：' . var_export($orderDetails, true) . "\n";

					if (empty($orderDetails)) return false;
					$this->_debugLog .= '3.循环保存订单' . "\n";
					//保存每个订单
					foreach ($orderDetails as $orderDetail) {
						//锁定的单不需要拉取
						if ($orderDetail->locked == 2) continue;
						$flag = $this->saveOrderInfo($orderDetail);
						if (!$flag) {
							$this->_debugLog .= '失败原因：' . $this->_errorMsg . "\n";
							$this->_debugLog .= '-------------------该订单完成----------------' . "\n";
							return false;
						}
					}
					$this->_debugLog .= '-------------------该订单完成----------------' . "\n";
					
				} else {
					$this->_errorMsg = $request->getErrorMsg();
					$this->_debugLog .= '该页数据保存失败：' . "\n";
					$this->_debugLog .= '失败原因：' . $this->_errorMsg . "\n";
					return false;				
				}
				$this->_debugLog .= '该页订单完成' . "\n";
			}
		}catch (Exception $e){
			$this->_debugLog .= '#########'. $e->getMessage() .'########'. "\n";
			$this->_errorMsg = $e->getMessage();
			return false;
		}
		$this->_debugLog .= '=====================结束拉取订单（' . date('Y-m-d H:i:s') . "\n";
		return true;
	}
	
	/**
	 * @desc 拉取订单列表里面的订单的详情
	 * @param unknown $orderIDs
	 * @return boolean|unknown
	 */
	public function getOrderDetialByOrderIDs($orderIDs) {
		if (empty($orderIDs))
			return false;
		//拉取订单详情	
		try {
			$request = new GetOrderInfoByIDsRequest();
			$request->setOrderList($orderIDs);
			$response = $request->setAccount($this->_accountID)->setRequest()->sendRequest()->getResponse();
			if (!$request->getIfSuccess()) {
				$this->_errorMsg = $request->getErrorMsg();
				return false;
			}
			$orderDetails = array();
			if(isset($response->jingdong_ept_order_getorderinfobyids_responce->getorderinfobyjdpinandorderids_result->details))
				$orderDetails = $response->jingdong_ept_order_getorderinfobyids_responce->getorderinfobyjdpinandorderids_result->details;
			return $orderDetails;
		} catch (Exception $e) {
			$this->_errorMsg = $e->getMessage();
			return false;
		}
	}
	
	/**
	 * @desc 拉取单个订单的详情
	 * @param unknown $orderID
	 * @return boolean
	 */
	public function getOrderDetails($orderID) {
		if (empty($orderID)) return false;
		try {
		} catch (Exception $e) {
		}
	}
	
	/**
	 * @desc 保存订单数据
	 * @param unknown $order
	 * @return unknown|Ambigous <mixed, unknown>
	 */
	public function saveOrderInfo($order, $completeStatus = NULL) {
		if (empty($order)) return false;
        try {
	        $order_id  = AutoCode::getCodeNew('order'); // 获取订单号
	        if ( empty($order_id) ) {
	            throw new Exception("getCodeNew Error");
	        } else {
	            $order_id = $order_id . 'XJD';
	        }  

        	$platformOrderID = trim($order->orderId);
        	$this->_debugLog .= '-----------------平台订单号：' . $platformOrderID . "----------------\n";
        	//过滤订单状态不是已付款，等待发货
        	
        	//test 注释掉 20160223
        	
        	//if ($order->orderStatus != OrderSearchRequest::ORDER_STATUS_WAIT_DELIVERY) return true;
        	
        	//过滤掉京东发货的订单，运输方式为“JD-spsr to door”的
        	//if (strpos(trim($order->expressCorp), 'JD-spsr') !== false) return true;
        	$orderInfo = Order::model()->getOrderInfoByPlatformOrderID($platformOrderID, Platform::CODE_JD);
        	//过滤已经存在的单
        	if(!empty($orderInfo)) {
        		$this->_debugLog .= '订单号已存在：' . var_export($orderInfo,true) . "\n";
        		return true;
        	}
        	
	        //获取订单号
	    	$orderID = !empty($orderInfo) ? $orderInfo['order_id'] : $order_id;        	
        	$this->_debugLog .= '获取系统单号：' . $orderID . "\n";
        	        	
	        //订单号重复检查
	        $tmpOrder = Order::model()->getInfoByOrderId($orderID,'order_id');
	        if (!empty($tmpOrder)) {
	            throw new Exception($orderID.'订单号重复!');
	        } 

        	$dbTransaction = $this->dbConnection->getCurrentTransaction();
        	if (!$dbTransaction)
        		$dbTransaction = $this->dbConnection->beginTransaction();
        	$countryName = trim($order->country);
        	$countryName = trim(str_replace('The Republic of ','',$countryName));
        	if( $countryName == 'The State of Israel' ){
        		$countryName = 'Israel';
        	} else if ($countryName == 'Socialist Republic of Vietnam') {
        		$countryName = 'Vietnam';
        	} else if ($countryName == 'Slovakia') {
        		$countryName = 'Slovakia (Slovak Republic)';
        	}else if($countryName == 'Россия'){//Россия
        		$countryName = 'Russia';
        	}
        	$email = isset($order->email) ? trim($order->email) : '';
        	$payTime = isset($order->payTime) ? $this->formatDate($order->payTime) : '0000-00-00 00:00:00';

        	$couponDisBuy = isset($order->couponDisBuy) ? round($order->couponDisBuy,2) : 0;//平台优惠券
	        $totalShipFee = isset($order->shipCostBuy) ? round($order->shipCostBuy,2) : 0;//总运费
	      	$totalPrice = round($order->payTotalBuy + $couponDisBuy,2); //买家实际付款金额 + 平台优惠券 
	      	$totalDiscount = isset($order->promDisBuy) ? round($order->promDisBuy,2):0;//总优惠金额
        	$totalFvf = $this->getJdPlatformFees($order->payTotalBuy);//成交费
        	$totalTaxFee = 0;//税费,无

        	//组装订单主表数据
        	$orderData = array(          
	                'order_id'              => $orderID,
	                'platform_code'         => Platform::CODE_JD,
	                'platform_order_id'     => $platformOrderID,
	                'account_id'            => $this->_accountID,
	                'log_id'                => $this->_logID,
	                'order_status'          => trim($order->orderStatus),
	                'buyer_id'              => trim($order->userPin),
	                'created_time'          => $this->formatDate($order->bookTime),
	                'last_update_time'      => $payTime,
	                'ship_cost' 			=> $totalShipFee,//运费
	                'subtotal_price' 		=> 0,//产品总金额,由明细表计算
	                'total_price' 			=> $totalPrice,//订单交易金额
	                'final_value_fee'       => $totalFvf,//成交费
	                'insurance_amount' 		=> 0,//运费险,无
	                'currency'              => self::ORDER_CURRENCY,
	                'paytime'               => $payTime,
	                'payment_status'        => Order::PAYMENT_STATUS_END,
	        		'email'                 => $email,
	        		'timestamp'             => date('Y-m-d H:i:s'),
	        		'ship_country'          => Country::model()->getAbbrByEname($countryName),
	        		'ship_country_name'     => $countryName,
	        		'ship_phone'            => isset($order->phone) ? trim($order->phone) : trim($order->phone),
	        		'ship_name'             => trim($order->consignee),
	        		'ship_street1'          => trim($order->shipAddress1),
	        		'ship_street2'          => isset($order->shipAddress2)? trim($order->shipAddress2) : '',
	        		'ship_zip'              => isset($order->postCode) ? trim($order->postCode) : '',
	        		'ship_city_name'        => isset($order->city) ? trim($order->city) : '',
	        		'ship_stateorprovince'  => isset($order->state) ? trim($order->state) : '',
        			'ori_create_time'       => $this->formatDate($order->bookTime),
        			'ori_update_time'      	=> $payTime,
        			'ori_pay_time'          => $payTime,
	        );

        	//如果是有纠纷的单，标记为异常
        	if ($order->disputed == OrderSearchRequest::ORDER_HAS_DISPUTED){
        		$orderData['complete_status'] = Order::COMPLETE_STATUS_EXCEPTION;
        	}elseif($completeStatus === NULL && ($order->orderStatus == OrderSearchRequest::ORDER_STATUS_COMPLETED || $order->orderStatus == OrderSearchRequest::ORDER_STATUS_SHIPPED)){//订单已完成
        		$orderData['complete_status'] = Order::COMPLETE_STATUS_END;
        		$orderData['ship_status']	= Order::SHIP_STATUS_YES;
        		$orderData['refund_status'] = Order::REFUND_STATUS_DEFAULT;
        		//@todo 检测退款状态，目前没有办法检测
        		
        	}elseif ($order->orderStatus == OrderSearchRequest::ORDER_STATUS_CANCELED){//取消
        		$orderData['complete_status'] = Order::COMPLETE_STATUS_END;
        		$orderData['ship_status']	= Order::SHIP_STATUS_NOT;
        		$orderData['refund_status'] = Order::REFUND_STATUS_ALL;
        	}
        	//$this->_debugLog .= '订单主表数据 ：' . var_export($orderData, true) . "\n";
	        //插入订单主表数据
	        $flag = Order::model()->saveOrderRecord($orderData);
	        if (!$flag) {
	        	throw new Exception(Yii::t('jd', 'Save Order Info Failure'));
	        }

	      	$subTotalPrice = 0; //商品总金额
	      	foreach ($order->skus as $orderItem) {
	      		$subTotalPrice += ($orderItem->jdPriceBuy * $orderItem->quantity);//商品金额
	      	}
    	
	        $tmpshipFee = $tmpDiscount = $tmpFvf = 0;
	        //$tmpFeeAmt = $tmpTaxFee = $tmpInsuranceFee = 0;
	        $tmpItemSalePriceAllot = 0;

	        $skuCount = count($order->skus); //该订单包含的sku数量
	      	$itemIndex = 1;		//订单sku循环索引数字
	      	$orderSkuExceptionMsg = '';
	      	$logisticsTypeArr = array();
	        //插入订单详情表信息
	        foreach ($order->skus as $orderItem) {
	        	$skuOnline = trim($orderItem->rfId);

	        	//过滤京东发货的SKU
	        	if (strlen($skuOnline) >= 13) { 
	        		$this->_debugLog .= '京东发货订单，不拉取到系统';
	        		$dbTransaction->rollback();
	        		return true;
	        	}

	            //物流方式
	            if(isset($orderItem->carrierName)
	             && !in_array(trim($orderItem->carrierName),$logisticsTypeArr)) {
	                $logisticsTypeArr[] = trim($orderItem->carrierName);
	            }
	        
	        	$quantity = $orderItem->quantity;//购买数量
	        	$sku = encryptSku::getRealSku($skuOnline);
	        	$skuInfo = $sku ? Product::model()->getProductInfoBySku($sku) : null;
	        	
	        	$skuInfo2 = array();//发货sku信息
        		$pending_status  = OrderDetail::PEDNDING_STATUS_ABLE;
	        	if( !empty($skuInfo) ){//可以查到对应产品
	        		$realProduct = Product::model()->getRealSkuListNew($sku, $quantity, $skuInfo);
	        		if ($realProduct['sku'] == $skuInfo['sku']) {
	        			$skuInfo2 = $skuInfo;
	        		} else {
	        			$skuInfo2 = Product::model()->getProductInfoBySku($realProduct['sku']);
	        		}
	        	}

	        	if(empty($skuInfo) || empty($skuInfo2) ){
	        		$realProduct = array(
        				'sku'       => 'unknown',
        				'quantity'  => $quantity,
	        		);
	        		$orderSkuExceptionMsg .= 'sku信息不存在;';
	        	}
	        	
	        	if($skuInfo2 && $skuInfo2['product_is_multi'] == Product::PRODUCT_MULTIPLE_MAIN){
	        		$childSku = ProductSelectAttribute::model()->getChildSKUListByProductID($skuInfo2['id']);
	    			!empty($childSku) && $orderSkuExceptionMsg .= "sku:{$skuInfo2['sku']}为主sku;";
	        	}

	        	if ($orderSkuExceptionMsg != '') {
	        		$pending_status = OrderDetail::PEDNDING_STATUS_KF;
	        	}

	        	$unitSalePrice = floatval($orderItem->jdPriceBuy);//单价
	        	$itemSalePrice = $unitSalePrice * $quantity;//产品金额
	        	
	        	if($itemIndex == $skuCount){
					$shipFee            = round($totalShipFee - $tmpshipFee,2);//平摊后的运费
					$fvfAmt             = round($totalFvf - $tmpFvf,2);//平摊后的成交费
					$discount           = round($totalDiscount - $tmpDiscount,2);
					$itemSalePriceAllot = round($subTotalPrice - $totalDiscount - $tmpItemSalePriceAllot, 2);//平摊后的item售价
					$unitSalePriceAllot = round($itemSalePriceAllot/$quantity, 3);//平摊后的单价
	        	}else{
					$feeRate            = $itemSalePrice/$subTotalPrice;
					$shipFee            = round($feeRate * $totalShipFee,2);//平摊后的运费
					$fvfAmt             = round($feeRate * $totalFvf, 2);//平摊后的成交费
					$discount           = round($feeRate * $totalDiscount,2);//平摊后的优惠金额
					$itemSalePriceAllot = round($itemSalePrice - $discount, 2);//平摊后的item售价
					$unitSalePriceAllot = round($itemSalePriceAllot/$quantity, 3);//平摊后的单价 

					$tmpshipFee            += $shipFee;
					$tmpFvf                += $fvfAmt;   
					$tmpDiscount           += $discount;   
					$tmpItemSalePriceAllot += $itemSalePriceAllot; 						        	
	        	}
	        	$itemIndex++;

	        	//组装订单明细表数据	        	
	        	$orderItemData = array(
	        		'order_id'              => $orderID,
	        		'platform_code'         => Platform::CODE_JD,
	        		'transaction_id'        => $platformOrderID,
	        		'site'					=> '',
	        		'title'                 => $orderItem->title,
	        		'sku_old'               => $sku,
	        		'sku'                   => $realProduct['sku'],
	        		'quantity_old'          => $quantity,//购买数量
	        		'quantity'              => $realProduct['quantity'],//发货数量
	        		'sale_price'            => $unitSalePrice,//单价
	        		'total_price'           => round($itemSalePrice + $shipFee,2),//产品金额+平摊后的运费
	        		'ship_price'            => $shipFee,//平摊后的运费
	        		'final_value_fee'       => $fvfAmt,//平摊后的成交费
	        		'currency'              => self::ORDER_CURRENCY,
	        		'pending_status'        => $pending_status,
	        		'create_time'			=> date('Y-m-d H:i:s'),
	        	);
	        	
				//$this->_debugLog .= '订单详情表数据 ：' . var_export($orderItemData, true) . "\n";
	        	$detailID = OrderDetail::model()->addOrderDetail($orderItemData);
	        	if (!$detailID) {
	        		throw new Exception(Yii::t('jd', 'Save Order Items Failure'));
	        	}

		        //保存订单明细扩展表
		        $orderItemExtendRow = array(
		            'detail_id'              => $detailID,
		            'item_sale_price' 		 => $itemSalePrice,//产品金额(含成交费)
		            'item_sale_price_allot'  => $itemSalePriceAllot,//平摊后的产品金额(含成交费，减优惠金额)
		            'unit_sale_price_allot'  => $unitSalePriceAllot,//平摊后的单价(原销售单价-平摊后的优惠金额)
		            'coupon_price_allot'     => $discount,//平摊后的优惠金额
		            'tax_fee_allot'          => 0,//平摊后的税费
		            'insurance_amount_allot' => 0,//平摊后的运费险
		            'fee_amt_allot'          => 0,//平摊后的手续费
		        );
		        $flag = OrderDetailExtend::model()->addOrderDetailExtend($orderItemExtendRow);
		        if(!$flag) throw new Exception("save order detailExtend failure");	        	

	            //保存订单sku与销售关系数据
	            $orderSkuData = array(
	                'platform_code'         => Platform::CODE_JD,//平台code
	                'platform_order_id'     => $platformOrderID,//平台订单号
	                'online_sku'            => $skuOnline == '' ?'unknown':$skuOnline,//在线sku
	                'account_id'            => $this->_accountID,//账号id
	                'site'                  => '0',//站点
	                'sku'                   => $sku,//系统sku
	                'item_id'               => isset($orderItem->skuId) ? trim($orderItem->skuId):'unknown',//刊登号
	                'order_id'              => $orderID,//系统订单号
	            );   
	            $addRes = OrderSkuOwner::model()->addRow($orderSkuData);
	            if( $addRes['errorCode'] != '0' ){
	                 throw new Exception("save order OrderSkuOwner failure");
	            }  

	        	//检查是否需要添加转接头
	        	//$this->_debugLog .= '检查转接头开始' . "\n";
	        	$flag = OrderDetail::model()->addOrderAdapter($orderData, $orderItemData);
	        	if (!$flag) {
	        		throw new Exception(Yii::t('jd', 'Save Sku Adapter Failure'));
	        	}
	        	//$this->_debugLog .= '检查转接头成功' . "\n";
	        }

	        //更新订单信息
	        $orderUpdateData = array(
        		'subtotal_price'  => $subTotalPrice,
	        );
			//$this->_debugLog .= '订单主表更新数据 ：' . var_export($orderUpdateData, true) . "\n";        
			$flag = Order::model()->updateColumnByOrderID($orderID,$orderUpdateData);	        
	        if (!$flag) {
	        	throw new Exception(Yii::t('jd', 'Update Order Info Failure'));
	        }

	        //判断是否有异常存在
	        if($orderExceptionMsg != ''){
	        	$res = Order::model()->setExceptionOrder($orderID, OrderExceptionCheck::EXCEPTION_SKU_UNKNOWN, $orderExceptionMsg);
	        	if(! $res){
	        		throw new Exception ( 'Set order Exception Failure: '.$orderID);
	        	}
	        }	        

	        //保存订单扩展表数据        
	        $orderExtend = new OrderExtend();
	        $orderExtend->getDbConnection()->createCommand()->delete($orderExtend->tableName(),"platform_order_id='{$platformOrderID}' and platform_code='". Platform::CODE_JD ."'");
	        $orderExtend->getDbConnection()->createCommand()->insert($orderExtend->tableName(),array(
				'order_id'          => $orderID,
				'platform_code'     => Platform::CODE_JD,
				'platform_order_id' => $platformOrderID,//平台订单号
				'account_id'        => $this->_accountID,//账号id
				'tax_fee'           => 0,//总税费,无
				'coupon_price'      => $totalDiscount,//总优惠
				'currency' 			=> self::ORDER_CURRENCY,
	            'create_time'       => date('Y-m-d H:i:s'),
	            'payment_type' 		=> isset($order->payType) && trim($order->payType) != '0' ? trim($order->payType) : '',
	            'logistics_type' 	=> implode(',',$logisticsTypeArr),
	        ));

	        //组装订单交易数据
	        $orderTransactionData = array(
	        	'order_id'              => $orderID,
	        	'first'                 => 1,
	        	'is_first_transaction'  => 1,
	        	'receive_type'          => OrderTransaction::RECEIVE_TYPE_YES,
	        	'account_id'            => $this->_accountID,
	        	'parent_transaction_id' => '',
	        	'order_pay_time'        => isset($order->payTime) ? $this->formatDate($order->payTime) : '0000-00-00 00:00:00',
	        	'amt'                   => $totalPrice,
	        	'fee_amt'               => 0,
	        	'currency'              => self::ORDER_CURRENCY,
	        	'payment_status'        => 'Completed',
	        	'platform_code'         => Platform::CODE_JD,
	        );
			//$this->_debugLog .= '订单交易表数据 ：' . var_export($orderTransactionData, true) . "\n";
	        //插入订单交易信息
	        $flag = OrderTransaction::model()->saveTransactionRecord($platformOrderID, $orderID, $orderTransactionData);

      		if (!$flag) {
      			throw new Exception(Yii::t('jd', 'Save Transaction Info Failure'));
      		}
      		
      		//保存付款信息
      		$this->saveOrderPaypalTransactionRecord($orderID, $order);
      		
      		//如果有订单留言保存订单留言
      		if (isset($order->note) && !empty($order->note)) {
      			$orderNoteModel = new OrderNote();
      			$orderNoteData = array(
					'order_id'    => $orderID,
					'note'        => $order->note,
					'create_time' => date('Y-m-d H:i:s'),
					'modify_time' => date('Y-m-d H:i:s'),
      			);
				//$this->_debugLog .= '订单留言表数据 ：' . var_export($orderNoteData, true) . "\n";
				$flag = $orderNoteModel->addNoteRecord($orderNoteData);
      			if (!$flag) {
      				throw new Exception(Yii::t('jd', 'Save Order Notes Failure'));
      			}
      		}

      		if ($this->checkBlackList($order->consignee)) {
      			$orderNoteModel = new OrderNote();
      			$orderNoteData = array(
  					'order_id' => $orderID,
  					'note' => Yii::t('jd', 'This Customer In BlackList'),
  					'create_time' => date('Y-m-d H:i:s'),
  					'modify_time' => date('Y-m-d H:i:s'),
      			);
      			$flag = $orderNoteModel->addNoteRecord($orderNoteData);
      			if (!$flag) {
      				throw new Exception(Yii::t('jd', 'Save Order Notes Failure'));
      			}
      			Order::model()->setOrderCompleteStatus(Order::COMPLETE_STATUS_EXCEPTION, $orderID);
      		}

      		$dbTransaction->commit();
      		$this->_debugLog .= '订单添加成功' . "\n";
      		return true;
        } catch (Exception $e) {
        	$dbTransaction->rollback();
        	$this->_errorMsg = $e->getMessage();
        	$this->_debugLog .= '订单添加失败:' . $e->getMessage() . "\n";
        	return false;
        }	
	}
	
	/**
	 * @desc 保存付款信息
	 * @param unknown $orderID
	 * @param unknown $orderData
	 * @throws Exception
	 * @return boolean
	 */
	public function saveOrderPaypalTransactionRecord($orderID, $orderData){
		$flag = OrderPaypalTransactionRecord::model()->savePaypalRecord($orderData->orderId, $orderID, array(
				'order_id'              => 	$orderID,
				'receive_type'          => 	OrderPaypalTransactionRecord::RECEIVE_TYPE_YES,
				'receiver_business'		=> 	'',
				'receiver_email' 		=> 	'unknown@vakind.com',
				'receiver_id' 			=> 	'',
				'payer_id' 				=> 	'',
				'payer_name' 			=> 	isset($orderData->firstName) ? $orderData->firstName : '',
				'payer_email' 			=> 	isset($orderData->email) ? $orderData->email : '',
				'payer_status' 			=> 	'',
				'parent_transaction_id'	=>	'',
				'transaction_type'		=>	'',
				'payment_type'			=>	'',
				'order_time'			=>	isset($orderData->payTime) ? $this->formatDate($orderData->payTime) : '0000-00-00 00:00:00',
				'amt'					=>	floatval($orderData->payTotalBuy),// $orderData->couponDisBuy+
				'fee_amt'				=>	'',
				'tax_amt'				=>	'',
				'currency'				=>	self::ORDER_CURRENCY,
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
	 * @desc 将时间转化
	 * @param unknown $time
	 * @return string
	 */
	public function formatDate($time) {
		$time = substr($time, 0, 10);
		return date('Y-m-d H:i:s', $time);
	}
	
	/**
	 * @desc 获取京东成交费
	 * @param unknown $amount
	 * @return number
	 */
	public function getJdPlatformFees($amount) {
		$fees = $amount * 0.08;
		return $fees > 0.5 ? $fees : 0.5;
	}
	
	/**
	 * 检查客户名称是否在黑名单中
	 * @param string $customerName
	 * @return boolean
	 */
	public function checkBlackList($customerName = '') {
		if (in_array(trim($customerName), $this->_customerBlackList))
			return true;
		return false;	
	}
	
	
	/**
	 * @desc 将捆绑销售sku换算成对应数量
	 * @param unknown $sku
	 * @param unknown $quantity
	 * @return multitype:unknown Ambigous <number, unknown>
	 */
	public function checkSku($sku, $quantity, $salePrice) {
		$skuInfo = array();
		$skuArr = array(
			'92938.01'  => 2,
			'92938.02'  => 2,
			'92938.03'  => 2,
			'92938.04'  => 2,
			'92938.05'  => 2,
			'92938.06'  => 2,
			'92938.07'  => 2,
			'92938.08'  => 2,
			'92938.09'  => 2,
			'78739.01'  => 2,
			'78739.02'  => 2,
			'78739.03'  => 2,
			'78739.04'  => 2,
			'78739.05'  => 2,
			'78739.06'  => 2,
			'78739.07'  => 2,
			'78739.08'  => 2,
			'78739.09'  => 2,
			'78739.01'  => 2,
			'70103.01'  => 2,
			'70103.02'  => 2,
			'70103.03'  => 2,
			'70103.04'  => 2,
			'70103.05'  => 2,
			'70103.06'  => 2,
			'70103.07'  => 2,
		);
		$newQuantity = $quantity;
		$newSalePrice = $salePrice;
		if (array_key_exists($sku, $skuArr)) {
			$newQuantity = $quantity * $skuArr[$sku];
			$newSalePrice = round($salePrice / $skuArr[$sku], 2);
		}
		return array(
			'sku' => $sku,
			'quantity' => $newQuantity,
			'sale_price' => $newSalePrice,
		);
	}
	
	/**
	 * @desc 检查sku是否为异常SKU
	 * @param unknown $sku
	 */
	public function isExceptionSku($sku) {
		$skuArr = array('78913', '57405', '69135.05', '62844');
		if (in_array($sku, $skuArr))
			return true;
		return false;
	}
	
	/**
	 * @desc 设置订单发货
	 * @param unknown $shippedData
	 * @return boolean
	 */
	public function setOrderShipped($shippedData) {
		$orderID = null;
		$trackingNumber = null;
		if (isset($shippedData['order_id']))
			$orderID = $shippedData['order_id'];
		if (isset($shippedData['tracking_number']))
			$trackingNumber = $shippedData['tracking_number'];
		if (empty($orderID) || empty($trackingNumber)) {
			$this->_errorMsg = '无效的订单或者追踪号';
			return false;
		}
		
		$request = new DeliveryorderRequest();
		$request->setOrderId($orderID);
		$request->setExpressNo($trackingNumber);
		$response = $request->setAccount($this->_accountID)->setRequest()->sendRequest()->getResponse();
		//如果订单已经是发货状态了，则将新的追踪号用订单备注形式添加到平台
		if (isset($response->jingdong_ept_order_deliveryorder_responce->deliveryorder_result->message)
		&& $response->jingdong_ept_order_deliveryorder_responce->deliveryorder_result->message == 'order is not wait for delivery orderStatus=5')
		{
			$notes = 'Sorry for the mistake, the correct tracking number is ' . $trackingNumber;
			$flag = $this->updateJdOrderNotes($orderID, $notes);
			if (!$flag)
				return false;
			else
				return true;
		}
		if (!$request->getIfSuccess()) {
			$this->_errorMsg = $request->getErrorMsg();
			return false;
		}
		return true;
	}
	
	/**
	 * @desc 获取错误信息
	 */
	public function getErrorMessage() {
		return $this->_errorMsg;
	}
	
	/**
	 * @desc 获取拉单调试信息
	 * @return string
	 */
	public function getDebugLog() {
		return $this->_debugLog;
	}
	
	/**
	 * @desc 更新订单备注
	 * @param unknown $platformOrderID
	 * @param unknown $notes
	 * @return boolean
	 */
	public function updateJdOrderNotes($platformOrderID, $notes) {
		try {
			$request = new UpdateOrderNoteRequest();
			$request->setOrderId($platformOrderID);
			$request->setNote($notes);
			$response = $request->setAccount($this->_accountID)->setRequest()->sendRequest()->getResponse();
			if (!$request->getIfSuccess()) {
				$this->_errorMsg = $request->getErrorMsg();
				return false;
			}
			return true;
		} catch (Exception $e) {
			$this->_errorMsg = $e->getMessage();
			return false;
		}
	}
}