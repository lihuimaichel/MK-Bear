<?php
/**
 * @desc 京东站点
 * @author Gordon
 * @since 2015-08-13
 */
class JdSite extends JdModel{
    
    const SITE_EN = 1;	//英文站
    const SITE_RU = 2;	//俄文站
    
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @return array validation rules for model attributes.
     */
    public function rules(){}
    
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_jd_account';
    }
    
    /**
     * @desc 根据站点获取货币
     * @param int $siteID
     */
    public function getCurrencyBySite($site){
        $config = self::getSiteCurrencyList();
        return isset($config[$site]) ? $config[$site] : '';
    }
    
    /**
     * @desc 获取站点货币
     * @return multitype:string
     */
    public static function getSiteCurrencyList($key = null){
    	$currencyList = array(
        	self::SITE_EN => 'USD',
    	    self::SITE_RU => 'USD',
        );
    	if (!is_null($key) && array_key_exists($key, $currencyList))
        	return $currencyList[$key];
    	return $currencyList;
    }
    
    /**
     * @desc 获取站点简称
     * @param string $key
     */
    public static function getSiteShortName($id = null){
        $list =  array(
            self::SITE_EN => 'en',
            self::SITE_RU => 'ru',
        );
        if( $id && isset($list[$id]) ){
            return $list[$id];
        }else{
            return $list;
        }
    }
    
    /**
     * @desc 获取站点列表
     */
    public static function getSiteList($key = null){
    	$siteList = array(
    		self::SITE_EN => Yii::t('lazada', 'English Site'),
    		self::SITE_SG => Yii::t('lazada', 'Russian Site'),
    	);
      	if (!is_null($key) && array_key_exists($key, $siteList))
      		return $siteList[$key];
      	return $siteList;
    }
    
    /**
     * @desc 获取站点默认语言
     * @param string $siteID
     */
    public static function getLanguageBySite($siteID=null){
        $list = array(
                self::SITE_EN => 'english',
        );
        if($siteID===null){
            return $list;
        }else{
            return isset($list[$siteID]) ? $list[$siteID] : 'english';
        }
    }
}