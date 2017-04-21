<?php
/**
 * paytm平台日志管理
 *
 */
class PaytmlogController extends UebController{

	public function actionList(){
		$this->render("list", array(
			"model"	=>	new PaytmLog()
		));
	}

}