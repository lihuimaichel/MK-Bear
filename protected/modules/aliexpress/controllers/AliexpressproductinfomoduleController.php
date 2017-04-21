<?php
/**
 * @desc 产品信息模块控制期
 * @author zhangf
 *
 */
class AliexpressproductinfomoduleController extends UebController {
	public function actionIndex() {

	}
	
	public function actionGetproductinfomodules() {
		ini_set('display_errors', true);
		error_reporting(E_ALL);
		$accountID = Yii::app()->request->getParam('account_id');
		if ($accountID) {
			//创建日志
			$aliexpressLog = new AliexpressLog();
			$logID = $aliexpressLog->prepareLog($accountID, Aliexpressproductinfomodule::EVENT_NAME);
			if ($logID) {
				//检查账号是否可以拉取
				$checkRunning = $aliexpressLog->checkRunning($accountID, Aliexpressproductinfomodule::EVENT_NAME);
				if (0) {
					$aliexpressLog->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
				} else {
					//插入本次log参数日志(用来记录请求的参数)
					$time = date('Y-m-d H:i:s');
					$eventLogID = $aliexpressLog->saveEventLog(Aliexpressproductinfomodule::EVENT_NAME, array(
							'log_id' => $logID,
							'account_id' => $accountID,
							'start_time'    => $time,
							'end_time'      => $time,
					));
					//设置日志正在运行
					$aliexpressLog->setRunning($logID);
					//拉取产品
					$AliexpressproductinfomoduleModel = new Aliexpressproductinfomodule();
					$AliexpressproductinfomoduleModel->setAccountID($accountID);
					$flag = $AliexpressproductinfomoduleModel->getProductInfoModules();
					//更新日志信息
					if( $flag ){
						$aliexpressLog->setSuccess($logID);
						$aliexpressLog->saveEventStatus(Aliexpressproductinfomodule::EVENT_NAME, $eventLogID, AliexpressLog::STATUS_SUCCESS);
						echo $this->successJson(array(
								'message' => Yii::t('aliexpress', 'Get Product Info Modules Success'),
						));
						Yii::app()->end();
					}else{
						$aliexpressLog->setFailure($logID, $AliexpressproductinfomoduleModel->getErrorMessage());
						$aliexpressLog->saveEventStatus(Aliexpressproductinfomodule::EVENT_NAME, $eventLogID, AliexpressLog::STATUS_FAILURE);
						echo $this->failureJson(array(
								'message' => Yii::t('aliexpress', 'Get Product Info Modules Failure'),
						));
						Yii::app()->end();					
					}
				}
			}
		} else {
			//循环每个账号发送一个拉listing的请求
			$accountList = AliexpressAccount::getAbleAccountList();
			foreach($accountList as $account){
				MHelper::runThreadSOCKET('/'.$this->route.'/account_id/'.$account['id']);
				sleep(1);
			}
		}		
	}
	
	public function actionList() {
		$model = new Aliexpressproductinfomodule();
		echo $this->render('list', array(
			'model' => $model,
		));
	}
}