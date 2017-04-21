<?php
/**
 * 查询物流追踪信息
 * api.listLogisticsService
 * @author	Rex
 * @since	2015-9-16
 */

class ListLogisticsServiceRequest extends AliexpressApiAbstract {
	
	public function setApiMethod() {
		$this->_apiMethod = 'api.listLogisticsService';
	}
	
	/**
	 * 设置请求参数
	 */
	public function setRequest() {
		return $this;
	}
	
}