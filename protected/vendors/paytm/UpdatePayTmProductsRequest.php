<?php
/**
 * @desc 修改PayTm单个产品状态或者数据
 * @author AjunLongLive!
 * @since 2017-03-09
 */
class UpdatePayTmProductsRequest extends PaytmApiAbstract {
	

	/*提交参数设置*/
	protected  $_product_id                        = null;     //id
	protected  $_product_paytm_sku                 = null;     //paytm_sku
	protected  $_product_sku                       = null;     //sku
	protected  $_product_mrp                       = null;     //mrp
	protected  $_product_qty                       = null;     //qty
	protected  $_product_price                     = null;     //price
	protected  $_product_status                    = null;     //status
	protected  $_product_name                      = null;     //name
	protected  $_product_shipping_charge           = null;     //shipping_charge
	protected  $_product_max_dispatch_time         = null;     //max_dispatch_time
	protected  $_product_return_in_days            = null;     //return_in_days
	protected  $dataArr                            = array();  //用于批量提交产品更新

    /**
     * @desc 初始化对象
     */
    public function __construct() {
        parent::__construct();
    }

	/**
	 * 保存每个产品的更新数据
	 * 
	 */
	public function addEachProductParamsToRequestArray() {
		$request = array();			
		if (!is_null($this->_product_status)) {
			$request['status'] = $this->_product_status;
		}			
		if (!is_null($this->_product_id)) {
			$request['id'] = $this->_product_id;
		}			
		if (!is_null($this->_product_mrp)) {
			$request['mrp'] = $this->_product_mrp;
		}
		if (!is_null($this->_product_qty)) {
		    $request['qty'] = $this->_product_qty;
		}		
		if (!is_null($this->_product_name)) {
			$request['name'] = $this->_product_name;
		}			
		if (!is_null($this->_product_paytm_sku)) {
			$request['paytm_sku'] = $this->_product_paytm_sku;
		}			
		if (!is_null($this->_product_price)) {
			$request['price'] = $this->_product_price;
		}			
		if (!is_null($this->_product_shipping_charge)) {
			$request['shipping_charge'] = $this->_product_shipping_charge;
		}			
		if (!is_null($this->_product_sku)) {
			$request['sku'] = $this->_product_sku;
		}
		if (!is_null($this->_product_max_dispatch_time)) {
		    $request['max_dispatch_time'] = $this->_product_max_dispatch_time;
		}
		if (!is_null($this->_product_return_in_days)) {
		    $request['return_in_days'] = $this->_product_return_in_days;
		}		
		$this->dataArr[] = $request;
	}	
	
	/**
	 * set productMaxDispatchTime
	 * @param int $productMaxDispatchTime
	 */
	public function setProductMaxDispatchTime($productMaxDispatchTime) {
	    $this->_product_max_dispatch_time = $productMaxDispatchTime;
	    return $this;
	}
	
	/**
	 * set productReturnInDays
	 * @param int $productReturnInDays
	 */
	public function setProductReturnInDays($productReturnInDays) {
	    $this->_product_return_in_days = $productReturnInDays;
	    return $this;
	}	
	
	/**
	 * set productId
	 * @param int $productId
	 */
	public function setProductId($productId) {
	    $this->_product_id = $productId;
	    return $this;
	}
	
	/**
	 * set productPaytmSku
	 * @param int $productPaytmSku
	 */
	public function setProductPaytmSku($productPaytmSku) {
	    $this->_product_paytm_sku = $productPaytmSku;
	    return $this;
	}	
	
	/**
	 * set productSku
	 * @param int $productSku
	 */
	public function setProductSku($productSku) {
	    $this->_product_sku = $productSku;
	    return $this;
	}	
	
	/**
	 * set productMrp
	 * @param int $productMrp
	 */
	public function setProductMrp($productMrp) {
	    $this->_product_mrp = $productMrp;
	    return $this;
	}	
	
	/**
	 * set productMrp
	 * @param int $productMrp
	 */
	public function setProductQty($productQty) {
	    $this->_product_qty = $productQty;
	    return $this;
	}	
	
	/**
	 * set productPrice
	 * @param int $productPrice
	 */
	public function setProductPrice($productPrice) {
	    $this->_product_price = $productPrice;
	    return $this;
	}	
	
	/**
	 * set productName
	 * @param int $productName
	 */
	public function setProductName($productName) {
	    $this->_product_name = $productName;
	    return $this;
	}	
	
	/**
	 * set productShippingCharge
	 * @param STRING $productShippingCharge
	 */
	public function setProductShippingCharge($productShippingCharge) {
	    $this->_product_shipping_charge = $productShippingCharge;
	    return $this;
	}	
	
	/**
	 * set productStatus
	 * @param int $productStatus
	 */
	public function setStatus($productStatus) {
	    $this->_product_status = $productStatus;
	    return $this;
	}	
	
	
    /**
     * @desc 设置账号信息
     * @param int $accountID
     * @see PaytmApiAbstract::setAccount()
     */
    public function setAccount($accountID){
        parent::setAccount($accountID);
		$this->_baseUrl   = $this->paytmKeys['catalogUrl'];
		$this->_isPost    = true;  //POST

        return $this;
    }  

    /**
     * 设置EndPoint
     * @see PaytmApiAbstract::setEndPoint()
     */
    public function setEndPoint() {
    	$this->_endpoint = 'v1/merchant/'.$this->_merchantID.'/product.json?authtoken=' . $this->_accessToken;
    }

	
	/**
	 * @desc 设置请求参数
	 * @see PlatformApiInterface::setRequest()
	 */
	public function setRequest(){
	    $data = array();	
		$data['data'] = $this->dataArr;
		$this->request = $data;
		if (!empty($_REQUEST['debug'])){
		    print_r($this->request);
		}		
		return $this;
	}	
}