<?PHP
/**
 * Config factory is factory class to get config objects
 *
 * @package   @package Application.components
 * @author    Bob <zunfengke@gmail.com>
 * @access    public
 */
class ConfigFactory {

    /**
     * Internal array for cache
     * @var array
     */
    protected static $_configMap = array();  

    /**
     * facade, get config by the filename
     * 
     * @param string $fileName
     * @return mixd $config
     */
    public static function getConfig($fileName) {       
        if (array_key_exists($fileName, self::$_configMap)) {
            return self::$_configMap[$fileName];
        }
        
        $config = self::createConfig($fileName);
        if ( $config ) {
            self::$_configMap[$fileName] = $config;
        }           

        return $config;
    }

    /**
     * create config 
     * 
     * @param string $fileName name of object will be create    
     * @return mixd $config
     */
    public static function createConfig($fileName) {        
        return self::constructConfig($fileName);
    }

    /**
     *  set config
     * 
     * @param string $fileName
     * @param mixd $config
     */
    public function setConfig($fileName, $config) {
        self::$_configMap[$fileName] = $config;
    }

    /**
     * Get all configs from the internal object array (object cache)
     *
     * @return array array of object
     */
    public static function getAllConfigs() {
        return self::$_configMap;
    }

    /**
     * Construct an instance of an object
     *
     * @param string $objName object name    
     * @return mixd
     */
    protected static function constructConfig($fileName) { 
        $config = null;
        list($fileName, $type) = self::getFileInfoByFileName($fileName);
        $file = self::getFileWithFileNameAndType($fileName, $type);
       
        switch ($type) {
            case 'php':
                $config =  include_once $file; 
                break;    
            case 'ini':
                $config = parse_ini_file($file);
                break;
        }

        return $config;
    }
    
    /**
     * get file info
     * 
     * @param string $fileName
     * @return array
     */
    public static function getFileInfoByFileName($fileName) {
        $dotPos = strrpos($fileName, ".");
        $type = 'php';
        if ( $dotPos > 0 ) {
            $type = substr($fileName, $dotPos + 2);
            $fileName = substr($fileName, 0, $dotPos);
        }
        
        return array($fileName, $type);
    }


    /**
     * get file with file name
     *
     * @param string $fileName
     * @param string $type The file suffix
     * @return string php file path
     * */
    public static function getFileWithFileNameAndType($fileName, $type = 'php') {       
        if (! $fileName) return;     
        $file = CONF_PATH . $fileName .'.'. $type;
        if (! file_exists($file) ) {
            throw new Exception( "No there is no the $file.$type file in a configuration file directory");
        }              
        
        return $file;      
    }

}
