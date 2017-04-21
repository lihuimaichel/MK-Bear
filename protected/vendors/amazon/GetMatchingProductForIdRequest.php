<?php
class GetMatchingProductForIdRequest extends AmazonApiAbstract{
	protected $_urlPath = '/Products/2011-10-01';
	protected $_IdList;
	protected $_IdType = 'ASIN'; //IDTYPE = ASIN、GCID、SellerSKU、UPC、EAN、ISBN 和 JAN
	
	const MAX_REQUEST_TIMES = 600;	//接口最大请求次数
	const RESUME_RATE_INTERVAL = 1;	//请求恢复间隔 1秒
	const RESUME_RATE_NUM = 600;	//请求恢复每次600个
	/**
	 * @desc 设置服务对象实例
	 */
	protected function setServiceEntities(){
		$config = array (
				'ServiceURL' => $this->_serviceUrl,
				'ProxyHost' => $this->_proxyHost,
				'ProxyPort' => $this->_proxyPort,
				'MaxErrorRetry' => 3,
		);
	
		$service = new MarketplaceWebServiceProducts_Client(
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
		$request = new MarketplaceWebServiceProducts_Model_GetMatchingProductForIdRequest();
		$request->setSellerId($this->_merchantID);
		$request->setMarketplaceId($this->_marketPlaceID);
		$idList = new MarketplaceWebServiceProducts_Model_IdListType;
		$idList->setId($this->_IdList);
		$request->setIdList($idList);
		$request->setIdType($this->_IdType);
		$this->_requestEntities = $request;
	}
	
	/**
	 * 调用接口方法
	 * @see AmazonApiAbstract::call()
	 */
	public function call() {
		try {
			$response = $this->_serviceEntities->getMatchingProductForId($this->_requestEntities);
			if($response->isSetGetMatchingProductForIdResult()){
				$responseResult = $response->getGetMatchingProductForIdResult();
				foreach ($responseResult as $result){
					if($result->getstatus() == 'Success'){
						$product = $result->getProducts()->getProduct();
						echo  "<pre>";
						print_r($product);
						echo "</pre>";
					}
				}
			}
			return false;
		} catch (MarketplaceWebServiceProducts_Exception $ex) {
			$this->_errorMessage = 'Call api failure, ' . $ex->getMessage();
			return false;
		}
		return true;
	}
	
	public function setIdList($idList){
		$this->_IdList = $idList;
		return $this;
	}
	
	public function setIdType($idType){
		$this->_IdType = $idType;
		return $this;
	}
}