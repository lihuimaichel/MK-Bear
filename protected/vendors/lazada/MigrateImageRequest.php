<?php
/**
 * @desc 新接口图片上传
 * @author hanxy
 * @since 2017-01-12
 */
class MigrateImageRequest extends LazadaNewApiAbstract{
    
    public $_apiMethod = 'MigrateImage';
    
    /**@var 图片列表*/
    public $_imageList = null;
        
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
        return $xmlGeneration->buildXMLFilter($this->_imageList, 'Url')->pop()->getXml();
    }
    
    /**
     * @desc 推入数据
     */
    public function push(){
        $this->requestImages = array('Url' => $this->getImageList());
        $this->_imageList = null;//清空数据
    }
    
    /**
     * @desc 设置请求参数
     * @see PlatformApiInterface::setRequest()
     */
    public function setRequest(){
        $xmlGeneration = new XmlGenerator();
        $productXml = $xmlGeneration->buildXMLFilter($this->requestImages, 'Image')->pop()->getXml();
        $this->request = array($productXml);
        return $this;
    }
}