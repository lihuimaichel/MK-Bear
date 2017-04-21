<?php
/**
 * @desc Lazada订单相关
 * @author yangsh
 * @since 2016-11-10
 */
class LazadaorderController extends UebController{
    /**
     * AliexpressProduct 模型
     * @var unknown
     */
    protected $_model = null; 
    
    /**
     * (non-PHPdoc)
     * @see CController::init()
     */
    public function  init(){
        $this->_model = new LazadaOrderMain();
        parent::init();
    }
    
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
                    'getorders',//new
                    'checkgetorders',//new
                    'getorderinfo',//new
                    'getmultipleorderitems',//new
                    'syncorder',//new
                    'updateomstrans',//new           
                    'list',                  //hanxy于2017-02-10添加
                )
            ),
        );
    }

    /**
     * @desc 正常拉取订单
     * @author yangsh
     * @since 2016-10-12
     * @link /lazada/lazadaorder/getorders/account_id/9
     */
    public function actionGetorders() {
        set_time_limit(3600);
        ini_set ( 'display_errors', true );
        error_reporting( E_ALL & ~E_STRICT);
        
        $accountId  = trim(Yii::app ()->request->getParam ( 'account_id', ''));//1
        $eventName = LazadaLog::EVENT_GETORDER;//拉单事件

        //参数验证
        $validateMsg = '';
        if (!empty($accountId) && !preg_match('/^\d+$/',$accountId)) {
            $validateMsg .= 'account_id is invalid;';
        }
        if ($validateMsg != '') {
            Yii::app ()->end($validateMsg);
        }
        //开始拉单
        $accountArr = array();
        if ($accountId != '') {
            $accountArr = explode(',', $accountId);
        } else {
            if (!empty(LazadaAccount::$NEW_API_ACCOUNT_IDS)) {
                $accountArr = LazadaAccount::$NEW_API_ACCOUNT_IDS;
            } else {
                $accountList = LazadaAccount::model()->getAbleAccountList();
                foreach ($accountList as $accountInfo) {
                    $accountArr[] = $accountInfo['id'];
                }  
            }
        }
        if (empty($accountArr)) {
            die('accountArr is empty');
        }
        //循环每个账号发送一个拉listing的请求
        foreach ($accountArr as $accountId) {
            try {
                //开始抓取order_id
                $model = new LazadaOrderDownloadNew();
                $model ->setAccountID($accountId);
                $model ->setMode(2);//1:CreatedAfter 2:UpdatedAfter
 
                //get timeArr
                //$timeArr = $model->setTimeArr()->getTimeArr();
                $timeArr = array(
                    'start_time'    => date('Y-m-d H:i:s',time() - 3*3600),//最近3小时内订单
                    'end_time'      => date('Y-m-d H:i:s'),
                );

                //prepareLog
                $logModel = new LazadaLog ();
                $logID = $logModel->prepareLog ( $accountId, $eventName );
                if (!$logID) {
                    throw new Exception('Insert prepareLog failure', 1);
                }
                
                // 1.检查账号是否可拉取订单
                $checkRunning = $logModel->checkRunning ( $accountId, $eventName );
                if (! $checkRunning ) {
                    $logModel->setFailure ( $logID, Yii::t ( 'system', 'There Exists An Active Event' ) );
                    throw new Exception('There Exists An Active Event', 2);
                }

                // 2.插入本次log参数日志(用来记录请求的参数)
                $eventLog = $logModel->saveEventLog ( $eventName, array (
                    'log_id'     => $logID,
                    'account_id' => $accountId,
                    'start_time' => date('Y-m-d H:i:s',strtotime($timeArr['start_time']) ),
                    'end_time'   => date('Y-m-d H:i:s',strtotime($timeArr['end_time']) ), 
                ) );

                // 3.设置日志为正在运行
                $logModel->setRunning ( $logID );

                // 4. 设置日志编号并开始抓订单id
                $isOk = $model->setLogID($logID)
                                ->setFromCode('getorders')
                                ->startGetOrders();

                //更新日志信息
                $flag = $isOk ? 'Success' : 'Failure';
                if ( $isOk ) {
                    $logModel->setSuccess ( $logID, 'Done' );
                    $logModel->saveEventStatus ( $eventName, $eventLog, LazadaLog::STATUS_SUCCESS);
                } else {
                    $errMessage = $model->getExceptionMessage();
                    if (mb_strlen($errMessage)>200) {
                        $errMessage = mb_substr($errMessage,0,200);
                    }
                    $logModel->setFailure ( $logID, $errMessage );
                    $logModel->saveEventStatus ( $eventName, $eventLog, LazadaLog::STATUS_FAILURE);
                }
                //记录日志 
                $result = date("Y-m-d H:i:s").'###'. $accountId. '=='.json_encode($_REQUEST).'==='.$flag.'==='.$model->getExceptionMessage();
                echo $result."\r\n<br>";
            } catch (Exception $e) {
                echo $e->getMessage()."<br>";
            }
        }
        Yii::app()->end('finish');
    }

    /**
     * @desc 补拉订单
     * @author yangsh
     * @since 2016-10-12
     * @link /lazada/lazadaorder/checkgetorders/account_id/9
     *      /lazada/lazadaorder/checkgetorders/day/3/mode/2
     */
    public function actionCheckgetorders() {
        set_time_limit(3600);
        ini_set ( 'display_errors', true );
        error_reporting( E_ALL & ~E_STRICT);
        
        $accountId   = trim(Yii::app ()->request->getParam ( 'account_id', ''));//1,2
        $day         = trim(Yii::app ()->request->getParam ( 'day', 3 )); //默认补拉3天
        $startTime   = trim(Yii::app ()->request->getParam ( 'start_time', ''));//2016-06-03 14:30
        $endTime     = trim(Yii::app ()->request->getParam ( 'end_time', ''));//2016-06-06 
        $mode        = trim(Yii::app ()->request->getParam ( 'mode', 1));//1:CreatedAfter 2:UpdatedAfter
        $orderStatus = trim(Yii::app ()->request->getParam ( 'order_status', ''));
        $eventName = LazadaLog::EVENT_CHECK_GETORDER;//补拉单事件

        //参数验证
        $validateMsg = ''; 
        if (!empty($day) && !is_numeric($day)) {
            $validateMsg .= 'day is invalid;';
        }           
        if ($startTime != '' && $endTime != '') {
            $pattern = '/^([0-9]{4}-(0?[0-9]|1[0-2])-(0?[0-9]|[1-2][0-9]|3[0-1]))(\s(((0?[0-9])|(1[0-9])|(2[0-3])):([0-5]?[0-9])((\s)|(:([0-5]?[0-9])))?))?$/';
            if ( !preg_match($pattern,$startTime) ) {
                $validateMsg .= 'start_time is invalid;';
            }
            if ( !preg_match($pattern,$endTime) ) {
                $validateMsg .= 'end_time is invalid;';
            }       
        }
        if ($day == 0 && ($startTime == '' || $endTime == '') ) {
            $validateMsg .= 'day and time are empty;';
        }
        if ($validateMsg != '') {
            Yii::app ()->end($validateMsg);
        }

        $accountArr = array();
        if ($accountId != '') {
            $accountArr = explode(',', $accountId);
        } else {
            if (!empty(LazadaAccount::$NEW_API_ACCOUNT_IDS)) {
                $accountArr = LazadaAccount::$NEW_API_ACCOUNT_IDS;
            } else {
                $accountList = LazadaAccount::model()->getAbleAccountList();
                foreach ($accountList as $accountInfo) {
                    $accountArr[] = $accountInfo['id'];
                }  
            }
        }
        if (empty($accountArr)) {
            die('accountArr is empty');
        }        
        //循环每个账号发送一个拉listing的请求
        foreach ($accountArr as $accountId) {
            try {
                //开始抓取order_id
                $model = new LazadaOrderDownloadNew();
                $model ->setAccountID($accountId);
                $model ->setMode($mode);
                if ( $orderStatus ) {
                    $model->setOrderStatus($orderStatus);
                }

                //get timeArr
                $timeArr = array();
                if ($startTime != '' && $endTime != '') {
                    $timeArr ['start_time'] = date ( 'Y-m-d\TH:i:s', strtotime ( $startTime ) );
                    $timeArr ['end_time'] = date ( 'Y-m-d\TH:i:s', strtotime ( $endTime ) );
                    $fromCode = 'check_time_'.ceil((strtotime( $endTime ) - strtotime( $startTime ))/3600);
                } else {
                    $timeArr ['start_time'] = date ( 'Y-m-d\TH:i:s', time () - $day * 86400 );
                    $timeArr ['end_time'] = date ( 'Y-m-d\TH:i:s' );
                    $fromCode = 'check_day_'.$day;
                }
                $timeArr = $model->setTimeArr($timeArr)->getTimeArr();
                MHelper::printvar($timeArr,false);

                //prepareLog
                $logModel = new LazadaLog ();
                $logID = $logModel->prepareLog ( $accountId, $eventName );
                if (!$logID) {
                    throw new Exception('Insert prepareLog failure', 1);
                }
                
                // 1.检查账号是否可拉取订单
                $checkRunning = $logModel->checkRunning ( $accountId, $eventName );
                if (! $checkRunning ) {
                    $logModel->setFailure ( $logID, Yii::t ( 'system', 'There Exists An Active Event' ) );
                    throw new Exception('There Exists An Active Event ', 2);
                }

                // 3.设置日志为正在运行
                $logModel->setRunning ( $logID );

                // 4. 设置日志编号并开始抓订单id
                $isOk = $model ->setLogID($logID)
                                ->setFromCode($fromCode)
                                ->startGetOrders();

                //更新日志信息
                $flag = $isOk ? 'Success' : 'Failure';
                if ( $isOk ) {
                    $logModel->setSuccess ( $logID, 'Done' );
                } else {
                    $errMessage = $model->getExceptionMessage();
                    if (mb_strlen($errMessage)>200) {
                        $errMessage = mb_substr($errMessage,0,200);
                    }                    
                    $logModel->setFailure ( $logID, $errMessage );
                }
                
                //记录日志 
                $result = date("Y-m-d H:i:s").'###'. $accountId. '==' .json_encode($_REQUEST).'==='.$flag.'==='.$model->getExceptionMessage();
                echo $result."\r\n<br>";
            } catch (Exception $e) {
                echo $e->getMessage()."<br>";
            }
        }
        Yii::app ()->end('finish');
    }

    /**
     * @desc 拉取单个订单
     * @link /lazada/lazadaorder/getorderinfo/account_id/9/order_id/1858394
     */
    public function actionGetorderinfo() {
        ini_set ( 'display_errors', true );
        error_reporting( E_ALL & ~E_STRICT);

        $accountID = trim(Yii::app()->request->getParam('account_id', ''));
        $orderId = trim(Yii::app()->request->getParam('order_id', ''));
        if ($orderId == '' || $accountID == '') {
            echo 'order_id or account_id is empty';
            exit;
        }
        $request = new GetOrderRequest();
        $request->setOrderId($orderId);
        $response = $request->setApiAccount($accountID)->setRequest()->sendRequest()->getResponse();
        MHelper::printvar($response,false);
        if (!$request->getIfSuccess()) {
            echo 'ErrorMsg: '.$request->getErrorMsg()."<br>";
        }
        if (empty($response->Body->Orders->Order)) {
            die('response is empty');
        }
        //保存到中间表
        $model = new LazadaOrderDownloadNew();
        $model->setAccountID($accountID);
        $model->setFromCode('getorderinfo');
        $isOk = $model->saveOrderInfo($response->Body->Orders->Order);
        if (!$isOk) {
            echo 'ErrorMsg: '.$model->getExceptionMessage()."<br>";
        }
        die('ok');
    }

    /**
     * @desc 批量更新cod订单
     * @link /lazada/lazadaorder/updatecodorders/account_id/9
     */
    public function actionUpdatecodorders() {
        set_time_limit(7200);
        ini_set ( 'display_errors', true );
        error_reporting( E_ALL & ~E_STRICT);

        $accountID = trim(Yii::app()->request->getParam('account_id', ''));
        $condition = $accountID== '' ? '' : " and seller_account_id='{$accountID}' ";

        $createAtBefore = date('Y-m-d', strtotime("-60 days")); 
        $endTime = date('Y-m-d', strtotime("-5 days"));
        $updateTime = date('Y-m-d H:i',strtotime("-6 hours"));
        $where = " payment_method='CashOnDelivery' and order_status not in('delivered','failed','returned','canceled') and created_at>'{$createAtBefore}' and created_at<'{$endTime}' and updated_time<'{$updateTime}' {$condition} ";
        echo $where."<br>";
        $res = LazadaOrderMain::model()->getListByCondition('seller_account_id,order_id',$where,'','10000');
        if(empty($res)) {
            die('没有可抓取的订单数据');
        }
        foreach ($res as $v) {
            $acountOrderIds[$v['seller_account_id']][] = $v['order_id'];
        }
        foreach ($acountOrderIds as $accountID => $orderIdArr) {
            foreach ($orderIdArr as $orderId) {
                $request = new GetOrderRequest();
                $request->setOrderId($orderId);
                $response = $request->setApiAccount($accountID)->setRequest()->sendRequest()->getResponse();
                //MHelper::printvar($response,false);
                if (!$request->getIfSuccess()) {
                    echo 'ErrorMsg: '.$request->getErrorMsg()."<br>";
                }
                // E429:Too many requests
                if(empty($response->Body->Orders->Order) && trim($response->Head->ErrorCode) == '429' ) {
                    sleep(3);
                    $request = new GetOrderRequest();
                    $request->setOrderId($orderId);
                    $response = $request->setApiAccount($accountID)->setRequest()->sendRequest()->getResponse();
                }

                if (!empty($response->Body->Orders->Order)) {
                    //保存到中间表
                    $model = new LazadaOrderDownloadNew();
                    $model->setAccountID($accountID);
                    $model->setFromCode('getorderinfo');
                    $isOk = $model->saveOrderInfo($response->Body->Orders->Order);
                    if (!$isOk) {
                        echo 'ErrorMsg: '.$model->getExceptionMessage()."<br>";
                    }
                } else {
                    echo $orderId.' response is empty<br>';
                }
            }
        }
        die('ok');
    }   

    /**
     * @desc 批量拉取订单明细
     * @author yangsh
     * @since 2016-10-12
     * @link /lazada/lazadaorder/getmultipleorderitems/account_id/9/orderids/1858394/debug/1
     *       /lazada/lazadaorder/getmultipleorderitems/account_id/9
     */
    public function actionGetmultipleorderitems() {
        set_time_limit(3600);
        ini_set ( 'display_errors', true );
        error_reporting( E_ALL & ~E_STRICT);
        
        $accountId = trim(Yii::app ()->request->getParam ( 'account_id', ''));
        $orderIds  = trim(Yii::app ()->request->getParam ( 'orderids', ''));
        $eventName = LazadaLog::EVENT_ORDERITEMS;//拉取订单明细事件

        if ($accountId != '' && $orderIds != '') {
            $path  = 'lazada/getmultipleorderitems/'.date("Ymd").'/'.$accountId;
            $model = new LazadaOrderDownloadNew();
            $isOk = $model ->setAccountID($accountId)
                           ->setOrderIdList($orderIds)
                           ->startGetMultipleOrderItems();
            $flag = $isOk ? 'Success' : 'Failure';
            $result = date("Y-m-d H:i:s").'###'.json_encode($_REQUEST).'==='.$flag.'==='.$model->getExceptionMessage();
            echo $result."\r\n<br>";
            //MHelper::writefilelog($path.'/result_'.$flag.'.log', $result."\r\n"); 
        } else {
            $model = new LazadaOrderDownloadNew();
            if ($accountId != '') {
                $model->setAccountID($accountId);
            }
            if ($orderIds != '') {
                $model->setOrderIdList($orderIds);
            }
            $newApiAccounts = LazadaAccount::$NEW_API_ACCOUNT_IDS;
            if ($newApiAccounts) {
                $condition = " and seller_account_id in(".implode(',', $newApiAccounts).") ";
            } else {
                $condition = '';
            }            
            $dataList = $model->getNeedDownDetailOrders($condition);
            if (empty($dataList)) {
                Yii::app ()->end('没有可抓取的订单数据');
            }
            $total = 0;
            $accountIDs = array_keys($dataList);
            $accountIdGroupData = MHelper::getGroupData($accountIDs,3);
            foreach ($accountIdGroupData as $accountIdGroup) {
                foreach ($accountIdGroup as $account_id) {
                    $sucCount = 0;
                    $orderIdGroupData = MHelper::getGroupData($dataList[$account_id],100);
                    foreach ($orderIdGroupData as $orderIdArr) {
                        $total  += count($orderIdArr);
                        $orderIds = implode(",", $orderIdArr);
                        $model = new LazadaOrderDownloadNew();
                        $isOk = $model ->setAccountID($account_id)
                                       ->setOrderIdList($orderIds)
                                       ->startGetMultipleOrderItems();
                        if($isOk) {
                            $sucCount++;
                        }                                       
                        $flag = $isOk ? 'Success' : 'Failure';                 
                    }
                    //add log
                    $logModel = new LazadaLog();  
                    $logModel->getDbConnection()->createCommand()->insert(
                        $logModel->tableName(), array(
                            'account_id'    => $account_id,
                            'event'         => $eventName,
                            'start_time'    => date('Y-m-d H:i:s'),                         
                            'status'        => LazadaLog::STATUS_SUCCESS,
                            'message'       => 'Total:'.$sucCount,
                            'response_time' => date('Y-m-d H:i:s'),
                            'end_time'      => date('Y-m-d H:i:s'),
                            'create_user_id'=> intval(Yii::app()->user->id),
                        )
                    );
                    $result = date("Y-m-d H:i:s").'###'. $account_id. '=='.json_encode($_REQUEST).'==='.$sucCount.'==='.$model->getExceptionMessage();
                    echo $result."\r\n<br>";   
                }
            }          
            echo $total. " OrderIds sent!<br>\r\n";
        }
        Yii::app ()->end('finish');
    }

    /**
     * @desc 同步订单到OMS系统
     * @author yangsh
     * @since 2016-10-12
     * @link /lazada/lazadaorder/syncorder/show_result/1
     *       /lazada/lazadaorder/syncorder/show_result/1/platform_order_id/vn-c-383313166-8456624
     */
    public function actionSyncorder() {
        set_time_limit(1800);
        ini_set("display_errors", true);
        error_reporting( E_ALL & ~E_STRICT);

        $platformOrderID  = trim(Yii::app ()->request->getParam ( 'platform_order_id', ''));
        $showResult       = trim(Yii::app ()->request->getParam ( 'show_result', 0));

        //订单同步
        $orderCount = 0;
        $logModel = new LazadaLog();
        $eventName = LazadaLog::EVENT_SYNCORDER;
        $virAccountID = 90000;//虚拟账号id
        $logID = $logModel->prepareLog($virAccountID,$eventName);
        if (!$logID) {
            exit("Create LogID Failure!!!");
        }
        $checkRunning = $logModel->checkRunning($virAccountID, $eventName);
        if (!$checkRunning) {
            echo 'Exists An Active Event.<br>';
            $logModel->setFailure($logID, Yii::t('systems', 'Exists An Active Event'));
        }else {
            //设置日志为正在运行
            $logModel->setRunning($logID);
            //开始同步
            $count = LazadaOrderMain::model()->syncOrderToOms($platformOrderID,$showResult);
            $orderCount += $count;
            //标识事件成功
            $logModel->setSuccess($logID,'Total:'.$orderCount);
        }
        echo 'orderCount:'.$orderCount."<br>";
        Yii::app()->end('finish');
    }

    /**
     * @desc 
     * @author yangsh
     * @since 2016-12-01
     * @link /lazada/lazadaorder/updateomstrans
     *       /lazada/lazadaorder/updateomstrans/platform_order_id/vn-c-383313166-8456624
     */
    public function actionUpdateomstrans() {
        set_time_limit(1800);
        ini_set("display_errors", true);
        error_reporting( E_ALL & ~E_STRICT);

        $platformOrderID  = trim(Yii::app ()->request->getParam ( 'platform_order_id', ''));
        $limit  = trim(Yii::app ()->request->getParam ( 'limit', '500'));

        //订单同步
        $orderCount = 0;
        $logModel = new LazadaLog();
        $eventName = LazadaLog::EVENT_UPDATE_TRANS_STATUS;
        $virAccountID = 90000;//虚拟账号id
        $logID = $logModel->prepareLog($virAccountID,$eventName);
        if (!$logID) {
            exit("Create LogID Failure!!!");
        }
        $checkRunning = $logModel->checkRunning($virAccountID, $eventName);
        if (!$checkRunning) {
            echo 'Exists An Active Event.<br>';
            $logModel->setFailure($logID, Yii::t('systems', 'Exists An Active Event'));
        }else {
            //设置日志为正在运行
            $logModel->setRunning($logID);
            //开始同步
            $count = LazadaOrderMain::model()->updateCODTransactionStatus($limit,$platformOrderID);
            $orderCount += $count;
            //标识事件成功
            $logModel->setSuccess($logID,'Total:'.$orderCount);
        }
        echo 'orderCount:'.$orderCount."<br>";
        Yii::app()->end('finish');
    }

    public function actionList(){
        $request = http_build_query($_POST);
        $this->render("list", array("model"=>$this->_model, 'request'=>$request));
    }

    /**
     * @desc 导出货到付款订单明细的数据
     */
    public function actionCashondeliveryorderxlsajax(){
        set_time_limit(3600);
        ini_set('display_errors', true);
        ini_set('memory_limit', '2048M');

        $where = "payment_method='CashOnDelivery'";
        $bool = 1;

        $getParams = Yii::app()->request->getParam('updated_at');
        if($getParams){
            if($getParams[0]){
                $where .= " and updated_at >= '".$getParams[0]."'";
            }

            if($getParams[1]){
                $where .= " and updated_at < '".$getParams[1]."'";
            }
        }

        //从数据库中取出数据
        $fileds = 'id,platform_order_id,price,currency,created_at,order_status,updated_at';
        $datas = $this->_model->getListByCondition($fileds,$where);
        if(!$datas){
            $bool = 0;
        }

        $this->render("ajax", array('bool'=>$bool));
    }


    /**
     * @desc 导出货到付款订单明细的数据
     */
    public function actionCashondeliveryorderxls(){
        set_time_limit(3600);
        ini_set('display_errors', true);
        ini_set('memory_limit', '2048M');

        $where = "payment_method='CashOnDelivery'";

        $getParams = Yii::app()->request->getParam('updated_at');
        if($getParams){
            if($getParams[0]){
                $where .= " and updated_at >= '".$getParams[0]."'";
            }

            if($getParams[1]){
                $where .= " and updated_at < '".$getParams[1]."'";
            }
        }else{
            $where .= " AND updated_at >= '".date('Y-m-01 00:00:00', strtotime('-1 month'))."' AND updated_at < '".date('Y-m-d 00:00:00')."'";
        }

        //从数据库中取出数据
        $fileds = 'id,platform_order_id,price,currency,created_at,order_status,updated_at';
        $datas = $this->_model->getListByCondition($fileds,$where,'id desc');
        if(!$datas){
            throw new Exception("无数据");
        }

        $str = "平台订单号,CO订单号,交易金额,交易币种,交易时间,订单状态,更新时间\n";
        $fileds = 'order_id';
        foreach ($datas as $key => $value) {
            $platformOrderID = $value['platform_order_id'];
            $localNum = strrpos($platformOrderID,'-');
            if($localNum){
                $platformOrderID = substr($platformOrderID, 0, $localNum);
            }
            $wheres = "platform_code = '".Platform::CODE_LAZADA."' AND platform_order_id LIKE '".$platformOrderID."-%'";
            $orders = Order::model()->getOneByCondition($fileds,$wheres);
            $coOrderID = isset($orders['order_id'])?$orders['order_id']:'';
            $str .= trim($value['platform_order_id']).",".$coOrderID.",".$value['price'].",".$value['currency'].",".$value['created_at'].",".$value['order_status'].",".$value['updated_at']."\n";
        }

        //导出文档名称
        $exportName = 'lazada_货到付款订单明细_导出表'.date('Y-m-dHis').'.csv';

        $this->export_csv($exportName,$str);
        exit;
    }


    /**
     * @desc 导出海外仓订单明细的数据
     */
    public function actionOverseaswarehouseorderxls(){
        set_time_limit(3600);
        ini_set('display_errors', true);
        ini_set('memory_limit', '2048M');

        $where = "o.platform_code = 'lazada' AND d.warehouse_id <> 41";
        $getParams = Yii::app()->request->getParam('ori_update_time');
        if($getParams){
            if($getParams[0]){
                $where .= " AND o.ori_update_time >= '".$getParams[0]."'";
            }

            if($getParams[1]){
                $where .= " AND o.ori_update_time < '".$getParams[1]."'";
            }
        }
        // else{
        //     $where .= " AND o.ori_update_time >= '".date('Y-m-01 00:00:00', strtotime('-1 month'))."' AND o.ori_update_time < '".date('Y-m-d 00:00:00')."'";
        // }
        
        $warehouseID = Yii::app()->request->getParam('warehouse_id');
        if($warehouseID){
            $where .= " AND d.warehouse_id = ".$warehouseID;
        }

        //从数据库中取出数据
        $orderModel = new Order();
        $datas = $orderModel->getDbConnection()->createCommand()
            ->from($orderModel->tableName() . ' as o')
            ->leftJoin(OrderDetail::model()->tableName() . " as d", "d.order_id = o.order_id")
            ->leftJoin("ueb_warehouse." . Warehouse::model()->tableName() . " as w", "w.id = d.warehouse_id")
            ->select("o.order_id,o.platform_order_id,d.sku,d.quantity,o.ori_create_time,w.warehouse_name,o.total_price,d.sale_price,o.currency")
            ->where($where)
            ->queryAll();
        if(!$datas){
            throw new Exception("无数据");
        }

        $str = "CO订单号,平台订单号,币种,订单金额,下单时间,SKU,SKU单价,数量,发货仓,订单状态\n";
        foreach ($datas as $key => $value) {
            $orderMainInfo = LazadaOrderMain::model()->getOneByCondition('order_status', "platform_order_id = '{$value['platform_order_id']}'");
            $orderStatus = isset($orderMainInfo['order_status'])?$orderMainInfo['order_status']:'';
            $str .= $value['order_id'].",".$value['platform_order_id'].",".$value['currency'].",".$value['total_price'].",\t".$value['ori_create_time'].",\t".$value['sku'].",".$value['sale_price'].",".$value['quantity'].",".$value['warehouse_name'].",".$orderStatus."\n";
        }

        //导出文档名称
        $exportName = 'lazada_海外仓订单明细_导出表'.date('Y-m-dHis').'.csv';

        $this->export_csv($exportName,$str);
        exit;
    }


    /**
     * @desc 海外仓订单明细列表
     */
    public function actionOverseaswarehouseorderlist(){
        set_time_limit(300);
        ini_set("display_errors", true);
        error_reporting(E_ALL);
        $orderModel = new OverseasOrder();
        $request = http_build_query($_POST);
        $this->render("overseaslist", array("model"=>$orderModel, 'request'=>$request));
    }
}