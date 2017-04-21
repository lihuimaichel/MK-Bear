<?php
/**
 * 
 * @author xiej 2015-6-12
 * @package Website
 * @since NF 产品listing
 */
abstract class WebsiteProductListingController extends WebsiteController{
	
	//不要有这个方法
	// 	public function __construct(){

	// 	}
	public function accessRules(){
		return array(
				array(
						'allow',
						'users' => array('*'),
						'actions' => array('syslisting')
				),
		);
	}
	/**
	 * 同步产品list使用
	 */
	public function actionSyslisting(){
		$listing = new ProductListing();
		$listing->sysListing();
	}
}	