<?php
/**
 * @desc Acknowledge Order Request
 * @author yangsh
 * @since 2017-02-28
 */
class AcknowledgeOrderRequest extends PaytmApiAbstract {
	
	/*@var string orderID 平台订单号*/
	protected $_orderID = null;

	/*@var array orderItemIDs 平台订单明细id*/
	protected $_orderItemIDs = null;

	/*@var int status 状态*/
	protected $_status = null;//0: reject, 1: accept

	/*@var int comment 备注 (可选) */
	protected $_comment = null;

	const ACK_ACCEPT = 1;//accept

	const ACK_REJECT = 0;//reject

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
    	$this->_endpoint = 'v1/merchant/'.$this->_merchantID.'/fulfillment/ack/'.$this->_orderID.'?authtoken='.$this->_accessToken;
    }

    /**
     * set OrderItemIDs
     * @param array $orderItemIDs 
     */
    public function setOrderItemIDs($orderItemIDs) {
    	$this->_orderItemIDs = $orderItemIDs;
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
     * set comment
     * @param string $comment
     */
    public function setComment($comment) {
    	$this->_comment = $comment;
    	return $this;
    }    
	
	/**
	 * @desc 设置请求参数
	 * @see PlatformApiInterface::setRequest()
	 */
	public function setRequest(){
		$request = array();
		if ($this->_orderItemIDs) {
			$request['item_ids'] = $this->_orderItemIDs;
		}
		if (!is_null($this->_status)) {
			$request['status'] = $this->_status;
		}
		if (!empty($this->_comment)) {
			$request['comment'] = $this->_comment;
		}
		$this->request = $request;
		return $this;
	}	
}