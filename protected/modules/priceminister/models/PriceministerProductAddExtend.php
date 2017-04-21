<?php
/**
 * @desc pm刊登
 * @since 2016-12-19
 */
class PriceministerProductAddExtend extends PriceministerModel{

    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_pm_product_add_extend';
    }

}