<?php
class ProductUpdateRequest extends LazadaApiAbstract {
	
	protected $_SellerSku = '';
	
	protected $_Status = '';
	
	protected $_Price = '';
	
        protected $_Quantity = '';
        
	/** @var string 请求的Action名 **/
    protected $_apiMethod = 'ProductUpdate';
    
    /** @var string 请求方式 **/
    public $_httpMethod = 'POST';
	
	public function setRequest() {
 		$request = array();
 		//批量时调用批量方法
 		if(is_array($this->_SellerSku)){
 			return $this->setRequestMulti();
 		}
		$request[$this->_SellerSku]['SellerSku'] = $this->_SellerSku;
		if (!empty($this->_Status)) {
			$request[$this->_SellerSku]['Status'] = $this->_Status;
		}
		if (!empty($this->_Price)) {
			$request[$this->_SellerSku]['Price'] = $this->_Price;
		}
        if (is_integer($this->_Quantity) && $this->_Quantity >= 0 ) {
			$request[$this->_SellerSku]['Quantity'] = $this->_Quantity;
		}
		$xmlGeneration = new XmlGenerator();
		$productXml = $xmlGeneration->buildXMLFilter($request, 'Product')->pop()->getXml();
		$this->request = array($productXml);
		return $this;
	}
	
	/**
	 * @desc 批量设置
	 * @return ProductUpdateRequest
	 */
	public function setRequestMulti() {
		$request = array();
		foreach ($this->_SellerSku as $k=>$v){
			$request[$k]['SellerSku'] = $v;
			if (!empty($this->_Status)) {
				$request[$k]['Status'] = $this->_Status;
			}
			if (!empty($this->_Price)) {
				$request[$k]['Price'] = $this->_Price;
			}
                        if (is_integer($this->_Quantity) && $this->_Quantity >= 0 ) {
				$request[$k]['Quantity'] = $this->_Quantity;
			}
		}
		$xmlGeneration = new XmlGenerator();
		$productXml = $xmlGeneration->buildXMLFilter($request, 'Product')->pop()->getXml();
		$this->request = array($productXml);
		return $this;
	}
	/**
	 * @desc 设置sku
	 * @param array $sku
	 */
	public function setSellerSku($sku) {
		$this->_SellerSku = $sku;
	}
	
	/**
	 * 设置sku状态
	 * @param string $status
	 */
	public function setStatus($status) {
		$this->_Status = $status;
	}
	
	/**
	 * 设置sku价格
	 * @param string $price
	 */
	public function setPrice($price) {
		$this->_Price = $price;
	}
	
	/**
	 * 设置sku数量
	 * @param string $quantity
	 */
	public function setQuantity($quantity) {
		$this->_Quantity = $quantity;
	}
}