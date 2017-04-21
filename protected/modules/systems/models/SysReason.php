<?php

class SysReason extends SystemsModel {
          
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
	public function tableName()
	{
		return 'ueb_sys_reason';
	}

    /**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{	      
		return array(
			
		);
	}	

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(			
			
         );
	}
	/**
	 * get search info
	 */
	public function search() {
		$sort = new CSort();
		$sort->attributes = array(
				'defaultOrder'  => 'id',
		);
		return parent::search(get_class($this), $sort);
	}
	/**
	 * filter search options
	 * @return type
	 */
	public function filterOptions() {
		return array();
	}
	/**
	 * get param by type
	 */
	public function getParamByType($type=0){
		$list =  Yii::app()->db->createCommand()
			->select('id,type,param')
			->from(self::tableName())
			//->where('type=:type',array(':type'=>$type))
			->order('id asc')
			->queryAll();
		if($list){
			foreach($list as $key=>$val){
				$data[$val['id']] = $val['param'];
			}
		}
		if($type>0) return $data[$type];
		return $data;
	}
	
	
	/**
     * get index nav tab id 
     * 
     * @return type
     */
    public static function getIndexNavTabId() {
    	return Menu::model()->getIdByUrl('/systems/sysreason/list');
    }  
}