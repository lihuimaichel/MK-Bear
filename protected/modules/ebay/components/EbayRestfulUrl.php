<?php
/**
 * @desc eBay RESTful URL
 * @author yangsh
 * @since 2017-03-11
 */
class EbayRestfulUrl {

    /**@var string 最终交互url地址*/
    protected $_serviceUrl = null;

    /**@var string url path*/
    protected $_urlPath = null;    

    /**@var string base url*/
    protected $_baseUrl = null;

    /**@var string 调用类型*/
    protected $_callType = null;

    /**@var string api名称*/
    protected $_apiName = null;

    /**@var string api版本号*/
    protected $_apiVersion = null;

    /**@var string api方法*/
    protected $_apiMethod = null;

    /**@var string path参数*/
    protected $_pathParameter = null;

    /**
     * @desc 初始化对象
     */
    public function __construct($baseUrl, $callType, $apiName, $apiVersion, $apiMethod, $pathParameter){
        $this->_baseUrl       = $baseUrl;
        $this->_callType      = $callType;
        $this->_apiName       = $apiName;
        $this->_apiVersion    = $apiVersion;
        $this->_apiMethod     = $apiMethod;
        $this->_pathParameter = $pathParameter;
    }

    /**
     * Getter for serviceUrl
     * @return string
     */
    public function getServiceUrl(){
        return $this->_serviceUrl;
    }
    
    /**
     * Setter for serviceUrl
     * @return self
     */
    public function setServiceUrl(){
        $this->setUrlPath();
        $this->_serviceUrl = $this->_baseUrl . $this->_urlPath;
        return $this;
    }

    /**
     * Getter for urlPath
     * @return string
     */
    public function getUrlPath(){
        return $this->_urlPath;
    }
    
    /**
     * Setter for urlPath
     * @return self
     */
    public function setUrlPath(){
        $this->_urlPath = '';
        if($this->_callType) {
            $this->_urlPath .= $this->_callType .'/';
        }
        if($this->_apiName) {
            $this->_urlPath .= $this->_apiName .'/';
        }    
        if($this->_apiVersion) {
            $this->_urlPath .= $this->_apiVersion .'/';
        }    
        if($this->_apiMethod) {
            $this->_urlPath .= $this->_apiMethod .'/';
        }    
        if($this->_pathParameter) {
            $this->_urlPath .= $this->_pathParameter .'/';
        }  
        $this->_urlPath = substr($this->_urlPath,0,-1);  

        return $this;
    }    
    
    /**
     * Getter for baseUrl
     * @return string
     */
    public function getBaseUrl(){
        return $this->_baseUrl;
    }
    
    /**
     * Setter for baseUrl
     * @param baseUrl value to set
     * @return self
     */
    public function setBaseUrl($baseUrl){
        $this->_baseUrl = $baseUrl;
        return $this;
    }
    
    /**
     * Getter for callType
     * @return string
     */
    public function getCallType(){
        return $this->_callType;
    }
    
    /**
     * Setter for callType
     * @param callType value to set
     * @return self
     */
    public function setCallType($callType){
        $this->_callType = $callType;
        return $this;
    }

    /**
     * Getter for apiName
     * @return string
     */
    public function getApiName(){
        return $this->_apiName;
    }
    
    /**
     * Setter for apiName
     * @param apiName value to set
     * @return self
     */
    public function setApiName($apiName){
        $this->_apiName = $apiName;
        return $this;
    }
    
    /**
     * Getter for apiVersion
     * @return string
     */
    public function getApiVersion(){
        return $this->_apiVersion;
    }
    
    /**
     * Setter for apiVersion
     * @param apiVersion value to set
     * @return self
     */
    public function setApiVersion($apiVersion){
        $this->_apiVersion = $apiVersion;
        return $this;
    }
    
    /**
     * Getter for apiMethod
     * @return string
     */
    public function getApiMethod(){
        return $this->_apiMethod;
    }
    
    /**
     * Setter for apiMethod
     * @param apiMethod value to set
     * @return self
     */
    public function setApiMethod($apiMethod){
        $this->_apiMethod = $apiMethod;
        return $this;
    }
    
    /**
     * Getter for pathParameter
     * @return string
     */
    public function getPathParameter(){
        return $this->_pathParameter;
    }
    
    /**
     * Setter for pathParameter
     * @param pathParameter value to set
     * @return self
     */
    public function setPathParameter($pathParameter){
        $this->_pathParameter = $pathParameter;
        return $this;
    }
    
}

?>