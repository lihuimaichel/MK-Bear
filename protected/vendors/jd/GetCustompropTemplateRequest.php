<?php
/**
 * @desc 获取自定义属性模板
 * @author lihy
 *
 */
class GetCustompropTemplateRequest extends JdApiAbstract {
	protected $_apiMethod = 'jingdong.ept.warecenter.customprop.get';
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