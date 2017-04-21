<?php
/**
 * sku销量临时表
 * @since	2017-01-17
 */

class SkuSales extends CommonModel {
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}
	
	public function tableName() {
		return 'ueb_sku_sales';
	}	
}