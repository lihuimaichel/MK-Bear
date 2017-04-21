<?php

/**
 * @desc 控制台角色配置
 * @author guoll
 * 2015-9-14
 */
class DashboardroleController extends UebController {

    public $modelClass = 'DashboardRole';

    /**
     * Specifies the access control rules.
     * This method is used by the 'accessControl' filter.
     * @return array access control rules
     */
    public function accessRules() {
        return array();
    }

    public function actionIndex() {
        $this->render('index');
    }

    /**
     * role or dashboard
     */
    public function actionList(){
    	
        $resources = array();
        if (isset($_REQUEST['resources'])) {
            $resources = empty($_REQUEST['resources']) ? array() : explode(",", $_REQUEST['resources']);
            try {
                DashBoardRole::model()->delRoleDashboard($_REQUEST['roleId']);
                DashBoardRole::model()->addRoleDashboard($_REQUEST['roleId'], $resources);
                $jsonData = array(
                    'message' => Yii::t('system', 'Save successful'),
                );
                echo $this->successJson($jsonData);
            } catch (Exception $e) {
                $jsonData = array(
                    'message' => Yii::t('system', 'Save failure')
                );
                echo $this->failureJson($jsonData);
            }
            Yii::app()->end();
        } else {
        	
            if (isset($_REQUEST['roleId'])) {
                $resources = DashBoardRole::model()->getDashboardByRoleId($_REQUEST['roleId']);
                foreach ($resources as $key=>$val){
                    $resources[$key] = 'dashboard_'.$val['dashboard_id'];
                }
            }
        }
       
        $dashboardList = DashBoard::getDashboardList();
        $this->render('list', array(
            'resources' => $resources,
            'dashboardList' => $dashboardList
        ));
    }

    /**
     * Personalized Settings from dashboard
     */
    public function actionPerson() {
        if (Yii::app()->request->isAjaxRequest && isset($_POST['UserConfig'])) {
            DashBoardRole::saveUserDashboardConfig($_POST['UserConfig']);
        }
    }
    public function actionAsynRefresh(){
    	$type=$_REQUEST['type'];
    	if($type){
    		echo $type;
    	}  
    	exit;	
    }
}
