<?php
abstract class WebsiteController extends UebController {
	public function getServerHost(){
		return $_SERVER['HTTP_HOST'];
	}
	//test
 	public function actionTest(){
 		$realatedSku = $this->getProductModel(true)->getRealtedSkus();
 		
 		
 		die;
 		$orderIncrementId = '30000043071';
 		$order_info = $this->getProductModel(true)->__api()->sales_order_info($orderIncrementId);
 		var_dump($order_info);die;
	 }
	 /**
	  * module init 初始化 module
	  * @param unknown $moduleConfigFile
	  * @throws WebsitesException
	  */
	 public static function __WebsiteInit($moduleConfigFile){
	 	WebsiteModelAbstract::__WebsiteInit($moduleConfigFile);
	 }	 	 
	 /**
	  * Model Map
	 * @param unknown $class
	 * Default return modelName;  if param $instance is true ,return model instance
	 */
	 public function __getModel($class,$instance = false){
	 	return WebsiteModelAbstract::__getModel($class,$instance);
	 }
	 /**
	  * 获取模型实例
	  * @param string $class
	  * @return Ambigous <unknown, Array>
	  */
	 public function __getModelInstance($class){
	 	return $this->__getModel($class,TRUE);
	 }
	 /**
	  * @param boolean $instance
	  * @return ModelName or Model instance if param $instance is true.
	  */
	 public function getProductModel($instance = false){
	 	return $this->__getModel('product',$instance);
	 }
	 public function getUebProductModel($instance = false){
	 	return $this->__getModel('uebproduct',$instance);
	 }
	 public static $_viewGridId = null;
	 public function getViewGridId(){
	 	if(self::$_viewGridId == null){
	 		self::$_viewGridId = $this->id.'_'.time().'_'.'grid';
	 	}
	 	return self::$_viewGridId;
	 }
}