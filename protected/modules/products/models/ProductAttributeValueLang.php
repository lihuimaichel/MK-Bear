<?php
/**
 * @desc oms 产品属性值语言model
 * @author wx
 * 2015-09-22
 */
class ProductAttributeValueLang extends ProductsModel { 
    
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
        return 'ueb_product_attribute_value_lang';
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
	 * @desc 获取属性值对应语言值
	 * @param unknown $attributeValues
	 * @param string $lang
	 * @return multitype:
	 */
	public function getAttributeValueLangs($attributeValues, $lang = 'English'){
		if(empty($attributeValues)) return array();
		if(!is_array($attributeValues))
			$attributeValues = array($attributeValues);
		$langList = $this->getDbConnection()->createCommand()
									->from(self::tableName())
									->where(array('IN', 'attribute_value_name', $attributeValues))
									->queryAll();
		if(empty($langList)) return array();
		$newLangList = array();
		foreach ($langList as $val){
			$newLangList[$val['attribute_value_name']] = $val['attribute_value_lang_name'];
		}
		return $newLangList;
	}


	/**
	 * @desc 获取attribute_value_lang_name值
	 * @param unknown $attrrbute_code
	 * @param string $lang
	 * @return string
	 */
	public function getAttributeNameByCode($attrrbute_code,$language=CN){
    	$string = '';
    	$data = $this->find('attribute_value_name=:attribute_value_name and language_code=:language_code',
    		array(':attribute_value_name'=>$attrrbute_code,':language_code'=>$language));
    	if($data)
    		return $data->attribute_value_lang_name;
    	else 
    		return '';
    }


    /**
	 * 获取属性值 对应中文
	 */
    public function getAttrChineseVal($ids){
    	$data = $this->getDbConnection()->createCommand()
    	->select('c.attribute_id,b.id,a.attribute_value_lang_name')
    	->from(self::tableName().' a')
    	->join(ProductAttributeValue::tableName().' b', 'a.attribute_value_name = b.attribute_value_name')
    	->join(ProductAttributeMap::tableName().' c', 'b.id = c.attribute_value_id')
    	->where(array('IN', 'c.attribute_id', $ids))
    	->andWhere("a.language_code = 'Chinese'")   		
    	->queryAll();
		$list=array();
		foreach ($data as $val){
			$list[$val['attribute_id']][$val['id']]=$val['attribute_value_lang_name'];
		}
		return $list;
    }
}	 