<?php
/**
 * @desc Lazada订单下载model
 * @author yangsh
 * @since 2016-10-12
 */
class LazadaOrderDownload extends LazadaOrderMain {
    
    /** @var string 异常信息*/
    public $exception           = null;
    
    /** @var int 账号ID*/
    protected $_AccountID       = null;
    
    /** @var int 账号分组ID */
    protected $_AccountGroupID  = null;

    /** @var int 旧账号ID*/
    protected $_OldAccountID    = null;    
    
    /** @var int 站点id */
    protected $_SiteID          = null;        
    
    /** @var int 日志编号*/
    protected $_LogID           = 0;
    
    /** @var array 拉单时间段 */
    protected $_TimeArr         = array(); 
    
    protected $_Mode            = 1;//1:CreatedAfter 2:UpdatedAfter 
    
    /** @var int 平台订单号前缀 */
    protected $_Prefix          = '';
    
    /** @var string order_id */
    protected $_OrderIdList     = '';

    /** @var string 订单状态 */
    protected $_OrderStatus     = '';

    /** @var int 拉单方式*/
    protected $_FromCode        = '';

    /** @var int 手续费比例*/
    public $_amtfeeRate = 0.02;
    
    /** @var int 成交费比例*/
    public $_valuefeeRateNew = array(
        1 => 0.04,  //'马来西亚站点'
        2 => 0.04,  //'新加坡'
        3 => 0,     //'印尼站点'
        4 => 0.04,  //'泰国站点'
        5 => 0.04,  //'菲律宾站点'
        6 => 0.04,  //'越南站点'
    );      

    public static function model($className = __CLASS__) {
        return parent::model($className);
    } 

    /**
     * @desc 设置异常信息
     * @param string $message
     */
    public function setExceptionMessage($message){
        $this->exception = $message;
        return $this;
    }

    /**
     * @desc 拉单方式
     * @param int $fromCode
     */
    public function setFromCode($fromCode) {
        $this->_FromCode = $fromCode;
        return $this;
    }

    /**
     * 设置日志编号
     *
     * @param int $logID            
     */
    public function setLogID($logID) {
        $this->_LogID = $logID;
        return $this;
    }   

    /**
     * 设置账号ID
     * @param int $accountID
     */
    public function setAccountID($accountID) {
        $accountInfo           = LazadaAccount::model()->getApiAccountInfoByID($accountID);
        $siteName              = LazadaSite::getSiteShortName($accountInfo['site_id']);
        $this->_AccountID      = $accountID;
        $this->_AccountGroupID = $accountInfo['account_id'];
        $this->_OldAccountID   = $accountInfo['old_account_id'];//老系统的账户id
        $this->_SiteID         = $accountInfo['site_id'];
        $this->_Prefix         = $siteName . '-' . strtolower(substr($accountInfo["seller_name"],0,1)) .'-';
        return $this;
    }

    /**
     * 设置Mode
     * @param int $mode
     */
    public function setMode($mode) {
        $this->_Mode = $mode;
        return $this;
    }   

    /**
     * @desc 设置订单状态
     * @param string $orderStatus
     */
    public function setOrderStatus($orderStatus) {
        $this->_OrderStatus = $orderStatus;
        return $this;
    }

    /**
     * @desc 订单ID
     * @param string $orderIdList
     */
    public function setOrderIdList($orderIdList) {
        $this->_OrderIdList = $orderIdList;
        return $this;
    } 

    /**
     * 设置拉单时间段
     * @param array $timeArr
     */
    public function setTimeArr($timeArr = array()) {
        $eventName = LazadaLog::EVENT_GETORDER;//拉单事件
        if ( empty($timeArr) ) {
            $lastLog = LazadaLog::model()->getLastLogByCondition(array(
                'account_id'    => $this->_AccountID,
                'event'         => $eventName,
                'status'        => LazadaLog::STATUS_SUCCESS,
            ));
            $timeArr = array(
                'start_time'    => !empty($lastLog) ? date('Y-m-d H:i:s',strtotime($lastLog['end_time']) - 15*60) : date('Y-m-d H:i:s',time() - 3*86400),
                'end_time'      => date('Y-m-d H:i:s',time()),
            );
        }
        $this->_TimeArr = $timeArr;
        return $this;
    }

    /**
     * @desc 获取异常信息
     * @return string
     */
    public function getExceptionMessage(){
        return $this->exception;
    }    

    /**
     * [getTimeArr description]
     * @return [type] [description]
     */
    public function getTimeArr() {
        return $this->_TimeArr;
    }

    /**
     * @desc 获取需要抓取订单明细的订单id
     * @return array
     */
    public function getNeedDownDetailOrders($condition='') {
        $where = 'is_downdetail='.LazadaOrderMain::IS_DOWNDETAIL_NO;
        if ($this->_AccountID) {
            $where .= " and seller_account_id='{$this->_AccountID}'";
        }
        if ($this->_OrderIdList != '') {
            $where .= " and order_id in(".$this->_OrderIdList.")";
        }
        $where .= $condition;
        $order = "created_at desc";
        $limit = 5000;
        $res = LazadaOrderMain::model()->getListByCondition('seller_account_id,order_id',$where,$order,$limit);
        $rtn = array();
        if (!empty($res)) {
            foreach ($res as $v) {
                $rtn[$v['seller_account_id']][] = $v['order_id'];
            }
        }
        return $rtn;
    }

    /**
     * @desc 拉取订单
     */
    public function startGetOrders() {
        $path           = 'lazada/getOrders/'.date("Ymd").'/'.$this->_AccountID.'/'.date("H");
        $accountID      = $this->_AccountID;
        $request        = new GetOrdersRequest();
        if (!empty($this->_TimeArr['start_time']) && !empty($this->_TimeArr['end_time'])) {
            $startTime  = $this->_TimeArr['start_time'];
            $endTime    = $this->_TimeArr['end_time'];
            if($this->_Mode == 1) {
                $request->setCreateAfter(date('Y-m-d\TH:i:s', strtotime($startTime)));
                $request->setCreateBefore(date('Y-m-d\TH:i:s', strtotime($endTime))); 
            } else {
                $request->setUpdatedAfter(date('Y-m-d\TH:i:s', strtotime($startTime)));
                $request->setUpdatedBefore(date('Y-m-d\TH:i:s', strtotime($endTime)));
            }
        }
        if (!empty($this->_OrderStatus)) {
            $request->setOrderStatus($this->_OrderStatus);
        }
        $errMsg = '';
        $page = 1;
        while ( $page <= ceil($request->_totalCount / $request->_limit ) ) {
            try {
                $request->setPageNum ( $page );   
                $response = $request->setApiAccount($accountID)
                                    ->setRequest()->sendRequest()->getResponse();
                // MHelper::printvar($response);
                // MHelper::writefilelog($path.'/response_'.$page.'.txt', print_r($response,true)."\r\n");
                //返回失败
                if (!$request->getIfSuccess()) {
                    throw new Exception('ErrPageNo:'.$page.'##'.$request->getErrorMsg (), 110);
                }
                if (!isset($response->Head->TotalCount) ) {
                   throw new Exception('TotalCount is Empty', 112);
                }
                if ( intval($response->Head->TotalCount) <= 0 ) {
                    break;
                }
                $request->_totalCount = intval($response->Head->TotalCount);
                $page++;
                if (empty($response->Body->Orders->Order)) {
                    throw new Exception('Orders is Empty',113);
                }
                //开始保存订单
                foreach ( $response->Body->Orders->Order as $order ) {
                    try {
                        $orderId = trim($order->OrderId);
                        $isOk = $this->saveOrderInfo($order);
                        if (!$isOk) {
                            echo $orderId.' ## '. $this->getExceptionMessage()."<br>";
                            throw new Exception('Save Order Error', 4);
                        }                                    
                    } catch (Exception $e) {
                        $errMsg .= $orderId.' ## '. $e->getMessage()."<br>";
                        continue;
                    }
                }
            } catch (Exception $e) {
                $errMsg .= $e->getMessage();
                break;
            }
        }
        //MHelper::writefilelog($path.'/exception.txt', $errMsg."\r\n");
        $this->setExceptionMessage($errMsg);
        return $errMsg == '' ? true : false;
    }

    /**
     * @desc 拉取订单明细
     */
    public function startGetMultipleOrderItems() {
        $path = 'lazada/getMultipleOrderItems/'.date("Ymd").'/'.$this->_AccountID.'/'.date("H");
        $accountID      = $this->_AccountID;
        $orderIdList    = $this->_OrderIdList;
        $errMsg         = '';
        try {
            $request    = new GetMultipleOrderItemsRequest();
            $response   = $request->setApiAccount($accountID)
                                  ->setOrderIdList($orderIdList)
                                  ->setRequestNew()->sendRequest()->getResponse();
            // MHelper::printvar($response,false);
            // MHelper::writefilelog($path.'/response.txt', print_r($response,true)."\r\n");
            if (!$request->getIfSuccess()) {
                throw new Exception($request->getErrorMsg (), 1);
            }
            if (empty($response->Body->Orders->Order)) {
                throw new Exception('orders is empty', 2);
            }
            foreach ($response->Body->Orders->Order as $order) {
                try {
                    $orderId = trim($order->OrderId);
                    $isOk = $this->saveOrderDetailInfos($order);         
                    if (!$isOk) {
                        echo $orderId.' # '.$this->getExceptionMessage()."<br>";
                        throw new Exception("Save OrderItems Error", 3);
                    }
                } catch (Exception $e) {
                    $errMsg .= $orderId. ' # ' .$e->getMessage();
                    continue;
                }
            }
        } catch (Exception $e) {
            $errMsg .= $e->getMessage();
        }
        //MHelper::writefilelog($path.'/exception.txt', $e->getMessage()."\r\n");
        $this->setExceptionMessage($errMsg);
        return $errMsg == '' ? true : false;
    }

    /**
     * @desc 保存订单主表信息
     * @param  object $order
     * @return boolean
     */
    private function saveOrderInfo($order) {
        try {
            $nowTime         = date('Y-m-d H:i:s');
            $orderNumber     = trim($order->OrderNumber);
            $orderID         = trim($order->OrderId);
            if ($orderNumber == '' || $orderID == '') {
                throw new Exception("orderId or orderNumber is empty ");
            }
            $platformOrderID = $this->_Prefix . $orderNumber . '-' . $orderID;
            $addressBilling  = isset($order->AddressBilling) ? $order->AddressBilling : null;//object
            $addressShipping = isset($order->AddressShipping) ? $order->AddressShipping : null;//object
            $orderNote       = isset($order->Remarks) ? trim($order->Remarks) : '';
            if ($orderNote != '') {//mysql编码格式utf-8格式，不支持带四字节的字符串插入
                $orderNote = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $orderNote);
            }
            $giftMessage     = trim($order->GiftMessage);
            if ($giftMessage != '') {//mysql编码格式utf-8格式，不支持带四字节的字符串插入
                $giftMessage = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $giftMessage);
            }
            $statuses = (array)$order->Statuses->Status;
            $orderStatus = isset($statuses[0]) ? trim($statuses[0]) : '';
            $data = array(
                'platform_order_id'               => $platformOrderID,
                'order_id'                        => $orderID,
                'order_number'                    => $orderNumber,
                'seller_account_id'               => $this->_AccountID,
                'old_account_id'                  => $this->_OldAccountID,
                'site_id'                         => $this->_SiteID,
                'commission_rate'                 => $this->_valuefeeRateNew[$this->_SiteID],
                'paymentfee_rate'                 => $this->_amtfeeRate,
                'customer_first_name'             => trim($order->CustomerFirstName),
                'customer_last_name'              => trim($order->CustomerLastName),             
                'payment_method'                  => trim($order->PaymentMethod),
                'remarks'                         => $orderNote,
                'price'                           => floatval($order->Price),
                'gift_option'                     => intval($order->GiftOption),
                'gift_message'                    => $giftMessage,
                'created_at'                      => $order->CreatedAt,
                'updated_at'                      => isset($order->UpdatedAt) ? $order->UpdatedAt : '0000-00-00 00:00:00',
                'address_bill_first_name'         => isset($addressBilling->FirstName) ? trim($addressBilling->FirstName) : '',
                'address_bill_last_name'          => isset($addressBilling->LastName) ? trim($addressBilling->LastName) : '',
                'address_bill_phone'              => isset($addressBilling->Phone) ? trim($addressBilling->Phone) : '',
                'address_bill_phone2'             => isset($addressBilling->Phone2) ? trim($addressBilling->Phone2) : '',
                'address_bill_address1'           => isset($addressBilling->Address1) ? trim($addressBilling->Address1) : '',
                'address_bill_address2'           => isset($addressBilling->Address2) ? trim($addressBilling->Address2) : '',
                'address_bill_address3'           => isset($addressBilling->Address3) ? trim($addressBilling->Address3) : '',
                'address_bill_address4'           => isset($addressBilling->Address4) ? trim($addressBilling->Address4) : '',
                'address_bill_address5'           => isset($addressBilling->Address5) ? trim($addressBilling->Address5) : '',
                'address_bill_customer_email'     => isset($addressBilling->CustomerEmail) ? trim($addressBilling->CustomerEmail) : '',                
                'address_bill_ward'               => isset($addressBilling->Ward) ? trim($addressBilling->Ward) : '',
                'address_bill_region'             => isset($addressBilling->Region) ? trim($addressBilling->Region) : '',              
                'address_bill_city'               => isset($addressBilling->City) ? trim($addressBilling->City) : '',
                'address_bill_country'            => isset($addressBilling->Country) ? trim($addressBilling->Country) : '',
                'address_bill_postcode'           => isset($addressBilling->PostCode) ? trim($addressBilling->PostCode) : '',
                'address_shipping_first_name'     => isset($addressShipping->FirstName) ? trim($addressShipping->FirstName) : '',
                'address_shipping_last_name'      => isset($addressShipping->LastName) ? trim($addressShipping->LastName) : '',
                'address_shipping_phone'          => isset($addressShipping->Phone) ? trim($addressShipping->Phone) : '',
                'address_shipping_phone2'         => isset($addressShipping->Phone2) ? trim($addressShipping->Phone2) : '',
                'address_shipping_address1'       => isset($addressShipping->Address1) ? trim($addressShipping->Address1) : '',
                'address_shipping_address2'       => isset($addressShipping->Address2) ? trim($addressShipping->Address2) : '',
                'address_shipping_address3'       => isset($addressShipping->Address3) ? trim($addressShipping->Address3) : '',
                'address_shipping_address4'       => isset($addressShipping->Address4) ? trim($addressShipping->Address4) : '',
                'address_shipping_address5'       => isset($addressShipping->Address5) ? trim($addressShipping->Address5) : '',
                'address_shipping_customer_email' => isset($addressShipping->CustomerEmail) ? trim($addressShipping->CustomerEmail) : '',
                'address_shipping_ward'           => isset($addressShipping->Ward) ? trim($addressShipping->Ward) : '',
                'address_shipping_region'         => isset($addressShipping->Region) ? trim($addressShipping->Region) : '',                
                'address_shipping_city'           => isset($addressShipping->City) ? trim($addressShipping->City) : '',
                'address_shipping_country'        => isset($addressShipping->Country) ? trim($addressShipping->Country) : '',
                'address_shipping_postcode'       => isset($addressShipping->PostCode) ? trim($addressShipping->PostCode) : '',
                'national_registration_number'    => isset($order->NationalRegistrationNumber) ? trim($order->NationalRegistrationNumber) : '',
                'voucher_code'                    => isset($order->VoucherCode) ? trim($order->VoucherCode) : '',
                'promised_shipping_times'         => !isset($order->PromisedShippingTimes) || trim($order->PromisedShippingTimes) == '' ? '0000-00-00 00:00:00' : trim($order->PromisedShippingTimes),
                'items_count'                     => intval($order->ItemsCount),
                'order_status'                    => $orderStatus,
                'statuses'                        => json_encode($statuses),//状态列表
            );
            $row = $this->getOneByCondition('platform_order_id,order_status,is_to_oms',"platform_order_id='{$platformOrderID}'");
            if (!empty($row)) {
                if ($row['order_status'] != $data['order_status']) {
                    $data['is_downdetail'] = self::IS_DOWNDETAIL_NO;//是否已下载订单明细(1:是,0:否)
                }
                $data['updated_time'] = $nowTime;
                $this->dbConnection->createCommand()->update($this->tableName(),$data,"platform_order_id='{$row['platform_order_id']}'");
            } else {
                $data['from_code']       = $this->_FromCode;
                $data['subtotal_price']  = 0;//由明细更新 
                $data['shipping_amount'] = 0;//由明细更新 
                $data['tax_amount']      = 0;//由明细更新 
                $data['voucher_amount']  = 0;//由明细更新 
                $data['currency']        = '';//由明细更新                 
                $data['log_id']          = $this->_LogID;//第一次拉取时的日志id
                $data['is_downdetail']   = self::IS_DOWNDETAIL_NO;
                $data['created_time']    = $nowTime;
                $this->dbConnection->createCommand()->insert($this->tableName(),$data);
            }
            return true;
        } catch (Exception $e) {
            $this->setExceptionMessage($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 保存订单明细信息
     * @param  object $order
     * @return boolean
     */
    private function saveOrderDetailInfos($order) {
        $lazadaOrderDetail = new LazadaOrderDetail();
        $dbTransaction = $lazadaOrderDetail->dbConnection->beginTransaction();// 开启事务
        try {
            $accountID   = $this->_AccountID;
            $orderId     = trim($order->OrderId);
            $orderNumber = trim($order->OrderNumber);            
            if (empty($order->OrderItems->OrderItem) || $orderId == '' || $orderNumber == '') {
                throw new Exception("OrderItems data is Empty");
            }
            $platformOrderID = $this->_Prefix . $orderNumber . '-' . $orderId;
            //delete ALL
            $lazadaOrderDetail->dbConnection->createCommand()
                              ->delete($lazadaOrderDetail->tableName(),"platform_order_id='{$platformOrderID}' ");
            $subtotalPrice = 0;//产品总金额
            $shippingAmt   = 0;//总运费
            $taxAmt        = 0;//总税费
            $voucherAmt    = 0;//代金券
            foreach ($order->OrderItems->OrderItem as $item) {
                $orderItemId    = trim($item->OrderItemId);
                $currency       = trim($item->Currency);//币种
                $itemPrice      = floatval($item->ItemPrice);
                $paidPrice      = floatval($item->PaidPrice);
                $shippingAmount = floatval($item->ShippingAmount);
                $taxAmount      = floatval($item->TaxAmount);
                $voucherAmount  = floatval($item->VoucherAmount);
                $subtotalPrice  += $itemPrice;//产品总金额
                $shippingAmt    += $shippingAmount;//总运费
                $taxAmt         += $taxAmount;//总税费
                $voucherAmt     += $voucherAmount;//代金券
                $title          = trim($item->Name);  
                if ($title != '') {//mysql编码格式utf-8格式，不支持带四字节的字符串插入
                    $title = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $title);
                }
                //格式化数据
                $data = array(
                    'platform_order_id'       => $platformOrderID,
                    'seller_account_id'       => $accountID,
                    'order_id'                => $orderId,
                    'order_number'            => $orderNumber,
                    'order_item_id'           => $orderItemId,
                    'shop_id'                 => trim($item->ShopId),
                    'name'                    => $title,
                    'sku'                     => trim($item->Sku),
                    'shop_sku'                => trim($item->ShopSku),
                    'shipping_type'           => isset($item->ShippingType) ? trim($item->ShippingType) : '',
                    'item_price'              => $itemPrice,
                    'paid_price'              => $paidPrice,
                    'currency'                => trim($item->Currency),
                    'wallet_credits'          => isset($item->WalletCredits) ? floatval($item->WalletCredits) : 0,
                    'tax_amount'              => $taxAmount,
                    'tax_percent'             => isset($item->TaxPercent) ? floatval($item->TaxPercent) : 0,
                    'abatement_rate'          => isset($item->AbatementRate) ? floatval($item->AbatementRate) : 0,
                    'excise_rate'             => isset($item->ExciseRate) ? floatval($item->ExciseRate) : 0,
                    'hsn_code'                => isset($item->HsnCode) ? trim($item->HsnCode) : '',
                    'cod_collectable_amount'  => isset($item->CodCollectableAmount) ? floatval($item->CodCollectableAmount) : 0,
                    'shipping_amount'         => $shippingAmount,  
                    'shipping_service_cost'   => isset($item->ShippingServiceCost) ? floatval($item->ShippingServiceCost) : 0,
                    'voucher_amount'          => $voucherAmount,
                    'voucher_code'            => isset($item->VoucherCode) ? trim($item->VoucherCode) : '',
                    'status'                  => trim($item->Status),
                    'shipment_provider'       => isset($item->ShipmentProvider) ? trim($item->ShipmentProvider) : '',
                    'is_digital'              => intval($item->IsDigital),
                    'tracking_code'           => isset($item->TrackingCode) ? trim($item->TrackingCode) : '',
                    'tracking_code_pre'       => isset($item->TrackingCodePre) ? trim($item->TrackingCodePre) : '',
                    'reason'                  => isset($item->Reason) ? trim($item->Reason) : '',
                    'created_at'              => trim($item->CreatedAt),
                    'updated_at'              => isset($item->UpdatedAt) ? trim($item->UpdatedAt) : '0000-00-00 00:00:00',
                    'purchase_order_id'       => isset($item->PurchaseOrderId) ? trim($item->PurchaseOrderId) : '',
                    'purchase_order_number'   => isset($item->PurchaseOrderNumber) ? trim($item->PurchaseOrderNumber) : '',
                    'package_id'              => isset($item->PackageId) ? trim($item->PackageId) : '',
                    'promised_shipping_times' => !isset($item->PromisedShippingTime) || trim($item->PromisedShippingTime) =='' ? '0000-00-00 00:00:00' : trim($item->PromisedShippingTime),      
                    'shipping_provider_type'  => isset($item->ShippingProviderType) ? trim($item->ShippingProviderType) : '',
                );
                $data['created_time'] = date('Y-m-d H:i:s');
                $lazadaOrderDetail->dbConnection->createCommand()
                                  ->insert($lazadaOrderDetail->tableName(),$data);
            }
            //更新订单主表信息
            $this->dbConnection->createCommand()->update( $this->tableName(),
                array(
                    'is_downdetail'   => LazadaOrderMain::IS_DOWNDETAIL_YES,
                    'subtotal_price'  => $subtotalPrice,
                    'shipping_amount' => $shippingAmt,
                    'tax_amount'      => $taxAmt,
                    'voucher_amount'  => $voucherAmt,
                    'currency'        => $currency, 
                ),
                "platform_order_id='{$platformOrderID}' ");
            $dbTransaction->commit();
            return true;
        } catch (Exception $e) {
            $this->setExceptionMessage($e->getMessage());
            $dbTransaction->rollback();
            return false;
        }
    }

}