<?php
/**
 * @desc amazon 订单模型
 * @author zhangF
 *
 */
class AmazonUploadTrack extends AmazonModel {
	
	const EVENT_NAME = 'uploadtracknum';
	
	/** @var object 拉单返回信息*/
	public $orderResponse = null;
	
	/** @var int 账号ID*/
	public $_accountID = null;
	
	/** @var string 异常信息*/
	public $exception = null;
	
	/** @var int 日志编号*/
	public $_logID = 0;	
	
	/** @var string 此次请求批次号 **/
	public $_batchNo = null;
	
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
	 * @desc 设置账号ID
	 */
	public function setAccountID($accountID){
		$this->_accountID = $accountID;
	}	

	/**
	 * @desc 设置日志编号
	 * @param int $logID
	 */
	public function setLogID($logID){
		$this->_logID = $logID;
	}	
	
	/**
	 * @desc 上传跟踪号到平台
	 * @param array $itemData
	 */
	public function uploadTrackNum( $itemData ){
		//ini_set('display_errors', true);
		//error_reporting(E_ALL);
		if( count($itemData)<0 || !$itemData){
			return false;
		}
		try {
			//获取xml串
			$request = new SubmitFeedRequest();
			$request->setAccount($this->_accountID);
			$merchantId = $request->getMerchantID();
			$feedContent = $this->getXmlData( $itemData,$merchantId );
			//echo $feedContent;exit;
			$request->setPurgeAndReplace(false);
			$request->setFeedContent($feedContent);
			$request->setBatchNo($this->_batchNo);
			//获取feedSubmissionId
			$response = $request->setRequest()->sendRequest()->getResponse();
			if (!empty($response)) {
				sleep(60);
				$request1 = new GetFeedSubmissionListRequest();
				$request1->setFeedSubmissionId($response);
				$request1->setBatchNo($this->_batchNo);
				//获取未处理完成的feed
				$response1 = $request1->setAccount($this->_accountID)->setRequest()->sendRequest()->getResponse();
				$response2 = $response1;
				$i = 0;
				while(!empty($response2) && $i<5){
					sleep(20);
					foreach($response2 as $val){
						foreach($val as $v){
							$request1->setFeedSubmissionId($v);
							$request1->setBatchNo($this->_batchNo);
							$response2 = $request1->setAccount($this->_accountID)->setRequest()->sendRequest()->getResponse();
						}
					}
					$i++;
				}
				
			} else {
				$this->setExceptionMessage($request->getErrorMsg());
				return false;
			}
		} catch (Exception $e) {
			$this->setExceptionMessage($e->getMessage());
			return false;
		}
		return true;
	}
	
	
    /**
	 * @desc 获取异常信息
	 * @return string
	 */
	public function getExceptionMessage(){
		return $this->exception;
	}
	
	/**
	 * @desc 设置异常信息
	 * @param string $message
	 */
	public function setExceptionMessage($message){
		$this->exception = $message;
	}
	
	/**
	 * @desc 获取提交的xml代码
	 */
	public function getXmlData( $itemData,$merchantId ){
		$feedHeader = '<?xml version="1.0" encoding="UTF-8"?>
				<AmazonEnvelope xsi:noNamespaceSchemaLocation="amzn-envelope.xsd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
				    <Header>
				        <DocumentVersion>1.01</DocumentVersion>
				        <MerchantIdentifier>'.$merchantId.'</MerchantIdentifier>
				    </Header>
				    <MessageType>OrderFulfillment</MessageType>';
	
		$feedMain = '';
		$i = 1;
		$insertSql = '';
		$batchNo = 'SN'.date('YmdHis').rand(100,999).count($itemData);
		$this->_batchNo = $batchNo;
		$packageIdStr = '';
		foreach($itemData as $key => $val){
			//if(strtolower(substr($val['amazon_order_id'],0,2)) == 're') continue;
			$shipDate = substr($val['ship_date'],0,10).'T'.substr($val['ship_date'],11);
			
			$feedMain .= '<Message>
						        <MessageID>'.$i.'</MessageID>
						        <OperationType>Update</OperationType>
						        <OrderFulfillment>
						            <AmazonOrderID>'.$val['amazon_order_id'].'</AmazonOrderID>
						            <FulfillmentDate>'.$shipDate.'</FulfillmentDate>
						            <FulfillmentData>
						                <CarrierName>'.$val['carrier_name'].'</CarrierName>
						                <ShipperTrackingNumber>'.$val['tracking_number'].'</ShipperTrackingNumber>
						            </FulfillmentData>
						            <Item>
						                <AmazonOrderItemCode>'.$val['item_id'].'</AmazonOrderItemCode>
						                <Quantity>'.$val['qty'].'</Quantity>
						            </Item>
						        </OrderFulfillment>
						    </Message>';
			$i++;
			
			//$packageIdStr .= ",'".$val['package_id']."'";
			if($val['is_old_type']){
				$insertSql .= ",('".$val['amazon_order_id']."',now(),'".$batchNo."','".$val['package_id']."','','".$val['carrier_name']."','".$val['tracking_number']."')";
			}else{
				$insertSql .= ",('".$val['amazon_order_id']."',now(),'".$batchNo."','".$val['package_id']."','".$val['ship_date']."','".$val['carrier_name']."','".$val['tracking_number']."')";
			}
		}
		$feedFoot = '</AmazonEnvelope>';
		if(substr($insertSql,2)){
			$sql = "insert into market_amazon.".AmazonUploadTnLog::model()->tableName()."(amazon_order_id,create_time,batch_no,package_id,ship_date,carrier_name,tracking_number) values ".substr($insertSql,1);
			Yii::app()->db->createCommand($sql)->execute();
		}
		return $feedHeader.$feedMain.$feedFoot;
		 
	}
}