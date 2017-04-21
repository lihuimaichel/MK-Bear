<?php
/**
 * 顺友---配置类
 * @author ltf
 * @since 2016/08/08
 */

abstract class SyService{
	const SANDBOX = FALSE;//是否为沙盒环境
	
	private $_url 		= '';
	private $xml    			= null;
	
	public function __construct(){
		
		if(self::SANDBOX){//沙盒环境
			$this->_url 			= "http://api.sandbox.sunyou.hk/logistics/";//获取配置接口地址：正式
		}else{//真实环境
			$this->_url 			= "http://a2.sunyou.hk/logistics/";//获取配置接口地址：正式
		}
		$this->xml = new XmlWriter();
	}

	/**
	 * curl post操作
	 * @param post数据  $post_data
	 * @param 进行的操作 $action
	 * @return object
	 */
// 	protected function curl_config_post($post_data,$action){
// 		$headers = $this->get_config_header();
// 		/* $action_url = $this->get_action_url($action); */
// 		$ch = curl_init();
// 		curl_setopt($ch, CURLOPT_URL,$this->_url);
// 		curl_setopt($ch, CURLOPT_POST, 1);
// 		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
// 		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
//  		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
// 		$data = curl_exec($ch);
// 		curl_close($ch);
// 		return $data;
// 	}
	
// 	protected function get_config_header(){
// 		$header = array(
// 				"Content-Type: application/json",
// // 			"Authorization: basic ".base64_encode($this->_num.'&'.$this->_secret),
// 				"Accept-Language: zh-cn",
// 				"Accept: text/json",
// 		);
// 		return $header;
// 	}
	
	/**
	 * curl post操作
	 * @param post数据  $post_data
	 * @param 进行的操作 $action
	 * @return object
	 */
	protected function curl_post($post_data,$action){
		$headers = $this->post_header();
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$this->_url.$action);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
		
		/* $response_header = curl_exec($ch);
		$meta = curl_getinfo($ch);
		$request_header = $meta['request_header'];
		var_dump($request_header);
		var_dump($response_header); */
		
		$data = curl_exec($ch);
		curl_close($ch);
		return $data;
	}
	
	protected function post_header(){
		$header = array(
				"Content-Type: application/json",
				"Authorization: basic",
				"Accept-Language: zh-cn",
				"Accept: text/json",
		);
		return $header;
	}
	
	/**
	 * 推送包裹信息
	 * @param unknown $post_data
	 * @param unknown $action
	 * @return mixed
	 */
// 	protected function curl_post_upload($post_data){
// 		$postDataXML	= $this->getPostDataXML($post_data);
// 		$ch = curl_init();
// 		curl_setopt($ch, CURLOPT_URL,$this->_url);
// 		curl_setopt($ch, CURLOPT_POST, 1);
// 		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
// 		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
// 		//curl_setopt($ch, CURLOPT_HEADER, $headers);
// 		curl_setopt($ch, CURLOPT_POSTFIELDS, $postDataXML);
		
// 		/* $response_header = curl_exec($ch);
// 		$meta = curl_getinfo($ch);
// 		$request_header = $meta['request_header'];
// 		var_dump($request_header);
// 		var_dump($response_header); */
		
// 		$data = curl_exec($ch);
// 		curl_close($ch);
// 		return $data;
// 	}
	
	/**
	 * 转化数组为XML字符串,并加密验证功能
	 * @param array $data
	 * @return xml
	 */
// 	protected function getPostDataXML($data) {
// 		$xml = $this->arrtoxml($data);
// 		$varsion = '2.0';//上传包裹的版本号写成 2.0
// 		$dataArr		= array(
// 				"logistics_interface" 	=> $xml,
// 				'data_digest'	 		=> urlencode(base64_encode(md5($xml.$this->_secret,true))),
// 				//'msg_type'		 	=> $this->_msgType,
// 				//'ecCompanyId'	 		=> $this->_ecCompanyId,
// 				'version'		 		=> $varsion
// 		);
// 		$postStr = '';
// 		foreach ($dataArr as $key => $val){
// 			$postStr .= $key.'='.$val.'&';
// 		}
// 		$postStr = substr($postStr,0,-1);
// 		return $postStr;
// 	}
	
	/**
	 * 将数组转化为xml
	 * @param array $data
	 * @return xml
	 */
// 	function arrtoxml($data, $eIsArray=FALSE){
// 		if(!$eIsArray) {
// 	      $this->xml->openMemory();
// 	      $this->xml->startDocument("1.0","UTF-8");
// 	      $this->xml->startElement("");
// 	    }
// 	    foreach($data as $key => $value){
// 	      if(is_array($value)){
// 	        $this->xml->startElement($key);
// 	        $this->arrtoxml($value, TRUE);
// 	        $this->xml->endElement();
// 	        continue;
// 	      }
// 	      $this->xml->writeElement($key, $value);
// 	    }
// 	    if(!$eIsArray) {
// 	      $this->xml->endElement();
// 	      return $this->xml->outputMemory(true);
// 	    }
// 	}
	
	/**
	 * curl get 操作
	 * @param 进行的操作 $action
	 * @return object
	 */
	protected function curl_get($action,$get_data=array()){
		$headers = $this->get_curl_header();
		$action_url = $this->get_action_url($action,$get_data);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$this->_url.$action_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$data = curl_exec($ch);
		curl_close($ch);
		return $data;
	}
	
}