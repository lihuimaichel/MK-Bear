<?php
/**
 * @desc 获取授权品牌
 * @author lihy
 *
 */
class GetBrandRequest extends JdApiAbstract {
	protected $_apiMethod = 'jingdong.ept.vender.brand.get';
	private $_status = 1;
	private $_pageSize = 20;
	private $_currentPage = 1;
	public function setRequest() {
		$this->_request = array(
			'status'	=>$this->_status,
			'pageSize'	=>$this->_pageSize,
			'currentPage'=>$this->_currentPage
		);
		return $this;
	}
	
	public function setStatus($status){
		$this->_status = $status;
	}
	
	public function setPageSize($pageSize){
		$this->_pageSize = $pageSize;
	}
	
	public function setCurrentPage($currentPage){
		$this->_currentPage = $currentPage;
	}
}

?>