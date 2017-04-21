<?php
/**
 * @desc amazon 订单模型
 * @author zhangF
 *
 */
class AmazonOrder extends AmazonModel {
	
	// ================== 特殊标记SKU =============
	public $AMAZON_SZSKU_UK = array('57459.01','57459.02','57459.03','57459.04','57459.05','57459.06','57459.07','57459.08','57999.01','57999.02','57999.03','57999.04','46782.01','30890','27581','52995','55384.02','55384.05','57263','57263.01','57263.03','54995','54995.01','52555','61242','61242.01','61242.02','61242.03','61242.04','61242.05','29890','29890.01','54170.01','54170.02','27574','1176','58085.01','58085.02','58085.03','52916','16491','54365','52806','51286','36865','31503','16474','58711.01','58711.02','55385','55385.01','55385.02','55385.03','19532','60576.02','54655','54655.01','51290','51290.01','51290.02','51290.03','51290.04','51290.05','51290.06','51290.07','51290.08','3288','57524','62842','58710.01','58710.02','64132.01','64132.02','64132.03','31505','56741','56741.01','51328','54376','54376.01','54376.02','54376.03','54376.04','54376.05','54376.06','54376.07','54376.08','54376.09','54376.10','54376.11','54376.12','54376.13','54376.14','54376.15','54376.16','54376.17','54376.18','54376.19','54376.20','54376.21','54376.22','54376.23','54376.24','54376.25','54376.26','26750','17095','60105','17565','55435','54697.01','53692','52909','54833','0267','6233','44140','43809','18166','55434.02','55434.01','27025','56368','55394','54851','2225','53356','7621','17569','23520','4158','27574','1243','56964','43992','2012','53896','27025.01','20718','54947','54947.01','54949.03','54949.01','43993','51884','58392.04','58392.1','58392.03','58392.08','58392.09','70406.02','70406.01','70406.06','0046','54696','60639','55435.01','55435.02','55129','22131.06','58012.01','58012.02','56845','56845.01','56845.02','61942.01','61942.02','61942.03','61942.04','61942.05','55384.03','55384.04','55384.06','57405','60400.01','60400.02','60400.03','6035','45363','55117','55573','5295','60105.01','60105.02','60105.03','60105.04 ','61681.02','61681.04','61681.05','68977','68978','55271','35443','72326.01','72326.02','72326.03','72326.04','4122A','52807','61899','20697','55523','76678','55673','20684','23516','23154','56730','54639','51532','56519','55053','55053.01','62512.01','62512.03','61790.06','61790.04','61790.02','64408.01','64408.02','64408.03','64408.04','53953','56838','53953.06','54507','54507.01','54507.02','54507.03','54509','54509.01','54509.02','54509.03','54510','54510.01','54510.02','54510.03','54511','54511.01','54511.02','54511.03','54512','54512.02','54512.03','53953.05','53953.02','54505','54505.01','54505.02','54505.03','54506','54506.01','54506.02','54506.03','54850','54364','52809','52330','67015','54168','6302','59713.01','59713.04','5486','52533','56279','55404.01','31658','20651.02','20626','47820');
	public $AMAZON_SZSKU_DE = array('61563.06','61563.05','1207','61561.01','55434.01','61790','67082','57621','54646','58714','62747','69425','4396','5295','53573.02','1243','52668','54718','61567.05','54949.02','54949','54949.01','54949.03','58714.01','58714.02','58714.02','61911.02','61911.04','34402','58085.01','58085.02','62512.01');
	public $AMAZON_SZGHSKU_DE = array('65458','56838','56837','54509','54509.01','54509.02','54509.03','54510','54510.02','54510.01','54510.03','54511','54511.01','54511.02','54511.03','54512','54513.02','54513.01','54513','54513.03','53575');
	public $AMAZON_FEDSKU_ES = array('32653','62133.01','62565.06','75987.01','72431','75317','75295','75315','74337','71079.01','62133.02','62133.03','71079.02','69058','73235.01','73235.02','76169','72953.01','72953.02','73527.04','73527.01','73527.02','73527.03','69773.01','69773.02','77374.02','77374.03','77374.04','77374.05','77374.06','65724.01','65724.02','65724.03','65724.04','77607','73276','52664.05','52664.04','52664.02','52664.03','73269','20857','73276','72540.01','72540.02','72540.03','59336');//
	public $AMAZON_FEDHKSKU_ES = array('75987.05','75987.02','75987.01','75987.03','75987.04','75331.01','75331.02','62455.02','69539','37993','68001','69058','69043.02','69043.01','71181','74236','73938.01','73938.03','73938.02','71121','71090.03','71090.01','71090.02','76180.01','76180.02','76180.03','76180.04','74474.01','74474.02','74293.02','74293.01','62892','78158.01','78158.02','76075.04','76075.06','76075.05','74684.01','74684.02','74640.01','74640.02','74640.03','74684.03','52337.01','52337.02','52337.03','52337.04','80028.01','80028.02','80028.03');
	public $AMAZON_SZGHSKU_FR = array('53953','54505.02','54505.03','54505','54505.01','54509.03','54509','54509.01','54509.02','56837');
	public $AMAZON_SZSKU_US = array('54679','52999','26499','61899','53381','31658','55662','55662.01','56622','56622.01','54851','54916','54443','54124','54376','54376.01','54376.02','54376.03','54376.04','54376.05','54376.06','54376.07','54376.08','54376.09','54376.1','54376.11','54376.12','54376.13','54376.14','54376.15','54376.16','54376.17','54376.18','54376.19','54376.2','54376.21','54376.22','54376.23','54376.24','54376.25','50600.01','27046.02','27046.01','70272','7105','54717','55271','0347','1071','55173','60565','55069','17295','52618','1093.01','54949','54949.01','54949.02','54949.03','4396','55394','19994','55384','55384.01','55384.02','55384.03','55384.04','57459.01','57459.02','57459.03','57459.04','57459.05','57459.06','57459.07','57459.08','36421','52807','59966','1093.02','61859.01','61859.02','61859.03','53201','50600.02','16395','54443.02','27022','66816.01','66816.02','6048','50600','66232','58392.09','58392.03','58392.05','58392.04','58392.10','52229','20366','57405','44057','1123','50679','50679.01','57524','53571','0266','54718','54718.01','54718.02','54718.03','54718.04','54718.05','54642','53272','0046','59799','61726.01','61726.02','54948','54948.01','55187.02','23154','54851','0267','54124','20493','55434','55434.02','55435','55435.01','55435.02','54947','54947.01','51823','17510','3083','5509','1243','50600.03','5295','51824','70406.01','60311','57248','57248.02','52330','58557.01','31004','69747','69763','71542','54121','55995','53573','70406.07','61273.01','61273.02','61273.03','61273.04','55673','55673.01','61829.01','61829.02','61829.03','61829.04','61829.05','61829.06','61829.07','57448','55396','56837','58008.01','58008.02','44055','31651','55120','6317A','55211','55559','52908','60608','52715.01','52715.02','52715.03','52715.04','52715.05','52715.01','52715.02','52715.03','52715.04','52715.05 ','72687.01','72687.02','72687.03','72687.04','72687.05','72687.06','72687.07','72687.08','72687.09','72687.10','72687.11','72687.12 ','65715','69490','6538E','2034A','1253A','54634','6183E','58065.06','58065.05','58065.02','58065.03','45357','6183A','20701 ','71379.01','71379.02','71379.03','56730','62149','1253A','70055','63551.01','63551.02','5432','51372','51372.01','51372.02','51372.03','55053.01','55384.05','55732','52783','56368','59397.01','59399.01','61147.02','66737.01','66737.02','66737.03','62991.01','62991.02','62991.03','27574','55833.01','54170.01','54170.02','71762','59776','62935','4158','71395','72573','61440.01','61440.02','61440.03','61440.04','61440.05','61440.06','61440.07','61440.08','72573','59405','27030','1124','51279','57998.01','57998.02','57998.03','57998.06','29183');
	public $AMAZON_DHLSKU_US = array('55594','62149','69713.01','69713.02','69713.01','69713.02','60558.01','60558.02','60558.03','56524','56524.01','56524.02','56524.03','56524.04','68037.01','68037.02 ','68037.03','68037.04','68037.05','68037.06','68037.07','68037.08','68037.09','60895');//AMAZON_DHLSKU_US   70406.01,57405
	public $AMAZON_DHLSKU_TS = array('0046','0490','22131.06','27025','27025','27025.01','27025.01','31004','31503','31505','35443','35443','46782.01','46782.01','46782.01','51328','52555','52995','53394','54347','54347.01','54347.02','54347.03','54365','55594','56964','57405','57748.01','58557.01','58710.01','58710.01','58710.02','58710.02','58711.01','58711.01','58711.02','58711.02','58714','58714.01','58714.02','59799','61242','61726.01','61726.02','64132.01','64132.01','64132.02','64132.02','64132.03','64132.03','68001','69043.01','69043.01','69043.02','69043.02','69058','69058','69713.01','69713.02','71079.01','71079.01','71079.02','71079.02','71090.01','71090.01','71090.02','71090.02','71090.03','71090.03','71181','71181','71538','71542','71640.01','71640.02','72528','73938.01','73938.02','73938.03','74104','74236','74293.01','74293.01','74293.02','74293.02','74474.01','74474.01','74474.02','74474.02','75331.01','75331.02','75987.01','75987.01','75987.02','75987.02','75987.03','75987.03','75987.04','75987.04','75987.05','75987.05','76180.01','76180.01','76180.02','76180.02','76180.03','76180.03','76180.04','76180.04');
	// ================== 特殊标记 ==============
	
	const EVENT_NAME = 'getorder';
	
	/** @var 补拉程序 */
	const EVENT_PULL_UP_NAME = 'pull_up_order';
	
	/** @var object 拉单返回信息*/
	public $orderResponse = null;
	
	/** @var int 账号ID*/
	public $_accountID = null;
	/** @var string 账号对应站点 */
	public $_accountSite = '';
	/** @var string 异常信息*/
	public $exception = null;
	
	/** @var int 日志编号*/
	public $_logID = 0;	
	public $shipCountry = "";
	public $shipCountryName = "";
	/** @var 拉单类型 */
	private $_pullOrderType = null;
	/** @var 拉单状态*/
	private $_orderStatus = null;
	
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
	 * @desc 获取拉单时间段
	 */
	public function getTimeArr($accountID, $eventName = self::EVENT_NAME){
		$lastLog = AmazonLog::model()->getLastLogByCondition(array(
				'account_id'    => $accountID,
				'event'         => $eventName,
				'status'        => AmazonLog::STATUS_SUCCESS,
		));
		$eventLog = null;
		if($lastLog){
			//取出对应的eventLog
			$eventLog = AmazonLog::model()->getEventLogListByLogID($eventName, $lastLog['id']);
		}
		return array(
				'start_time'    => !empty($eventLog) ? str_replace(" ", "T", date("Y-m-d H:i:s", strtotime($eventLog['end_time'])-8*24*3600-30*60)) : date('Y-m-d\TH:i:s',time() - 86400*7 - 8*3600),
				'end_time'      => date('Y-m-d\TH:i:s',time() - 8*3600 - 5*60),	//参数时间不能比提交时间晚，且必须是提交时间前两分钟
		);
	}
	
	/**
	 * @desc 设置账号ID
	 */
	public function setAccountID($accountID){
		$accountInfo = AmazonAccount::model()->findByPk($accountID);
		if($accountInfo){
			$this->_accountSite = strtoupper($accountInfo->country_code);
		}
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
	 * @desc 设置拉单类型（补拉pull_up_order，正常getorder）
	 * @param string $type
	 */
	public function setPullOrderType($type = 'getorder'){
		$this->_pullOrderType = $type;
	}
	/**
	 * @desc 设置订单状态
	 * @param unknown $orderStatus
	 */
	public function setOrderStatus($orderStatus = array()){
		$this->_orderStatus = $orderStatus;
	}
	/**
	 * @desc 获取amazon订单方法
	 * @param array $timeArr
	 * @return boolean
	 */
	public function getOrders($timeArr, $fillChannel) {
		$fillChannel = strtoupper($fillChannel);
		$accountID = $this->_accountID;
		$path = 'amazon/getOrders/'.date("Ymd").'/'.$accountID.'/'.date("His");
		try {
			$request = new ListOrdersRequest();
			/* $request->setStartTime($timeArr['start_time']);
			$request->setEndTime($timeArr['end_time']); */
			$request->setFulfillmentChannel($fillChannel);
			$request->setStartUpdateTime($timeArr['start_time']);
			if(!empty($timeArr['end_time']))
				$request->setEndUpdateTime($timeArr['end_time']);

			if($fillChannel == ListOrdersRequest::ORDER_FULLFILLMENTCHANNEL_FBA){
				$request->setOrderStatus(array( ListOrdersRequest::ORDER_STATUS_SHIPPED));
			}else{
				$request->setOrderStatus(array( /* ListOrdersRequest::ORDER_STATUS_CANCELED,  */
								//ListOrdersRequest::ORDER_STATUS_SHIPPED,
								ListOrdersRequest::ORDER_STATUS_UNSHIPPED, 
								ListOrdersRequest::ORDER_STATUS_PARTIALLY_SHIPPED));
				if($this->_orderStatus){
					$request->setOrderStatus(
							$this->_orderStatus
					);
				}
			}
			$request->setCaller(self::EVENT_NAME);
			$response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
			//MHelper::writefilelog($path.'/response.log', print_r($response,true)."\r\n");// fortest

			/* echo $request->getErrorMsg(); */
			if (!empty($response)) {
				return $this->saveOrders($response, $request);
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
	 * @desc 添加到订单表中
	 * @param unknown $response
	 * @throws Exception
	 * @return boolean
	 */
	public function saveOrders($orderDatas, $request){
		try {
			if (!empty($orderDatas)) {
				$path = 'amazon/getOrderItems/'.date("Ymd").'/'.$this->_accountID.'/'.date("His");
				foreach ($orderDatas as $orderData) {
					
					if(!is_array($orderData) || !isset($orderData['AmazonOrderId'])) continue;
					//$this->lastUpdateDate = $orderData['LastUpdateDate'];
					//超过30天的订单直接过滤掉
					$minDate = str_replace(" ", "T", date("Y-m-d H:i:s", time()-60*24*3600-8*3600)."Z");
					if($orderData['PurchaseDate'] <= $minDate){//小于1月前过滤掉
						continue;
					}

			        $order_id  = AutoCode::getCodeNew('order'); // 获取订单号
			        if ( empty($order_id) ) {
			            throw new Exception("getCodeNew Error");
			        } else {
			            $order_id = $order_id . 'AZ';
			        }

					$amazonOrderID = $orderData['AmazonOrderId'];
					//对比现有表中对应的订单
					$orderInfo = Order::model()->getOrderInfoByPlatformOrderID($amazonOrderID, Platform::CODE_AMAZON);
					if( $orderData['OrderStatus'] == ListOrdersRequest::ORDER_STATUS_CANCELED ){//退款订单
						if( !empty($orderInfo) && $orderInfo['ship_status']==Order::SHIP_STATUS_NOT ){//未出货的订单可以取消
							Order::model()->cancelOrders($orderInfo['order_id']);
						}
						continue;//已退款订单跳过
					}
					if(empty($orderInfo)){
						//去历史表获取，又太慢了，不去获取我又好担忧
					}
					if( !empty($orderInfo) && $orderInfo['payment_status']==Order::PAYMENT_STATUS_END ){//存在已付款的订单，不更新
						continue;
					}
					
					$orderID = !empty($orderInfo) ? $orderInfo['order_id'] : $order_id;//获取订单号

			        //订单号重复检查
			        $tmpOrder = Order::model()->getInfoByOrderId($orderID,'order_id');
			        if (!empty($tmpOrder)) {
			            throw new Exception($orderID.'订单号重复!');
			        }					

					$orderItemsResponse = $this->getOrderItems($amazonOrderID);
					//MHelper::writefilelog($path.'/response.log', print_r($orderItemsResponse,true)."\r\n");// fortest
					//获取订单详细信息，如果没有获取到失败直接返回
					if (empty($orderItemsResponse))
						throw new Exception($this->getExceptionMessage());
						
					try{
						//生成事物
						$transaction = $this->dbConnection->beginTransaction();
						/** 1.保存订单主数据*/
						$orderID = $this->saveOrderInfo($orderID, $orderData);
						if($orderID){
							/** 2.保存订单详情信息*/
							$this->saveOrderItems($orderID, $orderItemsResponse, $orderData);
							/** 3.保存交易信息*/
							$this->saveTransaction($orderID, $orderData);
							/** 4.保存发货时间*/
							$this->saveShipDate($orderID, $orderData);
							/** 5.保存付款信息*/
							$this->saveOrderPaypalTransactionRecord($orderID, $orderData);
						}else{
							throw new Exception("save order info failure!");
						}
						$transaction->commit();
					}catch (Exception $e){
						echo 'ocuur error1: '.$e->getMessage()."<br>";
						$transaction->rollback();
						throw new Exception($e->getMessage());
					}
						
				}
			} else {
				echo 'ocuur error2: '.$request->getErrorMsg()."<br>";
				$this->setExceptionMessage($request->getErrorMsg());
				return false;
			}
		} catch (Exception $e) {
			echo 'ocuur error3: '.$e->getMessage()."<br>";
			$this->setExceptionMessage($e->getMessage());
			return false;
		}
		return true;
	}

	/**
	 * @desc 获取Amazon订单商品信息
	 * @param integer $amazonId
	 * @return boolean|multitype:multitype: unknown
	 */
	public function getOrderItems($amazonId) {
		$request = new ListOrderItemsRequest();
		$request->setCaller(self::EVENT_NAME);
		$request->setOrderId($amazonId);
		$response = $request->setAccount($this->_accountID)->setRequest()->sendRequest()->getResponse();
		if (empty($response)) {
			$this->setExceptionMessage($request->getErrorMsg().": {$amazonId}");
			return false;
		}
		//@desc 识别到部分发货会有二维数组出现  
		//@notice add by lihy  in 2016-01-11
		$newResponse = array();
		foreach ($response as $k=>$res){
			if(empty($res['SellerSKU']) && is_array($res[0])){
				$newResponse = array_merge($newResponse, $res);
			}else{
				$newResponse[] = $res;
			}
		}
		unset($response, $res);
		return $newResponse;
	}
	
	/**
	 * @desc 保存订单主表信息
	 * @param integer $orderId
	 * @param array $orderData
	 * @return integer
	 */
	public function saveOrderInfo($orderId, $orderData) {
		//运费方式
		$shipType = "";
		$warehouse_id = 1;
		$isASS = 0;			#是否平台发货 Rex 16.07.04 加
		//如果是FBA的订单，查找对应账号走的哪个FBA仓库
		if ($orderData['FulfillmentChannel'] == ListOrdersRequest::ORDER_FULLFILLMENTCHANNEL_FBA) {
			$accountWarehouse = include CONF_PATH . 'amazonAccountWarehouse.php';
			if (!empty($accountWarehouse) && array_key_exists($this->_accountID, $accountWarehouse))
				$warehouse_id = $accountWarehouse[$this->_accountID];
			$shipType = "amazon";
			$shipStatus = Order::SHIP_STATUS_YES;
			$completeStatus = Order::COMPLETE_STATUS_END;
			$isASS = 1;
		}else{
			if($orderData['OrderStatus'] == ListOrdersRequest::ORDER_STATUS_PARTIALLY_SHIPPED){
				$shipStatus = Order::SHIP_STATUS_PART;
				$completeStatus = Order::COMPLETE_STATUS_DEFAULT;
			}elseif($orderData['OrderStatus'] == ListOrdersRequest::ORDER_STATUS_UNSHIPPED){
				$shipStatus = Order::SHIP_STATUS_NOT;
				$completeStatus = Order::COMPLETE_STATUS_DEFAULT;
			}elseif($orderData['OrderStatus'] == ListOrdersRequest::ORDER_STATUS_SHIPPED){
				$shipStatus = Order::SHIP_STATUS_NOT;
				$completeStatus = Order::COMPLETE_STATUS_DEFAULT;
			}
		}
		$UKisland = array('Isle of Man', 'Jersey', 'Wales', 'Scotland', 'Northern Ireland', 'Guernsey');
		$shipCountry = trim($orderData['ShippingAddress']['CountryCode']);
		$stateOrRegion = isset($orderData['ShippingAddress']['StateOrRegion']) ? trim($orderData['ShippingAddress']['StateOrRegion']) : '';
		// ================ S:英国国家所属岛屿的处理 ================
		if($shipCountry != "GB" && in_array($stateOrRegion, $UKisland)){
			$this->shipCountry = $shipCountry = "GB";
		}
		if ($shipCountry == 'JE') {
			$this->shipCountry = $shipCountry = "GB";
		}
		if($shipCountry == "IM" || $shipCountry == "GG" ){
			$this->shipCountry = $shipCountry = "GB";
			if($shipCountry == "IM"){
				$orderData['ShippingAddress']['StateOrRegion'] = "Isle of Man";
			}
		}
		// ================ E:英国国家所属岛屿的处理================
		
		$this->shipCountryName = $shipCountryName = Country::model()->getEnNameByAbbr($shipCountry);
		//订单主表数据
		$flag = Order::model()->saveOrderRecord(array(
				'order_id'              => $orderId,
				'platform_code'         => Platform::CODE_AMAZON,
				'platform_order_id'     => trim($orderData['AmazonOrderId']),
				'account_id'            => $this->_accountID,
				'log_id'                => $this->_logID,
				'order_status'          => trim($orderData['OrderStatus']),
				'buyer_id'              => trim($orderData['BuyerName']),
				'email'                 => trim($orderData['BuyerEmail']),
				'timestamp'             => date('Y-m-d H:i:s'),
				'created_time'          => AmazonList::transactionUTCTimeFormat($orderData['PurchaseDate']),
				'last_update_time'      => AmazonList::transactionUTCTimeFormat($orderData['LastUpdateDate']),
				'ship_cost'             => 0.00,		//运费在订单商品中累加更新
				'subtotal_price'        => 0.00,
				'total_price'           => floatval($orderData['OrderTotal']['Amount']),
				'currency'              => $orderData['OrderTotal']['CurrencyCode'],
				'ship_country'          => $shipCountry,
				'ship_country_name'     => $shipCountryName,
				'paytime'               => AmazonList::transactionUTCTimeFormat($orderData['PurchaseDate']),
				'payment_status'        => Order::PAYMENT_STATUS_END,
				'ship_phone'            => isset($orderData['ShippingAddress']['Phone']) ? trim($orderData['ShippingAddress']['Phone']) : '',
				'ship_name'             => trim($orderData['ShippingAddress']['Name']),
				'ship_street1'          => isset($orderData['ShippingAddress']['AddressLine1']) ? trim($orderData['ShippingAddress']['AddressLine1']) : '',
				'ship_street2'          => isset($orderData['ShippingAddress']['AddressLine2']) ? trim($orderData['ShippingAddress']['AddressLine2']) : '',
				'ship_zip'              => isset($orderData['ShippingAddress']['PostalCode']) ? trim($orderData['ShippingAddress']['PostalCode']) : '',
				'ship_city_name'        => isset($orderData['ShippingAddress']['City']) ? trim($orderData['ShippingAddress']['City']) : '',
				'ship_stateorprovince'  => isset($orderData['ShippingAddress']['StateOrRegion']) ? trim($orderData['ShippingAddress']['StateOrRegion']) : '',
				'is_multi_warehouse'	=> $warehouse_id,
				'ship_code'				=>	$shipType,
				'ship_status'			=>	$shipStatus,
				'complete_status'		=>	$completeStatus,
				//add lihy 2016-04-13
				'ori_create_time'      => date('Y-m-d H:i:s', strtotime($orderData['PurchaseDate'])),
				'ori_update_time'      	=> date('Y-m-d H:i:s', strtotime($orderData['LastUpdateDate'])),
				'ori_pay_time'           => date('Y-m-d H:i:s', strtotime($orderData['PurchaseDate'])),
				'is_ASS'				=> $isASS,
		));
		if(!$flag){
			throw new Exception("Save Order Record Failure");
		}
		return $orderId;		
	}
	
	/**
	 * @desc 获取物流方式
	 * @param  array $oderInfo   array(
	 *								'ship_country_code'=>'',
	 *								'order_total'=>'',
	 *								'ship_country_name'=>'',
	 *								'ship_free'	=>	0,
	 *								'total_weight'=>0
	 *							);
	 * @param array $items[]  array(
	 * 									'sku'=>''
	 * 								)
	 */
	public function  getShipType($oderInfo, $items){
		$ship_type = '';
		$ship_types = array();
		$ukAccount = array(4, 8, 9);//vakind_uk、vakind_uk_a3、vakind_uk_b
		/*获取每个条目的属性*/
		$attributes = array();
		foreach($items as $item){
			$attribute = self::model('ProductSelectAttribute')->getProductAttributeIds($item['sku']);
			if(isset($attribute[$item['sku']]))
				$attributes[$item['sku']] = $attribute[$item['sku']];
		}
		unset($attribute);
		//特殊属性系统选择物流
		if($this->_accountID == 4||$this->_accountID==8||$this->_accountID==9){//uk
			if(in_array(strtoupper($oderInfo['ship_country_code']),array('GB','GG','JE'))){//UK,Jersey,Guernsey
				if($oderInfo['order_total']<18){ //order_total
					$ship_types = array(Logistics::CODE_DHL_XB,Logistics::CODE_ZE,Logistics::CODE_CM_CESL);
				}else{
					$ship_types = array(Logistics::CODE_DHL_XB,Logistics::CODE_ZE,Logistics::CODE_CM_CESL,Logistics::CODE_FEDEX_IE);
				}
			}else{
				$ship_type = Logistics::CODE_FEDEX_IE;
			}
			//2014-6-21 penny的特殊要求，Fox add  uk
			if($this->_accountID == 4){
				$szsku = $this->AMAZON_SZSKU_UK;
				$v = true;
				foreach($items as $item){
					if (!in_array($item['sku'],$szsku)){
						$v = false;
						continue 1;
					}
				}
				if($v==true){
					$ship_type = Logistics::CODE_CM_GZ;//广州小包
				}
			}

			if($this->_accountID == 8){//uk_a3
				//检测产品属性
				$diff = false;
				foreach($items as $item){
					if(!isset($attributes[$item['sku']])) continue;
					$attrs = $attributes[$item['sku']];
					$diff = array_intersect(self::model('ProductAttribute')->amazon_special_attribute, $attrs);//有交集，则为带电池产品
					if(!empty($diff)){
						$diff = true;
						continue 1;
					}
				}
				if(empty($diff)){
					if($oderInfo['order_total'] > 10){
						$ship_types = array(Logistics::CODE_DHL_XB,Logistics::CODE_ZE,Logistics::CODE_CM_CESL);
					}else{
						$ship_types = array(Logistics::CODE_CM_GZ);
					}
					//$ship_type = Logistics::CODE_CM_CNXB;//深圳小包
						
				}
		
			}
			
			if($this->_accountID==8 && $shipfree > 0){
				$ship_types = array(Logistics::CODE_FEDEX_IE,Logistics::CODE_FEDEX_IE_HK);
			}
				
		}elseif($this->_accountID == 16){
			if(!in_array(strtoupper($oderInfo['ship_country_code']),array('GB','UK')) || $oderInfo['total_weight'] > 2000){//付运费的和重量超过2kg的用fedexie
				$ship_type = Logistics::CODE_FEDEX_IE;
			}else{
				$ship_types = array(Logistics::CODE_DHL_XB,Logistics::CODE_CM_GZ,Logistics::CODE_ZE,Logistics::CODE_CM_CESL); //其它系统自动选走深圳或DHL小包
			}
		}elseif($this->_accountID == 5 || $this->_accountID==11 || $this->_accountID==12 || $this->_accountID==17){//de站点
			if(strtoupper($oderInfo['ship_country_code']) == 'DE'){ //如果是德国买家
				if($oderInfo['order_total']>20){//20欧以上发中欧专线?
					$ship_type = Logistics::CODE_DHL_XB_DE;
				}else{
					$ship_type = Logistics::CODE_DHL_XB_DE; //走DHL清关小包
				}
			}else{ //其他欧洲国家走中欧洲专线
				$ship_type = Logistics::CODE_DHL_XB;
			}
			if($this->_accountID == 5 || $this->_accountID==17){//指定走深圳小包  de
				$tsku = array();
				$tsku = $this->AMAZON_SZSKU_DE;
				$v = true;
				foreach($items as $item){
					if (!in_array($item['sku'],$tsku)){
						$v = false;
						continue 1;
					}
				}
				if($v==true){
					$ship_type = Logistics::CODE_CM_GZ;//深圳小包
				}
				if($this->_accountID == 5 && strtoupper($oderInfo['ship_country_code']) == 'AUT'){
					$ship_type = Logistics::CODE_DHL_XB;//奥地利的改为中欧专线   cm_cesl
				}
				$szghsku = array();
				$szghsku = $this->AMAZON_SZGHSKU_DE;
				$gh = true;
				foreach($items as $item){
					if (!in_array($item['sku'],$szghsku)){
						$gh = false;
						continue 1;
					}
				}
				if($gh==true){
					$ship_type = Logistics::CODE_GHXB_CN;
				}
			}
		}elseif($this->_accountID==7 || $this->_accountID==15 || $this->_accountID==19 || $this->_accountID==20){//fr  es西班牙走DHL小包/中欧专线 西班牙以外的走fedex-ie
			$ship_types = array(Logistics::CODE_DHL_XB,Logistics::CODE_FEDEX_IE,Logistics::CODE_ZE,Logistics::CODE_CM_CESL);
			if(strtoupper($oderInfo['ship_country_code']) != 'FR' && ($this->_accountID==7 || $this->_accountID==19 || $this->_accountID==20)){
				$ship_types = array(Logistics::CODE_FEDEX_IE,Logistics::CODE_DHL_XB,Logistics::CODE_ZE,Logistics::CODE_CM_CESL);
			}
			if(strtoupper($oderInfo['ship_country_code']) != 'ES' && $this->_accountID==15){
				$ship_types = array(Logistics::CODE_FEDEX_IE);
			}
				
			if($this->_accountID==7 || $this->_accountID==19 || $this->_accountID==20){
				$ship_types = array(Logistics::CODE_ZE,Logistics::CODE_CM_CESL,Logistics::CODE_DHL_XB);
			}
				
			if($this->_accountID==7 && $oderInfo['order_total']<7){
				$ship_types = array(Logistics::CODE_DHL_XB);
			}
				
			if($this->_accountID==7){
				//54365 54365.01
				$szsku = array('54365','54365.01');
				$v = true;
				foreach($items as $item){
					if (!in_array($item['sku'],$szsku)){
						$v = false;
						continue 1;
					}
				}
				if($v==true){
					$ship_type = Logistics::CODE_DHL_XB;
				}else{
					$ship_types = array(Logistics::CODE_CM_CESL);
				}
		
			}
				
			/*if($val['order_total']>22){
			 unset($ship_types[0]);//删除cm_dhl
			}*/
			if(($this->_accountID==7 || $this->_accountID==19 || $this->_accountID==20) && (strtoupper($oderInfo['ship_country_code']) == 'BE' || strtoupper($oderInfo['ship_country_code']) == 'CH' || strtoupper($oderInfo['ship_country_code']) == 'MCO')){
				$ship_types = array(Logistics::CODE_FEDEX_IE);
			}
			if(($this->_accountID==7 || $this->_accountID==19 || $this->_accountID==20) && strtoupper($oderInfo['ship_country_code']) == 'FR' && $oderInfo['order_total']>=20){
				$ship_types = array(Logistics::CODE_GHXB_CN,Logistics::CODE_GHXB_GZ);
			}
				
				
			if($this->_accountID==15){
				//AMAZON_FEDSKU_ES
				$tsku = array();
				$tsku = $this->AMAZON_FEDSKU_ES;
				$v = true;
				foreach($items as $item){
					if (!in_array($item['sku'],$tsku)){
						$v = false;
						continue 1;
					}
				}
				if($v==true){
					$ship_type = Logistics::CODE_FEDEX_IE;//
				}
		
				$hksku = array();
				$hksku = $this->AMAZON_FEDHKSKU_ES;
				$hk = true;
				foreach($items as $item){
					if (!in_array($item['sku'],$hksku)){
						$hk = false;
						continue 1;
					}
				}
				if($hk==true){
					$ship_type = Logistics::CODE_FEDEX_IE_HK;
				}
		
		
			}
			if($this->_accountID==15 && $oderInfo['order_total']>=22){
				$ship_type = Logistics::CODE_CM_CESL;//
			}
				
				
			if($this->_accountID==7){
				$szghsku = array();
				$szghsku = $this->AMAZON_SZGHSKU_FR;
				$gh = true;
				foreach($items as $item){
					if (!in_array($item['sku'],$szghsku)){
						$gh = false;
						continue 1;
					}
				}
				foreach($items as $item){
					if(!isset($attributes[$item['sku']])) continue;
					$attrs = $attributes[$item['sku']];
					$diff = array_intersect(self::model('ProductAttribute')->amazon_special_attribute, $attrs);//有交集，则为带电池产品
					if(!empty($diff)){
						$diff = true;
						continue 1;
					}
				}
		
				if($gh==true){
					if(!$diff){
						$ship_type = Logistics::CODE_GHXB_CN;
					}else{
						$ship_type = Logistics::CODE_DHL_XB;
					}
				}
			}
				
		}elseif($this->_accountID==14){//jp
			$ship_types = array(Logistics::CODE_GHXB_CN,Logistics::CODE_GHXB_HK);
		}elseif($this->_accountID == 3 || $this->_accountID==6 || $this->_accountID==10 || $this->_accountID==13){//us
			if(strtoupper($oderInfo['ship_country_code']) == 'US'){
				//超过一百美金走联邦快递  2014-05-30
				if($oderInfo['order_total']>100 && $this->_accountID!=13){
					$ship_type = Logistics::CODE_FEDEX_IE;
				}else{
					// by tom 2014-05-29
					$ship_types = array(
							Logistics::CODE_EUB,Logistics::CODE_GHXB_HK,Logistics::CODE_FEDEX_IE
					);
				}
				if($this->_accountID==13){
					if($oderInfo['order_total']>120 || $oderInfo['ship_free'] > 0){
						$ship_type = Logistics::CODE_FEDEX_IE;
					}elseif($oderInfo['order_total']<=8){
						$ship_type = Logistics::CODE_CM_GZ;
					}elseif($oderInfo['order_total']>8){
						$ship_types = array(
								Logistics::CODE_EUB,Logistics::CODE_GHXB_HK,Logistics::CODE_FEDEX_IE
						);
					}
				}
			}else{
				$ship_type = Logistics::CODE_FEDEX_IE;
			}
				
				
			//2014-6-21 penny的特殊要求，Fox add  us
			if($this->_accountID == 3 && $oderInfo['order_total']<15){
				$szsku = array();
				$szsku = $this->AMAZON_SZSKU_US;
				$v = true;
				foreach($items as $item){
					if (!in_array($item['sku'],$szsku)){
						$v = false;
						continue 1;
					}
				}
				if($v==true){
					$ship_type = Logistics::CODE_CM_GZ;//广州小包
					//$ship_types = array(Logistics::CODE_CM_CNXB);//深圳小包
				}
		
				$skudhl = array();
				$skudhl = $this->AMAZON_DHLSKU_US;
				$x = true;
				foreach($items as $item){
					if (!in_array($item['sku'],$skudhl)){
						$x = false;
						continue 1;
					}
				}
				if($x==true){
					//$ship_type = Logistics::CODE_DHL_XB;
					$ship_types = array(Logistics::CODE_DHL_XB,Logistics::CODE_ZE,Logistics::CODE_CM_CESL);//DHL
				}
		
				if($oderInfo['order_total']<10){
					$ship_types = array(Logistics::CODE_CM_GZ,Logistics::CODE_DHL_XB,Logistics::CODE_ZE,Logistics::CODE_CM_CESL);
				}
		
			}
			if($this->_accountID == 6){
				if($oderInfo['order_total'] > 10){
					$ship_type = Logistics::CODE_EUB;
					//$ship_types = (Logistics::CODE_EUB);
				}else{
					$ship_type = Logistics::CODE_CM_GZ;
				}
			}
				
			//检测产品属性
			$diff = false;
			foreach($items as $item){
				if(!isset($attributes[$item['sku']])) continue;
				$attrs = $attributes[$item['sku']];
				$diff = array_intersect(self::model('ProductAttribute')->amazon_special_attribute, $attrs);//有交集，则为带电池产品
				if(!empty($diff)){
					$diff = true;
					continue 1;
				}
			}
			if($diff==true){
				$ship_type = Logistics::CODE_DHL_XB;
			}
				
			if($this->_accountID==6 && $shipfree > 0){
				$ship_types = array(Logistics::CODE_FEDEX_IE,Logistics::CODE_FEDEX_IE_HK);
			}
				
		}
		if($this->_accountID == 3 || $this->_accountID==4 || $this->_accountID==6 || $this->_accountID==8){
			$dhlsku = array();
			$dhlsku = $this->AMAZON_DHLSKU_TS;
			$v = true;
			foreach($items as $item){
				if (!in_array($item['sku'],$dhlsku)){
					$v = false;
					continue 1;
				}
			}
			if($v==true){
				$ship_type = Logistics::CODE_DHL_XB;//DHL小包
			}
		}
		//美国新帐号缺少ship_type的判断
		if($ship_type==''){
			$min_ship_cost = 0;
			$proAttributes = array();
			if($attributes){
				foreach ($attributes as $attri){
					$proAttributes = array_merge($proAttributes, $attri);
				}
			}
			foreach ($ship_types as $code){
				$ship_cost = self::model("Logistics")->getShipFee($code, $oderInfo['total_weight'], array(
						'attributeid'		=>	array_unique($proAttributes),
						'country'			=> $oderInfo['ship_country_name'],
						'discount'			=> 1,
						'volume'			=> 0,
						'warehouse'			=> '',
				));
				
				if($ship_type=='' || $ship_cost>0 && ($ship_cost<$min_ship_cost || $min_ship_cost<=0)){
					$min_ship_cost = $ship_cost;
					$ship_type = $code;
					break;
				}
			}
		}
		return $ship_type;
	}

	/**
	 * @desc 保存订单商品信息
	 * @param integer $orderId
	 * @param array $itemsDatas
	 * @return boolean
	 */
	public function saveOrderItems($orderId, $itemsDatas, $orderData) {
		$path = 'amazon/saveOrderItems/'.date("Ymd").'/'.$this->_accountID.'/'.date("His");
		//删除原有订单详情
		OrderDetail::model()->deleteOrderDetailByOrderID($orderId);
		$totalShippingPrice = 0;//订单总运费
		$totalTaxFee 		= 0;//总税费
		$totalDiscount 		= 0;//折扣总金额
		$subtotalPrice      = 0;//订单商品总金额
		$totalWeight        = 0;//订单sku总重量
		$shipFee            = 0;
		$encryptSku         = new encryptSku();
		$itemSkus           = array();
		$shipCoutryName     = $this->shipCountryName;
		$orderExceptionMsg  = "";
		$partDetailInfos  = array();
		foreach ($itemsDatas as $itemsData) {
			$asin 	   = $itemsData['ASIN'];
			$skuOnline = $itemsData['SellerSKU'];
			$sku       = $encryptSku->getAmazonRealSku2($skuOnline);
			$skuInfo   = Product::model()->getProductInfoBySku($sku);
			$quantity  = (int)$itemsData['QuantityOrdered'];
			//商品总金额
			$itemPrice = floatval($itemsData['ItemPrice']['Amount']);	//商品小计金额
			$salePrice = round($itemPrice / $quantity, 2);	//商品单价

			//运费
			$shipPrice = isset($itemsData['ShippingPrice']['Amount']) ? floatval($itemsData['ShippingPrice']['Amount']) : 0.00;
			$totalShippingPrice += $shipPrice;
			$currency = trim($itemsData['ItemPrice']['CurrencyCode']);
			$subtotalPrice += $itemPrice;

			//计算税费
			if (isset($itemsData['ItemTax']['Amount'])) {
				$totalTaxFee += $itemsData['ItemTax']['Amount'];
			}
			if (isset($itemsData['ShippingTax']['Amount'])) {
				$totalTaxFee += $itemsData['ShippingTax']['Amount'];
			}
			if (isset($itemsData['GiftWrapTax']['Amount'])) {
				$totalTaxFee += $itemsData['GiftWrapTax']['Amount'];
			}

			//计算折扣金额
			if (isset($itemsData['ShippingDiscount']['Amount'])) {
				$totalDiscount += $itemsData['ShippingDiscount']['Amount'];
			}

			if (isset($itemsData['PromotionDiscount']['Amount'])) {
				$totalDiscount += $itemsData['PromotionDiscount']['Amount'];
			}			

			if( !empty($skuInfo) ){//可以查到对应产品
				$realProduct = Product::model()->getRealSkuList($sku, $quantity);
				$itemSkus[]['sku'] = $realProduct['sku'];
			}else{
				$realProduct = array(
						'sku'       => 'unknow',
						'quantity'  => $quantity,
				);
				Order::model()->setOrderCompleteStatus(Order::COMPLETE_STATUS_PENGDING, $orderId);
			}
			if($skuInfo && $skuInfo['product_is_multi'] == Product::PRODUCT_MULTIPLE_MAIN){
				$childSku = ProductSelectAttribute::model()->getChildSKUListByProductID($skuInfo['id']);
	    		!empty($childSku) && $orderExceptionMsg .= "sku:{$sku} 为主SKU <br/>";
			}
			//保存
			$orderItemRow = array(
					'transaction_id'          => $orderData['AmazonOrderId'],
					'order_id'                => $orderId,
					'platform_code'           => Platform::CODE_AMAZON,
					'item_id'                 => $itemsData['OrderItemId'],
					'title'                   => trim($itemsData['Title']),
					'sku_old'                 => $sku == ''? 'unknow' : $sku,
					'sku'                     => $realProduct['sku'],
					'site'                    => $this->_accountSite,
					'quantity_old'            => $quantity,
					'quantity'                => $realProduct['quantity'],
					'ship_price'			  => $realProduct['quantity'] ? round($shipPrice/$realProduct['quantity'], 2) : 0,
					'sale_price'              => $salePrice,
					'total_price'             => $itemPrice,
					'currency'                => $currency,
			);

			//通过amazon海外仓和asin映射表(ueb_amazon_overseas_warehouse_asin_map)，把对应的海外仓的asin+在线SKU订单添加海外仓ID
			if (!empty($asin)){
				$amazonAsinWarehouseModel = new AmazonAsinWarehouse();
				$wret = $amazonAsinWarehouseModel->getWarehouseInfoByAsin($asin,$skuOnline);
				if ($wret){
					$orderItemRow['warehouse_id'] = (int)$wret['overseas_warehouse_id'];
				}				
			}
			
			$orderItemID = OrderDetail::model()->addOrderDetail($orderItemRow);	
			if(!$orderItemID){
				throw new Exception("save order item failure");
			} 	
			//保存amazon线上sku
			$sellerFlag = OrderSellerSkus::model()->saveSellerSku(array(
				'order_id'			=>$orderId,
				'detail_id'			=>$orderItemID,
				'platform_code'		=>Platform::CODE_AMAZON,
				'seller_sku'		=>$skuOnline
			));		
			if(!$sellerFlag){
				throw new Exception("save order item seller sku failure");
			}	
			$shipFee += $realProduct['quantity'] ? round($shipPrice/$realProduct['quantity'], 2) : 0;
			if($skuInfo)
				$totalWeight += floatval($skuInfo['product_weight'])*intval($realProduct['quantity']);
			//判断是否需要添加插头数据
			$flag = OrderDetail::model()->addOrderAdapter(array(
					'order_id'          =>	$orderId,
					'ship_country_name' =>	$shipCoutryName,
					'platform_code'     =>	Platform::CODE_AMAZON,
					'currency'          =>	$currency
			),	$realProduct);
			if(!$flag) throw new Exception($orderData['AmazonOrderId'] . ": Save order adapter failure");

	        //保存订单sku与销售关系数据
	        $part_data = array(
	            'platform_code'         => Platform::CODE_AMAZON,//平台code
	            'platform_order_id'     => $orderData['AmazonOrderId'],//平台订单号
	            'online_sku'            => $skuOnline,//在线sku
	            'account_id'            => $this->_accountID,//账号id
	            'site'                  => $orderItemRow['site'],//站点
	            'sku'                   => $orderItemRow['sku_old'],//系统sku
	            'item_id'               => $asin,//主产品id
	            'order_id'              => $orderId,//系统订单号
	        );
	        $partDetailInfos[] = $part_data;
		}
		
		//判断是否有异常存在
		if($orderExceptionMsg){
			$res = Order::model()->setExceptionOrder($orderId, OrderExceptionCheck::EXCEPTION_SKU_UNKNOWN, $orderExceptionMsg);
			if(! $res){
				throw new Exception ( 'Set order Exception Failure: '.$orderId);
			}
		}
		
		$ship_country_name = Country::model()->getEnNameByAbbr(trim($orderData['ShippingAddress']['CountryCode']));
		$oderInfo = array(
			'ship_country_code' 	=> trim($orderData['ShippingAddress']['CountryCode']),
			'order_total'       	=> floatval($orderData['OrderTotal']['Amount']),
			'ship_country_name' 	=> $ship_country_name,
			'total_weight'      	=> $totalWeight,
			'ship_free'             => $shipFee
	 	);
		
		//更新订单运费和物流方式
		$order = Order::model();
		$updateOrderData = array(
			'ship_cost' 	 => $totalShippingPrice, 		
			'subtotal_price' => $subtotalPrice
		);
		if ($orderData['FulfillmentChannel'] != ListOrdersRequest::ORDER_FULLFILLMENTCHANNEL_FBA) {
			//获取物流方式
			$shipType = $this->getShipType($oderInfo, $itemSkus);
			$updateOrderData['ship_code'] = $shipType;
		}
		$flag = Order::model()->updateByPk($orderId, $updateOrderData);
		if(!$flag){
			throw new Exception("save order item failure");
		}

		//更新订单优惠金额信息
		$orderExtendInfo = array(
			'order_id'          => $orderId,
			'platform_code'     => Platform::CODE_AMAZON,
			'platform_order_id' => $orderData['AmazonOrderId'],//平台订单号
			'account_id'        => $this->_accountID,//账号id
			'tax_fee'           => $totalTaxFee,
			'coupon_price'      => $totalDiscount,
			'currency' 			=> $currency

		);
		if ($totalTaxFee>0 || $totalDiscount>0) {
			$orderExtend = new OrderExtend();
			$info = $orderExtend->getOneByCondition('order_id',"order_id='{$orderId}'");
			if ($info) {
				$orderExtend->getDbConnection()->createCommand()->update($orderExtend->tableName(),$orderExtendInfo,"order_id='{$orderId}'");
			} else {
				$orderExtendInfo['create_time'] = date('Y-m-d H:i:s');
				$orderExtend->getDbConnection()->createCommand()->insert($orderExtend->tableName(),$orderExtendInfo);
			}
		}

		//保存订单sku与销售关系
        $flag = true;
        if (!empty($partDetailInfos)) {
            foreach ($partDetailInfos as $orderSkuOwnerInfo) {
                $addRes = OrderSkuOwner::model()->addRow($orderSkuOwnerInfo);
               // MHelper::writefilelog($path.'/OrderSkuOwner.log', print_r(array('data'=>$orderSkuOwnerInfo,'res'=>$addRes),true)."\r\n");// fortest
                if( $addRes['errorCode'] != '0' ){
                    $flag = false;
                }
            }
        }
        if (!$flag) {
            throw new Exception("Save OrderSkuOwnerInfo Failure");
        }
       
		return $flag;		
	}
	
	/**
	 * @desc 保存订单交易信息
	 * @param integer $orderId
	 * @param array $orderData
	 */
	public function saveTransaction($orderId, $orderData) {
		$flag = OrderTransaction::model()->saveTransactionRecord($orderData['AmazonOrderId'], $orderId, array(
    		'order_id'              => $orderId,
    		'first'                 => 1,
    		'is_first_transaction'  => 1,
    		'receive_type'          => OrderTransaction::RECEIVE_TYPE_YES,
    		'account_id'            => $this->_accountID,
    		'parent_transaction_id' => '',
    		//'order_pay_time'        => date('Y-m-d H:i:s', strtotime($orderData['PurchaseDate'])),
			'order_pay_time'        => AmazonList::transactionUTCTimeFormat($orderData['PurchaseDate']),
    		'amt'                   => $orderData['OrderTotal']['Amount'],
    		'fee_amt'               => 0,
    		'currency'              => $orderData['OrderTotal']['CurrencyCode'],
    		'payment_status'        => 'Completed',
		    'platform_code'         => Platform::CODE_AMAZON,
		));//保存交易信息
		if(!$flag){
			throw new Exception("save order transaction failure");
		}
	}
	
	/**
	 * @desc 保存付款信息
	 * @param unknown $orderID
	 * @param unknown $orderData
	 * @throws Exception
	 * @return boolean
	 */
	public function saveOrderPaypalTransactionRecord($orderID, $orderData){
		$flag = OrderPaypalTransactionRecord::model()->savePaypalRecord($orderData['AmazonOrderId'], $orderID, array(
				'order_id'              => 	$orderID,
				'receive_type'          => 	OrderPaypalTransactionRecord::RECEIVE_TYPE_YES,
				'receiver_business'		=> 	'',
				'receiver_email' 		=> 	'unknown@vakind.com',
				'receiver_id' 			=> 	'',
				'payer_id' 				=> 	'',
				'payer_name' 			=> 	isset($orderData['BuyerName']) ? $orderData['BuyerName'] : '',
				'payer_email' 			=> 	isset($orderData['BuyerEmail']) ? $orderData['BuyerEmail'] : '',
				'payer_status' 			=> 	'',
				'parent_transaction_id'	=>	'',
				'transaction_type'		=>	'',
				'payment_type'			=>	'',
				'order_time'			=>	AmazonList::transactionUTCTimeFormat($orderData['PurchaseDate']),
				'amt'					=>	isset($orderData['OrderTotal']['Amount']) ? $orderData['OrderTotal']['Amount'] : 0,
				'fee_amt'				=>	'',
				'tax_amt'				=>	'',
				'currency'				=>	isset($orderData['OrderTotal']['CurrencyCode']) ? $orderData['OrderTotal']['CurrencyCode'] : '',
				'payment_status' 		=> 	'Completed',
				'note'					=>	'',
				'modify_time'			=>	''
		));//保存交易付款信息
		if($flag){
			return true;
		}
		throw new Exception("save order trans paypal info failure");
	}
	/**
	 * @desc 保存发货日期数据
	 * @param unknown $orderId
	 * @param unknown $orderData
	 */
	public function saveShipDate($orderID, $orderData){
		OrderShipDate::model()->deleteAll("order_id=:order_id AND platform_code=:platform_code", array(
			':order_id'=>$orderID, ':platform_code'=>Platform::CODE_AMAZON
		));
		$flag = OrderShipDate::model()->addData($orderID, array(
				'platform_code'	=>	Platform::CODE_AMAZON,
				'order_id' 		=>	$orderID,
				'earliest_ship_date' =>		AmazonList::transactionUTCTimeFormat($orderData['EarliestShipDate']),
				'latest_ship_date' =>	AmazonList::transactionUTCTimeFormat($orderData['LatestShipDate'])
		));
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
}