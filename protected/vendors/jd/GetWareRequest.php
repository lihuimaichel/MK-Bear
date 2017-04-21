<?php
/**
 * @desc 获取商品明细请求接口
 * @author lihy
 *
 */
class GetWareRequest extends JdApiAbstract {
	protected $_apiMethod = 'jingdong.ept.warecenter.ware.get';// jingdong.ept.warecenter.ware.get
	private $_wareId = 0;
	public function setRequest() {
		$this->_request = array(
			'wareId'		=>	$this->_wareId
		);
		return $this;
	}
	
	public function setWareId($wareId){
		$this->_wareId = $wareId;
	}
}

?>