<?php
class AmazonCategoryAttribute extends AmazonModel {

	public $_errorMsg;

	public static function model($className = __CLASS__) {
		return parent::model($className);
	}

	/**
	 * @desc 设置数据库名
	 * @return string
	 */
	public function getDbKey() {
		return 'db_amazon';
	}
	
	/**
	 * @desc 设置表名
	 * @see CActiveRecord::tableName()
	 */
	public function tableName() {
		return 'ueb_amazon_category_attribute';
	}

    /**
     * @desc 获取分类下指定属性
     * @param unknown $categoryID
     * @param unknown $attributeID
     * @return mixed
     */
    public function getCategoryAttribute($categoryID, $attributeID) {
    	return $this->getDbConnection()->createCommand()
    		->from(self::tableName())
    		->select("*")
    		->where("cid = :category_id", array(':category_id' => $categoryID))
    		->andWhere("id = :attribute_id", array(':attribute_id' => $attributeID))
    		->queryRow();
    }	

	
	/**
	 *  
	 * @desc 设置错误消息
	 * @param unknown $msg
	 */
	
	private function setErrorMsg($msg){
		$this->_errorMsg = $msg;
	}
	/**
	 * @desc 获取错误消息
	 * @return unknown
	 */
	public function getErrorMsg(){
		return $this->_errorMsg;
	}
}