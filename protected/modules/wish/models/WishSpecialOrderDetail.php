<?php
/**
 * @desc 订单详情Model
 * @author Gordon
 */
class WishSpecialOrderDetail extends WishModel {
	
	/**
	 * 定义产品类型
	 */
	const IS_ADAPTER = 1;	
	
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_wish_order_detail';
    }
    
    /**
     * @desc 根据订单号删除订单详情
     * @param string $orderID
     */
    public function deleteOrderDetailByOrderID($orderID){
        return $this->dbConnection->createCommand()->delete(self::tableName(), 'order_id = "'.$orderID.'"');
    }
    
    /**
     * @desc 添加订单详情记录
     * @param unknown $params
     */
    public function addOrderDetail($params){
        $res = $this->dbConnection->createCommand()->insert(self::tableName(), $params);
        if($res){
        	return $this->dbConnection->getLastInsertID();
        }
        return false;
    }
    
    /**
     * @desc 设置订单详情待处理状态
     * @param tinyint $status
     * @param string $orderID
     */
    public function setOrderDetailPendingStatus($status, $orderID){
    	return $this->dbConnection->createCommand()->update(self::tableName(), array('pending_status' => $status), 'order_id = "'.$orderID.'"');
    }
    
    /**
     * @desc 根据orderid获取订单详细
     * @param string $orderId,string $field
     */
    public function getOrderDetailByOrderDetailId( $orderDetailId = null,$field = '*' ){
    	if(!$orderDetailId) return null;
    	$ret = $this->dbConnection->createCommand()
    			->select($field)
    			->from(self::tableName())
    			->where('id="'.$orderDetailId.'"')
    			->queryRow();
    	return $ret;
    }

    /**
     * 功能：通过订单号获取明细
     * 参数：$orderid:订单编号
     * 返回：订单明细
     * 作者：ada
     * 时间：2016-03-17
     */
    public  function getOrderDetailByOrderId($orderId=null,$field='*')
    {
        if(!$orderId)return null;
        $ret=$this->dbConnection->createCommand()->select($field)->from(self::tableName())->where('order_id="'.$orderId.'"')->queryAll();
        return $ret;
    }
    /**
     * @desc 根据订单ID和sku
     * @param string $orderId
     * @param string $sku
     * @param string $field
     * @return NULL|Ambigous <multitype:, mixed>
     */
    public function getOrderDetailByOrderIdAndSKU($orderId = null, $sku = null, $field = '*'){
    	if(!$orderId)return null;
    	$ret=$this->dbConnection->createCommand()->select($field)
    											->from(self::tableName())
    											->where('order_id="'.$orderId.'"')
    											->andWhere($sku ? "sku='{$sku}'" : '1')
    											->queryAll();
    	return $ret;
    }
    
    /**
     * @desc 获取订单IDs通过条件
     * @param unknown $condition
     * @param unknown $params
     * @return multitype:Ambigous <>
     */
    public function getOrderIdsByCondition($condition, $params = array()){
    	$rets = $this->dbConnection->createCommand()
    							->select("t.order_id")
    							->from(self::tableName() . ' t')
    							->join(WishSpecialOrder::model()->tableName() . ' o', 'o.order_id=t.order_id')
    							->where($condition, $params)
    							->queryAll();
    	$orderIds = array();
    	if($rets){
    		foreach ($rets as $ret){
    			$orderIds[] = $ret['order_id'];
    		}
    	}
    	return $orderIds;
    }
    
    /**
     * @desc 添加订单明细sku对应转接头
     * @param unknown $order
     * @param unknown $orderItem
     * @return boolean|Ambigous <number, boolean>
     */
    public function addOrderAdapter($order, $orderItem) {
    	$orderID = $order['order_id'];
    	$country = $order['ship_country_name'];
    	$platformCode = $order['platform_code'];
    	//货币
    	$currency = $order['currency'];
    	
    	$sku = $orderItem['sku'];
    	$quantity = $orderItem['quantity'];
    	//查找sku对应转接头
    	$adapterSku = ProductAdapter::model()->getAdapterSkuByOrderSku($sku,$country);
    	if (!empty($adapterSku)) {
    		if($adapterSku == $sku){//如果该订单sku本身就是对应转接头，则不发货，一般在设置转接头时不会存在该情况，这里以防万一判断一下
    			return true;
    		}
    		$condition = 'order_id=:order_id and sku=:sku and platform_code=:platform_code';
    		$param = array(':order_id'=>$orderID,':sku'=>$adapterSku,':platform_code'=>$platformCode);
    		//根据订单号取该转接头sku记录
    		$dataObj = $this->find($condition,$param);
    		if ($dataObj) {//update
    			$oldQuantity = $dataObj->quantity;
    			$newQuantity = $oldQuantity + $quantity;
    			$data = array(
    					'modify_user_id'	=>Yii::app()->user->id,
    					'modify_time'=>date('Y-m-d H:i:s'),
    			);
    			$data['quantity'] = $oldQuantity;
    			return $this->updateByPk($dataObj->id,$data);
    		}else{//insert
    			/**    			
    			$orderItemID = OrderDetail::model()->getPlanInsertID($platformCode);
    			if (empty($orderItemID)) {
    				throw new Exception(Yii::t('jd', 'Fetch Order Item ID Failure'));
    			}   
    			**/			
    			$data = array(
    					//'id'			=>$orderItemID,
    					'order_id'		=>$orderID,
    					'platform_code'	=>$platformCode,
    					'currency'		=>$currency,
    					'sku'			=>$adapterSku,
    					'detail_type'	=>self::IS_ADAPTER,
    					//'opration_id'	=>Yii::app()->user->id,
    					//'opration_date'=>date('Y-m-d H:i:s'),
    					//'create_user_id'=>Yii::app()->user->id,
    					'create_time'	=>date('Y-m-d H:i:s'),
    					//'modify_user_id'	=>Yii::app()->user->id,
    					'modify_time'=>date('Y-m-d H:i:s'),
    					'quantity' => $quantity,
    			);
    			return $this->addOrderDetail($data);    			
    		}
    	} 	
    	return true;
    }
    
   
    /**
     * 根据订单修改详情数量
     * @author	liuj
     * @since	2016-02-25
     */
    public function updateQtyByOrderID( $order_id, $quantity ){
        $this->dbConnection->createCommand()->update(self::tableName(), array( 'quantity' => $quantity ), 'order_id = "'.$order_id.'"');
    }
}