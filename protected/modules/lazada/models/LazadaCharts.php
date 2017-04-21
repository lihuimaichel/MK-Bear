<?php
/**
 * @desc Lazada图表
 * @author Gordon
 * @since 2015-08-13
 */
class LazadaCharts extends LazadaModel{
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @return array validation rules for model attributes.
     */
    public function rules(){}
    
    /**
     * @desc 切换数据库连接
     * @see LazadaModel::getDbKey()
     */
    public function getDbKey() {
        return 'db_oms_order';
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_order';
    }
    
    /**
     * @desc 获取lazada每日订单数
     */
    public function getOrderCount(){
        $startTime = date('Y-m-01 00:00:00');
        $endTime = date('Y-m-d H:i:s', strtotime('+1 months', strtotime($startTime)));
        $data = $this->dbConnection->createCommand()
                ->select('DATE_FORMAT(paytime, "%Y-%m-%d") AS date,platform_code,COUNT(order_id) AS count')
                ->from(self::tableName())
                ->where('paytime >= "'.$startTime.'" AND paytime < "'.$endTime.'"')
                ->andWhere('payment_status = 1')
//                 ->andWhere('platform_code = "'.Platform::CODE_LAZADA.'"')
                ->group('DATE_FORMAT(paytime, "%Y-%m-%d"),platform_code')
                ->queryAll();
        return $data;
    }
}