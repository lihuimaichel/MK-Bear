<?php

/**
 * @desc Lazada账号
 * @author Gordon
 * @since 2015-08-07
 */
class LazadaAccount extends LazadaModel
{

	/** @var tinyint 账号状态开启 */
	const STATUS_OPEN = 1;

	/** @var tinyint 账号状态关闭 */
	const STATUS_SHUTDOWN = 0;

	/** @var tinyint 账号状态锁定 */
	const STATUS_ISLOCK = 1;

	/** @var tinyint 账号状态未锁定 */
	const STATUS_NOTLOCK = 0;

	/** @var int 账号编号 */
	public $num = null;

	/** @var string 站点名称 * */
	public $site_name = null;

	/** @var int 是否进行调价 * */
	public $is_change_price;

	/** @var tinyint 开启自动调价 */
	const OPEN_CHANGE_PRICE = 1;

	/** @var tinyint 关闭自动调价 */
	const CLOSE_CHANGE_PRICE = 0;

	static $NEW_API_ACCOUNT_IDS = array();//指定使用新api的账号id

	//B-SG账号ID
	public static $OVERSEAS_ACCOUNT_ID = array(35);

	public static function model($className = __CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @desc 数据库表名
	 * @see CActiveRecord::tableName()
	 */
	public function tableName()
	{
		return 'ueb_lazada_account';
	}

	/**
	 * @desc 属性翻译
	 */
	public function attributeLabels()
	{
		return array(
			'num'             => Yii::t('system', 'No.'),
			'is_lock'         => Yii::t('system', 'Lock Status'),
			'status'          => Yii::t('system', 'Use Status'),
			'seller_name'     => Yii::t('system', 'Seller Name'),
			'site_id'         => Yii::t('system', 'Site'),
			'is_change_price' => '是否进行自动调价',
		);
	}

	/**
	 * @desc 定义URL
	 */
	public static function getIndexNavTabId()
	{
		return Menu::model()->getIdByUrl('/lazada/lazadaaccount/list');
	}

	/**
	 * @desc 账号是否冻结的映射关系
	 */
	public static function accountIsOpen($status = '')
	{
		$statusArr = array(
			self::STATUS_OPEN => Yii::t('system', 'Account Open'),
			self::STATUS_SHUTDOWN => Yii::t('system', 'Account ShutDown'),
		);
		if ($status === '') {
			return $statusArr;
		} else {
			return $statusArr[$status];
		}
	}

	/**
	 * @desc 账号是否关闭的映射关系
	 */
	public static function accountIsLock($status = '')
	{
		$statusArr = array(
			self::STATUS_ISLOCK => Yii::t('system', 'Account Locked'),
			self::STATUS_NOTLOCK => Yii::t('system', 'Account OK'),
		);
		if ($status === '') {
			return $statusArr;
		} else {
			return $statusArr[$status];
		}
	}

	/**
	 * get lock lable
	 *
	 * @param type $status
	 */
	public static function getStatusLable($status)
	{
		if ($status == self::STATUS_OPEN) {
			echo '<font color="green" >' . Yii::t('system', 'Account Open') . '</font>';
		} else {
			echo '<font color="red" >' . Yii::t('system', 'Account Close') . '</font>';
		}
	}

	/**
	 * get status lable
	 *
	 * @param type $status
	 */
	public static function getLockLable($status)
	{
		if ($status == self::STATUS_NOTLOCK) {
			echo '<font color="green" >' . Yii::t('system', 'Account OK') . '</font>';
		} else {
			echo '<font color="red" >' . Yii::t('system', 'Account Locked') . '</font>';
		}
	}

	/**
	 * @return array search filter (name=>label)
	 */
	public function filterOptions()
	{
		$result = array(
			array(
				'name' => 'seller_name',
				'type' => 'text',
				'search' => 'LIKE',
				'alias' => 't',
			),
		);

		return $result;

	}

	/**
	 * order field options
	 * @return $array
	 */
	public function orderFieldOptions()
	{
		return array();
	}

	/**
	 * search SQL
	 * @return $array
	 */
	protected function _setCDbCriteria()
	{
//     	$criteria = new CDbCriteria();
//     	$criteria->select = '*';
//     	return $criteria;
		return NULL;
	}

	/**
	 * @return $array
	 */
	public function search()
	{
		$sort = new CSort();
		$sort->attributes = array(
			'defaultOrder' => 'id',
		);
		$criteria = null;
		$criteria = $this->_setCDbCriteria();
		$dataProvider = parent::search(get_class($this), $sort, array(), $criteria);

		$data = $this->addition($dataProvider->data);
		$dataProvider->setData($data);
		return $dataProvider;
	}

	/**
	 * @return $array
	 */
	public function addition($data)
	{
		foreach ($data as $key => $val) {
			$data[$key]->num = $val['id'];
			$data[$key]->site_name = LazadaSite::getSiteList($val['site_id']);
		}
		return $data;
	}

	/**
	 * @desc 获取可用账号列表
	 * @author Gordon
	 */
	public static function getAbleAccountList($siteID = null)
	{
		$command = LazadaAccount::model()->dbConnection->createCommand()
			->select('*')
			->from(self::tableName())
			->where('status = ' . self::STATUS_OPEN);
		if (!empty($siteID))
			$command->andWhere("site_id = :site_id", array(':site_id' => $siteID));
		return $command->queryAll();
	}

	/**
	 * @desc 根据小时取余法分组获取账号ID
	 */
	public function getGroupAccounts($offset = 0)
	{
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
		if (isset($groupList[$index]))
			return $groupList[$index];
		else
			return array();
	}

	/**
	 * @desc 根据账号ID获取账号信息
	 * @param int $id
	 */
	public static function getAccountInfoById($id)
	{
		$flag = false;
		if (!is_array($id)) {
			$flag = true;
			$id = array($id);
		}
		$sql = LazadaAccount::model()->dbConnection->createCommand()->select('*')->from(self::model()->tableName())->where('id IN (' . implode(',', $id) . ')');
		if ($flag) {
			return $sql->queryRow();
		} else {
			return $sql->queryAll();
		}
	}

	/**
	 * @desc 获取一个可用的Lazada账号
	 * @return mixed
	 */
	public static function getAbleAccountByOne()
	{
		return LazadaAccount::model()->dbConnection->createCommand()
			->select('*')
			->from(self::model()->tableName())
			->where('status = ' . self::STATUS_OPEN)
			->andWhere('is_lock = ' . self::STATUS_NOTLOCK)
			->queryRow();
	}

	/**
	 * @desc 冻结账号
	 * @return boolean
	 */
	public function lockAccount($accountID)
	{
		$lockArr = array();
		$lockArr = array(
			'is_lock' => self::STATUS_ISLOCK,
			'modify_user_id' => Yii::app()->user->id,
			'modify_time' => date('Y-m-d H:i:s'),
		);
		$flag = $this->updateAll($lockArr, "id in ( '{$accountID}' )");
		return $flag;
	}

	/**
	 * @desc 解冻账号
	 * @return boolean
	 */
	public function unLockAccount($accountID)
	{
		$unlockArr = array();
		$unlockArr = array(
			'is_lock' => self::STATUS_NOTLOCK,
			'modify_user_id' => Yii::app()->user->id,
			'modify_time' => date('Y-m-d H:i:s'),
		);
		$flag = $this->updateAll($unlockArr, "id in ( '{$accountID}' )");
		return $flag;
	}

	/**
	 * @desc 开启账号
	 * @return boolean
	 */
	public function openAccount($accountID)
	{
		$openArr = array();
		$openArr = array(
			'status' => self::STATUS_OPEN,
			'modify_user_id' => Yii::app()->user->id,
			'modify_time' => date('Y-m-d H:i:s'),
		);
		$flag = $this->updateAll($openArr, "id in ( '{$accountID}' )");
		return $flag;
	}

	/**
	 * @desc 关闭账号
	 * @return boolean
	 */
	public function shutDownAccount($accountID)
	{
		$closeArr = array();
		$closeArr = array(
			'status' => self::STATUS_SHUTDOWN,
			'modify_user_id' => Yii::app()->user->id,
			'modify_time' => date('Y-m-d H:i:s'),
		);
		$flag = $this->updateAll($closeArr, "id in ( '{$accountID}' )");
		return $flag;
	}


	/**
	 * @desc 修改账号
	 * @return boolean
	 */
	public function updateAccount($data, $id)
	{
		$updateArr = array();
		$oldAccountData = $this->getAccountInfoById($id);
		$updateArr = array(
			'seller_name' => $data['seller_name'] ? $data['seller_name'] : $oldAccountData['seller_name'],
			'token' => $data['token'] ? $data['token'] : $oldAccountData['token'],
			'modify_user_id' => Yii::app()->user->id,
			'modify_time' => date('Y-m-d H:i:s'),
		);
		$flag = $this->updateAll($updateArr, "id in ( '{$id}' )");
		return $flag;
	}

	/**
	 * @desc 根据账号名称获取账号信息
	 * @param unknown $accountName
	 */
	public function getByAccountName($accountName)
	{
		$ret = LazadaAccount::model()->dbConnection->createCommand()
			->select('*')
			->from(self::tableName())
			->where("seller_name = '{$accountName}'")
			->queryRow();
		return $ret;
	}

	/**
	 * @desc 根据账号获取API账号信息
	 * @param int $accountID
	 * @param tinyint $siteID
	 */
	public function getApiAccountByIDAndSite($accountID, $siteID)
	{
		return $this->dbConnection->createCommand()
			->select('*')
			->from(self::tableName())
			->where('account_id = ' . $accountID . ' AND site_id = ' . $siteID)
			->queryRow();
	}


	/**
	 * @desc 根据API账号ID获取API账号信息
	 * @param int $id
	 * @return array
	 */
	public function getApiAccountInfoByID($id)
	{
		return $this->dbConnection->createCommand()
			->select('*')
			->from(self::tableName())
			->where('id = ' . $id)
			->queryRow();
	}

	/**
	 * @desc 根据老系统账号ID获取市场业务系统账号信息
	 * @param unknown $accountID
	 */
	public function getAccountByOldAccountID($accountID)
	{
		return $this->dbConnection->createCommand()
			->select('*')
			->from(self::tableName())
			->where('old_account_id = ' . $accountID)
			->queryRow();
	}

	public function getAccountList($siteID = null)
	{
		$accountIdArr = array();
		if (isset(Yii::app()->user->id)) {
			$accountIdArr = LazadaAccountSeller::model()->getListByCondition('account_id', 'seller_user_id = ' . Yii::app()->user->id);
		}

		$data = array();
		$command = $this->getDbConnection()->createCommand()
			->from(self::tableName())
			->select("account_id, seller_name")
			->group("account_id");
		if (!empty($siteID)) {
			$command->where("site_id = :site_id", array(':site_id' => $siteID));
		}
		if (!empty($accountIdArr)) {
			$command->where("account_id IN(" . MHelper::simplode($accountIdArr) . ")");
		}
		$res = $command->queryAll();
		if (!empty($res)) {
			foreach ($res as $row)
				$data[$row['account_id']] = $row['seller_name'];
		}
		return $data;
	}

	public function getAccountListWithID($siteID = null)
	{
		$data = array();
		$command = $this->getDbConnection()->createCommand()
			->from(self::tableName())
			->select("id, account_id, seller_name")
			->group("account_id");
		if (!empty($siteID))
			$command->where("site_id = :site_id", array(':site_id' => $siteID));
		$res = $command->queryAll();
		if (!empty($res)) {
			foreach ($res as $row)
				$data[$row['id']] = $row['seller_name'];
		}
		return $data;
	}

	public function getSiteList($accountID)
	{
		return $this->getDbConnection()->createCommand()
			->select("site_id")
			->from(self::tableName())
			->where("account_id = :account_id", array(':account_id' => $accountID))
			->queryColumn();
	}

	/**
	 * @desc 获取带站点的账号名称简写
	 * @param
	 */
	public function getShortAccountNameList($id = null)
	{
		$data = array();
		$command = $this->getDbConnection()->createCommand()
			->from(self::tableName())
			->select("id,site_id,account_id, seller_name");
		//->group("account_id");
		if (!empty($id))
			$command->where("id = :id", array(':id' => $id));
		$res = $command->queryAll();
		if (!empty($res)) {
			foreach ($res as $row) {
				$site_short_name = LazadaSite::model()->getSiteShortName($row['site_id']);
				$account_first_name = substr($row['seller_name'], 0, 1);
				$account_name = strtoupper($account_first_name) . '-' . strtoupper($site_short_name);
				$data[$row['id']] = $account_name;
			}
		}
		return $data;
	}


	public static function getIdNamePairs()
	{
		$data = array();
		$command = LazadaAccount::model()->getDbConnection()->createCommand()
			->from(self::tableName())
			->select("old_account_id, short_name");
		$res = $command->queryAll();
		if (!empty($res)) {
			foreach ($res as $row) {
				$data[$row['old_account_id']] = $row['short_name'];
			}
		}
		return $data;
	}


    /**
     * @return string
     *
     * 返回以 account 为键值，id为值的数组
     */
    public static function getAccountToAccountId()
    {
        $data = array();
        $command = LazadaAccount::model()->getDbConnection()->createCommand()
            ->from(self::tableName())
            ->select("old_account_id, id");
        $res = $command->queryAll();
        if (!empty($res)) {
            foreach ($res as $row) {
                $data[$row['old_account_id']] = $row['id'];
            }
        }
        return $data;
    }

	/**
	 * getOneByCondition
	 * @param  string $fields
	 * @param  string $where
	 * @param  mixed $order 
	 * @return array
	 */
	public function getOneByCondition($fields = '*', $where = '1', $order = '')
	{
		$cmd = $this->dbConnection->createCommand();
		$cmd->select($fields)
			->from(self::tableName())
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
	public function getListByCondition($fields = '*', $where = '1', $order = '')
	{
		$cmd = $this->dbConnection->createCommand();
		$cmd->select($fields)
			->from(self::tableName())
			->where($where);
		$order != '' && $cmd->order($order);
		return $cmd->queryAll();
	}

	/**
	 * 获取键值为id，值为seller_name的方法
	 * @return array
	 */
	public static function getIdSellerNamePairs()
	{
		$data = array();
		$command = LazadaAccount::model()->getDbConnection()->createCommand()
			->from(self::tableName())
			->select("id, seller_name");
		$res = $command->queryAll();
		if (!empty($res)) {
			foreach ($res as $row) {
				$data[$row['id']] = $row['seller_name'];
			}
		}
		return $data;
	}

	/**
	 * get lock lable
	 * @param type $status
	 */
	public static function getChangePriceStatus($status)
	{
		if ($status == 1) {
			echo '<font color="red" >是</font>';
		} else {
			echo '<font color="green" >否</font>';
		}
	}


	/**
	 * @desc 开启或关闭自动调价
	 * @return boolean
	 */
	public function changePriceStatus($accountID, $changeStatus)
	{
		$lockArr = array(
			'is_change_price' => $changeStatus,
			'modify_user_id'  => Yii::app()->user->id,
			'modify_time'     => date('Y-m-d H:i:s'),
		);
		$flag = $this->updateAll($lockArr, "id in ( '{$accountID}' )");
		return $flag;
	}
}