<?php
/**
 * @desc Lazada订单明细表
 * @author yangsh
 * @since 2016-10-12
 */
class LazadaOrderDetail extends LazadaModel{

    /** @var string 异常信息*/
    public $exception = null;

    /** @var int 账号ID*/
    protected $_AccountID = null;

    protected $_OrderId = null;
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_lazada_order_detail';
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
     * @desc 账号ID
     * @param [type] $accountID 
     */
    public function setAccountID($accountID){
        $this->_AccountID = $accountID;
        return $this;
    }    

    /**
     * @desc 订单ID
     * @param int $orderId
     */
    public function setOrderId($orderId) {
        $this->_OrderId = $orderId;
        return $this;
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
     * @desc 组装订单明细数据
     * @param  array $details 
     * @return array
     */
    public static function getFormatedOrderDetailInfos($order,$details) {
        //验证订单明细数据是否完整
        $listCount  = count($details);
        if ( $listCount != $order['items_count'] ) {//订单明细记录数
            return false;
        }

        $nowTime           = date("Y-m-d H:i:s");
        $formatDetails     = array();
        $partFormatDetails = array();
        $matchGiftInfos    = array();
        $totalMoney        = floatval($order['price']);//订单总金额=实际交易金额
        $totalShipFee      = floatval($order['shipping_amount']);//总运费
        $subTotalPrice     = 0;//产品总金额          
        $totalFvf          = 0;//成交费
        foreach ($details as $orderDetail) {
            $defaultCount  = 1;//每个item默认购买数量为1
            $skuOnline     = $orderDetail['sku'] != '' ? $orderDetail['sku'] : '';
            $productAmt    = floatval($orderDetail['item_price']) * $defaultCount;//产品金额
            $subTotalPrice += $productAmt;

            /*-----------------------通过栏目和站点查询类目的佣金开始----------------------------------*/
            //通过订单主表和订单明细表拼接productID
            $categoryRate = '';
            $productID = implode('-',array($order['site_id'],$order['old_account_id'],$skuOnline));

            //通过产品表的product_id查询出类目
            $listingInfo = LazadaProduct::model()->getOneByCondition('primary_category',"product_id='{$productID}'");
            if ($listingInfo && $listingInfo['primary_category']) {
                $categoryRate = LazadaCategoryCommissionRate::model()->getCommissionRate($listingInfo['primary_category'],$order['site_id']);
            }

            if(!$categoryRate){
                $categoryRate = $order['commission_rate'];
                if($order['site_id'] == 3 && time()>=strtotime("2017-01-27 00:00:00")){
                    $categoryRate = 0.04;//ID站点27号开始收佣金
                }
            }

            $detailFinalValueFee = round($productAmt * $categoryRate, 2);
            $totalFvf += $detailFinalValueFee;
            /*-----------------------通过栏目和站点查询类目的佣金结束----------------------------------*/
        }
        $totalDiscount = 0;//优惠金额  
        $subTotalPrice = round($subTotalPrice,2);
        $totalFeeAmt   = round($subTotalPrice * $order['paymentfee_rate'],2);//手续费  

        $site                 = LazadaSite::getSiteShortName($order['site_id']);
        /* 是否lazada发货, 如果订单明细的shipping_type值都为'Own Warehouse', 则订单标识已完成、已发货 */
        $isLazadaShipping     = $details[0]['shipping_type'] == 'Own Warehouse' ? true : false;
        $existOwnWareHouse    = false;//标识是否存在lazada发货item
        $itemStatusArr        = array();
        $orderSkuExceptionMsg = '';//订单sku异常信息
        $index                = 1;
        $tmpshipFee           = $tmpDiscount = $tmpFvf = 0;
        $tmpFeeAmt            = $tmpItemSalePriceAllot = 0;
        $logisticsTypeArr     = array();
        foreach ($details as $orderDetail) {
            $defaultCount    = 1;//每个item默认购买数量为1
            $itemStatusArr[] = strtolower($orderDetail['status']);
            $warehouseId     = 41;//光明仓

            if (trim($orderDetail['shipping_type']) == 'Own Warehouse') {
                $existOwnWareHouse = true;
                $isLazadaShipping  &= true;
                if ($order['old_account_id'] == 40) {//SG站点
                    $warehouseId = 71;//F-SG FBL仓
                } else if ( LazadaSite::SITE_ID == $order['site_id'] ) {//ID站点
                    $warehouseId = 77;//LZD 印尼虚拟仓
                } else if ( LazadaSite::SITE_TH == $order['site_id'] ) {//ID站点
                    $warehouseId = 81;//LZD 泰国虚拟仓
                }
            } else {
                $isLazadaShipping &= false;
            }

            //物流方式
            if(!in_array($orderDetail['shipping_type'],$logisticsTypeArr)) {
                $logisticsTypeArr[] = $orderDetail['shipping_type'];
            }

            //1.格式化前数据验证
            $orderLineItemID    = $orderDetail['order_item_id'];      
            $skuOnline          = $orderDetail['sku'] != '' ? $orderDetail['sku'] : '';
            $title              = trim($orderDetail['name']);
            if (mb_strlen($title)>100) {//title超长,截取OMS定义的长度值
                $title          = mb_substr($title,0,100);
            } 

            if ($skuOnline != '' && $skuOnline != 'unknown' ) {
                $sku            = encryptSku::getRealSku ( $skuOnline );
                $skuInfo        = Product::model ()->getProductInfoBySku ( $sku );
            } else {
                $sku            = 'unknown';
                $skuInfo        = array();
            }

            $skuInfo2 = array();//发货sku信息
            $pending_status     = OrderDetail::PEDNDING_STATUS_ABLE;
            if (! empty ( $skuInfo )) { // 可以查到对应产品
                $realProduct    = Product::model ()->getRealSkuListNew ( $skuInfo['sku'], $defaultCount, $skuInfo );
                if ($realProduct['sku'] == $skuInfo['sku']) {
                    $skuInfo2 = $skuInfo;
                } else {
                    $skuInfo2 = Product::model()->getProductInfoBySku($newsku);
                }
            }

            if( empty($skuInfo) || empty($skuInfo2)) {
                $realProduct    = array (
                    'sku'       => 'unknown',
                    'quantity'  => $defaultCount
                );
                $orderSkuExceptionMsg .= 'sku信息不存在;';
                $pending_status = OrderDetail::PEDNDING_STATUS_KF;
            }

            if($skuInfo2 && $skuInfo2['product_is_multi'] == Product::PRODUCT_MULTIPLE_MAIN){
                $childSku = ProductSelectAttribute::model()->getChildSKUListByProductID($skuInfo2['id']);
                if (!empty($childSku)) {//$sku为主sku
                    $orderSkuExceptionMsg .= "sku:{$skuInfo2['sku']}为主sku;";
                    $pending_status = OrderDetail::PEDNDING_STATUS_KF;
                }
            }

            $unitSalePrice = floatval ( $orderDetail['item_price'] );//单价
            $quantity      = $defaultCount;//购买数量
            $productAmt    = $unitSalePrice * $quantity;//产品金额
            $itemSalePrice = $productAmt;

            if ($index == $listCount) {
                $shipFee            = round($totalShipFee - $tmpshipFee,2);//平摊后的运费 
                $discount           = round($totalDiscount - $tmpDiscount,2);//平摊后的优惠金额
                $fvfAmt             = round($totalFvf - $tmpFvf,2);//平摊后的成交费
                $feeAmt             = round($totalFeeAmt - $tmpFeeAmt,2);//平摊后的手续费
                $itemSalePriceAllot = round($subTotalPrice - $totalDiscount - $tmpItemSalePriceAllot, 2);//平摊后的item售价
                $unitSalePriceAllot = round($itemSalePriceAllot/$quantity, 3);//平摊后的单价                
            } else {
                $feeRate            = $itemSalePrice/$subTotalPrice;
                $shipFee            = round($feeRate * $totalShipFee,2);//平摊后的运费 
                $discount           = round($feeRate * $totalDiscount,2);//平摊后的优惠金额
                $fvfAmt             = round($feeRate * $totalFvf,2);//平摊后的成交费
                $feeAmt             = round($feeRate * $totalFeeAmt,2);//平摊后的手续费
                $itemSalePriceAllot = round($itemSalePrice - $discount, 2);//平摊后的item售价
                $unitSalePriceAllot = round($itemSalePriceAllot/$quantity, 3);//平摊后的单价

                $tmpshipFee            += $shipFee;
                $tmpDiscount           += $discount;
                $tmpFvf                += $fvfAmt;
                $tmpFeeAmt             += $feeAmt;
                $tmpItemSalePriceAllot += $itemSalePriceAllot;
            }
            $index++;
            
            //组装OMS需要的数据格式  
            $detailData = array(
                'transaction_id'    => $order['platform_order_id'].'-'.$orderDetail['order_item_id'],
                'item_id'           => $orderDetail['order_item_id'],
                'title'             => $title,
                'sku_old'           => $sku,//系统sku
                'sku'               => $realProduct['sku'],//实际sku
                'site'              => $site,
                'quantity_old'      => $quantity,//购买数量
                'quantity'          => $realProduct['quantity'],//发货数量
                'sale_price'        => $unitSalePrice,//单价
                'total_price'       => round($itemSalePrice + $shipFee,2),//产品金额+平摊后的运费
                'ship_price'        => $shipFee,//平摊后的运费
                'final_value_fee'   => $fvfAmt,//平摊后的成交费
                'currency'          => $orderDetail['currency'],
                'pending_status'    => $pending_status,
                'warehouse_id'      => $warehouseId,
                'create_time'       => date("Y-m-d H:i:s")
            );
            $formatDetails[] = $detailData;

            //组装订单明细扩展表数据
            $detailExtData = array(
                'item_sale_price'        => $itemSalePrice,//产品金额(含成交费)
                'item_sale_price_allot'  => $itemSalePriceAllot,//平摊后的产品金额(含成交费，减优惠金额)
                'unit_sale_price_allot'  => $unitSalePriceAllot,//平摊后的单价(原销售单价-平摊后的优惠金额)
                'coupon_price_allot'     => $discount,//平摊后的优惠金额
                'tax_fee_allot'          => 0,//平摊后的税费,无
                'insurance_amount_allot' => 0,//平摊后的运费险,无
                'fee_amt_allot'          => $feeAmt,//平摊后的手续费
            );
            $formatDetailsExts[] = $detailExtData;            

            //组装订单sku与销售数据
            $orderSkuData = array(
                'sku_online'        => $skuOnline == '' ? 'unknown' : $skuOnline,//在线sku,
                'sku_old'           => $sku,//系统sku
                'site'              => $order['site_id'],
                'item_id'           => implode('-',array($order['site_id'],$order['old_account_id'],$skuOnline)),
            );
            $partFormatDetails[] = $orderSkuData;

            //组装匹配礼品数据
            $giftInfos = array(
                'transaction_id' => $order['platform_order_id'],
                'item_id'        => $orderDetail['order_item_id'],
                'account_id'     => $order['seller_account_id'],
                'sku'            => $skuOnline == '' ? 'unknown' : $skuOnline,//在线sku
                'quantity'       => $quantity,
                'currency'       => $orderDetail['currency'],
            );
            $matchGiftInfos[] = $giftInfos;
        }

        //返回订单使用
        $itemStatusArr = array_unique($itemStatusArr);
        $isPartialCancel = in_array('canceled',$itemStatusArr) && count($itemStatusArr)>1;//订单item是否部分取消
        $orderDatas = array(
            'subtotal_price'       => $subTotalPrice,//产品总金额
            'final_value_fee'      => $totalFvf,//成交费
            'isLazadaShipping'     => $isLazadaShipping,
            'existOwnWareHouse'    => $existOwnWareHouse,
            'isPartialCancel'      => $isPartialCancel,
            'orderSkuExceptionMsg' => $orderSkuExceptionMsg,//订单sku异常信息
        );

        //组装订单扩展表数据
        $orderExtendInfo = array(
            'platform_order_id'    => $order['platform_order_id'],
            'account_id'           => $order['old_account_id'],
            'tax_fee'              => 0,//税费,无
            'coupon_price'         => 0,//优惠金额,无
            'currency'             => $order['currency'],
            'payment_type'         => $order['payment_method'],
            'logistics_type'       => implode(',',$logisticsTypeArr),         
        );

        return array($formatDetails,$partFormatDetails,$matchGiftInfos,
            $orderDatas,$formatDetailsExts,$orderExtendInfo);
    }   
    
}