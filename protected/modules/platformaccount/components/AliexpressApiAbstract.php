<?php
/**
 * @desc Aliexpress API Abstract
 * @author Gordon
 * @since 2015-06-25
 */
abstract class AliexpressApiAbstract implements PlatformApiInterface {
    
    /**@var string 用户访问Token*/
    private  $_access_token = null;
    
    /**@var string 用户刷新Token*/
    protected $_refresh_token = null;
    
    /**@var string appKey*/
    protected $_appKey = null;
    
    /**@var string secretKey*/
    protected $_secretKey = null;
    
    /**@var string 签名*/
    protected $_signature = null;
    
    /**@var string 协议*/
    protected $_protocol = null;
    
    /**@var string 版本*/
    protected $_version = null;
    
    /**@var string 命名空间*/
    protected $_namespace = null;
    
    /**@var string 接口名*/
    protected $_apiMethod = null;
    
    /**@var string 服务地址*/
    protected $_baseUrl = null;
    
    /**@var string 交互urlPath*/
    protected $_urlPath = null;
    
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
    
    /**@var string 是否添加签名*/
    protected $_addSignature = true;
    
    /**@var string 获取的code*/
    protected $_code='';
    
    protected $_authorize = null;
    /**
     * @desc 设置账号信息
     * @param int $accountID
     */
    public function setAccount($accountID,$code=null){
    	$this->_code=$code;
        $this->accountID = $accountID;
        //获取账号相关信息
        $accountInfo = PlatformAliexpressAccount::getAccountInfoById($this->accountID);
        $aliexpressKeys = ConfigFactory::getConfig('aliexpressKeys');
        $this->accountID        = $accountID;
        $this->_access_token    = $accountInfo['access_token'];
        $this->_refresh_token   = $accountInfo['refresh_token'];
        $this->_appKey          = $accountInfo['app_key'];
        $this->_secretKey       = $accountInfo['secret_key'];
        $this->_baseUrl         = $aliexpressKeys['baseUrl'];
        $this->_version         = $aliexpressKeys['version'];
        $this->_protocol        = $aliexpressKeys['protocol'];
        $this->_namespace       = $aliexpressKeys['namespace'];
        $this->setApiMethod();
        
        return $this;
    }
    
    /**
     * @desc 设置交互urlPath
     */
    public function setUrlPath(){
        $this->_urlPath = $this->_protocol .'/'. $this->_version .'/'. $this->_namespace .'/'.$this->_apiMethod .'/'.$this->_appKey;
    }
    
    /**
     * @desc 设置交互链接
     */
    public function setUrl(){
        $this->setUrlPath();
        $this->_url = $this->_baseUrl .'/'. $this->_urlPath;
    }
    
    /**
     * @desc 设置API交互接口
     */
    public function setApiMethod(){}
    
    /**
     * @desc 设置签名
     */
    public function setSignature(){
        $request = $this->getRequest();
        if( empty($request) ){
            return;
        }
        ksort($request);
        $signatureStr = $this->getUrlPath();
        foreach($request as $key=>$value){
            $signatureStr .= $key.$value;
        }
        $this->_signature = strtoupper(bin2hex(hash_hmac('sha1', $signatureStr, $this->_secretKey, true)));
     	if($this->_authorize){
      	  $this->_signature=$request['_aop_signature'];
      	}
    }

    /**
     * @desc 发送请求,获取响应结果
     */
    public function sendRequest() {
        try {
            $this->setUrl();
            $url = $this->getUrl();
            $requestBody = $this->getRequestBody();

            //MHelper::writefilelog('aliexpress/'.date("Ymd").'/'.$this->accountID.'/'.$this->_apiMethod.'/'.'Request_'.date("H").'.log', date("Y-m-d H:i:s").' #####request##### '.$url.' @@@@@@@@@@@@ '.$this->_requestbody."\r\n\r\n");//test

            $response = Yii::app()->curl->post($url, $requestBody);

            //MHelper::writefilelog('aliexpress/'.date("Ymd").'/'.$this->accountID.'/'.$this->_apiMethod.'/'.'Response_'.date("H").'.log', date("Y-m-d H:i:s").' ######response#### '.$url.' @@@@@@@@@@@@@@ '.$response."\r\n\r\n");//test

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
        $this->request['access_token'] = $this->_access_token;
        if( $this->_addSignature ){
            $this->setSignature();
            $this->request['_aop_signature'] = $this->_signature;
        }
        $this->transParam();
        return $this->_requestbody;
    }
    
    /**
     * @desc 转换参数
     */
    private function transParam(){
        $requestBody = $this->getRequest();
        $bodyStr = '';
        $postMultipart = false;
        foreach ($requestBody as $k => $v) {
            if( substr($v, 0, 1) != "@" ) {
               $bodyStr .= "$k=". urlencode($v)."&";
            }else {
                $postMultipart = true;
            }
        }
        unset($k, $v);
        if ($postMultipart) {
            $this->_requestbody = $requestBody;
        } else {
            $this->_requestbody = substr($bodyStr,0,-1);
        }
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
        return $this->_access_token;
    }
    
    /**
     * @desc 返回urlPath
     * @return string
     */
    public function getUrlPath(){
        return $this->_urlPath;
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
    	if (isset($this->response->error_code) && $this->response->error_code != 0) {
    		return false;
    	}else if (isset($this->response->errorCode) && $this->response->errorCode != 0) {
    		return false;
    	}else if (isset($this->response->success) && $this->response->success != true) {
    		return false;
    	}else if(empty($this->response)){
    		return false;
    	}
		return true;
    }
    
    /**
     * @desc 获取失败信息
     * @return string 
     */
    public function getErrorMsg(){
    	$errorMessage = '';
    	if( isset($this->response->error_message) ){
	    	$errorMessage .= $this->response->error_message;
    	}elseif( isset($this->response->errorMsg) ){
	    	$errorMessage .= $this->response->errorMsg;
    	}
    	return $errorMessage;
    }
    
    /**
     * @desc 获取失败code
     * @return string
     */
    public function getErrorCode(){
    	$errorCode = '';
    	if( isset($this->response->error_code) ){
    		$errorCode = $this->response->error_code;
    	}elseif( isset($this->response->errorCode) ){
    		$errorCode = $this->response->errorCode;
    	}
    	return $errorCode;
    }
    
    /**
     * @desc 记录文件错误日志
     */
    public function writeErrorLog(){
        $logPath = Yii::getPathOfAlias('webroot').'/log/aliexpress/'.date('Y').'/'.date('m').'/'.date('d').'/'.date('H');
        if( !is_dir($logPath) ){
            mkdir($logPath, 0777, true);
        }
        $log = date('Y-m-d H:i:s').'           '.$this->_url."\n";//时间，接口名
        $log .= $this->_requestbody."\n";//交互报文
        $log .= 'Error Message:'.$this->getErrorMsg()."\n\n";//错误信息
        $fileName = $this->accountID.'-'.str_replace('api.', '', $this->_apiMethod).'.txt';
        file_put_contents($logPath.'/'.$fileName, $log, FILE_APPEND);
    }
}

?>