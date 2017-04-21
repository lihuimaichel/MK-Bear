<?php
/**
 * @desc Lazada模块引入
 * @author Gordon
 * @since 2015-08-07
 */
class LazadaModule extends CWebModule {
    public function init() {
        $this->setImport(array(
            'application.vendors.lazada.*',
            'application.components.*',
            'application.modules.lazada.components.*',
            'application.modules.lazada.models.*',
            'application.modules.orders.models.*',
            'application.modules.products.components.*',
            'application.modules.products.models.*',
            'application.modules.logistics.models.*',
            'application.modules.common.models.*',
            'application.modules.common.components.*',
            'application.modules.warehouse.components.*',
            'application.modules.warehouse.models.*',
        ));
    }
}