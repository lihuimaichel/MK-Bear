<?php
/**
 * @desc 下载品牌
 * @author Gordon
 * @since 2015-08-13
 */
class GetBrandsRequest extends LazadaApiAbstract{
    
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