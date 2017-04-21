<?php
/**
 * @desc Ebay刊登
 * @author Gordon
 * @since 2015-07-27
 */
class EbaySkuCategory extends EbayModel{
   
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_ebay_sku_category';
    }
    
  
}