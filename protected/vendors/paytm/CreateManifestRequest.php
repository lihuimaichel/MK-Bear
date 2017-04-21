<?php
/**
 * @desc Create Manifest Request
 * @author yangsh
 * @since 2017-03-30
 */
class CreateManifestRequest extends PaytmApiAbstract {

    /**
     * @var array fulfillment ids
     */
    protected $_fulfillmentIds = null;

    /**
     * init params
     * @param array $fulfillmentIds 
     */
    public function __construct($fulfillmentIds) {
        parent::__construct();
        $this->_fulfillmentIds = $fulfillmentIds;
    }

    /**
     * @desc 设置账号信息
     * @param int $accountID
     * @see PaytmApiAbstract::setAccount()
     */
    public function setAccount($accountID){
        parent::setAccount($accountID);
		$this->_baseUrl   = $this->paytmKeys['fulfillmentUrl'];
		$this->_isPost    = true;//POST

        return $this;
    }

    /**
     * 设置EndPoint
     * @see PaytmApiAbstract::setEndPoint()
     */
    public function setEndPoint() {
        $this->_endpoint = 'v1/merchant/'.$this->_merchantID.'/fulfillment/manifest/?authtoken='.$this->_accessToken;
    }
	
	/**
	 * @desc 设置请求参数
	 * @see PlatformApiInterface::setRequest()
	 */
	public function setRequest(){
		$request = array(
			'fulfillment_ids' => implode(',',$this->_fulfillmentIds),
		);
		$this->request = $request;
		return $this;
	}	
}