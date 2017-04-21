<?php
class PlatformlazadaaccountController extends UebController {
	
	/**
	 * @todo lazada帐号管理列表
	 * @author hanxy
	 * @since 2017-03-07
	 */
	/** @var object  模型对象 **/
	protected $_model = null;
	
	/**
	 * (non-PHPdoc)
	 * @see CController::init()
	 */
	public function init() {
		$this->_model = new PlatformLazadaAccount();
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
		//获取站点列表
		$siteList = LazadaSite::getSiteList();

		//获取所属部门
		$departmentList = Department::model()->getDepartmentByKeywords('lazada');

		$this->render("add", array("model"=>$this->_model, 'siteList'=>$siteList, 'departmentList'=>$departmentList));
		Yii::app()->end();			
	}


	/**
	 * 保存插入数据
	 */
	public function actionAddsave(){
		$accountParam = Yii::app()->request->getParam('PlatformLazadaAccount');
		list($accountID, $shortName, $email, $siteId, $departmentID, $apiUrl, $apiKey) = array_values($accountParam);
		if(!$accountID){
			echo $this->failureJson(array('message'=>'账号名称必须选择'));
			Yii::app()->end();
		}

		if(!$shortName){
			echo $this->failureJson(array('message'=>'账号简称不能为空'));
			Yii::app()->end();
		}

		if(!$siteId){
			echo $this->failureJson(array('message'=>'站点不能为空'));
			Yii::app()->end();
		}

		$siteList = LazadaSite::getSiteList();

		//账号简称分割成数组，判断是否有站点名称和是否符合格式
		$shortNameArr = explode('-', $shortName);
		if(count($shortNameArr) != 2){
			echo $this->failureJson(array('message'=>'账号简称不正确'));
			Yii::app()->end();
		}

		if($shortNameArr[1] != $siteList[$siteId]){
			echo $this->failureJson(array('message'=>'账号简称里的站点名称不正确'));
			Yii::app()->end();
		}

		//判断账号简称是否其他账号已经存在
		$isExist = $this->_model->getOneByCondition('short_name', "short_name = '{$shortName}'");
		if($isExist){
			echo $this->failureJson(array('message'=>'此账号简称已经存在'));
			Yii::app()->end();
		}

		if(!$email){
			echo $this->failureJson(array('message'=>'邮箱不能为空'));
			Yii::app()->end();
		}

		if(!$departmentID){
			echo $this->failureJson(array('message'=>'所属部门必须选择'));
			Yii::app()->end();
		}

		if(!$apiUrl){
			echo $this->failureJson(array('message'=>'API URL不能为空'));
			Yii::app()->end();
		}

		if(!$apiKey){
			echo $this->failureJson(array('message'=>'API KEY不能为空'));
			Yii::app()->end();
		}

		$isExist = $this->_model->getOneByCondition('id',"site_id = '{$siteId}' AND short_name = '{$shortName}'");
		if($isExist){
			echo $this->failureJson(array('message'=>'此账号已存在'));
			Yii::app()->end();
		}

		$nameArr = PlatformLazadaAccount::getAccount();
		$accountParam['seller_name'] = $nameArr[$accountID];
		$accountParam['api_url'] = trim($apiUrl);
		$accountParam['api_key'] = trim($apiKey);
		$accountParam['token_status'] = 1;
		$accountParam['update_time'] = date('Y-m-d H:i:s');
		$accountParam['create_user_id'] = (int)Yii::app()->user->id;
		$accountParam['create_time'] = date('Y-m-d H:i:s');
		$accountParam['modify_user_id'] = (int)Yii::app()->user->id;
		$accountParam['modify_time'] = date('Y-m-d H:i:s');

		$result = $this->_model->insertData($accountParam);
		if($result){
			$jsonData = array(
				'message' => '添加成功',
				'forward' =>'/platformaccount/platformlazadaaccount/list',
				'navTabId'=> 'page' . PlatformLazadaAccount::getIndexNavTabId(),
				'callbackType'=>'closeCurrent'
			);
			echo $this->successJson($jsonData);
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

		$accountInfo = $this->_model->findByPk($id);
		if(!$accountInfo){
			echo $this->failureJson(array('message'=>'没有找到数据'));
			Yii::app()->end();
		}

		$siteList = LazadaSite::getSiteList();

		if($_POST){
			$accountParam = Yii::app()->request->getParam('PlatformLazadaAccount');
			//判断账号简称是否存在和是否为空
			if(!isset($accountParam['short_name']) || !$accountParam['short_name']){
				echo $this->failureJson(array('message'=>'账号简称不能为空'));
				Yii::app()->end();
			}

			//账号简称分割成数组，判断是否有站点名称和是否符合格式
			$shortNameArr = explode('-', $accountParam['short_name']);
			if(count($shortNameArr) != 2){
				echo $this->failureJson(array('message'=>'账号简称不正确'));
				Yii::app()->end();
			}

			if($shortNameArr[1] != $siteList[$accountParam['site_id']]){
				echo $this->failureJson(array('message'=>'账号简称里的站点名称不正确'));
				Yii::app()->end();
			}

			//判断账号简称是否其他账号已经存在
			$isExist = $this->_model->getOneByCondition('short_name', "short_name = '{$accountParam['short_name']}' AND id <> {$id}");
			if($isExist){
				echo $this->failureJson(array('message'=>'此账号简称已经存在'));
				Yii::app()->end();
			}

			$nameArr = PlatformLazadaAccount::getAccount();
			$accountParam['seller_name'] = $nameArr[$accountParam['account_id']];
			$accountParam['modify_user_id'] = (int)Yii::app()->user->id;
			$accountParam['modify_time'] = date('Y-m-d H:i:s');
			$result = $this->_model->updateData($accountParam, 'id = :id', array(':id'=>$id));
			if($result){
				$jsonData = array(
					'message' => '更改成功',
					'forward' =>'/platformaccount/platformlazadaaccount/list',
					'navTabId'=> 'page' . PlatformLazadaAccount::getIndexNavTabId(),
					'callbackType'=>'closeCurrent'
				);
				echo $this->successJson($jsonData);
			}else{
				echo $this->failureJson(array('message'=>'修改失败'));
			}
			Yii::app()->end();
		}

		//获取账号状态
		$accountStatusList = PlatformAccountModel::getAccountStatus();

		//获取所属部门
		$departmentList = Department::model()->getDepartmentByKeywords('lazada');

		$this->render("edit", array("model"=>$accountInfo, 'siteList'=>$siteList, 'departmentList'=>$departmentList, 'accountStatusList'=>$accountStatusList));
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

		$omsIds = array();
		$lazadaAccountModel = new LazadaAccount();
		$accountInfos = $lazadaAccountModel->getListByCondition('id','id > 0');
		foreach ($accountInfos as $infos) {
			$omsIds[] = $infos['id'];
		}
		
		//通过id查询出账号信息
		$accountArr = array();
		$accountInfo = $this->_model->getListByCondition('*',"id IN(".MHelper::simplode($idsArr).")");
		if($accountInfo){
			foreach ($accountInfo as $accInfo) {
				$accountArr[$accInfo['id']] = array(
					'short_name'     => $accInfo['short_name'],
					'email'          => $accInfo['email'],
					'seller_name'    => $accInfo['seller_name'],
					'site_id'        => $accInfo['site_id'],
					'api_key'        => $accInfo['api_key'],
					'api_url'        => $accInfo['api_url'],
					'account_id'     => $accInfo['account_id'],
					'status'         => $accInfo['status'],
					'create_user_id' => $accInfo['create_user_id'],
					'create_time'    => $accInfo['create_time'],
					'modify_user_id' => $accInfo['modify_user_id'],
					'modify_time'    => $accInfo['modify_time'],
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
					'short_name'  => $accountArr[$accountID]['short_name'],
					'email'       => $accountArr[$accountID]['email'],
					'site_id'     => $accountArr[$accountID]['site_id'],
					'seller_name' => $accountArr[$accountID]['seller_name'],
					'server_url'  => trim($accountArr[$accountID]['api_url']),
					'token'       => trim($accountArr[$accountID]['api_key']),
					'status'      => $accountArr[$accountID]['status']
			    );
			    $result = $lazadaAccountModel->getDbConnection()->createCommand()->update($lazadaAccountModel->tableName(), $data, "id='{$accountID}'");
				if($result){
					$omsStatus = 1;
				}

			}else{

				$data = array(
					'id'             => $accountID,
					'account_id'     => $accountArr[$accountID]['account_id'],
					'short_name'     => $accountArr[$accountID]['short_name'],
					'email'          => $accountArr[$accountID]['email'],
					'site_id'        => $accountArr[$accountID]['site_id'],
					'seller_name'    => $accountArr[$accountID]['seller_name'],
					'server_url'     => trim($accountArr[$accountID]['api_url']),
					'token'          => trim($accountArr[$accountID]['api_key']),
					'old_account_id' => 0,
					'is_lock'        => 0,
					'status'         => $accountArr[$accountID]['status'],
					'create_user_id' => $accountArr[$accountID]['create_user_id'],
					'create_time'    => $accountArr[$accountID]['create_time'],
					'modify_user_id' => $accountArr[$accountID]['modify_user_id'],
					'modify_time'    => $accountArr[$accountID]['modify_time']
			    );

			    $res = $lazadaAccountModel->getDbConnection()->createCommand()->insert($lazadaAccountModel->tableName(), $data);
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
			$accountParam = Yii::app()->request->getParam('PlatformLazadaAccount');
			$accountParam['modify_user_id'] = (int)Yii::app()->user->id;
			$accountParam['modify_time'] = date('Y-m-d H:i:s');
			$accountParam['update_time'] = date('Y-m-d H:i:s');
			$result = $this->_model->updateData($accountParam, 'id = :id', array(':id'=>$id));
			if($result){
				$jsonData = array(
					'message' => '更改成功',
					'forward' =>'/platformaccount/platformlazadaaccount/list',
					'navTabId'=> 'page' . PlatformLazadaAccount::getIndexNavTabId(),
					'callbackType'=>'closeCurrent'
				);
				echo $this->successJson($jsonData);
			}else{
				echo $this->failureJson(array('message'=>'修改失败'));
			}
			Yii::app()->end();
		}

		$this->render("authadd", array("model"=>$info));
	}
}