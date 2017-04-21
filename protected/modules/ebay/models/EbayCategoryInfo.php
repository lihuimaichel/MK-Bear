<?php
/**
 * @desc Ebay分类管理
 * @author Gordon
 * @since 2015-07-25
 */
class EbayCategoryInfo extends EbayModel{
    
    /**@var 事件名称*/
    const CATEGORY_FEATURES_EVENT_NAME = 'get_category_features';
    const CATEGORY_SPECIFICS_EVENT_NAME = 'get_category_specifics';
    /**@var 分类额外信息 */
    const CATEGORY_FEATURES = 'category_features';
    const CATEGORY_SPECIFICS = 'category_specifics';
    
    /** @var int 账号ID*/
    public $_accountID = null;
    
    /** @var int 站点ID*/
    public $_siteID = 0;
    
    /** @var string 异常信息*/
    public $_exception = null;
    
    public $needSpecificArr = array('BRAND','MPN');
    public $needSpecificNames = array(
    		'BRAND' => array('Name'=>'Brand',
    				'ValidationRules'=>array('ValueType'=>'Text','MinValues'=>'1','MaxValues'=>'1','SelectionMode'=>'FreeText','VariationSpecifics'=>'Disabled'),
    				'ValueRecommendation'=>array('Value'=>'Unbranded','ValidationRules'=>'')
    		),
    		'MPN' => array('Name'=>'MPN',
    				'ValidationRules'=>array('ValueType'=>'Text','MinValues'=>'1','MaxValues'=>'1','SelectionMode'=>'FreeText','VariationSpecifics'=>'Enabled'),
    				'ValueRecommendation'=>array('Value'=>'Does Not Apply','ValidationRules'=>'')
    		),
    		'UPC' => array('Name'=>'UPC',
    				'ValidationRules'=>array('ValueType'=>'Text','MinValues'=>'1','MaxValues'=>'1','SelectionMode'=>'FreeText','VariationSpecifics'=>'Disabled'),
    				'ValueRecommendation'=>array('Value'=>'Does Not Apply','ValidationRules'=>'')
    		),
    		'EAN' => array('Name'=>'EAN',
    				'ValidationRules'=>array('ValueType'=>'Text','MinValues'=>'1','MaxValues'=>'1','SelectionMode'=>'FreeText','VariationSpecifics'=>'Disabled'),
    				'ValueRecommendation'=>array('Value'=>'Does Not Apply','ValidationRules'=>'')
    		),
    		'ISBN' => array('Name'=>'ISBN',
    				'ValidationRules'=>array('ValueType'=>'Text','MinValues'=>'1','MaxValues'=>'1','SelectionMode'=>'FreeText','VariationSpecifics'=>'Disabled'),
    				'ValueRecommendation'=>array('Value'=>'Does Not Apply','ValidationRules'=>'')
    		),
    		'GTIN' => array('Name'=>'GTIN',
    				'ValidationRules'=>array('ValueType'=>'Text','MinValues'=>'1','MaxValues'=>'1','SelectionMode'=>'FreeText','VariationSpecifics'=>'Disabled'),
    				'ValueRecommendation'=>array('Value'=>'Does Not Apply','ValidationRules'=>'')
    		),
    
    );
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_ebay_category_info';
    }

    /**
     * @desc 获取特性IDs
     * @return multitype:string
     */
    public function getFeatureIDs(){
    	$FeatureIDs = array(
    			'ConditionEnabled',
    			'ConditionValues',
    			'VariationsEnabled',
    			'UPCEnabled',
    			'EANEnabled',
    			'ISBNEnabled',
    			'BrandMPNIdentifierEnabled'
    	);
    	return $FeatureIDs;
    }
    /**
     * @desc 获取分类特征,是否强制更新
     * @param unknown $siteID
     * @param unknown $categoryID
     * @param number $updateOnline
     * @param unknown $featureIDs
     * @return multitype:string NULL unknown Ambigous <string, boolean, NULL, unknown> |boolean
     */
    public function getCategoryFeatures($accountID, $siteID, $categoryID, $updateOnline = 0, $featureIDs = array()){
    	$info = $this->find("site_id='$siteID' AND var='". self::CATEGORY_FEATURES ."' AND category_id='$categoryID' AND update_time>'".date("Y-m-d H:i:s",time()-86400*15)."'");
    	
    	if(empty($info) || $updateOnline){//本地没有信息,或强制更新
    		$this->saveCategoryFeaturesData($categoryID, $accountID, $siteID);
    		$info = self::model()->find("site_id='$siteID' AND var='". self::CATEGORY_FEATURES ."' AND category_id='$categoryID'");
    	}
    	
    	if($info){
    		$result = array();
    		$resp = simplexml_load_string($info['value']);
    		if($resp->Ack=='Success' || $resp->Ack=='Warning'){
    			$currentFeatures = '';
    			foreach ($resp->Category as $category){//查询当前分类的features
    				if(trim($category->CategoryID) == $categoryID){
    					$currentFeatures = $category;
    					break;
    				}
    			}
    			if(empty($featureIDs)){
    				$featureIDs = $this->getFeatureIDs();
    			}
    			$notReturnFeatureIDS = array();
    			foreach ($featureIDs as $featureID){
    				$featureValue = isset($currentFeatures->$featureID) ? $currentFeatures->$featureID : '';
    				if($featureValue){
    					if(trim($featureValue)==''){
    						$result[$featureID] = $featureValue;
    					}else{
    						$result[$featureID] = trim($featureValue);
    					}
    				}else{
    					$categoryinfo = self::model("EbayCategory")->find("site_id='$siteID' AND category_id='{$categoryID}'");
    					if($categoryinfo['parent_id'] != $categoryinfo['category_id']){//找父类
    						$notReturnFeatureIDS[] = $featureID;
    					}else{//根目录
    						if(trim($resp->SiteDefaults->$featureID) == ''){
    							$result[$featureID] = $resp->SiteDefaults->$featureID;
    						}else{
    							$result[$featureID] = trim($resp->SiteDefaults->$featureID);
    						}
    					}
    				}
    			}
    			
    			if($notReturnFeatureIDS){//找父类
    				$parentFeatures = $this->getCategoryFeatures($siteID, $categoryinfo['parent_id'], $updateOnline, $notReturnFeatureIDS);
    				if($parentFeatures){
	    				foreach ($notReturnFeatureIDS as $featureID){
	    					$result[$featureID] = $parentFeatures[$featureID];
	    				}
    				}
    			}
    		}
    		return $result;
    	}else{
    		return false;
    	}
    }
    
    /**
     * @desc 获取分类属性
     * @param unknown $accountID
     * @param unknown $siteID
     * @param unknown $categoryID
     * @param number $updateOnline
     * @return multitype:mixed unknown |boolean
     */
    public function getCategorySpecifics($accountID, $siteID, $categoryID, $updateOnline = 0){
    	$info = self::model()->find("site_id='$siteID' AND var='". self::CATEGORY_SPECIFICS ."' AND category_id='$categoryID' AND update_time>'".date("Y-m-d H:i:s",time()-86400*15)."'");
    	if(empty($info) || $updateOnline){//本地没有信息,或强制更新
    		$this->saveCategorySpecificsData($categoryID, $accountID, $siteID);
    		$info = self::model()->find("site_id='$siteID' AND var='". self::CATEGORY_SPECIFICS ."' AND category_id='$categoryID'");
    	}
    	if($info){
    		$resp = simplexml_load_string($info['value']);
    			
    		$tmpSpecificArr = array();
    		foreach ($resp->Recommendations->NameRecommendation as $detail){
    			$tmpSpecificArr[] = strtoupper($detail->Name);
    		}
    		$diffSpecificArr = array_diff($this->needSpecificArr,$tmpSpecificArr);
    			
    		$result = array();
    		foreach ($resp->Recommendations->NameRecommendation as $detail){
    			$result[] = $detail;
    		}
    			
    		if( count($diffSpecificArr) > 0 ){
    			$tmpNeedSpecificNames = $this->needSpecificNames;
    			foreach( $diffSpecificArr as $key => $item ){
    				$tmpItem = json_encode($tmpNeedSpecificNames[$item]);
    				$result[] = json_decode($tmpItem);
    			}
    		}
    		return $result;
    	}else{
    		return false;
    	}
    }
    
    /**
     * @desc 保存分类特性
     * @param unknown $data
     */
  	public function saveCategoryFeaturesData($categoryID, $accountID, $siteID){
  		$respXml = "";
  		$getCategoryFeatureRequest = new GetCategoryFeaturesRequest();
  		$getCategoryFeatureRequest->setAccount($accountID);
  		$getCategoryFeatureRequest->setSiteID($siteID);
  		$getCategoryFeatureRequest->setCategoryID($categoryID);
  		$getCategoryFeatureRequest->setFeatureIDs($this->getFeatureIDs());
  		$getCategoryFeatureRequest->setIsXML(1);
  		$respXml = $getCategoryFeatureRequest->setRequest()->sendRequest()->getResponse();
  		$response = simplexml_load_string($respXml);
  		//print_r($respXml);
  		if( isset($response->Ack) && ($response->Ack=='Success' || $response->Ack=='Warning') ){
  			$data = array(
  					'var'			=>	self::CATEGORY_FEATURES,
  					'site_id' 		=> 	$siteID,
  					'category_id' 	=> 	$categoryID,
  					'value' 		=> 	$respXml,
  					'update_time' 	=> 	date('Y-m-d H:i:s'),
  			);
  			return $this->saveCategoryInfoData($data);
  		}else{
  			return false;
  		}
  	}
  	/**
  	 * @desc 保存分类属性
  	 * @param unknown $categoryID
  	 * @param unknown $accountID
  	 * @param unknown $siteID
  	 * @return Ambigous <Ambigous, number, boolean>|boolean
  	 */
  	public function saveCategorySpecificsData($categoryID, $accountID, $siteID){
  		$respXml = "";
  		$getCategorySpecificsRequest = new GetCategorySpecificsRequest();
  		$getCategorySpecificsRequest->setAccount($accountID);
  		$getCategorySpecificsRequest->setSiteID($siteID);
  		$getCategorySpecificsRequest->setCategoryID($categoryID);
  		$getCategorySpecificsRequest->setIsXML(1);
  		$respXml = $getCategorySpecificsRequest->setRequest()->sendRequest()->getResponse();
  		$response = simplexml_load_string($respXml);
  		if( isset($response->Ack) && ($response->Ack=='Success' || $response->Ack=='Warning') ){
  			$data = array(
  					'var'			=>	self::CATEGORY_SPECIFICS,
  					'site_id' 		=> 	$siteID,
  					'category_id' 	=> 	$categoryID,
  					'value' 		=> 	$respXml,
  					'update_time' 	=> 	date('Y-m-d H:i:s'),
  			);
  			return $this->saveCategoryInfoData($data);
  		}else{
  			return false;
  		}
  		
  	}
  	
  	/**
  	 * @desc 保存数据
  	 * @param unknown $data
  	 * @return Ambigous <number, boolean>
  	 */
  	public function saveCategoryInfoData($data){
  		$conditions = "var=:var AND site_id=:site_id AND category_id=:category_id";
  		$params = array(':var'=>$data['var'], ':site_id'=>$data['site_id'], ':category_id'=>$data['category_id']);
  		$checkExists = $this->getDbConnection()->createCommand()->from($this->tableName())->where($conditions, $params)->queryRow();
  		if($checkExists){
  			return $this->getDbConnection()->createCommand()->update($this->tableName(), $data, $conditions, $params);
  		}else{
  			return $this->getDbConnection()->createCommand()->insert($this->tableName(), $data);
  		}
  		
  	}
  	
  	// ======================= S:退货政策  ======================//
  	/**
  	 * @desc 从接口获取退货政策数据
  	 * @param unknown $accountID
  	 * @param unknown $siteID
  	 * @return boolean
  	 */
  	public function getEbayDetailsRequest($accountID, $siteID){
  		set_time_limit(100);
  		$request = new GeteBayDetailsRequest();
  		$respXml = $request->setAccount($accountID)->setSiteID($siteID)->setRequest()->sendRequest()->getResponse();
  		$response = simplexml_load_string($respXml);
  		if( isset($response->Ack) && ($response->Ack=='Success' || $response->Ack=='Warning') ){
  			//入库操作
  			$var = 'ebay_details';
  			$data = array(
  					'var' 			=> 	$var,
  					'site_id'	 	=> 	$siteID,
  					'value' 		=> 	$respXml,
  					'update_time' 	=> 	date('Y-m-d H:i:s'),
  					'category_id'	=>	''
  			);
  			$this->getDbConnection()->createCommand()->delete($this->tableName(), "var=:var and site_id=:site_id", array(':var'=>$var, ':site_id'=>$siteID));
  			$res = $this->getDbConnection()->createCommand()->insert($this->tableName(), $data);
  			if(!$res){
  				return false;
  			}
  		}else{
  			//@TODO 设置错误提示信息
  			return false;
  		}
  		return $respXml;
  	}
  	
  	/**
  	 * @desc 获取退货政策
  	 * @param unknown $siteID
  	 * @return Ambigous <multitype:, string>
  	 */
  	public function getReturnPolicyInfo($siteID){
  		$policyInfo = array();
  		$info = $this->getDbConnection()->createCommand()->from($this->tableName())->where("site_id=:site_id AND var=:var", array(':site_id'=>$siteID, ':var'=>'ebay_details'))->queryRow();
  		if(empty($info)){
  			 $response = $this->getEbayDetailsRequest($siteID, 11);
  			 if(!$response){
  			 	return array();
  			 }
  			 $info['value'] = $response;
  		}
  		$resp = simplexml_load_string($info['value']);
  	
  		foreach ($resp->ReturnPolicyDetails->ReturnsAccepted as $value){
  			$policyInfo['ReturnsAccepted'][trim($value->ReturnsAcceptedOption)] = trim($value->Description);
  		}
  		foreach ($resp->ReturnPolicyDetails->Refund as $value){
  			$policyInfo['RefundOptions'][trim($value->RefundOption)] = trim($value->Description);
  		}
  		foreach ($resp->ReturnPolicyDetails->ReturnsWithin as $value){
  			$policyInfo['ReturnsWithin'][trim($value->ReturnsWithinOption)] = trim($value->Description);
  		}
  		foreach ($resp->ReturnPolicyDetails->ShippingCostPaidBy as $value){
  			$policyInfo['ShippingCostPaidBy'][trim($value->ShippingCostPaidByOption)] = trim($value->Description);
  		}
  		return $policyInfo;
  	}
  	// ======================= E:退货政策  ======================//
  	
  	// ======================= S:物流信息  ======================//
  	/**
  	 * @desc 获取运送方式
  	 * @param unknown $siteID
  	 * @return Ambigous <multitype:, string>
  	 */
  	public function getShippingInfo($siteID){
  		$shippingInfo = array();
  	
  		$info = $this->getDbConnection()->createCommand()->from($this->tableName())->where("site_id=:site_id AND var=:var", array(':site_id'=>$siteID, ':var'=>'ebay_details'))->queryRow();
  		if(empty($info)){
  			$response = $this->getEbayDetailsRequest(11, $siteID);
  			if(!$response){
  				return array();
  			}
  			$info['value'] = $response;
  		}
  		$resp = simplexml_load_string($info['value']);
  	
  		foreach ($resp->ShippingLocationDetails as $value){
  			$shippingInfo['ShippingLocationDetails'][trim($value->ShippingLocation)] = trim($value->Description);
  		}
  		foreach($resp->ShippingServiceDetails as $service) {
  			//判断是国际运输还是本地运输
  			$type = $service->InternationalService?'InternationalServices':'DomesticServices';
  			$shippingCategory = trim($service->ShippingCategory);
  			$ShippingService = trim($service->ShippingService);
  			if($service->ShippingTimeMin || $service->ShippingTimeMax){
  				$description = trim($service->Description)."(".$service->ShippingTimeMin." to ".$service->ShippingTimeMax." business days)";
  			}else{
  				$description = trim($service->Description);
  			}
  			$shippingInfo[$type][$shippingCategory][$ShippingService] = $description;
  		}//end foreach
  		return $shippingInfo;
  	}
  	
  	/**
  	 * @desc 获取默认本地服务商
  	 * @param unknown $siteID
  	 * @return Ambigous <string>
  	 */
  	public function getDefaultLocalService($siteID){
  		$services = array(
  				'0' => 'EconomyShippingFromOutsideUS',
  				'2' => 'CA_StandardShippingfromoutsideCanada',
  				'3' => 'UK_EconomyShippingFromOutside',
  				'15' => 'AU_EconomyDeliveryFromOutsideAU',
  		);
  		return $services[$siteID];
  	}
  	
  	/**
  	 * @desc 获取国际服务商
  	 * @param unknown $siteID
  	 * @return Ambigous <string>
  	 */
  	public function getInternationalService($siteID){
  		$services = array(
  				'0' => 'StandardInternational',
  				'2' => 'CA_StandardInternational',
  				'3' => 'UK_SellersStandardInternationalRate',
  				'15' => 'AU_StandardInternational',
  		);
  		return $services[$siteID];
  	}
  	
  	// ======================= E:物流信息 ======================//
  	
  	// ======================= S:国家信息 =====================//
  	
  	public function getExcludeShippingLocation($siteID){
  		$accountID = 16;
  		$info = "";
  		$info = $this->getDbConnection()->createCommand()->from($this->tableName())->where("site_id=:site_id AND var=:var", array(':site_id'=>$siteID, ':var'=>'ebay_details'))->queryRow();
  		if(empty($info)){
  			$response = $this->getEbayDetailsRequest($accountID, $siteID);
  			if(!$response){
  				return array();
  			}
  			$info['value'] = $response;
  		}
  		$resp = simplexml_load_string($info['value']);
  		$excludeShippingLocations = array();
  		if($resp->ExcludeShippingLocationDetails){
  			foreach ($resp->ExcludeShippingLocationDetails as $location){
  				$excludeShippingLocations[(string)$location->Region][] = array('code'=>(string)$location->Location, 'name'=>(string)$location->Description);
  			}
  		}
  		return $excludeShippingLocations;
  	}
  	
  	public function getExcludeShippingLocationPairs($siteID){
  		$accountID = 16;
  		$info = "";
  		$info = $this->getDbConnection()->createCommand()->from($this->tableName())->where("site_id=:site_id AND var=:var", array(':site_id'=>$siteID, ':var'=>'ebay_details'))->queryRow();
  		if(empty($info)){
  			$response = $this->getEbayDetailsRequest($accountID, $siteID);
  			if(!$response){
  				return array();
  			}
  			$info['value'] = $response;
  		}
  		$resp = simplexml_load_string($info['value']);
  		$excludeShippingLocations = array();
  		if($resp->ExcludeShippingLocationDetails){
  			foreach ($resp->ExcludeShippingLocationDetails as $location){
  				$excludeShippingLocations[(string)$location->Region][(string)$location->Location] = array('code'=>(string)$location->Location, 'name'=>(string)$location->Description);
  			}
  		}
  		return $excludeShippingLocations;
  	}
  	
  	public function getExcludeShippingLocationCodePairs($siteID, $includeCode = array()){
  		$accountID = 16;
  		$info = "";
  		$info = $this->getDbConnection()->createCommand()->from($this->tableName())->where("site_id=:site_id AND var=:var", array(':site_id'=>$siteID, ':var'=>'ebay_details'))->queryRow();
  		if(empty($info)){
  			$response = $this->getEbayDetailsRequest($accountID, $siteID);
  			if(!$response){
  				return array();
  			}
  			$info['value'] = $response;
  		}
  		$resp = simplexml_load_string($info['value']);
  		$excludeShippingLocations = array();
  		if($resp->ExcludeShippingLocationDetails){
  			foreach ($resp->ExcludeShippingLocationDetails as $location){
  				if(!empty($includeCode) && !in_array($location->Location, $includeCode)) continue;
  				$excludeShippingLocations[(string)$location->Location] = (string)$location->Description;
  			}
  		}
  		return $excludeShippingLocations;
  	}
  	// ======================= E:国家信息 ====================//
}