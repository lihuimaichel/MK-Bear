<?php
/**
 * 
 * @author qzz
 *
 */
class EbayaccountpaypalgroupController extends UebController{

	/*
	 * 列表
	 */
	public function actionList(){
		$model = new EbayAccountPaypalGroup();
		$this->render("list", array("model"	=>$model));
	}
	/*
	 * 添加
	 */
	public function actionAdd(){

		$model = UebModel::model("EbayAccountPaypalGroup");
		if(Yii::app()->request->isAjaxRequest && isset($_POST['EbayAccountPaypalGroup'])){

			if(empty($_POST['EbayAccountPaypalGroup']['group_name'])){
				echo $this->failureJson(array('message' => '请输入分组名'));
				die;
			}
			$gname = $_POST['EbayAccountPaypalGroup']['group_name'];
			$hasName = $model->getOneByCondition('group_name',"group_name = '{$gname}'");
			if($hasName){
				echo $this->failureJson(array('message' => '分组名重复'));
				die;
			}
			if(!isset($_POST['account_paypal']) || !isset($_POST['amount_start']) || !isset($_POST['amount_end'])){
				echo $this->failureJson(array('message' => '请输入paypal帐号和规则'));
				die;
			}

			$accountPaypal = $_POST['account_paypal'];
			$amountStart = $_POST['amount_start'];
			$amountEnd = $_POST['amount_end'];

			$model->attributes = $_POST['EbayAccountPaypalGroup'];
			$userId = Yii::app()->user->id;
			$model->setAttribute('create_user_id', $userId);
			$model->setAttribute('add_time', date('Y-m-d H:i:s'));
			if ($model->validate()) {
				$model->setIsNewRecord(true);
				$flag = $model->save();
				$group_id = $model->id;
				if($flag){
					foreach($accountPaypal as $key=>$value){
						$data = array(
							'group_id' => $group_id,
							'paypal_id' => $value,
							'amount_start' => str_replace(' ', '', $amountStart[$key]),
							'amount_end' =>	$amountEnd[$key]>999999 ? 999999.99 : str_replace(' ', '', $amountEnd[$key]),
						);
						EbayGroupRuleRelation::model()->saveData($data);
					}
				}
				if ($flag) {
					$forward = Yii::app()->createUrl("ebay/ebayaccountpaypalgroup/list");
					$jsonData = array(
						'message' => Yii::t('system', 'Save successful'),
						'forward' => $forward,
						'navTabId' => 'page'.Menu::model()->getIdByUrl('/ebay/ebayaccountpaypalgroup/list'),
						'callbackType' => 'closeCurrent'
					);
					echo $this->successJson($jsonData);
				}
			}else{
				$flag = false;
			}

			if (!$flag) {
				echo $this->failureJson(array('message' => Yii::t('system', 'Save failure')));
			}
			Yii::app()->end();

		}
		$paypalList = PaypalAccount::getPaypalList(Platform::CODE_EBAY);
		$this->render("add", array("data"=>array('model'=>$model,'paypalList'=>$paypalList,'paypalRule'=>array())));
	}
	/*
	 * 修改
	 */
	public function actionUpdate(){
		$id = Yii::app()->request->getParam('id');
		$model = UebModel::model("EbayAccountPaypalGroup")->findByPk($id);
		if(Yii::app()->request->isAjaxRequest && isset($_POST['EbayAccountPaypalGroup'])){
			if(empty($_POST['EbayAccountPaypalGroup']['group_name'])){
				echo $this->failureJson(array('message' => '请输入分组名'));
				die;
			}
			$gname = $_POST['EbayAccountPaypalGroup']['group_name'];
			$hasName = $model->getOneByCondition('group_name',"group_name = '{$gname}' and id <> {$id}");
			if($hasName){
				echo $this->failureJson(array('message' => '分组名重复'));
				die;
			}
			if(!isset($_POST['account_paypal']) || !isset($_POST['amount_start']) || !isset($_POST['amount_end'])){
				echo $this->failureJson(array('message' => '请输入paypal帐号和规则'));
				die;
			}
			//修改为禁用的时候，查询是否有账户用了此规则组
			if($_POST['EbayAccountPaypalGroup']['status'] == 0){
				$hasAccount = $model->getEbayAccountList($id);
				if($hasAccount){
					echo $this->failureJson(array('message' => '请先移除该分组下的ebay帐号'));
					die;
				}
			}

			$accountPaypal = $_POST['account_paypal'];
			$amountStart = $_POST['amount_start'];
			$amountEnd = $_POST['amount_end'];

			$model->attributes = $_POST['EbayAccountPaypalGroup'];
			$userId = Yii::app()->user->id;
			$model->setAttribute('create_user_id', $userId);
			$model->setAttribute('add_time', date('Y-m-d H:i:s'));
			if ($model->validate()) {
				$flag = $model->save();
				$group_id = $model->id;

				if($flag){
					//删除关系
					EbayGroupRuleRelation::model()->deleteAll("group_id={$group_id}");
					foreach($accountPaypal as $key=>$value){
						$data = array(
							'group_id' => $group_id,
							'paypal_id' => $value,
							'amount_start' => str_replace(' ', '', $amountStart[$key]),
							'amount_end' =>	$amountEnd[$key]>999999 ? 999999.99 : str_replace(' ', '', $amountEnd[$key]),
						);
						EbayGroupRuleRelation::model()->saveData($data);
					}
				}
				if ($flag) {
					$forward = Yii::app()->createUrl("ebay/ebayaccountpaypalgroup/list");
					$jsonData = array(
						'message' => Yii::t('system', 'Save successful'),
						'forward' => $forward,
						'navTabId' => 'page'.Menu::model()->getIdByUrl('/ebay/ebayaccountpaypalgroup/list'),
						'callbackType' => 'closeCurrent'
					);
					echo $this->successJson($jsonData);
				}
			}else{
				$flag = false;
			}

			if (!$flag) {
				echo $this->failureJson(array('message' => Yii::t('system', 'Save failure')));
			}
			Yii::app()->end();
		}

		$paypalList = PaypalAccount::getPaypalList(Platform::CODE_EBAY);
		$paypalRule = EbayGroupRuleRelation::model()->getListByGroupId($id);
		$paypalRule = MHelper::custom_array_sort($paypalRule,'amount_start');//排序
		$this->render("update", array("data"=>array('model'=>$model,'paypalList'=>$paypalList,'paypalRule'=>$paypalRule)));
	}

	/*
	 * 添加帐号
	 */
	public function actionAddaccount(){

		$id = Yii::app()->request->getParam('id');

		if(Yii::app()->request->isAjaxRequest && isset($_POST['group_select_account'])){
			try{
				$account = Yii::app()->request->getParam('account');
				if(empty($account)){
					throw new Exception('请选择帐号');
				}
				//更新操作
				$ebayAccountModel = new EbayAccount();
				$updateData = array('paypal_group_id'=>$id);
				$res = $ebayAccountModel->getDbConnection()->createCommand()
					->update($ebayAccountModel->tableName(), $updateData, "id IN (".MHelper::simplode($account).")");

				if ($res) {
					$forward = Yii::app()->createUrl("ebay/ebayaccountpaypalgroup/list");
					$jsonData = array(
						'message' => Yii::t('system', 'Save successful'),
						'forward' => $forward,
						'navTabId' => 'page'.Menu::model()->getIdByUrl('/ebay/ebayaccountpaypalgroup/list'),
						'callbackType' => 'closeCurrent'
					);
					echo $this->successJson($jsonData);
				}else{
					throw new Exception(Yii::t('system', 'Save failure'));
				}

			}catch (Exception $e) {
				echo $this->failureJson(array('message' => $e->getMessage()));
			}
			Yii::app()->end();
		}

		$ebayPayPalGroupModel = new EbayAccountPaypalGroup();
		$ebayAccountModel = new EbayAccount();

		//获取当前帐号信息
		$currentPayPalList = $ebayAccountModel->getListByCondition("id,short_name",'paypal_group_id = '.$id);
		$currentInfo = array();

		//获取所有组与帐号的关系
		$groupList = $ebayPayPalGroupModel->getListByCondition("id,group_name");
		$groupIdArr = array();
		foreach($groupList as $k=>$group){
			$groupIdArr[] = $group['id'];
			if($group['id']==$id){
				$currentInfo = $groupList[$k];
				unset($groupList[$k]);//释放当前组的
				continue;
			}
			$accountPayPalList = $ebayAccountModel->getListByCondition("id,short_name",'paypal_group_id = '.$group['id']);
			$groupList[$k]['account_list'] = $accountPayPalList;
		}

		//获取未选择组的帐号
		$noPayPalList = $ebayAccountModel->getListByCondition("id,short_name",'paypal_group_id = 0 or paypal_group_id NOT IN ('.MHelper::simplode($groupIdArr).')');
		$this->render(
			"addaccount",
			array(
				'model'=>$ebayPayPalGroupModel,
				'currentInfo'=>$currentInfo,
				'currentPayPalList'=>$currentPayPalList,
				'groupList'=>$groupList,
				'noPayPalList'=>$noPayPalList
			)
		);
	}
}