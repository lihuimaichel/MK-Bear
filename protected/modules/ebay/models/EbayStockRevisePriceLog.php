<?php
/**
 * @desc Ebay下架操作记录表
 * @author hanxy
 * @since 2016-10-17
 */
class EbayStockRevisePriceLog extends EbayModel{
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_ebay_stock_revise_price_log';
    }


   	public function addData($data){
   		$res = $this->getDbConnection()->createCommand()->insert($this->tableName(), $data);
   		if($res){
   			return $this->getDbConnection()->getLastInsertID();
   		}
   		return false;
   	}

   	public function updateDataByID($id, $updateData){
   		return $this->getDbConnection()->createCommand()->update($this->tableName(), $updateData, "id='{$id}'");
   	}
   	
   	
   	public function getRevisePriceLogRow($condition, $param = null){
   		return $this->getDbConnection()->createCommand()
	 	  			->from($this->tableName())
	   				->select("*")
	   				->where($condition, $param)
	   				->order('id desc')
	   				->queryRow();
   	}
   	
   	public function checkExistsReviseLog($skuOnline, $itemID, $accountID, $siteID, $withinDay = 0){
   		$row = $this->getDbConnection()->createCommand()
   			->from($this->tableName())
   			->select("id")
   			->where("sku_online=:sku_online and item_id=:item_id and account_id=:account_id and site_id=:site_id and status=:status", 
   					array(':sku_online'=>$skuOnline, ':item_id'=>$itemID, ':account_id'=>$accountID, ':site_id'=>$siteID, ':status'=>1))
   			->andWhere($withinDay ? "create_time>".date("Y-m-d", time()-$withinDay*24*3600) : '1')
   			->order('id desc')
   			->queryRow();
   		return $row ? true: false;
   	}
   	
   	public function checkIsRestoreByID($id){
   		$row = $this->getDbConnection()->createCommand()
   		->from($this->tableName())
   		->select("id")
   		->where("id=:id and restore_status=:restore_status",
   				array(':id'=>$id, ':restore_status'=>1))
   				->queryRow();
   		return $row ? true: false;
   	}
   	
   	
   	public function getPendingRestoreList($limit, $offset = 0, $accountID = null, $siteID = null, $itemID = null){
   		return $this->getDbConnection()->createCommand()
   					->from($this->tableName())
   					->select("id,sku,account_id,site_id, sku_online,item_id,old_price")
   					->where("restore_status=:restore_status and status=:status", array(':restore_status'=>0, ':status'=>1))
   					->andWhere($accountID ? "account_id='{$accountID}'" : '1')
   					->andWhere($siteID ? "site_id='{$siteID}'" : '1')
   					->andWhere($itemID ? "item_id='{$itemID}'" : '1')
   		 			->limit($limit, $offset)
   					->queryAll();
   	}
}