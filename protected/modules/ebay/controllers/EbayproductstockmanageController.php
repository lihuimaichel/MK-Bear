<?php
/**
 * @desc ebay库存管理控制器
 * @since 2016-08-31
 * @author yangsh
 */
class EbayproductstockmanageController extends UebController
{
	
    /**
     * 访问过滤配置
     *
     * @see CController::accessRules()
     */
    public function accessRules() {
        return array (
                array (
                        'allow',
                        'users' => array (
                                '*' 
                        ),
                        'actions' => array (
                            'replenish',
                            'fixedstock',
                        ) 
                ) 
        );
    }

    /**
     * @desc 售出后补库存
     * @link /ebay/ebayproductstockmanage/replenishstock/account_id/64
     */
    public function actionReplenishstock() {
        set_time_limit ( 0 );
        ini_set ( 'display_errors', true );
        error_reporting( E_ALL );

        //海外仓账号ID：37,54,55,57,59,60,62,13
        $model = new EbayProductStockManage ();

        $accountId = trim(Yii::app ()->request->getParam ( 'account_id', ''));//必须
        $itemID    = trim(Yii::app ()->request->getParam ( 'item_id', ''));//非必须
        $onlineSku = trim(Yii::app ()->request->getParam ( 'online_sku', ''));//非必须
        
        //参数验证
        $validateMsg = '';
        if (!empty($accountId) && !preg_match('/^\d+$/',$accountId)) {
            $validateMsg .= 'account_id is invalid;';
        }
        $autoReviseQtyAccountIDs = EbayAccount::getAutoReviseQtyAccountIds();//需要补库存的销售账号ID
        if ($accountId != '' && !in_array($accountId, $autoReviseQtyAccountIDs)) {
            $model->setInvalid($accountId);//相关记录设置不补
            $validateMsg .= 'account_id is not need to Revise Qty;';
        }
        if ($validateMsg != '') {
            Yii::app ()->end($validateMsg);
        }

        if ($accountId != '') {
            //prepareLog
            $ebayLogModel = new EbayLog ();
            $logID = $ebayLogModel->prepareLog ( $accountId, EbayProductStockManage::EVENT_NAME );
            if (!$logID) {
                echo 'Insert prepareLog failure';
                Yii::app ()->end();
            }
            // 1.检查事件是否正在运行
            $checkRunning = $ebayLogModel->checkRunning ( $accountId, EbayProductStockManage::EVENT_NAME );
            if (! $checkRunning ) {
                $ebayLogModel->setFailure ( $logID, Yii::t ( 'system', 'There Exists An Active Event' ) );
                echo 'There Exists An Active Event ';
                Yii::app ()->end();
            }
            // 3.设置日志为正在运行
            $ebayLogModel->setRunning ( $logID );
            // 4. 开始执行
            $isOk = $model->replenishStock($accountId,$itemID,$onlineSku);
            //更新日志信息
            $flag = $isOk ? 'Success' : 'Failure';
            if ( $isOk ) {
                $ebayLogModel->setSuccess ( $logID );
            } else {
                $ebayLogModel->setFailure ( $logID, $model->getExceptionMessage() );
            }
            //记录日志 
            $result = date("Y-m-d H:i:s").'###'.json_encode($_REQUEST).'==='.$flag.'==='.$model->getExceptionMessage();
            echo $result."\r\n<br>";
        } else {
            //循环每个账号
            $accountList = EbayAccount::model()->getAbleAccountList();
            $accountIdArr = array();
            foreach ($accountList as $accountInfo) {
                if (!in_array($accountInfo['id'], $autoReviseQtyAccountIDs)) {//需要自动补库存的账号ID
                    $model->setInvalid($accountInfo['id']);//相关记录设置不补
                    continue;
                }                               
                $accountIdArr[] = $accountInfo['id'];
            }
            if (empty($accountIdArr)) {
                die('No Data For Run');
            }
            sort($accountIdArr,SORT_NUMERIC);
            $accountIdGroupData = MHelper::getGroupData($accountIdArr,3);
            foreach ($accountIdGroupData as $key => $idGroupData) {
                foreach ($idGroupData as $account_id) {                  
                    $url = Yii::app()->request->hostInfo.'/' . $this->route . '/account_id/' . $account_id;
                    echo $url." <br>\r\n";
                    MHelper::runThreadBySocket($url);
                    sleep(300);
                }
            }
        }
        Yii::app ()->end('finish');
    }

    /**
     * @desc 来货后补库存
     * @link /ebay/ebayproductstockmanage/fixedstock/account_id/64
     */
    public function actionFixedstock() {
        set_time_limit ( 0 );
        ini_set ( 'display_errors', true );
        error_reporting( E_ALL );

        $model     = new EbayProductStockManage ();
        
        $accountId = trim(Yii::app ()->request->getParam ( 'account_id', ''));//必须
        $itemID    = trim(Yii::app ()->request->getParam ( 'item_id', ''));//非必须
        $onlineSku = trim(Yii::app ()->request->getParam ( 'online_sku', ''));//非必须

        //参数验证
        $validateMsg = '';
        if (!empty($accountId) && !preg_match('/^\d+$/',$accountId)) {
            $validateMsg .= 'account_id is invalid;';
        }
        $autoReviseQtyAccountIDs = EbayAccount::getAutoReviseQtyAccountIds();//需要补库存的销售账号ID
        if ($accountId != '' && !in_array($accountId, $autoReviseQtyAccountIDs)) {
            $model->setInvalid($accountId);//相关记录设置不补
            $validateMsg .= 'account_id is not need to Revise Qty;';
        }
        if ($validateMsg != '') {
            Yii::app ()->end($validateMsg);
        }
        if ($accountId != '') {           
            //prepareLog
            $ebayLogModel = new EbayLog ();
            $logID = $ebayLogModel->prepareLog ( $accountId, EbayProductStockManage::EVENT_FIXED_NAME );
            if (!$logID) {
                echo 'Insert prepareLog failure';
                Yii::app ()->end();
            }
            //1.检查是否正在运行
            $checkRunning = $ebayLogModel->checkRunning ( $accountId, EbayProductStockManage::EVENT_FIXED_NAME );
            if (! $checkRunning ) {
                $ebayLogModel->setFailure ( $logID, Yii::t ( 'system', 'There Exists An Active Event' ) );
                echo 'There Exists An Active Event ';
                Yii::app ()->end();
            }
            //3.设置日志为正在运行
            $ebayLogModel->setRunning ( $logID );
            //4. 开始执行
            $isOk = $model->fixedStock($accountId,$itemID,$onlineSku);
            //更新日志信息
            $flag = $isOk ? 'Success' : 'Failure';
            if ( $isOk ) {
                $ebayLogModel->setSuccess ( $logID );
            } else {
                $ebayLogModel->setFailure ( $logID, $model->getExceptionMessage() );
            }
            //记录日志 
            $result = date("Y-m-d H:i:s").'###'.json_encode($_REQUEST).'==='.$flag.'==='.$model->getExceptionMessage();
            echo $result."\r\n<br>";
        } else {
            //循环每个账号发送一个拉listing的请求
            $accountList = EbayAccount::model()->getAbleAccountList();
            $accountIdArr = array();
            foreach ($accountList as $accountInfo) {
                if (!in_array($accountInfo['id'], $autoReviseQtyAccountIDs)) {
                    $model->setInvalid($accountInfo['id']);//相关记录设置不补
                    continue;
                }                
                $accountIdArr[] = $accountInfo['id'];
            }
            if (empty($accountIdArr)) {
                die('No Data for Run');
            }
            sort($accountIdArr,SORT_NUMERIC);
            $accountIdGroupData = MHelper::getGroupData($accountIdArr,3);
            foreach ($accountIdGroupData as $key => $idGroupData) {
                foreach ($idGroupData as $account_id) {
                    $url = Yii::app()->request->hostInfo.'/' . $this->route . '/account_id/' . $account_id;
                    echo $url." <br>\r\n";
                    MHelper::runThreadBySocket($url);
                    sleep(300);
                }
            }
        }
        Yii::app ()->end('finish');
    }

    /**
     * @补在线库存
     * @link /ebay/ebayproductstockmanage/addonlinestock/account_id/64
     */
    public function actionAddonlinestock() {
        set_time_limit ( 0 );
        ini_set ( 'display_errors', true );
        error_reporting( E_ALL );

        $model = new EbayProductStockManage ();

        $accountId = trim(Yii::app ()->request->getParam ( 'account_id', ''));//必须
        $itemID    = trim(Yii::app ()->request->getParam ( 'item_id', ''));//非必须
        $onlineSku = trim(Yii::app ()->request->getParam ( 'online_sku', ''));//非必须
        
        //参数验证
        $validateMsg = '';
        if (!empty($accountId) && !preg_match('/^\d+$/',$accountId)) {
            $validateMsg .= 'account_id is invalid;';
        }
        $autoReviseQtyAccountIDs = EbayAccount::getAutoReviseQtyAccountIds();//需要补库存的销售账号ID
        if ($accountId != '' && !in_array($accountId, $autoReviseQtyAccountIDs)) {
            $model->setInvalid($accountId);//相关记录设置不补
            $validateMsg .= 'account_id is not need to Revise Qty;';
        }
        if ($validateMsg != '') {
            Yii::app ()->end($validateMsg);
        }

        if ($accountId != '') {
            //prepareLog
            $ebayLogModel = new EbayLog ();
            $logID = $ebayLogModel->prepareLog ( $accountId, EbayProductStockManage::EVENT_NAME_ADDSTOCK );
            if (!$logID) {
                echo 'Insert prepareLog failure';
                Yii::app ()->end();
            }
            // 1.检查事件是否正在运行
            $checkRunning = $ebayLogModel->checkRunning ( $accountId, EbayProductStockManage::EVENT_NAME_ADDSTOCK );
            if (! $checkRunning ) {
                $ebayLogModel->setFailure ( $logID, Yii::t ( 'system', 'There Exists An Active Event' ) );
                echo 'There Exists An Active Event ';
                Yii::app ()->end();
            }
            // 3.设置日志为正在运行
            $ebayLogModel->setRunning ( $logID );
            // 4. 开始执行
            $isOk = $model->addOnlineStock($accountId,$itemID,$onlineSku);
            //更新日志信息
            $flag = $isOk ? 'Success' : 'Failure';
            if ( $isOk ) {
                $ebayLogModel->setSuccess ( $logID );
            } else {
                $ebayLogModel->setFailure ( $logID, $model->getExceptionMessage() );
            }
            //记录日志 
            $result = date("Y-m-d H:i:s").'###'.json_encode($_REQUEST).'==='.$flag.'==='.$model->getExceptionMessage();
            echo $result."\r\n<br>";
        } else {
            //循环每个账号
            $accountList = EbayAccount::model()->getAbleAccountList();
            $accountIdArr = array();
            foreach ($accountList as $accountInfo) {
                if (!in_array($accountInfo['id'], $autoReviseQtyAccountIDs)) {//需要自动补库存的账号ID
                    $model->setInvalid($accountInfo['id']);//相关记录设置不补
                    continue;
                }                               
                $accountIdArr[] = $accountInfo['id'];
            }
            if (empty($accountIdArr)) {
                die('No Data For Run');
            }
            sort($accountIdArr,SORT_NUMERIC);
            $accountIdGroupData = MHelper::getGroupData($accountIdArr,10);
            foreach ($accountIdGroupData as $key => $idGroupData) {
                foreach ($idGroupData as $account_id) {                  
                    $url = Yii::app()->request->hostInfo.'/' . $this->route . '/account_id/' . $account_id;
                    echo $url." <br>\r\n";
                    MHelper::runThreadBySocket($url);
                    sleep(120);
                }
            }
        }
        Yii::app ()->end('finish');
    }


}