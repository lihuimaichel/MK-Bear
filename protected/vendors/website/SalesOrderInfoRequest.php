<?php
/**
 * @desc 网站平台订单信息交互类
 * @author zhangF
 *
 */
class SalesOrderInfoRequest extends WebsiteApiAbstract {
	
	protected $_orderID = null;		//网站订单号
	
	/**
	 * @desc 发送请求
	 * @see PlatformApiInterface::sendRequest()
	 */
	public function sendRequest() {
		try {
			$params = array(
				self::$_sessionID[$this->_accountID],
				'sales_order.info',
				$this->_orderID,
			);
			$response = $this->_soapClient->__call('call', $params);
			if (empty($response)) {
				$this->_errors = 'No Order Informations';
			}
			$this->_requestbody = $this->_soapClient->__getLastRequest();
			$this->response = $response;
		} catch (Exception $e) {
			$this->_errors = $e->getMessage();
		}
		if (!empty($this->_errors))
			$this->writeErrorLog();
		return $this;
	}
	
	/**
	 * @desc 设置订单ID
	 * @param string $orderID
	 */
	public function setOrderID($orderID) {
		$this->_orderID = $orderID;
	}
}