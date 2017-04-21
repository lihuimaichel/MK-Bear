<?php
/**
 * @desc 亚马逊已停售sku
 * @author hanxy
 * @since 2016-11-07
 */ 

class AmazonStoppedSellingSku extends AmazonModel{
			
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}
	
	/**
	 * @desc 数据库表名
	 * @see CActiveRecord::tableName()
	 */
	public function tableName() {
		return 'ueb_amazon_stopped_selling_sku';
	}


	/**
	 * 更新数据
	 */
	public function update($data, $id){
		return $this->getDbConnection()->createCommand()->update(self::tableName(), $data, "id = ".$id);
	}
}