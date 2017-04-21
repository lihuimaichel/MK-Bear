<?php
/**
 * @desc Ebay刊登
 * @author Gordon
 * @since 2015-07-27
 */
class EbayProductAddVariation extends EbayModel{
	
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_ebay_product_add_variation';
    }
    
    /**
     * @desc 根据ADD ID获取产品添加信息
     * @param unknown $addID
     */
    public function getEbayProductAddVariationJoinEbayProductAddByAddID($addID, $select = "v.*"){
    	return $this->getDbConnection()
	    			->createCommand()
	    			->from($this->tableName() . ' as v')
	    			->select($select)
	    			->leftJoin(EbayProductAdd::model()->tableName() . ' as p', 'p.id=v.add_id')
	    			->where('p.id='.$addID)
    				->queryAll();	
    }
    
    /**
     * @desc 根据addID获取
     * @param unknown $addID
     * @return mixed
     */
    public function getEbayProductAddVariationListByAddID($addID){
    	return $this->getDbConnection()
			    	->createCommand()
			    	->from($this->tableName())
			    	->select("*")
			    	->where('add_id='.$addID)
			    	->queryAll();
    }
}