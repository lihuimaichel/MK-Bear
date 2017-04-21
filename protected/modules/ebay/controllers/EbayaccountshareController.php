<?php
class EbayaccountshareController extends UebController
{
	
	/**
	 * 帐号共享列表页
	 */
	public function actionList()
	{
 		$model = UebModel::model('EbayAccountShare');
		$this->render('list', array(
				'model' => $model,
		));
	}

	/**
	 * 添加
	 */
	public function actionAdd(){
		$model = UebModel::model('EbayAccountShare');

		if(Yii::app()->request->isAjaxRequest && isset($_POST['eaby_account_share'])){
			try{
				$account = Yii::app()->request->getParam('ebay_account');
				$shareTime = Yii::app()->request->getParam('share_time');
				$departmentId = Yii::app()->request->getParam('department_id');
				$sellerIds = Yii::app()->request->getParam('ebay_seller_id');

				if(empty($account)){
					throw new Exception('请选择帐号');
				}
				if(empty($shareTime)){
					throw new Exception('请选择有效期');
				}
				if(empty($departmentId)){
					throw new Exception('请选择部门');
				}
				if(empty($sellerIds)){
					throw new Exception('请选择销售人员');
				}

				//添加入库
				foreach($sellerIds as $seller){
					$addData = array(
						'department_id'=>$departmentId,
						'account_id'=>$account,
						'seller_id'=>$seller,
						'create_time'=>date("Y-m-d H:i:s"),
						'end_time'=>date("Y-m-d H:i:s",time()+24*3600*$shareTime),
					);

					$condition = "account_id = {$account} and seller_id = {$seller}";
					$hasOne = $model->getOneByCondition('id',$condition);
					if($hasOne){
						$updateData = array(
							'create_time'=>date("Y-m-d H:i:s"),
							'end_time'=>date("Y-m-d H:i:s",time()+24*3600*$shareTime),
						);
						$model->getDbConnection()->createCommand()->update($model->tableName(), $updateData, $condition);
					}else{
						$model->getDbConnection()->createCommand()->insert($model->tableName(), $addData);
					}
				}


				$forward = Yii::app()->createUrl("ebay/ebayaccountshare/list");
				$jsonData = array(
					'message' => Yii::t('system', 'Save successful'),
					'forward' => $forward,
					'navTabId' => 'page'.Menu::model()->getIdByUrl('/ebay/ebayaccountshare/list'),
					'callbackType' => 'closeCurrent'
				);
				echo $this->successJson($jsonData);

			}catch (Exception $e) {
				echo $this->failureJson(array('message' => $e->getMessage()));
			}
			Yii::app()->end();
		}

		$accountList = EbayAccount::model()->getIdNamePairs();
		$departmentList = EbayAccount::model()->getDepartment();
		$this->render('add', array(
			'model' => $model,
			'accountList' => $accountList,
			'departmentList'=>$departmentList
		));
	}

	/**
	 * 删除
	 */
	public function actionBatchdel()
	{
		$ids = Yii::app()->request->getParam("ids");

		if($ids){
			$idArr = explode(",", $ids);
			$model = new EbayAccountShare();
			if(empty($idArr)) {
				echo $this->failureJson(array('message'	=>	"请选择"));
				Yii::app()->end();
			}
			$res = $model->getDbConnection()->createCommand()->delete($model->tableName(), "id in(".MHelper::simplode($idArr).")");
			if($res){
				echo $this->successJson(array('message'	=>	Yii::t('system', 'Successful')));
				Yii::app()->end();
			}
		}
		echo $this->failureJson(array('message'	=>	"操作失败"));

		Yii::app()->end();

	}

	//选择部门
	public function actionSelectDepart(){
		$departID = Yii::app()->request->getParam("depart_id");
		try{

			$departUser = array();
			if($departID!=''){
				//$departUser = User::model()->getEmpByDept($departID);
				//排序方法
				$departUser = User::model()->findUserListByDepartmentId($departID);
			}

			echo $this->successJson(array('message'=>'success','departUser'=>$departUser));
		}catch (Exception $e){
			echo $this->failureJson(array('message'	=>	$e->getMessage()));
		}
	}

}