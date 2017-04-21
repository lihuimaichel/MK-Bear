<?php 
/**
 * @desc paypal交易 Model
 * @author Gordon
 */
class OrderPaypalTransactionRecord extends OrdersModel{
    
	const RECEIVE_TYPE_YES     = 1; //收款
	const RECEIVE_TYPE_NO      = 2; //退款

    
    public static function model($className = __CLASS__) {
    	return parent::model($className);
    
    }
    
    /**
     * @desc 表名
     * @see CActiveRecord::tableName()
     */
    public function tableName(){
        return 'ueb_paypal_transaction_record';
    }

    /**
     * [getOneByCondition description]
     * @param  string $fields [description]
     * @param  string $where  [description]
     * @param  mixed $order  [description]
     * @return [type]         [description]
     * @author yangsh
     */
    public function getOneByCondition($fields='*', $where='1',$order='') {
        $cmd = $this->dbConnection->createCommand();
        $cmd->select($fields)
            ->from(self::tableName())
            ->where($where);
        $order != '' && $cmd->order($order);
        $cmd->limit(1);
        return $cmd->queryRow();
    }    

    public function insert($data) {
        return $this->dbConnection->createCommand()
                ->insert($this->tableName(),$data);
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