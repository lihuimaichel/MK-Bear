<?php
/**
 * @desc 下载订单
 * @author lihy
 * @since 2016-07-01
 */
class GetCurrentSalesRequest extends PriceministerApiAbstract{
    public $_action = "getcurrentsales";
	public $_version = "2016-03-16";
	public $_urlPath = "sales_ws";
	
	
	public $_nexttoken = null;
	public $_purchasedata = null;
	public $_notshippeditemsonly = true;
    public $_ispendingpreorder = null;
    /**
     * @desc 设置请求参数
     * @see PlatformApiInterface::setRequest()
     */
    public function setRequest(){
        $request = array();
        if($this->_nexttoken){
        	$request['nexttoken'] = $this->_nexttoken;
        }
        if($this->_purchasedata){
        	$request['purchasedate'] = $this->_purchasedata;
        }else{
        	$request['purchasedate'] = date("Y-m-d", time()-3*24*3600);
        }
        
        
        $request['notshippeditemsonly'] = $this->_notshippeditemsonly;
        
        if($this->_ispendingpreorder){
        	$request['ispendingpreorder'] = $this->_ispendingpreorder;
        }
        $this->request = $request;
        return $this;
    }
    
    public function setNexttoken($nexttoken){
    	$this->_nexttoken = $nexttoken;
    	return $this;
    }
    
    public function setPurchaseData($purchasedate){
    	$this->_purchasedata = $purchasedate;
    	return $this;
    }
    
    public function setNotshippeditemsonly($flag){
    	$this->_notshippeditemsonly = $flag;
    	return $this;
    }
    
    public function setIspendingpreorder($flag){
    	if($flag)
    		$this->_ispendingpreorder = "y";
    	else
    		$this->_ispendingpreorder = "n";
    	return $this;
    }
}