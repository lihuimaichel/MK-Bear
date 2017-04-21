<?php

class SiteController extends UebController {

    public $layout = 'main';
    public $id;

    public $accessIP = array('127.0.0.1', '172.16.*','192.168.10.*','192.168.0.*');

    /**
     * Declares class-based actions.
     */
    public function actions() {
        return array(
            // captcha action renders the CAPTCHA image displayed on the contact page
            'captcha' => array(
                'class'         => 'CCaptchaAction',
                'backColor'     => 0xFFFFFF,
                'minLength'     => 4,  //min length
                'maxLength'     => 4,   //max length
                'testLimit'     => 3,
                'transparent'   => true, 
            ),
            // page action renders "static" pages stored under 'protected/views/site/pages'
            // They can be accessed via: index.php?r=site/page&view=FileName
            'page' => array(
                'class' => 'CViewAction',
            ),
        );
    }

    /**
     * This is the action to handle external exceptions.
     */
    public function actionError() {
        if ($error = Yii::app()->errorHandler->error) {
            if (Yii::app()->request->isAjaxRequest)
                echo $error['message'];
            else
                $this->render('error', $error);
        }
    }

    /**
     * Displays the login page
     */
    public function actionLogin() {

    	if(!$this->ipAccess()){
    		echo 'error';exit;
    	}
		
        $this->layout = '//layouts/login';
        if (!defined('CRYPT_BLOWFISH') || !CRYPT_BLOWFISH) {
             throw new CHttpException(500, "This application requires that PHP was compiled with Blowfish support for crypt().");
        }    
        if(Yii::app()->user->isInitialized && !Yii::app()->user->isGuest){           
            $this->redirect(Yii::app()->homeUrl);
            return;
        }
        $model = new LoginForm;    
        $model->useCaptcha = false;
        // if it is ajax validation request
        if (isset($_POST['ajax']) && $_POST['ajax'] === 'login-form') {
            echo CActiveForm::validate($model);
            Yii::app()->end();
        }
        // collect user input data
        if (isset($_POST['LoginForm'])) {          
            $model->attributes = $_POST['LoginForm'];                
            // validate user input and redirect to the previous page if valid
            if ($model->validate() && $model->login())
                $this->redirect(Yii::app()->user->returnUrl);
        }
//         // display the login form

         $this->render('login', array('model' => $model));

    }

    /**
     * Logs out the current user and redirect to homepage.
     */
    public function actionLogout() {
        Yii::ulog(
            Yii::t('excep', 'The user:{user_name} logout success.', array('{user_name}' =>  Yii::app()->user->name)), 
            Yii::t('system', 'Logout'),    
            'operation', 
            ULogger::LEVEL_SUCCESS                     
        );
        Yii::app()->user->logout();        
        $this->redirect(Yii::app()->homeUrl);
    }

    // display the index form
    public function actionIndex() {

    	if(!$this->ipAccess()){
    		echo 'error';exit;
    	}
    	
        if ( isset($_SESSION['registerScript']) ) {
            unset($_SESSION['registerScript']);
        }
        if ( Yii::app()->user->isGuest ) {
            $this->redirect(array('/site/login'));
        }
        $this->render('index');

    }
    
    public function ipAccess(){
    	$userIP = Yii::app()->request->userHostAddress;
    	$accessIP = $this->accessIP;

    	$access = false;
    	foreach($accessIP as $ip){
    		if( strpos($userIP, str_replace('*','',$ip))!==false ){
    			$access = true;
    			break;
    		}
    	}
    	return $access;
    }
}