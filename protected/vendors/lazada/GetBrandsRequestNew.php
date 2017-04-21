<?php
/**
 * @desc 新接口下载品牌
 * @author hanxy
 * @since 2016-12-16
 */
class GetBrandsRequestNew extends LazadaNewApiAbstract{
    
    public $_apiMethod = 'GetBrands';
    
    public $_httpMethod = 'GET';
    
    /**
     * @desc 设置请求参数
     * @see PlatformApiInterface::setRequest()
     */
    public function setRequest(){
        $request = array();
        $this->request = $request;
        return $this;
    }
}