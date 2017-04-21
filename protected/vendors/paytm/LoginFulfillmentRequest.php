<?php
/**
 * @desc Login at Fulfillment Request
 * @author yangsh
 * @since 2017-02-28
 */
class LoginFulfillmentRequest extends PaytmApiAbstract {
		
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
		$this->_baseUrl   = $this->paytmKeys['fulfillmentUrl'];
		$this->_isPost    = false;//GET

        return $this;
    }

    /**
     * 设置EndPoint
     * @see PaytmApiAbstract::setEndPoint()
     */
    public function setEndPoint() {
        $this->_endpoint = 'authorize';
    }       
	
	/**
	 * @desc 设置请求参数
	 * @see PlatformApiInterface::setRequest()
	 */
	public function setRequest(){
		$request = array(
			'authtoken' => $this->_accessToken,
		);
		$this->request = $request;
		return $this;
	}	
}