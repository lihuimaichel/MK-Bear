<?php

class AuthItem extends UsersModel {

    /**
     * Returns the static model of the specified AR class.
     * @return CActiveRecord the static model class
     */
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    /**
     * @return string the associated database table name
     */
    public function tableName() {
        return 'ueb_auth_item';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules() {
        return array();
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels() {
        return array();
    }
    
    /**
     * get child auth items 
     * 
     * @param type $itemId
     * @return array $data2
     */
    public static function getChildAuthItems($itemId) {          
        $data = array();
        $data2 = self::getChildrenByParent($itemId);      
        if ( empty($data2) ) {
            return null;
        }
        $data2 = array_merge($data, $data2);              
        while ( count($data2) != count($data) ) {           
            $data = array_unique(array_merge($data, $data2));              
            unset($data2);
            $data2 = self::getChildrenByParent($data);
            $data2 = array_unique(array_merge($data, $data2));          
        }
        
        return $data2;
    }


    /**
     * get all resources by role id
     */  
    public static function getResourcesByRoleId($roleId) {
        $resources = array();
        $data = self::getChildAuthItems($roleId); 
        if ( empty($data) ) {
            return null;
        }
        $roles = Role::getAllRoles();
        foreach ($data as $key => $val ) {
            if ( !in_array($val, $roles)) {
                $resources[] = $val;
            }
        }
        
        return $resources;
    }
    
    /**
     * @desc 根据角色获取操作权限列表
     * @param string $roleId
     */
    public static function getResourcesByRole($roleId,$parents='') {
    	$resources = Yii::app()->db->createCommand()
    				->select('child')
    				->from('ueb_auth_item_child')
    				->where('parent IN ('.MHelper::simplode(array('superpei')).')')
    				->andWhere('child LIKE "resource_%"')
    				->queryColumn();
    	$resourcesChild = array();
    	
    	if($parents){
	    	$resourcesChild = Yii::app()->db->createCommand()
		    				->select('child')
		    				->from('ueb_auth_item_child')
		    				->where('parent IN ('.MHelper::simplode($parents).')')
		    				->andWhere('child LIKE "resource_%"')
		    				->queryColumn();
    	}    	
    	return $resourcesChild ? array_intersect($resources,$resourcesChild) : $resources;
    }

    /**
     * add role resources
     * 
     * @param type $roleId
     * @param type $resources
     * @return boolean
     */
    public function addRoleResources($roleId, $resources) {      
        $roles = Role::getAllRoles();        
        $this->filterNodeResources($roleId, $roles, $resources);
        $auth = Yii::app()->authManager;
        $authItem = new CAuthItem($auth, $roleId, 2);
        if (empty($resources)) {
            return true;
        }       
        foreach ($resources as $val) {
            if ( $val == 'treeItem_0') { continue;}
            if (!$authItem->hasChild($val)) {
                $authItem->addChild($val);
                $id = substr($val, 5);              
                $menuInfo = Menu::model()->findByPk($id);
                if (! empty($menuInfo) && !empty($menuInfo['menu_parent_id'])) {
                    if (!$authItem->hasChild('menu_'.$menuInfo['menu_parent_id'])) {
                        $authItem->addChild('menu_'.$menuInfo['menu_parent_id']);
                    }
                }
            }
        }

        return true;
    }

    /**
     * fiter role and role children resources
     * @param type $roleId
     * @param type $roles
     * @param type $resources
     */
    public function filterNodeResources($roleId, $roles, $resources) {
        $auth = Yii::app()->authManager;
        $authItem = new CAuthItem($auth, $roleId, 2);
        $childAuthItems = self::getChildAuthItems($roleId);       
        $childRoles = array();
        if (! empty($childAuthItems) ) {
            foreach ($childAuthItems as $key => $val) {
                if ( in_array($val, $roles) ) {
                    $childRoles[] = $val;
                    unset($childAuthItems[$key]);
                }
            }
        }       
        $oldResources = $childAuthItems;      
        unset($childAuthItems);
        if (! empty($oldResources) ) {
            foreach ($oldResources as $val) {
                if ( !in_array($val, $resources)) {                                       
                    $id = substr($val, 5);                                     
                    $subMenuIds = Menu::getSubMenuIdsById($id);                        
                    $deleteParentFlag = true; 
                    if (! empty($subMenuIds) ) {
                        foreach ($subMenuIds as $subMenuId ) {
                            if (in_array('menu_'.$subMenuId, $resources)) {
                                $deleteParentFlag = false;
                                break;
                            }
                        }
                    }                        
                    if ($deleteParentFlag ) {
                        $authItem->removeChild($val);
                        foreach ($childRoles as $childRoleId ) {
                            $childAuthItem = new CAuthItem($auth, $childRoleId, 2);
                             if ( $childAuthItem->hasChild($val)) {
                                $childAuthItem->removeChild($val);
                            }
                        }
                    }                                      
                }               
            }
        }    
    }
    
     /**
     * get children by parent
     * 
     * @param type $parent
     * @return type
     */
    public static function getChildrenByParent($parent) {  
        $parent = (array) $parent;
        return Yii::app()->db->createCommand()
                   ->select('child')
                   ->from(AuthItemChild::model()->tableName())  
                   ->where(array('IN', 'parent', $parent))
                   ->queryColumn();
    }
    
    public static function getAllTaskNames() {
          return Yii::app()->db->createCommand()
                   ->select('name')
                   ->from(self::tableName())  
                   ->where("type = 1")
                   ->queryColumn();
    }
    
    public static function getAllOperationNames(){
    	return Yii::app()->db->createCommand()
				    	->select('name')
				    	->from(self::tableName())
				    	->where("type = 0")
				    	->queryColumn();
    }

    /**
     * get assign resources
     */
    public static function getAssignResources() {       
//         $taskNames = self::getAllTaskNames();
//         $data = array();
//         foreach ($taskNames as $taskName) {
//             if (substr($taskName, 0, 5) != 'menu_') {
//                 $arr = explode("_", substr($taskName, 9));
//                 $data[$arr[0]][$arr[1]][$arr[2]] = $taskName;
//             }
//         }
    	$operationNames = self::getAllOperationNames();
    	$data = array();
    	foreach ($operationNames as $operationName) {
    		$arr = explode("_", substr($operationName, 9));
    		$data[$arr[0]][$arr[1]][$arr[2]] = $operationName;
    	}
        return $data;
    }
    
    /**
     * assign child resources
     * 
     * @param type $id
     * @param type $resources
     */
    public function assignChildResources($id, $resources) {       
        $parent = 'menu_' . $id;        
        $this->filterChildResources($parent, $resources);
        $auth = Yii::app()->authManager;
        $authItem = new CAuthItem($auth, $parent, 1);    
        foreach ($resources as $val) {
            if (! $authItem->hasChild($val)) {
                $authItem->addChild($val);
            }
        }
    }
    
    /**
     * filter assigned resources 
     * 
     * @param type $parent 
     * @param type $resources
     */
    public function filterChildResources($parent, $resources) {        
        $assignedResources = self::getChildrenByParent($parent);     
        $auth = Yii::app()->authManager;
        $authItem = new CAuthItem($auth, $parent, 1);
        if (! empty($assignedResources) ) {
            foreach ($assignedResources as $val) {
                if (!in_array($val, $resources)) {
                    if ($authItem->hasChild($val)) {
                        $authItem->removeChild($val);
                    }
                }
            }
        }       
    }
    
    public function getOperationOfTask($taskName = ''){
    	$where = '';
    	if($taskName){
    		$where .= ' AND c.parent = "'.$taskName.'"';
    	}
    	$operationArr = array();
    	$list = Yii::app()->db->createCommand()
    					->select('*')
    					->from(self::tableName().' AS i')
    					->leftJoin('ueb_auth_item_child AS c', 'i.name = c.child')
    					->where('i.type = 0')
    					->andWhere('c.child IS NOT NULL'.$where)
    					->queryAll();
    	foreach($list as $item){
    		$operationArr[$item['parent']][] = $item['child'];
    	}
    	
    	return $operationArr;
    }
    
    public function getItemInfoByName($name){
    	return $this->findByPk($name);	
    }
    
	public function checkItemExist($item,$type){
		$result = Yii::app()->db->createCommand()
						->select('name')
						->from(self::tableName())
						->where('name = "'.$item.'" AND type = "'.$type.'"')
						->queryColumn();
		if( !empty($result) ){
			return true;	
		}else{
			return false;
		}
	}
	
}