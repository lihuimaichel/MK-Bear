<?php
/**
 * 查询物流追踪信息
 * api.queryTrackingResult
 * @author	Rex
 * @since	2015-9-16
 */

class QueryTrackingResultRequest extends AliexpressApiAbstract {
	
	/**@var string 物流服务 */
	public $_serviceName = NULL;
	
	/**@var string 物流追踪号 */
	public $_logisticsNo = NULL;
	
	/**@var string 交易订单收货国家(简称) */
	public $_toArea = NULL;
	
	/**@var string 订单来源 */
	public $_origin = 'ESCROW';
	
	/**@var string 平台订单号 */
	public $_outRef = NULL;
	
	/**
	 * set serviceName
	 * @param	string	$serviceName
	 */
	public function setServiceName($serviceName) {
		$this->_serviceName = $serviceName;
	}
	
	/**
	 * set logisticsNo
	 * @param	string	$logisticsNo
	 */
	public function setLogisticsNo($logisticsNo) {
		$this->_logisticsNo = $logisticsNo;
	}
	
	/**
	 * set toArea
	 * @param	string	$toArea
	 */
	public function setToArea($toArea) {
		$this->_toArea = $toArea;
	}
	
	/**
	 * set order.platform_order_id
	 * @param	string	$outRef
	 */
	public function setOutRef($outRef) {
		$this->_outRef = $outRef;
	}
	
	public function setApiMethod() {
		$this->_apiMethod = 'api.queryTrackingResult';
	}
	
	/**
	 * 设置请求参数
	 */
	public function setRequest(){
		$requst = array(
				'serviceName'	=> $this->_serviceName,
				'logisticsNo'	=> $this->_logisticsNo,
				'toArea'		=> $this->_toArea,
				'origin'		=> $this->_origin,
				'outRef'		=> $this->_outRef
		);
		$this->request = $requst;
		return $this;
	}
	
}
