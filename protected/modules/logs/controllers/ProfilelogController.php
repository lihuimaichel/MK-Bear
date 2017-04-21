<?php

/**
 * @package Ueb.modules.logs.controllers
 * 
 * @author Bob <zunfengke@gmail.com>
 */
class ProfilelogController extends UebController {

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
        $model = UebModel::model('profileLog');   
        $model->setScenario('search');
        $this->render('list', array(
            'model'     => $model,           
        ));
    }
    
    public function actionIndex() {         
        $this->render('index');
    }
}
