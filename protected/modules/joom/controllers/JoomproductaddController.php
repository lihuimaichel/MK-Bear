<?php
/**
 * @desc 产品刊登
 * @author lihy
 *
 */
class JoomproductaddController extends UebController{
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
		//刊登类型
		// ... 
		$listingType = JoomProductAdd::getListingType();
		//sku信息
		$skuInfo = Product::model()->getProductInfoBySku($sku);
		if(empty($skuInfo)){
			echo $this->failureJson(array('message'=>Yii::t('joom_listing', 'Not found the sku')));
			Yii::app()->end();
		}
                
        //检测是否有权限去刊登该sku
		//上线后打开注释---lihy 2016-05-10
		if(! Product::model()->checkCurrentUserAccessToSaleSKU($sku, Platform::CODE_JOOM)){
		 	echo $this->failureJson(array('message' => Yii::t('system', 'Not Access to Add the SKU')));
			Yii::app()->end();
		}

		//检查产品属性，如果为纯电池的不允许刊登---2017-02-06
		$wheres3 = 'attribute_id = :attribute_id AND attribute_value_id = :attribute_value_id';
        $params3 = array(':attribute_id'=>3,':attribute_value_id'=>4);
        $attributeIdsInfo = ProductSelectAttribute::model()->getSelectedAttributeSKUListByProductId($skuInfo['id'],$wheres3,$params3);
        if($attributeIdsInfo){
        	echo $this->failureJson(array('message' => $sku.'产品属性为纯电池不允许刊登'));
			Yii::app()->end();
        }
                
/*		$config = ConfigFactory::getConfig('serverKeys');
		//sku图片加载
		$imageType = array('zt','ft');
		$skuImg = array();
		foreach($imageType as $type){
			$images = Product::model()->getImgList($sku,$type);
			foreach($images as $k=>$img){
				$skuImg[$type][$k] = $config['oms']['host'].$img;
			}
		}*/

        $skuImg = ProductImageAdd::getOrPushImageUrlFromRestfulBySku($skuInfo, $pushWithChild = true, $typeAlisa = null, $type = 'normal', $width = 100, $height = 100, $platform = Platform::CODE_JOOM);
        /**
         * 修复java api接口无主图返回问题
         */
        if (isset($skuImg['ft']) && !isset($skuImg['zt'])) {
            $skuImg['zt'] = $skuImg['ft'];
        }
		//刊登模式
		$this->render('add',   array(
				'sku'           => $sku,
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
	/**
	 * @desc 添加产品信息
	 */
	public function actionAddinfo(){
		$saveType = JoomProductAdd::SAVE_TYPE_ALL;
		$sku = Yii::app()->request->getParam('sku');
		$accounts = Yii::app()->request->getParam('accounts');
		$listingType = Yii::app()->request->getParam('listing_type', JoomProductAdd::LISTING_TYPE_VARIATION);
		//检查刊登账号是否设置
		if (empty($accounts)) {
			echo $this->failureJson(array(
					'message' => Yii::t('aliexpress_product', 'Have not Chosen Account'),
			));
			Yii::app()->end();
		}
		//限制账号为一个：2015-12-11
		if(count($accounts)>1){
			echo $this->failureJson(array(
					'message' => Yii::t('joom_listing', 'Only Chose One Account')
			));
			Yii::app()->end();
		}
		$accountId = $accounts[0];
		$skuInfo = Product::model()->getProductInfoBySku($sku);
		if(empty($skuInfo)){
			echo $this->failureJson(array(
					'message' => Yii::t('joom_listing', 'Invalide SKU'),
			));
			Yii::app()->end();
		}

        //检测是否有权限去刊登该sku -- yangsh 2016-12-14
		if(! Product::model()->checkCurrentUserAccessToSaleSKUNew($sku,$accountId,Platform::CODE_JOOM)){
		 	echo $this->failureJson(array(
		 		'message' => Yii::t('system', 'Not Access to Add the SKU')
		 	));
			Yii::app()->end();
		}

		//检查产品属性，如果为纯电池的不允许刊登---2017-02-06
		$wheres3 = 'attribute_id = :attribute_id AND attribute_value_id = :attribute_value_id';
        $params3 = array(':attribute_id'=>3,':attribute_value_id'=>4);
        $attributeIdsInfo = ProductSelectAttribute::model()->getSelectedAttributeSKUListByProductId($skuInfo['id'],$wheres3,$params3);
        if($attributeIdsInfo){
        	echo $this->failureJson(array('message' => $sku.'产品属性为纯电池不允许刊登'));
			Yii::app()->end();
        }

		//转换成sku库的判断是否是单品的属性值
		$publishType = 2;
		if($listingType == JoomProductAdd::LISTING_TYPE_FIXEDPRICE){
			$publishType = 0;
		}
		
		Product::model()->checkPublishSKU($publishType, $skuInfo);

		//为子sku时，判断父SKU是否已经刊登过了
		$parentSku = '';
		if($skuInfo['product_is_multi'] == JoomProductAdd::PRODUCT_IS_SINGLE){
			/* echo $this->failureJson(array(
					'message' => Yii::t('joom_listing', 'Please Input Main SKU'),
			));
			Yii::app()->end(); */
			$parentSku = $sku;
			$skuInfo['product_is_multi'] = WishProductAdd::PRODUCT_IS_NORMAL;		
		}else{
			$parentSku = $sku;
		}
		//去除描述中的html标签
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
				
		if($skuInfo['product_is_multi'] == JoomProductAdd::PRODUCT_IS_NORMAL){
			$listingType = JoomProductAdd::LISTING_TYPE_FIXEDPRICE;
		}else{
			$listingType = JoomProductAdd::LISTING_TYPE_VARIATION;
		}		//获取刊登类型
		$listingTypeArr = JoomProductAdd::getListingType();
		
		//获取账号
		$accountInfo = JoomAccount::getAccountInfoByIds($accounts);
		if (!$accountInfo){
			echo $this->failureJson(array(
					'message' => Yii::t('aliexpress_product', 'Have not Chosen Account'),
			));
			Yii::app()->end();
		}
		//目前账号限制在了一个
		$accountList = array();
		$account = $accountInfo[0];
		//判断是否在刊登列表里面了
		$parentAddSkuInfo = UebModel::model('JoomProductAdd')->getProductAddInfo("parent_sku=:parent_sku AND account_id=:account_id",array(':parent_sku'=>$parentSku, ':account_id'=>$account['id']));

		
		//已经添加过的对应的值
		if($parentAddSkuInfo){
			//subject、description、brand、tags
			$account['title'] = $parentAddSkuInfo['name'];
			$account['brand'] = $parentAddSkuInfo['brand'];
			$account['description'] = $parentAddSkuInfo['description'];
			$account['tags'] = explode(",", $parentAddSkuInfo['tags']);
			$account['is_add'] = true;
			//$saveType = JoomProductAdd::SAVE_TYPE_ONLY_SUBSKU;
		}else{
			$account['title'] = !empty($skuInfo['title']['english'])?$skuInfo['title']['english']:'';
			$account['brand'] = !empty($skuInfo['brand_info']['brand_en_name'])?$skuInfo['brand_info']['brand_en_name']:'';
			$account['tags'] = array();
			$account['is_add'] = false;
			$account['description'] = !empty($skuInfo['description']['english'])?"Description:\n".$skuInfo['description']['english']:'';
			if(!empty($skuInfo['included']['english'])){
				$account['description'] .= "\nIncluded:\n".$skuInfo['included']['english'];
			}
			//判断是否已经在joom产品列表中
			$listingInfo = UebModel::model('JoomListing')->find('sku=:sku AND account_id=:account_id', 
												array(':sku'=>$sku, ':account_id'=>$accountId));
			if($listingInfo){
				$account['is_add'] = true;
				//$saveType = JoomProductAdd::SAVE_TYPE_NO_MAIN_SKU;
			}
			unset($listingInfo);
		}
		
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
		unset($account);
		$listingParam = array(
				'listing_type'      => array('id' => $listingType, 'text' => $listingTypeArr[$listingType]),
				'listing_account'   => $accountList,
		);
		/**@ 获取产品信息*/


//		$imageType = array('zt', 'ft');
//		$config = ConfigFactory::getConfig('serverKeys');
//		$skuImg = array();
//		foreach($imageType as $type){
//			$images = Product::model()->getImgList($sku,$type);
//			foreach($images as $k=>$img){
//				$skuImg[$type][$k] = $config['oms']['host'].$img;
//			}
//		}
		$skuImg = ProductImageAdd::getImageUrlFromRestfulBySku($sku,  $typeAlisa = null, $type = 'normal', $width = 100, $height = 100, $platform = Platform::CODE_JOOM);

        /**
         * 修复java api接口无主图返回问题
         */
        if (isset($skuImg['ft']) && !isset($skuImg['zt'])) {
            $skuImg['zt'] = $skuImg['ft'];
        }

		$skuInfo['is_add'] = $accountList[$accountId]['is_add'];
		$listingProduct = array(
				'parentSku'		=> $parentSku,
				'sku'           => $sku,
				'skuImg'        => $skuImg,
				'skuInfo'       => $skuInfo,
		);
		//获取子SKU信息
		$listingSubSKU = JoomProductAdd::getSubProductByMainProductId($skuInfo['id'], $skuInfo['product_is_multi']);
		//获取物流方式列表
		$logisticsList = JoomProductAdd::$logisticsType;
		$shipCode = Yii::app()->request->getParam('ship_code');
		// if(!$shipCode) {
  //                   $attributes = Product::model()->getAttributeBySku($sku, 'product_features');//属性
  //                   if($attributes){
  //                           //特殊属性的按黑龙江俄速通亚欧小包
  //                           //$shipCode = Logistics::CODE_CM_SF;
  //                           $shipCode = Logistics::CODE_CM_YO_EST;
  //                   }else{
  //                           //普通的是易时达俄罗斯专线
  //                           $shipCode = Logistics::CODE_CM_HUNGARY;
  //                   }
  //               }
		//增加价格统计
		if($listingSubSKU['skuList']){
			foreach ($listingSubSKU['skuList'] as $key=>$skuRow){
				$salePrice = JoomProductAdd::getSalePrice($skuRow['sku'], $accountId, '');
				$profitRate = $salePrice['profitRate']*100;
				$listingSubSKU['skuList'][$key]['skuInfo']['shipping'] = $salePrice['shipPrice'];
				$listingSubSKU['skuList'][$key]['skuInfo']['market_price'] = round($salePrice['salePrice']*1.8, 2);
				$listingSubSKU['skuList'][$key]['skuInfo']['price_profit'] = '利润:<b>'.$salePrice['profit'].'</b>,利润率:<b>'.$profitRate.'%</b>';
				$listingSubSKU['skuList'][$key]['skuInfo']['product_cost'] = $salePrice['salePrice'];
				$listingSubSKU['skuList'][$key]['skuInfo']['inventory'] = JoomProductAdd::PRODUCT_PUBLISH_INVENTORY;
				$listingSubSKU['skuList'][$key]['skuInfo']['price_error'] = $salePrice['errormsg'];
				$upload_status = false;
				$listingSubSKU['skuList'][$key]['skuInfo']['upload_status'] = $upload_status;
				//在账号只有一个情况下才能做出下面的判断
				//判断是否在待刊登列表
				if($parentAddSkuInfo){
					$variantSku = UebModel::model('JoomProductVariantsAdd')->find("sku=:sku and add_id=:add_id", array(":sku"=>$skuRow['sku'], ":add_id"=>$parentAddSkuInfo['id']));
					
					if($variantSku){
						unset($listingSubSKU['skuList'][$key]);
					}
				}
				if(isset($listingSubSKU['skuList'][$key])){//判断是否在产品子表中
					$variantSku = UebModel::model('JoomVariants')->find('account_id=:account_id AND sku=:sku',
														 array(':account_id'=>$accountId, ':sku'=>$skuRow['sku']));
					if($variantSku){
						unset($listingSubSKU['skuList'][$key]);
					}
				}
			}
			//已经没有可用的子sku了
			if(empty($listingSubSKU['skuList'])){
				echo $this->failureJson(array(
						'message' => Yii::t('joom_listing', 'Had upload the SKU')
				));
				Yii::app()->end();
			}
		}else{
			if($accountList[$accountId]['is_add']){
				echo $this->failureJson(array(
						'message' => Yii::t('joom_listing', 'Had upload the SKU')
				));
				Yii::app()->end();
			}
			$salePrice = JoomProductAdd::getSalePrice($listingProduct['skuInfo']['sku'], $accountId, $shipCode);
			$profitRate = $salePrice['profitRate']*100;
			$listingProduct['skuInfo']['shipping'] = $salePrice['shipPrice'];
			$listingProduct['skuInfo']['market_price'] = round($salePrice['salePrice']*1.8, 2);
			$listingProduct['skuInfo']['price_profit'] = '利润:<b>'.$salePrice['profit'].'</b>,利润率:<b>'.$profitRate.'%</b>';
			$listingProduct['skuInfo']['product_cost'] = $salePrice['salePrice'];
			$listingProduct['skuInfo']['inventory'] = JoomProductAdd::PRODUCT_PUBLISH_INVENTORY;
			//在账号只有一个的情况下
			$listingProduct['skuInfo']['upload_status'] = $accountList[$accountId]['is_add'];
			$listingProduct['skuInfo']['price_error'] = $salePrice['errormsg'];
		}
		$this->render('addinfo', array(
			'shipCode'		=>	$shipCode,
			'logisticsList'	=>	$logisticsList,
			'listingParam'	=>	$listingParam,
			'listingProduct' => $listingProduct,
			'listingSubSKU'	=>	$listingSubSKU['skuList'],
			'attributeList'	=>	$listingSubSKU['attributeList'],
			'action'		=>	'add',
			'saveType'		=>	$saveType,
			'isSubSku'		=>	$parentSku == $sku ? false : true
		));
	}
	
	
	public function actionUpdate(){
		$addId = Yii::app()->request->getParam('add_id');
		$saveType = 0;
		//获取待刊登中的信息
		$joomProductAddModel = new JoomProductAdd;
		$productInfo = $joomProductAddModel->getProductAddInfo('id=:id', array(':id'=>$addId));
		$sku = $parentSku = $productInfo['parent_sku'];
		
		//获取账号
		$accountInfo = JoomAccount::getAccountInfoByIds(array($productInfo['account_id']));
		$accountList = array();
		foreach ($accountInfo as $account){
			//已经添加过的对应的值
			//subject、description、brand、tags
			$account['title'] = $productInfo['name'];
			$account['brand'] = $productInfo['brand'];
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

		if($productInfo['main_image']) {
            $skuImg['zt'][$productInfo['main_image']] = ProductImageAdd::getImageUrlFromRestfulByFileName($productInfo['main_image'], $sku, $type = 'normal', $width = 100, $height = 100, $platform = Platform::CODE_JOOM);
        }
		if($productInfo['extra_images']){
            $tmpImageArray = explode("|", $productInfo['extra_images']);
            foreach($tmpImageArray as $image) {
                $skuImg['ft'][$image] = ProductImageAdd::getImageUrlFromRestfulByFileName($image, $sku, $type = 'normal', $width = 100, $height = 100, $platform = Platform::CODE_JOOM);
            }
		}



        $listingProduct = array(
				'parentSku'		=> $parentSku,
				'sku'           => $sku,
				'skuImg'        => $skuImg,
				'skuInfo'       => $productInfo,
		);
		$listingSubSKU = $joomProductAddModel->getProductVariantsByProductAddId($addId);
		if($productInfo['upload_status'] != JoomProductAdd::JOOM_UPLOAD_SUCCESS){
			$saveType = JoomProductAdd::SAVE_TYPE_ALL;
		}elseif($listingSubSKU['hasUploadFailNum']>0){
			$saveType = JoomProductAdd::SAVE_TYPE_ONLY_SUBSKU;
		}
		$this->render('update', array(
				'listingParam'	=>	$listingParam,
				'listingProduct' => $listingProduct,
				'listingSubSKU'	=>	$listingSubSKU['skuList'],
				'attributeList'	=>	$listingSubSKU['attributeList'],
				'action'		=>	'update',
				'isSubSku'		=>	false,
				'saveType'		=>	$saveType,
				'addId'			=>	$addId,
				'currentNavTab' =>	'page'.UebModel::model('Menu')->getIdByUrl('/joom/joomproductaddlist/index')
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
				throw new Exception(Yii::t('joom_listing', 'Param error'));
			}
			$joomProductVariantModel = new JoomProductVariantsAdd;
			if($joomProductVariantModel->deleteProductVariant('add_id=:add_id AND sku=:sku', 
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
			//0为基本产品，1为子sku产品，2为多属性值产品
			$productIsMulti = Yii::app()->request->getParam('product_is_multi');
			//parent_sku
			$parentSku = Yii::app()->request->getParam('parent_sku');
			$sku = Yii::app()->request->getParam('sku');
			//action
			$action = Yii::app()->request->getParam('action');
			//img
			$skuImage = Yii::app()->request->getParam('skuImage');
			$addId = Yii::app()->request->getParam('add_id');
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


			if(empty($skuImage) || empty($mainImg)){
				//throw  new Exception(Yii::t('joom_listing', "No main image can't upload"));
			}
			
			//skuinfo 
			$skuinfo = Yii::app()->request->getParam('skuinfo');
			//baseinfo
			$baseinfo = Yii::app()->request->getParam('baseinfo');
			//accounts
			$accounts = Yii::app()->request->getParam('account_id');
			if(!$parentSku || !$sku || !$skuinfo){
				throw new Exception(Yii::t('joom_listing', 'Param error'));
			}
			if(empty($accounts)){
				throw new Exception(Yii::t('joom_listing', 'Account Error'));
			}
			//过滤掉未选择的子sku
			$joomAddSelupload = Yii::app()->request->getParam('joom_add_selupload');
			if($action == 'add' && empty($joomAddSelupload)){
				throw new Exception(Yii::t('joom_listing', 'Anyhow need to chose one SKU'));
			}

			$skuInfo = Product::model()->getProductInfoBySku($sku);

			//验证主sku
			Product::model()->checkPublishSKU($productIsMulti,$skuInfo);

			//检查sku信息是否填写完整
			foreach ($skuinfo as $k=>$sku){
				if($action == 'add' && !in_array($k, $joomAddSelupload)){ 
					unset($skuinfo[$k]);
					continue;
				}
				$sku['price'] = floatval($sku['price']);
				$sku['market_price'] = floatval($sku['market_price']);
				$sku['shipping'] = floatval($sku['shipping']);
				if(!is_numeric($sku['inventory']) 
					|| empty($sku['price'])
					|| empty($sku['market_price'])){
					throw new Exception(Yii::t('joom_listing', 'Please check sku info'));
				}
				$sku['inventory'] = intval($sku['inventory']);
				$skuinfo[$k] = $sku;
			}
			//检查基本信息是否填写完整
			if($baseinfo){
				foreach ($baseinfo as $info){
					if(empty($info['subject']) || empty($info['detail']))
						throw new Exception(Yii::t('joom_listing', 'Please Check Subject Or Description'));
					
					if(!empty($info['tags'])){
						foreach ($info['tags'] as $k=>$tag){
							if(empty($tag)) unset($info['tags'][$k]);
						}
					}
					if(empty($info['tags']))
						throw new Exception(Yii::t('joom_listing',  'Tags required'));
				}
			}elseif($sku == $parentSku){
				throw new Exception(Yii::t('joom_listing', 'Please Check Subject Or Description'));
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
				$baseinfo[$accountId]['add_id'] = $addId;
				$mainSku = array(
						'parent_sku' => $parentSku,
						'main_image' => $mainImg,
						'extra_images' => $extraImg,
						'product_is_multi'	=>	$productIsMulti
				);
				$baseinfo[$accountId] = array_merge($baseinfo[$accountId], $mainSku);
			}
			if($productIsMulti == JoomProductAdd::PRODUCT_IS_SINGLE){
				$model = new JoomProductVariantsAdd;
				$res = $model->saveJoomAddVariantsData($baseinfo);
			}else{
				$model = new JoomProductAdd;
				$res = $model->saveJoomAddData($baseinfo);
			}
			if(!$res){
				$model->getErrorMsg();
				throw new Exception(Yii::t('system', 'Save failure')); 
			}
			if($action == 'update'){
				$navTabId = 'page'.UebModel::model('Menu')->getIdByUrl('/joom/joomproductaddlist/index');
			}else{
				$navTabId = 'page'.UebModel::model('Menu')->getIdByUrl('/joom/joomproductadd/index');
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
	 * @desc 只保存sku信息并且有主刊登信息
	 * @throws Exception
	 */
	private function _saveinfo2(){
		try{
			//0为基本产品，1为子sku产品，2为多属性值产品
			$productIsMulti = Yii::app()->request->getParam('product_is_multi');
			//parent_sku
			$parentSku = Yii::app()->request->getParam('parent_sku');
			$sku = Yii::app()->request->getParam('sku');
			//action
			$action = Yii::app()->request->getParam('action');
			
			//skuinfo
			$skuinfo = Yii::app()->request->getParam('skuinfo');
			
			$addId = Yii::app()->request->getParam('add_id');
			
			//accounts
			$accounts = Yii::app()->request->getParam('account_id');
			if(!$parentSku || !$sku || !$skuinfo){
				throw new Exception(Yii::t('joom_listing', 'Param error'));
			}
			if(empty($accounts)){
				throw new Exception(Yii::t('joom_listing', 'Account Error'));
			}
			//过滤掉未选择的子sku
			$joomAddSelupload = Yii::app()->request->getParam('joom_add_selupload');
			if($action == 'add' && empty($joomAddSelupload)){
				throw new Exception(Yii::t('joom_listing', 'Anyhow need to chose one SKU'));
			}
			//检查sku信息是否填写完整
			foreach ($skuinfo as $k=>$sku){
				if($action == 'add' && !in_array($k, $joomAddSelupload)){
					unset($skuinfo[$k]);
					continue;
				}
				$sku['price'] = floatval($sku['price']);
				$sku['market_price'] = floatval($sku['market_price']);
				$sku['shipping'] = floatval($sku['shipping']);
				if(!is_numeric($sku['inventory'])
				|| empty($sku['price'])
				|| empty($sku['market_price'])){
					throw new Exception(Yii::t('joom_listing', 'Please check sku info'));
				}
				$sku['inventory'] = intval($sku['inventory']);
				$skuinfo[$k] = $sku;
			}
			
			foreach ($accounts as $accountId){
				$baseinfo[$accountId]['variants'] = $skuinfo;
				$baseinfo[$accountId]['parent_sku'] = $parentSku;
				$baseinfo[$accountId]['add_id'] = $addId;
			}
			
			$model = new JoomProductVariantsAdd;
			$res = $model->saveJoomAddVariantsData($baseinfo);
			
			if(!$res){
				$model->getErrorMsg();
				throw new Exception(Yii::t('system', 'Save failure'));
			}
			if($action == 'update'){
				$navTabId = 'page'.UebModel::model('Menu')->getIdByUrl('/joom/joomproductaddlist/index');
			}else{
				$navTabId = 'page'.UebModel::model('Menu')->getIdByUrl('/joom/joomproductadd/index');
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
				throw new Exception(Yii::t('joom_listing', 'Param error'));
			}
			if(empty($accounts)){
				throw new Exception(Yii::t('joom_listing', 'Account Error'));
			}
			//过滤掉未选择的子sku
			$joomAddSelupload = Yii::app()->request->getParam('joom_add_selupload');
			if($action == 'add' && empty($joomAddSelupload)){
				throw new Exception(Yii::t('joom_listing', 'Anyhow need to chose one SKU'));
			}
			//检查sku信息是否填写完整
			foreach ($skuinfo as $k=>$sku){
				if($action == 'add' && !in_array($k, $joomAddSelupload)){
					unset($skuinfo[$k]);
					continue;
				}
				$sku['price'] = floatval($sku['price']);
				$sku['market_price'] = floatval($sku['market_price']);
				$sku['shipping'] = floatval($sku['shipping']);
				if(!is_numeric($sku['inventory'])
				|| empty($sku['price'])
				|| empty($sku['market_price'])){
					throw new Exception(Yii::t('joom_listing', 'Please check sku info'));
				}
				$sku['inventory'] = intval($sku['inventory']);
				$skuinfo[$k] = $sku;
			}
			
			foreach ($accounts as $accountId){
				$baseinfo[$accountId]['variants'] = $skuinfo;
				//获取主信息
				$listingInfo = UebModel::model('JoomListing')->find('account_id=:account_id AND sku=:sku', 
													array(':account_id'=>$accountId, ':sku'=>$parentSku));
				if(empty($listingInfo)){
					throw new Exception(Yii::t('joom_listing', 'Not found the sku'));
				}
				//获取描述信息
				$extendInfo = UebModel::model('JoomListingExtend')->find('listing_id='.$listingInfo['id']);
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
			$model = new JoomProductAdd;
			$res = $model->saveJoomAddData($baseinfo, JoomProductAdd::SAVE_TYPE_NO_MAIN_SKU);
			if(!$res){
				$model->getErrorMsg();
				throw new Exception(Yii::t('system', 'Save failure'));
			}
			if($action == 'update'){
				$navTabId = 'page'.UebModel::model('Menu')->getIdByUrl('/joom/joomproductaddlist/index');
			}else{
				$navTabId = 'page'.UebModel::model('Menu')->getIdByUrl('/joom/joomproductadd/index');
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
		$saveType = Yii::app()->request->getParam('save_type');

		switch($saveType){
			case JoomProductAdd::SAVE_TYPE_ALL:
				$this->_saveinfo1();
				break;
			case JoomProductAdd::SAVE_TYPE_ONLY_SUBSKU:
				$this->_saveinfo2();
				break;
			case JoomProductAdd::SAVE_TYPE_NO_MAIN_SKU:
				$this->_saveinfo4();
				break;
			default:
				echo $this->failureJson(array(
						'message' => Yii::t('joom_listing', 'Illegal Operation'),
				));
				Yii::app()->end();
				break;
		}
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
		$accounts = JoomProductAdd::model()->getAbleAccountsBySku($sku);
		echo json_encode($accounts);exit;
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
			$productaddModel = new JoomProductAdd;
			$data = $productaddModel->getProductAddInfo("id=:id", array(':id'=>$addId));
			if(empty($data)){
				throw new Exception(Yii::t('wish_listing', 'NO main sku'));
			}
			$accountId = $data['account_id'];
			$joomLog = new JoomLog;
			//创建运行日志
			$logId = $joomLog->prepareLog($accountId,  JoomProductAdd::EVENT_UPLOAD_PRODUCT);
			if(!$logId) {
				throw new Exception(Yii::t('joom_listing', 'Log create failure'));
			}
			//检查账号是可以提交请求报告
			$checkRunning = $joomLog->checkRunning($accountId, JoomProductAdd::EVENT_UPLOAD_PRODUCT);
			if(!$checkRunning){
				$joomLog->setFailure($logId, Yii::t('systems', 'There Exists An Active Event'));
				throw new Exception(Yii::t('systems', 'There Exists An Active Event'));
			}
			//插入本次log参数日志(用来记录请求的参数)
			$eventLog = $joomLog->saveEventLog(JoomProductAdd::EVENT_UPLOAD_PRODUCT, array(
					'log_id'        => $logId,
					'account_id'    => $accountId,
					'start_time'    => date('Y-m-d H:i:s'),
					'end_time'      => date('Y-m-d H:i:s'),
			));
			//设置日志为正在运行
			$joomLog->setRunning($logId);
			if(!$addId){
				throw new Exception(Yii::t('joom_listing', 'Param error'));
			}
			
			
			$res = $productaddModel->uploadProduct($addId);
			if($res){
				$joomLog->setSuccess($logId);
				$joomLog->saveEventStatus(JoomProductAdd::EVENT_UPLOAD_PRODUCT, $eventLog, JoomLog::STATUS_SUCCESS);
				echo $this->successJson(array(
						'message' => Yii::t('system', 'Operate Successful'),
				));
				Yii::app()->end();
			}else{
				$joomLog->setFailure($logId, $productaddModel->getErrorMsg());
				$joomLog->saveEventStatus(JoomProductAdd::EVENT_UPLOAD_PRODUCT, $eventLog, JoomLog::STATUS_FAILURE);
				throw new Exception($productaddModel->getErrorMsg());
			}
		}catch(Exception $e){
			echo $this->failureJson(array(
					'message' => Yii::t('joom_listing', 'Joom upload failure').":".$e->getMessage(),
			));
			Yii::app()->end();
		}
	}
	
	/**
	 * @desc 从wish产品列表导入到joom待刊登列表
	 */
	public function actionImportlisting(){
		set_time_limit(2*3600);
		ini_set("display_errors", true);
		error_reporting(E_ALL);
		ini_set('memory_limit','512M');
		$type = Yii::app()->request->getParam("type");
		$limit = Yii::app()->request->getParam("limit");
		$accountID = Yii::app()->request->getParam("account_id");
		$sku = Yii::app()->request->getParam("sku");
		$bug = Yii::app()->request->getParam("bug");
		if(empty($accountID)){
			exit("指定账号ID:1或者2等");
		}
		$joomProductAddModel         = new JoomProductAdd();
		$joomProductVariantsAddModel = new JoomProductVariantsAdd();
		$warehouseSkuMapModel        = new WarehouseSkuMap();

	
		
		$where = "l.review_status = 'approved' and l.enabled=1 AND l.sku IN(select parent_sku from market_joom.ueb_joom_product_temporary)";
		if($sku){
			$skuArr = explode(",", $sku);
			$where .= " and l.sku in(".MHelper::simplode($skuArr).") ";
		}
		$sql = "select l.id,l.sku from market_wish.ueb_wish_listing l 
				where {$where} 
				group by l.sku";

		// $sql = "select l.id,l.sku from market_wish.ueb_wish_listing l 
		// 		left join market_joom.ueb_joom_listing j on j.sku=l.sku 
		// 		left join market_joom.ueb_joom_product_add ja on ja.parent_sku=l.sku 
		// 		where {$where} 
		// 		and ISNULL(j.id) 
		// 		and ISNULL(ja.id)
		// 		group by l.sku";
		/* $sql1 = "select *,d.description from market_wish.ueb_wish_listing l 
				left join market_wish.ueb_wish_listing_extend d on d.listing_id=l.id where {$where} group by sku"; */
		
		if($limit){
			$sql .= " limit {$limit} ";
		}
		$sql1 = "select l.*,d.description from market_wish.ueb_wish_listing l
				left join market_wish.ueb_wish_listing_extend d on d.listing_id=l.id where l.id=";
		$sql2 = "select * from market_wish.ueb_listing_variants where listing_id=";

		$listing = $joomProductAddModel->getDbConnection()->createCommand($sql)->queryAll();
		if($bug){
			echo "sql:", $sql, "<br/>";
		}
		
		try{
			if($listing){
				foreach ($listing as $idd){
					//过滤子sku
					/* if(strpos($idd['sku'], ".")){
						continue;
					} */
					$sqlm = $sql1.$idd['id'];
					$sql3 = $sql2.$idd['id'];
					if($bug){
						echo "sqlm:".$sqlm, "<br/>";
						echo "sql3:".$sql3, "<br/>";
					}
					$list = $joomProductAddModel->getDbConnection()->createCommand($sqlm)->queryRow();
					if($bug){
						echo "<br/>=====list====<br/>";
						var_dump($list);
						echo "<br/>";
					}
					if(empty($list)){
						continue;
					}
					
					//只要刊登过就不需要在刊登了
					//判断是否在刊登列表里面了
					// $parentAddSkuInfo = UebModel::model('JoomProductAdd')->getProductAddInfo("parent_sku=:parent_sku",array(':parent_sku'=>$list['sku']));
					// if($bug){
					// 	echo "parentAddSkuInfo1 : ". ($parentAddSkuInfo ? "跳过" : "未跳过");
					// 	echo "<br/>";
					// }
					// if($parentAddSkuInfo) continue;
					//判断是否在产品管理里面了
					$parentAddSkuInfo = UebModel::model('JoomProduct')->find("sku=:sku AND (review_status = 'approved' OR review_status = 'pending')",array(':sku'=>$list['sku']));
					if($bug){
						echo "parentAddSkuInfo2 : ". ($parentAddSkuInfo ? "跳过" : "未跳过");
						echo "<br/>";
					}
					if($parentAddSkuInfo) continue;

					//取出sku属性
					$skuInfos = Product::model()->getProductBySku($list['sku']);
					if(!$skuInfos){
						continue;
					}

					$skuWhereArr = array($list['sku']);
					//判断sku是否在售状态
					if($skuInfos['product_status'] != 4){
						continue;
					}

					//如果为多属性取出子sku
					if($skuInfos['product_is_multi'] == 2){
						//取出子sku
						$attributeSku = ProductSelectAttribute::model()->getChildSKUListByProductID($skuInfos['id']);
						if(!$attributeSku){
							continue;
						}

						$skuWhereArr = $attributeSku;
					}

					//库存量大于等于10
		            $conditions = 't.available_qty >= :available_qty 
		                            AND t.warehouse_id = :warehouse_id 
		                            AND p.product_is_multi IN(0,1) 
		                            AND p.product_status = 4 
		                            AND t.sku IN ('.MHelper::simplode($skuWhereArr).')';
		            $param = array(':available_qty'=>10, ':warehouse_id'=>WarehouseSkuMap::WARE_HOUSE_GM);
		            $skuList = $warehouseSkuMapModel->getSkuListLeftJoinProductByCondition($conditions, $param, '', 't.sku');
		            if(!$skuList){
		                continue;            
		            }
					
					$variantListing = $joomProductAddModel->getDbConnection()->createCommand($sql3)->queryAll();
					if($bug){
						echo "<br>======variantListing======<br/>";
						print_r($variantListing);
						echo "<br/>";
					}
					$data = array(
							'parent_sku'		=>	$list['sku'],
							'subject'			=>	$list['name'],
							'detail'			=>	$list['description'],
							'tags'				=>	$list['tags'],
							'brand'				=>	$list['brand'],
							'main_image'		=>	$list['main_image'],
							'extra_images'		=>	$list['extra_images'],
							'product_is_multi'	=>	$list['is_varation'],
							'remote_main_img'	=>	$list['main_image'],
							'remote_extra_img'	=>	$list['extra_images'],
							'variants'			=>	array()
					);
					$variants = array();
					if($variantListing){
						foreach ($variantListing as $variant){
							$variantData = array(
									'sku'			=>	$variant['sku'],
									'inventory'		=>	1000,
									'price'			=>	$variant['price']+$variant['shipping'],
									'shipping'		=>	0,
									'market_price'	=>	$variant['msrp'],
									'color'			=>	$variant['color'],
									'size'			=>	$variant['size'],
									'main_image'	=>	$variant['all_image']
							);
							if(empty($variant['all_image'])){
								//@todo 获取子sku图片
								//获取子SKU一张图
								$images = Product::model()->getImgList($variant['sku'], 'ft');
								if($images){
									$imgname = array_shift($images);
									$basefilename = basename($imgname);
									if(strtolower($basefilename) == $variant['sku'].".jpg" && count($images)>1){
										$imgname = array_shift($images);
									}
									$remoteImgUrl = (string)UebModel::model("JoomProductAdd")->getRemoteImgPathByName($imgname, $accountID, $variant['sku']);
			
									$variantData['main_image'] = $remoteImgUrl;
								}
							}
			
							$variants[] = $variantData;
						}
					}

					$data['variants'] = $variants;
					$addData = array();
					
					$addData[$accountID] = $data;
						
					if($addData){
						$res = $joomProductAddModel->saveJoomAddData($addData, null, JoomProductAdd::ADD_TYPE_COPY);
						if($bug){
							echo $joomProductAddModel->getErrorMsg();
							echo "<br/>";
							var_dump($res);
							echo "<br/>";
						}
					}
					if($bug){
						echo "<br/>====res===<br/>";
						var_dump($res);
					}
					
				}
			}
		}catch (Exception $e){
			echo "<br/>=====message=====<br/>";
			echo $e->getMessage();
		}
		echo "<br>";
		echo "done!!";
	}

	
	
	/**
	 * @desc 从产品库导入到待刊登列表
	 */
	public function actionImportnotaglisting(){
		set_time_limit(10*3600);
		ini_set("display_errors", true);
		error_reporting(E_ALL);
		$saveType = JoomProductAdd::SAVE_TYPE_ALL;
		$accountId = Yii::app()->request->getParam('account_id');
		$limit = Yii::app()->request->getParam('limit');
		if(empty($accountId)) {
			exit("没有指定账号ID");
		}
		//获取sku
		$sql0 = "select id,sku from ueb_joom_sku_list where status=0";
		if($type){
			$sql0 .= " and type='{$type}'";
		}
		if($limit){
			$sql0 .= " limit {$limit} ";
		}
		$joomProductAddModel = new JoomProductAdd();
		$skuList = $joomProductAddModel->getDbConnection()->createCommand($sql0)->queryAll();
		if(empty($skuList)) exit("没有sku列表了");
		$skuIds = array();
		$skuarr = array();
		foreach ($skuList as $val){
			$skuarr[] = $val['sku'];
			$skuIds[] = $val['id'];
		}
		foreach ($skuarr as $sku){
			$skuInfo = Product::model()->getProductInfoBySku($sku);
			if(empty($skuInfo)) continue;
			//为子sku时，判断父SKU是否已经刊登过了
			$parentSku = $sku;
			//去除描述中的html标签
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
			
			//获取账号
			$account = JoomAccount::getAccountInfoByIds($accountId);
			if(empty($account)) break;
			//目前账号限制在了一个
			$accountList = array();
			//判断是否在刊登列表里面了
			$parentAddSkuInfo = UebModel::model('JoomProductAdd')->getProductAddInfo("parent_sku=:parent_sku AND account_id=:account_id",array(':parent_sku'=>$parentSku, ':account_id'=>$account['id']));
			if($parentAddSkuInfo) continue;
			
			/**@ 获取产品信息*/
			$config = ConfigFactory::getConfig('serverKeys');
			$skuImg = array();
			$images = Product::model()->getImgList($sku,'ft');
			foreach($images as $k=>$img){
				$skuImg[] = $config['oms']['host'].$img;
			}
			if(empty($skuImg)) continue;
			$mainImg = $skuImg[0];
			unset($skuImg[0]);
			$extraImg = implode("|", $skuImg);
			$data = array();
			$data['parent_sku']	=	$sku;
			$data['subject'] = !empty($skuInfo['title']['english'])?$skuInfo['title']['english']:'';
			$data['brand'] = !empty($skuInfo['brand_info']['brand_en_name'])?$skuInfo['brand_info']['brand_en_name']:'';
			$data['tags'] = '';
			$data['detail'] = !empty($skuInfo['description']['english'])?"Description:\n".$skuInfo['description']['english']:'';
			if(!empty($skuInfo['included']['english'])){
				$data['detail'] .= "\nIncluded:\n".$skuInfo['included']['english'];
			}
			$data['main_image'] = $mainImg;
			$data['extra_images'] = $extraImg;
			$data['product_is_multi'] = $skuInfo['product_is_multi'] == Product::PRODUCT_MULTIPLE_MAIN ? 1 : 0;
			$data['variants'] = array();
			
			//判断是否已经在joom产品列表中
			$listingInfo = UebModel::model('JoomListing')->find('sku=:sku AND account_id=:account_id',
					array(':sku'=>$sku, ':account_id'=>$accountId));
			if($listingInfo){
				continue;
			}
			unset($listingInfo);
			
			//获取子SKU信息
			$listingSubSKU = JoomProductAdd::getSubProductByMainProductId($skuInfo['id'], $skuInfo['product_is_multi']);
			//获取物流方式列表
			$logisticsList = JoomProductAdd::$logisticsType;
			$shipCode = Yii::app ()->request->getParam ( 'ship_code' );
			if (! $shipCode) {
				$attributes = Product::model ()->getAttributeBySku ( $sku, 'product_features' ); // 属性
				if ($attributes) {
					// 特殊属性的按黑龙江俄速通亚欧小包
					$shipCode = Logistics::CODE_CM_YO_EST;
				} else {
					// 普通的是易时达俄罗斯专线
					$shipCode = Logistics::CODE_CM_HUNGARY;
				}
			}
			$variants = array();
			// 增加价格统计
			if ($listingSubSKU ['skuList']) {
				foreach ( $listingSubSKU ['skuList'] as $key => $skuRow ) {
					$salePrice = JoomProductAdd::getSalePrice ( $skuRow ['sku'], null, $shipCode );
					$variants [$key] ['shipping'] = $salePrice ['shipPrice'];
					$variants [$key] ['market_price'] = round ( $salePrice ['salePrice'] * 1.8, 2 );
					$variants [$key] ['price'] = $salePrice ['salePrice'];
					$variants [$key] ['inventory'] = JoomProductAdd::PRODUCT_PUBLISH_INVENTORY;
					$variants [$key] ['sku'] = $skuRow['sku'];
					if ($listingSubSKU['attributeList']){
						foreach ($listingSubSKU['attributeList'] as $attribute){
							if(isset($skuRow['attribute'][$attribute['id']])){ 
								$variants [$key] [$attribute['attribute_name']] = $skuRow['attribute'][$attribute['id']]['attribute_value_name']; 
							}
						}
					}
					//图片
					$images = Product::model()->getImgList($skuRow['sku'],'ft');
					foreach($images as $k=>$img){
						$variants [$key] ['main_image'] = $config['oms']['host'].$img;
						break;
					}
				}
			}else{
				$salePrice = JoomProductAdd::getSalePrice($sku, null, $shipCode);
				$variants [0]['shipping'] 	 = $salePrice['shipPrice'];
				$variants [0]['market_price'] = round($salePrice['salePrice']*1.8, 2);
				$variants [0]['price'] = $salePrice['salePrice'];
				$variants [0]['inventory']	 = JoomProductAdd::PRODUCT_PUBLISH_INVENTORY;
				$variants [0]['sku'] = $sku;
			}
			$data['variants'] = $variants;
			JoomProductAdd::model()->saveJoomAddData(array($accountId => $data));
		}
		if($skuIds){
			$joomProductAddModel->getDbConnection()->createCommand()->update("ueb_joom_sku_list", array('status'=>1), array("IN", "id", $skuIds));
		}
		echo "done!!";
	}
	
	public function actionTest(){
		
		
		$neworder = AutoCode::getCodeNew("order");
		var_dump($neworder);
		
		exit;
		$imgName = "http://k.fozoom.com/image/118626.07/normal/118626.07-1.jpg?1=1&width=800&height=800";
		
		$res = JoomProductAdd::model()->getRemoteImgPathByName('x', 1, '119581');
		
		var_dump($res);
		exit;
		$imageName = basename($imgName);
		var_dump($imageName);
		if(empty($sku)){
			$pos = strrpos($imageName, "-");
			if($pos === false){
				$pos = strrpos($imageName, ".");
			}
			$sku = substr($imageName, 0, $pos);
		}
		var_dump($sku);
		exit;
		
		$images = Product::model()->getImgList('102850.03', 'ft');
		var_dump($images);
		//$url = "http://172.16.1.11//upload/image/assistant/1/1/7/2/117219-1.jpg";
		
		$url = array_shift(array_slice($images, 1, 1));
		$remoteUrl = JoomProductAdd::model()->getRemoteImgPathByName($url, 12);
		var_dump($remoteUrl);
		exit;
		
	}


	/**
	 * @desc 获取利润和利润率
	 */
	public function actionGetjoomproductprofitinfo(){
		$sku       = Yii::app()->request->getParam('sku');
		$salePrice = Yii::app()->request->getParam('sale_price');
		$shipPrice = Yii::app()->request->getParam('ship_price','0');
		//获取运费
/*		$attributesFeatures = Product::model()->getAttributeBySku($sku, 'product_features');//属性
        if($attributesFeatures){
            //特殊属性的按黑龙江俄速通亚欧小包
            $shipCode = Logistics::CODE_CM_YO_EST;
        }else{
            //普通的是易时达俄罗斯专线
            $shipCode = Logistics::CODE_CM_HUNGARY;
        }*/

        $status = 500;
        $profit = $profitRate = false;
        $salePriceInfo = JoomProductAdd::model()->getListingProfit($sku, $salePrice, '', $shipPrice);
        $salePriceInfo->getProfit();
        if($salePriceInfo){
        	$status     = 200;
        	$profit     = $salePriceInfo->profit;
        	$profitRate = $salePriceInfo->profitRate *100;
        }
        $result = array(
    			'statusCode' => $status,
    			'data' => array(
					'profit' => $profit,
					'profitRate' => $profitRate,
    			),
    	);

        echo json_encode($result);exit;
	}


	/**
	 * @desc 从aliexpress产品列表导入到joom待刊登列表
	 * @link /joom/joomproductadd/importaliexpresslisting/account_id/7/limit/20000/bug/1
	 */
	public function actionImportaliexpresslisting(){
		set_time_limit(2*3600);
		ini_set("display_errors", true);
		error_reporting(E_ALL);
		ini_set('memory_limit','512M');
		$type = Yii::app()->request->getParam("type");
		$limit = Yii::app()->request->getParam("limit");
		$accountID = Yii::app()->request->getParam("account_id");
		$sku = Yii::app()->request->getParam("sku");
		$bug = Yii::app()->request->getParam("bug");
		if(empty($accountID)){
			exit("指定账号ID:1或者2等");
		}
		$joomProductAddModel         = new JoomProductAdd();
		$joomProductVariantsAddModel = new JoomProductVariantsAdd();
		$warehouseSkuMapModel        = new WarehouseSkuMap();

	
		
		$where = "l.product_status_type = 'onSelling'";
		if($sku){
			$skuArr = explode(",", $sku);
			$where .= " and l.sku in(".MHelper::simplode($skuArr).") ";
		}

		//连接指定sku的sql
		$skusArr = array('72768','74515','31475','89459','92766','93126','94643','95302','95325','95979','97828','99902','100444','102568','104079','108931','110570','112265','113148','113215','114231','115537','120647');
		$where .= ' AND l.sku IN ('.MHelper::simplode($skusArr).')';
		$sql = "select l.id,l.sku from market_aliexpress.ueb_aliexpress_product l  
				where {$where} 
				group by l.sku";

		//连接速卖通产品表sql
		// $sql = "select l.id,l.sku from market_aliexpress.ueb_aliexpress_product l 
		// 		left join market_joom.ueb_joom_listing j on j.sku=l.sku 
		// 		left join market_joom.ueb_joom_product_add ja on ja.parent_sku=l.sku 
		// 		where {$where} 
		// 		and ISNULL(j.id) 
		// 		and ISNULL(ja.id)
		// 		group by l.sku";
		
		if($limit){
			$sql .= " limit {$limit} ";
		}
		$sql1 = "select l.* from market_aliexpress.ueb_aliexpress_product l where l.id=";
		$sql2 = "select * from market_aliexpress.ueb_aliexpress_product_variation where product_id=";

		$listing = $joomProductAddModel->getDbConnection()->createCommand($sql)->queryAll();
		if($bug){
			echo "sql:", $sql, "<br/>";
		}
		
		try{
			if($listing){
				foreach ($listing as $idd){
					//过滤子sku
					/* if(strpos($idd['sku'], ".")){
						continue;
					} */
					$sqlm = $sql1.$idd['id'];
					$sql3 = $sql2.$idd['id'];
					if($bug){
						echo "sqlm:".$sqlm, "<br/>";
						echo "sql3:".$sql3, "<br/>";
					}
					$list = $joomProductAddModel->getDbConnection()->createCommand($sqlm)->queryRow();
					if($bug){
						echo "<br/>=====list====<br/>";
						var_dump($list);
						echo "<br/>";
					}
					if(empty($list)){
						continue;
					}
					
					//只要刊登过就不需要在刊登了
					//判断是否在刊登列表里面了
					// $parentAddSkuInfo = UebModel::model('JoomProductAdd')->getProductAddInfo("parent_sku=:parent_sku",array(':parent_sku'=>$list['sku']));
					// if($bug){
					// 	echo "parentAddSkuInfo1 : ". ($parentAddSkuInfo ? "跳过" : "未跳过");
					// 	echo "<br/>";
					// }
					// if($parentAddSkuInfo) continue;
					//判断是否在产品管理里面了
					// $parentAddSkuInfo = UebModel::model('JoomProduct')->find("sku=:sku AND (review_status = 'approved' OR review_status = 'pending')",array(':sku'=>$list['sku']));
					// if($bug){
					// 	echo "parentAddSkuInfo2 : ". ($parentAddSkuInfo ? "跳过" : "未跳过");
					// 	echo "<br/>";
					// }
					// if($parentAddSkuInfo) continue;

					//取出sku属性
					$skuInfos = Product::model()->getProductBySku($list['sku']);
					if(!$skuInfos){
						continue;
					}

					$skuInfoMuli = Product::model()->getProductInfoBySku($list['sku']);
					//去除描述中的html标签
					if(!empty($skuInfoMuli['description'])){
						foreach ($skuInfoMuli['description'] as $key=>$val){
							$val = preg_replace_callback("/(?<TT><\/?(?<TAG>\w+).*?>)/im", array($this, 'preg_replace_call_func'), $val);
							$val = preg_replace("/(\n){2,}|\r\n/ie", "\n", $val);
							$val = strip_tags($val);
							$skuInfoMuli['description'][$key] = $val;
						}
					}
					//去除included里面html标签
					if(!empty($skuInfoMuli['included'])){
						foreach ($skuInfoMuli['included'] as $key=>$val){
							$val = preg_replace_callback("/(?<TT><\/?(?<TAG>\w+).*?>)/im", array($this, 'preg_replace_call_func'), $val);
							$val = preg_replace("/(\n){2,}|\r\n/ie", "\n", $val);
							$val = strip_tags($val);
							$skuInfoMuli['included'][$key] = $val;
						}
					}

					$description = '';
					if($skuInfoMuli['description']['english']){
						$description = "Description:\n".$skuInfoMuli['description']['english'];
					}
					if($skuInfoMuli['included']['english']){
						$description .= "\nIncluded:\n".$skuInfoMuli['included']['english'];
					}

					//获取物流方式
					$attributes = Product::model()->getAttributeBySku($list['sku'], 'product_features');//属性
                    if($attributes){
                            //特殊属性的按黑龙江俄速通亚欧小包
                            //$shipCode = Logistics::CODE_CM_SF;
                            $shipCode = Logistics::CODE_CM_YO_EST;
                    }else{
                            //普通的是易时达俄罗斯专线
                            $shipCode = Logistics::CODE_CM_HUNGARY;
                    }

					$skuWhereArr = array($list['sku']);
					//判断sku是否在售状态
					if($skuInfos['product_status'] != 4){
						continue;
					}

					//如果为多属性取出子sku
					if($skuInfos['product_is_multi'] == 2){
						//取出子sku
						$attributeSku = ProductSelectAttribute::model()->getChildSKUListByProductID($skuInfos['id']);
						if(!$attributeSku){
							continue;
						}

						$skuWhereArr = $attributeSku;
					}

					//库存量大于等于10
		            $conditions = 't.available_qty >= :available_qty 
		                            AND t.warehouse_id = :warehouse_id 
		                            AND p.product_is_multi IN(0,1) 
		                            AND p.product_status = 4 
		                            AND t.sku IN ('.MHelper::simplode($skuWhereArr).')';
		            $param = array(':available_qty'=>10, ':warehouse_id'=>WarehouseSkuMap::WARE_HOUSE_GM);
		            $skuList = $warehouseSkuMapModel->getSkuListLeftJoinProductByCondition($conditions, $param, '', 't.sku');
		            if(!$skuList){
		                continue;            
		            }
					
					$variantListing = $joomProductAddModel->getDbConnection()->createCommand($sql3)->queryAll();
					if($bug){
						echo "<br>======variantListing======<br/>";
						print_r($variantListing);
						echo "<br/>";
					}

					//获取产品图片
					$imageType = array('zt', 'ft');
					$config = ConfigFactory::getConfig('serverKeys');
					$skuImage = array();
					foreach($imageType as $type){
						$images = Product::model()->getImgList($list['sku'],$type);
						foreach($images as $k=>$img){
							if($k == $list['sku']) continue;
							$skuImage[$type][] = $config['oms']['host'].$img;
						}
					}

					$mainImg = $extraImg = '';
					if($skuImage){
						$mainImg = !empty($skuImage['zt'][0]) ? $skuImage['zt'][0] : '';
						if(!empty($skuImage['ft'])){
							$extraImg = $skuImage['ft'];
							if(!$mainImg){
								$mainImg = $extraImg[0];
								unset($extraImg[0]);
							}
							$extraImg = implode("|",  $extraImg);
						}
					}

					$subject = trim($list['subject']);

					//更新tags
		        	$tags = '';
		        	$nameArr = explode(' ', $subject);
		        	if($nameArr){
			    		foreach ($nameArr as $k => $v) {
			    			if($k >= 12) break;
			    			$tags .= $v.',';
			    		}
			    	}

			    	//主图为空不添加数据
			    	if(!$mainImg){
			    		continue;
			    	}

			    	//判断多属性值  速卖通的单品值为1--joom的值为0   速卖通的多属性值为2--joom的值为2
			    	if($list['is_variation'] == 1){
			    		$list['is_variation'] = 0;
			    	}

					$data = array(
							'parent_sku'		=>	$list['sku'],
							'subject'			=>	$subject,
							'detail'			=>	$description,
							'tags'				=>	rtrim($tags,','),
							'brand'				=>	'',
							'main_image'		=>	$mainImg,
							'extra_images'		=>	$extraImg,
							'product_is_multi'	=>	$list['is_variation'],
							'remote_main_img'	=>	'',
							'remote_extra_img'	=>	'',
							'variants'			=>	array()
					);

					//获取颜色和尺寸
					$listingSubSKU = JoomProductAdd::getSubProductByMainProductId($skuInfos['id'], $skuInfos['product_is_multi']);

					$variants = array();
					$variantData = array();
					if($variantListing){
						foreach ($variantListing as $variant){

							//判断是否在刊登列表里面了
							// $variantAddSkuInfo = UebModel::model('JoomProductAdd')->getProductAddInfo("parent_sku=:parent_sku",array(':parent_sku'=>$variant['sku']));
							// if($variantAddSkuInfo) break;
							//判断是否在产品管理里面了
							// $variantAddSkuInfo = UebModel::model('JoomProduct')->find("sku=:sku AND (review_status = 'approved' OR review_status = 'pending')",array(':sku'=>$variant['sku']));
							// if($variantAddSkuInfo) break;


							$color = $size = '';
							if($listingSubSKU){
				        		//获取color
				        		if(isset($listingSubSKU['skuList'][$variant['sku']]['attribute']['22']['attribute_value_name'])){
				        			$color = trim($listingSubSKU['skuList'][$variant['sku']]['attribute']['22']['attribute_value_name']);
				        			//判断颜色是否是中文
						        	if(preg_match("/[\x7f-\xff]/",$color)){
						        		$color = '';
						        	}						        	
				        		}

				        		//获取size
				        		if(isset($listingSubSKU['skuList'][$variant['sku']]['attribute']['23']['attribute_value_name'])){
				        			$size = trim($listingSubSKU['skuList'][$variant['sku']]['attribute']['23']['attribute_value_name']);
				        			//判断尺寸是否是中文
						        	if(preg_match("/[\x7f-\xff]/",$size)){
						        		$size = '';
						        	}
				        		}
				        	}

							$salePrice = JoomProductAdd::getSalePrice($variant['sku'], $accountID, $shipCode);
							$variantData = array(
									'sku'			=>	$variant['sku'],
									'inventory'		=>	1000,
									'price'			=>	$salePrice['salePrice'],
									'shipping'		=>	0,
									'market_price'	=>	round($salePrice['salePrice']*1.8, 2),
									'color'			=>	$color,
									'size'			=>	$size,
									'main_image'	=>	''
							);
							if(empty($variant['all_image'])){
								//@todo 获取子sku图片
								//获取子SKU一张图
								$images = Product::model()->getImgList($variant['sku'], 'ft');
								if($images){
									$imgname = array_shift($images);
									$basefilename = basename($imgname);
									if(strtolower($basefilename) == $variant['sku'].".jpg" && count($images)>1){
										$imgname = array_shift($images);
									}
									$remoteImgUrl = (string)UebModel::model("JoomProductAdd")->getRemoteImgPathByName($imgname, $accountID, $variant['sku']);
			
									$variantData['main_image'] = $remoteImgUrl;
								}
							}
			
							$variants[] = $variantData;
						}
					}

					if(!$variantData){
						continue;
					}

					$data['variants'] = $variants;
					$addData = array();
					
					$addData[$accountID] = $data;
						
					if($addData){
						$res = $joomProductAddModel->saveJoomAddData($addData, null, JoomProductAdd::ADD_TYPE_COPY);
						if($bug){
							echo $joomProductAddModel->getErrorMsg();
							echo "<br/>";
							var_dump($res);
							echo "<br/>";
						}
					}
					if($bug){
						echo "<br/>====res===<br/>";
						var_dump($res);
					}
					
				}
			}
		}catch (Exception $e){
			echo "<br/>=====message=====<br/>";
			echo $e->getMessage();
		}
		echo "<br>";
		echo "done!!";
	}


	/**
	 * @desc 从ebay产品列表导入到joom待刊登列表
	 * @link /joom/joomproductadd/importebaylisting/account_id/7/limit/2000000/bug/1
	 */
	public function actionImportebaylisting(){
		set_time_limit(2*3600);
		ini_set("display_errors", true);
		error_reporting(E_ALL);
		ini_set('memory_limit','512M');
		$type = Yii::app()->request->getParam("type");
		$limit = Yii::app()->request->getParam("limit");
		$accountID = Yii::app()->request->getParam("account_id");
		$sku = Yii::app()->request->getParam("sku");
		$bug = Yii::app()->request->getParam("bug");
		if(empty($accountID)){
			exit("指定账号ID:1或者2等");
		}
		$joomProductAddModel         = new JoomProductAdd();
		$joomProductVariantsAddModel = new JoomProductVariantsAdd();
		$warehouseSkuMapModel        = new WarehouseSkuMap();

	
		$sku = "75441,76429,21695,4019,98760.02,77017.01,68118.01,85273,4122A,77936";
		// $where = "l.location = 'ShenZhen' AND l.item_status = 1";
		$where = "l.id > 0";
		if($sku){
			$skuArr = explode(",", $sku);
			$where .= " and l.sku in(".MHelper::simplode($skuArr).") ";
		}

		//连接速卖通产品表sql
		$sql = "select l.id,l.sku from market_ebay.ueb_ebay_product l 
				left join market_joom.ueb_joom_listing j on j.sku=l.sku 
				left join market_joom.ueb_joom_product_add ja on ja.parent_sku=l.sku 
				where {$where} 
				and ISNULL(j.id) 
				and ISNULL(ja.id)
				group by l.sku";
		
		if($limit){
			$sql .= " limit {$limit} ";
		}
		$sql1 = "select l.* from market_ebay.ueb_ebay_product l where l.id=";
		$sql2 = "select * from market_ebay.ueb_ebay_product_variation where listing_id=";

		$listing = $joomProductAddModel->getDbConnection()->createCommand($sql)->queryAll();
		if($bug){
			echo "sql:", $sql, "<br/>";
		}
		
		try{
			if($listing){
				foreach ($listing as $idd){
					//过滤子sku
					/* if(strpos($idd['sku'], ".")){
						continue;
					} */
					$sqlm = $sql1.$idd['id'];
					$sql3 = $sql2.$idd['id'];
					if($bug){
						echo "sqlm:".$sqlm, "<br/>";
						echo "sql3:".$sql3, "<br/>";
					}
					$list = $joomProductAddModel->getDbConnection()->createCommand($sqlm)->queryRow();
					if($bug){
						echo "<br/>=====list====<br/>";
						var_dump($list);
						echo "<br/>";
					}
					if(empty($list)){
						continue;
					}
					
					//只要刊登过就不需要在刊登了
					//判断是否在刊登列表里面了
					// $parentAddSkuInfo = UebModel::model('JoomProductAdd')->getProductAddInfo("parent_sku=:parent_sku",array(':parent_sku'=>$list['sku']));
					// if($bug){
					// 	echo "parentAddSkuInfo1 : ". ($parentAddSkuInfo ? "跳过" : "未跳过");
					// 	echo "<br/>";
					// }
					// if($parentAddSkuInfo) continue;
					//判断是否在产品管理里面了
					$parentAddSkuInfo = UebModel::model('JoomProduct')->find("sku=:sku AND (review_status = 'approved' OR review_status = 'pending')",array(':sku'=>$list['sku']));
					if($bug){
						echo "parentAddSkuInfo2 : ". ($parentAddSkuInfo ? "跳过" : "未跳过");
						echo "<br/>";
					}
					if($parentAddSkuInfo) continue;

					//取出sku属性
					$skuInfos = Product::model()->getProductBySku($list['sku']);
					if(!$skuInfos){
						continue;
					}

					$skuInfoMuli = Product::model()->getProductInfoBySku($list['sku']);
					//去除描述中的html标签
					if(!empty($skuInfoMuli['description'])){
						foreach ($skuInfoMuli['description'] as $key=>$val){
							$val = preg_replace_callback("/(?<TT><\/?(?<TAG>\w+).*?>)/im", array($this, 'preg_replace_call_func'), $val);
							$val = preg_replace("/(\n){2,}|\r\n/ie", "\n", $val);
							$val = strip_tags($val);
							$skuInfoMuli['description'][$key] = $val;
						}
					}
					//去除included里面html标签
					if(!empty($skuInfoMuli['included'])){
						foreach ($skuInfoMuli['included'] as $key=>$val){
							$val = preg_replace_callback("/(?<TT><\/?(?<TAG>\w+).*?>)/im", array($this, 'preg_replace_call_func'), $val);
							$val = preg_replace("/(\n){2,}|\r\n/ie", "\n", $val);
							$val = strip_tags($val);
							$skuInfoMuli['included'][$key] = $val;
						}
					}

					$description = '';
					if($skuInfoMuli['description']['english']){
						$description = "Description:\n".$skuInfoMuli['description']['english'];
					}
					if($skuInfoMuli['included']['english']){
						$description .= "\nIncluded:\n".$skuInfoMuli['included']['english'];
					}

					//获取物流方式
					$attributes = Product::model()->getAttributeBySku($list['sku'], 'product_features');//属性
                    if($attributes){
                            //特殊属性的按黑龙江俄速通亚欧小包
                            //$shipCode = Logistics::CODE_CM_SF;
                            $shipCode = Logistics::CODE_CM_YO_EST;
                    }else{
                            //普通的是易时达俄罗斯专线
                            $shipCode = Logistics::CODE_CM_HUNGARY;
                    }

					$skuWhereArr = array($list['sku']);
					//判断sku是否在售状态
					// if($skuInfos['product_status'] != 4){
					// 	continue;
					// }

					//如果为多属性取出子sku
					if($skuInfos['product_is_multi'] == 2){
						//取出子sku
						$attributeSku = ProductSelectAttribute::model()->getChildSKUListByProductID($skuInfos['id']);
						if(!$attributeSku){
							continue;
						}

						$skuWhereArr = $attributeSku;
					}

					//库存量大于等于10
		            $conditions = 't.available_qty >= :available_qty 
		                            AND t.warehouse_id = :warehouse_id 
		                            AND p.product_is_multi IN(0,1) 
		                            AND t.sku IN ('.MHelper::simplode($skuWhereArr).')';
		            $param = array(':available_qty'=>1, ':warehouse_id'=>WarehouseSkuMap::WARE_HOUSE_GM);
		            $skuList = $warehouseSkuMapModel->getSkuListLeftJoinProductByCondition($conditions, $param, '', 't.sku');
		            if(!$skuList){
		                continue;            
		            }
					
					$variantListing = $joomProductAddModel->getDbConnection()->createCommand($sql3)->queryAll();
					if($bug){
						echo "<br>======variantListing======<br/>";
						print_r($variantListing);
						echo "<br/>";
					}

					//获取产品图片
					$imageType = array('zt', 'ft');
					$config = ConfigFactory::getConfig('serverKeys');
					$skuImage = array();
					foreach($imageType as $type){
						$images = Product::model()->getImgList($list['sku'],$type);
						foreach($images as $k=>$img){
							if($k == $list['sku']) continue;
							$skuImage[$type][] = $config['oms']['host'].$img;
						}
					}

					$mainImg = $extraImg = '';
					if($skuImage){
						$mainImg = !empty($skuImage['zt'][0]) ? $skuImage['zt'][0] : '';
						if(!empty($skuImage['ft'])){
							$extraImg = $skuImage['ft'];
							if(!$mainImg){
								$mainImg = $extraImg[0];
								unset($extraImg[0]);
							}
							$extraImg = implode("|",  $extraImg);
						}
					}

					$subject = trim($list['title']);
					$subject = str_replace('"', '', $subject);

					//更新tags
		        	$tags = '';
		        	$nameArr = explode(' ', $subject);
		        	if($nameArr){
			    		foreach ($nameArr as $k => $v) {
			    			if($k >= 12) break;
			    			$tags .= $v.',';
			    		}
			    	}

			    	//主图为空不添加数据
			    	if(!$mainImg){
			    		continue;
			    	}

			    	//判断多属性值  ebay的单品值为0--joom的值为0   ebay的多属性值为1--joom的值为2
			    	if($list['is_multiple'] == 1){
			    		$list['is_multiple'] = 2;
			    	}

					$data = array(
							'parent_sku'		=>	$list['sku'],
							'subject'			=>	$subject,
							'detail'			=>	$description,
							'tags'				=>	rtrim($tags,','),
							'brand'				=>	'',
							'main_image'		=>	$mainImg,
							'extra_images'		=>	$extraImg,
							'product_is_multi'	=>	$list['is_multiple'],
							'remote_main_img'	=>	'',
							'remote_extra_img'	=>	'',
							'variants'			=>	array()
					);

					//获取颜色和尺寸
					$listingSubSKU = JoomProductAdd::getSubProductByMainProductId($skuInfos['id'], $skuInfos['product_is_multi']);

					$variants = array();
					$variantData = array();
					if($variantListing){
						foreach ($variantListing as $variant){

							//判断是否在刊登列表里面了
							// $variantAddSkuInfo = UebModel::model('JoomProductAdd')->getProductAddInfo("parent_sku=:parent_sku",array(':parent_sku'=>$variant['sku']));
							// if($variantAddSkuInfo) break;
							//判断是否在产品管理里面了
							$variantAddSkuInfo = UebModel::model('JoomVariants')->find("sku=:sku",array(':sku'=>$variant['sku']));
							if($variantAddSkuInfo) break;


							$color = $size = '';
							//查询Ebay在线广告多属性子表，判断是否有颜色或者尺寸
							$variationSpecifics = json_decode($variant['variation_specifics']);
							if($variationSpecifics){
								$color = isset($variationSpecifics->Color)?$variationSpecifics->Color:'';
								$size  = isset($variationSpecifics->size)?$variationSpecifics->size:'';
							}

							if($listingSubSKU && !$color && !$size){
				        		//获取color
				        		if(isset($listingSubSKU['skuList'][$variant['sku']]['attribute']['22']['attribute_value_name'])){
				        			$color = trim($listingSubSKU['skuList'][$variant['sku']]['attribute']['22']['attribute_value_name']);
				        			//判断颜色是否是中文
						        	if(preg_match("/[\x7f-\xff]/",$color)){
						        		$color = '';
						        	}						        	
				        		}

				        		//获取size
				        		if(isset($listingSubSKU['skuList'][$variant['sku']]['attribute']['23']['attribute_value_name'])){
				        			$size = trim($listingSubSKU['skuList'][$variant['sku']]['attribute']['23']['attribute_value_name']);
				        			//判断尺寸是否是中文
						        	if(preg_match("/[\x7f-\xff]/",$size)){
						        		$size = '';
						        	}
				        		}
				        	}

							$salePrice = JoomProductAdd::getSalePrice($variant['sku'], $accountID, $shipCode);
							$variantData = array(
									'sku'			=>	$variant['sku'],
									'inventory'		=>	1000,
									'price'			=>	$salePrice['salePrice'],
									'shipping'		=>	0,
									'market_price'	=>	round($salePrice['salePrice']*1.8, 2),
									'color'			=>	$color,
									'size'			=>	$size,
									'main_image'	=>	''
							);
							if(empty($variant['all_image'])){
								//@todo 获取子sku图片
								//获取子SKU一张图
								$images = Product::model()->getImgList($variant['sku'], 'ft');
								if($images){
									$imgname = array_shift($images);
									$basefilename = basename($imgname);
									if(strtolower($basefilename) == $variant['sku'].".jpg" && count($images)>1){
										$imgname = array_shift($images);
									}
									$remoteImgUrl = (string)UebModel::model("JoomProductAdd")->getRemoteImgPathByName($imgname, $accountID, $variant['sku']);
			
									$variantData['main_image'] = $remoteImgUrl;
								}
							}
			
							$variants[] = $variantData;
						}
					}

					if(!$variantData){
						continue;
					}

					$data['variants'] = $variants;
					$addData = array();
					
					$addData[$accountID] = $data;
						
					if($addData){
						$res = $joomProductAddModel->saveJoomAddData($addData, null, JoomProductAdd::ADD_TYPE_COPY);
						if($bug){
							echo $joomProductAddModel->getErrorMsg();
							echo "<br/>";
							var_dump($res);
							echo "<br/>";
						}
					}
					if($bug){
						echo "<br/>====res===<br/>";
						var_dump($res);
					}
					
				}
			}
		}catch (Exception $e){
			echo "<br/>=====message=====<br/>";
			echo $e->getMessage();
		}
		echo "<br>";
		echo "done!!";
	}


	/**
	 * @desc 从lazada产品列表导入到joom待刊登列表
	 * @link /joom/joomproductadd/importlazadalisting/account_id/7/limit/2000000/bug/1
	 */
	public function actionImportlazadalisting(){
		set_time_limit(2*3600);
		ini_set("display_errors", true);
		error_reporting(E_ALL);
		ini_set('memory_limit','512M');
		$type = Yii::app()->request->getParam("type");
		$limit = Yii::app()->request->getParam("limit");
		$accountID = Yii::app()->request->getParam("account_id");
		$sku = Yii::app()->request->getParam("sku");
		$bug = Yii::app()->request->getParam("bug");
		if(empty($accountID)){
			exit("指定账号ID:1或者2等");
		}
		$joomProductAddModel         = new JoomProductAdd();
		$joomProductVariantsAddModel = new JoomProductVariantsAdd();
		$warehouseSkuMapModel        = new WarehouseSkuMap();

	
		$skuArr = array('79738.04','79738.05','79760.02','92607.03','93357.02','94521.07','57225.02','57589.02','58654.03','63370.01','63702.02','71604.04','71841.02','72358.04','72362.01','73066.03','73250.02','73563.02','75619.01','75619.02','75619.03','75627.02','75669.02','77204.04','77311.03','78182.04','78345.01','79552.02','79577.03','79609.01','79738.06','80194.02','80325.01','80777.02','81115.02','81570.02','82701.08','84389.02','85658.02','85992.01','100430','16249','25678','36910','53170','53174','54136','71089','7143','74838','87508','0405','107594','57960','71515','74015','77293','77820','81829','83069','83412','81774');
		$where = "l.status < 4";
		// if($sku){
		// 	$skuArr = explode(",", $sku);
			$where .= " and l.sku in(".MHelper::simplode($skuArr).") ";
		// }

		//连接速卖通产品表sql
		$sql = "select l.id,l.sku from market_lazada.ueb_lazada_product l 
				left join market_joom.ueb_joom_listing j on j.sku=l.sku 
				left join market_joom.ueb_joom_product_add ja on ja.parent_sku=l.sku 
				where {$where} 
				and ISNULL(j.id) 
				and ISNULL(ja.id)
				group by l.sku";
		
		if($limit){
			$sql .= " limit {$limit} ";
		}
		$sql1 = "select l.* from market_lazada.ueb_lazada_product l where l.id=";

		$listing = $joomProductAddModel->getDbConnection()->createCommand($sql)->queryAll();
		if($bug){
			echo "sql:", $sql, "<br/>";
		}
		
		try{
			if($listing){
				foreach ($listing as $idd){
					//过滤子sku
					/* if(strpos($idd['sku'], ".")){
						continue;
					} */
					$sqlm = $sql1.$idd['id'];
					if($bug){
						echo "sqlm:".$sqlm, "<br/>";
					}
					$list = $joomProductAddModel->getDbConnection()->createCommand($sqlm)->queryRow();
					if($bug){
						echo "<br/>=====list====<br/>";
						var_dump($list);
						echo "<br/>";
					}
					if(empty($list)){
						continue;
					}
					
					//只要刊登过就不需要在刊登了
					//判断是否在刊登列表里面了
					// $parentAddSkuInfo = UebModel::model('JoomProductAdd')->getProductAddInfo("parent_sku=:parent_sku",array(':parent_sku'=>$list['sku']));
					// if($bug){
					// 	echo "parentAddSkuInfo1 : ". ($parentAddSkuInfo ? "跳过" : "未跳过");
					// 	echo "<br/>";
					// }
					// if($parentAddSkuInfo) continue;
					//判断是否在产品管理里面了
					$parentAddSkuInfo = UebModel::model('JoomVariants')->find("sku=:sku",array(':sku'=>$list['sku']));
					if($bug){
						echo "parentAddSkuInfo2 : ". ($parentAddSkuInfo ? "跳过" : "未跳过");
						echo "<br/>";
					}
					if($parentAddSkuInfo) continue;

					//取出sku属性
					$skuInfos = Product::model()->getProductBySku($list['sku']);
					if(!$skuInfos){
						continue;
					}

					$skuInfoMuli = Product::model()->getProductInfoBySku($list['sku']);
					//去除描述中的html标签
					if(!empty($skuInfoMuli['description'])){
						foreach ($skuInfoMuli['description'] as $key=>$val){
							$val = preg_replace_callback("/(?<TT><\/?(?<TAG>\w+).*?>)/im", array($this, 'preg_replace_call_func'), $val);
							$val = preg_replace("/(\n){2,}|\r\n/ie", "\n", $val);
							$val = strip_tags($val);
							$skuInfoMuli['description'][$key] = $val;
						}
					}
					//去除included里面html标签
					if(!empty($skuInfoMuli['included'])){
						foreach ($skuInfoMuli['included'] as $key=>$val){
							$val = preg_replace_callback("/(?<TT><\/?(?<TAG>\w+).*?>)/im", array($this, 'preg_replace_call_func'), $val);
							$val = preg_replace("/(\n){2,}|\r\n/ie", "\n", $val);
							$val = strip_tags($val);
							$skuInfoMuli['included'][$key] = $val;
						}
					}

					$description = '';
					if($skuInfoMuli['description']['english']){
						$description = "Description:\n".$skuInfoMuli['description']['english'];
					}
					if($skuInfoMuli['included']['english']){
						$description .= "\nIncluded:\n".$skuInfoMuli['included']['english'];
					}

					//获取物流方式
					$attributes = Product::model()->getAttributeBySku($list['sku'], 'product_features');//属性
                    if($attributes){
                            //特殊属性的按黑龙江俄速通亚欧小包
                            //$shipCode = Logistics::CODE_CM_SF;
                            $shipCode = Logistics::CODE_CM_YO_EST;
                    }else{
                            //普通的是易时达俄罗斯专线
                            $shipCode = Logistics::CODE_CM_HUNGARY;
                    }

					$skuWhereArr = array($list['sku']);
					//判断sku是否在售状态
					// if($skuInfos['product_status'] != 4){
					// 	echo '不是在售状态<br>';
					// 	continue;
					// }

					//如果为多属性取出子sku
					if($skuInfos['product_is_multi'] == 2){
						//取出子sku
						$attributeSku = ProductSelectAttribute::model()->getChildSKUListByProductID($skuInfos['id']);
						if(!$attributeSku){
							continue;
						}

						$skuWhereArr = $attributeSku;
					}

					//库存量大于等于5
		            $conditions = 't.available_qty >= :available_qty 
		                            AND t.warehouse_id = :warehouse_id 
		                            AND p.product_is_multi IN(0,1) 
		                            AND t.sku IN ('.MHelper::simplode($skuWhereArr).')';
		            $param = array(':available_qty'=>1, ':warehouse_id'=>WarehouseSkuMap::WARE_HOUSE_GM);
		            $skuList = $warehouseSkuMapModel->getSkuListLeftJoinProductByCondition($conditions, $param, '', 't.sku');
		            if(!$skuList){
		                continue;            
		            }

					//获取产品图片
					$imageType = array('zt', 'ft');
					$config = ConfigFactory::getConfig('serverKeys');
					$skuImage = array();
					foreach($imageType as $type){
						$images = Product::model()->getImgList($list['sku'],$type);
						foreach($images as $k=>$img){
							if($k == $list['sku']) continue;
							$skuImage[$type][] = $config['oms']['host'].$img;
						}
					}

					$mainImg = $extraImg = '';
					if($skuImage){
						$mainImg = !empty($skuImage['zt'][0]) ? $skuImage['zt'][0] : '';
						if(!empty($skuImage['ft'])){
							$extraImg = $skuImage['ft'];
							if(!$mainImg){
								$mainImg = $extraImg[0];
								unset($extraImg[0]);
							}
							$extraImg = implode("|",  $extraImg);
						}
					}

					$subject = trim($list['name']);
					$subject = str_replace('"', '', $subject);

					//更新tags
		        	$tags = '';
		        	$nameArr = explode(' ', $subject);
		        	if($nameArr){
			    		foreach ($nameArr as $k => $v) {
			    			if($k >= 12) break;
			    			$tags .= $v.',';
			    		}
			    	}

			    	//主图为空不添加数据
			    	if(!$mainImg){
			    		echo '获取不到主图';
			    		continue;
			    	}

					$data = array(
							'parent_sku'		=>	$list['sku'],
							'subject'			=>	$subject,
							'detail'			=>	$description,
							'tags'				=>	rtrim($tags,','),
							'brand'				=>	'',
							'main_image'		=>	$mainImg,
							'extra_images'		=>	$extraImg,
							'product_is_multi'	=>	0,
							'remote_main_img'	=>	'',
							'remote_extra_img'	=>	'',
							'variants'			=>	array()
					);

					//获取颜色和尺寸
					$listingSubSKU = JoomProductAdd::getSubProductByMainProductId($skuInfos['id'], $skuInfos['product_is_multi']);

					$variants = array();
					$variantData = array();

					$color = $size = '';
					if($listingSubSKU && !$color && !$size){
		        		//获取color
		        		if(isset($listingSubSKU['skuList'][$list['sku']]['attribute']['22']['attribute_value_name'])){
		        			$color = trim($listingSubSKU['skuList'][$list['sku']]['attribute']['22']['attribute_value_name']);
		        			//判断颜色是否是中文
				        	if(preg_match("/[\x7f-\xff]/",$color)){
				        		$color = '';
				        	}						        	
		        		}

		        		//获取size
		        		if(isset($listingSubSKU['skuList'][$list['sku']]['attribute']['23']['attribute_value_name'])){
		        			$size = trim($listingSubSKU['skuList'][$list['sku']]['attribute']['23']['attribute_value_name']);
		        			//判断尺寸是否是中文
				        	if(preg_match("/[\x7f-\xff]/",$size)){
				        		$size = '';
				        	}
		        		}
		        	}

					$salePrice = JoomProductAdd::getSalePrice($list['sku'], $accountID, $shipCode);
					$variantData = array(
							'sku'			=>	$list['sku'],
							'inventory'		=>	1000,
							'price'			=>	$salePrice['salePrice'],
							'shipping'		=>	0,
							'market_price'	=>	round($salePrice['salePrice']*1.8, 2),
							'color'			=>	$color,
							'size'			=>	$size,
							'main_image'	=>	''
					);

					//@todo 获取子sku图片
					//获取子SKU一张图
					$images = Product::model()->getImgList($list['sku'], 'ft');
					if($images){
						$imgname = array_shift($images);
						$basefilename = basename($imgname);
						if(strtolower($basefilename) == $list['sku'].".jpg" && count($images)>1){
							$imgname = array_shift($images);
						}
						$remoteImgUrl = (string)UebModel::model("JoomProductAdd")->getRemoteImgPathByName($imgname, $accountID, $list['sku']);

						$variantData['main_image'] = $remoteImgUrl;
					}
	
					$variants[] = $variantData;

					if(!$variantData){
						echo '子属性获取失败<br>';
						continue;
					}

					$data['variants'] = $variants;
					$addData = array();
					
					$addData[$accountID] = $data;
						
					if($addData){
						$res = $joomProductAddModel->saveJoomAddData($addData, null, JoomProductAdd::ADD_TYPE_COPY);
						if($bug){
							echo $joomProductAddModel->getErrorMsg();
							echo "<br/>";
							var_dump($res);
							echo "<br/>";
						}
					}
					if($bug){
						echo "<br/>====res===<br/>";
						var_dump($res);
					}
					
				}
			}
		}catch (Exception $e){
			echo "<br/>=====message=====<br/>";
			echo $e->getMessage();
		}
		echo "<br>";
		echo "done!!";
	}
}