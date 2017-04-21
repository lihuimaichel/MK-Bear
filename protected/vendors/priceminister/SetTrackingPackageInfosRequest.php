<?php
/**
 * @desc 上传订单追踪号
 * @author lihy
 * @since 2016-07-01
 */
class SetTrackingPackageInfosRequest extends PriceministerApiAbstract{
    public $_action = "settrackingpackageinfos";
	public $_version = "2016-03-16";
	public $_urlPath = "sales_ws";
	
	public $_itemid = null;
	public $_transporter_name = null;
	public $_tracking_number = null;
	public $_tracking_url = null;
    
    /**
     * @desc 设置请求参数
     * @see PlatformApiInterface::setRequest()
     */
    public function setRequest(){
        $request = array(
               	'itemid'			=>	$this->_itemid,
        		'transporter_name'	=>	$this->_transporter_name,
        		'tracking_number'	=>	$this->_tracking_number,
        );
        if($this->_tracking_url){
        	$request['tracking_url'] = $this->_tracking_url;
        }
        $this->request = $request;
        return $this;
    }
    
    
    public function setItemid($itemid){
    	$this->_itemid = $itemid;
    	return $this;
    }
    
    public function setTransporterName($transporterName){
    	$this->_transporter_name = $transporterName;
    	return $this;
    }
    
    public function setTrackingNumber($trackingNumber){
    	$this->_tracking_number = $trackingNumber;
    	return $this;
    }
    
    public function setTrackingUrl($trackingUrl){
    	$this->_tracking_url = $trackingUrl;
    	return $this;
    }
}