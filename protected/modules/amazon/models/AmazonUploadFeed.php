<?php
/**
 * @desc Amazon记录需上传跟踪号的feed
 * @since	2015-10-12 wx
 */

class AmazonUploadFeed extends AmazonModel {
	const IS_SYNC_SUCCESS = 1; //同步到中转服务器失败
	const IS_SYNC_FAILURE = 2; //同步到中转服务器成功
	const IS_SYNC_DEFAULT = 0; //默认未同步
	
	const STATUS_SUCCESS = 1; //上传Amazon成功
	const STATUS_FAILURE = 2; //上传Amazon失败
	const STATUS_DEFAULT = 0; //默认未上传
	
	const FEED_TYPE_CONFIRM = 1; //确认发货
	const FEED_TYPE_UPLOAD_TN = 2; //上传跟踪号
	
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}
	
	public function tableName() {
		return 'ueb_amazon_upload_feed';
	}
	
	public function rules() {
		return array(
				array('order_id,amazon_order_id,item_id,package_id,paytime,carrier_code,carrier_name,tracking_number,ship_date,qty,account_id,feed_type,create_time,is_sync,status,sync_status_time','safe')
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
	
	/**
	 * @desc 查询待同步中转服务器的feed
	 * @param $limit
	 * @return array
	 */
	public function getWaitSyncFeed($limit){
		$dbCommand = $this->dbConnection->createCommand()
				->select('*')
				->from(self::tableName())
				->where('is_sync in('.self::IS_SYNC_FAILURE.','.self::IS_SYNC_DEFAULT.')')
				->andWhere('status != '.self::STATUS_SUCCESS);
		if (!empty($limit))
			$dbCommand->limit((int)$limit, 0);
		return $dbCommand->queryAll();
	}
	
	/**
	 * @desc 根据订单id查询已经存在的order_id
	 * @param string $orderId string $itemId stirng $field
	 */
	public function getInfoByOrderId( $orderId,$itemId,$feedType,$field='*' ){
		if( !$orderId || !$itemId || !$feedType ) return null;
		$ret = $this->dbConnection->createCommand()
			->select($field)
			->from(self::tableName())
			->where('order_id in("'.$orderId.'")')
			->andWhere('item_id in("'.$itemId.'")')
			->andWhere('feed_type='.$feedType)
			->queryRow();
		return $ret;
	}
	
	/**
	 * @desc 查询待同步状态的feed
	 * @param $accountId,$limit
	 * @return array
	 */
	public function getWaitSyncStatusFeed($accountId,$limit){
		$dbCommand = $this->dbConnection->createCommand()
			->select('order_id')
			->from(self::tableName())
			->where('status in('.self::STATUS_DEFAULT.')')
			->andWhere('create_time >= "'.date('Y-m-d', strtotime('-10 days')).'"')
			->andWhere('is_sync='.self::IS_SYNC_SUCCESS);
		
		if($accountId){
			$dbCommand->andWhere('account_id='.$accountId);
		}
		if (!empty($limit))
			$dbCommand->limit((int)$limit, 0);
		return $dbCommand->queryColumn();
	}
	
}