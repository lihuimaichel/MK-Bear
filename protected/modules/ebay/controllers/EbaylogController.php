<?php
/**
 * 
 * @author liuj
 *
 */
class EbaylogController extends UebController{
	public function actionList(){
		$this->render("list", array(
			"model"	=>	new EbayLog()
		));
	}


	/**
	 * #2909 清除三个月或六个月之前的ebay日志
	 * /ebay/ebaylog/deletelog
	 */
	public function actionDeleteLog(){
		set_time_limit(2*3600);
		ini_set("display_errors", true);
		error_reporting(E_ALL);

		$threeMonth = date('Y-m-d H:i:s', time() - 3*30*86400);	//三个月之前
		$sixMonth = date('Y-m-d H:i:s', time() - 6*30*86400);	//六个月之前
		$model = new EbayLog();
		$accountID = 3001;//指定虚拟account_id,3001

		try {
			$eventName = "clear_log";
			$logID = $model->prepareLog($accountID, $eventName);
			if (!$logID) {
				throw new Exception("Create Log ID Failure");
			}
			//检测是否可以允许
			if (!$model->checkRunning($accountID, $eventName)) {
				throw new Exception("There Exists An Active Event");
			}
			//设置运行
			$model->setRunning($logID);

			//三个月
			//程序运行日志
			$sql = "delete from market_ebay.ueb_ebay_log where start_time < '{$threeMonth}'";
			$model->getDbConnection()->createCommand($sql)->execute();

			//complete sale 日志
			$sql = "delete from market_ebay.ueb_ebay_log_complete_sale where start_time < '{$threeMonth}'";
			$model->getDbConnection()->createCommand($sql)->execute();

			//拉取订单日志
			$sql = "delete from market_ebay.ueb_ebay_log_get_orderids where start_time < '{$threeMonth}'";
			$model->getDbConnection()->createCommand($sql)->execute();

			//ebay拉取产品日志
			$sql = "delete from market_ebay.ueb_ebay_log_get_product where start_time < '{$threeMonth}'";
			$model->getDbConnection()->createCommand($sql)->execute();

			//ebay订单拉取日志
			$sql = "delete from market_ebay.ueb_ebay_log_getorder where start_time < '{$threeMonth}'";
			$model->getDbConnection()->createCommand($sql)->execute();

			//ebay更新产品日志
			$sql = "delete from market_ebay.ueb_ebay_log_update_product where start_time < '{$threeMonth}'";
			$model->getDbConnection()->createCommand($sql)->execute();

			//ebay置零库存日志
			$sql = "delete from market_ebay.ueb_ebay_log_zero_stock where start_time < '{$threeMonth}'";
			$model->getDbConnection()->createCommand($sql)->execute();



			//六个月
			//提前标记发货
			$sql = "delete from market_ebay.ueb_ebay_log_advance_shipped where start_time < '{$sixMonth}'";
			$model->getDbConnection()->createCommand($sql)->execute();

			//上传ebay跟踪号
			$sql = "delete from market_ebay.ueb_ebay_log_upload_track where start_time < '{$sixMonth}'";
			$model->getDbConnection()->createCommand($sql)->execute();

			//ebay上传追踪号日志
			$sql = "delete from market_ebay.ueb_ebay_log_uploadtracknum where start_time < '{$sixMonth}'";
			$model->getDbConnection()->createCommand($sql)->execute();

			$model->setSuccess($logID);
		} catch (Exception $e) {
			if ($logID) {
				$model->setFailure($logID, $e->getMessage());
			}
			echo $e->getMessage() . "<br/>";
		}

		Yii::app()->end('finish');

	}

}