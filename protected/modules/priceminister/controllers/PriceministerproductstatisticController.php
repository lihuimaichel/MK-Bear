<?php
/**
 * @desc ebay
 * @author qzz
 */
class PriceministerproductstatisticController extends UebController {
	protected $_model = null;
	public function init() {
		$this->_model = new PriceministerProductStatistic();
	}

	/**
	 * @desc 列表页
	 */
	public function actionList() {
		$accountID = Yii::app()->request->getParam('account_id', '0');
		$this->_model->account_id = $accountID;
		$this->render('list', array(
			'model' => $this->_model,'accountID'=>$accountID
		));
	}

	/**
	 * @desc 批量添加刊登任务
	 * @throws Exception
	 */
	public function actionBatchPublish(){

		$accountID = Yii::app()->request->getParam('account_id');
		$skus = Yii::app()->request->getParam('ids');

		if (empty($accountID)) {
			echo $this->failureJson(array(
				'message' => Yii::t('jd', 'Invalid Account'),
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
		$message = '';
		$PriceministerProductAddModel = new PriceministerProductAdd();

		foreach ($skuArr as $sku) {
			$flag = $PriceministerProductAddModel->addProductByBatch($sku,$accountID);

			if (!$flag)
				$message .= $PriceministerProductAddModel->getErrorMessage() . "<br />";
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
		Yii::app()->end();
	}


	/*
	 * 	获取xml分类上传别名
	 * 	/priceminister/priceministerproductstatistic/gettype
	 */
	public function actionGetType()
	{
		//暂时指定帐号为1，后续要改
		$accountID = 1;
		$request = new ProductTypesRequest();
		$response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();

		$pmProductType = PriceministerProductType::model();
		foreach($response->response->producttypetemplate as $value){
			$typeInfo = $pmProductType->getOneByCondition('id',"alias ='".$value->alias."'");
			if($typeInfo){
				$updateData = array();
				$updateData['label'] = $value->label;
				$updateData['update_date'] = isset($value->updatedate) ? str_replace("T"," ",$value->updatedate) : date("Y-m-d H:i:s");
				$updateData['update_time'] = date("Y-m-d H:i:s");
				$pmProductType->getDbConnection()->createCommand()->update($pmProductType->tableName(), $updateData,'id='.$typeInfo['id']);
			}else{
				$addData = array();
				$addData['alias'] = $value->alias;
				$addData['label'] = $value->label;
				$addData['update_date'] = isset($value->updatedate) ? str_replace("T"," ",$value->updatedate) : date("Y-m-d H:i:s");
				$addData['add_time'] = date("Y-m-d H:i:s");
				$addData['update_time'] = date("Y-m-d H:i:s");
				$pmProductType->getDbConnection()->createCommand()->insert($pmProductType->tableName(), $addData);
			}
		}
	}

	/*
	 * 	获取xml分类上传模版
	 * 	/priceminister/priceministerproductstatistic/gettypetemplate/type_id/1
	 */
	public function actionGetTypeTemplate()
	{
		set_time_limit(0);
		ini_set('memory_limit','2048M');
		ini_set("display_errors", true);
		error_reporting(E_ALL);

		$typeID = Yii::app()->request->getParam('type_id');
		//暂时指定帐号为1，后续要改
		$accountID = 1;

		$pmProductType = PriceministerProductType::model();
		$pmProductTypeTemplate = PriceministerProductTypeTemplate::model();
		$request = new ProductTypeTemplatesRequest();

		$where = "1";
		if($typeID){
			$where .= " and id = {$typeID}";
		}
		//获取模版类目列表
		$typeList = $pmProductType->getListByCondition('id,alias,label',$where);

		if($typeList){
			foreach($typeList as $typeInfo){
				$request->setAlias($typeInfo['alias']);
				$request->setScope('VALUES');
				$response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
				if(isset($response->response->attributes)){
					$templateInfo = $pmProductTypeTemplate->getOneByCondition('id',"type_id = {$typeInfo['id']}");
					if($templateInfo){
						$updateData = array();
						$updateData['attribute'] = json_encode($response->response->attributes);
						$updateData['update_time'] = date("Y-m-d H:i:s");
						$pmProductType->getDbConnection()->createCommand()->update($pmProductTypeTemplate->tableName(), $updateData,'type_id ='.$typeInfo['id']);
					}else{
						$addData = array();
						$addData['type_id'] = $typeInfo['id'];
						$addData['attribute'] = json_encode($response->response->attributes);
						$addData['add_time'] = date("Y-m-d H:i:s");
						$addData['update_time'] = date("Y-m-d H:i:s");
						$pmProductType->getDbConnection()->createCommand()->insert($pmProductTypeTemplate->tableName(), $addData);
					}
				}else{
					echo $typeInfo['id'].',';
				}
			}
		}
	}
}