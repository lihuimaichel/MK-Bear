<?php
/**
 * @desc Ebay账号
 * @author Gordon
 * @since 2015-06-06
 */
class EbayAccount extends EbayModel{
    
    /** @var tinyint 账号状态开启*/
    const STATUS_OPEN = 1;
    
    /** @var tinyint 账号状态关闭*/
    const STATUS_SHUTDOWN = 0;
    
    /** @var tinyint 账号状态锁定*/
    const STATUS_ISLOCK = 1;
    
    /** @var tinyint 账号状态未锁定*/
    const STATUS_NOTLOCK = 0;
    
    const STORE_LEVEL_NONE = 0; //非店铺
    const STORE_LEVEL_BASIC = 1; //基本店铺
    const STORE_LEVEL_FEATURED = 2; //中等店铺
    const STORE_LEVEL_ANCHOR = 3; //高级店铺

    //设置ebay账号是否具有自动上传的权限
    CONST AUTO_UPLOAD_ON    = 1,    //ebay账号有自动上传的权限
          AUTO_UPLOAD_OFF   = 0;    //ebay账号没有自动上传的权限
    
    
    /** @var 海外账号短名称 */
    public static $OVERSEAS_ACCOUNT_SHORT_NAME = array('M','H2','11','12','14','18','19','24','O3','B8','WZ','TS','LS','L1','X1','X2','X3','HGGL','HGSY');//海外仓账号短名称
    
    public static $OVERSEAS_ACCOUNT_ID = array(13,37,54,55,57,59,60,62,34,39,69,73,74,77,75,76,78,79,80);//海外仓账号ID
    
    /** @var 自动刊登数量*/
    public $publish_count;
    
    /** @var 是否自动调数量*/
    public $if_adjust_count;

    /** @var 是否自动上传 */
    public $is_auto_upload;

    /** @var 使用状态 */
    public $status;

    /** @var 冻结状态 */
    public $is_lock;

    /** @var 邮箱 */
    public $email;

    /** @var 账号简称 */
    public $short_name;


    public $accountSiteList = null;
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
    	return array(
    			array('store_name,user_name,short_name,store_level,commission','required'),
    			//array('store_name,user_name,short_name,store_level,commission,paypal_group_id','required'),paypal规则开启使用这行换掉上一行
    			array('status','safe'),
    			array('is_lock','safe'),
    			array('store_site,store_level,commission,email,add_qty,auto_revise_qty,relist_qty,is_eub,is_auto_upload,is_eub_under5,update_eub,is_restrict,is_free_shipping','safe'),
    	);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_ebay_account';
    }

    /**
     * @desc 获取自动补库存账号ID
     * @author Gordon
     */
    public static function getAutoReviseQtyAccountIds(){
        return EbayAccount::model()->getDbConnection()->createCommand()
                       ->select('id')
                       ->from(self::model()->tableName())
                       ->where('status = '.self::STATUS_OPEN)
                       ->andWhere('auto_revise_qty=0')
                       ->queryColumn();
    }

    /**
     * @desc 带参数搜索账号
     * @author ketu.lai
     * @date 2017/02/20
     * @param array $filters
     * @return array|CDbDataReader
     */
    public function findAccountByFilter($filters = array())
    {
        $command = self::model()->getDbConnection()->createCommand()
            ->select("id, short_name, store_name")
            ->where('status='.self::STATUS_OPEN)
            ->from($this->tableName());
        foreach($filters as $column=> $filter) {
            if(is_array($filter)) {
                list($op, $value) = array_shift($filter);
                $command->andWhere($column. $op. ":".$column)->bindParam(':'.$column, $value);
            }else {
                $command->andWhere($column."=:".$column)->bindParam(':'.$column, $filter);
            }
        }

        return $command->queryAll();
    }
    public function getAccountByStoreName($storeName)
    {
        $account = self::model()->getDbConnection()->createCommand()
            //->setFetchMode(PDO::FETCH_CLASS, __CLASS__)
            ->select('id, short_name, store_name')
            ->from($this->tableName())
            ->where(" store_name = :storeName")->bindParam(':storeName', $storeName)
            ->queryRow();
        return $account? (object) $account: false;
    }


    /**
     * @desc 获取可用账号列表
     * @author Gordon
     */
    public static function getAbleAccountList(){
        return EbayAccount::model()->getDbConnection()->createCommand()
                       ->select('*')
                       ->from(self::model()->tableName())
                       ->where('status = '.self::STATUS_OPEN)
                       ->queryAll();
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
     * @desc 获取可用账号分组
     * @param int $groupnum 分组数
     * @param int $offset 余数（分组值）
     * @author Liz
     */
    public function getAbleAccountListByGroup($groupnum = 1,$offset = 0){
        
        $sql = "select * from " .self::model()->tableName(). " where id % " .$groupnum. " = " .$offset. " and status =" .self::STATUS_OPEN. "";
        // $ret = self::model()->getDbConnection()->createCommand($sql)->query();     
        // $ret = Yii::app()->db->createCommand($sql)->execute();             
        return $this->getDbConnection()->createCommand($sql)->queryAll();
    }    
    
    /**
     * @desc 根据账号ID获取账号信息
     * @param int $id
     */
    public static function getAccountInfoById($id){
        $flag = false;
        if( !is_array($id) ){
            $flag = true;
            $id = array($id);
        }
        $sql = EbayAccount::model()->getDbConnection()->createCommand()->select('*')->from(self::model()->tableName())->where('id IN ('.implode(',', $id).')');
        if( $flag ){
            return $sql->queryRow();
        }else{
            return $sql->queryAll();
        }
    }
    
    /**
     * @desc 获取一个可用的Ebay账号
     * @return mixed
     */
    public function getAbleAccountByOne(){
        return EbayAccount::model()->getDbConnection()->createCommand()
                    ->select('*')
                    ->from(self::model()->tableName())
                    ->where('status = '.self::STATUS_OPEN)
                    ->andWhere('is_lock = '.self::STATUS_NOTLOCK)
                    ->andWhere('id = 58')
                    ->queryRow();
    }
    
    /**
     * @desc 页面的跳转链接地址
     */
    public static function getIndexNavTabId() {
    	return Menu::model()->getIdByUrl('/ebay/ebayaccount/index');
    }
    

    /**
     * @since 2015/07/31
     * @desc 获取ebay帐号的使用状态
     * @return array
     */
    public function getAccountStatus($accStatus=''){
    	$accStatusArr = array
    	(
    			self::STATUS_OPEN 		=> Yii::t('system', 'Account Open'),
    			self::STATUS_SHUTDOWN 	=> Yii::t('system', 'Account Close'),
    	);
    	if($accStatus != ''){
    		return $accStatusArr[$accStatus];
    	}else{
    		return $accStatusArr;
    	}
    }
    /**
     * @desc 获取店铺级别
     * @param string $level
     */
    public static function getStoreLevel($level = ''){
    	$storeLevel = array(
    					self::STORE_LEVEL_NONE	=>	Yii::t('ebay', 'Store Level Standard'),
    					self::STORE_LEVEL_BASIC	=>	Yii::t('ebay', 'Store Level Basic'),
    					self::STORE_LEVEL_FEATURED	=>	Yii::t('ebay', 'Store Level Featured'),
    					self::STORE_LEVEL_ANCHOR	=>	Yii::t('ebay', 'Store Level Anchor'),
    				);
    	if(!empty($level))
    		return $storeLevel[$level];
    	return $storeLevel;
    }
    
    /**
     * @since 2015/07/31
     * @desc 获取ebay帐号的冻结状态
     * @return array
     */
    public static function getAccountLockStatus($accStatus = ''){
    	$accStatusArr = array(
    			self::STATUS_NOTLOCK         =>Yii::t('system', 'Account OK'),
    			self::STATUS_ISLOCK          =>Yii::t('system', 'Account Locked'),
    	);
    	if($accStatus != ''){
    		return $accStatusArr[$accStatus];
    	}else{
    		return $accStatusArr;
    	}
    }
    
    public function attributeLabels(){
    	return array(
    			'id'             =>Yii::t('ebay', 'Account Id'),
    			'email'          =>Yii::t('system', 'Email'),
    			'user_name'      =>Yii::t('system', 'user_name'),
    			'store_name'     =>Yii::t('system', 'store_name'),
    			'short_name'	 =>Yii::t('system', 'short_name'),	
    			'use_status'     =>Yii::t('system', 'use_status'),
                'status'         =>Yii::t('system', 'use_status'),
    			'frozen_status'  =>Yii::t('system', 'frozen_status'),
                'is_lock'        =>Yii::t('system', 'frozen_status'),
    			'group_id'       =>Yii::t('system', 'Group Id'),
    			'group_name'     =>Yii::t('system', 'Group Name'),
    			'add_qty'		 =>Yii::t('system', 'Publish Count'),
    			'auto_revise_qty'=>Yii::t('system', 'IfAdjust Count'),
    			'relist_qty'  	 =>	Yii::t('ebay', 'Relist Count'),
    			'is_eub'		 =>	Yii::t('ebay', 'Is EUB'),
    			'is_eub_under5'	 =>	Yii::t('ebay', 'Is EUB Under 5 Dollar'),
                'is_auto_upload' => Yii::t('ebay', 'Is Auto Upload'),
    			'update_eub'	 =>	Yii::t('ebay', 'Whether EUB price has been Changed'),
    			'is_restrict'	=>	Yii::t('ebay', 'Whether is limited permanent'),
    			'is_free_shipping'	=>	Yii::t('ebay', 'Whether is free shipping'),
    			'commission'		=>	Yii::t('ebay', 'Commission'),
    			'store_level'		=>	Yii::t('ebay', 'Store Level'),
    			'store_site'		=>	Yii::t('ebay', 'Store Site'),
    			'paypal_group_id'	=>	Yii::t('ebay', 'Paypal Group Id'),
    	);
    }
    
    public function filterOptions(){
    	$result = array(
    			array(
    					'name'      => 'email',
    					'type'      => 'text',
    					'search'    => 'LIKE',
    					'alias'     => 't',
    			),

                array(
                        'name'      => 'short_name',
                        'type'      => 'text',
                        'search'    => 'LIKE',
                        'alias'     => 't',
                ),

                array(
                        'name'      => 'status',
                        'type'      => 'dropDownList',
                        'search'    => '=',
                        'data'      => array(self::STATUS_SHUTDOWN=>'停用', self::STATUS_OPEN=>'启用'),
                        'value'     =>  Yii::app()->request->getParam('status'),
                        'alias'     => 't',
                ),

                array(
                        'name'      => 'is_lock',
                        'type'      => 'dropDownList',
                        'search'    => '=',
                        'data'      => array(self::STATUS_NOTLOCK=>'账号未锁', self::STATUS_ISLOCK=>'账号已锁'),
                        'value'     =>  Yii::app()->request->getParam('is_lock'),
                        'alias'     => 't',
                ),

                array(
                        'name'      => 'is_auto_upload',
                        'type'      => 'dropDownList',
                        'search'    => '=',
                        'data'      => array(self::AUTO_UPLOAD_OFF=>'否', self::AUTO_UPLOAD_ON=>'是'),
                        'value'     =>  Yii::app()->request->getParam('is_auto_upload'),
                        'alias'     => 't',
                ),
    	);
    	return $result;
    }
    
    public function search(){
    	$sort = new CSort();
    	$sort->attributes = array('defaultOrder'=>'t.id');
    	$dataProvider = parent::search(get_class($this), $sort,array(),$this->_setCDbCriteria());
    	$data = $this->addition($dataProvider->data);
    	$dataProvider->setData($data);
    	return $dataProvider;
    }
    
    protected function _setCDbCriteria(){
    	$criteria = new CDbCriteria;
    	$criteria->order = 't.id';
    	return $criteria;
    }
    
    public function addition($data){
    	foreach ($data as $k=>$v){
    		$dataInfo = $this->getDbConnection()->createCommand()
    		->select('t.id,t.email,t.status,t.is_lock,t.is_auto_upload,t.user_name,t.store_name,t.group_id')
    		->from($this->tableName().' AS t')
    		->where("t.id = '{$v['id']}'")
    		->queryRow();
    	}
    	return $data;
    }
    
    /**
     * @desc 获取账号名
     * @param unknown $accountId
     * @return Ambigous <string, unknown, mixed>
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
     * @desc 获取账号名称
     * @param unknown $accountIds
     * @return Ambigous <string, unknown, mixed>
     */
    public function getAccountNameByIds($accountIds) {
    	$accountIds = self::model()->getDbConnection()
		    	->createCommand()
		    	->select("id, short_name")
		    	->from(self::tableName())
		    	->where(array('IN', 'id', $accountIds))
		    	->queryAll();
    	if(!$accountIds){
    		return array();
    	}
    	$return = array();
    	foreach ($accountIds as $account){
    		$return[$account['id']] = $account['short_name'];
    	}
    	return $return;
    }
    
    /**
     * 获取ID和NAME的键值对
     * @return multitype:unknown
     */
    public static function getIdNamePairs() {
    	$pairs = array();
    	$res = EbayAccount::model()
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
    
    /**
     * @desc 根据账号简称获取账号信息
     */
    public static function getByShortName($shortName) {
    	return EbayAccount::model()->getDbConnection()->createCommand()
		    	->select('*')
		    	->from(self::tableName())
		    	->where(" short_name = '{$shortName}'")
		    	->queryRow();
    }
    /**
     * @desc 根据账号简称获取账号列表
     * @param unknown $shortNames
     * @return mixed
     */
    public static function getAccountsByShortNames($shortNames) {
    	return EbayAccount::model()->getDbConnection()->createCommand()
				    	->select('*')
				    	->from(self::tableName())
				    	->where(array('IN', 'short_name', $shortNames))
				    	->queryAll();
    }


    /**
     * @desc 获取可用并具有自动上传的账号列表
     * @author hanxy
     */
    public function getAutoUploadAccountList(){
        return EbayAccount::model()->getDbConnection()->createCommand()
                       ->select('id')
                       ->from(self::model()->tableName())
                       ->where('status = '.self::STATUS_OPEN.' AND is_auto_upload = '.self::AUTO_UPLOAD_ON)
                       ->queryAll();
    }


    /**
     * @desc 获取ebay帐号的是否自动上传状态
     * @return string
     */
    public static function getAccountAutoUploadStatus($uploadStatus = 1){
        $uploadStatusArr = array(
                self::AUTO_UPLOAD_OFF        => '否',
                self::AUTO_UPLOAD_ON         => '是'
        );

        return $uploadStatusArr[$uploadStatus];
        
    }


    /**
     * 获取ID和NAME的键值对
     * @return multitype:unknown
     */
    public static function getIdUserNameList() {
        $pairs = array();
        $res = EbayAccount::model()
        ->getDbConnection()
        ->createCommand()
        ->select("id, user_name")
        ->from(self::tableName())
        ->queryAll();
        if (!empty($res)) {
            foreach ($res as $row)
                $pairs[$row['id']] = $row['user_name'];
        }
        return $pairs;
    }


    /**
     * 所在部门
     */
    public static function getDepartment(){
        $departId = Department::model()->getDepartmentByPlatform(Platform::CODE_EBAY);
        $departList = Department::model()->findAll("id in ( " . MHelper::simplode($departId) . " )");
        $departData = array();
        foreach($departList as $value){
            $departData[$value['id']] = $value['department_name'];
        }
        return $departData;
    }

    /**
     * 更新数据
     */
    public function updateData($data, $conditions, $params=array()){
        return $this->getDbConnection()->createCommand()->update($this->tableName(), $data, $conditions, $params);
    }

    /**
     * 插入数据
     */
    public function insertData($data){
        return $this->getDbConnection()->createCommand()->insert($this->tableName(), $data);
    } 

    /**
     * getOneByCondition
     * @param  string $fields
     * @param  string $where 
     * @param  string $order 
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
     * @param  string $order 
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

}