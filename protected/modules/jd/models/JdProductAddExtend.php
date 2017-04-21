<?php
/**
 * @desc 京东商品刊登扩展表
 * @author zhangf
 *
 */
class JdProductAddExtend extends JdModel {
	
	/**
	 * (non-PHPdoc)
	 * @see UebModel::model()
	 */
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see CActiveRecord::tableName()
	 */
	public function tableName() {
		return 'ueb_jd_product_add_extend';
	}
	
	/**
	 * @desc 根据刊登ID获取刊登扩展信息
	 * @param unknown $addID
	 * @return mixed
	 */
	public function getInfoByAddID($addID) {
		return $this->dbConnection->createCommand()
			->from(self::tableName())
			->where("add_id = :add_id", array(':add_id' => $addID))
			->select("*")
			->queryRow();
	}
}