<?php
/**
 * @desc   获取日报列表的下一页
 * @author lihy
 *
 */
class GetReportListByNextTokenRequest extends AmazonApiAbstract{
	private $_nextToken;
	public function setServiceEntities(){
		$config = array (
				'ServiceURL' => $this->_serviceUrl,
				'ProxyHost' => null,
				'ProxyPort' => -1,
				'MaxErrorRetry' => 3,
		);
		$service = new MarketplaceWebService_Client(
				$this->_accessKeyID,
				$this->_secretAccessKey,
				$config,
				$this->_appName,
				$this->_appVersion);
		$this->_serviceEntities = $service;
	}
	
	public function setRequestEntities(){
		$request = new MarketplaceWebService_Model_GetReportRequestListByNextTokenRequest();
		$request->setMarketplace($this->_marketPlaceID);
		$request->setMerchant($this->_merchantID);
		$request->setNextToken($this->_nextToken);
		$this->_requestEntities = $request;
	}
	
	public function call(){
		try{
			$reponse = $this->_serviceEntities->getReportListByNextToken($this->_requestEntities);
			$reportList = null;
			if($reponse->isSetGetReportListByNextTokenResult()){
				$reportList = $reponse->getGetReportListByNextTokenResult();
			}
			$this->response = $reportList;
			return true;
		}catch(MarketplaceWebService_Exception $e){
			$this->_errorMessage = $e->getMessage();
			return false;
		}
		return true;
	}
	
	public function setNextToken($nextToken){
		$this->_nextToken = $nextToken;
	}
	
	public function getErrorMsg(){
		return $this->_errorMessage;
	}
}