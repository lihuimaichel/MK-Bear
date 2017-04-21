<?php
/**
 * @desc lazada param template
 * @author	wx
 * @since	2015-07-29
 */

class LazadaparamtemplateController extends UebController {
	
	public $modelClass = 'LazadaParamTemplate';
	protected $_model = null;
	
	public function init() {
		$this->_model = new LazadaParamTemplate();
		parent::init();
	}
	
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
	 * list page
	 */
	public function actionList() {
		$model = new $this->modelClass;
		$model_name = Yii::app()->request->getParam('model_name');
		$this->render('list', array(
				'model' => $model,'modelName'=>$model_name,
		));
	}
	
	/**
	 * selelct template get template_name
	 */
	public function actionGetcode() {
		$id = Yii::app()->request->getParam('id');
		if ( empty($id) ) die('');
		$id = explode(",", $id);
		$paramTplInfo = UebModel::model('LazadaParamTemplate')->getParamTplById($id);
		if($paramTplInfo){
			echo json_encode($paramTplInfo);
		}else{
			echo '';
		}
		die();
	}
	
	/**
	 * @desc 添加新模板
	 */
	public function actionCreate() {
		$model = new $this->modelClass;
		if (Yii::app()->request->isAjaxRequest && isset($_POST[$this->modelClass])) {
			$model->attributes = $_POST[$this->modelClass];
			$model->setAttribute('create_user_id',Yii::app()->user->id);
			$model->setAttribute('create_time',date('Y-m-d H:i:s'));
			$model->setAttribute('is_enable',1);
			
			if ($model->validate()){
				try {
					$model->setIsNewRecord(true);					
					$flag = $model->save();
				} catch (Exception $e) {
					$flag = false;
				}
				if ( $flag ) {
					$jsonData = array(
							'message' => Yii::t('system', 'Add successful'),
							'forward' => '/lazada/lazadaparamtemplate/list',
							'navTabId' => 'page'.Menu::model()->getIdByUrl('/lazada/lazadaparamtemplate/list'),
							'callbackType' => 'closeCurrent'
					);
					echo $this->successJson($jsonData);
				}
			}else{
				$flag = false;
			}
			if (! $flag) {
				echo $this->failureJson(array( 'message' => Yii::t('system', 'Add failure')));
			}
			Yii::app()->end();
		}
		$this->render('create', array('model'=>$model));
	}
	
	/**
	 * @desc 更新模板
	 * @param unknown $id
	 */
	public function actionUpdate($id) {
		$model = $this->loadModel($id);
		
		if (Yii::app()->request->isAjaxRequest && isset($_POST[$this->modelClass])) {
			$updateInfo = array_diff_assoc($_POST[$this->modelClass],$model->attributes);
			$model->attributes = $_POST[$this->modelClass];
			$model->setAttribute('modify_user_id',Yii::app()->user->id);
			$model->setAttribute('modify_time',date('Y-m-d H:i:s'));
			
			if ($model->validate()){
				try {
					$flag = $model->save();
				} catch (Exception $e) {
					$flag = false;
				}
				if ( $flag ) {
					$jsonData = array(
							'message' => Yii::t('system', 'Update successful'),
							'forward' => '/common/conditionsfield/list',
							'navTabId' => 'page'.Menu::model()->getIdByUrl('/common/conditionsfield/list'),
							'callbackType' => 'closeCurrent'
					);
					echo $this->successJson($jsonData);
				}
			}else{
				$flag = false;
			}
			if (! $flag) {
				echo $this->failureJson(array( 'message' => Yii::t('system', 'Update failure')));
			}
			Yii::app()->end();
		}
		
		$this->render('update', array('model'=>$model));
	}
	
	/**
	 * Delete
	 */
	public function actionDelete() {
		$model = new $this->modelClass;
		$ids = explode(",", $_REQUEST['ids']);
		try {
			$flag = $model->deleteByPk($ids);

			if (!$flag) {
				throw new Exception('Delete failure');
			}
			$jsonData = array(
					'message' => Yii::t('system', 'Delete successful'),
			);
			echo $this->successJson($jsonData);
			
		} catch (Exception $e) {
			$jsonData = array(
					'message' => Yii::t('system', 'Delete failure')
			);
			echo $this->failureJson($jsonData);
		}
		Yii::app()->end();
	}
	
	public function loadModel($id) {
		$model = UebModel::model($this->modelClass)->findByPk((int) $id);
		if ( $model === null )
			throw new CHttpException(404, Yii::t('app', 'The requested page does not exist.'));
	
		return $model;
	}
	
}

