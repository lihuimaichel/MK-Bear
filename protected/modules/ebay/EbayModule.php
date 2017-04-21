<?php
/**
 * @desc ebay模块引入
 * @author Gordon
 * @since 2015-06-01
 */
class EbayModule extends CWebModule {

    public function init() {
        $this->setImport(array(
            'application.vendors.ebay.*',
            'application.vendors.ebayRestful.*',
            'application.vendors.paypal.*',
            'application.components.*',
            'application.modules.ebay.components.*',
            'application.modules.ebay.models.*',
            'application.modules.orders.models.*',
            'application.modules.products.components.*',
            'application.modules.products.models.*',
        	'application.modules.warehouse.models.*',
        	'application.modules.common.models.*',
        	'application.modules.common.components.*',
        	'application.modules.logistics.models.*',
        ));
    }
}