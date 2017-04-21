<?php
/**
 * @desc BulkFetch Packing Label Request
 * @author yangsh
 * @since 2017-03-30
 */
class BulkFetchPackingLabelRequest extends PaytmApiAbstract {
	
    /**
     * @var array fulfillment ids
     */
    protected $_fulfillmentIds = null;

    protected $_template = 'shared';//固定值

    protected $_ffUpdate = 'false';//固定值

    /**
     * @desc 初始化对象
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
		$this->_isPost    = false;

        return $this;
    }

    /**
     * 设置EndPoint
     * @see PaytmApiAbstract::setEndPoint()
     */
    public function setEndPoint() {
        $this->_endpoint = 'v1/merchant/'.$this->_merchantID.'/fulfillment/pdf/bulkfetch?authtoken='.$this->_accessToken;
    }       
	
	/**
	 * @desc 设置请求参数
	 * @see PlatformApiInterface::setRequest()
	 */
	public function setRequest(){
		$request = array(
            'fulfillment_ids' => implode(',',$this->_fulfillmentIds),
            'template'        => $this->_template,
            'ffUpdate'        => $this->_ffUpdate,
		);
		$this->request = $request;
		return $this;
	}

    public function sendRequest() {
        try {
            $this->setUrl();

            if(!empty($_REQUEST['debug'])) {
                echo '<hr>##### serviceUrl: '.$this->_url."<br>";
            }

            $curl = new Curl();
            $curl->init();

            if($this->_isPost){
                $response = $curl->setOption(CURLOPT_TIMEOUT, $this->_timeout)
                                       ->addCertificate()->post($this->_url, $this->getRequestBody());//添加证书
                if(!empty($_REQUEST['debug'])){
                    echo '<br>##### requestBody[POST]: '.htmlspecialchars($this->_requestbody)."<br>";
                }
            }else{
                $response = $curl->setOption(CURLOPT_TIMEOUT, $this->_timeout)
                                       ->addCertificate()->get($this->_url, $this->getRequest());
                if(!empty($_REQUEST['debug'])) {
                    echo '<br><pre>#####requestBody[GET]: ';print_r($this->request);
                }                             
            }
            
            $response2 = new stdClass();
            $curlResponse= $curl->getCurlResponse();
            if($curlResponse->info['http_code'] != 200) {
                $response2->error = $curlResponse->error;
                $response2->body = $response;
            } else {
                $response2 = $response;
            }
            $this->response = $response2;
            if(!empty($_REQUEST['debug']))  {
                echo '<br>##### response: '.var_export($response,true).'<hr>';
            }             

            if( !$this->getIfSuccess() ){
                $this->writeErrorLog();
            }
        } catch (Exception $e ) {
            $this->writeErrorLog();
        }
        return $this;
    }     

}