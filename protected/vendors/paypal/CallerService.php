<?php
/******************************************************************************************
 * 程序说明: 连接paypal api,请求数据和响应数据的主要文件.
 * 程序开发: roc
 * 修改日期: 2011-08-26
 ******************************************************************************************/

initPaypal();

/* 初始化paypal参数 */
function initPaypal(){
	global $API_Endpoint,$version,$API_UserName,$API_Password,$API_Signature,$nvp_Header, $subject, $AUTH_token,$AUTH_signature,$AUTH_timestamp;
	$production  = TRUE;   
	
	if ($production) {
		$API_Endpoint = 'https://api-3t.paypal.com/nvp';
		define('PAYPAL_URL', 'https://www.paypal.com/webscr&cmd=_express-checkout&token=');
	} else {
		$API_Endpoint = 'https://api-3t.sandbox.paypal.com/nvp';
		define('PAYPAL_URL', 'https://www.sandbox.paypal.com/webscr&cmd=_express-checkout&token=');
	}
	
	/**
	# Version: this is the API version in the request.
	# It is a mandatory parameter for each API request.
	# The only supported value at this time is 2.3
	*/
	$version = '65.1';
	/*
	 # Third party Email address that you granted permission to make api call.
	 */
	$subject = '';
	
	// below three are needed if used permissioning
	$AUTH_token= '';
	
	$AUTH_signature = '';
	
	$AUTH_timestamp = '';
	
	/**
	USE_PROXY: Set this variable to TRUE to route all the API requests through proxy.
	like define('USE_PROXY',TRUE);
	*/
	define('USE_PROXY',FALSE);
	/**
	PROXY_HOST: Set the host name or the IP address of proxy server.
	PROXY_PORT: Set proxy port.
	
	PROXY_HOST and PROXY_PORT will be read only if USE_PROXY is set to TRUE
	*/
	define('PROXY_HOST', '127.0.0.1');
	define('PROXY_PORT', '808');
	/**
	# Version: this is the API version in the request.
	# It is a mandatory parameter for each API request.
	# The only supported value at this time is 2.3
	*/
	define('VERSION', '65.1');
	// Ack related constants
	define('ACK_SUCCESS', 'SUCCESS');
	define('ACK_SUCCESS_WITH_WARNING', 'SUCCESSWITHWARNING');
}

function nvpHeader()
{
	global $API_Endpoint,$version,$API_UserName,$API_Password,$API_Signature,$nvp_Header, $subject, $AUTH_token,$AUTH_signature,$AUTH_timestamp;
	$nvpHeaderStr = "";
	
	if(defined('AUTH_MODE')) {
		//$AuthMode = "3TOKEN"; //Merchant's API 3-TOKEN Credential is required to make API Call.
		//$AuthMode = "FIRSTPARTY"; //Only merchant Email is required to make EC Calls.
		//$AuthMode = "THIRDPARTY";Partner's API Credential and Merchant Email as Subject are required.
		$AuthMode = "AUTH_MODE"; 
	} 
	else {
		
		if((!empty($API_UserName)) && (!empty($API_Password)) && (!empty($API_Signature)) && (!empty($subject))) {
			$AuthMode = "THIRDPARTY";
		}
		
		else if((!empty($API_UserName)) && (!empty($API_Password)) && (!empty($API_Signature))) {
			$AuthMode = "3TOKEN";
		}
		
		elseif (!empty($AUTH_token) && !empty($AUTH_signature) && !empty($AUTH_timestamp)) {
			$AuthMode = "PERMISSION";
		}
	    elseif(!empty($subject)) {
			$AuthMode = "FIRSTPARTY";
		}
	}
	switch($AuthMode) {
		
		case "3TOKEN" : 
				$nvpHeaderStr = "&PWD=".urlencode($API_Password)."&USER=".urlencode($API_UserName)."&SIGNATURE=".urlencode($API_Signature);
				break;
		case "FIRSTPARTY" :
				$nvpHeaderStr = "&SUBJECT=".urlencode($subject);
				break;
		case "THIRDPARTY" :
				$nvpHeaderStr = "&PWD=".urlencode($API_Password)."&USER=".urlencode($API_UserName)."&SIGNATURE=".urlencode($API_Signature)."&SUBJECT=".urlencode($subject);
				break;		
		case "PERMISSION" :
			    $nvpHeaderStr = formAutorization($AUTH_token,$AUTH_signature,$AUTH_timestamp);
			    break;
	}
	return $nvpHeaderStr;
}

/**
  * hash_call: Function to perform the API call to PayPal using API signature
  * @methodName is name of API  method.
  * @nvpStr is nvp string.
  * returns an associtive array containing the response from the server.
*/


function hash_call($methodName,$nvpStr)
{
	//declaring of global variables
	global $API_Endpoint,$version,$API_UserName,$API_Password,$API_Signature,$nvp_Header, $subject, $AUTH_token,$AUTH_signature,$AUTH_timestamp;
	// form header string
	$nvpheader=nvpHeader();
	//setting the curl parameters.
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,$API_Endpoint);
	curl_setopt($ch, CURLOPT_VERBOSE, 0);

	//turning off the server and peer verification(TrustManager Concept).
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
	curl_setopt($ch, CURLOPT_POST, 1);
	
	//set connection timeout as 120 second
	curl_setopt($connection, CURLOPT_CONNECTTIMEOUT, 120);
	
	//in case of permission APIs send headers as HTTPheders
	if(!empty($AUTH_token) && !empty($AUTH_signature) && !empty($AUTH_timestamp))
	 {
		$headers_array[] = "X-PP-AUTHORIZATION: ".$nvpheader;
  
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers_array);
    curl_setopt($ch, CURLOPT_HEADER, false);
	}
	else 
	{
		$nvpStr=$nvpheader.$nvpStr;
	}
    //if USE_PROXY constant set to TRUE in Constants.php, then only proxy will be enabled.
   //Set proxy name to PROXY_HOST and port number to PROXY_PORT in constants.php 
	if(USE_PROXY)
	curl_setopt ($ch, CURLOPT_PROXY, PROXY_HOST.":".PROXY_PORT); 

	//check if version is included in $nvpStr else include the version.
	if(strlen(str_replace('VERSION=', '', strtoupper($nvpStr))) == strlen($nvpStr)) {
		$nvpStr = "&VERSION=" . urlencode($version) . $nvpStr;	
	}
	
	$nvpreq="METHOD=".urlencode($methodName).$nvpStr;

	//setting the nvpreq as POST FIELD to curl
	curl_setopt($ch,CURLOPT_POSTFIELDS,$nvpreq);

//echo("CallerService");exit;
	//getting response from server
	//var_dump($ch);exit;
	$response = curl_exec($ch);
//var_dump($response);exit;
	//convrting NVPResponse to an Associative Array
	$nvpResArray=deformatNVP($response);
	$nvpReqArray=deformatNVP($nvpreq);
	$_SESSION['nvpReqArray']=$nvpReqArray;

	if (curl_errno($ch)) {
		// moving to display page to display curl errors
		  $_SESSION['curl_error_no']=curl_errno($ch) ;
		  $_SESSION['curl_error_msg']=curl_error($ch);
		  $location = "APIError.php";
//		  header("Location: $location");
	 } else {
		 //closing the curl
			curl_close($ch);
	  }

return $nvpResArray;
}

/** This function will take NVPString and convert it to an Associative Array and it will decode the response.
  * It is usefull to search for a particular key and displaying arrays.
  * @nvpstr is NVPString.
  * @nvpArray is Associative Array.
  */

function deformatNVP($nvpstr)
{

	$intial=0;
 	$nvpArray = array();


	while(strlen($nvpstr)){
		//postion of Key
		$keypos= strpos($nvpstr,'=');
		//position of value
		$valuepos = strpos($nvpstr,'&') ? strpos($nvpstr,'&'): strlen($nvpstr);

		/*getting the Key and Value values and storing in a Associative Array*/
		$keyval=substr($nvpstr,$intial,$keypos);
		$valval=substr($nvpstr,$keypos+1,$valuepos-$keypos-1);
		//decoding the respose
		$nvpArray[urldecode($keyval)] =urldecode( $valval);
		$nvpstr=substr($nvpstr,$valuepos+1,strlen($nvpstr));
     }
	return $nvpArray;
}
function formAutorization($auth_token,$auth_signature,$auth_timestamp)
{
	$authString="token=".$auth_token.",signature=".$auth_signature.",timestamp=".$auth_timestamp ;
	return $authString;
}

//加载指定paypal账户
function loadPaypalAccount($api_username,$api_password,$api_signature,$api_appid=''){
	global $API_UserName,$API_Password,$API_Signature,$API_Appid;	
	$API_UserName = $api_username;
	$API_Password = $api_password;
	$API_Signature = $api_signature;
	$API_Appid = $api_appid;
}
?>
