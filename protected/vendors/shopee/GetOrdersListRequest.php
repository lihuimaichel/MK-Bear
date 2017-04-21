<?php
/**
 * @desc 下载订单
 * @author lihy	
 * @since 2016-06-22
 */
class GetOrdersListRequest extends ShopeeApiAbstract{
    
	public $_create_time_from;
	
	public $_create_time_to;
    
	public $_pagination_entries_per_page = 100;
	
	public $_pagination_offset = 0;

    public $_limit = 100;
    
    
    /**
     * @desc 设置endpoint
     * @see ShopeeApiAbstract::setEndpoint()
     */
    public function setEndpoint(){
        parent::setEndpoint('orders/basics', true);
    }
    
    /**
     * @desc 设置开始Index
     * @param int $index
     */
    public function setStartIndex($index){
        $this->_start = intval($index) * $this->_limit;
    }
    
   
    public function setCreateTimeTo($time){
    	$this->_create_time_to = $time;
    	return $this;
    }
    
    public function setCreateTimeFrom($time){
    	$this->_create_time_from = $time;
    	return $this;
    }
    
    
    public function setPerPageSize($size){
    	$this->_pagination_entries_per_page = $size;
    	return $this;
    }
    
    public function setPageOffset($offset){
    	$this->_pagination_offset = $offset;
    	return $this;
    }
    
    /**
     * @desc 设置请求参数
     * @see PlatformApiInterface::setRequest()
     */
    public function setRequest(){
        $request = array(
        		'create_time_from'				=>	$this->_create_time_from,
                'create_time_to'				=>	$this->_create_time_to,
        		'pagination_entries_per_page'	=>	$this->_pagination_entries_per_page,
        		'pagination_offset'				=>	$this->_pagination_offset
        );
        
        $this->setEndpoint();
        $this->request = $request;
        return $this;
    }
}