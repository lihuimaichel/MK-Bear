<?php
/**
 * @desc jd模块类
 * @author zhangf
 *
 */
class JdModule extends CWebModule {
	public function init() {
		Yii::app()->setImport(array(
            'application.vendors.jd.*',
            'application.components.*',
            'application.modules.jd.components.*',
            'application.modules.jd.models.*',
            'application.modules.orders.models.*',
            'application.modules.products.components.*',
            'application.modules.products.models.*',
            'application.modules.logistics.models.*',
        	'application.modules.common.models.*',
        	'application.modules.common.components.*',
        	'application.modules.aliexpress.models.*',
		));
	}
}