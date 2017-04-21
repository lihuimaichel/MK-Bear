<?php
/**
 * @package Ueb.modules.products.models
 * 
 * @author Bob <zunfengke@gmail.com>
 */
class ProductCategoryAttribute extends ProductsModel {   

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
        return 'ueb_product_category_attribute';
    }
    
    /**
	 * @return array relational rules.
	 */
	public function relations() {
        return array();       
    }
    
    
    /**
     * get attribute list by category id
     * @param Integer $categoryId
     * @return array $data
     */
    public function getAttributeList($categoryId) {      
        $data = array();
        $joinTable = UebModel::model('productAttribute')->tableName();      
        $list = $this->getDbConnection()->createCommand()
                ->select('ca.attribute_is_required, ca.attribute_sort, a.*')
                ->from(self::tableName() . ' ca')
                ->join($joinTable . ' a', "ca.`attribute_id` = a.id")
                ->where("ca.category_id = '{$categoryId}'")
                ->order('ca.attribute_sort')                 
                ->queryAll(); 
        foreach ($list as $key => $val) {
            $data[$val['id']] = $val;
        }         
        return $data;
    }   
    
}
