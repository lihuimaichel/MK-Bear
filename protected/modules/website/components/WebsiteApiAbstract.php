<?php
/**
 * @desc 网站平台API抽象类
 * @author zhangF
 *
 */
abstract class WebsiteApiAbstract implements PlatformApiInterface {
	protected $_soapClient = null;		//soap client对象
	protected $_endpoint = '';			//接口地址
	protected static $_sessionID = array();			//和服务端的会话ID
	protected $_apiUsername = '';		//API用户名
	protected $_apiKey = '';			//API key值
	protected $_accountID = '';			//账号ID
	protected $request = null;				//@var string 请求内容
	protected $response = null;				//@var string 返回响应信息
	protected $_requestbody = null;			//@var string 请求报文信息	
	protected $_errors = '';		//错误信息
	
	/**
	 * @desc 设置账号
	 * @param unknown $accountID
	 * @throws Exception
	 * @return WebsiteApiAbstract
	 */
	public function setAccount($accountID) {
		$this->_accountID = $accountID;
		$accountInfo = WebsiteAccount::getAccountInfoById($accountID);
		if (empty($accountInfo))
			throw new Exception('Could\'t find account by ID{' . $accountID . '}');
		$this->_endpoint = $accountInfo['service_url'];
		$this->_apiUsername = $accountInfo['api_username'];
		$this->_apiKey = $accountInfo['api_key'];
		return $this;
	}
	
	/**
	 * 获取认证sessionID
	 * @return string:
	 */
	public function getApiSessionID() {
		return $this->_sessionID;
	}
	
	/**
	 * @desc 获取错误信息
	 * @return string
	 */
	public function getErrorMsg() {
		return $this->_errors;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see PlatformApiInterface::setRequest()
	 */
	public function setRequest() {
		try {
			$this->_soapClient = new SoapClient($this->_endpoint, array(
				'soap_version' => SOAP_1_1,
				'trace' => 1
			));
			//每个账号只需要认证一次
			if (!isset(self::$_sessionID[$this->_accountID]) || empty($this->_sessionID[$this->_accountID])) {
				$sessionID = $this->_soapClient->__call('login', array($this->_apiUsername, $this->_apiKey));
				self::$_sessionID[$this->_accountID] = $sessionID;
			}
		} catch (Exception $e) {
			$this->_errors = $e->getMessage();
		}
		return $this;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see PlatformApiInterface::getRequest()
	 */
	public function getRequest() {
		return $this->request;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see PlatformApiInterface::getResponse()
	 */
	public function getResponse() {
		return $this->response;
	}
	
	/**
	 * @desc 判断交互是否成功
	 */
	public function getIfSuccess(){
		return empty($this->_errors) ? true : false;
	}	
	
	/**
	 * @desc 记录文件错误日志
	 */
	public function writeErrorLog(){
		$logPath = Yii::getPathOfAlias('webroot').'/log/website/'.date('Y').'/'.date('m').'/'.date('d').'/'.date('H');
		if( !is_dir($logPath) ){
			mkdir($logPath, 0777, true);
		}
		$log = date('Y-m-d H:i:s').'           ' . $this->_endpoint ."\n";//时间，接口名
		$log .= $this->_requestbody."\n";//交互报文
		$log .= 'Error Message:' . $this->getErrorMsg() . "\n\n";//错误信息
		$fileName = $this->_accountID.'-' . __CLASS__ . '.txt';
		file_put_contents($logPath.'/'.$fileName, $log, FILE_APPEND);
	}	
}