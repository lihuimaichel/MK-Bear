<?php
/**
 * @desc 订单物流上传记录
 * @author Gordon
 */
class OrderPackageTrackLog extends OrdersModel {
	
	CONST STATUS_DEFAULT = 0;	#发起
	CONST STATUS_OK	= 1;		#成功
	CONST STATUS_FAIL = 2;		#失败
	
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_order_package_track_log';
    }
    
    /**
     * @desc 保存信息
     * @param array $param
     */
    public function saveTrackInfo($param){
        $param['timestamp'] = date('Y-m-d H:i:s');
        return $this->dbConnection->createCommand()->replace(self::tableName(), $param);
    }
    
    /**
     * Insert The New Log
     * @param array $data
     * @throws CException
     * @return boolean
     */
    public function addNewData($data){
    	if (!isset($data['package_id']) || !isset($data['ship_code'])) {
    		return false;
    	}
    	$model = new self();
    	$model->package_id 	= $data['package_id'];
    	$model->ship_code 	= $data['ship_code'];
    	$model->status 		= 0;
    	$model->upload_time = date('Y-m-d H:i:s');
    	$model->note		= $data['note'];
    	$model->setIsNewRecord(true);
    	if ($model->save()) {
    		return $model->attributes['id'];
    	}
    	return false;
    }
    
}