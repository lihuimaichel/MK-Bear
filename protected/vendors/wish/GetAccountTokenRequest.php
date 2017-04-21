<?php
/**
 * @desc 根据refresh_token获取wish账号access_token
 * @author liht
 * @since 2015-11-16
 */
class GetAccountTokenRequest extends WishApiAbstract{

	public $_apiMethod = 'getToken';
	
	public $_client_id = null;
	
    /**
     * @desc 设置请求参数
     * @see AliexpressApiAbstract::setRequest()
     */
    public function setRequest(){
        //https://sandbox.merchant.wish.com/api/v2/
    	$this->_baseUrl .= 'oauth/refresh_token';

		$this->_namespace = 'system.oauth2';
    	$this->_addSignature = false;
		
        $request = array(
                'grant_type'          => 'refresh_token',
                'client_id'           => $this->_clientID,
                'client_secret'       => $this->_clientSecret,
                'refresh_token'       => $this->_refreshToken,
        );

        $this->_isPost = true;
        $this->request = $request;

        return $this;
    }

}