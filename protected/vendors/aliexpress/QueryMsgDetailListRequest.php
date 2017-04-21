<?php
/**
 * @desc 查询留言详情
 * @author hanxy
 * @since 2017-03-30
 */
class QueryMsgDetailListRequest extends AliexpressApiAbstract{

    /**@var string 通道ID，即关系ID*/
    public $_channelId = null;
    
    /**@var string 消息类型---(message_center/order_msg)*/
    public $_msgSources = '';

    /**@var int 当前页码*/
    public $_currentPage = 1;

    /**@var String 每页结果数量*/
    public $_pageSize = 10;     //每页条数,pageSize取值范围(0~100) 最多返回前5000条数据
    
    /**
     * @desc 设置通道ID
     * @param string $channelId
     */
    public function setChannelId($channelId){
        $this->_channelId = $channelId;
    }
        
    /**
     * @desc 设置消息类型
     * @param string $msgSources
     */
    public function setMsgSources($msgSources){
        $this->_msgSources = $msgSources;
    }
    
    public function setApiMethod(){
        $this->_apiMethod = 'api.queryMsgDetailList';
    }
    
    /**
     * @desc 设置请求参数
     * @see PlatformApiInterface::setRequest()
     */
    public function setRequest(){
        $request = array(
                'channelId'   => $this->_channelId,
                'msgSources'  => $this->_msgSources,
                'currentPage' => $this->_currentPage,
                'pageSize'    => $this->_pageSize,
        );
        $this->request = $request;
        return $this;
    }    
}