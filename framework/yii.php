<?php

/**
 * Yii bootstrap file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 * @package system
 * @since 1.0
 */
require(dirname(__FILE__) . '/YiiBase.php');

/**
 * Yii is a helper class serving common framework functionalities.
 *
 * It encapsulates {@link YiiBase} which provides the actual implementation.
 * By writing your own Yii class, you can customize some functionalities of YiiBase.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package system
 * @since 1.0
 */
class Yii extends YiiBase {

    /**
     * api logger
     * 
     * @var type 
     */
    private static $_ulogger;

    /**
     * test env
     * print mixed data 
     * 
     * @param type $vars
     */
    public static function p($vars) {
        if ( Env::DEVELOPMENT == YII_ENV ) {
            echo '<pre>';
            print_r($vars);
            exit;           
        }       
    }

    /**
     * @return business Logger message logger
     */
    public static function getULogger() {
        if (self::$_ulogger !== null)
            return self::$_ulogger;
        else
            return self::$_ulogger = new ULogger;
    }

    /**
     * api server logger
     * @param string $tag record tag
     * @param string $message
     * @param string $type ebay,amazon,aliexpress etc.
     * @param string $level
     * @param string $key
     * @param string $requestUrl request url
     */
    public static function ulog($message, $tag= null, $key = null, $type = 'operation', $level = ULogger::LEVEL_INFO, $requestUrl = null) {
        if (self::$_ulogger === null) {
            self::$_ulogger = new ULogger;
        }
        self::$_ulogger->log($tag, $message, $type, $level, $key, $requestUrl);
    }
    
    /**
     * api db log
     * 
     * @param string $message
     * @param string $tag
     * @param string $key
     * @param string $type
     * @param string $level
     * @param string $requestUrl
     */
    public static function apiDbLog($message, $tag= null, $key = null, $type = 'ebay', $level = ULogger::LEVEL_ERROR, $requestUrl = null) {
        $apiDbLog = ApiDbLog::getInstance();
        $apiDbLog->type = $type;
        $apiDbLog->message = $message;
        $apiDbLog->tag = $tag;
        $apiDbLog->key = $key;
        $apiDbLog->level = $level;
        $apiDbLog->requestUrl = $requestUrl;
        $apiDbLog->setModelObj()->save();      
    }

}
