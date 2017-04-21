<?php
class CPrivApplication extends CComponent{
    
    public $_module = null;
    
    /**@var 权限配置 */
    public $_priv = null;
    
    /**@var 记录模块权限 */
    public static $privMap = array();
    
    public function init(){}
    
    /**
     * @desc 设置权限
     * @param array $privArr
     */
    public function setPriv($privArr){
        if($this->_module!==null){
            if(!isset(self::$privMap[$this->_module])){
                self::$privMap[$this->_module] = $privArr;
            }
        }else{
            throw new CException('Module Name Must Be Set');
        }
        
    }
    
    /**
     * @desc 设置模块
     * @param string $module
     * @throws CException
     */
    public function setModule($module){
        if($module){
            $module = ucfirst($module);
            $filePath = Yii::app()->basePath.'/modules/'.$module.'/components/'.$module.'Priv.php';
            if(file_exists($filePath)){
                include_once $filePath;
                $className = $module.'Priv';
                if( class_exists($className) ){
                    $this->_priv = new $className();
                    $this->_priv->init();
                }
            }
        }else{
            throw new CException('Module Name Must Be Set');
        }
    }
    
    /**
     * @desc 获取模块权限配置
     * @return Ambigous <multitype:, array>
     */
    public function getPriv(){
        return self::$privMap;
    }
    
    /**
     * @desc 获取所有权限配置
     */
    public function getAllPriv(){
        $modules = array_keys(Yii::app()->modules);
        foreach($modules as $module){
            $this->setModule($module);
        }
        $allPriv = $this->getPriv();
        $privResource = array();
        foreach($allPriv as $mod=>$_v1){
            foreach($_v1 as $controller=>$_v2){
                foreach($_v2 as $item){
                    $privResource[] = 'resource_'.$mod.'_'.$controller.'_'.$item['action'];
                }
            }
        }
        return $privResource;
    }
    
    /**
     * @desc 检测权限
     * @param string $resourceId
     * @param int $userId
     */
    public function checkAccess($resourceId, $userId){
        $privResource = $this->getAllPriv();
        if(in_array($resourceId, $privResource)){
            $auth = Yii::app()->authManager;
            return $auth->checkAccess($resourceId, $userId);
        }else{
            return true;
        }
    }
}