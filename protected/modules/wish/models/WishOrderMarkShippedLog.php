<?php
/**
 * @desc Wish 记录订单确认发货信息
 * @since	2015-10-12
 */

class WishOrderMarkShippedLog extends WishModel {
	const STATUS_SUCCESS = 1; //上传成功
	const STATUS_FAILURE = 2; //上传失败
	const STATUS_DEFAULT = 0; //默认未上传
	
	const TYPE_FAKE = 1; //上传假tn
	const TYPE_TRUE = 2; //上传真实tn
	
	const UPDATE_STATUS_SUCCESS = 1;//成功
	const UPDATE_STATUS_FAILURE = 2;//失败
	
	
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}
	
	public function tableName() {
		return 'ueb_wish_order_markshipped_log';
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
	
	public function updateData($model,$data) {
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
	public function getWaitingMarkShipOrder($accountId){
		if(!$accountId) return null;
		$payTimeStart = date('Y-m-d H:i',strtotime('-375 hours')); //前15天
		$ret = $this->dbConnection->createCommand()
				->select('id,order_id,platform_order_id')
				->from(self::tableName())
				->where('paytime >= "' .$payTimeStart. '"')
				->andWhere('account_id="'.$accountId.'"')
				->andWhere('type="'.self::TYPE_FAKE.'"')
				->andWhere('status in('.self::STATUS_DEFAULT.','.self::STATUS_FAILURE.')')
				->andWhere('error_type=0')
				->queryAll();
		return $ret;
	}
	
	/**
	 * @desc 根据tracknum查询已经存在的order_id
	 * @param string $trackNum stirng $field
	 */
	public function getInfoByTrackNum( $trackNum = null,$field='*' ){
		if( !$trackNum ) return null;
		$ret = $this->dbConnection->createCommand()
			->select($field)
			->from(self::tableName())
			->where('track_num in("'.$trackNum.'")')
			->queryRow();
		return $ret;
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