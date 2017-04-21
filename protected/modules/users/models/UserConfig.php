<?php

class UserConfig extends UsersModel
{	
    
    public $theme = null;
    
    public $language = null;
    
    public $per_page_num = null;
    
    public $msg_notify_interval = null;
    
    public $msg_notify_show_count = null;
    
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
		return 'ueb_user_config';
	}
    
    public function rules() {
        $rules = array(         
           array('theme,language, per_page_num, msg_notify_interval, msg_notify_show_count', 'required'), 
           array('per_page_num', 'numerical', 'integerOnly' => true),    
           array('msg_notify_show_count', 'numerical', 'integerOnly' => true, 'min' => 1, 'max' => '10'),         
           array('msg_notify_interval', 'numerical', 'integerOnly' => true, 'min' => 10), 
        );        
        return $rules;
    }
    
    /**
     * Declares attribute labels.
     * @return array
     */
    public function attributeLabels() {
        return array(
            'theme'                 => Yii::t('users', 'Default Theme'),
            'language'              => Yii::t('users', 'Default Language'),
            'per_page_num'          => Yii::t('users', 'Per Page Num'),    
            'msg_notify_interval'   => Yii::t('users', 'Message Notify Interval(Sec)'),
            'msg_notify_show_count' => Yii::t('users', 'Message Notify Show Count'),
        );
    }
    
    /**
     * default theme config
     */
    public static function getThemeConfig() {
        return array(
            'azure'     => Yii::t('system', 'Azure'),
            'default'   => Yii::t('system', 'Blue'),
            'purple'    => Yii::t('system', 'Purple'),
            'green'     => Yii::t('system', 'Green'),
            'silver'    => Yii::t('system', 'Silver'),
        );
    }
    
    /**
     * default language config 
     */
    public static function getLanguageConfig() {
        return array(
            'zh_cn'     => Yii::t('system', 'Chinese Language'),
            'en'        => Yii::t('system', 'English Language'),          
        );
    }
    
    /**
     * set init 
     */
    public function setInit() {
        $userId = Yii::app()->user->id;
        $pairs = $this->getPairByUserId($userId);      
        $this->theme = isset($pairs['theme']) ? 
            $pairs['theme'] : Yii::app()->params['theme'];
        $this->language = isset($pairs['language']) ? 
            $pairs['language'] : Yii::app()->language;
        $this->per_page_num = isset($pairs['per_page_num']) ? 
            $pairs['per_page_num'] : Yii::app()->params['per_page_num'];
        $this->msg_notify_interval = isset($pairs['msg_notify_interval']) ? 
            $pairs['msg_notify_interval'] : Yii::app()->params['msg_notify_interval'];
        $this->msg_notify_show_count = isset($pairs['msg_notify_show_count']) ? 
            $pairs['msg_notify_show_count'] : Yii::app()->params['msg_notify_show_count'];
    }
    
    /**
     * batch save data
     * 
     * @param type $vars
     * @return boolean
     */
    public function batchSave($vars) {
        $transaction = Yii::app()->db->beginTransaction();
        try {
            $userId = Yii::app()->user->id;               
            foreach ($vars as $key => $val) {              
                $info = Yii::app()->db->createCommand()
                            ->select('id')
                            ->from(self::tableName())
                            ->where( 'config_key=:config_key', array(':config_key'=>$key))                           
                            ->andWhere('config_user_id=:config_user_id', array(':config_user_id' => $userId))
                            ->queryRow();             
                $data = array(
                    'config_key'        => $key,
                    'config_value'      => $val,
                    'config_user_id'    => $userId
                );
                if ( empty($info['id']) ) {
                   Yii::app()->db->createCommand()->insert(self::tableName(), $data);                  
                } else {                       
                   Yii::app()->db->createCommand()->update(self::tableName(), $data,
                    'id=:id', array(':id' => $info['id'])); 
                }                      
            }                               
            $transaction->commit();
            $flag = true;
        } catch (Exception $e) {
            $transaction->rollback();
            $flag = false;
        }
        
        return $flag;
    }
    
    /**
     * get list cache by user id
     * 
     * @param type $userId
     * @return null | array $data
     */
    public static function getConfigCacheByUserId($userId) {
        $userConfig = Yii::app()->cache->get('userconfig'.$userId);       
        if ( $userConfig === false )
        {
            $userConfig = self::getPairByUserId($userId);
            Yii::app()->cache->set('userconfig'.$userId, $userConfig, 60*60*24);
        }          

        return $userConfig;
    }
    
     /**
     * get list by user id
     * 
     * @param type $userId
     * @return null | array $data
     */
    public static function getPairByUserId($userId) {
        $columns = array('config_key', 'config_value');
        $data = UebModel::model('userConfig')
                 ->queryPairs($columns, " config_user_id = '{$userId}'");
        
       return $data;                 
    }

}