<?php
/**
 * @desc shopee API Abstract
 * @author yangsh
 * @since 2016-10-25
 */
class GetOrdersListFromLocalRequest implements PlatformApiInterface {
    
    protected $_endpoint = 'GetOrdersList';

    /**@var string 最终交互地址*/
    protected $_url = null;
    
    /**@var int 账号ID*/
    protected $accountID = 0;
    
    /**@var string 请求内容*/
    protected $request = null;
    
    /**@var string 返回响应信息*/
    protected $response = null;
    
    /**@var string 请求报文信息*/
    protected $_requestbody = null;
    
    /** @var boolean 是否为Post交互*/
    protected $_isPost = true;

    /*@var 拉单时间*/
    protected $_createTimeFrom = null;

    protected $_createTimeTo = null;

    protected $_updateTimeFrom = null;

    protected $_updateTimeTo = null;

    /*@var 平台时间*/
    protected $_platformCreateTimeFrom;

    protected $_platformCreateTimeTo;

    protected $_platformUpdateTimeFrom;

    protected $_platformUpdateTimeTo;

    /*@var 订单状态*/
    protected $_orderStatus = null;

    public $pageNum = 1;//当前页

    public $pageSize = 100;//每页记录数

    public $pageCount = 1;//页总数
    
    /**
     * 秘钥key
     * @var unknown
     */
    protected $_secretkey = null;
    /** @var string client id **/
    protected $_partnerID = null;
    
    /** @var string client秘钥 **/
    protected $_shopID = null;    
    /**
     * curl timeout
     * @var unknown
     */
    protected $_timeout = 1800;
    
    protected $_contentType = "application/json";
    protected $_headers = null;

    /**
     * @desc 设置账号信息
     * @param int $accountID
     */
    public function setAccount($accountID){
        $config = ConfigFactory::getConfig('imageKeys');
        if( !isset($config[ 'COMMON' ]) ){
            throw new CException(Yii::t('system', 'Server Does Not Exists'));
        }
        $this->_url                 = $config[ 'COMMON' ] ['url'] ['getOrders'];        
        $this->accountID            = $accountID;
        //获取账号相关信息
        // $accountInfo                = ShopeeAccount::getAccountInfoById($accountID);

        return $this;
    }

    public function setCreateTimeFrom($createTimeFrom) {
        $this->_createTimeFrom = $createTimeFrom;
        return $this;
    }   
    
    public function setCreateTimeTo($createTimeTo) {
        $this->_createTimeTo = $createTimeTo;
        return $this;
    } 

    public function setUpdateTimeFrom($updateTimeFrom) {
        $this->_updateTimeFrom = $updateTimeFrom;
        return $this;
    }   
    
    public function setUpdateTimeTo($updateTimeTo) {
        $this->_updateTimeTo = $updateTimeTo;
        return $this;
    }   

    public function setPlatformCreateTimeFrom($createTimeFrom) {
        $this->_platformCreateTimeFrom = $createTimeFrom;
        return $this;
    }   
    
    public function setPlatformCreateTimeTo($createTimeTo) {
        $this->_platformCreateTimeTo = $createTimeTo;
        return $this;
    } 

    public function setPlatformUpdateTimeFrom($updateTimeFrom) {
        $this->_platformUpdateTimeFrom = $updateTimeFrom;
        return $this;
    }   
    
    public function setPlatformUpdateTimeTo($updateTimeTo) {
        $this->_platformUpdateTimeTo = $updateTimeTo;
        return $this;
    }     

    public function setOrderStatus($orderStatus) {
        $this->_orderStatus = $orderStatus;
        return $this;
    }             
    
    public function setPageNum($pageNum) {
        $this->pageNum = $pageNum;
        return $this;
    }

    public function setHeader(){
        $headers = array(
                'Content-Type'=>$this->_contentType,
        );
        $this->_headers = $headers;
    }  

    public function setRequest() {
        $request = array(
            'platform' => Platform::CODE_SHOPEE,
            'account'  => $this->accountID,
            'pageNum'  => $this->pageNum,
            'pageSize' => $this->pageSize,
        );

        if (!empty($this->_createTimeFrom)) {
            $request['createTimeFrom'] = $this->_createTimeFrom;
        }

        if (!empty($this->_createTimeTo)) {
            $request['createTimeTo'] = $this->_createTimeTo;
        }

        if (!empty($this->_updateTimeFrom)) {
            $request['updateTimeFrom'] = $this->_updateTimeFrom;
        }

        if (!empty($this->_updateTimeTo)) {
            $request['updateTimeTo'] = $this->_updateTimeTo;
        }   

        if (!empty($this->_platformCreateTimeFrom)) {
            $request['platformCreateTimeFrom'] = $this->_platformCreateTimeFrom;
        }

        if (!empty($this->_platformCreateTimeTo)) {
            $request['platformCreateTimeTo'] = $this->_platformCreateTimeTo;
        }

        if (!empty($this->_platformUpdateTimeFrom)) {
            $request['platformUpdateTimeFrom'] = $this->_platformUpdateTimeFrom;
        }

        if (!empty($this->_platformUpdateTimeTo)) {
            $request['platformUpdateTimeTo'] = $this->_platformUpdateTimeTo;
        }          

        if (!empty($this->_orderStatus)) {
            $request['orderStatus'] = $this->_orderStatus;
        }                                
        $this->request = $request;
        return $this;
    }

    /**
     * @desc 发送请求,获取响应结果
     */
    public function sendRequest() {
        try {
            $this->getRequestBody();
            $this->setHeader();
            $curl = new Curl();
            $curl->init();
            //MHelper::writefilelog('shopee/request.txt',json_encode($this->_requestbody)."\r\n"); 
            if($this->_isPost){
                $response = $curl->setOption(CURLOPT_TIMEOUT, $this->_timeout)
                                 ->setOption(CURLOPT_CONNECTTIMEOUT,$this->_timeout)
                                 ->postByJson($this->_url, $this->_requestbody);
            }else{
                $response = $curl->setOption(CURLOPT_TIMEOUT, $this->_timeout)
                                 ->setOption(CURLOPT_CONNECTTIMEOUT,$this->_timeout)
                                 ->getByRestful($this->_url, $this->_requestbody);
            }
            $this->response = json_decode($response);
            //MHelper::writefilelog('shopee/response.txt',$response."\r\n"); 
            if( !$this->getIfSuccess() ){
                $this->writeErrorLog();
            }
        } catch (Exception $e ) {
            $this->writeErrorLog();
        }
        return $this;
    }  

    /**
     * @desc 将请求参数转化为Json
     */
    public function getRequestBody(){
        $this->_requestbody = $this->getRequest();
        return $this->_requestbody;
    }
    
    /**
     * @desc 获取请求参数
     * @see ApiInterface::getRequest()
     */
    public function getRequest() {
        return $this->request;
    }
    
    /**
     * @desc 获取响应结果
     * @see ApiInterface::getResponse()
     */
    public function getResponse() {
        return $this->response;
    }
    
    /**
     * @desc 判断交互是否成功
     */
    public function getIfSuccess(){
        if( isset($this->response->status) && ( $this->response->status=='succ' ) ){
            return true;
        }else{
            return false;
        }
    }
    
    /**
     * @desc 获取失败信息
     * @return string 
     */
    public function getErrorMsg(){
        $errorMessage = '';
        if( isset($this->response->errormsg) ){
            $errorMessage .= $this->response->errormsg;
        }else if(isset($this->response->message)){
            $errorMessage .= $this->response->message;
        } else {
            $errorMessage = 'unkown';
        }
        return $errorMessage;
    }
    
    /**
     * @desc 记录文件错误日志
     */
    public function writeErrorLog(){
        $logPath = Yii::getPathOfAlias('webroot').'/log/wish/'.date('Y').'/'.date('m').'/'.date('d').'/'.date('H');
        if( !is_dir($logPath) ){
            mkdir($logPath, 0777, true);
        }
        $log = date('Y-m-d H:i:s').'           '.$this->_url."\n";//时间，接口名
        $log .= json_encode($this->_requestbody)."\n";//交互报文
        $log .= 'Error Message:'.$this->getErrorMsg()."\n\n";//错误信息
        $fileName = $this->accountID.'-'.str_replace('/', '_', $this->_endpoint).'.txt';
        file_put_contents($logPath.'/'.$fileName, $log, FILE_APPEND);
    }

}

?>