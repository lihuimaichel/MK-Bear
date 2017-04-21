<?php
/**
 * @desc 产品刊登多属性产品属性
 * @author Liz
 *
 */
class AmazonProductAddVariationAttribute extends AmazonModel {
	
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
		return 'ueb_amazon_product_add_variation_attribute';
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
}