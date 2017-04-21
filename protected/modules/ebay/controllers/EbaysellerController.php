<?php
class EbaysellerController extends UebController{
	
	/**
	 * Specifies the access control rules.
	 * This method is used by the 'accessControl' filter.
	 * @return array access control rules 
	 */
	public function accessRules(){
		return array();
	}
	
	/**
	 * @dsec EbaySeller帐号管理列表
	 * @author tony
	 * @since 2015/08/29
	 */
	public function actionList(){
		$model = UebModel::model('EbaySeller');
		$this->render('list',array('model'=>$model));
	}
	
	/**
	 * 添加卖家账号
	 */
	public function actionCreateselleraccount(){
		$model = new EbaySeller();
		if(isset($_REQUEST['EbaySeller'])){
			$userName = $_REQUEST['EbaySeller']['user_name'];
			//准备添加数据
			$param = $model->getNewUserInfo($userName);
			//添加卖家账号
			$insertId  = $model->saveNewUserName($param);
			//根据是否添加成功,返回信息
			if ($insertId) {
				//添加一份到oms
				$omsSellerModel = new SuggestProductSeller();
				$sellerData = array(
						'account_id' => $insertId,
						'user_name' => $userName,
						'platform_code' => Platform::CODE_EBAY,
						'status' => '1',
						'create_time' => date('Y-m-d H:i:s'),
						'create_user_id' => Yii::app()->user->id
				);
				$omsSellerModel->saveSellerInfo( $sellerData );
				$jsonData = array(
						'message' => Yii::t('system', 'Add successful'),
						'forward' => '/ebay/ebayseller/list',
						'navTabId' => 'page' . $model->getIndexNavTabId(),
						'callbackType' => 'closeCurrent'
				);
				echo $this->successJson($jsonData);
			} else {
				$insertId = false;
			}
			if (! $insertId) {
				echo $this->failureJson(array('message' => Yii::t('system', 'Add failure')));
			}
			Yii::app()->end();
		}
		$this->render('create',array('model'=>$model));
	}
	
	/**
	 * @desc 关闭账号
	 */
	public function actionShutdownaccount(){
		
		$userName =$_REQUEST['ids'];
		$straccount = 'user_name';
		
		$ebaySellerModel = EbaySeller::model();
		
		$shutDown = $ebaySellerModel::STATUS_SHUTDOWN;//获取账号状态为关闭的参数
		
		if (Yii::app()->request->isAjaxRequest && isset($_REQUEST['ids'])) {
			try {
				foreach (explode(',',$_REQUEST['ids']) as $ebaySellerAccount){
					//获取账号基本信息
					$data = $ebaySellerModel->getAccountInfoByAttribute($straccount,$ebaySellerAccount);
					if($data['status'] == $shutDown){
						$jsonData = array(
								'message' => Yii::t('system', 'the chosen account has been haven account closed'),
						);
						echo $this->failureJson($jsonData);
						die;
					}
					//修改账号状态为关闭
					$flag=$ebaySellerModel->shutDownAccount($ebaySellerAccount);
					if($flag){
						//同步到oms
						$omsSellerModel = new SuggestProductSeller();
						$sellerData = array(
								'status' => '0'
						);
						$omsSellerModel->updateAll($sellerData,'user_name in("'.$ebaySellerAccount.'") and platform_code = "'.Platform::CODE_EBAY.'"');
					}
				}
			} catch (Exception $e) {
				$flag = false;
			}
		}
		if($flag){
			$jsonData = array(
					'message' => Yii::t('system', 'ShutDown Success'),
					'navTabId' => 'page' . $ebaySellerModel->getIndexNavTabId(),
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
		$userName =$_REQUEST['ids'];
		$straccount = 'user_name';
		
		$ebaySellerModel = EbaySeller::model();
		
		$open = $ebaySellerModel::STATUS_OPEN;//获取账号状态为关闭的参数
		
		if (Yii::app()->request->isAjaxRequest && isset($_REQUEST['ids'])) {
			try {
				foreach (explode(',',$_REQUEST['ids']) as $ebaySellerAccount){
					//获取账号基本信息
					$data = $ebaySellerModel->getAccountInfoByAttribute($straccount,$ebaySellerAccount);
					if($data['status'] == $open){
						$jsonData = array(
								'message' => Yii::t('system', 'the chosen account has been haven account closed'),
						);
						echo $this->failureJson($jsonData);
						die;
					}
					//修改账号状态为开启
					$flag=$ebaySellerModel->openAccount($ebaySellerAccount);
					if($flag){
						//同步到oms
						$omsSellerModel = new SuggestProductSeller();
						$sellerData = array(
								'status' => '1'
						);
						$omsSellerModel->updateAll($sellerData,'user_name in("'.$ebaySellerAccount.'") and platform_code = "'.Platform::CODE_EBAY.'"');
					}
				}
			} catch (Exception $e) {
				$flag = false;
			}
		}
		if($flag){
			$jsonData = array(
					'message' => Yii::t('system', 'Open Success'),
					'navTabId' => 'page' . $ebaySellerModel->getIndexNavTabId(),
			);
			echo $this->successJson($jsonData);
		}else{
			echo $this->failureJson(array('message'=> Yii::t('system', 'ShutDown Failed')));
		}
		Yii::app()->end();
	}
	
	/**
	 * 批量删除
	 */
	public function actionDeleteaccount() {
		$userName =$_REQUEST['ids'];
		$straccount = 'user_name';
		
		$ebaySellerModel = EbaySeller::model();
		
		if (Yii::app()->request->isAjaxRequest && isset($_REQUEST['ids'])) {
			try {
				foreach (explode(',',$_REQUEST['ids']) as $ebaySellerAccount){
					//通过账号名删除账号信息
					$flag = $ebaySellerModel->deleteAccountByAttribute($straccount,$ebaySellerAccount);
					if($flag){
						//同步到oms
						$omsSellerModel = new SuggestProductSeller();
						$sellerData = array(
								'status' => '0'
						);
						$omsSellerModel->updateAll($sellerData,'user_name in("'.$ebaySellerAccount.'") and platform_code = "'.Platform::CODE_EBAY.'"');
					}
				}
			} catch (Exception $e) {
				$flag = false;
			}
			if ($flag) {
				$jsonData = array(
						'message' => Yii::t('system', 'Delete successful'),
						'navTabId' => 'page' . $ebaySellerModel->getIndexNavTabId(),
				);
				echo $this->successJson($jsonData);
			} else {
				$jsonData = array(
						'message' => Yii::t('system', 'Delete failure')
				);
				echo $this->failureJson($jsonData);
			}
			Yii::app()->end();
		}
	}
	
	/**
	 * @desc 更新选择的卖家的item
	 */
	public function actionUpdateitem(){
		$userName =$_REQUEST['ids'];
		$straccount = 'user_name';
		
		$EndTimeFrom = date('Y-m-d\TH:i:s\Z',time()-3600*9);//开始时间
		$EndTimeTo = date('Y-m-d\T00:00:00\Z',time()+86400*7);//结束时间
		if (Yii::app()->request->isAjaxRequest && isset($_REQUEST['ids'])) {
			$flag = false;
			try {
				//1.取一个可用的来验证的账号
				$account = EbayAccount::model()->getAbleAccountByOne();
				//2.设置账号站点
				$ebayProductOtherModel = new EbayProductOther();
				$ebayProductOtherModel->setAccountID( $account['id'] );
				$ebayProductOtherModel->setSite(0);
				foreach (explode(',',$_REQUEST['ids']) as $value){
					$ebaySellerInfo = UebModel::model('EbaySeller')->find('user_name = "'.$value.'"');
					if($ebaySellerInfo->status == EbaySeller::STATUS_SHUTDOWN) continue;
					//3.准备日志
					$logID = EbayLog::model()->prepareLog( $account['id'],EbayProductOther::EVENT_NAME );
					//4.设置日志为正在运行
					EbayLog::model()->setRunning($logID);
					//5.组合参数
					$params = array(
							'UserId'=>$value,
							'account_id'=>$ebaySellerInfo->id,
							'EndTimeFrom' => $EndTimeFrom,
							'EndTimeTo' => $EndTimeTo,
							'IncludeWatchCount' => true,
							'IncludeVariations' => true
					);
					
					$flag = $ebayProductOtherModel->getSellerItemByCondition( $params );
					//6.更新日志信息
					if( $flag ){
						EbayLog::model()->setSuccess($logID);
					}else{
						EbayLog::model()->setFailure($logID, $ebayProductOtherModel->getExceptionMessage());
					}
				}
			} catch (Exception $e) {
				$flag = false;
			}
			if ($flag) {
				$jsonData = array(
						'message' => Yii::t('system', 'Oprate successful'),
						'navTabId' => 'page' . EbaySeller::getIndexNavTabId(),
				);
				echo $this->successJson($jsonData);
			} else {
				$jsonData = array(
						'message' => Yii::t('system', 'Oprate failure')
				);
				echo $this->failureJson($jsonData);
			}
			Yii::app()->end();
		}
	}
	
	
	/**
	 * @desc 获取账号基本信息
	 */
	public function loadModel($id){
		$model = UebModel::model('EbaySeller')->findByPk($id);
		if($model===false){
			throw new CHttpException ( 404, Yii::t ( 'app', 'The requested page does not exist.' ) );
		}else{
			return $model;
		}
	}
	
	/**
	 * @desc 获取账号基本信息By用户名
	 */
	public function loadModelByattribute($attribute,$data){
		$model = UebModel::model('EbaySeller')->findByAttributes(array($attribute=>$data));
		if($model===false){
			throw new CHttpException ( 404, Yii::t ( 'app', 'The requested page does not exist.' ) );
		}else{
			return $model;
		}
	}
	
}