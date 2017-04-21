<?php
/**
 * @desc 新增站内信/订单备注
 * @author wx
 * @since 2015-12-08
 */
class AddMsgRequest extends AliexpressApiAbstract{ 
    
    /**@var string 通道ID，即关系ID*/
    public $_channelId = null;
    
    /**@var string 买家账号*/
    public $_buyerId = null;
    
    /**@var string 内容*/
    public $_content = '';
    
    /**@var string 消息类型---(message_center/order_msg)*/
    public $_msgSources = '';
    
    /**
     * @desc 设置通道ID
     * @param string $channelId
     */
    public function setChannelId($channelId){
        $this->_channelId = $channelId;
    }
    
    /**
     * @desc 设置买家ID
     * @param string $buyerId
     */
    public function setBuyerId($buyerId){
        $this->_buyerId = $buyerId;
    }

    /**
     * @desc 设置消息内容
     * @param string $content
     */
    public function setContent($content){
        $this->_content = $content;
    }
    
    /**
     * @desc 设置消息类型
     * @param string $msgSources
     */
    public function setMsgSources($msgSources){
        $this->_msgSources = $msgSources;
    }
    
    public function setApiMethod(){
        $this->_apiMethod = 'api.addMsg';
    }
    
    /**
     * @desc 设置请求参数
     * @see PlatformApiInterface::setRequest()
     */
    public function setRequest(){
        $request = array(
                'channelId'         => $this->_channelId,
                'buyerId'           => $this->_buyerId,
                'content'           => $this->_content,
                'msgSources'        => $this->_msgSources,
        );
        $this->request = $request;
        return $this;
    }
}