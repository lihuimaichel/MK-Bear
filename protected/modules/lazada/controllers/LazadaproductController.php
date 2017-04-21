<?php
/**
 * @desc lazada listing控制器类
 * @author zhangF
 *
 */
class LazadaproductController extends UebController {
	
	/** @var object  模型对象 **/
	protected $_model = null;
	
	/**
	 * (non-PHPdoc)
	 * @see CController::init()
	 */
	public function init() {
		$this->_model = new LazadaProduct();
		parent::init();
	}
	
	/**
	 * @desc 设置访问规则
	 * @see CController::accessRules()
	 */
	public function accessRules() {
		return array(
			array(
				'allow',
				'users' => ('*'),
				'actions' => array('getproducts', 'deletelazadaproduct'),
			),
		);
	}
	
	/**
	 * @desc 列表页
	 */
	public function actionList() {
		$this->render('list', array(
			'model' => $this->_model,
		));
	}

	/**
	 * @desc 拉取lazada listing
	 * @author yangsh
	 * @since  2016-10-07
	 * @link  /lazada/lazadaproduct/getproducts
	 *        /lazada/lazadaproduct/getproducts/account_id/1/debug/1
	 */
	public function actionGetproducts() {
        set_time_limit(3600);
        //ini_set('memory_limit','2048M');
        error_reporting(E_ALL & ~E_STRICT);
        ini_set('display_errors', TRUE);

		$accountID 	= trim(Yii::app()->request->getParam('account_id',''));
		$state 		= trim(Yii::app()->request->getParam('state',''));//产品状态
		$day 		= trim(Yii::app()->request->getParam('day',2));//默认2天内创建的listing	

        //参数验证
        $validateMsg = '';
        if ( !empty($accountID) && !preg_match('/^\d+$/',$accountID)) {
            $validateMsg .= 'account_id is invalid;';
        }
        //只取all 和 deleted, all 包括了live inactive
        $stateArr = array(
        	GetProductsRequest::PRODUCT_STATUS_ALL,//'all',  包括了live inactive, 除deleted
        	GetProductsRequest::PRODUCT_STATUS_DELETED,//'deleted',
        );        
        if ($state !='' && !in_array($state, $stateArr) ) {
        	$validateMsg .= 'state is invalid;';
        }
        if ($validateMsg != '') {
            Yii::app ()->end($validateMsg);
        }

        //状态查询
        $statuslist = $state == '' ? $stateArr : array($state);        

        // 默认按天数拉取
        $timeArr = array();
        if ($day>0) {
			$timeArr[] = date(DateTime::ISO8601, strtotime("-{$day} days"));
			$timeArr[] = date(DateTime::ISO8601);
        }

        //指定账号执行
		if ($accountID) {
			foreach ($statuslist as $productState) {
				$lazadaLogModel = new LazadaLog();
				//拉指定账号的listing
				$logID = $lazadaLogModel->prepareLog($accountID,LazadaProductDownload::EVENT_NAME);
				if (!$logID) {
	                echo 'Insert prepareLog failure';
	                Yii::app()->end();		
				}
				//检查当前账号是否可以拉取Listing
				$checkRunning = $lazadaLogModel->checkRunning($accountID, LazadaProductDownload::EVENT_NAME);//账号、站点
				if( !$checkRunning ){
					$lazadaLogModel->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
					echo 'There Exists An Active Event';
					Yii::app()->end();
				}
				//插入本次log参数日志(用来记录请求的参数)
				$time = date('Y-m-d H:i:s');
				$eventLog = $lazadaLogModel->saveEventLog(LazadaProductDownload::EVENT_NAME, array(
					'log_id'        => $logID,
					'account_id'    => $accountID,
					'start_time'    => $time,
					'end_time'      => $time,
				));
				//设置日志为正在运行
				$lazadaLogModel->setRunning($logID);
				//拉取listing
				$model = new LazadaProductDownload();
				!empty($timeArr) && $model->setCreated($timeArr);
				$isOk = $model->setAccountID($accountID)
								 ->setProductState($productState)
								 ->startDownloadProducts();
				//更新日志信息
				$flag = $isOk ? 'Success' : 'Failure';
				if( $isOk ){
					$lazadaLogModel->setSuccess($logID);
					$lazadaLogModel->saveEventStatus(LazadaProductDownload::EVENT_NAME, $eventLog, LazadaLog::STATUS_SUCCESS);
				}else{
                    $errMessage = $model->getExceptionMessage();
                    if (mb_strlen($errMessage)>200) {
                        $errMessage = mb_substr($errMessage,0,200);
                    }
					$lazadaLogModel->setFailure($logID, $errMessage );
					$lazadaLogModel->saveEventStatus(LazadaProductDownload::EVENT_NAME, $eventLog, LazadaLog::STATUS_FAILURE);
				}
				//记录日志 
	            $result = json_encode($_REQUEST).'========'.$flag.'========'.$model->getExceptionMessage();
	            echo $result."\r\n<br>";            
			}
		} else {
			$accountInfos = LazadaAccount::getAbleAccountList();
			foreach ($accountInfos as $info) {
                $url = Yii::app()->request->hostInfo.'/' . $this->route 
		                . '/account_id/' . $info['id']
		                . '/type/' . $type. '/day/' . $day.'/state/'.$state;
                echo $url." <br>\r\n";
                MHelper::runThreadBySocket ( $url );
                sleep(180);
            }
		}
		Yii::app()->end('finish');
	}

	/**
	 * @desc 补拉取lazada listing
	 * @author yangsh
	 * @since  2016-10-07
	 * @link  /lazada/lazadaproduct/checkgetproducts
	 *        /lazada/lazadaproduct/checkgetproducts/account_id/1
	 */
	public function actionCheckgetproducts() {
		set_time_limit(3600);
        //ini_set('memory_limit','2048M');
        error_reporting(E_ALL & ~E_STRICT);
        ini_set('display_errors', TRUE);

		$accountID 		= trim(Yii::app()->request->getParam('account_id',''));
		$state 			= trim(Yii::app()->request->getParam('state',''));//产品状态
		$type 			= trim(Yii::app()->request->getParam('type','2'));//默认1:更新时间,2:创建时间
		$day 			= trim(Yii::app()->request->getParam('day',7));//7天
		$skuList 		= trim(Yii::app()->request->getParam('sku_list',''));
		$search 		= trim(Yii::app()->request->getParam('search',''));			

        //参数验证
        $validateMsg = '';
        if ( !empty($accountID) && !preg_match('/^\d+$/',$accountID)) {
            $validateMsg .= 'account_id is invalid;';
        }
        //只取all 和 deleted, all 包括了live inactive
        $stateArr = array(
        	GetProductsRequest::PRODUCT_STATUS_ALL,//'all',  包括了live inactive, 除deleted
        	GetProductsRequest::PRODUCT_STATUS_DELETED,//'deleted',
        );
        if ($state !='' && !in_array($state, $stateArr) ) {
        	$validateMsg .= 'state is invalid;';
        }
        if ($validateMsg != '') {
            Yii::app ()->end($validateMsg);
        }
        //状态查询
        $statuslist = $state == '' ? $stateArr : array($state);  

        //默认按天数拉取
        $timeArr = array();
        if ($skuList =='' && $search == '' ) {
			$timeArr[] = date(DateTime::ISO8601, strtotime("-{$day} days"));
			$timeArr[] = date(DateTime::ISO8601);
        }

        //指定账号执行
		if ($accountID) {
			$skuList = $skuList == '' ? array() : explode(',',$skuList);
			foreach ($statuslist as $productState) {
				$lazadaLogModel = new LazadaLog();
				//拉指定账号的listing
				$logID = $lazadaLogModel->prepareLog($accountID,LazadaProductDownload::EVENT_NAME_CHECK);
				if (!$logID) {
	                echo 'Insert prepareLog failure';
	                Yii::app()->end();		
				}
				//检查当前账号是否可以拉取Listing
				$checkRunning = $lazadaLogModel->checkRunning($accountID, LazadaProductDownload::EVENT_NAME_CHECK);//账号、站点
				if( !$checkRunning ){
					$lazadaLogModel->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
					echo 'There Exists An Active Event';
					Yii::app()->end();
				}
				//设置日志为正在运行
				$lazadaLogModel->setRunning($logID);
				//拉取listing
				$model = new LazadaProductDownload();
				!empty($skuList) && $model->setSellerSkuList( $skuList );
				!empty($search) && $model->setSearch($search);
				if (!empty($timeArr) && $type == 1 ) {
					$model->setUpdated($timeArr);
				}				
				if (!empty($timeArr) && $type == 2 ) {
					$model->setCreated($timeArr);
				}
				$isOk = $model->setAccountID($accountID)
							  ->setProductState($productState)
							  ->startDownloadProducts();
				//更新日志信息
				$flag = $isOk ? 'Success' : 'Failure';
				if( $isOk ){
					$lazadaLogModel->setSuccess($logID);
				}else{
					$errMessage = $model->getExceptionMessage();
                    if (mb_strlen($errMessage)>200) {
                        $errMessage = mb_substr($errMessage,0,200);
                    }
					$lazadaLogModel->setFailure($logID, $errMessage );
				}
				//记录日志 
	            $result = json_encode($_REQUEST).'========'.$flag.'========'.$model->getExceptionMessage();
	            echo $result."\r\n<br>";
			}
		} else {
			$accountInfos = LazadaAccount::getAbleAccountList();
			foreach ($accountInfos as $info) {
                $url = Yii::app()->request->hostInfo.'/' . $this->route 
		                . '/account_id/' . $info['id']
		                . '/type/' . $type. '/day/'. $day.'/state/'.$state
		                . '/sku_list/'.$skuList. '/search/'.$search;
                echo $url." <br>\r\n";
                MHelper::runThreadBySocket ( $url );
                sleep(300);
            }
		}
		Yii::app()->end('finish');
	}
	
	/**
	 * @desc 批量删除在线的产品
	 */
	public function actionBatchdelete() {
		$listingIds = Yii::app()->request->getParam('listing_id');
		if (empty($listingIds)) {
			echo $this->failureJson(array(
				'message' => Yii::t('system', 'Please select a record'),
			));
			Yii::app()->end();
		}
		//获取listing信息
		$deleteDatas = array();
		$listingModels = $this->_model->findAll("id in (" . implode(',', $listingIds) . ")");
		foreach ($listingModels as $listingModel) {
			$deleteDatas[$listingModel['site_id']][$listingModel['account_id']][] = $listingModel['seller_sku'];
		}
		//分账户删除对应lazada账号的产品
		foreach ($deleteDatas as $siteID => $deleteData) {
			foreach ($deleteData as $accountID => $data) {
				MHelper::runThreadSOCKET('/lazada/lazadaproduct/deletelazadaproduct/site_id/' . $siteID . '/account_id/' . $accountID . '/skus/' . implode(',', $data));
			}
		}
		echo $this->successJson(array(
			'message' => Yii::t('lazada_product', 'Delete Request Has Been Posted'),
		));
		Yii::app()->end();
	}
	
	/**
	 * @desc 批量下线在线的产品
	 */
	public function  actionBatchoffline(){
		$listIds = Yii::app()->request->getParam('listing_id');
		if($listIds){
			$listIds = implode(",", $listIds);
			//echo '/lazada/lazadaproduct/offlinetask/listing_id/' . $listIds;
			MHelper::runThreadSOCKET('/lazada/lazadaproduct/offlinetask/listing_id/' . $listIds);
			$successNum = 1;
			if($successNum>0){
				echo $this->successJson(array(
						'message' => Yii::t('system', 'Update successful'),
				));
			}
		}		
		else{
			echo $this->failureJson(array(
				'message' => Yii::t('system', 'Update failure'),
			));
		}
	}
	
	/**
	 * @desc 运行下线任务
	 */
	public function actionOfflinetask(){
		$listIds = Yii::app()->request->getParam('listing_id');
		//1根据获取到的id，找出对应的sku,siteId,accountId
		//2循环调用对应的接口，更改状态
		//3同时更改本地数据中的状态
		//4统一做日志记录
		$updateDatas = array();
		$allProductRows = $this->_model->findAll("id in (" . $listIds . ")");
		foreach ($allProductRows as $row){
			$updateDatas[$row['site_id']][$row['account_id']][$row['id']] = $row['seller_sku'];
		}
		
		$failIds = array();
		$successNum = 0;
		$lazadaProductUpdateModel = new LazadaProductUpdate();
		foreach ($updateDatas as $siteId=>$accoutData) {
			foreach ($accoutData as $accountId=>$skudata){
				$lazadaLog = new LazadaLog;
				$logID = $lazadaLog->prepareLog($accountId,LazadaProductUpdate::EVENT_NAME);
				if ($logID) {
					//插入本次log参数日志(用来记录请求的参数)
					$time = date('Y-m-d H:i:s');
					$eventLog = $lazadaLog->saveEventLog(LazadaProductUpdate::EVENT_NAME, array(
							'log_id'        => $logID,
							'account_id'    => $accountId,
							'start_time'    => $time,
							'end_time'      => $time,
							'status'		=> LazadaProduct::PRODUCT_STATUS_INACTIVE,
					));
						
					//设置日志为正在运行
					$lazadaLog->setRunning($logID);
					//更改lazada账号产品状态
					$flag = $lazadaProductUpdateModel->updateAccountProducts($siteId, $accountId,
							$skudata, LazadaProduct::PRODUCT_STATUS_TEXT_INACTIVE);
					if( $flag ){
						$successNum++;
						//根据产品信息更新库中产品状态
						$ids = array_keys($skudata);
						LazadaProduct::model()->batchUpdateStatus($ids, LazadaProduct::PRODUCT_STATUS_INACTIVE);
						$lazadaLog->setSuccess($logID);
						$lazadaLog->saveEventStatus(LazadaProductUpdate::EVENT_NAME, $eventLog, LazadaLog::STATUS_SUCCESS);
					}else{
						$failIds[$siteId][$accountId] = $skudata;
						$lazadaLog->setFailure($logID, $lazadaProductUpdateModel->getExceptionMessage());
						$lazadaLog->saveEventStatus(LazadaProductUpdate::EVENT_NAME, $eventLog, LazadaLog::STATUS_FAILURE);
					}
				}
				sleep(5);
			}
		}
		unset($allProductRows);
	}

	/**
	 * @desc 删除lazada账号指定SKU
	 */
	public function actionDeletelazadaproduct() {
		$siteID = Yii::app()->request->getParam('site_id');
		$accountID = Yii::app()->request->getParam('account_id');
		$skus = Yii::app()->request->getParam('skus');
		$skuList = explode(',', $skus);
		$skuList = array_filter($skuList);
		$lazadaAccountInfo = LazadaAccount::model()->getOneByCondition('id', "account_id='{$accountID}' and site_id='{$siteID}'");
		if (empty($lazadaAccountInfo)) {
			echo $this->failureJson(array(
					'message' => '账号信息不存在,非法操作!',
			));
			Yii::app()->end();
		}

		if (empty($skuList)) {
			echo $this->failureJson(array(
					'message' => Yii::t('lazada_product', 'Not Specify Sku Which Need To Delete'),
			));
			Yii::app()->end();
		}
		$accountAutoID = $lazadaAccountInfo['id'];
		$logID = LazadaLog::model()->prepareLog($accountID,LazadaProductRemove::EVENT_NAME);
		if ($logID) {
			//插入本次log参数日志(用来记录请求的参数)
			$time = date('Y-m-d H:i:s');
			$eventLog = LazadaLog::model()->saveEventLog(LazadaProductRemove::EVENT_NAME, array(
					'log_id'        => $logID,
					'account_id'    => $accountID,
					'start_time'    => $time,
					'end_time'      => $time,
			));
			//设置日志为正在运行
			LazadaLog::model()->setRunning($logID);
			$lazadaProductRemoveModel = new LazadaProductRemove();
			//删除lazada账号产品
			$flag = $lazadaProductRemoveModel->removeAccountProducts($siteID, $accountID, $skuList);
			//更新日志信息
			if( $flag ){
				LazadaLog::model()->setSuccess($logID);
				LazadaLog::model()->saveEventStatus(LazadaProductRemove::EVENT_NAME, $eventLog, LazadaLog::STATUS_SUCCESS);
				//重新拉取这些SKU信息保存到lazada product 表
				$url = Yii::app()->request->hostInfo . '/lazada/lazadaproduct/getproducts/account_id/' . $accountAutoID . '/sku_list/' . implode(',', $skuList) . '/state/' . GetProductsRequest::PRODUCT_STATUS_DELETED;
                MHelper::runThreadBySocket ( $url );

				echo $this->successJson(array(
					'message' => Yii::t('system', 'Delete successful'),
					'navTabId' => 'page' . LazadaProduct::getIndexNavTabId(),
				));
				Yii::app()->end();
			}else{
				LazadaLog::model()->setFailure($logID, $lazadaProductRemoveModel->getExceptionMessage());
				LazadaLog::model()->saveEventStatus(LazadaProduct::EVENT_NAME, $eventLog, LazadaLog::STATUS_FAILURE);
			}
		}
		echo $this->failureJson(array(
				'message' => Yii::t('system', 'Delete failure'),
		));
		Yii::app()->end();		
	}
	
	/**
	 * @desc 更改lazada账号指定SKU状态为下线
	 */
	public function actionUpdatelazadaproduct() {
		$id = Yii::app()->request->getParam('id');
		if (!empty($id)) {
			$productObj	= LazadaProduct::model()->findByPk($id);
		}else {
			echo $this->failureJson(array(
					'message' => Yii::t('lazada_product', 'Not Specify Sku Which Need To Inactive'),
			));
			Yii::app()->end();
		}
		$sku	= $productObj->seller_sku;
		$siteID	= $productObj->site_id;
		$accountID	= $productObj->account_id;
		if (empty($sku) || empty($id)) {
			echo $this->failureJson(array(
					'message' => Yii::t('lazada_product', 'Not Specify Sku Which Need To Inactive'),
			));
			Yii::app()->end();
		}
		
		$logID = LazadaLog::model()->prepareLog($accountID,LazadaProductUpdate::EVENT_NAME);
		if ($logID) {
			//插入本次log参数日志(用来记录请求的参数)
			$time = date('Y-m-d H:i:s');
			$eventLog = LazadaLog::model()->saveEventLog(LazadaProductUpdate::EVENT_NAME, array(
					'log_id'        => $logID,
					'account_id'    => $accountID,
					'start_time'    => $time,
					'end_time'      => $time,
					'status'		=> LazadaProduct::PRODUCT_STATUS_INACTIVE,
			));
			//设置日志为正在运行
			LazadaLog::model()->setRunning($logID);
			$lazadaProductUpdateModel = new LazadaProductUpdate();
			//更改lazada账号产品状态
			$flag = $lazadaProductUpdateModel->updateAccountProducts($siteID, $accountID, $sku, LazadaProduct::PRODUCT_STATUS_TEXT_INACTIVE);
			//更新日志信息
			if( $flag ){
				LazadaLog::model()->setSuccess($logID);
				LazadaLog::model()->saveEventStatus(LazadaProductUpdate::EVENT_NAME, $eventLog, LazadaLog::STATUS_SUCCESS);
				//根据产品信息更新库中产品状态
				LazadaProduct::model()->updateStatus($id, LazadaProduct::PRODUCT_STATUS_INACTIVE);
				echo $this->successJson(array(
						'message' => Yii::t('system', 'Update successful'),
						'navTabId' => 'page' . LazadaProduct::getIndexNavTabId(),
				));
				Yii::app()->end();
			}else{
				LazadaLog::model()->setFailure($logID, $lazadaProductUpdateModel->getExceptionMessage());
				LazadaLog::model()->saveEventStatus(LazadaProduct::EVENT_NAME, $eventLog, LazadaLog::STATUS_FAILURE);
			}
		}
		echo $this->failureJson(array(
				'message' => Yii::t('system', 'Update failure'),
		));
		Yii::app()->end();
	}

    /**
     * @desc 系统自动导入下线sku, 条件：待清仓且可用库存小于等于0
     * @link /lazada/lazadaproduct/autoimportofflinetask
     */
    public function actionAutoimportofflinetask() {
        set_time_limit(3600);
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
                $variationInfos = LazadaProduct::model()->filterByCondition('account_auto_id'," status=1 and sku='{$v['sku']}' ");
                if (!empty($variationInfos)) {
                    foreach ($variationInfos as $vs) {
                    	if ($vs['account_auto_id'] == '') {
                    		continue;
                    	}
                        $rows[] = array(
                            'sku'            => $v['sku'],
                            'account_id'     => $vs['account_auto_id'],
                            'status'         => 0,
                            'create_user_id' => (int)Yii::app()->user->id,
                            'create_time'    => $nowTime,
                            'type'           => 2,//系统导入
                        );      
                    }
                }
                if ($rows) {
                    $res = LazadaOfflineTask::model()->insertBatch($rows);
                }
            }
        }
        Yii::app()->end('finish');
    }  
	
	/**
	 * @desc 导入批量下线SKU
	 */
	public function actionOfflineimport() {
        set_time_limit(3*3600);
        ini_set('memory_limit','2048M');
		if (Yii::app()->request->isPostRequest) {
			$accountIDs = Yii::app()->request->getParam('account_id');
			$type = $_FILES['offline_file']['type'];
			$tmpName = $_FILES['offline_file']['tmp_name'];
			$error = $_FILES['offline_file']['error'];
			$size = $_FILES['offline_file']['size'];
			$fileName = $_FILES['offline_file']['name'];
			$errors = '';
			switch ($error) {
				case 0: break;
				case 1:
				case 2:
					$errors = Yii::t('lazada_product', 'File Too Large');
					break;
				case 3:
					$errors = Yii::t('lazada_product', 'File Upload Partial');
					break;
				case 4:
					$errors = Yii::t('lazada_product', 'No File Upload');
					break;
				case 5:
					$errors = Yii::t('lazada_product', 'Upload File Size Zero');
					break;
				default:
					$errors = Yii::t('lazada_product', 'Unknow Error');
			}
			if (!empty($errors)) {
				echo $this->failureJson(array( 'message' => $errors));
				Yii::app()->end();
			}
			if (empty($accountIDs)) {
				echo $this->failureJson(array( 'message' => Yii::t('lazada_product', 'Not Select Account')));
				Yii::app()->end();
			}
			if (strpos($fileName, '.csv') == false) {
				echo $this->failureJson(array( 'message' => Yii::t('lazada_product', 'Please Upload CSV File')));
				Yii::app()->end();
			}
			$fp = fopen($tmpName, 'r');
			if (!$fp) {
				echo $this->failureJson(array( 'message' => Yii::t('lazada_product', 'Open File Failure')));
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
					$data[] = array(
						'sku'            => $sku,
						'account_id'     => $accountID,
						'status'         => 0,
						'create_user_id' => (int)Yii::app()->user->id,
						'create_time'    => date('Y-m-d H:i:s'),
						'type'           => 1,//手工导入
					);
				}
                if($row % 50 ==0){
                    $res = LazadaOfflineTask::model()->insertBatch($data);
                    $data = array();
                }
			}
            if(!empty($data)){
                $res = LazadaOfflineTask::model()->insertBatch($data);
            }
			fclose($fp);
			echo $this->successJson(array(
					'message' => Yii::t('lazada_product', 'Batch Offline Task Add Successful'),
					'callbackType' => 'closeCurrent'
			));
			Yii::app()->end();
		}
		$accountList = LazadaAccount::getAbleAccountList();
		$this->render('offline_import', array('account_list' => $accountList));
	}
	
	/**
	 * 下线批量下线任务的产品
	 * @link /lazada/lazadaproduct/processofflinetask
	 */
	public function actionProcessofflinetask() {
	    set_time_limit(3600);
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
	    $accountInfos = array();
	    while( $flag_while ){
	        $exe_time   =  time();
	        if(($exe_time - $time) >= 25200 ){
	            exit('执行超过7小时');
	        }
	        $res = LazadaOfflineTask::model()->getDbConnection()->createCommand()
			        ->from("ueb_lazada_offline_task")
			        ->select('id, sku, account_id')
			        ->where("status = 0")
			        ->limit(1000)
			        ->queryAll();      
	        if (!empty($res)) {
	            foreach ($res as $row) {
	                $accountID = $row['account_id'];
	                if (!isset($accountInfos[$accountID]))
	                        $accountInfos[$accountID] = LazadaAccount::getAccountInfoById($row['account_id']);
	                $data = array(
	                    'process_time' => date('Y-m-d H:i:s'),
	                    'status' => 1,
	                );
	                //查询要下线的listing
	                $skuOnline = LazadaProduct::model()->getOnlineListingBySku($row['sku'], $accountInfos[$accountID]['account_id'], $accountInfos[$accountID]['site_id']);
	                if (empty($skuOnline)) {
	                    LazadaOfflineTask::model()->getDbConnection()->createCommand()->delete("ueb_lazada_offline_task", "id = " . $row['id'] );
	                } else {
	                    LazadaOfflineTask::model()->getDbConnection()->createCommand()->update("ueb_lazada_offline_task", $data, "id = " . $row['id']);
	                }
	            }
	        } else {
	            $flag_while = false;
	        }
	    }
	    
	    //有线上sku的执行下架
	    $current_time = array();
	    $accountInfos = array();
	    while( $flag_online ){
	        $exe_time   =  time();
	        if(($exe_time - $time) >= 25200 ){
	            exit('执行超过7小时');
	        }	    	
	        $res = LazadaOfflineTask::model()->getDbConnection()->createCommand()
		            ->from("ueb_lazada_offline_task")
		            ->select('id, sku, account_id')
		            ->where("status = 1")
		            ->limit(1000)
		            ->queryAll();
	        if (!empty($res)) {
	            foreach ($res as $row) {
	                $exe_time   =  time();
	                if(($exe_time - $time) >= 36000 ){
	                    exit('执行超过10小时');
	                }
	                $accountID = $row['account_id'];
	                if (!isset($accountInfos[$accountID]))
	                        $accountInfos[$accountID] = LazadaAccount::getAccountInfoById($row['account_id']);
	                $data = array(
                        'process_time' => date('Y-m-d H:i:s'),
                        'status' => 1,
	                );
	                //查询要下线的listing
	                $skuOnline = LazadaProduct::model()->getOnlineListingBySku($row['sku'], $accountInfos[$accountID]['account_id'], $accountInfos[$accountID]['site_id']);
	                if (empty($skuOnline)) {
	                    LazadaOfflineTask::model()->getDbConnection()->createCommand()->delete("ueb_lazada_offline_task", "id = " . $row['id'] );
	                } else {
                        if(isset($current_time[$accountID])){
                            $sleep_time = 125 - (time() - $current_time[$accountID]);
                            if($sleep_time >= 0){
                                sleep($sleep_time);
                            }
                        }
                        $request = new ProductUpdateRequestNew();    
                        $params = array();
				        $insertArr = array(
				            'SellerSku' => $skuOnline[0]['seller_sku'],
				            'status'  => 'inactive'
				        );
				        $params[] = $insertArr;
				        $request->setSkus($params);
				        $request->push();
                        $response = $request->setApiAccount($accountID)->setRequest()->sendRequest()->getResponse();
                        if (!$request->getIfSuccess()) {
                                $data['status'] = -1;
                                $data['response_msg'] = $request->getErrorMsg();
                        } else {
                                $data['status'] = 2;
                                $data['response_msg'] = 'SUCCESS';
                        }
                        LazadaOfflineTask::model()->getDbConnection()->createCommand()->update("ueb_lazada_offline_task", $data, "id = " . $row['id']);
                        $current_time[$accountID] = time();
	                }
	            }
	        } else {
	            $flag_online = false;
	        }
	    }

		exit('DONE');
	}
	
	public function actionDelrepeat(){
		set_time_limit(3600);
		$limit = 0;
		do{
			$productModel = new LazadaProduct();
			$limits = "$limit, 50";
			$list = $productModel->getDbConnection()->createCommand()
							->from($productModel->tableName())
							->select("id, count(1) total, sku, account_id, site_id,seller_sku")
							->group("seller_sku,account_id,site_id")
							->having("total>1")
							->order("sku DESC")	
							->limit(50, $limit)
							->queryAll();
			//$limit+=50;
			$ids = array();
			$skus = array();
			if($list){
				foreach ($list as $v){
					$skus[] = $v['seller_sku'];
					$ids[] = $v['id'];
				}
				$productModel->getDbConnection()->createCommand()
					->delete($productModel->tableName(), "id not in('". implode("','", $ids) ."') AND seller_sku in ('". implode("','", $skus) ."')");
			}else{
				break;
			}
		}while ($list);
		echo "done";
	}
        
    /**
	 * @desc 更改lazada账号指定SKU状态为上线
	 */
	public function actionOnlinelazadaproduct() {
		$id = Yii::app()->request->getParam('id');
		if (!empty($id)) {
			$productObj	= LazadaProduct::model()->findByPk($id);
		}else {
			echo $this->failureJson(array(
					'message' => Yii::t('lazada_product', 'Not Specify Sku Which Need To active'),
			));
			Yii::app()->end();
		}
		$sku	= $productObj->seller_sku;
		$siteID	= $productObj->site_id;
		$accountID	= $productObj->account_id;
		if (empty($sku) || empty($id)) {
			echo $this->failureJson(array(
					'message' => Yii::t('lazada_product', 'Not Specify Sku Which Need To active'),
			));
			Yii::app()->end();
		}
		
		$logID = LazadaLog::model()->prepareLog($accountID,LazadaProductUpdate::EVENT_NAME);
		if ($logID) {
			//插入本次log参数日志(用来记录请求的参数)
			$time = date('Y-m-d H:i:s');
			$eventLog = LazadaLog::model()->saveEventLog(LazadaProductUpdate::EVENT_NAME, array(
					'log_id'        => $logID,
					'account_id'    => $accountID,
					'start_time'    => $time,
					'end_time'      => $time,
					'status'	=> LazadaProduct::PRODUCT_STATUS_ACTIVE,
			));
			//设置日志为正在运行
			LazadaLog::model()->setRunning($logID);
			$lazadaProductUpdateModel = new LazadaProductUpdate();
			//更改lazada账号产品状态
			$flag = $lazadaProductUpdateModel->updateAccountProducts($siteID, $accountID, $sku, LazadaProduct::PRODUCT_STATUS_TEXT_ACTIVE);
			//更新日志信息
			if( $flag ){
				LazadaLog::model()->setSuccess($logID);
				LazadaLog::model()->saveEventStatus(LazadaProductUpdate::EVENT_NAME, $eventLog, LazadaLog::STATUS_SUCCESS);
				//根据产品信息更新库中产品状态
				LazadaProduct::model()->updateStatus($id, LazadaProduct::PRODUCT_STATUS_ACTIVE);
				echo $this->successJson(array(
						'message' => Yii::t('system', 'Update successful'),
						'navTabId' => 'page' . LazadaProduct::getIndexNavTabId(),
				));
				Yii::app()->end();
			}else{
				LazadaLog::model()->setFailure($logID, $lazadaProductUpdateModel->getExceptionMessage());
				LazadaLog::model()->saveEventStatus(LazadaProduct::EVENT_NAME, $eventLog, LazadaLog::STATUS_FAILURE);
			}
		}
		echo $this->failureJson(array(
				'message' => Yii::t('system', 'Update failure'),
		));
		Yii::app()->end();
	}
    

    /**
     * @desc lazada所有停售产品，在线listing直接下架
     * @link /lazada/lazadaproduct/autoshelfproducts/accountID/21/sku/111
     */
    public function actionAutoshelfproducts() {
        set_time_limit(3300);
        ini_set("memory_limit","2048M");
        ini_set('display_errors', true);
        error_reporting(E_ALL);

		$warehouseSkuMapModel   = new WarehouseSkuMap();
		$logModel               = new LazadaLog();
		$lazadaProductModel     = new LazadaProduct();
		$lazadaLogOfflineModel  = new LazadaLogOffline();

        //账号
        $accountID = Yii::app()->request->getParam('accountID');

        //指定某个特定sku----用于测试
        $setSku = Yii::app()->request->getParam('sku');

        $select    = 't.sku';
        $eventName = 'auto_shelf_products';
        $limit     = 30000;
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
                    $command = $lazadaProductModel->getDbConnection()->createCommand()
                        ->from($lazadaProductModel->tableName())
                        ->select("id,sku,account_auto_id,account_id,quantity,seller_sku,site_id,sale_price,sale_start_date,sale_end_date,price")
                        ->where('account_auto_id = '.$accountID.' AND status = 1');
                        if($setSku){
                            $command->andWhere("sku = '".$setSku."'");
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

                    //数组去重
                    $skuUnique = array_unique($skuArr);
 
                    $conditions = 't.available_qty <= :available_qty AND t.warehouse_id = :warehouse_id AND p.product_is_multi != 2 AND p.product_status IN(6,7) AND t.sku IN('.MHelper::simplode($skuUnique).')';
                    $param = array(
                        ':available_qty'  => 0, 
                        ':warehouse_id'   => WarehouseSkuMap::WARE_HOUSE_GM
                    );

                    $skuList = $warehouseSkuMapModel->getSkuListLeftJoinProductByCondition($conditions, $param, '', $select);
                    if(!$skuList){
                        continue;            
                    } 

                    unset($skuArr);

                    foreach ($skuList as $skuVal){
                        $skuListArr[] = $skuVal['sku'];
                    }

					$time = date("Y-m-d H:i:s");

                    foreach ($variantListing as $variant){
                    	$insertArr = array();
                        if(!in_array($variant['sku'], $skuListArr)){
                            continue;
                        }

                        $prices = $variant['sale_price'];
                        if($prices <= 0){
                        	$prices = $variant['price'];
                        }

                        if($prices <= 0){
                        	continue;
                        }

						$insertArr[] = array(
							'SellerSku'=>$variant['seller_sku'],
							'active'=>'false',
							'special_price' => $prices,
							'special_from_date' => date('Y-m-d 00:00:00'),
							'special_to_date' => date('Y-m-d 00:00:00', strtotime("+10 year"))
						);
                        $addData = array(
                            'product_id'        => $variant['id'],
                            'sku'               => $variant['sku'],
                            'account_id'        => $variant['account_auto_id'],
                            'site_id'           => $variant['site_id'],
                            'event'             => 'autoshelfproducts',
                            'status'            => 1,
                            'inventory'         => $variant['quantity'],                            
                            'message'           => '下架成功',
                            'start_time'        => $time,
                            'response_time'     => date("Y-m-d H:i:s"),
                            'operation_user_id' => 1
                        );
                        $request = new ProductUpdateRequestNew();
	                    $request->setSkus($insertArr);
	                    $request->push();
	                    $response = $request->setApiAccount($accountID)->setRequest()->sendRequest()->getResponse();
	                    if ($request->getIfSuccess()) {
	                        $this->_model->batchUpdateStatus($variant['id'], LazadaProduct::PRODUCT_STATUS_INACTIVE);
                        	$lazadaLogOfflineModel->savePrepareLog($addData);       
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
            $accountList = LazadaAccount::model()->getAbleAccountList();
            foreach($accountList as $key => $value){
                MHelper::runThreadSOCKET('/'.$this->route.'/accountID/' . $value['id']);
                sleep(1);
            }
        }
    }


    /**
     * 产品管理选择复制账号页面
     */
    public function actionCopylisting(){
    	$ids = Yii::app()->request->getParam('ids');
    	$accountList = array();
        $accountInfo = LazadaAccount::model()->getAbleAccountList(LazadaSite::SITE_MY);
        foreach ($accountInfo as $value) {
        	$accountList[$value['id']] = $value['short_name'];
        }
        $this->render('copylisting', array('model'=>$this->_model, 'accountList'=>$accountList, 'ids'=>$ids));
    }


    /**
     * 产品管理复制刊登
     */
    public function actionCopyproductadd(){
    	set_time_limit(5*3600);
        ini_set('display_errors', true);
        error_reporting(E_ALL);

        $accountArr = Yii::app()->request->getParam('LazadaProduct');
        $ids = $accountArr['ids'];
        $accountIdArr = $accountArr['account_auto_id'];
        if(!$ids){
            echo $this->failureJson(array('message'=>'请选择'));
            exit;
        }

        if(!$accountIdArr){
            echo $this->failureJson(array('message'=>'请选择要刊登的账号'));
            exit;
        }

        $accountList = array();
        $accountInfo = LazadaAccount::model()->getAbleAccountList(LazadaSite::SITE_MY);
        foreach ($accountInfo as $value) {
        	$accountList[] = $value['id'];
        }

        $idsArr = explode(',', $ids);
    	$lazadaProductModel = new LazadaProduct();
    	foreach ($accountIdArr as $accountID) {
    		if(!in_array($accountID, $accountList)){
    			continue;
    		}

    		foreach ($idsArr as $id) {
    			$errorArr = array(
					'product_id'     => $id,
					'account_id'     => $accountID,
					'create_user_id' => Yii::app()->user->id,
					'message'        => '',
					'create_time'    => date('Y-m-d H:i:s')
	    		);

    			$isExist = $lazadaProductModel->getOneByCondition('site_id,sku',"id = '{$id}' AND status <> 3");
    			if(!$isExist){
    				$errorArr['message'] = '此产品已被删除';
    				LazadaLogBatchProductAdd::model()->savePrepareLog($errorArr);
    				continue;
    			}

    			if($isExist['site_id'] != LazadaSite::SITE_MY){
    				$errorArr['message'] = '此产品不是MY站点无法复制';
    				LazadaLogBatchProductAdd::model()->savePrepareLog($errorArr);
    				continue;
    			}

    			$otherAccountInfo = $lazadaProductModel->getOneByCondition('site_id',"sku = '{$isExist['sku']}' AND account_auto_id = '{$accountID}' AND status <> 3");
    			if($otherAccountInfo){
    				$errorArr['message'] = '此账号的产品已经存在';
    				LazadaLogBatchProductAdd::model()->savePrepareLog($errorArr);
    				continue;
    			}
    			
    			$getResult = $lazadaProductModel->productByCopy($id, $accountID, LazadaSite::SITE_MY);
		    	if(!$getResult){
		    		$error = $lazadaProductModel->getExceptionMessage();
		    		$errorArr['message'] = $error;
		    		LazadaLogBatchProductAdd::model()->savePrepareLog($errorArr);
		    	}
    		}
    	}

    	$jsonData = array(
                'message' => '复制刊登完成',
                'forward' =>'/lazada/lazadaproduct/list',
                'navTabId'=> 'page' . LazadaProduct::getIndexNavTabId(),
                'callbackType'=>'closeCurrent'
            );
        echo $this->successJson($jsonData);    	
    }
}