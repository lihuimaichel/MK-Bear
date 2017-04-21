<?php 

class LazadaProductAddPreLog extends LazadaModel{

	private $_exceptionMsg = '';
	
    public static function model($className = __CLASS__) {
    	return parent::model($className);
    }
    
    /**
     * @desc 表名
     * @see CActiveRecord::tableName()
     */
    public function tableName(){
        return 'ueb_lazada_product_add_pre_log';
    }
    
    /**
     * @desc 保存
     * @param string $transactionID
     * @param string $orderID
     * @param array $param
     */
    public function addLogData($data){
        return $this->dbConnection->createCommand()->replace($this->tableName(), $data);
    }
}

?>