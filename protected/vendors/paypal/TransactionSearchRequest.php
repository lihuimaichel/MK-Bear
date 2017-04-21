<?php
/**
 * @desc 获取交易信息列表
 * @author Gordon
 */
class TransactionSearchRequest extends PaypalApiAbstract{
    public $_startDate = null;
    public $_endDate = null;
    public $_transactionClass;
    public $_currencyCode;
    public $_status;
    public $_transactionID;

    public function setRequest(){
        $request = array();
        if(!is_null($this->_startDate))
        	$request['StartDate'] = $this->_startDate;
        if(!is_null($this->_endDate))
        	$request['EndDate'] = $this->_endDate;
        if(!is_null($this->_transactionClass))
        	$request['TransactionClass'] = $this->_transactionClass;
        if(!is_null($this->_currencyCode))
        	$request['CurrencyCode'] = $this->_currencyCode;
        if(!is_null($this->_status))
        	$request['Status'] = $this->_status;
        if (!is_null($this->_transactionID)) {
            $request['TransactionID'] = $this->_transactionID;
        }
      	$this->request = '&'.http_build_query($request);
        return $this;
    }
    
    
    /**
     * @desc 设置调用接口名
     * @see PaypalApiAbstract::setMethod()
     */
    public function setMethod(){
        $this->_Method = 'TransactionSearch';
        return $this;
    }
    
    
    public function setStartDate($startDate){
    	$this->_startDate = $startDate;
    	return $this;
    }
    
    
    public function setEndDate($endDate){
    	$this->_endDate = $endDate;
    	return $this;
    }
    
    public function setTransactionClass($transactionClass){
    	$this->_transactionClass = $transactionClass;
    	return $this;
    }
    public function setCurrencyCode($currencyCode){
    	$this->_currencyCode = $currencyCode;
    	return $this;
    }
    
    public function setStatus($status){
    	$this->_status = $status;
    	return $this;
    }

    public function setTransactionID($transactionID) {
        $this->_transactionID = $transactionID;
        return $this;
    }
} 