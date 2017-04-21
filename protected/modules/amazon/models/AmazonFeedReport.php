<?php
class AmazonFeedReport extends AmazonModel {

	const EVENT_NAME = 'submitfeed';
	/**
	 * @desc 是否已取刊登结果
	 * @var unknown
	 */
	const SCHEDULED_SUBMIT = 0;	//未取：已提交
	const SCHEDULED_DONE   = 1;	//未取：平台已处理
	const SCHEDULED_YES    = 2;	//已取

	const MAX_FEED_ID_TOTAL = 100;	//接口提取最大的FeedSubmmissionId数量
	
	/** @var int 账号ID*/
	public $_accountID = null;
	
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
		return 'ueb_amazon_feed_report';
	}
		
	/**
	 * @desc 设置账号ID
	 */
	public function setAccountID($accountID){
		$this->_accountID = $accountID;
	}	

	/**
	 * @desc 设置日志编号
	 * @param int $logID
	 */
	public function setLogID($logID){
		$this->_logID = $logID;
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

	/**
	 * 根据条件获取上传状态数据
	 */
	public function getFeedReportInfoByCondition($where) {
		if (empty($where)) return false;
        return $this->getDbConnection()->createCommand()
					->select('*')
					->from($this->tableName())
					->where($where)
					->limit(1)
                    ->queryRow();
	}	
	
	/**
	 * @desc 新增刊登报告数据
	 * @param array $data
	 * @return int
	 */
	public function addFeedReport($data){
		if(empty($data)) return false;
		$id = 0;
		$ret = $this->getDbConnection()->createCommand()->insert(self::tableName(), $data);
		if($ret) $id = $this->getDbConnection()->getLastInsertID();
		return $id;
	}
	
	/**
	 * @desc 根据自增ID更新数据
	 * @param int $id
	 * @param array $updata
	 * @return boolean
	 */
	public function updateFeedReportByID($id, $updata){
		if(empty($id) || empty($updata)) return false;
		$conditions = "id = ".$id;
		return $this->getDbConnection()->createCommand()->update($this->tableName(), $updata, $conditions);
	}

	/**
	 * @desc 根据条件更新上传报告表
	 * @param string $condition
	 * @param array $updata
	 * @return boolean
	 */
	public function updateFeedReport($conditions, $updata){
		if(empty($conditions) || empty($updata)) return false;
		return $this->getDbConnection()->createCommand()
				    ->update($this->tableName(), $updata, $conditions);
	}	

	/**
	 * @desc 获取执行成功(并未取报告)的列表
	 * @param $accountID
	 * @param string $feedID
	 * @return array()
	 */
	public function getSubmissionIDList($accountID = 0, $feedID = ''){
		if(empty($accountID) || (int)$accountID == 0) return false;
		$feedIDList = array();		
		if (empty($feedID)){
			//从数据库取未处理刊登报告
			$ret = $this->getDbConnection()->createCommand()
					->select('feed_id')
					->from($this->tableName())
					->where('scheduled = '. self::SCHEDULED_SUBMIT)
					->andWhere('account_id = '. $accountID)
					->andWhere('status = 1')
					->order('id asc')
					->limit(self::MAX_FEED_ID_TOTAL)
					->queryAll();
			if ($ret){
				foreach($ret as $val){
					$feedIDList[] = $val['feed_id'];
				}
			}			
		}else{
			$condition = "feed_id = '{$feedID}'";
			$info = $this->getFeedReportInfoByCondition($condition);
			if ($info){
				if ($info['scheduled'] == self::SCHEDULED_SUBMIT && $info['status'] == 1){
					$feedIDList = array($feedID);
				}
			}														
		}
		return $feedIDList;
	}

	/**
	 * @desc 获取上传FeedID列表
	 * @param string $feedID
	 * @param int $max_nums 默认10条，平台最大连接数为15个
	 * @return array()
	 */
	public function getFeedResultList($account_id = 0,$feedID = '', $max_nums = 10){
		$feedIDList = array();		
		if($max_nums > 15) $max_nums = 15;
		if (empty($feedID)){
			$feedIDList = $this->getDbConnection()->createCommand()
								->select('*')
								->from($this->tableName())
								->where('account_id = '. $account_id)
								->andWhere('scheduled = '. self::SCHEDULED_DONE)
								->andWhere('feed_id IS NOT NULL')													
								->order('id asc')
								->limit($max_nums)
								->queryAll();																	
		}else{
			$condition = "feed_id = '{$feedID}'";
			$info = $this->getFeedReportInfoByCondition($condition);
			if($info){
				$feedIDList[] = $info;
			}else{
				//如果不在数据库中，也可以直接查询刊登结果
				$feedIDList[] =	$feedID;
			}
		}
		return $feedIDList;
	}	


	/**
	 * @desc 根据自增IDs批量更新获取刊登结果(scheduled)
	 * @param array $feedIDsArr
	 * @param int $status scheduled状态
	 * @param string $processing_status 处理状态
	 * @return boolean
	 */
	public function updateScheduledByFeedIDs($feedIDsArr = array(), $status = 0, $processing_status){
		if(!$feedIDsArr || !is_array($feedIDsArr)) return false;
		$errmessage = '';
		//如果是失败，则写入错误信息
		if($processing_status == CommonSubmitFeedRequest::FEED_STATUS_CANCELLED) $errmessage = '平台处理结果返回：'.CommonSubmitFeedRequest::FEED_STATUS_CANCELLED;
		foreach ($feedIDsArr as $feedID){
			if (!empty($feedID)){
				$this->getDbConnection()->createCommand()->update(self::tableName(), 
					array(
						'scheduled' => $status, 
						'get_feed_result_date' => date('Y-m-d H:i:s'),
						'feed_result_info' => $errmessage
						), 
					"feed_id = '{$feedID}'");
			}
		}
		return true;
	}



}