<?php
/**
 * @desc Lazada API Abstract
 * @author Gordon
 * @since 2015-08-07
 */
abstract class LazadaNewApiAbstract implements PlatformApiInterface {
    
    /**@var int 账号自增ID*/
    protected $_accountAutoID = 0;
    
    /**@var int 账号ID*/
    protected $accountID = 0;
    
    /**@var integer 站点ID **/
    protected $_siteID = null;

    /**@var string 用户名*/
    protected $_userID = null;    

    /**@var string 最终交互地址*/
    protected $_url = null;

    /**@var string 用户访问Token*/
    private $_token = null;
    
    /**@var string 版本*/
    private $_version = '1.0';
    
    /**@var string 接口名*/
    protected $_apiMethod = null;
    
    /**@var array 系统型参数*/
    protected $_parameters = null;
    
    /**@var string 请求内容*/
    protected $request = null;
    
    /**@var string 返回响应信息*/
    protected $response = null;
    
    /**@var string 请求报文信息*/
    protected $_requestbody = null;
    
    /**@var string 请求方式(GET/POST)*/
    public $_httpMethod = 'POST';

    private $_format = 'XML';//默认用xml
    
    private $_errorMsg = '';

    /**
     * @desc 设置账号信息
     * @param int $accountID
     */
    public function setAccount($accountID, $siteID=null){
        if (is_null($siteID)) {
            $siteID = $this->_siteID;
        }        
        $lazadaKeys = ConfigFactory::getConfig('lazadaNewKeys');
        if (empty($lazadaKeys)) {
            die('lazadaNewKeys is not exist');
        }
        //获取账号相关信息
        $accountInfo = LazadaAccount::model()->getApiAccountByIDAndSite($accountID, $siteID);
        $this->_accountAutoID   = $accountInfo['id'];
        $this->accountID        = $accountID;
        $this->_siteID          = $siteID;
        $this->_userID          = $accountInfo['email'];
        $this->_token           = $accountInfo[$lazadaKeys['token']];
        $this->_url             = $accountInfo[$lazadaKeys['server_url']];
        return $this;
    } 

    /**
     * @desc 设置账号信息 --- new
     * @param int $accountAutoID
     * @author yangsh
     * @since 2016-10-07
     */
    public function setApiAccount($accountAutoID){
        $lazadaKeys = ConfigFactory::getConfig('lazadaNewKeys');
        if (empty($lazadaKeys)) {
            die('lazadaNewKeys is not exist');
        }
        //获取账号相关信息
        $accountInfo = LazadaAccount::model()->getApiAccountInfoByID($accountAutoID);
        $this->_accountAutoID   = $accountAutoID;
        $this->accountID        = $accountInfo['account_id'];
        $this->_siteID          = $accountInfo['site_id'];
        $this->_userID          = $accountInfo['email'];
        $this->_token           = $accountInfo[$lazadaKeys['token']];
        $this->_url             = $accountInfo[$lazadaKeys['server_url']];
        return $this;
    }

    public function setSiteID($siteID) {
        $this->_siteID = $siteID;
        return $this;
    }  
    
    /**
     * @decs 设置系统型参数
     */
    public function setParameters(){
        $now = new DateTime();
        $parameters = array(
            'UserID'    => $this->_userID,
            'Version'   => $this->_version,
            'Action'    => $this->_apiMethod,
            'Format'    => $this->_format,
            'Timestamp' => $now->format(DateTime::ISO8601),
        );
        if($this->_httpMethod=='GET'){
            $parameters = array_merge($parameters, $this->request);
        }
        ksort($parameters);
        $this->_parameters = $parameters;
        $this->setSignature();
    }

    /**
     * @desc 设置签名
     */
    public function setSignature(){
        $params = array();
        foreach ($this->_parameters as $name=>$value) {
            $params[] = rawurlencode($name) . '=' . rawurlencode($value);
        }
        $strToSign = implode('&', $params);
        $this->_parameters['Signature'] = rawurlencode(hash_hmac('sha256', $strToSign, $this->_token, false));
    }    
    
    /**
     * @desc 设置Url
     */
    public function setUrl(){
        $queryString = http_build_query($this->_parameters, '', '&');
        $this->_url .= '?'. $queryString;
    }

    public function setToken($token){
    	$this->_token = $token;
    	return $this;
    }

    public function setUserID($userID){
    	$this->_userID = $userID;
    	return $this;
    }  

    /**
     * @desc 发送请求,获取响应结果
     */
    public function sendRequest() {
        $this->setParameters();
        $this->setUrl();
        try {
            $path = 'lazada/'.date("Ymd").'/'.$this->_siteID.'-'.$this->accountID.'/'.$this->_apiMethod.'/';
            if($this->_httpMethod=='GET'){
                $url = $this->getUrl();
                //MHelper::writefilelog($path.'RequestNew_'.date("H").'.log', date("Y-m-d H:i:s").' #####request##### '.$url."\r\n\r\n");//test
                $response = Yii::app()->curl
                            ->setOption(CURLOPT_TIMEOUT,300)->setOption(CURLOPT_CONNECTTIMEOUT,300)
                            ->get($url);
            }else{
                $this->_requestbody = $this->getRequestXmlBody();
                //MHelper::writefilelog($path.'RequestNew_'.date("H").'.log', date("Y-m-d H:i:s").' #####request##### '.$this->_requestbody."\r\n\r\n");//test
                $response = Yii::app()->curl
                            ->setOption(CURLOPT_TIMEOUT,300)->setOption(CURLOPT_CONNECTTIMEOUT,300)
                            ->post($this->getUrl(), $this->_requestbody);
            }
            //MHelper::writefilelog($path.'ResponseNew_'.date("H").'.log', date("Y-m-d H:i:s").' ######response#### '.$response."\r\n\r\n");//test

            $this->response = @simplexml_load_string($response);
            if( !$this->getIfSuccess() ){
                $this->writeErrorLog();
            }
        } catch (Exception $e ) {
            $this->setErrorMsg($e->getMessage());
            $this->writeErrorLog();
        }
        return $this;
    }
    
    /**
     * @desc 将请求参数转化为Xml
     */
    public function getRequestXmlBody(){
        $requestBody = $this->getRequest();
        $xmlGeneration = new XmlGenerator();
        return $xmlGeneration->XmlWriter()->push('Request')
                ->buildXMLFilter($this->getRequest())
                ->pop()
                ->getXml();
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
     * @desc 返回url
     * @return string
     */
    public function getUrl(){
        return $this->_url;
    }
    
    /**
     * @desc 返回签名
     * @return string
     */
    public function getSignature(){
        return $this->_signature;
    }
    
    /**
     * @desc 判断交互是否成功
     */
    public function getIfSuccess(){
        return empty($this->response) || isset($this->response->Head->ErrorCode) ? false : true;
    }
    
    /**
     * @desc 获取失败信息
     * @return string 
     */
    public function getErrorMsg(){
    	$errorMessage = $this->_errorMsg;
        if( isset($this->response->Head->ErrorMessage) ){
            $errorMessage .= $this->response->Head->ErrorMessage;
        }        
    	return $errorMessage;
    }
    
    public function setErrorMsg($msg){
   		$this->_errorMsg = $msg;
    	return $this;
    }
    /**
     * @desc 记录文件错误日志
     */
    public function writeErrorLog(){
        $logPath = Yii::getPathOfAlias('webroot').'/log/lazada/'.date('Y').'/'.date('m').'/'.date('d').'/'.date('H');
        if( !is_dir($logPath) ){
            mkdir($logPath, 0777, true);
        }
        $log = date('Y-m-d H:i:s').'           '.$this->_url."\n";//时间，接口名
        $log .= $this->_requestbody."\n";//交互报文
        $log .= 'Error Message:'.$this->getErrorMsg()."\n\n";//错误信息
        $fileName = $this->accountID.'-'.$this->_apiMethod.'.txt';
        file_put_contents($logPath.'/'.$fileName, $log, FILE_APPEND);
    }
}

?>