<?php
/**
 * @desc 菜单Model
 * @author Gordon
 * @since 2015-06-20
 */
class Menu extends SystemsModel{
    
    public $menu_parent_id;
    
	
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
	
	public function getDbKey(){
	    return 'db';
	}

	/**
	 * @desc 表名
	 * @see CActiveRecord::tableName()
	 */
	public function tableName() {
		return 'ueb_menu';
	}
    
	/**
	 * @desc 字段规则
	 * @see CModel::rules()
	 */
    public function rules() {
        $rules = array(         
            array('menu_display_name,menu_status,menu_order,menu_is_menu,menu_parent_id', 'required'), 
            array('menu_order', 'numerical', 'integerOnly'=>true),
            array('menu_url,menu_description', 'length', 'max'=>200),
        );        
        return $rules;
    }
    
    /**
     * @desc 属性标签
     * @see CModel::attributeLabels()
     */
    public function attributeLabels() {
        return array(
            'menu_display_name'     => Yii::t('system', 'Menu Name'),
            'menu_url'              => Yii::t('system', 'Menu URL'),
            'menu_description'      => Yii::t('system', 'Menu Description'),
            'menu_status'           => Yii::t('system', 'Status'),
            'menu_order'            => Yii::t('system', 'Order'),
            'menu_is_menu'          => Yii::t('system', 'Whether it is the menu'),
            'menu_parent_id'        => Yii::t('system', 'The parent menu'),
        );
    }
    
    /**
     * @desc 获取导航菜单
     */
    public function getNavMenu() {  
       // $menuList = $this->findAllByAttributes(array('menu_status' => 1,'menu_level' => 1)); 
    	$menuIds = $this->getDbConnection()->createCommand()->select('id')
    										->from($this->tableName())
    										->where("menu_status=1 and menu_level=1")->queryColumn()	;
       	$accessMenuIds = MenuPrivilege::model()->filterMenuAccessByMenuIds($menuIds);
       	if(empty($accessMenuIds)) return array();
        $menuList = $this->getDbConnection()->createCommand()->from($this->tableName())
        					->where("menu_status=1 and menu_level=1")
        					->andWhere(array("IN", "id", $accessMenuIds))
        					->order('menu_order desc')->queryAll()	;  
        $menuItems = array();
        foreach ($menuList as $key => $val) {           
            if ( parent::checkAccess('menu_'.$val['id']) ) {
                $menuItems[] = array(
                    'label'         => '<span>'.$val['menu_display_name'].'</span>',
                    'url'           => array('/systems/menu/sider/id/'.$val['id']),
                    'active'        => false,
                    'itemOptions'   => array('id' => $val['id'],),
                    'linkOptions'   => array('target' => 'htmlLoad','fillSpace' => 'navlist')
                );
            };           
        }        
        
        return $menuItems;
    }
    
    /**
     * @desc 获取所有Menu
     * @return Object
     */
    public function getAllMenu(){
    	return $this->findAllByAttributes(array('menu_status'=>1));
    }
    
    /**
     * @desc 菜单树
     * @param type $status
     * @param type $isMenu
     * @return array $data
     */
    public static function getTreeList($status = null, $isMenu = false, $forceMenu = false) {
        $selectObj = Yii::app()->db->createCommand() 
			         ->select('*')
			         ->from(self::tableName());	
        if ( $status ) {
            $selectObj->where("menu_status = 1");
            if ( $isMenu ) {
                 $selectObj->andWhere("menu_is_menu = 1");
            }
        } 
        $loginId = Yii::app()->user->id;
        //if(!in_array($loginId, MenuPrivilege::model()->superIds)){
        if(!UserSuperSetting::model()->checkSuperPrivilegeByUserId($loginId)){
        	$menuPrivileges = MenuPrivilege::model()->getMenuPrivilegeByUserId($loginId);
        	if(empty($menuPrivileges)){
        		/* if($forceMenu){
        			$selectObj->andWhere("1=0");
        		} */
        		$selectObj->andWhere("1=0");
        	}else{
        		//@todo 等待开启
        		$selectObj->andWhere("id in (".$menuPrivileges.")");
        		/* if($forceMenu){
        			$selectObj->andWhere("id in (".$menuPrivileges.")");
        		} */
        	}
        	
        }
        
        $menuList = $selectObj
                    ->order("menu_level Desc, menu_order Asc")
			         ->query();               
        $data = array();      
        foreach ($menuList as $key => $val) {          
           if ( isset($data[$val['id']]) ) {
               $submenu = $data[$val['id']]['submenu'];
               unset($data[$val['id']]['submenu']);
               $data[$val['menu_parent_id']]['submenu'][$val['id']] =  array(
                   'id'                 => $val['id'],
                   'name'               => $val['menu_display_name'],
                   'menu_parent_id'     => $val['menu_parent_id'],
                   'menu_url'           => $val['menu_url'],
                   'submenu'            => $submenu);              
           } else {
               $data[$val['menu_parent_id']]['submenu'][$val['id']] = array(
                   'id'                 => $val['id'], 
                   'name'               => $val['menu_display_name'],
                   'menu_parent_id'     => $val['menu_parent_id'],
                   'menu_url'           => $val['menu_url'],
                   'submenu'    => array());  
           }                   
        }       
        return $data[0]['submenu'];
    }
    
    public static function getIndexNavTabId() {
        return self::getIdByUrl('/systems/menu/index');
    } 
    
    public function getIdByUrl($url) {
        $url = trim($url);
        $firstString = substr($url,0,1);
        if($firstString !='/'){
            $url2 = '/'.$url;
        }else{
            $url2 = ltrim($url,'/');
        }
    
        $info = Yii::app()->db->createCommand()
                ->select('id')
                ->from(self::tableName())
                ->where(array('or',"menu_url = '{$url}'","menu_url = '{$url2}'"))
                ->queryRow();
        if (empty($info) ) {
            return null;
        }
        return $info['id'];
    }
   
    /**
     * get the highest frequency menu list
     */
    public static function getHighestFrequencyMenuList() {
        $userId = Yii::app()->user->id;
        $joinTable = UebModel::model('searchCondition')->tableName();
       	$menuList =  Yii::app()->db->createCommand()
        ->select('a.*,b.search_time')
        ->from(self::tableName().' a')
        ->join( $joinTable .' b', "a.`id` = b.search_menu_id")
        ->where("b.search_url != '' AND b.user_id = '{$userId}' AND b.model_name = b.search_type")
        ->order("b.search_count DESC")
        ->limit(15)
        ->queryAll();
        return $menuList;
    }
    
    /**
     * get history menu list
     */
    public static function getHistoryMenuList() {
        $userId = Yii::app()->user->id;
        $joinTable = UebModel::model('searchCondition')->tableName();
        return Yii::app()->db->createCommand()
        ->select('a.*,b.search_time')
        ->from(self::tableName().' a')
        ->join( $joinTable .' b', "a.`id` = b.search_menu_id")
        ->where("b.search_url != '' AND b.user_id = '{$userId}' AND b.model_name = b.search_type")
        ->order("b.search_time DESC")
        ->limit(15)
        ->queryAll();
    }
    
    /**
     * get record log
     */
    public function getRecordLog() {
        $msg = '';
        foreach ( $this->getAttributes() as $key => $val ) {
            if ( ! $this->getIsNewRecord() && $val == $this->beforeSaveInfo[$key] ) {
                continue;
            }
            $label = $this->getAttributeLabel($key);
            if (in_array($key, array( 'id', 'menu_parent_id' ,'menu_level'))) {
                continue;
            }else if ( $key == 'menu_status' ) {
                if ( $this->getIsNewRecord() ) {
                    $status = VHelper::getStatusConfig();
                    $msg .= MHelper::formatInsertFieldLog($label, $status[$val]);
                } else {
                    $status = VHelper::getStatusConfig();
                    $msg .= MHelper::formatUpdateFieldLog($label, $status[$this->beforeSaveInfo[$key]], $status[$val]);
                }
            }else if ( $key == 'menu_is_menu' ) {
                if ( $this->getIsNewRecord() ) {
                    $menu_is_menu = VHelper::getYesOrNoConfig();
                    $msg .= MHelper::formatInsertFieldLog($label, $menu_is_menu[$val]);
                } else {
                    $menu_is_menu = VHelper::getYesOrNoConfig();
                    $msg .= MHelper::formatUpdateFieldLog($label, $menu_is_menu[$this->beforeSaveInfo[$key]], $menu_is_menu[$val]);
                }
            }else {
                if ( $this->getIsNewRecord() ) {
                    $msg .= MHelper::formatInsertFieldLog($label, $val);
                } else {
                    $msg .= MHelper::formatUpdateFieldLog($label, $this->beforeSaveInfo[$key], $val);
                }
            }
        }
        $this->addLogMsg($msg);
    }
}