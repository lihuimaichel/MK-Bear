<?php
/**
 * @desc Aliexpress产品分组拉取
 * @since 2015-09-09
 */
class AliexpressCreateGroup extends AliexpressModel{
    
    const EVENT_NAME = 'creategroup';
    
    /** @var object 拉单返回信息*/
    public $createGroupResponse = null;
    
    /** @var int 分组ID*/
    public $_groupId = null;
    
    /** @var int 父类ID*/
    public $_parentId = null;
    
    /** @var string 分组名称*/
    public $_groupName = null;
    
    /** @var int 账号ID*/
    public $_accountId	= null;
    
    /** @var string 异常信息*/
    public $exception = null;
    
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
     * @return array validation rules for model attributes.
     */
    public function rules() {
    	return array(
    			array('group_name', 'required'),
    			array('group_name', 'length', 'max' => 50),
    	);
    }
    
    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels() {
    	return array(
    			'group_name'					=> Yii::t('aliexpress', 'Group Name'),
    			'account_id'         		 	=> Yii::t('aliexpress', 'Account Id'),
    			'parent_id'         		 	=> Yii::t('aliexpress', 'Parent Id'),
    			'group_id'						=> Yii::t('aliexpress', 'Group Id'),
    	);
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
	 * 设置父类ID
	 * @param int $parentId
	 */
	public function setParentId($parentId) {
		$this->_parentId = $parentId;
	}
	
	public function getParentId() {
		return $this->_parentId;
	}
	
	/**
	 * @desc 设置账号ID
	 * @param int $accountID
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
	
	/**
	 * filter group name, blank space replace with _
	 * @return String $name
	 */
	public static function filterName($groupName) {
		$groupName = str_replace("  ", " ", $groupName);
		return $groupName;
	}
	
	/**
	 * @desc 根据帐号创建产品分组
	 * 
	 */
	public function createGroup(){
		
        $request	= new CreateProductGroupRequest();
        $request->setParentId($this->_parentId);
        $request->setName($this->_groupName);
		$response	= $request->setAccount($this->_accountId)->setRequest()->sendRequest()->getResponse();
		
		//更新到数据库
		if(isset($response->success) && $response->success > 0 && isset($response->target)){
			$param	= array(
						'group_id'			=> $response->target,
						'account_id'		=> $this->_accountId,
						'group_name'		=> $this->_groupName,
						'parent_id'			=> $this->_parentId,
						'create_user_id'	=> Yii::app()->user->id,
						'modify_time'		=> date('Y-m-d H:i:s'),
						'create_time'		=> date('Y-m-d H:i:s')
					);
			return $this->dbConnection->createCommand()->insert(self::tableName(), $param);
        }else{//创建失败
        	if(isset($response->success) && $response->success==0){
        		$this->setExceptionMessage(Yii::t('aliexpress', 'No Product Group List!'));
        	}else{
        		$this->setExceptionMessage($request->getErrorMsg());
        	}
        	return false;
        }
        return true;
	}
	
}