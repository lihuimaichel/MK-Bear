<?php
/**
 * @desc 菜单Model
 * @author Gordon
 * @since 2015-06-20
 */
class UserSuperSetting extends SystemsModel{
	const STATUS_YES = 1;
	const STATUS_NO = 0;
	
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
		return 'ueb_user_super_setting';
	}
    

    
    public function checkSuperPrivilegeByUserId($userId){
    	$res = $this->getDbConnection()->createCommand()
    		->from($this->tableName())
    		->where("user_id=:user_id and status=:status", array(":user_id"=>$userId, ":status"=>self::STATUS_YES))
    		->queryRow();
    	if($res) return true;
    	return false;
    }
}