<?php
/**
 * @desc 用来做Joom请求接口使用
 * @author lihy
 *
 */
class JoomapirequestController extends UebController{
	/**
	 * @desc 设置访问权限
	 * @see CController::accessRules()
	 */
	public function accessRules(){
		return array(
				array(
						'allow',
						'users'=>'*',
						'actions'=>array('getallproduct', 'getlisting')
					),
				
		);
	}
	public function actionCreateencrysku(){
		$sku = Yii::app()->request->getParam('sku');
		$encrySku = new encryptSku();
		$ensku = $encrySku->getEncryptSku($sku);
		echo "sku:",$sku,"<br/>";
		echo "Ensku:",$ensku,"<br/>";
	}
	/**
	 * @desc 获取单个产品信息
	 */
	public function actionGetproduct(){
		set_time_limit(3600);
		$sku = Yii::app()->request->getParam('sku');
		$accountID = Yii::app()->request->getParam('account_id');
		if($sku && $accountID){
			$retrieveProductsRequest = new RetrieveProductsRequest();
			$response = $retrieveProductsRequest->setParentSku($sku)->setAccount($accountID)
									->setRequest()
									->sendRequest()
									->getResponse();
			echo "Product:<br/>";
			echo "<pre>";
			print_r($response);
			echo "</pre>";
			if ($retrieveProductsRequest->getIfSuccess() && !empty($response)) {
					$datas = $response->data;
					$datas = array($datas);
					$joomListing = new JoomListing();
					$joomListing->setAccountID($accountID);
					$joomListing->saveJoomListing($datas);
					echo $joomListing->getExceptionMessage();
					echo "get sku:{$sku} success";
			}else{
				echo $retrieveProductsRequest->getErrorMsg() , '<br/>';
			}
		}else{
			echo "Sku OR Account ID invalid";
		}
		
	}
	/**
	 * @desc 拉取产品列表
	 */
	public function actionGetlisting() {
		set_time_limit(4*3600);
        ini_set('memory_limit','2048M');
		ini_set('display_errors', true);
		error_reporting(E_ALL);
		$accountID = Yii::app()->request->getParam('account_id');
		if ($accountID) {
			//创建日志
			$joomLog = new JoomLog();
			$logID = $joomLog->prepareLog($accountID, JoomListing::EVENT_NAME);
			if ($logID) {
				//检查账号是否可以拉取
				$checkRunning = $joomLog->checkRunning($accountID, JoomListing::EVENT_NAME);
				if (!$checkRunning) {
					$joomLog->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
				} else {
					//插入本次log参数日志(用来记录请求的参数)
					$time = date('Y-m-d H:i:s');
					$eventLogID = $joomLog->saveEventLog(JoomListing::EVENT_NAME, array(
							'log_id' => $logID,
							'account_id' => $accountID,
							'start_time'    => $time,
							'end_time'      => $time,
					));
					//设置日志正在运行
					$joomLog->setRunning($logID);
					//$joomLog->setFailure($logID);
                   	$joomListing = new JoomListing();
					$flag = $joomListing->getAccountListing($accountID);
	                if($flag){
	                     $joomLog->setSuccess($logID);
	                     $joomLog->saveEventStatus(JoomListing::EVENT_NAME, $eventLogID, JoomLog::STATUS_SUCCESS);
	                } else {
	                     $joomLog->setFailure($logID, $joomListing->getExceptionMessage());
	                     $joomLog->saveEventStatus(JoomListing::EVENT_NAME, $eventLogID, JoomLog::STATUS_FAILURE);
	                }
				}
			}
		} else {
			$accountList = JoomAccount::model()->getCronGroupAccounts();
			//循环每个账号发送一个拉listing的请求
			foreach ($accountList as $accountID) {
				MHelper::runThreadSOCKET('/'.$this->route.'/account_id/' . $accountID);
				sleep(1);
			}
		}
	}
	/**
	 * @desc 获取所有产品列表
	 */
	public function actionGetallproduct(){
		set_time_limit(5*3600);
		ini_set('display_errors', true);
		error_reporting(E_ALL);
		$accountID = Yii::app()->request->getParam('account_id');
		if ($accountID) {
			$hasflish = false;
			$index = 0;
			//$request->setLimit(30);
			while (!$hasflish) {
				$request = new ListAllProductsRequest();
				$request->setAccount($accountID);
				$request->setStartIndex($index);
				$index++;
				$response = $request->setRequest()->sendRequest()->getResponse();
			
				if (!empty($response)) {
					$datas = $response->data;
					$joomListing = new JoomListing();
					$joomListing->setAccountID($accountID);
					$joomListing->saveJoomListing($datas);
					if (!isset($response->paging->next) || empty($response->paging->next))
						$hasflish = true;
				} else {
					$hasflish = true;
				}
				unset($response);
			}
		} else {
			$accountList = JoomAccount::getAbleAccountList();
			//循环每个账号发送一个拉listing的请求
			foreach ($accountList as $accountInfo) {
				$path = $this->createUrl('getlisting', array('account_id' => $accountInfo['id']));
				$header = "GET " . $path . $accountInfo['id'] . " HTTP\1.0\r\n";
				$header .= "Host: " . $_SERVER['HTTP_HOST'] . "\r\n";
				$header .= "Pragma: no-cache" . "\r\n";
				$header .= "Connection: Close\r\n\r\n";
				$fp = fsockopen($_SERVER['HTTP_HOST'], 80, $errno, $error, 300);
				if ($fp) {
					fwrite($fp, $header, strlen($header));
				}
				while (!feof($fp)) {
					echo fgets($fp, 1024);
				}
				fclose($fp);
			}
		}
	}
	/**
	 * @desc 获取所有的产品变种列表
	 */
	public function actionGetallproductvariant(){
		
	}
	
	
	public function actionRefreshaccounttoken(){
		$accountId = Yii::app()->request->getParam('account_id');
		if($accountId){
			$accountList = array(array('id'=>$accountId, 'account_name'=>'xx'));
		}else{
			$accountList = JoomAccount::getAbleAccountList();
		}
		$joomAccount = new JoomAccount();
		$refreshTokenRequest = new RefreshTokenRequest();
		foreach ($accountList as $account){
			$refreshTokenRequest->setAccount($account['id']);
			$response = $refreshTokenRequest->setRequest()->sendRequest()->getResponse();
			if($refreshTokenRequest->getIfSuccess()){
				//更新
				$updata = array('access_token'=>$response->data->access_token,
								'refresh_token'=>$response->data->refresh_token,
								'token_expired_time'=>$response->data->expiry_time
								);
				if($joomAccount->updateByPk($account['id'], $updata)){
					echo "{$account['account_name']} refresh success\n<br/>";
				}
			}else{
				echo $refreshTokenRequest->getErrorMsg(), '<br/>';
				echo "fail:{$account['account_name']}\n<br/>";
			}
		}
	}
	
	
	/**
	 * @desc 上传产品
	 * @throws Exception
	 * @link /joom/joomapirequest/uploadproduct/account_id/xx/add_type/3/limit/10/add_id/xxx
	 */
	public function actionUploadproduct(){
       	$begin_time = time();
        set_time_limit(5*3600);

        ini_set('memory_limit','256M');
        ini_set('display_errors', true);      
        $account_id = Yii::app()->request->getParam('account_id');
        $limit = Yii::app()->request->getParam('limit', 1000);
        $addId = Yii::app()->request->getParam('add_id');
        $addType = Yii::app()->request->getParam('add_type');
        if(!$limit) $limit = 1000;
        if(!$account_id){
                $account_id = 0;
        }
        $flag = true;
        // while($flag){
        //     $exe_time   =  time();
        //     if(($exe_time - $begin_time) >= 18000 ){
        //        exit('执行超过5小时');
        // }
        /**@ 获取产品信息*/
        // $config = ConfigFactory::getConfig('serverKeys');
		try{

			$joomProductAddModel = new JoomProductAdd;

			//获取未成功上传和上传次数小于10的主sku
			$pendingUploadProducts = $joomProductAddModel->getAllPendingUploadProduct($limit, "id,account_id", $account_id, $addId, $addType);

			if(empty($pendingUploadProducts)){
                $flag = false;
				throw new Exception('No data upload');
            }
			$newUploadProducts = array();
			foreach ($pendingUploadProducts as $product){
				$newUploadProducts[$product['account_id']][] = $product;
			}
			$total = count($pendingUploadProducts);
			unset($pendingUploadProducts);
			$successNum = 0;
			foreach ($newUploadProducts as $accountId=>$product){
				$joomLog = new JoomLog;
				//创建运行日志
				$logId = $joomLog->prepareLog($accountId,  JoomProductAdd::EVENT_UPLOAD_PRODUCT);
				if(!$logId) continue;
				//检查账号是可以提交请求报告
				$checkRunning = $joomLog->checkRunning($accountId, JoomProductAdd::EVENT_UPLOAD_PRODUCT);
				if(!$checkRunning){
                                        $flag = false;
					$joomLog->setFailure($logId, Yii::t('systems', 'There Exists An Active Event'));
					continue;
				}
				//插入本次log参数日志(用来记录请求的参数)
				$eventLog = $joomLog->saveEventLog(JoomProductAdd::EVENT_UPLOAD_PRODUCT, array(
						'log_id'        => $logId,
						'account_id'    => $accountId,
						'start_time'    => date('Y-m-d H:i:s'),
						'end_time'      => date('Y-m-d H:i:s'),
				));
				//设置日志为正在运行
				$joomLog->setRunning($logId);
				foreach ($product as $data){
                                    
				/*	$data = $joomProductAddModel->getProductAddInfo("id=:id AND upload_status<>:upload_status", array(':id'=>$data['id'], ':upload_status'=>JoomProductAdd::JOOM_UPLOAD_SUCCESS));
                    if(!$data) continue;
                    if(empty($data['tags'])) continue;*/
                    $res = $joomProductAddModel->uploadProduct($data['id']);
                    if($res){
                    	$successNum++;
                    }
					
				}
				$joomLog->setSuccess($logId);
				$joomLog->saveEventStatus(JoomProductAdd::EVENT_UPLOAD_PRODUCT, $eventLog, JoomLog::STATUS_SUCCESS);
			}
			if($successNum > 0){
				echo "total:$total, success: $successNum";
			}else {
                                //$flag = false;
				//throw new Exception('All item Failure!');
                        }
		}catch (Exception $e){
			//echo "failure:".$e->getMessage();
		}
              //  exit;//测试
            // }
	}
	
	/**
	 * @desc 上传变种产品
	 */
	public function actionUploadproductvariant(){
		exit("use upload product");
		set_time_limit(2*3600);
		ini_set('display_errors', true);
		error_reporting(E_ALL);
		$limit = Yii::app()->request->getParam("limit", 50);
		if(!$limit) $limit = 50;
		$account_id = Yii::app()->request->getParam('account_id');
		$addId = Yii::app()->request->getParam('add_id');
		if(!$account_id){
			$account_id = 0;
		}
		/**@ 获取产品信息*/
		$config = ConfigFactory::getConfig('serverKeys');
		try{
			$joomProductVariantAddModel = new JoomProductVariantsAdd;
			
			$joomProductAddModel = new JoomProductAdd;
			$pendingUploadVariants = $joomProductVariantAddModel->getPendingUploadVariants($limit, "v.id", $account_id, $addId);
			$createProductVariantRequest = new CreateProductVariantRequest();
			if(empty($pendingUploadVariants))
				throw new Exception('No pending upload');
			$productInfo = array();
			$successNum = 0;
			$total = count($pendingUploadVariants);
			foreach ($pendingUploadVariants as $variant){
				$variant = $joomProductVariantAddModel->getJoomProductVariantsAddInfo("id=:id AND upload_status<>:upload_status", array(":id"=>$variant['id'], ":upload_status"=>JoomProductAdd::JOOM_UPLOAD_SUCCESS));
				if(empty($variant)) continue;
				try{
					$time = date("Y-m-d H:i:s");
					$logId = 0;
					//获取父级数据
					if(!isset($productInfo[$variant['add_id']])){
						$productInfo[$variant['add_id']] = $joomProductAddModel->getProductAddInfo('id=:id', array(':id'=>$variant['add_id']));
					}
					$parentSkuInfo = $productInfo[$variant['add_id']];
					if(empty($parentSkuInfo)){
						throw new Exception('Parent sku no exists');
					}
					//检测父级是否已经上传成功
					if($parentSkuInfo['upload_status'] != JoomProductAdd::JOOM_UPLOAD_SUCCESS){
						continue;
						//throw new Exception('Parent sku no upload success');
					}
					$joomLog = new JoomLog;
					$accountId = $parentSkuInfo['account_id'];
					//1.检查账号是可以提交请求报告
					$checkRunning = $joomLog->checkRunning($accountId, JoomProductAdd::EVENT_UPLOAD_PRODUCT);
					if(!$checkRunning){
						continue;
					}
					//创建运行日志
					$logId = $joomLog->prepareLog($accountId,  JoomProductAdd::EVENT_UPLOAD_PRODUCT);
					if(!$logId) continue;
					//插入本次log参数日志(用来记录请求的参数)
					$eventLog = $joomLog->saveEventLog(JoomProductAdd::EVENT_UPLOAD_PRODUCT, array(
							'log_id'        => $logId,
							'account_id'    => $accountId,
							'start_time'    => date('Y-m-d H:i:s'),
							'end_time'      => date('Y-m-d H:i:s'),
					));
					//设置日志为正在运行
					$joomLog->setRunning($logId);
					
					$createProductVariantRequest->setAccount($accountId);
					$data = array(
							'parent_sku'	=>	$parentSkuInfo['online_sku'],
							'sku'			=>	$variant['online_sku'],
							'inventory'		=>	$variant['inventory'],
							'price'			=>	$variant['price'],
							'shipping'		=>	$variant['shipping'],
							'msrp'			=>	$variant['msrp'],
							'size'			=>	$variant['size'],
							'color'			=>	$variant['color'],
							'shipping_time'	=>	$variant['shipping_time']
							//'main_image'	=>	$variant['main_image']
					);
					/* if(empty($variant['main_image'])){
						//图片
						$images = Product::model()->getImgList($variant['sku'],'ft');
						foreach($images as $k=>$img){
							$variant ['main_image'] = $config['oms']['host'].$img;
							break;
						}
					} */
					if($variant['main_image'] && $variant['remote_main_img']){
						$data['main_image'] = $variant['remote_main_img'];
					}elseif($variant['main_image']){
						//$remoteImgUrl = $joomProductAddModel->uploadImageToServer($variant['main_image'], $accountId);
						$remoteImgUrl = $joomProductAddModel->getRemoteImgPathByName($variant['main_image'], $accountId);
						if(!$remoteImgUrl){
							throw new Exception($joomProductAddModel->getErrorMsg());
						}
						$joomProductVariantsAddModel->updateProductVariantAddInfoByPk($variant['id'], array(
								'remote_main_img'=>$remoteImgUrl
						));
						$data['main_image'] = $remoteImgUrl;
					}
					$createProductVariantRequest->setUploadData($data);
					$response = $createProductVariantRequest->setRequest()->sendRequest()->getResponse();
					if($createProductVariantRequest->getIfSuccess()){
						$successNum++;
						$joomProductVariantAddModel->updateProductVariantAddInfoByPk($variant['id'], array(
								'last_upload_time'=>$time,
								'upload_status'=>JoomProductAdd::JOOM_UPLOAD_SUCCESS,
								'upload_times'=>1+$variant['upload_times'],
								'last_upload_msg'=>'success'
						));
						
						$joomLog->setSuccess($logId);
						$joomLog->saveEventStatus(JoomProductAdd::EVENT_UPLOAD_PRODUCT, $eventLog, JoomLog::STATUS_SUCCESS);
					}else{
						throw new Exception($createProductVariantRequest->getErrorMsg());
					}
				}catch (Exception $e){
					if($logId>0){
						$joomLog->setFailure($logId, $e->getMessage());
						$joomLog->saveEventStatus(JoomProductAdd::EVENT_UPLOAD_PRODUCT, $eventLog, JoomLog::STATUS_FAILURE);
					}
					$joomProductVariantAddModel->updateProductVariantAddInfoByPk($variant['id'], array(
							'last_upload_time'=>$time,
							'upload_status'=>JoomProductAdd::JOOM_UPLOAD_FAIL,
							'upload_times'=>1+$variant['upload_times'],
							'last_upload_msg'=>$e->getMessage()
					));
				}
			}
			echo "total:",$total,"success:",$successNum;
		}catch (Exception $e){
			echo 'Failure:',$e->getMessage();
		}
	}
	
	
	/**
	 * @desc 检测仓库中的sku库存数量，从而自动更改平台上的库存数量为0
	 * @link /joom/joomapirequest/autochangestockfornostock/sku/99676/accountID/1
	 */
	public function actionAutochangestockfornostock(){
		ini_set('memory_limit','2048M');
        set_time_limit(4*3600);
        ini_set("display_errors", true);

        $warehouseSkuMapModel 	 = new WarehouseSkuMap();
        $joomProductModel 		 = new JoomProduct();
		$joomProductVariantModel = new JoomVariants();
		$joomZeroStockSKUModel   = new JoomZeroStockSku();
        $logModel 				 = new JoomLog();

        //账号
        $accountID = Yii::app()->request->getParam('accountID');

        //指定某个特定sku----用于测试
        $setSku = Yii::app()->request->getParam('sku');

        $select = 't.sku';
        $type = 0;
        $eventName = JoomZeroStockSku::EVENT_ZERO_STOCK;
        $limit = 200;
        $offset = 0;

        /**------------春节库存调0程序，2017-02-06  可以移除此代码  开始-------------**/
        $times = time();
        $oneTwenty = strtotime('2017-01-20 00:00:00');
        $twoSix = strtotime('2017-02-15 00:00:00');
        /**------------春节库存调0程序，2017-02-06  可以移除此代码  结束-------------**/

        //屏蔽改0的程序
        if($times >= $twoSix){
        	exit;
        }

        if($accountID){
            try{
                //写log
                $logID = $logModel->prepareLog($accountID, $eventName);
                if(!$logID){
                    exit('日志写入错误');
                }
                //检测是否可以允许
                if(!$logModel->checkRunning($accountID, $eventName)){
                    $logModel->setFailure($logID, Yii::t('system', 'There Exists An Active Event'));
                    exit('There Exists An Active Event');
                }

                //设置运行
                $logModel->setRunning($logID);

                do{
                    $command = $joomProductVariantModel->getDbConnection()->createCommand()
                        ->from($joomProductVariantModel->tableName() . " as t")
                        ->leftJoin($joomProductModel->tableName()." as p", "p.id=t.listing_id")
                        ->select("t.sku, t.account_id, t.online_sku, t.inventory as product_stock, t.variation_product_id")
                        ->where('p.account_id = '.$accountID)
                        ->andWhere("t.inventory>0")
                        ->andWhere("t.enabled=1")
                        ->andWhere("p.is_promoted=0");
                        if($setSku){
                            $command->andWhere("t.sku = '".$setSku."'");
                        }
                    $command->limit($limit, $offset);
                    $variantListing = $command->queryAll(); 
                    $offset += $limit;
                    if(!$variantListing){
                        break;
                        // exit("此账号无数据");
                    }

                    $skuArr = array();
                    $skuListArr = array();
                    foreach ($variantListing as $listingValue) {
                        $skuArr[] = $listingValue['sku'];
                    }

                    if ($times < $twoSix) {
                        $conditions = 't.available_qty <= IFNULL(s.day_sale_num,0) 
                                    AND t.warehouse_id = :warehouse_id 
                                    AND p.product_is_multi IN(0,1)  
                                    AND p.product_status NOT IN(6,7) 
                                    AND t.sku IN('.MHelper::simplode($skuArr).')';
                        $param = array(':warehouse_id'=>WarehouseSkuMap::WARE_HOUSE_GM);
                        $skuList = $warehouseSkuMapModel->getSkuListLeftJoinProductAndSalesByCondition($conditions, $param, '', $select);
                        $type = 6;
                    }else{
                        $conditions = 't.available_qty <= :available_qty 
                                    AND t.warehouse_id = :warehouse_id 
                                    AND p.product_is_multi IN(0,1) 
                                    AND p.product_status IN(6,7)  
                                    AND t.sku IN('.MHelper::simplode($skuArr).')';
                        $param = array(':available_qty'=>1, ':warehouse_id'=>WarehouseSkuMap::WARE_HOUSE_GM);
                        $skuList = $warehouseSkuMapModel->getSkuListLeftJoinProductByCondition($conditions, $param, '', $select);
                    }
                    /**------------春节库存调0程序，2017-02-06  可以移除此代码  结束-------------**/

                    // $limits = "{$offset},{$limit}";
                    if(!$skuList){
                        continue;            
                    } 
                    unset($skuArr);

                    foreach ($skuList as $skuVal){
                        $skuListArr[] = $skuVal['sku'];
                    }  

                    foreach ($variantListing as $variant){
                        if(!in_array($variant['sku'], $skuListArr)){
                            continue;
                        }

                        //检测是否已经运行了
                        if($joomZeroStockSKUModel->checkHadRunningForDay($variant['sku'], $accountID, $variant['variation_product_id'])){
                            continue;
                        }

                        if(isset($variant['product_stock']) && $variant['product_stock'] < 1){
							$logModel->setFailure($logID, Yii::t('system', 'There Exists An Active Event'));
							continue;
						}
						$msg = '';
                        $updateInventoryRequest = new UpdateInventoryRequest;
                        $updateInventoryRequest->setSku($variant['online_sku']);
						$updateInventoryRequest->setInventory(0);
						$updateInventoryRequest->setEnabled(true);
                        $updateInventoryRequest->setAccount($accountID);
						$response = $updateInventoryRequest->setRequest()->sendRequest()->getResponse();
						$message = "";
						if($updateInventoryRequest->getIfSuccess()){
							$status = 2;//成功
						}else{
							$status = 3;//失败
							$message = " accountID:{$accountID},  sku: {$list['online_sku']}" . $updateInventoryRequest->getErrorMsg();
							$msg .= $message;
						}
                        $time = date("Y-m-d H:i:s");
                        $addData = array(
                                'product_id'=>  $variant['variation_product_id'],
                                'seller_sku'=>  $variant['online_sku'],
                                'sku'       =>  $variant['sku'],
                                'account_id'=>  $variant['account_id'],
                                'site_id'   =>  0,
                                'old_quantity'=>$variant['product_stock'],
                                'status'    =>  $status,
                                'msg'       =>  $message,
                                'create_time'=> $time,
                                'type'      =>  $type
                        );

                        $zeroStockCondition = 'seller_sku = :seller_sku AND product_id = :product_id AND status = 2 AND type = '.$type.' AND is_restore = 0';
                        $zeroStockParam = array(':seller_sku'=>$variant['online_sku'], ':product_id'=>$variant['variation_product_id']);
                        $existsInfo = $joomZeroStockSKUModel->getZeroSkuOneByCondition($zeroStockCondition,$zeroStockParam);
                        if($existsInfo){
                            continue;
                        }else{
                            $joomZeroStockSKUModel->saveData($addData);
                        }
                    }

                }while($variantListing);     
                $logModel->setSuccess($logID, "success");

            }catch (Exception $e){
                if(isset($logID) && $logID){
                    $logModel->setFailure($logID, $e->getMessage());
                }
                echo $e->getMessage()."<br/>";
            }
        }else{
            $accountList = JoomAccount::model()->getIdNamePairs();
            foreach($accountList as $key => $value){
                MHelper::runThreadSOCKET('/'.$this->route.'/accountID/' . $key);
                sleep(10);
            }
        }
	}
	
	/**
	 * @desc 恢复从自动置为0的sku的库存
	 */
	public function actionRestoreskustockfromzerostocksku(){
		set_time_limit(3*3600);
		ini_set('display_errors', true);
		error_reporting(E_ALL);
		
		$accountID = Yii::app()->request->getParam('account_id');
		$type = Yii::app()->request->getParam('type');
		if(!$type) $type = 1;//强制设置，后续改
		if($accountID){
			$time = date("Y-m-d H:i:s");
			/**------------春节库存调0程序，2017-02-06  可以移除此代码  开始-------------**/
	        $times = time();
	        $oneTwenty = strtotime('2017-01-20 00:00:00');
	        $twoSix = strtotime('2017-02-15 00:00:00');
	        /**------------春节库存调0程序，2017-02-06  可以移除此代码  结束-------------**/
			//写log
			$logModel = new JoomLog();
			$eventName = JoomZeroStockSku::EVENT_RESTORE_STOCK;
			$logID = $logModel->prepareLog($accountID, $eventName);
			if(!$logID){
				exit('Create Log Failure');
			}
			//检测是否可以允许
			if(!$logModel->checkRunning($accountID, $eventName)){
				$logModel->setFailure($logID, Yii::t('system', 'There Exists An Active Event'));
				echo "There Exists An Active Event";
				exit;
			}
			$startTime = date("Y-m-d H:i:s");
			//设置运行
			$logModel->setRunning($logID);
			//@todo
			//1、获取对应的置为0的sku列表
			//2、寻找对应sku的可用库存数量
			$zeroStockSKUModel = new JoomZeroStockSku();
			$conditions = "t.is_restore=0 and t.status=2 and t.account_id={$accountID} ";
			$limit = 500;
			$offset = 0;
			$msg = "";
			$warehouseSkuMapModel = new WarehouseSkuMap();
			do{
				$skuList = $zeroStockSKUModel->getDbConnection()->createCommand()
											->from($zeroStockSKUModel->tableName() . " as t")
											->select("t.seller_sku,t.account_id,t.sku")
											->where($conditions)
											->limit($limit, $offset)
											->group('t.seller_sku')
											->queryAll();
				$offset += $limit;
				//$this->print_r($skuList);
				if($skuList){
					$skuIDs = array();
					$updateInventoryRequest = new UpdateInventoryRequest;
					$updateInventoryRequest->setAccount($accountID);
					foreach ($skuList as $list){
						$warehouseSkuList = null;
						$select = 't.sku';
	                    if ($times < $twoSix) {
	                        $WarehouseConditions = 't.available_qty >= IFNULL(s.day_sale_num,5) AND t.warehouse_id = :warehouse_id AND p.product_is_multi IN(0,1) AND t.sku = "'.$list['sku'].'"';
	                        $param = array(':warehouse_id'=>WarehouseSkuMap::WARE_HOUSE_GM);
	                        $andWhere = ' and s.day_sale_num > 5';
	                        $warehouseSkuList = $warehouseSkuMapModel->getSkuListLeftJoinProductAndSalesByCondition($WarehouseConditions, $param, '', $select, $andWhere);
	                    }else{
	                        $WarehouseConditions = 't.available_qty >= :available_qty AND t.warehouse_id = :warehouse_id AND p.product_is_multi IN(0,1)  AND t.sku = "'.$list['sku'].'"';
	                        $param = array(':available_qty'=>5, ':warehouse_id'=>WarehouseSkuMap::WARE_HOUSE_GM);
	                        $warehouseSkuList = $warehouseSkuMapModel->getSkuListLeftJoinProductByCondition($WarehouseConditions, $param, '', $select);
	                    }
	                    if(!$warehouseSkuList){
	                    	continue;
	                    }

						$updateInventoryRequest->setSku($list['seller_sku']);
						$updateInventoryRequest->setInventory(999);//2016-02-19
						$response = $updateInventoryRequest->setRequest()->sendRequest()->getResponse();
						if($updateInventoryRequest->getIfSuccess()){
							$zeroStockSKUModel::model()->getDbConnection()
														->createCommand()
														->update($zeroStockSKUModel->tableName(), 
																array('is_restore'=>1), "account_id={$accountID} AND seller_sku='{$list['seller_sku']}'");
						}else{
							$msg .= " sku:{$list['seller_sku']}, accountID:{$accountID} " . $updateInventoryRequest->getErrorMsg();
						}
					}
				}
			}while ($skuList);
			$logModel->setSuccess($logID, $msg);
		}else{
			//循环每个账号发送一个拉listing的请求
			$accountList = JoomAccount::model()->getIdNamePairs();
			foreach($accountList as $accountID=>$accountName){
				MHelper::runThreadSOCKET('/'.$this->route.'/account_id/' . $accountID . '/type/' . $type);
				sleep(1);
			}
		}
	}
	
	/**
	 * @desc 批量修改价格
	 */
	public function actionBatchchangeprice(){
		set_time_limit(10*3600);
		ini_set("display_errors", true);
		error_reporting(E_ALL);
		$accountID = Yii::app()->request->getParam('account_id');
		$limit = Yii::app()->request->getParam('limit');
		$debug = isset($_REQUEST['bug']);
		if(!$accountID) exit('account id no special');
		if(!$limit) exit('limit no special');
		//Update a Product Variation
		$tablename = "ueb_joom_test_batch_change_price";
		$sql0 = "select * from {$tablename} where status=0 and account_id='{$accountID}' ";
		
		if($limit){
			$sql0 .= " limit {$limit} ";
		}
		$joomProductAddModel = new JoomProductAdd();
		//do{
			$skuList = $joomProductAddModel->getDbConnection()->createCommand($sql0)->queryAll();
			if($debug){
				var_dump($skuList);
			}
			if($skuList){
				foreach ($skuList as $sku){
					$updateProductVariationRequest = new UpdateProductVariationRequest;
					$updateProductVariationRequest->setAccount($accountID);
					$updateProductVariationRequest->setSku($sku['online_sku']);
					$updateProductVariationRequest->setPrice($sku['price']);
					$updateProductVariationRequest->setMsrp($sku['msrp']);
					$updateProductVariationRequest->setShipping($sku['shipping_price']);
					$response = $updateProductVariationRequest->setRequest()->sendRequest()->getResponse();
					if($updateProductVariationRequest->getIfSuccess()){//success
						$joomProductAddModel->getDbConnection()->createCommand()->update($tablename, array('status'=>1), "id=:id", array(":id"=>$sku['id']));
						echo $sku['sku']."--". $sku['parent_sku_online'] ."--" . $sku['online_sku'] . " : success <br/>";
					}else{//failure
						$joomProductAddModel->getDbConnection()->createCommand()->update($tablename, array('status'=>2), "id=:id", array(":id"=>$sku['id']));
						echo $sku['sku']."--". $sku['parent_sku_online'] ."--" . $sku['online_sku'] . " : failure [{$updateProductVariationRequest->getErrorMsg()}]<br/>";
					}
				}
			}
		//}while ($skuList);
	}


	/**
     * @desc 停售、待清仓状态、实际库存数大于0的SKU批量修改为实际库存出销，0库存的执行批量下架
     * @link /joom/joomapirequest/autoofflineproducts/accountID/1/sku/111
     */
    public function actionAutoofflineproducts() {
        set_time_limit(5*3600);
        ini_set('display_errors', true);
        error_reporting(E_ALL);

		$warehouseSkuMapModel     = new WarehouseSkuMap();
		$joomListingVariantsModel = new JoomVariants();
		$joomListingModel   	  = new JoomListing();
		$logModel                 = new JoomLog();
		$joomLogOfflineModel      = new JoomLogOffline();

        //账号
        $accountID = Yii::app()->request->getParam('accountID');

        //指定某个特定sku----用于测试
        $setSku = Yii::app()->request->getParam('sku');

        $select    = 't.sku, t.true_qty';
        $eventName = 'autoofflineproducts';
        $limit     = 1000;
        $offset    = 0;

        if($accountID){
            try{
                //写log
                $logID = $logModel->prepareLog($accountID, $eventName);
                if(!$logID){
                    exit('日志写入错误');
                }
                //检测是否可以允许
                if(!$logModel->checkRunning($accountID, $eventName)){
                    $logModel->setFailure($logID, Yii::t('system', 'There Exists An Active Event'));
                    exit('There Exists An Active Event');
                }

                //设置运行
                $logModel->setRunning($logID);

                do{
                    $command = $joomListingVariantsModel->getDbConnection()->createCommand()
                        ->from($joomListingVariantsModel->tableName() . " as t")
                        ->leftJoin($joomListingModel->tableName()." as p", "p.id=t.listing_id")
                        ->select("t.id,t.sku,t.online_sku,t.inventory,t.variation_product_id,t.account_id")
                        ->where('t.account_id = '.$accountID)
                        ->andWhere("t.enabled=1");
                        if($setSku){
                            $command->andWhere("t.sku = '".$setSku."'");
                        }
                        $command->limit($limit, $offset);
                    $variantListing = $command->queryAll(); 
                    $offset += $limit;
                    if(!$variantListing){
                        break;
                        // exit("此账号无数据");
                    }

                    $skuArr     = array();
                    $skuListArr = array();
                    $stockArr   = array();
                    foreach ($variantListing as $listingValue) {
                        $skuArr[] = $listingValue['sku'];
                    }

                    //数组去重
                    $skuUnique = array_unique($skuArr);

                    $conditions = 't.warehouse_id = :warehouse_id AND p.product_is_multi != 2 AND p.product_status IN(6,7) AND t.sku IN('.MHelper::simplode($skuUnique).')';
                    $param = array(':warehouse_id' => WarehouseSkuMap::WARE_HOUSE_GM);
                    // $limits = "{$offset},{$limit}";
                    $skuList = $warehouseSkuMapModel->getSkuListLeftJoinProductByCondition($conditions, $param, '', $select);
                    if(!$skuList){
                        continue;            
                    } 

                    unset($skuArr);

                    foreach ($skuList as $skuVal){
                        $skuListArr[] = $skuVal['sku'];
                        $stockArr[$skuVal['sku']] = $skuVal['true_qty'];
                    }

                    //取出今天已经下架的记录
                    $newSkuArr = array();
                    $diffSkuArr= array();
                    $startTime = date("Y-m-d 00:00:00");
                    $endTime   = date("Y-m-d H:i:s");
                    $logWhere  = "account_id = {$accountID} AND start_time >='{$startTime}' AND start_time <= '{$endTime}'";
                    $order     = '';
                    $group     = 'sku';
                    $offLineList = $joomLogOfflineModel->getListByCondition('sku',$logWhere,$order,$group);
                    if($offLineList){
                        foreach ($offLineList as $getSku) {
                            $newSkuArr[] = $getSku['sku'];
                        }
                    }

                    //比较两个数组的差值
                    $diffSkuArr = array_diff($skuListArr, $newSkuArr);
                    if(empty($diffSkuArr)){
                        continue;
                    }

                    foreach ($variantListing as $variant){
                        if(!in_array($variant['sku'], $diffSkuArr)){
                            continue;
                        }

                        $time    = date("Y-m-d H:i:s");
                        $message = '';

                        $stocks = 0;

                        //判断可用库存
                        if($stockArr[$variant['sku']] > 0){

                            $stocks = $stockArr[$variant['sku']];
                            if($stocks == $variant['inventory']){
                            	continue;
                            }

                            $updateInventoryRequest = new UpdateInventoryRequest;
	                        $updateInventoryRequest->setSku($variant['online_sku']);
							$updateInventoryRequest->setInventory($stocks);
	                        $updateInventoryRequest->setAccount($accountID);
							$response = $updateInventoryRequest->setRequest()->sendRequest()->getResponse();
							$message = "";
							if($updateInventoryRequest->getIfSuccess()){
								$status = 1;//成功
								$message = "修改库存为{$stocks}成功";
								$joomListingVariantsModel->getDbConnection()->createCommand()
									->update($joomListingVariantsModel->tableName(),
											array('inventory'=>$stocks),
											'online_sku=:online_sku AND account_id=:account_id',
											array(':online_sku'=>$variant['online_sku'], ':account_id'=>$accountID)
										);
							}else{
								$status = 0;//失败
								$message = $updateInventoryRequest->getErrorMsg();
							}

                        }else{
                        	$disableProductVariantRequest = new DisabledProductVariantRequest();
					    	$disableProductVariantRequest->setAccount($accountID);
					    	$disableProductVariantRequest->setSku($variant['online_sku']);
				    		$response = $disableProductVariantRequest->setRequest()->sendRequest()->getResponse();
				    		if($disableProductVariantRequest->getIfSuccess()){
				    			$status = 1;//成功
								$message = "下架成功";
								$joomListingVariantsModel->disabledJoomVariantsByOnlineSku($variant['online_sku'],$accountID);
				    		}else{
								$status = 0;//失败
								$message = $disableProductVariantRequest->getErrorMsg();
							}                            
                        }

                        $addData = array(
                            'product_id'        => $variant['variation_product_id'],
                            'sku'               => $variant['sku'],
                            'online_sku'        => $variant['online_sku'],
                            'account_id'        => $variant['account_id'],
                            'event'             => 'autoofflineproducts',
                            'status'            => $status,
                            'inventory'         => $variant['inventory'],                            
                            'message'           => $message,
                            'start_time'        => date('Y-m-d H:i:s'),
                            'operation_user_id' => 1
                        );

                        $joomLogOfflineModel->savePrepareLog($addData);
                    }

                }while($variantListing);     
                $logModel->setSuccess($logID, "success");

            }catch (Exception $e){
                if(isset($logID) && $logID){
                    $logModel->setFailure($logID, $e->getMessage());
                }
                echo $e->getMessage()."<br/>";
            }
        }else{
			$accountList = JoomAccount::model()->getIdNamePairs();
			foreach($accountList as $accountID=>$accountName){
				MHelper::runThreadSOCKET('/'.$this->route.'/accountID/' . $accountID);
				sleep(5);
			}
        }
    }


    /**
	 * @desc 批量修改价格
	 * /joom/joomapirequest/batchchangepricetotmp/account_id/1/sku/0386
	 */
	public function actionBatchchangepricetotmp(){
		set_time_limit(10*3600);
		ini_set('memory_limit','2048M');
		ini_set("display_errors", true);
		error_reporting(E_ALL);

		$accountID = Yii::app()->request->getParam('account_id');
		if(!$accountID){
			exit('账号ID不能为空');
		}

		$sku = Yii::app()->request->getParam('sku');
		$where = ' AND t.account_id = '.$accountID;
		if($sku){
			$where .= " AND t.sku = '".$sku."'";
		}

		$listingVariantsModel = new JoomVariants();

		$tablename = 'ueb_joom_sku_temp';

		$sql = "SELECT t.id,t.sku,t.edit_price,v.account_id,v.online_sku,v.msrp,v.shipping,v.id as v_id FROM `ueb_joom_sku_temp` t 
				LEFT JOIN `ueb_listing_variants` v ON v.sku = t.sku AND v.account_id = t.account_id
				WHERE t.id > 0 ".$where;
		
		$joomProductAddModel = new JoomProductAdd();
		$skuList = $joomProductAddModel->getDbConnection()->createCommand($sql)->queryAll();
		if($skuList){
			foreach ($skuList as $sku){
				$updateProductVariationRequest = new UpdateProductVariationRequest;
				$updateProductVariationRequest->setAccount($accountID);
				$updateProductVariationRequest->setSku($sku['online_sku']);
				$updateProductVariationRequest->setPrice($sku['edit_price']);
				$updateProductVariationRequest->setMsrp($sku['msrp']);
				$updateProductVariationRequest->setShipping($sku['shipping']);
				$response = $updateProductVariationRequest->setRequest()->sendRequest()->getResponse();
				if($updateProductVariationRequest->getIfSuccess()){
					$listingVariantsModel->getDbConnection()->createCommand()->update(
						$listingVariantsModel->tableName(), 
						array('price'=>$sku['edit_price']), "id=:id", array(":id"=>$sku['v_id'])
					);
					$joomProductAddModel->getDbConnection()->createCommand()->update($tablename, array('msg'=>'success--'.$sku['v_id']), "id=:id", array(":id"=>$sku['id']));
					echo $sku['sku'] ."--" . $sku['online_sku'] . " : success <br/>";
				}else{
					$failure = $updateProductVariationRequest->getErrorMsg();
					$joomProductAddModel->getDbConnection()->createCommand()->update($tablename, array('msg'=>$failure), "id=:id", array(":id"=>$sku['id']));
					echo $sku['sku'] ."--" . $sku['online_sku'] . " : failure [{$failure}]<br/>";
				}
			}
		}
	}
}