<?php
/**
 * @desc Ebay刊登价格配置
 * @author lihy
 * @since 2016-03-28
 */
class EbayProductSalePriceConfig extends EbayModel{
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_ebay_sale_price_config';
    }
    
    
    /**
     * @desc 获取售价
     * @param unknown $sku
     * @param unknown $currency
     * @param unknown $siteID
     * @param unknown $accountID
     * @param string $categoryName
     * @return boolean
     */
    public function getSalePrice($sku, $currency, $siteID, $accountID, $categoryName = '', &$targetRate = 0){
    	//获取利润率
    	$productInfo = Product::model()->getProductInfoBySku($sku);
    	if(!$productInfo) return false;
    	if($targetRate){
    		$profitRate = $targetRate;
    	}else{
    		/* $cost = $productInfo['product_cost'];
    		$profitRate = $this->getRandomRate($cost); */
			//获取利润率,如果是特殊站点+0.02
			$where = "platform_code = :platform_code and profit_calculate_type = :profit_calculate_type";
			$param = array(':platform_code' => Platform::CODE_EBAY,':profit_calculate_type' => EbayProductAdd::AUCTION_PROFIT_SALEPRICE);
			$salePriceInfo = SalePriceScheme::model()->getSalePriceSchemeByWhere($where,$param);
			$specialSites = EbaySite::model()->getSpecialLanguageSiteIDs();
			$isSpecial = in_array($siteID, $specialSites);
			if($salePriceInfo){
				if($isSpecial){//特殊站点
					$profitRate = $salePriceInfo['standard_profit_rate']+0.02;
				}else{
					$profitRate = $salePriceInfo['standard_profit_rate'];
				}
			}else{
				if($isSpecial){//特殊站点
					$profitRate = 0.16;
				}else{
					$profitRate = 0.14;
				}
			}
    	}
    	
    	//货币转换
    	//计算卖价，获取描述
    	$priceCal = new CurrencyCalculate();

    	//设置参数值
    	$priceCal->setProfitRate($profitRate);//设置利润率
    	$priceCal->setCurrency($currency);//币种
    	$priceCal->setPlatform(Platform::CODE_EBAY);//设置销售平台
    	$priceCal->setSku($sku);//设置sku
    	$priceCal->setCategoryName($categoryName);
    	$priceCal->setAccountID($accountID);
    	$priceCal->setSiteID($siteID);
    	$priceCal->setPayPlatform("paypal");
    	$priceCal->setOrderLossRate(0.03);//新增订单损耗费比例
    	$time1 = time();
    	$data['salePrice']     = $priceCal->getSalePrice(true);//获取卖价
    	$data['xx4-1'] = $priceCal->profitCalculateFunc;
    	$minPrice = $this->getMinSaleprice($currency);
    	if($data['salePrice'] < $minPrice){
    		$data['salePrice'] = $minPrice; //因为有其他地方获取，所以利润率不重新计算
    	}
    	$time2 = time();
    	$data['profitRate']    = $priceCal->getProfitRate(true)*100 . '%';//获取利润率
    	//$data['xx4-2'] = $priceCal->profitCalculateFunc;
    	$time3 = time();
    	$data['profit']        = $priceCal->getProfit(true);//获取利润
    	//$data['xx4-3'] = $priceCal->profitCalculateFunc;
    	$time4 = time();
    	$data['desc'] = '';
    	
    	//$data['desc']          = $priceCal->getCalculateDescription();//获取计算详情
    	
    	$data['oriProfitRate'] = $profitRate;
    	$data['xx1'] = $priceCal->ebayPaypalParams;
    	$data['xx2'] = $priceCal->ebayfeeParams;
    	$data['xx3'] = $priceCal->shipParam;
    	$data['sku'] = $sku;
    	$data['time'] = array(
    						$time1, $time2, $time3, $time4
    					);
    	/* $data = array(
    		'salePrice'	=>	$cost*(1+$profitRate),
    		'profit'	=>	$cost*$profitRate,
    		'profitRate'	=>	$profitRate,
    		'desc'		=>	'',
    	); */
    	return $data;
    }
    
    /**
     * @desc 获取利润
     * 销量利润率 = (销售价-固定成本-销售价*(销售平台手续费比例+支付平台手续费比例))/销售价
     * ----> 销售价 = 固定成本/((1-(销售平台手续费比例+支付平台手续费比例))-利润率))
     * 固定成本 = 产品成本 + 运费成本 + 包装成本 + 包材成本
     */
    public function getProfitInfo($salePrice, $sku, $currency, $siteID, $accountID, $categoryName = '', $shipingPrice = 0){
    	$priceCal = new CurrencyCalculate();
    	$priceCal->setCurrency($currency);//币种
    	$priceCal->setPlatform(Platform::CODE_EBAY);//设置销售平台
    	$priceCal->setSku($sku);//设置sku
    	$priceCal->setCategoryName($categoryName);
    	$priceCal->setAccountID($accountID);
    	$priceCal->setSiteID($siteID);
    	$priceCal->setPayPlatform("paypal");
    	$priceCal->setSalePrice($salePrice);
    	$priceCal->setShipingPrice($shipingPrice);
    	$priceCal->setOrderLossRate(0.03);//新增订单损耗费比例
    	$time1 = time();
    	$profit = $priceCal->getProfit(true);
    	
    	$profitCalculateFunc1 = $priceCal->profitCalculateFunc;
    	
    	$time2 = time();
    	$profitRate = $priceCal->getProfitRate(true)*100 . '%';
    	
    	$profitCalculateFunc2 = $priceCal->profitCalculateFunc;
    	
    	$time3 = time();
        /*$desc = '';
        if (isset($_REQUEST['bug'])) {
            $desc = $priceCal->getCalculateDescription();
        }*/
		$desc = $priceCal->getEbayCalculateDescription();
    	
    	$xx1 = $priceCal->ebayPaypalParams;
    	$xx2 = $priceCal->ebayfeeParams;
		$productCost = $priceCal->getProductCost();
    	return array(
    		0=>$profit, 1=>$profitRate,'product_cost'=>$productCost,
    		'profit'=>$profit, 'profit_rate'=>$profitRate, 'error_msg'=>$priceCal->getErrorMessage(), 'desc'=>$desc, 
    		"xx1"=>$xx1, "xx2"=>$xx2, "xx3"=>$priceCal->shipParam,
    		'xx4-1'=>$profitCalculateFunc1,
    		//'xx4-2'=>$profitCalculateFunc2,
    		'sku'	=>	$sku,	
    		'time'	=>	array(
    						$time1, $time2, $time3
    					)
    	);
    }
    
    
    
    /**
     * @desc 计算价格
     * @param unknown $sku
     * @param unknown $currency
     * @param unknown $siteID
     * @param unknown $accountID
     * @param string $categoryName
     * @param number $targetRate
     * @return boolean|multitype:number
     */
    public function getSalePriceNew($sku, $currency, $siteID, $accountID, $categoryName = '', &$targetRate = 0){
    	//获取利润率
    	$productInfo = Product::model()->getProductInfoBySku($sku);
    	if(!$productInfo) return false;
    	if($targetRate){
    		$profitRate = $targetRate;
    	}else{
    		/* $cost = $productInfo['product_cost'];
    		 $profitRate = $this->getRandomRate($cost); */
    		//获取利润率,如果是特殊站点+0.02
    		$where = "platform_code = :platform_code and profit_calculate_type = :profit_calculate_type";
    		$param = array(':platform_code' => Platform::CODE_EBAY,':profit_calculate_type' => EbayProductAdd::AUCTION_PROFIT_SALEPRICE);
    		$salePriceInfo = SalePriceScheme::model()->getSalePriceSchemeByWhere($where,$param);
    		$specialSites = EbaySite::model()->getSpecialLanguageSiteIDs();
    		$isSpecial = in_array($siteID, $specialSites);
    		if($salePriceInfo){
    			if($isSpecial){//特殊站点
    				$profitRate = $salePriceInfo['standard_profit_rate']+0.02;
    			}else{
    				$profitRate = $salePriceInfo['standard_profit_rate'];
    			}
    		}else{
    			if($isSpecial){//特殊站点
    				$profitRate = 0.16;
    			}else{
    				$profitRate = 0.14;
    			}
    		}
    	}
    	 
    	//货币转换
    	//计算卖价，获取描述
    	$priceCal = new CurrencyCalculate();
    
    	//设置参数值
    	$priceCal->setProfitRate($profitRate);//设置利润率
    	$priceCal->setCurrency($currency);//币种
    	$priceCal->setPlatform(Platform::CODE_EBAY);//设置销售平台
    	$priceCal->setSku($sku);//设置sku
    	$priceCal->setCategoryName($categoryName);
    	$priceCal->setAccountID($accountID);
    	$priceCal->setSiteID($siteID);
    	$priceCal->setPayPlatform("paypal");
    	$priceCal->setOrderLossRate(0.03);//新增订单损耗费比例
    	$time1 = time();
    	$data['salePrice']     = $priceCal->getSalePrice(true);//获取卖价
    	$data['xx4-1'] = $priceCal->profitCalculateFunc;
    	$minPrice = $this->getMinSaleprice($currency);
    	if($data['salePrice'] < $minPrice){
    		$data['salePrice'] = $minPrice; //因为有其他地方获取，所以利润率不重新计算
    	}
    	$time2 = time();
    	$data['profitRate']    = $priceCal->getProfitRate(true)*100 . '%';//获取利润率
    	//$data['xx4-2'] = $priceCal->profitCalculateFunc;
    	$time3 = time();
    	$data['profit']        = $priceCal->getProfit(true);//获取利润
    	//$data['xx4-3'] = $priceCal->profitCalculateFunc;
    	$time4 = time();
    	$data['desc'] = '';
    	 
    	$data['desc']          = $priceCal->getEbayCalculateDescription();//获取计算详情
    	 
    	$data['oriProfitRate'] = $profitRate;
    	$data['xx1'] = $priceCal->ebayPaypalParams;
    	$data['xx2'] = $priceCal->ebayfeeParams;
    	$data['xx3'] = $priceCal->shipParam;
    	$data['sku'] = $sku;
    	$data['time'] = array(
    			$time1, $time2, $time3, $time4
    	);
    	/* $data = array(
    	 'salePrice'	=>	$cost*(1+$profitRate),
    			'profit'	=>	$cost*$profitRate,
    			'profitRate'	=>	$profitRate,
    			'desc'		=>	'',
    	); */
    	return $data;
    }
    
    
    
    /**
     * @desc 获取建议卖价
     * @param unknown $sku
     * @param unknown $currency
     * @param unknown $categoryname
     * @param number $old_price
     * @param number $target_rate
     * @return boolean
     */
    public function getSuggestedSaleprice($sku, $currency, $categoryname, $oldPrice=0, &$targetRate=0){
    	$productInfo = Product::model()->getProductInfoBySku($sku);
    	if(!$targetRate){//没有传目标利润率
    		$targetRate = $this->getRandomRate($productInfo['product_cost']);
    	}
    	if($targetRate){
    		//第一获取不知道卖价,则
    		$salePrice = $this->getSalePriceByProfitRate($oldPrice,$productInfo['product_cost'],$productInfo['product_weight'],$productInfo['product_package_code'],$currency, 0, $categoryname, $targetRate, $sku);
    		if(!$oldPrice){//如果第一个定价,之前没有传卖价. 则计算手续费不准确,再算一次.
    			$salePrice = $this->getSalePriceByProfitRate($salePrice, $productInfo['product_cost'], $productInfo['product_weight'], $productInfo['product_package_code'], $currency, 0, $categoryname, $targetRate, $sku);
    		}
    	}else{
    		$salePrice = false;
    	}
    	return $salePrice;
    }
  
    
    /**
     * @desc 通过利润率获取销售价格
     * @param unknown $salePrice
     * @param unknown $productCost
     * @param unknown $productWeight
     * @param unknown $postID //包装类型 舍弃字段
     * @param unknown $currency
     * @param unknown $iseub
     * @param unknown $categoryName
     * @param unknown $profitRate
     * @param string $sku
     * @param string $warehouse
     * @param number $accountID
     * @return number
     */
    public function getSalePriceByProfitRate($salePrice, $productCost, $productWeight, $currency, $iseub, $categoryName, $profitRate, $sku = '', $warehouse = '',$accountID = 0){
    	//@TODO 简单的售价计算
    	$newSalePrice = $productCost * (1+$profitRate);
    	return $newSalePrice;
    	//货币转换
    	//计算卖价，获取描述
    	$priceCal = new CurrencyCalculate();
    	//设置参数值
    	$priceCal->setProfitRate($profitRate);//设置利润率
    	$priceCal->setCurrency($currency);//币种
    	$priceCal->setPlatform(Platform::CODE_EBAY);//设置销售平台
    	$priceCal->setSku($sku);//设置sku
    	$data['salePrice']     = $priceCal->getSalePrice();//获取卖价
    	$data['profit']        = $priceCal->getProfit();//获取利润
    	$data['profitRate']    = $priceCal->getProfitRate();//获取利润率
    	$data['desc']          = $priceCal->getCalculateDescription();//获取计算详情
    }
    

    /**
     * @desc 获取随机利润率
     * @param unknown $cost
     * @return boolean|number
     */
    public function getRandomRate($cost){
    	$configInfo = $this->getDbConnection()->createCommand()->from($this->tableName())->where("start<='$cost' AND end>='$cost'")->queryRow();
    	if(!$configInfo){
    		return false;
    	}
    	$startRate = $configInfo['standard_rate'] - $configInfo['float_rate'];
    	$endRate = $configInfo['standard_rate'] + $configInfo['float_rate'];
    	$precision = 10000;//1000代表4位
    	return rand($startRate*$precision, $endRate*$precision)/$precision;
    }
    
    /**
     * @desc 获取最低的卖价
     * @param unknown $currency
     * @return Ambigous <number>
     */
    public function getMinSaleprice($currency){
    	//US：0.99美金、CA：0.99加币、UK：0.99英镑， AU、DE、ES、FR 最低价：1EUR
    	$currency = strtoupper($currency);
    	$minPrice = array(
    			'USD' => 0.99,
    			'AUD' => 1,
    			'GBP' => 0.99,
    			'CAD' => 0.99,
    			'EUR' => 1,
    	);
    	return $minPrice[$currency];
    }
   
    /**
     * @desc 函数功能: 通过两个数值和一个运算符求得最后的结果
	 * 		  传入参数: $price:本身的价格,$operator:运算符,$set:运算值,$decimals:保留几位小数,$minval:结果少于最小值则直接返回最小值
     * @param unknown $price
     * @param unknown $operator
     * @param unknown $set
     * @param number $decimals
     * @param real $minval
     * @return Ambigous <unknown, real>
     */
    public static function calculatePrice($price, $operator, $set, $decimals=2, $minval=0.00){
    	$result = $price;//返回结果默认为本身的价格
    
    	$operator = strtoupper($operator);
    	switch ($operator){
    		case '+':
    			$result = $price+$set;
    			break;
    		case '-':
    			$result = $price-$set;
    			break;
    		case 'X':
    			$result = $price*$set;
    			break;
    		case '/':
    			$result = $price/$set;
    			break;
    	}
    
    	$result = number_format($result, $decimals, '.', '');//保留小数位
    	$result = $result > $minval ? $result : $minval;//结果少于最小值则直接返回最小值
    	return $result;
    }
    
    // ======================  	搜索界面  ================

    public function rules(){
    	return array(
    			array('start,end,standard_rate,min_rate,float_rate,opration_date,opration_id', 'safe'),
    			array('start,end,standard_rate,min_rate,float_rate', 'required')
    	);
    }
    
    public function additions($datas){
    	/* if($datas){
    		foreach ($datas as $key=>$data){
    			//...
    		}
    	} */
    	return $datas;
    }
    
    public function search(){
    	$csort = new CSort();
    	$csort->descTag = 'asc';
    	$csort->attributes = array(
    								'defaultOrder'	=>	'start',
    						);
    	
						    	
    	
    	$dataProvider = parent::search($this, $csort, '', $this->setCDbCriteria());
    	$data = $this->additions($dataProvider->getData());
    	$dataProvider->setData($data);
    	return $dataProvider;
    }
    
    public function setCDbCriteria(){
    	$CDbCriteria = new CDbCriteria();
    	$CDbCriteria->select = "*";
    	return $CDbCriteria;
    }
    
    
    public function attributeLabels(){
    	return array(
			    'id'			=>		'ID',
    			'start'			=>		Yii::t("ebay", "Start Price"),
    			'end'			=>		Yii::t("ebay", "End Price"),
    			'standard_rate'	=>		Yii::t("ebay", "Standard Rate"),
    			'min_rate'		=>		Yii::t("ebay", "Min Rate"),
    			'float_rate'	=>		Yii::t("ebay", "Float Rate"),
    			'opration_date'	=>		Yii::t("ebay", "Opration Date"),
    	);
    }
    
    public function filterOptions(){
    	return array();
    }
    
    // ====================== 	搜索界面  ==============
    

    /**
     * 通过参数获取利润和利润率数组
     * @param  array $shipppingPriceArr  运费数组
     * @param  array $paramArr           参数数组
     * @return array 
     */
    public function getProfitAndProfitRateByParam($shipppingPriceArr,$paramArr){
        foreach ($shipppingPriceArr as $value) {
            $profitInfo = $this->getProfitInfo($paramArr['sale_price'], $paramArr['sku'], $paramArr['current_price_currency'], $paramArr['site_id'], $paramArr['account_id'], $paramArr['category_name'], $value);
            if($profitInfo){
                $profitArr[]        = (empty($profitInfo['profit']))?0:$profitInfo['profit'];
                $profitRateArr[]    = rtrim($profitInfo['profit_rate'],'%');
                $productCostArr[]   = (empty($profitInfo['product_cost']))?0:$profitInfo['product_cost'];
            }else{
                $profitArr[]        = 0;
                $profitRateArr[]    = 0;
				$productCostArr[]    = 0;
            }
        }

        return $profitAndProfitRateArr = array('profit'=>implode(',', $profitArr), 'profit_rate'=>implode(',', $profitRateArr), 'product_cost'=>implode(',', $productCostArr));
    }
}