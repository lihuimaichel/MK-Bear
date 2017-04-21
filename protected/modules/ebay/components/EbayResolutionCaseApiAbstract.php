<?php
/**
 * @desc Ebay API Abstract
 * @author lihy
 * @since 2015-06-02
 */
abstract class EbayResolutionCaseApiAbstract extends EbayApiAbstract {
	/**@var string 用户Token*/
	public $_usertoken = null;
	
	/**@var string 站点ID*/
	protected $_siteID = 0;//某些不需要传站点的请求默认为美国站
	
	/**@var string 开发者账号ID(与开发者账号绑定)*/
	protected $_devID = null;
	
	/**@var string APP ID(与开发者账号绑定)*/
	protected $_appID = null;
	
	/**@var string 证书ID(与开发者账号绑定)*/
	protected $_certID = null;
	
	/**@var string 请求接口名*/
	protected $_verb = null;
	
	/**@var string 请求版本号*/
	protected $_compatLevel = null;
	
	/**@var string 添加文件传输表头*/
	protected $_boundary = false;
	
	/**@var int 账号ID*/
	protected $accountID = 0;
	public $_callType = 'resolution';
	//https://svcs.ebay.com/services/resolution/v1/ResolutionCaseManagementService
	//https://svcs.ebay.com/services/resolution/ResolutionCaseManagementService/v1
	public $_serverUrl = 'https://svcs.ebay.com/services/resolution/v1/ResolutionCaseManagementService';
	
	public $_xmlsn = "http://www.ebay.com/marketplace/resolution/v1/services";
	
	
    // ==== 新增 20160302 ======
    protected $_GLOBAL_ID = null;
    protected $_MESSAGE_ENCODING = 'UTF-8';
    protected $_SERVICE_NAME = null;
    protected $_OPERATION_NAME = null;
    protected $_REQUEST_DATA_FORMAT = 'XML';
    protected $_RESPONSE_DATA_FORMAT = 'XML';
    protected $_REST_PAYLOAD = null;
    protected $_SECURITY_TOKEN = null;
    protected $_SERVICE_VERSION = '1.1.0';
    // ===== add end =======
    
    //@overried
    public function setAccount($accountID){
    	//获取账号相关信息
    	$accountInfo = EbayAccount::getAccountInfoById($accountID);
    	var_dump($accountInfo);
    	$ebayKeys = ConfigFactory::getConfig('ebayKeys');
    	$this->accountID    = $accountID;
    	$this->_usertoken   = $accountInfo['user_token'];
    	$this->_appID       = $ebayKeys['appID'];
    	$this->_devID       = $ebayKeys['devID'];
    	$this->_certID      = $ebayKeys['certID'];
    	return $this;
    }
    
    
}

?>