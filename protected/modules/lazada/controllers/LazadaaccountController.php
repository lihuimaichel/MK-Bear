<?php
class LazadaaccountController extends UebController {
	
	/**
	 * @todo lazada帐号管理列表
	 * @author tony
	 * @since 2015/08/18
	 */
	public function actionList(){
		$model = UebModel::model('LazadaAccount');
		$this->render('list',array('model'=>$model));
	}
	
	/**
	 * @desc 冻结账号
	 */
	public function actionLockaccount(){
		$lazadaAccountModel = UebModel::model('LazadaAccount');
		$lock = $lazadaAccountModel::STATUS_ISLOCK;//账号状态为冻结的参数
		if (Yii::app()->request->isAjaxRequest && isset($_REQUEST['ids'])) {
			try {
				foreach (explode(',',$_REQUEST['ids']) as $lazadaAccount){
					//获取账号信息
					$data = $lazadaAccountModel->getAccountInfoById($lazadaAccount);
					if($data['is_lock'] == $lock){
						$jsonData = array(
								'message' => Yii::t('system', 'the chosen account has been haven account locked'),
						);
						echo $this->failureJson($jsonData);
						die;
					}
					//需改账号状态变为冻结状态
					$flag=$lazadaAccountModel->LockAccount($lazadaAccount);
				}
			} catch (Exception $e) {
				$flag = false;
			}
		}
		if($flag){
			$jsonData = array(
					'message' => Yii::t('system', 'Lock Success'),
					'navTabId' => 'page' . $lazadaAccountModel->getIndexNavTabId(),
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
		$lazadaAccountModel = UebModel::model('LazadaAccount');
		$unLock = $lazadaAccountModel::STATUS_NOTLOCK;//账号状态为未冻结的参数
		if (Yii::app()->request->isAjaxRequest && isset($_REQUEST['ids'])) {
			try {
				foreach (explode(',',$_REQUEST['ids']) as $lazadaAccount){
					//获取账号基本信息
					$data = $lazadaAccountModel->getAccountInfoById($lazadaAccount);
					if($data['is_lock'] == $unLock){
						$jsonData = array(
								'message' => Yii::t('system', 'the chosen account has been haven account unlock'),
						);
						echo $this->failureJson($jsonData);
						die;
					}
					//修改账号状态为未冻结
					$flag=$lazadaAccountModel->unLockAccount($lazadaAccount);
				}
			} catch (Exception $e) {
				$flag = false;
			}
		}
		if($flag){
			$jsonData = array(
					'message' => Yii::t('system', 'Unlock Success'),
					'navTabId' => 'page' . $lazadaAccountModel->getIndexNavTabId(),
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
		$lazadaAccountModel = UebModel::model('LazadaAccount');
		$shutDown = $lazadaAccountModel::STATUS_SHUTDOWN;//获取账号状态为关闭的参数
		if (Yii::app()->request->isAjaxRequest && isset($_REQUEST['ids'])) {
			try {
				foreach (explode(',',$_REQUEST['ids']) as $lazadaAccount){
					//获取账号基本信息
					$data = $lazadaAccountModel->getAccountInfoById($lazadaAccount);
					if($data['status'] == $shutDown){
						$jsonData = array(
								'message' => Yii::t('system', 'the chosen account has been haven account closed'),
						);
						echo $this->failureJson($jsonData);
						die;
					}
					//修改账号状态为关闭
					$flag=$lazadaAccountModel->shutDownAccount($lazadaAccount);
				}
			} catch (Exception $e) {
				$flag = false;
			}
		}
		if($flag){
			$jsonData = array(
					'message' => Yii::t('system', 'ShutDown Success'),
					'navTabId' => 'page' . $lazadaAccountModel->getIndexNavTabId(),
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
		$lazadaAccountModel = UebModel::model('LazadaAccount');
		$open = $lazadaAccountModel::STATUS_OPEN;//获取账号状态为开启的参数
		if (Yii::app()->request->isAjaxRequest && isset($_REQUEST['ids'])) {
			try {
				foreach (explode(',',$_REQUEST['ids']) as $lazadaAccount){
					//获取账号基本信息
					$data = $lazadaAccountModel->getAccountInfoById($lazadaAccount);
					if($data['status'] == $open){
						$jsonData = array(
								'message' => Yii::t('order', 'the chosen account has been haven account opened'),
						);
						echo $this->failureJson($jsonData);
						die;
					}
					//修改账号状态为开启
					$flag=$lazadaAccountModel->openAccount($lazadaAccount);
				}
			} catch (Exception $e) {
				$flag = false;
			}
		}
		if($flag){
			$jsonData = array(
					'message' => Yii::t('system', 'Open Success'),
					'navTabId' => 'page' . $lazadaAccountModel->getIndexNavTabId(),
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
	public function actionUpdateaccount(){
		$id = $_REQUEST['id'];
		$model = $this->loadModel($id);
		$this->render('_tabs',array('model' => $model));
	}
	
	/**
	 * @desc 编辑账号基本信息
	 */
	public function actionBasic(){
		$id = $_REQUEST['id'];
		$model = $this->loadModel($id);
		$data = $_REQUEST['LazadaAccount'];
		
		if (Yii::app()->request->isAjaxRequest && isset($data)) {
			$lazadaAccountModle = new LazadaAccount();
			if($lazadaAccountModle->validate()){
				$flag = $lazadaAccountModle->updateAccount($data,$model->id);
				if($flag){
					$jsonData = array(
							'message' => Yii::t('system', 'Update Success'),
							'forward' => '/lazada/lazadaaccount/list',
							'navTabId' => 'page' . $lazadaAccountModle->getIndexNavTabId(),
							'callbackType' => 'closeCurrent'
					);
				}
					echo $this->successJson($jsonData);
			}else{
				$flag = false;
			}
			if(!$flag){
				echo $this->failureJson(array('message'=> Yii::t('system', 'save Failed')));
			}
			Yii::app()->end();
		}
		$this->render('basic',array('model' => $model));
	}
	
	/**
	 * @desc 编辑账号基本信息
	 */
	public function actionView($id){
		$accountID = $id;
		$model = UebModel::model('LazadaAccountConfig')->loadModelByaccountID($accountID);
		$data = $_REQUEST['LazadaAccountConfig'];
// 		if(Yii::app()->request->isAjaxRequest && isset($data)){
		$lazadaAccountConfigModel = new LazadaAccountConfig();
		$tag = $lazadaAccountConfigModel->validate();
		if (isset($data)) {
			if(isset($data['publish_count'])){
				$lazadaAccountConfigModel->setAttribute('account_id',$accountID);
				$lazadaAccountConfigModel->setAttribute('config_type', lazadaAccountConfig::PUBLISH_COUNT);
				$lazadaAccountConfigModel->setAttribute('config_value',$data['publish_count']);
				if($tag){
					$publish_Account_Flag = $lazadaAccountConfigModel->save();
				}
			}
			if(isset($data['adjust_count'])){
				$lazadaAccountConfigModel = new LazadaAccountConfig();
				$lazadaAccountConfigModel->setAttribute('account_id',$accountID);
				$lazadaAccountConfigModel->setAttribute('config_type', lazadaAccountConfig::IF_ADJUST_COUNT);
				$lazadaAccountConfigModel->setAttribute('config_value',$data['adjust_count']);
				if($tag){
					$adjust_Flag = $lazadaAccountConfigModel->save();
				}
			}
			if(isset($data['publish_site_id'])){
				$lazadaAccountConfigModel = new LazadaAccountConfig();
				$lazadaAccountConfigModel->setAttribute('account_id',$accountID);
				$lazadaAccountConfigModel->setAttribute('config_type', lazadaAccountConfig::PUBLISH_SITE_ID);
				$lazadaAccountConfigModel->setAttribute('config_value',$data['publish_site_id']);
				if($tag){
					$publish_Site_Flag = $lazadaAccountConfigModel->save();
				}
			}
			if($publish_Account_Flag ||$adjust_Flag||$publish_Site_Flag){
				$jsonData = array(
						'message' => Yii::t('system', 'Save successful'),
						'forward' => '/systems/lazadaaccount/list',
						'navTabId' => 'page' . UebModel::model('LazadaAccount')->getIndexNavTabId(),
						'callbackType' => 'closeCurrent'
				);
				 echo $this->successJson($jsonData);
			}else{
           		 $flag = false;
           	}
            if (!$flag){
                echo $this->failureJson(array(
                    'message' => Yii::t('system', 'Save failure')));
            }
	    Yii::app()->end();
	    }
		$this->render('view',array('model' => $model));
	}
	
	/**
	 * @desc 获取账号基本信息
	 */
	public function loadModel($id){
		$model = UebModel::model('LazadaAccount')->findByPk($id);
		if($model===false){
			throw new CHttpException ( 404, Yii::t ( 'app', 'The requested page does not exist.' ) );
		}else{
			return $model;
		}
	}
	
	public function actionGetaccountlist() {
		$siteID = Yii::app()->request->getParam('site_id');
		$accountList = LazadaAccount::model()->getAccountList($siteID);
		echo CHtml::listOptions('1', $accountList);
		Yii::app()->end();
	}
	
	/**
	 * 根据old_account_id得到卖家账号
	 */
	public function actionGetsellername() {
		$oldAccountId = $_GET['old_account_id'];
		if (empty($oldAccountId)) {
			exit('');
		}
		$accountInfo = LazadaAccount::model()->getAccountByOldAccountID($oldAccountId);
		if ($accountInfo) {
			echo $accountInfo['seller_name'];
		}
	}


	/**
	 * 同步到OMS
	 * /lazada/lazadaaccount/tooms/id/37
	 */
	public function actionTooms(){
		set_time_limit(1200);
		ini_set("display_errors", true);
		error_reporting(E_ALL);

		$ID = Yii::app()->request->getParam('id');

		$omsLazadaAccountModel = new OmsLazadaAccount();
		$omsIds = $omsLazadaAccountModel->getOmsLazadaAccountID();
		
		//通过id查询出账号信息
		$wheres = "id > 0";
		if($ID){
			$wheres = "id = ".$ID;
		}
		$accountArr = array();
		$lazadaAccountModel = new LazadaAccount();
		$accountInfo = $lazadaAccountModel->getListByCondition('*',$wheres);
		foreach ($accountInfo as $accInfo) {
			$oldAccountId = $accInfo['old_account_id'];

			$data = array(
				'seller_name' => $accInfo['short_name'],
				'email'       => $accInfo['email'],
				'site_id'     => $accInfo['site_id'],
				'service_url' => $accInfo['server_url'],
				'token'       => $accInfo['token']
		    );

			if(in_array($oldAccountId, $omsIds)){
			    $omsLazadaAccountModel->updateData($data, "id=:id", array(':id'=>$oldAccountId));
			    echo $accInfo['id'].'--更新成功<br>';
			}else{
				$data['is_lock']        = $accInfo['is_lock'];
				$data['create_user_id'] = $accInfo['create_user_id'];
				$data['create_time']    = $accInfo['create_time'];
				$data['modify_user_id'] = $accInfo['modify_user_id'];
				$data['modify_time']    = $accInfo['modify_time'];
				$result = $omsLazadaAccountModel->insertData($data);
				if($result){
					$lazadaAccountModel->getDbConnection()->createCommand()->update(
						$lazadaAccountModel->tableName(), 
						array('old_account_id' => $result), 
						'id = :id', 
						array('id' => $accInfo['id'])
					);
					echo $accInfo['id'].'--添加成功<br>';
				}
			}

		}

		Yii::app()->end();
	}
	

	/**
	 * @desc 开启自动调价
	 */
	public function actionOpenchangeprice(){
		$lazadaAccountModel = new LazadaAccount();
		$status = LazadaAccount::OPEN_CHANGE_PRICE;
		if (Yii::app()->request->isAjaxRequest && isset($_REQUEST['ids'])) {
			foreach (explode(',',$_REQUEST['ids']) as $lazadaAccount){
				$flag = $lazadaAccountModel->changePriceStatus($lazadaAccount, $status);
			}
		}

		$jsonData = array(
				'message' => '开启自动调价成功',
				'navTabId' => 'page' . $lazadaAccountModel->getIndexNavTabId(),
		);
		echo $this->successJson($jsonData);
		Yii::app()->end();
	}


	/**
	 * @desc 关闭自动调价
	 */
	public function actionClosechangeprice(){
		$lazadaAccountModel = new LazadaAccount();
		$status = LazadaAccount::CLOSE_CHANGE_PRICE;
		if (Yii::app()->request->isAjaxRequest && isset($_REQUEST['ids'])) {
			foreach (explode(',',$_REQUEST['ids']) as $lazadaAccount){
				$flag = $lazadaAccountModel->changePriceStatus($lazadaAccount, $status);
			}
		}

		$jsonData = array(
				'message' => '关闭自动调价成功',
				'navTabId' => 'page' . $lazadaAccountModel->getIndexNavTabId(),
		);
		echo $this->successJson($jsonData);
		Yii::app()->end();
	}
}