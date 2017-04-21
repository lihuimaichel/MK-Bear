<?php
/**
 * @desc 订单发货日期
 * @author lihy
 */
class OrderShipDate extends OrdersModel {
	
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_order_ship_date';
    }
    /**
     * @desc 添加某个订单id的发货时间数据
     * @param unknown $orderId
     * @param unknown $data
     * @return boolean|Ambigous <number, boolean>
     */
   	public function addData($orderId, $data){
   		//检测是否已经添加过该订单，已经存在则不添加了
   		$checkExists = $this->getOrderShipDateInfoByOrderId($orderId);
   		if($checkExists) return true;
   		$data['order_id'] = $orderId;
   		return $this->getDbConnection()->createCommand()->insert(self::tableName(), $data);
   	}
   	/**
   	 * @desc 根据订单id获取单个信息
   	 * @param unknown $orderId
   	 * @return mixed
   	 */
   	public function getOrderShipDateInfoByOrderId($orderId){
   		$shipDateInfo = $this->dbConnection->createCommand()
					   		->from(self::tableName())
					   		->where("order_id=:order_id", array(":order_id"=>$orderId))
					   		->queryRow();
   		return $shipDateInfo;
   	}
}