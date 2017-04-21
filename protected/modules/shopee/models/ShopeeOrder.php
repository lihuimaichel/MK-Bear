<?php
/**
 * @desc shopee订单拉取
 * @author yangsh
 * @since 2016-10-21
 */
class ShopeeOrder extends ShopeeModel {
    
    const EVENT_NAME       = 'getorder';
    const EVENT_NAME_CHECK = 'check_getorder';
    
    /** @var int 账号ID*/
    protected $_accountID = null;

    /** @var date 开店时间*/
    protected $_openTime = null;

    /** @var string 站点名称*/
    protected $_site = null;

    /** @var string 异常信息*/
    protected $exception = null;
    
    /** @var int 日志编号*/
    protected $_logID = 0;
    
    /** @var int 手续费比例*/
    protected $_amtfeeRate = 0;
    
    /** @var float 成交费*/
    protected $_finalValueFee = 0;

    /** @var int 成交费比例 2017/04/13 15:05*/
    public $_valuefeeRateNew = array(
        'id' => 0,      //'印尼站点'
        'sg' => 0,      //'新加坡'
        'th' => 0,      //'泰国站点'
        'my' => 0.03,   //'马来西亚站点'
        'tw' => 0.03,   //'台湾站点'
    );  

    protected $_orderStatus = null;

    protected $_timeArr = array();

    protected $_mode = 1;//1.createTime 2.updateTime

    /** @var string shopee订单状态*/   
    const ORDER_UNPAID             =  'UNPAID';//未付款订单
    const ORDER_READY_TO_SHIP      =  'READY_TO_SHIP';//已付款订单，可以安排发货
    const ORDER_SHIPPED            =  'SHIPPED';//已发货订单
    const ORDER_TO_CONFIRM_RECEIVE =  'TO_CONFIRM_RECEIVE';//已送达等待买家确认收货订单
    const ORDER_TO_RETURN          =  'TO_RETURN';//申请退货退款订单
    const ORDER_COMPLETED          =  'COMPLETED';//已完成订单
    const ORDER_CANCELLED          =  'CANCELLED';//被取消订单
    const ORDER_INVALID            =  'INVALID';//无效订单

    /** @var string shopee付款类型*/ 
    const PAY_COD                  = 'PAY_COD';

   /**
    * [model description]
    * @param  [type] $className [description]
    * @return [type]            [description]
    */
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
     * @param integer $accountID 账号ID
     */
    public function setAccountID($accountID){
        $accountInfo = ShopeeAccount::getAccountInfoById($accountID);
        if(empty($accountInfo)) {
            throw new Exception("AccountInfo Is Empty");
        }
        $this->_openTime = $accountInfo['open_time'];//开店时间,date类型
        $this->_site = strtolower($accountInfo['site']);        
        $this->_accountID = $accountID;
        return $this;
    } 

    /**
     * [setMode description]
     * @param [type] $mode [description]
     */
    public function setMode($mode) {
        $this->_mode = $mode;
        return $this;
    } 

    /**
     * @desc 设置订单状态
     * @param [type] $orderStatus [description]
     */
    public function setOrderStatus($orderStatus) {
        $this->_orderStatus = $orderStatus;
        return $this;
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
     * @desc 设置日志编号
     * @param int $logID
     */
    public function setLogID($logID){
        $this->_logID = $logID;
        return $this;
    }
    
    /**
     * @desc 设置拉单时间段 
     * 每半小时拉一次
     */
    public function setTimeArr($timeArr=array()){
        $eventName = ShopeeLog::EVENT_GETORDER;
        if (empty($timeArr)) {
            $lastLog = ShopeeLog::model()->getLastLogByCondition(array(
                'account_id'    => $this->_accountID,
                'event'         => $eventName,
                'status'        => ShopeeLog::STATUS_SUCCESS,
            ));
            $timeArr = array(
                'start_time'    => !empty($lastLog) ? date('Y-m-d H:i:s',strtotime($lastLog['end_time']) - 15*60) : date('Y-m-d H:i:s',time() - 3*86400),
                'end_time'      => date('Y-m-d H:i:s',time()),
            );
        }
        $this->_timeArr = $timeArr;
        return $this;
    }

    /**
     * @desc 获取拉单时间段
     */
    public function getTimeArr() {
        return $this->_timeArr;
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
    public function getOrders(){
        $path        = 'shopee/getOrders/'.date("Ymd").'/'.$this->_accountID.'/'.date("H");
        $accountID   = $this->_accountID;
        $site        = $this->_site;
        $logID       = $this->_logID;
        $startTime   = date('YmdHis', strtotime($this->_timeArr['start_time']));
        $endTime     = date('YmdHis', strtotime($this->_timeArr['end_time']));
        $request     = new GetOrdersListFromLocalRequest();
        if ($this->_mode == 1) {
            $request->setPlatformCreateTimeFrom($startTime);
            $request->setPlatformCreateTimeTo($endTime);
        } else {
            $request->setPlatformUpdateTimeFrom($startTime);
            $request->setPlatformUpdateTimeTo($endTime);
        }

        if ( $this->_orderStatus ) {
            $request->setOrderStatus($this->_orderStatus);
        }
        //抓取订单信息
        $errMsg = '';
        $page = 1;
        $count = 0;
        $getOrderNoFailureFlag = array();//获取订单号失败超过10次则退出程序
        while( $page <= ceil($request->pageCount/$request->pageSize) ){
            try {
                $request->setPageNum($page);
                $response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
                //MHelper::writefilelog($path.'/response_'.$page.'.txt', print_r($response,true)."\r\n");
                if (!$request->getIfSuccess()) {
                    throw new Exception($request->getErrorMsg(), 110);
                }
                if ( !isset($response->result) || !isset($response->result->total) ) {
                    throw new Exception("result is not exist", 112);
                }
                if ( intval($response->result->total) <= 0 || empty($response->result->list) ) {
                    break;
                }
                $request->pageCount = $response->result->total;
                $page++;
                foreach ($response->result->list as $order) {
                    try {
                        $orderObj = new Order();
                        $platformCode = Platform::CODE_SHOPEE;
                        $platformOrderID = trim($order->ordersn);

                        $orderId = AutoCode::getCodeNew('order'); // 获取订单号
                        if ( empty($orderId) ) {
                            throw new Exception("getCodeNew Error");
                            $getOrderNoFailureFlag[] = $platformOrderID;
                        } else {
                            $orderId = $orderId . 'XXP';
                        }  

                        if (!empty($getOrderNoFailureFlag) && count($getOrderNoFailureFlag)>10) {
                            $errLogMessage = '获取订单号失败:'.implode(',',$getOrderNoFailureFlag);
                            echo $errLogMessage."<br>";
                            $this->addShopeeLog($accountID,ShopeeLog::STATUS_FAILURE,$errLogMessage);
                            return false;
                        }
                        
                        $orderStatus     = trim($order->orderStatus);
                        $paymentMethod   = trim($order->paymentMethod);
                        $orderNote       = trim($order->messageToSeller);
                        $createAt        = self::getFormatTime($order->orderCreateTime);
                        
                        /*检查已取消订单是否已同步OMS，对已同步订单先取消未发货的包裹然后标识订单未出货已完成--start--*/
                        if( self::ORDER_CANCELLED == $orderStatus ) {
                            $omsOrderInfo = Order::model()->getOrderInfoByPlatformOrderID($platformOrderID, Platform::CODE_SHOPEE);
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

                        //按状态过滤
                        if (! in_array($orderStatus,array(
                                self::ORDER_READY_TO_SHIP, 
                                self::ORDER_SHIPPED,
                            )) ) {
                            echo $platformOrderID.'--'.$orderStatus."<br>";
                            continue;
                        }
                        
                        //按下单时间过滤
                        if ( $createAt < date('Y-m-d',strtotime('-30 days')) ) {
                            continue;
                        }

                        //1.1 判断OMS订单是否存在
                        $orderInfo = $orderObj->getOrderInfoByPlatformOrderID( $platformOrderID, $platformCode );
                        if( !empty($orderInfo) ){
                            echo $platformOrderID.'--'.'标记已存在！！！<br>';
                            continue;
                        }

                        //获取系统订单号
                        $orderId = !empty($orderInfo) ? $orderInfo['order_id'] : $orderId;
                        if (empty($orderId)) {
                            throw new Exception("order_id is empty");
                        }                        

                        //1.2 判断原始订单关键信息是否为空
                        list($formatOrderInfo, $partFormatOrderInfo) = $this->getFormatedOrderInfo($order);
                        if (!$formatOrderInfo) {
                            throw new Exception('formatOrderInfo error');
                        }

                        //1.3 判断原始订单详情关键信息是否为空
                        list($formatDetailInfos,$partFormatDetails,$orderDatas,$formatDetailsExts,$orderExtendInfo,$matchGiftInfos) = $this->getFormatedOrderDetailInfos($formatOrderInfo,$order);
                        if ( empty($formatDetailInfos) ) {
                            throw new Exception('formatDetailInfos error');
                        }

                        $formatOrderTransactionInfos = $this->getFormatOrderTransaction($orderId,$formatOrderInfo,$order);
                        if (empty($formatOrderTransactionInfos)) {
                            throw new Exception("formatOrderTransactionInfos error");
                        }
                        $formatPaypalTransactionInfos = $this->getFormatPaypalTransactionRecord($orderId,$formatOrderInfo,$order);
                         if (empty($formatPaypalTransactionInfos)) {
                            throw new Exception("formatPaypalTransactionInfos error");
                        }
                        //保存订单等数据，开启事务
                        $dbTransaction = $orderObj->dbConnection->beginTransaction();
                        try {
                            //init commonOrder
                            $commonOrder = new CommonOrder();

                            //保存订单主表信息
                            $isOk = $commonOrder->saveOrderInfo($platformCode,$orderId,$formatOrderInfo);
                            if (!$isOk) {
                                throw new Exception("saveOrderInfo Error " . $commonOrder->getExceptionMessage());
                            }

                            //保存订单备注
                            if ( $orderNote != '' ) {
                                $isOk = $commonOrder->saveOrderNoteInfo($orderId,$orderNote);
                                if (!$isOk) {
                                    throw new Exception("saveOrderNoteInfo Error " . $commonOrder->getExceptionMessage());
                                }
                            }

                            //保存订单扩展信息
                            $isOk = $commonOrder->saveOrderExtendInfo($platformCode,$orderId,$orderExtendInfo);
                            if (!$isOk) {
                                throw new Exception("saveOrderExtendInfo Error " . $commonOrder->getExceptionMessage());
                            }

                            //保存订单详情信息
                            $isOk = $commonOrder->saveOrderDetailInfo($platformCode,$orderId,$formatDetailInfos,$formatDetailsExts);
                            if (!$isOk) {
                                throw new Exception("saveOrderDetailInfo Error " . $commonOrder->getExceptionMessage());
                            }

                            //匹配插头
                            $isOk = $commonOrder->addOrderAdapter($platformCode,$orderId,$formatOrderInfo,$formatDetailInfos);
                            if (!$isOk) {
                                throw new Exception("addOrderAdapter Error " . $commonOrder->getExceptionMessage());
                            }

                            //匹配礼品
                            $shopeeOrder = new self();
                            $isOk = $shopeeOrder->addGift($orderId,$platformOrderID,$matchGiftInfos);
                            if (!$isOk) {
                                throw new Exception("addGift Error " . $shopeeOrder->getExceptionMessage());
                            }

                            //保存订单sku异常信息
                            $orderSkuExceptionMsg = $orderDatas['orderSkuExceptionMsg'];
                            if ($orderSkuExceptionMsg != '') {
                                $commonOrder->setExceptionOrder($orderId, OrderExceptionCheck::EXCEPTION_SKU_UNKNOWN, $orderSkuExceptionMsg);
                            }

                            //保存订单交易信息  
                            $isOk = $commonOrder->saveOrderTransactionInfo($platformCode,$orderId,$formatOrderTransactionInfos);
                            if (!$isOk) {
                                throw new Exception("saveOrderTransactionInfo Error " . $commonOrder->getExceptionMessage());
                            }

                            //保存付款信息
                            $isOk = $commonOrder->savePaypalTransactionInfo($orderId,$formatPaypalTransactionInfos);
                            if (!$isOk) {
                                throw new Exception("savePaypalTransactionInfo Error " . $commonOrder->getExceptionMessage());
                            }
                            
                            //保存订单sku与销售关系
                            $isOk = $commonOrder->saveOrderSkuOwnerInfo($platformCode,$orderId,$partFormatOrderInfo,$partFormatDetails);
                            if (!$isOk) {
                                throw new Exception("saveOrderSkuOwnerInfo Error " . $commonOrder->getExceptionMessage());
                            }               
                            $count++;    
                            $dbTransaction->commit();
                            if (!empty($_REQUEST['show_result'])) {
                                echo $platformOrderID . ' ### 同步成功！<br>';
                            }
                        } catch (Exception $e) {
                            $dbTransaction->rollback();
                            if (!empty($_REQUEST['show_result'])) {
                                echo $platformOrderID . '@@@'.$e->getMessage()."<br>";
                            }
                            $errMsg .= $platformOrderID . '@@@'.$e->getMessage()."<br>";
                        }
                    } catch (Exception $e) {
                        $errMsg .= $platformOrderID . ' ## ' . $e->getMessage()."<br>";
                        continue;
                    }
                }
            } catch (Exception $e) {
                $errMsg .= 'page: '.$page.' @@@ '. $e->getMessage()."<br><br>";
                break;
            }
        }
        $this->setExceptionMessage($errMsg);
        return $errMsg == '' ? true : false;
    }

    /**
     * @desc 格式化订单数据
     */
    public function getFormatedOrderInfo($order) {
        //1.格式化前数据验证      
        $accountID        = $this->_accountID;
        $site             = $this->_site;
        $logID            = $this->_logID;   
        $platformOrderID  = trim($order->ordersn);
        $orderStatus      = trim($order->orderStatus);
        $recipientAddress = isset($order->recipientAddress) ? $order->recipientAddress : null;
        $shipCountry      = $shipCountryName = $buyerId = '';
        $shipName         = $postCode = $phone = '';
        $street1          = $street2 = $state = $city ='';
        if ($recipientAddress) {
            $shipCountry     = trim($recipientAddress->country);
            $shipCountry == '' && $shipCountry = trim($order->country);
            $shipCountryName = Country::model ()->getEnNameByAbbr ( $shipCountry );
            $shipName        = trim($recipientAddress->name);
            $phone           = trim($recipientAddress->phone);
            $city            = trim($recipientAddress->city);
            $state           = trim($recipientAddress->state);
            $postCode        = trim($recipientAddress->zipcode);
            $street1         = trim($recipientAddress->fullAddress);
            if ($state != '' && strrpos($street1, ',') !== false && ($pos = strrpos($street1, $state) ) >0 ) {
                $street1     = trim(substr($street1, 0, $pos));
            }
            if ($postCode != '' && strrpos($street1, ',') !== false && ($pos = strrpos($street1, $postCode) ) >0 ) {
                $street1     = trim(substr($street1, 0, $pos));
            }
            if ($city != '' && strrpos($street1, ',') !== false && ($pos = strrpos($street1, $city) ) >0 ) {
                $street1     = trim(substr($street1, 0, $pos));
                
            }
            if ($shipCountry != '' && strrpos($street1, ',') !== false && ($pos = strrpos($street1, $shipCountry) ) >0 ) {
                $street1     = trim(substr($street1, 0, $pos));
            }
            $street1         = trim($street1,',');
        }
        $payMethod           = isset($order->paymentMethod) ? trim($order->paymentMethod) : '';
        $currency            = isset($order->currency) ? trim($order->currency) : '';
        //验证数据是否为空   
        // echo $platformOrderID.'--'.$accountID.' sssss.....................<br>';
        //MHelper::printvar($order,false); 
        if ($platformOrderID == '' || $shipName == '' || $currency == '' || $payMethod == '') {
            //echo $platformOrderID.' order.........<br>';
            return false;
        }

        //获取放款信息
        $request = new GetEscrowDetailsRequest();
        $request->setOrdersn($platformOrderID);
        $escrowDetails = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
        //MHelper::printvar($escrowDetails,false);        
        if (!$request->getIfSuccess() || !isset($escrowDetails->order->income_details)
         || trim($escrowDetails->order->ordersn) != $platformOrderID || trim($escrowDetails->order->income_details->local_currency) != $currency ) {
            //echo $platformOrderID.'--'.$accountID.' esc.....................<br>';
            return false;
        }   

        $income_details = $escrowDetails->order->income_details;
        $ori_create_time = self::getFormatTime($order->orderCreateTime);//返回格式：2017-03-17T23:19:01Z
        $ori_update_time = self::getFormatTime($order->orderUpdateTime); 
        $ori_pay_time    = $ori_update_time;      
        if( in_array(strtolower($site),array('id','vn')) ){
            //越南站和印尼站东7区utc+7
            $createAt = date('Y-m-d H:i:s', strtotime($ori_create_time) - 3600 );
            $updateAt = date('Y-m-d H:i:s', strtotime($ori_update_time) - 3600);
        } else {
            //其他站东八区utc+8
            $createAt = $ori_create_time;
            $updateAt = $ori_update_time;
        }

        $escrowAmount           = floatval($order->escrowAmount);//拨款金额
        $totalAmount            = floatval($order->totalAmount);//买家实际付款金额
        $estimatedShippingFee   = floatval($order->estimatedShippingFee);//买家实际支付的运费

        //平台返给的运费
        $shipping_fee_rebate = 0;
        if ( isset($income_details->shipping_fee_rebate) ) {
            $shipping_fee_rebate = floatval($income_details->shipping_fee_rebate);
        }

        //总运费
        $orderShipFee = $estimatedShippingFee + $shipping_fee_rebate;

        //产品总金额
        $totalProductAmt = 0;
        if (!empty($order->items)) {
            foreach ($order->items as $orderDetail) {
                $totalProductAmt += floatval($orderDetail->variationDiscountedPrice) * $orderDetail->variationQuantityPurchased;
            } 
        }

        //平台返给的补贴
        $seller_rebate = 0;
        if (isset($income_details->seller_rebate)) {
            $seller_rebate = floatval($income_details->seller_rebate);
        }

        //成交费,新账号前3个月不收佣金
        $isNewAccount = $this->_openTime != ''
             && date('Y-m-d',strtotime("-3 month")) >= $this->_openTime ? false : true;
        $finalValueFee = (float)$income_details->commission_fee;
        if($finalValueFee == 0 && !$isNewAccount && isset($this->_valuefeeRateNew[$site]) && $this->_valuefeeRateNew[$site]>0 ) {
            $finalValueFee = $totalProductAmt * $this->_valuefeeRateNew[$site];
            $finalValueFee = 'tw' == $site ? round($finalValueFee) : round($finalValueFee,2); 
        }
        $this->_finalValueFee = $finalValueFee;

        //订单总金额
        $orderTotal = $totalProductAmt + $orderShipFee;//产品总金额 + 总运费 

        //2.组装OMS需要的数据格式
        $formatOrderInfo = array(
            'platform_order_id'    => $platformOrderID,
            'account_id'           => $accountID,
            'log_id'               => $logID,
            'order_status'         => $orderStatus,
            'buyer_id'             => '',
            'email'                => '',
            'timestamp'            => date ( 'Y-m-d H:i:s' ),
            'created_time'         => $createAt,
            'last_update_time'     => $updateAt,
            'paytime'              => $updateAt,
            'ship_cost'            => $orderShipFee,//运费
            'subtotal_price'       => round($totalProductAmt,2),//产品总金额
            'total_price'          => $orderTotal,//订单交易金额
            'final_value_fee'      => $finalValueFee,//成交费
            'insurance_amount'     => 0,//运费险,无
            'currency'             => $currency,
            'ship_name'            => $shipName,
            'ship_country'         => $shipCountry,
            'ship_country_name'    => $shipCountryName,
            'ship_zip'             => $postCode,
            'ship_city_name'       => $city,
            'ship_stateorprovince' => $state,
            'ship_street1'         => $street1,
            'ship_street2'         => $street2,
            'ship_phone'           => $phone,
            'ori_create_time'      => $ori_create_time,
            'ori_update_time'      => $ori_update_time,
            'ori_pay_time'         => $ori_pay_time,
            'payment_status'       => Order::PAYMENT_STATUS_END,
        );

        //订单标识待处理
        $orderNote = isset($order->messageToSeller) ? trim($order->messageToSeller) : '';
        if ($orderNote != ''){
            $formatOrderInfo['complete_status'] = Order::COMPLETE_STATUS_PENGDING;
        }

        //组装订单sku与销售关系数据
        $partFormatOrderInfo = array(
            'platform_order_id'    => $platformOrderID,
            'account_id'           => $accountID,
        );
        return array($formatOrderInfo, $partFormatOrderInfo);
    }

    /**
     * @desc 格式化订单明细数据
     */
    public function getFormatedOrderDetailInfos($formatOrderInfo,$order) {
        //验证订单明细数据是否完整
        $accountID         = $this->_accountID;
        $site              = $this->_site;        
        if (empty($order->items)) {
            return false;
        }
        $nowTime           = date("Y-m-d H:i:s");
        $formatDetails     = array();
        $partFormatDetails = array();
        $matchGiftInfos    = array();

        $platformOrderID   = $formatOrderInfo['platform_order_id'];
        $currency          = $formatOrderInfo['currency'];
        $orderNote         = isset($order->messageToSeller) ? trim($order->messageToSeller) : '';

        $totalMoney        = $formatOrderInfo['total_price'];//订单总金额=实际交易金额+运费补贴
        $totalShipFee      = $formatOrderInfo['ship_cost'];//订单运费
        $totalProductAmt   = $formatOrderInfo['subtotal_price'];//产品总金额
        $subTotalPrice     = $totalProductAmt;
        $totalFvf          = $formatOrderInfo['final_value_fee'];//成交费
        $totalDiscount     = 0;//优惠金额 
        $totalFeeAmt       = 0;//手续费

        $listCount         = count($order->items); 
        $index             = 1;
        $tmpshipFee        = $tmpDiscount = $tmpFvf = 0;
        $tmpFeeAmt         = $tmpItemSalePriceAllot = 0;
        $orderSkuExceptionMsg = '';//记录订单sku异常信息;
        foreach ($order->items as $orderDetail) {
            //1.格式化前数据验证
            $title              = trim($orderDetail->itemName) != '' ? trim($orderDetail->itemName) : '';
            $title              .= trim($orderDetail->variationName) == ''?'':'['.trim($orderDetail->variationName).']';          
            $title              = mb_strlen($title) > 100 ? mb_substr($title,0,100) : $title;
            $skuOnline          = trim($orderDetail->variationSkuOnline);
            $skuOnline          = $skuOnline == '' && trim($orderDetail->itemSkuOnline) != '' ? trim($orderDetail->itemSkuOnline) : $skuOnline;
            if ($skuOnline == '') {
                $skuOnline = 'unknown';
            }
            $itemId             = implode('-',array($site,$accountID,$skuOnline));
            $price              = floatval ( $orderDetail->variationDiscountedPrice );
            $quantity           = (int)$orderDetail->variationQuantityPurchased;
            if ($skuOnline != 'unknown' ) {
                $sku            = encryptSku::getRealSku ( $skuOnline );
                $skuInfo        = Product::model ()->getProductInfoBySku ( $sku );
            } else {
                $sku            = 'unknown';
                $skuInfo        = array();
            }

            $skuInfo2 = array();//发货sku信息
            $pending_status     = OrderDetail::PEDNDING_STATUS_ABLE;
            if (! empty ( $skuInfo )) { // 可以查到对应产品
                $realProduct    = Product::model ()->getRealSkuListNew ( $sku, $quantity,  $skuInfo );
                if ($skuInfo['sku'] == $realProduct['sku']) {
                    $skuInfo2   = $skuInfo;
                } else {
                    $skuInfo2   = Product::model()->getProductInfoBySku( $realProduct['sku'] );
                }                
            } 

            if(empty($skuInfo) || empty($skuInfo2)) {
                $realProduct = array (
                    'sku'       => 'unknown',
                    'quantity'  => $quantity
                );
                $pending_status = OrderDetail::PEDNDING_STATUS_KF;
                $orderSkuExceptionMsg .= "sku信息不存在;";
            }
            
            if($skuInfo2 && $skuInfo2['product_is_multi'] == Product::PRODUCT_MULTIPLE_MAIN){
                $childSku = ProductSelectAttribute::model()->getChildSKUListByProductID($skuInfo2['id']);
                if (!empty($childSku)) {//$sku为主sku
                    $pending_status = OrderDetail::PEDNDING_STATUS_KF;
                    $orderSkuExceptionMsg .= "{$skuInfo2['sku']}为主sku;";
                }
            }

            //订单备注不为空标识客服待处理
            if ($orderNote != '') {
                $pending_status = OrderDetail::PEDNDING_STATUS_KF;
            }

            //平摊费用
            $unitSalePrice = $price;
            $productAmt    = $price * $quantity;//产品金额
            $itemSalePrice = $productAmt;
            if ($index == $listCount) {
                $shipFee            = round($totalShipFee - $tmpshipFee,2);
                $discount           = round($totalDiscount - $tmpDiscount,2);//平摊后的优惠金额
                $fvfAmt             = round($totalFvf - $tmpFvf,2);//平摊后的成交费
                $feeAmt             = round($totalFeeAmt - $tmpFeeAmt,2);//平摊后的手续费
                $itemSalePriceAllot = round($subTotalPrice - $totalDiscount - $tmpItemSalePriceAllot, 2);//平摊后的item售价
                $unitSalePriceAllot = round($itemSalePriceAllot/$quantity, 3);//平摊后的单价 
            } else {
                $feeRate            = $itemSalePrice/$subTotalPrice;
                $shipFee            = round($feeRate * $totalShipFee,2);//平摊运费 
                $discount           = round($feeRate * $totalDiscount,2);//平摊后的优惠金额
                $fvfAmt             = round($feeRate * $totalFvf,2);//平摊后的成交费
                $feeAmt             = round($feeRate * $totalFeeAmt,2);//平摊后的手续费
                $itemSalePriceAllot = round($itemSalePrice - $discount, 2);//平摊后的item售价
                $unitSalePriceAllot = round($itemSalePriceAllot/$quantity, 3);//平摊后的单价

                $tmpshipFee            += $shipFee;
                $tmpDiscount           += $discount;
                $tmpFvf                += $fvfAmt;
                $tmpFeeAmt             += $feeAmt;
                $tmpItemSalePriceAllot += $itemSalePriceAllot;                
            }
            $index++;

            //2.组装OMS需要的数据格式  
            $detailData = array(
                'transaction_id'    => $platformOrderID,
                'item_id'           => $itemId,
                'title'             => $title,
                'sku_old'           => $sku,//系统sku
                'sku'               => $realProduct['sku'],//实际sku
                'site'              => $site,
                'quantity_old'      => $quantity,//购买数量
                'quantity'          => $realProduct['quantity'],//发货数量
                'sale_price'        => $price,//销售单价
                'total_price'       => round($itemSalePrice + $shipFee,2),//产品金额+平摊后的运费
                'ship_price'        => $shipFee,//平摊后的运费
                'final_value_fee'   => $fvfAmt,//平摊后的成交费
                'currency'          => $currency,
                'pending_status'    => $pending_status,
                'create_time'       => date("Y-m-d H:i:s")
            );
            $formatDetails[] = $detailData;

            //组装订单明细扩展表数据
            $detailExtData = array(
                'item_sale_price'        => $itemSalePrice,//产品金额(含成交费)
                'item_sale_price_allot'  => $itemSalePriceAllot,//平摊后的产品金额(含成交费，减优惠金额)
                'unit_sale_price_allot'  => $unitSalePriceAllot,//平摊后的单价(原销售单价-平摊后的优惠金额)
                'coupon_price_allot'     => $discount,//平摊后的优惠金额
                'tax_fee_allot'          => 0,//平摊后的税费,无
                'insurance_amount_allot' => 0,//平摊后的运费险,无
                'fee_amt_allot'          => $feeAmt,//平摊后的手续费
            );
            $formatDetailsExts[] = $detailExtData;   

            //组装订单sku与销售数据
            $orderSkuData = array(
                'sku_online'        => $skuOnline,//在线sku,
                'sku_old'           => $sku,//系统sku
                'site'              => $site,
                'item_id'           => $itemId,
                'freight_price'     => $shipFee,
                'copoun_price'      => 0,
            );
            $partFormatDetails[] = $orderSkuData;

            //组装匹配礼品数据
            $giftInfos = array(
                'transaction_id' => $platformOrderID,
                'item_id'        => $itemId,
                'account_id'     => $accountID,
                'sku'            => $skuOnline,//在线sku
                'quantity'       => $quantity,
                'currency'       => $currency,
            );
            $matchGiftInfos[] = $giftInfos;
        }

        //返回订单处理
        $orderDatas = array(
            'orderSkuExceptionMsg'  => $orderSkuExceptionMsg,//订单sku异常信息
        );      

        //组装订单扩展表数据
        $orderExtendInfo = array(
            'platform_order_id'    => $platformOrderID,
            'account_id'           => $formatOrderInfo['account_id'],
            'tax_fee'              => 0,//税费,无
            'coupon_price'         => $discount,//优惠金额
            'currency'             => $formatOrderInfo['currency'],
            'payment_type'         => trim($order->paymentMethod),
            'logistics_type'       => trim($order->shippingCarrier),
        );   

        return array($formatDetails,$partFormatDetails,$orderDatas,
            $formatDetailsExts,$orderExtendInfo,$matchGiftInfos);
    }

    /**
     * @desc 格式化订单交易数据
     */
    public function getFormatOrderTransaction($orderId,$formatOrderInfo,$order) {
        if ($formatOrderInfo['total_price'] <= 0 || $formatOrderInfo['account_id'] == ''
             || $formatOrderInfo['currency'] == '') {
            return false;
        }
        $paymentType = self::PAY_COD == trim($order->paymentMethod) ? 'COD' : trim($order->paymentMethod);
        if($paymentType == 'COD'){
            //@todo 后期同步订单需要将k3_cloud_status、sync_cloud_error重置为0
            $paymentStatus = "Pending";
            $k3CloudStatus = 2;
            $syncCloudError = 6;
        }else{
            $paymentStatus = "Completed";
            $k3CloudStatus = 0;
            $syncCloudError = 0;
        }
        $rtn = array(
            'transaction_id'            => $orderId,
            'parent_transaction_id'     => '',
            'order_id'                  => $orderId,
            'status'                    => 0,
            'order_pay_time'            => $formatOrderInfo['ori_pay_time'],
            'last_update_time'          => $formatOrderInfo['ori_update_time'],
            'fee_amt'                   => round($formatOrderInfo['total_price']*$this->_amtfeeRate,2),
            'amt'                       => $formatOrderInfo['total_price'],
            'account_id'                => $formatOrderInfo['account_id'],
            'platform_code'             => Platform::CODE_SHOPEE,
            'currency'                  => $formatOrderInfo['currency'],
            'payment_status'            => $paymentStatus,
            'receive_type'              => OrderTransaction::RECEIVE_TYPE_YES,
            'first'                     => 1,
            'is_first_transaction'      => 1,//是否为第一次交易1:是,0:否
            'is_entry'                  => 0,//是否手工录入 1:手工录入；0：系统导入
            'modify_time'               => '0000-00-00 00:00:00',
            'k3_cloud_status'           => $k3CloudStatus,
            'sync_cloud_error'          => $syncCloudError
        );
        return array($rtn);
    }

    /**
     * @desc 格式化paypal交易数据
     */
    public function getFormatPaypalTransactionRecord($orderId,$formatOrderInfo,$order) {
        if ($formatOrderInfo['total_price'] <= 0 || $formatOrderInfo['account_id'] == '' || $formatOrderInfo['ship_name'] == '') {
            return false;
        }
        $paymentType = self::PAY_COD == trim($order->paymentMethod) ? 'COD' : trim($order->paymentMethod);
        if($paymentType == 'COD'){
            //@todo 后期同步订单需要将k3_cloud_status、sync_cloud_error重置为0
            $paymentStatus = "Pending";
        }else{
            $paymentStatus = "Completed";
        }        
        $rtn = array(
                'transaction_id'            => $orderId,
                'order_id'                  => $orderId,
                'receive_type'              => OrderTransaction::RECEIVE_TYPE_YES,
                'receiver_business'         => '',
                'receiver_email'            => 'unknown@vakind.com',
                'receiver_id'               => '',
                'payer_id'                  => '',
                'payer_name'                => trim($formatOrderInfo['ship_name']),
                'payer_email'               => '',
                'payer_status'              => '',
                'parent_transaction_id'     => '',
                'transaction_type'          => '',
                'payment_type'              => $paymentType,
                'order_time'                => $formatOrderInfo['ori_create_time'],
                'amt'                       => $formatOrderInfo['total_price'],
                'fee_amt'                   => round($formatOrderInfo['total_price']*$this->_amtfeeRate,2),
                'tax_amt'                   => 0,
                'currency'                  => $formatOrderInfo['currency'],
                'payment_status'            => $paymentStatus,
                'note'                      => '',
                'modify_time'               => '0000-00-00 00:00:00'
        );
        return array($rtn);
    }

    /**
     * @desc 时间戳转标准时间格式
     * @param  string $time
     * @return string
     */
    public static function getFormatTime($time) {
        return date('Y-m-d H:i:s',strtotime($time));
    }

    /**
     * @desc 批量更新COD订单交易状态
     * @param  integer $day 
     * @param  integer  $mode 
     * @return int
     */
    public function updateCODTransactionStatus($day,$mode) {
        $orderCount = 0;
        $accountList = ShopeeAccount::model()->getAbleAccountList();
        foreach ($accountList as $accountInfo) {
            $accountID = $accountInfo['id'];
            list($platformOrderIDs,$updateTimes) = $this->getNeedUpdateRecords($accountID,$day,$mode);
            echo 'account: '.$accountID.'<br>';
            MHelper::printvar($platformOrderIDs,false);
            if ($platformOrderIDs) {
                $sucPlatformOrderIDs = CommonOrder::model()->updateOmsTransactionStatus(Platform::CODE_SHOPEE, $platformOrderIDs,$updateTimes);               
                if ($sucPlatformOrderIDs) {
                    $orderCount += count($sucPlatformOrderIDs);
                }
            }
        }
        return $orderCount;
    }

    /**
     * @desc 更新订单费用扩展表
     * @param  int $day 
     * @param  int $mode
     * @return int     
     */
    public function updateOrderFeesExt($day,$mode) {
        $orderCount   = 0;
        $platformCode = Platform::CODE_SHOPEE;
        $startTime    = date('YmdHis', strtotime("-{$day} days") );
        $endTime      = date('YmdHis');        
        $accountList  = ShopeeAccount::model()->getAbleAccountList();
        foreach ($accountList as $accountInfo) {
            $accountID = $accountInfo['id'];
            $request = new GetOrdersListFromLocalRequest();
            $request->setOrderStatus(self::ORDER_SHIPPED);
            if ($mode == 1) {
                $request->setPlatformCreateTimeFrom($startTime);
                $request->setPlatformCreateTimeTo($endTime);
            } else {
                $request->setPlatformUpdateTimeFrom($startTime);
                $request->setPlatformUpdateTimeTo($endTime);
            }
            //抓取订单信息
            $errMsg = '';
            $page = 1;
            $count = 0;
            while( $page <= ceil($request->pageCount/$request->pageSize) ){
                $request->setPageNum($page);
                $response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
                //MHelper::writefilelog($path.'/response2_'.$page.'.txt', print_r($response,true)."\r\n");
                if (!$request->getIfSuccess()) {
                    echo $accountID.' ## '.$request->getErrorMsg()."<br>";
                    break;
                }
                if ( !isset($response->result) || !isset($response->result->total) ) {
                    echo $accountID. " ## result is not exist<br>";
                    break;
                }
                if ( intval($response->result->total) <= 0 || empty($response->result->list) ) {
                    break;
                }
                $request->pageCount = $response->result->total;
                $page++;
                foreach ($response->result->list as $order) {
                    $isOk = $this->saveOrderFeesExtData($accountID,$order);
                    if($isOk) {
                        $orderCount++;
                    } 
                }
            }
        }
        return $orderCount;        
    }

    protected function saveOrderFeesExtData($accountID,$order,$escrowDetails=null) {
        $platformCode         = Platform::CODE_SHOPEE;
        $nowTime              =  date('Y-m-d H:i:s'); 
        $platformOrderID      = trim($order->ordersn);
        $currency             = isset($order->currency) ? trim($order->currency) : '';
        $orderStatus          = trim($order->orderStatus);
        $estimatedShippingFee = floatval($order->estimatedShippingFee);//买家实付运费
        if (! in_array($orderStatus, array(
                self::ORDER_SHIPPED,//已经发货
                //self::ORDER_TO_CONFIRM_RECEIVE,//待买家确认收货
                //self::ORDER_COMPLETED,//已完成
            )) ) {
            //echo 'orderStatus:'.$orderStatus."<br>";
            return false;              
        }

        //查看订单是否已同步
        $omsOrderInfo = Order::model()->getOrderInfoByPlatformOrderID( $platformOrderID, $platformCode );
        if( empty($omsOrderInfo) ){
            //MHelper::printvar($omsOrderInfo,false);
            return false;
        }

        if(empty($escrowDetails)) {
            //获取平台回馈的运费
            $response2 = self::getEscrowDetails($accountID,$platformOrderID);
            if(!$response2['flag']) {
                sleep(3);
                //echo '<pre>platformOrderID:',$platformOrderID;print_r($response2['response']);
                return false;
            }
            $escrowDetails = $response2['response'];
        }
        //MHelper::printvar($escrowDetails,false);        
        if (!isset($escrowDetails->order->income_details)
         || trim($escrowDetails->order->ordersn) != $platformOrderID
          || trim($escrowDetails->order->income_details->local_currency) != $currency ) {
            echo $platformOrderID.'--'.$accountID.' esc.....................<br>';
            sleep(3);
            return false;
        }
        $income_details = $escrowDetails->order->income_details;
        //平台返给的运费
        $shippingFeeRebate = 0;
        if ( isset($income_details->shipping_fee_rebate) ) {
            $shippingFeeRebate = floatval($income_details->shipping_fee_rebate);
        }  
        //封装数据
        $orderFeesExtData = array(
            'order_id'            => $omsOrderInfo['order_id'],
            'account_id'          => $accountID,
            'platform_order_id'   => $platformOrderID,
            'platform_code'       => $platformCode,
            'order_status'        => $orderStatus,
            'shipping_fee'        => $estimatedShippingFee,
            'shipping_fee_rebate' => $shippingFeeRebate,
            'currency'            => $currency,
            'create_time'         => $nowTime,
            'update_time'         => $nowTime,
        );
        $feesExtInfo = OrderFeesExt::model()->getOneByCondition('order_id,order_status',"order_id='{$omsOrderInfo['order_id']}'");
        if($feesExtInfo) {
            if($feesExtInfo['order_status'] == $orderStatus ) {
                return true;
            }
            unset($orderFeesExtData['create_time']);
            $isOk = OrderFeesExt::model()->updateData($orderFeesExtData,"order_id='{$omsOrderInfo['order_id']}'");
        } else {
            $isOk = OrderFeesExt::model()->insertData($orderFeesExtData);
        }
        return $isOk;        
    }

    /**
     * @desc 获取妥投或完成的平台订单号
     * @param  int $accountID 
     * @param  int $day  
     * @param  int $mode 
     * @return array
     */
    public function getNeedUpdateRecords($accountID,$day,$mode) {
        $rtn = array();
        $updateAtArr = array();
        $request = new GetOrdersListFromLocalRequest();
        //确保订单先同步到OMS后更新交易信息
        $startTime = date('YmdHis', strtotime("-{$day} days") - 3600 );
        $endTime = date('YmdHis');
        if ($mode == 1) {
            $request->setPlatformCreateTimeFrom($startTime);
            $request->setPlatformCreateTimeTo($endTime);
        } else {
            $request->setPlatformUpdateTimeFrom($startTime);
            $request->setPlatformUpdateTimeTo($endTime);
        }

        //抓取订单信息
        $errMsg = '';
        $page = 1;
        $count = 0;
        while( $page <= ceil($request->pageCount/$request->pageSize) ){
            $request->setPageNum($page);
            $response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
            //MHelper::writefilelog($path.'/response2_'.$page.'.txt', print_r($response,true)."\r\n");
            if (!$request->getIfSuccess()) {
                echo $accountID.' ## '.$request->getErrorMsg()."<br>";
                break;
            }
            if ( !isset($response->result) || !isset($response->result->total) ) {
                echo $accountID. " ## result is not exist<br>";
                break;
            }
            if ( intval($response->result->total) <= 0 || empty($response->result->list) ) {
                break;
            }
            $request->pageCount = $response->result->total;
            $page++;
            foreach ($response->result->list as $order) {   
                $platformOrderID = trim($order->ordersn);
                $orderStatus     = trim($order->orderStatus);
                $paymentMethod   = trim($order->paymentMethod);
                $updateAt        = self::getFormatTime($order->orderUpdateTime);
                //订单妥投或完成，表示买家已付款
                if ( self::PAY_COD == trim($paymentMethod)
                     && in_array($orderStatus, array(
                        self::ORDER_TO_CONFIRM_RECEIVE,//待买家确认收货
                        self::ORDER_COMPLETED,//已完成
                    )) ) {
                    $rtn[] = $platformOrderID;
                    $updateAtArr[$platformOrderID] = $updateAt;
                }
            }
        }
        return array($rtn,$updateAtArr);
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
                $orderdetail        = new OrderDetail();
                $transactionId      = $detail['transaction_id'];
                $itemId             = $detail['item_id'];
                $accountID          = $detail['account_id'];
                $currency           = $detail['currency'];
                $sku                = $detail['sku'];//在线sku
                $quantity           = $detail['quantity'];
                //查找sku对应的赠品
                $giftSkuInfo = ShopeeGiftManage::model()->getOneByCondition('gift_sku',"account_id={$accountID} and sku='{$sku}' and is_delete=0");
                if (!empty($giftSkuInfo)) {
                    $giftSku = trim($giftSkuInfo['gift_sku']);
                    $info = $orderdetail->getDbConnection()->createCommand ()
                                    ->select ( 'id,quantity' )
                                    ->from ( $orderdetail->tableName() )
                                    ->where ( "item_id ='{$itemId}' and order_id='{$orderID}' and sku='{$giftSku}' " )
                                    ->queryRow ();
                    if (empty($info)) {
                        $data = array(
                                'transaction_id'    => $platformOrderID,
                                'order_id'          => $orderID,
                                'platform_code'     => Platform::CODE_SHOPEE,
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
                        $orderdetail->getDbConnection()->createCommand()->insert($orderdetail->tableName(), $data);
                    }
                }
            }
            return true;
        } catch (Exception $e) {
            $this->setExceptionMessage($e->getMessage());
            return false;
        }
    }    

    /**
     * @desc 获取订单信息
     */
    public static function getOrderDetails($accountID,$ordersnArr) {
        $request = new GetOrderDetailsRequest();
        $request->setOrdersnList($ordersnArr);
        $response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
        return array(
            'flag' => $request->getIfSuccess(),
            'response' => $response,
        );        
    }    

    /**
     * @desc 获取放款信息
     */
    public static function getEscrowDetails($accountID,$ordersn) {
        $request = new GetEscrowDetailsRequest();
        $request->setOrdersn($ordersn);
        $response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
        return array(
            'flag' => $request->getIfSuccess(),
            'response' => $response,
        );                
    }

    /**
     * [addShopeeLog description]
     * @param [type] $accountID [description]
     * @param [type] $status    [description]
     * @param [type] $message   [description]
     * @param [type] $eventName [description]
     */
    public function addShopeeLog($accountID,$status,$message,$eventName=ShopeeLog::EVENT_GETORDER) {
        $logModel = new ShopeeLog();
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

    /**
     * 根据包裹号，站点，渠道需要获取平台订单号
     * @param	string	$site	站点
     * @since 2017-2-28
     * @author cxy
     */
    public function getMultipleOrderItems($site='tw',$shipCode, $packageID){
    	$startTime = date('Y-m-d H:i:s', strtotime('-30 days'));
    	$obj = $this->dbConnection->createCommand()
    	->select('op.package_id as package_id,op.ship_code,o.platform_order_id,o.account_id,o.order_id')
    	->from(OrderPackage::model()->tableName().' AS op')
    	->leftJoin(OrderPackageDetail::model()->tableName().' AS d', 'op.package_id = d.package_id')
    	->leftJoin(OrderDetail::model()->tableName().' AS od', 'od.id = d.order_detail_id')
    	->leftJoin(Order::model()->tableName().' AS o', 'o.order_id = d.order_id')
    	->where('op.platform_code = "'.Platform::CODE_SHOPEE.'"')
    	
    	->andWhere(array('in','op.ship_code',$shipCode))
    	->andWhere('op.track_num = ""')
    	->andWhere('op.upload_ship = 0')
    	->andWhere('op.ship_status ='.OrderPackage::SHIP_STATUS_DEFAULT)
    	//->andWhere("op.upload_ship = ".OrderPackage::UPLOAD_SHIP_YES." or (op.upload_ship = ".OrderPackage::UPLOAD_SHIP_NO." and upload_time >= '0000-00-00 00:00:00') ")
    	->andWhere('op.ship_status != '.OrderPackage::SHIP_STATUS_CANCEL)
    	->andWhere('od.site = "'.$site.'"')//站点改变
    	->andWhere('o.paytime >= "'.$startTime.'" ')
    	->group('op.package_id')
    	->order('op.upload_time,op.package_id')
    	->limit('400');
    	//echo $obj->text;
    	!empty($packageID) && $obj->andWhere("op.package_id='{$packageID}'");
    	return $obj->queryAll();
    }
    /**
     * 获取跟踪号
     * @param unknown $orderArr 订单
     * $accountId账号ID
     * @author cxy
     * @since 2017-3-1
     */
    public function getTracknumByOrderId($orderArr,$accountId,$packageToPlatformOrder,$shipCodeArr){
    	$request = new GetTrackingNoRequest();
    	$request->setOrderArr($orderArr['orderNos']);
    	$response = $request->setAccount($accountId)->setRequest()->sendRequest()->getResponse();
    	if(!empty($_REQUEST['test'])) {
            echo 'orderArr:<pre>';print_r($orderArr);
            echo 'packageToPlatformOrder:<pre>';print_r($packageToPlatformOrder);
            echo 'shipCodeArr:<pre>';print_r($shipCodeArr);
            echo 'orderArr:<pre>';print_r($response);
        }
        if (!$request->getIfSuccess()) {
            throw new Exception($request->getErrorMsg(), 110);
        }
        if ( !isset($response->result) || empty($response->result) ) {
            throw new Exception("result is not exist", 112);
       }
	   try {
	       foreach($response->result as $val){
	       	   $platformOrderId   = $val->ordersn;
	       	   $trackingNo        = $val->trackingNo;
	       	   $shopee_ship_code  = isset($val->shippingCarrier) ? $val->shippingCarrier : '';
	       	   $packageId         = $packageToPlatformOrder[$platformOrderId]['package_id'];
	       	   $package_ship_code = $packageToPlatformOrder[$platformOrderId]['ship_code'];
			   $status = 1;
			   $note ='';
			   if(!$trackingNo || $shopee_ship_code != $shipCodeArr[$package_ship_code]){
			   		if(!$trackingNo){
			   			$note .= $platformOrderId.'在平台上没有跟踪号.';
			   		}
			   		if($shopee_ship_code != $shipCodeArr[$package_ship_code]){
			   			$note .= $platformOrderId.'平台上和包裹匹配的渠道不一致';
			   		}
			   		$status = 2;
			   }else{
			  	 	$model = new OrderPackage();
			   	    $ret = $model->updateByPk($packageId,array('track_num' => $trackingNo,'upload_ship' => 1,'track_update_time'=>date('Y-m-d H:i:s')));
			   		if(!$ret){
			   			$status = 2;
			   			$note .= $packageId.'同步跟踪号失败.';
			   		}else{
			   			$note ='从平台同步跟踪号成功';
			   		}
			   }
			 
			   //保存日志
			   $model = new OrderPackageTrackLog();
			   $model->package_id 	= $packageId;
			   $model->ship_code 	= $shopee_ship_code;
			   $model->status 		= $status;
			   $model->upload_time  = date('Y-m-d H:i:s');
			   $model->return_result= $note;
			   $model->setIsNewRecord(true);
			   $model->save();
	       	  
	       }         
	   		return count($response->result);
	   } catch (Exception $e) {
   			echo  $e->getMessage();
       }
    }
    /**
     * curl post操作
     * @param post数据  $post_data
     * @param 进行的操作 $action
     * @return object
     */
    public function getTracknumByOrderIdCurlPost($post_data,$url){
    	$post_data = json_encode($post_data);
    //	$post_data ='{"platform":"SHOPEE","account":"2","orderNos":["161106202935U6R"]}';
    	$hander = curl_init();
    	curl_setopt($hander,CURLOPT_URL,$url);
    	curl_setopt($hander,CURLOPT_HEADER,0);
    	curl_setopt($hander, CURLOPT_HTTPHEADER, array(
    	'Content-Type: application/json',
    	'Content-Length: ' . strlen(json_encode($post_data)))
    	);
    	curl_setopt($hander,CURLOPT_FOLLOWLOCATION,1);
    	curl_setopt($hander,CURLOPT_SSL_VERIFYPEER,0);
    	curl_setopt($hander,CURLOPT_POST, 1);
    	curl_setopt($hander,CURLOPT_POSTFIELDS, $post_data);
    	curl_setopt($hander,CURLOPT_RETURNTRANSFER,true);
    	curl_setopt($hander,CURLOPT_TIMEOUT,60);
    
    	$cnt=0;
    	while($cnt < 3 && ($result=curl_exec($hander))===FALSE) $cnt++;
    		$rinfo=curl_getinfo($hander);
    	var_dump($result);die('aaa');
    	curl_close($hander);
    	return $result;
    
    }
}