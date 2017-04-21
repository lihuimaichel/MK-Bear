<?php
/**
 * @desc 亚马孙产品管理
 * @author lihy
 *
 */
class AmazonproductController extends UebController {
	private $_model = null;
	public function init(){
		parent::init();
		$this->_model = new AmazonList();
	}
	/* public function accessRules(){
		return array(
				'allow',
				'users'=>array('*'),
				'actions'=>array('list')
				);
	} */
	public function actionList(){
		$this->render("list", array('model'=>$this->_model));
	}
	/**
	 * @desc 批量更新
	 * @throws Exception
	 */
	public function actionBatchoffline(){
		$id = Yii::app()->request->getParam('amazon_product_ids');
		if(empty($id)){
			//错误输出
			echo $this->failureJson(array(
					'message'=>Yii::t('amazon_product', 'Not Specify Sku Which Need To Inactive')
			));
			Yii::app()->end();
		}
		$productList = $this->_model->findAll('id in('.implode(",", $id).')');
		if(!$productList){
			echo $this->failureJson(array(
					'message'=>Yii::t('amazon_product', 'Not Specify Sku Which Need To Inactive')
			));
			Yii::app()->end();
		}
		$itemData = array();
		foreach ($productList as $product){
			$itemData[$product->account_id][] = array(
								'sku'=>$product->seller_sku,
								'quantity'=>0
							);
		}
		try{
			foreach ($itemData as $accountId=>$item){
				$this->_model->amazonProductOffline($accountId, $item);
			}
			//success
			echo $this->successJson(array(
					'message'=>Yii::t('amazon_product', 'Send Request successful')
			));
		}catch (Exception $e){
			//failure
			echo $this->failureJson(array(
					'message'=>Yii::t('amazon_product', 'Send Request failure')
			));
		}
		Yii::app()->end();
	}
	/**
	 * @desc 下架亚马逊产品
	 */
	public function actionOffline(){
		$id = Yii::app()->request->getParam('id');
		if(empty($id)){
			//错误输出
			echo $this->failureJson(array(
					'message'=>Yii::t('amazon_product', 'Not Specify Sku Which Need To Inactive')
			));
			Yii::app()->end();
		}
		//获取当前产品的信息
		$amazonRow = $this->_model->findByPk($id);
		$sku = $amazonRow->seller_sku;
		$asin1 = $amazonRow->asin1;
		$accountId = $amazonRow->account_id;
		$listingId = $amazonRow->amazon_listing_id;
		$sellerSku = $amazonRow->seller_sku;
		$itemData = array(
			array(
				'sku'=>$sku,
				'quantity'=>0
			)
		);
		try{
			$flag = $this->_model->amazonProductOffline($accountId, $itemData);
			if($flag){
				//success
				echo $this->successJson(array(
					'message'=>Yii::t('amazon_product', 'Send Request successful')
				));
			}else{
				//failure
				throw new Exception($this->_model->getErrorMsg());
			}
		}catch (Exception $e){
			//failure
			echo $this->failureJson(array(
				'message'=>Yii::t('amazon_product', 'Send Request failure')
			));
		}
		Yii::app()->end();
	}
	
	/**
	 * @desc 用来跑定时任务，检测提交的submissionId
	 */
	public function actionDetectsubmitfeed(){
		try{
			/*根据submitfeedid检测是否完成*/
			$amazonRequestReport = new AmazonRequestReport();
			$conditions = "scheduled=:scheduled AND report_type=:report_type";
			$params = array(
							':scheduled'=>AmazonRequestReport::SCHEDULED_NO,
							':report_type'=>SubmitFeedRequest::FEEDTYPE_POST_INVENTORY_AVAILABILITY_DATA
						);
			$reportList = $amazonRequestReport->getRequestReportList($conditions, $params);
			$newreportList = $reportSkuList = array();
			foreach ($reportList as $report){
				$newreportList[$report['account_id']][] = $report['report_request_id'];
				$reportSkuList[$report['report_request_id']] = $report['report_skus'];
			}
			unset($reportList);
			
			//获取submission处理状态
			//获取对应的处理报告
			//先更新本地销售状态
			//更新报表数据状态
			foreach ($newreportList as $accountId=>$newreport){
				$amazonLog = new AmazonLog;
				$logID = $amazonLog->prepareLog($accountId, AmazonList::EVENT_DETECT_SUBMISSION_NAME);
				if( $logID ){
					//1.检查账号是可以提交请求报告
					$checkRunning = $amazonLog->checkRunning($accountId, AmazonList::EVENT_DETECT_SUBMISSION_NAME);
					if( !$checkRunning ){
						$amazonLog->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
						throw new Exception('There Exists An Active Event');
					}else{
						//插入本次log参数日志(用来记录请求的参数)
						$eventLog = $amazonLog->saveEventLog(AmazonList::EVENT_DETECT_SUBMISSION_NAME, array(
								'log_id'        => $logID,
								'account_id'    => $accountId,
								'start_time'    => date('Y-m-d H:i:s'),
								'end_time'      => date('Y-m-d H:i:s'),
						));
						//设置日志为正在运行
						$amazonLog->setRunning($logID);
						$reponse = $this->_model->amazonFeedSubmissionRequest($accountId, $newreport, $reportSkuList);
						
						if( $reponse ){
							$amazonLog->setSuccess($logID);
							$amazonLog->saveEventStatus(AmazonList::EVENT_DETECT_SUBMISSION_NAME, $eventLog, AmazonLog::STATUS_SUCCESS);
						}else{
							$amazonLog->setFailure($logID, $amazonRequestReport->getExceptionMessage());
							$amazonLog->saveEventStatus(AmazonList::EVENT_DETECT_SUBMISSION_NAME, $eventLog, AmazonLog::STATUS_FAILURE);
							throw new Exception('There No Result');
						}
					}
				}
			}
			echo "finished！";
		}catch (Exception $e){
			echo $e->getMessage();
		}
	}
	
    /**
     * @desc 系统自动导入下线sku, 条件：待清仓且可用库存小于等于0
     * @link /amazon/amazonproduct/autoimportofflinetask
     */
    public function actionAutoimportofflinetask() {
        set_time_limit(3*3600);
        ini_set('display_errors', true);
        error_reporting(E_ALL);

        $nowTime     = date("Y-m-d H:i:s");
        $productTemp = ProductTemp::model();
        $res         = $productTemp->getDbConnection()->createCommand()
                            ->select("count(*) as total")
                            ->from($productTemp->tableName())
                            ->where("product_status=6 and available_qty<=0")
                            ->andWhere("product_is_multi!=2")
                            ->queryRow();
        $total     = $res['total'];                     
        $pageSize  = 2000;
        $pageCount = ceil($total/$pageSize);
        for ($page=1; $page <= $pageCount ; $page++) { 
            $offset = ($page - 1) * $pageSize;
            $res    = $productTemp->getDbConnection()->createCommand()
                            ->select("sku")
                            ->from($productTemp->tableName())
                            ->where("product_status=6 and available_qty<=0")
                            ->andWhere("product_is_multi!=2")
                            ->order("sku asc")
                            ->limit($pageSize,$offset)
                            ->queryAll();
            if (empty($res)) {
                continue;
            }
            foreach ($res as $v) {
                $rows = array();
                $variationInfos = AmazonList::model()->filterByCondition('id,sku,account_id,asin1,seller_sku'," seller_status=1 and sku='{$v['sku']}' ");
                if (!empty($variationInfos)) {
                    foreach ($variationInfos as $vs) {
                    	$aWhInfo = AmazonAsinWarehouse::model()->getWarehouseInfoByAsin($vs['asin1'],$vs['seller_sku']);
                    	if ($aWhInfo) {//如果是海外仓listing
                    		continue;
                    	}
                    	//存入下架任务表
                        $rows[] = array(
                            'sku'            => $v['sku'],
                            'account_id'     => $vs['account_id'],
                            'status'         => 0,
                            'create_user_id' => (int)Yii::app()->user->id,
                            'create_time'    => $nowTime,
                            'type'           => 2,//系统导入
                        );
                    }
                }
                if ($rows) {
                    $res = AmazonOfflineTask::model()->insertBatch($rows);
                }
            }
        }
        Yii::app()->end('finish');
    }
	
	/**
	 * @desc 批量文件导入
	 */
	public function actionImportcsvoffline(){
        set_time_limit(2*3600);
        ini_set('memory_limit','2048M');
		if(isset($_POST) && $_POST){
			try{
				if(empty($_FILES['csvfilename']['name'])){
					throw new Exception(Yii::t('amazon_product', 'No csv file upload'));
				}
				$accounts = isset($_POST['accounts'])?$_POST['accounts']:null;
				if(empty($accounts))
					throw new Exception(Yii::t('amazon_product', 'No chose account'));
				$fp  = fopen($_FILES['csvfilename']['tmp_name'], 'rb');
				if($fp){//导入SKU
					$data                   = array();
					$i                      = 0;
					$fieldName              = 'SKU';
					$fieldIndex             = 0;
					$hasSkuField            = false;
					$amazonOfflineTaskModel = UebModel::model('AmazonOfflineTask');
					$row                    = 0;
					$sql                    = "INSERT INTO `ueb_amazon_offline_task` ( `sku`, `account_id`, `status`, `create_user_id`, `create_time` ) VALUES ";
					while($value = fgetcsv($fp, 65535)){
						if(!isset($value[0])) continue;
						$fields = explode(" ", $value[0]);
						if($fields){
                            $row++;
							if($i == 0){
								foreach ($fields as $key=>$_field){
									if(strtoupper(trim($_field)) == $fieldName){
										$fieldIndex = $key;
										$hasSkuField = true;
									}
								}
								if(!$hasSkuField)
									throw new Exception(Yii::t('amazon_product', 'No sku field'));
								$i++;
								continue;
							}
                                                        
							foreach ($accounts as $account){
                                $data[] = array(
                                    'sku'               => trim($fields[$fieldIndex]),
                                    'account_id'        => $account,
                                    'status'            => 0,
                                    'create_user_id'    => (int)Yii::app()->user->id,
                                    'create_time'       => date('Y-m-d H:i:s'),
                                    'type' 				=> 1,//手工导入
                                );
							}
                            if($row % 50 ==0){
                                $res = $amazonOfflineTaskModel->insertBatch($data);
                                $data = array();
                            }
						}
					}
                    if(!empty($data)){
                        $res = $amazonOfflineTaskModel->insertBatch($data);
                    }
				}
				echo $this->successJson(array(
						'message'=>Yii::t('amazon_product', 'Upload success'),
						'callbackType' => 'closeCurrent'
						));
			}catch (Exception $e){
				echo $this->failureJson(array('message'=>$e->getMessage()));
			}
		}else{
			//获取全部可用账号
			$accounts = UebModel::model('AmazonAccount')->getIdNamePairs();
			
			$this->render('importcsvoffline', array(
				'accounts'=>$accounts
			));
		}
	}

    /**
	 * @desc 下线任务
	 * @link /amazon/amazonproduct/offlineTask
	 */
	public function actionOfflineTask(){
		set_time_limit(5*3600);
        ini_set('memory_limit','2048M');
		ini_set('display_errors', true);	

        $type = Yii::app()->request->getParam("type");
        $time = time();
        $amazonLogOfflineModel = new AmazonLogOffline();

        if($type == 'query'){
            //白天执行查询
            $flag_while = true;
            $flag_online = false;
        } else {
            //晚上执行下架
            $flag_while = true;
            $flag_online = true;
        }

        while( $flag_while ){
            $exe_time   =  time();
            if(($exe_time - $time) >= 36000 ){
                $flag_while = false;
                exit('执行超过10小时');
            }
            $amazonOfflineTaskModel = new AmazonOfflineTask();
            $res = $amazonOfflineTaskModel->getAmazonTaskListByStatus(AmazonOfflineTask::UPLOAD_STATUS_PENDING);
            if($res){
                foreach ($res as $row) {
                    $accountID = $row['account_id'];
                    $data = array(
                        'process_time' => date('Y-m-d H:i:s'),
                        'status' => 1,
                    );
                    
                    //查询要下线的listing
                    $command = AmazonList::model()->getDbConnection()->createCommand()
	                            ->select("*")
	                            ->from("ueb_amazon_listing")
	                            ->where("sku = '" . $row['sku'] . "'")
	                            ->andWhere("seller_status = :seller_status" ,array(':seller_status' => AmazonList::SELLER_STATUS_ONLINE) )
	                            ->andWhere("account_id = " . $accountID);
                    $skuOnline = $command->queryRow();
                    if (empty($skuOnline)) {
                        AmazonOfflineTask::model()->getDbConnection()->createCommand()->update("ueb_amazon_offline_task", array('status'=>3),"id = " . $row['id'] );
                    } else {
                        AmazonOfflineTask::model()->getDbConnection()->createCommand()->update("ueb_amazon_offline_task", $data, "id = " . $row['id']);
                    }
                }
            } else {
                $flag_while = false;
            }
        }
        
        $upload_listing = array();
        $skus_upload = array();
        while( $flag_online ){
            $exe_time   =  time();
            if(($exe_time - $time) >= 36000 ){
                $flag_online = false;
                exit('执行超过10小时');
            }
            $amazonOfflineTaskModel = new AmazonOfflineTask();
            $taskListing = $amazonOfflineTaskModel->getAmazonTaskListByStatus(AmazonOfflineTask::UPLOAD_STATUS_PROCESSING);
            if($taskListing){
                $data_zero = array(
                    'process_time' => date('Y-m-d H:i:s'),
                    'status' => 0,
                );
                $newTaskListing = array();
                foreach ($taskListing as $listing){
                    $newTaskListing[$listing['account_id']]['sku'][] = $listing['sku'];
                    AmazonOfflineTask::model()->getDbConnection()->createCommand()->update("ueb_amazon_offline_task", $data_zero, "id = " . $listing['id']);
                }
                foreach ($newTaskListing as $accountId=>$itemData){
                    //@TODO 加事件日志
                    //转换为线上sku
                    $skus = "'". implode("','", $itemData['sku']) ."'";
                    $productListing = $this->_model->findAll('account_id=' . $accountId . ' AND seller_status = '. AmazonList::SELLER_STATUS_ONLINE  . ' AND sku in('.$skus.')');
                    if($productListing){
                        foreach ($productListing as $product){
                            $upload_listing[$accountId][] = array('listing_id'=>$product->id, 'sku'=>$product->seller_sku, 'quantity'=>0);
                                $skus_upload[$accountId][] = $product->sku;
                        }
                    }
                }
            } else {
                $flag_online = false;
            }
        }
        
        if ($upload_listing) {
	        foreach ( $upload_listing as $accountId =>$sellerSku ){
	            //根据账号一次上传完
	            $flag = $this->_model->amazonProductOffline($accountId, $sellerSku);
	            $conditions_upload = 'account_id='.$accountId.' AND sku in ('.MHelper::simplode($skus_upload[$accountId]).')';
	            if($flag){
	                $data_success = array(
	                    'status'=>AmazonOfflineTask::UPLOAD_STATUS_SUCCESS,
	                    'process_time'=>date("Y-m-d H:i:s")
	                );
	                $amazonOfflineTaskModel->updateAmazonTask($data_success, $conditions_upload);

            		//成功才存入下架日志表
                	$addData = array(
                        'listing_id'        => $sellerSku['listing_id'],
                        'sku'               => $sellerSku['sku'],
                        'account_id'        => $accountId,
                        'event'             => 'offlinetask',	//事件类型：待清仓批量下线SKU任务
                        'inventory'         => $sellerSku['quantity'],  
                        'status'            => 1,	//成功                                                 
                        'message'           => 'SUCCESS',
                        'start_time'        => date("Y-m-d H:i:s"),
                        'response_time'     => date("Y-m-d H:i:s"),
                        'operation_user_id' => 1
                    );
                    $amazonLogOfflineModel->savePrepareLog($addData);

	            } else {
	                $data_fail = array(
	                    'status'=>AmazonOfflineTask::UPLOAD_STATUS_FAILURE,
	                    'process_time'=>date("Y-m-d H:i:s"),
	                    'response_msg' => $this->_model->getErrorMsg()
	                );
	                $amazonOfflineTaskModel->updateAmazonTask($data_fail, $conditions_upload);
	            }
	            //2分钟提交一次请求 
	            sleep(120);
	        }
        }
        
		echo "finished";
	}
	
	/**
	 * @desc 下线任务
	 */
	public function actionOfflineTaskbackup(){
		set_time_limit(3*3600);
        ini_set('memory_limit','2048M');
		ini_set('display_errors', true);
        $time = time();

        $flag_while = true;
        $upload_listing = array();
        $skus_upload = array();
        while( $flag_while ){
            $exe_time   =  time();
            if(($exe_time - $time) >= 18000 ){
                $flag_while = false;
                //exit('执行超过5小时');
            }
            $amazonOfflineTaskModel = new AmazonOfflineTask();
            $taskListing = $amazonOfflineTaskModel->getAmazonTaskListByStatus(AmazonOfflineTask::UPLOAD_STATUS_PENDING);
            if($taskListing){
                $newTaskListing = array();
                foreach ($taskListing as $listing){
                    //运行中
                    $data_running = array(
                                    'process_time' => date('Y-m-d H:i:s'),
                                    'status' => 1,
                    );
                    $conditions_running = ' sku = "' . $listing['sku'] . '" and account_id= "' . $listing['account_id'] .'"';
                    $amazonOfflineTaskModel->updateAmazonTask($data_running, $conditions_running);
                    $newTaskListing[$listing['account_id']]['sku'][] = $listing['sku'];
                }
                foreach ($newTaskListing as $accountId=>$itemData){
                    //@TODO 加事件日志
                    //转换为线上sku
                    $skus = "'". implode("','", $itemData['sku']) ."'";
                    $productListing = $this->_model->findAll('account_id=' . $accountId . ' AND seller_status = '. AmazonList::SELLER_STATUS_ONLINE  . ' AND sku in('.$skus.')');
                    if($productListing){
                        foreach ($productListing as $product){
                            $upload_listing[$accountId][] = array('sku'=>$product->seller_sku, 'quantity'=>0);
                            $skus_upload[$accountId][] = $product->sku;
                        }
                    }
                }
            } else {
                $flag_while = false;
            }
        }
        
        foreach ( $upload_listing as $accountId =>$sellerSku ){
            //根据账号一次上传完
            $flag = $this->_model->amazonProductOffline($accountId, $sellerSku);
            $conditions_upload = 'account_id='.$accountId.' AND sku in ('.MHelper::simplode($skus_upload[$accountId]).')';
            if($flag){
                $data_success = array(
                    'status'=>AmazonOfflineTask::UPLOAD_STATUS_SUCCESS,
                    'process_time'=>date("Y-m-d H:i:s")
                );
                $amazonOfflineTaskModel->updateAmazonTask($data_success, $conditions_upload);
            } else {
                $data_fail = array(
                    'status'=>AmazonOfflineTask::UPLOAD_STATUS_FAILURE,
                    'process_time'=>date("Y-m-d H:i:s"),
                    'response_msg' => $this->_model->getErrorMsg()
                );
                $amazonOfflineTaskModel->updateAmazonTask($data_fail, $conditions_upload);
            }

            //2分钟提交一次请求 
            sleep(120);
        }
        
        AmazonOfflineTask::model()->getDbConnection()->createCommand()->delete("ueb_amazon_offline_task", "status=1" );
		echo "finished";
	}

	/**
	 * @desc test
	 */
	public function actionTest(){
		$accountID = 13;
		$asin = array('B00HVQHTDW');
		$getMatching = new GetMatchingProductRequest();
		$getMatching->setAccount($accountID);
		$getMatching->setAsinID($asin);
		$getMatching->setRequest()->sendRequest()->getResponse();
	}

    /**
	 * 测试批量下线任务表的产品状态1改为0
	 */
    public function actionOfflineOneToZero(){
        $data = array(
                'process_time' => date('Y-m-d H:i:s'),
                'status' => 0,
        );
        AmazonOfflineTask::model()->getDbConnection()->createCommand()->update("ueb_amazon_offline_task", $data, "status = 1");
    }
	/**
	 * 导入下架任务表删除没有线上sku的数据
	 */
    public function actionDeleteNoOnlineSku(){
        AmazonOfflineTask::model()->getDbConnection()->createCommand()->delete("ueb_amazon_offline_task", "response_msg = 'no online sku'" );
    }

    /**
     * @desc 指定SKU下架（库存置0）
     * @link /amazon/amazonproduct/offlinebysku/
     */
    public function actionOfflineBySKU(){
		$offlineList = array();
		$skuArr = array('0281','103701','103973.03','103983.03','104125','104703.05','104986.02','108988','109446.01','110873.02','111103.03','112394','112618.02','113652.03','113652.04','115132.03','116338','72356.07','72451.05','90286','90437','92107.02','92146.09','92189.03','93302','95458.02','96532','97783.01');
		$amazonList = new AmazonList();
        //查询要下线的listing
        $command = $amazonList->getDbConnection()->createCommand()
                    ->select("*")
                    ->from("ueb_amazon_listing")
                    ->where("sku in('". implode("','", $skuArr) ."')");
        $skuList = $command->queryAll();
        // MHelper::printvar($skuList);

        if($skuList){
            foreach ($skuList as $product){
                $offlineList[$product['account_id']][] = array('id'=>$product['id'], 'account_id'=>$product['account_id'], 'sku'=>$product['seller_sku'], 'quantity'=>0);
            }
        }      
        // MHelper::printvar($offlineList);  
        // $offlineList = array();
        // $offlineList[10][] = array('id'=>'54857', 'account_id'=>'10', 'sku'=>'1th1da5ps1kh3ga2D02-US08', 'quantity'=>500);
        // echo '<pre>';
        // print_r($offlineList); 

        if ($offlineList){
	        foreach ($offlineList as $accountID => $item){
        		$flag = $this->_model->amazonProductOffline($accountID, $item);
        		if ($flag){
	        		foreach ($item as $val){
	        			$addID = $val['id'];	            			           
			            $updata = array('seller_status' => 2, 'quantity' => 0);
			            $amazonList->updateAmazonProduct("id = {$addID}", $updata);
			        }
			    echo $flag;
			    echo '<br />';   
			    }else{
			    	echo ($this->_model->getErrorMsg());
			    	echo '<pre>';
			    	print_r($item);
			    	break;
			    }
	        }
	    }
        echo 'Finish!';
        Yii::app()->end();
    }
}

?>