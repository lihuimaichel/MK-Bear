<?php
/**
 * @desc　aliexpress listing 报告类
 * @author zhangF
 *
 */
class AliexpressListingReport extends AccountListingReport {
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
		return 'db_aliexpress';
	}
	
	/**
	 * @desc 设置表名
	 * @see CActiveRecord::tableName()
	 */
	public function tableName() {
		return 'ueb_aliexpress_list_report';
	}
}