<?php
/**
 * @desc 站点访问控制器
 * @author Gordon
 * @since 2015-05-22
 */
class MainController extends ErpController {
    
    /** @var string 视图文件 */
    public $layout = 'main';
    public $default_widget = 'ext.eui.widgets';

    public function actions() {
        return array(
            'captcha' => array(
                'class'         => 'CCaptchaAction',
                'backColor'     => 0xFFFFFF,
                'minLength'     => 4,
                'maxLength'     => 4,
                'testLimit'     => 3,
                'transparent'   => true, 
            ),
            'page' => array(
                'class' => 'CViewAction',
            ),
        );
    }

    /**
     * @desc 异常报错
     */
    public function actionError() {
        $error = Yii::app()->errorHandler->error;
        if ($error) {
            if (Yii::app()->request->isAjaxRequest){
                echo $error['message'];
            }else{
                $this->render('error', $error);
            }
        }
    }

    /**
     * @desc 登录页面
     * @throws CHttpException
     */
    public function actionLogin() {
        //设置登录view
        $this->layout = '//layouts/login';
        //检验加密函数支持
        if (!defined('CRYPT_BLOWFISH') || !CRYPT_BLOWFISH) {
            throw new CHttpException(500, "This application requires that PHP was compiled with Blowfish support for crypt().");
        }
    	//客户端IP地址验证
		$ipModel = new IpAccessModel();
		if ( !$ipModel->authenticateIP() ){
		    //已登录跳至首页
		    if(Yii::app()->user->isInitialized && !Yii::app()->user->isGuest){
		        $this->redirect(Yii::app()->homeUrl);
		        return;
		    }
		    $model = new LoginForm;
		    $model->useCaptcha = true;//使用验证码
		    //如果是Ajax请求
		    if (isset($_POST['ajax']) && $_POST['ajax'] === 'login-form') {
		        echo CActiveForm::validate($model);
		        Yii::app()->end();
		    }
		    
		    if ( isset($_POST['LoginForm']) ) {
		        $model->attributes = $_POST['LoginForm'];//登录信息
		        //检验登录信息是否通过
		        if ($model->validate() && $model->login()){
		            $this->redirect(Yii::app()->user->returnUrl);//跳转至用户页面
		        }
		    }  
		}
        $this->render('login', array('model' => $model));//渲染登录界面
    }

    /**
     * @desc 退出登录
     */
    public function actionLogout() {
        Yii::ulog(
            Yii::t('excep', 'The user:{user_name} logout success.', 
            array('{user_name}' =>  Yii::app()->user->name)), 
            Yii::t('system', 'Logout'),    
            'operation',
            ULogger::LEVEL_SUCCESS      
        );
        Yii::app()->user->logout();        
        $this->redirect(Yii::app()->homeUrl);
    }

    /**
     * @desc 加载Index
     */
    public function actionIndex() {  
        if ( isset($_SESSION['registerScript']) ) {
            unset($_SESSION['registerScript']);
        }
        //若检测未登录,跳转至登陆页面
//         if ( Yii::app()->user->isGuest ) {
//             $this->redirect(array('/site/login'));
//         }    
        $this->render('index');
    }
    
    /**
     * @desc 重写渲染块
     * @see CBaseController::beginWidget()
     */
    public function UBeginWidget($className,$properties=array()){
        $className = $this->_buildWidgetName($className);
        return parent::beginWidget($className,$properties);
    }
    
    public function UCreateWidget($className,$properties=array()){
        $className = $this->_buildWidgetName($className);
        return parent::createWidget($className,$properties);
    }
    
    public function UWidget($className,$properties=array(),$captureOutput=false){
        $className = $this->_buildWidgetName($className);
        return parent::widget($className,$properties,$captureOutput);
    }
    
    /**
     * @desc 重写组件Alias
     * @param string $className
     * @param string $type
     * @return Ambigous
     */
    public function _buildWidgetName($className, $type=''){
        return $this->default_widget.'.'.$className;;
    }
}