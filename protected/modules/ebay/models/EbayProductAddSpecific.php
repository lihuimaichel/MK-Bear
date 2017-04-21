<?php
/**
 * @desc Ebay刊登属性model
 * @author lihy
 * @since 2016-03-28
 */
class EbayProductAddSpecific extends EbayModel{
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_ebay_product_add_specific';
    }
    
    
    /**
     * @desc 根据addID获取ebay刊登产品信息
     * @param unknown $addID
     * @return mixed
     */
    public function getEbayProductAddSpecificByAddID($addID, $indexKey = null){
    	$specificList = $this->getDbConnection()->createCommand()->from($this->tableName())
    							->where("add_id=:add_id", array(':add_id'=>$addID))
    							->queryAll();
    	if($specificList && $indexKey){
    		$newSpecificList = array();
    		foreach ($specificList as $val){
    			if(!isset($val[$indexKey])) break;
    			$newSpecificList[$val[$indexKey]] = $val;
    		}
    		$specificList = $newSpecificList;
    	}
    	return $specificList;
    }
    
    /**
     * @desc 保存产品刊登属性
     * @param unknown $data
     * @return Ambigous <number, boolean>
     */
    public function saveProductSpecificData($data){
    	return $this->getDbConnection()->createCommand()->insert($this->tableName(), $data);
    }
    
    /**
     * @desc 根据AddID删除产品全部属性
     * @param unknown $addID
     */
    public function deleteProductSpecificByAddId($addID){
    	return $this->getDbConnection()->createCommand()->delete($this->tableName(), "add_id=:add_id", array(":add_id"=>$addID));
   	}
    
}