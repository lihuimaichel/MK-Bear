<?php

class Dep extends UsersModel
{	 
	public $parent = '';
	public $parent_id = '';	
	public $description = '';
	public $name = '';
	public $name_code = '';
	/**
	 * Returns the static model of the specified AR class.
	 * @return CActiveRecord the static model class
	 */
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName() {
		return 'ueb_department';
	}
    
    public function rules() {
        $rules = array(         
            array( 'department_name, department_code, department_parent_id', 'required'),    
            array( 'department_name, department_code', 'unique'),
        	array( 'department_name, department_code', 'length', 'max' => 50),
        	array('parent', 'length', 'max' => 50),
        );        
        return $rules;
    }
    
    /**
     * Declares attribute labels.
     * @return array
     */
    public function attributeLabels() {
        return array(
            'department_name'     => Yii::t('users', 'Dep Name'),
            'menu_url'              => Yii::t('system', 'Menu URL'),
            'menu_description'      => Yii::t('system', 'Menu Description'),
            'menu_status'           => Yii::t('system', 'Status'),
            'menu_order'            => Yii::t('system', 'Order'),
            'menu_is_menu'          => Yii::t('system', 'Whether it is the menu'),
            'department_parent_id'        => Yii::t('system', 'The parent menu'),
        	//'name'                  => Yii::t('users', 'Dep Name'),
        	'department_code'                  => Yii::t('users', 'Dep Code'),
        	'department_description'           => Yii::t('users', 'Dep desc'),
        	'parent'                => Yii::t('users', 'Parent Dep'),
        );
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
    public static function backFilterName($name) {
    	return str_replace("_", " ", $name);
    }
    /**
     * @desc 获取当前用户所有的部门树（）
     * @param unknown $uid
     * @param string $hasTwoDep
     * @return Ambigous <multitype:, multitype:unknown >
     */
    public static function getUserAlldepIds($uid, $hasTwoDep = true){
    	$depId = User::model()->getDepIdById($uid);
    	//部门二
    	$depId2str = User::model()->getDep2IdById($uid);
    	//上下级部门
    	$allDepIds = array();
    	$parentDepIds = self::getAllParentDepId($depId);
    	$subDepIds = self::getAllSubDepId($depId);
    	if($parentDepIds){
    		$allDepIds = array_merge($allDepIds, $parentDepIds);
    	}
    	$allDepIds[] = $depId;
    	if($subDepIds){
    		$allDepIds = array_merge($allDepIds, $subDepIds);
    	}
    	if($hasTwoDep && $depId2str){
    		$dep2IDs = explode(",", $depId2str);
    		//$allDepIds = array_merge($allDepIds, $dep2IDs);
    		foreach ($dep2IDs as $depid){
    			$parentDepIds = self::getAllParentDepId($depid);
    			$subDepIds = self::getAllSubDepId($depid);
    			if($parentDepIds){
    				$allDepIds = array_merge($allDepIds, $parentDepIds);
    			}
    			$allDepIds[] = $depid;
    			if($subDepIds){
    				$allDepIds = array_merge($allDepIds, $subDepIds);
    			}
    		}
    	}
    	return $allDepIds;
    }
    
    /**
     * @desc 获取所有上级部门id
     * @param unknown $uid
     * @param string $hasTwo
     * @return multitype:
     */
    public static function getUserAllParentDepIds($uid, $hasTwo = true){
    	$depId = User::model()->getDepIdById($uid);
    	//部门二
    	$depId2str = User::model()->getDep2IdById($uid);
    	//上下级部门
    	$allDepIds = array();
    	$parentDepIds = self::getAllParentDepId($depId);
    	if($parentDepIds){
    		$allDepIds = array_merge($allDepIds, $parentDepIds);
    	}
    	
    	if($hasTwo && $depId2str){
    		$dep2IDs = explode(",", $depId2str);
    		foreach ($dep2IDs as $depid){
    			$parentDepIds = self::getAllParentDepId($depid);
    			if($parentDepIds){
    				$allDepIds = array_merge($allDepIds, $parentDepIds);
    			}
    		}
    	}
    	return $allDepIds;
    } 
    
    public static function getTreeList() {
    	$uid = Yii::app()->user->id;
    	//echo $uid;
    	if(UserSuperSetting::model()->checkSuperPrivilegeByUserId($uid)){
    		$depId = 0;
    	}else{
    		// === 2017-04-01 === //
    		$allDepIds = self::getUserAlldepIds($uid);
    		$depId = implode(",", $allDepIds);
    	}
    	
    	//echo $depId;
        $list = Yii::app()->db_oms_system->createCommand() 
			->select('id,department_parent_id,department_name,department_code,department_description,department_status,department_level,department_order')
			->from(self::tableName())	
            ->where("department_status = 1")
            ->andWhere($depId ? " id in ({$depId})" : "1")
            ->order("department_level Desc, department_order Asc")
			->query(); 
        $data = array();
        foreach ($list as $key => $val) {          
           if (  isset($data[$val['id']]) ) {
               $subdept = $data[$val['id']]['subdept'];
               unset($data[$val['id']]['subdept']);
               $data[$val['department_parent_id']]['subdept'][$val['id']] =  array(
                   'id'                     => $val['id'],
                   'name'                   => $val['department_name'],
                   'department_parent_id'   => $val['department_parent_id'],                 
                   'subdept'                => $subdept);              
           } else {
               $data[$val['department_parent_id']]['subdept'][$val['id']] = array(
                   'id'                     => $val['id'], 
                   'name'                   => $val['department_name'],
                   'department_parent_id'   => $val['department_parent_id'],                 
                   'subdept'    => array());  
           }                   
        }       
        return $data[0]['subdept'];
    }
    
    public static function get_max_order($pid) {
    	$arr = Yii::app()->db_oms_system->createCommand()
    	->select('max(department_order) as max_order')
    	->from(self::tableName())
    	->where("department_parent_id = '{$pid}'")
    	->queryColumn();
    	return $arr[0];
    }
    
    public static function getParentBydepId($id) {
    	return Yii::app()->db_oms_system->createCommand()
              ->select('department_parent_id,department_name,department_description,department_code')
              ->from(self::tableName())
              ->where(" id = ".$id)
              ->queryRow();
    	
    }
    
    public static function getSubDepId($parentId) {
    	return Yii::app()->db_oms_system->createCommand()
    	->select('id,department_name,department_description,department_code')
    	->from(self::tableName())
    	->where(" department_parent_id = ".$parentId)
    	->queryAll();
    }
    
    public static function getchilddep($id) {
    	return Yii::app()->db_oms_system->createCommand()
    	->select('count(*) as num')
    	->from(self::tableName())
    	->where(" department_parent_id = ".$id)
    	->queryRow();
    	 
    }
       
    public static function getIndexNavTabId() {
        return Menu::model()->getIdByUrl('/users/users/index');
    } 
    
    public function getDepIdByCode($code) {
    	$model = $this->findByAttributes('',"department_code='{$code}'");
    	return $model->attributes;
    }
    
    /**
     * @desc 获取全部上级部门ID(不含当前部门ID)
     * @param unknown $depId
     * @return Ambigous <multitype:, multitype:unknown >
     */
    public static function getAllParentDepId($depId){
    	$allDepIds = array();
    	$data = self::getParentBydepId($depId);
    	$allDepIds[] = $data['department_parent_id'];
    	if($data['department_parent_id']){
    		$data = self::getAllParentDepId($data['department_parent_id']);
    		$allDepIds = array_merge($allDepIds, $data);
    	}
    	return $allDepIds;
    }
    
    
    /**
     * @desc 获取全部上级部门ID(不含当前部门ID)
     * @param unknown $depId
     * @return Ambigous <multitype:, multitype:unknown >
     */
    public static function getAllSubDepId($depId){
    	$allDepIds = array();
    	$data = self::getSubDepId($depId);
    	if($data){
    		foreach ($data as $val){
    			$allDepIds[] = $val['id'];
    			if($val['id']){
    				$data = self::getAllSubDepId($val['id']);
    				$allDepIds = array_merge($allDepIds, $data);
    			}
    		}
    	}
    	return $allDepIds;
    }

}