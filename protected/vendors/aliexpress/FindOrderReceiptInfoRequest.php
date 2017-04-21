<?php
/**
 * @desc 订单收货信息查询
 * @author hanxy
 * @since 2016-11-26
 */
class FindOrderReceiptInfoRequest extends AliexpressApiAbstract{ 
    
    /**@var string 订单号*/
    public $_orderID = null;
    
    /**
     * @desc 设置订单ID
     * @param float $orderID
     */
    public function setOrderID($orderID){
        $this->_orderID = $orderID;
    }
    
    public function setApiMethod(){
        $this->_apiMethod = 'api.findOrderReceiptInfo';
    }
    
    /**
     * @desc 设置请求参数
     * @see PlatformApiInterface::setRequest()
     */
    public function setRequest(){
        $request = array('orderId' => $this->_orderID,);
        $this->request = $request;
        return $this;
    }
}