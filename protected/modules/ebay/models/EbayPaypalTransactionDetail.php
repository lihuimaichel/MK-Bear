<?php
/**
 * @desc EbayPaypalTransactionDetail
 * @author yangsh
 * @since 2017-01-18
 */
class EbayPaypalTransactionDetail extends EbayModel{
    
    protected $_paypalAccountID = null;

    protected $_transactionID = null;

    protected $_transactionTime = array();

    protected $_paypalAccount = null;


    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
        
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_ebay_paypal_transaction_detail';
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
        $data['update_time'] = date('Y-m-d H:i:s');
        if (empty($info)) {
            $data['create_time'] = date('Y-m-d H:i:s');
            return $this->insert($data);
        } else {
            return $this->update($data,$data['transaction_id']);
        }
    }

    /**
     * 通过交易号获取
     */
    public function getgetTransactionDetailsByTransactionID() {
        if (empty($this->_paypalAccountID)) {
            $detail = EbayPaypalTransactionDetail::model()->getOneByCondition("paypal_account_id","transaction_id='{$this->_transactionID}'");
            if (empty($detail)) {
                die("detail is empty");
            }
            $this->setPaypalAccountID($detail['paypal_account_id']);
        }
        try {
            $request = new GetTransactionDetailsRequest();
            $request->setTransactionID($this->_transactionID);
            $response = $request->setAccount($this->_paypalAccountID)->setRequest()->sendRequest()->getResponse();
            MHelper::printvar($response,false);
            //没有权限
            if ( isset($response['L_ERRORCODE0']) && in_array(trim($response['L_ERRORCODE0']),array('10007','10004')) ) {
                $this->update(array('is_delete'=>1), $this->_transactionID);
                return false;
            }
            if( $request->getIfSuccess() ){
                $response['paypal_account_id'] = $this->_paypalAccountID;   
                $response['paypal_account'] = $this->_paypalAccount; 
                if ($this->_transactionID != $response['TRANSACTIONID']) {
                    $response['transaction_id'] = $this->_transactionID;
                    $this->saveTransactionDetails($response);
                }
                $response['transaction_id'] = $response['TRANSACTIONID'];//重置交易号    
                $this->saveTransactionDetails($response);                                       
            }
            return true;            
        } catch (Exception $e) {
            echo $e->getMessage()."<br>";
            return false;
        }
    }

    /**
     * 通过搜索条件获取
     */
    public function getTransactionDetailsByCondition() {
        $maxSearchDate = date("Y-m-d H:i:s",strtotime("-60 days"));
        $where = "amt=0 and is_delete=0 and pay_time>'{$maxSearchDate}'";
        if ($this->_paypalAccountID) {
            $where .= " and paypal_account_id='{$this->_paypalAccountID}'";
        }
        if ($this->_transactionID) {
            $where .= " and transaction_id='{$this->_transactionID}'";
        }
        if ( !empty($this->_transactionTime[0]) && !empty($this->_transactionTime[1])) {
            $where .= " and pay_time>'{$this->_transactionTime[0]}'";
            $where .= " and pay_time<'{$this->_transactionTime[1]}'";
        }
        $res = $this->dbConnection->createCommand()
                    ->select("count(*) as total")
                    ->from($this->tableName())
                    ->where($where)
                    ->queryRow();                    
        if (empty($res) || $res['total'] == 0) {
            return false;
        }
        $total = $res['total'];
        $pageSize = 3000;
        $pageCount = ceil($total/$pageSize);
        for ($page=1; $page <= $pageCount ; $page++) { 
            $offset = ($page - 1) * $pageSize;
            $list = $cmd = $this->dbConnection->createCommand()
                        ->select("transaction_id,paypal_account_id,paypal_account")
                        ->from($this->tableName())
                        ->where($where)
                        ->order("id asc")
                        ->limit($pageSize,$offset)
                        ->queryAll();
            if (!empty($list)) {
                foreach ($list as $row) {
                    try {
                        $request = new GetTransactionDetailsRequest();
                        $request->setTransactionID($row['transaction_id']);
                        $response = $request->setAccount($row['paypal_account_id'])->setRequest()->sendRequest()->getResponse();                       
                        //没有权限
                        if ( isset($response['L_ERRORCODE0']) && in_array(trim($response['L_ERRORCODE0']),array('10007','10004')) ) {//&& strtoupper(trim($response['L_SHORTMESSAGE0'])) == 'PERMISSION DENIED'
                            $this->update(array('is_delete'=>1), $row['transaction_id']);
                            continue;
                        }
                        if( $request->getIfSuccess() ){
                            $response['paypal_account_id'] = $this->_paypalAccountID;   
                            $response['paypal_account'] = $this->_paypalAccount; 
                            if ($row['transaction_id'] != $response['TRANSACTIONID']) {
                                $response['transaction_id'] = $row['transaction_id'];
                                $this->saveTransactionDetails($response);
                            }        
                            $response['transaction_id'] = $response['TRANSACTIONID'];//重置交易号       
                            $this->saveTransactionDetails($response); 
                        }
                    } catch (Exception $e) {
                        $virtualAccountId = 80000 + $this->_paypalAccountID;
                        $errMsg = $response['TRANSACTIONID'].' ## '.$e->getMessage(); 
                        echo $errMsg."<br>";

                        //记录异常日志 
                        $logModel = new EbayLog();
                        $logModel->getDbConnection()->createCommand()->insert(
                            $logModel->tableName(), array(
                                'account_id'    => $virtualAccountId,
                                'event'         => EbayLog::EVENT_GETTRANSACTIONDETAILS,
                                'start_time'    => date('Y-m-d H:i:s'),                         
                                'status'        => EbayLog::STATUS_FAILURE,
                                'message'       => mb_strlen($errMsg)>500 ? mb_substr($errMsg,0,500) : $errMsg,
                                'response_time' => date('Y-m-d H:i:s'),
                                'end_time'      => date('Y-m-d H:i:s'),
                                'create_user_id'=> intval(Yii::app()->user->id),
                            )
                        );
                    }
                }
            }                         
        }
        return true;
    }

    /**
     * 保存交易记录
     * @param  array $paypalTransaction 
     * @return boolean 
     */
    public function saveTransactionDetails($paypalTransaction) {
        $transactionID  = isset($paypalTransaction['transaction_id']) ? $paypalTransaction['transaction_id'] : $paypalTransaction['TRANSACTIONID'];
        $payerName      = isset ( $paypalTransaction ['FIRSTNAME'] ) ? trim($paypalTransaction ['FIRSTNAME']) : '';
        isset ( $paypalTransaction ['MIDDLENAME'] ) && $payerName .= ' ' . trim($paypalTransaction ['MIDDLENAME']);
        isset ( $paypalTransaction ['LASTNAME'] ) && $payerName .= ' ' . trim($paypalTransaction ['LASTNAME']);
        if ($payerName != '') {
            $payerName = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $payerName);
        }        
        $receiverEmail  = isset ( $paypalTransaction ['RECEIVEREMAIL'] ) ? $paypalTransaction ['RECEIVEREMAIL'] : '';
        $receiverID     = isset ( $paypalTransaction ['RECEIVERID'] ) ? $paypalTransaction ['RECEIVERID'] : '';
        $payerID        = isset ( $paypalTransaction ['PAYERID'] ) ? $paypalTransaction ['PAYERID'] : '';
        $payerEmail     = isset ( $paypalTransaction ['EMAIL'] ) ? $paypalTransaction ['EMAIL'] : '';
        $paymentStatus  = isset ( $paypalTransaction ['PAYMENTSTATUS'] ) ? $paypalTransaction ['PAYMENTSTATUS'] : '';
        $paymentType    = isset ( $paypalTransaction ['PAYMENTTYPE'] ) ? $paypalTransaction ['PAYMENTTYPE'] : '';
        
        //amt
        $amt            = isset ( $paypalTransaction ['AMT'] ) ? $paypalTransaction ['AMT'] : 0;
        
        //以下字段若有为家，则需重拉
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

        $payTime = isset ( $paypalTransaction ['ORDERTIME'] ) ? self::transferUTCTimeFormat($paypalTransaction ['ORDERTIME']) : '0000-00-00 00:00:00';

        $ori_pay_time = isset ( $paypalTransaction ['ORDERTIME'] ) ? date("Y-m-d H:i:s", strtotime($paypalTransaction ['ORDERTIME'])) : '0000-00-00 00:00:00';        

        $transData = array (
            'transaction_id'        => $transactionID,
            'parent_transaction_id' => isset ( $paypalTransaction ['PARENTTRANSACTIONID'] ) ? $paypalTransaction ['PARENTTRANSACTIONID'] : '',            
            'paypal_account'        => isset ( $paypalTransaction ['paypal_account'] ) ? $paypalTransaction ['paypal_account'] : '',
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
            'amt'                   => $amt,
            'currency'              => isset ( $paypalTransaction ['CURRENCYCODE'] ) ? $paypalTransaction ['CURRENCYCODE'] : '',
            'pay_time'              => $payTime,
            'ori_pay_time'          => $ori_pay_time,
            'note'                  => $transNote,
        );

        //有返回则更新，没有用主表的
        if (isset ( $paypalTransaction ['FEEAMT'] )) {
            $transData['fee_amt'] = (float)$paypalTransaction ['FEEAMT'];
        }
        return $this->save($transData);
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

    public function setPaypalAccountID($payAccountId) {
        $this->_paypalAccountID = $payAccountId;
        if ($payAccountId) {
            $paypalInfo = PaypalAccount::model()->getOneByCondition("*","id={$payAccountId}");
            $this->_paypalAccount = $paypalInfo['email'];
            $this->_paypalAccountID = $paypalInfo['id'];
        }
    }

    public function setTransactionTime(array $timeArr) {
        $this->_transactionTime = $timeArr;
    }

    public function setTransactionID($transactionID) {
        $this->_transactionID = $transactionID;
    }

}