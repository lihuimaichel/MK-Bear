<?php
/**
 * @desc   Aliexpress自动调价监控日志
 * @author AjunLongLive!
 * @since  2017-04-13
 */
class AliexpressproductpricenoticeController extends UebController{
    
    const AVG_PRICE_TYPE      = 1;      //加权平均价
    const PRODUCT_WEIGHT_TYPE = 2;      //产品毛重
    
    const STATUS_WAITING = 0;      //等待处理
    const STATUS_CHANGED = 1;      //标记处理

    /**
     * @desc 访问过滤配置
     * @see CController::accessRules()
     */
    public function accessRules() {
        return array(
			array(
				'allow',
				'users' => array('*'),
				'actions' => array('autochangeproductprice')
			),
		);
    }

    /**
     * @desc 
     * /aliexpress/aliexpressproductpricenotice/list
     */
    public function actionList() {
    	$accountId = Yii::app()->request->getParam('account_id');
    	$Model = new AliexpressProductPriceAutoNotice();
    	$this->render("list", array(
    	    "model"      => $Model, 
    	));
    }
    
    /**
     * @desc 
     * /aliexpress/aliexpressproductpricenotice/batchchangeprice
     */
    public function actionBatchchangeprice() {
    	$ids = Yii::app()->request->getParam("ids");
    	$Model = new AliexpressProductPriceAutoNotice();
    	$allInfo = $Model->findAll("id in ({$ids})");
    	$this->render("edit_price_form", array(
    	    "allInfo"  =>  $allInfo, 
    	));
    }
    
    /**
     * @desc
     * /aliexpress/aliexpressproductpricenotice/batchchangeprice
     */
    public function actionBatchchangepricedo() {
        //exit();
        $reportMsg = "";
        $skuId    = Yii::app()->request->getParam("skuId");
        $skuPrice = Yii::app()->request->getParam("skuPrice");
        foreach ($skuId as $key=>$id){            
            if (!empty($skuPrice[$key])){
                $noticeInfo = AliexpressProductPriceAutoNotice::model()->find(" id={$id} ");
                //$this->print_r($noticeInfo['account_id']);
                //continue;
                //exit();
                //根据产品信息更新库中产品状态
                $childSkuInfo = AliexpressProductVariation::model()->find(" aliexpress_product_id={$noticeInfo['aliexpress_product_id']} ");
                $aliexpressEditPriceModel = AliexpressEditPrice::model();
                $res = $aliexpressEditPriceModel->updateProductsPrice($noticeInfo['account_id'], $noticeInfo['aliexpress_product_id'], trim($skuPrice[$key]), $childSkuInfo['sku_id']);
                //$res = false;
                if($res){  
                    $localUpdate = AliexpressProductVariation::model()->getDbConnection()->createCommand()
                        ->update(AliexpressProductVariation::model()->tableName(), array('sku_price' => trim($skuPrice[$key]))," aliexpress_product_id={$noticeInfo['aliexpress_product_id']} ");
                    if(!$localUpdate){
                        $reportMsg .= AliexpressAccount::model()->getAccountNameById($noticeInfo['account_id']) . " {$noticeInfo['sku']} 本地价格修改失败! <br />\n";
                    } else {
                        $noticeArray = array(
                            'status' => self::STATUS_CHANGED,
                            'change_time' => date('Y-m-d H:i:s'),
                            'change_user_id' => Yii::app()->user->id,
                            'change_now_price' => trim($skuPrice[$key]),
                        );
                        $noticeUpdate = AliexpressProductPriceAutoNotice::model()->getDbConnection()->createCommand()
                            ->update(AliexpressProductPriceAutoNotice::model()->tableName(), $noticeArray," id={$id} ");
                        if (!$noticeUpdate){
                            $reportMsg .= AliexpressAccount::model()->getAccountNameById($noticeInfo['account_id']) . " {$noticeInfo['sku']} 本地调价监控日志修改失败! <br />\n";
                        }
                    }
                } else {
                    $errMsg = $aliexpressEditPriceModel->getExceptionMessage();
                    $reportMsg .= '用户：' . AliexpressAccount::model()->getAccountNameById($noticeInfo['account_id']) . "<br />\n";
                    $reportMsg .= '&nbsp;sku：' . $noticeInfo['sku'] . "<br />\n";
                    $reportMsg .= "错误： 线上价格修改失败!<br />\n";
                    $reportMsg .= "原因： {$errMsg} <br /><br />\n";
                }
            }
        }
        //echo 1;
        if ($reportMsg == "" ) echo $this->successJson(array('message'=>'更新成功'));
        if ($reportMsg != "")  echo $this->failureJson(array('message'=>$reportMsg));
        //$this->failureJson($data);
    }
    
    /**
     * @desc 自动读取成本变动sku，并计算折扣的利润率
     *  /aliexpress/aliexpressproductpricenotice/autodo
     */
    public function actionAutodo() {
        set_time_limit(24*3600);
        //ini_set('display_errors', false);
        //error_reporting(0);
        
    	$costModel = ProductFieldChangeStatistics::model();
    	$dateNow = date('Y-m-d',time());
    	$dateYesterday = date('Y-m-d',time() - 24*3600);
    	//$dateNow = '2017-04-10';
    	$timeNow = date('Y-m-d H:i:s');
    	//$costList = $costModel->getListByCondition('*', " report_time in ('{$dateNow}','{$dateYesterday}') ",'id desc');
    	$costList = $costModel->getListByCondition('*', " report_time='{$dateYesterday}' ",'id desc');
    	//$this->print_r($costList);
    	foreach ($costList as $val){
    	    //$this->print_r($val);    	    
    	    $lastCost = $val['last_field'];
    	    $nowCost  = $val['new_field'];
    	    $sku      = $val['sku'];
    	    
    	    if ((abs($nowCost - $lastCost)/$lastCost > 0.05 && $val['type'] == self::AVG_PRICE_TYPE) || 
    	        (abs($nowCost - $lastCost) > 20 && $val['type'] == self::PRODUCT_WEIGHT_TYPE)) {
    	        $productModel = AliexpressProduct::model();
    	        //$productList = $productModel->getListByCondition('*', "sku='{$sku}'",'id desc');
    	        $productList = $productModel->getDbConnection()->createCommand("
                    SELECT a.account_id,a.online_sku,b.aliexpress_product_id,b.sku_price as product_price,a.category_id,b.sku_code
                    FROM ueb_aliexpress_product a
                    LEFT JOIN ueb_aliexpress_product_variation b
                    ON a.id = b.product_id
                    WHERE b.sku = '{$sku}'
                    ORDER BY b.product_id DESC    	            
    	        ")->queryAll();
    	        //$this->print_r($productList);
    	        foreach ($productList as $productVal){
    	            $productPrice      = $productVal['product_price'];
    	            $productCategoryID = $productVal['category_id'];
    	            $productCurrency   = 'USD';
    	            //根据刊登条件匹配卖价方案 TODO
    	            $productCost = 0;
    	            //$standardProfitRate = 0.18;  //标准利润率
    	            $data = array();
    	        
    	            //获取产品信息
    	            $skuInfo = Product::model()->getProductInfoBySku($sku);
    	            if(!$skuInfo){
    	                echo json_encode($data);
    	                Yii::app()->end();
    	            }
    	        
    	            if($skuInfo['avg_price'] <= 0){
    	                $productCost = $skuInfo['product_cost'];   //加权成本
    	            } else {
    	                $productCost = $skuInfo['avg_price'];      //产品成本
    	            }
    	        
    	            //产品成本转换成美金
    	            $productCost = $productCost / CurrencyRate::model()->getRateToCny($productCurrency);
    	            $productCost = round($productCost,2);
    	            $shipCode    = AliexpressProductAdd::model()->returnShipCode($productCost,$sku);
    	        
    	            //取出佣金
    	            $commissionRate = AliexpressCategoryCommissionRate::getCommissionRate($productCategoryID);
    	        
    	            //计算卖价，获取描述
    	            $priceCal = new CurrencyCalculate();
    	        
    	            //设置运费code
    	            if($shipCode){
    	                $priceCal->setShipCode($shipCode);
    	            }
    	        
    	            //设置价格
    	            if($productPrice){
    	                $priceCal->setSalePrice($productPrice);
    	            }
    	            	
    	            //$priceCal->setProfitRate($standardProfitRate);//设置利润率
    	            $priceCal->setCurrency($productCurrency);//币种
    	            $priceCal->setPlatform(Platform::CODE_ALIEXPRESS);//设置销售平台
    	            $priceCal->setSku($sku);//设置sku
    	            $priceCal->setCommissionRate($commissionRate);//设置佣金比例
    	            $priceCal->setUnionCommission(AliexpressProductAdd::UNION_COMMISSION); //联盟佣金
    	            //$salePrice = $priceCal->getSalePrice();//获取卖价
    	            if($productPrice > 5){
    	                $priceCal2 = new CurrencyCalculate();
    	                $shipCode = AliexpressProductAdd::model()->returnShipCode($productPrice,$sku);
    	                //设置运费code
    	                if($shipCode){
    	                    $priceCal2->setShipCode($shipCode);
    	                }
    	                //设置价格
    	                if($productPrice){
    	                    $priceCal2->setSalePrice($productPrice);
    	                }
    	        
    	                //$priceCal2->setProfitRate($standardProfitRate);//设置利润率
    	                $priceCal2->setCurrency($productCurrency);//币种
    	                $priceCal2->setPlatform(Platform::CODE_ALIEXPRESS);//设置销售平台
    	                $priceCal2->setSku($sku);//设置sku
    	                $priceCal2->setCommissionRate($commissionRate);//设置佣金比例
    	                $priceCal2->setUnionCommission(AliexpressProductAdd::UNION_COMMISSION); //联盟佣金
    	                //标准利润率
    	                $standardProfitRate = $priceCal2->getProfitRate(true);
    	                if ($standardProfitRate <= 0){
    	                    $fiveProfitRate = 0;
    	                    $tenProfitRate = 0;
    	                    $fifteenProfitRate = 0;
    	                    $twentyProfitRate = 0;
    	                    $twentyFiveProfitRate = 0;
    	                    $fiftyProfitRate = 0;
    	                } else {
    	                    //5%折扣利润率
    	                    $priceCal2->setSalePrice($productPrice * 0.95);
    	                    $shipCode = AliexpressProductAdd::model()->returnShipCode($productPrice * 0.95,$sku);
    	                    //设置运费code
    	                    if($shipCode){
    	                        $priceCal2->setShipCode($shipCode);
    	                    }
    	                    $priceCal2->setProfitRate(null);
    	                    $fiveProfitRate = $priceCal2->getProfitRate(true);
    	                    if ($fiveProfitRate <= 0){
    	                        $tenProfitRate = 0;
    	                        $fifteenProfitRate = 0;
    	                        $twentyProfitRate = 0;
    	                        $twentyFiveProfitRate = 0;
    	                        $fiftyProfitRate = 0;
    	                    } else {
    	                        //10%折扣利润率
    	                        $priceCal2->setSalePrice($productPrice * 0.9);
    	                        $shipCode = AliexpressProductAdd::model()->returnShipCode($productPrice * 0.9,$sku);
    	                        //设置运费code
    	                        if($shipCode){
    	                            $priceCal2->setShipCode($shipCode);
    	                        }
    	                        $priceCal2->setProfitRate(null);
    	                        $tenProfitRate = $priceCal2->getProfitRate(true);
    	                        if ($tenProfitRate <= 0){
    	                            $fifteenProfitRate = 0;
    	                            $twentyProfitRate = 0;
    	                            $twentyFiveProfitRate = 0;
    	                            $fiftyProfitRate = 0;
    	                        } else {
    	                            //15%折扣利润率
    	                            $priceCal2->setSalePrice($productPrice * 0.85);
    	                            $shipCode = AliexpressProductAdd::model()->returnShipCode($productPrice * 0.85,$sku);
    	                            //设置运费code
    	                            if($shipCode){
    	                                $priceCal2->setShipCode($shipCode);
    	                            }
    	                            $priceCal2->setProfitRate(null);
    	                            $fifteenProfitRate = $priceCal2->getProfitRate(true);
    	                            if ($fifteenProfitRate <= 0){
    	                                $twentyProfitRate = 0;
    	                                $twentyFiveProfitRate = 0;
    	                                $fiftyProfitRate = 0;
    	                            } else {
    	                                //20%折扣利润率
    	                                $priceCal2->setSalePrice($productPrice * 0.8);
    	                                $shipCode = AliexpressProductAdd::model()->returnShipCode($productPrice * 0.8,$sku);
    	                                //设置运费code
    	                                if($shipCode){
    	                                    $priceCal2->setShipCode($shipCode);
    	                                }
    	                                $priceCal2->setProfitRate(null);
    	                                $twentyProfitRate = $priceCal2->getProfitRate(true);
    	                                if ($twentyProfitRate <= 0){
    	                                    $twentyFiveProfitRate = 0;
    	                                    $fiftyProfitRate = 0;
    	                                } else {
    	                                    //25%折扣利润率
    	                                    $priceCal2->setSalePrice($productPrice * 0.75);
    	                                    $shipCode = AliexpressProductAdd::model()->returnShipCode($productPrice * 0.75,$sku);
    	                                    //设置运费code
    	                                    if($shipCode){
    	                                        $priceCal2->setShipCode($shipCode);
    	                                    }
    	                                    $priceCal2->setProfitRate(null);
    	                                    $twentyFiveProfitRate = $priceCal2->getProfitRate(true);
    	                                    if ($twentyFiveProfitRate <= 0){
    	                                        $fiftyProfitRate = 0;
    	                                    } else {
    	                                        //50%折扣利润率
    	                                        $priceCal2->setSalePrice($productPrice * 0.5);
    	                                        $shipCode = AliexpressProductAdd::model()->returnShipCode($productPrice * 0.5,$sku);
    	                                        //设置运费code
    	                                        if($shipCode){
    	                                            $priceCal2->setShipCode($shipCode);
    	                                        }
    	                                        $priceCal2->setProfitRate(null);
    	                                        $fiftyProfitRate = $priceCal2->getProfitRate(true);
    	                                    }   	                                    
    	                                }    	                                
    	                            }    	                            
    	                        }    	                        
    	                    }    	                    
    	                }
    	                     
    	            } else {
    	                //$data['profitRate']    = $priceCal->getProfitRate(true);//获取利润率
    	            						//标准利润率
    	                $standardProfitRate = $priceCal->getProfitRate(true);
    	                if ($standardProfitRate <= 0){
    	                    $fiveProfitRate = 0;
    	                    $tenProfitRate = 0;
    	                    $fifteenProfitRate = 0;
    	                    $twentyProfitRate = 0;
    	                    $twentyFiveProfitRate = 0;
    	                    $fiftyProfitRate = 0;
    	                } else {
    	                    //5%折扣利润率
    	                    $priceCal->setSalePrice($productPrice * 0.95);
    	                    $priceCal->setProfitRate(null);
    	                    $fiveProfitRate = $priceCal->getProfitRate(true);
    	                    if ($fiveProfitRate <= 0){
    	                        $tenProfitRate = 0;
    	                        $fifteenProfitRate = 0;
    	                        $twentyProfitRate = 0;
    	                        $twentyFiveProfitRate = 0;
    	                        $fiftyProfitRate = 0;
    	                    } else {
    	                        //10%折扣利润率
    	                        $priceCal->setSalePrice($productPrice * 0.9);
    	                        $priceCal->setProfitRate(null);
    	                        $tenProfitRate = $priceCal->getProfitRate(true);
    	                        if ($tenProfitRate <= 0){
    	                            $fifteenProfitRate = 0;
    	                            $twentyProfitRate = 0;
    	                            $twentyFiveProfitRate = 0;
    	                            $fiftyProfitRate = 0;
    	                        } else {
    	                            //15%折扣利润率
    	                            $priceCal->setSalePrice($productPrice * 0.85);
    	                            $priceCal->setProfitRate(null);
    	                            $fifteenProfitRate = $priceCal->getProfitRate(true);
    	                            if ($fifteenProfitRate <= 0){
    	                                $twentyProfitRate = 0;
    	                                $twentyFiveProfitRate = 0;
    	                                $fiftyProfitRate = 0;
    	                            } else {
    	                                //20%折扣利润率
    	                                $priceCal->setSalePrice($productPrice * 0.8);
    	                                $priceCal->setProfitRate(null);
    	                                $twentyProfitRate = $priceCal->getProfitRate(true);
    	                                if ($twentyProfitRate <= 0){
    	                                    $twentyFiveProfitRate = 0;
    	                                    $fiftyProfitRate = 0;
    	                                } else {
    	                                    //25%折扣利润率
    	                                    $priceCal->setSalePrice($productPrice * 0.75);
    	                                    $priceCal->setProfitRate(null);
    	                                    $twentyFiveProfitRate = $priceCal->getProfitRate(true);
    	                                    if ($twentyFiveProfitRate <= 0){
    	                                        $fiftyProfitRate = 0;
    	                                    } else {
    	                                        //50%折扣利润率
    	                                        $priceCal->setSalePrice($productPrice * 0.5);
    	                                        $priceCal->setProfitRate(null);
    	                                        $fiftyProfitRate = $priceCal->getProfitRate(true);
    	                                    }   	                                    
    	                                }    	                                
    	                            }    	                            
    	                        }    	                        
    	                    }    	                    
    	                }
    	            }
    	             
    	            //加权
    	            if ($val['type'] == self::AVG_PRICE_TYPE){
    	                $lastAvgPrice = $lastCost;
    	                $nowAvgPrice  = $nowCost;
    	                $lastWeight = 0.0;
    	                $nowWeight  = 0.0;
    	            }
    	            
    	            //毛重
    	            if ($val['type'] == self::PRODUCT_WEIGHT_TYPE){
    	                $lastAvgPrice = 0.0;
    	                $nowAvgPrice  = 0.0;
    	                $lastWeight = $lastCost;
    	                $nowWeight  = $nowCost;
    	            }
    	            
    	            $sellerUserList = User::model()->getPairs();
    	            $productSellerRelationInfo = AliexpressProductSellerRelation::model()
    	                                           ->getProductSellerRelationInfoByItemIdandSKU($productVal['aliexpress_product_id'], $sku, $productVal['sku_code']);
    	            $sellerName = $productSellerRelationInfo && isset($sellerUserList[$productSellerRelationInfo['seller_id']]) ? $sellerUserList[$productSellerRelationInfo['seller_id']] : '-';
    	            
    	            $insertArray = array();
    	            $insertArray['sku'] = $sku;
    	            $insertArray['online_sku'] = $productVal['online_sku'];
    	            $insertArray['aliexpress_product_id'] = $productVal['aliexpress_product_id'];
    	            $insertArray['account_id'] = $productVal['account_id'];
    	            $insertArray['category_id'] = $productVal['category_id'];
    	            $insertArray['seller_name'] = $sellerName;
    	            $insertArray['last_avg_price'] = $lastAvgPrice;
    	            $insertArray['now_avg_price']  = $nowAvgPrice;
    	            $insertArray['last_weight']    = $lastWeight;
    	            $insertArray['now_weight']     = $nowWeight;
    	            $insertArray['standard_price'] = $productPrice;
    	            $insertArray['standard_profit_rate'] = 0.1;
    	            $insertArray['status'] = self::STATUS_WAITING;
    	            $insertArray['log_date'] = $dateNow;
    	            $insertArray['log_type'] = $val['type'];
    	            $insertArray['change_time'] = null;
    	            $insertArray['change_user_id'] = null;
    	            $insertArray['change_now_price'] = null;
    	            $insertArray['five_profit_rate'] = round($fiveProfitRate,4);
    	            $insertArray['ten_profit_rate'] = round($tenProfitRate,4);
    	            $insertArray['fifteen_profit_rate'] = round($fifteenProfitRate,4);
    	            $insertArray['twenty_profit_rate'] = round($twentyProfitRate,4);
    	            $insertArray['twenty_five_profit_rate'] = round($twentyFiveProfitRate,4);
    	            $insertArray['fifty_profit_rate'] = round($fiftyProfitRate,4);
    	            $insertArray['creat_time'] = $timeNow;
                    //$this->print_r($insertArray);
                    ///*
                    //$_REQUEST['debug'] = 1;
                    $updateModel = AliexpressProductPriceAutoNotice::model()
                    ->getDbConnection()
                    ->createCommand()
                    ->insert(AliexpressProductPriceAutoNotice::model()->tableName(), $insertArray);
                    if ($updateModel){
                        echo "{$sku} {$productVal['account_id']} {$productVal['aliexpress_product_id']} 插入数据成功! <br />";
                    } else {
                        echo "{$sku} {$productVal['account_id']} {$productVal['aliexpress_product_id']} 插入数据失败! <br />";
                    }
                    //*/
    	        }
    	    }    
    	    
    	}
    }
    
    /**
     * @desc 拉取单个产品
     */
    public function actionGetproduct() {
    	$accountID = Yii::app()->request->getParam('account_id');
    	$productStatusType = Yii::app()->request->getParam('product_status_type');
    	$productID = Yii::app()->request->getParam('product_id');
    	if (empty($accountID) || empty($productStatusType) || empty($productID)) {
    		echo $this->failureJson(array(
    			'message' => Yii::t('aliexpress', 'Params Error'),
    		));
    		Yii::app()->end();
    	}
    	//拉取产品
    	$aliexpressProductModel = new AliexpressProduct();
    	$aliexpressProductModel->setAccountID($accountID);
    	$params['product_status_type'] = $productStatusType;
    	$params['product_id'] = $productID;
    	$flag = $aliexpressProductModel->getAccountProducts($params);
    	if ($flag) {
    		echo $this->successJson(array(
    			'message' => Yii::t('aliexpress', 'Get Product Success'),
    		));
    		Yii::app()->end();
    	} else {
    		echo $this->successJson(array(
    				'message' => $aliexpressProductModel->getErrorMessage(),
    		));
    		Yii::app()->end();
    	}
    }
	
    
}