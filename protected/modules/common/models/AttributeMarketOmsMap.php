<?php
/**
 * Conditions Field Detail Model
 * @author	wx
 * @since	2015-07-29
 */

class AttributeMarketOmsMap extends CommonModel {
	
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}
	
	public function tableName() {
		return 'ueb_attribute_market_oms_map';
	}
	
	public function rules() {
		return array(
				array('platform_code,platform_attr_id,platform_attr_name,oms_attr_id,create_time,create_user_id,modify_time,modify_user_id','safe')
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
	
	/*
	 * @desc 根据平台属性id查oms属性Id
	 */
	public function getOmsAttrIdByPlatAttrId( $platformCode,$attrId ){
		$ret = $this->dbConnection->createCommand()
			->select('oms_attr_id')
			->from(self::tableName())
			->where( 'platform_code="'.$platformCode.'"' )
			->andWhere( 'platform_attr_id="'.$attrId.'"' )
			->queryScalar();
		return $ret;
	}
	
	/*
	 * @desc 根据平台属性name查oms属性Id
	*/
	public function getOmsAttrIdByPlatAttrName( $platformCode,$attrName ){
		$ret = $this->dbConnection->createCommand()
			->select('oms_attr_id')
			->from(self::tableName())
			->where( 'platform_code="'.$platformCode.'"' )
			->andWhere( 'platform_attr_name="'.$attrName.'"' )
			->queryScalar();
		return $ret;
	}
	
	/**
	 * @desc 获取对应平台下所有的属性id
	 * @param unknown $platFformCode
	 * @param unknown $attrId
	 */
	public function getOmsAttrIdsByPlatAttrName($platformCode, $platAttrId)
	{
		$ret = $this->dbConnection->createCommand()
					->select('oms_attr_id,platform_attr_name')
					->from(self::tableName())
					->where( 'platform_code="'.$platformCode.'"' )
					->andWhere( 'platform_attr_id="'.$platAttrId.'"' )
					->queryAll();
		return $ret;
	}
	
	/**
	 * @desc 根据平台属性ID获取对应OMS属性
	 * @param unknown $platformCode
	 * @param unknown $attributeID
	 * @return Ambigous <mixed, string, unknown>
	 */
	public function getPlatformAttributeIDByOmsAttributeID($platformCode, $attributeID) {
		$ret = $this->dbConnection->createCommand()
		->select('platform_attr_id')
		->from(self::tableName())
		->where( 'platform_code="'.$platformCode.'"' )
		->andWhere( 'oms_attr_id="'.$attributeID.'"' )
		->queryScalar();
		return $ret;		
	}
}