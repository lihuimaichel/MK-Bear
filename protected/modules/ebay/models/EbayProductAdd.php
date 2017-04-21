<?php
/**
 * @desc Ebay刊登
 * @author Gordon
 * @since 2015-07-27
 */
class EbayProductAdd extends EbayModel{
    
    /**@var 事件名称*/
    const EVENT_NAME = 'ebay_product_add';
    
    /** @var int 账号ID*/
    public $_accountID = null;
    
    /** @var int 站点ID*/
    public $_siteID = 0;
    
    /** @var string 异常信息*/
    public $_exception = null;
    
    public $site_name;
    public $category_title;
    public $status_text;
    public $config_name;
    public $sale_prices;
    public $account_name; 
    public $visiupload;
    public $add_type_text;
    public $img_url;
    
    public $errormsg;
    
    
    /**
     * @desc 指定匹配分类ID
     * @var unknown
     */
    public $specialCategoryID = 0;
    
    /** @var 刊登类型 */
    const LISTING_TYPE_AUCTION = 1;//拍卖
    const LISTING_TYPE_FIXEDPRICE = 2;//一口价
    const LISTING_TYPE_VARIATION = 3;//多属性
    
    /** @var 刊登模式 */
    const LISTING_MODE_EASY = 1;//简单模式
    const LISTING_MODE_ALL = 2;//详细模式
    
    
    /** @var 拍卖规则类型 */
    const AUCTION_NO_RULE			= 0;		//无规则
    const AUCTION_PROFIT_SALEPRICE 	= 1;		//同步利润到卖价
    const AUCTION_PROFIT_SHIPCOST 	= 2;		//同步利润到运费
    const AUCTION_PROFIT_HOTSALE 	= 3;		//热销品拍卖起价
    
    /**
     * @desc 站点配置
     * @var unknown
     */
    const CONFIG_TYPE_DEFAULT = 0;//默认站点配置，采用站点和仓库进行查询

    //状态
    const STATUS_PENDING    = 1;//待上传
    const STATUS_OPERATING  = 2;//正在操作
    const STATUS_FAILURE    = 3;//刊登失败
    const STATUS_SUCCESS    = 4;//刊登成功
    const STATUS_IMGPENDING = 5;//图片待上传
    
    //添加类型
    const ADD_TYPE_DEFAULT = 0; //默认，单个刊登 
    const ADD_TYPE_BATCH   = 1; //批量刊登
    const ADD_TYPE_SMALL   = 2; //小语种批量刊登
    const ADD_TYPE_COPY    = 3; //复制批量刊登
    const ADD_TYPE_PRE     = 4; //预刊登
    const ADD_TYPE_XLSX    = 5; //xlsx批量导入
    
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
        return 'ueb_ebay_product_add';
    }
    
    /**
     * @desc 获取菜单对应ID
     * @return integer
     */
    public static function getIndexNavTabId() {
    	return UebModel::model('Menu')->getIdByUrl('/ebay/ebayproductadd/index');
    }
    /**
     * @desc 获取条件列表
     * @return multitype:string
     */
    public static function getSpecialConditions(){
    	return array('NEW','BRAND NEW','NEW WITHOUT TAGS','NEU','BRANDNEU','NEU OHNE ETIKETT');
    }
    /**
     * @desc 获取刊登类型
     */
    public static function getListingType(){
        return array(
            self::LISTING_TYPE_AUCTION      => Yii::t('ebay', 'Auction'),
            self::LISTING_TYPE_FIXEDPRICE   => Yii::t('ebay', 'FixedFrice'),
            self::LISTING_TYPE_VARIATION    => Yii::t('ebay', 'Variation'),
        );
    }
    
    /**
     * @desc 获取刊登模式
     */
    public static function getListingMode(){
        return array(
            self::LISTING_MODE_EASY     => Yii::t('ebay', 'Easy'),
            self::LISTING_MODE_ALL      => Yii::t('ebay', 'All'),
        );
    }
    /**
     * @desc 获取拍卖类型
     * @return multitype:string
     */
    public static function getAuctionType(){
    	return array(
    				self::AUCTION_NO_RULE	=>	'无规则',
    				self::AUCTION_PROFIT_SALEPRICE => '0利润(成本同步到卖价)',
    				self::AUCTION_PROFIT_SHIPCOST => '0利润(成本同步到运费)',
    				//self::AUCTION_PROFIT_HOTSALE => '热销拍卖',
    			);
    }
    /**
     * @desc 获取站点配置类型
     * @return multitype:string
     */
    public static function getConfigType(){

		$configType = array();
		$list = EbaySiteParamConfig::model()->getList();
		if ($list){
			$configType[self::CONFIG_TYPE_DEFAULT] = '默认';
			foreach ($list as $val){
				$configType[$val['id']] = $val['config_name'];
			}
		}
		return $configType;
    }
    
    /**
     * @desc 获取默认的GTIN
     * @param number $siteID
     * @return multitype:string |string
     */
    public static function getDefaultGTIN($siteID = 0){
    	//法国	德国	西班牙
    	//Non applicable	NICHT ZUTREFFEND	No aplicable
    	//other Does not apply
    	$defaultGTIN = array(
    			'77'    => 'NICHT ZUTREFFEND',
    			'71'    => 'Non applicable',
    			'186'   => 'No aplicable',
    	);
    	if(is_null($siteID)) return $defaultGTIN;
    	return isset($defaultGTIN[$siteID]) ? $defaultGTIN[$siteID] : 'Does not apply';
    }
    
    function _init(){
    	parent::_init();
    
    	$this->LISTINGTYPE_ARR = array(
                $this->LISTINGTYPE_AUCTION    => '拍卖',
                $this->LISTINGTYPE_FIXEDPRICE => '一口价',
                $this->LISTINGTYPE_VARIATION  => '多属性',
    	);
    
    	$this->STATUS_ARR = array(
                $this->STATUS_PENDING    => '待上传',
                $this->STATUS_OPERATING  => '操作中',
                $this->STATUS_FAILURE    => '刊登失败',
                $this->STATUS_SUCCESS    => '刊登成功',
                $this->STATUS_IMGPENDING => '图片待上传',
    	);
    
    	$this->AUCTION_TYPE = array(
                $this->AUCTION_PROFIT_SALEPRICE => '0利润(成本同步到卖价)',
                $this->AUCTION_PROFIT_SHIPCOST  => '0利润(成本同步到运费)',
                $this->AUCTION_PROFIT_HOTSALE   => '热销拍卖',
    	);

    	$this->CONFIG_TYPE_ARR = self::getConfigType();
    }
    
    //====================== Setter Start ====================== //
    /**
     * @desc 设置指定类目ID
     * @param unknown $categoryID
     */
    public function setSpecialCategoryID($categoryID){
    	$this->specialCategoryID = $categoryID;
    }
    
    
    
    //====================== Setter End ======================== //
    
    
    /**
     * @desc 根据addID获取ebay刊登产品信息
     * @param unknown $addID
     * @return mixed
     */
    public function getEbayProductAddInfoByAddID($addID){
    	return $this->getDbConnection()->createCommand()->from($this->tableName())
    							->where("id=:add_id", array(':add_id'=>$addID))
    							->queryRow();
    }
    
    /**
     * @desc 获取ebay产品刊登信息
     * @param array $condition
     * @param string $order
     * @param string $field
     * @return unknown
     */
    public function getEbayProductAddInfo($condition, $order = '', $field = '*'){
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
     * @desc 获取子sku
     * @param unknown $mainProductId
     * @return multitype:
     */
    public function getSubSKUListByMainProductId($mainProductId){
    	$productSelectedAttribute = new ProductSelectAttribute();
    	$skuAttributeList = $productSelectedAttribute->getSelectedAttributeSKUListByMainProductId($mainProductId);
    	$skus = array();
    	return $skus;
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
     * @desc 根据addID获取子SKU
     * @param unknown $addId
     * @return multitype:unknown
     */
    public function getSubSKUListPairsByAddId($addId){
    	$ebayProductAddVariationModel = new EbayProductAddVariation();
    	$variationList = $ebayProductAddVariationModel->getEbayProductAddVariationJoinEbayProductAddByAddID($addId);
    	
    	$skus = array();
		if($variationList){
			foreach ($variationList as $variation){
				$skus[$variation['id']] = $variation['son_sku'];
			}
		}
    	return $skus;
    }
    
    /**
     * @desc 获取产品属性列表
     * @param unknown $mainProductId
     * @return mixed
     */
    public function getProductAttributeListByMainProductId($mainProductId){
    	$productSelectedAttribute = new ProductSelectAttribute($mainProductId);
    	$attributeList = $productSelectedAttribute->getAttributeSKUListByMainProductId($mainProductId);
    	return $attributeList;
    }
    
    public function getProductInfoByVariationSKU($variationSKU, $accountID, $siteID = NULL){
    	
    }
    
    /**
     * @desc 检测是否刊登过sku
     * @param unknown $sku
     * @param unknown $accountID
     * @param number $siteID
     * @param string $uploadStatus
     * @param number $withinMaxDay
     * @return boolean
     */
    public function checkSKUExists($sku, $accountID, $siteID = 0, $uploadStatus = null, $withinMaxDay = 0){
    	$flag = false;
    	$skuInfo = $this->getDbConnection()->createCommand()
    							->from($this->tableName())
    							->where("sku=:sku and account_id=:account_id and site_id=:site_id", array(':sku'=>$sku, ':account_id'=>$accountID, ':site_id'=>$siteID))
    							->andWhere($uploadStatus === null ? "1" : "status in(". $uploadStatus .")")
    							->andWhere($withinMaxDay == 0 ? '1' : "update_time>='" . date("Y-m-d H:i:s", time()-$withinMaxDay*24*3600) . "'")
    							->queryRow();
    	if($skuInfo){
    		$flag = true;
    	}
    	return $flag;
    }
    
    /**
     * @desc 批量删除
     * @param unknown $addIDs
     * @return boolean
     */
    public function batchDel($addIDs){
    	if(empty($addIDs)) return false;
    	//只删除待上传和上传失败的，图片待上传的
    	$idList = $this->getDbConnection()->createCommand()->from($this->tableName())->select("id")
    							->where(array('in', 'id', $addIDs))->andWhere("status in(".self::STATUS_PENDING.", ".self::STATUS_FAILURE.", ".self::STATUS_IMGPENDING.")")
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
	    	$res = $this->getDbConnection()->createCommand()->delete($this->tableName(), "id in(".MHelper::simplode($newAddIDs).") and status in(".self::STATUS_PENDING.", ".self::STATUS_FAILURE.", ".self::STATUS_IMGPENDING.")");
	    	//删除其他的
	    	EbayProductAddShipping::model()->getDbConnection()->createCommand()->delete(EbayProductAddShipping::model()->tableName(), "add_id in(".MHelper::simplode($newAddIDs).")");
	    	EbayProductAddVariation::model()->getDbConnection()->createCommand()->delete(EbayProductAddVariation::model()->tableName(), "add_id in(".MHelper::simplode($newAddIDs).")");
	    	EbayProductAddVariationAttribute::model()->getDbConnection()->createCommand()->delete(EbayProductAddVariationAttribute::model()->tableName(), "add_id in(".MHelper::simplode($newAddIDs).")");
	    	EbayProductAddSpecific::model()->getDbConnection()->createCommand()->delete(EbayProductAddSpecific::model()->tableName(), "add_id in(".MHelper::simplode($newAddIDs).")");
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
    
    /**
     * @desc 获取待上传产品
     * @param number $limit
     * @param string $accountID
     * @return mixed
     */
    public function getPenddingUploadProductListByLimit($limit = 100, $siteID = null, $accountID = null, $fields = "*", $sku='', $addType=null, $listingType = null) {
    	$command = $this->getDbConnection()->createCommand()
                                ->select($fields)
    							->from($this->tableName())
    							->where("status in(".self::STATUS_PENDING .",". self::STATUS_FAILURE.",". self::STATUS_IMGPENDING ." )")
    							->limit($limit);
        $command->andWhere("upload_count<3");
    	if($siteID !== null){
    		if(!is_array($siteID)){
    			$siteID = array($siteID);
    		}
    		$command->andWhere(array('IN', 'site_id', $siteID));
    	}
    	if($accountID){
    		$command->andWhere("account_id='{$accountID}'");
    	}
    	if($addType !== null){
    		if(!is_array($addType)){
    			$addType = array($addType);
    		}
    		$command->andWhere(array('IN', 'add_type', $addType));
    	}
    	if($listingType !== null){
    		if(!is_array($listingType)){
    			$listingType = array($listingType);
    		}
    		$command->andWhere(array('IN', 'listing_type', $listingType));
    	}
        $command->order("id asc");
    	$result =  $command->queryAll();
    	if(isset($_REQUEST['bug'])){
    		echo $command->getText();
    	}
    	return $result;
    }
    
    
    /**
     * @desc 保存产品添加数据
     * @param unknown $datas
     */
    public function saveProductAddData($data){
    	$isUpdate = false;
    	//如果存在就更新
    	if($data['id']){
    		$isUpdate = true;
    	}
    	if($isUpdate){
    		$res = $this->getDbConnection()->createCommand()
    								->update($this->tableName(), $data, "id={$data['id']}");
    		if(!$res){
    			return false;
    		}
    		$addID = $data['id'];
    	}else{
    		$res = $this->getDbConnection()->createCommand()
    						->insert($this->tableName(), $data);
    		if(!$res){
    			return false;
    		}
    		$addID = $this->getDbConnection()->getLastInsertID();
    	}
    	return $addID;
    }
    
    /**
     * @desc 保存产品添加物流规则信息
     * @param unknown $addId
     * @param unknown $shippingData
     */
    public function saveProductAddShipping($addId, $shippingData){
    	$shippingData['add_id'] = $addId;
    	return EbayProductAddShipping::model()->saveData($shippingData);
    }
    
    /**
     * @desc 添加数据通过批量方式
     * @param unknown $sku
     * @param unknown $accountID
     * @param number $siteID
     * @throws Exception
     * @return boolean
     */
    public function addProductByBatch($sku, $accountID, $siteID = 0, $duration = 'GTC', $listingType = null, $auctionStatus = null, $auctionPlanDay = null, $auctionRule = null, $configType = 0, $addType=null,$sonSkuList=array()){
        if ( empty($addType) ) {
            $addType = self::ADD_TYPE_BATCH;
        }
        //11700;
        $specialCategoryID = $this->specialCategoryID;
    	//根据sku查出对应的当前待刊登、已经刊登好的sku，没有刊登好的不进入
    	//对比同账号是否已经存在相同的LISTING
    	//组装数据
    	
    	//1、判断当前账号当前站点是否在产品管理里在线的
    	//2、判断当前账号当前站点是否在待刊登待上传（2天内）
    	//3、判断当前站点是否上传过该sku（包括下线的）
    	//4、取出各部分数据
    	//5、写入到待刊登列表
    	$configType = (int)$configType;
    	if($listingType == EbayProductAdd::LISTING_TYPE_AUCTION){
    		if($duration == 'GTC') $duration = "Days_5";
    		if($auctionStatus == null) $auctionStatus = 0;
    		if($auctionPlanDay == null) $auctionPlanDay = 10;
    		if($auctionRule == null) $auctionRule = 0;
    	}
    	try{
    		$ebayProductModel = new EbayProduct();
    		if($listingType != EbayProductAdd::LISTING_TYPE_AUCTION){//拍卖的不需要验证
	    		//1、判断当前账号当前站点是否在产品管理里在线的
	    		$exists = $ebayProductModel->checkSKUExists($sku, $accountID, $siteID, true);
	    		if($exists){
	    			throw new Exception(Yii::t("ebay", "Had Upload This SKU"));
	    		}
	    		//2、判断当前账号当前站点是否在待刊登待上传（2天内）
	    		$exists = $this->checkSKUExists($sku, $accountID, $siteID, null, 2);//self::STATUS_PENDING
	    		if($exists){
	    			throw new Exception(Yii::t("ebay", "Had Upload This SKU"));
	    		}
    		}
			//获取sku信息
			$skuInfo = Product::model()->getProductBySku($sku);
			//@todo 过滤掉停售下架、侵权的产品
			if (empty($skuInfo)) {
				throw new Exception(Yii::t('ebay', 'SKU has not Exists'));
			}
			//检测是否侵权
			if(ProductInfringe::model()->getProductIfInfringe($sku)){
				throw new Exception(Yii::t('ebay', 'The SKU has been infringed, can not be uploaded to EBAY'));
			}
			
			$isVariation  = (isset($skuInfo['product_is_multi']) && $skuInfo['product_is_multi'] == Product::PRODUCT_MULTIPLE_VARIATION) ? true : false;
			
			$langCode = EbaySite::getLanguageBySiteIDs($siteID);
			$productDesc = Productdesc::model()->getDescriptionInfoBySkuAndLanguageCode($sku, $langCode);
			
            //判断标题、描述是否为空
            if (trim($productDesc['title']) == '' || trim($productDesc['description']) == '') {
                throw new Exception($sku. Yii::t('ebay', ' Title or Description is empty, can not be uploaded to EBAY'));
            }

			$skuInfo['title'] = $productDesc['title'];
			$skuInfo['description'] = $productDesc['description'];
			$skuInfo['included'] = $productDesc['included'];
			$variationSkuOriList = $this->getSubSKUListPairsByMainProductId($skuInfo['id']);
    		//优先取出在刊登表中的数据，再取出在线的
            if($addType != self::ADD_TYPE_XLSX){
        		$addInfo = $this->getEbayProductAddInfo("sku='{$sku}' and site_id='{$siteID}'");
            }

    		//是否确定为子SKU拍卖刊登
    		$isVariationAuction = false;
    		$mainSKU = $sku;
    		if(empty($addInfo) && $listingType == EbayProductAdd::LISTING_TYPE_AUCTION && $isVariation){//拍卖 子sku
    			$isVariationAuction = true;
    			//获取子sku所在的主sku
    			$mainSKU = Product::model()->getMainSkuByVariationSku($sku);
    			$addInfo = $this->getEbayProductAddInfo("sku='{$mainSKU}' and site_id='{$siteID}'");
    		}
    		//models
    		$ebayProductAddSpecificModel = new EbayProductAddSpecific();
    		$ebayProductAddShippingModel = new EbayProductAddShipping();
    		$ebayProductVariationModel = new EbayProductVariation();
    		
    		$encryptSku = new encryptSku();
    		
    		//分类信息
    		$categoryID = $categoryID2 = 0;
    		$storeCategoryID = $storeCategoryID2 = 0;
    		$conditionID = 0;
    		$variationPictureSpecific = "";//设置图片属性
    		$categoryName = "";
    		$specialData = array();//共有属性数据
    		$variationAttributes = array();//子sku属性数据
    		$variationSkus = array();//子SKU数据
    		$addShippingInfo = array();//物流模板数据
    		$excludeShippingCountry = array();//屏蔽发货国家数据
    		$ebayCategory = new EbayCategory();
    		if(!empty($addInfo)){
    			$variationPictureSpecific = $addInfo['variation_picture_specific'];
    			$categoryID = $addInfo['category_id'];
    			$categoryID2 = $addInfo['category_id2'];
    			$categoryName = EbayCategory::model()->getCategoryNameByID($categoryID);
    			/* $storeCategoryID = $addInfo['store_category_id'];
    			$storeCategoryID2 = $addInfo['store_category_id2']; */
    			$conditionID = $addInfo['condition_id'];
    			//取出共有属性信息
    			$addSpecificList = $ebayProductAddSpecificModel->getEbayProductAddSpecificByAddID($addInfo['id']);
    			if($addSpecificList){
    				foreach ($addSpecificList as $value){
    					$specialData[$value['name']] = array(
    							'add_id'	=>	0,//@todo 插入时记得插入addID
    							'name'		=>	$value['name'],
    							'value'		=>	$value['value'],
    							'custom'	=>	0
    					);
    				}
    			}
    			
    			
    			//取出子SKU
    			if($isVariationAuction){
    				//如果是子sku拍卖的则不需要取出了
    				$variationSkuList = array();
    			}else{
    				$variationSkuList = EbayProductAddVariation::model()->getEbayProductAddVariationListByAddID($addInfo['id']);
					if(!empty($sonSkuList)){
						foreach ($variationSkuList as $k=>$variation){
							if(!in_array($variation['son_sku'], $sonSkuList)) unset($variationSkuList[$k]);
						}
					}
    			}
    			
    			if(empty($variationSkuOriList)){
    				$variationSkuList = array();
    			}
    			if($variationSkuList){
    				foreach ($variationSkuList as $variation){
    					if(!in_array($variation['son_sku'], $variationSkuOriList)) continue;
    					//取出子sku属性
    					$variationAttributes[$variation['son_sku']] = EbayProductAddVariationAttribute::model()->getVariationAttributeListByVariationId($variation['id'], $variation['add_id']);
    					$variation['son_seller_sku'] = $encryptSku->getEncryptSku($variation['son_sku']);
    					$variation['add_id'] = 0;
    					$variation['variation_price'] = 0.0;
    					unset($variation['id']);
    					$variationSkus[$variation['son_sku']] = $variation;
    				}
    			}
    			
    			//取出对应物流运费模板
    			$shippingTemplate = $ebayProductAddShippingModel->getProductShippingInfoByAddID($addInfo['id']);
    			if($shippingTemplate){
    				foreach ($shippingTemplate as $shipping){
    					$shipping['add_id'] = 0;
    					unset($shipping['id']);
    					$addShippingInfo[] = $shipping;
    				}
    			}
    		}else{
    			//自动匹配分类id
    			/* $cateList = $ebayCategory->getSuggestCategoryList($accountID, $siteID, $skuInfo['title'], $sku);
    			$firstcate = null;
    			is_array($cateList) && $cateList && $firstcate = array_shift($cateList);
    			if($firstcate){
    				$categoryID = $firstcate['categoryid'];
    				$categoryName = $firstcate['categoryname'];
    			} */
    			
    			// 1、检测是否为子SKU
    			// 2、子SKU不存在的情况下，找到父级sku
    			// 3、父SKU存在的 情况下，检测是否有对应的子SKU，
    			// 4、对应的子SKU则取出数据
    			
    			//从产品表中获取
    			$ebayProductInfo = $ebayProductModel->getEbayProductInfoBySKU($sku, $siteID);
    			if(empty($ebayProductInfo) && $isVariationAuction){
    				//寻找父SKU信息是否存在
    				$ebayProductInfo = $ebayProductModel->getEbayProductInfoBySKU($mainSKU, $siteID);
    			}

                if(empty($ebayProductInfo)){
                    $ebayProductInfo = $ebayProductModel->getEbayProductInfoBySKU($mainSKU, null);
                    $cateList = $ebayCategory->getSuggestCategoryList($accountID, $siteID, $skuInfo['title'], $mainSKU);
                    if($addType == 2 && $cateList && $specialCategoryID>0){
                    	//临时处理指定类目
                    	foreach ($cateList as $_cateInfo){
                    		$checkResult = $ebayCategory->checkCategoryWithInParentCategory($siteID, $_cateInfo['categoryid'], $specialCategoryID);//在指定类目范围内
                    		if($checkResult){
                    			$categoryID = $_cateInfo['categoryid'];
                    		}
                    	}
                    }else{
                    	$firstcate = null;
                    	is_array($cateList) && $cateList && $firstcate = array_shift($cateList);
                    	if($firstcate){
                    		//$defaultHistoryCategory[$firstcate['categoryid']] = $firstcate['categoryname'];
                    		$categoryID = $firstcate['categoryid'];
                    	}
                    }
                    
                    
                }else{
                    $categoryID = $ebayProductInfo['category_id'];
                    $categoryID2 = $ebayProductInfo['category_id2'];
                }
                //@todo 如果为拍卖子sku则取父SKU信息
                
                if(empty($ebayProductInfo)){
                    throw new Exception(Yii::t('ebay', 'SKU Not Add'));
                }

    			if(!$categoryID){
    				throw new Exception(Yii::t('ebay', 'SKU Not Find Category'));
    			}
    			
    			
    			$categoryName = EbayCategory::model()->getCategoryNameByID($categoryID);
    			//获取属性信息
    			//获取specific
    			$categorySpecifics = EbayCategoryInfo::model()->getCategorySpecifics($accountID, $siteID, $categoryID);
    			//获取features
    			$categoryFeatures = EbayCategoryInfo::model()->getCategoryFeatures($accountID, $siteID, $categoryID);
    			//获取conditionID 
    			if($categoryFeatures){
    				if(isset($categoryFeatures['ConditionEnabled']) && ($categoryFeatures['ConditionEnabled'] == 'Enabled' || $categoryFeatures['ConditionEnabled'] == 'Required')){
		    			$specialNames = $this->getSpecialConditions();
			    		foreach ($categoryFeatures['ConditionValues']->Condition as $condition){
			    			$conditionName = strtoupper(trim($condition->DisplayName));
			    			if(in_array($conditionName, $specialNames)){
			    				$conditionID = trim($condition->ID);
			    				break;
			    			}
			    		}
    				}
    			}
    			
    			if($categorySpecifics){
    				foreach ($categorySpecifics as $special){
    					$isRequired = isset($special->ValidationRules->MinValues) ? (int)$special->ValidationRules->MinValues : 0; //大于0必填
    					if(!$isRequired){
    						continue;
    					}
    					//ebay有默认值跳过
    					if((string)$special->ValidationRules->SelectionMode == 'Prefilled'){
    						continue;
    					}
    					$spcname = (string)$special->Name;
    					//判断为输入型的
    					if(!isset($special->ValueRecommendation)){
    						$specific = "Does not apply";
    					}else{
    						//取第一条默认
    						if(isset($special->ValueRecommendation->Value)){//一个推荐值
    							$specific = (string)$special->ValueRecommendation->Value;
    						}else{
    							foreach ($specific->ValueRecommendation as $val){
    								if($spcname == 'Brand' && ((string)$val->Value == 'Unbranded/Generic' || (string)$val->value == 'Unbranded')){//@todo 可能需要根据每个站点来进行判断
    									$specific = (string)$val->Value;
    								}elseif($spcname == 'MPN' && ((string)$val->Value == 'Does Not Apply')){//@todo 可能需要根据每个站点来进行判断
    									$specific = (string)$val->Value;
    								}
    							}
    						}
    					}
    					
    					//判断上下级关系
    					$parentName = "";
    					$parentVal = "";
    					if(isset($special->ValidationRules->Relationship)){
    						$parentName = (string)$special->ValidationRules->Relationship->ParentName;
    						
    						if(!empty($specialData[$parentName])){
    							$parentVal = $specialData[$parentName]['value'];
    						}else{
    							$parentVal = "";
    						}
    						
    						foreach ($special->ValueRecommendation as $val){
    							if(!isset($val->ValidationRules) || !isset($val->ValidationRules->Relationship)){
    								$specific = (string)$val->Value;
    							}else{
    								foreach ($val->ValidationRules->Relationship as $v){
    									if((string)$v->ParentName == $parentName && (string)$v->ParentValue == $parentVal){
    										$specific = (string)$val->Value;
    										break;
    									} 
    								}
    							}
    						}
    						
    					}
    					$specialData[$spcname] = array(
    							'add_id'	=>	0,//@todo 插入时记得插入addID
    							'name'		=>	$spcname,
    							'value'		=>	$specific,
    							'custom'	=>	0
    					);
    				}
    			}
    			
    			if($isVariationAuction){//如果是子SKU拍卖的则不需要子sku了
    				$ebayVariationList = array();
    			}else{
    				//获取已有的子SKU属性
    				$ebayVariationList = $ebayProductVariationModel->getProductVariantListByCondition("listing_id=:listing_id", array(':listing_id'=>$ebayProductInfo['id']));
					if(!empty($sonSkuList)){
						foreach ($ebayVariationList as $k=>$variation){
							if(!in_array($variation['sku'], $sonSkuList)) unset($ebayVariationList[$k]);
						}
					}
    			}
    			
    			if(empty($variationSkuOriList)){
    				$ebayVariationList = array();
    			}
    			if($ebayVariationList){
    				foreach ($ebayVariationList as $variation){
    					if(!in_array($variation['sku'], $variationSkuOriList)) continue;
    					$variationSkus[$variation['sku']] = array(
    															'son_sku'	=>	$variation['sku'],
    															'son_seller_sku'	=>	$encryptSku->getEncryptSku($variation['sku']),
    															'add_id'	=>	0,
    															'variation_price'	=>	0.00
    														);
    					

						if($variation['variation_specifics']){
							$variationSpecifics = json_decode($variation['variation_specifics'], true);
							foreach ($variationSpecifics as $key=>$value){
								if(empty($variationPictureSpecific) || strtoupper($key) == "COLOR"){
									$variationPictureSpecific = $key;
								}
								unset($specialData[$key]);
								$variationAttributes[$variation['sku']][] = array(
										'name'	=>	$key,
										'value'	=>	$value
								);
							}
    						
						}
    					
    				}
    			}
    			//根据模板选择物流方式
    			//获取属性模板
    			$wareHouseID = EbayAccountSite::model()->getWarehouseByAccountSite($accountID, $siteID);//找出对应的仓库ID
    			$attributeInfo = EbayProductAttributeTemplate::model()->getProductAttributeTemplage("site_id='".$siteID."' AND abroad_warehouse = '".$wareHouseID."' AND config_type = ".$configType);
    			if(empty($attributeInfo)){
    				throw new Exception(Yii::t('ebay', 'Not Find Attribute Template'));
    			}
    			$shippingTemplate = EbayProductShippingTemplate::model()->getShippingTemplateListByPid($attributeInfo['id']);
    			if($shippingTemplate){
    				foreach ($shippingTemplate as $template){
    					$template['ship_cost'] = "0";
    					$template['additional_ship_cost'] = "0";
    					unset($template['id'], $template['pid']);
    					$addShippingInfo[] = $template;
    				}
    			}
    		}
    		
    		if($addType == 2 && $specialCategoryID){//临时处理指定类目
    			$checkResult = $ebayCategory->checkCategoryWithInParentCategory($siteID, $categoryID, $specialCategoryID);//在家居范围内
    			if(!$checkResult){
    				throw new Exception(Yii::t('ebay', 'SKU Category Not Allow'));
    			}
    		}
    		
    		//主表数据
    		//图片数据和子sku图片
    		//多属性子sku数据
    		//特殊属性数据
    		//物流数据
    		//屏蔽国家
    		
    		//默认排除国家
    		$defaultExcludeCountryCode = $defaultExcludeCountryNameStr = "";
    		$defaultExcludeCountry = EbayExcludeShipingCountry::model()->getExcludeShipingCountry($siteID, $accountID);
    		if($defaultExcludeCountry){
    			$defaultExcludeCountryCode = $defaultExcludeCountry['exclude_ship_code'];
    			$defaultExcludeCountryNameStr = $defaultExcludeCountry['exclude_ship_name'];
    		}
    		
    		if($listingType != EbayProductAdd::LISTING_TYPE_AUCTION){
    			$listingType = $skuInfo['product_is_multi'] != Product::PRODUCT_MULTIPLE_MAIN ? self::LISTING_TYPE_FIXEDPRICE : self::LISTING_TYPE_VARIATION;
    		}
    		
	        $title = EbayProductDescriptionTemplate::model()->getTitle($skuInfo['title'], $accountID, $siteID);
	        $detail = $this->getDescriptionNoImg($sku, $accountID, $siteID);
	        
	        $nowtime = date("Y-m-d H:i:s");
	       	$userID = (int)Yii::app()->user->id;
	  		$currency = EbaySite::getCurrencyBySiteID($siteID);
	  		
	  		$auctionShipingCost = array();
	        //计算价格
	  		$salePrice = 0;
	  		if($listingType == self::LISTING_TYPE_FIXEDPRICE){
	       		$salePriceData = EbayProductSalePriceConfig::model()->getSalePrice($sku, $currency, $siteID, $accountID, $categoryName);
	       		if($salePriceData && $salePriceData['salePrice'] > 0){
	       			$salePrice = $salePriceData['salePrice'];
	       		}else{
	       			throw new Exception("SalePrice Error");
	       		}
	       		
	  		}elseif($listingType == self::LISTING_TYPE_AUCTION){
	  			if($variationSkus){
	  				foreach ($variationSkus as &$variation){
	  					$salePriceData = $this->getAuctionPriceByAuctionRule($auctionRule, $variation['son_sku'], $currency, $siteID, $accountID, $categoryName);
	  					if($salePriceData && $salePriceData['auction_price'] > 0){
	  						$variation['variation_price'] = $salePriceData['auction_price'];
	  						$auctionShipingCost[$variation['son_sku']] = $salePriceData['shiping_cost'];
	  					}else{
	  						$variation['variation_price'] = 0.01;
	  						$auctionShipingCost[$variation['son_sku']] = 0;
	  						throw new Exception("SalePrice Error");
	  					}
	  				}
	  			}else{
	  				$salePriceData = $this->getAuctionPriceByAuctionRule($auctionRule, $sku, $currency, $siteID, $accountID, $categoryName);
	  				if($salePriceData && $salePriceData['auction_price'] > 0){
	  					$salePrice = $salePriceData['auction_price'];
	  					$auctionShipingCost[$sku] = $salePriceData['shiping_cost'];
	  				}else{
	  					$salePrice = 0.01;
	  					$auctionShipingCost[$sku] = 0;
	  					throw new Exception("SalePrice Error");
	  				}
	  			}
	  		}elseif($variationSkus){
	  			foreach ($variationSkus as $key=>$variation){
	  				$salePriceData = EbayProductSalePriceConfig::model()->getSalePrice($variation['son_sku'], $currency, $siteID, $accountID, $categoryName);
	  				if($salePriceData && $salePriceData['salePrice'] > 0){
	  					$variationSkus[$key]['variation_price'] = $salePriceData['salePrice'];
	  				}else{
	  					throw new Exception("SalePrice Error");
	  				}
	  			}			  				
	  		}else{
	  			$salePriceData = EbayProductSalePriceConfig::model()->getSalePrice($sku, $currency, $siteID, $accountID, $categoryName);
	  			if($salePriceData && $salePriceData['salePrice'] > 0){
	  				$salePrice = $salePriceData['salePrice'];
	  			}else{
	  				throw new Exception("SalePrice Error");
	  			}
	  		}
	  		//GTIN UPC ISBN EAN
	  		
	  		
	  		/* echo "<pre>";
	  		print_r($variationSkus); */
	  		//return true;
	  		// ================= START:入库操作 ================ //
	  		$dbtransaction = $this->getDbConnection()->getCurrentTransaction();
	  		if(!$dbtransaction){
	  			$dbtransaction = $this->getDbConnection()->beginTransaction();
	  		}
	  		try{
	  			$defaultGTIN = 'Does not apply';
	  			/* if(EbaySite::getSiteName($siteID) == 'Spain'){//西班牙站点no aplicable
	  				$defaultGTIN = "no aplicable";
	  			} */
	  			$defaultGTIN = $this->getDefaultGTIN($siteID);
	  			$listingDetail = array(
	  					'brand'	=>	$defaultGTIN, 'mpn'	=>	$defaultGTIN, 'upc'	=>	$defaultGTIN, 'isbn'	=>	$defaultGTIN, 'ean'	=>	$defaultGTIN
	  			);
	  			$gtinkeys = array('UPC', 'EAN', 'ISBN', 'GTIN');
	  			$defaultConditionID = 1000; //全新
	  			$addData = array(
	  					'site_id'		=>	intval($siteID),
	  					'account_id'	=>	intval($accountID),
	  					'sku'			=>	$sku,
	  					'seller_sku'	=>	$encryptSku->getEncryptSku($sku),
	  					'listing_type'	=>	intval($listingType),
	  					'title'			=>	$title,
	  					'start_price'	=>	$salePrice,
	  					'currency'		=>	$currency,
	  					'category_id'	=>	intval($categoryID),
	  					'category_id2'	=>	intval($categoryID2),
	  					'store_category_id'	=>	intval($storeCategoryID),
	  					'store_category_id2'=>	intval($storeCategoryID2),
	  					'condition_id'	=>	empty($conditionID) ? intval($defaultConditionID): intval($conditionID),
	  					'config_type'	=>	intval($configType),
	  					'variation_picture_specific'	=> $variationPictureSpecific,
	  					'auction_rule'	=>	intval($auctionRule),
	  					'listing_duration'	=> empty($duration) ? 'GTC' : $duration,
	  					'brand'			=>	$listingDetail['brand'],
	  					'mpn'			=>	$listingDetail['mpn'],
	  					'upc'			=>	$listingDetail['upc'],
	  					'isbn'			=>	$listingDetail['isbn'],
	  					'ean'			=>	$listingDetail['ean'],
	  					'upload_msg'	=>	'',
	  					'uuid'			=>	'',
	  					'item_id'		=>	0,
	  					'create_user_id'	=>	intval($userID),
	  					'update_user_id'	=>	intval($userID),
	  					'create_time'		=>	$nowtime,
	  					'update_time'		=>	$nowtime,
	  					'last_response_time'=>	$nowtime,
	  					'status'			=>	EbayProductAdd::STATUS_PENDING,
	  					'exclude_ship_code'	=>	$defaultExcludeCountryCode,
	  					'exclude_ship_name'	=>	$defaultExcludeCountryNameStr,
	  					'detail'			=>	$detail,
	  					'add_type'			=>	intval($addType)
	  			);
	  			
	  			$ebayProductImageAddModel = new EbayProductImageAdd();
	  			$ebayProductAddVariationModel = new EbayProductAddVariation();
	  			$ebayProductAddVariationAttributeModel = new EbayProductAddVariationAttribute();
	  			$beginLetter = "A";
	  			$beginLetterNumber = ord($beginLetter);
	  			if($listingType == EbayProductAdd::LISTING_TYPE_AUCTION && $variationSkus){
	  				//子sku
	  				foreach ($variationSkus as $variationData){
	  					$variationSKU = $variationData['son_sku'];
	  					$ebayProductImageAddModel->addProductImageByVariationSku($variationSKU, $accountID, $siteID, true, true);
	  					$tempAddData = $addData;
	  		
	  					$tempAddData['sku'] = $variationSKU;
	  					$tempAddData['seller_sku'] = $encryptSku->getEncryptSku($variationSKU);
	  					$tempAddData['start_price'] = $variationData['variation_price'];
	  					
	  					/* $titlePrefix = array();
	  					if(!empty($variationAttributes[$variationSKU])){
	  						foreach ($variationAttributes[$variationSKU] as $attr){
	  							if(in_array(strtoupper($attr['name']), $gtinkeys)) continue;
	  							$titlePrefix[$attr['value']] = $attr['value'];
	  						}
	  					}
	  					
	  					//加前后缀
	  					if($titlePrefix){
	  						$tempAddData['title'] = $tempAddData['title'] . " " . implode(" ", $titlePrefix);
	  					} */
	  					$tempAddData['title'] = "#" . chr($beginLetterNumber) . " " . $tempAddData['title'];
	  					$tempAddData['title'] = mb_substr($tempAddData['title'], 0, 80);
	  					$beginLetterNumber++;
	  					$addInsertID = 0;
	  					$this->getDbConnection()->createCommand()->insert($this->tableName(), $tempAddData);
	  					$addInsertID = $this->getDbConnection()->getLastInsertID();
	  		
	  					//保存物流信息
	  					if($addShippingInfo){
	  						foreach ($addShippingInfo as $shipping){
	  							$shipping['site_id'] = $siteID;
	  							$shipping['add_id'] = $addInsertID;
	  							$shipping['ship_cost'] = isset($auctionShipingCost[$variationSKU]) ? $auctionShipingCost[$variationSKU] : 0;
	  							$ebayProductAddShippingModel->saveData($shipping);
	  						}
	  					}
	  		
	  					//保存共有属性
	  					if($specialData){
	  						foreach ($specialData as $specific){
	  							$specific['add_id'] = $addInsertID;
	  							$ebayProductAddSpecificModel->getDbConnection()->createCommand()->insert($ebayProductAddSpecificModel->tableName(), $specific);
	  						}
	  					}
	  		
	  					// Start:写入拍卖表
	  					$auctionStartTime = date("Y-m-d H:i:s");
	  					$auctionEndTime = date("Y-m-d H:i:s", strtotime("+6 month"));
	  					$auctionData = array(
	  							'add_id'		=>	$addInsertID,
	  							'start_time'	=>	$auctionStartTime,
	  							'end_time'		=>	$auctionEndTime,
	  							'plan_day'		=>	intval($auctionPlanDay),
	  							'auction_status'=>	intval($auctionStatus),
	  							'update_time'	=>	$nowtime,
	  							'pid'			=>	0,
	  							'count'			=>	0
	  					);
	  					$res = EbayProductAddAuction::model()->saveData($auctionData);
	  					if(!$res){
	  						throw  new Exception("Save Auction Info Failure!!!");
	  					}
	  					// End:写入拍卖表
	  				}
	  			}else{
	  				$addData['title'] = mb_substr($addData['title'], 0, 80);
	  				$addInsertID = 0;
	  				$this->getDbConnection()->createCommand()->insert($this->tableName(), $addData);
	  				$addInsertID = $this->getDbConnection()->getLastInsertID();
	  		
	  				//保存物流信息
	  				if($addShippingInfo){
	  					foreach ($addShippingInfo as $shipping){
	  						$shipping['add_id'] = $addInsertID;
	  						if($listingType == EbayProductAdd::LISTING_TYPE_AUCTION){
	  							$shipping['ship_cost'] = isset($auctionShipingCost[$sku]) ? $auctionShipingCost[$sku] : 0;
	  						}
							//美国站点低于5美金本地物流方式
							if($shipping['shipping_type']==1 && $shipping['site_id']==0){
								if($shipping['ship_cost']==0 && $salePrice < 5 && $currency=='USD'){
									$shipping['shipping_service'] = 'EconomyShippingFromOutsideUS';
								}
							}
	  						$ebayProductAddShippingModel->saveData($shipping);
	  					}
	  				}
	  				//主图片
	  				$ebayProductImageAddModel->addProductImageBySku2($sku, $accountID, $siteID);
	  				if($variationSkus){
	  					//子sku
	  					foreach ($variationSkus as $variationData){
	  						$variationSKU = $variationData['son_sku'];
	  						$ebayProductImageAddModel->addProductImageByVariationSku($variationSKU, $accountID, $siteID);
	  						//添加子sku
	  						$variationData['add_id'] = $addInsertID;
	  						unset($variationData['id']);
	  						$ebayProductAddVariationModel->getDbConnection()->createCommand()->insert($ebayProductAddVariationModel->tableName(), $variationData);
	  						$variationID = $ebayProductAddVariationModel->getDbConnection()->getLastInsertID();
	  						//添加子sku属性
	  						$addVariationAttributes = array();
	  						//GTIN(upc isbn ean mpn)
	  						//variation_id  add_id name value
	  						foreach ($gtinkeys as $gtinkey){
	  							$addVariationAttributes[] = array(
	  									'variation_id'	=>	$variationID,
	  									'add_id'		=>	$addInsertID,
	  									'name'			=>	$gtinkey,
	  									'value'			=>	$defaultGTIN
	  							);
	  						}
	  						//其他属性
	  						$variationValue = $variationAttributes[$variationSKU];
	  						foreach ($variationValue as $value){
	  							unset($specialData[$value['name']]);
	  							$addVariationAttributes[] = array(
	  									'variation_id'	=>	$variationID,
	  									'add_id'		=>	$addInsertID,
	  									'name'			=>	$value['name'],
	  									'value'			=>	$value['value']
	  							);
	  						}
	  						//添加子SKU属性
	  						if($addVariationAttributes){
	  							foreach ($addVariationAttributes as $attribute){
	  								$ebayProductAddVariationAttributeModel->getDbConnection()->createCommand()->insert($ebayProductAddVariationAttributeModel->tableName(), $attribute);
	  							}
	  						}
	  					}
	  				}
	  				//保存共有属性
	  				if($specialData){
	  					foreach ($specialData as $specific){
	  						$specific['add_id'] = $addInsertID;
	  						$ebayProductAddSpecificModel->getDbConnection()->createCommand()->insert($ebayProductAddSpecificModel->tableName(), $specific);
	  					}
	  				}
	  				 
	  				if($listingType == EbayProductAdd::LISTING_TYPE_AUCTION){
	  					// ============== Start:写入拍卖表 =================
	  					$auctionStartTime = date("Y-m-d H:i:s");
	  					if($auctionPlanDay){
	  						$auctionEndTime = date("Y-m-d H:i:s", strtotime("+ ". ($auctionPlanDay * 4) . "day"));
	  					}else{
	  						$auctionEndTime = date("Y-m-d H:i:s", strtotime("+6 month"));
	  					}
	  					$auctionData = array(
	  							'add_id'		=>	$addInsertID,
	  							'start_time'	=>	$auctionStartTime,
	  							'end_time'		=>	$auctionEndTime,
	  							'plan_day'		=>	intval($auctionPlanDay),
	  							'auction_status'=>	intval($auctionStatus),
	  							'update_time'	=>	$nowtime,
	  							'pid'			=>	0,
	  							'count'			=>	0
	  					);
	  					$res = EbayProductAddAuction::model()->saveData($auctionData);
	  					if(!$res){
	  						throw  new Exception("Save Auction Info Failure!!!");
	  					}
	  					// =============== End:写入拍卖表 ===================
	  				}
	  			}
	  			$dbtransaction->commit();
	  			//MHelper::writefilelog("ebay/ebayBatchAdd-".$accountID.".log", date("Y-m-d H:i:s") . "\t\tSKU:{$sku}\t\tAccountID:{$accountID}\t\tSiteID:{$siteID}\t\tMessage: Success\r\n");
	  			
	  			
	  			// ========== 推送SKU ===========
	  			$pushskus[] = $sku;
	  			if($variationSkus){
	  				$variationSkus = array_keys($variationSkus);
	  				$pushskus = array_merge($pushskus, $variationSkus);
	  			}
	  			$url = Yii::app()->request->hostInfo."/ebay/ebayproductadd/pushskutoimg/account_id/{$accountID}/sku/".implode(",", $pushskus)."/site_id/{$siteID}/status/0";
	  			MHelper::runThreadBySocket ( $url );
	  			// ========= 推送END ==========
	  			
	  		}catch (Exception $e){
	  			if($dbtransaction){
	  				$dbtransaction->rollback();
	  			}
	  			throw new Exception($e->getMessage());
	  		}
	  		
    		// =============== END入库操作 ============== //
    	}catch (Exception $e){
    		$this->setErrorMessage($e->getMessage());
    		//MHelper::writefilelog("ebay/ebayBatchAdd-".$accountID.".log", date("Y-m-d H:i:s") . "\t\tSKU:{$sku}\t\tAccountID:{$accountID}\t\tSiteID:{$siteID}\t\tMessage:" . $e->getMessage()."\r\n");
		    return false;		
    	}
    	return true;
    }

    /**
     * @desc 自动添加对应的拍卖产品
     * @param unknown $addID
     * @throws Exception
     * @return boolean
     */
    public function autoAddProduct($addID){
    	try{
	    	$addInfo = $this->findByPk($addID);
	    	if(empty($addInfo)){
	    		$this->throwE("Not Find the Add ID");
	    	}
	    	//判断该刊登是否为拍卖模式和是否已经刊登成功上传过
	    	if($addInfo['listing_type'] != self::LISTING_TYPE_AUCTION || $addInfo['status'] != self::STATUS_SUCCESS){
	    		$this->throwE("Not match auction auto add rule");
	    	}
	    	//判断该SKU是否为单品
	    	$skuInfo = Product::model()->getProductInfoBySku($addInfo['sku']);
	    	if(!$skuInfo){
	    		$this->throwE("Not find the sku");
	    	}
	    	if($skuInfo['product_is_multi'] == Product::PRODUCT_MULTIPLE_MAIN){
	    		$this->throwE("Variation product not allow");
	    	}
	    	//获取组装标题
	    	$title = $addInfo['title'];   //已经在入库时加了账号前后缀,EbayProductDescriptionTemplate::model()->getTitle($addInfo['title'], $addInfo['account_id'], $addInfo['site_id']);

	    	$addData = $addInfo;
	    	$nowtime = date("Y-m-d H:i:s");
	    	$encryptSku = new encryptSku();
	    	$addData['item_id'] = 0;
	    	$addData['id'] = 0;
	    	$addData['upload_msg'] = "";
	    	$addData['uuid'] = "";
	    	$addData['last_response_time'] = $nowtime;
	    	$addData['status'] = self::STATUS_PENDING;
	    	$addData['create_time'] = $addData['update_time'] = $nowtime;
	    	$addData['seller_sku'] = $encryptSku->getEncryptSku($addData['sku']);
	    	//添加主表数据
	    	$newAddID = $this->saveProductAddData($addData);
	    	if(!$newAddID)
	    		$this->throwE("add product info failure");
	    	//添加运输信息
	    	$shippInfo = EbayProductAddShipping::model()->getProductShippingInfoByAddID($addID);
	    	if($shippInfo){
	    		foreach ($shippInfo as $shipping){
	    			unset($shipping['id']);
	    			$shipping['add_id'] = $newAddID;
	    			$res = EbayProductAddShipping::model()->saveData($shipping);
	    			if(!$res) $this->throwE("shipping info save failure");
	    		}
	    	}
	    	//添加属性值
	    	$specificsInfo = EbayProductAddSpecific::model()->getEbayProductAddSpecificByAddID($addID);
	    	if($specificsInfo){
	    		foreach ($specificsInfo as $specifics){
	    			unset($specifics['id']);
	    			$specifics['add_id'] = $newAddID;
	    			$res = EbayProductAddSpecific::model()->saveProductSpecificData($specifics);
	    			if(!$res) $this->throwE("specifics save failure");
	    		}
	    	}
	    	
	    	//推送SKU
	    	$pushskus[] = $addData['sku'];
	    	$url = Yii::app()->request->hostInfo."/ebay/ebayproductadd/pushskutoimg/account_id/{$addData['account_id']}/sku/".implode(",", $pushskus)."/site_id/{$addData['site_id']}/status/0";
	    	MHelper::runThreadBySocket ( $url );
	    	
	    	return $newAddID;
    	}catch (Exception $e){
    		throw new Exception($e->getMessage());
    		//return false;
    	}
    }
    
    /**
     * @desc 重置超时上传的状态
     * @param unknown $accountId
     * @param string $siteId
     * @return Ambigous <number, boolean>
     */
    public function recoverUploadingProductToFailure($accountId, $siteId = null){
    	$beforeTime = date("Y-m-d H:i:s", time()-1*3600);//一个小时
    	$conditions = "account_id='{$accountId}' and update_time<'{$beforeTime}' and status=".self::STATUS_OPERATING;
    	if($siteId !== null){
    		$conditions .= " and site_id='{$siteId}'";
    	}
    	return $this->getDbConnection()->createCommand()->update($this->tableName(), array('status'=>self::STATUS_FAILURE, 'upload_msg'=>'上传超时'), $conditions);
    }
    
    /**
     * @desc 上传产品数据
     * @param unknown $addID
     * @return boolean
     */
    public function uploadProductData($addID, $type = 0){//$type 1 为手动上传
		$userID = (int)Yii::app()->user->id;
    	try{
    		$addInfo = $this->findByPk($addID);
            if(empty($addInfo)) $this->throwE("Not Find the Add ID",111);
            $addType = $addInfo['add_type'];
            $currency = $addInfo['currency'];
    		if($addInfo['status'] == self::STATUS_OPERATING){
    			//检测是否超过一个小时,超过则放行
    			if($addInfo['update_time'] > date("Y-m-d H:i:s", time()-1*3600)){
    				$this->setErrorMessage("This SKU Already in the upload");
    				return false;
    			}
    		}
    		if($addInfo['item_id']>0){
    			$this->setErrorMessage("This SKU Already in the upload");
    			return false;
    		}
    		//非拍卖产品再次检验此SKU是否已经刊登在相同的站点了
    		if($addInfo['listing_type'] != EbayProductAdd::LISTING_TYPE_AUCTION){
	    		$checkIsExists = EbayProduct::model()->find("site_id={$addInfo['site_id']} AND account_id={$addInfo['account_id']} AND sku='{$addInfo['sku']}' and item_status=1 and listing_type <>'chinese'");
	    		if($checkIsExists && $checkIsExists['item_status'] == 1){
	    			$this->throwE(Yii::t("ebay", "SKU has Upload in Product Listing"),112);
	    		}
	    		//检测是否在刊登记录里面
	    		$filterStatus = array(self::STATUS_OPERATING, self::STATUS_SUCCESS);
	    		$checkIsExists2 = $this->find("site_id='{$addInfo['site_id']}' AND account_id='{$addInfo['account_id']}' and sku='{$addInfo['sku']}' and status in (" . MHelper::simplode($filterStatus) . ") and id<>{$addID} and listing_type <> ".EbayProductAdd::LISTING_TYPE_AUCTION);
	    		if(($checkIsExists2 && $checkIsExists2['status'] == self::STATUS_OPERATING)){
	    			$this->throwE(Yii::t("ebay", "SKU has Upload in Product Listing"),113);
	    		}/* elseif($checkIsExists2['status'] == self::STATUS_SUCCESS){
	    			$checkIsExists = EbayProduct::model()->find("site_id={$addInfo['site_id']} AND account_id={$addInfo['account_id']} AND sku='{$addInfo['sku']}' and item_status=0");
	    			if(!$checkIsExists){
	    				
	    			}
	    		} */
	    		
	    		$checkIsExists = EbayProduct::model()->find("site_id={$addInfo['site_id']} AND account_id={$addInfo['account_id']} AND sku='{$addInfo['sku']}' and item_status=0 and listing_type <>'chinese'");
	    		if ($checkIsExists2 && $checkIsExists2['status'] == self::STATUS_SUCCESS && !$checkIsExists){
	    			$this->throwE(Yii::t("ebay", "SKU has Upload in Product Listing"),114);
	    		}
    		}
    		
    		//断货判断,针对深圳本地仓，排除海外仓账号
    		$skuInfo = Product::model()->getProductInfoBySku($addInfo['sku']);
    		if(empty($skuInfo) || ( !in_array($addInfo['account_id'],EbayAccount::$OVERSEAS_ACCOUNT_ID) && $skuInfo['product_status'] == Product::STATUS_STOP_SELLING )) {
                $this->throwE(Yii::t("ebay", "这个sku已停售"),115);//This SKU has off Selling
            }
			//海外仓帐号已停售产品判断仓库是否有库存
			if(in_array($addInfo['account_id'],EbayAccount::$OVERSEAS_ACCOUNT_ID) && $skuInfo['product_status'] == Product::STATUS_STOP_SELLING){
				$wareHouseID = EbayAccountSite::model()->getWarehouseByAccountSite($addInfo['account_id'], $addInfo['site_id']);
				if(!$wareHouseID){
					$this->throwE(Yii::t("ebay", "没有找到对应的仓库"),115);
				}
				$quantity = WarehouseSkuMap::model()->getAvailableBySkuAndWarehouse($addInfo['sku'],$wareHouseID);
				if($quantity<=0){
					$this->throwE(Yii::t("ebay", "可用库存小于0"),115);
				}
			}

    		//侵权判断
    		if(ProductInfringe::model()->getProductIfInfringe($addInfo['sku'])) {
    			$this->throwE(Yii::t("ebay", "The SKU has been infringed, can not be uploaded to EBAY"),116);
    		}

    		//获取产品库中的子SKU列表
    		$variationSkuOriList = EbayProductAdd::model()->getSubSKUListPairsByMainProductId($skuInfo['id']);
    		
    		//多属性不能拍卖
    		if($addInfo['listing_type'] == EbayProductAdd::LISTING_TYPE_AUCTION && $skuInfo['product_is_multi'] == Product::PRODUCT_MULTIPLE_MAIN && $variationSkuOriList) { 
    			$this->throwE(Yii::t("ebay", "This SKU is multi Product, Not allow to Auction"),117);
    		}

            //
            $variationSKUList = $diffVariationList = $variationSkuAddList = array();
            if($addInfo['listing_type'] != self::LISTING_TYPE_AUCTION){
                $variationSKUList = EbayProductAddVariation::model()->getEbayProductAddVariationJoinEbayProductAddByAddID($addInfo['id']);//取刊登多属性表记录
                $variationSkuAddList = EbayProductAdd::model()->getSubSKUListPairsByAddId($addInfo['id']);//取出组合的子sku
                $diffVariationList = array_diff($variationSkuAddList, $variationSkuOriList);//取差集
            } else {//拍卖的话，检测是否存在拍卖价高于当前刊登的产品
                $listingInfo = EbayProduct::model()->getDbConnection()
                                                    ->createCommand()
                                                    ->from(EbayProduct::model()->tableName())
                                                    ->where("site_id={$addInfo['site_id']} AND sku='{$addInfo['sku']}' AND listing_duration='GTC'")
                                                    ->order("buyitnow_price asc")
                                                    ->queryRow();
                if($listingInfo && floatval($listingInfo['buyitnow_price'])> 0 && floatval($listingInfo['buyitnow_price']) < $addInfo['start_price']){
                    $this->throwE(Yii::t('ebay', 'Auction Price Too High'),118);
                }
            } 

            //如果是小语种刊登，深圳光明本地仓可用库存大于5, add in 20160809
            // $notSatisfiedArr = array();
            // if ($addType == 2) {
                // if (Product::PRODUCT_MULTIPLE_MAIN == $skuInfo['product_is_multi'] && !empty($variationSkuAddList)) {
                //     $stockInfos = WarehouseSkuMap::model()->getListByCondition("sku,available_qty","warehouse_id=41 and sku in('".implode("','",$variationSkuAddList)."')");
                // } else {
                //     $stockInfos = WarehouseSkuMap::model()->getListByCondition("sku,available_qty","warehouse_id=41 and sku='{$addInfo['sku']}'");
                // }

                // //MHelper::writefilelog('stock.txt',json_encode($stockInfos)."\r\n");
                // if (empty($stockInfos)) {
                //     $this->throwE($addInfo['sku'].Yii::t('ebay', 'StockInfo is not exist'));
                // }

                // foreach ($stockInfos as $info) {
                //     if ($info['available_qty'] < 5) {
                //         if( Product::PRODUCT_MULTIPLE_MAIN == $skuInfo['product_is_multi'] && !empty($variationSkuAddList) ) {
                //             $notSatisfiedArr[] = $info['sku'];
                //         } else {
                //             $this->throwE($info['sku'].Yii::t('ebay', 'Available Stock is not satisfied'));
                //         }
                //     }
                // }
                // //如果是多属性刊登，可用库存全小于5，不刊登
                // if( Product::PRODUCT_MULTIPLE_MAIN == $skuInfo['product_is_multi'] && !empty($variationSkuAddList) && count($variationSkuAddList) == count($notSatisfiedArr) ) {
                //     $this->throwE($addInfo['sku'].' 的所有子sku不满足刊登条件，库存都小于5 ');
                // }
            // }

    		//查出账号站点所对应的仓库
    		$wareHouseID = EbayAccountSite::model()->getWarehouseByAccountSite($addInfo['account_id'], $addInfo['site_id']);

    		//获取属性模板
    		$attributeInfo = EbayProductAttributeTemplate::model()->getProductAttributeTemplage("site_id='".$addInfo['site_id']."' AND abroad_warehouse = '".$wareHouseID."' AND config_type = '".(int)$addInfo['config_type']."'");

    		//检测是否有模板
    		if(empty($attributeInfo)){
    			$this->throwE(Yii::t("ebay", "Not Find the Attribute Template!!"),119);
    		}

    		//@note 这个为了促进清库而做的现在应该不要了，默认设置为5天 add by lihy 2016-05-03
    		/**********************滞销品拍卖特殊处理 START***************************/
    		/* if( $addInfo['listing_type'] == self::LISTING_TYPE_AUCTION){
    			if( $addInfo['auction_rule'] == self::AUCTION_PROFIT_SHIPCOST){//如果是成本同步到运费
    				$attributeInfo['listing_duration_auction'] = 'Days_5';
    			}elseif($addInfo['auction_rule']==0){
    				$attributeInfo['listing_duration_auction'] = 'Days_3';
    			}
    		} */
    		/**********************滞销品拍卖特殊处理 END***************************/

    		if( $addInfo['listing_type'] == self::LISTING_TYPE_AUCTION){
    			$listingDuration = !empty($addInfo['listing_duration']) ? $addInfo['listing_duration'] : 'Days_5';
    		}else{
    			$listingDuration = !empty($addInfo['listing_duration']) ? $addInfo['listing_duration'] : 'GTC';
    		}
    		
            //正常刊登，检查上架时间是否有效
    		if($addInfo['listing_type'] == self::LISTING_TYPE_AUCTION){
    			$auctionTime = EbayProductAddAuction::model()
    									->getDbConnection()
    									->createCommand()
    									->from(EbayProductAddAuction::model()->tableName())
                                        ->where("add_id=".$addID)
                                        ->queryRow();
    			if($auctionTime){
    				if(date("Y-m-d H:i:s") < $auctionTime['start_time']){
    					$this->throwE(Yii::t("ebay", "Auction Start time shoul't Bigger than Now"),120);
    				}
    			}
    		}
    		
    		//获取分类
    		$categoryName = EbayCategory::model()->getCategoryNameByID($addInfo['category_id'], $addInfo['site_id']);
    		if(empty($categoryName)) $this->throwE(Yii::t("ebay", "Not Find Category Info"),121);

    		//获取当前账号信息
    		$accountInfo = EbayAccount::model()->getAccountInfoById($addInfo['account_id']);

    		//生成uuid
    		if(empty($addInfo['uuid'])){
    			$UUID = md5($addInfo['id'].$accountInfo['user_name'].$addInfo['site_id'].time());
    		}else {
    			$UUID = $addInfo['uuid'];
    		}

    		
    		//======================== 图片上传 ========================
    		//@todo 如果需要使用自己上传图片的话，请迁移本段代码到更新标记上传中下面去！！！！！！
    		$pass_start = time();
    		$uploadCount = 10;
    		$ebayProductImageAddModel = new EbayProductImageAdd();
    		//addType为2：表示小语种批量刊登
    		//@todo 这里后续的话去除只有addtype 等于2的限制
    		$res1 = false;
    		//if ($addType == 2) {
    		$res1 = $ebayProductImageAddModel->sendImageUploadRequest($addInfo['sku'], $addInfo['account_id'], $addInfo['site_id']);
    		if($addInfo['listing_type'] != self::LISTING_TYPE_AUCTION && $variationSKUList){
    			foreach ($variationSKUList as $variationSKU){
    				$res = $ebayProductImageAddModel->sendImageUploadRequest($variationSKU['son_sku'], $addInfo['account_id'], $addInfo['site_id']);
    				$res1 &= $res;
    			}
    		}


    		/* } */ 
    		//2017-02-11注释掉
    		/* if(!$res1 && $type == 1) {//手动上传
    		 	$updateData = array(
    		 			'uuid'			=>	$UUID,
    		 			'status'		=>	self::STATUS_OPERATING,
    		 			'update_time'	=>	date("Y-m-d H:i:s")
    		 	);
    		 	$res = $this->getDbConnection()->createCommand()->update($this->tableName(), $updateData, "id={$addInfo['id']}");
    		 	if(!$res)	$this->throwE(Yii::t("ebay", "Update Product Add Info Failure"),122);
    		 	$res2 = false;
    		 	$ebayProductImageAddModel2 = new EbayProductImageAdd();
	    		while($uploadCount--){
	    			$res2 = $ebayProductImageAddModel2->uploadImageOnline($addInfo['sku'], $addInfo['account_id'], $addInfo['site_id']);
		    		if($addInfo['listing_type'] != self::LISTING_TYPE_AUCTION && $variationSKUList){
		    			foreach ($variationSKUList as $variationSKU){
		    				$res = $ebayProductImageAddModel2->uploadImageOnline($variationSKU['son_sku'], $addInfo['account_id'], $addInfo['site_id']);
		    				$res2 &= $res;
		    			}
		    		}
		    		if($res2) break;
	    		}
	    		if(!$res2){
	    			$this->throwE($ebayProductImageAddModel->getErrorMessage(), '100');
	    		}
	    		$res1 = $res2;
	    		unset($ebayProductImageAddModel2);
    		} */
    		if(!$res1){
    			$pushSKUs = array();
    			$pushSKUs[] = $addInfo['sku'];
    			if($variationSKUList){
    				foreach ($variationSKUList as $variationSKU){
    					$pushSKUs[] = $variationSKU['son_sku'];
    				}
    			}
    			//紧急推送
    			$ebayProductImageAddModel->addSkuImageUpload($addInfo['account_id'], $pushSKUs, 0);
    			//$this->throwE($ebayProductImageAddModel->getErrorMessage());
				$this->throwE($ebayProductImageAddModel->getErrorMessage(),100);
    			//$this->throwE("图片正在后台上传处理中，请过几分钟再上传操作...",100);//code不要改
    			//$this->setErrorMessage("图片正在后台上传处理中，请过几分钟再上传操作...");
    			//return false;
    		}
    		$pass_end = time();
    		if ($addType==2 ) {
    			//MHelper::writefilelog('timepass_new3.txt', $addInfo['sku'].' 图片上传 pass: '. ($pass_end-$pass_start)."\r\n" );
    		}
    		//=========================图片上传end=============

    		//更新标记上传中
    		$updateData = array(
    							'uuid'			=>	$UUID,
    							'status'		=>	self::STATUS_OPERATING,
    							'update_time'	=>	date("Y-m-d H:i:s")
    						);
    		$res = $this->getDbConnection()->createCommand()->update($this->tableName(), $updateData, "id={$addInfo['id']}");
    		if(!$res)	$this->throwE(Yii::t("ebay", "Update Product Add Info Failure"),123);



    		//找出对应的最小运费
    		$ebayProductAddShipping = new EbayProductAddShipping();
    		$minShipCost = $ebayProductAddShipping->getMiniShipingCostByAddID($addID);
    		
    		//利润判断
            $pass_start = time();
    		$message = "";
    		$result = true;
			$profitMsg = "";
			$profitRes = true;
			//获取最低利润率
			$profitWhere = "platform_code = :platform_code and profit_calculate_type = :profit_calculate_type";
			$profitParam = array(':platform_code' => Platform::CODE_EBAY,':profit_calculate_type' => EbayProductAdd::AUCTION_PROFIT_SALEPRICE);
			$profitConfInfo = SalePriceScheme::model()->getSalePriceSchemeByWhere($profitWhere,$profitParam);
    		$ebayProductSalePriceConfig = new EbayProductSalePriceConfig();
    		if($addInfo['listing_type'] == self::LISTING_TYPE_VARIATION){//如果是多属性
    			if(empty($variationSKUList)){
    				$this->throwE(Yii::t("ebay", "Not Find the SKU's Variations"),124);
    			}else{
    				$message .= '';
    				foreach($variationSKUList as $item){
    					$variationSkuInfo = Product::model()->getProductBySku($item['son_sku']);
    					if(!$variationSkuInfo || $variationSkuInfo['product_status'] == Product::STATUS_HAS_UNSALABLE || $variationSkuInfo['product_status'] == Product::STATUS_WAIT_CLEARANCE){//为滞销产品,不检查利润
    						$result = $result && true;
    					}else{
    						$ebayProductSalePriceConfig = new EbayProductSalePriceConfig();
    						$profitInfo = $ebayProductSalePriceConfig->getProfitInfo($item['variation_price'], $item['son_sku'], $currency, $addInfo['site_id'], $addInfo['account_id'], $categoryName, $minShipCost);
    						//$profitInfo = EbayProductSalePriceConfig::model()->getProfitInfo($item['variation_price'], $item['son_sku'], $currency, $addInfo['site_id'], $addInfo['account_id'], $categoryName, $minShipCost);
    						if(isset($_REQUEST['bug'])){
    							var_dump($profitInfo);
    						}
    						if(floatval($profitInfo['profit'])<=0){
    							$message .= $item['son_sku'].'利润为：'.$profitInfo['profit'].'不能刊登!';
    							$result = $result && false;
    						}else{
    							$result = $result && true;
    						}
							if($profitConfInfo){
								if($profitInfo['profit'] < $profitConfInfo['lowest_profit_rate']){
									$profitMsg .= $item['son_sku'].'利润率过低';
									$profitRes = $profitRes && false;
								}else{
									$profitRes = $profitRes && true;
								}
							}
    					}
    		
    				}
    				
    			}
    		}else{
    			if($addInfo['listing_type'] == self::LISTING_TYPE_AUCTION){//拍卖不限制初始利润
    				$result = $result && true;
    			}else{
    				//滞销产品不进行利润检测
    				if($skuInfo['product_status'] == Product::STATUS_HAS_UNSALABLE || $skuInfo['product_status'] == Product::STATUS_WAIT_CLEARANCE){//为滞销产品,不检查利润
    					$result = $result && true;
    				}else{
    					$ebayProductSalePriceConfig = new EbayProductSalePriceConfig();
    					$profitInfo = $ebayProductSalePriceConfig->getProfitInfo($addInfo['start_price'], $addInfo['sku'], $currency, $addInfo['site_id'], $addInfo['account_id'], $categoryName, $minShipCost);
    					//$profitInfo = EbayProductSalePriceConfig::model()->getProfitInfo($addInfo['start_price'], $addInfo['sku'], $currency, $addInfo['site_id'], $addInfo['account_id'], $categoryName, $minShipCost);
    					if(isset($_REQUEST['bug'])){
    						var_dump($profitInfo);
    					}
    					if(floatval($profitInfo['profit'])<=0){
    						$message .= '利润为：'.$profitInfo['profit'].'不能刊登!';
    						$result = $result && false;
    					}else{
    						$result = $result && true;
    					}
						if($profitConfInfo){
							if($profitInfo['profit'] < $profitConfInfo['lowest_profit_rate']){
								$profitMsg .= '利润率过低';
								$profitRes = $profitRes && false;
							}else{
								$profitRes = $profitRes && true;
							}
						}
    				}
    			}
    		}
            $pass_end = time();
            if ($addType==2) {
                //MHelper::writefilelog('timepass_new3.txt', $addInfo['sku'].' 利润判断 pass: '. ($pass_end-$pass_start)."\r\n" );
            }
    		if(!$result){
    			$this->throwE($message,125);	
    		}
			if(!$profitRes){
				$this->throwE($profitMsg,125);
			}

    		$ebaySiteModel = new EbaySite;
    		$realCurrency = ($addInfo['site_id']==71 || $addInfo['site_id'] == 186) ? 'EUR' : $currency;
    		$language = $ebaySiteModel->getLanguageBySiteIDs($addInfo['site_id']);
    		//检测是否包含有included
    		$descInfo = Productdesc::model()->getDescriptionInfoBySkuAndLanguageCode($addInfo['sku'], $language);
    		if(empty($descInfo) || empty($descInfo['included'])){
    			$this->throwE(Yii::t("ebay", "Not Find Included"),126);
    		}
    		//判断是否为ebay motor分类
    		$categorySiteID = EbayCategory::model()->getCategorySiteId($addInfo['site_id'], $addInfo['category_id']);
    		$siteName = $ebaySiteModel->getSiteName($addInfo['site_id']);
    		//如果分类是ebay motors 则需要指定Item.Site to eBayMotors
    		//并且siteId = 100
    		$sid = $categorySiteID ? $categorySiteID : $addInfo['site_id'];
    		if($sid == $ebaySiteModel::EBAY_MOTOR_SITEID){
    			$siteName = "eBayMotors";
    		}
    		$title = $addInfo['title'];   //已经在入库时加了账号前后缀, EbayProductDescriptionTemplate::model()->getTitle($addInfo['title'], $addInfo['account_id'], $addInfo['site_id']);
    		//组装上传数据
    		$xmlgenerator = new XmlGenerator;
    		$itemInfo = array(
    				'PrimaryCategory' => array(
    						'CategoryID' => $addInfo['category_id'],
    				),
    				'SecondaryCategory'	=>	array(
    						'CategoryID'	=>	$addInfo['category_id2']
    				),
    				
    				'Title' => "<![CDATA[" . $title . "]]>",
    				'Currency' => $realCurrency,
    				'Location' => $attributeInfo['location'],
    				'Country' => $attributeInfo['country'],
    				
    				'PaymentMethods' => 'PayPal',
    				
    				'Site' => $siteName,
    				'SKU' => $addInfo['seller_sku'],
    				
    				'ListingDuration' => $listingDuration,
    				'DispatchTimeMax' => $attributeInfo['dispatch_time_max'],
    				
    				'ReturnPolicy' => array(
    						'RefundOption' => $attributeInfo['refund_option'],
    						'ReturnsAcceptedOption' => $attributeInfo['returns_accepted_option'],
    						'ReturnsWithinOption' => $attributeInfo['returns_within_option'],
    						'ShippingCostPaidByOption' => $attributeInfo['shipping_cost_option'],
    						'Description' => $attributeInfo['return_description'],
    				),
    				
    				'UUID' => $UUID,
    				'HitCounter'	=>	'HiddenStyle'
    		);

            //店铺分类,add by yangshihui
            $storeCategoryName = EbayStoreCategory::model()->getCategoryNameByID($addInfo['store_category_id'], $addInfo['account_id']);
            if ($storeCategoryName != '') {
                $itemInfo['Storefront']['StoreCategoryID'] = $addInfo['store_category_id'];
                $itemInfo['Storefront']['StoreCategoryName'] = "<![CDATA[{$storeCategoryName}]]>";
            }

            if($addInfo['store_category_id2']){//这里表示二级分类，不是表示第二个店铺ID
            	$storeCategoryName = EbayStoreCategory::model()->getCategoryNameByID($addInfo['store_category_id2'], $addInfo['account_id']);
            	if ($storeCategoryName != '') {
            		/* $itemInfo['Storefront']['StoreCategory2ID'] = $addInfo['store_category_id2'];
            		$itemInfo['Storefront']['StoreCategory2Name'] = "<![CDATA[{$storeCategoryName}]]>"; */
            		$itemInfo['Storefront']['StoreCategoryID'] = $addInfo['store_category_id'];
            		$itemInfo['Storefront']['StoreCategoryName'] = "<![CDATA[{$storeCategoryName}]]>";
            	}
            }
           
    		$upcFlag = false;
    		$ebayCategoryInfoModel = new EbayCategoryInfo();
    		$defaultGtinVal = "Does not apply";
    		if($siteName == 'Spain'){
    			$defaultGtinVal = 'no aplicable';
    		}
    		$defaultGtinVal = $this->getDefaultGTIN($addInfo['site_id']);
    		$defaultBrandVal = 'Unbranded';
    		if( strpos($addInfo['errormsg'], "specified catalog product details and variations")===false ){
    			$feature = $ebayCategoryInfoModel->getCategoryFeatures($addInfo['account_id'], $addInfo['site_id'], $addInfo['category_id']);
    			//if( isset($feature['UPCEnabled']) && trim($feature['UPCEnabled'])=='Required' && $addInfo['listing_type'] != self::LISTING_TYPE_VARIATION){
                if( $addInfo['listing_type'] != self::LISTING_TYPE_VARIATION ){
    				$itemInfo['ProductListingDetails']['UPC'] = $addInfo['upc'] ? $addInfo['upc'] : $defaultGtinVal;

					if( isset($feature['EANEnabled']) && trim($feature['EANEnabled'])=='Required' ){
						$itemInfo['ProductListingDetails']['EAN'] = $addInfo['ean'] ? $addInfo['ean'] : $defaultGtinVal;
					}
					if( isset($feature['ISBNEnabled']) && trim($feature['ISBNEnabled'])=='Required' ){
						$itemInfo['ProductListingDetails']['ISBN'] = $addInfo['isbn'] ? $addInfo['isbn'] : $defaultGtinVal;
					}
					if( isset($feature['BrandMPN']) && trim($feature['BrandMPN'])=='true' ){
						$itemInfo['ProductListingDetails']['BrandMPN']['Brand'] = $addInfo['brand'] ? $addInfo['brand'] : $defaultBrandVal;
						$itemInfo['ProductListingDetails']['BrandMPN']['MPN'] = $addInfo['mpn'] ? $addInfo['mpn'] : $defaultGtinVal;
					}
    			}

    		}
    		if($addInfo['listing_type'] != self::LISTING_TYPE_AUCTION && $listingDuration == 'GTC'){
    			$itemInfo['OutOfStockControl'] = 'true';
    		}
    		//@note 我只是个搬运工 BY LIHY
    		if(!$itemInfo['ReturnPolicy']['RefundOption']){
    			unset($itemInfo['ReturnPolicy']['RefundOption']);
    		}
    			
    		if($addInfo['category_id2']){
    			$itemInfo['SecondaryCategory'] = array(
    					'CategoryID' => $addInfo['category_id2'],
    			);
    		}
    		if($addInfo['condition_id']>0){
    			$itemInfo['ConditionID'] = $addInfo['condition_id'];
    		}else{
    			$itemInfo['ConditionID'] = 1000;
    		}
    		$currencyXml =  array('name'=>'currencyID', 'value'=>$realCurrency);//货币需要设置的属+性值
    		
    		$saleprice = 0;
    		$addQty = $accountInfo['add_qty'];//@TODO 账号对应的添加额度
    		if($addQty == 0) $addQty = 5;
    		if($addInfo['listing_type']	==	self::LISTING_TYPE_AUCTION){
    			$itemInfo['Quantity'] = '1';
    			$itemInfo['ListingType'] = 'Chinese';
    		}else{
    			if($addInfo['listing_type'] != self::LISTING_TYPE_VARIATION){
    				$itemInfo['Quantity'] = $addQty;
    			}
    			$itemInfo['ListingType'] = 'FixedPriceItem';
    		}
    		
    		//描述
    		if($addInfo['detail']==''){
    			$this->throwE(Yii::t("ebay", "刊登描述内容为空，重新编辑"),127);
    		}
    		//$description = $this->getDescription($addInfo['sku'], $addInfo['account_id'], $addInfo['site_id'], $language, $addInfo['listing_type'], false);
    		$description = $this->getDescriptionOnlyImg($title, $addInfo['detail'], $addInfo['sku'], $addInfo['account_id'], $addInfo['site_id'], $diffVariationList);
    		if($description==''){
    			$this->throwE(Yii::t("ebay", "Not match description template"),128);
    		}
    		$description = '<![CDATA['.$description.']]>';
    		$itemInfo['Description'] = $description;
    		
    		//图片
    		$ztimages = $ebayProductImageAddModel->getRemoteImageList($addInfo['sku'], $addInfo['account_id'], EbayProductImageAdd::IMAGE_ZT, $addInfo['site_id']);
    		$ftimages = $ebayProductImageAddModel->getRemoteImageList($addInfo['sku'], $addInfo['account_id'], EbayProductImageAdd::IMAGE_FT, $addInfo['site_id']);
    		$PictureURL = isset($ztimages[0]) ? $ztimages[0] : (isset($ftimages[0]) ? $ftimages[0] : '');
    		if(empty($ztimages)){
    			$ztimages = array_slice($ftimages, 0, 12);
    		} else {
                $ztimages = array_slice($ztimages, 0, 12);
            }
    		if($ebaySiteModel->getMoreZtSite($addInfo['site_id'])){//可以放多张主图
    			$itemInfo['PictureDetails'] = array(
    					'PhotoDisplay'	=>	'PicturePack',
    					'PictureSource'	=>	'EPS',
    					'PictureURL'	=>	$ztimages
    			);
    		}else{
    			$itemInfo['PictureDetails'] = array(
    					'ExternalPictureURL' => $PictureURL,
    					'PictureURL' => $PictureURL,
    			);
    		}
    		//检测价格是否低于最小价格
    		$minPrice = (int)$ebayProductSalePriceConfig->getMinSaleprice($currency);
    		$newvariationAttributeKeys = array();
    		if($addInfo['listing_type'] != self::LISTING_TYPE_VARIATION){
    			//检测价格是否低于最小价格
				if($addInfo['listing_type'] != self::LISTING_TYPE_AUCTION){ //不等于拍卖限制
					if($addInfo['start_price'] < $minPrice){
						$this->throwE("{$addInfo['sku']}:SKU价格低于最小价：{$minPrice}",129);
					}
				}
    			$itemInfo['StartPrice'] = $saleprice = $addInfo['start_price'];
				//折后价
				if($addInfo['discount_price'] > 0){
					$itemInfo['DiscountPriceInfo'] = array(
						'OriginalRetailPrice'	=>	$addInfo['discount_price'],
						'PricingTreatment'	=>	'STP',
						'SoldOneBay'	=>	'true',
					);
				}
    		}else{//多属性
    			/* $itemInfo['PictureDetails'] = array(
    					'ExternalPictureURL' => $PictureURL,
    					'PictureURL' => $PictureURL,
    			); */
    			//ebay类别属性
    			$catSpec = $ebayCategoryInfoModel->getCategorySpecifics($addInfo['account_id'], $addInfo['site_id'], $addInfo['category_id']);
    			$categorySpec = array();
    			foreach($catSpec as $spec){
    				$categorySpec[strtolower($spec->Name)] = $spec->Name;
    			}
    			$catKeys = array_keys($categorySpec);//ebay类别属性小写，用于匹配保存的属性名
    			
    			//查出保存的多样性属性
    			$ebayProductAddVariationValModel = new EbayProductAddVariationAttribute();
    			$conditions = "a.add_id='{$addID}'";
    			$variationAttribute = $ebayProductAddVariationValModel->getVariationAttributeJoinVariationByWhere($conditions);
    			$gtinkeys = array_merge($catKeys, array('GTIN', 'EAN', 'UPC', 'ISBN'));
    			
    			//组合数据
    			/*多样性数据*/
    			$nameValueList = array();
    			$newvariationAttribute = array();
    			
    			foreach($variationAttribute as $key=>$val){
    				$newvariationAttribute[$val['name']][] = $val;
    				if(!in_array(strtoupper($val['name']), $gtinkeys)){
    					$newvariationAttributeKeys[] = strtoupper($val['name']);
    				}
    			}
    			foreach($newvariationAttribute as $key=>$val){
    				if(in_array(strtoupper($key), $gtinkeys)) continue;
    				$value_arr = array();
    				foreach($val as $v){
    					$value_arr[] = '<![CDATA['.trim($v['value']).']]>';
    				}
    				$value_arr = array_unique($value_arr);
    				$nameValueList['NameValueList'][] = array(
    																'Name'	=>	in_array($key,$catKeys) ? '<![CDATA['.trim($categorySpec[$key]).']]>' : '<![CDATA['.trim($key).']]>',
    																'Value'	=>	$value_arr
    													);
    				
    			}
    			$variations = array('VariationSpecificsSet'=>$nameValueList);
    		
    			$newVariationAttribute = array();
    			foreach($variationAttribute as $value){
    				$newVariationAttribute[$value['son_sku']][] = $value;
    			}
    			$picval = array();
    			$detailInfo = array();
    			foreach($newVariationAttribute as $sonSKU=>$itm){
    				$dtl = array();
    				$variationProductListingDetails = array();// lihy add 2016-01-27
    				$variationPrice = 0;
    				$variationSellerSku = '';
    				$variationSysSku = '';
					$variationDiscountPrice = 0;
    				foreach($itm as $val){
    					$val['name'] = trim($val['name']);
    					if(!$variationPrice)
    						$variationPrice = $val['variation_price'];
    					if(!$variationSellerSku)
    						$variationSellerSku = $val['son_seller_sku'];
    					if(!$variationSysSku){
    						$variationSysSku = $val['son_sku'];
    					}
						if(!$variationDiscountPrice)
							$variationDiscountPrice = $val['variation_discount_price'];
    					if(in_array(strtoupper($val['name']), $gtinkeys)){// lihy add 2016-01-27
    						$variationProductListingDetails[$val['name']] = $val['value'];
    						continue;
    					}
    					$dtl[] = array(
    							'Name' => in_array($val['name'],$catKeys) ? '<![CDATA['.trim($categorySpec[$val['name']]).']]>' : '<![CDATA['.$val['name'].']]>',//若ebay类别属性中已有，读取ebay类别属性
    							'Value' => '<![CDATA['.trim($val['value']).']]>',
    					);
    					if($addInfo['variation_picture_specific'] == $val['name']){//判断属性名为多属性图片
    						$picval[$sonSKU] = $val['value'];//图片属性值
    					}
    					//@todo 应该调整为最大值
    					if($val['variation_price'] > (int)$saleprice){
							$saleprice = $startprice = $val['variation_price'];
						}

    				}
    				if(empty($variationPrice) || empty($variationSellerSku)){
    					$this->throwE("{$variationSysSku}:子SKU价格或在线SKU不存在",130);
    				}

    				//检测价格是否低于最小价格
    				if($variationPrice < $minPrice){
    					$this->throwE("{$variationSysSku}:子SKU价格低于最小价：{$minPrice}",131);
    				}
                    $variationAddQty = $addQty;
                    // if (in_array($sonSKU,$notSatisfiedArr)) {
                    //     $variationAddQty = 0;
                    // }

    				$temp = array(
    						'SKU'			=>	$variationSellerSku,
    						'StartPrice'	=>	$variationPrice,
    						'Quantity'		=>	$variationAddQty,
    						'VariationSpecifics'	=>	array("NameValueList"=>$dtl),
    				);

    				if($variationProductListingDetails){
    					$temp['VariationProductListingDetails'] = $variationProductListingDetails;
    				}
					//折后价
					if($variationDiscountPrice > 0){
						$temp['DiscountPriceInfo'] = array(
							'OriginalRetailPrice'	=>	$variationDiscountPrice,
							'PricingTreatment'	=>	'STP',
							'SoldOneBay'	=>	'true',
						);
					}
    				$detailInfo[] = $temp;
    					
    			}
    			$variations['Variation'] = $detailInfo;
    			
    			/*图片数据*/
    			$picvals = array();
    			$picDetail = array();
    			foreach($picval as $k=>$pic){
    				if(!in_array($pic,$picvals)){
    					$images = $ebayProductImageAddModel->getRemoteImageList($k, $addInfo['account_id'], ProductImageAdd::IMAGE_ZT, $addInfo['site_id']);
    					if($images){
    						
    						//过滤掉每个sku的小图
    						foreach ($images as $imgkey=>$image){
    							//$imageName = current(explode(".", end(explode("/", $image))));
    							
    							$imageName = end(explode("/", $image));
    							$imageName = substr($imageName, 0, strrpos($imageName, "."));
    							//$_1.JPG?  更换为原图
    							$image = str_replace('$_1.JPG?', '$_10.JPG?', $image);
    							$images[$imgkey] = $image;
    							if($imageName == $k){
    								unset($images[$imgkey]);
    							}
    							
    						}
    						if($ebaySiteModel->getMoreFtSite($addInfo['site_id'])){//可以放多张副图
    							$images = array_slice($images, 0, 12);
    						}else{
    							$images = array_slice($images, 0, 1);
    						}
	    					//$picUrl = $images[0];
	    					$picDetail[] = array(
	    							'VariationSpecificValue' => "<![CDATA[".$pic."]]>",
	    							//'PictureURL' => $picUrl,
	    							'PictureURL' 	=> 	$images,
	    					);
	    					$picvals[] = $pic;
    					}
    				}
    			}
    			if($picDetail){
    				$variations['Pictures'] = array(
    					'VariationSpecificName'			=>	in_array($addInfo['variation_picture_specific'], $catKeys) ? '<![CDATA['.$categorySpec[$addInfo['variation_picture_specific']].']]>' : '<![CDATA['.$addInfo['variation_picture_specific'].']]>',
    					'VariationSpecificPictureSet'	=>	$picDetail
    				);
    			}
    			$itemInfo['Variations'] = $variations;
    		}

			//paypal账号获取修改  2017-04-18
			$ebayAccountPayPalGroupModel = new EbayAccountPaypalGroup();
			$payPalEmail = $ebayAccountPayPalGroupModel->getEbayPaypal($addInfo['account_id'],$saleprice,$realCurrency);
			if($payPalEmail==false){
				$this->throwE($ebayAccountPayPalGroupModel->getErrorMessage(),132);
			}
			$itemInfo['PayPalEmailAddress'] = $payPalEmail;
    		//...
    		//paypal账号
    		/*$itemInfo['PayPalEmailAddress'] = PaypalAccount::model()->getPaypalAccountBySalePrice($saleprice, $realCurrency);
    		//summer_z和winter_z的
    		if($addInfo['account_id']==68||$addInfo['account_id']==69){
    			$itemInfo['PayPalEmailAddress']="kexinzhang1995@outlook.com";
    		//newstarting和goodquality* 的
    		}else if($addInfo['account_id']==70||$addInfo['account_id']==72){
    			$itemInfo['PayPalEmailAddress']="wenbozeng@outlook.com";
    		}else if($addInfo['account_id']==73||$addInfo['account_id']==74){
				//long-store和there-shop的
				$itemInfo['PayPalEmailAddress']="xixiang6688@outlook.com";
    		}else if($addInfo['account_id']==75||$addInfo['account_id']==76){
				//xinjieqixie和xinjieyiliaoqixie的
				$itemInfo['PayPalEmailAddress']="xinjieyiliao@hotmail.com";
    		}else if($addInfo['account_id']==77||$addInfo['account_id']==78){
				//linranshangmao和xiangpengzou的
				$itemInfo['PayPalEmailAddress']="zoupeng666@hotmail.com";
    		}else if($addInfo['account_id']==79||$addInfo['account_id']==80){
				//huaguangguanli和huaguangshiye的
				$itemInfo['PayPalEmailAddress']="huaguangqiang@hotmail.com";
    		}else if($addInfo['account_id']==81||$addInfo['account_id']==82){
				//nanchangshangheng和shanghengjinshuai的
				$itemInfo['PayPalEmailAddress']="shanghengmaoyi@hotmail.com";
    		}else if($addInfo['account_id']==83||$addInfo['account_id']==84){
				//zhibuxinxi和zhibucaojie的
				$itemInfo['PayPalEmailAddress']="zhibucaoliang@outlook.com";
    		}else if($addInfo['account_id']==85||$addInfo['account_id']==86){
				//haozheshiye8和haozhelinke的
				$itemInfo['PayPalEmailAddress']="haozheshiye@hotmail.com";
    		}else if($addInfo['account_id']==87||$addInfo['account_id']==88){
				//gaoan-store和gaoanqiyun的
				$itemInfo['PayPalEmailAddress']="gaoanhong11@hotmail.com";
			}*/
    		//属性
    		$itemSpecifics = array();
    		$ebayProductAddSpecificModel = new EbayProductAddSpecific();
    		$nameValueList = $ebayProductAddSpecificModel->getEbayProductAddSpecificByAddID($addID);
    		foreach ($nameValueList as $value){
    			if(empty($value['name'])) continue;
    			if(in_array(strtoupper($value['name']), $newvariationAttributeKeys)){
    				continue;
    			}
    			$itemSpecifics['NameValueList'][] = array(
    											'Name'	=>	'<![CDATA['.$value['name'].']]>',
    											'Value'	=>	'<![CDATA['.$value['value'].']]>'
    								);
    		}
    		$itemInfo['ItemSpecifics'] = $itemSpecifics;
    		
    		//运输相关
    		$localPriority = $internationalPriority = 0;
    		$attributes = array(//运费中需要设置币货的所有属性
    				'ShippingServiceCost' => $currencyXml,
    				'ShippingServiceAdditionalCost' => $currencyXml
    		);
    		
    		$shippingXml = "";
    		
    		//国家屏蔽
    		if($addInfo['exclude_ship_code']){
    			$itemInfo['BuyerRequirementDetails']['ShipToRegistrationCountry'] = true;
    			$excludeShippLocation['ExcludeShipToLocation'] = explode(",", $addInfo['exclude_ship_code']);
    			$xmlgenerator->xml = "";
    			$shippingXml .= $xmlgenerator->buildXMLFilterMulti($excludeShippLocation, '', '')->getXml();
    		}
    		if($currency=='USD'){
    			$totalUSD = $saleprice;
    		}else{
    			$totalUSD = $saleprice * (CurrencyRate::model()->getRateToOther($currency));
    		}
    		//找到存在的运费信息
    		$shippingInfo = $ebayProductAddShipping->getProductShippingInfoByAddID($addID);
    		//查出对应的shipping配置
    		if(empty($shippingInfo)){
    			$ebayProductShippingTemplateModel = new EbayProductShippingTemplate;
    			$shippingInfo = $ebayProductShippingTemplateModel->getDbConnection()->createCommand()
								    		->from($ebayProductShippingTemplateModel->tableName())
								    		->select("site_id,shipping_type,shipping_service,cost_type,additional_cost,priority,ship_location")
								    		->where("pid='{$attributeInfo['id']}'")
								    		->queryAll();
    		}
    		foreach ($shippingInfo as $value){
    			if($value['shipping_type'] == 1){
    				$localPriority++;  				
    				
    				$shippingcost = isset($value['ship_cost']) ? $value['ship_cost'] : 0;
    				$shippingcostAdd = isset($value['additional_ship_cost']) ? $value['additional_ship_cost'] : 0;
					//$shippingService = (($currency=='USD' && $shippingcost==0 && $totalUSD < 5) || $addInfo['listing_type'] == self::LISTING_TYPE_AUCTION || $categorySiteID == EbaySite::EBAY_MOTOR_SITEID) ? EbayCategoryInfo::model()->getDefaultLocalService($addInfo['site_id']) : $value['shipping_service'];
    				if(empty($value['shipping_service'])){
    					$this->throwE("本地运输服务商没有设置",132);
    				}
    				$dataArr = array(
    						'FreeShipping'	 	=> 	$shippingcost==0 ? 'true': 'false',
    						'ShippingService' 	=> 	$value['shipping_service'],
    						'ShippingServiceCost' => $shippingcost,
    						'ShippingServiceAdditionalCost' => $shippingcostAdd,
    						'ShippingServicePriority' => $localPriority,
    				);
    				//添加本地运输的xml
    				$xmlgenerator->xml = "";
    				$shippingXml .= $xmlgenerator->buildXMLFilterMulti(array('ShippingServiceOptions'=>$dataArr),'',$attributes)->getXml();
    			}else{
    				$internationalPriority++;
    				
    				$shippingcost = isset($value['ship_cost']) ? $value['ship_cost'] : 0;
    				$shippingcostAdd = isset($value['additional_ship_cost']) ? $value['additional_ship_cost'] : 0;
    				
    				if(empty($value['shipping_service'])){
    					$this->throwE("本地运输服务商没有设置",133);
    				}
    				if(empty($value['ship_location'])){
    					$this->throwE("非全球运送需要指定国家",134);
    				}
    				$dataArr = array(
    						'FreeShipping'	 => 	$shippingcost==0 ? 'true': 'false',
    						'ShippingService' => $value['shipping_service'],
    						'ShippingServiceCost' => $shippingcost,
    						'ShippingServiceAdditionalCost' => $shippingcostAdd,
    						'ShippingServicePriority' => $internationalPriority,
    				);
    				$ShipToLocation = explode(',', $value['ship_location']);
    				$xmlgenerator->xml = "";
    				$locationXml = $xmlgenerator->buildXMLFilter($ShipToLocation, 'ShipToLocation')->getXml();//1个或多个运输地区
    				$xmlgenerator->xml = "";
    				$internationalXml = $xmlgenerator->buildXMLFilter($dataArr, '', $attributes)->getXml();
    				//print_r($internationalXml);
    				$internationalXml .= $locationXml;
    				//添加国际运输的xml
    				$xmlgenerator->xml = "";
    				$shippingXml .= $xmlgenerator->buildXMLFilter(array('InternationalShippingServiceOption'=>$internationalXml), '', $attributes)->getXml();
    			}
    		}
    		
    		$addInfo2 = $this->findByPk($addID);
    		if($addInfo2['item_id']>0 ) $this->throwE("Has uploading",135);
    		
    		if($shippingXml){
    			$itemInfo['ShippingDetails'] = $shippingXml;
    		}

            $pass_start = time();
    		if($addInfo['listing_type'] == self::LISTING_TYPE_AUCTION){
    			$request = new AddItemRequest();
    		}else {
    			$request = new AddFixedPriceItemRequest();
    		}
    		$request->setAccount($addInfo['account_id']);
    		$request->setSiteID($sid);
    		$request->setItemInfo($itemInfo);
			if(isset($_REQUEST['debug']) && $_REQUEST['debug']==1){
				print_r($request->setRequest()->getRequestXmlBody());
			}
    		$response = $request->setRequest()->sendRequest()->getResponse();

            $pass_end = time();
            if ($addType==2) {
                //MHelper::writefilelog('timepass_new3.txt', $addInfo2['sku'].' 刊登 pass: '. ($pass_end-$pass_start)."\r\n" );
            }
    		if($request->getIfSuccess()){
    			$itemID = trim($response->ItemID);
    			//上传成功，更新状态
    			$data = array(
    					'status' => self::STATUS_SUCCESS,
    					'last_response_time' => date("Y-m-d H:i:s"),
    					//'update_time' => date("Y-m-d H:i:s"),
    					'upload_msg' => '',
    					'item_id' => $itemID,
						'upload_user_id' =>	$userID,
    			);
    			$this->getDbConnection()->createCommand()->update($this->tableName(), $data, "id=".$addID);
    			//更新待刊登列表的状态
				EbayWaitListing::model()->updateWaitingListingStatus($addInfo, EbayWaitListing::STATUS_SCUCESS);
				//更新历史数据表中的状态
				EbayHistoryListing::model()->updateWaitingListingStatus($addInfo, EbayWaitListing::STATUS_SCUCESS);
                //异常触发拉取listing
                $url = Yii::app()->request->hostInfo."/ebay/ebayproduct/getitem/item_id/".$itemID;
                MHelper::runThreadBySocket ( $url );
    			return true;
    		}else{
    			$this->throwE('##ebay刊登失败##'.$request->getErrorMsg(),136);
    		}
    	}catch (Exception $e){
    		$addInfo = $this->findByPk($addID);
    		$data = array(
    				'status' => $e->getCode() == '100' ? self::STATUS_IMGPENDING : self::STATUS_FAILURE,
    				'last_response_time' => date("Y-m-d H:i:s"),
    				'update_time' => date("Y-m-d H:i:s"),
    				'upload_msg' => $e->getMessage(),
					'upload_user_id' =>	$userID,
    				'upload_count'	=>	isset($addInfo->upload_count) ? ++$addInfo->upload_count : 1
    		);
    		$this->getDbConnection()->createCommand()->update($this->tableName(), $data, "id=".$addID." and item_id=0");
    		$this->setErrorMessage($e->getMessage().' errCode:'.$e->getCode() );
    		return false;
    	}
    	return true;
    }
    
    /**
     * @desc 获取拍卖价
     * @param unknown $auctionRule
     * @param unknown $sku
     * @param unknown $currency
     * @param unknown $siteID
     * @param unknown $accountID
     * @param unknown $categoryName
     * @return multitype:number unknown Ambigous <number, unknown, unknown, Ambigous <number, unknown>, Ambigous <unknown, string>, number, Ambigous <number, unknown>, Ambigous <unknown, number>, boolean, Ambigous <number, mixed>, Ambigous <unknown, unknown>, unknown>
     */
    public function getAuctionPriceByAuctionRule($auctionRule, $sku, $currency, $siteID, $accountID, $categoryName){
    	$saleProductPriceConfig = new EbayProductSalePriceConfig();
    	$priceData = $saleProductPriceConfig->getSalePrice($sku, $currency, $siteID, $accountID, $categoryName);
    	$return = array(
    		'auction_price'	=>	0.01,
    		'shiping_cost'	=>	0
    	);
    	//拍卖规则
    	if($auctionRule == self::AUCTION_PROFIT_SALEPRICE){//如果拍卖的规则是将利润同步到卖价
    		$return['auction_price'] = $priceData['salePrice'];
    	}elseif ($auctionRule == self::AUCTION_PROFIT_SHIPCOST){//利润同步到运费
    		$return['shiping_cost'] = $priceData['salePrice'];
    	}else{//无规则，默认
    	    	
    	}
    	return $return;
    }
    /**
     * @desc 获取eub费用
     * @param unknown $sku
     * @param unknown $salePrice
     * @param unknown $internalShippingCost
     * @param unknown $currency
     * @param unknown $categoryName
     * @param unknown $accountID
     * @return unknown|number
     */
    public function getEubCost($sku, $salePrice, $internalShippingCost, $currency, $categoryName, $accountID){
    	//判断是否支持eub
    	//获取当前账号信息
    	$accountInfo = EbayAccount::model()->getAccountInfoById($accountID);
    	if(!$accountInfo || !$accountInfo['is_eub']) return $internalShippingCost;
    	//@TODO 获取sku的特殊属性
    	
    	//获取产品信息
    	$productInfo = Product::model()->getProductBySku($sku);
    	if(empty($productInfo)){
    		return 0;
    	}
    	$realWeight = $productInfo['product_weight'];
    	$productCost = $productInfo['product_cost'];
    	$shipCountry = '';
    	if($accountInfo['is_eub']) $shipCountry = 'United States';
    	$attributes = $sku ? Product::model()->getAttributeBySku($sku, 'product_features') : array();//属性
    	if($attributes) return $internalShippingCost;
    	$shipfee = Logistics::model()->getShipFee(Logistics::CODE_EUB, $realWeight, array(
    			'country'   => $shipCountry,
    			'attributeid'   => $attributes,
    			'platform_code' => Platform::CODE_EBAY,
    	));
    	//算出卖价+运费一共多少美金(用于选择最优运费
    	if($currency=='USD'){
    		$totalUSD = $salePrice + $internalShippingCost;
    	}else{
    		$totalUSD = ($salePrice + $internalShippingCost) * CurrencyRate::model()->getRateToOther($currency,'USD');
    	}
    	if($totalUSD < 5){
    		if($accountID){
    			$isEubUnder5 = $accountInfo['is_eub_under5'];
    			if($isEubUnder5 != 1){//5美元以下也走eub的账号
    				return $internalShippingCost;
    			}
    		}else{
    			return $internalShippingCost;
    		}
    	}
    	if(!$accountInfo['is_eub']){
    		if(in_array($currency, array('AUD','GBP'))){//如果是au,uk采用 SPECIAL_AREA_ukau 运费
    			$countryname = 'SPECIAL_AREA_ukau'; //@TODO 不知道新系统是否支持此国家名称
    		}else{
    			$countryname = '';
    		}	
    	}
    	
    	$useGHUSD = SysParaSetting::getSysPara('useGH'); //读取需要寄挂号的金额.

    	$useExpressCost = floatval(SysParaSetting::getSysPara('use_express_cost')); //读取需要寄快递的成本.
    	$shipCode = $shipCode2 = '';
    	if(floatval($productCost) > 0 && $productCost > $useExpressCost || $realWeight>2000){//走快递
    		$shipCode = Logistics::CODE_4HY_EXPRESS;
    	}elseif(floatval($totalUSD) >= floatval($useGHUSD)){//走挂号
    		$shipCode = Logistics::CODE_GHXB;
    		$shipCode2 = Logistics::CODE_CM;
    	}else{//普邮
    		$shipCode = Logistics::CODE_CM;
    	}
    	
    	$shipfee2 = Logistics::model()->getShipFee($shipCode, $realWeight, array(
    			'country'   => $shipCountry,
    			'attributeid'   => $attributes,
    			'platform_code' => Platform::CODE_EBAY,
    	));
    	if($shipCode2 && $shipfee2 <= 0){
    		$shipfee2 = Logistics::model()->getShipFee($shipCode2, $realWeight, array(
    				'country'   => $shipCountry,
    				'attributeid'   => $attributes,
    				'platform_code' => Platform::CODE_EBAY,
    		));
    	}
    	
    	//获取小包转eub后的差价
    	$feeCNY = $shipfee-$shipfee2;//人民币
    	
    	if ($feeCNY < 1) {		//差价小于1人民币，不收运算
    		$feeCNY = 0;
    	}
    	
    	//如果是美金就直接转,如果是其它货币先转换成港币,再转到指定货币.
    	$currencyRate = CurrencyRate::model()->getRateToCny($currency);
    	$feeFinal = $feeCNY/$currencyRate;//最终的货币
    	//算出多出的paypal交易费和ebay成交费
    	$currencyCalculate = new CurrencyCalculate();
    	$currencyCalculate->setCurrency($currency);
    	$paypalFeeInfo = $currencyCalculate->getPaypalPayFee(array(
    											'totalAmount'	=>	floatval(($salePrice+$internalShippingCost)/$currencyRate),
    											'rate'			=>	$currencyRate
    										));
    	$paypalRate = $paypalFeeInfo['rate'];
    	if($currency=='USD'){
    		$ebayFeeInfo = $currencyCalculate->getEbayFee(array(
    				'salePrice'     => floatval(($salePrice+$internalShippingCost)/$currencyRate),
    				'shippingCost'	=> $internalShippingCost,
    				'categoryName'  => $categoryName,
    				'currency'      => $currency,
    				'rate'          => $currencyRate,
    				'accountID'		=> $accountID,
    				'siteID'		=> $siteID
    		));
    		$ebayRate = $ebayFeeInfo['rate'];
    		$feeFinal = $feeFinal/(1-$ebayRate-$paypalRate);
    	}else{
    		$feeFinal = $feeFinal/(1-$paypalRate);
    	}
    	return $internalShippingCost+$feeFinal;
    }
    
    /**
     * @desc 获取特殊国家的费用
     * @param unknown $sku
     * @param unknown $salePrice
     * @param unknown $internalShippingCost
     * @param unknown $currency
     * @param unknown $categoryName
     * @param unknown $specialCountry
     * @return number
     */
    public function getSpecialCost($sku, $salePrice, $internalShippingCost, $currency, $categoryName, $specialCountry, $accountID, $siteID){
    	//获取产品信息
    	$productInfo = Product::model()->getProductBySku($sku);
    	if(empty($productInfo)){
    		return 0;
    	}
    	$realWeight = $productInfo['product_weight'];
    	$productCost = $productInfo['product_cost'];
    	$shipCountry = '';
    	
    	$shipfeeInfo = Logistics::model()->getMinShippingInfo($realWeight, array(
    			'country'   	=> 	$shipCountry,
    			'attributeid'   => 	array(),
    			'platform_code' => 	Platform::CODE_EBAY,
    			'ship_code'		=>	''
    	));
    	$shipfee = isset($shipfeeInfo['ship_cost']) ? $shipfeeInfo['ship_cost'] : 0; 
    	//算出卖价+运费一共多少美金(用于选择最优运费
    	if($currency=='USD'){
    		$totalUSD = $salePrice + $internalShippingCost;
    	}else{
    		$totalUSD = ($salePrice + $internalShippingCost) * CurrencyRate::model()->getRateToOther($currency,'USD');
    	}
    	$attributes = $sku ? Product::model()->getAttributeBySku($sku, 'product_features') : array();//属性
    	
    	if(in_array($currency, array('AUD','GBP'))){//如果是au,uk采用 SPECIAL_AREA_ukau 运费
    		$countryname = 'SPECIAL_AREA_ukau'; //@TODO 不知道新系统是否支持此国家名称
   		}else{
   			$countryname = '';
   		}
    	
    	$useGHUSD = SysParaSetting::getSysPara('useGH'); //读取需要寄挂号的金额.
    
    	$useExpressCost = floatval(SysParaSetting::getSysPara('use_express_cost')); //读取需要寄快递的成本.
    	$shipCode = $shipCode2 = '';
    	if(floatval($productCost) > 0 && $productCost > $useExpressCost || $realWeight>2000){//走快递
    		$shipCode = Logistics::CODE_4HY_EXPRESS;
    	}elseif(floatval($totalUSD) >= floatval($useGHUSD)){//走挂号
    		$shipCode = Logistics::CODE_GHXB;
    		$shipCode2 = Logistics::CODE_CM;
    	}else{//普邮
    		$shipCode = Logistics::CODE_CM;
    	}
    	 
    	$shipfee2 = Logistics::model()->getShipFee($shipCode, $realWeight, array(
    			'country'   => $shipCountry,
    			'attributeid'   => $attributes,
    			'platform_code' => Platform::CODE_EBAY,
    	));
    	if($shipCode2 && $shipfee2 <= 0){
    		$shipfee2 = Logistics::model()->getShipFee($shipCode2, $realWeight, array(
    				'country'   => $shipCountry,
    				'attributeid'   => $attributes,
    				'platform_code' => Platform::CODE_EBAY,
    		));
    	}
    	//获取小包转eub后的差价
    	$feeCNY = $shipfee-$shipfee2;//人民币
    	
    	if ($feeCNY < 1) {		//差价小于1人民币，不收运算
    		$feeCNY = 0;
    	}
    	 
    	//如果是美金就直接转,如果是其它货币先转换成港币,再转到指定货币.
    	$currencyRate = CurrencyRate::model()->getRateToCny($currency);
    	$feeFinal = $feeCNY/$currencyRate;//最终的货币
    	//算出多出的paypal交易费和ebay成交费
    	$currencyCalculate = new CurrencyCalculate();
    	$currencyCalculate->setCurrency($currency);
    	$paypalFeeInfo = $currencyCalculate->getPaypalPayFee(array(
    			'totalAmount'	=>	floatval(($salePrice+$internalShippingCost)/$currencyRate),
    			'rate'			=>	$currencyRate
    	));
    	$paypalRate = $paypalFeeInfo['rate'];
    	if($currency=='USD'){
    		$ebayFeeInfo = $currencyCalculate->getEbayFee(array(
    				'salePrice'     => floatval(($salePrice+$internalShippingCost)/$currencyRate),
    				'shippingCost'	=> $internalShippingCost,
    				'categoryName'  => $categoryName,
    				'currency'      => $currency,
    				'rate'          => $currencyRate,
    				'accountID'		=> $accountID,
    				'siteID'		=> $siteID
    		));
    		$ebayRate = $ebayFeeInfo['rate'];
    		$feeFinal = $feeFinal/(1-$ebayRate-$paypalRate);
    	}else{
    		$feeFinal = $feeFinal/(1-$paypalRate);
    	}
    	return $internalShippingCost+$feeFinal;
    }
	/**
	 * @desc 获取产品在某平台某账号中的预览描述
	 * @param unknown $sku
	 * @param unknown $accountID
	 * @param string $listingType
	 * @param string $preview
	 * @param string $title
	 * @param string $description
	 * @param string $included
	 * @param string $siteID
	 * @param string $warehouse
	 * @param string $language
	 * @return unknown
	 */
	public function getDescription($sku, $accountID, $siteID = '', $language = '', $listingType = '', $preview = true, $title = '', $description = '', $included = '', $warehouse = '', $imageList = array()){
		$platformCode = Platform::CODE_EBAY;
		/* if(!$warehouse && $accountID && $siteID !== ''){//有账号和站点，可以查出仓库
			$warehouse = EbayAccountSite::model()->getWarehouseByAccountSite($accountID, $siteID);
		} */
		$ebaySiteModel = new EbaySite();
		if($siteID && !$language){//有siteid,可以通过siteid查到语言
			$language = $ebaySiteModel->getLanguageBySiteIDs($siteID);
		}
		$descriptionTemplateModel = new EbayDescriptionTemplate;
		$conditions = " account_id='$accountID'";
		if($language){
			$conditions .= " AND language_code = '$language'";
		}
		$templateInfo = EbayDescriptionTemplate::model()->getDbConnection()
													->createCommand()
													->from(EbayDescriptionTemplate::model()->tableName())
													->where($conditions)
													->queryRow();
		$productInfo = Product::model()->getProductInfoBySku($sku);
		$productDesc = Productdesc::model()->getDescriptionInfoBySkuAndLanguageCode($sku, $language);
		$ebayProductmageAddModel = new EbayProductImageAdd();
		if($preview){
			$config = ConfigFactory::getConfig('serverKeys');
			$firstImage = "";
			$imagelist = Product::model()->getImgList($sku,'ft');
			foreach($imagelist as &$img){
				$img = $config['oms']['host'].$img;//TODO 做成系统配置
				if(empty($firstImage))
					$firstImage = $img;
			}
		} else {      
			if($imageList){
				$imagelist = $imageList;
			}else{
				$imagelist = $ebayProductmageAddModel->getRemoteImageList($sku, $accountID, '', $siteID);
				if(empty($imagelist)) {
					$imagelist = $ebayProductmageAddModel->getRemoteImageList($sku, $accountID, EbayProductImageAdd::IMAGE_ZT, $siteID);
					if(empty($imagelist)){
						$this->throwE(Yii::t('ebay', 'Image Upload Failure'));
					}
				}
			} 
			$firstImage = $imagelist[0];
		}
		$specialSiteIDs = $ebaySiteModel->getSpecialLanguageSiteIDs();
		$is_special = in_array($siteID, $specialSiteIDs); 
		
		if(!$title){
			$title = $productDesc['title'];
		}
			
		if(!$description){
			$description = $descriptionTemplateModel->getMatchResult($productDesc['description']);
		}
		if(!$included){
			$included = $descriptionTemplateModel->getMatchResult($productDesc['included']);
		}
		$content = $templateInfo['template_content']; 
		$content = str_replace('[title/]', $title, $content);
		$content = str_replace('[firstimage/]', $firstImage, $content);
		
		//替换description
		$content = $descriptionTemplateModel->getReplacedListContent($content,'description','descriptionline', $description);
		//替换include
		$content = $descriptionTemplateModel->getReplacedListContent($content,'included','includedline', $included);
		//替换图片
		$content = $descriptionTemplateModel->getReplacedListContent($content,'imagelist','imageurl', $imagelist);
		
		return $content;
	}
	
	/**
	 * @desc 获取没有图片替换的描述信息
	 * @param unknown $sku
	 * @param unknown $accountID
	 * @param string $siteID
	 * @param string $language
	 * @param string $listingType
	 * @param string $title
	 * @param string $description
	 * @param string $included
	 * @param string $warehouse
	 * @return Ambigous <unknown, mixed>
	 */
	public function getDescriptionNoImg($sku, $accountID, $siteID = '', $language = '', $listingType = '', $title = '', $description = '', $included = '', $warehouse = ''){
		
		$platformCode = Platform::CODE_EBAY;
		if(!$warehouse && $accountID && $siteID !== ''){//有账号和站点，可以查出仓库
			$warehouse = EbayAccountSite::model()->getWarehouseByAccountSite($accountID, $siteID);
		}
		$ebaySiteModel = new EbaySite();
		if($siteID && !$language){//有siteid,可以通过siteid查到语言
			$language = $ebaySiteModel->getLanguageBySiteIDs($siteID);
		}
		$descriptionTemplateModel = new EbayDescriptionTemplate;
		$conditions = " account_id='$accountID'";
		if(empty($language)){
			$language = "";
		}
		if($language){
			$conditions .= " AND language_code = '$language'";
		}
		
		$templateInfo = EbayDescriptionTemplate::model()->getDbConnection()
		->createCommand()
		->from(EbayDescriptionTemplate::model()->tableName())
		->where($conditions)
		->queryRow();
		$productInfo = Product::model()->getProductInfoBySku($sku);
		
		if(empty($language)){
			$language = "english";
		}
		$productDesc = Productdesc::model()->getDescriptionInfoBySkuAndLanguageCode($sku, $language);
		
		$specialSiteIDs = $ebaySiteModel->getSpecialLanguageSiteIDs();
		$is_special = in_array($siteID, $specialSiteIDs);
	
		if(!$title){
			$title = $productDesc['title'];
		}
			
		if(!$description){
			$description = $descriptionTemplateModel->getMatchResult($productDesc['description']);
		}
		if(!$included){
			$included = $descriptionTemplateModel->getMatchResult($productDesc['included']);
		}
		$content = $templateInfo['template_content'];
		//title 自动替换
		//$content = str_replace('[title/]', $title, $content);
	
		//替换description
		$content = $descriptionTemplateModel->getReplacedListContent($content,'description','descriptionline', $description);
		//替换include
		$content = $descriptionTemplateModel->getReplacedListContent($content,'included','includedline', $included);

		return $content;
	}
	
	/**
	 * @desc 只替换图片的详细描述内容
	 * @param unknown $detail
	 * @param unknown $sku
	 * @param unknown $accountID
	 * @param string $siteID
	 * @param unknown $diffVariationList
	 * @return unknown|Ambigous <unknown, mixed>
	 */
	public function getDescriptionOnlyImg($title, $detail, $sku, $accountID, $siteID = '', $diffVariationList = array(), $imagelist = array()){
		if(empty($detail)) return $detail;
		$ebayProductmageAddModel = new EbayProductImageAdd();
		$descriptionTemplateModel = new EbayDescriptionTemplate;
		if(empty($imagelist)){
			$imagelist = $ebayProductmageAddModel->getRemoteImageList($sku, $accountID, EbayProductImageAdd::IMAGE_FT, $siteID);
			if(empty($imagelist)){
				$imagelist = $ebayProductmageAddModel->getRemoteImageList($sku, $accountID, EbayProductImageAdd::IMAGE_ZT, $siteID);
			}
		}
		if(empty($imagelist)){
			$this->throwE(Yii::t('ebay', 'Image Upload Failure'));
		}
		$firstImage = $imagelist[0];
		$firstImage = str_replace('$_1.JPG?', '$_10.JPG?', $firstImage);
		$content = $detail; 
		$content = str_replace('[title/]', $title, $content);
		$content = str_replace('[firstimage/]', $firstImage, $content);
		//替换图片
		if($diffVariationList){



            /**
             * @author ketu.lai
             * @desc 多属性SKU刊登、多个SKU组合刊登时，listing详情图只显示对应主SKU的图片 20170227
             */

            $query = 'SELECT distinct IFNULL(pp.sku, pe.main_sku) as sku FROM 
                  (SELECT p.id, p.sku as main_sku, psa.id as pid, psa.product_id, psa.multi_product_id, psa.sku FROM `ueb_product` `p` LEFT JOIN 
                  `ueb_product_select_attribute` `psa` ON p.id = psa.product_id WHERE p.sku in (
                  '.MHelper::simplode($diffVariationList).'
                  )) as pe left join ueb_product as pp on pe.multi_product_id = pp.id';
            $queryBuilder = Product::model()->getDbConnection()->createCommand($query);
            $queryBuilder->getText();

            $mainImageSku = $queryBuilder->queryAll();

            foreach ($mainImageSku as $imageSku) {
                $mainSkuImageList = $ebayProductmageAddModel->getRemoteImageList($imageSku['sku'], $accountID, ProductImageAdd::IMAGE_ZT, $siteID);
                if($mainSkuImageList){
                    $imagelist = array_merge($imagelist, $mainSkuImageList);
                }
            }

		}
		if($imagelist){
			foreach ($imagelist as $key=>$img){
				$imagelist[$key] = str_replace('$_1.JPG?', '$_10.JPG?', $img);
			}
		}
		$content = $descriptionTemplateModel->getReplacedListContent($content,'imagelist','imageurl', $imagelist);
		return $content;
	}
	
	
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
    
    
    
    // ======================== Start:Search =============================
    public function search(){
    	$csort = new CSort();
    	$csort->attributes = array(
    		'defaultOrder'=>'update_time'
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
    	$siteList = EbaySite::getSiteList();
    	$accountList = EbayAccount::getIdNamePairs();
    	$ebayProductVariants = new EbayProductAddVariation();
    	$ebayProductImage = new EbayProductImageAdd();
    	foreach ($datas as &$data){
			if($data['upload_count']>=3){$data['upload_count'] = "<span style='color:red'>上传次数为".$data['upload_count']."，需要手动上传</span>";}
    		$data['site_name'] = isset($siteList[$data['site_id']]) ? $siteList[$data['site_id']] : '-';
    		$data['account_name'] = isset($accountList[$data['account_id']]) ? $accountList[$data['account_id']] : '-';
    		$data['category_title'] = EbayCategory::model()->getBreadcrumbCategory($data['category_id'], $data['site_id']) . '<br/><br/><br/>' . $data['title'];
    		$data['status_text'] = $this->getStatusOptionsText($data['status']);
    		$data['config_name'] = $this->getAttributeOptions($data['config_type']);
    		if($data['status'] == self::STATUS_FAILURE || $data['status'] == self::STATUS_IMGPENDING){
    			$data['status_text'] .= "<br/>".$data['upload_msg'];
    			$data['status_text'] = "<span style='color:red'>".$data['status_text']."</span>";
    		}elseif($data['status'] == self::STATUS_PENDING){
    			$data['status_text'] = "<span style='color:green'>".$data['status_text']. ($data['upload_msg'] ?  "<br/>Notice：".$data['upload_msg'] : '')."</span>";
    		}
    		$data['item_id'] = $this->getItemlink($data['item_id'], $data['site_id']);
    		$data['add_type_text'] = $this->getAddTypeOption($data['add_type']);
    		$data['sale_prices']	=	'';
    		if($data['listing_type'] == self::LISTING_TYPE_VARIATION){
    			//取出多属性sku价格
    			$variationList = $ebayProductVariants->getEbayProductAddVariationJoinEbayProductAddByAddID($data['id'], 'v.son_sku, v.variation_price,v.son_seller_sku, v.variation_discount_price');
    			if($variationList){
    				foreach ($variationList as $variations){
						$data['sale_prices'] .= "sku:{$variations['son_sku']}-{$variations['variation_price']}<br/>";
    				}
    			}
    		}else {
				$data['sale_prices'] = $data['start_price'];
    		}
    		$data['visiupload'] = $data['status'] == self::STATUS_PENDING || $data['status'] == self::STATUS_FAILURE || $data['status'] == self::STATUS_IMGPENDING ? 1:0;
    		$data['listing_type'] = $this->getListingTypeOptionText($data['listing_type']);
    	}
    	
    	return $datas;
    }
    
    public function setCdbCriteria(){
    	$cdbCriteria = new CDbCriteria();
    	$cdbCriteria->select = "*";
		$accountId = Yii::app()->request->getParam('account_id');
		$siteId = Yii::app()->request->getParam('site_id');
		$userSiteArr = array();
		$userAccountArr = array();
		if(isset(Yii::app()->user->id)){
			$job_param = 'job_id=:job_id AND is_del =:is_del AND seller_user_id =:seller_user_id';
			$job_array = array(':job_id' => ProductsGroupModel::GROUP_LEADER, ':is_del' => 0,':seller_user_id' => Yii::app()->user->id);
			$is_job = ProductsGroupModel::model()->find($job_param,$job_array);
			if($is_job){//排除组长

			}else{
				$userAccountSite = SellerUserToAccountSite::model()->getAccountSiteByCondition(Platform::CODE_EBAY,Yii::app()->user->id);
				if($userAccountSite){
					foreach ($userAccountSite as $sellerList) {
						$userSiteArr[] = EbaySite::model()->getSiteIdByName($sellerList['site']);
						$userAccountArr[] = $sellerList['account_id'];
					}
					$userSiteArr = array_unique($userSiteArr);
					$userAccountArr = array_unique($userAccountArr);
				}
			}
		}
		if($userAccountArr && !in_array($accountId, $userAccountArr)){
			$accountId = implode(',', $userAccountArr);
		}
		if($accountId){
			$cdbCriteria->addCondition("t.account_id IN (".$accountId.")");
		}
		if($userSiteArr && !in_array($siteId, $userSiteArr)){
			$siteId = implode(',', $userSiteArr);
		}
		if(is_int($siteId)){
			$cdbCriteria->addCondition("t.site_id IN (".$siteId.")");
		}

    	return $cdbCriteria;
    }
    
    public function getItemlink($itemID, $siteID){
    	$return = $itemID;
    	if($itemID){
    		$url = "http://www.ebay.com/itm/{$itemID}";
    		$return = '<a href="'.$url.'" target="__blank">'.$itemID.'</a>';
    	}
    	return $return;
    }
    
    public function getAddTypeOption($addType = null){
    	$addTypeOptions = array(
    			self::ADD_TYPE_DEFAULT	=>	'普通',
    			self::ADD_TYPE_BATCH	=>	'批量',
    			self::ADD_TYPE_SMALL    =>	'小语种',
                self::ADD_TYPE_COPY     =>  '复制',
    			self::ADD_TYPE_PRE      =>  '预刊登',
                self::ADD_TYPE_XLSX     =>  'xlsx批量导入',
    			
    	);
    	if($addType !== null){
    		return isset($addTypeOptions[$addType]) ? $addTypeOptions[$addType] : '';
    	}
    	return $addTypeOptions;
    }
    
    /**
     * @desc 获取刊登类型选择项
     * @param unknown $listingType
     * @return Ambigous <NULL, Ambigous <string, string, unknown>>|multitype:NULL Ambigous <string, string, unknown>
     */
    public function getListingTypeOptionText($listingType = null){
    	$listingTypeArr = array(
    			self::LISTING_TYPE_AUCTION		=>	Yii::t('ebay', 'Listing Auction Type'),
    			self::LISTING_TYPE_FIXEDPRICE	=>	Yii::t('ebay', 'Listing FixedPrice Type'),
    			self::LISTING_TYPE_VARIATION	=>	Yii::t('ebay', 'Listing Variation Type'),
    	);
    	if($listingType) return $listingTypeArr[$listingType];
    	return $listingTypeArr;
    }
    
    /**
     * @desc 获取状态选项
     * @param unknown $status
     * @return Ambigous <NULL, Ambigous <string, string, unknown>>|multitype:NULL Ambigous <string, string, unknown>
     */
    public function getStatusOptionsText($status = null){
    	$statusArr = array(
                            self::STATUS_FAILURE    => Yii::t('ebay', 'Product Add Failure'),
                            self::STATUS_SUCCESS    => Yii::t('ebay', 'Product Add Successful'),
                            self::STATUS_OPERATING  => Yii::t('ebay', 'Product Add Operating'),
                            self::STATUS_PENDING    => Yii::t('ebay', 'Product Add Pending'),
                            self::STATUS_IMGPENDING => Yii::t('ebay', 'Product Add Image Pending'),
    				);	
    	if($status) return $statusArr[$status];
    	return $statusArr;
    }
    
  	public function getCreateUserOptions(){
  		return User::model()->getEbayUserList(false, true);
		/*  return UebModel::model('user')  
                        ->queryPairs('id,user_full_name', "department_id in(".MHelper::simplode(Department::getDepartmentByPlatform(Platform::CODE_EBAY)).") and user_status=1");   //ebay部门
	 */
  	}

	//获取参数类型
	public function getAttributeOptions($attributeId = null){

		$attribute = $this->getConfigType();
		if($attributeId !== null){
			return $attribute[$attributeId];
		}

		return $attribute;
	}

    /**
     * @desc 显示用户名
     * @author hanxy
     */
    public function getUserNameList(){
    	return User::model()->getEbayUserList(false, false);
        /* return UebModel::model('user')  
                        ->queryPairs('id,user_name', "department_id in(".MHelper::simplode(Department::getDepartmentByPlatform(Platform::CODE_EBAY)).") and user_status=1");   //ebay部门
     */
    }
    
    public function filterOptions(){
    	$status = Yii::app()->request->getParam('status');
    	if($status === null){
    		$status = self::STATUS_PENDING;
    	}
    	$siteID = Yii::app()->request->getParam('site_id');
    	$addType = Yii::app()->request->getParam('add_type');
    	$configType = Yii::app()->request->getParam('config_type');
    	return array(
    				array(
    						'name'		=>	'sku',
    						'type'		=>	'text',
    						'search'	=>	'=',
    				),
	    			array(
	    					'name'		=>	'item_id',
	    					'type'		=>	'text',
	    					'search'	=>	'=',
	    			),
    				array(
    						'name'		=>	'account_id',
    						'type'		=>	'dropDownList',
    						'data'		=>	EbayAccount::getIdNamePairs(),
    						'search'	=>	'='
    				),
	    			array(
	    					'name'		=>	'site_id',
	    					'type'		=>	'dropDownList',
	    					'data'		=>	EbaySite::getSiteList(),
	    					'search'	=>	'=',
	    					'value'		=>	$siteID
	    			),
	    			array(
	    					'name'		=>	'status',
	    					'type'		=>	'dropDownList',
	    					'data'		=>	$this->getStatusOptionsText(),
	    					'search'	=>	'=',
	    					'value'		=>	$status,
	    				//	'default'	=>	self::STATUS_PENDING
	    			),
	    			array(
	    					'name'		=>	'listing_type',
	    					'type'		=>	'dropDownList',
	    					'data'		=>	$this->getListingTypeOptionText(),
	    					'search'	=>	'=',
	    					
	    			),
	    			array(
	    					'name'		=>	'add_type',
	    					'type'		=>	'dropDownList',
	    					'data'		=>	$this->getAddTypeOption(),
	    					'search'	=>	'=',
	    					'value'		=> $addType,
	    			
	    			),
    			
	    			array(
	    					'name'		=>	'update_user_id',
	    					'type'		=>	'dropDownList',
	    					'data'		=>	$this->getCreateUserOptions(),
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
				array(
					'name'		=>	'config_type',
					'type'		=>	'dropDownList',
					'data'		=>	$this->getAttributeOptions(),
					'search'	=>	'=',
					'value'		=> $configType,

				),
    			
    	);
    }

    public function attributeLabels(){
    	return array(
    				'sku'			=>	'SKU',
    				'seller_sku'	=>	Yii::t('ebay', 'Sku Online'),
    				'site_name'		=>	Yii::t('ebay', 'Site Name'),
    				'site_id'		=>	Yii::t('ebay', 'Site Name'),
    				'account_name'	=>	Yii::t('ebay', 'Account Name'),
    				'account_id'	=>	Yii::t('ebay', 'Account Name'),
    				'listing_type'	=>	Yii::t('ebay', 'Listing Type'),
    				'category_title'	=>	Yii::t('ebay', 'Category Title'),
    				'sale_prices'		=>	Yii::t('ebay', 'Start Price'),
    				'status'			=>	Yii::t('ebay', 'Status'),
    				'status_text'		=>	Yii::t('ebay', 'Status'),
    				'update_user_id'	=>	Yii::t('system', 'Modify User'),
    				'update_time'		=>	Yii::t('system', 'Modify Time' ),
    				'last_response_time'	=>	Yii::t('system', 'Respond time'),
    				'item_id'				=>	'ItemID',
    				'add_type'				=>	Yii::t('ebay', 'Product Add Type'),
    				'listing_duration'		=>	Yii::t('ebay', 'Listing Duration'),
    				'create_time'			=>	Yii::t('ebay', 'Create Time'),
    				'config_type'				=>	Yii::t('ebay', 'Config Type Name'),
    				'config_name'				=>	Yii::t('ebay', 'Config Type Name'),
    	);
    }
    
    
    // ======================== End:Search ===============================


    /**
     * @desc 获取可用账号列表，根据sku、站点site
     * @param unknown $sku
     * @return multitype:unknown
     */
    public function getAbleAccountListBySku($sku,$site = null, $listingType = null){
        $excludeAccounts = array();
        //获取sku在线listing
        if(!is_array($listingType)){
        	$listingType = array($listingType);
        }
        $newListingType = array();
        foreach ($listingType as $val){
        	if($val == EbayProductAdd::LISTING_TYPE_AUCTION){
        		$newListingType[] = "Chinese";
                $newListingType[] = "PersonalOffer";
        	}else{
                $newListingType[] = "StoresFixedPrice";
        		$newListingType[] = "FixedPriceItem";
        	}
        }
        
        //获取产品管理在线listing账号id
        $excludeAccounts = EbayProduct::model()->getOnlineAccountIdArr($sku, $site, $newListingType, EbayProduct::STATUS_ONLINE );
        //MHelper::writefilelog('account.txt','excludeAccounts1: '.print_r($excludeAccounts,true)."\r\n");
        //获取准备刊登(在刊登列表里)待刊登、刊登成功且在线的记录账号id
        $excludeAccounts2 = $this->getPrepareAccountIdArr($sku, $site, $listingType);

		//product_add,默认状态并且待刊登的取出来
		//$excludeAccounts2 = $this->getPrepareAccountIdArr2($sku, $site, $listingType);

        //MHelper::writefilelog('account.txt','excludeAccounts2: '.print_r($excludeAccounts2,true)."\r\n"); 
        // all account
        $accountAll  = EbayAccountSite::getAbleAccountListBySiteID($site);
        $accounts    = array();
        $accountInfo = array();
        foreach($accountAll as $account){
            //TODO 排除锁定状态设定为无法刊登的账号
            $accounts[$account['id']] = $account['id'];
        }
        //MHelper::writefilelog('account.txt','accounts: '.print_r($accounts,true)."\r\n");

        $ableAccounts = array_diff($accounts,$excludeAccounts);
        //MHelper::writefilelog('account.txt','excludeAccounts-1: '.print_r($excludeAccounts,true)."\r\n");

        $ableAccounts = array_diff($ableAccounts,$excludeAccounts2);
        //MHelper::writefilelog('account.txt','excludeAccounts-2: '.print_r($excludeAccounts2,true)."\r\n"); 
             
        if ($ableAccounts){
            foreach($accountAll as $account){
                $account['is_upload'] = true;
                if( in_array($account['id'], $ableAccounts) ){
                    $account['is_upload'] = false;
                }
                $accountInfo[$account['id']] = $account;
            }
        }
        return $accountInfo;
    }

    /**
     * 通过SKU在待刊登表查询列表记录（条件：除失败外），判断可刊登操作
     * @param  int $sku
     * @return array
     */
    public function getListingPrepareUploadBySku($sku, $siteId = null, $listingType = null){
        return $this->dbConnection->createCommand()
        ->select('*')
        ->from(self::tableName())
        ->where('sku = "'.$sku.'"')
        ->andWhere('status != '.self::STATUS_FAILURE)
        ->andWhere($siteId === null ? "1" : "site_id='{$siteId}'")
        ->andWhere($siteId === null ? "1" : (is_array($listingType) ? array("IN", 'listing_type', $listingType) : "listing_type='{$listingType}'"))
        ->andWhere("item_status!=".EbayProduct::STATUS_OFFLINE)
        ->queryAll();
    }  

    public function getPrepareAccountIdArr($sku, $siteId = null, $listingType = null){
        return $this->dbConnection->createCommand()
                    ->selectDistinct('account_id')
                    ->from($this->tableName())
                    ->where('sku = "'.$sku.'"')
                    ->andWhere('status != '.self::STATUS_FAILURE)
                    ->andWhere($siteId === null ? "1" : "site_id='{$siteId}'")
                    ->andWhere($siteId === null ? "1" : (is_array($listingType) ? array("IN", 'listing_type', $listingType) : "listing_type='{$listingType}'"))
                    ->andWhere("item_status!=".EbayProduct::STATUS_OFFLINE)
                    ->queryColumn();
    }  

	//获取待刊登并且是item_status=-1的
	public function getPrepareAccountIdArr2($sku, $siteId = null, $listingType = null){
		return $this->dbConnection->createCommand()
			->selectDistinct('account_id')
			->from($this->tableName())
			->where('sku = "'.$sku.'"')
			->andWhere(array("NOT IN", 'status', array(self::STATUS_SUCCESS,self::STATUS_FAILURE)))
			->andWhere($siteId === null ? "1" : "site_id='{$siteId}'")
			->andWhere($siteId === null ? "1" : (is_array($listingType) ? array("IN", 'listing_type', $listingType) : "listing_type='{$listingType}'"))
			->andWhere("item_status=-1")
			->queryColumn();
	}

	/**
     * @desc 根据时间段来匹配可以刊登的站点id
     * @return multitype:multitype:multitype:string
     */
    public function matchSiteIdByTime($hour = ""){
    	$siteTimes = array(
    			//美国
    			'0'=> array(
    					array('start'	=>	'03:00',  'end'=>'07:00'),
    					array('start'	=>	'10:00',  'end'=>'13:00'),
    			),
    	
    			//CA
    			'2'=> array(
    					array('start'	=>	'00:00',  'end'=>'04:00'),
    					array('start'	=>	'07:00',  'end'=>'10:00'),
    					
    			),
    			//UK
    			'3'=> array(
    					array('start'	=>	'02:00',  'end'=>'05:00'),
    					array('start'	=>	'19:00',  'end'=>'23:00'),
    			),
    			//au
    			'15'=> array(
    					array('start'	=>	'08:00',  'end'=>'12:00'),
    					array('start'	=>	'15:00',  'end'=>'18:00'),
    					
    			),
    			//FR
    			'71'=> array(
    					array('start'	=>	'01:00',  'end'=>'04:00'),
    					array('start'	=>	'18:00',  'end'=>'22:00'),
    			),
    			//DE
    			'77'=> array(
    					array('start'	=>	'01:00',  'end'=>'04:00'),
    					array('start'	=>	'18:00',  'end'=>'22:00'),
    			),
    			
    			//ES
    			'186' => array(
    					array('start'	=>	'14:00',  'end'=>'17:00'),
    			),
    	);
    	if($hour){
    		$nowHour = $hour;
    	}else{
    		$nowHour = date("H:i");
    	}
    	$matchSiteIds =array();
    	foreach ($siteTimes as $siteId=>$times){
    		foreach ($times as $time){
    			if($nowHour >= $time['start'] && $nowHour < $time['end']){
    				$matchSiteIds[] = $siteId;
    				break;
    			}
    		}
    	}
    	return $matchSiteIds;
    }
    
    
    public function getExcludeShipCountryBySite($siteId = '0'){
    	$shipCountry = EbayCategoryInfo::model()->getExcludeShippingLocationPairs($siteId);
    	unset($shipCountry['Worldwide']);
    	$excludeCountry = array();
    	if($siteId == 77){
    		//德国 只发德国
    		
    		foreach ($shipCountry as $countrys){
    			foreach ($countrys as $country){
    				if($country['code'] == 'DE'){
    					continue;
    				}
    				$excludeCountry[] = $country['code'];
    			}
    		}
    		
    	}else{    		
    		unset($shipCountry['South America']);
    		unset($shipCountry['Oceania']);
    		unset($shipCountry['North America']);
    		unset($shipCountry['Central America and Caribbean']);
    		
    		$filterCountry[] = "HK";
    		$filterCountry[] = "MO";
    		$filterCountry[] = "IQ";
    		$filterCountry[] = "KW";
    		$filterCountry[] = "CN";
    		$filterCountry[] = "LA";
    		$filterCountry[] = "KH";
    		$filterCountry[] = "DE";
    		$filterCountry[] = "AT";
    		$filterCountry[] = "CH";
    		$filterCountry[] = "JE";
    		
    		$filterContinents = array("Africa");
    		foreach ($shipCountry as $key=>$countrys){
    			foreach ($countrys as $country){
	    			if(in_array($country['code'], $filterCountry) || in_array($key, $filterContinents)){
	    				$excludeCountry[] = $country['code'];
	    			}
	    			
    			}
    		}
    	}
    	return $excludeCountry;
    }

    /**
     * @desc 指定销售账号复制刊登
     * 指定账号把A站点在线listing复制到B站点
     * 条件：1.SKU状态：在售中；2.目标站点不重复刊登；3.光明本地仓可用库存>-20
     * @param  int $fromAccountID  源账号ID
     * @param  int $fromSiteID 源站点ID  
     * @param  boolean $isAuction 是否拍卖
     * @param  int $toAccountID  目标账号ID
     * @param  int $toSiteID  目标站点ID
     * @param  array $toCustoms 指定其他字段, 如 ['listing_duration'=>'GTC']
     * @return boolean
     * @author yangsh
     * @since 2016-09-23
     */
    public function copyListing($fromAccountID,$fromSiteID,$isAuction,$toAccountID,$toSiteID,$toCustoms=null){       
        //1. 获取源站点listing数据
        if ($isAuction) {
            $listingType = array('Chinese');
        } else {
            $listingType = array('FixedPriceItem', 'StoresFixedPrice');
        }        
        $ebayProductModel = new EbayProduct();
        $list = $ebayProductModel->getListByCondition('item_id',"account_id='{$fromAccountID}' and site_id='{$fromSiteID}' and listing_type in('".implode("','", $listingType)."') and item_status=1 ");
        if (empty($list)) {
            $this->setErrorMessage('源站点无listing数据');
            return false;
        }
        $res = array();
        foreach ($list as $v) {
            try {
                //echo $v['item_id']." ###### start<br>";
                $this->copyListingByItemID($v['item_id'],$toAccountID,$toSiteID,$toCustoms);
                $res[$v['item_id']] = 'ok';
            } catch (Exception $e) {
                $res[$v['item_id']] = $e->getMessage();
            }
        }
        return $res;
    }

    /**
     * @desc 指定刊登号复制刊登
     * @param  string $itemID   
     * @param  int $toAccountID  目标账号ID
     * @param  int $toSiteID  目标站点ID
     * @param  array $toCustoms 指定其他字段, 如 ['listing_duration'=>'GTC']
     * @return boolean
     */
    public function copyListingByItemID($itemID,$toAccountID,$toSiteID,$toCustoms=null) { 
        $addLog                    = false;
        $nowtime                   = date("Y-m-d H:i:s");
        $userID                    = 0;//(int)Yii::app()->user->id;       
        $minAvailableQty           = -20;//最低可用库存
        $auctionStatus             = 0;//是否循环拍卖
        $auctionPlanDay            = 10;//循环拍周期
        $auctionRule               = 0;//拍卖规则
        $addType                   = self::ADD_TYPE_COPY;//复制刊登
        $configType                = self::CONFIG_TYPE_DEFAULT;//默认
        $ebayProductModel          = new EbayProduct();
        $ebayProductVariationModel = new EbayProductVariation();
        $ebayProductInfo           = $ebayProductModel->getOneByCondition("*","item_id='{$itemID}' ");
        if (empty($ebayProductInfo)) {
            throw new Exception($itemID.' '."itemID不存在");
        }
        $ebayProductVariants = $ebayProductVariationModel->getListByCondition("*", "listing_id={$ebayProductInfo['id']}");
        if (empty($ebayProductVariants)) {
            throw new Exception($itemID.' '."多属性表数据为空");
        }
        $fromAccountID    = $ebayProductInfo['account_id'];
        $fromSiteID       = $ebayProductInfo['site_id'];
        $sku              = $ebayProductInfo['sku'];//主sku
        $duration         = $ebayProductInfo['listing_duration'];
        $storeCategoryID  = $ebayProductInfo['store_category_id'];
        $storeCategoryID2 = 0;
        //指定字段值
        if (!empty($toCustoms)) {
            //指定listing_duration
            if (!empty($toCustoms['listing_duration'])) {
                $duration = $toCustoms['listing_duration'];
            }
        }     
        if (isset($toCustoms['configtype'])) {
            $configType = $toCustoms['configtype'];
        }
        
        $isMultiple = $ebayProductInfo['is_multiple'] == 1  ? true : false;
        $isAuction  = false;
        if ($isMultiple) {
            $listingType   = EbayProductAdd::LISTING_TYPE_VARIATION;
        } else {
			if (isset($toCustoms['listing_type'])) {
				$listingType = $toCustoms['listing_type'];
				if($listingType == EbayProductAdd::LISTING_TYPE_AUCTION){
					$isAuction = true;
				}
			}else{
				if ($ebayProductInfo['listing_type'] == 'Chinese' ) {
					$isAuction     = true;
					$listingType   = EbayProductAdd::LISTING_TYPE_AUCTION;
				} else {
					$listingType   = EbayProductAdd::LISTING_TYPE_FIXEDPRICE;
				}
			}
        }

        if( !$isAuction ) {
            //1、判断当前账号当前站点是否在产品管理里在线的
            $exists = $ebayProductModel->checkSKUExists($sku, $toAccountID, $toSiteID, true);
            if($exists){
                throw new Exception($sku.' '.Yii::t("ebay", "Had Upload This SKU"));
            }
            //2、判断当前账号当前站点是否在待刊登待上传（2天内）
            $exists = $this->checkSKUExists($sku, $toAccountID, $toSiteID, null, 2);
            if($exists){
                throw new Exception($sku.' '.Yii::t("ebay", "Had Upload This SKU"));
            }
        }
        //获取sku信息
        $skuInfo = Product::model()->getProductBySku($sku);
        //@todo 过滤掉停售下架、侵权的产品
        if (empty($skuInfo)) {
            throw new Exception($sku.' '.Yii::t('ebay', 'SKU has not Exists'));
        }
        //是否多属性子sku
        $isVariation  = (isset($skuInfo['product_is_multi']) && $skuInfo['product_is_multi'] == Product::PRODUCT_MULTIPLE_VARIATION) ? true : false;     
        //检测主sku是否侵权
        if(ProductInfringe::model()->getProductIfInfringe($sku)){
            throw new Exception($sku.' '.Yii::t('ebay', 'The SKU has been infringed, can not be uploaded to EBAY'));
        }
        
        $skuArr = array();
        foreach ($ebayProductVariants as $k => $v) {
            $skuArr[$v['sku']] = $k;
        }

        //检查SKU是否在售中
        //$productModel = new Product();
        // $statusInfos = $productModel->dbConnection->createCommand()
        //              ->select('sku,product_status')
        //              ->from($productModel->tableName())
        //              ->where("sku in('".implode("','", array_keys($skuArr) )."')")
        //              ->queryAll();
        // foreach ($statusInfos as $v) {
        //     if ($v['product_status'] != 4) {
        //         $addLog == true && MHelper::writefilelog('copyListing.txt',$v['sku'].'非在售中'."\r\n");
        //         unset($ebayProductVariants[ $skuArr[$v['sku']] ]);
        //     }
        // }

        //检查SKU可用库存>-20
        //$warehouseSkuMapModel = new WarehouseSkuMap();
        // $stockInfos = $warehouseSkuMapModel->dbConnection->createCommand()
        //              ->select('sku,available_qty')
        //              ->from($warehouseSkuMapModel->tableName())
        //              ->where("warehouse_id=41 and sku in('".implode("','", array_keys($skuArr) )."')")
        //              ->queryAll();
        // foreach ($stockInfos as $v) {
        //     if ($v['available_qty'] <= $minAvailableQty ) {
        //         $addLog == true && MHelper::writefilelog('copyListing.txt',$v['sku'].'可用库存:'.$v['available_qty']."\r\n");
        //         unset($ebayProductVariants[ $skuArr[$v['sku']] ]);
        //     }
        // }

        //排除带插头属性的sku, 属性值为刀具,
        // $productSelectAttribute = new ProductSelectAttribute();
        // $selAttrInfos = $productSelectAttribute->dbConnection->createCommand()
        //              ->select('sku')
        //              ->from($productSelectAttribute->tableName())
        //              ->where("sku in('".implode("','", array_keys($skuArr) )."')")
        //              ->andWhere("attribute_id=18 or attribute_value_id=6919")
        //              ->queryAll();
        // if (!empty($selAttrInfos)) {
        //     foreach ($selAttrInfos as $v) {
        //         $addLog == true && MHelper::writefilelog('copyListing.txt',$v['sku'].'带插头属性的sku,或属性值为刀具'."\r\n");
        //         unset($ebayProductVariants[ $skuArr[$v['sku']] ]);
        //     }
        // }

        //排除标题laser pen[指定sku]
        // $laserPenSkuList = array('80949.04','80949.05','80934.01','80934.02','80934.03','80949.01','80949.02','80949.03','80949.04','80949.05','81038.01','81038.02','81038.03','94880.01','94880.02','94880.03','94880.04','94880.05','94880.06','94880.01','94880.02','94880.03','94880.04','94880.05','94880.06','94882.01','94882.01','94882.02','94882.02','94882.03','94882.03','94882.04','94882.04','94882.05','94882.05','94882.01','94882.02','94882.03','94882.04','94882.05','94882.06','94882.06','94880.01','94880.02','94880.03','94880.04','94880.05','94880.06','94882.01','94882.02','94882.03','94882.04','94882.05','94882.06','94882.01','94882.02','94882.03','94882.04','94882.05','94882.06','80934.01','80934.02','80934.03','11173.01','11173.02','17766','4137.01','66619','66495','80934','79050','79043','79044','81038','80949','94882','94880','001897');
        // foreach ($ebayProductVariants as $v) {
        //     if (in_array($v['sku'],$laserPenSkuList)) {
        //         $addLog == true && MHelper::writefilelog('copyListing.txt',$v['sku'].'为激光笔'."\r\n");
        //         unset($ebayProductVariants[ $skuArr[$v['sku']] ]);
        //     }
        // }

        //有没有可复制刊登的数据?
        if (empty($ebayProductVariants) ) {
            throw new Exception($itemID .' '. "listing没有可复制刊登的数据" );
        }

        //取标题、描述
        $langCode = EbaySite::getLanguageBySiteIDs($fromSiteID);
        $productDesc = Productdesc::model()->getDescriptionInfoBySkuAndLanguageCode($sku, $langCode);
        //判断标题、描述是否为空
        if ( trim($productDesc['description']) == '' ) {
            throw new Exception($sku.' '.Yii::t('ebay', ' Title or Description is empty, can not be uploaded to EBAY'));
        }

        //先去掉原listing前后缀,再加上新站点的描述模板的前后缀
        $ffix = EbayProductDescriptionTemplate::model()->getTitleFix($fromAccountID, $fromSiteID);
        $title  = trim($ebayProductInfo['title']);//listing title
        if ( $ffix && $ffix['title_prefix'] != '' ) {
            $title = str_replace($ffix['title_prefix'], '', $title);
        }
        if ( $ffix && $ffix['title_suffix'] != '' ) {
            $title = str_replace($ffix['title_suffix'], '', $title);
        }

        //models
        $ebayProductAddSpecificModel = new EbayProductAddSpecific();
        $ebayProductAddShippingModel = new EbayProductAddShipping();
        $ebayProductVariationModel   = new EbayProductVariation();
        $encryptSku                  = new encryptSku();
        
        //分类信息
        $categoryID               = $categoryID2 = 0;
        $conditionID              = 1000;//NEW
        $variationPictureSpecific = "";//设置图片属性
        $categoryName             = "";
        $specialData              = array();//共有属性数据
        $variationAttributes      = array();//子sku属性数据
        $variationSkus            = array();//子SKU数据
        $addShippingInfo          = array();//物流模板数据
        $excludeShippingCountry   = array();//屏蔽发货国家数据
        $ebayCategory             = new EbayCategory();
        
        $variationPictureSpecific = $ebayProductInfo['variation_picture_specific'];
        $categoryID               = $ebayProductInfo['category_id'];
        $categoryID2              = $ebayProductInfo['category_id2'];
        $categoryName             = EbayCategory::model()->getCategoryNameByID($categoryID);

        //获取类目
        $cateList = $ebayCategory->getSuggestCategoryList($toAccountID, $toSiteID, $title, $sku);
        $firstcate = null;
        is_array($cateList) && $cateList && $firstcate = array_shift($cateList);
        if($firstcate){
            $categoryID = $firstcate['categoryid'];
            $categoryName = $firstcate['categoryname'];
        } 

        //获取specific
        $categorySpecifics = EbayCategoryInfo::model()->getCategorySpecifics($toAccountID, $toSiteID, $categoryID);
        //获取features
        $categoryFeatures = EbayCategoryInfo::model()->getCategoryFeatures($toAccountID, $toSiteID, $categoryID);
        //获取conditionID 
        if($categoryFeatures){
            if(isset($categoryFeatures['ConditionEnabled']) && ($categoryFeatures['ConditionEnabled'] == 'Enabled' || $categoryFeatures['ConditionEnabled'] == 'Required')){
                $specialNames = $this->getSpecialConditions();
                foreach ($categoryFeatures['ConditionValues']->Condition as $condition){
                    $conditionName = strtoupper(trim($condition->DisplayName));
                    if(in_array($conditionName, $specialNames)){
                        $conditionID = trim($condition->ID);
                        break;
                    }
                }
            }
        }
        if($categorySpecifics){
            foreach ($categorySpecifics as $special){
                $isRequired = isset($special->ValidationRules->MinValues) ? (int)$special->ValidationRules->MinValues : 0; //大于0必填
                if(!$isRequired){
                    continue;
                }
                //ebay有默认值跳过
                if((string)$special->ValidationRules->SelectionMode == 'Prefilled'){
                    continue;
                }
                $spcname = (string)$special->Name;
                //判断为输入型的
                if(!isset($special->ValueRecommendation)){
                    $specific = "Does not apply";
                }else{
                    //取第一条默认
                    if(isset($special->ValueRecommendation->Value)){//一个推荐值
                        $specific = (string)$special->ValueRecommendation->Value;
                    }else{
                        foreach ($specific->ValueRecommendation as $val){
                            if($spcname == 'Brand' && ((string)$val->Value == 'Unbranded/Generic' || (string)$val->value == 'Unbranded')){//@todo 可能需要根据每个站点来进行判断
                                $specific = (string)$val->Value;
                            }elseif($spcname == 'MPN' && ((string)$val->Value == 'Does Not Apply')){//@todo 可能需要根据每个站点来进行判断
                                $specific = (string)$val->Value;
                            }
                        }
                    }
                }
                //判断上下级关系
                $parentName = "";
                $parentVal = "";
                if(isset($special->ValidationRules->Relationship)){
                    $parentName = (string)$special->ValidationRules->Relationship->ParentName;
                    if(!empty($specialData[$parentName])){
                        $parentVal = $specialData[$parentName]['value'];
                    }else{
                        $parentVal = "";
                    }
                    foreach ($special->ValueRecommendation as $val){
                        if(!isset($val->ValidationRules) || !isset($val->ValidationRules->Relationship)){
                            $specific = (string)$val->Value;
                        }else{
                            foreach ($val->ValidationRules->Relationship as $v){
                                if((string)$v->ParentName == $parentName && (string)$v->ParentValue == $parentVal){
                                    $specific = (string)$val->Value;
                                    break;
                                } 
                            }
                        }
                    }
                    
                }
                $specialData[$spcname] = array(
                        'add_id'    =>  0,//@todo 插入时记得插入addID
                        'name'      =>  $spcname,
                        'value'     =>  $specific,
                        'custom'    =>  0
                );
            }
        }
        
        //多属性表
        if($ebayProductVariants){
            foreach ($ebayProductVariants as $variation){
                $variationSkus[$variation['sku']] = array(
                                                        'son_sku'         =>  $variation['sku'],
                                                        'son_seller_sku'  =>  $encryptSku->getEncryptSku($variation['sku']),
                                                        'add_id'          =>  0,
                                                        'variation_price' =>  0.00,
                                                        'variation_discount_price' =>  0.00
                                                    );
                if($variation['variation_specifics']){
                    $variationSpecifics = json_decode($variation['variation_specifics'], true);
                    foreach ($variationSpecifics as $key=>$value){
                        if(empty($variationPictureSpecific) || strtoupper($key) == "COLOR"){
                            $variationPictureSpecific = $key;
                        }
                        unset($specialData[$key]);
                        $variationAttributes[$variation['sku']][] = array(
                                'name'  =>  $key,
                                'value' =>  $value
                        );
                    }
                    
                }
                
            }
        }  

        //根据模板选择物流方式
        $wareHouseID = EbayAccountSite::model()->getWarehouseByAccountSite($toAccountID, $toSiteID);//找出对应的仓库ID
        $attributeInfo = EbayProductAttributeTemplate::model()->getProductAttributeTemplage("site_id='".$toSiteID."' AND abroad_warehouse = '".$wareHouseID."' AND config_type = ".$configType);
        if(empty($attributeInfo)){
            throw new Exception(Yii::t('ebay', 'Not Find Attribute Template'));
        }
        $shippingTemplate = EbayProductShippingTemplate::model()->getShippingTemplateListByPid($attributeInfo['id']);
        if($shippingTemplate){
            foreach ($shippingTemplate as $template){
                $template['ship_cost'] = "0";
                $template['additional_ship_cost'] = "0";
                unset($template['id'], $template['pid']);
                $addShippingInfo[] = $template;
            }
        }

        //主表数据
        //图片数据和子sku图片
        //多属性子sku数据
        //特殊属性数据
        //物流数据
        //屏蔽国家
        //默认排除国家
        $defaultExcludeCountryCode = $defaultExcludeCountryNameStr = "";
        $defaultExcludeCountry = EbayExcludeShipingCountry::model()->getExcludeShipingCountry($toSiteID, $toAccountID);
        if($defaultExcludeCountry){
            $defaultExcludeCountryCode = $defaultExcludeCountry['exclude_ship_code'];
            $defaultExcludeCountryNameStr = $defaultExcludeCountry['exclude_ship_name'];
        }
        
        $detail = $this->getDescriptionNoImg($sku, $toAccountID, $toSiteID);
        $currency = EbaySite::getCurrencyBySiteID($toSiteID);
        
        $auctionShipingCost = array();
        //计算价格
        $salePrice = 0;
        if($listingType == EbayProductAdd::LISTING_TYPE_FIXEDPRICE){
            $salePriceData = EbayProductSalePriceConfig::model()->getSalePrice($sku, $currency, $toSiteID, $toAccountID, $categoryName);
            if($salePriceData && $salePriceData['salePrice'] > 0){
                $salePrice = $salePriceData['salePrice'];
            }else{
                throw new Exception("SalePrice Error");
            }
			//有传入折扣率，计算打折初始价
			if (isset($toCustoms['discount_rate'])) {
				$originPrice = sprintf("%.2f",$salePrice/$toCustoms['discount_rate']);
			}
        }elseif($listingType == EbayProductAdd::LISTING_TYPE_AUCTION){
            $salePriceData = $this->getAuctionPriceByAuctionRule($auctionRule, $sku, $currency, $toSiteID, $toAccountID, $categoryName);
            if($salePriceData && $salePriceData['auction_price'] > 0){
                $salePrice = $salePriceData['auction_price'];
                $auctionShipingCost[$sku] = $salePriceData['shiping_cost'];
            }else{
                $salePrice = 0.01;
                $auctionShipingCost[$sku] = 0;
                throw new Exception("SalePrice Error");
            }
			//有传入折扣率，计算打折初始价
			if (isset($toCustoms['discount_rate'])) {
				$originPrice = sprintf("%.2f",$salePrice/$toCustoms['discount_rate']);
			}
        }elseif($listingType == EbayProductAdd::LISTING_TYPE_VARIATION && $variationSkus){
            foreach ($variationSkus as $key=>$variation){
                $salePriceData = EbayProductSalePriceConfig::model()->getSalePrice($variation['son_sku'], $currency, $toSiteID, $toAccountID, $categoryName);
                if($salePriceData && $salePriceData['salePrice'] > 0){
                    $variationSkus[$key]['variation_price'] = $salePriceData['salePrice'];
                }else{
                    throw new Exception("SalePrice Error");
                }
				//有传入折扣率，计算打折初始价
				if (isset($toCustoms['discount_rate'])) {
					$variationSkus[$key]['variation_discount_price'] = sprintf("%.2f",$variationSkus[$key]['variation_price']/$toCustoms['discount_rate']);
				}
            }                           
        }else{
            $salePriceData = EbayProductSalePriceConfig::model()->getSalePrice($sku, $currency, $toSiteID, $toAccountID, $categoryName);
            if($salePriceData && $salePriceData['salePrice'] > 0){
                $salePrice = $salePriceData['salePrice'];
            }else{
                throw new Exception("SalePrice Error");
            }
			if (isset($toCustoms['discount_rate'])) {
				$originPrice = sprintf("%.2f",$salePrice/$toCustoms['discount_rate']);
			}
        }
        //GTIN UPC ISBN EAN
        // ================= START:入库操作 ================ //
        $dbtransaction = $this->getDbConnection()->getCurrentTransaction();
        if(!$dbtransaction){
            $dbtransaction = $this->getDbConnection()->beginTransaction();
        }
        try{
            $defaultGTIN   = 'Does not apply';
            $defaultGTIN   = $this->getDefaultGTIN($toSiteID);
            $listingDetail = array(
                    'brand' =>  $defaultGTIN, 
                    'mpn'   =>  $defaultGTIN, 
                    'upc'   =>  $defaultGTIN, 
                    'isbn'  =>  $defaultGTIN, 
                    'ean'   =>  $defaultGTIN
            );
            $gtinkeys = array('UPC', 'EAN', 'ISBN', 'GTIN');

            //title长度处理
            $ffix2 = EbayProductDescriptionTemplate::model()->getTitleFix($toAccountID, $toSiteID);
            $ffixlen2 = 0;//前后缀长度
            if ( $ffix2 && $ffix2['title_prefix'] != '' ) {
                $ffixlen2 += mb_strlen($ffix2['title_prefix'])+1;//1为空格
            }
            if ( $ffix2 && $ffix2['title_suffix'] != '' ) {
                $ffixlen2 += mb_strlen($ffix2['title_suffix'])+1;
            }            
            $titleLen = mb_strlen($title);
            if ( $titleLen + $ffixlen2 > 80) {
                $title = mb_substr($title,0, $titleLen - $ffixlen2 );
            }
            $toTitle = EbayProductDescriptionTemplate::model()->getTitle($title, $toAccountID, $toSiteID);
            $toTitle = mb_substr($toTitle, 0, 80);

            //组装数据
            $addData = array(
                    'site_id'                    =>  intval($toSiteID),
                    'account_id'                 =>  intval($toAccountID),
                    'sku'                        =>  $sku,
                    'seller_sku'                 =>  $encryptSku->getEncryptSku($sku),
                    'listing_type'               =>  intval($listingType),
                    'title'                      =>  $toTitle,
                    'start_price'                =>  $salePrice,
					'discount_price'             =>  isset($originPrice)?$originPrice:0.00,
                    'currency'                   =>  $currency,
                    'category_id'                =>  intval($categoryID),
                    'category_id2'               =>  intval($categoryID2),
                    'store_category_id'          =>  intval($storeCategoryID),
                    'store_category_id2'         =>  intval($storeCategoryID2),
                    'condition_id'               =>  intval($conditionID),
                    'config_type'                =>  intval($configType),
                    'variation_picture_specific' =>  $variationPictureSpecific,
                    'auction_rule'               =>  intval($auctionRule),
                    'listing_duration'           =>  empty($duration) ? 'GTC' : $duration,
                    'brand'                      =>  $listingDetail['brand'],
                    'mpn'                        =>  $listingDetail['mpn'],
                    'upc'                        =>  $listingDetail['upc'],
                    'isbn'                       =>  $listingDetail['isbn'],
                    'ean'                        =>  $listingDetail['ean'],
                    'upload_msg'                 =>  '',
                    'uuid'                       =>  '',
                    'item_id'                    =>  0,
                    'create_user_id'             =>  intval($userID),
                    'update_user_id'             =>  intval($userID),
                    'create_time'                =>  $nowtime,
                    'update_time'                =>  $nowtime,
                    'last_response_time'         =>  $nowtime,
                    'status'                     =>  EbayProductAdd::STATUS_PENDING,
                    'exclude_ship_code'          =>  $defaultExcludeCountryCode,
                    'exclude_ship_name'          =>  $defaultExcludeCountryNameStr,
                    'detail'                     =>  $detail,
                    'add_type'                   =>  intval($addType)
            );
            
            $ebayProductImageAddModel              = new EbayProductImageAdd();
            $ebayProductAddVariationModel          = new EbayProductAddVariation();
            $ebayProductAddVariationAttributeModel = new EbayProductAddVariationAttribute();
            $addInsertID                           = 0;
            $this->getDbConnection()->createCommand()->insert($this->tableName(), $addData);
            $addInsertID = $this->getDbConnection()->getLastInsertID();
    
            //保存物流信息
            if($addShippingInfo){
                foreach ($addShippingInfo as $shipping){
                    $shipping['add_id'] = $addInsertID;
                    if($listingType == EbayProductAdd::LISTING_TYPE_AUCTION){
                        $shipping['ship_cost'] = isset($auctionShipingCost[$sku]) ? $auctionShipingCost[$sku] : 0;
                    }
					//美国站点低于5美金本地物流方式,排除海外仓帐号
					if(!in_array($toAccountID,EbayAccount::$OVERSEAS_ACCOUNT_ID)){
						if($shipping['shipping_type']==1 && $shipping['site_id']==0){
							if($salePrice < 5 && $currency=='USD'){
								$shipping['shipping_service'] = 'EconomyShippingFromOutsideUS';
								$shipping['additional_cost'] = 0;
								$shipping['cost_type'] = '';
							}
						}
					}
                    $ebayProductAddShippingModel->saveData($shipping);
                }
            }

            //主图片
            $ebayProductImageAddModel->addProductImageBySku2($sku, $toAccountID, $toSiteID);

            //子sku
            if($isMultiple && $variationSkus){
                foreach ($variationSkus as $variationData){
                    $variationSKU = $variationData['son_sku'];
                    $ebayProductImageAddModel->addProductImageByVariationSku($variationSKU, $toAccountID, $toSiteID);
                    //添加子sku
                    $variationData['add_id'] = $addInsertID;
                    unset($variationData['id']);
                    $ebayProductAddVariationModel->getDbConnection()->createCommand()->insert($ebayProductAddVariationModel->tableName(), $variationData);
                    $variationID = $ebayProductAddVariationModel->getDbConnection()->getLastInsertID();
                    //添加子sku属性
                    $addVariationAttributes = array();
                    //GTIN(upc isbn ean mpn)
                    //variation_id  add_id name value
                    foreach ($gtinkeys as $gtinkey){
                        $addVariationAttributes[] = array(
                                'variation_id'  =>  $variationID,
                                'add_id'        =>  $addInsertID,
                                'name'          =>  $gtinkey,
                                'value'         =>  $defaultGTIN
                        );
                    }
                    //其他属性
                    $variationValue = $variationAttributes[$variationSKU];
                    foreach ($variationValue as $value){
                        unset($specialData[$value['name']]);
                        $addVariationAttributes[] = array(
                                'variation_id'  =>  $variationID,
                                'add_id'        =>  $addInsertID,
                                'name'          =>  $value['name'],
                                'value'         =>  $value['value']
                        );
                    }
                    //添加子SKU属性
                    if($addVariationAttributes){
                        foreach ($addVariationAttributes as $attribute){
                            $ebayProductAddVariationAttributeModel->getDbConnection()->createCommand()->insert($ebayProductAddVariationAttributeModel->tableName(), $attribute);
                        }
                    }
                }
            }

			//复制specific，不同站点复制后数据库添加了数据，编辑打开的数据是不一样的，因为模版不同,重新编辑保存数据模版没有的会被过滤
			$fromSpecificList = $this->getDbConnection()->createCommand()
				->from($this->tableName() . " as t")
				->leftJoin(EbayProductAddSpecific::model()->tableName()." as p", "p.add_id=t.id")
				->select("p.*")
				->where('t.item_id = '.$itemID)
				->queryAll();
			$copySpecialData = array();
			if($fromSpecificList){
				foreach($fromSpecificList as $fromSpecificInfo){
					$copySpecialData[$fromSpecificInfo['name']] = array(
						'add_id'    =>  0,
						'name'      =>  $fromSpecificInfo['name'],
						'value'     =>  $fromSpecificInfo['value'],
						'custom'    =>  $fromSpecificInfo['custom'],
					);
				}
				$specialData = array_merge($specialData,$copySpecialData);
			}

            //保存共有属性
            if($specialData){
                foreach ($specialData as $specific){
                    $specific['add_id'] = $addInsertID;
                    $ebayProductAddSpecificModel->getDbConnection()->createCommand()->insert($ebayProductAddSpecificModel->tableName(), $specific);
                }
            }
             
            if($listingType == EbayProductAdd::LISTING_TYPE_AUCTION){
                // ============== Start:写入拍卖表 =================
                $auctionStartTime = date("Y-m-d H:i:s");
                if($auctionPlanDay){
                    $auctionEndTime = date("Y-m-d H:i:s", strtotime("+ ". ($auctionPlanDay * 4) . "day"));
                }else{
                    $auctionEndTime = date("Y-m-d H:i:s", strtotime("+6 month"));
                }
                $auctionData = array(
                        'add_id'        =>  $addInsertID,
                        'start_time'    =>  $auctionStartTime,
                        'end_time'      =>  $auctionEndTime,
                        'plan_day'      =>  intval($auctionPlanDay),
                        'auction_status'=>  intval($auctionStatus),
                        'update_time'   =>  $nowtime,
                        'pid'           =>  0,
                        'count'         =>  0
                );
                $res = EbayProductAddAuction::model()->saveData($auctionData);
                if(!$res){
                    throw  new Exception("Save Auction Info Failure!!!");
                }
                // =============== End:写入拍卖表 ===================
            }
     
            $dbtransaction->commit();
            //MHelper::writefilelog("ebay/ebayBatchAdd-".$accountID.".log", date("Y-m-d H:i:s") . "\t\tSKU:{$sku}\t\tAccountID:{$accountID}\t\tSiteID:{$siteID}\t\tMessage: Success\r\n");
            // ========== 推送SKU ===========
            $pushskus[] = $sku;
            if($variationSkus){
                $variationSkus = array_keys($variationSkus);
                $pushskus = array_merge($pushskus, $variationSkus);
            }
            $url = Yii::app()->request->hostInfo."/ebay/ebayproductadd/pushskutoimg/account_id/{$toAccountID}/sku/".implode(",", $pushskus)."/site_id/{$toSiteID}/status/0";
            MHelper::runThreadBySocket ( $url );
            // ========= 推送END ==========
        }catch (Exception $e){
            if($dbtransaction){
                $dbtransaction->rollback();
            }
            throw new Exception($e->getMessage());
        }
        return true;
    }

    /**
     * @desc 为图片待上传状态的记录重置upload_count为0
     */
    public function resetUploadCount() {
        $responseTime = date("Y-m-d",strtotime("-10 minutes"));
        $this->getDbConnection()->createCommand()->update($this->tableName(), array('status'=>1,'upload_count'=>0),"status=5 and upload_count>=3 and last_response_time <'{$responseTime}'");
    }


    /**
     * @desc 根据条件获取多条数据
     * @param unknown $fields
     * @param unknown $conditions
     * @param string $param
     * @return mixed
     */
    public function getProductAddInfoAll($fields, $conditions, $param = null){
        return $this->getDbConnection()->createCommand()
                                ->select($fields)
                                ->from(self::tableName())
                                ->where($conditions, $param)
                                ->queryAll();
    }


	/**
	 * @param string $fields
	 * @param string $where
	 * @param string $order
	 * @return mixed
	 */
	public function getAllByCondition($fields='*', $where='1',$order='')
	{
		$sql = "SELECT {$fields} FROM ".$this->tableName()." WHERE {$where} ";
		$cmd = $this->dbConnection->createCommand($sql);
		return $cmd->queryAll();
	}


    /**
     * @param $start_time
     * @param $end_time
     * @return array|CDbDataReader
     *
     * 返回批量刊登或者复制无销售人员id对应的账号站点列表
     */
	public function productAddAccountSiteData($start_time, $end_time)
    {
        $date_time = date('Y-m-d', strtotime($start_time));
        $sql = "SELECT DISTINCT(account_id) AS account_id, site_id FROM ".$this->tableName()." WHERE 1
                AND listing_type IN(2,3) 
                AND create_time BETWEEN '{$start_time}'	AND '{$end_time}'
                AND id NOT IN (
                    SELECT add_id FROM ueb_ebay_task_history_listing WHERE 1 AND date_time = '{$date_time}'
                ) AND create_user_id = 0 GROUP BY account_id, site_id;";
        $cmd = $this->dbConnection->createCommand($sql);
        return $cmd->queryAll();
    }

}