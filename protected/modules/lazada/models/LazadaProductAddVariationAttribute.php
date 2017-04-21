<?php
/**
 * @desc Lazada刊登多属性详细属性
 * @author Liujie
 * @since 2015-08-20
 */
class LazadaProductAddVariationAttribute extends LazadaModel{
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @return array validation rules for model variationAttribute.
     */
    public function rules(){}
    
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_lazada_product_add_variation_attribute';
    }
    
    /**
     * @desc 多属性详细属性  存储
     */
    public function saveRecord($variationID, $name, $value, $addID){

         $flag = $this->dbConnection->createCommand()->insert(self::tableName(), array(
            'variation_id'  => $variationID,
            'name'          => trim(addslashes($name)),
            'value'         => trim(addslashes($value)),
            'add_id'        => $addID,
        ));
        if( $flag ){
            return $this->dbConnection->getLastInsertID();
        }else{
            return false;
        }
    }
    
    /**
     * @desc 根据variation_id获取属性值
     */
    public function getAttributeByVariationID($variationID){
        //$addID = 29;
        return $this->dbConnection->createCommand()
            ->select('*')
            ->from(self::tableName())
            ->where('variation_id = '.$variationID)
            ->queryRow();
    }
}