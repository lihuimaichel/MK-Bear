<?php
/**
 * @desc 获取alirxpress acount access_token
 * @author guoll
 * @since 2015-09-09
 */
class GetAccountTokenRequest extends AliexpressApiAbstract{ 

	public $_apiMethod = 'getToken';
	
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
                'grant_type'          => 'refresh_token',
                'client_id'           => $this->_appKey,
                'client_secret'       => $this->_secretKey,
                'refresh_token'       => $this->_refresh_token,
        );
        $this->request = $request;
        return $this;
    }
}