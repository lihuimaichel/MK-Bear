<?php
/**
 * @desc Aliexpress 创建产品分组相关
 * @since 2015-09-08
 */
class AliexpresscreategroupController extends UebController{
    
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
     * 添加分组信息
     */
    public function actionCreate() {
    	$model = new AliexpressCreateGroup();
        if (isset($_GET['parentId'])) {
           $model->parentId  = $_GET['parentId'];
           $model->accountId = $_GET['accountId'];
        }
        if (Yii::app()->request->isAjaxRequest && isset($_POST['AliexpressCreateGroup'])) {
            $model->attributes = $_POST['AliexpressCreateGroup'];
            $model->setAttribute('group_name', AliexpressCreateGroup::filterName($_POST['AliexpressCreateGroup']['group_name']));
            $model->setAttribute('account_id', AliexpressCreateGroup::filterName($_POST['AliexpressCreateGroup']['account_id']));
            $model->setAttribute('parent_id', AliexpressCreateGroup::filterName($_POST['AliexpressCreateGroup']['parent_id']));
            if ($model->validate()) {
                $transaction = Yii::app()->db->beginTransaction();
                try {
                    $groupName = $model->getAttribute('group_name');
                    $accountId = $model->getAttribute('account_id');
                    $parentId  = $model->getAttribute('parent_id');
                    
                    if ($accountId) {//根据账号创建产品分组
                    	$logId = AliexpressLog::model()->prepareLog($accountId,AliexpressCreateGroup::EVENT_NAME);
                    	if($logId){
                    		//1.检查账号是否可创建产品分组
                    		$checkRunning = AliexpressLog::model()->checkRunning($accountId, AliexpressCreateGroup::EVENT_NAME);
                    		if(!$checkRunning){
                    			AliexpressLog::model()->setFailure($logId, Yii::t('systems', 'There Exists An Active Event'));
                    		}else{
                    			//2.创建产品分组
                    			$model->setAccountId($accountId);//设置账号
                    			$model->setParentId($parentId);//设置账号
                    			$model->setGroupName($groupName);//设置账号
                    			$flag = $model->createGroup();//拉取产品分组信息
                    		}
                    	}
                    }else {
                    	$flag = false;
                    }
                    $transaction->commit();
                } catch (Exception $e) {
                    $transaction->rollback();
                    $flag = false;
                }        
                if ($flag) {     
                    $jsonData = array(                    
                        'message' => Yii::t('system', 'Add successful'),
                        'forward' => '/aliexpress/aliexpressgrouplist/index',
                        'navTabId' => 'page'.AliexpressGroupList::getIndexNavTabId(),
                        'callbackType' => 'closeCurrent'
                    );
                    echo $this->successJson($jsonData);
                }
            } else {               
                $flag = false;
            }
            if (!$flag) {
                echo $this->failureJson(array('message' => Yii::t('system', 'Add failure')));
            }
            Yii::app()->end();
        }
        $this->render('create', array('model' => $model));
    }
    
    
}