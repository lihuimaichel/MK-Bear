<?php
/**
 * @desc SuggestProductOther model
 * @author wx 
 */
class SuggestProductOther extends ProductsModel {
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_suggest_product_other';
    }
    
    /**
     * @desc 查找一条item
     * @author wx
     */
    public function getItemByItemId( $itemId ){
    	if(empty($itemId)) return false;
    	$ret = $this->dbConnection->createCommand()
    			->select( '*' )
    			->from( self::tableName() )
    			->where( 'item_id = "'.$itemId.'"' )
    			->queryRow();
    			
    	return $ret;
    }
    
	
}