<?php
/**
 * @desc 速卖通下架记录
 * @author hanxy
 *
 */
class AliexpressproductofflineController extends UebController {
	
	/** @var object 模型实例 **/
	protected $_model = NULL;
	
	/**
	 * (non-PHPdoc)
	 * @see CController::init()
	 */
	public function init() {
		$this->_model = new AliexpressOffline();
	}
	
	/**
	 * @desc 列表页
	 */
	public function actionList() {
		$this->render("list", array("model"=>$this->_model));
	}


	/**
	 * 产品下架，由于产品不存在，导致更新失败，所以更新产品管理表为下架
	 * /aliexpress/aliexpressproductoffline/updateproductstatus
	 */
	public function actionUpdateproductstatus(){
		$aliProduct      = new AliexpressProduct();
		$productId       = Yii::app()->request->getParam('product_id');
		$wheres = "`status` = 0 AND message LIKE '%proudctId:input parameter error: the product does not exist.'";
		if($productId){
			$wheres = "product_id = '".$productId."'";
		}
        $command = $this->_model->getDbConnection()->createCommand()
            ->from($this->_model->tableName())
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