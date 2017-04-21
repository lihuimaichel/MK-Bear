<?php
class JdorderController extends UebController {
	
	/**
	 * @desc 访问过滤配置
	 * @see CController::accessRules()
	 */
	public function accessRules() {
		return array(
				array(
						'allow',
						'users' => array('*'),
						'actions' => array('getorders')
				),
		);
	}
	
	/**
	 * @desc 拉取订单 
	 * @link /jd/jdorder/getorders/account_id/1/type/1/day/3/bug/1
	 */
	public function actionGetorders() {
		set_time_limit(2*3600);
		ini_set('display_errors', true);
		error_reporting(E_ALL && ~E_STRICT);

		$accountID = Yii::app()->request->getParam('account_id');
		$type      = Yii::app ()->request->getParam ( 'type' ); // 1为补拉
		$day       = Yii::app ()->request->getParam ( 'day' ); // 补拉天数

		if (empty ( $day ))
			$day = 7;	//补拉默认为7天 Liz|20160527	
		if ($accountID) {
			$logID = JdLog::model()->prepareLog($accountID,JdOrder::EVENT_NAME);
			if( $logID ){
				//1.检查账号是否可拉取订单
				$checkRunning = JdLog::model()->checkRunning($accountID, JdOrder::EVENT_NAME);
				if( !$checkRunning ){
					echo Yii::t('systems', 'There Exists An Active Event')."<br>";
					JdLog::model()->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
				}else{
					//2.准备拉取日志信息
					$timeArr = JdOrder::model()->getTimeArr($accountID);

					//补拉是取最近三天的记录
					if ($type == 1) {
						$timeArr ['start_time'] = date ( 'Y-m-d H:i:s', time () - $day * 24 * 3600 );
						$timeArr ['end_time'] = date ( 'Y-m-d H:i:s', time () );
					}

					//插入本次log参数日志(用来记录请求的参数)
					$eventLog = JdLog::model()->saveEventLog(JdOrder::EVENT_NAME, array(
							'log_id'        => $logID,
							'account_id'    => $accountID,
							'start_time'    => $timeArr['start_time'],
							'end_time'      => $timeArr['end_time'],
					));

					//设置日志为正在运行
					JdLog::model()->setRunning($logID);

					//3.拉取订单
					$jdOrderModel = new JdOrder();
					$jdOrderModel->setAccountID($accountID);//设置账号
					$jdOrderModel->setLogID($logID);		//设置日志编号
					//$jdOrderModel->setPage($page);			//设置页数
					//$jdOrderModel->setPageSize($pageSize);	//设置每页记录数
					$flag = $jdOrderModel->getOrders($timeArr);//拉单
					var_dump($flag);

					//4.更新日志信息
					if( $flag ){
						JdLog::model()->setSuccess($logID);
						JdLog::model()->saveEventStatus(JdOrder::EVENT_NAME, $eventLog, JdLog::STATUS_SUCCESS);
					}else{
						//保存日志到文件
						$logPath = Yii::getPathOfAlias('webroot').'/log/jd/getorder_error-' . date('Ymd') . '.txt';
						file_put_contents($logPath, $jdOrderModel->getDebugLog(), FILE_APPEND);
						JdLog::model()->setFailure($logID, $jdOrderModel->getErrorMessage());
						JdLog::model()->saveEventStatus(JdOrder::EVENT_NAME, $eventLog, JdLog::STATUS_FAILURE);
					}					
				}
			}
		} else {
			$accountList = JdAccount::model()->getAbleAccountList();
			foreach ($accountList as $accountInfo) {
				MHelper::runThreadSOCKET('/'.$this->route.'/account_id/' . $accountInfo['id']. "/type/" . $type . "/day/" . $day);
				sleep(1);
			}
		}
	}

	/**
	 * @desc 单个订单拉取
	 * @link /jd/jdorder/getorder/account_id/1/order_id/####
	 */
	public function actionGetorder() {
		$accountID = Yii::app()->request->getParam('account_id');
		$orderID = Yii::app()->request->getParam('order_id');
		$type = Yii::app()->request->getParam('type');
		$msg = "";
		if($accountID && $orderID){
			$jdOrderModel = new JdOrder();
			$jdOrderModel->setAccountID($accountID);
			$orderDetails = $jdOrderModel->getOrderDetialByOrderIDs($orderID);
			$completeStatus = Order::COMPLETE_STATUS_DEFAULT;
			//$completeStatus = NULL
			if(!$orderDetails){
				$msg .= "<br/> 获取为空：".$jdOrderModel->getErrorMessage();
			}else{
				foreach ($orderDetails as $orderDetail) {
					$flag = $jdOrderModel->saveOrderInfo($orderDetail, $completeStatus);
					$msg  .= " OrderID：{$orderDetail->orderId} " . $jdOrderModel->getErrorMessage()." ";
					if($flag){
						$msg .= " success！！<br/>";
					}else{
						$msg .= " failure!! <br/>";
					}
				}
			}
		}
		$accountList = JdAccount::model()->getAccountPairs();
		$this->render("downjdorder", array('accountList'=>$accountList, 'message'=>$msg, "order_id"=>$orderID, "account_id"=>$accountID, "type"=>$type));
	}	

	/**
	 * 获取单个订单信息
	 * @link /jd/jdorder/getordertest/account_id/1/order_id/45884153050
	 */
	public function actionGetordertest() {
		$accountID = Yii::app()->request->getParam('account_id');
		$orderID = Yii::app()->request->getParam('order_id');
		if($accountID && $orderID){
			$jdOrderModel = new JdOrder();
			$jdOrderModel->setAccountID($accountID);
			$orderDetails = $jdOrderModel->getOrderDetialByOrderIDs($orderID);
			MHelper::printvar($orderDetails);
		}
		die('ok');
	}	
	
	//上传追踪号
	public function actionAutouploadtracknumber() {
		$limit = Yii::app()->request->getParam('limit');
		if (!empty($limit))
			$limit = (int)$limit;
		else
			$limit = 100;
		$consignDate = date('Y-m-d',strtotime('-15 days'));
    	$packageInfos = OrderPackage::model()->getJdWaitingUploadPackages(null, null, $limit);
    	$orderTrackingNumbers = array();
    	foreach( $packageInfos as $key => $val ){
    		//判断是否要上传
    		if($val['track_num'] == ''){//没有单号
    				continue;
    		}
    		
    		//获取包裹明细
    		$packageDetail = OrderPackageDetail::model()->getPackageDetailByPackageId( $val['package_id'],'order_detail_id,order_id' );
    		//循环明细
    		foreach ($packageDetail as $detail){
    			//获取订单信息
    			$orderInfo = Order::model()->getInfoByOrderId( $detail['order_id']);
    			if (empty($orderInfo)) continue;
    			$orderTrackingNumbers[$orderInfo['platform_order_id']] = array(
    				'account_id' => $orderInfo['account_id'],
    				'tracking_number' => $val['track_num'],
    				'package_id' => $val['package_id'],
    			);
    		}
    	}
    	//上传追踪号
    	foreach ($orderTrackingNumbers as $orderID => $row) {
    		$accountID = $row['account_id'];
    		$shippedData = array(
    			'order_id' => $orderID,
    			'tracking_number' => $row['tracking_number'],
    		);
    		$jdLog = new JdLog();
    		$logID = $jdLog->prepareLog($accountID,JdOrder::EVENT_NAME_UPLOAD_TN);
    		if( $logID ){
    			//1.检查账号是否在提交发货确认
    			$checkRunning = $jdLog->checkRunning($accountID, JdOrder::EVENT_NAME_UPLOAD_TN);
    			if( !$checkRunning ){
    				$jdLog->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
    			}else{
    				//2.准备拉取日志信息
    				//插入本次log参数日志(用来记录请求的参数)
    				$eventLog = $jdLog->saveEventLog(JdOrder::EVENT_NAME_UPLOAD_TN, array(
    						'log_id'        => $logID,
    						'account_id'    => $accountID,
    						'start_time'    => date('Y-m-d H:i:s'),
    						'end_time'      => date('Y-m-d H:i:s'),
    				));
    				//设置日志为正在运行
    				$jdLog->setRunning($logID);
    				//3.上传追踪号
    				$jdOrderModel = new JdOrder();
    				$jdOrderModel->setAccountID($accountID);//设置账号
    				$flag = $jdOrderModel->setOrderShipped( $shippedData );//上传
    				//4.更新日志信息
    				if( $flag ){
    					//5.更新包裹上传跟踪号标识
    					UebModel::model('OrderPackage')->updateAll(array('is_confirm_shiped'=>1),' is_confirm_shiped=0 and package_id in("'.$row['package_id'].'")');
    					$jdLog->setSuccess($logID);
    					$jdLog->saveEventStatus(JdOrder::EVENT_NAME_UPLOAD_TN, $eventLog, JdLog::STATUS_SUCCESS);
    				}else{
    					$jdLog->setFailure($logID, $jdOrderModel->getErrorMessage());
    					$jdLog->saveEventStatus(JdOrder::EVENT_NAME_UPLOAD_TN, $eventLog, JdLog::STATUS_FAILURE);
    				}
    			}   			
    		}
    	}		
	}
	
	//临时上传追京东踪号
	public function actionAutouploadtracknumbertemp() {
		$limit = Yii::app()->request->getParam('limit');
		if (!empty($limit))
			$limit = (int)$limit;
		else
			$limit = 100;
		$consignDate = date('Y-m-d',strtotime('-15 days'));
		$packageInfos = OrderPackage::model()->getJdWaitingUploadPackagesTemp($limit);
		//print_r($packageInfos);exit;
		foreach( $packageInfos as $key => $val ){
			//判断是否要上传
			if($val['track_num'] == ''){//没有单号
				continue;
			}
			
			//$accountID = $val['account_id'];
			$accountID = 1;
			$orderInfo = Order::model()->getInfoByOrderId($val['order_id']);
			if (empty($orderInfo)) continue;
			//print_r($orderInfo);exit;
			$shippedData = array(
					'order_id' => $orderInfo['platform_order_id'],
					'tracking_number' => $val['track_num'],
			);
			//print_r($shippedData);
			$jdLog = new JdLog();
			$logID = $jdLog->prepareLog($accountID,JdOrder::EVENT_NAME_UPLOAD_TN);
			if( $logID ){
				//1.检查账号是否在提交发货确认
				$checkRunning = $jdLog->checkRunning($accountID, JdOrder::EVENT_NAME_UPLOAD_TN);
				if( !$checkRunning ){
					$jdLog->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
				}else{
					//2.准备拉取日志信息
					//插入本次log参数日志(用来记录请求的参数)
					$eventLog = $jdLog->saveEventLog(JdOrder::EVENT_NAME_UPLOAD_TN, array(
							'log_id'        => $logID,
							'account_id'    => $accountID,
							'start_time'    => date('Y-m-d H:i:s'),
							'end_time'      => date('Y-m-d H:i:s'),
					));
					//设置日志为正在运行
					$jdLog->setRunning($logID);
					//3.拉取订单
					$jdOrderModel = new JdOrder();
					$jdOrderModel->setAccountID($accountID);//设置账号
					$flag = $jdOrderModel->setOrderShipped( $shippedData );//上传
					//4.更新日志信息
					if( $flag ){
						//5.更新包裹上传跟踪号标识
						UebModel::model('OrderPackage')->updatePrepareMatchData(array('is_confirm_shiped'=>1, 'confirm_shiped_time' => date('Y-m-d H:i:s')),'is_confirm_shiped=0 and id in("'.$val['id'].'")');
						$jdLog->setSuccess($logID);
						$jdLog->saveEventStatus(JdOrder::EVENT_NAME_UPLOAD_TN, $eventLog, JdLog::STATUS_SUCCESS);
					}else{
						$jdLog->setFailure($logID, $jdOrderModel->getErrorMessage());
						$jdLog->saveEventStatus(JdOrder::EVENT_NAME_UPLOAD_TN, $eventLog, JdLog::STATUS_FAILURE);
					}
				}
			}	
		}
	}	

	//临时上传追京东踪号
	public function actionAutouploadtracknumberfromexcel() {
		set_time_limit(0);
		$file = 'd:/wamp/www/2.csv';
		$fp = fopen($file, 'r');
		$accountID = 1;
		$count = 1;
		while (!feof($fp)) {
			echo '====================================' . "<br />";
			$rows = fgetcsv($fp, 1024);
			$orderID = $rows[0];
			$trackingNumber = $rows[1];
			echo '订单号：' . $orderID . "<br />";
			echo '追踪号：' . $trackingNumber . "<br />";
			$shippedData = array(
				'order_id' => $orderID,
				'tracking_number' => $trackingNumber,
			);
			$jdOrderModel = new JdOrder();
			$jdOrderModel->setAccountID($accountID);//设置账号
			$flag = $jdOrderModel->setOrderShipped( $shippedData );//上传
			if (!$flag) {
				echo  '上传失败：' . $jdOrderModel->getErrorMessage() . "<br />";
			} else {
				echo '上传成功' . "<br />";
			}
			$count++;	
		}
	}	

	public function actionUpdateplatformtracknumber() {
		ini_set('display_errors', true);
		error_reporting(E_ALL);
		$file = 'order.csv';
		$fp = fopen($file, 'r');
		while (!feof($fp)) {
			$rows = fgetcsv($fp, 1024);
			$platformOrderID = trim($rows[0]);
			if (empty($platformOrderID)) continue;
			//查找平台订单对应的订单号
			$orderID = Order::model()->getDbConnection()->createCommand()
				->from("ueb_order")
				->select("order_id")
				->where("platform_code = :platform_code", array('platform_code' => Platform::CODE_JD))
				->andWhere("platform_order_id = :order_id", array('order_id' => $platformOrderID))
				->queryScalar();
			if (empty($orderID)) {
				echo '<font color="red">' . $platformOrderID . ' 更新失败, ' . '找不到对应订单' . '</font><br />';
				continue;
			}
			//查找对应的包裹号
			$command = OrderPackage::model()->getDbConnection()->createCommand()
				->from("ueb_order_package as a")
				->select("a.package_id, a.track_num, a.track_num2, a.is_confirm_shiped")
				->join("ueb_order_package_detail as b", "a.package_id = b.package_id")
				->where("b.platform_code = '" . Platform::CODE_JD . "'")
				->andWhere("b.order_id = '" . $orderID . "'")
				->order("a.package_id DESC");
			$command->distinct = "a.package_id";
			$packageInfo = $command->queryRow();
			if (empty($packageInfo)) {
				echo '<font color="red">' . $platformOrderID . ' 更新失败, ' . '找不到对应包裹' . '</font><br />';
				continue;
			}
			$trackingNumber = !empty($packageInfo['track_num2']) ? $packageInfo['track_num2'] : $packageInfo['track_num'];
			if (empty($trackingNumber)) {
				echo '<font color="red">' . $platformOrderID . ' 更新失败, ' . '对应包裹追踪号为空' . '</font><br />';
				continue;				
			}
			if ($packageInfo['is_confirm_shiped'] == 0) {
				echo '<font color="red">' . $platformOrderID . ' 更新失败, ' . '还没确认到平台不需要更新' . '</font><br />';
				continue;				
			}
			$notes = 'Sorry for the mistake, the correct tracking number is ' . $trackingNumber;
			$JdOrderModel = new JdOrder();
			$JdOrderModel->setAccountID(1);
			$flag = $JdOrderModel->updateJdOrderNotes($platformOrderID, $notes);
			if (!$flag) {
				echo '<font color="red">' . $platformOrderID . ' 更新失败, ' . $JdOrderModel->getErrorMessage() . '</font><br />';
				continue;
			} else {
				echo '<font color="green">' . $platformOrderID . ' 更新成功</font><br />';				
			}
		}
		fclose($fp);
	}

	public function actionUpdateplatformtracknumber1() {
		$orderList = OrderPackage::model()->getDbConnection()->createCommand()
			->select("order_id, track_num")
			->from("ueb_order_track_num_prepare_match")
			->where("platform_code = '" . Platform::CODE_JD . "'")
			->andWhere("is_match = 1")
			->queryAll();
		if (empty($orderList)) exit('No Record');
		foreach ($orderList as $rows) {
			$orderID = $rows['order_id'];
			$oldTrackingNumber = $rows['track_num'];
 			echo 'orderID => ' . $orderID . "<br />";
			echo 'oldTrackingNumber => ' . $oldTrackingNumber . "<br />";
			//查找平台订单对应的订单号
			$platformOrderID = Order::model()->getDbConnection()->createCommand()
			->from("ueb_order")
			->select("platform_order_id")
			->where("platform_code = :platform_code", array('platform_code' => Platform::CODE_JD))
			->andWhere("order_id = :order_id", array('order_id' => $orderID))
			->queryScalar();
			if (empty($platformOrderID)) {
				echo '<font color="red">' . $orderID . ' => ' . $platformOrderID . ' 更新失败, ' . '找不到对应订单' . '</font><br />';
				continue;
			}
 			echo 'platformOrderID => ' . $platformOrderID . "<br />";
			//查找对应的包裹号
			$command = OrderPackage::model()->getDbConnection()->createCommand()
			->from("ueb_order_package as a")
			->select("a.package_id, a.track_num, a.track_num2, a.is_confirm_shiped")
			->join("ueb_order_package_detail as b", "a.package_id = b.package_id")
			->where("b.platform_code = '" . Platform::CODE_JD . "'")
			->andWhere("b.order_id = '" . $orderID . "'")
			->order("a.package_id DESC");
			$command->distinct = "a.package_id";
			$packageInfo = $command->queryRow();
			if (empty($packageInfo)) {
				echo '<font color="red">' . $orderID . ' => ' . $platformOrderID . ' 更新失败, ' . '找不到对应包裹' . '</font><br />';
				continue;
			}
			$trackingNumber = !empty($packageInfo['track_num2']) ? $packageInfo['track_num2'] : $packageInfo['track_num'];
			if (empty($trackingNumber)) {
				echo '<font color="red">' . $orderID . ' => ' . $platformOrderID . ' 更新失败, ' . '对应包裹追踪号为空' . '</font><br />';
				continue;
			}
			if ($packageInfo['is_confirm_shiped'] == 0) {
				echo '<font color="red">' . $orderID . ' => ' . $platformOrderID . ' 更新失败, ' . '还没确认到平台不需要更新' . '</font><br />';
				continue;
			}
			echo 'actualTrackingNumber1 => ' . $packageInfo['track_num'] . "<br />";
			echo 'actualTrackingNumber2 => ' . $packageInfo['track_num2'] . "<br />";
			//如果预先匹配的追踪号和实际发货包裹追踪号不同，则给订单加备注
			if ($oldTrackingNumber != $packageInfo['track_num']) {
				$notes = 'Sorry for the mistake, the correct tracking number is ' . $trackingNumber;
				echo $notes . "<br />";
				$JdOrderModel = new JdOrder();
				$JdOrderModel->setAccountID(1);
				$flag = $JdOrderModel->updateJdOrderNotes($platformOrderID, $notes);
 				if (!$flag) {
					echo '<font color="red">' . $orderID . ' => ' . $platformOrderID . ' 更新失败, ' . $JdOrderModel->getErrorMessage() . '</font><br />';
					continue;
				} else {
					echo '<font color="green">' . $orderID . ' => ' . $platformOrderID . ' 更新成功</font><br />';
				}
			}
			echo '------------------------------------------' . "<br />";
		}
		exit('DONE');
	}	
	
	//临时查找11月份漏掉的订单
	public function actionFindlostorder() {
		ini_set('display_errors', true);
		error_reporting(E_ALL);
		$timeArr = array(
			'start_time' => '2015-11-01 00:00:01',
			'end_time' => '2015-11-30 23:59:59',
		);
		$accountID = 1;
		$request = new OrderSearchRequest();
		$request->setStartDate($timeArr['start_time']);
		$request->setEndDate($timeArr['end_time']);
		$request->setOrderStatus(OrderSearchRequest::ORDER_STATUS_WAIT_DELIVERY);
		$page = $totalPage = 1;
		$orderInfoRequest = new GetOrderInfoByIDRequest();
		$outputFile = UPLOAD_DIR . 'orderlist.csv';
		$fp = fopen($outputFile, 'w');
		while( $page <= $totalPage ){
			$startRow = ($page - 1) * OrderSearchRequest::ORDER_NUMBER_PER_PAGE;
			$request->setStartRow($startRow);	//设置从多少条开始取
			$response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
			if ($request->getIfSuccess()) {
				$orderIDs = $response->jingdong_ept_order_getorderIdsbyquery_responce->getorderidsbyquery_result->orderIds;
				if (empty($orderIDs)) {
					exit('No Order!');
				}
				$total = $response->jingdong_ept_order_getorderIdsbyquery_responce->getorderidsbyquery_result->totalItem;
				$totalPage = ceil($total / OrderSearchRequest::ORDER_NUMBER_PER_PAGE);
				foreach ($orderIDs as $orderID) {
					//检查订单是否存在
					$orderInfo = Order::model()->getOrderInfoByPlatformOrderID($orderID, Platform::CODE_JD);
					if (empty($orderInfo)) {
						fputcsv($fp, array($orderID, '1'));
					} else {
						fputcsv($fp, array($orderID, '0'));
					}
				}
				$page++;
			} else {
				exit($this->_errorMsg);		
			}
		}
		fclose($fp);
		exit('DONE');
	}

}