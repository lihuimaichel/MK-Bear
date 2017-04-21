<?php
/**
 * @desc 下线在线广告
 * @author Gordon
 * @since 2015-06-02
 */
class EndItemRequest extends EbayApiAbstract{
    
	private $_EndingReason = null;
	private $_ItemID       = null;
	public $_verb          = 'EndFixedPriceItem';

    public function setRequest(){
    	$request = array(
            'RequesterCredentials' => array(
                'eBayAuthToken' => $this->getToken(),
            ),
    	);
    	if(!is_null($this->_EndingReason)) $request['EndingReason'] = $this->_EndingReason;
    	if(!is_null($this->_ItemID)) $request['ItemID'] = $this->_ItemID;
    	$this->request = $request;
    	return $this;
    }
    
    public function setItemID($itemID){
    	$this->_ItemID = $itemID;
    	return $this;
    }
    
    public function setEndingReason($reason){
    	$this->_EndingReason = $reason;
    	return $this;
    }
}