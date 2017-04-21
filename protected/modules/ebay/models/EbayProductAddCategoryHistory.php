<?php
/**
 * @desc Ebay刊登分类
 * @author lihy
 * @since 2016-03-28
 */
class EbayProductAddCategoryHistory extends EbayModel{
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_ebay_product_add_category_history';
    }

    /**
     * @desc 获取历史分类列表
     * @param unknown $siteID
     * @param unknown $sku
     * @return multitype:unknown
     */
    public function getHistoryCategoryListPairsBySKU($siteID, $sku){
    	$categoryList = $this->getDbConnection()->createCommand()
    							->from(self::tableName())
    							->select('category_id, category_name')
    							->where("sku='{$sku}' and site_id={$siteID}")
    							->queryAll();
    	$newCategoryList = array();
    	if($categoryList){
    		foreach ($categoryList as $category){
    			$newCategoryList[$category['category_id']] = $category['category_name'];
    		}
    	}
    	return $newCategoryList;
    }
}