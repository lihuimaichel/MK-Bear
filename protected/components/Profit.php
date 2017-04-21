<?php
/**
 * Profit Calculate Class
 * 
 * @author Gordon
 * @since 2014-03-25
 */
class Profit {
	
	/**
	 * Get Profit And Profit Rate
	 * @param array $params
	 * 	
	 * @return array
	 */
	public function getProfit( $params=array() ){
		extract($params);
		if( !isset($salePrice) ){ throw new CException(Yii::t('system','Sale Price').Yii::t('system','Is Required'));}
		if( !isset($shippingCost) ){ throw new CException(Yii::t('system','Shipping Cost').Yii::t('system','Is Required'));}
		if( !isset($shippingCostCommon) ){ throw new CException(Yii::t('system','Common Shipping Cost').Yii::t('system','Is Required'));}
		if( !isset($currency) ){ throw new CException(Yii::t('system','Currency').Yii::t('system','Is Required'));}
		if( !isset($sku) ){//没传递sku
			if( !isset($cost) ){ throw new CException(Yii::t('system','Cost').Yii::t('system','Is Required'));}
			if( !isset($weight) ){ throw new CException(Yii::t('system','Weight').Yii::t('system','Is Required'));}
		}else{
			$productInfo = $productModel->getBySku($sku);//产品信息
			if( !$productInfo['sku'] ){
				throw new CException(Yii::t('products','SKU Dose Not Exist'));
			}	
		}
		$productModel = UebModel::model('product');
		
		$packagePrice = 0;
		$packageWeight = 0;
		//有sku则获取包装的重量和价值（珍珠棉等）
		if( !empty($sku) ){//传递了sku
			$weight = floatval($productInfo['product_weight']);
			$cost = floatval($productInfo['product_cost']); 
			$packageMaterial = $productModel->getBySku($productInfo['product_pack_code']);
			$packageType = $productModel->getBySku($productInfo['product_package_code']);
			$packageWeight = floatval(isset( $packageMaterial['sku'] ) ? $packageMaterial['product_weight'] : 0) + floatval(isset( $packageType['sku'] ) ? $packageType['product_weight'] : 0);
			$packagePrice = floatval(isset( $packageMaterial['sku'] ) ? $packageMaterial['product_cost'] : 0) + floatval(isset( $packageType['sku'] ) ? $packageType['product_cost'] : 0);
		}else{
			if( isset($packageMaterialSku) ){//包材
				$packageMaterial = $productModel->getBySku($packageMaterialSku);
				$packageWeight += floatval(isset( $packageMaterial['sku'] ) ? $packageMaterial['product_weight'] : 0);
				$packagePrice += floatval(isset( $packageMaterial['sku'] ) ? $packageMaterial['product_cost'] : 0);
			}
			if( isset($packageTypeSku) ){//包装
				$packageType = $productModel->getBySku($packageTypeSku);
				$packageWeight += floatval(isset( $packageType['sku'] ) ? $packageType['product_weight'] : 0);
				$packagePrice += floatval(isset( $packageType['sku'] ) ? $packageType['product_cost'] : 0);
			}	
		}
		$weight = ceil($weight + $packageWeight);//总重量
		$cost	= $cost + $packagePrice;//总成本
		//将总收入转化为美元	
		$rateToUSD = UebModel::model('currencyRate')->getRateByCondition($currency, 'USD');
		$totalUSD = $currency == 'USD' ? ( floatval($salePrice) + floatval($shippingCost) ) : ( floatval($salePrice) + floatval($shippingCost) ) * floatval($rateToUSD); 
		//计算发货所需运费（分海外仓和本地仓）
		//检查特殊参数,如国家,属性,仓库等信息
		$shippingInfo = array();
		$shippingInfo['weight'] = $weight;
		$shippingInfo['amount'] = $totalUSD;
		if( isset( $country ) ) 	$shippingInfo['countryname'] = $country;//国家
		if( isset( $attributes ) ) 	$shippingInfo['attributes'] = $attributes;//属性
		if( isset( $warehouse ) ) 	$shippingInfo['warehouse'] = $warehouse;//仓库
		if( isset( $shipCode ) ) 	$shippingInfo['shipCode'] = $shipCode;//物流编码
		if( isset( $shipGroup ) ) 	$shippingInfo['shipGroup'] = $shipGroup;//计价方案组
		if( !empty($sku) ){//传递了sku
			$productAttrId = UebModel::model('productAttribute')->getAttributeIdByCode();
			$shippingInfo['attributes'] = UebModel::model('productSelectAttribute')->getAttributeBySku($sku, $productAttrId);//根据sku获取属性值数组
		}
		$shippingCostCal = $this->getMinShippingCost($shippingInfo);//根据系统配置的值决定走哪种类型物流并找到最好的
		//本地仓到海外仓运费 TODO
		
		//获取销售平台成交费(默认取ebay平台)
		$platformCode = isset( $platformCode ) ? $platformCode : UebModel::model('Platform')->getEbayPlatformCode();
		$platformFee 	= 0;
		$platformRate 	= 0;
		//获取汇率(转化为人民币)
		$rateToCNY = UebModel::model('currencyRate')->getRateByCondition($currency, 'HKD') * UebModel::model('currencyRate')->getRateByCondition('HKD', 'CNY');
		if( isset($categoryName) ){
			$platformFeeArr = $this->getPlatformFee($platformCode,array(
					'salePrice' 	=> $salePrice,
					'shippingCost' 	=> $shippingCost,
					'categoryName' 	=> $categoryName,
					'currency' 		=> $currency,
					'rate' 			=> $rateToCNY,
			));
			$platformFee 	= $platformFeeArr['fee'];
			$platformRate 	= $platformFeeArr['rate'];
		}
		
		//获取支付平台交易费
		$payPlatform = isset( $payPlatform ) ? $payPlatform : 'Paypal';
		$payFeeArr = $this->getPayPlatformFee($payPlatform, array(
				'totalAmount' 	=> $salePrice + $shippingCostCommon,
				'rate' 			=> $rateToCNY,
				'price' 		=> $salePrice + $shippingCost,
		));
		$payFee = $payFeeArr['fee'];
		//转化相关费用为人民币
		$salePrice 		= round(floatval($salePrice * $rateToCNY), 2);//卖价
		$shippingCost 	= round(floatval($shippingCost * $rateToCNY), 2);//运费
		//获取利润和利润率       利润=(卖价+运费-销售平台相关费用-支付平台相关费用-商品成本-运费成本-邮包成本)*0.99
		$profit = round(($salePrice + $shippingCost - $platformFee - $payFee - $cost - $shippingCostCal) * 0.99, 2);
		$profitRate = round($profit/($salePrice + $shippingCost) * 100, 2).'%';
		//返回利润相关信息
		return array(
				'profit'		=> $profit,
				'profit_rate' 	=> $profitRate,
		);
	}
	
	/**
	 * 根据利润率获取卖价(免运费)
	 * @param array $params
	 * $salePrice 卖价(非人民币)
	 * $cost 成本
	 * $weight 重量
	 * $packageTypeSku 包装类型SKU
	 * $packageMaterialSku 包材类型SKU
	 * $currency 币种
	 * $categoryName 分类名称
	 * $profitRate 利润率
	 * $sku SKU
	 */
	public function getSalePriceByProfitRate( $params = array() ){
		extract($params);
		if( isset($salePrice) ){ throw new CException(Yii::t('system','Sale Price').Yii::t('system','Is Required'));}
		if( isset($profitRate) ){ throw new CException(Yii::t('system','Profit Rate').Yii::t('system','Is Required'));}
		if( isset($currency) ){ throw new CException(Yii::t('system','Currency').Yii::t('system','Is Required'));}
		if( !isset($sku) ){//没传递sku
			if( isset($cost) ){ throw new CException(Yii::t('system','Cost').Yii::t('system','Is Required'));}
			if( isset($weight) ){ throw new CException(Yii::t('system','Weight').Yii::t('system','Is Required'));}
		}else{
			$productInfo = $productModel->getBySku($sku);//产品信息
			if( !$productInfo['sku'] ){
				throw new CException(Yii::t('products','SKU dosenot exist'));
			}
		}
		$productModel = UebModel::model('product');
		
		$packagePrice = 0;
		$packageWeight = 0;
		//有sku则获取包装的重量和价值（珍珠棉等）
		if( !empty($sku) ){//传递了sku
			$weight = floatval($productInfo['product_weight']);
			$cost = floatval($productInfo['product_cost']);
			$packageMaterial = $productModel->getBySku($productInfo['product_pack_code']);
			$packageType = $productModel->getBySku($productInfo['product_package_code']);
			$packageWeight = floatval(isset( $packageMaterial['sku'] ) ? $packageMaterial['product_weight'] : 0) + floatval(isset( $packageType['sku'] ) ? $packageType['product_weight'] : 0);
			$packagePrice = floatval(isset( $packageMaterial['sku'] ) ? $packageMaterial['product_cost'] : 0) + floatval(isset( $packageType['sku'] ) ? $packageType['product_cost'] : 0);
		}else{
			if( isset($packageMaterialSku) ){//包材
				$packageMaterial = $productModel->getBySku($packageMaterialSku);
				$packageWeight += floatval(isset( $packageMaterial['sku'] ) ? $packageMaterial['product_weight'] : 0);
				$packagePrice += floatval(isset( $packageMaterial['sku'] ) ? $packageMaterial['product_cost'] : 0);
			}
			if( isset($packageTypeSku) ){//包装
				$packageType = $productModel->getBySku($packageTypeSku);
				$packageWeight += floatval(isset( $packageType['sku'] ) ? $packageType['product_weight'] : 0);
				$packagePrice += floatval(isset( $packageType['sku'] ) ? $packageType['product_cost'] : 0);
			}
		}
		$weight = ceil($weight + $packageWeight);//总重量
		$cost	= $cost + $packagePrice;//总成本
		//将总收入转化为美元
		$rateToUSD = UebModel::model('currencyRate')->getRateByCondition($currency, 'USD');
		$totalUSD = $currency == 'USD' ? floatval($salePrice) : floatval($salePrice) * floatval($rateToUSD);
		//计算发货所需运费（分海外仓和本地仓）
		//检查特殊参数,如国家,属性,仓库等信息
		$shippingInfo = array();
		if( isset( $country ) ) 	$shippingInfo['country'] = $country;//国家
		if( isset( $attributes ) ) 	$shippingInfo['attributes'] = $attributes;//属性
		if( isset( $warehouse ) ) 	$shippingInfo['warehouse'] = $warehouse;//仓库
		if( isset( $shipCode ) ) 	$shippingInfo['shipCode'] = $shipCode;//物流编码
		if( isset( $shipGroup ) ) 	$shippingInfo['shipGroup'] = $shipGroup;//计价方案组
		if( !empty($sku) ){//传递了sku
			$productAttrId = UebModel::model('productAttribute')->getAttributeIdByCode();
			$shippingInfo['attributes'] = UebModel::model('productSelectAttribute')->getAttributeBySku($sku, $productAttrId);//根据sku获取属性值数组
		}
		$shippingCostCal = $this->getMinShippingCost($shippingInfo);//根据系统配置的值决定走哪种类型物流并找到最好的
		//获取销售平台成交费(默认取ebay平台)
		$platformCode = isset( $platformCode ) ? $platformCode : UebModel::model('Platform')->getEbayPlatformCode();
		$platformRate 	= 0;
		//获取汇率(转化为人民币)
		$rateToCNY = UebModel::model('currencyRate')->getRateByCondition($currency, 'HKD') * UebModel::model('currencyRate')->getRateByCondition('HKD', 'CNY');
		
		if( isset($categoryName) ){
			$platformFeeArr = $this->getPlatformFee($platformCode,array(
					'salePrice' 	=> $salePrice,
					'shippingCost' 	=> 0,
					'categoryName' 	=> $categoryName,
					'currency' 		=> $currency,
					'rate' 			=> $rateToCNY,
			));
			$platformRate 	= $platformFeeArr['rate'];
		}
		
		//获取支付平台交易费
		$payPlatform = isset( $payPlatform ) ? $payPlatform : 'Paypal';
		$payFeeArr = $this->getPayPlatformFee($payPlatform, array(
				'totalAmount' 	=> $salePrice,
				'rate' 			=> $rateToCNY,
				'price' 		=> $salePrice,
		));
		$payRate = $payFeeArr['rate'];//支付平台成交费比例
		$payAdd = $payFeeArr['addFee'];//支付平台附加费
		
		/**
		 *	销量利润率 = (销售价-固定成本-销售价*(销售平台手续费比例+支付平台手续费比例))*0.99/销售价
		 *	----> 销售价=0.99*固定成本/((1-(销售平台手续费比例+支付平台手续费比例))*0.99-利润率))
		 *	固定成本 = 产品成本 + 运费成本 + 包装成本 + 包材成本 + 支付平台附加手续费
		 */
		$salePrice = ( 0.99 * ($cost + $shippingCostCal + $payAdd) / ( (1 - $platformRate - $payRate) * 0.99 - $profitRate) ) / $rateToCNY;
		//返回售价
		return ceil( $salePrice * 100 ) / 100;		
	}
	
	/**
	 * 获取指定平台的平台交易费
	 * @param string $platformCode
	 * @return
	 */
	public function getPlatformFee($platformCode, $param = array()){
		return call_user_func_array(array("self",'get'.$platformCode.'Fee'),array());
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
	public function getEBFee( $params = array() ){
		extract($params);
		if( !isset($salePrice) )  throw new Exception( Yii::t('system','Sale Price').Yii::t('system','Is Required') );
		if( !isset($shippingCost) )  throw new Exception( Yii::t('system','Shipping Cost').Yii::t('system','Is Required') );
		if( !isset($categoryName) )  throw new Exception( Yii::t('system','Category Name').Yii::t('system','Is Required') );
		if( !isset($currency) )  throw new Exception( Yii::t('system','Currency').Yii::t('system','Is Required') );
		//if( !isset($rate) )  throw new Exception( Yii::t('system','Rate').Yii::t('system','Is Required') );
		$ebayFee = 0;
		$ebayFee = 0;
		$categoryRule = array();//成交费计算规则
		$categoryName = strtolower($categoryName);//将类名转化为小写
		$categoryArr = explode(':',$categoryName);//转化为数组
		switch ($currency){
			case 'USD':
				$ebayRate = 10/100;
				$ebayFee = ( floatval($salePrice) + floatval($shippingCost) ) * $ebayRate;
				break;
			case 'GBP':
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
							$ebayFee = $salePrice * $details['final_value_rate'];//通过此分类的成交费百分比算出成交费;
							if( $details['max_fee'] && $ebayFee > $details['max_fee'] ){
								$ebayFee = $details['max_fee'];
							}
							$ebayRate = $details['final_value_rate'];//成交费比例
							break 2;
						}
					}
				}
				if(!$hasCategoryRule && !$ebayFee){//没找到类型则为其它类型
					$ebayRate = 10/100;//成交费比例
					$ebayFee = $salePrice * $ebayRate;//其它类型的成交费百分比为10%
				}
				break;
			case 'AUD':
				$ebayRate = 9.5/100;
				$ebayFee = $salePrice * $ebayRate;
				break;
			case 'CAD':
				$ebayRate = 10/100;
				$ebayFee = $salePrice * $ebayRate;
				break;
			case 'EUR':
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
							$ebayFee = $this->calculateFinalValue($salePrice,$details['rule'],$ebayRate);//通过此分类的规则算出成交费;
							break 2;//跳出最外层
						}
					}
				}
				if( !$ebayFee ){//没找到类型则为其它类型
					$ebayFee = $this->calculateFinalValue($salePrice,$otherRule,$ebayRate);
				}
				break;
			default:
				$ebayRate = 10/100;
				$ebayFee = $salePrice * $ebayRate;
				break;
		}
		if( isset($rate) ){//如果有汇率
			$ebayFee = $ebayFee * $rate;//把最终的ebay成交费乘以汇率得到人民币
		}
		return array(
				'fee' 	=> $ebayFee,
				'rate' 	=> round($ebayRate, 2), 
		);	
	}
	
	/**
	 * 根据规则计算成交费用
	 * @param float $fee
	 * @param array $rule
	 * @param number $ebayRate
	 * @return
	 */
	private function calculateFinalValue($fee, $rule, &$ebayRate=0){
		$finalValueFee = 0;
		foreach ($rule as $option) {
			if( $option['start_fee'] <= $fee && ( $option['end_fee'] >= $fee || $option['end_fee']==0 ) ){
				$tempFee = $fee - $option['start_fee'] + 0.01;//在此规则下要算的费用
				$finalValueFee = $tempFee * $option['final_value_rate'];//计算出此费用段要交的成交费
				$ebayRate = $option['final_value_rate'];//成交费比例
					
				$leaveFee = $fee - $tempFee;//此规则剩下的费用
				if( $leaveFee > 0 ){//如果还有剩余，则继续按此规则算成交费
					$finalValueFee += $this->calculateFinalValue($leaveFee,$rule,$ebayRate);
				}
				break;
			}
			if( $option['add_fee'] > 0 ){//如果要加额外费用
				$finalValueFee += $option['add_fee'];
			}
		}
		return $finalValueFee;
	}
	
	/**
	 * 获取指定支付平台的交易费
	 * @param string $payPlatform
	 * @param array $param
	 * @return
	 */
	public function getPayPlatformFee($payPlatform, $param = array()){
		return call_user_func_array(array("self",'get'.$payPlatform.'PayFee'),array($param));
	}
	
	/**
	 * 获取paypal支付平台的交易费
	 * @param array $params
	 * $totalAmount 付款到paypal的金额(包括卖价和运费)
	 * $rate  当前的汇率(转人民币的)
	 * $price 该金额要付的手续费,默认不传则是total的值
	 */
	public function getPaypalPayFee( $params = array() ){
		extract($params);
		if( !isset($totalAmount) )  throw new Exception( Yii::t('system','Price').Yii::t('system','Is Required') );
		if( !isset($price) ){
			$price = $totalAmount;
		}
		if( $totalAmount < 10 ){
			$paypalRate = 0.06;
			$paypalAdd = 0.05;
		}else{
			$paypalRate = 0.032;
			$paypalAdd = 0.3;
		}
		$fee = $price * $paypalRate + $paypalAdd;
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
	 * 根据系统配置选择最佳的物流方式
	 * @param array $params
	 * @throws Exception
	 */
	public function getMinShippingCost( $params = array() ){
		extract($params);
		if( !isset($weight) )  throw new Exception( Yii::t('system','Weight').Yii::t('system','Is Required') );
		//读取系统设置
		static $ghPrice,$expressCost,$expressWeight;
		if( empty($ghPrice) || empty($expressCost) || empty($expressWeight) ){
			$configInfo = UebModel::model('sysConfig')->getConfigCacheByType(UebModel::model('logisticsSet')->getSettingType());
			if( empty($ghPrice) ){
				$ghPrice = $configInfo['gh_price'];//使用挂号的最低金额
			}
			if( empty($expressCost) ){
				$expressCost = $configInfo['express_cost'];//使用快递的采购成本
			}
			if( empty($expressWeight) ){
				$expressWeight = $configInfo['express_weight'];
			}
		}
		unset($params['weight']);
		if( !isset($shipCode) ){
			if( !isset($amount) )  throw new Exception( Yii::t('system','Price').Yii::t('system','Is Required') );//没有传运输方式则需必传金额
			if( !isset($cost) )  $cost = 0;
			if( floatval($cost) > 0 && $cost > $expressCost || $weight > $expressWeight){//走快递
				$shipCode = UebModel::model('logisticsType')->CODE_EXPRESS;
			}elseif(floatval($amount) >= floatval($ghPrice)){//走挂号
				$shipCode = UebModel::model('logisticsType')->CODE_GHXB;
			}else{//走普邮
				$shipCode = UebModel::model('logisticsType')->CODE_COMMON;
			}	
		}
		return UebModel::model('logistics')->getShipFee($shipCode, $weight, $params);
	}
}