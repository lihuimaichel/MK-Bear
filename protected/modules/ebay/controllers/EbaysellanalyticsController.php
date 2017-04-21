<?php
/**
 * @desc ebay销售分析控制器
 * @author yangsh
 * @since 2017-03-15
 */
class EbaysellanalyticsController extends UebController {
	
	/**
	 * 访问过滤配置
	 *
	 * @see CController::accessRules()
	 */
	public function accessRules() {
		return array (
					array (
						'allow',
						'users' => array ('*'),
						'actions' => array (
							'getorderids',
						) 
					) 
				);
	}


	public function actionListbestmatches() {
		$this->render('listbestmatches',array('model'=>new EbayItembestmatches()));
	}

	public function actionGetebaycategorybysite() {
		$siteId = intval(Yii::app()->request->getParam("site_id"));
		list($catLevel1,$catLevel2) = EbayItembestmatches::model()->getEbayCategoryListBySite($siteId);
		echo $this->successJson(array('data'=>array('catLevel1'=>$catLevel1,'catLevel2'=>$catLevel2)));
	}

	/**
	 * @desc Item Best Matches转化率分析
	 * @link /ebay/ebaysellanalytics/getitembestmatches/debug/1/account_id/7
	 *       /ebay/ebaysellanalytics/getitembestmatches/debug/1/account_id/7/start_date/20161228/end_date/20170322
	 * @author yangsh
	 */
	public function actionGetitembestmatches(){
		set_time_limit(12*3600);
		ini_set('memory_limit','1024M');
		ini_set ( 'display_errors', true );
		error_reporting( E_ALL & ~E_STRICT );

		$accountID = trim(Yii::app()->request->getParam('account_id'));	
		$startDate = trim(Yii::app()->request->getParam('start_date',''));	
		$endDate = trim(Yii::app()->request->getParam('end_date',''));	

		$lastDate = date('Ymd', strtotime("-2 days"));
		if ($endDate == '') {
			$endDate = $lastDate;
		}
		if ($startDate == '') {
			$startDate = date('Ymd',strtotime($endDate)-86400);
		}
		$dateRange = $startDate.'..'.$endDate;

		if ($accountID) {
			$logModel = new EbayLog();
	        $eventName = EbayLog::EVENT_ITEM_BEST_MATCHES;
	        $logID = $logModel->prepareLog($accountID,$eventName);
	        if (!$logID) {
	        	exit("Create LogID Failure!!!");
	        }
	        $checkRunning = $logModel->checkRunning($accountID, $eventName);
	        if (!$checkRunning) {
	            echo 'Exists An Active Event.<br>';
	            $logModel->setFailure($logID, Yii::t('systems', 'Exists An Active Event'));
	        }else {
	            //设置日志为正在运行
	            $logModel->setRunning($logID);
	            //开始
				$model = new EbayItembestmatches();
				$model->setAccountID($accountID);
				$isOk = $model->getItemBestMatches($dateRange);
	            //标识事件成功
	            if($isOk) {
	            	$logModel->setSuccess($logID);
	            } else {
	            	$errMessage = $model->getExceptionMessage();
                    if (mb_strlen($errMessage)>200) {
                        $errMessage = mb_substr($errMessage,0,500);
                    }
	            	$logModel->setFailure($logID,$errMessage);
	            }
	            echo ($isOk ? 'success' : 'failure') .' ## '. $model->getExceptionMessage()."<br>";
	        }	
		} else {
			$accountIdArr = array(7,8,9,12,19,62);
			//$accountList = EbayAccount::model()->getAbleAccountList();
            // foreach ($accountList as $accountInfo) {
            //     $accountIdArr[] = $accountInfo['id'];
            // }
            $accountIdArr = array_unique($accountIdArr);
            sort($accountIdArr,SORT_NUMERIC);
            $accountIdGroupData = MHelper::getGroupData($accountIdArr,6);
            foreach ($accountIdGroupData as $key => $idGroupData) {
                foreach ($idGroupData as $account_id) {
                    $url = Yii::app()->request->hostInfo.'/' . $this->route 
                    	. '/account_id/' . $account_id
                    	. '/start_date/'.$startDate
                    	.'/end_date/'.$endDate ;
                    echo $url." <br>\r\n";
                    MHelper::runThreadBySocket($url);
                }
                sleep(300);//每5分钟执行6个账号
            }
		}
		die('finish');
	}

}