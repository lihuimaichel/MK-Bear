<?php
/**
 * @desc PM模块类
 * @author LIHY
 * @since 2016-7-1
 *
 */
class PriceministerModule extends CWebModule {
	public function init() {
		$this->setImport(
			array(
				'application.components.*',
				'application.vendors.priceminister.*',
				'application.modules.priceminister.components.*',
				'application.modules.priceminister.models.*',
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