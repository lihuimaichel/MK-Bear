<?php
/**
 * @desc 根据 ASIN 值列表，返回商品及其属性列表。
 * @author lihy
 *
 */
class GetMatchingProductRequest extends AmazonApiAbstract{
	protected $_urlPath = '/Products/2011-10-01';
	protected $_asinID;
	const MAX_REQUEST_TIMES = 6;	//接口最大请求次数
//	const RESUME_RATE_INTERVAL = 60;		//请求恢复间隔 60秒
	//const RESUME_RATE_NUM = 1;				//请求恢复每次1个
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
		$request = new MarketplaceWebServiceProducts_Model_GetMatchingProductRequest();
		$request->setSellerId($this->_merchantID);
		$request->setMarketplaceId($this->_marketPlaceID);
		$Ansin = new MarketplaceWebServiceProducts_Model_ASINListType();
		$Ansin->setASIN($this->_asinID);
		$request->setASINList($Ansin);
		$this->_requestEntities = $request;
	}
	
	/**
	 * 调用接口方法
	 * @see AmazonApiAbstract::call()
	 */
	public function call() {
		try {
			$response = $this->_serviceEntities->getMatchingProduct($this->_requestEntities);
			if($response->isSetGetMatchingProductResult()){
				$responseResult = $response->getGetMatchingProductResult();
				foreach ($responseResult as $result){
					if($result->getstatus() == 'Success'){
						$product = $result->getProduct();
						//print_r($product);
						$identifiers = $product->getIdentifiers();
						$marketplaceASIN = $identifiers->getMarketplaceASIN();
						$ASIN  = $marketplaceASIN->getASIN();
						$SKUidentifiers = $identifiers->getSKUIdentifier();
						if($SKUidentifiers){
							
						}
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
	/**
	 * @设置商品asin码
	 * @param unknown $asinids
	 */
	public function setAsinID($asinids){
		$this->_asinID = $asinids;
	}
}