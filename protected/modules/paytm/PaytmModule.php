<?php
/**
 * @desc paytm模块引入
 * @author Yangsh
 * @since 2017-02-28
 */
class PaytmModule extends CWebModule {
    public function init() {
        $this->setImport(array(
        		'application.components.*',
	            'application.vendors.paytm.*',
	            'application.modules.paytm.components.*',
	            'application.modules.paytm.models.*',
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