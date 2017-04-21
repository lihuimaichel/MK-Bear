<?php
/**
 * @package Ueb.modules.logistics.models
 * 
 * @author Gordon
 */
class LogisticsPricingSolutionGroup extends LogisticsModel { 
    
    /**
     * Returns the static model of the specified AR class.
     * @return CActiveRecord the static model class
     */
    public static function model($className = __CLASS__) {     
        return parent::model($className);
    }
    
    public function getModelName(){
    	return str_replace('Controller', "", get_class($this));
    }

    /**
     * @return string the associated database table name
     */
    public function tableName() {
        return 'ueb_logistics_pricing_solution_group';
    }
    
    /**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{	      
		return array();
	}
	
	public function getAllGroups(){
		$groups = array();
		$list = $this->getDbConnection()->createCommand()
					->select('*')
					->from(self::tableName())
					->where("use_status=:use_status",array(":use_status"=>1))
					->queryAll();
		foreach($list as $item){
			$groups[$item['id']] = $item['group_name'];
		}
		return $groups;
	}
    
}