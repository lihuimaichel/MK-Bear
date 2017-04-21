<?php

/**
 * @package Ueb.modules.NewFrog
 * 
 * @author xj
 */
class NewfrogModule extends CWebModule {


    public function init() {
        // import the module-level models and components
        $this->setImport(array(
        	'newfrog.components.*',
        	'newfrog.models.*',
//         	'application.modules.system.models.*' ,	
//         	'application.modules.products.models.*',
//         	//计算最小运费使用
//         	'application.modules.logistics.models.*',
//         	'application.modules.logistics.components.*',
//         	'application.components.*',
        ));
		//init app
        WebsiteController::__WebsiteInit($this->getBasePath().DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'config.php');
    }

}