<?php
/**
 * @desc Ebay订单相关
 * @author Gordon
 * @since 2015-06-02
 */
class EbayorderController extends UebController {
	
	/**
	 * 访问过滤配置
	 *
	 * @see CController::accessRules()
	 */
	public function accessRules() {
		return array (
				array (
						'allow',
						'users' => array (
								'*' 
						),
						'actions' => array (
								'getorderids',//new
								'checkorderids',//new
								'downbycondition',//new
								'downorders',//new
								'getpaypaltransaction',//new
								'getpaypaltransactionofecheck',//new
								'syncorder',//new
								'modtransaction',//new
								'confirmshipped',
								'uploadtracknum',
						) 
				) 
		);
	}

	/**
	 * 获取平台订单id
	 * @author yangsh
	 * @since 2016-06-08
	 * @link /ebay/ebayorder/getorderids
	 *       /ebay/ebayorder/getorderids/account_id/11
	 */
	public function actionGetorderids() {
		set_time_limit ( 1800 );
		ini_set ( 'display_errors', true );
		error_reporting( E_ALL & ~E_STRICT );
		
		$accountId 	= trim(Yii::app ()->request->getParam ( 'account_id', ''));//13

		if ($accountId != '') {
			$path  = 'ebay/getOrderIds/'.date("Ymd").'/'.$accountId;
			//每次抓取订单id前检查是否有下载订单超时的记录，如果有则设置成待下载状态
			$ebayGetorder = EbayGetorder::model();
			$runningRecords = $ebayGetorder->checkRunning($accountId);
		    if (!empty($runningRecords)) {
		    	$ebayOrderDetail = EbayOrderDetail::model();
	            foreach ($runningRecords as $value) {
	            	$platformOrderID = $value['platform_order_id'];
	            	if ($ebayOrderDetail->checkOrderLineItemID($platformOrderID)) {
	            		$ebayGetorder->setFinish($platformOrderID);
	            	} else {
	            		$ebayGetorder->setPending($value['id']);
	            	}
	            }
	        }
	        //开始抓取order_id
			$model = new EbayOrderDownLoad ();
			$model ->setAccountID($accountId)
				   ->setOrderStatus('All');//所有类型订单

			//get timeArr
			$timeArr = $model->setTimeArr()->getTimeArr();

			//prepareLog
			$ebayLogModel = new EbayLog ();
			$logID = $ebayLogModel->prepareLog ( $accountId, EbayOrderDownLoad::EVENT_NAME );
			if (!$logID) {
				echo 'Insert prepareLog failure';
				Yii::app ()->end();
			}
			
			// 1.检查账号是否可拉取订单
			$checkRunning = $ebayLogModel->checkRunning ( $accountId, EbayOrderDownLoad::EVENT_NAME );
			if (! $checkRunning ) {
				$ebayLogModel->setFailure ( $logID, Yii::t ( 'system', 'There Exists An Active Event' ) );
				echo 'There Exists An Active Event ';
				Yii::app ()->end();
			}

			// 2.插入本次log参数日志(用来记录请求的参数)
			$eventLog = $ebayLogModel->saveEventLog ( EbayOrderDownLoad::EVENT_NAME, array (
				'log_id'     => $logID,
				'account_id' => $accountId,
				'start_time' => $timeArr['start_time'],//UTC时间
				'end_time'   => $timeArr['end_time'], //UTC时间
			) );

			// 3.设置日志为正在运行
			$ebayLogModel->setRunning ( $logID );

			// 4. 设置日志编号并开始抓订单id
			$isOk = $model->setLogID($logID)
			              ->startGetOrderIds(1);//1:ModTime 2:CreateTime

			////更新日志信息
			$flag = $isOk ? 'Success' : 'Failure';
			if ( $isOk ) {
				$ebayLogModel->setSuccess ( $logID, 'Done' );
				$ebayLogModel->saveEventStatus ( EbayOrderDownLoad::EVENT_NAME, $eventLog, EbayLog::STATUS_SUCCESS );
			} else {
				$ebayLogModel->setFailure ( $logID, $model->getExceptionMessage() );
				$ebayLogModel->saveEventStatus ( EbayOrderDownLoad::EVENT_NAME, $eventLog, EbayLog::STATUS_FAILURE );				
			}

			//记录日志 
            $result = date("Y-m-d H:i:s").'###'.json_encode($_REQUEST).'==='.$flag.'==='.$model->getExceptionMessage();
            echo $result."\r\n<br>";
            //MHelper::writefilelog($path.'/result_'.$flag.'.log', $result."\r\n");	
		} else {
			//循环每个账号发送请求
            $accountList = EbayAccount::model()->getAbleAccountList();
            $accountIdArr = array();
            foreach ($accountList as $accountInfo) {
                $accountIdArr[] = $accountInfo['id'];
            }
            sort($accountIdArr,SORT_NUMERIC);
            $accountIdGroupData = MHelper::getGroupData($accountIdArr,10);
            foreach ($accountIdGroupData as $key => $idGroupData) {
                foreach ($idGroupData as $account_id) {
                    $url = Yii::app()->request->hostInfo.'/' . $this->route 
                    	. '/account_id/' . $account_id ;
                    echo $url." <br>\r\n";
                    MHelper::runThreadBySocket($url);
                }
                sleep(60);//每1分钟执行10个账号
            }
		}
		Yii::app ()->end('finish');
	}

	/**
	 * 补拉平台订单id
	 * @author yangsh
	 * @since 2016-07-26
	 * @link /ebay/ebayorder/checkorderids
	 *       /ebay/ebayorder/checkorderids/account_id/11
	 *       /ebay/ebayorder/checkorderids/account_id/11/day/3
	 *       /ebay/ebayorder/checkorderids/account_id/11/start_time/2016-06-08/end_time/2016-06-10 
	 */
	public function actionCheckorderids() {
		set_time_limit ( 3*3600 );
		ini_set ( 'display_errors', true );
		error_reporting( E_ALL & ~E_STRICT );
		
		$accountId      = trim(Yii::app ()->request->getParam ( 'account_id', ''));//13
		$day            = trim(Yii::app ()->request->getParam ( 'day', 3 )); // type为1时，day生效。默认补拉3天
		$startTime      = trim(Yii::app ()->request->getParam ( 'start_time', ''));//2016-06-03 14:30
		$endTime        = trim(Yii::app ()->request->getParam ( 'end_time', ''));//2016-06-06 14:30
		$status 		= trim(Yii::app ()->request->getParam ( 'status', 'All'));//默认All
		$mode 			= trim(Yii::app ()->request->getParam ( 'mode', 1));//1:ModTime 2:CreateTime
		$offtime 		= 8 * 3600;//时区,相差8小时

		if ($accountId != '') {
			$path  = 'ebay/getOrderIds/'.date("Ymd").'/'.$accountId;
			//每次抓取订单id前检查是否有下载订单超时的记录，如果有则设置成待下载状态
			$ebayGetorder = EbayGetorder::model();
			$runningRecords = $ebayGetorder->checkRunning($accountId);
		    if (!empty($runningRecords)) {
		    	$ebayOrderDetail = EbayOrderDetail::model();
	            foreach ($runningRecords as $value) {
	            	$platformOrderID = $value['platform_order_id'];
	            	if ($ebayOrderDetail->checkOrderLineItemID($platformOrderID)) {
	            		$ebayGetorder->setFinish($platformOrderID);
	            	} else {
	            		$ebayGetorder->setPending($value['id']);
	            	}
	            }
	        }

	        //开始抓取order_id
			$model = new EbayOrderDownLoad ();
			$model ->setAccountID($accountId)
				   ->setOrderStatus($status);

			$timeArr = array();
			if ($startTime != '' && $endTime != '') {
				$timeArr ['start_time'] = date('Y-m-d H:i:s', strtotime ( $startTime ) - $offtime );
				$timeArr ['end_time'] = date('Y-m-d H:i:s', strtotime ( $endTime ) - $offtime );
			} else {
				$timeArr ['start_time'] = date('Y-m-d H:i:s', time () - $offtime - $day * 86400 );
				$timeArr ['end_time'] = date('Y-m-d H:i:s', time () - $offtime );
			}

			//get timeArr
			$timeArr = $model->setTimeArr($timeArr)->getTimeArr();

			//prepareLog
			$ebayLogModel = new EbayLog ();
			$logID = $ebayLogModel->prepareLog ( $accountId, EbayOrderDownLoad::EVENT_NAME_CHECK );
			if (!$logID) {
				echo 'Insert prepareLog failure';
				Yii::app ()->end();
			}

			// 1.检查账号是否可拉取订单
			$checkRunning = $ebayLogModel->checkRunning ( $accountId, EbayOrderDownLoad::EVENT_NAME_CHECK );
			if (! $checkRunning ) {
				$ebayLogModel->setFailure ( $logID, Yii::t ( 'system', 'There Exists An Active Event' ) );
				echo 'There Exists An Active Event ';
				Yii::app ()->end();
			}

			// 3.设置日志为正在运行
			$ebayLogModel->setRunning ( $logID );

			// 4. 设置日志编号并开始抓订单id
			$isOk = $model->setLogID($logID)
			              ->startGetOrderIds($mode);

			////更新日志信息
			$flag = $isOk ? 'Success' : 'Failure';
			if ( $isOk ) {
				$ebayLogModel->setSuccess ( $logID, 'Done' );
			} else {
				$ebayLogModel->setFailure ( $logID, $model->getExceptionMessage() );
			}

			//记录日志 
            $result = date("Y-m-d H:i:s").'###'.json_encode($_REQUEST).'==='.$flag.'==='.$model->getExceptionMessage();
            echo $result."\r\n<br>";
            //MHelper::writefilelog($path.'/result_'.$flag.'.log', $result."\r\n");	

		} else {
			//循环每个账号发送一个拉listing的请求
            $accountList = EbayAccount::model()->getAbleAccountList();
            $accountIdArr = array();
            foreach ($accountList as $accountInfo) {
                $accountIdArr[] = $accountInfo['id'];
            }
            sort($accountIdArr,SORT_NUMERIC);
            $accountIdGroupData = MHelper::getGroupData($accountIdArr,5);
            foreach ($accountIdGroupData as $key => $idGroupData) {
                foreach ($idGroupData as $account_id) {
                    $url = Yii::app()->request->hostInfo.'/' . $this->route . '/mode/' . $mode . '/account_id/' . $account_id . "/day/" . $day . "/start_time/" . $startTime . "/end_time/" . $endTime. "/status/" . $status;
                    echo $url." <br>\r\n";
                    MHelper::runThreadBySocket($url);
                }
                sleep(300);//每5分钟执行5个账号
            }
		}
		Yii::app ()->end('finish');
	}

	/**
	 * 通过查询条件将查询结果按账号分组, 下载订单完整信息
	 * @author yangsh
	 * @since 2016-06-08
	 * @link /ebay/ebayorder/downbycondition
	 *       /ebay/ebayorder/downbycondition/account_id/11
	 *       /ebay/ebayorder/downbycondition/orderids/211149906010,231829248157-1321814650013,231966018184-0
	 *       /ebay/ebayorder/downbycondition/start_time/2016-06-08/end_time/2016-06-10
	 */
	public function actionDownbycondition() {
		set_time_limit ( 1200 );
		ini_set ( 'display_errors', true );
		error_reporting( E_ALL & ~E_STRICT );

		$accountId 	= trim(Yii::app ()->request->getParam ( 'account_id', ''));//13
		$orderIds 	= trim(Yii::app ()->request->getParam ( 'orderids', ''));

		//开始抓单
		$criterias = array();
		if (!empty($orderIds)) {
			$orderIdArr = explode(',',$orderIds);
			$criterias['orderIds'] = $orderIdArr;	
		}
		if ($accountId) {
			$criterias['accountId'] = $accountId;
		}
		$dataList = EbayGetorder::model()->getPendingRecordsByCriterias($criterias);
		if (empty($dataList)) {
			Yii::app ()->end('no orderid for grabing ebay order');
		}
		$total = 0;
		$accountIDs = array_keys($dataList);
		$accountIdGroupData = MHelper::getGroupData($accountIDs,10);
		foreach ($accountIdGroupData as $accountIdGroup) {
			foreach ($accountIdGroup as $account_id) {
				$orderIdGroupData = MHelper::getGroupData($dataList[$account_id],100);
				foreach ($orderIdGroupData as $orderIdArr) {
					$total  += count($orderIdArr);
					$url 	= Yii::app()->request->hostInfo.'/ebay/ebayorder/downorders';
					$post 	= array(
						'account_id'	=> $account_id,
						'orderids'		=> implode(',',$orderIdArr) 
					);
					MHelper::runThreadBySocket($url, $post, 0,'','',600,false);
				}
			}
			sleep(15);
		}
		echo $total. " OrderIds have already sent!<br>\r\n";
		Yii::app ()->end('finish');
	}

	/**
	 * 通过按账号id分组好的订单id, 下载订单完整信息
	 * @author yangsh
	 * @since 2016-06-08
	 * @link /ebay/ebayorder/downorders/account_id/64/orderids/211149906010,231829248157-1321814650013,231966018184-0
	 */
	public function actionDownorders() {
		set_time_limit ( 1200 );
		ini_set ( 'display_errors', true );
		error_reporting( E_ALL & ~E_STRICT );

		$accountId = trim(Yii::app ()->request->getParam ( 'account_id', ''));//
		$orderIds = trim(Yii::app ()->request->getParam ( 'orderids', ''));//211149906010,231829248157-1321814650013
		//参数验证
		$validateMsg = '';
		if (empty($accountId) || !preg_match('/^\d+$/',$accountId)) {
			$validateMsg .= 'account_id is invalid;';
		}
		if ($orderIds == '') {
			$validateMsg .= 'orderids is empty;';
		}
		if ($validateMsg != '') {
			Yii::app ()->end($validateMsg);
		}
		//开始拉单
		$path  = 'ebay/downOrders/'.date("Ymd").'/'.$accountId;
		$model = new EbayOrderDownLoad ();
		$isOk  = $model ->setAccountID($accountId)
						->setOrderID($orderIds)
						->startDownloadOrder();						
		//记录日志 
		$flag 	= $isOk ? 'Success' : 'Failure';
        $result = date("Y-m-d H:i:s").'###'.json_encode($_REQUEST).'==='.$flag.'==='.$model->getExceptionMessage();
        echo $result."\r\n<br>";
        //MHelper::writefilelog($path.'/result_'.$flag.'.log', $result."\r\n");	
		echo 'finish....';        
	}

	/**
	 * 抓取order for test
	 * @author yangsh
	 * @since 2016-06-08
	 * @link /ebay/ebayorder/getordertest/account_id/3/orderids/211149906010
	 */
	public function actionGetordertest() {
		$accountID = trim(Yii::app ()->request->getParam ( 'account_id', ''));//13
		$orderIds = trim(Yii::app ()->request->getParam ( 'orderids', ''));
		$isXml = Yii::app ()->request->getParam ( 'is_xml', false);
		if ($accountID == '' || $orderIds == '') {
			die('account_id or orderids is empty');
		}
		$orderIdArray = explode(',', $orderIds);
		$request = new GetOrdersRequest ();
		$request ->setAccount( $accountID )
				 ->setOrderIDArray($orderIdArray);			 
		while($request->_PageNumber <= $request->_TotalPage ) {
			$currentPage = $request->_PageNumber;
			$response 	 = $request->setIsXML($isXml)->setRequest ()->sendRequest ()->getResponse ();
			echo '<hr>';
			MHelper::printvar($response,false);
			$request->setTotalPage ( $response->PaginationResult->TotalNumberOfPages ); // 设置总页数
			$request->setPageNum ( $request->_PageNumber + 1 );
		}
		die('finish');
	}

	/**
	 * 抓取paypal交易信息
	 * @author yangsh
	 * @since 2016-06-08
	 * @link /ebay/ebayorder/getpaypaltransaction/account_id/3/debug/1
	 */
	public function actionGetpaypaltransaction() {
		set_time_limit ( 3600 );
		ini_set ( 'display_errors', true );
		error_reporting( E_ALL & ~E_STRICT );

		$transactionID 		= trim(Yii::app ()->request->getParam ('transaction_id'));
		$paypalAccount 		= trim(Yii::app ()->request->getParam ('paypal_account',''));
		$platformOrderID 	= trim(Yii::app ()->request->getParam ('platform_order_id',''));
		$accountIDs 		= trim(Yii::app ()->request->getParam ('account_id',''));//逗号分割

		//检查超时下载
		EbayOrderTransaction::model()->checkNoPaypalModifyTime();
        if ($accountIDs != '') {
        	$accountIDs = explode(',', $accountIDs);
        	foreach ($accountIDs as $accountID) {
	        	$model = EbayOrderTransaction::model();
				//获取待下载记录
				$orderTransactions = $model->setTransactionID($transactionID)
											->setPaypalAccount($paypalAccount)
											->setPlatformOrderID($platformOrderID)
											->setAccountID($accountID)
											->getNoPaypalTransactions();
				if (empty($orderTransactions)) {
					echo $accountID.' orderTransactions is empty';
					continue;
				}

        		//add log
                $logModel = new EbayLog();
                $logModel->getDbConnection()->createCommand()->insert(
                    $logModel->tableName(), array(
                        'account_id'    => $accountID,
                        'event'         => EbayLog::EVENT_DOWNTRANSACTIONS,
                        'start_time'    => date('Y-m-d H:i:s'),                         
                        'status'        => EbayLog::STATUS_SUCCESS,
                        'message'       => 'Total:'. count($orderTransactions),
                        'response_time' => date('Y-m-d H:i:s'),
                        'end_time'      => date('Y-m-d H:i:s'),
                        'create_user_id'=> intval(Yii::app()->user->id),
                    )
                );

				$data = array();
				$transactionIdArr = array();
				foreach ($orderTransactions as $transaction) {
					$transactionIdArr[] = $transaction['transaction_id'];
					$data[$transaction['paypal_account']][] = $transaction;
				}

				if (!empty($transactionIdArr)) {
					$groupData = MHelper::getGroupData($transactionIdArr, 200);
					foreach ($groupData as $transIdArr) {
						$model->updateModifyTime($transIdArr);
					}
				}

				foreach ($orderTransactions as $transaction) {
					$isOk = $model->savePaypalTransactionRecord($transaction);
					if (!empty($_REQUEST['debug'])) {
						$flag = $isOk ? 'success' : 'failure';
						echo $transaction['transaction_id'].'# '.$flag.'@@@'.$model->getExceptionMessage()."<br>";
					}
				}
        	}
        } else {
        	//循环每个账号发送一个拉listing的请求
        	$accountList = EbayOrderTransaction::model()->getNoPaypalsSellerAccountByOrder();
        	if (empty($accountList)) {
        		Yii::app()->end('No Data for download...');
        	}
        	$accountIdArr = array();
        	foreach ($accountList as $v) {
        		$accountIdArr[] = $v['seller_account_id'];
        	}
            $accountIdGroupData = MHelper::getGroupData($accountIdArr,5);
            foreach ($accountIdGroupData as $key => $idGroupData) {
            	foreach ($idGroupData as $account_id) {
					$url = Yii::app()->request->hostInfo.'/' . $this->route . '/account_id/' . $account_id ;
	                echo $url." <br>\r\n";
	                MHelper::runThreadBySocket($url);
            	}
            	sleep(60);
            }
        }
		Yii::app()->end('finish');
	}

	/**
	 * 抓取paypal echeck类型交易
	 * @author yangsh
	 * @since 2016-06-08
	 * @link /ebay/ebayorder/getpaypaltransactionofecheck/account_id/3/debug/1
	 */
	public function actionGetpaypaltransactionofecheck() {
		set_time_limit ( 3600 );
		ini_set ( 'display_errors', true );
		error_reporting( E_ALL ^ E_NOTICE );

		$transactionID 		= trim(Yii::app ()->request->getParam ('transaction_id'));
		$paypalAccount 		= trim(Yii::app ()->request->getParam ('paypal_account',''));
		$platformOrderID 	= trim(Yii::app ()->request->getParam ('platform_order_id',''));
		$accountID 			= trim(Yii::app ()->request->getParam ('account_id',''));

		//获取待下载记录
    	$model = EbayOrderTransaction::model();
		$orderTransactions 	= $model->setTransactionID($transactionID)
									->setPaypalAccount($paypalAccount)
									->setPlatformOrderID($platformOrderID)
									->setAccountID($accountID)
									->getNoPaypalTransactionsOfEcheck();
		if (empty($orderTransactions)) {
			Yii::app()->end('orderTransactions is empty');
		}

		//add log
        $logModel = new EbayLog();
        $virAccountID = 90000;
        $logModel->getDbConnection()->createCommand()->insert(
            $logModel->tableName(), array(
                'account_id'    => $virAccountID,
                'event'         => EbayLog::EVENT_DOWNTRANSACTIONS,
                'start_time'    => date('Y-m-d H:i:s'),                         
                'status'        => EbayLog::STATUS_SUCCESS,
                'message'       => 'Total:'. count($orderTransactions),
                'response_time' => date('Y-m-d H:i:s'),
                'end_time'      => date('Y-m-d H:i:s'),
                'create_user_id'=> intval(Yii::app()->user->id),
            )
        );

		foreach ($orderTransactions as $transaction) {
			$isOk = $model->savePaypalTransactionRecord($transaction);
			if (!empty($_REQUEST['debug'])) {
				$flag = $isOk ? 'success' : 'failure';
				echo $transaction['transaction_id'].'# '.$flag.'@@@'.$model->getExceptionMessage()."<br>";
			}
		}

		Yii::app()->end('finish');
	}

	/**
	 * 抓取paypal交易信息
	 * @author yangsh
	 * @since 2016-06-08
	 * @link /ebay/ebayorder/getpaypaltransactiontest/transaction_id/66U66041SP300100F/paypal_account/chinatera@gmail.com
	 *       /ebay/ebayorder/getpaypaltransactiontest/transaction_id/66U66041SP300100F/paypal_account_id/1
	 */
	public function actionGetpaypaltransactiontest() {
		set_time_limit(60);
		ini_set("display_errors", true);
		error_reporting( E_ALL & ~E_STRICT );

		$transactionID 		= trim(Yii::app ()->request->getParam ('transaction_id',''));
		$paypalAccountID 	= trim(Yii::app ()->request->getParam ('paypal_account_id',''));
		$paypalAccount 		= trim(Yii::app ()->request->getParam ('paypal_account',''));
		if ($transactionID == '') {
			die('transaction_id is empty');
		}
		if ($paypalAccountID != '' ) {
			$paypalTransaction  = PaypalAccount::model ()->getPaypalTransactionByTransactionID( $transactionID, Platform::CODE_EBAY, $paypalAccountID );
		}		
		if ( $paypalAccount != '') {
			$paypalTransaction  = PaypalAccount::model ()->getPaypalTransactionByCondition( $transactionID, Platform::CODE_EBAY, $paypalAccount );
		}	
		MHelper::printvar($paypalTransaction);
	}	

	/**
	 * 从中间库同步ebay订单到OMS系统
	 * @author yangsh
	 * @since 2016-08-25
	 * @link /ebay/ebayorder/syncorder/show_result/1
	 *       /ebay/ebayorder/syncorder/show_result/1/platform_order_id/171253269521-1560056006007
	 */
	public function actionSyncorder() {
		set_time_limit(600);
		ini_set("display_errors", true);
		error_reporting( E_ALL & ~E_STRICT );

		$platformOrderID = trim(Yii::app ()->request->getParam ( 'platform_order_id', ''));
		$showResult = trim(Yii::app ()->request->getParam ( 'show_result', 0));
		
		//订单同步
		$orderCount = 0;
        $logModel = new EbayLog();
        $eventName = EbayLog::EVENT_SYNCORDER;
        $virAccountID = 90000;//虚拟账号id
        $logID = $logModel->prepareLog($virAccountID,$eventName);
        if (!$logID) {
        	exit("Create LogID Failure!!!");
        }
        $checkRunning = $logModel->checkRunning($virAccountID, $eventName);
        if (!$checkRunning) {
            echo 'Exists An Active Event.<br>';
            $logModel->setFailure($logID, Yii::t('systems', 'Exists An Active Event'));
        }else {
            //设置日志为正在运行
            $logModel->setRunning($logID);
            //开始同步
            $count = EbayOrderMain::model()->syncOrderToOms($platformOrderID,$showResult);
            $orderCount += $count;
            //标识事件成功
            $logModel->setSuccess($logID,'Total:'.$orderCount);
        }
		Yii::app()->end('finish');
	}

	/**
	 * 设置订单已发货 
	 * @link /ebay/ebayorder/confirmshipped/limit/10/account_id/28
	 *
	 * @author wx
	 */
	public function actionConfirmShipped() {
		ini_set ( 'display_errors', true );
		error_reporting( E_ALL );
		// 获取近3天的已付款，未确认发货的订单
		$limit = Yii::app ()->request->getParam ( 'limit', '' );
		$payTimeStart = date ( 'Y-m-d H:i', strtotime ( '-72 hours' ) );
		$payTimeEnd = date ( 'Y-m-d H:i', strtotime ( '-12 hours' ) );
		$accountID = Yii::app ()->request->getParam ( 'account_id' );
		
		if ($accountID) {
			set_time_limit ( 7200 );
		} else {
			set_time_limit ( 3600 );
		}

		if ($accountID) {
			// 满足条件订单
			$orderInfos = Order::model ()->getEbayWaitingConfirmOrders ( $payTimeStart, $payTimeEnd, $accountID, $limit );
			
			// 排除已经上传过的
			$tmpOrderIds = array ();
			foreach ( $orderInfos as $key => $val ) {
				$tmpOrderIds [] = $val ['order_id'];
			}
			$orderIdArray = $this->splitByn ( $tmpOrderIds, 500 );
			$tmpMarkOrdIds = array ();
			foreach ( $orderIdArray as $key => $val ) {
				$tmpRet = EbayOrderMarkShippedLog::model ()->getInfoByOrderIds ( MHelper::simplode ( $val ), 'order_id' );
				foreach ( $tmpRet as $v ) {
					$tmpMarkOrdIds [] = $v ['order_id'];
				}
			}
			// 记录此次需要上传的订单
			foreach ( $orderInfos as $key => $val ) {
				if (in_array ( $val ['order_id'], $tmpMarkOrdIds )) {
					unset ( $orderInfos [$key] );
					continue;
				}
				$markOrderData = array (
						'status' => 0,
						'order_id' => $val ['order_id'],
						'paytime' => $val ['paytime'],
						'account_id' => $accountID 
				);
				$markModel = new EbayOrderMarkShippedLog ();
				$markModel->saveNewData ( $markOrderData );
			}
			$ebayLog = new EbayLog ();
			$logID = $ebayLog->prepareLog ( $accountID, EbayOrder::EVENT_NAME_UPLOAD_TN );
			if ($logID) {
				// 1.检查账号是否在提交发货确认
				$checkRunning = $ebayLog->checkRunning ( $accountID, EbayOrder::EVENT_NAME_UPLOAD_TN );
				if (! $checkRunning) {
					$ebayLog->setFailure ( $logID, Yii::t ( 'systems', 'There Exists An Active Event' ) );
				} else {
					// 设置日志为正在运行
					$ebayLog->setRunning ( $logID );
					// 查询要上传的订单 开始上传
					$waitingMarkOrders = EbayOrderMarkShippedLog::model ()->getWaitingMarkShipOrder ( $accountID, $payTimeStart, $payTimeEnd );
					$isSuccess = true;
					$errorMessage = '';
					foreach ( $waitingMarkOrders as $key => $val ) {
						// 有gift的不上传
						$retGift = OrderGiftLog::model ()->checkGiftIsOrNot ( $val ['order_id'] );
						if ($retGift) {
							$updateData = array (
									'id' => $val ['id'],
									'status' => EbayOrderMarkShippedLog::STATUS_HAS_GIFT,
									'errormsg' => 'There exists a gift package, need not upload',
									'upload_time' => date ( 'Y-m-d H:i:s' ) 
							);
							EbayOrderMarkShippedLog::model ()->updateData ( $updateData );
							continue;
						} else {
							// 有eub包裹的不上传
							$eubPakcages = OrderPackage::model ()->getEbayEubPackages ( $val ['order_id'], 't.package_id' );
							if ($eubPakcages) {
								$updateData = array (
										'id' => $val ['id'],
										'status' => EbayOrderMarkShippedLog::STATUS_HAS_GIFT,
										'errormsg' => 'There exists the EUB package, need not upload',
										'upload_time' => date ( 'Y-m-d H:i:s' ) 
								);
								EbayOrderMarkShippedLog::model ()->updateData ( $updateData );
								continue;
							}
						}
						$orderInfo = Order::model ()->getInfoByOrderId ( $val ['order_id'], 'account_id,platform_order_id' );
						if (empty ( $orderInfo ))
							continue;
						$shippedData = array (
								'platform_order_id' => $orderInfo ['platform_order_id'] 
						);
						// 插入本次log参数日志(用来记录请求的参数)
						$eventLog = $ebayLog->saveEventLog ( EbayOrder::EVENT_NAME_UPLOAD_TN, array (
								'log_id' => $logID,
								'account_id' => $accountID,
								'start_time' => date ( 'Y-m-d H:i:s' ),
								'end_time' => date ( 'Y-m-d H:i:s' ),
								'order_id' => $val ['order_id'] 
						) );
						
						// 3.拉取订单
						$ebayOrderModel = new EbayOrder ();
						$ebayOrderModel->setAccountID ( $accountID ); // 设置账号
						$flag = $ebayOrderModel->setOrderShipped ( $shippedData ); // 上传
						                                                           // 4.更新日志信息
						if ($flag) {
							// 5.上传成功更新记录表
							$updateData = array (
									'id' => $val ['id'],
									'status' => EbayOrderMarkShippedLog::STATUS_SUCCESS,
									'errormsg' => '',
									'upload_time' => date ( 'Y-m-d H:i:s' ) 
							);
							EbayOrderMarkShippedLog::model ()->updateData ( $updateData );
							$ebayLog->saveEventStatus ( EbayOrder::EVENT_NAME_UPLOAD_TN, $eventLog, EbayLog::STATUS_SUCCESS );
						} else {
							$updateData = array (
									'id' => $val ['id'],
									'status' => EbayOrderMarkShippedLog::STATUS_FAILURE,
									'errormsg' => $ebayOrderModel->getExceptionMessage (),
									'upload_time' => date ( 'Y-m-d H:i:s' ) 
							);
							EbayOrderMarkShippedLog::model ()->updateData ( $updateData );
							$ebayLog->saveEventStatus ( EbayOrder::EVENT_NAME_UPLOAD_TN, $eventLog, EbayLog::STATUS_FAILURE );
							$errorMessage .= $ebayOrderModel->getExceptionMessage ();
						}
						$isSuccess = $isSuccess && $flag;
					}
					if ($isSuccess) {
						$ebayLog->setSuccess ( $logID );
					} else {
						$ebayLog->setFailure ( $logID, $errorMessage );
					}
				}
			}
		} else {
			$ebayAccounts = EbayAccount::model ()->getAbleAccountList ();
			foreach ( $ebayAccounts as $account ) {
				MHelper::runThreadSOCKET ( '/' . $this->route . '/account_id/' . $account ['id'] );
				sleep ( 10 );
			}
		}
	}
	
	/**
	 * 上传跟踪号 
	 * @link /ebay/ebayorder/uploadtracknum/limit/1/package_id/
	 *
	 * @author wx
	 */
	public function actionUploadTrackNum() {
		set_time_limit ( 7200 );
		ini_set ( 'display_errors', true );
		error_reporting( E_ALL );

		// 获取近3天的已付款，未确认发货的订单
		$limit = Yii::app ()->request->getParam ( 'limit', '' );
		$packageId = Yii::app ()->request->getParam ( 'package_id', '' );
		$consignDate = date ( 'Y-m-d', strtotime ( '-15 days' ) );
		$carrierArr = array (
				'ems' => 'EMS',
				'ghxb' => "China Post",
				'other' => 'Other' 
		);
		
		// 获取要上传的包裹 包含之前提前发货的订单。
		$packageInfos = OrderPackage::model ()->getEbayWaitingUploadPackages ( $consignDate, $packageId, $limit );
		
		//MHelper::printvar($packageInfos,false);//fortest

		$tmpOrderIds = array ();
		foreach ( $packageInfos as $key => $val ) {
			if (! in_array ( $val ['order_id'], $tmpOrderIds )) {
				$tmpOrderIds [] = $val ['order_id'];
			}
		}
		
		// 列表字符串有限制，每次查询限制在500以内
		$ordArr = $this->splitByn ( $tmpOrderIds, 500 );
		// var_dump($ordArr);exit;
		unset ( $tmpOrderIds );
		
		// 循环查出订单,item相关信息，并采集accountid
		$data = array ();
		$orderArray = array ();
		foreach ( $ordArr as $val ) {
			$orderIdStr = "'" . implode ( "','", $val ) . "'";
			$orderList = Order::model ()->getInfoListByOrderIds ( $orderIdStr, 'o.order_id,o.account_id,o.platform_order_id,o.paytime,o.currency,d.item_id,d.id as order_detail_id', Platform::CODE_EBAY );
			// var_dump($orderList);exit;
			foreach ( $orderList as $k => $v ) {
				if (! in_array ( $v ['account_id'], array_keys ( $data ) )) {
					$data [$v ['account_id']] = array ();
				}
				$orderArray [$v ['order_id']] [$v ['order_detail_id']] ['account_id'] = $v ['account_id'];
				$orderArray [$v ['order_id']] [$v ['order_detail_id']] ['platform_order_id'] = $v ['platform_order_id'];
				$orderArray [$v ['order_id']] [$v ['order_detail_id']] ['paytime'] = $v ['paytime'];
				$orderArray [$v ['order_id']] [$v ['order_detail_id']] ['item_id'] = $v ['item_id'];
			}
		}
		// var_dump($orderArray);
		
		// 按照每个账号来整理数据
		foreach ( $packageInfos as $key => $val ) {
			$order_detail = $orderArray [$val ['order_id']] [$val ['order_detail_id']];
			$carrierCode = '';
			if ($carrierArr [$val ['real_ship_type']]) {
				$carrierCode = LogisticsPlatformCarrier::model ()->getCarrierByShipCode ( ! empty ( $val ['real_ship_type'] ) ? strtolower ( $val ['real_ship_type'] ) : $val ['ship_code'], Platform::CODE_EBAY );
			} else {
				$carrierCode = $carrierArr ['ghxb'];
			}
			
			if (! $carrierCode)
				continue;
			$tmp = array (
					'order_id' => $val ['order_id'],
					'platform_order_id' => $order_detail ['platform_order_id'],
					'order_detail_id' => $val ['order_detail_id'],
					'package_id' => $val ['package_id'],
					'carrier_name' => $carrierCode,
					'real_ship_type' => ! empty ( $val ['real_ship_type'] ) ? $val ['real_ship_type'] : $val ['ship_code'],
					'tracking_number' => $val ['track_num'],
					'ship_country_name' => $val ['ship_country_name'],
					'item_id' => $order_detail ['item_id'] 
			);
			$data [$order_detail ['account_id']] [$val ['package_id']] [] = $tmp;
		}
		
		// var_dump($data);exit;
		foreach ( $data as $key => $val ) { // 循环账号
			$accountID = $key;
			$ebayLog = new EbayLog ();
			$logID = $ebayLog->prepareLog ( $accountID, EbayOrder::EVENT_NAME_UPLOAD_TN );
			if ($logID) {
				// 1.检查账号是否上传跟踪号
				$checkRunning = $ebayLog->checkRunning ( $accountID, EbayOrder::EVENT_NAME_UPLOAD_TN );
				if (! $checkRunning) {
					$ebayLog->setFailure ( $logID, Yii::t ( 'systems', 'There Exists An Active Event' ) );
				} else {
					// 设置日志为正在运行
					$ebayLog->setRunning ( $logID );
					$isSuccess = true;
					foreach ( $val as $pkId => $vv ) { // 循环包裹
						$isResult = true;
						foreach ( $vv as $vvItem ) { // 循环订单明细
							$eventLog = $ebayLog->saveEventLog ( EbayOrder::EVENT_NAME_UPLOAD_TN, array (
									'log_id' => $logID,
									'account_id' => intval($accountID),
									'start_time' => date ( 'Y-m-d H:i:s' ),
									'end_time' => date ( 'Y-m-d H:i:s' ),
									'item_id' => $vvItem ['item_id'],
									'order_id' => $vvItem ['order_id'] 
							) );
							// 获取订单详情
							$orderDetail = OrderDetail::model ()->getOrderDetailByOrderDetailId ( $vvItem ['order_detail_id'], 'item_id,transaction_id' );
							if (! $orderDetail ['item_id']) {
								continue;
							}
							// 获取订单信息
							$orderInfo = Order::model ()->getInfoByOrderId ( $vvItem ['order_id'], 'account_id' );
							if (empty ( $orderInfo ))
								continue;
								// 查出是否存在另外一个包裹用的eub方式
							$check = OrderPackage::model ()->getEbayEubPackagesByOrderId ( $vvItem ['order_id'], $pkId );
							// 有gift
							$retGift = OrderGiftLog::model ()->checkGiftIsOrNot ( $vvItem ['order_id'] );
							if ($check && $retGift) { // 存在另外一个包裹用的eub方式,并且订单有礼品记录，则不上传跟踪号，防止上传的挂号tn覆盖掉eub的tn。
								continue;
							}
							$shippedData = array ();
							
							if ($vvItem ['real_ship_type'] == 'other') {
								$vvItem ['tracking_number'] = $vvItem ['order_detail_id'];
							}
							
							$accountID = $orderInfo ['account_id'];
							$shippedData = array (
									'item_id' => $orderDetail ['item_id'],
									'transaction_id' => $orderDetail ['transaction_id'],
									'track_number' => $vvItem ['tracking_number'],
									'shipped_carrier' => $vvItem ['carrier_name'] 
							);
							$ebayOrderModel = new EbayOrder ();
							$ebayOrderModel->setAccountID ( $accountID ); // 设置账号
							$flag = $ebayOrderModel->setOrderShipped ( $shippedData ); // 上传
							
							if ($flag) {
								// 5.上传成功更新记录表
								$ebayLog->saveEventStatus ( EbayOrder::EVENT_NAME_UPLOAD_TN, $eventLog, $ebayLog::STATUS_SUCCESS );
							} else {
								$ebayLog->saveEventStatus ( EbayOrder::EVENT_NAME_UPLOAD_TN, $eventLog, $ebayLog::STATUS_FAILURE );
								$errorMessage .= $ebayOrderModel->getExceptionMessage ();
							}
							$isResult = $isResult && $flag;
						}
						if ($isResult) {
							UebModel::model ( 'OrderPackage' )->updateAll ( array (
									'is_confirm_shiped' => 1 
							), ' is_confirm_shiped=0 and package_id in("' . $pkId . '")' );
						}
						$isSuccess = $isSuccess && $isResult;
					}
					if ($isSuccess) {
						$ebayLog->setSuccess ( $logID );
					} else {
						$ebayLog->setFailure ( $logID, $errorMessage );
					}
				}
			}
		}
	}
	
	/**
	 * 按指定大小$n 截取数组
	 *
	 * @param unknown $n        	
	 * @return multitype:unknown multitype:
	 */
	public function splitByn($ordArr, $n) {
		$newArr = array ();
		$count = ceil ( count ( $ordArr ) / $n );
		for($i = 0; $i <= $count - 1; $i ++) {
			if ($i == ($count - 1)) {
				$newArr [] = $ordArr;
			} else {
				$newArr [] = array_splice ( $ordArr, 0, $n );
			}
		}
		return $newArr;
	}
	
	/**
	 * @desc 设置UNkown sku的异常数据日志（临时恢复使用）
	 */
	public function actionExceptionorder(){
		$orderID = Yii::app()->request->getParam("order_id");
		if($orderID){
			$orderArr = explode(",", $orderID);
			foreach ($orderArr as $id){
				//检测是否有异常订单
				$orderInfo = Order::model()->findByPk($id);
				if($orderInfo && $orderInfo['complete_status'] == Order::COMPLETE_STATUS_EXCEPTION){
					//判断是否已经存在异常记录数据
					$orderExceptionInfo = OrderExceptionCheck::model()->find("order_id='{$id}'");
					if(!$orderExceptionInfo){
						$res = Order::model()->setExceptionOrder($id, OrderExceptionCheck::EXCEPTION_SKU_UNKNOWN, "该订单下面有主SKU");
						if($res){
							echo "{$id}:操作成功！<br/>";
						}else{
							echo "{$id}:失败！<br/>";
						}
						
					}else{
						echo "{$id}:已存在！<br/>";
					}
					
				}else{
					echo("不符合异常条件<br/>");
				}
			}
		}else{
			exit("未知订单id");
		}
	}

}