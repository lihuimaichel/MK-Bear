<?php
class PlatformlazadaaccountlistController extends UebController {
	
	/**
	 * @todo lazada总帐号管理列表
	 * @author hanxy
	 * @since 2017-03-20
	 */
	/** @var object  模型对象 **/
	protected $_model = null;
	
	/**
	 * (non-PHPdoc)
	 * @see CController::init()
	 */
	public function init() {
		$this->_model = new PlatformLazadaAccountList();
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
			$accountParam = Yii::app()->request->getParam('PlatformLazadaAccountList');
			list($accountName) = array_values($accountParam);

			if(!$accountName){
				echo $this->failureJson(array('message'=>'账号简称不能为空'));
				Yii::app()->end();
			}

			$isExist = $this->_model->getOneByCondition('id',"account_name = '{$accountName}'");
			if($isExist){
				echo $this->failureJson(array('message'=>'此账号已存在'));
				Yii::app()->end();
			}

			$accountParam['account_name'] = $accountName;
			$accountParam['create_user_id'] = (int)Yii::app()->user->id;
			$accountParam['create_time'] = date('Y-m-d H:i:s');
			$accountParam['modify_user_id'] = (int)Yii::app()->user->id;
			$accountParam['modify_time'] = date('Y-m-d H:i:s');

			$result = $this->_model->insertData($accountParam);
			if($result){
				$jsonData = array(
					'message' => '添加成功',
					'forward' =>'/platformaccount/platformlazadaaccountlist/list',
					'navTabId'=> 'page' . PlatformLazadaAccountList::getIndexNavTabId(),
					'callbackType'=>'closeCurrent'
				);
				echo $this->successJson($jsonData);
			}else{
				echo $this->failureJson(array('message'=>'添加失败'));
			}
			Yii::app()->end();
		}
		//获取站点列表
		$siteList = LazadaSite::getSiteList();
		$this->render("add", array("model"=>$this->_model, 'siteList'=>$siteList));
		Yii::app()->end();			
	}


	/**
	 * 修改账号
	 */
	public function actionEdit(){
		$id = Yii::app()->request->getParam('id');
		if($_POST){
			$accountParam = Yii::app()->request->getParam('PlatformLazadaAccountList');
			list($accountName) = array_values($accountParam);

			if(!$accountName){
				echo $this->failureJson(array('message'=>'账号简称不能为空'));
				Yii::app()->end();
			}
			$accountParam['account_name'] = $accountName;
			$accountParam['modify_user_id'] = (int)Yii::app()->user->id;
			$accountParam['modify_time'] = date('Y-m-d H:i:s');
			$result = $this->_model->updateData($accountParam, 'id = :id', array(':id'=>$id));
			if($result){
				$jsonData = array(
					'message' => '更改成功',
					'forward' =>'/platformaccount/platformlazadaaccountlist/list',
					'navTabId'=> 'page' . PlatformLazadaAccountList::getIndexNavTabId(),
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

		$this->render("edit", array("model"=>$accountInfo));
		Yii::app()->end();
	}
}