<?php
/**
 * @desc ebay价格修改日志
 * @since 2016-12-20
 */
class EbayProductChangePriceLog extends EbayModel{

	
    public function tableName(){
    	return "ueb_ebay_log_price_change";
    }
    
    /**
     * @desc 添加数据
     * @param unknown $data
     * @return string|boolean
     */
    public function addData($data){
    	$res = $this->getDbConnection()->createCommand()->insert($this->tableName(), $data);
    	if($res)
    		return $this->getDbConnection()->getLastInsertID();
    	return false;
    }
    
}