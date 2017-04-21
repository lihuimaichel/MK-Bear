<?php
/**
 * @desc 网站模块类
 * @author zhangF
 *
 */
class TaskModule extends CWebModule {
	public function init() {
		$this->setImport(array(
			'ebay.models.*',
			'aliexpress.models.*',
            'wish.models.*',
            'amazon.models.*',
            'lazada.models.*',
			'task.models.*',
			'system.models.*',
			'task.components.*',
			'task.Api.*',
			'task.controllers.TaskCommonController',
			'task.controllers.TaskBaseController',
			'products.models.*',
			'application.components.*',
			'report.models.*',
		));
	}
}