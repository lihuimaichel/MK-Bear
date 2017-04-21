<?php
/**
 * @desc 获取产品分类属性
 * @author lihy
 * @since 2015-06-02
 */
class GetCategoryFeaturesRequest extends EbayApiAbstract{
    public $_verb = "GetCategoryFeatures";
	private $_categoryID = null;
	private $_featureIDs = null;
	/**
	 * @desc 设置请求参数
	 * @see PlatformApiInterface::setRequest()
	 */
    public function setRequest(){
    	$request = array(
    			'RequesterCredentials' => array(
    					'eBayAuthToken' => $this->_usertoken
    			),
    			'DetailLevel' => 'ReturnAll',
    			'ViewAllNodes' => 'true',
    			'CategoryID' => $this->_categoryID
    	);
    	if($this->_featureIDs){
    		$request['FeatureID'] = $this->_featureIDs;
    	}
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
    
    /**
     * @desc 设置特征ids
     * @param unknown $featureIDs
     * @return GetCategoryFeaturesRequest
     */
    public function setFeatureIDs($featureIDs){
    	$this->_featureIDs = $featureIDs;
    	return $this;
    }
    
    /**
     * @override 重写
     * @desc 将请求参数转化为Xml
     */
    public function getRequestXmlBody(){
    	$xmlGeneration = new XmlGenerator();
    	return $xmlGeneration->XmlWriter()->push($this->getXmlRequestHeader(), array('xmlns' => 'urn:ebay:apis:eBLBaseComponents'))
    	->buildXMLFilterMulti($this->getRequest())
    	->pop()
    	->getXml();
    }
}