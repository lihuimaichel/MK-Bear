<?php
/**
 * @desc 下载订单
 * @author Gordon
 * @since 2015-08-07
 */
class GetOrdersRequest extends LazadaApiAbstract{
    
    /**@var string 创建开始时间*/
    public $_createdAfter = null;
    
    /**@var string 创建结束时间*/
    public $_createdBefore = null;
    
    /**@var string 修改开始时间*/
    public $_updatedAfter = null;
    
    /**@var string 修改结束时间*/
    public $_updatedBefore = null;
    
    /**@var int 总订单数量*/
    public $_totalCount = 1;

    /**@var string 每次请求订单个数*/
    public $_limit = 100;
    
    /**@var string 请求的页数*/
    public $_pageNumber = 1;
    
    public $_status = null;
    
    public $_apiMethod = 'GetOrders';
    
    public $_httpMethod = 'GET';
    /**
     * @desc 设置修改开始时间
     * @param date $time
     */
    public function setUpdatedAfter($time){
        $this->_updatedAfter = $time;
    }
    
    /**
     * @desc 设置修改结束时间
     * @param date $time
     */
    public function setUpdatedBefore($time){
        $this->_updatedBefore = $time;
    }
    
    /**
     * @desc 设置页码
     * @param number $pageNum
     */
    public function setPageNum($pageNum){
        $this->_pageNumber = $pageNum;
    }
    
    /**
     * @desc 设置创建开始时间
     * @param unknown $date
     */
    public function setCreateAfter($date) {
    	$this->_createdAfter = $date;
    }

    /**
     * @desc 设置创建结束时间
     * @param unknown $date
     */
    public function setCreateBefore($date) {
    	$this->_createdBefore = $date;
    }
    
    /**
     * @desc 订单状态
     * @param tinyint $status
     */
    public function setOrderStatus($status){
        $this->_status = $status;
    }
    
    /**
     * @desc 设置请求参数
     * @see PlatformApiInterface::setRequest()
     */
    public function setRequest(){
        $request = array(
            'Limit'     => $this->_limit,
            'Offset'    => $this->_limit * ($this->_pageNumber - 1),
        );
        if($this->_updatedAfter) $request['UpdatedAfter'] = $this->_updatedAfter;
        if($this->_updatedBefore) $request['UpdatedBefore'] = $this->_updatedBefore;
        if($this->_createdAfter) $request['CreatedAfter'] = $this->_createdAfter;
        if($this->_createdBefore) $request['CreatedBefore'] = $this->_createdBefore;        
        if($this->_status) $request['Status'] = $this->_status;
        
        $this->request = $request;
        return $this;
    }
}