<?php

/**
 * @package Ueb.modules.users.controllers
 * 
 * @author Bob <zunfengke@gmail.com>
 */
class UsersController extends UebController {

    public $modelClass = 'User';

    /**
     * Specifies the access control rules.
     * This method is used by the 'accessControl' filter.
     * @return array access control rules
     */
    public function accessRules() {
        return array();
    }

    /**
     * users management
     */
    public function actionIndex() {   
        $this->render('index');
    }

    /**
     * users list
     */
    public function actionList() {      
        $model = UebModel::model('User');   
        $model->setScenario('search');
        $departmentId = Yii::app()->request->getParam('department_id','');
        
		$uid = Yii::app()->user->id;
		$depId = User::model()->getDepIdById($uid);
		$departmentId = empty($departmentId)?$depId:$departmentId;
        //判断用户是否是超级管理员
        $isAdmin = UserSuperSetting::model()->checkSuperPrivilegeByUserId($uid);
		if(!$isAdmin){
			//上级部门的不允许查看
			$parentDepIds = Dep::getUserAllParentDepIds($uid);
			if(in_array($departmentId, $parentDepIds)){
				$departmentId = -1;
			}
		}
        $this->render('list', array(
            'model' => $model, 'departmentId'=>$departmentId, 'isAdmin'=>$isAdmin
        )); 
    }
    
    
    /**
     * add users
     */
    public function actionCreate() {
        $model = new User();
        if (Yii::app()->request->isAjaxRequest && isset($_POST['User'])) {
            $_POST['User']['user_password']=  $model->getCryptPassword($_POST['User']['user_password']);
            $model->attributes = $_POST['User'];
            if ($model->validate()) {   
                try {
                    $flag = $model->save();
                    if( $flag ){
	                    //为每个用户创建一个角色
	                    $auth = Yii::app()->authManager;
	                    $auth->createRole($_POST['User']['user_name'], $model->role_mark.'_'.$_POST['User']['user_full_name']);
	                    //分配角色给自己
	                    $auth->assign($_POST['User']['user_name'],$model->attributes['id']);
                    }
                } catch (Exception $e) { 
                    //echo $e->getMessage();
                    $flag = false;
                }
                if ($flag) {
                    $jsonData = array(
                        'message' => Yii::t('system', 'Add successful'),
                        'forward' => '/users/users/index',
                        'navTabId' => 'page' . User::getIndexNavTabId(),
                        'callbackType' => 'closeCurrent'
                    );
                    echo $this->successJson($jsonData);
                }
            } else {
                $flag = false;
            }
            if (! $flag) {
                echo $this->failureJson(array('message' => Yii::t('system', 'Add failure')));
            }
            Yii::app()->end();
        }
        $departmentId = Yii::app()->request->getParam('department_id','');
        if (!empty($departmentId)) $model->department_id = $departmentId;
        $this->render('create', array('model' => $model));
    }
    
    /**
     * update users list
     * 
     * @param type $id
     */
    public function actionUpdate($id) {
        $model = $this->loadModel($id);
        if (Yii::app()->request->isAjaxRequest && isset($_POST['User'])) {
            
            $model->attributes = $_POST['User'];
            if ($model->validate()) {
                $flag = $model->save();
                if ($flag) {
                    $jsonData = array(
                        'message' => Yii::t('system', 'Save successful'),
                        'forward' => '/users/users/index',
                        'navTabId' => 'page' . User::getIndexNavTabId(),
                        'callbackType' => 'closeCurrent'
                    );
                }
                echo $this->successJson($jsonData);
            } else {
                $flag = false;
            }
            if (!$flag) {
                echo $this->failureJson(array(
                    'message' => Yii::t('system', 'Save failure')));
            }
            Yii::app()->end();
        }   
        $this->render('update', array('model' => $model));
    }
    
    /**
     * delete users
     *
     * @param type $id
     */
    public function actionDelete() {
    	if (Yii::app()->request->isAjaxRequest && isset($_REQUEST['ids'])) {
    		try {
    			$flag = Yii::app()->db->createCommand()
    			->delete(User::model()->tableName(), " id IN({$_REQUEST['ids']})");
    			if ( ! $flag ) {
    				throw new Exception('Delete failure');
    			}
    			$jsonData = array(
    					'message' => Yii::t('system', 'Delete successful'),
                                        'forward' => '/users/users/index',
                                        'navTabId' => 'page' . User::getIndexNavTabId(),
                                        //'callbackType' => 'closeCurrent'
    			);
    			echo $this->successJson($jsonData);
    		} catch (Exception $exc) {
    			$jsonData = array(
    					'message' => Yii::t('system', 'Delete failure')
    			);
    			echo $this->failureJson($jsonData);
    		}
    		Yii::app()->end();
    	}
    }
    
    
    /**
     * 批量启用 或停用用户的状态
     * @throws Exception
     */
    public function actionBatchchangestatus(){
    
    	if (Yii::app()->request->isAjaxRequest && isset($_REQUEST['ids'])) {
//    		var_dump($_REQUEST['ids']);
    		$flag = Yii::app()->request->getParam('type')=='0' ? false :true;
//    		var_dump($flag);exit('1111111111');
    		try{
    			$flag = UebModel::model($this->modelClass)->changeUserStatus($_REQUEST['ids'],$flag);
    			if (!$flag) {
    				throw new Exception('Oprate failure');
    			}
    			$jsonData = array(
    					'message' => Yii::t('system', 'Operate Successful'),
    			);
    			echo $this->successJson($jsonData);
    		}catch (Exception $exc) {
    			$jsonData = array(
    					'message' => Yii::t('system', 'Operate failure')
    			);
    			echo $this->failureJson($jsonData);
    		}
    		Yii::app()->end();
    	}
    }
    /**
     * access role users action
     */

    public function actionUlist() {
        if (isset($_REQUEST['roleId'])) {
            $roleId = $_REQUEST['roleId'];
        }
        if (isset($_REQUEST['id'])) {
            $auth = Yii::app()->authManager;
            $authItem = new CAuthItem($auth, $roleId, 2);
            $userIds = explode(",", $_REQUEST['id']);
            foreach ($userIds as $userId) {
                if (!$authItem->isAssigned($userId)) {
                    $authItem->assign($userId);
                }
            }
        }
        if (isset($_REQUEST['ids'])) {
            $transaction = Yii::app()->db->beginTransaction();
            try {
                $auth = Yii::app()->authManager;
                $authItem = new CAuthItem($auth, $roleId, 2);
                $userIds = explode(",", $_REQUEST['ids']);
                foreach ($userIds as $userId) {
                    if ($authItem->isAssigned($userId)) {
                        $authItem->revoke($userId);
                    }
                }
                $transaction->commit();
                $jsonData = array(
                    'message' => Yii::t('system', 'Delete successful'),
                    'ids' => $_REQUEST['ids']
                );
                echo $this->successJson($jsonData);
            } catch (Exception $exc) {
                $transaction->rollback();
                $jsonData = array(
                    'message' => Yii::t('system', 'Delete failure')
                );
                echo $this->failureJson($jsonData);
            }
            Yii::app()->end();
        }
        $models = AuthAssignment::model()->getUlist($roleId);
        $this->render('ulist', array(
            'models' => $models,
        	'role' => $roleId,
        ));
    }
    
    /**
     * change user password
     */
    public function actionChange($id) {              
         if (Yii::app()->request->isAjaxRequest && isset($_POST['User'])) {
             $model = $this->loadModel($id);
             $model->attributes = $_POST['User'];
             if( $_POST['User']['new_password'] != NULL) {                
                try {
                    $model->setAttribute('user_password', $model->getCryptPassword($_POST['User']['new_password']));
                    $flag = $model->update();
                } catch (Exception $e) {                  
                    $flag = false;
                }
                if ($flag) {
                    $jsonData = array(
                        'message' => Yii::t('system', 'Save successful'),                        
                        'callbackType' => 'closeCurrent'
                    );
                    echo $this->successJson($jsonData);
                }
            } else {
                $flag = false;
            }
            if (!$flag) {
                echo $this->failureJson(array('message' => Yii::t('system', 'Save failure')));
            }
            Yii::app()->end();
         }
         $model = new User('change');
         $info = User::model()->findByPk((int) $id);
         $model->setAttribute('user_password', $info->user_password);
         $model->setAttribute('id', $info->id);
         $this->render('change', array(
            'model' => $model,
        ));
    }  
    
    /**
     * 复制权限
     */
    public function actionCopyauth(){
    	$uid = 0;
    	if( isset($_REQUEST['uid']) ){
    		$uid = $_REQUEST['uid'];
    	}
    	
    	if( isset($_POST['copyAuth']) && $_POST['copyAuth']==1 ){//保存权限
    		$fromId = isset($_POST['from_user_id']) ? $_POST['from_user_id'] : 0;
    		$toIds = isset($_POST['to_user_id']) ? explode(',',$_POST['to_user_id']) : array();
    		if( !$fromId || empty($toIds) ){
    			throw new CException('No User To Operate!');
    		}
    		$userInfo = UebModel::model('user')->getUserNameById($fromId);
    		//取出被选择的用户的所有操作权限
    		$resouce = UebModel::model('AuthItem')->getResourcesByRoleId($userInfo['user_name']);
    		$auth = Yii::app()->authManager;
    		$transaction = Yii::app()->db->beginTransaction();
    		try {
    			foreach($toIds as $id){
    				$userInfo = UebModel::model('user')->getUserNameById($id);
    				$authItem = new CAuthItem($auth, $userInfo['user_name'], 2);
    				$authItem->revoke($id);//解除角色绑定
    				UebModel::model('AuthItemChild')->deleteAll('parent = "'.$userInfo['user_name'].'" AND child LIKE "resource_"');//解除授权
    				foreach($resouce as $item){
    					$authItem->addChild($item);//角色添加授权
    				}
    				$authItem->assign($id);//添加角色绑定
    			}
    			$transaction->commit();
    			$jsonData = array(
    					'message' => Yii::t('system', 'Save successful'),
    					'callbackType' => 'closeCurrent'
    			);
    			echo $this->successJson($jsonData);
    		} catch (Exception $e){
    			$transaction->rollback();
    			echo $this->failureJson(array(
    					'message' => Yii::t('system', 'Save failure'))
    			);
    		}
    		Yii::app()->end();
    	}
    	if( !$uid ){
    		throw new CException('Please Choose A User');
    	}
    	$model = UebModel::model('user');
    	$this->render('copyauth', array(
    			'model' => $model,
    			'uid' 	=> $uid
    	));
    }
    
    /**
     * 获取用户ID
     */
    public function actionGetuserid(){
    	$id = Yii::app()->request->getParam('id');
    	if ( empty($id) ) exit;
    	$ids = explode(",", $id);
    	$userArr = array();
    	foreach($ids as $item){
    		$userName = MHelper::getUsername($item);
    		$userArr[] = array(
    				'id' => $item,
    				'name' => $userName, 
    		);
    	}
    	echo json_encode($userArr);
    	exit;
    }
    
    public function loadModel($id) {      
        $model = User::model()->findByPk($id);
        if ($model === null)
            throw new CHttpException(404, Yii::t('app', 'The requested page does not exist.'));
        return $model;
    }  
    
    
    public function actionSetprivilege(){
    	$id = Yii::app()->request->getParam('id');
    	$menuModel = new Menu();
    	$menuGroup = $menuModel->getTreeList(null, false, true);
    	$userInfo = User::model()->getUserNameById($id);
    	$menuPrivilege = MenuPrivilege::model()->getMenuPrivilegeByUserId($id);
    	$selectedMenu = explode(",", $menuPrivilege);
    	
    	$this->render("setprivilege", array(
    			'uid'			=>		$id,
    			'model' 		=> 		$menuModel,
    			'userInfo'		=>		$userInfo,
    			'menuGroup'		=>		$menuGroup,
    			'selectedMenu'	=>		$selectedMenu,
    			
    	));
    }
    
    
    public function actionSaveprivilege(){
    	try{
    		$menus = Yii::app()->request->getParam('menu');
    		$type = Yii::app()->request->getParam('type');
    		if(empty($menus)){
    			throw new Exception(Yii::t('user', 'No Selected Menu'));
    		}
    		$uid = Yii::app()->request->getParam('uid');
    		if(empty($uid)){
    			throw new Exception(Yii::t('user', 'No Selected User'));
    		}
    		$loginId = Yii::app()->user->id;
    		if($type != 2 && $uid == $loginId){
    			throw new Exception(Yii::t('user', 'No Setting Yourself!'));
    		}
    		$menuArr = array();
    		$userIdArr = explode(",", $uid);
    		foreach ($userIdArr as $id){
    			if($type == 2 && $id == $loginId){
    				continue;
    			}
    			foreach ($menus as $menuId=>$submenu){
    				$menuArr[] = $menuId;
    				$menuArr = array_merge($menuArr, $submenu);
    			}
    			$res = MenuPrivilege::model()->updateMenuPrivilegeById($id, $menuArr);
    		}
    		
    		if(!$res){
    			throw new Exception(Yii::t('system', 'Save failure'));
    		}
    		echo $this->successJson(array('message'=>Yii::t('system', 'Save successful')));
    	}catch (Exception $e){
    		echo $this->failureJson(array('message'=>$e->getMessage()));
    	}
    }
    
    
    public function actionBatchsetprivilege(){
    	try{
	    	$ids = Yii::app()->request->getParam('ids');
	    	if(empty($ids)){
	    		throw new Exception(Yii::t('user', 'No Selected User'));
	    	}
	    	$idarr = explode(",", $ids);
	    	$menuModel = new Menu();
	    	$menuGroup = $menuModel->getTreeList(null, false, true);
	    	$userList = User::model()->getUserListByIDs($idarr);
	    	$this->render("batchsetprivilege", array(
	    			'uid'			=>		$ids,
	    			'userList'		=>		$userList,
	    			'model' 		=> 		$menuModel,
	    			'menuGroup'		=>		$menuGroup,
	    			 
	    	));
    	}catch (Exception $e){
    		echo $this->failureJson(array("message"=>$e->getMessage()));
    	}
    }
    
    
    
    public function actionDeptempuser(){
    	$arr = UebModel::model('User')->getEmpByDept(trim($_POST['dept']));
    	//超级管理，主管 组长可以查看全部自己部门下面所有销售人员
    	$userId = Yii::app()->user->id;
    	$isSuper = UebModel::model("UserSuperSetting")->checkSuperPrivilegeByUserId($userId);
    	$isAdmin = UebModel::model("AuthAssignment")->checkCurrentUserIsAdminister($userId, '');
    	if(!$isSuper && !$isAdmin){
    		$isGroup = UebModel::model("AuthAssignment")->checkCurrentUserIsGroup($userId, '');
    		if(!$isGroup){
    			if(isset($arr[$userId]))
    				$arr = array($userId=>$arr[$userId]);
    			else
    				$arr = array();
    		}
    	}
    	
    	print_r(json_encode($arr));
    	exit;
    
    }
}
