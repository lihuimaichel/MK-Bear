<?php
/**
 * @desc 获取feedsubmission result
 * @author liz
 *
 */
class GetCommonFeedSubmissionResultRequest extends GetFeedSubmissionResultRequest {
	
	/**
	 * 调用接口方法
	 * @see AmazonApiAbstract::call()
	 */
	public function call() {
		try {
			$response = $this->_serviceEntities->getFeedSubmissionResult($this->_requestEntities);
			if (empty($response))
				throw new Exception('Amazom MWS Return Empty');
			
			rewind($this->_fp);
			$amazonReport = stream_get_contents($this->_fp); 
			$logStr = '';
			$xml_obj = null;
			if( $response->isSetGetFeedSubmissionResultResult() ){
				$getFeedSubmissionResultResult = $response->getGetFeedSubmissionResultResult();
				if($getFeedSubmissionResultResult->isSetContentMd5()){
					$contentMd5 = $getFeedSubmissionResultResult->getContentMd5();
					if($contentMd5 == base64_encode(md5($amazonReport, true))){
						$xml_obj = simplexml_load_string($amazonReport);
						//echo $amazon_report;
						$results             = $xml_obj->Message->ProcessingReport->Result;
						$messagesProcessed   = $xml_obj->Message->ProcessingReport->ProcessingSummary->MessagesProcessed;
						$messagesSuccessful  = $xml_obj->Message->ProcessingReport->ProcessingSummary->MessagesSuccessful;
						$messagesWithError   = $xml_obj->Message->ProcessingReport->ProcessingSummary->MessagesWithError;
						$messagesWithWarning = $xml_obj->Message->ProcessingReport->ProcessingSummary->MessagesWithWarning;
						$logStr .= 'FeedSubmissionId = '.$this->_feedSubmissionId . ' messagesProcessed = '.$messagesProcessed.' messagesSuccessful = '.$messagesSuccessful.' messagesWithError = '.$messagesWithError.' messagesWithWarning = '.$messagesWithWarning .'\r\n';						
					}else{
						$logStr .= 'FeedSubmissionId = '.$this->_feedSubmissionId .' amazon contentMd5:'.$contentMd5.' 返回处理报告 MD5验证失败! \r\n';
					}
				}
			}
			$this->_errorMessage = $logStr;
			if(!$xml_obj) $xml_obj = $logStr;
			$this->response = $xml_obj;
		} catch (MarketplaceWebService_Exception $ex) {
			$this->_errorMessage = $ex->getErrorMessage();
			return false;
		} catch (Exception $e) {
			$this->_errorMessage = 'Call api failure, ' . $e->getMessage();
			return false;
		}
		return true;
	}
	
}