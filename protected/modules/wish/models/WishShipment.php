<?php
/**
 * Wish 物流相关
 * @author	Rex
 * @since	2015-10-10
 */

class WishShipment extends WishModel {
	
	const EVNET_POSTORDER = 'post_order';		//创建发货
	const EVENT_UPLOAD_TRACK = 'upload_track';		//上传跟踪号
	const EVENT_ADVANCE_SHIPPED = 'advance_shipped';		//提前发货
	const EVENT_UPLOAD_SPECIAL_TRACK = 'upload_special_track'; //上传特追踪号
	
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
	 * @desc 声明发货
	 * @param array $shippedData
	 */
	public function uploadSellerShipment( $shippedData ){
		 
		try {
			$request = new FulfillAnOrderRequest();
			$request->setTrackingProvider($shippedData['tracking_provider']);
			$request->setTrackingNumber($shippedData['tracking_number']);
			$request->setID($shippedData['id']);
			$response = $request->setAccount($this->_accountID)->setRequest()->sendRequest()->getResponse();
			if ( $request->getIfSuccess() ) {
				return true;
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
	 * 修改声明发货
	 * @param	array	$shippedData
	 * @return	bool
	 */
	public function modifySellerShipment( $shippedData ) {
		try {
			$request = new ModifyTrackingRequest();
			$request->setTrackingProvider($shippedData['tracking_provider']);
			$request->setTrackingNumber($shippedData['tracking_number']);
			$request->setID($shippedData['id']);
			$response = $request->setAccount($this->_accountID)->setRequest()->sendRequest()->getResponse();
			if ( $request->getIfSuccess() ) {
				return true;
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
	 * 获取支持wish邮的订单
	 * @param	array	$shipCodes
	 * @return	array
	 */
	public function getWishPostOrderList($packageID,$shipCodes=array()) {
		if (empty($shipCodes)) {
			$shipCodes = array('cm_wish','ghxb_wish','cm_gz_wish','ghxb_gz_wish');
		}
		
		$obj = $this->dbConnection->createCommand()
			->select('O.*,P.package_id')
			->from(self::tableName(). ' O')
			->leftJoin(OrderPackageDetail::model()->tableName().' PD', 'PD.order_id = O.order_id')
			->leftJoin(OrderPackage::model()->tableName().' P', 'P.package_id = PD.package_id')
			->leftJoin(OrderCreateOnline::model()->tableName().' OC', 'OC.order_id = O.order_id')
			->where('O.platform_code = "'.Platform::CODE_WISH.'" ')	//and O.account_id = 17
			->andWhere(' ( (O.complete_status in (1,2) and O.ship_status != 2) or P.is_repeat = 1 ) and O.payment_status = 1 and O.package_nums > 0 ')
			->andWhere(array('in','P.ship_code',$shipCodes))
			->andWhere('OC.order_id IS NULL or OC.package_id != P.package_id ')
			->andWhere('P.ship_status = 0 and  P.create_time >= "2015-10-19 16:30:00" ')
			->group('O.order_id')
			->order('P.package_id asc');
		!empty($packageID) && $obj->andWhere('P.package_id="'.$packageID.'"');
			//->limit(1000)
		//echo $obj->text;
		$list = $obj->queryAll();
		return $list;
	}
	
}