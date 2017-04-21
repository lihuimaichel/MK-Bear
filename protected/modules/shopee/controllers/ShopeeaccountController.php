<?php

/**
 * Created by PhpStorm.
 * User: ketu.lai
 * Date: 2017/3/8
 * Time: 15:20
 */
class ShopeeAccountController extends UebController
{

    /**
     * 列表
     */
    public function actionIndex()
    {
        $this->render("list", array(
            "model" => new ShopeeAccount(),
        ));
    }
    /**
     * @desc 冻结账号
     */
    public function actionLock(){
        try {
            $accountModel = new ShopeeAccount();
            $ids = Yii::app()->request->getParam('ids');
            foreach(explode(',', $ids) as $id) {
                $accountInfo = $accountModel->getAccountInfoById($id);
                if (!$accountInfo) {
                    continue;
                }
                $accountModel->lockAccount($accountInfo['id']);
            }
            $jsonData = array(
                'message' =>Yii::t('system', 'Lock Success'),
            );
            echo $this->successJson($jsonData);

        }catch (\Exception $e) {
            echo $this->failureJson(array('message'=> Yii::t('system', 'Lock Failed')));
        }

    }

    /**
     * @desc 解冻账号
     */
    public function actionUnlock(){
        try {
            $accountModel = new ShopeeAccount();
            $ids = Yii::app()->request->getParam('ids');
            foreach(explode(',', $ids) as $id) {
                $accountInfo = $accountModel->getAccountInfoById($id);
                if (!$accountInfo) {
                    continue;
                }
                $accountModel->unLockAccount($accountInfo['id']);
            }
            $jsonData = array(
                'message' =>Yii::t('system', 'ShutDown Success'),
            );
            echo $this->successJson($jsonData);

        }catch (\Exception $e) {
            echo $this->failureJson(array('message'=> Yii::t('system', 'ShutDown Failed')));
        }
    }
    /**
     * @desc 关闭账号
     */
    public function actionClose(){
        try {
            $accountModel = new ShopeeAccount();
            $ids = Yii::app()->request->getParam('ids');
            foreach(explode(',', $ids) as $id) {
                $accountInfo = $accountModel->getAccountInfoById($id);
                if (!$accountInfo) {
                    continue;
                }
                $accountModel->shutDownAccount($accountInfo['id']);
            }
            $jsonData = array(
                'message' =>Yii::t('system', 'Unlock Success'),
            );
            echo $this->successJson($jsonData);

        }catch (\Exception $e) {
            echo $this->failureJson(array('message'=> Yii::t('system', 'Unlock Failed')));
        }
    }

    /**
     * @desc 开启账号
     */
    public function actionOpen(){
        try {
            $accountModel = new ShopeeAccount();
            $ids = Yii::app()->request->getParam('ids');
            foreach(explode(',', $ids) as $id) {
                $accountInfo = $accountModel->getAccountInfoById($id);
                if (!$accountInfo) {
                    continue;
                }
                $accountModel->openAccount($accountInfo['id']);
            }
            $jsonData = array(
                'message' =>Yii::t('system', 'Open Success'),
            );
            echo $this->successJson($jsonData);

        }catch (\Exception $e) {
            echo $this->failureJson(array('message'=> Yii::t('system', 'Open Failed')));
        }
    }


    /**
     * @desc 同步到oms
     * @link /shopee/shopeeaccount/synctooms
     */
    public function actionSyncToOMS(){
        error_reporting(E_ALL);
        $accountList = ShopeeAccount::model()->getListByCondition();
        foreach ($accountList as $account) {
            try {
                $info = OmsShopeeAccount::model()->getAccountInfoById($account['id']);
                if (!$info) {
                    unset($account['department_id']);
                    OmsShopeeAccount::model()->saveInfo($account);
                } else {
                    $accountInfo = array(
                        'secret_key' => $account['secret_key'],
                        'shop_id'=> $account['shop_id'],
                        'partner_name'=> $account['partner_name'],
                        'partner_id'=> $account['partner_id'],
                    );
 
                    OmsShopeeAccount::model()->updateInfo($info['id'], $accountInfo);
                }
            }catch (\Exception $e){
                echo $e->getMessage();
                continue;
            }
        }
    }

}

