<?php
/**
 * @desc 下载单个订单
 * @author lihy
 * @since 2016-01-07
 */
class RetrieveOrdersRequest extends WishApiAbstract{
    
    public $orderId = null;
    
    /**
     * @desc 设置endpoint
     * @see WishApiAbstract::setEndpoint()
     */
    public function setEndpoint(){
        parent::setEndpoint('order', false);
    }

    /**
     * @desc 设置订单id号（wish平台上的id）
     * @param unknown $orderId
     * @return RetrieveOrdersRequest
     */
    public function setOrderId($orderId){
    	$this->orderId = $orderId;
    	return $this;
    }
    
    /**
     * @desc 设置请求参数
     * @see PlatformApiInterface::setRequest()
     */
    public function setRequest(){
        $request = array(
               "id"	=>	$this->orderId
        );
        $this->request = $request;
        return $this;
    }
}