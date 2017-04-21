<?php
/**
 * @desc shopee API Abstract
 * @author lihy
 * @since 
 */
abstract class ShopeeApiAbstract implements PlatformApiInterface {

    /**@var string 服务地址*/
    protected $_baseUrl = null;
    
    /**@var string 交互Endpoint*/
    protected $_endpoint = null;
    
    /**@var string 最终交互地址*/
    protected $_url = null;
    
    /**@var int 账号ID*/
    protected $accountID = 0;
    
    /**@var string 请求内容*/
    protected $request = null;
    
    /**@var string 返回响应信息*/
    protected $response = null;
    
    /**@var string 请求报文信息*/
    protected $_requestbody = null;
    
    /** @var boolean 是否为Post交互*/
    protected $_isPost = true;
    

    /**
     * 秘钥key
     * @var unknown
     */
	protected $_secretkey = null;
    /** @var string client id **/
    protected $_partnerID = null;
    
    /** @var string client秘钥 **/
    protected $_shopID = null;    
    /**
     * curl timeout
     * @var unknown
     */
    protected $_timeout = 1800;
    
    protected $_authorization = null;
    protected $_contentType = "application/json";
    protected $_headers = null;
    /**
     partner_id
     shop_id	One
     timestamp
    
     HMAC-SHA256
    
     https://partner.shopeemobile.com/api/v1/orders/detail|{"ordersn": "160726152598865", "shopid": 61299, "partner_id": 1, "timestamp": 1470198856}
    
     mhash(MHASH_SHA256, $string, serctyKEY);
     */
    /**
     * @desc 设置账号信息
     * @param int $accountID
     */
    public function setAccount($accountID){
        $accountInfo                = ShopeeAccount::getAccountInfoById($accountID);//获取账号相关信息
        $shopeeKeys                 = ConfigFactory::getConfig('shopeeKeys');
        $this->accountID            = $accountID;
        $this->_partnerID			= $accountInfo['partner_id'];
        $this->_shopID				= $accountInfo['shop_id'];
        $this->_secretkey			= trim($accountInfo['secret_key']);
        $this->_baseUrl             = $shopeeKeys['baseUrl'];

        return $this;
    }
    
    /**
     * @desc 设置endpoint
     * @param string $verb
     */
    public function setEndpoint($endpoint, $isPost = true) {
        $this->_endpoint = $endpoint;
        $this->_isPost = $isPost;
        return $this;
    }
    
    /**
     * @desc 设置交互链接
     */
    public function setUrl(){
        $this->_url = $this->_baseUrl . $this->_endpoint;
    }

    public function setSignKey(){
	    $jsonStr = $this->_url."|". $this->_requestbody;
    	$this->_authorization = rawurlencode(hash_hmac('sha256', $jsonStr, trim($this->_secretkey), false));
    }
    
    public function setHeader(){
    	$headers = array(
    		'Content-Type'   => $this->_contentType,
    		'Authorization'  => $this->_authorization
    	);
    	$this->_headers = $headers;
    }

    /**
     * @desc 发送请求,获取响应结果
     */
    public function sendRequest() {
        try {
            $path = 'shopee/'.date("Ymd").'/'.$this->accountID.'/'.str_replace('/','-',$this->_endpoint).'/';
            $this->setUrl();
            $this->getRequestBody();
            //计算签名
           	$this->setSignKey();
           	$this->setHeader();

            if($this->_isPost){
                $response = Yii::app()->curl
                                        ->addCertificate()
                                        ->setOption(CURLOPT_TIMEOUT, $this->_timeout)
                                        ->setHeaders($this->_headers)
                                        ->post($this->_url, $this->_requestbody);//添加证书
                //MHelper::writefilelog($path.'Request_'.date("H").'.log', date("Y-m-d H:i:s").' #####request##### '.$this->_url.'  '.$this->_requestbody."\r\n\r\n");
            }else{
                $response = Yii::app()->curl
                                        ->addCertificate()
                                        ->setOption(CURLOPT_TIMEOUT, $this->_timeout)
                                        ->setHeaders($this->_headers)
                                        ->get($this->_url, $this->getRequestBody());
            }
            //MHelper::writefilelog($path.'Response_'.date("H").'.log', date("Y-m-d H:i:s").' @@ '.$response ."\r\n\r\n");
            $this->response = json_decode($response);
            if( !$this->getIfSuccess() ){
                $this->writeErrorLog();
            }
        } catch (Exception $e ) {
            $this->writeErrorLog();
        }
        return $this;
    }  

    /**
     * @desc 将请求参数转化为Json
     */
    public function getRequestBody(){
        $requestBody = $this->getRequest();
        $requestBody['shopid'] = (int)$this->_shopID;
        $requestBody['partner_id'] = (int)$this->_partnerID;
        $requestBody['timestamp'] = time();
        $this->_requestbody = json_encode($requestBody);
        return $this->_requestbody;
    }
    
    /**
     * @desc 获取请求参数
     * @see ApiInterface::getRequest()
     */
    public function getRequest() {
        return $this->request;
    }
    
    /**
     * @desc 获取响应结果
     * @see ApiInterface::getResponse()
     */
    public function getResponse() {
        return $this->response;
    }
    
    /**
     * @desc 获取token
     * @return string
     */
    public function getToken(){
        return $this->_token;
    }
    
    /**
     * @desc 判断交互是否成功
     */
    public function getIfSuccess(){
    	if( isset($this->response->error) ){
    		return false;
    	}else{
    		return true;
    	}
    }
    
    /**
     * @desc 获取失败信息
     * @return string 
     */
    public function getErrorMsg(){
    	$errorMessage = '';
    	if( isset($this->response->message) ){
	    	$errorMessage .= $this->response->message;
    	}else{
    		$errorMessage = 'unkown';
    	}
    	return $errorMessage;
    }
    
    /**
     * @desc 记录文件错误日志
     */
    public function writeErrorLog(){
        $logPath = Yii::getPathOfAlias('webroot').'/log/shopee/'.date('Y').'/'.date('m').'/'.date('d').'/'.date('H');
        if( !is_dir($logPath) ){
            mkdir($logPath, 0777, true);
        }
        $log = date('Y-m-d H:i:s').'           '.$this->_url."\n";//时间，接口名
        $log .= $this->_requestbody."\n";//交互报文
        $log .= 'Error Message:'.$this->getErrorMsg()."\n\n";//错误信息
        $fileName = $this->accountID.'-'.str_replace('/', '_', $this->_endpoint).'.txt';
        file_put_contents($logPath.'/'.$fileName, $log, FILE_APPEND);
    }
    
}

?>