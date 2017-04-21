<?php
/**
 * @desc Ebay刊登
 * @author Gordon
 * @since 2015-07-25
 */
class EbayproductaddController extends UebController{

	protected $_specialSiteIds = array(71,77,186);//小语种站点id

    /**
     * @desc 刊登列表
     */
	public function actionIndex(){
    	$model = new EbayProductAdd();
    	$this->render("index", array('model'=>$model));
    }

    /**
     * 临时修复数据库图片image_name
     */
/*    public function actionCleanImageData()
    {

        $tableName = EbayProductImageAdd::model()->tableName();
        $result = EbayProductImageAdd::model()->getDbConnection()->createCommand()->select('id, image_name, local_path')
            ->from($tableName)->where(
                'create_time >= "2017-03-08 00:00:00" AND create_time <= "2017-03-10 23:59:59" AND platform_code=\'EB\' AND POSITION(".jpg" IN image_name) = 0'
            )->order("id", 'DESC')->queryAll();
        $index = 0;
        foreach($result as $data)
        {
            EbayProductImageAdd::model()->getDbConnection()->createCommand()->update(
                $tableName,
                array(
                    'image_name'=> $data['image_name'] .'.jpg',
                    'local_path'=> $data['image_name']. '.jpg',
                ),
                'id= '.$data['id']
            );
            $index++;
        }
        echo $index;
    }*/

    /**
     * @desc Ebay刊登(1.sku录入)
     */
    public function actionProductaddstepfirst(){
        $params = array();
        if( Yii::app()->request->getParam('dialog')==1 ){
            $params['dialog'] = true;
        }
        $this->render('productAdd1',$params);
    }
    
    /**
     * @desc Ebay刊登(2.账号和站点选择)
     */
    public function actionProductaddstepsecond(){
    	try{
    		//获取站点
    		$siteArr = array();
    		$ebaySite = new EbaySite();
    		$siteArr = $ebaySite->getSiteList();
    		$sku = Yii::app()->request->getParam('sku');

    		//从刊登任务那里连接过来的地址
    		$site_id = Yii::app()->request->getParam('site_id', '');
			$account_id = Yii::app()->request->getParam('account_id', '');

    		//获取刊登类型
    		$listingType = EbayProductAdd::getListingType();
    		//可用账号,包括冻结情况
    		//sku信息
    		$skuInfo = Product::model()->getProductInfoBySku($sku);
    		if (empty($skuInfo)) {
    			throw new Exception(Yii::t('ebay', 'SKU has not Exists'));
    		}
    		//检测是否侵权
    		if(ProductInfringe::model()->getProductIfInfringe($sku)){
    			throw new Exception(Yii::t('ebay', 'The SKU has been infringed, can not be uploaded to EBAY'));
    		}
    		
    		//检测是否有权限去刊登该sku
    		//上线后打开注释---lihy 2016-05-10
    		/*if(! Product::model()->checkCurrentUserAccessToSaleSKU($sku, Platform::CODE_EBAY)){
	    		echo $this->failureJson(array(
	    		 		'message' => Yii::t('system', 'Not Access to Add the SKU'),
	    		 		'navTabId' => EbayProductAdd::getIndexNavTabId(),
	    		 ));
    			Yii::app()->end();
    		}*/


//    		$config = ConfigFactory::getConfig('serverKeys');
//    		//sku图片加载
//    		$imageType = array('zt','ft');
//    		$skuImg = array();
//    		foreach($imageType as $type){
//    			$images = Product::model()->getImgList($sku,$type);
//    			foreach($images as $k=>$img){
//    				$skuImg[$type][$k] = $config['oms']['host'].$img;
//    			}
//    		}
            /**@ 获取产品信息*/
            // 更改为拉取JAVA组图片API接口 ketu.lai
            $skuImg = ProductImageAdd::getOrPushImageUrlFromRestfulBySku($skuInfo, $pushWithChild = true, $typeAlisa = null, $type = 'normal', $width = 100, $height = 100, $platform = Platform::CODE_EBAY);
            /**
             * 修复java api接口无主图返回问题
             */
            if (isset($skuImg['ft']) && !isset($skuImg['zt'])) {
                $skuImg['zt'] = $skuImg['ft'];
            }

            if(Product::PRODUCT_MULTIPLE_MAIN == $skuInfo['product_is_multi']){
    			$currenListingType = EbayProductAdd::LISTING_TYPE_VARIATION;
    		}else{
    			$currenListingType = EbayProductAdd::LISTING_TYPE_FIXEDPRICE;
    		}
    		if($currenListingType == EbayProductAdd::LISTING_TYPE_VARIATION){
    			unset($listingType[EbayProductAdd::LISTING_TYPE_AUCTION]);
    			unset($listingType[EbayProductAdd::LISTING_TYPE_FIXEDPRICE]);
    		}elseif ($currenListingType == EbayProductAdd::LISTING_TYPE_FIXEDPRICE){
    			//unset($listingType[EbayProductAdd::LISTING_TYPE_VARIATION]);
    		}

			$userID = isset(Yii::app()->user->id)?Yii::app()->user->id:'';
			if(!$userID){
				echo $this->failureJson(array('message' => '登录状态失效，请重新登录'));
				Yii::app()->end();
			}
			$job_param = 'job_id=:job_id AND is_del =:is_del AND seller_user_id =:seller_user_id';
			$job_array = array(':job_id' => ProductsGroupModel::GROUP_LEADER, ':is_del' => 0,':seller_user_id' => Yii::app()->user->id);
			$is_job = ProductsGroupModel::model()->find($job_param,$job_array);
			if($is_job){//排除组长
				$siteRes = $siteArr;
			}else{
				//销售人员帐号控制
				$userSiteArr = array();
				$userAccountSite = SellerUserToAccountSite::model()->getAccountSiteByCondition(Platform::CODE_EBAY,$userID);
				if($userAccountSite){
					foreach ($userAccountSite as $sellerList) {
						$userSiteArr[] = $sellerList['site'];
					}
					$userSiteArr = array_unique($userSiteArr);
				}
				if($userAccountSite){
					$siteRes = array();
					foreach ($siteArr as $k=>$site){
						if(!in_array($site, $userSiteArr)){
							continue;
						}
						$siteRes[$k] = $site;
					}
				}else{
					$siteRes = $siteArr;
				}
			}

    		//获取站点
    		$this->render('productAdd2',array(
    				'siteArr'       => $siteRes,
    				'sku'           => $sku,
    				'listingType'   => $listingType,
    				'skuImg'        => $skuImg,
    				'skuInfo'		=> $skuInfo,
    				'currenListingType'	=> $currenListingType,
					'site_id' => $site_id,
					'account_id' => $account_id,
    		));
    	}catch (Exception $e){
    		echo $this->failureJson(array(
    				'message' => $e->getMessage(),
    				'navTabId' => EbayProductAdd::getIndexNavTabId(),
    		));
    		Yii::app()->end();
    	}
    }
    
    /**
     * @desc Ebay刊登(3.刊登资料详情)
     */
    public function actionProductaddstepthird(){
    	try{
	        $listingType = Yii::app()->request->getParam('listing_type');
	        $listingSite = Yii::app()->request->getParam('listing_site');
	        $listingAccount = Yii::app()->request->getParam('accounts');
	        $sku = trim(Yii::app()->request->getParam('sku'));
	        if( !$listingType || $listingSite===null || !$listingAccount){
	            throw new CException(Yii::t('ebay', 'Site and Account Not Valid'));
	        }
	        //1.验证sku
	        //sku信息
	        $skuInfo = Product::model()->getProductInfoBySku($sku);
	        if (empty($skuInfo)) {
	        	echo $this->failureJson(array(
	        			'message' => Yii::t('ebay', 'SKU has not Exists'),
	        			'navTabId' => EbayProductAdd::getIndexNavTabId(),
	        	));
	        	Yii::app()->end();
	        }

			//帐号保护期
			$userID = (int)Yii::app()->user->id;
			$accountShare = EbayAccountShare::model()->getAccountBySellerId($userID);
			//验证刊登权限,平台/账号/站点/sku
			foreach ($listingAccount as $sellerAccountId) {
				if(!in_array($sellerAccountId,$accountShare)){
					if(! Product::model()->checkCurrentUserAccessToSaleSKUNew($sku, $sellerAccountId, Platform::CODE_EBAY, $listingSite)){
						echo $this->failureJson(array(
							'message' => Yii::t('system', 'Not Access to Add the SKU'),
							'navTabId' => EbayProductAdd::getIndexNavTabId(),
						));
						Yii::app()->end();
					}
				}
			}

			//主SKU，没有子SKU则提示“异常主sku”，有子SKU选择的是单品提示“主sku不能当做单品刊登”
			if($skuInfo['product_is_multi'] == Product::PRODUCT_MULTIPLE_MAIN){
				$productSelectedAttribute = new ProductSelectAttribute();
				$skuAttributeList = $productSelectedAttribute->getChildSKUListByProductID($skuInfo['id']);
				if(!$skuAttributeList){
					echo $this->failureJson(array(
						'message' => Yii::t('ebay', 'Abnormal main sku'),
						'navTabId' => EbayProductAdd::getIndexNavTabId(),
					));
					Yii::app()->end();
				}

				if($listingType != EbayProductAdd::LISTING_TYPE_VARIATION){
					echo $this->failureJson(array(
						'message' => Yii::t('ebay', 'Main sku can not single publish'),
						'navTabId' => EbayProductAdd::getIndexNavTabId(),
					));
					Yii::app()->end();
				}
			}

	        /**@ 获取刊登参数*/
	        //获取刊登类型
	        $listingTypeArr = EbayProductAdd::getListingType();
	        //获取站点
	        $ebaySite = new EbaySite();
	        $listingSiteArr = $ebaySite->getSiteList();
	        //获取账号
	        $accountInfo = EbayAccount::getAccountInfoById($listingAccount);
			//海外仓帐号获取
			$overseaAccount = array();
			$overseaAccountList = EbayAccount::model()->getAccountsByShortNames(EbayAccount::$OVERSEAS_ACCOUNT_SHORT_NAME);
			if ($overseaAccountList) {
				foreach ($overseaAccountList as $account) {
					$overseaAccount[] = $account['id'];
				}
			}
	       
	        //获取标题和内容
	        /* $skuTitle = Productdesc::model()->getTitleBySkuK($sku);
	        $skuDesc = Productdesc::model()->getDescBySku($sku); */

	        $language = EbaySite::getLanguageBySiteIDs($listingSite);
	        $productDesc = Productdesc::model()->getDescriptionInfoBySkuAndLanguageCode($sku,$language);
	        $skuInfo['title'] = $productDesc['title'];
	        $skuInfo['description'] = $productDesc['description'];
	        $skuInfo['included'] = $productDesc['included'];
	        $skuInfo['sale_price'] = $skuInfo['product_cost'];
	        //获取对应账号下面的店铺分类信息
	        set_time_limit(60);
	        if($accountInfo){
	        	$ebayStoreCategory = new EbayStoreCategory();
	        	foreach ($accountInfo as $key=>$account){
	        		$accountInfo[$key]['store_category'] = $ebayStoreCategory->getCategoryTree($account['id']);
	        	}
	        }
	        /**@ 获取产品信息*/
//	        $config = ConfigFactory::getConfig('serverKeys');
//	        $imageType = array('zt', 'ft');
//	        $skuImg = array();
//	        $images = Product::model()->getImgList($sku, 'ft');
//	        foreach($imageType as $type){
//	        	foreach($images as $k=>$img){
//	        		if($k == $sku) continue;
//	        		$skuImg[$type][$k] = $config['oms']['host'].$img;//TODO 做成系统配置
//	        	}
//	        }
//	        unset($images);

            /**@ 获取产品信息*/
            // 更改为拉取JAVA组图片API接口 ketu.lai
            $skuImg = ProductImageAdd::getImageUrlFromRestfulBySku($sku, $typeAlisa = null, $type = 'normal', $width = 100, $height = 100, $platform = Platform::CODE_EBAY);
            /**
             * 修复java api接口无主图返回问题
             */
            if (isset($skuImg['ft']) && !isset($skuImg['zt'])) {
                $skuImg['zt'] = $skuImg['ft'];
            }


            //获取分类信息
	       	$rootCategoryTree = EbayCategory::model()->getCategoriesBySiteID($listingSite, 0);
	       	//获取货币
	       	$currency = $ebaySite->getCurrencyBySiteID($listingSite);
	       	//获取子sku列表
	       	$variationSkus = EbayProductAdd::model()->getSubSKUListPairsByMainProductId($skuInfo['id']);
	       	//获取具有的属性列表
	       	$attributeList = EbayProductAdd::model()->getProductAttributeListByMainProductId($skuInfo['id']);
	       	$attributes = array(); 
	       	if($attributeList){
	       		foreach ($attributeList as $attribute){
					if($attribute['attribute_name']=='季节段' || $attribute['attribute_name']=='Product features'){ //这两种属性值不需要显示 3.16
						continue;
					}
	       			$attributes[] = $attribute['attribute_name'];
	       		}
	       	}
	       	
	       	//设置是否加图片
	       	$variationPic = '';
	       	$defaultGTIN = 'Does not apply';
	       	/* if(trim($listingSiteArr[$listingSite]) == 'Spain'){//西班牙站点no aplicable
	       		$defaultGTIN = "no aplicable";
	       	} */
	       	$defaultGTIN = EbayProductAdd::model()->getDefaultGTIN($listingSite);
	       	//检测是否已经刊登过
	       	$conditions = array(
	       						'sku'			=>	$sku,
	       						'site_id'		=>	$listingSite,
	       						'listing_type'	=>	$listingType,
	       						'status'		=>	EbayProductAdd::STATUS_SUCCESS
	       					);
	       	$checkIsUpload = EbayProductAdd::model()->getEbayProductAddInfo($conditions, 'update_time desc', 'id');
	       	//获取属性列表
	       	$variationDetail = array();
	       	$newSkus = array();
	       	if($checkIsUpload){
	       		$variationAttributes = EbayProductAddVariationAttribute::model()->getVariationAttributeJoinVariationByWhere("b.add_id={$checkIsUpload['id']} AND b.son_sku in(" . MHelper::simplode($variationSkus) . ")");
	       		if($variationAttributes){
	       			foreach ($variationAttributes as $attribute){
						$variationDetail[$attribute['son_sku']][strtolower($attribute['name'])] = $attribute['value'];
	       			}
	       		}
	       		//检测是否全子sku都取有
	       		foreach ($variationSkus as $variationSku){
	       			if(!isset($variationDetail[$variationSku])){
	       				$newSkus[] = $variationSku;
	       			} 
	       		}
	       		
	       	}else{
	       		$newSkus = $variationSkus;
	       	}
	       	if($newSkus){
	       		//去产品属性表中读取
	       		$variationAttributes = ProductSelectAttribute::model()->getSkuAttributeListBySku($newSkus);
	       		$variationDetail = array_merge($variationDetail, $variationAttributes);
	       		
	       	}
	       	//查找产品历史分类
	       	$historyCategoryList = EbayProductAddCategoryHistory::model()->getHistoryCategoryListPairsBySKU($listingSite, $sku);
	       	//默认历史分类
	       	$defaultHistoryCategory = array();
	       	if (!empty($historyCategoryList)) {
	       		$defaultHistoryCategory = array_slice($historyCategoryList, 0, 1, true);
	       	}else{
	       		//根据标题选择一个默认分类
	       		$keyword = str_replace( array(' to ',' for ',' in ',' & ',' + ',' of ',' and ',' or ',' For ',' Of ',' To ',' And ',' Of ',' In '), ' ', $skuInfo['title'] );
	       		$ebayCategory = new EbayCategory();
	       		if (!empty($keyword)) {
	       			$accountID = $listingAccount[0];
	       			$cateList = array();
	       			$cateList = $ebayCategory->getSuggestCategoryList($accountID, $listingSite, $keyword, $sku);
	       			$firstcate = null;
	       			is_array($cateList) && $cateList && $firstcate = array_shift($cateList);
	       			if($firstcate){
	       				$defaultHistoryCategory[$firstcate['categoryid']] = $firstcate['categoryname'];
	       			}
	       			/* $secondcate = "";
	       			is_array($cateList) && $cateList && $secondcate = array_shift($cateList);
	       			if($secondcate){
	       				$defaultHistoryCategory[$secondcate['categoryid']] = $secondcate['categoryname'];
	       			} */
	       		}
	       	}
	       	//默认排除国家
	       	$defaultExcludeCountryCode = $defaultExcludeCountryNameStr = "";
	       	$defaultExcludeCountry = EbayExcludeShipingCountry::model()->getExcludeShipingCountry($listingSite);
	       	if($defaultExcludeCountry){
	       		$defaultExcludeCountryCode = $defaultExcludeCountry['exclude_ship_code'];
	       		$defaultExcludeCountryNameStr = $defaultExcludeCountry['exclude_ship_name'];
	       	}
	       	
	       	//标题格式化为带账号前后缀的标题
			if ($accountInfo){
				foreach($accountInfo as $key=>$val){
					$accountInfo[$key]['account_title'] = EbayProductDescriptionTemplate::model()->getTitle($skuInfo['title'], $val['id'], $listingSite);
					$accountInfo[$key]['account_detail'] = EbayProductAdd::model()->getDescriptionNoImg($sku, $val['id'], $listingSite);
					$excludeCountry = EbayExcludeShipingCountry::model()->getExcludeShipingCountry($listingSite, $val['id']);
					if($excludeCountry){
						$accountInfo[$key]['exclude_ship_code'] = $excludeCountry['exclude_ship_code'];
						$accountInfo[$key]['exclude_ship_name'] = $excludeCountry['exclude_ship_name'];
					}else{
						$accountInfo[$key]['exclude_ship_code'] = $defaultExcludeCountryCode;
						$accountInfo[$key]['exclude_ship_name'] = $defaultExcludeCountryNameStr;
					}
				}
			}
			//print_r($accountInfo);
			//已选择的图片
			$selectedImages = array('zt'=>array(), 'ft'=>array());
			//@todo test
			//$skuImg = array();
			if($listingType == EbayProductAdd::LISTING_TYPE_AUCTION){
				$listingDurations = array(
										'Days_5'	=>	'5天',
										'Days_3'	=>	'3天',
										'Days_7'	=>	'7天',
										'Days_10'	=>	'10天',
									);
			}else{
				$listingDurations = array(
										'GTC'		=>	'GTC',
										'Days_3'	=>	'3天',
										'Days_5'	=>	'5天',
										'Days_7'	=>	'7天',
										'Days_10'	=>	'10天',
										'Days_30'	=>	'30天',
								);
			}
			
	        $this->render('_formEasy', array(
							        		'sku'           		=> 	$sku,
							        		'skuImg'        		=> 	$skuImg,
	        								'skuInfo'				=> 	$skuInfo,		
							        		'listingType'      		=> 	array('id' => $listingType, 'text' => $listingTypeArr[$listingType]),
							        		'listingSite'      		=> 	array('id' => $listingSite, 'text' => $listingSiteArr[$listingSite]),
							        		'listingAccount'   		=> 	$accountInfo,
	        								'accountIds'			=> 	$listingAccount,
	        								'chooseCategoryList'	=> 	$rootCategoryTree,
	        								'defaultHistoryCategory'=> 	$defaultHistoryCategory,
	        								'historyCategoryList'	=>	$historyCategoryList,
	        								'currency'				=>	$currency,
	        								'variationSkus'			=>	$variationSkus,
	        								'variationDetail'		=>	json_encode($variationDetail),
	        								'attributes'			=>	$attributes,
	        								'variationPic'			=>	$variationPic,
	        								'defaultGTIN'			=>  $defaultGTIN,
	        								'selectedImages'		=>	$selectedImages,
	        								'action'				=> 'add',
	        								'addId'					=>	0,
											'overseaAccount'		=>	json_encode($overseaAccount),
	        								'listingDurations'		=>	$listingDurations,
	        						));
    	}catch(Exception $e){
    		echo $this->failureJson(array(
    				'message'=>$e->getMessage(),
    		));
    	}
        
    }
    
    
    public function actionSaveadddata(){
    	//@todo 验证数据
    	try{
    		$userID = (int)Yii::app()->user->id;
    		$accountIDs = Yii::app()->request->getParam('accountids');
    		if(!$accountIDs){
    			throw new Exception("Invalid Accounts");
    		}
    		$accountIDsArr = explode(",", $accountIDs);
    		//addID
    		$addID = Yii::app()->request->getParam('add_id');
    		//站点ID
    		$siteID = Yii::app()->request->getParam('listing_site');
			//listing_type
			$listingType = Yii::app()->request->getParam('listing_type');
			$sku = trim(Yii::app()->request->getParam('sku'));
			$categoryID = Yii::app()->request->getParam('category_id');
			$categoryID2 = Yii::app()->request->getParam('category_id2', 0);
			$categoryName = Yii::app()->request->getParam('category_name');
			$configType = Yii::app()->request->getParam('config_type');
			$listingDetail = Yii::app()->request->getParam('listing_detail');
			$conditionID = Yii::app()->request->getParam('condition_id');
			$variationPictureSpecific = Yii::app()->request->getParam('variation_picture_specific');
			$variationSkus = Yii::app()->request->getParam('variation_skus');
			$variationPrice = Yii::app()->request->getParam('variation_price');
			$variationDiscountPrice = Yii::app()->request->getParam('variation_discount_price');//折后价
			$variationGtin = Yii::app()->request->getParam('variation_gtin');
			$variationValues = Yii::app()->request->getParam('variation_values');
			$specificCustomvalues = Yii::app()->request->getParam('specific_customvalues');
			$specifics = Yii::app()->request->getParam('specifics');
			$customSpecificNames = Yii::app()->request->getParam('custom_specific_names');
			$customSpecificValues = Yii::app()->request->getParam('custom_specific_values');
			$baseInfo = Yii::app()->request->getParam('baseInfo');
			
			$shippingServices = Yii::app()->request->getParam('services');
			$shippingCosttypes = Yii::app()->request->getParam('costtypes');
			$shippingAdditionalcosts = Yii::app()->request->getParam('additionalcosts');
			
			// 2016-05-14
			$shippingCost = Yii::app()->request->getParam('shipcost');
			$additionalShippingCost = Yii::app()->request->getParam('additionalshipcost');
			
			$shippingShippingServices = Yii::app()->request->getParam('shippingServices');
			$shippingShoptos = Yii::app()->request->getParam('shoptos');
			$shippingLocations = Yii::app()->request->getParam('locations');
			
			//$title = $baseInfo['title'];
			// foreach ($accountIDsArr as $accountID){
			// 	$titles[$accountID] = $title;
			// }

			$titles = array();
			$titles = $baseInfo['title'];
			$details = $baseInfo['detail'];
			$listingDurations = $baseInfo['listing_duration'];
			//$planUploadTime = $baseInfo['plan_upload_time'];
			$storeCategoryIDs = isset($baseInfo['store_category'])?$baseInfo['store_category']:array();
			$storeCategoryIDs2 = isset($baseInfo['store_category2']) ? $baseInfo['store_category2'] : array();
			//$descriptions = $baseInfo['description'];
			$salePrice = isset($baseInfo['sale_price']) ? $baseInfo['sale_price'] : null;
			$discountPrice = isset($baseInfo['discount_price']) ? $baseInfo['discount_price'] : null;
			//验证数据
			if(empty($sku)){
				throw new Exception("Invalid SKU");
			}
			if(!$categoryID){
				throw new Exception("Not Specified Category"); 
			}
			
			$encryptSKU = new encryptSku();
			$ebayProduct = new EbayProduct();
			$ebayProductAddModel = new EbayProductAdd();
			$ebayProductAddSpecificModel = new EbayProductAddSpecific();
			$ebayProductAddVariationModel = new EbayProductAddVariation;
			$ebayProductAddVariationAttributeModel = new EbayProductAddVariationAttribute();
			$ebayProductAddCategoryHistoryModel = new EbayProductAddCategoryHistory;
			$ebayProductAddAuctionModel = new EbayProductAddAuction();
			$ebayProductImageAddModel = new EbayProductImageAdd();
			
			$isUpdate = false;
			if($addID){
				$isUpdate = true;
				//@todo 检查该条listing后台是否已经在上传中 / 上传完成
				$addinfo = $ebayProductAddModel->findByPk($addID);
				if($addinfo['status'] == EbayProductAdd::STATUS_OPERATING){
					throw new Exception("The SKU is Uploading !!!");
				}
				if(!empty($addinfo['item_id']) || $addinfo['status'] == EbayProductAdd::STATUS_OPERATING || $addinfo['status'] == EbayProductAdd::STATUS_SUCCESS){
					throw new Exception("Has Upload The SKU!!!");
				}
			}
			
			//获取账号名
			$accountNames = EbayAccount::model()->getAccountNameByIds($accountIDsArr);
			//拍卖
			$auctionRule = '';
			if($listingType == EbayProductAdd::LISTING_TYPE_AUCTION){
				//$auctionStartTime = Yii::app()->request->getParam('auction_start_time');
				$auctionStatus = Yii::app()->request->getParam('auction_status');
				$auctionRule = Yii::app()->request->getParam('auction_rule');
				$auctionPlanDay = $auctionEndTime = null;
				/* if(strtotime($auctionStartTime) <= time()){
					throw new Exception("拍卖开始时间不能迟于当前时间");
				} */
				if($auctionStatus == 1){
					$auctionPlanDay = Yii::app()->request->getParam('auction_plan_day');
					if(!$auctionPlanDay){
						throw new Exception(Yii::t('ebay', 'Please Input Auction Plan Day'));
					}
					/* $auctionEndTime = Yii::app()->request->getParam('auction_end_time');
					if($auctionEndTime<=$auctionStartTime){
						throw new Exception("拍卖结束时间不能迟于开始时间");
					} */
				}
				foreach ($accountIDsArr as $accountID){
					// 待刊登里面
					$twoHourBefore = date("Y-m-d H:i:s", time()-2*3600);
					$checkConditions = "site_id={$siteID} AND account_id={$accountID} AND sku='{$sku}' AND listing_type='{$listingType}' AND create_time>'{$twoHourBefore}'";
					if($addID){
						$checkConditions.=" and id <> {$addID}";
					}
					$checkIsExists = $ebayProductAddModel->find($checkConditions);
					
					if($checkIsExists){
						throw new Exception("{$accountNames[$accountID]} Has Upload The SKU!!!");
					}
				}
			}elseif(!$addID){// 验证是否已经刊登过，拍卖的不需要
				foreach ($accountIDsArr as $accountID){
					//$titles[$accountID] = addslashes($titles[$accountID]);
					// 待刊登里面
					$checkConditions = "site_id={$siteID} AND account_id={$accountID} AND sku='{$sku}' AND listing_duration='GTC' AND item_status=".EbayProduct::STATUS_ONLINE;
					$checkIsExists = $ebayProduct->find($checkConditions);
					if(!$checkIsExists){
						$checkConditions = "site_id={$siteID} AND account_id={$accountID} AND sku='{$sku}' AND listing_type='{$listingType}' AND status !=".EbayProductAdd::STATUS_SUCCESS;
						$checkIsExists = $ebayProductAddModel->find($checkConditions);
					}
					if($checkIsExists){
						throw new Exception("{$accountNames[$accountID]} Has Upload The SKU!!!");
					}
					$escapeTitle = mysql_real_escape_string($titles[$accountID]);
					//检测标题是否重复
					$checkConditions = "account_id !={$accountID} AND sku='{$sku}' AND title=\"{$escapeTitle}\" AND listing_duration='GTC' AND item_status=".EbayProduct::STATUS_ONLINE;
					$checkIsExists = $ebayProduct->find($checkConditions);
					if(!$checkIsExists){
						$checkConditions = "account_id !={$accountID} AND sku='{$sku}' AND title=\"{$escapeTitle}\" AND listing_type='{$listingType}' AND status !=".EbayProductAdd::STATUS_SUCCESS;
						$checkIsExists = $ebayProductAddModel->find($checkConditions);
					}
					if($checkIsExists){
						throw new Exception("{$accountNames[$accountID]} 已经上传了相同标题的产品了！");
					}
					//检测标题重复率
					$checkConditions = "account_id !={$accountID} AND sku='{$sku}' AND listing_type='{$listingType}'";
					$checkTitles = $ebayProductAddModel->findAll($checkConditions);
					if($checkTitles){
						$titleKeyWords = $titles;
						foreach ($checkTitles as $productInfo){
							$keyWords = explode(' ', $productInfo->title);//拆分title
							$commonWords = array_intersect($titleKeyWords, $keyWords);//找出交集
							$rate = count($commonWords)/count($titleKeyWords);
							if( $rate > 0.9 ){//相似度高于90%
								throw new Exception("{$accountNames[$accountID]}标题和{$accountNames[$productInfo->account_id]}账号中标题相似度达90%！");
							}
						}
					}
				}
			}
			
			$currency = EbaySite::getCurrencyBySiteID($siteID);
			$nowtime = date("Y-m-d H:i:s");
			$failnum = 0;
			$errorMsg = "";
			foreach ($accountIDsArr as $accountID){
				//判断10分钟内有没有刊登过，防止重复
				if(!$addID){
					$allowTime = date("Y-m-d H:i:s",time()-600);
					$repeatConditions = "site_id={$siteID} AND account_id={$accountID} AND sku='{$sku}' AND listing_type='{$listingType}' AND create_time >= '".$allowTime."'";
					$checkIsRepeat = $ebayProductAddModel->find($repeatConditions);
					if($checkIsRepeat){
						continue;
					}
				}

				try{
					$dbtransaction = $ebayProductAddModel->getDbConnection()->getCurrentTransaction();
					if(!$dbtransaction){
						$dbtransaction = $ebayProductAddModel->getDbConnection()->beginTransaction();
					}
					// @todo 控制用户权限设置，等待开启
					/* $checkResult = Product::model()->checkSellerAccessToSaleSKU(Platform::CODE_EBAY, $sku, $accountID, $siteID);
					if(! $checkResult['flag']){
						throw new Exception($checkResult['message']);
					} */
					$addData = array(
									'site_id'		=>	$siteID,
									'account_id'	=>	$accountID,
									'sku'			=>	$sku,
									'seller_sku'	=>	$encryptSKU->getEncryptSku($sku),
									'listing_type'	=>	$listingType,
									'title'			=>	$titles[$accountID],
									'start_price'	=>	$salePrice ? $salePrice[$accountID] : 0,
									'discount_price' =>	$discountPrice ? $discountPrice[$accountID] : 0,
									'currency'		=>	$currency,
									'category_id'	=>	intval($categoryID),
									'category_id2'	=>	intval($categoryID2),
									'store_category_id'	=>	isset($storeCategoryIDs[$accountID])? $storeCategoryIDs[$accountID]:'0',
									'store_category_id2'=>	isset($storeCategoryIDs2[$accountID])? $storeCategoryIDs2[$accountID]:'0',
									'condition_id'	=>	intval($conditionID)>0 ? intval($conditionID) : 0,
									'config_type'	=>	intval($configType),
									'variation_picture_specific'	=> $variationPictureSpecific,
									'auction_rule'	=>	intval($auctionRule),
									'listing_duration'	=>	isset($listingDurations[$accountID]) ? $listingDurations[$accountID] : '',
									//'plan_upload_time'	=>	isset($planUploadTime[$accountID]) ? $planUploadTime[$accountID] : '',
									'brand'			=>	$listingDetail['brand'],
									'mpn'			=>	$listingDetail['mpn'],
									'upc'			=>	$listingDetail['upc'],
									'isbn'			=>	$listingDetail['isbn'],
									'ean'			=>	$listingDetail['ean'],
									'upload_msg'	=>	'',
									'upload_count'	=>	0,
									'uuid'			=>	'',
									'item_id'		=>	0,
									'create_user_id'	=>	intval($userID),
									'update_user_id'	=>	intval($userID),
									'create_time'		=>	$nowtime,
									'update_time'		=>	$nowtime,
									'last_response_time'=>	$nowtime,
									'status'			=>	EbayProductAdd::STATUS_PENDING,
									'exclude_ship_code'	=>	Yii::app()->request->getParam('excludeShippingLocation_'.$accountID.'_code', ''),
									'exclude_ship_name'	=>	Yii::app()->request->getParam('excludeShippingLocation_'.$accountID.'_name', ''),
									'detail'			=>	isset($details[$accountID]) ? $details[$accountID] : ''
								);
					$addInsertID = 0;
					if($addID){
						unset($addData['create_time']);
						unset($addData['create_user_id']);
						$res = $ebayProductAddModel->getDbConnection()->createCommand()->update($ebayProductAddModel->tableName(), $addData, "id={$addID}");
						if(!$res) throw new Exception('Save Info Failure!!!');
						$addInsertID = $addID;
					}else{
						$ebayProductAddModel->getDbConnection()->createCommand()->insert($ebayProductAddModel->tableName(), $addData);
						$addInsertID = $ebayProductAddModel->getDbConnection()->getLastInsertID();
					}
					//图片
					//$ebayProductImageAddModel->deleteSkuImages($sku, $accountID, Platform::CODE_EBAY, $siteID);
					/* if(!$ebayProductImageAddModel->checkImageExistsBySKU($sku, $accountID, $siteID)){
						$ebayProductImageAddModel->addProductImageBySku($sku, $accountID, $siteID);
					} */
					$postImgs = Yii::app()->request->getParam("skuImage");

					$ebayProductImageAddModel->addProductImageByPost($postImgs, $sku, $accountID, $siteID);

					//属性
					$specificsData = array();
					$ebayProductAddSpecificModel->getDbConnection()->createCommand()->delete($ebayProductAddSpecificModel->tableName(), "add_id={$addInsertID}");
					//自定义属性组装
					if($customSpecificNames){
						foreach ($customSpecificNames as $key => $customName){
							if(empty($customName) || !isset($customSpecificValues[$key])) continue;
							$specificsData[] = array(
													'add_id'	=>	$addInsertID,
													'name'		=>	$customName,
													'value'		=>	isset($customSpecificValues[$key]) ? strlen($customSpecificValues[$key])>65?substr($customSpecificValues[$key],0,65):$customSpecificValues[$key] : '',
													'custom'	=>	1
												);
						}
					}
					if($specifics){
  						//add_id,name,value,custom
						foreach ($specifics as $key=>$specific){
							if($specific == "customvalue"){
								if(!isset($specificCustomvalues[$key]) && !isset($specificCustomvalues["specific_".$key])){
									continue;
								}
								$specific = !isset($specificCustomvalues[$key]) ? (isset($specificCustomvalues["specific_".$key]) ? $specificCustomvalues["specific_".$key] : '') : $specificCustomvalues[$key];
							}
							$specificsData[] = array(
									'add_id'	=>	$addInsertID,
									'name'		=>	$key,
									'value'		=>	$specific,
									'custom'	=>	0
							);
						}
					}
					if($specificsData){
						foreach ($specificsData as $specific){
							$ebayProductAddSpecificModel->getDbConnection()->createCommand()->insert($ebayProductAddSpecificModel->tableName(), $specific);
						}
					}
					//拍卖
					$ebayProductAddAuctionModel->getDbConnection()->createCommand()->delete($ebayProductAddAuctionModel->tableName(), "add_id=".$addInsertID);
					if($listingType == EbayProductAdd::LISTING_TYPE_AUCTION){
						//add_id,start_time,end_time,plan_day, auction_status
						
						/**
						 * 
						 * $warehouse = getModel('ebay_account_site')->getWarehouseByAccountSite($accountid,$siteid);//根据账号和站点得到海外仓
						 * $attribute_info = getModel('ebay_product_attribute_template')->getRowBySimple("siteid='".$siteid."' AND abroad_warehouse = '".$warehouse."'");//获取站点配置
						 * $attribute_info['time_zone'] -= 8;//计算时差
						 * 
						 * */
						$auctionStartTime = date("Y-m-d H:i:s");
						$auctionEndTime = date("Y-m-d H:i:s", strtotime("+1 month"));
						$auctionData = array(
											'add_id'		=>	$addInsertID,
											'start_time'	=>	$auctionStartTime,
											'end_time'		=>	$auctionEndTime ? $auctionEndTime : '0000-00-00 00:00:00',
											'plan_day'		=>	$auctionPlanDay ? intval($auctionPlanDay) : 0,
											'auction_status'=>	$auctionStatus ? 1	:	0,
											'update_time'	=>	$nowtime,
											'update_user_id'=>	intval($userID)	
										);
						$res = $ebayProductAddAuctionModel->getDbConnection()->createCommand()->insert($ebayProductAddAuctionModel->tableName(), $auctionData);
						if(!$res){
							throw new Exception("{$accountID}:Save Auction Info Failure!!!");
						}
						
					}
					//子sku
					$ebayProductAddVariationModel->getDbConnection()->createCommand()->delete($ebayProductAddVariationModel->tableName(), "add_id=" . $addInsertID);
					$ebayProductAddVariationAttributeModel->getDbConnection()->createCommand()->delete($ebayProductAddVariationAttributeModel->tableName(), "add_id=" . $addInsertID);
					if($variationSkus){
						foreach ($variationSkus as $variationSKU){
							//子SKU图片
							$ebayProductImageAddModel->deleteSkuImages($variationSKU, $accountID, Platform::CODE_EBAY, $siteID);
							//检测是否存在
							$ebayProductImageAddModel->addProductImageByVariationSku($variationSKU, $accountID, $siteID);
							$variationAddData = array(
									'add_id'	=>	$addInsertID,
									'son_sku'	=>	$variationSKU,
									'son_seller_sku'	=>	$encryptSKU->getEncryptSku($variationSKU),
									'variation_price'	=>	$variationPrice[$accountID][$variationSKU],
									'variation_discount_price'	=>	$variationDiscountPrice[$accountID][$variationSKU],
							);
							$ebayProductAddVariationModel->getDbConnection()->createCommand()->insert($ebayProductAddVariationModel->tableName(), $variationAddData);
							$variationID = $ebayProductAddVariationModel->getDbConnection()->getLastInsertID();
							//添加子sku属性
							$variationAttributes = array();
							//GTIN(upc isbn ean mpn)
							//variation_id  add_id name value
							$gtin = $variationGtin[$variationSKU];
							$gtinkeys = array('UPC', 'EAN', 'ISBN', 'GTIN');
							foreach ($gtinkeys as $gtinkey){
								$variationAttributes[] = array(
										'variation_id'	=>	$variationID,
										'add_id'		=>	$addInsertID,
										'name'			=>	$gtinkey,
										'value'			=>	$gtin
								);
							}
							$variationValue = $variationValues[$variationSKU];
							foreach ($variationValue as $key=>$value){
								if($value == 'customvalue'){
									$value = $specificCustomvalues[$variationSKU][$key];
								}
								$variationAttributes[] = array(
										'variation_id'	=>	$variationID,
										'add_id'		=>	$addInsertID,
										'name'			=>	$key,
										'value'			=>	$value
								);
							}
							
							if($variationAttributes){
								foreach ($variationAttributes as $attribute){
									$ebayProductAddVariationAttributeModel->getDbConnection()->createCommand()->insert($ebayProductAddVariationAttributeModel->tableName(), $attribute);
								}
							}
						}
					}
					//保存物流信息
					$productAddshippingModel = new EbayProductAddShipping();
					$productAddshippingModel->deleteAllByAddID($addInsertID);
					if($shippingShippingServices[$accountID]){
						$prioritys = array();
						foreach ($shippingShippingServices[$accountID] as $key=>$service){
							if(!isset($prioritys[$service])){
								$prioritys[$service] = 0;
							}
							$prioritys[$service]++;
							$shippingtype = $service=='international' ? '2' : '1';
							if($service == 'international' && !empty($shippingShoptos[$accountID][$key])){
								$shiplocation = $shippingShoptos[$accountID][$key];
							}else{
								$location = isset($shippingLocations[$accountID][$key]) ? $shippingLocations[$accountID][$key] : array();
								$shiplocation = implode(',', $location);
							}
							if(empty($shippingServices[$accountID][$key])){
								throw new Exception("运输设置里面需要指定服务商！");
							}
							if($shippingtype == 2 && empty($shiplocation)){
								throw new Exception("非全球运送的需要指定国家！");
							}
							$productAddshippingModel->saveData(array(
									'add_id'		=>	$addInsertID,	
									'site_id'		=>	$siteID,
									'shipping_type'	=>	$shippingtype,
									'shipping_service'	=>	$shippingServices[$accountID][$key],
									'cost_type'			=>	isset($shippingCosttypes[$accountID][$key]) ? $shippingCosttypes[$accountID][$key] : '',
									'additional_cost'	=>	isset($shippingAdditionalcosts[$accountID][$key]) ? floatval($shippingAdditionalcosts[$accountID][$key]) : 0.00,
									'ship_cost'			=>	isset($shippingCost[$accountID][$key]) ? floatval($shippingCost[$accountID][$key]) : 0.00,
									'additional_ship_cost'=> isset($additionalShippingCost[$accountID][$key]) ? floatval($additionalShippingCost[$accountID][$key]) : 0.00,
									'priority'			=>	$prioritys[$service],
									'ship_location'		=>	trim($shiplocation),
							));
						}
					}
					//添加分类历史信息
					$existsInfo = $ebayProductAddCategoryHistoryModel->find("site_id={$siteID} AND sku='{$sku}' AND category_id='{$categoryID}'");
					if(!$existsInfo){
						$categoryData = array(
										'site_id'		=>	$siteID,
										'sku'			=>	$sku,
										'add_user_id'	=>	$userID,
										'category_id' 	=>	$categoryID,
										'category_name'	=>	$categoryName,
										'update_time'	=>	$nowtime
								);
						$ebayProductAddCategoryHistoryModel->getDbConnection()->createCommand()->insert($ebayProductAddCategoryHistoryModel->tableName(), $categoryData);
					}
					
					$dbtransaction->commit();
					//添加成功这后，更新待刊登列表的状态为刊登中
					EbayWaitListing::model()->updateWaitingListingStatus(
						array(
							'create_user_id' => intval($userID),
							'account_id' => $accountID,
							'site_id'	=> $siteID,
							'sku'	=> $sku,
							'create_time' => date('Y-m-d H:i:s')
						),
						EbayWaitListing::STATUS_PROCESS //新增加的算刊登中
					);
					$errorMsg .= $accountNames[$accountID]."保存成功<br/>";
				}catch (Exception $e){
					$dbtransaction->rollback();
					$failnum++;
					//调试模式
					//echo $e->getMessage();
					$errorMsg .= $accountNames[$accountID]." 保存失败：{$e->getMessage()}<br/>";
				}
			}
			$accountTotal = count($accountIDsArr);
			if($failnum == $accountTotal){
				throw new Exception($errorMsg);
			}
			$successNum = $accountTotal-$failnum;
			
			if($isUpdate){
				$navTabId = 'ebay_product_add_list';
			}else{
				$navTabId = $navTabId = 'page'.UebModel::model('Menu')->getIdByUrl('/ebay/ebayproductadd/productaddstepfirst');
			}
			
			echo $this->successJson(array(
									'message'	=>	"{$successNum} Success And {$failnum} Failure<br/>".$errorMsg,
									'navTabId'	=>	$navTabId
							));
			
			//推送SKU
			$pushskus[] = $sku;
			if($variationSkus){
				$pushskus = array_merge($pushskus, $variationSkus);
			}
			$url = Yii::app()->request->hostInfo."/ebay/ebayproductadd/pushskutoimg/account_id/{$accountID}/sku/".implode(",", $pushskus)."/site_id/{$siteID}/status/0";
			MHelper::runThreadBySocket ( $url );
    	}catch (Exception $e){
    		echo $this->failureJson(array(
    							'message'=>$e->getMessage(),
    						));
    	}
    	Yii::app()->end();
    }
    
   	public function actionUpdate(){
   		try{
   			$logFilename = "ebayproductupdate.log";
	   		$addID = Yii::app()->request->getParam('add_id');
	   		//取出addInfo
	   		$conditions = array("id"=>$addID);
	   		$addInfo = EbayProductAdd::model()->getEbayProductAddInfo($conditions);
	   		if(empty($addInfo)){
	   			throw new Exception(Yii::t('ebay', 'The product add record not find'));
	   		}
	   		//只有待上传和失败的可以修改
	   		if(!in_array($addInfo['status'], array(EbayProductAdd::STATUS_PENDING, EbayProductAdd::STATUS_FAILURE,EbayProductAdd::STATUS_IMGPENDING))){
	   			throw new Exception(Yii::t('ebay', 'Only pending and failure status can modify'));
	   		}
	   		$listingType = $addInfo['listing_type'];
	   		$listingSite = $addInfo['site_id'];
	   		$listingAccount = array($addInfo['account_id']);
	   		$sku = trim($addInfo['sku']);
	   		//MHelper::writefilelog($logFilename, "SKU:{$sku}--START---".date("Y-m-d H:m:s")."\r\n");
	   		if( !$listingType || $listingSite===null || !$listingAccount){
	   			throw new CException(Yii::t('ebay', 'Fill Form Error'));
	   		}
	   		//1.验证sku
	   		//sku信息
	   		$skuInfo = Product::model()->getProductInfoBySku($sku);
	   		if (empty($skuInfo)) {
	   			throw new CException(Yii::t('ebay', 'SKU has not Exists'));
	   		}
	   		
	   		/**@ 获取刊登参数*/
	   		//获取刊登类型
	   		$listingTypeArr = EbayProductAdd::getListingType();
	   		//获取站点
	   		$ebaySite = new EbaySite();
	   		$listingSiteArr = $ebaySite->getSiteList();
	   		//获取账号
	   		$accountInfo = EbayAccount::getAccountInfoById($listingAccount);
			//海外仓帐号获取
			$overseaAccount = array();
			$overseaAccountList = EbayAccount::model()->getAccountsByShortNames(EbayAccount::$OVERSEAS_ACCOUNT_SHORT_NAME);
			if ($overseaAccountList) {
				foreach ($overseaAccountList as $account) {
					$overseaAccount[] = $account['id'];
				}
			}

	   		//获取标题和内容
	   		$skuTitle = Productdesc::model()->getTitleBySkuK($sku);
	   		$skuDesc = Productdesc::model()->getDescBySku($sku);
	   		$skuInfo['title'] = isset($skuTitle['english']) ? $skuTitle['english'] : '';
	   		//$skuInfo['description'] = isset($skuTitle['english']) ? $skuTitle['english'] : '';
	   		$skuInfo['sale_price'] = $skuInfo['product_cost'];
	   		
	   		//MHelper::writefilelog($logFilename, "SKU:{$sku}--2222---".date("Y-m-d H:m:s")."\r\n");
	   		
	   		//获取对应账号下面的店铺分类信息
	   		set_time_limit(60);
	   		if($accountInfo){
	   			$ebayStoreCategory = new EbayStoreCategory();
	   			foreach ($accountInfo as $key=>$account){
	   				$accountInfo[$key]['store_category'] = $ebayStoreCategory->getCategoryTree($account['id']);
	   				if(empty($addInfo['detail'])){
	   					$accountInfo[$key]['account_detail'] = EbayProductAdd::model()->getDescriptionNoImg($sku, $account['id'], $listingSite);
	   				}else{
	   					$accountInfo[$key]['account_detail'] = $addInfo['detail'];
	   				}
	   				$accountInfo[$key]['exclude_ship_code'] = $addInfo['exclude_ship_code'];
	   				$accountInfo[$key]['exclude_ship_name'] = $addInfo['exclude_ship_name'];
	   			}
	   		}
	   		//MHelper::writefilelog($logFilename, "SKU:{$sku}--3333---".date("Y-m-d H:m:s")."\r\n");
	   		/**@ 获取产品信息*/
//	   		$config = ConfigFactory::getConfig('serverKeys');
//	   		$imageType = array('zt', 'ft');
//	   		$skuImg = array();
//	   		//MHelper::writefilelog($logFilename, "SKU:{$sku}- Product::model()->getImgList Start---".date("Y-m-d H:m:s")."\r\n");
//	   		$images = Product::model()->getImgList($sku, 'ft');
//	   		//MHelper::writefilelog($logFilename, "SKU:{$sku}- Product::model()->getImgList End---".date("Y-m-d H:m:s")."\r\n");
//	   		foreach($imageType as $type){
//	   			foreach($images as $k=>$img){
//	   				if($k == $sku) continue;
//	   				$skuImg[$type][$k] = $config['oms']['host'].$img;//TODO 做成系统配置
//	   			}
//	   		}
//	   		unset($images);

            /**@ 获取产品信息*/
            // 更改为拉取JAVA组图片API接口 ketu.lai
            $skuImg = ProductImageAdd::getImageUrlFromRestfulBySku($sku, $typeAlisa = null, $type = 'normal', $width = 100, $height = 100, $platform = Platform::CODE_EBAY);
			/**
			 * 修复java api接口无主图返回问题
			 */
			if (isset($skuImg['ft']) && !isset($skuImg['zt'])) {
				$skuImg['zt'] = $skuImg['ft'];
			}


			//获取分类信息
	   		//MHelper::writefilelog($logFilename, "SKU:{$sku}- GET Category Start---".date("Y-m-d H:m:s")."\r\n");
	   		$rootCategoryTree = EbayCategory::model()->getCategoriesBySiteID($listingSite, 0);
	   		//MHelper::writefilelog($logFilename, "SKU:{$sku}- GET Category End---".date("Y-m-d H:m:s")."\r\n");
	   		//获取货币
	   		$currency = $ebaySite->getCurrencyBySiteID($listingSite);
	   		//获取子sku列表
	   		//$variationSkus = EbayProductAdd::model()->getSubSKUListPairsByMainProductId($skuInfo['id']);
	   		//获取已添加好的子sku列表
	   		$variationSkus = EbayProductAdd::model()->getSubSKUListPairsByAddId($addID);
	   		//获取具有的属性列表
	   		$attributeList = EbayProductAdd::model()->getProductAttributeListByMainProductId($skuInfo['id']);
	   		$attributes = array();
	   		/* if($attributeList){
	   			foreach ($attributeList as $attribute){
	   				$attributes[] = $attribute['attribute_name'];
	   			}
	   		} */
	   		
	   		//设置是否加图片
	   		$variationPic = $addInfo['variation_picture_specific'];
	   		$defaultGTIN = 'Does not apply';
	   		/* if(trim($listingSiteArr[$listingSite]) == 'Spain'){//西班牙站点no aplicable
	   			$defaultGTIN = "no aplicable";
	   		} */
	   		$defaultGTIN = EbayProductAdd::model()->getDefaultGTIN($listingSite);
	   		
	   		//检测是否已经刊登过
	   		$conditions = array(
	   				'id'			=>	$addID
	   				/* 'sku'			=>	$sku,
	   				'site_id'		=>	$listingSite,
	   				'listing_type'	=>	$listingType,
	   				'status'		=>	EbayProductAdd::STATUS_SUCCESS */
	   		);
	   		//$checkIsUpload = EbayProductAdd::model()->getEbayProductAddInfo($conditions, 'update_time desc', 'id');
	   		//获取属性列表
	   		$variationDetail = array();
	   		$newSkus = array();
	   		$filterKeys = array('GTIN', 'EAN', 'UPC', 'ISBN');
	   		$variationGtins = $variationAttrNames = array();
	   		//if($checkIsUpload){
	   			$variationAttributes = EbayProductAddVariationAttribute::model()->getVariationAttributeJoinVariationByWhere("b.add_id={$addID}");
	   			if($variationAttributes){
	   				foreach ($variationAttributes as $attribute){
	   					if(in_array(strtoupper($attribute['name']), $filterKeys)){
	   						$variationGtins[$attribute['son_sku']][$attribute['name']] = $attribute['value'];
	   						continue;
	   					}
	   					$variationDetail[$attribute['son_sku']][strtolower($attribute['name'])] = $attribute['value'];
						if($attribute['name']=='季节段' || $attribute['name']=='Product features'){ //这两种属性值不需要显示 3.16
							continue;
						}
	   					$attributes[$attribute['name']] = trim($attribute['name']);
	   				}
	   			}
	   			//检测是否全子sku都取有
	   			foreach ($variationSkus as $variationsku){
	   				if(!isset($variationDetail[$variationsku])){
	   					$newSkus[] = $variationsku;
	   				}
	   			}
	   			 
	   		//}else{
	   		//	$newSkus = $variationSkus;
	   		//}
	   		if($newSkus){
	   			//去产品属性表中读取
	   			$variationAttributes = ProductSelectAttribute::model()->getSkuAttributeListBySku($newSkus);
	   			$variationDetail = array_merge($variationDetail, $variationAttributes);
	   			 
	   		}
	   		//查找产品历史分类
	   		$historyCategoryList = EbayProductAddCategoryHistory::model()->getHistoryCategoryListPairsBySKU($listingSite, $sku);
	   		//默认历史分类
	   		$defaultHistoryCategory = array();
	   		if (!empty($historyCategoryList)) {
	   			$defaultHistoryCategory = array_slice($historyCategoryList, 0, 2, true);
	   		}
	   		if($addID){
	   			$defaultHistoryCategory = array($addInfo['category_id'] => EbayCategory::model()->getBreadcrumbCategory($addInfo['category_id'], $addInfo['site_id']));
	   			//二级分类
	   			$defaultHistoryCategory[$addInfo['category_id2']] = EbayCategory::model()->getBreadcrumbCategory($addInfo['category_id2'], $addInfo['site_id']);
	   		}
	   		//获取拍卖信息
	   		$auctionInfo = array();
	   		if($addInfo['listing_type'] == EbayProductAdd::LISTING_TYPE_AUCTION){
	   			$auctionInfo = EbayProductAddAuction::model()->getAuctionInfoByAddID($addID);
	   		}
	   		//MHelper::writefilelog($logFilename, "SKU:{$sku}-IMGSTART---".date("Y-m-d H:m:s")."\r\n");
	   		//已选择的图片
	   		$newImgList = array();
	   		$ebayProductImageAdd = new EbayProductImageAdd();
	   		$selectedImages = array('zt'=>array(), 'ft'=>array());
	   		$imgList = $ebayProductImageAdd->getImageBySku($sku, $addInfo['account_id'], Platform::CODE_EBAY, $addInfo['site_id']);

	   		if($imgList){
	   			foreach ($imgList as $type=>$imgs){
	   				foreach ($imgs as $img){
	   					$imgkey =  $img['image_name'];
	   					//$imgkey = substr($img['image_name'], 0, strrpos($img['image_name'], "."));
	   					if($type == EbayProductImageAdd::IMAGE_ZT){
	   						$selectedImages['zt'][] = $imgkey;
	   						if(!empty($skuImg['zt'][$imgkey])){
	   							$newImgList['zt'][$imgkey] = $skuImg['zt'][$imgkey];
	   							unset($skuImg['zt'][$imgkey]);
	   						}
	   					}else{
	   						$selectedImages['ft'][] = $imgkey;
	   						if(!empty($skuImg['ft'][$imgkey])){
	   							$newImgList['ft'][$imgkey] = $skuImg['ft'][$imgkey];
	   							unset($skuImg['ft'][$imgkey]);
	   						}
	   					}
	   				}
	   			}
	   		}
	   		$newImgList = array_merge_recursive($newImgList, $skuImg);
	   		//MHelper::writefilelog($logFilename, "SKU:{$sku}-IMAGE END---".date("Y-m-d H:m:s")."\r\n");
	   		//print_r($newImgList);
	   		//print_r($selectedImages);
	   		if($listingType == EbayProductAdd::LISTING_TYPE_AUCTION){
	   			$listingDurations = array(
	   					'Days_5'	=>	'5天',
	   					'Days_3'	=>	'3天',
	   					'Days_7'	=>	'7天',
	   					'Days_10'	=>	'20天',
	   			);
	   		}else{
	   			$listingDurations = array(
	   					'GTC'		=>	'GTC',
	   					'Days_3'	=>	'3天',
	   					'Days_5'	=>	'5天',
	   					'Days_7'	=>	'7天',
	   					'Days_10'	=>	'10天',
	   					'Days_30'	=>	'30天',
	   			);
	   		}
	   		
	   		//MHelper::writefilelog($logFilename, "SKU:{$sku}-END---".date("Y-m-d H:m:s")."\r\n");
	   		
	   		$this->render('_formEasy', array(
	   				'sku'           		=> 	$sku,
	   				'skuImg'        		=> 	$newImgList,
	   				'skuInfo'				=> 	$skuInfo,
	   				'listingType'      		=> 	array('id' => $listingType, 'text' => $listingTypeArr[$listingType]),
	   				'listingSite'      		=> 	array('id' => $listingSite, 'text' => $listingSiteArr[$listingSite]),
	   				'listingAccount'   		=> 	$accountInfo,
	   				'accountIds'			=> 	$listingAccount,
	   				'chooseCategoryList'	=> 	$rootCategoryTree,
	   				'defaultHistoryCategory'=> 	$defaultHistoryCategory,
	   				'historyCategoryList'	=>	$historyCategoryList,
	   				'currency'				=>	$currency,
	   				'variationSkus'			=>	$variationSkus,
	   				'variationAttrNames'	=>	$variationAttrNames,
	   				'variationDetail'		=>	json_encode($variationDetail),
	   				'variationGtins'		=>	json_encode($variationGtins),
	   				'attributes'			=>	$attributes,
	   				'variationPic'			=>	$variationPic,
	   				'defaultGTIN'			=>  $defaultGTIN,
	   				'action'				=>  'update',
	   				'addId'					=>	$addID,
	   				'addInfo'				=>	$addInfo,
	   				'auctionInfo'			=>	$auctionInfo,
	   				'selectedImages'		=>	$selectedImages,
					'overseaAccount'		=>	json_encode($overseaAccount),
	   				'listingDurations'		=>	$listingDurations
	   		));
   		}catch (Exception $e){
   			echo $this->failureJson(array(
   					'message' => $e->getMessage(),
   					'navTabId' => EbayProductAdd::getIndexNavTabId(),
   			));
   			Yii::app()->end();
   		}
   	}
   	
    /**
     * @desc 获取可用账号 TODO
     */
    public function actionGetableaccount(){
        $sku            = Yii::app()->request->getParam('sku');
        $siteID         = Yii::app()->request->getParam('listing_site');
        $listingType	= Yii::app()->request->getParam('listing_type');
        
        //获取该站点下可用的账号
        //$ebayAccountSiteModel = new EbayAccountSite;
        // $accountList = $ebayAccountSiteModel->getAbleAccountInfoListBySiteIdAndSku($siteID, $sku);
        if($listingType != EbayProductAdd::LISTING_TYPE_AUCTION){
        	$listingType = array(EbayProductAdd::LISTING_TYPE_FIXEDPRICE, EbayProductAdd::LISTING_TYPE_VARIATION);
        }
        $accountList = EbayProductAdd::model()->getAbleAccountListBySku($sku, $siteID, $listingType);
		$userID = isset(Yii::app()->user->id)?Yii::app()->user->id:'';
		if(!$userID){
			echo $this->failureJson(array('message' => '登录状态失效，请重新登录'));
			Yii::app()->end();
		}

		$job_param = 'job_id=:job_id AND is_del =:is_del AND seller_user_id =:seller_user_id';
		$job_array = array(':job_id' => ProductsGroupModel::GROUP_LEADER, ':is_del' => 0,':seller_user_id' => $userID);
		$is_job = ProductsGroupModel::model()->find($job_param,$job_array);

		//限制帐号显示
		$accountIdArr = array();
		$userAccountSite = SellerUserToAccountSite::model()->getAccountSiteByCondition(Platform::CODE_EBAY, $userID);
		if ($userAccountSite) {
			foreach ($userAccountSite as $sellerList) {
				$accountIdArr[] = $sellerList['account_id'];
			}
			$accountIdArr = array_unique($accountIdArr);
		}
		//共享帐号查询
		$accountShare = EbayAccountShare::model()->getAccountBySellerId($userID);
		$accountIdArr = array_merge($accountIdArr,$accountShare);

        $result = array();
        if($accountList){
        	foreach ($accountList as $account){
				if($is_job){//排除组长

				}else {
					if($userAccountSite && !in_array($account['id'], $accountIdArr)){
						continue;
					}
				}

				$result[] = array(
					'id'            => $account['id'],
					'short_name'    => $account['short_name'],
					'flag'			=>	$account['is_upload']
				);

        	}
        }
        echo json_encode($result);exit;
    }
    
    /**
     * @desc 上传产品
     * @link /ebay/ebayproductadd/uploadproduct/add_id/xx/bug/1
     */
    public function actionUploadproduct(){
    	set_time_limit(7200);
    	error_reporting(E_ALL);
    	ini_set('display_errors', true);
    	$addID = Yii::app()->request->getParam('add_id');
    	$ebayProductAdd = new EbayProductAdd();
    	$flag = $ebayProductAdd->uploadProductData($addID, 1);
    	if($flag){
    		echo $this->successJson(array(
    			'message'	=>	Yii::t('system', 'Successful')
    		));
    	}else{
    		$msg = $ebayProductAdd->getErrorMessage();
    		echo $this->failureJson(array(
    			'message'	=>	$msg
    		));
    	}
    	Yii::app()->end();
    }
    
    /**
     * @desc 批量删除
     */
	public function actionBatchdel(){
		$ids = Yii::app()->request->getParam("ids");
		if($ids){
			$idarr = explode(",", $ids);
			$ebayProductAddModel = new EbayProductAdd();
			$res = $ebayProductAddModel->batchDel($idarr);
			if($res){
				echo $this->successJson(array(
					'message'	=>	Yii::t('system', 'Successful')
				));
				Yii::app()->end();
			}
			
		}
		echo $this->failureJson(array(
				'message'	=>	"操作失败"
		));
		
		Yii::app()->end();
	}
	
	/**
	 * @desc 获取物流运费信息
	 */
	public function actionGetshipping(){
	
		$accountIDs = Yii::app()->request->getParam("account_id");
		$siteID = Yii::app()->request->getParam("site_id");
		$configType = Yii::app()->request->getParam("config_type");
		$addID = Yii::app()->request->getParam("add_id");
		//获取匹配的物流信息
		$shippingTemplate = array();
		$ebayCategoryInfoModel = new EbayCategoryInfo();
		if($accountIDs){
			$accountArr = explode(",", $accountIDs);
			foreach ($accountArr as $accountID){
				//查出账号站点所对应的仓库
				$wareHouseID = EbayAccountSite::model()->getWarehouseByAccountSite($accountID, $siteID);
				//获取属性模板
				$attributeInfo = EbayProductAttributeTemplate::model()->getProductAttributeTemplage("site_id='".$siteID."' AND abroad_warehouse = '".$wareHouseID."' AND config_type = ".$configType);
				if($addID){
					$shippingTemplate[$accountID] = EbayProductAddShipping::model()->getProductShippingInfoByAddID($addID);
				}
				if(empty($shippingTemplate[$accountID]) && $attributeInfo){
					$shippingTemplate[$accountID] = EbayProductShippingTemplate::model()->getShippingTemplateListByPid($attributeInfo['id']);
					if($shippingTemplate[$accountID]){
						foreach ($shippingTemplate[$accountID] as &$template){
							$template['ship_cost'] = "0";
							$template['additional_ship_cost'] = "0";
						}
					}
				}
			}
		}
		
		//运输信息
		$shippingInfo = $ebayCategoryInfoModel->getShippingInfo($siteID);
		//特殊国家
		$specialCountry = EbayProductAttributeTemplate::model()->special_country;
		$data =  array(
				'specialCountry'	=>	implode(",", $specialCountry),
				'shippingTemplate'	=>	$shippingTemplate,
				'shippingInfo'		=>	$shippingInfo
		);
		echo $this->successJson($data);
	}
	
	/**
	 * @desc 验证子sku有效性
	 * @throws Exception
	 */
	public function actionVerifyvariationsku(){
		try{
			$sku = Yii::app()->request->getParam("sku");
			//此sku是否存在，是否侵权，是否为主sku
			if(empty($sku)) throw new Exception("参数错误");
			$skuInfo = Product::model()->getProductInfoBySku($sku);
			if (empty($skuInfo)) {
				throw new Exception(Yii::t('ebay', 'SKU has not Exists'));
			}
			//检测是否为主SKU
			if($skuInfo['product_is_multi'] == Product::PRODUCT_MULTIPLE_MAIN){
				throw new Exception(Yii::t('ebay', '这个sku为主SKU，不能刊登'));
			}
			//检测是否侵权
			if(ProductInfringe::model()->getProductIfInfringe($sku)){
				throw new Exception(Yii::t('ebay', 'The SKU has been infringed, can not be uploaded to EBAY'));
			}
			echo $this->successJson(array());
		}catch (Exception $e){
			echo $this->failureJson(array(
									'message'	=>	$e->getMessage()
								));		
		}
	}

	/**
	 * @desc 自动上传产品 -- 小语种
	 */
	public function ActionAutoUploadProductSpecial(){		
		ini_set('memory_limit','2048M');
		ini_set("display_errors", true);
		error_reporting(E_ALL);
		$accountID = Yii::app()->request->getParam("account_id");
		$hour = Yii::app()->request->getParam("hour", '');

		if ($accountID) {
			set_time_limit(7200);
		} else {
			set_time_limit(3600);
		}

		if($accountID){
			try{				
				$eventName = "upload_product_special";
				$ebayLogModel = new EbayLog();
				$logID = $ebayLogModel->prepareLog($accountID, $eventName);
				$matchSiteIdstr = "";				
				if(!$logID){
					throw new Exception("不能创建LOG ID");
				}
				if(!$ebayLogModel->checkRunning($accountID, $eventName)){
					throw  new Exception("Event has exists");
				}
				$ebayLogModel->setRunning($logID);
				$message = "$hour\r\n";

				$ebayProductAddModel = new EbayProductAdd();
				$matchSiteIds = $ebayProductAddModel->matchSiteIdByTime($hour);
				if (empty($matchSiteIds)) {
					throw  new Exception('matchSiteIds is empty');
				}			
				$matchSiteIds = array_unique($matchSiteIds);	
				$specialSiteIds = $this->_specialSiteIds;//小语种站点id
				$matchSiteIds = array_intersect($matchSiteIds,$specialSiteIds);//取交集
				if (empty($matchSiteIds)) {
					throw  new Exception('matchSiteIds is empty');
				}

				$matchSiteIdstr = implode(",", $matchSiteIds);
				$message .= "siteids:{$matchSiteIdstr} \r\n";
				$limit = 100;
				if(isset($_REQUEST['bug'])){
					echo "<pre>";
					print_r($matchSiteIds);
				}
				if($matchSiteIds){
					$penddingList = $ebayProductAddModel->getPenddingUploadProductListByLimit($limit, $matchSiteIds, $accountID, 'id,sku','',2);
					if(isset($_REQUEST['bug'])){
						echo $siteID, "<br/>";
						print_r($penddingList);
					}
					if(!$penddingList){
						throw  new Exception('penddingList is empty');
					}
					foreach ($penddingList as $list){
						$ebayProductAddModel = new EbayProductAdd();
						$flag = $ebayProductAddModel->uploadProductData($list['id']);
						if(!$flag){
							$message .= $list['sku'] . " ". $ebayProductAddModel->getErrorMessage() ."  \r\n";
						}
					}
				}
				$ebayLogModel->setSuccess($logID, $message);
				echo "Success!<br>";
			}catch (Exception $e){
				$message = $e->getMessage();
				if($logID)
					$ebayLogModel->setFailure($logID, $e->getMessage());
			}
			echo "site_id:", $matchSiteIdstr ,"<br/>";
			echo $accountID,"-done<br/>";
			if(isset($_REQUEST['bug'])){
				echo $message;
			}
		}else{
			$ebayAccounts = EbayAccount::model ()->getAbleAccountList ();
			foreach ( $ebayAccounts as $account ) {
				$url = Yii::app()->request->hostInfo."/".$this->route."/account_id/".$account['id']."/hour/".$hour;
				MHelper::runThreadBySocket ( $url );
				echo $url."<br/>";
				sleep ( 30 );
			}
		}
		Yii::app()->end('finish');
	}	

	/**
	 * @desc 自动上传产品 - 复制刊登
	 * @link /ebay/ebayproductadd/autoUploadProductCopy
	 *       /ebay/ebayproductadd/autoUploadProductCopy/account_id/8/hour/15
	 */
	public function ActionAutoUploadProductCopy(){
		ini_set('memory_limit','2048M');
		ini_set("display_errors", true);
		error_reporting(E_ALL);

		$accountID = Yii::app()->request->getParam("account_id");
		$hour = Yii::app()->request->getParam("hour", '');

		if ($accountID) {
			set_time_limit(7200);
		} else {
			set_time_limit(3600);
		}

		if($accountID){
			try{				
				$eventName = "upload_product_copy";
				$ebayLogModel = new EbayLog();
				$logID = $ebayLogModel->prepareLog($accountID, $eventName);
				$matchSiteIdstr = "";				
				if(!$logID){
					throw new Exception("不能创建LOG ID");
				}
				if(!$ebayLogModel->checkRunning($accountID, $eventName)){
					throw  new Exception("Event has exists");
				}
				$ebayLogModel->setRunning($logID);
				$message = "$hour\r\n";

				$ebayProductAddModel = new EbayProductAdd();
				// $matchSiteIds = $ebayProductAddModel->matchSiteIdByTime($hour);
				// if (empty($matchSiteIds)) {
				// 	throw  new Exception('matchSiteIds is empty');
				// }			
				// $matchSiteIds = array_unique($matchSiteIds);	
				$matchSiteIds = array(0,2,3,15);
				$matchSiteIdstr = implode(",", $matchSiteIds);
				$message .= "siteids:{$matchSiteIdstr} \r\n";
				$limit = 200;
				if(isset($_REQUEST['bug'])){
					echo "<pre>";
					print_r($matchSiteIds);
				}
				if($matchSiteIds){
					$penddingList = $ebayProductAddModel->getPenddingUploadProductListByLimit($limit, $matchSiteIds, $accountID, 'id,sku','',3);
					if(isset($_REQUEST['bug'])){
						echo $siteID, "<br/>";
						print_r($penddingList);
					}
					if(!$penddingList){
						throw  new Exception('penddingList is empty');
					}
					foreach ($penddingList as $list){
						$ebayProductAddModel = new EbayProductAdd();
						$flag = $ebayProductAddModel->uploadProductData($list['id']);
						if(!$flag){
							$message .= $list['sku'] . " ". $ebayProductAddModel->getErrorMessage() ."  \r\n";
						}
					}
				}
				$ebayLogModel->setSuccess($logID, $message);
				echo "Success!<br>";
			}catch (Exception $e){
				$message = $e->getMessage();
				echo $message."<br>";
				if($logID)
					$ebayLogModel->setFailure($logID, $e->getMessage());
			}
			echo "site_id:", $matchSiteIdstr ,"<br/>";
			echo $accountID,"-done<br/>";
			if(isset($_REQUEST['bug'])){
				echo $message;
			}
		}else{
			EbayProductAdd::model()->resetUploadCount();//检查图片待上传失败次数，并重置超过2小时的记录
			$accountArr = array(15,9,5,47,42,38,49,51,41,26,25,3,52,18,8,16,23,19,22,12,14,70);
			$allowAccount = array();
			$ebayAccounts = EbayAccount::model ()->getAutoUploadAccountList();
			foreach($ebayAccounts as $k=>$v){
				$allowAccount[$k] = $v['id'];
			}
			//$ebayAccounts = EbayAccount::model ()->getAbleAccountList ();
			//foreach ( $ebayAccounts as $account ) {
			foreach ($accountArr as $account_id) {
				if(!in_array($account_id,$allowAccount)){//如果帐号没有开启自动上传则跳过
					continue;
				}
				$url = Yii::app()->request->hostInfo."/".$this->route."/account_id/".$account_id."/hour/".$hour;
				//$url = Yii::app()->request->hostInfo."/".$this->route."/account_id/".$account['id']."/hour/".$hour;
				MHelper::runThreadBySocket ( $url );
				echo $url."<br/>";
				sleep ( 60 );
			}
		}
		Yii::app()->end('finish');
	}	

	/**
	 * @desc 自动上传产品 -- 普通、批量刊登
	 */
	public function actionAutouploadproduct(){
		ini_set('memory_limit','2048M');
		ini_set("display_errors", true);
		error_reporting(E_ALL);

		$accountID = Yii::app()->request->getParam("account_id");
		$hour = Yii::app()->request->getParam("hour", '');
		$type = Yii::app()->request->getParam("type");

		if ($accountID) {
			set_time_limit(7200);
		} else {
			set_time_limit(3600);
		}

		if($accountID){
			try{
				$ebayProductAddModel = new EbayProductAdd();
				//释放被卡死的添加记录
				$ebayProductAddModel->recoverUploadingProductToFailure($accountID);
				$eventName = "upload_product";
				$ebayLogModel = new EbayLog();
				$logID = $ebayLogModel->prepareLog($accountID, $eventName);
				$matchSiteIdstr = "";				
				if(!$logID){
					throw new Exception("不能创建LOG ID");
				}
				if(!$ebayLogModel->checkRunning($accountID, $eventName)){
					throw  new Exception("Event has exists");
				}
				$ebayLogModel->setRunning($logID);
				$message = "$hour\r\n";
				if($type == 1){
					$matchSiteIds = EbaySite::model()->getSiteIDs();
					sort($matchSiteIds);
				}else{
					$matchSiteIds = $ebayProductAddModel->matchSiteIdByTime($hour);
				}
				
				if (empty($matchSiteIds)) {
					throw  new Exception('matchSiteIds is empty');
				}			
				$matchSiteIds = array_unique($matchSiteIds);	
				$specialSiteIds = $this->_specialSiteIds;//小语种站点id
				$matchSiteIds = array_diff($matchSiteIds, $specialSiteIds);
				if (empty($matchSiteIds)) {
					throw  new Exception('matchSiteIds is empty');
				}

				$matchSiteIdstr = implode(",", $matchSiteIds);
				$message .= "siteids:{$matchSiteIdstr} \r\n";
				$limit = 200;
				if(isset($_REQUEST['bug'])){
					echo "<pre>";
					print_r($matchSiteIds);
				}

				$allowListingType = array(EbayProductAdd::LISTING_TYPE_FIXEDPRICE, EbayProductAdd::LISTING_TYPE_VARIATION);
				$penddingList = $ebayProductAddModel->getPenddingUploadProductListByLimit($limit, $matchSiteIds, $accountID, 'id,sku', '', array(0, 1), $allowListingType);
				if(isset($_REQUEST['bug'])){
					echo "penddingList<br/>";
					print_r($penddingList);
				}
				if(!$penddingList){
					throw  new Exception('penddingList is empty');
				}
				foreach ($penddingList as $list){
					$ebayProductAddModel = new EbayProductAdd();
					$flag = $ebayProductAddModel->uploadProductData($list['id']);
					if(!$flag){
						$message .= $list['sku'] . " ". $ebayProductAddModel->getErrorMessage() ."  \r\n";
						// $ebayProductAddModel->updateByPk($list['id'], array(
						// 	'upload_msg'	=>	$ebayProductAddModel->getErrorMessage()
						// ));
					}
				}
				$ebayLogModel->setSuccess($logID, $message);
				echo "Success!<br>";
			}catch (Exception $e){
				$message = $e->getMessage();
				if($logID)
					$ebayLogModel->setFailure($logID, $e->getMessage());
			}
			echo "site_id:", $matchSiteIdstr ,"<br/>";
			echo $accountID,"-done<br/>";
			if(isset($_REQUEST['bug'])){
				echo $message;
			}
		}else{
			$ebayAccounts = EbayAccount::model ()->getAutoUploadAccountList();
			// $excludeAccountIDs = array(/* '11', '10','22','20','21','24','29','46','63','64' */,'13','37','54','55','57','59','60','62');
			
			foreach ( $ebayAccounts as $account ) {
				// if(in_array($account['id'], $excludeAccountIDs)) continue;
				$url = Yii::app()->request->hostInfo."/".$this->route."/account_id/".$account['id']."/hour/".$hour."/type/".$type;
				MHelper::runThreadBySocket ( $url );
				echo $url."<br/>";
				sleep ( 120 );
			}
		}
		Yii::app()->end('finish');
	}
	
	/**
	 * @desc 批量上传
	 */
	public function actionBatchupload(){
		set_time_limit(3600);
		$ids = Yii::app()->request->getParam("ids");
		$errorMsg = "";
		if($ids){
			$idArr = explode(",", $ids);
			
			$ebayProductAddModel = new EbayProductAdd();
			foreach ($idArr as $id){
				$res = $ebayProductAddModel->uploadProductData($id, 1);
				if(!$res){
					$addInfo = $ebayProductAddModel->findByPk($id);
					$errorMsg .= "<br/>".$addInfo['title'] . ":" .$ebayProductAddModel->getErrorMessage();
				}
			}
		}else{
			$errorMsg = "没有选择记录";
		}
		echo $this->successJson(array(
				'message'	=>	$errorMsg
		));
		Yii::app()->end();
	}
	
	/**
	 * @desc 查找带回屏蔽国家数据页面
	 */
	public function actionGetexcludeshippinglocationlookup(){
		$siteID = Yii::app()->request->getParam('site_id');
		$accountID = Yii::app()->request->getParam('account_id');
		$addID = Yii::app()->request->getParam('add_id');
		//$countryCode = Yii::app()->request->getParam('code');
		$excludeShippingLocation = EbayCategoryInfo::model()->getExcludeShippingLocation($siteID);
		
		$continents = $excludeShippingLocation['Worldwide'];
		unset($excludeShippingLocation['Worldwide']);
		
		$conditions = array("id"=>$addID);
		$addInfo = EbayProductAdd::model()->getEbayProductAddInfo($conditions);
		$selectedCountry = array();
		if(!empty($addInfo['exclude_ship_code'])){
			$selectedCountry = explode(",", $addInfo['exclude_ship_code']);
		}else{
			$selectedCountry = EbayProductAdd::model()->getExcludeShipCountryBySite($siteID);
			
			$defaultExcludeCountry = EbayExcludeShipingCountry::model()->getExcludeShipingCountry($siteID, $accountID);
			if($defaultExcludeCountry){
				$selectedCountry = explode(",", $defaultExcludeCountry['exclude_ship_code']);
			}
		}
		$this->render('excludeshippinglocationlookup', 
						array(
							'excludeShippingLocation'	=>	$excludeShippingLocation,
							'siteID'					=>	$siteID,
							'selectedCountry'			=>	$selectedCountry				
						));
	}
	
	/**
	 * @desc 更新店铺分类
	 * @throws Exception
	 * @link /ebay/ebayproductadd/updatestorecategory/account_id/xx
	 */
	public function actionUpdatestorecategory(){
		$accountID = Yii::app()->request->getParam("account_id");
		if($accountID){
			try{
				$ebayStoreCategory = new EbayStoreCategory();
				$res = $ebayStoreCategory->updateStoreCategory($accountID);
				if($res){
					$storeCategory = $ebayStoreCategory->getCategoryTree($accountID);
					echo $this->successJson(array('store_category'=>$storeCategory));
				}else{
					throw new Exception($ebayStoreCategory->getErrorMsg());
				}
			}catch(Exception $e){
				echo $this->failureJson(array('message'=>$e->getMessage()));
			}
		}else{
			$ebayAccounts = EbayAccount::model ()->getAbleAccountList ();
			foreach ( $ebayAccounts as $account ) {
				$url = Yii::app()->request->hostInfo."/".$this->route."/account_id/".$account['id'];
				MHelper::runThreadBySocket ( $url );
				//echo $url."<br/>";
				sleep ( 5 );
			}
		}
	}

	public function actionMatchcategory(){
		set_time_limit(3600);
		ini_set("display_errors", true);
		error_reporting(E_ALL);

		$langCode = Yii::app()->request->getParam("lang");
		if($langCode){
			$limit = 500;
			$offset = 0;
			$market_product = EbayProduct::model();
			$accountIDs = array(64,67,50,34,12);
			$lanCodeSite = array('French'=>71, 'German'=>77, 'Spain'=>186);//需要翻译的语言
			if(!isset($lanCodeSite[$langCode])){
				exit(" NO Match Site");
			}
			$tableName = 'ueb_product_description';
			$maxID = 0;
			do{
				$aindex = rand(0, 4);
				$accountID = $accountIDs[$aindex];
				
				$command = $market_product->dbConnection->createCommand()
				                        ->select('id, sku, title')
				                        ->from($tableName)
				                        ->where(" language_code = '{$langCode}' and title != ''")
				                        ->order(' id asc, sku asc ');
										
				if($maxID){
					$command->andWhere($maxID > 0 ? "id>{$maxID}" : "1");
					$command->limit($limit);
				}else{
					$command->limit($limit, $offset);
				}
				
				$result	= $command->queryAll();
				$offset += $limit;
				if($result){
					foreach ($result as $res){
						$maxID = $res['id'];
						//var_dump($res);
						$ebayCategory = new EbayCategory();
						$ebayCategory->getSuggestCategoryList($accountID, $lanCodeSite[$langCode], $res['title'], $res['sku']);
						//var_dump($ebayCategory->getExceptionMessage());
					}
				}
			}while($result);
			
		}else{
			$lanCodeArr = array('fr'=>'French','de'=>'German','es'=>'Spain');//需要翻译的语言
			$accoutID = 64;
			foreach ($lanCodeArr as $lang){
				$url = Yii::app()->request->hostInfo."/".$this->route."/account_id/".$accoutID."/lang/".$lang;
				//MHelper::runThreadBySocket ( $url );
				echo $url."<br/>";
				//sleep ( 30 );
			}
		}
	}

	/**
	 * @desc push sku到图片服务器上，进行预上传
	 * @link /ebay/ebayproductadd/pushskutoimg/account_id/4/sku/xxx,ssx/site_id/0
	 */
	public function actionPushskutoimg(){
		try{
			$skus = Yii::app()->request->getParam("sku");
			$accountID = Yii::app()->request->getParam("account_id");
			$status = Yii::app()->request->getParam("status", 1);
			if(empty($skus)){
				throw new Exception("没有指定SKU");
			}
			if(empty($accountID)){
				throw new Exception("没有指定帐号");
			}
			$siteID = Yii::app()->request->getParam("site_id");
			$skuArr = explode(",", $skus);
			//查找主sku对应下面的子SKU
			foreach ($skuArr as $sku){
				$skuInfo = Product::model()->getProductInfoBySku($sku);
				if (!empty($skuInfo) && $skuInfo['product_is_multi'] == Product::PRODUCT_MULTIPLE_MAIN ) {
					//获取产品库中的子SKU列表
					$variationSkuOriList = EbayProductAdd::model()->getSubSKUListPairsByMainProductId($skuInfo['id']);
					if($variationSkuOriList){
						$skuArr = array_merge($skuArr,$variationSkuOriList);
					}
				}
			}

			$res = EbayProductImageAdd::addSkuImageUpload($accountID,$skuArr, $status);
			var_dump($res);
		}catch (Exception $e){
			echo $e->getMessage();
		}
	}
	
	/**
	 * @desc 更新详情
	 * @throws Exception
	 */
	public function actionUpdatedesc(){
		set_time_limit(3600);
		error_reporting(E_ALL);
		$accountID = Yii::app()->request->getParam("account_id");
		$ids = Yii::app()->request->getParam("ids");
		try{
			if(!$ids){
				throw new Exception("没有指定更新的记录");
			}
			$model = EbayProductAdd::model();
			$lists = $model->findAll("id in (".$ids.") and item_id>0 and status='".EbayProductAdd::STATUS_SUCCESS."'");
			if($lists){
				foreach ($lists as $list){
					$res = $model->changOnlineDescriptionByItemID($list['item_id'], $list['account_id']);
					
					throw new Exception($model->getErrorMessage());
				}
			}else{
				throw new Exception("没有找到符合条件的listing：需要刊登成功的！");
			}
			echo $this->successJson(array('message'=>'执行完毕，请检查结果'));
		}catch (Exception $e){
			echo $this->failureJson(array('message'=>$e->getMessage()));
		}
	}
	
} 