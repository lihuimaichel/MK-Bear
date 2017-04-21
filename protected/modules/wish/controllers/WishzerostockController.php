<?php
/**
 * 
 * @author lihy
 *
 */
class WishzerostockController extends UebController{
	public function actionList(){
		$this->render("list", array(
			"model"	=>	new WishZeroStockSku()
		));
	}
}