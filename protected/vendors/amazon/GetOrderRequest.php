<?php
/**
 * @desc 获取amazon订单列表
 * @author zhangF
 *
 */
class GetOrderRequest extends AmazonApiAbstract {
	protected $_urlPath = '/Orders/2013-09-01';		//@var string 请求url path 部分
	
	protected $_orderId = null;						//订单ID
	protected $_caller = null;						//设置调用者名称
	
	const ORDER_STATUS_PENDING 				= 'Pending';	//订单已生成，但是付款未授权
	const ORDER_STATUS_PENDING_AVAIABILITY 	= 'pendingAvailability';	//只有预订订单才有此状态, 订单已生成，但是付款未授权
	const ORDER_STATUS_UNSHIPPED 			= 'Unshipped';	//付款已经过授权，等待发货
	const ORDER_STATUS_PARTIALLY_SHIPPED 	= 'PartiallyShipped';	//已经部分发货
	const ORDER_STATUS_SHIPPED 				= 'Shipped';	//已经发货
	const ORDER_STATUS_INVOCIE_UNCONFIRMED 	= 'InvoiceUnconfirmed';	//未确认已经向买家提供发票
	const ORDER_STATUS_CANCELED 			= 'Canceled';	//已经取消的订单
	const ORDER_STATUS_UNFULFILLABLE 		= 'Unfulfillable';	//无法进行配送的订单
	
	const ORDER_FULLFILLMENTCHANNEL_FBA		= 'AFN';	//FBA仓库发货
	const ORDER_FULLFILLMENTCHANNEL_MBA		= 'MFN';	//自己仓库发货
	
	const MAX_REQUEST_TIMES = 6;	//接口最大请求次数
	const RESUME_RATE_INTERVAL = 60;		//请求恢复间隔 60秒
	const RESUME_RATE_NUM = 1;				//请求恢复每次1个
	
	public function __construct() {
		$this->_remainTimes = self::MAX_REQUEST_TIMES;
	}
	
	/**
	 * @desc 设置服务对象实例
	 */
	protected function setServiceEntities(){
		$config = array (
				'ServiceURL' => $this->_serviceUrl,
				'ProxyHost' => $this->_proxyHost,
				'ProxyPort' => $this->_proxyPort,
				'MaxErrorRetry' => 3,
				'ProxyUsername' => $this->_proxyUserName,
				'ProxyPassword' => $this->_proxyPassword,
		);
		$service = new MarketplaceWebServiceOrders_Client(
				$this->_accessKeyID,
				$this->_secretAccessKey,
				$this->_appName,
				$this->_appVersion,
				$config
		);
		$this->_serviceEntities = $service;
	}
	
	/**
	 * @desc 设置请求对象实例
	 */
	protected function setRequestEntities() {
		$request = new MarketplaceWebServiceOrders_Model_GetOrderRequest();
		$request->setSellerId($this->_merchantID);
		$request->setMWSAuthToken($this->_marketPlaceID);
		$request->setAmazonOrderId($this->_orderId);
		$this->_requestEntities = $request;
	}
	
	public function requestAble(){
		if(!$this->_caller){
			throw new Exception("No Specify Caller");
		}else{
			$amazonApiRequestPool = new AmazonAPIRequestPool();
			$i = 0;
			do{
				$dlaf = $amazonApiRequestPool->getRequestAccess($this->_caller, $this->_accountID, "Order/GetOrder", self::MAX_REQUEST_TIMES, self::RESUME_RATE_INTERVAL);
				if(!$dlaf){
					sleep(self::RESUME_RATE_INTERVAL);
				}
				$i++;
				if($i>10)
					throw new Exception("request Max Num!!");
			}while (!$dlaf);
		}
	}
	
	
	
	/**
	 * 调用接口方法
	 * @see AmazonApiAbstract::call()
	 */
	public function call() {
		$orderDatas = array();
		try {

			$this->requestAble();
			$response = $this->_serviceEntities->getOrder($this->_requestEntities);

			if (empty($response))
				throw new Exception('Amazom MWS Return Empty');
			
			$responseXml = $response->toXML();
			$resonseArray = XML2Array::createArray($responseXml);
			$orderDatas = isset($resonseArray['GetOrderResponse']['GetOrderResult']['Orders']['Order']) ? $resonseArray['GetOrderResponse']['GetOrderResult']['Orders']['Order'] : array();
			if(isset($orderDatas['AmazonOrderId'])){
				//lihy add 2016-03-10
				//如果只拉取了一个,则只是一维数组,所以需要转换为二维数组
				$orderDatas = array($orderDatas);
			}
			$this->response = $orderDatas;
			if (empty($orderDatas))
				$this->_errorMessage = 'No Orders';
		} catch (MarketplaceWebServiceOrders_Exception $e) {
			$this->_errorMessage = "MarketplaceWebServiceOrders_Exception: " . $e->getErrorMessage();
			return false;
		} catch (Exception $e) {
			$this->_errorMessage = 'Call api failure, ' . $e->getMessage();
			return false;
		}
		return true;
	}
	
	
	public function setOrderId($orderId){
		$this->_orderId = $orderId;
		return $this;
	}
}