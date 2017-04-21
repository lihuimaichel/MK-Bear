<?php
/**
 * @desc Ebay订单拉取
 * @author Gordon
 * @since 2015-06-03
 */
class EbayOrder extends EbayModel {
	const EVENT_NAME = 'getorder';
	const EVENT_NAME_UPLOAD_TN = 'uploadtracknum';
	
	/** @var object 拉单返回信息*/
	private $_Order_Response = null;
	
	/** @var int 账号ID*/
	private $_AccountID = null;
	
	/** @var string 异常信息*/
	private $_Exception = null;
	
	/** @var int 日志编号*/
	private $_LogID = 0;
	const STATUS_PAYMENT_NO = 0;
	const STATUS_PAYMENT_YES = 1;
	public static function model($className = __CLASS__) {
		return parent::model ( $className );
	}
	
	/**
	 * 切换数据库连接
	 *
	 * @see EbayModel::getDbKey()
	 */
	public function getDbKey() {
		return 'db_oms_order';
	}
	
	/**
	 * 数据库表名
	 *
	 * @see CActiveRecord::tableName()
	 */
	public function tableName() {
		return 'ueb_order';
	}

	public function tableNameDetail() {
		return 'ueb_order_detail';
	}
	
	/**
	 * 设置账号ID
	 */
	public function setAccountID($accountID) {
		$this->_AccountID = $accountID;
	}
	
	/**
	 * 设置异常信息
	 *
	 * @param string $message        	
	 */
	public function setExceptionMessage($message) {
		$this->_Exception = $message;
	}
	
	/**
	 * 设置日志编号
	 *
	 * @param int $logID        	
	 */
	public function setLogID($logID) {
		$this->_LogID = $logID;
	}
	
	/**
	 * 获取拉单时间段
	 */
	public function getTimeArr($accountID) {
		$lastLog = EbayLog::model ()->getLastLogByCondition ( array (
				'account_id' => $accountID,
				'event' => self::EVENT_NAME,
				'status' => EbayLog::STATUS_SUCCESS 
		) );
		
		$lastEventLog = array ();
		if (! empty ( $lastLog )) {
			$lastEventLog = EbayLog::model ()->getEventLogByLogID ( self::EVENT_NAME, $lastLog ['id'] );
		}
		$start_time = time () - 8 * 3600;
		$end_time = time () - 8 * 3600;
		$now_time = time () - 8 * 3600;
		if (! empty ( $lastEventLog )) {
			$start_time = strtotime ( $lastEventLog ['end_time'] );
			if ($now_time - $start_time > 5 * 86400) {
				$start_time = $now_time - 3 * 86400;
			} else {
				$start_time = $start_time - 15 * 60;
			}
			$start_time = date ( 'Y-m-d\TH:i:s\Z', $start_time );
		} else {
			$start_time = date ( 'Y-m-d\TH:i:s\Z', $now_time - 2 * 86400 );
		}
		$end_time = date ( 'Y-m-d\TH:i:s\Z', $now_time );
		return array (
				// 上次有成功,则将上次结束时间往前推15分钟，避免漏单，若不存在已成功的，则从2天前开始拉(需换算成格林威治时间)
				'start_time' => $start_time,
				'end_time' => $end_time 
		);
	}
	
	/**
	 * 获取未付款订单信息
	 *
	 * @param array $accountArr        	
	 * @param number $dayLast        	
	 */
	public function getNoPayOrders($dayLast = 0) {
		/**
		 * $platformOrderList = array (
		 * '322012090097-1585408425011'
		 * );
		 * $this->getOrdersByCondition ( array (
		 * 'OrderIDArray' => $platformOrderList
		 * ) );
		 * exit ();
		 */
		$where = '';
		if ($dayLast) {
			$where .= ' AND created_time BETWEEN SUBDATE(NOW(),' . $dayLast . ') AND NOW() ';
		}
		
		// 查出ebay未付款定订单
		$platformOrderIDs = $this->dbConnection->createCommand ()->select ( 'platform_order_id' )->from ( self::tableName () )->where ( ' platform_code = "' . Platform::CODE_EBAY . '" ' )->andWhere ( ' payment_status = ' . self::STATUS_PAYMENT_NO )->andWhere ( ' account_id = ' . $this->_AccountID . $where )->queryColumn ();
		
		if (! empty ( $platformOrderIDs )) {
			return $this->getOrdersByCondition ( array (
					'OrderIDArray' => $platformOrderIDs 
			) ,0);
		}
		
		// 补拉交易信息是空的记录
		$platformOrderIDsShip = $this->dbConnection->createCommand ()->select ( 'platform_order_id' )->from ( self::tableName () )->where ( ' platform_code = "' . Platform::CODE_EBAY . '" ' )->andWhere ( ' payment_status = ' . self::STATUS_PAYMENT_YES )->andWhere ( ' ship_country = "" or ship_country_name = ""  or ship_city_name= "" ' )->andWhere ( ' account_id = ' . $this->_AccountID . $where )->queryColumn ();
		
		if (! empty ( $platformOrderIDsShip )) {
			return $this->getOrdersByCondition ( array (
					'OrderIDArray' => $platformOrderIDsShip 
			) ,1);
		}
	}
	
	public function getSingleNoPayOrders($dayLast = 0,$poid,$type) {
		$where = '';
		if ($dayLast) {
			$where .= ' AND created_time BETWEEN SUBDATE(NOW(),' . $dayLast . ') AND NOW() ';
		}
		
		$platformOrderIDs = $this->dbConnection->createCommand ()->select ( 'platform_order_id' )->from ( self::tableName () )
		->where ( ' platform_code = "' . Platform::CODE_EBAY . '" ' )
		->andWhere('platform_order_id = "'.$poid.'"')
		->andWhere ( ' account_id = ' . $this->_AccountID  )
		->queryColumn ();
		
		if (! empty ( $platformOrderIDs )) {
			return $this->getOrdersByCondition ( array (
					'OrderIDArray' => $platformOrderIDs
			),$type);
		}
	}
	
	public function getOrdersByModifyDate($date) {
		return $this->getOrdersByCondition ( array (
				'ModTimeFrom' => $date ['start_time'],
				'ModTimeTo' => $date ['end_time'] 
		),0 );
	}
	
	/**
	 * 根据条件获取订单
	 *
	 * @param int $accountID        	
	 * @param array $params        	
	 */
	public function getOrdersByCondition($params = array(),$type) {
		$accountID = $this->_AccountID;
		$result = array ();
		$request = new GetOrdersRequest ();
		$result = true;
		// 设置需要的参数
		foreach ( $params as $col => $val ) {
			switch ($col) {
				case 'OrderIDArray' :
					$request->setOrderIDArray ( $val );
					break;
				case 'ModTimeFrom' :
					$request->setModTimeFrom ( $val );
					break;
				case 'ModTimeTo' :
					$request->setModTimeTo ( $val );
					break;
			}
		}
		
		// 抓取订单信息
		while ( $request->_PageNumber <= $request->_TotalPage ) {
			$response = $request->setAccount ( $accountID )->setRequest ()->sendRequest ()->getResponse ();
			//MHelper::writefilelog('ebay/bulkgetorder_response.txt',print_r($response,true)."\r\n");	
					
			if (!$request->getIfSuccess ()) { // 交互失败
				$this->setExceptionMessage ( $request->getErrorMsg () );
				$result = false;
				return $result;
			}

			$request->setTotalPage ( $response->PaginationResult->TotalNumberOfPages ); // 设置总页数
			$request->setPageNum ( $request->_PageNumber + 1 );
			
			foreach ( $response->OrderArray->Order as $order ) {
				// 循环订单信息
				$dbTransaction = $this->dbConnection->getCurrentTransaction ();
				if (! $dbTransaction) {
					$dbTransaction = $this->dbConnection->beginTransaction (); // 开启事务
                } else {
                     $dbTransaction->rollback ();
                }

				try {
					// $this->_Order_Response = $order;
					$ebayOrderInfo = $order;
					
					/**
					 * 1.检测是否为能处理的订单
					 */
					if ($this->checkTransactionExist ()) {
						continue;
					}

					/**
					 * 2.保存订单主数据和订单详情信息
					 */
					$orderID = '';
					$orderInfo = Order::model ()->getOrderInfoByPlatformOrderID ( $ebayOrderInfo->OrderID, Platform::CODE_EBAY );
					
					// 检查ebay平台拉下来已取消的订单
					if ( in_array ( $order->OrderStatus, array ( $request::STATUS_CANCELLED, $request::STATUS_CANCELPENDING ) )) 
					{ // 过滤已取消的订单
						if (! empty ( $orderInfo ) && $orderInfo ['ship_status'] == Order::SHIP_STATUS_NOT) { // 未出货的订单可以取消
							Order::model ()->cancelOrders ( $orderInfo ['order_id'] );
						}
                        $dbTransaction->commit ();
						continue; // 已退款订单跳过
					}

					//补拉订单
					if($type==1)
					{							
						$orderID = $this->saveOrderInfo ( $orderInfo, $ebayOrderInfo );							
						if ($orderID) {
							$this->saveTransaction ( $orderID, $ebayOrderInfo );
						} else {
                            $dbTransaction->rollback ();
							continue;
							//throw new Exception ( 'Save Order Failed' );
						}
					} else {
						if (! empty ( $orderInfo ) && $orderInfo ['payment_status'] == self::STATUS_PAYMENT_NO) {
							// 检查oms是否取消订单，已完成和未发货
							if ($orderInfo ['complete_status'] == Order::COMPLETE_STATUS_END && $orderInfo ['ship_status'] == Order::SHIP_STATUS_NOT) {
                                $dbTransaction->rollback ();
                                continue;
							} else {
								$orderID = $this->saveOrderInfo ( $orderInfo, $ebayOrderInfo );
								if ($orderID) {
									/**
									 * 3.保存交易信息
									 */
									$this->saveTransaction ( $orderID, $ebayOrderInfo );
								} else {
                                    $dbTransaction->rollback ();
									continue;
									//throw new Exception ( 'Save Order Failed' );
								}
							}
						} elseif (empty ( $orderInfo )) {
							$orderID = $this->saveOrderInfo ( $orderInfo, $ebayOrderInfo );
							if ($orderID) {
								$this->saveTransaction ( $orderID, $ebayOrderInfo );
							} else {
                                $dbTransaction->rollback ();
								continue;
								//throw new Exception ( 'Save Order Failed' );
							}
						} else {
                            $dbTransaction->rollback ();
							continue;
						}
					}
					$dbTransaction->commit ();
					//echo "========commit========";
				} catch ( Exception $e ) {
					$dbTransaction->rollback ();
					//echo "=========rollback =======";
					$this->setExceptionMessage ( $e->getMessage () );
					$result = false;
					continue;
					// return false;
				}
			}
		}//end while
		return $result;
	}
	
	/**
	 * 检测交易是否已存在
	 */
	public function checkTransactionExist() {
		return false;
		// $order = $this->_Order_Response;
		// if( isset($order->ExternalTransaction) && !empty($order->ExternalTransaction) ){
		// foreach($order->ExternalTransaction as $transaction){//如果是已抓取交易信息的订单则跳过(默认已知付款的订单不做修改)
		// if( OrderTransaction::model()->checkTransactionExist($transaction->ExternalTransactionID) ){
		// return true;
		// }
		// }
		// }
		// return false;
	}

    /**
     * [getOneByCondition description]
     * @param  string $fields [description]
     * @param  string $where  [description]
     * @param  mixed $order  [description]
     * @return array
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

    /**
     * [getListByCondition description]
     * @param  string $fields [description]
     * @param  string $where  [description]
     * @param  mixed $order  [description]
     * @return array
     * @author yangsh
     */
    public function getListByCondition($fields='*', $where='1',$order='') {
        $cmd = $this->dbConnection->createCommand();
        $cmd->select($fields)
            ->from(self::tableName())
            ->where($where);
        $order != '' && $cmd->order($order);
        return $cmd->queryAll();
    } 	
	
	/**
	 * 保存订单主信息
	 *
	 * @param object $info        	
	 */
	public function saveOrderInfo($orderInfo, $ebayOrderInfo) {
		// $orderInfo = Order::model ()->getOrderInfoByPlatformOrderID ( $this->_Order_Response->OrderID, Platform::CODE_EBAY );
		
		$flagExist = ! empty ( $orderInfo ) ? true : false;
		$order = $ebayOrderInfo;
		$autoCode = AutoCode::getInstance ();
		$orderID = $flagExist ? $orderInfo ['order_id'] : $autoCode::getCode ( 'order' ) . "EB"; // 获取订单号
		$flag = false;
		// 1.订单主表数据
		$email = '';
		if ($orderInfo ['email']) {
			$email = $orderInfo ['email'];
		} elseif ($order->TransactionArray->Transaction->Buyer->Email != 'Invalid Request') {
			$email = trim ( $order->TransactionArray->Transaction->Buyer->Email );
		}
		$phone = '';
		if ($orderInfo ['ship_phone']) {
			$phone = $orderInfo ['ship_phone'];
		} elseif ($order->ShippingAddress->Phone != 'Invalid Request') {
			$phone = trim ( $order->ShippingAddress->Phone );
		}
		
		$ship_country = trim ( $order->ShippingAddress->Country );
		$ship_country_name = trim ( $order->ShippingAddress->CountryName );
		$ship_stateorprovince = trim ( $order->ShippingAddress->StateOrProvince );
		 //echo ("ebay---orderID:" . $orderID . "---ship_country:" . $ship_country . "---ship_country_name:" . $ship_country_name . "---stateorprovince:" . $ship_stateorprovince . "---<br/>");
		// 检查国家表是否存在这个名称
		$shipCountryName = Country::model ()->getCountryInfoByshipCountryName ( trim ( $ship_country ), trim ( $ship_country_name ) );
		if ($shipCountryName != "" && $ship_country_name != $shipCountryName) {
			$ship_country_name = $shipCountryName;
		}
		
		if ($ship_country == "US" && $ship_country_name == "United States" && $ship_stateorprovince == "PR") {
			$ship_country = "PR";
			$ship_country_name = "Puerto Rico";
		}

		//关岛现为美国海外属地，是美国的非宪辖管制领土。但是由于运费的区别，发特殊属性与美国本土很大区别,请GU关岛订单导入直接为关岛
		if ($ship_country == "US" && $ship_country_name == "United States" && $ship_stateorprovince == "GU") {
			$ship_country = "GU";
			$ship_country_name = "Guam";
		}

		$flag = Order::model ()->saveOrderRecord ( array (
				'order_id' => $orderID,
				'platform_code' => Platform::CODE_EBAY,
				'platform_order_id' => trim ( $order->OrderID ),
				'account_id' => $this->_AccountID,
				'log_id' => $this->_LogID,
				'order_status' => trim ( $order->OrderStatus ),
				'buyer_id' => trim ( $order->BuyerUserID ),
				'email' => $email,
				'timestamp' => date ( 'Y-m-d H:i:s' ),
				'created_time' => $order->CreatedTime,
				'last_update_time' => $order->CheckoutStatus->LastModifiedTime,
				'ship_cost' => floatval ( $order->ShippingServiceSelected->ShippingServiceCost ),
				'subtotal_price' => floatval ( $order->Subtotal ),
				'total_price' => floatval ( $order->Total ),
				'currency' => trim ( $order->Total ['currencyID'] ),
				'ship_name' => trim ( $order->ShippingAddress->Name ),
				'ship_country' => $ship_country,
				'ship_country_name' => $ship_country_name,
				'ship_zip' => trim ( $order->ShippingAddress->PostalCode ),
				'ship_city_name' => trim ( $order->ShippingAddress->CityName ),
				'ship_stateorprovince' => trim ( $order->ShippingAddress->StateOrProvince ),
				'ship_street1' => trim ( $order->ShippingAddress->Street1 ),
				'ship_street2' => trim ( $order->ShippingAddress->Street2 ),
				'paytime' => $order->PaidTime,
				'ori_create_time' => date ( 'Y-m-d H:i:s', strtotime ( $order->CreatedTime ) ),
				'ori_update_time' => date ( 'Y-m-d H:i:s', strtotime ( $order->CheckoutStatus->LastModifiedTime ) ),
				'ori_pay_time' => date ( 'Y-m-d H:i:s', strtotime ( $order->PaidTime ) ),
				'payment_status' => Order::PAYMENT_STATUS_NOT,
				'ship_phone' => $phone 
		) );
		//echo "===check add result===<br/>";
		//var_dump($flag);
		if (! $flag)
			throw new Exception ( 'Save Order Info Failure!!!' );
			
			// 判断是否有收取运费
		$flagShipPrice = floatval ( $order->ShippingServiceSelected->ShippingServiceCost ) > 0 ? true : false;
		$weightArr = array (); // 记录订单中的产品重量比重
		$totalWeight = 0;
		// 2.订单详情数据
		// 删除详情
		OrderDetail::model ()->deleteOrderDetailByOrderID ( $orderID );
		$finalValueFee = 0;
		$orderExceptionMsg = "";
		foreach ( $order->TransactionArray->Transaction as $orderDetail ) {
			if (isset ( $orderDetail->Variation ) ) { // 多属性产品
				if (isset($orderDetail->Variation->SKU) && trim($orderDetail->Variation->SKU) != '' ) {
					$skuOnline = $orderDetail->Variation->SKU;
				} else {
					$skuOnline = 'unknow';
				}
				$title = trim ( $orderDetail->Variation->VariationTitle );
			} else {
				$skuOnline = trim($orderDetail->Item->SKU);
				$title = trim ( $orderDetail->Item->Title );
			}

			if ($skuOnline != 'unknow' ) {
				$sku = encryptSku::getRealSku ( $skuOnline );
				$skuInfo = Product::model ()->getProductInfoBySku ( $sku );
			} else {
				$sku = 'unknow';
				$skuInfo = array();
			}

			$pending_status = 0;
			if (! empty ( $skuInfo )) { // 可以查到对应产品
				$realProduct = Product::model ()->getRealSkuList ( $sku, $orderDetail->QuantityPurchased );
			} else {
				$realProduct = array (
						'sku' => 'unknow',
						'quantity' => $orderDetail->QuantityPurchased 
				);
				Order::model ()->setOrderCompleteStatus ( Order::COMPLETE_STATUS_PENGDING, $orderID );
				$pending_status = 2;
			}

			if($skuInfo && $skuInfo['product_is_multi'] == Product::PRODUCT_MULTIPLE_MAIN){
				$childSku = ProductSelectAttribute::model()->getChildSKUListByProductID($skuInfo['id']);
	    		!empty($childSku) && $orderExceptionMsg .= "sku:{$sku} 为主sku <br/>";
			}

			//add by yangsh in 2016-06-25
			$site = trim($orderDetail->Item->Site);//刊登站点
			$currency = (string)$orderDetail->TransactionPrice->attributes()->currencyID;//交易币种
			if ('eBayMotors' == $site && 'USD' == $currency ) {
				$site = 'US';
			}

			$orderdetailattr=array (
					'transaction_id' => $orderDetail->TransactionID,
					'order_id' => $orderID,
					'platform_code' => Platform::CODE_EBAY,
					'item_id' => $orderDetail->Item->ItemID,
					'title' => $title,
					'sku_old' => $sku,//系统sku
					'sku' => $realProduct ['sku'],//真实sku
					'site' => $site,//modify by yangsh in 2016-06-25
					'quantity_old' => $orderDetail->QuantityPurchased,
					'quantity' => $realProduct ['quantity'],
					'sale_price' => $orderDetail->TransactionPrice,
					'total_price' => floatval ( $orderDetail->TransactionPrice ) * intval ( $orderDetail->QuantityPurchased ),
					'currency' => $currency,//modify by yangsh in 2016-06-25
					'final_value_fee' => $orderDetail->FinalValueFee
			);
			
			if($pending_status>0){
				$orderdetailattr["pending_status"]=$pending_status;
			}
			// 保存
			$flag2 = OrderDetail::model ()->addOrderDetail ( $orderdetailattr );
			if (! $flag2)
				throw new Exception ( 'Save Order Details Failure!!!' );
			
			// ===== 增加转接头 add by lihy 2016-05-13 ====== 
			$flag3 = OrderDetail::model()->addOrderAdapter(array(
																'order_id'			=>	$orderID,
																'ship_country_name'	=>	$ship_country_name,
																'platform_code'		=>	Platform::CODE_EBAY,
																'currency'			=>	$currency //modify by yangsh in 2016-06-25
															), $realProduct);
			if(! $flag3){
				throw new Exception ( 'Save SKU ' . $sku . ' Adapter Failure!!!' );
			}
			if ($flag) { //
				$finalValueFee += floatval ( $orderDetail->FinalValueFee );
				if ($flagShipPrice) {
					$orderDetailID = OrderDetail::model ()->dbConnection->getLastInsertID ();
					$realSkuInfo = Product::model ()->getProductInfoBySku ( $realProduct ['sku'] ); // 获取真实发货产品的信息
					if (isset ( $realSkuInfo ['product_weight'] )) {
						$weightArr [$orderDetailID] = floatval ( $realSkuInfo ['product_weight'] ) * intval ( $realProduct ['quantity'] );
						$totalWeight += $weightArr [$orderDetailID];
					}
				}
			}
		}
		//判断是否有异常存在
		if($orderExceptionMsg){
			$res = Order::model()->setExceptionOrder($orderID, OrderExceptionCheck::EXCEPTION_SKU_UNKNOWN, $orderExceptionMsg);
			if(! $res){
				throw new Exception ( 'Set order Exception Failure: '.$orderID);
			}
		}
		// 更新成交费
		$res = Order::model ()->updateByPk ( $orderID, array (
				'final_value_fee' => $finalValueFee 
		) );
		if (! $res)
			throw new Exception ( 'Update order By Pk failure!!!' );
		if ($flagShipPrice) {
			$shipPrice = floatval ( $order->ShippingServiceSelected->ShippingServiceCost );
			if (count ( $weightArr ) == 1) {
				$res = OrderDetail::model ()->updateByPk ( $orderDetailID, array (
						'ship_price' => $shipPrice 
				) );
				if (! $res)
					throw new Exception ( 'Update order1 Detail By Pk failure!!!' );
			} else {
				foreach ( $weightArr as $detailID => $weight ) {
					$res = OrderDetail::model ()->updateByPk ( $detailID, array (
							'ship_price' => round ( $weight / $totalWeight * $shipPrice, 2 ) 
					) );
					if (! $res)
						throw new Exception ( 'Update order2 Detail By Pk failure!!!' );
				}
			}
		}
		return $flag ? $orderID : $flag;
	}
	
	/**
	 * 保存订单交易信息
	 *
	 * @param string $orderID        	
	 */
	public function saveTransaction($orderID, $ebayOrderInfo) {
		try {
			$sellerEmail 			= trim($ebayOrderInfo->SellerEmail);
			$externalTransactions 	= $ebayOrderInfo->ExternalTransaction;
			unset($ebayOrderInfo);
			$where 					= "status=1 and platform_code = 'EB' and email='{$sellerEmail}'";
			$paypalInfo 			= PaypalAccount::model()->getOneByCondition('id',$where);
			$paypalAccountID 		= empty($paypalInfo) ? 0 : $paypalInfo['id'];
			$orderTransaction 		= array ();
			foreach ( $externalTransactions as $externalTransaction ) {
				$orderTransaction [strtotime ( $externalTransaction->ExternalTransactionTime )] = $externalTransaction;
			}
			ksort ( $orderTransaction ); // 按交易时间先后排序
			$orderTransactionModel	= OrderTransaction::model ();
			foreach ( $orderTransaction as $externalTransaction ) {
				$externalTransactionStatus 	= trim( $externalTransaction->ExternalTransactionStatus );
				$amt 						= floatval ( $externalTransaction->PaymentOrRefundAmount );
				if ( 'Succeeded' != $externalTransactionStatus || $amt == 0 ) {
					continue;
				}
				// 判断交易记录是否抓取,没有先保存成交信息
				$transactionID 		= trim ( $externalTransaction->ExternalTransactionID );
				$receiveType  		= $amt > 0 ? 1 : 2; // 默认将收款作为首次交易
				// 不存在交易信息且ebay反映交易已完成，先保存交易信息，随后在paypal抓取
				$checkTransaction 	= $orderTransactionModel->getOrderTransactionInfoByTransactionID ( $transactionID );
				if ( empty($checkTransaction) ) {
					$isFirst 		=  $amt > 0 ? 1 : 0;
				} else {
					$isFirst 		=  $checkTransaction['first'];
				}
				OrderTransaction::model ()->saveTransactionRecord ( $transactionID, $orderID, array (
					'transaction_id' 		=> $transactionID,
					'order_id' 				=> $orderID,
					'account_id' 			=> $paypalAccountID,
					'platform_code' 		=> Platform::CODE_EBAY,
					'receive_type' 			=> $receiveType,
					'first' 				=> $isFirst,
					'is_first_transaction' 	=> $isFirst,
				) ); 
			}
			return true;
		} catch (Exception $e) {
			throw new Exception ( "save order transaction failure!!!".$e->getMessage() );
			return false;
		}
	}
	
	/**
	 * 抓取异常订单paypal信息防止个别订单送货地址出错
	 *
	 * @param int $accountID        	
	 */
	public function getExcePaypalTransaction($accountID) {
		$orderTransactions = OrderTransaction::model ()->getEbayExcepTransactionDataOrder ( $accountID );
		if (! empty ( $orderTransactions )) {
			$this->getPaypalTransactionRecord ( $accountID, $orderTransactions );
		}
	}
	
	public function getOrderTrans($accountID,$poid){
		$request = new GetOrdersRequest ();
		$result = true;
		$platformOrderIDs = $this->dbConnection->createCommand ()->select ( 'platform_order_id' )->from ( self::tableName () )
		->where ( ' platform_code = "' . Platform::CODE_EBAY . '" ' )
		->andWhere('platform_order_id = "'.$poid.'"')
		->andWhere ( ' account_id = ' . $accountID  )
		->queryColumn ();
		$params=array (
					'OrderIDArray' => $platformOrderIDs
			);
		// 设置需要的参数
		foreach ( $params as $col => $val ) {
			switch ($col) {
				case 'OrderIDArray' :
					$request->setOrderIDArray ( $val );
					break;
				case 'ModTimeFrom' :
					$request->setModTimeFrom ( $val );
					break;
				case 'ModTimeTo' :
					$request->setModTimeTo ( $val );
					break;
			}
		}
		//var_dump($params);
		// 抓取订单信息
		while ( $request->_PageNumber <= $request->_TotalPage ) {
			$response = $request->setAccount ( $accountID )->setRequest ()->sendRequest ()->getResponse ();
			var_dump($response);
			if ($request->getIfSuccess ()) { // 交互成功
				$request->setTotalPage ( $response->PaginationResult->TotalNumberOfPages ); // 设置总页数
				$request->setPageNum ( $request->_PageNumber + 1 );
				foreach ( $response->OrderArray->Order as $order ) {
					// 循环订单信息
					$dbTransaction = $this->dbConnection->getCurrentTransaction ();
					if (! $dbTransaction) {
						$dbTransaction = $this->dbConnection->beginTransaction (); // 开启事务
					} else {
						$dbTransaction->rollback ();
					}
					try {
						$ebayOrderInfo = $order;
						
						$orderID = '';
						$orderInfo = Order::model ()->getOrderInfoByPlatformOrderID ( $ebayOrderInfo->OrderID, Platform::CODE_EBAY );
						if(! empty ( $orderInfo )){
							$orderID = $orderInfo ['order_id'];
							$this->saveTransaction ( $orderID, $ebayOrderInfo );
						}
						/**
						 * 4.抓取交易信息 (单独拉取)
						 */
						$dbTransaction->commit ();
					} catch ( Exception $e ) {
						$dbTransaction->rollback ();
						$this->setExceptionMessage ( $e->getMessage () );
						$result = false;
						continue;
					}
				}
			} else { // 交互失败
				$this->setExceptionMessage ( $request->getErrorMsg () );
				$result = false;
				return $result;
			}
		}
		return $result;
	}
	
	/**
	 * 检查收件人
	 *
	 * @param string $ename        	
	 */
	private function checkReceiveEmail($remail) {
		$ebayorder = PaypalAccount::model ()->dbConnection->createCommand ()->select ( '*' )->from ( PaypalAccount::model ()->tableName () )->where ( 'email = "' . $remail . '"' )->queryRow ();
		
		if (isset ( $ebayorder )) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * 抓取paypal信息
	 *
	 * @param int $accountID
	 */
	
	public function getPaypalTransactionRecordAll($accountID) {
		// 拉取交易信息
		$orderTransactions = OrderTransaction::model ()->getEbayNoTransactionDataOrder ( $accountID );
		if (! empty ( $orderTransactions )) {
			$this->getPaypalTransactionRecord ( $accountID, $orderTransactions );
		}		
	}
	
	public function getSinglePaypalTransactionRecord($accountID,$poid) {
		// 拉取交易信息
		$orderTransactions = OrderTransaction::model ()->getSingleTransactionData ( $accountID,$poid);
		if (! empty ( $orderTransactions )) {
			$this->getPaypalTransactionRecord ( $accountID, $orderTransactions );
		}
	}
	
	public function getNomailPaypalTransactionRecord($accountID) {
		// 补拉异常信息
		$orderTransactions = $this->dbConnection->createCommand ()->select ( ' a.*,b.transaction_id ' )->from ( self::tableName () . ' AS a' )->leftJoin ( OrderPaypalTransactionRecord::model ()->tableName () . ' AS b', 'a.order_id = b.order_id' )
		->where ( ' a.platform_code = "' . Platform::CODE_EBAY . '" ' )
		->andWhere ( ' a.account_id = "' . $accountID . '" and a.payment_status=1 ' )
		->andWhere ( ' b.receiver_email="" or b.payer_email="" ' )
		->andWhere ( ' a.created_time BETWEEN SUBDATE(NOW(),' . OrderTransaction::CHECK_NOPAY_DAYS . ') AND NOW() ' )
		->order ( ' a.order_id desc  ' )->queryAll ();
		
		if (! empty ( $orderTransactions )) {
			$this->getPaypalTransactionRecord ( $accountID, $orderTransactions );
		}
	}
	
	/**
	 * 抓取paypal信息
	 *
	 * @param int $accountID        	
	 */
	public function getPaypalTransactionRecord($accountID,$orderTransactions) {
		foreach ( $orderTransactions as $info ) {
			// 查找paypal交易信息
			$order_info = Order::model ()->getInfoByOrderId ( $info ['order_id'] );
			$paypalAccountID = empty($info['paypalAccountID']) ? null : $info['paypalAccountID'];
			$paypalTransaction = PaypalAccount::model ()->getPaypalTransactionByTransactionID ( $info ['transaction_id'], Platform::CODE_EBAY, $paypalAccountID );			
			if (empty($paypalTransaction) || floatval($paypalTransaction ['AMT']) == 0) {
				continue;
			}
			$dbTransaction = $this->dbConnection->getCurrentTransaction ();
			if (! $dbTransaction) {
				$dbTransaction = $this->dbConnection->beginTransaction (); // 开启事务
			}
			try {
				$amt 			= floatval($paypalTransaction ['AMT']);
				$isfirst 		= $info ['first'];
				$receiveType 	= $paypalTransaction ['AMT'] > 0 ? 1 : 2;
				//不是已付款状态物流启动渠道：发往美国的才判断是否走eub 
				//物流启动渠道：判断是否走挂号 echo("Order"); 获取付款时间
				if ($info ['payment_status'] != Order::PAYMENT_STATUS_END) {
					$payTime = strtotime ( $info ['paytime'] ) > 0 ? $info ['paytime'] : (isset ( $paypalTransaction ['ORDERTIME'] ) ? $paypalTransaction ['ORDERTIME'] : '');
					$ori_pay_time = strtotime ( $info ['paytime'] ) > 0 ? date ( 'Y-m-d H:i:s', strtotime ( $info ['paytime'] ) + 8 * 3600 ) : (isset ( $paypalTransaction ['ORDERTIME'] ) ? date ( 'Y-m-d H:i:s', strtotime ( $paypalTransaction ['ORDERTIME'] ) ) : '');
					
					if (isset ( $paypalTransaction ['PAYMENTTYPE'] ) && $paypalTransaction ['PAYMENTTYPE'] == 'echeck') {
						$payTime = $paypalTransaction ['ORDERTIME'] = date ( "Y-m-d H:i:s", time () - 3600 * 8 );
						$ori_pay_time = date ( "Y-m-d H:i:s", time () );
					}
					
					$ship_street1 			=$info['ship_street1'];
					$ship_street2 			=$info['ship_street2'];
					$ship_zip 				=$info['ship_zip'];
					$ship_city_name 		=$info['ship_city_name'];
					$ship_country 			=$info ['ship_country'];
					$ship_country_name		=$info ['ship_country_name'];
					$ship_stateorprovince	=$info ['ship_stateorprovince'];
					
					if(isset ( $paypalTransaction ['SHIPTOSTREET'] ) && $paypalTransaction ['SHIPTOSTREET']!="")
					{
						$ship_street1 			=$paypalTransaction ['SHIPTOSTREET'] ;
					}
					if(isset ( $paypalTransaction ['SHIPTOSTREET2'] ) && $paypalTransaction ['SHIPTOSTREET2']!="")
					{
						$ship_street2 			=$paypalTransaction ['SHIPTOSTREET2'] ;
					}
					if(isset ( $paypalTransaction ['SHIPTOZIP'] ) && $paypalTransaction ['SHIPTOZIP']!="")
					{
						$ship_zip 				=$paypalTransaction ['SHIPTOZIP'] ;
					}
					if(isset ( $paypalTransaction ['SHIPTOCITY'] ) && $paypalTransaction ['SHIPTOCITY']!="")
					{
						$ship_city_name 		=$paypalTransaction ['SHIPTOCITY'] ;
					}				
					
					if(isset ( $paypalTransaction ['SHIPTOCOUNTRYCODE'] ) && $paypalTransaction ['SHIPTOCOUNTRYCODE']!="")
					{
						$ship_country 			=$paypalTransaction ['SHIPTOCOUNTRYCODE'] ;
					}
					if(isset ( $paypalTransaction ['SHIPTOCOUNTRYNAME'] ) && $paypalTransaction ['SHIPTOCOUNTRYNAME']!="")
					{
						$ship_country_name 		=$paypalTransaction ['SHIPTOCOUNTRYNAME'];
					}
					if(isset ( $paypalTransaction ['SHIPTOSTATE'] ) && $paypalTransaction ['SHIPTOSTATE']!="")
					{
						$ship_stateorprovince 	=$paypalTransaction ['SHIPTOSTATE'];
					}
					
					
					$shipCountryName = Country::model ()->getCountryInfoByshipCountryName ( trim ( $ship_country ), trim ( $ship_country_name ) );
					if ($shipCountryName != "" && $ship_country_name != $shipCountryName) {
						$ship_country_name = $shipCountryName;
					}
					
					if ($ship_country == "US" && $ship_country_name == "United States" && $ship_stateorprovince == "PR") {
						$ship_country = "PR";
						$ship_country_name = "Puerto Rico";
					}

					if ($ship_country == "US" && $ship_country_name == "United States" && $ship_stateorprovince == "GU") {
						$ship_country = "GU";
						$ship_country_name = "Guam";
					}						

					// 更新订单数据
					$orderColumn = array (
						'order_id' 				=> $info ['order_id'],
						'ship_street1' 			=> $ship_street1,
						'ship_street2' 			=> $ship_street2,
						'ship_zip' 				=> $ship_zip,
						'ship_city_name' 		=> $ship_city_name,
						'ship_stateorprovince' 	=> $ship_stateorprovince,
						'ship_country' 			=> $ship_country,
						'ship_country_name' 	=> $ship_country_name,
						'paytime' 				=> $payTime,
						'ori_pay_time' 			=> $ori_pay_time,
					);

					if ($isfirst > 0 && 'Completed' == $paypalTransaction ['PAYMENTSTATUS'] ) {
						$orderColumn['payment_status'] 	= Order::PAYMENT_STATUS_END;
					}

					// 保存NOTE信息
					if (isset ( $paypalTransaction ['NOTE'] ) && trim ( $paypalTransaction ['NOTE'] ) != '') {
						OrderNote::model ()->dbConnection->createCommand ()->delete ( OrderNote::model ()->tableName (), 'order_id = "' . $info ['order_id'] . '"' );
						OrderNote::model ()->addNoteRecord ( array (
								'order_id' => $info ['order_id'],
								'note' => isset ( $paypalTransaction ['NOTE'] ) ? trim ( $paypalTransaction ['NOTE'] ) : '',
								'create_time' => date ( 'Y-m-d H:i:s' ) 
						) );
						$orderColumn ['complete_status'] = Order::COMPLETE_STATUS_PENGDING;
						// 更新订单详情的pending_status状态为客服待处理
						OrderDetail::model ()->setOrderDetailPendingStatus ( Order::PENDING_STATUS_CUSTOMER, $info ['order_id'] );
					}
					Order::model ()->updateColumnByOrderID ( $info ['order_id'], $orderColumn );
				}

				// 保存交易信息				
				$transData = array (
						'order_id' 		=> $info ['order_id'],
						'receive_type' 	=> $receiveType,
						'account_id' 	=> isset ( $paypalTransaction ['paypal_account_id'] ) ? $paypalTransaction ['paypal_account_id'] : 0,
						'parent_transaction_id' => isset ( $paypalTransaction ['PARENTTRANSACTIONID'] ) ? $paypalTransaction ['PARENTTRANSACTIONID'] : '',
						'order_pay_time' => isset ( $paypalTransaction ['ORDERTIME'] ) ? $paypalTransaction ['ORDERTIME'] : '',
						'amt' 			=> isset ( $paypalTransaction ['AMT'] ) ? $paypalTransaction ['AMT'] : 0,
						'fee_amt' 		=> isset ( $paypalTransaction ['FEEAMT'] ) ? $paypalTransaction ['FEEAMT'] : 0,
						'currency' 		=> isset ( $paypalTransaction ['CURRENCYCODE'] ) ? $paypalTransaction ['CURRENCYCODE'] : '',
						'payment_status' => isset ( $paypalTransaction ['PAYMENTSTATUS'] ) ? $paypalTransaction ['PAYMENTSTATUS'] : '',
						'first' 		=> $isfirst,
						'is_first_transaction' => $isfirst,
						'platform_code' => Platform::CODE_EBAY 
				);

				// OrderTransaction::model ()->saveTransactionRecord ( $paypalTransaction ['TRANSACTIONID'], $info ['order_id'],$transData );
				OrderTransaction::model ()->saveTransactionRecord ( $info ['transaction_id'], $info ['order_id'], $transData );
				$payerName = isset ( $paypalTransaction ['FIRSTNAME'] ) ? $paypalTransaction ['FIRSTNAME'] : '';
				isset ( $paypalTransaction ['MIDDLENAME'] ) && $payerName .= ' ' . $paypalTransaction ['MIDDLENAME'];
				isset ( $paypalTransaction ['LASTNAME'] ) && $payerName .= ' ' . $paypalTransaction ['LASTNAME'];
				// 保存paypal交易记录
				OrderPaypalTransactionRecord::model ()->savePaypalRecord ( $info ['transaction_id'], $info ['order_id'], array (
						'transaction_id' => $info ['transaction_id'],
						'receive_type' => $receiveType,
						'receiver_business' => isset ( $paypalTransaction ['RECEIVERBUSINESS'] ) ? $paypalTransaction ['RECEIVERBUSINESS'] : '',
						'receiver_email' => isset ( $paypalTransaction ['RECEIVEREMAIL'] ) ? $paypalTransaction ['RECEIVEREMAIL'] : '',
						'receiver_id' => isset ( $paypalTransaction ['RECEIVERID'] ) ? $paypalTransaction ['RECEIVERID'] : '',
						'payer_id' => isset ( $paypalTransaction ['PAYERID'] ) ? $paypalTransaction ['PAYERID'] : '',
						'payer_name' => $payerName,
						'payer_email' => isset ( $paypalTransaction ['EMAIL'] ) ? $paypalTransaction ['EMAIL'] : '',
						'payer_status' => isset ( $paypalTransaction ['PAYERSTATUS'] ) ? $paypalTransaction ['PAYERSTATUS'] : '',
						'parent_transaction_id' => isset ( $paypalTransaction ['PARENTTRANSACTIONID'] ) ? $paypalTransaction ['PARENTTRANSACTIONID'] : '',
						'transaction_type' => isset ( $paypalTransaction ['TRANSACTIONTYPE'] ) ? $paypalTransaction ['TRANSACTIONTYPE'] : '',
						'payment_type' => isset ( $paypalTransaction ['PAYMENTTYPE'] ) ? $paypalTransaction ['PAYMENTTYPE'] : '',
						'order_time' => isset ( $paypalTransaction ['ORDERTIME'] ) ? $paypalTransaction ['ORDERTIME'] : '',
						'amt' => isset ( $paypalTransaction ['AMT'] ) ? $paypalTransaction ['AMT'] : 0,
						'fee_amt' => isset ( $paypalTransaction ['FEEAMT'] ) ? $paypalTransaction ['FEEAMT'] : 0,
						'tax_amt' => isset ( $paypalTransaction ['TAXAMT'] ) ? $paypalTransaction ['TAXAMT'] : 0,
						'currency' => isset ( $paypalTransaction ['CURRENCYCODE'] ) ? $paypalTransaction ['CURRENCYCODE'] : '',
						'payment_status' => isset ( $paypalTransaction ['PAYMENTSTATUS'] ) ? $paypalTransaction ['PAYMENTSTATUS'] : '',
						'note' => isset ( $paypalTransaction ['NOTE'] ) ? $paypalTransaction ['NOTE'] : '',
						'modify_time' => date ( 'Y-m-d H:i:s' ) 
				) );
				$dbTransaction->commit ();
			} catch ( Exception $e ) {
				$dbTransaction->rollback ();
				$this->setExceptionMessage ( $e->getMessage () );
				continue;
			}
		}
		
		return true;
	}
	
	/**
	 * 获取异常信息
	 *
	 * @return string
	 */
	public function getExceptionMessage() {
		return $this->_Exception;
	}
	
	/**
	 * ebay订单确认发货
	 *
	 * @param array $shippedData        	
	 * @return boolean
	 */
	public function setOrderShipped($shippedData = array()) {
		$itemID = $shippedData ['item_id'];
		$transactionID = $shippedData ['transaction_id'];
		$orderID = $shippedData ['platform_order_id'];
		
		$shipment = array ();
		// $shipment['ShippedTime'] = $shippedData['shipped_time'];
		
		$LineItem = array (
				'ItemID' => $shippedData ['item_id'],
				'TransactionID' => $shippedData ['transaction_id'] 
		);
		
		// 没有追踪号只标记发货
		if (! isset ( $shippedData ['track_number'] ) || empty ( $shippedData ['track_number'] )) {
			$ShipmentLineItem ['LineItem'] = $LineItem;
			$shipment ['ShipmentLineItem'] = $ShipmentLineItem;
		} else {
			// 有追踪号就上传追踪号
			$ShipmentLineItem ['LineItem'] = $LineItem;
			$ShipmentTrackingDetails ['ShipmentLineItem'] = $ShipmentLineItem;
			$ShipmentTrackingDetails ['ShipmentTrackingNumber'] = $shippedData ['track_number'];
			$ShipmentTrackingDetails ['ShippingCarrierUsed'] = $shippedData ['shipped_carrier'];
			$shipment ['ShipmentTrackingDetails'] = $ShipmentTrackingDetails;
		}
		
		try {
			$request = new CompleteSaleRequest ();
			$request->setShipment ( $shipment );
			$request->setOrderID ( $orderID );
			$request->setItemID ( $itemID );
			$request->setTransactionID ( $shippedData ['transaction_id'] );
			if (! isset ( $shippedData ['track_number'] ) || empty ( $shippedData ['track_number'] ))
				$request->setShipped ( true );
			
			$response = $request->setAccount ( $this->_AccountID )->setRequest ()->sendRequest ()->getResponse ();
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
	 * @desc 更新额度
	 * @param unknown $accountID
	 * @param unknown $orderLogID
	 * @return boolean
	 */
	public function updateEbayQuotaLimit($accountID, $orderLogID){
		
		//删除过去7天前的数据
		$ebayUpdateQuotaLogDetailModel = new EbayUpdateQuotaLogDetail();
		$ebayUpdateQuotaLogDetailModel->deleteLogInfoBeforeTime($accountID, date("Y-m-d H:i:s", strtotime("-7 days")));
		
		//首先判断该账号是否自动更新额度的设置
		$ebayAccountInfoModel = new EbayAccountInfo();
		$ebayAccountInfo = $ebayAccountInfoModel->getAccountInfoByAccountID($accountID);
		if(empty($ebayAccountInfo) || $ebayAccountInfo['auto_qty'] != 1){
			$this->setExceptionMessage("不存在该账号或该账号不需要自动更新额度");
			return false;
		}
		//重新拉取listing-- 这里这个好耗时间，主要看额度更新多久运行一次
		$ebayProductModel = new EbayProduct();
		/* $res = $ebayProductModel->getListingByAccountID($accountID);
		if(!$res){
			$this->setExceptionMessage("拉取listing发生错误！");
			return false;
		} */
		
		//判断最近是否已经运行过 EbayUpdateQuotaLog
		$ebayUpdateQuotaLogModel = new EbayUpdateQuotaLog();
		/* if(!$ebayUpdateQuotaLogModel->checkAbleRunAtHour($accountID, 3)){
			$this->setExceptionMessage("近期运行过额度调整，暂不允许运行！");
			return false;
		} */
		//检验是否可以运行
		if(!$ebayUpdateQuotaLogModel->checkRunning($accountID)){
			$this->setExceptionMessage("已经有一个运行");
			return false;
		}
		$logID = $ebayUpdateQuotaLogModel->addDefaultLogData($accountID, $orderLogID);
		if(!$logID){
			$this->setExceptionMessage("创建日志失败");
			return false;
		}
		//获取最近两次日志ID
		$latestLogList = $ebayUpdateQuotaLogModel->getLatestLogListByLimit(2,  "id <=".$logID.' AND account_id = "'.$accountID.'" AND status != '.$ebayUpdateQuotaLogModel::LOG_STATUS_SUCCESS);
		$orderLogIDs = array();
		if($latestLogList){
			foreach ($latestLogList as $log){
				$orderLogIDs[] = $log['order_logid'];
			}
		}
		//置为活动状态
		$ebayUpdateQuotaLogModel->setLogActive($logID);
		
		//更新获取最新的额度
		$res = $ebayAccountInfoModel->updateLimitRemaining($accountID);
		if(!$res){
			$ebayUpdateQuotaLogModel->setLogFailure($logID);
			$this->setExceptionMessage($ebayAccountInfoModel->getErrorMsg());
			return false;
		}
		//判断可用额度是否处于可用范围内
		$ebayAccountInfo = $ebayAccountInfoModel->getAccountInfoByAccountID($accountID);
		if($ebayAccountInfo['quantity_limit_remaining'] == 0 || $ebayAccountInfo['amount_limit_remaining'] == 0){
			$this->setExceptionMessage("空余量不足");
			return false;
		}
		
		//根据订单日志ID重新获取itemID
		$this->updateProductListingByOrderLogID($orderLogIDs, $logID, $accountID);
		
		$ebayUpdateQuotaLogModel->refreshLogResponseTime($logID);

		//根据分配原则分配库存数量更新到ebay线上
		//$this->updateEbayLimitRule($accountID, $logID, $ebayAccountInfo);
		$this->updateEbayLimitNewRule($accountID, $logID, $ebayAccountInfo);
		
		$ebayAccountInfoModel->updateLimitRemaining($accountID);
	}
	
	/**
	 * @desc 更新额度新规则：listing对象：过去30天内有售出记录的所有在线的listing，多属性的子sku以及单属性
	 * 			       根据销量将剩余的50%的可用额度调至30天内销量在30个以上（包括30）的listing， 
	 * 			   30%额度调至30天内销量在7-30之间（包括7）的listing，
	 *             20%额度调至销量在1-7之间的listing
	 *
	 */
	public function updateEbayLimitNewRule($accountID, $logID, $ebayAccountInfo){
		//新系统规则
		$day = 30;
		$saleStatistics = $this->getOrderSaleCountByDay($day, $accountID);
		//$itemids
		$itemIDs = $skus = array();
		$saleNum30 = $saleNum7 = $saleNum1 = array();
		if($saleStatistics){
			foreach($saleStatistics as $itemID => $items){
				foreach ($items as $sku=>$salenum){
					if($salenum == 0) continue;
					$skus[] = $sku;
					$itemData = array('item_id'=>$itemID, 'sku'=>$sku);
					$key = $itemID."-".$sku;
					if($salenum >= 30){
						$saleNum30[$key] = $itemData;
					}elseif ($salenum >= 7){
						$saleNum7[$key] = $itemData;
					}else{
						$saleNum1[$key] = $itemData;
					}
				}
				$itemIDs[] = $itemID;
			}
		}
		$limitRemainAmount = $ebayAccountInfo['amount_limit_remaining'];
		$limitRemainQuantity = $ebayAccountInfo['quantity_limit_remaining'];
		
		$availableRemainAmount = (1-intval($ebayAccountInfo['remain_rate'])/100)*$limitRemainAmount;
		$availableRemainQuantity = (1-intval($ebayAccountInfo['remain_rate'])/100)*$limitRemainQuantity;
		
		//取出所有符合的在线listing
		$ebayProductModel = new EbayProduct();
		$ebayProductVariantModel = new EbayProductVariation();
		//$ebayProductList = $ebayProductModel->findAll("item_id in(". MHelper::simplode($itemIDs) .") AND sku in(". MHelper::simplode($skus) .") and account_id='{$accountID}' and is_multiple=0");
		$ebayProductList = $ebayProductModel->getDbConnection()->createCommand()->from($ebayProductModel->tableName())
																->select("item_id,sku,quantity,current_price,sku_online")
																->where("item_id in(". MHelper::simplode($itemIDs) .") AND sku in(". MHelper::simplode($skus) .") and account_id='{$accountID}' and is_multiple=0")
																->queryAll();
		//$ebayProductVariantList = $ebayProductVariantModel->findAll("item_id in(". MHelper::simplode($itemIDs) .") AND sku in(". MHelper::simplode($skus) .")");
		$ebayProductVariantList = $ebayProductVariantModel->getDbConnection()->createCommand()->from($ebayProductVariantModel->tableName())
													->select("item_id,sku,quantity,sale_price as current_price,sku_online")
													->where("item_id in(". MHelper::simplode($itemIDs) .") AND sku in(". MHelper::simplode($skus) .")")
													->queryAll();
		$productLists = array();
		$productCount = 0;
		if($ebayProductList){
			foreach ($ebayProductList as $product){
				$productLists[$product['item_id']][$product['sku']] = $product;
				$productCount++;
			}
		}
		if($ebayProductVariantList){
			foreach ($ebayProductVariantList as $product){
				//$product['current_price'] = $product['sale_price'];
				$productLists[$product['item_id']][$product['sku']] = $product;
				$productCount++;
			}
		}
		unset($ebayProductList, $ebayProductVariantList);
		//计算总金额(没有汇率转换)
		$totalAmount1 = $ebayProductModel->getDbConnection()->createCommand()
										->from($ebayProductModel->tableName())
										->select("SUM(current_price*quantity) as total")
										->where("item_id in(". MHelper::simplode($itemIDs) .") AND sku in(". MHelper::simplode($skus) .") and account_id='{$accountID}' and is_multiple=0")
										->queryScalar();
		$totalAmount2 = $ebayProductVariantModel->getDbConnection()->createCommand()
										->from($ebayProductVariantModel->tableName())
										->select("SUM(sale_price*quantity) as total")
										->where("item_id in(". MHelper::simplode($itemIDs) .") AND sku in(". MHelper::simplode($skus) .") ")
										->queryScalar();
		$totalAmount = $totalAmount1+$totalAmount2;
		$limitRemainQuantity30 = $availableRemainQuantity*0.5;
		$limitRemainQuantity7 = $availableRemainQuantity*0.3;
		$limitRemainQuantity1 = $availableRemainQuantity*0.2;
		
		$limitRemainAmount30 = $availableRemainAmount*0.5;
		$limitRemainAmount7 = $availableRemainAmount*0.3;
		$limitRemainAmount1 = $availableRemainAmount*0.2;
		
		
		
		//需要更新的数据
		$updateData = array();

		//>30个销量
		$newupdateData = $this->getUploadLimitQuantityData($productLists, $saleNum30, $limitRemainQuantity30, $limitRemainAmount30, $totalAmount);
		if($newupdateData){
			$updateData = array_merge($updateData, $newupdateData);
		}
		//7-30个
		$newupdateData = $this->getUploadLimitQuantityData($productLists, $saleNum7, $limitRemainQuantity7, $limitRemainAmount7, $totalAmount);
		if($newupdateData){
			$updateData = array_merge($updateData, $newupdateData);
		}
		//0-7个
		$newupdateData = $this->getUploadLimitQuantityData($productLists, $saleNum7, $limitRemainQuantity7, $limitRemainAmount7, $totalAmount);
		if($newupdateData){
			$updateData = array_merge($updateData, $newupdateData);
		}
		if($this->changeQtyOnEbay($accountID, $updateData, array())){
			return true;
		}else{
			return false;
		}
	}
	
	/**
	 * @desc 获取需要更新库存数量
	 * @param unknown $productLists
	 * @param unknown $saleNum
	 * @param unknown $limitRemainQuantity
	 * @param unknown $limitRemainAmount
	 * @param unknown $totalAmount
	 * @return Ambigous <multitype:, multitype:multitype:unknown number  >
	 */
	public function getUploadLimitQuantityData($productLists, $saleNum, $limitRemainQuantity, $limitRemainAmount, $totalAmount){
		$tempUpdateData = array();
		//累计的金额数
		$addAmount = 0;
		$addQuantity = 0;
		if($productLists){
			//@TODO 需要判断更改的库存数量和本身的库存数量
			//是否需要更换方式
			$isChange = false;
			if(count($saleNum)){
				//平均数量
				$tempUpdateData = array();
				$averageQuantity = ceil($limitRemainQuantity/count($saleNum));//平摊
				if($averageQuantity <= 0 ) $averageQuantity = 5;
				foreach ($productLists as $itemID=>$products){
					foreach ($products as $sku=>$product){
						$key = $product['item_id']."-".$product['sku'];
						if(!isset($saleNum[$key])) continue;
						if($product['quantity'] >= $averageQuantity){
							$quantity = $averageQuantity;
							$diffQuantity = ($product['quantity'] - $quantity);
							$addQuantity -= $diffQuantity;
							$addAmount -= $diffQuantity*$product['current_price'];
						}else{
							$quantity = $averageQuantity;
							$diffQuantity = ($averageQuantity - $product['quantity']);
							$addQuantity += $diffQuantity;
							$addAmount += $diffQuantity*$product['current_price'];
						}
						
						if($addAmount > $limitRemainAmount){
							//退出且要清除数据到初始化
							$isChange = true;
							$tempUpdateData = array();
							$addAmount = 0;
							$addQuantity = 0;
							break;
						}
						$tempUpdateData[] = array(
								'ItemID'	=>	$product['item_id'],
								'SKU'		=>	$product['sku_online'],
								'Quantity'	=>	$quantity
						);
					}
				}
				//按金额比例, 而这个时候导致数量超过，直接跳出
				if($isChange){
					foreach ($productLists as $itemID=>$products){
						foreach ($products as $sku=>$product){
							$key = $product['item_id']."-".$product['sku'];
							if(!isset($saleNum[$key])) continue;
							
							if($product['current_price'] <= 0) continue;
							//按照金额比例进行调整
							$quantity = intval(($product['quantity']*$product['current_price'])/$totalAmount*$limitRemainAmount/$product['current_price']);
							if($quantity >= $product['quantity']){
								$diffQuantity = $quantity-$product['quantity'];
								$addQuantity += $diffQuantity;
							}else{
								$diffQuantity = $product['quantity'] - $quantity;
								$addQuantity -= $diffQuantity;
							}
							
							if($addQuantity > $limitRemainQuantity){
								//退出而不清除
								break;
							}
							$tempUpdateData[] = array(
									'ItemID'	=>	$product['item_id'],
									'SKU'		=>	$product['sku_online'],
									'Quantity'	=>	$quantity
							);
						}
					}
				}
			}
		}
		return $tempUpdateData;
	}
	/**
	 * @desc 更新额度老系统规则
	 * @param unknown $accountID
	 * @param unknown $logID
	 * @param unknown $ebayAccountInfo
	 */
	public function updateEbayLimitRule($accountID, $logID, $ebayAccountInfo){
		//老系统规则
		$ebayProductModel = EbayProduct::model();
		$ebayUpdateQuotaLogModel = EbayUpdateQuotaLog::model();
		
		//写入rank表
		$ebayProductRankModel = new EbayProductRank();
		$ebayProductRankModel->updateEbayProductRankWatchCount($accountID);
		
		$ebayProductRankModel->addEbayProductRankDataForNewAddProduct($accountID);
		
		//获取浏览数量列表
		$watchCountList = $ebayProductRankModel->getWatchCount($accountID);
		//统计7、15、30天内的在线sku销售量
		$rankArr = array(
				'7' => '0.65',
				'15' => '0.25',
				'30' => '0.095'
		);
		$wcRate = 0.005;//浏览量所占的比例
		$saleStatistics = array();
		foreach ($rankArr as $day=>$rate){
			$saleStatistics[$day] = $this->getOrderSaleCountByDay($day, $accountID);
		}
		
		$count=0;
		foreach($watchCountList as $itemID => $items){
			foreach($items as $sku=>$item){
				$count++;
				$rank[$itemID][$sku] = floatval($wcRate)*$item['watch_count'];//计算item_id下的sku的浏览量在权重计算中占得数值
				foreach($rankArr as $day=>$rankItem){//再循环计算规则
					if(!empty($saleStatistics[$day][$itemID][$sku])){//如果存在销量
						//计算权重
						$rank[$itemID][$sku] += $rankItem*intval($saleStatistics[$day][$itemID][$sku]);
					}
				}
				$rankData = array(
						'rank_weight' => $rank[$itemID][$sku],
				);
				if($item['quantity'] < $ebayAccountInfo['lowest_qty']){//在线数量小于最低可变数，优先做改变
					$rankData['spec_rank_weight'] = 1;
				}else{
					$rankData['spec_rank_weight'] = 2;
				}
				//更新产品权重
				$ebayProductRankModel->updateData($rankData, "item_id='{$itemID}' and sku='{$sku}'");
				if($count % 100 == 0){//每100个更新一次response_time
					$ebayUpdateQuotaLogModel->refreshLogResponseTime($logID);
				}
			}
		}
		
		//============== 老系统规则 ====================
		$lowest_qty = $ebayAccountInfo['lowest_qty'];//最低可变数
		
		$conditions = "item_status = 1 AND account_id = ".$accountID;
		$all_qty = $ebayProductModel->getDbConnection()->createCommand()->from($ebayProductModel->tableName())->select("SUM(quantity)")->where($conditions)->queryScalar();
		//计算账号的产品的平均销售价格（只算US）
		$conditions = "item_status = 1 AND account_id = ".$accountID." AND site_id = 0 AND buyitnow_price_currency = 'USD'";
		$totalPrice = $ebayProductModel->getDbConnection()->createCommand()->from($ebayProductModel->tableName())->select("SUM(buyitnow_price)")->where($conditions)->queryScalar();
		$countListing = $ebayProductModel->getDbConnection()->createCommand()->from($ebayProductModel->tableName())->select("count(*)")->where($conditions)->queryScalar();
		
		$validQtyRemain = floatval($ebayAccountInfo['amount_limit_remaining'])/($totalPrice / $countListing);//计算出来的可刊登个数 lihy
		if( $validQtyRemain < intval($ebayAccountInfo['quantity_limit_remaining']) && $validQtyRemain>0){
			$finalQty = $validQtyRemain;
		}else{
			$finalQty = intval($ebayAccountInfo['quantity_limit_remaining']);
		}
		//最高可变数（   计算方式 =  可用数*（1-警戒百分比）/在线listing数量*2  ）
		$heightest_qty = floor(($finalQty+intval($all_qty))*(1-intval($ebayAccountInfo['remain_rate'])/100)/count($watchCountList)*2);
		
		$standard_qty = intval($ebayAccountInfo['quantity_limit'])*intval($ebayAccountInfo['remain_rate'])/100;
		$standard_amount = floatval($ebayAccountInfo['amount_limit'])*intval($ebayAccountInfo['remain_rate'])/100;
		$now_qty_rate = intval($ebayAccountInfo['quantity_limit_remaining'])/intval($ebayAccountInfo['quantity_limit']);//现在的可用数量占总数的比例
		$now_amount_rate = floatval($ebayAccountInfo['amount_limit_remaining'])/floatval($ebayAccountInfo['amount_limit']);//现在的可用金额占总金额的比例
		$isChange = true;
		if($now_amount_rate >= intval($ebayAccountInfo['remain_rate'])/100 && $now_qty_rate >= intval($ebayAccountInfo['remain_rate'])/100){//如果数量和金额都超过了警戒线
			if($now_amount_rate >= $now_qty_rate){//金额超的更多，根据数量降
				$total = intval($ebayAccountInfo['quantity_limit_remaining']) - floor($standard_qty);//总的空余数
				$type = 1;
				$limit_qty = $heightest_qty;
				$change = 1;
		
			}else{//数量超的更多，根据金额降
				$total = floatval($ebayAccountInfo['amount_limit_remaining']) - $standard_amount;//总的空余金额
				$type = 1;
				$limit_qty = $heightest_qty;
				$change = 2;
		
			}
		}elseif($now_amount_rate < intval($ebayAccountInfo['remain_rate'])/100 && $now_qty_rate < intval($ebayAccountInfo['remain_rate'])/100){//如果数量和金额都低于警戒线
			if($now_amount_rate >= $now_qty_rate){//数量少的更多，根据数量升
				$total = floor($standard_qty) - intval($ebayAccountInfo['quantity_limit_remaining']);//总的不足数
				$type = 2;
				$limit_qty = $lowest_qty;
				$change = 1;
		
			}else{//金额少的更多，根据金额升
				$total =  $standard_amount - floatval($ebayAccountInfo['amount_limit_remaining']);//总的不足金额
				$type = 2;
				$limit_qty = $lowest_qty;
				$change = 2;
		
			}
		}elseif($now_amount_rate>=intval($ebayAccountInfo['remain_rate'])/100 && $now_qty_rate < intval($ebayAccountInfo['remain_rate'])/100){//只有数量低于警戒线，升数量
			$total = floor($standard_qty) - intval($ebayAccountInfo['quantity_limit_remaining']);//总的不足数
			$type = 2;
			$limit_qty = $lowest_qty;
			$change = 1;
				
		}elseif($now_amount_rate < intval($ebayAccountInfo['remain_rate'])/100 && $now_qty_rate >= intval($ebayAccountInfo['remain_rate'])/100){//只有金额低于警戒线，升金额
			$total =  $standard_amount - floatval($ebayAccountInfo['amount_limit_remaining']);//总的不足金额
			$type = 2;
			$limit_qty = $lowest_qty;
			$change = 2;
				
		}else{
			$isChange = false;
		}
		if($isChange){
			if($this->changeQty($logID, $accountID, $total, $type, $limit_qty, $change)){//更新响应时间
				$ebayUpdateQuotaLogModel->refreshLogResponseTime($logID, array('highest_count'=>$heightest_qty));
				$ebayUpdateQuotaLogModel->setLogSuccess($logID);
			}else{
				$ebayUpdateQuotaLogModel->setLogFailure($logID);
			}
		}
	}
	/**
	 * @desc 参数说明：$total_qty 要增加或减少的总量，$type,是增加还是减少，1为增加，2为减少    $limit_qty  最大可变数或最小可变数  $change  要改的是金额还是数量  1为数量，2为金额 
	 * @param unknown $logid
	 * @param unknown $ebay_account
	 * @param unknown $total_qty
	 * @param unknown $type
	 * @param unknown $limit_qty
	 * @param unknown $change
	 * @return boolean
	 */
	public function changeQty($logid, $accountID, $total_qty, $type, $limit_qty, $change){
		$i = 0;
		$rs = true;
		$qty = $total_qty;//最初的总变化数量或金额
	
		if($type=='1'){//如果是有空余
			$order = 'DESC';//倒序排列
		}else{
			$order = 'ASC';
		}
		$ebayProductModel = EbayProduct::model();
		$ebayProductRankModel = EbayProductRank::model();
		$conditions = 'p.account_id = "'.$accountID.'" AND p.item_status = 1 AND p.listing_duration="GTC"';
		$total = $ebayProductModel->getDbConnection()->createCommand()
											->from($ebayProductModel->tableName() ." as p")
											->join($ebayProductRankModel->tableName() . " as r", "p.item_id=r.item_id")
											->select("count(*)")
											->where($conditions)
											->queryScalar();

		$upData = array();
	
		$update_index = 0;
		while($total_qty > 0 && $i<$total){
			$change_mem = $this->getProductItem($accountID, $order);//每次取第一个
			$update_index++;
			if(empty($change_mem)){
				break;
			}
			if($change_mem['is_promote'] == 1) continue; //促销
			
			$data_change = array();
			$skuInfo = Product::model()->getProductBySku($change_mem['sku']);
			if(empty($skuInfo)) continue;
			
			if($change_mem['is_multiple']=='1'){//如果该产品为多属性子sku
				$child_sku = ProductSelectAttribute::model()->getChildSKUListByProductID($skuInfo['id']);
				$is_variation = EbayProductVariation::model()->getProductVariantInfoByCondition("item_id='{$change_mem['item_id']}' and main_sku = '".$change_mem['sku']."'");
				foreach($is_variation as $vari){
					if(!in_array($vari['sku'], $child_sku)){//如果已经解除了绑定关系，则跳过
						continue;
					}
					if($vari['is_promote'] == 1) continue; //促销
					$prodInfo = Product::model()->getProductBySku($vari['sku']);
					if($prodInfo['product_status'] > Product::STATUS_ON_SALE){//如果子sku下线了,跳过
						continue;
					}
					$currencyRate = new CurrencyRate();
					$rate = $currencyRate->getRateToOther($vari['currency'], 'HKD');
					$data_change[] = array(
					 							'sku' => $vari['sku'],
												'sku_online' => $vari['sku_online'],
					 							'quantity' => $vari['quantity'],
					 							'cur_price' => $vari['sale_price']*$rate//单价换算成港币
					 					);
				}
			}else{
				if($skuInfo['product_status'] > Product::STATUS_ON_SALE){//如果子sku下线了,跳过
					continue;
				}
			
				$currencyRate = new CurrencyRate();
				$rate = $currencyRate->getRateToOther($vari['current_price_currency'], 'HKD');
				$data_change[] = array(
						'sku' => $change_mem['sku'],
						'sku_online' => '',
						'quantity' => $change_mem['quantity'],
						'cur_price' => $change_mem['current_price'] * $rate//单价换算成港币
				);
			}
				
			//$siteid = $change_mem['siteid'];
	
			foreach($data_change as $itm){
				$log_detail[$i] = array(
						'log_id' =>$logid,
						'item_id' => $change_mem['item_id'],
						'sku' => $itm['sku'],
						'type' => $type,
						'account_id' => $accountID
				);
				//准备上传的数据
				$upData[$i]['ItemID'] = $change_mem['item_id'];
				if($itm['sku_online']){
					$upData[$i]['SKU'] = $itm['sku_online'];
				}
					
				if($type=='1'){//增加quantity
					if($change==1){//改变的是数量
						if(intval($itm['quantity'])>$limit_qty){//quantity已经大于最大可变数
							$log_detail[$i]['num'] = intval($itm['quantity'])-$limit_qty;
							$log_detail[$i]['type'] = 2;
	
							$upData[$i]['Quantity'] = $limit_qty;
							$total_qty +=$log_detail[$i]['num'];
							$i++;
						}elseif($total_qty <= ($limit_qty-intval($itm['quantity']))){//总空余数不够这次的增加
							$log_detail[$i]['num'] = $total_qty;
	
							$upData[$i]['Quantity'] = $total_qty+intval($itm['quantity']);
							$total_qty -=$total_qty;
							break 2;
						}elseif(intval($itm['quantity'])==$limit_qty){
							unset($upData[$i]);
							$i++;
						}else{
							$log_detail[$i]['num'] = $limit_qty - intval($itm['quantity']);
	
							$upData[$i]['Quantity'] = $limit_qty;
							$total_qty -=$log_detail[$i]['num'];
							$i++;
						}
					}else{//改变的是金额
						if(intval($itm['quantity'])>$limit_qty){//quantity已经大于最大可变数
							$log_detail[$i]['num'] = intval($itm['quantity'])-$limit_qty;
							$log_detail[$i]['type'] = 2;
	
							$upData[$i]['Quantity'] = $limit_qty;
							$total_qty +=$log_detail[$i]['num']*$itm['cur_price'];
							$i++;
						}elseif($total_qty/$itm['cur_price'] <= ($limit_qty-intval($itm['quantity']))){//总空余数不够这次的增加
							$log_detail[$i]['num'] = floor($total_qty/$itm['cur_price']);
	
							$upData[$i]['Quantity'] = intval($total_qty/$itm['cur_price'])+intval($itm['quantity']);
							$total_qty -=floor($total_qty/$itm['cur_price'])*$itm['cur_price'];
	
							break 2;
						}elseif(intval($itm['quantity'])==$limit_qty){
							unset($upData[$i]);
							$i++;
						}else{
							$log_detail[$i]['num'] = $limit_qty - intval($itm['quantity']);
	
							$upData[$i]['Quantity'] = $limit_qty;
							$total_qty -= ($limit_qty - intval($itm['quantity']))*$itm['cur_price'];
							$i++;
						}
					}
				}else{//减少quantity
					if($change==1){//改变的是数量
						if(intval($itm['quantity'])<$limit_qty){//quantity已经小于最小可变数
							$log_detail[$i]['num'] = $limit_qty - intval($itm['quantity']);
							$log_detail[$i]['type'] = 1;
	
							$upData[$i]['Quantity'] = $limit_qty;
							$total_qty +=$log_detail[$i]['num'];
							$i++;
						}elseif($total_qty <= (intval($itm['quantity']) - $limit_qty)){//总减少数不够这次的减少
							$log_detail[$i]['num'] = $total_qty;
	
							$upData[$i]['Quantity'] = intval($itm['quantity'])-$total_qty;
							$total_qty -=$total_qty;
							break 2;
						}elseif(intval($itm['quantity'])==$limit_qty){
							unset($upData[$i]);
							$i++;
						}else{
							$log_detail[$i]['num'] = intval($itm['quantity']) - $limit_qty;
	
							$upData[$i]['Quantity'] = $limit_qty;
							$total_qty -=$log_detail[$i]['num'];
							$i++;
						}
					}else{//改变的是金额
						if(intval($itm['quantity'])<$limit_qty){//quantity已经大于最大可变数
							$log_detail[$i]['num'] = $limit_qty - intval($itm['quantity']);
							$log_detail[$i]['type'] = 1;
	
							$upData[$i]['Quantity'] = $limit_qty;
							$total_qty +=$log_detail[$i]['num']*$itm['cur_price'];
							$i++;
						}elseif($total_qty/$itm['cur_price'] <= (intval($itm['quantity']) - $limit_qty)){//总减少数不够这次的减少
							$log_detail[$i]['num'] = floor($total_qty/$itm['cur_price']);
	
							$upData[$i]['Quantity'] = intval($itm['quantity'])-intval($total_qty/$itm['cur_price']);
							$total_qty -=floor($total_qty/$itm['cur_price'])*$itm['cur_price'];
	
							break 2;
						}elseif(intval($itm['quantity'])==$limit_qty){
							unset($upData[$i]);
							$i++;
						}else{
							$log_detail[$i]['num'] = intval($itm['quantity']) - $limit_qty;
	
							$upData[$i]['Quantity'] = $limit_qty;
							$total_qty -= (intval($itm['quantity']) - $limit_qty)*$itm['cur_price'];
							$i++;
						}
					}
				}
			}
		}
		
		if($this->changeQtyOnEbay($accountID, $upData, $log_detail)){
			return true;
		}else{
			return false;
		}
	}
	
	/**
	 * @desc 上传库存数量到ebay上
	 * @param unknown $accountID
	 * @param unknown $upData
	 * @param unknown $log_detail
	 * @return boolean
	 */
	public function changeQtyOnEbay($accountID, $upData, $log_detail){
		//@TODO test
		echo $accountID;
		var_dump(count($upData));
		//echo "<pre>";
		var_dump($upData);
		//echo "</pre>";
		exit;
		$result = true;
		$k = 0;
		$total = count($upData);
		$ebayUpdateQuotaLogDetail = new EbayUpdateQuotaLogDetail();
		$ebayProductVariationModel = new EbayProductVariation();
		$ebayProductModel = new EbayProduct();
		while($k < $total){
			$data_up = array_slice($upData, $k, 4, true);//每4个传一次
			$k += count($data_up);
			$request = new ReviseInventoryStatusRequest();
			$request->setAccount($accountID);
			if($data_up){
				foreach ($data_up as $val){
					$request->setItemID($val['ItemID']);
					$request->setQuantity($val['Quantity']);
					if(!empty($val['SKU'])){
						$request->setSku($val['SKU']);
					}
					$request->push();
				}
			}
			$response = $request->setRequest()->sendRequest()->getResponse();
			if($request->getIfSuccess()){
				$result &= true;
				foreach($data_up as $key=>$i){
					if($i['SKU']){
						$ebayProductVariationModel->updateAll(array('quantity'=>$i['Quantity']), "item_id = '".$i['ItemID']."' AND sku = '".$i['SKU']."'");
					}else{
						$ebayProductModel->updateAll(array('quantity'=>$i['Quantity']), "item_id = '".$i['ItemID']."'");
					}
					if(isset($log_detail[$key]))
						$ebayUpdateQuotaLogDetail->addLogData($log_detail[$key]);
				}
			}else{
				$result &= false;
			}
		}
		return $result;	
	}
	
	/**
	 * @desc 单个产品条目
	 * @param unknown $accountid
	 * @param unknown $order
	 * @return Ambigous <>
	 */
	public function getProductItem($accountid, $order){
		static $items=array(),$page=0,$index=0;
		$length = 200;
		if(!isset($items[$index])){
			$ebayProductModel = new EbayProduct();
			$ebayProductRankModel = new EbayProductRank();
			$items = $ebayProductModel->getDbConnection()->createCommand()
												->from($ebayProductModel->tableName() . " as p")
												->join($ebayProductRankModel->tableName() ." as r", "r.item_id=p.item_id")
												->select('r.sku,r.item_id,p.quantity,p.site_id, p.is_multiple, p.current_price_currency,p.current_price, p.is_promote')
												->where('p.account_id = "'.$accountid.'" AND p.item_status = 1 AND p.listing_duration="GTC"')
												->order('r.spec_rank_weight,r.rank_weight '.$order.', p.start_time '.$order)
												->limit($length, $page*$length)
												->queryAll();
			$page++;
			$index=0;
		}
		return $items[$index++];
	}
	
	/**
	 * @更新当前获取到的item信息
	 * @param unknown $orderLogIDs
	 * @param unknown $logID
	 * @param unknown $accountID
	 */
	public function updateProductListingByOrderLogID($orderLogIDs, $logID, $accountID){
		$orderModel = new Order();
		$orderDetailModel = new OrderDetail();
		$conditions = 'O.log_id IN ('.MHelper::simplode($orderLogIDs).') AND O.platform_code = "'. Platform::CODE_EBAY .'"';
		$lists = $orderModel->getDbConnection()->createCommand()
							->from($orderModel->tableName() . " as O")
							->select("D.item_id")
							->join($orderDetailModel->tableName() . " as D", "D.order_id=O.order_id")
							->where($conditions)
							->group("D.item_id")
							->queryAll();
		if($lists){
			$ebayProductModel = new EbayProduct();
			foreach ($lists as $list){
				//同步对应的item
				$ebayProductModel->setAccountID($accountID);
				$ebayProductModel->getItemInfo($list['item_id']);
				EbayUpdateQuotaLog::model()->refreshLogResponseTime($logID);
			}
		}
	}
	
	/**
	 * @desc 获取订单销售数量
	 * @param unknown $day
	 * @param unknown $accountID
	 * @return Ambigous <multitype:, unknown>
	 */
	public function getOrderSaleCountByDay($day, $accountID){
		$platformCode = Platform::CODE_EBAY;
		$searchTime = date("Y-m-d H:i:s",strtotime("-".intval($day)." days"));
		$return = array();
		$orderModel = new Order();
		$conditions = 'O.ori_pay_time >= "'.$searchTime.'" AND O.platform_code = "'.$platformCode.'" AND O.account_id = "'.$accountID.'"';
		$lists = $orderModel->getDbConnection()->createCommand()
						->from($orderModel->tableName() . " as O")
						->select("D.sku_old as sku, SUM(D.quantity_old) as sale_num, D.item_id")
						->join(OrderDetail::model()->tableName() . " as D", "D.order_id=O.order_id")
						->where($conditions)
						->group("D.item_id, D.sku_old")
						->queryAll();
		if($lists){
			foreach ($lists as $list){
				$return[$list['item_id']][$list['sku']] = $list['sale_num'];
			}
		}
		return $return;
	}
}