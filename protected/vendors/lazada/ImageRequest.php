<?php
/**
 * @desc 图片上传
 * @author Gordon
 * @since 2015-08-19
 */
class ImageRequest extends LazadaApiAbstract{
    
    public $_apiMethod = 'Image';
    
    /**@var 图片列表*/
    public $_imageList = null;
    
    /**@var 在线sku*/
    public $_sellerSku = null;
    
    public $requestImages = array();

    /**
     * @desc 设置图片
     */
    public function pushImage($url){
        $this->_imageList[] = $url;
    }
    
    /**
     * @desc 设置图片参数
     */
    public function getImageList(){
        $xmlGeneration = new XmlGenerator();
        return $xmlGeneration->buildXMLFilter($this->_imageList, 'Image')->pop()->getXml();
    }
    
    public function cleanImage(){
    	$this->_imageList = null;
    	return $this;
    }
    /**
     * @desc 设置sku
     * @param string $sku
     */
    public function setSellerSku($sku){
        $this->_sellerSku = $sku;
    }
    
    /**
     * @desc 推入数据
     */
    public function push(){
        $this->requestImages[] = array(
                'SellerSku' => $this->_sellerSku,
                'Images'    => $this->getImageList(),
        );
        $this->_sellerSku = $this->_imageList = null;//清空数据
    }
    
    /**
     * @desc 设置请求参数
     * @see PlatformApiInterface::setRequest()
     */
    public function setRequest(){
        $xmlGeneration = new XmlGenerator();
        $productXml = $xmlGeneration->buildXMLFilter($this->requestImages, 'ProductImage')->pop()->getXml();
        $this->request = array($productXml);
        return $this;
    }
}