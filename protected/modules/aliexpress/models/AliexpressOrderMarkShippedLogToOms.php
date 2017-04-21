<?php
/**
 * @desc Ali 记录订单确认发货信息数据同步到OMS
 * @since	2016-10-8
 */

class AliexpressOrderMarkShippedLogToOms extends OrdersModel {

	
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
	


}