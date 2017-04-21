<?php
/**
 * @desc 获取上传处理结果列表
 *
 */
class GetCommonFeedSubmissionListRequest extends AmazonApiAbstract {
	
	protected $_urlPath = '';		//@var string 请求url path 部分
	protected $_feedSubmissionId = '';					//@var siring feedSubmissionId
	protected $_batchNo = null;							//@val string 请求批次号
	/**
	 * @desc 请求类型
	 * @var unknown
	 */
	protected $_reqType = null;
	
	const EVENT_NAME           = 'getfeedsubmissionlist';
	const MAX_REQUEST_TIMES    = 6;	//接口最大请求次数
	const RESUME_RATE_INTERVAL = 60; //请求恢复间隔 60秒
	const RESUME_RATE_NUM      = 1;	//请求恢复每次1个
	
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
		);
		$service = new MarketplaceWebService_Client(
				$this->_accessKeyID,
				$this->_secretAccessKey,
				$config,
				$this->_appName,
				$this->_appVersion
		);
		$this->_serviceEntities = $service;
	}
	
	/**
	 * @desc 设置请求对象实例
	 */
	protected function setRequestEntities() {
		$request = new MarketplaceWebService_Model_GetFeedSubmissionListRequest();
		$request->setMerchant($this->_merchantID);
		$idList = new MarketplaceWebService_Model_IdList();
		if(is_array($this->_feedSubmissionId)){
			//add by lihy In the 2015-11-02
			$request->setFeedSubmissionIdList($idList->setId( $this->_feedSubmissionId ));
		}else{
			$request->setFeedSubmissionIdList($idList->withId( $this->_feedSubmissionId ));
		}
		$this->_requestEntities = $request;
	}
	
	/**
	 * 调用接口方法
	 * @see AmazonApiAbstract::call()
	 */
	public function call() {
		$result = '';
		try {
			$response = $this->_serviceEntities->getFeedSubmissionList($this->_requestEntities);
			if (empty($response))
				throw new Exception('Amazom MWS Return Empty');
			if($response->isSetGetFeedSubmissionListResult()){
				$getFeedSubmissionListResult = $response->getGetFeedSubmissionListResult();
				$nextToken = '';
				if($getFeedSubmissionListResult->isSetNextToken()){
					$nextToken  = $getFeedSubmissionListResult->getNextToken();
				}
				if($getFeedSubmissionListResult->isSetHasNext()){
					$hasNext = $getFeedSubmissionListResult->getHasNext();
				}
				//echo "<br>nextToken == ".$nextToken;
				$feedSubmissionInfoList = $getFeedSubmissionListResult->getFeedSubmissionInfoList();

				$this->doFilterSubmissionList($feedSubmissionInfoList);	//状态分组

				while( !empty($nextToken) ){
					if( !empty($nextToken) && $hasNext){
						$nextTokenRequest = new MarketplaceWebService_Model_GetFeedSubmissionListByNextTokenRequest();
						$nextTokenRequest->setMerchant($this->_merchantID);
						$nextTokenRequest->setNextToken($nextToken);
						$nextTokenResponse = $this->_serviceEntities->getFeedSubmissionListByNextToken( $nextTokenRequest );
						if( $nextTokenResponse->isSetGetFeedSubmissionListByNextTokenResult() ){
							$getFeedSubmissionListByNextTokenResult = $nextTokenResponse->getGetFeedSubmissionListByNextTokenResult();
							if($getFeedSubmissionListByNextTokenResult->isSetNextToken()){
								$nextToken = $getFeedSubmissionListByNextTokenResult->getNextToken();
							}
							if($getFeedSubmissionListByNextTokenResult->isSetHasNext()){
								$hasNext = $getFeedSubmissionListByNextTokenResult->getHasNext();
							}
							$feedSubmissionInfoList = $getFeedSubmissionListByNextTokenResult->getFeedSubmissionInfoList();
						}

						$this->doFilterSubmissionList($feedSubmissionInfoList);
					}
				}
			}
			
		} catch (MarketplaceWebService_Exception $ex) {
			$this->_errorMessage = $ex->getErrorMessage();
			return false;
		} catch (Exception $e) {
			$this->_errorMessage = 'Call api failure, ' . $e->getMessage();
			return false;
		}
		return true;
	}
	
	/**
	 * @desc 设置feedSubmissionId
	 * @param string $feedSubmissionId
	 */
	public function setFeedSubmissionId($feedSubmissionId) {
		$this->_feedSubmissionId = $feedSubmissionId;
	}
	
	/**
	 * @desc 设置请求类型,以便处理数据
	 * @param string $reqType
	 * @return GetFeedSubmissionListRequest
	 */
	public function setReqType($reqType = null){
		$this->_reqType = $reqType;
		return $this;
	}
	
	/**
	 * @desc 过滤处理得到的submission列表数据
	 * @param unknown $feedSubmissionInfoList
	 */
	private function doFilterSubmissionList($feedSubmissionInfoList){
		$result = array();
		if( count($feedSubmissionInfoList)>0 ){
			foreach($feedSubmissionInfoList as $feedSubmissionInfo){
				$feedSubmissionId = $feedSubmissionInfo->getFeedSubmissionId();
				$feedProcessingStatus = $feedSubmissionInfo->getFeedProcessingStatus();
				$this->response[$feedProcessingStatus][] = $feedSubmissionId;
			}
		}
	}
		
}