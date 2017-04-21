<?php
/**
 * Conditions Rules Model
 * @author	wx
 * @since	2015-07-29
 */

class ParamTemplate extends CommonModel {
	
	const ENABLE_ON = 1;	//启用
	const ENABLE_OFF = 0;	//停用
	
	const RETURN_HAVE = 1;
	const RETURN_NO = 0;
	
	
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}
	
	public function tableName() {
		return 'ueb_param_template';
	}
	
	public function rules() {
		return array(
				array('temp_name,priority,platform_code,is_enable,dispatch_time', 'required'),
				array('location,country,duration,time_zone', 'safe')
		);
	}
	
	/**
	 * Declares attribute labels.
	 * @return array
	 */
	public function attributeLabels() {
		return array(
				'id'                    	=> Yii::t('system', 'No.'),
				'tpl_code'				=> Yii::t('orderrules', 'Rule Class'),
				'tpl_name'                	=> Yii::t('orderrules', 'Rule Code'),
				'dispatch_time'					=> Yii::t('orderrules', 'Rule Name'),
				'location'			=> Yii::t('orderrules', 'Return Content'),
				'country'					=> Yii::t('orderrules', 'Return Content Exclude'),
				'is_enable'					=> Yii::t('orderrules', 'Is Enable'),
				'duration'					=> Yii::t('orderrules', 'Rule Sort'),
				'time_zone'		=> Yii::t('orderrules', 'Rule Platform Code'),
				'warehouse_type'			=> Yii::t('orderrules', 'Rule Warehouse Type'),
				'platform_code'				=> Yii::t('orderrules', 'Account Id'),
				'priority'				=> Yii::t('orderrules', 'Select Key'),
				'create_time'				=> Yii::t('system', 'Create Time'),
				'create_user_id'			=> Yii::t('system', 'Create User'),
				'modify_time'				=> Yii::t('system', 'Modify Time'),
				'modify_user_id'			=> Yii::t('system', 'Modify User'),
		);
	}
	
	public function scopes() {
		return array(
				
		);
	}
	
	public function filterOptions() {
		$result = array(
				array(
						'name'          => 'id',
						'type'          => 'text',
						'search'        => '=',
						'alias'			=> 't',
						//'htmlOptions'   => array('readonly' => 'true'),
				),
				array(
						'name'          => 'rule_class',
						'type'          => 'dropDownList',
						'search'        => '=',
						'data'          => TemplateRulesBase::getRuleClassList(),
						'htmlOptions'   => array('empty' => '请选择'),
				),
				array(
						'name'          => 'platform_code',
						'type'          => 'dropDownList',
						'search'        => '=',
						'data'          => array_merge(UebModel::model('Platform')->getPlatformList(), array('%'=>'%')),
						'htmlOptions'   => array('empty' => '请选择'),
				),
				array(
						'name'          => 'warehouse_type',
						'type'          => 'dropDownList',
						'search'        => '=',
						'data'          => self::getWarehouseTypeArr(),
						'htmlOptions'   => array('empty' => '请选择'),
				),
				array(
						'name'          => 'rule_name',
						'type'          => 'text',
						'search'        => 'LIKE',
						'alias'			=> 't',
				),
				array(
						'name'			=> 'is_enable',
						'type'			=> 'dropDownList',
						'search'		=> '=',
						'value'			=> Yii::app()->request->getParam('is_enable', self::ENABLE_ON),
						'data'          => $this->getUseStatusConfig(),
						'htmlOptions'   => array(),
				),
				array(
						'name'          => 'select_key',
						'type'          => 'text',
						'search'        => '=',
						'rel'			=> true,
						'htmlOptions'   => array(),
				),
		);
		
		$this->addFilterOptions($result);
		return $result;
	}
	
	/**
	 * add relate table filter conditions
	 * @return array $filterOptions
	 */
	public function addFilterOptions(&$result) {
		$idArr = array();
		$flag = false;
		$id = $_REQUEST['id'];
		$idArr = !empty($id) ? (array)$id : array();
		
		if (!empty($_POST['select_key'])) {
			$flag = true;
			$ruleIds = UebModel::model('ConditionsDetail')->getRuleIdBySelectkey(trim($_POST['select_key']));
			$idArr = !empty($idArr) ? array_intersect($ruleIds,$idArr) : $ruleIds;
		}
		
		if($flag) {
			$_REQUEST['search']['id'] = $idArr;
		}
		
	}
	
	public function search() {
		$sort = new CSort();
		$sort->attributes = array(
				'defaultOrder'  => 'rule_name,priority',
		);
		$criteria = $this->_setCDbCriteria();
		$dataProvider = parent::search(get_class($this), $sort,array(),$criteria);
		$data = $this->addition($dataProvider->data);
		$dataProvider->setData($data);
		return $dataProvider;
	}
	
	protected function _setCDbCriteria(){
// 		$criteria = new CDbCriteria;
// 		$criteria->order = '';
// 		return $criteria;
		return NULL;
	}
	
	private function addition($data) {
		foreach ($data as $key => $value) {
			$data[$key]->rule_class_cn = $value->rule_class ? TemplateRulesBase::getRuleClassList($value->rule_class) : '%';
			$data[$key]->template_name = $value->template_id ? TemplateRulesBase::getRuleClassList($value->rule_class) : '%';
		}
		return $data;
	}
	
	public function getUseStatusConfig() {
		return array(
				self::ENABLE_ON				=>Yii::t('system',     'Enable'),
				self::ENABLE_OFF			=>Yii::t('system',     'Disable'),
		);
	}
	
}