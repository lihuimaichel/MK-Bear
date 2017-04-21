<?php
/**
 * @desc EABY其他商家店铺基本信息
 * @author tony 
 * @since 2015-08-28
 */
class EbaySeller extends EbayModel{
	
	/** @var tinyint 账号状态开启*/
	const STATUS_OPEN = 1;
	
	/** @var tinyint 账号状态关闭*/
	const STATUS_SHUTDOWN = 0;
	public $all_item_nums = 0;
	
	
	/** @var 是否自动调数量*/
	//public $if_adjust_count;
	
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}
	
	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules(){}
	
	/**
	 * @desc 数据库表名
	 * @see CActiveRecord::tableName()
	 */
	public function tableName() {
		return 'ueb_ebay_seller';
	}
	
	/**
	 * @desc 获取可用账号列表
	 */
	public function getAbleAccountList(){
		return EbayAccount::model()->dbConnection->createCommand()
		->select('*')
		->from(self::model()->tableName())
		->where('status = '.self::STATUS_OPEN)
		->queryAll();
	}
	
	
	
	
	/**
	 * @desc 页面的跳转链接地址
	 */
	public static function getIndexNavTabId() {
		return Menu::model()->getIdByUrl('/ebay/ebayseller/list');
	}
	
	
	/**
	 * @since 2015/07/31
	 * @desc 获取ebay其他帐号的状态
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
	 * @desc 获取字段映射
	 * @return array
	 */
	public function attributeLabels(){
		return array(
				'id'             =>Yii::t('system', 'No.'),
				'user_name'      =>Yii::t('system', 'store_name'),
				'status'    	 =>Yii::t('system', 'Use Status'),
				'user_id'        =>Yii::t('system', 'User'),
				'create_time'    =>Yii::t('system', 'Create Time'),
				'update_time'    =>Yii::t('system', 'Modify Time'),
				'total_item_num' =>Yii::t('ebay',   'Last Total Item Num'),
				'all_item_nums'  =>Yii::t('ebay',   'All Item Nums'),
		);
	}
	
	/**
	 * @desc 工具栏搜索条件
	 * @return array
	 */
	public function filterOptions(){
		$result = array(
				array(
						'name'      => 'user_name',
						'type'      => 'text',
						'search'    => 'LIKE',
						'alias'     => 't',
				)
		);
		return $result;
	}
	
	/**
	 * @desc 执行搜索SQL
	 * @return array
	 */
	public function search(){
		$sort = new CSort();
		$sort->attributes = array('defaultOrder'=>'t.id');
		$dataProvider = parent::search(get_class($this), $sort,array(),$this->_setCDbCriteria());
		$data = $this->addition($dataProvider->data);
		$dataProvider->setData($data);
		return $dataProvider;
	}
	
	/**
	 * @desc 定义基本SQL条件
	 * @return array
	 */
	protected function _setCDbCriteria(){
		$criteria = new CDbCriteria;
		$criteria->order = 't.id';
		return $criteria;
	}
	
	/**
	 * @desc 若search function中搜索出的结果不能满足需求,则在此方法中添加和处理数据
	 * @return array
	 */
	public function addition($data){
		foreach( $data as $key => $value ){
			$data[$key]->all_item_nums = UebModel::model('EbayProductOther')->getTotalItemNum( $value->id );
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
	 * @desc 根据账号ID获取账号信息
	 * @param int $id
	 */
	public static function getAccountInfoById($id){
		$flag = false;
		if( !is_array($id) ){
			$flag = true;
			$id = array($id);
		}
		$sql = EbayAccount::model()->dbConnection->createCommand()->select('*')->from(self::model()->tableName())->where('id IN ('.implode(',', $id).')');
		if( $flag ){
			return $sql->queryRow();
		}else{
			return $sql->queryAll();
		}
	}
	
	/**
	 * @desc 根据attribute获取账号信息
	 * @param int $id
	 */
	public static function getAccountInfoByAttribute($attribute,$data){
		$flag = false;
		if( !is_array($data) ){
			$flag = true;
			$id = array($data);
		}
		$sql = EbayAccount::model()->dbConnection->createCommand()->select('*')->from(self::model()->tableName())->where(" $attribute IN ('{$data}')");
		if( $flag ){
			return $sql->queryRow();
		}else{
			return $sql->queryAll();
		}
	}
	
	/**
	 * @desc 开启账号
	 * @return boolean
	 */
	public function openAccount($userName){
		$openArr = array();
		$openArr = array(
				'status' 		 => self::STATUS_OPEN,
				'user_id'		 => Yii::app()->user->id,
				'update_time'	 =>date('Y-m-d H:i:s'),
		);
		$flag=$this->updateAll($openArr,"user_name in ( '{$userName}' )");
		return $flag;
	}
	
	/**
	 * @desc 关闭账号
	 * @return boolean
	 */
	public function shutDownAccount($userName){
		$closeArr = array();
		$closeArr = array(
				'status' 		 => self::STATUS_SHUTDOWN,
				'user_id' 		 => Yii::app()->user->id,
				'update_time'	 => date('Y-m-d H:i:s'),
		);
		$flag=$this->updateAll($closeArr,"user_name in ( '{$userName}' )");
		return $flag;
	}
	
	/**
	 * @desc 删除账号
	 * @return boolean
	 */
	public function deleteAccountByAttribute($attribute,$data){
		$flag = $this->getDbConnection()->createCommand()->delete($this->tableName(), " $attribute IN ('{$data}')");
				
		return $flag;
	}
	
	
	/**
	 * @desc 获取调整价格数据
	 */
	public function getNewUserInfo($userName){
		
		return $param = array(
				'user_name'			=> $userName,
				'status'			=> self::STATUS_OPEN,
				'user_id'			=> Yii::app()->user->id,
				'create_time'		=> date('Y-m-d H:i:s'),
		);
	}
	
	/**
	 * @desc 添加店铺
	 */
	public function saveNewUserName($param){
		if( $this->dbConnection->createCommand()->insert(self::tableName(), $param) ){
			return $this->dbConnection->getLastInsertID();
		}else{
			return false;
		}
	}
	
	/**
	 * @desc 根据条件查询卖家信息
	 * @param string $condition
	 * @param string $field
	 * @return unknown
	 */
	public function getSellerByCondition( $condition = '', $field = '*' ){
		empty($condition) && $condition = ' 1=1 ';
		$ret = $this->dbConnection->createCommand()
			->select($field)
			->from($this->tableName())
			->where($condition)
			->queryAll();
		return $ret;
	}
	
	
}
