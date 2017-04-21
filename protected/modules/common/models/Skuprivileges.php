<?php
/**
 * @desc SKU权限管理模型
 * @author zhangF
 *
 */
class Skuprivileges extends CommonModel {
	
	/** @var integer Platform_sku_id **/
	public $id = null;
	
	/** @var integer 用户ID **/
	public $user_id = null;
	
	/** @var string 用户名称 **/
	public $username = null;
	
	/** @var integer 平台ID **/
	public $platform_id = null;
	
	/** @var string 产品名称 **/
	public $title = null;
	
	/** @var string 平台Code **/
	public $platform_code = null;
	
	/** @var string 平台名称 **/
	public $platform_name = null;
	
	/** @var integer 账号ID **/
	public $account_id = null;
	
	/** @var string 账号名称 **/
	public $account_name = null;
	
	/** @var string sku **/
	public $sku = null;
	
	public $skuList = array();
	
	/**
	 * @desc 设置表名
	 * @see CActiveRecord::tableName()
	 */
	public function tableName() {
		return 'ueb_platform_sku';
	}
	
	/**
	 * @desc 获取模型
	 * @param system $className
	 * @return Ambigous <CActiveRecord, unknown, multitype:>
	 */
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}
	
	public function rules() {
		return array(
			array('username, platform_id, platform_code, account_id', 'required'),
		);
	}
	
	/**
	 * 属性标签名
	 * @see CModel::attributeLabels()
	 */
	public function attributeLabels() {
		return array(
			'username' => Yii::t('sku_privileges', 'Username'),
			'user_id' => Yii::t('sku_privileges', 'Username'),
			'platform_id' => Yii::t('system', 'Platform'),
			'sku' => Yii::t('sku_privileges', 'Sku'),
			'title' => Yii::t('sku_privileges', 'Title'),
			'account_id' => Yii::t('sku_privileges', 'Account'),
		);
	}
	
	/**
	 * 列表
	 * @see UebModel::search()
	 */
	public function search($model = null) {
		$sort = new CSort();
		$sort->attributes = array(
			'defaultOrder' => 't1.user_id',
		);
		
		$dataProvider = parent::search($this->model(), $sort, '', $this->_setCDbCriteria());
		$datas = $dataProvider->data;
		$datas = $this->addition($datas);
		return $dataProvider;
	}
	
	/**
	 * @desc 处理列表数据
	 */
	public function addition($datas) {
		$this->username = isset($_REQUEST['user_name']) ? trim($_REQUEST['user_name']) : null;
		$platformIdCodePairs = Platform::model()->queryPairs(array('id', 'platform_code'));
		foreach ($datas as $key => $data) {
			$userName = '';
			$platformCode = isset($platformIdCodePairs[$data->platform_id]) ? $platformIdCodePairs[$data->platform_id] : '';
			$userInfo = User::model()->getUserNameById($data->user_id);
			if (!empty($userInfo))
				$userName = $userInfo['user_name'];
			$data->username = $userName;
			$data->platform_name = Platform::model()->getNameById($data->platform_id);
			if ($platformCode != '')
				$data->account_name = AccountFactory::factory($platformCode)->getAccountNameById($data->account_id);
			else
				$data->account_name = '';
			$data->title = Productdesc::model()->getProductCnTitleBySkuAndLanguageCode($data->sku);
			$userInfo = User::model()->getUserNameById($data->user_id);
			if (!empty($userInfo))
				$data->username = $userInfo['user_name'];
			else
				$data->username = '';
		}
	}
	
	/**
	 * @desc 设置列表查询条件
	 * @return CDbCriteria
	 */
	public function _setCDbCriteria() {
		$criterial = new CDbCriteria();
		$criterial->select = "t.sku, t1.user_id, t.platform_id, t.account_id";
		$criterial->join = "left join ueb_user_sku_privileges as t1 on (t1.platform_sku_id = t.id)";
		return $criterial;
	}
	
	/**
	 * @desc 查询产品列表
	 * @return unknown
	 */
	public function searchProduct() {
		$dataProvider = Product::model()->searchProduct();
		$datas = $dataProvider->data;
		foreach ($datas as $key => $data) {
			$this->sku = $data->sku;
			//检查该SKU是否已经分配给当前平台的当前用户
			if ($this->hasPrivileges()) {
				$data->has_privileges = true;
				$this->skuList[] = $data->sku;
			} else {
				$data->has_privileges = false;
			}
		}
		$dataProvider->data = $datas;
		return $dataProvider;
	}
	
	/**
	 * @desc 检查用户是否对某个平台某个账号上的SKU有权限
	 * @return boolean
	 */
	public function hasPrivileges() {
		$res = self::getDbConnection()->createCommand()->from(self::tableName() . " as t")
			->join("ueb_user_sku_privileges as t1", "t1.platform_sku_id = t.id")
			->where("t.platform_id = :platform_id and t.sku = :sku and t1.user_id = :user_id and account_id = :account_id", array(
					':platform_id' => $this->platform_id, 
					':sku' => $this->sku, 
					':user_id' => $this->user_id,
					':account_id' => $this->account_id
			))
			->queryRow();
		return empty($res) ? false : true;
	}
	
	/**
	 * @desc 获取某个用户在某个平台的所有有权限的SKU列表
	 */
	public function getPrivilegesSkuList() {
		return $this->getDbConnection()->createCommand()->from("ueb_user_sku_privileges as t")
			->select("t1.sku")
			->leftJoin(self::tableName() . " as t1", "t.platform_sku_id = t1.id")
			->where("t.user_id = :user_id and t1.platform_id = :platform_id", array(':user_id' => $this->user_id, ':platform_id' => $this->platform_id))
			->queryColumn();
	}
	
	/**
	 * 取消用户对SKU的权限
	 * @return boolean
	 */
	public function revokePrivileges() {
		if (empty($this->platform_id) || empty($this->account_id) || empty($this->user_id) || empty($this->sku))
			return false;
		//检查sku除当前用户有权限外，还有没有其他的用户有权限，有则值删除当前用户和该SKU对应关系，否则删除该权限记录
		$platformSkuModel = self::getByUniqueKey($this->platform_id, $this->account_id, $this->sku);
		if (empty($platformSkuModel))
			return false;
		$id = $platformSkuModel['id'];
		$privilegesInfos = $this->getSkuPrivliges($id);
		foreach ($privilegesInfos as $key => $privilegesInfo) {
			if ($privilegesInfo['user_id'] == $this->user_id)
				unset($privilegesInfos[$key]);
		}
		//删除该记录
		if (sizeof($privilegesInfos) < 1)
			$platformSkuModel->delete();
		//删除privlieges表里面的记录
		return $this->getDbConnection()->createCommand()->delete("ueb_user_sku_privileges", "platform_sku_id = :id", array(':id' => $id));	
	}
	
	/**
	 * 授予某个用户对SKU的权限
	 * @return boolean|Ambigous <number, boolean>
	 */
	public function grantPrivileges() {
		if (empty($this->platform_id) || empty($this->account_id) || empty($this->user_id) || empty($this->sku))
			return false;
		//检查SKU记录是否存在
		$platformSkuModel = self::getByUniqueKey($this->platform_id, $this->account_id, $this->sku);
		//var_dump($platformSkuModel);
		$id = '';
		if (empty($platformSkuModel)) {
			$platformSkuModel = new Skuprivileges();
			$platformSkuModel->setAttribute('platform_id', $this->platform_id);
			$platformSkuModel->setAttribute('account_id', $this->account_id);
			$platformSkuModel->setAttribute('sku', $this->sku);
			$userId = Yii::app()->user->id;
			$platformSkuModel->setAttribute('create_user_id', $userId);
			$platformSkuModel->isNewRecord = true;
			$platformSkuModel->insert();
			$id = $platformSkuModel->getDbConnection()->getLastInsertID();
		} else 
			$id = $platformSkuModel['id'];
		if (empty($id))
			return false;
		return $this->getDbConnection()->createCommand()->insert("ueb_user_sku_privileges", array('user_id' => $this->user_id, 'platform_sku_id' => $id));				
	}
	
	/**
	 * @desc 获取SKU的全部权限记录
	 * @param unknown $id
	 * @return mixed
	 */
	public function getSkuPrivliges($id) {
		return $this->getDbConnection()->createCommand()->from("ueb_user_sku_privileges")
			->where("platform_sku_id = :id", array(':id' => $id))
			->queryAll();
	}
	
	/**
	 * @desc 根据唯一索引获取数据
	 * @param unknown $platformId
	 * @param unknown $accountId
	 * @param unknown $sku
	 * @return Ambigous <NULL, unknown, multitype:unknown Ambigous <unknown, NULL> , mixed, multitype:, multitype:unknown >
	 */
	public static function getByUniqueKey($platformId, $accountId, $sku) {
		$model = new Skuprivileges();
		return $model->find("platform_id = :platform_id and account_id = :account_id and sku = :sku", array(
			':platform_id' => $platformId,
			':account_id' => $accountId,
			':sku' => $sku
		));
	}
	
	/**
	 * @desc 搜索条件设置
	 * @return multitype:multitype:string multitype:
	 */
	public function filterOptions() {
		$userId = '';
		if (isset($_REQUEST['user_id'])) {
			$userId = User::model()->getIdByName(trim($_REQUEST['user_id']));
			$_REQUEST['search']['user_id'] = $userId;
		}
		if (isset($_REQUEST['sku'])) {
			$_REQUEST['search']['sku'] = trim($_REQUEST['sku']);
		}
		return array(
			array(
				'name' => 'user_id',
				'type' => 'text',
				'search' => '=',
				'htmlOptions' => array(),
				'alias' => 't1',	
			),
			array(
				'name' => 'sku',
				'type' => 'text',
				'search' => 'LIKE',
				'htmlOptions' => array(),
				'alias' => 't',
			),				
		);
	}
	/**
	 * 获得有权限的账号ID
	 * @param integer $userId
	 * @param integer $platformId
	 * @return multitype:NULL
	 */
	public function getAccountIds($userId, $platformId) {
		$ids = array();
		$criteria = new CDbCriteria();
		$criteria->select = "account_id";
		$criteria->join = "left join ueb_user_sku_privileges as t1 on (t1.platform_sku_id = t.id)";
		$criteria->compare("t1.user_id", (int)$userId);
		$criteria->compare("t.platform_id", (int)$platformId);
		$criteria->group = "t.account_id";
		$res = $this->findAll($criteria);
		if ($res) {
			foreach ($res as $row)
				$ids[] = $row->account_id;
		}
		return $ids;
	}
}
