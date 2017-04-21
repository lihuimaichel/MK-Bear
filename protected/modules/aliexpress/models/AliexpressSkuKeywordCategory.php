<?php
/**
 * @desc aliexpress 产品扩展MODEL
 * @author zhangF
 *
 */
class AliexpressSkuKeywordCategory extends AliexpressModel {
	
	/** @var string 错误信息 */
	protected $_errorMessage = '';
	
	/**
	 * @desc 生成model
	 * @param system $className
	 * @return Ambigous <CActiveRecord, unknown, multitype:>
	 */
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}


	/**
	 * (non-PHPdoc)
	 * @see CActiveRecord::tableName()
	 */
	public function tableName() {
		return 'ueb_aliexpress_sku_keyword_category';
	}
	
	
	public function getPendingMatchCategorySKUList($limit = 100){
		return $this->getDbConnection()->createCommand()
								->from($this->tableName())
								->select("sku")
								->where("status=0")
								->limit($limit)
								->queryColumn();
	}
	
	/**
	 * @desc 根据SKU更新数据
	 * @param unknown $sku
	 * @param unknown $data
	 */
	public function updateDataBySku($sku, $data){
		return $this->getDbConnection()->createCommand()->update($this->tableName(), $data, "sku=:sku", array(":sku"=>$sku));
	}
	
}