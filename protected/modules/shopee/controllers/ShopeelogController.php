<?php
/**
 * 
 * @author liuj
 *
 */
class ShopeelogController extends UebController{
	public function actionList(){
		$this->render("list", array(
			"model"	=>	new ShopeeLog()
		));
	}
}