<?php
/**
 * @desc Ebay确认发货 订单日志记录
 * @since	2015-10-12
 */

class EbayOrderMarkShippedLog extends EbayModel {
	const STATUS_SUCCESS = 1; //上传成功
	const STATUS_FAILURE = 2; //上传失败
	const STATUS_DEFAULT = 0; //默认未上传
	const STATUS_HAS_GIFT = 3;//有gift包裹，不上传
	
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}
	
	public function tableName() {
		return 'ueb_ebay_order_markshipped_log';
	}
	
	public function rules() {
		return array(
				array('status,order_id,errormsg,paytime,create_time,upload_time,account_id','safe')
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
}