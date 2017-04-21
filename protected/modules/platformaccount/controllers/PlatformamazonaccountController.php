<?php
class PlatformamazonaccountController extends UebController {
	
	/**
	 * @todo amazon帐号管理列表
	 * @author hanxy
	 * @since 2017-03-09
	 */
	/** @var object  模型对象 **/
	protected $_model = null;
	
	/**
	 * (non-PHPdoc)
	 * @see CController::init()
	 */
	public function init() {
		$this->_model = new PlatformAmazonAccount();
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
		$siteList = PlatformAmazonAccount::getSiteList();

		//获取所属部门
		$departmentList = Department::model()->getDepartmentByKeywords('amazon');

		$this->render("add", array("model"=>$this->_model, 'siteList'=>$siteList, 'departmentList'=>$departmentList));
		Yii::app()->end();			
	}


	/**
	 * 保存插入数据
	 */
	public function actionAddsave(){
		$accountParam = Yii::app()->request->getParam('PlatformAmazonAccount');
		list($accountName, $departmentID, $countryCode, $merchantId, $accessKey, $secretKey) = array_values($accountParam);
		if(!$accountName){
			echo $this->failureJson(array('message'=>'账号名称不能为空'));
			Yii::app()->end();
		}

		if(!$departmentID){
			echo $this->failureJson(array('message'=>'所属部门必须选择'));
			Yii::app()->end();
		}

		if(!$countryCode){
			echo $this->failureJson(array('message'=>'站点不能为空'));
			Yii::app()->end();
		}

		if(!$merchantId){
			echo $this->failureJson(array('message'=>'Merchant ID不能为空'));
			Yii::app()->end();
		}

		if(!$accessKey){
			echo $this->failureJson(array('message'=>'AWS Access Key ID不能为空'));
			Yii::app()->end();
		}

		if(!$secretKey){
			echo $this->failureJson(array('message'=>'Secret Key不能为空'));
			Yii::app()->end();
		}

		$isExist = $this->_model->getOneByCondition('id',"country_code = '{$countryCode}' AND account_name = '{$accountName}'");
		if($isExist){
			echo $this->failureJson(array('message'=>'此账号已存在'));
			Yii::app()->end();
		}

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
				'forward' =>'/platformaccount/platformamazonaccount/list',
				'navTabId'=> 'page' . PlatformAmazonAccount::getIndexNavTabId(),
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
		if($_POST){
			$accountParam = Yii::app()->request->getParam('PlatformAmazonAccount');
			$accountParam['modify_user_id'] = (int)Yii::app()->user->id;
			$accountParam['modify_time'] = date('Y-m-d H:i:s');
			$result = $this->_model->updateData($accountParam, 'id = :id', array(':id'=>$id));
			if($result){
				$jsonData = array(
					'message' => '更改成功',
					'forward' =>'/platformaccount/platformamazonaccount/list',
					'navTabId'=> 'page' . PlatformAmazonAccount::getIndexNavTabId(),
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

		$siteList = PlatformAmazonAccount::getSiteList();

		//获取账号状态
		$accountStatusList = PlatformAmazonAccount::getAccountStatus();

		//获取所属部门
		$departmentList = Department::model()->getDepartmentByKeywords('amazon');

		$this->render("edit", array("model"=>$accountInfo, 'siteList'=>$siteList, 'accountStatusList'=>$accountStatusList, 'departmentList'=>$departmentList));
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
		$amazonAccountModel = new AmazonAccount();
		$accountInfos = $amazonAccountModel->getListByCondition('id','id > 0');
		foreach ($accountInfos as $infos) {
			$omsIds[] = $infos['id'];
		}
		
		//通过id查询出账号信息
		$accountArr = array();
		$accountInfo = $this->_model->getListByCondition('*',"id IN(".MHelper::simplode($idsArr).")");
		if($accountInfo){
			foreach ($accountInfo as $accInfo) {
				$accountArr[$accInfo['id']] = array(
					'account_name'    => $accInfo['account_name'],
					'country_code'    => $accInfo['country_code'],
					'site_domain'     => $accInfo['site_domain'],
					'service_url'     => $accInfo['service_url'],
					'merchant_id'     => $accInfo['merchant_id'],
					'market_place_id' => $accInfo['market_place_id'],
					'access_key'      => $accInfo['access_key'],
					'secret_key'      => $accInfo['secret_key'],
					'department_id'   => isset($accInfo['department_id'])?$accInfo['department_id']:5,
					'status'          => $accInfo['status'],
					'create_user_id'  => $accInfo['create_user_id'],
					'create_time'     => $accInfo['create_time'],
					'modify_user_id'  => $accInfo['modify_user_id'],
					'modify_time'     => $accInfo['modify_time'],
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
					'account_name'    => $accountArr[$accountID]['account_name'],
					'status'          => $accountArr[$accountID]['status'],
					'country_code'    => $accountArr[$accountID]['country_code'],
					'site_domain'     => $accountArr[$accountID]['site_domain'],
					'service_url'     => $accountArr[$accountID]['service_url'],
					'merchant_id'     => $accountArr[$accountID]['merchant_id'],
					'market_place_id' => $accountArr[$accountID]['market_place_id'],
					'access_key'      => $accountArr[$accountID]['access_key'],
					'secret_key'      => $accountArr[$accountID]['secret_key'],
					'department_id'   => $accountArr[$accountID]['department_id']
			    );
			    $result = $amazonAccountModel->getDbConnection()->createCommand()->update($amazonAccountModel->tableName(), $data, "id='{$accountID}'");
				if($result){
					$omsStatus = 1;
				}

			}else{

				$data = array(
					'id'              => $accountID,
					'account_name'    => $accountArr[$accountID]['account_name'],
					'status'          => $accountArr[$accountID]['status'],
					'country_code'    => $accountArr[$accountID]['country_code'],
					'site_domain'     => $accountArr[$accountID]['site_domain'],
					'service_url'     => $accountArr[$accountID]['service_url'],
					'merchant_id'     => $accountArr[$accountID]['merchant_id'],
					'market_place_id' => $accountArr[$accountID]['market_place_id'],
					'access_key'      => $accountArr[$accountID]['access_key'],
					'secret_key'      => $accountArr[$accountID]['secret_key'],
					'create_user_id'  => $accountArr[$accountID]['create_user_id'],
					'create_time'     => $accountArr[$accountID]['create_time'],
					'department_id'   => $accountArr[$accountID]['department_id']
			    );

			    $res = $amazonAccountModel->getDbConnection()->createCommand()->insert($amazonAccountModel->tableName(), $data);
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
			$accountParam = Yii::app()->request->getParam('PlatformAmazonAccount');
			$accountParam['modify_user_id'] = (int)Yii::app()->user->id;
			$accountParam['modify_time'] = date('Y-m-d H:i:s');
			$accountParam['update_time'] = date('Y-m-d H:i:s');
			$result = $this->_model->updateData($accountParam, 'id = :id', array(':id'=>$id));
			if($result){
				$jsonData = array(
					'message' => '更改成功',
					'forward' =>'/platformaccount/platformamazonaccount/list',
					'navTabId'=> 'page' . PlatformAmazonAccount::getIndexNavTabId(),
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