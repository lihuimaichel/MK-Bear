<?php
/**
 * @desc Aliexpress param template
 * @author	tony
 * @since	2015-09-14
 */

class AliexpresspromisetemplateController extends UebController {
	
	/**
	 * @desc 获取分类
	 */
	public function actionGetpromisetemplate(){
		set_time_limit(3600);
		error_reporting(E_ALL);
    	ini_set("display_errors", true);
		$accountID = Yii::app()->request->getParam('account_id');
		if ($accountID) {
			$aliexpressLog = new AliexpressLog();
			$logID = $aliexpressLog->prepareLog($accountID,AliexpressPromiseTemplate::EVENT_NAME);
			$promiseTemplateModel = new AliexpressPromiseTemplate();
			$promiseTemplateModel->setAccountID($accountID);
			$templateID = isset($_REQUEST['template_id'])?$_REQUEST['template_id']:-1; //category_id
			$promiseTemplateModel->setTemplateID($templateID);
			$promiseTemplateModel->deleteAll('account_id = :account_id', array('account_id'=>$accountID));
			$flag = $promiseTemplateModel->updatePromiseTemplate();
			if( $flag ){
				$aliexpressLog->setSuccess($logID);
			}else{
				$aliexpressLog->setFailure($logID, $promiseTemplateModel->getExceptionMessage());
			}
		} else {
    		//循环每个账号发送一个拉listing的请求
    		$accountList = AliexpressAccount::getAbleAccountList();
    		foreach($accountList as $account){
    			$promiseTemplateModel->deleteAll('account_id = :account_id', array('account_id'=>$account['id']));
				MHelper::runThreadSOCKET('/'.$this->route.'/account_id/'.$account['id']);
				sleep(1);
			}
    	}
	}


	/**
	 * @desc 根据账号ID更新服务模板
	 */
	public function actionGetpromisetemplatebyaccountid(){
		set_time_limit(3600);
		error_reporting(E_ALL);
    	ini_set("display_errors", true);
		$accountID = Yii::app()->request->getParam('account_id');
		if ($accountID) {
			$aliexpressLog = new AliexpressLog();
			$logID = $aliexpressLog->prepareLog($accountID,AliexpressPromiseTemplate::EVENT_NAME);
			$promiseTemplateModel = new AliexpressPromiseTemplate();
			$promiseTemplateModel->setAccountID($accountID);
			$templateID = isset($_REQUEST['template_id'])?$_REQUEST['template_id']:-1; //category_id
			$promiseTemplateModel->setTemplateID($templateID);
			$promiseTemplateModel->deleteAll('account_id = :account_id', array('account_id'=>$accountID));
			$flag = $promiseTemplateModel->updatePromiseTemplate();
			if( $flag ){
				$promiseData = '<option>请选择</option>';
				$data = $promiseTemplateModel->getTemplateIdInfoByAccountId($accountID);
				if($data){
					foreach ($data as $key => $value) {
						$promiseData .= '<option value="'.$value['template_id'].'">'.$value['name'].'</option>';
					}
				}
				echo $this->successJson(array('message'=>'更新成功', 'data'=>$promiseData));
			}else{
				echo $this->failureJson(array('message'=>'更新失败'));
			}
		} else {
    		echo $this->failureJson(array('message'=>'更新失败，没有账号ID'));
    	}
	}
}