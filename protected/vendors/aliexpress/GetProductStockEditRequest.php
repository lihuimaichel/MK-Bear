<?php
/**
 * @desc 产品下线请求
 * @author tony
 * @since 2015-09-14
 */
class GetProductStockEditRequest extends AliexpressApiAbstract{ 

    /**@var integer 产品ID*/
    public $_productIds = null;
    
    /**@var integer sku*/
    public $_skuId = null;
    
    /**@var integer 产品库存*/
    public $_ipmSkuStock = null;
    
    /**
     * @desc 设置产品id
     * @param integer $cateId
     */
    public function setPrdouctID($productIds){
        $this->_productIds = $productIds;
    }
    
    /**
     * @desc 设置产品id
     * @param integer $cateId
     */
    public function setSkuID($skuId){
    	$this->_skuId = $skuId;
    }
    
    /**
     * @desc 设置产品id
     * @param integer $cateId
     */
    public function setIpmSkuStock($ipmSkuStock){
    	$this->_ipmSkuStock = $ipmSkuStock;
    }
    
    
    public function setApiMethod(){
        $this->_apiMethod = 'api.offlineAeProduct';
    }
    
    /**
     * @desc 设置请求参数
     * @see PlatformApiInterface::setRequest()
     */
    public function setRequest(){
        $request = array(
        		'productId'=>$this->_productIds,
        		'ipmSkuStock'=>$this->_ipmSkuStock,
        		'skuId'=>$this->_skuId,
        );
       
        $this->request = $request;
        return $this;
    }
}