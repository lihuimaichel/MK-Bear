<?php
/**
 * @desc AMAZON listing 报告类
 * @author zhangF
 *
 */
class AmazonListingReport extends AccountListingReport {
	
	/**
	 * @desc 设置主键
	 * @return string
	 */
	public function primaryKey() {
		return 'iid';
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
		return 'ueb_amazon_list_report';
	}
}