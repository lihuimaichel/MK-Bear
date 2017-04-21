<?php
/**
 * 创建线上发货物流订单
 * @author	Rex
 * @since	2015-09-24
 */
class OrderCreateOnline extends OrdersModel {
	
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_order_create_online';
    }
    
    /**
     * @desc 保存信息
     * @param array $param
     */
    public function saveCreateInfo($param){
        return $this->dbConnection->createCommand()->replace(self::tableName(), $param);
    }
    
}