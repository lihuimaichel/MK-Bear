<?php
/**
 * @desc Ebay分类控制器
 * @author Tony
 * @since 2015-07-17
 */
class EbaycategoryController extends UebController{
    
    /**@var 分类自动翻译*/
    const TRANSLATE_AUTO = false;
    
    /**
     * @desc 获取分类
     */
    public function actionGetcategory(){
    	set_time_limit(3600);
    	ini_set("display_errors", true);
    	error_reporting(E_ALL);
        $siteArr = array();
        if( isset($_REQUEST['site_id']) ){
            //1.验证站点是否可用 TODO
            $siteArr = array($_REQUEST['site_id']);
            if($_REQUEST['site_id'] == 0){
            	$siteArr[] = EbaySite::EBAY_MOTOR_SITEID;
            }
        }else{
        	$ebaySiteModel = new EbaySite;
            $siteArr = $ebaySiteModel->getSiteIDs();
            $siteArr[] = EbaySite::EBAY_MOTOR_SITEID;
        }
       
        //2.准备日志
        $account = EbayAccount::model()->getAbleAccountByOne();
        $logID = EbayLog::model()->prepareLog($account['id'],EbayCategory::EVENT_NAME); 
        //设置日志为正在运行
        EbayLog::model()->setRunning($logID);
        $ebayCategoryModel = new EbayCategory();
        foreach($siteArr as $site){
            $ebayCategoryModel->setAccountID($account['id']);
            $ebayCategoryModel->setSite($site);
            //3.获取分类信息
            $flag = $ebayCategoryModel->getCategories();
        }
        //4.更新日志信息
        if( $flag ){
            EbayLog::model()->setSuccess($logID);
        }else{
            EbayLog::model()->setFailure($logID, $ebayCategoryModel->getExceptionMessage());
        }
        if( self::TRANSLATE_AUTO ){
            //调用google翻译自动翻译分类 TODO
        }
        if($flag){
        	echo $this->successJson(array(
        		'message'=>"success"
        	));
        }else{
        	echo $this->failureJson(array(
        		'message'=>$ebayCategoryModel->getExceptionMessage()
        	));
        }
    }
    
    /**
     * @desc 获取分类树
     */
    public function actionCategorytree(){
        $siteID = Yii::app()->request->getParam('site_id');
        $parentId = Yii::app()->request->getParam('category_id');
        $categories = EbayCategory::model()->getCategoriesBySiteID($siteID, $parentId);
        if(!$parentId){
            $this->render('CategoryTree', array(
                'categories'    => $categories,
                'siteID'        => $siteID,
            ));
        }else{
        	if($categories){
        		$tempArr = array_slice($categories, 0, 1);
        		$level = $tempArr[0]['level'];
        		$data = array(
        				'statusCode'	=>	200,
        				'level'			=>	$level,
        				'category_list'	=>	$categories
        		);
        	}else{
        		$data = array(
        				'statusCode'	=>	300,
        				'level'			=>	0,
        				'category_list'	=>	array()
        		);
        	}
        	
            echo json_encode($data);
            Yii::app()->end();
        }
    }
    
    /**
     * @desc 根据关键词获取推荐类目
     */
    public function actionGetcategorysuggest() {
    	$siteID = Yii::app()->request->getParam('site_id', 0);
    	$keyword = Yii::app()->request->getParam('keyword');
    	$keyword = str_replace( array(' to ',' for ',' in ',' & ',' + ',' of ',' and ',' or ',' For ',' Of ',' To ',' And ',' Of ',' In '),' ',$keyword );
    	
    	$ebayCategory = new EbayCategory();
    	if (!empty($keyword)) {
    		//获取该站点下的有效账号
    		$accountInfo = EbayAccountSite::model()->getOneAbleAccountBySiteID($siteID);
    		
    		if($accountInfo){
    			$accountID = $accountInfo['id'];
    			$cateList = $ebayCategory->getSuggestCategoryList($accountID, $siteID, $keyword);
    			
    			if($cateList)
    				echo json_encode( array('statusCode'=>200,'categoryList'=>$cateList) );
    			else
    				echo json_encode( array('statusCode'=>400, 'message' => $ebayCategory->getExceptionMessage()) );
    		}else{
    			echo json_encode( array('statusCode'=>400, 'message' => "Invalid Account") );
    		}
    	} else {
    		echo json_encode( array('statusCode'=>400, 'message' => Yii::t('aliexpress_product', 'Search Keywords Empty')) );
    	}
    }
    /**
     * @desc 
     */
    public function actionFindcategoryinfo(){
    	$accountIDs = Yii::app()->request->getParam('account_id');
    	$sku = Yii::app()->request->getParam('sku');
    	$addID = Yii::app()->request->getParam('add_id');
    	$categoryID = Yii::app()->request->getParam('category_id');
    	$siteID = Yii::app()->request->getParam('site_id', 0);
    	$listingType = Yii::app()->request->getParam('listing_type');
    	$accountIDArr = array();
    	if($accountIDs){
    		$accountIDArr = explode(",", $accountIDs);
    	}
    	//取第一个
    	$accountID = $accountIDArr[0];
    	$addInfo = array();
    	if($addID){//存在修改情况，获取对应的刊登信息
    		$addInfo = EbayProductAdd::model()->getEbayProductAddInfoByAddID($addID);
    	}
    	//币种
    	$currency = EbaySite::getCurrencyBySiteID($siteID);
    	
    	//分类名称
    	$categoryName = EbayCategory::model()->getCategoryNameByID($categoryID, $siteID);
    	//获取真实站点ID
    	$categorySiteID = EbayCategory::model()->getCategorySiteId($siteID, $categoryID);
    	//如果分类是ebay motors 则需要指定Item.Site to eBayMotors
    	//并且siteId = 100
    	$sid = $categorySiteID ? $categorySiteID : $siteID;
    	//获取specific
    	$categorySpecifics = EbayCategoryInfo::model()->getCategorySpecifics($accountID, $sid, $categoryID);
    	//获取features
    	$categoryFeatures = EbayCategoryInfo::model()->getCategoryFeatures($accountID, $sid, $categoryID);
    	$conditionId = 0; $specificValues = array();
    	$salePrices[] = array(
    			'account_id' => $accountID,
    			'sale_price' => 0.01,
    			'profit_info'	=>	array()
    	);
    	if($addInfo){
    		//判断是否为修改,直接取
    		$salePrices = array();
    		$salePrices[] = array(
    				'account_id' => $accountID,
    				'sale_price' => $addInfo['start_price'],
    				'discount_price' => $addInfo['discount_price'],
    				'profit_info'	=>	array()
    		);
    		//取 condition
    		$conditionId = $addInfo['condition_id'];
    		//取 属性
    		$specificValues = EbayProductAddSpecific::model()->getEbayProductAddSpecificByAddID($addID, 'name');
    		//var_dump($specificValues);
    	}else{
    		if($listingType == EbayProductAdd::LISTING_TYPE_AUCTION){//拍卖
    			$price = EbayProductAttributeTemplate::model()->getEbayAttributeTemplateBySiteID($siteID, 'auction_price');
    			$salePrice = ($price && $price > 0)  ? floatval($price) : 0.01;
    			$salePrices = array();
    			foreach ($accountIDArr as $accountID){
    				$salePrices[] = array(
    						'account_id' => $accountID,
    						'sale_price' => $salePrice,
    						'profit_info'	=>	array()
    				);
    			}
    		}elseif($listingType != EbayProductAdd::LISTING_TYPE_VARIATION){
    			
    			$price = 0;
    			//判断是否有在线的广告
    			/* $condition = array(
    					'site_id'	=>	$siteID,
    					'sku'		=>	$sku,
    					'item_status'	=>	1,
    					'listing_duration'	=>	'GTC'
    			);
    			$listingInfo = EbayProduct::model()->getEbayProductInfo($condition, "current_price asc", "current_price");
    			$price = isset($listingInfo['current_price'])?$listingInfo['current_price']:0;
    			$condition = "site_id='$siteID' AND sku='$sku' AND status!='". EbayProductAdd::STATUS_SUCCESS ."' AND listing_type != '". EbayProductAdd::LISTING_TYPE_AUCTION ."'";
    			$productAddInfo = EbayProductAdd::model()->getEbayProductAddInfo($condition, 'start_price asc', 'start_price');
    			$price2 = isset($productAddInfo['start_price'])?$productAddInfo['start_price']:0;
    			if($price || $price2){//之前有价格
    				if($price>0){
    					if($price2>0 && $price2<$price){
    						$price = $price2;
    					}
    				}else{
    					$price = $price2;
    				}
    			}else{
    				//@TODO 判断是否有关联的竞争对手
    				//@TODO 取参考的item价格
    				
    			} */
    			$salePrices = array();
    			$prices = array();
    			foreach ($accountIDArr as $accountID){
    				$saleProfitInfo = array();
    				if(!$price){
    					$salepriceData = EbayProductSalePriceConfig::model()->getSalePrice($sku, $currency, $siteID, $accountID, $categoryName);
    					$saleprice = isset($salepriceData['salePrice'])?$salepriceData['salePrice']:'0';
    				}else{
    					$saleprice = $price;
    					$saleProfitInfo = EbayProductSalePriceConfig::model()->getProfitInfo($saleprice, $sku, $currency, $siteID, $accountID, $categoryName);
    				}
    				if(isset($salepriceData) && $salepriceData){
    					$saleProfitInfo = array(
    							0=>$salepriceData['profit'], 	1=>$salepriceData['profitRate'],
    							'profit'=>$salepriceData['profit'], 'profit_rate'=>$salepriceData['profitRate'], 'desc'=>$salepriceData['desc'],
    							'salePriceData'	=>	$salepriceData
    					);
    				}
    				$salePrices[] = array(
    						'account_id' => $accountID,
    						'sale_price' => $saleprice,
    						'profit_info'=>$saleProfitInfo,
    						'price'=>$price
    				);
    			}
    		}
    	}
    	if(!$conditionId && $categoryFeatures !== false && !empty($categoryFeatures['ConditionValues'])){
    		$specialNames = EbayProductAdd::getSpecialConditions();
    		foreach ($categoryFeatures['ConditionValues']->Condition as $condition){
    			$conditionName = strtoupper(trim($condition->DisplayName));
    			if(in_array($conditionName, $specialNames)){
    				$conditionId = trim($condition->ID);
    				break;
    			}
    		}
    	}
    	if($categorySpecifics===false || $categoryFeatures===false || !$salePrices){
    		$status = 400;
    	}else{
    		$status = 200;
    	}
    	$result = array(
    			'statusCode'=> $status,
    			'data'=> array(
    					'category_specifics' => $categorySpecifics,
    					'category_features' => $categoryFeatures,
    					'saleprices' => $salePrices,
    					'base_info' => array(
    							'conditionid' => $conditionId,
    					),
    					'special_values' => $specificValues,
    			),
    	);
    	echo json_encode($result);exit;
    }
    
    /**
     * @desc sku历史分类
     */
    public function actionHistorycategory(){
        $siteID = Yii::app()->request->getParam('site_id');
        $sku = Yii::app()->request->getParam('sku');
        $categories = EbayCategory::model()->getHistoryCategoriesBySiteID($sku,$siteID);
        $this->render('CategorySelect',array(
                'categories'    => $categories,
        ));
    }
    
    /**
     * @desc 获取分类特性
     */
    public function actionGetcategoryfeatures(){
    	$categoryID = 	Yii::app()->request->getParam("cate_id");
    	$accountID 	= 	Yii::app()->request->getParam("account_id");
    	$siteID		=	Yii::app()->request->getParam("site_id", 0);
    	
    	//@TODO 更改为自动从数据获取
    	if(empty($categoryID)) exit('Must Specify the Category ID');
    	
    	if($categoryID && $accountID){
    		$categoryInfoModel = new EbayCategoryInfo;
    		$res = $categoryInfoModel->saveCategoryFeaturesData($categoryID, $accountID, $siteID);
    	}else{
    		//循环
    		$ebayAccountSiteModel = new EbayAccountSite;
    		$accountList = $ebayAccountSiteModel->getAccountSiteListBySiteID($siteID);
    		if(empty($accountList)) exit('No Account');
    		foreach ($accountList as $account){
    			MHelper::runThreadSOCKET($this->route . '/account_id/'.$account['account_id'] . '/' . $account['site_id'] . '/cate_id/'.$categoryID);
    			sleep(2);
    		}
    	}
    }
    
    
    /**
     * @desc 获取分类特性
     */
    public function actionGetcategoryspecifies(){
    	$categoryID = 	Yii::app()->request->getParam("cate_id");
    	$accountID 	= 	Yii::app()->request->getParam("account_id");
    	$siteID		=	Yii::app()->request->getParam("site_id", 0);
    	//@TODO 更改为自动从数据获取
    	if(empty($categoryID)) exit('Must Specify the Category ID');
    	 
    	if($categoryID && $accountID){
    		$categoryInfoModel = new EbayCategoryInfo;
    		$res = $categoryInfoModel->saveCategorySpecificsData($categoryID, $accountID, $siteID);
    	}else{
    		//循环
    		$ebayAccountSiteModel = new EbayAccountSite;
    		$accountList = $ebayAccountSiteModel->getAccountSiteList();
    		if(empty($accountList)) exit('No Account');
    		foreach ($accountList as $account){
    			MHelper::runThreadSOCKET($this->route . '/account_id/'.$account['account_id'] . '/' . $account['site_id'] . '/cate_id/'.$categoryID);
    			sleep(2);
    		}
    	}
    }
    
    
    /**
     * @desc 获取ebay子sku的销售价格
     */
    public function actionGetebayvariationprice(){
    	$addID = Yii::app()->request->getParam('add_id');
    	$siteID = Yii::app()->request->getParam('site_id');
    	$categoryID = Yii::app()->request->getParam('category_id');
    	$skus = Yii::app()->request->getParam('skus');
    	$accountIDs = Yii::app()->request->getParam('account_ids');
    	$priceType = Yii::app()->request->getParam('price_type');//类型 1为同利润率,2为同卖价
    	$status = 200;
    	$saleprices = array();
    	$newVariationProduct = array();
    	$ebaySite = new EbaySite();
    	//分类名称
    	$ebayCategory = new EbayCategory();
    	$categoryName = $ebayCategory->getCategoryNameByID($categoryID);
    	//币种
    	$currency = $ebaySite->getCurrencyBySiteID($siteID);
    	if($addID){//取出已保存的价格信息
    		$variationProduct = EbayProductAddVariation::model()->getEbayProductAddVariationJoinEbayProductAddByAddID($addID);
    		if($variationProduct){
    			foreach ($variationProduct as $variation){
    				$newVariationProduct[$variation['son_sku']] = $variation;
    			}
    		}
    		unset($variationProduct);
    	}
    	$ebayProductSalePriceConfig = new EbayProductSalePriceConfig;
    	$accountInfos = array();
    	$saleprice = 0;
    	$accountIDArr = explode(',',$accountIDs);
    	foreach ($accountIDArr as $accountID){
    		foreach (explode(',',$skus) as $sku){
    			//判断是否为修改,直接取
    			if($addID){
    				$salePrice = $newVariationProduct[$sku]['variation_price'];
    				$shipCost = 0;
    				$saleProfitInfo = array();
    				//$salepriceData = $ebayProductSalePriceConfig->getSalePrice($sku, $currency, $siteID, $accountID, $categoryName);
    				//$saleProfitInfo = $ebayProductSalePriceConfig->getProfitInfo($salePrice, $sku, $currency, $siteID, $accountID, $categoryName, $shipCost);
    				/*
    				if(isset($salepriceData) && $salepriceData){
    					$saleProfitInfo = array(
    							0=>$salepriceData['profit'], 	1=>$salepriceData['profitRate'],
    							'profit'=>$salepriceData['profit'], 'profit_rate'=>$salepriceData['profitRate']
    					);
    				} */
    				$saleprices[] = array(
    						'account_id' 	=> 	$accountID,
    						'sku' 			=> 	$sku,
    						'sale_price' 	=> 	$salePrice,
    						'variation_discount_price' 	=> 	$newVariationProduct[$sku]['variation_discount_price'],
    						'profit_info' 	=> 	$saleProfitInfo,
    						//'salePriceData'		=>	$salepriceData
    				);
    				continue;
    			}
    				
    			//根据sku和利润规则得到卖价
    			if($priceType=='1'){//同利润率
    				if(isset($accountInfos[$accountID])){
    					$targetRate = $accountInfos[$accountID];
    				}else{
    					$targetRate = 0;
    				}
    				
    				$salepriceData = $ebayProductSalePriceConfig->getSalePrice($sku, $currency, $siteID, $accountID, $categoryName, $targetRate);
    				$saleprice = 0;
    				if($salepriceData){
    					$saleprice = $salepriceData['salePrice'];
    				}
    				$accountInfos[$accountID] = $targetRate;
    			}else{//同卖价
    				if(isset($accountInfos[$accountID])){
    					$saleprice = $accountInfos[$accountID];
    				}else{
    					$salepriceData = $ebayProductSalePriceConfig->getSalePrice($sku, $currency, $siteID, $accountID, $categoryName);
    					$accountInfos[$accountID] =  $saleprice = isset($salepriceData['salePrice'])?$salepriceData['salePrice']:'0';
    				}
    			}
    			$saleProfitInfo = array();
    			if(isset($salepriceData) && $salepriceData){
    				$saleProfitInfo = array(
    						0=>$salepriceData['profit'], 	1=>$salepriceData['profitRate'],
    						'profit'=>$salepriceData['profit'], 'profit_rate'=>$salepriceData['profitRate']
    				);
    			}
    			$saleprices[] = array(
    					'account_id' => $accountID,
    					'sku' => $sku,
    					'sale_price' => $saleprice,
    					'profit_info' => $saleProfitInfo,
    					'salePriceData'	=>	$salepriceData
    			);
    		}
    	}
    	$result = array(
    			'statusCode'=> $status,
    			'data' => array(
    					'saleprices' => $saleprices,
    			),
    	);
    	echo json_encode($result);exit;
    }
    
    /**
     * @desc 获取产品利润信息
     */
    public function actionGetebayproductprofitinfo(){
    	$status = 200;
    	$profitInfo = array();
    	
    	$siteID = Yii::app()->request->getParam('site_id');
    	$categoryID = Yii::app()->request->getParam('category_id');
    	$sku = Yii::app()->request->getParam('sku');
    	$shipCost = Yii::app()->request->getParam('ship_cost');
    	//币种
    	$ebaySite = new EbaySite();
    	//分类名称
    	$ebayCategory = new EbayCategory();
    	$categoryName = $ebayCategory->getCategoryNameByID($categoryID);
    	//币种
    	$currency = $ebaySite->getCurrencyBySiteID($siteID);
    	
    	//卖价
    	$salePrice = Yii::app()->request->getParam('sale_price');
    	//账号
    	$accountID = Yii::app()->request->getParam('account_id');
    	//@TODO 求出账号和站点对应的刊登仓库
    	$listingType = Yii::app()->request->getParam('listing_type');
    	//产品信息
    	$productInfo = Product::model()->getProductInfoBySku($sku);
    	$pubPrice = '0.3';
    	if ($currency == 'GBP') {
    		$pubPrice = '0.26';
    	}
    	if($listingType != EbayProductAdd::LISTING_TYPE_AUCTION){
    		$minSalePrice = EbayProductSalePriceConfig::model()->getMinSaleprice($currency);
    		if($salePrice < $minSalePrice){
    			$salePrice = $minSalePrice;
    		}
    	}
    	$ebayProductSalePriceConfig = new EbayProductSalePriceConfig;
    	$saleProfitInfo = $ebayProductSalePriceConfig->getProfitInfo($salePrice, $sku, $currency, $siteID, $accountID, $categoryName, $shipCost);
    	$result = array(
    			'statusCode' => $status,
    			'data' => array(
    					'sale_price' => $salePrice,
    					'profit_info' => $saleProfitInfo,
    			),
    	);
    	echo json_encode($result);exit;
    }

	/**
	 * @desc 获取产品卖价
	 */
	public function actionGetebayproductsaleprice(){
		$status = 200;

		$sku = Yii::app()->request->getParam('sku');
		$siteID = Yii::app()->request->getParam('site_id');
		$accountID = Yii::app()->request->getParam('account_id');
		$categoryID = Yii::app()->request->getParam('category_id');
		$profitRate = Yii::app()->request->getParam('profit_rate');
		$profitRate = $profitRate*0.01;

		//分类名称
		$ebayCategory = new EbayCategory();
		$categoryName = $ebayCategory->getCategoryNameByID($categoryID);
		//币种
		$ebaySite = new EbaySite();
		$currency = $ebaySite->getCurrencyBySiteID($siteID);

		$ebayProductSalePriceConfig = new EbayProductSalePriceConfig;
		$salePriceData = $ebayProductSalePriceConfig->getSalePriceNew($sku, $currency, $siteID, $accountID, $categoryName,$profitRate);

		$result = array(
			'statusCode' => $status,
			'data' => array(
				'sale_price' => $salePriceData['salePrice'],
				'profit_info' => $salePriceData,
			),
		);
		echo json_encode($result);exit;
	}

	//通过分类id获取
	public function actionGetcategorybyid(){

		$siteID = Yii::app()->request->getParam('site_id');
		$categoryID = Yii::app()->request->getParam('category_id');
		$status = 200;

		$ebayCategory = new EbayCategory();

		//确定是否是最终分类
		$categories = EbayCategory::model()->getCategoriesBySiteID($siteID, $categoryID);
		if($categories){
			echo json_encode(array('statusCode' => 300,'message'=>'此分类下还有子分类'));
			exit;
		}

		//获取联动菜单
		$categoryName = $ebayCategory->getBreadcrumbCategory($categoryID,$siteID);
		if(!$categoryName){
			echo json_encode(array('statusCode' => 300,'message'=>'没有找到此分类'));
			exit;
		}

		$result = array(
			'statusCode' => $status,
			'data' => array(
				'category_name' => $categoryName,
			),
		);
		echo json_encode($result);exit;
	}

    
    public function actionTest(){
    	$categoryID = 	Yii::app()->request->getParam("cate_id");
    	$accountID 	= 	Yii::app()->request->getParam("account_id");
    	$siteID		=	Yii::app()->request->getParam("site_id", 0);
    	var_dump($accountID);
    	var_dump($categoryID);
    	//exit;
    	$categoryInfoModel = new EbayCategoryInfo();
    	$res = $categoryInfoModel->getCategorySpecifics($accountID, $siteID, $categoryID);
    	var_dump($res);
    }
} 