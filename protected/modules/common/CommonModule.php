<?php

/**
 * @desc 公用模块
 * @author zhangF
 *
 */
class CommonModule extends CWebModule {
	
	public function init() {
		$this->setImport(array(
			'application.components.*',
			'application.modules.common.components.*',
			'application.modules.common.models.*',
			'application.modules.amazon.models.*',
			'application.modules.aliexpress.models.*',
			'application.modules.ebay.models.*',
			'application.modules.website.models.*',
			'application.modules.wish.models.*',
			'application.modules.joom.models.*',
			'application.modules.products.models.*',
			'application.modules.orders.models.*',
			'application.modules.warehouse.models.*',
			'application.modules.lazada.models.*',
			'application.modules.jd.models.*',
		));
	}
	
}