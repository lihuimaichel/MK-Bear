<?php
class RefreshTokenRequest extends WishApiAbstract {
	
	protected $_grantType = 'refresh_token';
	
	/**
	 * @desc 设置endpoint
	 * @see WishApiAbstract::setEndpoint()
	 */
	public function setEndpoint(){
		parent::setEndpoint('oauth/refresh_token', false);
	}
	
	/**
	 * @desc 设置账号信息
	 * @param int $accountID
	 */
	public function setAccount($accountID){
		$this->accountID = $accountID;
		//获取账号相关信息
		$accountInfo = WishAccount::getAccountInfoById($this->accountID);
		$wishKeys = ConfigFactory::getConfig('wishKeys');
		$this->accountID    = $accountID;
		$this->_accessToken = $accountInfo['access_token'];
		$this->_refreshToken = $accountInfo['refresh_token'];
		$this->_clientID = $accountInfo['client_id'];
		$this->_clientSecret = $accountInfo['client_secret'];
		$this->_tokenExpiredTime = $accountInfo['token_expired_time'];
		$this->_baseUrl     = $wishKeys['baseUrl'];
		return $this;
	}
	
	/**
	 * @desc 设置请求参数
	 * @see PlatformApiInterface::setRequest()
	 */
	public function setRequest(){
		$request = array(
			'client_id' => $this->_clientID,
			'client_secret' => $this->_clientSecret,
			'refresh_token' => $this->_refreshToken,
			'grant_type' => $this->_grantType,
		);
		$this->request = $request;
		return $this;
	}	
}