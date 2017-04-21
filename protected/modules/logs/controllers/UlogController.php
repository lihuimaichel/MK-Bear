<?php

/**
 * @package Ueb.modules.logs.controllers
 * 
 * @author Bob <zunfengke@gmail.com>
 */
class UlogController extends UebController {

    /**
     * Specifies the access control rules.
     * This method is used by the 'accessControl' filter.
     * @return array access control rules
     */
    public function accessRules() {
        return array();
    }

    /**
     * log list
     */
    public function actionList() {
        switch ($_REQUEST['platform']) {
            case 'ebay':
                list($models, $pages) = EbayLog::model()->getPageList();
                break;
            case 'amazon':
                list($models, $pages) = AmazonLog::model()->getPageList();
                break;
            case 'aliexpress':
                list($models, $pages) = AliexpressLog::model()->getPageList();
                break;
            case 'operation':
                list($models, $pages) = OperationLog::model()->getPageList();
                break;
        }
        $this->render('list', array(
            'models' => $models,
            'pages' => $pages,
        ));
    }

    public function actionIndex() {
        $this->render('index');
    }

}
