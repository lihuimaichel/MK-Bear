<?php
/**
 * @desc 订单交易信息
 * @author Gordon
 */
class WishSpecialOrderTransaction extends WishModel {
	
	/** @var 检查多少天未付款的订单 */
	const CHECK_NOPAY_DAYS = 60;
	
	/** @var 接收类型 */
	const RECEIVE_TYPE_YES = 1; // 接收
	const RECEIVE_TYPE_NO = 2; // 付款
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
		return 'ueb_wish_order_transaction';
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
	

	

}