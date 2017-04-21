<?php
/**
 * @desc 系统模块引入
 * @author Gordon
 * @since 2015-06-06
 */
class SystemsModule extends CWebModule {

    private $_assetsUrl;

    public function init() {
        $this->setImport(array(
            'systems.models.*',
            'systems.components.*',
            'application.components.*',
			'task.controllers.TaskBaseController',
			'ebay.models.*',
			'aliexpress.models.*',
            'wish.models.*',
			'amazon.models.*',
			'lazada.models.*',
			'products.models.*',
			'report.models.*',
			'task.models.*',
        ));
    }

    public function getAssetsUrl() {

        if ($this->_assetsUrl === null)
            $this->_assetsUrl = Yii::app()->getAssetManager()->publish(Yii::getPathOfAlias('application.modules.systems.assets'));

        return $this->_assetsUrl;
    }

    public function setAssetsUrl($value) {

        $this->_assetsUrl = $value;
    }

}
