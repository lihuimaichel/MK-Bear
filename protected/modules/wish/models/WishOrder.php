<?php
/**
 * @desc Wish订单拉取
 * @author Gordon
 * @since 2015-06-22
 */
class WishOrder extends WishModel{
    
    const EVENT_NAME = 'getorder';
    const EVENT_NAME_CHECK = 'check_getorder';
    
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
    
    /** @var string wish订单状态*/
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
     * @see WishModel::getDbKey()
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
        $lastLog = WishLog::model()->getLastLogByCondition(array(
                'account_id'    => $accountID,
                'event'         => self::EVENT_NAME,
                'status'        => WishLog::STATUS_SUCCESS,
        ));
        $lastEventLog = array();
        if( !empty($lastLog) ){
            $lastEventLog = WishLog::model()->getEventLogByLogID(self::EVENT_NAME, $lastLog['id']);
        }
        return (!empty($lastEventLog) && $lastEventLog['complete_time'] != "0000-00-00 00:00:00") ? 
       	 		str_replace(" ", "T", date('Y-m-d H:i:s',strtotime($lastEventLog['complete_time']) - (8+6)*3600)) 
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
        $path = 'wish/getOrders/'.date("Ymd").'/'.$accountID.'/'.date("His");
        //抓取订单信息
        $index = 0;
        while( !$this->finishMark ){
            $request->setStartIndex($index);
            $response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
            //MHelper::writefilelog($path.'/response_'.$index.'.log', print_r($response,true)."\r\n");// fortest
            if( $request->getIfSuccess() ){
                try {
                    foreach($response->data as $order) {//循环订单信息
                    	// ============= 160906 =============
                    	// 只抓取审核通过的订单
                    	//APPROVED REQUIRE_REVIEW 
                    	if(trim($order->Order->state) != "APPROVED"){
                    		continue;
                    	}
                    	//==== START 改造开始 ========== 20160708
                    	$buyerID = trim($order->Order->buyer_id);
                    	$phoneNumber = isset($order->Order->ShippingDetail->phone_number) ? trim($order->Order->ShippingDetail->phone_number) : '';
                    	$flag1 = WishSpecialOrderAccount::model()->checkExistsByBuyerId($buyerID, WishSpecialOrderAccount::STATUS_YES);
                    	//$flag2 = $phoneNumber && WishSpecialOrderAccount::model()->checkExistsByBuyerPhone($phoneNumber, WishSpecialOrderAccount::STATUS_YES);
                    	if(isset($_REQUEST['bug'])){
                    		echo "=====check buyer_id===";
                    		var_dump($buyerID);
                    		var_dump($flag1);
                    		echo "=====check buyer_phone===";
                    		var_dump($phoneNumber);
                    		//var_dump($flag2);
                    		
                    	}
                    	if($flag1){
                    		$this->saveSpecOrderData($order);
                    	}else{
                    		$this->saveOrderData($order);
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
    
    public function saveOrderData($order){
    	$dbTransactionModel = $this;
    	//==== END 改造结束 ============
    	$dbTransaction = $dbTransactionModel->dbConnection->getCurrentTransaction();
    	if( !$dbTransaction ){
    		$dbTransaction = $dbTransactionModel->dbConnection->beginTransaction();//开启事务
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
    
    /**
     * @desc 保存订单主信息
     * @param object $info
     */
    public function saveOrderInfo(){
        $order_id  = AutoCode::getCodeNew('order'); // 获取订单号
        if ( empty($order_id) ) {
            throw new Exception("getCodeNew Error");
        } else {
            $order_id = $order_id . 'KF';
        }
    	$ship_type_arr = array(
    			'US' => Logistics::CODE_EUB,
    	);
        $orderInfo = Order::model()->getOrderInfoByPlatformOrderID($this->orderResponse->order_id, Platform::CODE_WISH);
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
        $autoCode = AutoCode::getInstance();
        $orderID = !empty($orderInfo) ? $orderInfo['order_id'] : $order_id;//获取订单号， 拼接订单号，识别新老系统
        //订单主表数据
        $order->ShippingDetail->ship_country_name = Country::model()->getEnNameByAbbr(trim($order->ShippingDetail->country));
		if(!$order->ShippingDetail->ship_country_name){//为空时默认
			$order->ShippingDetail->ship_country_name = $order->ShippingDetail->country;
		}
		//====设置运送方式=====
		$shipcode = '';
		$ship_type_flag = false;
		$battery_flag = false;//检测是否为特殊属性
		$order_attr = array();//记录订单属性
		
		
		if($this->_accountID != 1 && $this->_accountID !=2 && $this->_accountID != 3){
			$sku_online = $order->sku;
			$sku_online = encryptSku::getWishRealSku($sku_online);
		}else{
			$sku_online = $order->sku;
		}
		if(strlen($sku_online) < 4){
			$sku_online = sprintf("%04d",$sku_online);//格式化产品号.不足位的在前加0
		}
		
		//转换相关产品(批量销量)
		$new_info = Product::model()->getRealSkuList($sku_online, $order->quantity);
		$order_sku = trim($new_info['sku']);
		$skuarr = array();
		$skuarr = explode(".",$order_sku);
		if(isset($skuarr[1]) && $skuarr[1]!=''){
			$skuInfo = Product::model()->getProductInfoBySku($order_sku);
			if(!$skuInfo && preg_match('[a-zA-Z]',substr($skuarr[1],strlen($skuarr[1])-1,1))){
				$order_sku = $skuarr[0].'.'.substr($skuarr[1],0,strlen($skuarr[1])-1);
			}
		}
		$new_info['sku'] = $order_sku;
		
		$product_info = Product::model()->getProductInfoBySku($order_sku);
		$total_weight = 0;
		if($product_info){
			$total_weight = floatval($product_info['product_weight'])*intval($order->quantity);
		}
		//检测产品属性
		$attributes = self::model('ProductSelectAttribute')->getProductAttributeIds($order_sku);
		$order_attr = array_merge($order_attr, $attributes);
		$diff = array_intersect(self::model('ProductAttribute')->wish_special_attribute, $attributes);//有交集，则为wish特殊属性订单
		if(!empty($diff)){
			$battery_flag = true;
		}
		if($battery_flag){//为特殊属性，匹配云途挂号
			$chosenShip = array(Logistics::CODE_GHXB_YUNTUDD, Logistics::CODE_GHXB_SG, Logistics::CODE_BE);
			$minCost = 0;
			foreach($chosenShip as $ship){
				$tempCost = self::model("Logistics")->getShipFee($ship, $total_weight, array(
						'attributeid'		=>	array_unique($order_attr),
						'country'			=> $order->ShippingDetail->ship_country_name,
						'discount'			=> 1,
						'volume'			=> 0,
						'warehouse'			=> '',
						'platform_code' 	=> Platform::CODE_WISH,
				));
				$tempCost = floatval($tempCost);
				if( $tempCost > 0 && ($tempCost < $minCost || $minCost==0) ){//可以计算出运费,得到最低运费
					$minCost = $tempCost;
					$ship_type_flag = true;
					$shipcode = $ship;
				}
			}
		}
		if(!$ship_type_flag){//不是特殊属性的，匹配是否国家规定
			if(isset($ship_type_arr[trim($order->ShippingDetail->country)])){
				$tempCost = self::model("Logistics")->getShipFee($ship_type_arr[trim($order->ShippingDetail->country)], $total_weight, array(
						'attributeid'		=>	array_unique($order_attr),
						'country'			=> $order->ShippingDetail->ship_country_name,
						'discount'			=> 1,
						'volume'			=> 0,
						'warehouse'			=> '',
						'platform_code' 	=> Platform::CODE_WISH,
				));
				$tempCost = floatval($tempCost);
				if($tempCost > 0 ){
					$ship_type_flag = true;
					$shipcode = $ship_type_arr[trim($order->ShippingDetail->country)];
				}
			}
		}
		
		if(!$ship_type_flag){//以上都匹配不到，在广州挂号和深圳挂号中选便宜的
			
			$tempCost = self::model("Logistics")->getShipFee(Logistics::CODE_GHXB_GZ, $total_weight, array(
					'attributeid'		=>	array_unique($order_attr),
					'country'			=> $order->ShippingDetail->ship_country_name,
					'discount'			=> 1,
					'volume'			=> 0,
					'warehouse'			=> '',
					'platform_code' 	=> Platform::CODE_WISH,
			));
			$gzShip = floatval($tempCost);
			
			$tempCost = self::model("Logistics")->getShipFee(Logistics::CODE_GHXB_CN, $total_weight, array(
					'attributeid'		=>	array_unique($order_attr),
					'country'			=> $order->ShippingDetail->ship_country_name,
					'discount'			=> 1,
					'volume'			=> 0,
					'warehouse'			=> '',
					'platform_code' 	=> Platform::CODE_WISH,
			));
			$szShip = floatval($tempCost);
			
			if( $gzShip >= $szShip && $szShip > 0 ){
				$ship_type_flag = true;
				$shipcode = Logistics::CODE_GHXB_CN;
			}elseif($szShip > $gzShip && $gzShip > 0 ){
				$ship_type_flag = true;
				$shipcode = Logistics::CODE_GHXB_GZ;
			}else{
		
			}
		}
		if(!$ship_type_flag){
			$shipcode = Logistics::CODE_DHL_XB;
		}
		// == 设置运送方式结束 ==
		
        $flag = Order::model()->saveOrderRecord(array(
                'order_id'              => $orderID,
                'platform_code'         => Platform::CODE_WISH,
                'platform_order_id'     => trim($order->order_id),
                'account_id'            => $this->_accountID,
                'log_id'                => $this->_logID,
                'order_status'          => trim($order->state),
                'buyer_id'              => trim($order->buyer_id),
                'email'                 => '',
                'timestamp'             => date('Y-m-d H:i:s'),
                'created_time'          => $this->transactionUTCTimeFormat($order->order_time),
                'last_update_time'      => $this->transactionUTCTimeFormat($order->last_updated),
                'ship_cost'             => floatval($order->shipping_cost) * intval($order->quantity),
                'subtotal_price'        => floatval($order->cost) * intval($order->quantity),
                'total_price'           => floatval($order->order_total),
                'currency'              => self::DEFAULT_CURRENCY,
                'ship_country'          => trim($order->ShippingDetail->country),
                'ship_country_name'     => $order->ShippingDetail->ship_country_name,
                'paytime'               => $this->transactionUTCTimeFormat($order->order_time),
                'payment_status'        => Order::PAYMENT_STATUS_NOT,
                'ship_phone'            => isset($order->ShippingDetail->phone_number) ? trim($order->ShippingDetail->phone_number) : '',
                'ship_name'             => trim($order->ShippingDetail->name),
				'ship_street1'          => trim($order->ShippingDetail->street_address1),
				'ship_street2'          => isset($order->ShippingDetail->street_address2) ? trim($order->ShippingDetail->street_address2) : '',
				'ship_zip'              => isset($order->ShippingDetail->zipcode) ? trim($order->ShippingDetail->zipcode) : '',
				'ship_city_name'        => isset($order->ShippingDetail->city) ? trim($order->ShippingDetail->city) : (isset($order->ShippingDetail->state) ? trim($order->ShippingDetail->state) : ''),
				'ship_stateorprovince'  => isset($order->ShippingDetail->state) ? trim($order->ShippingDetail->state) : (isset($order->ShippingDetail->city) ? trim($order->ShippingDetail->city):''),
        		'ship_code'				=>	$shipcode,
        		'ship_status'			=>	Order::SHIP_STATUS_NOT,
        		'complete_status'		=>	Order::COMPLETE_STATUS_DEFAULT,
        		//add lihy 2016-04-13
        		'ori_create_time'      => date('Y-m-d H:i:s', strtotime($order->order_time)+8*3600),
        		'ori_update_time'      	=> date('Y-m-d H:i:s', strtotime($order->last_updated)+8*3600),
        		'ori_pay_time'           => date('Y-m-d H:i:s', strtotime($order->order_time)+8*3600),
        ));
        if(!$flag) throw new Exception("save failure");
        return $orderID;
    }
    /**
     * @desc UTC时间格式转换
     * @param unknown $UTCTime
     * @return mixed
     */
    public function transactionUTCTimeFormat($UTCTime){
    	$UTCTime = strtoupper($UTCTime);
    	$newUTCTime = str_replace("T", " ", $UTCTime);
    	$newUTCTime = str_replace("Z", "", $UTCTime);
		return $newUTCTime;
    }
    
    /**
     * @desc 保存订单详情信息
     */
    public function saveOrderDetail($orderID){
        $path = 'wish/saveOrderDetail/'.date("Ymd").'/'.$this->_accountID.'/'.date("His");
        $order = $this->orderResponse;
        $platformOrderID = trim($order->order_id);//平台订单号
        //判断是否有收取运费
        $flagShipPrice = floatval($order->shipping_cost) > 0 ? true : false;
        $weightArr = array();//记录订单中的产品重量比重
        $totalWeight = 0;
        //2.订单详情数据
        //删除详情
        OrderDetail::model()->deleteOrderDetailByOrderID($orderID);
        $skuOnline = trim($order->sku);//在线sku
        $sku = encryptSku::getWishRealSku($skuOnline);
        $sku = $this->isAddZero($sku);
            
        if(strlen($sku) < 4){
            $sku = sprintf("%04d",$sku);//格式化产品号.不足位的在前加0
        }
        $skuarr = array();
        $skuarr = explode(".",$sku);
        if(isset($skuarr[1]) && $skuarr[1]!=''){
            $skuInfo = Product::model()->getProductInfoBySku($sku);
            if(!$skuInfo && preg_match('/[a-zA-Z]/',substr($skuarr[1],strlen($skuarr[1])-1,1))){
                $sku = $skuarr[0].'.'.substr($skuarr[1],0,strlen($skuarr[1])-1);
            }
        }
        $realProduct = Product::model()->getRealSkuList($sku, $order->quantity);
        $newsku = trim($realProduct['sku']);
        
        $realProduct['sku'] = $newsku;
        $skuInfo = Product::model()->getProductInfoBySku($newsku);
        if(empty($skuInfo) )
        {
            $realProduct = array(
                'sku'       => 'unknow',
                'quantity'  => trim($order->quantity),
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
        //保存
        $orderItemRow = array(                      
                'transaction_id'          => trim($order->order_id),
                'order_id'                => $orderID,
                'platform_code'           => Platform::CODE_WISH,
                'item_id'                 => trim($order->product_id),
                'title'                   => trim($order->product_name),
                'sku_old'                 => $sku,
                'sku'                     => $realProduct['sku'],
                'site'                    => '',
                'quantity_old'            => trim($order->quantity),
                'quantity'                => $realProduct['quantity'],
                'sale_price'              => trim($order->cost),
                'total_price'             => floatval($order->cost) * intval($order->quantity),
                'currency'                => self::DEFAULT_CURRENCY,
                'ship_price'              => floatval($order->shipping_cost) * intval($order->quantity),
                'create_time'			  => date('Y-m-d H:i:s')
        );

        //通过海外仓和产品ID映射表(ueb_wish_overseas_warehouse_map)，把对应产品ID的海外仓ID添加到订单详情表
        $item_id = trim($order->product_id);
        if (!empty($item_id)){
            $wishOverseasWarehouseModel = new WishOverseasWarehouse();
            $wret = $wishOverseasWarehouseModel->getWarehouseInfoByProductID($item_id);
            if ($wret){
                $orderItemRow['warehouse_id'] = (int)$wret['overseas_warehouse_id'];
            }               
        }

        $flag = OrderDetail::model()->addOrderDetail($orderItemRow);
        if(!$flag) throw new Exception("save order detail failure");

        //判断是否需要添加插头数据
        $flag = OrderDetail::model()->addOrderAdapter(array(
                    'order_id'              =>  $orderID,
                    'ship_country_name'     =>  trim($order->ShippingDetail->ship_country_name),
                    'platform_code'         =>  Platform::CODE_WISH,
                    'currency'              =>  self::DEFAULT_CURRENCY
                ),  $realProduct);
        if(!$flag) throw new Exception("save order adapter failure");

        //保存订单sku与销售关系数据
        $orderSkuOwnerInfo = array(
            'platform_code'         => Platform::CODE_WISH,//平台code
            'platform_order_id'     => $platformOrderID,//平台订单号
            'online_sku'            => $skuOnline,//在线sku
            'account_id'            => $this->_accountID,//账号id
            'site'                  => 0,//站点
            'sku'                   => $orderItemRow['sku_old'],//系统sku
            'item_id'               => $orderItemRow['item_id'],//主产品id
            'order_id'              => $orderID,//系统订单号
        );              
        $flag = true;
        $addRes = OrderSkuOwner::model()->addRow($orderSkuOwnerInfo);
        if( $addRes['errorCode'] != '0' ){
            throw new Exception("Save OrderSkuOwnerInfo Failure");
            $flag = false;
        }
        
        return $flag;
    }
    
    /**
     * @desc 保存订单交易信息
     * @param string $orderID
     */
    public function saveTransaction($orderID){
        $order = $this->orderResponse;
        $flag = OrderTransaction::model()->saveTransactionRecord($order->order_id, $orderID, array(
                'order_id'              => $orderID,
                'first'                 => 1,
                'is_first_transaction'  => 1,
                'receive_type'          => OrderTransaction::RECEIVE_TYPE_YES,
                'account_id'            => $this->_accountID,
                'parent_transaction_id' => '',
                'order_pay_time'        => date('Y-m-d H:i:s', strtotime($order->order_time)),
                'amt'                   => $order->order_total,
                'fee_amt'               => 0,
                'currency'              => self::DEFAULT_CURRENCY,
                'payment_status'        => 'Completed',
                'platform_code'         => Platform::CODE_WISH,
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
    			'fee_amt'				=>	'',
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
    		if($product_info = Product::model()->getProductInfoBySku($pro_code)){k
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
     * @desc 上传追踪号到wish平台
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
    
    /**
     * @desc 保存指定订单
     * @throws Exception
     */
    public function saveSpecOrderData($order){
    	$dbTransactionModel = new WishSpecialOrder();
    	$dbTransaction = $dbTransactionModel->dbConnection->getCurrentTransaction();
    	if( !$dbTransaction ){
    		$dbTransaction = $dbTransactionModel->dbConnection->beginTransaction();//开启事务
    	}
    	try{
    		$this->orderResponse = $order->Order;
    		/** 1.保存订单主数据*/
    		$orderID = $this->saveSpecialOrderInfo();
    		if($orderID){//保存失败已生成异常，这里主要会有不需要进行再次操作的情况下返回false
    			//throw new Exception("save order info failure");
    			/** 2.保存订单详情信息*/
    			$this->saveSpecialOrderDetail($orderID);
    			/** 3.保存交易信息*/
    			$this->saveSpecialTransaction($orderID);
    			/** 4.保存付款信息*/
    			$this->saveSpecialOrderPaypalTransactionRecord($orderID);
    		}
    		$dbTransaction->commit();
    	}catch (Exception $e){
    		$dbTransaction->rollback();
    		$msg = Yii::t('ebay', 'Save Order Infomation Failed');
    		throw  new Exception($e->getMessage());
    	}
    }
    
    /**
     * @desc 保存订单主信息
     * @param object $info
     */
    public function saveSpecialOrderInfo(){
    	$ship_type_arr = array(
    			'US' => Logistics::CODE_EUB,
    	);
    	$orderInfo = WishSpecialOrder::model()->getOrderInfoByPlatformOrderID($this->orderResponse->order_id, Platform::CODE_WISH);
    	$order = $this->orderResponse;
    	if( $order->state==self::ORDER_STATE_REFUNDED ){//退款订单
    		if( !empty($orderInfo) && $orderInfo['ship_status'] == WishSpecialOrder::SHIP_STATUS_NOT ){//未出货的订单可以取消
    			//WishSpecialOrder::model()->cancelOrders($orderInfo['order_id']);
    		}
    		return false;
    	}
    	if( !empty($orderInfo) && $orderInfo['payment_status'] == WishSpecialOrder::PAYMENT_STATUS_END ){//存在已付款的订单，不更新
    		return false;
    	}
    	$autoCode = AutoCode::getInstance();
    	$orderID = !empty($orderInfo) ? $orderInfo['order_id'] : $autoCode::getCode('order')."KFS";//获取订单号， 拼接订单号，识别新老系统
    	//订单主表数据
    	$order->ShippingDetail->ship_country_name = Country::model()->getEnNameByAbbr(trim($order->ShippingDetail->country));
    	if(!$order->ShippingDetail->ship_country_name){//为空时默认
    		$order->ShippingDetail->ship_country_name = $order->ShippingDetail->country;
    	}
    	//====设置运送方式=====
    	$shipcode = '';
    	$ship_type_flag = false;
    	$battery_flag = false;//检测是否为特殊属性
    	$order_attr = array();//记录订单属性
    
    
    	if($this->_accountID != 1 && $this->_accountID !=2 && $this->_accountID != 3){
    		$sku_online = $order->sku;
    		$sku_online = encryptSku::getWishRealSku($sku_online);
    	}else{
    		$sku_online = $order->sku;
    	}
    	if(strlen($sku_online) < 4){
    		$sku_online = sprintf("%04d",$sku_online);//格式化产品号.不足位的在前加0
    	}
    
    	//转换相关产品(批量销量)
    	$new_info = Product::model()->getRealSkuList($sku_online, $order->quantity);
    	$order_sku = trim($new_info['sku']);
    	$skuarr = array();
    	$skuarr = explode(".",$order_sku);
    	if(isset($skuarr[1]) && $skuarr[1]!=''){
    		$skuInfo = Product::model()->getProductInfoBySku($order_sku);
    		if(!$skuInfo && preg_match('[a-zA-Z]',substr($skuarr[1],strlen($skuarr[1])-1,1))){
    			$order_sku = $skuarr[0].'.'.substr($skuarr[1],0,strlen($skuarr[1])-1);
    		}
    	}
    	$new_info['sku'] = $order_sku;
    
    	$product_info = Product::model()->getProductInfoBySku($order_sku);
    	$total_weight = 0;
    	if($product_info){
    		$total_weight = floatval($product_info['product_weight'])*intval($order->quantity);
    	}
    	//检测产品属性
    	$attributes = self::model('ProductSelectAttribute')->getProductAttributeIds($order_sku);
    	$order_attr = array_merge($order_attr, $attributes);
    	$diff = array_intersect(self::model('ProductAttribute')->wish_special_attribute, $attributes);//有交集，则为wish特殊属性订单
    	if(!empty($diff)){
    		$battery_flag = true;
    	}
    	if($battery_flag){//为特殊属性，匹配云途挂号
    		$chosenShip = array(Logistics::CODE_GHXB_YUNTUDD, Logistics::CODE_GHXB_SG, Logistics::CODE_BE);
    		$minCost = 0;
    		foreach($chosenShip as $ship){
    			$tempCost = self::model("Logistics")->getShipFee($ship, $total_weight, array(
    					'attributeid'		=>	array_unique($order_attr),
    					'country'			=> $order->ShippingDetail->ship_country_name,
    					'discount'			=> 1,
    					'volume'			=> 0,
    					'warehouse'			=> '',
    					'platform_code' 	=> Platform::CODE_WISH,
    			));
    			$tempCost = floatval($tempCost);
    			if( $tempCost > 0 && ($tempCost < $minCost || $minCost==0) ){//可以计算出运费,得到最低运费
    				$minCost = $tempCost;
    				$ship_type_flag = true;
    				$shipcode = $ship;
    			}
    		}
    	}
    	if(!$ship_type_flag){//不是特殊属性的，匹配是否国家规定
    		if(isset($ship_type_arr[trim($order->ShippingDetail->country)])){
    			$tempCost = self::model("Logistics")->getShipFee($ship_type_arr[trim($order->ShippingDetail->country)], $total_weight, array(
    					'attributeid'		=>	array_unique($order_attr),
    					'country'			=> $order->ShippingDetail->ship_country_name,
    					'discount'			=> 1,
    					'volume'			=> 0,
    					'warehouse'			=> '',
    					'platform_code' 	=> Platform::CODE_WISH,
    			));
    			$tempCost = floatval($tempCost);
    			if($tempCost > 0 ){
    				$ship_type_flag = true;
    				$shipcode = $ship_type_arr[trim($order->ShippingDetail->country)];
    			}
    		}
    	}
    
    	if(!$ship_type_flag){//以上都匹配不到，在广州挂号和深圳挂号中选便宜的
    			
    		$tempCost = self::model("Logistics")->getShipFee(Logistics::CODE_GHXB_GZ, $total_weight, array(
    				'attributeid'		=>	array_unique($order_attr),
    				'country'			=> $order->ShippingDetail->ship_country_name,
    				'discount'			=> 1,
    				'volume'			=> 0,
    				'warehouse'			=> '',
    				'platform_code' 	=> Platform::CODE_WISH,
    		));
    		$gzShip = floatval($tempCost);
    			
    		$tempCost = self::model("Logistics")->getShipFee(Logistics::CODE_GHXB_CN, $total_weight, array(
    				'attributeid'		=>	array_unique($order_attr),
    				'country'			=> $order->ShippingDetail->ship_country_name,
    				'discount'			=> 1,
    				'volume'			=> 0,
    				'warehouse'			=> '',
    				'platform_code' 	=> Platform::CODE_WISH,
    		));
    		$szShip = floatval($tempCost);
    			
    		if( $gzShip >= $szShip && $szShip > 0 ){
    			$ship_type_flag = true;
    			$shipcode = Logistics::CODE_GHXB_CN;
    		}elseif($szShip > $gzShip && $gzShip > 0 ){
    			$ship_type_flag = true;
    			$shipcode = Logistics::CODE_GHXB_GZ;
    		}else{
    
    		}
    	}
    	if(!$ship_type_flag){
    		$shipcode = Logistics::CODE_DHL_XB;
    	}
    	// == 设置运送方式结束 ==
    	$flag = WishSpecialOrder::model()->saveOrderRecord(array(
    			'order_id'              => $orderID,
    			'platform_code'         => Platform::CODE_WISH,
    			'platform_order_id'     => trim($order->order_id),
    			'account_id'            => $this->_accountID,
    			'log_id'                => $this->_logID,
    			'order_status'          => trim($order->state),
    			'buyer_id'              => trim($order->buyer_id),
    			'email'                 => '',
    			'timestamp'             => date('Y-m-d H:i:s'),
    			'created_time'          => $this->transactionUTCTimeFormat($order->order_time),
    			'last_update_time'      => $this->transactionUTCTimeFormat($order->last_updated),
    			'ship_cost'             => floatval($order->shipping_cost) * intval($order->quantity),
    			'subtotal_price'        => floatval($order->cost) * intval($order->quantity),
    			'total_price'           => floatval($order->order_total),
    			'currency'              => self::DEFAULT_CURRENCY,
    			'ship_country'          => trim($order->ShippingDetail->country),
    			'ship_country_name'     => $order->ShippingDetail->ship_country_name,
    			'paytime'               => $this->transactionUTCTimeFormat($order->order_time),
    			'payment_status'        => Order::PAYMENT_STATUS_NOT,
    			'ship_phone'            => isset($order->ShippingDetail->phone_number) ? trim($order->ShippingDetail->phone_number) : '',
    			'ship_name'             => trim($order->ShippingDetail->name),
    			'ship_street1'          => trim($order->ShippingDetail->street_address1),
    			'ship_street2'          => isset($order->ShippingDetail->street_address2) ? trim($order->ShippingDetail->street_address2) : '',
    			'ship_zip'              => isset($order->ShippingDetail->zipcode) ? trim($order->ShippingDetail->zipcode) : '',
    			'ship_city_name'        => isset($order->ShippingDetail->city) ? trim($order->ShippingDetail->city) : (isset($order->ShippingDetail->state) ? trim($order->ShippingDetail->state) : ''),
    			'ship_stateorprovince'  => isset($order->ShippingDetail->state) ? trim($order->ShippingDetail->state) : (isset($order->ShippingDetail->city) ? trim($order->ShippingDetail->city):''),
    			'ship_code'				=>	$shipcode,
    			'ship_status'			=>	Order::SHIP_STATUS_NOT,
    			'complete_status'		=>	Order::COMPLETE_STATUS_DEFAULT,
    			//add lihy 2016-04-13
    			'ori_create_time'      => date('Y-m-d H:i:s', strtotime($order->order_time)+8*3600),
    			'ori_update_time'      	=> date('Y-m-d H:i:s', strtotime($order->last_updated)+8*3600),
    			'ori_pay_time'           => date('Y-m-d H:i:s', strtotime($order->order_time)+8*3600),
    	));
    	if(!$flag) throw new Exception("save failure");
    	return $orderID;
    }
    
    /**
     * @desc 保存订单详情信息
     */
    public function saveSpecialOrderDetail($orderID){
        $path = 'wish/saveSpecialOrderDetail/'.date("Ymd").'/'.$this->_accountID.'/'.date("His");
        $order = $this->orderResponse;
        $platformOrderID = trim($order->order_id);//平台订单号
        //判断是否有收取运费
        $flagShipPrice = floatval($order->shipping_cost) > 0 ? true : false;
        $weightArr = array();//记录订单中的产品重量比重
        $totalWeight = 0;
        //2.订单详情数据
        //删除详情
        WishSpecialOrderDetail::model()->deleteOrderDetailByOrderID($orderID);
        $skuOnline = trim($order->sku);//在线sku
        $sku = encryptSku::getWishRealSku($skuOnline);
        $sku = $this->isAddZero($sku);
         
        if(strlen($sku) < 4){
            $sku = sprintf("%04d",$sku);//格式化产品号.不足位的在前加0
        }
        $skuarr = array();
        $skuarr = explode(".",$sku);
        if(isset($skuarr[1]) && $skuarr[1]!=''){
            $skuInfo = Product::model()->getProductInfoBySku($sku);
            if(!$skuInfo && preg_match('/[a-zA-Z]/',substr($skuarr[1],strlen($skuarr[1])-1,1))){
                $sku = $skuarr[0].'.'.substr($skuarr[1],0,strlen($skuarr[1])-1);
            }
        }
        $realProduct = Product::model()->getRealSkuList($sku, $order->quantity);
        $newsku = trim($realProduct['sku']);
        
        $realProduct['sku'] = $newsku;
        $skuInfo = Product::model()->getProductInfoBySku($newsku);
        if(empty($skuInfo) )
        {
            $realProduct = array(
                    'sku'       => 'unknow',
                    'quantity'  => trim($order->quantity),
            );
            WishSpecialOrder::model()->setOrderCompleteStatus(WishSpecialOrder::COMPLETE_STATUS_PENGDING, $orderID);
        }
        //保存
        $orderItemRow = array(
                'transaction_id'          => trim($order->order_id),
                'order_id'                => $orderID,
                'platform_code'           => Platform::CODE_WISH,
                'item_id'                 => trim($order->product_id),
                'title'                   => trim($order->product_name),
                'sku_old'                 => $sku,
                'sku'                     => $realProduct['sku'],
                'site'                    => 0,
                'quantity_old'            => trim($order->quantity),
                'quantity'                => $realProduct['quantity'],
                'sale_price'              => trim($order->cost),
                'total_price'             => floatval($order->cost) * intval($order->quantity),
                'currency'                => self::DEFAULT_CURRENCY,
                'ship_price'              => floatval($order->shipping_cost) * intval($order->quantity),
				'create_time'			  => date('Y-m-d H:i:s')
        );
        $flag = WishSpecialOrderDetail::model()->addOrderDetail($orderItemRow);
        if(!$flag) throw new Exception("save order detail failure");

        //判断是否需要添加插头数据
        $flag = WishSpecialOrderDetail::model()->addOrderAdapter(array(
                'order_id'            =>    $orderID,
                'ship_country_name'   =>    trim($order->ShippingDetail->ship_country_name),
                'platform_code'       =>    Platform::CODE_WISH,
                'currency'            =>    self::DEFAULT_CURRENCY
        ),  $realProduct);
        if(!$flag) throw new Exception("save order adapter failure");

        //@todo 一个特殊不需要这个
        //保存订单sku与销售关系数据
        
        return $flag;
    }
    
    /**
     * @desc 保存订单交易信息
     * @param string $orderID
     */
    public function saveSpecialTransaction($orderID){
    	$order = $this->orderResponse;
    	$flag = WishSpecialOrderTransaction::model()->saveTransactionRecord($order->order_id, $orderID, array(
    			'order_id'              => $orderID,
    			'first'                 => 1,
    			'is_first_transaction'  => 1,
    			'receive_type'          => WishSpecialOrderTransaction::RECEIVE_TYPE_YES,
    			'account_id'            => $this->_accountID,
    			'parent_transaction_id' => 0,
    			'order_pay_time'        => date('Y-m-d H:i:s', strtotime($order->order_time)),
    			'amt'                   => $order->order_total,
    			'fee_amt'               => 0,
    			'currency'              => self::DEFAULT_CURRENCY,
    			'payment_status'        => 'Completed',
    			'platform_code'         => Platform::CODE_WISH,
    	));//保存交易信息
    	if($flag){
    		$flag = WishSpecialOrder::model()->updateColumnByOrderID($orderID, array('payment_status' => Order::PAYMENT_STATUS_END));//保存为已付款
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
    public function saveSpecialOrderPaypalTransactionRecord($orderID){
    	$order = $this->orderResponse;
    	$flag = WishSpecialOrderPaypalTransactionRecord::model()->savePaypalRecord($order->order_id, $orderID, array(
    			'order_id'              => 	$orderID,
    			'receive_type'          => 	WishSpecialOrderPaypalTransactionRecord::RECEIVE_TYPE_YES,
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
    			'fee_amt'				=>	0.00,
    			'tax_amt'				=>	0.00,
    			'currency'				=>	self::DEFAULT_CURRENCY,
    			'payment_status' 		=> 	'Completed',
    			'note'					=>	'',
    			'modify_time'			=>	date('Y-m-d H:i:s')
    	));//保存交易付款信息
    	if($flag){
    		return true;
    	}
    	throw new Exception("save order trans paypal info failure");
    }
}