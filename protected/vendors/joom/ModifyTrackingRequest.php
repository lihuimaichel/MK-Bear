<?php
/**
 * @desc 设置订单发货接口
 * @author zhangF
 *
 */
class ModifyTrackingRequest extends JoomApiAbstract{
	/** @var string 设置交互Endpoint */
	protected $_endpoint = null;
	
	/** @var string 设置订单号 */
	protected $_id = null;
	
	/** @var string 设置Carrier */
	protected $_trackingProvider = null;
	
	/** @var string 设置追踪号 */
	protected $_trackingNumber = null;
	
	/** @var string 设置备注 */
	protected $_shipNote = null;
	
        /** @var string 设置shiping_time */
	protected $_shippingTime = 7;
	/**
	 * (non-PHPdoc)
	 * @see PlatformApiInterface::setRequest()
	 */
	public function setRequest() {
		$request = array(
			'id' => $this->_id,
			'tracking_provider' => $this->_trackingProvider,
		);
		if (!is_null($this->_trackingNumber))
			$request['tracking_number'] = $this->_trackingNumber;
		/* if (!is_null($this->_shipNote))
			$request['ship_note'] = $this->_shipNote;
        if (!is_null($this->_shippingTime))
			$request['shipping_time'] = $this->_shippingTime; */
		$this->request = $request;
		if(isset($_REQUEST['bug']) && $_REQUEST['bug']){
			var_dump($request);
		}
		return $this;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see JoomApiAbstract::setEndpoint()
	 */
	public function setEndpoint() {
		parent::setEndpoint('order/modify-tracking', true);
	}
	
	/**
	 * @desc 设置UD
	 * @param integer $ID
	 */
	public function setID($ID) {
		$this->_id = $ID;
	}
	
	/**
	 * @desc 设置设置 tracking provider
	 * @param string $trackingProvider
	 */
	public function setTrackingProvider($trackingProvider) {
		$this->_trackingProvider = $trackingProvider;
	}
	
	/**
	 * @desc 设置tracking number
	 * @param string $trackingNumber
	 */
	public function setTrackingNumber($trackingNumber) {
		$this->_trackingNumber = $trackingNumber;
	}
	
	/**
	 * @desc 设置ship note
	 * @param string $note
	 */
	public function setShipNote($note) {
		$this->_shipNote = $note;
	}
}