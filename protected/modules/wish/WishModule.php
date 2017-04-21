<?php
/**
 * @desc wish模块引入
 * @author Gordon
 * @since 2015-06-22
 */
class WishModule extends CWebModule {
    public function init() {
        $this->setImport(array(
        		'application.components.*',
	            'application.vendors.wish.*',
	            'application.modules.wish.components.*',
	            'application.modules.wish.models.*',
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