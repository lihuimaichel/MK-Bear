<?php
class AliexpressaccountController extends UebController {
	
	/**
	 * @todo Aliexpress帐号管理列表
	 * @author guoll
	 * @since 2015/09/08
	 */
	public function actionList(){
		$model = UebModel::model('AliexpressAccount');
		$this->render('list',array('model'=>$model));
	}
	
	/**
	 * @desc 冻结账号
	 */
	public function actionLockaccount(){
		$models =  new AliexpressAccount();
		$lock = AliexpressAccount::STATUS_ISLOCK;//账号状态为冻结的参数
		if (Yii::app()->request->isAjaxRequest && isset($_REQUEST['ids'])) {
			try {
				foreach (explode(',',$_REQUEST['ids']) as $aliexpressAccount){
					//获取账号信息
					$data = $models->getAccountInfoById($aliexpressAccount);
					if($data['is_lock'] == $lock){
						$jsonData = array(
								'message' => Yii::t('system', 'the chosen account has been haven account locked'),
						);
						echo $this->failureJson($jsonData);
						die;
					}
					//需改账号状态变为冻结状态
					$flag=$models->LockAccount($aliexpressAccount);
				}
			} catch (Exception $e) {
				$flag = false;
			}
		}
		if($flag){
			$jsonData = array(
					'message' =>Yii::t('system', 'Lock Success'),
					'navTabId'=>'page' . AliexpressAccount::getIndexNavTabId(),
			);
			
			echo $this->successJson($jsonData);
		}else{
			echo $this->failureJson(array('message'=> Yii::t('system', 'Lock Failed')));
		}
		Yii::app()->end();
	}
	
	/**
	 * @desc 解冻账号
	 */
	public function actionUnlockaccount(){
		$models =  new AliexpressAccount();
		$unLock = AliexpressAccount::STATUS_NOTLOCK;//账号状态为未冻结的参数
		if (Yii::app()->request->isAjaxRequest && isset($_REQUEST['ids'])) {
			try {
				foreach (explode(',',$_REQUEST['ids']) as $aliexpressAccount){
					//获取账号基本信息
					$data = $models->getAccountInfoById($aliexpressAccount);
					if($data['is_lock'] == $unLock){
						$jsonData = array(
								'message' => Yii::t('system', 'the chosen account has been haven account unlock'),
						);
						echo $this->failureJson($jsonData);
						die;
					}
					//修改账号状态为未冻结
					$flag=$models->unLockAccount($aliexpressAccount);
				}
			} catch (Exception $e) {
				$flag = false;
			}
		}
		if($flag){
			$jsonData = array(
					'message' =>Yii::t('system', 'Unlock Success'),
					'navTabId'=>'page' . AliexpressAccount::getIndexNavTabId(),
			);
			echo $this->successJson($jsonData);
		}else{
			echo $this->failureJson(array('message'=> Yii::t('system', 'Unlock Failed')));
		}
		Yii::app()->end();
	}
	
	/**
	 * @desc 关闭账号
	 */
	public function actionShutdownaccount(){
		$models =  new AliexpressAccount();
		$shutDown = AliexpressAccount::STATUS_SHUTDOWN;//获取账号状态为关闭的参数
		if (Yii::app()->request->isAjaxRequest && isset($_REQUEST['ids'])) {
			try {
				foreach (explode(',',$_REQUEST['ids']) as $aliexpressAccount){
					//获取账号基本信息
					$data = $models->getAccountInfoById($aliexpressAccount);
					if($data['status'] == $shutDown){
						$jsonData = array(
								'message' => Yii::t('system', 'the chosen account has been haven account closed'),
						);
						echo $this->failureJson($jsonData);
						die;
					}
					//修改账号状态为关闭
					$flag=$models->shutDownAccount($aliexpressAccount);
				}
			} catch (Exception $e) {
				$flag = false;
			}
		}
		if($flag){
			$jsonData = array(
					'message' =>Yii::t('system', 'ShutDown Success'),
					'navTabId'=>'page' . AliexpressAccount::getIndexNavTabId(),
			);
			echo $this->successJson($jsonData);
		}else{
			echo $this->failureJson(array('message'=> Yii::t('system', 'ShutDown Failed')));
		}
		Yii::app()->end();
	}
	
	/**
	 * @desc 开启账号
	 */
	public function actionOpenaccount(){
		$models =  new AliexpressAccount();
		$open = AliexpressAccount::STATUS_OPEN;//获取账号状态为开启的参数
		if (Yii::app()->request->isAjaxRequest && isset($_REQUEST['ids'])) {
			try {
				foreach (explode(',',$_REQUEST['ids']) as $aliexpressAccount){
					//获取账号基本信息
					$data = $models->getAccountInfoById($aliexpressAccount);
					if($data['status'] == $open){
						$jsonData = array(
								'message' => Yii::t('order', 'the chosen account has been haven account opened'),
						);
						echo $this->failureJson($jsonData);
						die;
					}
					//修改账号状态为开启
					$flag=$models->openAccount($aliexpressAccount);
				}
			} catch (Exception $e) {
				$flag = false;
			}
		}	
		if($flag){
			$jsonData = array(
					'message' =>Yii::t('system', 'Open Success'),
					'navTabId'=>'page' . AliexpressAccount::getIndexNavTabId(),
			);
			echo $this->successJson($jsonData);
		}else{
			echo $this->failureJson(array('message'=> Yii::t('system', 'Open Failed')));
		}
		Yii::app()->end();

	}
	
	/**
	 * @desc 编辑账号
	 */
	public function actionUpdate(){
	    $option = false;
		$model = $this->loadModel($_REQUEST['id']);
		if (Yii::app()->request->isAjaxRequest && isset($_POST['AliexpressAccount'])) {
			$msg = '';
		    $userId = Yii::app()->user->id;
		    $model->setAttribute('modify_user_id', $userId);
		    $model->setAttribute('modify_time', date('Y-m-d H:i:s'));
		    $model->setAttribute('is_overseas_warehouse', isset($_POST['AliexpressAccount']['is_overseas_warehouse']) ? $_POST['AliexpressAccount']['is_overseas_warehouse'] : 0);
		    if ($model->validate()) {
		        $flag = $model->save();
		        $msg = "海外仓设置修改成功！";
		    } else {
		        $flag = false;
		        $msg = "海外仓设置修改失败！";
		    }
		    $option = true;
			
			if($flag){
				$jsonData = array(
						'message' =>$msg,
						'navTabId'=>'page' . AliexpressAccount::getIndexNavTabId(),						
				);
				if ($option) 
				$jsonData['alertOptions'] = array(
						    'okCall' => '',
						    'cancelCall' => ''
				);
				echo $this->successJson($jsonData);
			}else{
				echo $this->failureJson(array('message' => $msg));
			}
			Yii::app()->end();
		}
		$this->render('update',array('model' => $model));
	}
	
	/**
	 * 激活帐号
	 */
	public function actionActivation(){
		$model= new AliexpressAccount();
		if (Yii::app()->request->isAjaxRequest && isset($_REQUEST['ids'])) {
			if(count(explode(',',$_REQUEST['ids']))>1){
				echo $this->failureJson(array('message' => Yii::t('system', '每次只能激活一个帐号！')));
				Yii::app()->end();
			}
			$flag=$model->accountActivation($_REQUEST['ids']);
			if($flag){
				$jsonData = array(
						'message' =>Yii::t('system', '激活成功'),
				);
				echo $this->successJson($jsonData);
			}else{
				echo $this->failureJson(array('message' => Yii::t('system', '激活失败')));
			}
			Yii::app()->end();
		}
	}
	
	
	/**
	 * 授权帐号第一步 获取临时Code
	 */
	public function actionAuthorize(){
		$model= new AliexpressAccount();
		$data=$model->accountAuthorize($_REQUEST['id']);
		$url=$data->_ali_url;
		echo "<script>";
		echo "location.href='$url'";
		echo "</script>";
	}
	
	/**
	 * 授权帐号第二步 获取refresh_token
	 */
	public function actionRedirecurl(){
		header("Content-type: text/html; charset=utf-8");
		$model= new AliexpressAccount();
		$data=$model->getRefreshToken($_REQUEST);
		if($data){
			echo "<center><div style='font-size:38px;color:red;margin-top:150px;'>授权成功</div></center>";
		}else{
			echo "<center><div style='font-size:38px;color:red;margin-top:150px;'>授权失败</div></center>";
		}
	}
	/**
	 * @desc 获取账号基本信息
	 */
	public function loadModel($id){
		$model = UebModel::model('AliexpressAccount')->findByPk($id);
		if($model===false){
			throw new CHttpException ( 404, Yii::t ( 'app', 'The requested page does not exist.' ) );
		}else{
			return $model;
		}
	}
	
	/**
	 * @link /aliexpress/aliexpressaccount/gettoken
	 */
	public function actionGettoken(){
		exit('关闭此接口');
		// error_reporting(E_ALL);
		// ini_set("display_errors", true);
		$accountID = Yii::app()->request->getParam('account_id');
		if($accountID){
			//if($accountID == 248 or $accountID == 249) continue;
			$request = new GetAccountTokenRequest;
			$response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
			//print_r($response);
			if($request->getIfSuccess()){
				$data = array(
					'access_token' => $response->access_token,
					'modify_time'	=> date("Y-m-d H:i:s")
				);
				//print_r($data);
				AliexpressAccount::model()->getDbConnection()->createCommand()->update(AliexpressAccount::model()->tableName(), $data, "id='{$accountID}'");
			}
			echo $request->getErrorMsg();
		}else{
			$accountList = AliexpressAccount::getAbleAccountList();
			//循环每个账号发送一个拉listing的请求
			foreach ($accountList as $accountInfo) {
				echo $url = Yii::app()->request->hostInfo.'/'.$this->route.'/account_id/'.$accountInfo['id'];
				echo "<br/>";
				MHelper::runThreadBySocket($url);
			}
		}
	}
	
	/**
	 * @link /aliexpress/aliexpressaccount/synctokentooms/account_id/149
	 */
	public function actionSynctokentooms(){
		$accountID = Yii::app()->request->getParam('account_id');

		if($accountID){
			$accountList = array(array('id'=>$accountID));
		}else{
			$accountList = AliexpressAccount::getAbleAccountList();
		}
		
		//循环每个账号发送一个拉listing的请求
		foreach ($accountList as $accountInfo) {
			$accountID = $accountInfo['id'];
			$accountInfo = AliexpressAccount::model()->find("id='{$accountID}'");
			if(empty($accountInfo)) exit("no find");
				
			$data = array(
					'access_token' => $accountInfo['access_token'],
					'short_name'   => $accountInfo['short_name'],
					'modify_time'  => date("Y-m-d H:i:s")
			);
			$checkExists = OmsAliexpressAccount::model()->getDbConnection()->createCommand()
									->from(OmsAliexpressAccount::model()->tableName())
									->select("id")
									->where("account='{$accountInfo['account']}'")
									->queryRow();
			if($checkExists){
				$res = OmsAliexpressAccount::model()->getDbConnection()->createCommand()->update(OmsAliexpressAccount::model()->tableName(), $data, "account='{$accountInfo['account']}'");
			}else{
				//添加
				$data['id'] = $accountID;
				$data['account_key'] = $accountInfo['account_key'];
				$data['short_name'] = $accountInfo['short_name'];
				$data['email'] = $accountInfo['email'];
				$data['resource_owner'] = $accountInfo['resource_owner'];
				$data['refresh_token'] = $accountInfo['refresh_token'];
				$data['status'] = $accountInfo['status'];
				$data['account_group'] = $accountInfo['account_group'];
				$data['account_discount'] = $accountInfo['account_discount'];
				$data['last_update_time'] = date("Y-m-d H:i:s");
				$data['modify_user_id'] = 0;
				$data['app_key'] = $accountInfo['app_key'];
				$data['secret_key'] = $accountInfo['secret_key'];
				$data['account'] = $accountInfo['account'];
				var_dump($data);
				$res = OmsAliexpressAccount::model()->getDbConnection()->createCommand()->insert(OmsAliexpressAccount::model()->tableName(), $data);
			}
			//print_r($data);
			
			echo $accountID;
			var_dump($res);
		}
	}
	
}