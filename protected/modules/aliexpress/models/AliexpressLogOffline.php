<?php
/**
 * @desc Aliexpress产品下线日志
 * @author Gordon
 * @since 2015-09-21
 */ 

class AliexpressLogOffline extends AliexpressModel{
	
	const EVENT_NAME_VARIATION = 'product_offline_Variation';
	const EVENT_NAME_NOT_VARIATION = 'product_offline_not_variation';
	
	/**
	 * @desc 运行状态
	 * @var tinyint
	 */
	const STATUS_PREPARE	= 1;//准备运行
	const STATUS_SUCCESS    = 2;//运行成功
	const STATUS_FAILURE    = 3;//运行失败
	
	const MAX_RUNNING_TIME  = 3600;//最大运行时间
	
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}
	
	/**
	 * @desc 数据库表名
	 * @see CActiveRecord::tableName()
	 */
	public function tableName() {
		return 'ueb_aliexpress_log_offline';
	}
	
	/**
	 * @desc 准备日志数据
	 * @param int $accountID
	 * @param string $eventName
	 */
	public function prepareLog($productID,$sku,$eventName){
		$this->setAttributes(array(
				'product_id'    => $productID,
				'sku'			=> $sku,
				'event'         => $eventName,
				'start_time'    => date('Y-m-d H:i:s'),
				'response_time' => date('Y-m-d H:i:s'),
				'create_user_id'=> Yii::app()->user->id ? Yii::app()->user->id : User::admin(),
				'status'        => self::STATUS_PREPARE,
		),false);
		$this->setIsNewRecord(true);
		$flag = $this->save();
		if( $flag ){
			return $this->dbConnection->getLastInsertID();
		}
		return false;
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
     * @desc 存储日志
     * @param string $eventName
     * @param array $param
     */
    public function savePrepareLog($param){
    	$result = false;
        $flag = $this->dbConnection->createCommand()->insert(self::tableName(), $param);
        if( $flag ){
            $result = $this->dbConnection->getLastInsertID();
        }
        return $result;
    }


    /**
	 * [getListByCondition description]
	 * @param  string $fields [description]
	 * @param  string $where  [description]
	 * @param  mixed $order  [description]
	 * @return [type]         [description]
	 * @author yangsh
	 */
	public function getListByCondition($fields='*', $where='1',$order='',$group='') {
		$cmd = $this->dbConnection->createCommand();
		$cmd->select($fields)
			->from(self::tableName())
			->where($where);
		$group != '' && $cmd->group($group);
		$order != '' && $cmd->order($order);
		return $cmd->queryAll();
	}	
}