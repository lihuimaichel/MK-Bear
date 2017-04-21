<?php
/**
 * @desc PM订单相关
 * @author LIHY
 * @since 2016-07-01
 */
class PriceministerorderController extends UebController{
    
    /**
     * @desc 访问过滤配置
     * @see CController::accessRules()
     */
    public function accessRules() {
        return array(
			array(
				'allow',
				'users' => array('*'),
				'actions' => array('getorders')
			),
		);
    }
    
    /**
     * @desc 获取订单
     * @author Gordon
     * @since 2015-06-03
     * @link /priceminister/priceministerorder/getorders/account_id/1/type/1/since_time/2017-02-24/bug/1
     */
    public function actionGetorders(){
    	set_time_limit(2*3600);
    	ini_set("display_errors", true);
        error_reporting(E_ALL && ~E_STRICT);
    	 
    	$accountID = Yii::app()->request->getParam('account_id');
    	$timeSince = Yii::app()->request->getParam('since_time');
    	$type = Yii::app()->request->getParam('type'); //type 1为补拉
    	
    	if( $accountID ){//根据账号抓取订单信息
    		$logModel = new PriceministerLog();
    		if($type == 1){
    			$eventName = PriceministerOrder::EVENT_NAME_PULL_ORDER;
    		}else{
    			$eventName = PriceministerOrder::EVENT_NAME;
    		}
    		
    		$logID = $logModel->prepareLog($accountID, $eventName);
    		if( $logID ){
    			//1.检查账号是否可拉取订单
    			$checkRunning = $logModel->checkRunning($accountID, $eventName);
    			if( !$checkRunning ){
    				$logModel->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
    				echo "There Exists An Active Event";
    			}else{
    				//2.准备拉取日志信息
    				if($type == 1){//补拉
    					if(!$timeSince){
    						//$timeSince = PriceministerOrder::model()->getTimeSince($accountID);
    						$timeSince = date('Y-m-d', time()-30*24*3600); //补拉三十天
    					}
    				}else{
    					$timeSince = date('Y-m-d', time()-7*3600);
    				}
    				var_dump($timeSince);
    				//插入本次log参数日志(用来记录请求的参数)
    				$eventLog = $logModel->saveEventLog($eventName, array(
    						'log_id'        => $logID,
    						'account_id'    => $accountID,
    						'since_time'    => $timeSince,
    						'complete_time'	=> date('Y-m-d H:i:s', time())//下次拉取时间可以从当前时间点进行,这是北京时间
    				));
    				//设置日志为正在运行
    				$logModel->setRunning($logID);
    				//3.拉取订单
    				$PriceministerOrderModel = new PriceministerOrder();
    				$PriceministerOrderModel->setAccountID($accountID);//设置账号
    				$PriceministerOrderModel->setLogID($logID);//设置日志编号
    				if($type == 1){
    					$flag = $PriceministerOrderModel->getOrdersByTime($timeSince);//拉单
    				}else{
    					$flag = $PriceministerOrderModel->getNewOrders();//拉单
    				}
    				var_dump($flag);
    				echo $PriceministerOrderModel->getExceptionMessage();
    				//4.更新日志信息
    				if( $flag ){
    					$logModel->setSuccess($logID);
    					$logModel->saveEventStatus($eventName, $eventLog, PriceministerLog::STATUS_SUCCESS);
    				}else{
    					$logModel->setFailure($logID, $PriceministerOrderModel->getExceptionMessage());
    					$logModel->saveEventStatus($eventName, $eventLog, PriceministerLog::STATUS_FAILURE);
    				}
    			}
    		}
    	}else{//循环可用账号，多线程抓取
    		$pmAccounts = PriceministerAccount::model()->getAbleAccountList();
    		foreach($pmAccounts as $account){
    			//echo '/'.$this->route.'/account_id/'.$account['id']."/type/".$type."/since_time/".$timeSince;
    			MHelper::runThreadSOCKET('/'.$this->route.'/account_id/'.$account['id']."/type/".$type."/since_time/".$timeSince);
    			sleep(10);
    		}
    	}
    }

    /**
     * @desc 获取订单 -- fortest
     * @author Gordon
     * @since 2015-06-03
     */
    public function actionGetordertest(){
        set_time_limit(3600);
        error_reporting(E_ALL);
        ini_set("display_errors", true);
        
        $request = new GetNewSalesRequest();
        $response = $request->setAccount(1)->setRequest()->sendRequest()->getResponse();
        if($request->getIfSuccess()){
            $this->print_r($response->response->sales);
        }else{
            echo $request->getErrorMsg();
        }
        
    }    
    
 }