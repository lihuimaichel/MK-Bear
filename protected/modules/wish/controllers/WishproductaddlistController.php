<?php
/**
 * @desc 产品刊登
 * @author lihy
 *
 */
class WishproductaddlistController extends UebController{
	public function accessRules(){
		return array(
				array(
					'allow', 
					'users'=>'*', 
					'actions'=>array('index'))
		);
	}
	/**
	 * @desc 待刊登产品列表
	 */
	public function actionIndex(){
		$model = new WishProductAdd;
		$disabledCheckbox = isset($_REQUEST['upload_status']) && $_REQUEST['upload_status'] == WishProductAdd::WISH_UPLOAD_SUCCESS ? true:false;
		$this->render('index', array(
									'model'=>$model,
									'disabledCheckbox'=>$disabledCheckbox
								));
	}
	/**
	 * @desc 批量删除
	 * @throws Exception
	 */
	public function actionBatchdel(){
		try{
			$ids = Yii::app()->request->getParam('ids');
			if(empty($ids)){
				throw new Exception(Yii::t('wish_listing', 'No chose any one'));
			}
			$ids = explode(",", $ids);
			$productAddModel = new WishProductAdd;
			if($productAddModel->deleteProductAddInfoByIds($ids, 'upload_status!=:upload_status', array(':upload_status'=>WishProductAdd::WISH_UPLOAD_SUCCESS))){
				//删除子产品
				$productVariantsAddModel = new WishProductVariantsAdd;
				$productVariantsAddModel->deleteProductVariantsAddInfoByAddIds($ids, 'upload_status!=:upload_status', array(':upload_status'=>WishProductAdd::WISH_UPLOAD_SUCCESS));
				echo $this->successJson(array(
								'message'=>Yii::t('system', 'Delete successful'),
						));
				Yii::app()->end();
			}
			throw new Exception(Yii::t('system', 'Delete failure'));
		}catch(Exception $e){
			echo $this->failureJson(array(
				'message'=>$e->getMessage()
			));
		}
	}
}