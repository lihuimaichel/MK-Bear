<?php
/**
 * @desc 系统设置
 * @author Gordon
 */
class SysConfig extends SystemsModel
{	
    public $type = null;
    
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
	
	/**
	 * @desc 切换数据库
	 * @see SystemsModel::getDbKey()
	 */
	public function getDbKey() {
	    return 'db_oms_system';
	}
	
	/**
	 * @desc 表名
	 * @see CActiveRecord::tableName()
	 */
	public function tableName() {
		return 'ueb_sys_config';
	}
    
	/**
	 * @desc 设置类型
	 * @param string $type
	 */
    public function setType($type) {
        $this->type = $type;
        return $this;
    }
    
    /**
     * @desc 获取类型
     * @throws CException
     * @return string
     */
    public function getType() {
        if ( is_null($this->type) ) {
            throw new CException('Config type is not allow empty');
        }
        return $this->type;
    }     
    
    /**
     * @desc 根据类型获取配置
     * @param string $type
     * @return array $data
     */
    public static function getConfigCacheByType($type) {
        $key = 'sysconfig'.$type;
        $data = Yii::app()->cache->get($key); 
        if ( $data === false )
        {
            $data = self::getPairByType($type);
            Yii::app()->cache->set($key, $data, 60*60*24);
        } 
        return $data;
    }
    
    /**
     * @desc 根据key值获取系统配置
     * @param string $key
     * @return string|boolean
     */
    public static function getConfigByKey($key){
    	$info = SysConfig::model()->find('config_key = "'.$key.'"');
    	if( !$info ){
    		return false;
    	}
    	return $info->config_value;
    }
    
    /**
     * @desc 根据种类获取系统配置
     * @param string $type
     */
    public static function getPairByType($type) {
        $list = Yii::app()->db->createCommand()
                ->select('*')
                ->from(self::model()->tableName())
                ->where('config_type = "'.$type.'"')
                ->query();
        $data = array();
        foreach ($list as $val) {
            $data[$val['config_key']] = $val['config_value'];
        }
        return $data;
    }
}