<?php
/**
 * @desc Ebay API Abstract
 * @author Gordon
 * @since 2015-06-02
 */
abstract class PaypalApiAbstract implements PlatformApiInterface {
	
	/**@var string 提交节点*/
	protected $_Endpoint = null;
	
	/**@var string 请求版本号*/
	protected $_Version = null;
	
	/**@var string 请求头*/
	protected $_Nvp_Header = null;
	
	/**@var string 用户名*/
	protected $_Api_Username = null;
	
	/**@var string 用户密码*/
	protected $_Api_Password = null;
	
	/**@var string 用户API签名*/
	protected $_Api_Signature = null;
	
	/**@var string APP ID*/
	protected $_App_Id = null;
	
	/**@var string 调用方法名*/
	protected $_Method = null;

	
	/**
	 * 设置账号信息
	 * 
	 * @param int $accountID        	
	 */
	public function setAccount($accountID) {
		$accountInfo = PaypalAccount::model ()->getPaypalInfoById ( $accountID );
		$paypalKeys = ConfigFactory::getConfig ( 'paypalKeys' );
		//var_dump($paypalKeys);exit;
		if ($accountInfo) {
			$this->_Endpoint = $paypalKeys ['serverUrl'];
			$this->_Version = $paypalKeys ['version'];
			$this->_Api_Username = $accountInfo ['api_user_name'];
			$this->_Api_Password = $accountInfo ['api_password'];
			$this->_Api_Signature = $accountInfo ['api_signature'];
			$this->_App_Id = $accountInfo ['app_id'];
		}
		return $this;
	}
	
	/**
	 * 头信息
	 */
	public function getHeader() {
		$nvpHeaderStr = "&PWD=" . urlencode ( $this->_Api_Password ) . "&USER=" . urlencode ( $this->_Api_Username ) . "&SIGNATURE=" . urlencode ( $this->_Api_Signature );
		return $nvpHeaderStr;
	}
	
	/**
	 * 设置调用接口名
	 */
	public function setMethod($method = '') {
		$this->_Method = $method;
		return $this;
	}
	
	/**
	 * 发送请求,获取响应结果
	 */
	public function sendRequest() {
		try {
			$this->setMethod (); // 设置接口
			$ch = curl_init ();
			curl_setopt ( $ch, CURLOPT_URL, $this->_Endpoint );
			curl_setopt ( $ch, CURLOPT_VERBOSE, 0 );
			curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
			curl_setopt ( $ch, CURLOPT_SSL_VERIFYHOST, FALSE );
			curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
			curl_setopt ( $ch, CURLOPT_POST, 1 );
			curl_setopt ( $ch, CURLOPT_CONNECTTIMEOUT, 120 );
			curl_setopt ( $ch, CURLOPT_POSTFIELDS, 'METHOD=' . urlencode ( $this->_Method ) . '&VERSION=' . urlencode ( $this->_Version ) . $this->getHeader () . $this->getRequest () );
			//$request = curl_getinfo($ch);
			//var_dump($request);exit();		
			$response = curl_exec ( $ch );
			//print_r($response);			
			curl_close ( $ch );
			$this->response = $this->deformatNVP ( $response );		
		} catch ( Exception $e ) {
			// TODO API log 日志
		}
		
		return $this;
	}
	
	/**
	 * 发送请求,获取响应结果
	 */
	public function sendRequestAda() {
				//echo("sendRequest TransactionID<br/>");
			//echo($this->_TransactionID);exit;
			// 抓取一条订单

		loadPaypalAccount($this->_Api_Username, $this->_Api_Password, $this->_Api_Signature, $this->_App_Id);

			$dnvpStr = "&TRANSACTIONID=$this->_TransactionID";
			$resArr = hash_call ( "gettransactionDetails", $dnvpStr );
	
			$this->response = $this->deformatNVP ( $resArr );
			$dack = strtoupper ( $resArr ["ACK"] );
			if ($dack != "SUCCESS" && $dack != "SUCCESSWITHWARNING") {
				$this->errormessage = $resArr ['L_LONGMESSAGE0'];
				return false;
			} else {
				return $resArr;
			}
	}
	
	/**
	 * 将返回信息转化为数组
	 * 
	 * @param string $response        	
	 */
	public function deformatNVP($response) {
		$intial = 0;
		$nvpArray = array ();
		while ( strlen ( $response ) ) {
			$keypos = strpos ( $response, '=' );
			$valuepos = strpos ( $response, '&' ) ? strpos ( $response, '&' ) : strlen ( $response );
			
			$keyval = substr ( $response, $intial, $keypos );
			$valval = substr ( $response, $keypos + 1, $valuepos - $keypos - 1 );
			$nvpArray [urldecode ( $keyval )] = urldecode ( $valval );
			$response = substr ( $response, $valuepos + 1, strlen ( $response ) );
		}
		return $nvpArray;
	}
	
	/**
	 * 获取请求参数
	 * 
	 * @see ApiInterface::getRequest()
	 */
	public function getRequest() {
		return $this->request;
	}
	
	/**
	 * 获取响应结果
	 * 
	 * @see ApiInterface::getResponse()
	 */
	public function getResponse() {
		return $this->response;
	}
	
	/**
	 * 判断交互是否成功
	 */
	public function getIfSuccess() {
		if (isset ( $this->response ['ACK'] ) && (strtoupper ( $this->response ['ACK'] ) == 'SUCCESS' || strtoupper ( $this->response ['ACK'] ) == 'SUCCESSWITHWARNING')) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * 获取失败信息
	 * 
	 * @return string
	 */
	public function getErrorMsg() {
		$errorMessage = '';
		if (isset ( $this->response ['L_LONGMESSAGE0'] )) {
			$errorMessage .= $this->response ['L_LONGMESSAGE0'];
		}
		return $errorMessage;
	}
}

?>