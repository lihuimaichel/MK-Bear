<?php
/**
 * @desc 订单Model
 * @author Gordon
 */
class Order extends OrdersModel {
    
    /** @var tinyint 未付款*/
	const  PAYMENT_STATUS_NOT = 0;
	
	/** @var tinyint 已付款*/
	const  PAYMENT_STATUS_END = 1;
	
	/** @var tinyint 刚导入*/
	const  COMPLETE_STATUS_DEFAULT = 0;
	
	/** @var tinyint 待处理*/
	const  COMPLETE_STATUS_PENGDING = 1;
	
	/** @var tinyint 备货中*/
	const  COMPLETE_STATUS_PROCESSIBLE = 2;
	
	/** @var tinyint 已完成*/
	const  COMPLETE_STATUS_END = 3;
	
	/** @var tinyint 异常订单*/
	const  COMPLETE_STATUS_EXCEPTION = 4;
	
	/** @var tinyint 锁定订单*/
	const  COMPLETE_STATUS_WAIT_CANCEL = 9;
	
	/** @var tinyint 未出货*/
	const SHIP_STATUS_NOT = 0;
	
	/** @var tinyint 部分出货*/
	const SHIP_STATUS_PART =  1;
	
	/** @var tinyint 已出货*/
	const SHIP_STATUS_YES = 2;
	
	const PENDIND_STATUS_CUSTOMER=2;
	
	/** @var tinyint  退款状态默认  退款状态:0默认,2全部退款**/
	const REFUND_STATUS_DEFAULT = 0; //默认
	const REFUND_STATUS_PART = 1; //部分
	const REFUND_STATUS_ALL = 2; //全部
	
	const PENDING_STATUS_SEND=0;  //可发待处理
	const PENDING_STATUS_OWE=1;   //欠货待处理
	const PENDING_STATUS_CUSTOMER=2;//客服待处理
	
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_order';
    }
    
    /**
     * @desc 根据平台订单号获取订单信息
     * @param string $platformOrderID
     * @param string $platformCode
     * @return mixed
     */
    public function getOrderInfoByPlatformOrderID($platformOrderID, $platformCode=''){
        $where = '';
        if($platformCode){
            $where .= ' AND platform_code = "'.$platformCode.'"';
        }
        return $this->dbConnection->createCommand()
                    ->select('*')
                    ->from(self::tableName())
                    ->where('platform_order_id = "'.$platformOrderID.'"'.$where)
                    ->queryRow();
    }

    /**
     * 根据条件获取记录
     * @param  string $platformOrderID 
     * @param  string $platformCode    
     * @param  int $accountID      
     * @return array                 
     */
    public function getOrderInfoByCondition($platformOrderID, $platformCode, $accountID=null){
        $condition = empty($accountID) ? '' : " and account_id='{$accountID}' ";
        return $this->dbConnection->createCommand()
                    ->select('*')
                    ->from(self::tableName())
                    ->where("platform_order_id='{$platformOrderID}' and platform_code='{$platformCode}'"
                        . $condition )
                    ->queryRow();
    }
    
    /**
     * @desc 根据订单号更新订单信息
     * @param string $orderID
     * @param array $params
     */
    public function updateColumnByOrderID($orderID, $params){
        return $this->dbConnection->createCommand()->update(self::tableName(), $params, 'order_id = "'.$orderID.'"');
    }
    
    /**
     * @desc 设置订单完成状态
     * @param tinyint $status
     * @param string $orderID
     */
    public function setOrderCompleteStatus($status, $orderID){
        return $this->dbConnection->createCommand()->update(self::tableName(), array('complete_status' => $status), 'order_id = "'.$orderID.'"');
    }
    
    /**
     * @desc 保存订单主表记录
     * @param array $param
     */
    public function saveOrderRecord($param){
        if( isset($param['order_id']) ){
            return $this->dbConnection->createCommand()->replace(self::tableName(), $param);
        }else{
            return $this->dbConnection->createCommand()->insert(self::tableName(), $param);
        }
    }
    
    /**
     * @author liuj
     * @desc 取消订单
     * @param unknown $orderId
     * @return boolean
     */
    public function cancelOrders( $orderId ){
        //查询订单主表，查看订单的complete_status和ship_status，查看是否已经取消.
        $order_info = $this->getInfoByOrderId( $orderId, 'complete_status, ship_status' );
        $message = '';
        $return = array('status' => true , 'message' => $message );
        if( $order_info ){
            if( $order_info['complete_status'] == self::COMPLETE_STATUS_END && $order_info['ship_status'] == self::SHIP_STATUS_NOT){
                //已处理
                return $return;
            }
            // 1包裹处理 [查询包裹]
            $params = OrderPackageDetail::model()->getPackageIdsByOrderId( $orderId );
            if( !empty( $params ) ){
                //取消包裹
                $cancel_package_return = OrderPackage::model()->cancelPackage( $params );
                $cancel_package = $cancel_package_return['status'];
            } else {
                //没有包裹
                $cancel_package = true;
            }
            if( $cancel_package ){
                // 2订单详情表处理 [数量改为0]
                OrderDetail::model()->updateQtyByOrderID( $orderId, 0 );
                // 3订单主表处理 [shipstatus状态改为未出货，complete status改为已完成]
                $params_order = array(
                    'ship_status'       => Order::SHIP_STATUS_NOT,
                    'complete_status'   => Order::COMPLETE_STATUS_END,
                );
                Order::model()->updateColumnByOrderID( $orderId, $params_order );
                //order status 改为取消状态 [不同平台的值不一样，各平台单独处理]
            } else {
                $return = array('status' => false , 'message' => $cancel_package_return['message'] );
            }
        } else {
            $return = array('status' => false , 'message' => 'can not find order when cancel' );
        }
        return $return;
    }

    /**
     * @desc 订单部分取消
     * @param string $orderId
     * @return boolean
     */
    public function cancelOrdersPartial( $orderId , $detailIdArr=array()){
        //查询订单主表，查看订单的complete_status和ship_status，查看是否已经取消.
        $order_info = $this->getInfoByOrderId( $orderId, 'complete_status, ship_status' );
        $message = '';
        $return = array('status' => true , 'message' => $message );
        if( $order_info ){
            if( $order_info['ship_status'] == self::SHIP_STATUS_NOT && ($order_info['complete_status'] == self::COMPLETE_STATUS_END || $order_info['complete_status'] == self::COMPLETE_STATUS_WAIT_CANCEL ) ){
                //已处理
                return $return;
            }
            // 1包裹处理 [查询包裹]
            $params = OrderPackageDetail::model()->getPackageIdsByOrderId( $orderId );
            if( !empty( $params ) ){
                //取消包裹
                $cancel_package_return = OrderPackage::model()->cancelPackage( $params );
                $cancel_package = $cancel_package_return['status'];
            } else {
                //没有包裹
                $cancel_package = true;
            }
            if( $cancel_package ){
                // 2订单详情表处理 [数量改为0]
                if($detailIdArr) {
                    OrderDetail::model()->updateQtyByID( $detailIdArr, 0 );    
                }
                // 3订单主表处理 [shipstatus状态改为未出货，complete status改为已完成]
                $params_order = array(
                    'ship_status'       => Order::SHIP_STATUS_NOT,
                    'complete_status'   => Order::COMPLETE_STATUS_WAIT_CANCEL,//待取消
                    'is_lock'           => 1,//锁定订单
                );
                Order::model()->updateColumnByOrderID( $orderId, $params_order );
                //order status 改为取消状态 [不同平台的值不一样，各平台单独处理]
            } else {
                $return = array('status' => false , 'message' => $cancel_package_return['message'] );
            }
        } else {
            $return = array('status' => false , 'message' => 'can not find order when cancel' );
        }
        return $return;
    }

    /**
     * @desc 根据包裹号查订单信息
     * @param string $packageID
     */
    public function getOrderByPackageID($packageID){
        return $this->dbConnection->createCommand()
                ->select('O.*,OD.*')
                ->from(OrderPackageDetail::model()->tableName().' AS PD')
                ->leftJoin(OrderDetail::model()->tableName().' AS OD', 'OD.id = PD.order_detail_id')
                ->leftJoin(self::tableName().' AS O', 'O.order_id = OD.order_id')
                ->andWhere('PD.package_id = "'.$packageID.'"')
                ->queryAll();
    }
    
    /**
     * @desc 根据订单id查询指定字段
     * @param string $packageID
     */
    public function getInfoListByOrderIds( $orderIds,$field = '*',$platformCode ){
    	if( empty($orderIds) ) return '';
    	$ret = $this->dbConnection->createCommand()
	    	->select($field)
	    	->from(self::tableName().' AS o')
	    	->join(OrderDetail::model()->tableName().' AS d', 'd.order_id = o.order_id')
	    	->andWhere('o.order_id in('.$orderIds.')')
	    	->andWhere( 'o.platform_code = "'.$platformCode.'"' )
	    	->queryAll();
    	return $ret;
    }
    
    /**
     * @desc 根据订单id查询订单信息
     * @param string $orderId,string $field
     */
    public function getInfoByOrderId( $orderId,$field = '*' ){
    	if( empty($orderId) ) return null;
    	$ret = $this->dbConnection->createCommand()
		    	->select($field)
		    	->from(self::tableName().' AS o')
		    	->andWhere('o.order_id="'.$orderId.'"')
		    	->queryRow();
    	return $ret;
    }
    
    /**
     * 获取amazon付款超过24小时、未超过48小时没有确认发货的订单
     * @param string $platformCode
     * @param string $limit $orderId
     */
    public function getAmazonWaitingConfirmPackages($orderId,$gmtime,$limit = null, $excludeAccount = array()) {
    	//付款时间在24小时前且不超过48小时的订单。
    	$payTimeEnd = date('Y-m-d H:i',strtotime('-20 hours'));//4小时前
    	$payTimeStart = date('Y-m-d H:i',strtotime('-63 hours'));//55小时前
    	$dbCommand = $this->dbConnection->createCommand()
		    	->select('o.order_id,o.account_id,o.paytime,o.platform_order_id,d.quantity,d.item_id')
		    	->from(self::tableName() . " o")
		    	->join(OrderDetail::model()->tableName().' as d', "o.order_id = d.order_id")
		    	->where("o.payment_status = ".Order::PAYMENT_STATUS_END)
		    	->andWhere("o.paytime >= '" .$payTimeStart. "' and o.paytime <= '".$payTimeEnd."'")
		    	//->andWhere("TIMESTAMPDIFF(hour,o.paytime,'".$gmtime."') > (31)")
		    	//->andWhere("TIMESTAMPDIFF(hour,o.paytime,'".$gmtime."') < (55)")
		    	->andWhere("o.platform_code = :platform_code", array(':platform_code' => Platform::CODE_AMAZON))
		    	->andWhere('o.ship_status='.Order::SHIP_STATUS_NOT)
		    	->andWhere('o.complete_status not in('.self::COMPLETE_STATUS_END.','.self::COMPLETE_STATUS_WAIT_CANCEL.')' )
		    	->andWhere('d.quantity>0')
		    	->order("o.paytime asc");
    	if(!empty($orderId)){
    		$dbCommand->andWhere('o.order_id like "'.$orderId.'%"');
    	}
    	//2017-02-03 add lihy
    	if(!empty($excludeAccount)){
    		$dbCommand->andWhere(array('NOT IN', 'o.account_id', $excludeAccount));
    	}
    	if (!empty($limit))
    		$dbCommand->limit((int)$limit, 0);
    	//echo $dbCommand->text;exit;
    	return $dbCommand->queryAll();
    }
    
    /**
     * 获取ebay待确认发货到平台的包裹
     * @param string $platformCode
     * @param string $limit
     */
    public function getEbayWaitingConfirmOrders($payTimeStart,$payTimeEnd,$accountId,$limit = null) {
    	if( !$accountId ) return null;
    	//获取近3天的已付款，未确认发货的订单
    	$dbCommand = $this->dbConnection->createCommand()
		    	->select('o.order_id,o.account_id,o.paytime,o.platform_order_id')
		    	->from(self::tableName() . " o")
		    	->where("o.payment_status = ".Order::PAYMENT_STATUS_END)
		    	->andWhere("o.paytime >= '" .$payTimeStart. "' and o.paytime <= '".$payTimeEnd."'")
		    	->andWhere("o.platform_code = :platform_code", array(':platform_code' => Platform::CODE_EBAY))
		    	->andWhere('o.platform_order_id != ""')
		    	->andWhere('o.account_id='.$accountId)
		    	->andWhere('o.timestamp >= "2015-11-18 00:00:00"')
		    	->andWhere('o.timestamp <= "2016-09-03 00:00:00"')
		    	->order("o.paytime asc");
    	if (!empty($limit))
    		$dbCommand->limit((int)$limit, 0);
    	
    	//echo $dbCommand->text;exit;
    	return $dbCommand->queryAll();
    }
    
    /**
     * 获取ebay待确认发货的订单 [辅助程序]
     * @param string $limit
     */
    public function getEbayWaitingConfirmOrders1($accountId,$gmtime,$orderId,$limit = null) {
    	if(!$accountId) return null;
    	//$payTimeStart = gmdate('Y-m-d H:i',strtotime('-96 hours'));
    	$payTimeStart = gmdate('Y-m-d 00:00:00',strtotime('-15 days'));   //utc时间往前推N天的订单
    	$payTimeEnd = gmdate('Y-m-d 00:00:00',strtotime('-3 days')); 	  //utc时间往前推3天的订单
    	//$payTimeEnd = gmdate('Y-m-d H:i',strtotime('-24 hours'));
    	$dbCommand = $this->dbConnection->createCommand()
			    	->select('o.order_id,o.account_id,o.paytime,o.platform_order_id')
			    	->from(self::tableName() . " o")
			    	->where("o.payment_status = ".Order::PAYMENT_STATUS_END)
			    	//->andWhere('o.ship_status='.Order::SHIP_STATUS_NOT)
			    	//->andWhere('o.complete_status not in('.Order::COMPLETE_STATUS_EXCEPTION.','.Order::COMPLETE_STATUS_WAIT_CANCEL.') ')
			    	//->andWhere("o.paytime >= '" .$payTimeStart. "' and o.paytime <= '".$payTimeEnd."'")
			    	->andWhere('o.paytime >= "'.$payTimeStart.'"')
			    	->andWhere("o.paytime <= '" .$payTimeEnd. "'")
			    	->andWhere("o.platform_code = :platform_code", array(':platform_code' => Platform::CODE_EBAY))
			    	->andWhere('o.platform_order_id != ""')
			    	->andWhere('o.account_id="'.$accountId.'"')
			    	->order("o.paytime asc");
    	 
    	if (!empty($limit))
    		$dbCommand->limit((int)$limit, 0);
    	 
    	if(!empty($orderId)){
    		$dbCommand->andWhere('o.order_id like "'.$orderId.'%"');
    	}
    	 
    	//echo $dbCommand->text;exit;
    	return $dbCommand->queryAll();
    }
    
    /**
     * 获取ebay待确认发货的订单 [重写]
     * @param string $limit 
     */
    public function getEbayWaitingConfirmOrdersNew1($accountID, $orderId, $currDay) {
    	//if(!$accountId) return null;
    	//$payTimeStart = gmdate('Y-m-d H:i',strtotime('-72 hours')); //utc时间往前推15小时的订单
    	//$payTimeEnd = gmdate('Y-m-d H:i',strtotime('-15 hours'));
    	if( !in_array($currDay,array(1,2,3)) ) return null;
    	
    	$payTimeStart = gmdate('Y-m-d 00:00:00',strtotime('-'.$currDay.' days'));   //utc时间往前推N天的订单
    	if( $currDay == 1 ){
    		$payTimeEnd = gmdate('Y-m-d H:i',strtotime('-15 hours')); //utc时间往前推15小时的订单
    	}else{
    		$payTimeEnd = gmdate('Y-m-d 00:00:00',strtotime('-'.($currDay-1).' days')); //utc时间往前推N-1天的订单
    	}
    	
    	$dbCommand = $this->dbConnection->createCommand()
		    	->select('o.order_id,o.account_id,o.paytime,o.platform_order_id,o.complete_status,o.order_status')
		    	->from(self::tableName() . " o")
		    	->where("o.payment_status = ".Order::PAYMENT_STATUS_END)
		    	//->andWhere('o.ship_status='.Order::SHIP_STATUS_NOT)
		    	//->andWhere('o.complete_status not in('.Order::COMPLETE_STATUS_EXCEPTION.','.Order::COMPLETE_STATUS_WAIT_CANCEL.') ')
		    	->andWhere("o.paytime >= '" .$payTimeStart. "'")
		    	->andWhere("o.paytime <= '" .$payTimeEnd. "'")
		    	//->andWhere("TIMESTAMPDIFF(hour,o.paytime,'".$gmtime."') > (20)")
		    	->andWhere("o.platform_code = :platform_code", array(':platform_code' => Platform::CODE_EBAY))
		    	->andWhere('o.platform_order_id != ""')
    			//->andWhere('o.complete_status not in('.Order::COMPLETE_STATUS_EXCEPTION.') or (o.complete_status = 4 and o.order_status != "CustomCode" )')
		    	//->andWhere('o.account_id="'.$accountId.'"')
		    	->order("o.paytime asc");
    	
    	if (!empty($limit))
    		$dbCommand->limit((int)$limit, 0);
    
    	if(!empty($orderId)){
    		$dbCommand->andWhere('o.order_id like "'.$orderId.'%"');
    	}
    	if(!empty($accountID)){
    		$dbCommand->andWhere('o.account_id="'.$accountID.'"');
    	}
    
    	//echo $dbCommand->text;exit;
    	
    	//MHelper::writefilelog("ebayconfirmorder.log", "AccountID：【".$accountID."】,DAYS：【".$currDay."】, SQL：".$dbCommand->text."\r\n");
    	
    	return $dbCommand->queryAll();
    }
    
    /**
     * 获取aliexpress待确认发货的订单
     * @param string $limit
     */
    public function getAliexpressWaitingConfirmOrders($accountId,$gmtime,$orderId,$limit = null) {
    	if(!$accountId) return null;
    	$payTimeStart = gmdate('Y-m-d H:i',strtotime('-240 hours'));
    	$payTimeEnd = gmdate('Y-m-d H:i',strtotime('-120 hours'));
    	//$payTimeStart = '2016-04-14 00:00:00';
    	//$payTimeEnd = '2016-04-16 00:00:00';
    	$dbCommand = $this->dbConnection->createCommand()
	    	->select('o.order_id,o.account_id,o.paytime,o.platform_order_id')
	    	->from(self::tableName() . " o")
	    	->where("o.payment_status = ".Order::PAYMENT_STATUS_END)
	    	->andWhere('o.ship_status='.Order::SHIP_STATUS_NOT)
	    	->andWhere('o.complete_status not in('.Order::COMPLETE_STATUS_EXCEPTION.','.Order::COMPLETE_STATUS_WAIT_CANCEL.','.Order::COMPLETE_STATUS_END.') ')
	    	->andWhere("o.paytime >= '" .$payTimeStart. "' and o.paytime <= '".$payTimeEnd."'")
	    	//->andWhere("TIMESTAMPDIFF(hour,o.paytime,'".$gmtime."') > (48)")
	    	->andWhere("o.platform_code = :platform_code", array(':platform_code' => Platform::CODE_ALIEXPRESS))
	    	->andWhere('o.platform_order_id != ""')
	    	->andWhere('o.account_id="'.$accountId.'"')
	    	->andWhere('o.paytime >= "'.$payTimeStart.'"')
	    	->order("o.paytime asc");
    	
	    if (!empty($limit))
    		$dbCommand->limit((int)$limit, 0);
    	
    	if(!empty($orderId)){
    		$dbCommand->andWhere('o.order_id like "'.$orderId.'%"');
    	}
    	
    	//echo $dbCommand->text;exit;
    	return $dbCommand->queryAll();
    }
    
    /**
     * 获取wish付款超过3天还没有跟踪号的订单
     * @param integer $limit string $gmtime integer $accountId string $orderId
     */
    public function getWishWaitingConfirmOrders($accountId,$gmtime,$orderId,$limit = null) {
    	if(!$accountId) return null;
    	$dbCommand = $this->dbConnection->createCommand()
			    	->select('o.order_id,o.account_id,o.paytime,o.platform_order_id')
			    	->from(self::tableName() . " o")
			    	->where("o.payment_status = ".Order::PAYMENT_STATUS_END)
			    	->andWhere('o.ship_status='.Order::SHIP_STATUS_NOT)
			    	->andWhere('o.complete_status != '.Order::COMPLETE_STATUS_END)
			    	->andWhere("TIMESTAMPDIFF(hour,o.paytime,'".$gmtime."') > (72)")
			    	->andWhere("o.platform_code = '".Platform::CODE_WISH."'")
			    	->andWhere('o.account_id="'.$accountId.'"')
			    	->andWhere('o.paytime >= "2015-12-31 00:00:00"')
			    	->order("o.paytime asc");
    	
    	if (!empty($limit))
    		$dbCommand->limit((int)$limit, 0);
    	
    	if(!empty($orderId)){
    		$dbCommand->andWhere('o.order_id like "'.$orderId.'%"');
    	}
    	return $dbCommand->queryAll();
    }
    
    
    /**
     * @desc 获取wish未上传追踪号
     * @param unknown $accountId
     * @param unknown $gmtime
     * @param number $type
     * @param string $orderId
     * @param string $limit
     * @return NULL|multitype:|Ambigous <multitype:, mixed>
     */
    public function getWishUnshippingTracenumberOrders($accountId, $gmtime, $type = 1, $orderId = null, $limit = null, $isLocalWarehouse = true) {
    	if(!$accountId) return null;
    	$paytime = date("Y-m-d H:i:s", time()-15*24*3600);
    	$dbCommand = $this->getDbConnection()
    						->createCommand()
					    	->from(self::tableName() . " o")
					    	->where("o.payment_status = ".Order::PAYMENT_STATUS_END)
					    	->andWhere("o.platform_code = '".Platform::CODE_WISH."'")
					    	->andWhere('o.account_id="'.$accountId.'"')
					    	->andWhere('o.paytime >= "2015-12-31 00:00:00"')
					    	->andWhere('o.ori_pay_time >= "'.$paytime.'"')
					    	->order("o.paytime asc");
    	 
    	//type 
    	// 1  备货中 不在指定物流方式的
    	// 2 （备货中 只在指定物流方式的） 
    	// 3 （待处理）
    	// 4  待取消订单
    	$filterConditions = "";
    	$join = "";
    	$orderPackageTab = OrderPackage::model()->tableName();
    	$orderPackageDetail = OrderPackageDetail::model()->tableName();
    	$filterShipCode = array(
    			Logistics::CODE_CM_ZXYZ,
    			Logistics::CODE_CM_DEYZ,
    			Logistics::CODE_CM_DHL,
    			Logistics::CODE_CM_HK,
    			// === 2017 03 31 ===
    			// === 中邮 ===
    			Logistics::CODE_CM_DGYZ,//cm_dgyz
    			Logistics::CODE_CM_PTXB,//cm_ptxb
    			// === 外邮 ===
    			Logistics::CODE_CM_BYYD,//cm_byxb_yd
    			Logistics::CODE_CM_YW_TEQXB,//cm_yw_teqxb
    			Logistics::CODE_CM_PLUS_SGXB//cm_plus_sgxb
    			
    	);
    	$localWarehouseID = WarehouseSkuMap::WARE_HOUSE_GM;
    	switch ($type){
    		case -1://备货中，追踪号未生成的 用来做标记处理, 30小时内
    			$within30H = date("Y-m-d H:i:s", time()-50*3600);
    			$select = 'o.order_id,o.account_id,o.paytime,o.ori_pay_time,o.ship_country,o.platform_order_id,op.package_id,op.ship_code,op.track_num';
    			$filterConditions = "o.complete_status IN ( " . Order::COMPLETE_STATUS_PROCESSIBLE .",". Order::COMPLETE_STATUS_END ." ) 
    					AND op.ship_code not in(".MHelper::simplode($filterShipCode).")
    					AND op.ship_code<>''
    					AND op.is_confirm_shiped in(0) AND  op.is_repeat = 0 AND op.track_num=''";
    			
    			$dbCommand->join("$orderPackageDetail AS opd", "opd.order_id=o.order_id");
    			$dbCommand->join("$orderPackageTab AS op", "op.package_id=opd.package_id");
    			$dbCommand->andWhere($filterConditions);
    			$dbCommand->andWhere('o.ori_pay_time >= "'.$within30H.'"');
    			$dbCommand->group("o.order_id");
    			//海外仓和本地仓之分
    			if($isLocalWarehouse){
    				$dbCommand->andWhere("op.warehouse_id='' OR op.warehouse_id='{$localWarehouseID}'");
    			}else{
    				$dbCommand->andWhere("op.warehouse_id<>'' AND op.warehouse_id<>'{$localWarehouseID}'");
    			}
    			break;
    		case 1:
    			$select = 'o.order_id,o.account_id,o.paytime,o.ori_pay_time,o.ship_country,o.platform_order_id,op.package_id,op.ship_code,op.track_num,o.complete_status';
    			$filterConditions = "o.complete_status IN ( " . Order::COMPLETE_STATUS_PROCESSIBLE .",". Order::COMPLETE_STATUS_END ." ) AND op.ship_code not in(".MHelper::simplode($filterShipCode).") 
    					AND op.is_confirm_shiped in(0) AND  op.is_repeat = 0 AND op.track_num!='' and op.ship_status!=5";
    		
    			$dbCommand->join("$orderPackageDetail AS opd", "opd.order_id=o.order_id");
    			$dbCommand->join("$orderPackageTab AS op", "op.package_id=opd.package_id");
    			$dbCommand->andWhere($filterConditions);
    			$dbCommand->group("o.order_id");
    			//海外仓和本地仓之分
    			if($isLocalWarehouse){
    				$dbCommand->andWhere("op.warehouse_id='' OR op.warehouse_id='{$localWarehouseID}'");
    			}else{
    				$dbCommand->andWhere("op.warehouse_id<>'' AND op.warehouse_id<>'{$localWarehouseID}'");
    			}
    			
    			break;
    		case 2://备货中
    			$select = 'o.order_id,o.account_id,o.paytime,o.ori_pay_time,o.ship_country,o.platform_order_id,op.package_id,op.ship_code,op.track_num,o.complete_status';
    			$filterConditions = " (o.complete_status IN ( " . Order::COMPLETE_STATUS_PROCESSIBLE .",". Order::COMPLETE_STATUS_END .") AND op.ship_code in(".MHelper::simplode($filterShipCode).") ) 
    								AND op.is_confirm_shiped in(0) AND op.is_repeat = 0 and op.ship_status!=5";
    			
    			$dbCommand->join("$orderPackageDetail AS opd", "opd.order_id=o.order_id");
    			$dbCommand->join("$orderPackageTab AS op", "op.package_id=opd.package_id");
    			$dbCommand->andWhere($filterConditions);
    			$dbCommand->group("o.order_id");
    			//海外仓和本地仓之分
    			if($isLocalWarehouse){
    				$dbCommand->andWhere("op.warehouse_id='' OR op.warehouse_id='{$localWarehouseID}'");
    			}else{
    				$dbCommand->andWhere("op.warehouse_id<>'' AND op.warehouse_id<>'{$localWarehouseID}'");
    			}
    			break;
    		case 3://待处理，可以连预生成包裹表，先不连
    			$select = 'o.order_id,o.account_id,o.paytime,o.ori_pay_time,o.ship_country,o.platform_order_id,op.package_id,op.ship_code,op.track_num,o.complete_status';
    			$filterConditions = "o.complete_status IN ( " . Order::COMPLETE_STATUS_PENGDING ." ) 
    								AND op.is_confirm_shiped in(0) AND op.is_repeat = 0 ";
    			 //排除掉快递 2017-03-31
    			$filterConditions .= " AND op.ship_code not like 'kd%'";
    			$dbCommand->join("$orderPackageDetail AS opd", "opd.order_id=o.order_id");
    			$dbCommand->join("$orderPackageTab AS op", "op.package_id=opd.package_id");
    			$dbCommand->andWhere($filterConditions);
    			$dbCommand->group("o.order_id");
    			//海外仓和本地仓之分
    			if($isLocalWarehouse){
    				$dbCommand->andWhere("op.warehouse_id='' OR op.warehouse_id='{$localWarehouseID}'");
    			}else{
    				$dbCommand->andWhere("op.warehouse_id<>'' AND op.warehouse_id<>'{$localWarehouseID}'");
    			}
    			break;
    		case 4://待取消,不支持海外仓
    			$select = 'o.order_id,o.account_id,o.paytime,o.ori_pay_time,o.ship_country,o.platform_order_id,o.complete_status';
    			$filterConditions = "o.complete_status IN( " . Order::COMPLETE_STATUS_WAIT_CANCEL  . ") ";
    			$filterConditions .= " AND TIMESTAMPDIFF(hour,o.paytime,'".$gmtime."') > (8)";//超过8小时
    			$filterConditions .= " AND TIMESTAMPDIFF(hour,o.paytime,'".$gmtime."') < (48)";//未超过48小时
    			$dbCommand->andWhere('o.ship_status='.Order::SHIP_STATUS_NOT); //待取消和待处理的都是未发货的
    			$dbCommand->andWhere($filterConditions);
    			break;
    			
    		case 10://指定订单ID
    			if(empty($orderId))
    				return array();
    			$select = 'o.order_id,o.account_id,o.paytime,o.ori_pay_time,o.ship_country,o.platform_order_id,op.package_id,op.ship_code,op.track_num,o.complete_status';
    			$filterConditions = " o.complete_status IN ( " . Order::COMPLETE_STATUS_PROCESSIBLE .",". Order::COMPLETE_STATUS_END .",". Order::COMPLETE_STATUS_PENGDING .") 
    								AND op.is_repeat = 0 AND op.track_num!=''";
    			
    			$dbCommand->join("$orderPackageDetail AS opd", "opd.order_id=o.order_id");
    			$dbCommand->join("$orderPackageTab AS op", "op.package_id=opd.package_id");
    			$dbCommand->andWhere($filterConditions);
    			$dbCommand->group("o.order_id");
    			//海外仓和本地仓之分
    			if($isLocalWarehouse){
    				$dbCommand->andWhere("op.warehouse_id='' OR op.warehouse_id='{$localWarehouseID}'");
    			}else{
    				$dbCommand->andWhere("op.warehouse_id<>'' AND op.warehouse_id<>'{$localWarehouseID}'");
    			}
    			break;
    		default:
    			return array();
    	}
    	$dbCommand->select($select);
    	if (!empty($limit))
    		$dbCommand->limit((int)$limit, 0);
    	 
    	if(!empty($orderId)){
    		if(!is_array($orderId)) $orderId = array($orderId);
    		$dbCommand->andWhere(array("IN", "o.order_id", $orderId));
    	}
    	$month = date("m");
    	$year = (int)date("y");
    	$lasty = $year-1;
    	if($month == "01"){
    		$dbCommand->andWhere("(o.order_id like 'co{$year}%' OR o.order_id like 'co{$lasty}12%')");
    	}else{
    		$dbCommand->andWhere("o.order_id like 'co{$year}%'");
    	}
    	if(isset($_REQUEST['bug']) && $_REQUEST['bug']){
    		echo "================SQL:===============<br/>";
    		echo $dbCommand->text;
    		echo "<br/>";
    	}
    	return $dbCommand->queryAll();
    }
    
	/**
	 * @desc 获取欠货待处理sku列表
	 * @return CDbCommand
	 */
    public function getOweWaitingConfirmOrdersSkuListByCondition($conditions = null, $params = null, $limits = null, $select = "sku"){
    	//原始sql
    	/* $sql = "select B.sku from ueb_order.ueb_order A 
    			left join ueb_order.ueb_order_detail B on B.order_id = A.order_id 
    			where A.complete_status = 1 and B.pending_status = 1 
    			group by B.sku"; */
    	$command = $this->getDbConnection()->createCommand()
    					->from($this->tableName() . " as A")
    					->select($select)
    					->leftJoin(OrderDetail::model()->tableName() . " as B", "B.order_id = A.order_id")
    					->where("A.complete_status = 1 and B.pending_status = 1")
    					->group("B.sku");
    	if($conditions){
    		$command->andWhere($conditions, $params);
    	}
    	if($limits){
    		$limitsarr = explode(",", $limits);
    		$limit = isset($limitsarr[1]) ? trim($limitsarr[1]) : 0;
    		$offset = isset($limitsarr[0]) ? trim($limitsarr[0]) : 0;
    		$command->limit($limit, $offset);
    	}
    	$result = $command->queryAll();
    	return $result;	
    }
    
    /**
     * @desc 设置订单为异常订单
     * @param unknown $orderID
     * @param unknown $exceptionType
     * @param string $exceptionReason
     * @return boolean
     */
    public function setExceptionOrder($orderID, $exceptionType, $exceptionReason = ''){
    	//把订单标为异常状态
    	$res = $this->setOrderCompleteStatus(Order::COMPLETE_STATUS_EXCEPTION, $orderID);
    	if(!$res){
    		return false;
    	}
    	//写入异常记录表
		$params = array(
			'exception_reason'	=>	$exceptionReason,
			'status'			=>	OrderExceptionCheck::STATUS_DEFAULT
		);    	
    	return OrderExceptionCheck::model()->addExceptionRecord($orderID, $exceptionType, $params);
    }
    
    /**
     * 获取joom付款超过3天还没有跟踪号的订单
     * @param integer $limit string $gmtime integer $accountId string $orderId
     */
    public function getJoomWaitingConfirmOrders($accountId,$gmtime,$orderId,$limit = null) {
    	if(!$accountId) return null;
    	$dbCommand = $this->dbConnection->createCommand()
			    	->select('o.order_id,o.account_id,o.paytime,o.platform_order_id')
			    	->from(self::tableName() . " o")
			    	->where("o.payment_status = ".Order::PAYMENT_STATUS_END)
			    	->andWhere('o.ship_status='.Order::SHIP_STATUS_NOT)
			    	->andWhere('o.complete_status != '.Order::COMPLETE_STATUS_END)
			    	->andWhere("TIMESTAMPDIFF(hour,o.paytime,'".$gmtime."') > (36)")
			    	->andWhere("TIMESTAMPDIFF(hour,o.paytime,'".$gmtime."') < (54)") //54小时内
			    	->andWhere("o.platform_code = '".Platform::CODE_JOOM."'")
			    	->andWhere('o.account_id="'.$accountId.'"')
			    	->andWhere('o.paytime >= "2015-12-31 00:00:00"')
			    	->order("o.paytime asc");
    	
    	if (!empty($limit))
    		$dbCommand->limit((int)$limit, 0);
    	
    	if(!empty($orderId)){
    		$dbCommand->andWhere('o.order_id like "'.$orderId.'%"');
    	}
    	if(isset($_REQUEST['bug']) && $_REQUEST['bug']){
    		echo $dbCommand->text,"<br/>";
    	}
    	//echo $dbCommand->text;exit;
    	return $dbCommand->queryAll();
    }

    /**
     * @desc 根据平台订单号获取订单信息
     * @param string $platformOrderID
     * @param string $platformCode
     * @return mixed
     */
    public function getOrderInfosByPlatformOrderIs($platformOrderID, $platformCode=''){
        $where = '';
        if($platformCode){
            $where .= '  platform_code = "'.$platformCode.'"  AND  ';
        }
        return $this->dbConnection->createCommand()
                    ->select('*')
                    ->from(self::tableName())
                    ->where($where.'platform_order_id = "'.$platformOrderID.'"')
                    ->queryRow();
    }   

    /**
     * 保存速卖通订单主体信息
     * @param array $data
     * 
     */
    public function saveAliOrderInfo($data){
        $model = new self();
        foreach ($data as $key => $value) {
            $model->setAttribute($key, $value);
        }
        $result =  $model->save();
        if($result){
            return  array('order_id' => $data['order_id'],'status' => 2,'ship_country_name'=> $data['ship_country_name']);
        }else{
            return array('order_id' => '','status' => -1);
        }
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
        
}