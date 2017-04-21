<?php
/**
 * @desc 获取广告信息
 * @author Gordon 
 * @since 2015-06-02
 */
class GetSellerListRequest extends EbayApiAbstract{
	
	/**@var string 粒度等级*/
	protected $_granularityLevel = null;
	/**@var string 是否包含多属性*/
	protected $_includeVariations = null;
	/**@var string 是否包含浏览次数*/
	protected $_includeWatchCount = null;
	/**@var string 每次请求的listing数量*/
	public $_entriesPerPage = 50;  //每页50条
	/**@var string 请求的页数*/
	public $_pageNumber = 1;
	/**@var string 请求的listring总页数*/
	public $_totalPage = 1;
	/**@var string 返回详情等级*/
    public $_DetailLevel = 'ReturnAll';
	/**@var string 请求的接口*/
	public $_verb = 'GetSellerList';
	/**@var string 上架开始时间*/
	public $_StartTimeFrom = null;
	/**@var string 上架结束时间*/
	public $_StartTimeTo   = null;
	/**@var string 下架开始时间*/
	public $_EndTimeFrom = null;
	/**@var string 下架结束时间*/
	public $_EndTimeTo   = null;
	/**@var string 卖家账号*/
	public $_UserId   = null;
	/**@var string 版本号 */
	public $_Version = null;	

    /**@var array SKU列表 */
    public $_SkuArray = null;   

    public function setRequest(){
    	$sort = 2; //升序
    	$request = array(
    			'RequesterCredentials' => array(
    					'eBayAuthToken' => $this->getToken(),
    			),
    			'DetailLevel'           => $this->_DetailLevel,
    			'Pagination' => array(
    					'EntriesPerPage' => $this->_entriesPerPage,
    					'PageNumber' => $this->_pageNumber
    			),
    			'Sort' => $sort,
				'OutputSelector'	=>	array(
										'PaginationResult',
										'Seller',
										'ItemArray.Item.ItemID',
										'ItemArray.Item.TimeLeft',
										'ItemArray.Item.Title',
                                        'ItemArray.Item.Description',
										'ItemArray.Item.SKU',
										'ItemArray.Item.ListingDuration',
										'ItemArray.Item.ListingType',
										'ItemArray.Item.TotalQuestionCount',
										'ItemArray.Item.PrimaryCategory',
										'ItemArray.Item.PayPalEmailAddress',
										'ItemArray.Item.Variations',
										'ItemArray.Item.ListingDetails',
										'ItemArray.Item.PictureDetails',
										'ItemArray.Item.ShippingDetails',
										'ItemArray.Item.SellingStatus',
										'ItemArray.Item.Quantity',
										'ItemArray.Item.Site',
										'ItemArray.Item.Storefront',
										'ItemArray.Item.WatchCount',
										'ItemArray.Item.Location',
										'ItemArray.Item.DispatchTimeMax',
    							)
    	);
    	if (!is_null($this->_includeVariations))
    		$request['IncludeVariations'] = $this->_includeVariations ? 'true' : 'false';
    	if (!is_null($this->_includeWatchCount))
    		$request['IncludeWatchCount'] = $this->_includeWatchCount ? 'true' : 'false';
	   	if(!is_null($this->_StartTimeFrom))
     		$request['StartTimeFrom'] = $this->_StartTimeFrom;
    	if(!is_null($this->_StartTimeTo))
     		$request['StartTimeTo'] = $this->_StartTimeTo;
    	if(!is_null($this->_EndTimeFrom))
     		$request['EndTimeFrom'] = $this->_EndTimeFrom;
    	if(!is_null($this->_EndTimeTo))
     		$request['EndTimeTo']   = $this->_EndTimeTo;
    	if(!is_null($this->_UserId))
    		$request['UserID']   = $this->_UserId;
    	if(!is_null($this->_Version)){
    		$request['Version'] = $this->_Version;
    	}

        //添加SKU数组参数拉取对应的listing记录  Liz|20160601
        if(!is_null($this->_SkuArray)){
            $request['SKUArray'] = $this->_SkuArray;
        }
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
     * @desc 守则是否返回产品被查看次数
     * @param unknown $boolen
     */
    public function setIncludeWatchCount($boolen) {
    	$this->_includeWatchCount = $boolen;
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
    public function setPageNum($page) {
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
    
    /**
     * @desc 设置卖家账号
     * @param string $userId
     */
    public function setUserId($userId) {
    	$this->_UserId  = $userId;
    }
    

    /**
     * @desc 设置SKU数组
     * @param string $skuarray
     */
    public function setSkuArray($skuarray) {
        if (!$skuarray) return null;
        $ret = array();
        $tmp = array();
        foreach($skuarray as $val){
            if(!empty($val)) $tmp[] = $val['sku'];
        }
        if (!$tmp) return null;
        $ret['SKU'] = $tmp;
        $this->_SkuArray = $ret;
    }

    
    /**
     * @override 重写
     * @desc 将请求参数转化为Xml
     */
    public function getRequestXmlBody(){
    	$xmlGeneration = new XmlGenerator();
    	return $xmlGeneration->XmlWriter()->push($this->getXmlRequestHeader(), array('xmlns' => 'urn:ebay:apis:eBLBaseComponents'))
    	->buildXMLFilterMulti($this->getRequest())
    	->pop()
    	->getXml();
    }
}