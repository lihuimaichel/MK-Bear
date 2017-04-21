<?php
class GetProductsRequestNew extends LazadaNewApiAbstract {
	
	/** @var datetime 创建起始时间 **/
	protected $_createdAfter 		= null;
	
	/** @var datetime 创建结束时间 **/
	protected $_createdBefore 		= null;

	/** @var datetime 起始更新时间 **/
	protected $_updatedAfter 		= null;
	
	/** @var datetime 结束更新时间 **/
	protected $_updatedBefore 		= null;	
	
	/** @var string 搜索关键字（SKU或者产品名关键字）**/
	protected $_search              = null;
	
	/** @var string 过滤产品状态 **/
	protected $_filter              = null;
	
	/** @var integer 每次取多少条记录 **/
	protected $_limit               = 500;
	
	/** @var integer 从第多少条开始取 **/
	protected $_offset              = 0;
	
	/** @var string 设定搜索那些SKU **/
	protected $_skuSellerList       = null;
	
	/** @var string 请求的Action名 **/
    protected $_apiMethod           = 'GetProducts';
    
    /** @var string 请求方式 **/
    public $_httpMethod             = 'GET';
	
	const PRODUCT_STATUS_ALL 				= 'all';			//所有产品
	const PRODUCT_STATUS_LIVE 				= 'live';			//在线产品
	const PRODUCT_STATUS_INACTIVE           = 'inactive';		//不活跃的产品
	const PRODUCT_STATUS_DELETED            = 'deleted';		//已经删除的产品
	const PRODUCT_STATUS_UPDATE             = 'update';			//已经下线的产品
	const PRODUCT_STATUS_IMAGE_MISSING      = 'image-missing';	//图片丢失的产品
	const PRODUCT_STATUS_PEDDING            = 'pending';		//待定的产品
	const PRODUCT_STATUS_REJECTED           = 'rejected';		//被拒绝的产品
	const PRODUCT_STATUS_SOLD_OUT           = 'sold-out';		//售完的产品
	
	public function setRequest() {
		$request 	= array();
		$request['Limit'] 				= $this->_limit;
		$request['Offset'] 				= $this->_offset;
		if (!is_null($this->_createdAfter)) {
			$request['CreatedAfter'] 	= $this->_createdAfter;
		}
		if (!is_null($this->_createdBefore)) {
			$request['CreateBefore'] 	= $this->_createdBefore;
		}
		if (!is_null($this->_updatedAfter)){
			$request['UpdatedAfter'] 	= $this->_updatedAfter;
		}
		if (!is_null($this->_updatedBefore)){
			$request['UpdatedBefore'] 	= $this->_updatedBefore;		
		}
		if (!is_null($this->_search)){
			$request['search'] 			= $this->_search;
		}
		if (!is_null($this->_filter)){
			$request['Filter'] 			= $this->_filter;
		}
		if (!is_null($this->_skuSellerList) && !empty($this->_skuSellerList)){
			$request['SkuSellerList'] 	= $this->_skuSellerList;
		}
		$this->request = $request;
		return $this;
	}
	
	/**
	 * @desc 设置创建起始时间
	 * @param datetime $date
	 */
	public function setCreatedAfter($date) {
		$this->_createdAfter = $date;
	}
	
	/**
	 * @desc 设置创建结束时间
	 * @param datetime $date
	 */
	public function setCreatedBefore($date) {
		$this->_createdBefore = $date;
	}

	/**
	 * @desc 设置起始更新时间
	 * @param datetime $date
	 */
	public function setUpdatedAfter($date) {
		$this->_updatedAfter = $date;
	}
	
	/**
	 * @desc 设置结束更新时间
	 * @param datetime $date
	 */
	public function setUpdatedBefore($date) {
		$this->_updatedBefore = $date;
	}	
	
	/**
	 * @desc 设置搜索关键字
	 * @param string $keyword
	 */
	public function setSearch($keyword) {
		$this->_search = $keyword;
	}
	
	/**
	 * @desc 设置过滤产品状态
	 * @param string $status
	 */
	public function setFilter($status) {
		$this->_filter = $status;
	}

	/**
	 * @desc 设置一次取多少条
	 * @param integer $limit
	 */
	public function setLimit($limit) {
		$this->_limit = $limit;
	}
	
	/**
	 * @desc 设置从多少条开始取
	 * @param limit $offset
	 */
	public function setOffset($offset) {
		$this->_offset = $offset;
	}
	
	/**
	 * @desc 设置搜索的SKU列表
	 * @param string $skuList
	 */
	public function setSkuSellerList($skuList) {
		$this->_skuSellerList = $skuList;
	}
}