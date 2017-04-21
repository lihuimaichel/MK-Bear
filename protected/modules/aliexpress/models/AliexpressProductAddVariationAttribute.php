<?php
/**
 * @desc 产品刊登多属性产品属性
 * @author zhangf
 *
 */
class AliexpressProductAddVariationAttribute extends AliexpressModel {
	
	/**
	 * @desc 获取model
	 * @param system $className
	 * @return Ambigous <CActiveRecord, unknown, multitype:>
	 */
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}
	
	/**
	 * @设置表名
	 * (non-PHPdoc)
	 * @see CActiveRecord::tableName()
	 */
	public function tableName() {
		return 'ueb_aliexpress_product_add_variation_attribute';
	}
	
	/**
	 * @desc 获取多属性产品的属性
	 * @param unknown $variationID
	 */
	public function getVariationProductAttributes($variationID) {
		return $this->getDbConnection()->createCommand()
			->from(self::tableName())
			->where("variation_id = :variation_id", array(':variation_id' => $variationID))
			->queryAll();
	}


	/**
	 * 用sql语句插入数据
	 */
	public function insertBySql($insertFields,$insertData){
		$insertSql = "INSERT INTO ".self::tableName()." (".$insertFields.") VALUES".$insertData;
		return $this->dbConnection->createCommand($insertSql)->execute();
	}


	/**
	 * 插入刊登多属性产品属性表
	 * @param  array $addVariationAttribute
	 */
	public function saveAliProductAddVariationAttribute($addVariationAttribute) {
		try {

			$this->dbConnection->createCommand()->insert(self::tableName(), $addVariationAttribute);
			unset($addVariationAttribute);
			return true;

		} catch (Exception $e) {

			$this->setErrorMessage($e->getMessage());
			return false;

		}
	}
}