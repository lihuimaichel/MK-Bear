<?php
/**
 * Condition Field Model
 * @author	wx
 * @since	2015-07-29
 */

class ConditionsField extends CommonModel {
	
	const ENABLE_ON = 1;	//启用
	const ENABLE_OFF = 0;	//停用
	
	const UNIT_HAVE = 1;
	const UNIT_NO = 0;
	
	const FIELD_TYPE_TEXT = 1; //文本框
	const FIELD_TYPE_LIST = 2; //下拉列表
	
	public $rule_class_cn;
	
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}
	
	public function tableName() {
		return 'ueb_conditions_field';
	}
	
	public function rules() {
		return array(
				array('field_name,field_title,field_type', 'required'),
				array('platform_code,rule_class,unit_code,input_msg_content,field_default_value,validate_type,field_type', 'safe'),
		);
	}
	
	/**
	 * Declares attribute labels.
	 * @return array
	 */
	public function attributeLabels() {
		return array(
				'id'                    	=> Yii::t('system', 'No.'),
				'rule_class'				=> Yii::t('conditions_field', 'Rule Class'),
				'field_name'                => Yii::t('conditions_field', 'Field Name'),
				'field_title'				=> Yii::t('conditions_field', 'Field Title'),
				'is_unit'					=> Yii::t('conditions_field', 'Is Unit'),
				'unit_code'					=> Yii::t('conditions_field', 'Unit Code'),
				'field_default_value'		=> Yii::t('conditions_field', 'Field Default Value'),
				'input_msg_content'			=> Yii::t('conditions_field', 'Input Msg Content'),
				'is_enable'					=> Yii::t('conditions_field', 'Is Enable'),
				'validate_type'				=> Yii::t('conditions_field', 'Validate Type'),
				'create_time'				=> Yii::t('system', 'Create Time'),
				'create_user_id'			=> Yii::t('system', 'Create User'),
				'modify_time'				=> Yii::t('system', 'Modify Time'),
				'modify_user_id'			=> Yii::t('system', 'Modify User'),
				'platform_code'				=> Yii::t('system', 'Platform type'),
				'field_type'				=> Yii::t('conditions_field', 'Field Type')
		);
	}
	
	public function filterOptions() {
		return array(
				array(
	    			'name'          => 'platform_code',
	    			'type'          => 'dropDownList',
	    			'search'        => '=',
	    			'data'          => UebModel::model('Platform')->getPlatformList(),
	    			'htmlOptions'   => array()
    			),
				array(
						'name'          => 'rule_class',
						'type'          => 'dropDownList',
						'search'        => '=',
						'data'          => TemplateRulesBase::getRuleClassList(),
						'htmlOptions'   => array('empty' => '请选择'),
				),
				array(
						'name'          => 'field_title',
						'type'          => 'text',
						'search'        => '=',
				),
				array(
						'name'			=> 'is_enable',
						'type'			=> 'dropDownList',
						'search'		=> '=',
						'value'			=> Yii::app()->request->getParam('is_enable', self::ENABLE_ON),
						'data'          => $this->getUseStatusConfig(),
						'htmlOptions'   => array(),
				),
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
			$data[$key]->rule_class_cn = $value->rule_class ? TemplateRulesBase::getRuleClassList($value->rule_class) : '%';
			$data[$key]->platform_code = $value->platform_code ? $value->platform_code : '%(所有平台)';
		}
		return $data;
	}
	
	public function getByPk($id) {
		$model = $this->findByPk($id);
		return $model->attributes;
	}
	
	/**
	 * 得到规则字段列表
	 * @param	int	$classId , string $platformCode
	 * @return	array
	 */
	public function getRuleFieldListByClass($classId,$platformCode) {
		$list = $this->getDbConnection()->createCommand()
				->select('*')
				->from($this->tableName())
				->where("rule_class = {$classId}")
				->andWhere('platform_code = "'.$platformCode.'"')
				->queryAll();
		return $list;
	}
	
	public function getUseStatusConfig() {
		return array(
				self::ENABLE_ON				=>Yii::t('system',     'Enable'),
				self::ENABLE_OFF			=>Yii::t('system',     'Disable'),
		);
	}
	
	public function getFieldType( $fieldType = '' ) {
		$fieldTypeArr = array(
				self::FIELD_TYPE_TEXT				=>Yii::t('conditions_field',     'Field Type Text'),
				self::FIELD_TYPE_LIST				=>Yii::t('conditions_field',     'Field Type Drop Down List')
		);
		$fieldTypeStr = isset($fieldTypeArr[$fieldType])?$fieldTypeArr[$fieldType]:'';
		return $fieldType !== '' ? $fieldTypeStr : $fieldTypeArr;
	}
	
}