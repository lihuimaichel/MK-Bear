<?php
/**
* @desc Ebay帐号管理模型
* @author Michael
* @since 2015/07/20 20:28
* 
*/
class Ebayaccountmanage extends EbayModel{

	const   ACCOUNT_STATUS_OPEN   = 1;   //启用帐号
	const   ACCOUNT_STATUS_CLOSE  = 0;   //禁用帐号
	const   ACCOUNT_STATUS_NORMAL = 0;   //帐号正常
	const   ACCOUNT_STATUS_LOCK   = 1;   //帐号锁定
	
	public $publish_count;
	
	public $if_adjust_count;
	
	public static function model($className = __CLASS__){
		return parent::model($className);
	}
	
	public function tableName(){
		return 'ueb_ebay_account';
	}
	
	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		return array(
				array('store_name,user_name','required'),
				array('status','safe'),
				array('is_lock','safe')
		);
	}
	
	/**
	 * @desc 页面的跳转链接地址
	 */
	public static function getIndexNavTabId() {
		return Menu::model()->getIdByUrl('/warehouses/warehouseArea/list');
	}
	
	/**
	 * @since 2015/07/31
	 * @desc 获取ebay帐号的使用状态
	 * @return array
	 */
	public function getAccountStatus($accStatus=''){
		$accStatusArr = array
		(
				self::ACCOUNT_STATUS_OPEN 		=> Yii::t('system', 'Account Open'),
				self::ACCOUNT_STATUS_CLOSE 	    => Yii::t('system', 'Account Close'),
		);
		if($accStatus != ''){
			return $accStatusArr[$accStatus];
		}else{
			return $accStatusArr;
		}
	}
	
	/**
	 * @since 2015/07/31
	 * @desc 获取ebay帐号的冻结状态
	 * @return array
	 */
	public function getAccountLockStatus($accStatus = ''){
		$accStatusArr = array(
			self::ACCOUNT_STATUS_NORMAL  =>Yii::t('system', 'Account OK'),
			self::ACCOUNT_STATUS_LOCK    =>Yii::t('system', 'Account Locked'),	
		);
		if($accStatus != ''){
			return $accStatusArr[$accStatus];
		}else{
			return $accStatusArr;
		}
	}

	public function attributeLabels(){
		 return array(
				'id'             =>Yii::t('system', 'No.'),
 				'email'          =>Yii::t('system', 'Email'),
				'user_name'      =>Yii::t('system', 'user_name'),
				'store_name'     =>Yii::t('system', 'store_name'),
				'use_status'     =>Yii::t('system', 'use_status'),
				'frozen_status'  =>Yii::t('system', 'frozen_status'), 
				'group_id'       =>Yii::t('system', 'Group Id'),
				'group_name'     =>Yii::t('system', 'Group Name'),
		 		'publish_count'  =>Yii::t('system', 'Publish Count'),
		 		'if_adjust_count'=>Yii::t('system', 'IfAdjust Count'),
		);
	}
	
	public function filterOptions(){
		$result = array(
				array(
						'name'      => 'email',
						'type'      => 'text',
						'search'    => 'LIKE',
						'alias'     => 't',
				)
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
			$dataInfo = $this->dbConnection->createCommand()
			->select('t.id,t.email,t.status,t.is_lock,t.user_name,t.store_name,t.group_id')
			->from($this->tableName().' AS t')
			->where("t.id = '{$v['id']}'")
			->queryRow();
		}
		return $data;
	}
}
?>