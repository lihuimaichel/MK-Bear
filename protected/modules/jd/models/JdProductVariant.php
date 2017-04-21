<?php

class JdProductVariant extends JdModel {
	public function tableName(){
		
		return 'ueb_jd_variants';
	}
	
	public static function model($className = __CLASS__){
		return parent::model($className);
	}
	
	/**
	 * @desc 根据父sku获取全部的子sku
	 * @param unknown $parentSku
	 * @return mixed
	 */
	public function getVariantListByParentSku($parentSku, $conditions = null, $params = null){
		$command = $this->getDbConnection()->createCommand()
								->from(self::tableName())
								->where('parent_sku=:parent_sku', array(':parent_sku'=>$parentSku));
		if($conditions){
			$command->andWhere($conditions, $params);
		}
		return $command->queryAll();
	}
	
}

?>