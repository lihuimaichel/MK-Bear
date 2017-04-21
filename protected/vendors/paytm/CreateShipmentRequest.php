<?php
/**
 * @desc Create Shipment Request
 * @author yangsh
 * @since 2017-03-30
 */
class CreateShipmentRequest extends PaytmApiAbstract {

    /**
     * @var number
     */
    protected $_orderID = null;

    /**
     * @var string
     */
    protected $_shippingDescription = null;

    /**
     * @var string
     */
    protected $_trackingURL = null;

    /**
     * @var number
     */
    protected $_shipperId = null;

    /**
     * @var array
     */
    protected $_orderItemIds = null;

    /**
     * @var string
     */
    protected $_trackingNumber = null;

    /**
     * init params
     * @param string $orderID 
     */
    public function __construct($orderID) {
        parent::__construct();
        $this->_orderID = $orderID;
    }

    /**
     * @desc 设置账号信息
     * @param int $accountID
     * @see PaytmApiAbstract::setAccount()
     */
    public function setAccount($accountID){
        parent::setAccount($accountID);
		$this->_baseUrl   = $this->paytmKeys['fulfillmentUrl'];
		$this->_isPost    = true;//POST

        return $this;
    }

    /**
     * 设置EndPoint
     * @see PaytmApiAbstract::setEndPoint()
     */
    public function setEndPoint() {
        $this->_endpoint = 'v1/merchant/'.$this->_merchantID.'/fulfillment/create/'.$this->_orderID.'?authtoken='.$this->_accessToken;
    }

    /**
     * Getter for shippingDescription
     * @return
     */
    public function getShippingDescription(){
        return $this->_shippingDescription;
    }

    /**
     * Setter for shippingDescription
     * @param shippingDescription value to set
     * @return self
     */
    public function setShippingDescription($shippingDescription){
        $this->_shippingDescription = $shippingDescription;
        return $this;
    }    

    /**
     * Getter for trackingURL
     * @return
     */
    public function getTrackingURL(){
        return $this->_trackingURL;
    }
    
    /**
     * Setter for trackingURL
     * @param trackingURL value to set
     * @return self
     */
    public function setTrackingURL($trackingURL){
        $this->_trackingURL = $trackingURL;
        return $this;
    }

    /**
     * Getter for shipperId
     * @return
     */
    public function getShipperId(){
        return $this->_shipperId;
    }
    
    /**
     * Setter for shipperId
     * @param shipperId value to set
     * @return self
     */
    public function setShipperId($shipperId){
        $this->_shipperId = $shipperId;
        return $this;
    }
    
    /**
     * Getter for orderItemIds
     * @return
     */
    public function getOrderItemIds(){
        return $this->_orderItemIds;
    }
    
    /**
     * Setter for orderItemIds
     * @param orderItemIds value to set
     * @return self
     */
    public function setOrderItemIds($orderItemIds){
        $this->_orderItemIds = $orderItemIds;
        return $this;
    }

    /**
     * Getter for trackingNumber
     * @return
     */
    public function getTrackingNumber(){
        return $this->_trackingNumber;
    }
    
    /**
     * Setter for trackingNumber
     * @param trackingNumber value to set
     * @return self
     */
    public function setTrackingNumber($trackingNumber){
        $this->_trackingNumber = $trackingNumber;
        return $this;
    }
	
	/**
	 * @desc 设置请求参数
	 * @see PlatformApiInterface::setRequest()
	 */
	public function setRequest(){
		$request = array(
			'authtoken' => $this->_accessToken,
		);
        if(!is_null($this->_orderItemIds)) {
            $request['order_item_ids'] = $this->_orderItemIds;
        }
        if(!is_null($this->_trackingNumber)) {
            $request['tracking_number'] = $this->_trackingNumber;
        }
        if(!is_null($this->_trackingURL)) {
            $request['tracking_URL'] = $this->_trackingURL;
        }
        if(!is_null($this->_shippingDescription)) {
            $request['shipping_description'] = $this->_shippingDescription;
        }
        if(!is_null($this->_shipperId)) {
            $request['shipper_id'] = $this->_shipperId;
        }                        
		$this->request = $request;
		return $this;
	}	
}