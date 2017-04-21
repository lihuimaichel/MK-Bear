<?php
/**
 * Conditions Rules Model
 * @author	wx
 * @since	2015-07-29
 */

class ConditionsRules extends CommonModel {
	
	const ENABLE_ON = 1;	//启用
	const ENABLE_OFF = 0;	//停用
	
	const RETURN_HAVE = 1;
	const RETURN_NO = 0;
	
	public $rule_class_cn;
	public $template_name;
	
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}
	
	public function tableName() {
		return 'ueb_conditions_rules';
	}
	
	public function rules() {
		return array(
				array('rule_name,rule_class,priority,platform_code,template_id', 'required'),
				array('rule_code,is_enable,create_time,create_user_id,modify_time,modify_user_id', 'safe')
		);
	}
	
	/**
	 * Declares attribute labels.
	 * @return array
	 */
	public function attributeLabels() {
		return array(
				'id'                    	=> Yii::t('system', 'No.'),
				'rule_class'				=> Yii::t('conditionsrules', 'Rule Class'),
				'rule_code'                	=> Yii::t('conditionsrules', 'Rule Code'),
				'rule_name'					=> Yii::t('conditionsrules', 'Rule Name'),
				'is_enable'					=> Yii::t('conditionsrules', 'Is Enable'),
				'priority'					=> Yii::t('conditionsrules', 'Priority'),
				'platform_code'				=> Yii::t('conditionsrules', 'Platform Code'),
				'select_key'				=> Yii::t('conditionsrules', 'Select Key'),
				'create_time'				=> Yii::t('system', 'Create Time'),
				'create_user_id'			=> Yii::t('system', 'Create User'),
				'modify_time'				=> Yii::t('system', 'Modify Time'),
				'modify_user_id'			=> Yii::t('system', 'Modify User'),
				'template_name'				=> Yii::t('conditionsrules', 'Template Name')
		);
	}
	
	/**
	 * @return array relational rules.
	 */
	public function relations(){
		return array(
				'detail'   => array(self::HAS_MANY, 'ConditionsDetail',array('rule_id'=>'id')),
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
		
		//$this->addFilterOptions($result);
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
			if( $value->rule_class == TemplateRulesBase::MATCH_PARAM_TEMPLATE ){
				if( $value->platform_code == Platform::CODE_LAZADA ){
					$lazadaParamTemplate = UebModel::model('LazadaParamTemplate')->getParamTemplateByPk( $value->template_id );
					$data[$key]->template_name = $lazadaParamTemplate['tpl_name'];
					//$data[$key]->template_name = $value->template_id;
				}else if( $value->platform_code  == Platform::CODE_EBAY ){
					
				}else if( $value->platform_code  == Platform::CODE_ALIEXPRESS ){
					$aliexpressParamTemplate = UebModel::model('AliexpressParamTemplate')->getParamTemplateByPk( $value->template_id );
					$data[$key]->template_name = $aliexpressParamTemplate['tamplate_name'];
				}else if( $value->platform_code  == Platform::CODE_AMAZON ){
					Yii::app()->end();
				}else if( $value->platform_code  == Platform::CODE_WISH ){
					Yii::app()->end();
				}
			}else if( $value->rule_class == TemplateRulesBase::MATCH_DESCRI_TEMPLATE ){
				$descTemplate = UebModel::model('DescriptionTemplate')->getDescTemplateByPk( $value->template_id );
				$data[$key]->template_name = $descTemplate['template_name'];
			}else if( $value->rule_class == TemplateRulesBase::MATCH_PRICE_TEMPLATE ){
				$priceTemplate = UebModel::model('SalePriceScheme')->getPriceTemplateByPk( $value->template_id );
				$data[$key]->template_name = $priceTemplate['scheme_name'];
			}
		}
		return $data;
	}
	
	public function getUseStatusConfig() {
		return array(
				self::ENABLE_ON				=>Yii::t('system',     'Enable'),
				self::ENABLE_OFF			=>Yii::t('system',     'Disable'),
		);
	}
	
	
	/**
	 * 得到规则列表
	 * @param	int	$classId
	 * @return	array
	 */
	public function getRuleListByClass($classId) {
		$list = $this->getDbConnection()->createCommand()
		->select('*')
		->from($this->tableName())
		->where("rule_class = {$classId}")
		->queryAll();
		return $list;
	}
	
	/**
	 * 得到可匹配规则
	 */
	public function getEnableRuleList($classId, $platform_code, $order='priority') {
		$condition = array('condition'=>"rule_class = {$classId} and is_enable =".self::ENABLE_ON." and detail.is_del = 0");
		($platform_code != '') && $condition['condition'] = $condition['condition']." and (platform_code = '{$platform_code}' or platform_code = '%' ) ";
		$condition['order'] = "{$order} desc";
		return $this->with('detail')->findAll($condition);
	}
	
	public function getRuleListByData($data) {
		$ret = array();
		foreach ($data as $key => $value) {
			$ret[$key] = array('id'=>$value->id,'rule_name'=>$value->rule_name,'platform_code'=>$value->platform_code,'is_enbale'=>$value->is_enable);
		}
		return $ret;
	}
	
	/**
	 * 得到规则列表，仅供日志用
	 */
	public function getRuleListLogByData($data) {
		$ret = array();
		foreach ($data as $key => $value) {
			$ret[$key] = array($value['id'],$value['priority']);
		}
		return $ret;
	}
	
	public function saveOrderRule($data) {
		$model = new self();
		
		foreach ($data as $key => $value) {
			$model->$key = $value;
		}
		$model->platform_code == '' && $model->platform_code = '%';
		$model->create_time = date('Y-m-d H:i:s');
		$model->create_user_id = Yii::app()->user->id;
		$model->setIsNewRecord(true);
		if ($model->save()) {
			return $model->id;
		}
		return false;
	}
	
	/**
	 * get by rule_name
	 * @param	string	$ruleName
	 * @return	array
	 */
	public function getRuleInfoByRuleName($ruleName) {
		$row = $this->getDbConnection()->createCommand()
			->select('*')
			->from($this->tableName())
			->where("rule_name = '{$ruleName}'")
			->queryRow();
		return $row; 
	}
	
	/**
	 * swithUsingRule
	 * @param	array	$ids
	 * @package	bool	$isEnable
	 * @return	bool
	 */
	public function swithUsingRule($ids, $isEnable=true) {
		$arrData = array(
				'is_enable'	=> $isEnable ? self::ENABLE_ON : self::ENABLE_OFF,
		);
		$flag = $this->updateAll($arrData, "id IN (".$ids.")");
		
		//写日志
		$updateLogData = array(
				'type'	=> ConditionsUpdateLog::TYPE_RULE,
				'update_id'	=> 0,
				'update_name' => '批量'.($isEnable ? '启用' : '停用'),
				'update_content' => 'ids: '.$ids,
		);
		UebModel::model('ConditionsUpdateLog')->saveNewData($updateLogData);
		
		return $flag;
	}
	
	/**
	 * get warehouse_type
	 */
	public function getWarehouseTypeArr($type='') {
		$arr = array(
				TemplateRulesBase::WAREHOUSE_LOCAL => '本地仓',
				TemplateRulesBase::WAREHOUSE_OVERSEAS => '海外仓',
		);
		if (key_exists($type, $arr)) {
			return $arr[$type];
		}
		return $arr;
	}
	
	/**
	 * get is_enable
	 */
	public function getIsEnableArr() {
		return array(
				self::ENABLE_OFF => '停用',
				self::ENABLE_ON	=> '启用',
		);
	}
	
	/**
	 * @desc 获取模版名称
	 */
	public function getTemplateName( $platformCode ){
		$ret = '';
	}
	
	public function getTemplateInfo($params, $templateModel) {
		return $templateModel->getTemplateInfo($params);
	}
}