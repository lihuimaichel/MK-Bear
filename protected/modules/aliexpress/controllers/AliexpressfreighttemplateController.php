<?php
/**
 * @desc Aliexpress param template
 * @author	tony
 * @since	2015-09-14
 */

class AliexpressfreighttemplateController extends UebController {

	/**
	 * @desc 获取分类
	 */
	public function actionGetfreighttemplate(){
		set_time_limit(3600);
		$accountID = Yii::app()->request->getParam('account_id');
		if ($accountID) {
			$aliexpressLog = new AliexpressLog();
			$logID = $aliexpressLog->prepareLog($accountID,AliexpressFreightTemplate::EVENT_NAME);
	
			//3.请求模板详情
			$freightTemplateModel = new AliexpressFreightTemplate();
			//3.1 设置AccountID
			$freightTemplateModel->setAccountID($accountID);
			//3.2设置模板ID
			$templateID = isset($_REQUEST['template_id'])?$_REQUEST['template_id']:-1; //category_id
			$freightTemplateModel->setTemplateID($templateID);
			//3.3删除以前的运费模板信息
			AliexpressFreightTemplate::model()->deleteAll('account_id = :account_id', array('account_id'=>$accountID));
			//3.4通过账号和模板ID请求模板详情
			$flag = $freightTemplateModel->updateFreightTemplate();
	
			//4.更新日志信息
			if( $flag ){
				$aliexpressLog->setSuccess($logID);
			}else{
				$aliexpressLog->setFailure($logID, $freightTemplateModel->getExceptionMessage());
			}    	
		} else {
    		//循环每个账号发送一个拉listing的请求
    		$accountList = AliexpressAccount::getAbleAccountList();
    		foreach($accountList as $account){
    			AliexpressFreightTemplate::model()->deleteAll('account_id = :account_id', array('account_id'=>$account['id']));
				MHelper::runThreadSOCKET('/'.$this->route.'/account_id/'.$account['id']);
				sleep(1);
			}
    	}
	}


	/**
	 * @desc 获取分类
	 */
	public function actionGetfreighttemplatebyaccountid(){
		set_time_limit(3600);
		$accountID = Yii::app()->request->getParam('account_id');
		if ($accountID) {
			$aliexpressLog = new AliexpressLog();
			$logID = $aliexpressLog->prepareLog($accountID,AliexpressFreightTemplate::EVENT_NAME);
	
			//3.请求模板详情
			$freightTemplateModel = new AliexpressFreightTemplate();
			//3.1 设置AccountID
			$freightTemplateModel->setAccountID($accountID);
			//3.2设置模板ID
			$templateID = isset($_REQUEST['template_id'])?$_REQUEST['template_id']:-1; //category_id
			$freightTemplateModel->setTemplateID($templateID);
			//3.3删除以前的运费模板信息
			AliexpressFreightTemplate::model()->deleteAll('account_id = :account_id', array('account_id'=>$accountID));
			//3.4通过账号和模板ID请求模板详情
			$flag = $freightTemplateModel->updateFreightTemplate();
	
			//4.更新日志信息
			if( $flag ){
				$freightData = '<option>请选择</option>';
				$data = AliexpressFreightTemplate::model()->getTemplateIdInfoByAccountId($accountID);
				if($data){
					foreach ($data as $key => $value) {
						$freightData .= '<option value="'.$value['template_id'].'">'.$value['template_name'].'</option>';
					}
				}
				echo $this->successJson(array('message'=>'更新成功', 'data'=>$freightData));
			}else{
				echo $this->failureJson(array('message'=>'更新失败'));
			}    	
		} else {
    		echo $this->failureJson(array('message'=>'更新失败，没有账号ID'));
    	}
	}

}