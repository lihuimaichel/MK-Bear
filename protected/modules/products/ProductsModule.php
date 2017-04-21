<?php
/**
 * @desc 产品模块引入
 * @author Gordon
 */
class productsModule extends CWebModule {

    public function init() {
        $this->setImport(array(
            'products.models.*',
            'products.components.*',
        	'application.vendors.ebay.*',
        	'application.vendors.amazon.*',
        	'application.vendors.wish.*',
            'application.components.*',
        	'application.modules.orders.models.*',
        	'application.modules.orders.components.*',
        	'application.modules.ebay.models.*',
        	'application.modules.ebay.components.*',
        	'application.modules.aliexpress.models.*',
        	'application.modules.amazon.models.*',
        	'application.modules.lazada.models.*',
       		'application.modules.wish.models.*',
        	'application.modules.joom.models.*',
       		'application.modules.priceminister.models.*',
        	'application.modules.common.models.*',
        	'application.modules.common.components.*',
        	'application.modules.logistics.models.*',
			'task.controllers.TaskBaseController',
			'systems.models.*'
        ));
    }

}
