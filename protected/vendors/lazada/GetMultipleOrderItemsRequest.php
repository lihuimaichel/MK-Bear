<?php
/**
 * @desc 抓取返回的TrackingCode和PackageId
 * @author ltf
 * @since 2015.11.10
 */
class GetMultipleOrderItemsRequest extends LazadaApiAbstract{
    
	public $_apiMethod = 'GetMultipleOrderItems';
	
	/**@var 标记的订单*/
	public $_orderIdList = null;
	
	public $_httpMethod = 'GET';
	
	/**
	 * @desc 设置订单
	 * @param array $orders
	 */
	public function setOrderIdList($orders){
		$this->_orderIdList = $orders;
		return $this;
	}
	
	/**
	 * @desc 设置请求参数
	 * @param array $this->_orderIdList
	 * @see PlatformApiInterface::setRequest()
	 */
	public function setRequest(){
		$request = array(
			'OrderIdList'      => str_replace('"', '', json_encode($this->_orderIdList)),
		);
		$this->request = $request;
		return $this;
	}

	/**
	 * @desc 设置请求参数
	 * @param string $this->_orderIdList
	 * @see PlatformApiInterface::setRequest()
	 */
	public function setRequestNew(){
		$request = array(
			'OrderIdList' => '['. $this->_orderIdList .']',
		);
		$this->request = $request;
		return $this;
	}	
	
}