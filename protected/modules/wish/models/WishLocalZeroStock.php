<?php
/**
 * @desc wish本地仓置0数据检测
 * @author lihy
 *
 */
class WishLocalZeroStock extends WishModel {

	public static $accountPairs = array();
	public $account_name;
	public $detail;

	public function tableName() {
		return 'ueb_wish_local_zero_stock';
	}

	public static function model($className = __CLASS__) {
		return parent::model($className);		
	}

	// =========== start: search ==================

	public function attributeLabels() {
		return array(
				'id'                            => Yii::t('system', 'No.'),
				'parent_sys_sku'				=>	'SKU',
				'parent_sku'					=>	'在线父sku',
				'product_id'					=>	'产品id',
				'sku'							=>	'系统子SKU',
				'online_sku'					=>	'在线子SKU',
				'variation_product_id'			=>	'variation_product_id',
				'account_id'					=>	'账号',
				'account_name'					=>	'账号名称',
				'create_time'					=>	'创建时间',
				'status'						=>	'状态',
				'update_user_id'				=>	'修改人',
				'remark'						=>	'备注',
		);
	}

	/**
	 * get search info
	 */
	public function search() {
		$sort = new CSort();
		$sort->attributes = array(
				'defaultOrder'  => 'id',
		);
		$dataProvider = parent::search(get_class($this), $sort,'',$this->_setdbCriteria());
		$data = $this->addtions($dataProvider->data);
		$dataProvider->setData($data);
		return $dataProvider;
	}

	private function _setdbCriteria(){
		$cdbcriteria = new CDbCriteria();
		return $cdbcriteria;
	}

	public function addtions($datas){
		if(empty($datas)) return $datas;
		$statusArr = array('0' => '未断货', '1' => '已断货', '2' => '来货取消');
		$sellerUserList = User::model()->getPairs();
		foreach ($datas as &$data){
			$data['account_name'] = isset(self::$accountPairs[$data['account_id']]) ? self::$accountPairs[$data['account_id']]:'';
			$data['status'] = $statusArr[$data['status']];
			$data['update_user_id'] =  isset($sellerUserList[$data['update_user_id']]) ? $sellerUserList[$data['update_user_id']] : '-';
		}
		return $datas;
	}

	/**
	 * filter search options
	 * @return type
	 */
	public function filterOptions() {
		$status = Yii::app()->request->getParam("status");
		$result = array(
			array(
				'name'=>'product_id',
				'type'=>'text',
				'search'=>'=',
				'htmlOption'=>array(
					'size'=>'22'
				)
			),
			array(
				'name'=>'parent_sys_sku',
				'type'=>'text',
				'search'=>'=',
				'htmlOption' => array(
					'size' => '22',
				)
			),
			array(
				'name'=>'parent_sku',
				'type'=>'text',
				'search'=>'=',
				'htmlOption' => array(
					'size' => '22',
				)
			),

			array(
				'name'=>'sku',
				'type'=>'text',
				'search'=>'=',
				'htmlOption' => array(
					'size' => '22',
				),
			),
			array(
				'name'=>'account_id',
				'type'=>'dropDownList',
				'search'=>'=',
				'data'=>$this->getAccountList()
			),
			array(
				'name' 			=> 'create_time',
				'type' 			=> 'text',
				'search' 		=> 'RANGE',
				'alias'			=>	't',
				'htmlOptions'	=> array(
					'size' => 4,
					'class'=>'date',
					'style'=>'width:80px;'
				),
			),
			array(
				'name'		=>	'status',
				'type'		=>	'dropDownList',
				'data'		=>	array('0' => '未断货', '1' => '已断货', '2' => '来货取消'),
				'value'		=> 	$status,
				'search'	=>	'=',

			),
			array(
				'name'      =>  'update_user_id',
				'type'      =>  'dropDownList',
				'data'      =>  User::model()->getUserNameByDeptID(array(15, 37)),
				'search'    =>  '=',

			),
		);
		return $result;
	}
	
	/**
	 * @desc  获取公司账号
	 */
	public function getAccountList(){
		if(self::$accountPairs == null)
			self::$accountPairs = self::model('WishAccount')->getIdNamePairs();
		return self::$accountPairs;
	}


	public function getWishProductVariantList($listingID){
		$conditions = "listing_id={$listingID}";
		if(isset($_REQUEST['online_sku']) && $_REQUEST['online_sku']){
			$conditions .= " AND online_sku='{$_REQUEST['online_sku']}'";
		}
		if(isset($_REQUEST['sub_sku']) && $_REQUEST['sub_sku']){
			$conditions .= " AND sku='{$_REQUEST['sub_sku']}'";
		}
		return WishVariants::model()->findAll($conditions);
	}

	// =========== end: search ==================

	/**
	 * @desc 获取最新一条
	 * @param unknown $condition
	 * @param string $params
	 * @return mixed
	 */
	public function getLastOneByCondition($condition, $params = null){
		return $this->getDbConnection()
			->createCommand()
			->from($this->tableName())
			->select('*')
			->where($condition, $params)
			->order("id desc")
			->queryRow();
	}

	/**
	 * @desc 保存信息
	 * @param unknown $params
	 * @return boolean|Ambigous <number, boolean>
	 */
	public function saveData($params){
		if(empty($params)) return false;
		return $this->getDbConnection()->createCommand()
			->insert($this->tableName(), $params);
	}

	public function updateByIds($ids){
		if(empty($ids)) return false;
		if(!is_array($ids)){
			$ids = array($ids);
		}
		$userID = intval(Yii::app()->user->id);
		return $this->getDbConnection()
			->createCommand()
			->update($this->tableName(), array('status' => 1,'update_user_id' => $userID), "id in(". MHelper::simplode($ids) .")");
	}

}