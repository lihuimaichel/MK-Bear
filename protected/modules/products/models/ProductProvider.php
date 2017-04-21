<?php

/**
 * @package Ueb.modules.products.models
 */
class ProductProvider extends ProductsModel {

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
        return 'ueb_product_provider';
    }
    
    
    /**
     * get provider id by product id
     * @param integer $productId
     */
    public function getProviderIdByProductId($productId) {              
        $result = $this->getDbConnection()->createCommand()
                ->select('provider_id')
                ->from(self::tableName())
                ->where(" product_id = '{$productId}'")
                ->queryColumn(); 
      
       return $result;         
    } 


    /**
     * 获取sku绑定供应商
     */
    public function getBindSkuProvider($id,$providerId =null){  
    	$where = '';
    	if($providerId){
    		$where =" and a.provider_id = $providerId";
    	} 	
    	$result = $this->getDbConnection()->createCommand()
                ->select("b.provider_code")
    			->from($this->tableName().' a')    			
    			->leftjoin('ueb_purchase.'.UebModel::model('Provider')->tableName().' b','a.provider_id = b.id')
    			->where("a.product_id ='{$id}' $where")   	
    			->queryRow();
		if($result){
			return $result['provider_code'];
		}else{
			return '-';
		}
    }    
}