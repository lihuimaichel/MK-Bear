<?php
/**
 * @desc 账号listing报告基类
 * @author zhangF
 *
 */
class AccountListingReport extends UebModel {
	/** @var 平台ID **/
	protected static $_platform_ids = array();
	/** @var 平台CODE **/
	public $platform_code = Platform::CODE_AMAZON;
	/** @var 用户ID **/
	public $user_id = null;
	
	/** @var 可以查看的账号列表 **/
	protected static $_hasPrivilegesColumns = array();
	/** @var 列名和账号名之间的映射 **/
	protected static $_columnAccountMaps = array();
	
	/**
	 * @desc 构造函数
	 * @param string $platformCode
	 */
	public function __construct($platformCode = null) {
		$this->user_id = Yii::app()->user->id;
		if (!empty($platformCode))
			$this->platform_code = $platformCode;
		if (!isset(self::$_platform_ids[$this->platform_code]) || empty(self::$_platform_ids[$this->platform_code])) {
			$platformInfo = Platform::model()->getPlatformByCode($this->platform_code);
			if (!empty($platformInfo)) {
				self::$_platform_ids[$this->platform_code] = $platformInfo->id;
			} else {
				self::$_platform_ids[$this->platform_code] = null;
			}
		}
	}	
	
	/**
	 * @desc 设置属性标签
	 * @see CModel::attributeLabels()
	 */
	public function attributeLabels() {
		return array(
			'sku' => Yii::t('account_listing_report', 'SKU'),
			'iid' => Yii::t('system', 'Id'),
			'report_date' => Yii::t('account_listing_report', 'Report Date'),
			'platform_code' => Yii::t('account_listing_report', 'Platform Name'),
		);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see UebModel::search()
	 */
	public function search() {
		$sort = new CSort();
		$sort->attributes = array(
			'defaultOrder' => 'sku',
		);
		$dataProvider = parent::search(get_class($this), $sort, '', $this->_setCDbCriteria());
		$datas = $this->addition($dataProvider->data);
		$dataProvider->data = $datas;
		return $dataProvider;
	}
	
	/**
	 * @desc 设置查询条件
	 */
	public function _setCDbCriteria() {
		$criteria = new CDbCriteria();
		$criteria->select = "t.iid, t.sku, t.report_date";
		$selectColumns = $this->getHasPrivilegesColumns();
		foreach ($selectColumns as $column)
			$criteria->select .= ", " . $column;
	}
	
	/**
	 * @desc 处理列表数据
	 * @param array $datas
	 * @return array
	 */
	public function addition($datas) {
		foreach ($datas as $key => $data) {
			foreach (self::$_hasPrivilegesColumns[$this->platform_code] as $column)
				$data->{$column} = $data->{$column} == '000' ? '<strong style="color:red;font-size:16px;font-weight:bold">×</strong>' : '<strong style="color:green;font-size:16px;font-weight:bold">√</strong>'; 
		}
		return $datas;
	}
	
	/**
	 * @desc 设置搜索条件
	 * @return multitype:multitype:string multitype: Ambigous <unknown, string> Ambigous <multitype:unknown , string, mixed, unknown, multitype:unknown mixed >  multitype:string multitype: Ambigous <multitype:string , mixed>
	 */
	public function filterOptions() {
		$skuList = array();
		$skuPrivilegesModel = new Skuprivileges();
		$skuPrivilegesModel->platform_id = self::$_platform_ids[$this->platform_code];
		$skuPrivilegesModel->user_id = $this->user_id;
		$sku = isset($_REQUEST['sku']) ? trim($_REQUEST['sku']) : '';
		$skuList = !empty($sku) ? array($sku) : $skuPrivilegesModel->getPrivilegesSkuList();
		$skuStr = implode("','", $skuList);
		$platformCode = isset($_REQUEST['platform_code']) && empty($_REQUEST['platform_code']) ? $_REQUEST['platform_code'] : $this->platform_code; 
		return array(
			array(
				'name' => 'sku',
				'type' => 'text',
				'search' => 'IN',
				'data' => $skuStr,
				'value' => '',
				'htmlOptions' => array(),
				'alias' => 't',
			),
			array(
				'name' => 'platform_code',
				'type' => 'dropDownList',
				'rel' => 'selectedTodo',
				'value' => $platformCode,
				'search' => '=',
				'data' => CHtml::listData(UebModel::model('platform')->findAll(), 'platform_code', 'platform_name'),
				'htmlOptions' => array(),
				'alias' => 't',
			),
		);
	}
	
	/**
	 * @desc 获取listing report 表列和账号名的映射
	 * @return multitype:Ambigous <multitype:, multitype:unknown >
	 */
	public function getColumnAccountMaps() {
		if (!isset(self::$_columnAccountMaps[$this->platform_code])) {
			$maps = array();
			$accountModel = AccountFactory::factory($this->platform_code);
			$accountList = $accountModel->getIdNamePairs();
			if (!empty($accountList)) {
				foreach ($accountList as $id => $account) {
					$column = 'account' . str_pad($id, 3, '0', STR_PAD_LEFT);
					$maps[$column] = $account;
				}
			}
			self::$_columnAccountMaps[$this->platform_code] = $maps;
		}
		return self::$_columnAccountMaps[$this->platform_code];
	}
	
	/**
	 * @desc 设置用户ID
	 * @param integer $userId
	 */
	public function setUserId($userId) {
		$this->user_id = $userId;
	}
	
	/**
	 * @desc 获取当前用户有权限查看的列
	 * @return multitype:
	 */
	public function getHasPrivilegesColumns() {
		if (!isset(self::$_hasPrivilegesColumns[$this->platform_code])) {
			$columns = array();
			$maps = $this->getColumnAccountMaps();
			//获取当前用户在当前平台上拥有权限的账号
			$accountIds = Skuprivileges::model()->getAccountIds($this->user_id, self::$_platform_ids[$this->platform_code]);
			foreach ($maps as $key => $account) {
				$accountId = str_replace('account', '', $key);
				$accountId = (int)$accountId;
				if (in_array($accountId, $accountIds))
					$columns[] = $key;
			}
			self::$_hasPrivilegesColumns[$this->platform_code] = $columns;
		}	
		return self::$_hasPrivilegesColumns[$this->platform_code];
	}
}