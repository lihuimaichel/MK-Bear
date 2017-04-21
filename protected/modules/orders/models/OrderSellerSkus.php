<?php
/**
 * @desc 订单详情中的线上sku
 * @author Gordon
 */
class OrderSellerSkus extends OrdersModel {
	

	
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_order_seller_skus';
    }
    
  
    public function saveSellerSku($data){
    	$platformCode = $data['platform_code'];
    	$orderId = $data['order_id'];
    	$detailId = $data['detail_id'];
    	$sellerSku = $data['seller_sku'];
    	$pos = strrpos($sellerSku, "-");
    	$data['sku_suffix'] = '';
    	if($pos){
    		$pos += 1;
    		$skuSubfix = substr($sellerSku, $pos);
    		$data['sku_suffix'] = $skuSubfix;
    	}
    	
    	//检测
    	$checkExists = $this->getDbConnection()->createCommand()
    							->from($this->tableName())
    							->where("order_id='{$orderId}' and detail_id={$detailId} and platform_code='{$platformCode}' and seller_sku='{$sellerSku}'")
    							->queryRow();
    	if($checkExists){
    		return $this->getDbConnection()->createCommand()->update($this->tableName(), $data, "id={$checkExists['id']}");
    	}else{
    		return $this->getDbConnection()->createCommand()->insert($this->tableName(), $data);
    	}
    }
}