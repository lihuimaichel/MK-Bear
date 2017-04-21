<?php
/**
 * 
 * @author hanxy
 *
 */
class AmazonoverseaswarehousezerostockskuController extends UebController{
	public function actionList(){
		$this->render("list", array(
			"model"	=>	new AmazonOverseasWarehouseZeroStockSku()
		));
	}
}