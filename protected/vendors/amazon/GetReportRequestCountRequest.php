<?php
class GetReportRequestCountRequest extends AmazonWebServerAbstract {
	protected $_urlPath = '';			//请求url path 部分
	
	const MAX_REQUEST_TIMES = 10;                   //接口最大请求次数
	const RESUME_RATE_INTERVAL = 45;		//请求恢复间隔 45秒
	const RESUME_RATE_NUM = 1;			//请求恢复每次1个
	
	public function setRequestEntities() {
		$request = new MarketplaceWebService_Model_GetReportCountRequest();
		$request->setMerchant($this->_merchantID);
		$this->_requestEntities = $request;
	}
	
	public function call() {
            
		try {
			$response = $this->_serviceEntities->getReportCount($this->_requestEntities);
			$this->response = $response;
			return true;
		} catch (Exception $e) {
			$this->_errorMessage = $e->getMessage();
			return false;
		}
	}
	
}