<?php
/**
 * Condition Field Update log Model
 * @author	wx
 * @since	2015-07-29
 */

class ConditionsUpdateLog extends CommonModel {
	
	const TYPE_FIELD = 'field';
	const TYPE_RULE = 'rule';
	const TYPE_RULE_DETAIL = 'rule_detail';
	
	/**
	 * Returns the static model of the specified AR class.
	 * @return CActiveRecord the static model class
	 */
	public static function model($className=__CLASS__){
		return parent::model($className);
	}
	/**
	 * @param none
	 * @return string the associated database table name
	 */
	public function tableName(){
		return 'ueb_conditions_update_log';
	}
	
	public function columnName() {
		return MHelper::getColumnsArrByTableName(self::tableName());
	}
	
    public function rules() {
        $rules = array(         
        	array('type,update_id','required'),
        	array('update_name,update_content,create_user_id,create_time','safe'),
        );      
        return $rules;
    }
	
	/**
     * get search info
     */
    public function search() {
    	$sort = new CSort();
    	$sort->attributes = array(
    			'defaultOrder'  => 'id',
    			
    	);
    	return parent::search(get_class($this), $sort);
    }
    
	/**
     * Declares attribute labels.
     * @return array
     */
    //定义数据含义
    public function attributeLabels() {
        return array(
        );
    }
    
	//定义数据过滤规则
	public function filterOptions() {
	    return array();
	}
	
	//排序
    public function orderFieldOptions() {
    	return array(
    		
    	);
    }
	
	/**
	 * save
	 * @param array $attr
	 * @return boolean
	 */
	public function saveNewData($attr=array()){
		$model = new self();
		$model->attributes = $attr;
		$model->create_time = date('Y-m-d H:i:s');
		$model->create_user_id = Yii::app()->user->id;
		if ($model->save()){
			return $model->id;
		}else{
			echo $model->errors;
			return false;
		}
	}
	
	public function getUpdateMsg($arr, $type='') {
		$ret = array();
		$typeMsg = ' 修改为：';
		($type == 'N') && $typeMsg = ' 新增为：';
		foreach ($arr as $key => $val) {
			$ret[] = $key.$typeMsg.$val;
		}
		return implode(', ', $ret);
	}
	
}