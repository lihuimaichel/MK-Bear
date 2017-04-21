<?php
/**
 * ALiexpress OrderDetails Model
 * @author	Rex
 * @since	2016-05-26
 */
class AliexpressOrderDetails extends AliexpressModel{
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_aliexpress_order_details';
    }
    
    /**
     * 保存订单主信息
     * @author	Rex
     * @since	2016-05-26
     */
    public function addNewData($data) {
    	$model = new self();
    	foreach ($data as $key => $value) {
    		$model->setAttribute($key, $value);
    	}
    	return $model->save();
    }

    /**
     * 根据平台系统订单号得到订单详情数据
     * @param unknown $platformOrderId
     * @return mixed
     */
    public function getOrderListByOrderId($platformOrderId) {
        return $this->getDbConnection()->createCommand()
        ->select('*')
        ->from(self::tableName())
        ->where("platform_order_id = '{$platformOrderId}'")
        ->queryAll();
    }

    /**
     * 对订单详情进行处理
     * @param array $order 订单主表信息
     * @param array $orderDetailList订单详情列表
     * @param string $orderId 订单ID
     * @param string $platform 平台CODE
     * @param float $fee_rate 费率
     * @param bool $note_flag 是否有订单备注
     * @param bool $isOverseasAccount 是否海外仓账号
     * @return array
     */
    public function getOrderDetailList($order,$orderDetailList,$orderId,$platform,$fee_rate,$note_flag=false,$isOverseasAccount){
        $dataArr = array();//结果数组

        //计算产品总金额
        $totalProductAmt = 0;
        foreach($orderDetailList as $list) {
            $productAmt  = $list['product_unit_price'] * $list['product_count'];//产品金额
            $totalProductAmt += $productAmt;
        }

        //订单实际交易金额
        $actualAmount = $order['actual_payment_amount'];
        if($order['actual_payment_amount'] == 0){
            $actualAmount = $order['amount'];
        }        

        //计算成交费
        if($order['logistics_amount'] > 0){
            $shipCostFee = $actualAmount - ($order['init_order_amount'] - $order['logistics_amount']);
            if($shipCostFee <= 0){
                $shipCostFee = $order['logistics_amount'];
            }
        }else{
            $shipCostFee = $order['logistics_amount'];
        }

        $subTotalPrice = $totalProductAmt;//产品总金额
        $totalMoney    = floatval($actualAmount);//订单总金额=实际交易金额
        $totalShipFee  = floatval($shipCostFee);//总运费 
        $totalDiscount = round($totalProductAmt + $totalShipFee - $totalMoney,2);//优惠金额 

        $totalFVF      = $tmpitemFee = $tmpDiscount = 0;
        $listCount     = count($orderDetailList);
        $index         = 1;
        foreach($orderDetailList as $list) {
            $productAmt  = $list['product_unit_price'] * $list['product_count'];//产品金额        
            $listingInfo = AliexpressProduct::model()->getOneByCondition('category_id',"aliexpress_product_id='{$list['product_id']}'");
            if ($listingInfo && $listingInfo['category_id']) {
                $categoryRate = AliexpressCategoryCommissionRate::getCommissionRate($listingInfo['category_id'],true);
            }
            if (empty($categoryRate)) {
                $categoryRate = 0.08;
            }
            if ($listCount > 1 ) {//多品
                if ($index == $listCount) {
                    $discount = round($totalDiscount - $tmpDiscount,2);
                } else {
                    $itemRate = $productAmt/$totalProductAmt;
                    $discount = round( $itemRate * $totalDiscount, 2);//平摊优惠金额
                    $tmpDiscount += $discount;
                }
                $itemfee = round($productAmt - $discount,2);
            } else {//单品
                $discount = $totalDiscount;
                $itemfee = round($totalMoney,2);
            }
            $index++;
            $finaleFVF = $itemfee * $categoryRate;  
            $totalFVF += $finaleFVF;
        }
        $totalFVF = round($totalFVF,2);

        //平摊运费、优惠金额、成交费
        $tmpshipFee = $tmpDiscount = $tmpFVF = 0;
        $tmpItemSalePriceAllot = 0;
        $orderSkuExceptionMsg = '';//记录订单sku异常信息;
        $warehouseExceptionMsg = '';//海外仓账号匹配异常信息
        $index = 1;
        $logisticsTypeArr = array();
        $warehouseIdArr = array();
        foreach($orderDetailList as $list) {
            //检查价格、数量是否正常
            if(empty($list['product_count']) ||$list['product_count'] ==0){
                return false;
            }
            if(empty($list['product_unit_price']) ||$list['product_unit_price'] ==0){
                return false;
            }
            if(empty($list['total_product_amount']) ||$list['total_product_amount'] ==0){
                return false;
            }

            //物流方式
            if(!in_array($list['logistics_type'],$logisticsTypeArr)) {
                $logisticsTypeArr[] = $list['logistics_type'];
            }
            
            $aliProductId    = $list['product_id'];
            $skuOnline       = isset($list['sku_code']) ? trim($list['sku_code']) : '';//在线sku
            $pending_status  = OrderDetail::PEDNDING_STATUS_ABLE;
            $sku             = $skuOnline == '' ? '' : encryptSku::getAliRealSku ( $skuOnline );//系统sku
            
            $skuInfo2        = array();//发货sku信息
            $skuInfo         = $sku == '' ? '' : Product::model()->getProductInfoBySku($sku);
            if($skuInfo){
                $realProduct = Product::model()->getRealSkuListNew($sku, $list['product_count'], $skuInfo);
                if ($skuInfo['sku'] == $realProduct['sku']) {
                    $skuInfo2   = $skuInfo;
                } else {
                    $skuInfo2   = Product::model()->getProductInfoBySku( $realProduct['sku'] );
                }                
            }

            if(empty($skuInfo) || empty($skuInfo2)){
                $realProduct = array(
                    'sku'      =>  'unknown',
                    'quantity' =>  $list['product_count']
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

            if ($note_flag) {
                $pending_status = OrderDetail::PEDNDING_STATUS_KF;
            }

            $title = mb_strlen(trim($list['product_name']))>100 ? mb_substr(trim($list['product_name']),0,100) : trim($list['product_name']);
            $title = addslashes($title);

            $unitSalePrice = $list['product_unit_price'];//销售单价(含成交费)
            $quantity      = $list['product_count'];//购买数量
            $productAmt    = $unitSalePrice * $quantity;//产品金额
            $itemSalePrice = $productAmt;

            if ($index == $listCount) {
                $shipFee                = round($totalShipFee - $tmpshipFee,2);
                $discount               = round($totalDiscount - $tmpDiscount,2);
                $fvf                    = round($totalFVF - $tmpFVF,2);
                $itemSalePriceAllot     = round($subTotalPrice - $totalDiscount - $tmpItemSalePriceAllot, 2);//平摊后的item售价
                $unitSalePriceAllot     = round($itemSalePriceAllot/$quantity, 3);//平摊后的单价
            } else {
                $itemRate              = $productAmt/$totalProductAmt;
                $shipFee               = round( $itemRate * $totalShipFee, 2);//平摊运费
                $discount              = round( $itemRate * $totalDiscount, 2);//平摊优惠金额
                $fvf                   = round( $itemRate * $totalFVF, 2);//平摊成交费
                $itemSalePriceAllot    = round($itemSalePrice - $discount, 2);//平摊后的item售价
                $unitSalePriceAllot    = round($itemSalePriceAllot/$quantity, 3);//平摊后的单价

                $tmpshipFee            += $shipFee;
                $tmpDiscount           += $discount;
                $tmpFVF                += $fvf;
                $tmpItemSalePriceAllot += $itemSalePriceAllot;
            }
            $index++;

            //组装订单明细表数据
            $detailData = array(
                'transaction_id'          => $order['platform_order_id'],
                'order_id'                => $orderId,
                'platform_code'           => $platform,
                'item_id'                 => $aliProductId,
                'title'                   => $title,
                'sku_old'                 => $sku,//系统sku
                'sku'                     => $realProduct['sku'],
                'site'                    => '',
                'quantity_old'            => $quantity,//购买数量
                'quantity'                => $realProduct['quantity'],//实际发货数量
                'sale_price'              => $unitSalePrice,//销售单价(含成交费)
                'total_price'             => round($itemSalePrice+$shipFee,2),//产品金额+平摊后的item运费
                'ship_price'              => $shipFee,//平摊后的运费
                'final_value_fee'         => $fvf,//平摊后的成交费
                'currency'                => trim($list['total_product_currency_code']),
                'pending_status'          => $pending_status,
                'create_time'             => date("Y-m-d H:i:s")
            );

            //海外仓账号匹配仓库id
            if($isOverseasAccount) {
                $aliOverseaRelation = $skuOnline == '' ? null : AliexpressOverseasWarehouse::model()->getInfoByCondition("product_id='{$aliProductId}' and sku='{$skuOnline}'");
                if(!empty($aliOverseaRelation)) {
                    $detailData['warehouse_id'] = $aliOverseaRelation['overseas_warehouse_id'];
                    $warehouseIdArr[] = $detailData['warehouse_id'];
                } else {
                    $warehouseExceptionMsg .= '产品ID:'.$aliProductId.',在线sku：'.$skuOnline.'找不到对应的仓库id';
                }
            }

            //组装订单明细扩展表数据
            $detailExtData = array(
                'item_sale_price'        => $itemSalePrice,//产品金额(含成交费)
                'item_sale_price_allot'  => $itemSalePriceAllot,//平摊后的产品金额(含成交费，减优惠金额)
                'unit_sale_price_allot'  => $unitSalePriceAllot,//平摊后的单价(原销售单价-平摊后的优惠金额)
                'coupon_price_allot'     => $discount,//平摊后的优惠金额
                'tax_fee_allot'          => 0,//平摊后的税费
                'insurance_amount_allot' => 0,//平摊后的运费险
                'fee_amt_allot'          => 0,//平摊后的手续费
            );

            //组装订单sku与销售关系数据
            $orderSkuData = array(
                'sku_online'        => $skuOnline == '' ? 'unknown' : $skuOnline,//在线sku
                'sku_old'           => $sku == '' ? 'unknown' : $sku,//系统sku
                'site'              => '0',
                'item_id'           => $detailData['item_id'],
            );

            $dataArr[$orderId]['data'][]            = $detailData; //订单明细表数据
            $dataArr[$orderId]['detailExtData'][]   = $detailExtData; //订单明细扩展表数据
            $dataArr[$orderId]['part_data'][]       = $orderSkuData; //订单sku与销售关系数据
        }

        //判断是否多仓
        $warehouseIdArr = array_unique($warehouseIdArr);
        $isMultiWarehouse = !empty($warehouseIdArr) && count($warehouseIdArr)>1 ? true : false;

        //返回订单处理
        $orderData = array(
            'subtotal_price'        => $subTotalPrice,//产品总金额
            'final_value_fee'       => $totalFVF,//成交费
            'orderSkuExceptionMsg'  => $orderSkuExceptionMsg,//订单sku异常信息
            'warehouseExceptionMsg' => $warehouseExceptionMsg,//海外仓账号匹配仓库异常
            'isMultiWarehouse'      => $isMultiWarehouse,
        );

        //组装订单扩展表数据
        $orderExtendInfo = array(
            'platform_order_id'    => $order['platform_order_id'],
            'account_id'           => trim($order['account_id']),
            'tax_fee'              => 0,//税费,无
            'coupon_price'         => $totalDiscount,//优惠金额
            'currency'             => $order['currency_code'],//币种
            'payment_type'         => $order['payment_type'],
            'logistics_type'       => implode(',',$logisticsTypeArr),
        ); 

        $dataArr[$orderId]['order_data']        = $orderData;//订单表部分数据
        $dataArr[$orderId]['orderExtendInfo']   = $orderExtendInfo;//订单扩展表数据
        return $dataArr;
    }    

}