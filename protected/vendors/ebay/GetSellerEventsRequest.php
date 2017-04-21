<?php
/**
 * @desc 获取在线广告revise
 * @author Michael
 * @since 2015-08-15
 */
class GetSellerEventsRequest extends EbayApiAbstract{
	
    /**@var string 请求的接口*/
    public $_verb = 'GetSellerEvents';

    protected $_hideVariations = null;

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
    public $_detailLevel = 'ReturnAll';

	/**@var string 上架开始时间*/
	public $_StartTimeFrom = null;

	/**@var string 上架结束时间*/
	public $_StartTimeTo   = null;

	/**@var string 下架开始时间*/
	public $_EndTimeFrom = null;

	/**@var string 下架结束时间*/
	public $_EndTimeTo   = null;

    /**@var string 修改开始时间*/
    public $_ModTimeFrom = null;

    /**@var string 修改结束时间*/
    public $_ModTimeTo   = null;    
	
	/**
	 * @desc 设置请求所需的参数
	 * @see PlatformApiInterface::setRequest()
	 */
    public function setRequest(){
    	$request = array(
    			'RequesterCredentials' => array(
    					'eBayAuthToken' => $this->getToken(),
    			),
    			'Pagination' => array(
    					'EntriesPerPage' => $this->_entriesPerPage,
    					'PageNumber' => $this->_pageNumber
    			),
    	);

        if (!is_null($this->_hideVariations))
            $request['HideVariations'] = $this->_hideVariations ? true : false;        

    	if (!is_null($this->_includeVariations))
    		$request['IncludeVariations'] = $this->_includeVariations ? true : false;

    	if (!is_null($this->_includeWatchCount))
    		$request['IncludeWatchCount'] = $this->_includeWatchCount ? true : false;

    	if (!is_null($this->_detailLevel))
    		$request['DetailLevel'] = $this->_detailLevel;

	   	if(!is_null($this->_StartTimeFrom))
     		$request['StartTimeFrom'] = $this->_StartTimeFrom;

    	if(!is_null($this->_StartTimeTo))
     		$request['StartTimeTo'] = $this->_StartTimeTo;

    	if(!is_null($this->_EndTimeFrom))
     		$request['EndTimeFrom'] = $this->_EndTimeFrom;

    	if(!is_null($this->_EndTimeTo))
     		$request['EndTimeTo']   = $this->_EndTimeTo;

        if(!is_null($this->_ModTimeFrom))
            $request['ModTimeFrom '] = $this->_ModTimeFrom;

        if(!is_null($this->_ModTimeTo))
            $request['ModTimeTo ']   = $this->_ModTimeTo;

    	$this->request = $request;
    	return $this;
    }

    /**
     * @desc 设置返回数据等级
     * @param string $level
     */
    public function setDetailLevel($level) {
    	$this->_detailLevel = $level;
        return $this;
    }

    /**
     * 设置是否返回Variations信息
     * @param boolean $hideVariations
     */
    public function setHideVariations($hideVariations) {
        $this->_hideVariations = $hideVariations;
        return $this;
    }    
    
    /**
     * @desc 设置是否返回产品多属性
     * @param boolean $boolean
     */
    public function setIncludeVariations($boolean) {
    	$this->_includeVariations = $boolean;
        return $this;
    }
    
    /**
     * @desc 设置是否返回产品被查看次数
     * @param unknown $boolen
     */
    public function setIncludeWatchCount($boolen) {
    	$this->_includeWatchCount;
        return $this;
    }
    
    /**
     * @desc 设置返回数据每页个数
     * @param integer $pageSize
     */
    public function setEntriesPerPage($pageSize) {
    	$this->_entriesPerPage = $pageSize;
        return $this;
    }
    
    /**
     * @desc 设置当前页数
     * @param interger $page
     */
    public function setPageNumber($page) {
    	$this->_pageNumber = $page;
        return $this;
    }
    /**
     * @desc 设置总页数
     * @param number $pageNum
     */
    public function setTotalPage($pageNum) {
    	$this->_totalPage = $pageNum;
        return $this;
    }
    
    /**
     * @desc 设置返回数据的等级
     * @param string $level
     */
    public function getDetialLevel($level) {
    	$this->_detailLevel = $level;
        return $this;
    }
    
    /**
     * @desc 设置商品上架开始时间
     * @param string $startTimeFrom
     */
    public function setStartTimeFrom($startTimeFrom) {
    	$this->_StartTimeFrom = $startTimeFrom;
        return $this;
    }

    /**
     * @desc 设置商品上架结束时间
     * @param string $startTimeTo
     */
    public function setStartTimeTo($startTimeTo) {
    	$this->_StartTimeTo = $startTimeTo;
        return $this;
    }

    /**
     * @desc 设置商品下架开始时间
     * @param string $endTimeTo
     */
    public function setEndTimeFrom($endTimeFrom) {
    	$this->_EndTimeFrom = $endTimeFrom;
        return $this;
    }
    
    /**
     * @desc 设置商品下架结束时间
     * @param string $endTimeTo
     */
    public function setEndTimeTo($endTimeTo) {
    	$this->_EndTimeTo  = $endTimeTo;
        return $this;
    }

    /**
     * @desc 设置商品修改开始时间
     * @param string $modTimeFrom
     */
    public function setModTimeFrom($modTimeFrom) {
        $this->_ModTimeFrom = $modTimeFrom;
        return $this;
    }
    
    /**
     * @desc 设置商品修改结束时间
     * @param string $modTimeTo
     */
    public function setModTimeTo($modTimeTo) {
        $this->_ModTimeTo  = $modTimeTo;
        return $this;
    } 


}