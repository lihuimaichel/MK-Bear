<?php
/**
 * conditons rules field manage
 * @author	wx
 * @since	2015-07-29
 */

class ConditionsfieldController extends UebController {
	
	public $modelClass = 'ConditionsField';
	protected $_model = null;
	
	public function init() {
		$this->_model = new ConditionsField();
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
        $this->render('list', array('model'=>$model));
	}
	
	/**
	 * add
	 */
	public function actionCreate() {
		$model = new $this->modelClass;
		
		if (Yii::app()->request->isAjaxRequest && isset($_POST[$this->modelClass])) {
			$model->attributes = $_POST[$this->modelClass];
			$model->setAttribute('create_user_id',Yii::app()->user->id);
			$model->setAttribute('create_time',date('Y-m-d H:i:s'));
			$model->setAttribute('is_enable',1);
			if (!empty($model->unit_code)) {
				$model->setAttribute('is_unit', ConditionsField::UNIT_HAVE);
			}else {
				$model->setAttribute('is_unit', ConditionsField::UNIT_NO);
			}
			if ($model->validate()){
				try {
					$model->setIsNewRecord(true);					
					$flag = $model->save();
					
					$updateLogData = array(
							'type'	=> ConditionsUpdateLog::TYPE_FIELD,
							'update_id'	=> $model->id,
							'update_name' => $model->field_title,
							'update_content' => UebModel::model('ConditionsUpdateLog')->getUpdateMsg($model->attributes, 'N'),
					);
					$ret = UebModel::model('ConditionsUpdateLog')->saveNewData($updateLogData);
					
				} catch (Exception $e) {
					$flag = false;
				}
				if ( $flag ) {
					$jsonData = array(
							'message' => Yii::t('system', 'Add successful'),
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
				echo $this->failureJson(array( 'message' => Yii::t('system', 'Add failure')));
			}
			Yii::app()->end();
		}
		$this->render('create', array('model'=>$model));
	}
	
	/**
	 * update
	 */
	public function actionUpdate($id) {
		$model = $this->loadModel($id);
		$updateName = $model->field_title;
		
		if (Yii::app()->request->isAjaxRequest && isset($_POST[$this->modelClass])) {
			$updateInfo = array_diff_assoc($_POST[$this->modelClass],$model->attributes);
			$model->attributes = $_POST[$this->modelClass];
			$model->setAttribute('modify_user_id',Yii::app()->user->id);
			$model->setAttribute('modify_time',date('Y-m-d H:i:s'));
			if (!empty($model->unit_code)) {
				$model->setAttribute('is_unit', ConditionsField::UNIT_HAVE);
			}else {
				$model->setAttribute('is_unit', ConditionsField::UNIT_NO);
			}
			
			if ($model->validate()){
				try {
					$updateLogData = array(
							'type'	=> ConditionsUpdateLog::TYPE_FIELD,
							'update_id'	=> $id,
							'update_name' => $updateName,
							'update_content' => UebModel::model('ConditionsUpdateLog')->getUpdateMsg($updateInfo),
					);
					$ret = UebModel::model('ConditionsUpdateLog')->saveNewData($updateLogData);
					if ($ret) {
						$flag = $model->save();
					}else {
						$flag = false;
					}
				} catch (Exception $e) {
					$flag = false;
				}
				if ( $flag ) {
					$jsonData = array(
							'message' => Yii::t('system', 'Add successful'),
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
				echo $this->failureJson(array( 'message' => Yii::t('system', 'Add failure')));
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
		$ruleDetailList = UebModel::model('ConditionsDetail')->getRuleDetailListByFieldIds($ids);
		if (count($ruleDetailList)) {
			echo $this->failureJson(array('message'=>Yii::t('conditionsrules', 'The existence of the rule can not be deleted')));
			Yii::app()->end();
		}
		try {
			$updateLogData = array(
					'type'	=> ConditionsUpdateLog::TYPE_FIELD,
					'update_id'	=> 0,
					'update_name' => '',
					'update_content' => 'Delete field: '.$ids,
			);
			$ret = UebModel::model('ConditionsUpdateLog')->saveNewData($updateLogData);
			
			if ($ret) {
				$flag = $model->deleteByPk($ids);
			}else {
				$flag = false;
			}

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
	
	public function actionSelectfield() {
		$model = new $this->modelClass;
		$this->render('_selectfield', array('model'=>$model));
	}
	
	/**
	 * ajax 得到表字段
	 */
	public function actionGetfields() {
		$tabName = $_GET['tab'];
		$modelList = TemplateRulesBase::$modelList;
		$fieldsList = array();
		if (key_exists($tabName, $modelList)) {
			if (substr($tabName, 0, 7) != 'Virtual') {
				//$model = new $tabName();
				//$fieldsList = array_keys($model->attributes);
				//isset($modelList[$tabName]['field']) && $fieldsList = array_intersect($fieldsList, $modelList[$tabName]['field']);
				isset($modelList[$tabName]['field']) && $fieldsList = $modelList[$tabName]['field'];
			}else {
				isset($modelList[$tabName]['field']) && $fieldsList = $modelList[$tabName]['field'];
			}
			
			echo json_encode($fieldsList);
		}else {
			echo json_encode(array());
		}
	}
	
	public function loadModel($id) {
		$model = UebModel::model($this->modelClass)->findByPk((int) $id);
		if ( $model === null )
			throw new CHttpException(404, Yii::t('app', 'The requested page does not exist.'));
	
		return $model;
	}
	
}

