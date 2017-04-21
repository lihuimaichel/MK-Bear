<?php
/**
 * 
 * @desc 获取账号产品信息模块接口
 * @author	tan
 * @since	2015-9-18
 */

class FindAeProductDetailModuleListByQureyRequest extends AliexpressApiAbstract {
	
	/**@var string 产品信息模块的状态 **/
	protected $_moduleStatus = null;
	
	/**@var string 产品信息模块的类型 **/
	protected $_type = null;
	
	/**@var integer 当前页数 **/
	protected $_pageIndex = null;
	
	const MODULE_STATUS_TBD		 		 = 'tbd' ;  		   //审核不通过
	const MODULE_STATUS_AUDITING		 = 'auditing';	       //审核中
	const MODULE_STATUS_APPROVED		 = 'approved' ;	       //审核通过
	
	const MODULE_TYPE_CUSTOM			 = 'custom';		   //自定义模块
	const MODULE_TYPE_RELATION			 = 'relation';		   //关联模块	
	
	public function setApiMethod() {
		$this->_apiMethod = 'api.findAeProductDetailModuleListByQurey';
	}
	
	/**
	 * @desc set module satatus
	 * @param unknown $status
	 */
	public function setModuleStatus($status) {
		$this->_moduleStatus = $status;
	}
	
	/**
	 * @desc set type
	 * @param unknown $type
	 */
	public function setType($type) {
		$this->_type = $type;
	}
	
	/**
	 * @desc set page index
	 * @param unknown $index
	 */
	public function setPageIndex($index) {
		$this->_pageIndex = $index;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see PlatformApiInterface::setRequest()
	 */
	public function setRequest() {
		$request['moduleStatus'] = $this->_moduleStatus;
		$request['pageIndex'] = $this->_pageIndex;
		if (!is_null($this->_type))
			$request['type'] = $this->_type;
		$this->request = $request;
		return $this;
	}
	
}