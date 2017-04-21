<?php
class GetCategoryAttributesRequest extends LazadaApiAbstract {
	
	/** @var integer 主分类ID **/
	protected $_primaryCategory = null;
	
	/** @var string 请求的Action名 **/
	public $_apiMethod = 'GetCategoryAttributes';
	
	/** @var string 请求方式 **/
	public $_httpMethod = 'GET';	
	
	/**
	 * @desc 设置请求参数
	 * @see PlatformApiInterface::setRequest()
	 */
	public function setRequest() {
		$request = array();
		if (!is_null($this->_primaryCategory))
			$request['PrimaryCategory'] = $this->_primaryCategory;
		$this->request = $request;
		return $this;
	}
	
	/**
	 * @desc 设置主分类ID
	 * @param integer $ID
	 */
	public function setPrimaryCategory($ID) {
		$this->_primaryCategory = $ID;
	}
	
	/**
	 * @desc 获取主分类ID
	 * @return integer
	 */
	public function getPrimaryCategory() {
		return $this->_primaryCategory;
	}
}