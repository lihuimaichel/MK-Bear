<?php
/**
 * @desc wish线上海外仓库存置零日志
 *
 */
class WishOverseaZeroStockLog extends WishModel {
	/** @var 把库存置为0 */
	const EVENT_ZERO_STOCK = 'oversea_zero_stock';
	/** @var 恢复库存 */
	const EVENT_RESTORE_STOCK = 'restore_oversea_stock';

	/**
	 * (non-PHPdoc)
	 * @see CActiveRecord::tableName()
	 */
	public function tableName() {
		return 'ueb_wish_oversea_zero_stock_log';
	}
	
	
	public static function model($className = __CLASS__) {
		return parent::model($className);		
	}
	
	/**
	 * @desc 保存信息
	 * @param unknown $params
	 * @return boolean|Ambigous <number, boolean>
	 */
	public function saveData($params){
		if(empty($params)) return false;
		return $this->getDbConnection()->createCommand()
		->insert($this->tableName(), $params);
	}
	
	/**
	 * @desc 更新
	 * @param unknown $data
	 * @param unknown $id
	 * @return Ambigous <number, boolean>
	 */
	public function updateDataByID($data, $id){
		if(!is_array($id)) $id = array($id);
		return $this->getDbConnection()
		->createCommand()
		->update($this->tableName(), $data, "id in(". implode(",", $id) .")");
	}

}