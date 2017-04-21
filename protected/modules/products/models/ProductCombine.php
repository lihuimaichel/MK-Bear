<?php
/**
 * @package Ueb.modules.products.models
 * 
 * @author Bob <zunfengke@gmail.com>
 */
class ProductCombine extends ProductsModel {    

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
        return 'ueb_product_combine';
    }     
    
    /**
     * Declares attribute labels.
     * @return array
     */
    public function attributeLabels() {
        return array();
    }  	   
    

    /**
     * get combine list by product combine id 
     * @param integer $combineId
     * @return array $result;
     */
    public function getCombineList($combineId) {
        $result = array();
        $list = $this->findAllByAttributes(array( 'product_combine_id' => $combineId));
        $productIds = array();
        
        foreach ($list as $key => $val) {
            $productIds[] = $val['product_id'];
            $result[$key]['product_id'] = $val['product_id'];
            $result[$key]['product_qty'] = $val['product_qty'];
        }
        $skuPairs = UebModel::model('product')->queryPairs('id, sku', array('IN', 'id', $productIds));
        foreach ($result as $key => &$val) {
            $val['sku'] = $skuPairs[$val['product_id']];
        }
        return $result;
    }
    
}