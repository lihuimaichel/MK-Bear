<?php
/**
 * @desc 获取订单列表API
 * @author zhangf
 *
 */
class OrderSearchRequest extends JdApiAbstract {
	
	/** @var string 下单时间开始 **/
	protected $_bookTimeBegin = null;
	
	/** @var string 下单时间结束 **/
	protected $_bookTimeEnd = null;
	
	/** @var string 订单状态 **/
	protected $_orderStatus = null;
	
	/** @var integer 是否锁定 **/
	protected $_locked = null;
	
	/** @var integer 是否有纠纷 **/
	protected $_disputed = null;
	
	/** @var string 下单用户 **/
	protected $_userPin = null;
	
	/** @var integer 从第多少条开始取 **/
	protected $_startRow = null;

	protected $_apiMethod = 'jingdong.ept.order.getorderIdsbyquery';
	
	const ORDER_STATUS_WAIT_DELIVERY 				= 4;	//已付款,等待发货 
	const ORDER_STATUS_SHIPPED 						= 5;	//已发货
	const ORDER_STATUS_COMPLETED	 				= 6;	//已完成 
	const ORDER_STATUS_CANCELED						= 99;	//已取消
	
	const ORDER_NOT_DISPUTED						= 1;	//没有纠纷
	const ORDER_HAS_DISPUTED 						= 2;	//有纠纷
	
	const ORDER_NUMBER_PER_PAGE 					= 20;	//每页订单数						
	
	/**
	 * (non-PHPdoc)
	 * @see JdApiAbstract::setRequest()
	 */
	public function setRequest() {
		$request = array();
		if (!is_null($this->_bookTimeBegin))
			$request['bookTimeBegin'] = $this->_bookTimeBegin;
		if (!is_null($this->_bookTimeEnd))
			$request['bookTimeEnd'] = $this->_bookTimeEnd;
		if (!is_null($this->_orderStatus))
			$request['orderStatus'] = $this->_orderStatus;
		if (!is_null($this->_locked))
			$request['locked'] = $this->_locked;
		if (!is_null($this->_disputed))
			$request['disputed '] = $this->_disputed;
		if (!is_null($this->_userPin))
			$request['userPin '] = $this->_userPin;
		if (!is_null($this->_startRow))
			$request['startRow'] = $this->_startRow;
		$this->_request = $request;
		return $this;
	}
	
	/**
	 * @desc 设置开始日期
	 * @param unknown $date
	 */
	public function setStartDate($date) {
		$this->_bookTimeBegin = $date;
	}
	
	/**
	 * @desc 设置结束日期
	 * @param unknown $date
	 */
	public function setEndDate($date) {
		$this->_bookTimeEnd = $date;
	}
	
	/**
	 * @desc 设置订单状态
	 * @param unknown $status
	 */
	public function setOrderStatus($status) {
		$this->_orderStatus = $status;
	}
	
	/**
	 * @desc set 是否锁定
	 * @param unknown $locked
	 */
	public function setLocked($locked) {
		$this->_locked = $locked;
	}
	
	/**
	 * @desc 设置是否有争议
	 * @param unknown $disputed
	 */
	public function setDisputed($disputed) {
		$this->_disputed = $disputed;
	}
	
	/**
	 * @desc 设置下单用户
	 * @param unknown $user
	 */
	public function setUserPin($user) {
		$this->_userPin = $user;
	} 
	
	/**
	 * @desc 设置从第多少条开始
	 * @param unknown $row
	 */
	public function setStartRow($row) {
		$this->_startRow = $row;
	}
}