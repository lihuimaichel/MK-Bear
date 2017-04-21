<?php
/**
 * Config factory is factory class to get config objects
 *
 * @package Application.components
 * @author    Bob <zunfengke@gmail.com>
 * @access    public
 */
class Configuration {    
    
    /**
     *  get configuration
     * 
     * @return array 
     */
    public static function getConfiguration() {
        return Env::getConfig();
    }
    
    /**
     * components config
     * 
     * @return array
     */
    public static function getComponentsConfig() {
        $configuration = self::getConfiguration();
        
        return $configuration['components'];
    }
    
    /**
     * get dbs config
     * 
     * @return array
     */
    public static function getDbsConfig() {
        $result = array();
        $componentsConfig = self::getComponentsConfig();
        foreach ($componentsConfig as $key => $val) {
            if ( $key == 'db' || substr($key, 0, 3) == 'db_') {
                $result[$key] = $val;
            }
        }
        
        return $result;
    }
    
    /**
     * get db names config
     * 
     * @return array
     */
    public static function getDbNamesConfig() {
        $result = array();
        $dbsConfig = self::getDbsConfig();
        foreach ($dbsConfig as $key => $val ) {          
           $connectArr = explode(";", $val['connectionString']); 
           $dbNameArr = explode("=", $connectArr[2]);//update by ethan 2014.07.16
           $result[$key] = $dbNameArr[1];
        }
        
        return $result;
    }
    
}
?>
