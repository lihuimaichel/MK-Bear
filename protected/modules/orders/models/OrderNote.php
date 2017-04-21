<?php
/**
 * @desc Order Note Model
 * @author Gordon
 */
class OrderNote extends OrdersModel {
    
    const ORDER_NOTE_STATUS_YES = 1;
    const ORDER_NOTE_STATUS_NO = 0;
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 表名
     * @see CActiveRecord::tableName()
     */
    public function tableName(){
    	return 'ueb_order_note';
    }
    
    /**
     * @desc 添加note记录
     * @param array $param
     */
    public function addNoteRecord($param){
        return $this->dbConnection->createCommand()->insert(self::tableName(), $param);
    }
}