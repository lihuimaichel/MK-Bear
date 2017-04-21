<?php
/**
 * @desc Aliexpress账号绑定分类列表
 * @author AjunLongLive!
 * @since 2017-03-03
 */
class AliexpressAccountListForBindCategory extends AliexpressModel{
    
    /** @var tinyint 账号状态开启*/
    const STATUS_OPEN = 1;
     
    /** @var tinyint 账号状态关闭*/
    const STATUS_SHUTDOWN = 0;
    
    /** @var tinyint 账号状态锁定*/
    const STATUS_ISLOCK = 1;
    
    /** @var tinyint 账号状态未锁定*/
    const STATUS_NOTLOCK = 0;
    
    public $num;
    public $is_lock;
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_aliexpress_account';
    }

    /**
     * @desc 数据库表名
     */
    public function mapTableName() {
        return 'ueb_aliexpress_account_map';
    }    
    
    /**
     * @desc 属性翻译
     */
    public function attributeLabels() {
    	return array(
    			'is_lock'					=> Yii::t('system', 'Lock Status'),
    			'status'         		 	=> Yii::t('system', 'Use Status'),
    			'short_name'				=> Yii::t('system', 'Seller Name'),
    			'first_category'			=> '所属一级分类',
    			'second_category'			=> '二级分类',
    			'third_category'			=> '三级分类',
    			'setting'			        => '操作',
    	);
    }
    
    /**
     * @desc 获取状态列表
     * @param integer $status
     * @return multitype:
     */
    public function getStatus($status = null){
    	$list = array(
    		self::STATUS_OPEN => Yii::t('system','Open'),
    		self::STATUS_SHUTDOWN => Yii::t('system','ShutDown')
    	);
    	($status && array_key_exists($status, $list)) && $list = $list[$status];
    	return $list;
    }
    
	/**
	 * @return array search filter (name=>label)
	 */
	public function filterOptions() {
		$tmpStatus = isset($_REQUEST['status']) ? $_REQUEST['status'] : "";
		if( $tmpStatus === '' ){
			$tmpStatus = '';
		}else if( $tmpStatus === '0' ){
			$tmpStatus = self::STATUS_SHUTDOWN;
		}else if( trim($tmpStatus) === '1'){
			$tmpStatus = self::STATUS_OPEN;
		}
		$result = array(
				array(
						'name'     		 => 'short_name',
						'type'     		 => 'text',
						'search'   		 => 'LIKE',
						'alias'    		 => 't',
				),
				array(
					'name'          => 'status',
					'type'          => 'dropDownList',
					'search'        => '=',
					'data'          => $this->getStatus(),
					'htmlOptions'   => array(),
					'value'			=> $tmpStatus,
       				'alias'			=> 't'
				)
		);
	
		return $result;
	
	}
    /**
     * search SQL
     * @return $array
     */
    protected function _setCDbCriteria() {

    	return NULL;
    }
    /**
     * @return $array
     */
    public function search(){
    	$sort = new CSort();
    	$sort->attributes = array(
    			'defaultOrder'  => 'short_name',
    			'defaultDirection'	=>	'ASC'
    	);
    	$criteria = null;
    	$criteria = $this->_setCDbCriteria();
    	$dataProvider = parent::search(get_class($this), $sort,array(),$criteria);
    
    	$data = $this->addition($dataProvider->data);
    	$dataProvider->setData($data);
    	return $dataProvider;
    }
    
    /**
     * @return $array
     */
    public function addition($data){
    	foreach ($data as $key => $val){
    		$data[$key]->num 	  = $val['id'];
    	}
    	return $data;
    }

    /**
     * @desc 获取可用账号列表
     * @author Gordon
     */
    public static function getAbleAccountList($order = "short_name ASC"){
        return AliexpressAccount::model()->dbConnection->createCommand()
                       ->select('*')
                       ->from(self::tableName())
                       ->where('status = '.self::STATUS_OPEN)
                       ->andWhere("is_lock <> " . self::STATUS_ISLOCK)
                       ->order($order)
                       ->queryAll();
    }

    /**
     * @desc 获取分表账号ID列表
     * @param integer $groupID
     * @return array
     */
    public function getMapAccountList($groupID=null) {
        $cmd = $this->dbConnection->createCommand()
                    ->select("a.id,m.group_id")
                    ->from($this->tableName().' as a')
                    ->leftJoin($this->mapTableName().' as m',"a.id=m.account_id")
                    ->where('a.status = '.self::STATUS_OPEN)
                    ->andWhere("a.is_lock <> " . self::STATUS_ISLOCK);
        if (!empty($groupID)) {
            $cmd->andWhere("m.group_id='{$groupID}'");
        }
        $res = $cmd->queryAll();
        $rtn = array();
        if (!empty($res)) {
            foreach ($res as $v) {
                $group_id = empty($v['group_id']) ? 0 : $v['group_id'];
                $rtn[$group_id][] = $v['id'];
            }
        }
        return isset($rtn[$groupID]) ? $rtn[$groupID] : $rtn;
    }

    /**
     * @desc 根据账号ID获取账号信息
     * @param int $id
     */
    public function getMapAccountInfoById($id){
        return $this->dbConnection->createCommand()
                    ->select("a.*,m.group_id")
                    ->from($this->tableName().' as a')
                    ->leftJoin($this->mapTableName().' as m',"a.id=m.account_id")
                    ->where('a.id =:id ',array('id'=>$id))
                    ->queryRow();
    }    

    /**
     * @desc 根据小时取余法分组获取账号ID
     */
    public function getGroupAccounts($offset = 0) {
        //19-23, 0-4
        $groupList = array();
        $accountInfos = self::getAbleAccountList();
        //根据账号ID最后一个数字分组
        foreach ($accountInfos as $accountInfo) {
            $key = substr($accountInfo['id'], -1, 1);
            //echo 'key: ','--',$key."<br>";

            //因为3、4点属备份时间，相应推后2小时
            if (date('G') == 6 && $key == 3) {
                $key = 6;
            }
            if (date('G') == 7 && $key == 4) {
                $key = 7;
            }

            $groupList[$key][] = $accountInfo['id'];
        }
        //ksort($groupList);
        //MHelper::printvar($groupList,false);
        //获取当前时间小时对应的数组
        $hour = date('G');

        $index = ($hour + $offset) % 10;
        //echo 'index: ','--',$index."<br>";
        if(isset($groupList[$index]))
            return $groupList[$index];
        else 
            return array();
    }
    
    /**
     * @desc 根据账号ID获取账号信息
     * @param int $id
     */
    public static function getAccountInfoById($id){
        $info = AliexpressAccount::model()->dbConnection->createCommand()
                ->select('*')->from(self::tableName())->where('id = '.$id)->queryRow();
        if(empty($info)){
            $info = AliexpressAccount::model()->dbConnection->createCommand()
                    ->select('*')->from(self::tableName())->where('account = '.$id)->queryRow();
        }
        return $info;
    }
    
    /**
     * @desc 获取一个可用的Aliexpress账号
     * @return mixed
     */
    public static function getAbleAccountByOne(){
    	return AliexpressAccount::model()->dbConnection->createCommand()
	    	->select('*')
	    	->from(self::model()->tableName())
	    	->where('status = '.self::STATUS_OPEN)
	    	->andWhere('is_lock = '.self::STATUS_NOTLOCK)
	    	->queryRow();
    }
    
    /**
     * 获取账号ID => ACCOUNT NAME对
     * @return array
     */
    public static function getIdNamePairs() {
    	$pairs = array();
    	$res = AliexpressAccount::model()
    	->getDbConnection()
    	->createCommand()
    	->select("id, short_name")
    	->from(self::tableName())
        ->order('short_name')
    	->queryAll();
    	if (!empty($res)) {
    		foreach ($res as $row)
    			$pairs[$row['id']] = $row['short_name'];
    	}
    	return $pairs;    	
    }
    
    /**
     * @desc 获取一个可用的Aliexpress账号
     * @return mixed
     */
    public  function getAccountInfoByAccountID($accountID){
    	return $this->getDbConnection()->createCommand()
    	->select('*')
    	->from(self::model()->tableName())
    	->where('status = '.self::STATUS_OPEN)
    	->where("id = :id", array(':id' => $accountID))
    	->queryRow();
    }
    
    /**
     * get status lable
     *
     * @param type $status
     */
    public static function getLockLable($status) {
    	if ($status == self::STATUS_NOTLOCK) {
    		echo '<font color="green" >' . Yii::t('system', 'Account OK') . '</font>';
    	} else {
    		echo '<font color="red" >' . Yii::t('system', 'Account Locked') . '</font>';
    	}
    }
    
    /**
     * get lock lable
     *
     * @param type $status
     */
    public static function getStatusLable($status) {
    	if ($status == self::STATUS_OPEN) {
    		echo '<font color="green" >' . Yii::t('system', 'Account Open') . '</font>';
    	} else {
    		echo '<font color="red" >' . Yii::t('system', 'Account Close') . '</font>';
    	}
    }
    
    /**
     * @desc 根据账号ID获取账号名称
     * @param string $accountId
     */
    public function getAccountNameById($accountId) {
    	return self::model()->getDbConnection()
    		->createCommand()
    		->select("short_name")
    		->from(self::tableName())
    		->where("id = :id", array(':id' => $accountId))
    		->queryScalar();
    }
    /**
     * @desc 冻结账号
     * @return boolean
     */
    public function lockAccount($accountID){
    	$lockArr = array();
    	$lockArr = array(
    			'is_lock' 		 => self::STATUS_ISLOCK,
    			'modify_user_id' => Yii::app()->user->id,
    			'modify_time'	 =>date('Y-m-d H:i:s'),
    	);
    	$flag=$this->updateAll($lockArr,"id in ( '{$accountID}' )");
    	return $flag;
    }
    
    
    /**
     * @desc 解冻账号
     * @return boolean
     */
    public function unLockAccount($accountID){
    	$unlockArr = array();
    	$unlockArr = array(
    			'is_lock' 		 => self::STATUS_NOTLOCK,
    			'modify_user_id' => Yii::app()->user->id,
    			'modify_time'	 =>date('Y-m-d H:i:s'),
    	);
    	$flag=$this->updateAll($unlockArr,"id in ( '{$accountID}' )");
    	return $flag;
    }
    
    
    /**
     * @desc 开启账号
     * @return boolean
     */
    public function openAccount($accountID){
    	$openArr = array();
    	$openArr = array(
    			'status' 		 => self::STATUS_OPEN,
    			'modify_user_id' => Yii::app()->user->id,
    			'modify_time'	 =>date('Y-m-d H:i:s'),
    	);
    	$flag=$this->updateAll($openArr,"id in ( '{$accountID}' )");
    	return $flag;
    }
    /**
     * @desc 关闭账号
     * @return boolean
     */
    public function shutDownAccount($accountID){
    	$closeArr = array();
    	$closeArr = array(
    			'status' 		 => self::STATUS_SHUTDOWN,
    			'modify_user_id' => Yii::app()->user->id,
    			'modify_time'	 => date('Y-m-d H:i:s'),
    	);
    	$flag=$this->updateAll($closeArr,"id in ( '{$accountID}' )");
    	return $flag;
    }
    /**
     * @desc 帐号激活
     * @return boolean
     */
    public function accountActivation($accountId){
    	$request= new GetAccountTokenRequest();
    	$response=$request->setAccount($accountId)->setRequest()->sendRequest()->getResponse();
		if($response->access_token){
			$updateArr=array('access_token'=>$response->access_token);
			$flag=$this->updateAll($updateArr,"id in ( '{$accountId}' )");
		}else{
			$flag=false;
		}
		return $flag;
    }
    
    /**
     * @desc帐号授权第一步获取CODE
     * @return boolean
     */
    public function accountAuthorize($accountId){
    	$flag='authorize';
    	$request= new GetAccountAuthorizeRequest();
    	$response=$request->setAccount($accountId)->setRequest();
		return $response;
    }
    /**
     * $desc帐号授权第二步  根据第一步获取CODE 获取refresh_token
     */
    public function getRefreshToken($data){
    	$request= new GetAccountRefreshTokenRequest();
    	$response=$request->setAccount($data['state'],$data['code'])->setRequest()->sendRequest()->getResponse();//
    	if($response->refresh_token){
    		$updateArr=array(
    			'resource_owner'=> $response->resource_owner,
    			'refresh_token'	=> $response->refresh_token,
    			'access_token'	=> $response->access_token
    		);
    		$flag=$this->updateAll($updateArr,"id in ( '{$data['state']}' )");
    	}
    	return $flag;
    }
    /**
     * @desc 根据shortName获取账号信息
     * @param unknown $shortName
     */
    public function getAccountInfoByShortName($shortName){
    	return $this->find("short_name = :short_name AND status = :status", array(':short_name' => $shortName,':status'=>self::STATUS_OPEN));
    }
    
    /**
     * @desc 根据shortName获取账号信息
     * @param unknown $shortName
     */
    public function getInfoByShortName($shortName){
    	$ret = $this->dbConnection->createCommand()
    			->select('*')
    			->from(self::tableName())
    			->where('short_name="'.$shortName.'"')
    			->queryRow();
    	return $ret;
    }
    
    /**
     * @desc 定义URL
     */
    public static function getIndexNavTabId() {
    	return Menu::model()->getIdByUrl('/aliexpress/aliexpressaccount/list');
    }
    
    /**
     * @desc 根据account ids获取账号列表
     * @param unknown $ids
     */
    public function getAccountInfoByIds($ids) {
    	return $this->findAll("id in (" . implode(',', $ids) . ")");
    }
    
    /**
     * @desc 获取计划任务执行同一组账号列表
     */
    public function getCronGroupAccounts() {
    	$accountList = array();
    	$groupList = array();
    	$accountInfos = AliexpressAccount::getAbleAccountList();
    	//根据账号ID最后一个数字分组
    	foreach ($accountInfos as $accountInfo) {
    		$key = substr($accountInfo['id'], -1, 1);
    		$groupList[$key][] = $accountInfo['id'];
    		$accountIDs[] = $accountInfo['id'];
    	}
    	return $accountIDs;
    	//获取当前时间小时对应的数组
		$offset = 4;
    	$hour = date('H');
    	$index = ($hour + $offset) % 24;
    	return $groupList[$index];
    }
    
    /**
     * @desc 获取计划任务执行同一组账号列表
     */
    public function getCronGroupAccountsDivide() {
    	$groupList = array();
    	$accountInfos = AliexpressAccount::getAbleAccountList();
    	//根据账号ID最后一个数字分组
    	foreach ($accountInfos as $accountInfo) {
    		$key = substr($accountInfo['id'], -1, 1);
    		$groupList[$key][] = $accountInfo['id'];
    	}
    	//获取当前时间小时对应的数组
        $hour  = date('H') + 3;
        $index = ($hour) % 24;
        $index = substr($index, -1);
    	return $groupList[$index];
    }


    /**
     * 获取账号ID => ACCOUNT NAME对
     * @return array
     */
    public static function getIdNamePairsByIdArr($idArr) {
        $pairs = array();
        $res = AliexpressAccount::model()
        ->getDbConnection()
        ->createCommand()
        ->select("id, short_name")
        ->from(self::tableName())
        ->where('id IN('.MHelper::simplode($idArr).')')
        ->queryAll();
        if (!empty($res)) {
            foreach ($res as $row)
                $pairs[$row['id']] = $row['short_name'];
        }
        return $pairs;      
    }
}