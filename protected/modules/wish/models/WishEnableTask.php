<?php
class WishEnableTask extends WishModel {
	
	public function tableName(){
		return  'ueb_wish_enable_sku';
	}
	
	public static function model($className = __CLASS__){
		return parent::model($className);
	}

	/**
	 * @desc 添加数据
	 * @param unknown $data
	 */
	public function addData($data){
		return $this->getDbConnection()->createCommand()->insert(self::tableName(), $data);
	}

}

?>