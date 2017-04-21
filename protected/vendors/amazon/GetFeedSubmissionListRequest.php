<?php
/**
 * @desc 上传Amazon跟踪号
 * @author wx
 *
 */
class GetFeedSubmissionListRequest extends AmazonApiAbstract {
	
	protected $_urlPath = '';		//@var string 请求url path 部分
	protected $_feedSubmissionId = '';					//@var siring feedSubmissionId
	protected $_batchNo = null;							//@val string 请求批次号
	/**
	 * @desc 请求类型
	 * @var unknown
	 */
	protected $_reqType = null;
	const REQ_TYPE_ONLY_FEED_SUBMISSION_LIST = 'only_feed_submission_list';//只获取feed submission的列表
	
	const ORDER_STATUS_PENDING 				= 'Pending';	//订单已生成，但是付款未授权
	const ORDER_STATUS_PENDING_AVAIABILITY 	= 'pendingAvailability';	//只有预订订单才有此状态, 订单已生成，但是付款未授权
	const ORDER_STATUS_UNSHIPPED 			= 'Unshipped';	//付款已经过授权，等待发货
	const ORDER_STATUS_PARTIALLY_SHIPPED 	= 'PartiallyShipped';	//已经部分发货
	const ORDER_STATUS_SHIPPED 				= 'Shipped';	//已经发货
	const ORDER_STATUS_INVOCIE_UNCONFIRMED 	= 'InvoiceUnconfirmed';	//未确认已经向买家提供发票
	const ORDER_STATUS_CANCELED 			= 'Canceled';	//已经取消的订单
	const ORDER_STATUS_UNFULFILLABLE 		= 'Unfulfillable';	//无法进行配送的订单
	
	const ORDER_FULLFILLMENTCHANNEL_FBA		= 'FBA';	//FBA仓库发货
	const ORDER_FULLFILLMENTCHANNEL_MBA		= 'MBA';	//自己仓库发货
	
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
				if($this->_reqType == self::REQ_TYPE_ONLY_FEED_SUBMISSION_LIST){
					$this->doFilterSubmissionList($feedSubmissionInfoList);
				}else{
					$retArray[] = $this->doFilter( $feedSubmissionInfoList );
				}
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
						if($this->_reqType == self::REQ_TYPE_ONLY_FEED_SUBMISSION_LIST){
							$this->doFilterSubmissionList($feedSubmissionInfoList);
						}else{
							$retArray[] = $this->doFilter( $feedSubmissionInfoList );
						}
					}
				}
			}
			if($this->_reqType != self::REQ_TYPE_ONLY_FEED_SUBMISSION_LIST){
				$this->response = $retArray;
			}
			
		} catch (MarketplaceWebService_Exception $ex) {
			$this->_errorMessage = $ex->getErrorMessage();
			UebModel::model('AmazonUploadTnError')->saveNewData( array('msg'=>$ex->getMessage(),'response_code'=>$ex->getStatusCode(),'error_code'=>$ex->getErrorCode(),'error_type'=>$ex->getErrorType(),'request_id'=>$ex->getRequestId(),'feed_submission_id'=>$this->_feedSubmissionId,'upload_batch_no'=>$this->_batchNo,'type'=>1,'error_from'=>'getFeedSubmissionListRequest','response_xml'=>$ex->getXML(),'response_header_metadata'=>$ex->getResponseHeaderMetadata()) );
			return false;
		} catch (Exception $e) {
			$this->_errorMessage = 'Call api failure, ' . $e->getMessage();
			return false;
		}
		return true;
	}
	
	/**
	 * @desc 设置batchNo
	 * @param string $batchNo
	 */
	public function setBatchNo($batchNo) {
		$this->_batchNo = $batchNo;
	}
	
	/**
	 * @desc 设置feedSubmissionId
	 * @param string $feedSubmissionId
	 */
	public function setFeedSubmissionId($feedSubmissionId) {
		$this->_feedSubmissionId = $feedSubmissionId;
	}
	
	private function doFilter($feedSubmissionInfoList){
		$tmp_arr1 = array(); //未处理的 feed
		$tmp_arr2 = array(); //处理完成的 feed
		if( count($feedSubmissionInfoList)>0 ){
			foreach($feedSubmissionInfoList as $feedSubmissionInfo){
				$feedSubmissionId = $feedSubmissionInfo->getFeedSubmissionId();
				$feedProcessingStatus = $feedSubmissionInfo->getFeedProcessingStatus();
				if($feedProcessingStatus == '_DONE_'){ //处理完成
					$tmp_arr2[] = $feedSubmissionId;
				}else{ //未处理完成的
					$tmp_arr1[] = $feedSubmissionId;
				}
			}
		}
		sleep(10);
		if(count($tmp_arr2)>0){
			$request = new GetFeedSubmissionResultRequest();
			$request->setBatchNo($this->_batchNo);
			foreach($tmp_arr2 as $val){
				$request->setFeedSubmissionId($val);
				//获取feedSubmissionId
				$response = $request->setAccount($this->_accountID)->setRequest()->sendRequest()->getResponse();
			}
		}
		return $tmp_arr1;
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