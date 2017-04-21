<?php
/**
 * @desc 获取运费模板
 * @author lihy
 *
 */
class GetFreightTemplateRequest extends JdApiAbstract {
	protected $_apiMethod = 'jingdong.ept.feight.outapi.query';
	private $_pageSize = 20;
	private $_currentPage = 1;
	public function setRequest() {
		$this->_request = array(
			'pageSize'	=>$this->_pageSize,
			'currentPage'=>$this->_currentPage
		);
		return $this;
	}
	
	
	public function setPageSize($pageSize){
		$this->_pageSize = $pageSize;
	}
	
	public function setCurrentPage($currentPage){
		$this->_currentPage = $currentPage;
	}
}

?>