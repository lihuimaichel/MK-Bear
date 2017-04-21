<?php
class EbaysiteController extends UebController
{
	/**
	 * @todo ebay站点管理列表
	 * @author Michael
	 * @since 2015/08/05
	 */
	public function actionIndex()
	{
 		$model = UebModel::model('EbaySite');
		$this->render('index', array(
				'model' => $model,
		));
	}
	
	/**
	 * @todo ebay添加站点
	 * @author Michael
	 * @since 2015/08/05
	 */
	public function actionCreate() {
		$model = new Ebaysite();
		if(Yii::app()->request->isAjaxRequest && isset($_POST['Ebaysite'])){
			$model->attributes = $_POST['Ebaysite'];
			if($model->validate()){
				try {
					$flag = $model->save();
					$flag = true;
				}catch (Exception $e){
					$flag = false;
				}
				if($flag){
					$jsonData = array('message'=>Yii::t('system','Add successful'),'forward'=>'/ebay/ebaysite/index','navTabId' => 'page' . $model->getIndexNavTabId(),'callbackType'=>'closeCurrent');
					echo $this->successJson($jsonData);
				}	
			}else {
				$flag = false;
			}
			if(!$flag){
				echo $this->failureJson(array('message'=>Yii::t('system', 'Add failure')));
			}
		}else {
			$this->render('create',array('model'=>$model));
		}
	}
	
	/**
	 * @todo ebay编辑站点
	 * @author Michael
	 * @since 2015/08/05
	 */
	public function actionUpdate($id)
	{
		$model = $this->loadModel($id);
		if(Yii::app()->request->isAjaxRequest && isset($_POST['Ebaysite'])){
			if($model->validate()){
				$model->attributes = $_POST['Ebaysite'];
				$flag = $model->save();
				if($flag){
					$jsonData = array(
							'message' => Yii::t('system', 'Save successful'),
							'forward' =>'/ebay/ebayaccount/index',
							'navTabId'=>'page'.$model->getIndexNavTabId(),
							'callbackType'=>'closeCurrent'
					);
					echo $this->successJson($jsonData);
				}else{
					$flag = false;
				}
			}
			if(!$flag){
				echo $this->failureJson(array('message'=>Yii::t('system','Save failure')));
			}
		}else {
				$this->render('update',array('model' =>$model));
		}
	}
	
	/**
	 * @todo ebay开启站点
	 * @author Michael
	 * @since 2015/08/05
	 */
	public function actionOpen()
	{
		if(Yii::app()->request->isAjaxRequest && isset($_REQUEST['ids'])){
			$ids = explode(',',$_REQUEST['ids']);
			foreach($ids as $key=>$id){
				$model = $this->loadModel($id);
				$flag = UebModel::model('Ebaysite')->getDbConnection()->createCommand()->update(Ebaysite::tableName(), array('is_open'=>Ebaysite::SITE_OPEN),'id=:id', array(':id' =>$id));
			}
			if($flag){
				$jsonData = array(
						'message' =>Yii::t('system', 'Save successful'),
						'forward' =>'/ebay/ebaysite/index',
						'navTabId'=>'page' . Ebaysite::getIndexNavTabId(),
				);
				echo $this->successJson($jsonData);
			}
			if(!$flag) {
				echo $this->failureJson(array('message'=>Yii::t('system','Save failure')));
			}
			Yii::app()->end();
		}
	}
	
	/**
	 * @todo ebay关闭站点
	 * @author Michael
	 * @since 2015/08/05
	 */
	public function actionClose()
	{
		if(Yii::app()->request->isAjaxRequest && isset($_REQUEST['ids'])){
			$ids = explode(',',$_REQUEST['ids']);
			foreach($ids as $key=>$id){
				$model = $this->loadModel($id);
				$flag = UebModel::model('Ebaysite')->getDbConnection()->createCommand()->update(Ebaysite::tableName(), array('is_open'=>Ebaysite::SITE_CLOSE),'id=:id', array(':id' =>$id));
			}
			if($flag){
				$jsonData = array(
						'message' =>Yii::t('system', 'Save successful'),
						'forward' =>'/ebay/ebaysite/index',
						'navTabId'=>'page' . Ebaysite::getIndexNavTabId(),
				);
				echo $this->successJson($jsonData);
			}
			if(!$flag) {
				echo $this->failureJson(array('message'=>Yii::t('system','Save failure')));
			}
			Yii::app()->end();
		}
	}
	
	/**
	 * @todo 实例化Model模型
	 * @param int $id
	 */
	public function loadModel($id){
		$model = Ebaysite::model()->findByPk((int)$id);
		if($model === null){
			throw new CHttpException(404, Yii::t('app', 'The requested page does not exist.'));
		}
		return $model;
	}
}
