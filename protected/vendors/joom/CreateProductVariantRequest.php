<?php
/**
 * @desc 上传产品变种
 * @author lihy
 *
 */
class CreateProductVariantRequest extends JoomApiAbstract {
	private $_uploadData;
	/**
	 * @desc 设置endpoint
	 * @see JoomApiAbstract::setEndpoint()
	 */
	public function setEndpoint(){
		parent::setEndpoint('variant/add', true);
	}
	
	public function setRequest(){
		$this->request = $this->_uploadData;
		return $this;
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
}