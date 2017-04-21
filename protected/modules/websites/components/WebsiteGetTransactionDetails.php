<?php
Yii::import('application.modules.services.components.*');
/**
 * @author xiej
 */
class WebsiteGetTransactionDetails {
	
	//付款网站
	public $_platformCode = NULL;
	protected $_customerObj = null;
	/**
	 * 
	 * @param String $platformCode
	 */
	public function setPlatformCode($platformCode){
		$this->_platformCode = $platformCode;
	}
	/**
	 * 待重写 不可用
	 * @param unknown $detailObj
	 */
	public function setCustomer($detailObj){
		$this->_customerObj->customer_name = $detailObj->PayerInfo->Address->Name;
		$this->_customerObj->address1 = $detailObj->PayerInfo->Address->Street1;
		$this->_customerObj->address2 = $detailObj->PayerInfo->Address->Street2;
		$this->_customerObj->buyer_id = $detailObj->PaymentItemInfo->Auction->BuyerID;
		$this->_customerObj->email = $detailObj->PayerInfo->Payer;
		$this->_customerObj->country = $detailObj->PayerInfo->Address->CountryName;
		$this->_customerObj->ship_to_name = $detailObj->PayerInfo->Address->Name;
		$this->_customerObj->tel = '';
		$this->_customerObj->city = $detailObj->PayerInfo->Address->CityName;
		$this->_customerObj->state_province = $detailObj->PayerInfo->Address->StateOrProvince;
		$this->_customerObj->zip = $detailObj->PayerInfo->Address->PostalCode;
		$this->_customerObj->add_time = date('Y-m-d H:i:s');
		$this->_customerObj->datafrom = UebModel::model('Customer')->getDataFromPaypal();
		$this->_customerObj->update_time = date('Y-m-d H:i:s');
	}
	/**
	 * 交易信息
	 * @param String $order_id
	 * @param string $transactionId
	 * @param String $platformCode
	 * @param array $Obj
	 */
	public function setPaypalTransaction($transactionId,$order_id,$platformCode,$Obj){
		return OrderTransaction::model()->saveTransactionRecord($transactionId, $order_id, array(
    		'order_id'              => $order_id,
    		'first'                 => 1,
    		'is_first_transaction'  => 1,
    		'receive_type'          => OrderTransaction::RECEIVE_TYPE_YES,
    		'account_id'            => isset($Obj['paypal_account_id']) ? $Obj['paypal_account_id'] : '',
    		'parent_transaction_id' => '',
			'order_pay_time'        => $Obj['ORDERTIME'],
    		'amt'                   => isset($Obj['AMT']) ? $Obj['AMT'] : 0,
    		'fee_amt'               => isset($Obj['FEEAMT']) ? $Obj['FEEAMT'] : 0,
    		'currency'              => $Obj['CURRENCYCODE'],
    		'payment_status'        => $Obj['PAYMENTSTATUS'],
		    'platform_code'         => $platformCode
		));//保存交易信息
	}
	/**
	 * 交易记录 record
	 * @param array $Obj
	 * @param string $transactionId
	 * @param string $platformCode
	 * @param string $orderId
	 */
	public function setPaypalTransactionRecord($transactionId,$order_id,$platformCode,$Obj){
		return  OrderPaypalTransactionRecord::model()->savePaypalRecord($transactionId, $order_id, array(
				'order_id'              => 	$order_id,
				'receive_type'          => 	OrderPaypalTransactionRecord::RECEIVE_TYPE_YES,
				'receiver_business'		=> 	$platformCode,
				'receiver_email' 		=> 	$Obj['RECEIVEREMAIL'],
				'receiver_id' 			=> 	$Obj['RECEIVERID'],
				'payer_id' 				=> 	$Obj['PAYERID'],
				'payer_name' 			=> 	isset($Obj['FIRSTNAME']) ? $Obj['FIRSTNAME'] : '' . ' ' . isset($Obj['LASTNAME']) ? $Obj['LASTNAME'] : '',
				'payer_email' 			=> 	$Obj['EMAIL'],
				'payer_status' 			=> 	$Obj['PAYERSTATUS'],
				'parent_transaction_id'	=>	'',
				'transaction_type'		=>	$Obj['TRANSACTIONTYPE'],
				'payment_type'			=>	$Obj['PAYMENTTYPE'],
				'order_time'			=>	$Obj['ORDERTIME'],
				'amt'					=>	isset($Obj['AMT']) ? $Obj['AMT'] : 0,
				'fee_amt'				=>	isset($Obj['FEEAMT']) ? $Obj['FEEAMT'] : 0,
				'tax_amt'				=>	isset($Obj['TAXAMT']) ? $Obj['TAXAMT'] : 0,
				'currency'				=>	$Obj['CURRENCYCODE'],
				'payment_status' 		=> 	$Obj['PAYMENTSTATUS'],
				'note'					=>	'',
				'modify_time'			=>	''
		));//保存交易信息 record
	}
	/**
	 * to get detail for transaction
	 * @param String $transactionId
	 * @param String $email
	 * @return Object
	 */
	public function getDetailByTransactionId($transactionId,$platformCode){
		//set $platformCode
		$this->setPlatformCode($platformCode);
		if($transactionObj = $this->checkTransactionDetail(PaypalAccount::model()->getPaypalTransactionByTransactionID($transactionId,$platformCode))){
			//未完成支付的
			if($transactionObj['PAYMENTSTATUS'] != 'Completed'){
				return false;
			}
			return $transactionObj;
		}else{
			throw new WebsitesException(Yii::t('Websites', 'Can not find any PaypalTransaction'));
		}
		return false;
	}
	/**
	 * check
	 */
	public function checkTransactionDetail($respone){
		if(isset($_GET['debug'])){var_dump($respone);die;}//debug
		if (empty($respone)) {return false;}
		$ack = strtoupper($respone['ACK']);
		if($ack!="SUCCESS" && $ack!="SUCCESSWITHWARNING"){
			return false;
		}else{
			return $respone;
		}
	}
}