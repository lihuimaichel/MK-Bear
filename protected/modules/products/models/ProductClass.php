<?php

/**
 * @package Ueb.modules.products.models
 * 
 * @author lihy
 */
class ProductClass extends ProductsModel {
	const  DISABLE = 0;//停用
	const  ENABLE  = 1;//启用
    /**
     * Returns the static model of the specified AR class.
     * @return CActiveRecord the static model class
     */
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    /**
     * @return string the associated database table name
     */
    public function tableName() {
        return 'ueb_product_class';
    }
    
    /**
     * @desc 获取分类
     * @return multitype:unknown
     */
    public function getProductClassPair(){
    	$classList = $this->getDbConnection()->createCommand()
    							->from($this->tableName())
    							->select("id, class_name")
    							->where("is_use=1")
    							->queryAll();
    	$newList = array();
    	if($classList){
    		foreach ($classList as $class){
    			$newList[$class['id']] = $class['class_name'];
    		}
    	}
    	return $newList;
    }
    
    /**
     * 得到公司分类
     * @param number $classId
     * @return multitype:Ambigous <>
     */
    public function getCat($classId=0){
    	$catArr = array();
    	$select = '';
    	if($classId){
    		$select = 'id ='.$classId.' AND ';
    	}
    	$cats = $this->getDbConnection()->createCommand()
    	->select('*')
    	->from($this->tableName())
    	->where($select." is_use = 1")
    	//->where($select)
    	->queryAll();
    	if($cats){
    		foreach ($cats as $catInfo){
    			$catArr[$catInfo['id']] = $catInfo['class_name'];
    		}
    	}
    	if($classId){
    		return $catArr[$classId];
    	}
    	return $catArr;
    }
    
    
    //根据分类ID得到公司分类名称
    public function getClassInfoById($id) {
    	if($id == 'totalProduct'){
    		return '合计';
    	}else if($id == 'other_category'){
    		return '未分类';
    	}
    	$id = (array) $id;
    	$data= $this->getDbConnection()
			    	->createCommand()
			    	->select('id,class_name')
			    	->from(self::tableName())
			    	->where(array('IN', 'id', $id))
			    	//	->andWhere("is_use =".self::ENABLE)//启用
			    	->queryRow();
			    	return $data['class_name'];
    }
    
    
    /**
     * 得到公司分类下的SKU数量
     * @param string $classId
     * string $productStatus  4,6
     */
    public function getClassToSkuConut($productStatus=null,$classId = null){
    	$result = array();
    	$where = '';
    	if($productStatus){
    		$where .= 'p.product_status in ('.$productStatus.') and ';
    	}
    	if($classId){
    		$where .= " s.id =$classId and ";
    	}
    	$data= $this->getDbConnection()
    	->createCommand()
    	->select('count(p.id) as sku_count,c.category_id')
    	->from(UebModel::model('Product')->tableName() . ' p')
    	->leftJoin( UebModel::model('ProductClassToOnlineClass')->tableName() . ' c', 'c.online_id = p.online_category_id')
    	->leftJoin( self::tableName()  . ' s', 's.id = c.category_id')
    	->where(" p.product_is_multi != 2 and  $where  s.is_use = ".self::ENABLE)
    	->group('c.category_id')
    	->queryAll();
    	foreach($data as $val){
    		$result[$val['category_id']] = $val['sku_count'];
    	}
    	return $result;
    }
    /**
     * 根据二级品类ID得到公司分类名称
     */
    public function getClassNameByOnlineId($onlineId){
    	$data= $this->getDbConnection()
    	->createCommand()
    	->select('s.class_name')
    	->from(self::tableName() . ' s')
    	->leftJoin( UebModel::model('ProductClassToOnlineClass')->tableName() . ' c', 's.id = c.category_id')
    	->where(" c.online_id ='{$onlineId}' and s.is_use = ".self::ENABLE)
    	->queryRow();
    	return $data['class_name'];
    }
}  
?>