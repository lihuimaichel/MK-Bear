<?php
/**
 * @desc 仓库模块引入
 * @author Gordon
 */
class WarehouseModule extends CWebModule {

    public function init() {
        $this->setImport(array(
        	'warehouse.models.*',
        	'warehouse.components.*',
        	'products.models.*',
            'products.components.*',
            'application.components.*',
        ));
    }

}
