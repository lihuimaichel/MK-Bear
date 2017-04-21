<?php
/**
 * @desc 控制面板
 * @author guoll
 * 2015-9-14
 */
class DashBoard extends SystemsModel {

	const DASHBOARD_STATUS_YES  = 1;//启用
	const DASHBOARD_STATUS_NO   = 0;//停用
	
	const DASHBOARD_GLOBAL_YES  = 1;//全局的
	const DASHBOARD_GLOBAL_NO   = 0;//非全局的
	
	const TYPE_URL=1; //URL
	const TYPE_TABLE=2; //数据表
    /**
     * Returns the static model of the specified AR class.
     * @return CActiveRecord the static model class
     */
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    public function getDbKey(){
    	return 'db';
    }
    /**
     * @return string the associated database table name
     */
    public function tableName() {
        return 'ueb_dashboard';
    }

    public function attributeLabels() {
    	$labels = array(
    			'id'                             => Yii::t('system', 'No.'),
    			'dashboard_title'                => Yii::t('system', 'Dashboard Title'),
    			'dashboard_url'                  => Yii::t('system', 'Dashboard Url'),
    			'is_global'                      => Yii::t('system', 'Is Global'),
    			'type'                      	 => Yii::t('system', 'Type'),
    			'status'                      	 => Yii::t('system', 'Status'),
    	);
    	return $labels;
    }
    
    
    /**
     * get search info
     */
    public function search() {
    	$sort = new CSort();
    	$sort->attributes = array(
    			'defaultOrder'  => 'id',
    	);
    	$dataProvider = parent::search(get_class($this), $sort);
    	return $dataProvider;
    }
    
    /**
     * filter search options
     * @return type
     */
    public function filterOptions() {
    	$result = array(
    	);
    	return $result;
    }
    
    /**
     * order field options
     * @return $array
     */
    public function orderFieldOptions() {
    	return array(
    			'dashboard_title','status'
    	);
    }
    
    /**
     * get const array
     * @return array $config
     */
    public function getMyConfig($key='',$val=''){
    	$config = array(
    			'status' => array(
    					self::DASHBOARD_STATUS_YES  => Yii::t('system', 'Dashboard Status YES'),
    					self::DASHBOARD_STATUS_NO   => Yii::t('system', 'Dashboard Status NO'),
    			),
    			'is_global' => array(
    					self::DASHBOARD_GLOBAL_YES  => Yii::t('system', 'Global'),
    					self::DASHBOARD_GLOBAL_NO   => Yii::t('system', 'Not Global'),
    			),
    			'type' => array(
    					self::TYPE_URL  		=> Yii::t('system', 'Url'),
    					self::TYPE_TABLE   		=> Yii::t('system', 'Data Table'),
    			)
    	);
    	if (array_key_exists($key, $config) && $val!=''){
    		return $config[$key][$val];
    	}else if (array_key_exists($key, $config)){
    		return $config[$key];
    	}
    	return $config;
    }
    
    /**
     * get tree dashboard list
     *
     * @return array $data
     */
    public static function getDashboardList() {
    	$menuList = DashBoard::model()->findAll(array(
    			'select'=>'*',
    			'condition'=>'status=:status and is_global=:is_global',
    			'params'=>array(
    					':status'=>self::DASHBOARD_STATUS_YES,  //列出所有启用的
    					':is_global'=>self::DASHBOARD_GLOBAL_NO //列出所有非全局的
    			),
    	));
    	$data = array();
    	
    	foreach ($menuList as $key => $val) {
    		if (  isset($data[$val['id']]) ) {
    			$submenu = $data[$val['id']]['submenu'];
    			unset($data[$val['id']]['submenu']);
    			$data[0]['submenu'][$val['id']] =  array(
    					'id'                 => $val['id'],
    					'name'               => $val['dashboard_title'],
    					'menu_url'           => $val['dashboard_url'],
    					'is_global'         => $val['is_global'],
    					'status'             => $val['status'],
    					'submenu'            => $submenu);
    		} else {
    			$data[0]['submenu'][$val['id']] = array(
    					'id'                 => $val['id'],
    					'name'               => $val['dashboard_title'],
    					'menu_url'           => $val['dashboard_url'],
    					'is_global'         => $val['is_global'],
    					'status'             => $val['status'],
    					'submenu'    => array());
    		}
    	}
    	if(!empty($data)){
    		return $data[0]['submenu'];
    	}
    }
    public static function getIndexNavTabId() {
    	return Menu::model()->getIdByUrl('/systems/dashboard/list');
    }
}