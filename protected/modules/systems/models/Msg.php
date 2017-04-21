<?php

class Msg extends SystemsModel {
    
    public $msg_type_name = null; 

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
	public function tableName()
	{
		return 'ueb_msg';
	}

    /**
     * add message
     * 
     * @param type $type
     * @param type $title
     * @param type $content
     * @return boolean
     */
    public static function add($type, $title, $content, $sendUser=0, $users = array()) {
        $db = Yii::app()->db;
        $transaction = $db->beginTransaction();
        try { 
        	if( $type == MsgType::PERSONAL_MSG_CODE ){
        		$msgTypeInfo['send_types'] = 1;
        		if( !empty($users) ){
        			$msgTypeInfo['send_roles'] = !is_array($users) ? $users : implode(",", $users);
        		}
        	}else{
	            $msgTypeInfo = MsgType::getByCode($type);
	            if ( empty($msgTypeInfo) || empty($msgTypeInfo['send_types']) ||
	                    empty($msgTypeInfo['send_roles'])) { 
	                return false;               
	            }            
        	}
        	if(!$sendUser){
        		$sendUser = Yii::app()->user->id ? Yii::app()->user->id : 'admin';
        	}
            $msgRow = array(
                'msg_type'          => $type,
                'msg_title'         => $title,
                'msg_content'       => $content,
            	'create_user_id'	=> $sendUser,
            );
            $db->createCommand()->insert(self::tableName(), $msgRow);
            $msgId = $db->getLastInsertID();
            $sendTypes = explode(",", $msgTypeInfo['send_types']);
            $sendRoles = explode(",", trim($msgTypeInfo['send_roles'], ','));
            $userIds = AuthAssignment::getUserIdsByRoleId($sendRoles);
            foreach ( $sendTypes as $sendType ) {
                foreach ( $userIds as $userId ) {
                    $userMsgRow = array(
                        'msg_id'        => $msgId,
                        'send_type'     => $sendType,
                        'user_id'       => $userId,
                        'update_time'   => date('Y-m-d H:i:s'),
                    );
                    $db->createCommand()->insert(UserMsg::tableName(), $userMsgRow);
                }
            }
            $transaction->commit();
            $flag = true;
        } catch (Exception $e) {
            $transaction->rollback();
            $flag = false;
        }

        return $flag;
    }

    /**
     * @desc 发送个人短信息
     * @author Gordon
     * @since 2014-09-29
     */
    public function sendPersonalMessage($title, $content, $users){
    	$sendUser = Yii::app()->user->id ? Yii::app()->user->id : 'admin';
    	$userArr = array();
    	foreach($users as $userId){
    		$userInfo = User::model()->getUserNameById($userId);
    		if( !empty($userInfo) ){
    			$userArr[] = $userInfo['user_name'];
    		}
    	}
    	$flag = self::add(MsgType::PERSONAL_MSG_CODE, $title, $content, $sendUser, $userArr);
    	return $flag;
    }
    
    /**
     * @desc 获取短消息内容
     * @param int $id
     * @throws CHttpException
     * @return Object
     */
    public function getMsgById($id){
    	$info = $this->findByPk($id);
    	if($info===null){
    		throw new CHttpException(404, Yii::t('app', 'The requested page does not exist.'));
    	}
    	return $info;
    }
}