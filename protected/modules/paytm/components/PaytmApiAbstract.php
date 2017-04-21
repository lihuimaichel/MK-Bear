<?php
/**
 * @desc Paytm API Abstract
 * @since 2017-02-28
 */
abstract class PaytmApiAbstract implements PlatformApiInterface {
    
    /**@var string 最终交互地址*/
    protected $_url = null;

    /**@var string 服务地址*/
    protected $_baseUrl = null;
    
    /**@var string 交互Endpoint*/
    protected $_endpoint = null;
    
    /**@var int 账号ID*/
    protected $accountID = 0;

    /*@var string EMAIL*/
    protected $_email = null;

    /*@var string merchant id*/
    protected $_merchantID = null;

    /** @var string client id **/
    protected $_clientID = null;
    
    /** @var string client秘钥 **/
    protected $_clientSecret = null;

    /**@var string 用户Token*/
    protected $_accessToken = null;
    
    /** @var string refresh token **/
    protected $_refreshToken = null;    

    /* @var string redirect_uri */
    protected $_redirectUri = null;

    /** @var int token过期时间 **/
    protected $_tokenExpiredTime = null;

    /**@var string paytmKeys配置*/
    protected $paytmKeys = null; 
    
    /**@var string 请求内容*/
    protected $request = null;
    
    /**@var string 返回响应信息*/
    protected $response = null;
    
    /**@var string 请求报文信息*/
    protected $_requestbody = null;

    /** @var boolean 是否为Post交互*/
    protected $_isPost = FALSE;

    /* @var int timeout */
    protected $_timeout = 1800;
    
    /**
     * @desc 初始化对象
     */    
    public function __construct(){
        $paytmKeys = ConfigFactory::getConfig('paytmKeys');
        if(empty($paytmKeys)) {
            throw new Exception("paytmKeys Not Exist");
        }
        $this->paytmKeys = $paytmKeys;
    }

    /**
     * @desc 设置账号信息
     * @param int $accountID
     */
    public function setAccount($accountID){
        $accountInfo                = PaytmAccount::getAccountInfoById($accountID);//获取账号相关信息
        if(empty($accountInfo)) {
            throw new Exception("Account Not Exist");
        }
        $this->accountID            = $accountID;
        $this->_email               = $accountInfo['email'];
        $this->_accessToken         = $accountInfo['access_token'];
        $this->_refreshToken        = $accountInfo['refresh_token'];
        $this->_clientID            = $accountInfo['client_id'];
        $this->_clientSecret        = $accountInfo['client_secret'];
        $this->_merchantID          = $accountInfo['merchant_id'];
        $this->_tokenExpiredTime    = $accountInfo['token_expired_time'];
        $this->_redirectUri         = $accountInfo['redirect_uri'];

        return $this;
    }

    /**
     * @desc 判断token是否过期
     * @return boolean
     */
    public function isTokenExpired() {
        return $this->_tokenExpiredTime < time();
    }

    public function setPost( $isPost) {
        $this->_isPost = $isPost;
        return $this;
    }   

    public function isPost() {
        return $this->_isPost;
    }     
    
    public function setTimeout($timeout) {
        $this->_timeout = $timeout;
        return $this;
    }

    public function getTimeout() {
        return $this->_timeout;
    }    

    /**
     * @desc 设置Endpoint
     */
    public abstract function setEndPoint();    

    /**
     * @desc 设置交互链接
     * @see PaytmApiAbstract::setUrl()
     */
    public function setUrl() {
        $this->setEndPoint();
        $this->_url = $this->_baseUrl . $this->_endpoint;
    }      

    /**
     * @desc 发送请求,获取响应结果
     */
    public function sendRequest() {
        try {
            $this->setUrl();

            if(!empty($_REQUEST['debug'])) {
                echo '<hr>##### serviceUrl: '.$this->_url."<br>";
            }
            if($this->_isPost){
                $response = Yii::app()->curl
                					   ->setOption(CURLOPT_TIMEOUT, $this->_timeout)
                					   ->addCertificate()->post($this->_url, $this->getRequestBody());//添加证书
                if(!empty($_REQUEST['debug'])){
                    echo '<br>##### requestBody[POST]: '.htmlspecialchars($this->_requestbody)."<br>";
                }
            }else{
                $response = Yii::app()->curl
                					   ->setOption(CURLOPT_TIMEOUT, $this->_timeout)
                					   ->addCertificate()->get($this->_url, $this->getRequest());
                if(!empty($_REQUEST['debug'])) {
                    echo '<br><pre>#####requestBody[GET]: ';print_r($this->request);
                }                             
            }

            $this->response = json_decode($response);
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
     * @desc 判断交互是否成功
     * @return boolean
     */
    public function getIfSuccess(){
        return isset($this->response->error) ? false : true;
    }

    /**
     * @desc 获取token
     * @return string
     */
    public function getToken(){
        return $this->_token;
    }   
    
    /**
     * @desc 获取失败信息
     * @return string 
     */
    public function getErrorMsg(){
    	$errorMessage = '';
    	if( isset($this->response->error) ){
	    	$errorMessage .= $this->response->error;
    	}
    	return $errorMessage;
    }
    
    /**
     * @desc 记录文件错误日志
     */
    public function writeErrorLog(){
        $logPath = Yii::getPathOfAlias('webroot').'/log/paytm/'.date('Y').'/'.date('m').'/'.date('d').'/'.date('H');
        if( !is_dir($logPath) ){
            mkdir($logPath, 0777, true);
        }
        $log = date('Y-m-d H:i:s')."\n";//时间，接口名
        $log .= $this->_requestbody."\n";//交互报文
        $log .= 'Error Message:'.$this->getErrorMsg()."\n\n";//错误信息
        $fileName = $this->accountID.'-'.date("YmdHis").'.txt';
        file_put_contents($logPath.'/'.$fileName, $log, FILE_APPEND);
    }
    
}

?>