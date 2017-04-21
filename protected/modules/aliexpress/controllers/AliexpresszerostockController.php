<?php
/**
 * 
 * @author lihy
 *
 */
class AliexpresszerostockController extends UebController{
	public function actionList(){
		$this->render("list", array(
			"model"	=>	new AliexpressZeroStockSku()
		));
	}


	/**
	 * 库存为0，由于产品不存在，导致更新失败，所以更新产品管理表为下架
	 * /aliexpress/aliexpresszerostock/updateproductstatus
	 */
	public function actionUpdateproductstatus(){
		$aliZeroStockSku = new AliexpressZeroStockSku();
		$aliProduct      = new AliexpressProduct();
		$productId       = Yii::app()->request->getParam('product_id');
		$wheres = "`status` = 3 AND msg LIKE '%proudctId:input parameter error: the product does not exist.'";
		if($productId){
			$wheres = "product_id = '".$productId."'";
		}
        $command = $aliZeroStockSku->getDbConnection()->createCommand()
            ->from($aliZeroStockSku->tableName())
            ->select("product_id,account_id,sku")
            ->where($wheres);
        $command->group("product_id");
        $variantListing = $command->queryAll();
        foreach ($variantListing as $key => $value) {
        	$productData = array('product_status_type'=>'offline');
        	$aliProduct->getDbConnection()->createCommand()->update($aliProduct->tableName(), $productData, "aliexpress_product_id = '".$value['product_id']."'");
        	echo $value['product_id'].'---'.$value['account_id'].'---'.$value['sku'].'<br>';
        }
	}
}