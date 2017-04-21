<?php
/**
 * @desc 订单详情扩展表Model
 * @author Gordon
 */
class OrderDetailExtend extends OrdersModel {
	
	
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_order_detail_extend';
    }
        
    /**
     * @desc 添加订单详情记录
     * @param unknown $params
     */
    public function addOrderDetailExtend($params){
        $res = $this->dbConnection->createCommand()->insert(self::tableName(), $params);
        if($res){
        	return $this->dbConnection->getLastInsertID();
        }
        return false;
    }
    
}