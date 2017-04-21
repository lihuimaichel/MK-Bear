<?php
/**
 * @desc IP验证类
 * @author Gordon
 * @since 2015-05-26
 */
class IpAccessModel extends CFormModel {
    /** @var string 用户访问IP */
    public $client_ip = '';
    /** @var string IP配置参数 */
    public static $config_ip = array();
    /** @var string 报错记录*/
    public $error_message = '';
    
    public function __construct(){
        $this->client_ip = Yii::app()->request->userHostAddress;
        $this->setConfigIP();
    }
    /**
     * @desc 获取客户端IP
     */
    public function getClientIP(){
        if( !$this->client_ip ){
            $this->client_ip = Yii::app()->request->userHostAddress;
        }
        return $this->client_ip;
    }
    
    /**
     * @desc 配置IP
     */
    public function setConfigIP(){
        if( empty(self::$config_ip) ){
            self::$config_ip = array('127.0.0.1', '172.16.*');
        }
    } 
    
    /**
     * @desc 验证IP
     */
    public function authenticateIP(){
        $userIP     = $this->getClientIP();
        $accessIP   = self::$config_ip;
        $access     = false;
        foreach($accessIP as $ip){
            if( strpos($userIP, str_replace('*','',$ip))!==false ){
                $access = true;
                break;
            }
        }
        if(!$access){
            $this->addError('client_ip', Yii::t('app', 'Invalid IP Address.'));
        }
        return $access;
    }
    
    public function getErrorMessge(){
        
    }
} 