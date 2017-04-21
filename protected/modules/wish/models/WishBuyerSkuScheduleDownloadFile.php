<?php 

class WishBuyerSkuScheduleDownloadFile extends WishModel{

	private $_exceptionMsg = '';
	
    public static function model($className = __CLASS__) {
    	return parent::model($className);
    }
    
    /**
     * @desc 表名
     * @see CActiveRecord::tableName()
     */
    public function tableName(){
        return 'ueb_wish_buyer_sku_schedule_download_file';
    }
    
    /**
     * @desc 保存
     * @param string $transactionID
     * @param string $orderID
     * @param array $param
     */
    public function saveRecord($data){
        return $this->dbConnection->createCommand()->replace($this->tableName(), $data);
    }
    
    
    public function setExceptionMsg($msg){
    	$this->_exceptionMsg = $msg;
    }
    
    public function getExceptionMsg(){
    	return $this->_exceptionMsg;
    	
    }
}

?>