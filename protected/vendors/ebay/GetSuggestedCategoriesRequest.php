<?php
/**
 * @desc 获取建议分类
 * @author Gordon
 * @since 2015-06-02
 */
class GetSuggestedCategoriesRequest extends EbayApiAbstract{
	/**@var 接口名*/
	public $_verb = 'GetSuggestedCategories';
	public $_query = "";
	/**
	 * @desc 设置请求
	 * @see PlatformApiInterface::setRequest()
	 */
	public function setRequest(){
		$this->setSiteID($this->_siteID);
		$request = array(
				'RequesterCredentials' => array(
						'eBayAuthToken' => $this->getToken(),
				),
				'Query'					=>	$this->_query
		
		);
		
		$this->request = $request;
		return $this;
	}
	
	/**
	 * @desc 设置查询词
	 * @param unknown $query
	 * @return GetSuggestedCategoriesRequest
	 */
	public function setQuery($query){
		$this->_query = $query;
		return $this;
	}
}