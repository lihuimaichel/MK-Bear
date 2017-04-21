<?php
/**
 * @package Api.models
 * @author Gordon
 * @since 2015-01-05
 */
class ApiModel extends UebModel {
	
	public $client 	= '';//请求端
	public $key 	= '';//请求验证key
	public $method 	= '';//请求接口
	public $_error 	= 0;
	public $_requestResult = 0;
	public $_attribute = array();
	private $_config = array(
		'oms' => 'vakind_omsClient2016',
	);
	
	/**
	 * 初始化验证信息
	 */
	public function initApiParam($attribute){
		$this->_attribute = $attribute;
		$this->clientConfig();
		$this->keyConfig();
		if( isset($this->_attribute->method) ){
			$this->method = $this->_attribute->method;
		}
	}
	
	public function run(){
		$callResult = $this->_call();
		if( isset($callResult['errorCode']) ){
			if($callResult['errorCode']=='0'){
				$this->_requestResult = 1;
				$this->_error = 200;//成功返回的code
			}else{
				$this->_requestResult = 0;
				$this->_error = $callResult['errorCode'];//失败返回的code
			}
		}else{
			$this->_requestResult = 1;
		}
		return $this->_buildReturnData($callResult);
	}
	
	/**
	 * 运行查询逻辑
	 */
	private function _call(){
		$model = new ApiAdapter();
		return $model->_call($this->method, $this->_attribute);
	}
	
	/**
	 * 请求客户端配置
	 * @return boolean
	 */
	public function clientConfig(){
		$config = array_keys($this->_config);
		if( isset($this->_attribute->client) ){
			if( in_array($this->_attribute->client, $config) ){				
				$this->client = $this->_attribute->client;
				return true;
			}
		}
		return false;
	}
	
	/**
	 * key值配置
	 * @return boolean
	 */
	public function keyConfig(){
		$config = $this->_config;
		if( isset($this->_attribute->key) ){
			if( $this->_attribute->key==$config[$this->client] ){
				$this->key = $this->_attribute->key;
				return true;
			}
		}
		return false;
	}
	
	/**
	 * 记录交互日志
	 */
	public function connectLog(){
		//TODO
	}
	
	/**
	 * 错误信息配置
	 */
	public function errorCodeConfig(){
		$config = array(
				1 		=> '请求异常',//common
				2 		=> '请求验证失败',//common
				3 		=> '请求方法不存在',//common
				200 	=> 'Success',//common
		);
		return isset($config[$this->_error]) ? $config[$this->_error] : false;
	}
	
	/**
	 * 组建返回信息
	 */
	public function _buildReturnData($data = array()){
		$returnArr = array(
				'Ack' 		=> $this->_requestResult==0 ? 'FAILURE' : 'SUCCESS',
				'CallName'	=> $this->method,
				'Time'		=> date('Y-m-d H:i:s'),
		);
		if( $this->_requestResult==0 ){
			$errMsg = $this->errorCodeConfig();
			if ($errMsg === false) {//取自定义message
				$errMsg = isset($data['errorMsg']) ? $data['errorMsg'] : 'unknown error';
			}
			$returnArr['ErrorMsg'] = $errMsg;
		}
		if( !empty($data) ){
			$returnArr['Data'] = isset($data['data']) ? $data['data'] : null;
		}	
		return $returnArr;
	}
	
	/**
	 * 验证交互
	 */
	public function authenticate(){
		if( $this->client && $this->key && $this->method ){
			return true;
		}else{
			if( !$this->client || !$this->key ){
				$this->_error = 2;//失败代码
				$this->_requestResult = 0;//请求失败
			}else{
				$this->_error = 3;//失败代码
				$this->_requestResult = 0;//请求失败
			}
		}
	}
	
	public function getDbKey() {
		return 'db_ebay';
	}
	
	public function tableName() {
		return 'ueb_ebay_product';
	}
	
}