<?php
/**
 * @desc lazada 在线产品统计
 * @author zhangF
 *
 */
class LazadaproductstatisticController extends UebController {
	
	/** @var object 模型实例 **/
	protected $_model = null;
	
	/**
	 * (non-PHPdoc)
	 * @see CController::init()
	 */
	public function init() {
		$this->_model = new LazadaProductStatistic();
	}
	
	/**
	 * @desc 列表页
	 */
	public function actionList() {
		$siteID = Yii::app()->request->getParam('site_id', '0');
		$accountID = Yii::app()->request->getParam('account_id', '0');
		$this->render('list', array(
			'model' => $this->_model,
			'siteID' => $siteID,
			'accountID' => $accountID,
		));
	}
	
	/**
	 * @desc 批量添加刊登任务
	 * @throws Exception
	 */
	public function actionBatchPublish() {
		$siteID = Yii::app()->request->getParam('site_id');
		$accountID = Yii::app()->request->getParam('account_id');
		$skus = Yii::app()->request->getParam('ids');
		$category_id = Yii::app()->request->getParam('online_category_id');
		if (empty($siteID) || empty($accountID)) {
			echo $this->failureJson(array(
				'message' => Yii::t('lazada_product_statistic', 'Invalid Site or Account'),
			));
			Yii::app()->end();
		}
		if (empty($category_id)) {
			echo $this->failureJson(array(
				'message' => Yii::t('lazada_product_statistic', 'Invalid Category'),
			));
			Yii::app()->end();
		}
		$accountInfo = LazadaAccount::model()->getApiAccountByIDAndSite($accountID, $siteID);
		$apiAccountID = $accountID;
		$accountID = $accountInfo['id'];
		$skuArr = explode(',', $skus);
		$skuArr = array_filter($skuArr);
		if (empty($skuArr)) {
			echo $this->failureJson(array(
					'message' => Yii::t('lazada_product_statistic', 'Not Chosen Products'),
			));
			Yii::app()->end();
		}
		//批量添加到待上传列表
		$lazadaProductAddModel = LazadaProductAdd::model();
		$message = '';
		foreach ($skuArr as $sku) {
	       //检测是否有权限去刊登该sku
	       //上线后打开注释---yangsh 2016-12-14
	       if(! Product::model()->checkCurrentUserAccessToSaleSKUNew($sku,$accountID,Platform::CODE_LAZADA)){
	               echo $this->failureJson(array(
	                    'message' => Yii::t('system', 'Not Access to Add the SKU')
	               ));
	               Yii::app()->end();
	        }
                    
			//$return = $lazadaProductAddModel->productAdd($sku, $accountID, $siteID);
			$return = $lazadaProductAddModel->productAddByCategory($sku, $accountID, $siteID, $category_id, LazadaProductAdd::ADD_TYPE_BATCH);
			if ($return['status'] == '0') {
			    $message .= $sku.$return['message'].'<br/>';
			}
		}
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
	}
}