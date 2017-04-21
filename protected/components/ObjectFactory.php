<?PHP
/**
 * ObjectFactory is factory class to create metadata based objects
 *
 * @package   @package Application.components
 * @author    Bob <zunfengke@gmail.com>
 * @access    public
 */
class ObjectFactory {

    /**
     * Internal array for cache MetaObject
     * @var array
     */
    protected static $_objsRefMap = array();

    public function __construct() {}

    /**
     * Get a metadata based object instance.
     *
     * @param string $objectName name of object that want to get
     * @return object
     */
    public static function getObject($objectName) {       
        if (array_key_exists($objectName, self::$_objsRefMap)) {
            return self::$_objsRefMap[$objectName];
        }
        
        $obj = self::createObject($objectName);
        if ($obj) {
            self::$_objsRefMap[$objectName] = $obj;
        }           

        return $obj;
    }

    /**
     *
     * @param string $objName name of object will be create    
     * @return object
     */
    public static function createObject($objName) {        
        $obj = self::constructObject($objName);

        return $obj;
    }

    public function setObject($objName, $obj) {
        self::$_objsRefMap[$objName] = $obj;
    }

    /**
     * Get all object from the internal object array (object cache)
     *
     * @return array array of object
     */
    public static function getAllObjects() {
        return self::_objsRefMap;
    }

    /**
     * Construct an instance of an object
     *
     * @param string $objName object name    
     * @return object
     */
    protected function constructObject($objName) {       
        $dotPos = strrpos($objName, ".");
        $package = $dotPos > 0 ? substr($objName, 0, $dotPos) : null;       
        $class = $dotPos > 0 ? substr($objName, $dotPos + 1) : $objName;
       
        if (!class_exists($class, false)) {           
            $classFile= self::getFileWithPath($class, $package);
             
              if (! $classFile)
              {
                    if ($package)
                        trigger_error("Cannot find the class with name as $package.$class", E_USER_ERROR);
                    else
                        trigger_error("Cannot find the class with name as $class of $objName", E_USER_ERROR);
                    exit();
              }
              include_once($classFile); 
        }
        $objRef = new $class();     
        if (class_exists($class)) {           
            $objRef = new $class();
            if ($objRef) {
                return $objRef;
            }
        }

        return null;
    }
    
    /**
     * get file with path
     *
     * @param string $className
     * @return string php file path
     * */
    public static function getFileWithPath($className, $packageName = "")
    {       
        if (! $className) return;     
        $classFile = $className.'.php';
        if ( empty($packageName) ) return $classFile;
        
        $classFile = str_replace(".", DS, $packageName). DS . $classFile;
        
        return $classFile;      
    }

}
