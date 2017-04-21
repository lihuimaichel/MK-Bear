<?php
/**
 * 处理网站订单类
 * 订单下载 目前支持 paypal GC类型的订单下载
 * @author xiej
 * @since 2015-8-3 
 */
class WebsiteOrderService extends WebsiteModelAbstract{
	
	const PAYMENT_METHOD_GLOBALCOLLECT = 'globalcollect';
	const PAYMENT_METHOD_PAYPAL = 'paypal_express';
	const PAYMENT_METHOD_CHECK_MONEY_ORDER = 'checkmo';
	const PAYMENT_METHOD_OC_PAY = 'oceanpayment';//浅海支付的前缀
	const PAYMENT_METHOD_PAYPAL_PRE = 'paypal';
	//GC交易
	const GLOBALCOLLECT_TRANCSACTION_TYPE = 'GC';
	const GLOBALCOLLECT_TRANCSACTION_ID_PRE = 'GC-';
	//check money order
	const CHECK_MONEY_ORDER_TRANCSACTION_TYPE = 'CHECKMO';
	const CHECK_MONEY_ORDER_TRANCSACTION_ID_PRE = 'FREE-';
	//订单状态
	const WEBSITE_ORDER_STATUS_COMPLETE		= 'complete';
	const WEBSITE_ORDER_STATUS_PROCESSING	= 'processing';
	const WEBSITE_ORDER_STATUS_PENDING 		= 'pending_payment';
	//order_service
	const WEBSITE_ORDER_SERVICE_COMPLETE_DEFAULT = 0;
	const WEBSITE_ORDER_SERVICE_COMPLETE_YES = 1;
	
	public $RECEIVE_TYPE_YES = 1;	//接收
	public $RECEIVE_TYPE_NO = 2;	//发起
	//允许的支付方式
	public static $allowPaymentMethods = array(
			self::PAYMENT_METHOD_GLOBALCOLLECT,
			self::PAYMENT_METHOD_PAYPAL,
			self::PAYMENT_METHOD_CHECK_MONEY_ORDER
	);
	//paypal 之外各种收款方式对应的收款帐号
	public static $paymentAccounts = array(
			self::PAYMENT_METHOD_GLOBALCOLLECT		=>	'8482',
			self::PAYMENT_METHOD_OC_PAY				=> 	'160415',
			self::PAYMENT_METHOD_CHECK_MONEY_ORDER	=>	'0000'
	);
	public static $downloadOrderStatus = array(
			self::WEBSITE_ORDER_STATUS_PENDING,self::WEBSITE_ORDER_STATUS_PROCESSING
	);
	//支付成功状态
	const STATUS_PAID 			= 'processing';
	const STATUS_COMPLETE		= 'complete';
	//platform map to module
	public static $platformMap = array(
		'NF' => 'newfrog',
		'ECB' => 'ecoolbuy'	
	);
	//self force update order
	public $forseUpdate = false;
	/**
	 * 强制更新订单
	 */
	public function setForseUpdate(){
		$this->forseUpdate = true;
	}
	
	//order
	public $_order = null;
	public $_orderDetail = null;
	public $_orderTransactions = null;

	public function tableName(){
		return 'ueb_orderservice';
	}
	
	public $platform_order_id = NULL;
	public $platform_code = NUll;
	public $complete_status = NULL;
	public $updated_at = NULL;
	public $download_at = NULl;

// 	function __construct($platformCode){
// 		$this->api = Website
// 	}
/* ########################################################################## 同步订单  start 这部分 暂时不可用 因为从 老OA 迁移过来还为修改#####################################*/
	/**
	 * 检测 orderservice  Creditmemo 完成状态
	 */
	public function checkOrderServiceCompleteStatus($platformCode,$platformOrderId){
		$websiteOrderservice = $this->find('platform_code=:platform_code and platform_order_id=:platform_order_id',array(':platform_code'=>$platformCode,':platform_order_id'=>$platformOrderId));
		if($websiteOrderservice){
			if($websiteOrderservice->complete_status == self::WEBSITE_ORDER_SERVICE_COMPLETE_YES){
				return true;
			}
		}else {
			$record = new self();
			$record->platform_order_id 	= $platformOrderId;
			$record->platform_code 		= $platformCode;
			$record->complete_status 	= self::WEBSITE_ORDER_SERVICE_COMPLETE_DEFAULT;
			$record->save();
		}
		return false;//未检测到已经同步的 orderservice Creditmemo
	}
	/**
	 * orderservice Creditmemo 完成状态
	 * @param String $platformCode
	 * @param String $platformOrderId
	 */
	public function completeOrderService($platformCode,$platformOrderId){
		$websiteOrderservice = $this->find('platform_code=:platform_code and platform_order_id=:platform_order_id',array(':platform_code'=>$platformCode,':platform_order_id'=>$platformOrderId));
		$websiteOrderservice->complete_status = self::WEBSITE_ORDER_SERVICE_COMPLETE_YES;
		return $websiteOrderservice->save();
	}
	/**
	 * 待续写 如果订单多了  则批量向平台处理
	 * 
	 * 批量 向网站平台取消订单未发货的产品 -  付款 但是未发货    状态为已完成的订单未发货的产品
	 * @param String $platformCode
	 * @param Array $orderList
	 * 
	 */
	public function createCreditmemoListToWebsite($platformCode,$from_time=NULL,$to_time=NULL){
		$needArr = $this->getNeedCreateCreditmemoOrderList($platformCode,$from_time,$to_time);
		if($_REQUEST['debug']){echo 'type 2';var_dump($needArr);die;}
		$log = array();
		foreach ($needArr as $order){
			$platformOrderId = $order['platform_order_id'];
			if($platformOrderId){
				$log[$platformOrderId] = $this->createCreditmemoToWebsite($platformCode, $platformOrderId);
			}
		}
		return $log;
	}
	/**
	 * 向网站平台取消已付款状态为已完成的订单中未发货的产品 -  
	 * @param String $platformCode
	 * @param String $platformOrderId
	 */
	
	public function createCreditmemoToWebsite($platformCode,$platformOrderId){
		try{
			$this->checkOrder($platformCode, $platformOrderId);//加载订单
			if($creditmemo = $this->getCreditmemo($platformCode, $platformOrderId)){
				if($_REQUEST['debug']){var_dump($creditmemo);die(2);}
				//同步
				if($this->__api()->website_order_createCreditmemo($platformOrderId,array('qtys'=>$creditmemo))){
					//添加到已同步
					$this->completeOrderService($platformCode, $platformOrderId);
					return TRUE;//成功
				}else{
					throw new WebsitesException($this->__api()->getError());//失败
				}
			}
			throw new WebsitesException(Yii::t('Websites', 'can not CreateCreditmemo for this order'));//失败
		}catch(Exception $e){
			return $e->getMessage();
		}
	}
	/**
	 * 订单是否可以 CreateCreditmemo
	 * @param String $platformCode
	 * @param String $platformOrderId
	 */
	public function canCreateCreditmemo($platformCode,$platformOrderId){
		if($this->_order == NULL){
			$this->checkOrder($platformCode, $platformOrderId);
			if($this->_order == NULL){
				throw new WebsitesException(Yii::t('Websites', 'Order does not exists'));//no order
			}
		}
		//订单号一致
		if($platformOrderId != $this->_order->platform_order_id){
			throw new WebsitesException(Yii::t('Websites', 'PlatformOrderId error'));//与当前订单号不一致
		}
		//确定是付款  状态未已完成的 但是又欠货的订单 而且已发货的部分已经同步至网站
		if($this->_order->complete_status != Order::COMPLETE_STATUS_END){
			throw new WebsitesException(Yii::t('Websites', 'can not CreateCreditmemo for this order'));//订单不支持CreateCredit
		}
		//已经同步
		if($this->checkOrderServiceCompleteStatus($platformCode,$platformOrderId)){
			throw new WebsitesException(Yii::t('Websites', 'this order had been Create Creditmemo yet'));//已经同步
		}
		return TRUE;
	}
	/**
	 * 获取订单的欠货信息 
	 * 支持部分发货的订单  createCreditmemo
	 * @param String $platformCode
	 * @param String $platformOrderId
	 */
	public function getCreditmemo($platformCode,$platformOrderId){
		if($this->canCreateCreditmemo($platformCode, $platformOrderId)){
			//获取欠货信息
			$arr = array();
			$orderItems = $this->getSystemOrderModelInstance()->getDbConnection()->createCommand()
								->select('o.platform_order_id,o.platform_code,o.ship_status,main.quantity,main.quantity_old,main.order_item_id')
								->from('ueb_order_detail main')
								->leftJoin('ueb_order o', "main.order_id = o.order_id and o.platform_code='$platformCode'")
								->where("main.platform_code=:platform_code and o.platform_order_id=:platform_order_id and o.payment_status=1 and o.complete_status=3 and main.quantity < main.quantity_old and main.order_item_id > 0",
										array(':platform_code'=>$platformCode,':platform_order_id'=>$platformOrderId)
								)
								->queryAll();
			if($orderItems){
				foreach ($orderItems as $item){
					$qs = $item['quantity_old'] - $item['quantity'];
					$arr[$item['order_item_id']] = $qs;//欠货数
				}
			}
			return $arr;
		}
		throw new WebsitesException(Yii::t('Websites', 'this order can not Create Creditmemo')) ;
	}
	/**
	 * 批量关闭订单
	 */
	public function closedOrderListToWebsite($platformCode,$from_time=NULL,$to_time=NULL){
		$needArr = $this->getNeedClosedOrderList($platformCode, $from_time, $to_time);
		if($_REQUEST['debug']){echo 'type 3';var_dump($needArr);die(3);}
		$log = array();
		foreach ($needArr as $order){
			$platformOrderId = $order['platform_order_id'];
			if($platformOrderId){
				$log[$platformOrderId] = $this->createCreditmemoToWebsite($platformCode, $platformOrderId);
			}
		}
		return $log;
	}
	/**
	 * 更新订单的 invoice paid
	 * @param unknown $platformCode
	 * @param unknown $platformOrderId
	 */
	public function updateOrderInvoices($platformCode,$platformOrderId){
		return $this->__api()->create_website_order_paidInvoice($platformOrderId);
	}
	/**
	 * 获取部分发货的订单 - 即部分退款给客户的订单 - 部分筛选条件待核实
	 * @param String $platformCode
	 */
	
	public function getNeedCreateCreditmemoOrderList($platformCode,$from_time=NULL,$to_time=NULL){
		//若$from $没赋值 取上最近两个月符合条件的订单
		if($from_time == NULL){
			$from_time=date("Y-m-d", strtotime('-61 days'));
			$to_time=date("Y-m-d", strtotime('+1 days'));
		}
		$websiteDB = CommUtils::getDbNameByModelName('WebsiteOrderService');
		return $this->getSystemOrderModelInstance()->getDbConnection()->createCommand()
			->select('DISTINCT(main.platform_order_id)')
			->from('ueb_order main')
			->leftJoin('ueb_order_detail d', "main.order_id = d.order_id and d.platform_code='$platformCode'")
			->leftJoin($websiteDB.'.'.$this->tableName().' s', "main.platform_order_id = s.platform_order_id and s.platform_code='$platformCode'")
			->where("(s.complete_status is null or s.complete_status=0) and main.platform_code='$platformCode' and main.payment_status=1 and main.complete_status=3 and main.ship_status=2 and d.quantity < d.quantity_old and main.paytime BETWEEN '$from_time' AND '$to_time'")
			->order('main.order_id desc')
			->queryAll();
	}
	/**
	 * 获取付款完但是未发货 已完成订单  -  即应全额退款给客户的订单 这列订单我们 同步到网站 关闭订单同时通知客户进行退款 | 或者在ERP已经主动退款给客户  | 看以后ERP以后的订单退款流程
	 * @param String $platformCode
	 */
	public function getNeedClosedOrderList($platformCode,$from_time=NULL,$to_time=NULL){
		//若$from $没赋值 取上最近两个月符合条件的订单
		if($from_time == NULL){
			$from_time=date("Y-m-d", strtotime('-61 days'));
			$to_time=date("Y-m-d", strtotime('+1 days'));
		}
		$websiteDB = CommUtils::getDbNameByModelName('WebsiteOrderService');
		return $this->getSystemOrderModelInstance()->getDbConnection()->createCommand()
			->select('DISTINCT(main.platform_order_id)')
			->from('ueb_order main')
			->leftJoin($websiteDB.'.'.$this->tableName().' s', "main.platform_order_id = s.platform_order_id and s.platform_code='$platformCode'")
			->where("(s.complete_status is null or s.complete_status=0) and main.platform_code='$platformCode' and main.payment_status=1 and main.complete_status=3 and main.ship_status=0 and main.paytime BETWEEN '$from_time' AND '$to_time'")
			->order('main.order_id desc')
			->queryAll();
	}
	/**
	 * 优先根据包裹 -订单  同步到网站  - 前期主要考虑到 多个订单合并的包裹的同步
	 */
	public function sysCombinePackagesOrdersToWebsites($platformCode,$from_time=NULL,$to_time=NULL){
		if($from_time == NULL){
			$from_time=date("Y-m-d", strtotime('-61 days'));
			$to_time=date("Y-m-d", strtotime('+1 days'));
		}
		$checkConfirmShiped = isset($_GET['checkConfirmShiped']) ? FALSE : TRUE ;
		$Packages = $this->getCombinePackages($platformCode,$from_time,$to_time,$checkConfirmShiped);
		if($_REQUEST['debug']){echo 'type 4';var_dump($Packages);die(3);}
		foreach ($Packages as $package){
			$orders = $this->getOrdersByPackageId($platformCode, $package['package_id']);//包裹对应的订单
			if(empty($orders)) continue;
			foreach ($orders as $order){
				if($order['platform_order_id']) {
					$platformOrderId = $order['platform_order_id'];
					$this->sysOrderShipmentToWebsite($platformCode, $platformOrderId,FALSE);
				}
			}
		}
	}
	/**
	 * 获取包含多个订单的包裹
	 * @param string $platformCode
	 * @param string $from_time
	 * @param string $to_time
	 */
	public function getCombinePackages($platformCode,$from_time=NULL,$to_time=NULL,$checkConfirmShiped = true){
		$checkConfirmShiped = $checkConfirmShiped ? "main.is_confirm_shiped=0" :"1";
		return $this->getSystemOrderModelInstance()->getDbConnection()->createCommand()
					->select('main.package_id')
					->from('ueb_order_package main')
					->leftJoin('ueb_order_package_detail opd', "main.package_id= opd.package_id and opd.platform_code='$platformCode'")
					->where("main.platform_code='$platformCode' and main.is_repeat=0 and main.ship_date BETWEEN '$from_time' and '$to_time'")
					->andWhere($checkConfirmShiped)
					->group('main.package_id')
					->having('count(DISTINCT(opd.order_id)) > 1')//包含订单数量
					->queryAll();
	}
	/**
	 * 根据packageId 获取对应的订单号
	 * @param string $packageId
	 */
	public function getOrdersByPackageId($platformCode,$packageId){
		return $this->getSystemOrderModelInstance()->getDbConnection()->createCommand()
					->select('DISTINCT(main.platform_order_id)')
					->from('ueb_order main')
					->leftJoin('ueb_order_package_detail opd', "main.order_id= opd.order_id and opd.platform_code='$platformCode'")
					->where("main.platform_code='$platformCode' and opd.package_id='$packageId'")
					->queryAll();
	}
	/**
	 * 根据发货状态获取已经付款的订单列表
	 */
	public function getShippedOrderList($platformCode,$from,$to,$shipStatus=1){
		return $this->getSystemOrderModelInstance()->getDbConnection()->createCommand()
						->select('order_id,platform_code,platform_order_id,ship_status,payment_status')
						->where("payment_status=1 and platform_code='$platformCode' and paytime BETWEEN '$from' AND '$to'")
						->queryAll();
	}
	/**
	 * 按包裹邮寄的时间上传 跟踪号
	 * @param String $platformCode
	 * @param String $from_time   | 生成包裹的时间
	 * @param String $to_time
	 */
	public function uploadOrderListShipment($platformCode,$from_time=NULL,$to_time=NULL){
		//若$from $没赋值 取最近一个星期发出的包裹
		if($from_time == NULL){
			$from_time=date("Y-m-d", strtotime('-7 days'));
			$to_time=date("Y-m-d", strtotime('+1 days'));
		}
		$needArr = $this->getNeedSysOrderShipment($platformCode, $from_time, $to_time);
		if($_REQUEST['debug']){echo 'type 1';var_dump($needArr);die;}
		$log = array();
		foreach ($needArr as $order){
			$platformOrderId = $order['platform_order_id'];
			if($platformOrderId){
				$log[$platformOrderId] = $this->sysOrderShipmentToWebsite($platformCode, $platformOrderId);
			}
		}
		return $log;
	}
	/**
	 * 获取需要更新状态的订单  | 最近两天更新包裹 已经发货的且 没有同步到网站上的订单   - 非重发包裹
	 * @param String $platformCode
	 * @param String $from_time   | 生成包裹的时间
	 * @param String $to_time
	 */
	public function  getNeedSysOrderShipment($platformCode,$from_time,$to_time){
		return $this->getSystemOrderModelInstance()->getDbConnection()->createCommand()
					->select('DISTINCT(main.platform_order_id)')
					->from('ueb_order main')
					->leftJoin('ueb_order_detail od', "main.order_id = od.order_id and od.platform_code='$platformCode'")
					->leftJoin('ueb_order_package_detail opd', "main.order_id= opd.order_id and od.id=opd.order_detail_id and opd.platform_code='$platformCode'")
					->leftJoin('ueb_order_package op', "opd.package_id = op.package_id and op.platform_code='$platformCode'")
					->where("main.platform_code='$platformCode'  and od.order_item_id > 0 and main.ship_status in (1,2)  and op.is_repeat=0 and op.is_confirm_shiped=0 and op.ship_date BETWEEN '$from_time' and '$to_time'")
					->queryAll();
	}
	/**
	 * 更新订单状态 跟踪号到网站上
	 * @param string $platformOrderId
	 * @param array  $order_shipment_info  一般用来同步 订单合并功能的  一个包裹中含多个订单的产品
	 */
	function sysOrderShipmentToWebsite($platformCode,$platformOrderId,$checkConfirmShiped = true){
		try {
			//检查系统订单
			$this->checkOrder($platformCode, $platformOrderId);//加载订单
			if($this->_order == NULL){
				throw new WebsitesException(Yii::t('Websites', 'Order does not exists'));//no order
			}else{
				//还没出货 	
				if(Order::SHIP_STATUS_YES != $this->_order->ship_status && Order::SHIP_STATUS_PART != $this->_order->ship_status){
					throw new WebsitesException(Yii::t('Websites', 'Order does not start ship'));//no ship
				}
				//set shipment_info
				$order_shipment_info = $this->getOrderShipmentInfo($platformCode,$platformOrderId,$checkConfirmShiped);
				if($_REQUEST['debug']){var_dump($order_shipment_info);die;}
				//还未发货 查询不到shipment info
				if(empty($order_shipment_info)){throw new WebsitesException(Yii::t('Websites','Order has no track info now'));}
				$result = $this->__api()->create_website_order_shipment($platformOrderId,$order_shipment_info,$email);//改写的M系统api
				if(!is_array($result)){throw new WebsitesException(Yii::t('Websites','Websites api error,please contact admin'));}
				foreach ($result as $packageId => $value){
					if(TRUE === $value){
						$this->__setUploadTrackYes($packageId,$platformCode);
					}
				}
				return $result;
			}
		}catch (Exception $e){
			return $e->getMessage();//返回错误
			//failed log
		}
	}
	/**
	 * 获取单个订单的 shipment 状态
	 * array(
	 * 	'comment' => string
	 * 	'track'		=> $carrier
	 * 	'itemsQty'  => array()
	 * )
	 */
	function getOrderShipmentInfo($platformCode,$platformOrderId,$checkConfirmShiped = true){
		if($this->_order == NULL){
			$this->checkOrder($platformCode, $platformOrderId);
			if($this->_order == NULL){
				throw new WebsitesException(Yii::t('Websites', 'Order does not exists'));//no order
			}	
		}
		//订单号一致
		if($platformOrderId != $this->_order->platform_order_id){
			throw new WebsitesException(Yii::t('Websites', 'PlatformOrderId error'));//与当前订单号不一致
		}
		//还没出货
		if(Order::SHIP_STATUS_YES != $this->_order->ship_status && Order::SHIP_STATUS_PART != $this->_order->ship_status){
			throw new WebsitesException(Yii::t('Websites', 'Order does not start ship'));//no ship
		}
		$orderId = $this->_order->order_id;
		//查询已经发货的包裹信息 - 根据订单号按包裹查询  - 非重发包裹
		$checkConfirmShiped = $checkConfirmShiped ? "main.is_confirm_shiped=0" :"1";//是否核实已经同步到网站平台
		$_orderShipmentInfo = $this->_order->getDbConnection()->createCommand()
		->select('main.package_id,d.order_item_id,pd.quantity,main.track_num,main.real_ship_type,main.ship_date')
		->from('ueb_order_package main')
		->leftJoin('ueb_order_package_detail pd', 'main.package_id = pd.package_id')
		->leftJoin('ueb_order_detail d', 'pd.order_detail_id=d.id')
		->where("main.ship_status=1 and main.is_repeat=0 and main.platform_code='".$platformCode."' and d.order_id='".$orderId."'")
		->andWhere($checkConfirmShiped)
		->queryAll();
		//shipment info  一个包裹 一个shipment
		$shipmentInfo = array();
		if($_orderShipmentInfo){//一个订单可能包含几个包裹（跟踪号）
			foreach ($_orderShipmentInfo as $info){		
				$key = $info['package_id'];
				//track info  按包裹区分
				if(!is_array($shipmentInfo[$key])){
					$track_num = (empty($info['track_num']) || $info['track_num'] == 'NONE') ? NULL : $info['track_num'];
					$comment1 = 'The package has been posted successfully';
					$comment2 = "The package has been posted successfully with tracking number: {$track_num} You can trace it via http://www.17track.net/. (PS: Only tracking number with four letters can be traced. For example: RI185627649CN The tracking number like:44016708891 cannot be traced.)";
					$carrier = self::getCarrier($info['real_ship_type']);
					$shipmentInfo[$key] = array(
							'comment' => $track_num == NULL ? $comment1 : $comment2,
							'track'   => array('carrier'=>$carrier['carriers'],'title'=>$carrier['title'],'trackNumber'=>$track_num)
					);
				}
				//shiped items
				$itemId 	= $info['order_item_id'];
				$itemQty 	= $info['quantity'];
				if(!is_array($shipmentInfo[$key]['itemsQty'])){
					$shipmentInfo[$key]['itemsQty'] = array();
				}
				$shipmentInfo[$key]['itemsQty'][$itemId] = $itemQty;//构造一个itemsQty
				//- email - 默认 大于一个月的前期未同步的 不发邮件通知
				if($info['ship_date'] && $info['ship_date'] > date("Y-m-d", strtotime('-30 days'))){
					$shipmentInfo[$key]['email'] = true;
				}
			}
		}
		return $shipmentInfo;
	}
	/**
	 * 标识是已经同步
	 * @param unknown $platformCode
	 * @param unknown $platformOrderId
	 */
	function __setUploadTrackYes($packageId,$platformCode){
		$package = new OrderPackage();
		$package->updateByPk($packageId, array('is_confirm_shiped'=>OrderPackage::UPLOAD_TRACKNO_YES),"platform_code='$platformCode'");
	}
	/**
	 * 上传跟踪号调用
	 * @param String $real_ship_type
	 * @return array 
	 */
	static function  getCarrier($real_ship_type){
		//define carriers
		$shippingList=array ("ghxb"=>array("carriers"=>"custom","title"=>"Registered Airmail"),"airmail"=>array("carriers"=>"custom","title"=>"Airmail"),"eub"=>array("carriers"=>"custom","title"=>"USPS"),"ems"=>array("carriers"=>"custom","title"=>"EMS"),"dhl"=>array("carriers"=>"dhl","title"=>"DHL"),"fedex"=>array("carriers"=>"fedex","title"=>"Federal Express"),"ups"=>array("carriers"=>"ups","title"=>"United Parcel Service"));
		if (strpos($real_ship_type,"ghxb")===0){
			return $shippingList["ghxb"];
		}elseif(strpos($real_ship_type,"ems")!==false){
			return $shippingList['ems'];
		}elseif (isset($shippingList[$real_ship_type])){
			return $shippingList[$real_ship_type];
		}else{
			return $shippingList["airmail"];
		}
	}
/* ########################################################################## 同步订单  end #####################################*/ 			
	/**
	 * 下载订单时调用
	 * @param string $shipping_method
	 * @return string
	 */
	static function getShipType($shipping_method){
		$ship_type = '';
		if($shipping_method=="flatrate_flatrate"){//判断是否走挂号
			$ship_type = Logistics::CODE_GHXB;
		}elseif ($shipping_method=="ems_ems"){//判断是否走EMS
			$ship_type = Logistics::CODE_EMS;
		}
		return $ship_type;
	}
	/**
	 * 获取支付成功但是未下载成功的订单 - 一般最近三十天
	 */
	public function checkUndownlodOrderList($platformCode,$filters){
		$order_list = $this->__api()->sales_order_olist($filters);
		if(!is_array($order_list)) {die('data error :order list must as array');}
		$undownloadList = array();
		foreach ($order_list as $order){
			if($order['status'] == self::WEBSITE_ORDER_STATUS_PROCESSING){
				//检查系统订单
				$this->checkOrder($platformCode,$order['increment_id']);
				if($this->_order instanceof Order && $this->_order->payment_status == Order::PAYMENT_STATUS_END){
					continue;//下载到系统并且已经支付成功的订单
				}
				$undownloadList[] = $order['increment_id'];//
			}
		}
		return $undownloadList;
	}
	/**
	 * 一般来讲我们按天去获取订单 进行更新  情形  当天下载的订单可能会多   以前的订单多是在检测
	 * 下载了 已经付款的订单默认不再下载更新
	 *@param array $filters like 
	 *$filters=array("increment_id"=>array("in"=>array("30000015899")));
	 *$filters=array("updated_at"=>array("from"=>$from_time,"to"=>$to_time,"datetime"=>true),"status"=>array("in"=>array("processing","pending_payment")));
	 */
	function getWebsiteOrderlist($platformCode,$filters){
		$order_list = $this->__api()->sales_order_olist($filters);
		if(!is_array($order_list)) {die('data error :order list must as array');}
		$needUpdateArr = array();
		foreach ($order_list as $order){
			//检查系统订单
			$this->checkOrder($platformCode,$order['increment_id']);
			if($this->_order instanceof Order && $this->_order->payment_status == Order::PAYMENT_STATUS_END){
				continue;//下载到系统并且已经支付成功的订单 不做下载更新
			}
			//订单更新时间 update_at
			if($this->_order instanceof Order && $this->_order->last_update_time == $order['updated_at']){
				continue;//订单更新时间update_at没有变化的也不做更新
			}
			$needUpdateArr[] = $order['increment_id'];
		}
		//20个一组更新
		$needUpdateArr2 = array_chunk($needUpdateArr,20);
		//unset 
		unset($needUpdateArr);unset($order_list);
		$result = array();
		foreach ($needUpdateArr2 as $platformOrderIdArr){
			$result[] = $this->downloadOrderList($platformCode,$platformOrderIdArr);
		}
		return $result;
	}
	/**
	 * reset 
	 * @return $this
	 */
	private function reset(){
		//order
		$this->_order = null;
		$this->_orderDetail = null;
		$this->_orderTransactions = null;
		return $this;
	}
	/**
	 * 默认十个一组进行订单更新
	 */
	function downloadOrderList($platformCode,$platformOrderIdsArr = array()){
		$array = $errors = array();
		//获取多个订单详细信息
		$orderInfoList = $this->__api()->sales_order_info_list($platformOrderIdsArr);
		if($this->__api()->getError()){ echo 'api error : '.$this->__api()->getError();die;}
		if($orderInfoList){
			foreach ($orderInfoList as $website_order_info){
				if(is_array($website_order_info)){
					//$newOrder = new self(); 节省生成对象开销 注释掉 采用 reset 方式
					$array[$website_order_info['increment_id']] = $this->reset()->downloadOrder($platformCode, $website_order_info['increment_id'],$website_order_info);
				}else {
					//error log 
					$errors[] = $website_order_info;
				}
			}
		}
		return $array;
	}
	/**
	 * 下载订单 更新还是新记录  - 还是忽略 （每次保存了 website最新更新时间的，如果两次更新时间一次不做更新）
	 * （根据网站订单的实际情况，订单生成后订单基本信息都不做改变的） 只是 订单状态 交易 信息 发生改变
	 * @param string  	$platformCode
	 * @param string 	$incrementId
	 * @param Array 	$website_order_info -- 传递了这个参数就不去执行api去获取订单详细了
	 */
	function downloadOrder($platformCode,$platformOrderId,$website_order_info = array()){
		try{
			//start
			$OrderTransaction = $this->getSystemOrderModelInstance()->getDbConnection()->getCurrentTransaction();
			if($OrderTransaction == null){
				$OrderTransaction = $this->getSystemOrderModelInstance()->getDbConnection()->beginTransaction();
			}
			//检查系统订单
			$this->checkOrder($platformCode, $platformOrderId);
			if($this->_order instanceof Order && $this->_order->payment_status == Order::PAYMENT_STATUS_END){
				//return true;//下载到系统并且已经支付成功的订单 不做下载更新
				throw new Exception("Order $platformOrderId had been download yet");
			}
			//website order info
			if(!$website_order_info){//没有赋值的情况下
				//网站订单
				$website_order_info = $this->__api()->sales_order_info($platformOrderId);
				if(!is_array($website_order_info) && $this->__api()->getError()){
					throw new Exception($this->__api()->getError());
				}
				if(!$website_order_info){
					throw new Exception("Order $platformOrderId doesn't exist on website");
				}
			}else{
				if($platformOrderId != $website_order_info['increment_id']){throw new Exception("Platform Order id $platformOrderId is not eq ".$website_order_info['increment_id']);} //保证是同一订单
			}
			//订单存在已经下载并且无须更新产品和主体信息
			if($this->_order instanceof Order && !$this->forseUpdate) {
			}else {
				if($this->_order == NULL) { $this->_order = $this->getSystemOrderModelInstance();} //如果是新订单
				//订单主体
				$this->doSaveOrderMain($platformCode,$website_order_info);
				//产品 items
				$this->doSaveOrderItems($platformCode,$website_order_info);
			}
			//payment
			$this->doSaveOrderPayment($platformCode, $website_order_info);
			//commit
			$OrderTransaction->commit();
			return true;
		}catch (Exception $e){
			$OrderTransaction->rollback();//roll back
			return $e->getMessage();
		}
	}
	/**
	 * 转化订单  -- 
	 */
	function convertWebsiteOrder(){
		
	}
	/**
	 * 装载订单
	 * @param unknown $incrementId
	 */
	function loadOrder($platformCode,$platformOrderId){
		$this->_order = '';
		if($this->_order){
			$this->_orderDetail = '';
			$this->_orderTransactions = '';
		}
		return $this;
	}
	/**
	 * 获取 order items
	 * @param unknown $orderInfo
	 */
	function __getOrderDetail($orderInfo){
		if($this->_orderDetail){
			
		}
		return $this->_orderDetail;
	}
	/**
	 * 获取 order transactions
	 * @param unknown $orderInfo
	 */
	function __getOrderTransactions($orderInfo){
		if($this->_orderTransactions){
			
		}
		return $this->_orderTransactions;
	}
	/**
	 * 网站订单根据需求转化成国内时间 - 已经在module 里申明 采取 Asia/Shanghai
	 */
	static function getChineseDate($date_time){
		return date('Y-m-d H:i:s', strtotime('+8 hours',strtotime($date_time)));
	}
	/**
	 * 获取系统新订单号
	 */
	function getNewOrderID(){
		return UebModel::model('autoCode')->getCode('order');
	}
	/**
	 *	根据网站订单号检测订单是否已经存在
	 */
	function checkOrder($platformCode,$platformOrderId){
		return $this->_order = $this->getSystemOrderModelInstance()->find('platform_code=:platform_code and platform_order_id=:platform_order_id',
				array(
						':platform_code'	 => $platformCode,
						':platform_order_id' => $platformOrderId
				)
		);
	}
	/**
	 * 获取订单实例
	 * @return Order
	 */
	function getSystemOrderModelInstance(){
		return new Order();
	}
	
	/**
	 * // 优惠计算公式
	 */
	function getCouponPrice($order_info){
		return round($order_info['subtotal'] + $order_info['insurance_amount'] + $order_info['shipping_amount'] - $order_info['grand_total'],2); // 优惠计算公式;
	}
	/**
	 * 订单信息
	 */
	public function doSaveOrderMain($platformCode,$order_info){
		//是否新记录
		if(!$this->_order->order_id){
			$this->_order->order_id = UebModel::model('autoCode')->getCode('order');
		}
		
		//basic
 		$this->_order->account_id 			= '';//$order_info['account_id'];
		$this->_order->platform_code 		= $platformCode;
		$this->_order->platform_order_id 	= $order_info['increment_id'];
		$this->_order->order_status 		= $order_info['status'];
		$this->_order->buyer_id 			= $order_info['customer_id'];//暂时保存网站上客户ID
		$this->_order->email 				= $order_info['customer_email'];
		$this->_order->timestamp 			= MHelper::getNowTime();
		$this->_order->created_time 		= $order_info['created_at'];
		$this->_order->last_update_time 	= $order_info['updated_at'];
		//运费和保险
		$this->_order->ship_code			= self::getShipType($order_info['shipping_method']);
		$this->_order->ship_cost 			= $order_info['shipping_amount'];
		$this->_order->insurance_amount		= $order_info['insurance_amount'];
		$this->_order->subtotal_price 		= $order_info['subtotal'];
		$this->_order->total_price 			= $order_info['grand_total'];
		$this->_order->currency 			= $order_info['order_currency_code'];
		//ship
		$shipping_address					= $order_info['shipping_address'];
		$this->_order->ship_name 			= $shipping_address['firstname'].' '.$shipping_address['lastname'];
		$this->_order->ship_phone 			= $shipping_address['telephone'];
		$this->_order->ship_street1 		= $shipping_address['street'];
		$this->_order->ship_street2 		= '';
		$this->_order->ship_city_name 		= $shipping_address['city'];
		$this->_order->ship_stateorprovince = $shipping_address['region'];
		$this->_order->ship_country 		= $shipping_address['country_id'];
		$this->_order->ship_country_name 	= $this->getCountryName($shipping_address['country_id']);
		$this->_order->ship_zip 			= $shipping_address['postcode'];
		//$this->_order->log_id 			= $order_info['log_id'];
		//ori time
		$this->_order->ori_create_time		= self::getChineseDate($order_info['created_at']);
		$this->_order->ori_update_time		= self::getChineseDate($order_info['updated_at']);
		
		//save order
		if(!($this->_order->save())){
			throw new Exception("System save order $platformOrderId failed");	//订单保存失败
		}
		//save order_extend
		$orderExtendInfo = array(
				'order_id'			   => $this->_order->order_id,
				'platform_order_id'    => $order_info['increment_id'],
				'platform_code'		   => $platformCode ,
				'account_id'           => 0,
				'tax_fee'              => 0,//网站无税费
				'coupon_price'         => $this->getCouponPrice($order_info),//优惠金额
				'currency'             => $order_info['order_currency_code'],//币种
		);
		$flag = OrderExtend::model()->dbConnection->createCommand()->insert(OrderExtend::tableName(), $orderExtendInfo);
		if(!$flag){
			throw new Exception("System save orderExtendInfo $platformOrderId failed");	//订单保存失败
		}
		
		return true;
	}
	/**
	 * 获取国家信息
	 * @param String $country
	 * @return string
	 */
	public static function getCountryName($country){
		$CountryConfig = Array(
			    'AF' => 'Afghanistan',
			    'AL' => 'Albania',
			    'DZ' => 'Algeria',
			    'AS' => 'American Samoa',
			    'AD' => 'Andorra',
			    'AO' => 'Angola',
			    'AI' => 'Anguilla',
			    'AQ' => 'Antarctica',
			    'AG' => 'Antigua and Barbuda',
			    'AR' => 'Argentina',
			    'AM' => 'Armenia',
			    'AW' => 'Aruba',
			    'AU' => 'Australia',
			    'AT' => 'Austria',
			    'AZ' => 'Azerbaijan',
			    'BS' => 'Bahamas',
			    'BH' => 'Bahrain',
			    'BD' => 'Bangladesh',
			    'BB' => 'Barbados',
			    'BY' => 'Belarus',
			    'BE' => 'Belgium',
			    'BZ' => 'Belize',
			    'BJ' => 'Benin',
			    'BM' => 'Bermuda',
			    'BT' => 'Bhutan',
			    'BO' => 'Bolivia',
			    'BA' => 'Bosnia and Herzegovina',
			    'BW' => 'Botswana',
			    'BV' => 'Bouvet Island',
			    'BR' => 'Brazil',
			    'IO' => 'British Indian Ocean Territory',
			    'VG' => 'British Virgin Islands',
			    'BN' => 'Brunei',
			    'BG' => 'Bulgaria',
			    'BF' => 'Burkina Faso',
			    'BI' => 'Burundi',
			    'KH' => 'Cambodia',
			    'CM' => 'Cameroon',
			    'CA' => 'Canada',
			    'CV' => 'Cape Verde',
			    'KY' => 'Cayman Islands',
			    'CF' => 'Central African Republic',
			    'TD' => 'Chad',
			    'CL' => 'Chile',
			    'CN' => 'China',
			    'CX' => 'Christmas Island',
			    'CC' => 'Cocos [Keeling] Islands',
			    'CO' => 'Colombia',
			    'KM' => 'Comoros',
			    'CG' => 'Congo - Brazzaville',
			    'CD' => 'Congo - Kinshasa',
			    'CK' => 'Cook Islands',
			    'CR' => 'Costa Rica',
			    'HR' => 'Croatia',
			    'CU' => 'Cuba',
			    'CY' => 'Cyprus',
			    'CZ' => 'Czech Republic',
			    'CI' => 'Côte d’Ivoire',
			    'DK' => 'Denmark',
			    'DJ' => 'Djibouti',
			    'DM' => 'Dominica',
			    'DO' => 'Dominican Republic',
			    'EC' => 'Ecuador',
			    'EG' => 'Egypt',
			    'SV' => 'El Salvador',
			    'GQ' => 'Equatorial Guinea',
			    'ER' => 'Eritrea',
			    'EE' => 'Estonia',
			    'ET' => 'Ethiopia',
			    'FK' => 'Falkland Islands',
			    'FO' => 'Faroe Islands',
			    'FJ' => 'Fiji',
			    'FI' => 'Finland',
			    'FR' => 'France',
			    'GF' => 'French Guiana',
			    'PF' => 'French Polynesia',
			    'TF' => 'French Southern Territories',
			    'GA' => 'Gabon',
			    'GM' => 'Gambia',
			    'GE' => 'Georgia',
			    'DE' => 'Germany',
			    'GH' => 'Ghana',
			    'GI' => 'Gibraltar',
			    'GR' => 'Greece',
			    'GL' => 'Greenland',
			    'GD' => 'Grenada',
			    'GP' => 'Guadeloupe',
			    'GU' => 'Guam',
			    'GT' => 'Guatemala',
			    'GG' => 'Guernsey',
			    'GN' => 'Guinea',
			    'GW' => 'Guinea-Bissau',
			    'GY' => 'Guyana',
			    'HT' => 'Haiti',
			    'HM' => 'Heard Island and McDonald Islands',
			    'HN' => 'Honduras',
			    'HK' => 'Hong Kong SAR China',
			    'HU' => 'Hungary',
			    'IS' => 'Iceland',
			    'IN' => 'India',
			    'ID' => 'Indonesia',
			    'IR' => 'Iran',
			    'IQ' => 'Iraq',
			    'IE' => 'Ireland',
			    'IM' => 'Isle of Man',
			    'IL' => 'Israel',
			    'IT' => 'Italy',
			    'JM' => 'Jamaica',
			    'JP' => 'Japan',
			    'JE' => 'Jersey',
			    'JO' => 'Jordan',
			    'KZ' => 'Kazakhstan',
			    'KE' => 'Kenya',
			    'KI' => 'Kiribati',
			    'KW' => 'Kuwait',
			    'KG' => 'Kyrgyzstan',
			    'LA' => 'Laos',
			    'LV' => 'Latvia',
			    'LB' => 'Lebanon',
			    'LS' => 'Lesotho',
			    'LR' => 'Liberia',
			    'LY' => 'Libya',
			    'LI' => 'Liechtenstein',
			    'LT' => 'Lithuania',
			    'LU' => 'Luxembourg',
			    'MO' => 'Macau SAR China',
			    'MK' => 'Macedonia',
			    'MG' => 'Madagascar',
			    'MW' => 'Malawi',
			    'MY' => 'Malaysia',
			    'MV' => 'Maldives',
			    'ML' => 'Mali',
			    'MT' => 'Malta',
			    'MH' => 'Marshall Islands',
			    'MQ' => 'Martinique',
			    'MR' => 'Mauritania',
			    'MU' => 'Mauritius',
			    'YT' => 'Mayotte',
			    'MX' => 'Mexico',
			    'FM' => 'Micronesia',
			    'MD' => 'Moldova',
			    'MC' => 'Monaco',
			    'MN' => 'Mongolia',
			    'ME' => 'Montenegro',
			    'MS' => 'Montserrat',
			    'MA' => 'Morocco',
			    'MZ' => 'Mozambique',
			    'MM' => 'Myanmar [Burma]',
			    'NA' => 'Namibia',
			    'NR' => 'Nauru',
			    'NP' => 'Nepal',
			    'NL' => 'Netherlands',
			    'AN' => 'Netherlands Antilles',
			    'NC' => 'New Caledonia',
			    'NZ' => 'New Zealand',
			    'NI' => 'Nicaragua',
			    'NE' => 'Niger',
			    'NG' => 'Nigeria',
			    'NU' => 'Niue',
			    'NF' => 'Norfolk Island',
			    'KP' => 'North Korea',
			    'MP' => 'Northern Mariana Islands',
			    'NO' => 'Norway',
			    'OM' => 'Oman',
			    'PK' => 'Pakistan',
			    'PW' => 'Palau',
			    'PS' => 'Palestinian Territories',
			    'PA' => 'Panama',
			    'PG' => 'Papua New Guinea',
			    'PY' => 'Paraguay',
			    'PE' => 'Peru',
			    'PH' => 'Philippines',
			    'PN' => 'Pitcairn Islands',
			    'PL' => 'Poland',
			    'PT' => 'Portugal',
			    'PR' => 'Puerto Rico',
			    'QA' => 'Qatar',
			    'RO' => 'Romania',
			    'RU' => 'Russia',
			    'RW' => 'Rwanda',
			    'RE' => 'Réunion',
			    'BL' => 'Saint Barthélemy',
			    'SH' => 'Saint Helena',
			    'KN' => 'Saint Kitts and Nevis',
			    'LC' => 'Saint Lucia',
			    'MF' => 'Saint Martin',
			    'PM' => 'Saint Pierre and Miquelon',
			    'VC' => 'Saint Vincent and the Grenadines',
			    'WS' => 'Samoa',
			    'SM' => 'San Marino',
			    'SA' => 'Saudi Arabia',
			    'SN' => 'Senegal',
			    'RS' => 'Serbia',
			    'SC' => 'Seychelles',
			    'SL' => 'Sierra Leone',
			    'SG' => 'Singapore',
			    'SK' => 'Slovakia',
			    'SI' => 'Slovenia',
			    'SB' => 'Solomon Islands',
			    'SO' => 'Somalia',
			    'ZA' => 'South Africa',
			    'GS' => 'South Georgia and the South Sandwich Islands',
			    'KR' => 'South Korea',
			    'ES' => 'Spain',
			    'LK' => 'Sri Lanka',
			    'SD' => 'Sudan',
			    'SR' => 'Suriname',
			    'SJ' => 'Svalbard and Jan Mayen',
			    'SZ' => 'Swaziland',
			    'SE' => 'Sweden',
			    'CH' => 'Switzerland',
			    'SY' => 'Syria',
			    'ST' => 'São Tomé and Príncipe',
			    'TW' => 'Taiwan',
			    'TJ' => 'Tajikistan',
			    'TZ' => 'Tanzania',
			    'TH' => 'Thailand',
			    'TL' => 'Timor-Leste',
			    'TG' => 'Togo',
			    'TK' => 'Tokelau',
			    'TO' => 'Tonga',
			    'TT' => 'Trinidad and Tobago',
			    'TN' => 'Tunisia',
			    'TR' => 'Turkey',
			    'TM' => 'Turkmenistan',
			    'TC' => 'Turks and Caicos Islands',
			    'TV' => 'Tuvalu',
			    'UM' => 'U.S. Minor Outlying Islands',
			    'VI' => 'U.S. Virgin Islands',
			    'UG' => 'Uganda',
			    'UA' => 'Ukraine',
				'UK'=>'United Kingdom',
			    'AE' => 'United Arab Emirates',
			    'GB' => 'United Kingdom',
			    'US' => 'United States',
			    'UY' => 'Uruguay',
			    'UZ' => 'Uzbekistan',
			    'VU' => 'Vanuatu',
			    'VA' => 'Vatican City',
			    'VE' => 'Venezuela',
			    'VN' => 'Vietnam',
			    'WF' => 'Wallis and Futuna',
			    'EH' => 'Western Sahara',
			    'YE' => 'Yemen',
			    'ZM' => 'Zambia',
			    'ZW' => 'Zimbabwe',
			    'AX' => 'Åland Islands',
				'SRB'=> 'Serbia',
				'MNE'=> 'Montenegro',
				'ASC'=> 'Ascension Island', 
				'GBA'=> 'Alderney',
				'ALA'=> 'Aland Islands',
				'TLS'=> 'Timor-Leste',
				'SGS'=> 'South Georgia and the South Sandwich Islands',
		);
		$countryName = '';
		if(isset($CountryConfig[$country])){
			$countryName = $CountryConfig[$country];
		}
		return $countryName;
	}
	/**
	 * 保存订单产品详细
	 */
	function doSaveOrderItems($platformCode,$order_info){
		//清空
		if($this->forseUpdate){
			UebModel::model('orderDetail')->deleteAll('order_id=:order_id',array(':order_id' => $this->_order->order_id));
		}

		$subTotalPrice  = floatval($order_info['subtotal']);//订单产品总金额,不含运费、处理费和税费等
		$totalPrice     = 0;//订单明细总金额 ,同时unset 除去配置型产品多余信息,免去平摊计算最后一个可能是配置型产品的问题
		foreach ($order_info['items'] as $index => $item) {
			if (!($item['product_type'] == 'configurable' || ($item['product_type'] == 'simple' && $item['parent_item_id'] == null))) {
				unset($order_info['items'][$index]);//除去配置型产品多余信息
				continue;
			}
			$itemTotalPrice = $item['price'] * intval ( $item['qty_ordered'] );//item 总金额
			$totalPrice += $itemTotalPrice;
		}
		if ( round($subTotalPrice,2) < round($totalPrice,2) ) {
			//echo round($subTotalPrice,2),'--',round($totalPrice,2),'<br>';
			//throw new Exception('check website order:'.$this->_order->platform_order_id.' subtotal fail');//金额不等
		}
		
		$orderTotalPrice   = floatval($order_info['grand_total']);//订单总金额=实际交易金额
		$totalShipFee      = floatval($order_info['shipping_amount']);//总运费
		$totalTaxFee       = 0	;//总税费
		$totalInsuranceFee = floatval($order_info['insurance_amount']);//运费险
		$totalFeeAmt       = 0;//paypal手续费
		
		//订单成交费,USD
		$totalFvf          = 0;
		
		//优惠金额
		$totalDiscount = $this->getCouponPrice($order_info); // 优惠计算公式
		if ($totalDiscount < 0) {
			$totalDiscount = 0;
		}
		
		//存储订单明细
		$currentIndex = 0 ;
		$listCount    = count($order_info['items']);
		$tmpshipFee   = $tmpDiscount = $tmpFvf = 0;
		$tmpFeeAmt    = $tmpTaxFee = $tmpInsuranceFee = $tmpItemSalePriceAllot = 0;//tmp 初始化参数
		
		foreach ($order_info['items'] as $item){
			//$currentNam 自增
			$currentIndex++;
			//前面已经去除了 这里暂时保留
			if (!($item['product_type'] == 'configurable' || ($item['product_type'] == 'simple' && $item['parent_item_id'] == null))) {
				continue;
			}
			$unitSalePrice = floatval ( $item['price'] );//销售单价(含成交费)
			$quantity      = intval ( $item['qty_ordered'] );//购买数量
			$itemSalePrice = $unitSalePrice * $quantity;//产品金额
			//非可配性产品
			
			//转成本地SKU
			$realSku=  $item['sku'] ;
			$realProduct = Product::model()->getRealSkuList($realSku,$item['qty_ordered']);
			if($realProduct){
				//平摊费用
				if ($currentIndex == $listCount) {
					$shipFee                = round($totalShipFee - $tmpshipFee,2);
					$discount               = round($totalDiscount - $tmpDiscount,2);
					$fvfAmt                 = round($totalFvf - $tmpFvf,2);
					$feeAmt                 = round($totalFeeAmt - $tmpFeeAmt,2);
					$taxFee                 = round($totalTaxFee - $tmpTaxFee,2);
					$insuranceFee           = round($totalInsuranceFee - $tmpInsuranceFee,2);
					$itemSalePriceAllot     = round($subTotalPrice - $totalDiscount - $tmpItemSalePriceAllot, 2);//平摊后的item售价
					$unitSalePriceAllot     = round($itemSalePriceAllot/$quantity, 3);//平摊后的单价
				} else {
					$rate                  = $itemSalePrice/$subTotalPrice;
					$shipFee               = round($rate * $totalShipFee,2);//平摊后的运费
					$discount              = round($rate * $totalDiscount,2);//平摊后的优惠金额
					$fvfAmt                = round($rate * $totalFvf,2);//平摊后的成交费
					$feeAmt                = round($rate * $totalFeeAmt,2);//平摊后的手续费
					$taxFee                = round($rate * $totalTaxFee,2);//平摊后的税费
					$insuranceFee          = round($rate * $totalInsuranceFee,2);//平摊后的运费险
					$itemSalePriceAllot    = round($itemSalePrice - $discount, 2);//平摊后的item售价
					$unitSalePriceAllot    = round($itemSalePriceAllot/$quantity, 3);//平摊后的单价
				
					$tmpshipFee            += $shipFee;
					$tmpDiscount           += $discount;
					$tmpFvf                += $fvfAmt;
					$tmpFeeAmt             += $feeAmt;
					$tmpTaxFee             += $taxFee;
					$tmpInsuranceFee       += $insuranceFee;
					$tmpItemSalePriceAllot += $itemSalePriceAllot;
				}
				//组装订单明细扩展表数据
				$detailExtData = array(
						'item_sale_price'        => $itemSalePrice,//产品金额(含成交费)
						'item_sale_price_allot'  => $itemSalePriceAllot,//平摊后的产品金额(含成交费，减优惠金额)
						'unit_sale_price_allot'  => $unitSalePriceAllot,//平摊后的单价(原销售单价-平摊后的优惠金额)
						'coupon_price_allot'     => $discount,//平摊后的优惠金额
						'tax_fee_allot'          => $taxFee,//平摊后的税费
						'insurance_amount_allot' => $insuranceFee,//平摊后的运费险
						'fee_amt_allot'          => $feeAmt,//平摊后的手续费
				);
				
				$detailObj = new OrderDetail();
				$detailObj->id				= OrderDetail::model()->getPlanInsertID(Platform::CODE_NEWFROG);
				$detailObj->platform_code 	= $platformCode;
				$detailObj->transaction_id 	= '';//$platformOrderId;
				//订单号
				$detailObj->order_id 		= $this->_order->order_id;//对应订单ID
				$detailObj->item_id 		= $item['product_id'];
				$detailObj->title 			= $item['name'];
				$detailObj->sku_old 		= $realSku;
				$detailObj->quantity_old 	= intval($item['qty_ordered']);
				$detailObj->sku 			= $realProduct['sku'];
				$detailObj->quantity 		= intval($realProduct['quantity']);
				$detailObj->site 			= $platformCode;
					
				$detailObj->sale_price 		= $unitSalePrice;
				$detailObj->ship_price 		= $shipFee;
				$detailObj->total_price 	= round($itemSalePrice + $shipFee,2);//$item['row_total'];
				$detailObj->currency 		= $order_info['order_currency_code'];
				//other set default value
				$detailObj->final_value_fee	= 0;
				$detailObj->order_item_id 	= $item['item_id'];

				// orderDetail
				if($detailObj->save() == FALSE){
					throw new Exception('save website order:'.$this->_order->platform_order_id.' orderdetail fail');
				}
				//orderDetail extend
				$detailExtData['detail_id'] = $detailObj->dbConnection->getLastInsertID();
				$isOk = OrderDetailExtend::model()->addOrderDetailExtend($detailExtData);
				if(!$isOk) {
					throw new Exception("save detailExtend failure");
				}
				
				//判断是否需要添加插头数据
				$flag = OrderDetail::model()->addOrderAdapter(array(
						'order_id'	=>	$this->_order->order_id,
						'ship_country_name'	=>	$this->_order->ship_country,
						'platform_code'	=>	Platform::CODE_NEWFROG,
						'currency'	=>	$order_info['order_currency_code']
				),	$realProduct);
				//如果保存失败
				if(!$flag) throw new Exception('save website order:'.$this->_order->platform_order_id. ": Save order adapter failure");
			}else{
				throw new Exception('Item sku '.$realSku.' can not get realProducts');
			}
		}
		return TRUE;
	}
	/**
	 * 保存产订单付款信息
	 */
	function doSaveOrderPayment($platformCode,$order_info){
		//未付款的
		if(self::STATUS_PAID != $order_info['status']){
			return;
		}
		$payment 			= 	$order_info['payment'];
		//$status_history		=	$order_info['status_history'];
		if(self::PAYMENT_METHOD_PAYPAL == $payment['method']){
			//paypal
			$this->savePaypalTransaction($platformCode, $order_info);
		}elseif (self::PAYMENT_METHOD_GLOBALCOLLECT == $payment['method']){
			//globalcollect
			$this->saveGCPaymentTransaction($platformCode, $order_info);
		}elseif (self::PAYMENT_METHOD_CHECK_MONEY_ORDER == $payment['method']
				||  strstr($payment['method'],self::PAYMENT_METHOD_OC_PAY) ){
			//checkmo and oc_pay
			$this->saveStandardOrderPaymentTransaction($platformCode, $order_info);
		}else {
			throw new Exception('Un known payment method');
		}
		//付款成功,更新主订单状态
		$ship_type = '';
		$this->_order->ship_code 		= $ship_type;
		$this->_order->paytime 			= $order_info['updated_at'];//取第一次付款时间
		$this->_order->last_update_time = $order_info['updated_at'];
		$this->_order->payment_status 	= Order::PAYMENT_STATUS_END;
		//ori time
		$this->_order->ori_pay_time		= self::getChineseDate($order_info['updated_at']);
		if(!$this->_order->save()){
			throw new Exception('save order status fail');
		}
		return TRUE;
	}
	/**
	 * paypal 付款信息
	 */
	function savePaypalTransaction($platformCode,$order_info){
		if($this->_order instanceof Order && $order_info['status'] != self::STATUS_PAID && $order_info['status'] != self::STATUS_COMPLETE){
			return false;//付款不成功的 不保存
		}
		//paypal transaction
		$payment 				= $order_info['payment'];
		$transactionId			= $payment['last_trans_id'];
		$order_id 			= $this->_order->order_id;
		$transactionInstance = new WebsiteGetTransactionDetails();
		$transactionObj =  $transactionInstance->getDetailByTransactionId($transactionId, $platformCode);
		if(!$transactionObj
				|| !$transactionInstance->setPaypalTransaction($transactionId,$order_id, $platformCode,$transactionObj)
				|| !$transactionInstance->setPaypalTransactionRecord($transactionId,$order_id, $platformCode,$transactionObj)){
			throw new Exception('save order '.$order_id.' transaction failed');
		}
		//均摊paypal fee,其他收款方式无手续费,更新订单详情
		if($transactionObj && $transactionObj['FEEAMT']){
			$feeAmt = $transactionObj['FEEAMT'];
			$tmpFeeAmt = 0 ;
			//除去转接头  的产品明细
			$orderDetails = OrderDetail::model()->getDbConnection()->createCommand()
				->select('id')
				->from(OrderDetail::tableName())
				->where('order_id=:order_id and detail_type!=:detail_type',array(':order_id'=>$order_id,':detail_type'=>OrderDetail::IS_ADAPTER))
				->queryAll();
			if($orderDetails){
				$itemDetailIds = array();
				foreach ($orderDetails as $item){
					$itemDetailIds[] = $item['id'];
				}
				if($itemDetailIds){
					$orderDetailExtends = OrderDetailExtend::model()->findAll('detail_id in ('.implode(',', $itemDetailIds).')');
					if ($orderDetailExtends){
						//均摊手续费fee
						$itemCount = count($orderDetailExtends);
						$currentCount = 0;
						foreach ($orderDetailExtends as $orderDetailExtend){
							$currentCount++;
							if ($currentCount == $itemCount) {
								$fee_amt_allot	 		= round(($feeAmt - $tmpFeeAmt), 2);
							} else {
								$fee_amt_allot         	= round($feeAmt/$itemCount,2);
								//tmp
								$tmpFeeAmt += $fee_amt_allot;
							}
							//update fee_amt_allot
							$orderDetailExtend->fee_amt_allot = $fee_amt_allot;
							if (!$orderDetailExtend->save()){
								throw new Exception('update order fee_amt_allot failed');
							}
						}
					}
				}
			}
		}
		return true;
	}
	/**
	 * 保存 GC信用卡付款 信息
	 * @return boolean
	 */
	private function saveGCPaymentTransaction($platformCode,$order_info){
		if($this->_order instanceof Order && $order_info['status'] != self::STATUS_PAID && $order_info['status'] != self::STATUS_COMPLETE){
			return true;//付款不成功的 不保存
		}
		//后期 GC订单增加对 状态码的判断 600 不一定成功  800状态才下载到  OMS 并记录订单付款成功
		$additionalInfo = $order_info['payment']['additional_information'];
		$transactionId = $this->getGCTansactionId($additionalInfo);
		$order_id = $this->_order->order_id;
		//transaction 支付成功的
		$orderTransaction = array(
				'order_id'              => $order_id,
				'first'                 => 1,
				'is_first_transaction'  => 1,
				'receive_type'          => OrderTransaction::RECEIVE_TYPE_YES,
				'account_id'            => '',
				'parent_transaction_id' => '',
				'order_pay_time'        => $order_info['updated_at'],
				'amt'                   => $order_info['grand_total'],
				'fee_amt'               => '0',
				'currency'              => $order_info['order_currency_code'],
				'payment_status'        => 'Completed',
				'platform_code'         => $platformCode
		);
		//save transaction
		if(!OrderTransaction::model()->saveTransactionRecord($transactionId, $order_id,$orderTransaction)){
			throw new Exception('save order '.$this->_order->platform_order_id . ' transaction fail');
		}
		//free
		unset($orderTransaction);
		//save tranaction record  -- 构造一条交易记录
		$paymentAccount = self::getOtherPaymentAccount($order_info);
		$Records_data = array(
				'order_id' 			=> $this->_order->order_id,
				'receive_type' 		=> $this->RECEIVE_TYPE_YES,
				'receiver_business' => $platformCode,
				'receiver_email' 	=> $paymentAccount,
				'receiver_id'		=> '',
				'payer_id' 			=> '-',
				'payer_name' 		=> '-',
				'payer_email' 		=> '',
				'payer_status' 		=> '-',
				'parent_transaction_id'	=>	'-',
				'transaction_type' 	=> self::GLOBALCOLLECT_TRANCSACTION_TYPE,
				'payment_type' 		=> '-',
				'order_time' 		=> $order_info['updated_at'],
				'amt' 				=> $order_info['grand_total'],
				'fee_amt' 			=> '0',
				'tax_amt' 			=> '0',
				'currency' 			=> $order_info['order_currency_code'],
				'payment_status' 	=> $additionalInfo['STATUSID'],
				'note' 				=> '',
				'modify_time'		=> ''
		);//保存交易信息 record
		if(!OrderPaypalTransactionRecord::model()->savePaypalRecord($transactionId, $order_id,$Records_data)){
			throw new Exception('save order '.$this->_order->platform_order_id .' transaction record fail');	
		}
		return true;
	}
	/**
	 *  保存 Standard order 
	 * @param string $platformCode
	 * @param array $order_info
	 * @param number $first
	 * @param string $refund
	 * @throws Exception
	 * @return boolean
	 */
	public function saveStandardOrderPaymentTransaction($platformCode,$order_info){
		if($this->_order instanceof Order && $order_info['status'] != self::STATUS_PAID && $order_info['status'] != self::STATUS_COMPLETE){
			return true;//付款不成功的 不保存
		}
		$transactionId = $this->getStandardOrderTransactionid($order_info);
		$order_id = $this->_order->order_id;
		//transaction 支付成功的
		$orderTransaction = array(
				'order_id'              => $order_id,
				'first'                 => 1,
				'is_first_transaction'  => 1,
				'receive_type'          => OrderTransaction::RECEIVE_TYPE_YES,
				'account_id'            => '',
				'parent_transaction_id' => '',
				'order_pay_time'        => $order_info['updated_at'],
				'amt'                   => $order_info['grand_total'],
				'fee_amt'               => '0',
				'currency'              => $order_info['order_currency_code'],
				'payment_status'        => 'Completed',
				'platform_code'         => $platformCode
		);
		//save transaction
		if(!OrderTransaction::model()->saveTransactionRecord($transactionId, $order_id,$orderTransaction)){
			throw new Exception('save order '.$this->_order->platform_order_id . ' transaction fail');
		}
		//free
		unset($orderTransaction);unset($additionalInfo);
		//save tranaction record  -- 构造一条交易记录
		$paymentAccount = self::getOtherPaymentAccount($order_info);
		$Records_data = array(
				'order_id' 			=> $this->_order->order_id,
				'receive_type' 		=> $this->RECEIVE_TYPE_YES,
				'receiver_business' => $platformCode,
				'receiver_email' 	=> $paymentAccount,
				'receiver_id'		=> '',
				'payer_id' 			=> '-',
				'payer_name' 		=> '-',
				'payer_email' 		=> '',
				'payer_status' 		=> '-',
				'parent_transaction_id' => '-',
				'transaction_type' 	=> $order_info['payment']['method'],
				'payment_type' 		=> '-',
				'order_time' 		=> $order_info['updated_at'],
				'amt' 				=> $order_info['grand_total'],
				'fee_amt' 			=> '0',
				'tax_amt' 			=> '0',
				'currency' 			=> $order_info['order_currency_code'],
				'payment_status' 	=> 'Completed',
				'note' 				=> '',
				'modify_time'		=> ''
		);//保存交易信息 record
		if(!OrderPaypalTransactionRecord::model()->savePaypalRecord($transactionId, $order_id,$Records_data)){
			throw new Exception('save order '.$this->_order->platform_order_id .' transaction record fail');
		}
		return true;
	}
	/**
	 * 获取paypal 之外的付款方式的收款帐号
	 * @param array $order_info 订单信息
	 * @return String 
	 */
	public static function getOtherPaymentAccount($order_info){
		$account = 0 ;//init
		if (isset($order_info['payment']['method'])){
			if(strstr($order_info['payment']['method'],self::PAYMENT_METHOD_OC_PAY)){
				$account = self::$paymentAccounts[self::PAYMENT_METHOD_OC_PAY];
			}elseif (isset(self::$paymentAccounts[$order_info['payment']['method']])){
				$account = self::$paymentAccounts[$order_info['payment']['method']];
			}
		}
		return $account;
	}
	private function  getGCTansactionId($additionalInfo){
		return self::GLOBALCOLLECT_TRANCSACTION_ID_PRE.$additionalInfo['MERCHANTREFERENCE'];
	}
	private function getStandardOrderTransactionid($order_info){
		return $order_info['payment']['method'].'-'.$order_info['increment_id'];
	}
}