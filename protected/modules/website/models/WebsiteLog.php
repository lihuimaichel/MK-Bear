<?php
/**
 * @网站平台日志模型
 * @author zhangF
 *
 */
class WebsiteLog extends WebsiteModel {
	/**
	 * @desc 运行状态
	 * @var tinyint
	 */
	const STATUS_DEFAULT    = 0;//未开始
	const STATUS_RUNNING    = 1;//运行中
	const STATUS_SUCCESS    = 2;//运行成功
	const STATUS_FAILURE    = 3;//运行失败
	const STATUS_FINISH     = 4;//手动完成
	
	const MAX_RUNNING_TIME  = 3600;//最大运行时间
	
	/**
	 * @desc 获取模型实例
	 * @param system $className
	 * @return Ambigous <CActiveRecord, unknown, multitype:>
	 */
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see CActiveRecord::tableName()
	 */
	public function tableName() {
		return 'ueb_website_log';
	}
	
	/**
	 * @desc 准备日志数据
	 * @param int $accountID
	 * @param string $eventName
	 */
	public function prepareLog($accountID, $eventName){
		$this->setAttributes(array(
				'account_id'    => $accountID,
				'event'         => $eventName,
				'start_time'    => date('Y-m-d H:i:s'),
				'response_time' => date('Y-m-d H:i:s'),
				'create_user_id'=> Yii::app()->user->id ? Yii::app()->user->id : User::admin(),
				'status'        => self::STATUS_DEFAULT,
		),false);
		$this->setIsNewRecord(true);
		$flag = $this->save();
		if( $flag ){
			return $this->dbConnection->getLastInsertID();
		}
		return false;
	}
	
	/**
	 * @desc 标记事件手动改结束
	 * @param int $id
	 */
	public function setFinish($id){
		return $this->updateByPk($id, array(
				'status'    => self::STATUS_FINISH
		));
	}
	
	/**
	 * @desc 标记事件正在运行
	 * @param int $id
	 */
	public function setRunning($id){
		return $this->updateByPk($id, array(
				'status'    => self::STATUS_RUNNING
		));
	}
	
	/**
	 * @desc 标记事件失败
	 * @param int $id
	 */
	public function setFailure($id,$message = ''){
	
		return $this->updateByPk($id, array(
				'status'        => self::STATUS_FAILURE,
				'message'       => $message,
				'response_time' => date('Y-m-d H:i:s'),
				'end_time'      => date('Y-m-d H:i:s'),
				'create_user_id'=> Yii::app()->user->id ? Yii::app()->user->id : User::admin(),
		));
	}
	
	/**
	 * @desc 标记事件成功
	 * @param int $id
	 */
	public function setSuccess($id){
		return $this->updateByPk($id, array(
				'status'        => self::STATUS_SUCCESS,
				'response_time' => date('Y-m-d H:i:s'),
				'end_time'      => date('Y-m-d H:i:s'),
				'create_user_id'=> Yii::app()->user->id ? Yii::app()->user->id : User::admin(),
		));
	}
	
	/**
	 * @desc 检测是否能够运行
	 * @param int $accountID
	 * @param string $eventName
	 */
	public function checkRunning($accountID, $eventName){
		$runningRecord = $this->find('account_id = :account_id AND status = :status AND event = :event',array(
				':account_id'   => $accountID,
				':status'       => self::STATUS_RUNNING,
				':event'        => trim($eventName),
		));
		if( isset($runningRecord->id) && $runningRecord->id > 0 ){
			$diffSec = time() - strtotime($runningRecord->response_time);
			if( $diffSec <= self::MAX_RUNNING_TIME ){
				return false;
			}else{
				$this->setFinish($runningRecord->id);
			}
		}
		return true;
	}
	
	/**
	 * @desc 根据条件获取上一条log记录
	 * @param array $params
	 */
	public function getLastLogByCondition( $params = array() ){
		$whereArr = array();
		foreach( $params as $col=>$param){
			$whereArr[] = $col.' = "'.$param.'"';
		}
		return $this->dbConnection->createCommand()
		->select('*')
		->from(self::tableName())
		->where(implode(' AND ', $whereArr))
		->order('end_time DESC')
		->queryRow();
	}
	
	/**
	 * @desc 存储参数日志
	 * @param string $eventName
	 * @param array $param
	 */
	public function saveEventLog($eventName, $param){
		$tableName = self::tableName().'_'.$eventName;
		$flag = $this->dbConnection->createCommand()->insert($tableName, $param);
		if( $flag ){
			return $this->dbConnection->getLastInsertID();
		}
		return false;
	}
	
	/**
	 * @desc 存储参数日志状态
	 * @param tinyint $status
	 */
	public function saveEventStatus($eventName, $logID, $status){
		$tableName = self::tableName().'_'.$eventName;
		return $this->dbConnection->createCommand()->update($tableName, array('status' => $status), 'id = '.$logID);
	}	
}