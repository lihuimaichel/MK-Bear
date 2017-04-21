<?php

class UserMsg extends UsersModel {
    
    public $user_name = null;

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
        return 'ueb_user_msg';
    }
    
     /**
     * Declares attribute labels.
     * @return array
     */
    public function attributeLabels() {
        return array(
            'msg_type'         => Yii::t('system', 'Message Type'),
            'msg_title'        => Yii::t('system', 'Message Title'),
            'msg_content'      => Yii::t('system', 'Message Content'),          
            'status'           => Yii::t('system', 'Status'),
            'update_time'      => Yii::t('system', 'Update Time'), 
            'id'               => Yii::t('system', 'No.'),
            'user_name'        => Yii::t('system', 'Login Name'),
        );
    }
    
    /**
	 * @return array relational rules.
	 */
	public function relations() {
        return array(
            'msg'   => array(self::BELONGS_TO, 'Msg', array( 'msg_id' => 'id')),          
        );       
    }
    
    /**
     * get search info
     */
    public function search() {                
        $sort = new CSort();  
        $sort->attributes = array(  
            'defaultOrder'  => 'update_time',
            'msg_type',             
        );
        $with = array( 'msg' );
        return parent::search(get_class($this), $sort, $with);
    }
    
    /**
     * filter search options
     * @return type
     */
    public function filterOptions() {
        return array(
            array(  
                'label'         => Yii::t('system', 'Message Title'),
                'name'          => 'msg_title',               
                'type'          => 'text',
                'search'        => '=',
                'htmlOptions'   => array(),
            ),  
            array(  
                'label'         => Yii::t('system', 'Message Type'),
                'name'          => 'msg_type',              
                'type'          => 'dropDownList',
                'search'        => '=',
                'data'          => MHelper::getMsgTypeConfig(),
                'htmlOptions'   => array(),
            ),
            array(  
                'label'         => Yii::t('system', 'Status'),
                'name'          => 'status',              
                'type'          => 'dropDownList',
                'search'        => '=',
                'data'          => VHelper::getMsgStatusConfig(),
                'htmlOptions'   => array(),
            )
        );
    }
    
    /**
     * order field options
     * @return $array
     */
    public function orderFieldOptions() {
    	return array(
    			'update_time','status'
    	);
    }

    /**
     * get msg list
     * 
     * @param type $sendType
     * @return type
     */
    public static function getMsglist($sendType = 1) {
        $userId = Yii::app()->user->id;   
        $limit = Yii::app()->params['msg_notify_show_count'];
        $joinTable = Msg::model()->tableName();      
        $selectObj = Yii::app()->db->createCommand()
                ->select('m.msg_title,msg_content,um.id,m.create_user_id,m.created_time,um.status')
                ->from(self::tableName() . ' um')
                ->join($joinTable . ' m', "um.`msg_id` = m.id")
                ->where("um.status = 0")
                ->order('m.created_time DESC')
                ->limit($limit);
        $selectObj->andWhere(" send_type = '{$sendType}'");
        $selectObj->andWhere(" user_id = '{$userId}'");           
        $list = $selectObj->queryAll();                     
        
        return $list;
    }
    
    /**
     * 
     * batch refresh flags 
     * 
     * @param type $ids
     * @return type
     */
    public function batchFlags($ids) {      
        $db = Yii::app()->db;  
        $data = array( 'status' => 2);
        return $db->createCommand()
                ->update(self::tableName(), $data,  " id IN({$ids})");
    }
    
    public function getInfoById($id){
    	$info = $this->findByPk($id);
    	if($id===null){
    		throw new CHttpException(404, Yii::t('app', 'The requested page does not exist.'));
    	}
    	return $info;
    }

}