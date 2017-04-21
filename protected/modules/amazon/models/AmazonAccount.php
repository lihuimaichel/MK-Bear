<?php
/**
 * @desc Amazon 账号模型类
 * @author zhangf
 * @since 2015-7-7
 *
 */
class AmazonAccount extends AmazonModel {
    /** @var tinyint 账号状态开启*/
    const STATUS_OPEN = 1;
     
    /** @var tinyint 账号状态关闭*/
    const STATUS_SHUTDOWN = 0;

    /** @var tinyint 账号状态锁定*/
    const STATUS_ISLOCK = 1;
    
    /** @var tinyint 账号状态未锁定*/
    const STATUS_NOTLOCK = 0;

	/** @var tinyint 深圳amazon部*/
	const DEPARTMENT_SHENZHEN = 5;

	/** @var tinyint 长沙amazon部*/
	const DEPARTMENT_CHANGSHA = 24;

	public $num;
    
    public static function model($className = __CLASS__) {
    	return parent::model($className);
    }
    
    /**
     * (non-PHPdoc)
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
    	return 'ueb_amazon_account';
    }

    /**
     * 通过国家代码获取各（国家）地区信息
     * @return array
     */
    public static function getListByCountryCode() {
        return array(
        	'us' => array(
				'web_site'      => 'amazon.com',
				'currency_name' => '美元',
				'currency_code' => 'USD',
        		),
			'cn' => array(
				'web_site'      => 'amazon.cn',
				'currency_name' => '人民币',
				'currency_code' => 'CNY',
				),
			'jp' => array(
				'web_site'      =>'amazon.co.jp',
				'currency_name' => '日元',
				'currency_code' => 'JPY',
				),
			'in' => array(
				'web_site'      =>'amazon.in',
				'currency_name' => '卢比',
				'currency_code' => 'INR',
				),				
			'fr' => array(
				'web_site'      =>'amazon.fr',
				'currency_name' => '欧元',
				'currency_code' => 'EUR',
				),					
			'de' => array(
				'web_site'      =>'amazon.de',
				'currency_name' => '欧元',
				'currency_code' => 'EUR',
				),				
			'it' => array(
				'web_site'      =>'amazon.it',
				'currency_name' => '欧元',
				'currency_code' => 'EUR',
				),				
			'es' => array(
				'web_site'      =>'amazon.es',
				'currency_name' => '欧元',
				'currency_code' => 'EUR',
				),					
			'uk' => array(
				'web_site'      =>'amazon.co.uk',
				'currency_name' => '英镑',
				'currency_code' => 'GBP',
				),				
			'nl' => array(
				'web_site'      =>'amazon.nl',
				'currency_name' => '欧元',
				'currency_code' => 'EUR',
				),					
			'ca' => array(
				'web_site'      =>'amazon.ca',
				'currency_name' => '加拿大元',
				'currency_code' => 'CAD',
				),					
			'mx' => array(
				'web_site'      =>'amazon.com.mx',
				'currency_name' => '墨西哥比索',
				'currency_code' => 'MXN',
				),					
			'br' => array(
				'web_site'      =>'amazon.com.br',
				'currency_name' => '巴西雷亚尔',
				'currency_code' => 'BRL',
				),					
			'au' => array(
				'web_site'      =>'amazon.com.au',
				'currency_name' => '澳大利亚元',
				'currency_code' => 'AUD',
				)	     	
        );
    }     

    /**
     * 通过国家代码获取各地区货币
     * @return array
     */
    public static function getCurrencyCodeByCountryCode() {
    	$currencyList = array();
        $list = self::getListByCountryCode();
        if ($list){
        	foreach($list as $key => $item){
        		$currencyList[$key] = $item['currency_code'];
        	}
        }
        return $currencyList;
    }   

    /**
     * 通过国家代码获取各地区商城网站
     * @return array
     */
    public static function getWebsiteByCountryCode() {
    	$webList = array();
        $list = self::getListByCountryCode();
        if ($list){
        	foreach($list as $key => $item){
        		$webList[$key] = $item['web_site'];
        	}
        }
        return $webList;
    }       
    
    /**
     * @desc 获取指定账号的信息
     * @param integer $accountID
     * @return mixed
     */
    public static function getAccountInfoById($accountID) {
    	return self::model()->getDbConnection()->createCommand()->from(self::model()->tableName())->where("id = :id", array(':id' => $accountID))->queryRow();
    }
    
    /**
     * @desc 获取可用账号列表
     * @author Gordon
     */
    public static function getAbleAccountList($site = null){   
        if ($site){
            $ret = AmazonAccount::model()->dbConnection->createCommand()
            ->select('*')
            ->from(self::model()->tableName())
            ->where('status = '.self::STATUS_OPEN)
            ->andWhere('country_code = "'.$site.'"')
            ->queryAll();
        } else{
        	$ret = AmazonAccount::model()->dbConnection->createCommand()
        	->select('*')
        	->from(self::model()->tableName())
        	->where('status = '.self::STATUS_OPEN)
        	->queryAll();
        }
        return $ret;
    }

    /**
     * 获取lazada平台站点ID与国家code列表
     * @return array
     */
    public function getSiteCountryCodeList() {
        $list = self::getAbleAccountList();
        $countryList = array();
        if (!empty($list)) {
            foreach ($list as $v) {
                $countryList[] = strtoupper($v['country_code']);
            }
        }
        return array('errorCode'=>'0','errorMsg'=>'ok','data'=>array_unique($countryList));
    }    
    
    /**
     * 获取账号ID => ACCOUNT NAME对（有效账号）
     * @return array
     */
    public static function getIdNamePairs() {
    	$pairs = array();
    	$res = AmazonAccount::model()
    	->getDbConnection()
    	->createCommand()
    	->select("id, account_name")
    	->from(self::tableName())
    	->where('status=1')
    	->queryAll();
    	if (!empty($res)) {
    		foreach ($res as $row)
    			$pairs[$row['id']] = $row['account_name'];
    	}
    	return $pairs;    	
    }

    
    /**
     * 获取账号ID => ACCOUNT NAME对（所有账号）
     * @return array
     */
    public static function getAllIdNamePairs() {
        $pairs = array();
        $res = AmazonAccount::model()
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
     * @desc 根据账号名查账号信息
     * @param unknown $accountName
     */
    public static function getByAccountName($accountName) {
    	return self::model()->dbConnection->createCommand()
		    	->select('*')
		    	->from(self::tableName())
		    	->where(" account_name = '{$accountName}'")
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
    	$accountIDs = array();
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
    	if (isset($groupList[$index]))
    		return $groupList[$index];
    	return array();
    }    
    
    /**
     * @desc 根据account ids获取账号列表
     * @param unknown $ids
     */
    public function getAccountInfoByIds($ids) {
        return $this->findAll("id in (" . implode(',', $ids) . ")");
    }

	//================= search start=================//
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
			'account_name'				=> Yii::t('system', 'user_name'),
			'department_id'             => '所属部门'
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
			$tmpStatus = self::STATUS_SHUTDOWN;
		}else if( trim($tmpStatus) === '1'){
			$tmpStatus = self::STATUS_OPEN;
		}
		$isLock = isset($_REQUEST['is_lock']) ? $_REQUEST['is_lock'] : "";
		$result = array(
			array(
				'name'     		 => 'account_name',
				'type'     		 => 'text',
				'search'   		 => 'LIKE',
				'alias'    		 => 't',
			),
			array(
				'name'          => 'is_lock',
				'type'          => 'dropDownList',
				'search'        => '=',
				'data'          => $this->getLock(),
				'htmlOptions'   => array(),
				'value'			=> $isLock,
				'alias'			=> 't'
			),
			array(
				'name'          => 'status',
				'type'          => 'dropDownList',
				'search'        => '=',
				'data'          => $this->getStatus(),
				'htmlOptions'   => array(),
				'value'			=> $tmpStatus,
				'alias'			=> 't'
			),
			array(
				'name'          => 'department_id',
				'type'          => 'dropDownList',
				'search'        => '=',
				'data'          => $this->getDepartment(),
				'htmlOptions'   => array(),
				'value'			=> Yii::app()->request->getParam('department_id'),
				'alias'			=> 't'
			)
		);

		return $result;

	}

	/**
	 * 获取可用账号ID => ACCOUNT NAME对
	 * @return array
	 */
	public static function getAvailableIdNamePairs() {
		$pairs = array();
		$res = AmazonAccount::model()
			->getDbConnection()
			->createCommand()
			->select("id, account_name")
			->from(self::tableName())
			->where('status = '.self::STATUS_OPEN)
			->queryAll();
		if (!empty($res)) {
			foreach ($res as $row)
				$pairs[$row['id']] = $row['account_name'];
		}
		return $pairs;
	}


	/**
	 * @desc 获取部门列表
	 * @param integer $status
	 * @return multitype:
	 */
	public function getDepartment($department_id = null){
		$list = array(self::DEPARTMENT_SHENZHEN=>'深圳amazon部', self::DEPARTMENT_CHANGSHA=>'长沙amazon部');
		($department_id && array_key_exists($department_id, $list)) && $list = $list[$department_id];
		return $list;
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
	 * @desc 获取冻结状态列表
	 * @param integer $status
	 * @return multitype:
	 */
	public function getLock($isLock = null){
		$list = array(
			self::STATUS_ISLOCK => '锁定帐号',
			self::STATUS_NOTLOCK => '未锁帐号'
		);
		($isLock && array_key_exists($isLock, $list)) && $list = $list[$isLock];
		return $list;
	}


	/**
	 * get lock lable
	 * @param type $status
	 */
	public static function getDepartmentLable($department_id) {
		echo self::getDepartment($department_id);
	}

	//================= search end=================//


	public static function getIndexNavTabId() {
		return Menu::model()->getIdByUrl('/amazon/amazonaccount/list');
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
	 * [getListByCondition description]
	 * @param  string $fields [description]
	 * @param  string $where [description]
	 * @param  mixed $order [description]
	 * @return [type]         [description]
	 * @author yangsh
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
}