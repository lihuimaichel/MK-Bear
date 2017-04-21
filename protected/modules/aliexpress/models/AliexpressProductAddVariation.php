<?php
/**
 * @desc 产品刊登多属性产品
 * @author zhangf
 *
 */
class AliexpressProductAddVariation extends AliexpressModel {
	
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
		return 'ueb_aliexpress_product_add_variation';
	}
	
	/**
	 * @desc 获取多属性产品
	 * @param unknown $addID
	 */
	public function getVariationProductAdd($addID) {
		return $this->dbConnection->createCommand()
			->from(self::tableName())
			->where("add_id = :add_id", array(':add_id' => $addID))
			->order("id asc")
			->queryAll();
	}
	
	/**
	 * @desc 获取多属性产品属性
	 * @param unknown $addID
	 * @return Ambigous <multitype:unknown , multitype:multitype:multitype: unknown  >
	 */
	public function getVariationProductArributes($addID) {
		$variationAttributes = array();
		$command = $this->getDbConnection()->createCommand()
			->from(self::tableName() . " a")
			->leftJoin('ueb_aliexpress_product_add_variation_attribute b', "a.id = b.variation_id")
			->where("a.add_id = :add_id", array(':add_id' => $addID));
			$res = $command->queryAll();
		if (!empty($res)) {
			//查询出是否有自定义名称
			$customArr = array();
			foreach ($res as $customInfo) {
				if(!empty($customInfo['value_name'])){
					$customArr[$customInfo['sku']] = $customInfo['value_name'];
				}
			}

			foreach ($res as $row) {
				$attributes = array();
				if (!array_key_exists($row['variation_id'], $variationAttributes)) {
					$variationAttributes[$row['variation_id']] = array(
						'sku' => $row['sku'],
						'price' => $row['price'],
						'custom_name' => isset($customArr[$row['sku']])?$customArr[$row['sku']]:'',
						'attributes' => array(),
					);
				}
				$attributes = array(
					'attribute_id' => $row['attribute_id'],
					'attribute_name' => $row['attribute_name'],
					'attribute_value_id' => $row['value_id'],
					'attribute_value_name' => $row['value_name'],
				);
				$variationAttributes[$row['variation_id']]['attributes'][] = $attributes;
			}
		}
		return $variationAttributes;
	}


	/**
	 * 用sql语句插入数据
	 */
	public function insertBySql($insertFields,$insertData){
		$insertSql = "INSERT INTO ".self::tableName()." (".$insertFields.") VALUES".$insertData;
		return $this->dbConnection->createCommand($insertSql)->execute();
	}
}