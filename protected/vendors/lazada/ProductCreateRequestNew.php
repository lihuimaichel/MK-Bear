<?php
/**
 * @desc 新接口刊登接口
 * @author hanxy
 * @since 2016-12-16
 */
class ProductCreateRequestNew extends LazadaNewApiAbstract{
    
    public $_apiMethod = 'CreateProduct';
    
    /**@var 刊登主分类*/
    public $_PrimaryCategory = null;
    
    /**@var spu id*/
    public $_SPUId = null;
    
    /**@var 已经存在于系统中的产品的唯一标识符*/
    public $_AssociatedSku = null;
    
    /**@var 产品的所有共同属性*/
    public $_Attributes = null;
    
    /**@var sku*/
    public $_Skus = null;
    
    public $requestProducts = array();
    
    /**
     * @desc 设置分类
     * @param Integer $categoryID
     */
    public function setPrimaryCategory($categoryID){
        $this->_PrimaryCategory = $categoryID;
    }
    
    /**
     * @desc 设置spu
     * @param Integer $spuID
     */
    public function setSPUId($spuID){
        $this->_SPUId = $spuID;
    }
    
    /**
     * @desc 已经存在于系统中的产品的唯一标识符
     * @param string $associatedSku
     */
    public function setAssociatedSku($associatedSku){
        $this->_AssociatedSku = $associatedSku;
    }
    
    /**
     * @desc 设置产品的所有共同属性
     * @param subsection $attributes
     */
    public function setAttributes($attributes){
        $this->_Attributes = $attributes;
    }
    
    /**
     * @desc 设置sku信息  一个数组包含至少一个SKU
     * @param subsection $skus
     */
    public function setSkus($skus){
        $this->_Skus = $skus;
    }
    
    /**
     * @desc 将产品推入
     */
    public function push(){
        if(!isset($this->requestProducts)){
            $this->requestProducts = array();
        }

        $dataKeys = array(
            'PrimaryCategory', 'SPUId', 'AssociatedSku', 'Attributes', 'Skus'
        );
        $dataValue = array(
            $this->_PrimaryCategory, $this->_SPUId, $this->_AssociatedSku, $this->_Attributes, $this->_Skus
        );

        $this->requestProducts[] = array_combine($dataKeys, $dataValue);
    }
    
    /**
     * @desc 设置请求参数
     * @see PlatformApiInterface::setRequest()
     */
    public function setRequest(){
        $xmlGeneration = new XmlGenerator();
        $productXml = $xmlGeneration->buildXMLFilterMultiNew($this->requestProducts, 'Product')->pop()->getXml();
        $this->request = array($productXml);
        return $this;
    }
}