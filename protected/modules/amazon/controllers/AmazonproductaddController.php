<?php
/**
 * @desc Amazon 产品刊登
 * @author Liz
 *
 */
class AmazonproductaddController extends UebController {
	/**
	 * @desc 待刊登列表显示
	 */
	public function actionList() {
		$model = AmazonProductAdd::model();
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

		$siteArr = AmazonSite::getSiteAllList();
		$sku = trim(Yii::app()->request->getParam('publish_sku'));
        $account_id = trim(Yii::app()->request->getParam('account_id'));

		//检测是否有权限去刊登该sku
		//上线后打开注释---lihy 2016-05-10
		if(! Product::model()->checkCurrentUserAccessToSaleSKU($sku, Platform::CODE_AMAZON)){
			echo $this->failureJson(array(
					'message' => Yii::t('system', 'Not Access to Add the SKU'),
					'navTabId' => AmazonProductAdd::getIndexNavTabId(),
			));
			Yii::app()->end();
		} 

		//刊登模式列表
		$publishModeList = AmazonProductAdd::getProductPublishModelList();
		//刊登类型列表
		$publishTypeList = AmazonProductAdd::getProductPublishTypeList();
		//sku信息
		$skuInfo = Product::model()->getProductInfoBySku($sku);
		if (empty($skuInfo)) {
			echo $this->failureJson(array(
				'message' => Yii::t('amazon_product', 'Sku Not Exists'),
				'navTabId' => AmazonProductAdd::getIndexNavTabId(),
			));
			Yii::app()->end();
		}

//		$config = ConfigFactory::getConfig('serverKeys');
//		//sku图片加载
//		$imageType = array('zt','ft');
//		$skuImg = array();
//		foreach($imageType as $type){
//			$images = Product::model()->getImgList($sku,$type);
//			if ($images){
//				foreach($images as $k=>$img){
//					if (strpos($k,'-') > 0){
//						$skuImg[$type][$k] = $config['oms']['host'].$img;
//					}
//				}
//			}
//		}
        $skuImg = ProductImageAdd::getOrPushImageUrlFromRestfulBySku($skuInfo, $pushWithChild = true, $typeAlisa = null, $type = 'normal', $width = 100, $height = 100, $platform = Platform::CODE_AMAZON);
        /**
         * 修复java api接口无主图返回问题
         */
        if (isset($skuImg['ft']) && !isset($skuImg['zt'])) {
            $skuImg['zt'] = $skuImg['ft'];
        }

		$this->render('step2', array(
			'siteArr'         => $siteArr,
			'publishModeList' => $publishModeList,
			'publishTypeList' => $publishTypeList,
			'sku'             => $sku,
            'account_id'      => $account_id,
			'skuInfo'         => $skuInfo,
			'skuImg'          => $skuImg
		));
	}
	
	/**
	 * 刊登第三步，填写刊登数据
	 */
	public function actionStep3() {
    	error_reporting(E_ALL);
    	ini_set('display_errors', true);

		$sku          = Yii::app()->request->getParam('publish_sku');
		$publish_site = Yii::app()->request->getParam('publish_site');		
		$accounts     = Yii::app()->request->getParam('accounts');
		$publishType  = Yii::app()->request->getParam('publish_type');
		$publishMode  = Yii::app()->request->getParam('publish_mode');
		$accountName = '';

		//检查刊登账号是否设置
		if (empty($accounts)) {
			echo $this->failureJson(array(
				'message' => Yii::t('amazon_product', 'Have not Chosen Account'),
			));
			Yii::app()->end();
		}else{
			$accountID = $accounts[0];	//当前只充许一个账号刊登
		}

		$getSubSKUList         = array();
		$subSKUList            = array();
		$amazonProductAddModel = new AmazonProductAdd();
		$encryptSku            = new encryptSku();
		$productImageAdd       = new AmazonProductImageAdd();
		$MainSellSku           = $encryptSku->getAmazonEncryptSku($sku);
		$imageType             = array('zt', 'ft');
		$config                = ConfigFactory::getConfig('serverKeys');		

		$accountInfos = AmazonAccount::model()->getAccountInfoByIds($accounts);
		//检测是否有权限去刊登该sku --- yangsh 2016-12-14
		foreach ($accountInfos as $accountInfo) {	
			if(! Product::model()->checkCurrentUserAccessToSaleSKUNew($sku, $accountInfo->id, Platform::CODE_AMAZON)){
				echo $this->failureJson(array(
						'message' => Yii::t('system', 'Not Access to Add the SKU'),
				));
				Yii::app()->end();
			}
		}

		$publishParams = array(
				'sku'                      => $sku,
				'publish_site'             => $publish_site,
				'publish_type'             => array(
					'id'   => $publishType,
					'text' => $amazonProductAddModel->getProductPublishTypeList($publishType),
				),
				'publish_mode'             => array(
					'id'   => $publishMode,
					'text' => $amazonProductAddModel->getProductPublishModelList($publishMode),
				),
				'amazon_product_type'      => AmazonSite::getSiteProductType(),	//亚马逊平台产品类型
				'publish_product_readonly' => 0,								//基本产品			
		);

		//产品信息
		$skuInfo = Product::model()->getProductInfoBySku($sku);

		//如果是主SKU，单品刊登错误提示，一如果没有子SKU则提示“异常主sku”，二是如果有子SKU提示“主sku不能当做单品刊登”
		if ($skuInfo){
			//多属性
			if ($skuInfo['product_is_multi'] == Product::PRODUCT_MULTIPLE_MAIN){	
				//获取子SKU
				$getSubSKUList = ProductSelectAttribute::model()->getChildSKUListByProductID($skuInfo['id']);						
				if (!$getSubSKUList){
				// 	echo $this->failureJson(array(
				// 		'message' => Yii::t('amazon_product', 'Main sku can not single publish'),
				// 	));				
				// }else{
					echo $this->failureJson(array(
						'message' => Yii::t('amazon_product', 'Abnormal main sku'),
					));
					Yii::app()->end();
				}			
			}else{
				//单品
				$getSubSKUList[$skuInfo['id']] = $skuInfo['sku'];
			}
		}else{
			echo $this->failureJson(array(
				'message' => Yii::t('amazon_product', 'Sku Not Exists'),
			));
			Yii::app()->end();
		}	

		if($getSubSKUList){
			foreach($getSubSKUList as $key => $val){
				$sell_sku = '';
				$sell_sku = $encryptSku->getAmazonEncryptSku($val);

				//子SKU选中的图片字符串	   			 
				$imageStr = $amazonProductAddModel->getSkuSelectedImage($val, $accountID);  				

				$subSKUList[$val]['product_id']                        = $key;
				$subSKUList[$val]['sku']                               = $val;
				$subSKUList[$val]['skuInfo']['sell_sku']               = $sell_sku;
				$subSKUList[$val]['skuInfo']['upload_status']          = 0;
				$subSKUList[$val]['skuInfo']['price']                  = '';	//10.00;
				$subSKUList[$val]['skuInfo']['market_price']           = '';	//10.00 * 1.8;
				$subSKUList[$val]['skuInfo']['sale_start_time']        = date('Y-m-d H:i:s');
				$subSKUList[$val]['skuInfo']['sale_end_time']          = date('Y-m-d H:i:s',strtotime("+20 year"));
				
				$subSKUList[$val]['skuInfo']['inventory']              = AmazonProductAdd::PRODUCT_INVENTORY_DEFAULT;
				$subSKUList[$val]['skuInfo']['shipping']               = 0;
				$subSKUList[$val]['skuInfo']['price_profit']           = '';
				$subSKUList[$val]['skuInfo']['price_error']            = '';
				$subSKUList[$val]['skuInfo']['image']                  = $imageStr;
				
				$subSKUList[$val]['skuInfo']['sub_product_readonly']   = 0;
				$subSKUList[$val]['skuInfo']['sub_price_readonly']     = 0;
				$subSKUList[$val]['skuInfo']['sub_inventory_readonly'] = 0;
				$subSKUList[$val]['skuInfo']['sub_image_readonly']     = 0;	

			}
		}else{
			echo $this->failureJson(array(
				'message' => Yii::t('amazon_product', 'Publish Sub Info not Exists'),
			));
			Yii::app()->end();	
		}

		//查找产品历史分类
		$historyCategoryList = $amazonProductAddModel->getSkuHistoryCategory($sku);
		//默认历史分类
		$defaultHistoryCategory = array();
		if (!empty($historyCategoryList)) {
			$defaultHistoryCategory = array_slice($historyCategoryList, -1, 1, true);
		}
		//查找产品顶级分类列表
		$categoryList = array();
		$topCategories = AmazonCategory::model()->getSubCategoryList(0);	//获取父分类ID=0的根分类列表
		if ($topCategories) {
			foreach ($topCategories as $category) {
				$categoryList[$category['id']] = $category['en_name'];		//不用category_id，不唯一，所以用自增ID
			}
		}

//		//获取主SKU产品图片
//		$skuImg = array();
//		foreach($imageType as $type){
//			$images = Product::model()->getImgList($sku,$type);
//			if ($images){
//				foreach($images as $k=>$img){
//					if (strpos($k,'-') > 0){
//						$skuImg[$type][$k] = $img;
//					}
//				}
//			}
//		}
        $skuImg = ProductImageAdd::getImageUrlFromRestfulBySku($sku, $typeAlisa = null, $type = 'normal', $width = 100, $height = 100, $platform = Platform::CODE_AMAZON);
        /**
         * 修复java api接口无主图返回问题
         */
        if (isset($skuImg['ft']) && !isset($skuImg['zt'])) {
            $skuImg['zt'] = $skuImg['ft'];
        }

        //查找需要刊登的账号信息
		$accountList = array();
		//$accountInfos = AmazonAccount::model()->getAccountInfoByIds($accounts);
		if ($accountInfos){
			foreach ($accountInfos as $accountInfo) {		
				$accountList[$accountInfo->id]['account_name'] = $accountInfo->account_name;

				//设置产品描述
				$description = (isset($skuInfo['description']['english'])) ? $skuInfo['description']['english'] : '';
				$include     = (isset($skuInfo['included']['english'])) ? $skuInfo['included']['english'] : '';	//配件
				$content     = (isset($skuInfo['description']['english'])) ? $skuInfo['description']['english'] : '';
				$title       = (isset($skuInfo['title']['english'])) ? $skuInfo['title']['english'] : ''; 

				//产品标题等前缀处理
				$accountArr = array();
				$tmpName = $accountInfo->account_name;
				if ($tmpName){
					//针对个别账号特殊前缀（例如部分品牌账号），其它都是用账号名称
					switch ($tmpName){
						case 'ecoolbuy-us':
							$tmpName = 'mojoyce';
							break;
						case 'easydeal88-us':
							$tmpName = 'alloet';
							break;
						case 'mmdex-us':
							$tmpName = 'jocestyle';
							break;
						case 'diamond66-us':
							$tmpName = 'diamondo';
							break;	
						case 'd-us':
							$tmpName = 'awakingdemi';
							break;
						case 'w-us':
							$tmpName = 'winnerEco';
							break;																														
					}
					$accountArr = explode('-',$tmpName);
				}

				if($accountArr) $accountName = ucfirst($accountArr[0]);		
				$accountID = $accountInfo->id;		
				$accountList[$accountInfo->id]['product_title']  = $accountName.' '.$title;
				$accountList[$accountInfo->id]['product_brand']  = $accountName;	//品牌，可用店铺名称，默认用账号名		
				$accountList[$accountInfo->id]['manufacturer']   = $accountName;	//制造商，可用店铺名称，默认用账号名
				$accountList[$accountInfo->id]['mfr_partnumber'] = $MainSellSku; //制造商编码，默认使用在线SKU

				//产品简述
				$bullet_point = '';
				$accountList[$accountInfo->id]['bullet_point']  = $bullet_point;					//产品简述（最多五条）

				if(!empty($include)) $description .= '<br />'.$include;	//内容描述加上配件信息
				$accountList[$accountInfo->id]['description'] = $description;				
			}
		}

		// MHelper::printvar($subSKUList);

		//精简模式刊登
		$htmlOptions = array();
		if ($publishMode == AmazonProductAdd::PRODUCT_PUBLISH_MODE_EASY) {
		// MHelper::printvar($subSKUList);
			$this->render('_formeasy', array(
				'action'                 => 'create',
				'addID'					 => 0,
				'cur_account_id'         => $accountID,
				'imgomsURL'              =>'',	//$config['oms']['host'],
				'publishParams'          => $publishParams,
				'historyCategoryList'    => $historyCategoryList,
				'defaultHistoryCategory' => $defaultHistoryCategory,
				'chooseCategoryList'     => $categoryList,
				'skuImg'                 => $skuImg,
				'accountList'            => $accountList,
				'skuInfo'                => $skuInfo,
				'listingSubSKU'			 => $subSKUList
			));
		}
	}


	/**
	 * 刊登记录修改
	 */
	public function actionUpdate() {
    	// error_reporting(E_ALL);
    	// ini_set('display_errors', true);
		
		$seller_sku           = '';
		$amazonIdType         = '';
		$amazonIdText         = '';
		$standardPrice        = '';
		$productTypeText      = '';
		$havePublishFlag      = 0;	//没有刊登成功的标识
		$subSKUList           = array();

		$amazonProductAddModel = new AmazonProductAdd();
		$addID        = Yii::app()->request->getParam('id');
		$addInfo      = AmazonProductAdd::model()->getInfoById($addID);
		$varationList = AmazonProductAddVariation::model()->getVariationProductAdd($addID);
		if (empty($addInfo)) {
			echo $this->failureJson(array(
				'message' => Yii::t('amazon_product', 'Record Not Exists'),
			));
			Yii::app()->end();
		}
		if (empty($varationList)) {
			echo $this->failureJson(array(
				'message' => Yii::t('amazon_product', 'Variation Record Not Exists'),
			));
			Yii::app()->end();
		}

		$sku                = $addInfo['sku'];
		$accountID          = $addInfo['account_id'];
		$publishType        = $addInfo['publish_type'];
		$publishMode        = $addInfo['publish_mode'];
		$countryCode        = $addInfo['country_code'];
		$categoryID         = $addInfo['category_id'];
		$categoryPath       = $addInfo['category_path'];
		$title              = $addInfo['title'];
		$description        = $addInfo['description'];
		$brand              = $addInfo['brand'];
		$manufacturer       = $addInfo['manufacturer'];
		$partNumber         = $addInfo['part_number'];		
		$bulletPointAll     = $addInfo['bullet_point'];
		$searchTermsAll	 	= $addInfo['search_terms'];	//搜索关键字
		$productTypeTextStr = $addInfo['product_type_text'];	//XSD分类				

		if (!empty($productTypeTextStr)){
			$productTypeText = explode('.',$productTypeTextStr);
		}		

		$accountInfo = AmazonAccount::model()->getAccountInfoById($accountID);	
		$accountList[$accountID]['account_name']   = $accountInfo ? $accountInfo['account_name'] : '';
		$accountList[$accountID]['product_title']  = $title;
		$accountList[$accountID]['product_brand']  = $brand;
		$accountList[$accountID]['manufacturer']   = $manufacturer;
		$accountList[$accountID]['mfr_partnumber'] = $partNumber;

		$uploadTypeList = $amazonProductAddModel->getPublishList();	//上传类型列表

		$productImageAdd = new AmazonProductImageAdd();
		
		//获取产品图片
	/*	$imageType = array('zt', 'ft');
		$config = ConfigFactory::getConfig('serverKeys');
		$skuImg = array();
		foreach($imageType as $type){
			$images = Product::model()->getImgList($sku,$type);
			if ($images){
				foreach($images as $k=>$img){
					if (strpos($k,'-') > 0){
						$skuImg[$type][$k] = $img;
					}
				}
			}
		}
	*/
        $skuImg = ProductImageAdd::getImageUrlFromRestfulBySku($sku, $typeAlisa = null, $type = 'normal', $width = 100, $height = 100, $platform = Platform::CODE_AMAZON);

        /**
         * 修复java api接口无主图返回问题
         */
        if (isset($skuImg['ft']) && !isset($skuImg['zt'])) {
            $skuImg['zt'] = $skuImg['ft'];
        }
		//子SKU列表
		foreach($varationList as $val){
			$subSku = $val['sku'];

			//子SKU选中的图片 
	   		// $selectedImages = array('zt'=>array(), 'ft'=>array());  			   	
	   		// $imgList = $productImageAdd->getImageBySku($val['sku'], $accountID, Platform::CODE_AMAZON);
	   		// if($imgList){
	   		// 	foreach ($imgList as $type=>$imgs){
	   		// 		foreach ($imgs as $img){
	   		// 			//$imgkey = current(explode(".", $img['image_name']));
	   		// 			$imgkey = substr($img['image_name'], 0, strrpos($img['image_name'], "."));
	   		// 			if($type == ProductImageAdd::IMAGE_ZT){
	   		// 				$selectedImages['zt'][] = $imgkey;
	   		// 			}else{
	   		// 				$selectedImages['ft'][] = $imgkey;
	   		// 			}
	   		// 		}
	   		// 	}
	   		// }

	   		// //转成一主图+附图的字符串
	   		// $imageStr = '';
	   		// if ($selectedImages){
	   		// 	if(isset($selectedImages['zt'][0])) $imageStr .= $selectedImages['zt'][0];	//亚马逊的主图只支持一张
	   		// 	if (isset($selectedImages['ft']) && count($selectedImages['ft']) > 0){
	   		// 		if ($imageStr){
	   		// 			$imageStr .= ','.implode(',',$selectedImages['ft']);
	   		// 		}else{
	   		// 			$imageStr .= implode(',',$selectedImages['ft']);
	   		// 		}
	   		// 	}
	   		// }

			//子SKU选中的图片字符串	   			 
			$imageStr = $amazonProductAddModel->getSkuSelectedImage($subSku, $accountID, 'update');  

			$productID = 0;
			$subSkuInfo = Product::model()->getProductBySku($subSku);	//获取product_id
			if($subSkuInfo) $productID = $subSkuInfo['id'];

			$subSKUList[$subSku]['product_id']                        = $productID;	//不能用多属性刊登表的子SKU自增ID充product_id
			$subSKUList[$subSku]['id']                                = $val['id'];
			$subSKUList[$subSku]['sku']                               = $subSku;
			$subSKUList[$subSku]['skuInfo']['sell_sku']               = $val['seller_sku'];
			$subSKUList[$subSku]['skuInfo']['upload_status']          = $val['status'];
			$subSKUList[$subSku]['skuInfo']['product_id_type']        = $val['amazon_identifier_type'];
			$subSKUList[$subSku]['skuInfo']['product_id']             = $val['amazon_standard_product_id'];
			$subSKUList[$subSku]['skuInfo']['price']                  = $val['sale_price'];
			$subSKUList[$subSku]['skuInfo']['market_price']           = $val['standard_price'];
			$subSKUList[$subSku]['skuInfo']['sale_start_time']        = $val['sale_start_time'];
			$subSKUList[$subSku]['skuInfo']['sale_end_time']          = $val['sale_end_time'];			
			$subSKUList[$subSku]['skuInfo']['inventory']              = $val['inventory'];
			$subSKUList[$subSku]['skuInfo']['shipping']               = $val['shipping'];
			$subSKUList[$subSku]['skuInfo']['price_profit']           = '';
			$subSKUList[$subSku]['skuInfo']['price_error']            = '';
			$subSKUList[$subSku]['skuInfo']['image']                  = $imageStr;

			$subSKUList[$subSku]['skuInfo']['sub_product_readonly']   = 0;
			$subSKUList[$subSku]['skuInfo']['sub_price_readonly']     = 0;
			$subSKUList[$subSku]['skuInfo']['sub_inventory_readonly'] = 0;
			$subSKUList[$subSku]['skuInfo']['sub_image_readonly']     = 0;

			//子SKU上传状态
			$status_desc = '';		
			$subHavePublishFlag = 0;				
			$statusReadonlyList = AmazonProductAddStatus::model()->getAllUploadIsReadonlyListByVariationID($val['id']);	//获取子SKU四个接口上传状态只读标识（上传中和上传成功状态）
			$statusSeccessList = AmazonProductAddStatus::model()->getAllUploadIsFinishListByVariationID($val['id']);	//获取子SKU四个接口上传状态已成功标识			
			foreach($uploadTypeList as $uploadType){
				$showDesc = '';
				$showImage = '';
				//已刊登类型对应字段，不能修改，只能只读
				if (in_array($uploadType,$statusReadonlyList)){
					switch ($uploadType){
						case AmazonProductAdd::UPLOAD_TYPE_PRODUCT:		
							$subSKUList[$subSku]['skuInfo']['sub_product_readonly'] = 1;
							break;
						case AmazonProductAdd::UPLOAD_TYPE_PRICE:
							$subSKUList[$subSku]['skuInfo']['sub_price_readonly'] = 1;
							break;
						case AmazonProductAdd::UPLOAD_TYPE_INVENTORY:
							$subSKUList[$subSku]['skuInfo']['sub_inventory_readonly'] = 1;
							break;	
						case AmazonProductAdd::UPLOAD_TYPE_IMAGE:
							$subSKUList[$subSku]['skuInfo']['sub_image_readonly'] = 1;
							break;																							
					}
				}

				$typeName = $amazonProductAddModel->getUploadTypeDesc($uploadType);
				if ($typeName) $typeName = mb_substr($typeName,2,4,'utf-8');
				if ($statusReadonlyList){
					if ($statusSeccessList && in_array($uploadType,$statusSeccessList)){
						$subHavePublishFlag = 1;
						$havePublishFlag = 1;
						$showDesc = '已刊登成功';
						$showImage = 'btnHook';
					}else{
						if ($statusReadonlyList && in_array($uploadType,$statusReadonlyList)){
							$havePublishFlag = 1;
							$showDesc = '刊登中';
							$showImage = 'btnInfo';
						}else{						
							$showDesc = '未刊登成功';
							$showImage = 'btnFork';
						}
					}
				}else{
					$showDesc = '未刊登成功';
					$showImage = 'btnFork';
				}
				$status_desc .= "<a title='".$typeName.$showDesc."' class='".$showImage."' href='#'></a>";
			}
			//刷新失败重新上传
			$status_desc .= "<a title='刷新失败接口，重新上传' variation_id = '" .$val['id']. "' class='btnRefresh reuploadstatus' style='margin-top:5px;' href='javascript:void(0)' ></a>";


			$subSKUList[$subSku]['skuInfo']['sub_upload_status'] = $status_desc;
			$subSKUList[$subSku]['skuInfo']['sub_no_delete'] = $subHavePublishFlag;	//判断是否有上传成功的，有则不能删除
		}

		$publishParams = array(
			'sku'                      => $sku,
			'publish_site'             => $countryCode,
			'publish_type'             => array(
				'id'   => $publishType,
				'text' => AmazonProductAdd::getProductPublishTypeList($publishType),
			),
			'publish_mode'             => array(
				'id'   => $publishMode,
				'text' => AmazonProductAdd::getProductPublishModelList($publishMode),
			),
			'amazon_product_type'      => AmazonSite::getSiteProductType(),		//亚马逊平台产品类型
			'publish_product_readonly' => ($havePublishFlag == 1) ? 1 : 0,		//1-基本产品不能修改
		);

		//查找产品历史分类
		$historyCategoryList = AmazonProductAdd::model()->getSkuHistoryCategory($sku);

		//当前分类
		$defaultHistoryCategory = array();
		if ($historyCategoryList) {
			// $defaultHistoryCategory = array_slice($historyCategoryList, -1, 1, true);
			$catePath = AmazonCategory::model()->getBreadcrumbCategory($categoryPath);
			if($catePath) $defaultKey = array_search($catePath,$historyCategoryList);
			if ($defaultKey){
				$defaultHistoryCategory[$defaultKey] = $historyCategoryList[$defaultKey];
			}
		}

		//查找产品顶级分类列表
		$categoryList = array();
		$topCategories = AmazonCategory::model()->getSubCategoryList(0);	//获取父分类ID=0的根分类列表
		if ($topCategories) {
			foreach ($topCategories as $category) {
				$categoryList[$category['id']] = $category['en_name'];		//不用category_id，不唯一，所以用自增ID
			}
		}

		//查找产品信息
		$skuInfo = Product::model()->getProductInfoBySku($sku);

		//产品简述（最多五条）
		if ($bulletPointAll){
			$bulletPoint = '';
			$bulletArr = explode('@@@@@',$bulletPointAll);
			if ($bulletArr){
				foreach($bulletArr as $k => $val){
					if (!empty($val)){
						if ($k == 0){
							$accountList[$accountID]['bullet_point'] = $val;
						}else{
							$accountList[$accountID]['bullet_point'.$k] = $val;
						}									
					}
				}
			}
		}else{
			if (!empty($skuInfo['description']['english'])){
				$str_temp = trim(strip_tags($skuInfo['description']['english']));
				$accountList[$accountID]['bullet_point'] = substr($str_temp,0,200);	//截取描述200字符内容
			}
		}	

		//搜索关键字（最多五条）
		if ($searchTermsAll){
			$searchTermsArr = explode('@@@@@',$searchTermsAll);
			if ($searchTermsArr){
				foreach($searchTermsArr as $k => $val){
					if (!empty($val)){
						if ($k == 0){
							$accountList[$accountID]['search_terms'] = $val;
						}else{
							$accountList[$accountID]['search_terms'.$k] = $val;
						}									
					}
				}
			}
		}

		//产品图片
   		// $newImgList = array();
   		// $productImageAdd = new ProductImageAdd();
   		// $selectedImages = array('zt'=>array(), 'ft'=>array());
   		// $imgList = $productImageAdd->getImageBySku($sku, $accountID, Platform::CODE_AMAZON);
   		// if($imgList){
   		// 	foreach ($imgList as $type=>$imgs){
   		// 		foreach ($imgs as $img){
   		// 			//$imgkey = current(explode(".", $img['image_name']));
   		// 			$imgkey = substr($img['image_name'], 0, strrpos($img['image_name'], "."));
   		// 			if($type == ProductImageAdd::IMAGE_ZT){
   		// 				$selectedImages['zt'][] = $imgkey;
   		// 				if(!empty($skuImg['zt'][$imgkey])){
   		// 					$newImgList['zt'][$imgkey] = $skuImg['zt'][$imgkey];
   		// 					unset($skuImg['zt'][$imgkey]);
   		// 				}
   		// 			}else{
   		// 				$selectedImages['ft'][] = $imgkey;
   		// 				if(!empty($skuImg['ft'][$imgkey])){
   		// 					$newImgList['ft'][$imgkey] = $skuImg['ft'][$imgkey];
   		// 					unset($skuImg['ft'][$imgkey]);
   		// 				}
   		// 			}
   		// 		}
   		// 	}
   		// }
   		// $newImgList = array_merge_recursive($newImgList, $skuImg);


		$accountList[$accountID]['description'] = $description;

		//精简模式刊登
		$htmlOptions = array();
		if ($publishMode == AmazonProductAdd::PRODUCT_PUBLISH_MODE_EASY) {			
			$this->render('_formeasy', array(
				'action'                  => 'update',
				'cur_account_id'          => $accountID,
				'addID'                   => $addInfo['id'],
				'imgomsURL'               => '',// $config['oms']['host'],
				'publishParams'           => $publishParams,
				'historyCategoryList'     => $historyCategoryList,
				'selectedProductTypeText' => $productTypeText,
				'defaultHistoryCategory'  => $defaultHistoryCategory,
				'chooseCategoryList'      => $categoryList,
				'skuImg'                  => $skuImg,
				// 'selectedImages'          => $selectedImages,
				'accountList'             => $accountList,
				'skuInfo'                 => $skuInfo,
				'listingSubSKU'           => $subSKUList
			));
		}
	}	
	
	/**
	 * @desc 获取可用账号
	 */
	public function actionGetableaccount(){
		$sku          = Yii::app()->request->getParam('publish_sku');
		$publish_site  = Yii::app()->request->getParam('publish_site');
		$accounts     = AmazonProductAdd::model()->getAbleAccountListBySku($sku,$publish_site);
		$ableAccounts = array();
		if ($accounts){
			foreach($accounts as $id=>$account){
				$ableAccounts[] = array(
						'id'         => $id,
						'short_name' => $account['account_name'],
						'flag'       =>	$account['is_upload']
				);
			}
		}
		echo json_encode($ableAccounts);exit;
	}
	
	/**
	 * @desc 保存刊登数据
	 */
	public function actionSavedata() {
    	error_reporting(E_ALL);
    	ini_set('display_errors', true);

		$errors                    = '';		//验证数据错误信息
		$keywordsAll               = '';
		$searchTermsAll            = '';
		$hadSucessfulUploadProduct = false;	//子SKU是否有基本产品上传成功的
		//刊登基础数据
		$sku         = trim(Yii::app()->request->getParam('publish_sku'));	
		$publishSite = Yii::app()->request->getParam('publish_site');	//刊登站点
		$publishType = Yii::app()->request->getParam('publish_type');	//刊登类型
		$publishMode = Yii::app()->request->getParam('publish_mode');	//刊登模式
		$categoryID  = Yii::app()->request->getParam('category_id');	//产品刊登分类
		$action      = Yii::app()->request->getParam('action');			//添加还是更新
		$skuinfo     = Yii::app()->request->getParam('skuinfo');		//多属性SKU信息（单品或多属性SKU的加密SKU，产品编码，价格，库存等）		

		$subjectArr                 = Yii::app()->request->getParam('subject');					//产品标题
		$brandArr                   = Yii::app()->request->getParam('brand');					//品牌
		$manufacturerArr            = Yii::app()->request->getParam('manufacturer');			//制造商
		$mfrPartNumberArr           = Yii::app()->request->getParam('mfr_partnumber');			//制造商编码

		$keywordsArr                = Yii::app()->request->getParam('keywords');				//产品简述
		$more_keywords1Arr          = Yii::app()->request->getParam('more_keywords1');			//产品简述1
		$more_keywords2Arr          = Yii::app()->request->getParam('more_keywords2');			//产品简述2
		$more_keywords3Arr          = Yii::app()->request->getParam('more_keywords3');			//产品简述3		
		$more_keywords4Arr          = Yii::app()->request->getParam('more_keywords4');			//产品简述4

		$searchTermsArr             = Yii::app()->request->getParam('search_terms');			//搜索关键字
		$searchTerms1Arr            = Yii::app()->request->getParam('search_terms1');			//搜索关键字1
		$searchTerms2Arr            = Yii::app()->request->getParam('search_terms2');			//搜索关键字2
		$searchTerms3Arr            = Yii::app()->request->getParam('search_terms3');			//搜索关键字3
		$searchTerms4Arr            = Yii::app()->request->getParam('search_terms4');			//搜索关键字4

		$detailArr                  = Yii::app()->request->getParam('detail');		
		$skuImages                  = Yii::app()->request->getParam('skuImage');	//产品图片数据
		
		$xsd_product_type_select_id = Yii::app()->request->getParam('xsd_product_type_select');		//上传分类类型（顶级分类）
		$xsd_sub_select_id          = Yii::app()->request->getParam('xsd_sub_select');				//子分类类型ID
		$product_type_text          = Yii::app()->request->getParam('xsd_product_type_select_text');//子分类类型名称

		$product_type_id = 0;
		if ($xsd_sub_select_id != ''){
			$product_type_id = (int)$xsd_sub_select_id;
		}else{
			$product_type_id = (int)$xsd_product_type_select_id;
		}

		$amazonProductAddModel = new AmazonProductAdd();
		$productAddVariationModel = new AmazonProductAddVariation();
		$productAddStatusModel = new AmazonProductAddStatus();

		if ($action == 'update'){
			$addID = Yii::app()->request->getParam('id');
			$productAddInfo = $amazonProductAddModel->getMainAndVariationInfoById($addID); //主表信息和子SKU相关信息
			if (!$productAddInfo) {
				echo $this->failureJson(array(
						'message' => Yii::t('amazon_product', 'Not found the Publish Product'),
				));
				Yii::app()->end();
			}
		}else{
			  $addID = null;
		}	

		//刊登中和刊登成功的产品不能再修改  ？？？？？
		//$productAddInfo['status'] = AmazonProductAdd::UPLOAD_STATUS_SUCCESS
		// if ($productAddInfo && in_array($productAddInfo['status'], array(AmazonProductAdd::UPLOAD_STATUS_SUCCESS, AmazonProductAdd::UPLOAD_STATUS_RUNNING))) {
		// 	echo $this->failureJson(array(
		// 		'message' => Yii::t('amazon_product', 'This Status Can\'t Modify'),
		// 	));
		// 	Yii::app()->end();
		// }			

		$encryptSku = new encryptSku();		
		$config = ConfigFactory::getConfig('serverKeys');		

		//判断产品是否存在
		$mainSkuInfo = Product::model()->getProductInfoBySku($sku);	//主sku信息
		if (empty($mainSkuInfo)) {
			echo $this->failureJson(array(
					'message' => Yii::t('amazon_product', 'Sku Not Exists', array('sku' => $sku)),
			));
			Yii::app()->end();
		}

		//重新定义刊登类型：单品/多属性（界面支持单品变为多属性）
		if (count($skuinfo) > 1){
			$publishType = AmazonProductAdd::PRODUCT_PUBLISH_TYPE_VARIATION;
		}else{
			if(count($skuinfo) == 1){
				$cur_key = array_keys($skuinfo);
				if ($cur_key[0] == $mainSkuInfo['sku']){
					$publishType = AmazonProductAdd::PRODUCT_PUBLISH_TYPE_SINGLE;
				}else{
					$publishType = AmazonProductAdd::PRODUCT_PUBLISH_TYPE_VARIATION;
				}
			}else{
				echo $this->failureJson(array(
						'message' => Yii::t('amazon_product', 'Publish Sub Info not Exists'),
				));
				Yii::app()->end();	
			}
		}		

		//更新时，如果子SKU有个基本产品上传成功的，则基本产品数据都不能更新，图片也如此
		if($action == 'update'){			
			$hadSucessfulUploadProduct = $productAddStatusModel->getHadUploadSucessfulByConditions($addID,AmazonProductAdd::UPLOAD_TYPE_PRODUCT);
		}

		//检查子sku信息是否填写完整
		foreach ($skuinfo as $k => $subSku){			
			$subSku['sku']              = trim($subSku['sku']);
			$subSku['sell_sku']         = trim($subSku['sell_sku']);
			$subSku['product_id']       = trim($subSku['product_id']);
			$subSku['product_id_type']  = isset($subSku['product_id_type']) ? $subSku['product_id_type'] : 4;	//更新操作select只读时获取不到值			
			$subSku['market_price']     = floatval($subSku['market_price']);
			$subSku['shipping']         = floatval($subSku['shipping']);
			$subSku['inventory']        = intval($subSku['inventory']);
			
			$subSku['price']            = floatval($subSku['price']);	//促销价格
			$subSku['sales_start_date'] = trim($subSku['sales_start_date']);
			$subSku['sales_end_date']   = trim($subSku['sales_end_date']);

			if(empty($subSku['sell_sku']) || !is_numeric($subSku['inventory']) || empty($subSku['product_id']) || empty($subSku['market_price'])){
				echo $this->failureJson(array(
						'message' => Yii::t('amazon_product', 'Please check sku info', array('sku' => $subSku)),
				));
				Yii::app()->end();			
			}

			if($subSku['price'] > 0 && (empty($subSku['sales_start_date']) || empty($subSku['sales_end_date']))){
				echo $this->failureJson(array(
						'message' => Yii::t('amazon_product', 'Sku Sale price had, the promotion date must filled', array('Sku' => $subSku)),
				));
				Yii::app()->end();			
			}			

			//拼接子SKU图片
			$imageList = array();
			$imageArr = array();
			$imageStr = trim($subSku['image']);			
			if(empty($imageStr)){
				echo $this->failureJson(array(
						'message' => Yii::t('amazon_product', 'Sub SKU:sku Product Image Empty', array('sku' => $subSku['sku'])),
				));
				Yii::app()->end();			
			}else{
				//$imageArr = explode(',',$imageStr);
                //ketu.lai
				$imageArr = json_decode($imageStr);
				if(count($imageArr) < 2){
					echo $this->failureJson(array(
							'message' => Yii::t('amazon_product', 'Sub SKU:sku Product Image Less Than Two', array('sku' => $subSku['sku'])),
					));
					Yii::app()->end();	
				}else{

					$imageList[ProductImages::IMAGE_ZT] = array(array_shift($imageArr));	//主图，亚马逊主图只有一张
					$imageList[ProductImages::IMAGE_FT] = $imageArr;				//附图
				}
				$subSku['image'] = $imageList;
			}

			$skuinfo[$k] = $subSku;
		}
		if (!$skuinfo){
			echo $this->failureJson(array(
					'message' => Yii::t('amazon_product', 'Publish Sub Info not Exists'),
			));
			Yii::app()->end();				
		}

		// if (!empty($variationPriceArr)) {
		// 	foreach ($variationPriceArr as $price) {
		// 		if ($price <= 0.00)
		// 			$errors .= '<li>' . Yii::t('amazon_product', 'Variation SKU Price Empty') . '</li>';
		// 	}
		// }

 	// 	//检查是否有ProductID
 	// 	if ($product_idArr){
		// 	foreach ($product_idArr as $accountID => $product_id) {
		// 		if (empty($product_id))
		// 			$errors .= '<li>' . Yii::t('amazon_product', 'Product ID Empty', array('accountID' => $accountID)) . '</li>';
		// 		if (strlen($product_id) > 128)
		// 			$errors .= '<li>' . Yii::t('amazon_product', 'Product ID Could Too Long', array('accountID' => $accountID)) . '</li>';
		// 	}
		// }

		// //检查价格
		// if ($publishType == AmazonProductAdd::PRODUCT_PUBLISH_TYPE_SINGLE) {
		// 	if (empty($productPriceArr)){
		// 		$errors .= '<li>' . Yii::t('amazon_product', 'Product Price Empty', array('accountID' => $accountID)) . '</li>';
		// 	}else{
		// 		foreach ($productPriceArr as $accountID => $price){
		// 			if (empty($price)){
		// 				$errors .= '<li>' . Yii::t('amazon_product', 'Product Price Empty', array('accountID' => $accountID)) . '</li>';
		// 				}
		// 		}
		// 	}
		// }

		// //促销价格和促销日期必须同时存在
		// if ($publishType == AmazonProductAdd::PRODUCT_PUBLISH_TYPE_SINGLE) {
		// 	if ($productSalesPriceArr){
		// 		foreach ($productSalesPriceArr as $accountID => $salesPrice){
		// 			if (!empty($salesPrice) && (empty($salesStartDateArr[$accountID]) || empty($salesEndDateArr[$accountID]))){
		// 				$errors .= '<li>' . Yii::t('amazon_product', 'Product Sales Price And Date Empty', array('accountID' => $accountID)) . '</li>';
		// 			}
		// 			//促销价不能大于标准价
		// 			if (floatval($salesPrice) > floatval($productPriceArr[$accountID])){
		// 				throw new Exception(Yii::t('amazon_product', 'Product Sales Can\'t more harm than Standard Price'));
		// 			}						
		// 		}

		// 	}
		// }				

		$category_id   = '';
		$category_path = '';
		//检查分类是否选择
		if (empty($categoryID)){
			$errors .= '<li>' . Yii::t('amazon_product', 'Product Category Empty') . '</li>';
		}else{
			$categoryInfo = AmazonCategory::model()->getCategoryInfoByID($categoryID);
			if ($categoryInfo){
				$category_id   = $categoryInfo['category_id'];
				$category_path = $categoryInfo['category_path_name'];
			}
		}
 		//检查是否有图片
 		// if(empty($skuImages)) $errors .= '<li>' . Yii::t('amazon_product', 'Product Image Empty') . '</li>';
 		// $imgtotal1 = isset($skuImages[ProductImages::IMAGE_ZT])?sizeof($skuImages[ProductImages::IMAGE_ZT]):0;
 		// $imgtotal2 = isset($skuImages[3])?sizeof($skuImages[3]):0;
 		// if (($imgtotal1+$imgtotal2) > AmazonProductAdd::PRODUCT_MAIN_IMAGE_MAX_NUMBER) {
 		// 	$errors .= '<li>' . Yii::t('amazon_product', 'Product Main Image Must Less Then', array('num' => AmazonProductAdd::PRODUCT_MAIN_IMAGE_MAX_NUMBER)) . '</li>';
 		// }

 		//检查是否有标题
 		if ($subjectArr){
			foreach ($subjectArr as $accountID => $subject) {
				if (empty($subject))
					$errors .= '<li>' . Yii::t('amazon_product', 'Product Title Empty', array('accountID' => $accountID)) . '</li>';
				if (strlen($subject) > 128)
					$errors .= '<li>' . Yii::t('amazon_product', 'Product Title Could Too Long', array('accountID' => $accountID)) . '</li>';
			}
		}
 		//检查是否有品牌
 		if ($brandArr){
			foreach ($brandArr as $accountID => $brand) {
				if (empty($brand))
					$errors .= '<li>' . Yii::t('amazon_product', 'Product Brand Empty', array('accountID' => $accountID)) . '</li>';
				if (strlen($brand) > 128)
					$errors .= '<li>' . Yii::t('amazon_product', 'Product Brand Could Too Long', array('accountID' => $accountID)) . '</li>';
			}
		}
 		//检查是否有制造商
 		if ($manufacturerArr){
			foreach ($manufacturerArr as $accountID => $manufacturer) {
				if (empty($manufacturer))
					$errors .= '<li>' . Yii::t('amazon_product', 'Product Manufacturer Empty', array('accountID' => $accountID)) . '</li>';
				if (strlen($manufacturer) > 128)
					$errors .= '<li>' . Yii::t('amazon_product', 'Product Manufacturer Could Too Long', array('accountID' => $accountID)) . '</li>';
			}
		}

 		//检查是否有产品简述
 		if ($keywordsArr){
			foreach ($keywordsArr as $accountID => $keywords) {
				$keywords = trim($keywords);
				if (empty($keywords))
					$errors .= '<li>' . Yii::t('amazon_product', 'Product Keywords Empty', array('accountID' => $accountID)) . '</li>';
				if (strlen($keywords) > 500)
					$errors .= '<li>' . Yii::t('amazon_product', 'Product Keywords Could Too Long', array('accountID' => $accountID)) . '</li>';
			}				
		}		

 		//检查分类类型是否为空
 		if (empty($product_type_text) || $product_type_id == 0){
			$errors .= '<li>' . Yii::t('amazon_product', 'Product Type Empty') . '</li>';
		}

		if (!empty($errors)) {
			echo $this->failureJson(array(
				'message' => $errors,
			));
			Yii::app()->end();
		}
		
		$skuAttributeImages = array();	//多属性产品图片
		$attributeInfos = array();
		//保存账号的基本产品
		foreach ($subjectArr as $accountID => $subject) {
			$res = array();
			$variationInfo = array();
			$variationAddData = array();
			$accountIDs[] = $accountID;
			$dbTransaction = AmazonProductAdd::model()->getDbConnection()->getCurrentTransaction();
			if (is_null($dbTransaction))
				$dbTransaction = AmazonProductAdd::model()->getDbConnection()->beginTransaction();
			//保存刊登主表信息
			try {
				$description = '';
				$description = $detailArr[$accountID];
				if (!empty($description)){								
					// $description = htmlspecialchars($description);	//入库时不转义，使用实体入库
					//描述字符最大不能超过2000字符
					if(strlen($description) > 2000) $description = mb_strcut($description,0,2000,'utf-8');			
				}

				//合并简述
				if ($keywordsArr[$accountID]) $keywordsAll = strip_tags(trim($keywordsArr[$accountID]));
				if ($more_keywords1Arr[$accountID]) $keywordsAll .= '@@@@@'.strip_tags(trim($more_keywords1Arr[$accountID]));
				if ($more_keywords2Arr[$accountID]) $keywordsAll .= '@@@@@'.strip_tags(trim($more_keywords2Arr[$accountID]));
				if ($more_keywords3Arr[$accountID]) $keywordsAll .= '@@@@@'.strip_tags(trim($more_keywords3Arr[$accountID]));
				if ($more_keywords4Arr[$accountID]) $keywordsAll .= '@@@@@'.strip_tags(trim($more_keywords4Arr[$accountID]));

				//搜索关键字
				if ($searchTermsArr[$accountID]) $searchTermsAll = strip_tags(trim($searchTermsArr[$accountID]));
				if ($searchTerms1Arr[$accountID]) $searchTermsAll .= '@@@@@'.strip_tags(trim($searchTerms1Arr[$accountID]));
				if ($searchTerms2Arr[$accountID]) $searchTermsAll .= '@@@@@'.strip_tags(trim($searchTerms2Arr[$accountID]));
				if ($searchTerms3Arr[$accountID]) $searchTermsAll .= '@@@@@'.strip_tags(trim($searchTerms3Arr[$accountID]));
				if ($searchTerms4Arr[$accountID]) $searchTermsAll .= '@@@@@'.strip_tags(trim($searchTerms4Arr[$accountID]));

				$sell_main_sku = '';
				$sell_main_sku = $encryptSku->getAmazonEncryptSku($mainSkuInfo['sku']);	//加密主SKU

				$addData = array(
					'account_id'        => $accountID,
					'sku'               => $mainSkuInfo['sku'],	
					'seller_sku'        => $sell_main_sku,
					'country_code'      => $publishSite,	
					'publish_type'      => $publishType,
					'publish_mode'      => $publishMode,

					'title'             => $subject,					
					'category_id'       => $category_id,
					'category_path'     => $category_path,
					'create_user_id'    => Yii::app()->user->id,
					'create_time'       => date('Y-m-d H:i:s'),
					'update_user_id'    => Yii::app()->user->id,
					'update_time'       => date('Y-m-d H:i:s'),
										
					'brand'             => $brandArr[$accountID],
					'manufacturer'      => $manufacturerArr[$accountID],
					'part_number'       => (isset($mfrPartNumberArr) && isset($mfrPartNumberArr[$accountID]) && !empty($mfrPartNumberArr[$accountID])) ? $mfrPartNumberArr[$accountID] : $sell_sku,	//制造商编码如为空则用在线SKU填充
					'bullet_point'      => $keywordsAll,
					'search_terms'      => $searchTermsAll,
					'product_type_id'   => $product_type_id,
					'product_type_text' => $product_type_text,
					'description'       => $description,
					'currency'          => AmazonProductAdd::PRODUCT_PUBLISH_CURRENCY
				);

				if($action == 'update'){
					unset($addData['account_id']);	
					unset($addData['sku']);
					unset($addData['seller_sku']);
					unset($addData['country_code']);
					unset($addData['publish_mode']);
					unset($addData['create_user_id']);
					unset($addData['create_time']);
					unset($addData['currency']);

					//如果子SKU有基本产品上传成功的，则基本产品数据不能更新	
					if ($hadSucessfulUploadProduct){
						unset($addData['title']);
						unset($addData['category_id']);
						unset($addData['category_path']);
						unset($addData['brand']);
						unset($addData['manufacturer']);
						unset($addData['part_number']);
						unset($addData['bullet_point']);
						unset($addData['search_terms']);
						unset($addData['product_type_id']);
						unset($addData['product_type_text']);
						unset($addData['description']);						
					}					

					//不能更新总上传状态为已确认刊登成功的产品
					// if ($productAddInfo['status'] != AmazonProductAdd::UPLOAD_STATUS_SUCCESS){
					// 	//同步更新上传状态表的基本产品为待上传					
					// 	$variationInfo = $productAddInfo['variation']; //单品多属性数据 ？？？？？？多属性记录更新
					// 	if ($variationInfo){
					// 		$conditions = "variation_id = " .$variationInfo['id']. " AND upload_type = ".AmazonProductAdd::UPLOAD_TYPE_PRODUCT;
					// 		$uploadStatusRet = AmazonProductAddStatus::model()->getUploadStatusByCondition($conditions);
					// 		if ($uploadStatusRet && $uploadStatusRet['upload_status'] != AmazonProductAdd::UPLOAD_STATUS_SUCCESS) {
					// 			AmazonProductAddStatus::model()->updateProductAddStatus($conditions,array('upload_status' => AmazonProductAdd::UPLOAD_STATUS_DEFAULT));
					// 		}
					// 	}

					// 	//更新总上传状态为待上传
					// 	$addData['status'] = AmazonProductAdd::UPLOAD_STATUS_DEFAULT;
					// 	$addData['upload_start_time'] = '0000-00-00 00:00:00';	//清零
					// 	$addData['upload_finish_time'] = '0000-00-00 00:00:00';


					// 	$res = $amazonProductAddModel->getDbConnection()->createCommand()->update($amazonProductAddModel->tableName(), $addData, "id=:id", array(":id"=>$addID));						
					// }else{
					// 	// throw new Exception(Yii::t('amazon_product', 'Confirm Upload Seccess, Never Update Product Info'));
					// 	$addData = array();	//清空
					// 	$addData['publish_type']   = $publishType;
					// 	$addData['update_user_id'] = Yii::app()->user->id;
					// 	$addData['update_time']    = date('Y-m-d H:i:s');
					// }
					$res = $amazonProductAddModel->getDbConnection()->createCommand()->update($amazonProductAddModel->tableName(), $addData, "id=:id", array(":id"=>$addID));						
				}elseif ($action == 'create'){
					$res = $amazonProductAddModel->getDbConnection()->createCommand()->insert($amazonProductAddModel->tableName(), $addData);
				}
				if(!$res){
					throw new Exception(Yii::t('amazon_product', 'Save Product Info Failure'));
				}
				
				//主SKU自增ID
				if ($action == 'create'){
					$addID = $amazonProductAddModel->getDbConnection()->getLastInsertID();
				}
				if(empty($addID)){
					throw new Exception(Yii::t('amazon_product', 'Save Product Info Failure'));
				}
	
				//数据库已有子SKU数据
				$varList = $productAddVariationModel->getFormatVariationListByAddID($addID);

				if (count($skuinfo) < count($varList) || ($action == 'create' && $varList)){
					throw new Exception(Yii::t('amazon_product', 'Save Failed Sub SKU Due To Abnormal Data'));
				}else{
				}

				$site_id = AmazonSite::getSiteIdByName($publishSite);	//站点国家代码换成ID值			

				//提交的子SKU信息（单品、多属性都一样）
				foreach($skuinfo as $addItem){
					$subUploadSucessfulProduct   = false;
					$subUploadSucessfulPrice     = false;
					$subUploadSucessfulInventory = false;
					$subUploadSucessfulImage     = false;
					$skuImages                   = $addItem['image'];	//表单提交的子SKU图片

					$variationAddData = array(
						'add_id'                     => $addID,
						'sku'                        => $addItem['sku'],
						'seller_sku'                 => $addItem['sell_sku'],			
						'amazon_identifier_type'     => $addItem['product_id_type'],	
						'amazon_standard_product_id' => $addItem['product_id'],	
						'standard_price'             => floatval($addItem['market_price']),	
						'sale_price'                 => floatval($addItem['price']),	
						'shipping'                   =>	floatval($addItem['shipping']),
						'sale_start_time'            => $addItem['sales_start_date'],	
						'sale_end_time'              => $addItem['sales_end_date'],		
						'inventory'                  =>	$addItem['inventory'],
						'update_user_id'             => Yii::app()->user->id,		
						'update_time'                => date('Y-m-d H:i:s'),						
					);

					//修改操作								
					if ($action == 'update' && isset($varList[$addItem['sku']])){
						unset($variationAddData['add_id']);
						unset($variationAddData['sku']);

						$currentSubSkuInfo = $varList[$addItem['sku']];	//子SKU信息

						//子SKU基本产品，价格，库存、图片上传是否成功，成功的相应字段不能修改更新
						$subUploadSucessfulProduct   = $productAddStatusModel->getHadUploadSucessfulByConditions($addID,AmazonProductAdd::UPLOAD_TYPE_PRODUCT,$currentSubSkuInfo['id']);
						$subUploadSucessfulPrice     = $productAddStatusModel->getHadUploadSucessfulByConditions($addID,AmazonProductAdd::UPLOAD_TYPE_PRICE,$currentSubSkuInfo['id']);
						$subUploadSucessfulInventory = $productAddStatusModel->getHadUploadSucessfulByConditions($addID,AmazonProductAdd::UPLOAD_TYPE_INVENTORY,$currentSubSkuInfo['id']);
						$subUploadSucessfulImage     = $productAddStatusModel->getHadUploadSucessfulByConditions($addID,AmazonProductAdd::UPLOAD_TYPE_IMAGE,$currentSubSkuInfo['id']);

						if ($subUploadSucessfulProduct){
							unset($variationAddData['seller_sku']);
							unset($variationAddData['amazon_identifier_type']);
							unset($variationAddData['amazon_standard_product_id']);
						}
						if ($subUploadSucessfulPrice){
							unset($variationAddData['standard_price']);
							unset($variationAddData['sale_price']);
							unset($variationAddData['sale_start_time']);
							unset($variationAddData['sale_end_time']);
							unset($variationAddData['shipping']);
						}
						if ($subUploadSucessfulInventory){
							unset($variationAddData['inventory']);
						}				

						//如果子SKU上传状态为失败，则改为待上传，同时更新子SKU对应上传状态失败的改为待上传
						if ($currentSubSkuInfo['status'] == AmazonProductAdd::UPLOAD_STATUS_FAILURE) {
							$variationAddData['upload_finish_time'] = '0000-00-00 00:00:00';							
							$variationAddData['status'] = AmazonProductAdd::UPLOAD_STATUS_DEFAULT;

							//更新子SKU对应上传状态
							$upadtestatus_conditions = "variation_id = " .$currentSubSkuInfo['id']. " AND upload_status = ".AmazonProductAdd::UPLOAD_STATUS_FAILURE;
							AmazonProductAddStatus::model()->updateProductAddStatus($upadtestatus_conditions,array('upload_status' => AmazonProductAdd::UPLOAD_STATUS_DEFAULT));			

						}												
						//修改
						$variation_res = $productAddVariationModel->updateProductAddVariation("id = ".$currentSubSkuInfo['id'], $variationAddData);		
					}else{
						//新增
						$variation_res = $productAddVariationModel->getDbConnection()->createCommand()->insert($productAddVariationModel->tableName(), $variationAddData);	
					}

					//保存子SKU图片
					if (!$subUploadSucessfulImage && $skuImages){		
						//删除common图片库对应的以前的图片				
                        AmazonProductImageAdd::model()->deleteSkuImages($addItem['sku'], $accountID, Platform::CODE_AMAZON, $site_id);
		  				foreach ($skuImages as $type => $images) {
		  					foreach ($images as $image) {
								$imagename = basename($image);	//basename($image, '.jpg')
		  						//查询图片是否已经添加


		  						if (AmazonProductImageAdd::model()->checkImageExists($imagename, $type, Platform::CODE_AMAZON, $accountID)) continue;

                                $localPath = ProductImageAdd::getImageLocalPathBySkuAndName($addItem['sku'], $type, basename($imagename, '.jpg'));

                                $imageAddData = array(
									'image_name'    => $imagename,
									'sku'           => $addItem['sku'],
									'type'          => $type,
									'local_path'    => $localPath,
									'platform_code' => Platform::CODE_AMAZON,
									'account_id'    => $accountID,
									'upload_status' => AmazonProductImageAdd::UPLOAD_STATUS_DEFAULT,
									'create_user_id'=> Yii::app()->user->id,
									'create_time'   => date('Y-m-d H:i:s'),		
									'site_id'		=> $site_id,
								);
			                    $imageModel = new AmazonProductImageAdd();
			                    $imageModel->setAttributes($imageAddData,false);
			                    $imageModel->setIsNewRecord(true);
			                    $flag = $imageModel->save();
			                    if(!$flag) throw new Exception('Aliexpress_product', 'Save Product Image Failure');
		  					}
						}
					}
				}

				$dbTransaction->commit();
			} catch (Exception $e) {
				$dbTransaction->rollback();
				echo $this->failureJson(array(
					'message' => $e->getMessage(),
				));
				Yii::app()->end();
			}
		}

		echo $this->successJson(array(
				'message' => Yii::t('amazon_product', 'Save Product Publish Data Successful'),
				'sku' => $sku,
		));			
	}


	/**
	 * @desc 通过刊登ID获取单个产品刊登结果
	 * @link /amazon/amazonproductadd/setfeedresult/add_id/4/upload_type/2
	 */
	public function actionSetFeedResult() {
		set_time_limit(3600);
		$add_id = Yii::app()->request->getParam('add_id');
		$upload_type = Yii::app()->request->getParam('upload_type');

		$feedID              = '';
		$errMessageList      = '';
		$showErrMessage      = '';		
		$variationID         = 0;
		$feedList            = array();
		$upload_type_seccess = '';
		try{
			if(empty($add_id)){
				throw new Exception(Yii::t('amazon_product', 'Param Error'));
			}
			$amazonProductAddModel = new AmazonProductAdd();
			$productAddInfo = $amazonProductAddModel->getMainAndVariationInfoById($add_id);
			if ($productAddInfo){
				$accountID = $productAddInfo['account_id'];
				//单品刊登
				if($productAddInfo['publish_type'] == AmazonProductAdd::PRODUCT_PUBLISH_TYPE_SINGLE) $variationID = $productAddInfo['variation']['id'];
				$uploadStatus = $productAddInfo['status']; 
			}else{
				throw new Exception(Yii::t('amazon_product', 'Not found the Publish Product'));
			}

			if($variationID == 0){
				throw new Exception(Yii::t('amazon_product', 'Publish Sub Info not Exists'));
			}			

			$amazonProductAddStatusModel = new AmazonProductAddStatus();
			if (!empty($upload_type)){				
				$statusInfo = $amazonProductAddStatusModel->getStatusByAddIDAndType($add_id,(int)$upload_type);
				if($statusInfo) $feedList[0] = $statusInfo;
			}else{
				$feedList = $amazonProductAddStatusModel->getAllStatusByAddID($add_id);	
			}
			
			if ($feedList){
				foreach ($feedList as $item){
					$errflag     	= 0;
					$showErrMessage = '';
					$feedID         = $item['feed_id'];
					$upload_type    = $item['upload_type'];
					$upload_status  = $item['upload_status'];

					if(empty($feedID)){
						$errflag = 1;
						$showErrMessage .= Yii::t('amazon_product', 'Not found the Upload ID').'<br />';
					}else{
						if($upload_status != AmazonProductAdd::UPLOAD_STATUS_RUNNING){
							$errflag = 1;
							$showErrMessage .= Yii::t('amazon_product', 'The Feed Not UPLOAD STATUS RUNNING').'<br />';						
						}						
					}									

					if ($errflag == 0){
						//接口获取刊登结果
						$response = AmazonList::model()->GetCommonFeedSubmissionResult($accountID,$feedID);					
						if(!$response){
							$showErrMessage .= '上传接口返回：'.Yii::t('amazon_product', 'The API Result is Empty, Wait For Uploading ...').'<br />';							
						}else{
							if(isset($response->Message->ProcessingReport->ProcessingSummary)){
								$processingReport = $response->Message->ProcessingReport;	//处理明细
								$statusCode       = $processingReport->StatusCode;			//平台处理状态
								$errorsResult     = $processingReport->Result;				//错误集
								//平台对上传接口已处理完成的标识
								if($statusCode == 'Complete'){
									$processingSummary = $processingReport->ProcessingSummary;
									//标识请求报告已获取
									$amazonFeedReportModel = new AmazonFeedReport();
									$amazonFeedReportModel->updateFeedReport("feed_id = '{$feedID}'", array('scheduled' => 2, 'feed_seccess_total' => (int)$processingSummary->MessagesSuccessful));

									//如果处理数和成功数一致（全部都处理成功）										
									if(((int)$processingSummary->MessagesProcessed == (int)$processingSummary->MessagesSuccessful) && ((int)$processingSummary->MessagesProcessed > 0)){
										//刊登成功：更新相应上传状态
										$updateData = array(
											'upload_status' => AmazonProductAdd::UPLOAD_STATUS_SUCCESS,
											'receive_time' => date('Y-m-d H:i:s'),
										);
										$ret = $amazonProductAddStatusModel->updateUploadStatusByID($item['id'], $updateData);

										//当判断三个接口(价格、库存、图片)都已刊登成功（基本产品刊登不用判断），更新刊登总上传状态为刊登完成
										if ($ret && ($upload_type == AmazonProductAdd::UPLOAD_TYPE_PRICE || $upload_type == AmazonProductAdd::UPLOAD_TYPE_INVENTORY || $upload_type == AmazonProductAdd::UPLOAD_TYPE_IMAGE)){
											$tempRet = $amazonProductAddStatusModel->getAllUploadIsFinish($variationID);		
											if ($tempRet){
												$finishResult = $amazonProductAddModel->updateProductAddByID($add_id, array('status' => AmazonProductAdd::UPLOAD_STATUS_SUCCESS, 'upload_finish_time' => date('Y-m-d H:i:s')));			
												if ($finishResult){
													echo $this->successJson(array(
															'message' => Yii::t('amazon_product', 'All Upload Finish'),
													));
													Yii::app()->end();		
												}									
											}
											$upload_type_seccess .= ','.$amazonProductAddModel->getUploadTypeDesc($upload_type);
										}
									}else{
										//有错误或是警告处理
										$uploadMessage = '';
										if ($errorsResult){
											$uploadMessage = json_encode($errorsResult);
											foreach($errorsResult as $k => $v){
												if ($v['ResultCode'] == 'Error'){
													$showErrMessage .= '错误' .($k+1). ':<p style="color:red;">' .htmlentities($v->ResultDescription). '</p>';
													$showErrMessage .= '<br /><br />';
												}
											}
										}
										$updateData = array(
											'upload_status' => AmazonProductAdd::UPLOAD_STATUS_FAILURE,
											'receive_time' => date('Y-m-d H:i:s'),
											'upload_message' => $uploadMessage,
										);
										$ret = $amazonProductAddStatusModel->updateUploadStatusByID($item['id'],$updateData);

										//如果是基本产品刊登失败
										if ($ret && $upload_type == AmazonProductAdd::UPLOAD_TYPE_PRODUCT){
											$amazonProductAddModel->updateProductAddByID($add_id, array('status' => AmazonProductAdd::UPLOAD_STATUS_FAILURE, 'upload_finish_time' => date('Y-m-d H:i:s')));
										}
									}
								}
							}
						}
					}

					if ($showErrMessage){
						$errMessageList = 'FeedID:'.$feedID.'上传类型:'.$amazonProductAddModel->getUploadTypeDesc($upload_type).'<br />'.$showErrMessage.'<br /><br />';
					} 
				}
			}else{
				throw new Exception(Yii::t('amazon_product', 'Update Status not Exists'));
			}

			if ($errMessageList){
				echo $this->failureJson(array(
						'message' => Yii::t('amazon_product', 'UPLOAD STATUS FAILURE CONTENT', array('CONTENT' => $errMessageList)),
				));
			}else{
				echo $this->successJson(array(
						'message' => Yii::t('amazon_product', 'Callback Result Upload UploadText Success', array('UploadText' => $upload_type_seccess)),
				));
			}
		}catch (Exception $e){
			echo $this->failureJson(array(
					'message' => $e->getMessage(),
			));
		}
		Yii::app()->end();
	}	
	
	public function actionGetproductprice() {
		echo 'test';
		Yii::app()->end();
	}

	/**
	 * 批量删除选中的记录
	 * @link /amazon/amazonproductadd/selecteddelete/
	 * @param ids
	 */
	public function actionselectedDelete(){
		if (Yii::app()->request->isAjaxRequest && isset($_REQUEST['ids'])) {
			$ids = $_REQUEST['ids'];
			$idsArr = explode(',', $ids);
			$idsList = array_filter($idsArr);

			if (!empty($idsList)) {
				//刊登主表，刊登多属性表，上传状态表
				AmazonProductAddStatus::model()->deleteAll("add_id in (" .implode(',', $idsList). ")");
				$delVariation = AmazonProductAddVariation::model()->deleteAll("add_id in (" .implode(',', $idsList). ")");				
				if ($delVariation){
					if (AmazonProductAdd::model()->deleteAll("id in (" .implode(',', $idsList). ")")){
						echo $this->successJson(array(
							'message' => Yii::t('system', 'Delete successful'),
							'navTabId' => 'page' . Menu::model()->getIdByUrl('/amazon/amazonproductadd/list'),
						));
						Yii::app()->end();
					}
				}else{
					echo $this->failureJson(array(
						'message' => Yii::t('amazon_preduct', 'Sub SKU Delete failure'),
					));
					Yii::app()->end();
				}
			}
		}
		echo $this->failureJson(array(
			'message' => Yii::t('system', 'Delete failure'),
		));
		Yii::app()->end();		
	}	
	

	/**
	 * @desc 立即上传
	 * @link /amazon/amazonproductadd/uploadnow/add_id/4/upload_type/2
	 */
	/*public function actionUploadNow() {
		set_time_limit(3600);
		ini_set('memory_limit','1024M');
		ini_set('display_errors', true);

		$addId      = Yii::app()->request->getParam('add_id');
		$uploadType = Yii::app()->request->getParam('upload_type');
		$sku          = '';
		$accountID    = 0;
		$submitFeedId = '';

		try{
			if(empty($addId)){
				throw new Exception(Yii::t('amazon_product', 'Param Error'));
			}

			$amazonProductAddModel = new AmazonProductAdd();
			$addInfo = $amazonProductAddModel->getProductAddInfoByID($addId);
			if (!$addInfo){
				throw new Exception(Yii::t('amazon_product', 'Publish Info not Exists'));
			}else{
				$sku = $addInfo['sku'];
				$accountID = $addInfo['account_id'];
			}
			
			if ($uploadType == AmazonProductAdd::UPLOAD_TYPE_PRODUCT){
				$submitFeedId = $amazonProductAddModel->amazonProductPublish($accountID,$addId,$uploadType);
			}else{
				//获取基本产品上传确认成功后，才能上传价格、库存
				//相对于上传基本产品，应该是只要有成功的，就可以走后续的价格库存等接口
				$productInfo = AmazonProductAddStatus::model()->getCheckProductIsSeccess($addId);	
				if ($productInfo){	
					//如果是图片刊登，先检查图片表是否有远程地址
					if ($uploadType == AmazonProductAdd::UPLOAD_TYPE_IMAGE){
						$productImageAddModel = new ProductImageAdd();
		            	$imgResult = $productImageAddModel->sendImageUploadRequest($sku, $accountID, '', Platform::CODE_AMAZON);
		            	if ($imgResult){
		            		$submitFeedId = $amazonProductAddModel->amazonProductPublish($accountID,$addId,$uploadType);
		            	}else{
							echo $this->failureJson(array(
									'message' => Yii::t('amazon_product', $productImageAddModel->getErrorMessageImg()),
							));
							Yii::app()->end();            		
		            	}	            	
		            }else{
						$submitFeedId = $amazonProductAddModel->amazonProductPublish($accountID,$addId,$uploadType);
					}
				}else{
					echo $this->failureJson(array(
							'message' => Yii::t('amazon_product', 'Confirm Product Upload Had Successful'),
					));
					Yii::app()->end();
				}
			}

			if ($submitFeedId){
				echo $this->successJson(array(
						'message' => Yii::t('amazon_product', 'Upload UploadText Successful', array('UploadText' => AmazonProductAdd::model()->getUploadTypeDesc($uploadType))),
				));
			}else{
				echo $this->failureJson(array(
						'message' => Yii::t('system', 'Operate Failure:'.$amazonProductAddModel->getErrorMessage()),
				));
			}
		}catch (Exception $e){
			echo $this->failureJson(array(
					'message' => $e->getMessage(),
			));
		}
		Yii::app()->end();
	}*/	

	/**
	 * @desc 亚马逊批量刊登
	 * 刊登表整合同账号同类型上传记录，同账号同上传类型同组，分类型多个接口合并刊登，优先处理基本产品上传，其它类型要先判断基本产品成功才能刊登
	 * 通过刊登主表上传总状态为非上传成功为刊登依据，分账号多线程请求刊登，同一账号不同类型刊登时，sleep后再做下个批量刊登
	 * 刊登主表上传总状态为上传失败，指的是基本产品刊登失败，其它如价格、库存刊登失败，不写到总状态里，但其它每个类型的成功都会进行总状态判断
	 * @link /amazon/amazonproductadd/amazonbatchpublish/account_id/43/upload_type/3
	 * @param int $accountID 账号ID
	 * @param int $uploadType 上传类型
	 * @return string
	 */
	function actionAmazonBatchPublish(){
		set_time_limit(3*3600);
		ini_set('memory_limit', '1024M');
        ini_set("display_errors", true);
    	error_reporting(E_ALL);   

		$msg           = '';
		$variationIDs = '';
		$uploadType    = 0;
		$accountID     = Yii::app()->request->getParam('account_id');	
		$uploadType    = Yii::app()->request->getParam('upload_type');
		if(!empty($accountID)) $accountID = (int)$accountID;
		if(!empty($uploadType)) $uploadType = (int)$uploadType;
		if ($accountID){
			$amazonProductAddModel = new AmazonProductAdd();	
			// $productAddIDs = $amazonProductAddModel->getProductAddIDsByAccountID($accountID);	//获取同账号待刊登IDs
			$variationIDs = AmazonProductAddVariation::model()->getProductVariationIDsByAccountID($accountID);	//获取同账号待刊登多属性IDs
			if (empty($variationIDs)){
				$msg = '没有可刊登的数据';
			}else{
				$msg = $amazonProductAddModel->amazonProductPublish($accountID, $variationIDs, $uploadType);
			}
            echo $msg;
			Yii::app()->end();
		} else {
			$accountList = AmazonAccount::model()->getCronGroupAccounts();
			//循环每个账号
			foreach ($accountList as $accountID) {
				if(empty($accountID)) continue;
                // $url = Yii::app()->request->hostInfo.'/' . $this->route . '/account_id/' . $accountID . '/upload_type/' . $uploadType;
                // MHelper::runThreadBySocket($url);
                MHelper::runThreadSOCKET('/'.$this->route.'/account_id/' . $accountID . '/upload_type/' . $uploadType);
                sleep(1);
			}
			Yii::app()->end();		
		}
	}

	/**
	 * 亚马逊获取平台处理结果，并更新入库
	 * @link /amazon/amazonproductadd/getfeedsubmissionlist/account_id/43/feed_id/57049017070
	 */
	function actionGetFeedSubmissionList(){
		set_time_limit(2*3600);
        ini_set("display_errors", true);
    	error_reporting(E_ALL);		
		$accountID = Yii::app()->request->getParam('account_id');
		$feedID = Yii::app()->request->getParam('feed_id');
		if(!empty($accountID)) $accountID = (int)$accountID;
		if(!empty($feedID)) $feedID = addslashes(trim($feedID));
		$reportType = GetCommonFeedSubmissionListRequest::EVENT_NAME;
		if ($accountID){
			$errMessageList  = '';
			$showMessageList = '';
			$feedIDList_str  = '';

			//写入日志表		
			$amazonLogModel = new AmazonLog();		
			$logID = $amazonLogModel->prepareLog($accountID, $reportType);
			try{
				if(!$logID) throw new Exception("Log create failure!!");
				if(!$amazonLogModel->checkRunning($accountID, $reportType)){
					$amazonLogModel->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
					throw new Exception(Yii::t('systems', 'There Exists An Active Event'));
				}
				$amazonLogModel->setRunning($logID);

				$getFeedReportModel = new AmazonFeedReport();
				$feedIDList = $getFeedReportModel->getSubmissionIDList($accountID,$feedID);
				if ($feedIDList){	
					if($feedIDList) $feedIDList_str = implode(',',$feedIDList);

					$getFeedSubmissionListRequest = new GetCommonFeedSubmissionListRequest();		
					$getFeedSubmissionListRequest->setAccount($accountID);
					$getFeedSubmissionListRequest->setFeedSubmissionId($feedIDList);
					$response = $getFeedSubmissionListRequest->setRequest()->sendRequest()->getResponse();
					// MHelper::printvar($response);			
					if ($response){
						$done      = CommonSubmitFeedRequest::FEED_STATUS_DONE;			//成功
						$cancelled = CommonSubmitFeedRequest::FEED_STATUS_CANCELLED;	//失败
						//返回结果成功或是失败，写入刊登报告表(_IN_PROGRESS_或是_SUBMITTED_等其它状态都不处理)
						$statusDoneNums = isset($response[$done]) ? count($response[$done]) : 0;
						$statusCancelledNums = isset($response[$cancelled]) ? count($response[$cancelled]) : 0;
						if ($statusDoneNums > 0){
							$getFeedReportModel->updateScheduledByFeedIDs($response[$done], AmazonFeedReport::SCHEDULED_DONE, $done);
						}elseif($statusCancelledNums > 0){
							//平台处理已中止，即刊登结果为已失败，状态为已取（非平台已处理）
							$getFeedReportModel->updateScheduledByFeedIDs($response[$cancelled],AmazonFeedReport::SCHEDULED_YES, $cancelled);
							//批量更新上传状态表，标识上传状态为已失败
							AmazonProductAddStatus::model()->updateStatusByFeedIDs($response[$cancelled],AmazonProductAdd::UPLOAD_STATUS_FAILURE);
						}					
					}else{
						// $errMessageList = '获取刊登结果失败：接口返回为空';
						throw new Exception('获取刊登结果失败：接口返回为空<br />FeedIDList:'.$feedIDList_str);
					}
				}else{
					$showMessageList = '不存在未获取的刊登记录';
				}

				//输出显示
				$amazonLogModel->setSuccess($logID, "FeedIDList:".$feedIDList_str.$showMessageList);
				$msg = "操作成功：<br />FeedIDList:".$feedIDList_str.$showMessageList;
			} catch (Exception $e) {
				$amazonLogModel->setFailure($logID, $e->getMessage());
				$msg = "操作失败：".$e->getMessage()."<br />FeedIDList:".$feedIDList_str;
			}

			echo $msg;
			Yii::app()->end();
		} else {
			$accountList = AmazonAccount::model()->getCronGroupAccounts();
			foreach ($accountList as $accountID) {
				if(empty($accountID)) continue;
                // $url = Yii::app()->request->hostInfo.'/' . $this->route . '/account_id/' . $accountID . '/feed_id/' . $feedID;
                // MHelper::runThreadBySocket($url);
                MHelper::runThreadSOCKET('/'.$this->route.'/account_id/' . $accountID);
                sleep(1);
			}
			Yii::app()->end();
		}			
	}
	

	/**
	 * @desc 获取刊登结果，并把刊登状态和错误消息入库(定时任务，为刊登产品库存价格等接口的首要条件)
	 * @link /amazon/amazonproductadd/batchfeedresult/account_id/43/feed_id/57197017073
	 */
	public function actionBatchFeedResult() {
		set_time_limit(2*3600);
		ini_set('memory_limit', '1024M');
        ini_set("display_errors", true);
    	error_reporting(E_ALL);
    	$path = 'amazon/getfeedresult/'.date("Ymd");

		$accountID = Yii::app()->request->getParam('account_id');
		$feedID = Yii::app()->request->getParam('feed_id');
		if(!empty($accountID)) $accountID = (int)$accountID;
		if(!empty($feedID)) $feedID = addslashes(trim($feedID));		
		if ($accountID){
			try{
				$feedList = array();
				$singleFeedResult = 0;
				$amazonFeedReportModel = new AmazonFeedReport();
				$feedList = $amazonFeedReportModel ->getFeedResultList($accountID, $feedID);	//刊登报告列表（平台已处理的未取结果的记录）
				if ($feedList){
					foreach($feedList as $item){			
						$errMessage           = '';						
						$errLogMessage        = '';
						$showErrMessage       = '';	
						$noData               = '';
						$uploadVarationIDsArr = array();
						$errIDs               = array();	//错误ID列表
						$errList              = array();	//错误信息列表	
						$secessIDs            = array();	//成功信息列表	

						if (is_array($item)){
							$feedID = $item['feed_id'];
						}else{
							//查询单个刊登结果，如果不在数据库中，也可以通过FeedID直接查询打印刊登结果
							$singleFeedResult = 1;
							$feedID = (string)$item;
						}

						//调用接口取上传结果
						$response = AmazonList::model()->GetCommonFeedSubmissionResult($accountID,$feedID);
						if(!$response){
							$errMessage .= Yii::t('amazon_product', 'The API Result is Empty, Wait For Uploading ...')."\n";
						}

						//打印单个刊登结果，并结束
						if($singleFeedResult == 1){
							echo '<pre>';
							print_r($response);
							exit;
						}

						//调试用日志文档
						//MHelper::writefilelog($path.'/FeedID-'.$feedID.'.txt', json_encode($response));	

						$amazonProductAddModel          = new AmazonProductAdd();
						$amazonProductAddVariationModel = new AmazonProductAddVariation();
						$amazonProductAddStatusModel    = new AmazonProductAddStatus();

						if(isset($response->Message->ProcessingReport->ProcessingSummary)){
							$processingReport = $response->Message->ProcessingReport;	//处理明细
							$statusCode       = $processingReport->StatusCode;			//平台处理状态
							$errorsResult     = $processingReport->Result;				//警告和错误集
							//亚马逊平台已处理的标识
							if($statusCode == 'Complete'){
								$processingSummary = $processingReport->ProcessingSummary;
								//标识请求报告已获取
								$amazonFeedReportModel->updateFeedReportByID($item['id'], array('scheduled' => 2, 'feed_seccess_total' => (int)$processingSummary->MessagesSuccessful));
										
								if((int)$processingSummary->MessagesProcessed > 0){
									//处理警告错误信息，但过滤警告（警告是指已处理成功但有问题）																	
									if ($errorsResult){									
										$errLogMessage = $response; //写入日志
										foreach($errorsResult as $k => $errVal){
											$sell_sku    = '';
											$descArr     = array();
											//转换为数组操作，否则对象经常带有[0]
											$errVal = json_decode(json_encode($errVal),true);											
											$sell_sku = isset($errVal['AdditionalInfo']['SKU']) ? $errVal['AdditionalInfo']['SKU'] : '';	//在线SKU
											//以在线SKU为键名拼装数组
											if ($errVal['ResultCode'] == 'Error'){												
												//存在多条同个在线SKU的错误信息，合并在一个在线SKU数组中										
												if ($errList && array_key_exists($sell_sku,$errList)){
													$descArr = $errList[$sell_sku];												
													if($descArr) $descArr[] = $errVal['ResultDescription'];
												}else{
													$descArr[] = $errVal['ResultDescription'];
												}
												$errIDs[] = $sell_sku;
												$errList[$sell_sku] = $descArr;												
											}											
										}
									}
									// print_r($errIDs);
									// MHelper::printvar($errList);

									//处理上传状态表(同feedid且为上传中状态的记录)
									$uploadStatusList = $amazonProductAddStatusModel->getStatusListByFeedID($feedID);
									if ($uploadStatusList){
										foreach ($uploadStatusList as $val){	
											$sell_sku     = '';
											$ret          = false;
											$variationRet = array();
											$upload_type  = $val['upload_type'];
											$variationID  = $val['variation_id'];
											//通过多属性ID获取在线SKU
											$variationInfo = AmazonProductAddVariation::model()->getVariationInfoByID($val['variation_id']);
											if($variationInfo) $sell_sku = $variationInfo['seller_sku'];
											if(empty($sell_sku)){
												$errMessage .= '存在在线SKU或是多属性数据为空，多属性ID为 '.$variationID."\n";
												continue;
											}

											//如果包含在错误列表中，即表示刊登出错
											if ($errIDs && in_array($sell_sku,$errIDs)){
												$desc = (isset($errList[$sell_sku])) ? json_encode($errList[$sell_sku]) : '';
												$updateData = array(
													'upload_status' => AmazonProductAdd::UPLOAD_STATUS_FAILURE,
													'receive_time' => date('Y-m-d H:i:s'),
													'upload_message' => $desc,
												);
												$ret = $amazonProductAddStatusModel->updateUploadStatusByID($val['id'],$updateData);	

												//如果刊登失败，子SKU刊登上传状态更新为刊登失败	
												if ($ret){
													$amazonProductAddVariationModel->updateProductAddVarationByID($variationID, array('status' => AmazonProductAdd::UPLOAD_STATUS_FAILURE, 'upload_finish_time' => date('Y-m-d H:i:s')));
												}																					
											}else{
												//刊登成功：没有错误列表即所有都刊登成功
												$updateData = array(
													'upload_status' => AmazonProductAdd::UPLOAD_STATUS_SUCCESS,
													'receive_time' => date('Y-m-d H:i:s'),
												);
												$ret = $amazonProductAddStatusModel->updateUploadStatusByID($val['id'],$updateData);

												//基本产品成功，即标识主SKU上传中
												// if ($ret && ($upload_type == AmazonProductAdd::UPLOAD_TYPE_PRODUCT)){
												//标识主SKU为上传中
												if ($ret){	
													$mainInfo = $amazonProductAddModel->getProductAddInfoByID($val['add_id']);
													if ($mainInfo && $mainInfo['status'] != AmazonProductAdd::UPLOAD_STATUS_RUNNING){
														$amazonProductAddModel->updateProductAddByID($val['add_id'], array('status' => AmazonProductAdd::UPLOAD_STATUS_RUNNING));
													}
												}

												//当判断三个接口(价格、库存、图片)都已刊登成功（基本产品刊登不用判断），更新子SKU上传状态为刊登成功
												if ($ret && ($upload_type == AmazonProductAdd::UPLOAD_TYPE_PRICE || $upload_type == AmazonProductAdd::UPLOAD_TYPE_INVENTORY || $upload_type == AmazonProductAdd::UPLOAD_TYPE_IMAGE)){
													$finishRet = $amazonProductAddStatusModel->getAllUploadIsFinish($variationID);			
													if ($finishRet){														
														$variationRet = $amazonProductAddVariationModel->updateProductAddVarationByID($variationID, array('status' => AmazonProductAdd::UPLOAD_STATUS_SUCCESS, 'upload_finish_time' => date('Y-m-d H:i:s')));
														if ($variationRet){
														    //
															//查询主SKU下是不是所有子SKU都刊登成功，如果是则主SKU更新为刊登成功
															$variationList = $amazonProductAddVariationModel->getFormatVariationListByAddID($val['add_id']);	//获取子SKU列表
															$allSuccessFlag = 0;	//非所有子SKU刊登成功标识
															if ($variationList){
																$allSuccessFlag = 1;
																foreach($variationList as $variationItem){
																	if($variationItem['status'] != AmazonProductAdd::UPLOAD_STATUS_SUCCESS) $allSuccessFlag = 0;
																}
															}
															//子SKU都刊登成功
															if ($allSuccessFlag == 1){
																$amazonProductAddModel->updateProductAddByID($val['add_id'], array('status' => AmazonProductAdd::UPLOAD_STATUS_SUCCESS, 'upload_finish_time' => date('Y-m-d H:i:s')));
																$addInfo = $amazonProductAddModel->getProductAddInfoByID($val['add_id']);
																AmazonWaitListing::model()->updateWaitingListingStatus($addInfo, AmazonWaitListing::STATUS_SCUCESS);
																AmazonHistoryListing::model()->updateWaitingListingStatus($addInfo, AmazonHistoryListing::STATUS_SCUCESS);
															}
														}else{
															if(!$variationRet) throw new Exception(Yii::t('amazon_product', 'Update Status Operation Failure: variationRet'));										
														}
													}
												}												
											}
											//强制提示出错
											if(!$ret) throw new Exception(Yii::t('amazon_product', 'Update Status Operation Failure: ret'));										
										}
									}else{
										$noData .= Yii::t('amazon_product', 'Not Find The Upload Status Record')."\n";
									}
								}
							}else{
								$errMessage .= Yii::t('amazon_product', 'The Platform not Processing Finish')."\n";										
							}
						}
						//如果存在异常存入到刊登报告表
						if (!empty($errMessage) || !empty($noData)){
							$amazonFeedReportModel->updateFeedReportByID($item['id'], array('feed_result_info' => $errMessage.$noData));
							//把异常错误写入错误日志文档
							if (!empty($errMessage)){								
								MHelper::writefilelog($path.'/'.$accountID.'/Error-log'.$item['id'].'.txt', $errMessage);							
							}
						}
					}
				}else{
					echo Yii::t('amazon_product', 'Record Not Exists').'**<br />';
				}
				echo '操作完成';			
			}catch (Exception $e){
				//把异常错误写入错误日志文档
				//MHelper::writefilelog($path.'/Error-log.txt', "\n"."accountID:"."\n".$e->getMessage());
				echo $e->getMessage();				
			}
			Yii::app()->end();
		} else {
			$accountList = AmazonAccount::model()->getCronGroupAccounts();
			//循环每个账号
			foreach ($accountList as $accountID) {
				if(empty($accountID)) continue;
                MHelper::runThreadSOCKET('/'.$this->route.'/account_id/' . $accountID);
                sleep(3);
			}
			Yii::app()->end();
		}			
	}		

	/**
	 * @link /amazon/amazonproductadd/test
	 */

	public function actionTest(){
        ini_set("display_errors", true);
    	error_reporting(E_ALL);

		echo 'todo';
		// exit;
		// AmazonProductAdd::model()->updateProductAddByID('30',array('status'=>'0'));
		// $conditions = "status = 5";
		// AmazonProductAdd::model()->updateProductAdd($conditions, array('status'=>'1','upload_finish_time'=>'0000-00-00 00:00:00'));
		// $conditions = "upload_status = 5";
		// AmazonProductAddStatus::model()->updateProductAddStatus($conditions,array('upload_status'=>'4','upload_message'=>''));		
		// AmazonProductAddStatus::model()->updateUploadStatusByID('61',array('upload_status'=>'0'));
		// $desc = json_encode('刊登失败：产品表已存在同账号同系统SKU的记录：62114，不允许重复刊登。');
		// AmazonProductAddStatus::model()->updateUploadStatusByID('116',array('upload_message'=>$desc));	
		// AmazonProductAdd::model()->updateProductAddByID('65',array('create_user_id'=>'672'));
		// AmazonProductAdd::model()->updateProductAddByID('109',array('status'=>'5','upload_finish_time'=>'0000-00-00 00:00:00'));
		// AmazonProductAddStatus::model()->updateUploadStatusByID('389',array('feed_id'=>'','upload_status'=>'5'));	
		// AmazonFeedReport::model()->updateFeedReportByID('326',array('scheduled'=>'2'));	
		// AmazonProductAdd::model()->updateProductAddByID('152',array('status'=>'0'));	
		// AmazonProductAddStatus::model()->updateUploadStatusByID('461',array('upload_status'=>'0','upload_message'=>''));	

		// $sku = '102397.01';
		// $accountID = '58';
		// $site_id = AmazonSite::getSiteIdByName('us');
		// $productImageAddModel = new ProductImageAdd();
		// $imgResult = $productImageAddModel->sendImageUploadRequest($sku, $accountID, $site_id, Platform::CODE_AMAZON);	
		


		// $sku = '110954';
		// $accountID = '58';
		// $site_id = AmazonSite::getSiteIdByName('us');
		// $productImageAddModel = new ProductImageAdd();
		// $imgResult = $productImageAddModel->sendImageUploadRequest($sku, $accountID, $site_id, Platform::CODE_AMAZON);	

		//删除无用common图片
		// $productImageAddModel = new ProductImageAdd();
		// $productImageAddModel->dbConnection->createCommand()->delete($productImageAddModel->tableName(), "id = 11566913");	//119110


		// $info = '445,478,485,491,517,527,536,540,543';
		// $info = '573,574,575,576,577,579,580,581,582,583,584,585,586,587,588,589,590,591,592,593,594,595,596,597,598,599,600,602,605,606,607,614,628,629,630,631,642,643,644,650,651,654,659,660,661,668,671,681,685,687,688,689,694,699,701,702,703,706,707,709,713,718,724,743,744,745,746,747,748,749,750,751,752,755,756,757,758,759,768,769,770,771,772,773';
		// $variationList = explode(',',$info);
		// // MHelper::printvar($variationList);
		// foreach($variationList as $val){
		// 	$varationInfo = AmazonProductAddVariation::model()->getVariationInfoByID($val);
		// 	$addID = $varationInfo['add_id'];
		// 	if ($addID > 0){
		// 		$updata = array(
		// 			'upload_status' => '0'
		// 		);
		// 		$conditions = "add_id = '{$addID}'";
		// 		AmazonProductAddStatus::model()->updateProductAddStatus($conditions,$updata);
		// 		AmazonProductAdd::model()->updateProductAddByID($addID,array('status'=>'0'));
		// 	}					
		// }

		// AmazonProductAddStatus::model()->updateProductAddStatus("upload_type = 4 AND upload_status = 1", array('upload_status' => 0));

		// $sql = "update ueb_amazon_product_add p left join ueb_amazon_product_add_variation v on v.add_id = p.id set p.status = v.status,p.upload_finish_time = v.upload_finish_time where p.status = 1 AND v.status = 4";
		// $sql = "update ueb_amazon_product_add_variation set status = 5 where add_id in ('492','860','1503','1538','1626','2111','2274')";
		// $result = AmazonProductAdd::model()->getDbConnection()->createCommand($sql)->execute();		

	}

	/**
	 * 获取有效SKU信息
	 * @link /amazon/amazonproductadd/validatesku/sku/120580
	 */
	public function actionValidatesku(){
		$accountID = Yii::app()->request->getParam('account_id');
		$sku = Yii::app()->request->getParam('sku');
		$sku = trim($sku);	

		$skuInfo = Product::model()->getProductInfoBySku($sku);	//产品信息，是否为主SKU或空
		if ($skuInfo){
			//是否主SKU
			if ($skuInfo['product_is_multi'] == Product::PRODUCT_MULTIPLE_MAIN){
			 	echo $this->failureJson(array(
			 		'message' => Yii::t('wish_listing', 'Can not main sku')
				));
				Yii::app()->end();
			}else{
				//检测是否侵权
				if(ProductInfringe::model()->getProductIfInfringe($sku)){
				 	echo $this->failureJson(array(
				 		'message' => Yii::t('wish_listing', 'The SKU has been infringed')
					));
					Yii::app()->end();		
				}
			}
		}else{
		 	echo $this->failureJson(array(
		 		'message' => Yii::t('wish_listing', 'Not found the sku')
			));
			Yii::app()->end();
		}

		$encryptSku = new encryptSku();
		$SellSku = $encryptSku->getAmazonEncryptSku($sku);

		//子SKU选中的图片字符串
		$amazonProductAddModel = new AmazonProductAdd();	 
		$imageStr = $amazonProductAddModel->getSkuSelectedImage($sku, $accountID);		

		echo $this->successJson(array(
			'sell_sku'          => $SellSku,
			'product_id'        => $skuInfo['id'],
			'add_select_images' => $imageStr,
			'message'           => Yii::t('system', 'Save successful')
		));
		Yii::app()->end();
	}	

	/**
	 * 单个删除子SKU
	 * @link /amazon/amazonproductadd/delvariant/add_id/xxx/variation_id/xxx/
	 */
	public function actionDelvariant(){
		$addId = Yii::app()->request->getParam('add_id');
		$variationID = Yii::app()->request->getParam('variation_id');
		try{
			if(empty($addId) || empty($variationID)){
				throw new Exception(Yii::t('amazon_product', 'Param error'));
			}
			$VarInfo = AmazonProductAddVariation::model()->getVariationInfoByID($variationID);
			if ($VarInfo){
				if ($VarInfo['status'] == AmazonProductAdd::UPLOAD_STATUS_SUCCESS){
					throw new Exception(Yii::t('amazon_product', 'Sub SKU have Publish Seccessful! Delete Failure'));
				}else{
					//删除子SKU上传状态记录
					AmazonProductAddStatus::model()->deleteListByConditions('add_id=:add_id AND id=:id', array(':add_id'=>$addId, ':id'=>$variationID));
					$ret = AmazonProductAddVariation::model()->deleteListByConditions('add_id=:add_id AND id=:id', array(':add_id'=>$addId, ':id'=>$variationID));
					if($ret){
						echo $this->successJson(array(
								'message' => Yii::t('system', 'Delete successful'),
						));
						Yii::app()->end();
					}else{
						throw new Exception(Yii::t('system', 'Delete failure'));
					}
				}
			}else{
				throw new Exception(Yii::t('amazon_product', 'Publish Sub Info not Exists'));
			}
		}catch(Exception $e){
			echo $this->failureJson(array(
					'message' => $e->getMessage(),
			));
			Yii::app()->end();
		}		
	}	

	/**
	 * 刷新子SKU上传状态（把上传失败改为待上传状态）
	 * @link /amazon/amazonproductadd/reuploadstatus/variation_id/xxx/
	 */
	public function actionReuploadstatus(){
		$varationID = Yii::app()->request->getParam('variation_id');
		try{
			if(empty($varationID)){
				throw new Exception(Yii::t('amazon_product', 'Param error'));
			}
			$amazonProductAddVariationModel = new AmazonProductAddVariation;
			$variationInfo = $amazonProductAddVariationModel->getVariationInfoByID($varationID);
			if ($variationInfo){
				if ($variationInfo['status'] == AmazonProductAdd::UPLOAD_STATUS_FAILURE){
					//批量更新上传失败的接口
					$conditions = "variation_id = " .$variationInfo['id']. " AND upload_status = ".AmazonProductAdd::UPLOAD_STATUS_FAILURE;
					$uploadStatusRet = AmazonProductAddStatus::model()->getUploadStatusListByCondition($conditions);
					if ($uploadStatusRet) {
						$statusRes = AmazonProductAddStatus::model()->updateProductAddStatus($conditions,array('upload_status' => AmazonProductAdd::UPLOAD_STATUS_DEFAULT));
					}else{
						throw new Exception(Yii::t('amazon_product', 'Not Upload Failure Api Operation'));
					}
					//更新子SKU上传状态
					if ($statusRes){						
						$subStatusRes = $amazonProductAddVariationModel->updateProductAddVarationByID($varationID,array('status' => AmazonProductAdd::UPLOAD_STATUS_DEFAULT, 'upload_finish_time' => '0000-00-00 00:00:00'));
					}
					if (!$subStatusRes){						
						throw new Exception(Yii::t('system', 'Failure Operation'));
					}else{
						echo $this->successJson(array(
								'message' => Yii::t('system', 'Success Operation'),
						));
						Yii::app()->end();
					}
				}else{
					throw new Exception(Yii::t('amazon_product', 'Not Upload Failure Api Operation'));
				}
			}else{				
				throw new Exception(Yii::t('amazon_product', 'Publish Sub Info not Exists'));			
			}
		}catch(Exception $e){
			echo $this->failureJson(array(
					'message' => $e->getMessage(),
			));
			Yii::app()->end();
		}		
	}


	/**
	 * 子SKU选择刊登图片
	 */
	public function actionSelectimage() {
    	error_reporting(E_ALL);
    	ini_set('display_errors', true);

		$sku = Yii::app()->request->getParam('subsku');
		$accountID = Yii::app()->request->getParam('account_id');
		$imageReadOnly = Yii::app()->request->getParam('readonly', 0);

		$publishParams = array(
				'sku'                    => $sku,
				'publish_image_readonly' => $imageReadOnly,		
		);

		//产品信息
		$skuProductID = 0;
		$skuInfo = Product::model()->getProductInfoBySku($sku);
		if ($skuInfo){
			$skuProductID = $skuInfo['id'];
		}

		//获取SKU所有产品图片
//		$imageType = array('zt', 'ft');
//		$config = ConfigFactory::getConfig('serverKeys');
//		$skuImg = array();
//		foreach($imageType as $type){
//			$images = Product::model()->getImgList($sku,$type);
//			if ($images){
//				foreach($images as $k=>$img){
//					//如果图片名称没有带-，一般是不规范的小图，java服务器也不会上传这类不规范小图，需要屏蔽不显示
//					if (strpos($k,'-') > 0){
//						$skuImg[$type][$k] = $img;
//					}
//				}
//			}
//		}

        $skuImg = ProductImageAdd::getImageUrlFromRestfulBySku($sku, $typeAlisa = null, $type = 'normal', $width = 100, $height = 100, $platform = Platform::CODE_AMAZON);

        /**
         * 修复java api接口无主图返回问题
         */
        if (isset($skuImg['ft']) && !isset($skuImg['zt'])) {
            $skuImg['zt'] = $skuImg['ft'];
        }
		//选中的图片
   		// $newImgList = array();
   		// $productImageAdd = new ProductImageAdd();
   		// $selectedImages = array('zt'=>array(), 'ft'=>array());
   		// $imgList = $productImageAdd->getImageBySku($sku, $accountID, Platform::CODE_AMAZON);
   		// echo $sku.'+'.$accountID;
   		// MHelper::printvar($imgList);
   		// if($imgList){
   		// 	foreach ($imgList as $type=>$imgs){
   		// 		foreach ($imgs as $img){
   		// 			//$imgkey = current(explode(".", $img['image_name']));
   		// 			$imgkey = substr($img['image_name'], 0, strrpos($img['image_name'], "."));
   		// 			if($type == ProductImageAdd::IMAGE_ZT){
   		// 				$selectedImages['zt'][] = $imgkey;
   		// 				if(!empty($skuImg['zt'][$imgkey])){
   		// 					$newImgList['zt'][$imgkey] = $skuImg['zt'][$imgkey];
   		// 					unset($skuImg['zt'][$imgkey]);
   		// 				}
   		// 			}else{
   		// 				$selectedImages['ft'][] = $imgkey;
   		// 				if(!empty($skuImg['ft'][$imgkey])){
   		// 					$newImgList['ft'][$imgkey] = $skuImg['ft'][$imgkey];
   		// 					unset($skuImg['ft'][$imgkey]);
   		// 				}
   		// 			}
   		// 		}
   		// 	}
   		// }
   		// $newImgList = array_merge_recursive($newImgList, $skuImg);		

		$this->render('selectimage', array(
			'action'         => 'create',
			'addID'          => 0,
			'productID'      => $skuProductID,
			'sku'            => $sku,
			'imgomsURL'      => '',//$config['oms']['host'],
			'publishParams'  => $publishParams,
			'skuImg'         => $skuImg,
			// 'selectedImages' => $selectedImages,
		));
	}

	
}