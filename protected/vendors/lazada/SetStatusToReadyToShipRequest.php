<?php
/**
 * @desc 设置准备发货
 * @author Gordon
 * @since 2015-09-04
 */
class SetStatusToReadyToShipRequest extends LazadaApiAbstract{

    public $_apiMethod = 'SetStatusToReadyToShip';
    
    /**@var 标记的订单*/
    public $_orderItemIds = null;
    
    /**@var 标记的订单*/
    public $_deliveryType = 'dropship';
    
    /**@var 标记的订单*/
    public $_shippingProvider = null;
    
    /**@var 标记的订单*/
    public $_trackingNumber = null;

    public $_httpMethod = 'GET';
    
    /**
     * @desc 设置订单
     * @param array $orders
     */
    public function setOrderItemIds($orders){
        $this->_orderItemIds = $orders;
    }
    
    /**
     * @desc 设置发货类型
     * @param string $type
     */
    public function setDeliveryType($type){
        $this->_deliveryType = $type;
    }
    
    /**
     * @desc 设置物流供应商
     * @param string $provider
     */
    public function setShippingProvider($provider){
        $this->_shippingProvider = $provider;
    }
    
    /**
     * @desc 设置跟踪号
     * @param string $trackNum
     */
    public function setTrackingNumber($trackNum){
        $this->_trackingNumber = $trackNum;
    }
    
    /**
     * @desc 设置请求参数
     * @see PlatformApiInterface::setRequest()
     */
    public function setRequest(){
        $request = array(
                'OrderItemIds'      => json_encode($this->_orderItemIds),
                'DeliveryType'      => $this->_deliveryType,
                'ShippingProvider'  => $this->_shippingProvider,
                'TrackingNumber'    => $this->_trackingNumber,
        );
        $this->request = $request;
        return $this;
    }
}