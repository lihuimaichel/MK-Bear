<?php
/**
 * @desc Lazada订单主表
 * @author yangsh
 * @since 2016-10-12
 */
class LazadaOrderMain extends LazadaModel{

    /** @var string 异常信息*/
    public $exception           = null;
    
    /** @var string 平台订单号*/
    protected $_PlatformOrderID = null;
    
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

    /** @var string 订单号*/
    public $order_co_id         = '';      

    /** @var string lazada订单状态*/
    const STATUS_PENDING    = 'pending';
    const STATUS_CANCEL     = 'canceled';
    const STATUS_READY      = 'ready_to_ship';
    const STATUS_DELIVERED  = 'delivered';
    const STATUS_RETURNED   = 'returned';
    const STATUS_SHIPED     = 'shipped';
    const STATUS_FAILED     = 'failed';

    /** @var int is_downdetail*/
    const IS_DOWNDETAIL_YES = 1;
    const IS_DOWNDETAIL_NO  = 0;
    
    const MAX_TO_OMS_DAYS   = 7;//天
    
    //同步到oms标记
    const TO_OMS_DEFAULT    = 0; #默认未同步
    const TO_OMS_YES        = 1; #同步成功
    const TO_OMS_NO         = 2; #同步失败
    const TO_OMS_RUNNING    = 3; #同步执行中    
   
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_lazada_order_main';
    }  

    /**
     * @desc 平台订单号
     * @param [type] $platformOrderID [description]
     */
    public function setPlatformOrderID($platformOrderID) {
        $this->_PlatformOrderID = $platformOrderID;
        return $this;
    }

    /**
     * @desc 账号ID
     * @param [type] $accountID 
     */
    public function setAccountID($accountID){
        $this->_AccountID = $accountID;
        return $this;
    }

    /**
     * @desc 账号分组ID
     * @param [type] $accountGroupID 
     */
    public function setAccountGroupID($accountGroupID){
        $this->_AccountGroupID = $accountGroupID;
        return $this;
    }  

    /**
     * @desc 旧账号ID
     * @param int $oldAccountID 
     */
    public function setOldAccountID($oldAccountID){
        $this->_OldAccountID = $oldAccountID;
        return $this;
    }    

    /**
     * @desc 站点ID
     * @param [type] $siteID [description]
     */
    public function setSiteID($siteID){
        $this->_SiteID = $siteID;
        return $this;
    }     

    /**
     * @desc 设置日志编号
     * @param int $logID
     */
    public function setLogID($logID){
        $this->_LogID = $logID;
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
     * @desc 获取异常信息
     * @return string
     */
    public function getExceptionMessage(){
        return $this->exception;
    }    

    /**
     * [getOneByCondition description]
     * @param  string $fields [description]
     * @param  string $where  [description]
     * @param  mixed $order  [description]
     * @return array
     * @author yangsh
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
     * [getListByCondition description]
     * @param  string $fields [description]
     * @param  string $where  [description]
     * @param  mixed $order  [description]
     * @return array
     * @author yangsh
     */
    public function getListByCondition($fields='*', $where='1',$order='',$limit=null) {
        $cmd = $this->dbConnection->createCommand();
        $cmd->select($fields)
            ->from(self::tableName())
            ->where($where);
        $order != '' && $cmd->order($order);
        $limit != null && $cmd->limit($limit);
        return $cmd->queryAll();
    }  

    /**
     * @desc updateByCondition
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
     */
    public function resetIsToOmsForDefault() {
        $startTime = date("Y-m-d",strtotime('-'.self::MAX_TO_OMS_DAYS.' days'));
        $endTime = date("Y-m-d H:i:s", strtotime('-2 minutes'));
        return $this->dbConnection
                    ->createCommand()
                    ->update($this->tableName(), array('is_to_oms'=>self::TO_OMS_DEFAULT), "is_to_oms=:to_oms and to_oms_time>'{$startTime}' and to_oms_time < '{$endTime}' ", array(':to_oms'=> self::TO_OMS_RUNNING) );
    }

    /**
     * @desc 获取可同步订单的状态
     * @return [type] [description]
     */
    public static function getAbleSyncOrderStatus() {
        return array(self::STATUS_PENDING, self::STATUS_READY, self::STATUS_SHIPED, self::STATUS_DELIVERED );
    }

    /**
     * 得到待发货且未同步的订单,最多每次取500个订单
     * @param mixed $platformOrderID
     * @return array
     */
    public function getNotLoadOrderList($platformOrderID) {
        $startTime = date("Y-m-d",strtotime('-'.self::MAX_TO_OMS_DAYS.' days'));
        $obj = $this->getDbConnection()->createCommand()
                    ->select('*')
                    ->from($this->tableName())
                    ->where("is_downdetail=:is_down",array(':is_down'=>self::IS_DOWNDETAIL_YES))
                    ->andWhere(array('in','is_to_oms',array(self::TO_OMS_DEFAULT,self::TO_OMS_NO)))
                    ->andWhere(array('in','order_status', self::getAbleSyncOrderStatus() ))
                    ->andWhere("created_at>'{$startTime}'");
        if (empty($platformOrderID)) {
            $obj->order('created_at desc');
            $obj->limit(1000);
        }elseif(is_array($platformOrderID)) {
            $obj->andWhere(array('in','platform_order_id', $platformOrderID));
        } else {
            $obj->andWhere('platform_order_id = :order_id', array(':order_id'=>$platformOrderID));
        }
        echo "<br/>";
        echo $obj->text;
        echo "<br/>";
        return $obj->queryAll();
    } 

    /**
     * @desc 格式化订单数据
     */
    public static function getFormatedOrderInfo($order) {
        //1.格式化前数据验证  
        //Shipping Address
        $street1 = trim($order['address_shipping_address1']);//Address1
        $street2 = trim($order['address_shipping_address2']);//Address2
        $street3 = trim($order['address_shipping_address3']) != '' ? ','. trim($order['address_shipping_address3']) : '';
        $street4 = trim($order['address_shipping_address4']) != '' ? ','. trim($order['address_shipping_address4']) : '';
        $street5 = trim($order['address_shipping_address5']) != '' ? ','. trim($order['address_shipping_address5']) : '';
        //SG站点： Address3, Address4, Address5 不用合并到 Address2, 其他站点不变
        if ($order['site_id'] != 2) {
            $street2 .= $street3 . $street4 . $street5;
            $city = trim($order['address_shipping_city']);
        } else {
            $city = '';
        }
        $shippingCountryName = trim($order['address_shipping_country']);
        $shippingCountry     = Country::model()->getAbbrByEname($shippingCountryName);
        $buyerId             = trim($order['customer_first_name']. ' ' . $order['customer_last_name']);
        $shippingName        = trim($order['address_shipping_first_name']).' '.trim($order['address_shipping_last_name']); 
        $payMethod           = trim($order['payment_method']);
        $currency            = trim($order['currency']);
        $isDownDetail        = $order['is_downdetail'];
        //验证数据是否为空                    
        if ( $buyerId == '' || $shippingName == '' || $currency == '' || $payMethod == ''
             || $isDownDetail != self::IS_DOWNDETAIL_YES) {
            return false;
        }
        $nowTime = date ( 'Y-m-d H:i:s' );
        if( in_array($order['site_id'], array(3,6)) ){
            //越南站和印尼站东7区utc+7
            $ori_create_time = date('Y-m-d H:i:s', strtotime( $order['created_at'] ) + 3600 );
            $ori_update_time = date('Y-m-d H:i:s', strtotime( $order['updated_at'] ) + 3600);
            $ori_pay_time    = $ori_create_time;
        } else {
            //其他站东八区utc+8
            $ori_create_time = date('Y-m-d H:i:s', strtotime( $order['created_at'] ));
            $ori_update_time = date('Y-m-d H:i:s', strtotime( $order['updated_at'] ));
            $ori_pay_time    = $ori_create_time;
        }
        $paymentType     = $payMethod == 'CashOnDelivery' ? "COD" : "";
        //$finalValueFee   = round($order['subtotal_price'] * $order['commission_rate'], 2);

        //2.组装OMS需要的数据格式
        $formatOrderInfo = array(
            'platform_order_id'    => $order['platform_order_id'],
            'account_id'           => $order['old_account_id'],
            'log_id'               => $order['log_id'],
            'order_status'         => $order['order_status'],
            'buyer_id'             => $buyerId,
            'email'                => $order['address_bill_customer_email'],
            'timestamp'            => $nowTime,
            'created_time'         => $order['created_at'],
            'last_update_time'     => $order['updated_at'],
            'paytime'              => $order['created_at'],
            'ship_cost'            => $order['shipping_amount'],//运费(包含成交费)
            'subtotal_price'       => 0,//产品总金额(包含成交费),由明细表计算
            'total_price'          => $order['price'],//订单交易金额(包含成交费)
            'final_value_fee'      => 0,//成交费,由明细表计算
            'insurance_amount'     => 0,//运费险,无
            'currency'             => $order['currency'],
            'ship_name'            => $shippingName,
            'ship_country'         => $shippingCountry,
            'ship_country_name'    => $order['address_shipping_country'],
            'ship_zip'             => $order['address_shipping_postcode'],
            'ship_city_name'       => $city,
            'ship_stateorprovince' => '',
            'ship_street1'         => $street1,
            'ship_street2'         => $street2,
            'ship_phone'           => $order['address_shipping_phone'],
            'ori_create_time'      => $ori_create_time,
            'ori_update_time'      => $ori_update_time,
            'ori_pay_time'         => $ori_pay_time,
            'payment_status'       => Order::PAYMENT_STATUS_END,
        );

        //组装订单sku与销售关系数据
        $partFormatOrderInfo = array(
            'platform_order_id'    => $order['platform_order_id'],
            'account_id'           => $order['old_account_id'],
        );
        return array($formatOrderInfo, $partFormatOrderInfo);
    }

    /**
     * [getFormatOrderTransaction description]
     * @param  [type] $orderId [description]
     * @param  [type] $order   [description]
     * @return [type]          [description]
     */
    public function getFormatOrderTransaction($orderId,$order) {
        if ($order['price'] <= 0 || $order['old_account_id'] == '' || $order['currency'] == '') {
            return false;
        }
        $paymentType = $order['payment_method'] == 'CashOnDelivery' ? 'COD' : $order['payment_method'];
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
            'order_pay_time'            => date('Y-m-d H:i:s', strtotime($order['created_at'])),
            'last_update_time'          => date('Y-m-d H:i:s', strtotime($order['updated_at'])),
            'fee_amt'                   => round($order['subtotal_price']*$order['paymentfee_rate'],2),
            'amt'                       => $order['price'],
            'account_id'                => $order['old_account_id'],
            'platform_code'             => Platform::CODE_LAZADA,
            'currency'                  => $order['currency'],
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

    public function getFormatPaypalTransactionRecord($orderId,$order) {
        if ( $order['price'] <= 0 || $order['currency'] == '' || 
            ($order['customer_first_name'] == '' && $order['customer_last_name'] == '' ) ) {
            return false;
        }
        $paymentType = $order['payment_method'] == 'CashOnDelivery' ? 'COD' : $order['payment_method'];
        if($paymentType == 'COD'){
            //@todo 后期同步订单需要将k3_cloud_status、sync_cloud_error重置为0
            $paymentStatus = "Pending";
        }else{
            $paymentStatus = "Completed";
        }        
        $peyerName = trim($order['customer_first_name'] . ' ' . $order['customer_last_name']);
        $rtn = array(
                'transaction_id'            => $orderId,
                'order_id'                  => $orderId,
                'receive_type'              => OrderTransaction::RECEIVE_TYPE_YES,
                'receiver_business'         => '',
                'receiver_email'            => 'unknown@vakind.com',
                'receiver_id'               => '',
                'payer_id'                  => '',
                'payer_name'                => $peyerName,
                'payer_email'               => '',
                'payer_status'              => '',
                'parent_transaction_id'     => '',
                'transaction_type'          => '',
                'payment_type'              => $paymentType,
                'order_time'                => date('Y-m-d H:i:s', strtotime($order['created_at'])),
                'amt'                       => $order['price'],
                'fee_amt'                   => round($order['subtotal_price']*$order['paymentfee_rate'],2),
                'tax_amt'                   => '',
                'currency'                  => $order['currency'],
                'payment_status'            => $paymentStatus,
                'note'                      => '',
                'modify_time'               => '0000-00-00 00:00:00'
        );
        return array($rtn);
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
                $sku                = $detail['sku'];
                $quantity           = $detail['quantity'];
                //查找sku对应的赠品
                $giftSkuInfo = LazadaGiftManage::model()->getOneByCondition('gift_sku',"account_id={$accountID} and sku='{$sku}' and is_delete=0");
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
     * @desc 批量更新COD订单交易状态
     * @param  integer $limit 
     * @param  string  $platformOrderID 
     * @return int
     */
    public function updateCODTransactionStatus($limit=500,$platformOrderID=null) {
        $orderCount = 0;
        $start = date('Y-m-d',strtotime('-60 days'));
        $accountList = LazadaAccount::model()->getAbleAccountList();
        foreach ($accountList as $accountInfo) {
            $cmd = $this->getDbConnection()->createCommand()
                        ->select('platform_order_id,order_number,old_account_id,created_at,updated_at')
                        ->from($this->tableName())
                        ->where("seller_account_id={$accountInfo['id']}")
                        ->andWhere("payment_method='CashOnDelivery'")
                        ->andWhere("order_status=:status",array(':status'=>self::STATUS_DELIVERED))
                        ->andWhere("is_updated_trans=0 ")
                        ->andWhere("updated_at>'{$start}'")
                        ->limit($limit);
            if (!empty($platformOrderID) ) {
                $cmd->andWhere("platform_order_id='{$platformOrderID}'");
            }
            $platformOrderIDs = array();
            $updateTimes = array();
            $rlist = $cmd->queryAll();
            if (!empty($rlist)) {
                foreach ($rlist as $rval) {
                    $platformOrderIDs[] = $rval['platform_order_id'];
                    $updateTimes[$rval['platform_order_id']] = $rval['updated_at'];
                    //检测2016年12月份是否有相同订单, 老api拉下来的订单且已发货要更新状态或更换了platform_order_id的订单
                    if ( $rval['created_at'] > '2016-12-01' ) {
                        $o_cmd = $this->getDbConnection()->createCommand()
                                        ->select('platform_order_id')
                                        ->from($this->tableName())
                                        ->where("order_number='{$rval['order_number']}'")
                                        ->andWhere("old_account_id='{$rval['old_account_id']}'")
                                        ->andWhere("order_status!=:status",array(':status'=>self::STATUS_DELIVERED));
                        $existOrderInfo = $o_cmd->queryRow();
                        if (!empty($existOrderInfo) && !array_key_exists($existOrderInfo['platform_order_id'],$updateTimes) ) {
                            $platformOrderIDs[] = $existOrderInfo['platform_order_id'];
                            //用新api更新的订单时间
                            $updateTimes[$existOrderInfo['platform_order_id']] = $rval['updated_at'];
                        }
                    }
                }
                $sucPlatformOrderIDs = CommonOrder::model()->updateOmsTransactionStatus(Platform::CODE_LAZADA, $platformOrderIDs, $updateTimes);               
                if ($sucPlatformOrderIDs) {
                    $this->getDbConnection()->createCommand()->update($this->tableName(),
                        array('is_updated_trans' => 1,'updated_trans_time'=>date('Y-m-d H:i:s')),
                        "platform_order_id in('".implode("','",$sucPlatformOrderIDs)."')");
                    $orderCount += count($sucPlatformOrderIDs);
                }
                //指定订单号执行一次后跳出
                if (!empty($platformOrderID) ) {
                    break;
                }
            }
        }
        return $orderCount;
    }

    /**
     * @desc 同步订单到oms系统
     * @param  string  $platformOrderIDs
     * @param  boolean $showResult
     * @return int
     */
    public function syncOrderToOms($platformOrderIDs=null,$showResult=false) {
        $orderCount = 0;
        $platformCode = Platform::CODE_LAZADA;
        //重置is_to_oms状态为默认
        UebModel::model('LazadaOrderMain')->resetIsToOmsForDefault();
        if ($platformOrderIDs && strpos($platformOrderIDs,',')>0) {
            $platformOrderIDs = explode(',',$platformOrderIDs);
        }
        $orderList = UebModel::model('LazadaOrderMain')->getNotLoadOrderList($platformOrderIDs);
        if(empty($orderList)){
            echo '没有需要同步订单!';
            return 0;
        }
        
        //标记订单信息同步中
        foreach($orderList as $key => $order){
            $platformOrderID        = $order['platform_order_id'];
            UebModel::model('LazadaOrderMain')->updateByPlatformOrderID($platformOrderID, array('is_to_oms' => LazadaOrderMain::TO_OMS_RUNNING,'to_oms_time' => date('Y-m-d H:i:s')) );
        }

        $getOrderNoFailureFlag = array();//获取订单号失败超过10次则退出程序
        $flags = array();   
        foreach($orderList as $key => $order){
            $orderObj               = new Order();
            $flag                   = array();
            $platformOrderID        = $order['platform_order_id'];//平台订单号
            $oldAccountID           = $order['old_account_id'];
            //完整的平台订单号：ph-008-384477218-105757289
            $platformOrderIDArr = explode('-',$platformOrderID);
            if(count($platformOrderIDArr) != 4) {
                continue;
            }            
            $orderNote              = trim($order['remarks']);//订单备注
            $orderId                = AutoCode::getCodeNew('order'); // 获取订单号
            if (empty($orderId)) {
                echo '订单号获取失败';
                $flag               = array('errCode'=>'getCodeNew','errMessage'=>$platformOrderID.'###'.date("Y-m-d H:i:s").'订单号获取失败');
                $getOrderNoFailureFlag[] = $platformOrderID;
            } else {
                $orderId            = $orderId. "LZD"; // 获取订单号
            }

            if (!empty($getOrderNoFailureFlag) && count($getOrderNoFailureFlag)>10) {
                $errLogMessage = '获取订单号失败:'.implode(',',$getOrderNoFailureFlag);
                echo $errLogMessage."<br>";
                $this->addLazadaLog($order['seller_account_id'],LazadaLog::STATUS_FAILURE,$errLogMessage);
                return $orderCount;
            }              

            //1.判断订单是否已存在, 账号、站点、OrderNumber -- 唯一
            $platformOrderIDLike = '%'.'-'.'%'.'-'.$platformOrderIDArr[2].'-'.'%';//%-%-OrderNumber-%
            $o_cmd = $orderObj->getDbConnection()->createCommand()
                            ->select('*')
                            ->from($orderObj->tableName())
                            ->where("account_id='{$oldAccountID}'")
                            ->andWhere("platform_code='{$platformCode}' and platform_order_id like '{$platformOrderIDLike}' ");
            //echo $o_cmd->getText()."<br>";
            $orderInfo = $o_cmd->queryRow();
            if (!empty($orderInfo)) {
                echo $platformOrderID.'--'.'标记已存在！<br>';
                    UebModel::model('LazadaOrderMain')->updateByPlatformOrderID($platformOrderID, array('is_to_oms' => LazadaOrderMain::TO_OMS_YES,'to_oms_time' => date('Y-m-d H:i:s')));//标记已同步成功                  
                    continue;
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
                list($formatOrderInfo, $partFormatOrderInfo) = LazadaOrderMain::getFormatedOrderInfo($order);
                if (!$formatOrderInfo) {
                    $flag               = array('errCode'=>'formatOrderInfo','errMessage'=>$platformOrderID.'###'.date("Y-m-d H:i:s").'订单主表数据不完整，待补拉');
                }
            }

            //1.3 判断原始订单详情关键信息是否为空
            if (empty($flag)) {
                $detailInfos            = UebModel::model('LazadaOrderDetail')->getListByCondition('*',"platform_order_id='{$platformOrderID}' and status in('".implode("','", self::getAbleSyncOrderStatus() )."','canceled') ");
                if ( empty($detailInfos) ){
                    $flag               = array('errCode'=>'LazadaOrderDetail','errMessage'=>$platformOrderID.'###'.date("Y-m-d H:i:s").'订单明细为空');
                }
            }          

            if (empty($flag)) {
                list($formatDetailInfos,$partFormatDetails,$matchGiftInfos,$orderDatas,$formatDetailsExts,$orderExtendInfo) = LazadaOrderDetail::getFormatedOrderDetailInfos($order,$detailInfos);
                if ( empty($formatDetailInfos) ) {
                    $flag               = array('errCode'=>'formatDetailInfos','errMessage'=>$platformOrderID.'###'.date("Y-m-d H:i:s").'订单明细数据不完整，待补拉');
                }
            }

            //订单完成状态、发货状态统一在这里设置
            if ( empty($flag) ){
                $isLazadaShipping  = $orderDatas['isLazadaShipping'];//订单全由lazada发货
                $existOwnWareHouse = $orderDatas['existOwnWareHouse'];//存在由lazada发货的item
                $isPartialCancel   = $orderDatas['isPartialCancel'];//item是否部分取消

                //有订单备注，标识订单待处理
                if ($orderNote != '') {
                    $formatOrderInfo['complete_status'] = Order::COMPLETE_STATUS_PENGDING;
                }
                
                //海外仓账号：FBL-SG（即同个账号B-SG）,拉单下来默认已完成已发货  Liz|20161112
                if ( $order['old_account_id'] == 40 || ($isLazadaShipping && in_array($order['site_id'],array(LazadaSite::SITE_ID,LazadaSite::SITE_TH)) ) ) {
                    $formatOrderInfo['complete_status'] = Order::COMPLETE_STATUS_END;   //3-已完成
                    $formatOrderInfo['ship_status'] = Order::SHIP_STATUS_YES;           //2-已出货
                }

                //锁定既有lazada发货又有深圳仓发货的订单及部分取消订单
                if ($isPartialCancel || (!$isLazadaShipping && $existOwnWareHouse) ) {
                    $formatOrderInfo['complete_status'] = Order::COMPLETE_STATUS_WAIT_CANCEL;
                    $formatOrderInfo['is_lock'] = 1;//锁定
                }

                //对主表的final_value_fee的值进行更改，取ueb_order_detail表里的final_value_fee值进行累加
                $formatOrderInfo['final_value_fee'] = $orderDatas['final_value_fee'];
                $formatOrderInfo['subtotal_price'] = $orderDatas['subtotal_price'];//产品总金额

                //更新order原订单信息subtotal_price
                $order['subtotal_price'] = $orderDatas['subtotal_price'];//产品总金额
            }

            //验证订单交易   
            if( empty($flag) ){
                $formatOrderTransactionInfos = UebModel::model('LazadaOrderMain')->getFormatOrderTransaction($orderId,$order);
                if (empty($formatOrderTransactionInfos)) {
                    $flag = array('errCode'=>'formatOrderTransaction','errMessage'=>$platformOrderID.'###'.date("Y-m-d H:i:s").'订单交易数据不完整，待补拉');
                }
                $formatPaypalTransactionInfos = UebModel::model('LazadaOrderMain')->getFormatPaypalTransactionRecord($orderId,$order);
                 if (empty($formatPaypalTransactionInfos)) {
                    $flag = array('errCode'=>'formatPaypalTransactionRecord','errMessage'=>$platformOrderID.'###'.date("Y-m-d H:i:s").'订单付款数据数据不完整，待补拉');
                }
            }

            //2. 开始同步
            $dbTransaction = $orderObj->dbConnection->beginTransaction();
            if (empty($flag)) {
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
                    $lazadaOrderMain = new LazadaOrderMain();
                    $isOk = $lazadaOrderMain->addGift($orderId,$platformOrderID,$matchGiftInfos);
                    if (!$isOk) {
                        throw new Exception("addGift Error " . $lazadaOrderMain->getExceptionMessage());
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
                    $flag = array('errCode'=>'success','errMessage'=>$platformOrderID.'###'.date("Y-m-d H:i:s").' ### 同步成功！');
                    $orderCount++;
                } catch (Exception $e) {
                    $flag = array('errCode'=>'failure','errMessage'=>$platformOrderID.'###'.date("Y-m-d H:i:s").'@@@'.$e->getMessage() );
                }
            }

            //add log
            if ($flag['errCode'] != 'success') {
                $errLogMessage = mb_substr($flag['errMessage'], 0, ( ($len = mb_strlen($flag['errMessage']))>200 ? 200: $len) );
                $this->addLazadaLog($order['seller_account_id'],LazadaLog::STATUS_FAILURE,$errLogMessage);
            }            

            //根据执行是否出错，确认提交还是回滚
            if ($flag['errCode'] == 'success') {
                UebModel::model('LazadaOrderMain')->updateByPlatformOrderID($platformOrderID, array('is_to_oms' => LazadaOrderMain::TO_OMS_YES,'to_oms_time' => date('Y-m-d H:i:s')));//标记已同步成功  
                $dbTransaction->commit();
            } else {
                $dbTransaction->rollback();
                UebModel::model('LazadaOrderMain')->updateByPlatformOrderID($platformOrderID, array('is_to_oms' => LazadaOrderMain::TO_OMS_NO,'to_oms_time' => date('Y-m-d H:i:s')));//标记未同步
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
     * [addLazadaLog description]
     * @param [type] $accountID [description]
     * @param [type] $status    [description]
     * @param [type] $message   [description]
     * @param [type] $eventName [description]
     */
    public function addLazadaLog($accountID,$status,$message,$eventName=LazadaLog::EVENT_SYNCORDER) {
        $logModel = new LazadaLog();
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
    
    // =========== start: 2017-02-10add search ==================
        
    /**
     * Declares attribute labels.
     * @return array
     */
    public function attributeLabels() {
        return array(
                'id'                => Yii::t('system', 'No.'),
                'platform_order_id' => '平台订单号',
                'order_co_id'       => 'CO订单号',
                'created_at'        => '交易时间',
                'updated_at'        => '更新时间',
                'order_status'      => '订单状态',
                'currency'          => '交易币种',
                'price'             => '交易金额'
        );
    }
    
    public function addtions($datas){
        if(empty($datas)) return $datas;
        foreach ($datas as &$data){
            $platformOrderID = $data['platform_order_id'];
            $localNum = strrpos($platformOrderID,'-');
            if($localNum){
                $platformOrderID = substr($platformOrderID, 0, $localNum);
            }
            $wheres = "platform_code = '".Platform::CODE_LAZADA."' AND platform_order_id LIKE '".$platformOrderID."-%'";
            $orders = Order::model()->getOneByCondition('order_id',$wheres);
            //订单号
            $data['order_co_id'] = isset($orders['order_id'])?$orders['order_id']:'';
        }
        return $datas;
    }   
    
    /**
     * get search info
     */
    public function search() {
        $sort = new CSort();
        $sort->attributes = array(
                'defaultOrder'  => 'id',
        );
        $cdbCriteria = $this->setCdbCriteria();
        $dataProvider = parent::search(get_class($this), $sort, '', $cdbCriteria);
        $data = $this->addtions($dataProvider->data);
        $dataProvider->setData($data);
        return $dataProvider;
    }

    public function setCdbCriteria(){
        $cdbcriteria = new CDbCriteria();
        $cdbcriteria->select = '*';
        $cdbcriteria->addCondition("payment_method = 'CashOnDelivery'");
        return $cdbcriteria;
    }
    
    /**
     * filter search options
     * @return type
     */
    public function filterOptions() {
        $type = Yii::app()->request->getParam('type');
        $status = Yii::app()->request->getParam('status');
        $isRestore = Yii::app()->request->getParam('is_restore');
        $result = array(
            array(
                'name'          => 'updated_at',
                'type'          => 'text',
                'search'        => 'RANGE',
                'htmlOptions'   => array(
                        'class'    => 'date',
                        'dateFmt'  => 'yyyy-MM-dd HH:mm:ss',
                ),
            ),
        );
        return $result;
    }    
    // =========== end: search ==================
    
}