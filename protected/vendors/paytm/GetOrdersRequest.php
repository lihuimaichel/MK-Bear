<?php
/**
 * @desc Fetch Orders Request
 * @author yangsh
 * @since 2017-02-28
 */
class GetOrdersRequest extends PaytmApiAbstract {
	
	/*Order id based filters*/
	protected $_orderIDs = null;//多个以逗号分隔

	/*Time based filters*/
	protected $_placedBefore = null;

	protected $_placedAfter = null;

	/*Status based filters*/
	protected $_status = null;

	/*Pagination*/
	protected $_limit = 500;//每页返回的记录数
	
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
    	$this->_endpoint = 'v1/merchant/'.$this->_merchantID.'/orders.json';
    }

    /**
     * set OrderIDs
     * @param array $orderIds
     */
    public function setOrderIDs($orderIds) {
    	$this->_orderIDs = $orderIds;
    	return $this;
    }

    /**
     * set PlacedAfter
     * @param int $placedAfter 
     */
    public function setPlacedAfter($placedAfter) {
    	$this->_placedAfter = $placedAfter;
    	return $this;
    }

    /**
     * set PlacedBefore
     * @param int $placedBefore 
     */
    public function setPlacedBefore($placedBefore) {
    	$this->_placedBefore = $placedBefore;
    	return $this;
    }

    /**
     * set Status
     * @param int $status
     */
    public function setStatus($status) {
    	$this->_status = $status;
    	return $this;
    }

    /**
     * set Limit
     * @param int $limit
     */
    public function setLimit($limit) {
    	$this->_limit = $limit;
    	return $this;
    }
	
	/**
	 * @desc 设置请求参数
	 * @see PlatformApiInterface::setRequest()
	 */
	public function setRequest(){
		$request = array(
			'authtoken' => $this->_accessToken,
			'limit'     => $this->_limit,
		);
		if (!empty($this->_orderIDs)) {
			$request['order_ids'] = implode(',',$this->_orderIDs);
		}		
		if ($this->_placedAfter) {
			$request['placed_after'] = $this->_placedAfter;
		}
		if ($this->_placedBefore) {
			$request['placed_before'] = $this->_placedBefore;
		}	
		if (!is_null($this->_status)) {
			$request['status'] = $this->_status;
		}			
		$this->request = $request;
		return $this;
	}	
}