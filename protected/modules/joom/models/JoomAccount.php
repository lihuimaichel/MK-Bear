<?php
/**
 * @desc Joom账号
 * @author Gordon
 * @since 2015-06-22
 */
class JoomAccount extends JoomModel{
    
    /** @var tinyint 账号状态开启*/
    const STATUS_OPEN = 1;
     
    /** @var tinyint 账号状态关闭*/
    const STATUS_SHUTDOWN = 0;
    
    /** @var tinyint 账号状态锁定*/
    const STATUS_ISLOCK = 1;
    
    /** @var tinyint 账号状态未锁定*/
    const STATUS_NOTLOCK = 0;

	public $num;
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_joom_account';
    }
    
    /**
     * @desc 获取可用账号列表
     * @author Gordon
     */
    public static function getAbleAccountList(){
        return JoomAccount::model()->dbConnection->createCommand()
                       ->select('*')
                       ->from(self::tableName())
                       ->where('status = '.self::STATUS_OPEN)
                       ->queryAll();
    }
    
    /**
     * @desc 根据账号ID获取账号信息
     * @param int $id
     */
    public static function getAccountInfoById($id){
        return JoomAccount::model()->dbConnection->createCommand()->select('*')->from(self::tableName())->where('id = '.$id)->queryRow();
    }
    
    /**
     * 获取账号ID => ACCOUNT NAME对
     * @return array
     */
    public static function getIdNamePairs() {
    	$pairs = array();
    	$res = JoomAccount::model()
    	->getDbConnection()
    	->createCommand()
    	->select("id, account_name")
    	->from(self::tableName())
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
    	return JoomAccount::model()->getDbConnection()->createCommand()
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
    	$command = JoomAccount::model()->dbConnection->createCommand()
					    	->select('*')
					    	->from(self::tableName())
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
    	$sql = JoomAccount::model()->dbConnection->createCommand()->select('*')->from(self::model()->tableName())->where('id IN ('.implode(',', $id).')');
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

		//$request= new GetAccountTokenRequest();
		$request= new RefreshTokenRequest();
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
		return Menu::model()->getIdByUrl('/Joom/joomaccount/list');
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
				'name'     		 => 'account_name',
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
	public function search(){
		$sort = new CSort();
		$sort->attributes = array(
			'defaultOrder'  => 'id',
		);
		$criteria = null;
		$criteria = $this->_setCDbCriteria();
		$dataProvider = parent::search(get_class($this), $sort,array(),$criteria);

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
		foreach ($data as $key => $val){
			$data[$key]->num 	  = $val['id'];
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
			'account_name'				=> Yii::t('system', 'Seller Name'),
		);
	}
        
    /**
     * 获取可用账号ID => ACCOUNT NAME对
     * @return array
     */
    public static function getAvailableIdNamePairs($idArr = null) {
    	$pairs = array();
    	$andWhere = '';
    	if($idArr){
        	$andWhere = ' AND id IN('.MHelper::simplode($idArr).')';
        }
    	$res = JoomAccount::model()
    	->getDbConnection()
    	->createCommand()
    	->select("id, account_name")
    	->from(self::tableName())
        ->where('status = '.self::STATUS_OPEN.$andWhere)
    	->queryAll();
    	if (!empty($res)) {
    		foreach ($res as $row)
    			$pairs[$row['id']] = $row['account_name'];
    	}
    	return $pairs;
    }
}