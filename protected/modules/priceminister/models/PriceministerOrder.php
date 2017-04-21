<?php
/**
 * @desc PM订单拉取
 * @author LIHY
 * @since 2016-07-1
 */
class PriceministerOrder extends PriceministerModel{
	
    const EVENT_NAME = 'getorder';
    
    const EVENT_NAME_PULL_ORDER = 'pullorder';
    
    const DEFAULT_CURRENCY = "EUR";
    /** @var object 拉单返回信息*/
    public $orderResponse = null;
    
    /** @var int 账号ID*/
    public $_accountID = null;
    
    /** @var string 异常信息*/
    public $exception = null;
    
    /** @var int 日志编号*/
    public $_logID = 0;

    private $finishMark = true;
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 切换数据库连接
     * @see WishModel::getDbKey()
     */
    public function getDbKey() {
        return 'db_oms_order';
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_order';
    }
    
    /**
     * @desc 设置账号ID
     */
    public function setAccountID($accountID){
        $this->_accountID = $accountID;
    }
    
    /**
     * @desc 设置异常信息
     * @param string $message
     */
    public function setExceptionMessage($message){
        $this->exception = $message;
    }
    
    /**
     * @desc 获取异常信息
     * @return string
     */
    public function getExceptionMessage(){
    	return $this->exception;
    }
    /**
     * @desc 设置日志编号
     * @param int $logID
     */
    public function setLogID($logID){
        $this->_logID = $logID;
    }
    
    /**
     * @desc 获取拉单开始时间
     */
    public function getTimeSince($accountID){
        $lastLog = PriceministerLog::model()->getLastLogByCondition(array(
                'account_id'    => $accountID,
                'event'         => self::EVENT_NAME,
                'status'        => PriceministerLog::STATUS_SUCCESS,
        ));
        $lastEventLog = array();
        if( !empty($lastLog) ){
            $lastEventLog = PriceministerLog::model()->getEventLogByLogID(self::EVENT_NAME, $lastLog['id']);
        }
        return (!empty($lastEventLog) && $lastEventLog['complete_time'] != "1997-12-12 00:00:00") ? 
       	 		date('Y-m-d H:i:s',strtotime($lastEventLog['complete_time']) - (7+6)*3600) 
  				: date('Y-m-d H:i:s',time() - 7*24*3600 - 3600*7);
    }
    
    /**
     * @desc 获取最新订单
     * @throws Exception
     * @return boolean
     */
    public function getNewOrders(){
    	try{
    		$request = new GetNewSalesRequest();
    		$response = $request->setAccount($this->_accountID)->setRequest()->sendRequest()->getResponse();
    		if($request->getIfSuccess()){
    			echo "<pre>";
    			if(isset($response->response->sales->sale)){
    				foreach ($response->response->sales->sale as $sale){
    					$res = $this->saveOrderData($sale);
    					if(isset($_REQUEST['bug'])){
    						print_r($sale);
    						var_dump($res);
    					}
    					//if($res){
    						//@todo 接受订单,上线后开启
    					foreach ($sale->items->item as $item){
    						$acceptRequest = new AcceptOrRefuseSalesRequest;
    						$itemID = trim($item->itemid);
    						$acceptRequest->setAccount($this->_accountID)->setItemId($itemID);
    						$acceptResponse = $acceptRequest->setRequest()->sendRequest()->getResponse();
    						if(isset($_REQUEST['bug'])){
    							var_dump($acceptResponse);
    						}
    						if(!$acceptRequest->getIfSuccess()){
    							if(isset($_REQUEST['bug'])){
    								echo $acceptRequest->getErrorMsg();
    							}
    							throw new Exception($acceptRequest->getErrorMsg());
    						}
    					}
    					//}
    				}
    			}
    		}else{
    			throw new Exception($request->getErrorMsg());
    		}
    	}catch (Exception $e){
    		$this->setExceptionMessage($e->getMessage());
    		return false;
    	}
    	return true;
    }
    
    /**
     * @desc 拉取订单
     * @param unknown $sinceTime
     * @throws Exception
     * @return boolean
     */
    public function getOrdersByTime($sinceTime){
    	try{
    		$request = new GetCurrentSalesRequest();
    		$nextToken = null;
    		do{
    			$request->setPurchaseData($sinceTime);
    			$request->setNotshippeditemsonly(true);
    			if($nextToken){
    				$request->setNexttoken($nextToken);
    			}
    			$response = $request->setAccount($this->_accountID)->setRequest()->sendRequest()->getResponse();
    			if(isset($_REQUEST['bug'])){
    				echo "<pre>";
    				var_dump((string)$response->response->nexttoken);
    				print_r($response);
    				
    			}
    			if($request->getIfSuccess()){
    				$nextToken = (string)$response->response->nexttoken;
    				
    				if(isset($response->response->sales->sale)){
    					foreach ($response->response->sales->sale as $sale){
    						if(isset($_REQUEST['bug'])){
    							print_r($sale);
    						}
    						$this->saveOrderData($sale);
    					}
    				}
    			}else{
    				throw new Exception($request->getErrorMsg());
    			}
    		}while ($nextToken);
    	}catch (Exception $e){
    		echo $e->getMessage();
    		$this->setExceptionMessage($e->getMessage());
    		return false;
    	}
    	return true;
    }
    
    /**
     * @desc 保存订单
     * @param unknown $order
     * @throws Exception
     * @return boolean
     */
    public function saveOrderData($order){
    	$dbTransactionModel = $this;
    	$dbTransaction = $dbTransactionModel->dbConnection->getCurrentTransaction();
    	if( !$dbTransaction ){
    		$dbTransaction = $dbTransactionModel->dbConnection->beginTransaction();//开启事务
    	}
    	try{
    		$this->orderResponse = $order;
    		$orderID = $this->saveOrderInfo();
    		if(!$orderID){
    			return false;
    		}
    		$this->saveOrderDetail($orderID);
    		$this->saveOrderTransaction($orderID);
    		$this->saveOrderPayRecord($orderID);
    		$dbTransaction->commit();
    	}catch (Exception $e){
    		$dbTransaction->rollback();
    		$msg = Yii::t('ebay', 'Save Order Infomation Failed');
    		throw  new Exception($e->getMessage());
    	}
    	return true;
    }
    
    /**
     * @desc 保存主订单信息
     * @throws Exception
     * @return boolean|Ambigous <string, unknown>
     */
    public function saveOrderInfo(){
        $order_id  = AutoCode::getCodeNew('order'); // 获取订单号
        if ( empty($order_id) ) {
            throw new Exception("getCodeNew Error");
        } else {
            $order_id = $order_id . 'XPM';
        }        
    	$order = $this->orderResponse;
    	$platformOrderID = trim($order->purchaseid);
    	$orderInfo = Order::model()->getOrderInfoByPlatformOrderID($platformOrderID, Platform::CODE_PM);
    	if(isset($_REQUEST['bug'])){
    		var_dump(trim((string)$order->deliveryinformation->deliveryaddress->country));
    		var_dump(Country::model()->getAbbrByEname(trim((string)$order->deliveryinformation->deliveryaddress->country)));
    	}
    	//@todo 退款订单暂时不拉

        //存在已付款的订单，不更新
    	if( !empty($orderInfo) && $orderInfo['payment_status']==Order::PAYMENT_STATUS_END ){
    		return false;
    	}

        //获取订单号
    	$orderID = !empty($orderInfo) ? $orderInfo['order_id'] : $order_id;
        
        //订单号重复检查
        $tmpOrder = Order::model()->getInfoByOrderId($orderID,'order_id');
        if (!empty($tmpOrder)) {
            throw new Exception($orderID.'订单号重复!');
        }

		$data = array(
    			'order_id'              => $orderID,
    			'platform_code'         => Platform::CODE_PM,
    			'platform_order_id'     => $platformOrderID,
    			'account_id'            => $this->_accountID,
    			'log_id'                => $this->_logID,
    			'order_status'          => 'Completed',
    			'buyer_id'              => trim($order->deliveryinformation->purchasebuyerlogin),
    			'email'                 => trim($order->deliveryinformation->purchasebuyeremail),
    			'timestamp'             => date('Y-m-d H:i:s'),
    			'created_time'          => $this->formOrderData($order->purchasedate),
    			'last_update_time'      => $this->formOrderData($order->purchasedate),
    			'ship_cost'             => 0,//运费,无
    			'subtotal_price'        => 0,//产品总金额,由明线表更新
    			'total_price'           => 0,//订单金额,由明线表更新
                'final_value_fee'       => 0,//成交费,由明线表更新
                'insurance_amount'      => 0,//运费险(无)
    			'currency'              => self::DEFAULT_CURRENCY,
    			'ship_country'          => Country::model()->getAbbrByEname(trim((string)$order->deliveryinformation->deliveryaddress->country)),
    			'ship_country_name'     => trim($order->deliveryinformation->deliveryaddress->country),
    			'paytime'               => $this->formOrderData($order->purchasedate),
    			'payment_status'        => Order::PAYMENT_STATUS_END,
    			'ship_phone'            => !empty($order->deliveryinformation->deliveryaddress->phonenumber1) ? trim((string)$order->deliveryinformation->deliveryaddress->phonenumber1) : (string)$order->deliveryinformation->deliveryaddress->phonenumber2,//phonenumber1
    			'ship_name'             => trim($order->deliveryinformation->deliveryaddress->firstname) . " " .trim($order->deliveryinformation->deliveryaddress->lastname),
    			'ship_street1'          => trim($order->deliveryinformation->deliveryaddress->address1),
    			'ship_street2'          => trim($order->deliveryinformation->deliveryaddress->address2),
    			'ship_zip'              => isset($order->deliveryinformation->deliveryaddress->zipcode) ? trim($order->deliveryinformation->deliveryaddress->zipcode) : '',
    			'ship_city_name'        => !empty($order->deliveryinformation->deliveryaddress->city) ? trim($order->deliveryinformation->deliveryaddress->city) : '',
    			'ship_stateorprovince'  => !empty($order->deliveryinformation->deliveryaddress->city) ? trim($order->deliveryinformation->deliveryaddress->city) : '',
    			'ship_code'				=>	"",
    			//add lihy 2016-04-13
    			'ori_create_time'      => $this->formLocalOrderData($order->purchasedate),
    			'ori_update_time'      => $this->formLocalOrderData($order->purchasedate),
    			'ori_pay_time'         => $this->formLocalOrderData($order->purchasedate),
    	);
		if(isset($_REQUEST['bug'])){
			var_dump(trim((string)$order->deliveryinformation->deliveryaddress->country));
			var_dump(Country::model()->getAbbrByEname(trim((string)$order->deliveryinformation->deliveryaddress->country)));
			print_r($data);
		}
		//var_dump($data);
    	$flag = Order::model()->saveOrderRecord($data);
    	if(!$flag) throw new Exception("save failure");
    	return $orderID;
    }
    
    public function formOrderData($oriDate){
        $oriDate = trim($oriDate);
    	//13/07/2016-23:27
    	$dateArr = explode("-", $oriDate);
    	$dateArr2 = explode("/", $dateArr[0]);
    	return date("Y-m-d H:i:s", strtotime($dateArr2[2]."-".$dateArr2[1]."-".$dateArr2[0]." ".$dateArr[1]));
    }
    
    public function formLocalOrderData($oriDate){
        $oriDate = trim($oriDate);
    	//13/07/2016-23:27
    	$dateArr = explode("-", $oriDate);
    	$dateArr2 = explode("/", $dateArr[0]);
    	return date("Y-m-d H:i:s", strtotime($dateArr2[2]."-".$dateArr2[1]."-".$dateArr2[0]." ".$dateArr[1])+7*3600);
    }
  	/**
  	 * @desc 保存订单详情
  	 * @param unknown $orderID
  	 * @throws Exception
  	 * @return boolean
  	 */
    public function saveOrderDetail($orderID){
    	$order = $this->orderResponse;
        $platformOrderID = trim($order->purchaseid);

    	//删除详情
    	OrderDetail::model()->deleteOrderDetailByOrderID($orderID);

    	//统计价格
        $totalShipFee = 0;//运费为0
    	$subTotalPrice = 0;//产品总金额
    	$totalPrice = 0;//订单交易金额
    	$finalFee = 0;//成交费
        $totalDiscount = 0;//优惠金额
    	$quantity = 1;//购买数量
        foreach ($order->items->item as $item){
            $subTotalPrice += floatval($item->advertpricelisted->amount);//原始金额
            $totalPrice    += floatval($item->price->amount);//实际成交金额
            $totalDiscount += (floatval($item->advertpricelisted->amount) - floatval($item->price->amount));//优惠金额
        }
        $totalDiscount = round($totalShipFee,2);
        $finalFee = $totalPrice*0.1+0.15;
        $this->orderResponse->totalPrice = $totalPrice;
        $this->orderResponse->totalFee = $finalFee;        
        $totalFvf = $finalFee;//成交费

        //组装数据
        $orderSkuExceptionMsg = '';//订单sku异常信息
        $tmpShipFee = $tmpDiscount = $tmpTaxFee = 0;
        $tmpFvf = $tmpFeeAmt = $tmpInsuranceFee = 0;
        $tmpItemSalePriceAllot = 0;
        $listCount = count($order->items->item); 
        $index = 1;
    	foreach ($order->items->item as $item){
            $skuOnline = trim($item->sku);
    		$sku = encryptSku::getRealSku($skuOnline);
            $skuInfo = Product::model()->getProductInfoBySku($sku);

            $skuInfo2 = array();//发货sku信息
            $pending_status  = OrderDetail::PEDNDING_STATUS_ABLE;
            if (!empty($skuInfo)) {
                $realProduct = Product::model()->getRealSkuListNew($sku, $quantity, $skuInfo);
                $newsku = trim($realProduct['sku']);
                $realProduct['sku'] = $newsku;
                if ($newsku == $skuInfo['sku']) {
                    $skuInfo2 = $skuInfo;
                } else {
                    $skuInfo2 = Product::model()->getProductInfoBySku($newsku);
                }
            }

            if(empty($skuInfo) || empty($skuInfo2) ) {
    			$realProduct = array(
    					'sku'       => 'unknown',
    					'quantity'  => $quantity,
    			);
    			$orderSkuExceptionMsg .= 'sku信息不存在;';
    		}
    		
            if($skuInfo2 && $skuInfo2['product_is_multi'] == Product::PRODUCT_MULTIPLE_MAIN){
    			$childSku = ProductSelectAttribute::model()->getChildSKUListByProductID($skuInfo2['id']);
    			if(!empty($childSku)){
                    $orderSkuExceptionMsg .= "sku:{$skuInfo2['sku']}为主sku;";
    			}
    		}

            if($orderSkuExceptionMsg != '') {
                $pending_status = OrderDetail::PEDNDING_STATUS_KF;
            }

            $unitSalePrice = floatval($item->advertpricelisted->amount);//单价
            $itemSalePrice = $unitSalePrice * $quantity;//产品金额

            //平摊开始
            if ( $index == $listCount ) {
                $discount           = round($totalDiscount - $tmpDiscount,2);//平摊后的优惠金额
                $fvfAmt             = round($totalFvf - $tmpFvf, 2);////平摊后的成交费
                $itemSalePriceAllot = round($subTotalPrice - $totalDiscount - $tmpItemSalePriceAllot, 2);//平摊后的item售价
                $unitSalePriceAllot = round($itemSalePriceAllot/$quantity, 3);//平摊后的销售单价
            } else {
                $itemRate           = $itemSalePrice/$subTotalPrice;
                $discount           = round($itemRate * $totalDiscount, 2);//平摊后的优惠金额
                $fvfAmt             = round($itemRate * $totalFvf, 2);////平摊后的成交费
                $itemSalePriceAllot = round($itemSalePrice - $discount, 2);//平摊后的item售价
                $unitSalePriceAllot = round($itemSalePriceAllot/$quantity, 3);//平摊后的销售单价

                $tmpDiscount           += $discount;
                $tmpFvf                += $fvfAmt;
                $tmpItemSalePriceAllot += $itemSalePriceAllot;
            }
            $index++;

    		//保存
    		$itemData = array(
    				'transaction_id'          => $platformOrderID,
    				'order_id'                => $orderID,
    				'platform_code'           => Platform::CODE_PM,
    				'item_id'                 => trim($item->itemid),
    				'title'                   => trim($item->headline),
    				'sku_old'                 => $sku,
    				'sku'                     => $realProduct['sku'],
    				'site'                    => '',
    				'quantity_old'            => $quantity,//购买数量
    				'quantity'                => $realProduct['quantity'],//发货数量
    				'sale_price'              => $unitSalePrice,//单价
    				'total_price'             => round($itemSalePrice+0,2),//产品金额+平摊后的运费
                    'ship_price'              => 0,//平摊后的运费
                    'final_value_fee'         => $fvfAmt,//平摊后的成交费
    				'currency'                => trim($item->price->currency),
                    'pending_status'          => $pending_status,
                    'create_time'             => date('Y-m-d H:i:s')
    		);

    		$detailId = OrderDetail::model()->addOrderDetail($itemData);
    		if(!$detailId) throw new Exception("save order detail failure");

            //保存订单明细扩展表
            $orderItemExtendRow = array(
                'detail_id'              => $detailId,
                'item_sale_price'        => $itemSalePrice,//产品金额(含成交费)
                'item_sale_price_allot'  => $itemSalePriceAllot,//平摊后的产品金额(含成交费，减优惠金额)
                'unit_sale_price_allot'  => $unitSalePriceAllot,//平摊后的单价(原销售单价-平摊后的优惠金额)
                'coupon_price_allot'     => $discount,//平摊后的优惠金额
                'tax_fee_allot'          => 0,//平摊后的税费
                'insurance_amount_allot' => 0,//平摊后的运费险
                'fee_amt_allot'          => 0,//平摊后的手续费
            );
            $flag = OrderDetailExtend::model()->addOrderDetailExtend($orderItemExtendRow);
            if(!$flag) throw new Exception("save order detailExtend failure");

            //保存订单sku与销售关系数据
            $itemID = isset($item->ean) ? trim($item->ean) : 'unknown';//刊登号
            if (strpos($itemID, ';')>0) {
                $itemID = substr($itemID,0,-1);
            }
            $orderSkuData = array(
                'platform_code'         => Platform::CODE_PM,//平台code
                'platform_order_id'     => $platformOrderID,//平台订单号
                'online_sku'            => $skuOnline == '' ?'unknown':$skuOnline,//在线sku
                'account_id'            => $this->_accountID,//账号id
                'site'                  => '0',//站点
                'sku'                   => $sku,//系统sku
                'item_id'               => $itemID,//主产品id
                'order_id'              => $orderID,//系统订单号
            );   
            $addRes = OrderSkuOwner::model()->addRow($orderSkuData);
            if( $addRes['errorCode'] != '0' ){
                 throw new Exception("save order OrderSkuOwner failure");
            }         

    		//判断是否需要添加插头数据
    		$flag = OrderDetail::model()->addOrderAdapter(array(
    				'order_id'				=>	$orderID,
    				'ship_country_name'		=>	trim($order->deliveryinformation->deliveryaddress->country),
    				'platform_code'			=>	Platform::CODE_PM,
    				'currency'				=>	self::DEFAULT_CURRENCY
    		),	$realProduct);
    		if(!$flag) throw new Exception("save order adapter failure");
    	}
    	
        //记录sku异常信息
        if ($orderSkuExceptionMsg != '') {
            $cres = Order::model()->setExceptionOrder($orderID, OrderExceptionCheck::EXCEPTION_SKU_UNKNOWN, $orderSkuExceptionMsg);
            if(! $cres){
                throw new Exception ( 'Set order Exception Failure: '.$orderID);
            }
        }

        //保存订单扩展表数据        
        $orderExtend = new OrderExtend();
        $orderExtend->getDbConnection()->createCommand()->delete($orderExtend->tableName(),"platform_order_id='{$platformOrderID}' and platform_code='". Platform::CODE_PM ."'");
        $orderExtend->getDbConnection()->createCommand()->insert($orderExtend->tableName(),array(
            'order_id'          => $orderID,
            'platform_order_id' => $platformOrderID,//平台订单号
            'account_id'        => $this->_accountID,//账号id
            'platform_code'     => Platform::CODE_PM,
            'tax_fee'           => 0,//总税费
            'coupon_price'      => $totalDiscount,//总优惠
            'currency'          => self::DEFAULT_CURRENCY,
            'create_time'       => date('Y-m-d H:i:s')
        ));

        //更新订单主表
    	$flag = Order::model()->updateColumnByOrderID($orderID, array(
					'subtotal_price'	=>	$subTotalPrice,
					'total_price'		=>	$totalPrice,
					'final_value_fee'	=> 	$finalFee
    			));
    	if(!$flag) throw new Exception("upadte order failure");

    	return true;
    }
    
    /**
     * @desc 保存交易信息
     * @param unknown $orderID
     * @throws Exception
     * @return boolean
     */
    public function saveOrderTransaction($orderID){
    	$order = $this->orderResponse;
    	$flag = OrderTransaction::model()->saveTransactionRecord($orderID, $orderID, array(
    			'order_id'              => $orderID,
    			'first'                 => 1,
    			'is_first_transaction'  => 1,
    			'receive_type'          => OrderTransaction::RECEIVE_TYPE_YES,
    			'account_id'            => $this->_accountID,
    			'parent_transaction_id' => '',
    			'order_pay_time'		=> $this->formOrderData($order->purchasedate),
    			'amt'                   => floatval($order->totalPrice),
    			'fee_amt'               => 0,
    			'currency'              => self::DEFAULT_CURRENCY,
    			'payment_status'        => 'Completed',
    			'platform_code'         => Platform::CODE_PM,
    	));//保存交易信息
    	if($flag){
    		/* $flag = Order::model()->updateColumnByOrderID($orderID, array('payment_status' => Order::PAYMENT_STATUS_END));//保存为已付款
    		if($flag){
    			return $flag;
    		} */
    		return true;
    	}
    	throw new Exception("save order trans failure");
    }
    
    /**
     * @desc 保存交易记录
     * @param unknown $orderID
     * @throws Exception
     * @return boolean
     */
    public function saveOrderPayRecord($orderID){
    	$order = $this->orderResponse;
    	$flag = OrderPaypalTransactionRecord::model()->savePaypalRecord($orderID, $orderID, array(
    			'order_id'              => 	$orderID,
    			'receive_type'          => 	OrderPaypalTransactionRecord::RECEIVE_TYPE_YES,
    			'receiver_business'		=> 	'',
    			'receiver_email' 		=> 	'unknown@vakind.com',
    			'receiver_id' 			=> 	'',
    			'payer_id' 				=> 	'',
    			'payer_name' 			=> 	trim($order->deliveryinformation->deliveryaddress->firstname) . " " .trim($order->deliveryinformation->deliveryaddress->lastname),
    			'payer_email' 			=> 	'',
    			'payer_status' 			=> 	'',
    			'parent_transaction_id'	=>	'',
    			'transaction_type'		=>	'',
    			'payment_type'			=>	'',
    			'order_time'			=>	date('Y-m-d H:i:s', strtotime($order->purchasedate)),
    			'amt'					=>	floatval($order->totalPrice),
    			'fee_amt'				=>	0,
    			'tax_amt'				=>	0,
    			'currency'				=>	self::DEFAULT_CURRENCY,
    			'payment_status' 		=> 	'Completed',
    			'note'					=>	'',
    			'modify_time'			=>	''
    	));//保存交易付款信息
    	if($flag){
    		return true;
    	}
    	throw new Exception("save order trans paypal info failure");
    }
}