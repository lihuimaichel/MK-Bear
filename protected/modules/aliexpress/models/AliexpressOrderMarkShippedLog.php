<?php
/**
 * @desc Ali 记录订单确认发货信息
 * @since	2015-10-12
 */

class AliexpressOrderMarkShippedLog extends AliexpressModel {
	const STATUS_SUCCESS = 1; //上传成功
	const STATUS_FAILURE = 2; //上传失败
	const STATUS_DEFAULT = 0; //默认未上传
	
	const TYPE_DEFAULT = 0; //默认值
	const TYPE_FAKE = 1; //上传假tn
	const TYPE_TRUE = 2; //上传真实tn
	const TYPE_SHAM_SHIP = 3;	//其他单号，有物流信息
	
	const UPDATE_STATUS_SUCCESS = 1;//更新成功
	const UPDATE_STATUS_FAILURE = 2;//更新失败
	
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}
	
	public function tableName() {
		return 'ueb_aliexpress_order_markshipped_log';
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
				->select('id,order_id,platform_order_id,package_id,track_num,carrier_code,ship_code')
				->from(self::tableName())
				->where('paytime >= "' .$payTimeStart. '"')
				->andWhere('account_id="'.$accountId.'"')
				->andWhere('type = 1')
				->andWhere('status in('.self::STATUS_DEFAULT.','.self::STATUS_FAILURE.')')
				->andWhere("track_num !='' and carrier_code != '' ")
				->andWhere('error_type in(0,5,999)')
				->order('upload_time,paytime')
				->limit('2000');
		//echo $ret->text;
		return $ret->queryAll();;
	}

	/**
	 * @desc 获取未匹配出货跟踪号订单
	 */
/*	public function getNotMatchWaitingMarkShipOrder($accountId,$orderId,$limit){
		if(!$accountId) return null;
		$payTimeStart = date('Y-m-d H:i',strtotime('-375 hours')); //前15天
		$obj = $this->dbConnection->createCommand()
				->select('id,order_id,platform_order_id,package_id,track_num,carrier_code')
				->from(self::tableName())
				->where('paytime >= "' .$payTimeStart. '"')
				->andWhere('account_id="'.$accountId.'"')
				->andWhere('type="'.self::TYPE_DEFAULT.'"')
				->andWhere("(track_num ='' and carrier_code = '') or (track_num is null and carrier_code is null) ");
		!empty($orderId) && $obj->andWhere("order_id = '{$orderId}'");
		!empty($limit) && $obj->limit($limit);
		//echo $obj->text;exit;
		return $obj->queryAll();;
	}*/

	/**
	 * 获取上传失败的订单
	 */
	public function getUploadFailureOrder($packageId,$limit=0) {
		$uploadTimeStart = date('Y-m-d H:i:s',strtotime('-3 days'));
		$uploadTimeEnd = date('Y-m-d H:i:s',strtotime('-4 hours'));
		$obj = $this->dbConnection->createCommand()
			->select('id,account_id,order_id,platform_order_id,package_id,track_num,carrier_code,status,error_type,update_error_type')
			->from(self::tableName());
			
		if (!empty($packageId) ) {
			$obj->where("package_id = '{$packageId}'");
		}else {
			$obj->where('status='.self::STATUS_FAILURE." ");
			$obj->andWhere("upload_time >= '{$uploadTimeStart}' and upload_time <= '{$uploadTimeEnd}'");
		}
		!empty($limit) && $obj->limit($limit);
		return $obj->queryAll();
	}

	/**
	 * 同步数据专用方法，其他地方不要调用
	 */
	public function getDataListZy() {
		$crateTimeStart = date('Y-m-d H:i:s',strtotime('-2 days'));
		
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
	
	/**
	 * @desc 获取需要同步到OMS的订单记录(同步500条)
	 */
	public function getOrderLog(){
		$timeStart = date('Y-m-d H:i:s',strtotime('-3 hours')); //前3小时
		$timeEnd   = date('Y-m-d H:i:s',strtotime('-24 hours')); //前24小时
		$ret = $this->dbConnection->createCommand()
		->select('a.*')
		->from(self::tableName().' as a')
		->leftjoin(self::tableName() . ' as b','a.id = b.id')
		//->where("is_to_oms = 0 or (`timestamp` < '{$timeStart}' and `timestamp` > '{$timeEnd}' and `timestamp` != `to_oms_time`)")
		->where("a.is_to_oms = 0 or (a.`timestamp` > b.`to_oms_time` +240)")//防止数据表更新缓慢导致timestamp的时间一直比to_oms_time大
		->order('a.id desc')
		->limit(500);
		//echo $ret->text;die;
		return $ret->queryAll();
	}
	
	/**
	 * @desc 获取需要同步到OMS的订单记录
	 */
	public function getOrderLogAll(){
		$ret = $this->dbConnection->createCommand()
		->select('*')
		->from(self::tableName())
		->where("is_to_oms = 0 ")
		->order('id desc')
		->limit(50000);
		return $ret->queryAll();
	}
	/**
	 * @desc 获取需要同步到OMS的订单记录
	 */
	public function getOrderLogById($id){
		$ret = $this->dbConnection->createCommand()
		->select('*')
		->from(self::tableName())
		->where("id = $id ");
		return $ret->queryAll();
	}
}