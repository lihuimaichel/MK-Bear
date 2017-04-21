<?php

/**
 * api db log class file
 *
 * @author Bob <zunfengke@gmail.com>
 * 
 */
class ApiDbLog extends CComponent {
    
   /**
    * 
    * @var object in instance
    */
	private static $_instance;
    
    /**
     *
     * @var string tag
     */
    public $tag = null;
    
    /**
     *
     * @var string level 
     */
    public $level = null;
    
    /**
     *
     * @var string key
     */
    public $key = null;
    
    /**
     *
     * @var string the message
     */
    public $message = null;
    
    /**
     *
     * @var string request URL
     */
    public $requestUrl = null;
    
    /**
     *
     * @var integer user id
     */
    public $userId = null;
    
    /**
     *
     * @var string type 
     */
    public $type = null;
    
    /**
     *
     * @var object model obj 
     */
    protected $_modelObj = null;
    
    public function __construct() {} 
    
    /**
     * 
     * @return object get instance
     */
    public static function getInstance() {
		if(! self::$_instance instanceof self){
			self::$_instance = new self();
		}
		
		return self::$_instance; 
	}

    /**
     *  set model obj 
     * 
     * @return \ApiDbLog
     * @throws Exception
     */
    public function setModelObj() {
        if ( empty($this->type) ) {
            throw new Exception('The type is not allowed to be empty');
        }  
        
        $tableName = "ueb_" . $this->type . "_log";      
        $modelObj = MHelper::getModelByTableName($tableName);        
        $this->_modelObj = $modelObj;
        
        return $this;
    }
    
    /**
     * get model obj
     * 
     * @return object
     * @throws Exception
     */
    public function getModelObj() {
        if ( empty($this->_modelObj) ) {
            throw new Exception('The type is not allowed to be empty');
        } 
        
        return $this->_modelObj;
    }
    
    /**
     * save api data
     */
    public function save() {     
        $requestUrl = empty($this->requestUrl) ? 
                Yii::app()->request->getRequestUri() : $this->requestUrl;
        
        $model = $this->getModelObj();
        $model->setIsNewRecord(true);
        $model->setAttribute('tag', $this->tag);
        $model->setAttribute('level', $this->level);
        $model->setAttribute('keywords', $this->key);
        $model->setAttribute('message', $this->message);
        $model->setAttribute('request_url', $requestUrl);
        $model->setAttribute('user_id', Yii::app()->user->id);
        $model->setAttribute('log_time', date('Y-m-d H:i:s'));      
        $model->save();
    }
}

