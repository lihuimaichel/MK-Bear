<?php
/**
 * @desc 下线产品
 * @author lihy
 *
 */
class DisabledProductRequest extends WishApiAbstract {
	private $_sku;
	/**
	 * @desc 设置endpoint
	 * @see WishApiAbstract::setEndpoint()
	 */
	public function setEndpoint(){
		parent::setEndpoint('product/disable', false);
	}
	
	public function setRequest(){
		$this->request = array(
								'parent_sku'=>$this->_sku
						);
		return $this;
	}
	
	public function setSku($sku){
		$this->_sku = $sku;
		return $this;
	}
}