<?php
/**
 * @desc 下架请求接口
 * @author lihy
 *
 */
class UnshelveWareRequest extends JdApiAbstract {
	protected $_apiMethod = 'jingdong.ept.warecenter.ware.unshelve';
	private $_wareId;
	public function setRequest() {
		$this->_request = array(
					'wareId'	=>	$this->_wareId
		);
		return $this;
	}
	
	public function setWareId($wareId){
		$this->_wareId = $wareId;
	}
}

?>