<?php
/**
 * @desc 速卖通线上价格更新log表
 * @author hanxy
 *
 */
class AliexpressUpdatePriceAll extends AliexpressModel {
	
	public function tableName() {
		return 'ueb_aliexpress_update_price_all';
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

	/**
	 * @desc 获取sku列表
	 * @param unknown $conditions
	 * @param unknown $params
	 * @param unknown $limit
	 * @param unknown $offset
	 */
	public function getOneByCondition($conditions, $params){
		return $this->getDbConnection()->createCommand()
						->from($this->tableName())
						->where($conditions, $params)
						->order('id desc')
						->queryRow();					
	}
}