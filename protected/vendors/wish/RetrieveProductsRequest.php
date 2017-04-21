<?php
class RetrieveProductsRequest extends WishApiAbstract {

	/**@var string 交互Endpoint*/
	public $_endpoint = null;
	private $_parentSku;
	/**
	 * @desc 设置endpoint
	 * @see WishApiAbstract::setEndpoint()
	 */
	public function setEndpoint(){
		parent::setEndpoint('product', false);
	}
	
	public function setParentSku($sku){
		$this->_parentSku = $sku;
		return $this;
	}
	
	/**
	 * @desc 设置请求参数
	 * @see PlatformApiInterface::setRequest()
	 */
	public function setRequest(){
		$request = array(
				'parent_sku'=>$this->_parentSku
		);
		$this->request = $request;
		return $this;
	}	
}