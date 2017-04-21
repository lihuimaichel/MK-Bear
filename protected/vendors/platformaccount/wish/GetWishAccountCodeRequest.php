<?php
/**
 * @desc 通过redirect_uri获取code
 * @author hanxy
 * @since 2017-02-27
 */
class GetWishAccountCodeRequest extends WishApiAbstract{ 

    public $_get_wish_code_url='';

    /** @var string redirect_uri **/
    public $_redirect_uri = 'https://wish.platform.vakind.com';

    /**
     * @desc 设置请求参数
     * @see AliexpressApiAbstract::setRequest()
     */

    public function setRequest(){
    	// $this->_baseUrl = 'https://sandbox.merchant.wish.com/oauth/authorize?';
        $request = array(
                'client_id'         => $this->_clientID,
             	'redirect_uri'      => $this->_redirect_uri,
        );
        $request['_url']=$this->_authorizationUrl.'?client_id='.$this->_clientID.'&redirect_uri='.$this->_redirect_uri;
        $this->_get_wish_code_url = $request['_url'];
        return $this;
    }


    /**
     * @desc 获取redirect_uri
     * @return string
     */
    public function setRedirectUri($uri){
        $this->_redirect_uri = $uri;
    }
} 