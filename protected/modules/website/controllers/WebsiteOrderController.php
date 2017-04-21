<?php
/**
 * @desc 网站平台订单控制器
 * @author zhangF
 *
 */
class WebsiteOrderController extends UebController {

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
	 * @desc 拉取订单action
	 */
	public function actionGetorders() {
		ini_set('display_errors', true);
		error_reporting(E_ALL);
		$accountID = Yii::app()->request->getParam('account_id');
		if ($accountID) {
			//记录任务日志
			$logID = WebsiteLog::model()->prepareLog($accountID,WebsiteOrder::EVENT_NAME);			
			if ($logID) {
				//检查当前账号是否可以拉取订单
				$checkRunning = WebsiteLog::model()->checkRunning($accountID, WebsiteOrder::EVENT_NAME);
				if (!$checkRunning) {
					WebsiteLog::model()->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
				} else {
					$timeArr = WebsiteOrder::model()->getTimeArr($accountID);
					//插入本次log参数日志(用来记录请求的参数)
					$eventLog = WebsiteLog::model()->saveEventLog(WebsiteOrder::EVENT_NAME, array(
							'log_id'        => $logID,
							'account_id'    => $accountID,
							'start_time'    => $timeArr['start_time'],
							'end_time'      => $timeArr['end_time'],
					));
					//设置日志为正在运行
					WebsiteLog::model()->setRunning($logID);
					//3.拉取订单
					$websiteOrderModel = new WebsiteOrder();
					$websiteOrderModel->setAccountID($accountID);//设置账号
					$websiteOrderModel->setLogID($logID);//设置日志编号
					$flag = $websiteOrderModel->getOrders($timeArr);//拉单
					//4.更新日志信息
					if( $flag ){
						WebsiteLog::model()->setSuccess($logID);
						WebsiteLog::model()->saveEventStatus(WebsiteOrder::EVENT_NAME, $eventLog, WebsiteLog::STATUS_SUCCESS);
					}else{
						WebsiteLog::model()->setFailure($logID, $websiteOrderModel->getExceptionMessage());
						WebsiteLog::model()->saveEventStatus(WebsiteOrder::EVENT_NAME, $eventLog, WebsiteLog::STATUS_FAILURE);
					}
				}
			}
			
		} else {
			
		}
	}
}