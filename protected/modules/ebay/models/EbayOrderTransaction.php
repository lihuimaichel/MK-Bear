<?php

/**
 * @desc Ebay订单交易表
 * @author yangsh
 * @since 2016-06-08
 */
class EbayOrderTransaction extends EbayModel {

    /** @var 检查多少天未付款的订单 */
    const CHECK_NOPAY_DAYS = 60;

    protected $_ExceptionMsg = null;

    /** @var mixed 交易id */
    protected $_TransactionID  = null;    

    /** @var string paypal账号 */
    protected $_PaypalAccount  = null;  

    /** @var int 平台订单id */
    protected $_PlatformOrderID  = null;   

    /** @var int 账号id */
    protected $_AccountID  = null;

    /** @var int 支付类型 */
    protected $_PaymentType  = null;

	public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_ebay_order_transaction';
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
     * 设置交易ID
     * @param mixed $transactionID
     */
    public function setTransactionID($transactionID) {
        $this->_TransactionID = $transactionID;
        return $this;
    }

    /**
     * 设置paypal账号
     * @param string $paypalAccount
     */
    public function setPaypalAccount($paypalAccount) {
        $this->_PaypalAccount = $paypalAccount;
        return $this;
    }        

    /**
     * 设置平台订单id
     * @param mixed $platformOrderID 
     */
    public function setPlatformOrderID($platformOrderID) {
        $this->_PlatformOrderID = $platformOrderID;
        return $this;
    }

    /**
     * 设置账号ID
     * @param int $accountID
     */
    public function setAccountID($accountID) {
        $this->_AccountID = $accountID;
        return $this;
    } 

    public function setPaymentType($paymentType) {
        $this->_PaymentType = $paymentType;
        return $this;
    }

    /**
     * 获取paypal手续费
     * @param  string $platformOrderID 
     * @return float
     */
    public function getOrderFeeAmt($platformOrderID) {
        return $this->dbConnection->createCommand()
                    ->select("SUM(fee_amt) as fee_amt")
                    ->from(self::tableName())
                    ->where("receive_type=1 and is_delete=0 and platform_order_id=:order_id",array(':order_id'=>$platformOrderID) )
                    ->queryScalar();
    }

    /**
     * [getOneByCondition description]
     * @param  string $fields [description]
     * @param  string $where  [description]
     * @param  mixed $order  [description]
     * @return [type]         [description]
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
     * @return [type]         [description]
     * @author yangsh
     */
    public function getListByCondition($fields='*',$where='1',$order='',$limit='') {
        $cmd = $this->dbConnection->createCommand();
        $cmd->select($fields)
            ->from(self::tableName())
            ->where($where);
        $order != '' && $cmd->order($order);
        $limit != '' && $cmd->limit($limit);
        return $cmd->queryAll();
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
     * saveOrderTransaction 
     * @param  object $order
     * @return boolean
     */
    public function saveOrderTransaction ($order) {
        try {        
            $accountID              = $this->_AccountID;
            $paypalAccount          = $this->_PaypalAccount;
            $platformOrderID        = trim($order->OrderID);
            $nowTime                = date('Y-m-d H:i:s');
            $externalTransactions   = isset($order->ExternalTransaction) ? $order->ExternalTransaction : null;
            unset($order);
            $this->deleteAll("platform_order_id='{$platformOrderID}'");//delete exist rows
            if (empty($externalTransactions)) {
                return true;
            }
            $transactions           = array();
            foreach ($externalTransactions as $externalTransaction) {
                $transactionID      = trim ( $externalTransaction->ExternalTransactionID );
                $this->deleteAll("transaction_id='{$transactionID}'");//delete exist rows
                $transactionTime    = trim( $externalTransaction->ExternalTransactionTime );
                $transactions [ strtotime( $transactionTime ) ] = $externalTransaction;
            }
            unset( $externalTransactions );
            ksort( $transactions ); // 按交易时间先后排序
            // 先保存交易id，随后在paypal抓取收付款信息
            foreach ($transactions as $externalTransaction) { 
                $transactionTime     = date ('Y-m-d H:i:s',strtotime(trim($externalTransaction->ExternalTransactionTime)) - 8* 3600);
                $transactionID       = trim ( $externalTransaction->ExternalTransactionID );
                $transactionStatus   = trim($externalTransaction->ExternalTransactionStatus);
                $amt                 = floatval ( $externalTransaction->PaymentOrRefundAmount );  
                if ( $amt == 0 ) {
                    continue;
                }        
                $receiveType         = $amt > 0 ? 1 : 2;//默认将收款作为首次交易
                $checkTransaction    = $this->getOneByCondition('id,is_first_transaction',"transaction_id='{$transactionID}'");
                if ( empty($checkTransaction) ) {
                    $isFirst        =  $amt > 0 ? 1 : 0;
                } else {
                    $isFirst        =  $checkTransaction['is_first_transaction'];
                }
                $insertData = array(
                    'transaction_id'            => $transactionID,
                    'platform_order_id'         => $platformOrderID,
                    'seller_account_id'         => $accountID,
                    'paypal_account'            => $paypalAccount,
                    'receive_type'              => $receiveType,
                    'is_first_transaction'      => $isFirst,
                    'transaction_time'          => $transactionTime,
                    'created_at'                => $nowTime,
                );
                $this->addNewData($insertData);
            }
            return true;     
        } catch (Exception $e) {
            $this->setExceptionMessage($e->getMessage());
            return false;
        }
    }   

    /**
     * 批量更新修改时间
     * @param  array $transactionIDArr 
     * @return boolean               
     */
    public function updateModifyTime($transactionIdArr,$modifyTime=null) {
        if (is_null($modifyTime)) {
            $modifyTime = date("Y-m-d H:i:s") ;
        }
        return $this->dbConnection->createCommand()->update($this->tableName(), array('modify_time'=>$modifyTime), array('in','transaction_id',$transactionIdArr)  );
    }   

    /**
     * 检查下载超时情况
     */
    public function checkNoPaypalModifyTime($accountID = null) {
        $default_modify_time = '0000-00-00 00:00:00';
        $modify_time = date("Y-m-d H:i:s",time() - 1 * 60 );//2分钟超时
        $where = 'amt=0 AND is_delete=0 AND modify_time<\''.$modify_time.'\' AND modify_time>\''.$default_modify_time.'\' AND (created_at BETWEEN SUBDATE(NOW(),' . self::CHECK_NOPAY_DAYS . ') AND NOW() ) ';
        if (!empty($accountID)) {
            $where .= " AND seller_account_id='{$accountID}' ";
        }

        $fields = 'transaction_id';
        $res = $this->getListByCondition($fields,$where);
        if (empty($res)) {
            return '';
        }
        $transactionIdArr = array();
        foreach ($res as $v) {
            $transactionIdArr[] = $v['transaction_id'];
        }
        $groupData = MHelper::getGroupData($transactionIdArr, 100);
        foreach ($groupData as $transIdArr) {
            $this->updateModifyTime($transIdArr,$default_modify_time);
        }
        return true;
    }

    /**
     * 获取待下载paypal交易记录 
     * @return array
     */
    public function getNoPaypalTransactions($limit = '') {
        $default_modify_time = '0000-00-00 00:00:00';
        $where = 'amt=0 AND is_delete=0 AND modify_time=\''.$default_modify_time.'\' AND (created_at BETWEEN SUBDATE(NOW(),' . self::CHECK_NOPAY_DAYS . ') AND NOW() ) ';

        if (!empty($this->_TransactionID)) {
            $where .= is_array($this->_TransactionID) ? " AND transaction_id IN('".implode("','",$this->_TransactionID)."')" : " AND transaction_id='{$this->_TransactionID}'";
        }
        if (!empty($this->_PaypalAccount)) {
            $where .= " AND paypal_account='{$this->_PaypalAccount}'";
        }
        if (!empty($this->_PlatformOrderID)) {
            $where .= is_array($this->_PlatformOrderID) ? " AND platform_order_id IN('".implode("','",$this->_PlatformOrderID)."')" : " AND platform_order_id='{$this->_PlatformOrderID}'";
        }
        if (!empty($this->_AccountID)) {
            $where .= " AND seller_account_id='{$this->_AccountID}'";
        }
        $fields = 'transaction_id,paypal_account,platform_order_id,seller_account_id,receive_type,is_first_transaction';
        $order = 'transaction_time asc';
        return $this->getListByCondition($fields,$where,$order,$limit);
    }

    /**
     * 获取待下载paypal交易记录 
     * @return array
     */
    public function getNoPaypalTransactionsOfEcheck($limit = '') {
        $where = 'amt>0 AND payment_status=\'Pending\' AND is_delete=0 AND (created_at BETWEEN SUBDATE(NOW(),' . self::CHECK_NOPAY_DAYS . ') AND NOW() ) ';//payment_type=\'echeck\' AND

        if (!empty($this->_TransactionID)) {
            $where .= is_array($this->_TransactionID) ? " AND transaction_id IN('".implode("','",$this->_TransactionID)."')" : " AND transaction_id='{$this->_TransactionID}'";
        }
        if (!empty($this->_PaypalAccount)) {
            $where .= " AND paypal_account='{$this->_PaypalAccount}'";
        }
        if (!empty($this->_PlatformOrderID)) {
            $where .= is_array($this->_PlatformOrderID) ? " AND platform_order_id IN('".implode("','",$this->_PlatformOrderID)."')" : " AND platform_order_id='{$this->_PlatformOrderID}'";
        }
        if (!empty($this->_AccountID)) {
            $where .= " AND seller_account_id='{$this->_AccountID}'";
        }
        $fields = 'transaction_id,paypal_account,platform_order_id,seller_account_id,receive_type,is_first_transaction';
        $order = 'transaction_time asc';
        return $this->getListByCondition($fields,$where,$order,$limit);
    }       

    /**
     * 获取待抓取交易销售账号id
     */
    public function getNoPaypalsSellerAccountByOrder() {
        $default_modify_time = '0000-00-00 00:00:00';
        $where = 'amt=0 AND is_delete=0 AND modify_time=\''.$default_modify_time.'\' AND (created_at BETWEEN SUBDATE(NOW(),' . self::CHECK_NOPAY_DAYS . ') AND NOW() ) ';
        $fields = "seller_account_id,count(seller_account_id) as num ";
        return $this->dbConnection->createCommand()
                    ->select($fields)
                    ->from(self::tableName())
                    ->where($where)
                    ->group("seller_account_id")
                    ->order("num desc")
                    ->queryAll();
    }

    /**
     * 获取待抓取交易销售账号id
     */
    public function getNoPaypalsSellerAccounts() {
        $default_modify_time = '0000-00-00 00:00:00';
        $where = 'amt=0 AND is_delete=0 AND modify_time=\''.$default_modify_time.'\' AND (created_at BETWEEN SUBDATE(NOW(),' . self::CHECK_NOPAY_DAYS . ') AND NOW() ) ';
        return $this->dbConnection->createCommand()
                    ->select("seller_account_id")
                    ->from(self::tableName())
                    ->where($where)
                    ->queryColumn();
    }    

    /**
     * 获取待抓取交易销售账号id
     */
    public function getNoPaypalsSellerAccount() {
        $default_modify_time = '0000-00-00 00:00:00';
        $where = 'amt=0 AND is_delete=0 AND modify_time=\''.$default_modify_time.'\' AND (created_at BETWEEN SUBDATE(NOW(),' . self::CHECK_NOPAY_DAYS . ') AND NOW() ) ';
        $fields = "seller_account_id ";
        return $this->dbConnection->createCommand()
                    ->select($fields)
                    ->from(self::tableName())
                    ->where($where)
                    ->queryColumn();
    }    

    /**
     * savePaypalTransactionRecord 
     * @param  array $info
     * @author yangsh
     * @since  2016-06-30
     */
    public function savePaypalTransactionRecord($info) {
        $transactionID      = $info['transaction_id'];
        $paypalAccount      = $info['paypal_account'];
        $order_info         = EbayOrderMain::model ()->getOneByCondition('*', "platform_order_id ='{$info['platform_order_id']}'");
        if ($transactionID == '' || $paypalAccount == '' || empty($order_info) ) {
            return null;
        }

        // 查找paypal交易信息
        $paypalTransaction  = PaypalAccount::model ()->getPaypalTransactionByCondition( $info ['transaction_id'], Platform::CODE_EBAY, $paypalAccount ); 
        if (!empty($_REQUEST['debug'])) {
            MHelper::printvar($paypalTransaction,false);//test
        }

        //如果paypal交易返回结果为Permission denied, 则设置记录无效
        $orderCreateAt = strtotime($order_info['created_at']);
        if (false === $paypalTransaction  && (time() - $orderCreateAt) > 86400  ) {
            echo $transactionID." 为无效记录<br>\r\n ";
            $transData = array('is_delete'=>1, 'modify_time'=> date ( 'Y-m-d H:i:s' ) );
            $this->dbConnection->createCommand()->update(self::tableName(), $transData, 'transaction_id = "'.$transactionID.'"');
            return false;
        }

        if (empty($paypalTransaction) || floatval($paypalTransaction ['AMT']) == 0) {
            return false;
        }
        $dbTransaction      = $this->dbConnection->getCurrentTransaction ();
        if (! $dbTransaction) {
            $dbTransaction  = $this->dbConnection->beginTransaction (); // 开启事务
        }
        try {
            $amt            = floatval($paypalTransaction ['AMT']);
            $isfirst        = $info ['is_first_transaction'];
            $receiveType    = $paypalTransaction ['AMT'] > 0 ? 1 : 2;
            $subTotal       = $order_info['subtotal'];

            $payTime        = strtotime ( $order_info ['paid_time'] ) > 0 ? $order_info ['paid_time'] : (isset ( $paypalTransaction ['ORDERTIME'] ) ? date('Y-m-d H:i:s',strtotime($paypalTransaction ['ORDERTIME'])- 8 * 3600) : '0000-00-00 00:00:00');

            $ori_pay_time   = strtotime ( $order_info ['ori_paid_time'] ) > 0 ? $order_info ['ori_paid_time'] : (isset ( $paypalTransaction ['ORDERTIME'] ) ? date ( 'Y-m-d H:i:s', strtotime ( $paypalTransaction ['ORDERTIME'] ) ) : '0000-00-00 00:00:00');
              
            // 更新订单数据
            if ($order_info ['payment_status'] != EbayOrderMain::PAYMENT_STATUS_END) {
                $ship_street1               = $order_info ['shipping_street1'];
                $ship_street2               = $order_info ['shipping_street2'];
                $ship_zip                   = $order_info ['shipping_postalcode'];
                $ship_city_name             = $order_info ['shipping_city_name'];
                $ship_country               = $order_info ['shipping_country'];
                $ship_country_name          = $order_info ['shipping_country_name'];
                $ship_stateorprovince       = $order_info ['shipping_stateorprovince'];
                
                if(isset ( $paypalTransaction ['SHIPTOSTREET'] ) && $paypalTransaction ['SHIPTOSTREET']!="") {
                    $ship_street1           = trim($paypalTransaction ['SHIPTOSTREET']) ;
                    if ($ship_street1 != '') {
                        $ship_street1 = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $ship_street1);
                    }
                }
                if(isset ( $paypalTransaction ['SHIPTOSTREET2'] ) && $paypalTransaction ['SHIPTOSTREET2']!="") {
                    $ship_street2           = trim($paypalTransaction ['SHIPTOSTREET2']) ;
                    if ($ship_street2 != '') {
                        $ship_street2 = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $ship_street2);
                    }                 
                }
                if(isset ( $paypalTransaction ['SHIPTOZIP'] ) && $paypalTransaction ['SHIPTOZIP']!="") {
                    $ship_zip               = trim($paypalTransaction ['SHIPTOZIP']) ;
                }
                if(isset ( $paypalTransaction ['SHIPTOCITY'] ) && $paypalTransaction ['SHIPTOCITY']!="") {
                    $ship_city_name         = trim($paypalTransaction ['SHIPTOCITY']) ;
                    if ($ship_city_name != '') {
                        $ship_city_name = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $ship_city_name);
                    }                     
                }    
                if(isset ( $paypalTransaction ['SHIPTOCOUNTRYCODE'] ) && $paypalTransaction ['SHIPTOCOUNTRYCODE']!="" && $paypalTransaction ['SHIPTOCOUNTRYCODE'] != 'CS' ) {
                    $ship_country           = trim($paypalTransaction ['SHIPTOCOUNTRYCODE']) ;
                }
                if(isset ( $paypalTransaction ['SHIPTOCOUNTRYNAME'] ) && $paypalTransaction ['SHIPTOCOUNTRYNAME']!="" && $paypalTransaction ['SHIPTOCOUNTRYCODE'] != 'CS' ) {
                    $ship_country_name      = trim($paypalTransaction ['SHIPTOCOUNTRYNAME']);
                }
                if(isset ( $paypalTransaction ['SHIPTOSTATE'] ) && $paypalTransaction ['SHIPTOSTATE']!="" && 'notprovided' != strtolower($paypalTransaction['SHIPTOSTATE']) ) {
                    $ship_stateorprovince   = trim($paypalTransaction ['SHIPTOSTATE']);
                    if ($ship_stateorprovince != '') {
                        $ship_stateorprovince = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $ship_stateorprovince);
                    }             
                }
                $orderColumn = array (
                    'shipping_street1'          => $ship_street1,
                    'shipping_street2'          => $ship_street2,
                    'shipping_postalcode'       => $ship_zip,
                    'shipping_city_name'        => $ship_city_name,
                    'shipping_stateorprovince'  => $ship_stateorprovince,
                    'shipping_country'          => $ship_country,
                    'shipping_country_name'     => $ship_country_name,
                    'paid_time'                 => $payTime,
                    'ori_paid_time'             => $ori_pay_time,
                );
                if ($isfirst > 0 && $amt >0 && isset($paypalTransaction ['PAYMENTSTATUS']) && 'Completed' == $paypalTransaction ['PAYMENTSTATUS'] ) {
                    $orderColumn['payment_status']  = EbayOrderMain::PAYMENT_STATUS_END;
                }
                $this->dbConnection->createCommand()->update(EbayOrderMain::tableName(), $orderColumn, 'platform_order_id = "'.$order_info ['platform_order_id'].'"');
            }

            // 保存交易信息  
            $payerName      = isset ( $paypalTransaction ['FIRSTNAME'] ) ? trim($paypalTransaction ['FIRSTNAME']) : '';
            isset ( $paypalTransaction ['MIDDLENAME'] ) && $payerName .= ' ' . trim($paypalTransaction ['MIDDLENAME']);
            isset ( $paypalTransaction ['LASTNAME'] ) && $payerName .= ' ' . trim($paypalTransaction ['LASTNAME']);
            if ($payerName != '') {
                $payerName = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $payerName);
            }

            //如果以下这些字段为空，则amt为0， 表示需要重新拉取paypal信息
            $receiverEmail  = isset ( $paypalTransaction ['RECEIVEREMAIL'] ) ? $paypalTransaction ['RECEIVEREMAIL'] : '';
            $receiverID     = isset ( $paypalTransaction ['RECEIVERID'] ) ? $paypalTransaction ['RECEIVERID'] : '';
            $payerID        = isset ( $paypalTransaction ['PAYERID'] ) ? $paypalTransaction ['PAYERID'] : '';
            $payerEmail     = isset ( $paypalTransaction ['EMAIL'] ) ? $paypalTransaction ['EMAIL'] : '';
            $paymentStatus  = isset ( $paypalTransaction ['PAYMENTSTATUS'] ) ? $paypalTransaction ['PAYMENTSTATUS'] : '';
            $paymentType    = isset ( $paypalTransaction ['PAYMENTTYPE'] ) ? $paypalTransaction ['PAYMENTTYPE'] : '';
            
            //amt
            $amt            = isset ( $paypalTransaction ['AMT'] ) ? $paypalTransaction ['AMT'] : 0;
            if ($receiverEmail == '' || $receiverID == '' || $payerEmail == '' || $payerID == '' 
                || $paymentStatus == '' || $paymentType == '') {
                $amt = 0;
            }

            $transNote = isset ( $paypalTransaction ['NOTE'] ) ? $paypalTransaction ['NOTE'] : '';
            if ($transNote != '') {//mysql编码格式utf-8格式，不支持带四字节的字符串插入
                $transNote = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $transNote);
            }   

            $buyerId = isset ( $paypalTransaction ['BUYERID'] ) ? $paypalTransaction ['BUYERID'] :'';
            if ($buyerId != '' && mb_strlen($buyerId) > 50 ) {
                $buyerId = mb_substr($buyerId,0,50);
            }

            $transData = array (
                'paypal_account'        => isset ( $paypalTransaction ['paypal_account'] ) ? $paypalTransaction ['paypal_account'] : '',
                'receive_type'          => $receiveType,
                'is_first_transaction'  => $isfirst,
                'parent_transaction_id' => isset ( $paypalTransaction ['PARENTTRANSACTIONID'] ) ? $paypalTransaction ['PARENTTRANSACTIONID'] : '',
                'paypal_account_id'     => isset ( $paypalTransaction ['paypal_account_id'] ) ? $paypalTransaction ['paypal_account_id'] : 0,
                'receiver_business'     => isset ( $paypalTransaction ['RECEIVERBUSINESS'] ) ? $paypalTransaction ['RECEIVERBUSINESS'] : '',
                'receiver_email'        => $receiverEmail,
                'receiver_id'           => $receiverID,
                'payer_id'              => $payerID,
                'payer_name'            => $payerName,
                'payer_email'           => $payerEmail,
                'payer_country_code'    => isset ( $paypalTransaction ['COUNTRYCODE'] ) ? $paypalTransaction ['COUNTRYCODE'] : '',
                'payer_address_status'  => isset ( $paypalTransaction ['ADDRESSSTATUS'] ) ? $paypalTransaction ['ADDRESSSTATUS'] : '',
                'payer_status'          => isset ( $paypalTransaction ['PAYERSTATUS'] ) ? $paypalTransaction ['PAYERSTATUS'] :'',                    
                'buyer_id'              => $buyerId,      
                'transaction_type'      => isset ( $paypalTransaction ['TRANSACTIONTYPE'] ) ? $paypalTransaction ['TRANSACTIONTYPE'] : '',
                'payment_type'          => $paymentType,
                'payment_status'        => $paymentStatus,
                'fee_amt'               => isset ( $paypalTransaction ['FEEAMT'] ) ? $paypalTransaction ['FEEAMT'] : 0,
                'amt'                   => $amt,
                'sub_amt'               => $subTotal,
                'tax_amt'               => isset ( $paypalTransaction ['TAXAMT'] ) ? $paypalTransaction ['TAXAMT'] : 0,
                'shipping_amt'          => isset ( $paypalTransaction ['SHIPPINGAMT'] ) ? $paypalTransaction ['SHIPPINGAMT'] : 0,
                'handling_amt'          => isset ( $paypalTransaction ['HANDLINGAMT'] ) ? $paypalTransaction ['HANDLINGAMT'] : 0,
                'insurance_amount'      => isset ( $paypalTransaction ['INSURANCEAMOUNT'] ) ? $paypalTransaction ['INSURANCEAMOUNT'] : 0,
                'currency'              => isset ( $paypalTransaction ['CURRENCYCODE'] ) ? $paypalTransaction ['CURRENCYCODE'] : '',
                'pay_time'              => $payTime,
                'ori_pay_time'          => $ori_pay_time,
                'modify_time'           => date ( 'Y-m-d H:i:s' ) ,
                'note'                  => $transNote,
            );
            $this->dbConnection->createCommand()->update(self::tableName(), $transData, 'transaction_id = "'.$transactionID.'"');
            $dbTransaction->commit ();
            return true;
        } catch ( Exception $e ) {
            $dbTransaction->rollback ();
            $this->setExceptionMessage ( $e->getMessage () );
            return false;
        }
    } 

    /**
     * [getFormatedOrderTransactions description]
     * @param  array $orderTransactions 
     * @return array
     */
    public static function getFormatedOrderTransactions($orderTransactions) {
        foreach ($orderTransactions as $key => $transaction) {
            //1.格式化前数据验证
            if ($transaction['amt'] == 0 || $transaction['currency'] == '' || $transaction['paypal_account_id'] == 0 ) {
                return false;
            }
            //2.组装OMS需要的数据格式        
            $row = array(
                'transaction_id'        => $transaction['transaction_id'],
                'account_id'            => $transaction['paypal_account_id'],
                'receive_type'          => $transaction['receive_type'],
                'first'                 => $transaction['is_first_transaction'],
                'is_first_transaction'  => $transaction['is_first_transaction'],
                'parent_transaction_id' => $transaction['parent_transaction_id'],
                'order_pay_time'        => $transaction['pay_time'],//UTC
                'amt'                   => $transaction['amt'],
                'fee_amt'               => $transaction['fee_amt'],
                'currency'              => $transaction['currency'],
                'payment_status'        => $transaction['payment_status'],              
            ); 
            $rtn[] = $row;
            //如果有退款，查找关联的收款记录
            if($transaction['receive_type'] == 2 && $transaction['parent_transaction_id'] != '') {
                $trans = UebModel::model('EbayOrderTransaction')->getOneByCondition('*',"transaction_id='{$transaction['parent_transaction_id']}' and receive_type=1 ");
                if($trans) {
                    if ($trans['amt'] == 0 || $trans['currency'] == '' || $trans['paypal_account_id'] == 0 ) {
                        return false;
                    }                    
                    $row = array(
                        'transaction_id'        => $trans['transaction_id'],
                        'account_id'            => $trans['paypal_account_id'],
                        'receive_type'          => $trans['receive_type'],
                        'first'                 => $trans['is_first_transaction'],
                        'is_first_transaction'  => $trans['is_first_transaction'],
                        'parent_transaction_id' => $trans['parent_transaction_id'],
                        'order_pay_time'        => $trans['pay_time'],//UTC
                        'amt'                   => $trans['amt'],
                        'fee_amt'               => $trans['fee_amt'],
                        'currency'              => $trans['currency'],
                        'payment_status'        => $trans['payment_status'],              
                    ); 
                    $rtn[] = $row;               
                }
            }
        }
        return $rtn;
    }

    /**
     * [getFormatedPaypalTransactions description]
     * @param  array $orderTransactions 
     * @return array
     */
    public static function getFormatedPaypalTransactions($orderTransactions) {
        foreach ($orderTransactions as $key => $transaction) {
            //1.格式化前数据验证
            if ($transaction['amt'] == 0 || $transaction['currency'] == '' || $transaction['receiver_email'] == '' 
             || $transaction['payer_email'] == '' || $transaction['transaction_type'] == '') {
                return false;
            }

            if ($transaction['receive_type'] == 1) {//收款
                $receiverBusiness = $transaction['receiver_business'];
                $receiverEmail    = $transaction['receiver_email']; 
                $receiverId       = $transaction['receiver_id']; 
                $payerId          = $transaction['payer_id']; 
                $payerEmail       = $transaction['payer_email']; 
                $payerName        = $transaction['payer_name'];
                $payerStatus      = $transaction['payer_status'];
            } else {//退款
                $receiverBusiness = ''; 
                $receiverEmail    = $transaction['payer_email']; 
                $receiverId       = $transaction['payer_id']; 
                $payerId          = $transaction['receiver_id']; 
                $payerEmail       = $transaction['receiver_email']; 
                $payerName        = 'vakind';
                $payerStatus      = 'verified';
            }

            //2.组装OMS需要的数据格式        
            $row = array(
                'transaction_id'        => $transaction['transaction_id'],
                'receive_type'          => $transaction['receive_type'],
                'receiver_business'     => $receiverBusiness, 
                'receiver_email'        => $receiverEmail, 
                'receiver_id'           => $receiverId, 
                'payer_id'              => $payerId, 
                'payer_email'           => $payerEmail, 
                'payer_name'            => $payerName, 
                'payer_status'          => $payerStatus,     
                'parent_transaction_id' => $transaction['parent_transaction_id'],
                'transaction_type'      => $transaction['transaction_type'],
                'payment_type'          => $transaction['payment_type'],
                'order_time'            => $transaction['pay_time'],//UTC
                'amt'                   => $transaction['amt'],
                'fee_amt'               => $transaction['fee_amt'],
                'tax_amt'               => $transaction['tax_amt'],
                'currency'              => $transaction['currency'],                
                'payment_status'        => $transaction['payment_status'], 
                'note'                  => $transaction['note'],   
                'modify_time'           => $transaction['modify_time']
            ); 
            $rtn[] = $row;
            //如果有退款，查找关联的收款记录
            if($transaction['receive_type'] == 2 && $transaction['parent_transaction_id'] != '') {
                $trans = UebModel::model('EbayOrderTransaction')->getOneByCondition('*',"transaction_id='{$transaction['parent_transaction_id']}' and receive_type=1 ");
                if($trans) {
                    if ($trans['amt'] == 0 || $trans['currency'] == '' || $trans['receiver_email'] == '' 
                     || $trans['payer_email'] == '' || $trans['transaction_type'] == '') {
                        return false;
                    }                    
                    $row = array(
                        'transaction_id'        => $trans['transaction_id'],
                        'receive_type'          => $trans['receive_type'],
                        'receiver_business'     => $trans['receiver_business'], 
                        'receiver_email'        => $trans['receiver_email'], 
                        'receiver_id'           => $trans['receiver_id'], 
                        'payer_id'              => $trans['payer_id'], 
                        'payer_email'           => $trans['payer_email'], 
                        'payer_name'            => $trans['payer_name'], 
                        'payer_status'          => $trans['payer_status'],     
                        'parent_transaction_id' => $trans['parent_transaction_id'],
                        'transaction_type'      => $trans['transaction_type'],
                        'payment_type'          => $trans['payment_type'],
                        'order_time'            => $trans['pay_time'],//UTC
                        'amt'                   => $trans['amt'],
                        'fee_amt'               => $trans['fee_amt'],
                        'tax_amt'               => $trans['tax_amt'],
                        'currency'              => $trans['currency'],                
                        'payment_status'        => $trans['payment_status'], 
                        'note'                  => $trans['note'],   
                        'modify_time'           => $trans['modify_time']
                    ); 
                    $rtn[] = $row;                                 
                }
            }     
        }
        return $rtn;
    }

}