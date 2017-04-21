<?php
/**
 * Priceminister平台日志管理
 *
 */
class PriceministerlogController extends UebController{

	public function actionList(){
		$this->render("list", array(
			"model"	=>	new PriceministerLog()
		));
	}
}