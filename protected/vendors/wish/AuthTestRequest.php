<?php
/**
 * @desc 测试验证是否正常
 * @author liuj
 *
 */
class AuthTestRequest extends WishApiAbstract {
	
	/**
	 * @desc 设置endpoint
	 * @see WishApiAbstract::setEndpoint()
	 */
	public function setEndpoint(){
		parent::setEndpoint('auth_test', false);
	}
	
	public function setRequest(){
		$this->request = array();
		return $this;
	}
	
}