<?php
/**
 * @desc 卖价方案模型
 * @author zhangF
 *
 */
class SalePriceScheme extends CommonModel {
	
	const STATUS_OPEN = 1;						//状态-开启
	const STATUS_CLOSED = 0;					//状态-关闭
	const PROFIT_SYNC_TO_SALE_PRICE = 1;		//利润同步到卖价
	const PROFIT_SYNC_TO_SHIPPING_PRICE = 2;	//利润同步到运费
	
	/**
	 * 
	 * @param system $className
	 * @return Ambigous <CActiveRecord, unknown, multitype:>
	 */
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}
	
	/**
	 * @desc 获取表名
	 * @see CActiveRecord::tableName()
	 */
	public function tableName() {
		return 'ueb_sale_price_scheme';	
	}
	
	/**
	 * @desc 字段验证规则
	 * @see CModel::rules()
	 */
	public function rules() {
		return array(
			array('standard_profit_rate, lowest_profit_rate, floating_profit_rate, profit_calculate_type, platform_code', 'required'),
			array('standard_profit_rate, lowest_profit_rate, floating_profit_rate, profit_calculate_type', 'numerical'),
			array('scheme_name', 'safe'),
		);
	}
	
	/**
	 * @desc 过滤设置
	 * @return multitype:
	 */
	function filterOptions() {
		return array(
			array(
				'name' => 'platform_code',
				'type' => 'dropDownList',
				'data' => CHtml::listData(UebModel::model('Platform')->findAll(), 'platform_code', 'platform_name'),
				'search' => '=',
			),
		);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see UebModel::search()
	 */
	public function search() {
		$sort = new CSort();
		$sort->attributes = array(
			'defaultOrder' => 'create_time'
		);
		$dbCriteria = $this->_setCDbCriteria();
		$dataProvider = parent::search(get_class($this), $sort);
		return $dataProvider;
	}
	
	/**
	 * 属性标签翻译
	 * @see CModel::attributeLabels()
	 */
	public function attributeLabels() {
		return array(
			'id' => Yii::t('system', 'No.'),
			'scheme_name' => Yii::t('sale_price_scheme', 'Scheme Name'),
			'standard_profit_rate' => Yii::t('sale_price_scheme', 'Standard Profit Rate'),
			'lowest_profit_rate' => Yii::t('sale_price_scheme', 'Lowest Profit Rate'),
			'floating_profit_rate' => Yii::t('sale_price_scheme', 'Floating Profit Rate'),
			'status' => Yii::t('system', 'Status'),
			'create_user_id' => Yii::t('system', 'Create User'),
			'create_time' => Yii::t('system', 'Create Time'),
			'modify_user_id' => Yii::t('system', 'Modify User'),
			'modify_time' => Yii::t('system', 'Modify Time'),
			'profit_calculate_type' => Yii::t('sale_price_scheme', 'Profit Calculate Type'),
			'platform_code' => Yii::t('system', 'Platform'),
		);
	}
	
	/**
	 * 设置查询条件
	 * @return CDbCriteria
	 */
	private function _setCDbCriteria(){
		$criteria = new CDbCriteria();
		return $criteria;
	}
	
	/**
	 * @desc 获得状态listing
	 * @param string $key
	 * @return Ambigous <string, Ambigous <string, string, unknown>>|multitype:string Ambigous <string, string, unknown>
	 */
	public function getStatusList($key = null) {
		$statusList = array(
				self::STATUS_OPEN => Yii::t('description_template', 'Status Normal'),
				self::STATUS_CLOSED => Yii::t('description_template', 'Status Invalid'),
		);
		if (!is_null($key) && array_key_exists($key, $statusList))
			return $statusList[$key];
		return $statusList;
	}

	/**
	 * @desc 获取菜单对应ID
	 * @return integer
	 */
	public static function getIndexNavTabId() {
		return Menu::getIdByUrl('/common/salepricescheme/list');
	}
	
	/**
	 * @desc 获取利润计算的方式 
	 * @param string $key
	 * @return Ambigous <NULL, Ambigous <string, string, unknown>>|multitype:NULL Ambigous <string, string, unknown>
	 */
	static function getProfitCalculateTypeList($key = null) {
		$list = array(
			self::PROFIT_SYNC_TO_SALE_PRICE => Yii::t('sale_price_scheme', 'Profit Sync to Sale Price'),
			self::PROFIT_SYNC_TO_SHIPPING_PRICE => Yii::t('sale_price_scheme', 'Profit Sync to Shipping Price'),
		);
		if (!is_null($key) && array_key_exists($key, $list))
			return $list[$key];
		return $list;
	}
	
	/**
	 * @desc 根据模板id查找模板信息
	 * @param unknown $id
	 */
	public function getParamTplById($id) {
		$id = (array) $id;
		return $this->getDbConnection()
				->createCommand()
				->select('id,scheme_name as tpl_name')
				->from(self::tableName())
				->where(array('IN', 'id', $id))
				->andWhere('status='.self::STATUS_OPEN)
				->queryAll();
	}
	
	public function getPriceTemplateByPk( $id ){
		$retObj = $this->findByPk($id,'status = '.self::STATUS_OPEN);
		return $retObj->attributes;
	}
	
	/**
	 * @desc 根据ID获取卖价方案模板
	 * @param unknown $id
	 * @return mixed
	 */
	function getSalePriceSchemeByID($id) {
		return $this->getDbConnection()->createCommand()
			->from(self::tableName())
			->where("id = :id", array(':id' => $id))
			->queryRow();
	}
	
	/**
	 * @desc 获取最卖价模板
	 * @param unknown $params
	 * @return unknown|boolean
	 */
	public function getTemplateInfo($params = array()) {
		$ruleModel = new ConditionsRulesMatch();
		$ruleModel->setRuleClass(TemplateRulesBase::MATCH_PRICE_TEMPLATE);
		$templateID = $ruleModel->runMatch($params);
		if (empty($templateID) || !($templateInfo = $this->getSalePriceSchemeByID($templateID))) {
			return $templateInfo;
		}
		return false;
	}


	/**
	 * @desc 根据平台获取最卖价模板
	 * @param  string $platformCode 销售平台
	 * @return array
	 */
	public function getSalePriceSchemeByPlatformCode($platformCode){
		return $this->getDbConnection()->createCommand()
			->from(self::tableName())
			->where("platform_code = :platform_code", array(':platform_code' => $platformCode))
			->queryRow();
	}


	/**
	 * @desc 根据条件获取卖价方案模板
	 * @param unknown $where
	 * @param unknown $dataParam
	 * @return mixed
	 */
	public function getSalePriceSchemeByWhere($where, $dataParam) {
		return $this->getDbConnection()->createCommand()
			->from(self::tableName())
			->where($where, $dataParam)
			->queryRow();
	}
}