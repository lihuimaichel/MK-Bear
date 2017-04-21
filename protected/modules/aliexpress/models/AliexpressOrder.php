<?php
/**
 * @desc Aliexpress订单拉取
 * @author Gordon
 * @since 2015-06-25
 */
class AliexpressOrder extends AliexpressModel{
    
    const EVENT_NAME = 'getorder';
    
    /** @var object 拉单返回信息*/
    public $orderResponse = null;
    
    /** @var int 账号ID*/
    public $_accountID = null;
    /** @var 速卖通账号信息 */
    public $_accountInfo=null;
    
    /** @var string 异常信息*/
    public $exception = null;
    /**
     * @desc 订单留言
     * @var unknown
     */
    public $_orderMemo = null;
    /** @var int 日志编号*/
    public $_logID = 0;
    public $_orderIDs = null;
    public $_orderStatus = null;
    /** @var int 手续费比例*/
    public $_feeRate = 0.05;
    
    public $order_total_fee = 0;
    /** @var string aliexpress订单状态*/
    const PLACE_ORDER_SUCCESS               = 'PLACE_ORDER_SUCCESS';//等待买家付款
    const ORDER_IN_CANCEL                   = 'IN_CANCEL';//买家申请取消
    const ORDER_WAIT_SELLER_SEND_GOODS      = 'WAIT_SELLER_SEND_GOODS';//等待您发货
    const ORDER_SELLER_PART_SEND_GOODS      = 'SELLER_PART_SEND_GOODS';//部分发货
    const ORDER_WAIT_BUYER_ACCEPT_GOODS     = 'WAIT_BUYER_ACCEPT_GOODS';//等待买家收货
    const ORDER_FUND_PROCESSING             = 'FUND_PROCESSING';//买卖家达成一致，资金处理中
    const ORDER_FINISH                      = 'FINISH';//已结束的订单
    const ORDER_IN_ISSUE                    = 'IN_ISSUE';//含纠纷的订单
    const ORDER_IN_FROZEN                   = 'IN_FROZEN';//冻结中的订单
    const ORDER_WAIT_SELLER_EXAMINE_MONEY   = 'WAIT_SELLER_EXAMINE_MONEY';//等待您确认金额
    const ORDER_RISK_CONTROL                = 'RISK_CONTROL';//订单处于风控24小时中
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 切换数据库连接
     * @see AliexpressModel::getDbKey()
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
        
        $accmodel = AliexpressAccount::model();
        $this->_accountInfo = $accmodel->getAccountInfoById($accountID);
    }
    
    /**
     * @desc 设置异常信息
     * @param string $message
     */
    public function setExceptionMessage($message){
        $this->exception = $message;
    }
    
    /**
     * @desc 设置日志编号
     * @param int $logID
     */
    public function setLogID($logID){
        $this->_logID = $logID;
    }
    
    public function setOrderIDs($orderIds){
    	$this->_orderIDs = $orderIds;
    }
    
    public function setOrderStatus($orderStatus){
    	$this->_orderStatus = $orderStatus;
    }
    /**
     * @desc 获取拉单时间段
     */
    public function getTimeArr($accountID){
        $lastLog = AliexpressLog::model()->getLastLogByCondition(array(
                'account_id'    => $accountID,
                'event'         => self::EVENT_NAME,
                'status'        => AliexpressLog::STATUS_SUCCESS,
        ));
        $lastEventLog = array();
        if( !empty($lastLog) ){
            $lastEventLog = AliexpressLog::model()->getEventLogByLogID(self::EVENT_NAME, $lastLog['id']);
        }
        return array(
                'start_time'    => !empty($lastEventLog) ? date('Y-m-d H:i:s',strtotime($lastEventLog['end_time']) - 15*60-48*3600) : date('Y-m-d H:i:s',time() - 2*86400 - 8*3600),
                'end_time'      => date('Y-m-d H:i:s',time() - 15*3600),
        );
    }
    
    /**
     * @desc 根据条件获取订单
     * @param int $accountID
     * @param array $params
     */
    public function getOrders($timeArr){
        $accountID = $this->_accountID;
        $request = new FindOrderListRequest();
        $request->setStartTime(date('m/d/Y H:i:s', strtotime($timeArr['start_time'])));
        $request->setEndTime(date('m/d/Y H:i:s', strtotime($timeArr['end_time'])));
        if(empty($this->_orderStatus)){
        	$request->setOrderStatus(self::ORDER_WAIT_SELLER_SEND_GOODS);
        	$this->setOrderStatus(self::ORDER_WAIT_SELLER_SEND_GOODS);
    	}else{ 
        	$request->setOrderStatus($this->_orderStatus);
    	}
        //抓取订单信息
        $page = 1;
        while( $page <= ceil($request->_totalItem/$request->_pageSize) ){
            $request->setPage($page);
            $response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
            if (!$request->getIfSuccess()) {
            	$this->setExceptionMessage($request->getErrorMsg());
            	return false;
            }
            if(isset($_REQUEST['bug'])){
	            echo "<pre>";
	            print_r($response);
	            echo "</pre>";
            }
            if (!isset($response->totalItem) || $response->totalItem <= 0) {
            	$this->setExceptionMessage(Yii::t('aliexpress', 'No Order!'));
            	return false;
            }
            $request->_totalItem = $response->totalItem;	//总个数
            $page++;
            //循环保存订单
            foreach($response->orderList as $order) {
            	if (empty($order))
            		return false;
            	//$platformOrderID = trim($order->orderId);
                if ( strrpos($order->orderId, 'E') > 0 ) {//orderId不要加trim,加了会对转字符串有影响
                    $platformOrderID = number_format($order->orderId,0,'','');
                } else {
                    $platformOrderID = $order->orderId;
                }

            	// add lihy 2016-04-11
            	if($this->_orderStatus != self::ORDER_WAIT_SELLER_SEND_GOODS && (empty($this->_orderIDs) || !in_array($platformOrderID, $this->_orderIDs))){
            		
            		if(isset($_REQUEST['bug'])){
            			var_dump($platformOrderID);
            			var_dump($this->_orderStatus);
            			var_dump($this->_orderIDs);
            			echo "<pre>";
            			echo "===========order seller status continue=======<br/>";
            			echo "</pre>";
            		}
            		continue;
            	}
            	$orderInfo = Order::model()->getOrderInfoByPlatformOrderID($order->orderId, Platform::CODE_ALIEXPRESS);
            	if(isset($_REQUEST['bug'])){
            		echo "<pre>";
            		echo "===========orderinfo=======<br/>";
            		print_r($orderInfo);
            		echo "</pre>";
            	}
            	if(!empty($orderInfo)){
            		//存在订单了不需要继续拉单
            		continue;
            	}
            	
            	//1.保存订单主数据
            	// $autoCode = AutoCode::getInstance();
            	// $orderID = !empty($orderInfo) ? $orderInfo['order_id'] : $autoCode::getCode('order')."ALI";//获取订单号

            	//获取订单详情
            	$orderDetail = $this->findOrderDetails($platformOrderID);
            	if(isset($_REQUEST['bug'])){
            		echo "<pre>";
            		echo "===========order Detail=======<br/>";
            		print_r($orderDetail);
            		echo "</pre>";
            	}
            	if (empty($orderDetail)) 
            		return false;
            	$this->_orderMemo = $this->getOrderNote($order, $orderDetail);
            	//开启事务
            	$dbTransaction = $this->dbConnection->getCurrentTransaction();
            	if( !$dbTransaction ){
            		$dbTransaction = $this->dbConnection->beginTransaction();//开启事务
            	}
            	try {
                    $orderID = AutoCode::getCodeNew('order'); // 获取订单号                  
                    if (empty($orderID)) {
                        throw new Exception("订单号获取失败");
                    } else {
                        $orderID = $orderID. "ALI"; // 获取订单号
                    }

            		$flag = $this->saveOrderInfo($orderID, $order, $orderDetail);
            		if ($flag){
            			//2.保存订单详情
            			$this->saveOrderDetails($orderID, $order, $orderDetail);
            			//3.保存订单交易信息
            			$this->saveOrderTransaction($orderID, $order);
            			//4.保存订单留言信息
            			if($this->_orderMemo){
            				$this->saveOrderNote($orderID, $order, $this->_orderMemo);
            			}
            			//5.保存付款信息
            			$this->saveOrderPaypalTransactionRecord($orderID, $order);
            			$dbTransaction->commit();
            		}else {
            			throw new Exception('save order failure!');
            		}
            	} catch (Exception $e) {
            		$dbTransaction->rollback();
            		$this->setExceptionMessage($e->getMessage());
            		return false;
            	}
            }
        }
        return true;
    }
    /**
     * @desc 获取订单详情
     * @param unknown $platformOrderID
     * @throws Exception
     * @return boolean|mixed
     */
    public function findOrderDetails($platformOrderID) {
    	if (empty($platformOrderID)) return false;
 		try {
 			$request = new FindOrderByIdRequest();
 			$request->setOrderID($platformOrderID);
 			$response = $request->setAccount($this->_accountID)->setRequest()->sendRequest()->getResponse();
 			if(isset($_REQUEST['bug'])){
 				echo "<pre>";
 				echo "==========order item response ========";
 				print_r($response);
 				echo "</pre>";
 			}
			if (!$request->getIfSuccess()) {
				throw new Exception($request->getErrorMsg());
			}	
			return $response;
 		} catch (Exception $e) {
 			$this->setExceptionMessage($e->getMessage());
 			return false;
 		}	
    }
    
    /**
     * @desc 查出需要拉取订单详细信息的订单
     */
    public function fillOrders(){
        $accountID = $this->_accountID;
        $orders = $this->dbConnection->createCommand()
                ->select('*')
                ->from(self::tableName())
                ->where('payment_status = '.Order::PAYMENT_STATUS_NOT.' AND ship_country_name = "" AND platform_code = "'.Platform::CODE_ALIEXPRESS.'"')
                ->andWhere('account_id = "'.$this->_accountInfo['account'].'"')
                ->queryAll();
        foreach($orders as $order){
            $request = new FindOrderByIdRequest();
            $request->setOrderID($order['platform_order_id']);
            $response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
            if( isset($response->receiptAddress->contactPerson) ){
                //1.完善订单主信息
                $flag = Order::model()->updateColumnByOrderID($order['order_id'],array(
                    'order_status'          => 'Completed',
                    'email'                 => $response->buyerInfo->email,
                    'timestamp'             => date('Y-m-d H:i:s'),
                    'ship_cost'             => floatval($response->logisticsAmount->amount),
                    'subtotal_price'        => $response->initOderAmount->amount,
                    'total_price'           => floatval($response->orderAmount->amount),
                    'ship_country'          => trim($response->receiptAddress->country),
                    'ship_country_name'     => Country::model()->getEnNameByAbbr(trim($response->receiptAddress->country)),
                    'ship_phone'            => isset($response->receiptAddress->mobileNo) ? trim($response->receiptAddress->mobileNo) : trim($response->receiptAddress->phoneNumber),
                    'ship_name'             => trim($response->receiptAddress->contactPerson),
                    'ship_street1'          => trim($response->receiptAddress->detailAddress),
                    'ship_street2'          => isset($response->receiptAddress->address2) ? trim($response->receiptAddress->address2) : '',
                    'ship_zip'              => isset($response->receiptAddress->zip) ? trim($response->receiptAddress->zip) : '',
                    'ship_city_name'        => isset($response->receiptAddress->city) ? trim($response->receiptAddress->city) : '',
                    'ship_stateorprovince'  => isset($response->receiptAddress->province) ? trim($response->receiptAddress->province) : '',
                    'final_value_fee'       => ceil($response->orderAmount->amount) * $this->_feeRate,
                ));
                if( $flag ){
                    //2.插入订单详情信息
                    //判断是否有收取运费
                    $flagShipPrice = floatval($response->logisticsAmount->amount) > 0 ? true : false;
                    $weightArr = array();//记录订单中的产品重量比重
                    $totalWeight = 0;
                    //2.订单详情数据
                    //删除详情
                    OrderDetail::model()->deleteOrderDetailByOrderID($order['order_id']);
                    $detailAmt = array();
                    foreach($response->childOrderList as $item){
                        $skuOnline = $item->skuCode;
                        $sku = encryptSku::getAliRealSku($skuOnline);
                        $skuInfo = Product::model()->getProductInfoBySku($sku);
                        if( !empty($skuInfo) ){//可以查到对应产品
                            $realProduct = Product::model()->getRealSkuList($sku, $item->productCount);
                        }else{
                            $realProduct = array(
                                'sku'       => 'unknown',
                                'quantity'  => $item->productCount,
                            );
                            Order::model()->setOrderCompleteStatus(Order::COMPLETE_STATUS_PENGDING, $order['order_id']);
                        }
                        OrderDetail::model()->addOrderDetail(array(
                                'order_id'              => $order['order_id'],
                                'platform_code'         => Platform::CODE_ALIEXPRESS,
                                'item_id'               => $item->productId,
                                'transaction_id'        => $order['platform_order_id'],
                                'title'                 => $item->productName,
                                'sku_old'               => $sku,
                                'sku'                   => $realProduct['sku'],
                                'quantity_old'          => $item->productCount,
                                'quantity'              => $realProduct['quantity'],
                                'sale_price'            => $item->initOrderAmt->amount / $realProduct['quantity'],
                                'total_price'           => $item->initOrderAmt->amount,
                                'currency'              => $item->initOrderAmt->currencyCode,
                        ));
                        if($flagShipPrice){
                            $orderDetailID = Yii::app()->db->getLastInsertID();
                            if( !empty($skuInfo) ){
                                $realSkuInfo = Product::model()->getProductInfoBySku($realProduct['sku']);//获取真实发货产品的信息
                                if( $realSkuInfo ){
                                    $weightArr[$orderDetailID] = floatval($realSkuInfo['product_weight']) * intval($realProduct['quantity']);
                                    $totalWeight += $weightArr[$orderDetailID];
                                    $detailAmt[$orderDetailID] = $item->initOrderAmt->amount;
                                }
                            }
                        }
                    }
                    
                    if( $flagShipPrice ){
                        $shipPrice = floatval($response->logisticsAmount->amount);
                        if( count($weightArr)==1 ){
                            OrderDetail::model()->updateByPk($orderDetailID, array(
                                'ship_price'        => floatval($response->logisticsAmount->amount),
                                'final_value_fee'   => ceil($response->orderAmount->amount) * $this->_feeRate,
                            ));
                        }else{
                            foreach($weightArr as $detailID=>$weight){
                                $detailShipPrice = round($weight/$totalWeight*$shipPrice,2);
                                OrderDetail::model()->updateByPk($detailID, array(
                                    'ship_price'        => $detailShipPrice,
                                    'final_value_fee'   => ($detailAmt[$detailID] + $detailShipPrice) * $this->_feeRate,
                                ));
                            }
                        }
                    }
                    //3.插入订单交易信息
                    OrderTransaction::model()->saveTransactionRecord($order['platform_order_id'], $order['order_id'], array(
                            'order_id'              => $order['order_id'],
                            'first'                 => 1,
                            'is_first_transaction'  => 1,
                            'receive_type'          => OrderTransaction::RECEIVE_TYPE_YES,
                            'account_id'            => -1,
                            'parent_transaction_id' => '',
                            'order_pay_time'        => $order['paytime'],
                            'amt'                   => floatval($response->orderAmount->amount),
                            'fee_amt'               => 0,
                            'currency'              => $response->orderAmount->currencyCode,
                            'payment_status'        => 'Completed',
                            'platform_code'         => Platform::CODE_ALIEXPRESS,
                    ));//保存交易信息
                    Order::model()->updateColumnByOrderID($order['order_id'], array('payment_status' => Order::PAYMENT_STATUS_END));//保存为已付款
                }
            }
        }
    }
    
    /**
     * @desc 转换时间数据格式
     * @param date $dateOnline
     */
    private function formatDateLocal($dateOnline){
        $localTime = substr($dateOnline, 0, 14);
        $timezone = substr($dateOnline, -5, 3);
        $zonetimediff = (8 - (int)$timezone) * 3600;
        return date('Y-m-d H:i:s', strtotime($localTime) + $zonetimediff);
    }
    /**
     * @desc 转换时间
     * @param unknown $dateOnline
     * @return string
     */
    private function formatDate($dateOnline){
    	$localTime = substr($dateOnline, 0, 14);
    	return date('Y-m-d H:i:s', strtotime($localTime));
    }
    /**
     * @desc 保存订单主信息
     * @param object $info
     */
    public function saveOrderInfo($orderID, $order, $orderDetail){
    	//$shipType 
    	$shipType = $this->getShipType($order, $orderDetail);
    	if($this->_orderMemo){
    		$completeStatus = Order::COMPLETE_STATUS_PENGDING;
    	}else{
    		$completeStatus = Order::COMPLETE_STATUS_DEFAULT;
    	}
    	//总手续费用
    	$this->order_total_fee = floatval($orderDetail->orderAmount->amount*1.00*$this->_feeRate);
    	if(trim($orderDetail->receiptAddress->country) == 'AL'){
    		$orderDetail->receiptAddress->country = 'ALB';
    		$shipCountryName = "Albania";
    	}elseif(trim($orderDetail->receiptAddress->country) == 'MK'){
    		$shipCountryName = "Macedonia";
    	}
    	else{
    		$shipCountryName = Country::model()->getEnNameByAbbr(trim($orderDetail->receiptAddress->country));
    	}
    	// IF 当前时间-28H  < 创建时间
    	// 		更新时间 = 当前时间
    	// ELSE
    	//		更新时间 = 创建时间+28H
    	$USCurrenTime = date("Y-m-d H:i:s", time()-15*3600);
    	$USCurrenTimeBefore28 = date("Y-m-d H:i:s", time()-(15+28)*3600);
    	$USCreateTime = $this->formatDate($order->gmtPayTime);
    	if($USCurrenTimeBefore28 < $USCreateTime){
    		$USUpdateTime = $USCurrenTime;
    	}else{
    		$USUpdateTime = date("Y-m-d H:i:s", strtotime($USCreateTime)+28*3600);//+28H
    	}
    	$CNUpdateTime = strtotime($USUpdateTime)+15*3600;//北京时间戳
    	//>2h 
    	if(time()-$CNUpdateTime > 2*3600){
    		$CNUpdateTime = time()-2*3600;
    	}
    	$CNUpdateTime = date("Y-m-d H:i:s", $CNUpdateTime);
    	
    	$flag = Order::model()->saveOrderRecord(array(
    			'order_id'              => $orderID,
    			'platform_code'         => Platform::CODE_ALIEXPRESS,
    			'platform_order_id'     => trim($order->orderId),
    			'account_id'            => $this->_accountInfo['account'],
    			'log_id'                => $this->_logID,
    			'order_status'          => trim($order->orderStatus),
    			'buyer_id'              => trim($order->buyerLoginId),
    			'timestamp'             => date('Y-m-d H:i:s'),
    			'created_time'          => $this->formatDate($order->gmtCreate),
    			//'last_update_time'      => $this->formatDate($order->gmtPayTime),
    			'last_update_time'      => $USUpdateTime,
    			'paytime'               => $this->formatDate($order->gmtPayTime),
    			'currency'              => $order->payAmount->currencyCode,
    			'payment_status'        => Order::PAYMENT_STATUS_END,
    			'email'                 => isset($orderDetail->buyerInfo->Email) ? $orderDetail->buyerInfo->Email : '',
    			'ship_cost'             => floatval($orderDetail->logisticsAmount->amount),
    			'subtotal_price'        => floatval($orderDetail->initOderAmount->amount),
    			'total_price'           => floatval($orderDetail->orderAmount->amount),
    			'ship_country'          => trim($orderDetail->receiptAddress->country),
    			'ship_country_name'     => $shipCountryName,
    			'ship_phone'            => isset($orderDetail->receiptAddress->mobileNo) && !empty($orderDetail->receiptAddress->mobileNo) ? trim($orderDetail->receiptAddress->mobileNo) : trim($orderDetail->receiptAddress->phoneCountry) . '-' . trim($orderDetail->receiptAddress->phoneArea) . '-' . trim($orderDetail->receiptAddress->phoneNumber),
    			'ship_name'             => trim($orderDetail->receiptAddress->contactPerson),
    			'ship_street1'          => trim($orderDetail->receiptAddress->detailAddress),
    			'ship_street2'          => isset($orderDetail->receiptAddress->address2) ? trim($orderDetail->receiptAddress->address2) : '',
    			'ship_zip'              => isset($orderDetail->receiptAddress->zip) ? trim($orderDetail->receiptAddress->zip) : '',
    			'ship_city_name'        => isset($orderDetail->receiptAddress->city) ? trim($orderDetail->receiptAddress->city) : '',
    			'ship_stateorprovince'  => isset($orderDetail->receiptAddress->province) ? trim($orderDetail->receiptAddress->province) : '',
    			'final_value_fee'       => $this->order_total_fee,
    			'ship_code'				=> $shipType,
    			'complete_status'		=> $completeStatus,
    			//add lihy 2016-04-13
    			'ori_create_time'      	=> $this->formatDateLocal($order->gmtCreate),
    			'ori_update_time'      	=> $CNUpdateTime,
    			'ori_pay_time'          => $this->formatDateLocal($order->gmtPayTime),
    	));
    	if(!$flag){
    		throw new Exception($order->orderId.": Save Order Info failure! ");
    	}
    	return $orderID;
    }
    
    /**
     * @desc 平摊费用(产品价格和运费)
     * @param unknown $order
     * @return multitype:multitype:NULL
     */
    public function diffOrderAmount($order, $orderDetail){
    	
    	//总付款里面包括运费和产品总价
    	//详情列表里面每个SKU对应的物流费用都是总的物流费用
    	//总付款
    	$paidAmount = floatval($orderDetail->orderAmount->amount);
    	//总物流费用
    	$logisticsAmount = floatval($orderDetail->logisticsAmount->amount);
    	//总付的产品总价
    	$paidProductAmount = $paidAmount - $logisticsAmount;
    	//平摊规则
    	//按每个SKU价格占比
    	$childSkuPriceTotal = array();
    	$childSumPrice = 0;
    	$childSumLogistic = 0;
    	$itemCount = 0;
    	foreach ($order->productList as $item){
    		++$itemCount;
    		//floatval($item->totalProductAmount->amount+$item->logisticsAmount->amount),
    		$childSumPrice += $item->totalProductAmount->amount;
    		$childSumLogistic += 0;
    		$childSkuPriceTotal[$item->productId] = array(
    															'totalPrice'		=>	$item->totalProductAmount->amount,
    															'logisticPrice'		=>	0
    														);
    		
    	}
    	
    	$diffAmount = $paidProductAmount - $childSumPrice;
    	$diffLogisticAmount = $logisticsAmount - $childSumLogistic;
    	$loopIndex = 0;
    	$productTotal = 0;
    	$logisticTotal = 0;
    	if($diffAmount || $diffLogisticAmount){
	    	foreach ($childSkuPriceTotal as &$item){
	    		++$loopIndex;
	    		if($loopIndex<$itemCount){
	    			$rate = ($item['totalPrice']/$childSumPrice);
	    			$item['totalPrice'] += $rate*$diffAmount;
	    			$item['logisticPrice'] += $rate*$diffLogisticAmount;
	    			$productTotal += $item['totalPrice'];
	    			$logisticTotal += $item['logisticPrice'];
	    		}else{
	    			$item['totalPrice'] = $paidProductAmount - $productTotal;
	    			$item['logisticPrice'] = $logisticsAmount - $logisticTotal;
	    		}
	    	}	
    	}
    	return $childSkuPriceTotal;
    }
    
    /**
     * @desc 保存订单详情
     * @param unknown $orderID
     * @param unknown $order
     * @param unknown $orderDetail
     * @throws Exception
     */
    public function saveOrderDetails($orderID, $order, $orderDetail){
    	//删除原详情
    	OrderDetail::model()->deleteOrderDetailByOrderID($orderID);
    	//循环
    	if(!isset($order->productList) || !$order->productList){
    		throw new Exception($order->OrderId . ": Save Detail Failure! ");
    	}
    	$shipCoutryName = Country::model()->getEnNameByAbbr(trim($orderDetail->receiptAddress->country));
    	$orderDetailModel = new OrderDetail();
    	$orderExceptionMsg = "";
    	$childSKUPrice = $this->diffOrderAmount($order, $orderDetail);
    	foreach ($order->productList as $item){
	    	//生成主键id，避免新老系统重复
	    	//统一去掉，让数据库自动增长id，2016-05-19 lihy
	    	/* $orderItemID = OrderDetail::model()->getPlanInsertID(Platform::CODE_ALIEXPRESS);
	    	if(!$orderItemID){
	    		throw new Exception(Yii::t('jd', 'Fetch Order Item ID Failure'));
	    	} */
	    	$onlineSku = $item->skuCode;
	    	$sku = encryptSku::getAliRealSku($onlineSku);
	    	$skuInfo = Product::model()->getProductInfoBySku($sku);
	    	if($skuInfo){
	    		$realProduct = Product::model()->getRealSkuList($sku, $item->productCount);
	    	}else{
	    		$realProduct = array(
	    			'sku'	=>	'unkown',
	    			'quantity'	=>	$item->productCount	
	    		);
	    	}
	    	if($skuInfo && $skuInfo['product_is_multi'] == Product::PRODUCT_MULTIPLE_MAIN){
	    		$childSku = ProductSelectAttribute::model()->getChildSKUListByProductID($skuInfo['id']);
	    		!empty($childSku) && $orderExceptionMsg .= "sku:{$sku} 为主sku <br/>";
	    	}
	    	$flag = $orderDetailModel->addOrderDetail(array(
	    			//'id'					  => $orderItemID,
	    			'transaction_id'          => $item->orderId,
	    			'order_id'                => $orderID,
	    			'platform_code'           => Platform::CODE_ALIEXPRESS,
	    			'item_id'                 => $item->productId,
	    			'title'                   => trim($item->productName),
	    			'sku_old'                 => $sku,
	    			'sku'                     => $realProduct['sku'],
	    			'site'                    => '',
	    			'quantity_old'            => $item->productCount,
	    			'quantity'                => $realProduct['quantity'],
	    			'sale_price'              => $item->productUnitPrice->amount,
	    			'total_price'             => floatval($childSKUPrice[$item->productId]['totalPrice']),
	    			'currency'                => trim($item->productUnitPrice->currencyCode),
	    			'ship_price'              => floatval($childSKUPrice[$item->productId]['logisticPrice']),
	    			'final_value_fee'		  => floatval(($childSKUPrice[$item->productId]['totalPrice']+$childSKUPrice[$item->productId]['logisticPrice'])*1.00*$this->_feeRate)
	    	));
	    	if(!$flag) throw new Exception($order->orderId . ": Save Detail Failure! ");
	    	//判断是否需要添加插头数据
       		$flag = $orderDetailModel->addOrderAdapter(array(
       													'order_id'	=>	$orderID,
       													'ship_country_name'	=>	$shipCoutryName,
       													'platform_code'	=>	Platform::CODE_ALIEXPRESS,
       													'currency'	=>	trim($item->productUnitPrice->currencyCode)
       												),	$realProduct);
       		if(!$flag) throw new Exception($order->orderId . ": Save order adapter failure");
    	}
    	//判断是否有异常存在
    	if($orderExceptionMsg){
    		$res = Order::model()->setExceptionOrder($orderID, OrderExceptionCheck::EXCEPTION_SKU_UNKNOWN, $orderExceptionMsg);
    		if(! $res){
    			throw new Exception ( 'Set order Exception Failure: '.$orderID);
    		}
    	}
    	return true;
    }
    
    /**
     * @desc 保存交易信息
     * @param unknown $orderID
     * @param unknown $order
     * @throws Exception
     */
    public function saveOrderTransaction($orderID, $order){
    	 $flag = OrderTransaction::model()->saveTransactionRecord($orderID, $orderID, array(
                'order_id'              => $orderID,
                'first'                 => 1,
                'is_first_transaction'  => 1,
                'receive_type'          => OrderTransaction::RECEIVE_TYPE_YES,
                'account_id'            => $this->_accountInfo['account'],
                'parent_transaction_id' => '',
                'order_pay_time'        => $this->formatDate($order->gmtPayTime),
                'amt'                   => $order->payAmount->amount,
                'fee_amt'               => 0,
                'currency'              => $order->payAmount->currencyCode,
                'payment_status'        => 'Completed',
                'platform_code'         => Platform::CODE_ALIEXPRESS,
        ));//保存交易信息
    	if(!$flag) throw new Exception($order->orderId . ": Save Transaction Failure! ");
    	return true;
    }
    /**
     * @desc 获取订单留言
     * @param unknown $orderID
     * @param unknown $order
     * @param unknown $orderDetail
     * @return string
     */
    public function getOrderNote($order, $orderDetail){
    	$aliexpressResourceOwner = $this->_accountInfo['resource_owner'];
    	$tip = '<font color="red">订单备注:</font>';
    	$memo = "";
    	foreach($order->productList as $product){
    		if(isset($product->memo)&&!empty($product->memo)){
    			$memo .= $product->memo.';';
    		}
    	}
    	if($memo){
    		$memo = $tip.$memo;
    	}
    	//获取订单里面的留言
    	if($orderDetail->orderMsgList){
    		$memo .= '<font color="red">订单留言:</font>';
    		foreach ($orderDetail->orderMsgList as $msg){
    			if(mb_substr_count($msg->senderLoginId, $aliexpressResourceOwner)){
    				break;
    			}
    				
    			$memo .= $msg->content.' ';
    	
    		}
    	}
    	return $memo;
    }
    /**
     * @desc 保存订单留言
     * @param unknown $orderID
     * @param unknown $orderDetail
     */
    public function saveOrderNote($orderID, $order, $noteMsg){
    	OrderNote::model()->deleteAll("order_id=:order_id", array(':order_id'=>$orderID));
    	$flag = OrderNote::model()->addNoteRecord(array(
    		'order_id' 	=>	$orderID,
    		'note' 		=>	$noteMsg,
    		'status' 	=>	0,
    		'create_time' => date('Y-m-d H:i:s'),
    		'modify_time' =>	'',
    		'modify_user_id' 	=>	(int)Yii::app()->user->id,
    		'create_user_id'	=>	(int)Yii::app()->user->id,
    	));
    	
    	if(!$flag) throw new Exception($order->orderId . ": save note failure! ");
    	return true;
    }
    
    /**
     * @desc 保存付款信息
     * @param unknown $orderID
     * @param unknown $orderData
     * @throws Exception
     * @return boolean
     */
	public function saveOrderPaypalTransactionRecord($orderID, $orderData){
		$flag = OrderPaypalTransactionRecord::model()->savePaypalRecord($orderID, $orderID, array(
				'order_id'              => 	$orderID,
				'receive_type'          => 	OrderPaypalTransactionRecord::RECEIVE_TYPE_YES,
				'receiver_business'		=> 	'',
				'receiver_email' 		=> 	'unknown@vakind.com',
				'receiver_id' 			=> 	'',
				'payer_id' 				=> 	'',
				'payer_name' 			=> 	isset($orderData->buyerSignerFullname) ? $orderData->buyerSignerFullname : '',
				'payer_email' 			=>  '',
				'payer_status' 			=> 	'',
				'parent_transaction_id'	=>	'',
				'transaction_type'		=>	'',
				'payment_type'			=>	'',
				'order_time'			=>	$this->formatDate($orderData->gmtPayTime),
				'amt'					=>	floatval($orderData->payAmount->amount),
				'fee_amt'				=>	'',
				'tax_amt'				=>	'',
				'currency'				=>	$orderData->payAmount->currencyCode,
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
     * @desc 获取物流类型
     * @param unknown $account
     * @param unknown $countryCode
     * @param unknown $order
     * @param unknown $rescoureShipType
     * @return unknown|string
     */
    private function getShipType($order, $orderDetail){
    	$countryCode = trim($orderDetail->receiptAddress->country);
    	unset($orderDetail);
    	//有运费且设置免邮费的特殊国家可走贝邮宝,有巴西和阿根廷
    	$sepical_country = array('AR','BR');
    	$countries = array('BR','AR','CL','CO');//巴西，阿根廷，智利，哥伦比亚
    	$products = array();
    	$totalWeight = 0;
    	$shipStr = $shipServerName = "";
    	foreach ( $order->productList as $product ) {
    		$onlineSku = $product->skuCode;
    		$sku = encryptSku::getAliRealSku($onlineSku);
    		$products [] = $sku;
    		$productInfo = Product::model()->getProductInfoBySku($sku);
    		if(empty($productInfo)){
    			$productInfo['product_weight'] = 0;
    		}
    		$realProductInfo = Product::model()->getRealSkuList($sku, $product->productCount);
    		$totalWeight += floatval($productInfo['product_weight'])*$realProductInfo['quantity'];
    		if(empty($shipStr)){
    			$shipStr = $product->logisticsType;
    		}
    		if(empty($shipServerName)){
    			$shipServerName = $product->logisticsServiceName;
    		}
    	}
    	$rescoureShipType = $this->getShipTypeByOrderInfo($shipStr, $shipServerName);
    	if(false){//新老系统数据结构不一致，导致该判定无法确定
    		if($rescoureShipType != Logistics::CODE_GHXB && $rescoureShipType != ''){
    			return $rescoureShipType;
    		}
    		$shipType = '';
    		//如果发往国不是俄罗斯，则走贝邮宝
    		$ableCountry = array(
    				'IT','CZ','DK','BE','PT',
    				'IE','UK','AU','NL','IL',
    				'US','CA','FR','ES','BR',
    				'AR','DE','SE','NO',
    		);
    		
    		if($countryCode == 'RU'){ //俄罗斯走燕邮宝
    			$shipTypeArr = array (
						//Logistics::CODE_CM_YYB,
						Logistics::CODE_CM_SF,
						Logistics::CODE_CM_HUNGARY 
				); //燕邮宝和顺丰备选
				$tempShipCost = 0;
				foreach ( $shipTypeArr as $shipCode ) {
					$shipCost = Logistics::model()->getShipFee ($shipCode, $totalWeight, array('country'=>'Russia'));
					if ($tempShipCost == 0 && $shipCost > 0) {
						$tempShipCost = $shipCost;
						$shipType = $shipCode;
					}
					if ($shipCost > 0 && $tempShipCost != 0 && $shipCost < $tempShipCost) {
						$tempShipCost = $shipCost;
						$shipType = $shipCode;
					}
				}
				if (! $shipType) {
					$shipType = Logistics::CODE_CM_SF;
				}
    			
    		}else{
	    		if( in_array($countryCode, $countries) ){
	    			$shipType = Logistics::CODE_GHXB;
	    		}else{
	    			$flag = $this->chekSepcailProduct($products);
	    			//如果为特殊产品或者是美国下的单则由系统自行匹配物流方式
	    			if(!$flag && $countryCode != 'US'){
	    				$shipType = Logistics::CODE_CM_YWBJ;
	    			}
	    		}
    		}
    		return $shipType;
    	
    	}else{
    		if($rescoureShipType=='' && $countryCode=='RU'){
    			$flag = $this->chekSepcailProduct($products);
    			if(!$flag){//如果为特殊产品则由系统自行匹配物流方式
					$shipTypeArr = array (
							//Logistics::CODE_CM_YYB,
							Logistics::CODE_CM_SF,
							Logistics::CODE_CM_HUNGARY 
					); // 燕邮宝和顺丰备选
					$tempShipCost = 0;
					foreach ( $shipTypeArr as $shipCode ) {
						$shipCost = Logistics::model()->getShipFee ($shipCode, $totalWeight, array('country'=>'Russia'));
						if ($tempShipCost == 0 && $shipCost > 0) {
							$tempShipCost = $shipCost;
							$rescoureShipType = $shipCode;
						}
						if ($shipCost > 0 && $tempShipCost != 0 && $shipCost < $tempShipCost) {
							$tempShipCost = $shipCost;
							$rescoureShipType = $shipCode;
						}
					}
					if (! $rescoureShipType) {
						$rescoureShipType = Logistics::CODE_CM_SF;
					}
					// $rescoureShipType = Logistics::CODE_CM_YYB;
    			}
    		}
    		return $rescoureShipType;
    	}
    	
    }
    
    /**
     * @desc 功能:贝邮宝不允许走特殊产品，如果是危险品、带电池、粉末、液体等则返回true
     * @param unknown $products
     * @return boolean
     */
    public function chekSepcailProduct($productSkus){
    	$specailArr = ProductAttribute::model()->aliexpress_special_attribute;
    	$flag = false;
    	if(!is_array($productSkus)){
    		$productSkus = array($productSkus);
    	}
    	foreach($productSkus as $sku){
    		$attributes = Product::model()->getAttributeBySku($sku);
    		$attr = $attributes[$sku];//产品属性数组
    		$diff = array_intersect($specailArr, $attr);
    		if(!empty($diff)){//有交集，则为特殊产品
    			$flag = true;
    			break;
    		}
    	}
    	return $flag;
    }
    /**
     * @desc  根据订单中的物流信息获取可行的物流类型
     * @param unknown $ship_str
     * @param unknown $serverName
     * @return string
     */
    private function getShipTypeByOrderInfo($ship_str,$serverName)
    {
    	$ship_str = strtoupper($ship_str);
    	$serverName = strtoupper($serverName);
    	if($ship_str=='CPAM'){
    		$ship_type = Logistics::CODE_GHXB;//全部走挂号
    	}elseif($ship_str=='HKPAM'){
    		$ship_type = Logistics::CODE_GHXB;//全部走挂号
    	}elseif($ship_str=='EPACKET'){
    		$ship_type = Logistics::CODE_EUB;
    	}elseif($ship_str=='EMS'){
    		$ship_type = Logistics::CODE_EMS;
    	}elseif($ship_str=='DHL'){
    		$ship_type = Logistics::CODE_DHL;
    	}elseif($ship_str=='TNT'){
    		$ship_type = Logistics::CODE_TNT;
    	}elseif($ship_str=='UPS'){
    		$ship_type = Logistics::CODE_UPS;
    	}elseif($ship_str=='ARAMEX'){
    		$ship_type = Logistics::CODE_ARAMEX;
    	}elseif(($ship_str=='FEDEX IP')||($ship_str=='FEDEX_IP')||($serverName=='FEDEX IP')||($serverName=='FEDEX_IP')){
    		$ship_type = Logistics::CODE_FEDEX_IP;
    	}elseif(($ship_str=='FEDEX_IE')||($ship_str=='FEDEX IE')||($serverName=='FEDEX_IE')||($serverName=='FEDEX IE')){
    		$ship_type = Logistics::CODE_FEDEX_IE;
    	}else{
    		$ship_type = Logistics::CODE_OTHER;
    	}
    	return $ship_type;
    }
    
    /**
     * @desc 获取异常信息
     * @return string
     */
    public function getExceptionMessage(){
        return $this->exception;
    }


    public function getPlaceOrderSuccess($accountID, $dates){
        $page = 1;
        $orderListArr = array();

        try {
            $request = new FindOrderListRequest();
            $request->setStartTime(date('m/d/Y 00:00:00', strtotime($dates)));
            $request->setEndTime(date('m/d/Y 23:59:59', strtotime($dates)));
            $request->setOrderStatus(self::PLACE_ORDER_SUCCESS);

            while ($page <= ceil($request->_totalItem/$request->_pageSize)) {
                $request->setPage($page);
                $response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
                if($request->getIfSuccess()){
                    foreach ($response->orderList as $orderInfo){
                        if ( strrpos($orderInfo->orderId, 'E') > 0 ) {//orderId不要加trim,加了会对转字符串有影响
                            $platformOrderID = number_format($orderInfo->orderId,0,'','');
                        } else {
                            $platformOrderID = $orderInfo->orderId;
                        }

                        //判断是否有订单留言，如果有跳过
                        $requestMsg = new QueryMsgDetailListRequest();
                        $requestMsg->setChannelId($platformOrderID);
                        $requestMsg->setMsgSources('order_msg');
                        $responseMsg = $requestMsg->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
                        if(isset($responseMsg->result) && $responseMsg->result){
                            continue;
                        }

                        //通过收货信息获取国家
                        $requestReceiptInfo = new FindOrderReceiptInfoRequest();
                        $requestReceiptInfo->setOrderID($platformOrderID);
                        $responseReceiptInfo = $requestReceiptInfo->setAccount($accountID)->setRequest()->sendRequest()->getResponse();

                        //判断用户ID是否存在
                        if(!isset($orderInfo->buyerLoginId)){
                            continue;
                        }

                        $orderListArr[] = array(
                            'account_id'        => $accountID,
                            'buyer_login_id'    => $orderInfo->buyerLoginId,
                            'platform_order_id' => $platformOrderID,
                            'gmt_create'        => $orderInfo->gmtCreate,
                            'pay_amount'        => $orderInfo->payAmount->amount,
                            'receipt_country'   => isset($responseReceiptInfo->country)?$responseReceiptInfo->country:''
                        );
                    }
                    $request->_totalItem = $response->totalItem;    //总个数
                    $page++;
                } else {
                    $this->setExceptionMessage($request->getErrorMsg());
                    return false;
                }
            }
            return $orderListArr;
        } catch (Exception $e) {
            $this->getExceptionMessage($e->getMessage());
            return false;
        }
    }
}