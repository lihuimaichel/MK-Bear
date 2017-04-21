<?php
/**
 * @desc 根据订单列表获取订单详情
 * @author zhangf
 *
 */
class GetOrderInfoByIDsRequest extends JdApiAbstract {
	
	/** @var array 订单号列表 **/
	protected $_orderList = array();

	protected $_apiMethod = 'jingdong.ept.order.getorderinfobyids';					
	
	/**
	 * (non-PHPdoc)
	 * @see JdApiAbstract::setRequest()
	 */
	public function setRequest() {
		$request = array();
		if (is_array($this->_orderList))
			$request['orderId'] = implode(',', $this->_orderList);
		else
			$request['orderId'] = (string)$this->_orderList;
		$this->_request = $request;
		return $this;
	}
	
	/**
	 * @desc 设置订单列表
	 * @param unknown $ids
	 */
	public function setOrderList($ids) {
		$this->_orderList = $ids;
	}
}