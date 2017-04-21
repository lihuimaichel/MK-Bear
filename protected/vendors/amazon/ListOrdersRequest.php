<?php
/**
 * @desc 获取amazon订单列表
 * @author zhangF
 *
 */
class ListOrdersRequest extends AmazonApiAbstract {
	protected $_urlPath = '/Orders/2013-09-01';		//@var string 请求url path 部分
	protected $_startTime = null;					//@var siring 订单开始时间
	protected $_endTime = null;						//@var string 订单结束时间
	
	protected $_startUpdateTime = null;					//@var siring 订单开始时间(以订单更新时间为准)
	protected $_endUpdateTime = null;					//@var string 订单结束时间(以订单更新时间为准)

	protected $_fulfillmentChannel = null;
	protected $_orderStatus = null;					//@var string 订单状态
	protected $_numbersOfPer = 100;				    //@var integer 每页拉取订单数
	
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
		$request = new MarketplaceWebServiceOrders_Model_ListOrdersRequest();
		$request->setSellerId($this->_merchantID);
		$request->setMarketplaceId($this->_marketPlaceID);
		if(!is_null($this->_startTime))
			$request->setCreatedAfter($this->_startTime);
		if (!is_null($this->_endTime))
			$request->setCreatedBefore($this->_endTime);
		
		if(!is_null($this->_startUpdateTime))
			$request->setLastUpdatedAfter($this->_startUpdateTime);
		if(!is_null($this->_endUpdateTime))
			$request->setLastUpdatedBefore($this->_endUpdateTime);
		
		if (is_array($this->_orderStatus))
			$request->setOrderStatus($this->_orderStatus);
		else if (is_string($this->_orderStatus))
			$request->setOrderStatus(array($this->_orderStatus));
		if (!is_null($this->_fulfillmentChannel)) {
			$request->setFulfillmentChannel($this->_fulfillmentChannel);
		}
		$request->setMaxResultsPerPage($this->_numbersOfPer);
		$this->_requestEntities = $request;
	}
	
	public function requestAble(){
		if(!$this->_caller){
			throw new Exception("No Specify Caller");
		}else{
			$amazonApiRequestPool = new AmazonAPIRequestPool();
			$i = 0;
			do{
				$dlaf = $amazonApiRequestPool->getRequestAccess($this->_caller, $this->_accountID, "Order/ListOrders", self::MAX_REQUEST_TIMES, self::RESUME_RATE_INTERVAL);
				if(!$dlaf){
					sleep(self::RESUME_RATE_INTERVAL+2);
				}
				$i++;
				if($i>30)
					throw new Exception("request Max Num!!");
			}while (!$dlaf);
		}
	}
	
	
	public function requestNextTokenAble(){
		if(!$this->_caller){
			throw new Exception("No Specify Caller");
		}else{
			$amazonApiRequestPool = new AmazonAPIRequestPool();
			$i = 0;
			do{
				$dlaf = $amazonApiRequestPool->getRequestAccess($this->_caller, $this->_accountID, "Order/ListOrdersByNextToken", self::MAX_REQUEST_TIMES, self::RESUME_RATE_INTERVAL);
				if(!$dlaf){
					sleep(self::RESUME_RATE_INTERVAL+2);
				}
				$i++;
				if($i>30)
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
        static $repeatNum = 0;
		try {
			/* if (!$this->requestAble())
				sleep(self::RESUME_RATE_INTERVAL); */
			$this->requestAble();

			$response = $this->_serviceEntities->listOrders($this->_requestEntities);		
			if (empty($response))
				throw new Exception('Amazom MWS Return Empty');
			//$this->_remainTimes--;	//统计接口调用次数
			$responseXml = $response->toXML();
			$resonseArray = XML2Array::createArray($responseXml);
			$orderDatas = isset($resonseArray['ListOrdersResponse']['ListOrdersResult']['Orders']['Order']) ? $resonseArray['ListOrdersResponse']['ListOrdersResult']['Orders']['Order'] : array();
			if(isset($orderDatas['AmazonOrderId'])){
				//lihy add 2016-03-10
				//如果只拉取了一个,则只是一维数组,所以需要转换为二维数组
				$orderDatas = array($orderDatas);
			}
			$nextToken = isset($resonseArray['ListOrdersResponse']['ListOrdersResult']['NextToken']) ? $resonseArray['ListOrdersResponse']['ListOrdersResult']['NextToken'] : false;
 			while ($nextToken) {
				//如果达到调用次数限制，休眠恢复间隔指定时间
				/* if (!$this->requestAble()) {
					sleep(self::RESUME_RATE_INTERVAL);
					$this->_remainTimes++;
					continue;
				} */
 				$this->requestNextTokenAble();
 				
				$request = new MarketplaceWebServiceOrders_Model_ListOrdersByNextTokenRequest();
				$request->setSellerId($this->_merchantID);
				$request->setNextToken($nextToken);
				$nextTokenResponse = $this->_serviceEntities->listOrdersByNextToken($request);
				//$this->_remainTimes--;	//统计接口调用次数
				$nextTokenResponseXml = $nextTokenResponse->toXML();
				$nextTokenResponseArray = XML2Array::createArray($nextTokenResponseXml);
				//将下页的订单合并到第一页里面
				foreach ($nextTokenResponseArray['ListOrdersByNextTokenResponse']['ListOrdersByNextTokenResult']['Orders']['Order'] as $orders) {
					$orderDatas[] = $orders;
				}
				$nextToken = isset($nextTokenResponseArray['ListOrdersByNextTokenResponse']['ListOrdersByNextTokenResult']['NextToken']) ? $nextTokenResponseArray['ListOrdersByNextTokenResponse']['ListOrdersByNextTokenResult']['NextToken'] : false;			
			}
			$this->response = $orderDatas;
			if (empty($orderDatas))
				$this->_errorMessage = 'No Orders';
		} catch (MarketplaceWebServiceOrders_Exception $e) {
			$msg = $e->getErrorMessage();
			if(trim($msg) == "Request is throttled" && $repeatNum < 30){
				$repeatNum++;
				sleep(self::RESUME_RATE_INTERVAL);
				return $this->call();
			}
			$this->_errorMessage = "MarketplaceWebServiceOrders_Exception: " . $e->getErrorMessage();
			return false;
		} catch (Exception $e) {
			$this->_errorMessage = 'Call api failure, ' . $e->getMessage();
			return false;
		}
		return true;
	}
	/**
	 * @desc 设置开始时间
	 * @param string $time
	 */
	public function setStartTime($time) {
		$this->_startTime = $time;
	}
	
	/**
	 * @desc 设置结束时间
	 * @param string $time
	 */
	public function setEndTime($time) {
		$this->_endTime = $time;
	}
	
	
	/**
	 * @desc 设置订单更新开始时间
	 * @param string $time
	 */
	public function setStartUpdateTime($time) {
		$this->_startUpdateTime = $time;
	}
	
	/**
	 * @desc 设置订单更新结束时间
	 * @param string $time
	 */
	public function setEndUpdateTime($time) {
		$this->_endUpdateTime = $time;
	}
	
	/**
	 * @desc 设置订单状态
	 * @param unknown $status
	 */
	public function setOrderStatus(array $status) {
		$this->_orderStatus = $status;
	}
	
	/**
	 * @DESC 设置运送方式
	 * @param unknown $fillmentChannel
	 * @return ListOrdersRequest
	 */
	public function setFulfillmentChannel($fillmentChannel){
		$this->_fulfillmentChannel = $fillmentChannel;
		return $this;
	}
}