<?php

class MsgType extends SystemsModel
{
    const ENABLED_STATUS = 1;
    
    const DISABLED_STATUS = 0;
    
    const PERSONAL_MSG_CODE = 'p';
            
	/**
	 * Returns the static model of the specified AR class.
	 * @return CActiveRecord the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'ueb_msg_type';
	}
    

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{	      
		return array(
			array('name, code, status, send_roles', 'required'),
		);
	}	

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(			
			'name'              => Yii::t('system', 'Message Type'),
			'code'              => Yii::t('system', 'Message Code'),	
            'send_types'        => Yii::t('system', 'Send Type'),
            'status'            => Yii::t('system', 'Status'),   
            'send_roles'        => Yii::t('system', 'Send Roles')
		);
	}	
	
	/**
	 * get search info
	 */
	public function search() {
		$sort = new CSort();
		$sort->attributes = array(
				'defaultOrder'  => 'status',
				'id',
		);
		$with = array( 'msg_type' );
		return parent::search(get_class($this), $sort);
	}
	
	/**
	 * filter search options
	 * @return type
	 */
	public function filterOptions() {
		return array(
				array(
						'name'          => 'name',
						'type'          => 'text',
						'search'        => '=',
						'htmlOptions'   => array(),
				)
		);
	}
	
	/**
	 * order field options
	 * @return $array
	 */
	public function orderFieldOptions() {
		return array(
				'status'
		);
	}

    /**
     * get page list
     * 
     * @return array
     */
    public function getPageList() {
        $this->_initCriteria();
        
        if (! empty($_REQUEST['code']) ) {
            $code = trim($_REQUEST['code']);
            $this->criteria->addCondition("code = '{$code}'");  
        }
        $this->_initPagination( $this->criteria);
        $models = $this->findAll($this->criteria);
        
        return array($models, $this->pages);
    }   
    
    /**
     * get index nav tab id 
     * 
     * @return type
     */
    public static function getIndexNavTabId() {
        return Menu::model()->getIdByUrl('/systems/msgconfig/list');
    } 
    
    public static function getByCode($code) {
         return Yii::app()->db->createCommand()
            ->select('*')
            ->from(self::tableName())
            ->where( 'code=:code', array(':code' => $code))                                      
            ->queryRow(); 
    }
              
}