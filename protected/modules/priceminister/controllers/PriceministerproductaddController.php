<?php
/**
 * @desc pm刊登
 * @author Gordon
 * @since 2016-12-19
 */
class PriceministerproductaddController extends UebController{

    /**
     * @desc 刊登列表
     */
	public function actionIndex(){
    	$model = new PriceministerProductAdd();
    	$this->render("index", array('model'=>$model));
    }

	/**
	 * @desc 上传产品
	 */
	public function actionUploadProduct(){
		set_time_limit(7200);
		error_reporting(E_ALL);
		ini_set('display_errors', true);
		$addID = Yii::app()->request->getParam('add_id');
		$priceministerProductAdd = new PriceministerProductAdd();
		$flag = $priceministerProductAdd->uploadProductData($addID);
		if($flag){
			echo $this->successJson(array(
				'message'	=>	Yii::t('system', 'Successful')
			));
		}else{
			$msg = $priceministerProductAdd->getErrorMessage();
			echo $this->failureJson(array(
				'message'	=>	$msg
			));
		}
		Yii::app()->end();
	}

	/*
	 * 	获取上传文件报告，是否成功上传
	 * 	/priceminister/priceministerproductadd/getUploadStatus/account_id/1/file_id/7209737
	 */
	public function actionGetUploadStatus(){

		set_time_limit(3600);
		error_reporting(E_ALL);
		ini_set("display_errors", true);

		$fileID = Yii::app()->request->getParam('file_id');
		$accountID = Yii::app()->request->getParam('account_id');
		$limit = Yii::app()->request->getParam('limit',1000);
		$offset = 0;

		$priceministerProductAddModel = new PriceministerProductAdd();
		$priceministerProductAddVariationModel = new PriceministerProductAddVariation();
		$reportRequest = new GenericImportReportRequest();

		if($accountID){
			try{
				$logModel = new PriceministerLog();
				$eventName = "update_upload_status";
				$logID = $logModel->prepareLog($accountID, $eventName);
				if(!$logID){
					throw new Exception("Create Log ID Failure");
				}
				//检测是否可以允许
				if(!$logModel->checkRunning($accountID, $eventName)){
					throw new Exception("There Exists An Active Event");
				}
				//设置运行
				$logModel->setRunning($logID);

				do{
					$command = $priceministerProductAddModel->getDbConnection()->createCommand()
						->from($priceministerProductAddModel->tableName() . " as t")
						->select("t.id, t.sku, t.import_id")
						->where('t.account_id = ' . $accountID)
						->andWhere('t.status = ' . PriceministerProductAdd::STATUS_OPERATING)
						->andWhere('t.import_id <> 0');
					if($fileID){
						$command->andWhere("t.import_id = '".$fileID."'");
					}
					$command->limit($limit, $offset);
					$productList = $command->queryAll();

					$offset += $limit;
					if($productList){
						$isContinue = true;

						foreach($productList as $info){
							$reportRequest->setFileId($info['import_id']);
							//$reportResponse = $reportRequest->setRequest()->sendRequest()->getResponse();
							$reportResponse = $reportRequest->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
							//$this->print_r($reportResponse);

							if(!isset($reportResponse->response->product)){//上传中
								continue;
							}
							$response = $reportResponse->response->product;
							$productInfo = array();
							if(!isset($response[0])){
								$productInfo[] = $response;

							}elseif(isset($response) && $response){	//返回多个,多属性

								foreach ($response as $feed){
									$productInfo[] = $feed;
								}
							}

							$error_msg = '';
							foreach($productInfo as $k=>$v){
								if($v->status=='Erreur'){
									$errorInfo = $v->errors->error;
									if(!isset($errorInfo[0])){
										$error_key = $errorInfo->error_key;
										$error_code = $errorInfo->error_code;
										$error_text = $errorInfo->error_text;
										$fatal_error = $errorInfo->fatal_error;
										$error_msg .= '错误:'.$error_code.','.'信息:'.$error_key.'—'.$error_text.'—'.$fatal_error.'##';
									}elseif(isset($errorInfo) && $errorInfo){	//返回多个,多属性
										foreach ($errorInfo as $errVal){
											$error_key = $errVal->error_key;
											$error_code = $errVal->error_code;
											$error_text = $errVal->error_text;
											$fatal_error = $errVal->fatal_error;
											$error_msg .= '错误:'.$error_code.','.'信息:'.$error_key.'—'.$error_text.'—'.$fatal_error.'##';
										}
									}

								}else{
									$productId = $v->pid;
									$advertId = $v->aid;
									$sonSku = (string)$v->sku;

									//更新子sku
									$where = "add_id = {$info['id']} and son_sku = '{$sonSku}'";
									$priceministerProductAddVariationModel->getDbConnection()->createCommand()
										->update($priceministerProductAddVariationModel->tableName(), array('advert_id'=>$advertId),$where);
								}
							}
							if($error_msg==''){//成功
								$updateData = array(
									'status'=>PriceministerProductAdd::STATUS_SUCCESS,
									'product_id'=>$productId,
								);
							}else{
								$updateData = array(
									'status'=>PriceministerProductAdd::STATUS_FAILURE,
									'upload_message'=>$error_msg
								);
							}
							//更新product
							$priceministerProductAddModel->getDbConnection()->createCommand()
								->update($priceministerProductAddModel->tableName(), $updateData,"id = " . $info['id'] );
						}
					}else{
						$isContinue = false;
					}
				}while($isContinue);
				$logModel->setSuccess($logID);
			} catch (Exception $e) {
				if($logID){
					$logModel->setFailure($logID, $e->getMessage());
				}
				echo $e->getMessage()."<br/>";
			}
		}else{
			$pmAccounts = PriceministerAccount::model()->getAbleAccountList();
			foreach($pmAccounts as $account){
				//echo '/'.$this->route.'/account_id/'.$account['id'];
				MHelper::runThreadSOCKET('/'.$this->route.'/account_id/'.$account['id']);
				sleep(5);
			}
		}
	}

	/**
	 * @desc 删除待刊登列表
	 */
	public function actionBatchDel()
	{
		$ids = Yii::app()->request->getParam("ids");
		if($ids){
			$idarr = explode(",", $ids);
			$priceministerProductAddModel = new PriceministerProductAdd();
			$res = $priceministerProductAddModel->batchDel($idarr);
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
	 * @desc pm刊登(1.sku录入)
	 */
	public function actionProductaddstepfirst(){
		$params = array();
		if( Yii::app()->request->getParam('dialog')==1 ){
			$params['dialog'] = true;
		}
		$this->render('productAdd1',$params);
	}

	/**
	 * @desc pm刊登(2.账号选择)
	 */
	public function actionProductaddstepsecond(){
		$sku = Yii::app()->request->getParam('sku');
		//刊登类型
		$listingType = PriceministerProductAdd::getListingType();

		//sku信息
		$skuInfo = Product::model()->getProductInfoBySku($sku);
		if(empty($skuInfo)){
			echo $this->failureJson(array('message'=>Yii::t('priceminister', 'Not found the sku')));
			Yii::app()->end();
		}

		//检测是否有权限去刊登该sku
		/*if(! Product::model()->checkCurrentUserAccessToSaleSKU($sku, Platform::CODE_PM)){
			echo $this->failureJson(array('message' => Yii::t('system', 'Not Access to Add the SKU')));
			Yii::app()->end();
		}*/
/*
		$config = ConfigFactory::getConfig('serverKeys');
		//sku图片加载
		$imageType = array('zt','ft');
		$skuImg = array();
		foreach($imageType as $type){
			$images = Product::model()->getImgList($sku,$type);
			foreach($images as $k=>$img){
				$skuImg[$type][$k] = $config['oms']['host'].$img;
			}
		}*/
        $skuImg = ProductImageAdd::getOrPushImageUrlFromRestfulBySku($skuInfo, $pushWithChild = true, $typeAlisa = null, $type = 'normal', $width = 100, $height = 100, $platform = Platform::CODE_PM);
        /**
         * 修复java api接口无主图返回问题
         */
        if (isset($skuImg['ft']) && !isset($skuImg['zt'])) {
            $skuImg['zt'] = $skuImg['ft'];
        }
		//获取模版分类别名
		$cate = PriceministerProductType::model()->getListByCondition();
		//$cate = PriceministerProductType::model()->getListByCondition('*','id=129');

		if(Product::PRODUCT_MULTIPLE_MAIN == $skuInfo['product_is_multi']){
			$currenListingType = PriceministerProductAdd::LISTING_TYPE_VARIATION;
		}else{
			$currenListingType = PriceministerProductAdd::LISTING_TYPE_FIXEDPRICE;
		}

		//刊登模式
		$this->render('productAdd2',   array(
			'sku'           => $sku,
			'skuInfo'       => $skuInfo,
			'currenListingType'	=> $currenListingType,
			'skuImg'        => $skuImg,
			'listingType'	=> $listingType,
			'cate'			=> $cate,
		));

	}
	/**
	 * @desc Ebay刊登(3.刊登资料详情)
	 */
	public function actionProductaddstepthird(){
		try{
			$listingType = Yii::app()->request->getParam('listing_type');
			$listingCateId = Yii::app()->request->getParam('listing_cate');
			$listingAccount = Yii::app()->request->getParam('accounts');
			$sku = Yii::app()->request->getParam('sku');

			if($listingCateId===null){
				throw new CException(Yii::t('priceminister', 'Choose Category'));
			}
			if (empty($listingAccount)) {
				throw new CException(Yii::t('priceminister', 'Account Not Valid'));
			}
			if(count($listingAccount)>1){
				throw new CException(Yii::t('priceminister', 'Only Chose One Account'));
			}

			$skuInfo = Product::model()->getProductInfoBySku($sku);
			if (empty($skuInfo)) {
				throw new CException(Yii::t('priceminister', 'SKU has not Exists'));
			}

			//判断是否在待刊登列表
			$isPublish = UebModel::model('PriceministerProductAdd')->find("sku=:sku",array(":sku"=>$sku,));
			if($isPublish){
				throw new CException(Yii::t('priceminister', 'Had upload the SKU'));
			}

			/**获取刊登参数*/
			$listingTypeArr = PriceministerProductAdd::getListingType();
			$accountId = $listingAccount[0];
			$accountInfo = PriceministerAccount::getAccountInfoById($accountId);
			$productDesc = Productdesc::model()->getDescriptionInfoBySkuAndLanguageCode($sku);
			$skuInfo['title'] = $productDesc['title'];
			$skuInfo['description'] = $productDesc['description'];
			$accountList[$accountInfo['id']] = $accountInfo;
			$listingParam = array(
				'listing_type'      => array('id' => $listingType, 'text' => $listingTypeArr[$listingType]),
				'listing_account'   => $accountList,
			);

//			/**获取产品信息*/
//			$imageType = array('zt', 'ft');
//			$config = ConfigFactory::getConfig('serverKeys');
//			$skuImg = array();
//			foreach($imageType as $type){
//				$images = Product::model()->getImgList($sku,$type);
//				foreach($images as $k=>$img){
//					$skuImg[$type][$k] = $config['oms']['host'].$img;
//				}
//			}

			$skuImg = ProductImageAdd::getImageUrlFromRestfulBySku($sku, $typeAlisa = null, $type = 'normal', $width = 100, $height = 100, $platform = Platform::CODE_PM);
            /**
             * 修复java api接口无主图返回问题
             */
            if (isset($skuImg['ft']) && !isset($skuImg['zt'])) {
                $skuImg['zt'] = $skuImg['ft'];
            }
			$listingProduct = array(
				'sku'           => $sku,
				'skuImg'        => $skuImg,
				'skuInfo'       => $skuInfo,
			);

			//获取子SKU信息
			$listingSubSKU = PriceministerProductAdd::getSubProductByMainProductId($skuInfo['id'], $skuInfo['product_is_multi']);
			if($listingSubSKU['skuList']){
				foreach ($listingSubSKU['skuList'] as $key=>$skuRow){
					$listingSubSKU['skuList'][$key]['skuInfo']['inventory'] = PriceministerProductAdd::PRODUCT_PUBLISH_INVENTORY;
				}
				//已经没有可用的子sku了
				if(empty($listingSubSKU['skuList'])){
					throw new CException(Yii::t('priceminister', 'Had upload the SKU'));
				}
			}else{
				if(isset($accountList[$accountId]['is_add'])){
					throw new CException(Yii::t('priceminister', 'Had upload the SKU'));
				}
				$listingProduct['skuInfo']['inventory'] = PriceministerProductAdd::PRODUCT_PUBLISH_INVENTORY;
			}


			//获取分类模版
			$listingCate = PriceministerProductTypeTemplate::model()->getOneByCondition('attribute','type_id='.$listingCateId);
			//$listingCate = PriceministerProductTypeTemplate::model()->getOneByCondition('attribute','type_id=129');
			if(!$listingCate){
				throw new Exception('没有找到类目模版');
			}
			$listingCate['attribute'] = json_decode($listingCate['attribute']);

			$this->render('addinfo', array(
				'template'	=>	$listingCate['attribute'],
				'listingParam'	=>	$listingParam,
				'listingProduct' => $listingProduct,
				'listingSubSKU'	=>	$listingSubSKU['skuList'],
				'attributeList'	=>	$listingSubSKU['attributeList'],
				'action'		=>	'add',
				'type_id'		=>	$listingCateId,
			));
		}catch(Exception $e){
			echo $this->failureJson(array(
				'message'=>$e->getMessage(),
			));
		}
	}

	/**
	 * @desc 保存到待刊登列表
	 */
	public function actionSaveadddata(){
		try{
			$addID = Yii::app()->request->getParam('add_id');
			$action = Yii::app()->request->getParam('action');
			$typeID = Yii::app()->request->getParam('type_id');
			$sku = Yii::app()->request->getParam('sku');
			$listingType = Yii::app()->request->getParam('listing_type');
			$accounts = Yii::app()->request->getParam('account_id');

			$skuImage         = Yii::app()->request->getParam('skuImage');
			$pmAdvert = Yii::app()->request->getParam('advert');
			$pmProduct = Yii::app()->request->getParam('product');
			//$pmCampaigns = Yii::app()->request->getParam('campaigns');
			$userID = intval(Yii::app()->user->id);
			$nowTime = date("Y-m-d H:i:s");

			if(empty($sku)){
				throw new Exception("Invalid SKU");
			}
			if(empty($accounts)){
				throw new Exception(Yii::t('priceminister', 'Account Error'));
			}
			$accountID = $accounts[0];


			//获取刊登主表信息
			if($action == 'update'){
				$conditions = array("id"=>$addID);
				$productInfo = PriceministerProductAdd::model()->getPmProductAddInfo($conditions);
				if(empty($productInfo)){
					throw new Exception(Yii::t('priceminister', 'Param error'));
				}
				if($productInfo['status'] == PriceministerProductAdd::STATUS_OPERATING){
					throw new Exception("The SKU is Uploading !!!");
				}
				if(!empty($productInfo['product_id']) || $productInfo['status'] == PriceministerProductAdd::STATUS_OPERATING || $productInfo['status'] == PriceministerProductAdd::STATUS_SUCCESS){
					throw new Exception("Has Upload The SKU!!!");
				}
			}

			//暂用副图存取上传
			$mainImg = $extraImg = '';
			if($skuImage){
				$mainImg = !empty($skuImage['main'][0]) ? $skuImage['main'][0] : '';
				if(!empty($skuImage['extra'])){
					$extraImg = $skuImage['extra'];
					if(!$mainImg){
						$mainImg = $extraImg[0];
						unset($extraImg[0]);
					}
					$extraImg = implode("|",  $extraImg);
				}
			}
			if(empty($skuImage) || empty($extraImg)){
				//throw  new Exception(Yii::t('priceminister', "No main image can't upload"));
			}

			//保存到数据库
			$pmProductAdd = new PriceministerProductAdd();
			$pmProductAddVariation = new PriceministerProductAddVariation();
			$pmProductAddExtend = new PriceministerProductAddExtend();
			try{
				$dbtransaction = $pmProductAdd->getDbConnection()->getCurrentTransaction();
				if(!$dbtransaction){
					$dbtransaction = $pmProductAdd->getDbConnection()->beginTransaction();
				}

				$title = '';
				if(isset($pmProduct['title'])) {
					$title = $pmProduct['title'];
				}elseif(isset($pmProduct['titre'])){
					$title = $pmProduct['titre'];
				}

				$addData = array(
					'account_id'		=>	$accountID,
					'sku'				=>	$sku,
					'title'				=>	$title,
					'type_id'			=>	$typeID,
					'description'		=>	'',
					'brand'				=>	isset($pmProduct['manufacturer'])?$pmProduct['manufacturer']:'',
					'main_image' 		=> 	$mainImg,
					'extra_images' 		=> 	$extraImg,
					'listing_type'		=>	$listingType,
					'upload_message'	=>	'',
					'upload_count'		=>	0,
					'product_id'		=>	0,
					'create_user_id'	=>	intval($userID),
					'update_user_id'	=>	intval($userID),
					'create_time'		=>	$nowTime,
					'update_time'		=>	$nowTime,
					'last_response_time'=>	$nowTime,
					'status'			=>	PriceministerProductAdd::STATUS_PENDING,
				);

				if($addID){
					unset($addData['create_time']);
					unset($addData['create_user_id']);
					$res = $pmProductAdd->getDbConnection()->createCommand()->update($pmProductAdd->tableName(), $addData, "id={$addID}");
					if(!$res) throw new Exception('Save Info Failure!!!');
					$addInsertID = $addID;
				}else{
					$pmProductAdd->getDbConnection()->createCommand()->insert($pmProductAdd->tableName(), $addData);
					$addInsertID = $pmProductAdd->getDbConnection()->getLastInsertID();
				}


				//多属性表，循环
				foreach($pmAdvert as $k=>$variantInfo){
					$variationAddData = array(
						'add_id'		=>	$addInsertID,
						'parent_sku'	=>	$sku,
						'son_sku'		=>	isset($variantInfo['sellerReference'])?$variantInfo['sellerReference']:'',
						'inventory'		=>	isset($variantInfo['qty'])?$variantInfo['qty']:'',
						'price'			=>	isset($variantInfo['sellingPrice'])?$variantInfo['sellingPrice']:'',
						'color'			=>	isset($variantInfo['color'])?$variantInfo['color']:'',
						'size'			=>	isset($variantInfo['size'])?$variantInfo['size']:'',
						'state'			=>	isset($variantInfo['state'])?$variantInfo['state']:'',
						//'ean'			=>	isset($variantInfo['eanVariant'])?$variantInfo['eanVariant']:'',
					);

					$variantInfo = $pmProductAddVariation->find('add_id=:add_id AND son_sku=:son_sku', array(':add_id'=>$addID, ':son_sku'=>$k));

					if($variantInfo){
						$variantInfo->updateByPk($variantInfo->id, $variationAddData);
					}else{
						$pmProductAddVariation->getDbConnection()->createCommand()->insert($pmProductAddVariation->tableName(), $variationAddData);
					}
				}

				//json格式到一张表
				$extendAddData=array(
					'add_id'=>$addInsertID,
					'product_desc'=>json_encode($pmProduct),
					'advert_desc'=>json_encode($pmAdvert),
				);
				if($addID){
					$pmProductAddExtend->getDbConnection()->createCommand()->update($pmProductAddExtend->tableName(), $extendAddData, "add_id={$addID}");
				}else{
					$pmProductAddExtend->getDbConnection()->createCommand()->insert($pmProductAddExtend->tableName(), $extendAddData);
				}
				$dbtransaction->commit();

			}catch (Exception $ex){

				$dbtransaction->rollback();
				echo $ex->getMessage();
			}

			if($action == 'update'){
				$navTabId = 'priceminister_product_add_list';
			}else {
				$navTabId = 'page' . UebModel::model('Menu')->getIdByUrl('/priceminister/priceministerproductadd/productaddstepfirst');
			}
			echo $this->successJson(array(
				'message' => Yii::t('system', 'Save successful'),
				'navTabId'	=>	$navTabId,
			));

		}catch (Exception $e){
			echo $this->failureJson(array(
				'message'=>$e->getMessage(),
			));
		}
		Yii::app()->end();
	}

	//更新
	public function actionUpdate(){
		try{
			$addID = Yii::app()->request->getParam('add_id');
			$conditions = array("id"=>$addID);
			$addInfo = PriceministerProductAdd::model()->getPmProductAddInfo($conditions);
			if(empty($addInfo)){
				throw new Exception(Yii::t('priceminister', 'The product add record not find'));
			}

			//只有待上传和失败的可以修改
			if(!in_array($addInfo['status'], array(PriceministerProductAdd::STATUS_PENDING, PriceministerProductAdd::STATUS_FAILURE))){
				throw new Exception(Yii::t('priceminister', 'Only pending and failure status can modify'));
			}
			$listingTypeArr = PriceministerProductAdd::getListingType();
			$sku = $addInfo['sku'];

			//获取帐号
			$accountInfo = PriceministerAccount::getAccountInfoByIds(array($addInfo['account_id']));
			$accountList = array();
			foreach ($accountInfo as $account){
				$accountList[$account['id']] = $account;
			}
			$listingParam = array(
				'listing_type'      => array('id' => $addInfo['listing_type'], 'text' => $listingTypeArr[$addInfo['listing_type']]),
				'listing_account'   => $accountList,
			);

			/**@ 获取产品图片*/
//			$skuImg = array();
//			if($addInfo['main_image'])
//				$skuImg['zt'][] = $addInfo['main_image'];
//			if($addInfo['extra_images']){
//				$skuImg['ft'] = explode("|", $addInfo['extra_images']);
//			}

            if($addInfo['main_image']) {
                $skuImg['zt'][$addInfo['main_image']] = ProductImageAdd::getImageUrlFromRestfulByFileName($addInfo['main_image'], $sku, $type = 'normal', $width = 100, $height = 100, $platform = Platform::CODE_PM);
            }

            if($addInfo['extra_images']){
                $tmpImageArray = explode("|", $addInfo['extra_images']);
                foreach($tmpImageArray as $image) {
                    $skuImg['ft'][$image] = ProductImageAdd::getImageUrlFromRestfulByFileName($image, $sku,  $type = 'normal', $width = 100, $height = 100, $platform = Platform::CODE_PM);
                }
            }

            $listingProduct = array(
				'sku'           => $sku,
				'skuImg'        => $skuImg,
				'skuInfo'       => $addInfo,
			);

			$pmProductAddExtend = new PriceministerProductAddExtend();
			$pmExtend = $pmProductAddExtend->getDbConnection()->createCommand()
							->select('*')
							->from($pmProductAddExtend->tableName())
							->where('add_id ='.$addID)
							->queryRow();

			$pmExtend['product_desc'] = json_decode($pmExtend['product_desc'],true);
			$pmExtend['advert_desc'] = json_decode($pmExtend['advert_desc'],true);


			$listingCate = PriceministerProductTypeTemplate::model()->getOneByCondition('attribute','type_id='.$addInfo['type_id']);
			//$listingCate = PriceministerProductTypeTemplate::model()->getOneByCondition('attribute','type_id=129');
			if(!$listingCate){
				throw new Exception('没有找到类目模版');
			}
			$listingCate['attribute'] = json_decode($listingCate['attribute']);

			$this->render('update', array(
				'template'	=>	$listingCate['attribute'],
				'listingParam'	=>	$listingParam,
				'listingProduct' => $listingProduct,
				'action'		=>	'update',
				'addID'			=>	$addID,
				'type_id'		=>	$addInfo['type_id'],
				'pmExtend'		=>	$pmExtend,
				'advertVariant'		=>	$pmExtend['advert_desc'],
				'productDesc'		=>	$pmExtend['product_desc'],
			));
		}catch (Exception $e){
			echo $this->failureJson(array(
			'message' => $e->getMessage(),
			'navTabId' => UebModel::model('Menu')->getIdByUrl('/priceminister/priceministeradd/index'),
			));
			Yii::app()->end();
		}
	}

	/**
	 * @desc 获取可用的账户列表
	 */
	public function actionGetableaccount(){

		$sku = Yii::app()->request->getParam('sku');
		$accounts = PriceministerProductAdd::model()->getAbleAccountsBySku($sku);
		echo json_encode($accounts);exit;
	}
} 