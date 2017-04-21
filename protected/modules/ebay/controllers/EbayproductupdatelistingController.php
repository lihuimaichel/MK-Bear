<?php
/**
 * @desc Ebay修改线上listing
 * @author lihy
 * @since 2016-01-28
 */
class EbayproductupdatelistingController extends UebController{
	/**
	 * @desc 被过滤掉的账号ID
	 * @var unknown
	 */
	private $_filterAccountIDs = array('3','5','9','10','11','12','16','19','21','22','23','24','27', '29', '42','46','48','49','51','53','56','63','64', '66', //普通账号
   								'26','32','33',
   								'13','34','37','39','54','55','57','59','60','62'//海外仓账号
   								);
   
	/**
	 * @desc 线上多属性gtin（UPC/ISBN/EAN）修改默认值（Does not apply）
	 */
   	public function actionUpdatevariationgtin(){
   		set_time_limit(3600);
   		ini_set("display_errors", true);
   		$accountID = Yii::app()->request->getParam('account_id');
   		$testItemID = Yii::app()->request->getParam('item_id');//测试
   		if($accountID){
   			$reviseFixedPriceItemRequest = new ReviseFixedPriceItemRequest();
   			$ebayProductModel = new EbayProduct();
   			$ebayProductVariantModel = new EbayProductVariation();
   			$ebayLogModel = new EbayLog();
   			$defaultGTIN = "Does not apply";
   			$eventName = EbayProduct::EVENT_NAME_UPDATE;
   			$logID = $ebayLogModel->prepareLog($accountID, $eventName);
   			if($logID){
   				//检测是否能允许运行
   				if(!$ebayLogModel->checkRunning($accountID, $eventName)){
   					$ebayLogModel->setFailure($logID, Yii::t('system', 'There Exists An Active Event'));
   					exit;
   				}
   				
   				$startTime = date("Y-m-d H:i:s");
   				// 设置日志的状态为正在运行
   				$ebayLogModel->setRunning($logID);
   				$limit = 200;
   				$offset = 0;
   				$reviseFixedPriceItemRequest->setAccount($accountID);
   				$message = "";
   				do{
   					$command = $ebayProductModel->getDbConnection()->createCommand()
						   					->from($ebayProductModel->tableName())
						   					->select("item_id")
						   					->where("account_id={$accountID}")
						   					->andWhere("is_multiple=1 and gtin_update_status=0")
						   					->limit($limit, $offset);
   					if($testItemID){
   						$command->andWhere("item_id='{$testItemID}'");//测试
   					}
   					$mainlisting = $command->queryAll();
   					$offset += $limit;
   					
   					$itemIDs = array();
   					if($mainlisting){
   						$flag = true;//继续循环
   						foreach ($mainlisting as $list){
   							$itemIDs[] = $list['item_id'];
   						}
   						unset($mainlisting);
   						$listing = $ebayProductVariantModel->getDbConnection()->createCommand()
				   						->from($ebayProductVariantModel->tableName() )
				   						->select("sku, sku_online, account_id, quantity, item_id")
				   						->where(array("IN", "item_id", $itemIDs))
				   						->andWhere("account_id={$accountID}")
				   						->queryAll();
   						
   						if($listing){
   							$variationListing = array();
   							foreach ($listing as $variation){
   								$variationListing[$variation['item_id']][] = $variation;
   							}
   							unset($listing);
   							foreach ($variationListing as $itemID=>$variations){
   								$reviseFixedPriceItemRequest->setItemID($itemID);
   								foreach ($variations as $variation){
	   								$reviseFixedPriceItemRequest->setVariation(array(
	   												"SKU"=>$variation['sku_online'],
	   												"VariationProductListingDetails"=>array(
	   													"EAN"=>$defaultGTIN,
	   													"UPC"=>$defaultGTIN,
	   													"ISBN"=>$defaultGTIN,
	   													"GTIN"=>$defaultGTIN
	   												)
	   										)
	   								);
   								}
   								$response = $reviseFixedPriceItemRequest->setRequest()->sendRequest()->getResponse();
   								if($testItemID){
   									echo "<pre>";
   									print_r($response);
   									echo "</pre>";
   								}
   								$reviseFixedPriceItemRequest->clean();
   								if($reviseFixedPriceItemRequest->getIfSuccess()){
   									//更新
   									$ebayProductModel->getDbConnection()->createCommand()->update($ebayProductModel->tableName(), array('gtin_update_status'=>1), "item_id=".$itemID);
   								}else{
   									$message .= " ".$itemID.":".$reviseFixedPriceItemRequest->getErrorMsg();
   								}
   							}
   							unset($variationListing);
   						}else {
   							echo "no found sku";
   						}
   					}else{
   						$flag = false;//循环结束
   						echo "no found main sku";
   					}
   				}while ($flag);
   				// 插入本次log参数日志(用来记录请求的参数)
   				$eventLog = $ebayLogModel->saveEventLog(
   						$eventName,
   						array(
   								'log_id'     => $logID,
   								'account_id' => $accountID,
   								'start_time' => $startTime,
   								'end_time'   => date("Y-m-d H:i:s"),
   						)
   				);
   				$ebayLogModel->setSuccess($logID, $message);
   				$ebayLogModel->saveEventStatus($eventName,$eventLog,EbayLog::STATUS_SUCCESS);
   			}
   		}else{
   			$ebayAccounts = EbayAccount::model()->getAbleAccountList();
   			foreach($ebayAccounts as $account){
   				MHelper::runThreadSOCKET('/'.$this->route.'/account_id/'.$account['id']);
   				sleep(1);
   			}
   		}
   	}
   	
   	public function actionUpdateproductgtin(){
   		set_time_limit(3*3600);
   		ini_set("display_errors", true);
   		$accountID = Yii::app()->request->getParam('account_id');
   		$testItemID = Yii::app()->request->getParam('item_id');//测试
   		if($accountID){
   			$reviseFixedPriceItemRequest = new ReviseFixedPriceItemRequest();
   			$getItemRequest = new GetItemRequest();
   			$ebayProductModel = new EbayProduct();
   			$ebayProductVariantModel = new EbayProductVariation();
   			$ebayLogModel = new EbayLog();
   			$defaultGTIN = "Does not apply";
   			$eventName = EbayProduct::EVENT_NAME_UPDATE;
   			$logID = $ebayLogModel->prepareLog($accountID, $eventName);
   			if($logID){
   				//检测是否能允许运行
   				if(!$ebayLogModel->checkRunning($accountID, $eventName)){
   					$ebayLogModel->setFailure($logID, Yii::t('system', 'There Exists An Active Event'));
   					echo 'There Exists An Active Event';
   					exit;
   				}
   					
   				$startTime = date("Y-m-d H:i:s");
   				// 设置日志的状态为正在运行
   				$ebayLogModel->setRunning($logID);
   				$limit = 100;
   				$offset = 0;
   				$reviseFixedPriceItemRequest->setAccount($accountID);
   				$message = "";
   				do{
   					$command = $ebayProductModel->getDbConnection()->createCommand()
							   					->from($ebayProductModel->tableName())
							   					->select("item_id, sku_online, is_multiple")
							   					->where("account_id={$accountID}")
							   					//->andWhere("is_multiple=0")
							   					->andWhere("gtin_update_status=0")
							   					->andWhere("item_status=1")//在线
							   					->andWhere("listing_type in('FixedPriceItem', 'StoresFixedPrice')")//非拍卖下
							   					->limit($limit, $offset);
   					if($testItemID){
   						$command->andWhere("item_id='{$testItemID}'");//测试
   					}
   					$mainlisting = $command->queryAll();
   					$offset += $limit;
   				
   					$itemIDs = array();
   					if($mainlisting){
   						$flag = true;//继续循环
   						if($testItemID){
   							$flag = false;
   						}
   						foreach ($mainlisting as $item){
   							$reviseFixedPriceItemRequest->setItemID($item['item_id']);
   							$responseItem = $getItemRequest->setAccount($accountID)->setItemID($item['item_id'])->setIncludeSpecifics(true)->setRequest()->sendRequest()->getResponse();
   							if(!$getItemRequest->getIfSuccess()) continue;
   							//获取原始的itemspecific
   							$orginItemSpecifics = array(
   									
   							);
   							$originAttr = array(
   									'EAN'	=>	$defaultGTIN,
   									'ISBN'	=>	$defaultGTIN,
   									'UPC'	=>	$defaultGTIN,
   									'GTIN'	=>	$defaultGTIN
   							);
   							//$this->print_r($responseItem->Item->ItemSpecifics);
   							if(isset($responseItem->Item->ItemSpecifics)){
   								foreach ($responseItem->Item->ItemSpecifics->NameValueList as $itemspec){
   									if($itemspec->Source == 'ItemSpecific')
   										$orginItemSpecifics[htmlspecialchars((string)$itemspec->Name)] = htmlspecialchars((string)$itemspec->Value);
   									else{
   										$originAttr[(string)$itemspec->Name] = htmlentities((string)$itemspec->Value);
   									}
   								}
   							}
   							//var_dump($orginItemSpecifics);
   							//exit();
   							//$this->print_r($responseItem);
   							//exit('xxxxxxxxxx');
   							$addItemspec = array(
   								'Brand'=>'Unbranded/Generic',
   								'MPN'=>'Does Not Apply',
   							);
   							
   							$addItemspec = array_merge($addItemspec, $orginItemSpecifics);
   							//$this->print_r($addItemspec);
   							$reviseFixedPriceItemRequest->setItemBrand($addItemspec['Brand']);
   							$reviseFixedPriceItemRequest->setItemMPN($addItemspec['MPN']);
   							$reviseFixedPriceItemRequest->setItemSpecifics($addItemspec);
   							if($item['is_multiple'] == 0){
   								$reviseFixedPriceItemRequest->setItemEAN($originAttr['EAN']);
   								$reviseFixedPriceItemRequest->setItemISBN($originAttr['ISBN']);
   								$reviseFixedPriceItemRequest->setItemUPC($originAttr['UPC']);
   								$reviseFixedPriceItemRequest->setItemGTIN($originAttr['GTIN']);
   								//if($accountID == 28 || $accountID == 29)
   								$reviseFixedPriceItemRequest->setItemQuantity(200);
   							}
   							//判断是否多属性
   							if($item['is_multiple'] == 1){
	   							$listing = $ebayProductVariantModel->getDbConnection()->createCommand()
							   							->from($ebayProductVariantModel->tableName() )
							   							->select("sku, sku_online, account_id, quantity, item_id")
							   							->where("item_id='{$item['item_id']}'")
							   							->andWhere("account_id={$accountID}")
							   							->queryAll();
	   							if($listing){
		   							foreach ($listing as $variation){
		   								$variationData = array(
		   										"SKU"=>$variation['sku_online'],
		   										"VariationProductListingDetails"=>array(
		   												"EAN"=>$defaultGTIN,
		   												"UPC"=>$defaultGTIN,
		   												"ISBN"=>$defaultGTIN,
		   												"GTIN"=>$defaultGTIN
		   										),
		   										'Quantity'=>200
		   										
		   								);
		   								/* if($accountID == 28 || $accountID == 29){
		   									$variationData['Quantity'] = 200;
		   								} */
		   								/* if($variation['variation_specifics']){
		   									$variationSpecifics = json_decode($variation['variation_specifics']);
		   									if($variationSpecifics){
		   										foreach ($variationSpecifics as $name=>$value){
		   											$variationData['VariationSpecifics']['NameValueList'][] = array('Name'=>$name, 'Value'=>$value);
		   										}
		   									}
		   								} */
		   								$reviseFixedPriceItemRequest->setVariation($variationData);
		   							}
	   							}
   							}
   							$response = $reviseFixedPriceItemRequest->setRequest()->sendRequest()->getResponse();
   							if($testItemID){
   								echo "<pre>";
   								print_r($response);
   								echo "</pre>";
   							}
   							$reviseFixedPriceItemRequest->clean();
   							if($reviseFixedPriceItemRequest->getIfSuccess()){
   								//更新
   								$ebayProductModel->getDbConnection()->createCommand()->update($ebayProductModel->tableName(), array('gtin_update_status'=>1), "item_id=".$item['item_id']);
   							}else{
   								$message .= " ".$item['item_id'].":".$reviseFixedPriceItemRequest->getErrorMsg();
   							}
   						}
   						unset($variationListing);
   						
   					}else{
   						$flag = false;//循环结束
   						echo "no found main sku";
   					}
   					sleep(3);
   				}while ($flag);
   				// 插入本次log参数日志(用来记录请求的参数)
   				$eventLog = $ebayLogModel->saveEventLog(
   						$eventName,
   						array(
   								'log_id'     => $logID,
   								'account_id' => $accountID,
   								'start_time' => $startTime,
   								'end_time'   => date("Y-m-d H:i:s"),
   						)
   				);
   				$ebayLogModel->setSuccess($logID, $message);
   				$ebayLogModel->saveEventStatus($eventName,$eventLog,EbayLog::STATUS_SUCCESS);
   			}
   		}else{
   			$ebayAccounts = EbayAccount::model()->getAbleAccountList();
   			foreach($ebayAccounts as $account){
   				MHelper::runThreadSOCKET('/'.$this->route.'/account_id/'.$account['id']);
   				sleep(1);
   			}
   		}
   	}
   	
   	/**
   	 * @desc 重新计算lisitng价格
   	 * @link /ebay/ebayproductupdatelisting/recalculateprice/account_id/xx/limit/xx/item_id/xx
   	 */
   	public function actionRecalculateprice(){
   		exit("停止");
   		set_time_limit(3600);
   		ini_set("display_errors", true);
   		error_reporting(E_ALL);
   		ini_set("memory_limit", "256M");
   		
   		
   		$filterAccountIDs = $this->_filterAccountIDs;
   		$accountID = Yii::app()->request->getParam("account_id");
   		$limit = Yii::app()->request->getParam("limit");
   		$itemID = Yii::app()->request->getParam("item_id");
   		$bug = Yii::app()->request->getParam("bug");
   		$allowAccountRun = array(25, 47, 7 ,40);//2017-02-20 新增S6帐号
   		if($accountID){
   			echo "<pre>";
   			if(in_array($accountID, $filterAccountIDs)){
   				exit("No Allow Account");
   			}
			if(! in_array($accountID, $allowAccountRun)){
				exit('No Allow Account2');
			}
			//指定了帐号运行  qzz
			if($accountID != 40){
				exit("No 40");
			}
   			//日志
   			$ebayLogModel = new EbayLog();
   			$eventName = "recalculate_price";
   			
   			$logID = $ebayLogModel->prepareLog($accountID, $eventName);
   			if(!$logID){
   				exit("Log create failure");
   			}
   			if(!$ebayLogModel->checkRunning($accountID, $eventName)){
   				$ebayLogModel->setFailure($logID, "Exists Event Running");
   				exit("Exists Event Running");
   			}
   			/* if(! in_array($accountID, $allowAccountRun)){
   				$ebayLogModel->setFailure($logID, "NO RUN THIS ACCOUNT");
   				exit('xx');
   			} */
   			$ebayLogModel->setRunning($logID);
   			try{
	   			$flag = true;
	   			$variantListing = array();
	   			$ebayProductModel = new EbayProduct();
	   			$ebayProductVariantModel = new EbayProductVariation();
	   			$ebayCategory = new EbayCategory();
	   			$ebayProductShippingModel = new EbayProductShipping();
	   			$ebayrecalPriceLogModel = new EbayRecalPriceLog();
	   			$ebayProductVariantExtendModel = new EbayProductVariationExtend();
	   			if(!$limit) $limit = 1000;
	   			//先入库
	   			
	   			$sql = "insert INTO ueb_ebay_product_variation_extend(item_id,sku) SELECT v.item_id,v.sku from ueb_ebay_product_variation v
	   			LEFT JOIN ueb_ebay_product p on p.id=v.listing_id 
	   			LEFT JOIN ueb_ebay_product_variation_extend ve on ve.item_id=v.item_id and ve.sku=v.sku 
	   			WHERE p.listing_type in('FixedPriceItem', 'StoresFixedPrice') and p.item_status=".EbayProduct::STATUS_ONLINE ." and  ISNULL(ve.id) and v.sku<>'' and  v.account_id={$accountID} limit 60000;";
	   			$ebayProductVariantModel->getDbConnection()->createCommand($sql)->execute();
	   			if($bug){
	   				echo "<br>====SQL:{$sql}=======<br/>";
	   			}
	   			do{
		   			$command = $ebayProductVariantModel->getDbConnection()->createCommand()
						   			->from($ebayProductVariantModel->tableName() . " as t")
						   			->leftJoin($ebayProductModel->tableName() . " p", "p.id=t.listing_id")
						   			->leftJoin($ebayProductVariantExtendModel->tableName() . " ve", "ve.item_id=t.item_id and ve.sku=t.sku")
						   			->select("t.id, t.sku, t.sku_online, t.sale_price, t.account_id, t.quantity, t.item_id, t.currency, p.site_id, p.is_multiple, p.category_id,p.category_name,p.shipping_price")
						   			->where('p.item_status='.EbayProduct::STATUS_ONLINE)
									->andWhere("t.account_id='{$accountID}'")
						   			->andWhere("p.listing_type in('FixedPriceItem', 'StoresFixedPrice')")//非拍卖下
						   			//->andWhere("t.status1=0")
						   			->andWhere("t.sku<>''")
						   			->andWhere("ve.status1=0");
		   			if($itemID){
		   				$command->andWhere(array("in", "t.item_id", $itemID));
		   			}
		   			if($limit){
		   				$command->limit($limit);
		   			}
		   			$variantListing = $command->queryAll();
		   			if($bug){
		   				echo "<br>====SQL:{$command->text}=======<br/>";
		   				echo "<br/>=======variantListing======<br/>";
	   					print_r($variantListing);
		   			}
		   			if($variantListing){
		   				$flag = true;
		   				$ebaySalePriceModel = new EbayProductSalePriceConfig;
		   				foreach ($variantListing as $listing){
		   					$itemID2 = $listing['item_id'];
		   					$sku = $listing['sku'];
		   					$siteID = $listing['site_id'];
		   					//$currency = EbaySite::getCurrencyBySiteID($listing['site_id']);
		   					$currency = $listing['currency'];
		   					$categoryID = $listing['category_id'];
		   					//分类名称
		   					//$categoryName = $ebayCategory->getCategoryNameByID($categoryID, $siteID);
		   					$categoryNames = explode(":", $listing['category_name']);
		   					$categoryName = array_pop($categoryNames);
		   					$salePriceData = $ebaySalePriceModel->getSalePriceNew($sku, $currency, $siteID, $accountID, $categoryName);
		   					if($bug){
		   						echo "<br>=========salePriceData:=========<br/>";
		   						print_r($salePriceData);
		   					}
		   					if($salePriceData){
		   						$salePriceInfo = $salePriceData['xx4-1'];
		   						$salePriceInfo['salePrice'] = $salePriceData['salePrice'];
		   						$ebayrecalPriceLogModel->saveLogDataByCalcalprice($salePriceInfo, $itemID2, $sku);
		   					}
		   					//获取运费
		   					//$shippingPrice = intval($listing['shipping_price']);
		   					$shippingPrice = $ebayProductShippingModel->getMiniShippingPriceByItemID($itemID2);
		   					$updateData = array();
		   					if(!$salePriceData || !$salePriceData['salePrice'] || $salePriceData['profit'] < 0.01 ){ //重新计算失败
		   						$updateData = array('status1'=>2);
		   					}else{//计算成功
		   						$updateData = array(
		   								'status1'=>1,
		   								'new_price'	=>	round($salePriceData['salePrice']-$shippingPrice, 2)
		   						);
		   					}
		   					$updateData['item_id'] 	= $itemID2;
		   					$updateData['sku']		=	$sku;
		   					
		   					$res = $ebayProductVariantExtendModel->addOrUpdate($updateData);
		   					if($bug){
		   						echo "<br>=========updateData:==== res : {$res} =====<br/>";
		   						print_r($updateData);
		   					}
		   					/* $ebayProductVariantModel->getDbConnection()->createCommand()->update($ebayProductVariantModel->tableName(),
		   							$updateData,
		   							"id='{$listing['id']}'"); */
		   					
		   				}
		   			} else {
		   				$flag = false;
		   			}
		   			if($bug){
		   				$flag = false;
		   			}
	   			}while ($flag);
   				$ebayLogModel->setSuccess($logID, "done");
   			}catch (Exception $e){
   				$ebayLogModel->setFailure($logID, $e->getMessage());
   			}
   		}else{
   			//@todo
   			$ebayAccounts = EbayAccount::model()->getAbleAccountList();
   			//排除帐号
   			foreach($ebayAccounts as $account){
   				if(in_array($account['id'], $filterAccountIDs)){
   					continue;
   				}
   				MHelper::runThreadSOCKET('/'.$this->route.'/account_id/'.$account['id']);
   				sleep(1);
   			}
   		}
   	}
   	
   	/**
   	 * @desc 批量更改价格
   	 * @link /ebay/ebayproductupdatelisting/batchchangeprice/account_id/xx/limit/xx/item_id/xx
   	 */
   	public function actionBatchchangeprice(){
   		exit("停止");
   		set_time_limit(7200);
   		ini_set("display_errors", true);
   		error_reporting(E_ALL);
   		ini_set("memory_limit", "256M");
   		
   		$filterAccountIDs = $this->_filterAccountIDs;
   		$accountID = Yii::app()->request->getParam("account_id");
   		$limit = Yii::app()->request->getParam("limit");
   		$itemID = Yii::app()->request->getParam("item_id");
   		$bug = Yii::app()->request->getParam("bug");
   		//'7','8','14','15','17','18','20','25','28','30','31','35','38','41','43','44','45','47','52','58','61','65','66','67'
   		//'J','H','A','F','K','P','R','H3','H9','S2','A3','H1','H4','Y','Z','1','2','4','9','15','20','21','22','23'
   		$allowAccountRun = array(25, 47, 7, 40); //2017-02-20 新增S6帐号
   		if($accountID){
   			echo "<pre>";
   			if(in_array($accountID, $filterAccountIDs)){
   				exit("No Allow Account");
   			}
			if(! in_array($accountID, $allowAccountRun)){
				exit('No Allow Account2');
			}
			//指定了帐号运行  qzz
			if($accountID != 40){
				exit("No 40");
			}
   			//日志
   			$ebayLogModel = new EbayLog();
   			$eventName = "batch_change_price";
   			
   			$logID = $ebayLogModel->prepareLog($accountID, $eventName);
   			if(!$logID){
   				exit("Log create failure");
   			}
   			if(!$ebayLogModel->checkRunning($accountID, $eventName)){
   				$ebayLogModel->setFailure($logID, "Exists Event Running");
   				exit("Exists Event Running");
   			}
   			/* if(! in_array($accountID, $allowAccountRun)){
   				$ebayLogModel->setFailure($logID, "NO RUN THIS ACCOUNT");
   				exit('xx');
   			} */
   			$ebayLogModel->setRunning($logID);
   			try{
	   			$flag = true;
	   			$variantListing = array();
	   			$ebayProductModel = new EbayProduct();
	   			$ebayProductVariantModel = new EbayProductVariation();
	   			$ebayCategory = new EbayCategory();
	   			$ebayProductChangePriceLogModel = new EbayProductChangePriceLog();
	   			$ebayProductVariantExtendModel = new EbayProductVariationExtend();
	   			if(!$limit) $limit = 1000;
	   			do{
	   				$command = $ebayProductVariantModel->getDbConnection()->createCommand()
					   				->from($ebayProductVariantModel->tableName() . " as t")
					   				->leftJoin($ebayProductModel->tableName() . " p", "p.id=t.listing_id")
					   				->leftJoin($ebayProductVariantExtendModel->tableName() . " ve", "ve.item_id=t.item_id and ve.sku=t.sku")
					   				->select("t.id, t.sku, t.sku_online, t.sale_price, t.account_id, t.quantity, t.item_id, t.currency, p.site_id, p.is_multiple, ve.new_price")
					   				->where('p.item_status='.EbayProduct::STATUS_ONLINE)
					   				->andWhere("t.account_id='{$accountID}'")
					   				->andWhere("p.listing_type in('FixedPriceItem', 'StoresFixedPrice')")//非拍卖下
					   				//->andWhere("t.status2=0 and t.status1=1 and t.new_price>t.sale_price")
	   								->andWhere("ve.status2=0 ")
	   								->andWhere("t.sku<>'' ")
	   								->andWhere("ve.status1=1 and ve.new_price>t.sale_price");
	   				if($itemID){
	   					$command->andWhere(array("in", "t.item_id", $itemID));
	   				}
	   				if($limit){
	   					$command->limit($limit);
	   				}
	   				$variantListing = $command->queryAll();
	   				if($bug){
	   					echo "<br>====SQL:{$command->text}=======<br/>";
	   					echo "<br/>=======variantListing======<br/>";
	   					print_r($variantListing);
	   				}
	   				if($variantListing){
	   					$flag = true;
	   					foreach ($variantListing as $listing){
	   						
	   						$newPrice = $listing['new_price'];
	   						$skuOnline = $listing['sku_online'];
	   						$itemID = $listing['item_id'];
	   						$sku = $listing['sku'];
	   						
	   						//检查是否已经运行过改价
	   						$checkExists = $ebayProductChangePriceLogModel->getDbConnection()
	   												->createCommand()->select("id,status")->where("item_id='{$itemID}' and account_id='{$accountID}' and sku_online='{$skuOnline}' and (status=1 or status=3)")
	   												->from($ebayProductChangePriceLogModel->tableName())->queryRow();
	   						if($checkExists){
	   							/* $updateResult = $ebayProductVariantModel->getDbConnection()
					   							->createCommand()
					   							->update($ebayProductVariantModel->tableName(), array("status2" => 1), "id=".$listing['id']); */
	   							$updateData = array(
	   									'item_id'	=>	$itemID,
	   									'sku'		=>	$sku,
	   									'status2'	=>	$checkExists['status']
	   							);
	   							$updateResult = $ebayProductVariantExtendModel->addOrUpdate($updateData);
	   							continue;
	   						}
	   						//获取sku
	   						$productInfo = Product::model()->getProductBySku($sku, 'product_status');
	   						$isStopSale = $productInfo ? ($productInfo['product_status'] == Product::STATUS_STOP_SELLING) : true;
	   						if(!$isStopSale){
	   							//@todo 执行改价操作
	   							$reviseInventoryStatusRequest = new ReviseInventoryStatusRequest();
	   							$reviseInventoryStatusRequest->setAccount($accountID);
	   							$reviseInventoryStatusRequest->setSku($skuOnline);
	   							$reviseInventoryStatusRequest->setItemID($itemID);
	   							$reviseInventoryStatusRequest->setStartPrice($newPrice);
	   							$reviseInventoryStatusRequest->push();
	   							$response = null;
	   							$response = $reviseInventoryStatusRequest->setRequest()->sendRequest()->getResponse();
	   							$reviseInventoryStatusRequest->clean();
	   							//收集错误信息
	   							$errormsg = $reviseInventoryStatusRequest->getErrorMsg();
	   							//$errormsg = "test";
	   							//写入记录表
	   							if(!$reviseInventoryStatusRequest->getIfSuccess() && $bug){
	   								echo "<br/>";
	   								echo $err = date("Y-m-d H:i:s")."-".$listing['item_id'].'---old:'.$listing['sale_price'].'---new:'.$newPrice.'---'. $errormsg;
	   								echo "<br/>";
	   							}
	   							if ($bug) {
	   								echo "<pre>";
	   								echo "<br/>====== {$skuOnline}  {$itemID} =======:<br/>";
	   								print_r($response);
	   								echo "</pre>";
	   							}
	   							
	   							$updateStatus = 0;
	   							if(isset($response->Fees) && $response->Fees){
		   							$feedItemIDs = array();
		   							if(!isset($response->Fees[0])){
		   								$feedItemIDs[] = $response->Fees->ItemID;
		   							}else{//返回多个
		   								foreach ($response->Fees as $feed){
		   									$feedItemIDs[] = $feed->ItemID;
		   								}
		   							}
		   							if(in_array($listing['item_id'], $feedItemIDs)){
		   								$updateStatus = 1;
		   							}else{
		   								$updateStatus = 2;
		   							}
		   						}else{
		   								$updateStatus = 2;
		   						}
	   						}else{
	   							//如果为停售
	   							$updateStatus = 3;//
	   							$errormsg = "stop sale Or sku error";
	   						}
	   						
	   						/* $updateResult = $ebayProductVariantModel->getDbConnection()
	   												->createCommand()
	   												->update($ebayProductVariantModel->tableName(), array("status2" => $updateStatus), "id=".$listing['id']); */
	   						$updateData = array(
	   											'item_id'	=>	$itemID,
	   											'sku'		=>	$sku,
	   											'status2'	=>	$updateStatus
	   										);
	   						$updateResult = $ebayProductVariantExtendModel->addOrUpdate($updateData);
	   						if($bug){
	   							echo "<br>======updateResult:======<br/>";
	   							var_dump($updateResult);
	   						}
	   						$logData = array(
	   								'account_id'	=>	$accountID,
	   								'item_id'		=>	$listing['item_id'],
	   								'sku'			=>	$listing['sku'],
	   								'sku_online'	=>	$listing['sku_online'],
	   								'old_price'		=>	$listing['sale_price'],
	   								'site_id'		=>	$listing['site_id'],
	   								'new_price'		=>	$newPrice,
	   								'status'		=>	$updateStatus,
	   								'message'		=>	is_null($errormsg) ? '' : $errormsg,
	   								'create_user_id'=>  intval(Yii::app()->user->id),
	   								'create_time'	=>	date("Y-m-d H:i:s")
	   						);
	   						
	   						//@todo 入库
	   						$ebayProductChangePriceLogModel->addData($logData);
						}
	   				} else {
	   					$flag = false;
	   				}
	   				if($bug){
	   					$flag = false;
	   				}
	   			}while ($flag);
	   			$ebayLogModel->setSuccess($logID, "done");
   			}catch (Exception $e){
   				$ebayLogModel->setFailure($logID, $e->getMessage());
   			}
   		}else{
   			//@todo
   			$ebayAccounts = EbayAccount::model()->getAbleAccountList();
   			//排除帐号
   			
   			foreach($ebayAccounts as $account){
   				if(in_array($account['id'], $filterAccountIDs)){
   					continue;
   				}
   				MHelper::runThreadSOCKET('/'.$this->route.'/account_id/'.$account['id']);
   				sleep(1);
   			}
   		}
   	}

	/*
	 * 临时使用 /ebay/ebayproductupdatelisting/RepairPrice/item_id/302165270738/sku/122539.03
	 * qzz 2017-02-21
	 */
	public function actionRepairPrice()
	{
		exit("no allow");
		set_time_limit(7200);
		ini_set("display_errors", true);
		error_reporting(E_ALL);
		ini_set("memory_limit", "256M");

		$accountID = Yii::app()->request->getParam("account_id");
		$sku = Yii::app()->request->getParam("sku");
		$itemID = Yii::app()->request->getParam("item_id");
		$limit = Yii::app()->request->getParam("limit", 1000);

		$ebayProductChangePriceLogModel = new EbayProductChangePriceLog();

		do{
			$command = $ebayProductChangePriceLogModel->getDbConnection()->createCommand()
				->select('t.*')
				->from($ebayProductChangePriceLogModel->tableName() . " as t")
				->where('t.status=1')
				->andWhere("t.account_id<>40")
				->andWhere("t.create_time > '2017-02-20 15:25:00'");

			if($sku){
				$command->andWhere("t.sku = '{$sku}'");
			}
			if($itemID){
				$command->andWhere("t.item_id = '{$itemID}'");
			}
			if ($limit) {
				$command->limit($limit);
			}

			$listing = $command->queryAll();
			if($listing){
				$flag = true;
				foreach($listing as $val){
					//更新
					$reviseInventoryStatusRequest = new ReviseInventoryStatusRequest();
					$reviseInventoryStatusRequest->setAccount($val['account_id']);
					$reviseInventoryStatusRequest->setSku($val['sku_online']);
					$reviseInventoryStatusRequest->setItemID($val['item_id']);
					$reviseInventoryStatusRequest->setStartPrice($val['old_price']);
					$reviseInventoryStatusRequest->push();
					$response = null;
					$response = $reviseInventoryStatusRequest->setRequest()->sendRequest()->getResponse();
					$reviseInventoryStatusRequest->clean();
					//收集错误信息
					$errormsg = $reviseInventoryStatusRequest->getErrorMsg();
					if(!$reviseInventoryStatusRequest->getIfSuccess()){
						//失败为7
						$ebayProductChangePriceLogModel->getDbConnection()->createCommand()
							->update($ebayProductChangePriceLogModel->tableName(), array("status" => 7,"message"=>is_null($errormsg) ? '' : $errormsg), "id=".$val['id']);
					}else{
						//成功为6
						$ebayProductChangePriceLogModel->getDbConnection()->createCommand()
							->update($ebayProductChangePriceLogModel->tableName(), array("status" => 6), "id=".$val['id']);
					}
				}
			}else{
				$flag = false;
			}
		}while($flag);
	}
} 