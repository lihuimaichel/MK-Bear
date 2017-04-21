<?php
/**
 * @desc Aliexpress订单相关
 * @author Gordon
 * @since 2015-06-25
 */
class AliexpressorderController extends UebController{
    
    /**
     * @desc 访问过滤配置
     * @see CController::accessRules()
     */
    public function accessRules() {
        return array(
			array(
				'allow',
				'users' => array('*'),
				'actions' => array( 'downloadorders','offsetdownloadorders','downloadone','getoneinfo','syncorder')
			),
		);
    }
    
    /**
     * 下载订单 [导入到中间库]
     * @author	Rex
     * @since	2016-05-26
     * @link	/aliexpress/aliexpressorder/downloadorders/group_id/1
     * 			/aliexpress/aliexpressorder/downloadorders/account_id/149
     */
    public function actionDownloadorders() {
        ini_set ( 'display_errors', TRUE );
        error_reporting( E_ALL & ~E_STRICT );

        $groupID    = Yii::app()->request->getParam('group_id',null);//表分组ID,默认未分组ID
        $accountIDs = trim(Yii::app()->request->getParam('account_id',''));//账号ID，多个用逗号分隔
        if ( !empty($accountIDs) ) {
            set_time_limit(3600);
        } else {
            set_time_limit(1800);
        }

    	if ( !empty($accountIDs) ) {
    		$accountIdArr = explode(",", $accountIDs);
    		foreach ($accountIdArr as $accountID){
    			try{
    				$aliLogModel = new AliexpressLog;
    				$logID = $aliLogModel->prepareLog($accountID,AliexpressOrderDownLoad::EVENT_NAME);
    				if ($logID) {
    					$checkRunning = $aliLogModel->checkRunning($accountID, AliexpressOrderDownLoad::EVENT_NAME);
    					if (!$checkRunning) {
                            echo 'There Exists An Active Event.<br>';
    						$aliLogModel->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
    					}else {
                            $aliexpressOrderModel = new AliexpressOrderDownLoad();
                            $aliexpressOrderModel->setAccountID($accountID);//设置账号
                            $timeArr = $aliexpressOrderModel->setTimeArr()->getTimeArr();

    						//插入本次log参数日志(用来记录请求的参数)
    						$eventLog = $aliLogModel->saveEventLog(AliexpressOrderDownLoad::EVENT_NAME, array(
    								'log_id'        => $logID,
    								'account_id'    => $accountID,
    								'start_time'    => $timeArr['start_time'],//速卖通时间
    								'end_time'      => $timeArr['end_time'],//速卖通时间
    						));
    						
    						//设置日志为正在运行
    						$aliLogModel->setRunning($logID);

    						//开始拉取订单
    						$aliexpressOrderModel->setLogID($logID);
    						$flag = $aliexpressOrderModel->startDownLoadMore($timeArr);//拉单

    						if($flag){
    							$aliLogModel->setSuccess($logID,'Done');
    							$aliLogModel->saveEventStatus(AliexpressOrderDownLoad::EVENT_NAME, $eventLog, AliexpressLog::STATUS_SUCCESS);
    						}else{
                                $errMsg = $aliexpressOrderModel->getExceptionMessage();
                                if (mb_strlen($errMsg)> 1000 ) {
                                    $errMsg = mb_substr($errMsg,0,1000);
                                }
    							$aliLogModel->setFailure($logID, $errMsg);
    							$aliLogModel->saveEventStatus(AliexpressOrderDownLoad::EVENT_NAME, $eventLog, AliexpressLog::STATUS_FAILURE);
    						}
                            echo json_encode($_REQUEST).($flag ? ' Success ' : ' Failure ').$aliexpressOrderModel->getExceptionMessage()."<br>";
    					}
    				}
    			}catch (Exception $e){
    				echo $e->getMessage();
    				continue;
    			}
    		}
    	} else {
            //循环每个账号发送请求
            $mapArr = AliexpressAccount::model()->getMapAccountList($groupID);
            if (!is_null($groupID)) {
                $accountIdList[$groupID] = $mapArr;
            } else {
                $accountIdList = $mapArr;
            }
            foreach ($accountIdList as $group_id => $accountIdArr) {
                sort($accountIdArr,SORT_NUMERIC);
                $accountIdGroupData = MHelper::getGroupData($accountIdArr,5);
                foreach ($accountIdGroupData as $key => $idGroupData) {
                    $accountIds = implode(",", $idGroupData);
                    $url = Yii::app()->request->hostInfo.'/'.$this->route.'/group_id/'.$group_id.'/account_id/'.$accountIds;
                    echo $url." <br>\r\n";
                    MHelper::runThreadBySocket($url);
                    if (count($accountIdGroupData)>1) {
                        sleep(10);
                    }
                }
            }           
    	}
    	Yii::app()->end('finish');
    }

    /**
     * 拉取前一天待发货订单 [导入到中间库]
     * @author  yangsh
     * @since   2016-10-20
     * @link    /aliexpress/aliexpressorder/getwaitorders/group_id/1
     *          /aliexpress/aliexpressorder/getwaitorders/account_id/149
     */
    public function actionGetwaitorders() {
        ini_set ( 'display_errors', TRUE );
        error_reporting( E_ALL & ~E_STRICT );

        $groupID    = Yii::app()->request->getParam('group_id',null);//表分组ID,默认未分组ID
        $accountIDs = Yii::app()->request->getParam('account_id');//账号ID，多个用逗号分隔
        $offtime    = 15 * 3600;//与北京时间相差15小时
        $startTime  = trim(Yii::app()->request->getParam('start_time',''));
        if ($startTime == '') {//抓取前1天处于风控期订单
            $startTime = date('Y-m-d H:i:s', strtotime("-2 days") - $offtime);
        }
        $endTime    = trim(Yii::app()->request->getParam('end_time',''));
        if ($endTime == '') {
            $endTime = date("Y-m-d H:i:s", strtotime("-0.1 day") - $offtime );
        }
        $fromCode   = 'GetWaitOrders';
        echo 'start:'.$startTime.' -- end:'. $endTime."<br>";

        if ( !empty($accountIDs) ) {
            set_time_limit(3600);
        } else {
            set_time_limit(1800);
        }

        if ( !empty($accountIDs) ) {
            $accountIdArr = explode(",", $accountIDs);
            foreach ($accountIdArr as $accountID){
                try{
                    $aliLogModel = new AliexpressLog;
                    $logID = $aliLogModel->prepareLog($accountID,AliexpressOrderDownLoad::EVENT_NAME_WAIT);
                    if ($logID) {
                        $checkRunning = $aliLogModel->checkRunning($accountID, AliexpressOrderDownLoad::EVENT_NAME_WAIT);
                        if (!$checkRunning) {
                            echo 'There Exists An Active Event.<br>';
                            $aliLogModel->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
                        }else {
                            $aliexpressOrderModel = new AliexpressOrderDownLoad();
                            $aliexpressOrderModel->setAccountID($accountID);//设置账号

                            $timeArr['start_time'] = $startTime;
                            $timeArr['end_time'] = $endTime;
                            $aliexpressOrderModel->setTimeArr($timeArr);
                            
                            //设置日志为正在运行
                            $aliLogModel->setRunning($logID);

                            //开始拉取订单
                            $aliexpressOrderModel->setLogID($logID);
                            $aliexpressOrderModel->setFromCode($fromCode);
                            $flag = $aliexpressOrderModel->startOffsetDownLoadMore($timeArr);//拉单

                            if($flag){
                                $aliLogModel->setSuccess($logID,'Done');
                            }else{
                                $errMsg = $aliexpressOrderModel->getExceptionMessage();
                                if (mb_strlen($errMsg)> 1000 ) {
                                    $errMsg = mb_substr($errMsg,0,1000);
                                }                                
                                $aliLogModel->setFailure($logID, $errMsg);
                            }
                            echo json_encode($_REQUEST).($flag ? ' Success ' : ' Failure ').$aliexpressOrderModel->getExceptionMessage()."<br>";
                        }
                    }
                }catch (Exception $e){
                    echo $e->getMessage();
                    continue;
                }
            }
        } else {
            //循环每个账号发送请求
            $mapArr = AliexpressAccount::model()->getMapAccountList($groupID);
            if (!is_null($groupID)) {
                $accountIdList[$groupID] = $mapArr;
            } else {
                $accountIdList = $mapArr;
            }
            foreach ($accountIdList as $group_id => $accountIdArr) {
                sort($accountIdArr,SORT_NUMERIC);
                $accountIdGroupData = MHelper::getGroupData($accountIdArr,5);
                foreach ($accountIdGroupData as $key => $idGroupData) {
                    $accountIds = implode(",", $idGroupData);
                    $url = Yii::app()->request->hostInfo.'/'.$this->route.'/group_id/'.$group_id.'/account_id/'.$accountIds;
                    echo $url." <br>\r\n";
                    MHelper::runThreadBySocket($url);
                    if (count($accountIdGroupData)>1) {
                        sleep(30);
                    }
                }
            }           
        }
        Yii::app()->end('finish');
    }

    /**
     * 补拉单 
     * @author  Rex
     * @since   2016-06-06
     * @link    /aliexpress/aliexpressorder/offsetdownloadorders/days/3/group_id/1
     *          /aliexpress/aliexpressorder/offsetdownloadorders/days/3/account_id/149
     *          /aliexpress/aliexpressorder/offsetdownloadorders/days/3/account_id/149/order_status/FINISH
     */
    public function actionOffsetdownloadorders() {
        ini_set ( 'display_errors', TRUE );
        error_reporting( E_ALL & ~E_STRICT );
        
        $groupID              = Yii::app()->request->getParam('group_id',null);//表分组ID,默认未分组ID
        $accountIDs           = trim(Yii::app()->request->getParam('account_id',''));
        $offtime              = 15*3600;//与北京时间相差15小时
        $days                 = Yii::app()->request->getParam('days',3);#补抓3天订单
        empty($days) && $days = 3;
        $startTime            = date('Y-m-d H:i:s', strtotime("-{$days} day") - $offtime );
        $endTime              = date("Y-m-d H:i:s", time() - $offtime );
        $fromCode             = 'Offsetdown_'.$days;
        $orderStatus          = trim(Yii::app()->request->getParam('order_status', AliexpressOrderDownLoad::ORDER_WAIT_SELLER_SEND_GOODS));//单独拉取FINISH
        if ($orderStatus == '') {
            $orderStatus = AliexpressOrderDownLoad::ORDER_WAIT_SELLER_SEND_GOODS;
        }
        if ( !empty($accountIDs) ) {
            set_time_limit(3600);
        } else {
            set_time_limit(1800);
        }
        //echo 'start:'.$startTime.' -- end:'. $endTime."<br>";
        if ( !empty($accountIDs) ) {
            $accountIdArr = explode(",", $accountIDs);
            foreach ($accountIdArr as $accountID){
                try{
                    $aliLogModel = new AliexpressLog;
                    $logID = $aliLogModel->prepareLog($accountID,AliexpressOrderDownLoad::EVENT_NAME_CHECK);
                    if ($logID) {
                        $checkRunning = $aliLogModel->checkRunning($accountID, AliexpressOrderDownLoad::EVENT_NAME_CHECK);
                        $checkRunning = true;
                        if (!$checkRunning) {
                            echo 'There Exists An Active Event.<br>';
                            $aliLogModel->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
                        }else {
                            $aliexpressOrderModel = new AliexpressOrderDownLoad();
                            $aliexpressOrderModel->setAccountID($accountID);//设置账号

                            $timeArr['start_time'] = $startTime;
                            $timeArr['end_time'] = $endTime;
                            $aliexpressOrderModel->setTimeArr($timeArr);

                            //设置日志为正在运行
                            $aliLogModel->setRunning($logID);
                                
                            //开始拉取订单
                            $aliexpressOrderModel->setLogID($logID);
                            $aliexpressOrderModel->setFromCode($fromCode);
                            $aliexpressOrderModel->setOrderStatus($orderStatus);
                            $flag = $aliexpressOrderModel->startOffsetDownLoadMore($timeArr);//补拉单

                            if($flag){
                                $aliLogModel->setSuccess($logID,'Done');
                            }else{
                                $errMsg = $aliexpressOrderModel->getExceptionMessage();
                                if (mb_strlen($errMsg)> 1000 ) {
                                    $errMsg = mb_substr($errMsg,0,1000);
                                }
                                $aliLogModel->setFailure($logID, $errMsg);
                            }
                            echo json_encode($_REQUEST).($flag ? ' Success ' : ' Failure ').$aliexpressOrderModel->getExceptionMessage()."<br>";
                        }
                    }
                }catch (Exception $e){
                    echo $e->getMessage();
                    continue;
                }
            }
        } else {
            //循环每个账号发送请求
            $mapArr = AliexpressAccount::model()->getMapAccountList($groupID);
            if (!is_null($groupID)) {
                $accountIdList[$groupID] = $mapArr;
            } else {
                $accountIdList = $mapArr;
            }
            foreach ($accountIdList as $group_id => $accountIdArr) {
                sort($accountIdArr,SORT_NUMERIC);
                $accountIdGroupData = MHelper::getGroupData($accountIdArr,5);
                foreach ($accountIdGroupData as $key => $idGroupData) {
                    $accountIds = implode(",", $idGroupData);
                    $url = Yii::app()->request->hostInfo.'/'.$this->route.'/group_id/'.$group_id.'/account_id/'.$accountIds.'/days/'.$days.'/order_status/'.$orderStatus;
                    echo $url." <br>\r\n";
                    MHelper::runThreadBySocket($url);
                    if (count($accountIdGroupData)>1) {
                        sleep(120);
                    }
                }
            }
        }
        Yii::app()->end('finish');
    }       

    /**
     * 下载订单 [单条]
     * @author  Rex
     * @since   2016-05-26
     * @link    /aliexpress/aliexpressorder/downloadone/account_id/150/platform_order_id/79384243533278
     */
    public function actionDownloadone($accountId=null,$platformOrderId=null) {
        ini_set ( 'display_errors', true );
        error_reporting( E_ALL & ~E_STRICT );

        if (is_null($accountId)) {
           $accountId = Yii::app()->request->getParam('account_id'); 
        }
        
        if (is_null($platformOrderId)) {
            $platformOrderId = Yii::app()->request->getParam('platform_order_id');
        }
        
        if (empty($accountId) || empty($platformOrderId)) {
            exit('No account or no order_id!');
        }
        
        $aliexpressOrderModel = new AliexpressOrderDownLoad();
        $aliexpressOrderModel->setAccountID($accountId);//设置账号
        
        $orderStatus = trim(Yii::app()->request->getParam('order_status', ''));//单独拉取FINISH
        if ($orderStatus != '') {
            $aliexpressOrderModel->setOrderStatus($orderStatus);
        }

        $flag = $aliexpressOrderModel->startDownLoadOne($platformOrderId);//拉单
        if (!empty($_REQUEST['debug'])) {
            echo $platformOrderId.' @@@'.$aliexpressOrderModel->getExceptionMessage().'<br/>OK';
            Yii::app()->end('finish');
        }
    }    

    /**
     * 显示单个订单信息  [用于测试]
     * @author	Rex
     * @since	2016-05-26
     * @link	/aliexpress/aliexpressorder/getoneinfo/account_id/150/platform_order_id/79384243533278
     */
    public function actionGetoneinfo() {
    	$accountId = Yii::app()->request->getParam('account_id');
    	$platformOrderId = Yii::app()->request->getParam('platform_order_id');
    	
    	if (empty($accountId) || empty($platformOrderId)) {
    		exit('No account or no order_id!');
    	}
    	
    	$aliexpressOrderModel = new AliexpressOrderDownLoad();
    	$aliexpressOrderModel->setAccountID($accountId);//设置账号

        $orderDetailInfo = $aliexpressOrderModel->startDownLoadOneTest($platformOrderId);
        
        MHelper::printvar($orderDetailInfo,false);
    	Yii::app()->end('finish');
    }

    /**
     * 从中间库同步ALI订单到OMS系统
     * @author yangsh
     * @since 2016-08-26
     * @link /aliexpress/aliexpressorder/syncorder/group_id/1/show_result/1
     *  /aliexpress/aliexpressorder/syncorder/group_id/1/account_id/149/show_result/1/platform_order_id/1
     */
    public function actionSyncorder() {
        set_time_limit(3600);
        ini_set("display_errors", TRUE);
        error_reporting( E_ALL & ~E_STRICT ); 

        $groupID         = Yii::app()->request->getParam('group_id', null);//表分组ID,默认未分组ID
        $accountIDs      = Yii::app()->request->getParam('account_id');//账号ID，多个用逗号分隔
        $platformOrderId = isset($_GET['platform_order_id']) ? trim($_GET['platform_order_id']) : null;
        $showResult      = trim(Yii::app ()->request->getParam ( 'show_result', 0));
        
        if ($platformOrderId != '' && strpos($platformOrderId,',') >0 ) {
            $platformOrderId = explode(',',$platformOrderId);
        }

        $groupIdArr = array();
        $mapArr = AliexpressAccount::model()->getMapAccountList();
        if (!empty($groupID)) {
            $groupIdArr[] = $groupID;
        } else {
            $groupIdArr = array_keys($mapArr);
        }
        foreach ($groupIdArr as $group_id) {
            if ($group_id == 0 || empty($mapArr[$group_id]) ) {
                continue;
            }
            $accountIdArr = $mapArr[$group_id];
            if (!empty($accountIDs)) {
                $tmpAccountArr = explode(',', $accountIDs);
                $accountIdArr = array_intersect($tmpAccountArr,$accountIdArr);
            }
            if (empty($accountIdArr)) {
                continue;
            }
            //订单同步
            $orderCount = 0;
            $logModel = new AliexpressLog;
            $eventName = AliexpressLog::EVENT_SYNCORDER;
            $virAccountID = 90000 + $group_id;//虚拟账号id
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
                $count = AliexpressOrderHeader::model()->syncOrderToOms($accountIdArr,$platformOrderId,$showResult);
                $orderCount += $count;
                //标识事件成功
                $logModel->setSuccess($logID,'Total:'.$orderCount);
            }
        }
        Yii::app()->end('finish');
    }   

    /**
     * @desc 补拉指定订单,平台订单ID，注意已发货的也会拉取下来
     */
    public function actionGetorderfilterids(){
    	$accountID = Yii::app()->request->getParam('account_id');
    	$orderIDs = Yii::app()->request->getParam('order_id');
    	try{
    		if(!$accountID){
    			throw new Exception('指定账号');
    		}
    		if(!$orderIDs){
    			throw new Exception("指定订单iD");
    		}
    		$orderIDs = trim($orderIDs);
    		$orderIDs = explode(",", $orderIDs);
            foreach ($orderIDs as $platformOrderId) {
                $this->actionDownloadone($accountID,$platformOrderId);
            }
    		echo $this->successJson(array(
    				'message'=>'运行完成，请到oms确认',
    		));
    	}catch(Exception $e){
    		echo $this->failureJson(array('message'=>$e->getMessage()));
    	}
    	Yii::app()->end();
    }
    
    /**
     * @desc 手动拉单
     */
    public function actionGetorderdetail(){
    	$this->render("getorder");
    }

    /**
     * @desc 针对速卖通等待买家付款的订单，进行批量发送留言
     * @link /aliexpress/aliexpressorder/placeordersendmsg/accountID/150/ymd/20161123
     */
    public function actionPlaceordersendmsg(){
        set_time_limit(6*3600);
        ini_set("display_errors", true);
        error_reporting(E_ALL);

        $aliOrderSendMsgModel     = new AliexpressNonPaymentOrderSendMessages();
        $aliAddMsgModel           = new AliexpressAddMsg();
        $aliOrderModel            = new AliexpressOrder();

        //账号
        $accountID = Yii::app()->request->getParam('accountID');

        //日期(只能输入年月日)
        $ymd = Yii::app()->request->getParam('ymd');

        //发送的留言内容
        $content = 'Thanks for interest on our products.

        We have this item in stock.
        We appreciated your purchase from us. However, we noticed you that haven’t made the payment yet. This is a friendly reminder to you to complete the payment transaction as soon as possible. Instant payments are very important; the earlier you pay, the sooner you will get the item.

        We sincerely hope the information will assist you.

        Should you have any queries, please feel free to contact us.

        Have a nice day!';

        if(!$ymd){
            $prevDate = date('Ymd',strtotime('-2 day'));
        }else{
            $prevDate = $ymd;
        }

        if($accountID){
            $orderList = $aliOrderModel->getPlaceOrderSuccess($accountID,$prevDate);
            if($orderList){
                $aliOrderSendMsgModel->insertPlaceOrder($orderList);
            }

            //发送留言
            $sendMsgInfo = $aliOrderSendMsgModel->getListByCondition('platform_order_id,buyer_login_id,account_id', 'status = 0 AND account_id = '.$accountID);
            if($sendMsgInfo){
                foreach ($sendMsgInfo as $msgInfo) {
                    $uploadData = array(
                        'channelId'  => $msgInfo['platform_order_id'],
                        'buyerId'    => $msgInfo['buyer_login_id'],
                        'content'    => $content,
                        'msgSources' => 'order_msg'
                    );
                    $aliAddMsgModel->setAccountID($accountID);
                    $result = $aliAddMsgModel->uploadAddMsg($uploadData);
                    if($result){
                        $updateData = array('status' => 1, 'send_msg' => '发送成功');
                    }else{
                        $updateData = array('status' => 2, 'send_msg' => $aliAddMsgModel->getExceptionMessage());
                    }
                    $updateWhere = 'platform_order_id = '.$msgInfo['platform_order_id'];
                    $aliOrderSendMsgModel->updateData($updateData, $updateWhere);
                }
            }

            echo '订单发送留言完成';
        }else{
            $accountList = AliexpressAccount::model()->getIdNamePairs();
            foreach($accountList as $key => $value){
                MHelper::runThreadSOCKET('/'.$this->route.'/accountID/' . $key);
                sleep(5);
            }
        }
    }


    /**
     * 修复订单
     * /aliexpress/aliexpressorder/updateshipcost/platform_order_id/81058402019473
     */
    public function actionUpdateshipcost(){
        set_time_limit(600);
        ini_set("display_errors", true);
        error_reporting(E_ALL);
        $platform_order_id = Yii::app()->request->getParam('platform_order_id');
        $shipCost = Yii::app()->request->getParam('ship_cost');
        $couponPrice = Yii::app()->request->getParam('coupon_price');
        $omsOrder = new Order();
        //更新订单order表运费
        $sql = "UPDATE `ueb_order` SET ship_cost = ".$shipCost." WHERE platform_code = 'ali' AND platform_order_id = '".$platform_order_id."'";
        $Info = $omsOrder->getDbConnection()->createCommand($sql)->execute();
        if(!$Info){
            exit('更新order表失败');
        }

        $orderInfo = $omsOrder->getDbConnection()->createCommand()
                ->select("*")
                ->from($omsOrder->tableName())
                ->where('platform_code = "ALI" AND platform_order_id = "'.$platform_order_id.'"')
                ->queryRow();

        if($orderInfo){
            $sql2 = "UPDATE `ueb_order_detail` SET ship_price = ".$shipCost." WHERE platform_code = 'ali' AND order_id = '".$orderInfo['order_id']."'";
            $Info2 = $omsOrder->getDbConnection()->createCommand($sql2)->execute();
            if(!$Info2){
                exit('更新detail表失败');
            }

            $extendInfo = $omsOrder->getDbConnection()->createCommand()
                ->select("order_id")
                ->from($omsOrder->tableName().'_extend')
                ->where('order_id = "'.$orderInfo['order_id'].'"')
                ->queryRow();

            if(!$extendInfo && $couponPrice > 0){
                $sql3 = "INSERT INTO `ueb_order_extend` VALUES ('".$orderInfo['order_id']."', 'ALI', '".$orderInfo['platform_order_id']."', '".$orderInfo['account_id']."', '0.00', 'USD', '".$couponPrice."', '0', '0', '".$orderInfo['timestamp']."');";
                $Info3 = $omsOrder->getDbConnection()->createCommand($sql3)->execute();
                if(!$Info3){
                    exit('插入extend表失败');
                }
            }
        }

        echo '修改成功';
        
    }
}