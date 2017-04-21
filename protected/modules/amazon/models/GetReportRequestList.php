<?php
class GetReportRequestList extends UebModel {
	const EVENT_NAME = 'getreportrequestlist';
	
	/** @var int 账号ID*/
	public $_accountID = null;
	
	/** @var string 异常信息*/
	public $exception = null;
	
	/** @var int 日志编号*/
	public $_logID = 0;	
	
	public function getReportIdByRequestId($requestId) {
		$reportId = 0;
		$logID = AmazonLog::model()->prepareLog($this->_accountID,self::EVENT_NAME);
		if( $logID ){
			//1.检查账号是否可以提交请求报告
			$checkRunning = AmazonLog::model()->checkRunning($this->_accountID, self::EVENT_NAME);
			if( !$checkRunning ){
				AmazonLog::model()->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
				return false;
			}else{
				//插入本次log参数日志(用来记录请求的参数)
				$eventLog = AmazonLog::model()->saveEventLog(self::EVENT_NAME, array(
						'log_id'        => $logID,
						'account_id'    => $this->_accountID,
						'start_time'    => date('Y-m-d H:i:s'),
						'end_time'      => date('Y-m-d H:i:s'),
				));
				//设置日志为正在运行
				AmazonLog::model()->setRunning($logID);
				$request = new GetReportRequestListRequest();
				$request->setReportRequestIdList($requestId);
				$requestListResponses  = $request->setAccount($this->_accountID)->setRequest()->sendRequest()->getResponse();
				if (!$request->getIfSuccess()) {
					AmazonLog::model()->setFailure($logID, $request->getErrorMsg());
					AmazonLog::model()->saveEventStatus(self::EVENT_NAME, $eventLog, AmazonLog::STATUS_FAILURE);					
					return false;
				} else {
					if (isset($requestListResponses['requestList'])) {
						$reportListResponse = $requestListResponses['requestList'][0];
						$reportRequestId = $reportListResponse['reportRequestId'];	//报告请求ID
						$reportRequestStatus = $reportListResponse['reportProcessingStatus'];
						if (isset($reportListResponse['reportId']))
							$reportId = $reportListResponse['reportId'];
						UebModel::model('AmazonRequestReport')->updateAll(array(
							'report_processing_status' => $reportRequestStatus,
							'report_id' => $reportId
						), "report_request_id = " . $reportRequestId);
					}
					AmazonLog::model()->setSuccess($logID);
					AmazonLog::model()->saveEventStatus(self::EVENT_NAME, $eventLog, AmazonLog::STATUS_SUCCESS);
					return $reportId;
				}			
			}
		}
		return false;		
	}
}