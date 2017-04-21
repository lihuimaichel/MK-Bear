<?php 
/**
 * @desc 自定义http响应根基类
 * @author yangsh
 * @since 2017-03-15
 */
class HttpResponseImpl {
	/** @var string http状态码 **/
	public $statusCode = null;

	/** @var string http error **/
	public $error = null;

	/** @var string http errorNo **/
	public $errorNo = null;

	/** @var array 响应头信息 **/
	public $responseHeaders = null;

	/** @var object 响应内容 **/
	public $responseData = null;

    /**
     * @desc 初始化对象
     */
	function __construct($responseHeaders, $responseData, $errorNo, $error){
		$this->responseHeaders = $responseHeaders;
		$this->responseData    = $responseData;
		$this->errorNo         = $errorNo;
		$this->error           = $error;
		$this->statusCode      = isset($responseHeaders['http_code']) ? $responseHeaders['http_code'] : null;
	}
	
}