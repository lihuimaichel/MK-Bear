<?php
/**
 * @desc Lazada请求获取
 * @author Gordon
 * @since 2015-08-13
 */
class LazadafeedController extends UebController{
    
    /**
     * @desc 访问过滤配置
     * @see CController::accessRules()
     */
    public function accessRules() {
        return array(
			array(
				'allow',
				'users' => array('*'),
				'actions' => array('getfeeds')
			),
		);
    }
    
    /**
     * @desc 下载完成的报告
     */
    public function actionGetfinishedfeeds(){
        set_time_limit(3600);
        LazadaFeed::model()->getFinishedFeeds();
    }
    
    /**
     * @desc 获取请求
     * @author Gordon
     */
    public function actionGetfeeds(){
        set_time_limit(3600);
        $accountID = Yii::app()->request->getParam('account_id');
        //$siteList = LazadaAccount::model()->getSiteList($accountID);
        if( $accountID ){//根据账号抓取报告信息
        	$accountInfo = LazadaAccount::getAccountInfoById($accountID);
        	if (empty($accountInfo)) return;
        	$siteID = $accountInfo['site_id'];
        	$apiAccountID = $accountInfo['account_id'];
        	//循环每个站点
        	//foreach ($siteList as $siteID) {
        		$lazadaLog = new LazadaLog();
	            $logID = $lazadaLog->prepareLog($accountID,LazadaFeed::EVENT_NAME);
	            if( $logID ){
	                //1.检查账号是否可拉取报告
	                $checkRunning = $lazadaLog->checkRunning($accountID, LazadaFeed::EVENT_NAME);
	                if( !$checkRunning ){
	                    LazadaLog::model()->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
	                }else{
	                    //2.准备拉取日志信息
	                    $timeArr = LazadaFeed::model()->getTimeArr($accountID);
	                    //插入本次log参数日志(用来记录请求的参数)
	                    $eventLog = $lazadaLog->saveEventLog(LazadaFeed::EVENT_NAME, array(
	                            'log_id'        => $logID,
	                            'account_id'    => $accountID,
	                            'start_time'    => $timeArr['start_time'],
	                            'request_status'=> LazadaFeed::STATUS_FINISHED,
	                    ));
	                    //设置日志为正在运行
	                    $lazadaLog->setRunning($logID);
	                    //3.拉取报告
	                    $lazadaFeedModel = new LazadaFeed;
	                    $lazadaFeedModel->setAccountID($apiAccountID);//设置账号
	                    $lazadaFeedModel->setSiteID($siteID);
	                    $lazadaFeedModel->setLogID($logID);//设置日志编号
	                    $flag = $lazadaFeedModel->getFeeds($timeArr);//拉取
	                    //4.更新日志信息
	                    if( $flag ){
	                        $lazadaLog->setSuccess($logID);
	                        $lazadaLog->saveEventStatus(LazadaFeed::EVENT_NAME, $eventLog, LazadaLog::STATUS_SUCCESS);
	                    }else{
	                        $lazadaLog->setFailure($logID, $lazadaFeedModel->getExceptionMessage());
	                        $lazadaLog->saveEventStatus(LazadaFeed::EVENT_NAME, $eventLog, LazadaLog::STATUS_FAILURE);
	                    }
	                }
	            }
        	//}
        }else{//循环可用账号，多线程抓取
            $lazadaAccounts = LazadaAccount::model()->getAbleAccountList();
            foreach($lazadaAccounts as $account){
                MHelper::runThreadSOCKET('/'.$this->route.'/account_id/'.$account['id']);
                sleep(1);
            }
        }
    }
    
    //手工测试网址
    public function actionMarkmanual($feed_id = '' ){
        ini_set('display_errors','On');
        error_reporting(E_ALL);
        echo $feed_id .'--';
        echo $_GET['feed_id'];
        if($_GET['feed_id']){
            $feed_id = $_GET['feed_id'];
        }
        if($feed_id == ''){
            return false;
        }
        $feedID = $feed_id;
        //= '210e7bd4-ec0b-48d3-afe4-d322f27c21c9';
        echo ($feedID);
        //$skus[] = $sku;
        $status = LazadaProductAdd::UPLOAD_STATUS_IMGFAIL;
        //LazadaProductAddVariation::model()->markStatusBySkusAndFeed($skus, $feed_id, $status, $message = '');
        LazadaProductAddVariation::model()->markVariationStatusByFeedID($feedID, $status, $message = '');
    }
    
    /**
     * @desc 测试删除不用的零库存记录
     * 
     */
    public function actionTestDeleteZeroRecord(){
        LazadaFeed::model()->dbConnection->createCommand()->delete(LazadaFeed::tableName(), 'action = "ProductUpdate" ');
    }
    
    /**
     * @desc 测试修改feed表数据
     * 
     */
    public function actionTestUpdateRecord(){

        $type = Yii::app()->request->getParam('type');
        if($type == 1){
            //按feed_id修改
            $feed_id = Yii::app()->request->getParam('feed_id');
            $where = 'feed_id = "'.$feed_id.'"';
        } elseif ($type == 2) {
            //按create_time修改
            $begin_time = Yii::app()->request->getParam('begin_time');
            if(Yii::app()->request->getParam('end_time')){
                $end_time = Yii::app()->request->getParam('end_time');
                $where = 'create_time > "'.$begin_time.'" and create_time < "'.$end_time.'"' ;
            } else {
                $where = 'create_time > "'.$begin_time.'"';
            }
            
        }
        
        //LazadaFeed::model()->dbConnection->createCommand()->update(LazadaFeed::tableName(),  array('status' => 'Queued','marked'=>0), 'feed_id = "'.$feed_id.'"');
        LazadaFeed::model()->dbConnection->createCommand()->update(LazadaFeed::tableName(),  array('marked'=>0), $where);
    }
    /**
     * @desc 测试标记feed并更新add、variation表状态
     * 
     */
    public function actionTestMark(){
        LazadaFeed::model()->comleteStatus();
    }
    
    /**
     * @desc 测试删除指定的无效历史记录
     * 
     */
    public function actionTestDeleteByTime(){
        LazadaFeed::model()->dbConnection->createCommand()->delete(LazadaFeed::tableName(), 'create_time < "2016-02-10 09:43:20" ');
    }
    /**
     * @desc 测试将Error状态的feed改为Queued状态，并改为未标记
     * 
     */
    public function actionTestUpdateErrorToQueued(){
        LazadaFeed::model()->dbConnection->createCommand()->update(LazadaFeed::tableName(), array('status' => 'Queued','marked'=>0), 'status = "Error" ');
    }
}