<?php
/**
 * @desc 获取店铺信息
 * @author Gordon
 * @since 2015-06-02
 */
class GetStoreRequest extends EbayApiAbstract{
	public $_verb = "GetStore";
    
    public function setRequest(){
    	$requestArr = array(
    			'RequesterCredentials' => array(
    					'eBayAuthToken' => $this->_usertoken
    			),
    	);
    	$this->request = $requestArr;
    	return $this;
    }
}