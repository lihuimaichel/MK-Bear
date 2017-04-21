<?php
/**
 * @desc 系统设置
 * @author Gordon
 */
class SysParaSetting extends SystemsModel
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
		return 'ueb_para_setting';
	}
    
	/**
	 * @desc 获取参数值
	 * @param unknown $name
	 * @return NULL
	 */
	public static function getSysPara($name){
		$sysPara = self::model()->find("para_name='{$name}'");
		if($sysPara){
			return $sysPara->para_value;
		}
		return null;
	} 
}