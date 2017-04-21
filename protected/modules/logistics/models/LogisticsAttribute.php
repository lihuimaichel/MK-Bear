<?php
/**
 * @package Ueb.modules.logistics.models
 * 
 * @author Gordon
 */
class LogisticsAttribute extends LogisticsModel { 
	
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
        return 'ueb_logistics_attribute';
    }
    
    /**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{	      
		return array(
			array('logistics_id','numerical'),
            array('include_attribute_id,exclude_attribute_id','default'),        
		);
	}
    
    /**
     * Declares attribute labels.
     * @return array
     */
    public function attributeLabels() {
        return array( 
        	'include_attribute_id'					=> Yii::t('logistics', 'Include Attribute'),
        	'exclude_attribute_id'					=> Yii::t('logistics', 'Exclude Attribute'),
        	'attribute_set'							=> Yii::t('logistics', 'Attribute Set'),
        );
    }
    
    /**
     * 功能:获取产品特殊属性的中文值
     * @return array('attribute_id'  => 'attribute_value_lang_name')
     * @author Super
     */
    public function getAttributeList($attributeCode=NULL){
    //	$attributeList = UebModel::model('ProductAttribute')->getAttributeList();
    	if($attributeCode === NULL){
    		$attributeCode = ProductAttribute::PRODUCT_FEATURES_CODE;
    	}
        
		$attributeList = UebModel::model('ProductAttribute')->getProductAdapterAttributeByCode($attributeCode,$attributeId=null);
    	return $attributeList;
    }
    
    /**
     * save the logistics attribute information
     * @param Insert or update data $attributeData
     * @param Logistics ID $logistics_id
     * @return boolean
     */
    public function saveAttributeInfo($attributeData,$logistics_id){
    	$result = true;
    	$keyArr = array();
    	foreach($attributeData['tmp_id'] as $key=>$attrs){
    		$model = new self();
    		$model->setAttribute('include_attribute_id', isset($attributeData['include_attribute_id'][$key]) ? implode(",",$attributeData['include_attribute_id'][$key]) : '');
    		$model->setAttribute('exclude_attribute_id', isset($attributeData['exclude_attribute_id'][$key]) ? implode(",",$attributeData['exclude_attribute_id'][$key]) : '');
    		$model->setAttribute('logistics_id', $logistics_id);
    		if($key <= 0){
    			$model->setIsNewRecord(true);
    		}else{
    			$model->setAttribute('id', $key);
    			$model->setIsNewRecord(false);
    		}
    		$result = $result && $model->save();
    		$keyArr[$key] = $model->attributes['id'];
    	}
    	if(!$result){
    		return $result;
    	}else{
			return $keyArr;	    		
    	}
    }
    
    /**
     * get attrs by logistics ID
     * @param logistics ID $id
     */
    public function getAttributeByLogisticsId($id){
    	return $this->getDbConnection()->createCommand()->from($this->tableName())->where("logistics_id=:logistics_id", array(":logistics_id"=>$id))->queryAll();
    	return $this->findAllByAttributes(array('logistics_id'=>$id));
    }
}