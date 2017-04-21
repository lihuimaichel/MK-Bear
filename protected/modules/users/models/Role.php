<?php

class Role extends UsersModel {

   	public $parent = ''; 
   	/**
    * Role List
    */
   	public $roleArr = array(
			'logistics' => array(
   					'pack' 	=> 'packer',
					'pick'	=> 'picker',
					'scan'	=> 'scaner',
			),
   			'purchase' => array(
					'create' => 'create_user',
   					'purchase' => 'purchase_user',
			),
   	);
   
   
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
       return array(         
            array( 'name, description, parent', 'required'),    
            array( 'name', 'unique'), 
            array('parent', 'length', 'max' => 50),
        );               
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels() {
        return array(
            'name'                  => Yii::t('users', 'Role Code'),
            'description'           => Yii::t('users', 'Role Name'),   
            'parent'                => Yii::t('users', 'Parent Role'),
        );
    }
    
    public static function getIndexNavTabId() {
        return Menu::model()->getIdByUrl('/users/access/index');
    } 
    
    /**
     * filter role code, blank space replace with _
     * @return String $name
     */
    public static function filterName($name) {
        $name = str_replace("  ", " ", $name);
        $name = str_replace(" ", "_", $name); 
        
        return $name;          
    }
    
    /**
     * back role code
     * 
     * @param type $name
     */
    public static function backFilterName($name) {
         return str_replace("_", " ", $name); 
    }


    /**
     * get role tree list 
     * 
     * @return array 
     */
    public static function getTreeList($roleIds  = null) {      
        $list = AuthItemChild::getAll();
        $data = array();
        $childArr = array();
        $roles = self::getAllRoles();
        $rolesTag =  array();
        foreach ($list as $key => $val) {
            if (in_array( $val['parent'], $roles) ) {
                $childArr[] = $val['child'];
                if (! in_array( $val['child'], $roles) ) {
                   $val['child'] = null;
                }
                $data[] = array('child' => $val['child'], 'parent' => $val['parent']);
                $rolesTag[] = $val['parent'];
            } 
            if ( in_array( $val['child'], $roles )) {
                 $rolesTag[] = $val['child'];
            }
        }
        foreach ($data as $key => $val) {           
            if (! in_array( $val['parent'], $childArr)) {
                $data[$val['parent']] = array('child' => $val['parent'], 'parent' => 0);
            }
            if ( empty($val['child']) ) {
                if ( self::search($val['parent'], $key, $data)) {
                    unset($data[$key]);
                }
            }
        }
        $otherRoles = array_diff($roles, $rolesTag);
        unset($rolesTag);
        foreach ($otherRoles as $key => $val) {
            $data[$val] = array( 'child' => $val, 'parent' => 0);
        }
        $result = self::tree($data); 
        unset($data);
        if (! empty($roleIds) ) {
            foreach ($roleIds as $roleId ) {
                $parents = AuthItemChild::getParentsByChild($roleId);
                if (! empty($parents)) {
                    $data = $result;
                    //unset($result);
                    while (! empty($parents)) {
                        $parent = array_pop($parents);
                        $data = $data[$parent]['children'];
                    }
                    $res[$roleId] = $data[$roleId];                  
                } else {
                    $res[$roleId] = isset($result[$roleId]) ? $result[$roleId] : null;
                }
            }          
        } else {
            $res = $result;
        }             
        return $res;
    }
    
    public function tree($arr, $p_id = 0) {
        $tree = array();
        foreach($arr as $row) {            
            if( $row['parent'] === $p_id) {              
                $tmp = self::tree($arr, $row['child']);             
                if($tmp) {
                    $row['children']=$tmp;
                }             
                $tree[$row['child']]=$row;               
            }
        }
        
        return $tree;
    }

    public function search($var1, $val2, $stack) {
        foreach ($stack as $key => $val) {
            if (in_array($var1, $val) && $key != $val2) {
                return true;
            }
        }
        return false;
    }
    
     /**
     * get all roles itemname
     * 
     * @return array 
     */
    public static function getAllRoles() {
        return Yii::app()->db->createCommand()
                        ->select('name')
                        ->from(self::tableName() . ' ai')
                        ->where("ai.type = 2")
                        ->andWhere("ai.description Not LIKE '".UebModel::model('user')->role_mark."_%'")//不取个人用户角色
                        ->queryColumn();
    }
    
    /**
     * get role name and description map
     * 
     * @return array $result
     */
    public static function getPairs() {
         $list =  Yii::app()->db->createCommand()
                        ->select('name,description')
                        ->from(self::tableName() . ' ai')
                        ->where("ai.type = 2")
                        ->query();
         $result = array();
         foreach ( $list as $val ) {
             $result[$val['name']] = $val['description'];
         }
         
         return $result;
    }  
    
    /**
     * get parent role id , if it is root role return null
     * 
     * @param type $roleId
     */
    public static function getParentByRoleId($roleId) {
        $data = AuthItemChild::getParentByChild($roleId);
        if (! empty($data) ) {
            return $data[0];
        }
        return null;
    } 
    
    /**
     * modify role, include role name, role code, role parent
     */
    public function modifyRole($oldName) {  
        $name = $this->getAttribute('name');       
        $authItemObj = AuthItem::model()->findByPk($oldName);        
        $authItemObj->name =  $this->getAttribute('name');
        $authItemObj->description =  $this->getAttribute('description');
        $authItemObj->save();
        $authItemChildObj = AuthItemChild::model()->find("child = '{$name}'");
        $parent = $this->getAttribute('parent');
        if (! empty($authItemChildObj) ) {
            if ( $parent != 'all' ) {
                $authItemChildObj->parent = $parent;
                $authItemChildObj->save(); 
            } else {
               $authItemChildObj->delete();  
            }            
        } else {
            if ( $parent != 'all' ) {
                $auth = Yii::app()->authManager;
                $authItem = new CAuthItem($auth, $parent, 2);                
                if (! $authItem->hasChild($name) ) {
                    $authItem->addChild($name);
                }
            }
        }        
    }

	public static function getRoleNameByRoleCode($role_code) {
         $result =  Yii::app()->db->createCommand()
                    ->select('name,description')
                    ->from(self::tableName() . ' ai')
                    ->where("ai.type = 2 and name='".$role_code."' AND ai.description Not LIKE '".UebModel::model('user')->role_mark."_%'")
			  		->queryRow();
		 return $result;
    } 
	//get user role
    public static function getUserRole($all = '') {
    	if ( User::isAdmin() ) {
    		$roles = self::getAllRoles();
    	} else {
    		$roles = User::getLoginUserRoles();
    		
    	}
    	foreach($roles as $val){
			$data = self::getRoleNameByRoleCode($val);
			if(!empty($data['description'])){
				$arr[$val] =  $data['description'];
			}
		}
		return $arr;
    	
    }
}