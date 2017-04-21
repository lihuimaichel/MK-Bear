<?php
/**
 * @package Ueb.modules.users.models
 * 
 * @author Bob <zunfengke@gmail.com>
 */
class UserListShow extends UsersModel
{	
	/**
	 * Returns the static model of the specified AR class.
	 * @return CActiveRecord the static model class
	 */
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName() {
		return 'ueb_user_list_show';
	}
    
    /**
     * get show values by the key
     * 
     * @param string $key
     * @return array $result
     */
    public function getShowValuesByKey($key) {
        $data = array(
            'show_key'  => $key,
            'user_id'   => Yii::app()->user->id
        );
        $userListInfo = $this->findByAttributes($data); 
        $result = array();
        if ( ! empty($userListInfo['show_values']) ) {
            $result = explode(",", $userListInfo['show_values']);
        } 
        
        return $result;
    }
}