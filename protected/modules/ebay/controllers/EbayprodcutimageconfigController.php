<?php
class EbayprodcutimageconfigController extends UebController
{
	/**
	 * @todo ebay站点管理列表
	 * @author Michael
	 * @since 2015/08/05
	 */
	public function actionIndex()
	{
 		$model = UebModel::model("EbayProductImageConfig");
 		$this->render("index", array('model'=>$model));
	}
	/**
	 * @desc 保存
	 */
	public function actionSavedata(){
		
	}
}