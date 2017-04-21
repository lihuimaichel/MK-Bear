<?php
/**
 * @desc 获取交易信息
 * @author Gordon
 */
class GetTransactionDetailsRequest extends PaypalApiAbstract{
    
    const STATUS_COMPLETE = 'Completed';
    /** @var string 交易号 */
    public $_TransactionID = null;
    
    public function setRequest(){
        $this->request = '&TRANSACTIONID='.$this->_TransactionID;
        return $this;
    }
    
    /**
     * @desc 设置交易号
     * @param string $transactionID
     */
    public function setTransactionID($transactionID){     	
        $this->_TransactionID = $transactionID;        
    }
    
    /**
     * @desc 设置调用接口名
     * @see PaypalApiAbstract::setMethod()
     */
    public function setMethod($method = ''){
        $this->_Method = 'GetTransactionDetails';
    }
} 