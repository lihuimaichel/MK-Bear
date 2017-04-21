<?php
/**
 * @desc 刊登广告(拍卖)
 * @author Gordon
 * @since 2015-06-02
 */
class AddItemRequest extends EbayApiAbstract{
	private $_itemInfo = array();    
    protected $_verb = "AddItem";
    
    public function setRequest(){
    	$requestArr = array(
    			'RequesterCredentials' => array(
    					'eBayAuthToken' =>	$this->getToken() 
    			),
    			'Item' => $this->_itemInfo
    	);
    	$this->request = $requestArr;
    	return $this;
    }
    
    /**
	 * @desc 设置请求参数
	 * @param unknown $requestParam
	 * @return AddItemRequest
	 */
	public function setItemInfo($itemInfo){
		$this->_itemInfo = $itemInfo;
		return $this;
	}
    
	/**
	 * @desc 将请求参数转化为Xml
	 */
	public function getRequestXmlBody(){
		$xmlGeneration = new XmlGenerator();
		return $xmlGeneration->XmlWriter()->push($this->getXmlRequestHeader(), array('xmlns' => $this->_xmlsn))
		->buildXMLFilterMulti($this->getRequest())
		->pop()
		->getXml();
	}
    
}