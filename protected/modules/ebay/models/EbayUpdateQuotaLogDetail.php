<?php
/**
 * @desc Ebay更新配额日志
 * @author Gordon
 * @since 2015-07-25
 */
class EbayUpdateQuotaLogDetail extends EbayModel{
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_ebay_update_quota_log_detail';
    }
    /**
     * @desc 删除before time 之前的日志
     * @param unknown $accountID
     * @param unknown $beforeTime
     * @return Ambigous <number, boolean>
     */
    public function deleteLogInfoBeforeTime($accountID, $beforeTime){
    	return $this->getDbConnection()->createCommand()
    							->delete($this->tableName(), "account_id=:account_id and create_time<:create_time", array(":account_id"=>$accountID, ":create_time"=>$beforeTime));
    								
    }
    
    public function addLogData($data){
    	$data['create_time'] = date("Y-m-d H:i:s");
    	return $this->getDbConnection()->createCommand()->insert($this->tableName(), $data);
    }
}