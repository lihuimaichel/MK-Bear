<?php
/**
 * @desc 修改单个SKU库存
 * @author zhangF
 * @since 2015-09-23
 */
class EditSingleSkuStockRequest extends AliexpressApiAbstract{ 
    
	/**@var Long 需修改编辑的商品ID*/
	public $_productId	= '';
	/**@var String 需修改编辑的商品单个SKUID*/
	public $_skuId = '';
	/**@var String 修改编辑后的商品价格*/
	public $_ipmSkuStock = '';
	
    public function setApiMethod(){
        $this->_apiMethod = 'api.editSingleSkuStock';
    }
   
    public function setRequest(){
        $request = array(
                'productId'         => $this->_productId,
        		'skuId'           	=> $this->_skuId,
        		'ipmSkuStock'       => $this->_ipmSkuStock
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
    	// if (!empty($skuID)) {
    		$this->_skuId = $skuID;
    	// }
    }
    
    /**
     * @desc 设置修改商品ID
     * @param long $startTime
     */
    public function setIpmSkuStock($ipmSkuStock){
    	$this->_ipmSkuStock = $ipmSkuStock;
    }
    
}