<?php
/**
 * @desc Aliexpress listing
 * @author Gordon
 * @since 2015-06-25
 */
class AliexpressproductController extends UebController{
    /**
     * AliexpressProduct 模型
     * @var unknown
     */
	protected $_model = null; 
	
	/**
	 * (non-PHPdoc)
	 * @see CController::init()
	 */
	public function  init(){
		$this->_model = new AliexpressProduct();
		parent::init();
	}
    /**
     * @desc 访问过滤配置
     * @see CController::accessRules()
     */
    public function accessRules() {
        return array(
			array(
				'allow',
				'users' => array('*'),
				'actions' => array('getproducts','getproductlist')
			),
		);
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
	
    /**
     * @desc 批量导出指定查询条件数据
     */
    public function actionDownloadExcel() {
        
        set_time_limit(3600);
        ini_set('display_errors', true);
        ini_set('memory_limit','2048M');
        error_reporting(1);
        //LEFT JOIN ueb_user d
        //ON d.id = c.seller_id
        $allArray = array();
    	$AccountID     = Yii::app()->request->getParam('a');
    	$Status        = Yii::app()->request->getParam('b');
    	$GmtStart      = Yii::app()->request->getParam('c');
    	$GmtEnd        = Yii::app()->request->getParam('d');
    	$aliexpressProductModel = new AliexpressProduct();
    	$exeSql = "
                    SELECT DISTINCT d.sku,a.product_status_type,a.gmt_create,a.aliexpress_product_id,c.seller_id,b.short_name
                    from ueb_aliexpress_product a 
                    LEFT JOIN ueb_aliexpress_account b
                    ON a.account_id = b.id
                    LEFT JOIN ueb_aliexpress_product_seller_relation c
                    ON a.aliexpress_product_id = c.item_id
                    LEFT JOIN ueb_aliexpress_product_variation d
                    ON d.product_id = a.id
                    WHERE 
                    a.sku IS NOT NULL AND
                    a.sku != '' AND
                    c.seller_id IS NOT NULL AND   	    
    	";
    	(isset($AccountID) && !empty($AccountID)) && $exeSql .= " a.account_id = {$AccountID} AND ";
    	if (isset($Status) && !empty($Status)) {
    	    $exeSql .= " a.product_status_type = '{$Status}' AND ";    	
    	} else {
    	    $exeSql .= " a.product_status_type = 'onSelling' AND ";
    	    $Status  = 'onSelling';
    	}
    	    	
    	if (isset($GmtStart) && isset($GmtEnd)) {
    	    $exeSql  .= " a.gmt_create BETWEEN '{$GmtStart}' AND '{$GmtEnd}' ";
    	} else {
    	    $GmtStart = date('Y-m-d H:i:s',time() - 30 * 24 * 3600);
    	    $GmtEnd   = date('Y-m-d H:i:s');
    	    $exeSql  .= " a.gmt_create BETWEEN '{$GmtStart}' AND '{$GmtEnd}' ";
    	}
    	$exeSql .= " ORDER BY d.sku DESC ";
    	$exeSql .= " limit 0,10000 ";       
    	$allData = $aliexpressProductModel->getDbConnection()->createCommand($exeSql)->queryAll();
    	//echo $exeSql;
    	$statusArray = array(
    	    'onSelling' => '上架',
    	    'offline' => '下架',
    	    'auditing' => '审核中',
    	    'editingRequired' => '审核不通过'
    	);
    	if ($allData){
    	    foreach ($allData as $data){
    	        if (!isset($allArray[$data['sku']])){
    	            $allArray[$data['sku']] = array();
    	            $allArray[$data['sku']][0] = "";
    	            $allArray[$data['sku']][1] = "\n";
    	            $allArray[$data['sku']][2] = "";
    	        }
    	        $userInfo = User::model()->getUserNameById($data['seller_id']);
    	        $allArray[$data['sku']][0]     = $data['sku'];
    	        $allArray[$data['sku']][1]    .= "{$data['short_name']},{$userInfo['user_full_name']}\n";
    	        $allArray[$data['sku']][2]     = $statusArray["$Status"];
    	    }
    	    //$this->print_r($allArray);
    	}
    	$accountName = '';
    	if (isset($AccountID) && !empty($AccountID)){
    	    $accountInfo = AliexpressAccount::model()->getAccountNameById($AccountID);
    	    $accountName = "{$accountInfo}_";
    	} else {
    	    $accountName = "全部用户_";
    	}
    	$excelData = new MyExcel();
    	$excelData->export_excel(
    	    $title = array('SKU', '账号&销售', '上架状态'), 
    	    $data  = $allArray,
    	    $file  = "{$accountName}{$statusArray["$Status"]}_{$GmtStart}_{$GmtEnd}_".time().'.csv',
    	    $limit = 10000,
    	    $output= 1,
    	    $column_width = array(20,30,20)
    	);
    }	
	
    /**
     * 拉取listing
     * @author yangsh
     * @since  2016-06-13
     * @link  /aliexpress/aliexpressproduct/getproductlist/account_id/228  
     *        /aliexpress/aliexpressproduct/getproductlist/account_id/228/status/onSelling
     *        /aliexpress/aliexpressproduct/getproductlist/account_id/228/product_id/32630764585
     */
    public function actionGetproductlist() {
        set_time_limit(0);
        ini_set('display_errors', false);
        error_reporting(0);

        //参数验证
        $accountId          = trim(Yii::app()->request->getParam('account_id',''));//销售账号id,可不传
        $status             = trim(Yii::app()->request->getParam('status',''));//商品业务状态(onSelling,offline,auditing,editingRequired)
        $productId          = trim(Yii::app()->request->getParam('product_id',0));//商品ID,可不传
        $offLineTime        = trim(Yii::app()->request->getParam('offlinetime',0));//商品的剩余有效期, 剩余天数,可不传
        $minute             = trim(Yii::app()->request->getParam('minute',0));//间隔分钟数，多少分钟内更新过的不更新,可不传
        $offset             = trim(Yii::app()->request->getParam('offset',0));//取账号ID间隔多少小时

        $validateMsg        = '';
        $statusTypeList     = AliexpressProductDownload::getProductStatusTypeList();//商品状态列表 
        $minute == 'all' && $minute = '';
        if (!empty($accountId) && !preg_match('/^\d+$/',$accountId)) {
            $validateMsg .= 'account_id is invalid;';
        }
        if (!empty($status) && !in_array($status,$statusTypeList)) {
            $validateMsg .= 'status is invalid;';
        }
        if (!empty($productId) && !is_numeric($productId)) {
            $validateMsg .= 'product_id is invalid;';
        }    
        if (!empty($offLineTime) && !is_numeric($offLineTime)) {
            $validateMsg .= 'offlinetime is invalid;';
        }
        if (!empty($minute) && !is_numeric($minute)) {
            $validateMsg .= 'minute is invalid;';
        }
        if ($validateMsg != '') {
            Yii::app ()->end($validateMsg);
        }
        //start handle params
        if ( $accountId ) {
            if (!empty($status)) {
                $statusTypeList = array($status);
            }
            foreach ($statusTypeList as $statusType) {
                //创建日志
                $aliexpressLog = new AliexpressLog();
                $logId = $aliexpressLog->prepareLog($accountId, AliexpressProductDownload::EVENT_NAME);
                if (!$logId) {
                    echo 'Insert prepareLog failure';
                    continue;
                }
                //检查账号是否可以拉取
                $checkRunning = $aliexpressLog->checkRunning($accountId, AliexpressProductDownload::EVENT_NAME);
                if (!$checkRunning) {
                    $aliexpressLog->setFailure($logId, Yii::t('systems', 'There Exists An Active Event'));
                    echo 'There Exists An Active Event ';
                    continue;
                } 
                //插入本次log参数日志(用来记录请求的参数)
                $time = date('Y-m-d H:i:s');
                $eventLogID = $aliexpressLog->saveEventLog(AliexpressProductDownload::EVENT_NAME, array(
                    'log_id'        => $logId,
                    'account_id'    => $accountId,
                    'start_time'    => $time,
                    'end_time'      => $time,
                ));
                //设置日志正在运行
                $aliexpressLog->setRunning($logId);
                //拉取产品
                $model = new AliexpressProductDownload();
                $isOk  = $model->setAccountID($accountId)
                               ->setProductStatusType($statusType)
                               ->setProductId($productId)
                               ->setOffLineTime($offLineTime)
                               ->setIntervalMinute($minute)
                               ->setExceptedProductIds()
                               ->getAliProducts();
                //更新日志信息
                if( $isOk ){
                    $aliexpressLog->setSuccess($logId, $model->getErrorMessage());
                    $aliexpressLog->saveEventStatus(AliexpressProductDownload::EVENT_NAME, $eventLogID, AliexpressLog::STATUS_SUCCESS);
                }else{
                    echo $model->getErrorMessage()."<br>";
                    $aliexpressLog->setFailure($logId, 'failure');
                    $aliexpressLog->saveEventStatus(AliexpressProductDownload::EVENT_NAME, $eventLogID, AliexpressLog::STATUS_FAILURE);
                }
                $flag = $isOk ? 'Success' : 'Failure';
                $result = json_encode($_REQUEST).'========'.$flag.'========'.$model->getErrorMessage();
                echo $result."\r\n<br>";
                //MHelper::writefilelog('aliexpress/aliexpress_listing/'.date('Ymd').'/'.$accountId.'/run_result'.'.log', $result."\r\n");         
            }
        } else {
            //按小时取分组法,每个账号5分钟
            $accountIDs = AliexpressAccount::model()->getGroupAccounts($offset);
            foreach ($accountIDs as $account_id) {
                $url = Yii::app()->request->hostInfo.'/'.$this->route. '/account_id/' . $account_id 
                . "/status/" . $status . "/productId/" . $productId . "/offLineTime/" . $offLineTime
                . "/minute/" . $minute .'/offset/'.$offset;
                echo $url." <br>\r\n";
                MHelper::runThreadBySocket($url);
                sleep(300);
            }
        }
        Yii::app ()->end('finish');
    }

    /**
     * @desc 拉取listing for test
     * @author yangsh
     * @since 2016-06-13
     * @link   /aliexpress/aliexpressproduct/getproducttest/account_id/234/product_status_type/onSelling
     *        /aliexpress/aliexpressproduct/getproducttest/account_id/234/product_status_type/onSelling/product_id/32683627151
     */
    public function actionGetproducttest() {
        // ini_set('display_errors', true);
        // error_reporting(E_ALL);
        $accountId         = trim(Yii::app()->request->getParam('account_id',234));
        $productStatusType = trim(Yii::app()->request->getParam('product_status_type','onSelling'));
        $productId         = trim(Yii::app()->request->getParam('product_id','32683627151'));
        //if (empty($accountID) || empty($productStatusType) || empty($productID)) {
        if (empty($accountId) ) {
            echo $this->failureJson(array(
                'message' => Yii::t('aliexpress', 'Params Error'),
            ));
            Yii::app()->end();
        }

        //拉取单个产品
       if (!empty($productId)) {
            $request = new FindAeProductByIdRequest();
            $request->setProductId($productId);
            $response = $request->setAccount($accountId)->setRequest()->sendRequest()->getResponse();
            MHelper::printvar($response);
        }
        exit;

        //拉取产品list
        $request = new FindProductInfoListQueryRequest();
        $request->setAccount($accountId);
        $request->setProductStatusType($productStatusType);
        if (!empty($productId)) {
            $request->setProductId($productId);
        }
        $request->setPage(1);
        $request->setPageSize(10);
        MHelper::printvar($request,false);

        $response = $request->setRequest()->sendRequest()->getResponse();        
        MHelper::printvar($response);
    }     
    
    /**
     * @desc 拉取账号产品
     */
   //  public function actionGetproducts() {
   //  	//ini_set('display_errors', true);
   //  	//error_reporting(E_ALL);
   //  	set_time_limit(0);
   //      ini_set('memory_limit','2048M');
   //  	$accountID = Yii::app()->request->getParam('account_id');
   //  	$productStatusType = Yii::app()->request->getParam('product_status_type');
   //  	$productID = Yii::app()->request->getParam('product_id');
   //  	if ($accountID) {
   //  		if (empty($productStatusType))
	  //   		$statusList = array(AliexpressProduct::PRODUCT_STATUS_ONSELLING,
	  //   				AliexpressProduct::PRODUCT_STATUS_OFFLINE,
	  //   				AliexpressProduct::PRODUCT_STATUS_AUDITING,
	  //   				AliexpressProduct::PRODUCT_STATUS_EDITINGREQUIRED
	  //   			);
   //  		else
   //  			$statusList = array($productStatusType);
   //  		//循环拉取所有状态的产品
   //  		foreach ($statusList as $status) {
	  //   		//创建日志
	  //   		$aliexpressLog = new AliexpressLog();
	  //   		$logID = $aliexpressLog->prepareLog($accountID, AliexpressProduct::EVENT_NAME);
	  //   		if ($logID) {
	  //   			//检查账号是否可以拉取
	  //   			$checkRunning = $aliexpressLog->checkRunning($accountID, AliexpressProduct::EVENT_NAME);
	  //   			if (!$checkRunning) {
	  //   				$aliexpressLog->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
	  //   			} else {
	  //   				//插入本次log参数日志(用来记录请求的参数)
	  //   				$time = date('Y-m-d H:i:s');
	  //   				$eventLogID = $aliexpressLog->saveEventLog(AliexpressProduct::EVENT_NAME, array(
	  //   					'log_id' => $logID,
	  //   					'account_id' => $accountID,
	  //   					'start_time'    => $time,
			// 				'end_time'      => $time,
	  //   				));
	  //   				//设置日志正在运行
	  //   				$aliexpressLog->setRunning($logID);
	  //   				//拉取产品
	  //   				$aliexpressProductModel = new AliexpressProduct();
	  //   				$aliexpressProductModel->setAccountID($accountID);
   //                      $params = array();
	  //   				$params['product_status_type'] = $status;
	  //   				if (!empty($productID))
	  //   					$params['product_id'] = $productID;
	  //   				$flag = $aliexpressProductModel->getAccountProducts($params);
	  //   				//更新日志信息
	  //   				if( $flag ){
	  //   					//删除掉未更新的产品
	  //   					//$aliexpressProductModel->getDbConnection()->createCommand()->delete($aliexpressProductModel->tableName(), "modify_time<'{$time}' and account_id={$accountID}");
	  //   					$aliexpressLog->setSuccess($logID, $aliexpressProductModel->getErrorMessage());
	  //   					$aliexpressLog->saveEventStatus(AliexpressProduct::EVENT_NAME, $eventLogID, AliexpressLog::STATUS_SUCCESS);
	  //   				}else{
	  //   					$aliexpressLog->setFailure($logID, $aliexpressProductModel->getErrorMessage());
	  //   					$aliexpressLog->saveEventStatus(AliexpressProduct::EVENT_NAME, $eventLogID, AliexpressLog::STATUS_FAILURE);
	  //   				}
	  //   			}
	  //   		}
   //  		}
   //  	} else {
   //  		//循环每个账号发送一个拉listing的请求
   //  		//$accountList = AliexpressAccount::model()->getCronGroupAccounts();
   //  		$accountList = AliexpressAccount::model()->getCronGroupAccountsDivide();
   //  		foreach($accountList as $accountID){
			// 	MHelper::runThreadSOCKET('/'.$this->route.'/account_id/' . $accountID . '/product_status_type/' . $productStatusType);
			// 	sleep(1);
			// }
   //  	}
   //  }
    
    /**
     * @desc 拉取单个产品
     */
    // public function actionGetproduct() {
    // 	$accountID = Yii::app()->request->getParam('account_id');
    // 	$productStatusType = Yii::app()->request->getParam('product_status_type');
    // 	$productID = Yii::app()->request->getParam('product_id');
    // 	if (empty($accountID) || empty($productStatusType) || empty($productID)) {
    // 		echo $this->failureJson(array(
    // 			'message' => Yii::t('aliexpress', 'Params Error'),
    // 		));
    // 		Yii::app()->end();
    // 	}
    // 	//拉取产品
    // 	$aliexpressProductModel = new AliexpressProduct();
    // 	$aliexpressProductModel->setAccountID($accountID);
    // 	$params['product_status_type'] = $productStatusType;
    // 	$params['product_id'] = $productID;
    // 	$flag = $aliexpressProductModel->getAccountProducts($params);
    // 	if ($flag) {
    // 		echo $this->successJson(array(
    // 			'message' => Yii::t('aliexpress', 'Get Product Success'),
    // 		));
    // 		Yii::app()->end();
    // 	} else {
    // 		echo $this->successJson(array(
    // 				'message' => $aliexpressProductModel->getErrorMessage(),
    // 		));
    // 		Yii::app()->end();
    // 	}
    // }
    
    /**
     * @desc 计算利润情况
     */
    public function actionGetpriceinfo(){
    	$sku            = Yii::app()->request->getParam('sku');
    	$categoryID     = Yii::app()->request->getParam('category_id');
    	$accountID      = Yii::app()->request->getParam('account_id');
    	$siteID         = Yii::app()->request->getParam('site_id');
    	$currency       = LazadaSite::getCurrencyBySite($siteID);
    	$salePrice      = Yii::app()->request->getParam('price');
    	$priceCal = new CurrencyCalculate();
    	$priceCal->setCurrency($currency);//币种
    	$priceCal->setPlatform(Platform::CODE_LAZADA);//设置销售平台
    	$priceCal->setSku($sku);//设置sku
    	$priceCal->setSalePrice($salePrice);
    	$output = new stdClass();
    	$output->salePrice  = $priceCal->getSalePrice();
    	$output->profit     = $priceCal->getProfit();
    	$output->profitRate = $priceCal->getProfitRate();
    	$output->desc       = $priceCal->getCalculateDescription();
    	echo json_encode($output);exit;
    }
    
    /**
     * @author lihy
     * @desc 获取速卖通产品列表
     */
    public function actionList(){
        $accountId = Yii::app()->request->getParam('account_id');
    	$aliexpressProductModel = new AliexpressProduct();
    	$this->render("list", array("model"=>$aliexpressProductModel, 'accountId'=>$accountId));
    }

    /**
     * @desc 上架操作
     */
    public function actionOnselling(){
    	$expressId = Yii::app()->request->getParam('id');
    	if($expressId){
    		//获取对应的aliexpress_product_id
    		$expressProduct = $this->_model->findByPk($expressId);
            $aliLogOnlineModel = new AliexpressLogOnline();
    		if($expressProduct){
    			//创建日志
    			$aliexpressLog = new AliexpressLog();
    			$logID = $aliexpressLog->prepareLog($expressProduct->account_id, AliexpressProduct::EVENT_UPDATE_NAME);
    			if($logID){
    				$time = date('Y-m-d H:i:s');
    				$eventLog = AliexpressLog::model()->saveEventLog(AliexpressProduct::EVENT_UPDATE_NAME, array(
    						'log_id'        => $logID,
    						'account_id'    => $expressProduct->account_id,
    						'start_time'    => $time,
    						'end_time'      => $time,
    						'status'		=> 1,
    				));
    				//设置日志为正在运行
    				AliexpressLog::model()->setRunning($logID);
	    			$flag = $this->_model->onSellingAliexpressProduct($expressProduct->account_id, $expressProduct->aliexpress_product_id);
                    $addData = array(
                        'product_id'        => $expressProduct->aliexpress_product_id,
                        'sku'               => $expressProduct->sku,
                        'online_sku'        => $expressProduct->online_sku,
                        'account_id'        => $expressProduct->account_id,
                        'event'             => 'onselling',
                        'status'            => 1,                           
                        'message'           => '上架成功',
                        'start_time'        => $time,
                        'operation_user_id' => (int)Yii::app()->user->id
                    );
	    			if($flag){
	    				//对本地做修改
	    				$this->_model->updateProductByPk($expressId, array('product_status_type'=>'onSelling'));
                        $aliLogOnlineModel->savePrepareLog($addData);
	    				AliexpressLog::model()->setSuccess($logID);
		    			AliexpressLog::model()->saveEventStatus(AliexpressProduct::EVENT_UPDATE_NAME, $eventLog, AliexpressLog::STATUS_SUCCESS);
	    				echo $this->successJson(array(
	    						'message' => Yii::t('system', 'Update successful'),
	    	
	    				));
	    				Yii::app()->end();
	    			}else{
                        $addData['status'] = 0;
                        $addData['message'] = '上架失败:'.$this->_model->getErrorMessage();
                        $aliLogOnlineModel->savePrepareLog($addData);
	    				AliexpressLog::model()->setFailure($logID, $this->_model->getErrorMessage());
	    				AliexpressLog::model()->saveEventStatus(AliexpressProduct::EVENT_UPDATE_NAME, $eventLog, AliexpressLog::STATUS_FAILURE);
	    			}
    			}
    		}
    	}
    	
    	echo $this->failureJson(array(
    			'message' => Yii::t('system', 'Update failure'),
    	));
    	Yii::app()->end();
    }
    /**
     * @desc 下线操作
     */
    public function actionOffline(){
        set_time_limit(0);
    	ini_set("display_errors", true);
    	error_reporting(E_ALL);
    	$expressId = Yii::app()->request->getParam('id');
        $aliLogOfflineModel = new AliexpressLogOffline();
    	if(empty($expressId)) $msg = "参数错误！";
    	if($expressId){
    		//获取对应的aliexpress_product_id
    		$expressProduct = $this->_model->findByPk($expressId);
    		if($expressProduct){
    			//创建日志
    			$aliexpresslog = new AliexpressLog();
    			$logID = $aliexpresslog->prepareLog($expressProduct->account_id, AliexpressProduct::EVENT_UPDATE_NAME);
				if($logID){
					$time = date('Y-m-d H:i:s');
					$eventLog = AliexpressLog::model()->saveEventLog(AliexpressProduct::EVENT_UPDATE_NAME, array(
							'log_id'        => $logID,
							'account_id'    => $expressProduct->account_id,
							'start_time'    => $time,
							'end_time'      => $time,
							'status'		=> 0,
					));
					//设置日志为正在运行
					AliexpressLog::model()->setRunning($logID);
	    			$flag = $this->_model->offlineAliexpressProduct($expressProduct->account_id, $expressProduct->aliexpress_product_id);

                    $addData = array(
                        'product_id'        => $expressProduct->aliexpress_product_id,
                        'sku'               => $expressProduct->sku,
                        'account_id'        => $expressProduct->account_id,
                        'event'             => 'offline',
                        'status'            => 1,
                        'ipm_sku_stock'     => $expressProduct->product_stock,                            
                        'message'           => '下架成功',
                        'start_time'        => date("Y-m-d H:i:s"),
                        'response_time'     => date("Y-m-d H:i:s"),
                        'operation_user_id' => (int)Yii::app()->user->id
                    );

		    		if($flag){
		    			//对本地做修改
		    			$this->_model->updateProductByPk($expressId, array('product_status_type'=>'offline'));
                        $aliLogOfflineModel->savePrepareLog($addData);
		    			AliexpressLog::model()->setSuccess($logID);
		    			AliexpressLog::model()->saveEventStatus(AliexpressProduct::EVENT_UPDATE_NAME, $eventLog, AliexpressLog::STATUS_SUCCESS);
		    			echo $this->successJson(array(
		    				'message' => Yii::t('system', 'Update successful'),
		    				'forward' => '/aliexpress/aliexpressproduct/list',
		    				'navTabId' => 'page'.Menu::model()->getIdByUrl('/aliexpress/aliexpressproduct/list'),
		    				'callbackType' => 'closeCurrent'
		    			));
		    			Yii::app()->end();
		    		}else{
                        $addData['status'] = 0;
                        $addData['message'] = '下架失败:'.$this->_model->getErrorMessage();
                        $aliLogOfflineModel->savePrepareLog($addData);
		    			AliexpressLog::model()->setFailure($logID, $this->_model->getErrorMessage());
						AliexpressLog::model()->saveEventStatus(AliexpressProduct::EVENT_UPDATE_NAME, $eventLog, AliexpressLog::STATUS_FAILURE);
						$msg = $this->_model->getErrorMessage();
		    		}
				}else{
					$msg = "日志生成错误!";
				}
    		}else{
    			$msg = "sku不存在！";
    		}
    	}
	    	
    	echo $this->failureJson(array(
    			//'message' => Yii::t('system', 'Update failure'),
    			'message' => $msg,
    	));
    	Yii::app()->end();
    }

    /**
     * 批量下架操作
     */
    public function actionBatchoffline(){
        set_time_limit(0);
    	ini_set("display_errors", true);
    	error_reporting(E_ALL);
    	$expressIds = Yii::app()->request->getParam('aliexpress_product_ids');
    	if(empty($expressIds)){
    		//没有选择
    		echo $this->failureJson(array(
    				'message' => Yii::t('aliexpress_product', 'Not Specify Sku Which Need To Inactive'),
    		));
    		Yii::app()->end();
    	}
    	$where = "id in('". implode("','", $expressIds) ."')";
    	$expressProducts = $this->_model->findAll($where);
    	$expressProductNew = array();
        $aliLogOfflineModel = new AliexpressLogOffline();
    	$successNum = 0;
    	if($expressProducts){
    		foreach ($expressProducts as $product){
                //创建日志
                $aliexpresslog = new AliexpressLog();
                $logID = $aliexpresslog->prepareLog($product['account_id'], AliexpressProduct::EVENT_UPDATE_NAME);
                if($logID){
                    $time = date('Y-m-d H:i:s');
                    $eventLog = AliexpressLog::model()->saveEventLog(AliexpressProduct::EVENT_UPDATE_NAME, array(
                            'log_id'        => $logID,
                            'account_id'    => $product['account_id'],
                            'start_time'    => $time,
                            'end_time'      => $time,
                            'status'        => 0,
                    ));
                    //设置日志为正在运行
                    AliexpressLog::model()->setRunning($logID);

                    $addData = array(
                        'product_id'        => $product['aliexpress_product_id'],
                        'sku'               => $product['sku'],
                        'account_id'        => $product['account_id'],
                        'event'             => 'batchoffline',
                        'status'            => 1,
                        'ipm_sku_stock'     => $product['product_stock'],                            
                        'message'           => '下架成功',
                        'start_time'        => date("Y-m-d H:i:s"),
                        'response_time'     => date("Y-m-d H:i:s"),
                        'operation_user_id' => (int)Yii::app()->user->id
                    );
                    $request = new GetOfflineProductRequest();
                    $request->setAccount($product['account_id'])
                        ->setPrdouctID($product['aliexpress_product_id']);
                    $response = $request->setRequest()
                                        ->sendRequest()
                                        ->getResponse();
                    if($request->getIfSuccess()){
                        ++$successNum;
                        $this->_model->batchUpdateProductByPk($product['id'], array('product_status_type'=>'offline'));
                        $aliLogOfflineModel->savePrepareLog($addData);
                        AliexpressLog::model()->setSuccess($logID);
                        AliexpressLog::model()->saveEventStatus(AliexpressProduct::EVENT_UPDATE_NAME, $eventLog, AliexpressLog::STATUS_SUCCESS);
                    }else{
                        $addData['status'] = 0;
                        $addData['message'] = '下架失败:'.$request->getErrorMsg();
                        $aliLogOfflineModel->savePrepareLog($addData);
                        AliexpressLog::model()->setFailure($logID, $request->getErrorMsg());
                        AliexpressLog::model()->saveEventStatus(AliexpressProduct::EVENT_UPDATE_NAME, $eventLog, AliexpressLog::STATUS_FAILURE);
                    }
                }

    		}
    	}
    	if($successNum > 0){
    		echo $this->successJson(array(
    				'message' => Yii::t('system', 'Update successful'),
    		));
    	}else{
    		echo $this->failureJson(array(
    				'message' => Yii::t('system', 'Update failure'),
    		));
    	}
    	Yii::app()->end();
    }

    /**
     * 批量下架（内部执行）
     */
    public function _batchOffline($expressIdsArr = array()){
        set_time_limit(3600);
        ini_set("display_errors", true);
        error_reporting(E_ALL);
        if(empty($expressIdsArr) || !is_array($expressIdsArr)) return false;

        $where = "id in('". implode("','", $expressIdsArr) ."')";
        $expressProducts = $this->_model->findAll($where);
        $expressProductNew = array();
        $successNum = 0;
        if($expressProducts){
            foreach ($expressProducts as $product){
                $expressProductNew[$product['account_id']][$product['id']] = $product['aliexpress_product_id'];
            }
            foreach ($expressProductNew as $accountId=>$productIds){
                //创建日志
                $aliexpresslog = new AliexpressLog();
                $logID = $aliexpresslog->prepareLog($accountId, AliexpressProduct::EVENT_UPDATE_NAME);
                if($logID){
                    $time = date('Y-m-d H:i:s');
                    $eventLog = AliexpressLog::model()->saveEventLog(AliexpressProduct::EVENT_UPDATE_NAME, array(
                            'log_id'        => $logID,
                            'account_id'    => $accountId,
                            'start_time'    => $time,
                            'end_time'      => $time,
                            'status'        => 0,
                    ));
                    //设置日志为正在运行
                    AliexpressLog::model()->setRunning($logID);
                    $flag = $this->_model->offlineAliexpressProduct($accountId, $productIds);
                    if($flag){
                        ++$successNum;
                        $pkids = array_keys($productIds);
                        $this->_model->batchUpdateProductByPk($pkids, array('product_status_type'=>'offline'));
                        AliexpressLog::model()->setSuccess($logID);
                        AliexpressLog::model()->saveEventStatus(AliexpressProduct::EVENT_UPDATE_NAME, $eventLog, AliexpressLog::STATUS_SUCCESS);
                    }else{
                        AliexpressLog::model()->setFailure($logID, $this->_model->getErrorMessage());
                        AliexpressLog::model()->saveEventStatus(AliexpressProduct::EVENT_UPDATE_NAME, $eventLog, AliexpressLog::STATUS_FAILURE);
                    }
                }
            }
        }
        if($successNum > 0){
            return true;
        }else{
            return false;
        }
    }    

    /**
     * 
     * @desc 批量上架操作
     *  
     */
    public function actionBatchonselling(){
    	$expressIds = Yii::app()->request->getParam('aliexpress_product_ids');
    	if(empty($expressIds)){
    		//没有选择
    		echo $this->failureJson(array(
    				'message' => Yii::t('aliexpress_product', 'Not Specify Sku Which Need To Active'),
    		));
    		Yii::app()->end();
    	}
    	$where = "id in('". implode("','", $expressIds) ."')";
    	$expressProducts = $this->_model->findAll($where);
    	$expressProductNew = array();
        $aliLogOnlineModel = new AliexpressLogOnline();
    	$successNum = 0;
    	if($expressProducts){
    		foreach ($expressProducts as $product){
                $accountId = $product['account_id'];
                $productIds = $product['aliexpress_product_id'];

                //创建日志
                $aliexpresslog = new AliexpressLog();
                $logID = $aliexpresslog->prepareLog($accountId, AliexpressProduct::EVENT_UPDATE_NAME);
                if($logID){
                    $time = date('Y-m-d H:i:s');
                    $eventLog = AliexpressLog::model()->saveEventLog(AliexpressProduct::EVENT_UPDATE_NAME, array(
                            'log_id'        => $logID,
                            'account_id'    => $accountId,
                            'start_time'    => $time,
                            'end_time'      => $time,
                            'status'        => 1,
                    ));
                    //设置日志为正在运行
                    AliexpressLog::model()->setRunning($logID);
                    $flag = $this->_model->onSellingAliexpressProduct($accountId, $productIds);
                    $addData = array(
                        'product_id'        => $productIds,
                        'sku'               => $product['sku'],
                        'online_sku'        => $product['online_sku'],
                        'account_id'        => $accountId,
                        'event'             => 'batchonselling',
                        'status'            => 1,                           
                        'message'           => '上架成功',
                        'start_time'        => $time,
                        'operation_user_id' => (int)Yii::app()->user->id
                    );
                    if($flag){
                        ++$successNum;
                        $this->_model->batchUpdateProductByPk($productIds, array('product_status_type'=>'onSelling'));
                        $aliLogOnlineModel->savePrepareLog($addData);
                        AliexpressLog::model()->setSuccess($logID);
                        AliexpressLog::model()->saveEventStatus(AliexpressProduct::EVENT_UPDATE_NAME, $eventLog, AliexpressLog::STATUS_SUCCESS);
                    }else{
                        $addData['status'] = 0;
                        $addData['message'] = '上架失败:'.$this->_model->getErrorMessage();
                        $aliLogOnlineModel->savePrepareLog($addData);
                        AliexpressLog::model()->setFailure($logID, $this->_model->getErrorMessage());
                        AliexpressLog::model()->saveEventStatus(AliexpressProduct::EVENT_UPDATE_NAME, $eventLog, AliexpressLog::STATUS_FAILURE);
                    }
                }
    		}
    	}
    	if($successNum > 0){
    		echo $this->successJson(array(
    				'message' => Yii::t('system', 'Update successful'),
    		));
    	}else{
    		echo $this->failureJson(array(
    				'message' => Yii::t('system', 'Update failure'),
    		));
    	}
    	Yii::app()->end();
    }
    
    /**
     * @desc 更改子sku对应库存
     * @throws Exception
     */
    public function actionChangevariationstock(){
    	ini_set("display_errors", true);
    	error_reporting(E_ALL);
    	$vid = Yii::app()->request->getParam('vid');
    	$type = Yii::app()->request->getParam('stock');
        $aliZeroStockSKUModel = new AliexpressZeroStockSku();

    	try{
    		if(empty($vid)) throw new Exception('参数错误');
    		//if($type != 1 && $type != 2) throw new Exception('参数错误');
    		if($type <= 0){
    			$updateStock = $type;
    			$stock = 0;
    		}else{
    			$updateStock = $type;
    			$stock = 1;
    		}
    		//首先获取子产品对应的信息
    		$skuInfo = AliexpressProductVariation::model()->getDbConnection()
				    		->createCommand()
				    		->from(AliexpressProductVariation::model()->tableName(). ' t')
				    		->leftJoin(AliexpressProduct::model()->tableName() . ' p', 'p.id=t.product_id')
				    		->select("t.id, t.sku, t.sku_id, t.sku_code as online_sku, p.account_id, p.aliexpress_product_id, t.ipm_sku_stock as product_stock")
				    		->where('t.id in('.$vid . ")")->queryAll();
    		if(!$skuInfo) throw new Exception('该sku不存在');
    		$skuaccountList = array();
    		foreach ($skuInfo as $skus){
    		    
    		    //排除海外仓账户 is_overseas_warehouse
    		    if($skus['account_id']){
    		        $accountInfo = AliexpressAccount::getAccountInfoById($skus['account_id']);
    		        if ($accountInfo){
    		            if ($accountInfo['is_overseas_warehouse'] == 1){
    		                continue;
    		            }
    		        }
    		    }
    		    
    			$addData = array(
                        'product_id'    =>  $skus['aliexpress_product_id'],
                        'seller_sku'    =>  $skus['online_sku'],
                        'sku'           =>  $skus['sku'],
                        'account_id'    =>  $skus['account_id'],
                        'site_id'       =>  0,
                        'old_quantity'  =>  $skus['product_stock'],
                        'status'        =>  AliexpressZeroStockSku::STATUS_SUCCESS,
                        'msg'           =>  '修改库存为'.$updateStock.'成功',
                        'create_time'   =>  date('Y-m-d H:i:s'),
                        'type'          =>  AliexpressZeroStockSku::TYPE_STOCK,
                        'set_quantity'  =>  $updateStock,
                        'operation_user_id' => (int)Yii::app()->user->id
                );

                $editMutilpleSkuStocksRequest = new EditMutilpleSkuStocksRequest;
                $editMutilpleSkuStocksRequest->setAccount($skus['account_id']);
                $editMutilpleSkuStocksRequest->setProductID($skus['aliexpress_product_id']);
                $editMutilpleSkuStocksRequest->setSkuID($skus['sku_id']);
                $editMutilpleSkuStocksRequest->setIpmSkuStock($updateStock);
                $editMutilpleSkuStocksRequest->push();
                $response = $editMutilpleSkuStocksRequest->setRequest()->sendRequest()->getResponse();
                $editMutilpleSkuStocksRequest->clean();
                if($editMutilpleSkuStocksRequest->getIfSuccess()){
                    //更新本地
                    AliexpressProductVariation::model()->getDbConnection()->createCommand()
                        ->update(AliexpressProductVariation::model()->tableName(), 
                            array('ipm_sku_stock'=>$updateStock, 'sku_stock'=>$stock), 
                            "id = '{$skus['id']}'");
                }else{
                    $addData['status'] = AliexpressZeroStockSku::STATUS_FAILURE;
                    $addData['msg'] = $editMutilpleSkuStocksRequest->getErrorMsg();
                }

                $aliZeroStockSKUModel->saveData($addData);

    		}    		
    		echo $this->successJson(array(
    				'message' => Yii::t('system', 'Update successful'),
    		));
    	}catch (Exception $e){
    		echo $this->failureJson(array(
    				'message' => Yii::t('system', $e->getMessage()),
    		));
    	}
    	Yii::app()->end();
    }

    /**
     * @desc 系统自动导入下线sku, 条件：待清仓且可用库存小于等于0
     * @link /aliexpress/aliexpressproduct/autoimportofflinetask
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
                $variationInfos = AliexpressProductVariation::model()->filterByCondition('p.account_id'," p.product_status_type='onSelling' and v.sku='{$v['sku']}' ");
                if (!empty($variationInfos)) {
                    foreach ($variationInfos as $vs) {
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
                    $res = AliexpressOfflineTask::model()->insertBatch($rows);
                }
            }
        }
        Yii::app()->end('finish');
    }
    
    /**
     * @desc 导入批量下线SKU
     */
    public function actionOfflineimport() {
        set_time_limit(0);
        ini_set('memory_limit','2048M');
    	$model = new AliexpressProduct();
        $aliLogOfflineModel = new AliexpressLogOffline();
    	if (Yii::app()->request->isPostRequest) {
            $accountIDs = Yii::app()->request->getParam('account_id');
            $type       = $_FILES['offline_file']['type'];
            $tmpName    = $_FILES['offline_file']['tmp_name'];
            $error      = $_FILES['offline_file']['error'];
            $size       = $_FILES['offline_file']['size'];
            $fileName   = $_FILES['offline_file']['name'];
            $errors     = '';
    		switch ($error) {
    			case 0: break;
    			case 1:
    			case 2:
    				$errors = Yii::t('aliexpress_product', 'File Too Large');
    				break;
    			case 3:
    				$errors = Yii::t('aliexpress_product', 'File Upload Partial');
    				break;
    			case 4:
    				$errors = Yii::t('aliexpress_product', 'No File Upload');
    				break;
    			case 5:
    				$errors = Yii::t('aliexpress_product', 'Upload File Size Zero');
    				break;
    			default:
    				$errors = Yii::t('aliexpress_product', 'Unknow Error');
    		}
    		if (!empty($errors)) {
    			echo $this->failureJson(array( 'message' => $errors));
    			Yii::app()->end();
    		}
			if (empty($accountIDs)) {
				echo $this->failureJson(array( 'message' => Yii::t('aliexpress_product', 'Not Select Account')));
				Yii::app()->end();				
			}
			if (strpos($fileName, '.csv') == false) {
				echo $this->failureJson(array( 'message' => Yii::t('aliexpress_product', 'Please Upload CSV File')));
				Yii::app()->end();				
			}
			$fp = fopen($tmpName, 'r');
			if (!$fp) {
				echo $this->failureJson(array( 'message' => Yii::t('aliexpress_product', 'Open File Failure')));
				Yii::app()->end();				
			}
			$row = 0;
            $data = array();
			while (!feof($fp)) {
				$row++;
				$rows = fgetcsv($fp, 1024);
				if ($row == 1) continue;
				$sku = trim($rows[0]);
				if (empty($sku)) continue;
                foreach ($accountIDs as $accountID) {
                    $productInfo = $model->getOneByCondition('id,aliexpress_product_id,product_stock',"sku = '".$sku."' AND account_id = ".$accountID);
                    if(!$productInfo){
                        continue;
                    }
                    $addData = array(
                        'product_id'        => $productInfo['aliexpress_product_id'],
                        'sku'               => $sku,
                        'account_id'        => $accountID,
                        'event'             => 'offlineimport',
                        'status'            => 1,
                        'ipm_sku_stock'     => $productInfo['product_stock'],                            
                        'message'           => '下架成功',
                        'start_time'        => date("Y-m-d H:i:s"),
                        'response_time'     => date("Y-m-d H:i:s"),
                        'operation_user_id' => (int)Yii::app()->user->id
                    );
                    $request = new GetOfflineProductRequest();
                    $request->setAccount($accountID)
                        ->setPrdouctID($productInfo['aliexpress_product_id']);
                    $response = $request->setRequest()
                                        ->sendRequest()
                                        ->getResponse();
                    if($request->getIfSuccess()){
                        $this->_model->batchUpdateProductByPk($productInfo['id'], array('product_status_type'=>'offline'));
                        $aliLogOfflineModel->savePrepareLog($addData);
                    }else{
                        $addData['status'] = 0;
                        $addData['message'] = '下架失败:'.$request->getErrorMsg();
                        $aliLogOfflineModel->savePrepareLog($addData);
                    }
                }
			}
			fclose($fp);
			echo $this->successJson(array(
				'message' => Yii::t('aliexpress_product', 'Batch Offline Task Add Successful'),
				'callbackType' => 'closeCurrent'
			));
			Yii::app()->end();
    	}
    	$accountList = AliexpressAccount::getAbleAccountList();
    	$this->render('offline_import', array('account_list' => $accountList, 'model' => $model));
    }
    
    /**
     * 下线批量下线任务的产品
     * @link /aliexpress/aliexpressproduct/processofflinetask
     */
    public function actionProcessofflinetask() {
        set_time_limit(0);
        ini_set('memory_limit','2048M');

        $type = Yii::app()->request->getParam("type");
        $time = time();
        if($type == 'query'){
            //白天执行查询
            $flag_while = true;
            $flag_online = false;
        } else {
            //晚上执行下架
            $flag_while = true;
            $flag_online = true;
        }
        
        //查询是否有线上sku，没有删除，有则状态改为1
        while( $flag_while ){
            $exe_time   =  time();
            if(($exe_time - $time) >= 25200 ){
                exit('执行超过7小时');
            }
            $res = AliexpressOfflineTask::model()->getDbConnection()->createCommand()
                    ->from("ueb_aliexpress_offline_task")
                    ->select('id, sku, account_id')
                    ->where("status = 0")
                    ->limit(1000)
                    ->queryAll();
            if (!empty($res)) {
                foreach ($res as $row) {
                    $accountID = $row['account_id'];
                    $data = array(
                        'process_time' => date('Y-m-d H:i:s'),
                        'status' => 1,
                    );
                    //查询要下线的listing
                    $command = AliexpressProduct::model()->getDbConnection()->createCommand()
                            ->select("t1.aliexpress_product_id, t.sku_id")
                            ->from("ueb_aliexpress_product_variation as t")
                            ->leftJoin("ueb_aliexpress_product as t1", "t.product_id = t1.id")
                            ->where("t.sku = '" . $row['sku'] . "'")
                            ->andWhere("t1.product_status_type = :product_status_type" ,array(':product_status_type' => AliexpressProduct::PRODUCT_STATUS_ONSELLING) )
                            ->andWhere("t1.account_id = " . $accountID);
                    $skuOnline = $command->queryRow();
                    if (empty($skuOnline)) {
                        AliexpressOfflineTask::model()->getDbConnection()->createCommand()->update("ueb_aliexpress_offline_task", array('status'=>3),"id = " . $row['id'] );
                    } else {
                        AliexpressOfflineTask::model()->getDbConnection()->createCommand()->update("ueb_aliexpress_offline_task", $data, "id = " . $row['id']);
                    }
                }
            } else {
                $flag_while = false;
            }
        }
        
        //查询有线上sku的数据并执行下架
        while( $flag_online ){
            $exe_time   =  time();
            if(($exe_time - $time) >= 36000 ){
                exit('执行超过10小时');
            }
            $res = AliexpressOfflineTask::model()->getDbConnection()->createCommand()
                    ->from("ueb_aliexpress_offline_task")
                    ->select('id, sku, account_id')
                    ->where("status = 1")
                    ->limit(1000)
                    ->queryAll();
            $taskList = array();
            $data_zero = array(
                    'process_time' => date('Y-m-d H:i:s'),
                    'status' => 0,
            );
            if (!empty($res)) {
                foreach ($res as $row) {
                    $accountID = $row['account_id'];
                    //查询要下线的listing
                    $command = AliexpressProduct::model()->getDbConnection()->createCommand()
                                ->select("t1.aliexpress_product_id, t.sku_id")
                                ->from("ueb_aliexpress_product_variation as t")
                                ->leftJoin("ueb_aliexpress_product as t1", "t.product_id = t1.id")
                                ->where("t.sku = '" . $row['sku'] . "'")
                                ->andWhere("t1.product_status_type = :product_status_type" ,array(':product_status_type' => AliexpressProduct::PRODUCT_STATUS_ONSELLING) )
                                ->andWhere("t1.account_id = " . $accountID);
                    $skuOnline = $command->queryAll();
                    if (empty($skuOnline)) {
                        AliexpressOfflineTask::model()->getDbConnection()->createCommand()->update("ueb_aliexpress_offline_task",array('status'=>3), "id = " . $row['id'] );
                    } else {
                        AliexpressOfflineTask::model()->getDbConnection()->createCommand()->update("ueb_aliexpress_offline_task", $data_zero, "id = " . $row['id']);
                        $taskList[$row['account_id']][$row['id']] = $skuOnline;
                    }
                }
            } else {
                $flag_online = false;
            }
            foreach ($taskList as $accountID => $list) {
                foreach ($list as $id => $rows_sku) {
                    foreach ($rows_sku as $rows){
                        $data = array();
                        //如果是子sku则将子sku库存修改为0
                        if ($rows['sku_id'] == '<none>') {
                            $request = new GetOfflineProductRequest();
                            $request->setPrdouctID($rows['aliexpress_product_id']);
                            $response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
                            $flag = $request->getIfSuccess();
                        } else {
                            $request = new EditSingleSkuStockRequest();
                            $request->setProductID($rows['aliexpress_product_id']);
                            $request->setSkuID($rows['sku_id']);
                            $request->setIpmSkuStock(0);
                            $response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
                            $flag = $request->getIfSuccess();
                            //如果子sku是最后一个有库存的，那么直接下架产品
                            if (isset($response->error_code) && $response->error_code == '13004001') {
                                $request = new GetOfflineProductRequest();
                                $request->setPrdouctID($rows['aliexpress_product_id']);
                                $response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
                                $flag = $request->getIfSuccess();
                            }
                        }
                        if (!$flag) {
                            $data['status'] = -1;
                            $data['process_time'] =  date('Y-m-d H:i:s');
                            $data['response_msg'] = $request->getErrorMsg();
                        } else {
                            $data['status'] = 2;
                            $data['process_time'] =  date('Y-m-d H:i:s');
                            $data['response_msg'] = 'SUCCESS';
                        }
                    }
                    AliexpressOfflineTask::model()->getDbConnection()->createCommand()->update("ueb_aliexpress_offline_task", $data, "id = " . $id);
                }
            }
        }
        
    	exit('DONE');
    }
    
    /**
     * @desc 检测仓库中的sku库存数量，从而自动更改平台上的库存数量为0
     *       方式一：以erp产品表为主，循环取出去对比仓库库存
     *       方式二：以仓库库存表为主，批量循环取出小于1的sku，再取出对应的产品表中的相关信息，更新在线产品库存
     */
    public function actionAutochangestockfornostock(){
    	//设置测试环境运行程序
    	$loopNum = 0;
    	$testFlag = false;//是否为测试标示
    	$runType = Yii::app()->request->getParam("runtype");
    	$testSKUs = Yii::app()->request->getParam("sku");
    	$testAccountID = Yii::app()->request->getParam("account_id");
    	$testSkuList = array();
    	//测试环境下必须指定sku和账号
    	if($runType != "y" && (empty($testSKUs) || empty($testAccountID))){
    		exit("测试下必须指定sku列表和账号，多个sku之间用半角,隔开。示例：{$this->route}/account_id/1/sku/1123,22444,3434.09");
    	}elseif ($runType != "y"){
    		$testFlag = true;
    		$testSkuList = explode(",", $testSKUs);
    	}
    	
    	set_time_limit(0);
    	ini_set("display_errors", true);
    	$allowWarehouse =  WarehouseSkuMap::WARE_HOUSE_GM;
    	
    	
    	$params = array();
    	$limit = 200;
    	$offset = 0;
    	$aliProductModel = new AliexpressProduct();
    	$aliVariantProductModel = new AliexpressProductVariation;	
    	$aliZeroStockSKUModel = new AliexpressZeroStockSku();
    	
    	//-- start 2016-02-01 增加type --
    	$type = Yii::app()->request->getParam("type");
    	if(empty($type)) $type = 0;
    	
    	switch ($type){
    		case 0://默认，库存《=1库存清零
    			$conditions = "t.available_qty <= 1 AND t.warehouse_id in(".$allowWarehouse.") AND p.product_status<7"; //lihy modify 2016-02-14
    			$SkuModel = new WarehouseSkuMap();
    			$method = "getSkuListLeftJoinProductByCondition";
    			$select = "t.sku";
    			break;
    		case 1:
    			$productStatus = Product::STATUS_HAS_UNSALABLE . "," . Product::STATUS_WAIT_CLEARANCE;
    			$conditions = "product_status in(". $productStatus .") and product_is_multi in (0, 1)";
    			$SkuModel = new Product();
    			$method = "getProductListByCondition";
    			$select = "sku";
    			break;
    		case 2:
    			$SkuModel = new Order();
    			$method = "getOweWaitingConfirmOrdersSkuListByCondition";
    			$conditions = null;
				$params = array();
    			$select = "sku";
    			break;
    		//2016-02-03 add
    		case 5://手动导入的sku来源
    			$SkuModel = new ProductImportSku();
    			$method = "getSkuListByCondition";
    			$conditions = "aliexpress_status=0";
    			$params = array();
    			$select = "sku";
    			break;
    		default:
    			exit('type is incorrect');
    	}
    	//-- end 2016-02-01 增加type --
    	
    	do{
    		//1、循环取出<=1的sku列表，每次100个
    		//2、取出上述sku对应的产品库中的信息
    		//3、提交到ebay平台，实现在线库存修改
    		if(!$testFlag){
    			$limits = "{$offset}, {$limit}";
    			$skuList = $SkuModel->$method($conditions, $params, $limits, $select);//2016-02-01 更改
    			$offset += $limit;
    		}else{
    			if($loopNum > 0){
    				exit("测试运行结束");
    			}
    			$skuList = array();
    			foreach ($testSkuList as $sku){
    				$skuList[] = array('sku'=>$sku);
    			}
    			$loopNum++;
    			echo "set testSkulist=". implode(",", $testSkuList) . "<br/>";
    		}
    		if($skuList){
    			$flag = true;
    			$skus = array();$productListing = $variantListing = array();
    			foreach ($skuList as $sku){
    				$skus[] = $sku['sku'];
    			}
    			unset($skuList);
    			
    			
    			$command = $aliVariantProductModel->getDbConnection()->createCommand()
							    			->from($aliVariantProductModel->tableName() . " as t")
							    			->leftJoin($aliProductModel->tableName()." as p", "p.id=t.product_id")
							    			->select("t.sku, t.sku_id, t.sku_code as online_sku, p.account_id, p.aliexpress_product_id, t.ipm_sku_stock as product_stock")
							    			->where(array("IN", "t.sku", $skus))
							    			->andWhere("p.product_status_type='onSelling'") //lihy add 2016-02-14
							    			->andWhere("t.ipm_sku_stock>0");
    			if($testFlag){
    				echo "set testaccount_id=".$testAccountID . "<br/>";
    				$command->andWhere("p.account_id=".$testAccountID);
    			}
    			$variantListing = $command->queryAll();
    			//查找出对应的父级sku信息，聚合同属一个产品的信息
    			$listing = array();
    			$updateSKUS = $skus;//2016-02-03 add
    			if($variantListing){
    				foreach ($variantListing as $variant){
    					//检测当天是否已经运行了，此表，加此判断之后数据量会降很多，并且会定时进行清理记录数据 lihy add 2016-02-14
    					if($aliZeroStockSKUModel->checkHadRunningForDay($variant['online_sku'], $variant['account_id'])){
    						continue;
    					}
    					$listing[$variant['account_id']][$variant['aliexpress_product_id']]['variant'][] = $variant;
    					/* if(!isset($updateSKUS[$variant['sku']]))//2016-02-03 add
    						$updateSKUS[$variant['sku']] = $variant['sku']; */
    				}
    			}
    			if($listing){
    				//print_r($listing);
    				$eventName = AliexpressZeroStockSku::EVENT_ZERO_STOCK;
    				foreach ($listing as $accountID=>$lists){
    					$time = date("Y-m-d H:i:s");
    					//写log
    					$logModel = new AliexpressLog();
    					$logID = $logModel->prepareLog($accountID, $eventName);
    					if(!$logID){
    						continue;
    					}
    					//检测是否可以允许
    					if(!$logModel->checkRunning($accountID, $eventName)){
    						$logModel->setFailure($logID, Yii::t('system', 'There Exists An Active Event'));
    						continue;
    					}
    					$startTime = date("Y-m-d H:i:s");
    					//设置运行
    					$logModel->setRunning($logID);
    					$editMutilpleSkuStocksRequest = new EditMutilpleSkuStocksRequest;
    					$editMutilpleSkuStocksRequest->setAccount($accountID);
    					$msg = "";
    					foreach ($lists as $productID=>$list){
    						$editMutilpleSkuStocksRequest->setProductID($productID);
    						$res = $this->_startSendRequest($editMutilpleSkuStocksRequest, $list);
    						if(!$res){
    							$res = $this->_startSendRequest($editMutilpleSkuStocksRequest, $list, 1);
    						}
    						$message = "";
    						if($res){
    							$status = 2;//成功
    						}else{
    							$status = 3;//失败
    							$message = " accountID:{$accountID}, productID:{$productID} " . $editMutilpleSkuStocksRequest->getErrorMsg();
    							$msg .= $message;
    						}
    						//写入记录
    						if(!empty($list['variant'])){
    							foreach ($list['variant'] as $variant){
    								$addData = array(
    										'product_id'=>	$productID,
    										'seller_sku'=>	$variant['online_sku'],
    										'sku'		=>	$variant['sku'],
    										'account_id'=>	$accountID,
    										'site_id'	=>	'',
    										'old_quantity'=>$variant['product_stock'],
    										'status'	=>	$status,
    										'msg'		=>	$message,
    										'create_time'=>	$time,
    										'type'		=>	$type
    								);
    								$aliZeroStockSKUModel->saveData($addData);
    							}
    						}
    					}
    					
    					//日志完成
    					$eventLogID = $logModel->saveEventLog($eventName, array(
    							'log_id'	=>	$logID,
    							'account_id'=>	$accountID,
    							'start_time'=>	$startTime,
    							'end_time'	=>	date("Y-m-d H:i:s")) );
    					$logModel->setSuccess($logID, $msg);
    					$logModel->saveEventStatus($eventName, $eventLogID, AliexpressLog::STATUS_SUCCESS);
    				}
    				//2016-02-03 add
    				//如果为手动导入的则还需要更新
    				if($type == 5 && $updateSKUS){
    					ProductImportSku::model()->updateDataByCondition("aliexpress_status=0 AND sku in(". MHelper::simplode($updateSKUS) .")", array('aliexpress_status'=>1));
    				}
    				unset($listing, $lists);
    			}else{
    				echo("no match sku ");
    			}
    		}else{ 
    			$flag = false;
    			exit('not found stock less than 0');
    		}
    	}while ($flag);
    }
    /**
     * @desc 执行请求，自动更改库存为0
     * @param unknown $reviseInventoryStatusRequest
     * @param unknown $currentSku
     */
    private function _startSendRequest($editMutilpleSkuStocksRequest, $list, $flag = 0){
    	if(!empty($list['variant'])){
    		$j = 0;
    		foreach ($list['variant'] as $variant){
    			$editMutilpleSkuStocksRequest->setSkuID($variant['sku_id']);
    			if($j == 0 && $flag != 0){
    				$editMutilpleSkuStocksRequest->setIpmSkuStock(1);
    			}else{
    				$editMutilpleSkuStocksRequest->setIpmSkuStock(0);
    			}
    			$editMutilpleSkuStocksRequest->push();
    			$j++;
    		}
    	}
    	//测试和上线时候开启
    	$response = $editMutilpleSkuStocksRequest->setRequest()->sendRequest()->getResponse();
    	
    	$editMutilpleSkuStocksRequest->clean();
    	return $editMutilpleSkuStocksRequest->getIfSuccess();
    }


    /**
     * @desc 执行请求，自动更改库存为0
     * @param unknown $reviseInventoryStatusRequest
     * @param unknown $currentSku
     */
    private function _startSendRequestBySkuID($editMutilpleSkuStocksRequest, $skuID, $flag = 0){
        $editMutilpleSkuStocksRequest->setSkuID($skuID);
        if($flag != 0){
            $editMutilpleSkuStocksRequest->setIpmSkuStock(1);
        }else{
            $editMutilpleSkuStocksRequest->setIpmSkuStock(0);
        }
        $editMutilpleSkuStocksRequest->push();
        //测试和上线时候开启
        $response = $editMutilpleSkuStocksRequest->setRequest()->sendRequest()->getResponse();
        $editMutilpleSkuStocksRequest->clean();
        return $editMutilpleSkuStocksRequest->getIfSuccess();
    }
    
    
    /**
     * @desc 恢复从自动置为0的sku的库存
     */
    public function actionRestoreskustockfromzerostocksku(){
    	set_time_limit(0);
    	$accountID = Yii::app()->request->getParam('account_id');
    	$type = Yii::app()->request->getParam('type');
    	if(!$type) $type = 1;//强制设置，后续改
    	if($accountID){
    		$time = date("Y-m-d H:i:s");
    		//写log
    		$logModel = new AliexpressLog();
    		$eventName = AliexpressZeroStockSku::EVENT_RESTORE_STOCK;
    		$logID = $logModel->prepareLog($accountID, $eventName);
    		if(!$logID){
    			exit('Create Log Failure');
    		}
    		//检测是否可以允许
    		if(!$logModel->checkRunning($accountID, $eventName)){
    			$logModel->setFailure($logID, Yii::t('system', 'There Exists An Active Event'));
    			exit('There Exists An Active Event');
    		}
    		$startTime = date("Y-m-d H:i:s");
    		//设置运行
    		$logModel->setRunning($logID);
    		//@todo 
    		//1、获取对应的置为0的sku列表
    		//2、寻找对应sku的可用库存数量
    		$aliProductVariantModel = new AliexpressProductVariation();
    		$aliZeroStockSKUModel = new AliexpressZeroStockSku();
    		$conditions = "t.is_restore=0 and t.status=2 and t.account_id={$accountID} ";
    		$limit = 200;
    		$offset = 0;
    		$editSingleSkuStockRequest = new EditSingleSkuStockRequest;//使用单个修改接口
    		$editSingleSkuStockRequest->setAccount($accountID);
    		$msg = "";
			do{
				$skuList = $aliZeroStockSKUModel->getDbConnection()->createCommand()
												->from($aliZeroStockSKUModel->tableName() . " as t")
												->select("t.*, v.sku_id")
												->leftJoin($aliProductVariantModel->tableName() . " as v", "v.sku_code=t.seller_sku and v.sku=t.sku")
												->where($conditions)
												->limit($limit, $offset)
												->group('t.seller_sku')
												->queryAll();
				$offset += $limit;
				//$this->print_r($skuList);
				if($skuList){
					$skuIDs = array();
					foreach ($skuList as $sku){
						$editSingleSkuStockRequest->setProductID($sku['product_id']);
						$editSingleSkuStockRequest->setSkuID($sku['sku_id']);
						$editSingleSkuStockRequest->setIpmSkuStock(999);
						$response = $editSingleSkuStockRequest->setRequest()->sendRequest()->getResponse();
						if($editSingleSkuStockRequest->getIfSuccess()){
							$aliZeroStockSKUModel->getDbConnection()
												->createCommand()
												->update($aliZeroStockSKUModel->tableName(), array('is_restore'=>1), 
														"account_id={$accountID} AND seller_sku='{$sku['seller_sku']}'");
						}else{
							$msg .= " sku:{$sku['seller_sku']} , accountID:{$accountID} " . $editSingleSkuStockRequest->getErrorMsg();
						}
					}
					
				}
			}while ($skuList);    		
    		
    		//日志完成
    		$logModel->setSuccess($logID, $msg);
    		
    	}else{
    		//循环每个账号发送一个拉listing的请求
    		$accountList = AliexpressAccount::model()->getCronGroupAccounts();
    		foreach($accountList as $accountID){
    			MHelper::runThreadSOCKET('/'.$this->route.'/account_id/' . $accountID . '/type/' . $type);
    			sleep(1);
    		}
    	}
    }
    
        
        /**
	 * 导入下架任务表删除没有线上sku的数据
	 */
        public function actionDeleteNoOnlineSku(){
            AliexpressProduct::model()->getDbConnection()->createCommand()->delete("ueb_aliexpress_offline_task", "response_msg = 'no online sku'" );
        }


    /**
     * 按账号批量修改运费模板
     */
    public function actionBatchmodifyfreighttemplate(){
        $accountId = Yii::app()->request->getParam('accountId');

        $ids = Yii::app()->request->getParam('ids');
        if($ids){
            $ids = trim($ids,',');
        }

        $freightTemplateInfoArr = array();

        //同步线上运费模板
        // MHelper::runThreadSOCKET('/aliexpress/aliexpressfreighttemplate/getfreighttemplate/account_id/149');
        
        //通过账号取出运费模板信息
        $freightTemplateInfo = AliexpressFreightTemplate::model()->getTemplateIdInfoByAccountId($accountId);
        if($freightTemplateInfo){
            foreach ($freightTemplateInfo as $key => $value) {
                $freightTemplateInfoArr[$value['template_id']] = $value['template_name'];
            } 
        }

        //通过账号ID查询账号名称
        $accountName = AliexpressAccount::model()->getAccountNameById($accountId);
        $this->render(
            'batchmodifyfreighttemplate', 
            array(
                'model'                 => $this->_model, 
                'freightTemplateInfo'   => $freightTemplateInfoArr, 
                'accountId'             => $accountId,
                'ids'                   => $ids,
                'accountName'           => $accountName
            )
        );
    }


    /**
     * 保存修改的运费模板
     */
    public function actionSavebatchmodifyfreighttemplate(){
        $accountId = Yii::app()->request->getParam('account_id');
        $freightTemplate = Yii::app()->request->getParam('AliexpressProduct');
        $freightTemplateId = $freightTemplate['freight_template_id'];
        $ids = Yii::app()->request->getParam('ids');

        //判断运费模板是否为空
        if(!$freightTemplateId){
            echo $this->failureJson(array('message'=>'没有选择运费模板'));
            exit;
        }

        //取出账号的模板ID
        $freightArr = array();
        $freightTemplateInfo = AliexpressFreightTemplate::model()->getTemplateIdInfoByAccountId($accountId);
        if($freightTemplateInfo){
            foreach ($freightTemplateInfo as $tkey => $tvalue) {
                $freightArr[] = $tvalue['template_id'];
            }
        }

        //判断运费模板是否是选择的账号
        if(!in_array($freightTemplateId, $freightArr)){
            echo $this->failureJson(array('message'=>'此账号下没有选择的模板'));
            exit;
        }

        if($accountId){
            $attributes = array('freight_template_id'=>$freightTemplateId);
            $condition = 'account_id = :account_id';
            $param = array(':account_id'=>$accountId);

            if($ids){
                $condition .= ' AND id IN('.$ids.')';
            }

            $result = $this->_model->batchUpdateFreightTemplateIdByAccountId($attributes,$condition,$param);
            if($result){
                $jsonData = array(
                        'message' => '更改成功',
                        'forward' =>'/aliexpress/aliexpressproduct/list',
                        'navTabId'=> 'page' .AliexpressProduct::getIndexNavTabId(),
                        'callbackType'=>'closeCurrent'
                );
                echo $this->successJson($jsonData);
                exit;
            }
            echo $this->failureJson(array('message'=>'修改运费模板失败'));
        }else{
            echo $this->failureJson(array('message'=>'没有找到账号信息'));
        }        
    }


    /**
     * 根据账号自动更新库存为0或为1的数据
     * /aliexpress/aliexpressproduct/updateipmskustock/accountID/150
     */
    public function actionUpdateipmskustock(){
        ini_set('memory_limit','2048M');
        set_time_limit(4*3600);
        ini_set("display_errors", true);

        //暂时停止库存调0
        exit();

        $warehouseSkuMapModel               = new WarehouseSkuMap();
        $aliexpressProductVariationModel    = new AliexpressProductVariation();
        $aliexpressProductModel             = new AliexpressProduct();
        $aliZeroStockSKUModel               = new AliexpressZeroStockSku();
        $logModel                           = new AliexpressLog();

        //采购周期
        $bakDays = Yii::app()->request->getParam('bakDays');
        if(!$bakDays){
            $bakDays = 10;
        }

        //账号
        $accountID = Yii::app()->request->getParam('accountID');
        // if(!$accountID){
        //     exit('账号不存在');
        // }

        //90天以外的新品
        $days = Yii::app()->request->getParam('days');
        if(!$days){
            $days = 90;
        }

        //指定某个特定sku----用于测试
        $setSku = Yii::app()->request->getParam('sku');

        $select = 't.sku';
        $type = 0;
        $eventName = AliexpressZeroStockSku::EVENT_ZERO_STOCK;
        $limit = 200;
        $offset = 0;

        /**------------春节库存调0程序，2017-02-06  可以移除此代码  开始-------------**/
        $times = time();
        $oneTwenty = strtotime('2017-01-20 00:00:00');
        $twoSix = strtotime('2017-02-15 00:00:00');
        /**------------春节库存调0程序，2017-02-06  可以移除此代码  结束-------------**/

        //排除海外仓账户 is_overseas_warehouse
        if($accountID){
            $accountInfo = AliexpressAccount::getAccountInfoById($accountID);
            if ($accountInfo){
                if ($accountInfo['is_overseas_warehouse'] == 1){
                    return false;
                }
            }
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
                    $command = $aliexpressProductVariationModel->getDbConnection()->createCommand()
                        ->from($aliexpressProductVariationModel->tableName() . " as t")
                        ->leftJoin($aliexpressProductModel->tableName()." as p", "p.id=t.product_id")
                        ->select("t.id, t.sku, t.sku_id, t.sku_code as online_sku, p.account_id, p.aliexpress_product_id, t.ipm_sku_stock as product_stock, t.product_id")
                        ->where('p.account_id = '.$accountID)
                        ->andWhere("p.product_status_type='onSelling'")
                        ->andWhere("t.ipm_sku_stock>0");
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
                        $conditions = 't.available_qty <= IFNULL(s.ali_day_sale_num,0) 
                                    AND t.warehouse_id = :warehouse_id 
                                    AND p.product_is_multi IN(0,1,2)  
                                    AND p.product_status NOT IN(6,7) 
                                    AND t.sku IN('.MHelper::simplode($skuArr).')';
                        $param = array(':warehouse_id'=>WarehouseSkuMap::WARE_HOUSE_GM);
                        $skuList = $warehouseSkuMapModel->getSkuListLeftJoinProductAndSalesByCondition($conditions, $param, '', $select);
                        $type = 7;
                    }else{
                        $conditions = 't.available_qty < :available_qty 
                                    AND t.warehouse_id = :warehouse_id 
                                    AND p.product_is_multi IN(0,1,2) 
                                    AND p.product_bak_days > :product_bak_days 
                                    AND p.product_status <> 7 
                                    AND t.sku IN('.MHelper::simplode($skuArr).') 
                                    AND (p.create_time <= NOW() - INTERVAL '.$days.' DAY OR (qe.qe_check_result = 1 AND qe.qe_check_time <= NOW() - INTERVAL 45 DAY))';
                        $param = array(':available_qty'=>1, ':warehouse_id'=>WarehouseSkuMap::WARE_HOUSE_GM, ':product_bak_days'=>$bakDays);
                        $skuList = $warehouseSkuMapModel->getSkuListLeftJoinProductAndQERecordByCondition($conditions, $param, '', $select);
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
                        if($aliZeroStockSKUModel->checkHadRunningForDay($variant['online_sku'], $accountID, $variant['aliexpress_product_id'])){
                            continue;
                        }

                        $time = date("Y-m-d H:i:s");
                        $editMutilpleSkuStocksRequest = new EditMutilpleSkuStocksRequest;
                        $editMutilpleSkuStocksRequest->setAccount($variant['account_id']);
                        $editMutilpleSkuStocksRequest->setProductID($variant['aliexpress_product_id']);
                        $stocks = 0;
                        $res = $this->_startSendRequestBySkuID($editMutilpleSkuStocksRequest, $variant['sku_id']);
                        if(!$res){
                            $stocks = 1;
                            $res = $this->_startSendRequestBySkuID($editMutilpleSkuStocksRequest, $variant['sku_id'], 1);
                        }
                        $msg = "";
                        $message = "sellerSku:{$variant['online_sku']}, productID:{$variant['aliexpress_product_id']} ";
                        if($res){
                            //更新产品多属性刊登表的库存为0
                            $aliexpressProductVariationModel->updateVariationById($variant['id'],array('ipm_sku_stock'=>$stocks));
                            $status = 2;//成功
                            $message .= '修改库存为'.$stocks.'成功';
                        }else{
                            $status = 3;//失败
                            $message .= $editMutilpleSkuStocksRequest->getErrorMsg();
                            $msg .= $message;
                        }

                        $addData = array(
                                'product_id'=>  $variant['aliexpress_product_id'],
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
                        $zeroStockParam = array(':seller_sku'=>$variant['online_sku'], ':product_id'=>$variant['aliexpress_product_id']);
                        $existsInfo = $aliZeroStockSKUModel->getZeroSkuOneByCondition($zeroStockCondition,$zeroStockParam);
                        if($existsInfo){
                            continue;
                        }else{
                            $aliZeroStockSKUModel->saveData($addData);
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
            $accountList = AliexpressAccount::model()->getIdNamePairs();
            foreach($accountList as $key => $value){
                MHelper::runThreadSOCKET('/'.$this->route.'/accountID/' . $key);
                sleep(1);
            }
        }

    }


    /**
     * 可以库存大于等于3  恢复库存数为99
     * /aliexpress/aliexpressproduct/recoveripmskustock           循环恢复所有库存数
     */
    public function actionRecoveripmskustock(){
        ini_set('memory_limit','2048M');
        set_time_limit(4*3600);
        ini_set("display_errors", true);
        error_reporting(E_ALL);

        $warehouseSkuMapModel               = new WarehouseSkuMap();
        $aliexpressProductVariationModel    = new AliexpressProductVariation();
        $aliexpressProductModel             = new AliexpressProduct();
        $aliZeroStockSKUModel               = new AliexpressZeroStockSku();
        $logModel                           = new AliexpressLog();

        //账号
        $accountID = Yii::app()->request->getParam('accountID');
        
        //排除海外仓账户 is_overseas_warehouse
        if($accountID){
            $accountInfo = AliexpressAccount::getAccountInfoById($accountID);
            if ($accountInfo){
                if ($accountInfo['is_overseas_warehouse'] == 1){
                    return false;
                }
            }
        }

        //指定某个特定sku----用于测试
        $setSku = Yii::app()->request->getParam('sku');

        $select = 't.sku';
        $eventName = 'updateipmskustockall';
        $limit = 200;
        $offset = 0;
        $stocks = 500;

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
                    $command = $aliexpressProductVariationModel->getDbConnection()->createCommand()
                        ->from($aliexpressProductVariationModel->tableName() . " as t")
                        ->leftJoin($aliexpressProductModel->tableName()." as p", "p.id=t.product_id")
                        ->select("t.id, t.sku, t.sku_id, t.sku_code as online_sku, p.account_id, p.aliexpress_product_id, t.ipm_sku_stock as product_stock, t.product_id")
                        ->where('p.account_id = '.$accountID)
                        ->andWhere("p.product_status_type = 'onSelling'")
                        ->andWhere("t.ipm_sku_stock <= 1");
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

                    $conditions = '(t.available_qty + t.transit_qty) >= :available_qty 
                                AND t.warehouse_id = :warehouse_id 
                                AND p.product_is_multi IN(0,1,2) 
                                AND p.product_status <> 7 
                                AND p.product_bak_days <= :product_bak_days 
                                AND t.sku IN('.MHelper::simplode($skuArr).')';
                    $param = array(':available_qty'=>10, ':warehouse_id'=>WarehouseSkuMap::WARE_HOUSE_GM, ':product_bak_days'=>3);
                    $skuList = $warehouseSkuMapModel->getSkuListLeftJoinProductByCondition($conditions, $param, '', $select);

                    if(!$skuList){
                        continue;            
                    } 
                    unset($skuArr);

                    foreach ($skuList as $skuVal){
                        //判断sku是否侵权
                        $checkInfringe = ProductInfringe::model()->getProductIfInfringe($skuVal['sku']);
                        if($checkInfringe){
                            continue;
                        }
                        $skuListArr[] = $skuVal['sku'];
                    }  

                    foreach ($variantListing as $variant){
                        if(!in_array($variant['sku'], $skuListArr)){
                            continue;
                        }

                        $time = date("Y-m-d H:i:s");
                        $message = "";

                        $addData = array(
                            'product_id'   => $variant['aliexpress_product_id'],
                            'seller_sku'   => $variant['online_sku'],
                            'sku'          => $variant['sku'],
                            'account_id'   => $variant['account_id'],
                            'site_id'      => 0,
                            'old_quantity' => $variant['product_stock'],
                            'create_time'  => $time
                        );

                        $editSingleSkuStockRequest = new EditSingleSkuStockRequest;//使用单个修改接口
                        $editSingleSkuStockRequest->setAccount($variant['account_id']);
                        $editSingleSkuStockRequest->setProductID($variant['aliexpress_product_id']);
                        $editSingleSkuStockRequest->setSkuID($variant['sku_id']);
                        $editSingleSkuStockRequest->setIpmSkuStock($stocks);
                        $response = $editSingleSkuStockRequest->setRequest()->sendRequest()->getResponse();
                        if($editSingleSkuStockRequest->getIfSuccess()){
                            $aliexpressProductVariationModel->updateVariationById($variant['id'],array('ipm_sku_stock'=>$stocks));
                            $status = 2;//成功
                            $message = 'success';
                            $addData['is_restore'] = 1;
                            $addData['restore_time'] = $time;
                            $addData['restore_num'] = 1;
                            $addData['restore_quantity'] = $stocks;

                            $aliZeroData = array(
                                'is_restore'    => 1,
                                'restore_time'  => date('Y-m-d H:i:s'), 
                                'msg'           => 'success'
                            );
                        }else{
                            $status = 3;//失败
                            $message = $editSingleSkuStockRequest->getErrorMsg();
                            $aliZeroData = array(
                                'is_restore'    => 2, 
                                'restore_time'  => date('Y-m-d H:i:s'), 
                                'msg'           => $editSingleSkuStockRequest->getErrorMsg()
                            );
                        }

                        $addData['status'] = $status;
                        $addData['msg'] = $message;

                        $zeroStockCondition = 'seller_sku = :seller_sku AND product_id = :product_id AND status = 2 AND is_restore = 0';
                        $zeroStockParam = array(':seller_sku'=>$variant['online_sku'], ':product_id'=>$variant['aliexpress_product_id']);
                        $existsInfo = $aliZeroStockSKUModel->getZeroSkuOneByCondition($zeroStockCondition,$zeroStockParam);
                        if($existsInfo){
                            $aliZeroData['restore_num'] = $existsInfo['restore_num'] + 1;
                            $aliZeroStockSKUModel->getDbConnection()->createCommand()
                                ->update($aliZeroStockSKUModel->tableName(), $aliZeroData, 
                                    "seller_sku='{$variant['online_sku']}' AND product_id = '{$variant['aliexpress_product_id']}' AND status = 2 AND is_restore = 0");
                        }else{
                            $aliZeroStockSKUModel->saveData($addData);
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
            $accountList = AliexpressAccount::model()->getIdNamePairs();
            foreach($accountList as $key => $value){
                MHelper::runThreadSOCKET('/'.$this->route.'/accountID/' . $key);
                sleep(1);
            }
        }                 
    }


    /**
     * 复制刊登
     */
    public function actionCopylisting(){
        $ids = Yii::app()->request->getParam('ids');
        $accountList = AliexpressAccount::model()->getIdNamePairs();
        $this->render('copylisting', array('model'=>$this->_model, 'accountList'=>$accountList, 'ids'=>$ids));
    }


    /**
     * 复制刊登sku到待刊登列表
     */
    public function ActionCopyproductadd(){
        ini_set('memory_limit','2048M');
        set_time_limit(2*3600);
        ini_set("display_errors", true);
        $accountArr = Yii::app()->request->getParam('AliexpressProduct');
        $ids = $accountArr['ids'];
        $accountIdArr = $accountArr['account_id'];
        if(!$ids){
            echo $this->failureJson(array('message'=>'请选择'));
            exit;
        }

        if(!$accountIdArr){
            echo $this->failureJson(array('message'=>'请选择要刊登的账号'));
            exit;
        }

        $aliProductModel    = new AliexpressProduct();
        $aliProductAddModel = new AliexpressProductAdd();
        $logModel           = new AliexpressLog();
        $batchAddModel      = new AliexpressLogBatchProductAdd();
        $eventName          = 'batch_product_add';
        $productInfo = $aliProductModel->getListByCondition('id,sku,account_id,aliexpress_product_id','id IN('.$ids.')');
        if(!$productInfo){
            echo $this->failureJson(array('message'=>'没有找到刊登的数据'));
            exit;
        }

        //刊登类型为复制刊登
        $addType = AliexpressProductAdd::ADD_TYPE_COPY;    

        foreach ($productInfo as $product) {
            foreach ($accountIdArr as $accountIDValue) {

                //写log
                // $logID = $logModel->prepareLog($accountIDValue, $eventName);
                // if(!$logID){
                //     exit('日志写入错误');
                // }

                // //检测是否可以允许
                // if(!$logModel->checkRunning($accountIDValue, $eventName)){
                //     $logModel->setFailure($logID, Yii::t('system', 'There Exists An Active Event'));
                //     exit('There Exists An Active Event');
                // }

                // //设置运行
                // $logModel->setRunning($logID);


                $result = $aliProductAddModel->productAddByCopy($product['sku'],$accountIDValue,$addType,$product['account_id']);
                if(!$result[0]){
                    $insertData = array(
                        'product_id'            => $product['id'],
                        'aliexpress_product_id' => $product['aliexpress_product_id'],
                        'create_user_id'        => isset(Yii::app()->user->id) ? Yii::app()->user->id : 0,
                        'message'               => $result[1],
                        'create_time'           => date('Y-m-d H:i:s')
                    );
                    $batchAddModel->savePrepareLog($insertData);
                    continue;
                }

                // $logModel->setSuccess($logID, "success");
            }
        }
        
        $jsonData = array(
                'message' => '复制刊登成功',
                'forward' =>'/aliexpress/aliexpressproduct/list',
                'navTabId'=> 'page' . AliexpressProductSellerRelation::getIndexNavTabId(),
                'callbackType'=>'closeCurrent'
            );
        echo $this->successJson($jsonData);
    }


    /**
     * @desc 系统可用库存小于等于0：单品广告下架，多属性子sku可以改为0的改0，不能的则下架广告
     * @link /aliexpress/aliexpressproduct/autoshelfproducts/accountID/150/sku/111
     */
    public function actionAutoshelfproducts() {
        set_time_limit(5*3600);
        ini_set('display_errors', true);
        error_reporting(E_ALL);

        $warehouseSkuMapModel            = new WarehouseSkuMap();
        $aliexpressProductVariationModel = new AliexpressProductVariation();
        $aliexpressProductModel          = new AliexpressProduct();
        $logModel                        = new AliexpressLog();
        $aliLogOfflineModel              = new AliexpressLogOffline();

        //账号
        $accountID = Yii::app()->request->getParam('accountID');

        //指定某个特定sku----用于测试
        $setSku = Yii::app()->request->getParam('sku');

        $select    = 't.sku, t.available_qty';
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
                    $command = $aliexpressProductVariationModel->getDbConnection()->createCommand()
                        ->from($aliexpressProductVariationModel->tableName() . " as t")
                        ->leftJoin($aliexpressProductModel->tableName()." as p", "p.id=t.product_id")
                        ->select("t.id,t.sku,p.account_id,t.aliexpress_product_id,t.sku_id,t.sku_code as seller_sku,t.product_id,p.is_variation,t.ipm_sku_stock")
                        ->where('p.account_id = '.$accountID)
                        ->andWhere("p.product_status_type='onSelling'")
                        ->andWhere("t.ipm_sku_stock>0");
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
                        $stockArr[$skuVal['sku']] = $skuVal['available_qty'];
                    }

                    //取出今天已经下架的记录
                    $newSkuArr = array();
                    $diffSkuArr= array();
                    $startTime = date("Y-m-d 00:00:00");
                    $endTime   = date("Y-m-d H:i:s");
                    $logWhere  = "account_id = {$accountID} AND start_time >='{$startTime}' AND start_time <= '{$endTime}'";
                    $order     = '';
                    $group     = 'sku';
                    $offLineList = $aliLogOfflineModel->getListByCondition('sku',$logWhere,$order,$group);
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

                            $editMutilpleSkuStocksRequest = new EditMutilpleSkuStocksRequest;
                            $editMutilpleSkuStocksRequest->setAccount($variant['account_id']);
                            $editMutilpleSkuStocksRequest->setProductID($variant['aliexpress_product_id']);
                            $editMutilpleSkuStocksRequest->setSkuID($variant['sku_id']);
                            $editMutilpleSkuStocksRequest->setIpmSkuStock($stocks);
                            $editMutilpleSkuStocksRequest->push();
                            $response = $editMutilpleSkuStocksRequest->setRequest()->sendRequest()->getResponse();
                            $editMutilpleSkuStocksRequest->clean();
                            $res = $editMutilpleSkuStocksRequest->getIfSuccess();
                            if($res){
                                //更新产品多属性刊登表的库存为0
                                $aliexpressProductVariationModel->updateVariationById($variant['id'],array('ipm_sku_stock'=>$stocks));
                            }

                        }else{

                            //判断是否是多属性sku
                            if($variant['is_variation'] == AliexpressProduct::VARIATION_YES){

                                $editMutilpleSkuStocksRequest = new EditMutilpleSkuStocksRequest;
                                $editMutilpleSkuStocksRequest->setAccount($variant['account_id']);
                                $editMutilpleSkuStocksRequest->setProductID($variant['aliexpress_product_id']);
                                $editMutilpleSkuStocksRequest->setSkuID($variant['sku_id']);
                                $editMutilpleSkuStocksRequest->setIpmSkuStock(0);
                                $editMutilpleSkuStocksRequest->push();
                                $response = $editMutilpleSkuStocksRequest->setRequest()->sendRequest()->getResponse();
                                $editMutilpleSkuStocksRequest->clean();
                                $res = $editMutilpleSkuStocksRequest->getIfSuccess();
                                // $res = $this->_startSendRequestBySkuID($editMutilpleSkuStocksRequest, $variant['sku_id']);
                                if($res){
                                    //更新产品多属性刊登表的库存为0
                                    $aliexpressProductVariationModel->updateVariationById($variant['id'],array('ipm_sku_stock'=>$stocks));
                                    $status  = 1;//成功
                                    $message = '修改库存为'.$stocks.'成功';
                                }else{
                                    //判断多属性可用库存是否都为0，如果是，下架
                                    if(isset($response->error_code) && $response->error_code == '13004001'){
                                        $flag = $this->_model->offlineAliexpressProduct($variant['account_id'], $variant['aliexpress_product_id']);
                                        if($flag){
                                            $status  = 1;//成功
                                            $message = '下架成功';
                                            $this->_model->updateProductByPk(
                                                $variant['product_id'], 
                                                array('product_status_type'=>AliexpressProduct::PRODUCT_STATUS_OFFLINE)
                                            );
                                        }else{
                                            $status  = 0;//失败
                                            $message = '产品管理已更新为下架---平台下架失败，'.$this->_model->getErrorMessage();
                                            $this->_model->updateProductByPk(
                                                $variant['product_id'], 
                                                array('product_status_type'=>AliexpressProduct::PRODUCT_STATUS_OFFLINE)
                                            );
                                        }
                                    }else{
                                        $status  = 0;//失败
                                        $message = '修改库存失败,'.$editMutilpleSkuStocksRequest->getErrorMsg();
                                    }
                                }

                            }else{

                                $flag = $this->_model->offlineAliexpressProduct($variant['account_id'], $variant['aliexpress_product_id']);
                                if($flag){
                                    $status  = 1;//成功
                                    $message = '下架成功';
                                    $this->_model->updateProductByPk(
                                        $variant['product_id'], 
                                        array('product_status_type'=>AliexpressProduct::PRODUCT_STATUS_OFFLINE)
                                    );
                                }else{
                                    $status  = 0;//失败
                                    $message = '产品管理已更新为下架---平台下架失败，'.$this->_model->getErrorMessage();
                                    $this->_model->updateProductByPk(
                                        $variant['product_id'], 
                                        array('product_status_type'=>AliexpressProduct::PRODUCT_STATUS_OFFLINE)
                                    );
                                }

                            }

                            $addData = array(
                                'product_id'        => $variant['aliexpress_product_id'],
                                'sku'               => $variant['sku'],
                                'account_id'        => $variant['account_id'],
                                'event'             => 'autoshelfproducts',
                                'status'            => $status,
                                'ipm_sku_stock'     => $variant['ipm_sku_stock'],                            
                                'message'           => $message,
                                'start_time'        => $time,
                                'response_time'     => date("Y-m-d H:i:s"),
                                'operation_user_id' => 1
                            );

                            $aliLogOfflineModel->savePrepareLog($addData);
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
            $accountList = AliexpressAccount::model()->getIdNamePairs();
            foreach($accountList as $key => $value){
                MHelper::runThreadSOCKET('/'.$this->route.'/accountID/' . $key);
                sleep(1);
            }
        }
    }


    /**
     * @desc 速卖通双十一多个规则，通过销量改库存和listing下架
     * 禅道详细规则：#1899
     * 速卖通双十一根据多个规则批量改Listing下架（30天内）（11.9号运行到15号停止此规则）：
     * 1.采购周期15天以上，SKU的国内仓（可用库存数量+在途）数量<7天销量，
     * 把其Listing销量前三的，根据其Listing30天内销量占比分摊（可用+在途）数量（如果小于0的，改为1，即最小为1），
     * 前三之外的其它Listing库存可以改为0的改为0，不能则改为1；此规则排除品牌产品、待清仓、停售三类SKU。
     * 如果SKU的国内仓（可用库存数量+在途）数量<=0，则把其全部Listing改库存为1；
     * 如果其Listing销量前三为0，则其Listing库存保持不变，即不做操作。
     * 
     * 2.所有品牌产品SKU国内仓可用库存数小于1000，30天内其Lisitng销量排前三的平摊可用数量，如果<=0的，改为1，销量排前三之外的其余全部调为1。
     * 如果SKU的国内仓可用库存数量<=0，则把其全部Listing改库存为1；
     * 如果其Listing销量前三为0，则其Listing库存保持不变，即不做操作。
     * @link /aliexpress/aliexpressproduct/setstockandofflinebysales/type/1/sku/90229.01,90229.04/account_id/170
     * @link /aliexpress/aliexpressproduct/setstockandofflinebysales/type/2/sku/100963,100967/account_id/167
     */
    public function actionSetstockandofflinebysales(){
        
        echo '此规则截止到2016-11-13号停止执行。';exit;

        set_time_limit(3600);
        ini_set('display_errors', true);
        error_reporting(E_ALL);

        if (time() - strtotime('2016-11-15') >= 0){
            echo '此规则截止到2016-11-15号停止执行。';exit;
        }

        $rulesType     = Yii::app()->request->getParam("type");    //规则类型，按上述说明1、2条规则对应
        $runType       = Yii::app()->request->getParam("runtype"); 
        $testSKUs      = Yii::app()->request->getParam("sku");
        $testAccountID = Yii::app()->request->getParam("account_id");
        $loopNum = 0;
        $testSkuList = array();
        $testFlag = false;  //是否为测试
        $rulesType = (empty($rulesType) || (int)$rulesType == 1) ? 1 : 2;

        //测试环境下必须指定sku和账号
        // if($runType != "y" && (empty($testSKUs) || empty($testAccountID))){
        //     exit("测试下必须指定sku列表和账号，多个sku之间用半角,隔开。示例：{$this->route}/account_id/1/sku/1123,22444,3434.09");
        // }elseif ($runType != "y"){
        //     $testFlag = true;
        //     $testSkuList = explode(",", $testSKUs);
        // }

        if (!empty($testSKUs)){
            $testFlag = true;
            $testSkuList = explode(",", $testSKUs);            
        }
 
        $ProductModel                    = new Product();    
        $aliexpressProductModel          = new AliexpressProduct();
        $aliexpressProductVariationModel = new AliexpressProductVariation();
        $productSalesModel               = new AliexpressProductSales();
        $aliZeroStockSKUModel            = new AliexpressZeroStockSku();        
        $editSingleSkuStockRequest       = new EditSingleSkuStockRequest;   //使用单个修改接口
        $editMutilpleSkuStocksRequest    = new EditMutilpleSkuStocksRequest;

        $productTablename      = "ueb_product." . Product::model ()->tableName ();
        $productSalesTablename = "ueb_product." . ProductSales::model ()->tableName ();  
        $warehouseTablename    = "ueb_warehouse." . WarehouseSkuMap::model ()->tableName (); 

        $skuWarehouse = WarehouseSkuMap::WARE_HOUSE_GM;   //国内仓
        $bakDays      = 15;  //采购周期
        $limit        = 500;
        $offset       = 0;
        do{
            //条件下的有效SKU数据
            $vaildSKUList = array();
            $vaildSKUArr  = array();
            $validSKUQtyArr = array();

            if ($rulesType == 1){
                //采购周期>=15
                //排除三类SKU：品牌产品(ueb_product.product_brand_id>0)、待清仓(ueb_product.product_status=6)、停售(ueb_product.product_status=7)
                $conditions = "p.product_bak_days >= {$bakDays} AND p.product_brand_id = 0 AND p.product_status NOT IN (6,7)";
                //SKU的国内仓（可用库存数量+在途）数量<7天销量
                $conditions .= " AND s.platform_code = 'ALI' AND s.day_type = 7 AND w.warehouse_id = " .$skuWarehouse;
                $conditions .= " AND (w.available_qty + w.transit_qty) < s.sale_num";
                $command = $ProductModel->getDbConnection()->createCommand()->select("p.sku,w.available_qty,(w.available_qty + w.transit_qty) as num")
                        ->from($productTablename . " as p")
                        ->leftJoin($warehouseTablename . " as w", "w.sku = p.sku")
                        ->leftJoin($productSalesTablename . " as s", "s.sku = p.sku")
                        ->where($conditions);                      
            }else{
                //所有品牌产品SKU国内仓可用库存数小于1000
                $conditions = "p.product_brand_id > 0 AND w.available_qty < 1000 AND w.warehouse_id = " .$skuWarehouse;
                $command = $ProductModel->getDbConnection()->createCommand()->select("p.sku,w.available_qty,(w.available_qty + w.transit_qty) as num")
                        ->from($productTablename . " as p")
                        ->leftJoin($warehouseTablename . " as w", "w.sku = p.sku")
                        ->where($conditions);
            }

            //测试
            if($testFlag){
                echo "set sku=".$testSKUs . "<br/>";
                $command->andWhere(array("IN", "p.sku", $testSkuList));
            }

            $command->limit($limit, $offset);
            $vaildSKUList = $command->queryAll();
            $offset += $limit;
            // MHelper::printvar($vaildSKUList);

            if ($vaildSKUList){
                $flag = true;
                foreach ($vaildSKUList as $val) {
                    $val = (array)$val;
                    $vaildSKUArr[] = $val['sku'];   //产品表的SKU是指子SKU
                    $validSKUQtyArr[$val['sku']] = $val['num'];                     //可用库存数量+在途
                    $validSKUAvailableQtyArr[$val['sku']] = $val['available_qty'];  //可用库存数量，用于品牌                    
                }
                unset($vaildSKUList);

                //获取listing数据
                $command = $aliexpressProductVariationModel->getDbConnection()->createCommand()
                    ->from($aliexpressProductVariationModel->tableName() . " as t")
                    ->leftJoin($aliexpressProductModel->tableName()." as p", "p.id=t.product_id")
                    ->select("t.id,t.sku,p.account_id,t.aliexpress_product_id,t.sku_id,t.sku_code as seller_sku,t.product_id,p.is_variation,t.ipm_sku_stock")
                    ->where(array("IN", "t.sku", $vaildSKUArr))
                    ->andWhere("p.product_status_type = 'onSelling'")                
                    ->andWhere("t.ipm_sku_stock > 0");
                // if($testFlag){
                //     echo "set testaccount_id=".$testAccountID . "<br/>";
                //     $command->andWhere("p.account_id=".$testAccountID);
                // }
                $variantListing = $command->queryAll();
                // MHelper::printvar($variantListing);

                $listing   = array();
                $salesInfo = array();
                if ($variantListing){
                    foreach ($variantListing as $variant) {
                        $salesNum  = 0; //listing销量
                        $salesInfo = $productSalesModel->getInfoByProductIDAndSku($variant['aliexpress_product_id'],$variant['sku']);
                        if($salesInfo) $salesNum = (int)$salesInfo['total'];
                        $variant['listing_sales_num'] = $salesNum;
                        $listing[$variant['sku']][$variant['aliexpress_product_id']] = $variant;
                    }
                }
                // MHelper::printvar($listing);
                if ($listing){
                    foreach ($listing as $variationSku => $list){
                        $listingStockArr     = array();   //销量前三
                        $listingOfflineArr   = array();   //销量前三外            
                        $listingSetStockList = array();   //listing设置库存列表
                        $listingOfflineList  = array();   //listing前三外列表
                        $listingSetStockTemp = array();

                        //规则1：SKU（可用+在途）库存
                        //规则2：SKU可用库存 
                        if($rulesType == 1){
                            $skuQty = (int)$validSKUQtyArr[$variationSku];          //SKU（可用+在途）库存                            
                        }else{
                            $skuQty = (int)$validSKUAvailableQtyArr[$variationSku]; //SKU可用库存
                        }

                        if(!$list) continue;

                        //如果SKU的国内仓（可用库存数量+在途）数量<=0，则把其全部Listing改库存为1
                        if ($skuQty <= 0){
                            $tmpList = array();
                            foreach($list as $val){
                                $val['set_stock_num'] = 1;
                                $tmpList[] = $val;
                            }
                            $listingSetStockList = $tmpList;
                        }else{
                            //降序排列，提取销量前三
                            uasort($list,array($this,'_sort_by_sales'));                            

                            if (count($list) > 3){
                                $listingStockArr = array_slice($list,0,3);
                                $listingOfflineArr = array_slice($list,3);
                            }else{
                                $listingStockArr = $list;
                            }
                            // echo '<pre>';
                            // print_r($listingStockArr);
                            // print_r($listingOfflineArr);
                            // exit;

                            //销量前三，根据其Listing30天内销量占比分摊（可用+在途）数量（如果小于0的，改为1，即最小为1）
                            if ($listingStockArr){
                                $listingTotal = 0; //前三销量总数
                                foreach($listingStockArr as $val){ 
                                    $listingTotal += $val['listing_sales_num'];
                                }

                                //如果其Listing销量前三为0，则其Listing库存保持不变，即不做操作。跳下个循环
                                if($listingTotal == 0) continue;

                                $tmpList = array();
                                foreach($listingStockArr as $val){     
                                    $listingAssignNum = 0;     
                                    $salesNum = 0;
                                    $salesNum = (int)$val['listing_sales_num'];     //listing销量                 
                                    $listingAssignNum = $skuQty * ($salesNum / $listingTotal);
                                    $listingAssignNum = round($listingAssignNum,0);   //四舍五入
                                    if($listingAssignNum < 1) $listingAssignNum = 1;
                                    $val['set_stock_num'] = $listingAssignNum;
                                    $tmpList[] = $val;
                                }
                                $listingSetStockTemp = $tmpList;
                                // MHelper::printvar($listingSetStockList);                                
                            }else{
                                //如果不存在前三list，则跳下个循环
                                continue;
                            }

                            //规则1：销量前三外，修改库存为0
                            //规则2：销量前三外，修改库存为1                            
                            if ($listingOfflineArr){
                                $stock = 0;
                                if($rulesType == 2) $stock = 1;    
                                $tmpList = array();                            
                                foreach($listingOfflineArr as $val){
                                    $val['set_stock_num'] = $stock;
                                    $tmpList[] = $val;
                                }                                
                                $listingOfflineList = $tmpList;
                            }

                            //合并前三、前三之外的listing
                            $listingSetStockList = ($listingOfflineList) ? array_merge($listingSetStockTemp,$listingOfflineList) : $listingSetStockTemp;                            
                        }

                        //更新库存操作
                        if ($listingSetStockList){
                            // echo '准备更新库存操作';
                            // MHelper::printvar($listingSetStockList);
                            foreach($listingSetStockList as $stockVal){
                                $status      = 3;   //失败
                                $logMessage  = '';
                                $errMessage  = '';
                                $RequestFlag = false;
                                $setStockNum = 0;   //设置库存数

                                $setStockNum = (int)$stockVal['set_stock_num'];
                                if ($setStockNum > 0){
                                    //使用单个修改接口
                                    $editSingleSkuStockRequest->setAccount($stockVal['account_id']);
                                    $editSingleSkuStockRequest->setProductID($stockVal['aliexpress_product_id']);
                                    $editSingleSkuStockRequest->setSkuID($stockVal['sku_id']);
                                    $editSingleSkuStockRequest->setIpmSkuStock($setStockNum); //设置库存数
                                    $editSingleSkuStockRequest->setRequest()->sendRequest()->getResponse();

                                    $errMessage = $editSingleSkuStockRequest->getErrorMsg();
                                    $RequestFlag = $editSingleSkuStockRequest->getIfSuccess();
                                }else{
                                    //设置库存为0
                                    $editMutilpleSkuStocksRequest->setAccount($stockVal['account_id']);
                                    $editMutilpleSkuStocksRequest->setProductID($stockVal['aliexpress_product_id']);                                    
                                    $setStockNum = 0;
                                    
                                    $RequestFlag = $this->_startSendRequestBySkuID($editMutilpleSkuStocksRequest, $stockVal['sku_id']);
                                    if(!$RequestFlag){
                                        $setStockNum = 1;
                                        $RequestFlag = $this->_startSendRequestBySkuID($editMutilpleSkuStocksRequest, $stockVal['sku_id'], 1);
                                    }                                    
                                }
                                // $RequestFlag = true;    //测试
                                if($RequestFlag){
                                    $status = 2;
                                    $logMessage = '修改库存成功（双十一规则）。sellerSku:' .$stockVal['seller_sku']. ',productID:' .$stockVal['aliexpress_product_id'];

                                    //更新本地listing多变体库对应库存数量
                                    $updateFlag = $aliexpressProductVariationModel->updateVariationById($stockVal['id'],array('ipm_sku_stock'=>$setStockNum));
                                    if (!$updateFlag){
                                       $logMessage .= '更新本地listing多变体库对应库存数量失败！！！更新库存量:' .$setStockNum; 
                                    }
                                }else{
                                    $logMessage = '修改库存失败（双十一规则）。sellerSku:' .$stockVal['seller_sku']. ',productID:' .$stockVal['aliexpress_product_id']. '。原因：' .$errMessage;
                                }                                

                                //判断库里是否已存在（未恢复状态）
                                $zeroCondition = 'seller_sku = :seller_sku AND product_id = :product_id AND is_restore = :is_restore';
                                $zeroParam = array(':seller_sku' => $stockVal['seller_sku'], ':product_id' => $stockVal['aliexpress_product_id'], ':is_restore' => 0);
                                $zeroStock = $aliZeroStockSKUModel->getZeroSkuOneByCondition($zeroCondition, $zeroParam);                                
                                if ($zeroStock){
                                    $updateData = array(
                                            'sku'          => $stockVal['sku'],
                                            'account_id'   => $stockVal['account_id'],
                                            'site_id'      => 0,
                                            'old_quantity' => $stockVal['ipm_sku_stock'],    //多变体原库存量
                                            'set_quantity' => $setStockNum,                  //设置的库存量
                                            'status'       => $status,
                                            'create_time'  => date("Y-m-d H:i:s"),
                                            'type'         => 6,                             //类型：双十一定制库存
                                            'msg'          => $logMessage
                                     );
                                    $aliZeroStockSKUModel->updateDataByID($updateData,$zeroStock['id']);                                    
                                }else{
                                    //新增入库
                                    $addData = array(
                                            'product_id'   => $stockVal['aliexpress_product_id'],
                                            'seller_sku'   => $stockVal['seller_sku'],
                                            'sku'          => $stockVal['sku'],
                                            'account_id'   => $stockVal['account_id'],
                                            'site_id'      => 0,
                                            'old_quantity' => $stockVal['ipm_sku_stock'],    //多变体原库存量
                                            'set_quantity' => $setStockNum,                  //设置的库存量
                                            'status'       => $status,
                                            'create_time'  => date("Y-m-d H:i:s"),
                                            'type'         => 6,                             //类型：双十一定制库存
                                            'msg'          => $logMessage
                                     );
                                    $aliZeroStockSKUModel->saveData($addData);
                                }                            
                            }
                        }
/*
                        //下架操作
                        if ($listingOfflineList){
                            foreach($listingOfflineList as $offlineVal){
                                // MHelper::printvar($offlineVal);
                                $logMessage  = '';
                                $status      = 0;
                                $offlineDate = date('Y-m-d H:i:s');
                                //创建日志
                                $logID = $logModel->prepareLog($offlineVal['account_id'], AliexpressProduct::EVENT_UPDATE_NAME);
                                if($logID){
                                    $eventLog = AliexpressLog::model()->saveEventLog(AliexpressProduct::EVENT_UPDATE_NAME, array(
                                            'log_id'        => $logID,
                                            'account_id'    => $offlineVal['account_id'],
                                            'start_time'    => $offlineDate,
                                            'end_time'      => $offlineDate,
                                            'status'        => 0,
                                    ));
                                    //设置日志为正在运行
                                    AliexpressLog::model()->setRunning($logID);
                                    $offlineFlag = $this->_model->offlineAliexpressProduct($offlineVal['account_id'], $offlineVal['aliexpress_product_id']);
                                    // $offlineFlag = true;    //测试
                                    if($offlineFlag){
                                        //更新本地库
                                        $status  = 1;
                                        $logMessage = '下架成功（双十一规则）';
                                        $this->_model->updateProductByPk($offlineVal['product_id'], array('product_status_type'=>'offline'));
                                        AliexpressLog::model()->setSuccess($logID);
                                        AliexpressLog::model()->saveEventStatus(AliexpressProduct::EVENT_UPDATE_NAME, $eventLog, AliexpressLog::STATUS_SUCCESS);
                                    }else{
                                        AliexpressLog::model()->setFailure($logID, $this->_model->getErrorMessage());
                                        AliexpressLog::model()->saveEventStatus(AliexpressProduct::EVENT_UPDATE_NAME, $eventLog, AliexpressLog::STATUS_FAILURE);
                                        $logMessage = '下架失败（双十一规则）：'.$this->_model->getErrorMessage();
                                    }
                                }else{                                        
                                    $logMessage = "日志生成错误!";
                                }
                                //下架记录入库
                                $addData = array(
                                    'product_id'        => $offlineVal['aliexpress_product_id'],
                                    'sku'               => $offlineVal['sku'],
                                    'account_id'        => $offlineVal['account_id'],
                                    'event'             => 'autoshelfproducts',
                                    'status'            => $status,
                                    'ipm_sku_stock'     => $offlineVal['ipm_sku_stock'],                            
                                    'message'           => $logMessage,
                                    'start_time'        => $offlineDate,
                                    'response_time'     => $offlineDate,
                                    'operation_user_id' => 1
                                );
                                $aliLogOfflineModel->savePrepareLog($addData);     
                                // echo 'OK'.$logMessage;exit;                             
                            }
                        }
*/                   
                    // echo 'OK'.$skuQty;
                    // MHelper::printvar($listingSetStockList);
                    // exit;      
                    }                        
                    unset($listing);
                }else{
                    echo("no match that sku lisitng");
                }
            }else{
                $flag = false;
            }
            unset($vaildSKUArr);
            unset($validSKUQtyArr);
            unset($validSKUAvailableQtyArr);
        }while($flag);     
        echo 'Finish!';
        Yii::app()->end();
    }

    //用于销量排序
    private function _sort_by_sales($x,$y){
        $a = (int)$y['listing_sales_num'];
        $b = (int)$x['listing_sales_num'];
        if($a == $b) return 0;
        return ($a < $b) ? -1 : 1;          
    }

    /**
     * @desc EXCEL导入库存置为0和下架
     * @link /aliexpress/aliexpressproduct/setstockandofflinebyimportsku/type/1/sku/100963,100967
     */
    public function actionSetstockandofflinebyimportsku(){
        set_time_limit(3600*3);
        ini_set('display_errors', true);
        error_reporting(E_ALL);

        $testSKUs      = Yii::app()->request->getParam("sku");
        $testAccountID = Yii::app()->request->getParam("account_id");
        $type          = Yii::app()->request->getParam("type"); //操作类型:1-设置库存为0；2-下架

        $type = (empty($type) || (int)$type == 1) ? 1 : 2;
        $testSkuList = array();
        $testFlag = false;  //是否为测试

        if (!empty($testSKUs)){
            $testFlag = true;
            $testSkuList = explode(",", $testSKUs);            
        }
 
        $SkuModel                        = new ProductImportSku();
        $ProductModel                    = new Product();    
        $aliexpressProductModel          = new AliexpressProduct();
        $aliexpressProductVariationModel = new AliexpressProductVariation();
        $aliZeroStockSKUModel            = new AliexpressZeroStockSku();        
        $editMutilpleSkuStocksRequest    = new EditMutilpleSkuStocksRequest;
        $aliLogOfflineModel              = new AliexpressLogOffline();

        $productTablename = "ueb_product." . Product::model ()->tableName ();
        $limit  = 500;
        $offset = 0;
        $params = array();
        do{
            $conditions   = '';
            $vaildSKUList = array();
            $vaildSKUArr  = array();
            $importIDsArr = array();
            //@todo test
            $conditions = "aliexpress_status = 0";
            //指定SKU
            if($testFlag){
                echo "set sku=".$testSKUs . "<br/>";
                $conditions .= " AND sku in('". implode("','", $testSkuList) ."')";
            }            
            //$limits = "{$offset}, {$limit}";
            $limits = "0, {$limit}";
            $vaildSKUList = $SkuModel->getSkuListByCondition($conditions, $params, $limits, "id,sku");
            $offset += $limit;
            // MHelper::printvar($vaildSKUList);

            if ($vaildSKUList){
                $flag = true;
                foreach ($vaildSKUList as $val) {
                    $val = (array)$val;
                    $vaildSKUArr[] = $val['sku']; 
                    $importIDsArr[] = $val['id'];                  
                }
                unset($vaildSKUList);

                //获取listing数据
                $command = $aliexpressProductVariationModel->getDbConnection()->createCommand()
                    ->from($aliexpressProductVariationModel->tableName() . " as t")
                    ->leftJoin($aliexpressProductModel->tableName()." as p", "p.id=t.product_id")
                    ->select("t.id,t.sku,p.account_id,t.aliexpress_product_id,t.sku_id,t.sku_code as seller_sku,t.product_id,p.is_variation,t.ipm_sku_stock")
                    ->where(array("IN", "t.sku", $vaildSKUArr))
                    ->andWhere("p.product_status_type = 'onSelling'")                
                    ->andWhere("t.ipm_sku_stock > 0");
                // if($testFlag){
                //     echo "set testaccount_id=".$testAccountID . "<br/>";
                //     $command->andWhere("p.account_id=".$testAccountID);
                // }
                $variantListing = $command->queryAll();
                // MHelper::printvar($variantListing);

                $listing = array();
                if ($variantListing){
                    foreach ($variantListing as $variant) {
                        $listing[$variant['aliexpress_product_id']] = $variant;
                    }
                }
                // MHelper::printvar($listing);
                if ($listing){
                    foreach ($listing as $stockVal){
                        //置库存为0
                        if ($type == 1){
                            $status      = 3;   //失败
                            $logMessage  = '';
                            $RequestFlag = false;
                            $setStockNum = 0;   //设置库存数

                            $editMutilpleSkuStocksRequest->setAccount($stockVal['account_id']);
                            $editMutilpleSkuStocksRequest->setProductID($stockVal['aliexpress_product_id']);                            
                            $RequestFlag = $this->_startSendRequestBySkuID($editMutilpleSkuStocksRequest, $stockVal['sku_id']);
                            if(!$RequestFlag){
                                $setStockNum = 1;
                                $RequestFlag = $this->_startSendRequestBySkuID($editMutilpleSkuStocksRequest, $stockVal['sku_id'], 1);
                            }                                    
                            if($RequestFlag){
                                $status = 2;   //成功
                                $logMessage = '修改库存成功。sellerSku:' .$stockVal['seller_sku']. ',productID:' .$stockVal['aliexpress_product_id'];

                                //更新本地listing多变体库对应库存数量
                                $updateFlag = $aliexpressProductVariationModel->updateVariationById($stockVal['id'],array('ipm_sku_stock'=>$setStockNum));
                                if (!$updateFlag){
                                   $logMessage .= '更新本地listing多变体库对应库存数量失败！！！更新库存量:' .$setStockNum; 
                                }
                            }else{
                                $logMessage = '修改库存失败。sellerSku:' .$stockVal['seller_sku']. ',productID:' .$stockVal['aliexpress_product_id'];
                            }                                

                            //判断置0库里是否已存在（未恢复状态）
                            $zeroCondition = 'seller_sku = :seller_sku AND product_id = :product_id AND is_restore = :is_restore';
                            $zeroParam = array(':seller_sku' => $stockVal['seller_sku'], ':product_id' => $stockVal['aliexpress_product_id'], ':is_restore' => 0);
                            $zeroStock = $aliZeroStockSKUModel->getZeroSkuOneByCondition($zeroCondition, $zeroParam);                                
                            //新增入库
                            if (!$zeroStock){                                
                                $addData = array(
                                        'product_id'   => $stockVal['aliexpress_product_id'],
                                        'seller_sku'   => $stockVal['seller_sku'],
                                        'sku'          => $stockVal['sku'],
                                        'account_id'   => $stockVal['account_id'],
                                        'site_id'      => 0,
                                        'old_quantity' => $stockVal['ipm_sku_stock'],
                                        'status'       => $status,
                                        'create_time'  => date("Y-m-d H:i:s"),
                                        'type'         => 5,    //类型：手动导入sku
                                        'msg'          => $logMessage
                                 );
                                $aliZeroStockSKUModel->saveData($addData);
                            }
                            //更新common导入库
                            if ($status == 2){                            
                                $skuConditions = "sku = '{$stockVal['sku']}' AND aliexpress_status = 0";
                                $SkuModel->updateDataByCondition($skuConditions, array('aliexpress_status' => 1));
                            }
                        }else{
                            //下架
                            $logMessage  = '';
                            $status      = 0;
                            $offlineDate = date('Y-m-d H:i:s');
                            //创建日志
                            //$logModel = new AliexpressLog();
                            // $logID = $logModel->prepareLog($stockVal['account_id'], AliexpressProduct::EVENT_UPDATE_NAME);
                            // if($logID){
                            //     $eventLog = $logModel->saveEventLog(AliexpressProduct::EVENT_UPDATE_NAME, array(
                            //             'log_id'        => $logID,
                            //             'account_id'    => $stockVal['account_id'],
                            //             'start_time'    => $offlineDate,
                            //             'end_time'      => $offlineDate,
                            //             'status'        => 0,
                            //     ));
                            //     //设置日志为正在运行
                            //     $logModel->setRunning($logID);
                                $offlineFlag = $this->_model->offlineAliexpressProduct($stockVal['account_id'], $stockVal['aliexpress_product_id']);
                                // $offlineFlag = true;    //测试
                                if($offlineFlag){
                                    //更新本地库
                                    $status  = 1;   //成功
                                    $logMessage = '下架成功';
                                    $this->_model->updateProductByPk($stockVal['product_id'], array('product_status_type'=>'offline'));
                                    // $logModel->setSuccess($logID);
                                    // $logModel->saveEventStatus(AliexpressProduct::EVENT_UPDATE_NAME, $eventLog, AliexpressLog::STATUS_SUCCESS);
                                }else{
                                    // $logModel->setFailure($logID, $this->_model->getErrorMessage());
                                    // $logModel->saveEventStatus(AliexpressProduct::EVENT_UPDATE_NAME, $eventLog, AliexpressLog::STATUS_FAILURE);
                                    $logMessage = '下架失败：'.$this->_model->getErrorMessage();
                                }
                            // }else{                                        
                            //     $logMessage = "日志生成错误!";
                            // }
                            //下架记录入库
                            $addData = array(
                                'product_id'        => $stockVal['aliexpress_product_id'],
                                'sku'               => $stockVal['sku'],
                                'account_id'        => $stockVal['account_id'],
                                'event'             => 'importskuoffline',
                                'status'            => $status,
                                'ipm_sku_stock'     => $stockVal['ipm_sku_stock'],                            
                                'message'           => $logMessage,
                                'start_time'        => $offlineDate,
                                'response_time'     => $offlineDate,
                                'operation_user_id' => 0
                            );
                            $aliLogOfflineModel->savePrepareLog($addData); 
                            //更新common导入库
                            // if ($status == 1){                            
                            //     $skuConditions = "sku = '{$stockVal['sku']}' AND aliexpress_status = 0";
                            //     $SkuModel->updateDataByCondition($skuConditions, array('aliexpress_status' => 1));
                            // }                            
                        }
                    }                      
                    unset($listing);
                }else{
                    echo("no match that sku lisitng");
                }
            }else{
                $flag = false;
            }

            //更新导入表
            $importIDs = MHelper::simplode($importIDsArr);
            $importConditions = "id IN ({$importIDs})";
            $SkuModel->updateDataByCondition($importConditions, array('aliexpress_status' => 1));  
                      
            unset($vaildSKUArr);
        }while($flag);     
        echo 'Finish!';
        Yii::app()->end();
    }    


    /**
     * 根据产品表aliexpress_product_id修改产品标题
     */
    public function actionRevisesubject(){
        $aliProductModel = new AliexpressProduct;
        $productId   = Yii::app()->request->getParam('productId');
        $prodcutInfo = $aliProductModel->getOneByCondition('subject,account_id,sku','aliexpress_product_id = '.$productId);
        if($_POST){
            set_time_limit(600);
            ini_set('display_errors', true);
            ini_set('memory_limit', '256M');
            $aliProductArr = Yii::app()->request->getParam('AliexpressProduct');
            $title         = isset($aliProductArr['subject'])?$aliProductArr['subject']:'';
            //判断标题是否超过了128个字符
            if(strlen($title) > 128){
                echo $this->failureJson(array('message'=>'标题不能超过128个字符'));
                exit;
            }

            $aliEditSimpleProductFileMessages = new AliexpressEditSimpleProductFiledMessages();

            $result = $aliProductModel->reviseSubjectByProductID($productId, $title, $prodcutInfo['account_id']);
            if($result){
                //插入修改记录
                $insertData = array(
                    'field_name'            => 'subject',
                    'account_id'            => $prodcutInfo['account_id'],
                    'aliexpress_product_id' => $productId,
                    'sku'                   => $prodcutInfo['sku'],
                    'send_msg'              => '修改成功',
                    'status'                => 1,
                    'create_user_id'        => isset(Yii::app()->user->id)?Yii::app()->user->id:0,
                    'create_time'           => date('Y-m-d H:i:s')
                );

                $jsonData = array(
                        'message' => '更改成功',
                        'forward' =>'/aliexpress/aliexpressproduct/list',
                        'navTabId'=> 'page' .AliexpressProduct::getIndexNavTabId(),
                        'callbackType'=>'closeCurrent'
                );
                echo $this->successJson($jsonData);
            }else{
                //插入修改记录
                $insertData = array(
                    'field_name'            => 'subject',
                    'account_id'            => $prodcutInfo['account_id'],
                    'aliexpress_product_id' => $productId,
                    'sku'                   => $prodcutInfo['sku'],
                    'send_msg'              => $aliProductModel->getErrorMessage(),
                    'status'                => 0,
                    'create_user_id'        => isset(Yii::app()->user->id)?Yii::app()->user->id:0,
                    'create_time'           => date('Y-m-d H:i:s')
                );
                
                echo $this->failureJson(array('message'=>$aliProductModel->getErrorMessage()));
            }

            $aliEditSimpleProductFileMessages->insertData($insertData);

            exit;
        }

        $this->render(
            "revisesubject", 
            array(
                'model'     => $aliProductModel, 
                'productId' => $productId,
                'subject'   => isset($prodcutInfo['subject'])?$prodcutInfo['subject']:''
            )
        );
    }


    /**
     * 根据产品表aliexpress_product_id修改产品描述
     * @link /aliexpress/aliexpressproduct/revisedetail
     */
    public function actionRevisedetail(){
        $aliProductModel = new AliexpressProduct;
        $id   = Yii::app()->request->getParam('id');
        $prodcutInfo = $aliProductModel->getOneByCondition('account_id,sku,aliexpress_product_id','id = '.$id);
        if(!$prodcutInfo){
            echo $this->failureJson(array('message'=>'信息错误'));
            exit;
        }

        $productId = $prodcutInfo['aliexpress_product_id'];
        $aliProductDownloadModel = new AliexpressProductDownload();
        $aliProductDownloadModel->setAccountID($prodcutInfo['account_id']);
        $response = $aliProductDownloadModel->findAeProductById($productId);
        if(!isset($response->detail)){
            echo $this->failureJson(array('message'=>$aliProductDownloadModel->getErrorMessage()));
            exit;
        }

        $getDetail = $response->detail;
        if($_POST){
            set_time_limit(600);
            ini_set('display_errors', true);
            ini_set('memory_limit', '256M');
            $aliProductArr = Yii::app()->request->getParam('AliexpressProduct');
            $detail        = isset($aliProductArr['detail'])?$aliProductArr['detail']:'';
            $accountID     = $prodcutInfo['account_id'];
            
            $aliEditSimpleProductFileMessages = new AliexpressEditSimpleProductFiledMessages();
            $request = new EditSimpleProductFiledRequest();
            $request->setProductID($productId);
            $request->setFiedName('detail');
            $request->setFiedValue($detail);
            $response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
            if($request->getIfSuccess()){
                //插入修改记录
                $insertData = array(
                    'field_name'            => 'detail',
                    'account_id'            => $accountID,
                    'aliexpress_product_id' => $productId,
                    'sku'                   => $prodcutInfo['sku'],
                    'send_msg'              => '修改成功',
                    'status'                => 1,
                    'create_user_id'        => isset(Yii::app()->user->id)?Yii::app()->user->id:0,
                    'create_time'           => date('Y-m-d H:i:s')
                );

                $jsonData = array(
                        'message' => '更改成功',
                        'forward' =>'/aliexpress/aliexpressproduct/list',
                        'navTabId'=> 'page' .AliexpressProduct::getIndexNavTabId(),
                        'callbackType'=>'closeCurrent'
                );
                echo $this->successJson($jsonData);
            }else{
                //插入修改记录
                $insertData = array(
                    'field_name'            => 'detail',
                    'account_id'            => $prodcutInfo['account_id'],
                    'aliexpress_product_id' => $productId,
                    'sku'                   => $prodcutInfo['sku'],
                    'send_msg'              => $request->getErrorMsg(),
                    'status'                => 0,
                    'create_user_id'        => isset(Yii::app()->user->id)?Yii::app()->user->id:0,
                    'create_time'           => date('Y-m-d H:i:s')
                );
                
                echo $this->failureJson(array('message'=>$request->getErrorMsg()));
            }

            $aliEditSimpleProductFileMessages->insertData($insertData);
            exit;
        }

        $this->render(
            "revisedetail", 
            array(
                'model'  => $aliProductModel, 
                'id'     => $id,
                'detail' => $getDetail
            )
        );
    }


    /**
     * 根据产品表aliexpress_product_id添加更新产品附图
     * @link /aliexpress/aliexpressproduct/addupdateftimages
     */
    public function actionAddupdateftimages(){
        $aliProductModel = new AliexpressProduct;
        $ids   = Yii::app()->request->getParam('ids');
        $prodcutInfo = $aliProductModel->getListByCondition('account_id,sku,aliexpress_product_id','id IN('.$ids.')');
        if(!$prodcutInfo){
            echo $this->failureJson(array('message'=>'信息错误'));
            exit;
        }

        foreach ($prodcutInfo as $proInfo) {
            $productId = $proInfo['aliexpress_product_id'];
            $accountID = $proInfo['account_id'];
            $sku       = $proInfo['sku'];

            $productImageAdd = new ProductImageAdd();
            $aliUpdateImgLog = new AliexpressUpdateFtImageLog();
            $platformCode    = Platform::CODE_ALIEXPRESS;
            $siteId          = 0;

                $insertData = array(
                    'product_id'     => $productId,
                    'upload_status'  => 0,
                    'upload_message' => '',
                    'upload_time'    => date('Y-m-d H:i:s'),
                    'update_status'  => 2,
                    'update_message' => '',
                    'update_nums'    => 0,
                    'operator'       => Yii::app()->user->id,
                    'operate_time'   => date('Y-m-d H:i:s'),
                    'operate_message'=> ''
                );

            //判断一天之内是否重复提交
            $logInfo = $aliUpdateImgLog->getOneByCondition('*', "product_id = '".$productId."'");
            if(!$logInfo || (time()-strtotime($logInfo['upload_time'])) > 86400){
                $response = $productImageAdd->addSkuImageUpload($accountID, $sku, 0, $platformCode, $siteId, null);
                if($response['status'] != 'succ'){
                    $insertData['upload_message'] = '上传图片失败';
                    $aliUpdateImgLog->insertData($insertData);
                    continue;
                }
            }

            if((time()-strtotime($logInfo['upload_time'])) < 86400){
                $insertData['operate_message'] = '一天之内只能更新一次';
                $aliUpdateImgLog->insertData($insertData);
                continue;
            }
            
            $insertData['upload_status']  = 1;
            $insertData['upload_message'] = '上传图片成功';
            $aliUpdateImgLog->insertData($insertData);

        }

        echo $this->successJson(array('message'=>'已提交，请到记录表查看'));
        exit;
    }


    /**
     * 根据产品表aliexpress_product_id更新产品附图
     * @link /aliexpress/aliexpressproduct/updateftimages
     */
    public function actionUpdateftimages(){
        set_time_limit(1600);
        $aliProductModel = new AliexpressProduct;
        $productImageAdd = new ProductImageAdd();
        $aliQueryImage   = new QueryPhotoBankImageByPathsRequest();
        $aliUpdateImgLog = new AliexpressUpdateFtImageLog();
        $platformCode    = Platform::CODE_ALIEXPRESS;
        $siteId          = 0;

        $nums = Yii::app()->request->getParam('nums');
        if(!$nums){
            $nums = 5;
        }

        //取出需要更新图片的数据
        $logInfo = $aliUpdateImgLog->getListByCondition('*', "update_status IN(0,2) AND upload_status = 1 AND update_nums < ".$nums);
        if(!$logInfo){
            exit;
        }

        foreach ($logInfo as $aliProInfo) {
            $prodcutInfo = $aliProductModel->getOneByCondition('account_id,sku','aliexpress_product_id = "'.$aliProInfo['product_id'].'"');
            if(!$prodcutInfo){
                continue;
            }

            $productId   = $aliProInfo['product_id'];
            $accountID   = $prodcutInfo['account_id'];
            $sku         = $prodcutInfo['sku'];

            $insertData = array(
                'product_id'     => $productId,
                'upload_status'  => $aliProInfo['upload_status'],
                'upload_message' => $aliProInfo['upload_message'],
                'upload_time'    => $aliProInfo['upload_time'],
                'update_status'  => $aliProInfo['update_status'],
                'update_message' => $aliProInfo['update_message'],
                'update_nums'    => $aliProInfo['update_nums'] + 1,
                'operator'       => $aliProInfo['operator'],
                'operate_time'   => $aliProInfo['operate_time'],
                'operate_message'=> $aliProInfo['operator'].'提交了更新图片操作'
            );

            $response = $productImageAdd->getSkuImageUpload($accountID, $sku, array(), $platformCode, $siteId);
            if(!isset($response['result']['imageInfoVOs'])){
                $insertData['update_message'] = '获取图片失败';
                $aliUpdateImgLog->insertData($insertData);
                continue;
            }

            $newImageArr = array();
            foreach ($response['result']['imageInfoVOs'] as $imgInfo) {
                if($imgInfo['watermark']){
                    continue;
                }
                $newName = str_replace('.jpg', '', $imgInfo['imageName']);
                $newImageArr[$newName] = $imgInfo['remotePath'];
            }

            $aliProductDownloadModel = new AliexpressProductDownload();
            $aliProductDownloadModel->setAccountID($accountID);
            $response = $aliProductDownloadModel->findAeProductById($productId);
            if(!isset($response->detail)){
                $insertData['update_message'] = $aliProductDownloadModel->getErrorMessage();
                $aliUpdateImgLog->insertData($insertData);
                continue;
            }
            
            $detail = $response->detail;
            $imgNameArr = array();
            preg_match_all("<img.*?src=\"(.*?.*?)\".*?>", $detail, $matches);
            if(isset($matches[1])){
                foreach ($matches[1] as $value) {
                    $names = basename($value);
                    $namesLen = strlen($names);
                    //查询.jpg出现的位置
                    $tmp = stripos($names, '.jpg');
                    $tmp += 4;
                    $names = substr($names, 0, $tmp);
                    $imgNameArr[$names] = $value;
                }
            }else{
                $insertData['update_message'] = '没有获取到图片地址';
                $aliUpdateImgLog->insertData($insertData);
                continue;
            }

            //获取图片地址
            $queryImageArr = array();
            $aliQueryImage->setPaths(array_keys($imgNameArr));
            $response = $aliQueryImage->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
            if(isset($response->images)){
                $infos = (array)$response->images;
                foreach ($infos as $imgKey => $queryInfo) {
                    $displayName = '';
                    $displayName = str_replace(array('normal'), array(''), $queryInfo->displayName);
                    if(!isset($newImageArr[$displayName])) continue;
                    $queryImageArr[$imgKey] = $newImageArr[$displayName];
                }
            }

            if(empty($queryImageArr)){
                $insertData['update_status']  = 0;
                $insertData['update_message'] = '根据图片名称获取sku失败';
                $aliUpdateImgLog->insertData($insertData);
                continue;
            }

            foreach ($imgNameArr as $iKey => $iValue) {
                if(!isset($queryImageArr[$iKey])) continue;
                $detail = str_replace($iValue, $queryImageArr[$iKey], $detail);
            }
            
            $request = new EditSimpleProductFiledRequest();
            $request->setProductID($productId);
            $request->setFiedName('detail');
            $request->setFiedValue($detail);
            $response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
            if($request->getIfSuccess()){
                $insertData['update_status']  = 1;
                $insertData['update_message'] = '更新图片成功';
                $aliUpdateImgLog->insertData($insertData);
            }else{
                $insertData['update_status']  = 0;
                $insertData['update_message'] = '更新图片失败-'.str_replace("'", "\"", $request->getErrorMsg());
                $aliUpdateImgLog->insertData($insertData);
            }

        }
    }


    /**
     * 针对速卖通所有账号价格调整
     * @link /aliexpress/aliexpressproduct/allupdateprice/account_id/206/sku/117736
     */
    public function actionAllupdateprice(){
        set_time_limit(5*3600);
        $account_id = Yii::app()->request->getParam('account_id');
        $setSku     = Yii::app()->request->getParam('sku');
        $logModel   = new AliexpressLog();
        $standardProfitRate = 0.15;  //标准利润率
        $eventName = 'allupdateprice';
        if($account_id){
            //写log
            $logID = $logModel->prepareLog($account_id, $eventName);
            if(!$logID){
                exit('日志写入错误');
            }
            //检测是否可以允许
            if(!$logModel->checkRunning($account_id, $eventName)){
                $logModel->setFailure($logID, Yii::t('system', 'There Exists An Active Event'));
                exit('There Exists An Active Event');
            }

            //设置运行
            $logModel->setRunning($logID);

            $aliProductModel          = new AliexpressProduct();
            $aliProductVariationModel = new AliexpressProductVariation();
            $aliUpdatePriceAllLog     = new AliexpressUpdatePriceAll();
            $aliEditePriceModel       = new AliexpressEditPrice();
            $command = $aliProductVariationModel->getDbConnection()->createCommand()
                ->from($aliProductVariationModel->tableName() . " as t")
                ->leftJoin($aliProductModel->tableName()." as p", "p.id=t.product_id")
                ->select("t.sku,p.account_id,t.aliexpress_product_id,t.sku_id,t.sku_code as seller_sku,p.is_variation,p.category_id,t.sku_price,t.id,p.is_variation,t.product_id as p_id")
                ->where('p.account_id = '.$account_id)
                ->andWhere("p.product_status_type='onSelling'");
                if($setSku){
                    $command->andWhere("t.sku = '".$setSku."'");
                }
            $variantListing = $command->queryAll();
            if(!$variantListing){
                $logModel->setFailure($logID, Yii::t('system', 'There Exists An Active Event'));
                exit('无数据');
            }

            foreach ($variantListing as $infos) {
                //查询是否存在已经更改成功的数据
                $exitsArr = array(':seller_sku'=>$infos['seller_sku'], ':product_id'=>$infos['aliexpress_product_id']);
                $exits = $aliUpdatePriceAllLog->getOneByCondition('seller_sku = :seller_sku AND product_id = :product_id',$exitsArr);
                if($exits && $exits['status'] == 1){
                    continue;
                }

                $productCost = 0;
                $salePrice = 0;
                $logData = array();
                
                $sku = $infos['sku'];
                $categoryID = $infos['category_id'];        
                //获取产品信息
                $skuInfo = Product::model()->getProductInfoBySku($sku);
                if(!$skuInfo){
                    continue;
                }

                if($skuInfo['avg_price'] <= 0){
                    $productCost = $skuInfo['product_cost'];   //加权成本
                }else{ 
                    $productCost = $skuInfo['avg_price'];      //产品成本
                }

                //产品成本转换成美金
                $productCost = $productCost / CurrencyRate::model()->getRateToCny(AliexpressProductAdd::PRODUCT_PUBLISH_CURRENCY);
                $shipCode = AliexpressProductAdd::model()->returnShipCode($productCost,$sku);    

                //取出佣金
                $commissionRate = AliexpressCategoryCommissionRate::getCommissionRate($categoryID);

                //计算卖价，获取描述
                $priceCal = new CurrencyCalculate();
                $priceCal->setProfitRate($standardProfitRate);//设置利润率
                $priceCal->setCurrency(AliexpressProductAdd::PRODUCT_PUBLISH_CURRENCY);//币种
                $priceCal->setPlatform(Platform::CODE_ALIEXPRESS);//设置销售平台
                $priceCal->setSku($sku);//设置sku
                $priceCal->setCommissionRate($commissionRate);//设置佣金比例
                $priceCal->setShipCode($shipCode);//设置运费code
                $salePrice = $priceCal->getSalePrice();//获取卖价
                if($salePrice > 5){
                    //取出产品属性
                    $shipCode = Logistics::CODE_GHXB_DGYZ;
                    $wheres2 = 'attribute_id = :attribute_id';
                    $params2 = array(':attribute_id'=>3);
                    $attributeIdsInfo = ProductSelectAttribute::model()->getSelectedAttributeSKUListByProductId($skuInfo['id'],$wheres2,$params2);
                    if($attributeIdsInfo){
                        $shipCode = Logistics::CODE_GHXB;
                    }

                    $priceCal2 = new CurrencyCalculate();
                    $priceCal2->setProfitRate($standardProfitRate);//设置利润率
                    $priceCal2->setCurrency(AliexpressProductAdd::PRODUCT_PUBLISH_CURRENCY);//币种
                    $priceCal2->setPlatform(Platform::CODE_ALIEXPRESS);//设置销售平台
                    $priceCal2->setSku($sku);//设置sku
                    $priceCal2->setCommissionRate($commissionRate);//设置佣金比例
                    $priceCal2->setShipCode($shipCode);//设置运费code
                    $salePrice = $priceCal2->getSalePrice();//获取卖价
                }

                $logData = array(
                    'product_id' => $infos['aliexpress_product_id'],
                    'seller_sku' => $infos['seller_sku'],
                    'sku' => $infos['sku'],
                    'account_id' => $infos['account_id'],
                    'old_price' => $infos['sku_price'],
                    'create_time' => date('Y-m-d H:i:s'),
                );
                $salePrice = round($salePrice,2);
                if(is_numeric($salePrice) && $salePrice > 0 && $salePrice != $infos['sku_price']){
                    $logData['price'] = $salePrice;
                    $result = $aliEditePriceModel->updateProductsPrice($account_id,$infos['aliexpress_product_id'],$salePrice,$infos['sku_id']);
                    if($result){
                        $logData['status'] = 1;
                        $logData['msg'] = '成功';
                        $aliProductVariationModel->updatePrice($infos['id'],$salePrice);
                        if($infos['is_variation'] == 0){
                            $updata = array('product_price'=>$salePrice);
                            $aliProductModel->updateProductByPk($infos['p_id'],$updata);
                        }
                        $fTemplateId = 0;
                        $aliFreightTemplate = new AliexpressFreightTemplate();
                        $templateInfo = $aliFreightTemplate->getTemplateIdInfoByAccountId($account_id);
                        $templateArr = array();
                        if($templateInfo){
                            foreach ($templateInfo as $template) {
                                $tempName = str_replace(' ', '', $template['template_name']);
                                $tempName = strtolower($tempName);
                                $templateArr[$tempName] = $template['template_id'];
                            }
                        }

                        $wheres3 = 'attribute_id = :attribute_id';
                        $params3 = array(':attribute_id'=>3);
                        $attributeIdsInfo = ProductSelectAttribute::model()->getSelectedAttributeSKUListByProductId($skuInfo['id'],$wheres3,$params3);
                        if($salePrice<=5 && !$attributeIdsInfo){
                            $fTemplateId = isset($templateArr['templatefornospecialpropertywithamountbelow5usd'])?$templateArr['templatefornospecialpropertywithamountbelow5usd']:0;
                        }elseif ($salePrice<=5 && $attributeIdsInfo) {
                            $fTemplateId = isset($templateArr['templateforspecialpropertywithamountbelow5usd'])?$templateArr['templateforspecialpropertywithamountbelow5usd']:0;
                        }elseif($salePrice > 5){
                            $fTemplateId = isset($templateArr['chinapostairmailfreeshippingabove20usd'])?$templateArr['chinapostairmailfreeshippingabove20usd']:0;
                        }

                        if($fTemplateId > 0){
                            $request2 = new EditSimpleProductFiledRequest();
                            $request2->setProductID($infos['aliexpress_product_id']);
                            $request2->setFiedName('freightTemplateId');
                            $request2->setFiedValue($fTemplateId);
                            $response = $request2->setAccount($account_id)->setRequest()->sendRequest()->getResponse();
                            if(!$request2->getIfSuccess()){
                                $logData['msg'] = '成功--但更新运费模板失败--'.$request2->getErrorMsg();
                            }
                        }    
                    }else{
                        $logData['status'] = 2;
                        $logData['msg'] = $aliEditePriceModel->getExceptionMessage();
                    }
                }else{
                    $logData['price'] = $salePrice;
                    $logData['status'] = 0;
                    $logData['msg'] = '计算的价格小于等于0或者价格相等';
                }

                $aliUpdatePriceAllLog->saveData($logData);
            }

            $logModel->setSuccess($logID, "success");

        }else{
            $accountList = AliexpressAccount::model()->getIdNamePairs();
            foreach($accountList as $key => $value){
                MHelper::runThreadSOCKET('/'.$this->route.'/account_id/' . $key);
                sleep(1);
            }
        }
    }


    /**
     * 根据价格更新运费模板
     * @link /aliexpress/aliexpressproduct/updatefreighttemplateid/account_id/206/product_id/117736
     */
    public function actionUpdatefreighttemplateid(){
        set_time_limit(5*3600);
        $account_id = Yii::app()->request->getParam('account_id');
        $product_id = Yii::app()->request->getParam('product_id');
        $aliUpdatePriceAllLog = new AliexpressUpdatePriceAll();
        $command = $aliUpdatePriceAllLog->getDbConnection()->createCommand()
            ->from($aliUpdatePriceAllLog->tableName())
            ->select("product_id,sku,MAX(price) as price,seller_sku")
            ->where("`status` = 1 and msg <> '成功--更新运费模板成功--2' and account_id = ".$account_id);
        if($product_id){
            $command->andWhere("product_id = '".$product_id."'");
        }
        $command->group("product_id");
        $variantListing = $command->queryAll();
        if(!$variantListing){
            exit('无数据');
        }

        foreach ($variantListing as $value) {
            // if($value['price'] > 5 ){
            //     continue;
            // }
            $salePrice = $value['price'];
            $msgs = '';
            //获取产品信息
            $skuInfo = Product::model()->getProductInfoBySku($value['sku']);
            if(!$skuInfo){
                continue;
            }

            $aliFreightTemplate = new AliexpressFreightTemplate();
            $templateInfo = $aliFreightTemplate->getTemplateIdInfoByAccountId($account_id);
            $templateArr = array();
            if($templateInfo){
                foreach ($templateInfo as $template) {
                    $tempName = str_replace(' ', '', $template['template_name']);
                    $tempName = strtolower($tempName);
                    $templateArr[$tempName] = $template['template_id'];
                }
            }

            $fTemplateId = 0;
            $wheres3 = 'attribute_id = :attribute_id';
            $params3 = array(':attribute_id'=>3);
            $attributeIdsInfo = ProductSelectAttribute::model()->getSelectedAttributeSKUListByProductId($skuInfo['id'],$wheres3,$params3);
            if($salePrice<=5 && !$attributeIdsInfo){
                $fTemplateId = isset($templateArr['templatefornospecialpropertywithamountbelow5usd'])?$templateArr['templatefornospecialpropertywithamountbelow5usd']:0;
            }elseif ($salePrice<=5 && $attributeIdsInfo) {
                $fTemplateId = isset($templateArr['templateforspecialpropertywithamountbelow5usd'])?$templateArr['templateforspecialpropertywithamountbelow5usd']:0;
            }elseif($salePrice > 5){
                $fTemplateId = isset($templateArr['chinapostairmailfreeshippingabove20usd'])?$templateArr['chinapostairmailfreeshippingabove20usd']:0;
            }else{
                $fTemplateId = 0;
            }

            if($fTemplateId && $fTemplateId > 0){
                $request2 = new EditSimpleProductFiledRequest();
                $request2->setProductID($value['product_id']);
                $request2->setFiedName('freightTemplateId');
                $request2->setFiedValue($fTemplateId);
                $response = $request2->setAccount($account_id)->setRequest()->sendRequest()->getResponse();
                if(!$request2->getIfSuccess()){
                    $msgs = '更新运费模板失败--'.$request2->getErrorMsg();
                }else{
                    $msgs = '成功--更新运费模板成功--2';
                    $aliUpdatePriceAllLog->getDbConnection()
                        ->createCommand()
                        ->update($aliUpdatePriceAllLog->tableName(), 
                            array('msg' => $msgs), "product_id = '{$value['product_id']}'");
                }

                echo $value['product_id'].$msgs.'<br>';
            }
        }
    }


    /**
     * 根据账号自动更新库存为0或为1的数据  禅道编号：2874的需求
     * /aliexpress/aliexpressproduct/updateipmskustockallone/accountID/150
     */
    public function actionUpdateipmskustockallone(){
        ini_set('memory_limit','2048M');
        set_time_limit(4*3600);
        ini_set("display_errors", true);
        error_reporting(E_ALL);

        $aliexpressOutofstockModel          = new AliexpressOutofstock();
        $aliexpressProductVariationModel    = new AliexpressProductVariation();
        $aliexpressProductModel             = new AliexpressProduct();
        $aliZeroStockSKUModel               = new AliexpressZeroStockSku();
        $logModel                           = new AliexpressLog();
        $productModel                       = new Product();

        //账号
        $accountID = Yii::app()->request->getParam('accountID');

        //指定某个特定sku----用于测试
        $setSku = Yii::app()->request->getParam('sku');

        $select = 'sku';
        $type = 10;
        $eventName = 'updateipmskustockallone';
        $limit = 200;
        $offset = 0;
        $stocks = 900;

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
                    $command = $aliexpressProductVariationModel->getDbConnection()->createCommand()
                        ->from($aliexpressProductVariationModel->tableName() . " as t")
                        ->leftJoin($aliexpressProductModel->tableName()." as p", "p.id=t.product_id")
                        ->select("t.id, t.sku, t.sku_id, t.sku_code as online_sku, p.account_id, p.aliexpress_product_id, t.ipm_sku_stock as product_stock, t.product_id")
                        ->where('p.account_id = '.$accountID)
                        ->andWhere("p.product_status_type = 'onSelling'")
                        ->andWhere("t.ipm_sku_stock < 2");
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

                    $conditions = 'sku IN('.MHelper::simplode($skuArr).') AND product_status = :product_status';
                    $param = array(':product_status'=>4);
                    $skuList = $productModel->getProductListByCondition($conditions, $param, '', $select);

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

                        //判断是否在断货设置里有sku
                        $outWhere = "sku = '".$variant['sku']."' AND ack = 1 AND message LIKE '断货设置成功%'";
                        $isOut = $aliexpressOutofstockModel->getOneByCondition('sku', $outWhere, 'id desc');
                        if($isOut){
                            continue;
                        }

                        $time = date("Y-m-d H:i:s");
                        $message = "";

                        $addData = array(
                            'product_id'   => $variant['aliexpress_product_id'],
                            'seller_sku'   => $variant['online_sku'],
                            'sku'          => $variant['sku'],
                            'account_id'   => $variant['account_id'],
                            'site_id'      => 0,
                            'old_quantity' => $variant['product_stock'],
                            'create_time'  => $time,
                            'type'         => $type
                        );

                        $editSingleSkuStockRequest = new EditSingleSkuStockRequest;//使用单个修改接口
                        $editSingleSkuStockRequest->setAccount($variant['account_id']);
                        $editSingleSkuStockRequest->setProductID($variant['aliexpress_product_id']);
                        $editSingleSkuStockRequest->setSkuID($variant['sku_id']);
                        $editSingleSkuStockRequest->setIpmSkuStock($stocks);
                        $response = $editSingleSkuStockRequest->setRequest()->sendRequest()->getResponse();
                        if($editSingleSkuStockRequest->getIfSuccess()){
                            $aliexpressProductVariationModel->updateVariationById($variant['id'],array('ipm_sku_stock'=>$stocks));
                            $status = 2;//成功
                            $message = '修改库存为'.$stocks.'成功';
                            $addData['is_restore'] = 1;
                            $addData['restore_time'] = $time;
                            $addData['restore_num'] = 1;
                            $addData['restore_quantity'] = $stocks;
                        }else{
                            $status = 3;//失败
                            $message = $editSingleSkuStockRequest->getErrorMsg();
                        }

                        $addData['status'] = $status;
                        $addData['msg'] = $message;

                        $zeroStockCondition = 'seller_sku = :seller_sku AND product_id = :product_id AND status = 2 AND type = '.$type.' AND is_restore = 0';
                        $zeroStockParam = array(':seller_sku'=>$variant['online_sku'], ':product_id'=>$variant['aliexpress_product_id']);
                        $existsInfo = $aliZeroStockSKUModel->getZeroSkuOneByCondition($zeroStockCondition,$zeroStockParam);
                        if($existsInfo){
                            continue;
                        }else{
                            $aliZeroStockSKUModel->saveData($addData);
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
            $accountList = AliexpressAccount::model()->getIdNamePairs();
            foreach($accountList as $key => $value){
                MHelper::runThreadSOCKET('/'.$this->route.'/accountID/' . $key);
                sleep(1);
            }
        } 
    }
}