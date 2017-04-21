<?php

 /**
  * @desc   编辑商品的分类对应的多属性 (categoryId: 分类id; productSkus: 商品的sku属性； productProperties: 商品的多属性)
  * @author AjunLongLive!
  * @since  2017-04-12
  */
 

class EditProductSkusAndPropertiesRequest extends AliexpressApiAbstract{ 
    
	/**@var Long 需修改编辑的商品ID*/
	public $_productId	= null; 
	/**@var 分类id*/
	public $_categoryId	= null; 
	/**@var 商品的sku属性*/
	public $_productSkus = null;
	/**@var 商品的多属性*/
	public $_productProperties = null;
	
    public function setApiMethod(){
        $this->_apiMethod = 'api.editProductCidAttIdSku';
    }
   
    public function setRequest(){
        $request = array();
        if (!is_null($this->_productId))
            $request['productId'] = $this->_productId;
        if (!is_null($this->_categoryId))
            $request['categoryId'] = $this->_categoryId;
        if (!is_null($this->_productSkus))
            $request['productSkus'] = json_encode($this->_productSkus);
        if (!is_null($this->_productProperties))
            $request['productProperties'] = json_encode($this->_productProperties);      
        $this->request = $request;
        return $this;
    }
    
    /**
     * @desc 设置商品ID
     * @param long $productID
     */
    public function setProductID($productID){
    	$this->_productId = $productID;
    }
    
    /**
     * @desc 设置分类id
     * @param string $fiedName
     */
    public function setCategoryId($categoryId){
		$this->_categoryId = $categoryId;
    }
    
    /**
     * @desc 设置商品的sku属性
     * @param string $fiedName
     */
    public function setProductSkus($productSkus){
		$this->_productSkus = $productSkus;
    }
    
    /**
     * @desc 设置商品的多属性
     * @param string $_fiedValue
     */
    public function setProductProperties($productProperties){
    	$this->_productProperties = $productProperties;
    }
    
    /**
     * @desc    获取错误的中文解释信息
     * @param   $erroCode
     * @return  详细的错误解释
     */
    public function getErrorDetail($erroCode) {
        $errorArray = array(
            '13004020' =>	'当前用户不在海外仓白名单内, 不允许编辑带海外仓属性的商品.',
            '13001041' =>	'TBD产品不能被编辑',
            '13null'   =>	'拷贝主图或者详情中的图片失败。现在暂时为这个错误码，我们会在下一版中给出具体的错误码。'
        );
        if (isset($errorArray[$erroCode])){
            return $errorArray[$erroCode];
        } else {
            return '未知错误，请联系技术！';
        }
   }            
}