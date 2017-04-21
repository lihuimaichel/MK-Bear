<?php
/**
 * @desc pm刊登
 * @since 2016-12-19
 */
class PriceministerProductAddVariation extends PriceministerModel{

    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_pm_product_add_variation';
    }
    /**
     * @desc 根据主键id更新variant数据
     * @param unknown $id
     * @param unknown $data
     * @return boolean|Ambigous <boolean, unknown, number>
     */
    public function updateProductVariantAddInfoByPk($id, $data){
        if(!$id || !$data) return false;
        return self::model()->updateByPk($id, $data);
    }

}