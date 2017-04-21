<?php
/**
 * @desc Aliexpress单个sku改价相关
 * @author Liutf
 * @since 2015-09-23
 */
class AliexpresseditpriceController extends UebController{
    
    /**
     * @desc 访问过滤配置
     * @see CController::accessRules()
     */
    public function accessRules() {
        return array(
			array(
				'allow',
				'users' => array('*'),
				'actions' => array('update')
			),
		);
    }
    
    /**
     * 列表
     */
    public function actionList() {
    	$model	= new AliexpressEditPrice();
        $discount = isset($_REQUEST['discount'])?$_REQUEST['discount']:'';
    	$this->render('list', array('model'	=> $model, 'discount'=>$discount));
    }

    /**
     * 改变产品价格
     */
    public function actionUpdate() {
    	
    	$id	= Yii::app()->request->getParam('id');
    	if (Yii::app()->request->isAjaxRequest && !empty($_POST['AliexpressEditPrice']['detail'])) {

            $aliexpressLogUpdatePriceModel = new AliexpressLogUpdatePrice(); 

    		foreach ($_POST['AliexpressEditPrice']['detail'] as $key => $val) {
    			if (empty($val['sku_price'])) {
    				echo $this->failureJson(array(
    					'message' => Yii::t('aliexpress_product', 'Update Price Failure!'),
	    			));
	    			Yii::app()->end();
    			}

                //查询产品子表
                $wheres = "aliexpress_product_id = '{$val['aliexpress_product_id']}' and sku = '{$val['sku']}'";
                $varInfo = AliexpressProductVariation::model()->getOneByCondition('sku_price', $wheres, 'id asc');
                if(!$varInfo){
                    echo $this->failureJson(array(
                        'message' => '子表无数据',
                    ));
                    Yii::app()->end();
                }
                
                //排除海外仓账户 is_overseas_warehouse
                if($val['account_id']){
                    $accountInfo = AliexpressAccount::getAccountInfoById($val['account_id']);
                    if ($accountInfo){
                        if ($accountInfo['is_overseas_warehouse'] == 1){
                            continue;
                        }
                    }
                }

                $time = date('Y-m-d H:i:s');
                $addData = array(
                    'account_id'        => $val['account_id'],
                    'sku'               => $val['sku'],
                    'product_id'        => $val['aliexpress_product_id'],
                    'event'             => 'editprice',
                    'old_price'         => $varInfo['sku_price'],
                    'update_price'      => $val['sku_price'],
                    'status'            => 1,                           
                    'message'           => '改价成功',
                    'start_time'        => $time,
                    'operation_user_id' => (int)Yii::app()->user->id
                );

                //更改aliexpress账号产品价格
				$aliexpressEditPriceModel = new AliexpressEditPrice();
				$flag = $aliexpressEditPriceModel->updateProductsPrice($val['account_id'], $val['aliexpress_product_id'], $val['sku_price'], $val['sku_id']);
				if( $flag ){
						AliexpressEditPrice::model()->updatePrice($val['product_id'], $val['sku_price']);
						AliexpressProductVariation::model()->updatePrice($val['id'], $val['sku_price']);
                        $aliexpressLogUpdatePriceModel->savePrepareLog($addData);  					
				}else{
					$addData['status'] = 0;
                    $addData['message'] = '改价失败:'.$aliexpressEditPriceModel->getExceptionMessage();
                    $aliexpressLogUpdatePriceModel->savePrepareLog($addData); 
				}
    		}

            echo $this->successJson(array('message' => '修改成功'));
            Yii::app()->end();
    	}
    	
    	if (empty($id)) return false;
    	$model = UebModel::model('AliexpressEditPrice')->findByPk($id);
    	if (!empty($model->is_variation)) {
    		$childList = UebModel::model('AliexpressProductVariation')->getByProductId($id); //加入多属性数据
    		$model->detail = !empty($childList) ? $childList : array();
    	}
    	$this->render('_edit_price_form', array('model' => $model));
    }
    

    public function actionBatchchangeprice(){
    	ini_set("display_errors", true);
    	error_reporting(E_ALL);
    	set_time_limit(4*3600);
    	
    	$accountName = Yii::app()->request->getParam("short_name");
    	$runType = Yii::app()->request->getParam("runtype");
    	$testFlag = false;
    	if($runType != 'changeprice2'){
    		$testFlag = true;
    	}
    	$skus = '';
    	if($testFlag){
    		$skus = Yii::app()->request->getParam("sku");
    		if(empty($skus)){
    			exit("Have to specified skus in test mode");
    		}
    		$skus = explode(",", $skus);
    		//$skus = MHelper::simplode($skus);
    	}
    	$accountID = 0;
    	if(empty($accountName)){
    		throw new Exception("No specified account");
    	}
    	try{
    		$acccountInfo = AliexpressAccount::model()->getAccountInfoByShortName($accountName);
    		if(empty($acccountInfo)){
    			throw new Exception("No specified account");
    		}
    		$accountID = $acccountInfo['id'];
    		//第一步取出符合改价的listing （7-14美元）
    		
    		//第二步计算卖价，获取描述
    		// 计算物流方式：重量=毛重+10g   小于等于30g的采用速卖通东莞邮局小包，国家以色列   大于30g采用顺丰立陶宛小包，国家俄罗斯
    		// 价格利润：22%
    		
    		//第三步执行改价操作并做状态记录
    		
    		//排除海外仓账户 is_overseas_warehouse
    		if($accountID){
    		    $accountInfo = AliexpressAccount::getAccountInfoById($accountID);
    		    if ($accountInfo){
    		        if ($accountInfo['is_overseas_warehouse'] == 1){
    		            return false;
    		        }
    		    }
    		}
    		
    		
    		// ===== START ========
    		
    		$filename = "./log/aliexpress/changeprice-{$accountName}.csv";
    		$handle = fopen($filename, "ab");
    		$minPrice = 7;
    		$maxPrice = 14;

    		$aliexpressProductModel = new AliexpressProduct();
    		$aliexpressProductVariationModel = new AliexpressProductVariation();
    		$skuLists = $aliexpressProductModel->getDbConnection()
    											->createCommand()
    											->from($aliexpressProductVariationModel->tableName() . " as v")
    											->select("v.*,p.account_id")
    											->join($aliexpressProductModel->tableName() . " as p", "v.product_id=p.id")
    											->where("p.account_id=:account_id and p.product_status_type=\"onSelling\"", array(":account_id"=>$accountID))
    											->andWhere('v.sku_price between '.$minPrice.' and '.$maxPrice)
    											->andWhere( !$testFlag ? "1" : array("IN", "v.sku", $skus))
    											->andWhere("v.temp_status=0")
    											->queryAll();
    		if(!$skuLists){
    			throw new Exception("no sku list");
    		}
    		echo "<pre>";
    		
    		fputcsv($handle, array(
    			'Time', 'SKU', 'online-sku', 'old-price', 'now-price', 'aliproduct-id', 'status', 'message'
    		));
    		//设置参数值
    		$currency = "USD";
    		foreach ($skuLists as $skuInfo){
    			$sku = $skuInfo['sku'];
    			$priceCal = new CurrencyCalculate();
    			$priceCal->setProfitRate(0.15);//设置利润率
    			$priceCal->setCurrency($currency);//币种
    			$priceCal->setPlatform(Platform::CODE_ALIEXPRESS);//设置销售平台
    			$priceCal->setSku($sku);//设置sku
    			$saleData = $priceCal->calculateAliSalePrice();
    			$reportMsg = array(
    					'time'		=>	date("Y-m-d H:i:s"),
    					'sku'		=>	$sku,
    					'online-sku'	=>	$skuInfo['sku_code'],
    					'old-price'	=>	$skuInfo['sku_price'],
    					'now-price'	=>	isset($saleData['salePrice']) ? $saleData['salePrice'] : 0,
    					'aliproduct-id'	=>	$skuInfo['aliexpress_product_id'],
    					'status'		=>	'success',
    					'message'		=>	'',
    			);
    			if(!$saleData){
    				$reportMsg['status'] = 'fail';
    				$reportMsg['message'] = $priceCal->getErrorMessage();
    				//MHelper::writefilelog("aliexpress/changeprice-{$accountID}.txt", date("Y-m-d H:i:s")." SKU:{$sku} calculateAliSalePrice fail:{$priceCal->getErrorMessage()}; \r\n");
    			}else{
    				
    				/* $message = "";
    				foreach ($saleData as $key=>$val){
    					$message .= "\t\t{$key}=>{$val}	\r\n";
    				} */
    				//$message = date("Y-m-d H:i:s")." SKU:{$sku} Old-price:{$skuInfo['sku_price']}\r\n\t {$message}  update success;  \r\n";
    				//MHelper::writefilelog("aliexpress/changeprice-{$accountID}.txt", $message);
    				
    				//根据产品信息更新库中产品状态
    				$aliexpressEditPriceModel = AliexpressEditPrice::model();
       				$res = $aliexpressEditPriceModel->updateProductsPrice($accountID, $skuInfo['aliexpress_product_id'], $saleData['salePrice'], $skuInfo['sku_id']);
    				//$res = false;
    				if($res){
    					$reportMsg['status'] = 'success';
    					$reportMsg['message'] = "";
    					if(! AliexpressProductVariation::model()->updateVariationById($skuInfo['id'], array('temp_status'=>1, 'sku_price'=>$saleData['salePrice']))){
							$reportMsg['message'] = "Upload Local Failure !!! ";
    					}
    					//同步改价
    					//MHelper::writefilelog("aliexpress/changeprice-{$accountID}.txt", $message);
    				}else{
    					$errMsg = $aliexpressEditPriceModel->getExceptionMessage();
    					//$message = date("Y-m-d H:i:s").",SKU:{$sku} update fail：{$errMsg} \r\n";
    					//MHelper::writefilelog("aliexpress/changeprice-{$accountID}.txt", $message);
    					$reportMsg['status'] = 'fail';
    					$reportMsg['message'] = $errMsg;
    				}
    				
    			}
    			if($testFlag){
    				print_r($reportMsg);
    			}
    			fputcsv($handle, array("done!!!"));
    			fputcsv($handle, $reportMsg);
    		}
    		fclose($handle);
    	}catch (Exception $e){
    		//MHelper::writefilelog("aliexpress/changeprice-{$accountName}.txt", date("Y-m-d H:i:s")." " . $e->getMessage() . " \r\n");
    	}
    }
    
    
    public function actionBatchchangeprice2(){
    	ini_set("display_errors", true);
    	error_reporting(E_ALL);
    	set_time_limit(4*3600);
    	 
    	$accountName = Yii::app()->request->getParam("short_name");
    	$runType = Yii::app()->request->getParam("runtype");
    	$testFlag = false;
    	if($runType != 'changeprice2'){
    		$testFlag = true;
    	}
    	$skus = '';
    	$skus = Yii::app()->request->getParam("sku");
    	if($testFlag){
    		if(empty($skus)){
    			exit("Have to specified skus in test mode");
    		}
    	}
    	$accountID = 0;
    	if(empty($accountName)){
    		//$allowNames = array();
    		$accountNames = Yii::app()->request->getParam("account_names");
    		if(!$accountNames){
    			$accountNames = AliexpressAccount::model()->getIdNamePairs();
    		}else{
    			$accountNames = explode(",", $accountNames);
    		}
    		if($accountNames){
    			foreach ($accountNames as $accountName){
    				//if(!in_array($accountName, $allowNames)) continue;
    				$url = Yii::app()->request->hostInfo."/".$this->route."/short_name/".$accountName."/sku/".$skus."/runtype/".$runType;
    				echo $url,"<br/>";
    				MHelper::runThreadBySocket($url);
    				sleep(2);
    			}
    		}
    	}else{
    		if($testFlag){
    			$skus = explode(",", $skus);
    		}
	    	try{
	    		$acccountInfo = AliexpressAccount::model()->getAccountInfoByShortName($accountName);
	    		if(empty($acccountInfo)){
	    			throw new Exception("No specified account");
	    		}
	    		$accountID = $acccountInfo['id'];
	    		//第一步取出符合改价的listing （7-14美元）
	    
	    		//第二步计算卖价，获取描述
	    		// 计算物流方式：重量=毛重+10g   小于等于30g的采用速卖通东莞邮局小包，国家以色列   大于30g采用顺丰立陶宛小包，国家俄罗斯
	    		// 价格利润：22%
	    
	    		//第三步执行改价操作并做状态记录
	    
	    		//排除海外仓账户 is_overseas_warehouse
	    		if($accountID){
	    		    $accountInfo = AliexpressAccount::getAccountInfoById($accountID);
	    		    if ($accountInfo){
	    		        if ($accountInfo['is_overseas_warehouse'] == 1){
	    		            return false;
	    		        }
	    		    }
	    		}
	    		
	    
	    		// ===== START ========
	    
	    		$filename = "./log/aliexpress/changeprice-{$accountName}.csv";
	    		$handle = fopen($filename, "ab");
	    		$minPrice = 7;
	    		$maxPrice = 14;
	    
	    		$aliexpressProductModel = new AliexpressProduct();
	    		$aliexpressProductVariationModel = new AliexpressProductVariation();
	    		$skuLists = $aliexpressProductModel->getDbConnection()
	    		->createCommand()
	    		->from($aliexpressProductVariationModel->tableName() . " as v")
	    		->select("v.*,p.account_id")
	    		->join($aliexpressProductModel->tableName() . " as p", "v.product_id=p.id")
	    		->where("p.account_id=:account_id and p.product_status_type=\"onSelling\"", array(":account_id"=>$accountID))
	    		->andWhere('v.sku_price between '.$minPrice.' and '.$maxPrice)
	    		->andWhere( !$testFlag ? "1" : array("IN", "v.sku", $skus))
	    		->andWhere("v.temp_status=0")
	    		->queryAll();
	    		if(!$skuLists){
	    			throw new Exception("no sku list");
	    		}
	    		
	    
	    		fputcsv($handle, array('Time', 'SKU', 'online-sku', 'old-price', 'now-price', 'aliproduct-id', 'status', 'message'));
    				//设置参数值
    				$currency = "USD";
    				foreach ($skuLists as $skuInfo){
    					$sku = $skuInfo['sku'];
    					$priceCal = new CurrencyCalculate();
    					$priceCal->setProfitRate(0.15);//设置利润率
    					$priceCal->setCurrency($currency);//币种
    					$priceCal->setPlatform(Platform::CODE_ALIEXPRESS);//设置销售平台
    					$priceCal->setSku($sku);//设置sku
    					$saleData = $priceCal->calculateAliSalePrice();
    					$reportMsg = array(
    							'time'		=>	date("Y-m-d H:i:s"),
    							'sku'		=>	$sku,
    							'online-sku'	=>	$skuInfo['sku_code'],
    							'old-price'	=>	$skuInfo['sku_price'],
    							'now-price'	=>	isset($saleData['salePrice']) ? $saleData['salePrice'] : 0,
    							'aliproduct-id'	=>	$skuInfo['aliexpress_product_id'],
    							'status'		=>	'success',
    							'message'		=>	'',
    					);
    					if(!$saleData){
    						$reportMsg['status'] = 'fail';
    						$reportMsg['message'] = $priceCal->getErrorMessage();
    					}else{
    						//根据产品信息更新库中产品状态
    						$aliexpressEditPriceModel = AliexpressEditPrice::model();
    						$res = $aliexpressEditPriceModel->updateProductsPrice($accountID, $skuInfo['aliexpress_product_id'], $saleData['salePrice'], $skuInfo['sku_id']);
    						//$res = false;
    						if($res){
    							$reportMsg['status'] = 'success';
    							$reportMsg['message'] = "";
    							if(! AliexpressProductVariation::model()->updateVariationById($skuInfo['id'], array('temp_status'=>1, 'sku_price'=>$saleData['salePrice']))){
    								$reportMsg['message'] = " Update Local Failure !!! ";
    							}
    						}else{
    							$errMsg = $aliexpressEditPriceModel->getExceptionMessage();
    							$reportMsg['status'] = 'fail';
    							$reportMsg['message'] = $errMsg;
    						}
    
    					}
    					if($testFlag){
    						echo "<pre>";
    						print_r($reportMsg);
    					}
    					fputcsv($handle, $reportMsg);
    				}
    				fputcsv($handle, array("done!!!"));
    				fclose($handle);
	    	}catch (Exception $e){
	    		//MHelper::writefilelog("aliexpress/changeprice-{$accountName}.txt", date("Y-m-d H:i:s")." " . $e->getMessage() . " \r\n");
	    	}
    	}
    }


    /**
     * 按折扣批量修改价格
     */
    public function actionSetdiscountbatcheditprice(){
        ini_set('memory_limit','2048M');
        set_time_limit(3600);
        ini_set("display_errors", true);
        error_reporting(E_ALL);
        $productId = rtrim(Yii::app()->request->getParam("ids"),',');
        $discount = Yii::app()->request->getParam("discount");
        
        //判断折扣是否为空
        if(empty($discount)) {
            echo $this->failureJson(array('message' => '折扣不许为空'));
            Yii::app()->end();
        }

        if(strpos($discount,'%') != false) {
            echo $this->failureJson(array('message' => '请不要输入%'));
            Yii::app()->end();
        }

        if($discount < 5 || $discount > 50) {
            echo $this->failureJson(array('message' => '折扣在5%-50%之间的数字'));
            Yii::app()->end();
        }

        //判断ID是否为空
        if(empty($productId)) {
            echo $this->failureJson(array('message' => '请选择'));
            Yii::app()->end();
        }

        $aliexpressEditPriceModel           = new AliexpressEditPrice();
        $aliexpressProductVariationModel    = new AliexpressProductVariation();
        $aliexpressProductModel             = new AliexpressProduct();
        $logModel                           = new AliexpressLog();
        $aliexpressLogUpdatePriceModel      = new AliexpressLogUpdatePrice();

        $fields = 'id,account_id,aliexpress_product_id';
        $where = 'id IN('.$productId.')';
        $productInfo = $aliexpressProductModel->getListByCondition($fields,$where);
        if(!$productInfo){
            echo $this->failureJson(array('message'=>'请选择sku'));
            Yii::app()->end();
        }

        $accountID = 1008;

        //更改aliexpress账号产品价格
        $setDiscount = (100-$discount)/100;

        //创建运行日志        
        $logId = $logModel->prepareLog($accountID, AliexpressLog::EVENT_DISCOUNT_SET_PRICE_LOG);
        if(!$logId) {
            echo Yii::t('wish_listing', 'Log create failure');
            Yii::app()->end();
        }
        //检查账号是可以提交请求报告
        $checkRunning = $logModel->checkRunning($accountID, AliexpressLog::EVENT_DISCOUNT_SET_PRICE_LOG);
        if(!$checkRunning){
            $logModel->setFailure($logId, Yii::t('systems', 'There Exists An Active Event'));
            echo Yii::t('systems', 'There Exists An Active Event');
            Yii::app()->end();
        }
        //设置日志为正在运行
        $logModel->setRunning($logId);

        try{
            foreach ($productInfo as $info) {
                $varFields = 'sku_price,sku,id,sku_id';
                $varWhere  = "product_id = '{$info['id']}'";
                $variationInfo = $aliexpressProductVariationModel->getListByCondition($varFields,$varWhere);
                if(!$variationInfo){
                    continue;
                }
                
                //排除海外仓账户 is_overseas_warehouse
                if($info['account_id']){
                    $accountInfo = AliexpressAccount::getAccountInfoById($info['account_id']);
                    if ($accountInfo){
                        if ($accountInfo['is_overseas_warehouse'] == 1){
                            continue;
                        }
                    }
                }
                
                foreach ($variationInfo as $val) {
                    $discountPrice = $val['sku_price'] / round($setDiscount,2);
                    $skuPrice = round($discountPrice,2);
                    if($skuPrice <= 0){
                        continue;
                    }

                    $time = date('Y-m-d H:i:s');
                    $addData = array(
                        'account_id'        => $info['account_id'],
                        'sku'               => $val['sku'],
                        'product_id'        => $info['aliexpress_product_id'],
                        'event'             => 'batcheditprice',
                        'old_price'         => $val['sku_price'],
                        'update_price'      => $skuPrice,
                        'discount'          => $discount,
                        'status'            => 1,                           
                        'message'           => '改价成功',
                        'start_time'        => $time,
                        'operation_user_id' => (int)Yii::app()->user->id
                    );
                    $aliexpressEditPriceModel = new AliexpressEditPrice();
                    //更改aliexpress账号产品价格
                    $flag = $aliexpressEditPriceModel->updateProductsPrice($info['account_id'], $info['aliexpress_product_id'], $skuPrice, $val['sku_id']);
                    //更新日志信息
                    if( $flag ){
                        AliexpressEditPrice::model()->updatePrice($info['id'], $skuPrice);
                        AliexpressProductVariation::model()->updatePrice($val['id'], $skuPrice);
                        $aliexpressLogUpdatePriceModel->savePrepareLog($addData);
                    }else{
                        $addData['status'] = 0;
                        $addData['message'] = '改价失败:'.$aliexpressEditPriceModel->getExceptionMessage();
                        $aliexpressLogUpdatePriceModel->savePrepareLog($addData);            
                    }
                }
            }

            $logModel->setSuccess($logId);

            echo $this->successJson(array('message'=>'success'));
            Yii::app()->end();

        }catch (Exception $e){
            $logModel->setFailure($logId, $e->getMessage());
            echo $this->failureJson(array('message'=>$e->getMessage()));
            Yii::app()->end();
        }    
        
        Yii::app()->end('Finish');    
    }
}