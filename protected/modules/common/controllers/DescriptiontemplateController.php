<?php
/**
 * @desc 描述模板控制器
 * @author zhangF
 *
 */
class DescriptiontemplateController extends UebController {
	
	/** @var DescriptionTemplate Instance */
	protected $_model = null;
	
	/**
	 * (non-PHPdoc)
	 * @see CController::init()
	 */
	public function init() {
		$this->_model = new DescriptionTemplate();
		parent::init();
	}
	
	/**
	 * @desc 访问规则
	 * @see CController::accessRules()
	 */
	public function accessRules() {
		return array(
			array(
					'allow',
					'users' => array('*'),
					'actions' => array('list')
			),			
		);
	}
	
	/**
	 * 显示列表
	 */
	public function actionList() {
		
		//$model = DescriptionTemplate::model();
		$model_name = Yii::app()->request->getParam('model_name');
		$this->render('list', array(
			'model' => $this->_model,'modelName'=>$model_name
		));
	}
	
	/**
	 * @desc 创建标题描述模板
	 */
	public function actionCreate() {
		
		if (Yii::app()->request->isPostRequest) {			
			$this->_model->attributes = $_POST['DescriptionTemplate'];
			$userId = Yii::app()->user->id;
			$this->_model->setAttribute('create_user_id', $userId);
			$this->_model->setAttribute('modify_user_id', $userId);
			if (!$this->_model->save()) {
				echo $this->failureJson(array(
					'message' => Yii::t('system', 'Save Failure'),
				));
			} else {
				echo $this->successJson(array(
					'message' => Yii::t('system', 'Save successful'),
					'callbackType' => 'closeCurrent',
					'navTabId' => 'page' . DescriptionTemplate::getIndexNavTabId(),
				));
			}
			Yii::app()->end();
		}
		$this->render('create', array(
			'model' => $this->_model,
		));
	}
	
	/**
	 * @desc 修改描述模板
	 */
	public function actionUpdate() {
		//echo("mobanupdate");exit;
		$id = Yii::app()->request->getParam('id');
		$descriptionTemplateMode = $this->_model->findByPk($id);
		if (empty($descriptionTemplateMode)) {
			$this->failureJson(array(
					'message' => Yii::t('system', 'Update failure'),
			));
		}		
		if (Yii::app()->request->isPostRequest) {
			$descriptionTemplateMode->attributes = $_POST['DescriptionTemplate'];
			if (!$descriptionTemplateMode->save()) {
				echo $this->failureJson(array(
						'message' => Yii::t('system', 'Save Failure'),
				));				
			} else {
				echo $this->successJson(array(
					'message' => Yii::t('system', 'Save Successful'),
					'callbackType' => 'closeCurrent',
					'navTabId' => 'page' . DescriptionTemplate::getIndexNavTabId(),
				));			
			}
			Yii::app()->end();
		}
		$this->render('update', array(
			'model' => $descriptionTemplateMode
		));
	}
	
	/**
	 * @desc 预览模板
	 */
	public function actionPreview() {
		$id = Yii::app()->request->getParam('id');
		$model = $this->_model->findByPk($id);
		$this->render('preview', array('model' => $model));
	}
	
	/**
	 * @desc 删除描述模板
	 */
	public function actionDelete() {
		$ids = Yii::app()->request->getParam('ids');
		if (!empty($ids)) {
			if (!$this->_model->deleteAll("id in ($ids)")) {
				echo $this->failureJson(array(
					'message' => Yii::t('system', 'Delete failure')
				));
				Yii::app()->end();
			} else {
				echo $this->successJson(array(
					'message' => Yii::t('system', 'Delete successful'), 
				));
				Yii::app()->end();
			}
		}
	}
	
	/**
	 * selelct template get template_name
	 */
	public function actionGetcode() {
		$id = Yii::app()->request->getParam('id');
		if ( empty($id) ) die('');
		$id = explode(",", $id);
		$paramTplInfo = UebModel::model('DescriptionTemplate')->getParamTplById($id);
		if($paramTplInfo){
			echo json_encode($paramTplInfo);
		}else{
			echo '';
		}
		die();
	}
}