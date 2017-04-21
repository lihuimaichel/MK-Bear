<?php
/**
 * @desc ebay
 * @author lihy
 *
 */
class EbayproductstatisticController extends UebController {
	
	/** @var object 模型实例 **/
	protected $_model = NULL;
	
	/**
	 * (non-PHPdoc)
	 * @see CController::init()
	 */
	public function init() {
		$this->_model = new EbayProductStatistic();
	}
	
	/**
	 * @desc 列表页
	 */
	public function actionList() {
		$accountID = Yii::app()->request->getParam('account_id', '0');
		$siteID = Yii::app()->request->getParam('site_id', '0');
		$duration = Yii::app()->request->getParam('listing_duration', 'GTC');
		$this->_model->account_id = $accountID;
		
		$auctionListingDurations = array(
				'Days_5'	=>	'5天',
				'Days_3'	=>	'3天',
				'Days_7'	=>	'7天',
				'Days_10'	=>	'20天',
		);
		
		$fixpriceListingDurations = array(
					'GTC'		=>	'GTC',
					'Days_3'	=>	'3天',
					'Days_5'	=>	'5天',
					'Days_7'	=>	'7天',
					'Days_10'	=>	'10天',
					'Days_30'	=>	'30天',
			);
		
		$this->render('list', array(
				'model' => $this->_model, 'accountID'=>$accountID, 'siteID'=>$siteID, 'duration'=>$duration, 
				'auctionListingDurations'	=>	$auctionListingDurations,
				'fixpriceListingDurations'	=>	$fixpriceListingDurations
		));
	}
	
	/**
	 * @desc 批量添加刊登任务
	 * @throws Exception
	 */
	public function actionBatchPublish() {
		set_time_limit(300);
		$accountID = Yii::app()->request->getParam('account_id');
		$skus = Yii::app()->request->getParam('ids');
		$siteId = Yii::app()->request->getParam('site_id');
		$duration = Yii::app()->request->getParam('listing_duration', 'GTC');
		$listingType = Yii::app()->request->getParam('listing_type', EbayProductAdd::LISTING_TYPE_FIXEDPRICE);
		$auctionStatus = Yii::app()->request->getParam('auction_status', 0);
		$auctionPlanDay = Yii::app()->request->getParam('auction_plan_day', 10);
		$auctionRule = Yii::app()->request->getParam('auction_rule', 0);
		$configType = Yii::app()->request->getParam('config_type', '');
		$addType = Yii::app()->request->getParam('add_type', '');

		$filename = 'BatchPublish_'.date("Ymd").'.txt';

		if(empty($duration))
			$duration = 'GTC';
		
		if (empty($accountID)) {
			echo $this->failureJson(array(
					'message' => Yii::t('aliexpress_product_statistic', 'Invalid Account'),
			));
			Yii::app()->end();
		}

		if ( $siteId === '' || $siteId === null) {
			echo $this->failureJson(array(
					'message' => Yii::t('ebay', 'Site and Account Not Valid'),
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
		$ebayProductAddModel = EbayProductAdd::model();
		$message = '';
		foreach ($skuArr as $sku) {
			//检测是否有权限去刊登该sku
			//上线后打开注释---lihy 2016-05-10
			if($addType !=2 && !Product::model()->checkCurrentUserAccessToSaleSKUNew($sku, $accountID,Platform::CODE_EBAY, $siteId)){
				$message .= $sku.Yii::t('system', 'Not Access to Add the SKU').'<br/>';				
				continue;
			}
			//@todo 后期转为数组
			$return = $ebayProductAddModel->addProductByBatch($sku, $accountID, $siteId, $duration, $listingType, $auctionStatus, $auctionPlanDay, $auctionRule, $configType, $addType);
			if (!$return) {
				$message .= $sku." ".$ebayProductAddModel->getErrorMessage().'<br/>';
			}
			if ($addType == 2) {
				//MHelper::writefilelog($filename,$sku.' add success'."\r\n");				
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
	 * @DESC 获取品类信息
	 */
	public function actionGetonlinecatebyclsid(){
		$onlineCategoryId = Yii::app()->request->getParam("cls_id");
		$onlineCategoryList = ProductCategoryOnline::model()->getProductOnlineCategoryPairByClassId($onlineCategoryId);
		echo $this->successJson(array('data'=>$onlineCategoryList));
	}

	/**
	 * 上传图片for test
	 * @link /ebay/ebayproductstatistic/testuploadimg/account_id/41/language_code/Spain
	 */
	public function actionTestuploadimg() {
		$accountID 		= trim(Yii::app()->request->getParam('account_id','19'));
		//$siteId 		= trim(Yii::app()->request->getParam('site_id',''));
		$sku 			= trim(Yii::app()->request->getParam('sku',''));	
		// $languageCode 	= trim(Yii::app()->request->getParam('language_code','Spain'));//languageCode
		
		if (!$accountID) {
			die('accountID is empty');
		}	

		if (!$sku) {
			die('sku is empty');
		}			

		$skuArr = explode(',',$sku);
		MHelper::printvar($skuArr,false);
		foreach ($skuArr as $sku) {
			$res = EbayProductImageAdd::getSkuImageUpload($accountID,$sku);
			echo '<hr>###getSkuImageUpload: <pre>';print_r($res);
			if (empty($res['result']) || empty($res['result']['imageInfoVOs'])) {
				$res2 = EbayProductImageAdd::addSkuImageUpload($accountID,array($sku));
				echo '<hr>@@@@addSkuImageUpload: <pre>';print_r($res2);
			}else{
				foreach ($res['result']['imageInfoVOs'] as $img){
					echo "<img src='{$img['remotePath']}'/><p/>";
				}
			}
		}
	}

	/**
	 * [actionTestshipfee description]
	 * @link /ebay/ebayproductstatistic/testshipfee/account_id/41/site_id/186/sku/001
	 */
	public function actionTestshipfee() {
		$accountID 		= trim(Yii::app()->request->getParam('account_id',''));
		$siteId 		= trim(Yii::app()->request->getParam('site_id',''));
		$sku 			= trim(Yii::app()->request->getParam('sku',''));	
		if (!$accountID) {
			die('accountID is empty');
		}
		if (!$siteId) {
			die('siteId is empty');
		}		
		if (!$sku) {
			die('sku is empty');
		}
		$ebayProductModel = new EbayProduct();
		//从产品表中获取
		$ebayProductInfo = $ebayProductModel->getEbayProductInfoBySKU($sku, $siteId);
        if(empty($ebayProductInfo)){
            $ebayProductInfo = $ebayProductModel->getEbayProductInfoBySKU($sku, null);
            $ebayCategory = new EbayCategory();
			$langCode = EbaySite::getLanguageBySiteIDs($siteId);
			$productDesc = Productdesc::model()->getDescriptionInfoBySkuAndLanguageCode($sku, $langCode);
            $cateList = $ebayCategory->getSuggestCategoryList($accountID, $siteId, $productDesc['title'], $sku);
            $firstcate = null;
            is_array($cateList) && $cateList && $firstcate = array_shift($cateList);
            if($firstcate){
                $categoryID = $firstcate['categoryid'];
            }
        }else{
            $categoryID = $ebayProductInfo['category_id'];
            $categoryID2 = $ebayProductInfo['category_id2'];
        }
		$categoryName = EbayCategory::model()->getCategoryNameByID($categoryID);
		$currency = EbaySite::getCurrencyBySiteID($siteId);
		$salePriceData = EbayProductSalePriceConfig::model()->getSalePrice($sku, $currency, $siteId, $accountID, $categoryName);
		MHelper::printvar($salePriceData);
		exit;		
	}

	/**
	 * [actionTest description]
	 * @link /ebay/ebayproductstatistic/testaddtask/account_id/41/site_id/186/language_code/Spain/bulk/1
	 */
	public function actionTestaddtask() {
		set_time_limit(3600);
		ini_set('display_errors', true);
		error_reporting(E_ALL);

		$accountID 		= trim(Yii::app()->request->getParam('account_id',''));
		$siteId 		= trim(Yii::app()->request->getParam('site_id',''));
		$sku 			= trim(Yii::app()->request->getParam('sku',''));
		$getshipfee 	= trim(Yii::app()->request->getParam('getshipfee',0));
		$fortest 		= trim(Yii::app()->request->getParam('fortest',0));
		$bulk 			= trim(Yii::app()->request->getParam('bulk',0));//批量
		$languageCode 	= trim(Yii::app()->request->getParam('language_code',''));//languageCode

		//添加刊登任务 
		$duration 		= Yii::app()->request->getParam('listing_duration', 'GTC');
		$listingType 	= Yii::app()->request->getParam('listing_type', EbayProductAdd::LISTING_TYPE_FIXEDPRICE);
		$auctionStatus 	= Yii::app()->request->getParam('auction_status', 0);
		$auctionPlanDay = Yii::app()->request->getParam('auction_plan_day', 10);
		$auctionRule 	= Yii::app()->request->getParam('auction_rule', 0);
		$configType 	= Yii::app()->request->getParam('config_type', '');
		$show 			= trim(Yii::app()->request->getParam('show',0));

		if (!$bulk) {
			$failure = array();
			$addType = 2;//小语种刊登
			$ebayProductAddModel = EbayProductAdd::model();
			$isok = $ebayProductAddModel->addProductByBatch($sku, $accountID, $siteId, $duration, $listingType, $auctionStatus, $auctionPlanDay, $auctionRule, $configType, $addType);
			if (!$isok) {
				echo $sku.'-----'.$ebayProductAddModel->getErrorMessage()."<br>";
				$failure[] = $sku;
			}
			echo 'failure sku: '.implode(',', $failure)."<br>";
			exit;
		} else {
			if ($languageCode == '' || $accountID == '' || $siteId === '') {
				die('params are some empty');
			}

			$skuArr = array(
				// '100197','101550','102666','102816','103144','103376','103421','103507','104043','104071',
				// '16468','16636','31119','31129','31472','33115','40628','43045','52550','52551',
				// '59045','59551','61238','61836','62239','64969','66259','69710','69712','69715',
				'69722','69725','70088','71056','71308','71881','72007','73417','73421','73983',
				'73985','74726','76935','77302','78503','82497','83666','85309','85402','85529',
				'85530','87680','89398','89399','89680','89808','90957','91784','92927','93721',
				'93730','94344','97342','97582','97934','99942'
				);
			$url 	= Yii::app()->request->hostInfo.'/ebay/ebayproductstatistic/batchPublish';
			$post 	= array(
				'account_id'	=> $accountID,
				'site_id' 		=> $siteId,
				'ids'			=> implode(',',$skuArr), 
				'add_type' 		=> 2,
			);
			echo $url." <br>\r\n";
			echo json_encode($post)."\r\n<br>";
			MHelper::runThreadBySocket($url, $post, 0,'','',30,false);
		}

		die('finish');
	}
	
	/**
	 * [actionSendimg description]
	 * @link /ebay/ebayproductstatistic/sendimg/account_id/28/site_id/71
	 */
	public function actionSendimg(){
		set_time_limit(3600);
		error_reporting(E_ALL);
		ini_set("display_errors", true);
		ini_set('memory_limit', '256M');
		
		$accountID = 3;
		$accountID 		= trim(Yii::app()->request->getParam('account_id','25'));
		$siteId 		= trim(Yii::app()->request->getParam('site_id','71'));
		$addtask 		= trim(Yii::app()->request->getParam('addtask',0));

		//添加刊登任务
		$duration 		= Yii::app()->request->getParam('listing_duration', 'GTC');
		$listingType 	= Yii::app()->request->getParam('listing_type', EbayProductAdd::LISTING_TYPE_FIXEDPRICE);
		$auctionStatus 	= Yii::app()->request->getParam('auction_status', 0);
		$auctionPlanDay = Yii::app()->request->getParam('auction_plan_day', 10);
		$auctionRule 	= Yii::app()->request->getParam('auction_rule', 0);
		$configType 	= Yii::app()->request->getParam('config_type', '');
		
		$skuArr = array(	
			'83425',
		);

		if (!$addtask) {
			foreach ($skuArr as $sku) {
				$skulist = array();
				$skulist[] = $sku;
				$skuInfo = Product::model()->getProductInfoBySku($sku);
				if (!empty($skuInfo) && $skuInfo['product_is_multi'] == Product::PRODUCT_MULTIPLE_MAIN ) {
					//获取产品库中的子SKU列表
					$variationSkuOriList = EbayProductAdd::model()->getSubSKUListPairsByMainProductId($skuInfo['id']);
					$skulist = array_merge($skulist,$variationSkuOriList);
				}
				MHelper::printvar($skulist,false);
				$res2 = EbayProductImageAdd::addSkuImageUpload($accountID,$skulist);
				MHelper::printvar($res2,false);
			}
			exit('ok'); 
		}

		$addType = 2;//小语种刊登
		$ebayProductAddModel = EbayProductAdd::model();
		$failure = array();
		foreach ($skuArr as $sku){
			//$ebayProductAddModel->setSpecialCategoryID(11700);
			$isok = $ebayProductAddModel->addProductByBatch($sku, $accountID, $siteId, $duration, $listingType, $auctionStatus, $auctionPlanDay, $auctionRule, $configType, $addType);
			if (!$isok) {
				//echo $sku.'-----'.$ebayProductAddModel->getErrorMessage()."<br>";
				$failure[] = $sku.'-----'.$ebayProductAddModel->getErrorMessage()."<br>";
			}
		}
		//MHelper::writefilelog("france-add-home.log", 'failure sku: '.implode(' ', $failure)."<br>");
		echo 'failure sku: '.implode(' ', $failure)."<br>";
		exit;
	}
	
	/**
	 * 批量导入
	 */
	public function actionImport(){
		$duration = Yii::app()->request->getParam('listing_duration', 'GTC');
		if($_POST){
			set_time_limit(3600);
			ini_set('display_errors', true);
			ini_set('memory_limit', '256M');

			try{
				if(empty($_FILES['csvfilename']['tmp_name'])){
					throw new Exception("文件上传失败");
				}
				if($_FILES['csvfilename']['error'] != UPLOAD_ERR_OK){
					throw new Exception("文件上传失败, error:".$_FILES['csvfilename']['error']);
				}
				//限制下文件大小
				if($_FILES['csvfilename']['size'] > 2048000){
					echo $this->failureJson(array('message'=>"文件太大，在2M以下"));
					exit();
				}
				$file = $_FILES['csvfilename']['tmp_name'];
				
				
				$PHPExcel = new MyExcel();
				//excel处理
				Yii::import('application.vendors.MyExcel');
				$datas = $PHPExcel->get_excel_con($file);

				if(!empty($datas)){

					$sellerUserList = EbayAccount::model()->getIdNamePairs();
					$sellerUserList = array_flip($sellerUserList);
					$getSellerName = '';

					$siteList = EbaySite::model()->getSiteList();
					$siteList = array_flip($siteList);

					$getSiteName = '';

					//添加刊登任务
					$duration 		= Yii::app()->request->getParam('listing_duration', 'GTC');
					$listingType 	= Yii::app()->request->getParam('listing_type', EbayProductAdd::LISTING_TYPE_FIXEDPRICE);
					$auctionStatus 	= Yii::app()->request->getParam('auction_status', 0);
					$auctionPlanDay = Yii::app()->request->getParam('auction_plan_day', 10);
					$auctionRule 	= Yii::app()->request->getParam('auction_rule', 0);
					$configType 	= '';

					foreach ($datas as $key=>$data){
						if($key == 1) continue;
						$dataA = '';
						$dataA = str_replace('"', '', $data['A']);
						$dataA = trim($dataA);

						//@TODO 每个平台不一致
						$sku 		= 	trim($dataA, "'");
						$sellerName = 	trim($data['B'], "'");
						$siteName 	= 	trim($data['C'], "'");
						$configName = 	trim($data['D']);

						//如果仓库名称不为空，判断是否在指定的仓库内，并取出仓库ID
						if($configName){
							$configInfo = EbayProductAdd::getConfigType();
							foreach ($configInfo as $configKey => $configVal) {
								if($configName == $configVal){
									$configType = $configKey;
									break;
								}
							}
						}

						if(empty($sku)){
							continue;
						}

						//判断是否有上传sku的权限
						// if(!Product::model()->checkCurrentUserAccessToSaleSKU($sku, Platform::CODE_EBAY)){
						// 	MHelper::writefilelog('ebayBatchimport'.date('Ymd').'.txt',$sku.' 没有上传SKU的权限'."\r\n");
				  //   		echo $this->failureJson(array('message' => '没有上传SKU的权限'));
			   //  			exit();
			   //  		}

						if(empty($sellerName) && $key==2){
							//MHelper::writefilelog('ebayBatchimport'.date('Ymd').'.txt',$sku.' 销售账号简称为空'."\r\n");
							continue;
						}

						if($sellerName){
							$getSellerName = $sellerName;
						}

						$accountID = isset($sellerUserList[$getSellerName]) ? $sellerUserList[$getSellerName] : '';
						if(empty($accountID)){
							//MHelper::writefilelog('ebayBatchimport'.date('Ymd').'.txt',$sku.' 销售账号ID为空'."\r\n");
							continue;
						}

						if(empty($siteName) && $key==2){
							//MHelper::writefilelog('ebayBatchimport'.date('Ymd').'.txt',$sku.' 销售站点名称为空'."\r\n");							
							continue;
						}

						if($siteName){
							$getSiteName = $siteName;
						}

						$siteId = isset($siteList[$getSiteName]) ? $siteList[$getSiteName] : '';
						if(!is_numeric($siteId) || $siteId < 0){
							//MHelper::writefilelog('ebayBatchimport'.date('Ymd').'.txt',$sku.' 销售站点id不存在'."\r\n");
							continue;
						}

						$skulist = array();
						$skulist[] = $sku;
						$skuInfo = Product::model()->getProductInfoBySku($sku);
						if (!empty($skuInfo) && $skuInfo['product_is_multi'] == Product::PRODUCT_MULTIPLE_MAIN ) {
							//获取产品库中的子SKU列表
							$variationSkuOriList = EbayProductAdd::model()->getSubSKUListPairsByMainProductId($skuInfo['id']);
							$skulist = array_merge($skulist,$variationSkuOriList);
						}

						//@todo 
						//EbayProductImageAdd::addSkuImageUpload($accountID,$skulist);

						$addType = EbayProductAdd::ADD_TYPE_XLSX;
						// if(in_array($siteId, EbaySite::getSpecialLanguageSiteIDs())){
						// 	$addType = 2;	//小语种刊登
						// }
						
						$ebayProductAddModel = EbayProductAdd::model();
						$failure = array();
						$isok = $ebayProductAddModel->addProductByBatch($sku, $accountID, $siteId, $duration, $listingType, $auctionStatus, $auctionPlanDay, $auctionRule, $configType, $addType);
						if (!$isok) {
							$failure[] = $sku.'-----'.$ebayProductAddModel->getErrorMessage()."<br>";
						}
						if (!empty($failure)) {
							//MHelper::writefilelog("ebayBatchimport-err".date('Ymd').".log", 'failure sku: '.implode(' ', $failure)."<br>");
						}
					}
				}

				echo $this->successJson(array('message'=>'success'));
				Yii::app()->end();

			}catch (Exception $e){
				echo $this->failureJson(array('message'=>$e->getMessage()));
				Yii::app()->end();
			}
		}
		
		$this->render("upload", array('duration'=>$duration));
		exit;
	}

	/**
	 * 海外仓批量导入 2017-03-23
	 */
	public function actionOverseaImport(){
		$duration = Yii::app()->request->getParam('listing_duration', 'GTC');
		if($_POST){
			set_time_limit(3600);
			ini_set('display_errors', true);
			ini_set('memory_limit', '256M');

			try{
				if(empty($_FILES['csvfilename']['tmp_name'])){
					throw new Exception("文件上传失败");
				}
				if($_FILES['csvfilename']['error'] != UPLOAD_ERR_OK){
					throw new Exception("文件上传失败, error:".$_FILES['csvfilename']['error']);
				}
				//限制下文件大小
				if($_FILES['csvfilename']['size'] > 2048000){
					echo $this->failureJson(array('message'=>"文件太大，在2M以下"));
					exit();
				}
				$file = $_FILES['csvfilename']['tmp_name'];


				$PHPExcel = new MyExcel();
				//excel处理
				Yii::import('application.vendors.MyExcel');
				$datas = $PHPExcel->get_excel_con($file);

				if(!empty($datas)){

					$sellerUserList = EbayAccount::model()->getIdNamePairs();
					$sellerUserList = array_flip($sellerUserList);
					$getSellerName = '';

					$siteList = EbaySite::model()->getSiteList();
					$siteList = array_flip($siteList);

					$getSiteName = '';

					//添加刊登任务
					$duration 		= Yii::app()->request->getParam('listing_duration', 'GTC');
					$listingType 	= Yii::app()->request->getParam('listing_type', EbayProductAdd::LISTING_TYPE_FIXEDPRICE);
					$auctionStatus 	= Yii::app()->request->getParam('auction_status', 0);
					$auctionPlanDay = Yii::app()->request->getParam('auction_plan_day', 10);
					$auctionRule 	= Yii::app()->request->getParam('auction_rule', 0);
					$configType 	= '';

					foreach ($datas as $key=>$data){
						if($key == 1) continue;
						$dataA = '';
						$dataA = str_replace('"', '', $data['A']);
						$dataA = trim($dataA);

						//@TODO 每个平台不一致
						$sku 		= 	trim($dataA, "'");
						$sonSku 	= 	trim(str_replace('"', '', $data['B']), "'");
						$sellerName = 	trim($data['C'], "'");
						$siteName 	= 	trim($data['D'], "'");
						$configName = 	trim($data['E']);

						//如果仓库名称不为空，判断是否在指定的仓库内，并取出仓库ID
						if($configName){
							$configInfo = EbayProductAdd::getConfigType();
							foreach ($configInfo as $configKey => $configVal) {
								if($configName == $configVal){
									$configType = $configKey;
									break;
								}
							}
						}

						if(empty($sku)){
							continue;
						}

						$sonSkuList = array();//子sku列表
						if(!empty($sonSku)){
							$sonSkuList = explode(',',$sonSku);
							//判断是否是主sku的子sku,有一个不是都跳过
							$skuInfo = Product::model()->getProductInfoBySku($sku);
							$variationSkuOriList = EbayProductAdd::model()->getSubSKUListPairsByMainProductId($skuInfo['id']);
							$flag = true;
							foreach($sonSkuList as $v){
								if(!in_array($v,$variationSkuOriList)){
									$flag = false;
									break;
								}
							}
							if($flag == false){
								continue;
							}
						}

						//判断是否有上传sku的权限
						// if(!Product::model()->checkCurrentUserAccessToSaleSKU($sku, Platform::CODE_EBAY)){
						// 	MHelper::writefilelog('ebayBatchimport'.date('Ymd').'.txt',$sku.' 没有上传SKU的权限'."\r\n");
						//   		echo $this->failureJson(array('message' => '没有上传SKU的权限'));
						//  			exit();
						//  		}

						if(empty($sellerName) && $key==2){
							//MHelper::writefilelog('ebayBatchimport'.date('Ymd').'.txt',$sku.' 销售账号简称为空'."\r\n");
							continue;
						}

						if($sellerName){
							$getSellerName = $sellerName;
						}

						$accountID = isset($sellerUserList[$getSellerName]) ? $sellerUserList[$getSellerName] : '';
						if(empty($accountID)){
							//MHelper::writefilelog('ebayBatchimport'.date('Ymd').'.txt',$sku.' 销售账号ID为空'."\r\n");
							continue;
						}

						if(empty($siteName) && $key==2){
							//MHelper::writefilelog('ebayBatchimport'.date('Ymd').'.txt',$sku.' 销售站点名称为空'."\r\n");
							continue;
						}

						if($siteName){
							$getSiteName = $siteName;
						}

						$siteId = isset($siteList[$getSiteName]) ? $siteList[$getSiteName] : '';
						if(!is_numeric($siteId) || $siteId < 0){
							//MHelper::writefilelog('ebayBatchimport'.date('Ymd').'.txt',$sku.' 销售站点id不存在'."\r\n");
							continue;
						}

						//@todo
						//EbayProductImageAdd::addSkuImageUpload($accountID,$skulist);

						$addType = EbayProductAdd::ADD_TYPE_XLSX;
						// if(in_array($siteId, EbaySite::getSpecialLanguageSiteIDs())){
						// 	$addType = 2;	//小语种刊登
						// }

						$ebayProductAddModel = EbayProductAdd::model();
						$failure = array();
						$isok = $ebayProductAddModel->addProductByBatch($sku, $accountID, $siteId, $duration, $listingType, $auctionStatus, $auctionPlanDay, $auctionRule, $configType, $addType,$sonSkuList);
						if (!$isok) {
							$failure[] = $sku.'-----'.$ebayProductAddModel->getErrorMessage()."<br>";
						}
						if (!empty($failure)) {
							//MHelper::writefilelog("ebayBatchimport-err".date('Ymd').".log", 'failure sku: '.implode(' ', $failure)."<br>");
						}
					}
				}

				echo $this->successJson(array('message'=>'success'));
				Yii::app()->end();

			}catch (Exception $e){
				echo $this->failureJson(array('message'=>$e->getMessage()));
				Yii::app()->end();
			}
		}

		$this->render("upload", array('duration'=>$duration,'is_oversea'=>1));
		exit;
	}

	/**
	 * 修复图片问题
	 * @link /ebay/ebayproductstatistic/repairimg/sku/119244/account_id/29/site_id/3/type/1
	 */
	public function actionRepairImg(){
		set_time_limit(0);
		$sku = trim(Yii::app()->request->getParam('sku'));
		$account_id = trim(Yii::app()->request->getParam('account_id'));
		$site_id = intval(trim(Yii::app()->request->getParam('site_id')));
		$type = trim(Yii::app()->request->getParam('type',1));

		if(!$sku || !$account_id || !$type){
			Yii::app()->end('参数不齐全');
		}

		$condition = "platform_code = 'EB' and `type` = {$type} and account_id = {$account_id}  and site_id = {$site_id} and sku = {$sku}";//条件
		$list = EbayProductImageAdd::model()->findAll($condition);

		if($list){
			$imgData = array(
				'upload_status'=>0,
				'remote_path'=>NULL
			);
			foreach($list as $info){
				//更新
				$res = EbayProductImageAdd::model()->getDbConnection()->createCommand()->update(EbayProductImageAdd::model()->tableName(), $imgData, 'id='.$info['id']);
				if($res){
					echo $info['id'].'success <br>';
				}else{
					echo $info['id'].'fail <br>';
				}
			}
		}
	}
}