<?php
class JdAccount extends JdModel {
	
	const STATUS_OPEN    = 1; 		//帐号状态为启用
	const STATUS_CLOSED  = 0; 		//帐号状态为停用
	const STATUS_ISLOCK  = 1;       //帐号被锁定		
	const STATUS_NOTLOCK = 0;		//帐号未被锁定
	public $num;
	public $is_lock;
	/**
	 * (non-PHPdoc)
	 * @see UebModel::model()
	 */
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see CActiveRecord::tableName()
	 */
	public function tableName() {
		return 'ueb_jd_account';
	}
	
	public function attributeLabels() {
		return array(
				'is_locked'					=> Yii::t('system', 'Lock Status'),
				'status'         		 	=> Yii::t('system', 'Use Status'),
				'short_name'				=> Yii::t('system', 'Seller Name'),
		);
	}
	
	/**
	 * @return array search filter (name=>label)
	 */
	public function filterOptions() {
		$tmpStatus = isset($_REQUEST['status']) ? $_REQUEST['status'] : "";
		if( $tmpStatus === '' ){
			$tmpStatus = '';
		}else if( $tmpStatus === '0' ){
			$tmpStatus = self::STATUS_CLOSED;
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
	 * @desc 获取状态列表
	 * @param integer $status
	 * @return multitype:
	 */
	public function getStatus($status = null){
		$list = array(
				self::STATUS_OPEN => Yii::t('system','Open'),
				self::STATUS_CLOSED => Yii::t('system','ShutDown')
		);
		($status && array_key_exists($status, $list)) && $list = $list[$status];
		return $list;
	}
	
	public function search(){
		$sort = new CSort();
		$sort->attributes = array('defaultOrder'  => 'id');
		$criteria = $this->_setCDbCriteria();
		$dataProvider = parent::search(get_class($this), $sort,array(),$criteria);
		$data = $this->addition($dataProvider->data);
		$dataProvider->setData($data);
		return $dataProvider;
	}
	
	protected function _setCDbCriteria() {
		return null;
	}
	
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
	public function getAbleAccountList(){
		return $this->dbConnection->createCommand()
				->select('*')
				->from(self::tableName())
				->where('status = '.self::STATUS_OPEN)
				->queryAll();
	}
	/**
	 * @desc 获取账号id=》名称列表
	 * @return multitype:unknown
	 */
	public function getAccountPairs(){
		$newAccountList = array();
		$accountList = $this->getAbleAccountList();
		if($accountList){
			foreach ($accountList as $account){
				$newAccountList[$account['id']] = $account['short_name'];
			}
		}
		return $newAccountList;
	}
	/**
	 * @desc 帐号锁定状态
	 * @param int $status
	 */
	public static function getLockLable($status) {
		if ($status == self::STATUS_NOTLOCK) {
			echo '<font color="green" >' . Yii::t('system', 'Account OK') . '</font>';
		} else {
			echo '<font color="red" >' .   Yii::t('system', 'Account Locked') . '</font>';
		}
	}
	
	/**
	 * @desc 帐号使用状态
	 * @param int $status
	 */
	public static function getStatusLable($status) {
		if ($status == self::STATUS_OPEN) {
			echo '<font color="green" >' . Yii::t('system', 'Account Open') . '</font>';
		} else {
			echo '<font color="red" >' . Yii::t('system', 'Account Close') . '</font>';
		}
	}
	
	/**
	 * @desc 依据帐号简称获取帐号信息
	 * @param string $shortName
	 * @return mixed
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
	 * @desc 页面跳转地址
	 */
	public static function getIndexNavTabId() {
		return Menu::model()->getIdByUrl('/jd/jdaccount/list');
	}
	
	
	/**
	 * @desc 依据id冻结京东平台帐号
	 * @param int $id
	 * @return mixed
	 */
	public function lockAccount($id){
		$lockArr = array(
				'is_locked' 		 => self::STATUS_ISLOCK,
				'modify_user_id' => Yii::app()->user->id,
				'modify_time'	 =>date('Y-m-d H:i:s'),
		);
		$flag=$this->updateAll($lockArr,"id in ( '{$id}' )");
		return $flag;
	}
	
	/**
	 * @desc 依据id解冻京东平台帐号
	 * @param int $id
	 * @return $mixed
	 */
	public function unLockAccount($id){
		$lockArr = array(
			'is_locked'		=> self::STATUS_NOTLOCK,
			'modify_user_id'=> Yii::app()->user->id,
			'modify_time'   => date('Y-m-d H:i:s'),
		);
		$flag = $this->updateAll($lockArr,"id in ( '{$id}')");
		return $flag;
	}
	
	/**
	 * @desc 依据id关闭京东平台帐号
	 * @param int $id
	 * @return mixed
	 */
	public function shutDownAccount($id){
		$closeArr = array(
				'status' 		 => self::STATUS_CLOSED,
				'modify_user_id' => Yii::app()->user->id,
				'modify_time'	 => date('Y-m-d H:i:s'),
		);
		$flag=$this->updateAll($closeArr,"id in ( '{$id}' )");
		return $flag;
	}
	
	/**
	 * @desc 依据id开启京东平台帐号
	 * @param int $id
	 * @return mixed
	 */
	public function openAccount($id){
		$openArr = array(
				'status' 		 => self::STATUS_OPEN,
				'modify_user_id' => Yii::app()->user->id,
				'modify_time'	 =>date('Y-m-d H:i:s'),
		);
		$flag=$this->updateAll($openArr,"id in ( '{$id}' )");
		return $flag;
	}
	
	/**
	 * @desc 根据账号名称获取账号信息
	 * @param unknown $accountName
	 */
	public function getByAccountName($accountName) {
		$ret = LazadaAccount::model()->dbConnection->createCommand()
		->select('*')
		->from(self::tableName())
		->where("short_name = '{$accountName}'")
		->queryRow();
		return $ret;
	}
	
	/**
	 * @desc 根据account id获取账号信息
	 * @param unknown $accountID
	 * @return mixed
	 */
	public function getAccountInfoByID($accountID) {
		return $this->getDbConnection()->createCommand()
		->where("id='{$accountID}'")
		->from(self::tableName())
		->queryRow();
	}
        
        /**
         * 获取ID和NAME的键值对
         * @return multitype:unknown
         */
        public static function getIdNamePairs() {
            $pairs = array();
            $res = JdAccount::model()
            ->getDbConnection()
            ->createCommand()
            ->select("id, short_name")
            ->from(self::tableName())
            ->queryAll();
            if (!empty($res)) {
                    foreach ($res as $row)
                            $pairs[$row['id']] = $row['short_name'];
            }
            return $pairs;
        }
}