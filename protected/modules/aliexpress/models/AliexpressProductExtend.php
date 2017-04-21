<?php
/**
 * @desc aliexpress 产品扩展MODEL
 * @author zhangF
 *
 */
class AliexpressProductExtend extends AliexpressModel {
	
	/** @var string 错误信息 */
	protected $_errorMessage = '';
	
	/**
	 * @desc 生成model
	 * @param system $className
	 * @return Ambigous <CActiveRecord, unknown, multitype:>
	 */
	public static function model($className = __CLASS__) {
		return parent::model($className);
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
	 * (non-PHPdoc)
	 * @see CActiveRecord::tableName()
	 */
	public function tableName() {
		return 'ueb_aliexpress_product_extend';
	}
	
	/**
	 * @desc 根据aliexpress product id 删除记录
	 * @param int $id
	 */
	public function deleteByProductID($id) {
		return $this->deleteAll("product_id = :id", array(':id' => $id));
	}
	
	/**
	 * @desc 根据产品ID获取产品扩展信息
	 * @param unknown $productID
	 */
	public function getInfoByProductID($productID) {
		return $this->getDbConnection()->createCommand()
			->from(self::tableName())
			->where("product_id = :product_id", array(':product_id' => $productID))
			->queryRow();
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
	public function getListByCondition($fields='*', $where='1',$order='') {
		$cmd = $this->dbConnection->createCommand();
		$cmd->select($fields)
			->from(self::tableName())
			->where($where);
		$order != '' && $cmd->order($order);
		return $cmd->queryAll();
	}		

	/**
	 * [saveAliProductExtend description]
	 * @param  [type] $productId   主表id
	 * @param  [type] $productInfo [description]
	 * @return [type]              [description]
	 */
	public function saveAliProductExtend($productId, $productInfo) {
		try {
			$aliexpresProductID = $productInfo->productId;
			$productExtendInfo  = $this->getOneByCondition('id',"product_id='{$productId}'");
			//商品的SKU信息
			$aeopAeProductSKUs 	= $productInfo->aeopAeProductSKUs;
			$encryptSku 		= new encryptSku();
			$firstOnlineSku 	= $aeopAeProductSKUs[0]->skuCode;//Sku商家编码
			$firstSku 			= $encryptSku->getAliRealSku($firstOnlineSku);
			$skuID 				= $aeopAeProductSKUs[0]->id;//SKU ID
			if (empty($firstSku)){
				$firstSku 		= $firstOnlineSku;
			}
			$skuInfo 			= Product::model()->getProductInfoBySku($firstSku);
			$skuProperty 		= '';
			if (!empty($skuInfo) && Product::PRODUCT_MULTIPLE_VARIATION != $skuInfo['product_is_multi'] ) {
				if (isset($aeopAeProductSKUs[0]->aeopAeProductPropertys)) {
					$skuProperty 	= json_encode($aeopAeProductSKUs[0]->aeopAeProductPropertys);
				}
			}
			$productProperty = array();
			if (isset($productInfo->aeopAeProductPropertys)) {
				$productProperty = $productInfo->aeopAeProductPropertys;
			}
			//插入扩展表
			$productExtendData = array(
					'product_id' 				=> $productId,
					'aliexpress_product_id'		=> $aliexpresProductID,
					'image_urls' 				=> $productInfo->imageURLs,
					'sku_property' 				=> $skuProperty,
					'product_property' 			=> json_encode($productProperty),
					'detail' 					=> $productInfo->detail,
			);
			if (!empty($productExtendInfo)) {
				$this->dbConnection->createCommand()->update(self::tableName(), $productExtendData, 'id = '.$productExtendInfo['id'] );
			} else {
				$this->dbConnection->createCommand()->insert(self::tableName(), $productExtendData);
			}
			unset($productExtendData);
			return true;
		} catch (Exception $e) {
			$this->setErrorMessage($e->getMessage());
			return false;
		}
	}

}