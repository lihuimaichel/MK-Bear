<?php

class JdAttribute extends JdModel {
	const EVENT_GET_ATTRIBUTE = 'get_attribute';
	
	const ATTRIBUTE_TYPE_COMMON = 1;	//普通属性
	
	const ATTRIBUTE_TYPE_SALE_TEXT = 3;	//销售属性（文字）
	
	const ATTRIBUTE_TYPE_SALE_IMAGE = 4;	//销售属性（图片）
	
	public function tableName(){
		
		return 'ueb_jd_attribute';
	}
	
	public static function model($className = __CLASS__){
		return parent::model($className);
	}
	/**
	 * @desc 保存分类属性数据
	 */
	public function saveCategoryPropertyData($queryData){
		if(empty($queryData)) return false;
		foreach($queryData as $property){
			$addData = array(
							'property_id'=> $property['propertyId'],
							'property_name' => $property['propertyName'],
							'property_name_en' => $property['propertyNameEn'],
							'cat_id'	=>	$property['catId'],
							'property_type' => $property['propertyType'],
							'input_type'	=>	$property['inputType'],
							'nav'	=>	isset($property['nav'])?$property['nav']:'',
							'require' => isset($property['require'])?$property['require']:''
						);
			//检测是否存在该属性
			$propertyInfo = $this->find('property_id=:property_id AND cat_id=:cat_id', array(':property_id'=>$property['propertyId'], ':cat_id'=>$property['catId']));
			if($propertyInfo){//update
				$this->getDbConnection()->createCommand()
										->update($this->tableName(), $addData, 
													'property_id=:property_id AND cat_id=:cat_id', 
													array(':property_id'=>$property['propertyId'], ':cat_id'=>$property['catId']));
			}else{//add
				$this->getDbConnection()->createCommand()->insert($this->tableName(), $addData);
			}
		}
		return true;
	}
	/**
	 * @desc  获取对应分类下的所有属性列表
	 * @param unknown $catId
	 * @return mixed
	 */
	public function getAttributeListByCatId($catId, $type = null, &$attributes){
		$parentID = JdCategory::model()->getParentCategoryID($catId);
		if (!empty($parentID))
			$this->getAttributeListByCatId($parentID, $type, $attributes);
		$command = $this->getDbConnection()->createCommand()
										->from($this->tableName())
										->where('cat_id=:cat_id', array(':cat_id'=>$catId));
		if (is_array($type) && !empty($type))
			$command->andWhere("property_type in (" . implode(',', $type) . ")");
		else if (is_int($type))
			$command->andWhere("property_type = :type", array(':type' => $type));
		$attributeInfos = $command->queryAll();
		if (!empty($attributeInfos)) {
			foreach ($attributeInfos as $attributeInfo)
				$attributes[] = $attributeInfo;
		}
		return $attributes;
	}
	
	/**
	 * @desc 获取分类下所有属性
	 * @param int $cateID
	 * @return Ambigous <multitype:unknown , unknown>
	 */
	public function getCategoryAttributes($cateID, $type = NULL) {
		$attributes = array();
		$attributeInfos = array();
		$attributeInfos = $this->getAttributeListByCatId($cateID, $type, $attributeInfos);
		if (!empty($attributeInfos)) {
			foreach ($attributeInfos as $key => $attributeInfo) {
				$attributeID = $attributeInfo['property_id'];
				$attributes[$key] = $attributeInfo;
				$valueInfos = JdAttributeValue::model()->getAttributeValueList($attributeInfo['cat_id'], $attributeID);
				$attributes[$key]['value_list'] = $valueInfos;
			}
		}
		return $attributes;
	}
}

?>