<?php
/**
 * @desc amazon产品下线日志
 * @author hanxy
 * @since 2016-11-02
 */ 

class AmazonLogOffline extends AmazonModel{
		
	/**
	 * @desc 运行状态
	 * @var tinyint
	 */
	const STATUS_SUCCESS    = 1;//成功
	const STATUS_FAILURE    = 0;//失败
	
	const MAX_RUNNING_TIME  = 3600;//最大运行时间
	
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}
	
	/**
	 * @desc 数据库表名
	 * @see CActiveRecord::tableName()
	 */
	public function tableName() {
		return 'ueb_amazon_log_offline';
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
}