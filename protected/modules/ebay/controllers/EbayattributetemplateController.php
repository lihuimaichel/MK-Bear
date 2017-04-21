<?php
/**
 * @desc ebay产品属性模板
 * @author lihy
 *
 */
class EbayattributetemplateController extends UebController{
	
	public function actionIndex(){
		$model = UebModel::model("EbayProductAttributeTemplate");
		$this->render("index", array(
					'model'	=>	$model
		));			
	}
	
	public function actionChoosesite(){
		$model = UebModel::model("EbayProductAttributeTemplate");
		$this->render("choose_site", array('model'=>$model));
	}
	/**
	 * @desc 添加
	 */
	public function actionAdd(){
		$model = UebModel::model("EbayProductAttributeTemplate");
		if(Yii::app()->request->isAjaxRequest && isset($_POST['EbayProductAttributeTemplate'])){
			$services = Yii::app()->request->getParam('services');
			$costtypes = Yii::app()->request->getParam('costtypes');
			$additionalcosts = Yii::app()->request->getParam('additionalcosts');
			$shippingServices = Yii::app()->request->getParam('shippingServices');
			$shoptos = Yii::app()->request->getParam('shoptos');
			$locations = Yii::app()->request->getParam('locations');
			$_POST['EbayProductAttributeTemplate']['time_zone'] = $_POST['EbayProductAttributeTemplate']['time_zone_prefix'].$_POST['EbayProductAttributeTemplate']['time_zone'];
			$model->attributes = $_POST['EbayProductAttributeTemplate'];
			$siteID = $_POST['EbayProductAttributeTemplate']['site_id'];
			$userId = Yii::app()->user->id;
			$model->setAttribute('opration_id', $userId);
			$model->setAttribute('opration_date', date('Y-m-d H:i:s'));
			//@todo 验证价格区间是否已经存在
			if ($model->validate()) {
				$model->setIsNewRecord(true);
				$flag = $model->save();
				$pid = $model->id;
				//保存到运费模板表
				if($flag && $shippingServices){
					$shippingTemplateModel = new EbayProductShippingTemplate();
					$prioritys = array();
					foreach ($shippingServices as $key=>$service){
						if(!isset($prioritys[$service])){
							$prioritys[$service] = 0;
						}
						$prioritys[$service]++;
						$shippingtype = $service=='international' ? '2' : '1';
						if($service == 'international' && isset($shoptos[$key])){
							$shiplocation = $shoptos[$key];
						}else{
							$location = isset($locations[$key]) ? $locations[$key] : array();
							$shiplocation = implode(',', $location);
						}
						$shippingTemplateModel->saveShippingTemplateData(array(
																			'pid'			=>	$pid,
																			'site_id'		=>	$siteID,
																			'shipping_type'	=>	$shippingtype,
																			'shipping_service'	=>	$services[$key],
																			'cost_type'			=>	$costtypes[$key],
																			'additional_cost'	=>	$additionalcosts[$key],
																			'priority'			=>	$prioritys[$service],
																			'ship_location'		=>	trim($shiplocation),
																		));
					}
				}
				if ($flag) {
					$forward = Yii::app()->createUrl("ebay/ebaysalepriceconfig/index");
					$jsonData = array(
							'message' => Yii::t('system', 'Save successful'),
							'forward' => $forward,
							'navTabId' => '',
							'callbackType' => 'closeCurrent'
					);
					echo $this->successJson($jsonData);
				}
			} else {
				$flag = false;
			}
			if (!$flag) {
				echo $this->failureJson(array(
						'message' => Yii::t('system', 'Save failure')));
			}
			Yii::app()->end();
		}
		$siteID = Yii::app()->request->getParam("site_id");
		$returnPolicy = $shippingInfo = $specialCountry = array();
		if($siteID){
			$model->site_id = $siteID;
			$ebayCategoryInfoModel = new EbayCategoryInfo();
			//退货政策信息
			$returnPolicy = $ebayCategoryInfoModel->getReturnPolicyInfo($siteID);
			//运输信息
			$shippingInfo = $ebayCategoryInfoModel->getShippingInfo($siteID);
			//特殊国家
			$specialCountry = $model->special_country;
		}

		$this->render("add", array("data"=>array('model'=>$model, 'site_id'=>$siteID, 'returnPolicy'=>$returnPolicy, 
				'shippingInfo'=>$shippingInfo, 'shippingTemplate'=>array(), 'specialCountry'=>$specialCountry)));
	}
	
	/**
	 * @desc 更新
	 */
	public function actionUpdate(){
		$id = Yii::app()->request->getParam('id');
		$model = UebModel::model("EbayProductAttributeTemplate")->findByPk($id);
		if(Yii::app()->request->isAjaxRequest && isset($_POST['EbayProductAttributeTemplate'])){
			$services = Yii::app()->request->getParam('services');
			$costtypes = Yii::app()->request->getParam('costtypes');
			$additionalcosts = Yii::app()->request->getParam('additionalcosts');
			$shippingServices = Yii::app()->request->getParam('shippingServices');
			$shoptos = Yii::app()->request->getParam('shoptos');
			$locations = Yii::app()->request->getParam('locations');
			
			$_POST['EbayProductAttributeTemplate']['time_zone'] = $_POST['EbayProductAttributeTemplate']['time_zone_prefix'].$_POST['EbayProductAttributeTemplate']['time_zone'];
			$model->attributes = $_POST['EbayProductAttributeTemplate'];
			$siteID = $_POST['EbayProductAttributeTemplate']['site_id'];
			$userId = Yii::app()->user->id;
			$model->setAttribute('opration_id', $userId);
			$model->setAttribute('opration_date', date('Y-m-d H:i:s'));
			//@todo 验证价格区间是否已经存在
			
			if ($model->validate()) {
				$flag = $model->save();
				$pid = $model->id;
				//保存到运费模板表
				$shippingTemplateModel = new EbayProductShippingTemplate();
				if($flag)	$shippingTemplateModel->deleteAll("pid={$pid}");
				if($flag && $shippingServices){
					$prioritys = array();
					foreach ($shippingServices as $key=>$service){
						if(!isset($prioritys[$service])){
							$prioritys[$service] = 0;
						}
						$prioritys[$service]++;
						$shippingtype = $service=='international' ? '2' : '1';
						if($service == 'international' && !empty($shoptos[$key])){
							$shiplocation = $shoptos[$key];
						}else{
							$location = isset($locations[$key]) ? $locations[$key] : array();
							$shiplocation = implode(',', $location);
						}
						$shippingTemplateModel->saveShippingTemplateData(array(
								'pid'			=>	$pid,
								'site_id'		=>	$siteID,
								'shipping_type'	=>	$shippingtype,
								'shipping_service'	=>	$services[$key],
								'cost_type'			=>	$costtypes[$key],
								'additional_cost'	=>	$additionalcosts[$key],
								'priority'			=>	$prioritys[$service],
								'ship_location'		=>	trim($shiplocation),
						));
					}
				}
				if ($flag) {
					$forward = "";
					$jsonData = array(
							'message' => Yii::t('system', 'Save successful'),
							'forward' => $forward,
							'navTabId' => '',
							'callbackType' => 'closeCurrent'
					);
					echo $this->successJson($jsonData);
				}
			} else {
				$flag = false;
			}
			if (!$flag) {
				echo $this->failureJson(array(
						'message' => Yii::t('system', 'Save failure')));
			}
			Yii::app()->end();
		}
		$siteID = $model->site_id;
		$model->time_zone_prefix = substr($model->time_zone, 0, 1);
		$model->time_zone = substr($model->time_zone, 1);
		//$this->print_r($model);
		$ebayCategoryInfoModel = new EbayCategoryInfo();
		//退货政策信息
		$returnPolicy = $ebayCategoryInfoModel->getReturnPolicyInfo($siteID);
		//运输信息
		$shippingInfo = $ebayCategoryInfoModel->getShippingInfo($siteID);
		
		//已有的物流信息
		$shippingTemplate = EbayProductShippingTemplate::model()->getShippingTemplateListByPid($id);
		//特殊国家
		$specialCountry = $model->special_country;
		
		$this->render("update", array("data"=>array('model'=>$model, 'site_id'=>$siteID, 'returnPolicy'=>$returnPolicy, 'shippingInfo'=>$shippingInfo, 
						'shippingTemplate'=>$shippingTemplate, 'specialCountry'=>$specialCountry), "siteID"=>$siteID)
		);
	}
	
	
	/**
	 * @DESC  批量删除
	 * @throws Exception
	 */
	public function actionBatchdelete(){
		$ids = Yii::app()->request->getParam("ids");
		try{
			if(empty($ids)) throw new Exception("参数错误");
			$flag = EbayProductAttributeTemplate::model()->batchDelete($ids);
			if ($flag) {
				$forward = Yii::app()->createUrl("/ebay/ebayattributetemplate/index");
				$jsonData = array(
						'message' 		=> 	Yii::t('system', 'Operate Successful'),
						'forward' 		=> 	$forward,
						'navTabId' 		=> 	'',
						'callbackType' 	=> 	''
				);
				echo $this->successJson($jsonData);
			}else{
				throw new Exception("操作失败！");
			}
		}catch(Exception $e){
			echo $this->failureJson(array(
					'message' => Yii::t('system', 'Operate failure')));
		}
		Yii::app()->end();
	}
}