<?php
/**
 * @desc Amazon站点
 * @since 2016-05-05
 */
class AmazonSite {

    /**
     * @desc 获取站点列表
     * @return array
     */
    public static function getSiteAllList(){
    	return array(
    	        '0'  => array('CountryCode'=>'amazon', 'CountryName'=>'亚马逊', 'MarketplaceId' => 'EX026DKIKX0DERWL'),
                '1'  => array('CountryCode'=>'us','CountryName'=>'美国','MarketplaceId'=>'ATVPDKIKX0DER'),
                '2'  => array('CountryCode'=>'ca','CountryName'=>'加拿大','MarketplaceId'=>'A2EUQ1WTGCTBG2'),
                '3'  => array('CountryCode'=>'de','CountryName'=>'德国','MarketplaceId'=>'A1PA6795UKMFR9'),
                '4'  => array('CountryCode'=>'es','CountryName'=>'西班牙','MarketplaceId'=>'A1RKKUPIHCS9HS'),
                '5'  => array('CountryCode'=>'fr','CountryName'=>'法国','MarketplaceId'=>'A13V1IB3VIYZZH'),
                '6'  => array('CountryCode'=>'in','CountryName'=>'印度','MarketplaceId'=>'A21TJRUUN4KGV'),
                '7'  => array('CountryCode'=>'it','CountryName'=>'意大利','MarketplaceId'=>'APJ6JRA9NG5V4'),
                '8'  => array('CountryCode'=>'uk','CountryName'=>'英国','MarketplaceId'=>'A1F83G8C2ARO7P'),
                '9'  => array('CountryCode'=>'jp','CountryName'=>'日本','MarketplaceId'=>'A1VC38T7YXB528'),
                '10' => array('CountryCode'=>'cn','CountryName'=>'中国','MarketplaceId'=>'AAHKV2X7AFYLW')           
    	);
    }
   
    /**
     * @desc 获取站点键值对
     * @return array
     */
    public static function getSiteList(){
        $simplelist = array();
        $siteList = self::getSiteAllList();
        foreach ($siteList as $key => $val){
            $simplelist[$key] = $val['CountryCode'];
        }
        return $simplelist;
    }    

    /**
     * @desc 获取站点id列表
     * @return array
     */
    public static function getSiteIDs(){
        $siteList = self::getSiteList();
        $siteIDs = array_flip($siteList);
        return $siteIDs;
    }

    /**
     * @desc 根据站点名称获取站点ID
     * @param unknown $sitename
     * @return Ambigous <>|number
     */
    public static function getSiteIdByName($sitename){
    	$siteIDs = self::getSiteIDs();
    	if(isset($siteIDs[$sitename])){
    		return $siteIDs[$sitename];
    	}else{
    		return 0;
    	}
    }
    
    /**
     * @desc 获取站点名称
     * @param unknown $siteid
     * @return Ambigous <string>
     */
    public static function getSiteName($siteid){
    	$siteList = self::getSiteList();
    	return $siteList[$siteid];
    }

    /**
     * @desc 根据站点代码获取MarketplaceId 
     * @param string $countrycode
     * @return string
     */
    public static function getMarketplaceIdByName($countrycode){
        if (empty($countrycode)) return false;
        $siteList = self::getSiteAllList();
        foreach ($siteList as $val){
            if ($val['CountryCode'] = trim(strtolower($countrycode))){
                return $val['MarketplaceId'];
            }
        }
        return false;
    }  

    /**
     * @desc 获取亚马逊产品标识码类型
     * @return array
     */
    public static function getSiteProductType(){
        return array(
                '1' => 'EAN',       
                '2' => 'GCID',
                '3' => 'GTIN',
                '4' => 'UPC'
        );
    } 

    /**
     * @desc 根据ID值获对应的亚马逊产品标识码名称
     * @param $id 
     * @return array
     */
    public static function getSiteProductTypeByID($id){
        $list = self::getSiteProductType();
        return $list[$id];
    }         

    /**
     * @desc 刊登上传类型和刊登子SKU表字段影射表
     * @return array
     */
    public static function getPublishToUploadFieldsMap(){
        return array(
                '_POST_PRODUCT_DATA_'                => '1',                       
                '_POST_PRODUCT_PRICING_DATA_'        => '2',
                '_POST_INVENTORY_AVAILABILITY_DATA_' => '3',
                '_POST_PRODUCT_IMAGE_DATA_'          => '4',
                '_POST_PRODUCT_RELATIONSHIP_DATA_'   => '5',
                '_POST_PRODUCT_OVERRIDES_DATA_'      => '6',
        );
    }       
    
}