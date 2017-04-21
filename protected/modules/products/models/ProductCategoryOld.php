<?php
/**
 * @package Ueb.modules.products.models
 * @author Super
 * @since 2014-12-09
 */
class ProductCategoryOld extends ProductsModel {

	const CATEGORY_STATE=1;
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
        return 'ueb_product_category_old';
    }
    
    public function rules() {
        $rules = array(); 
        
        return $rules;
    }    
    
    /**
     * Declares attribute labels.
     * @return array
     */
    public function attributeLabels() {
        return array(
            'cls_name'          	=> Yii::t('products', 'Category Chinese Name'),
            'en_name'          	 	=> Yii::t('products', 'Category English Name'),
            'cls_code'             	=> Yii::t('products', 'Category Code'), 
            'use_state'           	=> Yii::t('system', 'Status'),
        );
    }
     
    public function ProductCategory($id=null){
    	static $categoryArr= array();
    	$categoryData=$this->getDbConnection()->createCommand()
    	->select('id,cls_name')
    	->from(self::tableName())
    	->where("use_state='".self::CATEGORY_STATE."'")
    	->queryAll();
    	foreach ($categoryData as $val){
    		$categoryArr[$val['id']]=$val['cls_name'];
    	}
    	if($id!=null){
    		return $categoryArr[$id];
    	}else{
    		return $categoryArr;
    	}
    	
    }
    
   /**
    * 
    * @param int $classId  product category id
    * @return array $catArr  array(
    * 							1 => '其他(默认)',
    * 							...............
    * 							...............
    * 							20 => '安防产品',
    * 						)
    * @author Super
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
    				 ->where($select." use_state = 1")
    				 ->queryAll();
    	foreach ($cats as $catInfo){
    		$catArr[$catInfo['id']] = $catInfo['cls_name'];
    	}
    	return $catArr;
    }
    
    public function getCatNameCnOrEn($classId=0){
    	$catArr = array();
    	$select = '';
    	if($classId){
    		$select = 'id ='.$classId.' AND ';
    	}
    	$cats = $this->getDbConnection()->createCommand()
    	->select('id,cls_name,en_name')
    	->from($this->tableName())
    	->where($select." use_state = 1")
    	->queryAll();
    	foreach ($cats as $catInfo){
    		$catArr[$catInfo['id']]['cn'] = $catInfo['cls_name'];
    		$catArr[$catInfo['id']]['en'] = $catInfo['en_name'];
    	}
    	return $catArr;
    }
    
    public function getIdByClsname($clsname){
    	$id = $this->getDbConnection()->createCommand()
	    	->select('id')
	    	->from(self::tableName())
	    	->where(array('IN','cls_name',$clsname))
	    	->queryRow();
    	return $id;
    }
    
    /**
     * @desc 根据条件查询指定字段
     */
    public function getProductCategoryByCondition( $condition,$field = '*' ){
    	$condition = empty($condition)?'1=1':$condition;
    	$ret = $this->dbConnection->createCommand()
		    	->select( $field )
		    	->from( $this->tableName() )
		    	->where( $condition )
		    	->queryAll();
    	return $ret;
    }
    
}