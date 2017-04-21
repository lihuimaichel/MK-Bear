<?php
/**
 * @desc 更改产品库存
 * @author lihy
 *
 */
class UpdateInventoryRequest extends WishApiAbstract {
	private $_sku;
	private $_inventory;
	private $_enabled;
	/**
	 * @desc 设置endpoint
	 * @see WishApiAbstract::setEndpoint()
	 */
	public function setEndpoint(){
		parent::setEndpoint('variant/update-inventory', true);
	}
	
	public function setRequest(){
		$this->request = array(
								'sku'=>$this->_sku,
								'inventory'=>$this->_inventory
						);
		if(!is_null($this->_enabled)){
			$this->request['enabled'] = $this->_enabled;
		}
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
		$this->_enabled = $enabled ? 'True' : 'False';
		return $this;
	}
}