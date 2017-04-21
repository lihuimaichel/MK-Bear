<?php
/**
 * @desc 获取授权分类请求接口
 * @author lihy
 *
 */
class GetCategoryRequest extends JdApiAbstract {
	protected $_apiMethod = 'jingdong.ept.vender.category.get';
	private $_status = 1;//类目状态1.启用;2停用
	public function setRequest() {
		$this->_request = array(
					'status'	=>	$this->_status
		);
		return $this;
	}
	
	public function setStatus($status){
		$this->_status = $status;
	}
}

?>