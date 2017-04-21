<?php
class PlatformebayaccountController extends UebController {
	
	/**
	 * @todo ebay帐号管理列表
	 * @author hanxy
	 * @since 2017-03-11
	 */
	/** @var object  模型对象 **/
	protected $_model = null;
	
	/**
	 * (non-PHPdoc)
	 * @see CController::init()
	 */
	public function init() {
		$this->_model = new PlatformEbayAccount();
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
		//获取开发者账号列表
		$developerList = PlatformEbayDeveloperAccount::model()->getDeveloperAccountList();

		//获取所属部门
		$departmentList = EbayAccount::model()->getDepartment();

		//获取店铺站点
		$siteList = EbaySite::getSiteList();

		$this->render("add", array("model"=>$this->_model, 'developerList'=>$developerList, 'departmentList'=>$departmentList, 'siteList'=>$siteList));
		Yii::app()->end();			
	}


	/**
	 * 保存插入数据
	 */
	public function actionAddsave(){
		$userName = Yii::app()->request->getParam('user_name');
		if(!$userName){
			echo $this->failureJson(array('message'=>'账号名称必须选择'));
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

		$storeSite = Yii::app()->request->getParam('store_site');
		if(!$storeSite){
			echo $this->failureJson(array('message'=>'店铺站点必须选择'));
			Yii::app()->end();
		}

		$developerAccountModel = new PlatformEbayDeveloperAccount();
		$developerID = Yii::app()->request->getParam('developer_id');
		if(!$developerID){
			$developerList = $developerAccountModel->getDeveloperAccountList();
			$developerIdArr = array_keys($developerList);
			$developerID = array_rand($developerIdArr,1);
		}

		$isExist = $this->_model->getOneByCondition('id',"user_name = '{$userName}' OR short_name = '{$shortName}'");
		if($isExist){
			echo $this->failureJson(array('message'=>'此账号已存在'));
			Yii::app()->end();
		}

		//取出开发者账号ru_name
		$developerInfo = $developerAccountModel->getOneByCondition('ru_name', "id = ".$developerID);
		if(!$developerInfo){
			echo $this->failureJson(array('message'=>'开发者账号不存在'));
			Yii::app()->end();
		}

		if(!$developerInfo['ru_name']){
			echo $this->failureJson(array('message'=>'开发者账号ru_name为空'));
			Yii::app()->end();
		}

		$accountParam['user_name'] = $userName;
		$accountParam['short_name'] = $shortName;
		$accountParam['department_id'] = $departmentID;
		$accountParam['developer_id'] = $developerID;
		$accountParam['store_site'] = $storeSite;
		$accountParam['token_status'] = 0;
		$accountParam['update_time'] = date('Y-m-d H:i:s');
		$accountParam['create_user_id'] = (int)Yii::app()->user->id;
		$accountParam['create_time'] = date('Y-m-d H:i:s');
		$accountParam['modify_user_id'] = (int)Yii::app()->user->id;
		$accountParam['modify_time'] = date('Y-m-d H:i:s');

		$result = $this->_model->insertData($accountParam);
		if($result){
			//获取sessionID
			$sessionInfo = $this->_model->getSessionIdAndUrl($result, $developerInfo['ru_name']);
			if(!$sessionInfo){
				echo $this->failureJson(array('message'=>$this->_model->getErrorMessage()));
				Yii::app()->end();
			}

			echo $this->successJson(array(
				'message' => '添加成功', 
				'url' => $sessionInfo['url'], 
				'id' => $result, 
				'sessionId'=>$sessionInfo['SessionID'])
			);

		}else{
			echo $this->failureJson(array('message'=>'添加失败'));
		}
		Yii::app()->end();
	}


	/**
	 * 修改账号
	 */
	public function actionEdit(){
		$id = Yii::app()->request->getParam('id');
		if($_POST){
			$accountParam = Yii::app()->request->getParam('PlatformEbayAccount');
			$accountParam['modify_user_id'] = (int)Yii::app()->user->id;
			$accountParam['modify_time'] = date('Y-m-d H:i:s');
			$result = $this->_model->updateData($accountParam, 'id = :id', array(':id'=>$id));
			if($result){
				$jsonData = array(
					'message' => '更改成功',
					'forward' =>'/platformaccount/platformebayaccount/list',
					'navTabId'=> 'page' . PlatformEbayAccount::getIndexNavTabId(),
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

		//获取店铺站点
		$siteList = EbaySite::getSiteList();

		//获取账号状态
		$accountStatusList = PlatformWishAccount::getAccountStatus();

		//获取所属部门
		$departmentList = EbayAccount::model()->getDepartment();

		$this->render("edit", array("model"=>$accountInfo, 'siteList'=>$siteList, 'departmentList'=>$departmentList, 'accountStatusList'=>$accountStatusList));
		Yii::app()->end();
	}


	/**
	 * 同步到market系统ebay账号表
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

		$omsIds = array();
		$developerAccountModel = new PlatformEbayDeveloperAccount();
		$ebayAccountModel = new EbayAccount();
		$accountInfos = $ebayAccountModel->getIdUserNameList();
		$omsIds = array_keys($accountInfos);
		
		//通过id查询出账号信息
		$accountArr = array();
		$wheres = "a.id IN(".MHelper::simplode($idsArr).")";
		$accountInfo = $this->_model->getDbConnection()->createCommand()
	        ->from($this->_model->tableName() . " as a")
	        ->leftJoin($developerAccountModel->tableName()." as d", "a.developer_id=d.id")
	        ->select("a.id,a.user_name,a.short_name,a.store_site,a.user_token,a.token_invalid_time,a.status,d.appid,d.devid,d.certid,d.ru_name")
	        ->where($wheres)
	    	->queryAll();
		if($accountInfo){
			foreach ($accountInfo as $accInfo) {
				$accountArr[$accInfo['id']] = array(
					'user_name'          => $accInfo['user_name'],
					'short_name'         => $accInfo['short_name'],
					'store_site'         => $accInfo['store_site'],
					'user_token'         => $accInfo['user_token'],
					'token_invalid_time' => $accInfo['token_invalid_time'],
					'status'             => $accInfo['status'],
					'appid'              => $accInfo['appid'],
					'devid'              => $accInfo['devid'],
					'certid'             => $accInfo['certid'],
					'ru_name'            => $accInfo['ru_name']
				);
			}
		}

		foreach ($idsArr as $accountID) {
			//判断账号是否存在
			if(!in_array($accountID, array_keys($accountArr))){
				continue;
			}

			$omsStatus = 0;

			if(in_array($accountID, $omsIds)){
			
				$data = array(
					'user_name'          => $accountArr[$accountID]['user_name'],
					'short_name'         => $accountArr[$accountID]['short_name'],
					'store_site'         => $accountArr[$accountID]['store_site'],
					'user_token'         => $accountArr[$accountID]['user_token'],
					'user_token_endtime' => $accountArr[$accountID]['token_invalid_time'],
					'status'             => $accountArr[$accountID]['status'],
					'appid'              => $accountArr[$accountID]['appid'],
					'devid'              => $accountArr[$accountID]['devid'],
					'certid'             => $accountArr[$accountID]['certid'],
					'ru_name'            => $accountArr[$accountID]['ru_name']
			    );
			    $result = $ebayAccountModel->getDbConnection()->createCommand()->update($ebayAccountModel->tableName(), $data, "id='{$accountID}'");
				if($result){
					$omsStatus = 1;
				}

			}else{

				$data = array(
					'id'             	 => $accountID,
					'user_name'          => $accountArr[$accountID]['user_name'],
					'store_name'         => $accountArr[$accountID]['user_name'],
					'short_name'         => $accountArr[$accountID]['short_name'],
					'store_site'         => $accountArr[$accountID]['store_site'],
					'user_token'         => $accountArr[$accountID]['user_token'],
					'user_token_endtime' => $accountArr[$accountID]['token_invalid_time'],
					'status'             => $accountArr[$accountID]['status'],
					'is_lock'            => 0,
					'appid'              => $accountArr[$accountID]['appid'],
					'devid'              => $accountArr[$accountID]['devid'],
					'certid'             => $accountArr[$accountID]['certid'],
					'ru_name'            => $accountArr[$accountID]['ru_name'],
					'paypal_group_id'   =>0,
					'email'          	=> '',
					'email_host'     	=> '',
					'email_port'     	=> '',
					'email_password' 	=> '',
					'group_id'          => 1,
					'is_eub_under5'     => 0
			    );

			    $res = $ebayAccountModel->getDbConnection()->createCommand()->insert($ebayAccountModel->tableName(), $data);
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
		ini_set("display_errors", true);
		error_reporting(E_ALL);
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
			$developerId = Yii::app()->request->getParam('developer_id');
			if(!$developerId){
				$developerId = $info['developer_id'];
			}

			$developerAccountModel = new PlatformEbayDeveloperAccount();
			$developerInfo = $developerAccountModel->findByPk($developerId);
			if(!$developerInfo['ru_name']){
				echo $this->failureJson(array('message'=>'ruName为空'));
				Yii::app()->end();
			}

			$accountParam['modify_user_id'] = (int)Yii::app()->user->id;
			$accountParam['modify_time'] = date('Y-m-d H:i:s');
			$accountParam['update_time'] = date('Y-m-d H:i:s');
			$result = $this->_model->updateData($accountParam, 'id = :id', array(':id'=>$id));
			if($result){
				//获取sessionID
				$sessionInfo = $this->_model->getSessionIdAndUrl($id, $developerInfo['ru_name']);
				if(!$sessionInfo){
					echo $this->failureJson(array('message'=>$this->_model->getErrorMessage()));
					Yii::app()->end();
				}

				echo $this->successJson(array(
					'message' => '成功', 
					'url' => $sessionInfo['url'], 
					'id' => $id, 
					'sessionId'=>$sessionInfo['SessionID'])
				);
			}else{
				echo $this->failureJson(array('message'=>'授权失败'));
			}
			Yii::app()->end();
		}

		$developerList = PlatformEbayDeveloperAccount::model()->getDeveloperAccountList();
		$this->render("authadd", array("model"=>$info, 'developerList'=>$developerList));
	}


	/**
	 * 保存获取到的token信息
	 */
	public function actionTokensave(){
		$sessionId = Yii::app()->request->getParam('sessionId');
		if(!$sessionId){
			echo $this->failureJson(array('message'=>'sessionId不能为空'));
			Yii::app()->end();
		}

		$accountID = Yii::app()->request->getParam('accountId');
		if(!$accountID){
			echo $this->failureJson(array('message'=>'账号ID不能为空'));
			Yii::app()->end();
		}

		//获取token
		$request = new FetchTokenRequest;
		$request->setAccount($accountID);
		$request->setSessionID($sessionId);
		$response = $request->setRequest()->sendRequest()->getResponse();
		if(isset($response->eBayAuthToken)){
			$expirationTimeArr = explode('.', $response->HardExpirationTime);
			$expirationTime = str_replace('T', ' ', $expirationTimeArr[0]);
			$updateArr=array(
				'user_token'         => $response->eBayAuthToken,
				'token_status'       => 1,
				'token_invalid_time' => $expirationTime
    		);
    		$flag = $this->_model->updateData($updateArr,'id = :id', array(':id'=>$accountID));
    		if($flag){
    			echo $this->successJson(array('message' => '获取token成功'));
    			Yii::app()->end();
    		}else{
    			echo $this->failureJson(array('message'=>'更新token失败'));
    			Yii::app()->end();
    		}
		}else{
			echo $this->failureJson(array('message'=>'获取token失败'));
		}
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
			//获取token
			$accountInfo = $this->_model->findByPk($accountID);
			if(!$accountInfo){
				continue;
			}
			
			$request = new GetTokenStatusRequest;
			$request->setAccount($accountID);
			$request->setEBayAuthToken($accountInfo['user_token']);
			$response = $request->setRequest()->sendRequest()->getResponse();
			if(isset($response->TokenStatus->Status) && $response->TokenStatus->Status == 'Active'){
				$tokenStatus = 1;
				$expirationTimeArr = explode('.', $response->TokenStatus->ExpirationTime);
				$expirationTime = str_replace('T', ' ', $expirationTimeArr[0]);
			}
			
			$conditions = 'id = :id';
			$params = array(':id' => $accountID);
			$this->_model->updateData(array('token_status' => $tokenStatus, 'token_invalid_time' => $expirationTime), $conditions, $params);
		}

		echo $this->successJson(array('message'=>'操作成功'));
		Yii::app()->end();
	}
}