<?php
/**
 * @desc 产品刊登属性表
 * @author zhangf
 *
 */
class AliexpressProductAddAttribute extends AliexpressModel {
	
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
		return 'ueb_aliexpress_product_add_attribute';
	}
	
	/**
	 * @desc 产品刊登属性
	 * @param unknown $addID
	 * @return multitype:
	 */
	public function getProductAddAttributes($addID) {
		$attributes = array();
		$res = $this->findAll("add_id = :add_id", array(':add_id' => $addID));
		if (!empty($res)) {
			foreach ($res as $key =>$row) {
				if (!empty($row->attribute_id))
					$attributes[$key]['attrNameId'] = $row->attribute_id;
				if (!empty($row->attribute_name))
					$attributes[$key]['attrName'] = $row->attribute_name;
				if (!empty($row->value_id))
					$attributes[$key]['attrValueId'] = $row->value_id;
				if (!empty($row->value_name))
					$attributes[$key]['attrValue'] = $row->value_name;
			}
		}
		return $attributes;
	}
	
	/**
	 * @desc 获取刊登的普通属性
	 * @param unknown $addID
	 * @return unknown
	 */
	public function getProductAttributes($addID, $isCustom = 0) {
		$productAttributes = array();
		$command = $this->getDbConnection()->createCommand()
		->from(self::tableName())
		->where("add_id = :add_id and is_custom = :is_custom", array(':add_id' => $addID, ':is_custom' => $isCustom));
		$res =	$command->queryAll();
		return $res;
	}	


	/**
	 * 用sql语句插入数据
	 */
	public function insertBySql($insertFields,$insertData){
		$insertSql = "INSERT INTO ".self::tableName()." (".$insertFields.") VALUES".$insertData;
		return $this->dbConnection->createCommand($insertSql)->execute();
	}

	/**
	 * 用sql语句更新数据
	 */
	public function updateBySql($updateData,$updateWhere){
		return $this->dbConnection->createCommand()->update(self::tableName(), $updateData, $updateWhere );
	}


	/**
	 * [getOneByCondition description]
	 * @param  string $fields [description]
	 * @param  string $where  [description]
	 * @return [type]         [description]
	 */
	public function getOneByCondition($fields='*', $where='1') {
		$cmd = $this->dbConnection->createCommand();
		$cmd->select($fields)
			->from(self::tableName())
			->where($where);
		$cmd->limit(1);
		return $cmd->queryRow();
	}
}