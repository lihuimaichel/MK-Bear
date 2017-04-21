<?php
/**
 * @desc pm刊登
 * @since 2016-12-19
 */
class PriceministerProductAdd extends PriceministerModel{

	public $status_text;
	public $account_name;
	public $visiupload;


	//状态
	const STATUS_PENDING    = 0;//待上传
	const STATUS_OPERATING  = 1;//上传中
	const STATUS_SUCCESS    = 2;//刊登成功
	const STATUS_FAILURE	= 3;//刊登失败


	/** @var 刊登类型 */
	const LISTING_TYPE_FIXEDPRICE = 1;//一口价
	const LISTING_TYPE_VARIATION = 2;//多属性

	const PRODUCT_PUBLISH_CURRENCY = 'EUR';		//刊登货币
	const PRODUCT_PUBLISH_INVENTORY = 10;    //刊登数量


	//移除模版中advert的属性
	public static $REMOVE_ADVERT_ATTR = array("aid","shipping","zipcode","phoneNumber");
	//移除模版中product的属性
	public static $REMOVE_PRODUCT_ATTR = array('pid');

	/**@var 消息提示*/
	private $_errorMessage = null;

    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_pm_product_add';
    }

    
    // ======================== Start:Search =============================
    public function search(){
    	$csort = new CSort();
    	$csort->attributes = array(
    		'defaultOrder'=>'id'
    	);
    	$cdbCriteria = $this->setCdbCriteria();
    	$dataProvider = parent::search($this, $csort, '', $cdbCriteria);
    	$data = $this->additions($dataProvider->getData());
    	$dataProvider->setData($data);
    	return $dataProvider;
    }
    /**
     * @desc  处理额外数据
     * @param unknown $datas
     * @return unknown
     */
    public function additions($datas){
    	if(empty($datas)) return $datas;
		$accountList = PriceministerAccount::getIdNamePairs();
		$type = PriceministerProductType::getListByCondition('id,label');
		$typeArr = array();
		if (!empty($type)) {
			foreach ($type as $row)
				$typeArr[$row['id']] = $row['label'];
		}
    	foreach ($datas as &$data){
			$data['type_id'] = isset($typeArr[$data['type_id']]) ? $typeArr[$data['type_id']] : '-';
    		$data['account_name'] = isset($accountList[$data['account_id']]) ? $accountList[$data['account_id']] : '-';
    		$data['status_text'] = $this->getStatusOptionsText($data['status']);
			$data['listing_type'] = $this->getListingTypeOptionText($data['listing_type']);
			$data['visiupload'] = $data['status'] == self::STATUS_PENDING || $data['status'] == self::STATUS_FAILURE ? 1:0;
			if($data['status'] == self::STATUS_FAILURE){
				$data['status_text'] .= "<br/>".$data['upload_message'];
				$data['status_text'] = "<span style='color:red'>".$data['status_text']."</span>";
			}elseif($data['status'] == self::STATUS_PENDING){
				$data['status_text'] = "<span style='color:green'>".$data['status_text']. ($data['upload_message'] ?  "<br/>Notice：".$data['upload_message'] : '')."</span>";
			}
    	}
    	
    	return $datas;
    }
    
    public function setCdbCriteria(){
    	$cdbCriteria = new CDbCriteria();
    	$cdbCriteria->select = "*";
    	return $cdbCriteria;
    }

    /**
     * @desc 获取状态选项
     * @param unknown $status
     * @return Ambigous <NULL, Ambigous <string, string, unknown>>|multitype:NULL Ambigous <string, string, unknown>
     */
    public function getStatusOptionsText($status = null){
		$statusArr = array(
			self::STATUS_FAILURE    => Yii::t('priceminister', 'Product Add Failure'),
			self::STATUS_SUCCESS    => Yii::t('priceminister', 'Product Add Successful'),
			self::STATUS_OPERATING  => Yii::t('priceminister', 'Product Add Operating'),
			self::STATUS_PENDING    => Yii::t('priceminister', 'Product Add Pending'),
		);
		if($status != null) return $statusArr[$status];
		return $statusArr;
    }

	/**
	 * @desc 获取刊登类型选择项
	 * @param unknown $listingType
	 * @return Ambigous <NULL, Ambigous <string, string, unknown>>|multitype:NULL Ambigous <string, string, unknown>
	 */
	public function getListingTypeOptionText($listingType = null){
		$listingTypeArr = array(
			self::LISTING_TYPE_FIXEDPRICE	=>	Yii::t('priceminister', 'Listing FixedPrice Type'),
			self::LISTING_TYPE_VARIATION	=>	Yii::t('priceminister', 'Listing Variation Type'),
		);
		if($listingType) return $listingTypeArr[$listingType];
		return $listingTypeArr;
	}

    public function filterOptions(){
    	$status = Yii::app()->request->getParam('status');
    	return array(
			array(
				'name'		=>	'sku',
				'type'		=>	'text',
				'search'	=>	'=',
			),
			array(
				'name'		=>	'product_id',
				'type'		=>	'text',
				'search'	=>	'=',
			),
			array(
				'name'		=>	'account_id',
				'type'		=>	'dropDownList',
				'data'		=>	PriceministerAccount::getIdNamePairs(),
				'search'	=>	'='
			),
			array(
				'name'		=>	'status',
				'type'		=>	'dropDownList',
				'data'		=>	$this->getStatusOptionsText(),
				'search'	=>	'=',
				'value'		=>	$status,
			),
			array(
				'name'		=>	'listing_type',
				'type'		=>	'dropDownList',
				'data'		=>	$this->getListingTypeOptionText(),
				'search'	=>	'=',

			),
			array(
				'name' 			=> 'create_time',
				'type' 			=> 'text',
				'search' 		=> 'RANGE',
				'alias'			=>	't',
				'htmlOptions'	=> array(
						'size' => 4,
						'class'=>'date',
						'style'=>'width:80px;'
				),
			),
    	);
    }

    public function attributeLabels(){
    	return array(
			'sku'				=>	'SKU',
			'account_name'		=>	Yii::t('priceminister', 'Account Name'),
			'account_id'		=>	Yii::t('priceminister', 'Account Name'),
			'listing_type'		=>	Yii::t('priceminister', 'Listing Type'),
			//'price'				=>	Yii::t('priceminister', 'Price'),
			'status'			=>	Yii::t('priceminister', 'Status'),
			'status_text'		=>	Yii::t('priceminister', 'Status'),
			'update_user_id'	=>	Yii::t('priceminister', 'Modify User'),
			'update_time'		=>	Yii::t('priceminister', 'Modify Time' ),
			'last_response_time'=>	Yii::t('priceminister', 'Respond Time'),
			'title'				=>	Yii::t('priceminister', 'Title'),
			'category_id'		=>	Yii::t('priceminister', 'Category Name'),
			'create_time'		=>	Yii::t('priceminister', 'Create Time'),
			'product_id'		=>	'product_id',
			'type_id'			=>	'模版类目',
    	);
    }

    // ======================== End:Search ===============================

	// ============ S:设置错误消息提示 =================
	public function getErrorMessage(){
		return $this->_errorMessage;
	}

	public function setErrorMessage($errorMsg){
		$this->_errorMessage = $errorMsg;
	}
	// ============ E:设置错误消息提示 =================

	private function throwE($message,$code=null){
		throw new Exception($message,$code);
	}

	/**
	 * @desc 根据主产品id获取下面所有子产品信息，只能当前产品为多属性时
	 * @param unknown $mainProductId
	 */
	public function getSubProductByMainProductId($mainProductId, $is_multi = Product::PRODUCT_MULTIPLE_MAIN){
		$hasAttributes = self::model('AttributeMarketOmsMap')->getOmsAttrIdsByPlatAttrName(Platform::CODE_JOOM, 0);
		if(!$hasAttributes) return null;
		$platformOwnAttributes = '';
		$platformOwnAttribute = array();
		$sizeAttributeId = 0;
		$colorAttributeId = 0;
		foreach ($hasAttributes as $val){
			$platformOwnAttribute[] = (int)$val['oms_attr_id'];
			if(strtoupper($val['platform_attr_name']) == 'SIZE'){
				$sizeAttributeId = (int)$val['oms_attr_id'];
			}elseif(strtoupper($val['platform_attr_name']) == 'COLOR'){
				$colorAttributeId = (int)$val['oms_attr_id'];
			}
		}
		$platformOwnAttributes = implode(',', $platformOwnAttribute);
		//获取到当前主产品对应下面所有的子产品sku和对应属性,并且与平台属性一一对应的
		$productSelectAttribute = new ProductSelectAttribute();
		//modified 2016-01-03 去除属性限制
		if($is_multi == Product::PRODUCT_MULTIPLE_MAIN)
			$attributeSkuList = $productSelectAttribute->getSelectedAttributeSKUListByMainProductId($mainProductId/* , 'attribute_id in('.$platformOwnAttributes.')' */);
		else
			$attributeSkuList = $productSelectAttribute->getSelectedAttributeSKUListByProductId($mainProductId/* , 'attribute_id in('.$platformOwnAttributes.')' */);
		$subSku = array();
		$skuAttributes = array();
		$attributeValIds = array();
		$skuProductIds = array();
		$attributeList = array();
		//获取对应的属性名称
		$attributeList = self::model('ProductAttribute')->getAttributeListByIds($platformOwnAttribute);
		//判断是否有size
		$hasSizeAttribute = false;
		if($attributeSkuList){
			foreach ($attributeSkuList as $attribute){
				//抽离出子SKU
				$subSku[$attribute['sku']]['attribute'][$attribute['attribute_id']] = array('attribute_id'=>$attribute['attribute_id'], 'attribute_value_id'=>$attribute['attribute_value_id']);
				$subSku[$attribute['sku']]['product_id'] = $attribute['product_id'];
				$subSku[$attribute['sku']]['sku'] = $attribute['sku'];
				$skuProductIds[] = $attribute['product_id'];
				$skuAttributes[$attribute['attribute_id']] = $attribute['attribute_id'];
				//获取对应的属性名称
				//获取对应属性值名称
				$attributeValIds[] = $attribute['attribute_value_id'];
				if(!$hasSizeAttribute && $attribute['attribute_id'] == $sizeAttributeId){
					$hasSizeAttribute = true;
				}
			}
			//在无size属性下抽取一个非颜色的属性,替换掉size属性
			$replaceSizeAttributeId = 0;
			if(!$hasSizeAttribute){
				foreach ($skuAttributes as $val){
					if($val != $colorAttributeId){
						$replaceSizeAttributeId = $val;
						break;
					}
				}
			}
			//获取对应的属性名称
			//$attributeList = self::model('ProductAttribute')->getAttributeListByIds($skuAttributes);
			//获取对应属性值名称
			//获取对应的英文名称
			$attributeValList = self::model('ProductAttributeValue')->getAttributeValueListByIds($attributeValIds);
			$attributeValNamesList = array();
			if($attributeValList){
				foreach ($attributeValList as $val){
					$attributeValNamesList[] = $val['attribute_value_name'];
				}
			}
			//获取对应语言的属性值名称
			$attributeValLangList = self::model('ProductAttributeValueLang')->getAttributeValueLangs($attributeValNamesList);
			//获取子SKU信息
			$productInfoList = self::model('Product')->getProductInfoListByIds($skuProductIds);
			foreach ($productInfoList as $product){
				foreach ($subSku[$product['sku']]['attribute'] as $k=>$attribute){
					foreach ($attributeList as $attr){
						if($attr['id'] == $attribute['attribute_id']){
							$subSku[$product['sku']]['attribute'][$k]['attribute_name'] = $attr['attribute_name'];
							break;
						}
					}
					foreach ($attributeValList as $attrVal){
						if($attrVal['id'] == $attribute['attribute_value_id']){
							$attrivalname = isset($attributeValLangList[$attrVal['attribute_value_name']]) ? $attributeValLangList[$attrVal['attribute_value_name']]:$attrVal['attribute_value_name'];
							if(!$hasSizeAttribute && $attribute['attribute_id'] == $replaceSizeAttributeId){//填补size的空缺
								$subSku[$product['sku']]['attribute'][$sizeAttributeId]['attribute_value_name'] = $attrivalname;
							}else{
								$subSku[$product['sku']]['attribute'][$k]['attribute_value_name'] = $attrivalname;
							}
							break;
						}
					}
				}
				$subSku[$product['sku']]['skuInfo'] = $product;
				unset($product);
			}
		}
		return array('skuList'=>$subSku, 'attributeList'=>$attributeList);
	}
	/**
	 * @desc 添加数据通过批量方式
	 * @param unknown $sku
	 * @param unknown $accountID
	 * @throws Exception
	 * @return boolean
	 */
	public function addProductByBatch($sku, $accountID){
		$dbTransaction = $this->dbConnection->getCurrentTransaction();
		if ($dbTransaction == null)
			$dbTransaction = $this->dbConnection->beginTransaction();
		try{
			//检查产品是否存在
			//$productInfo = Product::model()->getProductInfoBySku($sku);
			$productInfo = Product::model()->getProductBySku($sku);

			if (empty($productInfo)) {
				throw new Exception(Yii::t('priceminister', 'SKU has not Exists'));
			}

			//检查产品是否可以刊登
			if (!$this->isAllowPublish($sku, $accountID)){
				throw new Exception($this->getErrorMessage());
			}

			//判断标题、描述是否为空
			$productDesc = Productdesc::model()->getDescriptionInfoBySkuAndLanguageCode($sku,'en');
			if (trim($productDesc['title']) == '' || trim($productDesc['description']) == '') {
				throw new Exception($sku. Yii::t('priceminister', ' Title or Description is empty, can not be uploaded to EBAY'));
			}



			//刊登类型
			$listingType = $productInfo['product_is_multi'] != Product::PRODUCT_MULTIPLE_MAIN ? self::LISTING_TYPE_FIXEDPRICE : self::LISTING_TYPE_VARIATION;


			$variationSkuOriList = $this->getSubSKUListPairsByMainProductId($productInfo['id']);


			if(empty($variationSkuOriList)){
				$variationSkuList = array();
			}
			$variationSkus = array();
			if($variationSkuList){
				foreach ($variationSkuList as $variation){
					if(!in_array($variation['son_sku'], $variationSkuOriList)) continue;
					//取出子sku属性
					//$variationAttributes[$variation['son_sku']] = EbayProductAddVariationAttribute::model()->getVariationAttributeListByVariationId($variation['id'], $variation['add_id']);
					//$variation['add_id'] = 0;
					//$variation['variation_price'] = 0.0;
					//unset($variation['id']);
					$variationSkus[$variation['son_sku']] = $variation;
				}
			}
			//计算价格
			$tplParam = array(
				'scheme_name'           => '通用方案',
				'standard_profit_rate'  => 0.25,
				'lowest_profit_rate'    => 0.25,
			);
			$priceCal = new CurrencyCalculate();
			$priceCal->setProfitRate($tplParam['standard_profit_rate']);//设置利润率
			$priceCal->setCurrency("USD");//币种  暂时写死
			$priceCal->setPlatform(Platform::CODE_PM);//设置销售平台
			$priceCal->setSku($sku);//设置sku
			$salePrice = $priceCal->getSalePrice();//获取卖价
			if ($salePrice <= 0.00) {
				throw new Exception($sku. Yii::t('priceminister', ' Price Error'));
			}

			//计算多属性SKU价格
			foreach ($variationSkus as $key => $variationSku) {
				$priceCal = new CurrencyCalculate();
				$priceCal->setProfitRate($tplParam['standard_profit_rate']);//设置利润率
				$priceCal->setCurrency("USD");//币种  暂时写死
				$priceCal->setPlatform(Platform::CODE_PM);//设置销售平台
				$priceCal->setSku($variationSku['sku']);//设置sku
				$variationSkus[$key]['price'] = $priceCal->getSalePrice();//获取卖价
				if ($variationSkus[$key]['price'] <= 0.00) {
					throw new Exception($sku. Yii::t('priceminister', ' Price Error'));
				}
			}


			$data = array(
				'account_id' => $accountID,
				'sku' => $sku,
				//'category_id' => $categoryID,
				'title' => $productDesc['title'],
				//'transport_id' => $transportID,
				'price' => $salePrice,
				'currency' => 'USD',//暂时写死，上线查看修改
//				'brand_id' => $brandID,
//				'weight' => $grossWeight,
//				'net_weight' => $netWeight,
//				'package_length' => $packageLength,
//				'package_width' => $packageWidth,
//				'package_height' => $packageHeight,
				'create_user_id' => Yii::app()->user->id,
				'create_time' => date('Y-m-d H:i:s'),
				'listing_type' => $listingType,
				'status' => self::STATUS_PENDING,
			);

			$flag = $this->dbConnection->createCommand()->insert(self::tableName(), $data);
			if (empty($flag))
				throw new Exception(Yii::t('priceminister', 'Add Product Failure'));
			$addID = $this->dbConnection->getLastInsertID();

			//保存多属性SKU
			foreach ($variationSkus as $variation) {
				$variationData = array(
					'add_id' => $addID,
					'son_sku' => $variation['sku'],
					'attributes' => $variation['attributes'],
					'price' => $variation['price']
				);
				$flag = $this->dbConnection->createCommand()->insert("ueb_jd_product_add_variation", $variationData);
				if (!$flag)
					throw new Exception(Yii::t('priceminister', 'Add Product Variation Failure'));
			}
			$dbTransaction->commit();
			//生成文件
			$filePath = $this->makeFile($sku,1);
			$this->getDbConnection()->createCommand()->update(self::tableName(),array('file_path'=>$filePath),'id='.$addID);
		} catch (Exception $e) {
			$dbTransaction->rollback();
			$this->setErrorMessage($e->getMessage());
			return false;
		}
		return true;
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
			->where("status <> " . self::STATUS_SUCCESS)
			->andWhere("sku = :sku", array(':sku' => $sku))
			->andWhere("account_id = :account_id", array(':account_id' => $accountID))
			->queryScalar();
		if ($check) {
			$this->setErrorMessage(Yii::t('priceminister', 'Exists Product Records'));
			return false;
		}
		//判断是否已有在线广告
		/*$existListing = JdProduct::model()->getOnlineListingBySku($sku, $accountID);
		if( !empty($existListing) ){
			$this->_errorMessage(Yii::t('priceminister', 'Exist Product'));
			return false;
		}*/

		//判断产品是否侵权
		$checkInfringe = ProductInfringe::model()->getProductIfInfringe($sku);
		if( $checkInfringe ){
			$this->setErrorMessage(Yii::t('priceminister', 'SKU Is Infringe'));
			return false;
		}
		return true;
	}

	/**
	 * @desc 获取 product_id	=>	sku 列表
	 * @param unknown $mainProductId
	 * @return multitype:unknown
	 */
	public function getSubSKUListPairsByMainProductId($mainProductId){
		$productSelectedAttribute = new ProductSelectAttribute();
		$skuAttributeList = $productSelectedAttribute->getSelectedAttributeSKUListByMainProductId($mainProductId);
		$skus = array();
		foreach ($skuAttributeList as $sku){
			$skus[$sku['product_id']] = $sku['sku'];
		}
		return $skus;
	}

	/**
	 * @desc 批量删除
	 */
	public function batchDel($addIDs){
		if(empty($addIDs)) return false;
		//只删除待上传和上传失败的
		$idList = $this->getDbConnection()->createCommand()->from($this->tableName())->select("id")
			->where(array('in', 'id', $addIDs))->andWhere("status in(".self::STATUS_PENDING.", ".self::STATUS_FAILURE.")")
			->queryAll();
		$newAddIDs = array();
		if($idList){
			foreach ($idList as $ids){
				$newAddIDs[] = $ids['id'];
			}
		}
		if(empty($newAddIDs)) return false;
		try{
			$transacation  = $this->getDbConnection()->beginTransaction();
			$res = $this->getDbConnection()->createCommand()->delete($this->tableName(), "id in(".MHelper::simplode($newAddIDs).") and status in(".self::STATUS_PENDING.", ".self::STATUS_FAILURE.")");
			//删除其他的
			PriceministerProductAddVariation::model()->getDbConnection()->createCommand()->delete(PriceministerProductAddVariation::model()->tableName(), "add_id in(".MHelper::simplode($newAddIDs).")");
			PriceministerProductAddExtend::model()->getDbConnection()->createCommand()->delete(PriceministerProductAddExtend::model()->tableName(), "add_id in(".MHelper::simplode($newAddIDs).")");
			$transacation->commit();
			return true;
		}catch (Exception $e){
			$transacation->rollback();
			return false;
		}catch (CDbException $e){
			$transacation->rollback();
			return false;
		}
	}

	//生成xml文件
	//图片格式必须以|相隔
	public function createXmlFile($addID,$pmData){
		$addInfo = self::model()->findByPk($addID);
		if($addInfo['listing_type'] != PriceministerProductAdd::LISTING_TYPE_FIXEDPRICE){
			return false;
		}
		$typeInfo = PriceministerProductType::model()->getOneByCondition('alias','id='.$addInfo['type_id']);
		if(!$typeInfo){
			return false;
		}

		$xmlGeneration = new XmlGenerator();

		//产品
		if(isset($pmData['pmProduct'])){
			$productData = array();
			$productAttr = array();
			foreach($pmData['pmProduct'] as $key=>$value){
				if(empty($value)){
					continue;
				}
				$productAttr['key'] = $key;
				$productAttr['value'] = '<![CDATA['.$value.']]>';
				$productData[] = $productAttr;
			}
			$productXml = $xmlGeneration->buildXMLFilterMulti($productData, 'attribute', '')->getXml();
		}

		//listing
		if(isset($pmData['pmAdvert'])){
			$advertData = array();
			$advertAttr = array();
			/*foreach($pmData['pmAdvert'] as $key=>$value){
				if(empty($value)){
					continue;
				}
				$advertAttr['key'] = $key;
				$advertAttr['value'] = '<![CDATA['.$value.']]>';
				$advertData[] = $advertAttr;
			}*/

			foreach($pmData['pmAdvert'] as $key=>$value){
				if(empty($value)){
					continue;
				}
				foreach($value as $k=>$v){
					if(empty($v)){
						continue;
					}
					$advertAttr['key'] = $k;
					$advertAttr['value'] = '<![CDATA['.$v.']]>';
					$advertData[] = $advertAttr;
				}
			}
			$xmlGeneration->xml = "";
			$advertXml = $xmlGeneration->buildXMLFilterMulti($advertData, 'attribute', '')->getXml();
		}

		//图片相关
		if(isset($pmData['pmMedia'])) {
			$mediaAttr['key'] = 'image_url';
			$mediaAttr['value'] = '<![CDATA[' . $pmData['pmMedia'] . ']]>';
			$mediaData[] = $mediaAttr;

			$xmlGeneration->xml = "";
			$imageXml = $xmlGeneration->buildXMLFilterMulti($mediaData, 'attribute', '')->getXml();
		}

		/*if(isset($pmData['pmMedia'])){
			$mediaData = array();
			$mediaAttr = array();
			foreach($pmData['pmMedia'] as $key=>$value){
				$imgValue = $value.'|';
			}
			$mediaAttr['key'] = 'image_url';
			$mediaAttr['value'] = '<![CDATA['.$imgValue.']]>';
			$mediaData[] = $mediaAttr;

			$xmlGeneration->xml = "";
			$imageXml = $xmlGeneration->buildXMLFilterMulti($mediaData, 'attribute', '')->getXml();
		}*/

		//运输方式
		if(isset($pmData['pmShipping'])){
			$shippingData = array();
			$shippingAttr = array();
			foreach($pmData['pmShipping'] as $key=>$value){
				$shippingAttr['key'] = $key;
				$shippingAttr['value'] = '<![CDATA['.$value.']]>';
				$shippingData[] = $shippingAttr;
			}
			$xmlGeneration->xml = "";
			$shippingXml = $xmlGeneration->buildXMLFilterMulti($shippingData, 'attribute', '')->getXml();
		}

		//促销相关
		if(isset($pmData['pmCampaigns'])){
			$campaignsData = array();
			$campaignsAttr = array();
			foreach($pmData['pmCampaigns'] as $key=>$value){
				$campaignsAttr['key'] = $key;
				$campaignsAttr['value'] = '<![CDATA['.$value.']]>';
				$campaignsData[] = $campaignsAttr;
			}
			$xmlGeneration->xml = "";
			$campaignsXml = $xmlGeneration->buildXMLFilterMulti($campaignsData, 'attribute', '')->getXml();
		}

		$data['items']['item'] = array(
			'alias' => $typeInfo['alias'],	//类型
			'attributes' => array(
				'product' => $productXml,		//产品
				'advert' => $advertXml,			//listing
				'media' => isset($imageXml)?$imageXml:'',			//图像
				//'campaigns' => array('campaign'=>$campaignsXml),	//促销相关
				//'campaigns' => $campaignsXml,	//促销相关
				//'shipping' => $shippingXml,		//运输方式
			),
		);
		$itemXml = $xmlGeneration->XmlWriter()->buildXMLFilterMulti($data)->pop()->getXml();

		$path = './uploads/pm/';
		if(!file_exists($path) ){
			mkdir($path, 0777, true);
		}
		$file_name = date('YmdHis').'_'.$addInfo['sku'].'.xml';
		file_put_contents($path.$file_name,$itemXml);
		return $path.$file_name;
	}

	//生成xml文件多属性
	public function createVariantXmlFile($addID,$pmData){
		$addInfo = self::model()->findByPk($addID);
		$xmlGeneration = new XmlGenerator();
		if($addInfo['listing_type'] != PriceministerProductAdd::LISTING_TYPE_VARIATION){
			return false;
		}
		$typeInfo = PriceministerProductType::model()->getOneByCondition('alias','id='.$addInfo['type_id']);
		if(!$typeInfo){
			return false;
		}

		/*//运输方式
		if(isset($pmData['pmShipping'])){
			$shippingData = array();
			$shippingAttr = array();
			foreach($pmData['pmShipping'] as $key=>$value){
				$shippingAttr['key'] = $key;
				$shippingAttr['value'] = '<![CDATA['.$value.']]>';
				$shippingData[] = $shippingAttr;
			}
			$xmlGeneration->xml = "";
			$shippingXml = $xmlGeneration->buildXMLFilterMulti($shippingData, 'attribute', '')->getXml();
		}

		//促销相关
		if(isset($pmData['pmCampaigns'])){
			$campaignsData = array();
			$campaignsAttr = array();
			foreach($pmData['pmCampaigns'] as $key=>$value){
				$campaignsAttr['key'] = $key;
				$campaignsAttr['value'] = '<![CDATA['.$value.']]>';
				$campaignsData[] = $campaignsAttr;
			}
			$xmlGeneration->xml = "";
			$campaignsXml = $xmlGeneration->buildXMLFilterMulti($campaignsData, 'attribute', '')->getXml();
		}*/


		//产品
		if(isset($pmData['pmProduct'])){
			$productData = array();
			$productAttr = array();
			foreach($pmData['pmProduct'] as $key=>$value){
				if(empty($value)){
					continue;
				}
				$productAttr['key'] = $key;
				$productAttr['value'] = '<![CDATA['.$value.']]>';
				$productData[] = $productAttr;
			}
			$xmlGeneration->xml = "";
			$productXml = $xmlGeneration->buildXMLFilterMulti($productData, 'attribute', '')->getXml();
		}

		$xmlGeneration->xml = "";
		$productAttr1['key'] = 'submitterreference';
		$productAttr1['value'] = '<![CDATA['.$addInfo['sku'].']]>';
		$productData1[] = $productAttr1;
		$productXml1 = $xmlGeneration->buildXMLFilterMulti($productData1, 'attribute', '')->getXml();

		//listing
		if(isset($pmData['pmAdvert'])){

			$itemXml= '';
			$s = 0;
			foreach($pmData['pmAdvert'] as $key=>$value){
				$advertData = array();
				$advertAttr = array();
				foreach($value as $k=>$v){
					if(empty($v)){
						continue;
					}
					$advertAttr['key'] = $k;
					$advertAttr['value'] = '<![CDATA['.$v.']]>';
					$advertData[] = $advertAttr;

				}
				$xmlGeneration->xml = "";
				$advertXml = $xmlGeneration->buildXMLFilterMulti($advertData, 'attribute', '')->getXml();


				//图片相关
				if(isset($pmData['pmMedia']) && array_key_exists($key, $pmData['pmMedia'])){

					$mediaData = array();
					$mediaAttr = array();

					$mediaAttr['key'] = 'image_url';
					$mediaAttr['value'] = '<![CDATA['.$pmData['pmMedia'][$key].']]>';
					$mediaData[] = $mediaAttr;

					$xmlGeneration->xml = "";
					$imageXml = $xmlGeneration->buildXMLFilterMulti($mediaData, 'attribute', '')->getXml();
				}

				$items['item'] = array(
					'attributes' => array(
						'product' => $s==0 ? $productXml : $productXml1,		//产品
						'advert' => $advertXml,			//listing
						'media' =>  isset($imageXml)?$imageXml:'',			//图像
						//'campaigns' => $campaignsXml,	//促销相关
						//'shipping' => $shippingXml,		//运输方式
					),
				);
				if($s==0){//类型
					$items['item']['alias'] = $typeInfo['alias'];
				}

				$xmlGeneration->xml = "";
				$itemXml .= $xmlGeneration->buildXMLFilterMulti($items)->getXml();

				$s++;
			}
		}
		$data['items'] = $itemXml;
		$itemXml = $xmlGeneration->XmlWriter()->buildXMLFilterMulti($data)->pop()->getXml();

		$path = './uploads/pm/';
		if(!file_exists($path) ){
			mkdir($path, 0777, true);
		}
		$file_name = date('YmdHis').'_'.$addInfo['sku'].'.xml';
		file_put_contents($path.$file_name,$itemXml);
		return $path.$file_name;
	}

	/**
	 * @desc 获取远程图片
	 * @param unknown $imgName
	 * @param unknown $accountID
	 * @param unknown $sku
	 * @return boolean
	 */
	public function getRemoteImgPathByName($imgName, $accountID, $sku = null){
		if(empty($imgName)) return false;
		$imageName = basename($imgName);
		$pos = strrpos($imageName, "?");
		if($pos)
			$imageName = substr($imageName, 0, $pos);
		if(empty($sku)){
			$pos = strrpos($imageName, "-");
			if($pos === false){
				$pos = strrpos($imageName, ".");
			}
			$sku = substr($imageName, 0, $pos);
		}
		$productImageAddModel = new ProductImageAdd;
		$imageNameList = array(
			$imageName
		);
		$platformCode = Platform::CODE_PM;
		$siteId = null;
		$assistantImage = false;
		$moreParams = array(
			'width'=>'800', 'height'=>'800'
		);
		$response = $productImageAddModel->getSkuImageUpload($accountID, $sku, array_values($imageNameList), $platformCode, $siteId, $assistantImage, $moreParams);
		if(isset($_REQUEST['bug'])){
			MHelper::printvar($response, false);
		}
		if (empty($response) || empty($response['result']) || empty($response['result']['imageInfoVOs']) || count($imageNameList) != count($response['result']['imageInfoVOs']) ) {
			$this->setErrorMessage('Remote get Sku images failure');
			//$productImageAddModel->addSkuImageUpload($accountID, $sku, 0, $platformCode, $siteId);//发送图片上传请求
			return false;
		}
		return $response['result']['imageInfoVOs'][0]['remotePath'];
	}
	/**
	 * @desc 上传产品数据
	 * @param unknown $addID
	 * @return boolean
	 */
	public function uploadProductData($addID){
		$userID = (int)Yii::app()->user->id;
		try{
			$pmProductAddVariant = PriceministerProductAddVariation::model();
			$addInfo = $this->findByPk($addID);
			if(empty($addInfo)) $this->throwE("Not Find the Add ID");

			$extendInfo=PriceministerProductAddExtend::model()->getDbConnection()->createCommand()
					->select("id,product_desc,advert_desc")
					->from(PriceministerProductAddExtend::model()->tableName())
					->where("add_id = ".$addID)
					->queryRow();
			if(empty($extendInfo)) $this->throwE("Not Find the Add ID2");


			//上传图片
			/*
			 * 1、已经转换域名的直接取
			 * 2、有本地地址没有转换域名的，转换后直接取并更新数据库
			 * 3、没本地地址的，先取本地地址，在转换后更新数据库
			 */
			if(empty($addInfo['remote_extra_img'])){
				if($addInfo['extra_images']){ //有本地地址

					$remoteExtraImagesArr = explode('|', $addInfo['extra_images']);
					$newRemoteExtraArr = array();
					foreach ($remoteExtraImagesArr as $img){
						$remoteImgUrl = $this->getRemoteImgPathByName($img, $addInfo['account_id']);
						if(!$remoteImgUrl){
							$this->throwE($addInfo['sku']."图片上传失败1");
						}
						$newRemoteExtraArr[] = $remoteImgUrl;
					}
					if(empty($newRemoteExtraArr)){
						$this->throwE("图片为空1");
					}
					if($newRemoteExtraArr){
						$remote_extra_image = implode('|', $newRemoteExtraArr);
						$this->updateProductAddInfoByPk($addInfo['id'], array('remote_extra_img'=>$remote_extra_image));
						$addInfo['remote_extra_img'] = $remote_extra_image;
					}
				}else{//没本地地址

					$images = Product::model()->getImgList($addInfo['sku'],'ft');
					$remoteExtraArr = array();
					$extraArr = array();
					foreach($images as $img){
						$remoteImgUrl = $this->getRemoteImgPathByName($img, $addInfo['account_id']);

						if(empty($remoteImgUrl)){
							$this->throwE($addInfo['sku']."图片上传失败2");
						}
						$extraArr[] = $img;
						$remoteExtraArr[] = $remoteImgUrl;
					}
					if(empty($remoteExtraArr)){
						$this->throwE("图片为空");
					}
					if($remoteExtraArr){
						$extra_image = implode('|', $extraArr);
						$remote_extra_image = implode('|', $remoteExtraArr);
						$this->updateProductAddInfoByPk($addInfo['id'], array('remote_extra_img'=>$remote_extra_image,'extra_images'=>$extra_image));
						$addInfo['remote_extra_img'] = $remote_extra_image;
					}
				}
			}


			//子sku图片
			if($addInfo['listing_type'] == PriceministerProductAdd::LISTING_TYPE_VARIATION){

				$variantList = $pmProductAddVariant->findAll('add_id=:add_id', array(':add_id'=>$addInfo['id']));

				foreach($variantList as $variant){
					if(empty($variant['remote_extra_img'])){
						if($variant['extra_images']){ //有本地地址

							$remoteSonImgUrl = (string)$this->getRemoteImgPathByName($variant['main_image'], $addInfo['account_id']);
							if(!$remoteSonImgUrl){
								$this->throwE($variant['son_sku']."图片上传失败");
							}
							$pmProductAddVariant->updateProductVariantAddInfoByPk($variant['id'], array('remote_main_img'=>$remoteSonImgUrl));

						}else{//没本地地址

							$sonImages = Product::model()->getImgList($variant['son_sku'], 'ft');
							$remoteSonExtraArr = array();
							$extraSonArr = array();
							foreach($sonImages as $sonImg){

								$remoteSonImgUrl = (string)$this->getRemoteImgPathByName($sonImg, $addInfo['account_id'], $variant['son_sku']);
								if(empty($remoteSonImgUrl)){
									$this->throwE($addInfo['sku']."图片上传失败2");
								}
								$extraSonArr[] = $img;
								$remoteSonExtraArr[] = $remoteSonImgUrl;
							}

							if(empty($remoteSonExtraArr)){
								$this->throwE("Variation SKU IMG FAILURE");
							}
							if($remoteSonExtraArr){
								$extra_image = implode('|', $extraSonArr);
								$remote_extra_image = implode('|', $remoteSonExtraArr);
								$pmProductAddVariant->updateProductVariantAddInfoByPk($variant['id'], array('remote_main_img'=>$remote_extra_image,'extra_images'=>$extra_image));
							}
						}
					}
				}
			}

			//生成xml文件到目录
			$xmlData = array(
				'pmProduct'=>json_decode($extendInfo['product_desc'],true),
				'pmAdvert'=>json_decode($extendInfo['advert_desc'],true),
				//'pmMedia'=>$pmMedia,
				//'pmShipping'=>$pmShipping,
				//'pmCampaigns'=>$pmCampaigns,
			);

			//组装xml数据生成文件
			if($addInfo['listing_type'] == PriceministerProductAdd::LISTING_TYPE_VARIATION){
				$variantList = $pmProductAddVariant->findAll('add_id=:add_id', array(':add_id'=>$addInfo['id']));
				$pmMediaArr = array();
				foreach($variantList as $variant){
					$pmMediaArr[$variant['son_sku']] = $variant['remote_extra_img'];
				}
				$xmlData['pmMedia'] = $pmMediaArr;
				$filePath = $this->createVariantXmlFile($addID,$xmlData);//多属性
			}else{
				$xmlData['pmMedia'] = $addInfo['remote_extra_img'];
				$filePath = $this->createXmlFile($addID,$xmlData);
			}

			//调用接口
			$request = new GenericImportFileRequest();
			//本地测试
			/*$filePath="D:/wamp/www/codes".ltrim($filePath,'.');
			$request->setXmlFile($filePath);
			$response = $request->setRequest()->sendRequest()->getResponse();*/
			//正式环境
			$request->setXmlFile($filePath);
			$response = $request->setAccount($addInfo['account_id'])->setRequest()->sendRequest()->getResponse();

			if(isset($response->response->status) && $response->response->status=='OK'){
				$importID = trim($response->response->importid);
				//上传成功，更新状态
				$data = array(
					'status' => self::STATUS_OPERATING,
					'last_response_time' => date("Y-m-d H:i:s"),
					'upload_time' => date("Y-m-d H:i:s"),
					'upload_message' => '',
					'import_id' => $importID,
					'upload_user_id' =>	$userID,
					'file_path' =>	$filePath,
				);
				$this->getDbConnection()->createCommand()->update($this->tableName(), $data, "id=".$addID);
				//上传成功删除文件
				@unlink($filePath);
			}else{
				@unlink($filePath);
				$error = isset($response->error->details->detail) ? strval($response->error->details->detail): $request->getErrorMsg();
				$this->throwE('##刊登失败##'.$request->getErrorMsg().'##'.$error);
			}

		}catch (Exception $e){
			$addInfo = $this->findByPk($addID);
			$data = array(
				'status' => self::STATUS_FAILURE,
				'last_response_time' => date("Y-m-d H:i:s"),
				'upload_time' => date("Y-m-d H:i:s"),
				'update_time' => date("Y-m-d H:i:s"),
				'upload_message' =>  $e->getMessage(),
				'upload_user_id' =>	$userID,
				'upload_count'	=>	isset($addInfo->upload_count) ? ++$addInfo->upload_count : 1
			);
			$this->getDbConnection()->createCommand()->update($this->tableName(), $data, "id=".$addID." and product_id=0");
			$this->setErrorMessage($e->getMessage().' errCode:'.$e->getCode() );
			return false;
		}
		return true;

	}


	/**
	 * @desc 根据sku获取可刊登账号
	 * @param string $sku
	 */
	public function getAbleAccountsBySku($sku){
		//排除掉已刊登过当前SKU的账户列表
		$filterAccountIds = array();
		//获取账号列表，同时排除掉部分
		$accountList = self::model('priceministerAccount')->getAbleAccountListByFilterId($filterAccountIds);
		$accounAbles = array();
		foreach ($accountList as $account){
			$accounAbles[] = array(
				'id' => $account['id'],
				'short_name' => $account['user_name']
			);
		}
		return $accounAbles;
	}

	/**
	 * @desc 获取刊登类型
	 * @param int $type
	 */
	public static function getListingType($type = ''){
		if($type != ''){
			switch ($type){
				case self::LISTING_TYPE_FIXEDPRICE :
					return Yii::t('priceminister', 'FixedFrice');
					break;
				case self::LISTING_TYPE_VARIATION:
					return Yii::t('priceminister', 'Variation');
					break;
			}
		}
		return array(
			self::LISTING_TYPE_FIXEDPRICE   => Yii::t('priceminister', 'FixedFrice'),
			self::LISTING_TYPE_VARIATION    => Yii::t('priceminister', 'Variation'),
		);
	}

	public function getPmProductAddInfo($condition, $order = '', $field = '*'){
		$conditions = " 1 ";
		if(is_array($condition)){
			foreach ($condition as $key=>$val){
				$conditions .= " AND {$key}='{$val}' ";
			}
		}else {
			$conditions = $condition;
		}
		$data =	$this->getDbConnection()->createCommand()
			->from($this->tableName())
			->where($conditions)
			->select($field)
			->order($order)
			->queryRow();
		return $data;
	}
	/**
	 * @desc 根据主键id更新
	 * @param unknown $id
	 * @param unknown $data
	 * @return boolean
	 */
	public function updateProductAddInfoByPk($id, $data){
		if(empty($id) || empty($data)){
			return false;
		}
		return self::model()->updateByPk($id, $data);
	}
}