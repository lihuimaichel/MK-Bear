<?php
/**
 * @desc Amazon上传跟踪号异常记录
 * @since	2015-10-12
 */

class AmazonUploadTnError extends AmazonModel {
	const ERROR_TYPE_EXCEPTION = 1; //记录异常信息
	const ERROR_TYPE_RESULT = 2; //记录处理结果
	
	const STATUS_SUCCESS = 1; //处理结果成功
	const STATUS_FAILURE = 2; //处理结果失败
	
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}
	
	public function tableName() {
		return 'ueb_amazon_upload_tn_error';
	}
	
	public function rules() {
		return array(
				//array('amazon_order_id,create_time,batch_no','safe')
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
	 * @desc 根据批次号，获取上传有异常的记录
	 * @param string $batchNo
	 */
	public function getExceptionByBatchNo( $batchNo ){
		if( !$batchNo ) return null;
		$ret = $this->dbConnection->createCommand()
				->select('*')
				->from(self::tableName())
				->where('upload_batch_no="'.$batchNo.'"')
				->andWhere('type='.self::ERROR_TYPE_EXCEPTION)
				->queryAll();
		return $ret;
	}
	
	/**
	 * @desc 根据批次号，获取上传结果为失败的记录
	 * @param string $batchNo
	 */
	public function getFailureByBatchNo( $batchNo ){
		if( !$batchNo ) return null;
		$ret = $this->dbConnection->createCommand()
				->select('*')
				->from(self::tableName())
				->where('upload_batch_no="'.$batchNo.'"')
				->andWhere('type='.self::ERROR_TYPE_RESULT)
				->andWhere('status='.self::STATUS_FAILURE)
				->queryAll();
		return $ret;
	}
	
}