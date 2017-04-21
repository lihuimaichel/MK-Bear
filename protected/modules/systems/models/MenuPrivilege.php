<?php
/**
 * @desc 菜单Model
 * @author Gordon
 * @since 2015-06-20
 */
class MenuPrivilege extends SystemsModel{
	public $superIds = array(
							1, 1017
						);
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
	
	public function getDbKey(){
	    return 'db';
	}

	/**
	 * @desc 表名
	 * @see CActiveRecord::tableName()
	 */
	public function tableName() {
		return 'ueb_menu_privilege';
	}
    

    
    /**
     * @desc 属性标签
     * @see CModel::attributeLabels()
     */
    public function attributeLabels() {
        return array(
            
        );
    }
   
    /**
     * get the highest frequency menu list
     */
    public static function getMenuPrivilegeByUserId($userId) {
       	$menuPrivilege =  Yii::app()->db->createCommand()
							        ->select('privilege_ids')
							        ->from(self::tableName())
							        ->where("user_id='{$userId}'")
							        ->queryRow();
        return $menuPrivilege ? $menuPrivilege['privilege_ids'] : '';
    }
  
    public function checkMenuPrivilegeExistsByUserId($userId){
    	return Yii::app()->db->createCommand()
    	->select('id')
    	->from(self::tableName())
    	->where("user_id='{$userId}'")
    	->queryRow();
    	
    }
    
    /**
     * 过滤掉无权限菜单id
     * @param unknown $menuIds
     * @return multitype:
     */
    public function filterMenuAccessByMenuIds($menuIds){
    	//@todo 等待开启
    	//return $menuIds;
    	$userId = (int)Yii::app()->user->id;
    	//if(in_array($userId, $this->superIds)) return $menuIds;
    	if(UserSuperSetting::model()->checkSuperPrivilegeByUserId($userId)) return $menuIds;
    	$menuPrivileges = $this->getMenuPrivilegeByUserId($userId);
    	if(empty($menuPrivileges)) return array();
    	$menuPrivileges = explode(",", $menuPrivileges);
    	return array_intersect($menuIds, $menuPrivileges);
    }
    
    /**
     * @desc 检测是否有权限
     * @see UebModel::checkAccess()
     */
    public function checkMenuAccess($menuId){
    	//@todo 等待开启
    	//return true;
    	static $menuPrivilegesArr = array();
    	$userId = (int)Yii::app()->user->id;
    	//if(in_array($userId, $this->superIds)) return true;
    	if(UserSuperSetting::model()->checkSuperPrivilegeByUserId($userId)) return true;
    	if(empty($menuPrivilegesArr[$userId])){
    		$menuPrivilegesArr[$userId] = $this->getMenuPrivilegeByUserId($userId);
    	}
    	 
    	if(empty($menuPrivilegesArr[$userId])) false;
    	$menuPrivileges = explode(",", $menuPrivilegesArr[$userId]);
    	if(in_array($menuId, $menuPrivileges))
    		return true;
    	return false;
    }
    
    /**
     * @desc 更新操作菜单权限
     * @param unknown $uid
     * @param unknown $menuPrivilege
     * @return Ambigous <number, boolean>
     */
    public function updateMenuPrivilegeById($uid, $menuPrivilege = array()){
    	//更新菜单权限
    	$updateData = array(
    			'privilege_ids'	=>	implode(",", $menuPrivilege)
    	);
    	$nowtime = date("Y-m-d H:i:s");
    	if(!$this->checkMenuPrivilegeExistsByUserId($uid)){
    		$updateData['user_id'] = $uid;
    		$updateData['create_time'] = $nowtime;
    		$updateData['update_time'] = $nowtime;
    	 	$this->getDbConnection()->createCommand()->insert(self::tableName(), $updateData);
    	 	return $this->getDbConnection()->getLastInsertID();
    	}else{
    		$updateData['update_time'] = $nowtime;
    		return $this->getDbConnection()->createCommand()->update(self::tableName(), $updateData, "user_id=:user_id", array(":user_id"=>$uid));
    	}
    	
    	
    }
}