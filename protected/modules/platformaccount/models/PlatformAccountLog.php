<?php
/**
 * @desc 日志表
 * @author hanxy
 * @since 2017-03-03
 */ 
class PlatformAccountLog extends PlatformAccountModel{
    
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
    
    /** @var wish自动更新token */
    const EVENT_WISH_RUNING_TOKEN = 'wish_autorunaccesstoken';

    /** @var aliexpress自动更新token */
    const EVENT_ALIEXPRESS_RUNING_TOKEN = 'aliexpress_autorunaccesstoken';

    /** @var ebay自动更新token */
    const EVENT_EBAY_RUNING_TOKEN = 'ebay_autorunaccesstoken';

    /** @var joom自动更新token */
    const EVENT_JOOM_RUNING_TOKEN = 'joom_autorunaccesstoken';

    /** @var lazada自动更新token */
    const EVENT_LAZADA_RUNING_TOKEN = 'lazada_autorunaccesstoken';


    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_platform_account_log';
    }

    
    /**
     * @desc 准备日志数据
     * @param int $accountID
     * @param string $eventName
     */
    public function prepareLog($accountID, $eventName, $platformCode){
        $this->setAttributes(array(
                'account_id'    => $accountID,
                'event'         => $eventName,
                'start_time'    => date('Y-m-d H:i:s'),
                'response_time' => date('Y-m-d H:i:s'),
                'create_user_id'=> Yii::app()->user->id ? Yii::app()->user->id : 1,
                'status'        => self::STATUS_DEFAULT,
                'platform_code' => $platformCode
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
            'create_user_id'=> Yii::app()->user->id ? Yii::app()->user->id : 1,
        ));
    }
    
    /**
     * @desc 标记事件成功
     * @param int $id
     */
    public function setSuccess($id, $msg = ''){
        return $this->updateByPk($id, array(
            'status'        => self::STATUS_SUCCESS,
            'response_time' => date('Y-m-d H:i:s'),
            'end_time'      => date('Y-m-d H:i:s'),
            'create_user_id'=> Yii::app()->user->id ? Yii::app()->user->id : 1,
        	'message'		=>	$msg
        ));
    }
    
    /**
     * @desc 检测是否能够运行
     * @param int $accountID
     * @param string $eventName
     * @param string $platformCode
     */
    public function checkRunning($accountID, $eventName, $platformCode){
        $runningRecord = $this->find('account_id = :account_id AND status = :status AND event = :event AND platform_code = :platform_code',array(
                ':account_id'   => $accountID,
                ':status'       => self::STATUS_RUNNING,
                ':event'        => trim($eventName),
                ':platform_code'=> $platformCode,
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
}