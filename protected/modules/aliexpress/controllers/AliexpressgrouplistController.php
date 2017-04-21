<?php
/**
 * @desc Aliexpress产品分组相关
 * @since 2015-09-08
 */
class AliexpressgrouplistController extends UebController{
    
    /**
     * @desc 访问过滤配置
     * @see CController::accessRules()
     */
    public function accessRules() {
        return array(
			array(
				'allow',
				'users' => array('*'),
				'actions' => array()
			),
		);
    }
    
    /**
     * 展示帐号分组信息
     */
    public function actionIndex() {
    	$this->render('index', array('index' => AliexpressGroupList::model()->getAccountList(),));
    }
    
    /**
     * 展示产品分组信息
     */
    public function actionList() {
    	$accountId = Yii::app()->request->getParam('accountId');
    	$this->render('list', array('accountId'	=> $accountId));
    }
    
    /**
     * 从Aliexpress手动拉取所有帐号产品分组信息
     */
//     public function actionGetallgroup() {
//     	$model	= new AliexpressGroupList();
//     	$accountIds	= $model::getAccountList();
//     	foreach ($accountIds as $key => $val) {
//     		$model->setAccountId($val['id']);
//     		$model->getGroupList();
//     	}
//     }
    
    /**
     * @desc 获取分组信息
     */
    public function actionGetgrouplist(){
    	set_time_limit(3600);
        $accountId = Yii::app()->request->getParam('account_id');
        if( $accountId ){//根据账号抓取产品分组信息
        	$aliexpressLog = new AliexpressLog();
        	$logId = $aliexpressLog->prepareLog($accountId,AliexpressGroupList::EVENT_NAME);
        	if($logId){
        		//1.检查账号是否可拉取产品分组信息
        		$checkRunning = $aliexpressLog->checkRunning($accountId, AliexpressGroupList::EVENT_NAME);
        		if(!$checkRunning){
        			$aliexpressLog->setFailure($logId, Yii::t('systems', 'There Exists An Active Event'));
        		}else{
        			//2.拉取产品分组信息
        			$aliexpressGroupListModel = new AliexpressGroupList();
        			$aliexpressGroupListModel->setAccountId($accountId);//设置账号
        			$flag = $aliexpressGroupListModel->getGroupList();//拉取产品分组信息
        		}
        		if ($flag) {
        			$aliexpressLog->setSuccess($logId);
        			$jsonData = array(
        					'message' => Yii::t('aliexpress', 'Synchronization Successful'),
        					'forward' => '/aliexpress/aliexpressgrouplist/index',
        					'navTabId' => 'page'.AliexpressGroupList::getIndexNavTabId(),
        					'callbackType' => 'closeCurrent'
        			);
        			echo $this->successJson($jsonData);
        			Yii::app()->end();
        		}
        	}else {
        		$flag = false;
        	}
        	if (!$flag) {
        		$aliexpressLog->setFailure($logId, Yii::t('systems', $aliexpressGroupListModel->getExceptionMessage()));
        		echo $this->failureJson(array('message' => Yii::t('aliexpress', 'Synchronization Failed!')));
        	}
        }else{//循环可用账号，多线程抓取
            $aliexpressAccounts = AliexpressAccount::model()->getAbleAccountList();
            foreach($aliexpressAccounts as $account){
                MHelper::runThreadSOCKET('/'.$this->route.'/account_id/'.$account['id']);
                sleep(1);
            }
        }
    }
    
}