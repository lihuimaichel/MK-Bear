<?php
/**
 * @desc Ebay确认发货 订单日志记录
 * @since	2015-10-12
 */

class EbayOrderMarkedShippedLog extends EbayModel {
	const STATUS_SUCCESS = 1; //上传成功
	const STATUS_FAILURE = 2; //上传失败
	const STATUS_DEFAULT = 0; //默认未上传
	const STATUS_HAS_GIFT = 3;//有gift包裹，不上传
	
	const TYPE_FAKE = 1; //提前标记发货
	const TYPE_TRUE = 2; //上传真实tn
	
	const UPDATE_STATUS_SUCCESS = 1;//更新成功
	const UPDATE_STATUS_FAILURE = 2;//更新失败
	
	
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}
	
	public function tableName() {
		return 'ueb_ebay_order_markedshipped_log';
	}
	
	public function rules() {
		return array(
				array('account_id,platform_order_id,order_id,package_id,track_num,carrier_code,status,paytime,upload_time,type,create_time,errormsg,update_status,update_time,update_errormsg,error_type,update_error_type','safe')
		);
	}
	
	public function saveNewData($data) {
		$model = new self();
		foreach($data as $key => $value){
			$model->setAttribute($key,$value);
		}
		$model->create_time = date('Y-m-d H:i:s');
		$model->setIsNewRecord(true);
		if ($model->save()) {
			return $model->id;
		}
		return false;
	}
	
	public function updateData($data) {
		$model = new self();
		foreach($data as $key => $value){
			$model->setAttribute($key,$value);
		}
		$model->setIsNewRecord(false);
		if ($model->save()) {
			return true;
		}
		return false;
	}
	
	public function updateData1($model,$data) {
		foreach($data as $key => $value){
			$model->setAttribute($key,$value);
		}
		$model->setIsNewRecord(false);
		if ($model->save()) {
			return true;
		}
		return false;
	}
	
	/**
	 * @desc 根据订单id查询已经存在的order_id
	 * @param string $orderIds stirng $field
	 */
	public function getInfoByOrderIds( $orderIds,$field='*' ){
		if( !$orderIds ) return null;
		$ret = $this->dbConnection->createCommand()
				->select($field)
				->from(self::tableName())
				->where('order_id in('.$orderIds.')')
				->queryAll();
		return $ret;
	}
	
	/**
	 * @desc 获取需要标记发货的订单
	 */
	public function getWaitingMarkShipOrder($accountId,$payTimeStart,$payTimeEnd){
		if(!$accountId) return null;
		$ret = $this->dbConnection->createCommand()
				->select('id,order_id')
				->from(self::tableName())
				->where('paytime >= "' .$payTimeStart. '" and paytime <= "'.$payTimeEnd.'"')
				->andWhere('status in('.self::STATUS_DEFAULT.','.self::STATUS_FAILURE.')')
				->andWhere('account_id='.$accountId)
				->queryAll();
		return $ret;
	}
	
	/**
	 * @desc 获取需要标记发货的订单 [重写]
	 */
	public function getWaitingMarkShipOrder1($accountId){
		if(!$accountId) return null;
		$payTimeStart = gmdate('Y-m-d H:i',strtotime('-168 hours')); //utc时间往前推7天的订单
		$ret = $this->dbConnection->createCommand()
				->select('id,order_id,platform_order_id')
				->from(self::tableName())
				->where('paytime >= "' .$payTimeStart. '"')
				->andWhere('account_id="'.$accountId.'"')
				->andWhere('type="'.self::TYPE_FAKE.'"')
				->andWhere('status in('.self::STATUS_DEFAULT.','.self::STATUS_FAILURE.')')
				->andWhere('error_type in(0,5,999)');
		return $ret->queryAll();
	}
	
	/**
	 * @desc 获取需要标记发货的订单 [辅助程序]
	 */
	public function getWaitingMarkShipOrderAssit($accountId){
		if(!$accountId) return null;
		$payTimeStart = gmdate('Y-m-d 00:00:00',strtotime('-15 days'));   //utc时间往前推15天的订单
		$payTimeEnd = gmdate('Y-m-d 00:00:00',strtotime('-3 days')); 	  //utc时间往前推3天的订单
		$ret = $this->dbConnection->createCommand()
				->select('id,order_id,platform_order_id')
				->from(self::tableName())
				->where('paytime >= "' .$payTimeStart. '"')
				->andWhere('paytime <= "'.$payTimeEnd.'"')
				->andWhere('account_id="'.$accountId.'"')
				->andWhere('type="'.self::TYPE_FAKE.'"')
				->andWhere('status in('.self::STATUS_DEFAULT.','.self::STATUS_FAILURE.')')
				->andWhere('error_type in(0,5,999)');
		
		return $ret->queryAll();
	}
	
	/**
	 * @desc 根据$orderId查询已经存在的记录
	 * @param string $orderId stirng $field
	 */
	public function getInfoRowByOrderId( $orderId,$field='*' ){
		if( !$orderId ) return null;
		$ret = $this->dbConnection->createCommand()
				->select($field)
				->from(self::tableName())
				->where('order_id="'.$orderId.'"')
				->queryRow();
		return $ret;
	}
	
}