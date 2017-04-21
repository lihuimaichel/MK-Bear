<?php
/**
 * 订单列表简化查询  [api.findOrderListSimpleQuery -- version: 1 ]
 * @author Rex
 * @since 2016-06-06
 */
class FindOrderListSimpleRequest extends AliexpressApiAbstract{ 
    
    /**@var string 订单总个数*/
    public $_totalItem = 1;
    
    /**@var string 每次个数*/
    public $_pageSize = 50;
    
    /**@var string 当前页码*/
    public $_page = 1;
    
    /**@var date 开始时间*/
    public $_startTime = null;
    
    /**@var date 开始时间*/
    public $_endTime = null;
    
    /**@var date 开始时间*/
    public $_orderStatus = null;
    
    /**
     * @desc 设置开始时间
     * @param date $startTime
     */
    public function setStartTime($startTime){
        $this->_startTime = $startTime;
    }
    
    /**
     * @desc 设置结束时间
     * @param date $endTime
     */
    public function setEndTime($endTime){
        $this->_endTime = $endTime;
    }

    
    /**
     * @desc 设置订单状态
     * @param date $sinceTime
     */
    public function setOrderStatus($status){
        $this->_orderStatus = $status;
    }
    
    public function setApiMethod(){
        $this->_apiMethod = 'api.findOrderListSimpleQuery';
    }
    
    /**
     * @desc 设置页码
     * @param int $page
     */
    public function setPage($page){
        $this->_page = $page;
    }
    
    /**
     * @desc 设置请求参数
     * @see PlatformApiInterface::setRequest()
     */
    public function setRequest(){
        $request = array(
                'page'              => $this->_page,
                'pageSize'          => $this->_pageSize,
                'createDateStart'   => $this->_startTime,
                'createDateEnd'     => $this->_endTime,
                'orderStatus'       => $this->_orderStatus,
        );
        if(!$this->_startTime){
            $request['createDateStart'] = $this->_startTime;
        }
        $this->request = $request;
        return $this;
    }
}