<?php
/**
 * @desc paytm产品管理控制器
 * @by AjunLongLive!
 * @since 2017-03-09
 *
 */
class PaytmproductController extends UebController {

    /**
     * @desc 访问过滤配置
     * @see CController::accessRules()
     */
    public function accessRules() {
        return array(
			array(
				'allow',
				'users' => array('*'),
				'actions' => array( 
					'getproducts',
					'checkgetorders',
					'getorderinfo',
					'syncorder',
					'list',
					'getlistingfromapi',
				)
			),
		);
    }
    
    /**
     * @desc 用于查看特定异步线程的运行情况
     *\/paytm/paytmproduct/displayRunStatus/type/warehouses
     */
    public function actionDisplayRunStatus(){
        //屏蔽所有错误
        ini_set("display_errors", false);
        error_reporting(0);
        
        $type = Yii::app()->request->getParam('type','');
        $from = trim(Yii::app()->request->getParam('from',''));
        
        switch($type){
            case 'variation':  
                $sessionReturn = $this->readSession('market', array('variation_status','variation_num','variation_error'));
                echo 'Session ID：' . $sessionReturn['sessionID']. "<br />\n";
                echo '状态：' . $sessionReturn['variation_status'] . "<br />\n";
                echo '更新数量：' . $sessionReturn['variation_num'] . "<br />\n";
                echo '错误信息：' . $this->print_r(json_decode($sessionReturn['variation_error'])) . "<br />\n";
                break;
            case 'warehouses':
                $sessionReturn = $this->readSession('market', array('warehouses_status','warehouses_num','warehouses_error'));
                echo 'Session ID：' . $sessionReturn['sessionID']. "<br />\n";
                echo '状态：' . $sessionReturn['warehouses_status'] . "<br />\n";
                echo '更新数量：' . $sessionReturn['warehouses_num'] . "<br />\n";
                echo '错误信息：' . $this->print_r(json_decode($sessionReturn['warehouses_error'])) . "<br />\n";
                break;
            case 'listing':
                $accountList = PaytmAccount::model()->getIdNamePairs();
                //ksort($accountList);
                
                $tempArray = array();
                $tempNum = count($accountList);
                $j = 0;
                for ($i=0;  $i<666; $i++){
                    if (isset($accountList[$i])){
                        $tempArray[$i] = $accountList[$i];
                        $j++;
                        if ($j == $tempNum) break;
                    }
                }
                $accountList = $tempArray;
                
                foreach ($accountList as $id => $name){
                    $sessionKeyArray = array(
                        "paytm_listing_account_{$from}{$id}_status",
                        "paytm_listing_account_{$from}{$id}_timeStart",
                        "paytm_listing_account_{$from}{$id}_timeEnd",
                        "paytm_listing_account_{$from}{$id}_limit",
                        "paytm_listing_account_{$from}{$id}_while",
                        "paytm_listing_account_{$from}{$id}_whileTime",
                        "paytm_listing_account_{$from}{$id}_error",
                        "paytm_listing_account_{$from}{$id}_nums",
                        "paytm_listing_account_{$from}{$id}_error_sql_main",
                        "paytm_listing_account_{$from}{$id}_error_sql_warehouses",
                        "paytm_listing_account_{$from}{$id}_error_sql_variation"
                    );
                    $sessionReturn = $this->readSession('market', $sessionKeyArray);
                    if (isset($sessionReturn["paytm_listing_account_{$from}{$id}_status"])){
                        echo '用户ID：' . $id . "<br />\n";
                        echo '用户名称：' . $name . "<br />\n";
                        echo '采集状态：' . $sessionReturn["paytm_listing_account_{$from}{$id}_status"] . "<br />\n";
                        echo '采集开始时间：' . $sessionReturn["paytm_listing_account_{$from}{$id}_timeStart"] . "<br />\n";
                        echo '采集结束时间：' . $sessionReturn["paytm_listing_account_{$from}{$id}_timeEnd"] . "<br />\n";
                        echo '采集间隔：' . $sessionReturn["paytm_listing_account_{$from}{$id}_limit"] . " 条每次<br />\n";
                        echo '请求时间：' . $sessionReturn["paytm_listing_account_{$from}{$id}_whileTime"] . " 秒(最近一次)<br />\n";
                        echo '采集循环：' . $sessionReturn["paytm_listing_account_{$from}{$id}_while"] . " 次<br />\n";                       
                        echo '采集总数：' . $sessionReturn["paytm_listing_account_{$from}{$id}_nums"] . " 条<br />\n";
                        echo '采集错误：' . $sessionReturn["paytm_listing_account_{$from}{$id}_error"] . "<br />\n";
                        echo '主表更新错误：' . $sessionReturn["paytm_listing_account_{$from}{$id}_error_sql_main"] . "<br />\n";
                        echo '仓库更新错误：' . $sessionReturn["paytm_listing_account_{$from}{$id}_error_sql_warehouses"] . "<br />\n";
                        echo '子SKU更新错误：' . $sessionReturn["paytm_listing_account_{$from}{$id}_error_sql_variation"] . "<br />\n";
                        echo "<br />\n";
                    }
                }
                break;
            default:
                echo "请输入正确的参数！";
        }
        if (empty($from)){
            echo "
            <script>
                setTimeout(function(){
                    location.reload(true);
                },6000);
            </script>
        ";
        }        
    }
    
    /**
     * @desc 用于异步跑线程
     *\/paytm/paytmproduct/threadRun/type/warehouses
     */
    public function actionThreadRun(){
        $type = Yii::app()->request->getParam('type','');
        $accountID = Yii::app()->request->getParam('id','1');
        $listingLimit = Yii::app()->request->getParam('limit','66');
        $from = trim(Yii::app()->request->getParam('from',''));
        $skus = trim(Yii::app()->request->getParam('skus',''));
        $msg = '';
        $url = '';
        switch($type){
            case 'variation':
                $url = Yii::app()->request->hostInfo . "/paytm/paytmproduct/insertVariation";
                MHelper::runThreadBySocket($url);                
                $this->settingSession('market', array('variation_status' => 'start'));
                break;
            case 'warehouses':
                $url = Yii::app()->request->hostInfo . "/paytm/paytmproduct/insertWarehouses";
                MHelper::runThreadBySocket($url);
                $this->settingSession('market', array('warehouses_status' => 'start'));
                break;
            case 'listing':
                if (!empty($from)) $fromUrl = "/from/{$from}"; else $fromUrl = "";
                if (!empty($skus)) $skusUrl = "/skus/{$skus}"; else $skusUrl = "";
                $url = Yii::app()->request->hostInfo . "/paytm/paytmproduct/getproducts/account_id/{$accountID}/limit/{$listingLimit}{$fromUrl}{$skusUrl}";
                MHelper::runThreadBySocket($url);
                //$this->settingSession('market', array('warehouses_status' => 'start'));
                break;
                
            default:
                $msg = "请输入正确的参数！";
        }
        empty($msg) && $msg = "请求成功：{$type} {$accountID} {$listingLimit} {$url} {$from} {$skus}";
        echo $msg;
    }
    
    /**
     * @desc 批量转移主表子sku数据到子sku分表上
     *
     */
    public function actionInsertVariation(){
        set_time_limit(3600*24);
        $okNum = 0;
        $variationsInfo = PaytmProductMain::model()
                            ->getDbConnection()
                            ->createCommand()
                            ->from(PaytmProductMain::model()->tableName())
                            ->select("
                                 product_id,
                                 parent_id,
                                 sku,
                                 name,
                                 price,
                                 mrp,
                                 paytm_sku,
                                 warehouses,
                                 status,
                                 inventory
                               ")
                            ->where('parent_id IS NOT NULL')
                            ->order('id desc')
                            ->queryAll();
        if ($variationsInfo){
            $this->settingSession('market', array('variation_error' => ''));
            foreach ($variationsInfo as $variations){
                if (!empty($variations['parent_id']) && !empty($variations['product_id'])){
                    //$this->print_r($variations);
                    //break;
                    $return = PaytmProductChild::model()->updateChildSku($variations);
                    if ($return['status'] == 'failure'){
                        //echo $return['msg'] . $variations['product_id'];
                        $sessionReturn = $this->readSession('market', array('variation_error'));
                        $errorArray = json_decode($sessionReturn['variation_error']);
                        $errorArray[] = $variations['product_id'] .' - '. $return['msg'];
                        $this->settingSession('market', array('variation_error' => json_encode($errorArray)));
                    } else {
                        $okNum++;
                        $this->settingSession('market', array('variation_num' => "$okNum"));
                    }
                }
            }
        }
        $this->settingSession('market', array('variation_status' => 'end'));
        if ($okNum > 0){
            echo "更新了 {$okNum} 个数据!";
        }
    }
    
    /**
     * @desc 批量转移主表仓库数据到仓库分表上
     * 
     */
    public function actionInsertWarehouses(){
        set_time_limit(3600*24);
        $okNum = 0;
        $warehousesInfo = PaytmProductMain::model()->getDbConnection()->createCommand()
                                                    ->from(PaytmProductMain::model()->tableName())
                                                    ->select("product_id,sku,paytm_sku,warehouses")
                                                    ->order('id desc')
                                                    ->queryAll();
        if ($warehousesInfo){
            $this->settingSession('market', array('warehouses_error' => ''));
            foreach ($warehousesInfo as $warehouses){                
                if (!empty($warehouses['warehouses'])){
                    $warehousesArray = json_decode($warehouses['warehouses'],1);
                    foreach ($warehousesArray as $warehouse){
                        $warehousesSqlArray = array();
                        $warehousesSqlArray = $warehouse;
                        $warehousesSqlArray['product_id'] = $warehouses['product_id'];
                        $warehousesSqlArray['sku']        = $warehouses['sku'];
                        $warehousesSqlArray['paytm_sku']  = $warehouses['paytm_sku'];
                        //$this->print_r($warehousesSqlArray);
                        //break;
                        $return = PaytmProductWarehouses::model()->updateProductWarehouses($warehousesSqlArray);
                        if ($return['status'] == 'failure'){
                            //echo $return['msg'] . $warehouses['product_id'];
                            $sessionReturn = $this->readSession('market', array('warehouses_error'));
                            $errorArray = json_decode($sessionReturn['warehouses_error']);
                            $errorArray[] = $warehouses['product_id'] .' - '. $return['msg'];
                            $this->settingSession('market', array('warehouses_error' => json_encode($errorArray)));
                        } else {
                            $okNum++;                            
                            $this->settingSession('market', array('warehouses_num' => "$okNum"));
                        }
                    }                   
                }                
            }
        }
        $this->settingSession('market', array('warehouses_status' => 'end'));
        if ($okNum > 0){
            echo "更新了 {$okNum} 个数据!";
        }
    }
    
    /**
     * @desc 异步设置Session,微调自动拉取设置参数
     *\/paytm/paytmproduct/settingSession/id/market/key/test/value/test/debug/1
     */
    public function actionSettingSession(){
        $sessionID = Yii::app()->request->getParam('id','');
        $sessionKey = Yii::app()->request->getParam('key','');
        $sessionValue = Yii::app()->request->getParam('value','');
        $debug = Yii::app()->request->getParam('debug','');
        if (!empty($sessionID) && !empty($sessionKey) && !empty($sessionValue)){
            $this->settingSession($sessionID, array($sessionKey => $sessionValue));
            if($debug == '1'){
               Yii::app()->session->close();
               session_id($sessionID);
               Yii::app()->session->open();
               $this->print_r($_SESSION);
               Yii::app()->session->close();
            } else {
                $sessionReturn = $this->readSession($sessionID, array($sessionKey));
                echo $sessionReturn[$sessionKey];
            }            
            
            
        }
    }
    
    /**
     * @desc 异步批量读取Session
     *
     */
    public function readSession($sessionID,$sessionArray){
        $return = array();
        if (!empty($sessionID) && !empty($sessionArray)){
            Yii::app()->session->close();
            session_id($sessionID);
            Yii::app()->session->open();
            foreach ($sessionArray as $key){
               $return[$key] = $_SESSION[$key];
               if (empty($return[$key]) || $return[$key] == null){
                   $sessionLogInfo = PaytmProductLog::model()->getDbConnection()
                                                   ->createCommand()
                                                   ->from(PaytmProductLog::model()->tableName())
                                                   ->select("do_action")
                                                   ->where("paytm_sku=:paytm_sku_arr",array(":paytm_sku_arr" => $key))
                                                   ->queryRow();
                   $sessionLogInfo && $return[$key] = $sessionLogInfo['do_action'];
               }
            }
            $return['sessionID'] = session_id();
            Yii::app()->session->close();
        }
        return $return;
    }
    
    /**
     * @desc 默认查询日志表,用于错误调试
     *
     */
    public function actionGetList(){
        $table   = Yii::app()->request->getParam('table','ueb_paytm_product_log');
        $select  = Yii::app()->request->getParam('select','*');
        $where   = Yii::app()->request->getParam('where','id IS NOT NULL');
        $limit   = Yii::app()->request->getParam('limit','66');
        $offset  = Yii::app()->request->getParam('offset','0');
        $order   = Yii::app()->request->getParam('order','id DESC');
        $list    = PaytmProductLog::model()->getDbConnection()
                                                   ->createCommand()
                                                   ->from($table)
                                                   ->select($select)
                                                   ->where($where)
                                                   ->limit($limit,$offset)
                                                   ->order($order)
                                                   ->queryAll();
        $this->print_r($list);
    }
    
    /**
     * @desc 异步批量设置Session
     *
     */
    public function settingSession($sessionID,$sessionArray){
        if (!empty($sessionID) && !empty($sessionArray)){
            Yii::app()->session->close();
            session_id($sessionID);
            Yii::app()->session->open();
            foreach ($sessionArray as $key => $val){
                $_SESSION[$key] = $val;
                $sessionLog = array();
                $sessionLog['paytm_sku']   = $key;
                $sessionLog['do_action']   = $val;
                $sessionLog['modify_time'] = date('Y-m-d H:i:s');
                $pkId = PaytmProductLog::model()->getDbConnection()
                                                ->createCommand()
                                                ->from(PaytmProductLog::model()->tableName())
                                                ->select("id")
                                                ->where("paytm_sku=:paytm_sku_arr",array(":paytm_sku_arr" => $key))
                                                ->queryScalar();
                if($pkId){
                    $isOk = PaytmProductLog::model()->getDbConnection()
                                                    ->createCommand()                                                      
                                                    ->update(PaytmProductLog::model()->tableName(), $sessionLog, "id=:id", array(':id'=>$pkId));
                    if (!$isOk){
                        //$return['status'] = 'failure';
                    }
                } else {
                    $isOk = PaytmProductLog::model()->getDbConnection()
                                                    ->createCommand()
                                                    ->insert(PaytmProductLog::model()->tableName(), $sessionLog);
                    if($isOk) {                        
                        //$return['id'] = $pkId;
                    } else {
                        //$return['status'] = 'failure';
                    }
                }
            }
            Yii::app()->session->close();
        }        
    }
    
    /**
     * @desc 产品管理Ajax集中操作
     * @param 全部通过Post方式获取输入参数
     */
    public function actionAjax(){
        $return = array('status'=>'success','msg'=>'');
        $productIDPost = Yii::app()->request->getParam('productIDPost','');      
        $stockValuePost = Yii::app()->request->getParam('stockValuePost','');
        $priceValuePost = Yii::app()->request->getParam('priceValuePost','');
        $batchOfflineJsonPost = Yii::app()->request->getParam('batchOfflineJsonPost','');
        $batchOnlineJsonPost = Yii::app()->request->getParam('batchOnlineJsonPost','');
        $batchChangeStockJsonPost = Yii::app()->request->getParam('batchChangeStockJsonPost','');
        $batchChangePriceJsonPost = Yii::app()->request->getParam('batchChangePriceJsonPost','');
        $typePost = Yii::app()->request->getParam('typePost','');
        switch($typePost){
            case 'offline':
                $modifyArray = array();
                $modifyArray[0]['statusType'] = $typePost;
                $modifyArray[0]['product_id'] = $productIDPost;
                $returnModel = PaytmProductMain::model()->updateProducts($modifyArray);
                if ($returnModel['status'] == 'failure'){
                    $return['status'] = 'failure';
                    $return['msg'] = $returnModel['msg'];
                }
                break;
            case 'online':
                $modifyArray = array();
                $modifyArray[0]['statusType'] = $typePost;
                $modifyArray[0]['product_id'] = $productIDPost;
                $returnModel = PaytmProductMain::model()->updateProducts($modifyArray);
                if ($returnModel['status'] == 'failure'){
                    $return['status'] = 'failure';
                    $return['msg'] = $returnModel['msg'];
                }
                break;
            case 'modifyChildStock':
                $modifyArray = array();
                $modifyArray[0]['qty'] = $stockValuePost;
                $modifyArray[0]['product_id'] = $productIDPost;
                $returnModel = PaytmProductMain::model()->updateProducts($modifyArray);
                if ($returnModel['status'] == 'failure'){
                    $return['status'] = 'failure';
                    $return['msg'] = $returnModel['msg'];
                }
                break;
            case 'modifyChildPrice':
                $modifyArray = array();
                $modifyArray[0]['price'] = $priceValuePost;
                $modifyArray[0]['product_id'] = $productIDPost;
                $returnModel = PaytmProductMain::model()->updateProducts($modifyArray);
                if ($returnModel['status'] == 'failure'){
                    $return['status'] = 'failure';
                    $return['msg'] = $returnModel['msg'];
                }
                break;
            case 'batchOffline':
                $offlineArray = array();
                $allJsonArray = explode('=', $batchOfflineJsonPost);
                foreach ($allJsonArray as $offlineJson){
                    if (trim($offlineJson) != ''){
                        $offlineJsonArray = explode(',', $offlineJson);
                        $tempArray = array(
                            'product_id' => $offlineJsonArray[0],
                            'statusType' => 'offline'
                        );
                        $offlineArray[$offlineJsonArray[1]][] = $tempArray;
                    }
                }
                //print_r($offlineArray);
                ///*
                foreach ($offlineArray as $accoountID => $accountProducts){
                    $returnModel = PaytmProductMain::model()->updateProducts($accountProducts);
                    if ($returnModel['status'] == 'failure'){
                        $return['status'] = 'failure';
                        $return['msg'] .= "$accoountID ".$returnModel['msg'] . "<br />";
                        //break;
                    }
                }
                //*/
                break;
            case 'batchOnline':
                $onlineArray = array();
                $allJsonArray = explode('=', $batchOnlineJsonPost);
                foreach ($allJsonArray as $onlineJson){
                    if (trim($onlineJson) != ''){
                        $onlineJsonArray = explode(',', $onlineJson);
                        $tempArray = array(
                            'product_id' => $onlineJsonArray[0],
                            'statusType' => 'online'
                        );
                        $onlineArray[$onlineJsonArray[1]][] = $tempArray;
                    }
                }
                //print_r($onlineArray);
                ///*
                foreach ($onlineArray as $accoountID => $accountProducts){
                    $returnModel = PaytmProductMain::model()->updateProducts($accountProducts);
                    if ($returnModel['status'] == 'failure'){
                        $return['status'] = 'failure';
                        $return['msg'] .= "$accoountID ".$returnModel['msg'] . "<br />";
                        //break;
                    }
                }
                //*/
                break;
            case 'batchChangeStock':
                $stockArray = array();
                $allJsonArray = explode('=', $batchChangeStockJsonPost);
                foreach ($allJsonArray as $stockJson){
                    if (trim($stockJson) != ''){
                        $stockJsonArray = explode(',', $stockJson);
                        $tempArray = array(
                            'product_id' => $stockJsonArray[0],
                            'qty' => $stockJsonArray[1]
                        );
                        $stockArray[$stockJsonArray[2]][] = $tempArray;
                    }                    
                }
                //print_r($stockArray);
                foreach ($stockArray as $accoountID => $accountProducts){
                    $returnModel = PaytmProductMain::model()->updateProducts($accountProducts);
                    if ($returnModel['status'] == 'failure'){
                        $return['status'] = 'failure';
                        $return['msg'] .= "$accoountID ".$returnModel['msg'] . "<br />";
                        //break;
                    } 
                }
                break;
            case 'batchChangePrice':                
                $priceArray = array();
                $allJsonArray = explode('=', $batchChangePriceJsonPost);
                foreach ($allJsonArray as $priceJson){
                    if (trim($priceJson) != ''){
                        $priceJsonArray = explode(',', $priceJson);
                        $tempArray = array(
                            'product_id' => $priceJsonArray[0],
                            'price' => $priceJsonArray[1]
                        );
                        $priceArray[$priceJsonArray[2]][] = $tempArray;
                    }
                }
                //print_r($priceArray);
                ///*
                foreach ($priceArray as $accoountID => $accountProducts){
                    $returnModel = PaytmProductMain::model()->updateProducts($accountProducts);
                    if ($returnModel['status'] == 'failure'){
                        $return['status'] = 'failure';
                        $return['msg'] .= "$accoountID ".$returnModel['msg'] . "<br />";
                        //break;
                    }
                }
                //*/
                break;
            default:
                $return['status'] = 'failure';    
                $return['msg'] = '请求的类型不能为空';    
        }
        if (empty($return['msg'])){
            $return['msg'] = '数据更新成功！';
        }
        echo  json_encode($return);
    }
    
    /**
     * @desc 列表
     * @link 
     */
    public function actionList() {
        $this->render("list", array(
            "model"	=>	PaytmProductMain::model(),
        ));
    }
    
    /**
     * @desc 拉单
     * @link 
     */
    public function actionGetlistingfromapi() {
        $this->render("getlistingfromapi", array(
            "getProductsUrl"	=>	'/paytm/paytmproduct/getproducts/debug/1/account_id/1',
        ));
    }

	/**
	 * @desc 正常拉单
	 * @link /paytm/paytmproduct/getproducts/debug/1/account_id/1
	 */
	public function actionGetproducts() {
        set_time_limit(3600*24);
        ini_set("display_errors", true);
        error_reporting(E_ALL & ~E_STRICT);
         
        $accountID = trim(Yii::app()->request->getParam('account_id',''));
        $from = trim(Yii::app()->request->getParam('from',''));
        
        if( $accountID){  //根据账号抓取订单信息
            $logModel = new PaytmLog;
            $eventName = PaytmLog::EVENT_GETPRODUCT;   //getproductsnew事件
            $logID = $logModel->prepareLog($accountID,$eventName);
            if( $logID ){
                //1.检查账号是否可拉取订单
                $checkRunning = $logModel->checkRunning($accountID, $eventName);
                if( !$checkRunning && empty($from)){
                    $logModel->setFailure($logID, '已经有一个活动的采集在运作了');
                    echo "已经有一个活动的采集在运作了";
                    $this->settingSession('market', array("paytm_listing_account_{$from}{$accountID}_error" => "已经有一个活动的采集在运作了"));
                } else {
                    //当是用户指定拉取则不写日志，也不控制是否多次采集
                    if (empty($from)){
                        //2.准备拉取日志信息
                        $timeSince = PaytmProductMain::model()->getTimeSince($accountID);
                        
                        //插入本次log参数日志(用来记录请求的参数)
                        $eventLog = $logModel->saveEventLog($eventName, array(
                            'log_id'        => $logID,
                            'account_id'    => $accountID,
                            'since_time'    => date('Y-m-d H:i:s',strtotime($timeSince)),//北京时间
                            'complete_time' => date('Y-m-d H:i:s')//下次拉取时间可以从当前时间点进行,这是北京时间
                        ));
                        
                        //设置日志为正在运行
                        $logModel->setRunning($logID);
                    }
                    
                    //3.拉取订单
                    $paytmProductMain = new PaytmProductMain();
                    $paytmProductMain->setAccountID($accountID);//设置账号
                    $paytmProductMain->setLogID($logID);//设置日志编号

                    if(!empty($_REQUEST['debug'])){
                        $logModel->setSuccess($logID);
                        $logModel->saveEventStatus($eventName, $eventLog, PaytmLog::STATUS_SUCCESS);
                    }
                    
                    $this->settingSession('market', array("paytm_listing_account_{$from}{$accountID}_status" => "正在采集中..."));
                    $this->settingSession('market', array("paytm_listing_account_{$from}{$accountID}_timeStart" => date('Y-m-d H:i:s')));
                    $resetArray = array(
                        "paytm_listing_account_{$from}{$accountID}_limit" => '',
                        "paytm_listing_account_{$from}{$accountID}_while" => '', 
                        "paytm_listing_account_{$from}{$accountID}_whileTime" => '', 
                        "paytm_listing_account_{$from}{$accountID}_error" => '', 
                        "paytm_listing_account_{$from}{$accountID}_nums"  => '',
                        "paytm_listing_account_{$from}{$accountID}_timeEnd"  => '',
                        "paytm_listing_account_{$from}{$accountID}_error_sql_main" => '',
                        "paytm_listing_account_{$from}{$accountID}_error_sql_warehouses" => '',
                        "paytm_listing_account_{$from}{$accountID}_error_sql_variation" => ''
                    );
                    $this->settingSession('market', $resetArray);
                    //拉单
                    $flag = $paytmProductMain->getProducts();   
                    $this->settingSession('market', array("paytm_listing_account_{$from}{$accountID}_timeEnd" => date('Y-m-d H:i:s')));
                    $this->settingSession('market', array("paytm_listing_account_{$from}{$accountID}_status" => "采集结束！"));
                    
                    //4.更新日志信息
                    if (empty($from)){
                        if( $flag ){
                            $logModel->setSuccess($logID);
                            $logModel->saveEventStatus($eventName, $eventLog, PaytmLog::STATUS_SUCCESS);
                        } else {
                            $errMsg = $paytmProductMain->getExceptionMessage();
                            if (mb_strlen($errMsg)> 500 ) {
                                $errMsg = mb_substr($errMsg,0,500);
                            }
                            $logModel->setFailure($logID, $errMsg);
                            $logModel->saveEventStatus($eventName, $eventLog, PaytmLog::STATUS_FAILURE);
                        }
                    }
                    
                    echo json_encode($_REQUEST).($flag ? ' Success ' : ' Failure ').$paytmProductMain->getExceptionMessage()."<br>";   
                    //*/                 
                }
            }
        } else {    //循环可用账号，多线程抓取
            $paytmAccounts = PaytmAccount::model()->getAbleAccountList();
            foreach($paytmAccounts as $account){
                $limit = trim(Yii::app()->request->getParam('limit',''));
                empty($limit) && $limit = 66;
                isset($limit) ? $limitUrl = "/limit/$limit" : $limitUrl = "";
                $url = Yii::app()->request->hostInfo.'/'.$this->route.'/account_id/'.$account['id'] . $limitUrl;
                echo $url." <br>\r\n";
                $this->settingSession('market', array("paytm_listing_account_{$from}{$accountID}_status" => "开始采集:"));
                MHelper::runThreadBySocket($url);
                //break;
                sleep(66);
            }
        }
	}

	/**
	 * @desc 补拉单
	 * @link /paytm/paytmorder/checkgetorders/debug/1/account_id/1/since_time/2017-03-01
	 */
	public function actionCheckgetorders() {
		set_time_limit(3600);
        ini_set("display_errors", true);
        error_reporting(E_ALL & ~E_STRICT);
        
        $accountID = trim(Yii::app()->request->getParam('account_id',''));
        $timeSince = trim(Yii::app()->request->getParam('since_time',''));//2016-11-12T10:30:00
        $day = trim(Yii::app()->request->getParam('day',3));
        $day == '' && $day = 3;//默认3天
        if($timeSince != '') {
        	$sinceTime = strtotime($timeSince);
        } else if($day > 0) {
        	$sinceTime = time()-$day*86400;
        }
        if( $accountID ){//根据账号抓取订单信息
			$logModel = new PaytmLog;
            $eventName = PaytmLog::EVENT_CHECK_GETORDER;//补拉订单事件
            $logID = $logModel->prepareLog($accountID,$eventName);
            if( $logID ){
                //1.检查账号是否可拉取订单
                $checkRunning = $logModel->checkRunning($accountID, $eventName);
                if( !$checkRunning ){
                    $logModel->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
                    echo "There Exists An Active Event";
                }else{       
	                //设置日志为正在运行
	                $logModel->setRunning($logID);
	                //拉取订单
	                $interval = 3600;//秒
	        		$times = ceil((time() - $sinceTime)/$interval);//每小时抓一次,每次最多返回500个单
	        		$errMsg = '';
	        		$isOk = true;
                    echo 'sinceTime: '. date('Y-m-d H:i:s',$sinceTime)."<br>";
                    echo 'times: '. $times."<br>";
	        		for($i=0; $i<$times; $i++) {
                        $startTime = time() - $interval * ($times-$i);
                        $endTime = $startTime + $interval;
                        $startTime = $startTime - 2*60;//每次多拉2分钟
                        echo 'startTime: '.date('Y-m-d H:i:s',$startTime). ' '. $startTime ."<br>";
                        echo 'endTime: '.date('Y-m-d H:i:s',$endTime). ' '. $endTime . "<br>";
		                $paytmOrderMain = new PaytmOrderMain();
		                $paytmOrderMain->setAccountID($accountID);//设置账号
		                $paytmOrderMain->setLogID($logID);//设置日志编号
		                $flag = $paytmOrderMain->getOrders($startTime.'000',$endTime.'000');//拉单
		                $isOk &= $flag;
		                if( !$flag ){
		                	$reMsg = $paytmOrderMain->getExceptionMessage();
		                	$errMsg .= mb_strlen($reMsg)> 500 ? mb_substr($reMsg,0,500) : $reMsg;	
		                }
		            }
					//更新日志信息
	                if( $isOk ){
	                    $logModel->setSuccess($logID);
	                }else{        
	                    $logModel->setFailure($logID, $errMsg);
	                }
	                echo json_encode($_REQUEST).($isOk ? ' Success ' : ' Failure ').$errMsg."<br>";  
		        }
        	}
        }else{//循环可用账号，多线程抓取
            $paytmAccounts = PaytmAccount::model()->getAbleAccountList();
            foreach($paytmAccounts as $account){
                $url = Yii::app()->request->hostInfo.'/'.$this->route.'/account_id/'.$account['id'];
                echo $url." <br>\r\n";
                MHelper::runThreadBySocket($url);
                sleep(60);
            }
        }
        Yii::app()->end('finish');
	}

	/**
	 * @desc 单个拉单
	 * @link /paytm/paytmorder/getorderinfo/debug/1/account_id/1/order_id/##
	 */
	public function actionGetorderinfo() {
		ini_set("display_errors", true);
        error_reporting(E_ALL & ~E_STRICT);

		$orderIds = Yii::app()->request->getParam("order_id");
    	$accountId = Yii::app()->request->getParam("account_id");
    	if(!$orderIds){
    		exit("no orderID");
    	}
    	if(!$accountId){
    		exit("no accountID");
    	}
    	$orderIds = explode(",", $orderIds);
    	//拉单
		$request = new GetOrdersRequest();
        $request->setOrderIDs($orderIds);
        $response = $request->setAccount($accountId)->setRequest()->sendRequest()->getResponse();
        $this->print_r($response);
		if($request->getIfSuccess() && !empty($response)){
			$flag = true;
            $errorMsg = "";
            foreach($response as $order) {//循环订单信息
                $paytmOrderModel = new PaytmOrderMain();
				$paytmOrderModel->setLogID(10000);
				$paytmOrderModel->setAccountID($accountId);//设置账号
                $res = $paytmOrderModel->savePaytmOrder($order);
                $flag &= $res;
                if(!$res){
                    $errorMsg .= $paytmOrderModel->getExceptionMessage()."<br/>";
                }
            }         
	        echo "<br>".$errorMsg."<br>";
		}	
		echo "finish";	
	}

	/**
	 * @desc 同步订单
	 * @link /paytm/paytmorder/syncorder/debug/1/account_id/1/order_id/##
	 */
	public function actionSyncorder() {
        set_time_limit(1800);
        ini_set("display_errors", true);
        error_reporting(E_ALL & ~E_STRICT);
        
        $limit = Yii::app()->request->getParam("limit", 1000);
        $accountID = trim(Yii::app()->request->getParam("account_id",''));
        $platformOrderID = trim(Yii::app()->request->getParam("order_id",''));
        
        $syncTotal = 0;
        $logID = 1000;
        //@todo 增加日志控制
        $logModel = new PaytmLog();
        $virAccountID = 90000;
        $eventName = PaytmLog::EVENT_SYNC_ORDER;
        $logID = $logModel->prepareLog($virAccountID, $eventName);
        if($logID){
            $checkRunning = $logModel->checkRunning($virAccountID, $eventName);
            if(!$checkRunning){
                $logModel->setFailure($logID, "Have a active event");
                exit("Have a active event");
            }
            //设置日志为正在运行
            $logModel->setRunning($logID);
            $paytmAccounts = PaytmAccount::model()->getAbleAccountList();
            foreach($paytmAccounts as $account){
                if(!empty($accountID) && $account['id'] != $accountID){
                    continue;
                }
                $paytmOrderMain = new PaytmOrderMain();
                $paytmOrderMain->setAccountID($account['id']);
                $paytmOrderMain->setLogID($logID);
                $syncCount = $paytmOrderMain->syncOrderToOmsByAccountID($account['id'], $limit, $platformOrderID);
                $syncTotal += $syncCount;
                echo "<br>======={$account['account_name']}: {$syncCount}======".$paytmOrderMain->getExceptionMessage()."<br/>";
            }
            $logModel->setSuccess($logID, 'Total:'.$syncTotal);
            echo "finish";
        }else{
            exit("Create Log Id Failure!!!");
        }
	}

}