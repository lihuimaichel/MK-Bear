<?php
/**
 * @desc 获取分组
 * @author liutf
 * @since 2015-09-23
 */
class EditSingleSkuPriceRequest extends AliexpressApiAbstract{ 
    
	/**@var Long 需修改编辑的商品ID*/
	public $_productId	= '';
	/**@var String 需修改编辑的商品单个SKUID*/
	public $_skuId = '';
	/**@var String 修改编辑后的商品价格*/
	public $_skuPrice = '';
	
    public function setApiMethod(){
        $this->_apiMethod = 'api.editSingleSkuPrice';
    }
   
    public function setRequest(){
        $request = array(
                'productId'         => $this->_productId,
        		'skuId'           	=> $this->_skuId,
        		'skuPrice'          => $this->_skuPrice
        );
        $this->request = $request;
        return $this;
    }
    
    /**
     * @desc 设置修改商品ID
     * @param long $startTime
     */
    public function setProductID($productID){
    	$this->_productId = $productID;
    }
    
    /**
     * @desc 设置修改商品ID
     * @param long $startTime
     */
    public function setSkuID($skuID){
    	if (!empty($skuID)) {
    		$this->_skuId = $skuID;
    	}
    }
    
    /**
     * @desc 设置修改商品ID
     * @param long $startTime
     */
    public function setSkuPrice($SkuPrice){
    	$this->_skuPrice = $SkuPrice;
    }
    
}