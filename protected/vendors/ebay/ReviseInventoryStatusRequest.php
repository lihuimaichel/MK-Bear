<?php
/**
 * @desc 修改在线广告库存数量(可修改多条广告)
 * @author lihy
 * @since 2016-01-23
 */
class ReviseInventoryStatusRequest extends EbayApiAbstract{
    private $_sku = NULL;
    private $_itemID = NULL;
	private $_startPrice = NULL;
	private $_quantity = NULL;
	private $_inventoryStatus = array();
	public $_verb = 'ReviseInventoryStatus';
	/**
	 * @desc 设置sku
	 * @param unknown $sku
	 * @return ReviseInventoryStatusRequest
	 */
	public function setSku($sku){
		$this->_sku = $sku;
		return $this;
	}
	/**
	 * @desc 设置item ID
	 * @param unknown $itemID
	 * @return ReviseInventoryStatusRequest
	 */
	public function setItemID($itemID){
		$this->_itemID = $itemID;
		return $this;
	}
	/**
	 * @desc 设置开始价格
	 * @param unknown $startPrice
	 * @return ReviseInventoryStatusRequest
	 */
	public function setStartPrice($startPrice){
		$this->_startPrice = $startPrice;
		return $this;
	}
	/**
	 * @desc 设置库存数量
	 * @param unknown $quantity
	 * @return ReviseInventoryStatusRequest
	 */
	public function setQuantity($quantity){
		$this->_quantity = $quantity;
		return $this;
	}
	/**
	 * @desc push
	 * @return ReviseInventoryStatusRequest
	 */
	public function push(){
		$inventoryStatus = array();
		if($this->_sku !== NULL) $inventoryStatus['SKU'] = $this->_sku;
		if($this->_itemID !== NULL) $inventoryStatus['ItemID'] = $this->_itemID;
		if($this->_startPrice !== NULL) $inventoryStatus['StartPrice'] = floatval($this->_startPrice);
		if($this->_quantity !== NULL) $inventoryStatus['Quantity'] = (int)$this->_quantity;
		$this->_sku = NULL;
		$this->_itemID = NULL;
		$this->_startPrice = NULL;
		$this->_quantity = NULL;
		$this->_inventoryStatus[] = $inventoryStatus;
		return $this;
	}
	/**
	 * @DESC  清除
	 * @return ReviseInventoryStatusRequest
	 */
	public function clean(){
		$this->_inventoryStatus = array();
		return $this;
	}
	
    public function setRequest(){
    	/**
    	 * <SKU>cmg00002</SKU>
    	 * <ItemID>110035407916</ItemID>
    	 * <StartPrice>19.95</StartPrice>
    	 * <Quantity>80</Quantity>
    	 * */
    	$request = array(
		    			'RequesterCredentials' => array(
		    				'eBayAuthToken' => $this->getToken(),
		    			),
    	);
    	
		$request['InventoryStatus'] = $this->_inventoryStatus;
    	$this->request = $request;
    	return $this;
    }
    
    /**
     * @override 重写
     * @desc 将请求参数转化为Xml
     */
    public function getRequestXmlBody(){
    	$xmlGeneration = new XmlGenerator();
    	return $xmlGeneration->XmlWriter()->push($this->getXmlRequestHeader(), array('xmlns' => 'urn:ebay:apis:eBLBaseComponents'))
						    	->buildXMLFilterMulti($this->getRequest())
						    	->pop()
						    	->getXml();
    }
}