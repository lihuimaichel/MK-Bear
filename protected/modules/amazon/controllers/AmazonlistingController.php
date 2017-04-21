<?php
class AmazonlistingController extends UebController {
	public function accessRules() {
		return array(
			array(
				'allow',
				'users' => array('*'),
				'actions' => array('getlisting', 'requestreport', 'test', 'requestdelingreport', 'getdeledlisting')
			)
		);
	}
	
	/**
	 * @desc 发送获取在售商品列表报告，包含了库存为0的产品
	 * @link /amazon/amazonlisting/requestmerchantreport/account_id/xxx
	 */
	public function actionRequestmerchantreport(){
		ini_set('display_errors', true);
		error_reporting(E_ALL);
		set_time_limit(3600);
		$accountID = Yii::app()->request->getParam('account_id');
		if($accountID){
			$reportType = RequestReportRequest::REPORT_TYPE_MERCHANT_LISTINGS_DATA;

			//判断近3个小时内最近的日志是否有成功发起的请求，如果有则不再发请求
			// $left_time = date('Y-m-d H:i:s',time()-3600*3);   //三个小时前   
			// $lastreportRequestInfo = AmazonLog::model()->getAccountLastRequestInfo($accountID,AmazonRequestReport::EVENT_NAME);	//最近的账号请求日志
			// if ($lastreportRequestInfo && ($lastreportRequestInfo['status'] == AmazonLog::STATUS_SUCCESS) && ($lastreportRequestInfo['response_time'] > $left_time)){
			// 	echo "此账号最近（3个小时内）已成功发起在售商品报告的请求，不再发请求。";
			// }else{				
			// 	$this->_requestReport($accountID, $reportType);
			// 	echo "<br />SUCCESS";
			// }	
				$this->_requestReport($accountID, $reportType);
				echo "<br />SUCCESS";
			Yii::app()->end();
		}else {
			$accountList = AmazonAccount::model()->getCronGroupAccounts();
			//循环每个账号发送一个报告请求
			foreach ($accountList as $accountID) {
				MHelper::runThreadSOCKET('/'.$this->route.'/account_id/' . $accountID);
				sleep(1);				
			}
		}
	}
	
	/**
	 * @desc 获取在售商品列表，包含了库存为0的产品
	 * @link /amazon/amazonlisting/getmerchantlisting/account_id/43
	 */
	public function actionGetmerchantlisting(){
		set_time_limit(3600);
        ini_set("display_errors", false);
    	error_reporting(0);
	
		$msg = '';
		$save_false = 0;	//保存失败		
		$accountID = Yii::app()->request->getParam('account_id');
		$path  = 'amazon/getmerchantlisting/'.date("Ymd").'/'.$accountID;	

		if($accountID){
			$amazonListModel = new AmazonList();

			//判断最近的请求是否成功，不成功则不拉取（拉取也是无有效数据）
			// $reportRequestInfo = AmazonLog::model()->getAccountLastRequestInfo($accountID,AmazonRequestReport::EVENT_NAME);
			// if ($reportRequestInfo){
			// 	if ($reportRequestInfo['status'] != AmazonLog::STATUS_SUCCESS){
			// 		echo "此账号请求接口返回不成功，不进行数据拉取。";
			// 		Yii::app()->end();				
			// 	}
			// }else{
			// 	echo "此账号无相关请求记录，不执行数据拉取。";
			// 	Yii::app()->end();	
			// }

			//判断最近(3小时内)如果拉取成功则不再拉取（短时间内减少拉取次数）
			// $left_time = date('Y-m-d H:i:s',time()-3600*3);   //三个小时前
			// $getlistingInfo = AmazonLog::model()->getAccountLastRequestInfo($accountID,RequestReportRequest::REPORT_TYPE_MERCHANT_LISTINGS_DATA);
			// if ($getlistingInfo){
			// 	if (($getlistingInfo['status'] == AmazonLog::STATUS_SUCCESS) && ($getlistingInfo['response_time'] > $left_time)){
			// 		echo "此账号最近（3小时内）已成功拉取数据，因此此账号不再进行数据拉取。";
			// 		Yii::app()->end();				
			// 	}	
			// }			

			//因为每次都是拉取全量，如果上次操作的上架的listing确认状态为0（即超过五天没有更新），则认为此卖家的listing销售状态为已删除(2)
			$conditions = array(
				'account_id'       => $accountID,		
				'confirm_status'   => 0
				);
			$update_data = array(
				'seller_status' => AmazonList::SELLER_STATUS_CANCEL
				);	
			$update_ret = $amazonListModel->updateListingGroupByIds($conditions,$update_data);

			if ($update_ret){
				//重置标识状态为0
				$conditions = array(
					'account_id'       => $accountID,			
					'confirm_status'   => 1
					);			
				$update_data = array('confirm_status' => 0);
				$amazonListModel->updateListingGroupByIds($conditions,$update_data);
			}

			// AmazonRequestReport::model()->updateByPk('40353', array('report_processing_status'=>'_SUBMITTED_'));

            $reportType = RequestReportRequest::REPORT_TYPE_MERCHANT_LISTINGS_DATA;
			$listData = $this->_getListingReportList($accountID, $reportType);
			if (!empty($listData)) {

				//拉取数据保存到文档		
	            // $result = 'accountID: '.$accountID.' ###'.date("Y-m-d H:i:s").'###'."\r\n";
	            // $result .= json_encode($listData);
	            // MHelper::writefilelog($path.'/listing-'.$accountID.'-'.date("YmdHis").'.txt', $result."\r\n");

				$amazonListModel = new AmazonList();
				$amazonListModel->setAccountID($accountID);
				$ret = $amazonListModel->saveAmazonList($listData);
				if (!$ret){
					$error = $amazonListModel->getErrorMsg();
					$save_false = 1;
					$msg = '保存失败：'.$error;					
				}else{
					$count = count($listData);
					$msg = "<br />SUCCESS, Get {$count} ";	
				}
			}else{
				$save_false = 1;
				$msg = '返回结果为空';
			}	
			//如果没有成功获取到数据，则通知表ueb_amazon_request_report对应记录已完成(_DONE_)，并写入失败原因
			if ($save_false){
				//取出报告请求里面最后一次提交的获取可售商品报告的请求
				$reportRequestData = AmazonRequestReport::model()->getAccountLastRequest($accountID, $reportType);		
				if ($reportRequestData){		
					$Status = '_DONE_';	//手动标识此记录已结束
					AmazonRequestReport::model()->updateAll(array('report_processing_status' => $Status, 'report_skus' => $msg), "id=:id", array(":id"=>$reportRequestData['id']));			
				}
			}
/*
			//记录日志
			$errlog = '';		
            $err_result = date("Y-m-d H:i:s").'###'.json_encode($_REQUEST).'==='.$msg."\r\n";
            //MHelper::writefilelog($path.'/result_'.date("Ymd").'.log', $err_result."\r\n");	
*/
			echo $msg;
			Yii::app()->end();
		} else {
			$accountList = AmazonAccount::model()->getCronGroupAccounts();
			//循环每个账号拉取产品数据
			foreach ($accountList as $accountID) {
				MHelper::runThreadSOCKET('/'.$this->route.'/account_id/' . $accountID);
				sleep(1);
			}
			Yii::app()->end();
		}
	}
	
	/**
	 * @desc 向亚马逊提交可售商品列表请求报告的任务
	 */
	public function actionRequestreport() {
		ini_set('display_errors', true);
		error_reporting(E_ALL);
		set_time_limit(3600);
		$accountID     = Yii::app()->request->getParam('account_id');
		$reportType    = Yii::app()->request->getParam('report_type');
		$reportOptions = Yii::app()->request->getParam('report_options');

		if( $accountID ){//根据账号抓取订单信息
			$this->_requestReport($accountID, $reportType, $reportOptions);
		} else {
			$accountList = AmazonAccount::getAbleAccountList();
			//循环每个账号发送一个报告请求
			foreach ($accountList as $accountInfo) {
				$curlOptions = array(
					CURLOPT_POST => false,
					CURLOPT_URL => $this->createAbsoluteUrl('requestreport', array('account_id' => $accountInfo['id'], 'report_type' => '_GET_MERCHANT_LISTINGS_DATA_BACK_COMPAT_')),
					CURLOPT_TIMEOUT => 30
				);
				$curl = curl_init();
				curl_setopt_array($curl, $curlOptions);
				echo "<br/>=========AccountId:{$accountInfo['id']} >>  =============<br/>";
				$response = curl_exec($curl);
				print_r($response);
				echo "<br/>============== END ==============<br/>";
			}
		}
	}
	/**
	 * @desc  获取可售商品列表（不包含库存为0）
	 */
	public function actionGetlisting() {
		ini_set('display_errors', true);
		error_reporting(E_ERROR);
		set_time_limit(3600);
		ini_set('memory_limit', '1024M');
		$accountID = Yii::app()->request->getParam('account_id');
		if ($accountID) {
			$listData = $this->_getListingReportList($accountID);
			if (!empty($listData)) {
				$amazonListModel = new AmazonList();
				$amazonListModel->setAccountID($accountID);
				$amazonListModel->saveAmazonList($listData, true);
				$count = count($listData);
				echo "SUCCESS, Get {$count} ";
			}else{
				echo "No Result !";
			}
		} else {
			$accountList = AmazonAccount::getAbleAccountList();
			foreach ($accountList as $accountInfo) {
				$curlOptions = array(
						CURLOPT_POST => false,
						CURLOPT_URL => $this->createAbsoluteUrl('getlisting', 
																array(
																	'account_id' => $accountInfo['id'], 
																	'report_type' => RequestReportRequest::REPORT_TYPE_LISTINGS_DATA_CAMPAT
																)
														),
						CURLOPT_TIMEOUT => 300
				);
				$curl = curl_init();
				curl_setopt_array($curl, $curlOptions);
				echo "<br/>============= AccountId:{$accountInfo['id']} >>  ===============<br/>";
				$response = curl_exec($curl);
				print_r($response);
				echo "<br/>=========== END ============== <br/>";
			}
			exit();
		}
	}


	/**
	 * 
	 * @desc 获取某分类数据并入库	Liz|2016/3/14
	 * 
	 */
	public function actionSetCategory(){
		ini_set('display_errors', true);
		error_reporting(E_ALL);
		set_time_limit(3600);
		ini_set('memory_limit', '256M');

		$accountID         = Yii::app()->request->getParam('account_id');
		$reportType        = Yii::app()->request->getParam('report_type');
		$report_request_id = Yii::app()->request->getParam('report_request_id');
		if(empty($reportType)) $reportType = RequestReportRequest::REPORT_TYPE_BROWSE_TREE_DATA;	//设默认类型：分类树报告
		
		$result = $this->_getListingReportList($accountID, $reportType, $report_request_id);	//通过小分类做测试实例：63404016889
		
		// MHelper::printvar($result);

		if (isset($result->Node) && count($result->Node) > 0) {
			$amazonCategoryModel = new AmazonCategory();
			$amazonCategoryModel->saveAmazonCategory($result->Node);
			$count = count($result->Node);
			echo "SUCCESS, Get {$count} ";
		}else{
			echo "No Result !";
		}		
	}	


	/**
	 * @desc 提交请求报告并获取报告入库：请求根分类列表	Liz|2016/4/1
	 */
	public function actionSetRootCategory() {
		ini_set('display_errors', true);
		error_reporting(E_ALL);
		set_time_limit(3600);
		ini_set('memory_limit', '256M');

		$accountID     = Yii::app()->request->getParam('account_id');
		$reportType    = RequestReportRequest::REPORT_TYPE_BROWSE_TREE_DATA;	//指定分类树报告
		$reportOptions = 'RootNodesOnly=true';	//请求参数:指定根分类

		$this->_requestReport($accountID, $reportType, $reportOptions);

		//循环获取报告
		for($i=0; $i<AmazonCategory::REPROT_FOR_NUM; $i++){
			sleep(AmazonCategory::SLEEP_TIME);
			$result = $this->_getListingReportList($accountID, $reportType);
			if(is_object($result) || $result != AmazonCategory::FOR_CONTINUE_FLAG) break;
		}

		if (isset($result->Node) && count($result->Node) > 0) {
			$amazonCategoryModel = new AmazonCategory();
			$amazonCategoryModel->saveAmazonCategory($result->Node,1);
			$count = count($result->Node);
			echo "SUCCESS, Get {$count} ";
		}else{
			echo "No Result !";
		}		
	}	


	/**
	 * @desc 循环提交请求报告：请求各分类根节点子数据	Liz|2016/3/24
	 */
	public function actionRequestAllSubCategory() {
		ini_set('display_errors', true);
		error_reporting(E_ALL);
		set_time_limit(3*3600);
		ini_set('memory_limit', '256M');

		$accountID     = Yii::app()->request->getParam('account_id');
		$reportType = RequestReportRequest::REPORT_TYPE_BROWSE_TREE_DATA;	//指定分类树报告
		$amazonCategoryModel = new AmazonCategory();
		$rootCategoryList = $amazonCategoryModel->getSubCategoryList(0);	//获取父分类ID=0的根分类列表
		if ($rootCategoryList){
			foreach ($rootCategoryList as $key => $val){

				//if ($key == 5) break;	//暂时只是设置5条请求

				if (isset($val['category_id']))
				{
					$reportOptions = 'BrowseNodeId='.$val['category_id'];	//请求各根分类下的分类数据
					$this->_requestReport($accountID, $reportType, $reportOptions);
				}
				sleep(20);	//接口最大15个请求，每1分钟恢复一个，差6个，如20秒一个，21个分类则420秒>(6*60)
			}
		}else{
			echo '<br />Not root category record!';exit;
		}
		echo '<br />Success request all report!<br />Total requested have '.($key + 1).' record of root category!';exit;
	}


	/**
	 * 
	 * @desc 循环获取所有根分类报告并入库	Liz|2016/3/25
	 * 
	 */
	public function actionSetAllSubCategory(){
		ini_set('display_errors', true);
		error_reporting(E_ALL);
		set_time_limit(2*3600);
		ini_set('memory_limit', '1024M');	//256M不足，只执行到14条根记录

		$accountID = Yii::app()->request->getParam('account_id');
		$reportType = RequestReportRequest::REPORT_TYPE_BROWSE_TREE_DATA;
		
		//获取分类根节点数据，取到根节点分类ID，匹配到请求表ueb_amazon_request_report的report_skus，找到当前的请求报告ID:report_request_id
		$amazonCategoryModel = new AmazonCategory();
		$rootCategoryList = $amazonCategoryModel->getSubCategoryList(0);

		if ($rootCategoryList){
			$i = 0;
			$updateList = '';
			$notUpdateList = '';
			$notUpdateFlag = false;			
			foreach($rootCategoryList as $key => $val){
				if ($key < 11) continue;
				if (isset($val['category_id'])){
					$categoryID = $val['category_id'];
					$amazonRequestReport = new AmazonRequestReport();
					$conditions = "account_id=:account_id AND report_type=:report_type AND report_processing_status=:report_processing_status AND report_skus=:report_skus";
					$params = array(
									':account_id'               => $accountID,
									':report_type'              => $reportType,
									':report_processing_status' => GetReportRequestListRequest::PROCESSING_STATUS_SUBMITTED,
									':report_skus'              => $categoryID
								);
					$reportList = $amazonRequestReport->getRequestReportList($conditions, $params, 1);		

					$reportInfo = ($reportList) ? $reportList[0] : array();	
					if ($reportInfo && isset($reportInfo['report_request_id'])){
						$result = $this->_getListingReportList($accountID, $reportType, $reportInfo['report_request_id']);		//'63364016885'

						if (isset($result->Node) && count($result->Node) > 0) {
							$amazonCategoryModel = new AmazonCategory();
							$ret = $amazonCategoryModel->saveAmazonCategory($result->Node);
							if ($ret){
								$i++;
								$updateList .= $categoryID .',';
							}else{
								$notUpdateFlag = true;
							}
						}else{
							$notUpdateFlag = true;
						}	
						unset($result);				
					}else{
						$notUpdateFlag = true;
					}
					unset($reportInfo);
					unset($reportList);
				}
				if ($notUpdateFlag) $notUpdateList .= $categoryID .',';

				// sleep(20);	//接口最大15个请求，每1分钟恢复一个，差6个，如20秒一个，21个分类则420秒>(6*60)
			}
			echo 'Update success: 共 ' .$i. '个根分类，' .substr($updateList,0,strlen($updateList)-1);
			echo '<br />';
			echo 'Update false: ' .substr($notUpdateList,0,strlen($notUpdateList)-1);
		}else{
			echo '没有根分类数据！请先获取根分类！';
		}
		
	}
	
	/**
	 * @desc 请求已取消的商品报表
	 */
	public function actionRequestdelingreport(){
		$accountID = Yii::app()->request->getParam('account_id');
		$reportType = RequestReportRequest::REPORT_TYPE_CANCELLED_LISTINGS_DATA;
		$this->_requestReport($accountID, $reportType);
		echo "SUCCESS";
	}
	/**
	 * @desc 获取Amazon已经取消的商品列表
	 */
	public function actionGetdeledlisting(){
		$accountID = Yii::app()->request->getParam('account_id');
		$reportType = RequestReportRequest::REPORT_TYPE_CANCELLED_LISTINGS_DATA;
		$listData = $this->_getListingReportList($accountID, $reportType);
		if (!empty($listData)) {
			/* $amazonListModel = new AmazonList();
			$amazonListModel->setAccountID($accountID);
			$amazonListModel->saveAmazonList($listData, true); */
			print_r($listData);
			$count = count($listData);
			echo "SUCCESS, Get {$count} ";
		}else{
			echo "No Result !";
		}
		
	}
	
	
	/**
	 * @desc 发送已售商品列表报告
	 */
	public function actionRequestsaledreport(){
		$accountID = Yii::app()->request->getParam('account_id');
		$reportType = RequestReportRequest::REPORT_TYPE_SOLD_LISTINGS_DATA;
		$this->_requestReport($accountID, $reportType);
		echo "SUCCESS";
	}
	/**
	 * @desc 获取已售商品列表
	 */
	public function actionGetsaledlisting(){
		$accountID = Yii::app()->request->getParam('account_id');
		$reportType = RequestReportRequest::REPORT_TYPE_SOLD_LISTINGS_DATA;
		$listData = $this->_getListingReportList($accountID, $reportType);
		if (!empty($listData)) {
			/* $amazonListModel = new AmazonList();
				$amazonListModel->setAccountID($accountID);
			$amazonListModel->saveAmazonList($listData, true);
			*/
			$count = count($listData);
			echo "SUCCESS, Get {$count} ";
			print_r($listData);
		}else{
		echo "No Result !";
		}
	}	
	
	/**
	 * @desc 发送请求库存列表报告（包含库存为0）
	 */
	public function actionRequestavailablereport(){
		ini_set('display_errors', true);
		error_reporting(E_ALL);
		set_time_limit(3600);
		$accountID = Yii::app()->request->getParam('account_id');
		if($accountID){
			$reportType = RequestReportRequest::REPORT_TYPE_LIST_STOCK_DATA;
			$this->_requestReport($accountID, $reportType);
			echo "SUCCESS";
		}else {
			$accountList = AmazonAccount::model()->getCronGroupAccounts();
			foreach ($accountList as $accountID) {
				$url = Yii::app()->request->hostInfo.'/' . $this->route . '/account_id/' . $accountID;
				MHelper::runThreadBySocket($url);
				sleep(1);
			}
		}
		Yii::app()->end();
	}

	/**
	 * @desc 获取可售商品列表【库存列表】只包括了库存数量、ASIN码、seller_sku、price数据（包含库存为0）
	 * @link /amazon/amazonlisting/getavailablelisting/account_id/43
	 */
	public function actionGetavailablelisting(){
		ini_set('display_errors', true);
		error_reporting(E_ALL);
		set_time_limit(3600);			
		$accountID = Yii::app()->request->getParam('account_id');

		if ($accountID){
			$reportType = RequestReportRequest::REPORT_TYPE_LIST_STOCK_DATA;
			$listData = $this->_getListingReportList($accountID, $reportType);
			if (!empty($listData)) {

				$amazonListModel = new AmazonList();
				$amazonListModel->setAccountID($accountID);
				$amazonListModel->updateQuantityPriceList($listData);	//（仅更新库存和价格）
				
				$count = count($listData);
				echo "SUCCESS, Get {$count} ";
			}else{
				echo "No Result !";
			}
 		}else{
			$accountList = AmazonAccount::model()->getCronGroupAccounts();
			foreach ($accountList as $accountID) {
				$url = Yii::app()->request->hostInfo.'/' . $this->route . '/account_id/' . $accountID;
				MHelper::runThreadBySocket($url);
				sleep(1);
			}
			exit();
		}
		Yii::app()->end();
	}


	/**
	 * @desc 发送请求亚马逊物流(FBA)库存报告（包含库存为0，不可售等）
	 */
	public function actionRequestFBAInventoryReport(){
		ini_set('display_errors', true);
		error_reporting(E_ALL);
		set_time_limit(3600);
		$accountID = Yii::app()->request->getParam('account_id');
		if($accountID){
			$reportType = RequestReportRequest::REPORT_TYPE_AFN_INVENTORY_DATA;
			$this->_requestReport($accountID, $reportType);
			echo "SUCCESS";
		}else {
			$accountList = AmazonAccount::model()->getCronGroupAccounts();
			foreach ($accountList as $accountID) {
				$url = Yii::app()->request->hostInfo.'/' . $this->route . '/account_id/' . $accountID;
				MHelper::runThreadBySocket($url);
				sleep(1);
			}
		}
		Yii::app()->end();		
	}

	/**
	 * @desc 获取和更新FBA库存（包含库存为0，不可售等信息），确认FBA的销售状态。(seller-sku	fulfillment-channel-sku	asin  condition-type	Warehouse-Condition-code	Quantity Available)
	 * @link /amazon/amazonlisting/GetFBAInventorylist/account_id/43
	 * 
	 */
	public function actionGetFBAInventoryListing(){
		ini_set('display_errors', true);
		error_reporting(E_ALL);
		set_time_limit(3600);

		$accountID = Yii::app()->request->getParam('account_id');
		$fulfillment_type = AmazonList::FULFILLMENT_STATUS_AMAZON;

		//数据初始化,把库里seller_status=4(amazon发货)重新定义FBA状态（fulfillment_type=2）以及销售状态(默认为上架：seller_status=1)
		// AmazonList::model()->getDbConnection()->createCommand()->update(AmazonList::model()->tableName(),array("fulfillment_type"=>2,"seller_status" => 1),"seller_status = 4");

		if($accountID){
			$reportType = RequestReportRequest::REPORT_TYPE_AFN_INVENTORY_DATA;
			$listData = $this->_getListingReportList($accountID, $reportType);

			if (!empty($listData)) {
				$amazonListModel = new AmazonList();
				$amazonListModel->setAccountID($accountID);
				$amazonListModel->saveFBAInventoryList($listData);
				$count = count($listData);
				echo "SUCCESS, Get {$count} ";
				//print_r($listData);
			}else{
				echo "No Result !";
			}

		} else {
			$accountList = AmazonAccount::model()->getCronGroupAccounts();
			//循环每个账号拉取产品数据
			foreach ($accountList as $accountID) {
				MHelper::runThreadSOCKET('/'.$this->route.'/account_id/' . $accountID);
				sleep(1);
			}
			exit();
		}
	}		

	/**
	 * @desc 设置请求报表
	 * @param unknown $accountID
	 * @param unknown $reportType
	 * @param string $reportOptions 请求参数附加可选条件 liz|2016/3/18
	 */
	private function _requestReport($accountID, $reportType, $reportOptions = null){
		ini_set('display_errors', true);
		error_reporting(E_ALL);
		set_time_limit(3600);
		$msg = '';
		if( $accountID ){//根据账号抓取订单信息
			$amazonLogModel = new AmazonLog();
			$logID = $amazonLogModel->prepareLog($accountID,AmazonRequestReport::EVENT_NAME,$reportType);
			if( $logID ){
				//1.检查账号是可以提交请求报告
				$checkRunning = $amazonLogModel->checkRunning($accountID, AmazonRequestReport::EVENT_NAME);
				if( !$checkRunning ){
					$amazonLogModel->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
					$msg = Yii::t('systems', 'There Exists An Active Event');
				}else{
					//插入本次log参数日志(用来记录请求的参数)
					$eventLog = $amazonLogModel->saveEventLog(AmazonOrder::EVENT_NAME, array(
							'log_id'        => $logID,
							'account_id'    => $accountID,
							'start_time'    => date('Y-m-d H:i:s'),
							'end_time'      => date('Y-m-d H:i:s'),
							'log_file'		=> $reportType
					));
					//设置日志为正在运行
					$amazonLogModel->setRunning($logID);

					//获取账号相关信息(商城ID)
					$amazonAccountModel = new AmazonAccount();
					$marketplaceId = '';
					$accountInfo = $amazonAccountModel->getAccountInfoById($accountID);
					if($accountInfo){
						$marketplaceId = $accountInfo['market_place_id'];
						$countryCode = $accountInfo['country_code'];
					}

					$amazonRequestReport = new AmazonRequestReport();
					$amazonRequestReport->setAccountID($accountID);//设置账号

					//MarketplaceIdList 请求参数不在日本和中国商城使用
					if(!empty($marketplaceId) && $countryCode != 'jp'){
						$amazonRequestReport->setMarketPlaceId($marketplaceId);//设置指定商城ID
					}

					$amazonRequestReport->setLogID($logID);//设置日志编号
					$flag = $amazonRequestReport->requestReport($reportType, $reportOptions);
					//4.更新日志信息
					if( $flag ){
						$amazonLogModel->setSuccess($logID);
						$amazonLogModel->saveEventStatus(AmazonOrder::EVENT_NAME, $eventLog, AmazonLog::STATUS_SUCCESS);
						$msg = "Request Success";
					}else{
						$amazonLogModel->setFailure($logID, $amazonRequestReport->getExceptionMessage());
						$amazonLogModel->saveEventStatus(AmazonOrder::EVENT_NAME, $eventLog, AmazonLog::STATUS_FAILURE);
						$msg = "Request Failure:".$amazonRequestReport->getExceptionMessage();
					}
				}
			}else{
				$msg = "Not Create LogID";
			}
		}else{
			$msg = "Invalid Account Id";
		}
		echo $msg;
	}
	/**
	 * 
	 * @desc 获取Amazon Listing报表列表
	 * @param unknown $accountID
	 * @param string $reportType
	 * @param string $getreportRequestID 接口报告请求ID	Liz|2016/3/25
	 * @throws Exception
	 * @return Ambigous <multitype:, string>|NULL
	 */
	private function _getListingReportList($accountID, $reportType = null, $getreportRequestID = null){
        ini_set("display_errors", false);
    	error_reporting(0);
		set_time_limit(3600);
		ini_set('memory_limit', '1024M');

		if($reportType == null)
			$reportType = RequestReportRequest::REPORT_TYPE_LISTINGS_DATA_CAMPAT;
		if ($accountID) {
			$amazonLogModel = new AmazonLog();
			$logID = $amazonLogModel->prepareLog($accountID, $reportType);
			try {
				if(!$logID) throw new Exception("Log create failure!!");
				if(!$amazonLogModel->checkRunning($accountID, $reportType)){
					$amazonLogModel->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
					throw new Exception(Yii::t('systems', 'There Exists An Active Event'));
				}
				$amazonLogModel->setRunning($logID);
				
				//如果参数有接口报告请求ID，则用此ID取请求记录
				if ($getreportRequestID){
					$amazonRequestReport = new AmazonRequestReport();
					$conditions = "report_request_id=:report_request_id";
					$params = array(':report_request_id' => $getreportRequestID);
					$reportList = $amazonRequestReport->getRequestReportList($conditions, $params, 1);		
					$reportRequestData = ($reportList) ? $reportList[0] : array();	
				}else{
					//取出报告请求里面最后一次提交的获取可售商品报告的请求
					$reportRequestData = UebModel::model('AmazonRequestReport')->getAccountLastRequest($accountID, $reportType);
				}

				if (!empty($reportRequestData)) {
					//_SUBMITTED_或是_IN_PROGRESS_状态时	Liz|2016/4/5
					if ($reportRequestData['report_processing_status'] == GetReportRequestListRequest::PROCESSING_STATUS_SUBMITTED || $reportRequestData['report_processing_status'] == GetReportRequestListRequest::PROCESSING_STATUS_PROCESSING) {
						$requestId = $reportRequestData['report_request_id'];
						//查看该报告是否已经生成
						$request = new GetReportRequestListRequest();
						$request->setReportRequestIdList($requestId);
						$requestListResponses  = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();

						if (!$request->getIfSuccess())
							throw new Exception($request->getErrorMsg());
						if (isset($requestListResponses['requestList'])) {
							$reportListResponse = $requestListResponses['requestList'][0];
							$reportRequestId = $reportListResponse['reportRequestId'];	//报告请求ID
							$reportRequestStatus = $reportListResponse['reportProcessingStatus'];
							$reportId = 0;
							$reportRequest = UebModel::model('AmazonRequestReport')->updateAll(array(
									'report_processing_status' => $reportRequestStatus), "id=:id", array(":id"=>$reportRequestData['id']));

							if ($reportRequestStatus != GetReportRequestListRequest::PROCESSING_STATUS_DONE) {			

								//如果是获取分类树报告
								if ($reportType == RequestReportRequest::REPORT_TYPE_BROWSE_TREE_DATA){
									$amazonLogModel->setFinish($logID);
									return AmazonCategory::FOR_CONTINUE_FLAG;
								}else{					
									throw new Exception(Yii::t('amazon', 'Report Request Have Not Completed'));
								}		
											
							}
							$reportId = $reportListResponse['generatedReportId'];
							$reportRequest = new GetReportRequest();
							$reportRequest->setReportId($reportId);
							$reportRequest->setReturnDataType($reportType);		//设置数据返回模式(XML)	Liz|2016/3/18
							$listData = $reportRequest->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
							$amazonLogModel->setSuccess($logID);
							return $listData;
						}else{
							$msg = 'No Report Return !';
						}
					}else{						
						//如果是获取分类树报告
						if ($reportType == RequestReportRequest::REPORT_TYPE_BROWSE_TREE_DATA){
							$amazonLogModel->setFinish($logID);
							return AmazonCategory::FOR_CONTINUE_FLAG;
						}else{					
							$msg = 'No _SUBMITTED_ Status Request Report!';
						}						
					}
					throw new Exception($msg);
				}else{
					throw new Exception('No Request Report!');
				}
			} catch (Exception $e) {
				$amazonLogModel->setFailure($logID, $e->getMessage());
				echo $e->getMessage();
			}
		}else{ 
			echo "No Valide Account Id";
		}
		return null;
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
		set_time_limit(5*3600);
		ini_set("display_errors", true);
		$allowWarehouse =  WarehouseSkuMap::WARE_HOUSE_GM;
		$conditions = "1=0";
		$params = array();
		$limit = 500;
		$offset = 0;
		$wareSkuMapModel = new WarehouseSkuMap();
		$amazonListModel = new AmazonList();
		$amazonZeroStockSKUModel = new AmazonZeroStockSku();
	
		//-- start 2016-02-01 增加type --
		$type = Yii::app()->request->getParam("type");
		if(empty($type)) $type = 4;
		$availableQty = 5;
		switch ($type){
			case 0://默认，库存《=1库存清零
			case 4://从导入的数据库中进行
				$conditions = "t.available_qty <= {$availableQty} AND t.warehouse_id in(".$allowWarehouse.") AND p.product_status<7"; //lihy modify 2016-02-14
				$SkuModel = new WarehouseSkuMap();
				$method = "getSkuListLeftJoinProductByCondition";
				$select = "t.sku";
				break;
			case 1: //滞销、待清除
				$productStatus = Product::STATUS_HAS_UNSALABLE . "," . Product::STATUS_WAIT_CLEARANCE;
				$conditions = "product_status in(". $productStatus .") and product_is_multi in (0, 1)";
				$SkuModel = new Product();
				$method = "getProductListByCondition";
				$select = "sku";
				break;
			case 2: //欠货待处理
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
				$conditions = "amazon_status=0";
				$params = array();
				$select = "sku";
				break;
			default:
				exit('type is incorrect');
		}
		//-- end 2016-02-01 增加type --
		$apiRequestNum = 0;
		do{
			//1、循环取出<=1的sku列表，每次100个
			//2、取出上述sku对应的产品库中的信息
			//3、提交到ebay平台，实现在线库存修改
			if(!$testFlag){
				$limits = "{$offset}, {$limit}";
				$skuList = $SkuModel->$method($conditions, $params, $limits, $select);
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
				if($type == 5){//2016-02-03 add
					foreach ($skuList as $sku){
						$skus[] = $sku['sku'];
					}
					unset($skuList);
					$amazonAsinImport = new AmazonAsinImport;
					$andAccountWhere = "";
					if($testFlag){
						echo "set testaccount_id=".$testAccountID . "<br/>";
						$andAccountWhere = "account_id=".$testAccountID;
					}
					$skuList = $amazonAsinImport->getSkuListByCondition("sku in (" . MHelper::simplode($skus) . ")" . (empty($andAccountWhere) ? '' : " AND ".$andAccountWhere ), array(), null, "*");
					//$this->print_r($skuList);
					foreach ($skuList as $key=>$sku){
						$skuList[$key] = array(
								'sku'				=>	$sku['sku'],
								'account_id'		=>	$sku['account_id'],
								'seller_sku'		=>	$sku['sku_encrypt'],
								'product_stock'		=>	0,
								'amazon_listing_id'	=>	'ASIN-'.$sku['asin']
						);
					}
					$variantListing = $skuList;
					unset($skuList);
				}else{
					foreach ($skuList as $sku){
						$skus[] = $sku['sku'];
					}
					unset($skuList);
						
					$command = $amazonListModel->getDbConnection()->createCommand()
					->from($amazonListModel->tableName() )
					->select("sku, account_id, seller_sku, quantity as product_stock, amazon_listing_id")
					->where(array("IN", "sku", $skus))
					->andWhere("quantity>0")
					->andWhere("seller_status=". AmazonList::SELLER_STATUS_ONLINE) // lihy add 2016-02-14
					->andWhere("fulfillment_type=".AmazonList::FULFILLMENT_STATUS_MERCHANT);
					if($testFlag){
						echo "set testaccount_id=".$testAccountID . "<br/>";
						$command->andWhere("account_id=".$testAccountID);
					}
						
					$variantListing = $command->queryAll();
				}
				//查找出对应的父级sku信息，聚合同属一个产品的信息
				$listing = array();
				$updateSKUS = $skus;//2016-02-03 add
				if($variantListing){
					foreach ($variantListing as $variant){
						//检测当天是否已经运行了，此表，加此判断之后数据量会降很多，并且会定时进行清理记录数据 lihy add 2016-02-14
						/* if($amazonZeroStockSKUModel->checkHadRunningForDay($variant['seller_sku'], $variant['account_id'])){
						 continue;
						} */
						$listing[$variant['account_id']][] = $variant;
	
						/* if(!isset($updateSKUS[$variant['sku']]))//2016-02-03 add
						 $updateSKUS[$variant['sku']] = $variant['sku']; */
					}
				}
	
					
				if($listing){
					$eventName = AmazonZeroStockSku::EVENT_ZERO_STOCK;
					foreach ($listing as $accountID=>$lists){
						$time = date("Y-m-d H:i:s");
						//写log
						$logModel = new AmazonLog();
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
	
						//@TODO
						$sellerSku = array();
						foreach ($lists as $product){
							$sellerSku[] = array('sku'=>$product['seller_sku'], 'quantity'=>0);
						}
						// amazon测试和上线时开启
						$submitFeedId = $amazonListModel->amazonProductOffline($accountID, $sellerSku);
						//日志完成
						$eventLogID = $logModel->saveEventLog($eventName, array(
								'log_id'	=>	$logID,
								'account_id'=>	$accountID,
								'start_time'=>	$startTime,
								'end_time'	=>	date("Y-m-d H:i:s")) );
						$msg = '';
						if($submitFeedId){
							$status = 2;//已提交
							$logModel->setSuccess($logID);
							$logModel->saveEventStatus($eventName, $eventLogID, AmazonLog::STATUS_SUCCESS);
						}else{
							$status = 3;//失败
							$msg = $amazonListModel->getErrorMsg();
							$logModel->setFailure($logID, $msg);
							$logModel->saveEventStatus($eventName, $eventLogID, AmazonLog::STATUS_FAILURE);
						}
						foreach ($lists as $variant){
							$addData = array(
									'product_id'=>	$variant['amazon_listing_id'],
									'seller_sku'=>	$variant['seller_sku'],
									'sku'		=>	$variant['sku'],
									'account_id'=>	$accountID,
									'site_id'	=>	0,
									'old_quantity'=>$variant['product_stock'],
									'status'	=>	$status,
									'msg'		=>	$msg,
									'create_time'=>	$time,
									'type'		=>	$type, // 2016-02-01
									'request_id' => $submitFeedId
							);
							$amazonZeroStockSKUModel->saveData($addData);
						}
						sleep(2);//休眠2s
					}
					//2016-02-03 add
					//如果为手动导入的则还需要更新
					if($type == 5 && $updateSKUS){
						ProductImportSku::model()->updateDataByCondition("amazon_status=0 AND sku in(". MHelper::simplode($updateSKUS) .")", array('amazon_status'=>1));
					}
					unset($listing, $lists);
				}else{
					echo("no match sku ");
				}
			}else{
				$flag = false;
				exit('not found stock less than 0');
			}
			if($apiRequestNum > 8)
				sleep(120);//休眠2min
			$apiRequestNum++;
		}while ($flag);
	}
	

		
	// =================== S:2016-08-17 =========================
	
	/**
	 * @desc 自动更改库存为0（本地仓）
	 * @date 2016-08-17
	 * @link /amazon/amazonlisting/changestockzerofromskuwarehouse/account_id/x/sku/x
	 */
	public function actionChangestockzerofromskuwarehouse() {
		set_time_limit ( 2*3600 );
		ini_set ( 'display_errors', true );
		error_reporting ( E_ALL );
		$accountID = Yii::app ()->request->getParam ( 'account_id' );
		$testSKU = Yii::app ()->request->getParam ( 'sku' );

		$allowAccountID = array();
		$notAllowAccountID = array();
		$testSKUArr = array ();

		//春节规则(规则一：1.20号前；规则二：1.20号(含)-2.6号之前，2.8号临时调整到2.15之前)
		// 1、规则一：可用+在途 <= 日销量（没有日销量则用<1）, 调为0，不能调0的改为1，保持原来的业务逻辑
		// 2、规则二：可用库存 <= 日销量（没有日销量则用<1）, 调0，不能调0的改为1，保持原来的业务逻辑
		// 3、春节规则排除待清仓和停售状态产品
		// 4、2月6号暂停此规则，同时启用原来规则
		// 5、亚马逊参于春节规则的账号：共排除d-uk/d-fr/d-de/r-in/ecoolbuy-us/ecoolbuy-ca/chinatera-ca/mmdex-ca/a-fr/a-de/a-uk十一个账号(都不参与平时或是春节的调0操作)，所有账号（长沙和深圳）都参与春节规则，平时规则参与的账号按默认原来的不变。
		// 
		$specialRule = 0;
		$myDate = date('Y-m-d',time());
		// if($myDate < '2017-01-20') $specialRule = 1;
		// if($myDate >= '2017-01-20' && $myDate < '2017-02-06') $specialRule = 2;
		if($myDate < '2017-02-15') $specialRule = 2;

		if ($testSKU) {
			$testSKUArr = explode ( ",", $testSKU );
		}
		if ($accountID) {
			$time = date ( "Y-m-d H:i:s" );
			// 写log
			$logModel = new AmazonLog ();
			$eventName = AmazonZeroStockSku::EVENT_ZERO_STOCK;
			$logID = $logModel->prepareLog ( $accountID, $eventName );
			if (! $logID) {
				exit ( 'Create Log Failure' );
			}
			// 检测是否可以允许
			if (! $logModel->checkRunning ( $accountID, $eventName )) {
				$logModel->setFailure ( $logID, Yii::t ( 'system', 'There Exists An Active Event' ) );
				exit ( 'There Exists An Active Event' );
			}
			$startTime = date ( "Y-m-d H:i:s" );
			// 设置运行
			$logModel->setRunning ( $logID );
			// @todo
			// 1、获取对应的置为0的sku列表
			// 2、寻找对应sku的可用库存数量
			$amazonListModel = new AmazonList ();
			$amazonZeroStockSKUModel = new AmazonZeroStockSku ();
			$limit = 2000;
			if($accountID == 10) $limit = 15000;	//listing数量过多，造成上传跟踪号等上传的连接数超限，因此增加批量个数，节省连接数

			$offset = 0;
			$allowWarehouse = WarehouseSkuMap::WARE_HOUSE_GM;
			$availableQty = 1; // 可用库存数量由 <=5修改为<=1 
			$quantity = 0;	//设置的库存
			$warehouseTablename           = "ueb_warehouse." . WarehouseSkuMap::model ()->tableName ();
			$productTablename             = "ueb_product." . Product::model ()->tableName ();
			$productInfringementTablename = "ueb_product." . ProductInfringe::model ()->tableName ();
			if ($specialRule > 0){
				$skuSalesTablename = "ueb_sync.ueb_sku_sales";	//SKU日销量数据
			}
			
			if ($testSKUArr) {
				echo "<pre>";
				echo "test SKU:{$testSKU}<br/>";
			}
			$failureSKU = array ();
			$successSKU = array ();
			do {
				$command = $amazonListModel->getDbConnection ()->createCommand ()
									->from ( $amazonListModel->tableName () )
									->select ( "sku, account_id, seller_sku, quantity as product_stock, amazon_listing_id" )
									->where ( "account_id='{$accountID}'" )
									->andWhere ( "quantity>0" )
									->andWhere ( "seller_status=" . AmazonList::SELLER_STATUS_ONLINE )
									->andWhere ( "fulfillment_type=" . AmazonList::FULFILLMENT_STATUS_MERCHANT )
									->andWhere ( "warehouse_id = 41 " )
									->limit ( $limit, $offset );
				if ($testSKUArr) {
					$command->andWhere ( array ("IN", "sku", $testSKUArr));
				}
				$skuList = $command->queryAll ();
				$offset += $limit;
				if ($testSKUArr) {
					echo "skuList：<br/>";
					print_r ( $skuList );
				}
				if ($skuList) {
					$skus = array ();
					foreach ( $skuList as $sku ) {
						if(!empty($sku ['sku'])) $skus [] = $sku ['sku'];
					}
										
					//春节规则条件调库存为0
					if ($specialRule == 1){
						$conditions = "(t.available_qty + t.transit_qty) <= IFNULL(s.day_sale_num,0) AND p.product_status not in (6,7) "; //可用库存+在途库存<1
					}elseif($specialRule == 2){
						$conditions = "t.available_qty <= IFNULL(s.day_sale_num,0) AND p.product_status not in (6,7) "; //可用库存<1
					}else{
						$conditions = "t.available_qty <= {$availableQty} ";
					}

					$conditions .= " AND t.warehouse_id in(" . $allowWarehouse . ")  "; // lihy modify 2016-02-14
					//$conditions .= " AND p.product_status < 7 ";
					$skuModel = new WarehouseSkuMap ();

					if ($specialRule > 0){
						$vaildSKUList = $skuModel->getDbConnection ()->createCommand ()
												->select ( "t.sku" )
												->from ( $warehouseTablename . " as t" )
												->leftJoin ( $skuSalesTablename . " as s", "s.sku = t.sku" )
												->leftJoin ( $productTablename . " as p", "p.sku = s.sku" )
												->where ( array ("IN", "t.sku", $skus) )
												->andWhere ( $conditions )
												->queryColumn ();
					}else{
						$vaildSKUList = $skuModel->getDbConnection ()->createCommand ()
												->select ( "t.sku" )
												->from ( $warehouseTablename . " as t" )
												->where ( array ("IN", "t.sku", $skus) )
												->andWhere ( $conditions )
												->queryColumn ();
					}
					if ($testSKUArr) {
						echo "vaildSKUList：<br/>";
						print_r ( $vaildSKUList );
						echo "specialRule:".$specialRule."<br />";
						echo "conditions:".$conditions."<br />";						
					}

					if (! empty ( $vaildSKUList )) {
						$sellerSku = array ();
						$updateSellerSku = array();
						foreach ( $skuList as $sku ) {
							if (! in_array ( $sku ['sku'], $vaildSKUList )) {
								continue; // in_array()函数在大数组上效率可能会有点慢，后面再改一下
							}
							$sellerSku [] = array (
									'sku' => $sku ['seller_sku'],
									'quantity' => $quantity 
							);
							$updateSellerSku [] = $sku['seller_sku'];
						}
						if ($testSKUArr) {
							echo "sellerSKU:<br/>";
							print_r ( $sellerSku );
						}
						// amazon测试和上线时开启
						$submitFeedId = false;
				
						$submitFeedId = $amazonListModel->amazonProductOffline ( $accountID, $sellerSku );
						$msg = "";
						if($submitFeedId){
							$status = 2;//已提交							
							//同步更新本地
							$updateconditions = "account_id='{$accountID}' AND seller_sku IN(".MHelper::simplode($updateSellerSku).")";
							$amazonListModel->updateAmazonProduct($updateconditions, array('quantity'=>$quantity, 'seller_status'=>AmazonList::SELLER_STATUS_OFFLINE));
							
						}else{
							$status = 3;//失败
							$msg = $amazonListModel->getErrorMsg();
						}
						foreach ($skuList as $variant){
							if (! in_array ( $variant ['sku'], $vaildSKUList )) {
								continue; // in_array()函数在大数组上效率可能会有点慢，后面再改一下
							}
							$addData = array(
									'product_id'=>	$variant['amazon_listing_id'],
									'seller_sku'=>	$variant['seller_sku'],
									'sku'		=>	$variant['sku'],
									'account_id'=>	$accountID,
									'site_id'	=>	0,
									'old_quantity'=>$variant['product_stock'],
									'status'	=>	$status,
									'msg'		=>	$msg,
									'create_time'=>	$time,
									'type'		=>	($specialRule > 0) ? 6 : 0, // 2017-01-18 类型：6-2月6日库存0
									'request_id' => $submitFeedId
							);
							$amazonZeroStockSKUModel->saveData($addData);
						}
						sleep(2);//休眠2s
					}
				}
			} while ( $skuList );
			$logModel->setSuccess ( $logID, 'done' );
			echo "done";
		} else {
			// 循环每个账号发送一个拉listing的请求
			$accountList = AmazonAccount::model ()->getIdNamePairs();

			//春节规则
			if ($specialRule > 0){
				$notAllowAccountID = array('46','47','48','80','13','30','64','65','74','75','77');
			}else{
				// $allowAccountID = array('45','49','51','61','67','80');
				$allowAccountID = array('80');
			}
			
			foreach ( $accountList as $accountID => $accountName ) {

				//春节规则
				if ($specialRule > 0){
					if(in_array($accountID, $notAllowAccountID)) continue;
				}else{
					if(!in_array($accountID, $allowAccountID)) continue;
				}				
				
				MHelper::runThreadSOCKET ( '/' . $this->route . '/account_id/' . $accountID . '/sku/' . $testSKU );
				sleep ( 1 );
			}
		}
	}
	
	
	/**
	 * 自动恢复（本地仓）
	 * @date 2016-08-17
	 * @link /amazon/amazonlisting/restoreskustockfromskuwarehouse
	 */
	public function actionRestoreskustockfromskuwarehouse() {
		set_time_limit (2*3600);
		ini_set('display_errors', true);
		error_reporting(E_ALL);
		$accountID = Yii::app ()->request->getParam ( 'account_id' );
		$type = Yii::app ()->request->getParam ( 'type' );
		$testSKU = Yii::app ()->request->getParam ( 'sku' );
		$notAllowAccountID = array();
		$allowAccountID = array();
		$testSKUArr = array ();
		if ($testSKU) {
			$testSKUArr = explode ( ",", $testSKU );
		}

		// 库存自动恢复春节规则(规则：2.6号之前，2.8号临时调整到2.15之前)
		// 可用库存 >= 10（如果日销量 > 10 则用日销量）
		$specialRule = 0;
		$myDate = date('Y-m-d',time());
		if($myDate < '2017-02-15') $specialRule = 1;

		if ($accountID) {
			$time = date ( "Y-m-d H:i:s" );
			// 写log
			$logModel = new AmazonLog ();
			$eventName = AmazonZeroStockSku::EVENT_RESTORE_STOCK;
			$logID = $logModel->prepareLog ( $accountID, $eventName );
			if (! $logID) {
				exit ( 'Create Log Failure' );
			}
			// 检测是否可以允许
			if (! $logModel->checkRunning ( $accountID, $eventName )) {
				$logModel->setFailure ( $logID, Yii::t ( 'system', 'There Exists An Active Event' ) );
				exit ( 'There Exists An Active Event' );
			}
			$startTime = date ( "Y-m-d H:i:s" );
			// 设置运行
			$logModel->setRunning ( $logID );
			// @todo
			// 1、获取对应的置为0的sku列表
			// 2、寻找对应sku的可用库存数量
			$amazonListModel = new AmazonList ();
			$amazonZeroStockSKUModel = new AmazonZeroStockSku ();
			$limit = 2000;
			if($accountID == 10) $limit = 10000;	//listing数量过多，造成上传跟踪号等上传的连接数超限，因此增加批量个数，节省连接数

			$offset = 0;
			$quantity = 99; // 恢复库存数量
			$allowWarehouse = WarehouseSkuMap::WARE_HOUSE_GM;
			$availableQty = 2; // 可用库存数量由>=10修改为>=2
			$warehouseTablename = "ueb_warehouse." . WarehouseSkuMap::model ()->tableName ();
			$productTablename = "ueb_product." . Product::model ()->tableName ();
			$productInfringementTablename = "ueb_product." . ProductInfringe::model ()->tableName ();

			if ($specialRule > 0){
				$skuSalesTablename = "ueb_sync.ueb_sku_sales";	//SKU日销量数据
			}
			
			if ($testSKUArr) {
				echo "<pre>";
				echo "test SKU:{$testSKU}<br/>";
			}
			$failureSKU = array ();
			$successSKU = array ();
			do {
				$command = $amazonListModel->getDbConnection ()->createCommand ()
											->from ( $amazonListModel->tableName () )
											->select ( "sku, account_id, seller_sku, quantity as product_stock, amazon_listing_id" )
											->where ( "account_id='{$accountID}'" )->andWhere ( "quantity=0" )
											->andWhere ( "seller_status=" . AmazonList::SELLER_STATUS_OFFLINE )
											->andWhere ( "fulfillment_type=" . AmazonList::FULFILLMENT_STATUS_MERCHANT )
											->andWhere ( "warehouse_id = 41 " )
											->limit ( $limit, $offset );
				if ($testSKUArr) {
					$command->andWhere ( array (
							"IN",
							"sku",
							$testSKUArr 
					) );
				}
				$skuList = $command->queryAll ();
				$offset += $limit;
				if ($testSKUArr) {
					echo "skuList：<br/>";
					print_r ( $skuList );
				}
				if ($skuList) {
					// @todo 获取库存
					$skus = array ();
					foreach ( $skuList as $sku ) {
						if(!empty($sku ['sku'])) $skus [] = $sku ['sku'];
					}
					$skuModel = new WarehouseSkuMap ();
					
					//春节规则
					if ($specialRule > 0){
						$conditions = "t.available_qty >= IFNULL(s.day_sale_num,10) AND t.warehouse_id in(" . $allowWarehouse . ") 
										AND p.product_status in(" . Product::STATUS_ON_SALE . ") 
										AND (pi.security_level='A' OR pi.infringement=1 OR ISNULL(pi.sku))"; // lihy modify 2016-02-14   2016 11 17				
						$vaildSKUList = $skuModel->getDbConnection ()->createCommand ()->select ( "t.sku" )
						->from ( $warehouseTablename . " as t" )
						->leftJoin ( $productTablename . " as p", "p.sku=t.sku" )
						->leftJoin ( $productInfringementTablename . " as pi", "pi.sku=t.sku" )
						->leftJoin ( $skuSalesTablename . " as s", "s.sku=t.sku AND s.day_sale_num > 10 " )
						->where ( array (
								"IN",
								"t.sku",
								$skus 
						) )->andWhere ( $conditions )->queryColumn ();
					}else{
						$conditions = "t.available_qty >= {$availableQty} AND t.warehouse_id in(" . $allowWarehouse . ") 
										AND p.product_status in(" . Product::STATUS_ON_SALE . ") 
										AND (pi.security_level='A' OR pi.infringement=1 OR ISNULL(pi.sku))"; // lihy modify 2016-02-14   2016 11 17				
						$vaildSKUList = $skuModel->getDbConnection ()->createCommand ()->select ( "t.sku" )
						->from ( $warehouseTablename . " as t" )
						->leftJoin ( $productTablename . " as p", "p.sku=t.sku" )
						->leftJoin ( $productInfringementTablename . " as pi", "pi.sku=t.sku" )
						->where ( array (
								"IN",
								"t.sku",
								$skus 
						) )->andWhere ( $conditions )->queryColumn ();						
					}

					if ($testSKUArr) {
						echo "vaildSKUList：<br/>";
						print_r ( $vaildSKUList );
						echo "specialRule:".$specialRule."<br />";
						echo "conditions:".$conditions."<br />";						
					}

					if (! empty ( $vaildSKUList )) {
						$sellerSku = array ();
						$updateSellerSku = array();
						foreach ( $skuList as $sku ) {
							if (! in_array ( $sku ['sku'], $vaildSKUList )) {
								continue; // in_array()函数在大数组上效率可能会有点慢，后面再改一下
							}
							$sellerSku [] = array (
									'sku' => $sku ['seller_sku'],
									'quantity' => $quantity 
							);
							$updateSellerSku[] = $sku['seller_sku'];
						}
						if ($testSKUArr) {
							echo "sellerSKU:<br/>";
							print_r ( $sellerSku );
						}
						// amazon测试和上线时开启
						$submitFeedId = $amazonListModel->amazonProductOffline ( $accountID, $sellerSku );
						$msg = "";
						if ($submitFeedId) {
							$status = 2;//已提交		
							//同步更新本地
							$updateconditions = "account_id='{$accountID}' AND seller_sku IN(".MHelper::simplode($updateSellerSku).")";
							$amazonListModel->updateAmazonProduct($updateconditions, array('quantity'=>$quantity, 'seller_status'=>AmazonList::SELLER_STATUS_ONLINE));
							$successSKU [] = $sellerSku;
						} else {
							$msg = $amazonListModel->getErrorMsg();
							$status = 3;
							$failureSKU [] = $sellerSku;
						}
						
						foreach ($skuList as $variant){
							if (! in_array ( $variant ['sku'], $vaildSKUList )) {
								continue; // in_array()函数在大数组上效率可能会有点慢，后面再改一下
							}
							$addData = array(
									'product_id'=>	$variant['amazon_listing_id'],
									'seller_sku'=>	$variant['seller_sku'],
									'sku'		=>	$variant['sku'],
									'account_id'=>	$accountID,
									'site_id'	=>	0,
									'old_quantity'=>$quantity,
									'status'	=>	$status,
									'msg'		=>	$msg,
									'create_time'=>	$time,
									'type'		=>	8, // 2016-02-01
									'request_id' => $submitFeedId
							);
							$amazonZeroStockSKUModel->saveData($addData);
						}
					}
				}
			} while ( $skuList );
			$logModel->setSuccess ( $logID, 'done' );
			if ($testSKU) {
				echo "failure:<br/>";
				print_r ( $failureSKU );
				
				echo "success:<br/>";
				print_r ( $successSKU );
			}
			echo "done";
		} else {
			// 循环每个账号发送一个拉listing的请求
			$accountList = AmazonAccount::model ()->getIdNamePairs();

			//春节规则
			if ($specialRule > 0){
				$notAllowAccountID = array('46','47','48','80','13','30','64','65','74','75','77');
			}else{
				//$allowAccountID = array('45','49','51','61','67','80');
				$allowAccountID = array('80');
			}

			foreach ( $accountList as $accountID => $accountName ) {

				//春节规则
				if ($specialRule > 0){
					if(in_array($accountID, $notAllowAccountID)) continue;
				}else{
					if(!in_array($accountID, $allowAccountID)) continue;
				}

				MHelper::runThreadSOCKET ( '/' . $this->route . '/account_id/' . $accountID . '/sku/' . $testSKU );
				sleep ( 1 );
			}
		}
	}
	// =================== E:2016-08-17 =========================
	
	/**
	 * @desc 获取feed请求报告
	 */
	public function actionGetstockfeed(){
		$reportType = "";
		$accountID = Yii::app()->request->getParam("account_id");
		$type = SubmitFeedRequest::FEEDTYPE_POST_INVENTORY_AVAILABILITY_DATA;
		if($accountID){
			$conditions = "report_type=:report_type and account_id=:account_id and report_processing_status=:report_processing_status";
			$params = array(
						':report_type'	=>	$type,
						':account_id'	=>	$accountID,
						':report_processing_status'	=>	SubmitFeedRequest::FEED_STATUS_SUBMITTED
			);
			//@TODO 获取方案，未确定，先单独获取
			$requestReport = new AmazonRequestReport();
			$result = $requestReport->getDbConnection()->createCommand()
											->from($requestReport->tableName())
											->where($conditions, $params)
											->limit(5)
											->queryAll();
			$amazonZeroStockModel = AmazonZeroStockSku::model();
			if($result){
				foreach ($result as $report){
					$amazonListModel = new AmazonList();
					$response = $amazonListModel->getFeedSubmissionResult($accountID, $feedSubmissionID);
					if(isset($response->Message->ProcessingReport->ProcessingSummary)){
						//
						$processingReport = $response->Message->ProcessingReport;
						$statusCode = $processingReport->StatusCode;
						if($statusCode == 'Complete'){
							$processingSummary = $processingReport->ProcessingSummary;
							//标志已取
							$requestReport->getDbConnection()->createCommand()
							->update($requestReport->tableName(), array('scheduled'=>2, 'report_processing_status'=>SubmitFeedRequest::FEED_STATUS_DONE), "report_request_id='{$report['report_request_id']}'");
							if($processingSummary->MessagesProcessed == $processingSummary->MessagesSuccessful){
								//更新zerostock
								$amazonZeroStockModel->getDbConnection()->createCommand()
													->update($amazonZeroStockModel->tableName(), 
															array('status'=>2), 
															"acount_id={$accountID} and request_id='{$report['report_request_id']}'");
							}else{
								//失败处理
								//取出对应的sku
								$amazonZeroStockModel->getDbConnection()->createCommand()
								->update($amazonZeroStockModel->tableName(),
										array('status'=>3),
										"acount_id={$accountID} and request_id='{$report['report_request_id']}'");
							}
						}
						
						//
					}
				}
			}
			
		}else{
			//循环每个账号发送一个拉listing的请求
			$accountList = AmazonAccount::model()->getIdNamePairs();
			foreach($accountList as $accountID=>$accountName){
				MHelper::runThreadSOCKET('/'.$this->route.'/account_id/' . $accountID);
				sleep(1);
			}
		}
	}	

	public function actionTest() {
		set_time_limit(3600);
		ini_set('display_errors', true);
		error_reporting(E_ALL);		
		$accountID = Yii::app()->request->getParam('account_id');

		$productFieldChangeStatisticsModel = new ProductFieldChangeStatistics();
		$ret = $productFieldChangeStatisticsModel->getListByCondition('*');
		MHelper::printvar($ret);

		// AmazonLog::model()->getDbConnection()->createCommand()->update(AmazonLog::model()->tableName(), array('status' => '4'), "event = 'auto_shelf_products' AND status = 1");
		// AmazonLog::model()->getDbConnection()->createCommand()->update(AmazonLog::model()->tableName(), array('status' => '4'), "id = 852756");
		Yii::app()->end('OK');
/*
		// $getFeedSubmissionListRequest = new GetFeedSubmissionListRequest;
		$getFeedSubmissionListRequest = new GetCommonFeedSubmissionListRequest;
		
		$getFeedSubmissionListRequest->setAccount('43');
		$feedSubmissionID = array('57050017070','56963017068','56962017068','56961017068','56952017068');
		$getFeedSubmissionListRequest->setFeedSubmissionId($feedSubmissionID);
		$response = $getFeedSubmissionListRequest->setRequest()->sendRequest()->getResponse();
		$this->print_r($response);
		echo 'aaaaa';exit;*/

		/* $request = new GetReportRequest();
		$request->setReportId('62112248016640');
		$response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
		var_dump($response); */
		
		/* $feedSubmissionID = Yii::app()->request->getParam('feed_id');
		
		$getFeedSubmissionListRequest = new GetFeedSubmissionListRequest;
		
		$getFeedSubmissionListRequest->setAccount($accountID);
		$getFeedSubmissionListRequest->setFeedSubmissionId($feedSubmissionID);
		$response = $getFeedSubmissionListRequest->setRequest()->sendRequest()->getResponse();
		$this->print_r($response);
		
		echo "ttttttttttt<br/>";
		$amazonListModel = new AmazonList();
		$response = $amazonListModel->getFeedSubmissionResult($accountID, $feedSubmissionID);
		$this->print_r($response); */
		
		
		
		/* $sellerSku = array();
		
		$sellerSku[] = array('sku'=>'7pn8ny5qc2rq4-us01', 'quantity'=>0);
		$amazonListModel = new AmazonList();
		// amazon测试和上线时开启
		$submitFeedId = $amazonListModel->amazonProductOffline($accountID, $sellerSku); */
		
		
		/* $getMatchingProductForIdRequest = new GetMatchingProductForIdRequest;
		$getMatchingProductForIdRequest->setAccount($accountID);
		$getMatchingProductForIdRequest->setIdList(array('B013Y705WS','B00L31OZM4'));
		$response = $getMatchingProductForIdRequest->setRequest()->sendRequest()->getResponse();
		var_dump($response); */
	}


	/**
	 * @desc amazon所有停售产品，添加到下架表
     * @link /amazon/amazonlisting/addofflinesku/accountID/1
	 */
	public function actionAddofflinesku() {
        set_time_limit(5*3600);
        ini_set('display_errors', true);
        error_reporting(E_ALL);

		$warehouseSkuMapModel   = new WarehouseSkuMap();
		$logModel               = new AmazonLog();
		$amazonListModel        = new AmazonList();
		$amazonStoppedSellingSkuModel = new AmazonStoppedSellingSku();

        //账号
        $accountID = Yii::app()->request->getParam('accountID');

        //指定某个特定sku----用于测试
        $setSku = Yii::app()->request->getParam('sku');

        $select    = 't.sku';
        $eventName = 'addofflinesku';
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

                //删除以前的数据
                $amazonStoppedSellingSkuModel->getDbConnection()->createCommand()->delete($amazonStoppedSellingSkuModel->tableName(), 'account_id = '.$accountID);

                do{
                    $command = $amazonListModel->getDbConnection()->createCommand()
                        ->from($amazonListModel->tableName())
                        ->select("id,sku,amazon_listing_id,quantity,seller_sku,account_id")
                        ->where('account_id = '.$accountID.' AND seller_status = 1');
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

                        $insertParam = array(
							'account_id'        => $accountID,
							'amazon_listing_id' => $variant['amazon_listing_id'],
							'sku'               => $variant['sku'],
							'seller_sku'        => $variant['seller_sku'],
							'create_time'       => date('Y-m-d H:i:s')
                        );

                        $amazonStoppedSellingSkuModel->getDbConnection()->createCommand()->insert($amazonStoppedSellingSkuModel->tableName(), $insertParam);
                        
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
            $accountList = AmazonAccount::model()->getIdNamePairs();
            foreach($accountList as $key => $value){
                MHelper::runThreadSOCKET('/'.$this->route.'/accountID/' . $key);
                sleep(1);
            }
        }
    }


	/**
     * @desc amazon所有停售产品，在线listing直接下架
     * @link /amazon/amazonlisting/autoshelfproducts/accountID/1
     */
    public function actionAutoshelfproducts() {
        set_time_limit(300);
        ini_set('display_errors', true);
        error_reporting(E_ALL);

		$logModel                     = new AmazonLog();
		$amazonLogOfflineModel        = new AmazonLogOffline();
		$amazonStoppedSellingSkuModel = new AmazonStoppedSellingSku();
		$amazonListModel              = new AmazonList();

        //账号
        $accountID = Yii::app()->request->getParam('accountID');

        //指定某个特定sku----用于测试
        $setSku = Yii::app()->request->getParam('sku');

        $select    = 't.sku';
        $eventName = 'auto_shelf_products';
        $limit     = 10;

        if($accountID){
        	//屏蔽a-fr a-de a-uk  3个海外仓账号
        	if(in_array($accountID, array(74,75,77))){
        		exit('屏蔽此账号');
        	}
            try{
                //写log
                $logID = $logModel->prepareLog($accountID, $eventName);
                if(!$logID){
                    exit('日志写入错误');
                }
                //检测是否可以允许
                if(!$logModel->checkRunning($accountID, $eventName)){
                    // $logModel->setFailure($logID, Yii::t('system', 'There Exists An Active Event'));
                    // exit('There Exists An Active Event');
                    throw new Exception("There Exists An Active Event"); 
                }

                //设置运行
                $logModel->setRunning($logID);

                $command = $amazonStoppedSellingSkuModel->getDbConnection()->createCommand()
                    ->from($amazonStoppedSellingSkuModel->tableName()." as t")
                    ->leftJoin($amazonListModel->tableName()." as p", "p.amazon_listing_id=t.amazon_listing_id AND p.account_id = t.account_id")
                    ->select("t.id,t.seller_sku,t.account_id,p.id as listing_id,p.quantity,t.sku")
                    ->where('t.account_id = '.$accountID.' AND t.is_offline = 0');
                    if($setSku){
                        $command->andWhere("t.sku = '".$setSku."'");
                    }
                    $command->limit($limit);
                $variantListing = $command->queryAll(); 
                if(!$variantListing){
                    // exit("此账号无数据");
                    throw new Exception("No Data");                    
                }


                foreach ($variantListing as $variant){

                    $itemData   = array();
					$time       = date("Y-m-d H:i:s");
					$itemData[] = array('sku'=>$variant['seller_sku'], 'quantity'=>0);

                    $flag = $amazonListModel->amazonProductOffline($accountID, $itemData);
                    if ($flag) {
                    	$status  = 1;//成功
                        $message = 'SUCCESS';
                    	$amazonListModel->updateAmazonProductByPks($variant['listing_id'], array('seller_status'=>2));   

                    	$updateData = array('is_offline'=>1, 'create_time'=>date('Y-m-d H:i:s'));
                    	$amazonStoppedSellingSkuModel->update($updateData, $variant['id']);  

                    	$addData = array(
	                        'listing_id'        => $variant['listing_id'],
	                        'sku'               => $variant['sku'],
	                        'account_id'        => $variant['account_id'],
	                        'event'             => 'autoshelfproducts',
	                        'status'            => $status,
	                        'inventory'         => $variant['quantity'],                            
	                        'message'           => $message,
	                        'start_time'        => $time,
	                        'response_time'     => date("Y-m-d H:i:s"),
	                        'operation_user_id' => 1
	                    );

	                    $amazonLogOfflineModel->savePrepareLog($addData);

                    } else {
                        $status  = 0;//失败
                        $message = $amazonListModel->getErrorMsg();
                    }                    
                }
   
	            $logModel->setSuccess($logID, "success");

            }catch (Exception $e){
                if(isset($logID) && $logID){
                    $logModel->setFailure($logID, $e->getMessage());
                }
                echo $e->getMessage()."<br/>";
            }
        }else{
            $accountList = AmazonAccount::model()->getIdNamePairs();
            foreach($accountList as $key => $value){
                MHelper::runThreadSOCKET('/'.$this->route.'/accountID/' . $key);
                sleep(1);
            }
        }
    }


    /**
     * @desc Amazon海外仓产品自动下线规则--针对海外仓(乐宝，万邑通，4PX)实时库存
     * @link /amazon/amazonlisting/overseaswarehouseoffline/accountID/1
     */
    public function actionOverseaswarehouseoffline(){
    	set_time_limit(3400);
        ini_set('display_errors', true);
        error_reporting(E_ALL);

        //a-uk、a-de、a-fr，账号ID(74、75、77)三账号已转为本地仓，海外仓已无账号下架，暂时关闭 20170324|Liz
        Yii::app()->end('目前已无海外仓账号，暂时关闭');

		$warehouseSkuMapModel               = new WarehouseSkuMap();
		$logModel                           = new AmazonLog();
		$amazonListModel                    = new AmazonList();
		$overseasWarehouseZeroStockSkuModel = new AmazonOverseasWarehouseZeroStockSku();
		$overseasWarehouseArr               = WarehouseSkuMap::getOverseasWarehouse();

        //账号
        $accountID = Yii::app()->request->getParam('accountID');

        //海外仓要下线的账号(a-uk、a-de、a-fr)  账号ID(74、75、77)
        // $setAccountArr = array(74,75,77);	
        $setAccountArr = array();
        
        //指定某个特定sku----用于测试
        $setSku = Yii::app()->request->getParam('sku');

        $select    = 't.sku';
        $eventName = 'overseas_warehouse_offline';
        $limit     = 10000;
        $offset    = 0;

        if($accountID){
        	if(!in_array($accountID, $setAccountArr)){
	        	exit('不是要运行的账号');
	        }

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
                    $command = $amazonListModel->getDbConnection()->createCommand()
                        ->from($amazonListModel->tableName())
                        ->select("id,sku,amazon_listing_id,quantity,seller_sku,account_id")
                        ->where('account_id = '.$accountID.' AND seller_status = 1');
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
                    $newSkuUniqueArr = array();
                    foreach ($variantListing as $listingValue) {
                        $skuArr[] = $listingValue['sku'];
                    }

                    //数组去重
                    $skuUnique = array_unique($skuArr);

                    //查询海外仓(乐宝，万邑通，4PX)的数据  仓库ID: 乐宝：14  万邑通：58  4PX-英国仓：34  4PX-英国路藤仓：74  易时达英国仓：61
                    $conditions = "t.warehouse_id IN(".MHelper::simplode($overseasWarehouseArr).") AND p.product_is_multi != :product_is_multi AND t.sku IN(".MHelper::simplode($skuUnique).")";
                    $param = array(':product_is_multi' => Product::PRODUCT_MULTIPLE_MAIN);
                    // $limits = "{$offset},{$limit}";
                    $skuList = $warehouseSkuMapModel->getSkuListLeftJoinProductByCondition($conditions, $param, '', $select);
                    if(!$skuList){
                        continue;            
                    } 

                    unset($skuArr);

                    foreach ($skuList as $skuVal){
                        $skuListArr[] = $skuVal['sku'];
                    }

                    $newSkuUniqueArr = array_unique($skuListArr);
                    if(!$newSkuUniqueArr){
                    	break;
                    }

                    $itemData = array();  

                    foreach ($variantListing as $variant){
                        if(!in_array($variant['sku'], $newSkuUniqueArr)){
                            continue;
                        }

                        //排除seller_sku后缀为-UK01、UK02、fr、UKFBA、FBA的
                        $sellerArr = explode('-', $variant['seller_sku']);
				        $nums = count($sellerArr) - 1;
				        $uniqueArr = array('UK01','UK02','fr','UKFBA','FBA');
				        if(isset($sellerArr[$nums]) && in_array($sellerArr[$nums],$uniqueArr)){
				        	continue;
				        }

                        $isRunning = $overseasWarehouseZeroStockSkuModel->checkHadRunningForDay($variant['seller_sku'],$accountID,$variant['amazon_listing_id']);
                        if($isRunning){
                        	continue;
                        }

                        $mapWheres = "sku ='".$variant['sku']."' AND warehouse_id IN(".MHelper::simplode($overseasWarehouseArr).") AND true_qty > 1";
	                    $skuListInfo = $warehouseSkuMapModel->getListByCondition('sku',$mapWheres);
	                    if($skuListInfo){
	                    	continue;
	                    }

                        $itemData[] = array(
							'sku'               => $variant['seller_sku'], 
							'quantity'          => 0,
							'amazon_listing_id' => $variant['amazon_listing_id']
                        );

                        $insertParam = array(
							'amazon_listing_id' => $variant['amazon_listing_id'],
							'seller_sku'        => $variant['seller_sku'],
							'sku'               => $variant['sku'],
							'account_id'        => $accountID,
							'old_quantity'      => $variant['quantity'],
							'create_time'       => date('Y-m-d H:i:s'),
							'status'        	=> AmazonOverseasWarehouseZeroStockSku::STATUS_PENGDING,
							'msg'        		=> '',
							'type'        		=> 0
                        );

                        $overseasWarehouseZeroStockSkuModel->saveData($insertParam);
                        
                    }

	                $feedSubmissionID = $amazonListModel->amazonProductOffline($accountID, $itemData);
	                if($feedSubmissionID){
	                	foreach ($itemData as $itemVal) {
	                		$updateData = array('request_id'=>$feedSubmissionID, 'status'=>AmazonOverseasWarehouseZeroStockSku::STATUS_SUCCESS);
	                		$updateParam = "seller_sku = '".$itemVal['sku']."' AND amazon_listing_id = '".$itemVal['amazon_listing_id']."'";
	                		$overseasWarehouseZeroStockSkuModel->updateData($updateData,$updateParam);
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
            foreach($setAccountArr as $value){
                MHelper::runThreadSOCKET('/'.$this->route.'/accountID/' . $value);
                sleep(5);
            }
        }
    }


    /**
     * @desc Amazon海外仓产品自动上线规则--针对海外仓(乐宝，万邑通，4PX)实时库存
     * @link /amazon/amazonlisting/overseaswarehouseaddquantity/accountID/1
     */
    public function actionOverseaswarehouseaddquantity() {
    	ini_set('memory_limit','2048M');
        set_time_limit(3400);
        ini_set('display_errors', true);
        error_reporting(E_ALL);

        //a-uk、a-de、a-fr，账号ID(74、75、77)三账号已转为本地仓，海外仓已无账号恢复，暂时关闭 20170324|Liz
        Yii::app()->end('目前已无海外仓账号，暂时关闭');    

		$logModel                           = new AmazonLog();
		$overseasWarehouseZeroStockSkuModel = new AmazonOverseasWarehouseZeroStockSku();
		$amazonListModel                    = new AmazonList();
		$warehouseSkuMapModel               = new WarehouseSkuMap();
		$productInfringeModel               = new ProductInfringe();
		$productModel                       = new Product();
		$overseasWarehouseArr               = WarehouseSkuMap::getOverseasWarehouse();

        //账号
        $accountID = Yii::app()->request->getParam('accountID');

        //海外仓要上线的账号(a-uk、a-de、a-fr)  账号ID(74、75、77)
        // $setAccountArr = array(74,75,77);
        $setAccountArr = array();

        //指定某个特定sku----用于测试
        $setSku = Yii::app()->request->getParam('sku');

        $eventName = 'overseas_warehouse_add_quantity';
        $limit     = 10;
        $offset    = 0;

        if($accountID){
        	if(!in_array($accountID, $setAccountArr)){
	        	exit('不是要运行的账号');
	        }

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

	                $command = $overseasWarehouseZeroStockSkuModel->getDbConnection()->createCommand()
	                    ->from($overseasWarehouseZeroStockSkuModel->tableName()." as t")
	                    ->leftJoin($amazonListModel->tableName()." as l", "l.amazon_listing_id=t.amazon_listing_id AND l.account_id = t.account_id")
	                    ->select("t.id,t.amazon_listing_id,t.seller_sku,t.sku,l.id as listing_id")
	                    ->where('t.account_id = '.$accountID.' AND t.is_restore = 0');
	                    if($setSku){
	                        $command->andWhere("t.sku = '".$setSku."'");
	                    }
	                $command->limit($limit, $offset);
	                $variantListing = $command->queryAll(); 
                    $offset += $limit;

	                if(!$variantListing){
	                	break;
	                }
                	$itemData   = array();
	                foreach ($variantListing as $variant){

	                	//判断sku是否是在售状态
	                	$isOnSale = $productModel->getProductBySku($variant['sku']);
	                	if($isOnSale['product_status'] != Product::STATUS_ON_SALE){
	                		continue;
	                	}

	                	//判断sku是否侵权 true为侵权 
	                	$isInfringe = $productInfringeModel->getProductIfInfringe($variant['sku']);
	                	if($isInfringe){
	                		continue;
	                	}

	                	//判断实际库存是否大于等于2
	                	//查询海外仓(乐宝，万邑通，4PX)的数据  仓库ID: 乐宝：14  万邑通：58  4PX-英国仓：34  4PX-英国路藤仓：74
	                	$mapWhere = "sku = '{$variant['sku']}' AND warehouse_id IN(".MHelper::simplode($overseasWarehouseArr).")";
	                	$skuMap = $warehouseSkuMapModel->getListOneByCondition('true_qty',$mapWhere,'true_qty DESC');
	                	if($skuMap['true_qty'] < 2){
	                		continue;
	                	}

	                	$quantity   = $skuMap['true_qty'];
						$time       = date("Y-m-d H:i:s");
						$itemData[] = array(
							'sku'        => $variant['seller_sku'], 
							'quantity'   => $quantity, 
							'listing_id' => $variant['listing_id'],
							'id'         => $variant['id']
						);             
	                }

	                //判断是否为空
	                if(empty($itemData)){
	                	continue;
	                }
					
					//更新库存		                
                    $feedSubmissionID = $amazonListModel->amazonProductOffline($accountID, $itemData);
                    if ($feedSubmissionID) {
                    	foreach ($itemData as $itemInfo) {
	                    	$wheres = 'id = '.$itemInfo['id'];
	                    	$quantity = $itemInfo['quantity'];
	                    	$amazonListModel->updateAmazonProductByPks($itemInfo['listing_id'], array('seller_status'=>AmazonList::SELLER_STATUS_ONLINE, 'quantity'=>$quantity));   

	                    	$updateData = array('is_restore'=>1, 'restore_time'=>date('Y-m-d H:i:s'), 'restore_num'=>1, 'restore_quantity'=>$quantity, 'request_id'=>$feedSubmissionID);  
	                    	$overseasWarehouseZeroStockSkuModel->updateData($updateData, $wheres);
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
            foreach($setAccountArr as $key => $value){
                MHelper::runThreadSOCKET('/'.$this->route.'/accountID/' . $value);
                sleep(5);
            }
        }
    }
    
    /**
     * @desc 自动恢复调整海外仓库存，大于>=2
     * @link /amazon/amazonlisting/restoreoverwarehouse/account_id/xx/sku/xx/bug/1
     */
    public function actionRestoreoverwarehouse(){

    	ini_set('memory_limit','2048M');
    	set_time_limit(3400);
    	ini_set('display_errors', true);
    	error_reporting(E_ALL);
    	
    	$logModel                           = new AmazonLog();
    	$overseasWarehouseZeroStockSkuModel = new AmazonOverseasWarehouseZeroStockSku();
    	$amazonListModel                    = new AmazonList();
    	$warehouseSkuMapModel               = new WarehouseSkuMap();
    	$productInfringeModel               = new ProductInfringe();
    	$productModel                       = new Product();
    	$overseasWarehouseArr               = WarehouseSkuMap::getOverseasWarehouse();
    	
    	//账号
    	$accountID = Yii::app()->request->getParam('account_id');
    	$bug = Yii::app()->request->getParam('bug');
    	//海外仓要上线的账号(a-uk、a-de、a-fr)  账号ID(74、75、77)
    	$setAccountArr = array(74,75,77);
    	
    	//指定某个特定sku----用于测试
    	$setSku = Yii::app()->request->getParam('sku');
    	
    	$eventName = 'overseas_warehouse_add_quantity';
    	$limit     = 10;
    	$offset    = 0;
    	
    	if($accountID){
    		if(!in_array($accountID, $setAccountArr)){
    			exit('不是要运行的账号');
    		}
    	
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
    			
    			if($bug) echo "<pre>";
    			do{
    				$command = $amazonListModel->getDbConnection()->createCommand()
			    				->from($amazonListModel->tableName().' as t')
			    				->select('t.id,t.amazon_listing_id,t.seller_sku,t.sku,t.asin1,t.product_id,t.warehouse_id')
			    				->where('t.account_id = '.$accountID)
    							->andWhere('t.warehouse_id IN('.MHelper::simplode($overseasWarehouseArr).')')
    							->andWhere('t.quantity=0');
    				if($setSku){
    					$command->andWhere("t.sku = '".$setSku."'");
    				}
    				$command->limit($limit, $offset);
    				if($bug){
    					echo "<br/>=======sql:".$command->text."<br/>";
    				}
    				$variantListing = $command->queryAll();
    				$offset += $limit;
    				if($bug){
    					echo "<br/>=========variantListing=========<br/>";
    					print_r($variantListing);
    				}
    				if(!$variantListing){
    					break;
    				}
    				$itemData   = array();
    				foreach ($variantListing as $variant){
    					if($bug){
    						echo "<br/>========variant:======<br/>";
    						print_r($variant);
    					}
    					//判断sku是否是在售状态
    					$isOnSale = $productModel->getProductBySku($variant['sku']);
    					if($isOnSale['product_status'] != Product::STATUS_ON_SALE){
    						if($bug){
    							echo "<br/>====={$variant['sku']} NOT ON SALE, continue ====<br/>";
    						}
    						//continue;
    					}
    	
    					//判断sku是否侵权 true为侵权
    					$isInfringe = $productInfringeModel->getProductIfInfringe($variant['sku']);
    					if($isInfringe){
    						if($bug){
    							echo "<br/>====={$variant['sku']} is Infringe, continue ====<br/>";
    						}
    						continue;
    					}
    	
    					//判断实际库存是否大于等于2
    					//查询海外仓(乐宝，万邑通，4PX)的数据  仓库ID: 乐宝：14  万邑通：58  4PX-英国仓：34  4PX-英国路藤仓：74
    					$mapWhere = "sku = '{$variant['sku']}' AND warehouse_id in(".MHelper::simplode($overseasWarehouseArr).") AND true_qty>=2";
    					$skuMap = $warehouseSkuMapModel->getListOneByCondition('true_qty',$mapWhere,'true_qty DESC');
    					if($bug){
    						echo "<br/>======mapWhere:{$mapWhere}=====<br/>";
    						print_r($skuMap);
    					}
    					if($skuMap['true_qty'] < 2){
    						continue;
    					}
    	
    					$quantity   = $skuMap['true_qty'];
    					$time       = date("Y-m-d H:i:s");
    					$itemData[] = array(
    							'sku'        => $variant['seller_sku'],
    							'quantity'   => $quantity,
    							'listing_id' => $variant['id'],
    							'amazon_listing_id' => $variant['amazon_listing_id'],
    							'sys_sku'	=>	$variant['sku']
    					);
    				}
    				if($bug){
    					echo "<br/>========itemData:======<br/>";
    					print_r($itemData);
    				}
    				//判断是否为空
    				if(empty($itemData)){
    					continue;
    				}
    				
    				//更新库存
    				$feedSubmissionID = $amazonListModel->amazonProductOffline($accountID, $itemData);
    				if ($feedSubmissionID) {
    					foreach ($itemData as $itemInfo) {
    						$wheres = "seller_sku = '{$itemInfo['sku']}' and account_id='{$accountID}' and is_restore=0";
    						$quantity = $itemInfo['quantity'];
    						$amazonListModel->updateAmazonProductByPks($itemInfo['listing_id'], array('seller_status'=>AmazonList::SELLER_STATUS_ONLINE, 'quantity'=>$quantity));
    	
    						$updateData = array('is_restore'=>1, 'restore_time'=>date('Y-m-d H:i:s'), 'restore_num'=>1, 'restore_quantity'=>$quantity, 'request_id'=>$feedSubmissionID);
    						//如果不存在记录，则添加
    						$existsRecord = $overseasWarehouseZeroStockSkuModel->getDbConnection()
    															->createCommand()
    															->from($overseasWarehouseZeroStockSkuModel->tableName())
    															->select("id")
    															->where($wheres)->queryRow();
    						if($bug){
    							echo "<br/>=====wheres:{$wheres}======<br/>";
    							echo "<br/>====sellerSKU:{$itemInfo['sku']}====<br/>";
    							echo "<br>====existsRecord====<br/>";
    							print_r($existsRecord);
    						}
    						if($existsRecord){
    							$res = $overseasWarehouseZeroStockSkuModel->updateData($updateData, $wheres);
    						}else{
    							$addData = $updateData;
    							$addData['amazon_listing_id'] = $itemInfo['amazon_listing_id'];
    							$addData['seller_sku']	=	$itemInfo['sku'];
    							$addData['sku']			=	$itemInfo['sys_sku'];
    							$addData['account_id']	=	$accountID;
    							$addData['create_time']	=	date("Y-m-d H:i:s");
    							$addData['status']		=	1;
    							$addData['msg']		=	'库存恢复';
    							if($bug){
    								echo "<br/> ======addData====== <br/>";
    								print_r($addData);
    							}
    							$res = $overseasWarehouseZeroStockSkuModel->getDbConnection()
    															->createCommand()
    															->insert($overseasWarehouseZeroStockSkuModel->tableName(), $addData);
    							
    						}
    						
    						if($bug){
    							echo "<br/> =====res:=====<br/>";
    							var_dump($res);
    						}
    					}
    				}
    				if($bug){
    					break;
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
    		foreach($setAccountArr as $key => $value){
    			MHelper::runThreadSOCKET('/'.$this->route.'/accountID/' . $value);
    			sleep(5);
    		}
    	}
    	
    }
    
    /**
     * @desc 批量改发货日期
     * @link /amazon/amazonlisting/changefullday/account_id/xx/asin/x/bug/1
     */
    public function actionChangefullday(){
    	$amazonListModel = new AmazonList();
    	$accountID = Yii::app()->request->getParam("account_id");
    	$bug = Yii::app()->request->getParam("bug");
    	$asin = Yii::app()->request->getParam("asin");
    	if($accountID){
    		//获取lisitng
    		$isContinue = false;
    		$limit = 200;
    		$offset = 0;
    		$fulfillmentLatency = 2;
    		if($bug){
    			echo "<pre>";
    		}
    		do{
    			$command = $amazonListModel->getDbConnection()->createCommand()
					    			->from($amazonListModel->tableName())
					    			->select("id,sku,amazon_listing_id,quantity,seller_sku,account_id")
					    			->where('account_id = '.$accountID.' AND seller_status = 1');
    			if($asin){
    				$command->andWhere("asin1 = '".$asin."'");
    			}
    			$command->limit($limit, $offset);
    			$offset += $limit;
    			$variantListing = $command->queryAll();
    			if ($bug) {
    				echo "<br/>===========variantListing:==========<br/>";
    				print_r($variantListing);
    			}
    			
    			if(!$variantListing){
    				$isContinue = false;
    				break;
    			}else{
    				$isContinue = true;
    			}
    		
    			$skuArr = array();
    			$skuListArr = array();
    			$newSkuUniqueArr = array();
    			$itemData = array();
    			foreach ($variantListing as $listingValue) {
    				$itemData[] = array('sku'=>$listingValue['seller_sku'], 'fulfillmentLatency'=>$fulfillmentLatency,'quantity'=>198);
    			}
    			$res = $amazonListModel->amazonChangeFulfillmentLatency($accountID, $itemData);
    			if($bug){
    				echo "<br/>===========result:==========<br/>";
    				var_dump($res);
    				echo "<br/>===========itemData:==========<br/>";
    				print_r($itemData);
    				echo "<br/>";
    				echo $amazonListModel->getErrorMsg();
    			}
    		}while($isContinue);
    	}else{
    		//获取账号
    	}
    	
    }


	/**
	 * 针对春节规则后指定账号库存恢复（本地仓）
	 * @date 2017-02-15
	 * @link /amazon/amazonlisting/restoreskustockspecifyaccount/account_id/xx/sku/xx,xx
	 */
	public function actionRestoreskustockspecifyaccount() {
		set_time_limit (2*3600);
		ini_set('display_errors', true);
		error_reporting(E_ALL);
		$accountID = Yii::app ()->request->getParam ( 'account_id' );
		$type = Yii::app ()->request->getParam ( 'type' );
		$testSKU = Yii::app ()->request->getParam ( 'sku' );
		$notAllowAccountID = array();
		$allowAccountID = array();
		$testSKUArr = array ();
		if ($testSKU) {
			$testSKUArr = explode ( ",", $testSKU );
		}
		$myDate = date('Y-m-d',time());

		if ($accountID) {
			$time = date ( "Y-m-d H:i:s" );
			// 写log
			$logModel = new AmazonLog ();
			$eventName = AmazonZeroStockSku::EVENT_RESTORE_STOCK;
			$logID = $logModel->prepareLog ( $accountID, $eventName );
			if (! $logID) {
				exit ( 'Create Log Failure' );
			}
			// 检测是否可以允许
			if (! $logModel->checkRunning ( $accountID, $eventName )) {
				$logModel->setFailure ( $logID, Yii::t ( 'system', 'There Exists An Active Event' ) );
				exit ( 'There Exists An Active Event' );
			}
			$startTime = date ( "Y-m-d H:i:s" );
			// 设置运行
			$logModel->setRunning ( $logID );
			// @todo
			// 1、获取对应的置为0的sku列表
			// 2、寻找对应sku的可用库存数量
			$amazonListModel = new AmazonList ();
			$amazonZeroStockSKUModel = new AmazonZeroStockSku ();
			$limit = 2000;
			if($accountID == 10) $limit = 10000;	//listing数量过多，造成上传跟踪号等上传的连接数超限，因此增加批量个数，节省连接数

			$offset = 0;
			$quantity = 99; // 恢复库存数量
			$allowWarehouse = WarehouseSkuMap::WARE_HOUSE_GM;
			$availableQty = 10; // 可用库存
			
			$StockTablename = $amazonZeroStockSKUModel::model()->tableName();
			
			if ($testSKUArr) {
				echo "<pre>";
				echo "test SKU:{$testSKU}<br/>";
			}
			$failureSKU = array ();
			$successSKU = array ();
			do {
				$command = $amazonListModel->getDbConnection ()->createCommand ()
											->from ($amazonListModel->tableName ()." as p")
											->leftJoin($StockTablename." as s", "p.amazon_listing_id = s.product_id")
											->select ( "p.sku, p.product_id, p.account_id, p.seller_sku, p.quantity as product_stock, p.amazon_listing_id" )
											->where ( "p.account_id = '{$accountID}'" )
											->andWhere ( "p.quantity = 0" )
											->andWhere ( "p.seller_status = " . AmazonList::SELLER_STATUS_OFFLINE )
											->andWhere ( "p.fulfillment_type = " . AmazonList::FULFILLMENT_STATUS_MERCHANT )
											->andWhere ( "p.warehouse_id = 41 " )
											->andWhere ( "s.type = 6 " )
											->andWhere ( "s.status = 2 " )
											->andWhere ( "s.is_restore = 0 " )
											->group('p.seller_sku')
											->limit ( $limit, $offset );
				if ($testSKUArr) {
					$command->andWhere ( array (
							"IN",
							"p.sku",
							$testSKUArr 
					) );
				}
				$skuList = $command->queryAll ();
				$offset += $limit;
				if ($testSKUArr) {
					echo "skuList：<br/>";
					print_r ( $skuList );
				}
				if ($skuList) {
					// @todo 获取库存
					$vaildSKUList = array ();
					foreach ( $skuList as $sku ) {
						if(!empty($sku ['sku'])) $vaildSKUList [] = $sku ['sku'];
					}					

					if ($testSKUArr) {
						echo "vaildSKUList：<br/>";
						print_r ( $vaildSKUList );					
					}

					if (! empty ( $vaildSKUList )) {
						$sellerSku = array ();
						$updateSellerSku = array();
						foreach ( $skuList as $sku ) {
							$sellerSku [] = array (
									'sku' => $sku ['seller_sku'],
									'quantity' => $quantity 
							);
							$updateSellerSku[] = $sku['seller_sku'];
						}
						if ($testSKUArr) {
							echo "sellerSKU:<br/>";
							print_r ( $sellerSku );
						}
						// amazon测试和上线时开启
						$submitFeedId = $amazonListModel->amazonProductOffline ( $accountID, $sellerSku );			

						$msg = "";
						if ($submitFeedId) {
							$status = 2;//已提交		
							//同步更新本地
							$updateconditions = "account_id='{$accountID}' AND seller_sku IN(".MHelper::simplode($updateSellerSku).")";
							$amazonListModel->updateAmazonProduct($updateconditions, array('quantity'=>$quantity, 'seller_status'=>AmazonList::SELLER_STATUS_ONLINE));
							$successSKU [] = $sellerSku;
						} else {
							$msg = $amazonListModel->getErrorMsg();
							$status = 3;
							$failureSKU [] = $sellerSku;
						}
						
						foreach ($skuList as $variant){
							$addData = array(
									'product_id'   =>	$variant['amazon_listing_id'],
									'seller_sku'   =>	$variant['seller_sku'],
									'sku'          =>	$variant['sku'],
									'account_id'   =>	$accountID,
									'site_id'      =>	0,
									'old_quantity' =>   $quantity,
									'status'       =>	$status,
									'msg'          =>	$msg,
									'create_time'  =>	$time,
									'type'         =>	8, // 2016-02-01
									'request_id'   =>   $submitFeedId,
									'is_restore'   =>   1 //2017.2.15
							);
							$amazonZeroStockSKUModel->saveData($addData);
						}
					}
				}
			} while ( $skuList );
			$logModel->setSuccess ( $logID, 'done' );
			if ($testSKU) {
				echo "failure:<br/>";
				print_r ( $failureSKU );
				
				echo "success:<br/>";
				print_r ( $successSKU );
			}
			echo "Finish";
		} else {
			// 循环每个账号发送一个拉listing的请求
			$accountList = AmazonAccount::model ()->getIdNamePairs();
			//whitelotous-us/easydeal88-us/d-uk/chinatera-us/mmdex-us/diamond66-us/d-jp七个账号恢复库存
			$allowAccountID = array('10','43','46','58','59','60','62');

			foreach ( $accountList as $accountID => $accountName ) {
				if(!in_array($accountID, $allowAccountID)) continue;
				MHelper::runThreadSOCKET ( '/' . $this->route . '/account_id/' . $accountID . '/sku/' . $testSKU );
				sleep ( 1 );
			}
		}
	}



	/**
	 * 一次性指定账号库存恢复（规则：账号的listing的库存为0的都执行恢复）
	 * @date 2017-03-03
	 * @link /amazon/amazonlisting/restorestockfromlisting/account_id/xx/sku/xx,xx
	 */
	public function actionRestorestockfromlisting() {
		set_time_limit (2*3600);
		ini_set('display_errors', true);
		error_reporting(E_ALL);
		$accountID = Yii::app ()->request->getParam ( 'account_id' );
		$testSKU = Yii::app ()->request->getParam ( 'sku' );
		$notAllowAccountID = array();
		$allowAccountID = array();
		$testSKUArr = array ();
		if ($testSKU) {
			$testSKUArr = explode ( ",", $testSKU );
		}

		if ($accountID) {
			$time = date ( "Y-m-d H:i:s" );
			// 写log
			$logModel = new AmazonLog ();
			$eventName = AmazonZeroStockSku::EVENT_RESTORE_STOCK;
			$logID = $logModel->prepareLog ( $accountID, $eventName );
			if (! $logID) {
				exit ( 'Create Log Failure' );
			}
			// 检测是否可以允许
			if (! $logModel->checkRunning ( $accountID, $eventName )) {
				$logModel->setFailure ( $logID, Yii::t ( 'system', 'There Exists An Active Event' ) );
				exit ( 'There Exists An Active Event' );
			}
			$startTime = date ( "Y-m-d H:i:s" );

			$logModel->setRunning ( $logID );

			$amazonListModel = new AmazonList ();
			$amazonZeroStockSKUModel = new AmazonZeroStockSku ();
			$limit = 2000;

			$offset = 0;
			$quantity = 99; // 恢复库存数量
			$allowWarehouse = WarehouseSkuMap::WARE_HOUSE_GM;
			
			$StockTablename = $amazonZeroStockSKUModel::model()->tableName();
			
			if ($testSKUArr) {
				echo "<pre>";
				echo "test SKU:{$testSKU}<br/>";
			}
			$failureSKU = array ();
			$successSKU = array ();
			do {
				$command = $amazonListModel->getDbConnection ()->createCommand ()
											->from ($amazonListModel->tableName ()." as p")
											// ->leftJoin($StockTablename." as s", "p.amazon_listing_id = s.product_id")
											->select ( "p.sku, p.product_id, p.account_id, p.seller_sku, p.quantity as product_stock, p.amazon_listing_id" )
											->where ( "p.account_id = '{$accountID}'" )
											->andWhere ( "p.quantity = 0" )
											->andWhere ( "p.seller_status = " . AmazonList::SELLER_STATUS_OFFLINE )
											->andWhere ( "p.fulfillment_type = " . AmazonList::FULFILLMENT_STATUS_MERCHANT )
											->andWhere ( "p.warehouse_id = 41 " )
											// ->andWhere ( "s.type = 6 " )
											// ->andWhere ( "s.status = 2 " )
											// ->andWhere ( "s.is_restore = 0 " )
											->group('p.seller_sku')
											->limit ( $limit, $offset );
				if ($testSKUArr) {
					$command->andWhere ( array (
							"IN",
							"p.sku",
							$testSKUArr 
					) );
				}
				$skuList = $command->queryAll ();
				$offset += $limit;
				if ($testSKUArr) {
					echo "skuList：<br/>";
					print_r ( $skuList );
				}
				if ($skuList) {
					// @todo 获取库存
					$vaildSKUList = array ();
					foreach ( $skuList as $sku ) {
						if(!empty($sku ['sku'])) $vaildSKUList [] = $sku ['sku'];
					}					

					if ($testSKUArr) {
						echo "vaildSKUList：<br/>";
						print_r ( $vaildSKUList );					
					}

					if (! empty ( $vaildSKUList )) {
						$sellerSku = array ();
						$updateSellerSku = array();
						foreach ( $skuList as $sku ) {
							$sellerSku [] = array (
									'sku' => $sku ['seller_sku'],
									'quantity' => $quantity 
							);
							$updateSellerSku[] = $sku['seller_sku'];
						}
						if ($testSKUArr) {
							echo "sellerSKU:<br/>";
							print_r ( $sellerSku );
						}
						// amazon测试和上线时开启
						$submitFeedId = $amazonListModel->amazonProductOffline ( $accountID, $sellerSku );	

						$msg = "";
						if ($submitFeedId) {
							$status = 2;//已提交		
							//同步更新本地
							$updateconditions = "account_id='{$accountID}' AND seller_sku IN(".MHelper::simplode($updateSellerSku).")";
							$amazonListModel->updateAmazonProduct($updateconditions, array('quantity'=>$quantity, 'seller_status'=>AmazonList::SELLER_STATUS_ONLINE));
							$successSKU [] = $sellerSku;
						} else {
							$msg = $amazonListModel->getErrorMsg();
							$status = 3;
							$failureSKU [] = $sellerSku;
						}
						
						foreach ($skuList as $variant){
							$addData = array(
									'product_id'   =>	$variant['amazon_listing_id'],
									'seller_sku'   =>	$variant['seller_sku'],
									'sku'          =>	$variant['sku'],
									'account_id'   =>	$accountID,
									'site_id'      =>	0,
									'old_quantity' =>   0,
									'status'       =>	$status,
									'msg'          =>	$msg,
									'create_time'  =>	$time,
									'type'         =>	8, // 2016-02-01
									'request_id'   =>   $submitFeedId,
									'is_restore'   =>   1 //2017.2.15
							);
							$amazonZeroStockSKUModel->saveData($addData);
						}
					}
				}
			} while ( $skuList );
			$logModel->setSuccess ( $logID, 'done' );
			if ($testSKU) {
				echo "failure:<br/>";
				print_r ( $failureSKU );
				
				echo "success:<br/>";
				print_r ( $successSKU );
			}
			echo "Finish";
		} else {
			//暂不执行多个账号操作
			Yii::app()->end('没有账号参数');
		}
	}


}