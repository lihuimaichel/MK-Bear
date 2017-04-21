<?php
/**
 * Shopee 物流相关
 * @author	Rex
 * @since	2015-10-10
 */

class ShopeeShipment extends ShopeeModel {
	
	const EVNET_POSTORDER = 'post_order';		//创建发货
	const EVENT_UPLOAD_TRACK = 'upload_track';		//上传跟踪号
	const EVENT_ADVANCE_SHIPPED = 'advance_shipped';		//提前发货
	
	/** @var int 账号ID*/
	public $_accountID = null;
	
	/** @var string 异常信息*/
	public $exception = null;
	
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}
	
	/**
	 * 切换数据库连接
	 */
	public function getDbKey() {
		return 'db_oms_order';
	}
	
	/**
	 * 数据库表名
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
	 * @desc 获取异常信息
	 * @return string
	 */
	public function getExceptionMessage(){
		return $this->exception;
	}
	
	/**
	 * @desc 设置账号id
	 * @param integer $accountID
	 */
	public function setAccountId($accountID){
		$this->_accountID = $accountID;
	}
	
	/**
	 * @desc 上传跟踪号
	 * @param array $shippedData
	 */
	public function uploadTrackingNumber( $shippedData ){
		try {
			$request = new SetTrackingNoRequest();
			$request->setInfoList($shippedData);
			$response = $request->setAccount($this->_accountID)->setRequest()->sendRequest()->getResponse();
			if ( $request->getIfSuccess() ) {
				return $response;
			} else {
				$this->setExceptionMessage($request->getErrorMsg());
				return false;
			}
		} catch (Exception $e) {
			$this->setExceptionMessage($e->getMessage());
			return false;
		}
	}
	
}