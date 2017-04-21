<?php
/**
 * File Action Log 
 * 
 * @author Nick 2013-11-13
 * 
 */
class FileActionLog extends LogsModel {

	/**
	 *  action type status
	 */
	const ACTION_TYPE_UPLOAD = 1;
	
	const ACTION_TYPE_DOWNLOAD = 2;
	
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
        return 'ueb_file_action_log';
    }
    
    /**
     * Declares attribute labels.
     * @return array
     */
    public function attributeLabels() {
        return array(
            'id'                    => Yii::t('system', 'NO.'),
        	'file_type'             => Yii::t('logs', 'File type'),
        	'file_name'             => Yii::t('logs', 'File name'),
        	'file_path'             => Yii::t('logs', 'File path'),
        	'action_type'           => Yii::t('logs', 'Action type'),
        	'create_user_id'        => Yii::t('system', 'Create User'),
        	'create_time'           => Yii::t('system', 'Create Time'),
        );
    }
    
    /**
     * filter options
     * 
     * @return type
     */
    public function filterOptions() {
        return array(
        	array(
        		'name'          => 'action_type',
        		'type'          => 'dropDownList',
        		'value'         => self::ACTION_TYPE_UPLOAD,
        		'search'        => '=',
        		'data'          => $this->getActionType(),
        		'htmlOptions'   => array(),
        	),
            array(
        		'name'          => 'file_type',
        		'type'          => 'dropDownList',
        		'search'        => '=',
        		'data'          => UebModel::model("DownloadFile")->getFileType(),
        		'htmlOptions'   => array(),
        	),    
        	array(
        		'name'          => 'file_name',
        		'type'          => 'text',
        		'search'        => '=',
        		'htmlOptions'   => array(),
        	),
        	array(
        		'name'          => 'create_time',
        		'type'          => 'text',
        		'search'        => 'RANGE',
        		'htmlOptions'   => array(
        					'class'     	=> 'date',
        					'dateFmt'   	=> 'yyyy-MM-dd HH:mm:ss',
        		),
        	),
        );
    }
    
    /**
     * order field options
     * @return $array
     */
    public function orderFieldOptions() {
    	return array(
    		'file_type', 'file_name', 'file_path', 'action_type','create_user_id', 'create_time'
    	);
    }
    
    /**
     * get search info
     */
    public function search()  
    {                
        $sort = new CSort();  
        $sort->attributes = array(  
           'defaultOrder'  => 'file_type',
            'file_type', 'file_name', 'file_path', 'action_type','create_user_id', 'create_time'
        );  
        
        return parent::search(get_class($this), $sort);
    }
    
    /**
     * get Action Type
     * @author Nick 2013-11-13
     */
    public function getActionType($type=null){
    	$return =  array(
    			self::ACTION_TYPE_UPLOAD    => Yii::t('logs', 'Upload'),
    			self::ACTION_TYPE_DOWNLOAD  => Yii::t('logs', 'Download'),
    	);
    	if ( $type !== null ) {
    		return $return[$type];
    	}
    	return $return;
    }
    
    
    
}
