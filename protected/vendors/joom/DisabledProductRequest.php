<?php
/**
 * @desc 下线产品
 * @author lihy
 *
 */
class DisabledProductRequest extends JoomApiAbstract {
	private $_sku;
	/**
	 * @desc 设置endpoint
	 * @see JoomApiAbstract::setEndpoint()
	 */
	public function setEndpoint(){
		parent::setEndpoint('product/disable', true);
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