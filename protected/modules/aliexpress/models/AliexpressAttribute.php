<?php
/**
 * @desc Aliexpress 属性model
 * @author wx
 * @since 2015-09-11
 */
class AliexpressAttribute extends AliexpressModel{
    
    /** @var string 异常信息*/
    protected $exception = null;
    
    const ATTRIBUTE_TYPE_SKU = 1;			//SKU属性
    const ATTRIBUTE_TYPE_COMMON = 0;		//普通属性
	    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 设置账号ID
     */
    public function setAccountID($accountID){
    	$this->_accountID = $accountID;
    }
    
    /**
     * @return array validation rules for model attributes.
     */
    public function rules(){}
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_aliexpress_attribute';
    }
    
    /**
     * @desc 获取异常信息
     * @return string
     */
    public function getExceptionMessage(){
        return $this->exception;
    }
    
    /**
     * @desc 设置异常信息
     * @param string $message
     */
    public function setExceptionMessage($message){
        $this->exception = $message;
    }
    
    /**
     * @desc 根据attribute_id 查询属
     * @param integer $attributeId
     */
    public function getAttrByAttrId( $attributeId = '' ){
    	if(!$attributeId) return false;
    	$ret = $this->dbConnection->createCommand()
	    	->select('*')
	    	->from(self::tableName())
	    	->where('attribute_id = "'.$attributeId.'"')
	    	->queryRow();
    	 
    	return $ret;
    }
    

    
    public function relations() {
    	return array(
    			'attributeNames' => array(
    					self::HAS_MANY,
    					'AliexpressAttributeLanguageMap',
    					'',
    					'on' => 'attributeNames.attribute_id = t.attribute_id',
    			),
    	);
    }    
    
    /**
     * @desc 获取分类下面的属性列表
     * @param integer $cateID
     * @param integer $type
     * @param integer $visable
     * @return multitype:multitype:NULL
     */
    public function getCategoryAttributeList($cateID, $type = 0, $visable = 1) {
    	$attributes = array();
 		$command = $this->getDbConnection()->createCommand()
			->from(self::tableName() . " t")
			->join("ueb_aliexpress_category_attribute t1", "t.attribute_id = t1.attribute_id")
			->leftJoin("ueb_aliexpress_attribute_language_map t2", "t.attribute_id = t2.attribute_id")
			->where("category_id = :category_id", array(':category_id' => $cateID))
			->andWhere("t1.sku = :type", array(':type' => $type))
			->andWhere("t.attribute_id <> 200007763")	//排除销售属性为 发货地 的属性
			->order("t.attribute_spec asc");
		if (!empty($visable))
			$command->andWhere("t.attribute_visible = 1");
		$res = $command->queryAll();
    	if (!empty($res)) {
    		foreach ($res as $attribute){
    			if (!array_key_exists($attribute['attribute_id'], $attributes)) {
	    			$attributes[$attribute['attribute_id']] = array(
	    					'attribute_id' => $attribute['attribute_id'],
	    					'attribute_spec' => $attribute['spec'],
	    					'attribute_visible'	=> $attribute['visible'],
	    					'attribute_customized_name' => $attribute['costomized_name'],
	    					'attribute_customized_pic' => $attribute['customized_pic'],
	    					'attribute_key_attribute'	=> $attribute['key_attribute'],
	    					'attribute_required'	=> $attribute['attribute_required'],
	    					'attribute_sku' => $attribute['sku'],
	    					'attribute_input_type' => $attribute['input_type'],
	    					'attribute_showtype_value' => $attribute['showtype_value'],
	    			);
	    			$valueList = array();
	    			$valueIds = $attribute['attribute_value_ids'];
	    			if (!empty($valueIds))
	    				$valueList = AliexpressAttributeValue::model()->getAttributeValueByIds($valueIds);
	    			$attributes[$attribute['attribute_id']]['value_list'] = $valueList;
    			}
    			$k = 'attribute_name_' . $attribute['language_code'];
    			$attributes[$attribute['attribute_id']][$k] = $attribute['attribute_name'];
    		}
    	}
    	return $attributes;
    }
    
    /**
     * @desc 获取子属性
     * @param unknown $attributeID
     * @param unknown $valueID
     * @return multitype:multitype:unknown
     */
    public function getSubAttributes($attributeID, $valueID) {
    	$subAttributes = array();
 		$command = $this->getDbConnection()->createCommand()
			->from(self::tableName() . " t")
			->join("ueb_aliexpress_attribute_value_map t1", "t.attribute_id = t1.attribute_value_children_id")
			->leftJoin("ueb_aliexpress_attribute_language_map t2", "t.attribute_id = t2.attribute_id")
			->where("t1.attribute_id = :attribute_id", array(':attribute_id' => $attributeID))
			->andWhere("t1.attribute_value_id = :attribute_value_id", array(':attribute_value_id' => $valueID))
			->andWhere("t.attribute_visible = 1")
			->order("t.attribute_spec desc");
		$res = $command->queryAll();
    	if (!empty($res)) {
    		foreach ($res as $attribute){
    			if (!array_key_exists($attribute['attribute_id'], $subAttributes)) {
	    			$subAttributes[$attribute['attribute_id']] = array(
	    					'attribute_id' => $attribute['attribute_id'],
	    					'attribute_spec' => $attribute['attribute_spec'],
	    					'attribute_visible'	=> $attribute['attribute_visible'],
	    					'attribute_customized_name' => $attribute['attribute_customized_name'],
	    					'attribute_customized_pic' => $attribute['attribute_customized_pic'],
	    					'attribute_key_attribute'	=> $attribute['attribute_key_attribute'],
	    					'attribute_required'	=> $attribute['attribute_required'],
	    					'attribute_sku' => $attribute['attribute_sku'],
	    					'attribute_input_type' => $attribute['attribute_input_type'],
	    					'attribute_showtype_value' => $attribute['attribute_showtype_value'],
	    			);
	    			$valueList = array();
	    			$valueIds = $attribute['attribute_value_children_values'];
	    			if (!empty($valueIds))
	    				$valueList = AliexpressAttributeValue::model()->getAttributeValueByIds($valueIds);
	    			$subAttributes[$attribute['attribute_id']]['value_list'] = $valueList;
    			}
    			$k = 'attribute_name_' . $attribute['language_code'];
    			$subAttributes[$attribute['attribute_id']][$k] = $attribute['attribute_name'];
    		}
    	}
    	return $subAttributes;
    }
}