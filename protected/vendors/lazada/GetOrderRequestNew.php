<?php
/**
 * @desc 下载订单
 * @author Yangsh
 * @since 2016-10-13
 */
class GetOrderRequestNew extends LazadaNewApiAbstract{
    
    public $_orderId = null;
    
    public $_apiMethod = 'GetOrder';
    
    public $_httpMethod = 'GET';
    
    /**
     * @desc 订单状态
     * @param tinyint $status
     */
    public function setOrderId($orderId){
        $this->_orderId = $orderId;
    }
    
    /**
     * @desc 设置请求参数
     * @see PlatformApiInterface::setRequest()
     */
    public function setRequest(){
        if($this->_orderId) $request['OrderId'] = $this->_orderId;
        $this->request = $request;
        return $this;
    }
}