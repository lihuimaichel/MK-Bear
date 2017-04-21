<?php
class PlatformjoomaccountController extends UebController {
	
	/**
	 * @todo joom帐号管理列表
	 * @author hanxy
	 * @since 2017-02-27
	 */
	/** @var object  模型对象 **/
	protected $_model = null;
	
	/**
	 * (non-PHPdoc)
	 * @see CController::init()
	 */
	public function init() {
		$this->_model = new PlatformJoomAccount();
		parent::init();
	}

	/**
	 * 显示账号列表
	 */
	public function actionList(){
		$this->render('list',array('model'=>$this->_model));
	}


	/**
	 * 添加账号
	 */
	public function actionAdd(){
		//获取账号状态
		$accountStatusList = PlatformJoomAccount::getAccountStatus();

		//获取所属部门
		$departmentList = Department::model()->getDepartmentByKeywords('joom');

		$this->render("add", array("model"=>$this->_model, 'departmentList'=>$departmentList, 'accountStatusList'=>$accountStatusList));
		Yii::app()->end();			
	}


	/**
	 * 保存插入数据
	 */
	public function actionAddsave(){
		$account = Yii::app()->request->getParam('account');
		if(!$account){
			echo $this->failureJson(array('message'=>'账号名称不能为空'));
			Yii::app()->end();
		}

		$accountName = Yii::app()->request->getParam('account_name');
		if(!$accountName){
			echo $this->failureJson(array('message'=>'账号简称不能为空'));
			Yii::app()->end();
		}

		$departmentID = Yii::app()->request->getParam('department_id');
		if(!$departmentID){
			echo $this->failureJson(array('message'=>'所属部门必须选择'));
			Yii::app()->end();
		}

		$clientId = Yii::app()->request->getParam('client_id');
		if(!$clientId){
			echo $this->failureJson(array('message'=>'Client Id不能为空'));
			Yii::app()->end();
		}

		$clientSecret = Yii::app()->request->getParam('client_secret');
		if(!$clientSecret){
			echo $this->failureJson(array('message'=>'Client Secret不能为空'));
			Yii::app()->end();
		}

		$redirectUri = Yii::app()->request->getParam('redirect_uri');
		if(!$redirectUri){
			echo $this->failureJson(array('message'=>'Redirect Uri不能为空'));
			Yii::app()->end();
		}

		$isExist = $this->_model->getOneByCondition('id',"account = '{$account}' OR account_name = '{$accountName}' OR client_id = '{$clientId}'");
		if($isExist){
			echo $this->failureJson(array('message'=>'此账号已存在'));
			Yii::app()->end();
		}

		$accountParam = array(
			'account'              => $account,
			'account_name'         => $accountName,
			'department_id'        => $departmentID,
			'client_id'            => $clientId,
			'client_secret'        => $clientSecret,
			'redirect_uri'         => $redirectUri,
			'token_status'         => 0,
			'refresh_token_status' => 0,
			'create_user_id'       => (int)Yii::app()->user->id,
			'create_time'          => date('Y-m-d H:i:s')
		);

		$result = $this->_model->insertData($accountParam);
		if($result){
			$data=$this->_model->joomAccountAuthorize($result, $redirectUri);
			$url=$data->_get_joom_code_url;
			echo $this->successJson(array('message' => '添加成功', 'url' => $url, 'aid' => $result));
		}else{
			echo $this->failureJson(array('message'=>'添加失败'));
		}
		Yii::app()->end();
	}


	/**
	 * 保存获取到的token信息
	 */
	public function actionTokensave(){
		$code = Yii::app()->request->getParam('code');
		if(!$code){
			echo $this->failureJson(array('message'=>'code不能为空'));
			Yii::app()->end();
		}

		$accountID = Yii::app()->request->getParam('id');
		if(!$accountID){
			echo $this->failureJson(array('message'=>'账号ID不能为空'));
			Yii::app()->end();
		}

		$request = new JoomAccessTokenRequest();
		$request->setCode($code);
		$response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
		if ($request->getIfSuccess()){
			$updateArr=array(
				'refresh_token'        => $response->data->refresh_token,
				'access_token'         => $response->data->access_token,
				'token_invalid_time'   => date('Y-m-d H:i:s', $response->data->expiry_time),
				'token_status'         => 1,
				'refresh_token_status' => 1
    		);
    		$flag = $this->_model->updateData($updateArr,'id = :id', array(':id'=>$accountID));
    		if($flag){
    			echo $this->successJson(array('message' => '获取token成功'));
    		}else{
    			echo $this->failureJson(array('message'=>'获取token失败'));
    		}
		}else{
    		echo $this->failureJson(array('message'=>'获取token失败'));
    	}
	}


	/**
	 * 修改账号
	 */
	public function actionEdit(){
		$id = Yii::app()->request->getParam('id');
		if($_POST){
			$accountParam = Yii::app()->request->getParam('PlatformJoomAccount');
			$accountParam['modify_user_id'] = (int)Yii::app()->user->id;
			$accountParam['modify_time'] = date('Y-m-d H:i:s');
			$result = $this->_model->updateData($accountParam, 'id = :id', array(':id'=>$id));
			if($result){
				$jsonData = array(
					'message' => '更改成功',
					'forward' =>'/platformaccount/platformjoomaccount/list',
					'navTabId'=> 'page' . PlatformJoomAccount::getIndexNavTabId(),
					'callbackType'=>'closeCurrent'
				);
				echo $this->successJson($jsonData);
			}else{
				echo $this->failureJson(array('message'=>'修改失败'));
			}
			Yii::app()->end();
		}

		$accountInfo = $this->_model->findByPk($id);
		if(!$accountInfo){
			echo $this->failureJson(array('message'=>'没有找到数据'));
			Yii::app()->end();
		}

		//获取所属部门
		$departmentList = Department::model()->getDepartmentByKeywords('joom');

		//获取账号状态
		$accountStatusList = PlatformJoomAccount::getAccountStatus();

		$this->render("edit", array("model"=>$accountInfo, 'departmentList'=>$departmentList, 'accountStatusList'=>$accountStatusList));
		Yii::app()->end();
	}


	/**
	 * 检查token
	 */
	public function actionVerificationtoken(){
		set_time_limit(1200);
		$ids = Yii::app()->request->getParam('ids');
		$idsArr = explode(',', $ids);
		if(!$idsArr){
			echo $this->failureJson(array('message'=> '请选择账号'));
			Yii::app()->end();
		}

		foreach ($idsArr as $accountID) {
			$tokenStatus = 0;
			//判断账号token是否有效
			if($this->_model->isAccessTokenEffective($accountID)){
				$tokenStatus = 1;
			}
			
			$conditions = 'id = :id';
			$params = array(':id' => $accountID);
			$this->_model->updateData(array('token_status' => $tokenStatus), $conditions, $params);
		}

		echo $this->successJson(array('message'=>'操作成功'));
		Yii::app()->end();
	}


	/**
	 * 刷新token
	 */
	public function actionUpdatetoken(){
		set_time_limit(1200);
		$ids = Yii::app()->request->getParam('ids');
		$idsArr = explode(',', $ids);
		if(!$idsArr){
			echo $this->failureJson(array('message'=> '请选择账号'));
			Yii::app()->end();
		}

		foreach ($idsArr as $accountID) {
			//检查token是否有效
			if($this->_model->isAccessTokenEffective($accountID)){
				continue;
			}

			$request = new JoomRefreshTokenRequest;
			$response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
			if($request->getIfSuccess()){
				$data = array(
					'access_token'       => $response->data->access_token,
					'token_status'       => 1,
					'update_time'        => date('Y-m-d H:i:s'),
					'token_invalid_time' => date('Y-m-d H:i:s',$response->data->expiry_time),
					'to_oms_status'      => 0,
					'to_oms_time'        => NULL
				);
			}else{
				$data = array(
					'token_status' => 0
				);
			}

			$conditions = 'id = :id';
			$params = array(':id' => $accountID);
			$this->_model->updateData($data, $conditions, $params);
		}

		echo $this->successJson(array('message'=>'操作成功'));
		Yii::app()->end();
	}


	/**
	 * 同步到OMS
	 */
	public function actionTooms(){
		set_time_limit(1200);
		ini_set("display_errors", true);
		error_reporting(E_ALL);
		$ids = Yii::app()->request->getParam('ids');
		$idsArr = explode(',', $ids);
		if(!$idsArr){
			echo $this->failureJson(array('message'=> '请选择账号'));
			Yii::app()->end();
		}

		$joomAccountModel = new JoomAccount();
		$accountArray = $joomAccountModel->getIdNamePairs();
		$omsIds = array_keys($accountArray);

		//通过id查询出账号信息
		$accountArr = array();
		$accountInfo = $this->_model->getListByCondition('*',"id IN(".MHelper::simplode($idsArr).")");
		if($accountInfo){
			foreach ($accountInfo as $accInfo) {
				$accountArr[$accInfo['id']] = array(
					'account_name'       => $accInfo['account_name'],
					'account'            => $accInfo['account'],
					'client_id'          => $accInfo['client_id'],
					'client_secret'      => $accInfo['client_secret'],
					'access_token'       => $accInfo['access_token'],
					'refresh_token'      => $accInfo['refresh_token'],
					'status'             => $accInfo['status'],
					'redirect_uri'       => $accInfo['redirect_uri'],
					'token_invalid_time' => strtotime($accInfo['token_invalid_time'])
				);
			}
		}

		foreach ($idsArr as $accountID) {
			//判断账号是否存在
			if(!in_array($accountID, array_keys($accountArr))){
				continue;
			}

			$omsStatus = 0;

			//判断账号token是否有效
			if(!$this->_model->isAccessTokenEffective($accountID)){
				continue;
			}

			if(in_array($accountID, $omsIds)){
			
				$data = array(
					'access_token'       => $accountArr[$accountID]['access_token'],
					'client_id'          => $accountArr[$accountID]['client_id'],
					'client_secret'      => $accountArr[$accountID]['client_secret'],
					'refresh_token'      => $accountArr[$accountID]['refresh_token'],
					'token_expired_time' => $accountArr[$accountID]['token_invalid_time']
			    );
			    $result = $joomAccountModel->getDbConnection()->createCommand()->update($joomAccountModel->tableName(), $data, "id='{$accountID}'");
				if($result){
					$omsStatus = 1;
				}

			}else{

				$data = array(
					'id'                 => $accountID,
					'account'            => $accountArr[$accountID]['account'],
					'account_name'       => $accountArr[$accountID]['account_name'],
					'access_token'       => $accountArr[$accountID]['access_token'],
					'refresh_token'      => $accountArr[$accountID]['refresh_token'],
					'status'             => $accountArr[$accountID]['status'],
					'is_lock'            => 0,
					'token_expired_time' => $accountArr[$accountID]['token_invalid_time'],
					'client_id'          => $accountArr[$accountID]['client_id'],
					'client_secret'      => $accountArr[$accountID]['client_secret'],
					'token'              => '',
					'redirect_uri'       => $accountArr[$accountID]['redirect_uri']
			    );
			    $res = $joomAccountModel->getDbConnection()->createCommand()->insert($joomAccountModel->tableName(), $data);
				if($res){
					$omsStatus = 1;
				}
			}

			$updateArr = array('to_oms_status' => $omsStatus, 'to_oms_time'=> date('Y-m-d H:i:s'));
			$this->_model->updateData($updateArr, 'id = :id', array(':id'=>$accountID));
		}

		echo $this->successJson(array('message'=>'操作成功'));
		Yii::app()->end();
	}


	/**
	 * 重新授权
	 */
	public function actionReauthorization(){
		$id = Yii::app()->request->getParam('id');
		if(!$id){
			echo $this->failureJson(array('message'=> '请选择账号'));
			Yii::app()->end();
		}

		$info = $this->_model->findByPk($id);
		if(!$info){
			echo $this->failureJson(array('message'=> '信息不存在'));
			Yii::app()->end();
		}

		if($_POST){
			$data=$this->_model->joomAccountAuthorize($info->id, $info->redirect_uri);
			$url=$data->_get_joom_code_url;
			echo $this->successJson(array('message' => '添加成功', 'url' => $url));
			Yii::app()->end();
		}

		$this->render("authadd", array("model"=>$info));
	}


	/**
	 * 自动更新token程序
	 * @link /platformaccount/platformjoomaccount/autorunaccesstoken/id/1/times/1490878847
	 */
	public function actionAutorunaccesstoken(){
		set_time_limit(3600);
        ini_set("display_errors", true);
        error_reporting(E_ALL);

        $accountID = Yii::app()->request->getParam('id',1);
        $invalidTime = Yii::app()->request->getParam('times');

        $logModel = new PlatformAccountLog();
        //创建运行日志		
		$logId = $logModel->prepareLog($accountID, PlatformAccountLog::EVENT_JOOM_RUNING_TOKEN, Platform::CODE_JOOM);
		if(!$logId) {
			echo Yii::t('wish_listing', 'Log create failure');
			Yii::app()->end();
		}
		//检查账号是可以提交请求报告
		$checkRunning = $logModel->checkRunning($accountID, PlatformAccountLog::EVENT_JOOM_RUNING_TOKEN, Platform::CODE_JOOM);
		if(!$checkRunning){
			$logModel->setFailure($logId, Yii::t('systems', 'There Exists An Active Event'));
			echo Yii::t('systems', 'There Exists An Active Event');
			Yii::app()->end();
		}
		//设置日志为正在运行
		$logModel->setRunning($logId);
		if($accountID && $invalidTime){
			try{

				$joomAccountModel = new JoomAccount();

				//检查token是否有效
				if($this->_model->isAccessTokenEffective($accountID)){
					$expiredTime = $invalidTime - 3600;
					if($expiredTime > time()){
						$logModel->setSuccess($logId, '更新日期未小于过期时间');
						exit();
					}
				}

				$refreshTokenRequest = new JoomRefreshTokenRequest();
				$response = $refreshTokenRequest->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
				if ($refreshTokenRequest->getIfSuccess()) {
					//将token更新到账号表
					$accessToken = $response->data->access_token;
					$refreshToken = $response->data->refresh_token;
					$tokenExpiredTime = $response->data->expiry_time;
					$data = array(
							'access_token' => $accessToken,
							'refresh_token' => $refreshToken,
							'token_invalid_time' => date('Y-m-d H:i:s', $tokenExpiredTime),
							'token_status' => 1
					);

					$omsData = array('access_token' => $accessToken, 'token_expired_time' => $tokenExpiredTime);
					try{
						$dbtransaction = PlatformJoomAccount::model()->getDbConnection()->beginTransaction();
						$this->_model->updateByPk($accountID, $data);
						$joomAccountModel->getDbConnection()->createCommand()->update($joomAccountModel->tableName(), $omsData, "id='{$accountID}'");
						$dbtransaction->commit();
					}catch (Exception $e){
						$dbtransaction->rollback();
						echo $e->getMessage();
					}
				}
				
				$logModel->setSuccess($logId, 'token更新成功');

			}catch(Exception $e){
				$logModel->setFailure($logId, $e->getMessage());
				echo $e->getMessage();
			}
		}else{
			$wheres = "`status` = 1";
			$accountInfo = $this->_model->getListByCondition('id,token_invalid_time',$wheres);
			foreach ($accountInfo as $info) {
				MHelper::runThreadSOCKET('/'.$this->route.'/id/' . $info['id'] . '/times/' . strtotime($info['token_invalid_time']));
    			sleep(2);
			}
		}			

		Yii::app()->end('Finish');
	}


	/**
	 * 授权api设置
	 */
	public function actionSetapi(){
		$id = Yii::app()->request->getParam('id');
		$info = $this->_model->findByPk($id);
		if(!$info){
			echo $this->failureJson(array('message'=> '信息不存在'));
			Yii::app()->end();
		}

		if($_POST){
			$data = Yii::app()->request->getParam('PlatformJoomAccount');
			list($clientId, $clientSecret, $redirectUri) = array_values($data);
			if(!$clientId){
				echo $this->failureJson(array('message'=> 'Client Id不能为空'));
				Yii::app()->end();
			}

			if(!$clientSecret){
				echo $this->failureJson(array('message'=> 'Client Secret不能为空'));
				Yii::app()->end();
			}

			if(!$redirectUri){
				echo $this->failureJson(array('message'=> 'Redirect URI不能为空'));
				Yii::app()->end();
			}

			$conditions = 'id = :id';
			$params = array(':id' => $id);
			$this->_model->updateData($data, $conditions, $params);
			$jsonData = array(
				'message' => '更改成功',
				'forward' =>'/platformaccount/platformjoomaccount/list',
				'navTabId'=> 'page' . PlatformJoomAccount::getIndexNavTabId(),
				'callbackType'=>'closeCurrent'
			);
			echo $this->successJson($jsonData);
			Yii::app()->end();
		}

		$this->render("setapi", array("model"=>$info));
	}
}