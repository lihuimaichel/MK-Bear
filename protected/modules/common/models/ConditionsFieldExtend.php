<?php
/**
 * Conditions Field Detail Model
 * @author	wx
 * @since	2015-07-29
 */

class ConditionsFieldExtend extends CommonModel {
	
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}
	
	public function tableName() {
		return 'ueb_conditions_field_extend';
	}
	
	public function rules() {
		return array(
				array('field_id,rule_id,extend_name,extend_value,create_time,create_user_id,modify_time,modify_user_id','safe')
		);
	}
	
	/**
	 * Declares attribute labels.
	 * @return array
	 */
	public function attributeLabels() {
		return array(
				
		);
	}
	
	public function filterOptions() {
		return array(
				//'field_name','create_time','modify_time'
		);
	}
	
	public function search() {
		$sort = new CSort();
		$sort->attributes = array(
				'defaultOrder'  => 'id',
		);
		$criteria = null;
		$dataProvider = parent::search(get_class($this), $sort,array(),$criteria);
		$data = $this->addition($dataProvider->data);
		$dataProvider->setData($data);
		return $dataProvider;
	}
	
	private function addition($data) {
		foreach ($data as $key => $value) {
			//$data[$key]->rule_class_cn = $value->rule_class ? OrderRulesBase::getRuleClassList($value->rule_class) : '%';
		}
		return $data;
	}
	
	public function saveNewData($data) {
		$model = new self();
		foreach($data as $key => $value){
			$model->setAttribute($key,$value);
		}
		$model->create_time = date('Y-m-d H:i:s');
		$model->create_user_id = Yii::app()->user->id;
		$model->setIsNewRecord(true);
		if ($model->save()) {
			return $model->id;
		}
		return false;
	}
	
	public function getFieldExtend( $ruleId,$fieldId ) {
		$list = $this->getDbConnection()->createCommand()
				->select('*')
				->from($this->tableName())
				->where( 'rule_id='.$ruleId )
				->andWhere( 'field_id='.$fieldId )
				->queryAll();
		return $list;
	}
	
}