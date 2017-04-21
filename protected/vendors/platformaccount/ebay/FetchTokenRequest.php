<?php
/**
 * @DESC 获取token
 * @author lihy
 *
 */
class FetchTokenRequest extends EbayApiAbstract{
    private $_sessionID = '';
	private $_Version = null;
	public $_verb = 'FetchToken';
    public function setRequest(){
    	$request = array(
    			'SessionID'	=>	$this->_sessionID
    	);
    	
    	if(!is_null($this->_Version)) $request['Version'] = $this->_Version;
    	$this->request = $request;
    	$this->_callType = "";//改变
    	return $this;
    }

    public function setSessionID($sessionID){
    	$this->_sessionID = $sessionID;
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