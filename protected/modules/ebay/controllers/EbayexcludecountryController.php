<?php
/**
 * @desc ebay运输国家屏蔽
 * @author lihy
 *
 */
class EbayexcludecountryController extends UebController{
	
	public function actionIndex(){
		$model = UebModel::model("EbayExcludeShipingCountry");
		$this->render("index", array(
					'model'	=>	$model
		));			
	}
	
	
	public function actionCreate(){
		$siteID = Yii::app()->request->getParam("site_id", 0);
		try{
			$excludeShippingLocation = array();
			$selectedCountry = array();
			$listingSite = array();
			$model = UebModel::model("EbayExcludeShipingCountry");
			
			$excludeShippingLocation = EbayCategoryInfo::model()->getExcludeShippingLocation($siteID);
			$continents = $excludeShippingLocation['Worldwide'];
			unset($excludeShippingLocation['Worldwide']);
	
			$listingSite = EbaySite::getSiteList();
			$accountAll  = EbayAccountSite::model()->getAbleAccountListBySiteID($siteID);
			$accounts    = array();
			foreach($accountAll as $account){
				//TODO 排除锁定状态设定为无法刊登的账号
				$accounts[$account['id']] = $account['short_name'];
			}
			
			$this->render('create',
					array(
							'listingSite'				=>	$listingSite,
							'excludeShippingLocation'	=>	$excludeShippingLocation,
							'siteID'					=>	$siteID,
							'selectedCountry'			=>	$selectedCountry,
							'accounts'					=>	$accounts,
							'model'						=>	$model
					));
		}catch (Exception $e){
			echo $this->failureJson(array(
					'message'	=>	$e->getMessage()
			));
		}
	}
	
	public function actionUpdate(){
		$siteID = Yii::app()->request->getParam("site_id");
		$accountID = Yii::app()->request->getParam("account_id");
		$id = Yii::app()->request->getParam("id");
		try{
			$model = UebModel::model("EbayExcludeShipingCountry");
			//$excludeCountry = $model->getExcludeShipingCountry($siteID, $accountID);
			$excludeCountry = $model->getExcludeShipingCountryByID($id);
			$excludeShippingLocation = EbayCategoryInfo::model()->getExcludeShippingLocation($siteID);
			
			$continents = $excludeShippingLocation['Worldwide'];
			unset($excludeShippingLocation['Worldwide']);
			$listingSite = EbaySite::getSiteList();
			$selectedCountry = array();
			if(!empty($excludeCountry['exclude_ship_code'])){
				$selectedCountry = explode(",", $excludeCountry['exclude_ship_code']);
			}
			$accountAll  = EbayAccountSite::model()->getAbleAccountListBySiteID($siteID);
			$accounts    = array();
			foreach($accountAll as $account){
				//TODO 排除锁定状态设定为无法刊登的账号
				$accounts[$account['id']] = $account['short_name'];
			}
			$this->render('update',
					array(
							'listingSite'				=>	$listingSite,
							'excludeShippingLocation'	=>	$excludeShippingLocation,
							'siteID'					=>	$siteID,
							'accountID'					=>	$accountID,
							'selectedCountry'			=>	$selectedCountry,
							'accounts'					=>	$accounts,
							'id'						=>	$id,	
							'model'	=>	$model
					));
		}catch (Exception $e){
			echo $this->failureJson(array(
				'message'	=>	$e->getMessage()
			));
		}
	}
	
	public function actionSavedata(){
		try{
			$model = UebModel::model("EbayExcludeShipingCountry");
			$codeData = Yii::app()->request->getParam("code");
			$siteId = Yii::app()->request->getParam("site_id");
			$accountId = Yii::app()->request->getParam("account_id");
			$id = Yii::app()->request->getParam("id");
			$codes = array_keys($codeData);
			$countryCodes = implode(",", $codes);
			$countryNames = implode(",", $codeData);
			if(empty($countryCodes)) $countryCodes = "";
			if(empty($countryNames)) $countryNames = "";
			if(empty($accountId)){
				throw new Exception("帐号不能为空");
			}
			$data = array(
				'site_id'=>$siteId,
				'account_id'=>$accountId,
				'country_code'=>$countryCodes,
				'country_name'=>$countryNames
			);
			//判断是否已经存在
			if($model->checkExistsBySiteId($siteId, $accountId, $id)){
				throw new Exception("已经存在该站点账号");
			}
			if($id){
				$model->updateExcludeCountryDataById($id, $data);				
			}else{
				$model->saveExcludeCountryData($data);
			}
			echo $this->successJson(array('message'=>'保存成功！',
					'forward' => '/ebay/ebayexcludecountry/index',
					'navTabId' => 'page'.Menu::model()->getIdByUrl('/ebay/ebayexcludecountry/index'),
					'callbackType' => 'closeCurrent'
			));
		}catch (Exception $e){
			echo $this->failureJson(array(
					'message'	=>	$e->getMessage()
			));
		}
	}
	
	
	public function actionGetexcludecountryInfo(){
		$siteID = Yii::app()->request->getParam("site_id");
		try{
			$excludeShippingLocation = array();
			$selectedCountry = array();
			$listingSite = array();
			$model = UebModel::model("EbayExcludeShipingCountry");
				
			$excludeShippingLocation = EbayCategoryInfo::model()->getExcludeShippingLocation($siteID);
			if(empty($excludeShippingLocation)){
				throw new Exception("获取该站点的国家列表失败！");
			}
			$continents = $excludeShippingLocation['Worldwide'];
			unset($excludeShippingLocation['Worldwide']);
			
			
			$accountAll  = EbayAccountSite::model()->getAbleAccountListBySiteID($siteID);
			$accounts    = array();
			foreach($accountAll as $account){
				//TODO 排除锁定状态设定为无法刊登的账号
				$accounts[$account['id']] = $account['short_name'];
			}
			echo $this->successJson(array(
					'message'					=>	'success',
					'accountList' 				=> 	$accounts,
					'excludeShippingLocation'	=>	$excludeShippingLocation
				)
			);
		}catch (Exception $e){
			echo $this->failureJson(array(
					'message'	=>	$e->getMessage()
			));
		}
	}
	
	
	public function actionTest(){
		$model = UebModel::model("EbayExcludeShipingCountry");
		
		$listingSite = EbaySite::getSiteList();
		if($listingSite){
			foreach ($listingSite as $siteID=>$siteName){
				if($siteID == 77){
					$excludeCountry = $model->getExcludeShipingCountryByID(1);
				}else{
					$excludeCountry = $model->getExcludeShipingCountryByID(2);
				}
				$accountAll  = EbayAccountSite::model()->getAbleAccountListBySiteID($siteID);
				foreach($accountAll as $account){
					$accountId = $account['id'];
					//判断是否已经存在
					if($model->checkExistsBySiteId($siteID, $accountId)){
						continue;
					}
					$data = array(
							'site_id'=>$siteID,
							'account_id'=>$accountId,
							'country_code'=>$excludeCountry['exclude_ship_code'],
							'country_name'=>$excludeCountry['exclude_ship_name']
					);
					$model->saveExcludeCountryData($data);
				
				}
			}
		}
		
	}
}