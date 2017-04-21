<?php
/**
 * 
 * @author liuj
 *
 */
class AmazonlogController extends UebController{

	public function actionList(){
		$this->render("list", array(
			"model"	=>	new AmazonLog()
		));
	}

	/**
	 * $desc 清除三个月前的所有amazon日志数据
	 * @link /amazon/amazonlog/deletelogpartdata
	 * @return 
	 */
	public function actionDeletelogpartdata(){
        set_time_limit(2*3600);
        ini_set("display_errors", true);
        error_reporting(E_ALL & ~E_STRICT);

        $logModel = new AmazonLog();
		$accountID  = 1008;	//虚拟账号
		$threeMonth = date('Y-m-d H:i:s', time() - 90*86400 );	//三个月之前
		$sixMonth   = date('Y-m-d H:i:s', time() - 180*86400 );	//六个月之前

		//创建运行日志		
		$logId = $logModel->prepareLog($accountID,  AmazonLog::EVENT_CLEAR_LOG);
		if(!$logId) {
			echo Yii::t('amazon_product', 'Log create failure');
			Yii::app()->end();
		}
		//检查账号是可以提交请求报告
		$checkRunning = $logModel->checkRunning($accountID, AmazonLog::EVENT_CLEAR_LOG);
		if(!$checkRunning){
			$logModel->setFailure($logId, Yii::t('systems', 'There Exists An Active Event'));
			echo Yii::t('systems', 'There Exists An Active Event');
			Yii::app()->end();
		}
		//设置日志为正在运行
		$logModel->setRunning($logId);
		try{
			$sql = "delete from market_amazon.ueb_amazon_log where end_time < '{$threeMonth}'";
			$logModel->getDbConnection()->createCommand($sql)->execute();

			$sql = "delete from market_amazon.ueb_amazon_log_detect_offline_submission where end_time < '{$threeMonth}'";
			$logModel->getDbConnection()->createCommand($sql)->execute();

			$sql = "delete from market_amazon.ueb_amazon_log_getorder where end_time < '{$threeMonth}'";
			$logModel->getDbConnection()->createCommand($sql)->execute();

			$sql = "delete from market_amazon.ueb_amazon_log_getorder_afn where end_time < '{$threeMonth}'";
			$logModel->getDbConnection()->createCommand($sql)->execute();

			$sql = "delete from market_amazon.ueb_amazon_log_offline where response_time < '{$threeMonth}'";
			$logModel->getDbConnection()->createCommand($sql)->execute();

			$sql = "delete from market_amazon.ueb_amazon_log_pull_up_order where end_time < '{$threeMonth}'";
			$logModel->getDbConnection()->createCommand($sql)->execute();

			$sql = "delete from market_amazon.ueb_amazon_log_requestreport where end_time < '{$threeMonth}'";
			$logModel->getDbConnection()->createCommand($sql)->execute();

			$sql = "delete from market_amazon.ueb_amazon_log_submitfeed where end_time < '{$threeMonth}'";
			$logModel->getDbConnection()->createCommand($sql)->execute();

			//上传amazon跟踪号
			$sql = "delete from market_amazon.ueb_amazon_log_uploadtracknum where end_time < '{$sixMonth}'";
			$logModel->getDbConnection()->createCommand($sql)->execute();

			$sql = "delete from market_amazon.ueb_amazon_log_zero_stock where end_time < '{$threeMonth}'";
			$logModel->getDbConnection()->createCommand($sql)->execute();

			$logModel->setSuccess($logId);
		}catch(Exception $e){
			$logModel->setFailure($logId, $e->getMessage());
			echo $e->getMessage();
		}			

		Yii::app()->end('Finish');
	}


}