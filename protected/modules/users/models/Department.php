<?php

class Department extends UsersModel
{	   
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
        $rules = array();        
        return $rules;
    }
    
    /**
     * Declares attribute labels.
     * @return array
     */
    public function attributeLabels() {
        return array(
            'department_name'     => Yii::t('system', 'Menu Name'),
            'menu_url'              => Yii::t('system', 'Menu URL'),
            'menu_description'      => Yii::t('system', 'Menu Description'),
            'menu_status'           => Yii::t('system', 'Status'),
            'menu_order'            => Yii::t('system', 'Order'),
            'menu_is_menu'          => Yii::t('system', 'Whether it is the menu'),
            'department_parent_id'        => Yii::t('system', 'The parent menu'),
        );
    }
     
    public function getTreeList() {
        $list = $this->getDepartmentList();               
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
    /**
     * get Department List
     * @return Ambigous <CDbDataReader, mixed>
     */
    public function getDepartmentList($status = null){
    	return $list = Yii::app()->db_oms_system->createCommand()
	    	->select('*')
	    	->from(self::tableName())
	    	->where($status !== null ? "department_status = {$status}" : "1")
	    	->order("department_level Desc, department_order Asc")
	    	->query();
    }
    /**
     * get Department
     * @param $partmentId
     * @return multitype:unknown
     */
    public function getDepartment($partmentId = null){
    	$data = array();
    	$list = $this->getDepartmentList();
    	if ($list){
    		foreach ($list as $key=>$val){
    			$data[$val['id']] = $val['department_name'];
    		}
    	}
    	if ($partmentId !== null) return (isset($data[$partmentId]) ? $data[$partmentId] : 'unkown');
    	return $data;
    }
       
    public static function getIndexNavTabId() {
        return Menu::model()->getIdByUrl('/users/users/index');
    } 
    
    
    /**
     * @desc 获取所有平台市场业务部门信息
     * @param string $partmentId
     * @return unknown
     * 3->Ebay市场部,4->速卖通市场部,5->Amazon市场部,15->WISH市场部,20->LZD市场部
     */
    public function getMarketsDepartmentInfo($partmentId = null,$type=null){
    	$data = $this->getDbConnection()->createCommand()
			    	->select('id,department_name')
			    	->from(self::tableName())
			    	->where("department_type = 1")
			    	->order('id asc')
			    	->queryAll();
    	$partmentArr=array();
    	foreach ($data as $val){
    		$partmentArr[$val['id']]=$val['department_name'];
    	}
    	if(isset($type)){
    		return isset($partmentArr[$partmentId]) ? $partmentArr[$partmentId] : '';//@todo oms迁移过来的
    	}else{
    		return $partmentArr;
    	}
    }


    /**
     * @desc 通过搜索department_code字段关键字获取平台市场业务部门信息
     * @param string $keywords
     * @return unknown
     */
    public function getDepartmentByKeywords($keywords=null){
        $wheres = '';
        $data = $this->getDbConnection()->createCommand()
                    ->select('id,department_name')
                    ->from(self::tableName())
                    ->where("department_type = 1")
                    ->andWhere("department_status = 1");
        if($keywords){
            $wheres = "department_code LIKE '%{$keywords}%'";
            $data->andWhere($wheres);
        }
        $data->order('id asc');
        $res = $data->queryAll();
        $partmentArr=array();
        if($res){
            foreach ($res as $val){
                $partmentArr[$val['id']]=$val['department_name'];
            }
        }
        return $partmentArr;
    }


    /**
     * @desc 根据平台获取部门ID数组
     * @param string $platform
     * @return array
     */
    public static function getDepartmentByPlatform($platform){
        switch ($platform) {
            //速卖通部门ID数组
            case Platform::CODE_ALIEXPRESS:
                return array(4, 25);
                break;
            //EBAY部门ID数组
            case Platform::CODE_EBAY:
                return array(53, 23, 54, 19);
                break;
            //WISH部门ID数组
            case Platform::CODE_WISH:
                return array(43, 45, 37);
                break;
            //AMAZON部门ID数组
            case Platform::CODE_AMAZON:
                return array(5, 24);
                break;
            //LAZADA部门ID数组
            case Platform::CODE_LAZADA:
                return array(20);
                break;
            //JOOM部门ID数组
            case Platform::CODE_JOOM:
                return array(49, 50);
                break;
            default:
                return array();
                break;
        }
    }


    public static function departmentPlatform()
    {
        return array(
            43 => Platform::CODE_WISH,
            45 => Platform::CODE_WISH,
            37 => Platform::CODE_WISH,
            4 =>  Platform::CODE_ALIEXPRESS,
            25 => Platform::CODE_ALIEXPRESS,
            15 => Platform::CODE_WISH,
            37 => Platform::CODE_WISH,
            3 => Platform::CODE_EBAY,
            23 => Platform::CODE_EBAY,
            19 => Platform::CODE_EBAY,
            53 => Platform::CODE_EBAY,
            54 => Platform::CODE_EBAY,
            55 => Platform::CODE_EBAY,
            56 => Platform::CODE_EBAY,
            5 => Platform::CODE_AMAZON,
            24 => Platform::CODE_AMAZON,
            20 => Platform::CODE_LAZADA,
            38 => Platform::CODE_JOOM,
            49 => Platform::CODE_JOOM,
            50 => Platform::CODE_JOOM,
        );
    }


    /**
     * @desc 获取关联部门ID
     * @param int $id
     */
    public function getRelationDepartment(){
        $userID = isset(Yii::app()->user->id)?Yii::app()->user->id:0;
        // 判断是否是超级管理员
        $isAdmin = UserSuperSetting::model()->checkSuperPrivilegeByUserId($userID);

        $UserDepartmentIDs = null;
        //检测当前用户在哪个部门
        $userInfo = User::model()->findByPk($userID);
        $UserDepartmentID = isset($userInfo['department_id'])?$userInfo['department_id']:'';        
        if($isAdmin || $UserDepartmentID == 2){
            return $UserDepartmentIDs;
        }

        //循环取出最下级的部门ID
        $getLowDepartment = $this->getNextDepartment($UserDepartmentID);

        $UserDepartment2 = isset($userInfo['department2'])?rtrim($userInfo['department2'],','):'';
        //判断department2字段第一个字符是否有逗号
        if(substr($UserDepartment2, 1) != ',' && !$getLowDepartment){
            $UserDepartment2 = ','.$UserDepartment2;
        }

        $UserDepartmentIDs = $getLowDepartment.$UserDepartmentID.$UserDepartment2;

        return $UserDepartmentIDs;
    }


    /**
     * @desc 获取指定部门ID的下级部门ID
     * @param int $id
     */
    public function getNextDepartment($id){
        $nextDepartmentString = '';
        $info = $this->getDepartmentInfoByParentID($id);
        if($info){
            $nextDepartmentString .= $info.',';
            $level = 0;
            for($i = $level+1; $i > 0; $i++){
                $info = $this->getDepartmentInfoByParentID(ltrim($info , ','));
                if(!$info){
                    break;
                }

                $nextDepartmentString .= $info.',';
            }
        }

        return $nextDepartmentString;
    }


    /**
     * @desc 获取部门信息
     * @param int $parentID
     */
    public function getDepartmentInfoByParentID($parentID){
        $departmentIDs = null;
        if(!$parentID){
            return $departmentIDs;
        }

        $result = $this->getDbConnection()->createCommand()
                ->select('id')
                ->from(self::tableName())
                ->where('department_parent_id IN ('.$parentID.')')
                ->andWhere("department_status = 1")
                ->queryAll();
        if($result){
            foreach ($result as $val) {
                $departmentIDs .= $val['id'].',';
            }
            return rtrim($departmentIDs, ',');
        }
    }
}