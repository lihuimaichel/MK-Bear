<?php
/**
 * @desc Ebay刊登产品运费模板model
 * @author lihy
 * @since 2016-03-28
 */
class EbayGroupRuleRelation extends EbayModel{
	
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_ebay_group_rule_relation';
    }

    
    public function getListByGroupId($groupId){
    	return $this->getDbConnection()->createCommand()->from($this->tableName())->where("group_id={$groupId}")->queryAll();
    }
    
    
    public function saveData($data){
    	$res = $this->getDbConnection()->createCommand()->insert($this->tableName(), $data);
    	if($res){
    		return $this->getDbConnection()->getLastInsertID();
    	}
    	return false;
    }
    
    public function delByGroupId($groupId){
    	return $this->getDbConnection()->createCommand()->delete($this->tableName(), "group_id=".$groupId);
    }

}