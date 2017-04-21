<?php
/**
 * 
 * @author lihy
 *
 */
class EbayzerostockController extends UebController{
	public function actionList(){
		$this->render("list", array(
			"model"	=>	new EbayZeroStockSku()
		));
	}
}