<?php
/**
 * @desc Ebay sku自动匹配
 * @author lihy
 * @since 2016-06-24
 */
class EbaySkuSuggestCategory extends EbayModel{
   
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_ebay_sku_suggest_category';
    }
    
  
    public function getSuggestCategoryBySkuAndSite($sku, $site){
    	$expireTime = date("Y-m-d", time()-25*24*3600);
    	$condition = "sku like '{$sku}%' and site_id=$site and last_time>='{$expireTime}'";
    	$suggestCategory = $this->getDbConnection()->createCommand()
    							->from(self::tableName())
    							->where($condition)
    							->queryRow();
    	return $suggestCategory;
    }
    
    public function saveSuggestCategory($data){
    	$sku = $data['sku'];
    	$siteId = $data['site_id'];
    	$checkExists = $this->find("sku='{$sku}' and site_id=$siteId");
    	if($checkExists){
    		return $this->getDbConnection()->createCommand()->update(self::tableName(), $data, "id=".$checkExists['id']);
    	}else{
    		return $this->getDbConnection()->createCommand()->insert(self::tableName(), $data);
    	}
    }
    
}