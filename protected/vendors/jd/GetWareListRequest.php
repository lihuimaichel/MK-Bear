<?php
/**
 * @desc 获取商品列表请求接口
 * @author lihy
 *
 */
class GetWareListRequest extends JdApiAbstract {
	protected $_apiMethod = 'jingdong.ept.warecenter.warelist.get';
	private $_pageSize = 100;
	private $_currentPage = 1;
	private $_wareId;
	private $_wareStatus;
	private $_categoryId;
	private $_title;
	private $_itemNum;
	private $_transportId;
	private $_startOnlineTime;
	private $_endOnlineTime;
	private $_minSupplyPrice;
	private $_maxSupplyPrice;
	private $_recommendTpid;

	public function setRequest() {
		$this->_request = array(
			'pageSize'		=>	$this->_pageSize,
			'currentPage'	=>	$this->_currentPage
		);
		if($this->_wareId){
			$this->_request['wareIdsStr'] = $this->_wareId;
		}
		if($this->_wareStatus){
			$this->_request['wareStatus'] = $this->_wareStatus;
		}
		if($this->_categoryId){
			$this->_request['categoryId'] = $this->_categoryId;
		}
		if($this->_title){
			$this->_request['title'] = $this->_title;
		}
		if($this->_itemNum){
			$this->_request['itemNum'] = $this->_itemNum;
		}
		if($this->_transportId){
			$this->_request['transportId'] = $this->_transportId;
		}
		if($this->_startOnlineTime){
			$this->_request['startOnlineTime'] = $this->_startOnlineTime;
		}
		
		if($this->_endOnlineTime){
			$this->_request['endOnlineTime'] = $this->_endOnlineTime;
		}
		if($this->_minSupplyPrice){
			$this->_request['minSupplyPrice'] = $this->_minSupplyPrice;
		}
		if($this->_maxSupplyPrice){
			$this->_request['maxSupplyPrice'] = $this->_maxSupplyPrice;
		}
		if($this->_recommendTpid){
			$this->_request['recommendTpid'] = $this->_recommendTpid;
		}
		return $this;
	}
	/**
	 * @desc 设置每页数量
	 * @param unknown $pageSize
	 */
	public function setPageSize($pageSize){
		$this->_pageSize = $pageSize;
	}
	/**
	 * @desc 设置当前页码
	 * @param unknown $currentPage
	 */
	public function setCurrentPage($currentPage){
		$this->_currentPage = $currentPage;
	}
	/**
	 * @desc 设置wareId
	 * @param unknown $wareId
	 */
	public function setWareId($wareId){
		$this->_wareId = $wareId;
	}
	
	/**
	 * @desc 设置商品状态
	 * @param unknown $wareStatus
	 */
	public function setWareStatus($wareStatus){
		$this->_wareStatus = $wareStatus;
	}
	
	
	/**
	 * @desc 设置商品分类
	 * @param unknown $wareStatus
	 */
	public function setCategoryId($categoryId){
		$this->_categoryId = $categoryId;
	}
	/**
	 * @desc 设置标题
	 * @param unknown $title
	 */
	public function setTitle($title){
		$this->_title = $title;
	}
	/**
	 * @desc 设置货号
	 * @param unknown $itemNum
	 */
	public function setItemNum($itemNum){
		$this->_itemNum = $itemNum;
	}
	/**
	 * @desc 设置运输模板编号
	 * @param unknown $transportId
	 */
	public function setTransportId($transportId){
		$this->_transportId = $transportId;
	}
	/**
	 * @desc 设置开始上线时间
	 * @param unknown $startOnlineTime
	 */
	public function setStartOnlineTime($startOnlineTime){
		$this->_startOnlineTime = $startOnlineTime;
	}
	/**
	 * @desc 设置截止上线时间
	 * @param unknown $endOnlineTime
	 */
	public function setEndOnlineTime($endOnlineTime){
		$this->_endOnlineTime = $endOnlineTime;
	}
	/**
	 * @desc 设置最小供货价
	 * @param unknown $minSupplyPrice
	 */
	public function setMinSupplyPrice($minSupplyPrice){
		$this->_minSupplyPrice = $minSupplyPrice;
	}
	/**
	 * @desc 设置最大供货价
	 * @param unknown $maxSupplyPrice
	 */
	public function setMaxSupplyPrice($maxSupplyPrice){
		$this->_maxSupplyPrice = $maxSupplyPrice;
	}
	/**
	 * @desc 设置推荐模板
	 * @param unknown $recommendTpid
	 */
	public function setRecommendTpid($recommendTpid){
		$this->_recommendTpid = $recommendTpid;
	}
	
	
}

?>