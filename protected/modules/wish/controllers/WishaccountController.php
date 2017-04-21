<?php
/**
 * @desc wish账号控制器
 * @author liht
 * @since 20151116
 *
 */
class WishaccountController extends UebController {

	/**
	 * @todo Wish帐号管理列表
	 * @since 2015/11/16
	 */
	public function actionList(){
		$model = UebModel::model('WishAccount');
		$this->render('list',array('model'=>$model));
	}
	/**
	 * @desc 冻结账号
	 */
	public function actionLockaccount(){
		$models =  new WishAccount();
		$lock = WishAccount::STATUS_ISLOCK;//账号状态为冻结的参数
		if (Yii::app()->request->isAjaxRequest && isset($_REQUEST['ids'])) {
			try {
				foreach (explode(',',$_REQUEST['ids']) as $wishAccount){
					//获取账号信息
					$data = $models->getAccountInfoById($wishAccount);
					if($data['is_lock'] == $lock){
						$jsonData = array(
							'message' => Yii::t('system', 'the chosen account has been haven account locked'),
						);
						echo $this->failureJson($jsonData);
						die;
					}
					//需改账号状态变为冻结状态
					$flag=$models->LockAccount($wishAccount);
				}
			} catch (Exception $e) {
				$flag = false;
			}
		}
		if($flag){
			$jsonData = array(
				'message' =>Yii::t('system', 'Lock Success'),
				'forward' =>'/wish/wishaccount/list',
				'navTabId'=>'page' . WishAccount::getIndexNavTabId(),
				'callbackType'=>'closeCurrent'
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
		$models =  new WishAccount();
		$unLock = WishAccount::STATUS_NOTLOCK;//账号状态为未冻结的参数
		if (Yii::app()->request->isAjaxRequest && isset($_REQUEST['ids'])) {
			try {
				foreach (explode(',',$_REQUEST['ids']) as $wishAccount){
					//获取账号基本信息
					$data = $models->getAccountInfoById($wishAccount);
					if($data['is_lock'] == $unLock){
						$jsonData = array(
							'message' => Yii::t('system', 'the chosen account has been haven account unlock'),
						);
						echo $this->failureJson($jsonData);
						die;
					}
					//修改账号状态为未冻结
					$flag=$models->unLockAccount($wishAccount);
				}
			} catch (Exception $e) {
				$flag = false;
			}
		}
		if($flag){
			$jsonData = array(
				'message' =>Yii::t('system', 'Unlock Success'),
				'forward' =>'/wish/wishaccount/list',
				'navTabId'=>'page' . WishAccount::getIndexNavTabId(),
				'callbackType'=>'closeCurrent'
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
		$models =  new WishAccount();
		$shutDown = WishAccount::STATUS_SHUTDOWN;//获取账号状态为关闭的参数
		if (Yii::app()->request->isAjaxRequest && isset($_REQUEST['ids'])) {
			try {
				foreach (explode(',',$_REQUEST['ids']) as $wishAccount){
					//获取账号基本信息
					$data = $models->getAccountInfoById($wishAccount);
					if($data['status'] == $shutDown){
						$jsonData = array(
							'message' => Yii::t('system', 'the chosen account has been haven account closed'),
						);
						echo $this->failureJson($jsonData);
						die;
					}
					//修改账号状态为关闭
					$flag=$models->shutDownAccount($wishAccount);
				}
			} catch (Exception $e) {
				$flag = false;
			}
		}
		if($flag){
			$jsonData = array(
				'message' =>Yii::t('system', 'ShutDown Success'),
				'forward' =>'/wish/wishaccount/list',
				'navTabId'=>'page' . WishAccount::getIndexNavTabId(),
				'callbackType'=>'closeCurrent'
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
		$models =  new WishAccount();
		$open = WishAccount::STATUS_OPEN;//获取账号状态为开启的参数
		if (Yii::app()->request->isAjaxRequest && isset($_REQUEST['ids'])) {
			try {
				foreach (explode(',',$_REQUEST['ids']) as $wishAccount){
					//获取账号基本信息
					$data = $models->getAccountInfoById($wishAccount);
					if($data['status'] == $open){
						$jsonData = array(
							'message' => Yii::t('order', 'the chosen account has been haven account opened'),
						);
						echo $this->failureJson($jsonData);
						die;
					}
					//修改账号状态为开启
					$flag=$models->openAccount($wishAccount);
				}
			} catch (Exception $e) {
				$flag = false;
			}
		}
		if($flag){
			$jsonData = array(
				'message' =>Yii::t('system', 'Open Success'),
				'forward' =>'/wish/wishaccount/list',
				'navTabId'=>'page' . WishAccount::getIndexNavTabId(),
				'callbackType'=>'closeCurrent'
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
		$model = $this->loadModel($_REQUEST['id']);

		if (Yii::app()->request->isAjaxRequest && isset($_POST['WishAccount'])) {
			$isExist = $model->getInfoByAccountname( trim($_POST['WishAccount']['account_name']) );
			$msg = '';
			if( $isExist ){
				$flag = false;
				$msg = " The account '".$_POST['WishAccount']['account_name']."' has been exist already!";
			}else{
				$model->attributes = $_POST['WishAccount'];
				$userId = Yii::app()->user->id;
				$model->setAttribute('account_name', $_POST['WishAccount']['account_name']);
				$model->setAttribute('modify_user_id', $userId);
				$model->setAttribute('modify_time', date('Y-m-d H:i:s'));
				if ($model->validate()) {
					$flag = $model->save();
				}else{
					$flag = false;
				}
			}

			if($flag){
				$jsonData = array(
					'message' =>Yii::t('system', 'Update successful'),
					'forward' =>'/wish/wishaccount/list',
					'navTabId'=>'page' . WishAccount::getIndexNavTabId(),
					'callbackType'=>'closeCurrent'
				);
				echo $this->successJson($jsonData);
			}else{
				echo $this->failureJson(array('message' => Yii::t('system', 'Update failure').$msg));
			}
			Yii::app()->end();
		}
		$this->render('update',array('model' => $model));
	}

	/**
	 * 激活帐号
	 */
	public function actionActivation(){
		$model= new WishAccount();
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
	 * @desc 获取账号基本信息
	 */
	public function loadModel($id){
		$model = UebModel::model('WishAccount')->findByPk($id);
		if($model===false){
			throw new CHttpException ( 404, Yii::t ( 'app', 'The requested page does not exist.' ) );
		}else{
			return $model;
		}
	}


	/**
	 * @desc 设置部门
	 */
	public function actionSetdepartment(){
		$ids = Yii::app()->request->getParam('ids');
		$wishAccountModel = new WishAccount();
		if($_POST){
			$departmentArr = Yii::app()->request->getParam('WishAccount');
			$departmentID  = $departmentArr['department_id'];
			if(!$ids){
				echo $this->failureJson(array('message'=>'请选择'));
	            exit;
			}

			//判断部门
			if(!in_array($departmentID, array(WishAccount::DEPARTMENT_SHENZHEN,WishAccount::DEPARTMENT_CHANGSHA))){
				echo $this->failureJson(array('message'=>'请选择部门'));
	            exit;
			}

			$updateArr = array(
				'department_id' => $departmentID
			);
			$flag = $wishAccountModel->getDbConnection()->createCommand()->update($wishAccountModel::tableName(), $updateArr, "id IN(".$ids.")");
			if(!$flag){
				echo $this->failureJson(array('message'=>'设置部门失败'));
	            exit;
			}

			$jsonData = array(
                'message' => '设置成功',
                'forward' =>'/wish/wishaccount/list',
                'navTabId'=> 'page' . WishAccount::getIndexNavTabId(),
                'callbackType'=>'closeCurrent'
            );
	        echo $this->successJson($jsonData);

		}else{
			$departmentList = $wishAccountModel->getDepartment();
			$this->render('setdepartment',array('model' => $wishAccountModel, 'departmentList'=>$departmentList, 'ids'=>$ids));
		}
	}
	
	public function actionGettokenform(){
		$accountID = Yii::app()->request->getParam('account_id');
		if(empty($accountID)) exit('无账号ID');
		$accountInfo = WishAccount::model()->findByPk($accountID);
		if(empty($accountInfo)){
			exit("无效账号");
		}
		echo "https://sandbox.merchant.wish.com/oauth/authorize?client_id={$accountInfo['client_id']}";
		echo "<form action='https://sandbox.merchant.wish.com/oauth/authorize?client_id={$accountInfo['client_id']}' target='__blank'>
			<input type='submit' value='提交'></input>
		</form>";
	}
	
	/**
	 * @desc 根据code获取token
	 * @link /wish/wishaccount/gettoken/account_id/xx/code/xx
	 * https://merchant.wish.com/oauth/authorize?client_id=xxx
	 */
	public function actionGettoken(){
		$accountID = Yii::app()->request->getParam('account_id');
		$code = Yii::app()->request->getParam('code');
		if(empty($accountID) || empty($code)){
			exit('参数错误， account_id 和 code 都需要');
		}
		$request = new AccessTokenRequest();
		$request->setCode($code);
		$response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
		echo "$accountID <pre>";
		print_r($response);
		if ($request->getIfSuccess()) {
		//将token更新到账号表
			$accessToken = $response->data->access_token;
			$refreshToken = $response->data->refresh_token;
			$tokenExpiredTime = $response->data->expiry_time;
			$data = array(
						'access_token' => $accessToken,
						'refresh_token' => $refreshToken,
						'token_expired_time' => $tokenExpiredTime,
				);
			WishAccount::model()->updateByPk($accountID, $data);
		}
		
	}
	/**
	 * @desc 通过refreshtoken换取access token
	 * @link /wish/wishaccount/refreshtoken/account_id/xx
	 */
	public function actionRefreshtoken() {
		$saccountID = Yii::app()->request->getParam('account_id');
		//if(empty($accountID)) exit("没有指定账号");
		
		$wishAccountList = WishAccount::model()->getAvailableIdNamePairs();
		foreach ($wishAccountList as $accountID=>$accountName){
			if($saccountID && $accountID != $saccountID){
				continue;
			}
			echo "<br>", $accountID ,"<br>";
			$request = new AuthTestRequest();
			$request->setAccount($accountID);
			$testResult = $request->setRequest()->sendRequest()->getResponse();
			if($request->getIfSuccess()){
				$info = WishAccount::model()->getDbConnection()->createCommand()->from(WishAccount::model()->tableName())->where("id='{$accountID}'")->queryRow();
				if(empty($info)) continue;
				if($info['token_expired_time']-2*3600 > time()){
					//快过期的2小时内刷新
					echo "不需刷";
					continue;
				}
			}
			
			$refreshTokenRequest = new RefreshTokenRequest();
			$response = $refreshTokenRequest->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
			echo "$accountID <pre>";
			//print_r($response);
			if ($refreshTokenRequest->getIfSuccess()) {
				//将token更新到账号表
				$accessToken = $response->data->access_token;
				$refreshToken = $response->data->refresh_token;
				$tokenExpiredTime = $response->data->expiry_time;
				$data = array(
						'access_token' => $accessToken,
						'refresh_token' => $refreshToken,
						'token_expired_time' => $tokenExpiredTime,
				);
				WishAccount::model()->updateByPk($accountID, $data);
			}
		}
		
	}
	/**
	 * @desc 同步wish token到oms
	 * @link /wish/wishaccount/synctokentooms/account_id/xx
	 */
	public function actionSynctokentooms(){
		error_reporting(E_ALL);
		
		$accountID = Yii::app()->request->getParam('account_id');
		
		if($accountID){
			$accountList = array($accountID=>'specific account');
		}else{
			$accountList = WishAccount::model()->getAvailableIdNamePairs();
		}
		
		//循环每个账号发送一个拉listing的请求
		foreach ($accountList as $accountID=>$accountName) {
			$accountInfo = WishAccount::model()->find("id='{$accountID}'");
			if(empty($accountInfo)) continue;
		
			//
			$info = OmsWishAccount::model()->getDbConnection()->createCommand()->from(OmsWishAccount::model()->tableName())->where("wish_id='{$accountID}'")->queryRow();
			if(empty($info)){
				$data = array(
						'wish_id'=>$accountInfo['id'],
						'account'=>$accountInfo['account'],
						'account_name'=>$accountInfo['account_name'],
						'token'=>'',
						'status'=>1,
						'token_new'=>'',
						'client_id'=>$accountInfo['client_id'],
						'client_secret'=>$accountInfo['client_secret'],
						'access_token'=>$accountInfo['access_token'],
						'refresh_token'		=>	$accountInfo['refresh_token'],
						'token_expired_time'	=>	$accountInfo['token_expired_time'],
						'redirect_uri'			=>	$accountInfo['redirect_uri']
				);
				$res = OmsWishAccount::model()->getDbConnection()->createCommand()->insert(OmsWishAccount::model()->tableName(), $data);
			}else{
				$data = array(
						'access_token' 		=> 	$accountInfo['access_token'],
						'refresh_token'		=>	$accountInfo['refresh_token'],
						'token_expired_time'	=>	$accountInfo['token_expired_time'],
				);
				//print_r($data);
				$res = OmsWishAccount::model()->getDbConnection()->createCommand()->update(OmsWishAccount::model()->tableName(), $data, "wish_id='{$accountID}'");
			}
			
			echo $accountID;
			var_dump($res);
		}
	}
}