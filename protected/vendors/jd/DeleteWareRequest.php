<?php
/**
 * @desc 删除请求接口
 * @author lihy
 *
 */
class DeleteWareRequest extends JdApiAbstract {
	protected $_apiMethod = 'jingdong.ept.warecenter.ware.delete';
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