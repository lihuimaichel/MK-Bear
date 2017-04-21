<?php
/**
 * @desc Joom订单拉取
 * @author Gordon
 * @since 2015-06-22
 */
class JoomOrder extends JoomModel{
    
    const EVENT_NAME = 'getorder';
    
    /** @var object 拉单返回信息*/
    public $orderResponse = null;
    
    /** @var int 账号ID*/
    public $_accountID = null;
    
    /** @var string 异常信息*/
    public $exception = null;
    
    /** @var int 日志编号*/
    public $_logID = 0;
    
    /** @var boolean 交互结束标记*/
    public $finishMark = false;
    
    /** @var int 手续费比例*/
    public $_amtfeeRate = 0.15
            ;
    /** @var string joom订单状态*/
    const ORDER_STATE_APPROVED          = 'APPROVED';
    const ORDER_STATE_SHIPPED           = 'SHIPPED';
    const ORDER_STATE_REFUNDED          = 'REFUNDED';
    const ORDER_STATE_REQUIRE_REVIEW    = 'REQUIRE_REVIEW';
    
    const DEFAULT_CURRENCY = 'USD';
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 切换数据库连接
     * @see JoomModel::getDbKey()
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
    
    /**
     * @desc 获取拉单开始时间
     */
    public function getTimeSince($accountID){
        $lastLog = JoomLog::model()->getLastLogByCondition(array(
                'account_id'    => $accountID,
                'event'         => self::EVENT_NAME,
                'status'        => JoomLog::STATUS_SUCCESS,
        ));
        $lastEventLog = array();
        if( !empty($lastLog) ){
            $lastEventLog = JoomLog::model()->getEventLogByLogID(self::EVENT_NAME, $lastLog['id']);
        }
        return (!empty($lastEventLog) && $lastEventLog['complete_time'] != "0000-00-00 00:00:00") ? 
       	 		str_replace(" ", "T", date('Y-m-d H:i:s',strtotime($lastEventLog['complete_time'])-8*60*60)) 
  				: str_replace(" ", "T",  date('Y-m-d H:i:s',time() - 7*24*3600 - 3600*8));
    }
    
    /**
     * @desc 根据条件获取订单
     * @param int $accountID
     * @param array $params
     */
    public function getOrders($startTime){
        $accountID = $this->_accountID;
        $result = array();
        $request = new GetOrdersRequest();
        $request->setSinceTime($startTime);
        //抓取订单信息
        $index = 0;
        while( !$this->finishMark ){
            $request->setStartIndex($index);
            $response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
            //var_dump($response);
            //return false;
            if(isset($_REQUEST['bug'])){
	            echo "<pre>";
	            print_r($response);
	            echo "</pre>";
            }
            if( $request->getIfSuccess() ){
                try {
                    foreach($response->data as $order) {//循环订单信息
//                        echo "<pre>";
//                        var_dump($order);
//                        echo "</pre>";
//                        return false;
//                        exit;
                    	$dbTransaction = $this->dbConnection->getCurrentTransaction();
                    	if( !$dbTransaction ){
                    	    $dbTransaction = $this->dbConnection->beginTransaction();//开启事务
                    	}
                    	try{
	                        $this->orderResponse = $order->Order;
	                        /** 1.保存订单主数据*/
	                        $orderID = $this->saveOrderInfo();
	                        if($orderID){//保存失败已生成异常，这里主要会有不需要进行再次操作的情况下返回false
	                        	//throw new Exception("save order info failure");
	                        	/** 2.保存订单详情信息*/
	                        	$this->saveOrderDetail($orderID);
	                        	/** 3.保存交易信息*/
	                        	$this->saveTransaction($orderID);
	                        	/** 4.保存付款信息*/
	                        	$this->saveOrderPaypalTransactionRecord($orderID);
	                        }
	                        $dbTransaction->commit();
                    	}catch (Exception $e){
                    		$dbTransaction->rollback();
                    		$msg = Yii::t('ebay', 'Save Order Infomation Failed');
                   		 	throw  new Exception($e->getMessage());
                    	}
                    }
                    
                    if( count($response->data) < $request->_limit ){//抓取数量小于每页数量，说明抓完了
                        $this->finishMark = true;
                        break;
                    }
                    $index++;
                }catch (Exception $e){
                    $this->setExceptionMessage($e->getMessage());
                    return false;
                }
            }else{//抓取失败
                $this->setExceptionMessage($request->getErrorMsg());
                return false;
            }
        }
        return true;
    }
    
    /**
     * @desc 保存订单主信息
     * @param object $info
     */
    public function saveOrderInfo(){
        $order_id  = AutoCode::getCodeNew('order'); // 获取订单号
        if ( empty($order_id) ) {
            throw new Exception("getCodeNew Error");
        } else {
            $order_id = $order_id . 'JM';
        }
        $orderInfo = Order::model()->getOrderInfoByPlatformOrderID($this->orderResponse->order_id, Platform::CODE_JOOM);
        $order = $this->orderResponse;
        if( $order->state==self::ORDER_STATE_REFUNDED ){//退款订单
            if( !empty($orderInfo) && $orderInfo['ship_status']==Order::SHIP_STATUS_NOT ){//未出货的订单可以取消
                Order::model()->cancelOrders($orderInfo['order_id']);
            }
           // continue;//已退款订单跳过
           // throw new Exception("not need add");
           return false;
        }
        if( !empty($orderInfo) && $orderInfo['payment_status']==Order::PAYMENT_STATUS_END ){//存在已付款的订单，不更新
        	//continue;
        	//throw new Exception("not need update");
        	return false;
        }
        $orderID = !empty($orderInfo) ? $orderInfo['order_id'] : $order_id;//获取订单号， 拼接订单号，识别新老系统
        //订单主表数据
        $order->ShippingDetail->ship_country_name = Country::model()->getEnNameByAbbr(trim($order->ShippingDetail->country));
        if(!$order->ShippingDetail->ship_country_name){//为空时默认
                $order->ShippingDetail->ship_country_name = $order->ShippingDetail->country;
        }
        //====设置运送方式=====
        $shipcode = '';
	
	$insert_order = array(
                'order_id'              => $orderID,
                'platform_code'         => Platform::CODE_JOOM,
                'platform_order_id'     => trim($order->order_id),
                'account_id'            => $this->_accountID,
                'log_id'                => $this->_logID,
                'order_status'          => trim($order->state),
                'buyer_id'              => trim($order->buyer_id),
                'email'                 => '',
                'timestamp'             => date('Y-m-d H:i:s'),
                'created_time'          => $order->order_time,
                'last_update_time'      => $order->last_updated,
                'ship_cost'             => 0,   //floatval($order->shipping_cost) * intval($order->quantity),
                'subtotal_price'        => floatval($order->cost) * intval($order->quantity),
                'total_price'           => floatval($order->order_total),
                'currency'              => self::DEFAULT_CURRENCY,
                'ship_country'          => trim($order->ShippingDetail->country),
                'ship_country_name'     => $order->ShippingDetail->ship_country_name,
                'paytime'               => $order->order_time,
                'payment_status'        => Order::PAYMENT_STATUS_NOT,
                'ship_phone'            => isset($order->ShippingDetail->phone_number) ? trim($order->ShippingDetail->phone_number) : '',
                'ship_name'             => trim($order->ShippingDetail->name),
                'ship_street1'          => trim($order->ShippingDetail->street_address1),
                'ship_street2'          => isset($order->ShippingDetail->street_address2) ? trim($order->ShippingDetail->street_address2) : '',
                'ship_zip'              => isset($order->ShippingDetail->zipcode) ? trim($order->ShippingDetail->zipcode) : '',
                'ship_city_name'        => isset($order->ShippingDetail->city) ? trim($order->ShippingDetail->city) : '',
                'ship_stateorprovince'  => isset($order->ShippingDetail->state) ? trim($order->ShippingDetail->state) : (isset($order->ShippingDetail->city) ? trim($order->ShippingDetail->city) : ''),
                'ship_code'		=>	$shipcode,
                'ori_create_time'      => date('Y-m-d H:i:s', strtotime($order->order_time)),//date('Y-m-d H:i:s', strtotime($order->order_time)+8*3600),
                'ori_update_time'      	=> date('Y-m-d H:i:s', strtotime($order->last_updated)),//date('Y-m-d H:i:s', strtotime($order->last_updated)+8*3600),
                'ori_pay_time'           => date('Y-m-d H:i:s', strtotime($order->order_time)),//date('Y-m-d H:i:s', strtotime($order->order_time)+8*3600),
        );
        
        $flag = Order::model()->saveOrderRecord($insert_order);
        if(!$flag) throw new Exception("save failure");
        return $orderID;
    }
    
    /**
     * @desc 保存订单详情信息
     */
    public function saveOrderDetail($orderID){
        $order = $this->orderResponse;
        //判断是否有收取运费
        $flagShipPrice = floatval($order->shipping_cost) > 0 ? true : false;
        $weightArr = array();//记录订单中的产品重量比重
        $totalWeight = 0;
        //2.订单详情数据
        //删除详情
        OrderDetail::model()->deleteOrderDetailByOrderID($orderID);
        $sku = encryptSku::getJoomRealSku($order->sku);
      
        $sku = $this->isAddZero($sku);
        	
        if(strlen($sku) < 4){
        	$sku = sprintf("%04d",$sku);//格式化产品号.不足位的在前加0
        }
        
        $realProduct = Product::model()->getRealSkuList($sku, $order->quantity);
        $newsku = trim($realProduct['sku']);
        $skuarr = array();
        $skuarr = explode(".",$newsku);
        if(isset($skuarr[1]) && $skuarr[1]!=''){
        	$skuInfo = Product::model()->getProductInfoBySku($newsku);
        	if(!$skuInfo && preg_match('[a-zA-Z]',substr($skuarr[1],strlen($skuarr[1])-1,1))){
        		$newsku = $skuarr[0].'.'.substr($skuarr[1],0,strlen($skuarr[1])-1);
        	}
        }
        $realProduct['sku'] = $newsku;
        $skuInfo = Product::model()->getProductInfoBySku($newsku);
        if(empty($skuInfo) )
        {
            $realProduct = array(
                'sku'       => 'unknow',
                'quantity'  => $order->quantity,
            );
            Order::model()->setOrderCompleteStatus(Order::COMPLETE_STATUS_PENGDING, $orderID);
        }
        if($skuInfo && $skuInfo['product_is_multi'] == Product::PRODUCT_MULTIPLE_MAIN){
        	$childSku = ProductSelectAttribute::model()->getChildSKUListByProductID($skuInfo['id']);
        	if(!empty($childSku)){
	        	$res = Order::model()->setExceptionOrder($orderID, OrderExceptionCheck::EXCEPTION_SKU_UNKNOWN, "sku:{$sku}为主sku");
	        	if(! $res){
	        		throw new Exception ( 'Set order Exception Failure: '.$orderID);
	        	}
        	}
        }
        
        //生成主键id，避免新老系统重复 2016-05-19去掉
        /* $orderItemID = OrderDetail::model()->getPlanInsertID(Platform::CODE_JOOM);
        if (empty($orderItemID)) {
        	throw new Exception(Yii::t('jd', 'Fetch Order Item ID Failure'));
        } */
        //保存
        $insert_order_detail = array(
        	//'id'					  => $orderItemID,							
                'transaction_id'          => $order->order_id,
                'order_id'                => $orderID,
                'platform_code'           => Platform::CODE_JOOM,
                'item_id'                 => $order->product_id,
                'title'                   => trim($order->product_name),
                'sku_old'                 => $sku,
                'sku'                     => $realProduct['sku'],
                'site'                    => '',
                'quantity_old'            => $order->quantity,
                'quantity'                => $realProduct['quantity'],
                'sale_price'              => $order->cost,
                'total_price'             => floatval($order->cost) * intval($order->quantity),
                'currency'                => self::DEFAULT_CURRENCY,
                'ship_price'              => floatval($order->shipping_cost) * intval($order->quantity),
				'create_time'			  => date('Y-m-d H:i:s')
        );
        //var_dump($insert_order_detail);return false;
        $flag = OrderDetail::model()->addOrderDetail($insert_order_detail);
        if(!$flag) throw new Exception("save order detail failure");
        //判断是否需要添加插头数据
       	$flag = OrderDetail::model()->addOrderAdapter(array(
                'order_id'	=>	$orderID,
                'ship_country_name'	=>	$order->ShippingDetail->ship_country_name,
                'platform_code'	=>	Platform::CODE_JOOM,
                'currency'	=>	self::DEFAULT_CURRENCY
        ),	$realProduct);
       	if(!$flag) throw new Exception("save order adapter failure");
       	
        return $flag;
    }
    
    /**
     * @desc 保存订单交易信息
     * @param string $orderID
     */
    public function saveTransaction($orderID){
        $order = $this->orderResponse;
        $fee_amt = floatval($order->order_total) * $this->_amtfeeRate;
        $fee_amt = round($fee_amt, 2);
        $flag = OrderTransaction::model()->saveTransactionRecord($order->order_id, $orderID, array(
                'order_id'              => $orderID,
                'first'                 => 1,
                'is_first_transaction'  => 1,
                'receive_type'          => OrderTransaction::RECEIVE_TYPE_YES,
                'account_id'            => $this->_accountID,
                'parent_transaction_id' => '',
                'order_pay_time'        => date('Y-m-d H:i:s', strtotime($order->order_time)),
                'amt'                   => $order->order_total,
                'fee_amt'               => $fee_amt,
                'currency'              => self::DEFAULT_CURRENCY,
                'payment_status'        => 'Completed',
                'platform_code'         => Platform::CODE_JOOM,
        ));//保存交易信息
        if($flag){
        	$flag = Order::model()->updateColumnByOrderID($orderID, array('payment_status' => Order::PAYMENT_STATUS_END));//保存为已付款
        	if($flag){
        		return $flag;
        	}
        }
        throw new Exception("save order trans failure");
    }
    
    /**
     * @desc 保存订单付款数据信息
     * @param unknown $orderID
     * @throws Exception
     * @return boolean
     */
    public function saveOrderPaypalTransactionRecord($orderID){
    	$order = $this->orderResponse;
        $fee_amt = floatval($order->order_total) * $this->_amtfeeRate;
        $fee_amt = round($fee_amt, 2);
    	$flag = OrderPaypalTransactionRecord::model()->savePaypalRecord($order->order_id, $orderID, array(
    			'order_id'              => 	$orderID,
    			'receive_type'          => 	OrderPaypalTransactionRecord::RECEIVE_TYPE_YES,
    			'receiver_business'		=> 	'',
    			'receiver_email' 		=> 	'unknown@vakind.com',
    			'receiver_id' 			=> 	'',
    			'payer_id' 				=> 	'',
    			'payer_name' 			=> 	isset($order->ShippingDetail->name) ? $order->ShippingDetail->name : '',
    			'payer_email' 			=> 	'',
    			'payer_status' 			=> 	'',
    			'parent_transaction_id'	=>	'',
    			'transaction_type'		=>	'',
    			'payment_type'			=>	'',
    			'order_time'			=>	date('Y-m-d H:i:s', strtotime($order->order_time)),
    			'amt'					=>	$order->order_total,
    			'fee_amt'				=>	$fee_amt,
    			'tax_amt'				=>	'',
    			'currency'				=>	self::DEFAULT_CURRENCY,
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
     * 格式化sku
     * @param sku $pro_code
     * @return Ambigous <string, mixed>
     */
    public function isAddZero($pro_code){
    	$end = strstr($pro_code,'.');
    	$length = strlen($end);
    	if($length==2){
    		if($product_info = Product::model()->getProductInfoBySku($pro_code)){
    			;
    		}else{
    			$pro_code .='0';
    		}
    	}elseif($length==4){
    		$model = '/[a-z]/i';
    		if(preg_match($model, $pro_code,$arr)){
    			if(!empty($arr)){
    				$pro_code = str_replace($arr[0],'', $pro_code);
    			}
    		}
    	}
    	if(substr($pro_code, -3)=='.00'){
    		$pro_code = str_replace(".00", "", $pro_code);
    	}
    	if(substr($pro_code, -2)=='.0'){
    		$pro_code = str_replace(".0", "", $pro_code);
    	}
    	return $pro_code;
    }
    /**
     * @desc 获取异常信息
     * @return string
     */
    public function getExceptionMessage(){
        return $this->exception;
    }
    
    /**
     * @desc 上传追踪号到joom平台
     * @param array $shippedData
     * @return boolean
     */
    public function setOrderShipped($shippedData) {
    	try {
    		$request = new FulfillAnOrderRequest();
    		$request->setID($shippedData['order_id']);
    		$request->setTrackingProvider($shippedData['shipped_carrier']);
  			$request->setTrackingNumber($shippedData['tracking_number']);
  			$request->setAccount($this->_accountID)->setRequest()->sendRequest()->getResponse();
  			if (!$request->getIfSuccess()) {
  				$this->setExceptionMessage($request->getErrorMsg());
  				return false;
  			}
  			return true;
    	} catch (Exception $e) {
    		$this->setExceptionMessage($e->getMessage());
    		return false;
    	}
    }
}