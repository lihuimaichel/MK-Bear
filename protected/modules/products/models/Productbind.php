<?php

class Productbind extends ProductsModel
{
	
	/**
	 * Returns the static model of the specified AR class.
	 * @return CActiveRecord the static model class
	 */
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName() {
		return 'ueb_product_binding';
	}
    
    
    /**
     * 根据基sku获取绑定的sku列表
     */
    public function getBindSkuByBaseSku($sku) {
    	$result = array();
    	$result =  $this->getDbConnection()->createCommand()
    	->select('id,sku,sku_binding,num_binding,type_binding')
    	->from(self::tableName())
    	->where('sku=:sku',array(':sku'=>$sku))
    	->order('id desc')
    	->queryAll();
    	return $result;
    }      
    
}