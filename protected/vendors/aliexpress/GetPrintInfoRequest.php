<?php
/**
 * 获取线上发货标签
 * api.getPrintInfo
 * @author	Rex
 * @since	2015-9-23
 */

class GetPrintInfoRequest extends AliexpressApiAbstract {
	
	/**@var string 国际运单号*/
	public $_internationalLogisticsId = NULL;
	
	public function setApiMethod() {
		$this->_apiMethod = 'api.getPrintInfo';
	}
	
	/**
	 * 设置国际运单号
	 * @param	string	$internationalLogisticsId
	 */
	public function setInternationalLogisticsId($internationalLogisticsId) {
		$this->_internationalLogisticsId = $internationalLogisticsId;
	}
	
	public function setRequest() {
		$request = array(
				'internationalLogisticsId'	=> $this->_internationalLogisticsId
		);
		$this->request = $request;
		return $this;
	}
	
}