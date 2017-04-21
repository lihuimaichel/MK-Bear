<?php
/**
 * @desc shopee模块引入
 * @author lihy	
 * @since 2016-10-15
 */
class ShopeeModule extends CWebModule {
    public function init() {
        $this->setImport(array(
        		'application.components.*',
	            'application.vendors.shopee.*',
	            'application.modules.shopee.components.*',
	            'application.modules.shopee.models.*',
	            'application.modules.orders.models.*',
	            'application.modules.products.components.*',
	            'application.modules.products.models.*',
	        	'application.modules.logistics.models.*',
        		'application.modules.common.models.*',
        		'application.modules.common.components.*',
        		'application.modules.warehouse.models.*',
        ));
    }
}