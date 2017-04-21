<?php
/**
 * @desc aliexpress产品管理复制刊登失败日志
 * @author hanxy
 * @since 2016-10-27
 */ 

class AliexpressLogBatchProductAdd extends AliexpressModel{
	
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}
	
	
	/**
	 * @desc 数据库表名
	 * @see CActiveRecord::tableName()
	 */
	public function tableName() {
		return 'ueb_aliexpress_log_batch_product_add';
	}


	/**
     * @desc 存储日志
     * @param string $eventName
     * @param array $param
     */
    public function savePrepareLog($param){
        $flag = $this->dbConnection->createCommand()->insert(self::tableName(), $param);
        if( $flag ){
            return $this->dbConnection->getLastInsertID();
        }
        return false;
    }
		
}