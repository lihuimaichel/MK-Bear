<?php
if (!defined('DATE_FORMAT')) define('DATE_FORMAT', 'Y-m-d\TH:i:s\Z');
class RequestReportRequest extends AmazonWebServerAbstract {
	protected $_urlPath           = '';				//@var string 请求url path 部分
	protected $_reportType        = null;			//报告类型
	protected $_startDate         = '';				//报告开始时间
	protected $_endDate           = '';				//报告结束时间
	protected $_marketplaceIdList = array();		//商城编号列表
	protected $_reportOptions     = null;			//报告请求可选项 Liz|2016/3/18

	const MAX_REQUEST_TIMES    = 15;	//接口最大请求次数
	const RESUME_RATE_INTERVAL = 60;		//请求恢复间隔 60秒
	const RESUME_RATE_NUM      = 1;				//请求恢复每次1个	
	
	const REPORT_TYPE_LIST_STOCK_DATA         = '_GET_FLAT_FILE_OPEN_LISTINGS_DATA_';		//可售商品报告(库存报告) （包含库存为0）
	const REPORT_TYPE_LISTINGS_DATA_CAMPAT    = '_GET_MERCHANT_LISTINGS_DATA_BACK_COMPAT_';	//可售商品报告
	const REPORT_TYPE_CANCELLED_LISTINGS_DATA = '_GET_MERCHANT_CANCELLED_LISTINGS_DATA_'; //已取消的商品报告
	const REPORT_TYPE_SOLD_LISTINGS_DATA      = '_GET_CONVERGED_FLAT_FILE_SOLD_LISTINGS_DATA_'; //已售商品报告
	const REPORT_TYPE_MERCHANT_LISTINGS_DATA  = '_GET_MERCHANT_LISTINGS_DATA_';//在售商品报告
	const REPORT_TYPE_BROWSE_TREE_DATA        = '_GET_XML_BROWSE_TREE_DATA_';	//Amanzon分类树
	const REPORT_TYPE_AFN_INVENTORY_DATA      = '_GET_AFN_INVENTORY_DATA_';	//FBA库存报告（包含库存为0，不可售等）

	
	public function __construct() {
		$this->_remainTimes = self::MAX_REQUEST_TIMES;
	}	
	
	public function setRequestEntities() {
		$request = new MarketplaceWebService_Model_RequestReportRequest();
		$request->setMerchant($this->_merchantID);
		$request->setReportType($this->_reportType);

		if ($this->_reportOptions) 
			$request->setReportOptions($this->_reportOptions);	//设置请求可选项 Liz|2016/3/18
		if ($this->_startDate)
			$request->setStartDate($this->_startDate);
		if ($this->_endDate)
			$this->setEndDate($this->_endDate);
		if ($this->_marketplaceIdList){
			//设置商城ID(站点) Liz|20160831
			$this->setMarketPlaceIdList($this->_marketplaceIdList);
			$marketPlace['Id'] = $this->_marketplaceIdList;
			$request->setMarketPlaceIdList($marketPlace);
		}

		$this->_requestEntities = $request;
	}
	
	public function call() {
		$result = array();
		try {
			//如果请求次数超过限制，休眠相应的回复间隔时间
			if (!$this->requestAble())
				sleep(self::RESUME_RATE_INTERVAL);
			$response = $this->_serviceEntities->requestReport($this->_requestEntities);
			$this->_remainTimes--;	//统计接口调用次数
			if ($response->isSetRequestReportResult()) {
				$requestReportResult = $response->getRequestReportResult();
				if ($requestReportResult->isSetReportRequestInfo()) {
					$reportRequestInfo = $requestReportResult->getReportRequestInfo();
					if ($reportRequestInfo->isSetReportRequestId())
						$result['reportRequestId'] = $reportRequestInfo->getReportRequestId();
					if ($reportRequestInfo->isSetReportType())
						$result['reportType'] = $reportRequestInfo->getReportType();
					if ($reportRequestInfo->isSetStartDate())
						$result['startDate'] = $reportRequestInfo->getStartDate()->format(DATE_FORMAT);
					if ($reportRequestInfo->isSetEndDate())
						$result['startEnd'] = $reportRequestInfo->getEndDate()->format(DATE_FORMAT);
					if ($reportRequestInfo->isSetSubmittedDate())
						$result['submittedDate'] = $reportRequestInfo->getSubmittedDate()->format(DATE_FORMAT);
					if ($reportRequestInfo->isSetReportProcessingStatus())
						$result['reportProcessingStatus'] = $reportRequestInfo->getReportProcessingStatus();
				}
			}
			$this->response = $result;
			return true;
		} catch (Exception $e) {
			//var_dump($e->getMessage());exit;
			$this->_errorMessage = $e->getMessage();
			return false;
		}
	}
	
	/**
	 * @desc 设置报告类型
	 * @param string $type
	 */
	public function setReportType($type) {
		$this->_reportType = $type;
	}
	
	/**
	 * @desc 设置报告开始时间
	 * @param string $date
	 */
	public function setStartDate($date) {
		$this->_startDate = $date;
	}
	
	/**
	 * @desc 设置报告结束时间
	 * @param unknown $date
	 */
	public function setEndDate($date) {
		$this->_endDate = $date;
	}

	/**
	 * @desc 设置报告可选项	liz|2016/3/18
	 * @param string $options
	 */
	public function setReportOptions($options) {
		$this->_reportOptions = $options;
	}	
	
	/**
	 * 设置商城编号
	 * @param mixed $placeIds
	 */
	public function setMarketPlaceIdList($placeIds) {
		if (is_string($placeIds))
			$this->_marketplaceIdList = array($placeIds);
		else
			$this->_marketplaceIdList = $placeIds;
	}
	
	
}