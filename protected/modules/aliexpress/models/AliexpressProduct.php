<?php
/**
 * @desc 速卖通产品MODEL
 * @author zhangf
 *
 */
class AliexpressProduct extends AliexpressModel {
	
	public static $aliexpressAccountPairsData = null;
	public $offline	= null;
	public $account_name = null;
	public $status_text = null;
	public $detail = null;
	public $itemurl = null;
	public $modify_stock = null;
	public $product_category_path = null;
	public $no_auto_offline_time = null;	//手动下架时间
	public $seller_name;
	public $commission_rate;
	
	
	/** @var integer 账号ID */
	protected $_accountID = '';
	
	/** @var string 错误信息 */
	protected $_errorMessage = '';
	
	const EVENT_NAME = 'get_product';
	const EVENT_UPDATE_NAME = 'update_product';
	const CURRENCY_CODE = 'USD';
	
	const VARIATION_YES = 1;//多属性
	const VARIATION_NO  = 0;//单一属性
	
	const PRODUCT_STATUS_ONSELLING 		 = 'onSelling' ;  //'onSelling'
	const PRODUCT_STATUS_OFFLINE 		 = 'offline';	  //'offline'
	const PRODUCT_STATUS_AUDITING		 = 'auditing' ;	//'auditing'
	const PRODUCT_STATUS_EDITINGREQUIRED = 'editingRequired' ;  //'editingRequired'
	


	/**
	 * (non-PHPdoc)
	 * @see CActiveRecord::tableName()
	 */
	public function tableName() {
		return 'ueb_aliexpress_product';
	}
	
	
	public static function model($className = __CLASS__) {
		return parent::model($className);		
	}

	public function rules() {
		return array(
			array('account_id, aliexpress_product_id, subject, reduce_strategy, product_unit, ws_offline_date, sizechart_id, package_length, package_width, package_height, gmt_create, gmt_modified, ws_display, is_image_dynamic, category_id, owner_member_id, product_status_type, gross_weight,delivery_time, product_price, src, product_min_price, product_max_price, create_user_id, create_time, modify_user_id, modfiy_time, freight_template_id', 'safe'),
		);
	}	
	
	public function setAccountID($accountID) {
		$this->_accountID = $accountID;
	}

	/**
	 * [getOneByCondition description]
	 * @param  string $fields [description]
	 * @param  string $where  [description]
	 * @param  mixed $order  [description]
	 * @return [type]         [description]
	 * @author yangsh
	 */
	public function getOneByCondition($fields='*', $where='1',$order='') {
		$cmd = $this->dbConnection->createCommand();
		$cmd->select($fields)
			->from(self::tableName())
			->where($where);
		$order != '' && $cmd->order($order);
		$cmd->limit(1);
		return $cmd->queryRow();
	}

	/**
	 * [getListByCondition description]
	 * @param  string $fields [description]
	 * @param  string $where  [description]
	 * @param  mixed $order  [description]
	 * @return [type]         [description]
	 * @author yangsh
	 */
	public function getListByCondition($fields='*', $where='1',$order='',$group='') {
		$cmd = $this->dbConnection->createCommand();
		$cmd->select($fields)
			->from(self::tableName())
			->where($where);
		$group != '' && $cmd->group($group);
		$order != '' && $cmd->order($order);
		return $cmd->queryAll();
	}	

	/**
	 * @desc 保存aliexpress产品数据
	 * @param mixed $productInfo
	 * @throws Exception
	 * @return boolean
	 * @author yangsh
	 * @since 2016-06-13	 
	 */
	public function saveAliProductInfo($accountId, $product, $productInfo, $aliProductInfo) {
		try {
			$nowDateTime        = date('Y-m-d H:i:s');
			$userID             = isset(Yii::app()->user->id) ? Yii::app()->user->id : 0;
			$aliexpresProductID = $productInfo->productId;
			$productData 		= array(
					'account_id'            => $accountId,
					'aliexpress_product_id' => $productInfo->productId,
					'subject'               => $productInfo->subject,
					'reduce_strategy'       => $productInfo->reduceStrategy,
					'product_unit'          => $productInfo->productUnit,
					'ws_offline_date'       => MHelper::aliexpressTimeToBJTime($productInfo->wsOfflineDate),
					'sizechart_id'          => isset($productInfo->sizechartId) ? $productInfo->sizechartId : 0,
					'package_length'        => isset($productInfo->packageLength) ? $productInfo->packageLength : 0,
					'package_width'         => isset($productInfo->packageWidth) ? $productInfo->packageWidth : 0,
					'package_height'        => isset($productInfo->packageHeight) ? $productInfo->packageHeight : 0,
					'ws_display'            => $productInfo->wsDisplay,
					'category_id'           => $productInfo->categoryId,
					'image_urls'            => $productInfo->imageURLs,
					'product_status_type'   => $productInfo->productStatusType,
					'gross_weight'          => $productInfo->grossWeight,
					'delivery_time'         => $productInfo->deliveryTime,
					'product_price'	        => $productInfo->productPrice,
					'gmt_create'            => MHelper::aliexpressTimeToBJTime($product->gmtCreate),
					'gmt_modified'          => MHelper::aliexpressTimeToBJTime($product->gmtModified),
					'src'                   => isset($product->src) ? $product->src : '',
					'package_type'          => ($productInfo->packageType ? 1 : 0),
					'lot_num'               => $productInfo->lotNum,
					'freight_template_id'   => $productInfo->freightTemplateId,
					'promise_template_id'   => $productInfo->promiseTemplateId,
					'modify_time'			=> $nowDateTime,
					'modify_user_id'		=> $userID,
			);
			
			if (isset($productInfo->isPackSell)){
				$productData['is_pack_sell']   = ($productInfo->isPackSell ? 1 : 0);
			}
			if (isset($productInfo->addUnit)){
				$productData['add_unit']       = $productInfo->addUnit;
			}
			if (isset($productInfo->addWeight)){
				$productData['add_weight']     = $productInfo->addWeight;
			}
			if (isset($productInfo->baseUnit)){
				$productData['base_unit']      = $productInfo->baseUnit;
			}
			if (isset($productInfo->bulkOrder)) {
				$productData['bulk_order']     = $productInfo->bulkOrder;
			}
			if (isset($productInfo->bulkDiscount)) {
				$productData['bulk_discount']  = $productInfo->bulkDiscount;
			}

			//判断是否存在listing
			if (empty($aliProductInfo))  {
				$productData['create_time']    = $nowDateTime;
				$productData['create_user_id'] = $userID;
				$isOk = $this->dbConnection->createCommand()->insert(self::tableName(), $productData);
				if (!$isOk) {
					throw new Exception("Save ProductInfo Error--$aliexpresProductID");
				}
				$rowId = $this->dbConnection->getLastInsertID();
			} else {//更新产品表
				$this->dbConnection->createCommand()->update(self::tableName(), $productData, 'id = '.$aliProductInfo['id'] );//
				$rowId = $aliProductInfo['id'];
			}
			unset($productData);

			//商品的SKU信息
			$aeopAeProductSKUs 	= $productInfo->aeopAeProductSKUs;
			$encryptSku 		= new encryptSku();
			$firstOnlineSku 	= $aeopAeProductSKUs[0]->skuCode;//Sku商家编码
			$firstSku 			= $encryptSku->getAliRealSku($firstOnlineSku);
			$productStock 		= 0;//$aeopAeProductSKUs[0]->ipmSkuStock;//实际库存字段
			$currencyCode 		= $aeopAeProductSKUs[0]->currencyCode;//币种,USD/RUB
			$productPrice 		= $aeopAeProductSKUs[0]->skuPrice;//Sku价格
			$skuID 				= $aeopAeProductSKUs[0]->id == '<none>' ? '' : $aeopAeProductSKUs[0]->id;//SKU ID
			if (empty($firstSku)){
				$firstSku 		= $firstOnlineSku;
			}
			$mainProductData	= array(
				'sku'           => $firstSku,
				'online_sku'    => $firstOnlineSku,
				'product_stock' => $productStock,
				'product_price' => $productPrice,
				'is_variation'  => 0,
				'sku_id'        => $skuID,
			);
			$skuInfo 			= Product::model()->getProductInfoBySku($firstSku);
			if (!empty($skuInfo) && Product::PRODUCT_MULTIPLE_VARIATION == $skuInfo['product_is_multi'] ) {
				//如果是组合里面子sku，则查找出主sku
				$mainSku 		= ProductSelectAttribute::model()->getMainSku(null, $firstSku);
				$mainProductData['sku'] 			= $mainSku;
				$mainProductData['online_sku'] 		= $mainSku;
				$mainProductData['is_variation'] 	= 1;
			}
			$this->dbConnection->createCommand()->update(self::tableName(), $mainProductData, "id = $rowId");
			unset($mainProductData);
			return $rowId;
		} catch (Exception $e) {
			$this->setErrorMessage($e->getMessage());
			return false;
		}
	}	
	
	/**
	 * @desc 拉取账号产品数据
	 * @return boolean
	 */
	public function getAccountProducts($params = array()) {
		$accountID = $this->_accountID;
		$currentPage = 1;
		$hasNextPage = true;
		try {
			$request = new FindProductInfoListQueryRequest();
			if (isset($params['product_status_type']) && !empty($params['product_status_type']))
				$request->setProductStatusType($params['product_status_type']);
			else
				$request->setProductStatusType(FindProductInfoListQueryRequest::PRODUCT_STATUS_ONSELLING);
			if (isset($params['page_size']) && !empty($params['page_size']))
				$request->setPageSize($params['page_size']);
			if (isset($params['product_id']) && !empty($params['product_id']))
				$request->setProductId($params['product_id']);
			$tokenLoopNum = 0;
			//拉取所有页数
			while ($hasNextPage) {
				$request->setPage($currentPage);
				$response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
				if (!$request->getIfSuccess()) {
					if($tokenLoopNum < 2 && $request->getErrorMsg() == 'Request need user authorized'){
						sleep(11*60);//休眠11分钟，等待token同步过来
						$tokenLoopNum++;
						continue;
					}
					$this->setErrorMessage($request->getErrorMsg());
					return false;
				}
				$tokenLoopNum = 0;//恢复
				$productList = isset($response->aeopAEProductDisplayDTOList) ? $response->aeopAEProductDisplayDTOList : array();
				$totalPage = (int)$response->totalPage;
				$currentPage++;
				if ($currentPage > $totalPage)
					$hasNextPage = false;
				foreach ($productList as $product) {
					//循环取出每个产品的产品详情
					$productID = $product->productId;
					$productInfo = $this->getProductInfo($productID);
					if ($productInfo == false){
                                            $this->setErrorMessage($productID . ' get detail fail');
                                            continue;
                                            //return false;
                                        }
					//保存产品数据
					$flag = $this->saveProductInfo($product, $productInfo);
					if (!$flag){
                                            $this->setErrorMessage('save fail');
						return false;
                                        }
					//return $flag;
				}
			}
			return true;
		} catch (Exception $e) {
			$this->setErrorMessage($e->getMessage());
			return false;
		}
	}
	
	/**
	 * @desc 通过接口获取指定productId的产品数据
	 * @param string $productID
	 * @return boolean|mixed
	 */
	public function getProductInfo($productID) {
		$accountID = $this->_accountID;
		if (empty($accountID)) {
			$this->setErrorMessage(Yii::t('aliexpress_product', 'No Account ID'));
			return false;
		}
		$request = new FindAeProductByIdRequest();
		$request->setProductId($productID);
		$response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
		if (!$request->getIfSuccess()) {
			$this->setErrorMessage($request->getErrorMsg());
			return false;
		}
		return $response;
	}
	
	/**
	 * @desc 保存aliexpress产品数据
	 * @param mixed $productInfo
	 * @throws Exception
	 * @return boolean
	 */
	public function saveProductInfo($product, $productInfo) {
		if (empty($product) || empty($productInfo)) return false;
		$productData = array();
		$dbTransaction = $this->getDbConnection()->getCurrentTransaction();
		if (empty($dbTransaction)) {
			$dbTransaction = $this->getDbConnection()->beginTransaction();
		}
		try {
			$aliexpressProduct = new AliexpressProduct();
			$userID = isset(Yii::app()->user->id) ? Yii::app()->user->id : 0;
			$aliexpresProductID = $productInfo->productId;
			//检查当前账号该产品是否存在，存在则更新原数据
			$aliexpressProductInfo = $this->find("account_id = :account_id and aliexpress_product_id = :product_id", array(':account_id' => $this->_accountID, ':product_id' => $aliexpresProductID));
			$productID = null;
			$aliexpressProduct->isNewRecord = true;
			//$isNewRecord = true;
			if (!empty($aliexpressProductInfo)) {
				$aliexpressProduct->isNewRecord = false;
				$aliexpressProduct->id = $aliexpressProductInfo->id;
				$productID = $aliexpressProductInfo->id;
				//删除产品对应的扩展表
				AliexpressProductExtend::model()->deleteByProductID($aliexpressProductInfo->id);
				//删除产品SKU表
				AliexpressProductVariation::model()->deleteByProductID($aliexpressProductInfo->id);
			}
			$productData = array(
					'id' => $productID,
					'account_id' => $this->_accountID,
					'aliexpress_product_id' => $productInfo->productId,
					'subject' => $productInfo->subject,
					'reduce_strategy' => $productInfo->reduceStrategy,
					'product_unit' => $productInfo->productUnit,
					'ws_offline_date' => MHelper::aliexpressTimeToBJTime($productInfo->wsOfflineDate),
					'sizechart_id' => isset($productInfo->sizechartId) ? $productInfo->sizechartId : 0,
					'package_length' => isset($productInfo->packageLength) ? $productInfo->packageLength : 0,
					'package_width' => isset($productInfo->packageWidth) ? $productInfo->packageWidth : 0,
					'package_height' => isset($productInfo->packageHeight) ? $productInfo->packageHeight : 0,
					'ws_display' => $productInfo->wsDisplay,
					'category_id' => $productInfo->categoryId,
					'image_urls' => $productInfo->imageURLs,
					'product_status_type' => $productInfo->productStatusType,
					'gross_weight' => $productInfo->grossWeight,
					'delivery_time' => $productInfo->deliveryTime,
					'product_price'	=> $productInfo->productPrice,
					'gmt_create' => MHelper::aliexpressTimeToBJTime($product->gmtCreate),
					'gmt_modified' => MHelper::aliexpressTimeToBJTime($product->gmtModified),
					'src' => isset($product->src) ? $product->src : '',
					'package_type' => ($productInfo->packageType ? 1 : 0),
					'lot_num' => $productInfo->lotNum,
					'freight_template_id' => $productInfo->freightTemplateId,
					'promise_template_id' => $productInfo->promiseTemplateId,
			);
			
			if (isset($productInfo->isPackSell))
				$productData['is_pack_sell'] = ($productInfo->isPackSell ? 1 : 0);
			if (isset($productInfo->addUnit))
				$productData['add_unit'] = $productInfo->addUnit;
			if (isset($productInfo->addWeight))
				$productData['add_weight'] = $productInfo->addWeight;
			if (isset($productInfo->baseUnit))
				$productData['base_unit'] = $productInfo->baseUnit;
			if (isset($productInfo->bulkOrder))
				$productData['bulk_order'] = $productInfo->bulkOrder;
			if (isset($productInfo->bulkDiscount))
				$productData['bulk_discount'] = $productInfo->bulkDiscount;
			
			$productData['modify_time'] = date('Y-m-d H:i:s');
			$productData['modify_user_id'] = $userID;
			if (empty($aliexpressProductInfo))  {
				$productData['create_time'] = date('Y-m-d H:i:s');
				$productData['create_user_id'] = $userID;		
			}
			$aliexpressProduct->setAttributes($productData, false);
			//插入或者更新数据
			$flag = $aliexpressProduct->save(false);
			if (!$flag) {
				throw new Exception(Yii::t('aliexpress_product', 'Save Product Failure'));
			}
			$productID = $aliexpressProduct->id;
			
			$aeopAeProductSKUs = $productInfo->aeopAeProductSKUs;
			$encryptSku = new encryptSku();
			//取第一个SKU的数据, 判断是单品还是组和品
			$firstOnlineSku = $aeopAeProductSKUs[0]->skuCode;
			$firstSku = $encryptSku->getAliRealSku($firstOnlineSku);
			$productStock = $aeopAeProductSKUs[0]->ipmSkuStock;
			$currencyCode = $aeopAeProductSKUs[0]->currencyCode;
			$productPrice = $aeopAeProductSKUs[0]->skuPrice;
			$skuID = $aeopAeProductSKUs[0]->id;
			if (empty($firstSku))
				$firstSku = $firstOnlineSku;
			$skuInfo = Product::model()->getProductInfoBySku($firstSku);
			$mainProductData = array();
			$productStock = 0;
			if (!empty($skuInfo) && $skuInfo['product_is_multi'] == Product::PRODUCT_MULTIPLE_VARIATION) {
				//如果是组合里面子sku，则查找出主sku
				$mainSku = ProductSelectAttribute::model()->getMainSku(null, $firstSku);
				$mainProductData = array(
						'sku' => $mainSku,
						'online_sku' => $mainSku,
						'product_stock' => $productStock,
						'product_price' => $productPrice,
						'is_variation' => 1,
						'sku_id' => $skuID,
				);
				$skuProperty = '';
			} else {
				$mainProductData = array(
						'sku' => $firstSku,
						'online_sku' => $firstOnlineSku,
						'product_stock' => $productStock,
						'product_price' => $productPrice,
						'is_variation' => 0,
						'sku_id' => $skuID,
				);
				if (isset($aeopAeProductSKUs[0]->aeopAeProductPropertys))
					$skuProperty = json_encode($aeopAeProductSKUs[0]->aeopAeProductPropertys);
				else
					$skuProperty = '';
				//unset($aeopAeProductSKUs[0]);
			}
			$productProperty = array();
			if (isset($productInfo->aeopAeProductPropertys))
				$productProperty = $productInfo->aeopAeProductPropertys;
			//插入扩展表
			$productExtendData = array(
					'product_id' => $productID,
					'detail' => $productInfo->detail,
					//'keywords' => $productInfo->keyword,
					'image_urls' => $productInfo->imageURLs,
					'product_property' => json_encode($productProperty),
					'sku_property' => $skuProperty,
			);
/* 			if (isset($productInfo->productMoreKeywords1))
				$productExtendData['product_more_keywords1'] = $productInfo->productMoreKeywords1;
			if (isset($productInfo->productMoreKeywords2))
				$productExtendData['product_more_keywords2'] = $productInfo->productMoreKeywords2; */
			$aliexpressProductExtend = new AliexpressProductExtend();
			$aliexpressProductExtend->setAttributes($productExtendData, false);
			$flag = $aliexpressProductExtend->save(false);
			if (!$flag) {
				throw new Exception(Yii::t('aliexpress_product', 'Save Product Extend Failure'));
			}
			
			//插入多属性产品表			
			foreach ($aeopAeProductSKUs as $aeopAeProductSKU) {
				$aliexpressProductVariation = new AliexpressProductVariation();
				$skuParams = array();
				$aliexpressSku = $aeopAeProductSKU->skuCode;
				$sku = $encryptSku->getAliRealSku($aliexpressSku);
				if (empty($sku))
					$sku = $aliexpressSku;
				$productStock += $aeopAeProductSKU->skuStock;
				$skuParams = array(
						'product_id' => $productID,
						'ipm_sku_stock' => $aeopAeProductSKU->ipmSkuStock,
						'sku_code' => $aliexpressSku,
						'sku' => $sku,
						'sku_price' => $aeopAeProductSKU->skuPrice,
						'sku_stock' => intval($aeopAeProductSKU->skuStock),
						'sku_id' => $aeopAeProductSKU->id,
						'sku_property' => json_encode($aeopAeProductSKU->aeopSKUProperty),
						'profit_rate'	=>	0.00
						//'profit_rate' => round(floatval(self::getAliexpressSkuProfitRate($sku, $aeopAeProductSKU->skuPrice)), 2),
				);
				$aliexpressProductVariation->setAttributes($skuParams, false);
				$flag = $aliexpressProductVariation->save(false);
				if (!$flag) {
					throw new Exception(Yii::t('aliexpress_product', 'Save Product Variation Failure'));
				}
			}
			$mainProductData['product_stock'] = $productStock;
			$this->dbConnection->createCommand()->update(self::tableName(), $mainProductData, "id = $productID");
			$dbTransaction->commit();
			return true;
		} catch (Exception $e) {
			$dbTransaction->rollback();
			$this->setErrorMessage($e->getMessage());
			return false;
		}
	}
	
	/**
	 * @desc 设置错误信息
	 * @param sting $message
	 */
	public function setErrorMessage($message) {
		$this->_errorMessage .= $message;
	}
	
	/**
	 * @desc 获取错误信息
	 * @return string
	 */
	public function getErrorMessage() {
		return $this->_errorMessage;
	}
	
	/**
	 * @desc 根据条件查找在线广告
	 * @param unknown $param
	 */
	public function getProductByParam($param){
		if( empty($param) ){
			return array();
		}else{
			$sql = $this->dbConnection->createCommand()->select('*')->from(self::tableName())->where('1=1');
			foreach($param as $col=>$item){
				$sql = $sql->andWhere($col.' = "'.$item.'"');
			}
			return $sql->queryAll();
		}
	}
	
	/**
	 * @desc 计算sku的毛利率
	 * @param unknown $sku
	 * @param unknown $salePrice
	 * @return Ambigous <boolean, unknown, number>
	 */
	public function getAliexpressSkuProfitRate($sku, $salePrice) {
		$priceCal = new CurrencyCalculate();
		$priceCal->setCurrency(self::CURRENCY_CODE);//币种
		$priceCal->setPlatform(Platform::CODE_ALIEXPRESS);//设置销售平台
		$priceCal->setSku($sku);//设置sku
		$priceCal->setSalePrice($salePrice);
		return $priceCal->getProfitRate();
	}
	
	/**
	 * @desc 获取SKU对应的在线listing
	 * @param unknown $sku
	 * @param string $accountID
	 * @return Ambigous <multitype:, mixed>
	 */
	public function getOnlineListingBySku($sku, $accountID = null) {
			$command = $this->getDbConnection()->createCommand()
			->from(self::tableName() . " t")
			->select("t.*, t1.sku")
			->join("ueb_aliexpress_product_variation t1", "t.id = t1.product_id")
			->where("t.sku=:sku", array(':sku' => $sku));
			if (!is_null($accountID))
				$command->andWhere("t.account_id = :account_id", array(':account_id' => $accountID));
			$result1 = $command->queryAll();
			$command = $this->getDbConnection()->createCommand()
					->from(self::tableName() . " t")
					->select("t.*, t1.sku")
					->join("ueb_aliexpress_product_variation t1", "t.id = t1.product_id")
					->where("t1.sku=:sku", array(':sku' => $sku));
			if (!is_null($accountID))
				$command->andWhere("t.account_id = :account_id", array(':account_id' => $accountID));
			$result2 = $command->queryAll();
			if(!$result1) $result1 = array();
			if(!$result2) $result2 = array();
			return array_merge($result1, $result2);
 	}
	
	/**
	 * @desc 获取sku历史分类数据
	 * @param string $sku
	 * @return multitype:|multitype:NULL
	 */
	public function getSkuHistoryCategory($sku = '') {
		$categoryIds = array();
		$historyCategory = array();
		if (empty($sku)) return array();
		//查找在线listing的当前分类
		$onlineListing = self::getOnlineListingBySku($sku);
		if (!empty($onlineListing)) {
			foreach ($onlineListing as $listing) {
				if (!in_array($listing['category_id'], $categoryIds))
					$categoryIds[] = $listing['category_id'];
			}
		}
		//查找待刊登列表里面SKU的分类
		$publishList = AliexpressProductAdd::model()->getPublishListBySku($sku);
		if (!empty($publishList)) {
			foreach ($publishList as $list) {
				if (!in_array($list['category_id'], $publishList))
					$categoryIds[] = $list['category_id'];
			}
		}
		//查找分类数据
		if (!empty($categoryIds)) {
			foreach ($categoryIds as $categoryId) {
				$historyCategory[$categoryId] = AliexpressCategory::model()->getBreadcrumbCnAndEn($categoryId);
			}
		}
		return $historyCategory;
	}
	

	
	/**
	 * @desc 通过productID 获取产品属性
	 * @param int $productID
	 * @return array
	 */
	public function getVariationByproductID($productID){
		$data = $this->getDbConnection()->createCommand()
		->select('is_variation')
		->from($this->tableName())
		->where('product_id = "'.$productID.'"')
		->qureyRow();
		return $data['is_variation'];
	}
	
	/**
	 * @desc 通过productID 判断产品为单属性还是多属性
	 * @param int $productID
	 * @return array
	 */
	public function isVariation($variation){
		if ($variation == self::VARIATION_YES) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * @desc 产品下线
	 * @return array
	 */
	public function saveOffline($productId,$sku=''){
			if(isset($sku) && isset($productId)){
				$data = array(
						//'ws_display'   => self::PRODUCT_STATUS_OFFLINE,
						'aliexpress_product_id' => $productId,
						'sku'					=> $sku,
						'product_status_type'   => self::PRODUCT_STATUS_OFFLINE,
						'ws_offline_date'	    => date('Y-m-d H:i:s'),
				);
				$flag =	$this->dbConnection->createCommand()->update(self::tableName(), $data, array('aliexpress_product_id' =>$productId,'sku'=>$sku));
			} else {
	            $data = array(
	            		//'ws_display'   => self::PRODUCT_STATUS_OFFLINE,
	            		'aliexpress_product_id' => $productId,
	            		'product_status_type'   => self::PRODUCT_STATUS_OFFLINE,
	            		'ws_offline_date'	    => date('Y-m-d H:i:s'),
	            );
	            $flag = $this->saveRecord($data);
			}
			return $flag;
	}
	
	/**
	 * @desc 更新信息
	 */
	public function saveRecord($params){
		return $this->dbConnection->createCommand()->replace(self::tableName(), $params);
	}
	
	// ====================== add by lihy in The 2015-10-27 ===========
	/**
	 * @desc 设置搜索条件
	 * @return multitype:multitype:string multitype:string   multitype:string NULL
	 */
	public function filterOptions(){
		$result	= array(
				array(
						'name' => 'sku',
						'type' => 'text',
						'search' => 'LIKE',
						'alias'	=>	'v',	
						'htmlOption' => array(
								'size' => '22',
						),
				),

				array(
						'name' => 'sku2',
						'type' => 'text',
						'search' => '=',
						'htmlOption' => array(
								'size' => '22',
						),
						'rel'	=>	true
				),
			
				array(
						'name' => 'account_id',
						'type' => 'dropDownList',
						'alias'	=>	't',
						'data' => $this->getAliexpressAccountPairsList(),
						'search' => '=',
				),
				array(
						'name'	=>	'product_status_type',
						'type'	=>	'dropDownList',
						'alias'	=>	't',
						'data'	=>	$this->getAliexpressProductStatusTypeOptions(),
						'search'	=>	'='	
							
				),
                array(
	                    'name'          => 'gmt_create',
	                    'type'          => 'text',
	                    'alias' 		=> 't',
	                    'search'        => 'RANGE',
	                    'htmlOptions'   => array(
	                                    'class'    => 'date',
	                                    'dateFmt'  => 'yyyy-MM-dd HH:mm:ss',
	                    ),
                ),
                array(
						'name' => 'ipm_sku_stock',
						'type' => 'text',
		                                        'alias'	=> 'v',
						'search' => '=',
						'htmlOption' => array(
								'size' => '22',
						),
				),
				array(
						'name'	=>	'is_sku_stock',
						'type'	=>	'dropDownList',
						'data'	=>	array(1=>'无库存', 2=>'有库存'),
						'search'	=>	'=',
						'rel'	=>	true
				),
				array(
					'name' 			=> 'sku_price',
					'type' 			=> 'text',
					'search' 		=> 'RANGE',
					'htmlOptions'	=> array(
						'size' => 4,
					),
					'rel' 			=> true,
				),
				array(
					'name' 			=> 'sku_attribute',
					'type'			=> 'dropDownList',
					'data'			=>	ProductAttributeMap::model()->getAttributeValListArray(3),
					'search'		=>	'=',
					'rel' 			=> true,
				),		
				array(
						'id'	=>	'modify_stock',
						'name' => 'modify_stock',
						'type' => 'text',
						'search' => '=',
						'htmlOption' => array(
								'size' => '22',
						),
						'rel'	=>	true
				),
				array(
						'name'	=>	'commission_rate',
						'type'	=>	'dropDownList',
						'data'	=>	array('-1' => '空', 5 => '5%', 8 => '8%'),
						'search'	=>	'=',
						'rel'	=>	true
				),
    		    array(
						'name'   => 'category_id',
						'type'   => 'text',
						'search' => '=',
						'htmlOptions' => array(
							  'id'       => 'category_id_JS',
							  'size'     => '22',
							  'onclick'  => 'modifyProductCategory()',
							  'readonly' => 'readonly',
						),
    		            'rel'	=>	true
    		    ),	
    		    array(
    		        'name' => 'subject',
    		        'type' => 'text',
    		        'search' => 'LIKE',
    		        //'alias'	=>	'v',
    		        'htmlOption' => array(
    		            'size' => '22',
    		        ),
    		    ),		    
		);    
		return $result;
	}
	
	/**
	 * @desc 获取速卖通产品状态选项列表
	 * @return array
	 */
	public function getAliexpressProductStatusTypeOptions(){
		return array(
						'onSelling'=>Yii::t('aliexpress_product', 'Product Status Active'),
						'offline'	=>	Yii::t('aliexpress_product', 'Product Status Inactive'),
						'auditing'=>Yii::t('aliexpress_product', 'Product Status Auditing'),
						'editingRequired'	=>Yii::t('aliexpress_product', 'Product Status EditingRequired')
					);
	}
	/**
	 * @desc 获取下线操作单元格html
	 * @param unknown $status
	 * @param unknown $id
	 * @return string
	 */
	public function getOprationList($status, $id,$offlineArray = array()){
	    $temp = '';
	    $str = "<a style=\"display:none;\" 
    	           href=\"/aliexpress/aliexpressproductadd/updateonline/id/{$offlineArray['aliexpress_product_id']}/type/{$offlineArray['publish_type']}\" 
    	           target=\"navTab\" 
    	           id=\"ajun_{$offlineArray['aliexpress_product_id']}_0\" 
    	           rel=\"Ajun{$offlineArray['aliexpress_product_id']}\">修改</a>";
	    isset($offlineArray['aliexpress_product_id']) && $temp .= " productID='{$offlineArray['aliexpress_product_id']}' ";
	    isset($offlineArray['publish_type']) && $temp .= " publishType='{$offlineArray['publish_type']}' ";
		$str .= "<select style='width:75px;' onchange = 'offLine(this,".$id.")' {$temp} >
				<option>".Yii::t('system', 'Please Select')."</option>";
		if($status == 'onSelling'){//未下线
			$str .= "<option value='offline'>".Yii::t('system', 'Off Line')."</option>";
		}
		elseif($status == 'offline'){
			$str .= "<option value='onselling'>".Yii::t('aliexpress_product', 'On Selling')."</option>";
		}
		$str .= "<option value='modifyOnline'>修改</option>";
		$str .="</select>";
		return $str;
	}
	/**
	 * @desc 获取速卖通账户对列表
	 */
	public function getAliexpressAccountPairsList(){
		if(!self::$aliexpressAccountPairsData){
			$user_id = isset(Yii::app()->user->id)?Yii::app()->user->id:0;
			$idArr = AliexpressAccountSeller::model()->getListByCondition('account_id','seller_user_id = '.$user_id);
			if($idArr){
				self::$aliexpressAccountPairsData = self::model('AliexpressAccount')->getIdNamePairsByIdArr($idArr);
			}else{
				self::$aliexpressAccountPairsData = self::model('AliexpressAccount')->getIdNamePairs();
			}
		}
		return self::$aliexpressAccountPairsData;
	}
	
	public function getCreateUserOptions(){
		return UebModel::model('user')
		->queryPairs('id,user_full_name', "department_id=4");   //aliexpress部门
	}
	
	/**
	 * @desc 为前台列表添加额外数据
	 * @param unknown $datas
	 * @return unknown
	 */
	public function addition($datas){
		/**
		 * 传过来的数据已经被group by sku
		 * 组装数据
		 * */
		$sellerUserList = User::model()->getPairs();
		//ueb_aliexpress_account
		foreach ($datas as $key=>$data){
			
			$datas[$key]['account_name'] = isset(self::$aliexpressAccountPairsData[$data['account_id']]) ? self::$aliexpressAccountPairsData[$data['account_id']] : '-';
			$datas[$key]['itemurl'] = "<a href='http://www.aliexpress.com/item//{$data['aliexpress_product_id']}.html' target='__blank'>{$data['aliexpress_product_id']}</a>";
			$datas[$key]['status_text'] = $this->getProductStatusTypeStr($data['product_status_type']);
			

			//查询出佣金比例
			$commissionRateValue = '';
			$aliCategoryCommissionRate = new AliexpressCategoryCommissionRate();
			$commissionCategoryArr = array();
			$commissionRateArr = array();
			$commissionCategoryInfo = $aliCategoryCommissionRate->getListByCondition('category_id,commission_rate','id > 0');
			if($commissionCategoryInfo){
				foreach ($commissionCategoryInfo as $commCateInfo) {
					$commissionCategoryArr[] = $commCateInfo['category_id'];
					$commissionRateArr[$commCateInfo['category_id']] = $commCateInfo['commission_rate'];
				}
			}

			//取出产品栏目的一级分类ID
			$aliCategory = new AliexpressCategory();
			$levelOneCategory = $aliCategory->getTopCategory($data['category_id']);
			if($levelOneCategory == 36){
				$towCategoryId = $aliCategory->getTwoCategory($data['category_id']);
	            if($towCategoryId){
	                $levelOneCategory = $towCategoryId;
	            }
			}

			if(in_array($levelOneCategory, $commissionCategoryArr)){
				$commissionRateValue = $commissionRateArr[$levelOneCategory].'%';
			}
			

			//手动下架时间：如果listing是下架的，直接取更新时间（因为下架后，更新时间是不会再变化的）
			$datas[$key]['no_auto_offline_time'] = ($data['product_status_type'] == self::PRODUCT_STATUS_OFFLINE) ? $data['modify_time'] : '';

			//分类路径
			// $category_info = AliexpressCategory::model()->getCategotyInfoByID($data['category_id']);
			$category_info = AliexpressCategory::model()->getBreadcrumbCategoryCnEn($data['category_id'],'->');
			$datas[$key]['product_category_path'] = !empty($category_info) ? $category_info : '';
			
			$datas[$key]->detail =  array();
			//获取每个sku对应的全部数据
			$_datas = $this->getVariationListBySearchCondition($data['sku'], $data['account_id'], $data['id']);
			$publish_type = 1;
			if($_datas){
				foreach ($_datas as &$val){
					$productSellerRelationInfo = AliexpressProductSellerRelation::model()->getProductSellerRelationInfoByItemIdandSKU($val['aliexpress_product_id'], $val['sku'], $val['sku_code']);
					//if($data['is_variation']){
						$onclick = "changeVariation('{$val['id']}')";
						$val['modify_stock'] = '<input type="text" style="width:80px;" id="variation_'.$val['id'].'"><a href="javascript:void(0);" onclick="'.$onclick.'">'. ("保存") .'</a>';
					//}else{
						//$val['modify_stock'] = '-';
					//}
					



					$val['commission_rate'] = $commissionRateValue;





					
					$result_available = WarehouseSkuMap::model()->getAvailableBySkuAndWarehouse($val['sku'], WarehouseSkuMap::WARE_HOUSE_GM);
                    $val['system_available'] = $result_available;
                    //$val['seller_name'] = isset()
                    $sellerName = $productSellerRelationInfo && isset($sellerUserList[$productSellerRelationInfo['seller_id']]) ? $sellerUserList[$productSellerRelationInfo['seller_id']] : '-';
                    $val['seller_name'] = $sellerName;
				}
				$datas[$key]->detail = $_datas;
				if (count($_datas) > 1) $publish_type = 2;
			}
			$offlineArray = array();
			$offlineArray['aliexpress_product_id'] = $data['aliexpress_product_id'];
			$offlineArray['publish_type'] = $publish_type;
			$datas[$key]['offline'] = $this->getOprationList($data['product_status_type'], $data['id'],$offlineArray);
		}
		return $datas;
	}
	/**
	 * @DESC 获取子sku列表
	 * @param unknown $sku
	 * @param unknown $accountID
	 * @param unknown $listingID
	 * @return mixed
	 */
	public function getVariationListBySearchCondition($sku, $accountID, $listingID){
		$criteria = new CDbCriteria();
		$criteria->addCondition("m.id = '" . $listingID . "'" );
		$criteria->addCondition("t.product_id = '" . $listingID . "'" );
		/* if (isset($_REQUEST['product_status_type']) && !empty($_REQUEST['product_status_type']))
			$criteria->addCondition("m.product_status_type = '" . $_REQUEST['product_status_type']."'"); */
		/* if (isset($_REQUEST['account_id']) && !empty($_REQUEST['account_id']) )
			$criteria->addCondition("m.account_id = " . (int)$_REQUEST['account_id']);
		elseif ($accountID){
			$criteria->addCondition("m.account_id = " . (int)$accountID);
		} */
		//is_sku_stock
		if (isset($_REQUEST['is_sku_stock']) && !empty($_REQUEST['is_sku_stock'])){
			$isSkuStock = $_REQUEST['is_sku_stock'];
			if($isSkuStock == 1){
				$criteria->addCondition("t.ipm_sku_stock = 0");
			}else if($isSkuStock == 2){
				$criteria->addCondition("t.ipm_sku_stock > 0");
			}	
		}

		$DbCommand = $this->getDbConnection()
							->createCommand()
							->from(AliexpressProductVariation::model()->tableName(). ' t')
							->leftJoin(self::tableName() . ' m', 'm.id=t.product_id')
							->select("t.*")
							->where($criteria->condition);
		return $DbCommand->queryAll();
	}
	/**
	 * @desc 根据搜索条件获取对应的数据
	 * @param string $sku
	 * @return mixed
	 */
	public function getSkuListingBySearchCondition($sku = '', $accountId = 0) {
		$criteria = new CDbCriteria();
		$criteria->addCondition("sku = '" . $sku . "'" );
		if (isset($_REQUEST['product_status_type']) && !empty($_REQUEST['product_status_type']))
			$criteria->addCondition("product_status_type = '" . $_REQUEST['product_status_type']."'");
		if (isset($_REQUEST['account_id']) && !empty($_REQUEST['account_id']) )
			$criteria->addCondition("account_id = " . (int)$_REQUEST['account_id']);
		elseif ($accountId){
			$criteria->addCondition("account_id = " . (int)$accountId);
		}
		$DbCommand = $this->getDbConnection()->createCommand()
							->from(self::tableName())
							->select("*")
							->where($criteria->condition);
		return $DbCommand->queryAll();
	}
	/**
	 * @desc 获取速卖通产品状态
	 * @param string $productStatusType
	 * @return string
	 */
	private function getProductStatusTypeStr($productStatusType = ''){
		$msg = Yii::t('aliexpress_product', 'Product Status Inactive');
		$color = "red";
		switch ($productStatusType){
			case 'onSelling':
				$color = "green";
				$msg = Yii::t('aliexpress_product', 'Product Status Active');
				break;
			case 'onOffline':
				$msg = Yii::t('aliexpress_product', 'Product Status Inactive');
				break;
			case 'auditing':
				$msg = Yii::t('aliexpress_product', 'Product Status Auditing');
				break;
			case 'editingRequired':
				$msg = Yii::t('aliexpress_product', 'Product Status EditingRequired');
				break;
			default:
				
		}
		$options = "<font color='{$color}'>".$msg."</font>";
		return $options;
	}
	/**
	 * @desc 提供搜索方法
	 * @see UebModel::search()
	 */
	public function search(){
		$sort = new CSort();
		$sort->attributes = array(
				'defaultOrder' => 'id',
		);
		$dataProvider = parent::search(get_class($this), $sort, '', $this->_setDbCriteria());
		$datas = $this->addition($dataProvider->data);
		$dataProvider->setData($datas);
		return $dataProvider;
	}
	/**
	 * @desc 设置数据结构
	 * @return CDbCriteria
	 */
	protected function _setDbCriteria() {
        $join = false;
		$criteria = new CDbCriteria();
		$criteria->select = "t.*";
		$account_id = '';
		$accountIdArr = array();
		if(isset(Yii::app()->user->id)){
			$accountIdArr = AliexpressAccountSeller::model()->getListByCondition('account_id','seller_user_id = '.Yii::app()->user->id);
		}

		if (isset($_REQUEST['account_id']) && !empty($_REQUEST['account_id']) ){
			$account_id = (int)$_REQUEST['account_id'];
		}

		if($accountIdArr && !in_array($account_id, $accountIdArr)){
			$account_id = implode(',', $accountIdArr);
		}

		if($account_id){
			$criteria->addCondition("t.account_id IN(".$account_id.")");
		}

		if (isset($_REQUEST['sku']) && !empty($_REQUEST['sku']) ){
                        $join = true;
			//$criteria->addCondition("v.sku = " . $_REQUEST['sku']);
                }
		//$criteria->group = "t.sku";
		
		if(isset($_REQUEST['sku2']) && !empty($_REQUEST['sku2'])){
			$join = true;
			$criteria->addCondition("t.sku = " . $_REQUEST['sku2']);
		}
		
		if(isset($_REQUEST['category_id']) && !empty($_REQUEST['category_id'])){
		    $join = true;
		    $criteria->addCondition("t.category_id = " . $_REQUEST['category_id']);
		}

		if (isset($_REQUEST['is_sku_stock']) && !empty($_REQUEST['is_sku_stock']) ){
                        $join = true;
			$isSkuStock = $_REQUEST['is_sku_stock'];
			//$criteria->join = " LEFT JOIN ".AliexpressProductVariation::model()->tableName() . " as v on v.product_id=t.id";
			if($isSkuStock == 1){
				$criteria->addCondition("v.ipm_sku_stock=0");
			}elseif($isSkuStock == 2){
				$criteria->addCondition("v.ipm_sku_stock>0");
			}
			//$criteria->group = "t.aliexpress_product_id";
		}

		

        if (isset($_REQUEST['ipm_sku_stock']) && !empty($_REQUEST['ipm_sku_stock']) ){
                        $join = true;
			$ipm_sku_stock = $_REQUEST['ipm_sku_stock'];
			//$criteria->join = " LEFT JOIN ".AliexpressProductVariation::model()->tableName() . " as v on v.product_id=t.id";
			
			$criteria->addCondition("v.ipm_sku_stock='{$ipm_sku_stock}'");
			
			//$criteria->group = "t.aliexpress_product_id";
		}

		//查询产品价格
		if(!empty($_REQUEST['sku_price'][0]) || !empty($_REQUEST['sku_price'][1])){
			$join = true;
			$minPrice = $_REQUEST['sku_price'][0];
			$maxPrice = $_REQUEST['sku_price'][1];
			if(!empty($_REQUEST['sku_price'][0])){
				$criteria->addCondition("v.sku_price >= {$minPrice} ");
			}
			
			if(!empty($_REQUEST['sku_price'][1])){
				$criteria->addCondition("v.sku_price <= {$maxPrice} ");
				$inProductId =  $this->getDbConnection()->createCommand()
										->select('t.id')
							    		->from(self::tableName() . " t")
							    		->join(AliexpressProductVariation::model()->tableName()." as v", "v.product_id=t.id")
							    		->where($criteria->condition)
							    		->group('t.id')
							    		->queryColumn();
				if($inProductId){
					$notInProductId =  $this->getDbConnection()->createCommand()
										->select('v.product_id')
							    		->from(AliexpressProductVariation::model()->tableName() . " v")
							    		->where(array("IN","v.product_id",$inProductId))
							    		->andWhere('v.sku_price > :sku_price',array(':sku_price'=>$maxPrice))
							    		->group('v.product_id')
							    		->queryColumn();
					$criteria->addNotInCondition("t.id", $notInProductId);
				}				
			}
		}

		//查询产品属性
		if(isset($_REQUEST['sku_attribute']) && !empty($_REQUEST['sku_attribute'])){
			$join = true;
			$skuArr = ProductSelectAttribute::model()->getProductIdByAttributeId($_REQUEST['sku_attribute']);
			if($skuArr){
				$criteria->addInCondition("v.sku", $skuArr);
			}
		}

		//查询佣金比例
		if(isset($_REQUEST['commission_rate']) && !empty($_REQUEST['commission_rate'])){
			//取出佣金表里所有类目
			$commissionCateArr = array();
			if($_REQUEST['commission_rate'] == '-1'){
				$commissionWhere = 'id > 0';
			}else{
				$commissionWhere = 'commission_rate = \''.$_REQUEST['commission_rate'].'\'';
			}

			$commissionInfo = AliexpressCategoryCommissionRate::model()->getListByCondition('category_id',$commissionWhere);
			if($commissionInfo){
				foreach ($commissionInfo as $commVal) {
					$commissionCateArr[] = $commVal['category_id'];
				}
			}

			//取出所有最小分类
			$categoryArr = array();
			$aliCategoryModel = new AliexpressCategory();
			$smallCategoryInfo = $aliCategoryModel->getListByCondition('category_id','is_leaf = 1');
			foreach ($smallCategoryInfo as $SmallCateVal) {
				//取出最顶级分类
				$topCategory = $aliCategoryModel->getTopCategory($SmallCateVal['category_id']);
				if($topCategory == 36){
					$topCategory = $aliCategoryModel->getTwoCategory($SmallCateVal['category_id']);
				}

				//通过顶级分类查询是否在佣金表里
				if(!in_array($topCategory, $commissionCateArr)){
					continue;
				}
				$categoryArr[] = $SmallCateVal['category_id'];
			}

			if($categoryArr){
				if($_REQUEST['commission_rate'] == '-1'){
					$criteria->addNotInCondition("t.category_id", $categoryArr);
				}else{
					$criteria->addInCondition("t.category_id", $categoryArr);
				}
			}
		}

        if($join){
            $criteria->join = " LEFT JOIN ".AliexpressProductVariation::model()->tableName() . " as v on v.product_id=t.id";
            $criteria->group = "t.aliexpress_product_id";
        }
		return $criteria;
	}
	/**
	 * @desc 设置属性列表
	 * @see CModel::attributeLabels()
	 */
	public function attributeLabels() {
		return array(
				'sku'                 => Yii::t('aliexpress', 'Sku'),
				'subject'             => Yii::t('aliexpress', 'Subject'),
				'online_sku'          => Yii::t('aliexpress_product', 'Online Sku'),
				'product_stock'       => Yii::t('aliexpress_product', 'Product Stock'),
				'product_price'       => Yii::t('aliexpress_product', 'Sku Price'),
				'account_name'        => Yii::t('aliexpress_product', 'Account Name'),
				'account_id'          => Yii::t('aliexpress_product', 'Account Name'),
				'listing_id'          => Yii::t('aliexpress', 'Id'),
				'id'                  => '',
				'product_status_type' => Yii::t('aliexpress_product', 'Status'),
				'status'              => Yii::t('aliexpress_product', 'Status'),
				'status_text'         => Yii::t('aliexpress_product', 'Status'),
				'offline'             => Yii::t('system', 'Oprator'),
				'gmt_create'          => Yii::t('aliexpress_product', 'Product Create Date'),
				'modify_stock'        => Yii::t('aliexpress_product', 'Modify Stock'),
				'is_sku_stock'        => Yii::t('aliexpress_product', 'Is SKU Stock'),
				'sku_code'            => Yii::t('aliexpress_product', 'Online Sku'),
				//'ipm_sku_stock'     => Yii::t('aliexpress_product', 'Product Stock'),
				'sku_price'           => Yii::t('aliexpress_product', 'Sku Price'),
				'product_id'          => '产品id',
				'system_available'    => '系统可用库存',
				'ipm_sku_stock'       => '后台库存',
				'create_time'         => '上架时间',
				'sku2'                => 'SKU全称',
				'no_auto_offline_time'        => Yii::t('aliexpress_product', 'No Auto Product Offline Date'),
				'product_category_path'    => Yii::t('aliexpress_product', 'Product Category Path'),
				'seller_name'	=>	Yii::t('common', 'Seller Name'),
				'freight_template_id' => '产品运费模板',
				'sku_attribute'       => '产品属性',
				'commission_rate'     => '佣金比例',
				'detail'              => '产品描述',
				'category_id'         => '分类ID',
		);
	}
	
	/**
	 * @desc 下架线上速卖通产品接口
	 * @param unknown $accountId
	 * @param unknown $productIds(接口最大支持50个产品ID)
	 * @return boolean
	 */
	public function offlineAliexpressProduct($accountId, $productIds){
		if(empty($accountId) || empty($productIds)) return false;
		try {
			if(is_array($productIds))
				$productIds = implode(";", $productIds);
			$request = new GetOfflineProductRequest();
			$request->setAccount($accountId)
								->setPrdouctID($productIds);
			$response = $request->setRequest()
								->sendRequest()
								->getResponse();
			if(!$request->getIfSuccess()){
				$this->setErrorMessage($request->getErrorMsg());
				return false;
			}
			return true;
		}catch (Exception $e){
			$this->setErrorMessage($e->getMessage());
			return false;
		}
	}
	/**
	 * @desc 上架线上速卖通产品操作接口
	 * @param unknown $accountId
	 * @param unknown $productIds
	 * @return boolean
	 */
	public function onSellingAliexpressProduct($accountId, $productIds){
		if(empty($accountId) || empty($productIds)) return false;
		try {
			if(is_array($productIds))
				$productIds = implode(";", $productIds);
			$request = new SetOnSellingRequest();
			$response = $request->setAccount($accountId)
					->setPrdouctID($productIds)
					->setRequest()
					->sendRequest()
					->getResponse();
			if(!$request->getIfSuccess()){
				$this->setErrorMessage($request->getErrorMsg());
				return false;
			}
			return true;
		}catch (Exception $e){
			$this->setErrorMessage($e->getMessage());
			return false;
		}
	}
	/**
	 * 根据主键id更新
	 * @param unknown $id
	 * @param unknown $updata
	 * @return boolean|Ambigous <number, boolean>
	 */
	public function updateProductByPk($id, $updata){
		if(empty($id) || empty($updata)) return false;
		return $this->dbConnection
					->createCommand()
					->update(self::tableName(), $updata, "id=:id", array("id"=>$id));
	}
	/**
	 * @desc 批量更新数据
	 * @param unknown $ids
	 * @param unknown $updata
	 * @return boolean
	 */
	public function batchUpdateProductByPk($ids, $updata){
		if(empty($ids) || empty($updata)) return false;
		return $this->dbConnection
			->createCommand()
			->update(self::tableName(), $updata, array('in', 'id', $ids));	
	}
	/**
	 * @desc 批量删除数据
	 * @param unknown $ids
	 * @return boolean|Ambigous <number, boolean>
	 */
	public function batchDeleteProduct($ids){
		if(empty($ids) ) return false;
		return $this->dbConnection
					->createCommand()
					->delete(self::tableName(), array('in', 'id', $ids));
	}
	// ========= add by lihy end ==========
	
	
	/**
	 * 通过sku数组和账号ID查询数据
	 * @param array $sku
	 * @param int   $accountId  账号
	 * @return array
	 */
	public function getProductListBySkuAndAccountId($sku, $accountId) {
		$command = $this->dbConnection->createCommand()
			->select('*')
			->from(self::tableName())
			->where(array("IN",'sku',$sku))
			->andWhere('account_id = :accountId', array(':accountId'=>$accountId));
		return $command->queryAll();		
	}


	/**
	 * @desc 通过账号ID批量更新模板ID
	 * @param integer $accountId
	 * @param unknown $updata
	 * @return boolean
	 */
	public function batchUpdateFreightTemplateIdByAccountId($attributes,$condition,$param){
		if(!$attributes || !$condition || !$param) return false;
		return $this->updateAll($attributes,$condition,$param);	
	}


	/**
     * @desc 页面的跳转链接地址
     */
    public static function getIndexNavTabId() {
        return Menu::model()->getIdByUrl('/aliexpress/aliexpressproduct/list');
    }


    /**
	 * @desc 查找sku产品列表信息
	 * @param string $sku
	 * @return array
	 */
	public function getProductListBySku($sku) {
		$command = $this->dbConnection->createCommand()
			->select('*')
			->from(self::tableName())
			->where('sku = :sku', array(':sku' => $sku));
		return $command->queryAll();		
	}


	/**
     * @desc 根据产品ID，修改标题
     * @param unknown $productId  产品ID
     * @param string  $subject    产品标题
     * @param string  $accountID  账号ID
     * @return boolean
     */
    public function reviseSubjectByProductID($productId, $subject, $accountID){
        $errormsg = "";

        try{
            
            if(empty($accountID)){
                throw new Exception("账号ID不能为空");
            }

            if(empty($productId)){
                throw new Exception("线上产品ID不能为空");
            }

            if(empty($subject)){
                throw new Exception("产品标题不能为空");
            }

            $request = new EditSimpleProductFiledRequest();
			$request->setProductID($productId);
			$request->setFiedName('subject');
			$request->setFiedValue($subject);
			$response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
			if(!$request->getIfSuccess()){
				$this->setErrorMessage($request->getErrorMsg());
				return false;
			}
			
            if($request->getIfSuccess()){
                $this->getDbConnection()
                    ->createCommand()
                    ->update($this->tableName(), array("subject"=>$subject), "aliexpress_product_id=".$productId);
            }
            return true;

        }catch (Exception $e){
            $this->setErrorMessage($e->getMessage());
            return  false;
        }
    }


    /**
     * @desc 修改单个sku库存为0
     * @param bigint $productID  速卖通平台产品ID
     * @param string $skuID      商品单个SKUID
     * @param int    $stock      单个sku库存
     * @param int    $accountID  账号ID
     * @return boolen
     */
    public function editSingleSkuStockByParam($productID, $skuID, $stock, $accountID){
    	$request = new EditSingleSkuStockRequest();
		$request->setProductID($productID);
		$request->setSkuID($skuID);
		$request->setIpmSkuStock($stock);
		$response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
		if($request->getIfSuccess()){
			return true;
		}else{
			$this->setErrorMessage($request->getErrorMsg());
			return false;
		}    
    }
}