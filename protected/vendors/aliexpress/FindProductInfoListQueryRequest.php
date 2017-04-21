<?php
/**
 * @desc 获取产品列表接口
 * @author zhangf
 *
 */
class FindProductInfoListQueryRequest extends AliexpressApiAbstract {
	
	/** @var string 产品状态 **/
	protected $_productStatusType  = '';
	
	/** @var string 产品ID **/
	protected $_productId          = null;

	/** @var string 待排除的产品ID列表 **/
	protected $_exceptedProductIds = null;

	/** @var int 商品的剩余有效期,天数 **/
	protected $_offLineTime        = null;
	
	/** @var int 产品列表总数 **/
	public $_totalItem             = 1;
	
	/** @var int 页数 **/
	public $_page                  = 1;

	/** @var int 总页数 **/
	public $_totalPage             = 1;
	
	/** @var int 每页条数 **/
	public $_pageSize              = 100;
	
	const PRODUCT_STATUS_ONSELLING 			= 'onSelling';
	const PRODUCT_STATUS_OFFLINE 			= 'offline';
	const PRODUCT_STATUS_AUDITING 			= 'auditing';
	const PRODUCT_STATUS_EDITINGREQUIRED 	= 'editingRequired';
	
	/**
	 * (non-PHPdoc)
	 * @see PlatformApiInterface::setRequest()
	 */
	public function setRequest() {
		$request = array();
		$request['productStatusType'] = $this->_productStatusType;
		$request['pageSize'] = $this->_pageSize;
		$request['currentPage'] = $this->_page;
		if (!is_null($this->_productId)) {
			$request['productId'] = $this->_productId;
		}
		if (!is_null($this->_exceptedProductIds)) {
			$request['exceptedProductIds'] = $this->_exceptedProductIds;
		}
		$this->request = $request;
		return $this;
	}
	
	/**
	 * @desc 设置product status
	 * @param unknown $status
	 */
	public function setProductStatusType($status) {
		$this->_productStatusType = $status;
	}
	
	/**
	 * @desc 设置page size
	 * @param unknown $size
	 */
	public function setPageSize($size) {
		$this->_pageSize = $size;
	}
	
	/**
	 * @desc 设置page
	 * @param int $page
	 */
	public function setPage($page) {
		$this->_page = $page;
	}

	/**
	 * @desc 设置totalPage
	 * @param int $totalPage
	 */
	public function setTotalPage($totalPage) {
		$this->_totalPage = $totalPage;
	}	

	/**
	 * @desc 获取page
	 */
	public function getPage() {
		return $this->_page;
	}

	/**
	 * @desc 获取totalPage
	 */
	public function getTotalPage() {
		return $this->_totalPage;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see AliexpressApiAbstract::setApiMethod()
	 */
	public function setApiMethod(){
		$this->_apiMethod = 'api.findProductInfoListQuery';
	}
	
	/**
	 * @desc 设置product id
	 * @param unknown $id
	 */
	public function setProductId($id) {
		$this->_productId = $id;
	}

	/**
	 * @desc 设置exceptedProductIds
	 * @param string $exceptedProductIds
	 */
	public function setExceptedProductIds($exceptedProductIds) {
		$this->_exceptedProductIds = $exceptedProductIds;
	}

	/**
	 * @desc 设置offLineTime
	 * @param int $offLineTime
	 */
	public function setOffLineTime($offLineTime) {
		$this->_offLineTime = $offLineTime;
	}

}