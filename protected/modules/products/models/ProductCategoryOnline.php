<?php

/**
 * @package Ueb.modules.products.models
 * 
 * @author lihy
 */
class ProductCategoryOnline extends ProductsModel {
    
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
        return 'ueb_product_category_online';
    }
    

    /**
     * @desc 获取在线产品分类
     * @param unknown $classId
     * @return multitype:unknown
     */
    public function getProductOnlineCategoryPairByClassId($classId){
    	if(empty($classId)) return array();
    	$result = $this->getDbConnection()->createCommand()
    							->from($this->tableName() . " as t")
    							->select("t.cate_id2, t.cate_name2")
    							->join(ProductClassToOnlineClass::model()->tableName()." as c", "t.cate_id2=c.online_id")
    							->where("c.category_id=".$classId)
    							->queryAll();	
    	$newList = array();
    	if($result){
    		foreach ($result as $cate){
    			$newList[$cate['cate_id2']] = $cate['cate_name2'];
    		}
    	}	
    	return $newList;		
    }
    
    /**
     * @desc 根据产品分类ID，获取全部的品类id
     * @param unknown $classId
     * @return multitype:|unknown
     */
    public function getProductOnlineCategoryIDsClassId($classId){
    	if(empty($classId)) return array();
    	$result = $this->getDbConnection()->createCommand()
				    	->from(ProductClassToOnlineClass::model()->tableName())
				    	->select("online_id")
				    	->where("category_id=".$classId)
				    	->queryColumn();
    	return $result;
    }
    
    
    /**
     * 根据公司分类得到一级产品品类数据
     * @param number $classId
     */
    public function getcategoryOneByClassId($classId){
    	$catArr = array();
    	if(empty($classId)) return $catArr;
    	
    	$cats = $this->getDbConnection()->createCommand()
			    	->select('t.*')
			    	->from($this->tableName(). ' t')
			    	->leftJoin('ueb_product_class_to_online_class c', 'c.online_id = t.cate_id2')
			    	->where('c.category_id ='.$classId)
			    	->queryAll();
    	$cateId1 = array();
    	foreach ($cats as $catInfo){
    		if(!in_array($catInfo['cate_id1'],$cateId1)){
    			$catArr[$catInfo['cate_id1']] = $catInfo['cate_name1'];
    			$cateId1[] = $catInfo['cate_id1'];
    		}
    	}
    	return $catArr;
    }


    /**
     * 得到在线分类
     * @param number $classId 二级分类
     * @return multitype:Ambigous <>
     */
    public function getCat($classId=0){
        $catArr = array();
        $select = '';
        if($classId){
            $select = 'cate_id2 ='.$classId;
        }
        $cats = $this->getDbConnection()->createCommand()
        ->select('*')
        ->from($this->tableName())
        ->where($select)
        ->queryAll();
        foreach ($cats as $catInfo){
            $catArr[$catInfo['cate_id2']] = $catInfo['cate_name2'];
        }
        return $catArr;
    }
}  
?>