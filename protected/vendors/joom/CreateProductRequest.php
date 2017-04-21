<?php
/**
 * @desc 上传产品
 * @author lihy
 *
 */
class CreateProductRequest extends JoomApiAbstract {
	private $_uploadData;
	/**
	 * @desc 设置endpoint
	 * @see JoomApiAbstract::setEndpoint()
	 */
	public function setEndpoint(){
		parent::setEndpoint('product/add', true);
	}
	/**
	 * (non-PHPdoc)
	 * @see PlatformApiInterface::setRequest()
	 */
	public function setRequest(){
		$this->request = $this->_uploadData;
		$this->setTimeout();
		return $this;
	}
	/**
	 * (non-PHPdoc)
	 * @see JoomApiAbstract::getRequestBody()
	 */
	public function getRequestBody(){
		$requestBody = $this->getRequest();
		$this->_requestbody = http_build_query($requestBody);
		return $this->_requestbody;
	}
	
	/**
	 * @desc 设置需要提交的数据
	 * @param array $data
	 * @return CreateProductRequest
	 */
	public function setUploadData($data){
		$this->_uploadData = $data;
		return $this;
	}
	
	public function setTimeout(){
		$this->_timeout = 1000;
	}
}