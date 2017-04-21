<?php
/**
 * @desc 新接口更新价格和库存
 * @author hanxy
 * @since 2017-01-04
 */
class UpdatePriceQuantityRequestNew extends LazadaNewApiAbstract{
    
    public $_apiMethod = 'UpdatePriceQuantity';
        
    /**@var sku*/
    public $_Skus = null;
    
    public $requestProducts = array();
        
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

        $dataKeys = array('Skus');
        $dataValue = array($this->_Skus);

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