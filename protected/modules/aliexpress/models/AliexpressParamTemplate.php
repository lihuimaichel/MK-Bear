<?php
/**
 * @desc Aliexpress配置刊登参数模板
 * @author Tony
 * @since 2015-09-14
 */
class AliexpressParamTemplate extends AliexpressModel{
	
	const TEMPLATE_ENABLE  = 1;	//启用
	const TEMPLATE_DISABLE = 0;	//停用
	
	const PACKAGE_TYPE_YES = 1;//包裹打包
	const PACKAGE_TYPE_NO = 0;//包裹不打包
	
	const REDUCESTRATEGY_ORDER = 'place_order_withhold'; //下单扣减库存
	const REDUCESTRATEGY_PAYMENT = 'payment_success_deduct'; //支付成功扣减库存
	
	public $delivery_time_create = '';
	public $ws_valid_num_create = '';
	public $bulk_order_create = '';
	
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}
	
	/**
	 * @desc 数据库表名
	 * @see CActiveRecord::tableName()
	 */
	public function tableName() {
		return 'ueb_aliexpress_param_template';
	}
	
	/**
	 * @desc 获取库存扣减策略
	 * @param unknown $key
	 * @return unknown
	 */
	public static function getReduceStrategy($key){
		$reduceStrategy = array(
				self::REDUCESTRATEGY_ORDER	=>Yii::t('aliexpress',     'Place Order Withhold'),
				self::REDUCESTRATEGY_PAYMENT	=>Yii::t('aliexpress',     'Payment Success Deduct')
		);
		($key && array_key_exists($key, $reduceStrategy)) && $reduceStrategy = $reduceStrategy[$key];
		return $reduceStrategy;
	}
	
	/**
	 * @desc 获取产品单位
	 * @param unknown $key
	 * @return unknown
	 */
 	public static function getProductUnit($key = null){
		$getProductUnit = array(
			100000000 => '袋 (bag/bags)',
			100000001 => '桶 (barrel/barrels)',
			100000002 => '蒲式耳 (bushel/bushels)',
			100078580 => '箱 (carton)',
			100078581 => '厘米 (centimeter)',
			101528981 => '组合 (combo)',
			100000003 => '立方米 (cubic meter)',
			100000004 => '打 (dozen)',
			100078584 => '英尺 (feet)',
			100000005 => '加仑 (gallon)',
			100000006 => '克 (gram)',
			100078587 => '英寸 (inch)',
			100000007 => '千克 (kilogram)',
			100078589 => '千升 (kiloliter)',
			100000008 => '千米 (kilometer)',
			100078559 => '升 (liter/liters)',
			100000009 => '英吨 (long ton)',
			100000010 => '米 (meter)',
			100000011 => '公吨 (metric ton)',
			100078560 => '毫克 (milligram)',
			100078596 => '毫升 (milliliter)',
			100078597 => '毫米 (millimeter)',
			100000012 => '盎司 (ounce)',
			100000014 => '包 (pack/packs)',
			100000013 => '双 (pair)',
			100000015 => '件/个 (piece/pieces)',
			100000016 => '磅 (pound)',
			100078603 => '夸脱 (quart)',
			100000017 => '套 (set/sets)',
			100000018 => '美吨 (short ton)',
			100078606 => '平方英尺 (square feet)',
			100078607 => '平方英寸 (square inch)',
			100000019 => '平方米 (square meter)',
			100078609 => '平方码 (square yard)',
			100000020 => '吨 (ton)',
			100078558 => '码 (yard/yards)',
		);
		($key && array_key_exists($key, $getProductUnit)) && $getProductUnit = $getProductUnit[$key];
		return $getProductUnit;
	} 
	
	/**
	 * @desc 获取包裹打包类型
	 * @param unknown $key
	 * @return unknown
	 */
	public function getPackageTypeConfig() {
		return array(
				self::PACKAGE_TYPE_YES			=>Yii::t('aliexpress',     'Package type yes'),
				self::PACKAGE_TYPE_NO			=>Yii::t('aliexpress',     'Package type no'),
		);
	}
	
	/**
	 * @desc 获取模板启用类型
	 * @param unknown $key
	 * @return unknown
	 */
	public function getUseStatusConfig() {
		return array(
				self::TEMPLATE_ENABLE			=>Yii::t('system',	   'Enable'),
				self::TEMPLATE_DISABLE			=>Yii::t('system',     'Disable'),
		);
	}
	
	/**
	 * @desc 获取担保类型
	 * @param unknown $key
	 * @return unknown
	 */
	/* public static function getProductUnit($key){
		$getProductUnit = array(
				'bag/bags'		=>	100000000,
				'barrel/barrels'=>	100000001,
				'bushel/bushels'=>	100000002,
				'carton'		=>	100078580,
				'centimeter'	=>	100078581,
				'cubic meter'	=>	100000003,
				'dozen'			=>	100000004,
				'feet'			=>	100078584,
				'gallon'		=>	100000005,
				'gram'			=>	100000006,
				'inch'			=>	100078587,
				'kilogram'		=>	100000007,
				'kiloliter'		=>	100078589,
				'kilometer'		=>	100000008,
				'liter/liters'	=>	100078559,
				'long ton'		=>	100000009,
				'meter'			=>	100000010,
				'metric ton'	=>	100000011,
				'milligram'		=>	100078560,
				'milliliter'	=>	100078596,
				'millimeter'	=>	100078597,
				'ounce'			=>	100000012,
				'pack/packs'	=>	100000014,
				'pair'			=>	100000014,
				'piece/pieces'	=>	100000015,
				'pound'			=>	100000016,
				'quart'			=>	100078603,
				'set/sets'		=>	100000017,
				'short ton'		=>	100000018,
				'square feet'	=>	100078606,
				'square inch'	=>	100078607,
				'square meter'	=>	100000019,
				'square yard'	=>	100078609,
				'ton'			=>	100000020,
				'yard/yards'	=>	100078558,
		);
		($key && array_key_exists($key, $getProductUnit)) && $getProductUnit = $getProductUnit[$key];
		return $getProductUnit;
	} */
	
	public function rules() {
		return array(
				array('tamplate_name,delivery_time,package_type,product_unit,template_status,stock_num', 'required'),
 			 	array('delivery_time,bulk_discount,bulk_order,ws_valid_num', 'numerical', 'integerOnly' => true),
				array('delivery_time', 'numerical',  'min'=>1, 'max'=>60),
				array('ws_valid_num', 'numerical',  'min'=>1, 'max'=>30),
				array('bulk_order', 'numerical',  'min'=>2,'max'=>100000),
				array('bulk_discount', 'numerical',  'min'=>1,'max'=>99),
				array('reduce_strategy,promise_template_id,freight_template_Id,create_time,create_user_id,modify_time,modify_user_id', 'safe'),
				array('stock_num', 'numerical', 'min' => 1, 'max' => 10000),
		);
	}
	
	/**
	 * @desc 属性翻译
	 */
	public function attributeLabels() {
		return array(
				'id'							=> Yii::t('aliexpress', 'Id'),
				'tamplate_name'					=> Yii::t('aliexpress', 'Tamplate Name'),
				'delivery_time'					=> Yii::t('aliexpress', 'Delivery Time'),
				'delivery_time_create'			=> Yii::t('aliexpress', 'Delivery Time Create'),
				'promise_template_id'			=> Yii::t('aliexpress', 'Promise Template Id'),
				'freight_template_Id'			=> Yii::t('aliexpress', 'Freight Template Id'),
				'product_unit'					=> Yii::t('aliexpress', 'Product Unit'),
				'package_type'					=> Yii::t('aliexpress', 'Package Type'),
				'ws_valid_num'					=> Yii::t('aliexpress', 'Ws Valid Num'),
				'ws_valid_num_create'			=> Yii::t('aliexpress', 'Ws Valid Num Create'),
				'bulk_order'					=> Yii::t('aliexpress', 'Bulk Order'),
				'bulk_order_create'				=> Yii::t('aliexpress', 'Bulk Order Create'),
				'bulk_discount'					=> Yii::t('aliexpress', 'Bulk Discount'),
				'reduce_strategy'				=> Yii::t('aliexpress', 'Reduce Strategy'),
				'template_status'			    => Yii::t('aliexpress', 'Template Status'),
				'create_time'					=> Yii::t('aliexpress', 'Create Time'),
				'create_user_id'				=> Yii::t('aliexpress', 'Create User Id'),
				'modify_time'					=> Yii::t('aliexpress', 'Modify Time'),
				'modify_user_id'				=> Yii::t('aliexpress', 'Modify User Id'),
				'stock_num'						=> Yii::t('aliexpress', 'Stock Number'),
		);
	}
	
	/**
	 * @return array search filter (name=>label)
	 */
	public function filterOptions() {
		$result = array(
				array(
						'name'     		 => 'id',
						'type'     		 => 'text',
						'search'   		 => 'LIKE',
						'alias'    		 => 't',
				),
				array(
						'name'           => 'tamplate_name',
						'type'     		 => 'text',
						'search'         => 'LIKE',
						'alias'          => 't',
				),
				array(
		    			'name'          => 'create_time',
		    			'type'          => 'text',
		            	'alias'			=> 't',
		    			'search'        => 'RANGE',
		    		    'htmlOptions'   => array(
			    							'class'     	=> 'date',
			    							'dateFmt'   	=> 'yyyy-MM-dd HH:mm:ss',
		    							),
    			),
				array(
						'name'			=> 'template_status',
						'type'			=> 'dropDownList',
						'search'		=> '=',
						'value'			=> Yii::app()->request->getParam('template_status', self::TEMPLATE_ENABLE),
						'data'          => $this->getUseStatusConfig(),
						'htmlOptions'   => array(),
						'alias'			=> 't'
				) 
				
		);
	
		return $result;
	
	}
	/**
	 * search SQL
	 * @return $array
	 */
	protected function _setCDbCriteria() {
	
		return NULL;
	}
	/**
	 * @return $array
	 */
	public function search(){
		$sort = new CSort();
		$sort->attributes = array(
				'defaultOrder'  => 'id',
		);
		$criteria = null;
		$criteria = $this->_setCDbCriteria();
		$dataProvider = parent::search(get_class($this), $sort,array(),$criteria);
	
		$data = $this->addition($dataProvider->data);
		$dataProvider->setData($data);
		return $dataProvider;
	}
	
	/**
	 * @return $array
	 */
	public function addition($data){
		return $data;
	}
	
	/**
	 * @desc 根据模板id查找模板信息
	 * @param unknown $id
	 */
	public function getParamTplById($id) {
		$id = (array) $id;
		return $this->getDbConnection()
		->createCommand()
		->select('id,tamplate_name as tpl_name')
		->from(self::tableName())
		->where(array('IN', 'id', $id))
		->andWhere('template_status='.self::TEMPLATE_ENABLE)
		->queryAll();
	}
	
	public function getParamTemplateByPk( $id ){
		$retObj = $this->findByPk($id,'template_status = '.self::TEMPLATE_ENABLE);
		return $retObj->attributes;
	}
	
	/**
	 * @desc 根据ID获取参数模板
	 * @param unknown $id
	 */
	public function getParamTemplateByID($id) {
		return $this->getDbConnection()->createCommand()
			->from(self::tableName())
			->where("id = :id", array(':id' => $id))
			->queryRow();
	}
	
	/**
	 * @desc 获取最优描述模板
	 * @param unknown $params
	 * @return unknown|boolean
	 */
	public function getTemplateInfo($params = array()) {
		$ruleModel = new ConditionsRulesMatch();
		$ruleModel->setRuleClass(TemplateRulesBase::MATCH_PARAM_TEMPLATE);
		$templateID = $ruleModel->runMatch($params);
		if (empty($templateID) || !($templateInfo = $this->getParamTemplateByID($templateID))) {
			return $templateInfo;
		}
		return false;
	}	
}