<?php
/**
 * 线上物流发货信息
 * api.getOnlineLogisticsInfo
 * @author	tan
 * @since	2015-9-23
 */

class GetOnlineLogisticsInfoRequest extends AliexpressApiAbstract {
	
	/**@var string 交易订单ID*/
	public $_orderId = NULL;
	
	public function setApiMethod() {
		$this->_apiMethod = 'api.getOnlineLogisticsInfo';
	}
	
	/**
	 * Set orderId
	 * @param	string	$orderId
	 */
	public function setOrderId($orderId) {
		$this->_orderId = $orderId;
	}
	
	public function setRequest() {
		$request = array(
				'orderId'	=> $this->_orderId
		);
		$this->request = $request;
		return $this;
	}
	
}
