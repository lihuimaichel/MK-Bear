<?php
class PlatformaliexpressaccountController extends UebController {
	
	/**
	 * @todo Aliexpress帐号管理列表
	 * @author hanxy
	 * @since 2017-02-20
	 */
	/** @var object  模型对象 **/
	protected $_model = null;
	
	/**
	 * (non-PHPdoc)
	 * @see CController::init()
	 */
	public function init() {
		$this->_model = new PlatformAliexpressAccount();
		parent::init();
	}

	/**
	 * 显示账号列表
	 */
	public function actionList(){
		$this->render('list',array('model'=>$this->_model));
	}

	/**
	 * 刷新token
	 */
	public function actionUpdatetoken(){
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

			$request = new GetAccountTokenRequest;
			$response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
			if($request->getIfSuccess()){
				$datetimes = time() + $response->expires_in;
				$data = array(
					'access_token'       => $response->access_token,
					'token_status'       => 1,
					'update_time'        => date('Y-m-d H:i:s'),
					'token_invalid_time' => date('Y-m-d H:i:s',$datetimes),
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
	 * 检查token
	 */
	public function actionVerificationtoken(){
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
	 * 同步到OMS
	 */
	public function actionTooms(){
		$ids = Yii::app()->request->getParam('ids');
		$idsArr = explode(',', $ids);
		if(!$idsArr){
			echo $this->failureJson(array('message'=> '请选择账号'));
			Yii::app()->end();
		}

		//获取oms的速卖通账号ID
		// $omsAccountModel = new OmsAliexpressAccount();
		//获取速卖通账号ID
		$aliexpressAccountModel = new AliexpressAccount();
		$omsIds = $aliexpressAccountModel->getAliAccountID();

		//通过id查询出账号信息
		$accountArr = array();
		$accountInfo = $this->_model->getListByCondition('*',"id IN(".MHelper::simplode($idsArr).")");
		if($accountInfo){
			foreach ($accountInfo as $accInfo) {
				$accountArr[$accInfo['id']] = array(
					'short_name'     => $accInfo['short_name'],
					'email'          => $accInfo['email'],
					'app_key'        => $accInfo['app_key'],
					'secret_key'     => $accInfo['secret_key'],
					'access_token'   => $accInfo['access_token'],
					'refresh_token'  => $accInfo['refresh_token'],
					'resource_owner' => $accInfo['resource_owner'],
					'ali_id'         => $accInfo['ali_id'],
					'status'         => $accInfo['status'],
					'create_time'    => $accInfo['create_time'],
					'modify_time'    => $accInfo['modify_time'],
					'create_user_id' => $accInfo['create_user_id']
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
					'short_name'   => $accountArr[$accountID]['short_name'],
					'access_token' => $accountArr[$accountID]['access_token'],
					'modify_time'  => date("Y-m-d H:i:s")
			    );
				$result = $aliexpressAccountModel->updateData($data, 'id = :id', array(':id'=>$accountID));
				if($result){
					$omsStatus = 1;
				}

			}else{

				$data = array(
					'id'               => $accountID,
					'account_key'      => $accountArr[$accountID]['ali_id'],
					'short_name'       => $accountArr[$accountID]['short_name'],
					'email'            => $accountArr[$accountID]['email'],
					'resource_owner'   => $accountArr[$accountID]['resource_owner'],
					'access_token'     => $accountArr[$accountID]['access_token'],
					'refresh_token'    => $accountArr[$accountID]['refresh_token'],
					'status'           => $accountArr[$accountID]['status'],
					'last_update_time' => date("Y-m-d H:i:s"),
					'modify_user_id'   => $accountArr[$accountID]['create_user_id'],
					'modify_time'      => $accountArr[$accountID]['create_time'],
					'app_key'          => $accountArr[$accountID]['app_key'],
					'secret_key'       => $accountArr[$accountID]['secret_key'],
					'account'          => $accountArr[$accountID]['ali_id']
			    );
				$result = $aliexpressAccountModel->insertData($data);
				if($result){
					$omsStatus = 1;
					$randNum = rand(1,3);
					$insertSql = "INSERT INTO market_aliexpress.`ueb_aliexpress_account_map`(account_id,group_id) VALUES ({$accountID},{$randNum})";
					$aliexpressAccountModel->getDbConnection()->createCommand($insertSql)->execute();
				}
			}

			$updateArr = array('to_oms_status' => $omsStatus, 'to_oms_time'=> date('Y-m-d H:i:s'));
			$this->_model->updateData($updateArr, 'id = :id', array(':id'=>$accountID));
		}

		echo $this->successJson(array('message'=>'操作成功'));
		Yii::app()->end();
	}


	/**
	 * 修改账号
	 */
	public function actionEdit(){
		$id = Yii::app()->request->getParam('id');
		if($_POST){
			$accountParam = Yii::app()->request->getParam('PlatformAliexpressAccount');
			$result = $this->_model->updateData($accountParam, 'id = :id', array(':id'=>$id));
			if($result){
				$jsonData = array(
					'message' => '更改成功',
					'forward' =>'/platformaccount/platformaliexpressaccount/list',
					'navTabId'=> 'page' . PlatformAliexpressAccount::getIndexNavTabId(),
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
		$departmentList = Department::model()->getDepartmentByKeywords('ali');

		//获取账号状态
		$accountStatusList = PlatformAliexpressAccount::getAccountStatus();

		$this->render("edit", array("model"=>$accountInfo, 'departmentList'=>$departmentList, 'accountStatusList'=>$accountStatusList));
		Yii::app()->end();			
	}


	/**
	 * 添加账号
	 */
	public function actionAdd(){
		//获取账号状态
		$accountStatusList = PlatformAliexpressAccount::getAccountStatus();

		//获取所属部门
		$departmentList = Department::model()->getDepartmentByKeywords('ali');

		$this->render("add", array("model"=>$this->_model, 'departmentList'=>$departmentList, 'accountStatusList'=>$accountStatusList));
		Yii::app()->end();			
	}


	/**
	 * 保存插入数据
	 */
	public function actionAddsave(){
		$email = Yii::app()->request->getParam('email');
		if(!$email){
			echo $this->failureJson(array('message'=>'申请邮箱不能为空'));
			Yii::app()->end();
		}

		$shortName = Yii::app()->request->getParam('short_name');
		if(!$shortName){
			echo $this->failureJson(array('message'=>'账号简称不能为空'));
			Yii::app()->end();
		}

		$departmentID = Yii::app()->request->getParam('department_id');
		if(!$departmentID){
			echo $this->failureJson(array('message'=>'所属部门必须选择'));
			Yii::app()->end();
		}

		$appKey = Yii::app()->request->getParam('app_key');
		if(!$appKey){
			echo $this->failureJson(array('message'=>'appKey不能为空'));
			Yii::app()->end();
		}

		$secretKey = Yii::app()->request->getParam('secret_key');
		if(!$secretKey){
			echo $this->failureJson(array('message'=>'appSecret不能为空'));
			Yii::app()->end();
		}

		$redirectUri = Yii::app()->request->getParam('redirect_uri', null);

		$isExist = $this->_model->getOneByCondition('id',"email = '{$email}' OR short_name = '{$shortName}'");
		if($isExist){
			echo $this->failureJson(array('message'=>'此账号已存在'));
			Yii::app()->end();
		}

		$times = date('Y-m-d H:i:s');
		$accountParam = array(
			'email'                => $email,
			'short_name'           => $shortName,
			'app_key'              => $appKey,
			'secret_key'           => $secretKey,
			'department_id'        => $departmentID,
			'token_status'         => 0,
			'refresh_token_status' => 0,
			'redirect_uri'         => $redirectUri,
			'create_user_id'       => (int)Yii::app()->user->id,
			'create_time'          => $times,
			'modify_user_id'       => (int)Yii::app()->user->id,
			'modify_time'          => $times
		);

		$result = $this->_model->insertData($accountParam);
		if($result){
			$data=$this->_model->accountAuthorize($result, $redirectUri);
			$url=$data->_ali_url;
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

		$id = Yii::app()->request->getParam('id');
		if(!$id){
			echo $this->failureJson(array('message'=>'账号ID不能为空'));
			Yii::app()->end();
		}

		$request= new GetAccountRefreshTokenRequest();
    	$response=$request->setAccount($id,$code)->setRequest()->sendRequest()->getResponse();
    	if(isset($response->refresh_token)){
    		$datetimes = time() + $response->expires_in;
    		$updateArr=array(
				'ali_id'                => $response->aliId,
				'resource_owner'        => $response->resource_owner,
				'refresh_token'         => $response->refresh_token,
				'access_token'          => $response->access_token,
				'token_status'          => 1,
				'refresh_token_status'  => 1,
				'token_invalid_time' => date('Y-m-d H:i:s',$datetimes),
				'refresh_token_timeout' => MHelper::aliexpressTimeToBJTime($response->refresh_token_timeout)
    		);
    		$flag = $this->_model->updateData($updateArr,'id = :id', array(':id'=>$id));
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
	 * 更新refresh_token
	 */
	public function actionUpdaterefreshtoken(){
		$accountID = Yii::app()->request->getParam('ids');
		if(!$accountID){
			echo $this->failureJson(array('message'=> '请选择账号'));
			Yii::app()->end();
		}
		$request = new UpdateAccountRefreshTokenRequest;
		$response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
		if(isset($response->error_description)){
			$times = str_replace('refreshToken is too long to expire with expireTime ', '', $response->error_description);
			$times = substr($times, 0, 14);
			$data = array('refresh_token_timeout' => $times);
			$conditions = 'id = :id';
			$params = array(':id' => $accountID);
			$this->_model->updateData($data, $conditions, $params);
			echo $this->successJson(array('message'=>'刷新成功'));
			Yii::app()->end();
		}
		echo $this->failureJson(array('message'=>'刷新失败'));
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

		$redirectUri = Yii::app()->request->getParam('redirect_uri', null);

		$info = $this->_model->findByPk($id);
		if(!$info){
			echo $this->failureJson(array('message'=> '信息不存在'));
			Yii::app()->end();
		}

		if($_POST){
			$data=$this->_model->accountAuthorize($info->id, $redirectUri);
			$url=$data->_ali_url;
			echo $this->successJson(array('message' => '添加成功', 'url' => $url));
			Yii::app()->end();
		}

		$this->render("authadd", array("model"=>$info));
	}


	/**
	 * 自动更新token程序
	 * @link /platformaccount/platformaliexpressaccount/autorunaccesstoken/id/1/times/1490878847
	 */
	public function actionAutorunaccesstoken(){
		set_time_limit(3600);
        ini_set("display_errors", true);
        error_reporting(E_ALL);

        $accountID = Yii::app()->request->getParam('id');
        $invalidTime = Yii::app()->request->getParam('times');

		if($accountID && $invalidTime){
			try{

				$logModel = new PlatformAccountLog();
		        //创建运行日志		
				$logId = $logModel->prepareLog($accountID, PlatformAccountLog::EVENT_ALIEXPRESS_RUNING_TOKEN, Platform::CODE_ALIEXPRESS);
				if(!$logId) {
					echo Yii::t('wish_listing', 'Log create failure');
					Yii::app()->end();
				}
				//检查账号是可以提交请求报告
				$checkRunning = $logModel->checkRunning($accountID, PlatformAccountLog::EVENT_ALIEXPRESS_RUNING_TOKEN, Platform::CODE_ALIEXPRESS);
				if(!$checkRunning){
					$logModel->setFailure($logId, Yii::t('systems', 'There Exists An Active Event'));
					echo Yii::t('systems', 'There Exists An Active Event');
					Yii::app()->end();
				}
				//设置日志为正在运行
				$logModel->setRunning($logId);

				$AliexpressAccountModel = new AliexpressAccount();

				//检查token是否有效
				if($this->_model->isAccessTokenEffective($accountID)){
					$expiredTime = $invalidTime - 3600;
					if($expiredTime > time()){
						$logModel->setSuccess($logId, '更新日期未小于过期时间');
						exit();
					}
				}

				$request = new GetAccountTokenRequest;
				$response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
				if($request->getIfSuccess()){
					$datetimes = time() + $response->expires_in;
					$data = array(
						'access_token'       => $response->access_token,
						'token_status'       => 1,
						'update_time'        => date('Y-m-d H:i:s'),
						'token_invalid_time' => date('Y-m-d H:i:s',$datetimes)
					);
			
					//将token更新到账号表
					$omsData = array('access_token' => $response->access_token, 'modify_time' => date('Y-m-d H:i:s'));
					try{
						$dbtransaction = PlatformAliexpressAccount::model()->getDbConnection()->beginTransaction();
						$this->_model->updateByPk($accountID, $data);
						$AliexpressAccountModel->getDbConnection()->createCommand()->update($AliexpressAccountModel->tableName(), $omsData, "id='{$accountID}'");
						$dbtransaction->commit();
					}catch (Exception $e){
						$dbtransaction->rollback();
						echo $e->getMessage();
					}
				}
				
				$logModel->setSuccess($logId, 'token更新成功');

			}catch(Exception $e){
				if(isset($logId) && $logId){
					$logModel->setFailure($logId, $e->getMessage());
				}

				echo $e->getMessage();
			}
		}else{
			$wheres = "`status` = 1";
			$accountInfo = $this->_model->getListByCondition('id,token_invalid_time',$wheres);
			foreach ($accountInfo as $info) {
				$invalidTimes = time();
				if($info['token_invalid_time']){
					$invalidTimes = strtotime($info['token_invalid_time']);
				}

				MHelper::runThreadSOCKET('/'.$this->route.'/id/' . $info['id'] . '/times/' . $invalidTimes);
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
			$data = Yii::app()->request->getParam('PlatformAliexpressAccount');
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
				'forward' =>'/platformaccount/platformaliexpressaccount/list',
				'navTabId'=> 'page' . PlatformAliexpressAccount::getIndexNavTabId(),
				'callbackType'=>'closeCurrent'
			);
			echo $this->successJson($jsonData);
			Yii::app()->end();
		}

		$this->render("setapi", array("model"=>$info));
	}
}