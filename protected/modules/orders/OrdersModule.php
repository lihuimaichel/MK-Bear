<?php
/**
 * @desc 订单模块引入
 * @author Gordon
 */
class OrdersModule extends CWebModule {

    private $_assetsUrl;

    public function init() {
        $this->setImport(array(
            'orders.models.*',
            'orders.components.*',
        	'systems.model.*',
        	'application.extensions.*',
        	'application.modules.logistics.models.*',
        	'application.modules.common.models.*',
        	'application.modules.common.components.*',
        	'application.modules.ebay.models.*',
        	'application.modules.aliexpress.models.*',
        	'application.modules.amazon.models.*',
        	'application.modules.lazada.models.*',
        	'application.modules.wish.models.*',
        ));
    }

}
