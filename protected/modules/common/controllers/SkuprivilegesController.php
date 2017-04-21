<?php
/**
 * @desc SKU权限管理
 * @author zhangF
 *
 */
class SkuprivilegesController extends UebController {
	/** @var 模型对象 **/
	protected $_model = null;
	
	public function init() {
		$this->_model = new Skuprivileges();
		parent::init();
	}
	
	/**
	 * @desc SKU权限列表列表
	 */
	public function actionList() {
		$this->render('list', array(
			'model' => $this->_model,
		));
	}
	
	/**
	 * @desc 添加权限
	 */
	public function actionCreate() {
		$this->render('create', array(
			'model' => $this->_model,
		));
	}
	
	/**
	 * @desc 选择要添加权限的用户
	 */
	public function actionChooseuser() {
		if (Yii::app()->request->isPostRequest) {
			$username = trim($_POST['Skuprivileges']['username']);
			$platformCode = trim($_POST['Skuprivileges']['platform_code']);
			$accountId = (int)$_POST['Skuprivileges']['account_id'];
			//检查用户名是否存在
			$userInfo = User::model()->find("user_name = :username", array(':username' => $username));
			if (empty($userInfo)) {
				echo $this->failureJson(array(
					'message' => Yii::t('sku_privileges', 'User Not Exists'),
				));
				Yii::app()->end();
			}
			//检查平台是否存在
			$platformInfo = Platform::model()->find("platform_code = :code", array(':code' => $platformCode));
			if (empty($platformInfo)) {
				echo $this->failureJson(array(
						'message' => Yii::t('sku_privileges', 'Platform Not Exists'),
				));
				Yii::app()->end();
			}
			$platformId = $platformInfo['id'];
			echo $this->successJson(array(
				'message' => '',
				'callbackType' => 'closeCurrent',
				'url' => Yii::app()->createUrl('common/skuprivileges/createprivileges', array('user_id' => $userInfo['id'], 'platform_id' => $platformId, 'account_id' => $accountId)),
			));
			Yii::app()->end();
		}
		$this->render('chooseuser', array(
			'model' => $this->_model,
		));
	}
	
	/**
	 * @desc 添加权限
	 * @throws Exception
	 */
	public function actionCreateprivileges() {
		$userId = Yii::app()->request->getParam('user_id');
		$platformId = Yii::app()->request->getParam('platform_id');
		$accountId = Yii::app()->request->getParam('account_id');
		$this->_model->platform_id = $platformId;
		$this->_model->user_id = $userId;
		$this->_model->account_id = $accountId;
		//查询出当前用户在当前平台有权限的SKU
		$hasPrivilegesSkuList = $this->_model->getPrivilegesSkuList();
		
		if (Yii::app()->request->isPostRequest && isset($_POST['action'])) {
			$transaction = $this->_model->getDbConnection()->beginTransaction();
			try {
				if ($_POST['action'] == 'checkbox-do') {
					//根据checkbox选中的SKU添加
					$skuList = Yii::app()->request->getParam('create_privileges_id_c0');
					$skus = Yii::app()->request->getParam('skus');
					if (empty($skus))
						$skus = array();
					if (empty($skuList))
						$skuList = array();
					$skuList = array_filter($skuList);
					$oldSkuList = array_filter($skus);
					//取出要添加的SKU集合
					$addSkuList = array_diff($skuList, $oldSkuList);					
					//取出要删除的SKU集合
					$deleteSkuList = array_diff($oldSkuList, $skuList);
					
				} else {
					//根据搜索条件添加权限
					$sku = Yii::app()->request->getParam('sku', '');
					$productCategoryId = Yii::app()->request->getParam('product_category_id', '');
					$criteria = new CDbCriteria();
					if (!empty($sku)) {
						$criteria->addSearchCondition("sku", $sku, true);
					}
					if ($productCategoryId != '' && $productCategoryId != -1) {
						$criteria->compare("product_category_id", (int)$productCategoryId);
					}
					if ($criteria->condition == '') {
						echo $this->failureJson(array(
								'message' => Yii::t('sku_privileges', 'No Search Condition'),
						));
						Yii::app()->end();						
					}
					//按搜索条件查找产品
					$productInfos = Product::model()->findAll($criteria);
					if (empty($productInfos)) {
						echo $this->failureJson(array(
								'message' => Yii::t('sku_privileges', 'No Search Condition'),
						));
						Yii::app()->end();						
					}
					$skuList = array();
					foreach ($productInfos as $productInfo) {
						$skuList[] = $productInfo->sku;
					}
					$oldSkuList = $hasPrivilegesSkuList;
					if ($_POST['action'] == 'search-do-grant') {
						$deleteSkuList = array();
						//取出要添加的SKU集合
						$addSkuList = array_diff($skuList, $oldSkuList);
					} else if ($_POST['action'] == 'search-do-revoke') {
						$addSkuList = array();
						//取出要删除的SKU集合
						$deleteSkuList = $skuList;
					}
				}
				//print_r($addSkuList);
				//print_r($deleteSkuList);exit;
				//删除取消权限的
				foreach ($deleteSkuList as $sku) {
					$this->_model->sku = $sku;
					if (!$this->_model->revokePrivileges())
						throw new Exception(Yii::t('sku_privileges', 'Process Data Failure'));
				}
				//添加新加权限的
				foreach ($addSkuList as $sku) {
					$this->_model->sku = $sku;
					if (!$this->_model->grantPrivileges())
						throw new Exception(Yii::t('sku_privileges', 'Process Data Failure'));
				}
				$transaction->commit();
				echo $this->successJson(array(
					'message' => Yii::t('sku_privileges', 'Grant Privileges Successful'),
				));
				Yii::app()->end();
			} catch (Exception $e) {
				echo $e->getMessage();exit;
				$transaction->rollback();
				echo $this->failureJson(array(
						'message' => Yii::t('sku_privileges', 'Grant Privileges Failure'),
				));
				Yii::app()->end();				
			}
		}
		$this->render('createprivileges', array(
			'model' => $this->_model, 'productModel' => Product::model()
		));
	}
}