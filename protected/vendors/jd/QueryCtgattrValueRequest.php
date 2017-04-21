<?php
/**
 * @desc 根据三级类目id和属性id查询属性值请求接口
 * @author lihy
 *
 */
class QueryCtgattrValueRequest extends JdApiAbstract {
	protected $_apiMethod = 'jingdong.ept.warecenter.outapi.ctgattr.value.query';
	private $_catId;
	private $_propertyId;
	public function setRequest() {
		$this->_request = array(
					'catId'	=>	$this->_catId,
					'propertyId'=>	$this->_propertyId
		);
		return $this;
	}
	
	public function setCatId($catId){
		$this->_catId = $catId;
	}
	public function setPropertyId($propertyId){
		$this->_propertyId = $propertyId;
	}
}

?>