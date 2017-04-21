<?php

class EbayproductController extends UebController
{
    public function actionList()
    {
        //error_reporting(E_ALL);
        //ini_set("display_errors", true);
        $model = new EbayProduct();
        $this->render("list", array('model' => $model));
    }

    /**
     * @todo ebay拉取listing
     * @param  $account_id 账号
     * @param  $mode 拉取模式1:获取以listing下架计算48天时间段即所有数据；0:获取以listing上架计算前后5天内数据
     * @param  $type 拉取listing时间类型：0-刊登时间；1-下架时间；
     * @author Liz
     * @since 2016/6/1
     * @link   /ebay/ebayproduct/getNewListing/account_id/3/mode/0/type/0
     */
    public function actionGetNewListing()
    {
        set_time_limit(0);
        ini_set('memory_limit', '2048M');
        ini_set('display_errors', false);
        error_reporting(0);

        $accountID = trim(Yii::app()->request->getParam('account_id', ''));
        $type = trim(Yii::app()->request->getParam('type', 0));
        $mode = trim(Yii::app()->request->getParam('mode', 0));
        $type = (!empty($type)) ? (int)$type : 0;
        $mode = (!empty($mode)) ? (int)$mode : 0;
        $day = Yii::app()->request->getParam('day', 0);
        $offset = trim(Yii::app()->request->getParam('offset', 0));//取账号ID间隔多少小时

        $ebayProductModel = new EbayProduct();
        /*
            拉listing流程：
            1)两个月定时任务执行一次，把一个月前已下架的数据移出，备份到其它表。（避免当前listing表太多已下架的数据）
            2)通过以下的每天的定时任务，两种类型时间交错更新listing:0-刊登时间；1-下架时间，并且只拉取最近的几天数据:
            /ebay/ebayproduct/GetNewListing                ##以刊登时间类型拉取
            /ebay/ebayproduct/GetNewListing?type=1         ##以下架时间类型拉取
        */
        if ($accountID) {
            $ebayProductModel->getNewListingByAccountID($accountID, $type, $mode, $day);
        } else {
            if ($mode == 1) {//按全账号
                $accountInfos = EbayAccount::getAbleAccountList();
                $accountIDs = array();
                foreach ($accountInfos as $v) {
                    $accountIDs[] = $v['id'];
                }
                //$accountIDs = EbayAccount::$OVERSEAS_ACCOUNT_ID;
                $groupData = MHelper::getGroupData($accountIDs, 4);
                foreach ($groupData as $accountIDArr) {
                    foreach ($accountIDArr as $account_id) {
                        $url = Yii::app()->request->hostInfo . '/' . $this->route . '/account_id/' . $account_id
                            . '/type/' . $type . '/mode/' . $mode . "/day/" . $day;
                        echo $url . " <br>\r\n";
                        MHelper::runThreadBySocket($url);
                    }
                    sleep(300);
                }
            } else {//按小时取分组法,每个账号5分钟
               /* $accountIDs = EbayAccount::model()->getGroupAccounts($offset);
                foreach ($accountIDs as $account_id) {
                    $url = Yii::app()->request->hostInfo . '/' . $this->route . '/account_id/' . $account_id
                        . '/type/' . $type . '/mode/' . $mode . "/day/" . $day . '/offset/' . $offset;
                    echo $url . " <br>\r\n";
                    MHelper::runThreadBySocket($url);
                    sleep(300);
                }*/

                $todo = 0;
                $hour = date('G');
                $addArr = array(8,12,16,20);
                $offlineArr = array(6,10,14,18);
                if($type == 0 && in_array($hour,$addArr)){ //按刊登跑
                    $todo = 1;
                }elseif($type == 1 && in_array($hour,$offlineArr)){//按下架跑
                    $todo = 1;
                }
                if($todo==1){
                    //获取可用帐号
                    $ebayAccounts = EbayAccount::model()->getAbleAccountList();
                    foreach ($ebayAccounts as $account) {
                        $url = Yii::app()->request->hostInfo . '/' . $this->route . '/account_id/' . $account['id']
                            . '/type/' . $type . '/mode/' . $mode . "/day/" . $day . '/offset/' . $offset;
                        echo $url . " <br>\r\n";
                        MHelper::runThreadBySocket($url);
                        sleep(100);
                    }
                }

            }
        }
        Yii::app()->end('finish');
    }

    /**
     * [actionGetitem description]
     * @link /ebay/ebayproduct/Getitem/item_id/302168317156
     */
    public function actionGetitem()
    {
        set_time_limit(0);
        ini_set('display_errors', true);
        error_reporting(E_ALL);

        $itemID = Yii::app()->request->getParam("item_id", '');
        $accountID = Yii::app()->request->getParam("account_id", '');
        if ($itemID == '') {
            die('item_id is empty');
        }
        $model = EbayProduct::model();
        $model->setAccountID($accountID);
        $flag = $model->getItemInfo($itemID);
        if ($flag) {
            echo "success";
        } else {
            echo "failure:";
            echo($model->getExceptionMessage());
        }
        Yii::app()->end();
    }

    /**
     * @desc 获取拍卖产品竞拍
     * @author Michael
     * @since 2015-08-14
     */
    public function actionGetBidderList()
    {
        $accountID = Yii::app()->request->getParam('account_id');
        if ($accountID) {
            $logID = EbayLog::model()->prepareLog($accountID, EbayProductBidder::EVENT_NAME);
            if ($logID) {
                // 1. 检查帐号是否可拉取订单
                $checkRunning = EbayLog::model()->checkRunning($accountID, EbayProductBidder::EVENT_NAME);
                if (!$checkRunning) {
                    EbayLog::model()->setFailure($logID, Yii::t('system', 'There Exists An Active Event'));
                } else {
                    // 2. 准备拉取日志信息
                    $timeArr = EbayProductBidder::model()->getTimeArr($accountID);
                    //    插入本次log参数日志(用来记录请求的参数)
                    $eventLog = EbayLog::model()->saveEventLog(EbayProductBidder::EVENT_NAME, array(
                        'log_id' => $logID,
                        'account_id' => $accountID,
                        'start_time' => $timeArr['start_time'],
                        'end_time' => $timeArr['end_time'],
                    ));
                    //    设置日志为正在运行
                    EbayLog::model()->setRunning($logID);
                    //   3.拉取拍卖产品竞拍
                    $ebayProductBidderModel = new EbayProductBidder();
                    $ebayProductBidderModel->setAccountID($accountID);  //设置帐号
                    $ebayProductBidderModel->setLogID($logID);          //设置日志编号
                    $flag = $ebayProductBidderModel->getListingByDate($timeArr);
                    //   4.更新日志信息
                    if ($flag) {
                        EbayLog::model()->setSuccess($logID);
                        EbayLog::model()->saveEventStatus(EbayProductBidder::EVENT_NAME, $eventLog, EbayLog::STATUS_SUCCESS);
                    } else {
                        EbayLog::model()->setFailure($logID, $ebayProductBidderModel->getExceptionMessage());
                        EbayLog::model()->saveEventStatus(EbayProductBidder::EVENT_NAME, $eventLog, EbayLog::STATUS_FAILURE);
                    }
                }
            }
        } else {
            $ebayAccounts = EbayAccount::model()->getAbleAccountList();
            foreach ($ebayAccounts as $account) {
                MHelper::runThreadSOCKET('/' . $this->route . '/account_id/' . $account['id']);
                sleep(1);
            }
        }
    }

    /**
     * @desc 获取在线广告revise
     * @author Michael
     * @since 2015-08-15
     */
    public function actionGetSellerEventsList()
    {
        $accountID = Yii::app()->request->getParam('account_id');
        if ($accountID) {
            $logID = EbayLog::model()->prepareLog($accountID, EbayProductSellerEvents::EVENT_NAME);
            if ($logID) {
                // 1. 检查帐号是否可拉取订单
                $checkRunning = EbayLog::model()->checkRunning($accountID, EbayProductSellerEvents::EVENT_NAME);
                if (!$checkRunning) {
                    EbayLog::model()->setFailure($logID, Yii::t('system', 'There Exists An Active Event'));
                } else {
                    // 2. 准备拉取日志信息
                    $timeArr = EbayProductSellerEvents::model()->getTimeArr($accountID);
                    //    插入本次log参数日志(用来记录请求的参数)
                    $eventLog = EbayLog::model()->saveEventLog(EbayProductSellerEvents::EVENT_NAME, array(
                        'log_id' => $logID,
                        'account_id' => $accountID,
                        'start_time' => $timeArr['start_time'],
                        'end_time' => $timeArr['end_time'],
                    ));
                    //    设置日志为正在运行
                    EbayLog::model()->setRunning($logID);
                    //   3.拉取拍卖产品竞拍
                    $ebayProductSellerEventsModel = new EbayProductSellerEvents();
                    $ebayProductSellerEventsModel->setAccountID($accountID);  //设置帐号
                    $ebayProductSellerEventsModel->setLogID($logID);          //设置日志编号
                    $flag = $ebayProductSellerEventsModel->getListingByDate($timeArr);
                    //   4.更新日志信息
                    if ($flag) {
                        EbayLog::model()->setSuccess($logID);
                        EbayLog::model()->saveEventStatus(EbayProductSellerEvents::EVENT_NAME, $eventLog, EbayLog::STATUS_SUCCESS);
                    } else {
                        EbayLog::model()->setFailure($logID, $ebayProductSellerEventsModel->getExceptionMessage());
                        EbayLog::model()->saveEventStatus(EbayProductSellerEvents::EVENT_NAME, $eventLog, EbayLog::STATUS_FAILURE);
                    }
                }
            }
        } else {
            $ebayAccounts = EbayAccount::model()->getAbleAccountList();
            foreach ($ebayAccounts as $account) {
                MHelper::runThreadSOCKET('/' . $this->route . '/account_id/' . $account['id']);
                sleep(1);
            }
        }
    }

    /**
     * @desc 检测仓库中的sku库存数量，从而自动更改平台上的库存数量为0
     *       方式一：以erp产品表为主，循环取出去对比仓库库存
     *       方式二：以仓库库存表为主，批量循环取出小于1的sku，再取出对应的产品表中的相关信息，更新在线产品库存
     */
    public function actionAutochangestockfornostock()
    {
        //设置测试环境运行程序
        $loopNum = 0;
        $testFlag = false;//是否为测试标示
        $runType = Yii::app()->request->getParam("runtype");
        $testSKUs = Yii::app()->request->getParam("sku");
        $testAccountID = Yii::app()->request->getParam("account_id");
        $testSkuList = array();
        //测试环境下必须指定sku和账号
        if ($runType != "y" && (empty($testSKUs) || empty($testAccountID))) {
            exit("测试下必须指定sku列表和账号，多个sku之间用半角,隔开。示例：{$this->route}/account_id/1/sku/1123,22444,3434.09");
        } elseif ($runType != "y") {
            $testFlag = true;
            $testSkuList = explode(",", $testSKUs);
        }
        error_reporting(E_ALL);
        set_time_limit(0);
        ini_set("display_errors", true);
        $allowWarehouse = WarehouseSkuMap::WARE_HOUSE_GM;
        $conditions = "available_qty <= 1 AND warehouse_id in(" . $allowWarehouse . ")";
        $params = array();
        $limit = 200;
        $offset = 0;
        $wareSkuMapModel = new WarehouseSkuMap();
        $ebayProductModel = new EbayProduct();
        $ebayProductVariantModel = new EbayProductVariation();
        $ebayZeroStockSKUModel = new EbayZeroStockSku();

        //-- start 2016-02-01 增加type --
        $type = Yii::app()->request->getParam("type");
        if (empty($type)) $type = 0;
        $beforeTime = date("Y-m-d H:i:s", strtotime("-45 days"));
        switch ($type) {
            case 0://默认，库存<1库存清零
                $conditions = "t.available_qty < 1 AND t.warehouse_id in(" . $allowWarehouse . ") AND p.product_bak_days > 14 AND p.create_time<='{$beforeTime}' AND p.product_is_multi in (0, 1)"; //lihy modify 2016-02-14
                $SkuModel = new WarehouseSkuMap();
                $method = "getSkuListLeftJoinProductByCondition";
                $select = "t.sku";
                break;
            case 1:
                $productStatus = Product::STATUS_HAS_UNSALABLE . "," . Product::STATUS_WAIT_CLEARANCE;
                $conditions = "product_status in(" . $productStatus . ") and product_is_multi in (0, 1)";
                $SkuModel = new Product();
                $method = "getProductListByCondition";
                $select = "sku";
                break;
            case 2:
                $SkuModel = new Order();
                $method = "getOweWaitingConfirmOrdersSkuListByCondition";
                $conditions = null;
                $params = array();
                $select = "sku";
                break;
            //2016-02-03 add
            case 5://手动导入的sku来源
                $SkuModel = new ProductImportSku();
                $method = "getSkuListByCondition";
                $conditions = "ebay_status=0";
                $params = array();
                $select = "sku";
                break;
            default:
                exit('type is incorrect');
        }
        //-- end 2016-02-01 增加type --

        //取出海外仓账号id
        $filterAccountIds = array();
        $overseaAccounts = EbayAccount::model()->getAccountsByShortNames(EbayAccount::$OVERSEAS_ACCOUNT_SHORT_NAME);
        if ($overseaAccounts) {
            foreach ($overseaAccounts as $account) {
                $filterAccountIds[] = $account['id'];
            }
        }
        do {
            //1、循环取出<=1的sku列表，每次{$limit}个
            //2、取出上述sku对应的产品库中的信息
            //3、提交到ebay平台，实现在线库存修改
            if (!$testFlag) {
                $limits = "{$offset}, {$limit}";
                $skuList = $SkuModel->$method($conditions, $params, $limits, $select);
                $offset += $limit;
            } else {
                if ($loopNum > 0) {
                    exit("测试运行结束");
                }
                $skuList = array();
                foreach ($testSkuList as $sku) {
                    $skuList[] = array('sku' => $sku);
                }
                $loopNum++;
                echo "set testSkulist=" . implode(",", $testSkuList) . "<br/>";
            }
            if ($skuList) {
                $flag = true;
                $skus = array();
                foreach ($skuList as $sku) {
                    $skus[] = $sku['sku'];
                }
                unset($skuList);
                $listing = $variantListing = array();

                $command = $ebayProductVariantModel->getDbConnection()->createCommand()
                    ->from($ebayProductVariantModel->tableName() . " as t")
                    ->leftJoin($ebayProductModel->tableName() . " p", "p.id=t.listing_id")
                    ->select("t.sku, t.sku_online, t.account_id, t.quantity, t.item_id, p.site_id, p.is_multiple")
                    ->where(array("IN", "t.sku", $skus))
                    ->andWhere('p.item_status=' . EbayProduct::STATUS_ONLINE)
                    ->andWhere("p.listing_type in('FixedPriceItem', 'StoresFixedPrice')")//非拍卖下
                    ->andWhere("t.is_promote=0")// lihy add 2016-02-15
                    ->andWhere("t.quantity>0");
                if ($testAccountID) {
                    echo "set testaccount_id=" . $testAccountID . "<br/>";
                    $command->andWhere("t.account_id=" . $testAccountID);
                }
                if ($filterAccountIds) {
                    $command->andWhere(array("NOT IN", "t.account_id", $filterAccountIds));
                }
                $listing = $command->queryAll();

                if ($listing) {
                    $grouplisting = array();
                    $updateSKUS = $skus;//2016-02-03 add
                    foreach ($listing as $list) {
                        //检测当天是否已经运行了，此表，加此判断之后数据量会降很多，并且会定时进行清理记录数据 lihy add 2016-02-14
                        if ($ebayZeroStockSKUModel->checkHadRunningForDay($list['sku_online'], $list['account_id'], $list['site_id'], $list['item_id'])) {
                            continue;
                        }
                        $grouplisting[$list['account_id']][] = $list;
                        /* if(!isset($updateSKUS[$list['sku']]))//2016-02-03 add
                            $updateSKUS[$list['sku']] = $list['sku']; */
                    }
                    unset($listing, $list);
                    $eventName = EbayZeroStockSku::EVENT_ZERO_STOCK;
                    foreach ($grouplisting as $accountID => $lists) {
                        try {
                            //写log
                            $logModel = new EbayLog();
                            $logID = $logModel->prepareLog($accountID, $eventName);
                            if (!$logID) {
                                throw new Exception("Create Log ID Failure");
                            }
                            //检测是否可以允许
                            if (!$logModel->checkRunning($accountID, $eventName)) {
                                throw new Exception("There Exists An Active Event");
                            }
                            $msg = "";
                            $start_time = date("Y-m-d H:i:s");

                            //设置运行
                            $logModel->setRunning($logID);
                            $reviseInventoryStatusRequest = new ReviseInventoryStatusRequest();
                            $reviseInventoryStatusRequest->setAccount($accountID);
                            $count = 0;
                            $maxcount = 3;
                            $currentSku = array();
                            $itemstr = "";
                            foreach ($lists as $k => $list) {
                                ++$count;
                                if ($list['is_multiple'] == 1) {
                                    $reviseInventoryStatusRequest->setSku($list['sku_online']);
                                }
                                $reviseInventoryStatusRequest->setItemID($list['item_id']);
                                $reviseInventoryStatusRequest->setQuantity(0);
                                $reviseInventoryStatusRequest->push();
                                $currentSku[] = $list;
                                $itemstr .= $list['item_id'] . ",sku:" . $list['sku_online'] . "  ";
                                if ($count == $maxcount) {
                                    $count = 0;
                                    $message = $this->_startSendRequest($reviseInventoryStatusRequest, $currentSku, $type);
                                    $msg .= "<br/> accountID: {$accountID}:{$itemstr} " . $message;
                                    $currentSku = array();
                                    $itemstr = "";
                                }
                            }

                            if ($count > 0) {
                                $count = 0;
                                $message = $this->_startSendRequest($reviseInventoryStatusRequest, $currentSku, $type);
                                $msg .= "<br/> accountID: {$accountID}:{$itemstr} " . $message;
                                $currentSku = array();
                                $itemstr = "";
                            }
                            //日志完成
                            $eventLogID = $logModel->saveEventLog($eventName, array(
                                'log_id' => $logID,
                                'account_id' => $accountID,
                                'start_time' => $start_time,
                                'end_time' => date("Y-m-d H:i:s")));
                            $logModel->setSuccess($logID, $msg);
                            $logModel->saveEventStatus($eventName, $eventLogID, EbayLog::STATUS_SUCCESS);
                        } catch (Exception $e) {
                            if (isset($logID) && $logID) {
                                $logModel->setFailure($logID, $e->getMessage());
                            }
                        }

                    }
                    //2016-02-03 add
                    //如果为手动导入的则还需要更新
                    if ($type == 5 && $updateSKUS) {
                        ProductImportSku::model()->updateDataByCondition("ebay_status=0 AND sku in(" . MHelper::simplode($updateSKUS) . ")", array('ebay_status' => 1));
                    }
                    unset($grouplisting, $lists);
                } else {
                    echo("no match sku ");
                }
            } else {
                $flag = false;
                exit('not found stock less than 0');
            }
        } while ($flag);

    }

    /**
     * @desc 执行请求，自动更改库存为0
     * @param unknown $reviseInventoryStatusRequest
     * @param unknown $currentSku
     */
    private function _startSendRequest($reviseInventoryStatusRequest, $currentSku, $type, $zeroOrRestore = 'zero')
    {
        static $ebayZeroStockSKUModel;
        if (!$ebayZeroStockSKUModel)
            $ebayZeroStockSKUModel = new EbayZeroStockSku();
        //未测试，不开启
        $response = $reviseInventoryStatusRequest->setRequest()->sendRequest()->getResponse();
        $reviseInventoryStatusRequest->clean();
        //收集错误信息
        $errorMessage = '';
        if (isset($response->Errors)) {
            foreach ($response->Errors as $error) {
                $errorMessage .= (string)$error->LongMessage . ".";
            }
        }
        //echo "<br/>errormsg:",$errormsg,"xxx", $errorMessage;
        //写入记录表
        //if($reviseInventoryStatusRequest->getIfSuccess()){

        $feedItemIDs = array();
        if (!isset($response->Fees[0])) {
            $feedItemIDs[] = $response->Fees->ItemID;

        } elseif (isset($response->Fees) && $response->Fees) {//返回多个

            foreach ($response->Fees as $feed) {

                $feedItemIDs[] = $feed->ItemID;
            }

        }

        //根据返回结果测试
        foreach ($currentSku as $v) {
            $suberrormsg = "";
            if (!isset($v['type'])) {
                $v['type'] = $type;
            }
            if ($zeroOrRestore == 'zero') {
                if (in_array($v['item_id'], $feedItemIDs)) {
                    $status = 2;//成功
                    $suberrormsg = 'success';
                } else {
                    $status = 3;//失败
                    $suberrormsg = $errorMessage;
                }
                $addData = array(
                    'product_id' => $v['item_id'],
                    'seller_sku' => $v['sku_online'],
                    'sku' => $v['sku'],
                    'account_id' => $v['account_id'],
                    'site_id' => $v['site_id'],
                    'old_quantity' => $v['quantity'],
                    'status' => $status,
                    'msg' => $suberrormsg,
                    'create_time' => date("Y-m-d H:i:s"),
                    'type' => $v['type'],
                    'restore_num' => 0
                );
                //var_dump($addData);
                $ebayZeroStockSKUModel->saveData($addData);
            } elseif ($zeroOrRestore == 'restore') {
                //if($reviseInventoryStatusRequest->getIfSuccess()){
                if (in_array($v['product_id'], $feedItemIDs)) {
                    $isRestore = 1;//成功
                    $suberrormsg = 'success';
                } else {
                    $isRestore = 2;//失败
                    $suberrormsg = $errorMessage;
                }
                $updateData = array(
                    'is_restore' => $isRestore,
                    'restore_time' => date("Y-m-d H:i:s"),
                    'restore_num' => intval($v['restore_num']) + 1,
                    'restore_quantity' => $v['restore_quantity'],
                    'msg' => $suberrormsg
                );
                $ebayZeroStockSKUModel->getDbConnection()
                    ->createCommand()
                    ->update($ebayZeroStockSKUModel->tableName(),
                        $updateData, "status=2 and account_id={$v['account_id']} and seller_sku='{$v['seller_sku']}' and is_restore=0 and product_id='{$v['product_id']}'");
                //}
            }

        }

        return $errorMessage;
    }


    /**
     * @desc 海外仓库账号在国内发货的sku自动更改库存为0
     *
     */
    public function actionHwautochangestockfornostock()
    {
        //设置测试环境运行程序
        $loopNum = 0;
        $testFlag = false;//是否为测试标示
        $runType = Yii::app()->request->getParam("runtype");
        $testSKUs = Yii::app()->request->getParam("sku");
        $testAccountID = Yii::app()->request->getParam("account_id");
        $testSkuList = array();
        //测试环境下必须指定sku和账号
        if ($runType != "y" && (empty($testSKUs) || empty($testAccountID))) {
            exit("测试下必须指定sku列表和账号，多个sku之间用半角,隔开。示例：{$this->route}/account_id/1/sku/1123,22444,3434.09");
        } elseif ($runType != "y") {
            $testFlag = true;
            $testSkuList = explode(",", $testSKUs);
        }

        set_time_limit(0);
        ini_set("display_errors", true);
        $allowWarehouse = WarehouseSkuMap::WARE_HOUSE_GM;

        $params = array();
        $limit = 200;
        $offset = 0;
        $wareSkuMapModel = new WarehouseSkuMap();
        $ebayProductModel = new EbayProduct();
        $ebayProductVariantModel = new EbayProductVariation();
        $ebayZeroStockSKUModel = new EbayZeroStockSku();

        $productEbay = new ProductEbay();
        //-- start 2016-02-01 增加type --
        $type = 0;

        $conditions = "t.available_qty <= 1 AND t.warehouse_id in(" . $allowWarehouse . ") AND p.product_status<7"; //lihy modify 2016-02-14
        $SkuModel = new WarehouseSkuMap();
        $method = "getSkuListLeftJoinProductByCondition";
        $select = "t.sku";

        //-- end 2016-02-01 增加type --

        //取出海外仓账号id
        $filterAccountIds = array();
        $overseaAccounts = EbayAccount::model()->getAccountsByShortNames(EbayAccount::$OVERSEAS_ACCOUNT_SHORT_NAME);
        if ($overseaAccounts) {
            foreach ($overseaAccounts as $account) {
                $filterAccountIds[] = $account['id'];
            }
        }
        do {
            //1、循环取出<=1的sku列表，每次{$limit}个
            //2、取出上述sku对应的产品库中的信息
            //3、提交到ebay平台，实现在线库存修改
            if (!$testFlag) {
                $limits = "{$offset}, {$limit}";
                $skuList = $SkuModel->$method($conditions, $params, $limits, $select);
                $offset += $limit;
            } else {
                if ($loopNum > 0) {
                    exit("测试运行结束");
                }
                $skuList = array();
                foreach ($testSkuList as $sku) {
                    $skuList[] = array('sku' => $sku);
                }
                $loopNum++;
                echo "set testSkulist=" . implode(",", $testSkuList) . "<br/>";
            }
            if ($skuList) {
                $flag = true;
                $skus = array();
                foreach ($skuList as $sku) {
                    $skus[] = $sku['sku'];
                }
                unset($skuList);
                $listing = $variantListing = array();
                $command = $ebayProductModel->getDbConnection()->createCommand()
                    ->from($ebayProductModel->tableName())
                    ->select("sku, sku_online, account_id, site_id, item_id, quantity, is_multiple")
                    ->where(array("IN", "sku", $skus))
                    ->andWhere("is_multiple=0")
                    ->andWhere('item_status=' . EbayProduct::STATUS_ONLINE)
                    ->andWhere("is_promote=0")// lihy add 2016-02-15
                    ->andWhere("quantity>0");
                if ($testFlag) {
                    echo "set testaccount_id=" . $testAccountID . "<br/>";
                    $command->andWhere("account_id=" . $testAccountID);
                }
                if ($filterAccountIds) {
                    $command->andWhere(array("IN", "account_id", $filterAccountIds));
                }
                $listing = $command->queryAll();

                $command = $ebayProductVariantModel->getDbConnection()->createCommand()
                    ->from($ebayProductVariantModel->tableName() . " as t")
                    ->leftJoin($ebayProductModel->tableName() . " p", "p.id=t.listing_id")
                    ->select("t.sku, t.sku_online, t.account_id, t.quantity, t.item_id, p.site_id, p.is_multiple")
                    ->where(array("IN", "t.sku", $skus))
                    ->andWhere('p.item_status=' . EbayProduct::STATUS_ONLINE)
                    ->andWhere("t.is_promote=0")// lihy add 2016-02-15
                    ->andWhere("t.quantity>0");
                if ($testFlag) {
                    echo "set testaccount_id=" . $testAccountID . "<br/>";
                    $command->andWhere("t.account_id=" . $testAccountID);
                }
                if ($filterAccountIds) {
                    $command->andWhere(array("IN", "t.account_id", $filterAccountIds));
                }
                $variantListing = $command->queryAll();


                if ($listing && $variantListing)
                    $listing = array_merge($listing, $variantListing);
                elseif (!$listing && $variantListing)
                    $listing = $variantListing;
                unset($variantListing);
                if ($listing) {
                    $grouplisting = array();
                    foreach ($listing as $list) {
                        //检测当天是否已经运行了，此表，加此判断之后数据量会降很多，并且会定时进行清理记录数据 lihy add 2016-02-14
                        if ($ebayZeroStockSKUModel->checkHadRunningForDay($list['sku_online'], $list['account_id'], $list['site_id'], $list['item_id'])) {
                            continue;
                        }
                        $grouplisting[$list['account_id']][] = $list;
                    }
                    unset($listing, $list);
                    $eventName = EbayZeroStockSku::EVENT_ZERO_STOCK;
                    foreach ($grouplisting as $accountID => $lists) {
                        //获取由本地发货的海外账号的sku
                        $hwskus = array();
                        foreach ($lists as $hwlist) {
                            $hwskus[] = $hwlist['sku'];
                        }
                        $hwconditions = "ebay_accountid={$accountID} AND location='ShenZhen' AND sku in(" . MHelper::simplode($hwskus) . ")";
                        $hwLocalSkuList = $productEbay->getProductListByCondition($hwconditions, array());
                        if (empty($hwLocalSkuList)) continue;
                        $hwBeweenSkuList = array();
                        foreach ($hwLocalSkuList as $hwlocalsku) {
                            $hwBeweenSkuList[$hwlocalsku['sku']] = $hwlocalsku;
                        }
                        unset($hwLocalSkuList);
                        foreach ($lists as $key => $hwlist) {
                            if (!isset($hwBeweenSkuList[$hwlist['sku']])) {
                                unset($lists[$key]);
                            }
                        }
                        unset($hwBeweenSkuList);
                        // end

                        //写log
                        $logModel = new EbayLog();
                        $logID = $logModel->prepareLog($accountID, $eventName);
                        if (!$logID) {
                            continue;
                        }
                        //检测是否可以允许
                        if (!$logModel->checkRunning($accountID, $eventName)) {
                            $logModel->setFailure($logID, Yii::t('system', 'There Exists An Active Event'));
                            continue;
                        }
                        $msg = "";
                        $eventLogID = $logModel->saveEventLog($eventName, array(
                            'log_id' => $logID,
                            'account_id' => $accountID,
                            'start_time' => date("Y-m-d H:i:s"),
                            'end_time' => date("Y-m-d H:i:s")));
                        //设置运行
                        $logModel->setRunning($logID);
                        $reviseInventoryStatusRequest = new ReviseInventoryStatusRequest();
                        $reviseInventoryStatusRequest->setAccount($accountID);
                        $count = 0;
                        $maxcount = 3;
                        $currentSku = array();
                        foreach ($lists as $k => $list) {
                            ++$count;
                            if ($list['is_multiple'] == 1) {
                                $reviseInventoryStatusRequest->setSku($list['sku_online']);
                            }
                            $reviseInventoryStatusRequest->setItemID($list['item_id']);
                            $reviseInventoryStatusRequest->setQuantity(0);
                            $reviseInventoryStatusRequest->push();
                            $currentSku[] = $list;
                            if ($count == $maxcount) {
                                $count = 0;
                                $message = $this->_startSendRequest($reviseInventoryStatusRequest, $currentSku, $type);
                                $msg .= " accountID: {$accountID} " . $message;
                                $currentSku = array();
                            }
                        }

                        if ($count > 0) {
                            $count = 0;
                            $message = $this->_startSendRequest($reviseInventoryStatusRequest, $currentSku, $type);
                            $msg .= " accountID: {$accountID} " . $message;
                            $currentSku = array();
                        }
                        //日志完成
                        $logModel->setSuccess($logID, $msg);
                        $logModel->saveEventStatus($eventName, $eventLogID, EbayLog::STATUS_SUCCESS);
                    }
                    unset($grouplisting, $lists);
                } else {
                    echo("no match sku ");
                }
            } else {
                $flag = false;
                exit('not found stock less than 0');
            }
        } while ($flag);
    }

    /**
     * @desc 根据listing自动改库存为0
     * @link /ebay/ebayproduct/autochangestockbylisting/account_id/xx/sku/xx/limit/xx/bug/1/norun/1
     * @throws Exception
     */
    public function actionAutochangestockbylisting()
    {
        set_time_limit(4 * 3600);
        ini_set('display_errors', true);
        error_reporting(E_ALL);

        $accountIDs = Yii::app()->request->getParam('account_id');
        $sku = Yii::app()->request->getParam('sku');
        $limit = Yii::app()->request->getParam('limit');
        $bug = Yii::app()->request->getParam('bug');
        $norun = Yii::app()->request->getParam('norun');
        $itemID = Yii::app()->request->getParam('item_id');
        $norunle = Yii::app()->request->getParam('norule');
        $siteID = Yii::app()->request->getParam('site_id');
        $type = 0;
        if ($accountIDs) {
            $accountIDArr = explode(",", $accountIDs);
            $allowWarehouse = WarehouseSkuMap::WARE_HOUSE_GM;
            //取出海外仓账号id
            $filterAccountIds = array();
            $overseaAccounts = EbayAccount::model()->getAccountsByShortNames(EbayAccount::$OVERSEAS_ACCOUNT_SHORT_NAME);
            if ($overseaAccounts) {
                foreach ($overseaAccounts as $account) {
                    $filterAccountIds[] = $account['id'];
                }
            }
            $beforeTime = date("Y-m-d H:i:s", time() - 90 * 24 * 3600);
            $fourFiveDayBeforeTime = date("Y-m-d H:i:s", time() - 45 * 24 * 3600);
            $bakDay = 10;
            $nowTime = time();
            //临时调整为21号开始执行，---2017-01-19 lihy
            if ($nowTime < strtotime("2017-01-21 00:00:00")) {    //1月20号前可用库存+在途库存<1, 调为0
                $conditions = "(t.available_qty + t.transit_qty) < IFNULL(s.day_sale_num,1) AND p.product_status not in (6,7) AND t.warehouse_id in(" . $allowWarehouse . ")";
                $type = 7;//过年类型20号之前
            } elseif ($nowTime >= strtotime("2017-01-21 00:00:00") && $nowTime < strtotime("2017-02-15 00:00:00")) {    //1月20号(含)后可用库存<1, 调0
                $conditions = "t.available_qty < IFNULL(s.day_sale_num,1) AND p.product_status not in (6,7) AND t.warehouse_id in(" . $allowWarehouse . ")";
                $type = 8;//过年类型20号之后
            } else {    //2月6号恢复此规则
                $conditions = "t.available_qty < 1 AND t.warehouse_id in(" . $allowWarehouse . ") AND p.product_bak_days>{$bakDay} AND (p.create_time<='{$beforeTime}' OR (qe.qe_check_result=1 and qe.qe_check_time<='{$fourFiveDayBeforeTime}'))"; //lihy modify 2016-10-14
            }
            $method = "getSkuListLeftJoinProductAndQERecordByCondition";
            $select = "t.sku";
            if ($bug) {
                echo "<pre>";
                echo "<br>condition:{$conditions}<br/>";
            }
            foreach ($accountIDArr as $accountID) {
                $wareSkuMapModel = new WarehouseSkuMap();
                $ebayProductModel = new EbayProduct();
                $ebayProductVariantModel = new EbayProductVariation();
                $ebayZeroStockSKUModel = new EbayZeroStockSku();
                try {

                    $logModel = new EbayLog();
                    $eventName = $ebayZeroStockSKUModel::EVENT_ZERO_STOCK;
                    $logID = $logModel->prepareLog($accountID, $eventName);
                    if (!$logID) {
                        throw new Exception("Create Log ID fail");
                    }

                    if (!$limit)
                        $limit = 1000;
                    $offset = 0;
                    $msg = "";
                    do {
                        $command = $ebayProductVariantModel->getDbConnection()->createCommand()
                            ->from($ebayProductVariantModel->tableName() . " as t")
                            ->leftJoin($ebayProductModel->tableName() . " p", "p.id=t.listing_id")
                            ->select("t.sku, t.sku_online, t.account_id, t.quantity, t.item_id, p.site_id, p.is_multiple, p.location")
                            ->where("t.account_id='{$accountID}'")
                            ->andWhere('p.item_status=' . EbayProduct::STATUS_ONLINE)
                            ->andWhere("p.listing_type in('FixedPriceItem', 'StoresFixedPrice')")//非拍卖下
                            ->andWhere("t.is_promote=0")// lihy add 2016-02-15
                            ->andWhere("t.quantity>0")
                            ->limit($limit, $offset);
                        $offset += $limit;
                        if ($sku) {
                            $skus = explode(",", $sku);
                            $command->andWhere(array("IN", "t.sku", $skus));
                        }

                        if (!is_null($siteID)) {
                            $command->andWhere("p.site_id='{$siteID}'");
                        }
                        if ($itemID) {
                            $command->andWhere("t.item_id='{$itemID}'");
                        }
                        if ($filterAccountIds) {
                            $command->andWhere(array("NOT IN", "t.account_id", $filterAccountIds));
                        }
                        $variantListing = $command->queryAll();
                        if ($bug) {
                            echo "<br/>sql:{$command->text}<br/>";
                            echo "<br/>======variantListing======<br/>";
                            print_r($variantListing);
                        }
                        if ($variantListing) {
                            if ($bug) {
                                $isContinue = false;
                            } else {
                                $isContinue = true;
                            }

                            $listing = array();
                            foreach ($variantListing as $variant) {
                                //hongkong跳过 2017-02-15
                                if (strtolower($variant['location']) == 'hong kong' || strtolower($variant['location']) == 'hongkong') {
                                    continue;
                                }
                                //检测当天是否已经运行了，此表，加此判断之后数据量会降很多，并且会定时进行清理记录数据 lihy add 2016-02-14
                                if ($ebayZeroStockSKUModel->checkHadRunningForDay($variant['sku_online'], $variant['account_id'], $variant['site_id'])) {
                                    continue;
                                }
                                $variant['type'] = $type;
                                $listing[] = $variant;
                            }
                            unset($variantListing);
                            $skuMapArr = array();
                            $skuMapList = array();
                            if ($bug) {
                                echo "<br/>======Listing======<br/>";
                                print_r($listing);
                            }
                            if (!$listing) {
                                continue;
                            }
                            $skuList = array();
                            foreach ($listing as $list) {
                                $skuMapArr[] = $list['sku'];
                                $key = $list['item_id'] . "-" . $list['sku_online'];
                                $skuMapList[$list['sku']][$key] = $list;
                                $skuList[] = array('sku' => $list['sku']);
                            }
                            if ($bug) {
                                echo "<br>=========skuMapList=========<br/>";
                                print_r($skuMapList);
                            }

                            if (!$norunle) {
                                $conditions1 = $conditions;
                                $conditions1 .= " AND t.sku in(" . MHelper::simplode($skuMapArr) . ")";
                                if ($nowTime < strtotime("2017-02-15 00:00:00")) {//2月6号前用此规则
                                    $skuSalesTable = "ueb_sync.ueb_sku_sales";
                                    $command = $wareSkuMapModel->getDbConnection()->createCommand()
                                        ->from($wareSkuMapModel->tableName() . " as t")
                                        ->leftJoin("ueb_product." . Product::model()->tableName() . " as p", "p.sku=t.sku")
                                        ->leftJoin($skuSalesTable . " as s", "s.sku=t.sku")
                                        ->where($conditions1)
                                        ->select($select)
                                        ->order("t.available_qty asc");
                                    $skuList = $command->queryAll();

                                } else {
                                    $skuList = $wareSkuMapModel->$method($conditions1, array(), '', $select);
                                }
                            }

                            if ($bug) {
                                echo "<br/>========{getMapCondition1} {$conditions1} =========<br/>";
                                echo "<br/>============skuList==========<br/>";
                                print_r($skuList);
                            }
                            if (!$skuList) {
                                continue;
                            }
                            $newListing = array();
                            foreach ($skuList as $list) {
                                if (isset($skuMapList[$list['sku']])) {
                                    foreach ($skuMapList[$list['sku']] as $key => $val) {
                                        $newListing[$key] = $val;
                                    }

                                }
                            }
                            if ($bug) {
                                echo "<br>=========newListing=========<br/>";
                                print_r($newListing);
                            }

                            if (!$newListing) {
                                continue;
                            }


                            if ($bug) {
                                echo "<br/>=========begin:foreach=========<br/>";
                            }
                            if ($norun) {
                                echo "<br/>======norun========<br/>";
                                continue;//不执行运行
                            }


                            $reviseInventoryStatusRequest = new ReviseInventoryStatusRequest();
                            $reviseInventoryStatusRequest->setAccount($accountID);
                            $count = 0;
                            $maxcount = 3;
                            $currentSku = array();
                            $itemstr = "";
                            foreach ($newListing as $k => $list) {
                                if ($bug) {
                                    echo "<br/>========list==========<br/>";
                                    var_dump($list);
                                }
                                //获取最新记录,并且在7天之内的
                                $lastCreateTime = date("Y-m-d H:i:s", time() - 7 * 24 * 3600);
                                $lastRecord = $ebayZeroStockSKUModel->getLastOneByCondition("product_id=:product_id and seller_sku=:seller_sku and account_id=:account_id and is_restore=:is_restore and status=:status and type=:type and create_time>=:create_time", array(
                                    ':product_id' => $list['item_id'], ':seller_sku' => $list['sku_online'], ':account_id' => $accountID, ':is_restore' => 0, ':status' => 2, ':type' => $type, ':create_time' => $lastCreateTime
                                ));
                                if ($lastRecord) {
                                    if ($bug) {
                                        echo "<br>======last record : continue=====<br>";
                                    }
                                    continue;
                                }
                                ++$count;
                                if ($list['is_multiple'] == 1) {
                                    $reviseInventoryStatusRequest->setSku($list['sku_online']);
                                }
                                $reviseInventoryStatusRequest->setItemID($list['item_id']);
                                $reviseInventoryStatusRequest->setQuantity(0);
                                $reviseInventoryStatusRequest->push();
                                $currentSku[] = $list;
                                $itemstr .= $list['item_id'] . ",sku:" . $list['sku_online'] . "  ";
                                if ($count == $maxcount) {
                                    $count = 0;
                                    $message = $this->_startSendRequest($reviseInventoryStatusRequest, $currentSku, $type);
                                    $msg .= "<br/> accountID: {$accountID}:{$itemstr} " . $message;
                                    $currentSku = array();
                                    $itemstr = "";
                                }
                            }

                            if ($count > 0) {
                                $count = 0;
                                $message = $this->_startSendRequest($reviseInventoryStatusRequest, $currentSku, $type);
                                $msg .= "<br/> accountID: {$accountID}:{$itemstr} " . $message;
                                $currentSku = array();
                                $itemstr = "";
                            }


                            if ($bug) {
                                echo "<br/>=========end:foreach=========<br/>";
                            }
                        } else {
                            $isContinue = false;
                        }
                    } while ($isContinue);
                    $logModel->setSuccess($logID, $msg);
                } catch (Exception $e) {
                    if (isset($logID) && $logID) {
                        $logModel->setFailure($logID, $e->getMessage());
                    }
                    if ($bug) {
                        echo "<br/>=={$accountID}===Failuer======<br/>";
                        echo $e->getMessage() . "<br/>";
                    }
                }
            }
        } else {
            //循环每个账号发送一个拉listing的请求
            $accountList = EbayAccount::model()->getIdNamePairs();
            foreach ($accountList as $accountID => $accountName) {
                MHelper::runThreadSOCKET('/' . $this->route . '/account_id/' . $accountID . '/sku/' . $sku . '/limit/' . $limit);
                sleep(1);
            }
        }
    }



    /**
     * @param $accountId
     * @param null $itemId
     * @author ketu.lai
     * @desc 定时恢复listing为在线状态，在线可用库存为0， 仓库41可用库存>=3，不侵权的listing库存
     * @link /ebay/ebayproduct/restoreskufromzerostock/account_id/10/sku/55034
     */
    public function actionRestoreSkuFromZeroStock()
    {
        set_time_limit(5 * 3600);
        ini_set('display_errors', true);
        error_reporting(E_ALL);

        $accountId = Yii::app()->request->getParam("account_id", null);
        $testSku = Yii::app()->request->getParam("sku", null);

        /**
         * account set
         */

        if ($accountId) {
            try {
                $accountInfo = EbayAccount::model()->getAccountInfoById($accountId);
                if (!$accountInfo) {
                    throw new Exception('No account found');
                }

                $logModel = new EbayLog();
                $eventName = EbayZeroStockSku::EVENT_RESTORE_STOCK;

                //检测是否可以允许
                if (!$logModel->checkRunning($accountInfo['id'], $eventName)) {
                   // Yii::app()->end("Active event exists");
                    throw new Exception('There Exists An Active Event');
                }
                /**
                 * setup log
                 */
                $logId = $logModel->prepareLog($accountInfo['id'], $eventName);

                if (!$logId) {
                    throw new Exception('Create Log Failure');
                }
                //如果是海外仓账号则直接退出
                if (in_array($accountInfo['id'], EbayAccount::$OVERSEAS_ACCOUNT_ID)) {
                    $logModel->setFailure($logId, 'Overseas account');
                    throw new Exception('Overseas account');
                    //Yii::app()->end("Oversea account");
                }

                try {

                    $logModel->setRunning($logId); // comment it to test


                    $availableQtyCondition = 3; // 条件 可用库存>=3
                    $offset = 0;
                    $limit = 1000;
                    $processedListingCount = 0;
                    $ebayZeroStockSkuModel = new EbayZeroStockSku();
                    while (true) {
                        /**
                         * sku array to filter in warehouse
                         */
                        $skuFilter = array();
                        $availableListings = array();
                        $skuItemMapper = array();

                        /**
                         * find match listing;
                         */
                        $productCollection = EbayProductVariation::model()->getOnlineListingsWithZeroStock($accountInfo['id'], $limit, $offset, $testSku);

                        if (!$productCollection) {
                            break;
                        }

                        $offset += $limit;

                        foreach ($productCollection as $productInfo) {
                            $skuItemMapper[] = array(
                                'itemId' => $productInfo['item_id'],
                                'onlineSku' => $productInfo['sku_online'],
                                'sku'=> $productInfo['sku'],
                                'accountId' => $productInfo['account_id'],
                                'siteId' => $accountInfo['store_site'],
                                'relistQty' => $accountInfo['relist_qty'],
                                'autoReviseQty' => $accountInfo['auto_revise_qty'],
                                'addQty' => $accountInfo['add_qty'],
                                'oldQuantity' => $productInfo['quantity_available']
                            );
                            $skuFilter[] = $productInfo['sku'];
                        }

                        //$conditions .= "p.sku IN (:sku) AND t.warehouse_id = :warehouseId AND t.available_qty>=:availableQty AND (pi.infringement=:infringement OR pi.security_level = 'A' OR ISNULL(pi.sku)) ";
                        $productData = WarehouseSkuMap::model()->getFilterProductDataWithSkuList(array_unique($skuFilter), array(
                            ':availableQty' => $availableQtyCondition
                        ));

                        if (!$productData) {
                            continue;
                        }

                        foreach ($productData as $product) {
                            // 符合库存条件的SKU 及数量
                            $availableListings[] = $product['sku'];
                        }
                        unset($productData);

                        foreach ($skuItemMapper as $key=> $productData) {

                            $sku = $productData['sku'];

                            // 过滤不符合库存条件的Ebay Listing数据
                            if (!in_array($sku, $availableListings)) {
                                continue;
                            }



                            // 检测可用数量跟账号上传数量

                            $qty = $productData['relistQty'];

                            /* 去除这个限制 20170227
                            //仓库可用库存
                            $availableQty = $availableListings[$productData['sku']];
                            if ($productData['autoReviseQty']) {
                                if ($availableQty > $productData['addQty']) {
                                    $qty = $productData['addQty'];
                                } else {
                                    $qty = $availableQty;
                                }
                            }
                            */


                            //$qty = 0;
                            /** @var  $insertData data for EbayZeroStockSku */
                            $insertData = array(
                                'sku' => $sku,
                                'seller_sku' => $productData['onlineSku'],
                                'product_id' => $productData['itemId'],
                                'account_id' => $productData['accountId'],
                                'site_id' => $productData['siteId'],
                                'old_quantity' => $productData['oldQuantity'],
                                'set_quantity' => $qty,
                                'type' => 9,
                                'status' => EbayZeroStockSku::STATUS_PENGDING,
                                'is_restore' => 1,
                                'restore_time' => date("Y-m-d H:i:s"),
                                'create_time' => date("Y-m-d H:i:s"),
                                'restore_num' => 0,
                                'restore_quantity' => $qty,
                                'msg' => ''
                            );

                            // 检测当天是否处理过
                            $hasRecord = EbayZeroStockSku::model()->checkIfRanToday($productData['itemId'], $productData['accountId'], $productData['onlineSku']);

                            if (!$hasRecord) {
                                /**
                                 *  build ReviseInventoryStatusRequest and send
                                 */
                                $reviseInventoryStatusRequest = new ReviseInventoryStatusRequest();
                                $reviseInventoryStatusRequest->setAccount($productData['accountId']);
                                $reviseInventoryStatusRequest->setSiteID($productData['siteId']);
                                $reviseInventoryStatusRequest->setSku($productData['onlineSku']);
                                $reviseInventoryStatusRequest->setItemID($productData['itemId']);
                                $reviseInventoryStatusRequest->setQuantity($qty);
                                $reviseInventoryStatusRequest->push();

                                $reviseInventoryStatusRequest->setRequest()->sendRequest()->getResponse(); // comment to test

                                if ($reviseInventoryStatusRequest->getIfSuccess()) {    //online
                                //if (true) { //for test popure

                                    $insertData['status'] = EbayZeroStockSku::STATUS_SUCCESS;
                                    $insertData['msg'] = Yii::t("ebay", "update Qty from :from to :to success", array(":from" => $productData['oldQuantity'], ':to' => $qty));

                                    /**
                                     * 处理成功待恢复 状态处理
                                     */
                                    EbayZeroStockSku::model()->updateExistsRecordWithData(
                                        array(
                                            'is_restore' => 1,
                                            'restore_time' => date("Y-m-d H:i:s"),
                                        ),
                                        "product_id=:itemId AND account_id=:accountId AND seller_sku=:onlineSku AND status=:status AND is_restore=0",
                                        array(
                                            ":itemId" => $productData['itemId'],
                                            ':accountId' => $productData['accountId'],
                                            ':onlineSku' => $productData['onlineSku'],
                                            ':status' => EbayZeroStockSku::STATUS_SUCCESS
                                        )
                                    );


                                } else {
                                    $insertData['status'] = EbayZeroStockSku::STATUS_FAILURE;
                                    $insertData['msg'] = $reviseInventoryStatusRequest->getErrorMsg();
                                }
                            } else {
                                $insertData['status'] = EbayZeroStockSku::STATUS_FAILURE;
                                $insertData['is_restore'] = 0;
                                $insertData['msg'] = Yii::t('ebay', 'Action already ran within 1 day');
                            }

                            $processedListingCount++;

                            // 插入更改库存日志
                            $ebayZeroStockSkuModel->saveData($insertData);

                        } //loop listings

                        // 查询结果不够 $limit 数量 跳出while
                        if (count($productCollection) < $limit) {
                            break;
                        }
                    }
                    $logModel->setSuccess($logId, Yii::t('ebay', 'Total :count Listing', array(':count' => $processedListingCount)));


                } catch (\Exception $e) {
                    $logModel->setFailure($logId, $e->getMessage());
                }
                //Yii::app()->end();
            }catch (Exception $e) {
                echo $e->getMessage();
            }

        } else { /* no account id set, loop all ebay account*/
            $accountList = EbayAccount::model()->getIdNamePairs();
            foreach ($accountList as $accountId => $accountName) {

                MHelper::runThreadSOCKET('/' . $this->route . '/account_id/' . $accountId);
                sleep(1);


                // test
                /*
                $fp = fsockopen("localhost", 80, $errorNo, $errorMsg, 60);
                if ($fp) {
                    $out = "GET /market/". $this->route . '/account_id/' . $accountId." HTTP/1.1\r\n";
                    $out .= "Host: localhost\r\n";
                    $out .= "Connection: Close\r\n\r\n";
                    fwrite($fp, $out);
                    while (!feof($fp)) {
                        echo fgets($fp, 128);
                    }
                    fclose($fp);
                }
                */
            }
        }


    }

    /**
     * @desc 恢复从自动置为0的sku的库存
     * @link /ebay/ebayproduct/restoreskustockfromzerostocksku/account_id/xx/site_id/xx/bug/1/norun/1/norule/1
     */
    public function actionRestoreskustockfromzerostocksku()
    {
        set_time_limit(5 * 3600);
        $accountID = Yii::app()->request->getParam('account_id');
        $type = Yii::app()->request->getParam('type');
        $siteID = Yii::app()->request->getParam('site_id');
        $bug = Yii::app()->request->getParam('bug');
        $norun = Yii::app()->request->getParam('norun');
        $norule = Yii::app()->request->getParam('norule');
        $itemID = Yii::app()->request->getParam('item_id');
        $sku = Yii::app()->request->getParam('sku');
        if (!$type) $type = 1;//强制设置，后续改
        if ($accountID) {
            try {
                $accountInfo = EbayAccount::model()->getAccountInfoById($accountID);
                $time = date("Y-m-d H:i:s");
                //写log
                $logModel = new EbayLog();
                $eventName = EbayZeroStockSku::EVENT_RESTORE_STOCK;
                $logID = $logModel->prepareLog($accountID, $eventName);
                if (!$logID) {
                    throw new Exception('Create Log Failure');
                }
                //检测是否可以允许
                if (!$logModel->checkRunning($accountID, $eventName)) {
                    throw new Exception('There Exists An Active Event');
                }
                if (in_array($accountID, EbayAccount::$OVERSEAS_ACCOUNT_ID)) {//如果是海外仓账号则直接退出
                    $logModel->setFailure($logID, 'overseas account');
                    throw new Exception('overseas account');
                }
                $startTime = date("Y-m-d H:i:s");
                //设置运行
                $logModel->setRunning($logID);
                //@todo
                //1、获取对应的置为0的sku列表
                //2、寻找对应sku的可用库存数量
                $zeroStockSKUModel = new EbayZeroStockSku();
                $wareHouseModel = new WarehouseSkuMap();
                $beforeDay = date("Y-m-d H:i:s", strtotime("-90 days"));
                $conditions = "t.is_restore=0 and t.status=2 and t.account_id={$accountID} and create_time>'{$beforeDay}' and type <>6";

                if (!is_null($siteID)) {
                    $conditions .= " and t.site_id='{$siteID}'";
                }
                if ($itemID) {
                    $conditions .= " and t.product_id='{$itemID}'";
                }
                if ($sku) {
                    $conditions .= " and t.sku='{$sku}'";
                }

                $limit = 1000;
                $offset = 0;
                $msg = "";
                /**
                 *    额度数量低于10万，库存调整为20，账号： 11 12 14 20 26 A3 B8 H2 H4 L4 M O3；
                 * --->'11', '12', '14', '20', '26', 'A3', 'B8', 'H2', 'H4', 'L4', 'M', 'O3'
                 * --->数据库对应的id: 13,31,33,34,37,38,39,54,55,57,61,64
                 *
                 *    额度数量为10至100万, 库存调整为50，账号： 15 25 H3 S2 S7；
                 * --->'15', '25', 'H3', 'S2', 'S7'
                 * --->数据库对应的id：25,27,30,58,63
                 *
                 *    额度数量大于100万, 库存调整为100，账号：除海外仓和上面提及的账号外的其他账号.
                 * --->海外仓账号：'M','H2','11','12','14','18','19','24'
                 * --->数据库对应id：13,37,54,55,57,59,60,62
                 * */
                /* $twentyAccountIds = array(13,31,33,34,37,38,39,54,55,57,61,64);
                     $fiftyAccountIds = array(25,27,30,58,63);
                    $restoreSkuNum = 0;
                    if(in_array($accountID, $twentyAccountIds)){
                    $restoreSkuNum = 20;
                    }elseif(in_array($accountID, $fiftyAccountIds)){
                    $restoreSkuNum = 50;
                    }else{
                    $restoreSkuNum = 100;
                    } */

                $minTime = "0000-00-00 00:00:00";
                $maxTime = date("Y-m-d H:i:s", time() - 60);
                $conditions .= " and t.restore_time>='{$minTime}' and t.restore_time<='{$maxTime}'";
                if ($bug) {
                    echo "<br>===conditions:{$conditions} ===<br>";
                }
                do {
                    //@todo 由于自身状态更改了，所以这里用了偏移量会造成漏取，后面觉得采用一个时间标记
                    $skuList = $zeroStockSKUModel->getDbConnection()->createCommand()
                        ->from($zeroStockSKUModel->tableName() . " as t")
                        ->select("t.*")
                        ->where($conditions)
                        ->limit($limit)
                        //->group('product_id')
                        ->order("sku asc")
                        ->queryAll();
                    //$offset += $limit;
                    if ($bug) {
                        echo "<br>======skuList======<br/>";
                        $this->print_r($skuList);
                    }

                    if ($norun) {
                        echo "<br/>========norun=======<br/>";
                        continue;
                    }
                    if ($skuList) {
                        $isContinue = true;
                        if (!$norule) {
                            //匹配出来符合要求的
                            $skuArrs = array();
                            foreach ($skuList as $list) {
                                $skuArrs[] = $list['sku'];
                            }
                            $nowTime = time();
                            if ($nowTime < strtotime("2017-02-15 00:00:00")) {//2月6号前用此规则        2月8日修改时间调整为2月15号之前  by qzz
                                $skuSalesTablename = "ueb_sync.ueb_sku_sales";
                                $warecondition = "t.available_qty >= IFNULL(s.day_sale_num,5) and t.sku in ( " . MHelper::simplode($skuArrs) . " ) and t.warehouse_id=41";
                                $mapList = $wareHouseModel->getDbConnection()->createCommand()->select("t.sku")
                                    ->from($wareHouseModel->tableName() . " as t")
                                    ->leftJoin($skuSalesTablename . " as s", "s.sku=t.sku AND s.day_sale_num > 5 ")
                                    ->where($warecondition)
                                    ->queryAll();
                            } else {
                                $sql = "select sku from ueb_warehouse.ueb_warehouse_sku_map where
	    							available_qty>=3 and sku in ( " . MHelper::simplode($skuArrs) . " ) and warehouse_id=41";
                                $mapList = $wareHouseModel->getDbConnection()->createCommand($sql)->queryAll();
                            }

                            if ($bug) {
                                echo "<br/>sql:{$sql}<br/>";
                                echo "<br/>=======mapList=======<br/>";
                                print_r($mapList);
                            }
                            $skuMapArrs = array();
                            if ($mapList) {
                                foreach ($mapList as $list) {
                                    $skuMapArrs[$list['sku']] = $list['sku'];
                                }

                            }
                            $newLists = array();
                            $skuIDs = array();
                            $noMatchId = array();
                            foreach ($skuList as $list) {
                                if (isset($skuMapArrs[$list['sku']])) {
                                    $newLists[$list['site_id']][] = $list;
                                } else {
                                    $noMatchId[] = $list['id'];
                                }
                            }
                            if ($noMatchId) {
                                //@todo 更新时间更新
                                $zeroStockSKUModel->getDbConnection()
                                    ->createCommand()
                                    ->update($zeroStockSKUModel->tableName(), array('restore_time' => date("Y-m-d H:i:s")), array("IN", "id", $noMatchId));
                            }
                            unset($skuList);

                        } else {
                            foreach ($skuList as $list) {
                                $newLists[$list['site_id']][] = $list;
                            }

                            unset($skuList);
                        }
                        if ($bug) {
                            echo "<br/>======newLists======<br/>";
                            print_r($newLists);
                        }
                        if (empty($newLists)) continue;
                        foreach ($newLists as $siteID => $newList) {
                            $reviseInventoryStatusRequest = new ReviseInventoryStatusRequest();
                            $reviseInventoryStatusRequest->setAccount($accountID);
                            $reviseInventoryStatusRequest->setSiteID($list['site_id']);
                            $count = 0;
                            $maxcount = 3;
                            $currentSku = array();
                            foreach ($newList as $list) {
                                ++$count;
                                //$restoreNum = $list['old_quantity'];
                                $restoreNum = $accountInfo['relist_qty'];//恢复数量修改为重新刊登数量
                                //针对不自动补库存帐号数量的确定
                                if ($accountInfo['auto_revise_qty'] == 1) {
                                    $stockInfo = WarehouseSkuMap::model()->getListByCondition("available_qty", "warehouse_id=41 and sku='{$list['sku']}'");
                                    if ($stockInfo[0]['available_qty'] > $accountInfo['add_qty']) {
                                        $restoreNum = $accountInfo['add_qty'];
                                    } else {
                                        $restoreNum = $stockInfo[0]['available_qty'];
                                    }
                                }
                                $reviseInventoryStatusRequest->setSku($list['seller_sku']);
                                $reviseInventoryStatusRequest->setItemID($list['product_id']);
                                $reviseInventoryStatusRequest->setQuantity($restoreNum);
                                $reviseInventoryStatusRequest->push();
                                $list['restore_quantity'] = $restoreNum;
                                $currentSku[] = $list;
                                if ($count == $maxcount) {
                                    $count = 0;
                                    $msg .= " accountID:{$accountID} " . $this->_startSendRequest($reviseInventoryStatusRequest, $currentSku, $type, 'restore');
                                    $currentSku = array();
                                }
                            }

                            if ($count > 0) {
                                $count = 0;
                                $msg .= " accountID:{$accountID} " . $this->_startSendRequest($reviseInventoryStatusRequest, $currentSku, $type, 'restore');
                                $currentSku = array();
                            }
                        }

                    } else {
                        $isContinue = false;
                    }
                } while ($isContinue);
                $logModel->setSuccess($logID, 'done');
            } catch (Exception $e) {
                if (isset($logID) && $logID) {
                    $logModel->setFailure($logID, $e->getMessage());
                }
                if ($bug) {
                    echo $e->getMessage();
                }
            }

        } else {
            //循环每个账号发送一个拉listing的请求
            $accountList = EbayAccount::model()->getIdNamePairs();
            foreach ($accountList as $accountID => $accountName) {
                MHelper::runThreadSOCKET('/' . $this->route . '/account_id/' . $accountID . '/type/' . $type . "/sku/" . $sku . "/item_id/" . $itemID);
                sleep(1);
            }
        }
    }

    public function actionGetusercases()
    {
        $accountID = Yii::app()->request->getParam('id');
        if (empty($accountID)) exit('账号id');
        $getUserCaseRequest = new GetUserCasesRequest();
        $getUserCaseRequest->setAccount($accountID);
        //$getUserCaseRequest->setSiteID(15);
        $response = $getUserCaseRequest->setRequest()->sendRequest()->getResponse();

        $this->print_r($response);
        EXIT;
        $request = new GetUserDisputesRequest;
        $request->setAccount($accountID);
        $request->setSiteID(15);
        $request->setModTimeFrom("2015-12-01T00:00:00");
        $request->setModTimeTo("2016-03-07T00:00:00");
        $response = $request->setRequest()->sendRequest()->getResponse();
        $this->print_r($response);
    }

    /**
     * @desc 系统自动导入下线sku, 条件：待清仓且可用库存小于等于0
     * @link /ebay/ebayproduct/autoimportofflinetask
     */
    public function actionAutoimportofflinetask()
    {
        set_time_limit(0);
        ini_set('display_errors', true);
        error_reporting(E_ALL & ~E_STRICT);

        $sku = trim(Yii::app()->request->getParam('sku', ''));
        $nowTime = date("Y-m-d H:i:s");
        $productTemp = ProductTemp::model();
        $cmd = $productTemp->getDbConnection()->createCommand()
            ->select("count(*) as total")
            ->from($productTemp->tableName())
            ->where("product_status=6 and available_qty<=0")
            ->andWhere("product_is_multi!=2");
        $sku != '' && $cmd->andWhere("sku='{$sku}'");
        $res = $cmd->queryRow();
        $total = empty($res) ? 0 : $res['total'];
        if ($total == 0) {
            die('没有可导入的数据');
        }
        $pageSize = 2000;
        $pageCount = ceil($total / $pageSize);
        for ($page = 1; $page <= $pageCount; $page++) {
            $offset = ($page - 1) * $pageSize;
            $cmd = $productTemp->getDbConnection()->createCommand()
                ->select("sku")
                ->from($productTemp->tableName())
                ->where("product_status=6 and available_qty<=0")
                ->andWhere("product_is_multi!=2")
                ->order("sku asc")
                ->limit($pageSize, $offset);
            $sku != '' && $cmd->andWhere("sku='{$sku}'");
            $res = $cmd->queryAll();
            if (empty($res)) {
                continue;
            }
            foreach ($res as $v) {
                $rows = array();
                $variationInfos = EbayProductVariation::model()->filterByCondition('p.account_id,p.location', " p.item_status=1  and v.sku='{$v['sku']}' and p.account_id not in(" . implode(',', EbayAccount::$OVERSEAS_ACCOUNT_ID) . ") ");
                if (!empty($variationInfos)) {
                    foreach ($variationInfos as $vs) {
                        //hongkong跳过 2017-02-15
                        if (strtolower($vs['location']) == 'hong kong' || strtolower($vs['location']) == 'hongkong') {
                            continue;
                        }
                        //排除海外仓发货listing

                        $rows[] = array(
                            'sku' => $v['sku'],
                            'account_id' => $vs['account_id'],
                            'status' => 0,
                            'create_user_id' => (int)Yii::app()->user->id,
                            'create_time' => $nowTime,
                            'type' => 2,//系统导入
                        );
                    }
                }
                if ($rows) {
                    $res = EbayOfflineTask::model()->insertBatch($rows);
                }
            }
        }
        Yii::app()->end('finish');
    }

    /**
     * @desc 导入批量下线SKU
     */
    public function actionOfflineimport()
    {
        set_time_limit(0);
        ini_set('memory_limit', '2048M');
        $model = new EbayProduct();
        if (Yii::app()->request->isPostRequest) {
            $accountIDs = Yii::app()->request->getParam('account_id');
            $type = $_FILES['offline_file']['type'];
            $tmpName = $_FILES['offline_file']['tmp_name'];
            $error = $_FILES['offline_file']['error'];
            $size = $_FILES['offline_file']['size'];
            $fileName = $_FILES['offline_file']['name'];
            $errors = '';
            switch ($error) {
                case 0:
                    break;
                case 1:
                case 2:
                    $errors = Yii::t('aliexpress_product', 'File Too Large');
                    break;
                case 3:
                    $errors = Yii::t('aliexpress_product', 'File Upload Partial');
                    break;
                case 4:
                    $errors = Yii::t('aliexpress_product', 'No File Upload');
                    break;
                case 5:
                    $errors = Yii::t('aliexpress_product', 'Upload File Size Zero');
                    break;
                default:
                    $errors = Yii::t('aliexpress_product', 'Unknow Error');
            }
            if (!empty($errors)) {
                echo $this->failureJson(array('message' => $errors));
                Yii::app()->end();
            }
            if (empty($accountIDs)) {
                echo $this->failureJson(array('message' => Yii::t('aliexpress_product', 'Not Select Account')));
                Yii::app()->end();
            }
            if (strpos($fileName, '.csv') == false) {
                echo $this->failureJson(array('message' => Yii::t('aliexpress_product', 'Please Upload CSV File')));
                Yii::app()->end();
            }
            $fp = fopen($tmpName, 'r');
            if (!$fp) {
                echo $this->failureJson(array('message' => Yii::t('aliexpress_product', 'Open File Failure')));
                Yii::app()->end();
            }
            $row = 0;
            $data = array();
            while (!feof($fp)) {
                $row++;
                $rows = fgetcsv($fp, 1024);
                if ($row == 1) continue;
                $sku = trim($rows[0]);
                if (empty($sku)) continue;
                foreach ($accountIDs as $accountID) {
                    $data[] = array(
                        'sku' => $sku,
                        'account_id' => $accountID,
                        'status' => 0,
                        'create_user_id' => Yii::app()->user->id,
                        'create_time' => date('Y-m-d H:i:s'),
                        'type' => 1,//手工导入
                    );
                }
                if ($row % 50 == 0) {
                    $res = EbayOfflineTask::model()->insertBatch($data);
                    $data = array();
                }
            }

            if (!empty($data)) {
                $res = EbayOfflineTask::model()->insertBatch($data);
            }
            fclose($fp);
            echo $this->successJson(array(
                'message' => Yii::t('aliexpress_product', 'Batch Offline Task Add Successful'),
                'callbackType' => 'closeCurrent'
            ));
            Yii::app()->end();
        }
        $accountList = EbayAccount::getAbleAccountList();
        $accountList_no_overseas = array();
        foreach ($accountList as $key => $detail) {
            if (in_array($detail['id'], EbayAccount::$OVERSEAS_ACCOUNT_ID)) {//如果是海外仓账号则直接退出
                continue;
            }
            $accountList_no_overseas[$key] = $detail;
        }
        $this->render('offline_import', array('account_list' => $accountList_no_overseas, 'model' => $model));
    }

    /**
     * @desc 下线批量下线任务的产品
     * @link /ebay/ebayproduct/processofflinetask
     */
    public function actionProcessofflinetask()
    {
        set_time_limit(0);
        ini_set('memory_limit', '2048M');

        $time = time();
        $flag_while = true;
        while ($flag_while) {
            $exe_time = time();
            if (($exe_time - $time) >= 36000) {
                exit('执行超过10小时');
            }
            $res = EbayOfflineTask::model()->getDbConnection()->createCommand()
                ->from("ueb_ebay_offline_task")
                ->select('id, sku, account_id, create_user_id')
                ->where("status = 0")
                ->limit(1000)
                ->queryAll();
            $taskList = array();
            $taskList_variation = array();
            if (!empty($res)) {
                foreach ($res as $row) {
                    $accountID = $row['account_id'];
                    $data = array(
                        'process_time' => date('Y-m-d H:i:s'),
                        'status' => 1,
                    );
                    //根据sku和账号先查product表， product表查不到再跨variation表 listing_type = StoresFixedPrice，FixedPriceItem //PersonalOffer
                    $ebayProductModel = new EbayProduct();
                    $command = $ebayProductModel->getDbConnection()->createCommand()
                        ->from($ebayProductModel->tableName())
                        ->select("sku, sku_online, account_id, site_id, item_id, quantity,listing_type")
                        ->where("sku = '" . $row['sku'] . "'")
                        ->andWhere("account_id= '" . $row['account_id'] . "'")
                        ->andWhere("is_multiple=0")
                        ->andWhere('item_status=' . EbayProduct::STATUS_ONLINE);
                    $skuOnline = $command->queryAll();

                    if (empty($skuOnline)) {
                        //多属性
                        $ebayProductVariantModel = new EbayProductVariation();
                        $command = $ebayProductVariantModel->getDbConnection()->createCommand()
                            ->from($ebayProductVariantModel->tableName() . " as t")
                            ->leftJoin($ebayProductModel->tableName() . " p", "p.id=t.listing_id")
                            ->select("t.sku, t.sku_online, t.account_id, t.quantity_available, p.site_id,  t.item_id,t.quantity, p.listing_type")
                            ->where("t.sku = '" . $row['sku'] . "'")
                            ->andWhere("t.account_id= '" . $row['account_id'] . "'")
                            ->andWhere("p.is_multiple=1")
                            ->andWhere('p.item_status=' . EbayProduct::STATUS_ONLINE);
                        $sku_variation = $command->queryAll();
                    }

                    if (!empty($skuOnline)) {
                        foreach ($skuOnline as $key => $value) {
                            $taskList[$accountID][$row['id']][$key] = array('sku_online' => $value['sku_online'], 'sku' => $value['sku'], 'item_id' => $value['item_id'], 'listing_type' => $value['listing_type'], 'account_id' => $value['account_id'], 'site_id' => $value['site_id'], 'create_user_id' => $row['create_user_id']);
                        }
                    } elseif (!empty($sku_variation)) {
                        //多属性子sku
                        foreach ($sku_variation as $key => $value) {
                            $taskList_variation[$accountID][$row['id']][$key] = array('sku_online' => $value['sku_online'], 'sku' => $value['sku'], 'item_id' => $value['item_id'], 'listing_type' => $value['listing_type'], 'account_id' => $value['account_id'], 'site_id' => $value['site_id'], 'create_user_id' => $row['create_user_id'], 'quantity_available' => $value['quantity_available']);
                        }
                    } else {
                        EbayOfflineTask::model()->getDbConnection()->createCommand()->update("ueb_ebay_offline_task", array('status' => 3), "id = " . $row['id']);
                    }
                }
            } else {
                $flag_while = false;
            }

            if ($taskList) {
                foreach ($taskList as $accountID => $list) {
                    foreach ($list as $id => $rows_list) {
                        foreach ($rows_list as $rows) {
                            $data = array();
                            if ($rows['listing_type'] == 'Chinese') {
                                $request = new EndItemRequest();
                            } else {
                                $request = new EndFixedPriceItemRequest();
                                $request->setSKU($rows['sku_online']);
                            }
                            $request->setItemID($rows['item_id']);
                            $request->setEndingReason('NotAvailable');
                            $response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
                            if (!$request->getIfSuccess()) {
                                $data['status'] = -1;
                                $data['process_time'] = date('Y-m-d H:i:s');
                                $data['response_msg'] = $request->getErrorMsg();
                            } else {
                                $data['status'] = 2;
                                $data['process_time'] = date('Y-m-d H:i:s');
                                $data['response_msg'] = 'SUCCESS';
                                //记录操作日志
                                EbayProductOffline::model()->addNewData(array(
                                    'item_id' => $rows['item_id'],
                                    'seller_sku' => $rows['sku_online'],
                                    'sku' => $rows['sku'],
                                    'account_id' => $rows['account_id'],
                                    'site_id' => $rows['site_id'],
                                    'create_time' => date('Y-m-d H:i:s'),
                                    'create_user_id' => (int)$rows['create_user_id'],
                                ));
                            }
                        }
                        EbayOfflineTask::model()->getDbConnection()->createCommand()->update("ueb_ebay_offline_task", $data, "id = " . $id);
                    }
                }
            }

            if ($taskList_variation) {
                foreach ($taskList_variation as $accountID => $list) {
                    foreach ($list as $id => $rows_list) {
                        foreach ($rows_list as $rows) {
                            $reviseInventoryStatusRequest = new ReviseInventoryStatusRequest();
                            $reviseInventoryStatusRequest->setItemID($rows['item_id']);
                            $reviseInventoryStatusRequest->setSKU($rows['sku_online']);
                            $reviseInventoryStatusRequest->setQuantity(0);
                            $reviseInventoryStatusRequest->push();
                            $response_variation = $reviseInventoryStatusRequest->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
                            if (!$reviseInventoryStatusRequest->getIfSuccess()) {
                                $data['status'] = -1;
                                $data['process_time'] = date('Y-m-d H:i:s');
                                $data['response_msg'] = $reviseInventoryStatusRequest->getErrorMsg();
                            } else {
                                $data['status'] = 2;
                                $data['process_time'] = date('Y-m-d H:i:s');
                                $data['response_msg'] = 'SUCCESS';
                                //记录操作日志
                                EbayProductOffline::model()->addNewData(array(
                                    'item_id' => $rows['item_id'],
                                    'seller_sku' => $rows['sku_online'],
                                    'sku' => $rows['sku'],
                                    'account_id' => $rows['account_id'],
                                    'site_id' => $rows['site_id'],
                                    'create_time' => date('Y-m-d H:i:s'),
                                    'create_user_id' => (int)$rows['create_user_id'],
                                    'type' => 2,
                                    'old_quantity' => $rows['quantity_available'],
                                ));
                            }
                        }
                        EbayOfflineTask::model()->getDbConnection()->createCommand()->update("ueb_ebay_offline_task", $data, "id = " . $id);
                    }
                }
            }

        }
        exit('DONE');
    }

    /**
     * 导入下架任务表删除没有线上sku的数据
     */
    public function actionDeleteNoOnlineSku()
    {
        EbayProduct::model()->getDbConnection()->createCommand()->delete("ueb_ebay_offline_task", "response_msg = 'no online sku'");
    }


    /**
     * @desc SKU在线自动匹配ebay分类
     */
    public function actionGetskucategoryfromebay()
    {
        set_time_limit(0);
        ini_set("display_errors", true);
        error_reporting(E_ALL);
        ini_set('memory_limit', '2048M');
        $model = new EbayProduct();
        $productModel = new Product();
        $productDesModel = new Productdesc();
        $ebayCategory = new EbayCategory();
        $siteID = 0;
        $accountID = 0;
        //获取该站点下的有效账号
        $accountInfoList = EbayAccountSite::model()->getAbleAccountListBySiteID($siteID);
        if ($accountInfoList) {
            $accountID = $accountInfoList[0]['id'];
        }
        shuffle($accountInfoList);
        $maxKey = count($accountInfoList) - 1;
        $isContinue = false;
        $limit = 1000;
        $offset = 0;
        if ($accountID) {
            do {
                $accountID = 0;
                //一、获取该站点下的有效账号
                /* $key = rand(0, $maxKey);
                $accountInfo = $accountInfoList[$key]; */


                //二、每个账号获取一次
                $accountInfo = array_shift($accountInfoList);
                if (empty($accountInfo)) {
                    break;
                }


                if ($accountInfo) {
                    $accountID = $accountInfo['id'];
                }
                if (!$accountID) {
                    break;
                }
                //日志
                try {
                    $eventName = "get_sku_category";
                    $ebayLogModel = new EbayLog();
                    $logID = $ebayLogModel->prepareLog($accountID, $eventName);
                    if (!$logID) {
                        throw new Exception("不能创建LOG ID");
                    }
                    if (!$ebayLogModel->checkRunning($accountID, $eventName)) {
                        throw  new Exception("Event has exists");
                    }
                    $ebayLogModel->setRunning($logID);
                    // =================================================== START =============================================================//
                    $msg = "";

                    //获取sku、标题
                    $sql = "select p.id, d.sku,d.title from " . $productDesModel->tableName() . " as d left join " . $productModel->tableName() .
                        " as p on p.id=d.product_id where d.language_code='english' and p.product_status<7 and p.online_category_id=0 limit {$offset},{$limit}";
                    $offset += $limit;
                    $list = $productModel->getDbConnection()->createCommand($sql)->queryAll();
                    if ($list) {
                        $isContinue = true;
                        foreach ($list as $val) {
                            $keyword = str_replace(array(' to ', ' for ', ' in ', ' & ', '&', ' + ', ' of ', ' and ', ' or ', ' For ', ' Of ', ' To ', ' And ', ' Of ', ' In '), ' ', $val['title']);
                            //去除成人用语
                            $keyword = str_replace(array('sex', 'Adult', 'Sex', 'Penis', 'Dildo', 'Vagina', 'Pussy', 'Ejaculation'), ' ', $keyword);
                            if (!empty($keyword)) {
                                $request = new GetSuggestedCategoriesRequest();
                                $request->setAccount($accountID);
                                $request->setSiteID($siteID);
                                $request->setQuery($keyword);
                                $response = $request->setRequest()->sendRequest()->getResponse();
                                if ($request->getIfSuccess()) {
                                    $categories = array();
                                    foreach ($response->SuggestedCategoryArray->SuggestedCategory as $suggestedCategory) {
                                        $categoryid = trim($suggestedCategory->Category->CategoryID);
                                        $categorynames = array();
                                        $cateid1 = $cateid2 = 0;
                                        $catename1 = $catename2 = '';
                                        $cateid1 = isset($suggestedCategory->Category->CategoryParentID[0]) ? $suggestedCategory->Category->CategoryParentID[0] : 0;
                                        $cateid2 = isset($suggestedCategory->Category->CategoryParentID[1]) ? $suggestedCategory->Category->CategoryParentID[1] : 0;
                                        $catename1 = isset($suggestedCategory->Category->CategoryParentName[0]) ? $suggestedCategory->Category->CategoryParentName[0] : '';
                                        $catename2 = isset($suggestedCategory->Category->CategoryParentName[1]) ? $suggestedCategory->Category->CategoryParentName[1] : '';
                                        /* foreach ($suggestedCategory->Category->CategoryParentName as $key=>$categoryParentName)
                                         {
                                        $categorynames[] = trim($categoryParentName);
                                        }
                                        $categorynames[] = trim($suggestedCategory->Category->CategoryName);
                                        $data = array(
                                                'sku'		=>	$val['sku'],
                                                'site_id'	=>	$siteID,
                                                'title'		=>	$val['title'],
                                                'cate'		=>	$categoryid,
                                                'cate1'		=>	$cateid1,
                                                'cate2'		=>	$cateid2,
                                                'catename1'	=>	$catename1,
                                                'catename2'	=>	$catename2,
                                                'catename'	=>	implode("->", $categorynames),
                                        ); */
                                        //EbaySkuCategory::model()->getDbConnection()->createCommand()->insert(EbaySkuCategory::model()->tableName(), $data);

                                        if (!$cateid2) {//二级分类为空时赋值一级分类
                                            $cateid2 = $cateid1;
                                            $catename2 = $catename1;
                                        }
                                        $data = array(
                                            'cate_id1' => $cateid1,
                                            'cate_id2' => $cateid2,
                                            'cate_name1' => $catename1,
                                            'cate_name2' => $catename2,
                                            'create_time' => date("Y-m-d H:i:s")
                                        );

                                        $checkExists = ProductCategoryOnline::model()->find("cate_id2=" . $cateid2);
                                        $res = false;
                                        if (!$checkExists) {
                                            //入库
                                            $res = ProductCategoryOnline::model()->getDbConnection()->createCommand()->insert(ProductCategoryOnline::model()->tableName(), $data);
                                        } else {
                                            $res = ProductCategoryOnline::model()->updateByPk($checkExists->id, $data);
                                        }
                                        //更新online_category_id
                                        if ($res)
                                            Product::model()->updateByPk($val['id'], array('online_category_id' => $cateid2));
                                        break;//只取第一条
                                    }
                                } else {
                                    $msg .= ",'{$val['sku']}'";
                                }
                                //sleep(1);
                            }
                        }
                    } else {
                        $isContinue = false;
                    }

                    // ==================================== END =================================== //
                    $ebayLogModel->setSuccess($logID, $msg);
                } catch (Exception $e) {
                    if ($logID) {
                        $ebayLogModel->setFailure($logID, $e->getMessage());
                    }
                }
            } while ($isContinue);
            echo "done!!!!";
        }
    }

    /**
     * @desc 本地仓可用库存数量小于1时，并且产品的采购周期大于14天，进行2倍调价操作
     * @link /ebay/ebayproduct/reviseprice
     */
    public function actionReviseprice()
    {
        set_time_limit(5 * 3600);
        error_reporting(E_ALL);
        ini_set("display_errors", true);
        $itemID = trim(Yii::app()->request->getParam("item_id"));
        $accountID = Yii::app()->request->getParam("account_id");
        $siteID = Yii::app()->request->getParam("site_id");
        $sku = Yii::app()->request->getParam("sku");
        $bug = Yii::app()->request->getParam("bug");
        if ($itemID && !is_array($itemID)) {
            $itemID = array($itemID);
        }
        $limit = 2000;
        $offset = 0;
        $wareHouseModel = new WarehouseSkuMap();
        echo "<pre>";
        echo "StartTime:" . date("Y-m-d H:i:s"), "<br/>";
        do {
            $listing = $variantListing = array();
            $ebayProductModel = new EbayProduct();
            $ebayProductVariantModel = new EbayProductVariation();
            $command = $ebayProductVariantModel->getDbConnection()->createCommand()
                ->from($ebayProductVariantModel->tableName() . " as t")
                ->leftJoin($ebayProductModel->tableName() . " p", "p.id=t.listing_id")
                ->select("t.id, t.sku, t.sku_online, t.sale_price, t.account_id, t.quantity, t.item_id, p.site_id, p.is_multiple")
                ->where('p.item_status=' . EbayProduct::STATUS_ONLINE)
                ->andWhere("p.listing_type in('FixedPriceItem', 'StoresFixedPrice')")//非拍卖下
                ->limit($limit, $offset);
            $offset += $limit;
            if ($itemID) {
                $command->andWhere(array("in", "t.item_id", $itemID));
            }
            if ($accountID) {
                $command->andWhere("t.account_id='{$accountID}'");
            }
            if (!is_null($siteID)) {
                $command->andWhere("p.site_id='{$siteID}'");
            }
            if ($sku) {
                $command->andWhere("t.sku='{$sku}'");
            }
            $listing = $command->queryAll();
            if ($bug) {
                echo "<br/>======sql=====<br/>";
                echo $command->text, "<br/>";
                echo "<br/>==========listing=========<br/>";
                print_r($listing);
                echo "<br/>==========end-listing=========<br/>";
            }

            //从本地listing取出有效的listing
            $newListing = array();
            if ($listing) {
                $isContinue = true;
                $skuArrs = array();
                foreach ($listing as $list) {
                    $skuArrs[] = $list['sku'];
                }
                $beforeTime = date("Y-m-d H:i:s", time() - 45 * 24 * 3600);//36天
                //获取指定SKU对应的产品状态
                $sql = "select p.sku from ueb_product.ueb_product p join ueb_warehouse.ueb_warehouse_sku_map w on w.sku=p.sku where 1 
	    				and w.available_qty<1 and p.product_bak_days>14 and p.sku in ( " . MHelper::simplode($skuArrs) . " ) and w.warehouse_id=41 and p.create_time<='{$beforeTime}'";
                $mapList = $wareHouseModel->getDbConnection()->createCommand($sql)->queryAll();
                if ($bug) {
                    echo "sql:{$sql}<br/>";
                    echo "<br/>============mapList==========<br/>";
                    print_r($mapList);
                    echo "<br/>============end-mapList==========<br/>";
                }

                if (!$mapList) {
                    continue;
                }
                $mapSkuList = array();
                foreach ($mapList as $list) {
                    $mapSkuList[$list['sku']] = $list['sku'];
                }
                foreach ($listing as $list) {
                    if (isset($mapSkuList[$list['sku']])) {
                        $newListing[$list['account_id']][] = $list;
                    }
                }
                if ($bug) {
                    echo "<br/>=============newListing============<br/>";
                    print_r($newListing);
                    echo "<br/>=============END-newListing============<br/>";
                }
                if (!$newListing) {
                    continue;
                }

                $eventName = "stock_revice_price";
                //print_r($newListing);

                //exit;
                foreach ($newListing as $accountID => $skuList) {
                    try {
                        $logModel = new EbayLog();
                        $logID = $logModel->prepareLog($accountID, $eventName);
                        if (!$logID) throw new Exception("LOG ID create failure!!!");
                        if (!$logModel->checkRunning($accountID, $eventName)) {
                            throw new Exception("Has a event exists");
                        }
                        $logModel->setRunning($logID);

                        $reviseInventoryStatusRequest = new ReviseInventoryStatusRequest();
                        $reviseInventoryStatusRequest->setAccount($accountID);
                        $revisePriceLogModel = new EbayStockRevisePriceLog;
                        foreach ($skuList as $list) {
                            if ($bug) {
                                echo "<br/>==============List============<br/>";
                                print_r($list);
                                echo "<br/>==============List-End============<br/>";
                            }
                            //检测是否已经有过记录了
                            $recondition = "sku_online=:sku_online and item_id=:item_id and account_id=:account_id and site_id=:site_id and status=:status";
                            $reparams = array(
                                ':sku_online' => $list['sku_online'],
                                ':item_id' => $list['item_id'],
                                ':account_id' => $list['account_id'],
                                ':site_id' => $list['site_id'],
                                ':status' => 1
                            );
                            $checkExists = $revisePriceLogModel->getRevisePriceLogRow($recondition, $reparams);
                            if ($bug) {
                                echo "<br/>===============checkExists=============<br/>";
                                print_r($checkExists);
                                echo "<br/>=============end-checkExists============<br/>";
                            }
                            //如果是处于未恢复，直接跳过
                            //如果记录在最近1天内，也直接跳过
                            if ($checkExists && $checkExists['restore_status'] == 0) {
                                continue;
                            }
                            if ($checkExists && $checkExists['create_time'] > date("Y-m-d H:i:s", time() - 24 * 3600)) {
                                continue;
                            }

                            $newPrice = $list['sale_price'] * 2;
                            $reviseInventoryStatusRequest->setSku($list['sku_online']);
                            $reviseInventoryStatusRequest->setItemID($list['item_id']);
                            $reviseInventoryStatusRequest->setStartPrice($newPrice);
                            $reviseInventoryStatusRequest->push();
                            $response = $reviseInventoryStatusRequest->setRequest()->sendRequest()->getResponse();
                            if ($bug) {
                                echo "<br/>=================response=================<br/>";
                                print_r($response);
                                echo "<br/>=================end-response=================<br/>";
                            }
                            $reviseInventoryStatusRequest->clean();
                            //收集错误信息
                            $errormsg = $reviseInventoryStatusRequest->getErrorMsg();
                            if ($bug) {
                                echo "<br/>=================errormsg===================<br/>";
                                print_r($errormsg);
                                echo "<br/>=================end-errormsg===================<br/>";
                            }
                            //写入记录表
                            $revisePriceLogData = array(
                                'account_id' => $accountID,
                                'site_id' => $list['site_id'],
                                'sku' => $list['sku'],
                                'sku_online' => $list['sku_online'],
                                'item_id' => $list['item_id'],
                                'old_price' => $list['sale_price'],
                                'change_price' => $newPrice,
                                'create_time' => date("Y-m-d H:i:s"),
                                'update_time' => date("Y-m-d H:i:s"),
                                'restore_status' => 0
                            );
                            //@todo
                            //if($reviseInventoryStatusRequest->getIfSuccess()){
                            if (isset($response->Fees) && $response->Fees) {
                                $feedItemIDs = array();
                                if (!isset($response->Fees[0])) {
                                    $feedItemIDs[] = $response->Fees->ItemID;
                                } else {//返回多个
                                    foreach ($response->Fees as $feed) {
                                        $feedItemIDs[] = $feed->ItemID;
                                    }
                                }
                                if (in_array($list['item_id'], $feedItemIDs)) {
                                    $revisePriceLogData['status'] = 1;
                                } else {
                                    $revisePriceLogData['status'] = 2;
                                    $revisePriceLogData['last_message'] = $errormsg;
                                }
                            } else {
                                $revisePriceLogData['status'] = 2;
                                $revisePriceLogData['last_message'] = $errormsg;
                            }
                            $revisePriceLogModel->addData($revisePriceLogData);
                        }
                        $logModel->setSuccess($logID);
                    } catch (Exception $e) {
                        if ($logID) {
                            $logModel->setFailure($logID, $e->getMessage());
                        }
                        echo $e->getMessage();
                    }
                }
            } else {
                $isContinue = false;
            }
        } while ($isContinue);
        echo "EndTime:" . date("Y-m-d H:i:s"), "<br/>";
        echo "done!!!";
    }

    /**
     * @desc 恢复价格
     * @link /ebay/ebayproduct/restorereviseprice
     * @throws Exception
     */
    public function actionRestorereviseprice()
    {
        set_time_limit(5 * 3600);
        error_reporting(E_ALL);
        ini_set("display_errors", true);
        $accountID = Yii::app()->request->getParam("account_id");
        $siteID = Yii::app()->request->getParam("site_id");
        $itemID = Yii::app()->request->getParam("item_id");
        $limit = Yii::app()->request->getParam("limit", 1000);
        $bug = Yii::app()->request->getParam("bug");
        $offset = 0;
        $revisePriceLogModel = new EbayStockRevisePriceLog();
        $wareHouseModel = new WarehouseSkuMap();
        echo "StartTime:" . date("Y-m-d H:i:s"), "<br/>";
        do {
            $pendingList = $revisePriceLogModel->getPendingRestoreList($limit, $offset, $accountID, $siteID, $itemID);
            $offset += $limit;
            if ($bug) {
                echo "<BR/>============pendingList==========<br/>";
                print_r($pendingList);
                echo "<BR/>============END-pendingList==========<br/>";
            }
            //

            if ($pendingList) {
                $iscontinue = true;
                $skuArrs = array();
                foreach ($pendingList as $list) {
                    $skuArrs[] = $list['sku'];
                }
                //获取库存表中可用库存数量是否达到限定
                //>=3
                $sql = "select sku from ueb_warehouse.ueb_warehouse_sku_map where 
    					available_qty>=3 and sku in ( " . MHelper::simplode($skuArrs) . " ) and warehouse_id=41";
                $mapList = $wareHouseModel->getDbConnection()->createCommand($sql)->queryAll();
                if ($bug) {
                    echo "<br/>sql:$sql<br/>";
                    echo "<br/>==============mapList============<br/>";
                    print_r($mapList);
                    echo "<br/>==============END-mapList============<br/>";
                }

                if (!$mapList) continue;
                $skuMapList = array();
                foreach ($mapList as $list) {
                    $skuMapList[$list['sku']] = $list['sku'];
                }

                $newList = array();
                foreach ($pendingList as $list) {
                    if (isset($skuMapList[$list['sku']]))
                        $newList[$list['account_id']][] = $list;
                }

                if ($bug) {
                    echo "<br/>============newList===========<br/>";
                    print_r($newList);
                    echo "<br/>============end-newList===========<br/>";
                }
                if (!$newList) {
                    continue;
                }
                $eventName = "restore_revise_price";
                //print_r($newList);
                //exit;
                foreach ($newList as $accountID => $lists) {
                    try {
                        $logModel = new EbayLog();
                        $logID = $logModel->prepareLog($accountID, $eventName);
                        if (!$logID) throw new Exception("LOG ID create failure!!!");
                        if (!$logModel->checkRunning($accountID, $eventName)) {
                            throw new Exception("Has a event exists");
                        }
                        $logModel->setRunning($logID);

                        $reviseInventoryStatusRequest = new ReviseInventoryStatusRequest();
                        $reviseInventoryStatusRequest->setAccount($accountID);
                        $count = 0;
                        $maxcount = 3;
                        $currentSku = array();
                        $revisePriceLogModel = new EbayStockRevisePriceLog;
                        foreach ($lists as $list) {
                            if ($bug) {
                                echo "<br/>==========list===========<br/>";
                                print_r($list);
                                echo "<br/>==========end-list===========<br/>";
                            }
                            $newPrice = $list['old_price'];
                            $reviseInventoryStatusRequest->setSku($list['sku_online']);
                            $reviseInventoryStatusRequest->setItemID($list['item_id']);
                            $reviseInventoryStatusRequest->setStartPrice($newPrice);
                            $reviseInventoryStatusRequest->push();
                            $response = $reviseInventoryStatusRequest->setRequest()->sendRequest()->getResponse();
                            if ($bug) {
                                echo "<br/>==========response===========<br/>";
                                $this->print_r($response);
                                echo "<br/>==========end-response===========<br/>";
                            }
                            //收集错误信息
                            $errormsg = $reviseInventoryStatusRequest->getErrorMsg();
                            if ($bug) {
                                echo "<br/>===========errorMsg=============<br/>";
                                print_r($errormsg);
                                echo "<br/>==========end-errorMsg===========<br/>";
                            }
                            $reviseInventoryStatusRequest->clean();

                            $revisePriceLogData = array('update_time' => date("Y-m-d H:i:s"), 'restore_price' => $newPrice);

                            //@todo
                            //if($reviseInventoryStatusRequest->getIfSuccess()){
                            if (isset($response->Fees) && $response->Fees) {
                                $feedItemIDs = array();
                                if (!isset($response->Fees[0])) {
                                    $feedItemIDs[] = $response->Fees->ItemID;
                                } else {//返回多个
                                    foreach ($response->Fees as $feed) {
                                        $feedItemIDs[] = $feed->ItemID;
                                    }
                                }
                                if (in_array($list['item_id'], $feedItemIDs)) {
                                    $revisePriceLogData['restore_status'] = 1;//成功
                                    $revisePriceLogData['last_message'] = "success";
                                } else {
                                    $revisePriceLogData['restore_status'] = 2;//失败
                                    $revisePriceLogData['last_message'] = $errormsg;
                                }
                            } else {
                                $revisePriceLogData['restore_status'] = 2;//失败
                                $revisePriceLogData['last_message'] = $errormsg;
                            }
                            $revisePriceLogModel->updateDataByID($list['id'], $revisePriceLogData);
                        }
                        $logModel->setSuccess($logID);
                    } catch (Exception $e) {
                        if ($logID) {
                            $logModel->setFailure($logID, $e->getMessage());
                        }
                    }
                }
            } else {
                $iscontinue = false;
            }
        } while ($iscontinue);
        echo "EndTime:" . date("Y-m-d H:i:s"), "<br/>";
        echo "done!!!";
    }


    public function actionBatchupdatedesc()
    {
        $ids = Yii::app()->request->getParam("ids");
        $this->render("batchchangedescandpicture",
            array('model' => new EbayProduct(),
                'ids' => $ids,
                'sellerList' => array('描述不符', '主图更新', 'listing在线问题')
            )
        );

    }

    /**
     * @desc 更新详情
     * @throws Exception
     */
    public function actionUpdatedesc()
    {
        set_time_limit(3600);
        ini_set('display_errors', false);
        error_reporting(0);

        $accountID = Yii::app()->request->getParam("account_id");
        $ids = trim(Yii::app()->request->getParam("ids"), ",");
        $types = Yii::app()->request->getParam("types");
        $ebayProductForm = Yii::app()->request->getParam("EbayProduct"); //模拟
        $reasonArr = array('描述不符', '主图更新', 'listing在线问题');
        $reason = isset($reasonArr[$ebayProductForm['seller_id']]) ? $reasonArr[$ebayProductForm['seller_id']] : '';
        try {
            //@todo 测试
            if (!UserSuperSetting::model()->checkSuperPrivilegeByUserId(Yii::app()->user->id)) {
                //throw new Exception("没有权限操作");
            }

            if (!$types) {
                throw new Exception("没有指定操作类型");
            }
            $type = 0;
            foreach ($types as $v) {
                $type += intval($v);
            }
            if (!$ids) {
                throw new Exception("没有指定更新的记录");
            }
            $model = EbayProduct::model();
            $lists = $model->findAll("id in (" . $ids . ")");
            if ($lists) {
                $ebayProductBatchChangeLogModel = new EbayProductBatchChangeLog();
                $nowtime = date("Y-m-d H:i:s");
                foreach ($lists as $list) {
                    /*
                    $res = $model->changOnlineDescriptionByItemID($list['item_id'], $list['account_id'], $type);
                    if(!$res){
                        throw new Exception($model->getExceptionMessage());
                    } */
                    $exists = $ebayProductBatchChangeLogModel->checkStatusByAccountIDAndItemID($list['account_id'], $list['item_id']);
                    if (!$exists) {
                        $addData = array(
                            'item_id' => $list['item_id'],
                            'account_id' => $list['account_id'],
                            'type' => $type,
                            'reason' => $reason,
                            'status' => EbayProductBatchChangeLog::STATUS_DEFAULT,
                            'create_time' => $nowtime,
                            'update_time' => $nowtime,
                            'create_user_id' => intval(Yii::app()->user->id),
                            'last_msg' => '',
                        );
                        $ebayProductBatchChangeLogModel->addData($addData);

                        //取出对应的itemID
                        $itemInfo = EbayProduct::model()->find("item_id='{$list['item_id']}' and account_id='{$list['account_id']}'");
                        $sku = "";
                        if ($itemInfo) {
                            $sku = $itemInfo['sku'];
                        }
                        $variationSKUList = EbayProductVariation::model()->getProductVariantListByCondition("item_id=:item_id and account_id=:account_id", array(':item_id' => $list['item_id'], ':account_id' => $list['account_id']));

                        $variationSKUArr = array();
                        if ($variationSKUList) {
                            foreach ($variationSKUList as $variation) {
                                $variationSKUArr[] = $variation['sku'];
                            }
                        }
                        if ($sku) {
                            $variationSKUArr[] = $sku;
                        }
                        if ($variationSKUArr) {
                            //推送
                            EbayProductImageAdd::model()->addSkuImageUpload($list['account_id'], $variationSKUArr);
                        }
                    }
                }
            } else {
                throw new Exception("没有找到符合条件的listing：需要刊登成功的！");
            }
            echo $this->successJson(array('message' => '任务添加成功，等待后台执行'));
        } catch (Exception $e) {
            echo $this->failureJson(array('message' => $e->getMessage()));
        }
    }

    /**
     * @desc 批量更新备货时间
     */
    public function actionUpdatedispatchtime()
    {
        $ids = trim(Yii::app()->request->getParam('ids', ''));
        $accountIDs = EbayAccount::getAbleAccountList();
        foreach ($accountIDs as $v) {
            $accountArr[$v['id']] = $v['short_name'];
        }
        $locations = EbayProduct::getLocation();
        $this->render("updatedispatchtime",
            array(
                'model' => new EbayProduct(),
                'ids' => $ids,
                'accountArr' => $accountArr,
                'siteArr' => EbaySite::model()->getSiteList(),
                'locations' => $locations,
            )
        );
    }

    /*
     * 调用批量更新备货天数方法
     * ebay/ebayproduct/getbatchupdatedispatchtime
     */
    public function actionGetbatchupdatedispatchtime()
    {
        $type = trim(Yii::app()->request->getParam('type', '3'));
        $dispatchTime = trim(Yii::app()->request->getParam('dispatchtime', 4));
        $location = trim(Yii::app()->request->getParam('location', 'Derby'));
        $route = 'ebay/ebayproduct/Batchupdatedispatchtime';

        $siteArr = EbaySite::getAbleSiteList();
        $accountArr = array(13, 37, 54, 55, 57, 59, 60, 62);//指定海外仓帐号
        //$ebayAccounts = EbayAccount::model ()->getAbleAccountList ();
        foreach ($accountArr as $account) {
            foreach ($siteArr as $site) {
                $url = Yii::app()->request->hostInfo . "/" . $route . "/type/" . $type . "/account_id/" . $account . "/site_id/" . $site['site_id'] . "/dispatchtime/" . $dispatchTime . "/location/" . $location;
                MHelper::runThreadBySocket($url);
                sleep(5);
                //echo $url."<br/>";
            }
        }
    }

    /**
     * @desc 批量更新备货天数
     */
    public function actionBatchupdatedispatchtime()
    {
        set_time_limit(5 * 3600);
        ini_set('display_errors', true);
        error_reporting(E_ALL);
        $type = trim(Yii::app()->request->getParam('type', ''));
        $ids = trim(Yii::app()->request->getParam('ids', ''));
        $accountId = trim(Yii::app()->request->getParam('account_id', ''));
        $siteId = trim(Yii::app()->request->getParam('site_id', ''));
        $dispatchTime = trim(Yii::app()->request->getParam('dispatchtime', ''));
        $location = trim(Yii::app()->request->getParam('location', ''));
        try {
            $lists = array();
            if ($type == '') {
                throw new Exception("非法操作");
            }
            if ($dispatchTime == '' || !preg_match("/^\d{1,}$/", $dispatchTime)) {
                throw new Exception("备货天数必须为大于0整数");
            }
            if ($type == 1 || $type == 3) {//按账号站点
                if ($accountId == '') {
                    throw new Exception("请选择账号");
                }
                if ($siteId === '') {
                    throw new Exception("请选择站点");
                }
                if ($location == '') {
                    $condition = "account_id='{$accountId}' and site_id='{$siteId}' and item_status=1 and handing_time<>{$dispatchTime}";
                } else {
                    $condition = "account_id='{$accountId}' and site_id='{$siteId}' and item_status=1 and location = '" . $location . "' and handing_time<>{$dispatchTime}";
                }
                $lists = EbayProduct::model()->getListByCondition("account_id,item_id,listing_type", $condition);
            } else {//按所选itemid
                if (trim($ids, ',') == '') {
                    throw new Exception("所选item为空，请关闭弹窗选择要更新的item");
                }
                if ($location == '') {
                    $condition = "id in (" . $ids . ") and item_status=1 and handing_time<>{$dispatchTime}";
                } else {
                    $condition = "id in (" . $ids . ") and item_status=1  and location = '" . $location . "' and handing_time<>{$dispatchTime}";
                }
                $lists = EbayProduct::model()->getListByCondition("account_id,item_id,listing_type", $condition);
            }
            if ($lists) {
                $model = new EbayProductBatchChangeLog();
                foreach ($lists as $list) {
                    $itemID = $list['item_id'];
                    $accountID = $list['account_id'];
                    $listingType = $list['listing_type'];
                    $exists = false;
                    //@todo 测试后开启 2016-11-28
                    //$exists      = $model->checkStatusByAccountIDAndItemID($accountID, $itemID, array(0, 1, 2), EbayProductBatchChangeLog::TYPE_UPDATE_DISPATCHTIME);
                    if (!$exists) {
                        $status = EbayProductBatchChangeLog::STATUS_DEFAULT;
                        $lastMsg = '';
                        $uploadCount = 0;
                        if (($type == 2 && !empty($ids)) || $type == 3) {
                            $res = EbayProduct::model()->changeListingFields(array('dispatchTimeMax' => $dispatchTime), $itemID, $accountID, $listingType);
                            $uploadCount = 1;
                            if ($res['errorFlag']) {
                                $status = EbayProductBatchChangeLog::STATUS_SUCCESS;
                                $lastMsg = 'success';
                                EbayProduct::model()->getDbConnection()->createCommand()->update(EbayProduct::tableName(), array('handing_time' => $dispatchTime), "item_id='{$itemID}'");
                            } else {
                                $status = EbayProductBatchChangeLog::STATUS_FAILURE;
                                $lastMsg = trim($res['errorMsg']);
                            }
                        }
                        $addData = array(
                            'item_id' => $itemID,
                            'account_id' => $accountID,
                            'type' => EbayProductBatchChangeLog::TYPE_UPDATE_DISPATCHTIME,
                            'reason' => $dispatchTime,//更新的备货天数
                            'status' => $status,
                            'create_time' => date("Y-m-d H:i:s"),
                            'update_time' => date("Y-m-d H:i:s"),
                            'create_user_id' => intval(Yii::app()->user->id),
                            'last_msg' => $lastMsg,
                            'upload_count' => $uploadCount,
                        );
                        $model->addData($addData);
                    }
                }
            } else {
                throw new Exception("没有找到符合条件的在线listing！");
            }
            echo $this->successJson(array('message' => '任务添加成功，等待后台执行'));
        } catch (Exception $e) {
            echo $this->failureJson(array('message' => $e->getMessage()));
        }
    }

    /**
     * @desc 批量更新location
     */
    public function actionUpdatelocation()
    {
        $ids = trim(Yii::app()->request->getParam('ids', ''));
        $attrTemplates = EbayProductAttributeTemplate::model()->getListByCondition('country,location');
        foreach ($attrTemplates as $v) {
            if (!in_array($v['location'], $countryArr[$v['country']])) {
                $countryArr[$v['country']][] = $v['location'];
            }
        }
        $countryArr['CN'][999] = 'HongKong'; //添加hongkong到cn下
        $this->render("updatelocation",
            array(
                'model' => new EbayProduct(),
                'ids' => $ids,
                'countryArr' => $countryArr,
                'countryList' => json_encode($countryArr),
            )
        );
    }

    /**
     * @desc 批量更新location
     */
    public function actionBatchupdatelocation()
    {
        set_time_limit(3600);
        ini_set('display_errors', false);
        error_reporting(0);
        $ids = trim(Yii::app()->request->getParam('ids', ''));
        $country = trim(Yii::app()->request->getParam('country', ''));
        $location = trim(Yii::app()->request->getParam('location', ''));
        try {
            $lists = array();
            if (trim($ids, ',') == '') {
                throw new Exception("所选item为空，请关闭弹窗选择要更新的item");
            }
            if ($country == '') {
                throw new Exception("请选择country");
            }
            if ($location === '') {
                throw new Exception("请选择location");
            }
            $lists = EbayProduct::model()->getListByCondition("account_id,item_id,listing_type", "id in (" . $ids . ") and item_status=1");
            if ($lists) {
                $model = new EbayProductBatchChangeLog();
                foreach ($lists as $list) {
                    $ebayProductModel = new EbayProduct();
                    $itemID = $list['item_id'];
                    $accountID = $list['account_id'];
                    $listingType = $list['listing_type'];
                    $exists = $model->checkStatusByAccountIDAndItemID($accountID, $itemID, array(0, 1, 2), EbayProductBatchChangeLog::TYPE_UPDATE_LOCATION);
                    if (!$exists) {
                        $fields = array('country' => $country, 'location' => $location);
                        $res = $ebayProductModel->changeListingFields($fields, $itemID, $accountID, $listingType);
                        if ($res['errorFlag']) {
                            $status = EbayProductBatchChangeLog::STATUS_SUCCESS;
                            $lastMsg = 'success';
                            $ebayProductModel->getDbConnection()->createCommand()->update($ebayProductModel->tableName(), array('location' => $location), "item_id='{$itemID}'");
                            $res2 = $ebayProductModel->getListByCondition('*', "item_id='{$itemID}'");
                            if ($res2) {
                                $ebayProductModel->addOrUpdateEbayProduct($res2);
                            }
                        } else {
                            $status = EbayProductBatchChangeLog::STATUS_FAILURE;
                            $lastMsg = trim($res['errorMsg']);
                        }
                        $addData = array(
                            'item_id' => $itemID,
                            'account_id' => $accountID,
                            'type' => EbayProductBatchChangeLog::TYPE_UPDATE_LOCATION,
                            'reason' => json_encode($fields),
                            'status' => $status,
                            'create_time' => date("Y-m-d H:i:s"),
                            'update_time' => date("Y-m-d H:i:s"),
                            'create_user_id' => intval(Yii::app()->user->id),
                            'last_msg' => $lastMsg,
                            'upload_count' => 1,
                        );
                        $model->addData($addData);
                    }
                }
            } else {
                throw new Exception("没有找到符合条件的在线listing！");
            }
            echo $this->successJson(array('message' => '任务添加成功，等待后台执行'));
        } catch (Exception $e) {
            echo $this->failureJson(array('message' => $e->getMessage()));
        }
    }

    /**
     * @desc 自动更新在线listing详情
     * @link /ebay/ebayproduct/autoupdatedesc/account_id/2/limit/100/item_id/2
     */
    public function actionAutoupdatedesc()
    {
        $accountID = Yii::app()->request->getParam('account_id');
        $itemIDs = Yii::app()->request->getParam('item_id');
        $limit = Yii::app()->request->getParam('limit', 100);
        $itemIDArr = array();
        if ($itemIDs) {
            $itemIDArr = explode(",", $itemIDs);
        }
        if (empty($limit)) $limit = 100;

        //定义个小于8的数组，为了补充手动加入了3,5,6,7  by qzz
        $type = array(EbayProductBatchChangeLog::TYPE_UPDATE_DESC, EbayProductBatchChangeLog::TYPE_UPDATE_ZT, EbayProductBatchChangeLog::TYPE_UPDATE_TITLE, 3, 5, 6, 7);
        if ($accountID) {
            //日志
            $ebayLogModel = new EbayLog();
            $eventName = "update_desc";
            $logID = $ebayLogModel->prepareLog($accountID, $eventName);
            //检测
            if (!$ebayLogModel->checkRunning($accountID, $eventName)) {
                $ebayLogModel->setFailure($logID, "Has Exists Event");
                exit;
            }

            $ebayLogModel->setRunning($logID);
            $model = new EbayProduct();
            $ebayProductBatchChangeLogModel = new EbayProductBatchChangeLog();

            $lists = $ebayProductBatchChangeLogModel->getPenndingUpdateListByAccountID($accountID, $itemIDArr, $limit, array(0, 2), 5, $type);
            if ($lists) {
                foreach ($lists as $list) {
                    if ($list['status'] == EbayProductBatchChangeLog::STATUS_OPERATING) continue;
                    $ebayProductBatchChangeLogModel->updateDataByPK($list['id'],
                        array('status' => EbayProductBatchChangeLog::STATUS_OPERATING,
                            'update_time' => date("Y-m-d H:i:s")));
                    $res = $model->changOnlineDescriptionByItemID($list['item_id'], $list['account_id'], $list['type']);
                    $nowtime = date("Y-m-d H:i:s");
                    if (!$res) {
                        $exceptionMsg = $model->getExceptionMessage();
                        $exceptionCode = $model->getExceptionCode();
                        $data = array(
                            'status' => $exceptionCode == 2 ? EbayProductBatchChangeLog::STATUS_IMG_FAILURE : EbayProductBatchChangeLog::STATUS_FAILURE,
                            'upload_count' => intval($list['upload_count']) + 1,
                            'update_time' => $nowtime,
                            'last_msg' => $exceptionMsg
                        );
                    } else {
                        $data = array(
                            'status' => EbayProductBatchChangeLog::STATUS_SUCCESS,
                            'update_time' => $nowtime,
                            'last_msg' => 'success'
                        );
                    }
                    $ebayProductBatchChangeLogModel->updateDataByPK($list['id'], $data);
                }
            }

            $ebayLogModel->setSuccess($logID, "done");
        } else {
            $ebayAccounts = EbayAccount::model()->getAbleAccountList();
            foreach ($ebayAccounts as $account) {
                $url = Yii::app()->request->hostInfo . "/" . $this->route . "/account_id/" . $account['id'] . "/item_id/" . $itemIDs . "/limit/" . $limit;
                MHelper::runThreadBySocket($url);
                sleep(5);
                //echo $url."<br/>";
            }
        }
    }

    /**
     * @desc 自动更新在线listing备货时间/country/location
     * @link /ebay/ebayproduct/autoupdatelistingfields/account_id/57/item_id/131905502163
     */
    public function actionAutoupdatelistingfields()
    {
        $debug = Yii::app()->request->getParam('debug');
        if(isset($debug)){
            set_time_limit(3600*2);
        }else{
            set_time_limit(1800);
        }
        ini_set('display_errors', true);
        error_reporting(E_ALL & ~E_STRICT);

        $accountID = Yii::app()->request->getParam('account_id', '');
        $itemIDs = Yii::app()->request->getParam('item_id', '');
        $limit = Yii::app()->request->getParam('limit', 200);
        $typeId = Yii::app()->request->getParam('type');//8 -- dispatchtime  16 -- location

        $itemIDArr = array();
        if ($itemIDs) {
            $itemIDArr = explode(",", $itemIDs);
        }
        if (empty($limit)) $limit = 100;

        $typeArr = array(EbayProductBatchChangeLog::TYPE_UPDATE_DISPATCHTIME, EbayProductBatchChangeLog::TYPE_UPDATE_LOCATION);

        if (empty($typeId)) {
            $type = $typeArr;
        } else {
            if (!in_array($typeId, $typeArr)) {
                Yii::app()->end('type is undefined');
            }
            $type = array($typeId);
        }

        if ($accountID) {
            //日志
            $ebayLogModel = new EbayLog();
            $eventName = "update_listingfields";

            $logID = $ebayLogModel->prepareLog($accountID, $eventName);
            //检测
            if (!$ebayLogModel->checkRunning($accountID, $eventName)) {
                $ebayLogModel->setFailure($logID, "Has Exists Event");
                echo 'Has Exists Event';
                exit;
            }
            $ebayLogModel->setRunning($logID);
            $ebayProductBatchChangeLogModel = new EbayProductBatchChangeLog();
            $lists = $ebayProductBatchChangeLogModel->getPenndingUpdateListByAccountID($accountID, $itemIDArr, $limit, array(0, 2), 5, $type);
            if ($lists) {
                foreach ($lists as $list) {
                    $model = new EbayProduct();
                    if ($list['status'] == EbayProductBatchChangeLog::STATUS_OPERATING) continue;
                    $ebayProductBatchChangeLogModel->updateDataByPK($list['id'],
                        array('status' => EbayProductBatchChangeLog::STATUS_OPERATING,
                            'update_time' => date("Y-m-d H:i:s")));
                    switch ($list['type']) {
                        case EbayProductBatchChangeLog::TYPE_UPDATE_DISPATCHTIME:
                            $res = $model->changeListingFields(array('dispatchTimeMax' => intval($list['reason'])), $list['item_id']);
                            $model->getDbConnection()->createCommand()->update($model->tableName(), array('handing_time' => intval($list['reason'])), "item_id='{$list['item_id']}'");
                            break;
                        case EbayProductBatchChangeLog::TYPE_UPDATE_LOCATION:
                            $jsonarr = json_decode($list['reason'], true);
                            $res = $model->changeListingFields(array('country' => $jsonarr['country'], 'location' => $jsonarr['location']), $list['item_id']);
                            $model->getDbConnection()->createCommand()->update($model->tableName(), array('location' => $jsonarr['location']), "item_id='{$list['item_id']}'");
                            $res2 = $model->getListByCondition('*', "item_id='{$list['item_id']}'");
                            if ($res2) {
                                $model->addOrUpdateEbayProduct($res2);
                            }
                            break;
                        default:
                            break;
                    }
                    if ($res['errorFlag']) {
                        $data = array(
                            'status' => EbayProductBatchChangeLog::STATUS_SUCCESS,
                            'upload_count' => intval($list['upload_count']) + 1,
                            'update_time' => date("Y-m-d H:i:s"),
                            'last_msg' => 'success'
                        );
                    } else {
                        $data = array(
                            'status' => EbayProductBatchChangeLog::STATUS_FAILURE,
                            'upload_count' => intval($list['upload_count']) + 1,
                            'update_time' => date("Y-m-d H:i:s"),
                            'last_msg' => trim($res['errorMsg'])
                        );
                    }
                    $ebayProductBatchChangeLogModel->updateDataByPK($list['id'], $data);
                }
            }
            $ebayLogModel->setSuccess($logID, "done");
        } else {
            $ebayAccounts = EbayAccount::model()->getAbleAccountList();
            foreach ($ebayAccounts as $account) {
                $url = Yii::app()->request->hostInfo . "/" . $this->route . "/account_id/" . $account['id'] . "/item_id/" . $itemIDs . "/limit/" . $limit;
                MHelper::runThreadBySocket($url);
                sleep(30);
                //echo $url."<br/>";
            }
        }
        Yii::app()->end('finish');
    }

    /**
     * @desc 批量上架操作
     */
    public function actionBatchonline()
    {

        set_time_limit(120);
        error_reporting(E_ALL);
        try {
            $ids = Yii::app()->request->getParam('ids');
            if (empty($ids)) {
                throw new Exception("没有选择");
            }
            //取出全部
            $ebayProductModel = new EbayProduct;
            $idsArr = explode(",", $ids);
            $errorMsg = "";
            foreach ($idsArr as $id) {
                $res = $ebayProductModel->ebayProductOnlineById($id);
                if (!$res) {
                    $errorMsg .= $ebayProductModel->getExceptionMessage() . "<br/>";
                }
            }
            if (empty($errorMsg))
                echo $this->successJson(array('message' => '操作成功'));
            else
                echo $this->successJson(array('message' => $errorMsg));
        } catch (Exception $e) {
            echo $this->failureJson(array('message' => $e->getMessage()));
        }

    }

    /**
     * @desc 批量下架操作
     */
    public function actionBatchoffline()
    {
        set_time_limit(120);
        error_reporting(E_ALL);
        try {
            $ids = Yii::app()->request->getParam('ids');
            if (empty($ids)) {
                throw new Exception("没有选择");
            }

            //取出全部
            $ebayProductModel = new EbayProduct;
            $idsArr = explode(",", $ids);
            $errorMsg = "";
            foreach ($idsArr as $id) {
                $res = $ebayProductModel->ebayProductOfflineById($id);
                if (!$res) {
                    $errorMsg .= $ebayProductModel->getExceptionMessage() . "<br/>";
                }
            }
            if (empty($errorMsg))
                echo $this->successJson(array('message' => '操作成功'));
            else
                echo $this->successJson(array('message' => $errorMsg));
        } catch (Exception $e) {
            echo $this->failureJson(array('message' => $e->getMessage()));
        }

    }

    /**
     * @DESC 批量下架子sku
     * @throws Exception
     */
    public function actionBatchvariationoffline()
    {
        try {
            $ids = Yii::app()->request->getParam('ebay_varants_ids');
            if (empty($ids)) {
                throw new Exception("没有选择");
            }
            $ebayProductModel = new EbayProduct;
            $errorMsg = "";
            foreach ($ids as $id) {
                $res = $ebayProductModel->ebayVariationProductOfflineByVaritionID($id);
                if (!$res) {
                    $errorMsg .= $ebayProductModel->getExceptionMessage() . "<br/>";
                }
            }
            if (empty($errorMsg))
                echo $this->successJson(array('message' => '操作成功'));
            else
                echo $this->successJson(array('message' => $errorMsg));
        } catch (Exception $e) {
            echo $this->failureJson(array('message' => $e->getMessage()));
        }
    }

    /**
     * @desc 下架单个 子SKU
     * @throws Exception
     */
    public function actionVariationoffline()
    {
        try {
            $id = Yii::app()->request->getParam('id');
            if (empty($id)) {
                throw new Exception("没有选择");
            }
            $ebayProductModel = new EbayProduct;
            $errorMsg = "";
            $res = $ebayProductModel->ebayVariationProductOfflineByVaritionID($id);
            if (!$res) {
                throw new Exception($ebayProductModel->getExceptionMessage() . "<br/>");
            }
            echo $this->successJson(array('message' => '操作成功'));
        } catch (Exception $e) {
            echo $this->failureJson(array('message' => "操作失败：" . $e->getMessage()));
        }
    }

    /**
     * @desc /ebay/ebayproduct/test
     */
    public function actionTest()
    {
        exit();
        $ids = array(
            87236, 146426, 371563, 374400, 397059, 722190, 847588, 1081555, 2384412, 2385299, 2871906, 142327, 151344, 191399, 304299, 1537700, 1670910, 121833, 145136, 189391, 221451, 308949, 331099, 331187, 331335, 362473, 648479, 654204, 711033, 733119, 807416, 1670915, 1982449, 2567791, 2667109, 3738732, 3836033, 4835, 4839, 6657, 807389, 935333, 1101260, 3197336, 3197392, 315599, 316788, 317043, 319205, 339156, 1695042, 1929951, 1982299, 2019636, 2901553, 958214, 985281, 985290, 991482, 1001811, 1001833, 1008580, 1064119, 1077678, 1772853, 2048733, 3378379, 934281, 934287, 948287, 948294, 873482, 873499, 873526, 873594, 873625, 873648, 931765, 1151308, 2382504, 873483, 873500, 873527, 873595, 873626, 873649, 931766, 1151309, 2382505, 1491352, 1495429, 1495453, 1513200, 1522573, 1544782, 1544814, 1773156, 1919746, 3437515, 3009927, 10908, 73658, 73678, 107736, 216171, 1832334, 11578, 11595, 30570, 96050, 198221, 198243, 213056, 227330, 228972, 228981, 237521, 1839992, 1847249, 1851783, 1854812, 1859986, 1865572, 2069933, 3787743, 10377, 24304, 29416, 39199, 62437, 63901, 110976, 147013, 157645, 188605, 188996, 198191, 227077, 227131, 234627, 247077, 568320, 761841, 1542534, 1567036, 1735081, 1746132, 1821131, 1847866, 1851931, 1857932, 1859080, 1860459, 1866124, 1928417, 1931082, 1954991, 1957805, 2030950, 2504455, 2842475, 3110826, 3865458, 10164, 10170, 11531, 11543, 13618, 17074, 17674, 53126, 1183043
        );
        $ebayProductModel = new EbayProduct;
        $errorMsg = "";
        foreach ($ids as $id) {
            $res = $ebayProductModel->ebayVariationProductOfflineByVaritionID($id);
            if (!$res) {
                $errorMsg .= $ebayProductModel->getExceptionMessage() . "<br/>";
            }
        }
        exit;

        $addId = Yii::app()->request->getParam("add_id");
        if (empty($addId)) exit('add id exit');
        $addInfo = EbayProductAdd::model()->getEbayProductAddInfoByAddID($addId);
        if (empty($addInfo) || empty($addInfo['item_id'])) {
            exit('invalid id');
        }
        $onlineSKU = $addInfo['seller_sku'];
        $startPrice = $addInfo['start_price'];
        $itemId = $addInfo['item_id'];
        $accountID = $addInfo['account_id'];
        $variations = EbayProductAddVariation::model()->getEbayProductAddVariationListByAddID($addId);
        if ($variations) {
            foreach ($variations as $variation) {
                $startPrice = $variation['variation_price'] * 1.5;
                $onlineSKU = $variation['son_seller_sku'];
                $reviseInventoryStatusRequest = new ReviseInventoryStatusRequest();
                $reviseInventoryStatusRequest->setItemID($itemId);
                $reviseInventoryStatusRequest->setSKU($onlineSKU);
                $reviseInventoryStatusRequest->setStartPrice($startPrice);
                $reviseInventoryStatusRequest->push();
                $response_variation = $reviseInventoryStatusRequest->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
            }
        } else {
            $startPrice = $startPrice * 1.5;
            $reviseInventoryStatusRequest = new ReviseInventoryStatusRequest();
            $reviseInventoryStatusRequest->setItemID($itemId);
            $reviseInventoryStatusRequest->setSKU($onlineSKU);
            $reviseInventoryStatusRequest->setStartPrice($startPrice);
            $reviseInventoryStatusRequest->push();
            $response_variation = $reviseInventoryStatusRequest->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
        }
        echo "<pre>";
        print_r($response_variation);
    }

    /**
     * @desc 获取配置参数
     */
    public function actionGetconfigtype()
    {
        $accountID = Yii::app()->request->getParam("account_id", '');
        $siteID = Yii::app()->request->getParam("site_id", '');
        if ($accountID == '') {
            $rtn = array('errCode' => 101, 'errMessage' => 'account_id is empty');
            echo json_encode($rtn);
            exit;
        }
        if ($siteID === '') {
            $rtn = array('errCode' => 102, 'errMessage' => 'site_id is empty');
            echo json_encode($rtn);
            exit;
        }
        //找出对应的仓库ID
        $wareHouseID = EbayAccountSite::model()->getWarehouseByAccountSite($accountID, $siteID);
        //获取属性模板
        $attributeInfo = EbayProductAttributeTemplate::model()->getListByCondition("config_type", "site_id='" . $siteID . "' AND abroad_warehouse = '{$wareHouseID}'");
        $configTypeArr = EbayProductAdd::getConfigType();
        $configType = array();
        if (!empty($attributeInfo)) {
            $checked = count($attributeInfo) == 1 ? 1 : 0;
            foreach ($attributeInfo as $v) {
                $configType[$v['config_type']]['name'] = $configTypeArr[$v['config_type']];
                $configType[$v['config_type']]['checked'] = $checked;
            }
        }
        $rtn = array('errCode' => 200, 'errMessage' => 'ok', 'data' => $configType);
        echo json_encode($rtn);
        exit;
    }

    /**
     * @desc 复制刊登
     * @link /ebay/ebayproduct/copylisting
     */
    public function actionCopylisting()
    {
        $ids = trim(Yii::app()->request->getParam("ids"));
        if (Yii::app()->request->isAjaxRequest && isset($_POST['account_id'])) {
            $accountID = Yii::app()->request->getParam("account_id", '');
            $siteID = Yii::app()->request->getParam("site_id", '');
            $duration = Yii::app()->request->getParam("duration", '');
            $configtype = trim(Yii::app()->request->getParam("configtype", ''));
            $type = trim(Yii::app()->request->getParam("handle_type", 2));
            $discountRate = trim(Yii::app()->request->getParam("discount_rate", ''));
            $listingType = Yii::app()->request->getParam("listing_type", '');
            //MHelper::writefilelog('copylisting_req_'.date('Ymd').'.txt',print_r($_REQUEST,true)."\r\n");
            if (!preg_match('/^\d+(\.\d+)?$/', $discountRate)) {//折扣率过滤非数字
                $discountRate = 0;
            }
            try {
                if ($ids == '') {
                    throw new Exception("非法请求", 1);
                }
                if ($accountID == '') {
                    throw new Exception("目标账号不能为空", 2);
                }
                if ($siteID === '') {
                    throw new Exception("目标站点不能为空", 3);
                }
                if ($configtype === '') {
                    throw new Exception("配置参数不能为空", 4);
                }
                $ids = trim($ids, ',');
                $res = EbayProduct::model()->getListByCondition('item_id,account_id,site_id,sku', "id in({$ids})");
                if ($res) {
                    //获取要复制的部门
                    $info2 = EbayDepartmentAccountSite::model()->getListByCondition("account_id={$accountID} and site_id={$siteID}");
                    $msg = '';
                    foreach ($res as $value) {
                        //获取原帐号的部门
                        $info1 = EbayDepartmentAccountSite::model()->getListByCondition("account_id={$value['account_id']} and site_id={$value['site_id']}");
                        if ($info1[0]['department_id'] != $info2[0]['department_id']) {
                            $msg .= $value['sku'] . ',';
                        }
                    }
                    if ($msg != '') {
                        $msg = rtrim($msg, ',');
                        throw new Exception("sku " . $msg . "不能跨部门复制", 5);
                    }
                    if ($type == 1) {//实时处理方式
                        $toCustoms = array();
                        if ($duration != '') {
                            $toCustoms['listing_duration'] = $duration;
                        }
                        if ($configtype !== '') {
                            $toCustoms['configtype'] = $configtype;
                        }
                        if ($discountRate !== '' && $discountRate != 0) {
                            $toCustoms['discount_rate'] = $discountRate;
                        }
                        if($listingType != ''){
                            $toCustoms['listing_type'] = $listingType;
                        }

                        $tips = '';
                        foreach ($res as $v) {
                            try {
                                $model = new EbayProductAdd();
                                $model->copyListingByItemID($v['item_id'], $accountID, $siteID, $toCustoms);
                            } catch (Exception $e) {
                                $tips .= $e->getMessage();
                            }
                        }
                        echo $this->successJson(array('message' => '任务添加成功 ### ' . $tips));
                    } else {
                        //异步处理方式
                        $itemIDs = '';
                        foreach ($res as $v) {
                            $itemIDs .= $v['item_id'] . ',';
                        }
                        $itemIDs = rtrim($itemIDs, ',');
                        $url = Yii::app()->request->hostInfo . '/ebay/ebayproduct/copylistingdo';
                        $post = array(
                            'item_ids' => $itemIDs,
                            'account_id' => $accountID,
                            'site_ids' => $siteID,
                            'duration' => $duration,
                            'configtype' => $configtype,
                            'listing_type' => $listingType,
                        );
                        if ($discountRate !== '' && $discountRate != 0) {
                            $post['discount_rate'] = $discountRate;
                        }
                        MHelper::runThreadBySocket($url, $post, 0, '', '', 1800, false);
                        echo $this->successJson(array('message' => '任务添加成功，等待后台执行'));
                    }
                } else {
                    echo $this->failureJson(array('message' => '任务添加失败！'));
                }
            } catch (Exception $e) {
                echo $this->failureJson(array('message' => $e->getMessage()));
            }
            Yii::app()->end();
        }

        //如果是ebay相关部门的则显示对应部门的帐号
        $userID = Yii::app()->user->id;
        $department_id = User::model()->getDepIdById($userID);
        $ebayDepartArr = Department::model()->getDepartmentByPlatform(Platform::CODE_EBAY);
        if (in_array($department_id, $ebayDepartArr)) {
            $department = EbayDepartmentAccountSite::model()->getListByCondition('department_id = ' . $department_id);
            $departmentArr = array();
            foreach ($department as $v) {
                $departmentArr[] = $v['account_id'];
            }
            $accountArr = EbayAccount::getAccountNameByIds($departmentArr);
        } else {
            $accountIDs = EbayAccount::getAbleAccountList();
            foreach ($accountIDs as $v) {
                $accountArr[$v['id']] = $v['short_name'];
            }
        }
        $this->render("copylisting",
            array('model' => new EbayProduct(),
                'ids' => $ids,
                'accountArr' => $accountArr,
                'siteArr' => EbaySite::model()->getSiteList(),
                'listingDurations' => array(
                    'GTC' => 'GTC',
                    'Days_3' => '3天',
                    'Days_5' => '5天',
                    'Days_7' => '7天',
                    'Days_10' => '10天',
                    'Days_30' => '30天',
                ),
            )
        );
    }

    /**
     * @desc 复制刊登
     * @link /ebay/ebayproductadd/copylistingdo
     */
    public function actionCopylistingdo()
    {
        set_time_limit(3600);
        $userId = intval(Yii::app()->user->id);
        //MHelper::writefilelog('copylisting_request_'.date('Ymd').'.txt',$userId.' # '.print_r($_REQUEST,true)."\r\n");
        $logs = '';
        $itemIDs = trim(Yii::app()->request->getParam("item_ids", ''));
        $toAccountID = trim(Yii::app()->request->getParam("account_id", ''));
        $toSiteIDs = trim(Yii::app()->request->getParam("site_ids", ''));
        $duration = trim(Yii::app()->request->getParam("duration", ''));
        $configtype = trim(Yii::app()->request->getParam("configtype", ''));
        $discountRate = trim(Yii::app()->request->getParam("discount_rate", ''));
        $listingType = Yii::app()->request->getParam("listing_type", '');
        if ($itemIDs == '' || $toAccountID == '' || $toSiteIDs === '' || $configtype === '') {
            $logs .= 'params error ';
            //MHelper::writefilelog('copylisting_err_'.date('Ymd').'.txt', $userId.' # '. $logs."\r\n");
            return false;
        }
        $toCustoms = array();
        if ($duration != '') {
            $toCustoms['listing_duration'] = $duration;
        }
        if ($configtype !== '') {
            $toCustoms['configtype'] = $configtype;
        }
        if (isset($discountRate) && $discountRate !== '' && $discountRate != 0) {
            $toCustoms['discount_rate'] = $discountRate;
        }
        if ($listingType != '') {
            $toCustoms['listing_type'] = $listingType;
        }
        $itemIDArr = !is_array($itemIDs) ? array($itemIDs) : explode(',', $itemIDs);
        $siteIDArr = !is_array($toSiteIDs) ? array($toSiteIDs) : explode(',', $toSiteIDs);
        foreach ($itemIDArr as $itemID) {
            foreach ($siteIDArr as $toSiteID) {
                try {
                    $model = new EbayProductAdd();
                    $isok = $model->copyListingByItemID($itemID, $toAccountID, $toSiteID, $toCustoms);
                    $logs .= $itemID . ' ' . ($isok ? 'SUCCESS' : 'Failure');
                } catch (Exception $e) {
                    $logs .= $itemID . ' ' . $e->getMessage();
                    continue;
                }
            }
        }
        //MHelper::writefilelog('copylisting_'.date('Ymd').'.txt',$userId.' # '.$logs."\r\n");
        return $logs ? false : true;
    }

    /**
     * @desc 同步listing到ueb_product.ueb_ebay_product
     * @link /ebay/ebayproduct/syncinfo/account_id/13
     */
    public function actionSyncinfo()
    {
        set_time_limit(0);
        ini_set('memory_limit', '2048M');
        ini_set('display_errors', true);
        error_reporting(E_ALL & ~E_STRICT);

        $accountID = trim(Yii::app()->request->getParam("account_id", ''));
        $itemID = trim(Yii::app()->request->getParam("item_id", ''));

        $ebayProduct = new EbayProduct();
        $accountIDs = EbayAccount::$OVERSEAS_ACCOUNT_ID;//array(37,54,55,57,59,60,62,13);
        //按小时取分组法,每个账号5分钟
        $offlineTime = date('Y-m-d', strtotime('-60 days'));
        foreach ($accountIDs as $account_id) {
            if ($accountID != '') {
                if ($account_id != $accountID) {
                    continue;
                }
            }
            echo 'accountID:' . $account_id . "\r\n<br>";
            $condition = $itemID == '' ? "" : " and item_id='{$itemID}'";
            //同步所有在线listing
            $res = $ebayProduct->getListByCondition('*', "account_id={$account_id} and item_status=1 {$condition} ");//and location IN('Hamburg','Bruchsal','Markgröningen','Derby','London','Manchester','UK')
            if ($res) {
                EbayProduct::model()->addOrUpdateEbayProduct($res);
            }
            //同步最近3天下架的listing
            $res = $ebayProduct->getListByCondition('*', "account_id={$account_id} and item_status=0 and timestamp>'{$offlineTime}' {$condition} ");
            if ($res) {
                EbayProduct::model()->addOrUpdateEbayProduct($res);
            }
        }
        Yii::app()->end('finish');
    }

    /**
     * 根据子产品表ID修改在线库存
     */
    public function actionReviseinventorystatus()
    {
        $ebayProductModel = new EbayProduct;
        $ebayProductVariationModel = new EbayProductVariation;
        $variationID = Yii::app()->request->getParam('variationID');
        $variationInfo = $ebayProductVariationModel->findByPk($variationID);
        if (empty($variationInfo)) {
            echo $this->failureJson(array('message' => '没有对应子SKU'));
            exit;
        }
        $ebayProductArr = Yii::app()->request->getParam('EbayProduct');
        $quantity = isset($ebayProductArr['quantity_available']) ? $ebayProductArr['quantity_available'] : '';

        if ($_POST) {
            set_time_limit(0);
            ini_set('display_errors', true);
            ini_set('memory_limit', '256M');
            if (!preg_match("/^(0|[1-9][0-9]*)$/", $quantity)) {//验证是否为数字
                echo $this->failureJson(array('message' => '库存数量不正确'));
                exit;
            }

            $data = array(
                'item_id' => $variationInfo['item_id'],
                'sku_online' => $variationInfo['sku_online'],
                'count' => $quantity
            );

            $errorMsg = $ebayProductModel->reviseEbayListing($variationInfo['account_id'], $data);
            //库存更改记录
            $userID = (int)Yii::app()->user->id;
            $productInfo = $ebayProductModel->find(array(
                'select' => array('site_id'),
                'condition' => 'id=:id',
                'params' => array(':id' => $variationInfo['listing_id']),
            ));
            $stockData = array();
            $stockData['product_id'] = $variationInfo['item_id'];
            $stockData['seller_sku'] = $variationInfo['sku_online'];
            $stockData['sku'] = $variationInfo['sku'];
            $stockData['account_id'] = $variationInfo['account_id'];
            $stockData['site_id'] = $productInfo['site_id'];
            $stockData['old_quantity'] = $variationInfo['quantity_available'];
            $stockData['set_quantity'] = $quantity;//更改数量
            $stockData['create_user_id'] = $userID;//添加人
            $stockData['create_time'] = date("Y-m-d H:i:s");
            $stockData['type'] = 6;//手动修改
            if ($errorMsg['errorCode'] == 200) {
                $stockData['status'] = 2;
                $stockData['msg'] = 'success';
            } else {
                $stockData['status'] = 3;
                $stockData['msg'] = $errorMsg['errorMsg'];
            }
            $ebayZeroStockSku = new EbayZeroStockSku;
            $ebayZeroStockSku->saveData($stockData);
            if ($errorMsg['errorCode'] == 200) {
                $ebayProductVariationModel->getDbConnection()
                    ->createCommand()
                    ->update($ebayProductVariationModel->tableName(), array("quantity_available" => $quantity), "id=" . $variationID);
                // echo $this->successJson(array('message'=>'操作成功'));
                $jsonData = array(
                    'message' => '更改成功',
                    'forward' => '/ebay/ebayproduct/list',
                    'navTabId' => 'page' . EbayProduct::getIndexNavTabId(),
                    'callbackType' => 'closeCurrent'
                );
                echo $this->successJson($jsonData);
            } else {
                echo $this->failureJson(array('message' => $errorMsg['errorMsg']));
            }

            exit;
        }

        $model = new EbayProduct();
        $this->render(
            "reviseinventorystatus",
            array(
                'model' => $model,
                'variationID' => $variationID,
                'sku' => $variationInfo['sku'],
                'quantity' => $variationInfo['quantity_available']
            )
        );
    }

    /*
     * 批量修改库存
     */
    public function actionUpdateStock()
    {
        $ids = trim(Yii::app()->request->getParam('ids', ''));
        $this->render("updatestock",
            array(
                'model' => new EbayProduct(),
                'ids' => $ids,
            )
        );
    }

    /*
     * 批量修改库存
     */
    public function actionBatchUpdateStock()
    {
        set_time_limit(3600);
        ini_set('display_errors', false);
        error_reporting(0);

        $ebayProductModel = new EbayProduct;
        $ebayProductVariationModel = new EbayProductVariation;
        $ebayZeroStockSku = new EbayZeroStockSku;
        $ids = trim(Yii::app()->request->getParam('ids', ''));
        $ebayProductArr = Yii::app()->request->getParam('EbayProduct');
        $quantity = isset($ebayProductArr['quantity_available']) ? $ebayProductArr['quantity_available'] : '';

        try {
            if (trim($ids, ',') == '') {
                throw new Exception("没有选择 variants id");
            }
            if (!preg_match("/^(0|[1-9][0-9]*)$/", $quantity)) {//验证是否为数字
                throw new Exception("库存数量不正确");
            }

            $ids = trim($ids, ',');
            $lists = $ebayProductVariationModel->getListByCondition('id,item_id,sku,sku_online,account_id,listing_id,quantity_available', "id in({$ids})");
            if ($lists) {
                foreach ($lists as $variationInfo) {
                    $data = array(
                        'item_id' => $variationInfo['item_id'],
                        'sku_online' => $variationInfo['sku_online'],
                        'count' => $quantity
                    );
                    $errorMsg = $ebayProductModel->reviseEbayListing($variationInfo['account_id'], $data);

                    $stockData = array();
                    if ($errorMsg['errorCode'] == 200) {
                        $stockData['status'] = 2;
                        $stockData['msg'] = 'success';
                        //更新库存
                        $ebayProductVariationModel->getDbConnection()->createCommand()->update($ebayProductVariationModel->tableName(), array("quantity_available" => $quantity), "id=" . $variationInfo['id']);
                    } else {
                        $stockData['status'] = 3;
                        $stockData['msg'] = $errorMsg['errorMsg'];
                    }
                    //库存更改记录
                    $userID = (int)Yii::app()->user->id;
                    $productInfo = $ebayProductModel->find(array(
                        'select' => array('site_id'),
                        'condition' => 'id=:id',
                        'params' => array(':id' => $variationInfo['listing_id']),
                    ));
                    $stockData['product_id'] = $variationInfo['item_id'];
                    $stockData['seller_sku'] = $variationInfo['sku_online'];
                    $stockData['sku'] = $variationInfo['sku'];
                    $stockData['account_id'] = $variationInfo['account_id'];
                    $stockData['site_id'] = $productInfo['site_id'];
                    $stockData['old_quantity'] = $variationInfo['quantity_available'];
                    $stockData['set_quantity'] = $quantity;//更改数量
                    $stockData['create_user_id'] = $userID;//添加人
                    $stockData['create_time'] = date("Y-m-d H:i:s");
                    $stockData['type'] = 6;//手动修改
                    $ebayZeroStockSku->saveData($stockData);
                }
            } else {
                throw new Exception("没有找到符合条件的在线listing！");
            }
            /*$jsonData = array(
                'message' => '任务添加成功，等待后台执行',
                'forward' =>'/ebay/ebayproduct/list',
                'navTabId'=> 'page' .EbayProduct::getIndexNavTabId(),
                'callbackType'=>'closeCurrent'
            );*/
            echo $this->successJson(array('message' => '任务添加成功，等待后台执行'));
        } catch (Exception $e) {
            echo $this->failureJson(array('message' => $e->getMessage()));
        }
        Yii::app()->end();
    }

    /*
     * 批量修改发货方式
     */
    public function actionUpdateShip()
    {
        $ids = trim(Yii::app()->request->getParam('ids', ''));
        $siteID = trim(Yii::app()->request->getParam('site_id', ''));

        //运输信息
        $ebayCategoryInfoModel = new EbayCategoryInfo();
        $shippingInfo = $ebayCategoryInfoModel->getShippingInfo($siteID);
        $this->render("updateship",
            array(
                'model' => new EbayProduct(),
                'ids' => $ids,
                'shippingInfo' => json_encode($shippingInfo),
            )
        );
    }

    /*
     * 批量修改发货方式
     */
    public function actionBatchUpdateShip()
    {
        set_time_limit(3600);
        ini_set('display_errors', false);
        error_reporting(0);

        $ids = trim(Yii::app()->request->getParam('ids', ''));
        $services = Yii::app()->request->getParam('services');  //运输方式
        $shippingCost = Yii::app()->request->getParam('shipcost');//运输费用
        $additionalShippingCost = Yii::app()->request->getParam('additionalshipcost');//添加运输费用
        $shippingShippingServices = Yii::app()->request->getParam('shippingServices');//运输类型，本地还是国际
        $ebayProductShippingModel = new EbayProductShipping();
        $ebayProductBatchChangeLogModel = new EbayProductBatchChangeLog();

        try {
            if (trim($ids, ',') == '') {
                throw new Exception("没有选择");
            }
            if ($services == '') {
                throw new Exception("请选择物流方式");
            }

            $shippingData = array();
            $localPriority = 0;
            foreach ($services as $key => $value) {
                if (empty($value['shipping_service'])) {
                    throw new Exception("本地运输服务商没有设置");
                }
                $localPriority++;
                $shippingData[] = array(
                    'FreeShipping' => $shippingCost[$key] == 0 ? 'true' : 'false',
                    'ShippingService' => $value,
                    'ShippingServiceCost' => $shippingCost[$key],
                    'ShippingServiceAdditionalCost' => $additionalShippingCost[$key],
                    'ShippingServicePriority' => $localPriority,
                );
            }
            $lists = EbayProduct::model()->getListByCondition("account_id,site_id,item_id,listing_type", "id in (" . $ids . ") and item_status=1");

            if ($lists) {
                foreach ($lists as $list) {
                    //调用接口
                    $res = EbayProduct::model()->changeShipping($list['item_id'], $shippingData, $list['account_id'], $list['listing_type']);

                    if ($res['errorFlag']) {
                        //更新数据库
                        $condition = "item_id = '{$list['item_id']}' and service_option = 0";//指定为本地运输方式
                        $ebayProductShippingModel->getDbConnection()->createCommand()->delete($ebayProductShippingModel->tableName(), $condition);
                        foreach ($shippingData as $v) {
                            $dataArr = array(
                                'item_id' => $list['item_id'],
                                'account_id' => $list['account_id'],
                                'shipping_service' => $v['ShippingService'],
                                'shipping_service_cost' => $v['ShippingServiceCost'],
                                'shipping_service_additional_cost' => $v['ShippingServiceAdditionalCost'],
                                'shipping_service_priority' => $v['ShippingServicePriority'],
                                //'expedited_service' => ($shipping->ExpeditedService == 'true') ? 1 : 0,
                                //'shipping_time_min' => intval($shipping->ShippingTimeMin),
                                //'shipping_time_max' => intval($shipping->ShippingTimeMax),
                                'free_shipping' => $v['FreeShipping'] == 'true' ? 1 : 0,
                                'service_option' => 0,
                                'update_time' => date('Y-m-d H:i:s'),
                                'create_time' => date('Y-m-d H:i:s'),
                            );
                            $ebayProductShippingModel->saveData($dataArr);
                        }
                        $status = EbayProductBatchChangeLog::STATUS_SUCCESS;
                        $lastMsg = 'success';
                    } else {
                        $status = EbayProductBatchChangeLog::STATUS_FAILURE;
                        $lastMsg = trim($res['errorMsg']);
                    }
                    $addData = array(
                        'item_id' => $list['item_id'],
                        'account_id' => $list['account_id'],
                        'type' => 32,//自定义类型，批量更新送货方式
                        'reason' => json_encode($shippingData),
                        'status' => $status,
                        'create_time' => date("Y-m-d H:i:s"),
                        'update_time' => date("Y-m-d H:i:s"),
                        'create_user_id' => intval(Yii::app()->user->id),
                        'last_msg' => $lastMsg,
                        'upload_count' => 1,
                    );
                    $ebayProductBatchChangeLogModel->addData($addData);
                }
            } else {
                throw new Exception("没有找到符合条件的在线listing！");
            }
            echo $this->successJson(array('message' => '任务添加成功，等待后台执行'));
        } catch (Exception $e) {
            echo $this->failureJson(array('message' => $e->getMessage()));
        }
        Yii::app()->end();
    }

    /**
     * 根据产品表item_id修改产品标题
     */
    public function actionRevisetitle()
    {
        $ebayProductModel = new EbayProduct;
        $id = Yii::app()->request->getParam('id');
        $prodcutInfo = $ebayProductModel->getOneByCondition('title', 'id = ' . $id);
        if ($_POST) {
            set_time_limit(0);
            ini_set('display_errors', true);
            ini_set('memory_limit', '256M');
            $ebayProductArr = Yii::app()->request->getParam('EbayProduct');
            $title = isset($ebayProductArr['title']) ? $ebayProductArr['title'] : '';
            //判断标题是否超过了80个字符
            if (strlen($title) > 80) {
                echo $this->failureJson(array('message' => '标题不能超过80个字符'));
                exit;
            }

            $errorMsg = $ebayProductModel->reviseTitleByItemID($id, $title);
            if (empty($errorMsg)) {
                // echo $this->successJson(array('message'=>'操作成功'));
                $jsonData = array(
                    'message' => '更改成功',
                    'forward' => '/ebay/ebayproduct/list',
                    'navTabId' => 'page' . EbayProduct::getIndexNavTabId(),
                    'callbackType' => 'closeCurrent'
                );
                echo $this->successJson($jsonData);
            } else {
                echo $this->failureJson(array('message' => $errorMsg));
            }

            exit;
        }

        $this->render(
            "revisetitle",
            array(
                'model' => $ebayProductModel,
                'id' => $id,
                'title' => isset($prodcutInfo['title']) ? $prodcutInfo['title'] : ''
            )
        );
    }

    /**
     * 根据子产品表ID修改在线价格
     */
    public function actionReviseinventorystatusprice()
    {
        $ebayProductModel = new EbayProduct;
        $ebayProductVariationModel = new EbayProductVariation;
        $ebayProductShippingModel = new EbayProductShipping;
        $variationID = Yii::app()->request->getParam('variationID');
        $variationInfo = $ebayProductVariationModel->findByPk($variationID);
        if (empty($variationInfo)) {
            echo $this->failureJson(array('message' => '没有对应子SKU'));
            exit;
        }
        $ebayProductArr = Yii::app()->request->getParam('EbayProductVariation');
        $salePprice = isset($ebayProductArr['sale_price']) ? $ebayProductArr['sale_price'] : 0;

        if ($_POST) {
            set_time_limit(0);
            ini_set('display_errors', true);
            ini_set('memory_limit', '256M');
            if (!is_numeric($salePprice) || $salePprice <= 0) {
                echo $this->failureJson(array('message' => '价格必须大于0'));
                exit;
            }

            $data = array(
                'item_id' => $variationInfo['item_id'],
                'sku_online' => $variationInfo['sku_online'],
                'price' => $salePprice
            );

            $errorMsg = $ebayProductModel->reviseEbayListing($variationInfo['account_id'], $data);

            if ($errorMsg['errorCode'] = 200) {
                $ebayProductVariationModel->getDbConnection()
                    ->createCommand()
                    ->update($ebayProductVariationModel->tableName(), array("sale_price" => $salePprice), "id=" . $variationID);

                $ebayProductModel->getDbConnection()
                    ->createCommand()
                    ->update($ebayProductModel->tableName(), array("current_price" => $salePprice), "id=" . $variationInfo['listing_id']);

                //查询运费表，运费是否存在，如不存在插入数据
                $shippingInfo = $ebayProductShippingModel->getListByCondition('shipping_service_cost', 'item_id="' . $variationInfo['item_id'] . '"');
                if ($shippingInfo) {
                    //插入或更新利润表
                    $ebayProductShippingModel->setProfitByShippingWhere('item_id = "' . $variationInfo['item_id'] . '"');
                } else {
                    $itemInfo = $ebayProductModel->getItemRow($variationInfo['account_id'], $variationInfo['item_id']);
                    if (isset($itemInfo->ShippingDetails)) {
                        //插入运费表数据
                        $ebayProductShippingModel->insertShipping($variationInfo['account_id'], $variationInfo['item_id'], $itemInfo->ShippingDetails);

                        //插入或更新利润表
                        $ebayProductShippingModel->setProfitByShippingWhere('item_id = "' . $variationInfo['item_id'] . '"');
                    }
                }

                // echo $this->successJson(array('message'=>'操作成功'));
                $jsonData = array(
                    'message' => '更改成功',
                    'forward' => '/ebay/ebayproduct/list',
                    'navTabId' => 'page' . EbayProduct::getIndexNavTabId(),
                    'callbackType' => 'closeCurrent'
                );
                echo $this->successJson($jsonData);
            } else {
                echo $this->failureJson(array('message' => $errorMsg['errorMsg']));
            }

            exit;
        }

        $this->render(
            "reviseinventorystatusprice",
            array(
                'model' => $ebayProductVariationModel,
                'variationID' => $variationID,
                'sku' => $variationInfo['sku'],
                'price' => $variationInfo['sale_price'],
                'accountID' => $variationInfo['account_id'],
                'itemID' => $variationInfo['item_id'],
            )
        );
    }

    /**
     * 通过价格获取运费和费率
     */
    public function actionGetprofit()
    {
        ini_set('display_errors', true);
        $price = Yii::app()->request->getParam('price');
        $account_id = Yii::app()->request->getParam('account_id');
        $item_id = Yii::app()->request->getParam('item_id');
        $sku = Yii::app()->request->getParam('sku');

        $ebayProductModel = new EbayProduct;
        $ebayProductShippingModel = new EbayProductShipping;
        $ebaySalePriceConfigModel = new EbayProductSalePriceConfig();
        $ebayCategoryModel = new EbayCategory();

        $wheres = "item_id = '{$item_id}'";
        $shippingInfo = $ebayProductShippingModel->getShippingPriceByWhere($wheres);
        if (!$shippingInfo) {
            $itemInfo = $ebayProductModel->getItemRow($account_id, $item_id);
            if (!$itemInfo || !isset($itemInfo->ShippingDetails)) {
                echo $this->failureJson(array('message' => '没有获取到运费'));
                exit;
            }
            $shippingResult = $ebayProductShippingModel->insertShipping($account_id, $item_id, $itemInfo->ShippingDetails);
            if ($shippingResult) {
                $shippingInfo = $ebayProductShippingModel->getShippingPriceByWhere($wheres);
            }
        }

        if (!isset($shippingInfo[$item_id])) {
            echo $this->failureJson(array('message' => '没有获取到运费'));
            exit;
        }

        //获取产品信息
        $productInfo = $ebayProductModel->getOneByCondition('site_id,current_price_currency,category_id', "item_id ='{$item_id}'");

        //获取类目名称
        $categoryInfo = $ebayCategoryModel->getCategotyInfoByID($productInfo['category_id'], $productInfo['site_id']);
        if (!isset($categoryInfo['category_name']) || empty($categoryInfo['category_name'])) {
            echo $this->failureJson(array('message' => '没有获取到运费'));
            exit;
        }
        $categoryName = $categoryInfo['category_name'];

        $input = '';
        if (isset($shippingInfo[$item_id]) && $shippingInfo[$item_id]) {
            foreach ($shippingInfo[$item_id] as $k => $v) {
                $input .= '运费：' . $v;
                $profitInfo = $ebaySalePriceConfigModel->getProfitInfo($price, $sku, $productInfo['current_price_currency'], $productInfo['site_id'], $account_id, $categoryName, $v);
                if ($profitInfo) {
                    $profit = (empty($profitInfo['profit'])) ? 0 : $profitInfo['profit'];
                    $input .= ' 利润：' . $profit;
                    $profitRate = $profitInfo['profit_rate'];
                    $input .= ' 利润率' . $profitRate . '<br>';
                } else {
                    $profit = 0;
                    $input .= ' 利润：' . $profit;
                    $profitRate = 0;
                    $input .= ' 利润率' . $profitRate . '<br>';
                }
            }

            $messages = $input;

        } else {
            $messages = '没有获取到运费';
        }

        echo $this->successJson(array('message' => $messages));

    }

    /**
     * @desc ebay所有停售产品，在线listing直接下架
     * @link /ebay/ebayproduct/autoshelfproducts/accountID/1/sku/111
     */
    public function actionAutoshelfproducts()
    {
        set_time_limit(5 * 3600);
        ini_set('display_errors', true);
        error_reporting(E_ALL);

        $warehouseSkuMapModel = new WarehouseSkuMap();
        $logModel = new EbayLog();
        $ebayProductVariationModel = new EbayProductVariation();
        $ebayProductModel = new EbayProduct();

        //账号
        $accountID = Yii::app()->request->getParam('accountID');

        //指定某个特定sku----用于测试
        $setSku = Yii::app()->request->getParam('sku');

        $select = 't.sku';
        $eventName = 'auto_shelf_products';
        $limit = 1000;
        $offset = 0;
        $stocks = 0;

        //取出海外仓账号id
        $filterAccountIds = array();
        $overseaAccounts = EbayAccount::model()->getAccountsByShortNames(EbayAccount::$OVERSEAS_ACCOUNT_SHORT_NAME);
        if ($overseaAccounts) {
            foreach ($overseaAccounts as $account) {
                $filterAccountIds[] = $account['id'];
            }
        }

        //如果是海外仓的退出
        if (in_array($accountID, $filterAccountIds)) {
            exit('此账号为海外仓，不运行');
        }

        if ($accountID) {
            try {
                //写log
                $logID = $logModel->prepareLog($accountID, $eventName);
                if (!$logID) {
                    exit('日志写入错误');
                }
                //检测是否可以允许
                if (!$logModel->checkRunning($accountID, $eventName)) {
                    $logModel->setFailure($logID, Yii::t('system', 'There Exists An Active Event'));
                    exit('There Exists An Active Event');
                }

                //设置运行
                $logModel->setRunning($logID);

                do {
                    $command = $ebayProductVariationModel->getDbConnection()->createCommand()
                        ->from($ebayProductVariationModel->tableName() . " as t")
                        ->leftJoin($ebayProductModel->tableName() . " p", "p.id=t.listing_id")
                        ->select("t.id,t.listing_id,t.sku, t.sku_online, t.account_id, t.quantity, t.item_id, p.site_id, p.is_multiple,p.location")
                        ->where('p.account_id = ' . $accountID)
                        ->andWhere('p.item_status=' . EbayProduct::STATUS_ONLINE)
                        ->andWhere('p.is_multiple= 0 or (p.is_multiple= 1 and t.quantity_available>0)');//防重复,条件为单品全部和多属性可用库存大于0

                    if ($setSku) {
                        $command->andWhere("t.sku = '" . $setSku . "'");
                    }
                    $command->limit($limit, $offset);
                    $variantListing = $command->queryAll();
                    $offset += $limit;
                    if (!$variantListing) {
                        break;
                    }

                    $skuArr = array();
                    $skuListArr = array();
                    foreach ($variantListing as $listingValue) {
                        $skuArr[] = $listingValue['sku'];
                    }

                    //数组去重
                    $skuUnique = array_unique($skuArr);

                    $conditions = 't.available_qty <= :available_qty AND t.warehouse_id = :warehouse_id AND p.product_is_multi != 2 AND p.product_status IN(6,7) AND t.sku IN(' . MHelper::simplode($skuUnique) . ')';
                    $param = array(
                        ':available_qty' => 0,
                        ':warehouse_id' => WarehouseSkuMap::WARE_HOUSE_GM
                    );
                    // $limits = "{$offset},{$limit}";
                    $skuList = $warehouseSkuMapModel->getSkuListLeftJoinProductByCondition($conditions, $param, '', $select);
                    if (!$skuList) {
                        continue;
                    }

                    unset($skuArr);

                    foreach ($skuList as $skuVal) {
                        $skuListArr[] = $skuVal['sku'];
                    }

                    foreach ($variantListing as $variant) {
                        //hongkong跳过 2017-02-15
                        if (strtolower($variant['location']) == 'hong kong' || strtolower($variant['location']) == 'hongkong') {
                            continue;
                        }
                        if (!in_array($variant['sku'], $skuListArr)) {
                            continue;
                        }

                        //多属性改库存为0,单品下架  2017-04-11 qzz
                        if($variant['is_multiple'] == 1){
                            $ebayProductModel->ebayVariationProductOfflineByVaritionID($variant['id']);
                        }else{
                            $ebayProductModel->ebayProductOfflineByID($variant['listing_id']);
                        }
                    }
                } while ($variantListing);
                $logModel->setSuccess($logID, "success");

            } catch (Exception $e) {
                if (isset($logID) && $logID) {
                    $logModel->setFailure($logID, $e->getMessage());
                }
                echo $e->getMessage() . "<br/>";
            }
        } else {
            $accountList = EbayAccount::model()->getIdNamePairs();
            foreach ($accountList as $key => $value) {
                MHelper::runThreadSOCKET('/' . $this->route . '/accountID/' . $key);
                sleep(1);
            }
        }
    }

    /**
     * 更新描述中的http为https
     * 0 http://w.neototem.com,
     * 1 http://d.marsallo.com,
     * 2 http://k.fozoom.com
     * 3 http://ebayapp.vakind.info   css
     * 图片服务器配置https
     * @link /ebay/ebayproduct/updateonlinedescription/account_id/61/item_id/xxx/sku/123355/site_id/0
     */
    public function actionUpdateOnlineDescription()
    {
        //die('forbidden');

        set_time_limit(3600);
        error_reporting(E_ALL);
        ini_set('memory_limit', '1024M');
        ini_set("display_errors", true);

        $sku = Yii::app()->request->getParam('sku');
        $accountID = Yii::app()->request->getParam('account_id');
        $siteID = Yii::app()->request->getParam('site_id');
        $itemID = Yii::app()->request->getParam('item_id');
        $limit = Yii::app()->request->getParam('limit', 1000);
        $groupID = Yii::app()->request->getParam('group_id');//服务器分组运行id

        $ebayProductModel = new EbayProduct();
        $ebayProductExtendModel = new EbayProductExtend();
        $ebayDescriptionLogModel = new EbayDescriptionLog();

        if ($accountID) {
            try {
                $logModel = new EbayLog();
                $eventName = "update_description";
                $logID = $logModel->prepareLog($accountID, $eventName);
                if (!$logID) {
                    throw new Exception("Create Log ID Failure");
                }
                //检测是否可以允许
                if (!$logModel->checkRunning($accountID, $eventName)) {
                    throw new Exception("There Exists An Active Event");
                }
                //设置运行
                $logModel->setRunning($logID);

                //1、获取listing 的描述信息
                $command = $ebayProductModel->getDbConnection()->createCommand()
                    ->from($ebayProductModel->tableName() . " as t")
                    ->leftJoin($ebayProductExtendModel->tableName() . " p", "t.id=p.listing_id")
                    ->select("t.id, t.sku, t.sku_online, t.account_id, t.item_id, t.site_id, t.listing_type, p.description")
                    ->where('t.account_id = ' . $accountID)
                    ->andWhere('t.item_status = ' . EbayProduct::STATUS_ONLINE)
                    ->andWhere('t.do_statu_2 = 0')
                    ->andWhere('t.create_time < "2017-01-13 16:30:00"')
                    ->andWhere('p.description <> ""');
                if ($sku) {
                    $command->andWhere("t.sku = '" . $sku . "'");
                }
                if ($itemID) {
                    $command->andWhere("t.item_id = '" . $itemID . "'");
                }
                if (!is_null($siteID)) {
                    $command->andWhere("t.site_id = " . $siteID);
                }
                $command->limit($limit);
                $productList = $command->queryAll();

                if ($productList) {
                    //2、匹配替换
                    //$searchArr = array('http://w.neototem.com', 'http://d.marsallo.com', 'http://k.fozoom.com', 'http://ebayapp.vakind.info');
                    //$replaceArr = array('https://w.neototem.com', 'https://d.marsallo.com', 'https://k.fozoom.com', 'https://ebayapp.vakind.info');
                    //$descriptionNew = str_replace($searchArr, $replaceArr, $productInfo['description']);

                    $searchCss = array('http://ebayapp.vakind.info', 'http://www.vakind.info');
                    $replaceCss = array('https://usergoodspic004.photoebucket.com');

                    $searchImg = '/http:\/\/(w\.neototem\.com|d\.marsallo\.com|k\.fozoom\.com)/i';
                    $replaceImg = array(
                        'https://usergoodspic001.photoebucket.com',
                        'https://hjwlimgs.photoebucket.com',
                        'https://szjmpics.photoebucket.com'
                    );
                    $replaceImgCount = count($replaceImg);
                    foreach ($productList as $productInfo) {

                        if ($productInfo['description']) {
                            //替换css
                            $descriptionNew = str_replace($searchCss, $replaceCss, $productInfo['description']);

                            //替换图片
                            preg_match_all($searchImg, $descriptionNew, $matches);
                            $strCount = count($matches[0]);//查找总个数
                            for ($i = 0; $i < $strCount; $i++) {
                                $remainder = $i % $replaceImgCount;//求余
                                $descriptionNew = preg_replace($searchImg, $replaceImg[$remainder], $descriptionNew, 1);
                            }

                            //3、调用接口
                            if ($productInfo['listing_type'] == 'FixedPriceItem') {
                                $requestModel = new ReviseFixedPriceItemRequest();
                            } else {
                                $requestModel = new ReviseItemRequest();
                            }
                            $requestModel->setAccount($accountID)->setItemID($productInfo['item_id']);
                            $requestModel->setDescription($descriptionNew);
                            $response = $requestModel->setRequest()->sendRequest()->getResponse();

                            $recordData = array();
                            if ($requestModel->getIfSuccess()) {
                                $statusData = array('do_statu_2' => 1);
                                $recordData['status'] = 1;
                                $recordData['message'] = "success";
                            } else {
                                $statusData = array('do_statu_2' => 2);
                                $msg = $requestModel->getErrorMsg();
                                $recordData['status'] = 2;
                                $recordData['message'] = $msg;
                            }

                            $dbtransaction = $ebayProductModel->getDbConnection()->beginTransaction();
                            try {
                                //4、更新状态
                                $ebayProductModel->getDbConnection()->createCommand()
                                    ->update($ebayProductModel->tableName(), $statusData, "id=" . $productInfo['id']);

                                $recordData['sku'] = $productInfo['sku'];
                                $recordData['account_id'] = $accountID;
                                $recordData['item_id'] = $productInfo['item_id'];
                                $recordData['listing_id'] = $productInfo['id'];
                                $recordData['description'] = $descriptionNew;
                                $recordData['create_time'] = date("Y-m-d H:i:s");
                                $recordData['update_time'] = date("Y-m-d H:i:s");
                                //5、记录到表
                                $ebayDescriptionLogModel->getDbConnection()->createCommand()->insert($ebayDescriptionLogModel->tableName(), $recordData);

                                $dbtransaction->commit();
                            } catch (Exception $ex) {
                                if ($dbtransaction) {
                                    $dbtransaction->rollback();
                                }
                            }
                        }
                        unset($productInfo);
                        unset($recordData);
                    }
                }
                $logModel->setSuccess($logID);
            } catch (Exception $e) {
                if ($logID) {
                    $logModel->setFailure($logID, $e->getMessage());
                }
                echo $e->getMessage() . "<br/>";
            }
        } else {
            $sumDomain = 4;//服务器总数
            $accountList = EbayAccount::model()->getIdNamePairs();
            foreach ($accountList as $key => $value) {
                $domainRemainder = $key % $sumDomain + 1;    //余数+1
                if ($groupID == $domainRemainder) {
                    //echo $this->route.'/account_id/'.$key.'/group_id/'.$groupID;echo "<br>";
                    MHelper::runThreadSOCKET('/' . $this->route . '/account_id/' . $key . '/group_id/' . $groupID);
                    sleep(5);
                }
            }
        }
    }

    /**
     * @desc 拉取Item
     * @author ketu.lai
     * @date 2017/02/20
     */
    public function actionPullItemById()
    {
        if (Yii::app()->request->getIsPostRequest()) {
            // $results = array();
            $message = array();
            $hasError = false;
            $ebayAccount = null;

            $jsonData = array(
                'message' => '',
                'callbackType' => 'closeCurrent',
                // 'results'=> $results
            );


            $items = array();

            foreach (Yii::app()->request->getParam('items', array()) as $key => $value) {
                if ($value) {
                    $items[] = $value;
                }
            }

            $ebayAccountId = Yii::app()->request->getParam('id');
            //$ebayAccountName = Yii::app()->request->getParam("storeName");
            if ($ebayAccountId) {
                $ebayAccount = EbayAccount::model()->findByPk($ebayAccountId);
            }
            if ($ebayAccount) {
                /**
                 * 根据ITEM ID 拉取
                 */
                if ($items) {
                    foreach ($items as $itemId) {
                        $ebayProductModel = new EbayProduct();


                        $ebayProductModel->setAccountID($ebayAccount->id);
                        $trueOrFalse = $ebayProductModel->getItemInfo($itemId);
                        if ($trueOrFalse) {
                            $message[] = Yii::t("ebay", ':itemId Add successful', array(":itemId" => $itemId));
                        } else {
                            $message[] = Yii::t('ebay', ':itemId add failure', array(':itemId' => $itemId)) . $ebayProductModel->getExceptionMessage();
                        }
                        $hasError = $hasError || !$trueOrFalse;

                    }

                    $jsonData['message'] = $message;
                    if ($hasError) {
                        echo $this->failureJson($jsonData);
                    } else {
                        echo $this->successJson($jsonData);
                    }
                } else { //拉取账号下的所有Listing
                    $ebayProductModel = new EbayProduct();
                    $ebayProductModel->setAccountID($ebayAccount->id);

                    $isSuccess = $ebayProductModel->getNewListingByAccountID($ebayAccount->id, 0, 0);

                    if ($isSuccess) {
                        $jsonData['message'] = Yii::t('ebay', 'Success to pull all listing from :accountName', array(":accountName", $ebayAccount->store_name));
                        echo $this->successJson($jsonData);
                    }
                }
            } else {
                $jsonData['message'] = Yii::t('ebay', 'Invalid Account');
                echo $this->failureJson($jsonData);
            }
            Yii::app()->end();
        }

        $this->render('pull_item_by_id');
    }


    /**
     * @desc 批量替换模板#3058 海外仓帐号老模板更新
     * @link /ebay/ebayproduct/changetemplate/account_id/54/item_id/331735625611
     */
    public function actionChangetemplate() {
        error_reporting(E_ALL);
        ini_set('memory_limit', '1024M');
        ini_set("display_errors", true);
        set_time_limit(5*3600);

        $accountID  = trim(Yii::app()->request->getParam('account_id'));
        $siteID = Yii::app()->request->getParam('site_id');
        $sku  = trim(Yii::app()->request->getParam('sku'));
        $itemID  = trim(Yii::app()->request->getParam('item_id'));
        $limit = Yii::app()->request->getParam('limit',1000);

        $ebayProductModel = new EbayProduct();

        $overseaAccounts = EbayAccount::$OVERSEAS_ACCOUNT_ID;//海外仓帐号
        $date = "2017-03-10 18:00:00";

        if ($accountID != '') {
            if (!in_array($accountID, $overseaAccounts)) { //不是海外仓的帐号退出
                exit('no oversea account');
            }

            try {
                $logModel = new EbayLog();
                $eventName = "change_template";
                $logID = $logModel->prepareLog($accountID, $eventName);
                if (!$logID) {
                    throw new Exception("Create Log ID Failure");
                }
                //检测是否可以允许
                if (!$logModel->checkRunning($accountID, $eventName)) {
                    throw new Exception("There Exists An Active Event");
                }
                //设置运行
                $logModel->setRunning($logID);

                $command = $ebayProductModel->getDbConnection()->createCommand()
                    ->from($ebayProductModel->tableName() . " as t")
                    ->select("t.id, t.sku, t.account_id, t.item_id, t.site_id")
                    ->where('t.account_id = ' . $accountID)
                    ->andWhere('t.item_status = ' . EbayProduct::STATUS_ONLINE)
                    ->andWhere('t.do_statu_3 = 0')
                    ->andWhere("t.create_time < '{$date}'");
                if ($sku) {
                    $command->andWhere("t.sku = '" . $sku . "'");
                }
                if ($itemID) {
                    $command->andWhere("t.item_id = '" . $itemID . "'");
                }
                if (!is_null($siteID)) {
                    $command->andWhere("t.site_id = " . $siteID);
                }
                $command->limit($limit);
                $productList = $command->queryAll();

                foreach ($productList as $v) {
                    $isOk = $ebayProductModel->changOnlineDescriptionByItemID($v['item_id'], $v['account_id'], 1);
                    $recordData = array();
                    if ($isOk) {
                        $updateStatus = array('do_statu_3'=>1);
                        $recordData['status'] = 1;
                        $recordData['message'] = 'ok';
                    } else {
                        $updateStatus = array('do_statu_3'=>2);
                        $recordData['status'] = 2;
                        $recordData['message'] = $ebayProductModel->getExceptionMessage();
                    }
                    $ebayProductModel->getDbConnection()->createCommand()->update($ebayProductModel->tableName(),$updateStatus,"item_id='{$v['item_id']}'");

                    //记录日志表
                    $recordData['sku'] = $v['sku'];
                    $recordData['item_id'] = $v['item_id'];
                    $recordData['account_id'] = $v['account_id'];
                    $recordData['site_id'] = $v['site_id'];
                    $recordData['create_time'] = date("Y-m-d H:i:s");
                    $ebayProductModel->getDbConnection()->createCommand()->insert("ueb_ebay_change_template_log", $recordData);
                    unset($recordData);
                }

                $logModel->setSuccess($logID);
            } catch (Exception $e) {
                if ($logID) {
                    $logModel->setFailure($logID, $e->getMessage());
                }
                echo $e->getMessage() . "<br/>";
            }

        }else{

            $accountInfos = EbayAccount::getAbleAccountList();
            $accountIDs = array();
            foreach ($accountInfos as $v) {
                if (!in_array($v['id'], $overseaAccounts)) {
                    continue;
                }
                $accountIDs[] = $v['id'];
            }
            $groupData = MHelper::getGroupData($accountIDs,4);//分组跑
            foreach ($groupData as $accountIDArr) {
                foreach ($accountIDArr as $account_id) {
                    $url = Yii::app()->request->hostInfo.'/'.$this->route.'/account_id/'.$account_id.'/limit/'.$limit;
                    echo $url." <br>\r\n";
                    MHelper::runThreadBySocket($url);
                }
                sleep(3600);
            }
        }
        die('finish');
    }
    //   /ebay/ebayproduct/ceshi/account_id/2/sale_price/12
    public function actionCeshi(){
        $account_id = Yii::app()->request->getParam('account_id');
        $saleprice  = Yii::app()->request->getParam('sale_price');

        $ebayAccountPayPalGroupModel = new EbayAccountPaypalGroup();
        $payPalEmail = $ebayAccountPayPalGroupModel->getEbayPaypal($account_id,$saleprice);
        if($payPalEmail==false){
            throw new Exception($ebayAccountPayPalGroupModel->getErrorMessage(),132);
        }
        echo $payPalEmail;

    }
}