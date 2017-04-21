<?php
/**
 * @desc 下载订单信息
 * @author yangsh	
 * @since 2016-06-22
 */
class GetOrderDetailsRequest extends ShopeeApiAbstract{
    
    /**
     * [$_ordersn_list description]
     * @var array 
     */
	public $_ordersn_list;
    
    
    /**
     * @desc 设置endpoint
     * @see ShopeeApiAbstract::setEndpoint()
     */
    public function setEndpoint(){
        parent::setEndpoint('orders/detail', true);
    }
    
    /**
     * [setOrdersnList description]
     * @param array $ordersnList
     */
    public function setOrdersnList($ordersnList){
    	$this->_ordersn_list = $ordersnList;
    	return $this;
    }
    
    /**
     * @desc 设置请求参数
     * @see PlatformApiInterface::setRequest()
     */
    public function setRequest(){
        $request = array(
        	'ordersn_list'	=>	$this->_ordersn_list,
        );
        
        $this->setEndpoint();
        $this->request = $request;
        return $this;
    }
}