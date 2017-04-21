<?php
/**
 * @desc Ebay物流相关
 * @author Gordon
 * @since 2015-08-03
 */
class EbayShipment extends EbayModel{
    
	const EVENT_ADVANCE_SHIPPED = 'advance_shipped';		//提前发货
	const EVENT_UPLOAD_TRACK = 'upload_track';				//上传跟踪号
	
    
    const SERVICE_CONFIRM_SHIPPED = 1; //提前确认发货
    const SERVICE_UPLOAD_TRACK    = 2; //上传跟踪号
    
    /**@var 付款到上传的小时数*/
    const HOUR_UPLOAD = 48;
    
    /** @var int 账号ID*/
    public $_accountID = null;
    
    /** @var string 异常信息*/
    public $exception = null;
    
    /** @var string 错误code*/
    public $errorcode = null;
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 切换数据库连接
     * @see AliexpressModel::getDbKey()
     */
    public function getDbKey() {
        return 'db_oms_order';
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_order';
    }
    
    /**
     * @desc 设置异常信息
     * @param string $message
     */
    public function setExceptionMessage($message){
        $this->exception = $message;
    }
    
    /**
     * @desc 设置错误code
     * @param string $errorcode
     */
    public function setErrorcode($errorcode){
    	$this->errorcode = $errorcode;
    }
    
    /**
     * @desc 获取错误code
     * @return string
     */
    public function getErrorcode(){
    	return $this->errorcode;
    }
    
    /**
     * @desc 获取异常信息
     * @return string
     */
    public function getExceptionMessage(){
        return $this->exception;
    }
    
    /**
     * @desc 声明发货
     * @param array $shippedData
     */
    public function uploadSellerShipment( $shippedData = array() ) {
    	
    	$itemID 		= $shippedData ['item_id'];
    	$transactionID  = $shippedData ['transaction_id'];
    	$orderID 		= $shippedData ['platform_order_id'];
    
    	$shipment = array ();
    
    	$lineItem = array (
    			'ItemID' 		=> $shippedData ['item_id'],
    			'TransactionID' => $shippedData ['transaction_id']
    	);
    
    	//没有追踪号只标记发货
    	if (! isset ( $shippedData ['track_number'] ) || empty ( $shippedData ['track_number'] )) {
    		$shipmentLineItem ['LineItem'] = $lineItem;
    		$shipment ['ShipmentLineItem'] = $shipmentLineItem;
    	} else {
    		//有追踪号就上传追踪号
    		$shipmentLineItem ['LineItem'] 						= $lineItem;
    		$shipmentTrackingDetails ['ShipmentLineItem'] 		= $shipmentLineItem;
    		$shipmentTrackingDetails ['ShipmentTrackingNumber'] = $shippedData ['track_number'];
    		$shipmentTrackingDetails ['ShippingCarrierUsed'] 	= $shippedData ['shipped_carrier'];
    		$shipment ['ShipmentTrackingDetails'] 				= $shipmentTrackingDetails;
    	}
    
    	try {
    		$request = new CompleteSaleRequest ();
    		$request->setShipment ( $shipment );
    		$request->setOrderID ( $orderID );
    		$request->setItemID ( $itemID );
    		$request->setTransactionID ( $shippedData ['transaction_id'] );
    		if (! isset ( $shippedData ['track_number'] ) || empty ( $shippedData ['track_number'] )){
    			$request->setShipped ( true );
    		}
    			
    		$response = $request->setAccount ( $this->_accountID )->setRequest ()->sendRequest ()->getResponse ();
    		if ($request->getIfSuccess ()) {
    			return true;
    		} else {
    			$this->setExceptionMessage ( $request->getErrorMsg () );
    			return false;
    		}
    	} catch ( Exception $e ) {
    		$this->setExceptionMessage ( $e->getMessage () );
    		return false;
    	}
    }

    /**
     * @desc 设置账号ID
     */
    public function setAccountID($accountID){
    	$this->_accountID = $accountID;
    }
}