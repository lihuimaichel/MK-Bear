<?php
/**
 * @desc search condition
 * @author Bob <zunfengke@gmail.com>
 */
class SearchCondition extends SystemsModel {   

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
		return 'ueb_search_condition';
	}

    /**
     * search condition data save 
     * 
     * @param string $modelName
     * @param array $clearRequest:从$_REQUEST里要去掉的数组
     */
    public function dataSave($curUrl,$modelName,$clearRequest=array()) {
    	$requestArr = !empty($clearRequest) ? array_diff($_REQUEST,$clearRequest) : $_REQUEST;
        $searchUrl = Yii::app()->request->getPathInfo();      
        $searchType = Yii::app()->request->getParam('on', $modelName);
        $userId = Yii::app()->user->id;
        $model = $this->findByAttributes(array(
            'model_name'  => $curUrl, 
            'search_type' => $searchType, 
            'user_id'     => $userId
        ));
       if ( empty($model) ) {          
           $model = new self();
           $model->setIsNewRecord(true);
           $model->setAttribute('search_count', 1);
       } else {
           $model->setAttribute('search_count', $model->search_count + 1);
       }
       if (! empty($_REQUEST['on'])) {             
          $searchMenuId = UebModel::model('Menu')->getIdByUrl('/'.$searchUrl. '/on/'.$_REQUEST['on']);        
       } else {
          $searchMenuId = UebModel::model('Menu')->getIdByUrl('/'.$searchUrl); 
       }
       
       $model->setAttribute('model_name', $curUrl);
       $model->setAttribute('search_type', $searchType);
       $model->setAttribute('search_url', '/'. $searchUrl); 
       if (! empty($searchMenuId) ) {
           $model->setAttribute('search_menu_id', $searchMenuId);
       }      
       $model->setAttribute('user_id', $userId); 
       $model->setAttribute('search_time', date('Y-m-d H:i:s')); 
       $model->setAttribute('search_condition', serialize($requestArr));
       $model->save();
    }
    
    /**
     * get search condition by model name
     * 
     * @param string $modelName
     * @return null|array
     */
    public function getSearchConditionByModelName($curUrl,$modelName) {       
        $searchType = isset($_REQUEST['on']) ? $_REQUEST['on'] : $modelName;
        $userId = Yii::app()->user->id;
        $row = $this->findByAttributes(array(
            'model_name'  => $curUrl, 
            'search_type' => $searchType, 
            'user_id'     => $userId
        ));
        
        return empty($row) ? array() : unserialize($row->search_condition);
    }
    
    /**
     *  get the highest frequency urls
     * 
     * @return array
     */
    public static function getHighestFrequencyUrls() { 
        $userId = Yii::app()->user->id;
        return Yii::app()->db->createCommand()  
			->select('search_url')
			->from(self::tableName())
			->where("search_url != '' AND user_id = '{$userId}' AND model_name = search_type")  
            ->order("search_count", 'DESC')   
            ->limit(15)
            ->queryColumn();        
    }
    
    /**
     * get history urls
     * 
     * @return array
     */
    public static function getHistoryUrls() {
        $userId = Yii::app()->user->id;
        return Yii::app()->db->createCommand()  
			->select('search_url')
			->from(self::tableName())
			->where("search_url != '' AND user_id = '{$userId}' AND model_name = search_type")  
            ->order("search_time", 'DESC')   
            ->limit(15)
            ->queryColumn(); 
    }
    
    

}