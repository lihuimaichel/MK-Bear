<?php

/**
 * @package Ueb.modules.users.controllers
 * 
 * @author Bob <zunfengke@gmail.com>
 */
class UserlistshowController extends UebController {
    
	public function accessRules(){
		return array(
			array(
				'allow',
				'users' => array('*'),
				'actions' => array('refresh'),
			),
		);
	}
	
    /**
     * user list show field refresh
     */
    public function actionRefresh() {      
        $fieldValues = Yii::app()->request->getParam('fieldValues');
        $className = Yii::app()->request->getParam('className');
        $on = Yii::app()->request->getParam('on');
        $userId = Yii::app()->user->id;     
        $model = new UserListShow();  
        $classNameKey = empty($on) ? $className : $className .'-'. $on;
        if (Yii::app()->request->isAjaxRequest) { 
            $data = array(
                'show_key'  => $classNameKey,
                'user_id'   => $userId
            );
            $model->attributes = $data;
            try {
                $row = $model->findByAttributes($data);                 
                if (! empty($row) ) {
                    $row->setAttribute('show_values', $fieldValues);
                    $row->save();
                } else {                  
                    $model->setAttribute('show_values', $fieldValues);
                    $model->setAttribute('show_key', $classNameKey);
                    $model->setAttribute('user_id', $userId);                                               
                    $model->save();
                }
               
                Yii::app()->end('1');
            } catch ( UsersException $e ) {}             
        }
        Yii::app()->end('0');
    }    
    
}
