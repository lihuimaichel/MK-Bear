<?php
/**
 * @desc 获取access token
 * @author hanxy
 *
 */
class JoomAccessTokenRequest extends JoomApiAbstract {
	/** @var string 换取token的临时CODE **/
	protected $_code = null;
	
	/** @var string grant type **/
	protected $_grantType = 'authorization_code';
	
	/** @var string 重定向地址 **/
	protected $_redirectUri = null;
	
    /**
     * @desc 设置账号信息
     * @param int $accountID
     */
    public function setAccount($accountID){
        $this->accountID = $accountID;
        //获取账号相关信息
        $accountInfo = PlatformJoomAccount::getJoomAccountInfoById($this->accountID);
        $joomKeys = ConfigFactory::getConfig('joomKeys');
        $this->accountID    = $accountID;
        $this->_baseUrl     = $joomKeys['baseUrl'];
        $this->_clientID = $accountInfo['client_id'];
        $this->_clientSecret = $accountInfo['client_secret'];
        $this->_redirectUri = $accountInfo['redirect_uri'];
        return $this;
    }

    /**
     * @desc 设置交互链接
     */
    public function setUrl(){
    	$this->setEndpoint();
    	$this->_url = $this->_baseUrl . $this->_endpoint;
    }    
    
    /**
     * (non-PHPdoc)
     * @see PlatformApiInterface::setRequest()
     */
	public function setRequest() {
		$request = array(
			'client_id' => $this->_clientID,
			'client_secret' => $this->_clientSecret,
			'code' => $this->_code,
			'grant_type' => $this->_grantType,
			'redirect_uri' => $this->_redirectUri,
		);
		$this->request = $request;
		return $this;
	}

	/**
	 * @desc 发送请求,获取响应结果
	 */
	public function sendRequest() {
		try {
			$this->setUrl();
			$response = Yii::app()->curl->addCertificate()->post($this->_url, $this->getRequest());//添加证书
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
	 * @desc 设置endpoint
	 * @see JoomApiAbstract::setEndpoint()
	 */
	public function setEndpoint(){
		parent::setEndpoint('oauth/access_token', false);
	}	
	
	/**
	 * @desc 设置临时CODE
	 * @param unknown $code
	 */
	public function setCode($code) {
		$this->_code = $code;
	}
}