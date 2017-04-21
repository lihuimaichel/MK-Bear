<?php
/**
 * Controller Helper Class
 * @package Application.components
 * @auther Bob <zunfengke@gmail.com>
 */
class CHelper {

    /**
     * timing profiling log
     * 
     * @return string $timing;
     */
    public static function profilingTimeLog() {
        $url = Yii::app()->request->getUrl();
        $uri = explode("?_=", $url);
        $level = ULogger::LEVEL_SUCCESS;
        $begin = Yii::app()->session['timings_' . session_id(). $uri[1]];
        unset(Yii::app()->session['timings_' . session_id(). $uri[1]]);
        $timing = self::diffSeconds($begin); 
        $systemConfig = SysConfig::getConfigCacheByType('system');
        
        if ( $timing > $systemConfig['profileTimingLimit'] ) {          
            Yii::ulog($timing, $uri[1], 'profile', $level, Yii::app()->request->getPathInfo(), $url);
        }       
    }
    
    /**
     * profiling Time
     * @return string $timing
     */
    public static function profilingTime() {
        $timing = 0;
        $url = Yii::app()->request->getUrl();
        if ( strpos($url, "?_=") !== false ) {
            $uri = explode("?_=", $url);
            $uri[1] = substr($uri[1], 0, 13);
            $begin = Yii::app()->session['timings_' . session_id(). $uri[1]];
            unset(Yii::app()->session['timings_' . session_id(). $uri[1]]);              
            $timing = self::diffSeconds($begin);
        }      
        
        return $timing;
    }
    
    /**
     * diff seconds
     * @param string $beginTime
     * @param string $endTime
     * @return float
     */
    public static function diffSeconds($beginTime, $endTime = null) {
        if ( $endTime === null ) {
            $endTime = microtime();
        } 
        $beginTime = array_sum(preg_split('/[\s]+/', $beginTime));
        $timing = sprintf("%s", number_format(array_sum(preg_split('/[\s]+/', $endTime)) - $beginTime, 3));
        
        return $timing;
    }

}

?>
