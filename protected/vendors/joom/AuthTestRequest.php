<?php
/**
 * @desc 测试验证是否正常
 * @author liuj
 *
 */
class AuthTestRequest extends JoomApiAbstract {
	
	/**
	 * @desc 设置endpoint
	 * @see JoomApiAbstract::setEndpoint()
	 */
	public function setEndpoint(){
		parent::setEndpoint('auth_test', false);
	}
	
	public function setRequest(){
		$this->request = array();
		return $this;
	}
	
}