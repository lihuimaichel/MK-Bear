<?php
/**
 * @desc EbayPaypalTransaction
 * @author yangsh
 * @since 2017-01-18
 */
class EbayPaypalTransaction extends EbayModel{

    protected $_paypalTransactionInfo = null;

    protected $_paypalAccountID = null;

    protected $_transactionClass = null;

    protected $_transactionStatus = null;

    protected $_transactionID = null;

    protected $_transactionTime = array();

    protected $_paypalAccount = null;

    protected $_interval = 60;//1分钟

    protected $_paypalAccountIDArr;

    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
        
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_ebay_paypal_transaction';
    }

    /**
     * getOneByCondition
     * @param  string $fields
     * @param  string $where 
     * @param  mixed $order  
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
     * @param  mixed $order  
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

    public function insert($data) {
        $isOk = $this->dbConnection->createCommand()
                ->insert($this->tableName(),$data);
        if ($isOk) {
            return $this->dbConnection->getLastInsertID();
        }
        return false;
    }

    public function update($data,$transactionID) {
        return $this->dbConnection->createCommand()
                ->update($this->tableName(),$data,"transaction_id='{$transactionID}'");
    }

    public function save($data) {
        if (!isset($data['transaction_id'])) {
            return false;
        }        
        $info = $this->getOneByCondition("id","transaction_id='{$data['transaction_id']}'");
        $data['update_time'] = date("Y-m-d H:i:s");
        if (empty($info)) {
            $data['create_time'] = date("Y-m-d H:i:s");
            return $this->insert($data);
        } else {
            return $this->update($data,$data['transaction_id']);
        }  
    }

    /**
     * 按交易号搜索
     * @return boolean
     */
    public function searchByTransaction() {
        if (empty($this->_paypalAccountID)) {
            $detail = EbayPaypalTransaction::model()->getOneByCondition("paypal_account_id","transaction_id='{$this->_transactionID}'");
            if (empty($detail)) {
                die("detail is empty");
            }
            $this->setPaypalAccountID($detail['paypal_account_id']);
        }
        $start_date = date('Y-m-d\TH:i:s\Z', $this->_transactionTime[0] );
        $end_date   = date('Y-m-d\TH:i:s\Z',$this->_transactionTime[1] );
        echo $start_date.' -- '. $end_date."<br>";
        $request = new TransactionSearchRequest;
        $request->setAccount($this->_paypalAccountID);
        $request->setStartDate($start_date);
        $request->setEndDate($end_date);
        $request->setTransactionClass($this->_transactionClass);
        $request->setTransactionId($this->_transactionID);          
        $request->setStatus("Success");
        $request->setRequest();
        $response = $request->sendRequest()->getResponse();
        echo "<pre>";print_r($response);
        if (!$request->getIfSuccess()) {
            return false;
        }
        return $this->savePaypalTransactions($response);
    }

    /**
     * 查交易记录
     * @return boolean
     */
    public function startSearchTransaction() {
        $startTime = $this->_transactionTime[0];
        $endTime   = $this->_transactionTime[1];
        $times     = ceil(($endTime-$startTime)/$this->_interval);
        //echo 'times:'.$times."<br>";
        $flag = true;
        while ($times>0) {
            try {
                $start_date = date('Y-m-d\TH:i:s\Z',$endTime - $times*$this->_interval - 60 );
                $end_date   = date('Y-m-d\TH:i:s\Z',$endTime - ($times-1)*$this->_interval );
                //echo $start_date.' -- '. $end_date."<br>";
                $request = new TransactionSearchRequest;
                $request->setAccount($this->_paypalAccountID);
                $request->setStartDate($start_date);
                $request->setEndDate($end_date);
                $request->setTransactionClass($this->_transactionClass);
                $request->setStatus($this->_transactionStatus);
                $request->setRequest();
                $response = $request->sendRequest()->getResponse();
                $times --;
                //echo "<pre>";print_r($response);
                if (empty($response) || !$request->getIfSuccess()) {
                    throw new Exception("Request Error");
                }
                $this->savePaypalTransactions($response);
            } catch (Exception $e) {
                $flag = false;
                echo "failure timeArr:<br>";
                echo "<pre>";print_r($this->_transactionTime);
                continue;
            }

        }
        return $flag;
    }

    /**
     * 保存交易记录
     * @param  array $response 
     * @return boolean   
     */
    public function savePaypalTransactions($response) {
        $fieldMap = self::getFieldMap();
        $transCount = 0;//交易数量
        foreach ($response as $k => $v) {
            if (preg_match("/^L_TRANSACTIONID\d+$/", $k)) {
                $transCount++;
            }
        }
        if ($transCount == 0 ) {
            return true;
        }

        if ( $transCount >= 100 ) {//fortest
            MHelper::writefilelog('ebaypaypaltrans100.txt',$this->_paypalAccountID. ' ## '. date("Y-m-d H:i:s") .' @@@ '.print_r($this->_transactionTime,true)."\r\n\r\n");
        }
        
        $flag = true;
        for ($i=0; $i < $transCount; $i++) { 
            $dbTransaction = $this->dbConnection->beginTransaction ();// 开启事务
            try {
                $transDetail = array();
                $trans = array();
                $trans['paypal_account_id'] = $this->_paypalAccountID;
                $trans['paypal_account'] = $this->_paypalAccount;
                foreach ($fieldMap as $key => $field) {
                    if (isset($response[$key.$i])) {
                        if ($field == 'transaction_time') {
                            $trans[$field] = self::transferUTCTimeFormat($response[$key.$i]);
                            $transDetail['pay_time'] = $trans[$field];
                            $transDetail['ori_pay_time'] = date("Y-m-d H:i:s", strtotime($trans[$field]));
                        } else if ($field == 'customer_name') {
                            $trans[$field] =  preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $response[$key.$i]);
                        } else {
                            $trans[$field] = $response[$key.$i];
                        }
                    }
                }
                $transactionID = $trans['transaction_id'];
                $isOk = $this->save($trans);
                //同时保存交易明细表
                if ($isOk) {
                    $transDetail['transaction_id']    = $trans['transaction_id'];
                    $transDetail['paypal_account_id'] = $trans['paypal_account_id'];
                    $transDetail['paypal_account']    = $trans['paypal_account'];
                    $transDetail['fee_amt']           = isset($trans['fee_amt'])?$trans['fee_amt']:0;                    
                    $info2 = EbayPaypalTransactionDetail::model()->getOneByCondition("transaction_id","transaction_id='{$transDetail['transaction_id']}'");
                    //只插入退款记录
                    if (empty($info2) && in_array($trans['transaction_type'],array('Refund','Reversal') ) ) {
                        $transDetail['create_time'] = date('Y-m-d H:i:s');
                        EbayPaypalTransactionDetail::model()->insert($transDetail);
                    }
                }
                $dbTransaction->commit();
            } catch (Exception $e) {
                $flag = false;
                $dbTransaction->rollback();
                //记录异常日志
                $virtualAccountId = 80000 + $this->_paypalAccountID;
                $errMsg = $transactionID." ## ".$e->getMessage();
                echo $errMsg."<br>";
                $message = mb_strlen($errMsg)>500 ? mb_substr($errMsg,0,500) : $errMsg;
                $this->addEbayLog($virtualAccountId,EbayLog::STATUS_FAILURE,$message,EbayLog::EVENT_TRANSACTIONSEARCH);
            }
        }
        return $flag;
    }

    public static function getFieldMap() {
        return array(
            'L_TRANSACTIONID' => 'transaction_id',
            'L_TIMESTAMP'     => 'transaction_time',
            'L_TIMEZONE'      => 'transaction_timezone',
            'L_TYPE'          => 'transaction_type',
            'L_STATUS'        => 'transaction_status',
            'L_EMAIL'         => 'customer_email',
            'L_NAME'          => 'customer_name',
            'L_AMT'           => 'amt',
            'L_FEEAMT'        => 'fee_amt',
            'L_NETAMT'        => 'net_amt',
            'L_CURRENCYCODE'  => 'currency',
        );
    }

    /**
     * @desc UTC时间格式转换
     * @param unknown $UTCTime
     * @return mixed
     */
    public static function transferUTCTimeFormat($UTCTime){
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
    public static function transferToLocal($UTCTime){
        return date("Y-m-d H:i:s", strtotime($UTCTime)+8*3600);
    }    

    public function setInterval($interval) {
        $this->_interval = $interval;
    }

    public function setPaypalAccountID($payAccountId) {
        $this->_paypalAccountID = $payAccountId;
        if ($payAccountId) {
            $paypalInfo = PaypalAccount::model()->getOneByCondition("*","id={$payAccountId}");
            $this->_paypalAccount = $paypalInfo['email'];
            $this->_paypalAccountID = $paypalInfo['id'];
        }
    }

    public function setPaypalAccountIDArr($paypalAccountIDArr) {
        $this->_paypalAccountIDArr = $paypalAccountIDArr;
    }

    public function setTransactionClass($transactionClass) {
        $this->_transactionClass = $transactionClass;
    }

    public function setTransactionTime(array $timeArr) {
        $this->_transactionTime = $timeArr;
    }

    public function setTransactionStatus($transactionStatus) {
        $this->_transactionStatus = $transactionStatus;
    }

    public function setTransactionID($transactionID) {
        $this->_transactionID = $transactionID;
    }

    /**
     * 同步paypal退款交易记录到OMS系统
     * @return boolean
     */
    public function syncTransactions() {
        $syncNum = 0;
        $where = "m.is_to_oms=0 and d.is_delete=0 and m.transaction_type IN('Refund','Reversal') and m.transaction_status='Completed'";
        if (!empty($this->_paypalAccountIDArr) && is_array($this->_paypalAccountIDArr)) {
            $where .= " and m.paypal_account_id IN(".implode(',',$this->_paypalAccountIDArr).")";
        }
        if (!empty($this->_transactionTime[0]) && !empty($this->_transactionTime[1])) { 
            $where .= " and m.transaction_time>'{$this->_transactionTime[0]}'";
            $where .= " and m.transaction_time<'{$this->_transactionTime[1]}'";
        } else {
            $where .= " and m.transaction_time>'". date("Y-m-d H:i:s",strtotime("-60 days")) . "' ";
        }
        //指定交易号
        if ($this->_transactionID) {
            $where .= " and m.transaction_id='{$this->_transactionID}'";
        }
        echo $where;
        $res = $this->dbConnection->createCommand()
                    ->select("count(*) as total")
                    ->from($this->tableName().' as m')
                    ->join(EbayPaypalTransactionDetail::tableName().' as d', "m.transaction_id=d.transaction_id")
                    ->where($where)
                    ->queryRow();
        if (empty($res) || $res['total'] == 0) {
            return 0;
        }
        $total = $res['total'];
        $pageSize = 1000;
        $pageCount = ceil($total/$pageSize);
        for ($page=1; $page <= $pageCount ; $page++) { 
            $offset = ($page - 1) * $pageSize;
            $list = $cmd = $this->dbConnection->createCommand()
                        ->select("m.*")
                        ->from($this->tableName().' as m')
                        ->join(EbayPaypalTransactionDetail::tableName().' as d', "m.transaction_id=d.transaction_id")
                        ->where($where)
                        ->order("m.id asc")
                        ->limit($pageSize,$offset)
                        ->queryAll();
            if (!empty($list)) {
                foreach ($list as $transactionInfo) {     
                    try {
                        $isOk = $this->saveOrderPaypalTransData($transactionInfo);
                        if($isOk) {
                            $this->update(array('is_to_oms'=>1,'to_oms_time'=>date("Y-m-d H:i:s")),$transactionInfo['transaction_id']);
                        }
                        $syncNum++; 
                    } catch (Exception $e) {
                        $virtualAccountId = 70000;
                        $errMsg = $transactionInfo['transaction_id']." ## ".$e->getMessage();
                        echo $errMsg."<br>";
                        $message = mb_strlen($errMsg)>500 ? mb_substr($errMsg,0,500) : $errMsg;
                        $this->addEbayLog($virtualAccountId,EbayLog::STATUS_FAILURE,$message,EbayLog::EVENT_SYNC_TRANSACTION);
                    }  
                }
            }                         
        }
        return $syncNum;
    }

    /**
     * @desc 保存paypal交易
     * @param  array $transactionInfo 
     * @return boolean
     */
    public function saveOrderPaypalTransData($transactionInfo) {
        $model = new OrderPaypalTransaction();
        $dbTransaction = $model->getDbConnection()->beginTransaction();
        try {
            $this->_paypalTransactionInfo = $transactionInfo;
            $isOk = $this->saveOrderPaypalTransaction();
            if($isOk) {
                $this->saveOrderPaypalTransactionDetail($transactionInfo['transaction_id']);
            }
            $dbTransaction->commit();
            return true;
        } catch (Exception $e) {
            $dbTransaction->rollback();
            throw $e;
        }
    }

    /**
     * @desc 保存paypal交易
     * @return boolean
     */
    protected function saveOrderPaypalTransaction() {
        $info = $this->_paypalTransactionInfo;
        $data = array(
            'paypal_account'       => $info['paypal_account'],
            'paypal_account_id'    => $info['paypal_account_id'],
            'transaction_id'       => $info['transaction_id'],
            'transaction_time'     => $info['transaction_time'],
            'transaction_timezone' => $info['transaction_timezone'],
            'transaction_status'   => $info['transaction_status'],
            'transaction_type'     => $info['transaction_type'],
            'customer_name'        => $info['customer_name'],
            'customer_email'       => $info['customer_email'],
            'amt'                  => $info['amt'],
            'fee_amt'              => $info['fee_amt'],
            'net_amt'              => $info['net_amt'],
            'currency'             => $info['currency'],
            'create_time'          => date('Y-m-d H:i:s'),
            'update_time'          => date('Y-m-d H:i:s')
        );
        $tmp = OrderPaypalTransaction::model()->getOneByCondition('id',"transaction_id='{$info['transaction_id']}'");
        if(!empty($tmp)) {
            unset($tmp['create_time']);
            $isOk = OrderPaypalTransaction::model()->updateByPk($tmp['id'],$data);
        } else {
            $isOk = OrderPaypalTransaction::model()->insert($data);
        }
        return $isOk;
    }

    /**
     * @desc 保存paypal交易明细
     * @param  string $transactionID
     * @return boolean
     */
    protected function saveOrderPaypalTransactionDetail($transactionID) {
        $info = EbayPaypalTransactionDetail::model()->getOneByCondition('*',"transaction_id='{$transactionID}' and amt!=0 and is_delete=0 ");
        if(empty($info)) {
            throw new Exception("EbayPaypalTransactionDetail is empty");
        }
        $data = array(
            'transaction_id'        => $info['transaction_id'],
            'parent_transaction_id' => $info['parent_transaction_id'],
            'paypal_account'        => $info['paypal_account'],
            'paypal_account_id'     => $info['paypal_account_id'],
            'receiver_business'     => $info['receiver_business'],
            'receiver_email'        => $info['receiver_email'],
            'receiver_id'           => $info['receiver_id'],
            'payer_id'              => $info['payer_id'],
            'payer_name'            => $info['payer_name'],
            'payer_email'           => $info['payer_email'],
            'payer_country_code'    => $info['payer_country_code'],
            'payer_status'          => $info['payer_status'],
            'payer_address_status'  => $info['payer_address_status'],
            'buyer_id'              => $info['buyer_id'],
            'transaction_type'      => $info['transaction_type'],
            'payment_type'          => $info['payment_type'],
            'payment_status'        => $info['payment_status'],
            'amt'                   => $info['amt'],
            'fee_amt'               => $info['fee_amt'],
            'currency'              => $info['currency'],
            'pay_time'              => $info['pay_time'],
            'ori_pay_time'          => $info['ori_pay_time'],
            'note'                  => $info['note'],
            'create_time'           => date('Y-m-d H:i:s'),
            'update_time'           => date('Y-m-d H:i:s')
        );
        $tmp = OrderPaypalTransactionDetail::model()->getOneByCondition('id',"transaction_id='{$transactionID}'");
        if(!empty($tmp)) {
            unset($tmp['create_time']);
            $isOk = OrderPaypalTransactionDetail::model()->updateByPk($tmp['id'],$data);
        } else {
            $isOk = OrderPaypalTransactionDetail::model()->insert($data);
        }
        return $isOk;
    }

    /**
     * addEbayLog
     * @param int $accountID
     * @param int $status   
     * @param string $message  
     * @param string $eventName
     */
    public function addEbayLog($accountID,$status,$message,$eventName) {
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