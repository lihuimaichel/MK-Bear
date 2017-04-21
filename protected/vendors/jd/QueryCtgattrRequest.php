<?php
/**
 * @desc 根据三级类目id查询属性请求接口
 * @author lihy
 *
 */
class QueryCtgattrRequest extends JdApiAbstract {
	protected $_apiMethod = 'jingdong.ept.warecenter.outapi.ctgattr.query';
	private $_catId;
	public function setRequest() {
		$this->_request = array(
					'catId'	=>	$this->_catId
		);
		return $this;
	}
	
	public function setCatId($catId){
		$this->_catId = $catId;
	}
}

?>