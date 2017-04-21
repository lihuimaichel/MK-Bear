<?php
/**
 * @desc 获取报告结果
 * @author Gordon
 * @since 2015-08-24
 */
class FeedOffsetListRequest extends LazadaApiAbstract{
    
    /**@var string 修改时间*/
    public $_updatedDate = null;
    
    /**@var string 每次个数*/
    public $_limit = 100;
    
    /**@var string 请求的页数*/
    public $_pageNumber = 1;
    
    public $_status = null;
    
    public $_apiMethod = 'FeedOffsetList';
    
    const STATUS_FINISHED = 'Finished';//失败状态
    
    public $_httpMethod = 'GET';
    
    /**
     * @desc 设置修改时间
     * @param date $time
     */
    public function setUpdatedDate($time){
        $this->_updatedDate = $time;
    }
    
    /**
     * @desc 设置页码
     * @param number $pageNum
     */
    public function setPageNum($pageNum){
        $this->_pageNumber = $pageNum;
    }
    
    /**
     * @desc 订单状态
     * @param tinyint $status
     */
    public function setStatus($status){
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
        if($this->_updatedDate) $request['UpdatedDate'] = $this->_updatedDate;
        if($this->_status) $request['Status'] = $this->_status;
        $this->request = $request;
        return $this;
    }
}