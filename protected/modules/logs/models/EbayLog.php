<?php

class EbayLog extends LogsModel
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
		return 'ueb_ebay_log';
	}
    
    /**
     * get page list
     * 
     * @return array
     */
    public function getPageList() {
        $this->_initCriteria();         
        if (! empty($_REQUEST['keywords']) ) {
            $keywords = trim($_REQUEST['keywords']);
            $this->criteria->addCondition("keywords = '{$keywords}'");  
        }
        $this->_initPagination( $this->criteria);
        $models = $this->findAll($this->criteria);
        
        return array($models, $this->pages);
    }            
      
}