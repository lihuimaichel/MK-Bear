<?php
/**
 * @desc 抓取返回的TrackingCode和PackageId
 * @author ltf
 * @since 2015.11.10
 */
class GetMultipleOrderItemsRequestNew extends LazadaNewApiAbstract{
    
	public $_apiMethod = 'GetMultipleOrderItems';
	
	/**@var 标记的订单*/
	public $_orderIdList = null;//string
	
	public $_httpMethod = 'GET';
	
	/**
	 * @desc 设置订单
	 * @param string $orderIdList
	 */
	public function setOrderIdList($orderIdList){
		$this->_orderIdList = $orderIdList;
		return $this;
	}
	
	/**
	 * @desc 设置请求参数
	 * @see PlatformApiInterface::setRequest()
	 */
	public function setRequest(){
		$request = array(
			'OrderIdList' => '['. $this->_orderIdList .']',
		);
		$this->request = $request;
		return $this;
	}
	
}