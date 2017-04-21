<?php
/**
 * @desc 获取站点规则,配置信息
 * @author lihy
 * @since 2016-04-20
 */
class GeteBayDetailsRequest extends EbayApiAbstract{
    public $_verb = "GeteBayDetails";
    public function setRequest(){
    	$request = array(
    			'RequesterCredentials' => array(
    					'eBayAuthToken' => $this->_usertoken
    			),
    			'DetailName'=>array(
    					'ShippingServiceDetails',
    					'SiteDetails',
    					'ShippingLocationDetails',
    					'ReturnPolicyDetails',
    					'ExcludeShippingLocationDetails',
    					'CountryDetails'
    			),
    	);
    	$this->setIsXML(1);
    	$this->request = $request;
    	return $this;
    }
    
    
    public function getRequestXmlBody(){
    	$xmlGeneration = new XmlGenerator();
    	return $xmlGeneration->XmlWriter()->push($this->getXmlRequestHeader(), array('xmlns' => $this->_xmlsn))
    	->buildXMLFilterMulti($this->getRequest())
    	->pop()
    	->getXml();
    }
}