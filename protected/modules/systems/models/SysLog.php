<?php

/**
 * @package Ueb.modules.systems.models
 * 
 * @author Bob <zunfengke@gmail.com>
 */
class SysLog extends SystemsModel {

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
        return 'ueb_sys_log';
    }
    
    /**
     * Declares attribute labels.
     * @return array
     */
    public function attributeLabels() {
        return array(
            'user_name'             => Yii::t('system', 'Login Name'),
            'user_remote_ip'        => Yii::t('system', 'Remote IP'),
            'user_login_num'        => Yii::t('system', 'Login Num'),          
            'user_login_time'       => Yii::t('system', 'Login Time'),
            'user_login_status'     => Yii::t('system', 'Login Status'), 
            'id'                    => Yii::t('system', 'No.'),
        	'user_remote_ip'					=> 'IP'
        );
    }

    /**
     * add a log record
     * 
     * @param type $log
     */
    public static function log($userName, $userStatus = 'success') {      
        self::processLogs(array($userName, $userStatus));
    }

    /**
     * process log
     * 
     * @param type $log
     */
    protected static function processLogs($log) {
        $command = Yii::app()->db->createCommand();       
        $remoteIp = Yii::app()->request->userHostAddress;        
        $command->insert(self::tableName(), array(
            'user_name'         => $log[0],
            'user_login_status' => $log[1],
            'user_remote_ip'    => $remoteIp,
            'user_login_num'    => self::loginNum($log[0]),
        ));
    }
    
    /**
     * user login num
     * 
     * @param type $userName
     * @return int
     */
    public static function loginNum($userName) {      
       $logInfo = Yii::app()->db->createCommand() 
			->select('user_login_num')
			->from(self::tableName())
			->where(" user_name = '{$userName}'")      
            ->order("id DESC")
			->queryRow(); 
       if ( empty($logInfo) ) {
           return 1;
       } else {
           return $logInfo['user_login_num'] + 1;
       }
    }  
    
    /**
     * get search info
     */
    public function search()  
    {                
        $sort = new CSort();  
        $sort->attributes = array(  
            'defaultOrder'  => 'user_login_time',
            'user_login_num',  
            'user_login_time'
        );  
        
        return parent::search(get_class($this), $sort);
    }


    public function filterOptions() {
        return array(
            array(  
                'label'         => Yii::t('system', 'Login Name'),
                'name'          => 'user_name',               
                'type'          => 'text',
            	'search'        => 'LIKE',
            	'lookup'  		=> array(Yii::t('system','Choose a user'),'/users/access/selectOwn'),
        		'htmlOptions'   => array('readonly'=>'readonly','id'=>'user_result')
            ),
        		array(
        			'name'          => 'user_remote_ip',
        			'type'          => 'text',
        			'search'        => '=',
        			'htmlOptions'   => array()
        		),
        	array(
        		'name'          => 'user_login_time',
        		'type'          => 'text',
        		'search'        => 'RANGE',
        		'htmlOptions'   => array(
					    'class'     => 'date',
					    'dateFmt'   => 'yyyy-MM-dd HH:mm:ss',
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
    			'user_login_num','user_login_time'
    	);
    }

}