<?php
/**
 * @desc 帐号授权
 * @author guoll
 * @since 2015-09-09
 */
class GetAccountAuthorizeRequest extends AliexpressApiAbstract{ 

	
	public $redirect_uri='http://127.0.0.5/aliexpress/aliexpressaccount/redirecurl';
	public $site='aliexpress';
	public $_ali_url='';
    /**
     * @desc 设置请求参数
     * @see AliexpressApiAbstract::setRequest()
     */

    public function setRequest(){
    	$str = 'client_id'.$this->_appKey.'redirect_uri'.$this->redirect_uri.'site'.$this->site.'state'.$this->accountID;
    	$sign = strtoupper(bin2hex(hash_hmac('sha1', $str,$this->_secretKey, true)));
    	$this->_baseUrl = 'http://authhz.alibaba.com/auth/authorize.htm?';
    	$this->_authorize=true;
        $request = array(
                'client_id'         => $this->_appKey,
                'site'       		=> $this->site,
             	'redirect_uri'      => $this->redirect_uri,
        		'state'				=> $this->accountID
        );
        $request['_aop_signature']=$sign;
        $request['_url']=$this->_baseUrl.'client_id='.$this->_appKey.'&site='.$this->site.'&state='.$this->accountID.'&redirect_uri='.$this->redirect_uri.'&_aop_signature='.$request['_aop_signature'];
        $this->_ali_url = $request['_url'];
        return $this;
    }
} 