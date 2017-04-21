<?php
/**
 * @desc Amazon Api 抽象类
 * @author zhangf
 * @since 2015-7-7
 *
 */
abstract class AmazonApiAbstract implements PlatformApiInterface {
	
	protected $_serviceUrl = '';			//@var string Amazon请求地址
	protected $_proxyHost = null;			//@var string 使用代理服务器主机地址
	protected $_proxyPort = -1;				//@var string 代理服务器端口
	protected $_proxyUserName = null;	    //@var string 代理服务器用户名
	protected $_proxyPassword = null;		//@var string 代理服务器密码
	protected $_maxErrorRetry = 3;			//@var integer 
	
	protected $_accountID = 0;				//@var integer 账号ID
	protected $_appName = 'Amazon MWS';		//@var string 应用名称
	protected $_appVersion = '1.0';			//@var string 应用版本号
	protected $_accessKeyID = '';			//@var string Amazon MWS 访问密匙ID
	protected $_secretAccessKey = '';		//@var string Amazon MWS 访问密匙
	protected $_merchantID = '';			//@var string 商家编号
	protected $_marketPlaceID = '';			//@var string Amazon 账户市场编号
	protected $_siteUrl = '';				//@var string Amazon 账号站点url
	protected $_urlPath = '';				//@var string 请求url path 部分

	protected $request = null;				//@var string 请求内容
	protected $response = null;				//@var string 返回响应信息
	protected $_requestbody = null;			//@var string 请求报文信息
	protected $_requestEntities = null;		//@var object 请求对象实例
	protected $_serviceEntities = null;		//@var object 服务对象实例
	protected $_errorMessage = '';			//@var string 错误消息
	protected $_remainTimes = 0;			//@var string 可请求剩余次数

	// lihy add 2016-03-14
	protected $_caller = null;				//@var string 调用者名称 
	public function __construct() {
		$className = get_class($this);
		$this->_remainTimes = $className::MAX_REQUEST_TIMES;
	}	
	
	/**
	 * @desc 设置账号信息
	 * @param integer $accountID
	 * @throws Exception
	 * @return AmazonApiAbstract
	 */
	public function setAccount($accountID) {
		$this->_accountID = $accountID;
		$accountInfo = AmazonAccount::getAccountInfoById($accountID);
		if (empty($accountInfo))
			throw new Exception('Could\'t find account by ID{' . $accountID . '}');
		$this->_siteUrl = $accountInfo['service_url'];
		$this->_accessKeyID = $accountInfo['access_key'];
		$this->_secretAccessKey = $accountInfo['secret_key'];
		$this->_merchantID = $accountInfo['merchant_id'];
		$this->_marketPlaceID = $accountInfo['market_place_id'];
		$this->setServiceUrl();
		return $this;
	}
	
	/**
	 * @desc 获取请求参数
	 * @see PlatformApiInterface::getRequest()
	 */
	public function getRequest() {
		return $this->request;
	}

	/**
	 * @desc 获取响应结果
	 * @see PlatformApiInterface::getResponse()
	 */
	public function getResponse() {
		return $this->response;
	}
	
	/**
	 * @desc 设置请求url地址
	 */
	public function setServiceUrl() {
		$this->_serviceUrl = trim($this->_siteUrl, '/') . $this->_urlPath;
	}
	
	/**
	 * 设置请求
	 * @see PlatformApiInterface::setRequest()
	 */
	public function setRequest() {
		$this->setServiceEntities();
		$this->setRequestEntities();
		//$this->_requestbody = $this->_requestEntities->toQueryParameterArray();
		return $this;
	}	
	
	/**
	 * @desc 发送请求
	 * @see PlatformApiInterface::sendRequest()
	 */
	public function sendRequest() {
		if (!$this->call())
			$this->writeErrorLog();
		return $this;
	}
	
	/**
	 * @desc 抽象方法，设置请求服务实例
	 */
	abstract protected function setServiceEntities();
	
	/**
	 * @desc 抽象方法，设置请求对象实例
	 */
	abstract protected function setRequestEntities();
	
	/**
	 * @desc 抽象方法，调用接口方法
	 */
	abstract function call();
	
	/**
	 * @desc 记录日志
	 */
	public function writeErrorLog() {
		$logPath = Yii::getPathOfAlias('webroot').'/log/amazon/'.date('Y').'/'.date('m').'/'.date('d').'/'.date('H');
		if( !is_dir($logPath) ){
			mkdir($logPath, 0777, true);
		}
		$log = date('Y-m-d H:i:s').'           '.$this->_serviceUrl."\n";//时间，接口名
		$log .= "Request Parameters \n" . var_export($this->_requestbody, true) . "\n";//交互报文
		$log .= 'Error Message:'.$this->getErrorMsg()."\n\n";//错误信息
		$fileName = $this->_accountID.'-' . __CLASS__ . '.txt';
		file_put_contents($logPath.'/'.$fileName, $log, FILE_APPEND);		
	}
	
	/**
	 * @desc 获取失败信息
	 * @return string
	 */
	public function getErrorMsg(){
		return $this->_errorMessage;
	}
	
	protected function requestAble() {
		return $this->_remainTimes > 0 ? true : false;
	}
	
	/**
	 * @desc 判断交互是否成功
	 */
	public function getIfSuccess(){
		return empty($this->_errorMessage) ? true : false;
	}
	
	/**
	 * @desc 设置调用者，不是API名称，是应用用途
	 * @param unknown $caller
	 * @return AmazonApiAbstract
	 */
	public function setCaller($caller){
		$this->_caller = $caller;
		return $this;
	}
}