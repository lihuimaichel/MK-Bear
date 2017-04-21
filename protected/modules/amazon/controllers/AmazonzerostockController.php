<?php
/**
 * 
 * @author lihy
 *
 */
class AmazonzerostockController extends UebController{
	public function actionList(){
		$this->render("list", array(
			"model"	=>	new AmazonZeroStockSku()
		));
	}
}