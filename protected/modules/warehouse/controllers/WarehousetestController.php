<?php
class WarehousetestController extends UebController{
	
	
	public function actionTest(){
		echo "xxxxxxxxxxxx";
		$skuInfo = WarehouseSkuMap::model()->find("sku='1624'");
		var_dump($skuInfo);
	}
}