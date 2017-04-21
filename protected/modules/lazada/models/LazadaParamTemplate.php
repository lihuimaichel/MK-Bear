<?php
/**
 * Lazada Param Template Model
 * @author	wx
 * @since	2015-07-29
 */

class LazadaParamTemplate extends LazadaModel {
	
	const ENABLE_ON = 1;	//启用
	const ENABLE_OFF = 0;	//停用
	
	const TAXES_DEFAULT = 1;
	const TAXES_TAXSIX = 2;
	const TAXES_TAXSIXX = 3;
	
	const WARRANTY_DEFAULT = '1042';
	const WARRANTY_LAZADA = '1041';
	const WARRANTY_MANUFACTURER_INTERNATIONAL = '1040';
	const WARRANTY_MANUFACTURER_LOCAL = '1039';
	const WARRANTY_REGIONAL_ASIA = '1043';
	const WARRANTY_SUPPLIER_LOCAL = '1044';
	const WARRANTY_HYPHEN = '1045';
	const WARRANTY_DOT = '1046';
	const WARRANTY_ON_SITE = '1047';
	const WARRANTY_OFFICIAL_XIAOMI = '1194';
	const WARRANTY_RETURNS_POLICY = '1416';
	
	const WARRANTY_PERIOD_THREEMTHS = '1029';
	const WARRANTY_PERIOD_SIXMTHS = '1028';
	const WARRANTY_PERIOD_ONEYEAR = '1027';
	const WARRANTY_PERIOD_TWOYEARS = '1026';
	const WARRANTY_PERIOD_THREEYEARS = '1025';
	const WARRANTY_PERIOD_FIVEYEARS = '1024';
	const WARRANTY_PERIOD_TENYEARS = '1023';
	const WARRANTY_PERIOD_TWOMTHS = '1030';
	const WARRANTY_PERIOD_FOURMTHS = '1031';
	const WARRANTY_PERIOD_SEVENYEARS = '1032';
	const WARRANTY_PERIOD_TENMTHS = '1033';
	const WARRANTY_PERIOD_ONEMTH = '1034';
	const WARRANTY_PERIOD_TWOWEEKS = '1035';
	const WARRANTY_PERIOD_TWENTYFIVEYEARS = '1036';
	const WARRANTY_PERIOD_LIFEWARRANTY = '1037';
	const WARRANTY_PERIOD_NOWARRANTY = '1038';
	const WARRANTY_PERIOD_FOURTEENDAYS = '1101';
	
	
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}
	
	public function tableName() {
		return 'ueb_lazada_param_template';
	}
	
	public function rules() {
		return array(
				array('tpl_name,taxes,shipping_time_min,shipping_time_max,warranty_type,warranty_period,return_policy,buyer_protection_details,image_width,image_height', 'required'),
				array('shipping_time_min,shipping_time_max,image_width,image_height', 'numerical', 'integerOnly' => true),
				array('tpl_code,is_enable,create_time,create_user_id,modify_time,modify_user_id,manufacturer', 'safe')
		);
	}
	
	/**
	 * @desc 获取税类别
	 * @param unknown $key
	 * @return unknown
	 */
	public static function getTaxes($key){
		$taxesArray = array(
				self::TAXES_DEFAULT       => 'default',
				self::TAXES_TAXSIX        => 'tax 6',
				self::TAXES_TAXSIXX       => 'tax 6'
		);
		($key && array_key_exists($key, $taxesArray)) && $taxesArray = $taxesArray[$key];
		return $taxesArray;
	}
	
	/**
	 * @desc 获取担保类型
	 * @param unknown $key
	 * @return unknown
	 */
	public static function getWarrantyType($key){
		$warrantyTypeArray = array(
				self::WARRANTY_DEFAULT       		=> '',
				self::WARRANTY_LAZADA        		=> 'Lazada Warranty',
				self::WARRANTY_MANUFACTURER_INTERNATIONAL	=> 'International Manufacturer Warranty',
				self::WARRANTY_MANUFACTURER_LOCAL	=> 'Local Manufacturer Warranty',
				self::WARRANTY_REGIONAL_ASIA		=> 'Regional Warranty South & Southeast Asia',
				self::WARRANTY_SUPPLIER_LOCAL		=> 'Local Supplier Warranty',
				self::WARRANTY_HYPHEN 				=> '-',
				self::WARRANTY_DOT 					=> '...',
				self::WARRANTY_ON_SITE 				=> 'On-Site Warranty',
				self::WARRANTY_OFFICIAL_XIAOMI 		=> 'Official Xiaomi Warranty',
				self::WARRANTY_RETURNS_POLICY		=> 'Lazada Returns Policy'
		);
		($key && array_key_exists($key, $warrantyTypeArray)) && $warrantyTypeArray = $warrantyTypeArray[$key];
		return $warrantyTypeArray;
	}
	
	/**
	 * @desc 获取担保周期
	 * @param unknown $key
	 * @return unknown
	 */
	public static function getWarrantyPeriod($key){
		$warrantyPeriodArray = array(
				self::WARRANTY_PERIOD_THREEMTHS 	=> '3 Months',
				self::WARRANTY_PERIOD_SIXMTHS 		=> '6 Months',
				self::WARRANTY_PERIOD_ONEYEAR 		=> '1 Year',
				self::WARRANTY_PERIOD_TWOYEARS 		=> '2 Years',
				self::WARRANTY_PERIOD_THREEYEARS 	=> '3 Years',
				self::WARRANTY_PERIOD_FIVEYEARS 	=> '5 Years',
				self::WARRANTY_PERIOD_TENYEARS 		=> '10 Years',
				self::WARRANTY_PERIOD_TWOMTHS 		=> '2 Months',
				self::WARRANTY_PERIOD_FOURMTHS 		=> '4 Months',
				self::WARRANTY_PERIOD_SEVENYEARS 	=> '7 Years',
				self::WARRANTY_PERIOD_TENMTHS 		=> '10 Months',
				self::WARRANTY_PERIOD_ONEMTH 		=> '1 Month',
				self::WARRANTY_PERIOD_TWOWEEKS 		=> '2 Weeks',
				self::WARRANTY_PERIOD_TWENTYFIVEYEARS => '25 Years',
				self::WARRANTY_PERIOD_LIFEWARRANTY 	=> 'Life Time Warranty',
				self::WARRANTY_PERIOD_NOWARRANTY 	=> 'No Warranty',
				self::WARRANTY_PERIOD_FOURTEENDAYS 	=> '14 days'
		);
		($key && array_key_exists($key, $warrantyPeriodArray)) && $warrantyPeriodArray = $warrantyPeriodArray[$key];
		return $warrantyPeriodArray;
	}
	
	/**
	 * Declares attribute labels.
	 * @return array
	 */
	public function attributeLabels() {
		return array(
				'id'                    	=> Yii::t('system', 'No.'),
				'tpl_code'					=> Yii::t('lazadaparam', 'Template Code'),
				'tpl_name'                	=> Yii::t('lazadaparam', 'Template Name'),
				'taxes'						=> Yii::t('lazadaparam', 'Taxes'),
				'shipping_time_min'			=> Yii::t('lazadaparam', 'Sipping Time Min'),
				'shipping_time_max'			=> Yii::t('lazadaparam', 'Sipping Time Max'),
				'is_enable'					=> Yii::t('lazadaparam', 'Is Enable'),
				'warranty_type'				=> Yii::t('lazadaparam', 'Warranty Type'),
				'warranty_period'			=> Yii::t('lazadaparam', 'Warranty Period'),
				'return_policy'				=> Yii::t('lazadaparam', 'Return Policy'),
				'buyer_protection_details'	=> Yii::t('lazadaparam', 'Buyer Protection Details'),
				'manufacturer'				=> Yii::t('lazadaparam', 'From The Manufacturer'),
				'image_width'				=> Yii::t('lazadaparam', 'Image Width'),
				'image_height'				=> Yii::t('lazadaparam', 'Image Height'),
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
						'name'          => 'tpl_name',
						'type'          => 'text',
						'search'        => 'LIKE',
						'alias'			=> 't',
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
						'name'			=> 'is_enable',
						'type'			=> 'dropDownList',
						'search'		=> '=',
						'value'			=> Yii::app()->request->getParam('is_enable', self::ENABLE_ON),
						'data'          => $this->getUseStatusConfig(),
						'htmlOptions'   => array(),
						'alias'			=> 't'
				)
		);
		return $result;
	}
	
	public function search() {
		$sort = new CSort();
		$sort->attributes = array(
				'defaultOrder'  => 'create_time',
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
		/* foreach ($data as $key => $value) {
		} */
		return $data;
	}
	
	public function getUseStatusConfig() {
		return array(
				self::ENABLE_ON				=>Yii::t('system',     'Enable'),
				self::ENABLE_OFF			=>Yii::t('system',     'Disable'),
		);
	}
	
	public function getParamTplByCondition( $condition = '', $field = '*' ){
		empty($condition) && $condition = ' 1=1 ';
		$ret = $this->dbConnection->createCommand()
				->select($field)
				->from($this->tableName())
				->where($condition)
				->queryAll();
		
		return $ret;
	}
	
	/**
	 * @desc 根据模板id查找模板信息
	 * @param unknown $id
	 */
	public function getParamTplById($id) {
		$id = (array) $id;
		return $this->getDbConnection()
				->createCommand()
				->select('id,tpl_name')
				->from(self::tableName())
				->where(array('IN', 'id', $id))
				->andWhere('is_enable='.self::ENABLE_ON)
				->queryAll();
	}
	
	public function getParamTemplateByPk( $id ){
		$retObj = $this->findByPk($id,'is_enable = '.self::ENABLE_ON);
		return $retObj->attributes;
	}
	
}