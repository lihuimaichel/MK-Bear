<?php
class AmazonCategoryAttributeValueXSD extends AmazonModel {

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
		return 'ueb_amazon_xsd_category_attribute_value';
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