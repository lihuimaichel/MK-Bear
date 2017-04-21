<?php

/**
 * @package Ueb.modules.website
 * 
 * @author xj
 */
class WebsitesModule extends CWebModule {
    public function init() {
        // import the module-level models and components
        $this->setImport(array(
        	//记得引入controller
        	'websites.controller.*',
        	'websites.components.*',
        	'websites.models.*',
        	//order models
        	'application.modules.orders.models.*',
        		
        	'application.modules.system.models.*' ,
        	'application.modules.products.models.*',
        	'application.modules.purchases.models.*',
//         	//计算最小运费使用
        	'application.modules.logistics.models.*',
        	'application.modules.logistics.components.*',
        	'application.components.*',
        	'application.modules.warehouses.models.*',
        	//curl
        	'application.extensions.*',
        	//paypal
        	'application.vendors.paypal.*'
        ));
        //set timezone
        date_default_timezone_set('Asia/Shanghai');//此句用于消除时间差
        //ini set
        set_time_limit(0);
        if(
//         		1 ||
        	!empty($_REQUEST['debug_mode'])){
        	ini_set('display_errors', 1);
        	error_reporting(E_ALL | E_STRICT);
        }
    }
}