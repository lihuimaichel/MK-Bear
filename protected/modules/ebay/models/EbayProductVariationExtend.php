<?php

class EbayProductVariationExtend extends EbayModel{
		
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_ebay_product_variation_extend';
    }


    public function addData($data){
    	if(empty($data)) return false;
    	$res = $this->getDbConnection()->createCommand()->insert($this->tableName(), $data);
    	if($res){
    		return $this->getDbConnection()->getLastInsertID();
    	}
    	return false;
    }
    
    public function updateData($data, $condition, $params = array()){
    	if(empty($data) || empty($condition)) return false;
    	$res = $this->getDbConnection()->createCommand()->update($this->tableName(), $data, $condition, $params);
    	return $res;
    }
    
    public function addOrUpdate($data){
    	if(empty($data['item_id']) || empty($data['sku'])){
    		return false;
    	}
    	//查看是否存在
    	$res = $this->getDbConnection()->createCommand()
    									->from($this->tableName())
    									->where('item_id=:item_id and sku=:sku', array(':item_id'=>$data['item_id'], ':sku'=>$data['sku']))
    									->queryRow();
    	if($res){
    		return $this->updateData($data, 'item_id=:item_id and sku=:sku', array(':item_id'=>$data['item_id'], ':sku'=>$data['sku']));
    	}else{
    		return $this->addData($data);
    	}
    }
    
}