<?php
/**
 * @desc oms 产品属性值model
 * @author wx
 * 2015-09-22
 */
class ProductAttributeValue extends ProductsModel { 
    
    public $attribute_id = null;

    /**
     * Returns the static model of the specified AR class.
     * @return CActiveRecord the static model class
     */
    public static function model($className = __CLASS__) {     
        return parent::model($className);
    }

    /**
     * @return string the associated database table name
     */
    public function tableName() {
        return 'ueb_product_attribute_value';
    }
    
    /**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{	      
		return array(
			
		);
	}
	
	/**
	 * @desc 根据id数组来获取对应的属性值数据
	 * @param array $ids
	 * @param string $fields
	 * @return mixed
	 */
	public function getAttributeValueListByIds($ids, $fields = '*'){
		return $this->getDbConnection()->createCommand()
					->select($fields)
					->from(self::tableName())
					->where(array('in', 'id', $ids))
					->queryAll();
	}
	
	/**
	 * @desc 根据属性值ID获取属性值名
	 * @param unknown $valueID
	 */
	public function getValueNameByID($valueID) {
		return $this->dbConnection->createCommand()
			->select("attribute_value_name")
			->from(self::tableName())
			->where("id = :value_id", array(':value_id' => $valueID))
			->queryScalar();
	}


	/**
     * getSubSkuAttriValueName
     */
    public function getSubSkuAttriValueName($attributeValId){
    	$data=$this->getDbConnection()->createCommand()
  	 	->select('*')
  	 	->from(self::tableName())
  	 	->where("id= '{$attributeValId}'")
  	 	->queryRow();
    	return '('.$data['attribute_value_name'].')';
    }


    /**
     * get attribute value name by id
     * @param  int $id
     * @return string $attributeValueName
     */
   	 public function getAttributeValueNameById($id){  		
		$attributeValueName = $this->getDbConnection()->createCommand()
		->select('attribute_value_name')
		->from(self::tableName())
		->where("id = '{$id}'")
		->queryRow();
		return $attributeValueName['attribute_value_name'];
  	} 
}	 