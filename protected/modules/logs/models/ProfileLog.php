<?php
/**
 * record profile timing log 
 * 
 * @package Ueb.modules.logs.models
 * @author Bob <zunfengke@gmail.com>
 * 
 */
class ProfileLog extends LogsModel {

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
        return 'ueb_profile_log';
    }
    
    /**
     * Declares attribute labels.
     * @return array
     */
    public function attributeLabels() {
        return array(
            'id'                    => Yii::t('system', 'NO.'),
            'tag'                   => Yii::t('logs', 'Tag'),
            'keywords'              => Yii::t('system', 'Keywords'),          
            'message'               => Yii::t('logs', 'Execute Time(sec)'),
            'request_url'           => Yii::t('logs', 'Request Url'), 
            'log_time'              => Yii::t('logs', 'Log Time'),
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
                'name'          => 'keywords',               
                'type'          => 'text',
                'search'        => '=',
                'htmlOptions'   => array(),
            ),    
            array(               
                'name'          => 'message',               
                'type'          => 'text',
                'search'        => 'RANGE',
                'htmlOptions'   => array(
                    'size'  => 6,
                ),
            ), 
        );
    }
    
    /**
     * get search info
     */
    public function search()  
    {                
        $sort = new CSort();  
        $sort->attributes = array(  
            'defaultOrder'  => 'log_time',
            'tag',  
            'keywords',
            'message',
            'log_time'
        );  
        
        return parent::search(get_class($this), $sort);
    }
}