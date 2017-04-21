<?php
/**
 * @desc 产品刊登
 * @author lihy
 *
 */
class WishproductaddController extends UebController{
	public function accessRules(){
		return array(
				array(
					'allow', 
					'users'=>'*', 
					'actions'=>array('index', 'add', 'getableaccount', 'addinfo'))
		);
	}
	/**
	 * @desc 刊登产品时选择对应SKU
	 */
	public function actionIndex(){
		$this->render('index', array('dialog'=>Yii::app()->request->getParam('dialog')));
	}
	
	public function actionAdd(){
		$sku = Yii::app()->request->getParam('sku');
        $account_id = Yii::app()->request->getParam('account_id');
        $warehouse_id = Yii::app()->request->getParam('warehouse_id');
        $sku = trim($sku);
        $match = preg_match('/[^\w\d\.]+/i', $sku, $matches);
        if ($match) {
            echo $this->failureJson(array('message'=>Yii::t('wish_listing', ' Invalid SKU')));
            Yii::app()->end();
        }


		//刊登类型
		// ... 
		$listingType = WishProductAdd::getListingType();
		//sku信息
		$skuInfo = Product::model()->getProductInfoBySku($sku);
		if(empty($skuInfo)){
			echo $this->failureJson(array('message'=>Yii::t('wish_listing', 'Not found the sku')));
			Yii::app()->end();
		}


   /*     // 关联保护
        $hasSharedAccount = false;
        $sellerProductRelation = ProductToSellerRelation::model()->findSellerListBySku($sku);
		$sellerAccounts = ProductMarketersManager::model()->findSellerAccountsByPlatform(Platform::CODE_WISH,
            array_map(function ($e){
                return $e['seller_id'];
            }, $sellerProductRelation));
		if ($sellerAccounts) {
            $sellerRelations = WishAccountRelations::model()->findRelatedSellerByAccountId(array_keys($sellerAccounts));

            $relatedSellerIds = array();
            foreach ($sellerRelations as $relation) {
                $relatedSellerIds[] = $relation['seller_id'];
            }

            if (in_array(Yii::app()->user->id, $relatedSellerIds)) {
                $hasSharedAccount = true;
            }
        }
        //检测是否有权限去刊登该sku
        //上线后打开注释---lihy 2016-05-10
        if (!$hasSharedAccount) {
            if (!Product::model()->checkCurrentUserAccessToSaleSKU($sku, Platform::CODE_WISH)) {
                echo $this->failureJson(array(
                    'message' => Yii::t('system', 'Not Access to Add the SKU')
                ));
                Yii::app()->end();
            }
        }*/



        // 更改为拉取JAVA组图片API接口 ketu.lai
        $skuImg = ProductImageAdd::getOrPushImageUrlFromRestfulBySku($skuInfo, true, null, 'normal', 100, 100, Platform::CODE_WISH);
        /**
         * 修复java api接口无主图返回问题
         */
        if (isset($skuImg['ft']) && !isset($skuImg['zt'])) {
            $skuImg['zt'] = $skuImg['ft'];
        }

		//刊登模式
		$this->render('add',   array(
				'sku'           => $sku,
                'account_id'    => $account_id,
                'warehouse_id'  => $warehouse_id,
				'skuInfo'       => $skuInfo,
				'skuImg'        => $skuImg,
				'listingType'	=>	$listingType
		));		
	}
	private function preg_replace_call_func($match){
		if(in_array(strtolower($match['TT']), array('</p>', '<p/>', '<br/>', '</br>', '<br />'))){
			return "\n";
		}else{
			return '';
		}
	}

    public function actionAddinfo(){
        try {
            $warehouseList = array();    //发货仓库列表
            $saveType = WishProductAdd::SAVE_TYPE_ALL;
            $sku = Yii::app()->request->getParam('sku');
            $accounts = Yii::app()->request->getParam('accounts');

            $skuInfo = Product::model()->getProductInfoBySku($sku);
            if(!$skuInfo){
                throw new\Exception(Yii::t('wish', 'Invalid SKU'));
            }
            if (!$accounts) {
                throw new\Exception(Yii::t('wish', 'Please select account'));
            }
            $parentSku = $sku;
            if($skuInfo['product_is_multi'] == WishProductAdd::PRODUCT_IS_SINGLE){
                $skuInfo['product_is_multi'] = WishProductAdd::PRODUCT_IS_NORMAL;
            }

            if(!empty($skuInfo['description'])){
                foreach ($skuInfo['description'] as $key=>$val){
                    $val = preg_replace_callback("/(?<TT><\/?(?<TAG>\w+).*?>)/im", array($this, 'preg_replace_call_func'), $val);
                    $val = preg_replace("/(\n){2,}|\r\n/ie", "\n", $val);
                    $val = strip_tags($val);
                    $skuInfo['description'][$key] = $val;
                }
            }
            //去除included里面html标签
            if(!empty($skuInfo['included'])){
                foreach ($skuInfo['included'] as $key=>$val){
                    $val = preg_replace_callback("/(?<TT><\/?(?<TAG>\w+).*?>)/im", array($this, 'preg_replace_call_func'), $val);
                    $val = preg_replace("/(\n){2,}|\r\n/ie", "\n", $val);
                    $val = strip_tags($val);
                    $skuInfo['included'][$key] = $val;
                }
            }
            //刊登模式暂时不在这里起作用
            $listingType = Yii::app()->request->getParam('listing_type', WishProductAdd::LISTING_TYPE_VARIATION);
            if($skuInfo['product_is_multi'] == WishProductAdd::PRODUCT_IS_NORMAL){
                $listingType = WishProductAdd::LISTING_TYPE_FIXEDPRICE;
            }else{
                $listingType = WishProductAdd::LISTING_TYPE_VARIATION;
            }
            //验证主sku
            Product::model()->checkPublishSKU($listingType,$skuInfo);

            //获取刊登类型
            $listingTypeArr = WishProductAdd::getListingType();

            /**@ 获取产品信息*/

            // 更改为拉取JAVA组图片API接口 ketu.lai
            //$skuImg = ProductImageAdd::getImagesFromRemoteAddressBySku($sku);
            $skuImg = ProductImageAdd::getImageUrlFromRestfulBySku($sku,  null,  'normal',  100,  100,  Platform::CODE_WISH);
            /**
             * 修复java api接口无主图返回问题
             */
            if (isset($skuImg['ft']) && !isset($skuImg['zt'])) {
                $skuImg['zt'] = $skuImg['ft'];
            }

            $listings = WishListing::model()->findAllByAttributes(array('sku'=> $sku));
            $tags = array();
            foreach($listings as $listing) {
                $tags = array_merge($tags, explode(",", $listing['tags']));
            }
            $tags = array_unique($tags);
            //获取账号
            $accountInfo = WishAccount::getAccountInfoByIds($accounts);
            $accountList = array();
            $remoteImagesList = array();

            // 关联保护
            $sellerId = Yii::app()->user->id;
            $accountRelations = WishAccountRelations::model()->findRelationsBySellerId($sellerId);

            $relatedAccounts = array();
            foreach ($accountRelations as $account) {
                $relatedAccounts[] = $account['account_id'];
            }

            foreach($accountInfo as $i=> $account) {
                $accountId = $account['id'];
                if ($i == 0) {
                    $firstAccountId = $account['id'];
                }

                if (!in_array($accountId, $relatedAccounts)) {
                    //检测是否有权限去刊登该sku
                    if (!Product::model()->checkCurrentUserAccessToSaleSKUNew($sku, $accountId, Platform::CODE_WISH)) {
                        throw new \Exception(Yii::t('system', 'Not Access to Add the SKU' .
                            $account['account_name']));
                    }
                }


                //判断是否在刊登列表里面了
                $parentAddSkuInfo = WishProductAdd::model()->getProductAddInfo("parent_sku=:parent_sku AND account_id=:account_id",
                    array(':parent_sku' => $parentSku, ':account_id' => $account['id']));


                //已经添加过的对应的值
                if ($parentAddSkuInfo) {
                    //subject、description、brand、tags
                    $account['title'] = $parentAddSkuInfo['name'];
                    $account['brand'] = $parentAddSkuInfo['brand'];
                    $account['warehouse_id'] = $parentAddSkuInfo['warehouse_id'];
                    $account['description'] = $parentAddSkuInfo['description'];
                    //$account['tags'] = explode(",", $parentAddSkuInfo['tags']);
                    $account['add_msg'] = "";
                    if ($parentAddSkuInfo['upload_status'] != 0) {
                        $account['is_add'] = true;
                        $listingInfo = UebModel::model('WishListing')->find('sku=:sku AND account_id=:account_id and enabled=:enabled',
                            array(':sku' => $sku, ':account_id' => $accountId, ':enabled' => 1));
                        if ($listingInfo) {
                            $account['is_add'] = true;
                        } else {
                            $listingInfo = UebModel::model('WishListing')->find('sku=:sku AND account_id=:account_id and enabled=:enabled',
                                array(':sku' => $sku, ':account_id' => $accountId, ':enabled' => 0));
                            if ($listingInfo) {
                                $account['is_add'] = false;
                            }
                        }
                    } else {
                        $account['is_add'] = false;
                        $account['add_msg'] = "该sku存在待刊登列表里面待上传";
                        //判断是否已经在wish产品列表中
                        $listingInfo = UebModel::model('WishListing')->find('sku=:sku AND account_id=:account_id and enabled=:enabled',
                            array(':sku' => $sku, ':account_id' => $accountId, ':enabled' => 1));
                        if ($listingInfo) {
                            $account['is_add'] = true;
                        } else {
                            $listingInfo = UebModel::model('WishListing')->find('sku=:sku AND account_id=:account_id and enabled=:enabled',
                                array(':sku' => $sku, ':account_id' => $accountId, ':enabled' => 0));
                            if ($listingInfo) {
                                $account['is_add'] = false;
                            }
                        }
                    }
                    //$saveType = WishProductAdd::SAVE_TYPE_ONLY_SUBSKU;
                } else {
                    $account['title'] = !empty($skuInfo['title']['english']) ? $skuInfo['title']['english'] : '';
                    $account['brand'] = !empty($skuInfo['brand_info']['brand_en_name']) ? $skuInfo['brand_info']['brand_en_name'] : '';
                    $account['tags'] = array();
                    $account['is_add'] = false;
                    $account['description'] = !empty($skuInfo['description']['english']) ? "Description:\n" . $skuInfo['description']['english'] : '';
                    if (!empty($skuInfo['included']['english'])) {
                        $account['description'] .= "\nIncluded:\n" . $skuInfo['included']['english'];
                    }
                    //判断是否已经在wish产品列表中
                    $listingInfo = UebModel::model('WishListing')->find('sku=:sku AND account_id=:account_id',
                        array(':sku' => $sku, ':account_id' => $accountId));
                    if ($listingInfo && $listingInfo['enabled'] == 1) {
                        $account['is_add'] = true;
                        //$saveType = WishProductAdd::SAVE_TYPE_NO_MAIN_SKU;
                    }
//                    if ($listingInfo) {
//                        $account['tags'] = explode(",", $listingInfo['tags']);
//                    }
                    unset($listingInfo);
                }



                $randTags = array();
                foreach(array_rand($tags, 10) as $key) {
                    $randTags[] = $tags[$key];
                }
                $account['tags'] = $randTags;

                //array_rand($tags, 10);
                //补全10个
                $tagsNum = count($account['tags']);
                $diffNum = (10 - $tagsNum);
                if ($diffNum > 0) {
                    for ($i = $diffNum; $i > 0; $i--) {
                        $account['tags'][] = '';
                    }
                }


                $accountList[$account['id']] = $account;
                if ($accountList[$accountId]['is_add']) {
                    if (!empty($accountList[$accountId]['add_msg'])) {
                        $message = $accountList[$accountId]['add_msg'];
                    } else {
                        $message = Yii::t('wish_listing', 'Had upload the SKU, '.$account['account_name']);
                    }
                    throw new \Exception($message);
                }
                unset($account);

                try {
                    $remoteImages = ProductImageAdd::getSkuImageUpload($accountId, $sku, array(),
                        Platform::CODE_WISH);

                    foreach($remoteImages['result']['imageInfoVOs'] as $image) {
                        $remoteImagesList[] = $image['remotePath'];
                    }

                }catch (\Exception $e){

                }

            }//loop account end

            $listingParam = array(
                'listing_type'    => array('id' => $listingType, 'text' => $listingTypeArr[$listingType]),
                'listing_account' => $accountList,
            );

            //$skuInfo['is_add'] = $accountList[$accountId]['is_add'];
            $listingProduct = array(
                'parentSku'	=> $parentSku,
                'sku'           => $sku,
                'skuImg'        => $skuImg,
                'skuInfo'       => $skuInfo,
            );
            //获取子SKU信息
            $listingSubSKU = WishProductAdd::model()->getSubProductByMainProductId($skuInfo['id'], $skuInfo['product_is_multi']);

            //获取物流方式列表
            $logisticsList = WishProductAdd::$logisticsType;
            $warehouseList = WishOverseasWarehouse::model()->getWarehouseList();

            $shipCode = Yii::app()->request->getParam('ship_code');
            if(!$shipCode) $shipCode = Logistics::CODE_CM_GZ_WISH;//默认物流方式
            $shipWarehouseID = isset($skuInfo['warehouse_id']) ? $skuInfo['warehouse_id'] : 41;	//默认本地仓
            //增加价格统计
            $pricesNote = array();
            //多属性
            if($listingSubSKU['skuList']){
                foreach ($listingSubSKU['skuList'] as $key=>$skuRow){
                    // 2016-11-17 新修改
                    $upload_status = 0;
                    $salePrice = array();
                    $listingSubSKU['skuList'][$key]['skuInfo']['upload_status'] = $upload_status;

                    if($skuRow['sku'] != $sku){
                        // 更改为拉取JAVA组图片API接口 ketu.lai
                        // Product::model()->getImgList($skuRow['sku'], 'ft');
                        $images = ProductImageAdd::getImageUrlFromRestfulBySku($skuRow['sku'], 'ft',  'normal',  100,  100, Platform::CODE_WISH);
                        $listingProduct['skuImg']['xt'] = $images;
                    }

                    $salePrice = WishProductAdd::model()->getSalePrice($skuRow['sku'], $firstAccountId, $shipCode, $shipWarehouseID);
                    $listingSubSKU['skuList'][$key]['skuInfo']['shipping'] = $salePrice['shipPrice'];
                    $listingSubSKU['skuList'][$key]['skuInfo']['market_price'] = ceil($salePrice['salePrice']*1.8);
                    $listingSubSKU['skuList'][$key]['skuInfo']['product_cost'] = $salePrice['salePrice'];
                    $listingSubSKU['skuList'][$key]['skuInfo']['inventory'] = WishProductAdd::PRODUCT_PUBLISH_INVENTORY;
                    $listingSubSKU['skuList'][$key]['skuInfo']['price_error'] = $salePrice['errormsg'];
                    $listingSubSKU['skuList'][$key]['skuInfo']['price_profit'] = '利润:<b>'.$salePrice['profit'].'</b>，<br />利润率:<b>'.$salePrice['profitRate'].'</b>';
                }
            }else{
                //单品
                $salePrice = WishProductAdd::model()->getSalePrice($listingProduct['skuInfo']['sku'], $firstAccountId, $shipCode, $shipWarehouseID);
                $listingProduct['skuInfo']['shipping'] = $salePrice['shipPrice'];
                $listingProduct['skuInfo']['market_price'] = round($salePrice['salePrice']*1.8, 2);
                $listingProduct['skuInfo']['product_cost'] = $salePrice['salePrice'];
                $listingProduct['skuInfo']['inventory'] = WishProductAdd::PRODUCT_PUBLISH_INVENTORY;
                //在账号只有一个的情况下
                //$listingProduct['skuInfo']['upload_status'] = $accountList[$accountId]['is_add'];
                $listingProduct['skuInfo']['price_error'] = $salePrice['errormsg'];
                $upload_status = 0;
                $listingProduct['skuInfo']['upload_status'] = $upload_status;
                $listingProduct['skuInfo']['price_profit'] = '利润:<b>'.$salePrice['profit'].'</b>，<br />利润率:<b>'.$salePrice['profitRate'].'</b>';
                // $listingProduct['skuInfo']['profit_desc'] = '<a href="javascript:;" onClick="alertMsg.confirm(\''.$salePrice['desc'].'\')">查看详情</a>';

            }

            $this->render('addinfo', array(
                'shipCode'		=>	$shipCode,
                'cur_account_id' =>  $firstAccountId,
                'logisticsList'	=>	$logisticsList,
                'remoteImages'=> $remoteImagesList,
                'listingParam'	=>	$listingParam,
                'listingProduct' => $listingProduct,
                'listingSubSKU'	=>	$listingSubSKU['skuList'],
                'attributeList'	=>	$listingSubSKU['attributeList'],
                'action'		=>	'add',
                'saveType'		=>	$saveType,
                'isSubSku'		=>	$parentSku == $sku ? false : true,
                'pricesNote'	=>	$pricesNote,
                'warehouseList' =>  $warehouseList
            ));

        }catch (\Exception $e){
            echo $this->failureJson(array(
                'message' => Yii::t('wish', $e->getMessage()),
            ));
        }

    }

	
	public function actionUpdate(){
		$addId = Yii::app()->request->getParam('add_id');
		$saveType = 0;
		$profitInfo = '';
		$profitError = '';
		$warehouseList = array();
		$currency = WishProductAdd::PRODUCT_PUBLISH_CURRENCY; //货币
		//获取待刊登中的信息
		$wishProductAddModel = new WishProductAdd;
		$productInfo = $wishProductAddModel->getProductAddInfo('id=:id', array(':id'=>$addId));
		if(empty($productInfo)){
			echo $this->failureJson(array(
					'message' => Yii::t('wish_listing', 'Invalide SKU'),
			));
			Yii::app()->end();
		}

		$sku = $parentSku = $productInfo['parent_sku'];	
		$shipCode = Logistics::CODE_CM_GZ_WISH;//默认物流方式
		$shipWarehoustID = $productInfo['warehouse_id'];	
		$accountId = $productInfo['account_id'];	
		
		//获取账号
		$accountInfo = WishAccount::getAccountInfoByIds(array($productInfo['account_id']));
		$accountList = array();
		foreach ($accountInfo as $account){
			//已经添加过的对应的值
			//subject、description、brand、tags
			$account['title'] = $productInfo['name'];
			$account['brand'] = $productInfo['brand'];
			$account['warehouse_id'] = $productInfo['warehouse_id'];
			$account['description'] = $productInfo['description'];
			$account['tags'] = $productInfo['tags'] ? explode(",", $productInfo['tags']) : array();
			//补全10个
			$tagsNum = count($account['tags']);
			$diffNum = (10-$tagsNum);
			if($diffNum > 0){
				for ($i=$diffNum;$i>0;$i--)
				{
					$account['tags'][] = '';
				}	
			}
			$accountList[$account['id']] = $account;
		}
		$listingParam = array(
				'listing_type'      => array('id' => '', 'text' => ''),
				'listing_account'   => $accountList,
		);
		/**@ 获取产品图片*/
		$skuImg = array();
		$selectedImg = array();
		if($productInfo['main_image'])
		{
			$selectedImg['zt'][] = $productInfo['main_image'];
		}if($productInfo['extra_images']){
			$selectedImg['ft'] = explode("|", $productInfo['extra_images']);
		}

        /**@ 获取产品信息*/
        // 更改为拉取JAVA组图片API接口 ketu.lai
        $skuImg = ProductImageAdd::getImageUrlFromRestfulBySku($sku,  null, 'normal', 100, 100, Platform::CODE_WISH);
        /**
         * 修复java api接口无主图返回问题
         */
        if (isset($skuImg['ft']) && !isset($skuImg['zt'])) {
            $skuImg['zt'] = $skuImg['ft'];
        }
        $remoteImagesList = array();
        try {
            $remoteImages = ProductImageAdd::getSkuImageUpload($productInfo['account_id'], $sku, array(),
                Platform::CODE_WISH);

            foreach($remoteImages['result']['imageInfoVOs'] as $image) {

                $remoteImagesList[] = $image['remotePath'];
            }

        }catch (\Exception $e){

        }
		$listingProduct = array(
				'parentSku'		=> $parentSku,
				'sku'           => $sku,
				'skuImg'        => $skuImg,
				'skuInfo'       => $productInfo,
				'selectedImg'	=> $selectedImg
		);
		
		//获取子SKU信息（单品为空）
		$listingSubSKU = $wishProductAddModel->getProductVariantsByProductAddId($addId);
		if(!$listingSubSKU || !$listingSubSKU['skuList']){
			echo $this->failureJson(array(
					'message' => Yii::t('wish_listing', 'Invalide Product Variants'),
			));
			Yii::app()->end();
		}		


		if($productInfo['product_is_multi'] == 2){
			//多属性
			foreach ($listingSubSKU['skuList'] as $key=>$skuRow){
				//
				if($skuRow['sku'] == $sku) continue;

                $images = ProductImageAdd::getImageUrlFromRestfulBySku($skuRow['sku'], 'ft',  'normal',  100,  100,  Platform::CODE_WISH);

                $listingProduct['skuImg']['xt'] = $images;
//
//				$images = Product::model()->getImgList($skuRow['sku'], 'ft');
//				foreach($images as $k=>$img){
//					//@todo 过滤掉第一张小图
//					/* $filename = basename($img);
//					if($filename == $sku.".jpg") continue; */
//					$listingProduct['skuImg']['xt'][$k] = $config['oms']['host'].$img;
//				}

				//利润计算
				$profitInfo = '';
				$profitError = '';
		        $salePriceInfo = WishProductAdd::getProfitInfo($skuRow['skuInfo']['product_cost'], $skuRow['sku'], $currency, $skuRow['skuInfo']['shipping'], $shipWarehoustID);
		        if($salePriceInfo){	        	
		        	$profitInfo = '利润:<b>'.$salePriceInfo['profit'].'</b>，<br />利润率:<b>'.$salePriceInfo['profitRate'].'</b>';
		        	$profitError = $salePriceInfo['errormsg'];
		        }				
				
				$listingSubSKU['skuList'][$key]['skuInfo']['market_price']       = $skuRow['skuInfo']['msrp'];
				$listingSubSKU['skuList'][$key]['skuInfo']['product_cost']       = $skuRow['skuInfo']['price'];
				$listingSubSKU['skuList'][$key]['skuInfo']['shipping']           = $skuRow['skuInfo']['shipping'];
				$listingSubSKU['skuList'][$key]['skuInfo']['inventory']          = $skuRow['skuInfo']['inventory'];
				$listingSubSKU['skuList'][$key]['skuInfo']['upload_status_text'] = $skuRow['skuInfo']['upload_status_text'];				
				$listingSubSKU['skuList'][$key]['skuInfo']['price_profit']       = $profitInfo;			
				$listingSubSKU['skuList'][$key]['skuInfo']['price_error']        = $profitError;
				
			}
		}else{	
			//单品子SKU
			$productVariationInfo = $listingSubSKU['skuList'][$productInfo['parent_sku']];
			$listingSubSKU['skuList'] = array();	//清空数据，作为单品/多属性标识
			//利润计算
	        $salePriceInfo = WishProductAdd::getProfitInfo($productVariationInfo['skuInfo']['price'], $productVariationInfo['sku'], $currency, $productVariationInfo['skuInfo']['shipping'], $shipWarehoustID);
	        if($salePriceInfo){	        	
	        	$profitInfo = '利润:<b>'.$salePriceInfo['profit'].'</b>，<br />利润率:<b>'.$salePriceInfo['profitRate'].'</b>';
	        	$profitError = $salePriceInfo['errormsg'];
	        }

			$listingProduct['skuInfo']['product_cost']       = $productVariationInfo['skuInfo']['price'];
			$listingProduct['skuInfo']['shipping']           = $productVariationInfo['skuInfo']['shipping'];
			$listingProduct['skuInfo']['market_price']       = $productVariationInfo['skuInfo']['msrp'];			
			$listingProduct['skuInfo']['inventory']          = $productVariationInfo['skuInfo']['inventory'];
			$listingProduct['skuInfo']['upload_status_text'] = $productVariationInfo['skuInfo']['upload_status_text'];				
			$listingProduct['skuInfo']['price_profit']       = $profitInfo;		
			$listingProduct['skuInfo']['price_error']        = $profitError;	
		}

		if($productInfo['upload_status'] != WishProductAdd::WISH_UPLOAD_SUCCESS){
			$saveType = WishProductAdd::SAVE_TYPE_ALL;
		}elseif($listingSubSKU['hasUploadFailNum']>0){
			$saveType = WishProductAdd::SAVE_TYPE_ONLY_SUBSKU;
		}

		$warehouseList = WishOverseasWarehouse::model()->getWarehouseList();

		$this->render('update', array(
				'listingParam'   =>	$listingParam,
				'listingProduct' => $listingProduct,
				'listingSubSKU'  =>	$listingSubSKU['skuList'],
				'attributeList'  =>	$listingSubSKU['attributeList'],
				'action'         =>	'update',
				'remoteImages'=> $remoteImagesList,
				'isSubSku'       =>	false,
				'saveType'       =>	$saveType,
				'addId'          =>	$addId,
				'currentNavTab'  =>	'page'.UebModel::model('Menu')->getIdByUrl('/wish/wishproductaddlist/index'),
				'warehouseList'  => $warehouseList,
				'cur_account_id' => $accountId,
		));
	}
	
	/**
	 * 删除子sku
	 */
	public function actionDelvariant(){
		$addId = Yii::app()->request->getParam('add_id');
		$sku = Yii::app()->request->getParam('sku');
		try{
			if(empty($addId) || empty($sku)){
				throw new Exception(Yii::t('wish_listing', 'Param error'));
			}
			$wishProductVariantModel = new WishProductVariantsAdd;
			if($wishProductVariantModel->deleteProductVariant('add_id=:add_id AND sku=:sku', 
															array(':add_id'=>$addId, ':sku'=>$sku))){
				echo $this->successJson(array(
						'message' => Yii::t('system', 'Delete successful'),
				));
				Yii::app()->end();
			}else{
				throw new Exception(Yii::t('system', 'Delete failure'));
			}
		}catch(Exception $e){
			echo $this->failureJson(array(
					'message' => $e->getMessage(),
			));
			Yii::app()->end();
		}
		
	}

	/**
	 * @desc 保存数据信息
	 */
	private function _saveinfo1(){
		try{
			//1、sku信息与主要信息一起保存
			//2、待刊登表中已有主数据而且只保存sku数据
			//4、待刊登表中没有主数据而且只保存sku数据
			$productIsMulti   = Yii::app()->request->getParam('product_is_multi');
			$addID            = Yii::app()->request->getParam('add_id');
			$parentSku        = Yii::app()->request->getParam('parent_sku');
			$sku              = Yii::app()->request->getParam('sku');
			$action           = Yii::app()->request->getParam('action');
			$skuImage         = Yii::app()->request->getParam('skuImage');
			$skuinfo          = Yii::app()->request->getParam('skuinfo');
			$wishAddSelupload = Yii::app()->request->getParam('wish_add_selupload');	//过滤掉未勾选的子SKU

			$MainskuInfo = Product::model()->getProductInfoBySku($sku);	//主SKU信息

			//获取刊登主表信息
			if($action == 'update'){
				$wishProductAddModel = new WishProductAdd;
				$productInfo = $wishProductAddModel->getProductAddInfo('id=:id', array(':id'=>$addID));
				if(empty($productInfo)){
					throw new Exception(Yii::t('wish_listing', 'Param error'));
				}
			}
			
			//重新定义单品/多属性（因界面支持单品变为多属性）
			if (count($wishAddSelupload) > 1){
				$productIsMulti = WishProductAdd::PRODUCT_IS_VARIOUS;
			}else{
				if(count($wishAddSelupload) == 1){
					if (($wishAddSelupload[0]) == $MainskuInfo['sku']){
						$productIsMulti = WishProductAdd::PRODUCT_IS_NORMAL;
					}else{
						$productIsMulti = WishProductAdd::PRODUCT_IS_VARIOUS;
					}
				}
			}		
		
			//验证主sku
			Product::model()->checkPublishSKU($productIsMulti,$MainskuInfo);

			$mainImg = '';
			$extraImg = '';
			if($skuImage){
                // 修改为JAVA组API
                if (isset($skuImage['main']) && $skuImage['main']) {
                    foreach($skuImage['main'] as $fileName) {
                        $mainImg = $fileName;
                        break;
                    }
                }
                if (isset($skuImage['extra'])  && $skuImage['extra']) {
                    $extraImageArray = array();
                    $indexer = 0;
                    foreach($skuImage['extra'] as $fileName) {
                        if (!$mainImg && $indexer == 0) {
                            $mainImg = $fileName;
                        } else {
                            $extraImageArray[] = $fileName;
                        }
                        $indexer++;
                    }
                    $extraImg = implode("|", $extraImageArray);
                }
			}

			//baseinfo
			$baseinfo = Yii::app()->request->getParam('baseinfo');
			//accounts
			$accounts = Yii::app()->request->getParam('account_id');

			if(!$parentSku || !$sku || !$skuinfo){
				throw new Exception(Yii::t('wish_listing', 'Param error'));
			}
			if(empty($accounts)){
				throw new Exception(Yii::t('wish_listing', 'Account Error'));
			}

			if($action == 'add' && empty($wishAddSelupload)){
				throw new Exception(Yii::t('wish_listing', 'Anyhow need to chose one SKU'));
			}	

			//检查sku信息是否填写完整
			foreach ($skuinfo as $k=>$subSku){
				if($action == 'add' && !in_array($k, $wishAddSelupload)){ 
					unset($skuinfo[$k]);
					continue;
				}
				$subSku['price'] = floatval($subSku['price']);
				$subSku['market_price'] = floatval($subSku['market_price']);
				$subSku['shipping'] = floatval($subSku['shipping']);
				if(!is_numeric($subSku['inventory']) 
					|| empty($subSku['price'])
					|| empty($subSku['market_price'])){
					throw new Exception(Yii::t('wish_listing', 'Please check sku info'));
				}
				if (strlen($subSku['size']) > 30){
					throw new Exception(Yii::t('wish_listing', 'Sub SKU size more than 30 characters'));
				}

				$subSku['inventory'] = intval($subSku['inventory']);
				$skuinfo[$k] = $subSku;
			}
			
			//当主SKU未上传成功时，检查基本信息是否填写完整
			if ($action == 'add' || ($action == 'update' && $productInfo['upload_status'] != WishProductAdd::WISH_UPLOAD_SUCCESS)){
				if($baseinfo){
					foreach ($baseinfo as $info){
						if(empty($info['subject']) || empty($info['detail']))
							throw new Exception(Yii::t('wish_listing', 'Please Check Subject Or Description'));
						
						if(!empty($info['tags'])){
							foreach ($info['tags'] as $k=>$tag){
								if(empty($tag)) unset($info['tags'][$k]);
							}
						}
						if(empty($info['tags']))
							throw new Exception(Yii::t('wish_listing',  'Tags required'));
					}
				}elseif($sku == $parentSku){
					throw new Exception(Yii::t('wish_listing', 'Please Check Subject Or Description'));
				}
			}

			foreach ($accounts as $accountId){
				$tagstr = '';
				if($baseinfo[$accountId]['tags']){
					foreach ($baseinfo[$accountId]['tags'] as $tag){
						!empty($tag) && $tagstr.= $tag . ",";
					}
				}
				$baseinfo[$accountId]['tags'] = trim($tagstr, ',');
				$baseinfo[$accountId]['variants'] = $skuinfo;
				$baseinfo[$accountId]['add_id'] = $addID;
				$mainSku = array(
						'parent_sku' => $parentSku,
						'main_image' => $mainImg,
						'extra_images' => $extraImg,
						'product_is_multi'	=>	$productIsMulti
				);
				$baseinfo[$accountId] = array_merge($baseinfo[$accountId], $mainSku);
			}


			if($productIsMulti == WishProductAdd::PRODUCT_IS_SINGLE){
				$model = new WishProductVariantsAdd;
				$res = $model->saveWishAddVariantsData($baseinfo);
			}else{
				$model = new WishProductAdd;
				$res = $model->saveWishAddData($baseinfo);
			}

			/**---------------------韩翔宇  2017-03-28添加  开始------------------------------------**/
			//刊登完之后，把待刊登列表中状态改成刊登中
			WishWaitListing::model()->updateWaitingListingStatus(
				array(
					'create_user_id' => intval(Yii::app()->user->id),
					'account_id'     => $accountId,
					'sku'            => $sku,
					'create_time'    => date('Y-m-d H:i:s'),
					'warehouse_id'   => $baseinfo[$accountId]['warehouse_id']
				),
				WishWaitListing::STATUS_PROCESS //新增加的算刊登中
			);
			/**---------------------韩翔宇  2017-03-28添加  结束------------------------------------**/


			if(!$res){
				$model->getErrorMsg();
				throw new Exception(Yii::t('system', $model->getErrorMsg())); 
			}
			if($action == 'update'){
				$navTabId = 'page'.UebModel::model('Menu')->getIdByUrl('/wish/wishproductaddlist/index');
			}else{
				$navTabId = 'page'.UebModel::model('Menu')->getIdByUrl('/wish/wishproductadd/index');
			}
			
			echo $this->successJson(array(
					'message' => Yii::t('system', 'Save successful 1111'),
					'navTabId'	=>	$navTabId
			));
			Yii::app()->end();
		}catch(Exception $e){
			//Yii::t('system', 'Save failure')
			echo $this->failureJson(array(
					'message' => $e->getMessage() . ' 1111',
			));
			Yii::app()->end();
		}
	}
	/**
	 * @desc 只保存sku信息并且有主刊登信息
	 * @throws Exception
	 */
	private function _saveinfo2(){
		try{
			//1、sku信息与主要信息一起保存
			//2、待刊登表中已有主数据而且只保存sku数据
			//4、待刊登表中没有主数据而且只保存sku数据
			$productIsMulti = Yii::app()->request->getParam('product_is_multi');
			$addId = Yii::app()->request->getParam('add_id');
			//parent_sku
			$parentSku = Yii::app()->request->getParam('parent_sku');
			$sku = Yii::app()->request->getParam('sku');
			//action
			$action = Yii::app()->request->getParam('action');
			
			//skuinfo
			$skuinfo = Yii::app()->request->getParam('skuinfo');
			
			//accounts
			$accounts = Yii::app()->request->getParam('account_id');
			
			if(empty($addId)){
				throw new Exception(Yii::t('wish_listing', 'Param error: no add id'));
			}
			
			if(!$parentSku || !$sku || !$skuinfo){
				throw new Exception(Yii::t('wish_listing', 'Param error'));
			}
			if(empty($accounts)){
				throw new Exception(Yii::t('wish_listing', 'Account Error'));
			}
			//过滤掉未选择的子sku
			$wishAddSelupload = Yii::app()->request->getParam('wish_add_selupload');
			if($action == 'add' && empty($wishAddSelupload)){
				throw new Exception(Yii::t('wish_listing', 'Anyhow need to chose one SKU'));
			}
			//检查sku信息是否填写完整
			foreach ($skuinfo as $k=>$sku){
				if($action == 'add' && !in_array($k, $wishAddSelupload)){
					unset($skuinfo[$k]);
					continue;
				}
				$sku['price'] = floatval($sku['price']);
				$sku['market_price'] = floatval($sku['market_price']);
				$sku['shipping'] = floatval($sku['shipping']);
				if(!is_numeric($sku['inventory'])
				|| empty($sku['price'])
				|| empty($sku['market_price'])){
					throw new Exception(Yii::t('wish_listing', 'Please check sku info'));
				}
				$sku['inventory'] = intval($sku['inventory']);
				$skuinfo[$k] = $sku;
			}
			
			foreach ($accounts as $accountId){
				$baseinfo[$accountId]['add_id'] = $addId;
				$baseinfo[$accountId]['variants'] = $skuinfo;
				$baseinfo[$accountId]['parent_sku'] = $parentSku;
			}
			
			$model = new WishProductVariantsAdd;
			$res = $model->saveWishAddVariantsData($baseinfo);
			
			if(!$res){
				$model->getErrorMsg();
				throw new Exception(Yii::t('system', 'Save failure'));
			}
			if($action == 'update'){
				$navTabId = 'page'.UebModel::model('Menu')->getIdByUrl('/wish/wishproductaddlist/index');
			}else{
				$navTabId = 'page'.UebModel::model('Menu')->getIdByUrl('/wish/wishproductadd/index');
			}
				
			echo $this->successJson(array(
					'message' => Yii::t('system', 'Save successful'),
					'navTabId'	=>	$navTabId
			));
			Yii::app()->end();
		}catch(Exception $e){
			//Yii::t('system', 'Save failure')
			echo $this->failureJson(array(
					'message' => $e->getMessage(),
			));
			Yii::app()->end();
		}
	}
	/**
	 * @desc 没有保存主刊登信息并且只保存sku信息
	 * @throws Exception
	 */
	private function _saveinfo4(){
		try{
			//1、sku信息与主要信息一起保存
			//2、待刊登表中已有主数据而且只保存sku数据
			//4、待刊登表中没有主数据而且只保存sku数据
			//parent_sku
			$parentSku = Yii::app()->request->getParam('parent_sku');
			$sku = Yii::app()->request->getParam('sku');
			//action
			$action = Yii::app()->request->getParam('action');
			//skuinfo
			$skuinfo = Yii::app()->request->getParam('skuinfo');
			//accounts
			$accounts = Yii::app()->request->getParam('account_id');
			if(!$parentSku || !$sku || !$skuinfo){
				throw new Exception(Yii::t('wish_listing', 'Param error'));
			}
			if(empty($accounts)){
				throw new Exception(Yii::t('wish_listing', 'Account Error'));
			}
			//过滤掉未选择的子sku
			$wishAddSelupload = Yii::app()->request->getParam('wish_add_selupload');
			if($action == 'add' && empty($wishAddSelupload)){
				throw new Exception(Yii::t('wish_listing', 'Anyhow need to chose one SKU'));
			}
			//检查sku信息是否填写完整
			foreach ($skuinfo as $k=>$sku){
				if($action == 'add' && !in_array($k, $wishAddSelupload)){
					unset($skuinfo[$k]);
					continue;
				}
				$sku['price'] = floatval($sku['price']);
				$sku['market_price'] = floatval($sku['market_price']);
				$sku['shipping'] = floatval($sku['shipping']);
				if(!is_numeric($sku['inventory'])
				|| empty($sku['price'])
				|| empty($sku['market_price'])){
					throw new Exception(Yii::t('wish_listing', 'Please check sku info'));
				}
				$sku['inventory'] = intval($sku['inventory']);
				$skuinfo[$k] = $sku;
			}
			
			foreach ($accounts as $accountId){
				$baseinfo[$accountId]['variants'] = $skuinfo;
				//获取主信息
				$listingInfo = UebModel::model('WishListing')->find('account_id=:account_id AND sku=:sku', 
													array(':account_id'=>$accountId, ':sku'=>$parentSku));
				if(empty($listingInfo)){
					throw new Exception(Yii::t('wish_listing', 'Not found the sku'));
				}
				//获取描述信息
				$extendInfo = UebModel::model('WishListingExtend')->find('listing_id='.$listingInfo['id']);
				$mainSku = array(
						'parent_sku' => $parentSku,
						'online_sku'	=>	$listingInfo['parent_sku'],
						'main_image' => $listingInfo['main_image'],
						'remote_main_img'	=>	$listingInfo['main_image'],
						'extra_images' => $listingInfo['extra_images'],
						'remote_extra_img'	=>	$listingInfo['extra_images'],
						'product_is_multi'	=>	'',
						'detail'	=>	isset($extendInfo['description'])?$extendInfo['description']:'',
						'subject'	=>	$listingInfo['name'],
						'brand'	=>	$listingInfo['brand'],
						'tags'	=>	$listingInfo['tags'],
									
				);
				$baseinfo[$accountId] = array_merge($baseinfo[$accountId], $mainSku);
			}
			$model = new WishProductAdd;
			$res = $model->saveWishAddData($baseinfo, WishProductAdd::SAVE_TYPE_NO_MAIN_SKU);
			if(!$res){
				$model->getErrorMsg();
				throw new Exception(Yii::t('system', 'Save failure'));
			}
			if($action == 'update'){
				$navTabId = 'page'.UebModel::model('Menu')->getIdByUrl('/wish/wishproductaddlist/index');
			}else{
				$navTabId = 'page'.UebModel::model('Menu')->getIdByUrl('/wish/wishproductadd/index');
			}
				
			echo $this->successJson(array(
					'message' => Yii::t('system', 'Save successful'),
					'navTabId'	=>	$navTabId
			));
			Yii::app()->end();
		}catch(Exception $e){
			//Yii::t('system', 'Save failure')
			echo $this->failureJson(array(
					'message' => $e->getMessage(),
			));
			Yii::app()->end();
		}
	}
	
	public function actionSaveinfo(){
		//1、sku信息与主要信息一起保存
		//2、待刊登表中已有主数据而且只保存sku数据
		//4、待刊登表中没有主数据而且只保存sku数据
		
		//$this->_saveinfo1();
		//去除 2016-10-31
		// $saveType = Yii::app()->request->getParam('save_type');
		// switch($saveType){
		// 	case WishProductAdd::SAVE_TYPE_ALL:
		// 		$this->_saveinfo1();
		// 		break;
		// 	case WishProductAdd::SAVE_TYPE_ONLY_SUBSKU:
		// 		$this->_saveinfo2();
		// 		break;
		// 	case WishProductAdd::SAVE_TYPE_NO_MAIN_SKU:
		// 		$this->_saveinfo4();
		// 		break;
		// 	default:
		// 		echo $this->failureJson(array(
		// 				'message' => Yii::t('wish_listing', 'Illegal Operation'),
		// 		));
		// 		Yii::app()->end();
		// 		break;
		// }
		$this->_saveinfo1();
	}
	
	public function actionList(){
		$sku = Yii::app()->request->getParam('sku');
		echo $sku;
	}
	
	/**
	 * @desc 获取可用的账户列表
	 */
	public function actionGetableaccount(){		
		$sku            = Yii::app()->request->getParam('sku');
		$listingType    = Yii::app()->request->getParam('listing_type');
		$userLimit      = Yii::app()->request->getParam('user_limit',0);
		$accountLimit = array();
		$accountList = WishProductAdd::model()->getAbleAccountsBySku($sku);

		//销售人员只能看指定分配的账号数据		
        if ($userLimit > 0) {
            $accountIdArr = WishAccountSeller::model()->getListByCondition('account_id', 'seller_user_id = ' . Yii::app()->user->id);
            foreach($accountList as $account) {
                if (in_array($account['id'], $accountIdArr)) {
                    $accountLimit[$account['id']] = $account;
                }
            }
        }

		//没有限制的则看全部账号
        if (!$accountLimit) {
            $accountLimit = $accountList;
        }
        $accountIds = array_map(function ($e){
             return $e['id'];
        }, $accountLimit);

		// 关联保护
        $sellerId = Yii::app()->user->id;
        $accountRelations = WishAccountRelations::model()->findRelationsBySellerId($sellerId);

        $relatedAccounts = array();
        foreach ($accountRelations as $account) {
            if (!in_array($account['account_id'], $accountIds)) {
                $relatedAccounts[] = array(
                    'id' => $account['account_id'],
                    'short_name' => $account['account_name']
                );
            }
        }
        $accountLimit = array_merge($accountLimit, $relatedAccounts);

		echo json_encode($accountLimit);exit;
	}
	
	/**
	 * @desc 手动上传产品
	 * @throws Exception
	 */
	public function actionUploadproduct(){
		set_time_limit(200);
		ini_set("display_errors", true);
		error_reporting(E_ALL);
		$addId = Yii::app()->request->getParam("add_id");
		try{
			if(!$addId){
				throw new Exception(Yii::t('wish_listing', 'Param error'));
			}
			$productaddModel = new WishProductAdd;
			
			$data = $productaddModel->getProductAddInfo("id=:id", array(':id'=>$addId));
			if(empty($data)){
				throw new Exception(Yii::t('wish_listing', 'NO main sku'));
			}
			$accountId = $data['account_id'];
			$wishLog = new WishLog;
			//创建运行日志
			$logId = $wishLog->prepareLog($accountId,  WishProductAdd::EVENT_UPLOAD_PRODUCT);
			if(!$logId) {
				throw new Exception(Yii::t('wish_listing', 'Log create failure'));
			}
			//检查账号是可以提交请求报告
			$checkRunning = $wishLog->checkRunning($accountId, WishProductAdd::EVENT_UPLOAD_PRODUCT);
			if(!$checkRunning){
				$wishLog->setFailure($logId, Yii::t('systems', 'There Exists An Active Event'));
				throw new Exception(Yii::t('systems', 'There Exists An Active Event'));
			}
			//插入本次log参数日志(用来记录请求的参数)
			$eventLog = $wishLog->saveEventLog(WishProductAdd::EVENT_UPLOAD_PRODUCT, array(
					'log_id'        => $logId,
					'account_id'    => $accountId,
					'start_time'    => date('Y-m-d H:i:s'),
					'end_time'      => date('Y-m-d H:i:s'),
			));
			//设置日志为正在运行
			//$wishLog->setRunning($logId);
			
			$res = $productaddModel->uploadProduct($addId);
			if($res){
				$wishLog->setSuccess($logId);
				$wishLog->saveEventStatus(WishProductAdd::EVENT_UPLOAD_PRODUCT, $eventLog, WishLog::STATUS_SUCCESS);
				
				echo $this->successJson(array(
						'message' => Yii::t('system', 'Operate Successful'),
				));
				Yii::app()->end();
			}else{
				$wishLog->setFailure($logId, $productaddModel->getErrorMsg());
				$wishLog->saveEventStatus(WishProductAdd::EVENT_UPLOAD_PRODUCT, $eventLog, WishLog::STATUS_FAILURE);
				throw new Exception($productaddModel->getErrorMsg());
			}
			
			
		}catch(Exception $e){
			echo $this->failureJson(array(
					'message' => Yii::t('wish_listing', 'Wish upload failure').":".$e->getMessage(),
			));
			Yii::app()->end();
		}
	}
	
	/**
	 * @desc 批量上传
	 * @throws Exception
	 */
	public function actionBatchupload(){
		set_time_limit(5*3600);
		ini_set("display_errors", true);
		error_reporting(E_ALL);
		$addIds = Yii::app()->request->getParam("ids");
		try{
			if(!$addIds){
				throw new Exception(Yii::t('wish_listing', 'Param error'));
			}
			$addIdArr = explode(",", $addIds);
			$productaddModel = new WishProductAdd;
			$accountGroupAdd = array();
			$addList = $productaddModel->getProductAddInfoAll("account_id, id", array("IN", 'id', $addIdArr));
			foreach ($addList as $add){
				
				$accountGroupAdd[$add['account_id']][] = $add['id'];
				
			}
			
			foreach ($accountGroupAdd as $accountID=>$addArr){
				try{
					$wishLog = new WishLog;
					//创建运行日志
					$logId = $wishLog->prepareLog($accountID,  WishProductAdd::EVENT_UPLOAD_PRODUCT);
					if(!$logId) {
						continue;
						//throw new Exception(Yii::t('wish_listing', 'Log create failure'));
					}
					//检查账号是可以提交请求报告
					$checkRunning = $wishLog->checkRunning($accountID, WishProductAdd::EVENT_UPLOAD_PRODUCT);
					if(!$checkRunning){
						$wishLog->setFailure($logId, Yii::t('systems', 'There Exists An Active Event'));
						throw new Exception(Yii::t('systems', 'There Exists An Active Event'));
					}
					//插入本次log参数日志(用来记录请求的参数)
					$eventLog = $wishLog->saveEventLog(WishProductAdd::EVENT_UPLOAD_PRODUCT, array(
							'log_id'        => $logId,
							'account_id'    => $accountID,
							'start_time'    => date('Y-m-d H:i:s'),
							'end_time'      => date('Y-m-d H:i:s'),
					));
					//设置日志为正在运行
					$wishLog->setRunning($logId);
					$message = "";
					foreach ($addArr as $addID){
						$res = $productaddModel->uploadProduct($addID);
						if(!$res){
							$message .= "addID:{$addID}:".$productaddModel->getErrorMsg();
						}
					}
					$wishLog->setSuccess($logId, $message);
					$wishLog->saveEventStatus(WishProductAdd::EVENT_UPLOAD_PRODUCT, $eventLog, WishLog::STATUS_SUCCESS);
				}catch (Exception $e){
					if(isset($logId) && $logId){
						$wishLog->setFailure($logId, $productaddModel->getErrorMsg());
						$wishLog->saveEventStatus(WishProductAdd::EVENT_UPLOAD_PRODUCT, $eventLog, WishLog::STATUS_FAILURE);
					}
				}
			}
			echo $this->successJson(array(
					'message' => Yii::t('system', 'Operate Successful'),
			));
			Yii::app()->end();
		}catch(Exception $e){
			echo $this->failureJson(array(
					'message' => Yii::t('wish_listing', 'Wish upload failure').":".$e->getMessage(),
			));
			Yii::app()->end();
		}
	}

	/**
	 * [actionExportlistingimg description]
	 * @return [type] [description]
	 * @link /wish/wishproductadd/exportlistingimg
	 */
	public function actionExportlistingimg() {
		set_time_limit(5*3600);
		ini_set('memory_limit', '2048M');
		ini_set('display_errors', false);
		error_reporting(0);
		$filename = 'wish批量刊登产品表2'.date('Y-m-d_His').'.xls';
		header ( "Content-type:application/vnd.ms-excel" );
		header ( "Content-Disposition:filename=".$filename );
		$wishproductadd = new WishProductAdd();

		$title_fields = array('主SKU','主图','图片链接1','图片链接2','图片链接3','图片链接4','图片链接5','图片链接6','图片链接7','图片链接8','图片链接9','图片链接10');//,

		$titleStr = '';
		foreach ($title_fields as $v) {
			$titleStr .= "<td class='title'>".$v."</td>";
		}

		$trs = '';
		//图片链接，取最近上传过的listing主表remote_extra_img
    	$res = $wishproductadd->dbConnection->createCommand()
			                    ->select('parent_sku')
			                    ->from($wishproductadd->tableName())
			                    ->where("parent_sku!=''")
			                    ->group("parent_sku")
			                    ->order("parent_sku asc")
			                    //->limit(10)
			                    ->queryColumn();	                   
		if (empty($res)) {
			die('no data');
		}	
		foreach ($res as $sku) {
			$tds = '';
			$imageslist = array();
        	$wishproductaddInfo = $wishproductadd->dbConnection->createCommand()
				                    ->select('remote_main_img,remote_extra_img')
				                    ->from($wishproductadd->tableName())
				                    ->where("parent_sku='{$sku}'")
				                    ->order("id desc")
				                    ->limit(1)
				                    ->queryRow();	
            if ($wishproductaddInfo && $wishproductaddInfo['remote_extra_img'] != '') {
				$tmp = explode('|',$wishproductaddInfo['remote_extra_img']);
				foreach ($tmp as $ims) {
					$imageslist[] = $ims;
				}
            }
            $mainImg = $wishproductaddInfo && $wishproductaddInfo['remote_main_img'] != '' ? $wishproductaddInfo['remote_main_img'] : '';
			for($i=0;$i<10;$i++) {
				$tds .= "<td>".(isset($imageslist[$i]) ? $imageslist[$i] : '')."</td>";
			}
			$trs .= "<tr><td>".$sku."</td><td>".$mainImg."</td>".$tds."</tr>";
		}		

		$str = "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>
					<html xmlns='http://www.w3.org/1999/xhtml'>
					<head>
					<meta http-equiv='Content-Type' content='text/html; charset=UTF-8' />
					<title>无标题文档</title>
					<style>
					td{
						text-align:center;
						font-size:12px;
						font-family:Arial, Helvetica, sans-serif;
						border:#1C7A80 1px solid;
						color:#152122;
						width:100px;
					}
					table,tr{
						border-style:none;
					}
					.title{
						background:#7DDCF0;
						color:#FFFFFF;
						font-weight:bold;
					}
					</style>
					</head>

					<body>
					<table width='800' border='1'>
					  <tr>".  $titleStr."</tr>
					  ".$trs."
					</table>
					</body>
					</html>";
		echo $str;
		exit;	  
	}

	/**
	 * @desc wish批量刊登导数据
	 * @link /wish/wishproductadd/exportcsvforbatchadd
	 */
	public function actionExportcsvforbatchadd(){
		set_time_limit(5*3600);
		ini_set('memory_limit', '2048M');
		ini_set('display_errors', false);
		error_reporting(0);

		$filename = 'wish批量刊登产品表'.date('Y-m-d_His').'.xls';
		header ( "Content-type:application/vnd.ms-excel" );
		header ( "Content-Disposition:filename=".$filename );
		$sku_test = trim(Yii::app()->request->getParam("sku",''));
		//分页处理
		$wishproductadd = new WishProductAdd();
		$productClassToOnlineClass = new ProductClassToOnlineClass();
		$warehouseSkuMap = new WarehouseSkuMap();
		$productInfringe = new ProductInfringe();
		$productSelectAttribute = new ProductSelectAttribute();
		$productAttributeValue = new ProductAttributeValue();
		$product = new Product();
        $obj = $product->dbConnection->createCommand()
                    ->select('count(*) as total')
                    ->from("ueb_product as m")
                    ->leftJoin("ueb_product_description as e","e.product_id=m.id AND e.language_code='english'")
                    ->where("m.product_status=4")
                    ->andWhere("m.product_is_multi in(0,1)");
        $sku_test != '' && $obj->andWhere(" m.sku = '{$sku_test}' ");
        //echo $obj->Text;exit;
        $res = $obj->queryRow();
        $total = (int)$res['total'];
        if ($total == 0) {
            die('no data');
        }
        $currPage = 1;
        $pageSize = 10000;
        $pageCount = ceil($total/$pageSize);
        //echo 'total:'.$total.' pageCount:'.$pageCount."<br>";
        if ($currPage > $pageCount || $currPage < 1 ) {
            die('$currPage is invalid');
        }
        if ($endPage != '' && $endPage < 1 ) {
            die('$endPage is invalid');
        }      
        if ($endPage == '' || $endPage > $pageCount) {
            $endPage = $pageCount;
        }

        $companyCatArr = array(1=>'家居',2=>'3C',3=>'服装服饰',4=>'户外',5=>'美容保健',6=>'孕婴童');
        $infringeTypeArr = array(1=>'正规',2=>'侵权',3=>'违规');
		$title_fields = array('主SKU','子SKU','公司分类','产品状态','尺寸','颜色','成本价','可用库存','侵权种类','侵权原因','标题','描述','包装');//,'图片链接1','图片链接2','图片链接3','图片链接4','图片链接5','图片链接6','图片链接7','图片链接8','图片链接9','图片链接10'

		$titleStr = '';
		foreach ($title_fields as $v) {
			$titleStr .= "<td class='title'>".$v."</td>";
		}
		$str = "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>
			<html xmlns='http://www.w3.org/1999/xhtml'>
			<head>
			<meta http-equiv='Content-Type' content='text/html; charset=UTF-8' />
			<title>无标题文档</title>
			<style>
			td{
				text-align:center;
				font-size:12px;
				font-family:Arial, Helvetica, sans-serif;
				border:#1C7A80 1px solid;
				color:#152122;
				width:100px;
			}
			table,tr{
				border-style:none;
			}
			.title{
				background:#7DDCF0;
				color:#FFFFFF;
				font-weight:bold;
			}
			</style>
			</head>

			<body>
			<table width='800' border='1'>
			  <tr>".  $titleStr."</tr>";

        for($page = $currPage; $page<= $endPage; $page++) {
            $offset = ($page - 1) * $pageSize;
			$obj = $product->dbConnection->createCommand()
                    ->select('m.id,m.sku,m.product_cost,m.product_is_multi,f.category_id,n.infringement,n.infringement_reason,e.title,e.description,e.included')
                    ->from("ueb_product as m")
                    ->leftJoin("ueb_product_class_to_online_class as f","f.online_id=m.online_category_id ")
                    ->leftJoin("ueb_product_infringement as n","n.sku=m.sku ")
                    ->leftJoin("ueb_product_description as e","e.product_id=m.id AND e.language_code='english'")
                    ->where("m.product_status=4")
                    ->andWhere("m.product_is_multi in(0,1)")
                    ->order(' m.sku asc ')
                    ->limit($pageSize, $offset);
            //echo $obj->Text;exit;        
            $sku_test != '' && $obj->andWhere(" m.sku = '{$sku_test}' ");                    
            $res = $obj->queryAll();
            if (empty($res)) {
                break;
            }
            $parentSkuArr = array();
            foreach ($res as $v) {  
            	//子sku,标题,描述,成本价
				$sku         = $v['sku'];
				$title       = $v['title'];
				$description = $v['description'];
				$included    = $v['included'];
				$price       = $v['product_cost'];   

				//侵权种类
				$infringeInfo = '';
				$infringeReason = '';
				if ($v['infringement'] != '') {
					$infringeInfo = $infringeTypeArr[$v['infringement']];
					$infringeReason = $v['infringement_reason'];
				}	

				//可用库存
				$avaiableStock = 0;
				$avgInfo = $warehouseSkuMap->dbConnection->createCommand()
		                    ->select('available_qty')
		                    ->from($warehouseSkuMap->tableName())
		                    ->where(" sku='{$v['sku']}' and warehouse_id=41 ")
		                    ->queryRow();	  
		        if (!empty($avgInfo)) {
		        	$avaiableStock = $avgInfo['available_qty'];
		        }      

		        //公司分类
		        $companyCat = '未知分类';    
		        if ( isset($companyCatArr[$v['category_id']]) ) {
		        	$companyCat =  $companyCatArr[$v['category_id']];
		        }

            	//图片链接，取最近上传过的listing主表remote_extra_img
    //         	$imageslist = array();
    //         	$wishproductaddInfo = $wishproductadd->dbConnection->createCommand()
				// 	                    ->select('remote_extra_img')
				// 	                    ->from($wishproductadd->tableName())
				// 	                    ->where("parent_sku='{$v['sku']}'")
				// 	                    ->order("id desc")
				// 	                    ->limit(1)
				// 	                    ->queryRow();	                   
				// if (!empty($wishproductaddInfo)) {
				// 		$tmp = explode('|',$wishproductaddInfo['remote_extra_img']);
				// 		foreach ($tmp as $ims) {
				// 			$imageslist[] = $ims;
				// 		}					
				// }	
				$imageslistTds = '';
				// for($i=0;$i<10;$i++) {
				// 	$imageslistTds .= "<td>".(isset($imageslist[$i]) ? $imageslist[$i] : '')."</td>";
				// }	        

				//去除描述中的html标签
				$description = preg_replace_callback("/(?<TT><\/?(?<TAG>\w+).*?>)/im", array($this, 'preg_replace_call_func'), $description);
				$description = preg_replace("/(\n){2,}|\r\n/ie", "\n", $description);
				$description = strip_tags($description);
				//$description = '';

				//去除included里面html标签
				$included = preg_replace_callback("/(?<TT><\/?(?<TAG>\w+).*?>)/im", array($this, 'preg_replace_call_func'), $included);
				$included = preg_replace("/(\n){2,}|\r\n/ie", "\n", $included);
				$included = strip_tags($included);
				//$included = '';

            	//主sku,尺寸,颜色
            	if ($v['product_is_multi'] == 1 ) {//子sku
					$parentSku = '';
					$size      = '';
					$color     = '';
                	$attrDatas = $productSelectAttribute->dbConnection->createCommand()
		                    ->select('multi_product_id,sku,attribute_id,attribute_value_id')
		                    ->from($productSelectAttribute->tableName())
		                    ->where(" sku='{$v['sku']}' and attribute_id in(22,23) ")
		                    ->queryAll();
					if (!empty($attrDatas)) {
			            //size、color
						foreach ($attrDatas as $vv) {
							//parentSku
							if (!isset($parentSkuArr[$vv['multi_product_id']])) {
								$tmp = $product->dbConnection->createCommand()
					                    ->select('sku')
					                    ->from($product->tableName())
					                    ->where("id = {$vv['multi_product_id']}")
					                    ->queryRow();
					            $parentSku = !empty($tmp) ? $tmp['sku'] : '';
					            $parentSkuArr[$vv['multi_product_id']] = $parentSku;
							} else {
								$parentSku = $parentSkuArr[$vv['multi_product_id']];
							}
							$tmp = $productAttributeValue->dbConnection->createCommand()
				                    ->select('attribute_value_name')
				                    ->from($productAttributeValue->tableName())
				                    ->where("id = {$vv['attribute_value_id']}")
				                    ->queryRow();	
				            $attrName = !empty($tmp) ? $tmp['attribute_value_name'] : '';						
							if ($v['attribute_id'] == 22) {//color
								$color = $attrName;
							} else {//size
								$size = $attrName;
							}
						}
					}		                    
            	} else {//单品sku
            		$parentSku = $v['sku'];
            		$size = '';
            		$color = '';
            	}

				$str .= "
				  <tr>
				    <td style='vnd.ms-excel.numberformat:@'>".$parentSku."</td>
				    <td style='vnd.ms-excel.numberformat:@'>".$sku."</td>
				    <td>".$companyCat."</td>
				    <td>在售中</td>
				    <td>".$size."</td>
				    <td>".$color."</td>
				    <td style='vnd.ms-excel.numberformat:#,##0.00'>". number_format($price,2,'.','') ."</td>
				    <td>".$avaiableStock."</td>
				    <td>".$infringeInfo."</td>
				    <td>".$infringeReason."</td>
				    <td>".$title."</td>
				    <td>".$description."</td>
				    <td>".$included."</td>
				    ".$imageslistTds."
					</tr>";
            }
            // break;
        }

		$str .= "
		</table>
		</body>
		</html>
		";
		echo $str;
		exit;
	}	
	
	
	public function actionTest(){
		$model = new Logistics;
		$param = array(
				'country'       => "United Kingdom",
				'attributeid'   => array(),
				'platform_code' => Platform::CODE_EBAY,
				'is_quota'      =>  false,
				'warehouse'     =>  41,
		);
		$result = $model->getShipFee(Logistics::CODE_CM_DGYZ, 200, $param);
		var_dump($result);
		exit;
		$images = Product::model()->getImgList('102771.0', 'ft');
		
		
		var_dump($images);
		
		exit;
		$productImageModel = new ProductImageAdd();
		$response = $productImageModel->getSkuImageUpload(52, '117834', array(), Platform::CODE_EBAY);
		foreach ($response['result']['imageInfoVOs'] as $v) {
			echo "<img src='{$v['remotePath']}'><br/>";
			echo "<img src='{$v['uebPath']}'><br/>";
		}
		print_r($response);
		
		exit;
		echo $prepackage = date("ym");
		echo $prepackage2 = date("ym", strtotime("-1 months", strtotime("2017-01-01 00:00:00")));
		
		exit;
		
		$imgname = array_shift($images);
		var_dump($imgname);
		echo $basefilename = basename($imgname);
		$pos = strrpos($basefilename, "-");
		if($pos === false){
			$pos = strrpos($basefilename, ".");
		}
		$name = substr($basefilename, 0, $pos);
		var_dump($name);
		var_dump($imgname);
		exit;
		var_dump($images);
		foreach ($images as $img){
			echo "<img src='http://172.16.1.11{$img}'/><br/>";
		}
		$urls = $images;
		var_dump($urls);
		/* $urls = array("http://172.16.1.11//upload/image/assistant/1/1/7/2/117289.01-4.jpg","http://172.16.1.11//upload/image/assistant/1/1/7/2/117289.01-5.jpg",
				"http://172.16.1.11//upload/image/assistant/1/1/7/2/117289-4.jpg","http://172.16.1.11//upload/image/assistant/1/1/7/2/117289.02-4.jpg",
				"http://172.16.1.11//upload/image/assistant/1/1/7/2/117289.02-6.jpg","http://172.16.1.11//upload/image/assistant/1/1/7/2/117289.04-3.jpg",
				"http://172.16.1.11//upload/image/assistant/1/1/7/2/117289-11.jpg","http://172.16.1.11//upload/image/assistant/1/1/7/2/117289-7.jpg",
				"http://172.16.1.11//upload/image/assistant/1/1/7/2/117289-5.jpg","http://172.16.1.11//upload/image/assistant/1/1/7/2/117289.03-6.jpg",
				"http://172.16.1.11//upload/image/assistant/1/1/7/2/117289.04-12.jpg","http://172.16.1.11//upload/image/assistant/1/1/7/2/117289-6.jpg"); */
		foreach ($urls as $url){
			echo "==========url========<br/>";
			echo $url;
			echo "<br>";
			$remoteUrl = WishProductAdd::model()->getRemoteImgPathByName($url, 12);
			var_dump($remoteUrl);
		}
		exit;
	}

	public function actionValidatesku(){
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
		echo $this->successJson(array(
			'message' => Yii::t('system', 'Save successful')
		));
		Yii::app()->end();
	}	

	/**
	 * @desc 获取利润和利润率
	 */
	public function actionGetprofitinfo(){
		$sku             = Yii::app()->request->getParam('sku');
		$salePrice       = Yii::app()->request->getParam('sale_price','0');
		$shipPrice       = Yii::app()->request->getParam('ship_price','0');
		$shipWarehoustID = Yii::app()->request->getParam('ship_wharehoust_id','0');
		$accountID = Yii::app()->request->getParam('account_id','0');

		$currency = WishProductAdd::PRODUCT_PUBLISH_CURRENCY; //货币
        $status = 500;
        $profit = $profitRate = false;
        $salePriceInfo = WishProductAdd::getProfitInfo($salePrice, $sku, $currency, $shipPrice, $shipWarehoustID, $accountID);
        if($salePriceInfo){
        	$status     = 200;
        	$profit     = $salePriceInfo['profit'];
        	$profitRate = $salePriceInfo['profitRate'];
        	// $profitDesc = '<a href="javascript:;" onClick="alertMsg.confirm(\''.$salePriceInfo['desc'].'\')">查看详情</a>';
        }
        $result = array(
    			'statusCode' => $status,
    			'data' => array(
					'profit' => $profit,
					'profitRate' => $profitRate,
					'profitDesc' => $salePriceInfo
    			),
    	);

        echo json_encode($result);exit;
	}
	

/**
 * @link /wish/wishproductadd/getsaleprice/sku/xx/account_id/xx
 * @return [type] [description]
 */
	public function actionGetsaleprice(){
		$sku             = Yii::app()->request->getParam('sku');
		$shipWarehoustID = Yii::app()->request->getParam('ship_wharehoust_id');
		$accountID       = Yii::app()->request->getParam('account_id','0');
		
		$shipCode        = Logistics::CODE_CM_GZ_WISH;//默认物流方式
		if(empty($shipWarehoustID)) $shipWarehoustID = 41;	//默认本地仓
		$currency        = WishProductAdd::PRODUCT_PUBLISH_CURRENCY; //货币
		$status          = 500;
		$profit          = $profitRate = false;
        $salePriceInfo = WishProductAdd::getSalePrice($sku, $accountID, $shipCode, $shipWarehoustID);
        if($salePriceInfo){
        	$status     = 200;
        	$profit     = $salePriceInfo['profit'];
        	$profitRate = $salePriceInfo['profitRate'];
        	// $profitDesc = '<a href="javascript:;" onClick="alertMsg.confirm(\''.$salePriceInfo['desc'].'\')">查看详情</a>';
        }
        $result = array(
    			'statusCode' => $status,
    			'data' => array(
					'profit' => $profit,
					'profitRate' => $profitRate,
					'profitDesc' => $salePriceInfo
    			),
    	);

        echo json_encode($result);exit;
	}



	public function actionTagsLookupSuggestion($sku)
    {
        $listings = WishListing::model()->findAllByAttributes(array('sku'=> $sku));
        $account = Yii::app()->request->getParam('account');
        $tags = array();
        foreach($listings as $listing) {
            $tags = array_merge($tags, explode(",", $listing['tags']));

        }
        $tags = array_unique($tags);
        $this->render('tags-lookup-suggestion', array(
                'tags'=> $tags,
                'account'=>$account
            )
        );
    }

	/**
     * 查找相关sku的title
     */
	public function actionTitleLookupSuggestion($sku)
    {
        #error_reporting(2048);

        try{
            $platforms = Platform::model()->getPlatformList();
            $account = Yii::app()->request->getParam('account');

            $allowedPlatforms = array(
                Platform::CODE_WISH,
            );

            foreach($platforms as $code=> $name) {
                if (!in_array($code, $allowedPlatforms)) {
                    unset($platforms[$code]);
                }
            }
            $titles = array();

            // wish
            $listings = WishListing::model()->findAllByAttributes(array('sku'=> $sku));
            $tmpTitles = array();
            foreach($listings as $listing) {
                $tmpTitles[] = $listing['name'];
            }
            $titles[Platform::CODE_WISH] = array_unique($tmpTitles);

            $this->render('lookup-suggestion', array(
                    'platforms' => $platforms,
                    'titles' => $titles,
                    'account' => $account
                )
            );
        }catch (\Exception $e) {
            echo $e->getMessage();
        }
    }
}