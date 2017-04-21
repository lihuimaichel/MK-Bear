<?php

/**
 * File Action Log Controller
 * 
 * @author Nick 2013-11-13
 */
class FileActionLogController extends UebController {

    /**
     * Specifies the access control rules.
     * This method is used by the 'accessControl' filter.
     * @return array access control rules
     */
    public function accessRules() {
        return array();
    }

    /**
     * log list
     */
    public function actionList() {                     
        $model = UebModel::model('fileActionLog');   
        $model->setScenario('search');
        $this->render('list', array(
            'model'     => $model,           
        ));
    }
    
    public function actionIndex() {         
        $this->render('index');
    }
    
    /**
     * delete
     * @author Nick 2013-11-13
     */
    public function actionDelete(){
    	if (Yii::app()->request->isAjaxRequest && isset($_REQUEST['ids'])) {
    		try {
    			$flag = Yii::app()->db->createCommand()
    			->delete(FileActionLog::model()->tableName(), " id IN({$_REQUEST['ids']})");
    			if ( ! $flag ) {
    				throw new Exception('Delete failure');
    			}
    			$jsonData = array(
    					'message' => Yii::t('system', 'Delete successful'),
    					'target' => 'ajax',
    					'rel' => 'apilogBox',
    			);
    			echo $this->successJson($jsonData);
    		} catch (Exception $exc) {
    			$jsonData = array(
    					'message' => Yii::t('system', 'Delete failure'),
    			);
    			echo $this->failureJson($jsonData);
    		}
    		Yii::app()->end();
    	}
    }
    
}
