<?php
/**
 * @desc Ebay产品管理
 * @author Gordon
 * @since 2015-07-31
 */
class EbayProductShip extends EbayModel{
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_ebay_product_shipping';
    }
    
    /**
     * @desc 保存产品的多属性数据
     * @param Array $params
     */

    public function saveProductShip($params){
    	$tableName = self::tableName();
    	$flag = $this->dbConnection->createCommand()->insert($tableName, $params);
    	if($flag) {
    		return $this->dbConnection->getLastInsertID();
    	}
    	return false;
    }
}