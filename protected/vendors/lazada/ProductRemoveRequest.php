<?php
class ProductRemoveRequest extends LazadaApiAbstract {
	
	protected $_SellerSkuList = array();
	
	/** @var string 请求的Action名 **/
    protected $_apiMethod = 'ProductRemove';
    
    /** @var string 请求方式 **/
    public $_httpMethod = 'POST';
	
	public function setRequest() {
 		$request = array();
		foreach ($this->_SellerSkuList as $sellerSku)
			$request[]['SellerSku'] = $sellerSku;
		$xmlGeneration = new XmlGenerator();
		$productXml = $xmlGeneration->buildXMLFilter($request, 'Product')->pop()->getXml();
		$this->request = array($productXml);
		return $this;
	}
	
	/**
	 * @desc 设置sku列表
	 * @param array $skuList
	 */
	public function setSellerSkuList($skuList) {
		if (!is_array($skuList))
			$this->_SellerSkuList = array($skuList);
		else
			$this->_SellerSkuList = $skuList;
	}
}