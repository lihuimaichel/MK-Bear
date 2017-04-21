<?php
/**
 * @desc 获取alirxpress acount refresh_token
 * @author hanxy
 * @since 2017-02-24
 */
class UpdateAccountRefreshTokenRequest extends AliexpressApiAbstract{ 

	public $_apiMethod = 'postponeToken';
	
	public $_client_id = null;
	
    /**
     * @desc 设置请求参数
     * @see AliexpressApiAbstract::setRequest()
     */
    public function setRequest(){
    	$this->_baseUrl = 'https://gw.api.alibaba.com/openapi';
		$this->_namespace = 'system.oauth2';
    	$this->_addSignature = false;
		
        $request = array(
                'client_id'           => $this->_appKey,
                'client_secret'       => $this->_secretKey,
                'access_token'        => $this->_access_token,
                'refresh_token'       => $this->_refresh_token,
        );
        $this->request = $request;
        return $this;
    }
}