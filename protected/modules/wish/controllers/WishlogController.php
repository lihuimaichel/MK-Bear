<?php
/**
 * 
 * @author liuj
 *
 */
class WishlogController extends UebController{

	public function actionList(){
		$this->render("list", array(
			"model"	=>	new WishLog()
		));
	}

	/**
	 * $desc 清除三个月前的所有wish日志数据
	 * @link /wish/wishlog/deletelogpartdata
	 * @return 
	 */
	public function actionDeletelogpartdata(){
        set_time_limit(2*3600);
        ini_set("display_errors", true);
        error_reporting(E_ALL & ~E_STRICT);

        $logModel = new WishLog();
		$accountID  = 1008;	//虚拟账号
		$threeMonth = date('Y-m-d H:i:s', time() - 90*86400 );	//三个月之前
		$sixMonth   = date('Y-m-d H:i:s', time() - 180*86400 );	//六个月之前

		//创建运行日志		
		$logId = $logModel->prepareLog($accountID, WishLog::EVENT_CLEAR_LOG);
		if(!$logId) {
			echo Yii::t('wish_listing', 'Log create failure');
			Yii::app()->end();
		}
		//检查账号是可以提交请求报告
		$checkRunning = $logModel->checkRunning($accountID, WishLog::EVENT_CLEAR_LOG);
		if(!$checkRunning){
			$logModel->setFailure($logId, Yii::t('systems', 'There Exists An Active Event'));
			echo Yii::t('systems', 'There Exists An Active Event');
			Yii::app()->end();
		}
		//设置日志为正在运行
		$logModel->setRunning($logId);
		try{
			$sql = "delete from market_wish.ueb_wish_log where end_time < '{$threeMonth}'";
			$logModel->getDbConnection()->createCommand($sql)->execute();

			//wish提前标记发货 上传假单号
			$sql = "delete from market_wish.ueb_wish_log_advance_shipped where end_time < '{$sixMonth}'";
			$logModel->getDbConnection()->createCommand($sql)->execute();

			$sql = "delete from market_wish.ueb_wish_log_batch_product_add where create_time < '{$threeMonth}'";
			$logModel->getDbConnection()->createCommand($sql)->execute();

			$sql = "delete from market_wish.ueb_wish_log_disabled_variants where end_time < '{$threeMonth}'";
			$logModel->getDbConnection()->createCommand($sql)->execute();

			$sql = "delete from market_wish.ueb_wish_log_fulfill_order where complete_time < '{$threeMonth}'";
			$logModel->getDbConnection()->createCommand($sql)->execute();

			$sql = "delete from market_wish.ueb_wish_log_get_product where end_time < '{$threeMonth}'";
			$logModel->getDbConnection()->createCommand($sql)->execute();

			$sql = "delete from market_wish.ueb_wish_log_getorder where complete_time < '{$threeMonth}'";
			$logModel->getDbConnection()->createCommand($sql)->execute();

			$sql = "delete from market_wish.ueb_wish_log_offline where response_time < '{$threeMonth}'";
			$logModel->getDbConnection()->createCommand($sql)->execute();

			//创建Wish邮订单日志
			$sql = "delete from market_wish.ueb_wish_log_post_order where operate_time < '{$sixMonth}'";
			$logModel->getDbConnection()->createCommand($sql)->execute();

			$sql = "delete from market_wish.ueb_wish_log_upload_product where end_time < '{$threeMonth}'";
			$logModel->getDbConnection()->createCommand($sql)->execute();

			//上传wish跟踪号
			$sql = "delete from market_wish.ueb_wish_log_upload_track where end_time < '{$sixMonth}'";
			$logModel->getDbConnection()->createCommand($sql)->execute();

			$sql = "delete from market_wish.ueb_wish_log_zero_stock where end_time < '{$threeMonth}'";
			$logModel->getDbConnection()->createCommand($sql)->execute();

			$logModel->setSuccess($logId);
		}catch(Exception $e){
			$logModel->setFailure($logId, $e->getMessage());
			echo $e->getMessage();
		}			

		Yii::app()->end('Finish');
	}


}