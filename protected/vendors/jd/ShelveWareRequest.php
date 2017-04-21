<?php
/**
 * @desc 上架请求接口
 * @author lihy
 *
 */
class ShelveWareRequest extends JdApiAbstract {
	protected $_apiMethod = 'jingdong.ept.warecenter.ware.shelve';
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