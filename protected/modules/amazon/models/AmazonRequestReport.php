<?php
class AmazonRequestReport extends AmazonModel {

	const EVENT_NAME = 'requestreport';
	/**
	 * @desc 是否已取计划
	 * @var unknown
	 */
	const SCHEDULED_YES = 2;//已取
	const SCHEDULED_NO = 1;//未取
	
	/** @var int 账号ID*/
	public $_accountID = null;

	public $_marketplaceId = null;
	
	/** @var string 异常信息*/
	public $exception = null;
	
	/** @var int 日志编号*/
	public $_logID = 0;
	
	/**
	 * @desc 获取模型
	 * @param system $className
	 * @return Ambigous <CActiveRecord, unknown, multitype:>
	 */
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}
	
	/**
	 * @desc 设置数据库表
	 * @see CActiveRecord::tableName()
	 */
	public function tableName() {
		return 'ueb_amazon_request_report';
	}
		
	/**
	 * @desc 设置账号ID
	 */
	public function setAccountID($accountID){
		$this->_accountID = $accountID;
	}

	/**
	 * @desc 设置指定商城ID
	 */
	public function setMarketPlaceId($marketplaceId){
		$this->_marketplaceId = $marketplaceId;
	}	

	/**
	 * @desc 设置日志编号
	 * @param int $logID
	 */
	public function setLogID($logID){
		$this->_logID = $logID;
	}	
	
	public function requestReport($reportType, $reportOptions = null) {
		if (!in_array($reportType, array(RequestReportRequest::REPORT_TYPE_LIST_STOCK_DATA,
				RequestReportRequest::REPORT_TYPE_CANCELLED_LISTINGS_DATA,
				RequestReportRequest::REPORT_TYPE_LISTINGS_DATA_CAMPAT,
				RequestReportRequest::REPORT_TYPE_SOLD_LISTINGS_DATA,
				RequestReportRequest::REPORT_TYPE_MERCHANT_LISTINGS_DATA,
				RequestReportRequest::REPORT_TYPE_MERCHANT_LISTINGS_DATA,
				RequestReportRequest::REPORT_TYPE_BROWSE_TREE_DATA,
				RequestReportRequest::REPORT_TYPE_AFN_INVENTORY_DATA))) {
			$this->setExceptionMessage(Yii::t('amazon', 'Report Type Error'));
			return false;
		}
		$accountID = $this->_accountID;
		try {
			$request = new RequestReportRequest();
			$request->setReportType($reportType);

			if(!empty($this->_marketplaceId)) $request->setMarketPlaceIdList($this->_marketplaceId);	//设置指定商城ID（站点）

			if(isset($reportOptions)) $request->setReportOptions($reportOptions);		//设置可选项

			$response = $request->setAccount($this->_accountID)->setRequest()->sendRequest()->getResponse();
			if (empty($response)) {
				$this->setExceptionMessage(Yii::t('amazon', 'Soap Response Empty'));
				return false;
			}
			$params = array(
				'account_id'               => $this->_accountID,
				'report_request_id'        => $response['reportRequestId'],
				'report_type'              => $response['reportType'],
				'start_date'               => AmazonList::transactionUTCTimeFormat($response['startDate']),
				'end_date'                 => AmazonList::transactionUTCTimeFormat($response['startEnd']),
				'submitted_date'           => AmazonList::transactionUTCTimeFormat($response['submittedDate']),
				'report_processing_status' => $response['reportProcessingStatus']
			);

			//如果请求有$reportOptions参数选项，如果有分类ID，则把分类ID存入请求表，主要针对分类树报告循环请求 Liz|2016/3/25
			if (isset($reportOptions)){
				$options = urldecode($reportOptions);
				if ($options){
					$ret = explode('=',$options);
					if ($ret)
					{
						if($ret[0] == 'BrowseNodeId') $params['report_skus'] = $ret[1];
						if($ret[0] == 'RootNodesOnly') $params['report_skus'] = $ret[0];
					}
				}	
			}
			$this->getDbConnection()->createCommand()->insert(self::tableName(), $params);
			return true;
		} catch (Exception $e) {
			$this->setExceptionMessage($e->getMessage());
			return false;
		}
	}
	
	/**
	 * @desc 获取异常信息
	 * @return string
	 */
	public function getExceptionMessage(){
		return $this->exception;
	}
	
	/**
	 * @desc 设置异常信息
	 * @param string $message
	 */
	public function setExceptionMessage($message){
		$this->exception = $message;
	}	
	
	public function getAccountLastRequest($accountId, $reportType) {
		return $this->getDbConnection()->createCommand()
			->select('*')
			->from(self::model()->tableName())
			->order("submitted_date desc")
			->where("account_id = :id and report_type = :type", array(':id' => $accountId, ':type' => $reportType))
			->limit(1)
			->queryRow();
	}
	
	/**
	 * @desc 获取请求报告列表数据
	 * @param unknown $conditions
	 * @param unknown $params
	 */
	public function getRequestReportList($conditions, $params = array(), $limit = 10){
		return $this->getDbConnection()->createCommand()
					->select('*')
					->from(self::tableName())
					->order("submitted_date asc")
					->where($conditions, $params)
					->limit($limit)
					->queryAll();
	}
	/**
	 * @desc 增加请求报告数据
	 * @param unknown $data
	 * @return boolean|Ambigous <number, boolean>
	 */
	public function addRequestReport($data){
		if(empty($data)) return false;
		return $this->getDbConnection()->createCommand()->insert(self::tableName(), $data);
	}
	
	/**
	 * @desc 根据请求id，report_type, 账号id，更新报表日志数据
	 * @param unknown $where
	 * @param unknown $data
	 * @return boolean|Ambigous <number, boolean>
	 */
	public function batchUpdateRequestReport($reportRequestIds, $reportType, $data){
		if(empty($data) || empty($reportRequestIds) || empty($reportType)) return false;
		return $this->getDbConnection()->createCommand()
									->update(self::tableName(), $data, 
											" report_type='{$reportType}' 
											AND report_request_id in('".implode("','", $reportRequestIds)."')");
	}

	/**
	 * @desc 通过Feedid获取上传消息
	 * @param $feedID 亚马逊平台上传编号
	 * @return array
	 */
	public function getRequestReportInfoByFeedID($feedID){
		if (empty($feedID)) return false;
		return $this->getDbConnection()->createCommand()
					->select('*')
					->from(self::tableName())
					->where('report_request_id=:feedid',array(':feedid'=>$feedID))
					->queryRow();


	}
}