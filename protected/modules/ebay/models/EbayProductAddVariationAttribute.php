<?php
/**
 * @desc Ebay刊登 子sku属性model
 * @authorlihy
 * @since 2016-03-31
 */
class EbayProductAddVariationAttribute extends EbayModel{
	
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_ebay_product_add_variation_attribute';
    }
    
    /**
     * @desc 获取对应的属性列表
     * @param unknown $conditions
     * @param unknown $params
     * @param string $select
     * @return mixed
     */
    public function getVariationAttributeJoinVariationByWhere($conditions, $params = array(), $select = 'a.*,b.son_sku,b.variation_price,b.son_seller_sku,b.variation_discount_price'){
    	$variationsAttributes = $this->getDbConnection()->createCommand()
    									->from($this->tableName() . ' as a')
    									->select($select)
    									->leftJoin(EbayProductAddVariation::model()->tableName() . ' as b', 'a.variation_id=b.id')
    									->where($conditions, $params)
    									->queryAll();
    	return $variationsAttributes;
    }
    
    public function getVariationAttributeListByVariationId($variationId, $addId = 0){
    	$variationsAttributes = $this->getDbConnection()->createCommand()
						    	->from($this->tableName() . ' as a')
						    	->select("*")
						    	->where("variation_id=:variation_id", array(':variation_id'=>$variationId))
						    	->andWhere($addId>0?"add_id={$addId}" : "1")
						    	->queryAll();
    	return $variationsAttributes;
    }
}