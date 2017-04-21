<?php
/**
 * @desc aliexpress模块引入
 * @author Gordon
 * @since 2015-06-25
 */
class AliexpressModule extends CWebModule {
    public function init() {
        $this->setImport(array(
            'application.vendors.aliexpress.*',
            'application.components.*',
            'application.modules.aliexpress.components.*',
            'application.modules.aliexpress.models.*',
            'application.modules.orders.models.*',
            'application.modules.products.components.*',
            'application.modules.products.models.*',
            'application.modules.logistics.models.*',
        	'application.modules.common.models.*',
        	'application.modules.warehouse.models.*',
        	'application.modules.common.components.*',
        ));
    }
}