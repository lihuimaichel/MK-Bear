<?php
class PlatformebaydeveloperaccountController extends UebController {
	
	/**
	 * @todo ebay开发者帐号管理列表
	 * @author hanxy
	 * @since 2017-03-06
	 */
	/** @var object  模型对象 **/
	protected $_model = null;
	
	/**
	 * (non-PHPdoc)
	 * @see CController::init()
	 */
	public function init() {
		$this->_model = new PlatformEbayDeveloperAccount();
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
		if($_POST){
			$accountParam = Yii::app()->request->getParam('PlatformEbayDeveloperAccount');
			list($accountName, $appid, $devid, $certid, $ruName, $maxNums) = array_values($accountParam);
			if(!$accountName){
				echo $this->failureJson(array('message'=> '账号名称不能为空'));
				Yii::app()->end();
			}

			if(!$appid){
				echo $this->failureJson(array('message'=> 'AppID不能为空'));
				Yii::app()->end();
			}

			if(!$devid){
				echo $this->failureJson(array('message'=> 'DevID不能为空'));
				Yii::app()->end();
			}

			if(!$certid){
				echo $this->failureJson(array('message'=> 'CertID不能为空'));
				Yii::app()->end();
			}

			if(!$ruName){
				echo $this->failureJson(array('message'=> 'RuName不能为空'));
				Yii::app()->end();
			}

			if(!$maxNums || !is_numeric($maxNums) || $maxNums <= 0){
				echo $this->failureJson(array('message'=> '调用次数不能为空,且必须是数值'));
				Yii::app()->end();
			}

			$times = date('Y-m-d H:i:s');
			$userID = (int)Yii::app()->user->id;
			$accountParam['status'] = 1;
			$accountParam['create_user_id'] = $userID;
			$accountParam['create_time'] = $times;
			$accountParam['modify_user_id'] = $userID;
			$accountParam['modify_time'] = $times;
			$result = $this->_model->insertData($accountParam);
			if($result){
				$jsonData = array(
					'message' => '添加成功',
					'forward' =>'/platformaccount/platformebaydeveloperaccount/list',
					'navTabId'=> 'page' . PlatformEbayDeveloperAccount::getIndexNavTabId(),
					'callbackType'=>'closeCurrent'
				);
				echo $this->successJson($jsonData);
			}else{
				echo $this->failureJson(array('message'=> '添加失败'));
			}

			Yii::app()->end();
		}
		$this->render("add", array("model"=>$this->_model));	
	}


	/**
	 * 修改账号
	 */
	public function actionEdit(){
		$id = Yii::app()->request->getParam('id');
		if($_POST){
			$accountParam = Yii::app()->request->getParam('PlatformEbayDeveloperAccount');
			$times = date('Y-m-d H:i:s');
			$userID = (int)Yii::app()->user->id;
			$accountParam['create_user_id'] = $userID;
			$accountParam['create_time'] = $times;
			$accountParam['modify_user_id'] = $userID;
			$accountParam['modify_time'] = $times;
			$result = $this->_model->updateData($accountParam, 'id = :id', array(':id'=>$id));
			if($result){
				$jsonData = array(
					'message' => '更改成功',
					'forward' =>'/platformaccount/platformebaydeveloperaccount/list',
					'navTabId'=> 'page' . PlatformEbayDeveloperAccount::getIndexNavTabId(),
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

		//获取账号状态
		$accountStatusList = PlatformEbayDeveloperAccount::getDeveloperAccountStatus();

		$this->render("edit", array("model"=>$accountInfo, 'accountStatusList'=>$accountStatusList));
		Yii::app()->end();			
	}
}