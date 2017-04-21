<?php
abstract class WebsiteModelAbstract extends UebModel {
	
	//run model 正式环境改为false
	const DEBUG_MODEL = TRUE;
	
	//Magento function and variables
	const UEB_PRODUCT_IS_SIMPLE = 0;
	const UEB_PRODUCT_IS_MULTI_SINGLE = 1;
	const UEB_PRODUCT_IS_MULTI_CONFIG = 2;
	//定义
	const PRODUCT_TYPE_SIMPLE = 'simple';
	const PRODUCT_TYPE_CONFIGURABLE = 'configurable';
	
	const PRODUCT_STORE_UPLOAD_TO_WEBSITE_NOT = 0 ; //产品未刊登网站
	const PRODUCT_STORE_UPLOAD_TO_WEBSITE = 1 ; //产品已刊登网站
	
	const PRODUCT_STORE_STATUS_ENABLED = '1';	//产品可用 在线
	const PRODUCT_STORE_STATUS_DISABLED = '2';	//产品不可用 下线
	const PRODUCT_STORE_STATUS_IS_IN_STOCK_YES = '1';	//网站上有库存
	const PRODUCT_STORE_STATUS_IS_IN_STOCK_NO = '0';	//网站上无库存
	const PRODUCT_STORE_STATUS_INFRINGEMENT = '1';	//产品侵权
	const PRODUCT_STORE_STATUS_INFRINGEMENT_NOT = '0';	//产品侵权
	const PRODUCT_STORE_NEW_UPLOAD = 1;  //新品
	const PRODUCT_STORE_NEW_UPLOAD_NOT = 0;  //
	const PRODUCT_STORE_VERIFY = 1;  //审核
	const PRODUCT_STORE_VERIFY_NOT = 0;	 //未审核
	
	const PRODUCT_ACTION_UPDATE	= '1';
	const PRODUCT_ACTION_UPDATE_TO_WEBSITE = '1.1'; //产品更新到website
	const PRODUCT_ACTION_UPDATE_FROM_WEBSITE = '1.2'; //新产品website更新到 本地
	const PRODUCT_ACTION_UPDATE_STATUS_TO_WEBSITE = '2'; //产品更新status到website
	const PRODUCT_ACTION_UPDATE_STATUS_TO_WEBSITE_ON = '2.1' ; //下线
	const PRODUCT_ACTION_UPDATE_STATUS_TO_WEBSITE_UN = '2.2' ; //上线
	const PRODUCT_ACTION_UPDATE_STATUS_TO_WEBSITE_CLEARANCE = '2.6' ; //clearance
	const PRODUCT_ACTION_UPDATE_STATUS_TO_WEBSITE_STOPSELL = '2.7' ; //stopsell
	const PRODUCT_ACTION_UPDATE_IS_IN_STOCK_TO_WEBSITE = '2.8' ; //is_in_stock
	const PRODUCT_ACTION_UPDATE_OUT_OF_STOCK_TO_WEBSITE = '2.9' ; //out_of_stock
	
	const PRODUCT_ACTION_UPDATE_STOCK_TO_WEBSITE = '3'; //产品更新stock到website
	const PRODUCT_ACTION_UPDATE_STOCK_TO_WEBSITE_IN = '3.1'; //更新库有存状态到website
	const PRODUCT_ACTION_UPDATE_STOCK_TO_WEBSITE_OUT = '3.2'; //更新无库存状态到website
	const PRODUCT_ACTION_LOCAL_UPDATE = '4'; 			//产品本地更新
	const PRODUCT_ACTION_LOCAL_UPDATE_PRICE = '4.1'; 	//update产品本地价格
	const PRODUCT_ACTION_INFRINGE = '5'; //产品侵权
	const PRODUCT_ACTION_UPLOAD = '9'; //产品刊登

	const PRODUCT_UPDATE_LOG = 	'1';
	const PRODUCT_UPDATE_TO_WEBSITE_LOG = '2';

	const SUCCESS 	= '1';//成功
	const FAILD 	= '0' ; //失败
	
	/**
	 * website global
	 */
	const WEBSITE_LANG_EN = 'en';
	const WEBSITE_PRODUCT_ATTRIBUTE_COLOR = 'color';
	const WEBSITE_PRODUCT_ATTRIBUTE_SIZE = 'size';
	const WEBSITE_PRODUCT_ATTRIBUTE_STYLE = 'style';
	/**
	 * lang map
	 */
	public static $_websiteLangMap = array(
			self::WEBSITE_LANG_EN
	);
	/**
	 * 产品属性
	 */
	public static $_websiteProductAttributesMap = array(
			self::WEBSITE_PRODUCT_ATTRIBUTE_COLOR,self::WEBSITE_PRODUCT_ATTRIBUTE_SIZE,self::WEBSITE_PRODUCT_ATTRIBUTE_STYLE
	);
	/**
	 * module init 初始化 module
	 * @param unknown $moduleConfigFile
	 * @throws WebsitesException
	 */
	public static function __WebsiteInit($moduleConfigFile){
		if(!file_exists($moduleConfigFile)) throw new WebsitesException('You must setup a config file '.$moduleConfigFile);
		$moduleConfig = require_once $moduleConfigFile;
		//init websitemodel
		self::__setAPIConfig($moduleConfig['apiconfig']);
		self::__setModelsConfig($moduleConfig['models']);
	}
	
	/**
	 * 配置appAPI
	 */
	public static $apiInstance = null;
	/**
	 * 配置appAPI
	 */
	public static $appAPIConfig =null;
	/**
	 * @param Array $config
	 */
	public static function __setAPIConfig ($config=array()){
		if(self::$appAPIConfig == null){
			self::$appAPIConfig = $config;
		}
		return self::$appAPIConfig;
	}
	/**
	 * 获取magetno api
	 */
	public static function __api(){
		if(self::$apiInstance == null){
			if(!self::$appAPIConfig){
				throw new WebsitesException('magento api config not found,please contact web master.');
			}
			$soap_url 	= self::$appAPIConfig['soap_url'];
			$api_user 	= self::$appAPIConfig['api_user'];
			$api_key 	= self::$appAPIConfig['api_key'];
			self::$apiInstance = MagentoApiclientAbstract::singleton($soap_url, $api_user, $api_key);
			//check
// 			echo self::$apiInstance->getError();die;
			if(empty(self::$apiInstance) || self::$apiInstance->getError()){throw new WebsitesException('magento api error,please contact web master.'.self::$apiInstance->getError());}
		}
		return self::$apiInstance;
	}
	/**
	 * models 配置使用
	 */
	public static $appModelsConfig = NULL;
	/**
	 * @param Array $config
	 */
	public static function __setModelsConfig ($config=array()){
		if(self::$appModelsConfig == null){
			self::$appModelsConfig = $config;
		}
		return self::$appModelsConfig;
	}
	/**
	 * @param String $class
	 * Default return modelName;  if param $instance is true ,return model instance
	 */
	public static function __getModel($class,$instance = FALSE){
		$modelClass = self::$appModelsConfig[$class];
		if(!class_exists($modelClass)) throw new WebsitesException("not found class $class");
		//最好调用  new $modelClass 而不是 Ueb::model() 否则新记录没法保存
		return !$instance ? $modelClass : new $modelClass ();
	}
	public static function __getModelInstance($class){
		return self::__getModel($class,TRUE);
	}
	//overload this function
	
	//all websitemodel extentds this use db_websites
	public function getDbKey() {
		return 'db_website';
	}
	
	public function className(){
		return get_class($this);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see CActiveRecord::tableName()
	 */
	function tableName() {
		//
		throw new WebsitesException('You must return a table name.');
		return '';
	}
	/**
	 * cache related
	 */
	const WEBSETE_CACHE_TAG = 'website_cache';
	/**
	 * cache related 方便管理 website cache
	 */
	public static function __setCache($id,$value){
		$cache = Yii::app()->cache->get(self::__getModuleId());
		if(empty($cache)||!is_array($cache)) {$cache = array();}
		$cache[$id] = $value;
		$cache = Yii::app()->cache->set(self::__getModuleId(),$cache,60*60*24);
	}
	/**
	 * @param STRING $id
	 */
	public static function __getCache($id){
		$cache = Yii::app()->cache->get(self::__getModuleId());
		return isset($cache[$id]) ? $cache[$id] : NULL ;
	}
	/**
	 * 重置website cache
	 * @param string $id
	 */
	public static function __resetCache($id = null){
		if ($id == null) {
			Yii::app()->cache->delete(self::__getModuleId());
		}else {
			//
			$cache = Yii::app()->cache->get(self::__getModuleId());
			unset($cache[$id]);
			$cache = Yii::app()->cache->set(self::__getModuleId(),$cache,60*60*24);
		}
	}
	public static function __getModuleId(){
		return Yii::app()->getController()->module->id;
	}
}