<?php
/**
 * @desc 更改产品
 * @author lihy
 *
 */
class UpdateProductVariationRequest extends JoomApiAbstract {
	private $_sku;
	private $_inventory = NULL;
	private $_enabled = NULL;
	private $_price = null;
	private $_msrp = null;
	private $_shipping = null;
	// ... 
	/**
	 * @desc 设置endpoint
	 * @see JoomApiAbstract::setEndpoint()
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
		 if($this->_price !== NULL)
		 	$request['price'] = $this->_price;
		
		 if($this->_shipping !== NULL)
		 	$request['shipping'] = $this->_shipping;
		 
		 if($this->_msrp !== NULL)
		 	$request['msrp'] = $this->_msrp;
		 
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
	
	public function setPrice($price){
		$this->_price = $price;
		return $this;
	}
	
	public function setMsrp($msrp){
		$this->_msrp = $msrp;
		return $this;
	}
	
	public function setShipping($shipping){
		$this->_shipping = $shipping;
		return $this;
	}
}