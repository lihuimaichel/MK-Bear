<?php
/**
 * @desc 根据订单列表获取订单详情
 * @author zhangf
 *
 */
class GetOrderInfoByIDRequest extends JdApiAbstract {
	
	/** @var array 订单号列表 **/
	protected $_orderId  = null;

	protected $_apiMethod = 'jingdong.ept.order.getorderinfobyid';					
	
	/**
	 * (non-PHPdoc)
	 * @see JdApiAbstract::setRequest()
	 */
	public function setRequest() {
		$request = array(
			'orderId' => $this->_orderId,
		);
		$this->_request = $request;
		return $this;
	}
	
	/**
	 * @desc 设置订单列表
	 * @param unknown $ids
	 */
	public function setOrderID($orderID) {
		$this->_orderId = $orderID;
	}
}