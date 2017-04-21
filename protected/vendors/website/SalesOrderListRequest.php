<?php
/**
 * @desc 网站平台订单列表交互类
 * @author zhangF
 *
 */
class SalesOrderListRequest extends WebsiteApiAbstract {
	protected $_startTime = null;					//@var siring 订单开始时间
	protected $_endTime = null;						//@var string 订单结束时间
	protected $_orderStatus = null;					//@var string 订单状态

	const ORDER_STATUS_PROCESSING = 'processing';
	const ORDER_STATUS_PENDING = 'pending_payment';
	
	/**
	 * @desc 发送请求
	 * @see PlatformApiInterface::sendRequest()
	 */
	public function sendRequest() {
		try {
			$updateTimeStart = $this->_startTime;
			$updateTimeEnd = $this->_endTime;
			$orderStatus = array();
			$params = array(
				self::$_sessionID[$this->_accountID],
				'sales_order.list',
				array(
					array(
						'updated_at' => array(
							'from' => $updateTimeStart,
							'to' => $updateTimeEnd,
							'datetime' => true
						),
						'status' => array(
							'in' => $this->_orderStatus
						),
					),
				)
			);
			$response = $this->_soapClient->__call('call', $params);
			if (empty($response)) {
				$this->_errors = 'No Orders';
			}
			$this->_requestbody = $this->_soapClient->__getLastResponse();
			$this->response = $response;
		} catch (SoapFault $e) {
			$this->_errors = 'Soap Exception: ' . $e->getMessage();
		} catch (Exception $e) {
			$this->_errors = $e->getMessage();
		}
		if (!empty($this->_errors))
			$this->writeErrorLog();
		return $this;
	}
	
	/**
	 * @desc 设置订单开始时间
	 * @param string $time
	 */
	public function setStartTime($time) {
		$this->_startTime = $time;
	}
	
	/**
	 * @desc 设置订单结束时间
	 * @param string $time
	 */
	public function setEndTime($time) {
		$this->_endTime = $time;
	}
	
	/**
	 * @desc 设置订单状态
	 * @param mixed $status
	 */
	public function setOrderStatus($status) {
		if (is_array($status))
			$this->_orderStatus = $status;
		else
			$this->_orderStatus = array($status);
	}
}