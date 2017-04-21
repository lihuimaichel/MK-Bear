<?php
/**
 * 
 * @author liuj
 *
 */
class LazadalogController extends UebController{
	public function actionList(){
		$this->render("list", array(
			"model"	=>	new LazadaLog()
		));
	}


	/**
	 * 每月运行一次删除前3个月或者前6个月的log数据
	 * @link /lazada/lazadalog/deletelog
	 */
	public function actionDeletelog(){
		set_time_limit(3600);
        ini_set("display_errors", true);
        error_reporting(E_ALL);

		$logModel = new LazadaLog();
		$accountID  = 2017;	//虚拟账号
		$threeMonth = date('Y-m-d 00:00:00', strtotime('-3 month'));
		$sixMonth   = date('Y-m-d 00:00:00', strtotime('-6 month'));

		//创建运行日志		
		$logId = $logModel->prepareLog($accountID, LazadaLog::EVENT_CLEAR_LOG);
		if(!$logId) {
			echo Yii::t('wish_listing', 'Log create failure');
			Yii::app()->end();
		}
		//检查账号是可以提交请求报告
		$checkRunning = $logModel->checkRunning($accountID, LazadaLog::EVENT_CLEAR_LOG);
		if(!$checkRunning){
			$logModel->setFailure($logId, Yii::t('systems', 'There Exists An Active Event'));
			echo Yii::t('systems', 'There Exists An Active Event');
			Yii::app()->end();
		}
		//设置日志为正在运行
		$logModel->setRunning($logId);
		try{

			$sql = "DELETE FROM market_lazada.`ueb_lazada_log` WHERE end_time < '{$threeMonth}';
					DELETE FROM market_lazada.`ueb_lazada_log_getorder` WHERE end_time < '{$sixMonth}';
					DELETE FROM market_lazada.`ueb_lazada_log_getproduct` WHERE end_time < '{$threeMonth}';
					DELETE FROM market_lazada.`ueb_lazada_log_offline` WHERE start_time < '{$threeMonth}';
					DELETE FROM market_lazada.`ueb_lazada_log_upload_ship` WHERE create_time < '{$sixMonth}'; 
					DELETE FROM market_lazada.`ueb_lazada_log_upload_track` WHERE create_time < '{$sixMonth}';
					DELETE FROM market_lazada.`ueb_lazada_log_zero_stock` WHERE end_time < '{$threeMonth}';";
			$logModel->getDbConnection()->createCommand($sql)->execute();
			$logModel->setSuccess($logId);

		}catch(Exception $e){
			$logModel->setFailure($logId, $e->getMessage());
			echo $e->getMessage();
		}			

		Yii::app()->end('Finish');
	}
}