<?php

/**
 * @desc Ebay订单主表
 * @author yangsh
 * @since 2016-06-08
 */
class EbayOrderMain extends EbayModel {

    /** @var 同步多少天已付款的订单 */
    const MAX_PAID_DAYS        = 15;

    /** @var tinyint 未付款*/
    const  PAYMENT_STATUS_NOT   = 0;//未付款
    
    /** @var tinyint 已付款*/
    const  PAYMENT_STATUS_END   = 1;//已付款

    //同步到oms标记
    const TO_OMS_OK             = 1; #已同步至OMS
    const TO_OMS_NO             = 0; #未同步至OMS
    const TO_OMS_IN             = 2; #同步执行中

    protected $_ExceptionMsg;

    /** @var int 账号id */
    protected $_AccountID;

	public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_ebay_order_main';
    }

    /**
     * 设置异常信息
     * @param string $message           
     */
    public function setExceptionMessage($message) {
        $this->_ExceptionMsg = $message;
        return $this;
    }

    public function getExceptionMessage() {
        return $this->_ExceptionMsg;
    }       

    /**
     * 设置账号ID
     * @param int $accountID
     */
    public function setAccountID($accountID) {
        $this->_AccountID = $accountID;
        return $this;
    } 

    /**
     * [getOne description]
     * @param  int $orderId [description]
     * @return [type]          [description]
     */
    public function getOne($orderId) {
        return $this->find('platform_order_id = :order_id ',array( ':order_id' => $orderId ) );
    }

    /**
     * [getOneByCondition description]
     * @param  string $fields [description]
     * @param  string $where  [description]
     * @param  mixed $order  [description]
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
     * [getListByCondition description]
     * @param  string $fields [description]
     * @param  string $where  [description]
     * @param  mixed $order  [description]
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
     * [updateByPlatformID description]
     * @param  int $platformOrderID 
     * @param  array $data          
     * @return mixed
     */
    public function updateByPlatformID($platformOrderID, $data) {
        return $this->dbConnection->createCommand()->update(self::tableName(), $data, "platform_order_id='{$platformOrderID}'");
    }    
    
    /**
     * 插入数据
     */
    public function addNewData($data) {
        $isOk = $this->dbConnection->createCommand()->insert(self::tableName(), $data);
        if ($isOk) {
            return $this->dbConnection->getLastInsertID();
        }
        return false;
    }

    /**
     * 查找近2个月订单，判断订单号是否存在
     * @return boolean
     */
    public function checkOrderExist() {
        $row = $this->dbConnection->createCommand()
                    ->select('platform_order_id')
                    ->from(self::tableName())
                    ->where("platform_order_id = :order_id", array(':order_id'=> $platformOrderID))
                    ->andWhere("created_time > :created_time", array(':created_time'=> date('Y-m-d',strtotime('-2 months'))) )
                    ->queryRow();
        return empty($row) ? false : true;
    }
    
    /**
     * [saveOrder description]
     * @param  [type] $order [description]
     * @return [type]        [description]
     */
    public function saveOrder($order) {
        try {
            $data                   = array();//初始值
            $offtime                = 8 * 3600;//北京时间与UTC时间相差8小时间
            $accountID              = $this->_AccountID;
            $currency               = (string)$order->Total->attributes()->currencyID;//刊登站点币种
            $platformOrderId        = trim($order->OrderID);
            $nowTime                = date('Y-m-d H:i:s');
            $orderTransactions      = $order->TransactionArray->Transaction;
            if (empty($orderTransactions)) {
                throw new Exception("Error: Transaction is Empty---$platformOrderId");
            }
            //获取买家邮箱和刊登站点
            foreach ($orderTransactions as $trans) {
                $buyerEmail         = isset($trans->Buyer->Email) && 'Invalid Request' != trim($trans->Buyer->Email) ? trim($trans->Buyer->Email) : '';
                $site               = trim($trans->Item->Site);
                break;                                      
            }
            $oriCreateTime          = date ( 'Y-m-d H:i:s', strtotime ( trim($order->CreatedTime) ) );
            $oriLastModifiedTime    = !empty($checkoutStatus->LastModifiedTime) ? date ('Y-m-d H:i:s',strtotime(trim($checkoutStatus->LastModifiedTime))) :  '0000-00-00 00:00:00';
            $oriPaidTime            = !empty($order->PaidTime) ? date ('Y-m-d H:i:s', strtotime ( trim($order->PaidTime) ) )
                                         : '0000-00-00 00:00:00';
            $oriShippedTime         = !empty($order->ShippedTime) ? date ('Y-m-d H:i:s', strtotime ( trim($order->ShippedTime) ) )
                                         : '0000-00-00 00:00:00';    

            $orderNote              = isset($order->BuyerCheckoutMessage) ? trim($order->BuyerCheckoutMessage) : '';//订单留言
            if ($orderNote != '') {//mysql编码格式utf-8格式，不支持带四字节的字符串插入
                $orderNote = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $orderNote);
            }

            $buyerUserId            = trim($order->BuyerUserID);
            if ($buyerUserId != '' && mb_strlen($buyerUserId) > 60 ) {
                $buyerUserId = mb_substr($buyerUserId,0,60);
            }

            $shippingAddress        = isset($order->ShippingAddress) ? json_decode(json_encode($order->ShippingAddress),true) : array();     
                   
            $shipping_name          = empty($shippingAddress['Name']) ? '' : trim($shippingAddress['Name']);
            if ($shipping_name != '') {//mysql编码格式utf-8格式，不支持带四字节的字符串插入
                $shipping_name = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $shipping_name);
            }    

            $shipping_street1 = empty($shippingAddress['Street1']) ? ''           : trim($shippingAddress['Street1']); 
            if ($shipping_street1 != '') {
                $shipping_street1 = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $shipping_street1);
            }

            $shipping_street2 = empty($shippingAddress['Street2']) ? ''           : trim($shippingAddress['Street2']);   
            if ($shipping_street2 != '') {
                $shipping_street2 = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $shipping_street2);
            }

            $sellerEmail            = 'Invalid Request' != trim($order->SellerEmail) ? trim($order->SellerEmail) : '';
            $checkoutStatus         = $order->CheckoutStatus;//object
            
            $shipping_city_name     = empty($shippingAddress['CityName']) ? ''          : trim($shippingAddress['CityName']);
            if ($shipping_city_name != '') {
                $shipping_city_name = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $shipping_city_name);
            }

            $shipping_country       = empty($shippingAddress['Country']) ? ''           : trim($shippingAddress['Country']);
            $shipping_country_name  =  empty($shippingAddress['CountryName']) ? ''      : trim($shippingAddress['CountryName']);
            
            $shipping_stateorprovince = empty($shippingAddress['StateOrProvince']) ? '' : trim($shippingAddress['StateOrProvince']);
            if ($shipping_stateorprovince != '') {
                $shipping_stateorprovince = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $shipping_stateorprovince);
            }

            $shipping_phone         = empty($shippingAddress['Phone']) ? ''             : trim($shippingAddress['Phone']);
            $shipping_postalcode    = empty($shippingAddress['PostalCode']) ? ''        : trim($shippingAddress['PostalCode']);     
            $data                   = array(
                'platform_order_id'             => $platformOrderId,
                'seller_account_id'             => $accountID,
                'seller_userid'                 => trim($order->SellerUserID),
                'site'                          => $site,
                'buyer_userid'                  => $buyerUserId,
                'seller_email'                  => $sellerEmail,
                'buyer_email'                   => $buyerEmail,
                'order_status'                  => trim($order->OrderStatus),
                'ebay_payment_status'           => trim($checkoutStatus->eBayPaymentStatus),
                'checkout_status'               => trim($checkoutStatus->Status),
                'payment_method'                => trim($checkoutStatus->PaymentMethod),

                'created_time'                  => $oriCreateTime != '0000-00-00 00:00:00' ? date('Y-m-d H:i:s',strtotime($oriCreateTime)-$offtime) : '0000-00-00 00:00:00',
                'last_modified_time'            => $oriLastModifiedTime != '0000-00-00 00:00:00' ? date('Y-m-d H:i:s',strtotime($oriLastModifiedTime)-$offtime) : '0000-00-00 00:00:00',
                'paid_time'                     => $oriPaidTime != '0000-00-00 00:00:00' ? date('Y-m-d H:i:s',strtotime($oriPaidTime)-$offtime) : '0000-00-00 00:00:00',
                'shipped_time'                  => $oriShippedTime != '0000-00-00 00:00:00' ? date('Y-m-d H:i:s',strtotime($oriShippedTime)-$offtime) : '0000-00-00 00:00:00',
                
                'shipping_city_name'            => $shipping_city_name,
                'shipping_country'              => $shipping_country,
                'shipping_country_name'         => $shipping_country_name,
                'shipping_stateorprovince'      => $shipping_stateorprovince,
                'shipping_street1'              => $shipping_street1,
                'shipping_street2'              => $shipping_street2,
                'shipping_name'                 => $shipping_name,
                'shipping_phone'                => $shipping_phone,
                'shipping_postalcode'           => $shipping_postalcode,
                'shipping_service'              => trim($order->ShippingServiceSelected->ShippingService),
                'shipping_service_cost'         => (float)$order->ShippingServiceSelected->ShippingServiceCost,
                'adjustment_amount'             => (float)$order->AdjustmentAmount,
                'amount_paid'                   => (float)$order->AmountPaid,
                'amount_saved'                  => (float)$order->AmountSaved,
                'subtotal'                      => (float)$order->Subtotal,
                'total'                         => (float)$order->Total,
                'currency'                      => $currency,
                'ori_last_modified_time'        => $oriLastModifiedTime,
                'ori_created_time'              => $oriCreateTime,
                'ori_paid_time'                 => $oriPaidTime,
                'ori_shipped_time'              => $oriShippedTime,
                'buyer_checkout_message'        => $orderNote,
            );
            $orderMainInfo = $this->getOneByCondition('id,is_to_oms',"platform_order_id='{$platformOrderId}'");
            if (empty($orderMainInfo)) {
                $data['created_at']       = $nowTime;
                $data['is_to_oms']        = 0;
                $data['payment_status']   = 0;//0:未付款, 由paypal交易信息决定是否已付款   
                $data['to_oms_time']      = '0000-00-00 00:00:00';
                $id = $this->addNewData($data);
            } else {
                /*检查已取消订单是否已同步OMS，对已同步订单先取消未发货的包裹然后标识订单未出货已完成--start--*/
                if(GetOrdersRequest::STATUS_CANCELLED == trim($order->OrderStatus) && self::TO_OMS_OK == $orderMainInfo['is_to_oms']) {
                    $omsOrderInfo = Order::model()->getOrderInfoByPlatformOrderID($platformOrderId, Platform::CODE_EBAY);
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
                            OrderUpdateLog::model()->addOrderUpdateLog($omsOrderInfo['order_id'],'系统检测到订单状态为:'.trim($order->OrderStatus).',自动取消包裹及订单。');
                        }
                    }
                }
                /*检查已取消订单是否已同步OMS，对已同步订单先取消未发货的包裹然后标识订单未出货已完成--end--*/
                
                $this->updateByPk($orderMainInfo['id'],$data);
                $id = $orderMainInfo['id'];
            }
            return $id;
        } catch (Exception $e) {
            $this->setExceptionMessage($e->getMessage());
            return false;
        }
    }

    /**
     * [updateByCondition description]
     * @param  string $platformOrderID    
     * @param  array $datas          
     * @return int
     */
    public function updateByPlatformOrderID($platformOrderID, $datas) {
        return $this->dbConnection
                    ->createCommand()
                    ->update($this->tableName(), $datas, "platform_order_id='{$platformOrderID}'" );
    }

    /**
     * 重置is_to_oms状态为默认
     * @return [type] [description]
     */
    public function resetIsToOmsForDefault() {
        $startTime = date("Y-m-d",strtotime('-'.self::MAX_PAID_DAYS.' days'));
        $toOmsTime = date("Y-m-d H:i:s", strtotime('-2 minutes'));
        return $this->dbConnection
                    ->createCommand()
                    ->update($this->tableName(), array('is_to_oms'=>0), "is_to_oms=2 and paid_time>'{$startTime}' and to_oms_time < '{$toOmsTime}' " ); 
    }
    
    /**
     * 得到已付款、待发货且未同步的订单,最多每次取100个订单
     * @param mixed $platformOrderID
     * @return array
     */
    public function getNotLoadOrderList($platformOrderID) {
        $startTime = date("Y-m-d",strtotime('-'.self::MAX_PAID_DAYS.' days'));
        $obj = $this->getDbConnection()->createCommand()
            ->select('*')
            ->from($this->tableName())
            ->where("order_status in('Completed','CustomCode') and is_to_oms=".self::TO_OMS_NO)
            ->andWhere("payment_status=".self::PAYMENT_STATUS_END)
            ->andWhere("paid_time>'{$startTime}'");
        if (empty($platformOrderID)) {
            $obj->order('paid_time');
            $obj->limit(1000);
        }elseif(is_array($platformOrderID)) {
            $obj->andWhere(array('in','platform_order_id', $platformOrderID));
        } else {
            $obj->andWhere('platform_order_id = :order_id', array(':order_id'=>$platformOrderID));
        }
        // echo $obj->text;
        return $obj->queryAll();
    }

    /**
     * [getFormatedOrderInfo description]
     * @param array $order
     * @return mixed
     */
    public static function getFormatedOrderInfo($order) {
        //1.格式化前数据验证
        $nowTime    = date ( 'Y-m-d H:i:s' );

        //检查国家表是否存在这个名称
        if ($order['shipping_country'] != '' ) {
            $shipCountryName  = Country::model ()->getEnNameByAbbr ( $order['shipping_country'] );
            if ($order['shipping_country_name'] != $shipCountryName) {
                $order['shipping_country_name']  = $shipCountryName;
            }
        }
        if ($order['shipping_country'] == "US" && $order['shipping_country_name'] == "United States" && $order['shipping_stateorprovince'] == "PR") {
            $order['shipping_country']       = "PR";
            $order['shipping_country_name']  = "Puerto Rico";
        }
        //关岛现为美国海外属地，是美国的非宪辖管制领土。但是由于运费的区别，发特殊属性与美国本土很大区别,请GU关岛订单导入直接为关岛
        if ($order['shipping_country'] == "US" && $order['shipping_country_name'] == "United States" && $order['shipping_stateorprovince'] == "GU") {
            $order['shipping_country']       = "GU";
            $order['shipping_country_name']  = "Guam";
        }

        //Croatia, Republic of => Croatia
        if ($order['shipping_country'] == "HR" && $order['shipping_country_name'] == "Croatia, Republic of") {
            $order['shipping_country_name']  = "Croatia";
        }

        //2.组装OMS需要的数据格式
        $formatOrderInfo = array(
            'platform_order_id'    => $order['platform_order_id'],
            'account_id'           => $order['seller_account_id'],
            'log_id'               => 999,
            'order_status'         => $order['order_status'],
            'buyer_id'             => $order['buyer_userid'],
            'email'                => $order['buyer_email'],
            'timestamp'            => $nowTime,
            'created_time'         => $order['created_time'],
            'last_update_time'     => $order['last_modified_time'],
            'ship_cost'            => $order['shipping_service_cost'],//运费(包含成交费)
            'subtotal_price'       => $order['subtotal'],//产品总金额(包含成交费)
            'total_price'          => $order['total'],//订单交易金额(包含成交费)
            'final_value_fee'      => 0,//成交费,由明细表计算
            'insurance_amount'     => 0,//运费险,由明细表计算
            'currency'             => $order['currency'],
            'ship_name'            => $order['shipping_name'],
            'ship_country'         => $order['shipping_country'],
            'ship_country_name'    => $order['shipping_country_name'],
            'ship_zip'             => $order['shipping_postalcode'],
            'ship_city_name'       => $order['shipping_city_name'],
            'ship_stateorprovince' => $order['shipping_stateorprovince'],
            'ship_street1'         => $order['shipping_street1'],
            'ship_street2'         => $order['shipping_street2'],
            'ship_phone'           => $order['shipping_phone'],
            'paytime'              => $order['paid_time'],
            'ori_create_time'      => $order['ori_created_time'],
            'ori_update_time'      => $order['ori_last_modified_time'],
            'ori_pay_time'         => $order['ori_paid_time'],
            'payment_status'       => $order['payment_status'],
        );
        $orderNote  = trim($order['buyer_checkout_message']);
        if ($orderNote != ''){
            $formatOrderInfo['complete_status'] = Order::COMPLETE_STATUS_PENGDING;
        }

        //组装订单sku与销售关系数据
        $partFormatOrderInfo = array(
            'platform_order_id'    => $order['platform_order_id'],
            'account_id'           => $order['seller_account_id'],
        );

        return array($formatOrderInfo, $partFormatOrderInfo);
    }

    /**
     * @desc 同步订单到oms系统
     * @param  string  $platformOrderIDs
     * @param  boolean $showResult
     * @return int
     */
    public function syncOrderToOms($platformOrderIDs=null,$showResult=false) {
        $orderCount = 0;
        $platformCode = Platform::CODE_EBAY;
        //重置is_to_oms状态为默认
        UebModel::model('EbayOrderMain')->resetIsToOmsForDefault();
        if ($platformOrderIDs && strpos($platformOrderIDs,',')>0) {
            $platformOrderIDs = explode(',',$platformOrderIDs);
        }
        $orderList = UebModel::model('EbayOrderMain')->getNotLoadOrderList($platformOrderIDs);
        if(empty($orderList)){
            echo '没有需要同步订单!';
            return 0;
        }
        //标记订单信息同步中
        foreach($orderList as $order){
            $platformOrderID = $order['platform_order_id'];
            UebModel::model('EbayOrderMain')->updateByPlatformOrderID($platformOrderID, array('is_to_oms' => EbayOrderMain::TO_OMS_IN,'to_oms_time' => date('Y-m-d H:i:s')) );          
        }

        $getOrderNoFailureFlag = array();//获取订单号失败超过10次则退出程序
        $flags = array();
        foreach($orderList as $order){
            $orderObj               = new Order();
            $flag                   = array();
            $platformOrderID        = $order['platform_order_id'];//平台订单号
            $orderNote              = trim($order['buyer_checkout_message']);//订单备注
            $orderId                = AutoCode::getCodeNew('order'); // 获取订单号
            if (empty($orderId)) {
                $flag               = array('errCode'=>'getCodeNew','errMessage'=>$platformOrderID.'###'.date("Y-m-d H:i:s").'订单号获取失败');
                $getOrderNoFailureFlag[] = $platformOrderID;
            } else {
                $orderId            = $orderId. "EB"; // 获取订单号
            }

            if (!empty($getOrderNoFailureFlag) && count($getOrderNoFailureFlag)>10) {
                $errLogMessage = '获取订单号失败:'.implode(',',$getOrderNoFailureFlag);
                echo $errLogMessage."<br>";
                $this->addEbayLog($order['seller_account_id'],EbayLog::STATUS_FAILURE,$errLogMessage);
                return $orderCount;
            }

            //1. 根据平台订单号获取订单信息,进行以下条件判断：
            $orderInfo              = Order::model()->getOrderInfoByPlatformOrderID($platformOrderID, $platformCode);

            //1.1 判断订单是否已导入，则跳出
            if (empty($flag)) {
                if(!empty($orderInfo)){
                    echo $platformOrderID.'--'.'标记已存在！<br>';
                    UebModel::model('EbayOrderMain')->updateByPlatformOrderID($platformOrderID, array('is_to_oms' => EbayOrderMain::TO_OMS_OK,'to_oms_time' => date('Y-m-d H:i:s')));//标记已同步成功                  
                    continue;
                }
            }

            //获取系统订单号
            if (!empty($orderInfo)) {
                $orderId = $orderInfo['order_id'];
            }
            if ( $orderId == '' ) {
                $flag = array('errCode'=>'order_id','errMessage'=>$platformOrderID.'取订单号失败');
            }

            //1.2 判断原始订单关键信息是否为空
            if (empty($flag)) {
                list($formatOrderInfo, $partFormatOrderInfo) = EbayOrderMain::getFormatedOrderInfo($order);
                if (!$formatOrderInfo) {
                    $flag               = array('errCode'=>'formatOrderInfo','errMessage'=>$platformOrderID.'###'.date("Y-m-d H:i:s").'订单主表数据不完整，待补拉');
                }
            }

            //1.3 判断原始订单详情关键信息是否为空
            if (empty($flag)) {
                $detailInfos            = UebModel::model('EbayOrderDetail')->getListByCondition('*',"platform_order_id='{$platformOrderID}'","created_date ASC");
                if ( empty($detailInfos) ){
                    $flag               = array('errCode'=>'EbayOrderDetail','errMessage'=>$platformOrderID.'###'.date("Y-m-d H:i:s").'订单明细为空');
                }
            }

            if (empty($flag)) {
                list($formatDetailInfos,$partFormatDetails,$orderDatas,$orderExtendInfo,$formatDetailsExts) = EbayOrderDetail::getFormatedOrderDetailInfos($order,$detailInfos);
                if ( empty($formatDetailInfos) ) {
                    $flag               = array('errCode'=>'formatDetailInfos','errMessage'=>$platformOrderID.'###'.date("Y-m-d H:i:s").'订单明细数据不完整，待补拉');
                }
            }

            //1.4 判断原始交易信息是否为空
            if (empty($flag)) {
                $orderTransactionInfos  = UebModel::model('EbayOrderTransaction')->getListByCondition('*',"platform_order_id='{$platformOrderID}' AND is_delete=0 AND payment_status='Completed' ","pay_time ASC");
                if ( empty($orderTransactionInfos)){
                    $flag               = array('errCode'=>'EbayOrderTransaction','errMessage'=>$platformOrderID.'###'.date("Y-m-d H:i:s").'订单交易信息为空');
                }
            }

            if (empty($flag)) {
                $formatOrderTransactionInfos    = EbayOrderTransaction::getFormatedOrderTransactions($orderTransactionInfos);
                $formatPaypalTransactionInfos   = EbayOrderTransaction::getFormatedPaypalTransactions($orderTransactionInfos);
                if (!$formatOrderTransactionInfos || !$formatPaypalTransactionInfos ) {
                    $flag       = array('errCode'=>'formatTransactionInfos','errMessage'=>$platformOrderID.'订单交易数据不完整，待补拉');
                }
            }

            //2. 开始同步
            $dbTransaction = $orderObj->dbConnection->beginTransaction();
            if (empty($flag)) {
                try {
                    $commonOrder = new CommonOrder();

                    //保存订单主表信息
                    $formatOrderInfo['final_value_fee']  = $orderDatas['final_value_fee'];//成交费
                    $formatOrderInfo['insurance_amount'] = $orderDatas['insurance_amount'];//运费险
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
                    $isNeedMatchAdapter = $orderDatas['isNeedMatchAdapter'];
                    if ($isNeedMatchAdapter) {
                        $isOk = $commonOrder->addOrderAdapter($platformCode,$orderId,$formatOrderInfo,$formatDetailInfos);
                        if (!$isOk) {
                            throw new Exception("addOrderAdapter Error " . $commonOrder->getExceptionMessage());
                        }
                    }

                    //保存订单sku异常信息
                    $orderSkuExceptionMsg = $orderDatas['orderSkuExceptionMsg'];
                    if ($orderSkuExceptionMsg != '') {
                        $commonOrder->setExceptionOrder($orderId, OrderExceptionCheck::EXCEPTION_SKU_UNKNOWN, $orderSkuExceptionMsg);
                    }

                    //如果订单已支付且订单状态为CustomCode,设置为订单规则异常
                    if(self::PAYMENT_STATUS_END == $order['payment_status'] && 'CustomCode' == $order['order_status'] ) {
                        $commonOrder->setExceptionOrder($orderId, OrderExceptionCheck::EXCEPTION_ORDER_RULE, '订单状态为CustomCode,请向买家确认是否要发货!');
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
                    $flag = array('errCode'=>'success','errMessage'=>$platformOrderID.'###'.date("Y-m-d H:i:s").' ### 同步成功！');
                    $orderCount++;
                } catch (Exception $e) {
                    $flag = array('errCode'=>'failure','errMessage'=>$platformOrderID.'###'.date("Y-m-d H:i:s").'@@@'.$e->getMessage() );
                }
            }

            //add log
            if ($flag['errCode'] != 'success') {
                $errLogMessage = mb_substr($flag['errMessage'], 0, ( ($len = mb_strlen($flag['errMessage']))>500 ? 500: $len) );
                $this->addEbayLog($order['seller_account_id'],EbayLog::STATUS_FAILURE,$errLogMessage);
            }

            //根据执行是否出错，确认提交还是回滚
            if ($flag['errCode'] == 'success') {            
                UebModel::model('EbayOrderMain')->updateByPlatformOrderID($platformOrderID, array('is_to_oms' => EbayOrderMain::TO_OMS_OK,'to_oms_time' => date('Y-m-d H:i:s')));//标记已同步成功  
                $dbTransaction->commit();
            } else {
                $dbTransaction->rollback();
                UebModel::model('EbayOrderMain')->updateByPlatformOrderID($platformOrderID, array('is_to_oms' => EbayOrderMain::TO_OMS_NO,'to_oms_time' => date('Y-m-d H:i:s')));//标记未同步
            }

            //记录错误信息
            if ( $showResult ) {
                $flags[$platformOrderID] = $flag;           
            }
        }
        // show result
        if ( $showResult ) {
            MHelper::printvar($flags,false);
        }
        return $orderCount;
    }

    /**
     * [addEbayLog description]
     * @param [type] $accountID [description]
     * @param [type] $status    [description]
     * @param [type] $message   [description]
     * @param [type] $eventName [description]
     */
    public function addEbayLog($accountID,$status,$message,$eventName=EbayLog::EVENT_SYNCORDER) {
        $logModel = new EbayLog();
        return $logModel->getDbConnection()->createCommand()->insert(
            $logModel->tableName(), array(
                'account_id'    => $accountID,
                'event'         => $eventName,
                'start_time'    => date('Y-m-d H:i:s'),                         
                'status'        => $status,
                'message'       => $message,
                'response_time' => date('Y-m-d H:i:s'),
                'end_time'      => date('Y-m-d H:i:s'),
                'create_user_id'=> intval(Yii::app()->user->id),
            )
        );        
    }

}