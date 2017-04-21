<?php
/**
 * @desc 订单交易信息主表
 */
class OrderPaypalTransaction extends OrdersModel {
	
	public static function model($className = __CLASS__) {
		return parent::model ( $className );
	}
	
	/**
	 * 表名
	 * 
	 * @see CActiveRecord::tableName()
	 */
	public function tableName() {
		return 'ueb_order_paypal_transaction';
	}
	
	/**
	 * 根据交易号获取交易信息
	 * 
	 * @param string $transactionID        	
	 */
	public function getByTransactionID($transactionID) {
		return $this->dbConnection->createCommand ()
			->select ( '*' )
			->from ( self::tableName () )
			->where ( 'transaction_id = "' . $transactionID . '"' )
			->queryRow ();
	}

    /**
     * getOneByCondition
     * @param  string $fields
     * @param  string $where 
     * @param  mixed $order  
     * @return array           
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

    /**
     * getListByCondition
     * @param  string $fields
     * @param  string $where 
     * @param  mixed $order  
     * @return array      
     */
    public function getListByCondition($fields='*', $where='1',$order='') {
        $cmd = $this->dbConnection->createCommand();
        $cmd->select($fields)
            ->from(self::tableName())
            ->where($where);
        $order != '' && $cmd->order($order);
        return $cmd->queryAll();
    }	

    public function insert($data) {
        return $this->dbConnection->createCommand()
                ->insert($this->tableName(),$data);
    }

    public function update($data,$transactionID) {
        return $this->dbConnection->createCommand()
                ->update($this->tableName(),$data,"transaction_id='{$transactionID}'");
    }    	

}