<?php
/**
 * @desc Joom API Abstract
 * @author Gordon
 * @since 2015-06-22
 */
abstract class JoomApiAbstract implements PlatformApiInterface {
    
    /**@var string 用户Token*/
    protected $_accessToken = null;
    
    /** @var string refresh token **/
    protected $_refreshToken = null;
    
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
    protected $_isPost = FALSE;
    
    /** @var int token过期时间 **/
    protected $_tokenExpiredTime = null;

    /** @var string client id **/
    protected $_clientID = null;
    
    /** @var string client秘钥 **/
    protected $_clientSecret = null;    
    /**
     * curl timeout
     * @var unknown
     */
    protected $_timeout = 30;
    
    /**
     * @desc 设置账号信息
     * @param int $accountID
     */
    public function setAccount($accountID){

        $this->accountID = $accountID;
        //获取账号相关信息
        $accountInfo = JoomAccount::getAccountInfoById($this->accountID);

        $joomKeys = ConfigFactory::getConfig('joomKeys');

        $this->accountID    = $accountID;
        $this->_accessToken = $accountInfo['access_token'];
        $this->_refreshToken = $accountInfo['refresh_token'];
        $this->_clientID = $accountInfo['client_id'];
        $this->_clientSecret = $accountInfo['client_secret'];
        $this->_tokenExpiredTime = $accountInfo['token_expired_time'];
        $this->_baseUrl     = $joomKeys['baseUrl'];

        //如果token过期，重新获取新的token更新到账号表
        /*if ($this->isTokenExpired()) {
        	//$this->refreshToken();
        } */

        return $this;
    }
    
    /**
     * @desc 判断token是否过期
     * @return boolean
     */
    public function isTokenExpired() {
    	return $this->_tokenExpiredTime < time();
    }
    
    /**
     * @desc 通过refreshtoken换取access token
     */
    public function refreshToken() {
    	$RefreshTokenRequest = new RefreshTokenRequest();
    	$response = $RefreshTokenRequest->setAccount($this->accountID)->setRequest()->sendRequest()->getResponse();
    	if ($RefreshTokenRequest->getIfSuccess()) {
    		//将token更新到账号表
    		$this->_accessToken = $response->data->access_token;
    		$this->_refreshToken = $response->data->refresh_token;
    		$this->_tokenExpiredTime = $response->data->expiry_time;
    		$data = array(
    			'access_token' => $this->_accessToken,
    			'refresh_token' => $this->_refreshToken,
    			'token_expired_time' => $this->_tokenExpiredTime,
    		);
    		JoomAccount::model()->updateByPk($this->accountID, $data);
    	}
    }
    
    /**
     * @desc 设置endpoint
     * @param string $verb
     */
    public function setEndpoint($endpoint, $isPost = false) {
        $this->_endpoint = $endpoint;
        $this->_isPost = $isPost;
        return $this;
    }
    
    /**
     * @desc 设置交互链接
     */
    public function setUrl(){
        $this->setEndpoint();
        $this->_url = $this->_baseUrl . $this->_endpoint.'?access_token='.$this->_accessToken;
    }

    /**
     * @desc 发送请求,获取响应结果
     */
    public function sendRequest() {
        try {
            $this->setUrl();
            if($this->_isPost){
                $response = Yii::app()->curl
                						->setOption(CURLOPT_TIMEOUT, $this->_timeout)
                						->addCertificate()->post($this->_url, $this->getRequestBody());//添加证书
            }else{
                $response = Yii::app()->curl
                						->setOption(CURLOPT_TIMEOUT, $this->_timeout)
                						->addCertificate()->get($this->_url, $this->getRequest());
            }
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
        $this->_requestbody = http_build_query($requestBody);
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
    	if( isset($this->response->code) && ( $this->response->code==0 ) ){
    		return true;
    	}else{
    		return false;
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
        $logPath = Yii::getPathOfAlias('webroot').'/log/joom/'.date('Y').'/'.date('m').'/'.date('d').'/'.date('H');
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