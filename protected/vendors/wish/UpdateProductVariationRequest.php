<?php
/**
 * @desc 更改产品
 * @author lihy
 *
 */
class UpdateProductVariationRequest extends WishApiAbstract {
	private $_sku;
	private $_inventory = NULL;
	private $_enabled = NULL;
	// ... 
	/**
	 * @desc 设置endpoint
	 * @see WishApiAbstract::setEndpoint()
	 */
	public function setEndpoint(){
		parent::setEndpoint('variant/update', true);
	}
	
	public function setRequest(){
		 $request = array(
								'sku'=>$this->_sku,
						);
		 if($this->_inventory !== NULL)
		 	$request['inventory'] = $this->_inventory;
		 if($this->_enabled !== NULL)
		 	$request['enabled'] = $this->_enabled ? 'True' : 'False' ;
		 	
		 $this->request = $request;
		return $this;
	}
	
	public function setSku($sku){
		$this->_sku = $sku;
		return $this;
	}
	
	public function setInventory($inventory){
		$this->_inventory = $inventory;
		return $this;
	}
	
	public function setEnabled($enabled){
		$this->_enabled = $enabled;
		return $this;
	}
}