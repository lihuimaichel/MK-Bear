<?php
/**
 * @desc Ebay产品扩展
 * @author Gordon
 * @since 2015-07-31
 */
class EbayProductExtend extends EbayModel{
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_ebay_product_extend';
    }
    /**
     * @desc 获取单个信息
     * @param unknown $conditions
     * @param unknown $params
     * @param string $select
     * @return mixed
     */
    public function getProductExtendInfoByCondition($conditions, $params, $select = "*"){
    	if(empty($select)){
    		$select = "*";
    	}
    	return $this->getDbConnection()->createCommand()
    				->from($this->tableName())
    				->select($select)
    				->where($conditions, $params)
    				->queryRow();
    	
    }
    /**
     * @desc 保存产品其他信息
     * @param unknown $params
     * @return boolean|Ambigous <number, boolean>
     */
    public function saveProductExtend($params){
    	if(empty($params)) return false;
    	return $this->getDbConnection()->createCommand()
    				->insert($this->tableName(), $params);
    }
    /**
     * @desc 根据主键id更新数据
     * @param unknown $id
     * @param unknown $params
     * @return boolean|Ambigous <number, boolean>
     */
    public function updateProductExtendByID($id, $params){
    	if(empty($id) || empty($params)) return false;
    	return $this->getDbConnection()->createCommand()
    				->update($this->tableName(), $params, "id=:id", array(":id"=>$id));
    }
}