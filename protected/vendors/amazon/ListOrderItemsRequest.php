<?php
/**
 * @desc 获取订单商品信息
 * @author zhangF
 *
 */
class ListOrderItemsRequest extends AmazonApiAbstract {
	protected $_urlPath = '/Orders/2013-09-01';		//@var string 请求url path 部分
	protected $_orderId = null;						//@var integer Amazon 订单号

	const MAX_REQUEST_TIMES = 30;	//接口最大请求次数
	const RESUME_RATE_INTERVAL = 2;		//请求恢复间隔2秒
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
		$request = new MarketplaceWebServiceOrders_Model_ListOrderItemsRequest();
		$request->setSellerId($this->_merchantID);
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
				$dlaf = $amazonApiRequestPool->getRequestAccess($this->_caller, $this->_accountID, "Order/ListOrderItems", self::MAX_REQUEST_TIMES, self::RESUME_RATE_INTERVAL);
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
				$dlaf = $amazonApiRequestPool->getRequestAccess($this->_caller, $this->_accountID, "Order/ListOrderItemsByNextToken", self::MAX_REQUEST_TIMES, self::RESUME_RATE_INTERVAL);
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
		$orderItemDatas = array();
		try {
			/* if (!$this->requestAble())
				sleep(self::RESUME_RATE_INTERVAL); */
			$this->requestAble();
			$response = $this->_serviceEntities->listOrderItems($this->_requestEntities);
			//$this->_remainTimes--;	//统计接口调用次数
			$responseXml = $response->toXML();
			$responseArray = XML2Array::createArray($responseXml);
			$orderItemDatas[] = isset($responseArray['ListOrderItemsResponse']['ListOrderItemsResult']['OrderItems']['OrderItem']) ? $responseArray['ListOrderItemsResponse']['ListOrderItemsResult']['OrderItems']['OrderItem'] : array();
			$nextToken = isset($responseArray['ListOrderItemsResponse']['ListOrderItemsResult']['NextToken']) ? $responseArray['ListOrderItemsResponse']['ListOrderItemsResult']['NextToken'] : false;
			while ($nextToken) {
				//如果达到调用次数限制，休眠恢复间隔指定时间
				/* if (!$this->requestAble()) {
					sleep(self::RESUME_RATE_INTERVAL);
					$this->_remainTimes++;
					continue;
				} */
				$this->requestNextTokenAble();
				$request = new MarketplaceWebServiceOrders_Model_ListOrderItemsByNextTokenRequest();
				$request->setSellerId($this->_merchantID);
				$request->setNextToken($nextToken);
				$nextTokenResponse = $this->_serviceEntities->listOrderItemsByNextToken($request);
				//$this->_remainTimes--;	//统计接口调用次数
				$nextTokenResponseXml = $nextTokenResponse->toXML();
				$nextTokenResponseArray = XML2Array::createArray($nextTokenResponseXml);
				//将下页的订单合并到第一页里面
				foreach ($nextTokenResponseArray['ListOrderItemsByNextTokenResponse']['ListOrderItemsByNextTokenResult']['OrderItems']['OrderItem'] as $orderItems)
					$orderItemDatas[] = $orderItems;
				$nextToken = isset($nextTokenResponseArray['ListOrderItemsByNextTokenResponse']['ListOrderItemsByNextTokenResult']['NextToken']) ? $nextTokenResponseArray['ListOrderItemsByNextTokenResponse']['ListOrderItemsByNextTokenResult']['NextToken'] : false;
			}
			$this->response = $orderItemDatas;
			if (empty($orderItemDatas))
				$this->_errorMessage = 'No Order Items';
		} catch (MarketplaceWebServiceOrders_Exception $e) {
			$this->_errorMessage = $this->_accountID.' @@ '. $this->_orderId. ' ## ' .$e->getMessage();
			return false;
		} catch (Exception $e) {
			$this->_errorMessage = 'Call api failure, ' . $e->getMessage();
			return false;
		}
		return true;		
	}
	
	/**
	 * @desc 设置订单状态
	 * @param unknown $status
	 */
	public function setOrderId($orderId) {
		$this->_orderId = $orderId;
	}
}