<?php
class EbaysiteparamconfigController extends UebController{


	/*
	 * 列表
	 */
	public function actionList(){
		$model = new EbaySiteParamConfig();
		$this->render('list',array('model'=>$model));
	}

	/*
	 * 增加
	 */
	public function actionAdd(){
		$model = UebModel::model("EbaySiteParamConfig");
		if(Yii::app()->request->isAjaxRequest && isset($_POST['EbaySiteParamConfig'])){

			$model->attributes = $_POST['EbaySiteParamConfig'];
			$userId = Yii::app()->user->id;
			$model->setAttribute('create_user_id', $userId);
			$model->setAttribute('create_time', date('Y-m-d H:i:s'));
			if ($model->validate()) {
				$model->setIsNewRecord(true);
				$flag = $model->save();
				if ($flag) {
					$forward = Yii::app()->createUrl("ebay/ebaysiteparamconfig/list");
					$jsonData = array(
						'message' => Yii::t('system', 'Save successful'),
						'forward' => $forward,
						'navTabId' => '',
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

		$this->render("add",array('model'=>$model));
	}

	/*
	 * 修改
	 */
	public function actionUpdate(){
		$id = Yii::app()->request->getParam('id');
		$model = UebModel::model("EbaySiteParamConfig")->findByPk($id);
		if(Yii::app()->request->isAjaxRequest && isset($_POST['EbaySiteParamConfig'])){

			$model->attributes = $_POST['EbaySiteParamConfig'];
			$userId = Yii::app()->user->id;
			$model->setAttribute('update_user_id', $userId);
			$model->setAttribute('update_time', date('Y-m-d H:i:s'));
			if ($model->validate()) {
				$flag = $model->save();
				if ($flag) {
					$forward = Yii::app()->createUrl("ebay/ebaysiteparamconfig/list");
					$jsonData = array(
						'message' => Yii::t('system', 'Save successful'),
						'forward' => $forward,
						'navTabId' => '',
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

		$this->render("add",array('model'=>$model));
	}

	/*
	 * 批量删除
	 */
	public function actionBatchDelete(){
		$ids = Yii::app()->request->getParam("ids");
		try{
			if(empty($ids)) throw new Exception("参数错误");

			$model = new EbaySiteParamConfig();
			$flag = $model->getDbConnection()->createCommand()->delete($model->tableName(), "id in(".$ids.")");
			if ($flag) {
				$forward = Yii::app()->createUrl("/ebay/ebaysiteparamconfig/list");
				$jsonData = array(
					'message' 		=> 	Yii::t('system', 'Operate Successful'),
					'forward' 		=> 	$forward,
					'navTabId' 		=> 	'',
					'callbackType' 	=> 	''
				);
				echo $this->successJson($jsonData);
			}else{
				throw new Exception("操作失败！");
			}
		}catch(Exception $e){
			echo $this->failureJson(array(
				'message' => Yii::t('system', 'Operate failure')));
		}
		Yii::app()->end();
	}
}