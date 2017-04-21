<?php
/**
 * @author hanxy
 */
class EbaydepartmentaccountsiteController extends UebController{

    /**
     * 通过部门ID获取账号
     * @return string
     */
	public function actionGetaccount(){
		$data = '<option value="">所有</option>';
		$departID = Yii::app()->request->getParam('department_id', '');
		$accountID = Yii::app()->request->getParam('account_id', '');
		$ebayDepartArr = Department::model()->getDepartmentByPlatform(Platform::CODE_EBAY);
		$ebayAccountModel = new EbayAccount();
		if(in_array($departID, $ebayDepartArr)){
			$model 				= new EbayDepartmentAccountSite();
			$where = 'department_id = '.$departID;
			$info = $model->getListByCondition($where);
			if($info){
				$accountArr = array();
				foreach ($info as $key => $value) {
					$accountArr[] = $value['account_id'];
				}

				$uniqueAccount = array_unique($accountArr);
				$accountInfo = $ebayAccountModel->getAccountNameByIds($uniqueAccount);
				foreach ($accountInfo as $k => $v) {
					if($k == $accountID){
						$data .='<option value="'.$k.'" selected="selected">'.$v.'</option>';
					}else{
						$data .='<option value="'.$k.'">'.$v.'</option>';
					}
				}
			}
		}else{
			$accountList = $ebayAccountModel->getIdNamePairs();
			foreach ($accountList as $k => $v) {
				$data .='<option value="'.$k.'">'.$v.'</option>';
			}
		}
				
		$this->render('ajaxdata', array('data'=>$data));
	}


	/**
	 * 通过部门和账号获取站点
	 */
	public function actionGetsite(){
		$data = '<option value="">所有</option>';
		$departID = Yii::app()->request->getParam('department_id');
		$accountID = Yii::app()->request->getParam('account_id');
		$siteID = Yii::app()->request->getParam('site_id', '');
		$ebayDepartArr = Department::model()->getDepartmentByPlatform(Platform::CODE_EBAY);
		if(in_array($departID, $ebayDepartArr) && $accountID){
			$model = new EbayDepartmentAccountSite();
			$where = 'department_id = '.$departID. ' AND account_id = '.$accountID;
			$info = $model->getListByCondition($where);
			if($info){
				$siteArr = array();
				foreach ($info as $key => $value) {
					$siteArr[] = $value['site_id'];
				}

				$siteInfo = EbaySite::model()->getSiteNameByIds($siteArr);
				foreach ($siteInfo as $k => $v) {
					if($k == $siteID && is_numeric($siteID)){
						$data .='<option value="'.$k.'" selected="selected">'.$v.'</option>';
					}else{
						$data .='<option value="'.$k.'">'.$v.'</option>';
					}
				}
			}
		}else{
			$siteList = EbaySite::getSiteList();
			foreach ($siteList as $k => $v) {
				$data .='<option value="'.$k.'">'.$v.'</option>';
			}
		}

		$this->render('ajaxdata', array('data'=>$data));
	}

	//列表
	public function actionList(){
		$model = new EbayDepartmentAccountSite();
		$this->render("list", array('model'=>$model));
	}

	//添加
	public function actionCreate(){

		$model = new EbayDepartmentAccountSite();
		if (Yii::app()->request->isAjaxRequest && isset($_POST['account_id'])) {

			$departmentID   = Yii::app()->request->getParam("department_id",'');
			$accountID  = Yii::app()->request->getParam("account_id",'');
			$siteArr   = Yii::app()->request->getParam("site_id",'');

			try {
				if ($departmentID == '') {
					throw new Exception("部门不能为空", 1);
				}
				if ($accountID == '') {
					throw new Exception("账号不能为空", 2);
				}
				if ( $siteArr === '' ) {
					throw new Exception("站点不能为空", 3);
				}
				//存入数据库
				foreach($siteArr as $siteID){

					$oneInfo = $model->getDbConnection()->createCommand()
						->from($model->tableName())
						->where("account_id = ".$accountID)
						->andWhere("site_id = ".$siteID)
						->queryRow();

					//数据库有记录的跳过
					if($oneInfo){ continue; }

					$addData = array(
						'department_id'=>$departmentID,
						'account_id'=>$accountID,
						'site_id'=>$siteID,
						'create_time'=>date("Y-m-d H:i:s"),
					);
					$model->getDbConnection()->createCommand()->insert($model->tableName(), $addData);
				}
				$jsonData = array(
					'message' => '添加成功',
					'forward' =>'/ebay/ebaydepartmentaccountsite/list',
					'navTabId'=> 'page' .Menu::model()->getIdByUrl('/ebay/ebaydepartmentaccountsite/list'),
					'callbackType'=>'closeCurrent'
				);
				echo $this->successJson($jsonData);
			} catch (Exception $e) {
				echo $this->failureJson(array('message'=>$e->getMessage()));
			}
			Yii::app()->end();
		}


		$departmentLists = EbayAccount::model()->getDepartment();
		$accountArr = EbayAccount::model()->getIdNamePairs();
		$siteArr = EbaySite::model()->getSiteList();
		$this->render("create",array('model' => $model,'siteArr' => $siteArr,'accountArr' => $accountArr,'departmentLists'=>$departmentLists));
	}

	//修改
	public function actionUpdate(){
		$model = new EbayDepartmentAccountSite();
		$id = Yii::app()->request->getParam('id');
		$department_id = Yii::app()->request->getParam('department_id');

		if($_POST){
			$updateData = array(
				"department_id"=>$department_id,
			);
			$model->getDbConnection()->createCommand()->update($model->tableName(), $updateData, "id=".$id);

			$jsonData = array(
				'message' => '更改成功',
				'forward' =>'/ebay/ebaydepartmentaccountsite/list',
				'navTabId'=> 'page' .Menu::model()->getIdByUrl('/ebay/ebaydepartmentaccountsite/list'),
				'callbackType'=>'closeCurrent'
			);
			echo $this->successJson($jsonData);
			exit;
		}

		$info = $model->getDbConnection()->createCommand()
			->select("department_id")
			->from($model->tableName())
			->where("id = ".$id)
			->queryRow();
		$departmentLists = EbayAccount::model()->getDepartment();

		$this->render("update",array('model' => $model,'id' => $id,'info' => $info,'departmentLists'=>$departmentLists));

	}
	//批量修改
	public function actionBatchUpdate(){
		$model = new EbayDepartmentAccountSite();

		$ids = trim(Yii::app()->request->getParam("ids"));
		if (Yii::app()->request->isAjaxRequest && isset($_POST['department_id'])) {

			$department_id = Yii::app()->request->getParam('department_id');

			if($ids==''){
				echo $this->failureJson(array('message' => '没有选择帐号！'));
				exit;
			}
			if($department_id==''){
				echo $this->failureJson(array('message' => '没有选择部门！'));
				exit;
			}
			$updateData = array(
				"department_id"=>$department_id,
			);
			$model->getDbConnection()->createCommand()->update($model->tableName(), $updateData, 'id IN(' .$ids . ')');

			$jsonData = array(
				'message' => '更改成功',
				'forward' =>'/ebay/ebaydepartmentaccountsite/list',
				'navTabId'=> 'page' .Menu::model()->getIdByUrl('/ebay/ebaydepartmentaccountsite/list'),
				'callbackType'=>'closeCurrent'
			);
			echo $this->successJson($jsonData);
			exit;
		}

		$departmentLists = EbayAccount::model()->getDepartment();
		$this->render("batchupdate",array('model' => $model,'ids' => $ids,'departmentLists'=>$departmentLists));
	}

}