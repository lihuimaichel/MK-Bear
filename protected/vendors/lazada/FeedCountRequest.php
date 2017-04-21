<?php
/**
 * @desc 下载品牌
 * @author liuj
 * @since 2016-03-30
 */
class FeedCountRequest extends LazadaApiAbstract{
    
    public $_apiMethod = 'FeedCount';
    
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