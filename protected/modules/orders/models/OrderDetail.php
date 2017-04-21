<?php
/**
 * @desc 订单详情Model
 * @author Gordon
 */
class OrderDetail extends OrdersModel {
	
    const DETAIL_SKU_PRPARED_NO = 0;  //not be prepared
    const DETAIL_SKU_PRPARED_YES = 1; //be prepared
    
    const PEDNDING_STATUS_ABLE = 0; //可发待处理
    const PEDNDING_STATUS_QH = 1; //缺货待处理
    const PEDNDING_STATUS_KF = 2; //客服待处理
    const PEDNDING_STATUS_UN_STOCK = 7; //库存异常待处理
    
    const PENDING_PURCHASE_STATUS_UNPURCHASE    = 0;    //未下采购单
    const PENDING_PURCHASE_STATUS_PURCHASED     = 1;    //已下采购单
    const PENDING_PURCHASE_STATUS_CANCEL        = 2;    //已取消
    
    const IS_CHECK_YES = 1; //已检测
    const IS_CHECK_NO  = 0; //未检测--默认

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
        return 'ueb_order_detail';
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
        $ret=$this->dbConnection->createCommand()->select($field)->from(self::tableName())->where('order_id="'.$orderId.'"')->queryRow();
        return $ret;
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
     * 防新老系统重详单ID，市场业务新增订单详情自增ID时使用  [80000000 -- 90000000]
     * @return	int
     * @author	Rex
     * @since	2015-11-10
     */
    public function getPlanInsertID($platfromCode) {
    	$minID = null;
    	$maxID = null;
    	switch ($platfromCode) {
    		//京东item id段（80000000 - 80999999）
    		case Platform::CODE_JD:
    			$minID = 80000000;
    			$maxID = 80999999;
    			break;
    		//速卖通item id段（81000000 - 81999999）
    		case Platform::CODE_ALIEXPRESS:
    			$minID = 81000000;
    			$maxID = 81999999;
    			$orderDetailPrimaryKeyModel = new AliexpressOrderDetailPrimaryKey;
    			break;
    		//亚马逊item id段（82000000 - 82999999）
    		case Platform::CODE_AMAZON:
    			$minID = 82000000;
    			$maxID = 82999999;
    			
    			// lihy add 2016-03-15
    			$orderDetailPrimaryKeyModel = new AmazonOrderDetailPrimaryKey;
    			// lihy add end
    			
    			break;
    		//ebayitem id段（83000000 - 83999999）
    		case Platform::CODE_EBAY:
    			$minID = 83000000;
    			$maxID = 83999999;
    			break;
    		//LAZADA item id段（84000000 - 84999999）
    		case Platform::CODE_LAZADA:
    			$minID = 84000000;
    			$maxID = 84999999;
    			break;
    		//WISH item id段（85000000 - 85999999）
    		case Platform::CODE_WISH:
    			$minID = 85000000;
    			$maxID = 85999999;
    			// lihy add 2016-03-16
    			$orderDetailPrimaryKeyModel = new WishOrderDetailPrimaryKey;
    			break;    						    					
    		default:
    			return false;
    					
    	}
    	/* 特殊处理id生成 */
    	switch ($platfromCode){
    		case Platform::CODE_WISH:
    		case Platform::CODE_AMAZON:
    		case Platform::CODE_ALIEXPRESS:
    			// lihy add 2016-03-15
    			/**
    			$continue = false;
    			$continueNum = 0;
    			$id = null;
    			do{
    				$id = $orderDetailPrimaryKeyModel->getOrderDetailPrimaryKeyID();
    				if(!$id){
    					$continue = true;
    				}else{
    					$isExists = self::model()->findByPk($id);
    					if($isExists){
    						$continue = true;
    					}else {
    						$continue = false;
    					}
    				}
    				$continueNum++;
    				if($continueNum > 10){
    					$continue = false;
    				}
    			}while ($continue);
    			if($id > $maxID)
    				return null;
    			return $id;
    			// lihy add end
    			break;
    			**/
    	}
    	
    	$row = $this->getDbConnection()->createCommand()
								    	->select('id')
								    	->from(self::tableName())
								    	->where('id <= ' . $maxID)
								    	->andWhere("platform_code = :platform_code", array(':platform_code' => $platfromCode))
								    	->order('id desc')
								    	->limit(1)
								    	->queryRow();

    	$id = null;
    	if (!empty($row) && $row['id'] >= $minID) {
    		$id = $row['id'] + 1;
    		if ($id > $maxID)
    			$id = null;
    	} else {
    		$id = $minID;
    	}
    	//检测
    	$isExists = self::model()->findByPk($id);
    	if($isExists){
    		//重新抽取一次
    		$row = $this->getDbConnection()->createCommand()
			    		->select('id')
			    		->from(self::tableName())
			    		->where('id> '. $minID .' AND id <= ' . $maxID)
			    		//->andWhere("platform_code = :platform_code", array(':platform_code' => $platfromCode))
			    		->order('id desc')
			    		->limit(1)
			    		->queryRow();
    		$id = null;
    		if (!empty($row) && $row['id'] >= $minID) {
    			$id = $row['id'] + 1;
    			if ($id > $maxID)
    				$id = null;
    		} else {
    			$id = $minID;
    		}
    	}
		return $id;
    }
    
    /**
     * 根据订单修改详情数量
     * @author	liuj
     * @since	2016-02-25
     */
    public function updateQtyByOrderID( $order_id, $quantity ){
        $this->dbConnection->createCommand()->update(self::tableName(), array( 'quantity' => $quantity ), 'order_id = "'.$order_id.'"');
    }

    /**
     * @desc 通过订单id修改明细数量
     * @param  array $detailIdArr
     * @param  int $quantity
     * @return miexed 
     */
    public function updateQtyByID( $detailIdArr, $quantity ){
        return $this->dbConnection->createCommand()->update(self::tableName(), array( 'quantity' => $quantity ), 'id in('. implode(',', $detailIdArr) .') ');
    }
  
    /**
     * getOneByCondition
     * @param  string $fields
     * @param  string $where 
     * @param  mixed $order  
     * @return array        
     */
    public function getOneByCondition($fields='*', $where='1',$order='') {
        $cmd = $this->dbConnection->createCommand();
        $cmd->select($fields)
            ->from($this->tableName())
            ->where($where);
        $order != '' && $cmd->order($order);
        $cmd->limit(1);
        return $cmd->queryRow();
    }

    /**
     * getListByCondition
     * @param  string $fields
     * @param  string $where 
     * @param  mixed $order  
     * @return array        
     */
    public function getListByCondition($fields='*', $where='1',$order='') {
        $cmd = $this->dbConnection->createCommand();
        $cmd->select($fields)
            ->from($this->tableName())
            ->where($where);
        $order != '' && $cmd->order($order);
        return $cmd->queryAll();
    }      

    /**
     * 保存同步订单详情里的信息
     * @param unknown $list
     * @return multitype:number NULL |multitype:string number |multitype:number string
     */
    public function saveAliOrderDetailInfo($data){
        $model = new self();
        $result = $model->batchInsert(self::tableName(),array_keys($data[0]),$data);
        if($result){
            return  array('id' => $result,'status' => 23);
        }else{
            return array('id' => '','status' => -1);
        }
    }

}