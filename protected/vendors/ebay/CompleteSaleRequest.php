<?php
/**
 * @desc 交运订单(广告)的跟踪号完成订单
 * @author Gordon
 * @since 2015-06-02
 */
class CompleteSaleRequest extends EbayApiAbstract{
	
	/** @var 接口名 */
	public $_verb = 'CompleteSale';
	/** @var array 发运信息 */
    protected $_shipment = null;
    /** @var boolean 是否标记发货 */
    protected $_shipped = null;
    /** @var string item id */
    protected $_itemID = null;
    /**　@var string 交易ID */
    protected $_transactionID = null;
    /** @var string 订单号 */
    protected $_orderID = null;
    
    const SHIPPED_CARRIER_CODE = 'Other';	//ebay上传追踪号的承运商CODE
    /**
     * (non-PHPdoc)
     * @see PlatformApiInterface::setRequest()
     */
    public function setRequest(){
        $request = array(
                'RequesterCredentials' => array(
                    'eBayAuthToken' => $this->getToken(),
                ),
        );
    	if (!empty($this->_shipment))
    		$request['Shipment'] = $this->_shipment;
    	if (!is_null($this->_shipped))
    		$request['Shipped'] = $this->_shipped;
    	if (!is_null($this->_itemID))
    		$request['ItemID'] = $this->_itemID;
    	if (!is_null($this->_transactionID))
    		$request['TransactionID'] = $this->_transactionID;
    	if (!is_null($this->_orderID))
    		$request['OrderID'] = $this->_orderID;
    	$this->request = $request;
    	return $this;
    }
    
    /**
     * @desc 设置发运信息数据
     * @param array $shipment
     */
    public function setShipment($shipment) {
    	$this->_shipment = $shipment;
    }
    
    /**
     * @desc 设置发货标记
     * @param boolean $boolen
     */
    public function setShipped($boolen) {
    	$this->_shipped = $boolen;
    }
    /**
     * @desc 设置ITEM ID
     * @param unknown $itemID
     */
    public function setItemID($itemID) {
    	$this->_itemID = $itemID;
    }
    /**
     * @desc 设置交易ID
     * @param unknown $transactionID
     */
    public function setTransactionID($transactionID) {
    	$this->_transactionID = $transactionID;
    }
    /**
     * @desc 设置订单号
     * @param unknown $orderID
     */
    public function setOrderID($orderID) {
    	$this->_orderID = $orderID;
    }
}