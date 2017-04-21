<?php
/**
 * @desc 获取alirxpress acount refresh_token
 * @author guoll
 * @since 2015-09-09
 */
class GetAccountRefreshTokenRequest extends AliexpressApiAbstract{ 

	public $_apiMethod = 'getToken';
	public $_redirect_uri='http://127.0.0.5/aliexpress/aliexpressaccount/redirecurl';
// 	public $_client_id = null;
	public $_type='authorization_code';
	
    /**
     * @desc 设置请求参数
     * @see AliexpressApiAbstract::setRequest()
     */
    public function setRequest(){
    	$this->_baseUrl = 'https://gw.api.alibaba.com/openapi';
		$this->_namespace = 'system.oauth2';
    	$this->_addSignature = false;
		
        $request = array(
                'grant_type'          => $this->_type,
        		'need_refresh_token'  => 'true',
                'client_id'           => $this->_appKey,
                'client_secret'       => $this->_secretKey,
        		'redirect_uri'		  => $this->_redirect_uri,
                'code'       		  => $this->_code,
        );
        $this->request = $request;
        return $this;
    }
}