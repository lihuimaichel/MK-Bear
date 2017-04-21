<?php
/**
 * @desc Priceminister Api 抽象类
 * @author lihy
 * @since 2016-7-1
 *
 */
abstract class PriceministerApiAbstract implements PlatformApiInterface {
	//example：https://ws.priceminister.com/listing_ssl_ws?action=listing&login=xxxxx&pwd=xxxxx&version=xxxx-xx-xx&scope=xxxxx&nbproducts=xxxxx&kw=xxxxx&nav=xxxxx&refs=xxxxx&productids=xxxxx
	//example：https://ws.priceminister.com/sales_ws?action=getnewsales&login=xxxxxx&pwd=xxxxxx&version=xxxx-xx-xx
	//example：https://ws.priceminister.com/sales_ws?action=settrackingpackageinfos&login=xxxxxx&pwd=xxxxxx&version=xxxx-xx-xx&itemid=xxx&transporter_name=xxx&tracking_number=xxx&tracking_url=xxx
	protected $_serviceUrl = 'https://ws.priceminister.com/';			//@var string PM请求地址
	protected $_accountID = 0;				//@var integer 账号ID
	protected $_appVersion = '1.0';			//@var string 应用版本号
	protected $_accessKeyID = '';			//@var string 访问ID
	protected $_secretAccessKey = '';		//@var string 访问密匙

	protected $_siteUrl = 'https://ws.priceminister.com/';				//@var string 账号站点url
	//以下三个需要指定
	protected $_urlPath = 'sales_ws';		//@var string 请求的path部分 sales_ws、listing_ssl_ws
	protected $_action = '';				//@var string 请求action部分
	protected $_version = '';				//@var 版本部分
	

	protected $_isPost = FALSE;				/** @var boolean 是否为Post交互*/
	protected $request = null;				//@var string 	请求内容
	protected $response = null;				//@var string 	返回响应信息

	protected $_errorMessage = '';			//@var string 	错误消息
	protected $_timeout = 60;				//@var integer	超时时间
	public function __construct() {
		
	}	
	
	/**
	 * @desc 设置账号信息
	 * @param integer $accountID
	 * @throws Exception
	 * @return AmazonApiAbstract
	 */
	public function setAccount($accountID) {
		$this->_accountID = $accountID;
		$accountInfo = PriceministerAccount::getAccountInfoById($accountID);
		if (empty($accountInfo))
			throw new Exception('Could\'t find account by ID{' . $accountID . '}');
		$this->_siteUrl = $accountInfo['service_url'];
		$this->_accessKeyID = $accountInfo['user_name'];
		$this->_secretAccessKey = $accountInfo['user_secret'];
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
	 * 设置请求
	 * @see PlatformApiInterface::setRequest()
	 */
	//public abstract function setRequest();
	
	/**
	 * @desc 发送请求
	 * @see PlatformApiInterface::sendRequest()
	 */
	public function sendRequest() {
		try {
			
			$this->setServiceUrl();
			if(isset($_REQUEST['bug'])){
				echo "<br/>=================<br/>";
				echo $this->_serviceUrl;
				echo "<br/>=================<br/>";
				var_dump($this->getRequest());
			}
			if($this->_isPost){
				$response = Yii::app()->curl
					->setOption(CURLOPT_TIMEOUT, $this->_timeout)
					->addCertificate()->postFile($this->_serviceUrl, $this->getRequest());//添加证书
			}else {
				$response = Yii::app()->curl
					->setOption(CURLOPT_TIMEOUT, $this->_timeout)
					->addCertificate()->get($this->_serviceUrl, $this->getRequest());
			}
			//处理数据
			$response = simplexml_load_string($response);
			$this->response = $response;
		 } catch (Exception $e ) {
            //$this->writeErrorLog();
            echo $this->_errorMessage = $e->getMessage();
            
        }
        return $this;
	}
	
	/**
	 * @desc 设置请求url地址
	 */
	public function setServiceUrl(){
		$this->_serviceUrl = trim($this->_siteUrl, '/') . "/" .$this->_urlPath . "?";
		$request = array(
				'action'	=>	$this->_action,
				'version'	=>	$this->_version,
				'login'		=>	$this->_accessKeyID,
				'pwd'		=>	$this->_secretAccessKey,
		);
		if($this->_isPost){
			//$this->_serviceUrl = trim($this->_siteUrl, '/') . "/" .$this->_urlPath . "?action=".$this->_action."&version=".$this->_version."&login=".$this->_accessKeyID."&pwd=".$this->_secretAccessKey;
			$this->_serviceUrl .= http_build_query($request);
		}else {
			$this->request = array_merge($request, $this->request);
			//$this->_serviceUrl .= http_build_query($this->request);
		}
	}

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

	
	/**
	 * @desc 判断交互是否成功
	 */
	public function getIfSuccess(){
		if(isset($this->response->error)){
			$this->_errorMessage = $this->response->error->message;
			return false;
		}
		return true;
	}
}