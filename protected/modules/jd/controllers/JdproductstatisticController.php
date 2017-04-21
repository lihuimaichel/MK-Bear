<?php
/**
 * @desc 京东平台产品统计
 * @author Michael
 */
class JdproductstatisticController extends UebController {
	protected $_model = null;
	public function init() {
		$this->_model = new Jdproductstatistic();
	}
	
	public function actionList(){
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
		//$siteID = Yii::app()->request->getParam('site_id');
		$accountID = Yii::app()->request->getParam('account_id');
		$skus = Yii::app()->request->getParam('ids');
		if (empty($accountID)) {
			echo $this->failureJson(array(
					'message' => Yii::t('jd', 'Invalid Account'),
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
		//批量添加到待上传列表
		$message = '';
		foreach ($skuArr as $sku) {
			$JdProductAddModel = new JdProductAdd();
			$flag = $JdProductAddModel->productAdd($accountID, $sku);
			if (!$flag)
				$message .= $JdProductAddModel->getErrorMessage() . "<br />";
		}
		if( $message=='' ){
			echo $this->successJson(array(
					'message' => Yii::t('lazada_product_statistic', 'Publish Task Create Successful'),
					'callbackType' => 'navTabAjaxDone',
			));
			Yii::app()->end();
		}else{
			echo $this->failureJson(array(
					'message' => $message,
			));
			Yii::app()->end();
		}
	}	
	
}