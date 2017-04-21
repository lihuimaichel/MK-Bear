<?php
/**
 * @desc 产品刊登多属性产品
 * @author Liz
 *
 */
class AmazonProductAddVariation extends AmazonModel {

	const SKU_TYPE_SINGLE       	= 0;	//单品
	const SKU_TYPE_VARIATION_SON    = 1;	//多属性子SKU
	const SKU_TYPE_VARIATION_PARENT = 2;	//多属性父SKU
	
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
		return 'ueb_amazon_product_add_variation';
	}

	/**
	 * @desc 通过自增ID获取多属性产品
	 * @param int $variationID
	 */
	public function getVariationInfoByID($variationID) {
		return $this->dbConnection->createCommand()
			->from(self::tableName())
			->where("id = ".$variationID)
			->queryRow();
	}	
	
	/**
	 * @desc 通过主表ID获取多属性产品
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
	 * @desc 通过主表ID获取多属性产品（key为子SKU）
	 * @param unknown $addID
	 */
	public function getFormatVariationListByAddID($addID) {
		$result = array();
		$ret = $this->dbConnection->createCommand()
			->from($this->tableName())
			->where("add_id = :add_id", array(':add_id' => $addID))
			->order("id asc")
			->queryAll();
		if ($ret){
			foreach($ret as $val){
				$result[$val['sku']] = $val;
			}
			return $result;
		}
		return;
	}	

	/**
	 * @desc 通过在线SKU获取多属性产品
	 * @param string $sellerSku
	 */
	public function getProductAddVariationInfoBysellerSKU($sellerSku) {
		return $this->dbConnection->createCommand()
			->from(self::tableName())
			->where("seller_sku = :seller_sku", array(":seller_sku" => $sellerSku))
			->queryRow();
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
			foreach ($res as $row) {
				$attributes = array();
				if (!array_key_exists($row['variation_id'], $variationAttributes)) {
					$variationAttributes[$row['variation_id']] = array(
						'sku' => $row['sku'],
						'price' => $row['price'],
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
	 * @desc 根据自增ID更新
	 * @param int $varationID
	 * @param array $updata
	 * @return boolean
	 */
	public function updateProductAddVarationByID($varationID, $updata){
		if(empty($varationID) || empty($updata)) return false;
		$conditions = "id = {$varationID}";
		return $this->getDbConnection()->createCommand()->update(self::tableName(), $updata, $conditions);
	}

	/**
	 * @desc 更新亚马逊刊登子SKU表的数据
	 * @param string $condition
	 * @param array $updata
	 * @return boolean
	 */
	public function updateProductAddVariation($conditions, $updata){
		if(empty($conditions) || empty($updata)) return false;
		return $this->getDbConnection()->createCommand()
				    ->update(self::tableName(), $updata, $conditions);
	}	

	/**
	 * @desc 通过主表IDs获取对应的多属性IDs（单品刊登）
	 * @param string $AddIDs
	 */
	public function getVariationIDsByAddIds($AddIDs) {
		$list = array();
		if (empty($AddIDs)) return false;

		$AddIDsArr = explode(',',$AddIDs);
		foreach ($AddIDsArr as $addID){
			$addID = (int)$addID;
			if($addID == 0) continue;
			$ret = $this->dbConnection->createCommand()
				->select('id')
				->from(self::tableName())
				->where("add_id = " .$addID)
				->andWhere("sku_type = " .self::SKU_TYPE_SINGLE)
				->queryRow();
			if($ret) $list[] = $ret['id'];
		}	
		if ($list){
			return implode(',',$list);
		}else{
			return false;
		}
	}	

	/**
	 * @desc 通过账号获取待刊登（排除成功和失败状态的记录）子SKU自增ID串
	 * @param $accountID 账号ID
	 * @return string
	 */
	public function getProductVariationIDsByAccountID($accountID = 0){
		if ($accountID > 0){
			$ret = $this->getDBConnection()->createCommand()
					->select('v.id')
					->from($this->tableName() . " as v")
					->leftJoin(AmazonProductAdd::model()->tableName() . " as p", "p.id = v.add_id")
					->where('v.status !=' .AmazonProductAdd::UPLOAD_STATUS_SUCCESS)
					->andWhere('v.status !=' .AmazonProductAdd::UPLOAD_STATUS_FAILURE)
					->andWhere('p.account_id =' .$accountID)
					// ->limit(AmazonProductAdd::PRODUCT_PUBLISH_LIMIT)
					->order('v.id asc')
					->queryAll();	
		}else{
			$ret = $this->getDBConnection()->createCommand()
					->select('id')
					->from($this->tableName())
					->where('status !=' .AmazonProductAdd::UPLOAD_STATUS_SUCCESS)
					->andWhere('status !=' .AmazonProductAdd::UPLOAD_STATUS_FAILURE)
					->order('id asc')
					->queryAll();
		}
		if($ret){
			$item = array();
			foreach($ret as $val){
				$item[] = $val['id'];
			}
			return implode(",",$item);
		}else{
			return '';
		}
	}

	/**
	 * @desc 根据条件删除
	 * @param unknown $conditions
	 * @param unknown $param
	 * @return Ambigous <number, boolean>
	 */
	public function deleteListByConditions($conditions, $param = array()){
		return $this->getDbConnection()->createCommand()->delete($this->tableName(), $conditions, $param);
	}	


}