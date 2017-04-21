<?php
/**
 * @desc 下载报告
 * @author Gordon
 * @since 2015-08-13
 */
class FeedStatusRequest extends LazadaApiAbstract{

    public $_apiMethod = 'FeedStatus';
    
    /**@var 报告ID*/
    public $_feedID = null;

    public $_httpMethod = 'GET';
    
    public function setFeedID($feedID){
        $this->_feedID = $feedID;
    }

    /**
     * @desc 设置请求参数
     * @see PlatformApiInterface::setRequest()
     */
    public function setRequest(){
        $request = array(
                'FeedID'    => $this->_feedID,
        );
        $this->request = $request;
        return $this;
    }
}