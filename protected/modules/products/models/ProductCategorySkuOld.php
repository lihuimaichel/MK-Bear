<?php

/**
 * @package Ueb.modules.products.models
 * @author Super
 * @since 2014-12-09
 */
class ProductCategorySkuOld extends ProductsModel {
       
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
        return 'ueb_product_category_sku_old';
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
            'classid'          	=> Yii::t('products', 'Category Chinese Name'),
        );
    } 
    
    /**
     * getClassIdBySku
     * @return classId
     */
    public function getClassIdBySku($sku){
    	$skuInfo = $this->getDbConnection()->createCommand()
    	->select('classid')
    	->from(self::tableName())
    	->where("sku='".$sku."'")
    	->queryRow();
    	if(!empty($skuInfo)){
    		return $skuInfo['classid'];
    	}
    }
    
    /**
     * getSkuByOldCategoryId
     * @return array 
     */
    public function getSkuByOldCategoryId($categoryId){
    	$arrSku = $this->getDbConnection()->createCommand()
    	->select('sku')
    	->from(self::tableName())
    	->where("classid='".$categoryId."'")
    	->queryAll();
    	$list=array();
    	if(isset($arrSku)){
    		foreach ($arrSku as $val){
    			$list[]=$val['sku'];
    		}  		
    	}
    	return $list;
    }
    /**
     * 批量修改产品分类  没有分类insert
     */
    public function updateCategoryBySku($skuArr,$classid){
    	foreach ($skuArr as $sku){
    		$model=new ProductCategorySkuOld();
    		$data=$this->find('sku = :sku',array(':sku' => $sku));
    		if(empty($data)){
    			$model->setAttribute('sku', $sku);
    			$model->setAttribute('classid', $classid);
    			$model->save();
    		}else{
    			$model->updateAll(array('classid' => $classid), 'sku = :sku',array(':sku'=>$sku));
    		}
    		
    	}
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