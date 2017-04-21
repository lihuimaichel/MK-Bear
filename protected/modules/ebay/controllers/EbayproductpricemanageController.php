<?php
/**
 * @desc ebay价格管理控制器
 * @since 2016-09-17
 * @author yangsh
 */
class EbayproductpricemanageController extends UebController
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
                            'modifypriceforos',
                        ) 
                ) 
        );
    }

    /**
     * @desc 海外仓改价
     * @link /ebay/ebayproductpricemanage/modifypriceforos/account_id/64/site_id/3
     */
    public function actionModifypriceforos() {
        set_time_limit ( 0 );
        ini_set ( 'display_errors', true );
        error_reporting( E_ALL );
        
        $model     = new EbayProductPriceManage ();
        $locArr    = array_keys($model->getOverseaLocationWhIdMap());//需要自动改价的海外仓location
        $accountId = trim(Yii::app ()->request->getParam ( 'account_id', ''));//必须
        $siteID   = trim(Yii::app ()->request->getParam ( 'site_id', '3'));//必须,默认英国站
        $itemID    = trim(Yii::app ()->request->getParam ( 'item_id', ''));//非必须
        $onlineSku = trim(Yii::app ()->request->getParam ( 'online_sku', ''));//非必须
        
        //参数验证
        $validateMsg = '';
        if (!empty($accountId) && !preg_match('/^\d+$/',$accountId)) {
            $validateMsg .= 'account_id is invalid;';
        }
        if ($siteID !== '' && !preg_match('/^\d+$/',$siteID)) {
            $validateMsg .= 'site_id is invalid;';
        }
        if ($validateMsg != '') {
            Yii::app ()->end($validateMsg);
        }

        if ($accountId != '') {
            //prepareLog
            $ebayLogModel = new EbayLog ();
            $logID = $ebayLogModel->prepareLog ( $accountId, EbayProductPriceManage::EVENT_NAME );
            if (!$logID) {
                echo 'Insert prepareLog failure';
                Yii::app ()->end();
            }
            //1.检查事件是否正在运行
            $checkRunning = $ebayLogModel->checkRunning ( $accountId, EbayProductPriceManage::EVENT_NAME );
            if (! $checkRunning ) {
                $ebayLogModel->setFailure ( $logID, Yii::t ( 'system', 'There Exists An Active Event' ) );
                echo 'There Exists An Active Event ';
                Yii::app ()->end();
            }
            //3.设置日志为正在运行
            $ebayLogModel->setRunning ( $logID );
            //4. 开始执行
            $isOk = $model->startModifyPrice($accountId,$siteID,$itemID,$onlineSku,$locArr);
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
            $accountIdArr = $model->getOverseaAccountIDs();
            sort($accountIdArr,SORT_NUMERIC);
            $accountIdGroupData = MHelper::getGroupData($accountIdArr,10);
            foreach ($accountIdGroupData as $key => $idGroupData) {
                foreach ($idGroupData as $account_id) {                   
                    $url = Yii::app()->request->hostInfo.'/' . $this->route . '/account_id/' . $account_id . '/site_id/' . $siteID;
                    echo $url." <br>\r\n";
                    MHelper::runThreadBySocket($url);
                    sleep(300);
                }
            }
        }
        Yii::app ()->end('finish');
    }

}