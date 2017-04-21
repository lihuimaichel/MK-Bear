<?php
/**
 * @desc 获取产品分类属性
 * @author lihy
 * @since 2015-06-02
 */
class GetCategorySpecificsRequest extends EbayApiAbstract{
    public $_verb = "GetCategorySpecifics";
	private $_categoryID = null;
	/**
	 * @desc 设置请求参数
	 * @see PlatformApiInterface::setRequest()
	 */
    public function setRequest(){
    	$request = array(
    			'RequesterCredentials' => array(
    					'eBayAuthToken' => $this->_usertoken
    			),
    			'CategorySpecific' => array(
    					'CategoryID' => $this->_categoryID,
    			),
    	);
    	$this->request = $request;
    	return $this;
    }
    
    /**
     * @desc 设置分类ID
     * @param unknown $categoryID
     * @return GetCategoryFeaturesRequest
     */
    public function setCategoryID($categoryID){
    	$this->_categoryID = $categoryID;
    	return $this;
    }
}