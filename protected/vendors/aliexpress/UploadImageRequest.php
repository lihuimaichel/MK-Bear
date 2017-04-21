<?php
/**
 * @desc aliexpress 上传产品接口
 * @author zhangf
 *
 */
class UploadImageRequest extends AliexpressApiAbstract {
	
	/** @var string 文件名称 **/
	protected $_fileName = null;
	
	/** @var string 图片组 **/
	protected $_groupId = null;
	
	/** @var string 上传文件的二进制流 **/
	protected $_fileStream = null;
	
	/**
	 * @desc 设置请求
	 */
	public function setRequest() {
		$request = array();
		if (!is_null($this->_fileName))
			$request['fileName'] = $this->_fileName;
		if (!is_null($this->_groupId))
			$request['groupId'] = $this->_groupId;
		$this->request = $request;
		return $this;
	}

	/**
	 * @desc 设置交互链接
	 */
	public function setUrl(){
		$this->setUrlPath();
		$this->_url = str_replace('/openapi/', '/fileapi/', $this->_url);
		$this->_url = $this->_baseUrl .'/'. $this->_urlPath . '?'.$this->getRequestBody();
	}	
	
	/**
	 * @desc 发送请求,获取响应结果
	 */
	public function sendRequest() {
		try {
			$this->setUrl();
			$curl = new Curl();
			$curl->init();
			$curl->setOptions(array(
				CURLOPT_HTTPHEADER => array("Content-Type: application/x-www-from-urlencoded"),
				CURLOPT_TIMEOUT => 300,
				CURLOPT_CONNECTTIMEOUT => 300,
			));
			$response = $curl->post($this->getUrl(), $this->_fileStream);
			$this->response = json_decode($response);
			if( !$this->getIfSuccess() ){
				$this->writeErrorLog();
			}
		} catch (Exception $e ) {
			$this->writeErrorLog();
		}
		return $this;
	}	
	
	/**
	 * (non-PHPdoc)
	 * @see AliexpressApiAbstract::setApiMethod()
	 */
	public function setApiMethod(){
		$this->_apiMethod = 'api.uploadImage';
	}
	
	/**
	 * @desc 设置文件名
	 * @param unknown $name
	 */
	public function setFileName($name) {
		$this->_fileName = $name;
	}
	
	/**
	 * @desc 设置图片所在组
	 * @param unknown $groupId
	 */
	public function setGroupId($groupId) {
		$this->_groupId = $groupId;
	}
	
	/**
	 * @desc 设置上传文件的路径
	 * @param unknown $path
	 */
	public function setFileStream($stream) {
		$this->_fileStream = $stream;
	}
}