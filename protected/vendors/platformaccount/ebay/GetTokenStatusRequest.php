<?php
/**
 * @DESC 获取token状态
 * @author hanxy
 *
 */
class GetTokenStatusRequest extends EbayApiAbstract{
    private $_eBayAuthToken = '';
	private $_Version = null;
	public $_verb = 'GetTokenStatus';
    public function setRequest(){
    	$request = array(
            'RequesterCredentials' => array(
                'eBayAuthToken' => $this->_eBayAuthToken
            ),
    	);
    	
    	if(!is_null($this->_Version)) $request['Version'] = $this->_Version;
    	$this->request = $request;
    	$this->_callType = "";//改变
    	return $this;
    }

    
    public function setEBayAuthToken($eBayAuthToken){
    	$this->_eBayAuthToken = $eBayAuthToken;
    	return $this;
    }
    
    /**
     * @override 重写
     * @desc 将请求参数转化为Xml
     */
    public function getRequestXmlBody(){
    	$xmlGeneration = new XmlGenerator();
    	$xml = $xmlGeneration->XmlWriter()->push($this->getXmlRequestHeader(), array('xmlns' => 'urn:ebay:apis:eBLBaseComponents'))
					    	->buildXMLFilterMulti($this->getRequest())
					    	->pop()
					    	->getXml();
    	//print_r($xml);
    	return $xml;
    }
}