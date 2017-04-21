<?php
/**
 * @desc 获取feedsubmission result
 * @author wx
 *
 */
class GetFeedSubmissionResultRequest extends AmazonApiAbstract {
	
	protected $_urlPath = '';		//@var string 请求url path 部分
	protected $_feedSubmissionId = '';					//@var siring feedSubmissionId
	protected $_batchNo = null;							//@val string 请求批次号
	protected $_fp = null;								//@var fieldhandle
	
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
		$request = new MarketplaceWebService_Model_GetFeedSubmissionResultRequest();
		$request->setMerchant($this->_merchantID);
		$request->setFeedSubmissionId($this->_feedSubmissionId);
		$this->_fp = @fopen('php://memory', 'rw+');
		$request->setFeedSubmissionResult($this->_fp);
		$this->_requestEntities = $request;
	}
	
	/**
	 * 调用接口方法
	 * @see AmazonApiAbstract::call()
	 */
	public function call() {
		$result = '';
		try {
			$response = $this->_serviceEntities->getFeedSubmissionResult($this->_requestEntities);
			if (empty($response))
				throw new Exception('Amazom MWS Return Empty');
			
			/* echo '<br/>*************************************************';
			print_r($response);
			echo '<br/>*************************************************'; */
			rewind($this->_fp);
			$amazonReport = stream_get_contents($this->_fp); 
			$logStr = '';
			$doStatus = 0;
			$xml_obj = null;
			if( $response->isSetGetFeedSubmissionResultResult() ){
				$getFeedSubmissionResultResult = $response->getGetFeedSubmissionResultResult();
				if($getFeedSubmissionResultResult->isSetContentMd5()){
					$contentMd5 = $getFeedSubmissionResultResult->getContentMd5();
					if($contentMd5 == base64_encode(md5($amazonReport, true))){
						$xml_obj = simplexml_load_string($amazonReport);
						//echo $amazon_report;
						$results = $xml_obj->Message->ProcessingReport->Result;
						$messagesProcessed = $xml_obj->Message->ProcessingReport->ProcessingSummary->MessagesProcessed;
						$messagesSuccessful = $xml_obj->Message->ProcessingReport->ProcessingSummary->MessagesSuccessful;
						$messagesWithError = $xml_obj->Message->ProcessingReport->ProcessingSummary->MessagesWithError;
						$messagesWithWarning = $xml_obj->Message->ProcessingReport->ProcessingSummary->MessagesWithWarning;
						$logStr .= 'FeedSubmissionId = '.$this->_feedSubmissionId . ' messagesProcessed = '.$messagesProcessed.' messagesSuccessful = '.$messagesSuccessful.' messagesWithError = '.$messagesWithError.' messagesWithWarning = '.$messagesWithWarning .'\r\n';
						
						$errorOrderArr = array();
						if($messagesWithError > 0){
							$doStatus = 2;
							$error_order = '';
							if($results != ''){
								foreach($results as $val){
									if($val->ResultDescription != ''){
										$errorOrderArr[] = trim($val->AdditionalInfo->AmazonOrderID);
										$error_order .= 'AmazonOrderId: '.$val->AdditionalInfo->AmazonOrderID.'   ERROR_DESC: '.$val->ResultDescription.' \r\n';
									}
								}
							}
							$logStr .= '处理失败!需重新submitFeed操作 \r\n'.$error_order;
						}else{
							$doStatus = 1;
						}
						if($this->_batchNo && $messagesSuccessful>0){
							$packageList = UebModel::model('AmazonUploadTnLog')->getPackageIdsByBatchNo( $this->_batchNo,$errorOrderArr );
							if($packageList){
								
								$kdPackageList = UebModel::model('OrderPackage')->getKdPackageList( $packageList ); //获取指定快递物流渠道的暂无真实跟踪号的包裹
								
								$diffPackageIdList = array_diff( $packageList,$kdPackageList );
								
								if( $diffPackageIdList ){
									UebModel::model('OrderPackage')->updateAll(array('is_confirm_shiped'=>1),' is_confirm_shiped=0 and package_id in('.MHelper::simplode($diffPackageIdList).')');
								}
								
							}
						}
					}else{
						$doStatus = 2;
						$logStr .= 'FeedSubmissionId = '.$this->_feedSubmissionId .' amazon contentMd5:'.$contentMd5.' 返回处理报告 MD5验证失败! \r\n';
					}
				}
			}
			//echo '<br/>'.$logStr;
			UebModel::model('AmazonUploadTnError')->saveNewData( array('msg'=>$logStr,'feed_submission_id'=>$this->_feedSubmissionId,'upload_batch_no'=>$this->_batchNo,'type'=>2,'status'=>$doStatus) );
			$this->_errorMessage = $logStr;
			$this->response = $xml_obj;
		} catch (MarketplaceWebService_Exception $ex) {
			$this->_errorMessage = $ex->getErrorMessage();
			UebModel::model('AmazonUploadTnError')->saveNewData( array('msg'=>$ex->getMessage(),'response_code'=>$ex->getStatusCode(),'error_code'=>$ex->getErrorCode(),'error_type'=>$ex->getErrorType(),'request_id'=>$ex->getRequestId(),'feed_submission_id'=>$this->_feedSubmissionId,'upload_batch_no'=>$this->_batchNo,'type'=>1,'error_from'=>'getFeedSubmissionResultRequest','response_xml'=>$ex->getXML(),'response_header_metadata'=>$ex->getResponseHeaderMetadata()) );
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
}