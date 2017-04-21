<?php
/**
 * @desc 订单包裹Model
 * @author wx
 */
class OrderPackageQhPreTrack extends OrdersModel {
	
	//上传跟踪号标记
	const UPLOAD_STATUS_OK = 1;
	const UPLOAD_STATUS_NO = 0;
	const UPLOAD_STATUS_FAIL = -1;//获取顺友跟踪失败
	
	//物流商交运标记
	const UPLOAD_TEACK_STATUS_OK = 1;
	const UPLOAD_TEACK_STATUS_NO = 0;
	
	//平台确认发货标记
	const CONFIRM_SHIPED_STATUS_OK = 1;
	const CONDIRM_SHIPED_STATUS_NO = 0;

    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_order_package_qh_pre_track';
    }
    
}