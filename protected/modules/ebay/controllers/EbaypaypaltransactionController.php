<?php
/**
 * @paypal交易控制器
 */
class EbaypaypaltransactionController extends UebController
{

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
							'getpaypaltrans',
							'getonepaypaltrans',
							'getpaypaltransdetails',
							'getonepaypaltransdetails',
							'checkgetpaypaltrans',
							'checkgetpaypaltransdetails',
							'synctrans',//同步paypal交易
						) 
				) 
		);
	}

	/**
	 * 获取paypal交易信息 -- (默认拉取昨天的数据)
	 * @link /ebay/ebaypaypaltransaction/getpaypaltrans
	 *       /ebay/ebaypaypaltransaction/getpaypaltrans/account_id/19/start_date/2016-12-15/end_date/2017-01-01/transaction_class/All
	 *
	 * 12个账号（2017-01-21统计）： 1,2,3,8,9,10,12,15,16,17,18,20
	 */
	public function actionGetpaypaltrans() {
		set_time_limit(24*3600);
		ini_set ( 'display_errors', true );
		error_reporting( E_ALL & ~E_STRICT );

		$payAccountId = trim(Yii::app()->request->getParam('account_id',''));
		$startDate = trim(Yii::app()->request->getParam('start_date',''));//2016-09-10
		$endDate = trim(Yii::app()->request->getParam('end_date',''));//2016-09-12
		$transactionClass = trim(Yii::app()->request->getParam('transaction_class','All'));
		$interval = trim(Yii::app()->request->getParam('interval',120));//2分钟

		//默认拉取前一天数据
		$beforeDay = date('Y-m-d', strtotime("-1 days"));
		if ($startDate == '') {
			$startTime = strtotime( $beforeDay." 00:00:00" );
		} else {
			$startTime = strtotime($startDate);
		}
		if ($endDate == '') {
			$endTime = strtotime( $beforeDay." 23:59:59" );
		} else {
			$endTime = strtotime($endDate);
		}

		if ($payAccountId != '') {
			$logModel = new EbayLog();
	        $eventName = EbayLog::EVENT_TRANSACTIONSEARCH;
	        $virAccountID = 80000+$payAccountId;//虚拟账号id
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
				$model = new EbayPaypalTransaction();
				$model->setPaypalAccountID($payAccountId);
				$model->setTransactionTime(array($startTime,$endTime));
				$model->setInterval($interval);
				if ( $transactionClass ) {
					$model->setTransactionClass($transactionClass);
				}
				$isOk = $model->startSearchTransaction();
				var_dump($isOk);
	            
	            //标识事件成功
	            $logModel->setSuccess($logID);
	        }			
		} else {
			$accountList = array();
			$accounts = PaypalAccount::model()->getListByCondition("id,api_user_name","status=1");
			foreach ($accounts as $val) {
				if (!isset($accountList[$val['api_user_name']])) {//去重
					$accountList[$val['api_user_name']] = $val['id'];
				}
			}
			foreach ($accountList as $account_id) {
				$url = Yii::app()->request->hostInfo.'/' . $this->route 
					. '/account_id/' . $account_id
					. '/start_date/' . $startDate
					. '/end_date/' . $endDate
					. '/transaction_class/' . $transactionClass
					. '/interval/' . $interval;
                echo $url." <br>\r\n";
                MHelper::runThreadBySocket($url);
                sleep(3);
        	}
		}

		exit('##finish');
	}

	/**
	 * 获取paypal交易--check (默认check前天数据)
	 * @link /ebay/ebaypaypaltransaction/checkgetpaypaltrans/account_id/19/start_date/2016-12-15/end_date/2017-01-01/transaction_class/All
	 */
	public function actionCheckgetpaypaltrans() {
		set_time_limit(24*3600);
		ini_set ( 'display_errors', true );
		error_reporting( E_ALL & ~E_STRICT );

		$payAccountId = trim(Yii::app()->request->getParam('account_id',''));
		$startDate = trim(Yii::app()->request->getParam('start_date',''));//2016-09-10
		$endDate = trim(Yii::app()->request->getParam('end_date',''));//2016-09-12
		$transactionClass = trim(Yii::app()->request->getParam('transaction_class','All'));
		$interval = trim(Yii::app()->request->getParam('interval',120));//2分钟

		//默认拉取前2天数据
		$beforeDay = date('Y-m-d', strtotime("-2 days"));
		if ($startDate == '') {
			$startTime = strtotime( $beforeDay." 00:00:00" );
		} else {
			$startTime = strtotime($startDate);
		}
		if ($endDate == '') {
			$endTime = date('Y-m-d 23:59:59',strtotime("-1 days"));
		} else {
			$endTime = strtotime($endDate);
		}

		if ($payAccountId != '') {
			$model = new EbayPaypalTransaction();

			// //取最近一次结束时间作为开始时间
			// $last = $model->getOneByCondition("transaction_time","paypal_account_id='{$payAccountId}' and transaction_time>'".date('Y-m-d H:i',$startTime)."'  AND transaction_time<'".date('Y-m-d H:i',$endTime)."' ","transaction_time DESC");
			// if (!empty($last)) {
			// 	$startTime = strtotime($last['transaction_time']);
			// }
			// echo 'start:'.date('Y-m-d H:i:s',$startTime).'--'.date('Y-m-d H:i:s',$endTime)."<br>";
			// // exit;

			$model->setPaypalAccountID($payAccountId);
			$model->setTransactionTime(array($startTime,$endTime));
			$model->setInterval($interval);
			if ( $transactionClass ) {
				$model->setTransactionClass($transactionClass);
			}
			$isOk = $model->startSearchTransaction();
			var_dump($isOk);
	        			
		} else {
			$accountList = array();
			$accounts = PaypalAccount::model()->getListByCondition("id,api_user_name","status=1");
			foreach ($accounts as $val) {
				if (!isset($accountList[$val['api_user_name']])) {//去重
					$accountList[$val['api_user_name']] = $val['id'];
				}
			}
			foreach ($accountList as $account_id) {
				$url = Yii::app()->request->hostInfo.'/' . $this->route 
					. '/account_id/' . $account_id
					. '/start_date/' . $startDate
					. '/end_date/' . $endDate
					. '/transaction_class/' . $transactionClass
					. '/interval/' . $interval;
                echo $url." <br>\r\n";
                MHelper::runThreadBySocket($url);
                sleep(3);
        	}
		}

		exit('##finish');
	}	

	/**
	 * 获取单个paypal交易信息
	 * @link /ebay/ebaypaypaltransaction/getonepaypaltrans/account_id/2/start_date/##/end_date/##/transaction_id/##
	 */
	public function actionGetonepaypaltrans() {
		ini_set ( 'display_errors', true );
		error_reporting( E_ALL & ~E_STRICT );		
		//必填
		$transactionId = trim(Yii::app()->request->getParam('transaction_id'));	
		$startDate = trim(Yii::app()->request->getParam('start_date',''));//2016-09-10
		$endDate = trim(Yii::app()->request->getParam('end_date',''));//2016-09-12
		//选填
		$payAccountId = trim(Yii::app()->request->getParam('account_id',''));
		$transactionClass = trim(Yii::app()->request->getParam('transaction_class'));

		if ($payAccountId == '') {
			die('account_id is empty');
		}

		if ($startDate == '') {
			$startTime = strtotime("-1 days");
		} else {
			$startTime = strtotime($startDate);
		}

		if ($endDate == '') {
			$endTime = time();
		} else {
			$endTime = strtotime($endDate);
		}

		$model = new EbayPaypalTransaction();
		$model->setPaypalAccountID($payAccountId);
		$model->setTransactionId($transactionId);
		$model->setTransactionTime(array($startTime,$endTime));
		if ( $transactionClass ) {
			$model->setTransactionClass($transactionClass);
		}		
		$isOk = $model->searchByTransaction();
		var_dump($isOk);

        exit('##finish');
	}

	/**
	 * 获取paypal交易明细 
	 * @link  /ebay/ebaypaypaltransaction/getpaypaltransdetails/account_id/19
	 *        /ebay/ebaypaypaltransaction/getpaypaltransdetails/account_id/19/start_date/2016-12-15/end_date/2017-01-01
	 */
	public function actionGetpaypaltransdetails() {
		set_time_limit(24*3600);
		ini_set ( 'display_errors', true );
		error_reporting( E_ALL & ~E_STRICT );

		$payAccountId = trim(Yii::app()->request->getParam('account_id',''));
		$startDate = trim(Yii::app()->request->getParam('start_date',''));//2016-09-10
		$endDate = trim(Yii::app()->request->getParam('end_date',''));//2016-09-12

		//默认拉取前一天数据
		$beforeDay = date('Y-m-d', strtotime("-7 days"));
		if ($startDate == '') {
			$startTime = $beforeDay." 00:00:00";
		} else {
			$startTime = date('Y-m-d H:i:s', strtotime($startDate));
		}
		if ($endDate == '') {
			$endTime = date('Y-m-d H:i:s');
		} else {
			$endTime = date('Y-m-d H:i:s', strtotime($endDate));
		}

		if ($payAccountId != '') {
			$logModel = new EbayLog();
	        $eventName = EbayLog::EVENT_GETTRANSACTIONDETAILS;
	        $virAccountID = 80000+$payAccountId;//虚拟账号id
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
				$model = new EbayPaypalTransactionDetail();
				$model->setPaypalAccountID($payAccountId);
				if ($startTime != '' && $endTime != '') {
					$model->setTransactionTime(array($startTime,$endTime));
				}
				$isOk = $model->getTransactionDetailsByCondition();
				var_dump($isOk);

	            //标识事件成功
	            $logModel->setSuccess($logID);
	        }
		} else {
			$accountList = array();
			$accounts = PaypalAccount::model()->getListByCondition("id,api_user_name","status=1");
			foreach ($accounts as $val) {
				if (!isset($accountList[$val['api_user_name']])) {//去重
					$accountList[$val['api_user_name']] = $val['id'];
				}
			}
			foreach ($accountList as $account_id) {
				$url = Yii::app()->request->hostInfo.'/' . $this->route 
					. '/account_id/' . $account_id
					. '/start_date/' . $startDate
					. '/end_date/' . $endDate;
                echo $url." <br>\r\n";
                MHelper::runThreadBySocket($url);
                sleep(3);
        	}
		}

		exit('##finish');
	}

	/**
	 * 获取paypal交易明细 -- check
	 * @link  /ebay/ebaypaypaltransaction/checkgetpaypaltransdetails/account_id/19
	 *        /ebay/ebaypaypaltransaction/checkgetpaypaltransdetails/account_id/19/start_date/2016-12-15/end_date/2017-01-01
	 */
	public function actionCheckgetpaypaltransdetails() {
		set_time_limit(24*3600);
		ini_set ( 'display_errors', true );
		error_reporting( E_ALL & ~E_STRICT );

		$payAccountId = trim(Yii::app()->request->getParam('account_id',''));
		$startDate = trim(Yii::app()->request->getParam('start_date',''));//2016-09-10
		$endDate = trim(Yii::app()->request->getParam('end_date',''));//2016-09-12
		$day = trim(Yii::app()->request->getParam('day',7));//默认7天

		//默认拉取前一天数据
		$beforeDay = date('Y-m-d', strtotime("-{$day} days"));
		if ($startDate == '') {
			$startTime = $beforeDay." 00:00:00";
		} else {
			$startTime = date('Y-m-d H:i:s', strtotime($startDate));
		}
		if ($endDate == '') {
			$endTime = date('Y-m-d H:i:s');
		} else {
			$endTime = date('Y-m-d H:i:s', strtotime($endDate));
		}

		if ($payAccountId != '') {
			$model = new EbayPaypalTransactionDetail();
			$model->setPaypalAccountID($payAccountId);
			if ($startTime != '' && $endTime != '') {
				$model->setTransactionTime(array($startTime,$endTime));
			}
			$isOk = $model->getTransactionDetailsByCondition();
			var_dump($isOk);
		} else {
			$accountList = array();
			$accounts = PaypalAccount::model()->getListByCondition("id,api_user_name","status=1");
			foreach ($accounts as $val) {
				if (!isset($accountList[$val['api_user_name']])) {//去重
					$accountList[$val['api_user_name']] = $val['id'];
				}
			}
			foreach ($accountList as $account_id) {
				$url = Yii::app()->request->hostInfo.'/' . $this->route 
					. '/account_id/' . $account_id
					. '/start_date/' . $startDate
					. '/end_date/' . $endDate
					. '/day/' . $day;
                echo $url." <br>\r\n";
                MHelper::runThreadBySocket($url);
                sleep(3);
        	}
		}

		exit('##finish');
	}	

	/**
	 * 获取单个paypal交易明细 
	 * @link /ebay/ebaypaypaltransaction/getonepaypaltransdetails/transaction_id/###
	 *       /ebay/ebaypaypaltransaction/getonepaypaltransdetails/account_id/19/transaction_id/###
	 */
	public function actionGetonepaypaltransdetails() {
		ini_set ( 'display_errors', true );
		error_reporting( E_ALL & ~E_STRICT );

		$payAccountId = trim(Yii::app()->request->getParam('account_id'));
		$transactionId = trim(Yii::app()->request->getParam('transaction_id'));

		if (empty($transactionId)) {
			die("transaction_id is empty");
		}

		$model = new EbayPaypalTransactionDetail();
		$model->setPaypalAccountID($payAccountId);
		$model->setTransactionId($transactionId);  
		$isOk = $model->getgetTransactionDetailsByTransactionID();
		var_dump($isOk);

		die('finish');
	}

	/**
	 * 同步paypal退款交易记录到OMS系统
	 * @link /ebay/ebaypaypaltransaction/synctrans/account_id/##
	 *      /ebay/ebaypaypaltransaction/synctrans/account_id/##/start_date/##/end_date/##
	 */
	public function actionSynctrans() {
		set_time_limit(3600);
		ini_set ( 'display_errors', true );
		error_reporting( E_ALL & ~E_STRICT );

		$payAccountId = trim(Yii::app()->request->getParam('account_id',''));
		$startDate = trim(Yii::app()->request->getParam('start_date',''));//2016-09-10
		$endDate = trim(Yii::app()->request->getParam('end_date',''));//2016-09-12
		$day = trim(Yii::app()->request->getParam('day',14));
		$transactionId = trim(Yii::app()->request->getParam('transaction_id'));

		$beforeDay = date('Y-m-d', strtotime("-{$day} days"));
		$startDate2 = $startDate == '' ? $beforeDay." 00:00:00" : $startDate;
		$endDate2 = $endDate == '' ? date('Y-m-d', strtotime("-1 day"))." 23:59:59" : $endDate;
		echo $startDate2.' -- '.$endDate2.'<br>';

		if ($payAccountId != '') {
			$logModel = new EbayLog();
	        $eventName = EbayLog::EVENT_SYNC_TRANSACTION;
	        $virAccountID = 70000;//虚拟账号id
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
				$payAccountIdArr = explode(',',$payAccountId);
				$model = new EbayPaypalTransaction();
				$model->setPaypalAccountIDArr($payAccountIdArr);
				if ($startDate2 != '' && $endDate2 != '') {
					$model->setTransactionTime(array($startDate2,$endDate2));
				}
				if ($transactionId) {
					$model->setTransactionId($transactionId);  
				}
				$total = $model->syncTransactions();
	            //标识事件成功
	            $logModel->setSuccess($logID,'Total:'.$total);
	            echo $payAccountId.' ## '.'Total:'.$total."<br>";
	        }
		} else {
			$accounts = PaypalAccount::model()->getListByCondition("id,api_user_name","status=1");
			foreach ($accounts as $val) {
				$accountList[$val['api_user_name']][] = $val['id'];
			}
			foreach ($accountList as $accountIdArr) {
				$url = Yii::app()->request->hostInfo.'/' . $this->route 
					. '/account_id/' . implode(',', $accountIdArr)
					. '/start_date/' . $startDate2
					. '/end_date/' . $endDate2;
                echo $url." <br>\r\n";
                MHelper::runThreadBySocket($url);
                sleep(120);
        	}
		}
		die("finish");
	}

}