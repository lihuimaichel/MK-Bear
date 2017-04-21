<?php
/**
 * @desc Ebay Rest API Abstract
 * @author yangsh
 * @since 2017-03-11
 */
abstract class EbayRestfulApiAbstract implements PlatformApiInterface {
    
    /**@var int 账号ID*/
    protected $accountID = null;

    /**@var string eBay Redirect URL Name*/
    protected $_ruName = null;

    /**@var string access token*/
    protected $_accessToken = null;

    /**@var string refresh token*/
    protected $_refreshToken = null;

    /**@var string 开发者账号ID(与开发者账号绑定)*/
    protected $_devID = null;
    
    /**@var string Client ID(与开发者账号绑定)*/
    protected $_clientID = null;
    
    /**@var string Client 密钥(与开发者账号绑定)*/
    protected $_clientSecret = null;

    /**@var string Marketplace ID 如：EBAY-US.MOTORS */
    protected $_marketplaceID = null;

    /** @var array ebayRestfulKeys **/
    public $ebayRestfulKeys = null;    

    /**@var string 响应编码*/
    protected $_errorCode = null;

    /**@var string 错误提示信息*/
    protected $_errorMsg = null;

    /** @var object EbayRestfulUrl对象*/
    protected $_urlObject = null;

    /**@var object HttpRequest对象*/
    protected $_requestObject = null;

    /**@var object HttpResponse对象*/
    protected $_responseObject = null;    

    /** all ebay scope list **/
    public static $ALL_EBAY_SCOPE_LIST = array(
        'api_scope'                 =>'https://api.ebay.com/oauth/api_scope',
        'sell_marketing_readonly'   =>'https://api.ebay.com/oauth/api_scope/sell.marketing.readonly',
        'sell_marketing'            =>'https://api.ebay.com/oauth/api_scope/sell.marketing',
        'sell_inventory_readonly'   =>'https://api.ebay.com/oauth/api_scope/sell.inventory.readonly',
        'sell_inventory'            =>'https://api.ebay.com/oauth/api_scope/sell.inventory',
        'sell_account_readonly'     =>'https://api.ebay.com/oauth/api_scope/sell.account.readonly',
        'sell_account'              =>'https://api.ebay.com/oauth/api_scope/sell.account',
        'sell_fulfillment_readonly' =>'https://api.ebay.com/oauth/api_scope/sell.fulfillment.readonly',
        'sell_fulfillment'          =>'https://api.ebay.com/oauth/api_scope/sell.fulfillment',
        'sell_analytics_readonly'   =>'https://api.ebay.com/oauth/api_scope/sell.analytics.readonly',
        //'buy_order_readonly'        =>'https://api.ebay.com/oauth/api_scope/buy.order.readonly',
        //'buy_guest_order'           =>'https://api.ebay.com/oauth/api_scope/buy.guest.order',        
    );

    /**
     * @desc 初始化对象
     */    
    public function __construct(){
        $ebayRestfulKeys = ConfigFactory::getConfig('ebayRestfulKeys');
        if(empty($ebayRestfulKeys)) {
            throw new Exception("ebayRestfulKeys Not Exist");
        }
        $this->ebayRestfulKeys = $ebayRestfulKeys;
    }

    /**
     * @desc 设置账号信息
     * @param int $accountID
     */
    public function setAccount($accountID){
        $accountInfo = EbayAccountRestful::model()->getByAccountID($accountID);//获取账号相关信息
        if(empty($accountInfo)) {
            throw new Exception("Account Not Exist");
        }
        $this->accountID     = $accountID;
        $this->_ruName       = $accountInfo['ru_name'];   
        $this->_accessToken  = $accountInfo['access_token'];
        $this->_refreshToken = $accountInfo['refresh_token'];
        $this->_devID        = $accountInfo['dev_id'];
        $this->_clientID     = $accountInfo['client_id'];
        $this->_clientSecret = $accountInfo['client_secret'];

        return $this;
    }

    public function getMarketplaceID(){
        return $this->_marketplaceID;
    }

    /**
     * @desc 设置Marketplace ID
     * @param string $marketplaceID
     */
    public function setMarketplaceID($marketplaceID){
        $this->_marketplaceID = $marketplaceID;
        return $this;
    }
    
    /**
     * @desc 设置http头信息
     */
    public function setHeaders(){
        $requestHeaders = array(
            'Accept'         => 'application/json',
            'Accept-Charset' => 'utf-8',
            'Content-Type'   => 'application/json',
            'Authorization'  => 'Bearer '.$this->_accessToken,
        );
        if($this->_marketplaceID) {
            $requestHeaders['X-EBAY-C-MARKETPLACE-ID'] = $this->_marketplaceID;
        }
        $this->_requestObject->requestHeaders = $requestHeaders;
    }

    /**
     * @desc 发送请求,获取响应结果
     */
    public function sendRequest(){
        try {
            $this->_urlObject->setServiceUrl();
            $this->setHeaders();

            $serviceUrl        = $this->_urlObject->getServiceUrl();
            $requestHeaders    = $this->_requestObject->requestHeaders;
            $requestData       = $this->_requestObject->requestData;
            $httpMethod        = $this->_requestObject->httpMethod;
            $timeout           = $this->_requestObject->timeout;
            $isJsonContentType = $this->_requestObject->isJsonContentType();

            //debug
            if(!empty($_REQUEST['debug'])) {
                echo '<hr>##### serviceUrl: '.$serviceUrl."<br>";
                echo '<hr>##### http Method: '.$this->_requestObject->httpMethod."<br>";
                echo '<hr>##### requestHeaders: '.print_r($requestHeaders,true)."<br>";
                echo '<hr>##### requestData: '.print_r($requestData,true)."<br>";
            }

            $curl = new Curl();
            $curl->init();
            switch (strtolower($httpMethod)) {
                case HttpRequestImpl::HTTP_GET:
                    if($isJsonContentType) {
                        $response = $curl->setOption(CURLOPT_TIMEOUT, $timeout)
                                         ->addCertificate()//添加证书
                                         ->getByRestful($serviceUrl, $requestData, $requestHeaders);
                                         var_dump($response);exit;
                    } else {
                        $response = $curl->setHeaders($requestHeaders)
                                         ->setOption(CURLOPT_TIMEOUT, $timeout)
                                         ->addCertificate()//添加证书
                                         ->get($serviceUrl, $requestData);
                    }
                    break;
   
                case HttpRequestImpl::HTTP_PUT:
                    if($isJsonContentType) {
                        $response = $curl->setOption(CURLOPT_TIMEOUT, $timeout)
                                         ->addCertificate()//添加证书
                                         ->putByJson($serviceUrl, $requestData, $requestHeaders);
                    } else {
                        $response = $curl->setHeaders($requestHeaders)
                                         ->setOption(CURLOPT_TIMEOUT, $timeout)
                                         ->addCertificate()//添加证书
                                         ->put($serviceUrl, $requestData);
                    }
                    break;

                case HttpRequestImpl::HTTP_DELETE:
                    if($isJsonContentType) {
                        $response = $curl->setOption(CURLOPT_TIMEOUT, $timeout)
                                         ->addCertificate()//添加证书
                                         ->deleteByJson($serviceUrl, $requestData, $requestHeaders);
                    } else {
                        $response = $curl->setHeaders($requestHeaders)
                                         ->setOption(CURLOPT_TIMEOUT, $timeout)
                                         ->addCertificate()//添加证书
                                         ->delete($serviceUrl, $requestData);
                    }                   
                    break;                                    

                case HttpRequestImpl::HTTP_POST:              
                default:
                    if($isJsonContentType) {
                        $response = $curl->setOption(CURLOPT_TIMEOUT, $timeout)
                                         ->addCertificate()//添加证书
                                         ->postByJson($serviceUrl, $requestData, $requestHeaders);
                    } else {
                        $response = $curl->setHeaders($requestHeaders)
                                         ->setOption(CURLOPT_TIMEOUT, $timeout)
                                         ->addCertificate()//添加证书
                                         ->post($serviceUrl, $requestData);                   
                    }
                    break;
            }

            $response = json_decode($response);     
            $curlResponse= $curl->getCurlResponse();
            $this->_responseObject = new HttpResponseImpl($curlResponse->info,$response,$curlResponse->error,$curlResponse->errno);

            //debug
            if(!empty($_REQUEST['debug']))  {
                echo '<br>##### response: '.var_export($this->_responseObject,true).'<hr>';
            }

            if( !$this->getIfSuccess() ){
                $this->writeErrorLog();
            }
        } catch (Exception $e ) {
            $this->writeErrorLog();
        }
        return $this;
    } 

    /**
     * Getter for urlObject
     * @return
     */
    public function getUrlObject(){
        return $this->_urlObject;
    }    
    
    /**
     * Getter for requestObject
     * @return object
     */
    public function getRequestObject(){
        return $this->_requestObject;
    }
    
    /**
     * Getter for responseObject
     * @return object
     */
    public function getResponseObject(){
        return $this->_responseObject;
    }
    
    /**
     * @desc 获取请求参数
     * @see ApiInterface::getRequest()
     */
    public function getRequest(){
        return $requestData;
    }
    
    /**
     * @desc 获取响应结果
     * @see ApiInterface::getResponse()
     */
    public function getResponse(){
        return empty($this->_responseObject) ? null : $this->_responseObject->responseData;
    }
    
    /**
     * @desc 判断交互是否成功
     */
    public function getIfSuccess(){
        return empty($this->_responseObject)
             || $this->_responseObject->statusCode != 200
             || isset($this->_responseObject->responseData->errors) ? false : true;
    }

    /**
     * @desc 获取响应编码
     * @return string
     */
    public function getErrorCode(){
        return $this->_errorCode;
    }
    
    /**
     * @desc 获取失败信息
     * @return string 
     */
    public function getErrorMsg(){
        //curl error
        $this->_errorMsg = empty($this->_responseObject->error) ? '' : trim($this->_responseObject->error);
        //response error message
        if(isset($this->_responseObject->responseData->errors)
             || isset($this->_responseObject->responseData->warnings)) {
            $errData = isset($this->_responseObject->responseData->errors)
                         ? $this->_responseObject->responseData->errors
                          : $this->_responseObject->responseData->warnings;
            foreach ($errData as $value) {
                if(isset($value['longMessage'])) {
                    $this->_errorMsg .= $value['longMessage'].' ';
                } else if($value['message']) {
                    $this->_errorMsg .= $value['message'].' ';
                }
            }
        }        
        return $this->_errorMsg;
    }

    /**
     * @desc 记录文件错误日志
     */
    public function writeErrorLog(){
        $logPath = Yii::getPathOfAlias('webroot').'/log/ebay/'.date('Y').'/'.date('m').'/'.date('d').'/'.date('H');
        if( !is_dir($logPath) ){
            mkdir($logPath, 0777, true);
        }
        $apiMethod = str_replace('/','-',$this->_urlObject->getApiMethod());
        $log = date('Y-m-d H:i:s').' '.$apiMethod."\n";//时间，接口名
        $log .= json_encode($this->_requestObject->requestData)."\n";//交互报文
        $log .= 'Error Message:'.$this->getErrorMsg()."\n\n";//错误信息
        $fileName = $this->accountID.'-'.$apiMethod.'.txt';
        file_put_contents($logPath.'/'.$fileName, $log, FILE_APPEND);
    }
}

?>