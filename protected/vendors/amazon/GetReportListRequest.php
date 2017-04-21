<?php
if (!defined('DATE_FORMAT')) define('DATE_FORMAT', 'Y-m-d\TH:i:s\Z');
class GetReportListRequest extends AmazonWebServerAbstract {
	protected $_urlPath = '';					//请求url path 部分
	protected $_maxCount = 100;					//返回请求报告的最大数量
	protected $_reportTypeList = array();		//请求报告的类型
	protected $_acknowledged = '';				//用来指明在之前调用 UpdateReportAcknowledgements 时是否已确认订单报告,此过滤器仅对订单报告有效
	protected $_availableFromDate = '';			//查找的开始日期
	protected $_availableToDate = '';			//查找的结束日期
	protected $_reportRequestIdList = array();	//报告请求ID列表

	const MAX_REQUEST_TIMES = 6;	//接口最大请求次数
	const RESUME_RATE_INTERVAL = 60;		//请求恢复间隔 60秒
	const RESUME_RATE_NUM = 1;				//请求恢复每次1个	
	
	/**
	 * (non-PHPdoc)
	 * @see AmazonApiAbstract::setRequestEntities()
	 */
	public function setRequestEntities() {
		$request = new MarketplaceWebService_Model_GetReportListRequest();
		$request->setMerchant($this->_merchantID);
		$request->setMaxCount($this->_maxCount);
		if ($this->_reportTypeList)
			$request->setReportTypeList($this->_reportTypeList);
		if ($this->_acknowledged)
			$request->setAcknowledged($this->_acknowledged);
		if ($this->_availableFromDate)
			$request->setAvailableFromDate($this->_availableFromDate);
		if ($this->_availableToDate)
			$request->setAvailableToDate($this->_availableToDate);
		if ($this->_reportRequestIdList)
			$request->setReportRequestIdList($this->_reportRequestIdList);
		$this->_requestEntities = $request;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see AmazonApiAbstract::call()
	 */
	public function call() {
		$return = array();
		try {
			//如果请求次数超过限制，休眠相应的回复间隔时间
			if (!$this->requestAble())
				sleep(self::RESUME_RATE_INTERVAL);			
			$response = $this->_serviceEntities->getReportList($this->_requestEntities);
			$this->_remainTimes--;	//统计接口调用次数
			if ($response->isSetGetReportListResult()) {
				$getReportListResult = $response->getGetReportListResult();
				$reportInfoList = $getReportListResult->getReportInfoList();
				$return['reportList'] = array();
				$key = 0;
				foreach ($reportInfoList as $reportInfo) {
					if ($reportInfo->isSetReportId())
						$return['reportList'][$key]['reportId'] = $reportInfo->getReportId();
					if ($reportInfo->isSetReportType())
						$return['reportList'][$key]['reportType'] = $reportInfo->getReportType();
					if ($reportInfo->isSetReportRequestId())
						$return['reportList'][$key]['reportRequestId'] = $reportInfo->getReportRequestId();
					if ($reportInfo->isSetAvailableDate())
						$return['reportList'][$key]['availableDate'] = $reportInfo->getAvailableDate()->format(DATE_FORMAT);
					if ($reportInfo->isSetAcknowledged())
						$return['reportList'][$key]['acknowledged'] = $reportInfo->getAcknowledged();
					if ($reportInfo->isSetAcknowledgedDate())						
						$return['reportList'][$key]['acknowledgedDate'] = $reportInfo->getAcknowledgedDate()->format(DATE_FORMAT);
					$key++;
				}
				$nextToken = $hasNext = null;
				if ($getReportListResult->isSetNextToken())
					$nextToken = $getReportListResult->getNextToken();
				if ($getReportListResult->isSetHasNext())
					$hasNext = $getReportListResult->getHasNext();
				while($hasNext && $nextToken){
					static $getReportListByNextToken;
					if($getReportListByNextToken == null)
						$getReportListByNextToken = new GetReportListByNextTokenRequest();
					$getReportListByNextToken->setAccount($this->_accountID);
					$getReportListByNextToken->setNextToken($nextToken);
					$nextReportList = $getReportListByNextToken->setRequest()->sendRequest()->getResponse();
					if($nextReportList == null) break;
					$nextReportInfoList = $nextReportList->getReportInfoList();
					foreach ($nextReportList as $reportInfo) {
						if ($reportInfo->isSetReportId())
							$return['reportList'][$key]['reportId'] = $reportInfo->getReportId();
						if ($reportInfo->isSetReportType())
							$return['reportList'][$key]['reportType'] = $reportInfo->getReportType();
						if ($reportInfo->isSetReportRequestId())
							$return['reportList'][$key]['reportRequestId'] = $reportInfo->getReportRequestId();
						if ($reportInfo->isSetAvailableDate())
							$return['reportList'][$key]['availableDate'] = $reportInfo->getAvailableDate()->format(DATE_FORMAT);
						if ($reportInfo->isSetAcknowledged())
							$return['reportList'][$key]['acknowledged'] = $reportInfo->getAcknowledged();
						if ($reportInfo->isSetAcknowledgedDate())
							$return['reportList'][$key]['acknowledgedDate'] = $reportInfo->getAcknowledgedDate()->format(DATE_FORMAT);
						$key++;
					}
					if ($nextReportList->isSetNextToken())
						$nextToken = $nextReportList->getNextToken();
					else $nextToken = null;
					if ($nextReportList->isSetHasNext())
						$hasNext = $nextReportList->getHasNext();
					else $hasNext = null;
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
	 * 设置返回报告列表最大个数
	 * @param integer $count
	 */
	public function setMaxCount($count) {
		$this->_maxCount = $this->_maxCount;
	}
	
	/**
	 * 设置报告类型列表
	 * @param mixed $list
	 */
	public function setReportTypeList($list) {
		if (is_string($list))
			$this->_reportTypeList = array($list);
		else
			$this->_reportTypeList = $list;
	}
	
	/**
	 * 设置AcKnowledged
	 * @param boolen $value
	 */
	public function setAcKnowledged($value) {
		$this->_acknowledged = $value ? true : false;
	}
	
	/**
	 * 设置查找报告的开始日期
	 * @param string $date
	 */
	public function setAvailableFromDate($date) {
		$this->_availableFromDate = $date;
	}
	
	/**
	 * 设置查找报告的结束日期
	 * @param string $date
	 */
	public function setAvailableToDate($date) {
		$this->_availableToDate = $date;
	}
	
	/**
	 * 设置请求报告ID
	 * @param mixed $ids
	 */
	public function setReportRequestIdList($ids) {
		if (!is_array($ids))
			$this->_reportRequestIdList = array($ids);
		else
			$this->_reportRequestIdList = $ids;
	}
}