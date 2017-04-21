<?php
/**
 * @desc amazon账号控制器
 * @author qzz
 * @since 20170124
 *
 */
class AmazonaccountController extends UebController {


	public function actionList(){
		$model = UebModel::model('AmazonAccount');
		$this->render('list',array('model'=>$model));
	}


	/**
	 * @desc 设置部门
	 */
	public function actionSetdepartment(){
		$ids = Yii::app()->request->getParam('ids');
		$amazonAccountModel = new AmazonAccount();
		if($_POST){
			$departmentArr = Yii::app()->request->getParam('AmazonAccount');
			$departmentID  = $departmentArr['department_id'];
			if(!$ids){
				echo $this->failureJson(array('message'=>'请选择'));
				exit;
			}

			//判断部门
			if(!in_array($departmentID, array(AmazonAccount::DEPARTMENT_SHENZHEN,AmazonAccount::DEPARTMENT_CHANGSHA))){
				echo $this->failureJson(array('message'=>'请选择部门'));
				exit;
			}

			$updateArr = array(
				'department_id' => $departmentID
			);
			$flag = $amazonAccountModel->getDbConnection()->createCommand()->update($amazonAccountModel::tableName(), $updateArr, "id IN(".$ids.")");
			if(!$flag){
				echo $this->failureJson(array('message'=>'设置部门失败'));
				exit;
			}

			$jsonData = array(
				'message' => '设置成功',
				'forward' =>'/amazon/amazonaccount/list',
				'navTabId'=> 'page' . AmazonAccount::getIndexNavTabId(),
				'callbackType'=>'closeCurrent'
			);
			echo $this->successJson($jsonData);

		}else{
			$departmentList = $amazonAccountModel->getDepartment();
			$this->render('setdepartment',array('model' => $amazonAccountModel, 'departmentList'=>$departmentList, 'ids'=>$ids));
		}
	}

	/**
	 * @desc 关闭账号
	 */
	public function actionShutdownaccount(){
		$models =  new AmazonAccount();
		$shutDown = AmazonAccount::STATUS_SHUTDOWN;//获取账号状态为关闭的参数
		if (Yii::app()->request->isAjaxRequest && isset($_REQUEST['ids'])) {
			try {
				foreach (explode(',',$_REQUEST['ids']) as $amazonAccount){
					//获取账号基本信息
					$data = $models->getAccountInfoById($amazonAccount);
					if($data['status'] == $shutDown){
						$jsonData = array(
							'message' => Yii::t('system', 'the chosen account has been haven account closed'),
						);
						echo $this->failureJson($jsonData);
						die;
					}
					//修改账号状态为关闭
					$flag=$models->shutDownAccount($amazonAccount);
				}
			} catch (Exception $e) {
				$flag = false;
			}
		}
		if($flag){
			$jsonData = array(
				'message' =>Yii::t('system', 'ShutDown Success'),
				'forward' =>'/amazon/amazonaccount/list',
				'navTabId'=>'page' . AmazonAccount::getIndexNavTabId(),
				//'callbackType'=>'closeCurrent'
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
		$models =  new AmazonAccount();
		$open = AmazonAccount::STATUS_OPEN;//获取账号状态为开启的参数
		if (Yii::app()->request->isAjaxRequest && isset($_REQUEST['ids'])) {
			try {
				foreach (explode(',',$_REQUEST['ids']) as $amazonAccount){
					//获取账号基本信息
					$data = $models->getAccountInfoById($amazonAccount);
					if($data['status'] == $open){
						$jsonData = array(
							'message' => Yii::t('order', 'the chosen account has been haven account opened'),
						);
						echo $this->failureJson($jsonData);
						die;
					}
					//修改账号状态为开启
					$flag=$models->openAccount($amazonAccount);
				}
			} catch (Exception $e) {
				$flag = false;
			}
		}
		if($flag){
			$jsonData = array(
				'message' =>Yii::t('system', 'Open Success'),
				'forward' =>'/amazon/amazonaccount/list',
				'navTabId'=>'page' . AmazonAccount::getIndexNavTabId(),
				//'callbackType'=>'closeCurrent'
			);
			echo $this->successJson($jsonData);
		}else{
			echo $this->failureJson(array('message'=> Yii::t('system', 'Open Failed')));
		}
		Yii::app()->end();
	}

	/**
	 * @desc 冻结账号
	 */
	public function actionLockaccount(){
		$models =  new AmazonAccount();
		$lock = AmazonAccount::STATUS_ISLOCK;//账号状态为冻结的参数
		if (Yii::app()->request->isAjaxRequest && isset($_REQUEST['ids'])) {
			try {
				foreach (explode(',',$_REQUEST['ids']) as $amazonAccount){
					//获取账号信息
					$data = $models->getAccountInfoById($amazonAccount);
					if($data['is_lock'] == $lock){
						$jsonData = array(
							'message' => Yii::t('system', 'the chosen account has been haven account locked'),
						);
						echo $this->failureJson($jsonData);
						die;
					}
					//需改账号状态变为冻结状态
					$flag=$models->LockAccount($amazonAccount);
				}
			} catch (Exception $e) {
				$flag = false;
			}
		}
		if($flag){
			$jsonData = array(
				'message' =>Yii::t('system', 'Lock Success'),
				'forward' =>'/amazon/amazonaccount/list',
				'navTabId'=>'page' . AmazonAccount::getIndexNavTabId(),
				//'callbackType'=>'closeCurrent'
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
		$models =  new AmazonAccount();
		$unLock = AmazonAccount::STATUS_NOTLOCK;//账号状态为未冻结的参数
		if (Yii::app()->request->isAjaxRequest && isset($_REQUEST['ids'])) {
			try {
				foreach (explode(',',$_REQUEST['ids']) as $amazonAccount){
					//获取账号基本信息
					$data = $models->getAccountInfoById($amazonAccount);
					if($data['is_lock'] == $unLock){
						$jsonData = array(
							'message' => Yii::t('system', 'the chosen account has been haven account unlock'),
						);
						echo $this->failureJson($jsonData);
						die;
					}
					//修改账号状态为未冻结
					$flag=$models->unLockAccount($amazonAccount);
				}
			} catch (Exception $e) {
				$flag = false;
			}
		}
		if($flag){
			$jsonData = array(
				'message' =>Yii::t('system', 'Unlock Success'),
				'forward' =>'/amazon/amazonaccount/list',
				'navTabId'=>'page' . AmazonAccount::getIndexNavTabId(),
				//'callbackType'=>'closeCurrent'
			);
			echo $this->successJson($jsonData);
		}else{
			echo $this->failureJson(array('message'=> Yii::t('system', 'Unlock Failed')));
		}
		Yii::app()->end();
	}


	/**
	 * 同步到OMS
	 * /amazon/amazonaccount/tooms/id/37
	 */
	public function actionTooms(){
		set_time_limit(1200);
		ini_set("display_errors", true);
		error_reporting(E_ALL);

		$ID = Yii::app()->request->getParam('id');

		$omsAmazonAccountModel = new OmsAmazonAccount();
		$omsIds = $omsAmazonAccountModel->getOmsAmazonAccountID();
		
		//通过id查询出账号信息
		$wheres = "id > 0";
		if($ID){
			$wheres = "id = ".$ID;
		}
		$accountArr = array();
		$amazonAccountModel = new AmazonAccount();
		$accountInfo = $amazonAccountModel->getListByCondition('*',$wheres);
		foreach ($accountInfo as $accInfo) {

			$data = array(
				'account_name'      => $accInfo['account_name'],
				'short_name'        => $accInfo['account_name'],
				'merchant_id'       => $accInfo['merchant_id'],
				'market_place_id'   => $accInfo['market_place_id'],
				'aws_access_key_id' => $accInfo['access_key'],
				'secret_key'        => $accInfo['secret_key'],
				'service_url'       => $accInfo['service_url'],
				'site'              => $accInfo['country_code'],
				'site_domain'       => $accInfo['site_domain']
		    );

			if(in_array($accInfo['id'], $omsIds)){
			    $omsAmazonAccountModel->updateData($data, "id=:id", array(':id'=>$accInfo['id']));
			    echo $accInfo['id'].'--更新成功<br>';
			}else{
				$data['is_show']   = 1;
				$data['id']        = $accInfo['id'];
				$result = $omsAmazonAccountModel->insertData($data);
				if($result){
					echo $accInfo['id'].'--插入成功<br>';
				}
			}

		}

		Yii::app()->end();
	}
}