<?php
/**
 * @desc Wish订单拉取
 * @author lihy
 * @since 2016-11-11
 */
class WishOrderMain extends WishModel{
	
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
    
    private $_warehouseList = array();
    
    /** @var string wish订单状态*/
    const ORDER_STATE_APPROVED          = 'APPROVED';
    const ORDER_STATE_SHIPPED           = 'SHIPPED';
    const ORDER_STATE_REFUNDED          = 'REFUNDED';
    const ORDER_STATE_REQUIRE_REVIEW    = 'REQUIRE_REVIEW';//审核中
    
    const DEFAULT_CURRENCY = 'USD';

    const GET_ORDERNO_ERRNO = 1000;//获取订单号异常编号
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 切换数据库连接
     * @see WishModel::getDbKey()
     */
    public function getDbKey() {
        return 'db_wish';
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_wish_order_main';
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
     * getOneByCondition
     * @param  string $fields
     * @param  string $where 
     * @param  string $order 
     * @return array        
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
     * getListByCondition
     * @param  string $fields
     * @param  string $where 
     * @param  string $order 
     * @return array      
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
     * @desc 获取拉单开始时间
     */
    public function getTimeSince($accountID){
        $eventName = WishLog::EVENT_GETORDER;
        $lastLog = WishLog::model()->getLastLogByCondition(array(
                'account_id'    => $accountID,
                'event'         => $eventName,
                'status'        => WishLog::STATUS_SUCCESS,
        ));
        $lastEventLog = array();
        if( !empty($lastLog) ){
            $lastEventLog = WishLog::model()->getEventLogByLogID($eventName, $lastLog['id']);
        }
        return (!empty($lastEventLog) && $lastEventLog['complete_time'] != "0000-00-00 00:00:00") ? 
       	 		str_replace(" ", "T", date('Y-m-d H:i:s',strtotime($lastEventLog['complete_time']) -15*60 - 3600*8 )) 
  				: str_replace(" ", "T",  date('Y-m-d H:i:s',time() - 3*86400 - 3600*8));
    }
    
    /**
     * @desc 根据条件获取订单
     * @param int $accountID
     * @param array $params
     */
    public function getOrders($startTime){
        $path      = 'wish/getOrders/'.date("Ymd").'/'.$this->_accountID.'/'.date("His");
        $accountID = $this->_accountID;
        $result    = array();
        $request   = new GetOrdersRequest();
        $request->setSinceTime($startTime);
        //抓取订单信息
        $flag     = true;
        $errorMsg = "";        
        $index    = 0;
        while( !$this->finishMark ){
            $request->setStartIndex($index);
            $response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
            //MHelper::writefilelog($path.'/response_'.$index.'.log', print_r($response,true)."\r\n");// for test
            if( $request->getIfSuccess() ){
                foreach($response->data as $order) {//循环订单信息
                    $res = $this->saveOrderMainData($order->Order);
                    $flag &= $res;
                    if(!$res){
                        $errorMsg .= $this->getExceptionMessage()."<br/>";
                    }
                }                    
                if( count($response->data) < $request->_limit ){//抓取数量小于每页数量，说明抓完了
                    $this->finishMark = true;
                    break;
                }
                $index++;
            }else{//抓取失败
                $flag &= false;
                $errorMsg .= $request->getErrorMsg();
                break;
            }
        }
        //返回结果
        if(!$flag){
            $this->setExceptionMessage($errorMsg);
            return false;
        }       
        return true;
    }

    /**
     * @desc 根据条件获取订单
     * @param int $accountID
     * @param array $params
     */
    public function getChangeOrders($startTime){
        $path      = 'wish/getChangeOrders/'.date("Ymd").'/'.$this->_accountID.'/'.date("His");
        $accountID = $this->_accountID;
        $result    = array();
        $request   = new GetChangeOrdersRequest();
        $request->setSinceTime($startTime);
        //抓取订单信息
        $flag = true;
        $errorMsg = "";
        $index = 0;
        while( !$this->finishMark ){
            $request->setStartIndex($index);
            $response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
            //MHelper::writefilelog($path.'/response_'.$index.'.log', print_r($response,true)."\r\n");// for test
            if( $request->getIfSuccess() ){                
                foreach($response->data as $order) {//循环订单信息
                    $res = $this->saveOrderMainData($order->Order);
                    $flag &= $res;
                    if(!$res){
                        $errorMsg .= $this->getExceptionMessage()."<br/>";
                    }
                }                    
                if( count($response->data) < $request->_limit ){//抓取数量小于每页数量，说明抓完了
                    $this->finishMark = true;
                    break;
                }
                $index++;
            }else{//抓取失败
                $flag &= false;
                $errorMsg .= $request->getErrorMsg();
                break;
            }
        }
        //返回结果
        if(!$flag){
            $this->setExceptionMessage($errorMsg);
            return false;
        }
        return true;
    }

    /**
     * @desc 保存主表数据
     * @param unknown $order
     * @return Ambigous <number, boolean>
     */
    public function saveOrderMainData($order){
        $platformOrderID = trim($order->order_id);
        $orderStatus     = trim($order->state);
        $shipName        = trim($order->ShippingDetail->name);
        //mysql编码格式utf-8格式，不支持带四字节的字符串插入
        if ($shipName != '' && preg_match('/[\x{10000}-\x{10FFFF}]/u',$shipName) ) {
            $shipName = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $shipName);
        }
        $address1 = trim($order->ShippingDetail->street_address1);
        if ($address1 != '' && preg_match('/[\x{10000}-\x{10FFFF}]/u',$address1) ) {
            $address1 = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $address1);
        }        
        $address2 = isset($order->ShippingDetail->street_address2) ? trim($order->ShippingDetail->street_address2) : '';
        if ($address2 != '' && preg_match('/[\x{10000}-\x{10FFFF}]/u',$address2) ) {
            $address2 = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $address2);
        }
        $ship_state = isset($order->ShippingDetail->state) ? trim($order->ShippingDetail->state):'';
        if ($ship_state != '' && preg_match('/[\x{10000}-\x{10FFFF}]/u',$ship_state) ) {
            $ship_state = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $ship_state);
        }
        $ship_city = trim($order->ShippingDetail->city);
        if ($ship_city != '' && preg_match('/[\x{10000}-\x{10FFFF}]/u',$ship_city) ) {
            $ship_city = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $ship_city);
        }        

    	//组装数据
    	$orderData = array(
    			'order_id'     					=> $platformOrderID,
    			'account_id'            		=> $this->_accountID,
    			'log_id'                		=> $this->_logID,
    			'buyer_id'              		=> trim($order->buyer_id),
    			'transaction_id'				=> trim($order->transaction_id),
    			'product_name'					=> trim($order->product_name),
    			'quantity'						=> intval($order->quantity),
    			'tracking_number'				=> isset($order->tracking_number) ? $order->tracking_number : '',	
    			'state'							=> $orderStatus,
    			'tracking_confirmed'			=> isset($order->tracking_confirmed) ? $order->tracking_confirmed : '',
    			'product_id'					=> trim($order->product_id),
    			'variant_id'					=> trim($order->variant_id),
    			'shipping_provider'				=> isset($order->shipping_provider) ? $order->shipping_provider : '',
    			'sku'							=> trim($order->sku),
    			'sys_sku'						=> encryptSku::getWishRealSku(trim($order->sku)),
    			'size'							=> isset($order->size) ? $order->size : '',
    			'color'							=> isset($order->color) ? $order->color : '',
    			'product_image_url'				=> isset($order->product_image_url) ? $order->product_image_url : '',
    			'is_wish_express'				=> isset($order->is_wish_express) ? $order->is_wish_express : '',
    			'shiping'						=> isset($order->shipping) ? trim($order->shipping) : 0.00,
    			'shipping_cost'					=> isset($order->shipping_cost) ? trim($order->shipping_cost) : 0.00,
    			'price'							=> isset($order->price) ? trim($order->price) : 0.00,
    			'cost'							=> isset($order->cost) ? trim($order->cost) : 0.00,
    			'order_total'					=> isset($order->order_total) ? trim($order->order_total) : 0.00,
    			'shipped_date'					=> isset($order->shipped_date) ? $this->transactionUTCTimeFormat($order->shipped_date) : '0000-00-00',
    			'order_time'					=> isset($order->order_time) ? $this->transactionUTCTimeFormat($order->order_time) : '0000-00-00 00:00',
    			'last_updated'					=> isset($order->last_updated) ? $this->transactionUTCTimeFormat($order->last_updated) : '0000-00-00 00:00',
    			'shippingdetail_phone_number'	=> isset($order->ShippingDetail->phone_number) ? trim($order->ShippingDetail->phone_number) : '',
    			'shippingdetail_zipcode'       	=> isset($order->ShippingDetail->zipcode) ? trim($order->ShippingDetail->zipcode) : '',
    			'shippingdetail_city'   	    => $ship_city != '' ? $ship_city : ($ship_state != '' ? $ship_state : ''),
    			'shippingdetail_state'  		=> $ship_state != '' ? $ship_state : ($ship_city != '' ? $ship_city : ''),
	    		'shippingdetail_name'			=> $shipName,
    			'shippingdetail_country'		=> trim($order->ShippingDetail->country),
    			'shippingdetail_street_address1'=> $address1,
    			'shippingdetail_street_address2'=> $address2,
    			'days_to_fulfill'				=> isset($order->days_to_fulfill) ? $order->days_to_fulfill : 0,
    			'hours_to_fulfill'				=> isset($order->hours_to_fulfill) ? $order->hours_to_fulfill : 0,
    			'create_time'           		=> date('Y-m-d H:i:s'),
    			'update_time'           		=> date('Y-m-d H:i:s'),
    	);
    	
    	//判断是否存在
    	$row = $this->getDbConnection()->createCommand()
                                        ->from($this->tableName())
                        				->select("id,state,is_to_oms")
                        				->where("order_id=:order_id and account_id=:account_id", array(":order_id"=>$platformOrderID, ":account_id"=>$this->_accountID))
                        				->queryRow();
    	try{
	    	//更新或者修改
	    	if($row){ //update
                $orderPrimaryKeyID = $row['id'];
                /*检查已取消订单是否已同步OMS，对已同步订单先取消未发货的包裹然后标识订单未出货已完成--start--*/
                if($row['is_to_oms'] == 1 && self::ORDER_STATE_REFUNDED == $orderStatus ) {
                    $omsOrderInfo = Order::model()->getOrderInfoByPlatformOrderID($platformOrderID, Platform::CODE_WISH);
                    if(!empty($omsOrderInfo) && $omsOrderInfo['complete_status'] != Order::COMPLETE_STATUS_END && $omsOrderInfo['ship_status'] == Order::SHIP_STATUS_NOT ) {
                        $cancel_result = Order::model()->cancelOrders( $omsOrderInfo['order_id'] );
                        if( $cancel_result['status'] ){
                            $aSysTimeOrder = array('cancel_user_id'=>0,'cancel_time'=>date('Y-m-d H:i:s'));
                            $omsSysTimeOrderRow = SysTimeOrder::model()->getOneByCondition('id',"order_id='{$omsOrderInfo['order_id']}'");
                            if($omsSysTimeOrderRow) {
                                SysTimeOrder::model()->updateData($aSysTimeOrder,"id={$omsSysTimeOrderRow['id']}");
                            } else {
                                $aSysTimeOrder['order_id'] = $omsOrderInfo['order_id'];
                                SysTimeOrder::model()->insertData($aSysTimeOrder);
                            }
                            //添加订单修改记录
                            OrderUpdateLog::model()->addOrderUpdateLog($omsOrderInfo['order_id'],'系统检测到订单状态为:'.trim($orderStatus).',自动取消包裹及订单。');                             
                        }
                    }
                }
                /*检查已取消订单是否已同步OMS，对已同步订单先取消未发货的包裹然后标识订单未出货已完成--end--*/

	    		unset($orderData['create_time']);
                if($row['state'] == $orderStatus){
                    return true;
                }
	    		return $this->getDbConnection()->createCommand()->update($this->tableName(), $orderData, "id=:id", array(':id'=>$orderPrimaryKeyID));
	    	}else{ //add
	    		return $this->getDbConnection()->createCommand()->insert($this->tableName(), $orderData);
	    	}
    	}catch (Exception $e){
    		$this->setExceptionMessage($e->getMessage());
            return false;
    	}
    }
    
    /**
     * @desc 同步订单到oms系统
     * @param unknown $accountID
     * @param number $limit
     * @return number
     */
    public function syncOrderToOmsByAccountID($accountID, $limit = 1000, $platformOrderID=null,$maxDay=7){
        $maxDay = intval($maxDay);
        if($maxDay<=0 || $maxDay > 30) {
            $maxDay = 7;
        }
        $ctime = date('Y-m-d',strtotime('-'.$maxDay.' days'));
    	$cmd = $this->getDbConnection()->createCommand()
    									->from($this->tableName())
    									->where("account_id=:account_id and is_to_oms=0", array(":account_id"=>$accountID))
                                        ->andWhere(array('in','state',array('APPROVED','SHIPPED')))
                                        ->andWhere("order_time>'{$ctime}'")
    									->limit($limit);
        if (!empty($platformOrderID) ) {
            $cmd->andWhere("order_id=:orderid",array(':orderid'=>$platformOrderID));
        }
    	$orderList = $cmd->queryAll();
    	if($orderList){
            $getOrderNoFailureFlag = array();//获取订单号失败超过10次则退出程序
    		$syncTotal = 0;
    		$errorMsg = "";
    		foreach ($orderList as $order){
    			try{
	    			$buyerID = trim($order['buyer_id']);
	    			$phoneNumber = isset($order['shippingdetail_phone_number']) ? trim($order['shippingdetail_phone_number']) : '';
	    			$flag1 = WishSpecialOrderAccount::model()->checkExistsByBuyerId($buyerID, WishSpecialOrderAccount::STATUS_YES);
	    			if($flag1){
	    				$this->saveSpecOrderData($order);
	    			}else{
	    				$this->saveOrderData($order);
	    			}
	    			$syncTotal++;
	    			//更改本地状态
	    			$this->getDbConnection()->createCommand()
	    									->update($this->tableName(), array('is_to_oms'=>1, 'to_oms_time'=>date("Y-m-d H:i:s")), "id={$order['id']}");
    			}catch (Exception $e){
                    if (self::GET_ORDERNO_ERRNO == $e->getCode() ) {//获取订单号异常
                        $getOrderNoFailureFlag[] = $e->getMessage();
                    }
    				$errorMsg .= $e->getMessage()."<br/>";
    			}

                if (!empty($getOrderNoFailureFlag) && count($getOrderNoFailureFlag)>10) {
                    $errLogMessage = '获取订单号失败:'.implode(',',$getOrderNoFailureFlag);
                    echo $errLogMessage."<br>";
                    $this->addWishLog($accountID,WishLog::STATUS_FAILURE,$errLogMessage);
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
     * @param unknown $order
     * @throws Exception
     */
    public function saveOrderData($order){
    	$dbTransactionModel = new Order();
    	//==== END 改造结束 ============
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
    		}
    		$dbTransaction->commit();
    	}catch (Exception $e){
    		$dbTransaction->rollback();
    		throw new Exception($e->getMessage(),$e->getCode());
    	}
    }
    
    /**
     * @desc 保存订单主信息
     * @param object $info
     */
    public function saveOrderInfo(){
        $order = $this->orderResponse;
        $order_id  = AutoCode::getCodeNew('order'); // 获取订单号
        if ( empty($order_id) ) {
            throw new Exception($order['order_id']." getCodeNew Error",self::GET_ORDERNO_ERRNO);//指定code
        } else {
            $order_id = $order_id . 'KF';
        }
    	$ship_type_arr = array(
    		'US' => Logistics::CODE_EUB,
    	);

        $orderInfo = Order::model()->getOrderInfoByPlatformOrderID($order['order_id'], Platform::CODE_WISH);
        if( $order['state']==self::ORDER_STATE_REFUNDED ){//退款订单
            if( !empty($orderInfo) && $orderInfo['ship_status']==Order::SHIP_STATUS_NOT ){//未出货的订单可以取消
                Order::model()->cancelOrders($orderInfo['order_id']);
            }
           return false;
        }

        //存在已付款的订单，不更新
        if( !empty($orderInfo) && $orderInfo['payment_status']==Order::PAYMENT_STATUS_END ){
        	return false;
        }
        
        //获取订单号
        $orderID = !empty($orderInfo) ? $orderInfo['order_id'] : $order_id;

        //订单号重复检查
        $tmpOrder = Order::model()->getInfoByOrderId($orderID,'order_id');
        if (!empty($tmpOrder)) {
            throw new Exception($orderID.'订单号重复!');
        }

        //订单主表数据
        $order['shippingdetail_ship_country_name'] = Country::model()->getEnNameByAbbr(trim($order['shippingdetail_country']));
		if(!$order['shippingdetail_ship_country_name']){//为空时默认
			$order['shippingdetail_ship_country_name'] = $order['shippingdetail_country'];
		}
		$this->orderResponse['shippingdetail_ship_country_name'] = $order['shippingdetail_ship_country_name'];
		
		//====设置运送方式=====
		$shipcode = '';
		$ship_type_flag = false;
		$battery_flag = false;//检测是否为特殊属性
		$order_attr = array();//记录订单属性
		
		
		if($this->_accountID != 1 && $this->_accountID !=2 && $this->_accountID != 3){
			$sku_online = $order['sku'];
			$sku_online = encryptSku::getWishRealSku($sku_online);
		}else{
			$sku_online = $order['sku'];
		}
		if(strlen($sku_online) < 4){
			$sku_online = sprintf("%04d",$sku_online);//格式化产品号.不足位的在前加0
		}
		
		//转换相关产品(批量销量)
		$new_info = Product::model()->getRealSkuList($sku_online, $order['quantity']);
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
			$total_weight = floatval($product_info['product_weight'])*intval($order['quantity']);
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
						'country'			=> $order['shippingdetail_ship_country_name'],
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
			if(isset($ship_type_arr[trim($order['shippingdetail_country'])])){
				$tempCost = self::model("Logistics")->getShipFee($ship_type_arr[trim($order['shippingdetail_country'])], $total_weight, array(
						'attributeid'		=>	array_unique($order_attr),
						'country'			=> $order['shippingdetail_ship_country_name'],
						'discount'			=> 1,
						'volume'			=> 0,
						'warehouse'			=> '',
						'platform_code' 	=> Platform::CODE_WISH,
				));
				$tempCost = floatval($tempCost);
				if($tempCost > 0 ){
					$ship_type_flag = true;
					$shipcode = $ship_type_arr[trim($order['shippingdetail_country'])];
				}
			}
		}
		
		if(!$ship_type_flag){//以上都匹配不到，在广州挂号和深圳挂号中选便宜的
			$tempCost = self::model("Logistics")->getShipFee(Logistics::CODE_GHXB_GZ, $total_weight, array(
					'attributeid'		=>	array_unique($order_attr),
					'country'			=> $order['shippingdetail_ship_country_name'],
					'discount'			=> 1,
					'volume'			=> 0,
					'warehouse'			=> '',
					'platform_code' 	=> Platform::CODE_WISH,
			));
			$gzShip = floatval($tempCost);
			
			$tempCost = self::model("Logistics")->getShipFee(Logistics::CODE_GHXB_CN, $total_weight, array(
					'attributeid'		=>	array_unique($order_attr),
					'country'			=> $order['shippingdetail_ship_country_name'],
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
     
        //费用计算
        $shipCost      = $order['shiping'] * $order['quantity'];//订单运费(含成交费)
        $subtotalPrice = $order['price'] * $order['quantity'];//产品总金额(含成交费) 
        $totalPrice    = round($subtotalPrice + $shipCost,2);//订单金额(含成交费)

        $flag = Order::model()->saveOrderRecord(array(
                'order_id'              => $orderID,
                'platform_code'         => Platform::CODE_WISH,
                'platform_order_id'     => trim($order['order_id']),
                'account_id'            => $this->_accountID,
                'log_id'                => $order['log_id'],
                'order_status'          => trim($order['state']),
                'buyer_id'              => trim($order['buyer_id']),
                'email'                 => '',
                'timestamp'             => date('Y-m-d H:i:s'),
                'created_time'          => $order['order_time'],
                'last_update_time'      => $order['last_updated'],
                'ship_cost'             => $shipCost,//订单运费(含成交费)
                'subtotal_price'        => $subtotalPrice,//产品总金额(含成交费)
                'total_price'           => $totalPrice,//订单金额(含成交费)
                'final_value_fee'       => 0,//成交费,由明细表计算更新
                'insurance_amount'      => 0,//运费险(无)
                'currency'              => self::DEFAULT_CURRENCY,
                'ship_country'          => trim($order['shippingdetail_country']),
                'ship_country_name'     => $order['shippingdetail_ship_country_name'],
                'paytime'               => $order['order_time'],
                'payment_status'        => Order::PAYMENT_STATUS_NOT,
                'ship_phone'            => isset($order['shippingdetail_phone_number']) ? trim($order['shippingdetail_phone_number']) : '',
                'ship_name'             => trim($order['shippingdetail_name']),
				'ship_street1'          => trim($order['shippingdetail_street_address1']),
				'ship_street2'          => isset($order['shippingdetail_street_address2']) ? trim($order['shippingdetail_street_address2']) : '',
				'ship_zip'              => isset($order['shippingdetail_zipcode']) ? trim($order['shippingdetail_zipcode']) : '',
				'ship_city_name'        => isset($order['shippingdetail_city']) ? trim($order['shippingdetail_city']) : (isset($order['shippingdetail_state']) ? trim($order['shippingdetail_state']) : ''),
				'ship_stateorprovince'  => isset($order['shippingdetail_state']) ? trim($order['shippingdetail_state']) : (isset($order['shippingdetail_city']) ? trim($order['shippingdetail_city']):''),
        		'ship_code'				=>	$shipcode,
        		'ship_status'			=>	Order::SHIP_STATUS_NOT,
        		'complete_status'		=>	Order::COMPLETE_STATUS_DEFAULT,
        		'ori_create_time'       => $this->transactionToLocal($order['order_time']),
        		'ori_update_time'       => $this->transactionToLocal($order['last_updated']),
        		'ori_pay_time'          => $this->transactionToLocal($order['order_time']),
        ));
        if(!$flag) throw new Exception("save failure");
        return $orderID;
    }

    /**
     * @desc 保存订单详情信息
     */
    public function saveOrderDetail($orderID){
        $path = 'wish/saveOrderDetail/'.date("Ymd").'/'.$this->_accountID.'/'.date("His");
        $order = $this->orderResponse;

        //异常处理
        if(trim($order['price']) <= 0 ) {
            throw new Exception("price exception");
        }

        if(trim($order['quantity']) <= 0) {
            throw new Exception("quantity exception");
        }

        $platformOrderID = trim($order['order_id']);//平台订单号
        //判断是否有收取运费
        $flagShipPrice = floatval($order['shipping_cost']) > 0 ? true : false;
        $weightArr = array();//记录订单中的产品重量比重
        $totalWeight = 0;

        //2.订单详情数据
        //删除详情
        OrderDetail::model()->deleteOrderDetailByOrderID($orderID);

        $orderSkuExceptionMsg = '';//订单sku异常信息
        $skuOnline = trim($order['sku']);//在线sku
        $sku = encryptSku::getWishRealSku($skuOnline);
        $sku = $this->isAddZero($sku);
            
        if(strlen($sku) < 4){
            $sku = sprintf("%04d",$sku);//格式化产品号.不足位的在前加0
        }

        $skuInfo = Product::model()->getProductInfoBySku($sku);

        $skuarr = array();
        $skuarr = explode(".",$sku);
        if(isset($skuarr[1]) && $skuarr[1]!=''){
            if(!$skuInfo && preg_match('/[a-zA-Z]/',substr($skuarr[1],strlen($skuarr[1])-1,1))){
                $sku = $skuarr[0].'.'.substr($skuarr[1],0,strlen($skuarr[1])-1);
                $skuInfo = Product::model()->getProductInfoBySku($sku);
            }
        }

        $skuInfo2 = array();//发货sku信息
        $pending_status  = OrderDetail::PEDNDING_STATUS_ABLE;
        if (!empty($skuInfo)) {
            $realProduct = Product::model()->getRealSkuListNew($sku, $order['quantity'],$skuInfo);
            $newsku = trim($realProduct['sku']);
            $realProduct['sku'] = $newsku;
            if ($newsku == $skuInfo['sku']) {
                $skuInfo2 = $skuInfo;
            } else {
                $skuInfo2 = Product::model()->getProductInfoBySku($newsku);
            }
        }       

        if(empty($skuInfo) || empty($skuInfo2) ){
            $realProduct = array(
                'sku'       => 'unknow',
                'quantity'  => trim($order['quantity']),
            );
            $orderSkuExceptionMsg .= 'sku信息不存在;';
        }

        if($skuInfo2 && $skuInfo2['product_is_multi'] == Product::PRODUCT_MULTIPLE_MAIN){
            $childSku = ProductSelectAttribute::model()->getChildSKUListByProductID($skuInfo2['id']);
            if(!empty($childSku)){
                $orderSkuExceptionMsg .= "sku:{$skuInfo2['sku']}为主sku;";
            }
        }

        if ($orderSkuExceptionMsg != '') {
            $pending_status = OrderDetail::PEDNDING_STATUS_KF;
            $cres = Order::model()->setExceptionOrder($orderID, OrderExceptionCheck::EXCEPTION_SKU_UNKNOWN, $orderSkuExceptionMsg);
            if(! $cres){
                throw new Exception ( 'Set order Exception Failure: '.$orderID);
            }
        }

        //保存
        $itemSalePrice = $order['price'] * $order['quantity'];
        $subtotalPrice = $itemSalePrice;
        $shipFee       = $order['shiping'] * $order['quantity'];
        $totalPrice    = round($subtotalPrice + $shipFee,2);//产品总金额+平摊后的运费

        $subtotalPrice2 = $order['cost'] * $order['quantity'];//产品总金额(不含成交费) 
        $shipFee2       = $order['shipping_cost'] * $order['quantity'];//订单运费(不含成交费)
        $finalValueFee  = round($totalPrice - $subtotalPrice2 - $shipFee2, 2);//成交费

        $orderItemRow = array(                      
                'transaction_id'          => trim($order['order_id']),
                'order_id'                => $orderID,
                'platform_code'           => Platform::CODE_WISH,
                'item_id'                 => trim($order['product_id']),
                'title'                   => trim($order['product_name']),
                'sku_old'                 => $sku,
                'sku'                     => $realProduct['sku'],
                'site'                    => '',
                'quantity_old'            => trim($order['quantity']),
                'quantity'                => $realProduct['quantity'],
                'sale_price'              => $order['price'],//单价(含成交费)
                'total_price'             => $totalPrice,//产品总金额+平摊后的运费
                'ship_price'              => $shipFee,//平摊后的运费(含成交费)
                'final_value_fee'         => $finalValueFee,//平摊后的成交费
                'currency'                => self::DEFAULT_CURRENCY,
                'pending_status'          => $pending_status,
                'create_time'			  => date('Y-m-d H:i:s')
        );

        //通过海外仓和产品ID映射表(ueb_wish_overseas_warehouse_map)，把对应产品ID的海外仓ID添加到订单详情表
        $item_id = trim($order['product_id']);
        if (!empty($item_id) && $order['is_wish_express'] == "True"){
            $wishOverseasWarehouseModel = new WishOverseasWarehouse();
            $wret = $wishOverseasWarehouseModel->getWarehouseInfoByProductID($item_id);
            if ($wret){
                $warehouseID = (int)$wret['overseas_warehouse_id'];
                /* $warehouseList = $this->getWarehouseList();
                $warehouseInfo = isset($warehouseList[$warehouseID]) ? $warehouseList[$warehouseID] : array();
                //特殊国家隐射
                $tempmappCountryAddr = array(
                	'GB'	=>	'UK'
                );
                $orderCountry = strtoupper(trim($order['shippingdetail_country']));
                $warehouseCountry = strtoupper(trim($warehouseInfo['country']));
                if($warehouseInfo && ($orderCountry == $warehouseCountry 
                		|| (isset($tempmappCountryAddr[$orderCountry]) && $tempmappCountryAddr[$orderCountry] == $warehouseCountry)) ){
                	//@todo 仓库对应国家与收货地址相同则置为海外仓库ID，否则就是本地仓库了 */
                	$orderItemRow['warehouse_id'] = $warehouseID;
                //}
            }               
            
        }

        $detailId = OrderDetail::model()->addOrderDetail($orderItemRow);
        if(!$detailId) throw new Exception("save order detail failure");

        //保存订单明细扩展表
        $orderItemExtendRow = array(
            'detail_id'              => $detailId,
            'item_sale_price'        => $itemSalePrice,//产品金额(含成交费)
            'item_sale_price_allot'  => $itemSalePrice,//平摊后的产品金额(含成交费，减优惠金额)
            'unit_sale_price_allot'  => $order['price'],//平摊后的单价(原销售单价-平摊后的优惠金额)
            'coupon_price_allot'     => 0,//平摊后的优惠金额
            'tax_fee_allot'          => 0,//平摊后的税费
            'insurance_amount_allot' => 0,//平摊后的运费险
            'fee_amt_allot'          => 0,//平摊后的手续费
        );
        $flag = OrderDetailExtend::model()->addOrderDetailExtend($orderItemExtendRow);
        if(!$flag) throw new Exception("save order detailExtend failure");

        //判断是否需要添加插头数据,海外仓发货不需要匹配插头
        if (trim($order['is_wish_express']) != 'True') {
            $flag = OrderDetail::model()->addOrderAdapter(array(
                        'order_id'              =>  $orderID,
                        'ship_country_name'     =>  trim($order['shippingdetail_ship_country_name']),
                        'platform_code'         =>  Platform::CODE_WISH,
                        'currency'              =>  self::DEFAULT_CURRENCY
                    ),  $realProduct);
            if(!$flag) throw new Exception("save order adapter failure");
        }

        //保存订单sku与销售关系数据
        $orderSkuOwnerInfo = array(
            'platform_code'         => Platform::CODE_WISH,//平台code
            'platform_order_id'     => $platformOrderID,//平台订单号
            'online_sku'            => $skuOnline == ''? 'unknown' : $skuOnline,//在线sku
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
            //$flag = false;
        }

        //更新订单主表相关信息
        $updateOrderData = array(
            'final_value_fee'  => $finalValueFee,//成交费
        );
        $flag = Order::model()->updateByPk($orderID, $updateOrderData);
        if(!$flag){
            throw new Exception("update order datas failure");
        }

        //保存订单扩展表数据        
        $orderExtend = new OrderExtend();
        $orderExtend->getDbConnection()->createCommand()->delete($orderExtend->tableName(),"platform_order_id='{$platformOrderID}' and platform_code='". Platform::CODE_WISH ."'");
        $orderExtend->getDbConnection()->createCommand()->insert($orderExtend->tableName(),array(
            'order_id'          => $orderID,
            'platform_code'     => Platform::CODE_WISH,
            'platform_order_id' => $platformOrderID,//平台订单号
            'account_id'        => $this->_accountID,//账号id
            'tax_fee'           => 0,//总税费
            'coupon_price'      => 0,//总优惠
            'currency'          => self::DEFAULT_CURRENCY,
            'create_time'       => date('Y-m-d H:i:s'),
            'payment_type'      => '',//api没返回
            'logistics_type'    => $order['shipping_provider'],    
        ));
        
        return $flag;
    }
    
    /**
     * @desc 保存订单交易信息
     * @param string $orderID
     */
    public function saveTransaction($orderID){
        $order         = $this->orderResponse;
        $subtotalPrice = $order['price'] * $order['quantity'];
        $shipFee       = $order['shiping'] * $order['quantity'];
        $totalPrice    = round($subtotalPrice + $shipFee,2);//产品总金额+平摊后的运费

        $flag = OrderTransaction::model()->saveTransactionRecord($order['order_id'], $orderID, array(
                'order_id'              => $orderID,
                'first'                 => 1,
                'is_first_transaction'  => 1,
                'receive_type'          => OrderTransaction::RECEIVE_TYPE_YES,
                'account_id'            => $this->_accountID,
                'parent_transaction_id' => '',
                'order_pay_time'        => date('Y-m-d H:i:s', strtotime($order['order_time'])),
                'amt'                   => $totalPrice,//订单交易金额(含成交费)
                'fee_amt'               => 0,//无手续费
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
        $order         = $this->orderResponse;
        $subtotalPrice = $order['price'] * $order['quantity'];
        $shipFee       = $order['shiping'] * $order['quantity'];
        $totalPrice    = round($subtotalPrice + $shipFee,2);//产品总金额+平摊后的运费

    	$flag = OrderPaypalTransactionRecord::model()->savePaypalRecord($order['order_id'], $orderID, array(
    			'order_id'              => 	$orderID,
    			'receive_type'          => 	OrderPaypalTransactionRecord::RECEIVE_TYPE_YES,
    			'receiver_business'		=> 	'',
    			'receiver_email' 		=> 	'unknown@vakind.com',
    			'receiver_id' 			=> 	'',
    			'payer_id' 				=> 	'',
    			'payer_name' 			=> 	isset($order['shippingdetail_name']) ? $order['shippingdetail_name'] : '',
    			'payer_email' 			=> 	'',
    			'payer_status' 			=> 	'',
    			'parent_transaction_id'	=>	'',
    			'transaction_type'		=>	'',
    			'payment_type'			=>	'',
    			'order_time'			=>	date('Y-m-d H:i:s', strtotime($order['order_time'])),
    			'amt'					=>	$totalPrice,//订单交易金额(含成交费)
    			'fee_amt'				=>	0,//无手续费
    			'tax_amt'				=>	0,//无税费
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
     * @desc 
     * @return multitype:
     */
    public function getWarehouseList(){
    	$this->_warehouseList = array();
    	if(! $this->_warehouseList){
    		$warehouseList = Warehouse::model()->findAll();
    		$newWarehouseList = array();
    		if($warehouseList){
    			foreach ($warehouseList as $warehouse){
    				$newWarehouseList[$warehouse['id']] = $warehouse;
    			}
    		}
    		$this->_warehouseList = $newWarehouseList;
    	}
    	return $this->_warehouseList;
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
     * @desc 转换为北京时间
     * @param unknown $UTCTime
     * @return string
     */
    public function transactionToLocal($UTCTime){
        return date("Y-m-d H:i:s", strtotime($UTCTime)+8*3600);
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
    		$this->orderResponse = $order;
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
        $order_id  = AutoCode::getCodeNew('order'); // 获取订单号
        if ( empty($order_id) ) {
            throw new Exception("getCodeNew Error");
        } else {
            $order_id = $order_id . 'KFS';
        }
    	$ship_type_arr = array(
    			'US' => Logistics::CODE_EUB,
    	);
    	$order = $this->orderResponse;
    	$orderInfo = WishSpecialOrder::model()->getOrderInfoByPlatformOrderID($order['order_id'], Platform::CODE_WISH);
    	
    	if( $order['state']==self::ORDER_STATE_REFUNDED ){//退款订单
    		if( !empty($orderInfo) && $orderInfo['ship_status'] == WishSpecialOrder::SHIP_STATUS_NOT ){//未出货的订单可以取消
    			//WishSpecialOrder::model()->cancelOrders($orderInfo['order_id']);
    		}
    		return false;
    	}

    	if( !empty($orderInfo) && $orderInfo['payment_status'] == WishSpecialOrder::PAYMENT_STATUS_END ){//存在已付款的订单，不更新
    		return false;
    	}
    	
        //获取订单号
    	$orderID = !empty($orderInfo) ? $orderInfo['order_id'] : $order_id;

        //订单号重复检查
        $tmpOrder = WishSpecialOrder::model()->getInfoByOrderId($orderID,'order_id');
        if (!empty($tmpOrder)) {
            throw new Exception($orderID.'订单号重复!');
        }

    	//订单主表数据
    	$order['shippingdetail_ship_country_name'] = Country::model()->getEnNameByAbbr(trim($order['shippingdetail_country']));
    	if(!$order['shippingdetail_ship_country_name']){//为空时默认
    		$order['shippingdetail_ship_country_name'] = $order['shippingdetail_country'];
    	}
    	$this->orderResponse['shippingdetail_ship_country_name'] = $order['shippingdetail_ship_country_name'];
    	//====设置运送方式=====
    	$shipcode = '';
    	$ship_type_flag = false;
    	$battery_flag = false;//检测是否为特殊属性
    	$order_attr = array();//记录订单属性
    
    	if($this->_accountID != 1 && $this->_accountID !=2 && $this->_accountID != 3){
    		$sku_online = $order['sku'];
    		$sku_online = encryptSku::getWishRealSku($sku_online);
    	}else{
    		$sku_online = $order['sku'];
    	}
    	if(strlen($sku_online) < 4){
    		$sku_online = sprintf("%04d",$sku_online);//格式化产品号.不足位的在前加0
    	}
    
    	//转换相关产品(批量销量)
    	$new_info = Product::model()->getRealSkuList($sku_online, $order['quantity']);
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
    		$total_weight = floatval($product_info['product_weight'])*intval($order['quantity']);
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
    					'country'			=> $order['shippingdetail_ship_country_name'],
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
    		if(isset($ship_type_arr[trim($order['shippingdetail_country'])])){
    			$tempCost = self::model("Logistics")->getShipFee($ship_type_arr[trim($order['shippingdetail_country'])], $total_weight, array(
    					'attributeid'		=>	array_unique($order_attr),
    					'country'			=> $order['shippingdetail_ship_country_name'],
    					'discount'			=> 1,
    					'volume'			=> 0,
    					'warehouse'			=> '',
    					'platform_code' 	=> Platform::CODE_WISH,
    			));
    			$tempCost = floatval($tempCost);
    			if($tempCost > 0 ){
    				$ship_type_flag = true;
    				$shipcode = $ship_type_arr[trim($order['shippingdetail_country'])];
    			}
    		}
    	}
    
    	if(!$ship_type_flag){//以上都匹配不到，在广州挂号和深圳挂号中选便宜的
    			
    		$tempCost = self::model("Logistics")->getShipFee(Logistics::CODE_GHXB_GZ, $total_weight, array(
    				'attributeid'		=>	array_unique($order_attr),
    				'country'			=> $order['shippingdetail_ship_country_name'],
    				'discount'			=> 1,
    				'volume'			=> 0,
    				'warehouse'			=> '',
    				'platform_code' 	=> Platform::CODE_WISH,
    		));
    		$gzShip = floatval($tempCost);
    			
    		$tempCost = self::model("Logistics")->getShipFee(Logistics::CODE_GHXB_CN, $total_weight, array(
    				'attributeid'		=>	array_unique($order_attr),
    				'country'			=> $order['shippingdetail_ship_country_name'],
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
    			'platform_order_id'     => trim($order['order_id']),
    			'account_id'            => $this->_accountID,
    			'log_id'                => $this->_logID,
    			'order_status'          => trim($order['state']),
    			'buyer_id'              => trim($order['buyer_id']),
    			'email'                 => '',
    			'timestamp'             => date('Y-m-d H:i:s'),
    			'created_time'          => $order['order_time'],
    			'last_update_time'      => $order['last_updated'],
    			'ship_cost'             => floatval($order['shipping_cost']) * intval($order['quantity']),
    			'subtotal_price'        => floatval($order['cost']) * intval($order['quantity']),
    			'total_price'           => floatval($order['order_total']),
    			'currency'              => self::DEFAULT_CURRENCY,
    			'ship_country'          => trim($order['shippingdetail_country']),
    			'ship_country_name'     => $order['shippingdetail_ship_country_name'],
    			'paytime'               => $order['order_time'],
    			'payment_status'        => Order::PAYMENT_STATUS_NOT,
    			'ship_phone'            => isset($order['shippingdetail_phone_number']) ? trim($order['shippingdetail_phone_number']) : '',
    			'ship_name'             => trim($order['shippingdetail_name']),
    			'ship_street1'          => trim($order['shippingdetail_street_address1']),
    			'ship_street2'          => isset($order['shippingdetail_street_address2']) ? trim($order['shippingdetail_street_address2']) : '',
    			'ship_zip'              => isset($order['shippingdetail_zipcode']) ? trim($order['shippingdetail_zipcode']) : '',
    			'ship_city_name'        => isset($order['shippingdetail_city']) ? trim($order['shippingdetail_city']) : (isset($order['shippingdetail_state']) ? trim($order['shippingdetail_state']) : ''),
    			'ship_stateorprovince'  => isset($order['shippingdetail_state']) ? trim($order['shippingdetail_state']) : (isset($order['shippingdetail_city']) ? trim($order['shippingdetail_city']):''),
    			'ship_code'				=>	$shipcode,
    			'ship_status'			=>	Order::SHIP_STATUS_NOT,
    			'complete_status'		=>	Order::COMPLETE_STATUS_DEFAULT,
    			//add lihy 2016-04-13
    			'ori_create_time'      => date('Y-m-d H:i:s', strtotime($order['order_time'])+8*3600),
    			'ori_update_time'      	=> date('Y-m-d H:i:s', strtotime($order['last_updated'])+8*3600),
    			'ori_pay_time'           => date('Y-m-d H:i:s', strtotime($order['order_time'])+8*3600),
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
        $platformOrderID = trim($order['order_id']);//平台订单号
        //判断是否有收取运费
        $flagShipPrice = floatval($order['shipping_cost']) > 0 ? true : false;
        $weightArr = array();//记录订单中的产品重量比重
        $totalWeight = 0;
        //2.订单详情数据
        //删除详情
        WishSpecialOrderDetail::model()->deleteOrderDetailByOrderID($orderID);

        $skuOnline = trim($order['sku']);//在线sku
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
        $realProduct = Product::model()->getRealSkuList($sku, $order['quantity']);
        $newsku = trim($realProduct['sku']);
        
        $realProduct['sku'] = $newsku;
        $skuInfo = Product::model()->getProductInfoBySku($newsku);
        if(empty($skuInfo) )
        {
            $realProduct = array(
                    'sku'       => 'unknow',
                    'quantity'  => trim($order['quantity']),
            );
            WishSpecialOrder::model()->setOrderCompleteStatus(WishSpecialOrder::COMPLETE_STATUS_PENGDING, $orderID);
        }

        //保存
        $orderItemRow = array(
                'transaction_id'          => trim($order['order_id']),
                'order_id'                => $orderID,
                'platform_code'           => Platform::CODE_WISH,
                'item_id'                 => trim($order['product_id']),
                'title'                   => trim($order['product_name']),
                'sku_old'                 => $sku,
                'sku'                     => $realProduct['sku'],
                'site'                    => 0,
                'quantity_old'            => trim($order['quantity']),
                'quantity'                => $realProduct['quantity'],
                'sale_price'              => trim($order['cost']),
                'total_price'             => floatval($order['cost']) * intval($order['quantity']),
                'currency'                => self::DEFAULT_CURRENCY,
                'ship_price'              => floatval($order['shipping_cost']) * intval($order['quantity']),
				'create_time'			  => date('Y-m-d H:i:s')
        );
        $flag = WishSpecialOrderDetail::model()->addOrderDetail($orderItemRow);
        if(!$flag) throw new Exception("save order detail failure");

        //判断是否需要添加插头数据
        $flag = WishSpecialOrderDetail::model()->addOrderAdapter(array(
                'order_id'            =>    $orderID,
                'ship_country_name'   =>    trim($order['shippingdetail_ship_country_name']),
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
    	$flag = WishSpecialOrderTransaction::model()->saveTransactionRecord($order['order_id'], $orderID, array(
    			'order_id'              => $orderID,
    			'first'                 => 1,
    			'is_first_transaction'  => 1,
    			'receive_type'          => WishSpecialOrderTransaction::RECEIVE_TYPE_YES,
    			'account_id'            => $this->_accountID,
    			'parent_transaction_id' => 0,
    			'order_pay_time'        => date('Y-m-d H:i:s', strtotime($order['order_time'])),
    			'amt'                   => $order['order_total'],
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
    	$flag = WishSpecialOrderPaypalTransactionRecord::model()->savePaypalRecord($order['order_id'], $orderID, array(
    			'order_id'              => 	$orderID,
    			'receive_type'          => 	WishSpecialOrderPaypalTransactionRecord::RECEIVE_TYPE_YES,
    			'receiver_business'		=> 	'',
    			'receiver_email' 		=> 	'unknown@vakind.com',
    			'receiver_id' 			=> 	'',
    			'payer_id' 				=> 	'',
    			'payer_name' 			=> 	isset($order['shippingdetail_name']) ? $order['shippingdetail_name'] : '',
    			'payer_email' 			=> 	'',
    			'payer_status' 			=> 	'',
    			'parent_transaction_id'	=>	'',
    			'transaction_type'		=>	'',
    			'payment_type'			=>	'',
    			'order_time'			=>	date('Y-m-d H:i:s', strtotime($order['order_time'])),
    			'amt'					=>	$order['order_total'],
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

    /**
     * [addWishLog description]
     * @param [type] $accountID [description]
     * @param [type] $status    [description]
     * @param [type] $message   [description]
     * @param [type] $eventName [description]
     */
    public function addWishLog($accountID,$status,$message,$eventName=WishLog::EVENT_SYNC_ORDER) {
        $logModel = new WishLog();
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