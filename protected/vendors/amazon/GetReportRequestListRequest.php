<?php
if (!defined('DATE_FORMAT')) define('DATE_FORMAT', 'Y-m-d\TH:i:s\Z');
class GetReportRequestListRequest extends AmazonWebServerAbstract {
	protected $_urlPath = '';									//请求url path 部分
	protected $_reportRequestIdList =  array();					//报告ID列表
	protected $_reportTypeList	= array();						//报告类型列表
	protected $_reportProcessingStatusList = array();			//报告处理状态列表
	protected $_maxCount = 100;									//最大报告个数
	protected $_requestedFromDate = '';							//报告的起始日期
	protected $_requestedToDate = '';							//报告的结束日期
	
	const MAX_REQUEST_TIMES = 10;	//接口最大请求次数
	const RESUME_RATE_INTERVAL = 45;		//请求恢复间隔 45秒
	const RESUME_RATE_NUM = 1;				//请求恢复每次1个
	const PROCESSING_STATUS_SUBMITTED = '_SUBMITTED_';	//已经提交提交
	const PROCESSING_STATUS_PROCESSING = '_IN_PROGRESS_';	//正在处理
	const PROCESSING_STATUS_CANCELLED = '_CANCELLED_';		//已经取消
	const PROCESSING_STATUS_DONE = '_DONE_';	//已经完成
	const PROCESSING_STATUS_DONE_NOT_DATA = '_DONE_NO_DATA_';	//已经完成但是没有数据
	
	public function setRequestEntities() {
		$request = new MarketplaceWebService_Model_GetReportRequestListRequest();
		$request->setMerchant($this->_merchantID);
		$request->setMaxCount($this->_maxCount);
		if (!empty($this->_reportProcessingStatusList))
			$request->setReportProcessingStatusList($this->_reportProcessingStatusList);
		if (!empty($this->_reportTypeList))
			$request->setReportTypeList($this->_reportTypeList);
		if (!empty($this->_reportRequestIdList))
			$request->setReportRequestIdList($this->_reportRequestIdList);
		if (!empty($this->_requestedFromDate))
			$request->setRequestedFromDate($this->_requestedFromDate);
		if (!empty($this->_requestedToDate))
			$request->setRequestedToDate($this->_requestedToDate);
		$this->_requestEntities = $request;
	}
	
	public function call() {
		$return = array();
		try {
			if (!$this->requestAble())
				sleep(self::RESUME_RATE_INTERVAL);
			ini_set('display_errors', true);
			error_reporting(E_ALL);
			$response = $this->_serviceEntities->getReportRequestList($this->_requestEntities);
			$this->_remainTimes--;	//统计接口调用次数
			if ($response->isSetGetReportRequestListResult()) {
				$getReportRequestListResult = $response->getGetReportRequestListResult();
				if ($getReportRequestListResult->isSetNextToken())
					$return['nextToken'] = $getReportRequestListResult->getNextToken();
				if ($getReportRequestListResult->isSetHasNext())
					$return['hasNext'] = $getReportRequestListResult->getHasNext();
				$reportRequestInfoList = $getReportRequestListResult->getReportRequestInfoList();
				$key = 0;
				$return['requestList'] = array();
				foreach ($reportRequestInfoList as $reportRequestInfo) {
					if ($reportRequestInfo->isSetReportRequestId())
						$return['requestList'][$key]['reportRequestId'] = $reportRequestInfo->getReportRequestId();
					if ($reportRequestInfo->isSetReportType())
						$return['requestList'][$key]['reportType'] = $reportRequestInfo->getReportType();
					if ($reportRequestInfo->isSetStartDate())
						$return['requestList'][$key]['startDate'] = $reportRequestInfo->getStartDate()->format(DATE_FORMAT);
					if ($reportRequestInfo->isSetEndDate())
						$return['requestList'][$key]['endDate'] = $reportRequestInfo->getEndDate()->format(DATE_FORMAT);
					if ($reportRequestInfo->isSetScheduled())
						$return['requestList'][$key]['scheduled'] = $reportRequestInfo->getScheduled();
					if ($reportRequestInfo->isSetSubmittedDate())
						$return['requestList'][$key]['SubmittedDate'] = $reportRequestInfo->getSubmittedDate()->format(DATE_FORMAT);
					if ($reportRequestInfo->isSetReportProcessingStatus())
						$return['requestList'][$key]['reportProcessingStatus'] = $reportRequestInfo->getReportProcessingStatus();
					if ($reportRequestInfo->isSetGeneratedReportId())
						$return['requestList'][$key]['generatedReportId'] = $reportRequestInfo->getGeneratedReportId();
					if ($reportRequestInfo->isSetStartedProcessingDate())
						$return['requestList'][$key]['startedProcessingDate'] = $reportRequestInfo->getStartedProcessingDate()->format(DATE_FORMAT);
					if ($reportRequestInfo->isSetCompletedDate())
						$return['requestList'][$key]['completedDate'] = $reportRequestInfo->getCompletedDate()->format(DATE_FORMAT);
					$key++;
				}
			}
			$this->response = $return;
			return true;
		} catch (Exception $e) {
			$this->_errorMessage = $e->getMessage();
			return false;
		}
	}

	/**
	 * @desc 设置请求ID列表
	 * @param mixed $ids
	 */
	public function setReportRequestIdList($ids) {
		if (is_array($ids))
			$this->_reportRequestIdList = $ids;
		else
			$this->_reportRequestIdList = array($ids);
	}
	
	/**
	 * @desc 设置报告类型列表
	 * @param mixed $types
	 */
	public function setReportTypeList($types) {
		if (is_array($types))
			$this->_reportTypeList = $types;
		else
			$this->_reportTypeList = array($types);
	}
	
	/**
	 * @desc 设置报告请求处理状态列表
	 * @param mixed $status
	 */
	public function setReportProcessingStatusList($status) {
		if (is_array($status))
			$this->_reportProcessingStatusList = $status;
		else
			$this->_reportProcessingStatusList = array($status);
	}

	/**
	 * @desc 设置报告起始日期
	 * @param string $date
	 */
	public function setRequestedFormDate($date) {
		$this->_requestedFromDate = $date;
	}
	
	/**
	 * @desc 设置报告结束日期
	 * @param string $date
	 */
	public function setRequestedToDate($date) {
		$this->_requestedToDate = $date;
	}
}