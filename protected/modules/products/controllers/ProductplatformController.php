<?php 
/**
 * @author cxy
 * 产品刊登明细记录
 */
class ProductplatformController extends UebController {
	/**
	 * Specifies the access control rules.
	 * This method is used by the 'accessControl' filter.
	 * @return array access control rules
	 */
	public function accessRules(){
		return array(
				array(
						'allow',
						'users' => array('*'),
						'actions' => array('list','categoryview')
				),
		);
	}
	
	/**
	 * 
	 */
	public function actionList(){
	//	echo strlen('/products/productplatform/list/target/dialog');
		$model = UebModel::model('ProductPlatformListing');
		$this->render('list', array(
				'model'             => $model,
		));
	}
	
	public function actionCategoryview(){
		//echo strlen('/products/productplatform/categoryview/target/dialog');
		$category_id = Yii::app()->request->getParam('category_id');
		$model = UebModel::model('ProductPublish');
		$this->render('categoryview', array(
				'model'             => $model,
		));
	}
}