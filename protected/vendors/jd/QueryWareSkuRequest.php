<?php
/**
 * @desc 获取商品明细请求接口
 * @author lihy
 *
 */
class QueryWareSkuRequest extends JdApiAbstract {
	protected $_apiMethod = 'jingdong.ept.warecenter.outapi.waresku.query';
	private $_wareId = array();
	public function setRequest() {
		$wareIds = implode(',', $this->_wareId);
		$this->_request = array(
					'wareId'	=>	$wareIds
		);
		return $this;
	}
	
	public function setWareId($wareIds){
		if(!is_array($wareIds))
			$wareIds = array($wareIds);
		$this->_wareId = $wareIds;
	}
}

?>