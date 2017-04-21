<?php
/**
 * @desc 下载订单
 * @author Gordon
 * @since 2015-07-06
 */
class FindOrderByIdRequest extends AliexpressApiAbstract{ 
    
    /**@var string 订单号*/
    public $_orderID = null;
    
    /**
     * @desc 设置开始时间
     * @param date $startTime
     */
    public function setOrderID($orderID){
        $this->_orderID = $orderID;
    }
    
    public function setApiMethod(){
        $this->_apiMethod = 'api.findOrderById';
    }
    
    /**
     * @desc 设置请求参数
     * @see PlatformApiInterface::setRequest()
     */
    public function setRequest(){
        $request = array(
                'orderId'           => $this->_orderID,
        );
        $this->request = $request;
        return $this;
    }
}