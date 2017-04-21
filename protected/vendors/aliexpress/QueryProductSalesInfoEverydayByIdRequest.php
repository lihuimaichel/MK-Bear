<?php
/**
 * @desc 查询商品每天的销量数据(该数据仅限30天之内的时间区间数据查询）（试用）
 * @author Liz
 * @since 2016-11-03
 */
class QueryProductSalesInfoEverydayByIdRequest extends AliexpressApiAbstract{ 
    
	/**@var Long 需修改编辑的商品ID*/
	public $_productId	= '';
	/**@var String 查询时间段的开始时间，例如：yyyy-mm-dd 2016-11-02*/
	public $_startDate = '';
	/**@var String 查询时间段的截止时间*/
	public $_endDate = '';
    /**@var int 当前页码*/
    public $_currentPage = 1;
    /**@var String 每页结果数量*/
    public $_pageSize = 50;     //每页结果数量，默认20个，最大值 50 
	
    public function setApiMethod(){
        $this->_apiMethod = 'api.queryProductSalesInfoEverydayById';
    }
   
    public function setRequest(){
        $request = array(
                'productId'   => $this->_productId,
                'startDate'   => $this->_startDate,
                'endDate'     => $this->_endDate,
                'currentPage' => $this->_currentPage,
                'pageSize'    => $this->_pageSize,
        );
        $this->request = $request;
        return $this;
    }
    
    /**
     * @desc 设置修改商品ID
     * @param int $productID
     */
    public function setProductID($productID){
    	$this->_productId = $productID;
    }
    
    /**
     * @desc 设置查询时间段的开始时间
     * @param long $startTime
     */
    public function setStartDate($startDate){
    	if (!empty($startDate)) {
    		$this->_startDate = $startDate;
    	}
    }
    
    /**
     * @desc 设置查询时间段的截止时间
     * @param long $startTime
     */
    public function setEndDate($endDate){
        if (!empty($endDate)) {
            $this->_endDate = $endDate;
        }
    }
    
}