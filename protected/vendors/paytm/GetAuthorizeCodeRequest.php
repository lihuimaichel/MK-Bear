<?php
/**
 * @desc 获取Authorize Code
 * @author yangsh
 * @since 2017-02-28
 *
 */
class GetAuthorizeCodeRequest extends PaytmApiAbstract {

	/** @var string response type **/
	protected $_responseType = 'code';//固定值

	protected $_notredirect = true;//固定值

	/** @var string username 登录账号 **/
	protected $_username = null;

	/** @var string password 密码 **/
	protected $_password = null;

	/*@var string 参数，api原样返回*/
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
        $this->_baseUrl  = $this->paytmKeys['authorizationUrl'];
        $this->_username = $this->_email;//email ID
        $this->_isPost   = true;//POST

        return $this;
    }

    /**
     * 设置EndPoint
     * @see PaytmApiAbstract::setEndPoint()
     */
    public function setEndPoint() {
        $this->_endpoint = 'oauth2/authorize';
    }

    /**
     * set Password
     * @param string $password
     */
    public function setPassword($password) {
    	$this->_password = $password;
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
			'response_type'    	=> $this->_responseType,
			'notredirect' 		=> $this->_notredirect,
			'client_id'     	=> $this->_clientID,
		);
		if($this->_username) {
			$request['username'] = $this->_username;
		}
		if($this->_password) {
			$request['password'] = $this->_password;
		}
		if ($this->_state) {
			$request['state'] = $this->_state;
		}
		$this->request = $request;
		return $this;
	}	

}