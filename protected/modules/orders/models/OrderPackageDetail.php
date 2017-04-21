<?php
/**
 * @desc 订单包裹详情Model
 * @author Gordon
 */
class OrderPackageDetail extends OrdersModel {

	    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_order_package_detail';
    }
    
    /**
     * @desc 根据订单号得到包裹详情
     * @param string $orderID
     */
    public function getPackageDetailListByOrderID($orderID){
    	return $this->dbConnection->createCommand()
    	->select('A.*')
    	->from(self::tableName().' AS A')
    	->leftJoin(OrderPackage::model()->tableName().' AS B', 'B.package_id = A.package_id')
    	->where('B.ship_status != '.OrderPackage::SHIP_STATUS_CANCEL)
    	->andWhere('A.order_id = "'.$orderID.'"')
    	->queryAll();
    }
    
    /**
     * @desc 根据包裹id号得到包裹详情
     * @param string $packageId
     */
    public function getPackageDetailByPackageId( $packageId,$field='*' ){
    	if(!$packageId) return null;
    	return $this->dbConnection->createCommand()
	    	->select($field)
	    	->from(self::tableName())
	    	->where('package_id="'.$packageId.'"')
	    	->queryAll();
    }
    
    /**
     * @desc 根据订单id号得到包裹id号
     * @param string $order_id
     * @since	2016-02-25
     */
    public function getPackageIdsByOrderId( $order_id ){
        if(!$order_id) return null;
    	return $this->dbConnection->createCommand()
	    	->select('package_id')
	    	->from(self::tableName())
	    	->where('order_id = "' . $order_id . '"')
                ->group('package_id')
	    	->queryColumn();
    }

}