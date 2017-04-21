<?php
/**
 * 
 * @author lihy
 *
 */
class JoomzerostockController extends UebController{
	public function actionList(){
		$this->render("list", array(
			"model"	=>	new JoomZeroStockSku()
		));
	}
}