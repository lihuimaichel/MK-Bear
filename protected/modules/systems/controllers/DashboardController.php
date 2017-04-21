<?php
/**
 * @desc 控制台管理
 * @author guoll
 * 2015-9-14
 */
class DashboardController extends UebController {


    /**
     * Specifies the access control rules.
     * This method is used by the 'accessControl' filter.
     * @return array access control rules
     */
    public function accessRules() {
        return array();
    }

    /**
     * List 
     */
    public function actionList() {
    	$model=new Dashboard();
        $this->render('list',array('model' => $model));
    }
    /**添加
     * create a Dashboard
     */
    public function actionCreate(){
    	$model = new DashBoard();
    	if (Yii::app()->request->isAjaxRequest && isset($_POST['DashBoard'])) {
    		$model->setAttribute('dashboard_title', $_POST['DashBoard']['dashboard_title']);
    		$model->setAttribute('dashboard_url', $_POST['DashBoard']['dashboard_url']);
    		$model->setAttribute('is_global', $_POST['DashBoard']['is_global']);
    		$model->setAttribute('type', $_POST['DashBoard']['type']);
    		$model->setAttribute('status', $_POST['DashBoard']['status']);
    			if($model->save()){
    				$flag=true;
    			}else{
    				$flag=false;
    			}
    			if($flag){
    				$jsonData = array(
    						'message' => Yii::t('system', 'Add successful'),
    						'forward' => '/systems/dashboard/list',
    						'navTabId' => 'page'.DashBoard::getIndexNavTabId(),
    						'callbackType' => 'closeCurrent'
    				);
    				echo $this->successJson($jsonData);
    			}else{
    				echo $this->failureJson(array( 'message' => Yii::t('system', 'Add failure')));
    			}
    			Yii::app()->end();
    		}
    	$this->render('create',array('model' => $model));
    }
    
    
    /**
     * 修改
     * update one Dashboard
     * @param cargo company id $id
     */
    public function actionUpdate($id) {
    	$model = $this->loadModel($id);
    	$do = Yii::app()->request->getParam('do');
    	if (Yii::app()->request->isAjaxRequest && isset($_POST['Dashboard'])) {
    	$model->setAttribute('dashboard_title', $_POST['Dashboard']['dashboard_title']);
    		$model->setAttribute('dashboard_url', $_POST['Dashboard']['dashboard_url']);
    		$model->setAttribute('is_global', $_POST['Dashboard']['is_global']);
    		$model->setAttribute('type', $_POST['Dashboard']['type']);
    		$model->setAttribute('status', $_POST['Dashboard']['status']);
    			if($model->save()){
    				$flag=true;
    			}else{
    				$flag=false;
    			}
    			if($flag){
    				$jsonData = array(
    						'message' => Yii::t('system', 'Update successful'),
    						'forward' => '/systems/dashboard/list',
    						'navTabId' => 'page'.DashBoard::getIndexNavTabId(),
    						'callbackType' => 'closeCurrent'
    				);
    				echo $this->successJson($jsonData);
    			}else{
    				echo $this->failureJson(array( 'message' => Yii::t('system', 'Add failure')));
    			}
    			Yii::app()->end();
    		}
    	$this->render('update', array('model' => $model,'do' => $do,));
    }
    
    /**
     * 批量删除
     * delete the Dashboard
     * @throws Exception
     */
    public function actionDelete(){
    	if(Yii::app()->request->isAjaxRequest && isset($_REQUEST['ids'])) {
    			$boolen = UebModel::model('DashBoard')->deleteByPk(explode(",", $_REQUEST['ids']));;
    			if ($boolen) {
    				$flag=true;
    			}else{
    				$flag=false;
    			}
    			if($flag){
    				$jsonData = array(
    						'message' => Yii::t('system', 'Delete successful'),
    						'forward' => '/systems/dashboard/list',
    						'navTabId' => 'page'.DashBoard::getIndexNavTabId(),
    				);
    				echo $this->successJson($jsonData);
    			}else{
    				$jsonData = array(
    						'message' => Yii::t('system', 'Delete failure')
    				);
    				echo $this->failureJson($jsonData);
    			}
    		Yii::app()->end();
    	}
    }
    public function loadModel($id) {
    	$model = UebModel::model('DashBoard')->findByPk((int) $id);
    	if ( $model === null )
    		throw new CHttpException(404, Yii::t('app', 'The requested page does not exist.'));
    
    	return $model;
    }
}
