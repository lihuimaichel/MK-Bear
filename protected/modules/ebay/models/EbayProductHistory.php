<?php
/**
 * @desc Ebay刊登
 * @author Gordon
 * @since 2015-07-27
 */
class EbayProductHistory extends EbayModel{
   
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_ebay_product_history';
    }

    /**
     * @desc 把当前listing表所有记录复制到listing历史表
     * @param  $accountID int 账号ID
     * 
     */
    public function setProducttoHistory($accountID = 0){

        $where_sql = '';
        if (!empty($accountID) && $accountID > 0) $where_sql = " where account_id = " .$accountID;

        $sql = "insert into " .self::tableName(). " (listing_id,item_id,category_id,category_id2,category_name,store_category_id,site_id,account_id,sku,sku_online,title,subtitle,view_item_url,gallery_url,quantity,quantity_available,listing_duration,listing_type,buyitnow_price,buyitnow_price_currency,current_price,current_price_currency,shipping_price,shipping_price_currency,total_price,total_price_currency,timestamp,start_time,end_time,time_left,update_sku,item_status,paypal_email,quantity_sold,question_count,is_multiple,watch_count,location,handing_time,gtin_update_status,sale_start_time,sale_end_time,original_price,original_price_currency,is_promote,log_id,confirm_status,bak_time) ";
        $sql .=" select id,item_id,category_id,category_id2,category_name,store_category_id,site_id,account_id,sku,sku_online,title,subtitle,view_item_url,gallery_url,quantity,quantity_available,listing_duration,listing_type,buyitnow_price,buyitnow_price_currency,current_price,current_price_currency,shipping_price,shipping_price_currency,total_price,total_price_currency,timestamp,start_time,end_time,time_left,update_sku,item_status,paypal_email,quantity_sold,question_count,is_multiple,watch_count,location,handing_time,gtin_update_status,sale_start_time,sale_end_time,original_price,original_price_currency,is_promote,log_id,confirm_status,bak_time "; 
        $sql .=" from " .EbayProduct::model()->tableName();
        if ($where_sql) 
        return self::model()->getDbConnection()->createCommand($sql)->query();
    }      
    
  
}