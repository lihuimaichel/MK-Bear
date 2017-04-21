<?php
/**
 * AMAZON 配送库存
 * @author 	Rex
 * @since  	2016-07-06
 */

class ListInventorySupply extends AmazonApiAbstract {

	protected $_urlPath = '/FulfillmentInventory/2010-10-01';
	protected $_sellerSkus = null;
	protected $_queryStartDateTime = null;
	protected $_responseGroup = null;

	const MAX_REQUEST_TIMES = 6;	//接口最大请求次数

	public function __construct() {
		$this->_remainTimes = self::MAX_REQUEST_TIMES;
	}

	/**
	 * 设置查询开始日期
	 */
	public function setQueryStartDateTime($startDateTime) {
		$this->_queryStartDateTime = $startDateTime;
	}

	/**
	 * 设置服务对象实例
	 */
	public function setServiceEntities() {
		$config = array(
			'ServiceURL'	=> $this->_serviceUrl,
			'ProxyHost'		=> $this->_proxyHost,
			'ProxyPort'		=> $this->_proxyPort,
			'MaxErrorRetry'	=> 3,
			'ProxyUsername' => $this->_proxyUserName,
			'ProxyPassword'	=> $this->_proxyPassword,
		);
		$service = new FBAInventoryServiceMWS_Client(
			$this->_accessKeyID,
			$this->_secretAccessKey,
			$config,
			$this->_appName,
			$this->_appVersion
		);

		$this->_serviceEntities = $service;
	}

	/**
	 * 设置请求对象实例
	 */
	public function setRequestEntities() {
		$request = new FBAInventoryServiceMWS_Model_ListInventorySupplyRequest();
		$request->setSellerId($this->_merchantID);
		$request->setMarketplaceId($this->_marketPlaceID);
		if (!is_null($this->_queryStartDateTime)) {
			$request->setQueryStartDateTime($this->_queryStartDateTime);
		}
		$this->_requestEntities = $request;
	}

	/**
	 * 请求接口
	 */
	public function call() {
		try {
			$response = $this->_serviceEntities->listInventorySupply($this->_requestEntities);
			$responseXml = $response->toXML();
			$responseArray = XML2Array::createArray($responseXml);
			//var_dump($responseArray);
			$inventoryDatas = isset($responseArray['ListInventorySupplyResponse']['ListInventorySupplyResult']['InventorySupplyList']['member']) ? $responseArray['ListInventorySupplyResponse']['ListInventorySupplyResult']['InventorySupplyList']['member'] : array();
			//var_dump($inventoryDatas);

			//nextToken

			$this->response = $inventoryDatas;

		} catch (Exception $e) {

		}
		return true;
	}


}

?>