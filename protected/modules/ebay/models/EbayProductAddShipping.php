<?php
/**
 * @desc Ebay刊登拍卖信息model
 * @author lihy
 * @since 2016-03-28
 */
class EbayProductAddShipping extends EbayModel{
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_ebay_product_add_shipping';
    }

    /**
     * @desc 获取物流信息
     * @param unknown $addID
     * @return mixed
     */
    public function getProductShippingInfoByAddID($addID){
    	return $this->getDbConnection()->createCommand()
    							->from($this->tableName())
    							->where("add_id={$addID}")
    							->order("id asc")
    							->queryAll();	
    }
    /**
     * @desc 返回最小的运输费用
     * @param unknown $addID
     * @return Ambigous <string, mixed, unknown>|number
     */
    public function getMiniShipingCostByAddID($addID){
    	$minShipCost = $this->getDbConnection()->createCommand()
				    	->from($this->tableName())
				    	->select("ship_cost")
				    	->where("add_id={$addID}")
				    	->order("ship_cost asc")
				    	->queryScalar();
    	if($minShipCost){
    		return $minShipCost;
    	}
    	return 0;
    }
    /**
     * @desc 保存数据
     * @param unknown $data
     * @return Ambigous <number, boolean>
     */
    public function saveData($data){
    	return $this->getDbConnection()->createCommand()->insert($this->tableName(), $data);
    }
    
    /**
     * @DEsc 删除
     * @param unknown $addID
     * @return Ambigous <number, boolean>
     */
    public function deleteAllByAddID($addID){
    	return $this->getDbConnection()->createCommand()->delete($this->tableName(), "add_id=".$addID);
    }
}