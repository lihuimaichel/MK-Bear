<?php
/**
 * @desc 货币相关计算
 * @author Gordon
 * @since 2015-08-13
 */
class CurrencyCalculate{
    
    /**@var 销售平台*/
    public $plaformCode = null;
    
    /**@var 交易平台*/
    public $payPlatform = null;
    
    /**@var 卖价*/
    public $salePrice = null;
    
    /**@var 收取运费*/
    public $shippingPrice = null;
    
    /**@var 币种*/
    public $currency = null;
    
    /**@var SKU*/
    public $sku = null;
    
    /**@var 包材SKU*/
    public $skuPackageMaterial = null;
    
    /**@var 包装SKU*/
    public $skuPackageType = null;
    
    /**@var 产品成本*/
    public $productCost = null;
    
    /**@var 运费成本*/
    public $shipCost = null;
    
    /**@var 包材SKU成本*/
    public $skuPackageMaterialCost = null;
    
    /**@var 包装SKU成本*/
    public $skuPackageTypeCost = null;
    
    /**@var 产品重量(g)*/
    public $productWeight = null;
    
    /**@var 包材SKU重量*/
    public $skuPackageMaterialWeight = null;
    
    /**@var 包装SKU重量*/
    public $skuPackageTypeWeight = null;
    
    /**@var 销售平台费用*/
    public $platformCost = null;
    
    /**@var 销售平台费用比例*/
    public $platformRate = null;
    
    /**@var 交易平台费用*/
    public $payPlatformCost = null;
    
    /**@var 交易平台费用比例*/
    public $payplatformRate = null;
    /**@var 交易平台附加费用*/
    public $payplatformAddition = null;
    
    /**@var 利润*/
    public $profit = null;
    
    /**@var 利润率*/
    public $profitRate = null;
    
    /**@var 汇率*/
    public $rate = null;
    
    /**@var 账号*/
    public $accountID = null;
    
    /**@var sku信息*/
    public static $skuInfo = null;
    
    /**@var 报错信息*/
    public $errorMessage = null;
    
    /**@var 站点(只针对ebay)*/
    public $siteID = null;
    
    /**@var 分类名(与平台手续费挂钩)*/
    public $categoryName = null;
    
    /**@var 选择的运输方式code*/
    public $shipCode = null;

    /**@var 调试用code*/
    public $debug = null;    

    /**@var SKU属性*/
    public $attributes = null;
    
    /**
     * 选择运输的国家
     * @var unknown
     */
    public $shipCountry = null;
    
    /**@var 仓库ID*/
    public $warehouseID = null;
    
    /**@var 刊登费*/
    public $publicationFee = 0;
    public $publicationFeeOri = 0;
    
    //====== test =========== //
	public $ebayfeeParams = null;    
	public $ebayPaypalParams = null;
	public $shipParam = null;
	public $profitCalculateFunc = null;
    //===== end test ===========//
    
    /**@var 设置佣金比例*/
    public $commissionRate = null;

    /**@var 订单损耗费比例*/
    public $orderLossRate = null;

    /**@var 联盟佣金比例*/
    public $unionCommission = 0;

    /**
     * @desc 获取利润
     */
    public function getProfit($isNew = false){
        $flag = false;
        if( !$this->profit ){
            if($isNew || (isset($_REQUEST['calnew']) && $_REQUEST['calnew'])){
            	$flag = $this->calculateProfitnew();
            }else{
            	$flag = $this->calculateProfit();
            }
        }
        return !$this->profit ? false : $this->profit;
    }
    
    /**
     * @desc 获取利润率
     */
    public function getProfitRate($isNew = false){
        $flag = true;
        if( !$this->profitRate ){
        	if($isNew || (isset($_REQUEST['calnew']) && $_REQUEST['calnew'])){
        		$flag = $this->calculateProfitnew();
        	}else{
        		$flag = $this->calculateProfit();
        	}
        }
        return !$this->profitRate ? false : $this->profitRate;
    }
    
    /**
     * @desc 获取卖价
     */
    public function getSalePrice($isNew = false){
        $flag = false;
        if( !$this->salePrice ){
        	if($this->plaformCode == Platform::CODE_EBAY && ($isNew  || (isset($_REQUEST['calnew']) && $_REQUEST['calnew']))){
        		$flag = $this->calculateEbaySalePrice();
        	}elseif($this->plaformCode == Platform::CODE_WISH && ($isNew  || (isset($_REQUEST['calnew']) && $_REQUEST['calnew']))){
                $flag = $this->calculateWishSalePrice();
            }else{
        		$flag = $this->calculateSalePrice();
        	}
        }else{
        	$flag = true;
        }
        return $flag===false ? false : $this->salePrice;
    }
    
    /**
     * @desc 获取收取运费
     */
    public function getShippingPrice(){
        return $this->shippingPrice ? $this->shippingPrice : 0;
    }

    /**
     * @desc 获取收取订单损耗费
     */
    public function getOrderLossRate(){
        return $this->orderLossRate ? $this->orderLossRate : 0.03;
    }

    /**
     * @desc 获取联盟佣金
     */
    public function getUnionCommission(){
        return $this->unionCommission ? $this->unionCommission : 0;
    }
    
    /**
     * @desc 获取运输方式
     * @return Ambigous <unknown, string>
     */
    public function getShipCode(){
    	return $this->shipCode;
    }
    
    /**
     * @desc 获取运输国家
     * @return unknown
     */
    public function getShipCountry(){
    	return $this->shipCountry;
    }
    
    /**
     * @desc 获取计算说明
     */
    public function getCalculateDescription(){
        $turnLine = '<br/>';
        $space = '&nbsp;&nbsp;';
        $openTagR = '<font style=color:red;font-weight:bold;>';
        $openTagG = '<font style=color:green;font-weight:bold;>';
        $closeTag = '</font>';
        return Yii::t('common', 'Sale Price').':'.$space.$openTagR . '$' . $space.$this->getSalePrice() . $closeTag.$turnLine
               .Yii::t('common', 'Shipping Price').':'.$space.$openTagR . '￥' . $this->getShippingPrice(). $space . '' . $closeTag.$turnLine
               .Yii::t('common', 'Profit').':'.$space . '￥' . $this->getProfit(true). $space . $turnLine
               .Yii::t('common', 'Profit Rate').':'.$space . '￥' . $this->getProfitRate(true).$turnLine
               .Yii::t('common', 'Currency').':'.$space.$this->currency.$turnLine
               .Yii::t('common', 'Currency Rate').':'.$space.$this->getCurrencyRate().$turnLine
               .Yii::t('common', 'Logistics').':'.$space . Logistics::model()->getShipNameByShipCode($this->shipCode).$turnLine
               .Yii::t('common', 'Shipping Cost').':'.$space.$openTagG . '￥' . $this->getShippingCost(). $space . $closeTag.$turnLine
               .Yii::t('common', 'Product Cost').':'.$space.$openTagG . '￥' . $this->getProductCost(). $space . $closeTag.$turnLine
               .Yii::t('common', 'Platform Cost').':'.$space.$openTagG . '￥' . $this->getPlatformCost().$closeTag.$turnLine
               .Yii::t('common', 'Pay Platform Cost').':'.$space.$openTagG . '￥' . $this->getPayPlatformCost().$closeTag.$turnLine
               .Yii::t('common', 'Pay Platform Cost Rate').':'.$space.$openTagG . '￥' . $this->getPayPlatformCostRate()*$this->getSalePrice().$closeTag.$turnLine
               .Yii::t('common', 'Pay Platform Addition Cost').':'.$space . $openTagG. '$' . $this->getPayPlatformAdditionCost().$closeTag.$turnLine
               .Yii::t('common', 'Product Material Cost').':'.$space.$openTagG . '￥' . $this->getProductPackageMaterialCost().$closeTag.$turnLine
               .Yii::t('common', 'Product Type Cost').':'.$space.$openTagG . '￥' . $this->getProductPackageTypeCost(). $space . $closeTag.$turnLine
        	   .Yii::t('common', 'Ship Code').':'.$space.$openTagG . '￥' . $this->getShipCode(). $space . $closeTag.$turnLine
        	   .Yii::t('common', 'Ship Country').':'.$space.$openTagG . '￥' . $this->getShipCountry(). $space . $closeTag.$turnLine
        	   .Yii::t('common', 'Order Loss Rate').':'.$space.$openTagG . '$' . $this->getOrderLossRate()*$this->getSalePrice(). $space . $closeTag.$turnLine
               .Yii::t('common', 'Union Commission').':'.$space.$openTagG . '$' . $this->getUnionCommission()*$this->getSalePrice(). $space . $closeTag.$turnLine;
    }
    /**
     * @desc 获取ebay计算说明
     */
    public function getEbayCalculateDescription(){
        $turnLine = '<br/>';
        $space = '&nbsp;&nbsp;';
        $openTagR = '<font style=color:red;font-weight:bold;>';
        $openTagG = '<font style=color:green;font-weight:bold;>';
        $closeTag = '</font>';

        $text = '';
        $text .= '卖价:'.$space.$openTagR . '$' . $space.$this->getSalePrice() . $closeTag.$turnLine;
        $text .= '收取运费:'.$space.$openTagR . '$' . $space.$this->shippingPrice . $closeTag.$turnLine;
        $text .= '利润:'.$space. $space.$this->getProfit(true) .$turnLine;
        $text .= '利润率:'.$space. $space.$this->getProfitRate(true) .$turnLine;

        $text .= '货币:'.$space . $space.$this->currency .$turnLine;
        $text .= '汇率:'.$space . $space.$this->getCurrencyRate() .$turnLine;
 
        $text .= '平台成交费率:'.$space.$openTagG . $space.$this->getPlatformCostRate() . $closeTag.$turnLine;
        $text .= 'paypal支付费率:'.$space.$openTagG . $space.$this->payplatformRate . $closeTag.$turnLine;
        $text .= '订单损耗费率:'.$space.$openTagG . $space.$this->getOrderLossRate() . $closeTag.$turnLine;
        $text .= '刊登费:'.$space.$openTagG . '$' . $space.$this->publicationFee/$this->getCurrencyRate(). $closeTag.$turnLine;
        $text .= 'paypal额外附加费:'.$space.$openTagG . '$' . $space.$this->getPayPlatformAdditionCost(). $closeTag.$turnLine;

        $text .= '产品成本:'.$space.$openTagG . '￥' . $space.$this->getProductCost() . $closeTag.$turnLine;
        $text .= '运费支出:'.$space.$openTagG . '￥' . $space.$this->getShippingCost() . $closeTag.$turnLine;
        $text .= '物流方式:'.$space.$openTagG . Logistics::model()->getShipNameByShipCode($this->shipCode) . $closeTag.$turnLine;
        $text .= '包装材料费用:'.$space.$openTagG . '￥' . $space.$this->getProductPackageMaterialCost() . $closeTag.$turnLine;

        return $text;
    }
    
    
    /**
     * @desc 获取产品成本
     */
    public function getProductCost(){
        if( $this->productCost > 0 ){
            return $this->productCost;
        }elseif( $this->sku ){
            $skuInfo = $this->getSkuInfoBySku($this->sku);
            if( !empty($skuInfo) ){
            	if($skuInfo['avg_price'] <= 0)
                	$this->productCost = $skuInfo['product_cost'];
            	else 
            		$this->productCost = $skuInfo['avg_price'];
                return $this->productCost;
            }
        }
        return false;
    }
    
    /**
     * @desc 获取产品重量
     * @return boolean
     */
    public function getProductWeight(){
        if( $this->productWeight > 0 ){
            return $this->productWeight;
        }elseif( $this->sku ){
            $skuInfo = $this->getSkuInfoBySku($this->sku);
            if( !empty($skuInfo) ){
                $this->productWeight = $skuInfo['gross_product_weight'] > 0 ? $skuInfo['gross_product_weight'] : $skuInfo['product_weight'];
                return $this->productWeight;
            }
        }
        return false;
    }
    
    /**
     * @desc 获取产品包材成本
     */
    public function getProductPackageMaterialCost(){
        if( $this->skuPackageMaterialCost===null ){
            $this->setProductPackageMaterial();
        }
        return $this->skuPackageMaterialCost;
    }
    
    /**
     * @desc 获取产品包材重量
     */
    public function getProductPackageMaterialWeight(){
        if( $this->skuPackageMaterialWeight===null ){
            $this->setProductPackageMaterial();
        }
        return $this->skuPackageMaterialWeight;
    }
    
    /**
     * @desc 获取产品包装成本
     */
    public function getProductPackageTypeCost(){
        if( $this->skuPackageTypeCost===null ){
            $this->setProductPackageType();
        }
        return $this->skuPackageTypeCost;
    }
    
    /**
     * @desc 获取产品包装重量
     */
    public function getProductPackageTypeWeight(){
        if( $this->skuPackageTypeWeight===null ){
            $this->setProductPackageType();
        }
        return $this->skuPackageTypeWeight;
    }
    
    /**
     * @desc 获取销售平台花费
     */
    public function getPlatformCost(){
        if( !$this->platformCost ){
            if($this->plaformCode){
               $this->getPlatformFee();
            }else{
                $this->platformCost = 0;
                $this->platformRate = 0;
            }
        }
        return $this->platformCost;
    }
    
    /**
     * @desc 获取销售平台收费比例
     */
    public function getPlatformCostRate($platformCode = null){
        if( !$this->platformRate ){
            if($this->plaformCode){
                $this->getPlatformFee();
            }else{
                $this->platformCost = 0;
                $this->platformRate = 0;
            }
        }
        return $this->platformRate;
    }
    
    /**
     * @desc 获取销售平台费用
     */
    public function getPlatformFee(){
        switch ($this->plaformCode){
            case Platform::CODE_EBAY:
                $fee = $this->getEbayFee(array(
                        'salePrice'     => $this->salePrice ? $this->salePrice : 0,
                        'shippingCost'	=> $this->shippingPrice ? $this->shippingPrice : 0,
                        'categoryName'  => $this->categoryName,
                        'currency'      => $this->currency,
                        'rate'          => $this->getCurrencyRate(),
                        'accountID'		=>	$this->accountID,
                        'siteID'		=>	$this->siteID	
                ));
                $this->platformCost = $fee['fee'];
                $this->platformRate = $fee['rate'];
                break;
            case Platform::CODE_ALIEXPRESS:
            	$fee = $this->getAliexpressFee(array(
            		'salePrice'     => $this->salePrice ? $this->salePrice : 0,
            		'shippingPrice' => $this->shippingPrice ? $this->shippingPrice : 0,
            		'categoryName'  => $this->categoryName,
            		'currency'      => $this->currency,
            		'rate'          => $this->getCurrencyRate(),
            	));
            	$this->platformCost = $fee['fee'];
            	$this->platformRate = $fee['rate'];
            	break;
            case Platform::CODE_LAZADA:
                $fee = $this->getLazadaFee(array(
                        'salePrice'     => $this->salePrice ? $this->salePrice : 0,
                        'shippingPrice' => $this->shippingPrice ? $this->shippingPrice : 0,
                        'categoryName'  => $this->categoryName,
                        'currency'      => $this->currency,
                        'rate'          => $this->getCurrencyRate(),
                ));
                $this->platformCost = $fee['fee'];
                $this->platformRate = $fee['rate'];
                break;
            case Platform::CODE_WISH:
            	$fee = $this->getWishFee(array(
            			'salePrice'     => $this->salePrice ? $this->salePrice : 0,
            			'shippingPrice' => $this->shippingPrice ? $this->shippingPrice : 0,
            			'categoryName'  => $this->categoryName,
            			'currency'      => $this->currency,
            			'rate'          => $this->getCurrencyRate(),
            	));
            	$this->platformCost = $fee['fee'];
            	$this->platformRate = $fee['rate'];
            	break;
            case Platform::CODE_JOOM:
            	$fee = $this->getJoomFee(array(
            			'salePrice'     => $this->salePrice ? $this->salePrice : 0,
            			'shippingPrice' => $this->shippingPrice ? $this->shippingPrice : 0,
            			'categoryName'  => $this->categoryName,
            			'currency'      => $this->currency,
            			'rate'          => $this->getCurrencyRate(),
            	));
            	$this->platformCost = $fee['fee'];
            	$this->platformRate = $fee['rate'];
            	break;
            case Platform::CODE_JD:
            	$fee = $this->getJdFee(array(
            		'salePrice'     => $this->salePrice ? $this->salePrice : 0,
            		'shippingPrice' => $this->shippingPrice ? $this->shippingPrice : 0,
            		'categoryName'  => $this->categoryName,
            		'currency'      => $this->currency,
            		'rate'          => $this->getCurrencyRate(),
            	));
            	$this->platformCost = $fee['fee'];
            	$this->platformRate = $fee['rate'];
            	break;            	
            default:
                $this->platformCost = 0;
                $this->platformRate = 0;
                break;
        }
    }
    
    /**
     * @desc 获取支付平台费用比例
     */
    public function getPayPlatformCostRate(){
        if( $this->payplatformRate===null ){
            if($this->payPlatform){
                $this->getPayPlatformFee();
            }else{
                switch ($this->plaformCode){
                    case Platform::CODE_LAZADA:    
                        $this->payPlatformCost = 0;
                        $this->payplatformRate = 0.02;
                        $this->payplatformAddition = 0;
                        break;
                    case Platform::CODE_WISH:
                    	$this->payPlatformCost = 0;
                    	$this->payplatformRate = 0.01;
                    	$this->payplatformAddition = 0;
                    	break;
                    default:
                        $this->payPlatformCost = 0;
                        $this->payplatformRate = 0;
                        $this->payplatformAddition = 0;
                        break;
                }
            }
        }
        return $this->payplatformRate;
    }
    
    /**
     * @desc 获取支付平台费用
     */
    public function getPayPlatformCost(){
        if( $this->payPlatformCost===null ){
            
            if($this->payPlatform){
      
                $this->getPayPlatformFee();
            }else{
                $this->payPlatformCost = 0;
                $this->payplatformRate = 0;
                $this->payplatformAddition = 0;
            }
        }
        return $this->payPlatformCost;
    }
    
    /**
     * @desc 获取支付平台附加费用
     */
    public function getPayPlatformAdditionCost(){
        if( $this->payplatformAddition===null ){
            if($this->payPlatform){
                $this->getPayPlatformFee();
            }else{
                $this->payPlatformCost = 0;
                $this->payplatformRate = 0;
                $this->payplatformAddition = 0;
            }
        }
        return $this->payplatformAddition;
    }
    
    /**
     * @desc 获取交易平台费用
     */
    public function getPayPlatformFee(){
        switch ($this->payPlatform){
            case 'paypal':
            	$salePrice = $this->salePrice ? $this->salePrice : 0;
            	
            	$total = floatval($salePrice) + floatval($this->shippingPrice);
            	//换算为美元
			    if($this->currency=='USD'){
					$totalUSD = $total;
				}else{
					$totalUSD = $total*$this->getCurrencyRateToOther($this->currency, 'USD');
				}
                $fee = $this->getPaypalPayFee(array(
                        'totalAmount' 	=> $totalUSD,
                        'rate' 			=> $this->getCurrencyRate(),
                        'price' 		=> $total,
                ));
                $this->payPlatformCost = $fee['fee'];
                $this->payplatformRate = $fee['rate'];
                $this->payplatformAddition = $fee['addFee'];
                break;

            case 'wishpay':
                $this->payPlatformCost = 0;//不建议使用此值来获取
                $this->payplatformRate = 0.01;
                $this->payplatformAddition = 0;
                break;
            default:
                $this->payPlatformCost = 0;
                $this->payplatformRate = 0;
                $this->payplatformAddition = 0;
                break;
        }
    }
    
    /**
     * @desc 获取运输成本
     */
    public function getShippingCost(){
        //TODO 计算物流匹配规则
        //运行默认规则
        switch ($this->plaformCode){
            case Platform::CODE_LAZADA:
                $this->shipCost = $this->getLazadaShipCost();
                break;
            case Platform::CODE_ALIEXPRESS:
            	$this->shipCost = $this->getAliexpressShipCost();
            	break;
            case Platform::CODE_WISH:
                // if ($this->debug == 1){                    
                //     $this->shipCost = $this->getWishShipCostNew();
                // }else{
                //     $this->shipCost = $this->getWishShipCost();
                // }
                $this->shipCost = $this->getWishShipCostNew();
            	break;
            case Platform::CODE_JOOM:
            	$this->shipCost = $this->getJoomShipCost();
            	break;
            case Platform::CODE_JD:
            	$this->shipCost = $this->getJdShipCost();
            	break;
            case Platform::CODE_EBAY:
            	$this->shipCost = $this->getEbayShipCost();
            	break;
            default:
                $this->shipCost = $this->getDefaultShipFee();
                break;
        }
        return $this->shipCost;
    }
    
    /**
     * @desc 获取默认规则运费
     */
    public function getDefaultShipFee(){
        $attributes = $this->sku ? Product::model()->getAttributeBySku($this->sku) : array();
        $weight = $this->productWeight + $this->skuPackageMaterialWeight + $this->skuPackageTypeWeight;
        $params = array(
                'country'   => 'United States',
                'attributeid'   => $attributes,
        		'sku'	=>	$this->sku
        );
        !empty($this->warehouseID) && $params['warehouse'] = $this->warehouseID;
        !empty($this->shipCode) && $params['ship_code'] = $this->shipCode;
        return Logistics::model()->getShipFee(Logistics::CODE_CM, $weight, $params);
    }

    /**
     * @desc 获取wish产品默认规则运费
     * @return unknown
     */
    public function getWishShipCost(){
    	$attributes = $this->sku ? Product::model()->getAttributeBySku($this->sku, 'product_features') : array();//属性
    	$weight = $this->productWeight + $this->skuPackageMaterialWeight + $this->skuPackageTypeWeight;//重量
    	//@TODO 获取shipping code
    	//上线后开启该段代码
    	if($this->shipCode){
    		$shipCode = $this->shipCode;
    	}else{
    		if($attributes){
    			//$shipCode = Logistics::CODE_GHXB_WISH;
    			$shipCode = Logistics::CODE_CM_ZX_SYB;
    		}else{
	    		//$shipCode = Logistics::CODE_CM_WISH;
    			$shipCode = Logistics::CODE_CM_GZ_WISH;
    		}
    		$this->shipCode = $shipCode;
    	}
        $wareHouseId = '';
    	$wareHouseId = ($this->warehouseID) ? $this->warehouseID : 41; //深圳仓

    	
    	$shipFee = Logistics::model()->getShipFee($shipCode, $weight, array(
    			'country'   => 'United Kingdom',
    			'attributeid'   => $attributes,
    			'platform_code' => $this->plaformCode,
    			'is_quota'      =>  false,
    			'warehouse'     =>  $wareHouseId,
    			'sku'	=>	$this->sku
    	));
    	if(!$shipFee){
    		$shipFee = Logistics::model()->getShipFee("", $weight, array(
    				'country'   => 'United Kingdom',
    				'attributeid'   => $attributes,
    				'platform_code' => $this->plaformCode,
    				'is_quota'      =>  false,
    				'warehouse'     =>  $wareHouseId,
    				'sku'	=>	$this->sku
    		));
    	}
    	return $shipFee;
    }

    /**
     * @desc 获取wish产品默认规则运费（新）
     * 卖价<10；20>卖价>10；卖价>20取不同物流快递，初始卖价=0）
     * @return unknown
     */
    public function getWishShipCostNew(){
        $shipCode    = '';
        $shipCountry = 'United States'; //默认国家：美国（原来是英国United Kingdom）
        $salePrice   = $this->salePrice ? $this->salePrice : 0;     //卖价
        $attributes  = $this->sku ? Product::model()->getAttributeBySku($this->sku, 'product_features') : array();   //属性
        $this->attributes = $attributes;
        $weight = $this->productWeight + $this->skuPackageMaterialWeight + $this->skuPackageTypeWeight;         //重量        

        if($this->shipCode){
            $shipCode = $this->shipCode;
        }else{
            //带属性
            if($attributes){
                // $shipCode = Logistics::CODE_CM_ZX_SYB;
                if ($salePrice <= 20){
                    $shipCode = Logistics::CODE_CM_ZX_SYB;
                }                
            }else{
                // $shipCode = Logistics::CODE_CM_GZ_WISH;
                if ($salePrice <= 10){
                    $shipCode = Logistics::CODE_CM_GZ_WISH;
                }else{
                    $shipCode = Logistics::CODE_GHXB_GZ_WISH;
                }           
            }
            // if(!empty($shipCode)) $this->shipCode = $shipCode;
        }            

        $wareHouseId = '';
        $wareHouseId = ($this->warehouseID) ? $this->warehouseID : 41; //默认深圳仓
        
        $shipFee = Logistics::model()->getShipFee($shipCode, $weight, array(
                'country'       => $shipCountry,     
                'attributeid'   => $attributes,
                'platform_code' => $this->plaformCode,
                'is_quota'      => false,
                'warehouse'     => $wareHouseId,
                'sku'           => $this->sku
        ));
        // if(!$shipFee){
        //     $shipFee = Logistics::model()->getShipFee("", $weight, array(
        //             // 'country'   => 'United Kingdom',
        //             'country'       => 'United States',
        //             'attributeid'   => $attributes,
        //             'platform_code' => $this->plaformCode,
        //             'is_quota'      => false,
        //             'warehouse'     => $wareHouseId,
        //             'sku'           => $this->sku
        //     ));
        // }

        $shipResult = array();
        if(!$shipFee || empty($shipCode)){
            $param = array(
                    'country'       => $shipCountry,
                    'attributeid'   => $attributes,
                    'platform_code' => $this->plaformCode,
                    'ship_code'     => Logistics::CODE_GHXB,    //物流默认取挂号
                    'is_quota'      => false,
                    'warehouse'     => $wareHouseId,
                    'sku'           => $this->sku
            );
            $shipResult = Logistics::model()->getMinShippingInfo($weight, $param);  //获取最佳物流
            
            if(!empty($shipResult)){
                $shipFee = $shipResult['ship_cost'];
                if(isset($shipResult['ship_code']) && !empty($shipResult['ship_code'])) $shipCode = $shipResult['ship_code']; //获取物流运输方式
            }  

            //如果没有挂号，就只能不限定挂号
            // if (isset($_REQUEST['bug']) && $_REQUEST['bug']){
            if (!$shipFee){
                $param = array(
                        'country'       => $shipCountry,
                        'attributeid'   => $attributes,
                        'platform_code' => $this->plaformCode,
                        'ship_code'     => '',
                        'is_quota'      => false,
                        'warehouse'     => $wareHouseId,
                        'sku'           => $this->sku
                );
                $ret = Logistics::model()->getMinShippingInfo($weight, $param);  //获取最佳物流
                if(!empty($ret)){
                    $shipFee = $ret['ship_cost'];
                    if(isset($ret['ship_code']) && !empty($ret['ship_code'])) $shipCode = $ret['ship_code']; //获取物流运输方式
                } 
            }
            // }                      
        }       

        $this->shipParam = array(
                'sku'             => $this->sku,    
                'salePrice(卖价)'   => $salePrice,
                'shipFee(运费)'     => $shipFee,
                'attributeid(属性)' => ($this->attributes) ? '带属性' : '不带属性',
                'platform_code'   => $this->plaformCode,
                'country'         => 'United States',
                'ship_code'       => $shipCode,                
                'weight(重量)'    => 'productWeight('.$this->productWeight.') + skuPackageMaterialWeight('.$this->skuPackageMaterialWeight.') + skuPackageTypeWeight('.$this->skuPackageTypeWeight.') = '.$weight,
                'warehouse'       => $wareHouseId,                
        );

        return $shipFee;
    }    
    
        /**
     * @desc 获取joom产品默认规则运费
     * @return unknown
     */
    public function getJoomShipCost(){
    	$attributes = $this->sku ? Product::model()->getAttributeBySku($this->sku, 'product_features') : array();//属性
    	$weight = $this->productWeight + $this->skuPackageMaterialWeight + $this->skuPackageTypeWeight;//重量

        $skuInfo = Product::model()->getProductInfoBySku($this->sku);
        if (!$skuInfo) {
            return 0;
        }

        if ($skuInfo['avg_price'] <= 0) {
            $productCost = $skuInfo['product_cost'];   //加权成本
        } else {
            $productCost = $skuInfo['avg_price'];      //产品成本
        }

        //产品成本转换成美金
        $productCost = $productCost / CurrencyRate::model()->getRateToCny('USD');
    	// 获取shipping code

    	if($this->shipCode){
    		$shipCode = $this->shipCode;
    	}else{
            if ($productCost < 5 && !$attributes) {
                $shipCode = 'cm_zp_pcxb';
            } elseif ($productCost < 5 && $attributes) {
                $shipCode = 'cm';
            } elseif ($productCost >= 5 && !$attributes) {
                $shipCode = 'ghxb_zp_gh';
            } elseif ($productCost >= 5 && $attributes) {
                $shipCode = 'ghxb';
            }

    		$this->shipCode = $shipCode;
    	}

    	$shipFee = Logistics::model()->getShipFee($shipCode, $weight, array(
    			//'country'   => 'Russia',
    			'country'   => 'Russian Federation',
    			'attributeid'   => $attributes,
    			'platform_code' => Platform::CODE_JOOM,
                'is_quota'      =>  false,
    			'sku'	=>	$this->sku
    	));
        if ($shipFee) {
            if ($productCost < 5) {
                // 第一次计算成本小于5美金， 加上产品运费 如果大于5美金 就继续计算一次, 可能还是不大准确，原规则是第一次计算的售价
                $newCost = $productCost + $shipFee / CurrencyRate::model()->getRateToCny('USD');
                $newShipCode = '';
                if ($newCost >= 5) {
                    if (!$attributes) {
                        $newShipCode = 'ghxb_zp_gh';
                    } else {
                        $newShipCode = 'ghxb';
                    }
                    if ($newShipCode) {
                        $this->shipCode = $newShipCode;

                        $shipFee = Logistics::model()->getShipFee($newShipCode, $weight, array(
                            //'country'   => 'Russia',
                            'country' => 'Russian Federation',
                            'attributeid' => $attributes,
                            'platform_code' => Platform::CODE_JOOM,
                            'is_quota' => false,
                            'sku' => $this->sku
                        ));
                    }
                }

            }
        }else{
            $param = array(
                'country' => 'Russian Federation',
                'attributeid' => $attributes,
                'platform_code' => Platform::CODE_JOOM,
                'is_quota' => false,
                'sku' => $this->sku
            );
            $shipFee = Logistics::model()->getShipFee('', $weight, $param);
        }
      /*  //如果 $shipFee  为空，取出系统有的运费
        if(!$shipFee){
            $newShipCode = '';
            if($this->sku){
                //获取产品信息
                $skuInfo = Product::model()->getProductInfoBySku($this->sku);
                if($skuInfo){
                    if($skuInfo['avg_price'] <= 0){
                        $productCost = $skuInfo['product_cost'];   //加权成本
                    }else{ 
                        $productCost = $skuInfo['avg_price'];      //产品成本
                    }

                    //产品成本转换成美金
                    $productCost = $productCost / CurrencyRate::model()->getRateToCny('USD');
                    $productCost = round($productCost,2);
                    if($productCost <= 10){
                        $newShipCode = Logistics::CODE_CM;
                    }else{
                        $newShipCode = Logistics::CODE_GHXB;
                    }
                }
            }

            $shipFee = Logistics::model()->getShipFee($newShipCode, $weight, array(
                'country'   => 'Russian Federation',
                'attributeid'   => $attributes,
                'platform_code' => Platform::CODE_JOOM,
                'is_quota'      =>  false,
                'sku'           =>  $this->sku
            ));
        }*/

    	return $shipFee;
    }
    
    /**
     * @desc 获取ebay的运费
     * @return unknown
     */
    public function getEbayShipCost(){
    	$attributes = $this->sku ? Product::model()->getAttributeBySku($this->sku, 'product_features') : array();
        //属性
    	$weight = $this->productWeight + $this->skuPackageMaterialWeight + $this->skuPackageTypeWeight;//重量
    	/* $shipConfig = array(
    			'USD' => 'United States',
    			'AUD' => 'Australia',
    			'GBP' => 'United Kingdom',
    			'CAD' => 'Canada',
    			'EUR' => 'Germany',
    	); */
    	/**
    	 US 站点：美国 物流：广州邮局小包
    	 UK 站点：英国 物流：广州邮局小包
    	 AU 站点：澳大利亚 物流：广州邮局小包
    	 CA站点：加拿大 物流：广州邮局小包
    	 ES站点：西班牙 物流：东莞邮政小包
    	 DE 站点：德国  物流：A2B香港DHL德国专线
    	 FR站点：法国  物流：东莞邮政小包
    	 */
    	$siteName = EbaySite::getSiteName($this->siteID);
    	$shipCountryConfig = array(
    							'US'		=>	'United States',
				    			'Canada'	=>	'Canada',
				    			'UK'		=>	'United Kingdom',
				    			'Australia'	=>	'Australia',
				    			'Germany'	=>	'Germany',
				    			'France'	=>	'France',
				    			'Spain'		=>	'Spain',
    						);
    	//普通属性物流方式
    	$shipCodeConfig = array(
    			'US'		=>	Logistics::CODE_XN_YZ,
    			'Canada'	=>	Logistics::CODE_XN_YZ,
    			'UK'		=>	Logistics::CODE_XN_YZ,
    			'Australia'	=>	Logistics::CODE_XN_YZ,
    			'France'	=>	Logistics::CODE_XN_YZ,
    			'Germany'	=>	Logistics::CODE_XN_DE_YZ,
    			'Spain'		=>	Logistics::CODE_WYT_XGXB,
    	);
    	//特殊属性物流方式
    	$shipCodeConfigSpecial = array(
    			'US'		=>	Logistics::CODE_XN_YZ_DD,
    			'Canada'	=>	Logistics::CODE_XN_YZ_DD,
    			'UK'		=>	Logistics::CODE_XN_YZ_DD,
    			'Australia'	=>	Logistics::CODE_XN_YZ_DD,
    			'France'	=>	Logistics::CODE_XN_YZ_DD,
    			'Germany'	=>	Logistics::CODE_XN_DE_YZ_DD,
    			'Spain'		=>	Logistics::CODE_WYT_XGXB,
    	);
    	$shipCountry = 'United States';
    	$shipCode = Logistics::CODE_XN_YZ;

    	if(isset($shipCountryConfig[$siteName])){
    		$shipCountry = $shipCountryConfig[$siteName];
    	}
    	if(isset($shipCodeConfig[$siteName])){
    		$shipCode = $shipCodeConfig[$siteName];
    	}
    	
    	// ==================
    	if(!empty($attributes) && isset($shipCodeConfigSpecial[$siteName])){
    		$shipCode = $shipCodeConfigSpecial[$siteName];
    	}
    	
    	// ==================
    	
    	//根据账号获取
    	if($this->shipCode){
    		$shipCode = $this->shipCode;
    	}
    	/* else{
    		$accountInfo = EbayAccount::model()->findByPk($this->accountID);
    		if(empty($accountInfo)) return false;
    		$USDcost = floatval($this->productCost*$this->getCurrencyRateToOther("CNY", "USD"));
    		if($accountInfo['is_eub_under5'] && $USDcost > 5){
    			$shipCode = Logistics::CODE_EUB;
    		}
    	} */

    	$this->shipCode = $shipCode;
    	$this->shipCountry = $shipCountry;
    	
    	/* if(!empty($attributes)){
    		$shipCode = "";
    	} */
    	//@todo 改为数据库获取
    	$ebayAccountSite = new EbayAccountSite();
    	$wareHouseId = $ebayAccountSite->getWarehouseByAccountSite($this->accountID, $this->siteID);
    	if(empty($wareHouseId))
    		$wareHouseId = 41; //深圳仓
    	
        $param = array(
                'country'       => $shipCountry,
                'attributeid'   => $attributes,
                'platform_code' => $this->plaformCode,
                'is_quota'      =>  false,
                'warehouse'     =>  $wareHouseId,
        		'include_disable'=> true,
        		'sku'	=>	$this->sku
        );
    	$shipFee = Logistics::model()->getShipFee($shipCode, $weight, $param);

        //MHelper::writefilelog('shipfee3.txt', print_r(array('sku'=>$this->sku,'param'=>$param,'result'=>$shipFee),true)."\r\n");

    	$shipFee1 = $shipFee;
    	$shipResult = array();
    	if(!$shipFee){
            $param = array(
                    'country'       => $shipCountry,
                    'attributeid'   => $attributes,
                    'platform_code' => $this->plaformCode,
                    'ship_code'     =>  '',
                    'is_quota'      =>  false,
                    'warehouse'     =>  $wareHouseId,
            		'sku'	=>	$this->sku
            );
    		$shipResult = Logistics::model()->getMinShippingInfo($weight, $param);
            //MHelper::writefilelog('shipfee3.txt', print_r(array('sku'=>$this->sku,'param2'=>$param,'result2'=>$shipResult),true)."\r\n");

    		if(!empty($shipResult)){
    			$shipFee = $shipResult['ship_cost'];
    			$this->shipCode = $shipResult['ship_code'];
    		}
    	}
    	//@todo ==== test ====
    	$this->shipParam = array(
    			'country'   	=> $shipCountry,
    			'attributeid'   => $attributes,
    			'platform_code' => $this->plaformCode,
    			'ship_code'		=>	$this->shipCode,
    			'shipFee'		=>	$shipFee ,
    			'shipFee1'		=>	$shipFee1,
    			'shipResult'	=>	$shipResult,
    			'$weight'		=>	$weight,
    			'warehouse'		=>	$wareHouseId,
    			'sitename'		=>	$siteName
    	);
    	// ====== end ======
    	return $shipFee;
    }
    /**
     * @desc 获取京东产品默认规则运费
     * @return unknown
     */
    public function getJdShipCost(){
    	$attributes = $this->sku ? Product::model()->getAttributeBySku($this->sku, 'product_features') : array();//属性
    	$weight = $this->productWeight + $this->skuPackageMaterialWeight + $this->skuPackageTypeWeight;//重量
    	//重量提高10g
    	$weight += 10;
    	if (!empty($attributes)) {
    		$shipCode = Logistics::CODE_GHXB_SF;	//特殊属性的运输方式
    	} else {
    		$shipCode = Logistics::CODE_GHXB_CN;	//非特殊属性的运输方式
    	}
    	if ($weight > 2000)
    		$shipCode = Logistics::CODE_FEDEX_IE_HK;
    	$this->shipCode = $shipCode;
    	$shipFee = Logistics::model()->getShipFee($shipCode, $weight, array(
    			'country'   => 'Russian Federation',
    			'attributeid'   => $attributes,
    			'sku'	=>	$this->sku
    	));
    	return $shipFee;
    }    
    
    /**
     * @desc 获取默认规则运费
     */
    public function getAliexpressShipCost(){
    	$attributes = $this->sku ? Product::model()->getAttributeBySku($this->sku, 'product_features') : array();//属性
    	$weight = $this->productWeight + $this->skuPackageMaterialWeight + $this->skuPackageTypeWeight;//重量
        $wareHouseId = 41;
        $shipCountry = 'Russian Federation';
        $shipResult = '';
    	//@TODO 获取shipping code
    	$shipCode = '';
        if($this->shipCode){
           $shipCode = $this->shipCode; 
        }
    	$oriShipCode = $shipCode;   	
        $shipFee = Logistics::model()->getShipFee($shipCode, $weight, array(
    		'country'   => $shipCountry,
    		'attributeid'   => $attributes,
            'platform_code' => $this->plaformCode,
            'is_quota'      =>  false,
            'warehouse'     =>  $wareHouseId,
        	'sku'	=>	$this->sku
    	));
        $shipFee1 = $shipFee;
        //如果为0，重新获取系统默认
        if(!$shipFee){
            $shipCode = '';
            $param = array(
                    'country'       => $shipCountry,
                    'attributeid'   => $attributes,
                    'platform_code' => $this->plaformCode,
                    'ship_code'     =>  $shipCode,
                    'is_quota'      =>  false,
                    'warehouse'     =>  $wareHouseId,
            		'sku'	=>	$this->sku
            );
            $shipResult = Logistics::model()->getMinShippingInfo($weight, $param);
            
            if(!empty($shipResult)){
                $shipFee = $shipResult['ship_cost'];
                $this->shipCode = $shipResult['ship_code'];
            }
        }

        $this->shipParam = array(
                'country'       => $shipCountry,
                'attributeid'   => $attributes,
                'platform_code' => $this->plaformCode,
                'ship_code'     =>  $this->shipCode,
                'shipFee'       =>  $shipFee ,
                'shipResult'    =>  $shipResult,
                '$weight'       =>  $weight,
                'warehouse'     =>  $wareHouseId,
                'oriShipCode'   =>  $oriShipCode,
                'shipFee1'      =>  $shipFee1
        );
    	return $shipFee;
    }   

    
    
    
    /**
     * @desc 获取默认规则运费
     */
    public function getLazadaShipCost(){
        $attributes = $this->sku ? Product::model()->getAttributeBySku($this->sku, 'product_features') : array();//属性
        $weight = $this->productWeight + $this->skuPackageMaterialWeight + $this->skuPackageTypeWeight;//重量
        $shipCode = Logistics::CODE_MY_LGS;
        $country = '';

        //普通属性物流方式
        $shipCodeConfig = array(
            'my' => Logistics::CODE_MY_LGS,
            'sg' => Logistics::CODE_SG_LGS,
            'id' => Logistics::CODE_ID_LGS,
            'th' => Logistics::CODE_TH_LGS,
            'ph' => Logistics::CODE_PH_LGS,
            'vn' => Logistics::CODE_VN_LGS,
        );

        $siteName = LazadaSite::getLazadaSiteShortName($this->siteID);
        if(isset($shipCodeConfig[$siteName])){
            $shipCode = $shipCodeConfig[$siteName];
        }else{
            if($attributes){
                $shipCode = Logistics::CODE_GHXB_SG;
            }else{
                $shipCode = Logistics::CODE_GHXB_DGYZ;
            }
        }

        //获取国家名称
        $country = LazadaSite::getCountryName($this->siteID);

        //超过2KG的用EMS算价
        // if ($weight > 10000) {
        // 	$shipFee = Logistics::model()->getShipFee(Logistics::CODE_EMS, $weight, array(
        // 			'country'   => 'Malaysia',
        // 			'attributeid'   => $attributes,
        // 	));
        // 	return $shipFee;
        // }
        
        
        $shipFee = Logistics::model()->getShipFee($shipCode, $weight, array(
            "platform_code"=> Platform::CODE_LAZADA,
            'country'   => $country,
            'attributeid'   => $attributes,
            //'warehouse' => WarehouseSkuMap::WARE_HOUSE_DEF
        	'sku'	=>	$this->sku
        ));
        if($shipFee <= 0){
            $shipFee = Logistics::model()->getShipFee($shipCode, $weight, array(
                "platform_code"=> Platform::CODE_LAZADA,
                'country'   => $country,
                'attributeid'   => $attributes,
                'warehouse' => WarehouseSkuMap::WARE_HOUSE_GM,
            	'sku'	=>	$this->sku
            ));
        }
        if( $shipFee > 0 ){
            $this->shipCode = $shipCode;
        }
        return $shipFee;
    }
    
    /**
     * @desc 获取转化为人民币的汇率
     */
    public function getCurrencyRate(){
        if(!$this->rate){
            $this->rate = CurrencyRate::model()->getRateToCny($this->currency);
        }
        return $this->rate;
    }
    /**
     * @desc 获取指定汇率
     * @param unknown $currentCurrency
     * @param unknown $targetCurrency
     */
    public function getCurrencyRateToOther($currentCurrency, $targetCurrency){
    	return CurrencyRate::model()->getRateToOther($currentCurrency, $targetCurrency);
    }
    /**
     * @desc 获取报错信息
     */
    public function getErrorMessage(){
        return $this->errorMessage;
    }

    /**
     * @desc 获取信息
     */
    public function getShipParam(){
        return $this->shipParam;
    }    
    
    /**
     *
     * @desc 计算公式: (卖价+运费-销售平台相关费用-支付平台相关费用-商品成本-运费成本-邮包成本)
     * 				
     *
     */
    private function calculateProfit(){
    	$salePrice = $this->salePrice;//卖价
    	if( !$salePrice ){
    		$this->setErrorMessage(Yii::t('system', 'Sale Price Is Required'));
    		return false;
    	}
    	$shippingPrice = $this->shippingPrice ? $this->shippingPrice : 0;//收取运费
    	$productCost = $this->getProductCost();//产品成本
    	if( !$productCost ){
    		$this->setErrorMessage(Yii::t('system', 'Product Cost OR SKU Is Required'));
    		return false;
    	}
    	$productPackageMaterialCost = $this->getProductPackageMaterialCost();//包材费用
    	$productPackageMaterialCost = $productPackageMaterialCost ? $productPackageMaterialCost : 0;
    	$productPackageTypeCost = $this->getProductPackageTypeCost();//包装费用
    	$productPackageTypeCost = $productPackageTypeCost ? $productPackageTypeCost : 0;
    	$shippingCost = $this->getShippingCost();
    	if( !$shippingCost ){
    		//lihy modify 2016-05-04
    		$shippingCost = 0;
    		 
    		$this->setErrorMessage(Yii::t('system', 'Can Not Get Shipping Cost'));
    		return false;
    	}
    	$rateToCNY = $this->getCurrencyRate();
    	//$this->platformCost = $platformCost = $this->getPlatformCost();//销售平台费用
    	$platformRate = $this->getPlatformCostRate();
    	$this->platformCost = $platformCost = $platformRate*$salePrice*$rateToCNY;
    	$payPlatformCost = $this->getPayPlatformCost();//支付平台手续费
    	$payPlatformRate = $this->payplatformRate;//支付平台手续费
    	$USDRateToCNY = $this->getCurrencyRateToOther("USD", "CNY");//美员兑人民币
    	$payplatformAddition = 0;
    	//$payplatformAddition = $this->getPayPlatformAdditionCost();
    	
    	$orderLossPrice = 0;//损耗费
    	if($this->getOrderLossRate()){
    		$orderLossPrice = $this->getOrderLossRate()*$salePrice*$rateToCNY;
    	}

        $unionCommissionPrice = 0;//联盟佣金费
        if($this->getUnionCommission()){
            $unionCommissionPrice = $this->getUnionCommission()*$salePrice*$rateToCNY;
        }

    	//计算利润
    	/* $this->profit = round(($salePrice*$rateToCNY + $shippingPrice*$rateToCNY - $payplatformAddition*$rateToCNY - $platformCost - $payPlatformCost - $productCost - $productPackageMaterialCost - $productPackageTypeCost - $shippingCost), 2);
    	 $this->profitRate = round($this->profit / (($salePrice + $shippingPrice)*$rateToCNY), 3);
    	$this->profitCalculateFunc = "profit：round(({$salePrice}*{$rateToCNY} + {$shippingPrice}*{$rateToCNY} - {$payplatformAddition}*{$rateToCNY} - {$platformCost} - {$payPlatformCost} - {$productCost} - {$productPackageMaterialCost} - {$productPackageTypeCost} - {$shippingCost}), 2)";
    	$this->profitCalculateFunc .= "<br/>profit_text：round((\$salePrice*\$rateToCNY + \$shippingPrice*\$rateToCNY - \$payplatformAddition*\$rateToCNY - \$platformCost - \$payPlatformCost - \$productCost - \$productPackageMaterialCost - \$productPackageTypeCost - \$shippingCost), 2)";
    	$this->profitCalculateFunc .= "<br/>profitRate: round({$this->profit} / (({$salePrice} + {$shippingPrice})*{$rateToCNY}), 3)";
    	$this->profitCalculateFunc .= "<br/>profitRate_text: round(\$this->profit / ((\$salePrice + \$shippingPrice)*\$rateToCNY), 3)"; */
    	$publicationFee = $this->publicationFee;
    	//换一种方式计算
    	$this->profitRate = round(($salePrice+$shippingPrice - $payplatformAddition - ($publicationFee + $platformCost + $payPlatformCost + $productCost + $productPackageMaterialCost + $shippingCost + $orderLossPrice + $unionCommissionPrice)/$rateToCNY)/$salePrice, 4);
    	$this->profit = round($salePrice*$this->profitRate*$rateToCNY, 4);

    	$this->profitCalculateFunc = "<br/>profitRate: round(({$salePrice}+{$shippingPrice} - {$payplatformAddition} - ({$publicationFee} + {$platformCost} + {$payPlatformCost} + {$productCost} + {$productPackageMaterialCost} + {$shippingCost} + {$orderLossPrice} + {$unionCommissionPrice})/{$rateToCNY})/{$salePrice}, 4)";
    	$this->profitCalculateFunc .= "<br/>profitRate_text: round((\$salePrice+\$shippingPrice - \$payplatformAddition - (\$publicationFee + \$platformCost + \$payPlatformCost + \$productCost + \$productPackageMaterialCost + \$shippingCost+ \$orderLossPrice + \$unionCommissionPrice)/\$rateToCNY)/\$salePrice, 4)";
    	$this->profitCalculateFunc .= "<br/>profit：round({$salePrice}*{$this->profitRate}*{$rateToCNY}, 4)";
    	$this->profitCalculateFunc .= "<br/>profit_text：round(\$salePrice*\$this->profitRate*\$rateToCNY, 4)";
    	$this->profitCalculateFunc .= "<br/>profit_text：\$platformRate:{$platformRate}, \$rateToCNY:$rateToCNY, \$payPlatformCost:$payPlatformCost, \$payPlatformRate:$payPlatformRate";
    	return true;
    }
    
    /**
     * 
     * @desc 计算公式: (卖价+运费-销售平台相关费用-支付平台相关费用-商品成本-运费成本-邮包成本) 
     * 				销售价格 = (成本+物流运费+刊登费+支付手续附加费) / (1-利润率-佣金率-支付手续费率-订单损耗率)
     * 				(1-利润率-佣金率-支付手续费率-订单损耗率) = (成本+物流运费+刊登费+支付手续附加费) / 销售价格
     * 				利润率 = 1-佣金率-支付手续费率-订单损耗率- (成本+物流运费+刊登费+支付手续附加费) / 销售价格
     * @date 2017-01-03
     * 
     * 
     */
    private function calculateProfitnew(){
        $salePrice = $this->salePrice;//卖价
        if( !$salePrice ){ 
            $this->setErrorMessage(Yii::t('system', 'Sale Price Is Required'));
            return false;
        }
        $shippingPrice = $this->shippingPrice ? $this->shippingPrice : 0;//收取运费
        $productCost = $this->getProductCost();//产品成本
        if( !$productCost ){
            $this->setErrorMessage(Yii::t('system', 'Product Cost OR SKU Is Required'));
            return false;
        }
        $productPackageMaterialCost = $this->getProductPackageMaterialCost();//包材费用
        $productPackageMaterialCost = $productPackageMaterialCost ? $productPackageMaterialCost : 0;
        $productPackageTypeCost = $this->getProductPackageTypeCost();//包装费用
        $productPackageTypeCost = $productPackageTypeCost ? $productPackageTypeCost : 0;
        $shippingCost = $this->getShippingCost();
        if( !$shippingCost ){
        	//lihy modify 2016-05-04
        	$shippingCost = 0;
        	
            $this->setErrorMessage(Yii::t('system', 'Can Not Get Shipping Cost'));
            return false;
        }
        $realSalePrice = ($salePrice+$shippingPrice);
        $rateToCNY = $this->getCurrencyRate();
        //$this->platformCost = $platformCost = $this->getPlatformCost();//销售平台费用
        $platformRate = $this->getPlatformCostRate();

       	$this->platformCost = $platformCost = $platformRate*$realSalePrice*$rateToCNY;
        $payPlatformCost = $this->getPayPlatformCost();//支付平台手续费
        $payPlatformRate = $this->payplatformRate;//支付平台手续费

        $payPlatformCost = $realSalePrice*$rateToCNY*$payPlatformRate;

        $payplatformAddition = 0;
        $payplatformAddition = $this->getPayPlatformAdditionCost();
        $USDRateToCNY = $this->getCurrencyRateToOther("USD", "CNY");//美员兑人民币
        $payplatformAdditionCNY = $payplatformAddition * $rateToCNY;
        $orderLossPrice = 0;//损耗费
        $orderLossRate = $this->getOrderLossRate();
        if($orderLossRate){
            $orderLossPrice = $orderLossRate*$realSalePrice*$rateToCNY;
        }
        //联盟佣金比例
        $unionCommissionRate = $this->getUnionCommission();
    
        $publicationFee = $this->publicationFee;
        //利润率 = 1-佣金率-支付手续费率-订单损耗率-联盟佣金率 - (成本+物流运费+刊登费+支付手续附加费) / 销售价格 2017-01-03
        
        $profitRate = 1-$platformRate-$payPlatformRate-$orderLossRate-$unionCommissionRate-($productCost+$productPackageMaterialCost + $shippingCost + $publicationFee + $payplatformAdditionCNY)/($realSalePrice*$rateToCNY);
        
        $this->profitRate = round($profitRate, 4);
        $this->profit = round($realSalePrice*$this->profitRate*$rateToCNY, 4);
        $this->profitCalculateFunc = "time:" . date("Y-m-d H:i:s") . "<br/>";
        $this->profitCalculateFunc .= "<br/>profitRate: 1-{$platformRate}-{$payPlatformRate}-{$orderLossRate}-({$productCost}+{$productPackageMaterialCost} + {$shippingCost} + {$publicationFee} + {$payplatformAdditionCNY})/({$realSalePrice}*{$rateToCNY})";
        $this->profitCalculateFunc .= "<br/>profitRate_text: 1-\$platformRate-\$payPlatformRate-\$orderLossRate-(\$productCost+\$productPackageMaterialCost + \$shippingCost + \$publicationFee + \$payplatformAdditionCNY)/(\$realSalePrice*\$rateToCNY)";
        $this->profitCalculateFunc .= "<br/>profit：round({$realSalePrice}*{$this->profitRate}*{$rateToCNY}, 4)";
        $this->profitCalculateFunc .= "<br/>profit_text：round(\$realSalePrice*\$this->profitRate*\$rateToCNY, 4)";
        $this->profitCalculateFunc .= "<br/>profit_text：\$platformRate:{$platformRate}, \$rateToCNY:$rateToCNY, \$payPlatformCost:$payPlatformCost, \$payPlatformRate:$payPlatformRate";
        return true;
    }
    
    /**
     * @desc 计算卖价
     * 销量利润率 = (销售价-固定成本-销售价*(销售平台手续费比例+支付平台手续费比例))/销售价
     * ----> 销售价 = 固定成本/((1-(销售平台手续费比例+支付平台手续费比例))-利润率))
     * 固定成本 = 产品成本 + 运费成本 + 包装成本 + 包材成本
     */
    private function calculateSalePrice(){
    	$profitRate = $this->profitRate;//利润率
    	if( !$profitRate ){
    		$this->setErrorMessage(Yii::t('system', 'Profit Rate Is Required'));
    		return false;
    	}
    	$shippingPrice = $this->shippingPrice ? $this->shippingPrice : 0;//收取运费
    	$productCost = $this->getProductCost();//产品成本
    	if( !$productCost ){
    		$this->setErrorMessage(Yii::t('system', 'Product Cost OR SKU Is Required'));
    		return false;
    	}
    	$productPackageMaterialCost = $this->getProductPackageMaterialCost();//包材费用
    	$productPackageMaterialCost = $productPackageMaterialCost ? $productPackageMaterialCost : 0;
    	$productPackageTypeCost = $this->getProductPackageTypeCost();//包装费用
    	$productPackageTypeCost = $productPackageTypeCost ? $productPackageTypeCost : 0;
    	$shippingCost = $this->getShippingCost();
    	if( !$shippingCost ){
    		$this->setErrorMessage(Yii::t('system', 'Can Not Get Shipping Cost'));
    		return false;
    	}
    	$payPlatformCostRate = 0;
    	$platformRate = $this->getPlatformCostRate();//销售平台费用比例
    	$rateToCNY = $this->getCurrencyRate();
        $orderLossRate = 0;

    	//粗略支付比例
    	$salePrice = ($productCost + $shippingCost + $productPackageMaterialCost + $productPackageTypeCost) / (1 - $platformRate - $payPlatformCostRate - $profitRate - $orderLossRate) / $rateToCNY;
    	$this->salePrice = ceil($salePrice * 100) /100;

    	//重新计算
    	$platformRate = $this->getPlatformCostRate();//销售平台费用比例
    	$payPlatformCostRate = $this->getPayPlatformCostRate();//支付平台手续费比例
        $orderLossRate = $this->getOrderLossRate();//订单损耗费比例
        $unionCommission = $this->getUnionCommission();//联盟佣金比例
        //支付附加费
        $payplatformAddition = 0;
        //$payplatformAddition = $this->getPayPlatformAdditionCost();
        $USDRateToCNY = $this->getCurrencyRateToOther("USD", "CNY");//美员兑人民币
        
    	$publicationFee = $this->publicationFee;
    	$salePriceCNY = ($publicationFee + $payplatformAddition + $productCost + $shippingCost + $productPackageMaterialCost + $productPackageTypeCost) / (1 - $platformRate - $payPlatformCostRate - $profitRate - $orderLossRate - $unionCommission);
    	
    	$salePrice = $salePriceCNY / $rateToCNY;
        
    	$this->salePrice = ceil($salePrice * 100) /100;
    	$this->profit = round($salePriceCNY*$this->profitRate, 4);
    	$textField = array(
    			'publicationFee'=>$publicationFee,
    			'payplatformAddition'=>$payplatformAddition*$rateToCNY,
    			'productCost'=>$productCost,
    			'shippingCost'=>$shippingCost,
    			'productPackageMaterialCost'=>$productPackageMaterialCost,
    			'productPackageTypeCost'=>$productPackageTypeCost,
    			'$platformRate'	=>	$platformRate,
    			'$payPlatformCostRate'	=>	$payPlatformCostRate,
    			'$profitRate'			=>	$profitRate,
    			'$rateToCNY'			=>	$rateToCNY
    	);
    	$this->profitCalculateFunc = $textField;
    	return true;
    }
    

    /**
     * @desc 销售价格1 = (成本+物流运费+刊登费+0.05) / (1-利润率-佣金率-0.06-订单损耗率)   [销售价格<10]
    		  销售价格2 = (成本+物流运费+刊登费+0.3) / (1-利润率-佣金率-0.029-订单损耗率)   [销售价格>=10]
     * @return boolean
     */
    private function calculateEbaySalePrice(){
    	try{
    		$profitRate = $this->profitRate;//利润率
    		$publicationFee = 0;
    		$payplatformAddition = 0.00;
    		$payPlatformCostRate = 0.00;
    		$orderLossRate = 0;
    		$payPlatformCost = 0;
    		$productCost = 0;
    		$shippingCost = 0;
    		$productPackageMaterialCost = 0;
    		$productPackageTypeCost = 0;
    		$platformRate = 0;
    		$rateToCNY = $this->getCurrencyRate();
    		$salePriceCNY = 0;
    		$flag = 0;
    		$USDRateToCNY = $this->getCurrencyRateToOther("USD", "CNY");//美员兑人民币
    		
    		
    		if( !$profitRate ){
    			throw new Exception(Yii::t('system', 'Profit Rate Is Required'));
    		}
    		$shippingPrice = $this->shippingPrice ? $this->shippingPrice : 0;//收取运费
    		$productCost = $this->getProductCost();//产品成本
    		if( !$productCost ){
    			throw new Exception(Yii::t('system', 'Product Cost OR SKU Is Required'));
    		}
    		$productPackageMaterialCost = $this->getProductPackageMaterialCost();//包材费用
    		$productPackageMaterialCost = $productPackageMaterialCost ? $productPackageMaterialCost : 0;
    		$productPackageTypeCost = $this->getProductPackageTypeCost();//包装费用
    		$productPackageTypeCost = $productPackageTypeCost ? $productPackageTypeCost : 0;
    		$shippingCost = $this->getShippingCost();
    		if( !$shippingCost ){
    			throw new Exception(Yii::t('system', 'Can Not Get Shipping Cost'));
    		}
    		//重新计算
    		$platformRate = $this->getPlatformCostRate();//销售平台费用比例
        		
    		
    		$orderLossRate = $this->getOrderLossRate();//订单损耗费比例
    		$publicationFee = $this->publicationFee;
    		
    		 
    		$payplatformAddition = 0.05;
    		$payPlatformCostRate = 0.06;
    		 
    		//新的计算方式
    		//1
    		$salePriceCNY = ($publicationFee + $payplatformAddition*$rateToCNY + $productCost + $shippingCost + $productPackageMaterialCost + $productPackageTypeCost) / (1 - $platformRate - $payPlatformCostRate - $profitRate - $orderLossRate);
    		$salePrice = $salePriceCNY / $rateToCNY;
    		$this->salePrice = ceil($salePrice * 100) /100;
    		$flag = "1";
    		//如果大于等于10美金则重新计算
    		if($salePriceCNY/$USDRateToCNY >= 10){
    			//重新计算
    			$platformRate = $this->getPlatformCostRate();//销售平台费用比例
    			//2
    			$flag = "2";
    			$payplatformAddition = 0.3;
    			$payPlatformCostRate = 0.029;
    			$salePriceCNY = ($publicationFee + $payplatformAddition*$rateToCNY + $productCost + $shippingCost + $productPackageMaterialCost + $productPackageTypeCost) / (1 - $platformRate - $payPlatformCostRate - $profitRate - $orderLossRate);
    			$salePrice = $salePriceCNY / $rateToCNY;
    			$this->salePrice = ceil($salePrice * 100) /100;
    		}
    		 
    		
    		 
    		$this->profit = round($salePriceCNY*$this->profitRate, 4);
    		$message = 'success';
    		$returnFlag = true;
    	}catch (Exception $e){
    		$this->setErrorMessage($e->getMessage());
    		$returnFlag = false;
    		$message = $e->getMessage();
    	}
    	$textField = array(
    			'publicationFee'=>$publicationFee,
    			'publicationFeeOri'=>$this->publicationFeeOri,
    			'payplatformAddition'=>$payplatformAddition*$rateToCNY,
    			'productCost'=>$productCost,
    			'shippingCost'=>$shippingCost,
    			'productPackageMaterialCost'=>$productPackageMaterialCost,
    			'productPackageTypeCost'=>$productPackageTypeCost,
    			'$platformRate'	=>	$platformRate,
    			'$platformCost'	=>	$salePriceCNY*$platformRate,
    			'$payPlatformCostRate'	=>	$payPlatformCostRate,
    			'$payPlatformCost'		=>	$salePriceCNY*$payPlatformCostRate,
    			'$profitRate'			=>	$profitRate,
    			'$rateToCNY'			=>	$rateToCNY,
    			'$orderLossRate'		=>	$orderLossRate,
    			'$orderLoss'			=>	$salePriceCNY*$orderLossRate,
    			'method'				=>	'calculateEbaySalePrice',
    			'USDRateToCNY'			=>	$USDRateToCNY,
    			'flag'					=>	$flag,
    			'message'				=>	$message,
    			'time'					=> date("Y-m-d H:i:s")
    	);
    	$this->profitCalculateFunc = $textField;
    	return $returnFlag;
    }


    /**
     * @desc 计算卖价（wish）
     * 销售价 = （成本+运费+包材包装费）*汇率  / （1 - 利润率  - 支付手续费率 - 订单损耗率 - 成交率）
     *    
     */
    private function calculateWishSalePrice(){
        $profitRate = $this->profitRate;//利润率
        if( !$profitRate ){
            $this->setErrorMessage(Yii::t('system', 'Profit Rate Is Required'));
            return false;
        }

        $productCost = $this->getProductCost();//产品成本        
        if( !$productCost ){
            $this->setErrorMessage(Yii::t('system', 'Product Cost OR SKU Is Required'));
            return false;
        }
        //平台佣金：15%；订单损耗率：3%；支付手续费：2%
        $productPackageMaterialCost = $this->getProductPackageMaterialCost();   //包材费用
        $productPackageMaterialCost = $productPackageMaterialCost ? $productPackageMaterialCost : 0;
        $productPackageTypeCost     = $this->getProductPackageTypeCost();       //包装费用
        $productPackageTypeCost     = $productPackageTypeCost ? $productPackageTypeCost : 0;

        $shippingCost = $this->getShippingCost();   //运费（卖价<10；20>卖价>10；卖价>20取不同物流快递，初始卖价<10）

        if( !$shippingCost ){
            $this->setErrorMessage(Yii::t('system', 'Can Not Get Shipping Cost'));
            return false;
        }        
        // $USDRateToCNY        = $this->getCurrencyRateToOther("USD", "CNY");//美员兑人民币
        // $unionCommission     = $this->getUnionCommission();     //联盟佣金比例 
        
        $payplatformAddition = 0;                               //支付附加费      
        $publicationFee      = 0;                               //刊登费用  $this->publicationFee;         
        $rateToCNY           = $this->getCurrencyRate();        //获取转化为人民币的汇率
        $platformRate        = $this->getPlatformCostRate();    //销售平台费用比例（佣金、成交率）
        $payPlatformCostRate = $this->getPayPlatformCostRate(); //支付平台手续费比例
        $orderLossRate       = $this->getOrderLossRate();       //订单损耗费比例                    

        $salePriceCNY = ($productCost + $shippingCost + $productPackageMaterialCost + $productPackageTypeCost) / (1 - $profitRate- $payPlatformCostRate - $orderLossRate  - $platformRate);
        $salePrice = $salePriceCNY / $rateToCNY;
        //$salePrice = ($productCost + $shippingCost + $productPackageMaterialCost + $productPackageTypeCost) / (1 - $platformRate - $payPlatformCostRate - $profitRate - $orderLossRate) / $rateToCNY;
        $this->salePrice = $salePrice;

        //重新计算运费
        if ($this->salePrice > 10){
            $shipReturn = false;
            if (isset($this->attributes)){
                if ($this->attributes){
                    if ($this->salePrice > 20){
                        $shipReturn = true;                    
                    }
                }else{
                    $shipReturn = true;
                }
            }
            if ($shipReturn){
                $this->shipCode = '';
                $shippingCost = $this->getShippingCost();
                if( !$shippingCost ){
                	$this->setErrorMessage(Yii::t('system', 'Can Not Get Shipping Cost'));
                	return false;
                }
                
                //重新计算
                //销售价 = （成本+运费+包材包装费）*汇率  / （1 - 利润率  - 支付手续费率 - 订单损耗率 - 成交率）
                $salePriceCNY = ($productCost + $shippingCost + $productPackageMaterialCost + $productPackageTypeCost) / (1 - $profitRate- $payPlatformCostRate - $orderLossRate  - $platformRate);
                $salePrice = $salePriceCNY / $rateToCNY;
                $this->salePrice = ceil($salePrice * 100) /100;
            }
        }
        

        $this->profit = round($salePriceCNY*$this->profitRate, 4);
        $textField = array(
                'publicationFee'             => $publicationFee,
                'payplatformAddition'        => $payplatformAddition*$rateToCNY,
                'productCost'                => $productCost,
                'shippingCost'               => $shippingCost,
                'productPackageMaterialCost' => $productPackageMaterialCost,
                'productPackageTypeCost'     => $productPackageTypeCost,
                '$platformRate'              => $platformRate,
                '$payPlatformCostRate'       => $payPlatformCostRate,
                '$profitRate'                => $profitRate,
                '$rateToCNY'                 => $rateToCNY
        );
        $this->profitCalculateFunc = $textField;
        return true;
    }

    /**
     * @desc 设置产品包材信息
     * @return boolean
     */
    private function setProductPackageMaterial(){
    	$this->skuPackageMaterialCost = 0;
    	$this->skuPackageMaterialWeight = 0;
    	return;
        if( !$this->skuPackageMaterial ){
            if( $this->sku ){
                $skuInfo = $this->getSkuInfoBySku($this->sku);
                if( !empty($skuInfo) ){
                    $this->skuPackageMaterial = $skuInfo['product_pack_code'];
                }
            }
        }
        
        if( $this->skuPackageMaterial ){
            $productPackageMaterialInfo = $this->getSkuInfoBySku($this->skuPackageMaterial);
            if( !empty($productPackageMaterialInfo) ){
                $this->skuPackageMaterialCost = $productPackageMaterialInfo['product_cost'];
                $this->skuPackageMaterialWeight = $productPackageMaterialInfo['product_weight'];
            }
        }else{
            $this->skuPackageMaterialCost = 0;
            $this->skuPackageMaterialWeight = 0;
        }
        
    }
    
    /**
     * @desc 设置产品包装信息
     * @return boolean
     */
    private function setProductPackageType(){
    	$this->skuPackageTypeCost = 0;
    	$this->skuPackageTypeWeight = 0;
    	return;
        if( !$this->skuPackageType ){
            if( $this->sku ){
                $skuInfo = $this->getSkuInfoBySku($this->sku);
                if( !empty($skuInfo) ){
                    $this->skuPackageType = $skuInfo['product_package_code'];
                }
            }
        }
        if( $this->skuPackageType ){
            $productPackageTypeInfo = $this->getSkuInfoBySku($this->skuPackageType);
            if( !empty($productPackageTypeInfo) ){
                $this->skuPackageTypeCost = $productPackageTypeInfo['product_cost'];
                $this->skuPackageTypeWeight = $productPackageTypeInfo['product_weight'];
            }else{
                $this->skuPackageTypeCost = 0;
                $this->skuPackageTypeWeight = 0;
            }
        }
    }
    
    public function setErrorMessage($message){
        $this->errorMessage = $message;
    }
    
    /**
     * @desc 获取sku信息
     * @param string $sku
     */
    private function getSkuInfoBySku($sku){
        if( !isset(self::$skuInfo[$sku]) ){
            $skuInfo = Product::model()->getProductInfoBySku($sku);
            self::$skuInfo[$sku] = $skuInfo;
        }
        return self::$skuInfo[$sku];
    }
    
    /**
     * 获取ebay平台的交易费
     * @param array $params
     * $salePrice 卖价
     * $shippingCost 运费
     * $categoryName Listing分类
     * $currency 货币
     * $rate 汇率
     */
    public function getEbayFee( $params = array() ){
    	$this->ebayfeeParams = $params;
		extract($params);
		if( !isset($salePrice) )  throw new Exception( Yii::t('system','Sale Price').Yii::t('system','Is Required') );
		if( !isset($shippingCost) )  throw new Exception( Yii::t('system','Shipping Cost').Yii::t('system','Is Required') );
		if( !isset($categoryName) )  throw new Exception( Yii::t('system','Category Name').Yii::t('system','Is Required') );
		//if( !isset($currency) )  throw new Exception( Yii::t('system','Currency').Yii::t('system','Is Required') );
		if( !isset($siteID) )  throw new Exception( "siteID " . Yii::t('system','Is Required') );
		if( !isset($accountID) )  throw new Exception( "accountID " . Yii::t('system','Is Required') );
		$ebayFee = 0;
		$categoryRule = array();//成交费计算规则
		$categoryName = strtolower($categoryName);//将类名转化为小写
		$categoryArr = explode(':',$categoryName);//转化为数组
		$siteName = EbaySite::getSiteName($siteID);
		$ebayAccount = EbayAccount::model()->findByPk($accountID);
		if(empty($ebayAccount))  throw new Exception( Yii::t('ebay','Invalid Account'));
		//US :0.3 CA:0.3  UK:0.26 AU:0.35 DE:0.35 ES:0.3 FR:0.3 刊登费用
		$publicationFee = 0;
		$realSalePrice = ($salePrice + $shippingCost);
		switch ($siteName){
			case 'US':
				//@TODO 非店铺 10%   是店铺 9%
				$ebayRate = 10/100;
				if($ebayAccount->store_level && $ebayAccount->store_site == $siteID){
					$ebayRate = 9/100;
				}
				//$ebayFee = ( floatval($salePrice) + floatval($shippingCost) ) * $ebayRate;
				$ebayFee = floatval($realSalePrice) * $ebayRate;
				//刊登费用
				$publicationFee = 0.3;
				break;
			case 'UK':
				$categoryRule = array(
					'nofee' => array(
							'final_value_rate' => 0/100,
							'categories' => array(
									'property',
							),
					),
					'media' => array(
							'final_value_rate' => 9/100,
							'categories' => array(
									'books, comics & magazines',
									'dvd, film & tv',
									'music',
									'video games & consoles:games',
							),
					),
					'collectables' => array(
							'final_value_rate' => 9/100,
							'categories' => array(
									'antiques',
									'coins',
									'collectables',
									'sports memorabilia',
									'stamps',
									'art',
							),
					),
					'furniture, bath, holidays & travel' => array(
							'final_value_rate' => 10/100,
							'max_fee' => 40,
							'categories' => array(
									'home, furniture & diy:bath',
									'home, furniture & diy:furniture',
									'holidays & travel'
							),
					),
					'consumer electronics' => array(
							'final_value_rate' => 5/100,
							'max_fee' => 10,
							'categories' => array(
									'wholesale & job lots:consumer electronics',
							),
					),
					'vehicle parts & accessories' => array(
							'final_value_rate' => 8/100,
							'categories' => array(
									'vehicle parts & accessories'
							),
					),
					'watches' => array(
							'final_value_rate' => 11/100,
							'max_fee' => 50,
							'categories' => array(
									'jewellery & watches:watches',
							),
					),
					'clothes, shoes & accessories' => array(
							'final_value_rate' => 11/100,
							'categories' => array(
									'clothes, shoes & accessories',
									'jewellery & watches',
							),
					),
				);
				//计算成交费
				$hasCategoryRule = false;
				foreach ($categoryRule as $details){
					foreach ($details['categories'] as $detail){
						$detail = strtolower($detail);
						if( strpos($categoryName,$detail) === 0 ){//如果在最开头匹配到
							$hasCategoryRule = true;
							$ebayFee = $realSalePrice * $details['final_value_rate'];//通过此分类的成交费百分比算出成交费;
							if( isset($details['max_fee']) && $ebayFee > $details['max_fee'] ){
								$ebayFee = $details['max_fee'];
							}
							$ebayRate = $details['final_value_rate'];//成交费比例
							break 2;
						}
					}
				}
				if(!$hasCategoryRule && !$ebayFee){//没找到类型则为其它类型
					$ebayRate = 10/100;//成交费比例
					$ebayFee = $realSalePrice * $ebayRate;//其它类型的成交费百分比为10%
				}
				
				//刊登费用
				$publicationFee = 0.26;
				break;
			case 'Australia':
				//非店铺9.9/100
				if($ebayAccount->store_level && $ebayAccount->store_site == $siteID){
					$categoryRule = array(
							array(
								'final_value_rate' => 8.5/100,
								'max_fee' => 250,
								'categories' => array(
										"Fashion",
										"Tech accessories",
										"Media",
										"Collectables"
								
								),
							),
							array(
									'final_value_rate' => 5/100,
									'max_fee' => 250,
									'categories' => array(
											"Technology",
											"Home Appliances"
									),
							),
					);
					//计算成交费
					$hasCategoryRule = false;
					foreach ($categoryRule as $details){
						foreach ($details['categories'] as $detail){
							$detail = strtolower($detail);
							if( strpos($categoryName,$detail) === 0 ){//如果在最开头匹配到
								$hasCategoryRule = true;
								$ebayFee = $realSalePrice * $details['final_value_rate'];//通过此分类的成交费百分比算出成交费;
								if( $details['max_fee'] && $ebayFee > $details['max_fee'] ){
									$ebayFee = $details['max_fee'];
								}
								$ebayRate = $details['final_value_rate'];//成交费比例
								break 2;
							}
						}
					}
					if(!$hasCategoryRule && !$ebayFee){//没找到类型则为其它类型
						$ebayRate = 7/100;//成交费比例
						$ebayFee = $realSalePrice * $ebayRate;//其它类型的成交费百分比为10%
					}
				}else{
					$ebayRate = 9.9/100;//成交费比例
					$ebayFee = $realSalePrice * $ebayRate;//其它类型的成交费百分比为10%
				}
				
				//刊登费用
				$publicationFee = 0;
				break;
			case 'Canada':
				$ebayRate = 9/100;//2016-04-28 lihy modify 10/100-->9/100
				$ebayFee = $realSalePrice * $ebayRate;
				//刊登费用
				$publicationFee = 0.3;
				break;
			case 'Germany':
				$category_rule = array(
					array(
						'rule' => array(
							array(
									'start_fee' => 0.01,
									'end_fee' => 0,
									'final_value_rate' => 9/100,
							),
						),
						'categories' => array(
							'Filme & DVDs',
							'Musik',
							'PC- & Videospiele',
							'Tickets',
						),
					),
					array(
						'rule' => array(
							array(
								'start_fee' => 0.01,
								'end_fee' => 150.00,
								'final_value_rate' => 6/100,
							),
							array(
								'start_fee' => 150.01,
								'end_fee' => 0,
								'final_value_rate' => 0/100,
							),
						),
						'categories' => array(
							'Computer, Tablets & Netzwerk',
							'Haushaltsgeräte',
							'Foto & Camcorder',
							'Handys & Kommunikation',
							'Auto-Hi-Fi & Navigation',
							'TV, Video & Audio',
						),
					),
					array(
						'rule' => array(
							array(
								'start_fee' => 0.01,
								'end_fee' => 0,
								'final_value_rate' => 10/100,
							),
						),
						'categories' => array(
								'Auto & Motorrad: Teile',
						),
					),
					array(
						'rule' => array(
							array(
								'start_fee' => 0.01,
								'end_fee' => 0,
								'final_value_rate' => 5/100,
								'add_fee' => 19
							),
						),
						'categories' => array(
							'Auto & Motorrad: Fahrzeuge',
						),
					),
					array(
						'rule' => array(
							array(
								'start_fee' => 0.01,
								'end_fee' => 0,
								'final_value_rate' => 10/100,//拍卖为5%
							),
						),
						'categories' => array(
							'Antiquitäten & Kunst',
							'Sammeln & Seltenes',
							'Briefmarken',
							'Münzen',
						),
					),
					array(
						'rule' => array(
							array(
								'start_fee' => 0.01,
								'end_fee' => 0,
								'final_value_rate' => 11/100,
							),
						),
						'categories' => array(
							'Kleidung & Accessoires',
							'Uhren & Schmuck',
							'Bücher',
							'Spielzeug',
							'Baby',
							'Möbel & Wohnen',
							'Beauty & Gesundheit',
						),
					),
					array(
						'rule' => array(
							array(
								'start_fee' => 0.01,
								'end_fee' => 200,
								'final_value_rate' => 11/100,
							),
							array(
								'start_fee' => 200.01,
								'end_fee' => 0,
								'final_value_rate' => 0/100,
							),
						),
						'categories' => array(
								'Heimwerker'
						),
					),
					array(
						'rule' => array(
							array(
								'start_fee' => 0.01,
								'end_fee' => 0,
								'final_value_rate' => 11/100,
							),
							array(
								'start_fee' => 200.01,
								'end_fee' => 0,
								'final_value_rate' => 0/100,
							),
						),
						'categories' => array(
							'Garten & Terrasse'
						),
					),
					array(
						'rule' => array(
							array(
								'start_fee' => 0.01,
								'end_fee' => 0,
								'final_value_rate' => 12/100,
							),
							array(
								'start_fee' => 500.01,
								'end_fee' => 0,
								'final_value_rate' => 0/100,
							),
						),
						'categories' => array(
							'Uhren & Schmuck'
						),
					),
				);
				//其它分类的规则
				$otherRule = array(
					array(
						'start_fee' => 0.01,
						'end_fee' => 0,
						'final_value_rate' => 9/100,
					),
				);
				//计算成交费
				foreach ($categoryRule as $details){
					foreach ($details['categories'] as $detail){
						$detail = strtolower($detail);
						if(strpos($categoryName,$detail)===0){//如果在最开头匹配到
							$ebayFee = $this->calculateFinalValue($realSalePrice,$details['rule'],$ebayRate);//通过此分类的规则算出成交费;
							break 2;//跳出最外层
						}
					}
				}
				if( !$ebayFee ){//没找到类型则为其它类型
					//$ebayFee = $this->calculateFinalValue($realSalePrice,$otherRule,$ebayRate);
					$ebayRate = 9/100;//成交费比例
					$ebayFee = $realSalePrice * $ebayRate;//其它类型的成交费百分比为10%
				}
				//刊登费用
				$publicationFee = 0.35;
				break;
			case 'France':
				$categoryRule = array(
						array(
								'final_value_rate' => 3.9/100,
								'max_fee' => 0,
								'categories' => array(
										'Produits électroniques'
								),
						),
						array(
								'final_value_rate' => 3.9/100,
								'max_fee' => 2174,
								'categories' => array(
										'Pneus', 
										'jantes', 
										'enjoliveurs'
								),
						),
						array(
								'final_value_rate' => 5.7/100,
								'max_fee' => 0,
								'categories' => array(
										'Accessoires électroniques',
										'Vêtements, accessoires',
										'Maison, jardin, bricolage',
										'Véhicules: pièces, accessoires',
										'Moto: pièces, accessoires'
								),
						),
				);
				
				
				//计算成交费
				$hasCategoryRule = false;
				foreach ($categoryRule as $details){
					foreach ($details['categories'] as $detail){
						$detail = strtolower($detail);
						if( strpos($categoryName,$detail) === 0 ){//如果在最开头匹配到
							$hasCategoryRule = true;
							$ebayFee = $realSalePrice * $details['final_value_rate'];//通过此分类的成交费百分比算出成交费;
							if( $details['max_fee'] && $ebayFee > $details['max_fee'] ){
								$ebayFee = $details['max_fee'];
							}
							$ebayRate = $details['final_value_rate'];//成交费比例
							break 2;
						}
					}
				}
				if(!$hasCategoryRule && !$ebayFee){//没找到类型则为其它类型
					$ebayRate = 6.5/100;//成交费比例
					$ebayFee = $realSalePrice * $ebayRate;//其它类型的成交费百分比为10%
				}
				//刊登费用
				$publicationFee = 0.3;
				break;
			case 'Spain':
				$categoryRule = array(
						array(
								'final_value_rate' => 4.3/100,
								'max_fee' => 0,
								'categories' => array(
										'Electrónica'
								),
						),
						
						array(
								'final_value_rate' => 4.3/100,
								'max_fee' => 2174,
								'categories' => array(
										'Neumáticos, llantas, y tapacubos'
								),
						),
				);
				
				
				//计算成交费
				$hasCategoryRule = false;
				foreach ($categoryRule as $details){
					foreach ($details['categories'] as $detail){
						$detail = strtolower($detail);
						if( strpos($categoryName,$detail) === 0 ){//如果在最开头匹配到
							$hasCategoryRule = true;
							$ebayFee = $realSalePrice * $details['final_value_rate'];//通过此分类的成交费百分比算出成交费;
							if( $details['max_fee'] && $ebayFee > $details['max_fee'] ){
								$ebayFee = $details['max_fee'];
							}
							$ebayRate = $details['final_value_rate'];//成交费比例
							break 2;
						}
					}
				}
				if(!$hasCategoryRule && !$ebayFee){//没找到类型则为其它类型
					$ebayRate = 7/100;//成交费比例
					$ebayFee = $realSalePrice * $ebayRate;//其它类型的成交费百分比为10%
				}
				//刊登费用
				$publicationFee = 0.3;
				break;
			default:
				$ebayRate = 10/100;
				$ebayFee = $realSalePrice * $ebayRate;
				break;
		}
		
		$ebayFee += $publicationFee;
		$this->publicationFeeOri = $publicationFee;//原始的货币价格
		if( isset($rate) ){//如果有汇率
			$ebayFee = $ebayFee * $rate;//把最终的ebay成交费乘以汇率得到人民币
			$publicationFee = $publicationFee * $rate;
		}
		//刊登费用
		$this->publicationFee = $publicationFee;
		
		return array(
				'fee' 	=> $ebayFee,
				'rate' 	=> round($ebayRate, 2), 
		);	
	}
	
	/**
	 * @desc 计算最后的费率
	 * @param unknown $fee
	 * @param unknown $rule
	 * @param number $ebay_rate
	 * @return Ambigous <number, unknown>
	 */
	public function calculateFinalValue($fee,$rule,&$ebay_rate=0){
		$final_value_fee = 0;
		foreach ($rule as $option) {
			if($option['start_fee']<=$fee && ($option['end_fee']>=$fee or $option['end_fee']==0)){
				$temp_fee = $fee-$option['start_fee']+0.01;//在此规则下要算的费用
				$final_value_fee = $temp_fee*$option['final_value_rate'];//计算出此费用段要交的成交费
				$ebay_rate = $option['final_value_rate'];//成交费比例
				
				$leave_fee = $fee-$temp_fee;//此规则剩下的费用
				if($leave_fee>0){//如果还有剩余，则继续按此规则算成交费
					$final_value_fee += $this->calculateFinalValue($leave_fee,$rule,$ebay_rate);
				}
				break;
			}
			if(isset($option['add_fee']) && $option['add_fee']>0){//如果要加额外费用
				$final_value_fee += $option['add_fee'];
			}
		}
		return $final_value_fee;
	}
	/**
	 * @desc 获取lazada平台费用
	 * @param array $params
	 */
	public function getLazadaFee($params = array()){
	    extract($params);
	    if( $this->categoryName ){
	        
	    }else{
	        $lazadaRate = 0.04;//modify in 2016-10-15, 0.1 -> 0.04
	    }
	    $lazadaFee = $salePrice * $lazadaRate;
	    if( isset($rate) ){//如果有汇率
	        $lazadaFee = $lazadaFee * $rate;//把最终的ebay成交费乘以汇率得到人民币
	    }
	    return array(
	        'fee' 	=> $lazadaFee,
	        'rate' 	=> round($lazadaRate, 2),
	    );
	}
	
	public function getAliexpressFee($params = array()) {
		extract($params);
		$rate = isset($this->commissionRate)?$this->commissionRate:0.05;
		$fees = $salePrice * $rate;
        
		return array(
			'fee' => $fees,
			'rate' => $rate,
		);
	}	
	
	/**
	 * 获取paypal支付平台的交易费
	 * @param array $params
	 * $totalAmount 付款到paypal的金额(包括卖价和运费)
	 * $rate  当前的汇率(转人民币的)
	 * $price 该金额要付的手续费,默认不传则是total的值
	 */
	public function getPaypalPayFee( $params = array() ){
		$this->ebayPaypalParams = $params;
	    extract($params);
	    if( !isset($totalAmount) )  throw new Exception( Yii::t('system','Price').Yii::t('system','Is Required') );
	    if( !isset($price) ){
	        $price = $totalAmount;
	    }
	    $fee1 = $fee2 = 0;
	    $fee1 = $price * 0.06 + 0.05;
	    $fee2 = $price * 0.029 + 0.3;
	    $fee = min(array($fee1, $fee2));//这个计算还待确定
	    if( $totalAmount < 10 ){
	        $paypalRate = 0.06;
	        $paypalAdd = 0.05;
	    }else{
	        $paypalRate = 0.029;
	        $paypalAdd = 0.3;
	    }
	    //$fee = $price * $paypalRate + $paypalAdd; 
	    if( isset($rate) ){	
	        $fee = round($fee * $rate, 2);
	    }
	    return array(
	        'fee' 	=> $fee,
	        'rate' 	=> $paypalRate,
	        'addFee'=> $paypalAdd,
	    );
	}
	/**
	 * @desc 获取wish平台费用（还需确定）
	 * @param unknown $params
	 * @return multitype:number
	 */
	public function getWishFee($params = array()){
		extract($params);
		$rate = 0.15;
		$fees = $salePrice * $rate;
		return array(
				'fee' => $fees,
				'rate' => $rate,
		);
	}
	
        /**
	 * @desc 获取joom平台费用（还需确定）
	 * @param unknown $params
	 * @return multitype:number
	 */
	public function getJoomFee($params = array()){
		extract($params);
		$rate = 0.15;
		$fees = $salePrice * $rate;
		return array(
				'fee' => $fees,
				'rate' => $rate,
		);
	}
        
	/**
	 * @desc 获取京东平台费用
	 * @param unknown $params
	 * @return multitype:number
	 */
	public function getJdFee($params = array()){
		extract($params);
		$rate = 0.08;
		$fees = $salePrice * $rate;
		if ($fees < 0.5)
			$fees = 0.5;
		return array(
				'fee' => $fees,
				'rate' => $rate,
		);
	}
	
	/**
	 * @desc 设置销售平台
	 * @param string $platformCode
	 */
	public function setPlatform($platformCode){
	    $this->plaformCode = $platformCode;
	}
	
	/**
	 * @desc 设置SKU
	 * @param string $platformCode
	 */
	public function setSku($sku){
	    $this->sku = $sku;
	    $skuInfo = $this->getSkuInfoBySku($sku);
	    if( !empty($skuInfo) ){
            if($skuInfo['avg_price'] <= 0){
    	        $this->productCost = $skuInfo['product_cost'];
            }else{
                $this->productCost = $skuInfo['avg_price'];
            }

	        $this->productWeight = $skuInfo['product_weight'];
	    }
	}
	
	/**
	 * @desc 设置币种
	 * @param char $currency
	 */
	public function setCurrency($currency){
	    $this->currency = $currency;
	}
	
	/**
	 * @desc 设置利润率
	 * @param unknown $profitRate
	 */
	public function setProfitRate($profitRate){
	    $this->profitRate = $profitRate;
	}
	
	/**
	 * @desc 设置国家名
	 * @param string $countryName
	 */
	public function setCountryName($countryName){
	    $this->countryName = $countryName;
	}
	
	/**
	 * @desc 用于获取头程运费
	 * @param int $warehouseID
	 */
	public function setWarehouseID($warehouseID){
	    $this->warehouseID = $warehouseID;
	}
	
	/**
	 * @desc 设置卖价
	 * @param float $salePrice
	 */
	public function setSalePrice($salePrice){
	    $this->salePrice = $salePrice;
	}
	
	/**
	 * @desc 设置运费code
	 * @param unknown $shipCode
	 */
	public function setShipCode($shipCode){
		$this->shipCode = $shipCode;
	}
	
	/**
	 * @desc 设置运费
	 * @param unknown $shipCost
	 */
	public function setShipingPrice($shipingPrice){
		$this->shippingPrice = $shipingPrice;
	}
    /**
     * @desc 设置订单损耗费比率
     * @param float $orderLossRate
     */
    public function setOrderLossRate($orderLossRate){
        $this->orderLossRate = $orderLossRate;
    }
	/**
	 * @DESC 设置账号
	 * @param unknown $accountID
	 */
	public function setAccountID($accountID){
		$this->accountID = $accountID;
	}
	
	/**
	 * @desc 设置分类名称
	 * @param unknown $categoryName
	 */
	public function setCategoryName($categoryName){
		$this->categoryName = $categoryName;
	}
	
	/**
	 * @desc 设置站点ID
	 * @param unknown $siteID
	 */
	public function setSiteID($siteID){
		$this->siteID = $siteID;
	}

	/**
	 * @desc 设置支付平台
	 * @param unknown $payPlatform
	 */
	public function setPayPlatform($payPlatform){
		$this->payPlatform = $payPlatform;
	}

    /**
     * @desc 设置佣金比例
     * @param float $commissionRate
     */
    public function setCommissionRate($commissionRate){
        $this->commissionRate = $commissionRate;
    }

    /**
     * @desc 设置联盟佣金比例
     * @param float $unionCommission
     */
    public function setUnionCommission($unionCommission = 0){
        $this->unionCommission = $unionCommission;
    }

    /**
     * @desc 设置调试入口
     * @param string $debug
     */
    public function setDebug($debug){
        $this->debug = $debug;
    }	
	
	// ================== START 临时处理代码 请不要随便调用  =====================//
	
	/**
	 * @desc 计算卖价
	 * 销量利润率 = (销售价-固定成本-销售价*(销售平台手续费比例+支付平台手续费比例))/销售价
	 * ----> 销售价 = 固定成本/((1-(销售平台手续费比例+支付平台手续费比例))-利润率))
	 * 固定成本 = 产品成本 + 运费成本 + 包装成本 + 包材成本
	 */
	public function calculateAliSalePrice(){
		$profitRate = $this->profitRate;//利润率
		if( !$profitRate ){
			$this->setErrorMessage(Yii::t('system', 'Profit Rate Is Required'));
			return false;
		}
		$shippingPrice = $this->shippingPrice ? $this->shippingPrice : 0;//收取运费
		//$productCost = $this->getProductCost();//产品成本
		$skuInfo = $this->getSkuInfoBySku($this->sku);
		if(empty($skuInfo)){
			$this->setErrorMessage('SKU Not find ');
			return false;
		}
		$productCost = $skuInfo['avg_price'] > 0 ? $skuInfo['avg_price'] : $skuInfo['product_cost'];
		$this->productCost = $productCost;
		if( !$productCost ){
			$this->setErrorMessage(Yii::t('system', 'Product Cost OR SKU Is Required'));
			return false;
		}
		$productPackageMaterialCost = $this->getProductPackageMaterialCost();//包材费用
		$productPackageMaterialCost = $productPackageMaterialCost ? $productPackageMaterialCost : 0;
		$productPackageTypeCost = $this->getProductPackageTypeCost();//包装费用
		$productPackageTypeCost = $productPackageTypeCost ? $productPackageTypeCost : 0;
		$shippingCost = $this->getAliShippingCost();
		if( !$shippingCost ){
			$this->setErrorMessage(Yii::t('system', 'Can Not Get Shipping Cost'));
			return false;
		}
		$rateToCNY = $this->getCurrencyRate();
		$platformRate = 0.05;//销售平台费用比例
		$payPlatformCostRate = 0.02;//支付平台手续费比例
		$payplatformAddition = 0;
		$publicationFee = $this->publicationFee;
		$salePriceCNY = ($publicationFee + $payplatformAddition*$rateToCNY + $productCost + $shippingCost + $productPackageMaterialCost + $productPackageTypeCost) / (1 - $platformRate - $payPlatformCostRate - $profitRate);
		$salePrice = $salePriceCNY / $rateToCNY;
		$this->salePrice = ceil($salePrice * 100) /100;
		$this->profit = round($salePriceCNY*$this->profitRate, 4);
		$textField = array(
				'salePrice'					=>	$salePrice,
				'publicationFee'			=>	$publicationFee,
				'payplatformAddition'		=>	$payplatformAddition*$rateToCNY,
				'productCost'				=>	$productCost,
				'shippingCost'				=>	$shippingCost,
				'productPackageMaterialCost'=>	$productPackageMaterialCost,
				'productPackageTypeCost'	=>	$productPackageTypeCost,
				'platformRate'				=>	$platformRate,
				'payPlatformCostRate'		=>	$payPlatformCostRate,
				'profitRate'				=>	$profitRate,
				'rateToCNY'					=>	$rateToCNY,
				'shipCode'					=>	$this->shipCode,
				'shipCountry'				=>	$this->shipCountry,
				'productWeight'				=>	$this->productWeight,
				'oproductCost'				=>	$skuInfo['product_cost'],
				'oavgprice'					=>	$skuInfo['avg_price']
		);
		$this->profitCalculateFunc = $textField;
		return $textField;
	}
	
	
	/**
	 * @desc 获取运输成本
	 */
	public function getAliShippingCost(){
		$attributes = $this->sku ? Product::model()->getAttributeBySku($this->sku, 'product_features') : array();//属性
		$weight = $this->productWeight + $this->skuPackageMaterialWeight + $this->skuPackageTypeWeight;//重量
		//临时再加10g
		$weight += 10;
		$shipCode = $country = "";
	
		if($weight > 30){
			$country = 'Russian Federation';
			
			$shipCode = Logistics::CODE_CM_SF;
			
			$shipFee = Logistics::model()->getShipFee($shipCode, $weight, array(
					'country'   	=> $country,
					'attributeid'   => $attributes,
					'sku'			=>	$this->sku
			));
			if(!$shipFee){
				$shipCode = "";
				$shipFee = Logistics::model()->getShipFee($shipCode, $weight, array(
						'country'   	=> $country,
						'attributeid'   => $attributes,
						'sku'			=>	$this->sku
				));
			}
		}else{
			$country = 'Israel';
			if(!$attributes){
				$shipCode = Logistics::CODE_CM_ALI_DGYZ;
			}
			$shipFee = Logistics::model()->getShipFee($shipCode, $weight, array(
					'country'   	=> $country,
					'attributeid'   => $attributes,
					'sku'			=>	$this->sku
			));
		}
		
		$this->shipCode = $shipCode;
		$this->shipCost = $shipFee;
		$this->productWeight = $weight;
		$this->shipCountry = $country;
		return $this->shipCost;
	}
	
	
	// ================== END 临时处理代码 请不要随便调用 =======================//
}