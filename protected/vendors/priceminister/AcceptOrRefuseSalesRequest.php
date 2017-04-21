<?php
/**
 * @desc 接受或拒绝下载订单
 * @author lihy
 * @since 2016-07-01
 */
class AcceptOrRefuseSalesRequest extends PriceministerApiAbstract{
    public $_action = "acceptsale";
	public $_version = "2010-09-20";
	public $_urlPath = "sales_ws";
	
    public $_itemid = null;
    /**
     * @desc 设置请求参数
     * @see PlatformApiInterface::setRequest()
     */
    public function setRequest(){
        $request = array(
               'itemid'			=>	$this->_itemid
        );
        $this->request = $request;
        return $this;
    }
    /**
     * 
     * @param unknown $flag
     * @return AcceptOrRefuseSalesRequest
     */
    public function setActionRefuse($flag){
    	$this->_action = "acceptsale";
    	/* if(!$flag){
    		$this->_action = "acceptsale";
    	}else{
    		$this->_action = "refusesale";
    	} */
    	return $this;
    }
    
    public function setItemid($itemid){
    	$this->_itemid = $itemid;
    	return $this;
    }
}