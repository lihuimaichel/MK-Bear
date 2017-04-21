<?php
/**
* @desc Aliexpress Offline (数卖通产品下线)
* @author	tony
* @since	2015-09-21
*/

class AliexpressofflineController extends UebController {
	
	/**
	 * @desc 产品下架
	 */
	public function actionAliexpressproductoffline(){
		set_time_limit(86400);
		ini_set('memory_limit','2084M');
		ini_set('max_file_uploads','100M');
		//1获取带下线账号,带下线产品
		$requestData = $_REQUEST;
// 		$requestData[] = array(
// 				'product_id' => 
// 		);
		//2 准备LOG
		$AliexpressProductModel = AliexpressProduct::model();
		foreach ($requestData as $key => $val){
			$productID = $val['product_id'];
			$sku = $val['sku'];
			$accountID = $val['account_id'];
			
			$variation = $AliexpressProductModel->getVariationByproductID($productID);
			$isVariation = $AliexpressProductModel->isVariation($variation);
			
			$AliexpressOfflineModel = new AliexpressOffline();
			$AliexpressOfflineModel->setAccountID($accountID);
			$AliexpressOfflineModel->setProductID($productID);
			
			//如果产品为单品或者SKU为空则直接下架
			if( !$isVariation || (isset($productID) && empty($sku)) ){
				//准备LOG信息
				$eventName = AliexpressLogOffline::EVENT_NAME_NOT_VARIATION;
				$sku = AliexpressProductVariation::model()->getSkuByproductID($productID);
				$logID = AliexpressLogOffline::model()->prepareLog($productID,$sku,$eventName);
				//产品下架
				$returnData = $AliexpressOfflineModel::model()->AliexpressProductOffline();
				//更新LOG
				if($returnData['1'] && isset($returnData['0'])){//
					$modifyCount = $returnData['0'];
					AliexpressLogOffline::model()->setSuccess($logID);
				}else{
					AliexpressLogOffline::model()->setFailure($logID, $AliexpressOfflineModel->getExceptionMessage());
				}
			}
			//产品是多品,将具体SKU库存调整为0
			if( $isVariation  && (isset($productID) && isset($sku)) ){
				//准备LOG信息
				$eventName = AliexpressLogOffline::EVENT_NAME_VARIATION;
				
				$logID = AliexpressLogOffline::model()->prepareLog($productID,$sku,$eventName);
				//设置库存调整参数
				$ipmStockSku = 0;
				$AliexpressOfflineModel->setIpmSkuStock($ipmStockSku);
				$AliexpressOfflineModel->setSku($sku);
				//调整指定的SKU线上库存为0
				$returnData = $AliexpressOfflineModel::model()->AliexpressProductStockEdit();
				//更新LOG
				if($returnData['1'] && isset($returnData['0'])){
					$modifyCount = $returnData['0'];
					AliexpressLogOffline::model()->setSuccess($logID);
				}else{
					AliexpressLogOffline::model()->setFailure($logID, $AliexpressOfflineModel->getExceptionMessage());
				}
			}
		}
		
		
		//4 将调整结果写入LOG
	}
}

