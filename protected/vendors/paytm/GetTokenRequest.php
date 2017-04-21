<?php
/**
 * @desc 获取token
 * @author yangsh
 * @since 2017-02-28
 */
class GetTokenRequest extends PaytmApiAbstract {
	
	/** @var string grant type **/
	protected $_grantType = 'authorization_code';

	/** @var string 换取token的临时CODE **/
	protected $_code = null;
	
	/* @var string 获取授权code时指定的state*/
	protected $_state = null;
	
    /**
     * @desc 初始化对象
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * @desc 设置账号信息
     * @param int $accountID
     * @see PaytmApiAbstract::setAccount()
     */
    public function setAccount($accountID){
        parent::setAccount($accountID);
		$this->_baseUrl = $this->paytmKeys['authorizationUrl'];
		$this->_isPost  = true;//POST

        return $this;
    }

    /**
     * 设置EndPoint
     * @see PaytmApiAbstract::setEndPoint()
     */
    public function setEndPoint() {
        $this->_endpoint = 'oauth2/token';
    }   

	/**
	 * @desc 设置临时CODE
	 * @param string $code
	 */
	public function setCode($code) {
		$this->_code = $code;
		return $this;
	}   

	/**
	 * set State
	 * @param string $state
	 */
    public function setState($state) {
    	$this->_state = $state;
    	return $this;
    }
    
    /**
     * @desc 设置请求参数
     * @see PlatformApiInterface::setRequest()
     */
	public function setRequest() {
		$request = array(
			'grant_type'    => $this->_grantType,
			'client_id'     => $this->_clientID,
			'client_secret' => $this->_clientSecret,
		);
		if ($this->_code) {
			$request['code'] = $this->_code;
		}
		if ($this->_state) {
			$request['state'] = $this->_state;
		}		
		$this->request = $request;
		return $this;
	}
	
}