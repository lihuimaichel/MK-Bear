<?php
/**
 * @desc 订单发货接口
 * @author zhangf
 *
 */
class DeliveryorderRequest extends JdApiAbstract {
	/** @var string 订单号 **/
	protected $_orderId = null;
	/** @var string 追踪号 **/
	protected $_expressNo = null;

	/** @var 接口方法名 **/
	protected $_apiMethod = 'jingdong.ept.order.deliveryorder';
	
	/**
	 * @desc 设置订单号
	 * @param unknown $orderID
	 */
	public function setOrderId($orderID) {
		$this->_orderId = $orderID;
	}
	
	/**
	 * @desc 设置追踪号
	 * @param unknown $trackNo
	 */
	public function setExpressNo($trackNo) {
		$this->_expressNo = $trackNo;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see PlatformApiInterface::setRequest()
	 */
	public function setRequest() {
		$request = array(
			'orderId' => $this->_orderId,
			'expressNo' => $this->_expressNo,
		);
		$this->_request = $request;
		return $this;
	}
}