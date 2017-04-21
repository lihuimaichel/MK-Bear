<?php
/**
 * 
 * @author qzz
 *
 */
class EbayimageaddController extends UebController{

	/*
	 * 列表
	 */
	public function actionList(){
		$model = new EbayProductImageAdd();
		$this->render("list", array("model"	=>$model));
	}

	/**
	 * @desc 批量删除
	 */
	public function actionBatchdel(){
		$ids = Yii::app()->request->getParam("ids");
		if($ids){
			$idarr = explode(",", $ids);
			$ebayImageAddModel = new EbayProductImageAdd();
			$res = $ebayImageAddModel->batchDel($idarr);
			if($res){
				echo $this->successJson(array(
					'message'	=>	Yii::t('system', 'Successful')
				));
				Yii::app()->end();
			}

		}
		echo $this->failureJson(array(
			'message'	=>	"操作失败"
		));

		Yii::app()->end();
	}
}