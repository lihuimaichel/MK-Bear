<?php
/**
 * @DESC 获取sessionID
 * @author lihy
 *
 */
class GetSessionIDRequest extends EbayApiAbstract{
    private $_RuName = '';
	private $_Version = null;
	public $_verb = 'GetSessionID';
    public function setRequest(){
    	$request = array(
    			'RuName'	=>	$this->_RuName
    	);
    	
    	if(!is_null($this->_Version)) $request['Version'] = $this->_Version;
    	$this->request = $request;
    	$this->_callType = "";//改变
    	return $this;
    }

    
    public function setRuName($ruName){
    	$this->_RuName = $ruName;
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