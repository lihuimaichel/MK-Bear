<?php
class AliexpressListing extends AliexpressModel {
	
	protected $_accountID = '';
	
	public function tableName() {
		return 'ueb_aliexpress_listing';
	}

	public static function model($className = __CLASS__) {
		return parent::model($className);		
	}
	
	public function setAccountID($accountID) {
		$this->_accountID = $accountID;
	}
	
	public function saveAliexpressListing($datas) {
		if (empty($datas)) return false;
		$productId = $datas->productId;
		$checkExists = $this->getDbConnection()->createCommand()->from(self::model()->tableName())->select('*')->where("product_id = :id", array(':id' => $productId))->queryRow();
		if ($checkExists) return true;
		$dbTransaction = $this->getDbConnection()->beginTransaction();
		try {
			$params = array(
				'account_id' => $this->_accountID,
				'product_id' => $datas->productId,
				'subject' => $datas->subject,
				'keyword' => $datas->keyword,
				'reduce_strategy' => $datas->reduceStrategy,
				'product_unit' => $datas->productUnit,
				'ws_offline_date' => $datas->wsOfflineDate,
				'sizechart_id' => $datas->sizechartId,
				'package_length' => $datas->packageLength,
				'package_width' => $datas->packageWidth,
				'package_height' => $datas->packageHeight,
				'ws_display' => $datas->wsDisplay,
				'is_image_dynamic' => $datas->isImageDynamic,
				'category_id' => $datas->categoryId,
				'image_URLs' => $datas->imageURLs,
				'owner_member_id' => $datas->ownerMemberId,
				'product_status_type' => $datas->productStatusType,
				'gross_weight' => $datas->grossWeight,
				'delivery_time' => $datas->deliveryTime,
				'product_price'	=> $datas->productPrice
			);
			if (isset($datas->productMoreKeywords1))
				$params['product_more_keywords1'] = $datas->productMoreKeywords1;
			if (isset($datas->productMoreKeywords2))
				$params['product_more_keywords2'] = $datas->productMoreKeywords2;
			
			if ($this->getDbConnection()->createCommand()->insert(self::model()->tableName(), $params)) {
				$listingId = $this->getDbConnection()->getLastInsertID();
				$detail = $datas->detail;
				$datailParams = array(
					'listing_id' => $listingId,
					'detail' => $detail
				);
				$this->getDbConnection()->createCommand()->insert('ueb_aliexpress_listing_detail', $datailParams);
				$aeopAeProductSKUs = $datas->aeopAeProductSKUs;
				$encryptSku = new encryptSku();
				foreach ($aeopAeProductSKUs as $aeopAeProductSKU) {
					$skuParams = array();
					$alexpressSku = $aeopAeProductSKU->skuCode;
					$sku = $encryptSku->getAliRealSku($alexpressSku);
					if (empty($sku))
						$sku = $alexpressSku;
					$skuParams = array(
						'listing_id' => $listingId,
						'ipm_sku_stock' => $aeopAeProductSKU->ipmSkuStock,
						'sku_code' => $alexpressSku,
						'sku' => $sku,
						'sku_price' => $aeopAeProductSKU->skuPrice,
						'sku_stock' => $aeopAeProductSKU->skuStock,
						'aeopaeproductskus_id' => $aeopAeProductSKU->id
					);
					if ($this->getDbConnection()->createCommand()->insert('ueb_aliexpress_aeopaeproductskus', $skuParams)) {
						$aeopAeProductSKUId = $this->getDbConnection()->getLastInsertID();
						$aeopSKUProperty = $aeopAeProductSKU->aeopSKUProperty;
						if (!empty($aeopSKUProperty)) {
							foreach ($aeopSKUProperty as $property) {
								$propertyParams = array();
								if (isset($property->skuPropertyId))
									$params['sku_property_id'] = $property->skuPropertyId;
								if (isset($property->propertyValueId))
									$params['property_valueId'] = $property->propertyValueId;
								if (isset($property->skuImage))
									$propertyParams['sku_image'] = $property->skuImage;
								if (isset($property->propertyValueDefinitionName))
									$propertyParams['property_value_definition_name'] = $property->propertyValueDefinitionName;
								if (!empty($propertyParams)) {
									$propertyParams['aeopaeproductskus_id'] = $aeopAeProductSKUId;
									$this->getDbConnection()->createCommand()->insert('ueb_aliexpress_aeopskuproperty', $propertyParams);
								}
							}
						}
						$dbTransaction->commit();
					} else {
						
					}
				}
	
			} else {
				$dbTransaction->rollback();
				return false;
			}
		} catch (Exception $e) {
			
		}
	}
}