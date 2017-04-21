<?php
/**
 * @desc 刊登广告(一口价,多属性)
 * @author Gordon
 * @since 2015-06-02
 */
class AddFixedPriceItemRequest extends EbayApiAbstract{
	
	private $_itemInfo = array();
	
	protected $_verb = "AddFixedPriceItem";
	
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
		$xml = $xmlGeneration->XmlWriter()->push($this->getXmlRequestHeader(), array('xmlns' => $this->_xmlsn))
							->buildXMLFilterMulti($this->getRequest())
							->pop()
							->getXml();
		return $xml;
	}
}