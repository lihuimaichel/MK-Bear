<?php
/**
 * @desc 刊登接口
 * @author Gordon
 * @since 2015-08-19
 */
class ProductCreateRequest extends LazadaApiAbstract{
    
    public $_apiMethod = 'ProductCreate';
    
    /**@var 刊登加密sku*/
    public $_sellerSku = null;
    
    /**@var 刊登加密主sku*/
    public $_parentSku = null;
    
    /**@var 刊登Tile*/
    public $_name = null;
    
    /**@var 刊登属性*/
    public $_variation = null;
    
    /**@var 刊登主分类*/
    public $_primaryCategory = null;
    
    /**@var 关联分类*/
    public $_categories = null;
    
    /**@var 描述*/
    public $_description = null;
    
    /**@var 品牌*/
    public $_brand = null;
    
    /**@var 卖价*/
    public $_price = null;
    
    /**@var 特殊卖价*/
    public $_salePrice = null;
    
    /**@var 特殊卖价开始时间*/
    public $_saleStartDate = null;
    
    /**@var 特殊卖价结束时间*/
    public $_saleEndDate = null;
    
    /**@var 税类型*/
    public $_taxClass = 'default';
    
    /**@var 运送类型(crossdocking,dropshipping)*/
    public $_shipmentType = null;
    
    /**@var EAN/UPC/ISBN*/
    public $_productId = null;
    
    /**@var new,used,refurbished*/
    public $_condition = null;
    
    /**@var 属性*/
    public $_productData = null;
    
    /**@var 售卖数量*/
    public $_quantity = null;
    
    public $requestProducts = array();
    
    /**
     * @desc 设置卖家sku
     * @param string $sellerSku
     */
    public function setSellerSku($sellerSku){
        $this->_sellerSku = $sellerSku;
    }

    /**
     * @desc 设置卖家主sku
     * @param string $sellerSku
     */
    public function setParentSku($parentSku){
        $this->_parentSku = $parentSku;
    }
    
    /**
     * @desc 设置Title
     * @param string $name
     */
    public function setName($name){
        $this->_name = $name;
    }
    
    /**
     * @desc 设置描述
     * @param string $description
     */
    public function setDescription($description){
        $this->_description = $description;
    }
    
    /**
     * @desc 设置主分类
     * @param int $categoryID
     */
    public function setPrimaryCategory($categoryID){
        $this->_primaryCategory = $categoryID;
    }
    
    /**
     * @desc 设置品牌
     * @param string $brand
     */
    public function setBrand($brand){
        $this->_brand = $brand;
    }
    
    /**
     * @desc 设置卖价
     * @param string $price
     */
    public function setPrice($price){
        $this->_price = $price;
    }
    
    /**
     * @desc 设置特殊卖价
     * @param string $price
     */
    public function setSalePrice($price){
        $this->_salePrice = $price;
    }
    
    /**
     * @desc 设置特殊卖价开始时间
     * @param date $time
     */
    public function setSalePriceStartTime($time){
        $this->_saleStartDate = $time;
    }
    
    /**
     * @desc 设置特殊卖价结束时间
     * @param date $time
     */
    public function setSalePriceEndTime($time){
        $this->_saleEndDate = $time;
    }
    
    /**
     * @desc 设置在线库存数
     * @param int $quantity
     */
    public function setQuantity($quantity){
        $this->_quantity = $quantity;
    }
    
    /**
     * @desc 设置属性
     * @param array $productData
     */
    public function setProductData($productData){
        $this->_productData = $productData;
    }

    /**
     * @desc 设置多属性
     */
    public function setVariation($variation){
        $this->_variation = $variation;
    }
    
    /**
     * @desc 将产品推入
     */
    public function push(){
        if(!isset($this->requestProducts)){
            $this->requestProducts = array();
        }

        if(empty($this->_parentSku)) {
            $dataKeys = array(
                'SellerSku', 'Name', 'PrimaryCategory', 'Description', 'Brand', 'Price', 'TaxClass',
                'Quantity', 'ProductData', 'SalePrice', 'SaleStartDate', 'SaleEndDate','Variation'
            );
            $dataValue = array(
                $this->_sellerSku, $this->_name, $this->_primaryCategory, $this->_description, $this->_brand, $this->_price, $this->_taxClass,
                intval($this->_quantity), $this->_productData, $this->_salePrice, $this->_saleStartDate, $this->_saleEndDate,$this->_variation
            );
        } else {
            $dataKeys = array(
                'SellerSku', 'ParentSku', 'Name', 'PrimaryCategory', 'Description', 'Brand', 'Price', 'TaxClass',
                'Quantity', 'ProductData', 'SalePrice', 'SaleStartDate', 'SaleEndDate','Variation'
            );
            $dataValue = array(
                $this->_sellerSku, $this->_parentSku, $this->_name, $this->_primaryCategory, $this->_description, $this->_brand, $this->_price, $this->_taxClass,
                intval($this->_quantity), $this->_productData, $this->_salePrice, $this->_saleStartDate, $this->_saleEndDate,$this->_variation
            );
        }
        $this->requestProducts[] = array_combine($dataKeys, $dataValue);

    }
    
    /**
     * @desc 设置请求参数
     * @see PlatformApiInterface::setRequest()
     */
    public function setRequest(){
        $xmlGeneration = new XmlGenerator();
        $productXml = $xmlGeneration->buildXMLFilter($this->requestProducts, 'Product')->pop()->getXml();
        $this->request = array($productXml);
        return $this; 
        //
    }
}