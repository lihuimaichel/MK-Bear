<?php

class AuthItemChild extends UsersModel
{	
	/**
	 * Returns the static model of the specified AR class.
	 * @return CActiveRecord the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'ueb_auth_item_child';
	}	
	/**
	 * 
	 * @param array $parent
	 * @return multitype:
	 */
	public function getChildRoleByParent($parent){
		$result = array();
		$obj = Yii::app()->db->createCommand()
              ->select('child')
              ->from(self::tableName())
              ->where(array('in', 'parent', $parent))
              ->andwhere(array('not like', 'child', array('menu_%','resource_%')));
        $list = $obj->queryAll();
        if($list){
        	foreach ($list as $val){
        		$result[] = $val['child'];
        	}
        }
		return $result;
	}
    
    public static function getChildByParent($parent) {
        return Yii::app()->db->createCommand()
              ->select('child')
              ->from(self::tableName())
              ->where(" parent = '{$parent}'")
              ->queryColumn();
    }
    
    public static function getAll() {
        return Yii::app()->db->createCommand()
              ->select('child,parent')
              ->from(self::tableName())           
              ->queryAll();    
    }
    
    /**
     * get child parents
     * 
     * @param type $child
     * @return array $data
     */
    public static function getParentsByChild($child) {
        $data = array();
        $parent = self::getParentByChild($child);        
        while(! empty($parent) ) {
            $data[] = $parent[0];          
            $parent = self::getParentByChild($parent[0]);
        }
        
        return $data;
    }

    public static function getParentByChild($child) {      
        return Yii::app()->db->createCommand()
              ->select('parent')
              ->from(self::tableName())
              ->where(" child = '{$child}'")
              ->queryColumn();
    }
    
    /**
     * @desc 取消菜单的资源绑定
     * @param int $menuId
     */
    public function cancelMenuAssign($menuId=0){
    	$where = '';
    	if( $menuId > 0 ){
    		$where .= ' AND parent = "menu_'.$menuId.'"';
    	}else{
    		$where .= ' AND parent LIKE "menu_%"';
    	}
    	return $this->deleteAll('child LIKE "resource_%"'.$where);
    }
}