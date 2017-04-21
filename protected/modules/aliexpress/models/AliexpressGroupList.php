<?php
/**
 * @desc Aliexpress产品分组拉取
 * @since 2015-09-09
 */
class AliexpressGroupList extends AliexpressModel{
    
    const EVENT_NAME = 'grouplist';
    
    /** @var object 拉单返回信息*/
    public $groupListResponse = null;
    
    /** @var int 分组ID*/
    public $_groupId = null;
    
    /** @var string 分组名称*/
    public $_groupName = null;
    
    /** @var int 父类ID*/
    public $_parentId = null;
    
    /** @var int 创建者ID*/
    public $_createUserId = null;
    
    /** @var string 异常信息*/
    public $exception = null;
    
    /** @var int 账号ID*/
    public $_accountId	= null;
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_aliexpress_group';
    }
    
    /**
     * 设置分组ID
     * @param int $groupId
     */
	public function setGroupId($groupId) {
		$this->_groupId	= $groupId;
	}
    
	public function getGroupId() {
		return $this->_groupId;
	}
	
	/**
	 * 设置分组名称
	 * @param string $groupName
	 */
	public function setGroupName($groupName) {
		$this->_groupName = $groupName;
	}
	
	public function getGroupName() {
		return $this->_groupName;
	}
	
	/**
	 * 设置子分组
	 * @param string $group
	 */
	public function setParentId($parentId) {
		$this->_parentId = $parentId;
	}
	
	public function getParentId() {
		return $this->_parentId;
	}
	
	/**
	 * @desc 设置账号ID
	 * @param int $accountId
	 */
	public function setAccountId($accountId){
		$this->_accountId = $accountId;
	}
	
	public function getAccountId() {
		return $this->_accountId;
	}
	
	/**
	 * @desc 设置异常信息
	 * @param string $message
	 */
	public function setExceptionMessage($message){
		$this->exception = $message;
	}
	
	/**
	 * @desc 获取异常信息
	 * @return string
	 */
	public function getExceptionMessage(){
		return $this->exception;
	}
	
	public static function getIndexNavTabId() {
		return Menu::model()->getIdByUrl('/aliexpress/aliexpressgrouplist/index');
	}
	
	/**
	 * @desc 保存账户产品分组信息
	 * 
	 */
	public function getGroupList(){
		$accountId	= $this->_accountId;
        $request	= new GetProductGroupListRequest();
		$response	= $request->setAccount($accountId)->setRequest()->sendRequest()->getResponse();
		if( isset($response->target) && count($response->target) > 0 ){
			$dbTransaction = $this->dbConnection->getCurrentTransaction();
			if( !$dbTransaction ){
				$dbTransaction = $this->dbConnection->beginTransaction();//开启事务
			}
			try {
				$this->dbConnection->createCommand()->delete(self::tableName(), 'account_id = :accountId',array(":accountId" => $accountId));
				foreach($response->target as $group) {//循环插入产品分组信息
					$this->getChildInfo($group);
				}
				$dbTransaction->commit();
			}catch (Exception $e){
				
				$dbTransaction->rollback();
				$this->setExceptionMessage(Yii::t('aliexpress', $e->getMessage()));
				return false;
			}
        }else{//抓取失败
        	if(isset($response->success) && $response->success==0){
        		$this->setExceptionMessage(Yii::t('aliexpress', 'No Product Group List!'));
        	}else{
        		$this->setExceptionMessage($request->getErrorMsg());
        	}
        	return false;
        }
        return true;
	}
	
	/**
	 * 获取产品分组子类信息
	 * @param object $group
	 * @param int $parentId
	 */
	public function getChildInfo($group, $parentId = 0) {
		$this->saveGroupList($group, $parentId);
		if (!empty($group->childGroup)){
			foreach ($group->childGroup as $childInfo) {
				$this->saveGroupList($childInfo, $group->groupId);
				if (!empty($childInfo->childGroup)) {
					$this->getChildInfo($childInfo->childGroup, $childInfo->groupId);
				}
			}
		}
		return true;
	}
	
	/**
	 * @desc 保存产品分组信息
	 * @param object $info
	 */
	public function saveGroupList($group, $parentId = 0){
		$param	= array(
				'group_id'			=> $group->groupId,
				'account_id'		=> $this->_accountId,
				'group_name'		=> $group->groupName,
				'parent_id'			=> $parentId,
				'create_user_id'	=> isset(Yii::app()->user->id) ? Yii::app()->user->id : 0,
				'modify_time'		=> date('Y-m-d H:i:s'),
				'create_time'		=> date('Y-m-d H:i:s')
		);
		$data=$this->findByPk($group->groupId);
		if (!empty($data)) {
			unset($param['create_time']);
			unset($param['create_user_id']);
			return $this->updateByPk($group->groupId, $param);
		}else{
			return $this->dbConnection->createCommand()->insert(self::tableName(), $param);
		}
	}
	
	/**
	 * 获取所有帐号
	 * 
	 */
	public static function getAccountList() {
		return	self::model()->getDbConnection()->createCommand()
				->select('id, short_name')
				->from('market_aliexpress.ueb_aliexpress_account')
				->queryAll();
	}
	
	/**
	 * @desc 分组树
	 * @param type $status
	 * @param type $isMenu
	 * @return array $data
	 */
	public static function getTreeList($accountId) {
		$data = array();
		self::getLists($data, 0, $accountId);
		return $data;
	}
	
	/**
	 * 从数据库中获取产品分组信息
	 * @param array $data
	 * @param int $pid
	 * @param int $accountID
	 */
	public function getLists(&$data, $pid, $accountID) {
		$productGroup = self::model()->getDbConnection()->createCommand()
					->select('*')
					->from(self::tableName())
					->where('account_id = :account_id AND parent_id = :parent_id', array(':account_id'=>$accountID, ':parent_id' => $pid))
					->queryAll();
		if (!empty($productGroup)) {	
			foreach ($productGroup as $row) {
				$data[$row['group_id']] = array(
					'group_id' => $row['group_id'],
					'group_name' => $row['group_name'],
					'account_id' => $row['account_id']
				);
				self::getLists($data[$row['group_id']]['childGroup'], $row['group_id'], $accountID);
			}
		}
	}
	
	public function getGroupTree($accountID, $pid = 0, $level = 0, $selected = null) {
		$groupTree = '';
		$indent = '&nbsp;&nbsp;&nbsp;';
		$productGroup = self::model()->getDbConnection()->createCommand()
		->select('*')
		->from(self::tableName())
		->where('account_id = :account_id AND parent_id = :parent_id', array(':account_id'=>$accountID, ':parent_id' => $pid))
		->queryAll();
		if (!empty($productGroup)) {
			foreach ($productGroup as $row) {
				$groupTree .='<option' . ($selected == $row['group_id'] ? ' selected="selected"' : '') . ' value="' . $row['group_id'] . '">' .  str_repeat($indent, $level) . $row['group_name'] . '</option>' . "\n";
				$_level = $level;
				$groupTree .= self::getGroupTree($accountID, $row['group_id'], ++$level, $selected);
				$level = $_level;
			}
		}
		return $groupTree;
	}


	/**
	 * 通过账号ID和分组ID从数据库中获取产品分组信息
	 * @param int $accountID
	 * @param int $groupId
	 */
	public function getGroupNameByAccountIdAndGroupId($accountID, $groupId) {
		$productGroup = self::model()->getDbConnection()->createCommand()
					->select('group_name')
					->from(self::tableName())
					->where('account_id = :account_id AND group_id = :group_id', array(':account_id'=>$accountID, ':group_id' => $groupId))
					->queryScalar();

		return $productGroup;
	}


	/**
	 * 通过账号ID从数据库中获取产品分组信息
	 * @param int $accountID
	 */
	public function getGroupListOneByAccountId($accountID) {
		$productGroup = self::model()->getDbConnection()->createCommand()
					->select('group_id')
					->from(self::tableName())
					->where('account_id = :account_id', array(':account_id'=>$accountID))
					->queryRow();

		return $productGroup;
	}
}