<?php
/**
 * @desc 修改单个产品下面的多个SKU库存
 * @author lihy
 * @since 2016-01-21
 */
class EditMutilpleSkuStocksRequest extends AliexpressApiAbstract{ 
    
	/**@var Long 需修改编辑的商品ID*/
	public $_productId	= '';
	/**@var String 需修改编辑的商品单个SKUID*/
	public $_skuId = '';
	/**@var String 修改编辑后的商品价格*/
	public $_ipmSkuStock = '';
	
	private $_skuStocks = array();
	
    public function setApiMethod(){
        $this->_apiMethod = 'api.editMutilpleSkuStocks';
    }
   
    public function setRequest(){
    	//@TODO 处理sku字符串
    	$skuStocksStr = "";
    	if($this->_skuStocks){
    		$skuIDs = array();
    		foreach ($this->_skuStocks as $sku){
    			$skuIDs[$sku[0]] = $sku[1];
    		}
    		$skuStocksStr = json_encode($skuIDs);
    	}
        $request = array(
                'productId'         => 	(float)$this->_productId,
        		'skuStocks'			=>	$skuStocksStr
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
    public function setIpmSkuStock($ipmSkuStock){
    	$this->_ipmSkuStock = $ipmSkuStock;
    }
    
    public function push(){
    	//if($this->_skuId){
	    	$this->_skuStocks[] = array(
	    					$this->_skuId,
	    					$this->_ipmSkuStock
	   				);
	   		$this->_ipmSkuStock = 0;
	   		$this->_skuId = null;
    	//}
    	return $this;
    }
    
    public function clean(){
    	$this->_skuStocks = array();
    	return $this;
    }
}