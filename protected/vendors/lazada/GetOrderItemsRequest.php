<?php
/**
 * @desc 下载订单
 * @author Gordon
 * @since 2015-08-07
 */
class GetOrderItemsRequest extends LazadaApiAbstract{
    
    /**@var 订单号*/
    public $_orderID = null;
    
    public $_apiMethod = 'GetOrderItems';
    
    public $_httpMethod = 'GET';
    
    /**
     * @desc 设置订单号
     * @param date $time
     */
    public function setOrderID($orderID){
        $this->_orderID = $orderID;
    }
    
    /**
     * @desc 设置请求参数
     * @see PlatformApiInterface::setRequest()
     */
    public function setRequest(){
        $request = array(
                'OrderId'     => $this->_orderID,
        );
        $this->request = $request;
        return $this;
    }
}