<?php
class FindAeProductByIdRequest extends AliexpressApiAbstract {
	
	protected $_productId = '';
	
	public function setRequest() {
		$request = array();
		$request['productId'] = $this->_productId;
		$this->request = $request;
		return $this;
	}
	
	public function setProductId($id) {
		$this->_productId = $id;
	}
	
	public function setApiMethod(){
		$this->_apiMethod = 'api.findAeProductById';
	}	
}