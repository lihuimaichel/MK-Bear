<?php
/**
 * @desc 声明发货
 * @author Gordon
 * @since 2015-08-04
 */
class SellerShipmentRequest extends AliexpressApiAbstract{ 
    
    /**@var string 物流服务*/
    public $_serviceName = null;
    
    /**@var string 物流追踪号*/
    public $_logisticsNo = null;
    
    /**@var string 备注*/
    public $_description = '';
    
    /**@var string 发货状态---全部发货(all)、部分发货(part)*/
    public $_sendType = 'all';
    
    /**@var date 平台订单号*/
    public $_outRef = null;
    
    /**@var string 追踪网址*/
    public $_trackingWebsite = null;
    
    /**
     * @desc 设置物流服务
     * @param string $serviceName
     */
    public function setServiceName($serviceName){
        $this->_serviceName = $serviceName;
    }
    
    /**
     * @desc 设置跟踪号
     * @param string $trackNum
     */
    public function setLogisticsNo($trackNum){
        $this->_logisticsNo = $trackNum;
    }

    /**
     * @desc 设置平台订单号
     * @param string $platformOrderID
     */
    public function setOutRef($platformOrderID){
        $this->_outRef = $platformOrderID;
    }
    
    /**
     * @desc 设置备注
     * @param string $description
     */
    public function setDescription($description){
        $this->_description = $description;
    }
    
    /**
     * @desc 设置追踪网址
     * @param string $website
     */
    public function setWebsite($website){
        $this->_trackingWebsite = $website;
    }
    
    public function setApiMethod(){
        $this->_apiMethod = 'api.sellerShipment';
    }
    
    /**
     * @desc 设置请求参数
     * @see PlatformApiInterface::setRequest()
     */
    public function setRequest(){
        $request = array(
                'serviceName'           => $this->_serviceName,
                'logisticsNo'           => $this->_logisticsNo,
                'description'           => $this->_description,
                'sendType'              => $this->_sendType,
                'outRef'                => $this->_outRef,
                'trackingWebsite'       => $this->_trackingWebsite,
        );
        $this->request = $request;
        return $this;
    }
}