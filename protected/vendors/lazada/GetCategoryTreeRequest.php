<?php
/**
 * @desc 下载分类
 * @author Gordon
 * @since 2015-08-13
 */
class GetCategoryTreeRequest extends LazadaApiAbstract{
    
    public $_apiMethod = 'GetCategoryTree';
    
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