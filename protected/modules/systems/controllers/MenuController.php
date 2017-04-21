<?php
/**
 * @desc 菜单控制器
 * @author Gordon
 */
class MenuController extends UebController {

    public $modelClass = 'menu';

    /**
     * Specifies the access control rules.
     * This method is used by the 'accessControl' filter.
     * @return array access control rules
     */
    public function accessRules() {
        return array();
    }

    /**
     * menu index
     */
    public function actionIndex() {
        $this->render('index');
    }

    /**
     *  menu left sider
     * 
     * @param type $id
     */
    public function actionSider($id) {
        $data = Menu::model()->findByPk($id);
        $this->render('sider', $data);
    }

    /**
     * @desc 创建菜单
     * @author Gordon
     * @since 2015-07-17
     */
    public function actionCreate() {
        $model = new Menu();
        if (isset($_GET['treeItem'])) {
            $model->menu_parent_id = $_GET['treeItem'];
        }
        if (Yii::app()->request->isAjaxRequest && isset($_POST['Menu'])) {
            $menuParentId = $_POST['Menu']['menu_parent_id'];
            $parentInfo = $model->findByPk($menuParentId);
            $model->attributes = $_POST['Menu'];
            $model->setAttribute('menu_level', $parentInfo['menu_level'] + 1);
            if ($model->validate()) {
                $transaction = Yii::app()->db->beginTransaction();
                try {
                    $model->save();
//                     $auth = Yii::app()->authManager;
//                     $auth->createTask('menu_' . $model->id, $model->menu_display_name);
                    
                    $msg = UebModel::getLogMsg();
                    if (! empty($msg) ) {
                    	Yii::ulog($msg, Yii::t('system', 'System settings'), $_POST['Menu']['menu_display_name']);
                    }
                    
                    $transaction->commit();
                    $flag = true;
                } catch (Exception $e) {
                    $transaction->rollback();
                    $flag = false;
                }
                if ($flag) {
                    $jsonData = array(
                        'message' => Yii::t('system', 'Add successful'),
                        'forward' => '/systems/menu/index',
                        'navTabId' => 'page' . menu::getIndexNavTabId(),
                        'callbackType' => 'closeCurrent'
                    );
                    echo $this->successJson($jsonData);
                }
            } else {
                $flag = false;
            }
            if (!$flag) {
                echo $this->failureJson(array('message' => Yii::t('system', 'Add failure')));
            }
            Yii::app()->end();
        }
        $this->render('create', array('model' => $model));
    }

    /**
     * update menu list
     * 
     * @param type $id
     */
    public function actionUpdate($id) {
        $model = $this->loadModel($id);
        if (Yii::app()->request->isAjaxRequest && isset($_POST['Menu'])) {
            if ($_POST['Menu']) {
                $MenuModel = new Menu();
                $flag = $MenuModel->update($_POST['Menu'], $id);
                $msg = UebModel::getLogMsg();
                if (! empty($msg) ) {
                	Yii::ulog($msg, Yii::t('system', 'System settings'), $_POST['Menu']['menu_display_name']);
                }
                if ($flag) {
                    $jsonData = array(
                        'message'       => Yii::t('system', 'Save successful'),
                        'forward'       => '/systems/menu/index',
                        'navTabId'      => 'page'.Menu::getIndexNavTabId(),
                        'callbackType'  => 'closeCurrent'
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
     * delete menu list by menu id
     * 
     * @param type $id
     */
    public function actionDelete($id) {
        $model = $this->loadModel($id);
        if (Yii::app()->request->isAjaxRequest) {
            $transaction = Yii::app()->db->beginTransaction();
            try {
                $model->delete();
                // AuthItem::model()->findByPk('menu_' . $id)->delete();
                $msg = UebModel::getLogMsg();
                if (! empty($msg) ) {
                	Yii::ulog($msg, Yii::t('system', 'System settings'), $_POST['Menu']['menu_display_name']);
                }
                
                $transaction->commit();
                $flag = true;
            } catch (Exception $e) {
                $transaction->rollback();
                $flag = false;
            }
            if ($flag) {
                $jsonData = array(
                    'message' => Yii::t('system', 'Delete successful'),
                    'navTabId' => 'page' . Menu::getIndexNavTabId(),
                );
                echo $this->successJson($jsonData);
            } else {
                $jsonData = array(
                    'message' => Yii::t('system', 'Delete failure')
                );
                echo $this->failureJson($jsonData);
            }

            Yii::app()->end();
        }
    }

    /**
     * assion resources to menu
     */
    public function actionAssign() {
        $id = $_REQUEST['id'];
        if (isset($_REQUEST['resources'])) {
            $resources = empty($_REQUEST['resources']) ? array() : explode(",", $_REQUEST['resources']);                    
            $transaction = Yii::app()->db->beginTransaction();
            try {
                AuthItem::model()->assignChildResources($id, $resources);
                $transaction->commit();
                $jsonData = array(
                    'message' => Yii::t('system', 'Save successful'),                   
                    'callbackType' => 'closeCurrent'
                );
                echo $this->successJson($jsonData);
            } catch (Exception $e) {               
                $transaction->rollback();
                echo $this->failureJson(array(
                    'message' => Yii::t('system', 'Save failure'))
                );
            }
            Yii::app()->end();
        } else {
            $assignResources = AuthItem::getAssignResources();
            $assignedResources = AuthItem::getChildrenByParent('menu_'.$id); 
        }
        $this->render('assign', array(
            'assignResources'       => $assignResources,
            'assignedResources'    => $assignedResources    
        ));
    }

    /**
     * users or role resources
     */
    public function actionUlist() {
        if (isset($_REQUEST['resources'])) {
            $resources = empty($_REQUEST['resources']) ? array() : explode(",", $_REQUEST['resources']);
            $auth = Yii::app()->authManager;
            $transaction = Yii::app()->db->beginTransaction();
            try {
                AuthItem::model()->addRoleResources($_REQUEST['roleId'], $resources);
                $transaction->commit();
                $jsonData = array(
                    'message' => Yii::t('system', 'Save successful'),
                );
                echo $this->successJson($jsonData);
                Yii::app()->end();
            } catch (Exception $e) {
                $transaction->rollback();
                $jsonData = array(
                    'message' => Yii::t('system', 'Save failure')
                );
                echo $this->failureJson($jsonData);
                Yii::app()->end();
            }
        } else {
            $resources = array();
            $loginRoleResources = array();
            if (isset($_REQUEST['roleId'])) {
                $resources = AuthItem::model()->getResourcesByRoleId($_REQUEST['roleId']);
            }
            if (!User::isAdmin()) {
                $loginRoleResources = AuthItem::getResourcesByRoleId(User::getLoginUserRoles());
            }
        }
        
        $this->render('ulist', array(
            'resources' => $resources,
            'loginRoleResources' => $loginRoleResources
        ));
    }

    public function loadModel($id) {
        $model = Menu::model()->findByPk((int) $id);
        if ($model === null)
            throw new CHttpException(404, Yii::t('app', 'The requested page does not exist.'));
        return $model;
    }  

    /**
     * reflesh Histroy Url
     * @author Nick 2013-12-6
     */
    public function actionRefreshHistoryUrl(){
    	$this->render('_historyurl');
    }
    
    public function actionTaskTree(){
    	$role = $_REQUEST['role'];
    	$uid = intval($_REQUEST['uid']);
    	if( isset($_REQUEST['saveSubmit']) && $_REQUEST['saveSubmit']==1 ){
    		if(!$uid){
    			throw new CException('未指定用户');
    		}
    		//求出保存的所有菜单
    		$parentMenuID = $_REQUEST['menu_id'];
    		$subMenus = Menu::getSubMenuIdsById($parentMenuID);
    		$menus = array();
    		foreach($subMenus as $item){
    			$menus[$item] = 'menu_'.$item;
    		}
    		//记录新的权限项
    		$newAuth = array();
    		foreach( explode(',',$_REQUEST['resources']) as $item){
    			$newAuth[$item] = $item;
    		}
    		$auth = Yii::app()->authManager;
    		//根据id得到用户角色名(用户名作为个人角色名)
    		$roleInfo = UebModel::model('user')->getUserNameById($uid);
    		$roleName = $roleInfo['user_name'];
    		//检查用户是否有个人角色，没有则创建
    		$check = UebModel::model('AuthItem')->checkItemExist($roleName, 2);
    		if( !$check ){
    			$auth->createRole($roleName, UebModel::model('user')->role_mark.'_'.$roleInfo['user_full_name']);
    		}
    		$authItem = new CAuthItem($auth, $roleName, 2);
    		$authItem->revoke($uid);
    		//先解除角色绑定
    		$allOperation = Yii::app()->db->createCommand()
    						->select('child')
    						->from(UebModel::model('AuthItemChild')->tableName())
    						->where('parent IN ('.MHelper::simplode($menus).')')
    						->queryColumn();

    		UebModel::model('AuthItemChild')->deleteAll('parent = "'.$roleName.'" AND child IN ('. MHelper::simplode($allOperation) .')');//解除授权

    		foreach($newAuth as $item){
    			if( !$authItem->hasChild($item) ){
    				$authItem->addChild($item);//角色添加授权
    			}
    		}
    		$authItem->assign($uid);//添加角色绑定
    		$jsonData = array(
    				'message' => Yii::t('system', 'Save successful'),
    				'callbackType' => 'closeCurrent'
    		);
    		echo $this->successJson($jsonData);
    		Yii::app()->end();
    	}
    	$menuList = Menu::getTreeList();
    	if($_REQUEST['menu']){
			$menus = array();
			$subMenu = $menuList[$_REQUEST['menu']]['submenu'];
			foreach($subMenu as $key=>$items){
				$menus[$key] = 'menu_'.$key;
			}
    		$operationArr = array();
    		$allItem = Yii::app()->db->createCommand()
			    		->select('*')
			    		->from('ueb_auth_item AS i')
			    		->leftJoin('ueb_auth_item_child AS c', 'i.name = c.child')
			    		->where('i.type = 0')
			    		->andWhere('c.child IS NOT NULL AND c.parent IN ('.MHelper::simplode($menus).')')
			    		->queryAll();
    		foreach($allItem as $item){
    			$operationArr[$item['parent']][] = $item['child'];
    		}
    		$this->render('taskTree',array(
    				'operationArr'	=> $operationArr,
    				'uid'			=> $uid,
    				'menuID'		=> $_REQUEST['menu'],
    				'menuList'		=> $menuList,
    		    	'loginRoleResources' => AuthItem::getResourcesByRole(User::getLoginUserRoles($uid), $menus),
    		));
    	}else{
    		$this->render('_tabs',array(
    				'menuList' 		=> $menuList,
    				'uid'			=> $uid,
    		));
    	}
    }
    
    /**
     * Show Operation List
     * @author Gordon
     * @since 2014-06-05
     */
    public function actionOperationList(){
    	$id = $_REQUEST['uid'];
    	$taskName = isset($_REQUEST['task']) && $_REQUEST['task'] ? $_REQUEST['task'] : '';
    	if ( isset($_REQUEST['resources']) ) {
    		$resources = empty($_REQUEST['resources']) ? array() : explode(",", $_REQUEST['resources']);
    		$transaction = Yii::app()->db->beginTransaction();
    		try {
    			$auth = Yii::app()->authManager;
    			//根据id得到用户角色名(用户名作为个人角色名)
    			$roleInfo = UebModel::model('user')->getUserNameById($id);
    			$roleName = $roleInfo['user_name'];
    			//检查用户是否有个人角色，没有则创建
    			$check = UebModel::model('AuthItem')->checkItemExist($roleName, 2);
    			if( !$check ){
    				$auth->createRole($roleName, UebModel::model('user')->role_mark.'_'.$roleInfo['user_full_name']);
    			}
    			$authItem = new CAuthItem($auth, $roleName, 2);
    			$authItem->revoke($id);
    			//先解除角色绑定
    			$allOperation = Yii::app()->db->createCommand()
    							->select('child')
    							->from(UebModel::model('AuthItemChild')->tableName())
    							->where('parent = "'.$taskName.'"')
    							->queryColumn();
    			if (!User::isAdmin()) {
	    			$loginRoleResources = AuthItem::getResourcesByRoleId(User::getLoginUserRoles());//分配人所拥有的权限
// 	    			$a = array();
// 	    			foreach($loginRoleResources as $item){
// 	    				if( strpos($item, 'resource_logistics_abroadship_ship')===0 ){
// 	    					$a[] = $item;
// 	    				}
// 	    			}
// 	    			var_dump($a);exit;
	    			$operations = array_intersect($allOperation, $loginRoleResources);
    			}else{
    				$operations = $allOperation;
    			}
    			//var_dump($resources);exit;
    			UebModel::model('AuthItemChild')->deleteAll('parent = "'.$roleName.'" AND child IN ("'. implode('","',$allOperation) .'")');//解除授权
    			foreach($resources as $item){
    				//if( UebModel::model('AuthItem')->checkItemExist($item, CAuthItem::TYPE_OPERATION) ){
    					if( !$authItem->hasChild($item) ){
    						$authItem->addChild($item);//角色添加授权
    					}    					
    				//}
    			}
    			$authItem->assign($id);//添加角色绑定
    			$transaction->commit();
    			$jsonData = array(
    					'message' => Yii::t('system', 'Save successful'),
    					'callbackType' => 'closeCurrent'
    			);
    			echo $this->successJson($jsonData);
    		} catch (Exception $e) {
    			$transaction->rollback();
    			echo $this->failureJson(array(
    					'message' => Yii::t('system', 'Save failure'))
    			);
    		}
    		Yii::app()->end();
    	}else{
	    	$operation = UebModel::model('AuthItem');
	    	$operationList = $operation->getOperationOfTask($taskName);
	    	$operationInfo = array();
	    	$loginRoleResources = array();
	    	if (!User::isAdmin()) {
	    		$loginRoleResources = AuthItem::getResourcesByRoleId(Yii::app()->user->name);//分配人所拥有的权限
	    	}
	    	foreach($operationList as $key=>$item){
	    		$info = $operation->getItemInfoByName($key);
	    		$resourceArr = array();
	    		foreach($item as $itm){
	    			if( in_array($itm, $loginRoleResources) || User::isAdmin() ){
		    			$operationArr = explode("_",substr($itm,9));
		    			$resourceArr[$operationArr[0]][$operationArr[1]][] = $operationArr[2];
	    			}
	    		}
	    		$operationInfo[]  = array(
	    				'name' => $info['description'],
	    				'operation' => $resourceArr,
	    		);
	    	}
	    	$auth = Yii::app()->authManager;
	    	//根据id得到用户角色名(用户名作为个人角色名)
	    	$roleInfo = UebModel::model('user')->getUserNameById($id);
	    	$roleName = $roleInfo['user_name'];
	    	
	    	$assignedResources = UebModel::model('AuthItem')->getResourcesByRoleId($roleName);
//  	    	var_dump($assignedResources);exit;
	    	$this->render('operationList', array(
	            'operationList' => $operationInfo,
	    		'uid' => $id,
	    		'task' => $taskName,
	    		'assignedResources' => $assignedResources ? $assignedResources : array(),
	        ));
    	}
    }

    
    /**
     * @desc 自动分配操作资源到订单
     * @author Gordon
     * @since 2014-08-09
     */
    public function actionDistributeresourceauto(){
    	//取消所有操作资源和订单的绑定
    	$flag = AuthItemChild::model()->cancelMenuAssign();
    	//查出所有启用订单
    	$menus = Menu::model()->getAllMenu();
    	//循环订单，找出跟菜单action同级的其他action，进行分组
    	$menuArr = $topMenuArr = array();
    	foreach($menus as $menu){
    		if( $menu->menu_url ){
	    		$parseMenu = Menu::model()->parseMenuUrl($menu->menu_url);
	    		if( !empty($parseMenu) ){
	    			if($parseMenu['controller']){
		    			$menuArr[$parseMenu['module'].'-'.$parseMenu['controller']] = $menu->id;
	    			}else{
	    				$menuArr[$parseMenu['module']] = $menu->id;
	    			}
	    		}
    		}
    	}
    	//查询所有操作资源
    	$resources = AuthItem::getAllOperationNames();
    	//将操作绑定在订单下
    	$auth = Yii::app()->authManager;
    	$total = $num = 0;
    	//将无法归类的资源统一放在一个菜单下
    	$specialMenu = Menu::model()->findByAttributes(array('menu_url' => 'auth'));
    	foreach($resources as $resource){
    		$total++;
    		$ps = explode("_", $resource);
    		$key = $ps[1].'-'.$ps[2];
    		$add = false;
    		if( isset($menuArr[$key]) ){//可以放在子菜单的放在子菜单
    			$add = true;
    			$menuId = $menuArr[$key];
    		}elseif( isset($menuArr[$ps[1]]) ){//没有找到相应子菜单的找主菜单
    			$add = true;
    			$menuId = $menuArr[$ps[1]];
    		}else{
    			if($specialMenu!=null){
    				$add = true;
    				$menuId = $specialMenu->id;
    			}
    		}
    		if( $add ){
    			if( $menuId > 0 ){
    				$parent = 'menu_'.$menuId;
    				$authItem = new CAuthItem($auth, $parent, 1);
    				if( !$authItem->hasChild($resource) ){
    					$authItem->addChild($resource);
    					$num++;
    				}
    			}	
    		}
    	}
    	echo 'Total:'.$total.',Success:'.$num;
    }
}
