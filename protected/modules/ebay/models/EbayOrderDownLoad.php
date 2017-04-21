<?php
/**
 * @desc Ebay订单拉取
 * @author yangsh
 * @since 2016-06-08
 */
class EbayOrderDownLoad extends EbayOrderMain {

	const EVENT_NAME      		= 'get_orderids';

	const EVENT_NAME_CHECK      = 'check_orderids';

	const EVENT_NAME_SYNC      	= 'sync_order';
	
	/** @var string 异常信息*/
	protected $_Exception   	= null;
	
	/** @var int 日志编号*/
	protected $_LogID       	= 0;
	
	/** @var int 账号ID*/
	protected $_AccountID   	= null;

	/** @var string OrderID */
	protected $_OrderID     	= '';

	/** @var string OrderStatus */
	protected $_OrderStatus 	= '';

	/** @var array 拉单时间段 */
	protected $_TimeArr     	= array();

	/** @var int type */
	protected $_Type        	= 0;

	/** @var array OutputSelectors */
    protected $_OutputSelectors = array(
		'HasMoreOrders',
		'PaginationResult',
		'ReturnedOrderCountActual',
		'OrderArray.Order.OrderID',
		'OrderArray.Order.OrderStatus',
		'OrderArray.Order.Total',
		'OrderArray.Order.CreatedTime',
	);

    /**@var ebay订单状态 */
    const ORDER_ALL 			= 'All';
    const ORDER_ACTIVE      	= 'Active';
    const ORDER_CANCELLED   	= 'Cancelled';
    const ORDER_CANCELPENDING 	= 'CancelPending';
    const ORDER_COMPLETED   	= 'Completed';
    const ORDER_Inactive  		= 'Inactive';

	public static function model($className = __CLASS__) {
		return parent::model ( $className );
	}
		
	/**
	 * 设置异常信息
	 * @param string $message        	
	 */
	public function setExceptionMessage($message) {
		$this->_Exception = $message;
		return $this;
	}

	/**
	 * 获取异常信息
	 * @return string
	 */
	public function getExceptionMessage() {
		return $this->_Exception;
	}		

	/**
	 * 设置日志编号
	 *
	 * @param int $logID        	
	 */
	public function setLogID($logID) {
		$this->_LogID = $logID;
		return $this;
	}	

	/**
	 * 设置账号ID
	 * @param int $accountID
	 */
	public function setAccountID($accountID) {
		$this->_AccountID = $accountID;
		return $this;
	}

	/**
	 * 设置订单ID
	 * @param string $orderId
	 */
	public function setOrderID($orderId) {
		$this->_OrderID = $orderId;
		return $this;
	}	

	/**
	 * 设置订单状态
	 * @param string $orderStatus
	 */
	public function setOrderStatus($orderStatus) {
		$this->_OrderStatus = $orderStatus;
		return $this;
	}		

	/**
	 * 设置拉单时间段
	 * 上次有成功,则将上次结束时间往前推15分钟，避免漏单，
	 * 若不存在已成功的，则从2天前开始拉(需换算成格林威治时间)
	 * @param array $timeArr
	 */
	public function setTimeArr($timeArr = array()) {
		$offtime = 8 * 3600;
		if ( empty($timeArr) ) {
			$lastLog = EbayLog::model ()->getLastLogByCondition ( array (
				'account_id' => $this->_AccountID,
				'event'      => self::EVENT_NAME,
				'status'     => EbayLog::STATUS_SUCCESS 
			) );
			$lastEventLog = array ();
			if (! empty ( $lastLog )) {
				$lastEventLog = EbayLog::model ()->getEventLogByLogID ( self::EVENT_NAME, $lastLog ['id'] );
			}
			$now_time   = time () - $offtime;
			if (! empty ( $lastEventLog )) {
				$start_time = strtotime ( $lastEventLog ['end_time'] );//UTC时间
				if ($now_time - $start_time > 3 * 86400) {
					$start_time = $now_time - 3 * 86400;
				} else {
					$start_time = $start_time - 15 * 60;
				}
				$start_time = date ( 'Y-m-d H:i:s', $start_time );
			} else {
				$start_time = date ( 'Y-m-d H:i:s', $now_time - 3 * 86400 );
			}

			$end_time = date ( 'Y-m-d H:i:s', $now_time );
			$timeArr = array (
				'start_time' => $start_time,
				'end_time'   => $end_time 
			);
		}
		$this->_TimeArr = $timeArr;
		return $this;
	}

	/**
	 * [getTimeArr description]
	 * @return [type] [description]
	 */
	public function getTimeArr() {
		return $this->_TimeArr;
	}

	/**
	 * 抓取订单id
	 * @param int $mode 1:ModTime 2:CreateTime
	 */
	public function startGetOrderIds($mode=1) {
		$accountID 		= $this->_AccountID;
		$logID 			= $this->_LogID;
		$path 			= 'ebay/getOrderIds/'.date("Ymd").'/'.$accountID.'/'.date("Hi");
		$total 			= 0;	
		$errMsg  		= '';
		$request   		= new GetOrdersRequest ();
		$request ->setAccount( $accountID )
				 ->setOrderStatus( $this->_OrderStatus )
				 ->setOutputSelector( $this->_OutputSelectors );
				 
		if ($mode == 1) {
			$request->setModTimeFrom(date('Y-m-d\TH:i:s\Z',strtotime($this->_TimeArr['start_time'])));
			$request->setModTimeTo(date('Y-m-d\TH:i:s\Z',strtotime($this->_TimeArr['end_time'])));
		} else {
			$request->setCreateTimeFrom(date('Y-m-d\TH:i:s\Z',strtotime($this->_TimeArr['start_time'])));
			$request->setCreateTimeTo(date('Y-m-d\TH:i:s\Z',strtotime($this->_TimeArr['end_time'])));
		}

		while ( $request->_PageNumber <= $request->_TotalPage ) {
			$currentPage = $request->_PageNumber;
			$response 	 = $request->setRequest ()->sendRequest ()->getResponse ();
			//MHelper::writefilelog($path.'/response_'.$currentPage.'.log', print_r($response,true)."\r\n");
			//返回失败
			if (!$request->getIfSuccess ()) {
				$logText = 'ErrPageNo:'.$request->_PageNumber.',AccountID:'.$accountID.'##'.$request->getErrorMsg ();
				$errMsg .= $logText;
				//MHelper::writefilelog($path.'/requestErr_'.$currentPage.'.log', $logText."\r\n");
				break;
			}

			if (!isset($response->PaginationResult->TotalNumberOfPages) ) {
				$errMsg .= 'TotalNumberOfPages NOT EXIST';
				break;
			}

			if (empty($response->OrderArray->Order)) {
				break;
			}			

			$request->setTotalPage ( $response->PaginationResult->TotalNumberOfPages ); // 设置总页数
			$request->setPageNum ( $request->_PageNumber + 1 );			
			
			//处理每个订单数据
			foreach ( $response->OrderArray->Order as $order ) {
				try {
					$ebayGrabOrder 		= EbayGetorder::model();
					$platformOrderId 	= $order->OrderID;
					$lastInsertID 		= $ebayGrabOrder->setAccountID($accountID)
														->setLogID($logID)
														->saveEbayGetoderInfo($order);
					if (!$lastInsertID) {
						throw new Exception("Save OrderId Failure");
					}
					$total++;
					//MHelper::writefilelog($path.'/commit_'.$currentPage.'.log', $platformOrderId."\r\n");
				} catch (Exception $e) {
					$errMsg .= $platformOrderId.' ## '.$e->getMessage();
					//MHelper::writefilelog($path.'/rollback_'.$currentPage.'.log', $platformOrderId."\r\n");
				}
			}
		}//endwhile
		$logText = "pull num:{$total} ,AccountID:{$accountID} finish ##";
		echo $logText."\r\n<br>";
		//MHelper::writefilelog($path.'/total'.'.log', $logText."\r\n");
		$this->setExceptionMessage($errMsg);
		return $errMsg == '' ? true : false;
	}

	/**
	 * 下载订单完整信息
	 */
	public function startDownloadOrder() {
		$accountID 				= $this->_AccountID;
		$orderIds 				= $this->_OrderID;
		$path  					= 'ebay/downOrders/'.date("Ymd").'/'.$accountID.'/'.date("His");
		$orderIdArray 			= explode(',', $orderIds);
		
		//1.检查账号是否可拉取订单
		$ebayGetorder 			= EbayGetorder::model();
		$criterias['accountId'] = $accountID;
		$criterias['orderIds'] 	= $orderIdArray;
		$dataList 				= $ebayGetorder->getPendingRecordsByCriterias($criterias);
		if (empty($dataList)) {
			$this->setExceptionMessage('No Orderid for Grabing Order');
			return false;
		}
		//2.设置抓单状态为正在运行
		$ebayGetorder->setRunning($orderIdArray);
		//3. 开始抓单
		$request = new GetOrdersRequest ();
		$request ->setAccount( $accountID )
				 ->setOrderIDArray($orderIdArray);
		$total 	 = 0;
		$errMsg  = '';
		while($request->_PageNumber <= $request->_TotalPage ) {
			$currentPage = $request->_PageNumber;
			$response 	 = $request->setRequest ()->sendRequest ()->getResponse ();
			//MHelper::writefilelog($path.'/response_'.$currentPage.'.log', print_r($response,true)."\r\n");
			if (!$request->getIfSuccess ()) { // 交互成功
				$logText = 'ErrPageNo:'.$request->_PageNumber.',AccountID:'.$accountID.','.$request->getErrorMsg ();
				$errMsg .= $logText;
				//MHelper::writefilelog($path.'/requestErr_'.$currentPage.'.log', $logText."\r\n");
				break;
			}

			if (!isset($response->PaginationResult->TotalNumberOfPages) ) {
				$errMsg .= 'TotalNumberOfPages NOT EXIST';
				break;
			}

			if (empty($response->OrderArray->Order) ) {
				break;
			}

			$request->setTotalPage ( $response->PaginationResult->TotalNumberOfPages ); // 设置总页数
			$request->setPageNum ( $request->_PageNumber + 1 );

			//处理每个订单数据
			foreach ( $response->OrderArray->Order as $order ) {
				$dbTransaction 				= $this->dbConnection->beginTransaction ();// 开启事务
				try {
					$ebayOrderMain 			= EbayOrderMain::model();
					$ebayOrderDetail 		= EbayOrderDetail::model();
					$ebayOrderTransaction 	= EbayOrderTransaction::model();
					$platformOrderId		= trim($order->OrderID);
					$sellerEmail 			= 'Invalid Request' != trim($order->SellerEmail) ? trim($order->SellerEmail) : '';
					//Save OrderInfo
					$orderMainId 			= $ebayOrderMain->setAccountID($accountID)
															->saveOrder($order);
					if (!$orderMainId) {
						throw new Exception("Save OrderInfo Error ". $ebayOrderMain->getExceptionMessage());
					}
					//Save OrderDetailInfo
					$isOk 					= $ebayOrderDetail->setAccountID($accountID)
													  		  ->saveOrderDetail($order);
					if (!$isOk) {
						throw new Exception("Save OrderDetailInfo Error " . $ebayOrderDetail->getExceptionMessage());
					}
					//Save OrderTransactionInfo
					$isOk 					= $ebayOrderTransaction->setAccountID($accountID)
														   		   ->setPaypalAccount($sellerEmail)
														   		   ->saveOrderTransaction($order);
					if (!$isOk) {
						throw new Exception("Save OrderTransactionInfo Error " . $ebayOrderTransaction->getExceptionMessage());
					}
					//设置已完成下载			
					$ebayGetorder->setFinish($platformOrderId);
					
					$total++;
					$dbTransaction->commit ();
					//MHelper::writefilelog($path.'/commit_'.$currentPage.'.log', $platformOrderId."\r\n");
				} catch (Exception $e) {
					$logText = $platformOrderId.' ### '.$e->getMessage();
					$errMsg .= $logText;
					$dbTransaction->rollback ();
					EbayGetorder::model()->setPendingForInit($platformOrderId);
					//MHelper::writefilelog($path.'/rollback_'.$currentPage.'.log', $logText."\r\n");
				}
			}
		}//endwhile
		$logText = "pull num:{$total} ,AccountID:{$accountID} finish ##";
		echo $logText."\r\n<br>";
		//MHelper::writefilelog($path.'/total'.'.log', $logText."\r\n");
		$this->setExceptionMessage($errMsg);
		return $errMsg == '' ? true : false;
	}

}