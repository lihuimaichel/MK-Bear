<?php
/**
 * @desc 平台账号模块引入
 * @author hanxy
 * @since 2017-02-22
 */
class PlatformaccountModule extends CWebModule {
    public function init() {
        $this->setImport(array(
            'application.modules.platformaccount.models.*',
            'application.modules.platformaccount.components.*',
            'application.vendors.platformaccount.aliexpress.*',
            'application.vendors.platformaccount.wish.*',
            'application.vendors.platformaccount.joom.*',
            'application.vendors.platformaccount.lazada.*',
            'application.vendors.platformaccount.ebay.*',
            'application.components.*',
            'application.modules.ebay.models.*',
            'application.modules.aliexpress.models.*',
            'application.modules.amazon.models.*',
            'application.modules.lazada.models.*',
            'application.modules.wish.models.*',
            'application.modules.joom.models.*',
            'application.modules.shopee.models.*',
            'application.modules.priceminister.models.*',
            'application.modules.common.models.*',
            'application.modules.common.components.*',
            'systems.models.*',
            'users.models.*'
        ));
    }
}