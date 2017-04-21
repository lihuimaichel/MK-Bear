<?php
class EbayproductdescController extends UebController
{
	public function actionList(){
		$model = new EbayProductDescriptionTemplate();
		$this->render("list", array('model'=>$model));
	}

	public function actionBatchdelete(){
		$IDs = Yii::app()->request->getParam("ids");
		$model = EbayProductDescriptionTemplate::model();
		$flag = $model->getDbConnection()->createCommand()->delete($model->tableName(), "id in({$IDs})");
		if ($flag) {
			$forward = '';
			$jsonData = array(
					'message' => Yii::t('system', 'Delete successful'),
					'forward' => Yii::app()->createUrl('ebay/ebayproductdesc/list'),
					'navTabId' => 'page' . EbayProductDescriptionTemplate::getIndexNavTabId(),
					//'callbackType' => 'closeCurrent'
			);
			echo $this->successJson($jsonData);
		}else{
			echo $this->failureJson(array(
				'message' => Yii::t('system', 'Delete failure')));
		}
		
		Yii::app()->end();
	}
	
	public function actionAddtemplate(){
		$model = EbayProductDescriptionTemplate::model();
		if (Yii::app()->request->isAjaxRequest && isset($_POST['EbayProductDescriptionTemplate'])) {
			$model->attributes = $_POST['EbayProductDescriptionTemplate'];
			$userId = Yii::app()->user->id;
			$model->setAttribute('create_user_id', $userId);
			$model->setAttribute('create_time', date('Y-m-d H:i:s'));
			$model->setAttribute('modify_user_id', $userId);
			$model->setAttribute('modify_time', date('Y-m-d H:i:s'));
		
			if ($model->validate()) {
				$model->setIsNewRecord(true);
				$flag = $model->save();
				if ($flag) {
					$forward = '';
					$jsonData = array(
							'message' => Yii::t('system', 'Save successful'),
							'forward' => $forward,
							'navTabId' => 'page' . EbayProductDescriptionTemplate::getIndexNavTabId(),
							'callbackType' => 'closeCurrent'
					);
					echo $this->successJson($jsonData);
				}
			} else {
				$flag = false;
			}
			if (!$flag) {
				echo $this->failureJson(array(
						'message' => Yii::t('system', 'Save failure')));
			}
			Yii::app()->end();
		}
		$this->render("addtemplate", array('model'=>$model));
	}
	
	public function actionUpdatetemplate(){
		$id = Yii::app()->request->getParam('id');
		$model = EbayProductDescriptionTemplate::model()->findByPk($id);
		if (Yii::app()->request->isAjaxRequest && isset($_POST['EbayProductDescriptionTemplate'])) {
    		$model->attributes = $_POST['EbayProductDescriptionTemplate'];
    		$userId = Yii::app()->user->id;
    		$model->setAttribute('modify_user_id', $userId);
    		$model->setAttribute('modify_time', date('Y-m-d H:i:s'));
    		
    		if ($model->validate()) {
    			$flag = $model->save();
    			if ($flag) {
    				$forward = '';
    				$jsonData = array(
    						'message' => Yii::t('system', 'Save successful'),
    						'forward' => $forward,
    						'navTabId' => 'page' . EbayProductDescriptionTemplate::getIndexNavTabId(),
    						'callbackType' => 'closeCurrent'
    				);
    				echo $this->successJson($jsonData);
    			}
    		} else {
    			$flag = false;
    		}
    		if (!$flag) {
    			echo $this->failureJson(array(
    					'message' => Yii::t('system', 'Save failure')));
    		}
    		Yii::app()->end();
		}
		$this->render("updatetemplate", array('model'=>$model));
	}
	/**
	 * @DESC 预览模板
	 */
	public function actionPreviewtemplate(){
		$id = Yii::app()->request->getParam('id');
		$accountID = trim(Yii::app()->request->getParam('account_id'));
		$siteID = trim(Yii::app()->request->getParam('site_id', ''));
		$language = trim(Yii::app()->request->getParam('language', ''));
		$sku = Yii::app()->request->getParam('sku', '0001');
		if($id){
			$templateInfo = EbayProductDescriptionTemplate::model()->getDescriptionTemplateByID($id);
			if(empty($templateInfo)) exit("Not find the Description Template!!!");
			$accountID = isset($templateInfo['account_id']) ? $templateInfo['account_id'] : 0;
			$language = isset($templateInfo['language_code']) ? $templateInfo['language_code'] : '';
		}
		echo EbayProductAdd::model()->getDescription($sku, $accountID, $siteID, $language);
	}

	/*
	 * 模版更新http为https
	 * /ebay/ebayproductdesc/updatetemplatedescription/id/xxx
	 */
	public function actionUpdateTemplateDescription(){

		$templateId = Yii::app()->request->getParam('id');
		$limit = Yii::app()->request->getParam('limit',1000);

		$ebayProductDescriptionTemplateModel = new EbayProductDescriptionTemplate();

		$command = $ebayProductDescriptionTemplateModel->getDbConnection()->createCommand()
			->from($ebayProductDescriptionTemplateModel->tableName() . " as t")
			->select("t.id, t.template_content");
		if ($templateId) {
			$command->where("t.id = '".$templateId."'");
		}
		$command->limit($limit);
		$templateList = $command->queryAll();

		if ($templateList) {
			//匹配替换
			$searchCss = array('http://ebayapp.vakind.info', 'http://www.vakind.info');
			$replaceCss = array('https://usergoodspic004.photoebucket.com');
			foreach ($templateList as $templateInfo) {
				//替换css
				$descriptionNew = str_replace($searchCss, $replaceCss, $templateInfo['template_content']);
				$updateData = array('template_content'=>$descriptionNew);
				$res = $ebayProductDescriptionTemplateModel->getDbConnection()->createCommand()
					->update($ebayProductDescriptionTemplateModel->tableName(),$updateData, "id=" . $templateInfo['id']);
				if($res){
					echo "ok:".$templateInfo['id'];echo "<br>";
				}else{
					echo "fail:".$templateInfo['id'];echo "<br>";
				}
			}
		}
	}
}