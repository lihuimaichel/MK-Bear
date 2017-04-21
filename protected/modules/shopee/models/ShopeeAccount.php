<?php
/**
 * @desc shopee账号
 * @author lihy	
 * @since 2016-10-17
 */
class ShopeeAccount extends ShopeeModel{
    
    /** @var tinyint 账号状态开启*/
    const STATUS_OPEN = 1;
     
    /** @var tinyint 账号状态关闭*/
    const STATUS_SHUTDOWN = 0;
    
    /** @var tinyint 账号状态锁定*/
    const STATUS_ISLOCK = 1;
    
    /** @var tinyint 账号状态未锁定*/
    const STATUS_NOTLOCK = 0;

	public $num;
	public $departmentName;
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }


    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_shopee_account';
    }
    
    /**
     * @desc 获取可用账号列表
     * @author Gordon
     */
    public static function getAbleAccountList(){
        return ShopeeAccount::model()->dbConnection->createCommand()
                       ->select('*')
                       ->from(self::tableName())
                       ->where('status = '.self::STATUS_OPEN)
                       ->queryAll();
    }
    
    /**
     * @desc 根据小时取余法分组获取账号ID
     */
    public function getGroupAccounts($offset = 0) {
        //20-23, 0-5
    	$groupList = array();
    	$accountInfos = self::getAbleAccountList();
    	//根据账号ID最后一个数字分组
    	foreach ($accountInfos as $accountInfo) {
    		$key = substr($accountInfo['id'], -1, 1);

            //因为3、4点属备份时间，相应推后2小时
            if (date('G') == 6 && $key == 3) {
                $key = 6;
            }
            if (date('G') == 7 && $key == 4) {
                $key = 7;
            }  

    		$groupList[$key][] = $accountInfo['id'];
    	}
    	//获取当前时间小时对应的数组
    	$hour = date('G');
        
    	$index = ($hour + $offset) % 10;
    	if(isset($groupList[$index]))
    		return $groupList[$index];
    	else 
    		return array();
    }

    /**
     * getOneByCondition
     * @param  string $fields
     * @param  string $where 
     * @param  mixed $order  
     * @return array        
     */
    public function getOneByCondition($fields='*', $where='1',$order='') {
        $cmd = $this->dbConnection->createCommand();
        $cmd->select($fields)
            ->from($this->tableName())
            ->where($where);
        $order != '' && $cmd->order($order);
        $cmd->limit(1);
        return $cmd->queryRow();
    }

    /**
     * getListByCondition
     * @param  string $fields
     * @param  string $where 
     * @param  mixed $order  
     * @return array        
     */
    public function getListByCondition($fields='*', $where='1',$order='') {
        $cmd = $this->dbConnection->createCommand();
        $cmd->select($fields)
            ->from($this->tableName())
            ->where($where);
        $order != '' && $cmd->order($order);
        return $cmd->queryAll();
    }     

    /**
     * @desc 根据账号ID获取账号信息
     * @param int $id
     */
    public static function getAccountInfoById($id){
        return ShopeeAccount::model()->dbConnection->createCommand()->select('*')->from(self::tableName())->where('id = '.$id)->queryRow();
    }
    
    /**
     * 获取账号ID => ACCOUNT NAME对
     * @return array
     */
    public static function getIdNamePairs() {
    	$pairs = array();
    	$res = ShopeeAccount::model()
    	->getDbConnection()
    	->createCommand()
    	->select("id, account_name")
    	->from(self::tableName())
    	->order("account_name asc")
    	->queryAll();
    	if (!empty($res)) {
    		foreach ($res as $row)
    			$pairs[$row['id']] = $row['account_name'];
    	}
    	return $pairs;
    }
    
    /**
     * @desc 根据账号ID获取账号名称
     * @param string $accountId
     */
    public function getAccountNameById($accountId) {
    	return self::model()->getDbConnection()
    	->createCommand()
    	->select("account_name")
    	->from(self::tableName())
    	->where("id = :id", array(':id' => $accountId))
    	->queryScalar();
    }
    
    /**
     * @desc 根据账号名称获取账号信息
     * @param unknown $accountName
     */
    public static function getByAccountName($accountName) {
    	return ShopeeAccount::model()->getDbConnection()->createCommand()
		    	->select('*')
		    	->from(self::tableName())
		    	->where("account_name = '{$accountName}'")
		    	->queryRow();
    }
    /**
     * @desc 获取计划任务执行同一组账号列表
     */
    public function getCronGroupAccounts() {
    	$accountList = array();
    	$groupList = array();
    	$accountInfos = self::getAbleAccountList();
    	//根据账号ID最后一个数字分组
    	foreach ($accountInfos as $accountInfo) {
    		$key = substr($accountInfo['id'], -1, 1);
    		$groupList[$key][] = $accountInfo['id'];
    		$accountIDs[] = $accountInfo['id'];
    	}
    	return $accountIDs;
    	//获取当前时间小时对应的数组
    	$offset = 6;
    	$hour = date('H');
    	$index = ($hour + $offset) % 24;
    	if(isset($groupList[$index]))
    		return $groupList[$index];
    	else 
    		return array();
    }    
    /**
     * @desc 获取过滤掉的可用账户列表
     * @param unknown $ids
     * @return mixed
     */
    public static function getAbleAccountListByFilterId($ids){
    	if(is_array($ids) && $ids)
    		$ids = implode(",", $ids);
    	$command = ShopeeAccount::model()->dbConnection->createCommand()
					    	->select('*')
					    	->from(self::tableName())
					    	->order("account_name asc")
					    	->where('status = '.self::STATUS_OPEN .' AND is_lock='.self::STATUS_NOTLOCK);
    	if($ids){
    		$command->andWhere('id not in('.$ids.')');
    	}
		return $command->queryAll();
    }
    
    /**
     * @desc 根据账号ID获取账号信息
     * @param int $id
     */
    public static function getAccountInfoByIds($id){
    	$flag = false;
    	if( !is_array($id) ){
    		$flag = true;
    		$id = array($id);
    	}
    	$sql = ShopeeAccount::model()->dbConnection->createCommand()->select('*')->from(self::model()->tableName())->where('id IN ('.implode(',', $id).')');
    	if( $flag ){
    		return $sql->queryRow();
    	}else{
    		return $sql->queryAll();
    	}
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

		if(isset($response->data->access_token) && !empty($response->data->access_token)){
			$updateArr=array('access_token'=>$response->data->access_token);
			$flag=$this->updateAll($updateArr,"id in ( '{$accountId}' )");
		}else{
			$flag=false;
		}
		return $flag;

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
	 * @desc 定义URL
	 */
	public static function getIndexNavTabId() {
		return Menu::model()->getIdByUrl('/Wish/ShopeeAccount/list');
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
                'name' => 'account_name',
                'type' => 'dropDownList',
                //'expr' => 'IF(p.account_id=0, 1, 2)',
                'expr'=>" left(account_name,   POSITION('.' IN account_name) - 1)",
                'search' => '=',
                'data' => function (){
                    $accountList = ShopeeAccount::model()->getIdNamePairs();

                    $result = array();

                    foreach($accountList as $id=> $name) {
                        list($account, $site) = explode('.', $name);
                        $result[$account] = $account;
                    }

                    return $result;
                }
            ),
            array(
                'name' => 'site',
                'type' => 'dropDownList',
                'search' => '=',
                'data' => ShopeeAccount::model()->getSiteCodeList(true)
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
	 * @desc 根据account_name获取账号信息
	 * @param unknown $account_name
	 */
	public function getInfoByAccountname($account_name){
		$ret = $this->dbConnection->createCommand()
			->select('*')
			->from(self::tableName())
			->where('account_name="'.$account_name.'"')
			->queryRow();
		return $ret;
	}

	/**
	 * @return $array
	 */
	public function search($model = null, $sort = array(), $with = array(),$criteria = null) {
		$sort = new CSort();
		$sort->attributes = array(
			'defaultOrder'  => 'id',
		);

		$criteria = $this->_setCDbCriteria();
		$dataProvider = parent::search(get_class($this), $sort, $with, $criteria);

		$data = $this->addition($dataProvider->data);
		$dataProvider->setData($data);
		return $dataProvider;
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
	public function addition($data){
        $departmentList = Department::model()->getDepartmentByKeywords('shopee');
        foreach ($data as $key => $val){
			$data[$key]->num 	  = $val['id'];

			$data[$key]->departmentName = isset($departmentList[$val['department_id']])?$departmentList[$val['department_id']] :"";

		}
		return $data;
	}
	/**
	 * @desc 属性翻译
	 */
	public function attributeLabels() {
		return array(
			'is_lock'					=> Yii::t('system', 'Lock Status'),
			'status'         		 	=> Yii::t('system', 'Use Status'),
			'account_name'				=> Yii::t('shopee', 'Account'),
            'site' => Yii::t('shopee', 'Site'),
            'site_code' => Yii::t('shopee', 'Site'),
            'open_time'=> Yii::t('shopee', 'Open time')
        );
	}
        
    /**
     * 获取可用账号ID => ACCOUNT NAME对
     * @return array
     */
    public static function getAvailableIdNamePairs() {
    	$pairs = array();
    	$res = ShopeeAccount::model()
    	->getDbConnection()
    	->createCommand()
    	->select("id, short_name")
    	->from(self::tableName())
        ->where('status = '.self::STATUS_OPEN)
    	->queryAll();
    	if (!empty($res)) {
    		foreach ($res as $row)
    			$pairs[$row['id']] = $row['short_name'];
    	}
    	return $pairs;
    }

    /**
     * 获取账号站点
     */
    public function getSiteCodeList($toArray = false)
    {

        $result = array();
        $queryBuilder = $this->getDbConnection()->createCommand()->from($this->tableName())->select('site')->group('site');
        $siteList = $queryBuilder->queryAll();

        if ($toArray) {
            foreach($siteList as $site) {
                $result[$site['site']] = $site['site'];
            }

            return $result;
        }

        return $siteList;
    }



    /**
     * 保存账号
     * @param $accountInfo
     */
    public function updateOrCreate($accountInfo)
    {
        if (!isset($accountInfo['account_name'])) {
            throw new \Exception(Yii::t('shopee', 'Account name must set'));
        }

        $info = $this->getByAccountName($accountInfo['account_name']);
        if (!$info) {
            $success = $this->getDbConnection()->createCommand()->insert(
                $this->tableName(),
                $accountInfo
            );

            if (!$success) {
                throw  new \Exception(Yii::t('shopee', 'Create shopee account failed'));
            }

            return $this->getDbConnection()->getLastInsertID();
        } else {
            $success = $this->getDbConnection()->createCommand()->update(
                $this->tableName(),
                $accountInfo,
                'id=:id',
                array(':id'=> $info['id'])
            );

            if (!$success) {
                throw  new \Exception(Yii::t('shopee', 'Update shopee account failed'));
            }
        }
    }
}