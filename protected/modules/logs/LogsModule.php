<?php

/**
 * @package Ueb.modules.logs
 * 
 * @author Bob <zunfengke@gmail.com>
 */
class LogsModule extends CWebModule {

    private $_assetsUrl;

    public function init() {
        // import the module-level models and components
        $this->setImport(array(
            'logs.models.*',
            'logs.components.*',
            // 'application.controllers.*',
            'application.components.*',
            'logistics.models.*',
            'orders.models.*',
            'products.models.*',
            'purchases.models.*',
            'services.models.*',
            'systems.models.*',
            'users.models.*',
            'warehouses.models.*',
        ));
    }

}
