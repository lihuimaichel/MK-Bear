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
        return 'ueb_msg_type';
    }
    
     /**
     * Declares attribute labels.
     * @return array
     */
    public function attributeLabels() {
        return array(
            'name'             => Yii::t('system', 'Type Name'),
            'code'             => Yii::t('system', 'Message Code'),
            'send_types'       => Yii::t('system', 'Send Type'),          
            'send_roles'       => Yii::t('system', 'Send Role'),
            'status'           => Yii::t('system', 'Status'), 
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
            'defaultOrder'  => 'status',
            'id',             
        );
        $with = array( 'msg_type' );
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
                'data'          => MHelper::getMsgTypeConfig(true),
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
     * get page list
     * 
     * @return array
     */
    public function getPageList() {       
        $this->_initCriteria();
        $this->criteria->with = array(  
            'msg',  
        );                
        if (! empty($_REQUEST['msg_type']) ) {
            $msgType = trim($_REQUEST['msg_type']);
            $this->criteria->addCondition("msg_type = '{$msgType}'");  
        }
        if (! empty($_REQUEST['msg_title']) ) {
            $msgTitle = trim($_REQUEST['msg_title']);
            $this->criteria->addCondition("msg_title = '{$msgTitle}'");  
        } 
        if ( isset($_REQUEST['status']) ) {
            $status = trim($_REQUEST['status']);
            $this->criteria->addCondition("status = '{$status}'");  
        }
        if ( !User::isAdmin() ) {
            $userId = Yii::app()->user->id;
            $this->criteria->addCondition("user_id = '{$userId}'"); 
        }
        $msgTypeConfig = MHelper::getMsgTypeConfig();
        $this->_initPagination( $this->criteria);
        $models = $this->findAll($this->criteria); 
        if ( Yii::app()->params['isAdmin'] ) {
            $userPairs = UebModel::model('user')->getPairs();
        }
        foreach ( $models as $key => $val ) {
            $val->msg->msg_type_name = $msgTypeConfig[$val->msg->msg_type]; 
            if ( Yii::app()->params['isAdmin'] ) {              
                $val->user_name = $userPairs[$val['user_id']];
            }
        }      
        
        return array($models, $this->pages);
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
                ->select('m.msg_title,msg_content,um.id')
                ->from(self::tableName() . ' um')
                ->join($joinTable . ' m', "um.`msg_id` = m.id")
                ->where("um.status = 0")
                ->order('m.created_time')
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

}