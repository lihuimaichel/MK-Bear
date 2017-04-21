<?php
/**
 * @desc Lazada订单拉取
 * @author Gordon
 * @since 2015-08-07
 */
class LazadaOrder extends LazadaModel{
    
    const EVENT_NAME        = 'getorder';
    const EVENT_NAME_CHECK  = 'check_getorder';
    const EVENT_NAME_CANCEL = 'cancelorder';
    
    /** @var object 拉单返回信息*/
    public $orderResponse = null;
    
    /** @var int 账号ID*/
    public $_accountID = null;

    public $_accountAutoID = null;
    
    /** @var account id **/
    public $_account_id = null;
    
    /** @var integer 站点ID */
    public $_siteID = null;
    
    /** @var string 异常信息*/
    public $exception = null;
    
    /** @var int 日志编号*/
    public $_logID = 0;
    
    /** @var int 手续费比例*/
    public $_amtfeeRate = 0.02;
    
    /** @var int 成交费比例*/
    public $_valuefeeRate = 0.08;
    public $_valuefeeRateNew = array(
        1 => 0.04,  //'马来西亚站点'
        2 => 0.04,  //'新加坡'
        3 => 0,     //'印尼站点'
        4 => 0.04,  //'泰国站点'
        5 => 0.04,  //'菲律宾站点'
        6 => 0.04,  //'越南站点'
    );
    
    public $_prefix = "";

    /** @var string lazada订单状态*/
    const STATUS_PENDING    = 'pending';
    const STATUS_CANCEL     = 'canceled';
    const STATUS_READY      = 'ready_to_ship';
    const STATUS_DELIVERED  = 'delivered';
    const STATUS_RETURNED   = 'returned';
    const STATUS_SHIPED     = 'shipped';
    const STATUS_FAILED     = 'failed';
   
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
     * @desc 设置账号分组ID
     * @param integer $accountID 账号分组ID
     */
    public function setAccountID($accountID){
        $this->_accountID = $accountID;
        $accmodel = new LazadaAccount();
        $accountInfo = $accmodel->getApiAccountByIDAndSite($accountID, $this->_siteID);
        $siteName = LazadaSite::getSiteShortName($accountInfo['site_id']);
        $this->_prefix = $siteName . '-' . strtolower(substr($accountInfo["seller_name"],0,1)) .'-';
        $this->_account_id = $accountInfo['old_account_id'];        //老系统的账户id
        $this->_accountAutoID = $accountInfo['id'];
    }

    /**
     * @desc 设置账号分组ID
     * @param integer $accountID 账号自增ID
     */
    public function setAccountIDNew($accountID){
        $accmodel = new LazadaAccount();
        $accountInfo = $accmodel->getApiAccountInfoByID($accountID);
        $siteName = LazadaSite::getSiteShortName($accountInfo['site_id']);
        $this->_prefix = $siteName . '-' . substr($accountInfo["seller_name"],0,1).'-';
        $this->_accountID = $accountInfo['account_id'];
        $this->_account_id = $accountInfo['old_account_id'];        //老系统的账户id
        $this->_accountAutoID = $accountInfo['id'];
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
     * @desc 设置站点ID
     * @param int $siteID
     */
    public function setSiteID($siteID) {
    	$this->_siteID = $siteID;
    }
    
    /**
     * @desc 获取拉单时间段
     */
    public function getTimeArr($accountID){
        $lastLog = LazadaLog::model()->getLastLogByCondition(array(
            'account_id'    => $accountID,
            'event'         => self::EVENT_NAME,
            'status'        => LazadaLog::STATUS_SUCCESS,
        ));
        return array(
            'start_time'    => !empty($lastLog) ? date('Y-m-d H:i:s',strtotime($lastLog['end_time']) - 15*60) : date('Y-m-d H:i:s',time() - 86400*10),
            'end_time'      => date('Y-m-d H:i:s',time()),
        );
    }

    /**
     * @desc 获取异常信息
     * @return string
     */
    public function getExceptionMessage(){
        return $this->exception;
    }

    /**
     * @desc 根据条件获取订单
     * @param array $timeArr
     * @param int $order_status
     */
    public function getOrders($timeArr, $order_status=''){
        $accountID = $this->_accountID;
        $path = 'lazada/getOrders/'.date("Ymd").'/'.$this->_accountAutoID.'/'.date("H");

        $nowTime = date('Y-m-d H:i:s');
        $request = new GetOrdersRequest();
        $request->setCreateAfter(date('Y-m-d\TH:i:s', strtotime($timeArr['start_time'])));
        $request->setCreateBefore(date('Y-m-d\TH:i:s', strtotime($timeArr['end_time'])));
        $request->setOrderStatus( LazadaOrder::STATUS_PENDING );

        //抓取订单信息
        $page = 1;
        $finishMark = 0;
        $errMsg = '';
        while( !$finishMark ){
            // if ($page> 1) {
            //     sleep(2);
            // }
            $request->setPageNum($page);
            $response = $request->setSiteID($this->_siteID)->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
            //MHelper::writefilelog($path.'/GetOrdersResponse_'.$page.'.txt', print_r($response,true)."\r\n");// fortest
           
            if( isset($response->Body->Orders->Order) && count($response->Body->Orders->Order) > 0 ){
                //循环订单信息
                foreach($response->Body->Orders->Order as $order) {
                    try {
                        $orderModel     = Order::model();

                        $order_id  = AutoCode::getCodeNew('order'); // 获取订单号
                        if ( empty($order_id) ) {
                            throw new Exception("getCodeNew Error");
                        } else {
                            $order_id = $order_id . 'LZD';
                        }

                        $orderId_array  = (array)$order->OrderId;
                        $orderId        = $orderId_array[0];
                        $orderStatus    = (string)$order->Statuses->Status;
                        // if ('pending' != strtolower($orderStatus) ) {
                        //     continue;
                        // }

                        // 站点前缀-账号首字母-orderNumber-orderId
                        // 检查订单是否存在
                        $platform_orderId = $this->_prefix . $order->OrderNumber . '-' . $orderId;
                        $orderInfo = $orderModel->getOrderInfoByPlatformOrderID( $platform_orderId, Platform::CODE_LAZADA );

                        $paymentType = "";
                        if( $order->PaymentMethod == 'CashOnDelivery' ){
                        	$paymentType = "COD";
                        }             

                        //pending状态的订单处理
                        if( !empty($orderInfo) ){
                            echo '存在已付款的订单，不更新<br>';//fortest
                            continue;
                        }

                        $insert_orderID = !empty($orderInfo) ? $orderInfo['order_id'] : $order_id;//获取订单号                        
                        if ( empty($insert_orderID) ) {
                            throw new Exception("订单id为空");
                        }

                        $dbTransaction = $orderModel->dbConnection->beginTransaction();//开启事务 

                        //2保存detail表信息
                        $request_detail = new GetOrderItemsRequest();
                        $request_detail->setOrderID($orderId);
                        $response_detail = $request_detail->setSiteID($this->_siteID)->setAccount($accountID)->setRequest()->sendRequest()->getResponse();

                       //MHelper::writefilelog($path.'/GetOrderItemsResponse_'.$page.'.txt', print_r($response_detail,true)."\r\n");// fortest

                        if( ! isset( $response_detail->Body->OrderItems ) ){
                        	throw new Exception ('OrderItems is empty ');
                        }

                        //1保存order主表信息
                        $street1 = trim($order->AddressShipping->Address1);//Address1
                        $street2 = trim($order->AddressShipping->Address2);//Address2
                        $street3 = (isset($order->AddressShipping->Address3) && trim($order->AddressShipping->Address3) != '' ? ','. trim($order->AddressShipping->Address3) : '');
                        $street4 = (isset($order->AddressShipping->Address4) && trim($order->AddressShipping->Address4) != '' ? ','. trim($order->AddressShipping->Address4) : '');
                        $street5 = (isset($order->AddressShipping->Address5) && trim($order->AddressShipping->Address5) != '' ? ','. trim($order->AddressShipping->Address5) : '');
                        
                        //SG站点： Address3, Address4, Address5 不用合并到 Address2, 其他站点不变
                        if ($this->_siteID != 2) {
                            $street2 .= $street3 . $street4 . $street5;
                            $city = trim($order->AddressShipping->City);
                        } else {
                            $city = '';
                        }

                        if($this->_siteID == 6 || $this->_siteID == 3){
                            //越南站和印尼站东7区utc+7
                            $ori_create_time = date('Y-m-d H:i:s', strtotime( trim($order->CreatedAt) ) + 3600 );
                            $ori_update_time = date('Y-m-d H:i:s', strtotime( trim($order->UpdatedAt) ) + 3600);
                            $ori_pay_time = date('Y-m-d H:i:s', strtotime( trim($order->CreatedAt) ) + 3600);
                        } else {
                            //其他站东八区utc+8
                            $ori_create_time = date('Y-m-d H:i:s', strtotime( trim($order->CreatedAt) ));
                            $ori_update_time = date('Y-m-d H:i:s', strtotime( trim($order->UpdatedAt) ));
                            $ori_pay_time = date('Y-m-d H:i:s', strtotime( trim($order->CreatedAt) ));
                        }

                        if ( date('Y-m-d', strtotime( trim($order->CreatedAt) ) ) >= '2016-10-14' ) {
                            $this->_valuefeeRate = $this->_valuefeeRateNew[$this->_siteID];
                        }

                        $orderData = array(
                                'order_id'              => $insert_orderID,
                                'platform_code'         => Platform::CODE_LAZADA,
                                'buyer_id'              => $order->CustomerFirstName . ' ' . $order->CustomerLastName,
                                'platform_order_id'     => $platform_orderId,
                                'account_id'            => $this->_account_id,
                                'log_id'                => $this->_logID,
                                'order_status'          => $orderStatus,
                                'timestamp'             => date('Y-m-d H:i:s'),
                                'created_time'          => date('Y-m-d H:i:s', strtotime( trim($order->CreatedAt) )),
                                'last_update_time'      => date('Y-m-d H:i:s', strtotime( trim($order->UpdatedAt) )),
                                'paytime'               => date('Y-m-d H:i:s', strtotime( trim($order->CreatedAt) )),
                                'ship_name'             => trim($order->AddressShipping->FirstName).' '.trim($order->AddressShipping->LastName),
                                'ship_country'          => Country::model()->getAbbrByEname(trim($order->AddressShipping->Country)),
                                'ship_country_name'     => trim($order->AddressShipping->Country),
                                'payment_status'        => Order::PAYMENT_STATUS_END,
                                'ship_phone'            => trim($order->AddressShipping->Phone),
                                'ship_street1'          => $street1,
                                'ship_street2'          => $street2,
                                'ship_zip'              => trim($order->AddressShipping->PostCode),
                                'ship_city_name'        => $city,
                                'ship_stateorprovince'  => '',
                                'total_price'           => floatval($order->Price),
                                'ori_create_time'       => $ori_create_time,
                                'ori_update_time'       => $ori_update_time,
                                'ori_pay_time'          => $ori_pay_time,
                        );

                        if ( $this->_accountAutoID == 35 ) {
                            $orderData['complete_status'] = 3;//已完成
                            $orderData['ship_status'] = 2;//已出货
                        }

                        $flag = Order::model()->saveOrderRecord($orderData);

                        if (empty($flag)) {
                            throw new Exception(" save Order Error");
                        }                        

                        $OrderTransaction_data = array(
                            'paytime' => date('Y-m-d H:i:s', strtotime($order->CreatedAt)),
                            'total_price' => floatval($order->Price),
                        );

                        //如果有订单留言保存订单留言
                        if (isset($order->Remarks) && !empty($order->Remarks)){
                            $orderNoteModel = new OrderNote();
                            $orderNoteModel->save(false, array(
                                'order_id'      => $insert_orderID,
                                'note'          => $order->Remarks,
                                'create_time'   => date('Y-m-d H:i:s'),
                                'modify_time'   => date('Y-m-d H:i:s'),
                            ));
                        }

                        //插入订单详情信息
                        OrderDetail::model()->deleteOrderDetailByOrderID($insert_orderID);//删除详情
                        $shipAmount = 0; 
                        $finalValueFee = 0; 
                        $fee_amt = 0;
                        $subTotal = 0; 
                        $currency = '';
                        $OrderItems = (array)$response_detail->Body->OrderItems;

                        if(is_array($OrderItems['OrderItem'])){
                            $newOrderItem = $OrderItems['OrderItem'];
                        } else {
                            $newOrderItem = array($OrderItems['OrderItem']);
                        }

                        $formatDetailInfos = array();//匹配赠品数据
                        $partDetailInfos = array();//订单sku与销售员关系数据
                        $orderExceptionMsg = "";
                        foreach($newOrderItem as $item){
                            $part_data = array();
                            $defaultCount = 1;
                            $skuOnline = trim($item->Sku);
                            $sku = encryptSku::getRealSku($skuOnline);
                            $skuInfo = Product::model()->getProductInfoBySku($sku);
                            if( !empty($skuInfo) ){
                                //可以查到对应产品
                                $realProduct = Product::model()->getRealSkuList($sku, $defaultCount);
                            } else {
                                $realProduct = array(
                                    'sku'       => 'unknown',
                                    'quantity'  => $defaultCount,
                                );
                                Order::model()->setOrderCompleteStatus(Order::COMPLETE_STATUS_PENGDING, $insert_orderID);
                            }
                            if($skuInfo && $skuInfo['product_is_multi'] == Product::PRODUCT_MULTIPLE_MAIN){
                                $childSku = ProductSelectAttribute::model()->getChildSKUListByProductID($skuInfo['id']);
                                !empty($childSku) && $orderExceptionMsg .= "sku:{$sku}为主sku<br/>";
                            }

                            $orderItemRow = array(
                                'order_id'              => $insert_orderID,
                                'platform_code'         => Platform::CODE_LAZADA,
                                'item_id'               => trim($item->OrderItemId),
                                'transaction_id'        => $platform_orderId,
                                'title'                 => trim($item->Name),
                                'sku_old'               => $sku,
                                'sku'                   => $realProduct['sku'],
                                'quantity_old'          => $defaultCount,
                                'quantity'              => $realProduct['quantity'],
                                'sale_price'            => floatval($item->ItemPrice),
                                'total_price'           => floatval($item->ItemPrice),
                                'currency'              => trim($item->Currency),
                                'ship_price'            => floatval($item->ShippingAmount),
                                'final_value_fee'       => round(floatval($item->ItemPrice) * $this->_valuefeeRate, 2),//成交费
                                'site'                  => LazadaSite::getSiteShortName($this->_siteID),
                                'create_time'           => date('Y-m-d H:i:s'),
                            );

                            //MHelper::writefilelog($path.'/orderItemRow'.$page.'.log', print_r($orderItemRow,true)."\r\n");// fortest

                            $orderItemID = OrderDetail::model()->addOrderDetail($orderItemRow);
                            if (!$orderItemID) {
                                throw new Exception("Save OrderDetail Failure");
                            }

                            //订单sku与销售员关系数据
                            $part_data = array(
                                'platform_code'         => Platform::CODE_LAZADA,
                                'platform_order_id'     => $platform_orderId,//平台
                                'online_sku'            => $skuOnline,//在线sku
                                'account_id'            => $this->_account_id,//账号old id
                                'site'                  => $this->_siteID,
                                'sku'                   => $orderItemRow['sku_old'],//系统sku
                                'item_id'               => implode('-',array($this->_siteID,$this->_account_id,$skuOnline)),
                                'order_id'              => $insert_orderID,
                            );
                            $partDetailInfos[] = $part_data;

                            //匹配赠品数据
                            $formatDetailInfo = array(
                                'transaction_id' => $platform_orderId,//平台
                                'item_id'        => $orderItemRow['item_id'],
                                'account_id'     => $this->_accountAutoID,
                                'sku'            => $orderItemRow['sku_old'],
                                'quantity'       => $orderItemRow['quantity_old'],
                                'currency'       => $orderItemRow['currency'],
                            ); 
                            $formatDetailInfos[] = $formatDetailInfo;

                            $shipAmount         += floatval($item->ShippingAmount);
                            $finalValueFee      += floatval($item->ItemPrice) * $this->_valuefeeRate;
                            $fee_amt            += floatval($item->ItemPrice) * $this->_amtfeeRate;
                            $subTotal           += floatval($item->ItemPrice);
                            $currency           = $item->Currency;
                        }
                        $finalValueFee  = round($finalValueFee, 2);
                        $fee_amt        = round($fee_amt, 2);
                        
                        //判断是否有异常存在
                        if($orderExceptionMsg){
                            $res = Order::model()->setExceptionOrder($insert_orderID, OrderExceptionCheck::EXCEPTION_SKU_UNKNOWN, $orderExceptionMsg);
                            if(! $res){
                                throw new Exception ( 'Set order Exception Failure: '.$insert_orderID);
                            }
                        }

                        //3.完善订单主信息
                        $flag_update = Order::model()->updateColumnByOrderID($insert_orderID,array(
                            'ship_cost'             => $shipAmount,
                            'subtotal_price'        => $subTotal,
                            'final_value_fee'       => $finalValueFee,
                            'currency'              => $currency,
                            'platform_order_id'     => $platform_orderId,
                        ));
                        if (!$flag_update) {
                            throw new Exception("Update ColumnByOrderID Failure");
                        }

                        //匹配赠品
                        $isOk = $this->addGift($insert_orderID,$platform_orderId,$formatDetailInfos);
                        if (!$isOk) {
                            throw new Exception("addGift Failure");
                        }

                        //4.插入订单交易信息
                        if($paymentType == "COD"){
                        	//@todo 后期同步订单需要将k3_cloud_status、sync_cloud_error重置为0
                        	$paymentStatus = "Pending";
                        	$k3CloudStatus = 2;
                        	$syncCloudError = 6;
                        }else{
                        	$paymentStatus = "Completed";
                        	$k3CloudStatus = 0;
                        	$syncCloudError = 0;
                        }
                        $isok = OrderTransaction::model()->saveTransactionRecord($insert_orderID, $insert_orderID, array(
                            'order_id'              => $insert_orderID,
                            'first'                 => 1,
                            'is_first_transaction'  => 1,
                            'receive_type'          => OrderTransaction::RECEIVE_TYPE_YES,
                            'account_id'            => $this->_account_id,
                            'parent_transaction_id' => '',
                            'order_pay_time'        => $OrderTransaction_data['paytime'],
                            'amt'                   => $OrderTransaction_data['total_price'],
                            'fee_amt'               => $fee_amt,
                            'currency'              => $currency,
                            'payment_status'        => $paymentStatus,
                            'platform_code'         => Platform::CODE_LAZADA,
                        	'k3_cloud_status'		=>	$k3CloudStatus,
                        	'sync_cloud_error'		=>	$syncCloudError
                        ));
                        if (!$isok) {
                            throw new Exception("Save OrderTransaction Failure");
                        }

                        //5.插入paypal订单交易记录信息
                        $TransactionRecord = array(
                            'transaction_id'        => $insert_orderID,
                            'order_id'              => $insert_orderID,
                            'receive_type'          => OrderTransaction::RECEIVE_TYPE_YES,
                            'receiver_email'        => 'unknown@vakind.com',
                            'payer_name'            => $order->CustomerFirstName . ' ' . $order->CustomerLastName,
                            'order_time'            => date('Y-m-d H:i:s', strtotime($order->CreatedAt)),
                            'amt'                   => $OrderTransaction_data['total_price'],
                            'fee_amt'               => $fee_amt,
                            'currency'              => $currency,
                            'payment_status'        => $paymentStatus,
                        	'payment_type'			=> $paymentType
                        );
                        $isok = OrderPaypalTransactionRecord::model()->dbConnection->createCommand()->replace(OrderPaypalTransactionRecord::tableName(), $TransactionRecord);
                        if (!$isok) {
                            throw new Exception("Save PaypalTransactionRecord Failure");
                        }
                            
                        //保存订单sku与销售关系
                        $skuOwnerSaveFlag = true;
                        if (!empty($partDetailInfos)) {
                            foreach ($partDetailInfos as $orderSkuOwnerInfo) {
                                $addRes = OrderSkuOwner::model()->addRow($orderSkuOwnerInfo);
                                if( $addRes['errorCode'] != '0' ){
                                    $skuOwnerSaveFlag = false;
                                }
                            }
                        }
                        if (!$skuOwnerSaveFlag) {
                            throw new Exception("Save OrderSkuOwnerInfo Failure");
                        } 
                        $dbTransaction->commit();
                        sleep(3);
                    } catch (Exception $e) {
                        $errMsg = $platform_orderId.' ## '.$e->getMessage();
                        echo $errMsg."<br>";//fortest
                        //MHelper::writefilelog($path.'/Exception.log', $errMsg."\r\n");// fortest
                        $this->setExceptionMessage( $errMsg );
                        $dbTransaction->rollback();
                    }
                }
                $page++;
                if( count($response->Body->Orders->Order) < $request->_limit ){//抓取数量小于每页数量，说明抓完了
                    $finishMark = true;
                    break;
                }
            } else {
                //抓取失败
                if(isset($response->Head->TotalCount) && $response->Head->TotalCount==0){
                    $this->setExceptionMessage(Yii::t('lazada', 'No Order!'));
                    //没拉到取消单是正常现象返回true
                    if($order_status == LazadaOrder::STATUS_CANCEL){
                        return true;
                    }
                } else {                
                    $this->setExceptionMessage($request->getErrorMsg());
                }
                return false;
            }
        }
        return $errMsg == '' ? true : false;
    }   
    
    /**
     * @desc 匹配赠品 
     * @param string $orderID
     * @param array $platformOrderID
     * @param array $formatDetailInfos
     * @return boolean|Ambigous <number, boolean>
     */
    public function addGift($orderID,$platformOrderID,$formatDetailInfos) {
        try {       
            foreach ($formatDetailInfos as $detail) {
                $transactionId      = $detail['transaction_id'];
                $itemId             = $detail['item_id'];
                $accountID          = $detail['account_id'];
                $currency           = $detail['currency'];
                $sku                = $detail['sku'];
                $quantity           = $detail['quantity'];
                //查找sku对应的赠品
                $giftSkuInfo = LazadaGiftManage::model()->getOneByCondition('gift_sku',"account_id={$accountID} and sku='{$sku}' and is_delete=0");
                if (!empty($giftSkuInfo)) {
                    $giftSku = trim($giftSkuInfo['gift_sku']);
                    $info = $this->dbConnection->createCommand ()
                                    ->select ( 'id,quantity' )
                                    ->from ( OrderDetail::tableName() )
                                    ->where ( "item_id ='{$itemId}' and order_id='{$orderID}' and sku='{$giftSku}' " )
                                    ->queryRow ();
                    if (empty($info)) {
                        $data = array(
                                'transaction_id'    => $platformOrderID,
                                'order_id'          => $orderID,
                                'platform_code'     => Platform::CODE_LAZADA,
                                'currency'          => $currency,
                                'title'             => 'gift '.$itemId,
                                'sku'               => $giftSku,
                                'quantity'          => $quantity,
                                'is_adapter'        => 0,
                                'detail_type'       => 0,
                                'create_user_id'    => 0,
                                'create_time'       => date('Y-m-d H:i:s'),
                                'modify_user_id'    => 0,
                                'modify_time'       => '0000-00-00 00:00:00'
                        );
                        $this->dbConnection->createCommand()->insert(OrderDetail::tableName(), $data);
                    }
                }
            }
            return true;
        } catch (Exception $e) {
            $this->setExceptionMessage($e->getMessage());
            return false;
        }
    }  

}