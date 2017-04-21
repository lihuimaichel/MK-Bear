<?php
/**
 * @desc 获取拍卖产品竞拍信息
 * @author Michael
 * @since 2015-08-14
 */
class GetBidderListRequest extends EbayApiAbstract{
	
	/**@var string 粒度等级*/
	protected $_granularityLevel = null;
	/**@var string 是否包含多属性*/
	protected $_includeVariations = null;
	/**@var string 是否包含浏览次数*/
	protected $_includeWatchCount = null;
	/**@var string 每次请求的listing数量*/
	public $_entriesPerPage = 100;
	/**@var string 请求的页数*/
	public $_pageNumber = 1;
	/**@var string 请求的listring总页数*/
	public $_totalPage = 1;
	/**@var string 返回详情等级*/
    public $_DetailLevel = 'ReturnAll';
	/**@var string 请求的接口*/
	public $_verb = 'GetBidderList';
	/**@var string 上架开始时间*/
	public $_StartTimeFrom = null;
	/**@var string 上架结束时间*/
	public $_StartTimeTo   = null;
	/**@var string 下架开始时间*/
	public $_EndTimeFrom = null;
	/**@var string 下架结束时间*/
	public $_EndTimeTo   = null;
	
	/**
	 * @desc 设置请求所需的参数
	 * @see PlatformApiInterface::setRequest()
	 */
    public function setRequest(){
    	$request = array(
    			'RequesterCredentials' => array(
    					'eBayAuthToken' => $this->getToken(),
    			),
    			'DetailLevel'           => $this->_DetailLevel,
    			'Pagination' => array(
    					'EntriesPerPage' => $this->_entriesPerPage,
    					'PageNumber' => $this->_pageNumber
    			),
    	);
    	if (!is_null($this->_includeVariations))
    		$request['IncludeVariations'] = $this->_includeVariations ? true : false;
    	if (!is_null($this->_includeWatchCount))
    		$request['IncludeWatchCount'] = $this->_includeWatchCount ? true : false;
    	if (!is_null($this->_detailLevel))
    		$request['DetailLevel'] = $this->_detailLevel ? true : false;
	   	if(!is_null($this->_StartTimeFrom))
     		$request['StartTimeFrom'] = $this->_StartTimeFrom;
    	if(!is_null($this->_StartTimeTo))
     		$request['StartTimeTo'] = $this->_StartTimeTo;
    	if(!is_null($this->_EndTimeFrom))
     		$request['EndTimeFrom'] = $this->_EndTimeFrom;
    	if(!is_null($this->_EndTimeTo))
     		$request['EndTimeTo']   = $this->_EndTimeTo;
    	$this->request = $request;
    	return $this;
    }
    
    /**
     * @desc 设置返回数据等级
     * @param string $level
     */
    public function setGranularityLevel($level) {
    	$this->_granularityLevel = $level;
    }
    
    /**
     * @desc 设置是否返回产品多属性
     * @param boolean $boolean
     */
    public function setIncludeVariations($boolean) {
    	$this->_includeVariations = $boolean;
    }
    
    /**
     * @desc 设置是否返回产品被查看次数
     * @param unknown $boolen
     */
    public function setIncludeWatchCount($boolen) {
    	$this->_includeWatchCount;
    }
    
    /**
     * @desc 设置返回数据每页个数
     * @param integer $pageSize
     */
    public function setEntriesPerPage($pageSize) {
    	$this->_entriesPerPage = $pageSize;
    }
    
    /**
     * @desc 设置当前页数
     * @param interger $page
     */
    public function setPageNumber($page) {
    	$this->_pageNumber = $page;
    }
    /**
     * @desc 设置总页数
     * @param number $pageNum
     */
    public function setTotalPage($pageNum) {
    	$this->_totalPage = $pageNum;
    }
    
    /**
     * @desc 设置返回数据的等级
     * @param string $level
     */
    public function getDetialLevel($level) {
    	$this->_detailLevel = $level;
    }
    
    /**
     * @desc 设置商品上架开始时间
     * @param string $startTimeFrom
     */
    public function setStartTimeFrom($startTimeFrom) {
    	$this->_StartTimeFrom = $startTimeFrom;
    }
    /**
     * @desc 设置商品上架结束时间
     * @param string $startTimeTo
     */
    public function setStartTimeTo($startTimeTo) {
    	$this->_StartTimeTo = $startTimeTo;
    }
    /**
     * @desc 设置商品下架开始时间
     * @param string $endTimeTo
     */
    public function setEndTimeFrom($endTimeFrom) {
    	$this->_EndTimeFrom = $endTimeFrom;
    }
    
    /**
     * @desc 设置商品下架结束时间
     * @param string $endTimeTo
     */
    public function setEndTimeTo($endTimeTo) {
    	$this->_EndTimeTo  = $endTimeTo;
    }
}