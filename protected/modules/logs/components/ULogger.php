<?php

/**
 * ULogger class file
 *
 * @author Bob <zunfengke@gmail.com>
 * 
 */
class ULogger extends CComponent {

    const LEVEL_ERROR = 'error';
    
    const LEVEL_SUCCESS = 'success';
    
    const LEVEL_FAILURE = 'failure'; 
    
    const LEVEL_INFO = 'info';
      
    public $autoFlush = 10;
    
    public $autoDump = true;

    /**
     * @var array log messages
     */
    private $_logs = array();

    /**
     * @var integer number of log messages
     */
    private $_logCount = array();

    /**
     * @var array log levels for filtering (used when filtering)
     */
    private $_levels;

    /**
     * @var array log type
     */
    private $_types;

    /**
     * @var array log categories for excluding from filtering (used when filtering)
     */
    private $_except = array();

    /**
     * @var boolean if we are processing the log or still accepting new log messages
     * @since 1.1.9
     */
    private $_processing = false;

    /**
     * Logs a message.
     * @param string $tag message header or api name etc.
     * @param string $message message to be logged
     * @param string $type 
     * @param string $level level of the message , It is case-insensitive.
     * @param string $key, unique keywods of the type.
     * @param string $requestUrl ,request api url
     * @see getLogs
     */
    public function log($tag, $message, $type = 'info', $level = 'info', $key = null, $requestUrl = null) {       
        $user = Yii::app()->user->id;            
        if ( empty($requestUrl) ) {
            $requestUrl = Yii::app()->request->getRequestUri();
        }
        
        $this->_logs[$type][] = array($tag, $message, $level, $key, $requestUrl, microtime(true), $user);        
        if (! isset( $this->_logCount[$type])) {           
            $this->_logCount[$type] = 1;          
        } else {          
            $this->_logCount[$type]++;
        }
       
        if ($this->autoFlush > 0 && $this->_logCount[$type] >= $this->autoFlush && !$this->_processing) {           
            $this->_processing = true;
            $this->flush($this->autoDump);
            $this->_processing = false;
        }        
    }
    
     /**
	 * @param string $levels level filter
	 * @param string $types log type 
	 * @return array list of messages. Each array element represents one message
	 */
	public function getLogs($levels = '', $types = '')
	{      
		$this->_levels = preg_split('/[\s,]+/',strtolower($levels),-1,PREG_SPLIT_NO_EMPTY);  
        $this->_types = preg_split('/[\s,]+/',strtolower($types),-1,PREG_SPLIT_NO_EMPTY);      
		$ret = $this->_logs;        
        if(! empty($levels)) {          
            foreach ($ret as $key => $val) {
                if ( in_array(strtolower($key), $this->_types) ) {                   
                    $ret[$key] = array_values(array_filter($val,array($this,'filterByLevel')));	
                }               
            }          
        }
        
		return $ret;
	}
    
    /**
	 * Filter function used by {@link getLogs}
	 * @param array $value element to be filtered
	 * @return boolean true if valid log, false if not.
	 */
	private function filterByLevel($value)
	{             
		return in_array(strtolower($value[2]),$this->_levels);
	}
    
    	/**
	 * Removes all recorded messages from the memory.
	 * This method will raise an {@link onFlush} event.
	 * The attached event handlers can process the log messages before they are removed.
	 * @param boolean $dumpLogs whether to process the logs immediately as they are passed to log route
	 * @since 1.1.0
	 */
	public function flush($dumpLogs=false)
	{
		$this->onFlush(new CEvent($this, array('dumpLogs'=>$dumpLogs)));
		$this->_logs = array();
		$this->_logCount= array();
	}

	/**
	 * Raises an <code>onFlush</code> event.
	 * @param CEvent $event the event parameter
	 * @since 1.1.0
	 */
	public function onFlush($event)
	{
		$this->raiseEvent('onFlush', $event);
	}

}
