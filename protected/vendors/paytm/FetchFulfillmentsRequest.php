<?php
/**
 * @desc Fetch Fulfillments Request
 * @author yangsh
 * @since 2017-02-28
 */
class FetchFulfillmentsRequest extends PaytmApiAbstract {

	/**
     * @var number $_orderId
     */
	protected $_orderId = null;

    /**
     * @var string $_trackingNumber
     */
    protected $_trackingNumber = null;

    /**
     * @var number $_orderItemId
     */
	protected $_orderItemId = null;

    /**
     * @var number $_fulfillmentId
     */
    protected $_fulfillmentId = null;

    /**
     * @var int $_status
     */
    protected $_status = null;

    /**
     * @var int $_limit
     */
    public $_limit = null;    

    /**
     * @desc 初始化对象
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * @desc 设置账号信息
     * @param int $accountID
     * @see PaytmApiAbstract::setAccount()
     */
    public function setAccount($accountID){
        parent::setAccount($accountID);
		$this->_baseUrl   = $this->paytmKeys['fulfillmentUrl'];
		$this->_isPost    = false;//GET

        return $this;
    }  

    /**
     * 设置EndPoint
     * @see PaytmApiAbstract::setEndPoint()
     */
    public function setEndPoint() {
    	$this->_endpoint = 'v1/merchant/'.$this->_merchantID.'/fulfillments.json?authtoken='.$this->_accessToken;;
    }

    /**
     * Getter for fulfillmentId
     * @return
     */
    public function getFulfillmentId(){
        return $this->_fulfillmentId;
    }
    
    /**
     * Setter for fulfillmentId
     * @param fulfillmentId value to set
     * @return self
     */
    public function setFulfillmentId($fulfillmentId){
        $this->_fulfillmentId = $fulfillmentId;
        return $this;
    }
    
    /**
     * Getter for orderId
     * @return
     */
    public function getOrderId(){
        return $this->_orderId;
    }
    
    /**
     * Setter for orderId
     * @param orderId value to set
     * @return self
     */
    public function setOrderId($orderId){
        $this->_orderId = $orderId;
        return $this;
    }
    
    /**
     * Getter for orderItemId
     * @return
     */
    public function getOrderItemId(){
        return $this->_orderItemId;
    }
    
    /**
     * Setter for orderItemId
     * @param orderItemId value to set
     * @return self
     */
    public function setOrderItemId($orderItemId){
        $this->_orderItemId = $orderItemId;
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
     * Getter for status
     * @return
     */
    public function getStatus(){
        return $this->_status;
    }
    
    /**
     * Setter for status
     * @param status value to set
     * @return self
     */
    public function setStatus($status){
        $this->_status = $status;
        return $this;
    }
    
	/**
	 * @desc 设置请求参数
	 * @see PlatformApiInterface::setRequest()
	 */
	public function setRequest(){
		$request = array();
        if (!is_null($this->_status)) {
            $request['status'] = $this->_status;
        }   
        if (!is_null($this->_limit)) {
            $request['limit'] = $this->_limit;
        }         
		if (!empty($this->_fulfillmentId)) {
			$request['fulfillment_id'] = $this->_fulfillmentId;
		}
        if ($this->_orderItemId) {
            $request['order_item_id'] = $this->_orderItemId;
        }           		
		if ($this->_orderId) {
			$request['order_id'] = $this->_orderId;
		}
		if (!is_null($this->_trackingNumber)) {
			$request['tracking_number'] = $this->_trackingNumber;
		}	       
		$this->request = $request;
		return $this;
	}	
}