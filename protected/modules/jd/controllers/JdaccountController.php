<?php
/**
 * @DESC 京东账号管理
 * @author zhangf
 *
 */
class JdaccountController extends UebController {
	
	/**
	 * @desc 京东平台帐号列表
	 * @author Michael
	 * @since 2015/12/01
	 */
	public function actionList(){
		$model = UebModel::model('JdAccount');
		$this->render('list',array('model'=>$model));
	}
	
	/**
	 * @desc 京东平台冻结帐号
	 * @author Michael
	 * @since 2015/12/01
	 */
	public function actionLockaccount(){
		$jdModel =  new JdAccount();
		$lock = JdAccount::STATUS_ISLOCK;
		if (Yii::app()->request->isAjaxRequest && isset($_REQUEST['ids'])) {
			try {
				foreach (explode(',',$_REQUEST['ids']) as $jdAccount){
					//获取账号信息
					$data = $jdModel->getAccountInfoByID($jdAccount);
					if($data['is_locked'] == $lock){
						$jsonData = array(
								'message' => Yii::t('system', 'the chosen account has been haven account locked'),
						);
						echo $this->failureJson($jsonData);
						die;
					}
					//需改账号状态变为冻结状态
					$flag=$jdModel->LockAccount($jdAccount);
				}
			} catch (Exception $e) {
				$flag = false;
			}
		}
		if($flag){
			$jsonData = array(
					'message' =>Yii::t('system', 'Lock Success'),
					'navTabId'=>'page' . JdAccount::getIndexNavTabId(),
			);
			echo $this->successJson($jsonData);
		}else{
			echo $this->failureJson(array('message'=> Yii::t('system', 'Lock Failed')));
		}
		Yii::app()->end();
	}
	
	/**
	 * @desc 京东平台解冻帐号
	 * @author Michael
	 * @since 2015/12/01
	 */
	public function actionUnlockaccount(){
		$jdModel =  new JdAccount();
		$unLock = JdAccount::STATUS_NOTLOCK;
		if (Yii::app()->request->isAjaxRequest && isset($_REQUEST['ids'])) {
			try {
				foreach (explode(',',$_REQUEST['ids']) as $jdAccount){
					//获取账号基本信息
					$data = $jdModel->getAccountInfoByID($jdAccount);
					if($data['is_locked'] == $unLock){
						$jsonData = array(
								'message' => Yii::t('system', 'the chosen account has been haven account unlock'),
						);
						echo $this->failureJson($jsonData);
						die;
					}
					//修改账号状态为未冻结
					$flag=$jdModel->unLockAccount($jdAccount);
				}
			} catch (Exception $e) {
				$flag = false;
			}
		}
		if($flag){
			$jsonData = array(
					'message' =>Yii::t('system', 'Unlock Success'),
					'navTabId'=>'page' . JdAccount::getIndexNavTabId(),
			);
			echo $this->successJson($jsonData);
		}else{
			echo $this->failureJson(array('message'=> Yii::t('system', 'Unlock Failed')));
		}
		Yii::app()->end();
	}
	
	/**
	 * @desc 关闭京东平台帐号
	 * @author Michael
	 * @since  2015-12-01
	 */
	public function actionShutdownaccount(){
		$jdModel =  new JdAccount();
		$shutDown = JdAccount::STATUS_CLOSED;
		if (Yii::app()->request->isAjaxRequest && isset($_REQUEST['ids'])) {
			try {
				foreach (explode(',',$_REQUEST['ids']) as $jdAccount){
					$data = $jdModel->getAccountInfoByID($jdAccount);
					if($data['status'] == $shutDown){
						$jsonData = array(
								'message' => Yii::t('system', 'the chosen account has been haven account closed'),
						);
						echo $this->failureJson($jsonData);
						die;
					}
					//修改账号状态为关闭
					$flag=$jdModel->shutDownAccount($jdAccount);
				}
			} catch (Exception $e) {
				$flag = false;
			}
		}
		if($flag){
			$jsonData = array(
					'message' =>Yii::t('system', 'ShutDown Success'),
					'navTabId'=>'page' . JdAccount::getIndexNavTabId(),
			);
			echo $this->successJson($jsonData);
		}else{
			echo $this->failureJson(array('message'=> Yii::t('system', 'ShutDown Failed')));
		}
		Yii::app()->end();
	}
	
	/**
	 * @desc 开启京东平台帐号
	 * @author Michael
	 * @since 2015-12-01
	 */
	public function actionOpenaccount(){
		$jdModel =  new JdAccount();
		$open = JdAccount::STATUS_OPEN;
		if (Yii::app()->request->isAjaxRequest && isset($_REQUEST['ids'])) {
			try {
				foreach (explode(',',$_REQUEST['ids']) as $jdAccount){
					//获取账号基本信息
					$data = $jdModel->getAccountInfoByID($jdAccount);
					if($data['status'] == $open){
						$jsonData = array(
								'message' => Yii::t('order', 'the chosen account has been haven account opened'),
						);
						echo $this->failureJson($jsonData);
						die;
					}
					//修改账号状态为开启
					$flag=$jdModel->openAccount($jdAccount);
				}
			} catch (Exception $e) {
				$flag = false;
			}
		}
		if($flag){
			$jsonData = array(
					'message' =>Yii::t('system', 'Open Success'),
					'navTabId'=>'page' . JdAccount::getIndexNavTabId(),
			);
			echo $this->successJson($jsonData);
		}else{
			echo $this->failureJson(array('message'=> Yii::t('system', 'Open Failed')));
		}
		Yii::app()->end();
	}
	
	/**
	 * @desc 获取token
	 * 
	 * @author Micahel
	 * @since 2015/12/01
	 */
	public function actionGetaccesstoken() {
		error_reporting(E_ALL);
		if(count(explode(',',$_REQUEST['ids']))>1){
			echo $this->failureJson(array('message' => Yii::t('system', '每次只能激活一个帐号！')));
			Yii::app()->end();
		}
		$accountID = Yii::app()->request->getParam('ids');
		$code = Yii::app()->request->getParam('code');
		$accessTokenRequest = new AccessTokenRequest();
		$response = $accessTokenRequest->setAccount($accountID)
										->setCode($code)
										->setRequest()
										->sendRequest()->getResponse();
		if ($accessTokenRequest->getIfSuccess()) {
			$expiresIn = $response->expires_in;	//多少秒内过期
			$expiresTime = time() + $expiresIn;	//过期时间
			//将token保存
			$data = array(
					'access_token' 			=> $response->access_token,
					'refresh_token' 		=> $response->refresh_token,
					'token_expired_time' 	=> $expiresTime,
			);
			JdAccount::model()->updateByPk($accountID, $data);
			echo $this->successJson(array(
					'message' => Yii::t('system', 'Refresh Access Token Successful'),
			));
		} else {
			echo $this->failureJson(array(
					'message' => $accessTokenRequest->getErrorMsg(),
			));
		}
	}

	/**
	 * @desc 刷新token
	 * @link /jd/jdaccount/refreshtoken/ids/xx
	 */
	public function actionRefreshtoken(){
		error_reporting(E_ALL);
		if(count(explode(',',$_REQUEST['ids']))>1){
			echo $this->failureJson(array('message' => Yii::t('system', '每次只能激活一个帐号！')));
			Yii::app()->end();
		}
		$accountID = Yii::app()->request->getParam('ids');
		$accessTokenRequest = new RefreshAccessTokenRequest();
		$response = $accessTokenRequest->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
		if ($accessTokenRequest->getIfSuccess()) {
			$expiresIn = $response->expires_in;	//多少秒内过期
			$expiresTime = time() + $expiresIn;	//过期时间
			//将token保存
			$data = array(
					'access_token' => $response->access_token,
					'refresh_token' => $response->refresh_token,
					'token_expired_time' => $expiresTime,
			);
			JdAccount::model()->updateByPk($accountID, $data);
			echo $this->successJson(array(
					'message' => Yii::t('system', 'Refresh Access Token Successful'),
			));
		} else {
			echo $this->failureJson(array(
					'message' => $accessTokenRequest->getErrorMsg(),
			));
		}
	}
	/**
	 * @desc 编辑帐号简称
	 * @author Michael
	 * @since  2015/12/01
	 */
	public function actionUpdate(){
		$model = $this->loadModel($_REQUEST['id']);
		print $_POST['jdaccount'];
		if (Yii::app()->request->isAjaxRequest && isset($_POST['JdAccount'])) {
			$isExist = $model->getInfoByShortName(trim($_POST['JdAccount']['short_name']) );
			$msg = '';
			if( $isExist ){
				$flag = false;
				$msg = " The account '".$_POST['JdAccount']['short_name']."' has been exist already!";
			}else{
				$model->attributes = $_POST['JdAccount'];
				$userId = Yii::app()->user->id;
				$model->setAttribute('short_name', $_POST['JdAccount']['short_name']);
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
						'navTabId'=>'page' . JdAccount::getIndexNavTabId(),
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
	 * @desc 获取账号基本信息
	 */
	public function loadModel($id){
		$model = UebModel::model('JdAccount')->findByPk($id);
		if($model===false){
			throw new CHttpException (404, Yii::t ( 'app', 'The requested page does not exist.' ) );
		}else{
			return $model;
		}
	}
	
}