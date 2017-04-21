<?php
/**
 * @desc 下线产品变种
 * @author lihy
 *
 */
class DisabledProductVariantRequest extends JoomApiAbstract {
	private $_sku;
	/**
	 * @desc 设置endpoint
	 * @see JoomApiAbstract::setEndpoint()
	 */
	public function setEndpoint(){
		parent::setEndpoint('variant/disable', true);
	}
	
	public function setRequest(){
		$this->request = array(
								'sku'=>$this->_sku
						);
		return $this;
	}
	
	public function setSku($sku){
		$this->_sku = $sku;
		return $this;
	}
}