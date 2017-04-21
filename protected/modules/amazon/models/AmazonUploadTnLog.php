<?php
/**
 * @desc Amazon上传跟踪号日志记录
 * @since	2015-10-12
 */

class AmazonUploadTnLog extends AmazonModel {
	
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}
	
	public function tableName() {
		return 'ueb_amazon_upload_tn_log';
	}
	
	public function rules() {
		return array(
				array('amazon_order_id,create_time,batch_no,package_id,ship_date,carrier_name,tracking_number','safe')
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
	 * @desc 根据包裹id查询日志信息
	 * @param unknown $packageId
	 */
	public function getInfosByPackageId( $packageId ){
		if( !$packageId ) return null;
		$ret = $this->dbConnection->createCommand()
				->select('*')
				->from(self::tableName())
				->where('package_id="'.$packageId.'"')
				->order('id DESC')
				->queryAll();
		return $ret;
	}
	
	/**
	 * @desc 根据平台订单id查询日志信息
	 * @param string $platformOrderId
	 */
	public function getInfosByPlatformOrderId( $platformOrderId ){
		if( !$platformOrderId ) return null;
		$ret = $this->dbConnection->createCommand()
			->select('*')
			->from(self::tableName())
			->where('amazon_order_id="'.$platformOrderId.'"')
			->andWhere('package_id=""')
			->order('id DESC')
			->queryRow();
		return $ret;
	}
	
	/**
	 * @desc 根据平台订单id查询日志信息
	 * @param string $platformOrderId
	 */
	public function getInfoByPlatformOrderId( $platformOrderId ){
		if( !$platformOrderId ) return null;
		$ret = $this->dbConnection->createCommand()
			->select('*')
			->from(self::tableName())
			->where('amazon_order_id="'.$platformOrderId.'"')
			->order('id DESC')
			->queryRow();
		return $ret;
	}
	
	/**
	 * @desc 根据batchNo查询日志，并且排除amaozn处理错误的订单
	 * @param string $batchNo string $errorAmazonOrderId
	 */
	public function getPackageIdsByBatchNo( $batchNo,$errorAmazonOrderId=array() ){
		if( !$batchNo ) return null;
		$ret = $this->dbConnection->createCommand()
			->select('package_id')
			->from(self::tableName())
			->where('batch_no="'.$batchNo.'"');
		if( $errorAmazonOrderId ){
			$ret->andWhere('amazon_order_id not in('.MHelper::simplode($errorAmazonOrderId).')');
		}
		
		return $ret->queryColumn();
	}
	
}