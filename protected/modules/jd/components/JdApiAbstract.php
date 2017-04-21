<?php

Abstract class JdApiAbstract implements PlatformApiInterface {
	
	/** @var string 请求基础URL **/
	protected $_baseUrl = null;
	
	/** @var string 接口名称 **/
	protected $_apiMethod = null;
	
	/** @var string access token **/
	protected $_accessToken = null;
	
	/** @var string app key **/
	protected $_appKey = null;
	
	/** @var boolean 是否需要请求签名 **/
	protected $_signature = true;
	
	/** @var string 时间戳 **/
	protected $_timestamp = null;
	
	/** @var string api 版本 **/
	protected $_version = '2.0';
	
	/** @var string token过期时间 **/
	protected $_tokenExpiredTime = null;
	
	/** @var string app secret **/
	protected $_appSecret = null;
	
	/** @var string refresh token **/
	protected $_refreshToken = null;
	
    /** @var string 最终交互地址 **/
    protected $_url = null;
    
	/** @var string 数据格式 **/
	protected $_format = 'json';
	
	/** @var array 请求参数 **/
	protected $_request = null;
	
	/** @var boolean 是否需要授权 **/
	protected $_authorization = true;
	
	/** @var string url查询字符串 **/
	protected $_queryString = '';
	
	/** @var string 响应结果 **/
	protected $_response = '';
	
	protected $_accountID = null;
	
	protected $_isPost = false;
	
	protected $_errorMessage = null;
	
	public function setAccount($accountID) {
		$accountInfo = JdAccount::model()->getAccountInfoByID($accountID);
		$this->_accountID = $accountID;
		$this->_accessToken = $accountInfo['access_token'];
		$this->_appKey = $accountInfo['app_key'];
		$this->_tokenExpiredTime = $accountInfo['token_expired_time'];
		$this->_refreshToken = $accountInfo['refresh_token'];
		$jdKeys = ConfigFactory::getConfig('jdKeys');
		$this->_baseUrl     = $jdKeys['baseUrl'];
		//@TODO 检查token是否过期，过期了刷新token保存到账号表
		return $this;
	}
	
	/**
	 * @DESC 设置交互地址
	 */
	public function setUrl() {
		$this->_url = $this->_baseUrl;
		//$this->_url = $this->_baseUrl . '?' . $this->getQueryString();
	}
	
	/**
	 * @desc 获取请求参数
	 * @return string
	 */
	public function getQueryString() {
		$appParameters = array();
		$parameters = array();
		if (is_array($this->_request)) {
			foreach ($this->_request as $name => $value) {
				$appParameters[$name] = $value;
			}
			ksort($appParameters, SORT_STRING);		//按字母先后顺序排序
			$jsonString = json_encode($appParameters);
			$this->_queryString = '360buy_param_json=' . urlencode($jsonString);
			$parameters['360buy_param_json'] = urlencode($jsonString);
		} else {
			$this->_queryString = '360buy_param_json=' . urlencode($this->_request);
			$parameters['360buy_param_json'] = urlencode($this->_request);
		}
		if ($this->_authorization) {
			$this->_queryString .= '&access_token=' . $this->_accessToken;
			$parameters['access_token'] = urlencode($this->_accessToken);
		}
		$parameters['app_key'] = urlencode($this->_appKey);
		$parameters['method'] = urlencode($this->_apiMethod);
		$parameters['timestamp'] = urlencode(date('Y-m-d H:i:s'));
		$parameters['v'] = urlencode($this->_version);
		$this->_queryString .= '&app_key=' . $parameters['app_key'] . '&method=' . $parameters['method'] . '&timestamp=' . $parameters['timestamp'] . '&v=' . $parameters['v'];
		if ($this->_signature)
			$this->_queryString .= '&sign=' . $this->getSignature($parameters);
		return $this->_queryString;
	}
	
	/**
	 * @desc 获取签名
	 * @param unknown $parameters
	 * @return string
	 */
	public function getSignature($parameters) {
		$signature = '';
		if (is_array($parameters)) {
			$signature .= $this->_appSecret;
			ksort($parameters, SORT_STRING);
			foreach ($parameters as $name => $value) {
				$signature .= $name . $value;
			}
			$signature .= $this->_appSecret;
			$signature = strtoupper(md5($signature));
		}
		return $signature;
	}
	
	/**
	 * @desc 获取请求
	*/
	public function getRequest() {
		return $this->_request;
	}
	
	/**
	 * @desc 发送请求
	*/
	public function sendRequest() {
		try {
			
			$this->setUrl();
			$curl = Yii::app()->curl;
			if ($this->_isPost) {
				$respone = $curl
					->setOption(CURLOPT_TIMEOUT,300)->setOption(CURLOPT_CONNECTTIMEOUT,300)
					->post($this->_url, $this->getQueryString());
			} else {
				$respone = $curl
					->setOption(CURLOPT_TIMEOUT,300)->setOption(CURLOPT_CONNECTTIMEOUT,300)
					->get($this->_url . '?' . $this->getQueryString());
			}
//			print_r($respone);
			//替换json串里面的非法符号，避免无法解析
			$respone = str_replace(array("\r", "\n", "\t"), array(" ", " ", " "), $respone);
			$this->_response = json_decode($respone);
			if( !$this->getIfSuccess() ){
				$this->writeErrorLog();
			}		
		} catch (Exception $e) {
			//echo $e->getMessage();
			$this->writeErrorLog();
			throw  new Exception($e->getMessage());
		}
		return $this;
	}
	 
	/**
	 * @desc 获取响应信息
	*/
	public function getResponse() {
		return $this->_response;
	}
	
    /**
     * @desc 判断交互是否成功
     */
    public function getIfSuccess(){
    	if (empty($this->_response))
    		return false;
    	if( isset($this->_response->error_response) && ( $this->_response->error_response->code !== '0') ){
    		return false;
    	}else{
    		return true;
    	}
    }
    
    /**
     * @desc 记录文件错误日志
     */
    public function writeErrorLog(){
    	$logPath = Yii::getPathOfAlias('webroot').'/log/jd/'.date('Y').'/'.date('m').'/'.date('d').'/'.date('H');
    	if( !is_dir($logPath) ){
    		mkdir($logPath, 0777, true);
    	}
    	$log = date('Y-m-d H:i:s').'           '.$this->_url."\n";//时间，接口名
    	$log .= $this->_queryString."\n";//交互报文
    	$log .= 'Error Message:'.$this->getErrorMsg()."\n\n";//错误信息
    	$fileName = $this->_accountID.'-'.str_replace('/', '_', $this->_apiMethod).'.txt';
    	file_put_contents($logPath.'/'.$fileName, $log, FILE_APPEND);
    }
    
    /**
     * @desc 获取失败信息
     * @return string
     */
    public function getErrorMsg(){
    	//$errorMessage = '';
    	if (empty($this->_response))
    		$this->_errorMessage = 'Response Empty';
    	if( isset($this->_response->error_response->zh_desc) ){
    		$this->_errorMessage = $this->_response->error_response->zh_desc;
    	}
    	return $this->_errorMessage;
    }    
}