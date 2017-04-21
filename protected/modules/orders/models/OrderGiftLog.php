<?php
class OrderGiftLog extends OrdersModel {
	
	const STATUS_NOT = 0;//未处理
	const STATUS_YES = 1;//已处理
	public $STATUS_ARR =array();
	
	/**
	 * Returns the static model of the specified AR class.
	 * @return CActiveRecord the static model class
	 */
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}
	
	/**
	 * @return string the associated database table name
	 */
	public function tableName() {
		return 'ueb_order_gift_log';
	}
	
	/**
	 *
	 * @param string $val
	 * 
	 */
	public function getStatus($val=NULL){
		$this->STATUS_ARR = array(
				self::STATUS_NOT => "未处理",
				self::STATUS_YES => "已处理",
		);
		if($val !== NULL){
			return $this->STATUS_ARR[$val] ? $this->STATUS_ARR[$val] : '-';
		}else{
			return $this->STATUS_ARR;
		}
	}
	
	/**
	 * @desc 根据orderid查看该订单是否有分配过gift
	 */
	public function checkGiftIsOrNot( $orderId ){
		if( $orderId ){
			$giftInfo = $this->getDbConnection()->createCommand()
						->select( 'order_id,sku' )
						->from( self::tableName() )
						->where( "order_id = '{$orderId}'" )
						->queryRow();
						
		}else{
			$giftInfo = '';
		}
		return $giftInfo;
	}
	
}