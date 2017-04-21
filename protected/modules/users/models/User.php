<?php
/**
 * @desc User Model
 * @author Gordon
 * @since 2015-06-10
 */
class User extends UsersModel{
    public $onUser = 1;
    public $stopUser = 0;
    public $role_mark = 'roleSelf';//个人角色识别标志
    public $user_position;

    /** @var boolean 显示组长是否可以设置组员isleader **/
    public $isleader;

	public static function model($className=__CLASS__){
		return parent::model($className);
	}

	/**
	 * @desc 表名
	 * @see CActiveRecord::tableName()
	 */
	public function tableName(){
		return 'ueb_user';
	}
    
	/**
	 * @desc 返回admin的用户ID
	 */
	public static function admin(){
	    return 1;
	}
	
	/**
	 * Checks if the given password is correct.
	 * @param string the password to be validated
	 * @return boolean whether the password is valid
	 */
	public function validatePassword($password)
	{       
		return crypt($password,$this->user_password)===$this->user_password;
	}

	/**
	 * Generates the password hash.
	 * @param string password
	 * @return string hash
	 */
	public function hashPassword($password)
	{
		return crypt($password, $this->generateSalt());
	}

	/**
	 * Generates a salt that can be used to generate a password hash.
	 *
	 * The {@link http://php.net/manual/en/function.crypt.php PHP `crypt()` built-in function}
	 * requires, for the Blowfish hash algorithm, a salt string in a specific format:
	 *  - "$2a$"
	 *  - a two digit cost parameter
	 *  - "$"
	 *  - 22 characters from the alphabet "./0-9A-Za-z".
	 *
	 * @param int cost parameter for Blowfish hash algorithm
	 * @return string the salt
	 */
	protected function generateSalt($cost=10)
	{
		if(!is_numeric($cost)||$cost<4||$cost>31){
			throw new CException(Yii::t('Cost parameter must be between 4 and 31.'));
		}
		// Get some pseudo-random data from mt_rand().
		$rand='';
		for($i=0;$i<8;++$i)
			$rand.=pack('S',mt_rand(0,0xffff));
		// Add the microtime for a little more entropy.
		$rand.=microtime();
		// Mix the bits cryptographically.
		$rand=sha1($rand,true);
		// Form the prefix that specifies hash algorithm type and cost parameter.
		$salt='$2a$'.str_pad((int)$cost,2,'0',STR_PAD_RIGHT).'$';
		// Append the random salt string in the required base64 format.
		$salt.=strtr(substr(base64_encode($rand),0,22),array('+'=>'.'));
		return $salt;
	}
    
    /**
     * change user password
     * 
     * @param type $password
     * @return type
     */
    public function getCryptPassword($password) {
        return crypt($password, crypt($password));
    }


    /**
     * get page list
     * 
     * @return array
     */
    public function getPageList() {
        $this->_initCriteria();  
        if (isset($_REQUEST['department_id'])) {
            $departmentId = $_REQUEST['department_id'];
        } 
        if (! empty($_REQUEST['user_name']) ) {
            $userName = trim($_REQUEST['user_name']);
            $this->criteria->addCondition("user_name = '{$userName}'");  
        }
        $this->_initPagination( $this->criteria);
        $models = $this->findAll($this->criteria);       
        return array($models, $this->pages);
    }
    
    public function getUlist() {
        $this->_initCriteria();        
        if (! empty($_REQUEST['user_name']) ) {
            $userName = trim($_REQUEST['user_name']);
            $this->criteria->addCondition("user_name = '{$userName}'");  
        }       
        $models = $this->findAll($this->criteria);        
        return $models;
    }
    
    /**
     * get user list by department id
     */
    public function getUlistByDepId($depId) {
    	$models = $this->findAllByAttributes(array('department_id'=>$depId));
    	return $models;
    }
    
    public function getEmpByDept($deptId){
    	if(!is_array($deptId)){
    		$deptId = array($deptId);
    	}
    	$empArr=$this->findAll("department_id in (".MHelper::simplode($deptId).") and user_status=:user_status",array('user_status'=>1));
    	$empData=array();
    	foreach ($empArr as $emp){
    		$empData[$emp->id]=$emp->user_full_name;
    	}
    	return $empData;
    }
    
    public function getAllEmpByDept($deptId){
    	if(!is_array($deptId)){
    		$deptId = array($deptId);
    	}
    	$empArr=$this->findAll("department_id in (".MHelper::simplode($deptId).")");
    	$empData=array();
    	foreach ($empArr as $emp){
    		$empData[$emp->id]=$emp->user_full_name;
    	}
    	return $empData;
    }


    /**
     * @desc 根据部门ID获取用户名(user_name)
     * @param array or int $deptId 部门ID
     * @return array 返回用户名
     */
    public function getUserNameByDeptID($deptId, $isFull = false){
        if(!is_array($deptId)){
            $deptId = array($deptId);
        }
        $empArr=$this->findAll("department_id in (".MHelper::simplode($deptId).") and user_status=:user_status",array('user_status'=>1));
        $empData=array();
        foreach ($empArr as $emp){
            $empData[$emp->id]= (false == $isFull) ? $emp->user_name : $emp->user_full_name;
        }
        return $empData;
    }


    public function findUserListByDepartmentId($id)
    {
        $queryBuilder = $this->getDbConnection()->createCommand()->from($this->tableName())
            ->select('id, department_id, user_name, user_full_name, user_status')
            ->where('department_id=:departmentId', array(':departmentId'=> $id))
            ->andWhere('user_status=1')
            ->order('CONVERT(user_full_name USING GBK)');

        return $queryBuilder->queryAll();

    }
    /**
     * @desc 根据部门ID获取用户名(user_name)
     * @param array or int $deptId 部门ID
	 * @param $fullName
     * @return array 返回用户名或者全名
     */
    public function getUserNameAllEmpByDeptID($deptId, $fullName = false){
        if(!is_array($deptId)){
            $deptId = array($deptId);
        }
        $empArr=$this->findAll("department_id in (".MHelper::simplode($deptId).")");
        $empData=array();
        foreach ($empArr as $emp){
            $empData[$emp->id] = (false == $fullName) ? $emp->user_name : $emp->user_full_name;
        }
        return $empData;
    }


    /**
     * check if it is a super user
     * 
     * @return bool
     */
    public static function isAdmin() {
        return Yii::app()->user->name == 'admin';
    }
    
    /**
     * 
     * user status list
     */
    public function getUserStatusList(){
    	$status=array(
    		$this->onUser	=>Yii::t('system', 'Enable'),  
    		$this->stopUser	=>Yii::t('system', 'Disable'),
    	);
    	return $status;
    }
    /**
     * get login user roles
     * 
     * @return array 
     */
    public static function getLoginUserRoles($uid=null) {
		$userId = Yii::app()->user->id;
		if($uid !==null) $userId = $uid;
        return Yii::app()->db->createCommand() 
			->select('itemname')
			->from(AuthAssignment::model()->tableName())
			->where("userid = '{$userId}'")
			->queryColumn(); 
    } 
    /**
     * batch change user the status
     * @param warehouseIds $oprationIds
     */
    public function changeUserStatus($oprationIds,$beginUsing=true){
    	$updateData = array(
    			'user_status' => $beginUsing ? $this->onUser : $this->stopUser,
    	);
    	$flag = $this->updateAll($updateData,"id IN (".$oprationIds.")");
    	return $flag;
    }
    /**
     * get id - user name map
     * 
     * @return array
     */
    public function getPairs() {
        return UebModel::model('user')
                ->queryPairs('id,user_name', "user_status=1");
    }

    public function getAllUsers()
    {
        $userList = $this->getDbConnection()->createCommand()
                    ->select('id, user_name, user_full_name, department_id, department2')
                    ->from(self::tableName())
                    ->where("user_status = 1")
                    ->queryAll();

        return $userList;
    }
    
    /**
     * @desc 获取指定用户
     * @param unknown $uids
     * @return multitype:Ambigous <>
     */
    public function getSpecificPairs($uids = array()){
    	$userList = $this->getDbConnection()->createCommand()
				    	->select('id, user_name')
				    	->from(self::tableName())
				    	->where(array('IN', 'id', $uids))
				    	->queryAll();
    	$newUserList = array();
    	if($userList){
    		foreach ($userList as $user){
    			$newUserList[$user['id']] = $user['user_name'];
    		}
    	}
    	return $newUserList;
    }
    
    public static function getIndexNavTabId() {
        return Menu::model()->getIdByUrl('/users/users/index');
    } 

    public function getUserNameById($id){
    	return $this->getDbConnection()->createCommand() 
					->select('*')
					->from(self::tableName())
					->where("id = ".$id)        
					->queryRow(); 
    }
    public function getUserNameArrById($id){
    	$result = array();
    	$data = $this->getDbConnection()->createCommand() 
					->select('id,user_full_name')
					->from(self::tableName())
					->where("id = ".$id)        
					->queryRow();
    	if ($data) $result[$data['id']] = $data['user_full_name'];
    	return $result;
    }
    
    public function getUserIdByName($name){
    	$user_info =  $this->getDbConnection()->createCommand() 
					->select('id')
					->from(self::tableName())
					->where("user_name = '".$name."' or en_name = '".$name."'")        
					->queryRow(); 
		return $user_info['id'];
    }
    
    /**
     * @desc 获取部门ID
     * @param unknown $id
     * @return mixed
     */
    public function getDepIdById($id){
    	$user_info =  $this->getDbConnection()->createCommand()
    	->select('department_id')
    	->from(self::tableName())
    	->where("id = ".$id)
    	->queryRow();
    	return $user_info['department_id'];
    }
    
    /**
     * @desc 获取部门2id
     * @param unknown $id
     * @return mixed  字符串
     */
    public function getDep2IdById($id){
    	$user_info =  $this->getDbConnection()->createCommand()
    	->select('department2')
    	->from(self::tableName())
    	->where("id = ".$id)
    	->queryRow();
    	return $user_info['department2'];
    }
    
    public function getUserFullNameByName($name){
    	$user_info =  $this->getDbConnection()->createCommand()
    	->select('*')
    	->from(self::tableName())
    	->where("user_name = '".$name."' or en_name = '".$name."'")
    	->queryRow();
    	return $user_info['user_full_name'];
    }
    
    public function getIdByUserName($userName){
    	$list = array();
        $data = $this->findAll('user_name=:user_name', array(':user_name'=>$userName));
                if($data){
                    foreach ($data as $key=>$val){
                        $list[$val['id']]=$val['id'];
                    }
                }
                return $list;
    }
    
    public function getIdByName($userName) {
    	return $this->getDbConnection()->createCommand()->from(self::tableName())
    		->select("id")
    		->where("user_name = :user_name", array(':user_name' => $userName))
    		->queryScalar();
    }
    
    
    
    public function getUserData(){
    	return $this->getDbConnection()->createCommand()->select('id,user_name,user_full_name')->from(User::model()->tableName())->order("id asc")->queryAll();
    }

   
    public function getUserListByIDs($ids){
    	return $this->getDbConnection()->createCommand()->select('id,user_name,user_full_name,user_status')->from(User::model()->tableName())->where(array("IN", "id", $ids))->order("id asc")->queryAll();
    }
    
    /**
     * @desc 获取wish用户列表
     * @param string $isAll
     * @return multitype:NULL
     */
    public function getWishUserList($isAll = false, $isFull = false){
    	if($isAll){
    		return $this->getUserNameAllEmpByDeptID(Department::getDepartmentByPlatform(Platform::CODE_WISH), $isFull);
    	}else{
    		return $this->getUserNameByDeptID(Department::getDepartmentByPlatform(Platform::CODE_WISH), $isFull);

    	}
    }


    /**
     * @desc 获取aliexpress用户列表
     * @param string $isAll
     * @return multitype:NULL
     */
    public function getAliexpressUserList($isAll = false, $isFull = false){
        if($isAll){
            return $this->getUserNameAllEmpByDeptID(Department::getDepartmentByPlatform(Platform::CODE_ALIEXPRESS), $isFull);
        }else{
            return $this->getUserNameByDeptID(Department::getDepartmentByPlatform(Platform::CODE_ALIEXPRESS), $isFull);
        }
    }


    /**
     * @desc 获取amazon用户列表
     * @param string $isAll
     * @return multitype:NULL
     */
    public function getAmazonUserList($isAll = false, $isFull = false){
        if($isAll){
            return $this->getUserNameAllEmpByDeptID(Department::getDepartmentByPlatform(Platform::CODE_AMAZON), $isFull);
        }else{
            return $this->getUserNameByDeptID(Department::getDepartmentByPlatform(Platform::CODE_AMAZON), $isFull);
        }
    }


    /**
     * @desc 获取lazada用户列表
     * @param string $isAll
     * @return multitype:NULL
     */
    public function getLazadaUserList($isAll = false, $isFull = false){
        if($isAll){
            return $this->getUserNameAllEmpByDeptID(Department::getDepartmentByPlatform(Platform::CODE_LAZADA), $isFull);
        }else{
            return $this->getUserNameByDeptID(Department::getDepartmentByPlatform(Platform::CODE_LAZADA), $isFull);
        }
    }


    /**
     * @desc 获取ebay用户列表
     * @param string $isAll
     * @return multitype:NULL
     */
    public function getEbayUserList($isAll = false, $isFull = true){
        if($isAll){
			return $this->getUserNameAllEmpByDeptID(Department::getDepartmentByPlatform(Platform::CODE_EBAY), $isFull);
        } else {
			return 	$this->getUserNameByDeptID(Department::getDepartmentByPlatform(Platform::CODE_EBAY), $isFull);
        }
    }


    /**
     * @desc 获取joom用户列表
     * @param string $isAll
     * @return multitype:NULL
     */
    public function getJoomUserList($isAll = false, $isFull = false){
        if($isAll){
            return $this->getUserNameAllEmpByDeptID(Department::getDepartmentByPlatform(Platform::CODE_JOOM), $isFull);
        }else{
            return $this->getUserNameByDeptID(Department::getDepartmentByPlatform(Platform::CODE_JOOM), $isFull);
        }
    }


    /**
     * @desc 获取用户名
     * @param  integer $id 用户登录ID
     * @return string      用户名
     */
    public static function getUserNameScalarById($id){
        $result = '';
        $data = User::model()->getDbConnection()->createCommand() 
                    ->select('user_name')
                    ->from(self::tableName())
                    ->where("id = ".$id)        
                    ->queryScalar();
        if ($data) $result = $data;
        return $result;
    }


    /**
     * @desc 获取启用的指定用户
     * @param unknown $uids
     * @return multitype:Ambigous <>
     */
    public function getStatusOneByUids($uids = array()){
        $userList = $this->getDbConnection()->createCommand()
                        ->select('id, user_name')
                        ->from(self::tableName())
                        ->where(array('IN', 'id', $uids))
                        ->andWhere('user_status = 1')
                        ->queryAll();
        $newUserList = array();
        if($userList){
            foreach ($userList as $user){
                $newUserList[$user['id']] = $user['user_name'];
            }
        }
        return $newUserList;
    }


    /**
     * @desc 获取所有的用户
     * @return array
     */
    public function getAllUserName(){
        $userList = $this->getDbConnection()->createCommand()
                        ->select('id, user_name')
                        ->from(self::tableName())
                        ->queryAll();
        $AllUserName = array();
        if($userList){
            foreach ($userList as $user){
                $AllUserName[$user['id']] = $user['user_name'];
            }
        }
        return $AllUserName;
    }
    

    /**
     * @desc 获取用户名
     * @param  integer $id 用户登录ID
     * @return string      用户全名
     */
    public static function getUserFullNameScalarById($id){
        $result = '';
        $data = User::model()->getDbConnection()->createCommand() 
                    ->select('user_full_name')
                    ->from(self::tableName())
                    ->where("id = ".$id)        
                    ->queryScalar();
        if ($data) $result = $data;
        return $result;
    }    
    
    // =======================User Search ===================
    
    
    /**
     * @desc 搜索筛选栏定义
     * @return multitype:multitype:string  multitype:string multitype:NULL Ambigous <string, string, unknown>   multitype:string NULL
     */
    public function filterOptions(){
    	$depId = Yii::app()->request->getParam('department_id');
    	return array(
    		
    			array(
    					'name'		=>	'user_name',
    					'search'	=>	'=',
    					'type'		=>	'text',
    
    			),

                // array(
                //         'name'      =>  'post_id',
                //         'type'=>'dropDownList',
                //         'search'=>'=',
                //         'data'  => UserPosition::$positionArray,
                //         'value' => Yii::app()->request->getParam('post_id'),
                //         'htmlOption'=>array(
                //                 'size'=>'22'
                //         ),
                //         'rel'   => true
                // ),

    			/* array(
    					'name'		=>	'department_id',
    					'search'	=>	'=',
    					'type'		=>	'dropDownList',
    					'data'		=>	UebModel::model("Department")->getDepartment(),
    					'value'		=>	$depId
    			), */
    
    	);
    }
    
    /**
     * (non-PHPdoc)
     * @see CModel::attributeLabels()
     */
    public function attributeLabels(){
    	return array(
    			'user_name'			=>	Yii::t('user', 'User Name'),
                'post_id'           =>  Yii::t('user', '岗位'),
    			'department_id'		=>	Yii::t('user', 'Department Name'),
    			 
    	);
    }
    /**
     * get search info
     */
    
    public function search($depId = null)
    {
    	$sort = new CSort();
    	$sort->attributes = array(
    			'defaultOrder'  => 'id',
    	);
    	
    	$dataProvider = parent::search(get_class($this), $sort, null, $this->_setCDbcriteria());

        $data = $this->addition($dataProvider->data);
        $dataProvider->setData($data);
        return $dataProvider;
    }
    
    private function _setCDbcriteria(){
    	$cdbCri = new CDbCriteria();
    	$cdbCri->select = '*';
    	$depId = Yii::app()->request->getParam('department_id', $this->getDepIdById(Yii::app()->user->id));
    	if($depId){
    		$cdbCri->addCondition("department_id='{$depId}' and user_status=1");
    	}

        $postId = Yii::app()->request->getParam('post_id');
        if(is_numeric($postId) && $postId >= 0){
            $postUserIds = '';
            $postUserList = UserPosition::model()->getUserPositionInfoByPostID($postId);
            if($postUserList){
                foreach ($postUserList as $key => $value) {
                    $postUserIds .= $value['user_id'].',';
                }

                if($postUserIds){
                    $postUserIds = rtrim($postUserIds,',');
                    $cdbCri->addCondition("id IN({$postUserIds})");
                }
            }
        }

    	return $cdbCri;
    }


    /**
     * @desc 附加查询条件
     * @param unknown $data
     */
    public function addition($data){

        //判断用户是否是超级管理员
        $isAdmin = UserSuperSetting::model()->checkSuperPrivilegeByUserId(Yii::app()->user->id);

        foreach ($data as $key => $val){
            $data[$key]->isleader = false;
            // $leaderList = UserPosition::model()->getUserPositionInfoByUserID($val['id']);
            // if($leaderList && $leaderList['post_id'] == 1 && $isAdmin){
            //     $data[$key]->isleader = true;
            // }
        }
        return $data;
    }
    
    
    // =============================== 搜索end ===============================//
    

    /**
     * 返回第3级菜单，有时间做成无限循环的
     */
    public static function getMenu($submenu, $subKey, $selectedMenu){
        $menu = '';
        if($submenu){
            foreach ($submenu['submenu'] as $smallkey=>$smallmenu) {
                $checked = ' ';
                if(in_array($smallkey, $selectedMenu)){
                    $checked = ' checked ';
                }

                $menu .= '<span style="width: 160px;height:40px;line-height:24px;display:inline-block;">
                <input id="continents_'.$smallkey.'" type="checkbox" name="menu['.$subKey.']['.$smallkey.']"'.$checked.'value="'.$smallkey.'">
                <label for="continents_'.$smallkey.'" style="float: right;">'.$smallmenu['name'].'</label></span>
                ';
            }
        }

        echo $menu;
    }
}