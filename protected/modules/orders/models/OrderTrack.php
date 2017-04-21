<?php
/**
 * @desc 订单物流上传记录
 * @author Gordon
 */
class OrderTrack extends OrdersModel {
	
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_order_track';
    }
    
    /**
     * @desc 保存信息
     * @param array $param
     */
    public function saveTrackInfo($param){
        $param['timestamp'] = date('Y-m-d H:i:s');
        return $this->dbConnection->createCommand()->replace(self::tableName(), $param);
    }
}