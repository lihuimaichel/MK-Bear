<?php
/**
 * 
 * @desc 获取账号产品信息模块接口
 * @author	tan
 * @since	2015-9-18
 */

class FindAeProductModuleByIdRequest extends AliexpressApiAbstract {
	
	/**@var string 产品信息模块的状态 **/
	protected $_moduleId = null;
	
	public function setApiMethod() {
		$this->_apiMethod = 'api.findAeProductModuleById';
	}
	
	/**
	 * @desc set module id
	 * @param unknown moduleID
	 */
	public function setModuleID($moduleID) {
		$this->_moduleId = $moduleID;
	}

	/**
	 * (non-PHPdoc)
	 * @see PlatformApiInterface::setRequest()
	 */
	public function setRequest() {
		$request['moduleId'] = $this->_moduleId;
		$this->request = $request;
		return $this;
	}
	
}