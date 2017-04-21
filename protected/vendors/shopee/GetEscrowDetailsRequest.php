<?php
/**
 * @desc 获取放款信息
 * @author yangsh	
 * @since 2016-06-22
 */
class GetEscrowDetailsRequest extends ShopeeApiAbstract{
    
    /**
     * [$_ordersn description]
     * @var string 
     */
	public $_ordersn;
    
    
    /**
     * @desc 设置endpoint
     * @see ShopeeApiAbstract::setEndpoint()
     */
    public function setEndpoint(){
        parent::setEndpoint('orders/my_income', true);
    }
    
    /**
     * [setOrdersn description]
     * @param array $ordersnList
     */
    public function setOrdersn($ordersn){
    	$this->_ordersn = $ordersn;
    	return $this;
    }
    
    /**
     * @desc 设置请求参数
     * @see PlatformApiInterface::setRequest()
     */
    public function setRequest(){
        $request = array(
        	'ordersn'	=>	$this->_ordersn,
        );
        
        $this->setEndpoint();
        $this->request = $request;
        return $this;
    }
}