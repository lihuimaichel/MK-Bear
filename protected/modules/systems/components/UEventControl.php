<?php
/**
 * @desc 事件控制
 * @author Gordon
 * @since 2015-06-06
 */
class UEventControl {
    
    private static $_instance;
    
    /** @var int 最大运行时间间隔*/
    public $runningIntervalTime = 600;
    
    /** @var int 最大运行时间*/
    public $maxRunTime = 3600;
    
    /** @var int 事件控制模型*/
    protected $_modelObj = null;
    
    public function __construct() {               
        $this->_modelObj = UebModel::model('eventControl');
    }
    
    /**
     * @desc 初始化
     * @return UEventControl
     */
	public static function getInstance(){
		if( !self::$_instance instanceof self ){
			self::$_instance = new self();
		}
		return self::$_instance; 
	}
    
    /**
     * @desc 设置最大运行时间
     * @param int $maxTime
     */
    public function setMaxRunTime($maxTime) {
		$this->maxRunTime = $maxTime;
	}
    
    /**
     * @desc 返回最大运行时间
     * @param string $eventName
     */
    public function getMaxRunTime($eventName) {
        return $this->maxRunTime;
	}
    
    /**
     * @desc 事件运行动作
     * @param 事件名称 $eventName
     * @param string $step
     * @param string $note
     * @param string $relatedKey
     * @return unknown
     */
    public function event($eventName, $step = 'start', $note = '', $relatedKey = '') {
        $name = 'event'.ucfirst(strtolower($step));
        if ( $step == 'start') {
        	$flag=$this->$name($eventName, $note, $relatedKey);
            return $flag;
        } else {
        	$flag=$this->$name($eventName, $note);
            return $flag;
        }       
    }

    /**
     * @事件开始
     * @param string $eventName
     * @param string $note
     * @param string $relatedKey
     */
    public function eventStart($eventName, $note = '', $relatedKey = '') {
		if( $this->checkEventRunStatus($eventName)) {
            if ( empty($relatedKey) ) { 
                $relatedKey = $eventName;              
            }
            $this->_modelObj->setAttribute('event_name', $eventName);
            $this->_modelObj->setAttribute('event_related_key', $relatedKey);
            $this->_modelObj->setAttribute('event_status', EventControl::STATUS_RUNNING);
            $this->_modelObj->setAttribute('start_time', date('Y-m-d H:i:s'));
            $this->_modelObj->setAttribute('respond_time', date('Y-m-d H:i:s'));
            $this->_modelObj->setAttribute('note', $note); 
            $this->_modelObj->setIsNewRecord(true);
            return $this->_modelObj->save();			
		}
		return false;
	}
    
    /**
     * @desc 事件响应
     * @param string $eventName
     * @param string $note
     */
    public function eventRespond($eventName, $note = '') {       
        $model = $this->_modelObj->findByEventName($eventName);
        if ( $model['event_status'] == EventControl::STATUS_FAILURE ) {
            return false;
        }
        if( (time() - strtotime($model['start_time'])) >= $this->getMaxRunTime($eventName)) {
            $this->event($eventName, 'failure');          
            return false;
        }
        $model->setAttribute('respond_time', date('Y-m-d H:i:s'));
        $model->setAttribute('event_status', EventControl::STATUS_RUNNING);
        $model->setAttribute('note', $note);
		return $model->save();		
	}
    
    /**
     * @desc 事件结束
     * @param string $eventName
     * @param string $note
     */
    public function eventEnd($eventName, $note = '') {
        $model = $this->_modelObj->findByEventName($eventName);
        $model->setAttribute('respond_time', date('Y-m-d H:i:s'));
        $model->setAttribute('event_status', EventControl::STATUS_SUCCESS);
        $model->setAttribute('note', $note);
		$model->save();	
    } 
    
    /**
     * @desc 设置事件失败
     * @param unknown $eventName
     * @param string $note
     */
    public function eventFailure($eventName, $note = '') {
        $model = $this->_modelObj->findByEventName($eventName);
        $model->setAttribute('respond_time', date('Y-m-d H:i:s'));
        $model->setAttribute('event_status', EventControl::STATUS_FAILURE);
        $model->setAttribute('note', $note);
		$model->save();	
    } 
    
    /**
     * @desc 检测事件运行状态
     * @param string $eventName
     */
    public function checkEventRunStatus($eventName) {       			
       $model = $this->_modelObj->findByEventName($eventName); 
       
       if ( empty($model) ) return true;
       if ( $eventName != $model['event_related_key']) {      
           if (! $this->checkRelatedEventsRunStatus($eventName, $model['event_related_key'])) {
               return false;
           }
       } 
       if ( $model['event_status'] == EventControl::STATUS_FAILURE || 
                $model['event_status'] == EventControl::STATUS_SUCCESS) {
            return true;
       }
       
       if ( (time() - strtotime($model['respond_time'])) >= $this->runningIntervalTime) {
            $this->event($eventName, 'failure');          
            return true;
        }      
        
        return false;
    }
    
    /**
     * @desc 检测关联事件运行状态
     * @param string $eventName
     * @param string $relatedKey
     */
    public function checkRelatedEventsRunStatus($eventName, $relatedKey) {
        $eventName = (array) $eventName;  
        $model = $this->_modelObj->findByRelatedKeyNotINEventName($relatedKey, $eventName);       
        while (! empty($model) ) {
            $flag = $this->_checkRelatedEventRunStatus($model, $relatedKey);           
            if (! $flag) { 
                return false;                
            } 
            array_push($eventName, $model['event_name']);
            $model = $this->_modelObj->findByRelatedKeyNotINEventName($relatedKey, $eventName);          
        }
        
        return true;
    }

    /**
     * @desc 检测关联事件运行状态
     * @param type $eventName
     * @param type $relatedKey
     * @return boolean
     */
    protected function _checkRelatedEventRunStatus($model, $relatedKey) {     
        if ( $model['event_status'] == EventControl::STATUS_FAILURE || 
                $model['event_status'] == EventControl::STATUS_SUCCESS) {
            return true;
        }
        
        if ( (time() - strtotime($model['respond_time'])) >= $this->runningIntervalTime) {
            $this->event($model['event_name'], 'failure');          
            return true;
        }
        return false;
    }
}
?>
