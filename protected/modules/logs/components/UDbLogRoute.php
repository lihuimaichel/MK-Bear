<?php

/**
 * UDbLogRoute class file.
 *
 * @author Bob <zunfengke@gmail.com>
 * 
 */
class UDbLogRoute extends ULogRoute {

    /**
     * @var string the ID of CDbConnection application component. If not set, a SQLite database
     * will be automatically created and used. The SQLite database file is
     * <code>protected/runtime/log-YiiVersion.db</code>.
     */
    public $connectionID;

    /**
     * Note, the 'id' column must be created as an auto-incremental column.
     * In MySQL, this means it should be <code>id INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY</code>;    
     * @see autoCreateLogTable
     */
    public $logTableName = null;

    /**
     * @var boolean whether the log DB table should be automatically created if not exists. Defaults to true.
     * @see logTableName
     */
    public $autoCreateLogTable = true;

    /**
     * @var CDbConnection the DB connection instance
     */
    private $_db;      

    /**
     * Initializes the route.
     * This method is invoked after the route is created by the route manager.
     */
    public function init() {
        parent::init();      
    }

    /**
     * Creates the DB table for storing log messages.   
     * @param string $key
     */
    protected function createLogTable($key) {               
        try {
            Yii::app()->db->createCommand()->delete($this->logTableName, '0=1');
            $key = 'createlogtable';
            $data = Yii::app()->cache->get($key);                 
            if ( $data === false ) {
                Yii::app()->cache->set($key, true);       
            }
        } catch (Exception $e) {           
              Yii::app()->db->createCommand()->createTable($this->logTableName, array(
                'id'            => 'pk',
                'tag'           => 'varchar(128)',            
                'level'         => 'varchar(50)',
                'keywords'       => 'varchar(30)',
                'message'       => 'text',
                'request_url'   =>'varchar(255)',
                'user_id'          =>'varchar(255)',  
                'log_time'      => 'datetime',               
            ));
        }
    } 

    /**
     * Stores log messages into database.
     * @param array $logs list of log messages
     */
    protected function processLogs($logs) {      
        foreach ($logs as $key => $val) {     
            $this->logTableName = "ueb_" . $key . "_log";             
            if ($this->autoCreateLogTable || 
                    !Yii::app()->cache->get('createlogtable')) {              
                $this->createLogTable($key);
            }               
            foreach ($val as $log) { 
                if ( !empty($log[3]) ) {                    
                    /*$info = Yii::app()->db->createCommand()->select('id')
                            ->from($this->logTableName)
                            ->where( 'tag=:tag', array(':tag'=>$log[0]))                           
                            ->andWhere('keywords=:keywords', array(':keywords' => $log[3]))
                            ->queryRow();                      
                    if ( empty($info['id']) ) {*/                      
                        $this->_insert($log);
                    /*} else {                       
                        $this->_update($info['id'], $log);
                    } */                  
                }               
            }            
        }
    }
    
    /**
     * insert a log record
     * 
     * @param type $log
     */
    protected function _insert($log) {
        Yii::app()->db->createCommand()->insert($this->logTableName, array(
            'tag'           => $log[0], 
            'level'         => $log[2],
            'keywords'      => $log[3],
            'message'       => is_array($log[1]) ? htmlspecialchars(implode('',$log[1])) : $log[1],
            'request_url'   => $log[4],   
            'user_id'          => $log[6],  
            'log_time'      => date('Y-m-d H:i:s',$log[5]),                   
        ));
    }
    
    /**
     * update a log record
     * 
     * @param type $id
     * @param type $log
     */
    protected function _update($id, $log) {
        Yii::app()->db->createCommand()->update($this->logTableName, array(
            'tag'           => $log[0], 
            'level'         => $log[2],
            'keywords'      => $log[3],
            'message'       => $log[1],
            'request_url'   => $log[4],    
            'user_id'       => $log[6],  
            'log_time'      => date('Y-m-d H:i:s',$log[5]), 
        ),
        'id=:id', array(':id'=>$id)
        );
    }
}
