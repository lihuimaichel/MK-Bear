<?php

class JdAttributeValue extends JdModel {
	public function tableName(){
		
		return 'ueb_jd_attribute_value';
	}
	
	public static function model($className = __CLASS__){
		return parent::model($className);
	}
	/**
	 * @desc 保存属性值
	 * @param unknown $queryData
	 * @return boolean
	 */
	public function saveCategoryPropertyValueData($queryData, $propertyId){
		if(empty($queryData)) return false;
		
		foreach($queryData as $propertyVal){
			$addData = array(
					'property_id'	=>	$propertyId,
					'cat_id'		=>	$propertyVal['catId'],
					'property_value_id'=>	$propertyVal['propertyValueId'],
					'value_data'	=>	$propertyVal['valueData'],
					'value_data_en'	=>	$propertyVal['valueDataEn']
			);
			
			//检测是否存在该属性
			$propertyInfo = $this->find('property_id=:property_id AND cat_id=:cat_id AND property_value_id=:valid', 
									array(':property_id'=>$propertyId, ':cat_id'=>$propertyVal['catId'], ':valid'=>$propertyVal['propertyValueId']));
			if($propertyInfo){//update
				$this->getDbConnection()->createCommand()
										->update($this->tableName(), $addData, 'id=:id AND property_id=:property_id AND cat_id=:cat_id', 
											array(':id'=>$propertyInfo->id,':property_id'=>$propertyId, ':cat_id'=>$propertyVal['catId']));
			}else{//add
				$this->getDbConnection()->createCommand()->insert($this->tableName(), $addData);
			}
		}
		return true;
	}
	
	/**
	 * @desc 获取分类下属性值列表
	 * @param int $categoryID
	 * @param int $attributeID
	 */
	public function getAttributeValueList($categoryID, $attributeID) {
		return $this->dbConnection->createCommand()
			->from(self::tableName())
			->select("*")
			->where("cat_id = :category_id", array(':category_id' => $categoryID))
			->andWhere("property_id = :attribute_id", array(':attribute_id' => $attributeID))
			->queryAll();
	}
	
}

?>