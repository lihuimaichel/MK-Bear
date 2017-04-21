<?php
/**
 * @desc 亚马逊模块类
 * @author zhangf
 * @since 2015-7-6
 *
 */
class AmazonModule extends CWebModule {
	public function init() {
		$this->setImport(
			array(
				'application.components.*',
				'application.vendors.amazon.*',
				'application.modules.amazon.components.*',
				'application.modules.amazon.models.*',
				'application.modules.orders.models.*',
				'application.modules.products.components.*',
				'application.modules.products.models.*',
				'application.modules.logistics.models.*',
				'application.modules.common.models.*',
				'application.modules.common.components.*',
				'application.modules.warehouse.models.*',
			)
		);
	}
}