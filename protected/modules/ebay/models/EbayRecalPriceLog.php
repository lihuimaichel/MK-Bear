<?php

class EbayRecalPriceLog extends EbayModel{
		
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_ebay_recal_price_log';
    }


    public function addData($data){
    	if(empty($data)) return false;
    	$res = $this->getDbConnection()->createCommand()->insert($this->tableName(), $data);
    	if($res){
    		return $this->getDbConnection()->getLastInsertID();
    	}
    	return false;
    }
	/**
	 * 
	 * @param unknown $salePrice
	 * @param unknown $itemID
	 * @param unknown $sku
	 */
    
    public function saveLogDataByCalcalprice($salePrice, $itemID, $sku){

    	$data = array(
    			'item_id'				=>	$itemID,
    			'sku'					=>	$sku,
    			'sale_price'			=>	isset($salePrice['salePrice']) ? $salePrice['salePrice'] : '',
    			'publication_fee'		=>	isset($salePrice['publicationFee']) ? $salePrice['publicationFee'] : '',
    			'publication_fee_ori'	=>	isset($salePrice['publicationFeeOri']) ? $salePrice['publicationFeeOri'] : '',
    			'payplatform_addition'	=>	isset($salePrice['payplatformAddition']) ? $salePrice['payplatformAddition'] : '',
    			'shipping_cost'			=>	isset($salePrice['shippingCost']) ? $salePrice['shippingCost'] : '',
    			'platform_rate'			=>	isset($salePrice['$platformRate']) ? $salePrice['$platformRate'] : '',
    			'platform_cost'			=>	isset($salePrice['$platformCost']) ? $salePrice['$platformCost'] : '',
    			'profit_rate'			=>	isset($salePrice['$profitRate']) ? $salePrice['$profitRate'] : '',
    			'product_cost'			=>	isset($salePrice['productCost']) ? $salePrice['productCost'] : '',
    			'payplatform_cost_rate'	=>	isset($salePrice['$payPlatformCostRate']) ? $salePrice['$payPlatformCostRate'] : '',
    			'payplatform_cost'		=>	isset($salePrice['$payPlatformCost']) ? $salePrice['$payPlatformCost'] : '',
    			'ratetocny'				=>	isset($salePrice['$rateToCNY']) ? $salePrice['$rateToCNY'] : '',
    			'order_loss_rate'		=>	isset($salePrice['$orderLossRate']) ? $salePrice['$orderLossRate'] : '',
    			'order_loss'			=>	isset($salePrice['$orderLoss']) ? $salePrice['$orderLoss'] : '',
    			'usdratetocny'			=>	isset($salePrice['USDRateToCNY']) ? $salePrice['USDRateToCNY'] : '',
    			'create_time'			=>	date("Y-m-d H:i:s")	
    	);
    	return $this->addData($data);
    }
}