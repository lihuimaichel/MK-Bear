<?php

/**
 * @desc 系统模块引入
 * @author arke.wu
 * @since 2017-01-20
 */
class ReportModule extends CWebModule
{
	public function init()
	{
		$this->setImport(array(
			'ebay.models.*',
			'report.models.*',
			'products.models.*',
            'aliexpress.models.*',
            'wish.models.*',
            'amazon.models.*',
            'lazada.models.*',
            'task.models.*',
			'task.controllers.TaskBaseController',
		));
	}
}
