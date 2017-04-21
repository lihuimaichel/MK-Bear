<?php
/**
 * @desc 订单交易信息
 * @author Gordon
 */
class OrderTransaction extends OrdersModel {
	
	/** @var 检查多少天未付款的订单 */
	const CHECK_NOPAY_DAYS = 60;
	
	/** @var 接收类型 */
	const RECEIVE_TYPE_YES = 1; // 收款
	const RECEIVE_TYPE_NO = 2; // 退款
	const paymentstatus_completed = 'Completed'; // 付款完成
	const paymentstatus_refunded = 'Refunded'; // 退款
	public static function model($className = __CLASS__) {
		return parent::model ( $className );
	}
	
	/**
	 * 表名
	 * 
	 * @see CActiveRecord::tableName()
	 */
	public function tableName() {
		return 'ueb_order_transaction';
	}
	
	/**
	 * 根据交易号获取交易信息
	 * 
	 * @param string $transactionID        	
	 */
	public function getOrderTransactionInfoByTransactionID($transactionID) {
		return $this->dbConnection->createCommand ()->select ( '*' )->from ( self::tableName () )->where ( 'transaction_id = "' . $transactionID . '"' )->queryRow ();
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
	 * 保存交易信息
	 * 
	 * @param string $transactionID        	
	 * @param string $orderID        	
	 * @param tinyint $isFirst        	
	 */
	public function saveTransactionRecord($transactionID, $orderID, $param = array(), $transactionMethod = '', $transactionMethodData = array()) {
		$transactionData = array (
				'transaction_id' => $transactionID,
				'parent_transaction_id' => '',
				'order_id' => $orderID 
		);
		$insertData = array_merge ( $transactionData, $param );
		return $this->dbConnection->createCommand ()->replace ( self::tableName (), $insertData );
	}
	
	/**
	 * 获取ebay未获取交易信息的订单
	 * 
	 * @param unknown $accountID
	 *        	->andWhere(' o.platform_code = '.'"EB"') ->andWhere(' o.platform_order_id = '.'"121946952261-1623803160002"')
	 */
	public function getEbayNoTransactionDataOrder($accountID) {
		$transactions = $this->dbConnection->createCommand ()->select ( ' o.*,ot.first,ot.transaction_id, ot.account_id as paypalAccountID ' )->from ( self::tableName () . ' AS ot' )->leftJoin ( Order::model ()->tableName () . ' AS o', 'o.order_id = ot.order_id' )
		->where ( ' o.platform_code = "' . Platform::CODE_EBAY . '" ' )
		->andWhere ( ' o.account_id = "' . $accountID . '" ' )
		->andWhere ( ' ot.amt = 0 ' )
		->andWhere ( ' o.created_time BETWEEN SUBDATE(NOW(),' . self::CHECK_NOPAY_DAYS . ') AND NOW() ' )
		->order ( ' o.order_id desc  ' )->queryAll ();
		return $transactions;
	}
	
	/**
	 * 获取单个交易信息
	 *
	 * @param unknown $accountID
	 *        	->andWhere(' o.platform_code = '.'"EB"') ->andWhere(' o.platform_order_id = '.'"121946952261-1623803160002"')
	 */
	public function getSingleTransactionData($accountID,$poid) {
		$transactions = $this->dbConnection->createCommand ()->select ( ' o.*,ot.first,ot.transaction_id,ot.account_id as paypalAccountID ' )->from ( self::tableName () . ' AS ot' )->leftJoin ( Order::model ()->tableName () . ' AS o', 'o.order_id = ot.order_id' )
		->where ( ' o.platform_code = "' . Platform::CODE_EBAY . '" ' )
		->andWhere ( ' o.platform_order_id = "' . $poid . '" ' )
		->andWhere ( ' o.account_id = "' . $accountID . '" ' )
		->andWhere ( ' o.payment_status = 1 ' )
		->order ( ' o.order_id desc  ' )->queryAll ();
		return $transactions;
	}
	
	/**
	 * 获取ebay单个订单的获取交易信息
	 * 
	 * @param unknown $accountID        	
	 */
	public function getEbayExcepTransactionDataOrder($accountID) {
		$transactions = $this->dbConnection->createCommand ()->select ( ' o.*,ot.first,ot.transaction_id,ot.account_id as paypalAccountID' )->from ( Order::model ()->tableName () . ' AS o' )->leftJoin ( self::tableName () . ' AS ot', 'o.order_id = ot.order_id' )
		->where ( ' o.platform_code = "' . Platform::CODE_EBAY . '" ' )
		->andWhere ( ' o.account_id = "' . $accountID . '" ' )
		->andWhere ( ' o.payment_status = 1 ' )
		->andWhere ( ' o.created_time BETWEEN SUBDATE(NOW(),' . self::CHECK_NOPAY_DAYS . ') AND NOW() ' )
		->order ( ' o.order_id desc ' )->queryAll ();
		return $transactions;
	}
	
	/**
	 * 获取email出错的交易信息
	 *
	 * @param unknown $accountID
	 */
	public function getEbayEmailExcepTransactionDataOrder($accountID) {
		$transactions = $this->dbConnection->createCommand ()->select ( ' o.*,ot.first,ot.transaction_id,ot.account_id as paypalAccountID ' )->from ( Order::model ()->tableName () . ' AS o' )->leftJoin ( self::tableName () . ' AS ot', 'o.order_id = ot.order_id' )
		->where ( ' o.platform_code = "' . Platform::CODE_EBAY . '" ' )
		->andWhere ( ' o.account_id = "' . $accountID . '" ' )
		->andWhere ( ' o.payment_status = 1 ' )
		->andWhere ( ' o.created_time BETWEEN SUBDATE(NOW(),' . self::CHECK_NOPAY_DAYS . ') AND NOW() ' )
		->order ( ' o.order_id desc ' )->queryAll ();
		return $transactions;
	}
	
	/**
	 * 获取ebay未获取交易信息的订单
	 * 
	 * @param unknown $accountID        	
	 */
	public function getEbayNoTransactionDataOrder2($accountID) {
		$transactions = $this->dbConnection->createCommand ()->select ( ' o.*,ot.first,ot.transaction_id,ot.account_id as paypalAccountID ' )->from ( self::tableName () . ' AS ot' )->leftJoin ( Order::model ()->tableName () . ' AS o', 'o.order_id = ot.order_id' )->leftJoin ( OrderPaypalTransactionRecord::model ()->tableName () . ' AS p', ' ot.transaction_id=p.transaction_id ' )->where ( ' o.platform_code = "' . Platform::CODE_EBAY . '" ' )->andWhere ( ' o.account_id = "' . $accountID . '" ' )->andWhere ( ' o.order_id like "CO1604%" ' )->andWhere ( ' p.transaction_id is null ' )->order ( ' o.order_id desc,ot.first desc ' )->queryAll ();
		return $transactions;
	}

}