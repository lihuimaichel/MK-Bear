<?php
/**
 * @desc PayTm拉取产品列表
 * @author AjunLongLive!
 * @since 2017-03-09
 */
class GetPayTmProductsRequest extends PaytmApiAbstract {
	

	/*提交参数设置*/
	protected  $_product_status                            = null;  //状态
	protected  $_product_is_in_stock                       = null;  //是否有库存
	protected  $_product_stock                             = null;  //是否获取库存
	protected  $_product_limit                             = null;  //获取多少条
	protected  $_product_after_id                          = null;  //获取给定的产品id之后的产品
	protected  $_product_before_id                         = null;  //获取给定的产品id之前的产品
	protected  $_product_columns                           = null;  //获取产品图片
	protected  $_product_skus                              = null;  //只拉取给定的SKU
	protected  $_product_type                              = null;  //拉取产品的类型，比如是否简化拉取

    /**
     * @desc 初始化对象
     */
    public function __construct() {
        parent::__construct();
    }

	/**
	 * set productType
	 * @param int $productType
	 */
	public function setProductType($productType) {
	    $this->_product_type = $productType;
	    return $this;
	}	
	
	/**
	 * set productIsInStock
	 * @param int $productIsInStock
	 */
	public function setProductIsInStock($productIsInStock) {
	    $this->_product_is_in_stock = $productIsInStock;
	    return $this;
	}
	
	/**
	 * set productStock
	 * @param int $productStock
	 */
	public function setProductStock($productStock) {
	    $this->_product_stock = $productStock;
	    return $this;
	}	
	
	/**
	 * set productLimit
	 * @param int $productLimit
	 */
	public function setProductLimit($productLimit) {
	    $this->_product_limit = $productLimit;
	    return $this;
	}	
	
	/**
	 * set productAfterId
	 * @param int $productAfterId
	 */
	public function setProductAfterId($productAfterId) {
	    $this->_product_after_id = $productAfterId;
	    return $this;
	}	
	
	/**
	 * set productBeforeId
	 * @param int $productBeforeId
	 */
	public function setProductBeforeId($productBeforeId) {
	    $this->_product_before_id = $productBeforeId;
	    return $this;
	}	
	
	/**
	 * set productColumns
	 * @param int $productColumns
	 */
	public function setProductColumns($productColumns) {
	    $this->_product_columns = $productColumns;
	    return $this;
	}	
	
	
	/**
	 * set productSkus
	 * @param STRING $productSkus
	 */
	public function setProductSkus($productSkus) {
	    $this->_product_skus = $productSkus;
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
		$this->_isPost    = false;  //GET

        return $this;
    }  
    
    /**
     * @desc 获取merchantID
     * @param 
     * @see int merchantID
     */
    public function getMerchantID(){        
        return $this->_merchantID;
    }

    /**
     * 设置EndPoint
     * @see PaytmApiAbstract::setEndPoint()
     */
    public function setEndPoint() {
    	$this->_endpoint = 'v1/merchant/'.$this->_merchantID.'/catalog.json?authtoken=' . $this->_accessToken;
    }
	
	/**
	 * @desc 设置请求参数
	 * @see PlatformApiInterface::setRequest()
	 */
	public function setRequest(){
		$request = array();			
		if (!is_null($this->_product_status)) {
			$request['status'] = $this->_product_status;
		}			
		if (!is_null($this->_product_is_in_stock)) {
			$request['is_in_stock'] = $this->_product_is_in_stock;
		}			
		if (!is_null($this->_product_stock)) {
			$request['stock'] = $this->_product_stock;
		}
		if (!is_null($this->_product_limit)) {
		    $request['limit'] = $this->_product_limit;
		}
		if (!is_null($this->_product_after_id)) {
			$request['after_id'] = $this->_product_after_id;
		}			
		if (!is_null($this->_product_before_id)) {
			$request['before_id'] = $this->_product_before_id;
		}			
		if (!is_null($this->_product_columns)) {
			$request['columns'] = $this->_product_columns;
		}			
		if (!is_null($this->_product_skus)) {
			$request['skus'] = $this->_product_skus;
		}
		if (!is_null($this->_product_type)) {
		    $request['product_type'] = $this->_product_type;
		}
		$this->request = $request;
		return $this;
	}	
}