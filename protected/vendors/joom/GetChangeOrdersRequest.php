<?php
/**
 * @desc 下载订单(最近改动的订单)
 * @author Gordon
 * @since 2015-06-22
 */
class GetChangeOrdersRequest extends JoomApiAbstract{
    
    /**@var string 交互Endpoint*/
    public $_endpoint = null;
    
    /**@var string 开始订单*/
    public $_start = 0;
    
    /**@var string 每次个数*/
    public $_limit = 100;
    
    /**@var date 开始时间*/
    public $_since = null;
    
    /**
     * @desc 设置endpoint
     * @see JoomApiAbstract::setEndpoint()
     */
    public function setEndpoint(){
        parent::setEndpoint('order/multi-get', false);
    }
    
    /**
     * @desc 设置开始Index
     * @param int $index
     */
    public function setStartIndex($index){
        $this->_start = intval($index) * $this->_limit;
    }
    
    /**
     * @desc 设置开始时间
     * @param date $sinceTime
     */
    public function setSinceTime($sinceTime){
        $this->_since = $sinceTime;
    }
    
    /**
     * @desc 设置请求参数
     * @see PlatformApiInterface::setRequest()
     */
    public function setRequest(){
        $request = array(
                'start'     => $this->_start,
                'limit'     => $this->_limit,
                'since'     => $this->_since,
        );
        $this->request = $request;
        return $this;
    }
}