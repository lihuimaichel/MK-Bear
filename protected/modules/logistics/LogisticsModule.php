<?php
/**
 * @package Ueb.modules.logistics
 * 
 * @author Gordon
 */
class logisticsModule extends CWebModule {

    public function init() {
        // import the module-level models and components
        $this->setImport(array(
            'logistics.models.*',
            'logistics.components.*',
            'application.components.*',
        	'application.modules.products.models.*',
        	'application.modules.warehouses.models.*',
        	'application.modules.warehouses.components.*',
            'application.modules.orders.models.*',
        ));
    }
}
