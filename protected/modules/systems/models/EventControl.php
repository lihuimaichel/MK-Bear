<?php
/**
 * @desc 事件控制
 * @author Gordon
 */
class EventControl extends SystemsModel {
    
    /**
     * @desc 事件状态
     * @var tinyint
     */
    const STATUS_START = 0;
    
    const STATUS_RUNNING = 1;   
    
    const STATUS_SUCCESS = 2;
    
    const STATUS_FAILURE = -1;
    
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
		return 'ueb_event_control';
	}   
	   

    /**
     * get EventControl status
     */
    public function getEventControlStatus($type = null){
    	$status = array(
    		self::STATUS_START   => Yii::t('system', 'Status start'),
    		self::STATUS_RUNNING => Yii::t('system', 'Status running'),
    		self::STATUS_SUCCESS => Yii::t('system', 'Status success'),
    		self::STATUS_FAILURE => Yii::t('system', 'Status failure'),
    	);
    	if($type !== null){
	    	switch (strtolower($type)) {
	            case '0':          
	                echo '<font color="green" >' . Yii::t('system', 'Status start') . '</font>';
	                break;
	            case '1':
	                echo '<font color="black" >' . Yii::t('system', 'Status running') . '</font>';
	                break;
	            case '2':    
	                echo '<font color="green" >' . Yii::t('system', 'Status success') . '</font>';
	                break;
	            case '-1':
	                echo '<font color="red" >' . Yii::t('system', 'Status failure') . '</font>';
	                break;
	            
	        }     
    	}else{
    		return $status;
    	}
    }

    
    /**
     *  find by event name
     *
     * @param string $eventName
     * @return object $model
     * @throws CHttpException
     */
    public function findByEventName($eventName) {
    	$criteria = new CDbCriteria;
    	$criteria->addCondition(" event_name = '{$eventName}'");
    	$criteria->order = "id DESC";
    	$criteria->limit = '1';
    
    	return $this->find($criteria);
    }
    

    /**
     * find by related key not in event name array
     * 
     * @param string $relatedKey
     * @param string|array $eventName
     * @return array
     */
    public function findByRelatedKeyNotINEventName($relatedKey, $eventName) {
        if ( is_string($eventName) ) { $eventName = (array) $eventName;}
        $criteria = new CDbCriteria;
        $criteria->addNotInCondition('event_name', $eventName);       
        $criteria->addCondition(" event_related_key = '{$relatedKey}'");
        $criteria->order = "id DESC";
        $criteria->limit = '1';      
            
        return $this->find($criteria);
    }
    
    /**
     * get record log
     */
    public function getRecordLog() {
    	$msg = '';
    	foreach ( $this->getAttributes() as $key => $val ) {
    		if ( ! $this->getIsNewRecord() && $val == $this->beforeSaveInfo[$key] ) {
    			continue;
    		}
    		$label = $this->getAttributeLabel($key);
    		if (in_array($key, array( 'id'))) {
    			continue;
    		}else if($key == 'event_status'){
    			if ( $this->getIsNewRecord() ) {
					$status = $this->getEventControlStatus();
    				$msg .= MHelper::formatInsertFieldLog($label, $status[$val]);
    			} else {
    				$status = $this->getEventControlStatus();
    				$msg .= MHelper::formatUpdateFieldLog($label, $status[$this->beforeSaveInfo[$key]], $status[$val]);
    			}
    		}else {
    			if ( $this->getIsNewRecord() ) {
    				$msg .= MHelper::formatInsertFieldLog($label, $val);
    			} else {
    				$msg .= MHelper::formatUpdateFieldLog($label, $this->beforeSaveInfo[$key], $val);
    			}
    		}
    	}
    	$this->addLogMsg($msg);
    }
         

}