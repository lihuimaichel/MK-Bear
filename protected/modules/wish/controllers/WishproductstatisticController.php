<?php
/**
 * @desc wish 在线产品统计
 * @author liht
 * @since 20151117
 *
 */
class WishproductstatisticController extends UebController {

	/** @var object 模型实例 **/
	protected $_model = NULL;

	/**
	 * (non-PHPdoc)
	 * @see CController::init()
	 */
	public function init() {
		$this->_model = new WishProductStatistic();
	}

	/**
	 * @desc 列表页
	 */
	public function actionList() {
		$accountID = Yii::app()->request->getParam('account_id', '0');
		$this->_model->account_id = $accountID;
		$this->render('list', array(
			'model' => $this->_model,'accountID'=>$accountID
		));
	}

	/**
	 * @desc 批量添加刊登任务
	 * @throws Exception
	 */
	public function actionBatchPublish() {
		$accountID = Yii::app()->request->getParam('account_id');
		$skus = Yii::app()->request->getParam('ids');

		if (empty($accountID)) {
			echo $this->failureJson(array(
				'message' => Yii::t('lazada_product_statistic', 'Invalid Account'),
			));
			Yii::app()->end();
		}

		$skuArr = explode(',', $skus);
		$skuArr = array_filter($skuArr);

		if (empty($skuArr)) {
			echo $this->failureJson(array(
				'message' => Yii::t('lazada_product_statistic', 'Not Chosen Products'),
			));
			Yii::app()->end();
		}
		$message = '';
		//批量添加到待上传列表
		//$wishProductAddModel = WishProductAdd::model();
		/* foreach ($skuArr as $sku) {
			$return = $wishProductAddModel->productAdd($sku, $accountID);
			if ($return['status'] == '0') {
				$message .= $sku.$return['message'].'<br/>';
			}
		} */
		//$this->_model->batchAddProduct($skuArr, $accountID);
		$wishProductAddModel = new WishProductAdd();
		foreach ($skuArr as $sku){
			$res = $wishProductAddModel->productAddByBatch($sku, $accountID, WishProductAdd::ADD_TYPE_BATCH);
			if(!$res){
				$message .= $wishProductAddModel->getErrorMsg()."<br/>";
			}
		}
		/* $this->_model->batchAddProductFromListing($skuArr, $accountID);
        $message = $this->_model->getErrMsg(); */
		if( $message=='' ){
			echo $this->successJson(array(
				'message' => Yii::t('lazada_product_statistic', 'Publish Task Create Successful'),
				'callbackType' => 'navTabAjaxDone',
			));
		}else{
			echo $this->failureJson(array(
				'message' => $message,
			));
		}
        Yii::app()->end();

	}


}