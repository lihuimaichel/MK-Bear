<?php
/**
 * @desc 京东产品刊登Model
 * @author zhangf
 *
 */
class JdProductAdd extends JdModel {
	
	/** @var string 分类名称*/
	public $category_name;
	/** @var string 上传结果*/
	public $upload_result;
	/** @var string 状态说明*/
	public $status_desc;
	
	const EVENT_NAME = 'upload_prorduct';
	
	/** @var 错误信息 **/
	protected $_errorMessage = null;
	
	const PRODUCT_PUBLISH_CURRENCY = 'USD';		//产品刊登货币CODE
	
	const PRODUCT_PUBLISH_STATUS_DEFAULT = 0;	//待上传
	
	const PRODUCT_PUBLISH_STATUS_RUNNING = 1;	//上传中
	
	const PRODUCT_PUBLISH_STATUS_WAITNG_IMAGE = 2;	//商品上传成功,等待图片上传
	
	const PRODUCT_PUBLISH_STATUS_RUNNING_IMAGE = 3;	//图片上传中
	
	const PRODUCT_PUBLISH_STATUS_WAITING_VARIATION = 4;	//图片上传成功，等待多属性SKU上传

	const PRODUCT_PUBLISH_STATUS_RUNNING_VARIATION = 5;	//多属性产品上传中
	
	const PRODUCT_PUBLISH_STATUS_SUCCESS = 6;	//上传成功
	
	const PRODUCT_PUBLISH_STATUS_FAILURE = 7;	//上传失败
	
	const PRODUCT_STATUS_ON_SALE = 1;	//在售中
	
	const PRODUCT_STATUS_ON_WAREHOUSE = 2;	//仓库中
	
	/**@var 刊登类型*/
	const LISTING_TYPE_FIXEDPRICE   = 1;//一口价
	const LISTING_TYPE_VARIATION    = 2;//多属性
	
	/**@var 刊登模式*/
	const LISTING_MODE_EASY = 1;//简单模式
	const LISTING_MODE_ALL = 2;//详细模式
	
	const PUBLISH_MAX_NUMBER_PER_TIMES = 10;	//每次最大上传数
	
	/**
	 * 设置表名
	 * @see CActiveRecord::tableName()
	 */
	public function tableName() {
		return 'ueb_jd_product_add';	
	}
	
	/**
	 * @desc 获取model
	 * @param system $className
	 * @return Ambigous <CActiveRecord, unknown, multitype:>
	 */
	static public function model($className = __CLASS__) {
		return parent::model($className);
	}
	
	public function productAdd($accountID, $sku) {
		$dbTransaction = $this->dbConnection->getCurrentTransaction();
		if ($dbTransaction == null)
			$dbTransaction = $this->dbConnection->beginTransaction();
		try {
			//检查产品是否存在
			$productInfo = Product::model()->getProductInfoBySku($sku);
			if (empty($productInfo)) {
				$this->_errorMessage = Yii::t('jd', 'Product Not Exists', array('sku' => $sku));
				return false;
			}
			//获取产品老系统分类
			$oldCategoryID = Product::model()->getSkuOldCategory($sku);
			//检查产品是否可以刊登
	 		if (!$this->isAllowPublish($sku, $accountID)) return false;
			//1.获取分类
			$categoryID = JdCategory::model()->getRecommendCategoryIds($sku);
			if (empty($categoryID)) {
				$this->_errorMessage = Yii::t('jd', 'Not Match Category', array('sku' => $sku));
				return false;
			}
			//$categoryID = 75061124;
			//查找平台属性
			$platformSaleAttributeSet = array();	//平台销售属性集
			$platformSaleAttributeSet = JdAttribute::model()->getCategoryAttributes($categoryID, array(JdAttribute::ATTRIBUTE_TYPE_SALE_IMAGE, JdAttribute::ATTRIBUTE_TYPE_SALE_TEXT));
			$platformCommonAttributeSet = array();	//平台普通属性集
			$platformCommonAttributeSet = JdAttribute::model()->getCategoryAttributes($categoryID, array(JdAttribute::ATTRIBUTE_TYPE_COMMON));		
			//查找多属性SKU
			$childSkus = array();
			$variationSkus = array();	//多属性SKU
			$omsToPlatformAttributeMap = array();
			//print_r($platformSaleAttributeSet);
			$publisType = self::LISTING_TYPE_FIXEDPRICE;
			if ($productInfo['product_is_multi'] == Product::PRODUCT_MULTIPLE_MAIN) {
				if (empty($platformSaleAttributeSet)) {
					$this->_errorMessage = Yii::t('jd', 'The Category Can Not Publish Multiple Attribute Product', array('sku' => $sku));
					return false;
				}
				//多属性主SKU，获取多属性子sku
				$childSkuInfos = ProductSelectAttribute::model()->getSelectedAttributeSKUListByMainProductId($productInfo['id']);
				if (!empty($childSkuInfos)) {
					$publisType = self::LISTING_TYPE_VARIATION;
					foreach ($childSkuInfos as $childSkuInfo) {
						//检查每个子SKU的属性是否匹配平台的销售属性
						$omsAttributeID = $childSkuInfo['attribute_id'];
						$omsValueID = $childSkuInfo['attribute_value_id'];
						$variationSku = $childSkuInfo['sku'];
						if ($omsAttributeID == 3) continue;		//过滤特殊属性
						if (!array_key_exists($omsAttributeID, $omsToPlatformAttributeMap)) {					
							$platformAttributeID = AttributeMarketOmsMap::model()->getPlatformAttributeIDByOmsAttributeID(Platform::CODE_JD, $omsAttributeID);
							if (empty($platformAttributeID)) {
								$this->_errorMessage = Yii::t('jd', 'SKU Attribute Not Find In Platform Sale Attribute', array('sku' => $sku, 'attribute_id' => $omsAttributeID));
								return false;
							}
							$omsToPlatformAttributeMap[$omsAttributeID] = $platformAttributeID;
						} else {
							$platformAttributeID = $omsToPlatformAttributeMap[$omsAttributeID];
						}
						foreach ($platformSaleAttributeSet as $platformSaleAttribute) {
							if ($platformSaleAttribute['property_id'] == $platformAttributeID) {
								//如果有匹配的平台属性，则检查属性值是否匹配
								$omsValueName = ProductAttributeValue::model()->getValueNameByID($omsValueID);
								foreach ($platformSaleAttribute['value_list'] as $valueList) {
									$platformAttributeValueID = $valueList['property_value_id'];
									$platformAttributeValueName = $valueList['value_data_en'];
									if (strtolower($omsValueName) == strtolower($platformAttributeValueName)) {
										$childSkus[$variationSku][$platformAttributeID] = $platformAttributeValueID;
									}
								}
							}
						}
					}				
				}
			}
			foreach ($childSkus as $variationSku => $attributeList) {
				$count = 0;
				$attributeText = '';
				foreach ($attributeList as $attributeID => $valueID) {
					if ($count > 0)
						$attributeText .= '^';
					$attributeText .= $attributeID . ':' . $valueID;
					$count++;
				}
				$variationSkus[] = array(
					'sku' => $variationSku,
					'attributes' => $attributeText
				);
			}
			//查找sku在OMS的属性
			$omsAttributeSet = array();	//oms属性集
			$skuAttributes = ProductSelectAttribute::model()->getSelectedAttributeSKUListByProductId($productInfo['id']);
			$requiredAttributeVaidation = true;	//必填属性是否选择
			$attributes = '';
			foreach ($platformCommonAttributeSet as $platformCommonAttribute) {
				if ($platformCommonAttribute['require'] == 1){
					//选择默认
					if(empty($platformCommonAttribute['value_list'])){
						$requiredAttributeVaidation = false;
					}else{
						$attributes .= "^".$platformCommonAttribute['property_id'].":".$platformCommonAttribute['value_list'][0]['property_value_id'];
					}
				}
			}
			if($attributes){
				$attributes = substr($attributes, 1);
			}
			if ($requiredAttributeVaidation == false) {
				$this->_errorMessage = Yii::t('jd', 'SKU Required Attribute Empty', array('sku' => $sku, 'name' => $platformCommonAttribute['property_name']));
				return false;
			}
			
			//2.计算价格
			//@TODO 根据价格模板计算出价格
			$tplParam = array(
					'scheme_name'           => '通用方案',
		            'standard_profit_rate'  => 0.25,
					'lowest_profit_rate'    => 0.25,
			);
			$priceCal = new CurrencyCalculate();
			$priceCal->setProfitRate($tplParam['standard_profit_rate']);//设置利润率
			$priceCal->setCurrency(self::PRODUCT_PUBLISH_CURRENCY);//币种
			$priceCal->setPlatform(Platform::CODE_JD);//设置销售平台
			$priceCal->setSku($sku);//设置sku
			$salePrice = $priceCal->getSalePrice();//获取卖价		
			if ($salePrice <= 0.00) {
				$this->_errorMessage = Yii::t('jd', 'Price Error', array('sku' => $sku));
				return false;
			}
			//在原价基础上调20%
			$rate = 0.8;
			//计算多属性SKU价格
			$salePrice = $salePrice / $rate;
			foreach ($variationSkus as $key => $variationSku) {
				$priceCal = new CurrencyCalculate();
				$priceCal->setProfitRate($tplParam['standard_profit_rate']);//设置利润率
				$priceCal->setCurrency(self::PRODUCT_PUBLISH_CURRENCY);//币种
				$priceCal->setPlatform(Platform::CODE_JD);//设置销售平台
				$priceCal->setSku($variationSku['sku']);//设置sku
				$variationSkus[$key]['price'] = $priceCal->getSalePrice();//获取卖价
				//在原价基础上调20%
				$variationSkus[$key]['price'] = $variationSkus[$key]['price'] / $rate;
				if ($variationSkus[$key]['price'] <= 0.00) {
					$this->_errorMessage = Yii::t('jd', 'Price Error');
					return false;
				}			
			}
	
			//3.获取描述模板
/* 	  		$data = array(
				'sku' => $sku,
				'platform_code' => Platform::CODE_JD,
				'account_id' => $accountID,
		    );
			$ruleModel = new ConditionsRulesMatch();
			$ruleModel->setRuleClass(TemplateRulesBase::MATCH_DESCRI_TEMPLATE);
			$descriptionTemplateID = $ruleModel->runMatch($data);
			if (empty($descriptionTemplateID) || !($descriptionTemplate = DescriptionTemplate::model()->getDescriptionTemplateByID($descriptionTemplateID))) {
				$this->_errorMessage = Yii::t('jd', 'Could not Find Description Template');
				return false;
			} */
			$descriptionTemplateInfos = DescriptionTemplate::model()->getDescriptionTemplate(Platform::CODE_JD);
			if (empty($descriptionTemplateInfos)) {
				$this->_errorMessage = Yii::t('jd', 'Could not Find Description Template', array('sku' => $sku));
				return false;
			}
			$descriptionTemplate = $descriptionTemplateInfos[0];
	        $language = JdSite::getLanguageBySite(JdSite::SITE_EN);
			$originTitle = trim($productInfo['title'][$language]);
			$originTitle = str_replace(array('\'', '+', '/', '&', '"', ']', '['), ' ', $originTitle);
	        if (empty($originTitle)) {
	        	$this->_errorMessage = Yii::t('jd', 'Product Title Empty', array('sku' => $sku));
	        	return false;
	        }
			$title = $descriptionTemplate['title_prefix'] . ' ' . $originTitle . ' '.$descriptionTemplate['title_suffix'];
			//除手机配件， Apple配件，  3C电子产品， 手机外的产品标题加上VKTECH品牌名
			if (!in_array($oldCategoryID, array(22, 6, 12, 21))) {
				$title = 'VKTECH ' . $title;
			}
			$description = $descriptionTemplate['template_content'];
	  		$packageInfo = trim($productInfo['included'][$language]);
	  		$packageInfo = strip_tags($packageInfo);
			if (empty($packageInfo)) {
				$this->_errorMessage = Yii::t('jd', 'Product Package Info Empty', array('sku' => $sku));
				return false;
			}
	  		
			//4.获取图片
			$flag = JdProductImageAdd::model()->addProductImageBySku($sku, $accountID);
 			if (!$flag) {
				$this->_errorMessage = Yii::t('jd', 'Save Image Failure');
				return false;
			}
			//添加多属性的SKU图片
			foreach ($variationSkus as $variationSku) {
				$flag = JdProductImageAdd::model()->addProductImageBySku($variationSku['sku'], $accountID);
				if (!$flag)
					throw new Exception(Yii::t('jd', 'Save Image Failure'));
			}
			//5.获取参数模板
			$paramTpl = array(
				'transport_id' => 451,	//运费模板ID
				//'recommend_tpid' =>
				'brand_id' => 678,	//品牌模板参数 
			);
			$transportID = $paramTpl['transport_id'];
			$brandID = $paramTpl['brand_id'];
			
			//6.组装数据保存数据
			$grossWeight = round($productInfo['gross_product_weight'] / 1000, 3);
			$netWeight = round($productInfo['product_weight'] / 1000, 3);
			//避免数据库录入时，净重比毛重大，比较一下
			if ($netWeight > $grossWeight) {
				$tmpWeight = $grossWeight;
				$grossWeight = $netWeight;
				$netWeight = $tmpWeight;
			}
			//产品尺寸，先取包装尺寸，包装尺寸未填取产品尺寸
			$packageWidth = $productInfo['pack_product_width'] > 0.00 ? $productInfo['pack_product_width'] : $productInfo['product_width'];
			$packageWidth = round($packageWidth / 10, 3);
			$packageLength = $productInfo['pack_product_length'] > 0.00 ? $productInfo['pack_product_length'] : $productInfo['product_length'];
			$packageLength = round($packageLength / 10, 3);
			$packageHeight = $productInfo['pack_product_height'] > 0.00 ? $productInfo['pack_product_height'] : $productInfo['product_height'];
			$packageHeight = round($packageHeight / 10, 3);
			if ($grossWeight <= 0) {
				$this->_errorMessage = Yii::t('jd', 'Product Gross Weight Empty', array('sku' => $sku));
				return false;				
			}
			if ($netWeight <= 0) {
				$netWeight = $grossWeight;
				//$this->_errorMessage = Yii::t('jd', 'Product Net Weight Empty', array('sku' => $sku));
				//return false;
			}
			if ($packageWidth <= 0) {
				$packageWidth = 8;
				//$this->_errorMessage = Yii::t('jd', 'Product Package Width Empty', array('sku' => $sku));
				//return false;
			}
			if ($packageLength <= 0) {
				$packageLength = 8;
				//$this->_errorMessage = Yii::t('jd', 'Product Package Length Empty', array('sku' => $sku));
				//return false;
			}
			if ($packageHeight <= 0) {
				$packageHeight = 3;
				//$this->_errorMessage = Yii::t('jd', 'Product Package Height Empty', array('sku' => $sku));
				//return false;
			}
			//推荐产品模板
			$recommendTplID = $this->getRecommendTplID($oldCategoryID);
			$data = array(
				'account_id' => $accountID,
				'sku' => $sku,
				'category_id' => $categoryID,
				'title' => $title,
				'transport_id' => $transportID,
				'price' => $salePrice,
				'currency' => JdSite::getSiteCurrencyList(JdSite::SITE_EN),
				//'custom_tpid' => '',
				'brand_id' => $brandID,
				'weight' => $grossWeight,
				'net_weight' => $netWeight,
				'package_length' => $packageLength,
				'package_width' => $packageWidth,
				'package_height' => $packageHeight,
				'create_user_id' => Yii::app()->user->id,
				'create_time' => date('Y-m-d H:i:s'),
				'publish_mode' => self::LISTING_MODE_EASY,
				'publish_type' => $publisType,
			);
			
			if ($recommendTplID != false)
				$data['recommend_tpid'] = $recommendTplID;
			
			$flag = $this->dbConnection->createCommand()->insert(self::tableName(), $data);
			if (empty($flag))
				throw new Exception(Yii::t('jd', 'Add Product Failure'));
			$addID = $this->dbConnection->getLastInsertID();
			//保存扩展信息
			$extendData = array(
				'add_id' => $addID,
				'keywords' => $originTitle,
				'description' => $description,
				'package_info' => $packageInfo,
				'attributes' => $attributes,
			);
			$flag = $this->dbConnection->createCommand()->insert("ueb_jd_product_add_extend", $extendData);
			if (!$flag)
				throw new Exception(Yii::t('jd', 'Add Product Extend Failure'));
			
			//保存多属性SKU
			foreach ($variationSkus as $variation) {
				$variationData = array(
					'add_id' => $addID,
					'sku' => $variation['sku'],
					'attributes' => $variation['attributes'],
					'price' => $variation['price']
				);
				$flag = $this->dbConnection->createCommand()->insert("ueb_jd_product_add_variation", $variationData);
				if (!$flag)
					throw new Exception(Yii::t('jd', 'Add Product Variation Failure'));
			}
			$dbTransaction->commit();
		} catch (Exception $e) {
			$dbTransaction->rollback();
			$this->_errorMessage = $e->getMessage();
			return false;
		}
		return true;
	}
	
	/**
	 * @desc 获取错误消息
	 */
	public function getErrorMessage() {
		return $this->_errorMessage;
	}
	
	/**
	 * @desc 判断SKU是否可以添加到待刊登列表
	 * @param unknown $sku
	 * @param unknown $accountID
	 */
	public function isAllowPublish($sku, $accountID) {
		//判断是否有待上传的SKU存在
		$check = $this->dbConnection->createCommand()
		->from(self::tableName())
		->select("count(1)")
		->where("status <> " . self::PRODUCT_PUBLISH_STATUS_SUCCESS)
		->andWhere("sku = :sku", array(':sku' => $sku))
		->andWhere("account_id = :account_id", array(':account_id' => $accountID))
		->queryScalar();
		if ($check) {
			$this->_errorMessage = Yii::t('jd', 'Exists Product Records');
			return false;		
		}		
		//判断是否已有在线广告
		$existListing = JdProduct::model()->getOnlineListingBySku($sku, $accountID);
		if( !empty($existListing) ){
			$this->_errorMessage = Yii::t('aliexpress_product', 'Exist Product');
			return false;
		}
		
		//判断产品是否侵权
		$checkInfringe = ProductInfringe::model()->getProductIfInfringe($sku);
		if( $checkInfringe ){
			$this->_errorMessage = Yii::t('aliexpress_product', 'SKU Is Infringe');
			return false;
		}
		return true;
	}
	
	/**
	 * @desc 查找SKU的刊登信息
	 * @param unknown $sku
	 * @param string $accountID
	 */
	public function getAddInfosBySku($sku, $accountID = null) {
		$command = $this->dbConnection->createCommand()
			->from(self::tableName() . " a")
			->select("a.*")
			->leftJoin("ueb_jd_product_add_variation b", "a.id = b.add_id")
			->where("a.sku = :sku", array(':sku' => $sku))
			->orWhere("b.sku = :sku", array(':sku' => $sku));
		if (!is_null($accountID))
			$command->andWhere("account_id = :account_id", array(':account_id' => $accountID));
		return $command->queryAll();
	}
	
	/**
	 * @desc 获取待刊登的记录
	 */
	public function getWaitingUploadRecords() {
		$statusList = array(self::PRODUCT_PUBLISH_STATUS_DEFAULT);
		return $this->dbConnection->createCommand()
			->from(self::tableName())
			->select("id, account_id")
			->where("status in (" . implode(',', $statusList) . ")")
			->queryAll();
	}
	
	public function uploadProduct($addID) {
		$addInfo = $this->findByPk($addID);
		if (empty($addInfo) || $addInfo->status != self::PRODUCT_PUBLISH_STATUS_DEFAULT) return false;
		$addID = $addInfo->id;
		$addExtendInfo = JdProductAddExtend::model()->getInfoByAddID($addID);
		if (empty($addExtendInfo)) return false;
		//获取产品信息
		$skuInfo = Product::model()->getProductInfoBySku($addInfo->sku);
		if (empty($skuInfo)) {
			$this->setFailure(Yii::t('jd', 'Sku Could not Find'));
			//continue;
            return false;
		}
		try {
			$this->setRunning($addID);
			
			//@TODO 获取参数配置里面的参数
			$paramTpl = array(
				'transport_id' => 451,	//运费模板ID
				//'recommend_tpid' =>
				'brand_id' => 1,	//品牌模板参数 
				'ware_status' => self::PRODUCT_STATUS_ON_SALE,
				'stock_num' => 300,
				'delivery_days' => 5,
			);	
			//获取商品图片信息
			$images = ProductImageAdd::model()->getImageBySku($addInfo->sku, $addInfo->account_id, Platform::CODE_JD);
			if (empty($images)) {
				$this->setFailure($addID, Yii::t('jd', 'Product Image Empty'));
				return false;
			}
			$imagesZT = $images[ProductImages::IMAGE_ZT];
			$imagesFT = $images[ProductImages::IMAGE_FT];
			if (empty($imagesZT)) {
				$this->setFailure($addID, Yii::t('jd', 'Product Main Image Empty'));
				return false;
			}
			
			//取第一张为主图
			$mainImage = array_shift($imagesZT);
			$serverConfig = ConfigFactory::getConfig('serverKeys');
			$mainImageUrl = $serverConfig['oms']['host']  . ltrim($mainImage['local_path'], '/');
			$imageBytes = file_get_contents($mainImageUrl);
			if (empty($imageBytes)) {
				$this->setFailure($addID, Yii::t('jd', 'Read Main Image Failure'));
				return false;
			}
			$imageBytes = base64_encode($imageBytes);
			//上传产品描述图片
			$imageList = array();
			if (!empty($imagesFT)) {
				foreach ($imagesFT as $image) {
					$remoteImageUrl = JdProductImageAdd::model()->uploadImageToImageServer($image['id']);
					if ($remoteImageUrl) {
						$imageList[] = $remoteImageUrl;
					}
				}
			}
			
			//获取描述
			$description = $skuInfo['description']['english'];
			$include = $skuInfo['included']['english'];
			$content = $addExtendInfo['description'];
			$title = $addInfo->title;		
			$description = DescriptionTemplate::model()->getDescription($content, $description, $title, $include, $imageList);
			
			//1.上传商品信息
			$wareID = null;
			$request = new ProductAddRequest();
			$request->setCategoryId($addInfo->category_id);
			$request->setWareStatus($paramTpl['ware_status']);
			$request->setTitle($addInfo->title);
			$request->setRfId($addInfo->sku);
			$request->setItemNum($addInfo->sku);
			$request->setTransportId($addInfo->transport_id);
			$request->setSupplyPrice($addInfo->price * 100);
			$request->setAmountCount($paramTpl['stock_num']);
			$request->setDeliveryDays($paramTpl['delivery_days']);
			$request->setKeywords($addExtendInfo['keywords']);
			$request->setPackInfo($addExtendInfo['package_info']);
			$request->setNetWeight($addInfo->net_weight);
			$request->setWeight($addInfo->weight);
			$request->setPackLong($addInfo->package_length);
			$request->setPackWide($addInfo->package_width);
			$request->setPackHeight($addInfo->package_height);
			$request->setImageByte($imageBytes);
			$request->setDescription($description);
			if (!empty($addInfo->recommend_tpid))
				$request->setRecommendTpid($addInfo->recommend_tpid);
			if (!empty($addInfo->custom_tpid))
				$request->setCustomTpid($addInfo->custom_tpid);
			if (!empty($addInfo->brand_id))
				$request->setBrandId($addInfo->brand_id);		
			if (!empty($addExtendInfo['attributes']))
				$request->setAttributes($addExtendInfo['attributes']);
			$response = $request->setAccount($addInfo->account_id)->setRequest()->sendRequest()->getResponse();
			if (!$request->getIfSuccess()) {
				$this->setFailure($addID, $request->getErrorMsg());
				return false;
			} else {
				$wareID = $response->jingdong_ept_warecenter_ware_add_responce->addnewware_result->wareId;
				$this->dbConnection->createCommand()->update(self::tableName(), array('status' => self::PRODUCT_PUBLISH_STATUS_WAITNG_IMAGE, 'ware_id' => $wareID), 'id=' . (int)$addID);
			}
			//2.添加商品明细图片
			if (!empty($wareID) && !empty($imagesZT)) {
				$this->setStatus($addID, self::PRODUCT_PUBLISH_STATUS_RUNNING_IMAGE);
				foreach ($imagesZT as $key => $images) {
					$flag = $this->addProductImage($addInfo->account_id, $wareID, $images['local_path']);
					if (!$flag) {
						$this->setFailure($addID, $this->getErrorMessage());
						return false;
					}
				}
				$this->setStatus($addID, self::PRODUCT_PUBLISH_STATUS_WAITING_VARIATION);
			}
			
			if ($addInfo->publish_type == self::LISTING_TYPE_VARIATION) {
				//3.添加多属性SKU
				$variationSkuInfos = JdProductAddVariation::model()->getVariationSkusByAddID($addID);
				if (!empty($variationSkuInfos)) {
					$this->setStatus($addID, self::PRODUCT_PUBLISH_STATUS_RUNNING_VARIATION);
					foreach ($variationSkuInfos as $variationSkuInfo) {
						//循环添加多属性SKU
						$variationSkuInfo['stock_num'] = $paramTpl['stock_num'];
						$skuID = $this->addVariationSku($addInfo->account_id, $wareID, $variationSkuInfo);
						if (!$skuID) {
							$this->setFailure($addID, $this->getErrorMessage());
							return false;
						}
						//获取多属性SKU图片信息
						$images = ProductImageAdd::model()->getImageBySku($variationSkuInfo['sku'], $addInfo->account_id, Platform::CODE_JD);
						if (empty($images)) {
							$this->setFailure($addID, Yii::t('jd', 'Variation Sku Image Empty'));
							return false;
						}
						$imagesZT = $images[ProductImages::IMAGE_ZT];
						//上传多属性SKU图片
						foreach ($imagesZT as $image) {
							$flag = $this->addVariationSkuImage($addInfo->account_id, $wareID, $skuID, $image['local_path']);
							if (!$flag) {
								$this->setFailure($addID, $request->getErrorMsg());
								return false;
							}					
						}
					}
				}
			}
			$this->setStatus($addID, self::PRODUCT_PUBLISH_STATUS_SUCCESS);
			return $wareID;
		} catch (Exception $e) {
			$this->setFailure($addID, Yii::t('jd', 'Upload Failure, ' . $e->getMessage()));
			return false;
		}
	}
	
	/**
	 * @desc 设置记录运行中
	 * @param unknown $addID
	 * @return Ambigous <number, boolean>
	 */
	public function setRunning($addID) {
		return $this->dbConnection->createCommand()->update(self::tableName(), array('upload_time' => date('Y-m-d H:i:s'),
			'upload_user_id' => Yii::app()->user->id,
			'status' => self::PRODUCT_PUBLISH_STATUS_RUNNING,
		), "id = " . (int)$addID);
	}
	
	/**
	 * @desc 设置记录运行失败
	 * @param unknown $addID
	 * @param unknown $message
	 * @return Ambigous <number, boolean>
	 */
	public function setFailure($addID, $message) {
		return $this->dbConnection->createCommand()->update(self::tableName(), array('status' => self::PRODUCT_PUBLISH_STATUS_FAILURE,
				'upload_message' => $message,
		), "id = " . (int)$addID);		
	}
	
	/**
	 * @desc 设置上传状态
	 * @param unknown $addID
	 * @param unknown $status
	 * @return Ambigous <number, boolean>
	 */
	public function setStatus($addID, $status) {
		return $this->dbConnection->createCommand()->update(self::tableName(), array('status' => (int)$status), "id = " . (int)$addID);		
	}
	
	/**
	 * @desc 添加商品细节图
	 * @param unknown $accountID
	 * @param unknown $wareID
	 * @param unknown $imagePath
	 * @param string $index
	 * @return boolean
	 */
	public function addProductImage($accountID, $wareID, $imagePath, $index = null) {
		$serverConfig = ConfigFactory::getConfig('serverKeys');
		$imageUrl = $serverConfig['oms']['host']  . ltrim($imagePath, '/');
		$imageBytes = file_get_contents($imageUrl);
		if (empty($imageBytes)) {
			$this->_errorMessage = Yii::t('jd', 'Read File Failure', array('file' => $imagePath));
			return false;
		}
		$imageBytes = base64_encode($imageBytes);
		$request = new ProductImageAddRequest();
		$request->setWareId($wareID);
		$request->setImg($imageBytes);
		if (!is_null($index))
			$request->setSlot($index);
		$response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
		if (!$request->getIfSuccess()) {
			$this->_errorMessage = Yii::t('jd', 'Add Product Image Failure', array('file' => $imagePath)) . $request->getErrorMsg();
			return false;
		}
		return true;
	}
	
	/**
	 * @desc 添加多属性SKU
	 * @param unknown $accountID
	 * @param unknown $wareID
	 * @param unknown $skuInfo
	 * @return boolean|unknown
	 */
	public function addVariationSku($accountID, $wareID, $skuInfo) {
		$sku = $skuInfo['sku'];
		$attributes = $skuInfo['attributes'];
		$price = $skuInfo['price'] * 100;
		$variationAddID = $skuInfo['id'];
		$stockNum = $skuInfo['stock_num'];
		$request = new SkuAddRequest();
		$request->setRfId($sku);
		$request->setWareId($wareID);
		$request->setAttributes($attributes);
		$request->setSupplyPrice($price);
		$request->setAmountCount($stockNum);
		$response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
		if (!$request->getIfSuccess()) {
			$this->_errorMessage = Yii::t('jd', 'Add Product Variation Sku Failure', array('sku' => $sku)) . $request->getErrorMsg();
			return false;
		}
		$skuID = $response->jingdong_ept_warecenter_outapi_waresku_add_response->createskuinfo_result->skuId;
		$this->dbConnection->createCommand()->update(JdProductAddVariation::model()->tableName(), array('sku_id' => $skuID), "id = " . (int)$variationAddID);
		return $skuID;
	}
	
	/**
	 * @desc 添加多属性SKU图片
	 * @param unknown $accountID
	 * @param unknown $wareID
	 * @param unknown $skuID
	 * @param unknown $imagePath
	 * @param string $index
	 * @return boolean
	 */
	public function addVariationSkuImage($accountID, $wareID, $skuID, $imagePath, $index = null) {
		$serverConfig = ConfigFactory::getConfig('serverKeys');
		$imageUrl = $serverConfig['oms']['host']  . ltrim($imagePath, '/');
		$imageBytes = file_get_contents($imageUrl);
		if (empty($imageBytes)) {
			$this->_errorMessage = Yii::t('jd', 'Read File Failure', array('file' => $imagePath));
			return false;
		}
		$imageBytes = base64_encode($imageBytes);
		$request = new SkuImageAddRequest();
		$request->setWareId($wareID);
		$request->setImage($imageBytes);
		$request->setAttrValueId($skuID);
		$response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
		if (!$request->getIfSuccess()) {
			$this->_errorMessage = Yii::t('jd', 'Add Variation Sku Image Failure' , array('file' => $imagePath)) . $request->getErrorMsg();
			return false;		
		}
	}
	
	/**
	 * @desc 属性规则
	 * @see CModel::attributeLabels()
	 */
	public function attributeLabels(){
		return array(
				'id'                => Yii::t('system', 'No.'),
				'sku'               => Yii::t('jd', 'Sku'),
				'seller_sku'		   => Yii::t('jd', 'Seller Sku'),
				'publish_type'              => Yii::t('jd', 'Listing Type'),
				'title'      	   => Yii::t('jd', 'Title'),
				'categoryname'      => Yii::t('jd', 'Product Category'),
				'create_time'	   => Yii::t('system', 'Create Time'),
				'upload_time'	   => Yii::t('system', 'Modify Time'),
				'create_user_id'	   => Yii::t('system', 'Create User'),
				'upload_time'	   => Yii::t('system', 'Upload Time'),
				'upload_user_id'	   => Yii::t('system', 'Upload User'),
				'status'	           => Yii::t('jd', 'Status'),
				'price'			   => Yii::t('jd', 'Price'),
				'upload_message'	   => Yii::t('jd', 'Message'),
		);
	}
	
	/**
	 * @desc 关联查询
	 * @see UebModel::search()
	 */
	public function search(){
		$sort = new CSort();
		$sort->attributes = array('defaultOrder'=>'t.id');
		$dataProvider = parent::search(get_class($this), $sort,array(),$this->_setCDbCriteria());
		$data = $this->addition($dataProvider->data);
		$dataProvider->setData($data);
		return $dataProvider;
	}
	
	/**
	 * @desc 创建Criteria对象
	 * @return CDbCriteria
	 */
	protected function _setCDbCriteria(){
		$criteria = new CDbCriteria;
		$criteria->select = 't.id,t.account_id,t.sku,t.currency,t.price,t.publish_type,t.title,t.create_user_id,t.create_time,t.modify_user_id,t.modify_time,t.status,t.upload_user_id,t.upload_time,t.upload_message,t.category_id';
		//$criteria->join   = 'left join ueb_jd_category as c on(t.category_id = c.cat_id)';
		//$criteria->order = 't.id';
		return $criteria;
	}
	
	/**
	 * @desc 附加查询条件
	 * @param unknown $data
	 */
	public function addition($data){
		foreach($data as $k=>$item){
			$data[$k]->status_desc = self::getStatusList($data[$k]->status);
			$sku = $data[$k]->sku;
			$data[$k]->sku = CHtml::link($sku, 'products/product/productview/sku/'.$sku,
					array('style'=>'color:blue;','target'=>'dialog','width'=>'900','height'=>'600','mask'=>true, 'rel' => 'external', 'external' => 'true'));
			$data[$k]->upload_result = $item['upload_message'];
			$data[$k]->category_name = JdCategory::model()->getCategoryNameByCategoryID($item->category_id);
			
		}
		return $data;
	}
	
	/**
	 * @desc 生成条件搜索输入框模糊匹配查询
	 */
	public function filterOptions(){
		$result = array(
				array(
						'name'      => 'sku',
						'type'      => 'text',
						'search'    => '=',
						'alias'     => 't',
				),
				array(
						'name'       => 'publish_type',
						'type'	     => 'dropDownList',
						'search'     => '=',
						'data'		 => self::getListingType(),
						'htmlOptions'=> array(),
						'alias'	     => 't',
				),
				array(
						'name'		 => 'title',
						'type'		 => 'text',
						'search'	 => 'LIKE',
						'alias'	     => 't',
				),
				array(
						'name'          => 'create_time',
						'type'          => 'text',
						'search'        => 'RANGE',
						'htmlOptions'   => array(
								'class'    => 'date',
								'dateFmt'  => 'yyyy-MM-dd HH:mm:ss',
						),
						'alias'			=> 't',
				),
				array(
						'name'          => 'upload_time',
						'type'          => 'text',
						'search'        => 'RANGE',
						'htmlOptions'   => array(
								'class'    => 'date',
								'dateFmt'  => 'yyyy-MM-dd HH:mm:ss',
						),
						'alias'			=> 't',
				),
				array(
						'name'       => 'create_user_id',
						'type'	     => 'dropDownList',
						'search'     => '=',
						'data'		 => MHelper::getUserInfoList(),
						'htmlOptions'=> array(),
						'alias'	     => 't',
				),
				array(
						'name'       => 'upload_user_id',
						'type'	     => 'dropDownList',
						'search'     => '=',
						'data'		 => MHelper::getUserInfoList(),
						'htmlOptions'=> array(),
						'alias'	     => 't',
				),
				array(
						'name'       => 'status',
						'type'	     => 'dropDownList',
						'search'     => '=',
						'data'       => self::getStatusList(),
						'htmlOptions'=> array(),
						'alias'	     => 't',
						'value'      => self::PRODUCT_PUBLISH_STATUS_DEFAULT,
						'notAll'     => true,
				),
				array(
						'name'		 => 'upload_message',
						'type'		 => 'text',
						'search'	 => 'LIKE',
						'alias'	     => 't',
				),
				 
		);
		return $result;
	}
	
	/**
	 * @desc 获取刊登类型
	 * @param int $type
	 */
	public static function getListingType($type = ''){
		if($type != ''){
			switch ($type){
				case self::LISTING_TYPE_FIXEDPRICE:
					return Yii::t('jd', 'FixedFrice');
					break;
				case self::LISTING_TYPE_VARIATION:
					return Yii::t('jd', 'Variation');
					break;
			}
		}
		return array(
			self::LISTING_TYPE_FIXEDPRICE   => Yii::t('jd', 'FixedFrice'), 
				self::LISTING_TYPE_VARIATION    => Yii::t('jd', 'Variation'),
		);
	}
	
	/**
	 * @desc 获取状态列表
	 * @param string $status
	 */
	public static function getStatusList($status = null){
		$statusArr = array(
				self::PRODUCT_PUBLISH_STATUS_DEFAULT     			=> Yii::t('jd', 'PRODUCT_PUBLISH_STATUS_DEFAULT'),
				self::PRODUCT_PUBLISH_STATUS_RUNNING     			=> Yii::t('jd', 'PRODUCT_PUBLISH_STATUS_RUNNING'),
				self::PRODUCT_PUBLISH_STATUS_WAITNG_IMAGE     		=> Yii::t('jd', 'PRODUCT_PUBLISH_STATUS_WAITNG_IMAGE'),
				self::PRODUCT_PUBLISH_STATUS_RUNNING_IMAGE  		=> Yii::t('jd', 'PRODUCT_PUBLISH_STATUS_RUNNING_IMAGE'),
				self::PRODUCT_PUBLISH_STATUS_WAITING_VARIATION     	=> Yii::t('jd', 'PRODUCT_PUBLISH_STATUS_WAITING_VARIATION'),
				self::PRODUCT_PUBLISH_STATUS_RUNNING_VARIATION     	=> Yii::t('jd', 'PRODUCT_PUBLISH_STATUS_RUNNING_VARIATION'),
				self::PRODUCT_PUBLISH_STATUS_SUCCESS     			=> Yii::t('jd', 'PRODUCT_PUBLISH_STATUS_SUCCESS'),
				self::PRODUCT_PUBLISH_STATUS_FAILURE     			=> Yii::t('jd', 'PRODUCT_PUBLISH_STATUS_FAILURE'),
		);
		if($status===null){
			return $statusArr;
		}else{
			return $statusArr[$status];
		}
	}
	
	/**
	 * @desc 删除指定的待刊登列表
	 * @param array $ids
	 */
	public function deleteLazadaById($ids)
	{
		return $this->getDbConnection()->createCommand()->delete(self::model()->tableName(), " id IN ($ids)");
	}
	
	/**
	 * @desc 根据分类获取平台关联产品模板ID
	 * @param unknown $oldCategoryID
	 * @return Ambigous <number>|boolean
	 */
	public function getRecommendTplID($oldCategoryID) {
		$config = array(
			'11' 	=> 756,		//宠物用品
			'7'  	=> 756,		//家居用品
			'15'	=> 756,		//太阳能产品
			'20'	=> 756,		//安防产品
			'6'		=> 874,		//手机配件
			'12'	=> 874,		//APPLE配件
			'4'		=> 874,		//电脑配件
			'14'	=> 874,		//MP3,MP4
			'21'	=> 874,		//3C电子产品
			'22'	=> 874,		//手机
			'17'	=> 874,		//线材
			'13'	=> 875,		//车载用品
			'24'	=> 875,		//汽车配件
			'8'		=> 876,		//玩具礼品
			'19'	=> 877,		//数码相机
			'25'	=> 878,		//皮具包包
			'3'		=> 879,		//手表首饰
			'2'		=> 881,		//户外用品
			'10'	=> 891,		//美容保健
			'8'		=> 919,		//玩具礼品
			'5'		=> 950,		//游戏配件
			'9'		=> 1022,	//办公商业
			'16'	=> 1022,	//仪器仪表
			'18'	=> 1033,	//服装服饰
		);
		if (array_key_exists($oldCategoryID, $config))
			return $config[$oldCategoryID];
		return false;
	}
}