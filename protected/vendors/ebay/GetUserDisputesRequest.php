<?php 
class GetUserDisputesRequest extends EbayApiAbstract{

	/**@var string 每次请求订单个数*/
	public $_EntriesPerPage = 200;
	
	/**@var string 请求的页数*/
	public $_PageNumber = 1;
	
	/**@var string 请求订单的总页数*/
	public $_TotalPage = 1;
	
	/**@var string 修改开始时间*/
	public $_ModTimeFrom = null;
	
	/**@var string 修改结束时间*/
	public $_ModTimeTo = null;
	
	public $_verb = 'GetUserDisputes';
	
	/**
	 * @desc 设置修改开始时间
	 * @param date $time
	 */
	public function setModTimeFrom($time){
		$this->_ModTimeFrom = $time;
	}
	
	/**
	 * @desc 设置修改结束时间
	 * @param date $time
	 */
	public function setModTimeTo($time){
		$this->_ModTimeTo = $time;
	}
	
	/**
	 * @desc 设置总页数
	 * @param number $pageNum
	 */
	public function setTotalPage($pageNum){
		$this->_TotalPage = $pageNum;
	}
	
	/**
	 * @desc 设置页码
	 * @param number $pageNum
	 */
	public function setPageNum($pageNum){
		$this->_PageNumber = $pageNum;
	}
	
	/**
	 * @desc 设置请求参数
	 * @see PlatformApiInterface::setRequest()
	 */
	public function setRequest(){
		$request = array(
				'RequesterCredentials' => array(
						'eBayAuthToken' => $this->getToken(),
				),
				
				'Pagination'            => array(
						'EntriesPerPage' => $this->_EntriesPerPage,
						'PageNumber' => $this->_PageNumber,
				),
		);
		if($this->_ModTimeFrom) $request['ModTimeFrom'] = $this->_ModTimeFrom;
		if($this->_ModTimeTo) $request['ModTimeTo'] = $this->_ModTimeTo;
	
		$this->request = $request;
		return $this;
	}
}
?>