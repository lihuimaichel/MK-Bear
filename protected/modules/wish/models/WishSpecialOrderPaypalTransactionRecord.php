<?php 
/**
 * @desc paypal交易 Model
 * @author Gordon
 */
class WishSpecialOrderPaypalTransactionRecord extends WishModel{
    
	const RECEIVE_TYPE_YES     = 1; //接收
	const RECEIVE_TYPE_NO      = 2; //发起
	
	
    
    public static function model($className = __CLASS__) {
    	return parent::model($className);
    
    }
    
    /**
     * @desc 表名
     * @see CActiveRecord::tableName()
     */
    public function tableName(){
        return 'ueb_wish_paypal_transaction_record';
    }
    
    /**
     * @desc 保存paypal交易信息
     * @param string $transactionID
     * @param string $orderID
     * @param array $param
     */
    public function savePaypalRecord($transactionID, $orderID, $param = array()){
        $transactionData = array(
            'transaction_id'        => $transactionID,
            'order_id'              => $orderID,
        );
        $insertData = array_merge($transactionData, $param);
        return $this->dbConnection->createCommand()->replace(self::tableName(), $insertData);
    }
}

?>