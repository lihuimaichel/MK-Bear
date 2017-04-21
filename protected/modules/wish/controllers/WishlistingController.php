<?php
/**
 * @desc Wish listing
 * @author Gordon
 * @since 2015-06-02
 */
class WishlistingController extends UebController{
	private $_model = null;
    
    /**
     * @desc 访问过滤配置
     * @see CController::accessRules()
     */
    public function accessRules() {
        return array(
			array(
				'allow',
				'users' => array('*'),
				'actions' => array('getlisting', 'list')
			),
		);
    }  

    /**
     * @desc 初始化工作
     * @see CController::init()
     */
    public function init(){
    	parent::init();
    	$this->_model = new WishListing();
    }
    
    
    
    public function actionList(){
    	$this->render("list", array(
    			'model'=>new WishListing()
    	));
    }
    
    /**
     * @desc 单个下线功能
     * @throws Exception
     */
    public function actionOffline(){
    	set_time_limit(2*3600);
    	$variantId = Yii::app()->request->getParam('id');
    	if(empty($variantId)){
    		echo $this->failureJson(
    				array(
    					'message'=>Yii::t("wish_listing", 'Invalide Product Variants')
    				)
    			);
    		Yii::app()->end();
    	}
    	try{
	    	//获取
	    	$variants = UebModel::model('WishVariants')->findByPk($variantId);
	    	if($variants){
	    		$sku = $variants->online_sku;
	    		$accountID = $variants->account_id;
	    		$wishLog = new WishLog;
	    		$logID = $wishLog->prepareLog($accountID, WishListing::EVENT_DISABLED_VARIANTS);
	    		if( $logID ){
	    			//1.检查账号是可以提交请求报告
	    			$checkRunning = $wishLog->checkRunning($accountID,  WishListing::EVENT_DISABLED_VARIANTS);
	    			if( !$checkRunning ){
	    				$wishLog->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
	    				throw new Exception(Yii::t('systems', 'There Exists An Active Event'));
	    			}else{
	    				//插入本次log参数日志(用来记录请求的参数)
	    				$eventLog = $wishLog->saveEventLog(WishListing::EVENT_DISABLED_VARIANTS, array(
	    						'log_id'        => $logID,
	    						'account_id'    => $accountID,
	    						'start_time'    => date('Y-m-d H:i:s'),
	    						'end_time'      => date('Y-m-d H:i:s'),
	    				));
	    				//设置日志为正在运行
	    				$wishLog->setRunning($logID);
	    				$result = $this->_model->disabledVariants($accountID, $sku);
	    				if($result['success']){
	    					//UebModel::model('WishVariants')->updateAll(array('enabled'=>0), "account_id={$accountID} AND online_sku in('".implode("','", $result['success'])."')");
	    					UebModel::model('WishVariants')->disabledWishVariantsByOnlineSku($result['success'], $accountID);
	    					$wishLog->setSuccess($logID);
	    					$wishLog->saveEventStatus(WishListing::EVENT_DISABLED_VARIANTS, $eventLog, WishLog::STATUS_SUCCESS);
	    					echo $this->successJson(array('message'=>Yii::t('system', 'Update successful')));
	    					Yii::app()->end();
	    				}else{
	    					$wishLog->setFailure($logID, $this->_model->getExceptionMessage());
	    					$wishLog->saveEventStatus(WishListing::EVENT_DISABLED_VARIANTS, $eventLog, WishLog::STATUS_FAILURE);
	    					throw new Exception($this->_model->getExceptionMessage());
	    				}
	    			}
	    		}
	    	}
	    	throw new Exception('No Invalide Request');
    	}catch (Exception $e){
    		echo $this->failureJson(
    				array(
    						'message'=>$e->getMessage(),
    						//'message'=>Yii::t("system", 'Update failure')
    				)
    		);
    		Yii::app()->end();
    	}
    	
    }
    /**
     * @desc 批量下架wish产品
     */
    public function actionBatchoffline(){
    	set_time_limit(2*3600);
    	$variantsIds = Yii::app()->request->getParam('wish_varants_ids');
    	if(empty($variantsIds)){
    		echo $this->failureJson(array(
					'message'=>Yii::t('wish_listing', 'Not Specify Sku Which Need To Inactive')
			));
			Yii::app()->end();
    	}
    	$variants = UebModel::model('WishVariants')->findAllByPk($variantsIds);
    	if($variants){
    		$newVariants = $successRes = array();
    		foreach ($variants as $variant){
    			$newVariants[$variant['account_id']][] = $variant['online_sku'];
    		}
    		unset($variants);
    		foreach ($newVariants as $accountID=>$variant){
    			$wishLog = new WishLog;
    			$logID = $wishLog->prepareLog($accountID, WishListing::EVENT_DISABLED_VARIANTS);
    			if( $logID ){
    				//1.检查账号是可以提交请求报告
    				$checkRunning = $wishLog->checkRunning($accountID,  WishListing::EVENT_DISABLED_VARIANTS);
    				if( !$checkRunning ){
    					$wishLog->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
    				}else{
    					//插入本次log参数日志(用来记录请求的参数)
    					$eventLog = $wishLog->saveEventLog(WishListing::EVENT_DISABLED_VARIANTS, array(
    							'log_id'        => $logID,
    							'account_id'    => $accountID,
    							'start_time'    => date('Y-m-d H:i:s'),
    							'end_time'      => date('Y-m-d H:i:s'),
    					));
    					//设置日志为正在运行
    					$wishLog->setRunning($logID);
		    			$result = $this->_model->disabledVariants($accountID, $variant);
		    			
		    			if($result['success']){
		    				$successRes = array_merge($successRes, $result['success']);
		    				//更新本地
		    				//UebModel::model('WishVariants')->updateAll(array('enabled'=>0), "account_id={$accountID} AND online_sku in('".implode("','", $successRes)."')");
		    				UebModel::model('WishVariants')->disabledWishVariantsByOnlineSku($result['success'], $accountID);
		    				$wishLog->setSuccess($logID);
		    				$wishLog->saveEventStatus(WishListing::EVENT_DISABLED_VARIANTS, $eventLog, WishLog::STATUS_SUCCESS);
		    			}else{
		    				$wishLog->setFailure($logID, $this->_model->getExceptionMessage());
		    				$wishLog->saveEventStatus(WishListing::EVENT_DISABLED_VARIANTS, $eventLog, WishLog::STATUS_FAILURE);
		    			}
    				}
    			}
    		}
			if($successRes){
    			echo $this->successJson(array('message'=>Yii::t('system', 'Update successful')));
    			Yii::app()->end();
			}
    	}
    	echo $this->failureJson(
    			array(
    					'message'=>Yii::t("system", 'Update failure')
    			)
    	);
    	Yii::app()->end();
    }

    /**
     * @desc 系统自动导入下线sku, 条件：待清仓且可用库存小于等于0
     * @link /wish/wishlisting/autoimportofflinetask
     */
    public function actionAutoimportofflinetask() {
        set_time_limit(5*3600);
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
                $variationInfos = WishVariants::model()->filterByCondition('p.account_id,v.product_id,v.sku,v.id'," v.enabled=1 and v.sku='{$v['sku']}' ");
                if (!empty($variationInfos)) {
                    foreach ($variationInfos as $vs) {
                        $aWhInfo = WishOverseasWarehouse::model()->getWarehouseInfoByProductID($vs['product_id']);
                        if ($aWhInfo) {//如果是海外仓listing
                            continue;
                        }

                        // 将满足最近订单统计条件的下架listing拦截，不进行自动下架
                        // 1.待清仓的，满足wish产品下架规则的链接 ，wish平台该账号该SKU过去7天销量≥25个的，下架拦截，平台销售自行处理
                        // 2.待清仓的，满足wish产品下架规则的链接，wish平台该账号该SKU过去9天订单总金额≥$500的，下架拦截，平台销售自行处理
                        $offlineRet = false; 
                        $holdedInfo = WishOrderStatistics::model()->getListByCondition("account_id = ".$vs['account_id']." AND sku ='".$vs['sku']."'");                    
                        if ($holdedInfo) {
                            foreach($holdedInfo as $val){
                                $holdedRet = false;
                                if ($val['type'] == 1 || $val['type'] == 2){
                                    $addData = array(
                                        'account_id'   => $vs['account_id'],
                                        'sku'          => $vs['sku'],
                                        'variation_id' => $vs['id'],
                                        'product_id'   => $vs['product_id'],
                                        'type'         => $val['type'],
                                        'total'        => $val['total'],
                                        'source_type'  => 2,
                                        'create_time'  => date('Y-m-d H:i:s')
                                    );
                                    //拦截的数据更新入库
                                    $holdedRet = WishListingHoldedOffline::model()->setHoldedInfo($addData);
                                    if($holdedRet) $offlineRet = true;
                                }
                            }
                            if($offlineRet) continue;   //有拦截成功才跳出
                        }

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
                    $res = WishOfflineTask::model()->insertBatch($rows);
                }
            }
        }
        Yii::app()->end('finish');
    }    

    /**
     * @desc 导入csv文件批量下架
     */
    public function actionImportcsvoffline(){
        set_time_limit(5*3600);
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
                    $data                 = array();
                    $i                    = 0;
                    $fieldName            = 'SKU';
                    $fieldIndex           = 0;
                    $hasSkuField          = false;
                    $wishOfflineTaskModel = UebModel::model('WishOfflineTask');
                    $row                  = 0;
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
                                    'sku'           =>	trim($fields[$fieldIndex]),
                                    'account_id'    =>  $account,
                                    'status'        =>	0,
                                    'create_user_id'=>	Yii::app()->user->id,
                                    'create_time'   =>	date('Y-m-d H:i:s'),
                                    'type'          => 1,//手工录入
                                );
    						}
                            if($row % 50 ==0){
                                $res = WishOfflineTask::model()->insertBatch($data);
                                $data = array();
                            }
    					}
    				}
                    if(!empty($data)){
                        $res = WishOfflineTask::model()->insertBatch($data);
                    }
    			}
    			echo $this->successJson(array(
    					'message'=>Yii::t('amazon_product', 'Upload success'),
    					'callbackType' => 'closeCurrent'
    			));
    			Yii::app()->end();
    		}catch (Exception $e){
    			echo $this->failureJson(array('message'=>$e->getMessage()));
    			Yii::app()->end();
    		}
    	}else{
    		//获取全部可用账号
    		$accounts = UebModel::model('WishAccount')->getAvailableIdNamePairs();
    			
    		$this->render('importcsvoffline', array(
    				'accounts'=>$accounts,'model'=>$this->_model
    		));
    	}    	
    }
    
    /**
     * @desc 下线任务
     * @link /wish/wishlisting/offlinetask
     */
    public function actionOfflinetask(){
        set_time_limit(2*3600);
        ini_set('memory_limit','2048M');
        ini_set('display_errors', true);

        $time = time();            
        $type = Yii::app()->request->getParam("type");
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
                exit('执行超过10小时');
            }
            $wishOfflineTaskModel = new WishOfflineTask();
            $taskListing = $wishOfflineTaskModel->getWishTaskListByStatus(WishOfflineTask::UPLOAD_STATUS_PENDING);
            if($taskListing){
                foreach ($taskListing as $listing){
                    $data = array(
                        'process_time' => date('Y-m-d H:i:s'),
                        'status' => 1,
                    );
                    $sku = $listing['sku'];
                    //$parentInfo = $this->_model->getProductJoinVariantsBySkus($sku, $listing['account_id']);
                    $parentInfo = $this->_model->getListingSkusForOffline($sku, $listing['account_id']);
                    if($parentInfo){
                        WishOfflineTask::model()->getDbConnection()->createCommand()->update("ueb_wish_offline_task", $data, "id = " . $listing['id']);
                    }else{
                        WishOfflineTask::model()->getDbConnection()->createCommand()->update("ueb_wish_offline_task", array('status'=>3),"id = " . $listing['id']);
                    }
                }
            } else {
                $flag_while = false;
            }
        }

        while( $flag_online ){
            $exe_time   =  time();
            if(($exe_time - $time) >= 36000 ){
                exit('执行超过10小时');
            }
            $wishOfflineTaskModel = new WishOfflineTask();
            $taskListing = $wishOfflineTaskModel->getWishTaskListByStatus(WishOfflineTask::UPLOAD_STATUS_PROCESSING);
            if($taskListing){
                $newTaskListing = array();
                foreach ($taskListing as $listing){
                    $sku = $listing['sku'];
                    $newTaskListing[$listing['account_id']]['orig'][] = $sku;
                    //$parentInfo = $this->_model->getProductJoinVariantsBySkus($sku, $listing['account_id']);
                    $parentInfo = $this->_model->getListingSkusForOffline($sku, $listing['account_id']);
                    if($parentInfo){
                        //判断主子sku
                        if($parentInfo['psku'] == $sku){
                            $newTaskListing[$listing['account_id']]['parent'][$sku] = $parentInfo['parent_sku'];
                        }else{
                            $newTaskListing[$listing['account_id']]['child'][$sku] = $parentInfo['online_sku'];
                        }
                    }else{
                        WishOfflineTask::model()->getDbConnection()->createCommand()->update("ueb_wish_offline_task", array('status'=>3),"id = " . $listing['id'] );
                    }
                }
                foreach ($newTaskListing as $accountId=>$itemData){
                    $parentSku = isset($itemData['parent'])?$itemData['parent']:null;
                    $childSku = isset($itemData['child'])?$itemData['child']:null;

                    $this->_doOffline($parentSku, $accountId, true);
                    $this->_doOffline($childSku, $accountId, false);
                }
            } else {
                $flag_online = false;
            }
        }

        echo "finished";
    }
    /**
     * @desc 执行下线动作
     * @param unknown $itemData
     * @param unknown $accountId
     * @param string $isParent
     * @return boolean
     */
    private function _doOffline($itemData, $accountId, $isParent = true){
    	if(!$itemData){
    		return false;
    	}
    	
    	$wishOfflineTaskModel = new WishOfflineTask;
    	$parentSku = $itemData;
    	
    	if($isParent){
    		$result = $this->_model->disabledProduct($accountId, $itemData);
    	}else{
    		$result = $this->_model->disabledVariants($accountId, $itemData);
    	}
    	if($result['success']){
    		$successSkus = array();
    		foreach ($result['success'] as $sucsku){
    			$keys = array_keys($itemData, $sucsku);
    			if($keys)
    				$successSkus = array_merge($successSkus, $keys);
    		}
    		$data = array('status'=>WishOfflineTask::UPLOAD_STATUS_SUCCESS,
    				'process_time'=>date("Y-m-d H:i:s"),
    				'response_msg'=>'success');
    		$skus = '';
    		foreach ($successSkus as $item){
    			$skus .= "'{$item}',";
    		}
    		$skus = trim($skus, ',');
    		$conditions = 'account_id='.$accountId.' AND sku in('. $skus .')';
    		$wishOfflineTaskModel->updateWishTask($data, $conditions);
    		
    		// 更新本地产品
    		if($isParent){
    			UebModel::model('WishListing')->disabledWishProductByOnlineSku($result['success'], $accountId);
    		}else{
    			UebModel::model('WishVariants')->disabledWishVariantsByOnlineSku($result['success'], $accountId);
    		}
    	}
    	if($result['failure']){
    		$failureSkus = array();
    		$data = array('status'=>WishOfflineTask::UPLOAD_STATUS_FAILURE,
    				'process_time'=>date("Y-m-d H:i:s"));
    		foreach ($result['failure'] as $failsku){
    			$keys = array_keys($itemData, $failsku);
    			if($keys){
    				$data['response_msg'] = isset($result['errorMsg'][$failsku])?$result['errorMsg'][$failsku]:'unkown';
    				$skus = "'" . implode("','", $keys) . "'";
    				$conditions = 'account_id='.$accountId.' AND sku in('. $skus .')';
    				$wishOfflineTaskModel->updateWishTask($data, $conditions);
    			}
    		}
    	}
    	 
//    	if(!$result['success']){
//    		echo $this->_model->getExceptionMessage();
//    	}
    }
    
    /**
     * @desc 创建加密sku
     */
    public function actionCreatesku(){
		$this->render('createsku');    	
    }
    
    public function actionCreateencrysku(){
    	$sku = Yii::app()->request->getParam('sku');
    	if($sku){
    		$encrySku = new encryptSku();
    		$ensku = $encrySku->getEncryptSku($sku);
    		echo $this->successJson(array('message'=>$ensku));
    	}else{
    		echo $this->failureJson(array('message'=>Yii::t('wish_listing', 'Please Input Main SKU')));
    	}
    }

    /**
     * [actionUpvaration description]
     * @link /wish/wishlisting/Upvaration
     */
    public function actionUpvaration() {
        set_time_limit(5*3600);
        $WishListing = WishListing::model();
        $wishTableName = $WishListing->tableName();
        $total = $WishListing->dbConnection->createCommand()
                            ->select('count(*) as total')
                            ->from($wishTableName)
                            ->where('1')
                            ->queryRow();
        $total = $total['total'];  
        $pageSize = 2000;

        $pagecount = ceil($total/$pageSize);
        for($page=1; $page<=$pagecount; $page++) {
            $offset = ($page-1) * $pageSize;

            $rs = $WishListing->dbConnection->createCommand()
                                ->select('id')
                                ->from($wishTableName)
                                ->where('1')
                                ->limit($pageSize,$offset)
                                ->queryAll();     
            if ($rs) {
                foreach ($rs as $key => $v) {
                    $rs2 = $WishListing->dbConnection->createCommand()
                                ->select('count(*) as num')
                                ->from('ueb_listing_variants')
                                ->where("listing_id={$v['id']}")
                                ->queryRow();   
                    if ($rs2) {
                        //echo 'listing_id:'.$v['id']."<br>";
                        $is_varation = !empty($rs2['num']) && $rs2['num']>1 ? 1 : 0;

                        $WishListing->dbConnection->createCommand()
                                ->update('ueb_wish_listing',array('is_varation'=>$is_varation),"id={$v['id']}");
                    }
                }
            }
            //break; 
        }
        die('finish') ;
    }

    
    /**
	 * 导入下架任务表删除没有线上sku的数据
	 */
    public function actionDeleteNoOnlineSku(){
        WishProduct::model()->getDbConnection()->createCommand()->delete("ueb_wish_offline_task", "response_msg = 'no online sku'" );
    }

    /**
     * @desc 拉取产品列表
     * @author yangsh
     * @since  2016-06-20
     * @link  /wish/wishlisting/getlisting/account_id/19
     *        /wish/wishlisting/getlisting/account_id/19/since/2016-06-22
     */
    public function actionGetlisting() {
        set_time_limit(5*3600);
        //ini_set('memory_limit','2048M');
        $isBug = isset($_REQUEST['bug']);
        if($isBug){
        	error_reporting(E_ALL);
        }else{
        	error_reporting(0);
        }
        
        ini_set('display_errors', true);
        
        $accountID  = trim(Yii::app()->request->getParam('account_id',''));
        $since      = trim(Yii::app()->request->getParam('since',date('Y-m-d',strtotime('-15 days')) ));
        $offset     = trim(Yii::app()->request->getParam('offset',0));//取账号ID间隔多少小时
        
        //参数验证
        $validateMsg = '';
        if ( !empty($accountID) && !preg_match('/^\d+$/',$accountID)) {
            $validateMsg .= 'account_id is invalid;';
        }   
        $since == 'all' && $since = '';//all为全部
        if ( $since != '' ) {
            $pattern = '/^([0-9]{4}-(0?[0-9]|1[0-2])-(0?[0-9]|[1-2][0-9]|3[0-1]))$/';
            if ( !preg_match($pattern,$since) ) {
                $validateMsg .= 'since is invalid;';
            }
        }
        if ($validateMsg != '') {
            Yii::app ()->end($validateMsg);
        }
        
        if ($accountID) {
            //创建日志
            $logtxt     = '';
            $wishLog    = new WishLog();
            $logID      = $wishLog->prepareLog($accountID, WishListing::EVENT_NAME);
            if (!$logID) {
                echo 'Insert prepareLog failure';
                Yii::app()->end();
            }
            //检查账号是否可以拉取
            $checkRunning = $wishLog->checkRunning($accountID, WishListing::EVENT_NAME);
            if (!$checkRunning) {
                $wishLog->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
                echo 'There Exists An Active Event';
                Yii::app()->end();
            }
            //插入本次log参数日志(用来记录请求的参数)
            $time = date('Y-m-d H:i:s');
            $eventLogID = $wishLog->saveEventLog(WishListing::EVENT_NAME, array(
                    'log_id'        => $logID,
                    'account_id'    => $accountID,
                    'start_time'    => $time,
                    'end_time'      => $time,
            ));
            //设置日志正在运行
            $wishLog->setRunning($logID);

            //开始下载listing
            $model = new WishListingDownload();
            $isOk  = $model ->setAccountID ( $accountID )
                            ->setSince( $since )
                            ->startDownWishListing();
            // 5. 设置日志status
            if ( $isOk ) {
                $wishLog->setSuccess($logID);
                $wishLog->saveEventStatus(WishListingDownload::EVENT_NAME, $eventLogID, WishLog::STATUS_SUCCESS);
            } else {
                echo $model->getErrorMessage();
                $wishLog->setFailure ( $logID, $model->getErrorMessage());
                $wishLog->saveEventStatus ( WishListingDownload::EVENT_NAME, $eventLogID, WishLog::STATUS_FAILURE );
              //  $result .= $model->getErrorMessage(); 
            }
            $flag = $isOk ? 'Success' : 'Failure';
            $result = json_encode($_REQUEST).'========'.$flag.'========'.$model->getErrorMessage();
            echo $result."\r\n<br>";            
            //MHelper::writefilelog('wish/wishlisting/'.date("Ymd").'/'.$accountID.'/result_'.$accountID.'_'.$flag.'.log', $result."\r\n");
        } else {           
            //按小时取分组法,每个账号5分钟
            $accountIDs = WishAccount::model()->getGroupAccounts($offset);
            foreach ($accountIDs as $account_id) {
                $url = Yii::app()->request->hostInfo.'/' . $this->route . '/account_id/' . $account_id
                . '/since/' . $since . '/offset/'.$offset ;
                if($isBug) $url .= "/bug/".$isBug;
                echo $url." <br>\r\n";
                MHelper::runThreadBySocket ( $url );
                sleep(300);
            }
        }
        Yii::app()->end('finish');
    }


    /**
     * @desc 批量删除
     * @throws Exception
     */
    public function actionBatchdelete(){
    	$ids = Yii::app()->request->getParam('ids');
    	try{
    		if(empty($ids)){
    			throw new Exception("没有指定记录");
    		}
    		//删除同时要删除子表
    		$idArr = explode(",", $ids);
    		$successRes = WishListing::model()->batchDeleteProductByIds($idArr);
    		if($successRes){
    			echo $this->successJson(array('message'=>Yii::t('system', 'Delete successful')));
    			Yii::app()->end();
    		}else{
    			throw  new Exception(Yii::t("system", 'Delete failure'));
    		}
    	}catch (Exception $e){
    		echo $this->failureJson(
    				array(
    						'message'=>$e->getMessage()
    				)
    		);
    		Yii::app()->end();
    	}
    }
    


    /**
     * @desc wish所有停售产品，在线listing直接下架
     * @link /wish/wishlisting/autoshelfproducts/accountID/1/sku/111
     */
    public function actionAutoshelfproducts() {
        set_time_limit(5*3600);
        ini_set('display_errors', true);
        error_reporting(E_ALL);

        $warehouseSkuMapModel = new WarehouseSkuMap();
        $wishVariantsModel    = new WishVariants();
        $wishListingModel     = new WishListing();
        $logModel             = new WishLog();
        $wishLogOfflineModel  = new WishLogOffline();

        //账号
        $accountID = Yii::app()->request->getParam('accountID');

        //指定某个特定sku----用于测试
        $setSku = Yii::app()->request->getParam('sku');

        $select    = 't.sku';
        $eventName = 'auto_shelf_products';
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
                    $command = $wishVariantsModel->getDbConnection()->createCommand()
                        ->from($wishVariantsModel->tableName() . " as t")
                        ->leftJoin($wishListingModel->tableName()." as p", "p.id=t.listing_id")
                        ->select("t.id,t.sku,p.account_id,t.product_id,t.online_sku,p.is_varation,t.inventory,p.is_varation")
                        ->where('t.account_id = '.$accountID)
                        ->andWhere("p.review_status IN ('".WishListing::REVIEW_STATUS_APPROVED."','".WishListing::REVIEW_STATUS_PENDING."') ")
                        ->andWhere("t.enabled = 1");
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
                        $nums = strrpos($listingValue['sku'], '.');
                        if($nums){
                            $nums += 3;
                            $newSku = substr($listingValue['sku'], 0,$nums);
                            $skuArr[] = $newSku;
                        }else{
                            $skuArr[] = $listingValue['sku'];
                        }
                    }

                    //数组去重
                    $skuUnique = array_unique($skuArr);
                        
                    $conditions = 't.available_qty <= :available_qty AND t.warehouse_id = :warehouse_id AND p.product_is_multi != 2 AND p.product_status IN(6,7) AND t.sku IN('.MHelper::simplode($skuUnique).')';
                    $param = array(
                        ':available_qty'  => 0, 
                        ':warehouse_id'   => WarehouseSkuMap::WARE_HOUSE_GM
                    );

                    // $limits = "{$offset},{$limit}";
                    $skuList = $warehouseSkuMapModel->getSkuListLeftJoinProductByCondition($conditions, $param, '', $select);
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

                        $aWhInfo = WishOverseasWarehouse::model()->getWarehouseInfoByProductID($variant['product_id']);
                        if ($aWhInfo) {//如果是海外仓listing
                            continue;
                        }                       

                        // 停售的，满足wish平台该账号该SKU过去7天销量≥25个的，下架拦截
                        // 停售的待清仓的，满足wish产品下架规则的链接，wish平台该账号该SKU过去9天订单总金额≥$500的，下架拦截
                        $offlineRet = false;                        
                        $holdedInfo = WishOrderStatistics::model()->getListByCondition("account_id = ".$accountID." AND sku ='".$variant['sku']."'");                     
                        if ($holdedInfo) {
                            foreach($holdedInfo as $val){
                                $holdedRet = false;
                                if ($val['type'] == 1 || $val['type'] == 2){
                                    $addData = array(
                                        'account_id'   => $accountID,
                                        'sku'          => $variant['sku'],
                                        'variation_id' => $variant['id'],
                                        'product_id'   => $variant['product_id'],
                                        'type'         => $val['type'],
                                        'total'        => $val['total'],
                                        'source_type'  => 1,    //拦载来源类型（1=autoshelfproducts，2=autoimportofflinetask）
                                        'create_time'  => date('Y-m-d H:i:s')
                                    );
                                    //拦截的数据更新入库
                                    $holdedRet = WishListingHoldedOffline::model()->setHoldedInfo($addData);
                                    if($holdedRet) $offlineRet = true;
                                }
                            }
                            if($offlineRet) continue;   //有拦截成功才跳出
                        }

                        $time    = date("Y-m-d H:i:s");
                        $message = '';

                        //判断是否是子sku
                        if($variant['is_varation'] == 1){
                            $result = $this->_model->disabledVariants($accountID, $variant['online_sku']);
                        }else{
                            $result = $this->_model->disabledProduct($accountID, $variant['online_sku']);
                        }

                        if($result['success']){
                            //更新本地
                            $wishVariantsModel->disabledWishVariantsByOnlineSku($result['success'], $accountID);
                            $status  = 1;//成功
                            $message = '下架成功';
                        }else{
                            $status  = 0;//失败
                            $message = isset($result['errorMsg'][$variant['sku']]) ? $result['errorMsg'][$variant['sku']] : '';
                        }

                        $addData = array(
                            'product_id'        => $variant['product_id'],
                            'sku'               => $variant['sku'],
                            'account_id'        => $variant['account_id'],
                            'event'             => 'autoshelfproducts',
                            'status'            => $status,
                            'inventory'         => $variant['inventory'],                            
                            'message'           => $message,
                            'start_time'        => $time,
                            'response_time'     => date("Y-m-d H:i:s"),
                            'operation_user_id' => 1
                        );

                        $wishLogOfflineModel->savePrepareLog($addData);
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
            $accountList = WishAccount::model()->getIdNamePairs();
            foreach($accountList as $key => $value){
                MHelper::runThreadSOCKET('/'.$this->route.'/accountID/' . $key);
                sleep(1);
            }
        }
    }
    
    /**
     * @link /wish/wishlisting/test
     */
    public function actionTest(){
    	set_time_limit(3600);
    	error_reporting(E_ALL);
    	ini_set("display_errors", true);
    	$ids = array(
    			"628594","915539","1259095","1322468","1330376","1255671","1309432","1324128","1318154","1318384","1319050","1320389","1320876","1334962",
    			"1335356","1335628","1335799","1343124","1143144","1311224","1268771","1345095","992691","861543","1348272","903568","1256025","1265602"
    	);
    	$wishOfflineModel = new WishOffline();
    	$wishListingModel = new WishListing();

    	$listings = $wishListingModel->findAll("id in(".MHelper::simplode($ids).")");
    	foreach ($listings as $list){
    		$result = $wishListingModel->disabledProduct($list['account_id'], $list['parent_sku']);
    		echo $wishListingModel->getExceptionMessage(),"<br/>";

    		$status = empty($result['success']) ? 0: 1;
    		$wishOfflineModel->getDbConnection()->createCommand()->insert($wishOfflineModel->tableName(), 
    																array(
    																	'product_id'=>$list['product_id'],
    																	'sku'		=>$list['sku'],
    																	'account_id'	=>	$list['account_id'],
    																	'event'	=>	'offline',
    																	'status'	=>	$status,
    																		'message'=>$wishListingModel->getExceptionMessage(),
    																		'start_time'=>date("Y-m-d H:i:s"),
    																		'response_time'=>date("Y-m-d H:i:s"),
    																		'operation_user_id'=>intval(Yii::app()->user->id)
    																));
    	}
    	//子sku
    	$vids = array(
    			"951555","974982","1303868","1308036","1319436","1386269","1405846","1478536","1505037","1520040","1738267","1739804","1850282",
    			"1899189","1900358","1901591","1903581","1904278","1904759","1907252","1907271","1908422","1911121","1917306","1919225","1924884",
    			"1927829","1933869","1941350","1945946","1955160","1955161","1955832","1955833","1956805","1956806","1957340","1957341","1959428",
    			"1959429","1962192","1963029","1963641","1965427","1965819","1965908","1967022","1967152","1968802","1970025","1973849","1975875",
    			"1977309","1977921","1979203","1979437","1979722","1979854","1983406","1985635",
    			"1986384","1987711","1992967","2003985","2004886","2004965","2005498","2005926","2021433","2024507","2025397","2026208","2033460"
    	);
    	$wishVariationModel = new WishVariants();
    	$vlistings = $wishVariationModel->findAll("id in(".MHelper::simplode($vids).")");
    	foreach ($vlistings as $list){
    		$result = $wishListingModel->disabledVariants($list['account_id'], $list['online_sku']);
    		echo $wishListingModel->getExceptionMessage(),"<br/>";
    		$status = empty($result['success']) ? 0: 1;
    		$wishOfflineModel->getDbConnection()->createCommand()->insert($wishOfflineModel->tableName(),
    				array(
    						'product_id'=>$list['product_id'],
    						'sku'		=>$list['sku'],
    						'account_id'	=>	$list['account_id'],
    						'event'	=>	'offline',
    						'status'	=>	$status,
    						'message'=>$wishListingModel->getExceptionMessage(),
    						'start_time'=>date("Y-m-d H:i:s"),
    						'response_time'=>date("Y-m-d H:i:s"),
    						'operation_user_id'=>intval(Yii::app()->user->id)
    				));
    	}
    }


    /**
     * 复制刊登sku到待刊登列表
     */
    public function ActionCopytoproductaddlist(){
        set_time_limit(3600);
        $accounts_limit = array();

        //判断是否有权限复制刊登
        $wishCopyListingSellerModel = new WishCopyListingSeller();
        $sellerList = $wishCopyListingSellerModel->getListByCondition('seller_user_id','id>0');
        if(!isset(Yii::app()->user->id) || !in_array(Yii::app()->user->id,$sellerList)){
            echo $this->failureJson(array('message'=>'没有权限进行复制刊登操作'));
            exit;
        }
        
        $ids = Yii::app()->request->getParam('ids');
        $accountList = WishAccount::model()->getIdNamePairs();

        //销售人员只能看指定分配的账号数据      
        $accountIdArr = array();
        if(isset(Yii::app()->user->id)){
            $accountIdArr = WishAccountSeller::model()->getListByCondition('account_id','seller_user_id = '.Yii::app()->user->id);
            if($accountIdArr && $accountList){
                foreach($accountList as $key => $item){
                    if (in_array($key,$accountIdArr)){
                        $accounts_limit[$key] = $item;
                    }
                }
            }
        }
        //没有限制的则看全部账号
        if (!$accounts_limit) $accounts_limit = $accountList;        

        $this->render('copylisting', array('model'=>$this->_model, 'accountList'=>$accounts_limit, 'ids'=>$ids));
    }


    /**
     * 复制刊登sku到待刊登列表
     */
    public function ActionCopytoproductaddsave(){
        ini_set('memory_limit','2048M');
        set_time_limit(3600);
        ini_set("display_errors", true);

        //判断是否有权限复制刊登
        $wishCopyListingSellerModel = new WishCopyListingSeller();
        $sellerList = $wishCopyListingSellerModel->getListByCondition('seller_user_id','id>0');
        if(!isset(Yii::app()->user->id) || !in_array(Yii::app()->user->id,$sellerList)){
            echo $this->failureJson(array('message'=>'没有权限进行复制刊登操作'));
            exit;
        }

        //取出产品管理里的数据
        $accountArr = Yii::app()->request->getParam('WishListing');
        $ids = $accountArr['ids'];
        $accountIdArr = $accountArr['account_id'];

        if(!$ids){
            echo $this->failureJson(array('message'=>'请选择'));
            exit;
        }

        if(!$accountIdArr || !is_array($accountIdArr)){
            echo $this->failureJson(array('message'=>'请选择要刊登的账号'));
            exit;
        }

        $fields = 'id,account_id,sku,product_id,tags,brand,name';
        $where = 'id IN('.$ids.')';
        $listingInfo = $this->_model->getListByCondition($fields,$where);
        if(!$listingInfo){
            echo $this->failureJson(array('message'=>'没有找到刊登的数据'));
            exit;
        }

        $addType = WishProductAdd::ADD_TYPE_COPY;
        $batchAddModel = new WishLogBatchProductAdd();

        //循环取出要刊登的数据
        foreach ($listingInfo as $info) {
            foreach ($accountIdArr as $accountValue) {
                if($info['account_id'] == $accountValue){
                    continue;
                }

                $result = $this->_model->copyListingToAdd($info, $accountValue, $addType);
                if(!$result[0]){
                    $insertData = array(
                        'listing_id'     => $info['id'],
                        'product_id'     => $info['product_id'],
                        'create_user_id' => isset(Yii::app()->user->id) ? Yii::app()->user->id : 0,
                        'message'        => $result[1],
                        'create_time'    => date('Y-m-d H:i:s')
                    );
                    $batchAddModel->savePrepareLog($insertData);
                    continue;
                }
            }
        }

        $jsonData = array(
                'message' => '复制刊登成功',
                'forward' =>'/wish/wishlisting/list',
                'navTabId'=> 'page' . WishListing::getIndexNavTabId(),
                'callbackType'=>'closeCurrent'
            );
        echo $this->successJson($jsonData);        
    }
	//手动同步listing
	public function actionManualGetListing(){
		$this->render("manualgetlisting");
	}
	//手动同步listing(一周内)
	public function actionManualSaveListing(){
		$accountID = Yii::app()->request->getParam('account_id');
		$since = trim(Yii::app()->request->getParam('since',date('Y-m-d',strtotime('-7 days')) ));

		try{
			if(!$accountID){
				throw new Exception('指定账号');
			}
			if ( $since != '' ) {
				$pattern = '/^([0-9]{4}-(0?[0-9]|1[0-2])-(0?[0-9]|[1-2][0-9]|3[0-1]))$/';
				if ( !preg_match($pattern,$since) ) {
					throw new Exception('时间错误');
				}
			}

			//下载listing
			$model = new WishListingDownload();
			$isOk = $model ->setAccountID ( $accountID )
						->setSince( $since )
						->startDownWishListing();

			if ( !$isOk ) {
				throw new Exception($model->getErrorMessage());
			}
			echo $this->successJson(array('message'=>'拉取成功'));
		}catch(Exception $e){
			echo $this->failureJson(array('message'=>$e->getMessage()));
		}
		Yii::app()->end();
	}



    // 产品编辑
	public function actionEdit($id){
        error_reporting(1024);

	    $wishListingModel =  WishListing::model();
	    $wishListingVariantModel = WishVariants::model();

        $productInfo = $wishListingModel->findByPk($id);

	    if (!$productInfo) {
	        throw new \Exception('Product not exists');
        }

        // 编辑之前拉取最新listing 信息
        try {
            $downloadModel = new WishListingDownload();
            $downloadModel->pullSingleItem($productInfo['parent_sku'], $productInfo['account_id']);
        }catch (\Exception $e) {
            echo $this->failureJson(array('message' => $e->getMessage()));
            Yii::app()->end();
        }

        //reload product info
        $productInfo = $wishListingModel->findByPk($productInfo['id']);

        $selectedImg = array();
        if($productInfo['main_image']){
            $selectedImg[] = $productInfo['main_image'];
        }
        if($productInfo['extra_images']){
          #  var_dump(explode("|", $productInfo['extra_images']));
           $selectedImg = array_merge($selectedImg, explode("|", $productInfo['extra_images']));
        }

        $additionalData = WishListingExtend::model()->getExtendByParentId($productInfo['id']);

        $productInfo['description'] = isset($additionalData['description'])? $additionalData['description'] :'';


        $marketAttributeList = AttributeMarketOmsMap::model()->getOmsAttrIdsByPlatAttrName(Platform::CODE_WISH, 0);

        $variations = $wishListingVariantModel->getWishProductVarantListByProductId($productInfo['product_id']);

        $attributeList = array();
        foreach ($marketAttributeList as $attribute) {
            $attributeList[] = $attribute['platform_attr_name'];
        }
        //$accountInfo = WishAccount::getAccountInfoByIds($productInfo['account_id']);


        $tags = array();
        foreach(explode(',', $productInfo['tags']) as $tag) {
            $tags[] = $tag;
        }
        foreach(range(0, 9) as $i) {
            if (!isset($tags[$i])) {
                $tags[$i] = "";
            }
        }

        $productInfo['tags'] = $tags;


        /**@ 获取产品信息*/
        // 更改为拉取JAVA组图片API接口 ketu.lai
        $skuImg = ProductImageAdd::getImageUrlFromRestfulBySku($productInfo['sku'], null, 'normal', 100, 100,
            Platform::CODE_WISH);

        /**
         * 修复java api接口无主图返回问题
         */
        if (isset($skuImg['ft']) && !isset($skuImg['zt'])) {
            $skuImg['zt'] = $skuImg['ft'];
        }

        $this->render(
            'edit',
            array(
                'skuImg'=> $skuImg,
                'selectedImg'=> $selectedImg,
                'skuInfo'   =>	$productInfo,
                'variations' =>$variations,
                'attributeList'=> $attributeList,
                'currentNavTab' => 'page' . UebModel::model('Menu')->getIdByUrl('/wish/wishproduct/edit'),
            )
        );


    }

    //保存产品编辑
    public function actionSaveEdit()
    {
        try {
            $id = Yii::app()->request->getParam('id');
            $variants = Yii::app()->request->getParam('variants', array());
            $skuInfo = Yii::app()->request->getParam('skuInfo', array());
            $skuImage = Yii::app()->request->getParam('skuImage', array());
            $uploadedImages = Yii::app()->request->getParam('uploadedImages', '');

            $wishListingModel = WishListing::model();
            $wishListingVariantModel = WishVariants::model();
            $productInfo = $wishListingModel->findByPk($id);

            if (!$productInfo) {
                throw new \Exception('Product not exists');
            }
            $existsVariants = $wishListingVariantModel->getWishProductVarantListByProductId($productInfo['product_id']);
            $existsVariantMapper = array();
            foreach ($existsVariants as $variant) {
                $existsVariantMapper[$variant['sku']] = $variant['online_sku'];
            }

            $remoteImages = array();

            if ($skuImage) {
                $remoteImages = ProductImageAdd::getImagesFromRemoteAddressByFileName(array_values($skuImage), $productInfo['sku'], $productInfo['account_id'], Platform::CODE_WISH);
                if (count($skuImage) != count($remoteImages)) {
                    throw new \Exception("Get remote images from API failed");
                }

            } else {
                $remoteImages = array_map(function ($k) {
                        return array_shift(explode('?', $k));
                }, explode(",", $uploadedImages));
            }

            $mainProductData = array(
                // 'id'=> $productInfo['product_id'],
                'sku'=> $productInfo['sku'],
                'online_sku'=>$productInfo['parent_sku'],
                'name' => $skuInfo['subject'],
                'description' => $skuInfo['detail'],
                'tags' => join(',', $skuInfo['tags']),
                'brand' => $skuInfo['brand'],
                'main_image' => array_shift($remoteImages),
                'extra_images' => join('|', $remoteImages)
            );

            $variantNeedUpdate = array();
            $variantNeedCreate = array();
            $variantNeedDisable = array();

            foreach ($variants as $variant) {
                if (!isset($variant['action'])) {
                    throw new \Exception("Action parameter lost for this variant");
                }
                switch ($variant['action']) {
                    case 'create':
                        $skuEncrypt = new encryptSku();
                        $variantNeedCreate[] = array(
                            'parent_sku' => $mainProductData['online_sku'],
                            'sku' => $variant['sku'],
                            'online_sku' => $skuEncrypt->getEncryptSku($variant['sku']),
                            'color' => $variant['color'],
                            'size' => $variant['size'],
                            'inventory' => $variant['inventory'],
                            'price' => $variant['price'],
                            'shipping' => $variant['shipping'],
                            'msrp' => $variant['msrp'],
                            'upload_action' => WishProductVariantsUpdate::VARIANT_ACTION_CREATE,
                            //'main_image'=> ''
                        );
                        break;

                    case 'update':
                        $variantNeedUpdate[] = array(
                            'online_sku' => $existsVariantMapper[$variant['sku']],
                            'sku' => $variant['sku'],
                            'color' => $variant['color'],
                            'size' => $variant['size'],
                            'inventory' => $variant['inventory'],
                            'price' => $variant['price'],
                            'shipping' => $variant['shipping'],
                            'msrp' => $variant['msrp'],
                            'upload_action' => WishProductVariantsUpdate::VARIANT_ACTION_UPDATE,
                            //'main_image'=> ''
                        );
                        break;
                    case 'disable':
                        //beiyong
                        break;
                    default:
                        throw new \Exception("Action parameter lost for this variant");
                        break;
                }
            }

            $variantsData = array_merge($variantNeedCreate, $variantNeedUpdate, $variantNeedDisable);
            $mainProductData['variants'] = $variantsData;
            WishProductUpdate::model()->saveInfo($productInfo['product_id'], $productInfo['account_id'], $mainProductData);
            echo $this->successJson(array(
                'message'=> Yii::t('wish', 'Update info save successful.')
            ));
        }catch (\Exception $e) {
            echo $this->failureJson(array(
                'message'=> Yii::t('wish',  $e->getMessage())
            ));
        }
    }
} 