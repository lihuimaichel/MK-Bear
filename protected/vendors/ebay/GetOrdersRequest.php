<?php
/**
 * @desc 下载订单
 * @author Gordon
 * @since 2015-06-02
 */
class GetOrdersRequest extends EbayApiAbstract{
    
    /**@var string 创建开始时间*/
    public $_CreateTimeFrom = null;
    
    /**@var string 创建结束时间*/
    public $_CreateTimeTo = null;
    
    /**@var string 修改开始时间*/
    public $_ModTimeFrom = null;
    
    /**@var string 修改结束时间*/
    public $_ModTimeTo = null;
    
    /**@var string 平台订单号*/
    public $_OrderIDArray = null;

    /**@var array 字段选择器 */
    public $_OutputSelector = array();
    
    /**@var string 返回详情等级*/
    public $_DetailLevel = 'ReturnAll';
    
    /**@var string 包含手续费*/
    public $_IncludeFinalValueFee = 'true';
    
    /**@var string 订单状态*/
    public $_OrderStatus = null;

    /**@var string 订单交互角色*/
    public $_OrderRole = 'Seller';
    
    /**@var string 每次请求订单个数*/
    public $_EntriesPerPage = 100;
    
    /**@var string 请求的页数*/
    public $_PageNumber = 1;
    
    /**@var string 请求订单的总页数*/
    public $_TotalPage = 1;
    
    public $_verb = 'GetOrders';
    
    /**@var ebay订单状态 */
    const STATUS_All            = 'All';
    const STATUS_Active         = 'Active';
    const STATUS_CANCELLED      = 'Cancelled';//Inactive
    const STATUS_CANCELPENDING  = 'CancelPending';
    const STATUS_COMPLETED      = 'Completed';
    
    /**
     * @desc 设置创建开始时间
     * @param date $time
     */
    public function setCreateTimeFrom($time){
        $this->_CreateTimeFrom = $time;
        return $this;
    }
    
    /**
     * @desc 设置创建结束时间
     * @param date $time
     */
    public function setCreateTimeTo($time){
        $this->_CreateTimeTo = $time;
        return $this;
    }

    /**
     * @desc 设置修改开始时间
     * @param date $time
     */
    public function setModTimeFrom($time){
        $this->_ModTimeFrom = $time;
        return $this;
    }
    
    /**
     * @desc 设置修改结束时间
     * @param date $time
     */
    public function setModTimeTo($time){
        $this->_ModTimeTo = $time;
        return $this;
    }

    /**
     * @desc 设置订单状态
     * @param string $orderStatus
     */
    public function setOrderStatus($orderStatus){
        $this->_OrderStatus = $orderStatus;
        return $this;
    }
    
    /**
     * @desc 设置订单号
     * @param array
     */
    public function setOrderIDArray(array $orders){
        $xmlGeneration       = new XmlGenerator();//Xml生成器
        $this->_OrderIDArray = $xmlGeneration->buildXMLFilter($orders, 'OrderID')->pop()->getXml();
        return $this;
    }

    public function setOutputSelector(array $outputSelectors) {
        $xmlGeneration         = new XmlGenerator();//Xml生成器
        $this->_OutputSelector = $xmlGeneration->buildXMLFilter($outputSelectors, 'OutputSelector')->pop()->getXml();
        return $this;
    }
    
    /**
     * @desc 设置总页数
     * @param number $pageNum
     */
    public function setTotalPage($pageNum){
        $this->_TotalPage = $pageNum;
        return $this;
    }
    
    /**
     * @desc 设置页码
     * @param number $pageNum
     */
    public function setPageNum($pageNum){
        $this->_PageNumber = $pageNum;
        return $this;
    }
    
    /**
     * @desc 设置请求参数
     * @see PlatformApiInterface::setRequest()
     */
    public function setRequest() {
        $request = array(
            'RequesterCredentials'  => array(
                'eBayAuthToken'     => $this->getToken(),
            ),
            'DetailLevel'           => $this->_DetailLevel,
            'IncludeFinalValueFee'  => $this->_IncludeFinalValueFee,
            'OrderRole'             => $this->_OrderRole,
            'Pagination'            => array(
                'EntriesPerPage'    => $this->_EntriesPerPage,
                'PageNumber'        => $this->_PageNumber,
            ),
        );
        if($this->_CreateTimeFrom) {
            $request['CreateTimeFrom']      = $this->_CreateTimeFrom;
        }
        if($this->_CreateTimeTo) {
            $request['CreateTimeTo']        = $this->_CreateTimeTo;
        }        
        if($this->_ModTimeFrom) {
            $request['ModTimeFrom']         = $this->_ModTimeFrom;
        }
        if($this->_ModTimeTo) {
            $request['ModTimeTo']           = $this->_ModTimeTo;
        }
        if(!empty($this->_OrderIDArray)) {
            $request['OrderIDArray']        = $this->_OrderIDArray;
        }
        if (!empty($this->_OutputSelector)) {
            $request[] = $this->_OutputSelector;
        }
        if (!empty($this->_OrderStatus)) {
            $request['OrderStatus']         = $this->_OrderStatus;
        }

        $this->request = $request;
        return $this;
    }
}