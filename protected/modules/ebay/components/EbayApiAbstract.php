<?php
/**
 * @desc Ebay API Abstract
 * @author Gordon
 * @since 2015-06-02
 */
abstract class EbayApiAbstract implements PlatformApiInterface {
    
    /**@var string 用户Token*/
    protected  $_usertoken = null;
    
    /**@var string 站点ID*/
    protected $_siteID = 0;//某些不需要传站点的请求默认为美国站
    
    /**@var string 开发者账号ID(与开发者账号绑定)*/
    protected $_devID = null;
    
    /**@var string APP ID(与开发者账号绑定)*/
    protected $_appID = null;
    
    /**@var string 证书ID(与开发者账号绑定)*/
    protected $_certID = null;
    
    /**@var string 请求接口名*/
    protected $_verb = null;
    
    /**@var string 请求版本号*/
    protected $_compatLevel = null;
    
    /**@var string 服务地址*/
    protected $_serverUrl = null;
    
    /**@var string 交互类型*/
    protected $_callType = 'trading';
    
    /**@var string 添加文件传输表头*/
    protected $_boundary = '';
    
    /**@var int 账号ID*/
    protected $accountID = 0;
    
    /**@var string 请求内容*/
    protected $request = null;
    
    /**@var string 返回响应信息*/
    protected $response = null;
    
    protected $_requestbody = null;
    
    /**@var string 异常信息*/
    protected $errorMessage = null;
    
    protected $_xmlsn = 'urn:ebay:apis:eBLBaseComponents';
    
    protected $_isXML = 0;/* 是否返回xml字符串*/
    /**
     * @desc 设置账号信息
     * @param int $accountID
     */
    public function setAccount($accountID){
        $this->accountID = $accountID;
        //获取账号相关信息
        $accountInfo = EbayAccount::getAccountInfoById($this->accountID);
        $ebayKeys = ConfigFactory::getConfig('ebayKeys');
        $this->accountID    = $accountID;
        $this->_usertoken   = $accountInfo['user_token'];
        $this->_appID       = $accountInfo['appid'];
        $this->_devID       = $accountInfo['devid'];
        $this->_certID      = $accountInfo['certid'];
        $this->_serverUrl   = $ebayKeys['serverUrl'];
        $this->_compatLevel = $ebayKeys['compatabilityLevel'];
        return $this;
    }
    
    /**
     * @desc 设置站点ID
     * @param number $siteID
     */
    public function setSiteID($siteID = 0){
        $this->_siteID = $siteID;
        return $this;
    }
    
    /**
     * @desc 设置是否xml格式，0表示对象，1表示字符串
     * @param unknown $xml
     * @return EbayApiAbstract
     */
    public function setIsXML($xml){
    	$this->_isXML = $xml;
    	return $this;
    }
    /**
     * @desc 设置交互接口
     * @param string $verb
     */
    public function setVerb($verb) {
        $this->verb = $verb;
        return $this;
    }
    
    /**
     * @desc 设置报错信息
     * @param string $message
     */
    public function setExceptionMessage($message){
        $this->errorMessage = $message;
        return $this;
    }

    /**
     * @desc 发送请求,获取响应结果
     */
    public function sendRequest() {
        try {
            $ebayAPI = new EbaySession($this->_usertoken, $this->_devID, $this->_devID, $this->_certID, $this->_serverUrl, $this->_compatLevel, $this->_siteID, $this->_verb, $this->_callType, $this->_boundary);
            $requestXmlBody = $this->_requestbody = $this->getRequestXmlBody();
            //MHelper::writefilelog('ebay/'.date("Ymd").'/'.$this->_verb.'RequestXml.log', date("Y-m-d H:i:s").'##########'.$requestXmlBody."\r\n\r\n");//for test
            
            $response = $ebayAPI->sendHttpRequest($requestXmlBody, $this->_isXML);
            //MHelper::writefilelog('ebay/'.date("Ymd").'/'.$this->_verb.'Response.log', date("Y-m-d H:i:s").'##########'.print_r($response,true)."\r\n\r\n");//for test
            
            $this->response = $response;
            if( !$this->getIfSuccess() ){
                $errorMessage = '';
                if( isset($this->response->Errors) ){
                    foreach($this->response->Errors as $error) {
                        $errorMessage .= (string)$error->LongMessage.".";
                    }
                }
                $this->setExceptionMessage($errorMessage);
                //$this->writeErrorLog();
            }
        } catch (Exception $e ) {
            $this->setExceptionMessage($e->getMessage());
        }               
        return $this;
    }  

    /**
     * @desc 将请求参数转化为Xml
     */
    public function getRequestXmlBody(){
        $xmlGeneration = new XmlGenerator();
        return $xmlGeneration->XmlWriter()->push($this->getXmlRequestHeader(), array('xmlns' => $this->_xmlsn))
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
        return $this->_usertoken;
    }
    
    /**
     * @desc 判断交互是否成功
     */
    public function getIfSuccess(){
    	if( isset($this->response->Ack) && ($this->response->Ack=='Success' || $this->response->Ack=='Warning') ){
    		return true;
    	}else{
    		return false;
    	}
    }
    
    /**
     * @desc 获取xml请求头名称
     */
    public function getXmlRequestHeader(){
        return $this->_verb.'Request';
    }
    
    /**
     * @desc 获取失败信息
     * @return string 
     */
    public function getErrorMsg(){
        return $this->errorMessage;
    }
    
    public function setXmlsn($xmlsn){
    	$this->_xmlsn = $xmlsn;
    	return $this;
    }
    /**
     * @desc 记录文件错误日志
     */
    public function writeErrorLog(){
        $logPath = Yii::getPathOfAlias('webroot').'/log/ebay/'.date('Y').'/'.date('m').'/'.date('d').'/'.date('H');
        if( !is_dir($logPath) ){
            mkdir($logPath, 0777, true);
        }
        $log = date('Y-m-d H:i:s').'           '.$this->_verb."\n";//时间，接口名
        $log .= $this->_requestbody."\n";//交互报文
        $log .= 'Error Message:'.$this->getErrorMsg()."\n\n";//错误信息
        $fileName = $this->accountID.'-'.$this->_verb.'-'.$this->_siteID.'.txt';
        file_put_contents($logPath.'/'.$fileName, $log, FILE_APPEND);
    }
}

?>