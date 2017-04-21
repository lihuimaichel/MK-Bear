<?php
/**
 * @desc 通过刷新RefreshToken来延长AccessToken有效时长
 * @author Michael
 * @since 2015-12-02
 */
class RefreshAccessTokenRequest extends JdApiAbstract {
	protected $_grantType = 'refresh_token';
	protected $_redirectUri = null;
	protected $_apiMethod = 'oauth/token';
	protected $_refreshToken = null;
	/**
	 * @desc 设置账号信息
	 * @param int $accountID
	 */
	public function setAccount($accountID){
		//获取账号相关信息
		$accountInfo = JdAccount::model()->getAccountInfoById($accountID);
		$this->_accountID = $accountID;
		$jdKeys = ConfigFactory::getConfig('jdKeys');
		$this->_baseUrl     = $jdKeys['oauthUrl'];
		$this->_clientID = $accountInfo['app_key'];
		$this->_clientSecret = $accountInfo['app_secret'];
		$this->_redirectUri = $accountInfo['redirect_uri'];
		$this->_refreshToken = $accountInfo['refresh_token'];
		return $this;
	}
	
	/**
	 * @desc 设置交互链接
	 */
 	public function setUrl(){
		$this->_url = $this->_baseUrl . $this->_apiMethod;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see PlatformApiInterface::setRequest()
	 */
	public function setRequest() {
		$request = array(
				'client_id' => $this->_clientID,
				'client_secret' => $this->_clientSecret,
				'grant_type' => $this->_grantType,
				'refresh_token' => $this->_refreshToken,
		);
		$this->request = $request;
		return $this;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see JdApiAbstract::getQueryString()
	 */
	public function getQueryString() {
		foreach ($this->request as $key => $value) {
			$this->_queryString .= $key . '=' . urlencode($value) . '&';
		}
		return $this->_queryString;
	}
	
	/**
	 * @desc 发送请求,获取响应结果
	 */
	public function sendRequest() {
		try {
			$this->setUrl();
			$response = Yii::app()->curl->addCertificate()->post($this->_url, $this->getQueryString());//添加证书
			$this->_response = json_decode($response);
			if( !$this->getIfSuccess() ){
				$this->writeErrorLog();
			}
		} catch (Exception $e ) {
			$this->writeErrorLog();
		}
		return $this;
	}
	
	/**
	 * @desc 设置临时CODE
	 * @param unknown $code
	 */
	public function setCode($code) {
		$this->_code = $code;
	}
}