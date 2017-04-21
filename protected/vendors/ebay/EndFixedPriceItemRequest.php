<?php
/**
 * @desc 下线一口价在线广告
 * @author lihy
 * @since 2016-01-22
 */
class EndFixedPriceItemRequest extends EbayApiAbstract{

    private $_EndingReason = null;
    private $_ItemID       = null;
    private $_SKU          = null;
    public $_verb          = 'EndFixedPriceItem';

    public function setRequest(){
    	$request = array(
            'RequesterCredentials' => array(
                'eBayAuthToken' => $this->getToken(),
            ),
    	);
    	if(!is_null($this->_EndingReason)) $request['EndingReason'] = $this->_EndingReason;
    	if(!is_null($this->_ItemID)) $request['ItemID'] = $this->_ItemID;
    	if(!is_null($this->_SKU)) $request['SKU'] = $this->_SKU;
    	$this->request = $request;
    	return $this;
    }
    
    public function setSKU($sku){
    	$this->_SKU = $sku;
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