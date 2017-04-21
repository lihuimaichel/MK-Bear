<?php

class ConditionsRulesMatchLog extends CommonModel {
	
	CONST STATUS_DEFAULT		= 0;#初始
	CONST STATUS_MATCH_START	= 1;#开始匹配
	CONST STATUS_MATCH_OK		= 5;#匹配完成，存在
	CONST STATUS_MATCH_FAIL		= 6;#匹配完成，不存在
	const STATUS_END			= 9;#匹配结束

    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    public function tableName() {
        return 'ueb_rules_match_log';
    }
    
	public function rules() {
		return array(
				array('rule_class,platform_code,start_match_time,search_rule,match_rule,match_template_id,end_match_time,status,create_time,create_user_id,note,input_params','safe')
		);
	}
    
    public function columnName() {
    	return MHelper::getColumnsArrByTableName(self::tableName());
    }
    
    public function search() {
    	
    }
    
    public function addition($data) {
    	
    }
    
    protected function _setCDbCriteria(){
    	
    }
    
    /**
     * filter options
     *
     * @return type
     */
    public function filterOptions(){
    	 
    }
    
    /**
     * Declares attribute labels.
     * @return array
     */
    public function attributeLabels() {
    	return array(
//     			'package_id'               	=> Yii::t('orderpackage', 'Package ID'),
//     			'id' 						=> Yii::t('orderpackage', 'id'),
//     			'status'					=> Yii::t('orderpackage', '状态'),
//     			'upload_time'				=> Yii::t('orderpackage', '上传时间'),
//     			'return_result'				=> Yii::t('orderpackage', '返回结果'),
//     			'ship_code'					=> Yii::t('orderpackage', 'Ship Code'),
//     			'platform_code'				=> Yii::t('orderpackage', '销售平台'),
//     			'upload_ship' 				=> Yii::t('orderpackage', 'Upload Ship'),
//     			'ship_status'				=> Yii::t('orderpackage', 'Ship Status')
    	);
    }
    
    /**
     * Insert The New Log
     * @param array $data
     * @return boolean
     */
    public function addNewData($data){
    	if (!isset($data['platform_code'])) {
    		return false;
    	}
    	$model = new self();
    	foreach ($data as $key => $val) {
    		$model->setAttribute($key, $val);
    	}    	
    	$model->status 		= self::STATUS_DEFAULT;
    	$model->create_time = date('Y-m-d H:i:s');
    	$model->create_user_id = Yii::app()->user->id;
    	$model->setIsNewRecord(true);
    	 
    	if ($model->save()) {
    		return $model->attributes['id'];
    	}
    	return false;
    }
    
}

?>
