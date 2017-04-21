<?php

class OperationLog extends LogsModel
{	
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
		return 'ueb_operation_log';
	}
    
    /**
     * get page list
     * 
     * @return array
     */
    public function getPageList() {
        $this->_initCriteria();         
        if (! empty($_REQUEST['keywords']) ) {
            $key = trim($_REQUEST['keywords']);
            $this->criteria->addCondition("keywords = '{$key}'");  
        }
        if (! empty($_REQUEST['log_time']) ) {
        	if (! empty($_REQUEST['log_time'][0]) && ! empty($_REQUEST['log_time'][1])) {
				$log_time_0 = trim($_REQUEST['log_time'][0]);
				$log_time_1 = trim($_REQUEST['log_time'][1]);
        		$this->criteria->addCondition("log_time >= '{$log_time_0}' and log_time <= '{$log_time_1}'");
        	}else if (! empty($_REQUEST['log_time'][0])) {
        		$log_time_0 = trim($_REQUEST['log_time'][0]);
        		$this->criteria->addCondition("log_time >= '{$log_time_0}'");
        	}else if(! empty($_REQUEST['log_time'][1])){
        		$log_time_1 = trim($_REQUEST['log_time'][1]);
        		$this->criteria->addCondition("log_time <= '{$log_time_1}'");
        	}
        }
        $this->criteria->order = 'log_time DESC';
        if(!empty($_REQUEST['level'])){
        	$level = trim($_REQUEST['level']);
        	$this->criteria->addCondition("level = '{$level}'");
        }
        $this->_initPagination( $this->criteria);
        $models = $this->findAll($this->criteria);
        return array($models, $this->pages);
    }            
      
}