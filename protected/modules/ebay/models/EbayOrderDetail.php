<?php

/**
 * @desc Ebay订单明细表
 * @author yangsh
 * @since 2016-06-08
 */
class EbayOrderDetail extends EbayModel {

    protected $_ExceptionMsg;

    /** @var int 账号id */
    protected $_AccountID;    

	 public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_ebay_order_detail';
    }

    /**
     * 设置异常信息
     * @param string $message           
     */
    public function setExceptionMessage($message) {
        $this->_ExceptionMsg = $message;
        return $this;
    }

    public function getExceptionMessage() {
        return $this->_ExceptionMsg;
    }

    /**
     * 设置账号ID
     * @param int $accountID
     */
    public function setAccountID($accountID) {
        $this->_AccountID = $accountID;
        return $this;
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
     * 插入数据
     */
    public function addNewData($data) {
        $isOk = $this->dbConnection->createCommand()->insert(self::tableName(), $data);
        if ($isOk) {
            return $this->dbConnection->getLastInsertID();
        }
        return false;
    }

    /**
     * 检查近2个月订单明细，判断订单行ID是否已存在
     * @param  string $orderLineItemID 
     * @return boolean
     */
    public function checkOrderLineItemID($orderLineItemID) {
        //查找明细表
        $row = $this->dbConnection->createCommand()
                    ->select('platform_order_id')
                    ->from(self::tableName())
                    ->where("order_line_item_id = :orderLineItemID", array(':orderLineItemID'=> $orderLineItemID))
                    ->andWhere("created_date > :createDate", array(':createDate'=> date('Y-m-d',strtotime('-2 months'))) )
                    ->queryRow();
        //记录不为空且订单号和订单行ID不相等，订单号为12位数字
        if (!empty($row) && $row['platform_order_id'] != $orderLineItemID
             && preg_match("/^\d{12}$/i", $row['platform_order_id']) ) {
            return true;
        }
        return false;
    }

    /**
     * [saveOrderDetail description]
     * @param  object $order 
     * @return boolean
     */
    public function saveOrderDetail ($order) {
        try {
            $ebayKeys                       = ConfigFactory::getConfig('ebayKeys');
            $ebayOrderMain                  = EbayOrderMain::model();
            $accountID                      = $this->_AccountID;
            $platformOrderId                = trim($order->OrderID);
            $nowTime                        = date('Y-m-d H:i:s');
            $transactions                   = $order->TransactionArray->Transaction;
            unset($order);

            $orderLineItemIDArr             = array();
            foreach ($transactions as $trans) {
                $orderLineItemIDArr[]       = trim($trans->OrderLineItemID);
            }
            $this->dbConnection->createCommand()->delete(self::tableName(), array('in', 'order_line_item_id', $orderLineItemIDArr) );

            $totalDiscount                  = 0;//折扣金额
            $totalActualShippingCost        = 0; 
            $totalActualHandlingCost        = 0;
            $totalInsuranceFee              = 0; 
            $totalTaxAmount                 = 0;
            $fvfAmt                         = 0;
            foreach ($transactions as $trans) {
                $orderLineItemID            = trim($trans->OrderLineItemID);

                $currency                   = (string)$trans->TransactionPrice->attributes()->currencyID;//刊登站点币种
                $fvfCurrency                = (string)$trans->FinalValueFee->attributes()->currencyID;//成交费币种
                $finalValueFee              = (float)$trans->FinalValueFee;
                if ( isset($trans->Variation) ) { // 多属性产品
                    if (isset($trans->Variation->SKU) && trim($trans->Variation->SKU) != '') {
                        $skuOnline          = trim($trans->Variation->SKU);
                    } else {
                        $skuOnline          = '';
                    }
                    $title                  = trim($trans->Variation->VariationTitle);
                    $isVaration             = 1;//多属性
                } else {
                    $skuOnline              = trim($trans->Item->SKU);
                    $title                  = trim($trans->Item->Title);
                    $isVaration             = 0;
                }
                $email                      = isset( $trans->Buyer->Email ) && 'Invalid Request' != trim($trans->Buyer->Email) 
                                                ? trim($trans->Buyer->Email) : '';

                $conditionId = isset($trans->Item->ConditionID) ? trim($trans->Item->ConditionID) : '';                                                
                //购买数量                                                
                $quantity = (int)$trans->QuantityPurchased;

                //item交易金额
                $price = (float)$trans->TransactionPrice;
                      
                //折扣金额,Version 893 or lower 折后价，大于893版本使用折扣价
                $discount = $itemDiscount = $shippingDiscount = 0;                                             
                if (isset($trans->SellerDiscounts)) {
                    if (isset($trans->SellerDiscounts->SellerDiscount->ItemDiscountAmount)
                         && isset($trans->SellerDiscounts->OriginalItemPrice)) {
                        $originalItemPrice = floatval($trans->SellerDiscounts->OriginalItemPrice);//原价
                        $itemDiscountAmount = floatval($trans->SellerDiscounts->SellerDiscount->ItemDiscountAmount);
                        if ($ebayKeys['compatabilityLevel'] <= 893) {
                            $itemDiscount = round($originalItemPrice - $itemDiscountAmount,2);//itemDiscountAmount为折后价
                        } else {
                            $itemDiscount = $itemDiscountAmount;//itemDiscountAmount为折扣价
                        }
                    }
                    if (isset($trans->SellerDiscounts->SellerDiscount->ShippingDiscountAmount)
                         && isset($trans->SellerDiscounts->OriginalItemShippingCost) ) {
                        $originalItemShippingCost = floatval($trans->SellerDiscounts->OriginalItemShippingCost);//原价
                        $shippingDiscount = floatval($trans->SellerDiscounts->SellerDiscount->ShippingDiscountAmount) ;//折扣价
                    }
                    $discount += ($itemDiscount + $shippingDiscount) * $quantity;
                }                                     
                                                                
                $actualShippingCost         = isset($trans->ActualShippingCost)
                                                ? (float)$trans->ActualShippingCost : 0;
                $actualHandlingCost         = isset($trans->ActualHandlingCost) 
                                                ? (float)$trans->ActualHandlingCost : 0;
                $insuranceFee               = isset($trans->ShippingDetails->InsuranceFee) 
                                                ? (float)$trans->ShippingDetails->InsuranceFee : 0;
                $taxAmount                  = isset($trans->Taxes->TotalTaxAmount) ? (float)$trans->Taxes->TotalTaxAmount : 0;

                $totalDiscount              += $discount;
                $totalActualShippingCost    += $actualShippingCost;
                $totalActualHandlingCost    += $actualHandlingCost;
                $totalInsuranceFee          += $insuranceFee;
                $totalTaxAmount             += $taxAmount;
                $fvfAmt                     += $finalValueFee;

                $insertData = array(
                    'order_line_item_id'    => $orderLineItemID,
                    'platform_order_id'     => $platformOrderId,
                    'seller_account_id'     => $accountID,
                    'item_id'               => trim($trans->Item->ItemID),
                    'transaction_id'        => trim($trans->TransactionID),     
                    'site'                  => trim($trans->Item->Site),
                    'title'                 => $title,
                    'sku'                   => $skuOnline,
                    'is_varation'           => $isVaration,
                    'condition_id'          => $conditionId,
                    'quantity'              => $quantity,
                    'price'                 => $price,
                    'transaction_siteid'    => trim($trans->TransactionSiteID),
                    'currency'              => $currency,
                    'fvf_amt'               => $finalValueFee,
                    'fvf_currency'          => $fvfCurrency,
                    'shipping_cost'         => $actualShippingCost,
                    'handling_cost'         => $actualHandlingCost, 
                    'insurance_fee'         => $insuranceFee,
                    'tax_amount'            => $taxAmount,
                    'item_discount'         => $itemDiscount,
                    'shipping_discount'     => $shippingDiscount,
                    'email'                 => $email,
                    'static_alias'          => isset($trans->Buyer->StaticAlias) ? trim($trans->Buyer->StaticAlias) : '',
                    'created_date'          => date ( 'Y-m-d H:i:s', strtotime ( trim($trans->CreatedDate) ) ), 
                    'created_at'            => $nowTime
                );
                $this->addNewData($insertData);
            }
            //更新订单主表数据
            $updateData = array(
                'discount'                  => $totalDiscount,
                'shipping_cost'             => $totalActualShippingCost,
                'handling_cost'             => $totalActualHandlingCost,    
                'insurance_fee'             => $totalInsuranceFee,        
                'tax_amount'                => $totalTaxAmount,
                'fvf_amt'                   => $fvfAmt,
                'fvf_currency'              => $fvfCurrency,
            );
            $ebayOrderMain->updateByPlatformID($platformOrderId,$updateData);
            return true;            
        } catch (Exception $e) {
            $this->setExceptionMessage($e->getMessage().'####'.json_encode($insertData,$updateData));
            return false;
        }
    }    

    /**
     * [getFormatedOrderDetailInfos description]
     * @param  array $details 
     * @return array
     */
    public static function getFormatedOrderDetailInfos($order,$details) {
        //验证订单明细数据是否完整
        $platformOrderId = $order['platform_order_id'];//平台订单号
        if ( preg_match("/^\d{12}$/i", $platformOrderId)
             && count($details) == 1 ) {//合并支付订单记录大于1
            return false;
        }

        $subTotalPrice  = floatval($order['subtotal']);//订单产品总金额,不含运费、处理费和税费等
        $totalPrice     = 0;//订单明细总金额
        foreach ($details as $orderDetail) {
            $itemTotalPrice = floatval($orderDetail['price']) * intval ( $orderDetail['quantity'] );//item 总金额
            $totalPrice += $itemTotalPrice;
        }
        if ( round($subTotalPrice,2) != round($totalPrice,2) ) {
            //echo round($orderSubtotal,2),'--',round($totalPrice,2),'<br>';
            return false;
        }

        $formatDetails     = array();//订单明细表数据
        $partFormatDetails = array();//订单sku与销售关系数据
        $formatDetailsExts = array();//订单明细扩展表数据

        $orderTotalPrice   = floatval($order['total']);//订单总金额=实际交易金额
        $totalShipFee      = floatval($order['shipping_service_cost']);//总运费
        $totalTaxFee       = floatval($order['tax_amount']);//总税费
        $totalInsuranceFee = floatval($order['insurance_fee']);//运费险
        $totalFeeAmt       = (float)EbayOrderTransaction::model()->getOrderFeeAmt($platformOrderId);//paypal手续费
        
        //订单成交费,USD
        $totalFvf          = floatval($order['fvf_amt']);
        if ($totalFvf>0) {//转换币种
            $totalFvf      = $totalFvf * CurrencyRate::getRateByCondition($order['fvf_currency'],$order['currency']); 
            $totalFvf      = round($totalFvf,2);
        }

        //优惠金额
        $totalDiscount = round($subTotalPrice + $totalShipFee - $orderTotalPrice,2);
        if ($totalDiscount < 0) {
            $totalDiscount = 0;
        }
        
        $nowTime      = date("Y-m-d H:i:s");
        $ebaySiteIDs  = EbaySite::getSiteIDs();
        $listCount    = count($details);
        $index        = 1;
        $tmpshipFee   = $tmpDiscount = $tmpFvf = 0;
        $tmpFeeAmt    = $tmpTaxFee = $tmpInsuranceFee = 0;
        $tmpItemSalePriceAllot = 0;
        $isNeedMatchAdapter   = true;//是否需要匹配插头,如果是深圳仓账号+海外仓账号且深圳仓发货，需匹配插头
        $orderSkuExceptionMsg = '';//记录订单异常信息;
        foreach ($details as $orderDetail) {
            $row = array();
            //1.格式化前数据验证
            $orderLineItemID    = $orderDetail['order_line_item_id'];      
            $skuOnline          = $orderDetail['sku'];

            $title              = trim($orderDetail['title']);
            if (mb_strlen($title)>100) {//title超长,截取OMS定义的长度值
                $title          = mb_substr($title,0,100);
            }

            if ($skuOnline != '' && $skuOnline != 'unknown' ) {//unknow历史拉单保存进来的，这里做兼容
                $sku            = encryptSku::getRealSku ( $skuOnline );
                $skuInfo        = Product::model()->getProductInfoBySku( $sku );
            } else {
                $sku            = 'unknown';
                $skuInfo        = array();
            }
            
            $skuInfo2 = array();//发货sku信息
            $pending_status     = OrderDetail::PEDNDING_STATUS_ABLE;
            if (! empty ( $skuInfo )) { // 可以查到对应产品
                $realProduct    = Product::model()->getRealSkuListNew($sku,$orderDetail['quantity'],$skuInfo);
                if ($skuInfo['sku'] == $realProduct['sku']) {
                    $skuInfo2   = $skuInfo;
                } else {
                    $skuInfo2   = Product::model()->getProductInfoBySku( $realProduct['sku'] );
                }
            }

            if(empty($skuInfo) || empty($skuInfo2)) {
                $realProduct    = array (
                    'sku'       => 'unknown',
                    'quantity'  => $orderDetail['quantity']
                );
                $pending_status = OrderDetail::PEDNDING_STATUS_KF;
                $orderSkuExceptionMsg .= "sku信息不存在;";
            }

            if($skuInfo2 && $skuInfo2['product_is_multi'] == Product::PRODUCT_MULTIPLE_MAIN){
                $childSku = ProductSelectAttribute::model()->getChildSKUListByProductID($skuInfo2['id']);
                if (!empty($childSku)) {//$sku为主sku
                    $pending_status = OrderDetail::PEDNDING_STATUS_KF;
                    $orderSkuExceptionMsg .= "{$skuInfo2['sku']}为主sku;";
                }
            }

            //eBayMotors站归到US站
            if ('eBayMotors' == $orderDetail['site'] && 'USD' == $orderDetail['currency'] ) {
                $orderDetail['site'] = 'US';
            }

            $unitSalePrice = floatval ( $orderDetail['price'] );//销售单价(含成交费)
            $quantity      = intval ( $orderDetail['quantity'] );//购买数量
            $itemSalePrice = $unitSalePrice * $quantity;//产品金额

            if ($index == $listCount) {
                $shipFee                = round($totalShipFee - $tmpshipFee,2);
                $discount               = round($totalDiscount - $tmpDiscount,2);
                $fvfAmt                 = round($totalFvf - $tmpFvf,2);
                $feeAmt                 = round($totalFeeAmt - $tmpFeeAmt,2);
                $taxFee                 = round($totalTaxFee - $tmpTaxFee,2);
                $insuranceFee           = round($totalInsuranceFee - $tmpInsuranceFee,2);
                $itemSalePriceAllot     = round($subTotalPrice - $totalDiscount - $tmpItemSalePriceAllot, 2);//平摊后的item售价
                $unitSalePriceAllot     = round($itemSalePriceAllot/$quantity, 3);//平摊后的单价
            } else {
                $rate                  = $itemSalePrice/$subTotalPrice;
                $shipFee               = round($rate * $totalShipFee,2);//平摊后的运费 
                $discount              = round($rate * $totalDiscount,2);//平摊后的优惠金额
                $fvfAmt                = round($rate * $totalFvf,2);//平摊后的成交费
                $feeAmt                = round($rate * $totalFeeAmt,2);//平摊后的手续费
                $taxFee                = round($rate * $totalTaxFee,2);//平摊后的税费
                $insuranceFee          = round($rate * $totalInsuranceFee,2);//平摊后的运费险
                $itemSalePriceAllot    = round($itemSalePrice - $discount, 2);//平摊后的item售价
                $unitSalePriceAllot    = round($itemSalePriceAllot/$quantity, 3);//平摊后的单价

                $tmpshipFee            += $shipFee;
                $tmpDiscount           += $discount;
                $tmpFvf                += $fvfAmt;
                $tmpFeeAmt             += $feeAmt;
                $tmpTaxFee             += $taxFee;
                $tmpInsuranceFee       += $insuranceFee;
                $tmpItemSalePriceAllot += $itemSalePriceAllot;
            }
            $index++;
                    
            //检查是否需要匹配插头, 海外仓账号检测location是否为shenzhen
            if ( $isNeedMatchAdapter && in_array($orderDetail['seller_account_id'], EbayAccount::$OVERSEAS_ACCOUNT_ID) ) {
                $ebayProductInfo = EbayProduct::model()->getOneByCondition('location',"item_id='{$orderDetail['item_id']}'");
                if (empty($ebayProductInfo) || ! preg_match("/shenzhen|深圳|广东/i", $ebayProductInfo['location'] ) ) {
                    $isNeedMatchAdapter = false;
                }
            }

            //2.组装OMS需要的数据格式  
            $detailData = array(
                'transaction_id'    => $orderDetail['transaction_id'],
                'item_id'           => $orderDetail['item_id'],
                'title'             => $orderDetail['title'],
                'sku_old'           => $sku,//系统sku
                'sku'               => $realProduct['sku'],//实际sku
                'site'              => $orderDetail['site'],
                'quantity_old'      => $quantity,//购买数量
                'quantity'          => $realProduct['quantity'],//实际发货数量
                'sale_price'        => $unitSalePrice,//单价(含成交费)
                'total_price'       => round($itemSalePrice + $shipFee,2),//产品金额+平摊后的item运费          
                'ship_price'        => $shipFee,//平摊后的item运费
                'final_value_fee'   => $fvfAmt,//平摊后的成交费
                'currency'          => $orderDetail['currency'],
                'pending_status'    => $pending_status,
                'create_time'       => date("Y-m-d H:i:s")
            );
            $formatDetails[$orderLineItemID] = $detailData;

            //组装订单明细扩展表数据
            $detailExtData = array(
                'item_sale_price'        => $itemSalePrice,//产品金额(含成交费)
                'item_sale_price_allot'  => $itemSalePriceAllot,//平摊后的产品金额(含成交费，减优惠金额)
                'unit_sale_price_allot'  => $unitSalePriceAllot,//平摊后的单价(原销售单价-平摊后的优惠金额)
                'coupon_price_allot'     => $discount,//平摊后的优惠金额
                'tax_fee_allot'          => $taxFee,//平摊后的税费
                'insurance_amount_allot' => $insuranceFee,//平摊后的运费险
                'fee_amt_allot'          => $feeAmt,//平摊后的手续费
            );
            $formatDetailsExts[$orderLineItemID] = $detailExtData;

            //组装订单sku与销售数据
            $orderSkuData = array(
                'sku_online'        => $skuOnline == ''?'unknown':$skuOnline,//在线sku,
                'sku_old'           => $sku,//系统sku
                'site'              => isset($ebaySiteIDs[$orderDetail['site']]) ? $ebaySiteIDs[$orderDetail['site']] : -1,
                'item_id'           => $orderDetail['item_id'],
            );
            $partFormatDetails[$orderLineItemID] = $orderSkuData;
        }

        //返回订单处理
        $orderDatas = array(
            'final_value_fee'      => $totalFvf,//成交费
            'insurance_amount'     => $totalInsuranceFee,//运费险
            'isNeedMatchAdapter'   => $isNeedMatchAdapter,//是否需求匹配转接头
            'orderSkuExceptionMsg' => $orderSkuExceptionMsg,//订单sku异常信息
        );

        //组装订单扩展表数据
        $orderExtendInfo = array(
            'platform_order_id'    => $order['platform_order_id'],
            'account_id'           => $order['seller_account_id'],
            'tax_fee'              => $totalTaxFee,//税费
            'coupon_price'         => $totalDiscount,//优惠金额
            'currency'             => $order['currency'],//币种
            'payment_type'         => $order['payment_method'],
            'logistics_type'       => $order['shipping_service'],            
        );

        return array($formatDetails,$partFormatDetails,$orderDatas,$orderExtendInfo,$formatDetailsExts);
    }

}