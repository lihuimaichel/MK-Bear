<?php 
/**
 * @desc 自定义http请求根基类
 * @author yangsh
 * @since 2017-03-15
 */
class HttpRequestImpl {
	/** @var array 请求头 **/
	public $requestHeaders = null;

	/** @var array 请求内容 **/
	public $requestData = null;

	/** @var string 请求方式(get/post/put/delete) **/
	public $httpMethod = null;

	/** @var int timeout **/
	public $timeout = null;

    /** @var string HTTP请求方式常量 **/
    const HTTP_GET    = 'get';
    const HTTP_POST   = 'post';
    const HTTP_PUT    = 'put';
    const HTTP_DELETE = 'delete';	

    /**
     * @desc 初始化对象
     */
	function __construct($requestHeaders=array(), $requestData=array(), $httpMethod='get', $timeout=1800){
		$this->requestHeaders = $requestHeaders;
		$this->requestData    = $requestData;
		$this->httpMethod     = $httpMethod;
		$this->timeout        = $timeout;
	}

    /**
     * @desc 判断是否发送json请求格式内容
     * @return boolean
     */
    public function isJsonContentType(){
        return (isset($this->requestHeaders['Content-Type'])
        	 && $this->requestHeaders['Content-Type'] == 'application/json')
              || (isset($this->requestHeaders['Content-type'])
               && $this->requestHeaders['Content-type'] == 'application/json');                     
    }

}