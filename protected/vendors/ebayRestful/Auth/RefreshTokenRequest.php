<?php
/**
 * @desc 通过refresh Toeken 刷新 Access Token
 * @author yangsh
 * @since 2017-03-11
 *
 */
class Auth_RefreshTokenRequest extends EbayRestfulApiAbstract {

	/** @var string grant type **/
	protected $_grantType = 'refresh_token';

    /** @var string scope **/        
    protected $_scope = null;

    /**
     * @初始化对象
     */
    public function __construct() {
        parent::__construct();
        $this->_urlObject = new EbayRestfulUrl($this->ebayRestfulKeys['baseUrl'],'','identity','v1','oauth2/token','');
        $this->_requestObject = new HttpRequestImpl(null,null,HttpRequestImpl::HTTP_POST,300);
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
     * set Scope
     * @param string $scope
     */
    public function setScope($scope) {
    	$this->_scope = $scope;
    	return $this;
    }
    
    /**
     * @desc 设置请求参数
     */
	public function setRequest() {
		$request = array(
            'grant_type'    => $this->_grantType,
            'refresh_token' => $this->_refreshToken,
		);
		if ($this->_scope) {
			$request['scope'] = $this->_scope;
		}
		$this->_requestObject->requestData = $request;
		return $this;
	}	

}