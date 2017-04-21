<?php
/**
 * @desc 网站模块类
 * @author zhangF
 *
 */
class WebsiteModule extends CWebModule {
	public function init() {
		$this->setImport(array(
			'application.components.*',
			'application.vendors.website.*',
			'application.modules.website.components.*',
			'application.modules.website.models.*',
			'application.modules.orders.models.*',
			'application.modules.products.components.*',
			'application.modules.products.models.*',
			'application.modules.system.models.*',
			'application.vendors.paypal.*',
		));
	}
}