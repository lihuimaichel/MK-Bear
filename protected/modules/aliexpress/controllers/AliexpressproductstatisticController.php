<?php
/**
 * @desc lazada 在线产品统计
 * @author zhangF
 *
 */
class AliexpressproductstatisticController extends UebController {
	
	/** @var object 模型实例 **/
	protected $_model = NULL;
	
	/**
	 * (non-PHPdoc)
	 * @see CController::init()
	 */
	public function init() {
		$this->_model = new AliexpressProductStatistic();
	}
	
	/**
	 * @desc 列表页
	 */
	public function actionList() {
		$accountID = Yii::app()->request->getParam('account_id', '0');
		$publishAccountID = Yii::app()->request->getParam('publish_account_id', '0');
		$publishGroupID = Yii::app()->request->getParam('publish_group_id');
		$moduleId = Yii::app()->request->getParam('module_id');	
		$freightTemplateId = Yii::app()->request->getParam('freight_template_id');	
		$this->_model->account_id = $accountID;
		$this->render('list', array(
				'model' 			=> $this->_model,
				'accountID'			=> $accountID, 
				'publishAccountID'	=> $publishAccountID, 
				'publishGroupID'	=> $publishGroupID,
				'moduleId' 			=> $moduleId,
				'freightTemplateId' => $freightTemplateId
		));
	}
	
	/**
	 * @desc 批量添加刊登任务
	 * @throws Exception
	 */
	public function actionBatchPublish() {
		$accountID = Yii::app()->request->getParam('account_id');
		$skus = Yii::app()->request->getParam('ids');
		 if (empty($accountID)) {
			echo $this->failureJson(array(
					'message' => Yii::t('aliexpress_product_statistic', 'Invalid Site or Account'),
			));
			Yii::app()->end();
		}
		$skuArr = explode(',', $skus);
		$skuArr = array_filter($skuArr);
		if (empty($skuArr)) {
			echo $this->failureJson(array(
					'message' => Yii::t('aliexpress_product_statistic', 'Not Chosen Products'),
			));
			Yii::app()->end();
		}
		//批量添加到待上传列表
		$aliexpressProductAddModel = AliexpressProductAdd::model();
		$message = '';
		foreach ($skuArr as $sku) {
			//检测是否有权限去刊登该sku
			//上线后打开注释---lihy 2016-05-10
			if(! Product::model()->checkCurrentUserAccessToSaleSKUNew($sku,$accountID, Platform::CODE_ALIEXPRESS)){
				$message .= $sku.Yii::t('system', 'Not Access to Add the SKU').'<br/>';
				continue;
			}
			
			$return = $aliexpressProductAddModel->productAdd($sku, $accountID);
			if ($return['status'] == '0') {
				$message .= $sku.$return['message'].'<br/>';
			}
		}
		if( $message=='' ){
			echo $this->successJson(array(
					'message' => Yii::t('aliexpress_product_statistic', 'Publish Task Create Successful'),
					'callbackType' => 'navTabAjaxDone',
			));
		}else{
			echo $this->failureJson(array(
					'message' => $message,
			));
		}
		
	}


	/**
	 * 批量sku到其他账号保存
	 */
	public function actionBatchpublishselectaccountsave(){
		set_time_limit(8*3600);
		$onlineAccountId = Yii::app()->request->getParam('account_id');
		$addAccountId = Yii::app()->request->getParam('add_account_id');
		$moduleId = Yii::app()->request->getParam('module_id');

		//判断已发布的账号是否为空
		if(!$onlineAccountId){
			echo $this->failureJson(array('message' => '请选择已发布的账号'));
			exit;
		}
		
		$addSkuString = Yii::app()->request->getParam('ids');
		if(!$addSkuString){
			echo $this->failureJson(array('message' => '请选择要发布的sku'));
			exit;
		}

		//判断要发布的账号是否为空
		if(!$addAccountId){
			echo $this->failureJson(array('message' => '请选择要发布的账号'));
			exit;
		}

		//判断产品分组
		$groupId = Yii::app()->request->getParam('publish_group_id');
		if(!$groupId){
			echo $this->failureJson(array('message' => '请选择要发布的产品分组'));
			exit;
		}

		//判断运费模板
		$freightTemplateId = Yii::app()->request->getParam('freight_template_id');

		//判断产品分组是否存在该账号
		$isresult = AliexpressGroupList::model()->getGroupNameByAccountIdAndGroupId($addAccountId,$groupId);
		if(!$isresult){
			echo $this->failureJson(array('message' => $aliexpressAccountName[$addAccountId].'账号无此产品分组'));
			exit;
		}

		$addSkuArray = explode(',', $addSkuString);

		$addResut = $this->_model->PublishSkuToProductAdd($addSkuArray,$addAccountId,$onlineAccountId,$moduleId,$groupId,$freightTemplateId);	
		if(!$addResut[0]){
			echo $this->failureJson(array('message' => $addResut[1]));
			exit;
		}	

		echo $this->successJson(array('message' => '发布成功'));
	}


	/**
	 * @desc 获取产品分组的数据
	 */
	public function actionGroupajaxdata(){
		$groupData = '';
		$accountId = Yii::app()->request->getParam('accountId');
		$publishGroupId = Yii::app()->request->getParam('publishGroupId');
		$groupList = AliexpressGroupList::model()->getGroupTree($accountId);
		if($publishGroupId){
			$groupList = str_replace('<option value="'.$publishGroupId.'">', '<option value="'.$publishGroupId.'" selected>', $groupList);
		}
		$this->render('groupajaxdata', array('groupData'=>$groupList));
	}


	/**
	 * @desc 获取产品分类的目录
	 */
	public function actionCategorylevelajaxdata(){
		$categoryData = '<option value="">所有</option>';
		$parentCategoryId = Yii::app()->request->getParam('parentCategoryId');
		$categoryList = AliexpressCategory::model()->getCategoriesByParentID($parentCategoryId);
		if($categoryList){
			foreach ($categoryList as $key => $value) {
				$categoryData .= '<option value="'.$value['category_id'].'">'.$value['en_name'].'('.$value['cn_name'].')</option>';
			}
		}

		$this->render('categoryajaxdata', array('categoryData'=>$categoryData));
	}


	/**
	 * @desc 获取推荐产品信息模块
	 */
	public function actionModuleajaxdata(){
		$moduleData = '<option value="">所有</option>';
		$accountId = Yii::app()->request->getParam('accountId');
		$moduleList = Aliexpressproductinfomodule::model()->getModuleFieldsByAccountId($accountId);
		if($moduleList){
			foreach ($moduleList as $key => $value) {
				$moduleData .= '<option value="'.$value['module_id'].'">'.$value['name'].'</option>';
			}
		}

		$this->render('categoryajaxdata', array('categoryData'=>$moduleData));
	}	


	/**
	 * @desc 获取运费模板
	 */
	public function actionFreighttemplateajaxdata(){
		$freightData = '<option value="">所有</option>';
		$accountId = Yii::app()->request->getParam('accountId');
		$freightList = AliexpressFreightTemplate::model()->getTemplateIdInfoByAccountId($accountId);
		if($freightList){
			foreach ($freightList as $key => $value) {
				$freightData .= '<option value="'.$value['template_id'].'">'.$value['template_name'].'</option>';
			}
		}

		$this->render('categoryajaxdata', array('categoryData'=>$freightData));
	}
	
}