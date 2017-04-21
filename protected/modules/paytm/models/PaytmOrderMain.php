<?php
/**
 * @desc Paytm订单拉取
 * @author yangsh
 * @since 2017-03-02
 */
class PaytmOrderMain extends PaytmModel {
	
    /** @var object 订单信息*/
    protected $orderResponse = null;

    /** @var object 订单明细*/
    protected $orderDetailInfos = null;

    /** @var object 订单收件人信息*/
    protected $orderAddressInfo = null;
    
    /** @var int 账号ID*/
    public $_accountID = null;
    
    /** @var string 异常信息*/
    public $exception = null;
    
    /** @var int 日志编号*/
    public $_logID = 0;

    /** @var int 佣金比例*/
    public $_comminsionFeeRate = 0.15;
    
    /** @var int 订单状态*/
    const ORDER_PENDING_ACKNOWLEDGMENT = 2; //Order is Authorized to be Processed
    const ORDER_PENDING_SHIPMENT       = 5; //Order has been acknowledged. AWB is to be assigned now
    const ORDER_SHIPMENT_CREATED       = 23; //AWB has been assigned
    const ORDER_MANIFEST_REQUESTED     = 25; //Manifest has been created ready to be Picked from the Merchant
    const ORDER_READY_TO_SHIP          = 13; //Shipment is packed
    const ORDER_SHIPPED                = 15; //Order Shipped
    const ORDER_DELIVERED              = 7; //Order Delivered
    const ORDER_RETURN_REQUESTED       = 17; //Order Return Requested
    const ORDER_RETURNED               = 18; //Order Returned
    const ORDER_FAILURE                = 6; //Order has been cancelled by the merchant
    const ORDER_CANCELLED              = 8; //Order has been cancelled by the User
    const ORDER_REFUNDED               = 12; //Order refunded
    
    /* @var int 付款状态 */
    const PAYMENT_UNPAID    = 1;//未付款
    const PAYMENT_COMPLETED = 2;//已付款
    
    const TO_OMS_OK         = 1;//已同步
    const TO_OMS_NO         = 0;//未同步

    /* @var string 默认币种 */
    const DEFAULT_CURRENCY  = 'INR';
    
    const GET_ORDERNO_ERRNO = 1000;//获取订单号异常编号
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_paytm_order_main';
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
        $eventName = PaytmLog::EVENT_GETORDER;
        $lastLog = PaytmLog::model()->getLastLogByCondition(array(
                'account_id'    => $accountID,
                'event'         => $eventName,
                'status'        => PaytmLog::STATUS_SUCCESS,
        ));
        $lastEventLog = array();
        if( !empty($lastLog) ){
            $lastEventLog = PaytmLog::model()->getEventLogByLogID($eventName, $lastLog['id']);
        }
        return (!empty($lastEventLog) && $lastEventLog['complete_time'] != "0000-00-00 00:00:00")
                ? date('Y-m-d\TH:i:s.000\Z',strtotime($lastEventLog['complete_time']) -15*60 - 3600*8 ) 
                : date('Y-m-d\TH:i:s.000\Z',time() - 86400 - 3600*8);
    }
    
    /**
     * @desc 根据条件获取订单
     * @param int $startTime
     * @param int $endTime
     * @param array $params
     */
    public function getOrders($startTime,$endTime){
        $path = 'paytm/getOrders/'.date("Ymd").'/'.$this->_accountID.'/'.date("His");
        $request = new GetOrdersRequest();
        $request->setPlacedAfter($startTime);
        $request->setPlacedBefore($endTime);
        $response = $request->setAccount($this->_accountID)->setRequest()->sendRequest()->getResponse();
        //MHelper::writefilelog($path.'/response_'.$index.'.log', print_r($response,true)."\r\n");// for test
        if( $request->getIfSuccess() ){
            $errorMsg = '';
            foreach($response as $order) {//循环订单信息
                try {
                    $this->savePaytmOrder($order);
                } catch (Exception $e){
                    $errorMsg .= $e->getMessage()."<br/>";
                }
            }   
            $this->setExceptionMessage($errorMsg);    
            return true;
        }else{//抓取失败
            $this->setExceptionMessage($request->getErrorMsg());
            return false;
        }
    }

    /**
     * @desc 保存平台订单
     * @param object $order
     */
    public function savePaytmOrder($order) {
        $dbTransactionModel = new Order();
        //==== END 改造结束 ============
        $dbTransaction = $dbTransactionModel->dbConnection->getCurrentTransaction();
        if( !$dbTransaction ){
            $dbTransaction = $dbTransactionModel->dbConnection->beginTransaction();//开启事务
        }
        try{
            $this->orderResponse = $order;
            $orderID = $this->savePaytmOrderInfo();//1.保存订单主数据
            if($orderID){
                $this->savePaytmOrderAddress($orderID);//2.保存订单收件信息
                $this->savePaytmOrderDetail($orderID);//3.保存订单详情信息
            }
            $dbTransaction->commit();
            return true;
        }catch (Exception $e){
            $dbTransaction->rollback();
            throw new Exception($e->getMessage(),$e->getCode());
        }
    }

    /**
     * @desc 保存平台订单数据
     * @param object $order
     * @return boolean
     */
    public function savePaytmOrderInfo(){
        $order = $this->orderResponse;
        $platformOrderID = trim($order->id);
        $platformOrderIDNew = $this->_accountID.'-'.trim($order->id);//平台订单号(账号id-平台订单号)

        //取订单状态逻辑
        $status = isset($order->status) ? intval($order->status) : null;
        $isPartialCanceled = false;//部分取消
        $itemStatusArr = array();
        if(empty($status)) {
            foreach ($order->items as $item) {
                $itemStatusArr[] = isset($item->status) ? intval($item->status) : 0;           
            }

            //判断是否存在部分取消
            if($isPartialCanceled == false && (in_array(self::ORDER_CANCELLED, $itemStatusArr) || in_array(self::ORDER_FAILURE, $itemStatusArr)) ) {
                $isPartialCanceled = true;
            }

            //订单状态
            $status = in_array(self::ORDER_PENDING_ACKNOWLEDGMENT, $itemStatusArr) ? self::ORDER_PENDING_ACKNOWLEDGMENT :
                        in_array(self::ORDER_PENDING_SHIPMENT, $itemStatusArr) ? self::ORDER_PENDING_SHIPMENT : 0;
        }

    	$orderData = array(
            'account_id'         => $this->_accountID,
            'log_id'             => $this->_logID,            
            'platform_order_id'  => $platformOrderID,
            'customer_firstname' => isset($order->customer_firstname) ? trim($order->customer_firstname) : '',
            'customer_lastname'  => isset($order->customer_lastname) ? trim($order->customer_lastname) : '',
            'created_at'         => self::transferUTCTimeFormat($order->created_at),
            'payment_status'     => intval($order->payment_status),
            'status'             => $status,	
            'customer_email'     => isset($order->customer_email) ? trim($order->customer_email) : '',
            'customer_id'        => isset($order->customer_id) ? trim($order->customer_id) : '',
            'phone'              => isset($order->phone) ? trim($order->phone) : '',
            'title'              => isset($order->title) ? trim($order->title) : '',
            'channel_id'         => isset($order->channel_id) ? trim($order->channel_id) : '',
            'remote_ip'          => isset($order->remote_ip) ? trim($order->remote_ip) : '',
            'info'               => isset($order->info) ? trim($order->info) : '',
            'collectable_amount' => isset($order->collectableAmount) ? intval($order->collectableAmount) : 0,
            'selling_price'      => isset($order->selling_price) ? round($order->selling_price,2) : 0,
            'ship_by_date'       => isset($order->ship_by_date) ? self::transferUTCTimeFormat($order->ship_by_date) : '0000-00-00 00:00:00',
            'site_id'            => isset($order->site_id) ? intval($order->site_id) : null,
            'is_COD'             => isset($order->isCOD) ? intval($order->isCOD) : null,
            'is_mobile_verified' => isset($order->isMobileVerified) ? intval($order->isMobileVerified) : null,
            'is_email_verified'  => isset($order->isEmailVerified) ? intval($order->isEmailVerified) : null,
            'is_new_customer'    => isset($order->isNewCustomer) ? intval($order->isNewCustomer) : null,
            'is_scw_wallet'      => isset($order->isScwWallet) ? intval($order->isScwWallet) : null,
            'is_prime_wallet'    => isset($order->shipped_date) ? intval($order->isPrimeWallet) : null,
            'create_time'        => date('Y-m-d H:i:s'),
            'update_time'        => date('Y-m-d H:i:s'),
    	);
    	//按平台订单号+账号判断是否存在
    	$orderMainInfo = $this->getDbConnection()->createCommand()
                        ->from($this->tableName())
    					->select("id,is_to_oms")
    					->where("platform_order_id=:order_id and account_id=:account_id", 
                            array(":order_id"=>$platformOrderID, ":account_id"=>$this->_accountID))
    					->queryRow();
        if($orderMainInfo){
            $pkId = $orderMainInfo['id'];
            /*检查已取消订单是否已同步OMS，对已同步订单先取消未发货的包裹然后标识订单未出货已完成--start--*/
            if ( self::TO_OMS_OK == $orderMainInfo['is_to_oms'] && (self::ORDER_CANCELLED == $status || self::ORDER_FAILURE == $status || $isPartialCanceled) ) {
                $cancel_result = null;
                $omsOrderInfo = Order::model()->getOrderInfoByPlatformOrderID($platformOrderIDNew, Platform::CODE_PAYTM);
                if(!empty($omsOrderInfo) && $omsOrderInfo['ship_status'] == Order::SHIP_STATUS_NOT && ($omsOrderInfo['complete_status'] != Order::COMPLETE_STATUS_END && $omsOrderInfo['complete_status'] != Order::COMPLETE_STATUS_WAIT_CANCEL ) ) {
                    //1.取消包裹
                    if(self::ORDER_CANCELLED == $status || self::ORDER_FAILURE == $status) {//订单全取消
                        $cancel_result = Order::model()->cancelOrders( $omsOrderInfo['order_id'] );
                    } else if($isPartialCanceled) {//订单部分取消
                        $omsOrderDetailsIdArr = $omsItemIDArr = array();
                        $omsOrderDetails = OrderDetail::model()->getListByCondition('id,item_id', "order_id='{$omsOrderInfo['order_id']}' and quantity>0");
                        foreach ($omsOrderDetails as $omsOrderDetail) {
                            if($omsOrderDetail['item_id'] != '') {
                                $omsItemIDArr[] = $omsOrderDetail['item_id'];
                                $omsOrderDetailsIdArr[$omsOrderDetail['item_id']] = $omsOrderDetail['id'];
                            }
                        }
                        $cancelOrderDetailInfos = PaytmOrderDetail::model()->getListByCondition('order_item_id',"order_id={$pkId} and order_item_id in('".implode("','",$omsItemIDArr)."') and status in(".implode(',', self::getCancelOrderStatus()).") " );
                        if($cancelOrderDetailInfos) {
                            $omsDetailIdArr = array();
                            foreach ($cancelOrderDetailInfos as $cancelOrderDetailInfo) {
                                if(isset($omsOrderDetailsIdArr[$cancelOrderDetailInfo['order_item_id']])) {
                                    $omsDetailIdArr[] = $omsOrderDetailsIdArr[$cancelOrderDetailInfo['order_item_id']];
                                }
                            }   
                            if( !empty($omsDetailIdArr) ) {//有同步到OMS的商品取消
                                $cancel_result = Order::model()->cancelOrdersPartial( $omsOrderInfo['order_id'], $omsDetailIdArr );
                            }                                                     
                        }
                    }
                    //2.记录取消日志
                    if( isset($cancel_result['status']) && $cancel_result['status'] ){
                        $aSysTimeOrder = array('cancel_user_id'=>0,'cancel_time'=>date('Y-m-d H:i:s'));
                        $omsSysTimeOrderRow = SysTimeOrder::model()->getOneByCondition('id',"order_id='{$omsOrderInfo['order_id']}'");
                        if($omsSysTimeOrderRow) {
                            SysTimeOrder::model()->updateData($aSysTimeOrder,"id={$omsSysTimeOrderRow['id']}");
                        } else {
                            $aSysTimeOrder['order_id'] = $omsOrderInfo['order_id'];
                            SysTimeOrder::model()->insertData($aSysTimeOrder);
                        }
                        //添加订单修改记录
                        OrderUpdateLog::model()->addOrderUpdateLog($omsOrderInfo['order_id'],'系统检测到订单状态为:ORDER_CANCELLED,自动取消包裹及订单。');                        
                    }                    
                }
            }
            /*检查已取消订单是否已同步OMS，对已同步订单先取消未发货的包裹然后标识订单未出货已完成--end--*/

            unset($orderData['create_time']);
            $isOk = $this->getDbConnection()->createCommand()
                        ->update($this->tableName(), $orderData, "id=:id", array(':id'=>$pkId));
        }else{
            $isOk = $this->getDbConnection()->createCommand()
                        ->insert($this->tableName(), $orderData);
            if($isOk) {
                $pkId = $this->getDbConnection()->getLastInsertID();
            }                        
        }
        if(!$isOk) {
            throw new Exception("save PaytmOrderInfo Failure");
        }
        return $pkId;
    }

    /**
     * @desc 保存平台订单收件信息
     * @param string $orderID
     * @return boolean
     */
    public function savePaytmOrderAddress($orderID) {
        $order = $this->orderResponse;
        $platformOrderID = trim($order->id);
        if(empty($order->address)) {
            throw new Exception("address info is empty");
        }
        //先删除后插入
        PaytmOrderAddress::model()->deleteByOrderID($orderID);
        foreach ($order->address as $addr) {
            if($addr->order_id != $platformOrderID) {
                throw new Exception("the order_id of address is invalid");
            }
            //mysql编码格式utf-8格式，不支持带四字节的字符串插入
            $ship_firstname = isset($addr->firstname) ? trim($addr->firstname) : '';
            if ($ship_firstname != '') {
                $ship_firstname = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $ship_firstname);
            }

            $ship_lastname = isset($addr->lastname) ? trim($addr->lastname) : '';
            if ($ship_lastname != '') {
                $ship_lastname = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $ship_lastname);
            }   

            $ship_address = isset($addr->address) ? trim($addr->address) : '';
            if ($ship_address != '') {
                $ship_address = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $ship_address);
            }        

            $ship_address2 = isset($addr->address2) ? trim($addr->address2) : '';
            if ($ship_address2 != '') {
                $ship_address2 = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $ship_address2);
            }

            $ship_city = isset($addr->city) ? trim($addr->city) : '';
            if ($ship_city != '') {
                $ship_city = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $ship_city);
            }

            $ship_state = isset($addr->state) ? trim($addr->state):'';
            if ($ship_state != '') {
                $ship_state = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $ship_state);
            }
            //组装数据
            $addressData = array(
                'address_id'        => $addr->id,
                'order_id'          => $orderID,
                'platform_order_id' => $addr->order_id,
                'address_type'      => isset($addr->address_type) ? $addr->address_type : 0,
                'firstname'         => $ship_firstname,
                'lastname'          => $ship_lastname,
                'address'           => $ship_address,
                'address2'          => $ship_address2,
                'city'              => $ship_city,
                'state'             => $ship_state,   
                'country'           => trim($addr->country),//en
                'phone'             => trim($addr->phone),
                'altphone'          => isset($addr->altphone) ? trim($addr->altphone) : '',
                'pincode'           => isset($addr->pincode) ? trim($addr->pincode) : '',
                'email'             => isset($addr->email) ? trim($addr->email) : '',
                'status'            => intval($addr->status),
                'created_at'        => self::transferUTCTimeFormat($addr->created_at),
                'updated_at'        => self::transferUTCTimeFormat($addr->updated_at),
            );
            $isOk = PaytmOrderAddress::model()->insertData($addressData);
            if(!$isOk) {
                throw new Exception("save PaytmOrderAddress Failure");
            }            
        }
        return true;
    }

    /**
     * @desc 保存平台订单商品信息
     * @param string $orderID
     * @return boolean
     */
    public function savePaytmOrderDetail($orderID) {
        $order = $this->orderResponse;
        $platformOrderID = trim($order->id);
        if(empty($order->items) || empty($order->items[0])) {
            throw new Exception("order items is empty");
        }
        //先删除后插入
        PaytmOrderDetail::model()->deleteByOrderID($orderID);
        foreach ($order->items as $item) {
            if($item->order_id != $platformOrderID) {
                throw new Exception("the order_id of item is invalid");
            }
            //mysql编码格式utf-8格式，不支持带四字节的字符串插入
            $item_name = isset($item->name) ? trim($item->name) : '';
            if ($item_name != '') {
                $item_name = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $item_name);
            }
            //组装订单明细数据
            $itemData = array(
                'order_item_id'            => $item->id,
                'order_id'                 => $orderID,
                'platform_order_id'        => $item->order_id,
                'merchant_id'              => isset($item->merchant_id) ? $item->merchant_id : '',
                'sku'                      => isset($item->sku) ? trim($item->sku) : '',
                'qty_ordered'              => isset($item->qty_ordered) ? $item->qty_ordered : '',
                'mrp'                      => isset($item->mrp) ? $item->mrp : '',
                'price'                    => isset($item->price) ? $item->price : '',
                'selling_price'            => isset($item->selling_price) ? $item->selling_price : '',
                'discount'                 => isset($item->discount) ? $item->discount : '',
                'bulk_pricing'             => isset($item->bulk_pricing) ? $item->bulk_pricing : '',
                'shipping_amount'          => isset($item->shipping_amount) ? $item->shipping_amount : '',
                'status'                   => isset($item->status) ? $item->status : '',
                'product_id'               => isset($item->product_id) ? $item->product_id : '',
                'created_at'               => self::transferUTCTimeFormat($item->created_at),
                'updated_at'               => self::transferUTCTimeFormat($item->updated_at),
                'name'                     => $item_name,
                'ship_by_date'             => !empty($item->ship_by_date)
                                                 ? self::transferUTCTimeFormat($item->ship_by_date) : '0000-00-00 00:00:00',
                'fulfillment_id'           => isset($item->fulfillment_id) ? $item->fulfillment_id : '',
                'promo_code'               => isset($item->promo_code) ? trim($item->promo_code) : '',
                'promo_description'        => isset($item->promo_description) ? trim($item->promo_description) : '',
                'fulfillment_mode'         => isset($item->fulfillment_mode) ? trim($item->fulfillment_mode) : '',
                'fulfillment_service'      => isset($item->fulfillment_service) ? trim($item->fulfillment_service) : '',
                'ack_by'                   => !empty($item->ack_by) 
                                                ? self::transferUTCTimeFormat($item->ack_by) : '0000-00-00 00:00:00',
                'estimated_delivery'       => !empty($item->estimated_delivery)
                                                 ? self::transferUTCTimeFormat($item->estimated_delivery)  : '0000-00-00 00:00:00',
                'estimated_delivery_range' => !empty($item->estimated_delivery_range) ? json_encode($item->estimated_delivery_range) : '',
                'custom_text1'             => isset($item->custom_text1) ? trim($item->custom_text1) : '',
                'custom_text2'             => isset($item->custom_text2) ? trim($item->custom_text2) : '',
                'custom_text3'             => isset($item->custom_text3) ? trim($item->custom_text3) : '',
                'custom_text4'             => isset($item->custom_text4) ? trim($item->custom_text4) : '',
                'is_COD'                   => isset($item->isCOD) ? intval($item->isCOD) : null,
                'is_refund_attempted'      => isset($item->isRefundAttempted) ? intval($item->isRefundAttempted) : null,
                'is_NSS'                   => isset($item->isNSS) ? intval($item->isNSS) : null,
                'is_LMD'                   => isset($item->isLMD) ? intval($item->isLMD) : null,
                'is_disputed'              => isset($item->isDisputed) ? intval($item->isDisputed) : null, 
                'is_LC'                    => isset($item->isLC) ? intval($item->isLC) : null,
                'is_physical'              => isset($item->isPhysical) ? intval($item->isPhysical) : null, 
                'update_time'              => date('Y-m-d H:i:s'),
            );
            $isOk = PaytmOrderDetail::model()->insertData($itemData);
            if(!$isOk) {
                throw new Exception("save PaytmOrderAddress Failure");
            }            
        }
        return true;        
    }

    /**
     * @desc 获取可同步订单的状态
     * @return array
     */
    public static function getAbleSyncOrderStatus() {
        return array(self::ORDER_PENDING_ACKNOWLEDGMENT, self::ORDER_PENDING_SHIPMENT );
    }

    /**
     * @desc 获取订单取消状态
     * @return array
     */
    public static function getCancelOrderStatus(){
        return array(self::ORDER_CANCELLED, self::ORDER_FAILURE );
    }

    /**
     * @desc 同步订单到oms系统
     * @param int $accountID
     * @param int $limit
     * @return int
     */
    public function syncOrderToOmsByAccountID($accountID, $limit = 1000, $platformOrderID=null){
        $ctime = date('Y-m-d',strtotime('-14 days'));
    	$cmd = $this->getDbConnection()->createCommand()
					->from($this->tableName())
					->where("account_id=:account_id and is_to_oms=0", array(":account_id"=>$accountID))
                    ->andWhere(array('in','status',self::getAbleSyncOrderStatus()))
                    ->andWhere("payment_status=".self::PAYMENT_COMPLETED)
                    ->andWhere("created_at>'{$ctime}'")
					->limit($limit);
        if (!empty($platformOrderID)) {
            $cmd->andWhere("platform_order_id=:orderid",array(':orderid'=>$platformOrderID));
        }
        //echo $cmd->getText()."<br>";
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
                    $this->addPaytmLog($accountID,PaytmLog::STATUS_FAILURE,$errLogMessage);
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
            //查询订单明细
            $this->orderDetailInfos = PaytmOrderDetail::model()->getListByCondition('*',"order_id={$order['id']} and status in(".implode(',', self::getAbleSyncOrderStatus()).") " );
            if(empty($this->orderDetailInfos)) {
                throw new Exception("订单明细为空，待补拉");
            }            
            //查询订单收件信息            
            $this->orderAddressInfo = PaytmOrderAddress::model()->getOneByCondition('*',"order_id={$order['id']} and status=1 ");
            if(empty($this->orderAddressInfo)) {
                throw new Exception("订单收件信息为空，待补拉");
            }   
    		/** 1.保存订单主数据*/
    		$orderID = $this->saveOrderInfo();
    		if($orderID){//保存失败已生成异常，这里主要会有不需要进行再次操作的情况下返回false
    			/** 2.保存订单详情信息*/
    			$this->saveOrderDetail($orderID);
    			/** 3.保存交易信息*/
    			$this->saveTransaction($orderID);
    			/** 4.保存付款信息*/
    			$this->saveOrderPaypalTransactionRecord($orderID);
                /* 5.订单确认*/
                $isOk = $this->acknowledgeOrder();
                if(!$isOk) {
                    throw new Exception("订单确认失败!");
                }
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
    protected function saveOrderInfo(){
        $order = $this->orderResponse;
        $orderAddressInfo = $this->orderAddressInfo;
        $platformCode = Platform::CODE_PAYTM;
        $platformOrderIDNew = $this->_accountID.'-'.$order['platform_order_id'];//平台订单号(账号id-平台订单号)
        $order_id  = AutoCode::getCodeNew('order'); // 获取订单号
        if ( empty($order_id) ) {
            throw new Exception($order['order_id']." getCodeNew Error",self::GET_ORDERNO_ERRNO);//指定code
        } else {
            $order_id = $order_id . 'PT';
        }

        $orderInfo = Order::model()->getOrderInfoByCondition($order['platform_order_id'], $platformCode, $this->_accountID);
        if( $order['status']==self::ORDER_REFUNDED ){//退款订单
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

        //组装数据
        $ship_name = $orderAddressInfo['firstname'].($orderAddressInfo['lastname']==''?'':' '.$orderAddressInfo['lastname']);
        $ship_country = Country::model()->getAbbrByEname($orderAddressInfo['country']);//国家二字码

        $flag = Order::model()->saveOrderRecord(array(
                'order_id'              => $orderID,
                'platform_code'         => $platformCode,
                'platform_order_id'     => $platformOrderIDNew,
                'account_id'            => $this->_accountID,
                'log_id'                => $order['log_id'],
                'order_status'          => $order['status'],
                'buyer_id'              => $order['customer_id'],
                'email'                 => $order['customer_email'],
                'timestamp'             => date('Y-m-d H:i:s'),
                'created_time'          => $order['created_at'],
                'last_update_time'      => $order['created_at'],
                'ship_cost'             => 0,//订单运费(含成交费),由明细表计算更新
                'subtotal_price'        => 0,//产品总金额(含成交费),由明细表计算更新
                'total_price'           => 0,//订单金额(含成交费),由明细表计算更新
                'final_value_fee'       => 0,//成交费,由明细表计算更新
                'insurance_amount'      => 0,//运费险(无)
                'currency'              => self::DEFAULT_CURRENCY,
                'ship_country'          => $ship_country,
                'ship_country_name'     => $orderAddressInfo['country'],//国家名称
                'paytime'               => $order['created_at'],
                'payment_status'        => Order::PAYMENT_STATUS_NOT,
                'ship_phone'            => $orderAddressInfo['phone'],
                'ship_name'             => $ship_name,
				'ship_street1'          => $orderAddressInfo['address'],
				'ship_street2'          => $orderAddressInfo['address2'],
				'ship_zip'              => $orderAddressInfo['pincode'],
				'ship_city_name'        => $orderAddressInfo['city'],
				'ship_stateorprovince'  => $orderAddressInfo['state'],
        		'ship_code'				=> '',
        		'ship_status'			=> Order::SHIP_STATUS_NOT,
        		'complete_status'		=> Order::COMPLETE_STATUS_DEFAULT,
        		'ori_create_time'       => self::transferToLocal($order['created_at']),
        		'ori_update_time'       => self::transferToLocal($order['created_at']),
        		'ori_pay_time'          => self::transferToLocal($order['created_at']),
        ));
        if(!$flag) throw new Exception("save order failure");
        return $orderID;
    }

    /**
     * @desc 保存订单详情信息
     * @param string $orderID
     */
    protected function saveOrderDetail($orderID){
        $order = $this->orderResponse;
        $orderAddressInfo = $this->orderAddressInfo;
        $orderDetailInfos = $this->orderDetailInfos;//订单明细
        $platformCode = Platform::CODE_PAYTM;
        $platformOrderIDNew = $this->_accountID.'-'.$order['platform_order_id'];//平台订单号(账号id-平台订单号)

        //2.订单详情数据
        OrderDetail::model()->deleteOrderDetailByOrderID($orderID);//删除详情

        //费用计算
        $totalPrice    = 0;//订单总金额
        $subTotalPrice = 0;//订单商品总金额
        $totalShipFee  = 0;//订单运费
        $totalTaxFee   = 0;//总税费
        $totalDiscount = 0;//总优惠金额
        $totalFeeAmt   = 0;//手续费
        foreach ($orderDetailInfos as $detail) {
            $subTotalPrice += $detail['price'] * $detail['qty_ordered'];
            $totalShipFee += $detail['shipping_amount'];
        }
        $totalPrice = round($subTotalPrice + $totalShipFee,2);
        $this->orderResponse['totalPrice'] = $totalPrice;//供交易信息更新记录
        $totalFvf = $totalPrice * $this->_comminsionFeeRate;//总成交费

        $orderSkuExceptionMsg = '';//订单sku异常信息
        $tmpShipFee = $tmpDiscount = $tmpTaxFee = 0;
        $tmpFvf = $tmpFeeAmt = $tmpInsuranceFee = 0;
        $tmpItemSalePriceAllot = 0;        
        $listCount = count($orderDetailInfos);//item数量
        $index = 1;
        foreach ($orderDetailInfos as $detail) {
            $skuOnline = trim($detail['sku']);//在线sku
            $sku = encryptSku::getRealSku( $skuOnline );
            $skuInfo = Product::model()->getProductInfoBySku($sku);

            $skuInfo2 = array();//发货sku信息
            $pending_status  = OrderDetail::PEDNDING_STATUS_ABLE;
            if (!empty($skuInfo)) {
                $realProduct = Product::model()->getRealSkuListNew($sku, $detail['qty_ordered'],$skuInfo);
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
                    'sku'       => 'unknown',
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

            $title = trim($detail['name']);
            if (mb_strlen($title)>100) {//title超长,截取OMS定义的长度值
                $title = mb_substr($title,0,100);
            }

            //费用平摊
            $unitSalePrice = floatval ($detail['price'] );//销售单价(含成交费)
            $quantity      = intval ($detail['qty_ordered'] );//购买数量
            $itemSalePrice = $unitSalePrice * $quantity;//产品金额   

            if ($index == $listCount) {
                $shipFee                = round($totalShipFee - $tmpShipFee,2);
                $discount               = round($totalDiscount - $tmpDiscount,2);
                $fvfAmt                 = round($totalFvf - $tmpFvf,2);
                $feeAmt                 = round($totalFeeAmt - $tmpFeeAmt,2);
                $taxFee                 = round($totalTaxFee - $tmpTaxFee,2);
                $itemSalePriceAllot     = round($subTotalPrice - $totalDiscount - $tmpItemSalePriceAllot, 2);//平摊后的item售价
                $unitSalePriceAllot     = round($itemSalePriceAllot/$quantity, 3);//平摊后的单价
            } else {
                $rate                  = $itemSalePrice/$subTotalPrice;
                $shipFee               = round($rate * $totalShipFee,2);//平摊后的运费 
                $discount              = round($rate * $totalDiscount,2);//平摊后的优惠金额
                $fvfAmt                = round($rate * $totalFvf,2);//平摊后的成交费
                $feeAmt                = round($rate * $totalFeeAmt,2);//平摊后的手续费
                $taxFee                = round($rate * $totalTaxFee,2);//平摊后的税费
                $itemSalePriceAllot    = round($itemSalePrice - $discount, 2);//平摊后的item售价
                $unitSalePriceAllot    = round($itemSalePriceAllot/$quantity, 3);//平摊后的单价

                $tmpShipFee            += $shipFee;
                $tmpDiscount           += $discount;
                $tmpFvf                += $fvfAmt;
                $tmpFeeAmt             += $feeAmt;
                $tmpTaxFee             += $taxFee;
                $tmpItemSalePriceAllot += $itemSalePriceAllot;
            }
            $index++;

            //保存订单明细
            $orderItemRow = array(                      
                    'transaction_id'          => $platformOrderIDNew.'-'.$detail['order_item_id'],
                    'order_id'                => $orderID,
                    'platform_code'           => $platformCode,
                    'item_id'                 => $detail['order_item_id'],
                    'title'                   => $title,
                    'sku_old'                 => $sku,
                    'sku'                     => $realProduct['sku'],
                    'site'                    => '',
                    'quantity_old'            => $detail['qty_ordered'],
                    'quantity'                => $realProduct['quantity'],
                    'sale_price'              => $unitSalePrice,//单价(含成交费)
                    'total_price'             => round($itemSalePrice+$shipFee,2),//产品总金额+平摊后的运费
                    'ship_price'              => $shipFee,//平摊后的运费(含成交费)
                    'final_value_fee'         => $fvfAmt,//平摊后的成交费
                    'currency'                => self::DEFAULT_CURRENCY,
                    'pending_status'          => $pending_status,
                    'create_time'             => date('Y-m-d H:i:s')
            );
            $detailId = OrderDetail::model()->addOrderDetail($orderItemRow);
            if(!$detailId) throw new Exception("save order detail failure");

            //保存订单明细扩展表
            $orderItemExtendRow = array(
                'detail_id'              => $detailId,
                'item_sale_price'        => $itemSalePrice,//产品金额(含成交费)
                'item_sale_price_allot'  => $itemSalePriceAllot,//平摊后的产品金额(含成交费，减优惠金额)
                'unit_sale_price_allot'  => $unitSalePriceAllot,//平摊后的单价(原销售单价-平摊后的优惠金额)
                'coupon_price_allot'     => 0,//平摊后的优惠金额
                'tax_fee_allot'          => 0,//平摊后的税费
                'insurance_amount_allot' => 0,//平摊后的运费险
                'fee_amt_allot'          => 0,//平摊后的手续费
            );
            $flag = OrderDetailExtend::model()->addOrderDetailExtend($orderItemExtendRow);
            if(!$flag) throw new Exception("save order detailExtend failure");

            //判断是否需要添加插头数据
            $flag = OrderDetail::model()->addOrderAdapter(array(
                        'order_id'          =>  $orderID,
                        'ship_country_name' =>  $orderAddressInfo['country'],//国家英文名称
                        'platform_code'     =>  $platformCode,
                        'currency'          =>  self::DEFAULT_CURRENCY
                    ),  $realProduct);
            if(!$flag) throw new Exception("save order adapter failure");

            //保存订单sku与销售关系数据
            $orderSkuOwnerInfo = array(
                'platform_code'         => $platformCode,//平台code
                'platform_order_id'     => $platformOrderIDNew,//平台订单号
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
            }            
        }

        if ($orderSkuExceptionMsg != '') {
            $pending_status = OrderDetail::PEDNDING_STATUS_KF;
            $cres = Order::model()->setExceptionOrder($orderID, OrderExceptionCheck::EXCEPTION_SKU_UNKNOWN, $orderSkuExceptionMsg);
            if(! $cres){
                throw new Exception ( 'Set order Exception Failure: '.$orderID);
            }
        }

        //更新订单主表相关信息
        $updateOrderData = array(
            'ship_cost'       => $totalShipFee,//订单运费(含成交费)
            'subtotal_price'  => $subTotalPrice,//产品总金额(含成交费)
            'total_price'     => $totalPrice,//订单金额(含成交费)
            'final_value_fee' => $totalFvf,//成交费
        );
        $flag = Order::model()->updateByPk($orderID, $updateOrderData);
        if(!$flag){
            throw new Exception("update order datas failure");
        }

        //保存订单扩展表数据        
        $orderExtend = new OrderExtend();
        $orderExtend->getDbConnection()->createCommand()->delete($orderExtend->tableName(),"platform_order_id='{$platformOrderIDNew}' and platform_code='". $platformCode ."'");
        $orderExtend->getDbConnection()->createCommand()->insert($orderExtend->tableName(),array(
            'order_id'          => $orderID,
            'platform_code'     => $platformCode,
            'platform_order_id' => $platformOrderIDNew,//平台订单号
            'account_id'        => $this->_accountID,//账号id
            'tax_fee'           => 0,//总税费
            'coupon_price'      => 0,//总优惠
            'currency'          => self::DEFAULT_CURRENCY,
            'create_time'       => date('Y-m-d H:i:s'),
            'payment_type'      => '',//api没返回
            'logistics_type'    => '',//api没返回 
        ));
        
        return $flag;
    }
    
    /**
     * @desc 保存订单交易信息
     * @param string $orderID
     */
    protected function saveTransaction($orderID){
        $order = $this->orderResponse;
        $platformCode = Platform::CODE_PAYTM;
        $platformOrderIDNew = $this->_accountID.'-'.$order['platform_order_id'];//平台订单号(账号id-平台订单号)    
        if($order['is_COD'] == 1){
            //@todo 后期同步订单需要将k3_cloud_status、sync_cloud_error重置为0
            $paymentType = 'COD';
            $paymentStatus = "Pending";
            $k3CloudStatus = 2;
            $syncCloudError = 6;
        }else{
            $paymentType = '';
            $paymentStatus = "Completed";
            $k3CloudStatus = 0;
            $syncCloudError = 0;
        }
        $flag = OrderTransaction::model()->saveTransactionRecord($platformOrderIDNew, $orderID, array(
                'order_id'              => $orderID,
                'first'                 => 1,
                'is_first_transaction'  => 1,
                'receive_type'          => OrderTransaction::RECEIVE_TYPE_YES,
                'account_id'            => $this->_accountID,
                'parent_transaction_id' => '',
                'order_pay_time'        => $order['created_at'],
                'amt'                   => $order['totalPrice'],//订单交易金额(含成交费)
                'fee_amt'               => 0,//无手续费
                'currency'              => self::DEFAULT_CURRENCY,
                'payment_status'        => $paymentStatus,
                'platform_code'         => $platformCode,
                'k3_cloud_status'       => $k3CloudStatus,
                'sync_cloud_error'      => $syncCloudError                
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
     * @param string $orderID
     * @throws Exception
     * @return boolean
     */
    protected function saveOrderPaypalTransactionRecord($orderID){
        $order = $this->orderResponse;
        $platformCode = Platform::CODE_PAYTM;
        $platformOrderIDNew = $this->_accountID.'-'.$order['platform_order_id'];//平台订单号(账号id-平台订单号)        
        $customerName = $order['customer_firstname'].($order['customer_lastname'] == ''?'':' '.$order['customer_lastname']);
        if($order['is_COD'] == 1){
            //@todo 后期同步订单需要将k3_cloud_status、sync_cloud_error重置为0
            $paymentType = 'COD';
            $paymentStatus = "Pending";
        }else{
            $paymentType = '';
            $paymentStatus = "Completed";
        }        
    	$flag = OrderPaypalTransactionRecord::model()->savePaypalRecord($platformOrderIDNew, $orderID, array(
    			'order_id'              => 	$orderID,
    			'receive_type'          => 	OrderPaypalTransactionRecord::RECEIVE_TYPE_YES,
    			'receiver_business'		=> 	'',
    			'receiver_email' 		=> 	'unknown@vakind.com',
    			'receiver_id' 			=> 	'vakind',
    			'payer_id' 				=> 	$order['customer_id'],
    			'payer_name' 			=> 	$customerName,
    			'payer_email' 			=> 	$order['customer_email'],
    			'payer_status' 			=> 	'',
    			'parent_transaction_id'	=>	'',
    			'transaction_type'		=>	'',
    			'payment_type'			=>	$paymentType,
    			'order_time'			=>	$order['created_at'],
    			'amt'					=>	$order['totalPrice'],//订单交易金额(含成交费)
    			'fee_amt'				=>	0,//无手续费
    			'tax_amt'				=>	0,//无税费
    			'currency'				=>	self::DEFAULT_CURRENCY,
    			'payment_status' 		=> 	$paymentStatus,
    			'note'					=>	'',
    			'modify_time'			=>	'0000-00-00 00:00:00'
    	));//保存交易付款信息
    	if($flag){
    		return true;
    	}
    	throw new Exception("save order trans paypal info failure");
    }

    /**
     * 订单确认
     * @param int $rowID
     * @return boolean
     */
    protected function acknowledgeOrder() {
        if(self::ORDER_PENDING_ACKNOWLEDGMENT != $this->orderResponse['status'] ) {
            return true;
        }
        $request = new AcknowledgeOrderRequest($this->orderResponse['platform_order_id']);
        $orderItemIdArr = array();
        foreach ($this->orderDetailInfos as $detail) {
            $orderItemIdArr[] = $detail['order_item_id'];
        }
        for($i=0;$i<3;$i++) {
            $request->setOrderItemIDs($orderItemIdArr);
            $request->setAccount($this->_accountID);
            $request->setStatus(AcknowledgeOrderRequest::ACK_ACCEPT);
            $response = $request->setRequest()->sendRequest()->getResponse();
            if(!$request->getIfSuccess()) {//失败请求3次
                $i++;
                sleep(3);
                continue;
            }
            return $request->getIfSuccess() && $response ? true : false;
        }
    }
        
    /**
     * @desc UTC时间格式转换
     * @param unknown $UTCTime
     * @return mixed
     */
    public function transferUTCTimeFormat($UTCTime){
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
    public function transferToLocal($UTCTime){
        return date("Y-m-d H:i:s", strtotime($UTCTime)+8*3600);
    }

    /**
     * @desc 获取异常信息
     * @return string
     */
    public function getExceptionMessage(){
        return $this->exception;
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
            ->from(self::tableName())
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
            ->from(self::tableName())
            ->where($where);
        $order != '' && $cmd->order($order);
        return $cmd->queryAll();
    }    
    
    /**
     * addPaytmLog
     * @param int $accountID 
     * @param int $status    
     * @param string $message   
     * @param string $eventName
     */
    public function addPaytmLog($accountID,$status,$message,$eventName=PaytmLog::EVENT_SYNC_ORDER) {
        $logModel = new PaytmLog();
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