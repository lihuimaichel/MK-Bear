<?php

/**
 * ApplicationBehavior is a behavior for the application
 *
 * @package Ueb.components
 * 
 * @author Bob <zunfengke@gmail.com>
 */
class ApplicationBehavior extends CBehavior {

    /**
     * {@link CBehavior::events()}
     */
    public function events() {
        return array_merge(parent::events(), array(
            'onBeginRequest' => 'beginRequest',
            'onEndRequest'   => 'endRequest',
        ));
    }

    /**
     * this method will be called when the request has begun.
     */
    public function beginRequest() {   
    	Yii::import('application.controllers.UebController');
//     	Yii::import('application.controllers.ErpController');
        Yii::import('application.modules.users.models.*');  
        Yii::import('application.modules.systems.models.*');
        Yii::import('application.modules.logs.models.*');
        Yii::import('application.modules.systems.controllers.*');
        Yii::import('application.modules.systems.components.*'); 
        //is admin     
        if ( UebModel::model('user')->isAdmin() ) {
            Yii::app()->params['isAdmin'] = 1;          
        }      
        $userId = Yii::app()->user->id;                   
        $userConfig = UserConfig::getConfigCacheByUserId($userId);
        // language config
        if ( isset($userConfig['language']) ) {
             Yii::app()->owner->setLanguage($userConfig['language']);           
        }         
        //  theme config
        if ( isset($userConfig['theme']) ) {
             Yii::app()->params['theme'] = $userConfig['theme'];
        }
        // num per page
        if ( isset($userConfig['per_page_num']) ) {
            Yii::app()->params['per_page_num'] = $userConfig['per_page_num'];
        }
        
        // msg notify interval
        if ( isset($userConfig['msg_notify_interval']) ) {
            Yii::app()->params['msg_notify_interval'] = $userConfig['msg_notify_interval'];
        }
        
        // msg notify show count
        if ( isset($userConfig['msg_notify_show_count']) ) {
            Yii::app()->params['msg_notify_show_count'] = $userConfig['msg_notify_show_count'];
        }
               
        $sysConfig = SysConfig::getConfigCacheByType('system');
        // timezone setting
        if ( isset($sysConfig['timezone']) ) {            
            date_default_timezone_set($sysConfig['timezone']);
        } 
        
        if ( stripos(Yii::app()->request->getRequestUri(), "?_=") !== false ) {
            $uri = explode("?_=", Yii::app()->request->getUrl()); 
            $uri[1] = substr($uri[1], 0, 13);
            Yii::app()->session->add('timings_'.session_id().$uri[1], microtime(true));            
        }       
    }
    
    /**
     * this method will be called when the request has ended.
     */
    public function endRequest() {                    
        if ( stripos(Yii::app()->request->getRequestUri(), "?_=") !== false ) {
            $uri = explode("?_=", Yii::app()->request->getUrl()); 
            $uri[1] = substr($uri[1], 0, 13);
            $uniqueKey = 'submit_'.session_id().$uri[1];
            
            if ( Yii::app()->session->get($uniqueKey) ) {         
                Yii::app()->session->remove($uniqueKey);
            } 
            
            CHelper::profilingTimeLog(); 
        }  
    }

}
