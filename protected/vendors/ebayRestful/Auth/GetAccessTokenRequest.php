<?php
/**
 * @desc 获取Access Token
 * @author yangsh
 * @since 2017-03-11
 *
 */
class Auth_GetAccessTokenRequest extends EbayRestfulApiAbstract {

	/** @var string grant type **/
	protected $_grantType = 'authorization_code';

    /** @var string code **/        
    protected $_code = null;    

	protected $_redirectUri = null;//the RuName value

    /**
     * @desc 初始化对象
     */
    public function __construct() {
        parent::__construct();
        $this->_urlObject = new EbayRestfulUrl($this->ebayRestfulKeys['baseUrl'],'','identity','v1','oauth2/token','');
        $this->_requestObject = new HttpRequestImpl(null,null,HttpRequestImpl::HTTP_POST,300);
    }

    /**
     * @desc 设置账号信息
     * @param int $accountID
     */
    public function setAccount($accountID){
        parent::setAccount($accountID);
        $this->_redirectUri = $this->_ruName;

        return $this;
    } 

    /**
     * @desc 设置http头信息
     */
    public function setHeaders(){
        parent::setHeaders();
        $headers = array(
            'Content-Type'   => 'application/x-www-form-urlencoded',
            'Authorization'  => 'Basic '.base64_encode($this->_clientID.':'.$this->_clientSecret),
        ) + $this->_requestObject->requestHeaders;
        $this->_requestObject->requestHeaders = $headers;
    }

    /**
     * set Code
     * @param string $code
     */
    public function setCode($code) {
    	$this->_code = $code;
    	return $this;
    }
    
    /**
     * @desc 设置请求参数
     */
	public function setRequest() {
		$request = array(
			'grant_type'    	=> $this->_grantType,
            'redirect_uri'      => $this->_redirectUri,
		);
		if ($this->_code) {
			$request['code'] = $this->_code;
		}
		$this->_requestObject->requestData = $request;
		return $this;
	}	

}