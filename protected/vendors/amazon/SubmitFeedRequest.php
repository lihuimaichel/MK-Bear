<?php
/**
 * @desc 上传Amazon跟踪号
 * @author wx
 *
 */
class SubmitFeedRequest extends AmazonApiAbstract {
	protected $_urlPath         = '';		//@var string 请求url path 部分
	protected $_feedType        = '_POST_ORDER_FULFILLMENT_DATA_';					//@var siring 订单开始时间
	protected $_feedContent     = null;						//@var string 订单结束时间
	protected $_purgeAndReplace = false;					//@var string 订单状态
	protected $_batchNo         = null;							//@val string 请求批次号
	
	const MAX_REQUEST_TIMES     = 6;	//接口最大请求次数
	const RESUME_RATE_INTERVAL  = 60;		//请求恢复间隔 60秒
	const RESUME_RATE_NUM       = 1;				//请求恢复每次1个
	// =========== START:feedType 常量定义 ============
	const FEEDTYPE_POST_ORDER_FULFILLMENT_DATA      = '_POST_ORDER_FULFILLMENT_DATA_';		//订单配送确认上传数据
	const FEEDTYPE_POST_PRODUCT_DATA                = '_POST_PRODUCT_DATA_';				//商品上传数据
	const FEEDTYPE_POST_INVENTORY_AVAILABILITY_DATA = '_POST_INVENTORY_AVAILABILITY_DATA_';	//库存上传数据
	
	const FEEDTYPE_POST_PRODUCT_PRICING_DATA       	= '_POST_PRODUCT_PRICING_DATA_';		//价格上传数据
	const FEEDTYPE_POST_PRODUCT_IMAGE_DATA          = '_POST_PRODUCT_IMAGE_DATA_';			//图片上传数据
	const FEEDTYPE_POST_PRODUCT_RELATIONSHIP_DATA   = '_POST_PRODUCT_RELATIONSHIP_DATA_';	//关系上传数据
	const FEEDTYPE_POST_PRODUCT_OVERRIDES_DATA   	= '_POST_PRODUCT_OVERRIDES_DATA_';		//运费上传数据
	// more ...
	
	// =========== END: feedType 常量定义 =============
	
	
	// ============ START:=================
	/**
	 * 返回状态值
	 * _AWAITING_ASYNCHRONOUS_REPLY_	请求正在处理，但需要等待外部信息才能完成。
	 * _CANCELLED_	请求因严重错误而中止。
	 * _DONE_	请求已处理。您可以调用 GetFeedSubmissionResult 操作来接收处理报告，该报告列出了上传数据中成功处理的记录以及产生错误的记录。
	 * _IN_PROGRESS_	请求正在处理。
	 * _IN_SAFETY_NET_	请求正在处理，但系统发现上传数据可能包含错误（例如，请求将删除卖家账户中的所有库存）。 亚马逊卖家支持团队将联系卖家，以确认是否应处理该上传数据。
     * _SUBMITTED_	已收到请求，但尚未开始处理。
     * _UNCONFIRMED_ 请求等待中。
     * 
	 * */
	const FEED_STATUS_AWAITING_ASYNCHRONOUS_REPLY = '_AWAITING_ASYNCHRONOUS_REPLY_';
	const FEED_STATUS_CANCELLED = '_CANCELLED_';
	const FEED_STATUS_DONE = '_DONE_';
	const FEED_STATUS_IN_PROGRESS = '_IN_PROGRESS_';
	const FEED_STATUS_IN_SAFETY_NET = '_IN_SAFETY_NET_';
	const FEED_STATUS_SUBMITTED	=	'_SUBMITTED_';
	const FEED_STATUS_UNCONFIRMED = '_UNCONFIRMED_';
	private $_feedProcessingStatus;
	// ============ END:===================
	
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
		$feedHandle = @fopen('php://temp', 'rw+');
		fwrite($feedHandle, $this->_feedContent);
		rewind($feedHandle);
		$parameters = array (
				'Merchant' => $this->_merchantID,
				'MarketplaceIdList' => array('Id' => array($this->_marketPlaceID)),
				'FeedType' => $this->_feedType,
				'FeedContent' => $feedHandle,
				'PurgeAndReplace' => $this->_purgeAndReplace,
				'ContentMd5' => base64_encode(md5(stream_get_contents($feedHandle), true)),
		);
		rewind($feedHandle);
		$request = new MarketplaceWebService_Model_SubmitFeedRequest( $parameters );
		
		$this->_requestEntities = $request;
	}
	
	/**
	 * 调用接口方法
	 * @see AmazonApiAbstract::call()
	 */
	public function call() {
		$result = '';
		try {
			$response = $this->_serviceEntities->submitFeed($this->_requestEntities);
			if (empty($response))
				throw new Exception('Amazom MWS Return Empty');

			$feedProcessingStatus = '';
			if( $response->isSetSubmitFeedResult() ){
				$submitFeedResult = $response->getSubmitFeedResult();
				if($submitFeedResult->isSetFeedSubmissionInfo()){
					$feedSubmissionInfo = $submitFeedResult->getFeedSubmissionInfo();
					if ($feedSubmissionInfo->isSetFeedSubmissionId()){
						$feedSubmissionId = $feedSubmissionInfo->getFeedSubmissionId();
					}
					if($feedSubmissionInfo->isSetFeedProcessingStatus()){
						$feedProcessingStatus = $feedSubmissionInfo->getFeedProcessingStatus();
					}
					$result = $feedSubmissionId;
				}
			}
			$this->_feedProcessingStatus = $feedProcessingStatus;
			$this->response = $result;
			if (empty($result))
				$this->_errorMessage = 'Response FeedSubmissionId is null';
		} catch (MarketplaceWebService_Exception $ex) {
			$this->_errorMessage = $ex->getErrorMessage();
			UebModel::model('AmazonUploadTnError')->saveNewData( array('msg'=>$ex->getMessage(),'response_code'=>$ex->getStatusCode(),'error_code'=>$ex->getErrorCode(),'error_type'=>$ex->getErrorType(),'request_id'=>$ex->getRequestId(),'feed_submission_id'=>$result,'upload_batch_no'=>$this->_batchNo,'type'=>1,'error_from'=>'submitFeedRequest','response_xml'=>$ex->getXML(),'response_header_metadata'=>$ex->getResponseHeaderMetadata()) );
			return false;
		} catch (Exception $e) {
			$this->_errorMessage = 'Call api failure, ' . $e->getMessage();
			return false;
		}
		return true;
	}
	
	/**
	 * @desc 设置feed content
	 * @param string(xml string) $feedContent
	 */
	public function setFeedContent($feedContent) {
		$this->_feedContent = $feedContent;
	}
	
	/**
	 * @desc 设置purgeandrelace
	 * @param bool $purgeAndReplace
	 */
	public function setPurgeAndReplace($purgeAndReplace) {
		$this->_purgeAndReplace = $purgeAndReplace;
	}
	
	/**
	 * @desc 设置batchNo
	 * @param string $batchNo
	 */
	public function setBatchNo($batchNo) {
		$this->_batchNo = $batchNo;
	}
	
	/**
	 * @desc 获取merchantID
	 */
	public function getMerchantID(){
		return $this->_merchantID;
	}
	/**
	 * @desc 设置FeedType
	 * @param string $feedType
	 */
	public function setFeedType($feedType = null){
		if($feedType != null){
			$this->_feedType = $feedType;
		}
		return $this;
	}
	/**
	 * @desc 获取返回的状态值
	 * @return string
	 */
	public function getFeedProcessingStatus(){
		return $this->_feedProcessingStatus;
	}

	public function getErrorMsg(){
		return $this->_errorMessage;
	}	

	
}