<?php
/**
 * @desc ebay绑定未销售人员日志记录
 * @author hanxy
 * @since 2016-12-06
 */
class EbayProductSellerRelationLog extends EbayModel{
	
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_ebay_product_seller_relation_log';
    }
}