<?php
/**
 * @desc 跟踪号上传
 * @author yangsh	
 * @since 2016-11-30
 */
class SetTrackingNoRequest extends ShopeeApiAbstract{
    
    /**
     * @desc 一次最多上传50个订单跟踪号
     * @var array 
     */
	public $_info_list;
    
    /**
     * @desc 设置endpoint
     * @see ShopeeApiAbstract::setEndpoint()
     */
    public function setEndpoint(){
        parent::setEndpoint('logistics/tracking_number/set_mass', true);
    }
    
    /**
     * 设置infoList array(array('ordersn'=>'160627113469284','tracking_number'=>'156130090000080'),,,,,)
     * @param array $infoList
     */
    public function setInfoList($infoList){
    	$this->_info_list = $infoList;
    	return $this;
    }
    
    /**
     * @desc 设置请求参数
     * @see PlatformApiInterface::setRequest()
     */
    public function setRequest(){
        $request = array(
        	'info_list'	=>	$this->_info_list,
        );
        
        $this->setEndpoint();
        $this->request = $request;
        return $this;
    }
}