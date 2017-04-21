<?php
/**
 * @desc 用来做Wish请求接口使用
 * @author lihy
 *
 */
class WishapirequestController extends UebController{
	/**
	 * @desc 设置访问权限
	 * @see CController::accessRules()
	 */
	public function accessRules(){
		return array(
				array(
						'allow',
						'users'=>'*',
						'actions'=>array('getallproduct', 'getlisting')
					),
				
		);
	}

	public function actionCreateencrysku(){
		$sku = Yii::app()->request->getParam('sku');
		$encrySku = new encryptSku();
		$ensku = $encrySku->getEncryptSku($sku);
		echo "sku:",$sku,"<br/>";
		echo "Ensku:",$ensku,"<br/>";
	}
	/**
	 * @desc 获取单个产品信息
	 * @link /wish/wishapirequest/getproduct/sku/xx/account_id/xx
	 */
	public function actionGetproduct(){
		set_time_limit(3600);
		$sku = Yii::app()->request->getParam('sku');
		$accountID = Yii::app()->request->getParam('account_id');
		if($sku && $accountID){
			$retrieveProductsRequest = new RetrieveProductsRequest();
			$response = $retrieveProductsRequest->setParentSku($sku)->setAccount($accountID)
									->setRequest()
									->sendRequest()
									->getResponse();
			echo "Product:<br/>";
			echo "<pre>";
			print_r($response);
			echo "</pre>";
			if ($retrieveProductsRequest->getIfSuccess() && !empty($response)) {
					$datas = $response->data;
					$datas = array($datas);
					$wishListing = new WishListing();
					$wishListing->setAccountID($accountID);
					$wishListing->saveWishListing($datas);
					echo $wishListing->getExceptionMessage();
					echo "get sku:{$sku} success";
			}else{
				echo $retrieveProductsRequest->getErrorMsg() , '<br/>';
			}
		}else{
			echo "Sku OR Account ID invalid";
		}
		
	}

	/**
	 * @desc 拉取产品列表
	 * @link  /wish/Wishapirequest/getlisting/account_id/19 
	 */
	// public function actionGetlisting() {
	// 	set_time_limit(3600);
 //        ini_set('memory_limit','2048M');
	// 	//ini_set('display_errors', true);
	// 	//error_reporting(E_ALL);

	// 	$accountID = trim(Yii::app()->request->getParam('account_id',''));

	// 	// $request = new ListAllProductsRequest();
	// 	// $request->setAccount($accountID);
	// 	// $request->setStartIndex($index);
	// 	// $request->setLimit(500);
	// 	// $response = $request->setRequest()->sendRequest()->getResponse();
	// 	// MHelper::writefilelog('wish.log',print_r($response,true));
	// 	// die('ok');

	// 	if ($accountID) {
	// 		//创建日志
	// 		$logtxt  = '';
	// 		$wishLog = new WishLog();
	// 		$logID = $wishLog->prepareLog($accountID, WishListing::EVENT_NAME);
	// 		if ($logID) {
	// 			//检查账号是否可以拉取
	// 			$checkRunning = $wishLog->checkRunning($accountID, WishListing::EVENT_NAME);
	// 			if (!$checkRunning) {
	// 				$wishLog->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
	// 				echo 'There Exists An Active Event';
	// 			} else {
	// 				//插入本次log参数日志(用来记录请求的参数)
	// 				$time = date('Y-m-d H:i:s');
	// 				$eventLogID = $wishLog->saveEventLog(WishListing::EVENT_NAME, array(
	// 						'log_id' => $logID,
	// 						'account_id' => $accountID,
	// 						'start_time'    => $time,
	// 						'end_time'      => $time,
	// 				));
	// 				//设置日志正在运行
	// 				$wishLog->setRunning($logID);
					
	// 				$hasflish = false;
	// 				$index = 0;
	// 				$total = 0;
	// 				//$request->setLimit(30);
	// 				while (!$hasflish) {
	// 					$request = new ListAllProductsRequest();
	// 					$request->setAccount($accountID);
	// 					$request->setStartIndex($index);
	// 					$request->setLimit(20);
	// 					$index++;
	// 					$response = $request->setRequest()->sendRequest()->getResponse();
	// 					MHelper::printvar($response,false);//for test
	// 					if ($request->getIfSuccess() && !empty($response)) {
	// 						$datas = $response->data;
	// 						$wishListing = new WishListing();
	// 						$wishListing->setAccountID($accountID);
	// 						$wishListing->saveWishListing($datas);
	// 						$total += count($datas);
	// 						unset($datas);
	// 						if (!isset($response->paging->next) || empty($response->paging->next)){
	// 							$hasflish = true;
	// 						}
	// 					} else {
	// 						$hasflish = true;
	// 						echo $request->getErrorMsg() , '<br/>';
	// 						$logtxt .= $request->getErrorMsg()."\r\n";
	// 					}
	// 					unset($response);
	// 				}
	// 				$wishLog->setSuccess($logID);
	// 				$wishLog->saveEventStatus(WishListing::EVENT_NAME, $eventLogID, WishLog::STATUS_SUCCESS);
	// 				echo "pull num:{$total} ","AccountId:{$accountID} finish";
	// 			}
	// 		}
	// 	} else {
	// 		//循环每个账号发送一个拉listing的请求
	// 		$accountList = WishAccount::model()->getCronGroupAccounts();
	// 		foreach ($accountList as $accountID) {
	// 			$url = Yii::app()->request->hostInfo.'/' . $this->route . '/account_id/' . $accountID;
	// 			echo $url."<br>";
	// 			MHelper::runThreadBySocket ( $url );
	// 		}
	// 	}
	// 	Yii::app()->end('finish');
	// }

	/**
	 * @desc 获取所有产品列表
	 */
	// public function actionGetallproduct(){
	// 	set_time_limit(3600);
	// 	ini_set('display_errors', true);
	// 	error_reporting(E_ALL);
	// 	$accountID = Yii::app()->request->getParam('account_id');
	// 	if ($accountID) {
	// 		$hasflish = false;
	// 		$index = 0;
	// 		//$request->setLimit(30);
	// 		while (!$hasflish) {
	// 			$request = new ListAllProductsRequest();
	// 			$request->setAccount($accountID);
	// 			$request->setStartIndex($index);
	// 			$index++;
	// 			$response = $request->setRequest()->sendRequest()->getResponse();
			
	// 			if (!empty($response)) {
	// 				$datas = $response->data;
	// 				$wishListing = new WishListing();
	// 				$wishListing->setAccountID($accountID);
	// 				$wishListing->saveWishListing($datas);
	// 				if (!isset($response->paging->next) || empty($response->paging->next))
	// 					$hasflish = true;
	// 			} else {
	// 				$hasflish = true;
	// 			}
	// 			unset($response);
	// 		}
	// 	} else {
	// 		$accountList = WishAccount::getAbleAccountList();
	// 		//循环每个账号发送一个拉listing的请求
	// 		foreach ($accountList as $accountInfo) {
	// 			$path = $this->createUrl('getlisting', array('account_id' => $accountInfo['id']));
	// 			$header = "GET " . $path . $accountInfo['id'] . " HTTP\1.0\r\n";
	// 			$header .= "Host: " . $_SERVER['HTTP_HOST'] . "\r\n";
	// 			$header .= "Pragma: no-cache" . "\r\n";
	// 			$header .= "Connection: Close\r\n\r\n";
	// 			$fp = fsockopen($_SERVER['HTTP_HOST'], 80, $errno, $error, 300);
	// 			if ($fp) {
	// 				fwrite($fp, $header, strlen($header));
	// 			}
	// 			while (!feof($fp)) {
	// 				echo fgets($fp, 1024);
	// 			}
	// 			fclose($fp);
	// 		}
	// 	}
	// }
	
	/**
	 * @desc 获取所有的产品变种列表
	 */
	public function actionGetallproductvariant(){
		
	}
	
	
	public function actionRefreshaccounttoken(){
		$accountId = Yii::app()->request->getParam('account_id');
		if($accountId){
			$accountList = array(array('id'=>$accountId, 'account_name'=>'xx'));
		}else{
			$accountList = WishAccount::getAbleAccountList();
		}
		$wishAccount = new WishAccount();
		$refreshTokenRequest = new RefreshTokenRequest();
		foreach ($accountList as $account){
			$refreshTokenRequest->setAccount($account['id']);
			$response = $refreshTokenRequest->setRequest()->sendRequest()->getResponse();
			if($refreshTokenRequest->getIfSuccess()){
				//更新
				$updata = array('access_token'=>$response->data->access_token,
								'refresh_token'=>$response->data->refresh_token,
								'token_expired_time'=>$response->data->expiry_time
								);
				if($wishAccount->updateByPk($account['id'], $updata)){
					echo "{$account['account_name']} refresh success\n<br/>";
				}
			}else{
				echo $refreshTokenRequest->getErrorMsg(), '<br/>';
				echo "fail:{$account['account_name']}\n<br/>";
			}
		}
	}
	
	
	/**
	 * @desc 上传产品
	 * @throws Exception
	 */
	public function actionUploadproduct(){
		set_time_limit(5*3600);
		error_reporting(E_ALL);
		ini_set('display_errors', true);
		$limit = Yii::app()->request->getParam("limit");
		$type = Yii::app()->request->getParam("type");
		if(empty($limit)) $limit = 100;
		try{
			$wishProductAddModel = new WishProductAdd;
			//if($type == 1){
				//获取未成功上传和上传次数小于10的主sku
				$pendingUploadProducts = $wishProductAddModel->getPendingUploadProduct($limit, "id,account_id");
			/* }else{
				//===== 临时处理 =======
				
				$accountList = WishAccount::model()->getCronGroupAccounts();
				$pendingUploadProducts = array();
				$maxUploadTimes = 5;
				$findUploadStatus = WishProductAdd::WISH_UPLOAD_PENDING. ',' . WishProductAdd::WISH_UPLOAD_FAIL;
				foreach ($accountList as $accountID) {
					$pendingList = $wishProductAddModel->getDbConnection()
					->createCommand()
					->select("id,account_id")
					->from($wishProductAddModel->tableName())
					->where('account_id='.$accountID.' and upload_status in('. $findUploadStatus .') AND upload_times<'.$maxUploadTimes)
					->order('id desc')
					->limit($limit)->queryAll();
					if(!empty($pendingList)){
						$pendingUploadProducts = array_merge($pendingUploadProducts, $pendingList);
					}
				}
				//===== 临时处理 =======
			} */
			
			
			if(empty($pendingUploadProducts))
				throw new Exception('No data upload');
			$newUploadProducts = array();
			foreach ($pendingUploadProducts as $product){
				$newUploadProducts[$product['account_id']][] = $product;
			}
			$total = count($pendingUploadProducts);
			unset($pendingUploadProducts);
			$successNum = 0;
			//print_r($newUploadProducts);exit;
			foreach ($newUploadProducts as $accountId=>$product){
				$createProductRequest = new CreateProductRequest;
				$wishLog = new WishLog;
				//创建运行日志
				$logId = $wishLog->prepareLog($accountId,  WishProductAdd::EVENT_UPLOAD_PRODUCT);
				if(!$logId) continue;
				//检查账号是可以提交请求报告
				$checkRunning = $wishLog->checkRunning($accountId, WishProductAdd::EVENT_UPLOAD_PRODUCT);
				if(!$checkRunning){
					$wishLog->setFailure($logId, Yii::t('systems', 'There Exists An Active Event'));
					continue;
				}
				//插入本次log参数日志(用来记录请求的参数)
				$eventLog = $wishLog->saveEventLog(WishProductAdd::EVENT_UPLOAD_PRODUCT, array(
						'log_id'        => $logId,
						'account_id'    => $accountId,
						'start_time'    => date('Y-m-d H:i:s'),
						'end_time'      => date('Y-m-d H:i:s'),
				));
				//设置日志为正在运行
				$wishLog->setRunning($logId);
				foreach ($product as $data){
					$res = $wishProductAddModel->uploadProduct($data['id']);
					if($res){
						$successNum++;
					}
				}
				$wishLog->setSuccess($logId);
				$wishLog->saveEventStatus(WishProductAdd::EVENT_UPLOAD_PRODUCT, $eventLog, WishLog::STATUS_SUCCESS);
			}
			if($successNum > 0){
				echo "total:$total, success: $successNum";
			}else 
				throw new Exception('All item Failure!');
		}catch (Exception $e){
			echo "failure:".$e->getMessage();
		}
	}
	
	/**
	 * @desc 上传变种产品
	 */
	public function actionUploadproductvariant(){
		exit;
		set_time_limit(5*3600);
		ini_set('display_errors', true);
		$limit = 10;
		try{
			$wishProductVariantAddModel = new WishProductVariantsAdd;
			
			$wishProductAddModel = new WishProductAdd;
			$pendingUploadVariants = $wishProductVariantAddModel->getPendingUploadVariants($limit, "id");
			$createProductVariantRequest = new CreateProductVariantRequest();
			if(empty($pendingUploadVariants))
				throw new Exception('No pending upload');
			$productInfo = array();
			$successNum = 0;
			$total = count($pendingUploadVariants);
			foreach ($pendingUploadVariants as $variant){
				$variant = $wishProductVariantAddModel->getWishProductVariantsAddInfo("id=:id AND upload_status<>:upload_status", array(":id"=>$variant['id'], ":upload_status"=>WishProductAdd::WISH_UPLOAD_SUCCESS));
				if(empty($variant)) continue;
				try{
					$time = date("Y-m-d H:i:s");
					$logId = 0;
					//获取父级数据
					if(!isset($productInfo[$variant['add_id']])){
						$productInfo[$variant['add_id']] = $wishProductAddModel->getProductAddInfo('id=:id', array(':id'=>$variant['add_id']));
					}
					$parentSkuInfo = $productInfo[$variant['add_id']];
					if(empty($parentSkuInfo)){
						throw new Exception('Parent sku no exists');
					}
					//检测父级是否已经上传成功
					if($parentSkuInfo['upload_status'] != WishProductAdd::WISH_UPLOAD_SUCCESS){
						continue;
						//throw new Exception('Parent sku no upload success');
					}
					$wishLog = new WishLog;
					$accountId = $parentSkuInfo['account_id'];
					//1.检查账号是可以提交请求报告
					$checkRunning = $wishLog->checkRunning($accountId, WishProductAdd::EVENT_UPLOAD_PRODUCT);
					if(!$checkRunning){
						continue;
					}
					//创建运行日志
					$logId = $wishLog->prepareLog($accountId,  WishProductAdd::EVENT_UPLOAD_PRODUCT);
					if(!$logId) continue;
					//插入本次log参数日志(用来记录请求的参数)
					$eventLog = $wishLog->saveEventLog(WishProductAdd::EVENT_UPLOAD_PRODUCT, array(
							'log_id'        => $logId,
							'account_id'    => $accountId,
							'start_time'    => date('Y-m-d H:i:s'),
							'end_time'      => date('Y-m-d H:i:s'),
					));
					//设置日志为正在运行
					$wishLog->setRunning($logId);
					
					$createProductVariantRequest->setAccount($accountId);
					$data = array(
							'parent_sku'	=>	$parentSkuInfo['online_sku'],
							'sku'			=>	$variant['online_sku'],
							'inventory'		=>	$variant['inventory'],
							'price'			=>	$variant['price'],
							'shipping'		=>	$variant['shipping'],
							'msrp'			=>	$variant['msrp'],
							'size'			=>	$variant['size'],
							'color'			=>	$variant['color'],
							'shipping_time'	=>	$variant['shipping_time']
							//'main_image'	=>	$variant['main_image']
					);
					if($variant['main_image'] && $variant['remote_main_img']){
						$data['main_image'] = $variant['remote_main_img'];
					}elseif($variant['main_image']){
						//$remoteImgUrl = $wishProductAddModel->uploadImageToServer($variant['main_image'], $accountId);
						$remoteImgUrl = $wishProductAddModel->getRemoteImgPathByName($variant['main_image'], $accountId);
						if(!$remoteImgUrl){
							throw new Exception($wishProductAddModel->getErrorMsg(), WishProductAdd::WISH_UPLOAD_IMG_FAIL);
						}
						$wishProductVariantsAddModel->updateProductVariantAddInfoByPk($variant['id'], array(
								'remote_main_img'=>$remoteImgUrl
						));
						$data['main_image'] = $remoteImgUrl;
					}else{
						$images = Product::model()->getImgList($variant['sku'], 'ft');
						if($images){
							$imgname = array_shift($images);
							$basefilename = basename($imgname);
							if(strtolower($basefilename) == $variant['sku'].".jpg" && count($images)>1){
								$imgname = array_shift($images);
							}
							$remoteImgUrl = (string)$wishProductAddModel->getRemoteImgPathByName($imgname, $accountId, $variant['sku']);
							if(!$remoteImgUrl){
								throw new Exception($variant['sku'].":".$wishProductAddModel->getErrorMsg(), WishProductAdd::WISH_UPLOAD_IMG_FAIL);
							}
							$wishProductVariantsAddModel->updateProductVariantAddInfoByPk($variant['id'], array(
									'remote_main_img'=>$remoteImgUrl
							));
							$data['main_image'] = $remoteImgUrl;
						}
					}
					$createProductVariantRequest->setUploadData($data);
					$response = $createProductVariantRequest->setRequest()->sendRequest()->getResponse();
					if($createProductVariantRequest->getIfSuccess()){
						$successNum++;
						$wishProductVariantAddModel->updateProductVariantAddInfoByPk($variant['id'], array(
								'last_upload_time'=>$time,
								'upload_status'=>WishProductAdd::WISH_UPLOAD_SUCCESS,
								'upload_times'=>1+$variant['upload_times'],
								'last_upload_msg'=>'success',
								'wish_variant_product_id'	=>	isset($response->data->Variant->id) ? (string)$response->data->Variant->id : ''
						));
						
						$wishLog->setSuccess($logId);
						$wishLog->saveEventStatus(WishProductAdd::EVENT_UPLOAD_PRODUCT, $eventLog, WishLog::STATUS_SUCCESS);
					}else{
						throw new Exception($createProductVariantRequest->getErrorMsg());
					}
				}catch (Exception $e){
					$uploadStatus = ($e->getCode() == WishProductAdd::WISH_UPLOAD_IMG_FAIL ? WishProductAdd::WISH_UPLOAD_IMG_FAIL : WishProductAdd::WISH_UPLOAD_FAIL);
					if($logId>0){
						$wishLog->setFailure($logId, $e->getMessage());
						$wishLog->saveEventStatus(WishProductAdd::EVENT_UPLOAD_PRODUCT, $eventLog, WishLog::STATUS_FAILURE);
					}
					$uploadTimes = 1+$variant['upload_times'];
					/* if($uploadStatus == WishProductAdd::WISH_UPLOAD_IMG_FAIL){
						$uploadTimes = $variant['upload_times'];
					} */
					$wishProductVariantAddModel->updateProductVariantAddInfoByPk($variant['id'], array(
							'last_upload_time'=>$time,
							'upload_status'=>$uploadStatus,
							'upload_times'=>$uploadTimes,
							'last_upload_msg'=>$e->getMessage()
					));
				}
			}
			echo "total:",$total,"success:",$successNum;
		}catch (Exception $e){
			echo 'Failure:',$e->getMessage();
		}
	}
	
	
	/**
	 * @desc 检测仓库中的sku库存数量，从而自动更改平台上的库存数量为0
	 *       方式一：以erp产品表为主，循环取出去对比仓库库存
	 *       方式二：以仓库库存表为主，批量循环取出小于1的sku，再取出对应的产品表中的相关信息，更新在线产品库存
	 * @link /wish/wishapirequest/autochangestockfornostock
	 */
	public function actionAutochangestockfornostock(){
		//设置测试环境运行程序
		$loopNum = 0;
		$testFlag = false;//是否为测试标示
		$runType = Yii::app()->request->getParam("runtype");
		$testSKUs = Yii::app()->request->getParam("sku");
		$testAccountID = Yii::app()->request->getParam("account_id");
		$testSkuList = array();
		//测试环境下必须指定sku和账号
		if($runType != "y" && (empty($testSKUs) || empty($testAccountID))){
			exit("测试下必须指定sku列表和账号，多个sku之间用半角,隔开。示例：{$this->route}/account_id/1/sku/1123,22444,3434.09");
		}elseif ($runType != "y"){
			$testFlag = true;
			$testSkuList = explode(",", $testSKUs);
		}
		
		set_time_limit(5*3600);
		error_reporting(E_ALL);
		ini_set("display_errors", true);
		$allowWarehouse =  WarehouseSkuMap::WARE_HOUSE_GM;
		$conditions = "available_qty <= 1 AND warehouse_id in(".$allowWarehouse.")";
		$params = array();
		$limit =2000;
		$offset = 0;
		$wareSkuMapModel = new WarehouseSkuMap();
		$wishProductModel = new WishProduct();
		$wishProductVariantModel = new WishVariants();
		$wishZeroStockSKUModel = new WishZeroStockSku();
		
		
		//-- start 2016-02-01 增加type --
		$type = Yii::app()->request->getParam("type");
		if(empty($type)) $type = 0;
		 
		switch ($type){
			case 0://默认，库存《=1库存清零
				//$conditions = "t.available_qty <= 1 AND t.warehouse_id in(".$allowWarehouse.") AND p.product_status<7"; //lihy modify 2016-02-14
				$beforeTime = date("Y-m-d H:i:s", time()-45*24*3600);
				$conditions = "t.available_qty < 1 AND t.warehouse_id in(".$allowWarehouse.") AND p.product_bak_days>14 AND p.create_time<='{$beforeTime}'"; //lihy modify 2016-10-14
				$SkuModel = new WarehouseSkuMap();
				$method = "getSkuListLeftJoinProductByCondition";
				$select = "t.sku";
				break;
			/* case 1:
				$productStatus = Product::STATUS_HAS_UNSALABLE . "," . Product::STATUS_WAIT_CLEARANCE;
				$conditions = "product_status in(". $productStatus .") and product_is_multi in (0, 1)";
				$SkuModel = new Product();
				$method = "getProductListByCondition";
				$select = "sku";
				break;
			case 2:
				$SkuModel = new Order();
				$method = "getOweWaitingConfirmOrdersSkuListByCondition";
				$conditions = null;
				$params = array();
				$select = "sku";
				break;
			//2016-02-03 add
			case 5://手动导入的sku来源
				$SkuModel = new ProductImportSku();
				$method = "getSkuListByCondition";
				$conditions = "wish_status=0";
				$params = array();
				$select = "sku";
				break; */
			default:
				exit('type is incorrect');
		}
		//-- end 2016-02-01 增加type --
		
		do{
			//1、循环取出<=1的sku列表，每次100个
			//2、取出上述sku对应的产品库中的信息
			//3、提交到ebay平台，实现在线库存修改
			if(!$testFlag){
				$limits = "{$offset}, {$limit}";
				$skuList = $SkuModel->$method($conditions, $params, $limits, $select);
				$offset += $limit;
			}else{
				if($loopNum > 0){
					exit("测试运行结束");
				}
				$skuList = array();
    			foreach ($testSkuList as $sku){
    				$skuList[] = array('sku'=>$sku);
    			}
				$loopNum++;
				echo "set testSkulist=". implode(",", $testSkuList) . "<br/>";
			}
			if($skuList){
				$flag = true;
				$skus = array();$productListing = $variantListing = array();
				foreach ($skuList as $sku){
					$skus[] = $sku['sku'];
				}
				unset($skuList);
				$command = $wishProductVariantModel->getDbConnection()->createCommand()
										->from($wishProductVariantModel->tableName() ." AS t" )
										->leftJoin($wishProductModel->tableName() . " AS p", "p.id=t.listing_id")
										->select("t.sku, t.account_id, t.online_sku, t.inventory as product_stock, t.variation_product_id, p.product_id")
										->where(array("IN", "t.sku", $skus))
										->andWhere("p.is_promoted=0")
										->andWhere("t.enabled=1") //add lihy 20160213
										->andWhere("t.inventory>0");
				if($testAccountID){
					echo "set testaccount_id=".$testAccountID . "<br/>";
					$command->andWhere("t.account_id=".$testAccountID);
				}
				$variantListing = $command->queryAll();
				if($testFlag){
					echo "<br/>======variantListing======<br/>";
					print_r($variantListing);
				}
				//查找出对应的父级sku信息，聚合同属一个产品的信息
				$listing = array();
				$updateSKUS = $skus;//2016-02-03 add
				if($variantListing){
					foreach ($variantListing as $variant){
						//检测当天是否已经运行了，此表，加此判断之后数据量会降很多，并且会定时进行清理记录数据 lihy add 2016-02-14
						if($wishZeroStockSKUModel->checkHadRunningForDay($variant['online_sku'], $variant['account_id'])){
							continue;
						}
						//检测是否海外仓
						if(WishOverseasWarehouse::model()->getWarehouseInfoByProductID($variant['product_id'])){
							continue;
						}
						$listing[$variant['account_id']][] = $variant;
						/* if(!isset($updateSKUS[$variant['sku']]))//2016-02-03 add
							$updateSKUS[$variant['sku']] = $variant['sku']; */
						
					}
				}
				if($testFlag){
					echo "<br/>=======listing=====<br/>";
					print_r($listing);
				}
				if($listing){
					//print_r($listing);
					$eventName = WishZeroStockSku::EVENT_ZERO_STOCK;
					foreach ($listing as $accountID=>$lists){
						
						$time = date("Y-m-d H:i:s");
						//写log
						$logModel = new WishLog();
						$logID = $logModel->prepareLog($accountID, $eventName);
						if(!$logID){
							if($testFlag){
								echo "<br/>create logid fail<br/>";
							}
							continue;
						}
						//检测是否可以允许
						if(!$logModel->checkRunning($accountID, $eventName)){
							$logModel->setFailure($logID, Yii::t('system', 'There Exists An Active Event'));
							if($testFlag){
								echo "<br/>There Exists An Active Event<br/>";
							}
							continue;
						}
						$startTime = date("Y-m-d H:i:s");
						//设置运行
						$logModel->setRunning($logID);
						//@TODO
						$updateInventoryRequest = new UpdateInventoryRequest;
						//@todo test
						//$updateInventoryRequest = new UpdateProductVariationRequest;
						$updateInventoryRequest->setAccount($accountID);
						$msg = "";
						foreach ($lists as $list){
							if($testFlag){
								echo "<br/>========list==========<br/>";
								var_dump($list);
							}
							$updateInventoryRequest->setSku($list['online_sku']);
							//$updateInventoryRequest->setInventory(0);
							//因wish平台产品库存为0会自动将产品下架，固将线上数量修改为1 liht 20160206
							$updateInventoryRequest->setInventory(0);
							//@todo test
							$updateInventoryRequest->setEnabled(true);
							$response = $updateInventoryRequest->setRequest()->sendRequest()->getResponse();
							$message = "";
							if($updateInventoryRequest->getIfSuccess()){
								$status = 2;//成功
							}else{
								$status = 3;//失败
								$message = " accountID:{$accountID},  sku: {$list['online_sku']}" . $updateInventoryRequest->getErrorMsg();
								$msg .= $message;
							}
							//写记录
							$addData = array(
									'product_id'=>	$list['variation_product_id'],
									'seller_sku'=>	$list['online_sku'],
									'sku'		=>	$list['sku'],
									'account_id'=>	$accountID,
									'site_id'	=>	'0',
									'old_quantity'=>$list['product_stock'],
									'status'	=>	$status,
									'msg'		=>	$message,
									'create_time'=>	date("Y-m-d H:i:s"),
									'type'		=>	$type,
									'restore_num'=>0
							);
							$wishZeroStockSKUModel->saveData($addData);
						}
						//日志完成
						$eventLogID = $logModel->saveEventLog($eventName, array(
								'log_id'	=>	$logID,
								'account_id'=>	$accountID,
								'start_time'=>	$startTime,
								'end_time'	=>	date("Y-m-d H:i:s")) );
						$logModel->setSuccess($logID, $msg);
						$logModel->saveEventStatus($eventName, $eventLogID, WishLog::STATUS_SUCCESS);
					}
					//2016-02-03 add
					//如果为手动导入的则还需要更新
					if($type == 5 && $updateSKUS){
						ProductImportSku::model()->updateDataByCondition("wish_status=0 AND sku in(". MHelper::simplode($updateSKUS) .")", array('wish_status'=>1));
					}
					unset($listing, $lists);
				}else{
    				echo("no match sku ");
    			}
    		}else{ 
    			$flag = false;
    			exit('not found stock less than 0');
    		}
		}while ($flag);
	}
	
	/**
	 * @desc 自动更改库存通过lisitng	定时任务：wish国内仓自动改库存为0，按销售要求目前停止执行 20170303|Liz
	 * @link /wish/wishapirequest/autochangestockbylisting/account_id/xx/sku/xxx/limit/xx/bug/1/norun/1
	 */
	public function actionAutochangestockbylisting(){

		Yii::app()->end('按销售要求目前停止执行:20170303');

		set_time_limit(4*3600);
		ini_set('display_errors', true);
		error_reporting(E_ALL);
		
		$accountIDs = Yii::app()->request->getParam('account_id');
		$sku = Yii::app()->request->getParam('sku');
		$limit = Yii::app()->request->getParam('limit');
		$bug = Yii::app()->request->getParam('bug');
		$norun = Yii::app()->request->getParam('norun');
		$type = 0;
		if($accountIDs){
			$accountIDArr = explode(",", $accountIDs);
			$allowWarehouse = "41";
			foreach ($accountIDArr as $accountID){
				$wareSkuMapModel = new WarehouseSkuMap();
				$wishProductModel = new WishProduct();
				$wishProductVariantModel = new WishVariants();
				$wishZeroStockSKUModel = new WishZeroStockSku();
				try{
					$beforeTime = date("Y-m-d H:i:s", time()-90*24*3600);
					$fourFiveDayBeforeTime = date("Y-m-d H:i:s", time()-45*24*3600);
					// $bakDay = 3;	// 2017-02-14修改为3天
					$bakDay = 10;	//备货周期>10天 2017-02-20 
					$nowTime = time();
					if($nowTime<strtotime("2017-02-15 00:00:00")){	//2月6号前可用库存<1, 调0
						$conditions = "t.available_qty < IFNULL(s.day_sale_num,1) AND p.product_status not in (6,7) AND t.warehouse_id in(".$allowWarehouse.")";
						$type = 6;//过年类型
					}else{	//2月6号恢复此规则
						$conditions = "t.available_qty < 1 AND t.warehouse_id in(".$allowWarehouse.") AND p.product_bak_days>{$bakDay} AND (p.create_time<='{$beforeTime}' OR (qe.qe_check_result=1 and qe.qe_check_time<='{$fourFiveDayBeforeTime}'))"; //lihy modify 2016-10-14
					}
					$method = "getSkuListLeftJoinProductAndQERecordByCondition";
					$select = "t.sku";
					if($bug){
						echo "<br>condition:{$conditions}<br/>";
					}
					$logModel = new WishLog();
					$eventName = WishZeroStockSku::EVENT_ZERO_STOCK;
					$logID = $logModel->prepareLog($accountID, $eventName);
					if(!$logID){
						throw new Exception("Create Log ID fail");
					}
				
					if(!$limit)
						$limit = 1000;
					$offset = 0;
					do{
						$command = $wishProductVariantModel->getDbConnection()->createCommand()
						->from($wishProductVariantModel->tableName() ." AS t" )
						->leftJoin($wishProductModel->tableName() . " AS p", "p.id=t.listing_id")
						->select("t.sku, t.account_id, t.online_sku, t.inventory as product_stock, t.variation_product_id, p.product_id")
						->where("t.account_id=".$accountID)
						->andWhere("p.is_promoted=0")
						->andWhere("t.enabled=1") //add lihy 20160213
						->andWhere("t.inventory>0")
						->limit($limit, $offset);
						$offset += $limit;
						if($sku){
							$skus = explode(",", $sku);
							$command->andWhere(array("IN", "t.sku", $skus));
						}
						$variantListing = $command->queryAll();
						if($bug){
							echo "<br/>======variantListing======<br/>";
							print_r($variantListing);
						}
						if($variantListing){
							if($bug){
								$isContinue = false;
							}else{
								$isContinue = true;
							}
							$listing = array();
							foreach ($variantListing as $variant){
								//检测当天是否已经运行了，此表，加此判断之后数据量会降很多，并且会定时进行清理记录数据 lihy add 2016-02-14
								if($wishZeroStockSKUModel->checkHadRunningForDay($variant['online_sku'], $variant['account_id'])){
									continue;
								}
								//检测是否海外仓
								if(WishOverseasWarehouse::model()->getWarehouseInfoByProductID($variant['product_id'])){
									continue;
								}
								$listing[] = $variant;
								/* if(!isset($updateSKUS[$variant['sku']]))//2016-02-03 add
								 $updateSKUS[$variant['sku']] = $variant['sku']; */
									
							}
							unset($variantListing);
							$skuMapArr = array();
							$skuMapList = array();
							if($bug){
								echo "<br/>======Listing======<br/>";
								print_r($listing);
							}
							if(!$listing){
								continue;
							}
							foreach ($listing as $list){
								$skuMapArr[] = $list['sku'];
								$key = $list['variation_product_id']."-".$list['online_sku'];
								$skuMapList[$list['sku']][$key] = $list;
							}
				
							$conditions1 = $conditions;
							$conditions1 .= " AND t.sku in(".MHelper::simplode($skuMapArr).")";
							if($nowTime<strtotime("2017-02-15 00:00:00")){//2月6号前用此规则
								$skuSalesTable = "ueb_sync.ueb_sku_sales";
								$command = $wareSkuMapModel->getDbConnection()->createCommand()
									->from($wareSkuMapModel->tableName() . " as t")
									->leftJoin("ueb_product." . Product::model()->tableName() . " as p", "p.sku=t.sku")
									->leftJoin($skuSalesTable . " as s", "s.sku=t.sku")
									->where($conditions1)
									->select($select)
									->order("t.available_qty asc");
								$skuList = $command->queryAll();
							}else {
								$skuList = $wareSkuMapModel->$method($conditions1, array(), '', $select);
							}
							if($bug){
								echo "<br/>============skuList==========<br/>";
								print_r($skuList);
							}
							if(!$skuList){
								continue;
							}
							$newListing = array();
							foreach ($skuList as $list){
								if(isset($skuMapList[$list['sku']])){
									foreach ($skuMapList[$list['sku']] as $key=>$val){
										$newListing[$key] = $val;
									}
									
								}
							}
							if($bug){
								echo "<br>=========newListing=========<br/>";
								print_r($newListing);
							}
							if(!$newListing){
								continue;
							}
							//@TODO
							$updateInventoryRequest = new UpdateInventoryRequest;
							//@todo test
							//$updateInventoryRequest = new UpdateProductVariationRequest;
							$updateInventoryRequest->setAccount($accountID);
							$msg = "";
							if($bug){
								echo "<br/>=========begin:foreach=========<br/>";
							}
							if($norun){
								echo "<br/>======norun========<br/>";
								continue;//不执行运行
							}
							foreach ($newListing as $list){
								if($bug){
									echo "<br/>========list==========<br/>";
									var_dump($list);
								}
								//获取最新记录
								//获取最新记录,并且在7天之内的
								$lastCreateTime = date("Y-m-d H:i:s", time()-7*24*3600);
								$lastRecord = $wishZeroStockSKUModel->getLastOneByCondition("product_id=:product_id and seller_sku=:seller_sku and account_id=:account_id and is_restore=:is_restore and status=:status and type=:type and create_time>=:create_time", array(
									':product_id'=>$list['variation_product_id'], ':seller_sku'=>$list['online_sku'], ':account_id'=>$accountID, ':is_restore'=>0, ':status'=>2, ':type'=>$type, ':create_time'=>$lastCreateTime
								));
								if($lastRecord){
									continue;
								}
								$updateInventoryRequest->setSku($list['online_sku']);
								//$updateInventoryRequest->setInventory(0);
								//因wish平台产品库存为0会自动将产品下架，固将线上数量修改为1 liht 20160206
								$updateInventoryRequest->setInventory(0);
								//@todo test
								$updateInventoryRequest->setEnabled(true);
								$response = $updateInventoryRequest->setRequest()->sendRequest()->getResponse();
								$message = "";
								if($updateInventoryRequest->getIfSuccess()){
									$status = 2;//成功
								}else{
									$status = 3;//失败
									$message = " accountID:{$accountID},  sku: {$list['online_sku']}" . $updateInventoryRequest->getErrorMsg();
									$msg .= $message;
								}
								//写记录
								$addData = array(
										'product_id'=>	$list['variation_product_id'],
										'seller_sku'=>	$list['online_sku'],
										'sku'		=>	$list['sku'],
										'account_id'=>	$accountID,
										'site_id'	=>	'0',
										'old_quantity'=>$list['product_stock'],
										'status'	=>	$status,
										'msg'		=>	$message,
										'create_time'=>	date("Y-m-d H:i:s"),
										'type'		=>	$type,
										'restore_num'=> 0
								);
								$wishZeroStockSKUModel->saveData($addData);
							}
							if($bug){
								echo "<br/>=========end:foreach=========<br/>";
							}
						}else{
							$isContinue = false;
						}
					}while($isContinue);
					$logModel->setSuccess($logID, "success");
				}catch (Exception $e){
					if(isset($logID) && $logID){
						$logModel->setFailure($logID, $e->getMessage());
					}
					if($bug){
						echo "<br/>=====Failuer======<br/>";
						echo $e->getMessage()."<br/>";
					}
				}
			}
		}else{
			//循环每个账号发送一个拉listing的请求
			$accountList = WishAccount::model()->getAbleAccountList();
			foreach($accountList as $account){
				MHelper::runThreadSOCKET('/'.$this->route.'/account_id/' . $account['id'] . '/sku/' . $sku . '/limit/'.$limit);
				sleep(1);
			}
		}
	}
	/**
	 * @desc 恢复从自动置为0的sku的库存  定时任务：wish国内仓自动改库存为0，按销售要求目前停止执行 20170307|Liz
	 * @link /wish/wishapirequest/restoreskustockfromzerostocksku
	 */
	public function actionRestoreskustockfromzerostocksku(){

		Yii::app()->end('按销售要求目前停止执行:20170307');

		set_time_limit(5*3600);
		ini_set('display_errors', true);
		error_reporting(E_ALL);
		
		$accountID = Yii::app()->request->getParam('account_id');
		$skus = Yii::app()->request->getParam('sku');
		$bug = Yii::app()->request->getParam('bug');
		$type = Yii::app()->request->getParam('type');
		if(!$type) $type = 1;//强制设置，后续改
		if($accountID){
			$time = date("Y-m-d H:i:s");
			//写log
			$logModel = new WishLog();
			$eventName = WishZeroStockSku::EVENT_RESTORE_STOCK;
			$logID = $logModel->prepareLog($accountID, $eventName);
			if(!$logID){
				exit('Create Log Failure');
			}
			//检测是否可以允许
			if(!$logModel->checkRunning($accountID, $eventName)){
				$logModel->setFailure($logID, Yii::t('system', 'There Exists An Active Event'));
				echo "There Exists An Active Event";
				exit;
			}
			$startTime = date("Y-m-d H:i:s");
			//设置运行
			$logModel->setRunning($logID);
			//@todo
			//1、获取对应的置为0的sku列表
			//2、寻找对应sku的可用库存数量
			$zeroStockSKUModel = new WishZeroStockSku();
			$wareHouseModel = new WarehouseSkuMap();
			$conditions = "t.is_restore=0 and t.status=2 and t.account_id={$accountID} and t.restore_num<3";
			if($skus){
				$skuarr = explode(",", $skus);
				$conditions .= " AND t.sku in (".MHelper::simplode($skuarr).")";
			}
			
			$minTime = "0000-00-00 00:00:00";
			$maxTime = date("Y-m-d H:i:s", time()-60);
			$conditions .= " and t.restore_time>='{$minTime}' and t.restore_time<='{$maxTime}'";
			if($bug){
				echo "<br>===conditions:{$conditions} ===<br>";
			}			
			
			$limit = 1000;
			$offset = 0;
			$msg = "";
			do{
				$skuList = $zeroStockSKUModel->getDbConnection()->createCommand()
											->from($zeroStockSKUModel->tableName() . " as t")
											->select("t.id,t.seller_sku,account_id,t.sku,t.restore_num,t.old_quantity")
											->where($conditions)
											->limit($limit)
											//->group('seller_sku')
											->queryAll();
				//$offset += $limit;
				if($bug){
					echo "<br/>========skuList========<br/>";
					print_r($skuList);
				}
				
				if($skuList){
					$isContinue = true;
					//匹配出来符合要求的
					$skuArrs = array();
					foreach ($skuList as $list){
						$skuArrs[] = $list['sku'];
					}

					$nowTime = time();
					if($nowTime<strtotime("2017-02-15 00:00:00")){//2月6号前用此规则			2月8日修改时间调整为2月15号之前  by qzz
						$skuSalesTablename = "ueb_sync.ueb_sku_sales";
						$warecondition = "t.available_qty >= IFNULL(s.day_sale_num,5) and t.sku in ( ". MHelper::simplode($skuArrs) ." ) and t.warehouse_id=41";
						$mapList = $wareHouseModel->getDbConnection ()->createCommand ()->select ( "t.sku" )
							->from ( $wareHouseModel->tableName() . " as t" )
							->leftJoin ( $skuSalesTablename . " as s", "s.sku=t.sku AND s.day_sale_num > 5 " )
							->where ( $warecondition )
							->queryAll ();
					}else {
						$sql = "select sku from ueb_warehouse.ueb_warehouse_sku_map where
    					available_qty>=3 and sku in ( " . MHelper::simplode($skuArrs) . " ) and warehouse_id=41";
						$mapList = $wareHouseModel->getDbConnection()->createCommand($sql)->queryAll();

						//关闭以下临时规则。2017-02-20
						//恢复条件：采购周期小于等于3，在途+可用库存>=10个，光明仓库，恢复到改0前lisitng库存数量 2017-02-14
						// $bakDays = 3;
						// $select = 't.sku';
						// $conditionNew = '(t.available_qty + t.transit_qty) >= :available_qty AND p.product_bak_days <= :product_bak_days AND t.warehouse_id = :warehouse_id AND p.product_is_multi IN(0,1,2) AND t.sku IN('.MHelper::simplode($skuArrs).')';
						// $param = array(':available_qty'=>10, ':warehouse_id'=>WarehouseSkuMap::WARE_HOUSE_GM, ':product_bak_days'=>$bakDays);
						// $mapList = $wareHouseModel->getSkuListLeftJoinProductByCondition($conditionNew, $param, '', $select);
					}
					if($bug){
						// echo "<br/>conditionNew:{$conditionNew}<br/>";
						// echo "<br/>param:<br/>";print_r($param);
						echo "<br/>=======mapList=======<br/>";
						print_r($mapList);
					}
					$skuMapArrs = array();
					
					if($mapList){
						foreach ($mapList as $list){
							$skuMapArrs[$list['sku']] = $list['sku'];
						}
					}
					
					$newLists = array();
					$skuIDs = array();
					$noMatchId = array();
					foreach ($skuList as $list){
						if(isset($skuMapArrs[$list['sku']])){
							$newLists[] = $list;
						}else{
	    					$noMatchId[] = $list['id'];
	    				}
					}
					if($noMatchId){
						//@todo 更新时间更新
						$zeroStockSKUModel->getDbConnection()
											->createCommand()
											->update($zeroStockSKUModel->tableName(), array('restore_time'=>date("Y-m-d H:i:s")), array("IN", "id", $noMatchId));
					}
					if($bug){
						echo "<br/>======newLists======<br/>";
						print_r($newLists);
					}
					unset($skuList);
					if(empty($newLists)) continue;
					$updateInventoryRequest = new UpdateInventoryRequest;
					$updateInventoryRequest->setAccount($accountID);
					foreach ($newLists as $list){
						$quantity = 999;	//恢复库存数量为999 2017-02-20
						// $quantity = $list['old_quantity'];
						$updateInventoryRequest->setSku($list['seller_sku']);
						$updateInventoryRequest->setInventory($quantity);//2016-02-19
						$response = $updateInventoryRequest->setRequest()->sendRequest()->getResponse();
						if($bug){
							echo "<br>========response=======<br/>";
							print_r($response);
						}
						if($updateInventoryRequest->getIfSuccess()){
							$updateData = array('is_restore'=>1, 
												'restore_time'=>date("Y-m-d H:i:s"),
												'msg'	=>	'success',
												'restore_num'=>intval($list['restore_num'])+1
												);
						}else{
							$msg .= " sku:{$list['seller_sku']}, accountID:{$accountID} :" . $updateInventoryRequest->getErrorMsg();
							$updateData = array('is_restore'=>2,//失败 
												'restore_time'=>date("Y-m-d H:i:s"), 
												'msg'=>$updateInventoryRequest->getErrorMsg(),
												'restore_num'=>intval($list['restore_num'])+1	
											);
						}
						$updateData['restore_quantity'] = $quantity;
						$zeroStockSKUModel::model()->getDbConnection()
													->createCommand()
													->update($zeroStockSKUModel->tableName(),
															$updateData, "account_id={$accountID} AND seller_sku='{$list['seller_sku']}'");
					}
				}else{
					$isContinue = false;
				}
			}while ($isContinue);
			$logModel->setSuccess($logID, $msg);
		}else{
			//循环每个账号发送一个拉listing的请求
			$accountList = WishAccount::model()->getAbleAccountList();
			foreach($accountList as $account){
				MHelper::runThreadSOCKET('/'.$this->route.'/account_id/' . $account['id']. '/type/' . $type);
				sleep(1);
			}
		}
	}
        
            

    /**
     * @desc 最新拉取listing：1备份数据2拉取listing到文件3更新数据
     */
    // public function actionGetListingNew(){
    //     set_time_limit(5*3600);
    //     ini_set('memory_limit', '2048M');
    //     $accountID = Yii::app()->request->getParam('account_id');
    //     if ($accountID) {
    //             //创建日志
    //             $wishLog = new WishLog();
    //             $logID = $wishLog->prepareLog($accountID, WishListing::EVENT_NAME);
    //             if ($logID) {
    //                     //检查账号是否可以拉取
    //                     $checkRunning = $wishLog->checkRunning($accountID, WishListing::EVENT_NAME);
    //                     if (!$checkRunning) {
    //                             $wishLog->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
    //                     } else {
    //                             //插入本次log参数日志(用来记录请求的参数)
    //                             $time = date('Y-m-d H:i:s');
    //                             $eventLogID = $wishLog->saveEventLog(WishListing::EVENT_NAME, array(
    //                                             'log_id' => $logID,
    //                                             'account_id' => $accountID,
    //                                             'start_time'    => $time,
    //                                             'end_time'      => $time,
    //                             ));
    //                             //设置日志正在运行
    //                             $wishLog->setRunning($logID);
                                
    //                             $message = '';
    //                             //1备份当前表当前账号数据
    //                             $model_listing = new WishListing();
    //                             $model_listing->setAccountID($accountID);
    //                             //$bak_result = $model_listing->bakListing($logID);
    //                             //if(!$bak_result){
    //                                 //记录日志备份失败
    //                             //    $message = $model_listing->getExceptionMessage();
    //                             //} else {

    //                                 //2所有当前表当前账号数据状态改为未确认
    //                                 WishListing::model()->getDbConnection()->createCommand()->update(WishListing::model()->tableName(), array('confirm_status' => '0'), "account_id = '{$accountID}'");
    //                                 WishVariants::model()->getDbConnection()->createCommand()->update(WishVariants::model()->tableName(), array('confirm_status' => '0'), "account_id = '{$accountID}'");
    //                             //}
                                
    //                             //3拉取listing
    //                             $hasflish = false;
    //                             $get_result = false;
    //                             $index = 0;
    //                             $total = 0;
    //                             while (!$hasflish && $bak_result) {
    //                                     $request = new ListAllProductsRequest();
    //                                     $request->setAccount($accountID);
    //                                     $request->setLimit(500);//500
    //                                     $request->setStartIndex($index);
    //                                     $index++;
    //                                     $response = $request->setRequest()->sendRequest()->getResponse();
    //                                     if ($request->getIfSuccess() && !empty($response)) {
    //                                             $datas = $response->data;
    //                                             $wishListing = new WishListing();
    //                                             $wishListing->setAccountID($accountID);
                                                
    //                                             //插入、更新数据
    //                                             $save_return = $wishListing->saveWishListingNew($datas, $logID);
    //                                             if(!$save_return){
    //                                                 $message = $wishListing->getExceptionMessage();
    //                                                 $get_result = false;
    //                                                 break;
    //                                             }
                                                
    //                                             unset($datas);
    //                                             if (  (!isset($response->paging->next) || empty($response->paging->next)) && isset($response->paging->previous)  ){
    //                                                 $hasflish = true;
    //                                                 $get_result = true;
    //                                             }
    //                                             if( isset($response->message) && !empty($response->message)  ){
    //                                                 $get_result = false;
    //                                                 var_dump($response->message);
    //                                                 $message = $response->message;
    //                                                 break;
    //                                             }
    //                                     } else {
    //                                             $hasflish = true;
    //                                             $get_result = false;
    //                                             $message = 'get_fail';
    //                                             var_dump($response);
    //                                             break;
    //                                     }
    //                                     unset($response);
    //                             }
                                
    //                             if($get_result){
    //                                 //4成功的账号删除状态未确认的数据（校验）
    //                                 WishListing::model()->getDbConnection()->createCommand()->delete(WishListing::model()->tableName(), "account_id = '{$accountID}' and confirm_status = 0 ");
    //                                 WishVariants::model()->getDbConnection()->createCommand()->delete(WishVariants::model()->tableName(), "account_id = '{$accountID}' and confirm_status = 0");
    //                                 $wishLog->setSuccess($logID);
    //                                 $wishLog->saveEventStatus(WishListing::EVENT_NAME, $eventLogID, WishLog::STATUS_SUCCESS);
    //                             } else {
    //                                 //5失败的账号不删除
    //                                 $wishLog->setFailure($logID, $message);
    //                                 $wishLog->saveEventStatus(WishListing::EVENT_NAME, $eventLogID, WishLog::STATUS_FAILURE);
    //                             }
    //                     }
    //             }
    //     } else {
    //         $accountList = WishAccount::model()->getGroupAccounts();
    //         //循环每个账号发送一个拉listing的请求
    //         foreach ($accountList as $accountID) {
    //                 //43个账号按尾数分成十组
    //                 MHelper::runThreadSOCKET('/'.$this->route.'/account_id/' . $accountID);
    //                 sleep(20);
    //         }
    //     }
    // }


	/**
	 * @desc 针对指定下架的SKU，通过lisitng改上架状态
	 * @link /wish/wishapirequest/changeenablebylisting
	 */
	public function actionChangeenablebylisting(){
		set_time_limit(4*3600);
		ini_set('display_errors', true);
		error_reporting(E_ALL);

		$wishLogOfflineModel  = new WishLogOffline();
		$updateEnableRequest = new UpdateProductVariationRequest;

		$sql = "SELECT * FROM " .$wishLogOfflineModel->tableName(). " WHERE sku IN (
		'119605.01','119790.01','122123.02','122123.03','122123.04','122147.01','122147.02','122874.01'
		) and start_time>='2016-12-21 09:00:00' and start_time<='2016-12-21 12:00:00'";
		$list = $wishLogOfflineModel->getDbConnection()->createCommand($sql)->queryAll();
		// MHelper::printvar($list);
		if ($list){
			foreach($list as $val){
				$online_sku = '';
				$status = 0;
				$updateEnableRequest->setAccount($val['account_id']);

				$product_id = $val['product_id'];
				$subSku = $val['sku'];
				$where = "product_id = '{$product_id}' AND sku = '{$subSku}'";
				$variationInfo = WishVariants::model()->getInfoByCondition($where);
				if ($variationInfo){
					$online_sku = $variationInfo['online_sku'];
				}
				$updateEnableRequest->setSku($online_sku);

				$updateEnableRequest->setEnabled(true);
				$response = $updateEnableRequest->setRequest()->sendRequest()->getResponse();
				$message = "";
				if($updateEnableRequest->getIfSuccess()){
					$status = 1;//成功
				}else{
					$status = 2;//失败
					$message = "accountID:{$val['account_id']},product_id:{$product_id},sku:{$val['sku']},online_sku: {$online_sku},Error:" . $updateEnableRequest->getErrorMsg();
				}
				//写记录
				$addData = array(
						'product_id'=>	$val['product_id'],
						'seller_sku'=>	$online_sku,
						'sku'		=>	$val['sku'],
						'account_id'=>	$val['account_id'],
						'status'	=>	$status,
						'msg'		=>	$message,
						'create_time'=>	date("Y-m-d H:i:s"),
						'type'		=>	1
				);
				WishEnableTask::model()->addData($addData);							
			}
		}
		Yii::app()->end('Finish');
	}
	/*
	 * 针对wish海外仓全部调0
	 * 排除促销和本地仓
	 * /wish/wishapirequest/overseaZeroStock/account_id/2/sku/xxx
	 */
	public function actionOverseaZeroStock(){
		set_time_limit(5*3600);
		ini_set('display_errors', true);
		error_reporting(E_ALL);

		$accountID = Yii::app()->request->getParam('account_id');
		$sku = Yii::app()->request->getParam('sku');
		$limit = Yii::app()->request->getParam('limit',1000);
		$bug = Yii::app()->request->getParam('bug');
		$offset = 0;

		if($accountID){
			$wishProductModel = new WishProduct();
			$wishProductVariantModel = new WishVariants();
			$wishOverseaZeroStockLogModel = new WishOverseaZeroStockLog();

			try{
				$logModel = new WishLog();
				$eventName = WishOverseaZeroStockLog::EVENT_ZERO_STOCK;
				$logID = $logModel->prepareLog($accountID, $eventName);
				if(!$logID){
					throw new Exception("Create Log ID fail");
				}
				//检测是否可以允许
				if(!$logModel->checkRunning($accountID, $eventName)){
					throw new Exception("here Exists An Active Event");
				}
				$logModel->setRunning($logID);
				do {
					$command = $wishProductVariantModel->getDbConnection()->createCommand()
						->from($wishProductVariantModel->tableName() . " AS t")
						->leftJoin($wishProductModel->tableName() . " AS p", "p.id=t.listing_id")
						->select("t.id as vid,t.sku, t.account_id, t.online_sku, t.inventory as product_stock, t.variation_product_id, p.product_id")
						->where("t.account_id=" . $accountID)
						->andWhere("p.is_promoted=0")	//排除促销
						->andWhere("p.warehouse_id<>41")	//排除本地仓
						->limit($limit, $offset);
					if ($sku) {
						$command->andWhere("p.sku='{$sku}'");
					}
					$variantListing = $command->queryAll();
					$offset += $limit;

					if($variantListing){
						if($bug){
							$isContinue = false;
						}else{
							$isContinue = true;
						}

						foreach ($variantListing as $info) {
							//查询是否有记录，有则跳过
							/*$logOne = $wishOverseaZeroStockLogModel->getDbConnection()->createCommand()
								->select("id")
								->from($wishOverseaZeroStockLogModel->tableName())
								->where("sku = '{$info['sku']}' and account_id = '{$accountID}'")
								->limit(1)
								->queryRow();
							if($logOne){
								continue;
							}*/
							//更新库存为0
							$updateInventoryRequest = new UpdateInventoryRequest;
							$updateInventoryRequest->setAccount($accountID);
							$updateInventoryRequest->setSku($info['online_sku']);
							$updateInventoryRequest->setInventory(0);
							$updateInventoryRequest->setEnabled(true);
							$response = $updateInventoryRequest->setRequest()->sendRequest()->getResponse();

							if ($updateInventoryRequest->getIfSuccess()) {
								$status = 1;//成功
								$message = '';
								$wishProductVariantModel->getDbConnection()->createCommand()
									->update($wishProductVariantModel->tableName(),array('inventory'=>0),"id ={$info['vid']}");
							} else {
								$status = 2;//失败
								$message = " accountID:{$accountID},  sku: {$info['online_sku']}" . $updateInventoryRequest->getErrorMsg();
							}
							//记录到记录表
							$addData = array(
								'status' => $status,
								'msg' => $message,
								'product_id' => $info['variation_product_id'],
								'seller_sku' => $info['online_sku'],
								'sku' => $info['sku'],
								'account_id' => $accountID,
								'site_id' => '0',
								'old_quantity' => $info['product_stock'],
								'create_time' => date("Y-m-d H:i:s"),
								'restore_num' => 0
							);
							$wishOverseaZeroStockLogModel->saveData($addData);
							unset($addData);
						}
					}else{
						$isContinue = false;
					}
				}while($isContinue);
				echo "ok";
				$logModel->setSuccess($logID, "success");
			}catch (Exception $e){
				if(isset($logID) && $logID){
					$logModel->setFailure($logID, $e->getMessage());
				}
				echo $e->getMessage()."<br/>";
			}
		}else{
			//循环每个账号发送一个拉listing的请求
			$accountList = WishAccount::model()->getIdNamePairs();
			foreach($accountList as $accountID=>$accountName){
				MHelper::runThreadSOCKET('/'.$this->route.'/account_id/' . $accountID);
				sleep(1);
			}
		}
	}
	/*
	 * 恢复wish海外仓帐号为调0前的数量
	 * 排除促销和本地仓
	 * /wish/wishapirequest/restoreOverseaStock/account_id/2/sku/xxx
	 */
	public function actionRestoreOverseaStock(){
		set_time_limit(4*3600);
		ini_set('display_errors', true);
		error_reporting(E_ALL);

		$accountID = Yii::app()->request->getParam('account_id');
		$sku = Yii::app()->request->getParam('sku');
		$limit = Yii::app()->request->getParam('limit',1000);

		if($accountID){
			try{
				$logModel = new WishLog();
				$eventName = WishOverseaZeroStockLog::EVENT_RESTORE_STOCK;
				$logID = $logModel->prepareLog($accountID, $eventName);
				if(!$logID){
					throw new Exception("Create Log Failure");
				}
				//检测是否可以允许
				if(!$logModel->checkRunning($accountID, $eventName)){
					throw new Exception("here Exists An Active Event");
				}
				$logModel->setRunning($logID);

				$wishOverseaZeroStockLogModel = new WishOverseaZeroStockLog();
				$conditions = "t.is_restore=0 and t.status=1 and t.account_id={$accountID} and t.restore_num<3";
				do{
					$command = $wishOverseaZeroStockLogModel->getDbConnection()->createCommand()
						->from($wishOverseaZeroStockLogModel->tableName() . " as t")
						->select("t.id,t.seller_sku,account_id,t.sku,t.old_quantity,t.restore_num")
						->where($conditions)
						->limit($limit);
					if ($sku) {
						$command->andWhere("t.sku=".$sku);
					}
					$skuList = $command->queryAll();

					if($skuList){
						$isContinue = true;

						foreach ($skuList as $info){
							$updateInventoryRequest = new UpdateInventoryRequest;
							$updateInventoryRequest->setAccount($accountID);
							$updateInventoryRequest->setSku($info['seller_sku']);
							$updateInventoryRequest->setInventory($info['old_quantity']);
							$response = $updateInventoryRequest->setRequest()->sendRequest()->getResponse();

							if($updateInventoryRequest->getIfSuccess()){
								$updateData = array(
									'is_restore'=>1,
									'restore_time'=>date("Y-m-d H:i:s"),
									'msg'	=>	'success',
									'restore_num'=>intval($info['restore_num'])+1
								);
							}else{
								$updateData = array(
									'is_restore'=>2,//失败
									'restore_time'=>date("Y-m-d H:i:s"),
									'msg'=>$updateInventoryRequest->getErrorMsg(),
									'restore_num'=>intval($info['restore_num'])+1
								);
							}
							$updateData['restore_quantity'] = $info['old_quantity'];
							$wishOverseaZeroStockLogModel->getDbConnection()->createCommand()
								->update($wishOverseaZeroStockLogModel->tableName(),
									$updateData, "account_id={$accountID} AND seller_sku='{$info['seller_sku']}'");
						}
					}else{
						$isContinue = false;
					}
				}while ($isContinue);
				echo "ok";
				$logModel->setSuccess($logID, "success");
			}catch (Exception $e){
				if(isset($logID) && $logID){
					$logModel->setFailure($logID, $e->getMessage());
				}
				echo $e->getMessage()."<br/>";
			}
		}else{
			//循环每个账号发送一个拉listing的请求
			$accountList = WishAccount::model()->getIdNamePairs();
			foreach($accountList as $accountID=>$accountName){
				MHelper::runThreadSOCKET('/'.$this->route.'/account_id/' . $accountID);
				sleep(1);
			}
		}
	}


	/**
	 * @desc 一次性恢复listing的库存 2017-02-20 Liz
	 *  wish在线listing一次性恢复：sku在售中，listing在线（上架），listing库存数<10，限制在国内仓,统一恢复成999,恢复一次成功后，不需要继续执行
	 * @link /wish/wishapirequest/restorestockfromlisting/account_id/1/sku/xx/bug/1
	 */
	public function actionRestorestockfromlisting(){

		Yii::app()->end('已执行完成，关闭接口');

		set_time_limit(5*3600);
		ini_set('display_errors', true);
		error_reporting(E_ALL);

		$accountID = Yii::app()->request->getParam('account_id');
		$sku = Yii::app()->request->getParam('sku');
		$limit = Yii::app()->request->getParam('limit',1000);
		$bug = Yii::app()->request->getParam('bug');
		$offset = 0;
		$quantity = 999;	//恢复库存数量为999
		$type = 7;	//恢复类型：年后2.20恢复库存999

		if($accountID){
			$wishProductModel        = new WishProduct();
			$wishProductVariantModel = new WishVariants();
			$wishZeroStockSKUModel   = new WishZeroStockSku();
			$productModel            = new Product();

			try{
				$logModel = new WishLog();
				$eventName = WishZeroStockSku::EVENT_RESTORE_STOCK;
				$logID = $logModel->prepareLog($accountID, $eventName);
				if(!$logID){
					throw new Exception("Create Log ID fail");
				}
				//检测是否可以允许
				if(!$logModel->checkRunning($accountID, $eventName)){
					throw new Exception("here Exists An Active Event");
				}
				$logModel->setRunning($logID);
				do {
					$command = $wishProductVariantModel->getDbConnection()->createCommand()
						->from($wishProductVariantModel->tableName() . " AS t")
						->leftJoin($wishProductModel->tableName() . " AS p", "p.id=t.listing_id")
						->select("t.id as vid, t.sku, t.account_id, t.online_sku, t.inventory as product_stock, t.variation_product_id")
						->where("t.account_id=" . $accountID)
						->andWhere("p.warehouse_id = " .WarehouseSkuMap::WARE_HOUSE_GM)	//针对国内仓
						->andWhere("t.enabled = 1 ")	//listing在线(可售状态)
						->andWhere("t.inventory < 10 ")	//lisitng库存数<10
						->limit($limit, $offset);
					if($sku){
						$skus = explode(",", $sku);
						$command->andWhere(array("IN", "t.sku", $skus));				
					}
					$variantListing = $command->queryAll();
					if($bug){
						echo "<br/>======variantListing======<br/>";
						print_r($variantListing);
					}
			
					// $variantListing = $command->queryAll();
					$offset += $limit;

					if($variantListing){
						if($bug){
							$isContinue = false;
						}else{
							$isContinue = true;
						}

						//只允许SKU产品为在售中状态的
	                    $skuArr = array();
	                    $skuListArr = array();
	                    foreach ($variantListing as $listingValue) {
	                        $skuArr[] = $listingValue['sku'];
	                    }
	                    $conditions = 'sku IN('.MHelper::simplode($skuArr).') AND product_status = :product_status';
	                    $param = array(':product_status'=>4);
	                    $skuList = $productModel->getProductListByCondition($conditions, $param, '', 'sku');
	                    if(!$skuList){
	                        continue;            
	                    } 
	                    unset($skuArr);
	                    foreach ($skuList as $skuVal){
	                        $skuListArr[] = $skuVal['sku'];
	                    }

						foreach ($variantListing as $info) {
	                        if(!in_array($info['sku'], $skuListArr)){
	                            continue;
	                        }

							//更新库存
							$updateInventoryRequest = new UpdateInventoryRequest;
							$updateInventoryRequest->setAccount($accountID);
							$updateInventoryRequest->setSku($info['online_sku']);
							$updateInventoryRequest->setInventory($quantity);
							$response = $updateInventoryRequest->setRequest()->sendRequest()->getResponse();
							if ($updateInventoryRequest->getIfSuccess()) {
								$status = 2;//成功
								$message = 'success';
								$isRestore = 1;
								//更新listing库存
								$wishProductVariantModel->getDbConnection()->createCommand()->update($wishProductVariantModel->tableName(),array('inventory' => $quantity), "id ={$info['vid']}");
							} else {
								$status = 3;	//失败
								$isRestore = 2;	//失败 
								$message = " accountID:{$accountID},  sku: {$info['online_sku']}" . $updateInventoryRequest->getErrorMsg();
							}
							//记录到记录表
							$addData = array(
									'product_id'       => $info['variation_product_id'],
									'seller_sku'       => $info['online_sku'],
									'sku'              => $info['sku'],
									'account_id'       => $accountID,
									'site_id'          => '0',
									'restore_num'      => 1,
									'status'           => $status,
									'is_restore'       => $isRestore,
									'type'             => $type,
									'msg'              => $message,
									'create_time'      => date("Y-m-d H:i:s"),
									'restore_time'     => date("Y-m-d H:i:s"),							
									'old_quantity'     => $info['product_stock'],
									'restore_quantity' => $quantity,
							);
							$wishZeroStockSKUModel->saveData($addData);
							unset($addData);
						}
					}else{
						$isContinue = false;
					}
				}while($isContinue);
				
				$logModel->setSuccess($logID, "success");
				Yii::app()->end('finish');
			}catch (Exception $e){
				if(isset($logID) && $logID){
					$logModel->setFailure($logID, $e->getMessage());
				}
				echo $e->getMessage()."<br/>";
			}
		}else{
			//循环每个账号发送一个拉listing的请求
			$accountList = WishAccount::model()->getIdNamePairs();
			foreach($accountList as $accountID=>$accountName){
				MHelper::runThreadSOCKET('/'.$this->route.'/account_id/' . $accountID);
				sleep(1);
			}
		}
	}


	/**
	 * @desc 自动恢复库存调整(禅道需求#2829) 2017-03-02 Liz 目前没有执行定时任务
	 * 1、所有在线lisitng库存数量为0，安全级别A，不侵权，采购周期小于等于3，在途+可用库存>=10个，本地光明仓，符合以上条件的lisitng需要进行恢复库存，多属性的则对应子sku恢复库存，恢复库存数量为999；
	 * 2、恢复的同时需要检测当前恢复lisitng的sku在调0记录里面是否存在处理成功待恢复的，如果有需要作出对应的状态更改（恢复成功或失败）
	 * @link /wish/wishapirequest/restoreskustockfromlisting/account_id/1/sku/xx/debug/1
	 */
	public function actionRestoreskustockfromlisting(){
		set_time_limit(5*3600);
		ini_set('display_errors', true);
		error_reporting(E_ALL);

		$accountID = Yii::app()->request->getParam('account_id');
		$sku = Yii::app()->request->getParam('sku');
		$limit = Yii::app()->request->getParam('limit',1000);
		$debug = Yii::app()->request->getParam('debug');
		$offset       = 0;
		$bakDays      = 3; 		//采购周期
		$availableQty = 10;		//库存数量
		$quantity     = 999;	//恢复库存数量为999
		$type         = 8;		//恢复类型：3.02号自动恢复库存
		$allowWarehouse =  WarehouseSkuMap::WARE_HOUSE_GM;

		if($accountID){
			$wishProductModel        = new WishProduct();
			$wishProductVariantModel = new WishVariants();
			$skuModel                = new WarehouseSkuMap();
			$wishZeroStockSKUModel   = new WishZeroStockSku();
			$productModel            = new Product();

			$warehouseTablename = "ueb_warehouse." . WarehouseSkuMap::model ()->tableName ();
			$productTablename = "ueb_product." . Product::model ()->tableName ();
			$productInfringementTablename = "ueb_product." . ProductInfringe::model ()->tableName ();

			try{
				$logModel = new WishLog();
				$eventName = WishZeroStockSku::EVENT_RESTORE_STOCK;
				$logID = $logModel->prepareLog($accountID, $eventName);
				if(!$logID){
					throw new Exception("Create Log ID fail");
				}
				//检测是否可以允许
				if(!$logModel->checkRunning($accountID, $eventName)){
					throw new Exception("here Exists An Active Event");
				}
				$logModel->setRunning($logID);
				do {
					$command = $wishProductVariantModel->getDbConnection()->createCommand()
						->from($wishProductVariantModel->tableName() . " AS t")
						->leftJoin($wishProductModel->tableName() . " AS p", "p.id=t.listing_id")
						->select("t.id as vid, t.sku, t.account_id, t.online_sku, t.inventory as product_stock, t.variation_product_id")
						->where("t.account_id=" . $accountID)
						->andWhere("p.warehouse_id = " .$allowWarehouse)	//针对国内仓
						->andWhere("t.enabled = 1 ")	//listing在线(可售状态)
						->andWhere("t.inventory = 0 ")	//lisitng库存为0
						->limit($limit, $offset);
					if($sku){
						$skus = explode(",", $sku);
						$command->andWhere(array("IN", "t.sku", $skus));				
					}
					$variantListing = $command->queryAll();
					if($debug){
						echo "<br/>======variantListing======<br/><pre>";
						print_r($variantListing);
					}
			
					$variantListing = $command->queryAll();
					$offset += $limit;

					if($variantListing){
						if($debug){
							$isContinue = false;
						}else{
							$isContinue = true;
						}

	                    $skuArr = array();
	                    $skuListArr = array();
	                    foreach ($variantListing as $listingValue) {
	                        $skuArr[] = $listingValue['sku'];
	                    }	

	                    //所有在线lisitng库存数量为0，安全级别A，不侵权，采购周期小于等于3，在途+可用库存>=10个，本地光明仓
						$conditions = "(t.available_qty + t.transit_qty) >= {$availableQty} AND t.warehouse_id in(" . $allowWarehouse . ") 
										AND p.product_bak_days <= {$bakDays} AND p.product_status in(" . Product::STATUS_ON_SALE . ") 
										AND (pi.security_level = 'A' OR pi.infringement = 1 OR ISNULL(pi.sku))";

						$skuList = $skuModel->getDbConnection ()->createCommand ()->select ( "t.sku" )
						->from ( $warehouseTablename . " as t" )
						->leftJoin ( $productTablename . " as p", "p.sku=t.sku" )
						->leftJoin ( $productInfringementTablename . " as pi", "pi.sku=t.sku" )
						->where ( array (
								"IN",
								"t.sku",
								$skuArr 
						) )->andWhere ( $conditions )->queryColumn();							

						if($debug){
							echo "<br/>======skuList======<br/>";
							print_r($skuList);
						}						

	                    if(!$skuList){
	                        continue;            
	                    }
	                    unset($skuArr);
	                    $skuListArr = $skuList;

						foreach ($variantListing as $info) {
	                        if(!in_array($info['sku'], $skuListArr)){
	                            continue;
	                        }
	                        $onlineSku = $info['online_sku'];
	                        $todayHadRestore = false;

	                        //查询最近的一次listing恢复操作is_restore=1
							$lastRecord = $wishZeroStockSKUModel->getLastOneByCondition(
								"seller_sku=:seller_sku AND account_id=:account_id AND is_restore=:is_restore",
								array(':seller_sku'=>$onlineSku, ':account_id'=>$accountID, ':is_restore'=> 1)
							);

							//查询当前的listing如果当天已经做过自动恢复的操作，则不再做恢复处理，并记录下来，状态为处理失败，恢复失败，错误提示为当天已做自动恢复处理
							if ($lastRecord){
								$today = date('Y-m-d',time());
								$myday = date('Y-m-d',strtotime($lastRecord['restore_time']));
								if($today == $myday) $todayHadRestore = true;							
							}							

							if (!$todayHadRestore){
								//更新库存
								$updateInventoryRequest = new UpdateInventoryRequest;
								$updateInventoryRequest->setAccount($accountID);
								$updateInventoryRequest->setSku($onlineSku);
								$updateInventoryRequest->setInventory($quantity);
								$response = $updateInventoryRequest->setRequest()->sendRequest()->getResponse();
								if ($updateInventoryRequest->getIfSuccess()) {
								// if (true) {
									$status = 2;//成功
									$message = 'success';
									$isRestore = 1;
									//更新listing库存
									$wishProductVariantModel->getDbConnection()->createCommand()->update($wishProductVariantModel->tableName(),array('inventory' => $quantity), "id ={$info['vid']}");
								} else {
									$status = 3;	//失败
									$isRestore = 2;	//失败 
									$message = " accountID:{$accountID},  sku: {$onlineSku}" . $updateInventoryRequest->getErrorMsg();
								}
							}else{
								$status = 3;	//失败
								$isRestore = 2;	//失败 
								$message = " accountID:{$accountID}, online_sku: {$onlineSku}: 今天对该Listing已经执行过自动恢复操作。";								
							}

							//更新调0的恢复记录
							$updateData = array(
								'is_restore'   => $isRestore,
								'restore_time' => date("Y-m-d H:i:s"), 
								'msg'          => $message,
							);
						
							$wishZeroStockSKUModel::model()->getDbConnection()->createCommand()
								->update($wishZeroStockSKUModel->tableName(),
								$updateData, "account_id='{$accountID}' AND seller_sku='{$onlineSku}' AND status = 2 AND is_restore = 0");							
							
							//记录到记录表
							$addData = array(
									'product_id'       => $info['variation_product_id'],
									'seller_sku'       => $info['online_sku'],
									'sku'              => $info['sku'],
									'account_id'       => $accountID,
									'site_id'          => '0',
									'restore_num'      => 1,
									'status'           => $status,
									'is_restore'       => $isRestore,
									'type'             => $type,
									'msg'              => $message,
									'create_time'      => date("Y-m-d H:i:s"),
									'restore_time'     => date("Y-m-d H:i:s"),							
									'old_quantity'     => $info['product_stock'],
									'restore_quantity' => $quantity,
							);
							$wishZeroStockSKUModel->saveData($addData);
							unset($addData);
						}
					}else{
						$isContinue = false;
					}
				}while($isContinue);
				
				$logModel->setSuccess($logID, "success");
				Yii::app()->end('finish');
			}catch (Exception $e){
				if(isset($logID) && $logID){
					$logModel->setFailure($logID, $e->getMessage());
				}
				echo $e->getMessage()."<br/>";
			}
		}else{
			//循环每个账号发送一个拉listing的请求
			$accountList = WishAccount::model()->getIdNamePairs();
			foreach($accountList as $accountID=>$accountName){
				MHelper::runThreadSOCKET('/'.$this->route.'/account_id/' . $accountID);
				sleep(1);
			}
		}
	}	


    /**
     * 按条件excel导出SKU相关详情数据列表
     * @link /wish/wishapirequest/exportskubycondition/
     * @return excel
     */
    public function actionExportSkuByCondition()
    {
        set_time_limit(3600);
        ini_set('memory_limit', '1024M');
        // ini_set('display_errors', true);
        // error_reporting(E_ALL);      

        try {
        	$start = time();
            $skus = array();
/*            
            //配置相应条件取出SKU
            $productModel = new Product();
            $allowWarehouse =  WarehouseSkuMap::WARE_HOUSE_GM;
            $warehouseTablename = "ueb_warehouse." . WarehouseSkuMap::model()->tableName();
            $productTablename = "ueb_product." . Product::model()->tableName();
            $productInfringementTablename = "ueb_product." . ProductInfringe::model()->tableName();

            //SKU有效库存>0，在售中，安全级别A，不侵权，本地光明仓
            $conditions = "t.available_qty > 0 AND t.warehouse_id in(" . $allowWarehouse . ") 
                            AND p.product_status in(" . Product::STATUS_ON_SALE . ") 
                            AND (pi.security_level = 'A' OR pi.infringement = 1 OR ISNULL(pi.sku))";
            $skuList = $productModel->getDbConnection()->createCommand()->select( "p.sku" )
            ->from ( $productTablename . " as p" )
            ->leftJoin ( $warehouseTablename . " as t", "t.sku=p.sku" )
            ->leftJoin ( $productInfringementTablename . " as pi", "pi.sku=p.sku" )
            // ->limit(10,0)
            ->where($conditions)->queryColumn();
*/
			$skus = array('6316A','2182A','6538I','6538K','6538F','3017B','62427');	//7W+数据量		
			$list = array_chunk($skus,2000);			
			if(!$list) Yii::app()->end('No data');

			foreach($list as $i => $item){
	            $productInfo = Product::model()->getFullProductInfoBySku($item);             
	            if (!$productInfo) continue;

	            $exportData = array();
	            $indexer = 0;	            
	            $apiAddress = ProductImageAdd::getRestfulAddress('',Platform::CODE_SHOPEE);	// $apiAddress = 'http://shopee.erpimage.com:8084';

	            foreach ($productInfo as $key => $product) {
	            	$imageUrls = array();
	                $imageUrls = ProductImageAdd::getImageUrlFromRestfulBySku($product['sku'], 'ft', 'normal', 100, 100, Platform::CODE_SHOPEE);
	                if ($imageUrls){
		                $imageUrls = array_map(function ($i) use ($apiAddress) {
		                    $imageUrl = array_shift(explode("?", $i));
		                    $imageUrl = str_replace($apiAddress, 'http://w.neototem.com:8084', $imageUrl);
		                    return strip_tags($imageUrl);		                    
		                }, $imageUrls);

		                $imageUrls = array_slice($imageUrls, 0, 9);
		            }

			        $chineseTitle = $product['chinese_title'];		        
			        if (!empty($chineseTitle)){
						$chineseTitle = preg_replace_callback("/(?<TT><\/?(?<TAG>\w+).*?>)/im", array($this, 'preg_replace_call_func'), $chineseTitle);
						$chineseTitle = preg_replace("/(\n){2,}|\r\n/ie", "\n", $chineseTitle);
						$chineseTitle = strip_tags($chineseTitle);	
					}
			        $englishTitle = $product['english_title'];		        
			        if (!empty($englishTitle)){
						$englishTitle = preg_replace_callback("/(?<TT><\/?(?<TAG>\w+).*?>)/im", array($this, 'preg_replace_call_func'), $englishTitle);
						$englishTitle = preg_replace("/(\n){2,}|\r\n/ie", "\n", $englishTitle);
						$englishTitle = strip_tags($englishTitle);	
					}					
					$chineseDescription = $product['chinese_description'];
			        if (!empty($chineseDescription)){
						$chineseDescription = preg_replace_callback("/(?<TT><\/?(?<TAG>\w+).*?>)/im", array($this, 'preg_replace_call_func'), $chineseDescription);
						$chineseDescription = preg_replace("/(\n){2,}|\r\n/ie", "\n", $chineseDescription);
						$chineseDescription = strip_tags($chineseDescription);	
					}						            
					$englishDescription = $product['english_description'];
			        if (!empty($englishDescription)){
						$englishDescription = preg_replace_callback("/(?<TT><\/?(?<TAG>\w+).*?>)/im", array($this, 'preg_replace_call_func'), $englishDescription);
						$englishDescription = preg_replace("/(\n){2,}|\r\n/ie", "\n", $englishDescription);
						$englishDescription = strip_tags($englishDescription);	
					}
					$chineseIncluded = $product['chinese_included'];
			        if (!empty($chineseIncluded)){
						$chineseIncluded = preg_replace_callback("/(?<TT><\/?(?<TAG>\w+).*?>)/im", array($this, 'preg_replace_call_func'), $chineseIncluded);
						$chineseIncluded = preg_replace("/(\n){2,}|\r\n/ie", "\n", $chineseIncluded);
						$chineseIncluded = strip_tags($chineseIncluded);	
					}	
					$englishIncluded = $product['english_included'];
			        if (!empty($englishIncluded)){
						$englishIncluded = preg_replace_callback("/(?<TT><\/?(?<TAG>\w+).*?>)/im", array($this, 'preg_replace_call_func'), $englishIncluded);
						$englishIncluded = preg_replace("/(\n){2,}|\r\n/ie", "\n", $englishIncluded);
						$englishIncluded = strip_tags($englishIncluded);	
					}	

	                $exportData[$indexer] = array(
	                    '0' => $product['sku'],
	                    '1' => '',//$skuInfo['sub_sku'],
	                    '2' => $chineseTitle,// 中文标题
	                    '3' => $chineseDescription, // 中文描述
	                    '4' => $chineseIncluded, //included 中文
	                    //'F' => $skuInfo['name'],
	                    '5' => $englishTitle, //英文标题
	                    '6' => $englishDescription, //英文描述
	                    '7' => $englishIncluded,  //included 英文
	                    '8' => $product['security_level'] . ' ' . ProductInfringe::model()->getProductInfringementList($product['infringement']), //产品状态
	                    '9' => '', //产品属性
	                    '10' => $product['category_cn_name'], //公司分类
	                    '11' => $product['product_weight'], //毛重
	                    '12' => $product['product_cost'], //成本
	                    '13' => join("*", array($product['product_length'], $product['product_width'], $product['product_height'])), //产品尺寸
	                    '14' => join("*", array($product['pack_product_length'], $product['pack_product_width'], $product['pack_product_height'])), //包装尺寸
	                    '15' => '', //图片链接
	                );
	                if ($imageUrls){
	                	$imageUrls = array('P'=>implode("\n",$imageUrls));
	                	$exportData[$indexer] = array_merge($exportData[$indexer], $imageUrls);
	                }
	                $indexer++;
	                if ($product['product_is_multi'] > 0) {

	                    $child = array();
	                    $attributeList = ProductSelectAttribute::model()->getSelectedAttributeValueSKUListByMainProductId($product['id']);
	                    foreach ($attributeList as $attribute) {
	                        $child[$attribute['sku']] [] = $attribute['attribute_value_name'];
	                    }

	                    $children = Product::model()->getFullProductInfoBySku(array_keys($child));

	                    foreach ($children as $c) {
	                    	$imageUrls = array();
	                        $imageUrls = ProductImageAdd::getImageUrlFromRestfulBySku($c['sku'], 'ft');
	                        if ($imageUrls){
		                        $imageUrls = array_map(function ($i) use ($apiAddress) {
		                            $imageUrl = array_shift(explode("?", $i));
		                            $imageUrl = str_replace($apiAddress, 'http://w.neototem.com', $imageUrl);
		                            return strip_tags($imageUrl);
		                        }, $imageUrls);

		                        $imageUrls = array_slice($imageUrls, 0, 9);
		                    }

					        $chineseTitle = $c['chinese_title'];		        
					        if (!empty($chineseTitle)){
								$chineseTitle = preg_replace_callback("/(?<TT><\/?(?<TAG>\w+).*?>)/im", array($this, 'preg_replace_call_func'), $chineseTitle);
								$chineseTitle = preg_replace("/(\n){2,}|\r\n/ie", "\n", $chineseTitle);
								$chineseTitle = strip_tags($chineseTitle);	
							}
					        $englishTitle = $c['english_title'];		        
					        if (!empty($englishTitle)){
								$englishTitle = preg_replace_callback("/(?<TT><\/?(?<TAG>\w+).*?>)/im", array($this, 'preg_replace_call_func'), $englishTitle);
								$englishTitle = preg_replace("/(\n){2,}|\r\n/ie", "\n", $englishTitle);
								$englishTitle = strip_tags($englishTitle);	
							}							
							$chineseDescription = $c['chinese_description'];
					        if (!empty($chineseDescription)){
								$chineseDescription = preg_replace_callback("/(?<TT><\/?(?<TAG>\w+).*?>)/im", array($this, 'preg_replace_call_func'), $chineseDescription);
								$chineseDescription = preg_replace("/(\n){2,}|\r\n/ie", "\n", $chineseDescription);
								$chineseDescription = strip_tags($chineseDescription);	
							}						            
							$englishDescription = $c['english_description'];
					        if (!empty($englishDescription)){
								$englishDescription = preg_replace_callback("/(?<TT><\/?(?<TAG>\w+).*?>)/im", array($this, 'preg_replace_call_func'), $englishDescription);
								$englishDescription = preg_replace("/(\n){2,}|\r\n/ie", "\n", $englishDescription);
								$englishDescription = strip_tags($englishDescription);	
							}
							$chineseIncluded = $c['chinese_included'];
					        if (!empty($chineseIncluded)){
								$chineseIncluded = preg_replace_callback("/(?<TT><\/?(?<TAG>\w+).*?>)/im", array($this, 'preg_replace_call_func'), $chineseIncluded);
								$chineseIncluded = preg_replace("/(\n){2,}|\r\n/ie", "\n", $chineseIncluded);
								$chineseIncluded = strip_tags($chineseIncluded);	
							}	
							$englishIncluded = $c['english_included'];
					        if (!empty($englishIncluded)){
								$englishIncluded = preg_replace_callback("/(?<TT><\/?(?<TAG>\w+).*?>)/im", array($this, 'preg_replace_call_func'), $englishIncluded);
								$englishIncluded = preg_replace("/(\n){2,}|\r\n/ie", "\n", $englishIncluded);
								$englishIncluded = strip_tags($englishIncluded);	
							}

	                        $exportData[$indexer] = array(
	                            '0' => $product['sku'],
	                            '1' => $c['sku'],
	                            '2' => $chineseTitle,// 中文标题
	                            '3' => $chineseDescription, // 中文描述
	                            '4' => $chineseIncluded, //included 中文
	                            //'F' => $skuInfo['name'],
	                            '5' => $englishTitle, //英文标题
	                            '6' => $englishDescription, //英文描述
	                            '7' => $englishIncluded,  //included 英文
	                            '8' => $product['security_level'] . ' ' . ProductInfringe::model()->getProductInfringementList($product['infringement']), //产品状态
	                            '9' => join(",", $child[$c['sku']]), //产品属性
	                            '10' => $c['category_cn_name'], //公司分类
	                            '11' => $c['product_weight'], //毛重
	                            '12' => $c['product_cost'], //成本
	                            '13' => join("*", array($product['product_length'], $product['product_width'], $product['product_height'])), //产品尺寸
	                            '14' => join("*", array($product['pack_product_length'], $product['pack_product_width'], $product['pack_product_height'])), //包装尺寸
	                            '15' => '', //图片链接
	                        );
	                        if ($imageUrls){
		                        $imageUrls = array('P'=>implode("\n",$imageUrls));
		                        $exportData[$indexer] = array_merge($exportData[$indexer], $imageUrls);
		                    }

	                        $indexer++;
	                    }
	                }
	            }
	            $xlsHeader = array(
	                '主SKU',
	                '子SKU',
	                '中文标题',
	                '中文描述',
	                'Included（中文）',
	                '英文标题',
	                '英文描述',
	                'Included（英文）',
	                '产品状态',
	                '产品属性',
	                '公司分类',
	                '毛重',
	                '成本',
	                '产品尺寸（长*宽*高）',
	                '包装尺寸（长*宽*高）',
	                '产品图片URL链接（9张）'
	            );

				$filename = 'sku_export_'.($i+1).'_'.date('YmdHis').'.csv';
	    		$uploadDir = "./uploads/downloads/";
	    		$downUrl = "172.16.2.21/uploads/downloads/".$filename;	//下载路径
	    		if(!is_dir($uploadDir)){
	    			mkdir($uploadDir, 0755, true);
	    		}

	    		$filename = $uploadDir.$filename;
				$fp = fopen($filename, 'w+');

				foreach ($xlsHeader as $key => $val) {
					$xlsHeader[$key] = iconv('utf-8', 'gbk', $val);
				}
				@fputcsv($fp, $xlsHeader);

				foreach ($exportData as $key => $val) {
					foreach($val as $k=>$v){
						$val[$k] = iconv('utf-8//ignore', 'gbk//ignore', $v);
					}
					@fputcsv($fp, $val);	
				}
				unset($productInfo);
				unset($exportData);
				@fclose($fp);
				echo $downUrl.'<br />';
			}

			$end = time();
			echo 'todo time:START:'.date('Y-m-d H:i:s',$start).' - END:'.date('Y-m-d H:i:s',$end);
			echo '<br />';
            Yii::app()->end('Finish');

        } catch (\Exception $e) {
            echo $this->failureJson(array('message' => $e->getMessage()));
            Yii::app()->end();
        }
    }   

	private function preg_replace_call_func($match){
		if(in_array(strtolower($match['TT']), array('</p>', '<p/>', '<br/>', '</br>', '<br />'))){
			return "\n";
		}else{
			return '';
		}
	}    


	/**
	 * @desc 测试流程样例说明（非可执行接口）
	 * @link /wish/testexample  
	 * 此样例原型参考：/wish/wishapirequest/autochangestockfornostock
	 * 可以去了解此方法做测试方式，但不需要去了解该方法的整个业务逻辑
	 */
	public function actionTestExample(){
		//设置测试环境运行程序
		$loopNum = 0;
		$testFlag = false;//是否为测试标示
		$runType = Yii::app()->request->getParam("runtype");				//指定为y为正式执行，如果没有这个参数，就必须是测试操作
		$testSKUs = Yii::app()->request->getParam("sku");					//指定测试账号下的至少两个以上的SKU数据测试
		$testAccountID = Yii::app()->request->getParam("account_id");		//测试账号
		$testSkuList = array();
		//测试环境下必须指定sku和账号
		if($runType != "y" && (empty($testSKUs) || empty($testAccountID))){
			exit("测试下必须指定sku列表和账号，多个sku之间用半角,隔开。示例：{$this->route}/account_id/1/sku/1123,22444,3434.09");
		}elseif ($runType != "y"){
			$testFlag = true;
			$testSkuList = explode(",", $testSKUs);
		}
		
		set_time_limit(5*3600);
		error_reporting(E_ALL);
		ini_set("display_errors", true);	//打开错误信息
		
		//有可能是多线程分账号执行，但也要同时测试SKU和测试账号都要指定
		do{
			//取出上述sku对应的产品库中的信息
			if(!$testFlag){
				//如果是非测试，则执行正式的数据操作，分页等
				$limits = "{$offset}, {$limit}";
				$skuList = $SkuModel->$method($conditions, $params, $limits, $select);	//正式SKU列表
				$offset += $limit;
			}else{
				//测试情况下，不存在循环，一次就结束
				if($loopNum > 0){
					exit("测试运行结束");
				}
				$skuList = array();	
    			foreach ($testSkuList as $sku){
    				$skuList[] = array('sku'=>$sku);	//测试的SKU列表
    			}
				$loopNum++;
				echo "set testSkulist=". implode(",", $testSkuList) . "<br/>";	//打印是否自己要执行的测试SKU
			}
			if($skuList){
				$flag = true;
				$skus = array();
				$productListing = $variantListing = array();
				//这里把测试的SKU列表放到要执行的业务SQL里，执行接下来的整个正式流程
				foreach ($skuList as $sku){
					$skus[] = $sku['sku'];
				}
				unset($skuList);
				$command = $wishProductVariantModel->getDbConnection()->createCommand()
										->from($wishProductVariantModel->tableName() ." AS t" )
										->leftJoin($wishProductModel->tableName() . " AS p", "p.id=t.listing_id")
										->select("t.sku, t.account_id, t.online_sku, t.inventory as product_stock, t.variation_product_id, p.product_id")
										->where(array("IN", "t.sku", $skus))
										->andWhere("p.is_promoted=0")
										->andWhere("t.enabled=1") //add lihy 20160213
										->andWhere("t.inventory>0");
				//同时也把对应的测试账号也加到执行条件里										
				if($testAccountID){
					echo "set testaccount_id=".$testAccountID . "<br/>";
					$command->andWhere("t.account_id=".$testAccountID);
				}
				$variantListing = $command->queryAll();
				//如果是测试时，就可以时时打印看每步的数据执行情况，对每个步骤进行有效跟踪
				if($testFlag){
					echo "<br/>======variantListing======<br/>";
					print_r($variantListing);
				}
				//下面是具体业务流程，整合数据，循环接口调用，并且一定要有相关操作日志记录入库（无论接口调用成功或失败）
    		}else{ 
    			//一定要注意要有循环结束条件
    			$flag = false;
    		}
		}while ($flag);
	}	


}