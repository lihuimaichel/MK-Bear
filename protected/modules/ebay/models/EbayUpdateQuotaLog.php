<?php
/**
 * @desc Ebay更新配额日志
 * @author Gordon
 * @since 2015-07-25
 */
class EbayUpdateQuotaLog extends EbayModel{
	const LOG_STATUS_ACTIVE = 1;	//抓取中
	const LOG_STATUS_ERROR = 2;		//抓取失败
	const LOG_STATUS_SUCCESS = 3;	//抓取成功
	const LOG_STATUS_MISSING = 4;	//没有开始进行抓取
	
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_ebay_update_quota_log';
    }
    
    /**
     * @desc 获取账号下面最新的日志
     * @param unknown $accountID
     * @return mixed
     */
    public function getLatestLogByAccountID($accountID, $logStatus = ''){
    	$command = $this->getDbConnection()->createCommand()
    							->from($this->tableName())
    							->where("account_id=".$accountID);
    	if($logStatus){
    		$command->andWhere("status=".$logStatus);
    	}
    	$logInfo =	$command->order("update_time DESC")
    							->queryRow();
    	return $logInfo;
    }
    
    
    public function getLatestLogListByLimit($limit = 2, $conditions = "", $param = array()){
    	$logInfo = $this->getDbConnection()->createCommand()
				    	->from($this->tableName())
				    	->where($conditions, $param)
				    	->order("id DESC")
				    	->limit($limit, 0)
				    	->queryAll();
    	return $logInfo;
    }
    
    
    /**
     * @desc 检测运行可行性
     * @param unknown $accountID
     * @param number $hour
     * @param string $logStatus
     * @return boolean
     */
    public function checkAbleRunAtHour($accountID, $hour = 3, $logStatus = ''){
    	$logIno = $this->getLatestLogByAccountID($accountID, $logStatus);
    	if(empty($logIno)) return true;
    	if(time()-strtotime($logIno['update_time']) > $hour*3600){
    		return true;
    	}
    	return false;
    }
    
    public function addDefaultLogData($accountID, $orderLogID){
    	$time = date("Y-m-d H:i:s");
    	$data = array(
    			'account_id' => $accountID,
    			'update_time' => $time,
    			'order_logid' => $orderLogID,
    			'status' => self::LOG_STATUS_MISSING,
    			'response_time' => $time,
    	);
    	return $this->addLogData($data);
    }
    
    public function checkRunning($accountID){
    	$logIno = $this->getLatestLogByAccountID($accountID, self::LOG_STATUS_ACTIVE);
    	if(empty($logIno)) return true;
    	if(time()-strtotime($logIno['response_time']) > 30*60){
    		$this->setLogFailure($logIno['id']);
    		return true;
    	}
    	return false;
    }
    
    public function setLogFailure($logID){
    	return $this->updateByPk($logID, array(
    			'status'		=>	self::LOG_STATUS_ERROR,
    			'response_time'	=>	date("Y-m-d H:i:s")
    		));
    }
    
    public function setLogSuccess($logID){
    	return $this->updateByPk($logID, array(
    			'status'		=>	self::LOG_STATUS_SUCCESS,
    			'response_time'	=>	date("Y-m-d H:i:s")
    	));
    }
    
    public function setLogActive($logID){
    	return $this->updateByPk($logID, array(
    			'status'		=>	self::LOG_STATUS_ACTIVE,
    			'response_time'	=>	date("Y-m-d H:i:s")
    	));
    }
    
    public function refreshLogResponseTime($logID, $otherData = array()){
    	$updateData = array(
    			'response_time'	=>	date("Y-m-d H:i:s")
    	);
    	if($otherData){
    		$updateData = array_merge($updateData, $otherData);
    	}
    	return $this->updateByPk($logID, $updateData);
    }
    
    /**
     * @desc 添加日志数据
     * @param unknown $data
     * @return string|boolean
     */
    public function addLogData($data){
    	$res = $this->getDbConnection()->createCommand()->insert($this->tableName(), $data);
    	if($res){
    		return $this->getDbConnection()->getLastInsertID();
    	}else{
    		return false;
    	}
    }
}