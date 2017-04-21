<?php
/**
 * 
 * @author lihy
 *
 */
class LazadazerostockController extends UebController{
	public function actionList(){
		$this->render("list", array(
			"model"	=>	new LazadaZeroStockSku()
		));
	}


	/**
	 * 去掉库存改0失败的记录
	 * @link /lazada/lazadazerostock/deletefailrecord
	 */
	public function actionDeletefailrecord(){
		ini_set("display_errors", true);
        ini_set("memory_limit","2048M");
        set_time_limit(400);
        error_reporting(E_ALL);
		$sql = "DELETE FROM market_lazada.`ueb_lazada_zero_stock_sku` WHERE `status` = 3 AND create_time < '2017-03-01 00:00:00'";
		LazadaZeroStockSku::model()->getDbConnection()->createCommand($sql)->execute();
	}
}