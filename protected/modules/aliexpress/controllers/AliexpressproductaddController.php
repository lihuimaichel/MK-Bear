<?php
/**
 * @desc aliexpress 产品上传控制器
 * @author zhangF
 *
 */
class AliexpressproductaddController extends UebController {
	/**
	 * @desc 待刊登列表显示
	 */
	public function actionList() {
		$model = UebModel::model('AliexpressProductAdd');
		$this->render('list',array('model'=> $model));
	}
	
	/**
	 * 刊登第一步，填写刊登的sku
	 */
	public function actionStep1() {
		$dialog = Yii::app()->request->getParam('dialog');
		$this->render('step1', array('dialog' => $dialog));
	}
	
	/**
	 * 刊登第二步，选择刊登账号
	 */
	public function  actionStep2() {
		//error_reporting(E_ALL);
		//ini_set("display_errors", true);
		$sku = trim(Yii::app()->request->getParam('publish_sku'));
		//刊登模式列表
		$publishModeList = AliexpressProductAdd::getProductPublishModelList();
		//刊登类型列表
		$publishTypeList = AliexpressProductAdd::getProductPublishTypeList();
		//sku信息
		$skuInfo = Product::model()->getProductInfoBySku($sku);
		if (empty($skuInfo)) {
			echo $this->failureJson(array(
				'message' => Yii::t('aliexpress', 'SKU Has not Exists'),
				'navTabId' => AliexpressProductAdd::getIndexNavTabId(),
			));
			Yii::app()->end();
		}

		//检查多属性是否为纯电池、危险品、超尺寸、超重量
		$isTrue = ProductSelectAttribute::model()->getForbiddenAttribute($sku,Platform::CODE_ALIEXPRESS);
		if($isTrue){
			echo $this->failureJson(array('message' => '产品属性为禁止刊登的属性'));
			Yii::app()->end();
		}
		
		//检测是否有权限去刊登该sku
		//上线后打开注释---lihy 2016-12-14
		if(! Product::model()->checkCurrentUserAccessToSaleSKU($sku, Platform::CODE_ALIEXPRESS)){
			echo $this->failureJson(array(
					'message' => Yii::t('system', 'Not Access to Add the SKU'),
					'navTabId' => AliexpressProductAdd::getIndexNavTabId(),
			));
			Yii::app()->end();
		}
		
//		$config = ConfigFactory::getConfig('serverKeys');
//		//sku图片加载
//		$imageType = array('zt','ft');
//		$skuImg = array('zt'=>array());
//		foreach($imageType as $type){
//			$images = Product::model()->getImgList($sku,$type);
//			foreach($images as $k=>$img){
//				if($k == $sku) continue;
//				$skuImg[$type][$k] = $config['oms']['host'].$img;
//			}
//		}
		$skuImg = ProductImageAdd::getOrPushImageUrlFromRestfulBySku($skuInfo, $pushWithChild = true, $typeAlisa = null, $type = 'normal', $width = 100, $height = 100, $platform = Platform::CODE_ALIEXPRESS);

        /**
         * 修复java api接口无主图返回问题
         */
        if (isset($skuImg['ft']) && !isset($skuImg['zt'])) {
            $skuImg['zt'] = $skuImg['ft'];
        }

		$account_id = Yii::app()->request->getParam('account_id', 0);
		$this->render('step2', array(
			'publishModeList' => $publishModeList,
			'publishTypeList' => $publishTypeList,
			'sku' => $sku,
			'skuInfo'	=> $skuInfo,
			'skuImg' => $skuImg,
			'account_id' => $account_id,
		));
	}
	
	/**
	 * 刊登第三步，填写刊登数据
	 */
	public function actionStep3() {
		$sku = Yii::app()->request->getParam('publish_sku');
		$accounts = Yii::app()->request->getParam('accounts');
		$publishType = Yii::app()->request->getParam('publish_type');
		$publishMode = Yii::app()->request->getParam('publish_mode');
		//检查多属性是否为纯电池、危险品、超尺寸、超重量
		$isTrue = ProductSelectAttribute::model()->getForbiddenAttribute($sku,Platform::CODE_ALIEXPRESS);
		if($isTrue){
			echo $this->failureJson(array('message' => '产品属性为禁止刊登的属性'));
			Yii::app()->end();
		}
		//检查刊登账号是否设置
		if (empty($accounts)) {
				echo $this->failureJson(array(
					'message' => Yii::t('aliexpress_product', 'Have not Chosen Account'),
				));
				Yii::app()->end();
		}

		$accountInfos = AliexpressAccount::model()->getAccountInfoByIds($accounts);
		//检测是否有权限去刊登该sku --- yangsh 2016-12-14
		foreach ($accountInfos as $accountInfo) {	
			if(! Product::model()->checkCurrentUserAccessToSaleSKUNew($sku, $accountInfo['id'], Platform::CODE_ALIEXPRESS)){
				echo $this->failureJson(array(
					'message' => Yii::t('system', 'Not Access to Add the SKU'),
				));
				Yii::app()->end();
			}
		}

		$publishParams = array(
			'sku' => $sku,
			'publish_type' => array(
					'id' => $publishType,
					'text' => AliexpressProductAdd::getProductPublishTypeList($publishType),
			),
			'publish_mode' => array(
					'id' => $publishMode,
					'text' => AliexpressProductAdd::getProductPublishModelList($publishMode),
			),
		);
		//查找产品历史分类
		$historyCategoryList = AliexpressProduct::model()->getSkuHistoryCategory($sku);
		//默认历史分类
		$defaultHistoryCategory = array();
		if (!empty($historyCategoryList)) {
			$defaultHistoryCategory = array_slice($historyCategoryList, 0, 1, true);
		}
		//查找产品顶级分类列表
		$categoryList = array();
		$topCategories = AliexpressCategory::model()->getCategoryByCreateLevel(1);
		if (!empty($topCategories)) {
			foreach ($topCategories as $category) {
				$categoryList[$category->category_id] = $category->en_name . '(' . $category->cn_name . ')';
			}
		}
		//查找产品信息
		$skuInfo = Product::model()->getProductInfoBySku($sku);

		if ($skuInfo['product_is_multi'] == Product::PRODUCT_MULTIPLE_MAIN) {
			if ($publishType != AliexpressProductAdd::PRODUCT_PUBLISH_TYPE_VARIATION) {
				//多属性产品主SKU不能刊登一口价
				echo $this->failureJson(array(
					'message' => Yii::t('aliexpress_product', 'Main Product Could not Publish Fixed Price'),
				));
				Yii::app()->end();
			}
		}

		//验证主sku
		Product::model()->checkPublishSKU($publishType, $skuInfo);

		//获取产品图片
//		$imageType = array('zt', 'ft');
//		$config = ConfigFactory::getConfig('serverKeys');
//		$skuImg = array();
//		$skuImg = array('zt'=>array());
//		foreach($imageType as $type){
//			$images = Product::model()->getImgList($sku,$type);
//			foreach($images as $k=>$img){
//				if($k == $sku) continue;
//				$skuImg[$type][$k] = $img;
//			}
//		}
        $skuImg = ProductImageAdd::getImageUrlFromRestfulBySku($sku, $typeAlisa = null, $type = 'normal', $width = 100, $height = 100, $platform = Platform::CODE_ALIEXPRESS);

        /**
         * 修复java api接口无主图返回问题
         */
        if (isset($skuImg['ft']) && !isset($skuImg['zt'])) {
            $skuImg['zt'] = $skuImg['ft'];
        }

		//查找需要刊登的账号信息
		$accountList = array();
		//$accountInfos = AliexpressAccount::model()->getAccountInfoByIds($accounts);
		$overseasWarehouseList = '';
		foreach ($accountInfos as $accountInfo) {	
			$accountList[$accountInfo->id]['account_name'] = $accountInfo->short_name;
			//查找账号对应的分组列表
			$groupList = AliexpressGroupList::model()->getGroupTree($accountInfo['id'], 0);
			$accountList[$accountInfo->id]['group_list'] = $groupList;
			$accountList[$accountInfo->id]['product_keywords'] = '';
			$accountList[$accountInfo->id]['product_keywords1'] = '';
			$accountList[$accountInfo->id]['product_keywords2'] = '';
			//查找账号的参数模板
			$data = array(
					'sku' => $sku,
					'platform_code' => Platform::CODE_ALIEXPRESS,
					'account_id' => $accountInfo->id,
			);
			$ruleModel = new ConditionsRulesMatch();
			$ruleModel->setRuleClass(TemplateRulesBase::MATCH_PARAM_TEMPLATE);
			$paramsTemplateID = $ruleModel->runMatch($data);
			if (empty($paramsTemplateID) || !($paramsTemplate = AliexpressParamTemplate::model()->getParamTemplateByID($paramsTemplateID))) {
				echo $this->failureJson(array(
						'message' => Yii::t('aliexpress_product', 'Could not Find Params Template'),
				));
				Yii::app()->end();
			}
			$htmlOptions = array();
			//查找账号对应的服务模板列表
			$serviceList = CHtml::listOptions($paramsTemplate['promise_template_id'], 
							CHtml::listData(AliexpressPromiseTemplate::model()->findAll("account_id = :account_id", array(':account_id' => $accountInfo->id)), 'template_id', 'name'), $htmlOptions);
			$accountList[$accountInfo->id]['service_list'] = $serviceList;
			//查找账号对应的运费模板
			$freightList = CHtml::listOptions($paramsTemplate['freight_template_Id'], 
										CHtml::listData(AliexpressFreightTemplate::model()->findAll("account_id = :account_id", array(':account_id' => $accountInfo->id)), 'template_id', 'template_name'), 
										$htmlOptions);
			$accountList[$accountInfo->id]['freight_list'] = $freightList;
			$overseas_warehouse_list = AliexpressAccount::getAccountOvseasWarehouseListById($accountInfo->id);
			$accountList[$accountInfo->id]['overseas_warehouse_list'] = $overseas_warehouse_list;
			
			//查找描述模板
			//获取最优描述模板
			$data = array(
					'sku' => $sku,
					'platform_code' => Platform::CODE_ALIEXPRESS,
					'account_id' => $accountInfo->id,
			);
			$ruleModel = new ConditionsRulesMatch();
			$ruleModel->setRuleClass(TemplateRulesBase::MATCH_DESCRI_TEMPLATE);
			$descriptionTemplateID = $ruleModel->runMatch($data);
			if (empty($descriptionTemplateID) || !($descriptTemplate = DescriptionTemplate::model()->getDescriptionTemplateByID($descriptionTemplateID))) {
				echo $this->failureJson(array(
						'message' => Yii::t('aliexpress_product', 'Could not Find Description Template'),
				));
				Yii::app()->end();
			}
			//设置产品描述
			$description = $skuInfo['description']['english'];
			$include = $skuInfo['included']['english'];
			$content = $descriptTemplate['template_content'];
			$title = $descriptTemplate['title_prefix'] . ' ' . $skuInfo['title']['english'] . ' ' . $descriptTemplate['title_suffix'];
			$accountList[$accountInfo->id]['product_title'] = $title;
			//产品附图
			$additionalImageList = array();
			if (empty($additionalImageList))
				$additionalImageList = array();
			$description = DescriptionTemplate::model()->getDescription($content, $description, $title, $include, $additionalImageList);					
			$accountList[$accountInfo->id]['description'] = $description;
			$overseasWarehouseList = AliexpressAccount::getAccountOvseasWarehouseListById($accountInfo->id);
		}
		//print_r($overseasWarehouseList);

		//产品重量，包装尺寸
		$skuInfo['gross_weight'] = $skuInfo['product_weight'] > 0 ? round($skuInfo['product_weight'] / 1000, 3) : 0.000;
		$skuInfo['package_length'] = $skuInfo['pack_product_length'] > 0 ? round($skuInfo['pack_product_length'] / 10) : 0;
		$skuInfo['package_width'] = $skuInfo['pack_product_width'] > 0 ? round($skuInfo['pack_product_width'] / 10) : 0;
		$skuInfo['package_height'] = $skuInfo['pack_product_height'] > 0 ? round($skuInfo['pack_product_height'] / 10) : 0;
		//精简模式刊登
		$htmlOptions = array();
		if ($publishMode == AliexpressProductAdd::PRODUCT_PUBLISH_MODE_EASY) {
			$this->render('_formeasy', array(
				'action' => 'create',
				'imgomsURL'	=> '',//	$config['oms']['host'],
				'publishParams' => $publishParams,
				'historyCategoryList' => $historyCategoryList,
				'defaultHistoryCategory' => $defaultHistoryCategory,
				'chooseCategoryList' => $categoryList,
				'skuImg' => $skuImg,
				'accountList' => $accountList,
				'overseasWarehouseList' => $overseasWarehouseList,
				'skuInfo' => $skuInfo,
				'productUnitList' => CHtml::listOptions($paramsTemplate['product_unit'], AliexpressParamTemplate::getProductUnit(), $htmlOptions)
			));
		}
	}
	
	public function actionUpdateOnline() {
	    $addID = Yii::app()->request->getParam('id');
	    $addType = Yii::app()->request->getParam('type');
	    $aliProductModel = new AliexpressProduct;
	    $prodcutInfo = $aliProductModel->getOneByCondition('account_id,sku,aliexpress_product_id','aliexpress_product_id = ' . $addID);
	    if(!$prodcutInfo){
	        echo $this->failureJson(array('message'=>'信息错误'));
	        exit;
	    }
	    
	    $productId = $prodcutInfo['aliexpress_product_id'];
	    $aliProductDownloadModel = new AliexpressProductDownload();
	    $aliProductDownloadModel->setAccountID($prodcutInfo['account_id']);
	    $response = $aliProductDownloadModel->findAeProductById($productId);
	    $responseArray = json_decode(json_encode($response),true);
	    //debug
	    //$this->print_r($response);
	    //exit();
	    if(!isset($response->detail)){
	        echo $this->failureJson(array('message'=>'接口信息获取错误'));
	        exit;
	    }
	    $getDetail = $response->detail;
	    $sku = $addType == 2 ? reset(explode('.', $responseArray['aeopAeProductSKUs'][0]['skuCode'])) : $responseArray['aeopAeProductSKUs'][0]['skuCode'];
	    //print_r($sku);
	    //exit();
	    $accountID = $prodcutInfo['account_id'];
	    $publishType = $addType;
	    $publishMode = 1;
	    $categoryID = $response->categoryId;
	    $publishParams = array(
	        'sku' => $sku,
	        'publish_type' => array(
	            'id' => $publishType,
	            'text' => AliexpressProductAdd::getProductPublishTypeList($publishType),
	        ),
	        'publish_mode' => array(
	            'id' => $publishMode,
	            'text' => AliexpressProductAdd::getProductPublishModelList($publishMode),
	        ),
	    );
	    //查找产品历史分类
	    $historyCategoryList = AliexpressProduct::model()->getSkuHistoryCategory($sku);
	    //默认历史分类
	    $historyCategoryList[$categoryID] = AliexpressCategory::model()->getBreadcrumbCnAndEn($categoryID);
	    $defaultHistoryCategory = array();
	    if (!empty($historyCategoryList)) {
	        $defaultHistoryCategory = array($categoryID => $historyCategoryList[$categoryID]);
	    }
	    //获取分类下sku属性
	    $skuAttributes = AliexpressAttribute::model()->getCategoryAttributeList($categoryID, AliexpressAttribute::ATTRIBUTE_TYPE_SKU);
	    
	    //获取刊登记录的SKU属性 
	    if (isset($responseArray['aeopAeProductSKUs']))
	    foreach ($responseArray['aeopAeProductSKUs'] as $row) {
	                foreach ($row['aeopSKUProperty'] as $key => $values) {
	                        $skuAttributes[$values['skuPropertyId']]['value_list'][$values['propertyValueId']]['selected'] = true;
	                        $skuAttributes[$values['skuPropertyId']]['value_list'][$values['propertyValueId']]['sku']   = $row['skuCode'];
	                        $skuAttributes[$values['skuPropertyId']]['value_list'][$values['propertyValueId']]['price'] = $row['skuPrice'];
	                }
	    }
	    
	    
	    //print_r($skuAttributes);exit;
	    //获取分类下普通属性
	    $commonAttributes = AliexpressAttribute::model()->getCategoryAttributeList($categoryID);
	    //获取刊登记录普通属性
	    //$addCommonAttributes = AliexpressProductAddAttribute::model()->getProductAttributes($addID);
	    
	    //查找自定义属性
	    //$customAttributes = AliexpressProductAddAttribute::model()->getProductAttributes($addID, 1);
	    $customAttributes = array();
	    
	    if (isset($responseArray['aeopAeProductPropertys']))
	    foreach ($responseArray['aeopAeProductPropertys'] as $PropertysValue){
	            isset($PropertysValue['attrNameId']) && $attributeID      = $PropertysValue['attrNameId'];
	            $attributeName = '';
	            isset($PropertysValue['attrValueId']) && $attributeValueID = $PropertysValue['attrValueId'];
	            if (array_key_exists($attributeID, $commonAttributes)) {
	                if (empty($commonAttributes[$attributeID]['value_list'])) {
	                    isset($PropertysValue['attrValue']) && $commonAttributes[$attributeID]['value_list'] = $PropertysValue['attrValue'];
	                } else if (isset($PropertysValue['attrValueId']) && isset($PropertysValue['attrNameId'])){
	                    $commonAttributes[$attributeID]['value_list'][$attributeValueID]['selected'] = true;
	                    $commonAttributes[$attributeID]['value_list'][$attributeValueID]['sku'] = '';
	                    $commonAttributes[$attributeID]['value_list'][$attributeValueID]['value_name'] = isset($PropertysValue['attrValue']) ? $PropertysValue['attrValue'] : '' ;
	                }
	            }
	            if (isset($PropertysValue['attrName'])){
	                $customAttributesArray = array();
	                isset($PropertysValue['attrName'])   && $customAttributesArray['attribute_name'] = $PropertysValue['attrName'];
	                isset($PropertysValue['attrNameId']) && $customAttributesArray['attribute_id']   = $PropertysValue['attrNameId'];
	                isset($PropertysValue['attrValue'])  && $customAttributesArray['value_name']     = $PropertysValue['attrValue'];
	                isset($PropertysValue['attrValue'])  && $customAttributesArray['value_id']       = $PropertysValue['attrValue'];
	                $customAttributes[] = $customAttributesArray;
	            }
	    }	
	
	    
	
	    /*  		echo '<pre>';
	     print_r($commonAttributes);exit; */
	    //查找产品顶级分类列表
	    $categoryList = array();
	    $topCategories = AliexpressCategory::model()->getCategoryByCreateLevel(1);
	    if (!empty($topCategories)) {
	        foreach ($topCategories as $category) {
	            $categoryList[$category->category_id] = $category->en_name . '(' . $category->cn_name . ')';
	        }
	    }
	    //查找产品信息
	    $skuInfo = Product::model()->getProductInfoBySku($sku);
	
	    //获取产品需要上传的图片
	
/*	    $config = ConfigFactory::getConfig('serverKeys');
	    $skuImg = array();*/

        $skuImg = ProductImageAdd::getImageUrlFromRestfulBySku($sku, $typeAlisa = null, $type = 'normal', $width = 100, $height = 100, $platform = Platform::CODE_ALIEXPRESS);
	    /**
	     * 修复java api接口无主图返回问题
	    */
	    if (isset($skuImg['ft']) && !isset($skuImg['zt'])) {
	        $skuImg['zt'] = $skuImg['ft'];
	    }
	
	    $images = AliexpressProductImageAdd::model()->getImageBySku($sku, $accountID, Platform::CODE_ALIEXPRESS);
	
	    $selectedImages = array();
	    foreach($images as $type => $rows){
	        foreach ($rows as $row) {
	            if ($type == ProductImages::IMAGE_ZT)
	                $key = 'zt';
	            else
	                $key = 'ft';
	            //$skuImg[$key][$row['image_name']] = $row['local_path'];
	            $selectedImages[$key][] = $row['image_name'];
	        }
	    }
	        //查找需要刊登的账号信息
	        $accountList = array();
	        $accountInfos = AliexpressAccount::model()->getAccountInfoByIds(array($accountID));
	        foreach ($accountInfos as $accountInfo) {
	            $accountList[$accountInfo->id]['account_name'] = $accountInfo->short_name;
	            //查找账号对应的分组列表
	            if (isset($response->groupId)){
	                $groupList = AliexpressGroupList::model()->getGroupTree($accountInfo['id'], 0, 0, $response->groupId);
	                $accountList[$accountInfo->id]['group_list'] = $groupList;
	                
	            }
	            $accountList[$accountInfo->id]['product_title'] = $response->subject;
	            $accountList[$accountInfo->id]['product_keywords']  = '';
	            $accountList[$accountInfo->id]['product_keywords1'] = '';
	            $accountList[$accountInfo->id]['product_keywords2'] = '';
	            $accountList[$accountInfo->id]['product_price'] = $response->productPrice;
	            
	            //查找账号对应的服务模板列表
	            $htmlOptions = array();
	            if (isset($response->promiseTemplateId)){
	                $serviceList = CHtml::listOptions($response->promiseTemplateId, CHtml::listData(AliexpressPromiseTemplate::model()->findAll("account_id = :account_id", array(':account_id' => $accountInfo->id)), 'template_id', 'name'), $htmlOptions);
	                $accountList[$accountInfo->id]['service_list'] = $serviceList;
	            }
	            
	            //查找账号对应的运费模板
	            if (isset($response->freightTemplateId)){
	                $freightList = CHtml::listOptions($response->freightTemplateId, CHtml::listData(AliexpressFreightTemplate::model()->findAll("account_id = :account_id", array(':account_id' => $accountInfo->id)), 'template_id', 'template_name'), $htmlOptions);
	                $accountList[$accountInfo->id]['freight_list'] = $freightList;
	            }	            
	            $accountList[$accountInfo->id]['description'] = $response->detail;
	        }
	
	        //精简模式刊登
	        if ($publishMode == AliexpressProductAdd::PRODUCT_PUBLISH_MODE_EASY) {
	            $htmlOptions = array();
	            $this->render('_formeasy_online', array(
	                'imgomsURL'	=>'',	//$config['oms']['host'],
	                'publishParams' => $publishParams,
	                'historyCategoryList' => $historyCategoryList,
	                'defaultHistoryCategory' => $defaultHistoryCategory,
	                'chooseCategoryList' => $categoryList,
	                'skuImg' => $skuImg,
	                'accountList' => $accountList,
	                'skuInfo' => $skuInfo,
	                'addInfo' => $responseArray,
	                'action' => 'update',
	                'skuAttributes' => $skuAttributes,
	                'commonAttributes' => $commonAttributes,
	                'productUnitList' => CHtml::listOptions($response->productUnit, AliexpressParamTemplate::getProductUnit(), $htmlOptions),
	                'customAttributes' => $customAttributes,
	                'variationAttributes' => $responseArray['aeopAeProductSKUs'],
	                //'recycleImages' => $recycleImages,
	                'selectedImages'	=>	$selectedImages
	            ));
	        }
	}	
	
	public function actionAjax() {
	    set_time_limit(2*3600);
		ini_set("display_errors", true);
		error_reporting(E_ALL);		
	    
		$return = array('status'=>'success','msg'=>'');
	    //$this->print_r($_POST);
	    if ($_POST){
	        $onlineData = json_decode(base64_decode(trim(Yii::app()->request->getParam('onlineData'))),1);
	        //$this->print_r($onlineData);  
	        //exit();
	        $requestAccountID = reset(Yii::app()->request->getParam('account_id'));
	        $publishSku  = Yii::app()->request->getParam('publish_sku');
	        $publishType = Yii::app()->request->getParam('publish_type');
	        
	        $request = new EditMutilpleProductRequest();
	        
	        //简单属性设置	        
	        $request->setProductId(Yii::app()->request->getParam('id'));
	        $request->setCategoryID(Yii::app()->request->getParam('category_id'));	        
	        $request->setLotNum(Yii::app()->request->getParam('lot_num'));
	        $request->setGrossWeight(Yii::app()->request->getParam('gross_weight'));
	        $request->setPackageLength(Yii::app()->request->getParam('package_length'));
	        $request->setPackageWidth(Yii::app()->request->getParam('package_width'));
	        $request->setPackageHeight(Yii::app()->request->getParam('package_height'));
	        $request->setProductUnit(Yii::app()->request->getParam('product_unit'));
			$request->setDeliveryTime(5);
			$request->setCurrencyCode($onlineData['currencyCode']);
	        
	        //简单属性设置,绑定用户的
			$publishType == 1 && $request->setProductPrice(reset(Yii::app()->request->getParam('product_price')));
	        $request->setSubject(reset(Yii::app()->request->getParam('subject')));
	        $request->setGroupId(reset(Yii::app()->request->getParam('group_id')));
	        $request->setDetail(reset(Yii::app()->request->getParam('detail')));
	        $request->setFreightTemplateId(reset(Yii::app()->request->getParam('freight_template_id')));
	        $request->setPromiseTemplateId(reset(Yii::app()->request->getParam('service_template_id')));
	        
            //复杂属性设置
            $skuAttributes = Yii::app()->request->getParam('sku_attributes');
            $variationAttributes = Yii::app()->request->getParam('variation_attributes');
            $variationCustomName = Yii::app()->request->getParam('variation_custom_name');
            $variationSkus = Yii::app()->request->getParam('variation_skus');
            $variationPrice = Yii::app()->request->getParam('variation_price');
            $commonAttributes = Yii::app()->request->getParam('common_attributes');
            $commonAttributesCustomValue = Yii::app()->request->getParam('common_attributes_custom_value');
            $customAttributeName = Yii::app()->request->getParam('custom_attribute_name');
            $customAttributeValue = Yii::app()->request->getParam('custom_attribute_value');
            $skuImage = Yii::app()->request->getParam('skuImage');
            
            //设置sku属性
            $skuPropertys = array(); 
            if (isset($variationAttributes)) 
            foreach ($variationAttributes as $key => $val){
                $skuPropertysEach = array();
                $aeopSKUProperty = array();
                $id = '';
                $i = 0;
                foreach ($val as $keyID => $valID){
                    $id .= "{$keyID}:{$valID};";
                    $aeopSKUPropertyEach = array();
                    $aeopSKUPropertyEach['propertyValueId'] = (int)($valID);
                    if ($i == 0){
                        $aeopSKUPropertyEach['propertyValueDefinitionName'] = $variationCustomName[$key];
                    }                    
                    $aeopSKUPropertyEach['skuPropertyId'] = $keyID;
                    $aeopSKUProperty[] = $aeopSKUPropertyEach;
                    $i++;
                }
                $id = substr($id, 0,strlen($id) - 1);
                $skuPropertysEach['id'] = $id;
                $skuPropertysEach['currencyCode']  = $onlineData['currencyCode'];                
                //暂不开放修改价格,一旦修改错误后果很严重
                //开放修改价格，但是会记录详细修改日志
                $skuPropertysEach['skuPrice']      = $variationPrice[$key];
                $skuPropertysEach['skuCode']       = $variationSkus[$key];
                $skuPropertysEach['aeopSKUProperty'] = $aeopSKUProperty;
                
                $skuPropertys[] = $skuPropertysEach;
            }
            //$this->print_r($skuPropertys); 
            //$request->setAeopAeProductSKUs($onlineData['aeopAeProductSKUs']);
            $request->setAeopAeProductSKUs($skuPropertys);
            
            //设置产品属性
            $ProductPropertys = array();
            //$this->print_r($commonAttributes); 
            //$this->print_r($commonAttributesCustomValue); 
            if (isset($commonAttributes)) 
            foreach ($commonAttributes as $key => $val){
                $ProductPropertysEach = array();
                $resetVal = '';
                if (is_array($val)){
                    $resetVal = (int)reset($val);
                    if (empty($resetVal)){
                        $return['status'] = 'failure';
                        $return['msg'] = '提交失败！您有未选择的选项。';
                        echo json_encode($return);
                        Yii::app()->end();
                    } 
                }
                
                //$this->print_r($resetVal); 
                //$this->print_r($commonAttributesCustomValue[$resetVal][$resetVal]); exit();
                isset($commonAttributesCustomValue[$resetVal][$resetVal]) && $resetCommonAttributesCustomValue = $commonAttributesCustomValue[$resetVal][$resetVal];
                if (empty($resetCommonAttributesCustomValue)){
                    $ProductPropertysEach['attrNameId']  = (int)$key;
                    if (is_array($val)){
                        $ProductPropertysEach['attrValueId'] = $resetVal;
                    } else {
                        $ProductPropertysEach['attrValue'] = $val;
                    }
                    
                } else {
                    $attrName = '';
                    $attrNameInfo = AliexpressAttribute::model()->getAttrByAttrId($key);
                    if ($attrNameInfo){
                        foreach ($attrNameInfo as $attrNameInfoVal){
                            if ($attrNameInfoVal['language_code'] == 'english'){
                                $attrName = $attrNameInfo['attribute_name'];
                                break;
                            }
                        }
                    }
                    $ProductPropertysEach['attrNameId']  = (int)$key;
                    //$ProductPropertysEach['attrName']  = $attrName;
                    $ProductPropertysEach['attrValue'] = $resetCommonAttributesCustomValue;
                }
                $ProductPropertys[] = $ProductPropertysEach;
            }
            //自定义的属性
            if (isset($customAttributeName)) 
            foreach ($customAttributeName as $key => $val){
                $ProductPropertysEach = array();
                $ProductPropertysEach['attrName']  = $val;
                $ProductPropertysEach['attrValue'] = $customAttributeValue[$key];
                $ProductPropertys[] = $ProductPropertysEach;
            }
            //$this->print_r($ProductPropertys);
            //$request->setAeopAeProductPropertys($onlineData['aeopAeProductPropertys']);
            $request->setAeopAeProductPropertys($ProductPropertys);
            
            //设置产品主图
            $imgListMain = '';
            if (isset($skuImage)) 
            foreach ($skuImage as $key => $val){
                if ($key == 1){
                    foreach ($val as $keyMain => $valMain){
                        //$_REQUEST['debug'] = 1;
                        $basename = basename($valMain, '.jpg');
                        $imageInfo = AliexpressProductImageAdd::model()                        
                        ->getDbConnection()
                        ->createCommand()
                        ->from(AliexpressProductImageAdd::model()->tableName())
                        ->select('remote_path')
                        ->where("sku='$publishSku' and type=1 and image_name='$valMain' and account_id=$requestAccountID")
                        ->queryRow();
                        //$this->print_r("sku='$publishSku' and type=1 and image_name='{$valMain}' and account_id=$requestAccountID");
                        //$this->print_r($imageInfo);
                        if ($imageInfo){
                            $imgListMain .= "{$imageInfo['remote_path']};";
                        }
                    }
                }
            }
            $imgListMain = substr($imgListMain, 0,strlen($imgListMain) - 1);
            $request->setImageURLs($imgListMain);
            $response = $request->setAccount($requestAccountID)->setRequest()->sendRequest()->getResponse();
            //print_r($response);
            //$this->print_r($request->getRequest());
            if (isset($response->error_code)){
                $return['status'] = 'failure';
                if ($response->error_code == '13001030'){
                    $return['msg'] = "商品正在活动中，不能整体编辑。<br />以下是部分编辑内容更新情况：  <br /><br />";
                    $simpleRequest            = new EditSimpleProductFiledRequest();                                       
                    $simpleRequest->setAccount($requestAccountID);
                    $simpleRequest->setProductID(Yii::app()->request->getParam('id'));                   
                    
                    //产品标题
                    $simpleRequest->setFiedName('subject');
                    $simpleRequest->setFiedValue(reset(Yii::app()->request->getParam('subject')));
                    $response = $simpleRequest->setRequest()->sendRequest()->getResponse();
                    if (isset($response->error_code)){
                        $return['msg'] .= "
                            <b style='color:red;'>“产品标题”更新失败</b> <br />
                                                                                    错误代码：{$response->error_code} <br />
                                                                                    错误信息：{$response->error_message} <br />                                                        
                                                                                    详细解释：{$simpleRequest->getErrorDetail($response->error_code)} <br /> <br />                                                             
                        ";
                    } else $return['msg'] .= "“产品标题”编辑成功.  <br />";                   
                    sleep(3);
                    
                    //产品图片,暂时不能编辑，接口报错
                    /*
                    $simpleRequest->setFiedName('imageURLs');
                    $simpleRequest->setFiedValue($imgListMain);
                    //$simpleRequest->setFiedValue($onlineData['imageURLs']);
                    $response = $simpleRequest->setRequest()->sendRequest()->getResponse();
                    if (isset($response->error_code)){
                        $return['msg'] .= "
                            <b style='color:red;'>“产品图片”更新失败</b> <br />
                                                                                    错误代码：{$response->error_code} <br />
                                                                                   错误信息：{$response->error_message} <br />
                                                                                    详细解释：{$simpleRequest->getErrorDetail($response->error_code)} <br /> <br />                                                             
                        ";
                    } else $return['msg'] .= "“产品图片”编辑成功.  <br />";                   
                    sleep(3);
                    */
                    
                    //产品描述
                    $simpleRequest->setFiedName('detail');
                    $simpleRequest->setFiedValue(reset(Yii::app()->request->getParam('detail')));
                    $response = $simpleRequest->setRequest()->sendRequest()->getResponse();
                    if (isset($response->error_code)){
                        $return['msg'] .= "
                            <b style='color:red;'>“产品描述”更新失败</b> <br />
                                                                                    错误代码：{$response->error_code} <br />
                                                                                    错误信息：{$response->error_message} <br />                                                        
                                                                                    详细解释：{$simpleRequest->getErrorDetail($response->error_code)} <br /> <br />                                                             
                        ";
                    } else $return['msg'] .= "“产品描述”编辑成功.  <br />";
                    sleep(3);
                    
                    //产品属性
                    $skusAndPropertiesRequest = new EditProductSkusAndPropertiesRequest();
                    $skusAndPropertiesRequest->setAccount($requestAccountID);
                    $skusAndPropertiesRequest->setProductID(Yii::app()->request->getParam('id'));
                    $skusAndPropertiesRequest->setCategoryId(Yii::app()->request->getParam('category_id'));
                    $skusAndPropertiesRequest->setProductProperties($ProductPropertys);
                    $response = $skusAndPropertiesRequest->setRequest()->sendRequest()->getResponse();
                    if (isset($response->error_code)){
                        $return['msg'] .= "
                        <b style='color:red;'>“产品属性”更新失败</b> <br />
                                                                        错误代码：{$response->error_code} <br />
                                                                        错误信息：{$response->error_message} <br />                                                    
                                                                        详细解释：{$skusAndPropertiesRequest->getErrorDetail($response->error_code)} <br /> <br />
                        ";
                    } else $return['msg'] .= "“产品属性”编辑成功.  <br />";
                    
                    $this->updateLog($requestAccountID, 'edit_product', $return['msg'] . '产品id：' . Yii::app()->request->getParam('id'));
                    
                    //将产品拉到系统
                    $url = '/aliexpress/aliexpressnotautomatic/doactionajax/account_id/' . $requestAccountID . '/product_id_list/' . Yii::app()->request->getParam('id');
                    sleep(3);	//避免平台数据未更新，等待3秒
                    MHelper::runThreadSOCKET($url);
                } else {
                    $return['msg'] = "
                                                            请求序号：{$response->request_id} <br />
                                                            错误代码：{$response->error_code} <br />
                                                            错误信息：{$response->error_message} <br />
                                                            详细解释：{$request->getErrorDetail($response->error_code)}";
                    $return['api']    = base64_encode(json_encode($request->getRequest()));
                    //$return['online'] = base64_encode(json_encode($onlineData));
                }                               											
																
                echo json_encode($return);
                Yii::app()->end();
            } else {
                $this->updateLog($requestAccountID, 'edit_product', $return['msg'] . '产品id：' . Yii::app()->request->getParam('id'));
                
                //将产品拉到系统
                $url = '/aliexpress/aliexpressnotautomatic/doactionajax/account_id/' . $requestAccountID . '/product_id_list/' . Yii::app()->request->getParam('id');
                sleep(3);	//避免平台数据未更新，等待3秒
                MHelper::runThreadSOCKET($url);
            }
	    }
	    echo json_encode($return);
	}
	
	/**
	 * @desc 更新编辑产品的日志
	 */
	public function updateLog($accountId,$event,$msg){
	    $nowTime = date('Y-m-d H:i:s');
	    $updateArray = array();
	    $updateArray['account_id']     = $accountId;
	    $updateArray['event']          = $event;
	    $updateArray['start_time']     = $nowTime;
	    $updateArray['end_time']       = $nowTime;
	    $updateArray['response_time']  = $nowTime;
	    $updateArray['create_user_id'] = intval(Yii::app()->user->id);
	    $updateArray['status']         = 0;
	    $updateArray['message']        = $msg;
	    $result = AliexpressLog::model()->getDbConnection()
	    ->createCommand()
	    ->insert(AliexpressLog::model()->tableName(), $updateArray);
	}
	
	/**
	 * @desc 获取可用账号
	 */
	public function actionGetableaccount(){
		$sku            = Yii::app()->request->getParam('publish_sku');
		$listingType    = Yii::app()->request->getParam('listing_type');
		$accounts = AliexpressProductAdd::model()->getAbleAccountListBySku($sku);
		$ableAccounts = array();
		$userID = isset(Yii::app()->user->id)?Yii::app()->user->id:'';
		if(!$userID){
			echo $this->failureJson(array('message' => '登录状态失效，请重新登录'));
			Yii::app()->end();
		}

		//通过userid取出对应的账号
		$userAccount = AliexpressAccountSeller::model()->getListByCondition('account_id','seller_user_id = '.$userID);
		foreach($accounts as $id=>$account){
			if($userAccount && !in_array($id, $userAccount)){
				continue;
			}
			$ableAccounts[] = array(
					'id'            => $id,
					'short_name'    =>  $account['short_name'],
					'flag'			=>	$account['is_upload'],
					'is_overseas_warehouse' => $account['is_overseas_warehouse'] == 1 ? 'true' : 'false'
			);
		}
		echo json_encode($ableAccounts);exit;
	}
	
	/**
	 * @desc 保存刊登数据
	 */
	public function actionSavedata() {
		//$this->print_r($_POST);
		//exit;
		$errors = '';		//验证数据错误信息
		//刊登基础数据
		$sku = trim(Yii::app()->request->getParam('publish_sku'));
		$publishType = Yii::app()->request->getParam('publish_type');	//刊登类型
		$publishMode = Yii::app()->request->getParam('publish_mode');	//刊登模式
		$categoryID = Yii::app()->request->getParam('category_id');		//产品刊登分类
		$action = Yii::app()->request->getParam('action');	//添加还是更新
		if ($action == 'update')
			$addID = Yii::app()->request->getParam('id');
		else
			$addID = null;
		//多属性产品
		$skuAttributesArr = Yii::app()->request->getParam('sku_attributes');
		$variationAttributesArr = Yii::app()->request->getParam('variation_attributes');
		$variationSkusArr = Yii::app()->request->getParam('variation_skus');
		$variationPriceArr = Yii::app()->request->getParam('variation_price');
		$variationcustomNameArr = Yii::app()->request->getParam('variation_custom_name');
		
		//产品普通属性
		$commonAttributesArr = Yii::app()->request->getParam('common_attributes');
		$customAttributeArr = Yii::app()->request->getParam('custom_attribute_name');
		$customAttributeValueArr = Yii::app()->request->getParam('custom_attribute_value');
		$commonAttributesCustomValue = Yii::app()->request->getParam('common_attributes_custom_value');
		
		//产品基础数据
		$productUnit = Yii::app()->request->getParam('product_unit');	//产品单位
		$lotNum = (int)Yii::app()->request->getParam('lot_num');	//打包销售每包数量
		if ($lotNum > 0)
			$isPackage = 1;		//打包销售
		else
			$isPackage = 0;		//不打包销售
		$grossWeight = floatval(Yii::app()->request->getParam('gross_weight'));	//产品毛重
		$packageLength = (int)Yii::app()->request->getParam('package_length');	//产品包装长
		$packageWidth = (int)Yii::app()->request->getParam('package_width');	//产品包装宽
		$packageHeight = (int)Yii::app()->request->getParam('package_length');	//产品包装高
		$subjectArr = Yii::app()->request->getParam('subject');		//产品标题
		$productPriceArr = Yii::app()->request->getParam('product_price', array());	//产品价格
		$groupArr = Yii::app()->request->getParam('group_id');	//产品分组ID
		$serivceTemplateArr = Yii::app()->request->getParam('service_template_id');	//产品服务模板ID
		$freightTemplateArr = Yii::app()->request->getParam('freight_template_id');	//产品运费模板ID
		$detailArr = Yii::app()->request->getParam('detail');
		$discountArr = Yii::app()->request->getParam('discount');	//产品折扣
		//产品图片数据
		$skuImages = Yii::app()->request->getParam('skuImage');
		//sku信息
		$skuInfo = Product::model()->getProductInfoBySku($sku);
		//海外仓
		$overseas_warehouse_id = Yii::app()->request->getParam('overseas_warehouse_id');
		/** 验证提交数据完整性 **/
		//判断产品是否存在
		// if (empty($skuInfo)) {
		// 	echo $this->failureJson(array(
		// 			'message' => Yii::t('aliexpress_product', 'Sku Not Exists', array('sku' => $sku)),
		// 	));
		// 	Yii::app()->end();
		// }

		//验证主sku
		Product::model()->checkPublishSKU($publishType, $skuInfo);
		
		//判断主sku不能刊登一口价
		if($skuInfo['product_is_multi']==Product::PRODUCT_MULTIPLE_MAIN && $publishType != AliexpressProductAdd::PRODUCT_PUBLISH_TYPE_VARIATION){
			$errors .= '<li>' . Yii::t('aliexpress_product', 'Main SKU Can Not Be Published As Not Variation') . '</li>';
		}
		//验证多属性刊登，是否填写SKU
		if ($publishType == AliexpressProductAdd::PRODUCT_PUBLISH_TYPE_VARIATION && (empty($variationAttributesArr) ||
				empty($variationSkusArr) ||
				empty($variationPriceArr))
		) {
			$errors .= '<li>' . Yii::t('aliexpress_product', 'Varitaion Product Empty') . '</li>';
		}
		if (!empty($variationPriceArr)) {
			foreach ($variationPriceArr as $price) {
				if ($price <= 0.00)
					$errors .= '<li>' . Yii::t('aliexpress_product', 'Variation SKU Price Empty') . '</li>';
			}
		}
		//检查分类是否选择
		if (empty($categoryID))
			$errors .= '<li>' . Yii::t('aliexpress_product', 'Product Category Empty') . '</li>';
 		//检查是否有图片
 		if (empty($skuImages))
			$errors .= '<li>' . Yii::t('aliexpress_product', 'Product Image Empty') . '</li>';
 		$imgtotal1 = isset($skuImages[ProductImages::IMAGE_ZT])?sizeof($skuImages[ProductImages::IMAGE_ZT]):0;
 		$imgtotal2 = isset($skuImages[3])?sizeof($skuImages[3]):0;
 		if (($imgtotal1+$imgtotal2) > AliexpressProductAdd::PRODUCT_MAIN_IMAGE_MAX_NUMBER) {
 			$errors .= '<li>' . Yii::t('aliexpress_product', 'Product Main Image Must Less Then', array('num' => AliexpressProductAdd::PRODUCT_MAIN_IMAGE_MAX_NUMBER)) . '</li>';
 		}

 		//检查是否有标题
		foreach ($subjectArr as $accountID => $subject) {
			// 检查是否多次点击保存刊登
			// if($action == 'create'){
			// 	$aliProductAddModel = new AliexpressProductAdd();
			// 	$exist = $aliProductAddModel->getOneByCondition('id','sku = "'.$sku.'" AND account_id = '.$accountID);
			// 	if($exist){
			// 		$errors .= '<li>此sku已经存在该账号下，请不要重复刊登！</li>';
			// 	}
			// }

			if (empty($subject))
				$errors .= '<li>' . Yii::t('aliexpress_product', 'Product Tile Empty', array('accountID' => $accountID)) . '</li>';
			if (strlen($subject) > 128)
				$errors .= '<li>' . Yii::t('aliexpress_product', 'Product Tile Could Too Long', array('accountID' => $accountID)) . '</li>';
		}
		//检查价格
		if ($publishType == AliexpressProductAdd::PRODUCT_PUBLISH_TYPE_FIXEDPRICE) {
			if (empty($productPriceArr))
				$errors .= '<li>' . Yii::t('aliexpress_product', 'Product Price Empty');
		}
		foreach ($productPriceArr as $accountID => $price)
			if ($price == '0')
				
		//检查毛重
		if ($grossWeight <= 0) {
			$errors .= '<li>' . Yii::t('aliexpress', 'Product Gross Weight Must Greater Than Zero') . '</li>';
		}
		//检查包装长
		if ($packageLength <= 0) {
			$errors .= '<li>' . Yii::t('aliexpress', 'Product Package Length Must Greater Than Zero') . '</li>';
		}
		//检查包装宽
		if ($packageWidth <= 0) {
			$errors .= '<li>' . Yii::t('aliexpress', 'Product Package Width Must Greater Than Zero') . '</li>';
		}
		//检查包装高
		if ($packageHeight <= 0) {
			$errors .= '<li>' . Yii::t('aliexpress', 'Product Package Height Must Greater Than Zero') . '</li>';
		}

		if ($isPackage == 1 && $lotNum <= 1) {
			$errors .= '<li>' . Yii::t('aliexpress', 'Package Number Must Between 2 And 100,000') . '</li>';
		}

		//检查产品折扣
		$discount = $discountArr[$accountID];
		$discount = str_replace('%', '', $discount);
		if(!is_numeric($discount) || $discount < 0 || $discount > 100){
			$errors .= '<li>discount is not correct</li>';
		}
		
		//检查必填属性是否填写
		$requiredAttributes = AliexpressCategoryAttributes::model()->getCategoryRequiredAttributeList($categoryID);
		if (!empty($requiredAttributes)) {
			foreach ($requiredAttributes as $requiredAttribute) {
				if (!isset($commonAttributesArr[$requiredAttribute['attribute_id']]) || empty($commonAttributesArr[$requiredAttribute['attribute_id']]) || empty($commonAttributesArr[$requiredAttribute['attribute_id']][0])) {
					$errors .= '<li>' . Yii::t('aliexpress_product', 'Attribute Required', array('attribute' => $requiredAttribute['attribute_name']));
				}
			}
		}

		//检查自定义属性是否符合规范
		if (!empty($customAttributeArr)) {
			foreach ($customAttributeArr as $key => $attributeName) {
				$attributeName = trim($attributeName);
				$valueName = trim($customAttributeValueArr[$key]);
				if (empty($attributeName) || empty($valueName) || $attributeName == Yii::t('aliexpress', 'Attribute Name') || $valueName == Yii::t('aliexpress', 'Attribute Value')) {
					unset($customAttributeArr[$key]);
					unset($customAttributeValueArr[$key]);
					continue;
				}
				if (preg_match("/[\x{4e00}-\x{9fa5}]/u",$attributeName)) {
					$errors .= '<li>' . Yii::t('aliexpress', 'Custom Attribute Contain Chinese Charts', array('attribute_name' => $attributeName)) .'</li>';
					continue;
				}
				if (preg_match("/[\x{4e00}-\x{9fa5}]/u",$valueName)) {
					$errors .= '<li>' . Yii::t('aliexpress', 'Custom Attribute Value Contain Chinese Charts', array('attribute_value' => $valueName)) .'</li>';
					continue;
				}
			}
		}
			
		if (!empty($errors)) {
			echo $this->failureJson(array(
				'message' => $errors,
			));
			Yii::app()->end();
		}
		if ($action == 'update') {
			//删除产品图片
			AliexpressProductImageAdd::model()->deleteAll("sku = :sku and account_id =:account_id and platform_code = :platform_code", array(':sku' => $sku, ':account_id' => $accountID, ':platform_code' => Platform::CODE_ALIEXPRESS));
			//删除产品属性
			AliexpressProductAddAttribute::model()->deleteAll("add_id = :add_id", array(':add_id' => $addID));
			//删除多属性产品
			AliexpressProductAddVariation::model()->deleteAll("add_id = :add_id", array(':add_id' => $addID));
			//删除多属性产品属性
			AliexpressProductAddVariationAttribute::model()->deleteAll("add_id = :add_id", array(':add_id' => $addID));
				
		}

		//判断图片是否加水印
		$watermarkArr = Productimagesetting::model()->watermarkImages($accountID, Platform::CODE_ALIEXPRESS);
				
		$skuAttributeImages = array();	//多属性产品图片
		$attributeInfos = array();
		$watermarkImgNameList = array();
		//保存每个账号的数据
		foreach ($subjectArr as $accountID => $subject) {
			$accountIDs[] = $accountID;
			$dbTransaction = AliexpressProductAdd::model()->getDbConnection()->getCurrentTransaction();
			if (is_null($dbTransaction))
				$dbTransaction = AliexpressProductAdd::model()->getDbConnection()->beginTransaction();
			//保存刊登主表信息
			try {
				$checkDuplicateSkuArr = array();
				$addData = array(
					//'id' => $addID,
					'account_id' => $accountID,
					'sku' => $sku,
					'currency' => AliexpressProductAdd::PRODUCT_PUBLISH_CURRENCY,
					'publish_type' => $publishType,
					'publish_mode' => $publishMode,
					'subject' => $subject,
					//'keywords' => $keywordsArr[$accountID],
					//'more_keywords1' => $moreKeywords1Arr[$accountID],
					//'more_keywords2' => $moreKeywords2Arr[$accountID],
					'category_id' => $categoryID,
					'group_id' => intval($groupArr[$accountID]),
                    'create_user_id'=> intval(Yii::app()->user->id),
                    'create_time'   => date('Y-m-d H:i:s'),
                    'modify_user_id'=> intval(Yii::app()->user->id),
                    'modify_time'   => date('Y-m-d H:i:s'),
					'service_template_id' => $serivceTemplateArr[$accountID],
					'freight_template_id' => $freightTemplateArr[$accountID],
					'overseas_warehouse_id' => isset($overseas_warehouse_id) ? $overseas_warehouse_id : 0 ,
					'is_package' => $isPackage,
					'product_unit' => $productUnit,
					'lot_num' => $lotNum,
					'gross_weight' => $grossWeight,
					'package_length' => $packageLength,
					'package_width' => $packageWidth,
					'package_height' => $packageHeight,
					'detail' => $detailArr[$accountID],
					'discount' => $discount,
				);
				if ($publishType == AliexpressProductAdd::PRODUCT_PUBLISH_TYPE_FIXEDPRICE && empty($skuAttributesArr))
					$addData['product_price'] = round(floatval($productPriceArr[$accountID]), 2);
				$AliexpressProductAddModel = new AliexpressProductAdd();
				/* $AliexpressProductAddModel->setAttributes($addData, false);
				if ($action == 'update')
					$AliexpressProductAddModel->isNewRecord = false;
				if (!$AliexpressProductAddModel->save())
					throw new Exception(Yii::t('aliexpress_product', 'Save Product Info Failure'));
				if ($action == 'create')
					$addID = $AliexpressProductAddModel->getDbConnection()->getLastInsertID(); */
				// === 2016-01-11 add by lihy=======
				if($action == 'update'){
					$addData['status'] = AliexpressProductAdd::UPLOAD_STATUS_DEFAULT;
					$res = $AliexpressProductAddModel->getDbConnection()->createCommand()->update($AliexpressProductAddModel->tableName(), $addData, "id=:id", array(":id"=>$addID));
				}elseif ($action == 'create'){
					$res = $AliexpressProductAddModel->getDbConnection()->createCommand()->insert($AliexpressProductAddModel->tableName(), $addData);
				}
				if(!$res){
					throw new Exception(Yii::t('aliexpress_product', 'Save Product Info Failure'));
				}
				
				if ($action == 'create'){
					$addID = $AliexpressProductAddModel->getDbConnection()->getLastInsertID();
				}
				if(empty($addID)){
					throw new Exception(Yii::t('aliexpress_product', 'Save Product Info Failure'));
				}
				// === 2016-01-11 =====
				
				//保存产品图片
				$productImageAdd = new AliexpressProductImageAdd();
				//删除以前的图片
                AliexpressProductImageAdd::model()->deleteSkuImages($sku, $accountID, Platform::CODE_ALIEXPRESS);
  				foreach ($skuImages as $type => $images) {
  					foreach ($images as $image) {
  						$watermarkImgName = basename($image);
						//$imagename = basename($image, '.jpg');
  						//查询图片是否已经添加
  						//if (ProductImageAdd::model()->checkImageExists($imagename, $type, Platform::CODE_ALIEXPRESS, $accountID)) continue;  	
  						//图片加水印
  						if(in_array($type, $watermarkArr)){
	  						$watermarkImgNameList[] = $watermarkImgName;
  						}
                        $localPath = ProductImageAdd::getImageLocalPathBySkuAndName($sku, $type, basename($image, '.jpg'));

						$imageAddData = array(
							'image_name'    => $image,
							'sku'           => $sku,
							'type'          => $type,
							'local_path'    => $localPath,
							'platform_code' => Platform::CODE_ALIEXPRESS,
							'account_id'    => $accountID,
							'upload_status' => AliexpressProductImageAdd::UPLOAD_STATUS_DEFAULT,
							'create_user_id'=> Yii::app()->user->id,
							'create_time'   => date('Y-m-d H:i:s'),					
						);
	                    $imageModel = new AliexpressProductImageAdd();
	                    $imageModel->setAttributes($imageAddData,false);
	                    $imageModel->setIsNewRecord(true);
	                    $flag = $imageModel->save();
	                    if (!$flag)
	                    	throw new Exception('Aliexpress_product', 'Save Product Image Failure');
  					}
				}
				//保存产品普通属性
				foreach ($commonAttributesArr as $attributeID => $values) {
					$attributeAddData = array();
					$attributeID =  (int)$attributeID;		//属性名ID
					if (!is_array($values)) {
						//自定义属性值
						if (trim($values) == '') continue;
						$valueName = trim($values);
						$attributeAddData = array(
								'add_id' => $addID,
								'attribute_id' => $attributeID,
								'value_name' => $values,
						);
						$aliexpressProductAddAttributeModel = new AliexpressProductAddAttribute();
						$aliexpressProductAddAttributeModel->setAttributes($attributeAddData, false);
						$aliexpressProductAddAttributeModel->setIsNewRecord(true);
						$flag = $aliexpressProductAddAttributeModel->save();
						if (!$flag)
							throw new Exception(Yii::t('aliexpress_product', 'Save Product Attributes Failure'));
					} else {
						foreach ($values as $valueID) {
							if ($valueID == '') continue; //过滤掉属性值为空的属性
							$customValueName = null;	//自定义属性值
							if (isset($commonAttributesCustomValue[$attributeID][$valueID]) && trim($commonAttributesCustomValue[$attributeID][$valueID]) != '') {
								$customValueName = trim($commonAttributesCustomValue[$attributeID][$valueID]);
							}
							$attributeAddData = array(
								'add_id' => $addID,
								'attribute_id' => $attributeID,
								'value_id' => $valueID,
								'value_name' => $customValueName
							);
							$aliexpressProductAddAttributeModel = new AliexpressProductAddAttribute();
							$aliexpressProductAddAttributeModel->setAttributes($attributeAddData, false);
							$aliexpressProductAddAttributeModel->setIsNewRecord(true);
							$flag = $aliexpressProductAddAttributeModel->save();
							if (!$flag)
								throw new Exception(Yii::t('aliexpress_product', 'Save Product Attributes Failure'));
						}
					}
				}
				
				//保存自定义属性
				if (!empty($customAttributeArr)) {
					foreach ($customAttributeArr as $key => $attributeName) {
						$valueName = $customAttributeValueArr[$key];
						$attributeAddData = array(
								'add_id' => $addID,
								'attribute_name' => $attributeName,
								'value_name' => $valueName,
								'is_custom' => 1,
						);
						$aliexpressProductAddAttributeModel = new AliexpressProductAddAttribute();
						$aliexpressProductAddAttributeModel->setAttributes($attributeAddData, false);
						$aliexpressProductAddAttributeModel->setIsNewRecord(true);
						$flag = $aliexpressProductAddAttributeModel->save();
						if (!$flag)
							throw new Exception(Yii::t('aliexpress_product', 'Save Product Custom Attributes Failure'));												
					}
				}

				//子sku是否上传图片
				$isAddImages = 0;

				//保存多属性产品
				if (!empty($skuAttributesArr)) {
					if (!empty($variationSkusArr)) {
						//取出可以设置为自定义名称的值
						$newAttrIDArr = array();
						if($variationcustomNameArr && $variationAttributesArr){
							$attrIDArr = array();
							foreach ($variationAttributesArr as $AttrArr) {
								foreach ($AttrArr as $AttrKey => $AttrVal) {
									$attrIDArr[] = $AttrKey;
								}
							}

							$newAttrIDArr = array_unique($attrIDArr);

							//如果$newAttrIDArr为空，取出设置自定义名称的第一个
							if($newAttrIDArr){
								$attrFields = 'attribute_value_ids';
								$attrWhere  = 'category_id = '.$categoryID.' AND costomized_name = 1 AND attribute_id IN('.implode(',', $newAttrIDArr).')';
								$attrOrder  = 'spec ASC';
								$newAttrIDInfo = AliexpressCategoryAttributes::model()->getOneByCondition($attrFields,$attrWhere,$attrOrder);
								if(isset($newAttrIDInfo['attribute_value_ids']) && !empty($newAttrIDInfo['attribute_value_ids'])){
									$newAttrIDArr = explode(',', $newAttrIDInfo['attribute_value_ids']);
								}
							}
						}

						foreach ($variationSkusArr as $key => $variationSku) {
							$variationSku = trim($variationSku);
							if (empty($variationSku)) {
								throw new Exception(Yii::t('aliexpress_product', 'Variation Sku Empty'));
							}
							if (in_array($variationSku, $checkDuplicateSkuArr)) {
								throw new Exception(Yii::t('aliexpress_product', 'Duplicate Variation Sku', array('sku' => $variationSku)));
							}
							$checkDuplicateSkuArr[] = $variationSku;
							$variationProductAddData = array(
								'add_id' => $addID,
								'sku' => $variationSku,
								'price' => round(floatval($variationPriceArr[$key]), 2),
							);
							$aliexpressProductAddVariationModel = new AliexpressProductAddVariation();
							$aliexpressProductAddVariationModel->setAttributes($variationProductAddData, false);
							$aliexpressProductAddVariationModel->setIsNewRecord(true);
							$flag = $aliexpressProductAddVariationModel->save();
							if (!$flag)
								throw new Exception(Yii::t('aliexpress_product', 'Save Variation Product Failure'));
							$variationID = $aliexpressProductAddVariationModel->getDbConnection()->getLastInsertID();
							//保存多属性产品属性
							foreach ($variationAttributesArr[$key] as $attributeID => $attributeValueID) {
								$valueCustomName = null;
								if (!array_key_exists($attributeID, $attributeInfos)) {
									$attributeInfo = AliexpressCategoryAttributes::model()->getCategoryAttribute($categoryID, $attributeID);
									if (empty($attributeInfo)) continue;
									$attributeInfos[$attributeID] = $attributeInfo;
								}
								$attributeInfo = $attributeInfos[$attributeID];

								//是否需要自定义多属性图片
								if($attributeInfo['customized_pic'] == 1){
									$isAddImages = 1;
								}
								
								//是否可以自定义属性值名
								if ($attributeInfo['costomized_name'] == 1 && isset($variationcustomNameArr[$key]) && !empty($variationcustomNameArr[$key]) && in_array($attributeValueID, $newAttrIDArr))
									$valueCustomName = $variationcustomNameArr[$key];
								$variationProductAttributeAddData = array(
									'add_id' => $addID,
									'variation_id' => $variationID,
									'attribute_id' => $attributeID,
									'value_id' => $attributeValueID,
									'value_name' => $valueCustomName,
								);
								$aliexpressProductAddVariationAttributeModel = new AliexpressProductAddVariationAttribute();
								$aliexpressProductAddVariationAttributeModel->setAttributes($variationProductAttributeAddData, false);
								$aliexpressProductAddVariationAttributeModel->setIsNewRecord(true);
								$flag = $aliexpressProductAddVariationAttributeModel->save();
								if (!$flag)
									throw new Exception(Yii::t('aliexpress_product', 'Save Variation Product Attribute Failure'));
							}
							
							//是否需要自定义多属性图片
							if ($isAddImages == 1 && !array_key_exists($variationSku, $skuAttributeImages)) {
								//取sku的一个主图
								//$images = Product::model()->getImgList($variationSku, 'ft');
								$images = ProductImageAdd::getImagesPathFromRestfulBySkuAndType($variationSku, 'zt');

								if (!empty($images)) {
									$skuAttributeImages[$variationSku] = array_shift($images);
								}
								else {
	   				 				//$imagesFt = Product::model()->getImgList($variationSku, 'ft');
                                    $images = ProductImageAdd::getImagesPathFromRestfulBySkuAndType($variationSku, 'ft');

                                    if (!empty($images)){
                                        $skuAttributeImages[$variationSku] = array_shift($images);
                                    }
                                }
							}
							//将SKU多属性图片添加到图片上传表
							if ($isAddImages == 1 && isset($skuAttributeImages[$variationSku]) && !empty($skuAttributeImages[$variationSku])) {
								if($variationSku == $sku){
									continue;//当子SKU 和主SKU一致时
								}
								//查询图片是否已经添加
                                AliexpressProductImageAdd::model()->deleteSkuImages($variationSku, $accountID, Platform::CODE_ALIEXPRESS);

//								$varSkuImg = ProductImageAdd::getImageLocalPathBySkuAndName($variationSku, $type, $skuAttributeImages[$variationSku]);
//								if(!$varSkuImg){
//									continue;
//								}
//
								if(in_array(AliexpressProductImageAdd::IMAGE_ZT, $watermarkArr)){
								$watermarkImgNameList[] = $skuAttributeImages[$variationSku];
								}
                                $localPath = ProductImageAdd::getImageLocalPathBySkuAndName($variationSku, $type, basename($skuAttributeImages[$variationSku], '.jpg'));
                             

								$imageAddData = array(
										'image_name'    => $skuAttributeImages[$variationSku],
										'sku'           => $variationSku,
										'type'          => AliexpressProductImageAdd::IMAGE_ZT,
										'local_path'    =>  $localPath,
										'platform_code' => Platform::CODE_ALIEXPRESS,
										'account_id'    => $accountID,
										'upload_status' => AliexpressProductImageAdd::UPLOAD_STATUS_DEFAULT,
										'create_user_id'=> Yii::app()->user->id,
										'create_time'   => date('Y-m-d H:i:s'),
								);
								$imageModel = new AliexpressProductImageAdd();
								$imageModel->setAttributes($imageAddData,false);
								$imageModel->setIsNewRecord(true);
								$flag = $imageModel->save();
		
							}
						}

					} else {
						$variationProductAddData = array(
							'add_id' => $addID,
							'sku' => $sku,
							'price' => floatval($productPriceArr[$accountID]),
						);
						$aliexpressProductAddVariationModel = new AliexpressProductAddVariation();
						$aliexpressProductAddVariationModel->setAttributes($variationProductAddData, false);
						$aliexpressProductAddVariationModel->setIsNewRecord(true);
						$flag = $aliexpressProductAddVariationModel->save();
						if (!$flag)
							throw new Exception(Yii::t('aliexpress_product', 'Save Variation Product Failure'));	
						$variationID = $aliexpressProductAddVariationModel->getDbConnection()->getLastInsertID();
						foreach ($skuAttributesArr as $attributeID => $attributeValueID) {
							$variationProductAttributeAddData = array(
									'add_id' => $addID,
									'variation_id' => $variationID,
									'attribute_id' => $attributeID,
									'value_id' => $attributeValueID[0]
							);
							$aliexpressProductAddVariationAttributeModel = new AliexpressProductAddVariationAttribute();
							$aliexpressProductAddVariationAttributeModel->setAttributes($variationProductAttributeAddData, false);
							$aliexpressProductAddVariationAttributeModel->setIsNewRecord(true);
							$flag = $aliexpressProductAddVariationAttributeModel->save();
							if (!$flag)
								throw new Exception(Yii::t('aliexpress_product', 'Save Variation Product Attribute Failure'));							
						}
					}
				}
				//刊登完之后，把待刊登列表中状态改成刊登中
				AliexpressWaitListing::model()->updateWaitingListingStatus(
					array(
						'create_user_id' => intval(Yii::app()->user->id),
						'account_id' => $accountID,
						'sku'	=> $sku,
						'create_time' => date('Y-m-d H:i:s')
					),
					AliexpressWaitListing::STATUS_PROCESS //新增加的算刊登中
				);

				//上传图片
				$checkDuplicateSkuArr[] = $sku;
				$productImageAdd->addSkuImageUpload($accountID,$checkDuplicateSkuArr,0,Platform::CODE_ALIEXPRESS,0,$watermarkImgNameList);
				
				$dbTransaction->commit();
				
			} catch (Exception $e) {
				$dbTransaction->rollback();
				echo $this->failureJson(array(
					'message' => $e->getMessage(),
				));
				Yii::app()->end();
			}	//end try catch
		} //end foreach
		echo $this->successJson(array(
				'message' => Yii::t('aliexpress_product', 'Save Product Publish Data Successful'),
				'navTabId' => 'page' . AliexpressProductAdd::getIndexNavTabId(),
				'sku' => $sku,
		));		
	}
	
	public function actionGetproductpricebychangeoverseaswarehouse() {
	    $sku                       = Yii::app()->request->getParam('publish_sku');
	    $categoryID                = Yii::app()->request->getParam('category_id');
	    $currency                  = AliexpressProductAdd::PRODUCT_PUBLISH_CURRENCY;
	    $selectedAccountId         = Yii::app()->request->getParam('selected_account_id');
	    $accounts                  = Yii::app()->request->getParam('account_id');
	    $overseasWarehousId        = Yii::app()->request->getParam('overseas_warehous_id');
	    $prices                    = Yii::app()->request->getParam('price');
	
	    
	    foreach($accounts as $account){
	        if ($account == $selectedAccountId){
	            //根据刊登条件匹配卖价方案 TODO
	            $salePrice = $profit = $profitRate = $calcDesc = array();
	            $productCost = 0;
	            $standardProfitRate = 0.18;  //标准利润率
	            $data = array();
	            
	            //获取产品信息
	            $skuInfo = Product::model()->getProductInfoBySku($sku);
	            if(!$skuInfo){
	                echo json_encode($data);
	                Yii::app()->end();
	            }
	            
	            if($skuInfo['avg_price'] <= 0){
	                $productCost = $skuInfo['product_cost'];   //加权成本
	            }else{
	                $productCost = $skuInfo['avg_price'];      //产品成本
	            }
	            
	            //产品成本转换成美金
	            $productCost = $productCost / CurrencyRate::model()->getRateToCny($currency);
	            $productCost = round($productCost,2);
	            $shipCode = AliexpressProductAdd::model()->returnShipCode($productCost,$sku);
	            
	            //取出佣金
	            $commissionRate = AliexpressCategoryCommissionRate::getCommissionRate($categoryID);
	            
	            //计算卖价，获取描述
	            $priceCal = new CurrencyCalculate();
	            
	            //设置运费code
	            if($shipCode){
	                $priceCal->setShipCode($shipCode);
	            }
	            
	            //设置价格
	            if($prices){
	                $priceCal->setSalePrice($prices);
	            }
	            
	            $priceCal->setProfitRate($standardProfitRate);//设置利润率
	            $priceCal->setCurrency($currency);//币种
	            $priceCal->setPlatform(Platform::CODE_ALIEXPRESS);//设置销售平台
	            $priceCal->setSku($sku);//设置sku
	            $priceCal->setCommissionRate($commissionRate);//设置佣金比例
	            $priceCal->setUnionCommission(AliexpressProductAdd::UNION_COMMISSION); //联盟佣金
	            $priceCal->setWarehouseID($overseasWarehousId); //海外仓
	            $salePrice = $priceCal->getSalePrice();//获取卖价
	            if($salePrice > 5){
	                $priceCal2 = new CurrencyCalculate();
	                $shipCode = AliexpressProductAdd::model()->returnShipCode($salePrice,$sku);
	                //设置运费code
	                if($shipCode){
	                    $priceCal2->setShipCode($shipCode);
	                }
	            
	                //设置价格
	                if($prices){
	                    $priceCal2->setSalePrice($prices);
	                }
	                
	                $priceCal2->setProfitRate($standardProfitRate);//设置利润率
	                $priceCal2->setCurrency($currency);//币种
	                $priceCal2->setPlatform(Platform::CODE_ALIEXPRESS);//设置销售平台
	                $priceCal2->setSku($sku);//设置sku
	                $priceCal2->setCommissionRate($commissionRate);//设置佣金比例
	                $priceCal2->setUnionCommission(AliexpressProductAdd::UNION_COMMISSION); //联盟佣金
	                $priceCal2->setWarehouseID($overseasWarehousId); //海外仓
	                $data[$account]['salePrice']     = $priceCal2->getSalePrice();//获取卖价
	                $data[$account]['profit']        = $priceCal2->getProfit(true);//获取利润
	                $data[$account]['profitRate']    = $priceCal2->getProfitRate(true);//获取利润率
	                $data[$account]['desc']          = $priceCal2->getCalculateDescription();//获取计算详情
	            } else {
	                $data[$account]['salePrice']     = $priceCal->getSalePrice();//获取卖价
	                $data[$account]['profit']        = $priceCal->getProfit(true);//获取利润
	                $data[$account]['profitRate']    = $priceCal->getProfitRate(true);//获取利润率
	                $data[$account]['desc']          = $priceCal->getCalculateDescription();//获取计算详情
	            }
	        }	        
	    }
	    echo json_encode($data);
	    Yii::app()->end();
	}
	
	public function actionGetproductprice() {
        $sku            = Yii::app()->request->getParam('publish_sku');
        $categoryID     = Yii::app()->request->getParam('category_id');
        $currency       = AliexpressProductAdd::PRODUCT_PUBLISH_CURRENCY;
        $accounts       = Yii::app()->request->getParam('account_id');
        $prices         = Yii::app()->request->getParam('price');

        foreach($accounts as $account){
			//根据刊登条件匹配卖价方案 TODO
			$salePrice = $profit = $profitRate = $calcDesc = array();
			$productCost = 0;
			$standardProfitRate = 0.18;  //标准利润率
			$data = array();

			//获取产品信息
			$skuInfo = Product::model()->getProductInfoBySku($sku);
	        if(!$skuInfo){
	        	echo json_encode($data);
				Yii::app()->end();
	        }

	        if($skuInfo['avg_price'] <= 0){
	        	$productCost = $skuInfo['product_cost'];   //加权成本
	    	}else{ 
	    		$productCost = $skuInfo['avg_price'];      //产品成本
	    	}

	    	//产品成本转换成美金
	    	$productCost = $productCost / CurrencyRate::model()->getRateToCny($currency);
	    	$productCost = round($productCost,2);
	    	$shipCode = AliexpressProductAdd::model()->returnShipCode($productCost,$sku);   	

			//取出佣金
			$commissionRate = AliexpressCategoryCommissionRate::getCommissionRate($categoryID);

			//计算卖价，获取描述
			$priceCal = new CurrencyCalculate();

			//设置运费code
			if($shipCode){
				$priceCal->setShipCode($shipCode);
			}

			//设置价格
			if($prices){
				$priceCal->setSalePrice($prices);
			}
			
			$priceCal->setProfitRate($standardProfitRate);//设置利润率
			$priceCal->setCurrency($currency);//币种
			$priceCal->setPlatform(Platform::CODE_ALIEXPRESS);//设置销售平台
			$priceCal->setSku($sku);//设置sku
			$priceCal->setCommissionRate($commissionRate);//设置佣金比例
			$priceCal->setUnionCommission(AliexpressProductAdd::UNION_COMMISSION); //联盟佣金
			$salePrice = $priceCal->getSalePrice();//获取卖价
			if($salePrice > 5){
				$priceCal2 = new CurrencyCalculate();
				$shipCode = AliexpressProductAdd::model()->returnShipCode($salePrice,$sku);
				//设置运费code
				if($shipCode){
					$priceCal2->setShipCode($shipCode);
				}
				//设置价格
				if($prices){
				    $priceCal2->setSalePrice($prices);
				}

				$priceCal2->setProfitRate($standardProfitRate);//设置利润率
				$priceCal2->setCurrency($currency);//币种
				$priceCal2->setPlatform(Platform::CODE_ALIEXPRESS);//设置销售平台
				$priceCal2->setSku($sku);//设置sku
				$priceCal2->setCommissionRate($commissionRate);//设置佣金比例
				$priceCal2->setUnionCommission(AliexpressProductAdd::UNION_COMMISSION); //联盟佣金
				$data[$account]['salePrice']     = $priceCal2->getSalePrice();//获取卖价
				$data[$account]['profit']        = $priceCal2->getProfit(true);//获取利润
				$data[$account]['profitRate']    = $priceCal2->getProfitRate(true);//获取利润率
				$data[$account]['desc']          = $priceCal2->getCalculateDescription();//获取计算详情
			}else{
				$data[$account]['salePrice']     = $priceCal->getSalePrice();//获取卖价
				$data[$account]['profit']        = $priceCal->getProfit(true);//获取利润
				$data[$account]['profitRate']    = $priceCal->getProfitRate(true);//获取利润率
				$data[$account]['desc']          = $priceCal->getCalculateDescription();//获取计算详情
			}
		}
		echo json_encode($data);
		Yii::app()->end();
	}
	
	/**
	 * @desc 计算利润情况
	 */
	public function actionGetpriceinfo(){
		$sku            = Yii::app()->request->getParam('publish_sku');
		$categoryID     = Yii::app()->request->getParam('category_id');
		$accountID      = Yii::app()->request->getParam('account_id');
		$currency       = AliexpressProductAdd::PRODUCT_PUBLISH_CURRENCY;
		$salePrice      = Yii::app()->request->getParam('price');
		$shipCode 		= AliexpressProductAdd::model()->returnShipCode($salePrice,$sku);
		$data 			= array();

		//取出佣金
		$commissionRate = AliexpressCategoryCommissionRate::getCommissionRate($categoryID);

		$priceCal = new CurrencyCalculate();
		//设置运费code
		if($shipCode){
			$priceCal->setShipCode($shipCode);
		}
		$priceCal->setCurrency($currency);//币种
		$priceCal->setPlatform(Platform::CODE_ALIEXPRESS);//设置销售平台
		$priceCal->setSku($sku);//设置sku
		$priceCal->setSalePrice($salePrice);
		$priceCal->setCommissionRate($commissionRate);//设置佣金比例
		$priceCal->setUnionCommission(AliexpressProductAdd::UNION_COMMISSION); //联盟佣金
		$output = new stdClass();
		$output->salePrice  = $priceCal->getSalePrice();
		$output->profit     = $priceCal->getProfit(true);
		$output->profitRate = $priceCal->getProfitRate(true);
		$output->desc       = $priceCal->getCalculateDescription();
		echo json_encode($output);exit;
	}
	
	public function actionAutoupload() {
		set_time_limit(2*3600);
		ini_set("display_errors", true);
		error_reporting(E_ALL);
		
		$accountID = Yii::app()->request->getParam('account_id');
		$status = Yii::app()->request->getParam('status', null);
		if( $accountID ){
			$eventName = "upload_product";
			if($status){
				$eventName = "upload_image_fail_product";
			}
			$aliexpreLog = new AliexpressLog();
			$logID = $aliexpreLog->prepareLog($accountID, $eventName);
			if($logID){
				if(!$aliexpreLog->checkRunning($accountID, $eventName)){
					$aliexpreLog->setFailure($logID, "EXISTS EVENT");
					exit;
				}
				$aliexpreLog->setRunning($logID);
				$adds = AliexpressProductAdd::model()->getNeedUploadRecord($accountID, $status);
				print_r($adds);
				$model = AliexpressProductAdd::model();
				$result = $model->uploadProduct($adds);
				
				$aliexpreLog->setSuccess($logID, "done");
			}else{
				exit("NO CREATE LOG ID");
			}
			
		}else{//循环可用账号，多线程抓取
			$aliexpressAccounts = AliexpressAccount::model()->getAbleAccountList();
			foreach($aliexpressAccounts as $account){
				MHelper::runThreadSOCKET('/'.$this->route.'/account_id/'.$account['id']);
				MHelper::runThreadSOCKET('/'.$this->route.'/account_id/'.$account['id'].'/status/'.AliexpressProductAdd::UPLOAD_STATUS_IMGFAIL);
				sleep(2);
			}
		}		
	}
	
	/**
	 * @desc 自动上传到图片服务器
	 */
	public function actionAutoUploadimage(){
		$accountID = Yii::app()->request->getParam('account_id');
		if( $accountID ){
			$skuLine = Yii::app()->request->getParam('sku_line');
			$records = AliexpressProductAdd::model()->getUploadRecordByStatus($accountID, AliexpressProductAdd::UPLOAD_STATUS_IMGFAIL, $skuLine);
			foreach($records as $record){
				AliexpressProductImageAdd::model()->uploadImageOnline($record['sku'], $accountID);
			}
		}else{//循环可用账号，多线程抓取
			$aliexpressAccounts = AliexpressAccount::model()->getAbleAccountList();
			foreach($aliexpressAccounts as $account){
				for($i=0;$i<=9;$i++){
					MHelper::runThreadSOCKET('/'.$this->route.'/account_id/'.$account['id'].'/sku_line/'.$i);
				}
			}
		}
	}
	/**
	 * @desc 立即上传
	 */
	public function actionUploadnow() {
		set_time_limit(4*3600);
		ini_set("display_errors", false);
		error_reporting(0);
		$addId = Yii::app()->request->getParam('add_id');
		try{
			if(!$addId){
				throw  new Exception(Yii::t('aliexpress', 'Param Error'));
			}
			$addInfo = AliexpressProductAdd::model()->findByPk($addId);
			if (empty($addInfo)){
				throw new Exception(Yii::t('aliexpress', 'Publish Info not Exists'));
			}
			if (in_array($addInfo->status, array(AliexpressProductAdd::UPLOAD_STATUS_RUNNING, AliexpressProductAdd::UPLOAD_STATUS_SUCCESS, AliexpressProductAdd::UPLOAD_STATUS_IMGRUNNING))) {
				throw new Exception(Yii::t('aliexpress_product', 'Publish Task Has Running or Completed'));
			}
			$model = AliexpressProductAdd::model();
			$model->uploadProduct(array($addId));
			echo $this->successJson(array(
					'message' => Yii::t('system', 'Operate Successful'),
			));
			Yii::app()->end();
		}catch (Exception $e){
			echo $this->failureJson(array(
					'message' => $e->getMessage(),
			));
			Yii::app()->end();
		}
	}
	
	public function actionTestuploadimage() {
		$accountID = 247;
		$image = 'D:\wamp\www\erp\upload\image\main\1\6\16877.jpg';
		$fp = fopen($image, 'rb');
		$content = fread($fp, filesize($image));
		$uploadImageRequest = new UploadImageRequest();
		$uploadImageRequest->setFileName('test.jpg');
		$uploadImageRequest->setFileStream($content);
		$response = $uploadImageRequest->setAccount($accountID)->setRequest()->sendRequest()->getRequest();
		var_dump($response);exit;
	}
	

	public function actionGetpromisetemplate() {
		ini_set('display_errors', true);
		error_reporting(E_ERROR);
		$accountID = Yii::app()->request->getParam('account_id');
		$request = new QueryPromiseTemplateByIdRequest();
		$response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
		var_dump($response);
	}
	
	public function actionUpdate() {
		$addID = Yii::app()->request->getParam('id');
		$addInfo = AliexpressProductAdd::model()->getInfoById($addID);
		if (empty($addInfo)) {
			echo $this->failureJson(array(
				'message' => Yii::t('aliexpress', 'Record Not Exists'),
			));
			Yii::app()->end();
		}
		if (!in_array($addInfo['status'], array(AliexpressProductAdd::UPLOAD_STATUS_IMGFAIL, AliexpressProductAdd::UPLOAD_STATUS_DEFAULT, AliexpressProductAdd::UPLOAD_STATUS_FAILURE))) {
			echo $this->failureJson(array(
				'message' => Yii::t('aliexpress', 'This Status Can\'t Modify'),
			));
			Yii::app()->end();
		}
		$sku = $addInfo['sku'];
		$accountID = $addInfo['account_id'];
		$publishType = $addInfo['publish_type'];
		$publishMode = $addInfo['publish_mode'];
		$categoryID = $addInfo['category_id'];
		$publishParams = array(
				'sku' => $sku,
				'publish_type' => array(
						'id' => $publishType,
						'text' => AliexpressProductAdd::getProductPublishTypeList($publishType),
				),
				'publish_mode' => array(
						'id' => $publishMode,
						'text' => AliexpressProductAdd::getProductPublishModelList($publishMode),
				),
		);
		//查找产品历史分类
		$historyCategoryList = AliexpressProduct::model()->getSkuHistoryCategory($sku);
		//默认历史分类
		$historyCategoryList[$categoryID] = AliexpressCategory::model()->getBreadcrumbCnAndEn($categoryID);
		$defaultHistoryCategory = array();
		if (!empty($historyCategoryList)) {
			$defaultHistoryCategory = array($categoryID => $historyCategoryList[$categoryID]);
		}
		//获取分类下sku属性
		$skuAttributes = AliexpressAttribute::model()->getCategoryAttributeList($categoryID, AliexpressAttribute::ATTRIBUTE_TYPE_SKU);
		//获取刊登记录的SKU属性
		$variationAttributes = AliexpressProductAddVariation::model()->getVariationProductArributes($addID);
		//print_r($variationAttributes);
		foreach ($variationAttributes as $variationAttribute) {
			foreach ($variationAttribute['attributes'] as $row) {
				if (array_key_exists($row['attribute_id'], $skuAttributes)) {
					foreach ($skuAttributes[$row['attribute_id']]['value_list'] as $key => $values) {
						if ($row['attribute_value_id'] == $values['attribute_value_id']) {
							$skuAttributes[$row['attribute_id']]['value_list'][$key]['selected'] = true;
							$skuAttributes[$row['attribute_id']]['value_list'][$key]['sku'] = $variationAttribute['sku'];
							$skuAttributes[$row['attribute_id']]['value_list'][$key]['price'] = $variationAttribute['price'];
						}
					}
				}
			}
		}
		//print_r($skuAttributes);exit;
		//获取分类下普通属性
		$commonAttributes = AliexpressAttribute::model()->getCategoryAttributeList($categoryID);
		//获取刊登记录普通属性
		$addCommonAttributes = AliexpressProductAddAttribute::model()->getProductAttributes($addID);
		
		foreach ($addCommonAttributes as $addCommonAttribute) {
			$attributeID = $addCommonAttribute['attribute_id'];
			$attributeName = $addCommonAttribute['attribute_name'];
			if (array_key_exists($attributeID, $commonAttributes)) {
				if (empty($commonAttributes[$attributeID]['value_list'])) {
					$commonAttributes[$attributeID]['value_list'] = $addCommonAttribute['value_name'];
				} else {
					$commonAttributes[$attributeID]['value_list'][$addCommonAttribute['value_id']]['selected'] = true;
					$commonAttributes[$attributeID]['value_list'][$addCommonAttribute['value_id']]['sku'] = '';
					$commonAttributes[$attributeID]['value_list'][$addCommonAttribute['value_id']]['value_name'] = $addCommonAttribute['value_name'];
				}
			}	
		}
		
		//查找自定义属性
		$customAttributes = AliexpressProductAddAttribute::model()->getProductAttributes($addID, 1);
		
/*  		echo '<pre>';
		print_r($commonAttributes);exit; */
		//查找产品顶级分类列表
		$categoryList = array();
		$topCategories = AliexpressCategory::model()->getCategoryByCreateLevel(1);
		if (!empty($topCategories)) {
			foreach ($topCategories as $category) {
				$categoryList[$category->category_id] = $category->en_name . '(' . $category->cn_name . ')';
			}
		}
		//查找产品信息
		$skuInfo = Product::model()->getProductInfoBySku($sku);

		//获取产品需要上传的图片
/*
		$config = ConfigFactory::getConfig('serverKeys');
		$skuImg = array();*/

        $skuImg = ProductImageAdd::getImageUrlFromRestfulBySku($sku, $typeAlisa = null, $type = 'normal', $width = 100, $height = 100, $platform = Platform::CODE_ALIEXPRESS);

        /**
         * 修复java api接口无主图返回问题
         */
        if (isset($skuImg['ft']) && !isset($skuImg['zt'])) {
            $skuImg['zt'] = $skuImg['ft'];
        }

		$images = AliexpressProductImageAdd::model()->getImageBySku($sku, $accountID, Platform::CODE_ALIEXPRESS);

		$selectedImages = array();
		foreach($images as $type => $rows){
			foreach ($rows as $row) {
				if ($type == ProductImages::IMAGE_ZT)
					$key = 'zt';
				else
					$key = 'ft';
				//$skuImg[$key][$row['image_name']] = $row['local_path'];
				$selectedImages[$key][] = $row['image_name'];
			}
		}
//		//获取产品不用上传图片
//		$imageType = array('zt', 'ft');
//		$recycleImages = array();
//		foreach($imageType as $type){
//			$images = Product::model()->getImgList($sku,$type);
//			if ($type == 'zt')
//				$key = 1;
//			else
//				$key = 2;
//			foreach($images as $k=>$img){
//				if($k == $sku) continue;
//				$skuImg[$type][$k] = $img;
//				/* if (in_array($k, $selectedImages[$type])){
//
//				}else{
//					$recycleImages[$key][$k] = $img;
//				} */
//
//			}
//		}
		//查找需要刊登的账号信息
		$accountList = array();
		$accountInfos = AliexpressAccount::model()->getAccountInfoByIds(array($accountID));
		$overseasWarehouseList = '';
		foreach ($accountInfos as $accountInfo) {
			$accountList[$accountInfo->id]['account_name'] = $accountInfo->short_name;
			//查找账号对应的分组列表
			$groupList = AliexpressGroupList::model()->getGroupTree($accountInfo['id'], 0, 0, $addInfo['group_id']);
			$accountList[$accountInfo->id]['group_list'] = $groupList;
			$accountList[$accountInfo->id]['product_title'] = $addInfo['subject'];
			$accountList[$accountInfo->id]['product_keywords'] = $addInfo['keywords'];
			$accountList[$accountInfo->id]['product_keywords1'] = $addInfo['more_keywords1'];
			$accountList[$accountInfo->id]['product_keywords2'] = $addInfo['more_keywords2'];
			$accountList[$accountInfo->id]['product_price'] = $addInfo['product_price'];
			//查找账号对应的服务模板列表
			$htmlOptions = array();
			$serviceList = CHtml::listOptions($addInfo['service_template_id'], CHtml::listData(AliexpressPromiseTemplate::model()->findAll("account_id = :account_id", array(':account_id' => $accountInfo->id)), 'template_id', 'name'), $htmlOptions);
			$accountList[$accountInfo->id]['service_list'] = $serviceList;
			
			$overseas_warehouse_list = AliexpressAccount::getAccountOvseasWarehouseListById($accountInfo->id);
			$accountList[$accountInfo->id]['overseas_warehouse_list'] = $overseas_warehouse_list;
			
			//查找账号对应的运费模板
			$freightList = CHtml::listOptions($addInfo['freight_template_id'], CHtml::listData(AliexpressFreightTemplate::model()->findAll("account_id = :account_id", array(':account_id' => $accountInfo->id)), 'template_id', 'template_name'), $htmlOptions);
			$accountList[$accountInfo->id]['freight_list'] = $freightList;
			$accountList[$accountInfo->id]['description'] = $addInfo['detail'];
			$overseas_warehouse_list = AliexpressAccount::getAccountOvseasWarehouseListById($accountInfo['id']);
			$accountList[$accountInfo->id]['overseas_warehouse_list'] = $overseas_warehouse_list;
			$overseasWarehouseList = AliexpressAccount::getAccountOvseasWarehouseListById($accountInfo->id);
		}

		//精简模式刊登
		if ($publishMode == AliexpressProductAdd::PRODUCT_PUBLISH_MODE_EASY) {
			$htmlOptions = array();
			$this->render('_formeasy', array(
					'imgomsURL'	=>'',	//$config['oms']['host'],
					'publishParams' => $publishParams,
					'historyCategoryList' => $historyCategoryList,
					'defaultHistoryCategory' => $defaultHistoryCategory,
					'chooseCategoryList' => $categoryList,
					'skuImg' => $skuImg,
					'accountList' => $accountList,
					'skuInfo' => $skuInfo,
					'addInfo' => $addInfo,
					'action' => 'update',
					'skuAttributes' => $skuAttributes,
					'commonAttributes' => $commonAttributes,
					'productUnitList' => CHtml::listOptions($addInfo['product_unit'], AliexpressParamTemplate::getProductUnit(), $htmlOptions),
					'customAttributes' => $customAttributes,
					'variationAttributes' => $variationAttributes,
			        'overseasWarehouseList' => $overseasWarehouseList,
					//'recycleImages' => $recycleImages,
					'selectedImages'	=>	$selectedImages
			));
		}
	}
	
	/**
	 * @desc 批量删除
	 */
	public function actionBatchdelete(){
		try {
			$ids = Yii::app()->request->getParam("ids");
			$AliexpressProductAddModel = new AliexpressProductAdd();
			$resultInfo = $AliexpressProductAddModel
			->getDbConnection()
			->createCommand()
			->delete($AliexpressProductAddModel->tableName(),"id in ($ids) and status in (0,2,5)");
			echo $this->successJson(array(
					'message' => "删除成功",
			));
			Yii::app()->end();
		} catch (Exception $e){
			echo $this->failureJson(array(
					'message' => '操作失败',
			));
			Yii::app()->end();
		};
	}
	
	
	/**
	 * @desc 一键恢复上传中和上传失败的产品
	 */
	public function actionOnekeyrestorelisting(){
		try {
			$ids = Yii::app()->request->getParam("ids");
			$data = array(
				'status'=>0,
				//'upload_message'=>'',
				'upload_count'=>0
			);
			$time = date("Y-m-d H:i:s", time()-1800);
			AliexpressProductAdd::model()->updateByCondition($data, "status in(1, 2, 5) and id in({$ids}) and upload_time<'{$time}'");
			echo $this->successJson(array(
					'message' => '操作成功',
			));
			Yii::app()->end();
		}catch (Exception $e){
			echo $this->failureJson(array(
					'message' => '操作失败',
			));
			Yii::app()->end();
		}
	}


	/**
	 * 待上传状态和图片上传失败从新刊登
	 * /aliexpress/aliexpressproductadd/doubleautoupload
	 */
	public function actionDoubleautoupload() {
		set_time_limit(2*3600);
		ini_set("display_errors", true);
		error_reporting(E_ALL);

		$sku = Yii::app()->request->getParam('sku');
		$imageUploadType = 1;

		$aliProductAddModel = new AliexpressProductAdd();

		$fields = 'id';
		$conditions = '((status = :status AND create_time <= NOW() - INTERVAL 3 HOUR) OR (status = 2 AND upload_count > 3)) AND create_time >= NOW() - INTERVAL 3 DAY';
		$params = array(':status'=>0);

		if($sku){
			$conditions .= " AND sku = '{$sku}'";
		}

		$adds = $aliProductAddModel->getProductAddInfoAll($fields,$conditions,$params);
		$idsArr = array();
		if(!$adds){
			exit('无数据');
		}

		foreach ($adds as $value) {
			// $idsArr[] = $value['id'];
			$ids = array($value['id']);
			$aliProductAddModel->uploadProductByAccount($ids,$value['account_id'], $imageUploadType);
		}

		// $aliProductAddModel->uploadProduct($idsArr);
						
	}
	
	
	/**
	 * STORY #2916 Aliexpress - 产品刊登时sku输入需要验证
	 * /aliexpress/aliexpressproductadd/check_sku_status
	 */
	public function actionCheck_sku_status() {
		$sku = trim(Yii::app()->request->getParam('skuNo'));
		//sku信息
		$skuInfo = Product::model()->getProductInfoBySku($sku);
		//print_r($skuInfo);
		if (empty($skuInfo)) {
			echo $this->failureJson(array(
				'message' => Yii::t('aliexpress', 'SKU Has not Exists'),
				'navTabId' => AliexpressProductAdd::getIndexNavTabId(),
			));			
		} else {
    		    //判断是否侵权
    		    $omsProductInfringe = new ProductInfringe();
    		    $skuInfringeInfo = $omsProductInfringe->getProductIfInfringe($sku);
    		    //echo '1';
    		    //print_r($skuInfringeInfo);
    		    if ($skuInfringeInfo){
    		        echo $this->failureJson(array(
    		            'message' => "sku侵权，不能添加",
    		            'navTabId' => AliexpressProductAdd::getIndexNavTabId(),
    		        ));		      		   		        
		        } else {
		            $omsProduct = new Product();
		            $omsSkuInfo = $omsProduct->getProductInfoBySku($sku);
		            //print_r($omsSkuInfo);
		            //判断是否停售
		            if (isset($omsSkuInfo['product_status']) && $omsSkuInfo['product_status'] == 7){
		                echo $this->failureJson(array(
		                    'message' => "sku已停售，不能添加",
		                    'navTabId' => AliexpressProductAdd::getIndexNavTabId(),
		                ));		            	                
		            } else {
		                /*
		                $offlineReason = array('expire_offline','user_offline','violate_offline','punish_offline','degrade_offline','industry_offline');
            		    $offlineReasonText = array(
            		        'expire_offline' => '过期下架',
            		        'user_offline' => '用户手工下架',
            		        'violate_offline' => '违规下架',
            		        'punish_offline' => '处罚下架',
            		        'degrade_offline' => '降级下架',
            		        'industry_offline' => '行业准入未续约下架'		       
            		    );
            		    //判断是否已经下架
            		    if (isset($skuInfo['product_status_type']) && $skuInfo['product_status_type'] == "offline"){
            		        if (in_array($skuInfo['ws_display'], $offlineReason)){
            		            echo $this->failureJson(array(
            		                'message' => "sku已下架，不能添加，下架原因{$offlineReasonText[$skuInfo['ws_display']]}",
            		                'navTabId' => AliexpressProductAdd::getIndexNavTabId(),
            		            ));
            		        }	        
            		    }
            		    */
		            }
		        }
		    
		}
		Yii::app()->end();
	}	
	
}