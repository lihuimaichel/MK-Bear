<?php
/**
 * @desc lazada listing控制器类
 * @author Liutf
 *
 */
class LazadaproductupdateController extends UebController {

    /** @var string 异常信息*/
    protected $exception = null;

    /**
     * @desc 设置异常信息
     * @param string $message
     */
    public function setExceptionMessage($message){
        $this->exception = $message;
    }

    /**
     * @desc 获取异常信息
     * @return string
     */
    public function getExceptionMessage(){
        return $this->exception;
    }

    /**
     * @desc 设置访问规则
     * @see CController::accessRules()
     */
    public function accessRules() {
        return array(
            array(
                'allow',
                'users' => ('*'),
                'actions' => array(),
            ),
        );
    }

    /**
     * @desc 列表页
     */
    public function actionList() {
        $model	= new LazadaProductUpdate();
        $this->render('list', array('model' => $model));
    }

    /**
     * 改变产品价格
     */
    public function actionChangeprice() {
        $id	= Yii::app()->request->getParam('id');
        $changeWay = Yii::app()->request->getParam('val');
        if (Yii::app()->request->isAjaxRequest && !empty($_POST['LazadaProductUpdate'])) {
            $productObj	= UebModel::model('LazadaProductUpdate')->find('id = "'.$id.'"' );
            if (!empty($_POST['LazadaProductUpdate']['changeway'])){
                $price	= LazadaProductUpdate::getPrice($_POST['LazadaProductUpdate']['variable_price'], $_POST['LazadaProductUpdate']['changeway'], $productObj->price);
            }else {
                $price	= $_POST['LazadaProductUpdate']['price'];
            }
            $sku	= $productObj->seller_sku;
            $siteID	= $productObj->site_id;
            $accountID	= $productObj->account_id;
            if (empty($sku) || empty($id)) {
                echo $this->failureJson(array(
                    'message' => Yii::t('lazada_product', 'Not Specify Sku Which Need To Inactive'),
                ));
                Yii::app()->end();
            }
            $logID = LazadaLog::model()->prepareLog($accountID,LazadaProductUpdate::EVENT_NAME);
            if ($logID) {
                //插入本次log参数日志(用来记录请求的参数)
                $time = date('Y-m-d H:i:s');
                $eventLog = LazadaLog::model()->saveEventLog(LazadaProductUpdate::EVENT_NAME, array(
                    'log_id'        => $logID,
                    'account_id'    => $accountID,
                    'start_time'    => $time,
                    'end_time'      => $time,
                    'status'		=> $productObj->status,
                ));
                //设置日志为正在运行
                LazadaLog::model()->setRunning($logID);
                $lazadaProductUpdateModel = new LazadaProductUpdate();
                //更改lazada账号产品状态
                $flag = $lazadaProductUpdateModel->updateProductsPrice($siteID, $accountID, $sku, $price);
                //更新日志信息
                if( $flag ){
                    LazadaLog::model()->setSuccess($logID);
                    LazadaLog::model()->saveEventStatus(LazadaProductUpdate::EVENT_NAME, $eventLog, LazadaLog::STATUS_SUCCESS);
                    //根据产品信息更新库中产品状态
                    LazadaProductUpdate::model()->updatePrice($productObj->id, $price);
                    echo $this->successJson(array(
                        'message' => Yii::t('system', 'Update successful'),
                        'forward' => '/lazada/lazadaproductupdate/list',
                        'navTabId' => 'page' . LazadaProductUpdate::getIndexNavTabId(),
                        'callbackType' => 'closeCurrent',
                    ));
                    Yii::app()->end();
                }else{
                    LazadaLog::model()->setFailure($logID, $lazadaProductUpdateModel->getExceptionMessage());
                    LazadaLog::model()->saveEventStatus(LazadaProductUpdate::EVENT_NAME, $eventLog, LazadaLog::STATUS_FAILURE);
                }
            }
            echo $this->failureJson(array(
                'message' => Yii::t('system', 'Update failure'),
            ));
            Yii::app()->end();
        }

        $model = UebModel::model('LazadaProductUpdate')->find('id = "'.$id.'"' );
        $this->render('_update_price_form', array('model' => $model, 'changway' => $changeWay));
    }

    
    /**
     * @desc 查询库存记录，如果<=1且已上线，则将sku及site_id,account_id信息保存到零库存表
     */
    public function actionRecordZeroStockSku(){
        error_reporting(E_ALL);
        set_time_limit(3600);
        ini_set("display_errors", true);
        //设置测试环境运行程序
        $testFlag = false;//是否为测试标示
        $type = Yii::app()->request->getParam("type");          // sku类型 : 0库存<=1的，1滞销品，2欠货待处理';
        if(!$type){
            $type = 0;
        }
        $runType = Yii::app()->request->getParam("runtype");
        $testSKUs = Yii::app()->request->getParam("sku");
        $testAccountID = Yii::app()->request->getParam("account_id");
        $testSkuList = array();
        //测试环境下必须指定sku和账号
        if($runType != "y" && (empty($testSKUs) || empty($testAccountID))){
            exit("测试下必须指定sku列表和账号，多个sku之间用半角,隔开。示例：{$this->route}/account_id/1/sku/1123,22444,3434.09");
        }elseif ($runType != "y"){
            $testFlag = true;
            $testSkuList = explode(",", $testSKUs);
        }

        $limit          = 100;
        $account_id     = 0;
        $lazadaLogModel = new LazadaLog();
        //记录事件日志
        $logID = $lazadaLogModel->prepareLog($account_id,  LazadaProductUpdate::EVENT_NAME_RECORD_ZERO_STOCK);
        if ($logID) {
            //检查当前账号是否可以记录
            $checkRunning = $lazadaLogModel->checkRunning($account_id, LazadaProductUpdate::EVENT_NAME_RECORD_ZERO_STOCK);
            if( !$checkRunning ){
                $lazadaLogModel->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
            } else {
                //插入本次log参数日志
                $time = date('Y-m-d H:i:s');
                $eventLog = $lazadaLogModel->saveEventLog(LazadaProductUpdate::EVENT_NAME_RECORD_ZERO_STOCK, array(
                    'log_id'        => $logID,
                    'start_time'    => $time,
                    'end_time'      => $time,
                ));
                //设置日志为正在运行
                $lazadaLogModel->setRunning($logID);
                $flag = false;
                $flag_while = true;
                $offset = 0;

                while( $flag_while ){

                    if( !$testFlag ){
                        if( $type == 0 ){
                            $zero_sku_list = WarehouseSkuMap::model()->getZeroStockSku($limit, $offset);
                        } elseif ( $type == 1 ){
                            //滞销
                            $productStatus = Product::STATUS_HAS_UNSALABLE . "," . Product::STATUS_WAIT_CLEARANCE;
                            $conditions = "product_status in(". $productStatus .") and product_is_multi in (0, 1)";
                            $zero_sku_list = Product::model()->dbConnection->createCommand()
                                ->select( 'sku' )
                                ->from( Product::tableName() )
                                ->where( $conditions )
                                ->limit( $limit )
                                ->offset( $offset )
                                ->queryColumn();
                        } elseif ( $type == 2 ){
                            //待处理
                            $zero_sku_list = Order::model()->dbConnection->createCommand()
                                ->select( "sku" )
                                ->from(Order::tableName() . " as A")
                                ->leftJoin(OrderDetail::model()->tableName() . " as B", "B.order_id = A.order_id")
                                ->where("A.complete_status = 1 and B.pending_status = 1")
                                ->group("B.sku")
                                ->limit( $limit )
                                ->offset( $offset )
                                ->queryColumn();
                        } elseif ($type == 5){//手动导入的sku 2016-02-03 add
                            $conditions = "lazada_status=0";
                            $params = array();
                            $limits = "{$offset},{$limit}";
                            $productImportSku = new ProductImportSku;
                            $zero_sku_list = $productImportSku->getSkuListByCondition($conditions, $params, $limits, "sku");
                        }
                        $offset += $limit;
                    } else {
                        $zero_sku_list = $testSkuList;
                        $flag_while = false;
                        echo "set test Skulist=". implode(",", $testSkuList) . "<br/>";
                    }
                    if($zero_sku_list){
                        $updateSKUS = array();//2016-02-03 add
                        foreach ($zero_sku_list as $value){
                            if (!isset($updateSKUS[$value['sku']])) {//2016-02-03 add
                                $updateSKUS[$value['sku']] = $value['sku'];
                            }
                        }
                        // 查出库存<=1且已刊登的sku
                        $zero_online_sku_list = LazadaProduct::model()->getInfoBySku($zero_sku_list);
                        if($zero_online_sku_list){
                            foreach ($zero_online_sku_list as $value){
                                /* if (!isset($updateSKUS[$value['sku']])) {//2016-02-03 add
                                    $updateSKUS[$value['sku']] = $value['sku'];
                                } */
                                $value['type'] = $type;
                                $exist_sku = LazadaZeroStockSku::model()->getExistZeroStockSku($value);
                                //查询零库存表是否有未提交的数据，没有就添加
                                if( !$exist_sku ){
                                    $data = array(
                                        'product_id'    => $value['product_id'],
                                        'seller_sku'    => $value['seller_sku'],
                                        'sku'           => $value['sku'],
                                        'account_id'    => $value['account_id'],
                                        'site_id'       => $value['site_id'],
                                        'old_quantity'  => $value['quantity'],
                                        'create_time'   => date('Y-m-d H:i:s'),
                                        'update_time'   => date('Y-m-d H:i:s'),
                                        'status'        => 0,
                                        'type'          => $type,
                                    );
                                    $result = LazadaZeroStockSku::model()->dbConnection->createCommand()->insert(LazadaZeroStockSku::tableName(), $data);
                                    if( $result ){
                                        $flag = true;
                                        //$flag_while = false;
                                    }
                                }

                            }
                        }
                        //手动的sku还需要更新处理状态  2016-02-03 add
                        if($type == 5 && $updateSKUS){
                            ProductImportSku::model()->updateDataByCondition("lazada_status=0 AND sku in(". MHelper::simplode($updateSKUS) .")", array('lazada_status'=>1));
                        }
                    } else {
                        $flag_while = false;
                    }
                }

                //更新日志信息
                if($flag){
                    $lazadaLogModel->setSuccess($logID);
                    $lazadaLogModel->saveEventStatus(LazadaProductUpdate::EVENT_NAME_RECORD_ZERO_STOCK, $eventLog, LazadaLog::STATUS_SUCCESS);
                } else {
                    $lazadaLogModel->setFailure($logID, 'save failed');
                    $lazadaLogModel->saveEventStatus(LazadaProductUpdate::EVENT_NAME_RECORD_ZERO_STOCK, $eventLog, LazadaLog::STATUS_FAILURE);
                }
            }
        }
        //exit;
        //更新
        $this->actionUpdateOnlineQuantityToZero( $testFlag, $testAccountID, $type );
    }

    /**
     * @desc 查询零库存表，按站点账号批量更新sku的数量
     */
    public function actionUpdateOnlineQuantityToZero( $testFlag = false, $testAccountID = '', $type = 0 ){
        ini_set("display_errors", true);
        set_time_limit(6*3600);
        $limit     = 100;
        $site_list = LazadaSite::getSiteList();
        foreach ($site_list as $site_id => $value){
            //根据账号查出待更新sku
            $account_list = LazadaAccount::model()->getAccountList($site_id);
            foreach ($account_list as $account_id => $seller_name){
                if( $testFlag ){
                    $account_id = $testAccountID;
                    //$site_id = 1;
                    echo "set test account_id=".$testAccountID . "<br/>";
                }
                $condition = 'site_id = ' . $site_id . ' and account_id = ' . $account_id . " and status = 0 and type = " . $type ;
                //按站点和账号更新
                $lazadaLogModel = new LazadaLog();
                //按账号记录事件日志
                $logID = $lazadaLogModel->prepareLog($account_id,  LazadaProductUpdate::EVENT_NAME_UPDATE_QUANTITY);
                if ($logID) {
                    //检查当前账号是否可以更新数量
                    $checkRunning = $lazadaLogModel->checkRunning($account_id, LazadaProductUpdate::EVENT_NAME_UPDATE_QUANTITY);
                    if( !$checkRunning ){
                        $lazadaLogModel->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
                    } else {
                        //插入本次log参数日志(用来记录请求的参数)
                        $time = date('Y-m-d H:i:s');
                        $eventLog = $lazadaLogModel->saveEventLog(LazadaProductUpdate::EVENT_NAME_UPDATE_QUANTITY, array(
                            'log_id'        => $logID,
                            'account_id'    => $account_id,
                            'start_time'    => $time,
                            'end_time'      => $time,
                        ));
                        //设置日志为正在运行
                        $lazadaLogModel->setRunning($logID);
                        $flag = false;
                        $flag_while = true;
                        $offset = 0;
                        while( $flag_while ){
                            $update_sku = array();
                            $update_id  = array();
                            $data = array(
                                'column'        => 'id, seller_sku',
                                'condition'     => $condition ,
                                'limit'         => $limit,
                                'offset'        => $offset,
                                'order'        => 'id'
                            );
                            $zero_sku_list = LazadaZeroStockSku::model()->getList($data);
                            $offset += $limit;
                            if( $zero_sku_list ){
                                foreach ($zero_sku_list as $value){
                                    $update_sku[] = $value['seller_sku'];
                                    $update_id[] = $value['id'];
                                }

                                //api批量更新线上sku数量
                                try {
                                    $request = new ProductUpdateRequest();
                                    $request->setSellerSku($update_sku);
                                    $request->setQuantity(0);
                                    $response = $request->setSiteID($site_id)->setAccount($account_id)->setRequest()->sendRequest()->getResponse();
                                    if( $request->getIfSuccess() ){//上传成功，等待回复

                                        //添加feed
                                        LazadaFeed::model()->addRecord(array(
                                            'feed_id'       => $response->Head->RequestId,
                                            'site_id'       => $site_id,
                                            'status'        => LazadaFeed::STATUS_QUEUED,
                                            'create_time'   => date('Y-m-d H:i:s'),
                                            'account_id'    => $account_id,
                                            'action'        => LazadaFeed::ACTION_PRODUCT_UPDATE,
                                        ));


                                        $flag = true;
                                        $updateArr = array(
                                            'feed_id'       => $response->Head->RequestId,
                                            'update_time'   => date('Y-m-d H:i:s'),
                                            'status'        => 1    //改为已修改
                                        );
                                        LazadaZeroStockSku::model()->dbConnection->createCommand()->update(LazadaZeroStockSku::tableName(), $updateArr, 'id IN ('. MHelper::simplode($update_id) . ')');
                                    } else {
                                        $this->setExceptionMessage($request->getErrorMsg());
                                    }
                                } catch (Exception $e) {
                                    $this->setExceptionMessage($request->getErrorMsg());
                                }


                            } else {
                                $flag_while = false;
                            }
                        }
                        //更新日志信息
                        if($flag){
                            $lazadaLogModel->setSuccess($logID);
                            $lazadaLogModel->saveEventStatus(LazadaProductUpdate::EVENT_NAME_UPDATE_QUANTITY, $eventLog, LazadaLog::STATUS_SUCCESS);
                        } else {
                            $lazadaLogModel->setFailure($logID, $this->getExceptionMessage());
                            $lazadaLogModel->saveEventStatus(LazadaProductUpdate::EVENT_NAME_UPDATE_QUANTITY, $eventLog, LazadaLog::STATUS_FAILURE);
                        }
                    }
                }
                if( $testFlag){
                    break;
                }
            }
        }

    }


    /**
     * @desc 检测仓库中的sku库存数量，从而自动更改平台上的库存数量为0
     *       方式一：以erp产品表为主，循环取出去对比仓库库存
     *       方式二：以仓库库存表为主，批量循环取出小于1的sku，再取出对应的产品表中的相关信息，更新在线产品库存
     * @link /lazada/lazadaproductupdate/autochangestockfornostock/auto_account_id/30/sku/101407.01
     */
    public function actionAutochangestockfornostock(){
        ini_set("display_errors", true);
        ini_set("memory_limit","2048M");
        set_time_limit(6*3600);
        error_reporting(E_ALL);
        $limit = 800;
        $offset = 0;
        $select = 't.sku';
        $type = 0;
        $autoAccountID  = Yii::app()->request->getParam("auto_account_id");
        $setSku         = Yii::app()->request->getParam("sku");

        /**------------春节库存调0程序，2017-02-06  可以移除此代码  开始-------------**/
        $times = time();
        $oneTwenty = strtotime('2017-01-20 00:00:00');
        $twoSix = strtotime('2017-02-11 00:00:00');
        /**------------春节库存调0程序，2017-02-06  可以移除此代码  结束-------------**/

        $eventName = LazadaZeroStockSku::EVENT_ZERO_STOCK;
        $warehouseSkuMapModel = new WarehouseSkuMap();
        $lazadaProductModel   = new LazadaProduct();
        $lazadaZeroStockSKUModel = new LazadaZeroStockSku();
        $logModel = new LazadaLog();
        if($autoAccountID){
            try{
                //写log
                $logID = $logModel->prepareLog($autoAccountID, $eventName);
                if(!$logID){
                    exit('日志写入错误');
                }
                //检测是否可以允许
                if(!$logModel->checkRunning($autoAccountID, $eventName)){
                    $logModel->setFailure($logID, Yii::t('system', 'There Exists An Active Event'));
                    exit('There Exists An Active Event');
                }

                //设置运行
                $logModel->setRunning($logID);

                do{
                    $command = $lazadaProductModel->getDbConnection()->createCommand()
                        ->from($lazadaProductModel->tableName())
                        ->select("id,account_auto_id,account_id,site_id,seller_sku,quantity,sku,product_id,sale_price,price,sale_start_date,sale_end_date")
                        ->where('account_auto_id = '.$autoAccountID)
                        ->andWhere("`status` = 1")
                        ->andWhere("quantity>0");
                        if($setSku){
                            $command->andWhere("sku = '".$setSku."'");
                        }
                        $command->limit($limit, $offset);
                    $variantListing = $command->queryAll(); 
                    $offset += $limit;
                    if(!$variantListing){
                        break;
                        // exit("此账号无数据");
                    }

                    $skuArr = array();
                    $skuListArr = array();
                    foreach ($variantListing as $listingValue) {
                        $skuArr[] = $listingValue['sku'];
                    }

                    if ($times < $twoSix) {
                        $conditions = "t.available_qty <= IFNULL(s.day_sale_num,0) 
                                    AND t.warehouse_id = :warehouse_id 
                                    AND p.product_is_multi IN(0,1,2)  
                                    AND p.product_status NOT IN(6,7) 
                                    AND t.sku IN(".MHelper::simplode($skuArr).")";
                        $param = array(':warehouse_id'=>WarehouseSkuMap::WARE_HOUSE_GM);
                        $skuList = $warehouseSkuMapModel->getSkuListLeftJoinProductAndSalesByCondition($conditions, $param, '', $select);
                        $type = 6;
                    }else{
                        $createTime = date("Y-m-d 00:00:00", time()-30*24*3600);
                        $conditions = "t.available_qty < :available_qty 
                                    AND t.warehouse_id = :warehouse_id 
                                    AND p.product_is_multi IN(0,1,2) 
                                    AND p.product_status NOT IN(6,7) 
                                    AND p.create_time< '{$createTime}' 
                                    AND t.sku IN(".MHelper::simplode($skuArr).")";
                        $param = array(':available_qty'=>2, ':warehouse_id'=>WarehouseSkuMap::WARE_HOUSE_GM);
                        $skuList = $warehouseSkuMapModel->getSkuListLeftJoinProductByCondition($conditions, $param, '', $select);
                    }
                    /**------------春节库存调0程序，2017-02-06  可以移除此代码  结束-------------**/

                    // $limits = "{$offset},{$limit}";
                    if(!$skuList){
                        continue;            
                    } 
                    unset($skuArr);

                    foreach ($skuList as $skuVal){
                        $skuListArr[] = $skuVal['sku'];
                    }

                    $insertParams = array();
                    $request = new UpdatePriceQuantityRequestNew();
                    foreach ($variantListing as $variant){
                        if(!in_array($variant['sku'], $skuListArr)){
                            continue;
                        }

                        //检测是否已经运行了
                        if($lazadaZeroStockSKUModel->checkHadRunningForDay($variant['seller_sku'], $variant['account_id'], $variant['site_id'], $variant['product_id'])){
                            continue;
                        }

                        if($variant['sale_price'] <= 0){
                            continue;
                        }

                        $insertArr = array();
                        $message = 'SellerSku:'.$variant['seller_sku'];
                        $time = date("Y-m-d H:i:s");
                        if($variant['sale_start_date'] == '0000-00-00 00:00:00'){
                            $insertArr = array(
                                'SellerSku' => $variant['seller_sku'],
                                'Quantity'  => 0
                            );
                        }else{
                            $insertArr = array(
                                'SellerSku'     => $variant['seller_sku'],
                                'Quantity'      => 0,
                                'Price'         => $variant['price'],
                                'SalePrice'     => $variant['sale_price'],
                                'SaleStartDate' => date('Y-m-d',strtotime($variant['sale_start_date'])),
                                'SaleEndDate'   => date('Y-m-d',strtotime($variant['sale_end_date']))
                            );
                        }

                        $insertParams[] = $insertArr;
                        
                        $status = 2;//成功
                        $addData = array(
                                'product_id'=>  $variant['product_id'],
                                'seller_sku'=>  $variant['seller_sku'],
                                'sku'       =>  $variant['sku'],
                                'account_id'=>  $variant['account_id'],
                                'site_id'   =>  $variant['site_id'],
                                'old_quantity'=>$variant['quantity'],
                                'status'    =>  $status,
                                'msg'       =>  $message,
                                'create_time'=> $time,
                                'type'      =>  $type
                        );

                        $request->setSkus($insertParams);
                        $request->push();
                        $response = $request->setApiAccount($autoAccountID)->setRequest()->sendRequest()->getResponse();
                        if($request->getIfSuccess()){
                            $zeroStockCondition = 'seller_sku = :seller_sku AND product_id = :product_id AND status = 2 AND type = '.$type.' AND is_restore = 0';
                            $zeroStockParam = array(':seller_sku'=>$variant['seller_sku'], ':product_id'=>$variant['product_id']);
                            $existsInfo = $lazadaZeroStockSKUModel->getZeroSkuOneByCondition($zeroStockCondition,$zeroStockParam);
                            if($existsInfo){
                                continue;
                            }else{
                                $lazadaZeroStockSKUModel->saveData($addData);
                            }

                            $productData = array('quantity'=>0,'available'=>0);
                            $lazadaProductModel->getDbConnection()->createCommand()->update($lazadaProductModel->tableName(), $productData, "id = '".$variant['id']."'");
                        }else{
                            // $status = 3;//失败
                            $msg = isset($response->Body->Errors->ErrorDetail->Message)?$response->Body->Errors->ErrorDetail->Message:'';
                            $message = (string)$msg;
                            $quantityNum = str_replace('Sku quantity is less than allocated quantity, minimum is ', '', $message);
                            if($quantityNum && is_numeric($quantityNum)){
                                $doubleParams = array();
                                $insertArr['Quantity'] = $quantityNum;
                                $doubleParams[] = $insertArr;
                                $lazadaProductUpdateModel = new LazadaProductUpdate();
                                $result = $lazadaProductUpdateModel->updatePriceQuantity($autoAccountID,$doubleParams);
                                if($result){
                                    $productData = array('quantity'=>$quantityNum,'available'=>$quantityNum);
                                $lazadaProductModel->getDbConnection()->createCommand()->update($lazadaProductModel->tableName(), $productData, "id = '".$variant['id']."'");
                                }
                            }
                        }
                    }
                }while($variantListing);     
                $logModel->setSuccess($logID, "success");

            }catch (Exception $e){
                if(isset($logID) && $logID){
                    $logModel->setFailure($logID, $e->getMessage());
                }
                echo $e->getMessage()."<br/>";
            }
        }else{
            //循环每个账号发送一个拉listing的请求
            $accountList = LazadaAccount::model()->getListByCondition('id','id > 0 AND `status` = 1');
            foreach($accountList as $accounts){
                MHelper::runThreadSOCKET('/'.$this->route.'/auto_account_id/' . $accounts['id']);
                sleep(10);
            }
        }        
    }
    
    
    /**
     * @desc 恢复从自动置为0的sku的库存
     */
    public function actionRestoreskustockfromskuwarehouse(){
    	ini_set('memory_limit','2048M');
        set_time_limit(10*3600);
        ini_set("display_errors", true);
        error_reporting(E_ALL);

        $warehouseSkuMapModel    = new WarehouseSkuMap();
        $lazadaProductModel      = new LazadaProduct();
        $lazadaZeroStockSKUModel = new LazadaZeroStockSku();
        $logModel                = new LazadaLog();

        //账号
        $accountID = Yii::app()->request->getParam('accountID');

        //指定某个特定sku----用于测试
        $setSku = Yii::app()->request->getParam('sku');

        $select    = 't.sku,t.available_qty';
        $eventName = 'updateipmskustockall';
        $limit     = 300;
        $offset    = 0;

        if(in_array($accountID, LazadaProductUpdate::excludeAccount())){
            exit('此账号不运行');
        }

        if($accountID){
            try{
                //写log
                $logID = $logModel->prepareLog($accountID, $eventName);
                if(!$logID){
                    exit('日志写入错误');
                }
                //检测是否可以允许
                if(!$logModel->checkRunning($accountID, $eventName)){
                    $logModel->setFailure($logID, Yii::t('system', 'There Exists An Active Event'));
                    exit('There Exists An Active Event');
                }

                //设置运行
                $logModel->setRunning($logID);

                do{
                    $command = $lazadaProductModel->getDbConnection()->createCommand()
                        ->from($lazadaProductModel->tableName())
                        ->select("id,account_auto_id,account_id,site_id,seller_sku,quantity,sku,product_id,sale_price,price,sale_start_date,sale_end_date")
                        ->where('account_auto_id = '.$accountID.' AND `status` = 1')
                        ->andWhere("quantity <= 0");
                        if($setSku){
                            $command->andWhere("sku = '".$setSku."'");
                        }
                        $command->limit($limit, $offset);
                    $variantListing = $command->queryAll(); 
                    $offset += $limit;
                    if(!$variantListing){
                        break;
                    }

                    $skuArr = array();
                    $skuListArr = array();
                    $skuQtyArr = array();
                    foreach ($variantListing as $listingValue) {
                        $skuArr[] = $listingValue['sku'];
                    }

                    $createTime = date("Y-m-d 00:00:00", time()-30*24*3600);
                    $conditions = "t.available_qty > :available_qty 
                                AND t.warehouse_id = :warehouse_id 
                                AND p.product_is_multi IN(0,1,2) 
                                AND p.product_status = 4   
                                AND p.create_time < '{$createTime}' 
                                AND t.sku IN(".MHelper::simplode($skuArr).")";
                    $param = array(':available_qty'=>3, ':warehouse_id'=>WarehouseSkuMap::WARE_HOUSE_GM);
                    $skuList = $warehouseSkuMapModel->getSkuListLeftJoinProductByCondition($conditions, $param, '', $select);
                    if(!$skuList){
                        continue;            
                    } 
                    unset($skuArr);

                    foreach ($skuList as $skuVal){
                        //判断sku是否侵权
                        $checkInfringe = ProductInfringe::model()->getProductIfInfringe($skuVal['sku']);
                        if($checkInfringe){
                            continue;
                        }

                        $skuListArr[] = $skuVal['sku'];
                        $skuQtyArr[$skuVal['sku']] = $skuVal['available_qty'];
                    }

                    
                    foreach ($variantListing as $variant){
                        if(!in_array($variant['sku'], $skuListArr)){
                            continue;
                        }

                        if($variant['sale_price'] <= 0){
                            continue;
                        }

                        //恢复数量大于300的恢复成300
                        $quantity = isset($skuQtyArr[$variant['sku']])?$skuQtyArr[$variant['sku']]:0;
                        if($quantity <= 3){
                            continue;
                        }

                        if($quantity > 300){
                            $quantity = 300;
                        }

                        $request = new UpdatePriceQuantityRequestNew();
                        $insertParams = array();
                        $insertArr = array();
                        $message = 'SellerSku:'.$variant['seller_sku'];
                        $time = date("Y-m-d H:i:s");
                        if($variant['sale_start_date'] == '0000-00-00 00:00:00'){
                            $insertArr = array(
                                'SellerSku' => $variant['seller_sku'],
                                'Quantity'  => $quantity
                            );
                        }else{
                            $insertArr = array(
                                'SellerSku'     => $variant['seller_sku'],
                                'Quantity'      => $quantity,
                                'Price'         => $variant['price'],
                                'SalePrice'     => $variant['sale_price'],
                                'SaleStartDate' => date('Y-m-d',strtotime($variant['sale_start_date'])),
                                'SaleEndDate'   => date('Y-m-d',strtotime($variant['sale_end_date']))
                            );
                        }

                        $insertParams[] = $insertArr;
                        
                        $status = 2;//成功
                        $addData = array(
                                'product_id'=>  $variant['product_id'],
                                'seller_sku'=>  $variant['seller_sku'],
                                'sku'       =>  $variant['sku'],
                                'account_id'=>  $variant['account_id'],
                                'site_id'   =>  $variant['site_id'],
                                'old_quantity'=>$variant['quantity'],
                                'status'    =>  $status,
                                'msg'       =>  $message,
                                'create_time'=> $time
                        );

                        $request->setSkus($insertParams);
                        $request->push();
                        $response = $request->setApiAccount($accountID)->setRequest()->sendRequest()->getResponse();
                        if($request->getIfSuccess()){
                            $addData['is_restore'] = 1;
                            $productData = array('quantity'=>$quantity,'available'=>$quantity);
                            $lazadaProductModel->getDbConnection()->createCommand()->update($lazadaProductModel->tableName(), $productData, "id = '".$variant['id']."'");
                        }else{
                            $addData['status'] = 3;
                            $addData['msg'] = $request->getErrorMsg();
                        }

                        $zeroStockCondition = 'seller_sku = :seller_sku AND product_id = :product_id AND status = 2 AND is_restore = 0';
                        $zeroStockParam = array(':seller_sku'=>$variant['seller_sku'], ':product_id'=>$variant['product_id']);
                        $existsInfo = $lazadaZeroStockSKUModel->getZeroSkuOneByCondition($zeroStockCondition,$zeroStockParam);
                        if($existsInfo){
                            $lazadaZeroStockSKUModel->getDbConnection()->createCommand()
                                ->update(
                                    $lazadaZeroStockSKUModel->tableName(), 
                                    array('is_restore'=>1), 
                                    "seller_sku='{$variant['seller_sku']}' AND product_id = '{$variant['product_id']}' AND status = 2 AND is_restore = 0"
                                );
                        }else{
                            $lazadaZeroStockSKUModel->saveData($addData);
                        }

                    }

                }while($variantListing);     
                $logModel->setSuccess($logID, "success");

            }catch (Exception $e){
                if(isset($logID) && $logID){
                    $logModel->setFailure($logID, '运行错误');
                }
                echo $e->getMessage()."<br/>";
            }
        }else{
            $accountList = LazadaAccount::model()->getAbleAccountList();
            foreach($accountList as $key => $value){
                MHelper::runThreadSOCKET('/'.$this->route.'/accountID/' . $value['id']);
                sleep(1);
            }
        }
    }


    /**
     * 批量更新产品表类目
     * @link /lazada/lazadaproductupdate/updateprimarycategory/account_id/1/start_id/1/end_id/1
     */
    public function actionUpdateprimarycategory(){
        set_time_limit(5*3600);
        //取出产品数据
        $lazadaProduct = new LazadaProduct();
        $accountID = Yii::app()->request->getParam('account_id');
        $startID = Yii::app()->request->getParam('start_id');
        $endID = Yii::app()->request->getParam('end_id');
        if(!$accountID){
            exit('请输入账号ID');
        }
        $where = "account_auto_id = '{$accountID}' AND `status` < 3";
        if($startID){
            $where .= " and id > '{$startID}'";
        }
        if($endID){
            $where .= " and id < '{$endID}'";
        }

        $info = $lazadaProduct->filterByCondition('id,account_auto_id,seller_sku,primary_category',$where);
        if($info){
            foreach ($info as $value) {
                $category = $value['primary_category'];
                if(is_numeric($category)){
                    continue;
                }

                $request = new GetProductsRequestNew();
                $params = array();
                $autoAccountID = $value['account_auto_id'];
                $SkuSellerList = '["'.$value['seller_sku'].'"]';
                $request->setSkuSellerList($SkuSellerList);
                $response = $request->setApiAccount($autoAccountID)->setRequest()->sendRequest()->getResponse();
                if($request->getIfSuccess()){
                    $category = isset($response->Body->Products->Product->PrimaryCategory)?$response->Body->Products->Product->PrimaryCategory:'';
                    if(!$category){
                        continue;
                    }
                    $productData = array('primary_category'=>intval($category));
                    $lazadaProduct->getDbConnection()->createCommand()->update($lazadaProduct->tableName(), $productData, "id = '".$value['id']."'");
                    echo $value['id'].'成功<br>';
                }else{
                    echo $value['id'].'失败<br>';
                }

            }
        }
    }
}