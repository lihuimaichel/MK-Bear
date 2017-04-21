<?php 
/**
 * @desc 账号绑定关系
 * @author Gordon
 */
class PaypalGroupRelation extends SystemsModel{
    /**
     * Returns the static model of the specified AR class.
     * @return CActiveRecord the static model class
     */
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    /**
     * @return string the associated database table name
     */
    public function tableName() {
    	return 'ueb_paypal_group_relation';
    }
    
    /**
     * @desc 切换数据库
     * @see SystemsModel::getDbKey()
     */
    public function getDbKey(){
        return 'db';
    }
    
    
	/**
	 * 
	 * get relationInfo by groupId
	 * @param String $groupId
	 * @param String $fields
	 */
    public function getByGroupId($groupId,$fields='*') {           
        return Yii::app()->db->createCommand() 
		->select($fields)
		->from(self::tableName())
        ->where(" group_id = '{$groupId}'")
        ->queryAll();
     }
    
}
?>