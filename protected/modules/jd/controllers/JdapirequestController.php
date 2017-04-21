<?php
/**
 * @desc jd国际接口访问请求控制器
 * @author lihy
 *
 */
class JdapirequestController extends UebController {
	
	public function accessRules(){
		return array(
				array(
						'allow',
						'users'=>'*',
						'actions'=>array('getlisting')
					)
				
		);	
	}
	/**
	 * @desc 获取产品列表
	 */
	public function actionGetlisting(){
		set_time_limit(5*3600);
		ini_set('memory_limit','512M');
		ini_set('display_errors', true);
		$accountID = Yii::app()->request->getParam('account_id');
		$wareIds = Yii::app()->request->getParam('ware_id');//可以用多个，半角,隔开
		$day = Yii::app()->request->getParam('day');//拉取上架时间，单位为天

		if($accountID){
			try{
				$update_lasttime = date("Y-m-d H:i:s");//最后拉取时间
				$ago_time = date("Y-m-d H:i:s", strtotime("-3 day"));//三天前
				$jdLogModel = new JdLog;
				$jdProductModel = new JdProduct;
				$logId = $jdLogModel->prepareLog($accountID, JdProduct::EVENT_GET_PRODUCT);
				//检测是否已经有该账户的脚本运行了
				$checkRunning = $jdLogModel->checkRunning($accountID, JdProduct::EVENT_GET_PRODUCT);
				if(!$checkRunning){
					$jdLogModel->setFailure($logId, Yii::t('systems', 'There Exists An Active Event'));
					throw  new Exception(Yii::t('systems', 'There Exists An Active Event'));
				}
				//插入本次log参数日志(用来记录请求的参数)
				$eventLog = $jdLogModel->saveEventLog(JdProduct::EVENT_GET_PRODUCT, array(
						'log_id'        => $logId,
						'account_id'    => $accountID,
						'start_time'    => date("Y-m-d H:i:s"),
						'end_time'      => date("Y-m-d H:i:s"),
				));
				//设置日志为正在运行
				$jdLogModel->setRunning($logId);
				$success = 0;
				$pageSize = 20;
				$currentPage = isset($_REQUEST['page'])?(int)$_REQUEST['page']:1;
				$totalPage = 0;
				$debug = isset($_REQUEST['page'])?true:false;
				$getWareListRequestModel = new GetWareListRequest;
				do{
					if($wareIds){
						$getWareListRequestModel->setWareId($wareIds);
					}else{
						$getWareListRequestModel->setWareId(null);
					}
					if($day){
						$getWareListRequestModel->setStartOnlineTime(date("Y-m-d H:i:s",time()-86400*$day));
					}

					$getWareListRequestModel->setAccount($accountID);
					$getWareListRequestModel->setPageSize($pageSize);
					$getWareListRequestModel->setCurrentPage($currentPage);
					$reponse = $getWareListRequestModel->setRequest()->sendRequest()->getResponse();
					echo "currentPage:{$currentPage}<br/>";
					++$currentPage;
					if($getWareListRequestModel->getIfSuccess()){
						$success++;
						$reponse = json_decode(json_encode($reponse), true);
						$queryResult = $reponse['jingdong_ept_warecenter_warelist_get_responce']['querywarelist_result'];
						unset($reponse);
						//保存到数据库
						$datas = array();
						if(!empty($queryResult['wareList'])){
							$datas = $queryResult['wareList'];
						}
						if(empty($datas)){
							throw new Exception('currentPage:' . $currentPage . 'No Datas');
						}
						foreach($datas as $k=>$data){
							$datas[$k]['update_lasttime'] = $update_lasttime;
						}
						if($totalPage == 0)
							$totalPage = ceil($queryResult['totalCount']/$pageSize);
						$jdProductModel->saveProductData($accountID, $datas);
						unset($datas, $queryResult);
						echo $jdProductModel->getErrorMsg(), '<br/>';
						echo "finish<br/>";
					}else {
						echo $getWareListRequestModel->getErrorMsg(), '<br/>';
					}
					sleep(1);
				}while($currentPage <= $totalPage && !$debug);
				if($success){
					$jdLogModel->setSuccess($logId);
					$jdLogModel->saveEventStatus(JdProduct::EVENT_GET_PRODUCT, $eventLog, JdLog::STATUS_SUCCESS);
				}else{
					$jdLogModel->setFailure($logId, $getWareListRequestModel->getErrorMsg());
					$jdLogModel->saveEventStatus(JdProduct::EVENT_GET_PRODUCT, $eventLog, JdLog::STATUS_FAILURE);
					throw new Exception($getWareListRequestModel->getErrorMsg());
				}

				if(!$wareIds && !$debug){ //测试环境下更新
					//如果大于三天都没更新，状态更新为删除
					$update_res = $jdProductModel->getDbConnection()->createCommand()->update(
						$jdProductModel->tableName(),
						array('ware_status'=>JdProduct::WARE_STATUS_DELETE),
						'update_lasttime < "'.$ago_time.'"');
					if(!$update_res){
						throw new Exception('update status error');
					}
				}
			}catch (Exception $e){
				echo $e->getMessage();
			}
		}else{
			$accountList = JdAccount::model()->getAbleAccountList();
			foreach ($accountList as $accountInfo) {
				MHelper::runThreadSOCKET('/'.$this->route.'/account_id/' . $accountInfo['id']);
				sleep(1);
			}
		}
	}
	
	/**
	 * @desc拉取账号指定商品
	 * @throws Exception
	 */
	public function actionGetware() {
		set_time_limit(5*3600);
		ini_set('display_errors', true);
		$accountID = Yii::app()->request->getParam('account_id');
		$wareIds = Yii::app()->request->getParam('ware_id');//可以用多个，半角,隔开
		if($accountID){
			try{
				$jdProductModel = new JdProduct;
				$pageSize = 20;
				$currentPage = 1;
				$totalPage = 0;
				$getWareListRequestModel = new GetWareListRequest;
				do{
					if($wareIds){
						$getWareListRequestModel->setWareId($wareIds);
					}else{
						$getWareListRequestModel->setWareId(null);
					}
					$getWareListRequestModel->setAccount($accountID);
					$getWareListRequestModel->setPageSize($pageSize);
					$getWareListRequestModel->setCurrentPage($currentPage);
					$reponse = $getWareListRequestModel->setRequest()->sendRequest()->getResponse();
					++$currentPage;
					if($getWareListRequestModel->getIfSuccess()){
						$reponse = json_decode(json_encode($reponse), true);
						$queryResult = $reponse['jingdong_ept_warecenter_warelist_get_responce']['querywarelist_result'];
						unset($reponse);
						//保存到数据库
						$datas = array();
						if(!empty($queryResult['wareList'])){
							$datas = $queryResult['wareList'];
						}
						if(empty($datas)){
							throw new Exception('currentPage:' . $currentPage . 'No Datas');
						}
						if($totalPage == 0)
							$totalPage = ceil($queryResult['totalCount']/$pageSize);
						$jdProductModel->saveProductData($accountID, $datas);
						unset($datas, $queryResult);
					}else {
						echo $getWareListRequestModel->getErrorMsg(), '<br/>';
					}
				}while($currentPage <= $totalPage);
			}catch (Exception $e){
				echo $e->getMessage();
			}
		}	
	}
	
	public function actionGetjdware(){
		$wareid = Yii::app()->request->getParam('ware_id');
		$account_id = Yii::app()->request->getParam('account_id');
		if($wareid && $account_id){
			$getWareRequestModel = new GetWareRequest;
			$getWareRequestModel->setAccount($account_id);
			$getWareRequestModel->setWareId($wareid);
			$reponse = $getWareRequestModel->setRequest()->sendRequest()->getResponse();
			$this->__print($reponse);
		}
	}
	
	public function actionGetjdwaresku(){
		$wareid = Yii::app()->request->getParam('ware_id');
		$account_id = Yii::app()->request->getParam('account_id');
		if($wareid && $account_id){
			$getWareRequestModel = new QueryWareSkuRequest();
			$getWareRequestModel->setAccount($account_id);
			$getWareRequestModel->setWareId($wareid);
			$reponse = $getWareRequestModel->setRequest()->sendRequest()->getResponse();
			$this->__print($reponse);
		}
	}
	
	/**
	 * @desc 拉取类目
	 */
	public function actionGetcategory(){
		set_time_limit(3600);
		ini_set('display_errors', true);
		$accountId = Yii::app()->request->getParam('account_id');
		$status = Yii::app()->request->getParam('status');
		if($accountId){
			if(!$status)
				$status = 1;
			try{
				$jdLogModel = new JdLog();
				$logID = $jdLogModel->prepareLog($accountId, JdCategory::EVENT_GET_CATEGORY);
				if(!$logID){
					throw new Exception('Log ID Insert Failure');
				}
				//检测是否正在运行
				$checkRunning = $jdLogModel->checkRunning($accountId, JdCategory::EVENT_GET_CATEGORY);
				if(!$checkRunning){
					$jdLogModel->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
					throw new Exception(Yii::t('systems', 'There Exists An Active Event'));
				}
				$eventLog = $jdLogModel->saveEventLog(JdCategory::EVENT_GET_CATEGORY, 
													array(
															'log_id'        => $logID,
															'account_id'    => $accountId,
															'start_time'    => date("Y-m-d H:i:s"),
															'end_time'      => date("Y-m-d H:i:s")
													));
				$jdLogModel->setRunning($logID);
				$getCategory = new GetCategoryRequest;
				$getCategory->setAccount($accountId);
				$getCategory->setStatus($status);
				$response = $getCategory->setRequest()->sendRequest()->getResponse();
				$response = json_decode(json_encode($response), true);
				$queryResult = null;
				if($getCategory->getIfSuccess() && !empty($response['jingdong_ept_vender_category_get_responce']['getvendercategory_result']))
				{		
					$queryResult = $response['jingdong_ept_vender_category_get_responce']['getvendercategory_result'];
					$jdCategoryModel = new JdCategory();
					$jdCategoryModel->saveCategoryData($queryResult, $accountId);
					$jdLogModel->setSuccess($logID);
					$jdLogModel->saveEventStatus(JdCategory::EVENT_GET_CATEGORY, $eventLog, JdLog::STATUS_SUCCESS);
					echo "Finished";
				}else{
					$exceptionMsg = 'No Category Data';
					if(!$getCategory->getIfSuccess()){
						$exceptionMsg = $getCategory->getErrorMsg();
					}
					$jdLogModel->setFailure($logID, $exceptionMsg);
					$jdLogModel->saveEventStatus(JdCategory::EVENT_GET_CATEGORY, $eventLog, JdLog::STATUS_FAILURE);
					throw new Exception($exceptionMsg);
				}
			}catch (Exception $e){
				echo $e->getMessage();
			}
		}else{
			$accountList = JdAccount::model()->getAbleAccountList();
			foreach ($accountList as $accountInfo) {
				MHelper::runThreadSOCKET('/'.$this->route.'/account_id/' . $accountInfo['id']);
				sleep(1);
			}
		}
	}
	
	private function __print($data, $exist = false){
		echo "<pre>";
		print_r($data);
		echo "</pre>";
		if($exist) exit;
	}
	/**
	 * @desc 拉取属性列表及其对应值
	 * @throws Exception
	 */
	public function actionGetattribute(){
		set_time_limit(3600);
		ini_set('display_errors', true);
		$accountId = Yii::app()->request->getParam('account_id');
		if($accountId){
			try{
				$jdLogModel = new JdLog();
				$logID = $jdLogModel->prepareLog($accountId, JdAttribute::EVENT_GET_ATTRIBUTE);
				if(!$logID){
					throw new Exception('Log ID Insert Failure');
				}
				//检测是否正在运行
				$checkRunning = $jdLogModel->checkRunning($accountId, JdAttribute::EVENT_GET_ATTRIBUTE);
				if(!$checkRunning){
					$jdLogModel->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
					throw new Exception(Yii::t('systems', 'There Exists An Active Event'));
				}
				$eventLog = $jdLogModel->saveEventLog(JdAttribute::EVENT_GET_ATTRIBUTE, 
													array(
															'log_id'        => $logID,
															'account_id'    => $accountId,
															'start_time'    => date("Y-m-d H:i:s"),
															'end_time'      => date("Y-m-d H:i:s")
													));
				$jdLogModel->setRunning($logID);
				//获取对应的类目信息
				$jdCategoryModel = new JdCategory();
				$cateList = $jdCategoryModel->getThreeCategoryListByAccountId($accountId);
				if($cateList){
					foreach ($cateList as $cat){
						try{
							$catId = $cat['cat_id'];	
							echo " Category {$catId}:{$cat['category_name']} Finished<br/>";
							$queryCtgattrRequest = new QueryCtgattrRequest;
							$queryCtgattrRequest->setAccount($accountId);
							$queryCtgattrRequest->setCatId($catId);
							$response = $queryCtgattrRequest->setRequest()->sendRequest()->getResponse();
							if(!$queryCtgattrRequest->getIfSuccess()){
								throw new Exception($queryCtgattrRequest->getErrorMsg());
							}
							
							$response = json_decode(json_encode($response), true);
							if(empty($response['jingdong_ept_warecenter_outapi_ctgattr_query_responce']['querycategoryattrubite_result']['categoryPropertyList']))
								throw new Exception($response['jingdong_ept_warecenter_outapi_ctgattr_query_responce']['querycategoryattrubite_result']['message']);
							$propertyList = $response['jingdong_ept_warecenter_outapi_ctgattr_query_responce']['querycategoryattrubite_result']['categoryPropertyList'];
							$jdAttributeModel = new JdAttribute;
							$jdAttributeModel->saveCategoryPropertyData($propertyList);
							$queryCtgattrValueRequest = new QueryCtgattrValueRequest;
							$jdAttributeValueModel = new JdAttributeValue;
							foreach ($propertyList as $property){
								try{
									echo "Property {$property['propertyName']} Value Start<br/>";
									$propertyId = $property['propertyId'];
									$queryCtgattrValueRequest->setAccount($accountId);
									$queryCtgattrValueRequest->setCatId($catId);
									$queryCtgattrValueRequest->setPropertyId($propertyId);
									$response = $queryCtgattrValueRequest->setRequest()->sendRequest()->getResponse();
									if(!$queryCtgattrValueRequest->getIfSuccess())
										throw new Exception($queryCtgattrValueRequest->getErrorMsg());
									$response = json_decode(json_encode($response), true);
									/* $this->__print($response);
									 exit; */
									if(empty($response['jingdong_ept_warecenter_outapi_ctgattr_value_query_responce']['querycategoryattrubite_result']['categoryPropertyValueList']))
										throw new Exception($response['jingdong_ept_warecenter_outapi_ctgattr_value_query_responce']['querycategoryattrubite_result']['message']);
									$queryData = $response['jingdong_ept_warecenter_outapi_ctgattr_value_query_responce']['querycategoryattrubite_result']['categoryPropertyValueList'];
									foreach ($queryData as $v){
										$jdAttributeValueModel->saveCategoryPropertyValueData($v['propertyValue'], $propertyId);
									}
									echo "Property {$property['propertyName']} Value Finish<br/>";
								}catch (Exception $e){
									echo $e->getMessage();
								}
							}
							echo " Category {$catId}:{$cat['category_name']} Finished<br/>";
						}catch (Exception $e){
							echo $e->getMessage();
							echo "<br/>";
						}
					}
				}
				$jdLogModel->setSuccess($logID);
				$jdLogModel->saveEventStatus(JdAttribute::EVENT_GET_ATTRIBUTE, $eventLog, JdLog::STATUS_SUCCESS);
			}catch (Exception $e){
				echo $e->getMessage();
			}
		}else{
			$accountList = JdAccount::model()->getAbleAccountList();
			foreach ($accountList as $accountInfo) {
				MHelper::runThreadSOCKET('/'.$this->route.'/account_id/' . $accountInfo['id'] );
				sleep(1);
				
			}
		}
	}
	/**
	 * @desc 拉取属性值（拉取属性时，属性值已经附带拉取了，只有只更新属性值时才调用）
	 * @throws Exception
	 */
	public function actionGetattributeval(){
		set_time_limit(3600);
		ini_set('display_errors', true);
		$accountId = Yii::app()->request->getParam('account_id');
		$catId = Yii::app()->request->getParam('cat_id');
		$propertyId = Yii::app()->request->getParam('property_id');
		if($accountId && $catId && $propertyId){
			try{
				$jdLogModel = new JdLog();
				$logID = $jdLogModel->prepareLog($accountId, JdAttribute::EVENT_GET_ATTRIBUTE);
				if(!$logID){
					throw new Exception('Log ID Insert Failure');
				}
				//检测是否正在运行
				$checkRunning = $jdLogModel->checkRunning($accountId, JdAttribute::EVENT_GET_ATTRIBUTE);
				if(!$checkRunning){
					$jdLogModel->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
					throw new Exception(Yii::t('systems', 'There Exists An Active Event'));
				}
				$eventLog = $jdLogModel->saveEventLog(JdAttribute::EVENT_GET_ATTRIBUTE,
						array(
								'log_id'        => $logID,
								'account_id'    => $accountId,
								'start_time'    => date("Y-m-d H:i:s"),
								'end_time'      => date("Y-m-d H:i:s")
						));
				$jdLogModel->setRunning($logID);
				$queryCtgattrValueRequest = new QueryCtgattrValueRequest;
				$queryCtgattrValueRequest->setAccount($accountId);
				$queryCtgattrValueRequest->setCatId($catId);
				$queryCtgattrValueRequest->setPropertyId($propertyId);
				$response = $queryCtgattrValueRequest->setRequest()->sendRequest()->getResponse();
				
				$response = json_decode(json_encode($response), true);
				
				if($queryCtgattrValueRequest->getIfSuccess() && !empty($response['jingdong_ept_warecenter_outapi_ctgattr_value_query_responce']['querycategoryattrubite_result']['categoryPropertyValueList'])){
					$queryData = $response['jingdong_ept_warecenter_outapi_ctgattr_value_query_responce']['querycategoryattrubite_result']['categoryPropertyValueList'];
					$jdAttributeValueModel = new JdAttributeValue;
					foreach ($queryData as $v){
						$jdAttributeValueModel->saveCategoryPropertyValueData($v['propertyValue'], $propertyId);
					}
					$jdLogModel->setSuccess($logID);
					$jdLogModel->saveEventStatus(JdAttribute::EVENT_GET_ATTRIBUTE, $eventLog, JdLog::STATUS_SUCCESS);
					echo "finish";
				}else{
					if(!$queryCtgattrValueRequest->getIfSuccess())
						$exceptionMessage = $queryCtgattrValueRequest->getErrorMsg();
					else $exceptionMessage = $response['jingdong_ept_warecenter_outapi_ctgattr_value_query_responce']['querycategoryattrubite_result']['message'];
					$jdLogModel->setFailure($logID, $exceptionMessage);
					$jdLogModel->saveEventStatus(JdAttribute::EVENT_GET_ATTRIBUTE, $eventLog, JdLog::STATUS_FAILURE);
					throw new Exception($exceptionMessage);
				}
			}catch (Exception $e){
				echo $e->getMessage();
			}
		}else{
			$accountList = JdAccount::model()->getAbleAccountList();
			$jdCategoryModel = new JdCategory();
			$jdAttributeModel = new JdAttribute();
			foreach ($accountList as $accountInfo) {
				//获取对应的类目信息
				$cateList = $jdCategoryModel->getThreeCategoryListByAccountId($accountInfo['id']);
				if($cateList){
					foreach ($cateList as $cat){
						$attributeList = $jdAttributeModel->getAttributeListByCatId($cat['cat_id']);
						if($attributeList){
							foreach ($attributeList as $attr){
								MHelper::runThreadSOCKET('/'.$this->route.'/account_id/' . $accountInfo['id'] . '/cat_id/' . $cat['cat_id'] .'/property_id/' . $attr['property_id']);
								sleep(1);
							}
						}
					}
				}
			}
		}
	}
	/**
	 * @desc 获取品牌列表
	 * @throws Exception
	 */
	public function actionGetbrand(){
		$accountId = Yii::app()->request->getParam('account_id');
		$status = 1;
		if($accountId){
			try{
				$jdLogModel = new JdLog;
				$logID = $jdLogModel->prepareLog($accountId, JdBrand::EVENT_GET_BRAND);
				if(!$logID){
					throw new Exception('LogID Not Insert');
				}
				$checkRunning = $jdLogModel->checkRunning($accountId, JdBrand::EVENT_GET_BRAND);
				if(!$checkRunning){
					throw new Exception(Yii::t('systems', 'There Exists An Active Event'));
				}
				$eventLog = $jdLogModel->saveEventLog(JdBrand::EVENT_GET_BRAND,
						array(
								'log_id'        => $logID,
								'account_id'    => $accountId,
								'start_time'    => date("Y-m-d H:i:s"),
								'end_time'      => date("Y-m-d H:i:s")
						));
				$jdLogModel->setRunning($logID);
				$getBrandRequest = new GetBrandRequest;
				$getBrandRequest->setAccount($accountId);
				$getBrandRequest->setStatus(1);
				$response = $getBrandRequest->setRequest()->sendRequest()->getResponse();
				if($getBrandRequest->getIfSuccess()){
					$response = json_decode(json_encode($response), true);
					if(!empty($response['jingdong_ept_vender_brand_get_responce']['getvenderbrand_result']['brandList'])){
						$brandList = $response['jingdong_ept_vender_brand_get_responce']['getvenderbrand_result']['brandList'];
						$jdBrand = new JdBrand;
						$jdBrand->saveBrandData($brandList, $accountId);
						echo "finished";
					}else{
						echo "No Brand Data";
					}
					$jdLogModel->setSuccess($logID);
					$jdLogModel->saveEventStatus(JdBrand::EVENT_GET_BRAND, $eventLog, JdLog::STATUS_SUCCESS);
				}else{
					$jdLogModel->setFailure($logID, $getBrandRequest->getErrorMsg());
					$jdLogModel->saveEventStatus(JdBrand::EVENT_GET_BRAND, $eventLog, JdLog::STATUS_FAILURE);
				}
				
			}catch (Exception $e){
				echo $e->getMessage();
			}
		}else{
			$accountList = JdAccount::model()->getAbleAccountList();
			foreach ($accountList as $accountInfo) {
				MHelper::runThreadSOCKET('/'.$this->route.'/account_id/' . $accountInfo['id']);
				sleep(1);
			}
		}
	}
	
	/**
	 * @desc 拉取运费模板
	 * @throws Exception
	 */
	public function actionGetfreighttemp(){
		$accountId = Yii::app()->request->getParam('account_id');
		$status = 1;
		if($accountId){
			try{
				$jdLogModel = new JdLog;
				$eventName = JdFreightTemplate::EVENT_NAME;
				$logID = $jdLogModel->prepareLog($accountId, $eventName);
				if(!$logID){
					throw new Exception('LogID Not Insert');
				}
				$checkRunning = $jdLogModel->checkRunning($accountId, $eventName);
				if(!$checkRunning){
					throw new Exception(Yii::t('systems', 'There Exists An Active Event'));
				}
				$eventLog = $jdLogModel->saveEventLog($eventName,
						array(
								'log_id'        => $logID,
								'account_id'    => $accountId,
								'start_time'    => date("Y-m-d H:i:s"),
								'end_time'      => date("Y-m-d H:i:s")
						));
				$jdLogModel->setRunning($logID);
				$pageSize = 20;
				$totalPage = $currentPage = 1;
				$jdRequest = new GetFreightTemplateRequest;
				do{
					$jdRequest->setAccount($accountId);
					$jdRequest->setPageSize($pageSize);
					$jdRequest->setCurrentPage($currentPage);
					$response = $jdRequest->setRequest()->sendRequest()->getResponse();
					if($jdRequest->getIfSuccess()){
						$response = json_decode(json_encode($response), true);
						if(isset($response['jingdong_ept_feight_outapi_query_responce']['querytemplate_result']['freightMap']['totalPage'])){
							$totalPage = $response['jingdong_ept_feight_outapi_query_responce']['querytemplate_result']['freightMap']['totalPage'];
						}
						if(!empty($response['jingdong_ept_feight_outapi_query_responce']['querytemplate_result']['freightMap']['freightTemp'])){
							$datas = $response['jingdong_ept_feight_outapi_query_responce']['querytemplate_result']['freightMap']['freightTemp'];
							$jdFreightModel = new JdFreightTemplate;
							$jdFreightModel->saveTemplateData($datas, $accountId);
						}
					}else{
						/* $jdLogModel->setFailure($logID, $jdRequest->getErrorMsg());
						$jdLogModel->saveEventStatus($eventName, $eventLog, JdLog::STATUS_FAILURE); */
					}
					++$currentPage;
				}while ($currentPage<$totalPage);
				$jdLogModel->setSuccess($logID);
				$jdLogModel->saveEventStatus($eventName, $eventLog, JdLog::STATUS_SUCCESS);
				echo "finish";
			}catch (Exception $e){
				echo $e->getMessage();
			}
		}else{
			$accountList = JdAccount::model()->getAbleAccountList();
			foreach ($accountList as $accountInfo) {
				MHelper::runThreadSOCKET('/'.$this->route.'/account_id/' . $accountInfo['id']);
				sleep(1);
			}
		}
	}
	/**
	 * @desc 拉取商家推荐模板
	 * @throws Exception
	 */
	public function actionGetrecommandtemp(){
		$accountId = Yii::app()->request->getParam('account_id');
		$status = 1;
		if($accountId){
			try{
				$jdLogModel = new JdLog;
				$eventName = JdRecommandTemplate::EVENT_NAME;
				$logID = $jdLogModel->prepareLog($accountId, $eventName);
				if(!$logID){
					throw new Exception('LogID Not Insert');
				}
				$checkRunning = $jdLogModel->checkRunning($accountId, $eventName);
				if(!$checkRunning){
					throw new Exception(Yii::t('systems', 'There Exists An Active Event'));
				}
				$eventLog = $jdLogModel->saveEventLog($eventName,
						array(
								'log_id'        => $logID,
								'account_id'    => $accountId,
								'start_time'    => date("Y-m-d H:i:s"),
								'end_time'      => date("Y-m-d H:i:s")
						));
				$jdLogModel->setRunning($logID);
				$pageSize = 20;
				$totalPage = $currentPage = 1;
				$jdRequest = new GetRecommendTemplateRequest;
				do{
					$jdRequest->setAccount($accountId);
					$jdRequest->setPageSize($pageSize);
					$jdRequest->setCurrentPage($currentPage);
					$response = $jdRequest->setRequest()->sendRequest()->getResponse();
					if($jdRequest->getIfSuccess()){
						$response = json_decode(json_encode($response), true);
						if(isset($response['jingdong_ept_warecenter_recommendtemp_get_responce']['getrecommendtempbyid_result']['totalCount'])){
							$totalCount = $response['jingdong_ept_warecenter_recommendtemp_get_responce']['getrecommendtempbyid_result']['totalCount'];
							$totalPage = ceil($totalCount/$pageSize);
						}
						if(!empty($response['jingdong_ept_warecenter_recommendtemp_get_responce']['getrecommendtempbyid_result']['wareTempList'])){
							$datas = $response['jingdong_ept_warecenter_recommendtemp_get_responce']['getrecommendtempbyid_result']['wareTempList'];
							$jdFreightModel = new JdRecommandTemplate;
							$jdFreightModel->saveTemplateData($datas, $accountId);
						}
					}else{
						/* $jdLogModel->setFailure($logID, $jdRequest->getErrorMsg());
							$jdLogModel->saveEventStatus($eventName, $eventLog, JdLog::STATUS_FAILURE); */
					}
					++$currentPage;
				}while ($currentPage<=$totalPage);
				$jdLogModel->setSuccess($logID);
				$jdLogModel->saveEventStatus($eventName, $eventLog, JdLog::STATUS_SUCCESS);
				echo "finish";
			}catch (Exception $e){
				echo $e->getMessage();
			}
		}else{
			$accountList = JdAccount::model()->getAbleAccountList();
			foreach ($accountList as $accountInfo) {
				MHelper::runThreadSOCKET('/'.$this->route.'/account_id/' . $accountInfo['id']);
				sleep(1);
			}
		}
	}
	/**
	 * @desc 拉取自定义属性
	 * @throws Exception
	 */
	public function actionGetcustomprop(){
		$accountId = Yii::app()->request->getParam('account_id');
		$status = 1;
		if($accountId){
			try{
				$jdLogModel = new JdLog;
				$eventName = JdCustompropTemplate::EVENT_NAME;
				$logID = $jdLogModel->prepareLog($accountId, $eventName);
				if(!$logID){
					throw new Exception('LogID Not Insert');
				}
				$checkRunning = $jdLogModel->checkRunning($accountId, $eventName);
				if(!$checkRunning){
					throw new Exception(Yii::t('systems', 'There Exists An Active Event'));
				}
				$eventLog = $jdLogModel->saveEventLog($eventName,
						array(
								'log_id'        => $logID,
								'account_id'    => $accountId,
								'start_time'    => date("Y-m-d H:i:s"),
								'end_time'      => date("Y-m-d H:i:s")
						));
				$jdLogModel->setRunning($logID);
				$pageSize = 20;
				$totalPage = $currentPage = 1;
				$jdRequest = new GetCustompropTemplateRequest;
				do{
					$jdRequest->setAccount($accountId);
					$jdRequest->setPageSize($pageSize);
					$jdRequest->setCurrentPage($currentPage);
					$response = $jdRequest->setRequest()->sendRequest()->getResponse();
					if($jdRequest->getIfSuccess()){
						$response = json_decode(json_encode($response), true);
						if(isset($response['jingdong_ept_warecenter_customprop_get_responce']['querycustomproptempletelist_result']['totalCount'])){
							$totalCount = $response['jingdong_ept_warecenter_customprop_get_responce']['querycustomproptempletelist_result']['totalCount'];
							$totalPage = ceil($totalCount/$pageSize);
						}
						if(!empty($response['jingdong_ept_warecenter_customprop_get_responce']['querycustomproptempletelist_result']['tplList'])){
							$datas = $response['jingdong_ept_warecenter_customprop_get_responce']['querycustomproptempletelist_result']['tplList'];
							$jdFreightModel = new JdCustompropTemplate;
							$jdFreightModel->saveTemplateData($datas, $accountId);
						}
					}else{
						/* $jdLogModel->setFailure($logID, $jdRequest->getErrorMsg());
						 $jdLogModel->saveEventStatus($eventName, $eventLog, JdLog::STATUS_FAILURE); */
					}
					++$currentPage;
				}while ($currentPage<=$totalPage);
				$jdLogModel->setSuccess($logID);
				$jdLogModel->saveEventStatus($eventName, $eventLog, JdLog::STATUS_SUCCESS);
				echo "finish";
			}catch (Exception $e){
				echo $e->getMessage();
			}
		}else{
			$accountList = JdAccount::model()->getAbleAccountList();
			foreach ($accountList as $accountInfo) {
				MHelper::runThreadSOCKET('/'.$this->route.'/account_id/' . $accountInfo['id']);
				sleep(1);
			}
		}
	}
	/**
	 * @desc 测试删除
	 * @throws Exception
	 */
	public function actionDelware(){
		$wareId = Yii::app()->request->getParam("ware_id");
		exit("forbidden");
		try{
			if(empty($wareId)){
				throw new Exception("no ware id");
			}
			$jdProductModel = new JdProduct();
			$wareInfo = $jdProductModel->find('ware_id=:id', array(':id'=>$wareId));
			$result = $jdProductModel->deleteWare($wareId, $wareInfo->account_id);
			echo "finish";
		}catch (Exception $e){
			echo $e->getMessage();
		}
	}

	/**
	 * @desc JDGJ批量调整5折价格，利润10%
	 * @link /jd/jdapirequest/reviseprice
	 */
	public function actionReviseprice(){
		exit("forbidden");
		set_time_limit(5*3600);
		error_reporting(E_ALL);
		ini_set("display_errors", true);
		$accountId = Yii::app()->request->getParam('account_id');
		$online_sku = Yii::app()->request->getParam('online_sku');
		//$wareId = Yii::app()->request->getParam('ware_id');
		//$skuId = Yii::app()->request->getParam('sku_id');

		if($accountId){

			$limit = 1000;
			$offset = 0;
			$jdProductVariantModel = new JdProductVariant();
			try {
				$jdLogModel = new JdLog;
				$eventName = "update_price_record";
				$logID = $jdLogModel->prepareLog($accountId, $eventName);
				if (!$logID) {
					throw new Exception('LogID Not Insert');
				}
				$checkRunning = $jdLogModel->checkRunning($accountId, $eventName);
				if (!$checkRunning) {
					throw new Exception(Yii::t('systems', 'There Exists An Active Event'));
				}
				$jdLogModel->setRunning($logID);
				do {
					$listing = $jdProductVariantModel->getVariantList($limit,$offset,$online_sku);
					//var_dump($listing);
					$offset += $limit;

					if($listing){
						$isContinue = true;
						foreach($listing as $key=>$variationInfo) {

							$jdProductPriceRecordModel = new JdProductPriceRecord();
							//检测是否有记录过
							$recondition = "online_sku=:online_sku and ware_id=:ware_id and account_id=:account_id and sku_id=:sku_id and status=:status";
							$reparams = array(
								':online_sku'	=>	$variationInfo['online_sku'],
								':ware_id'		=>	$variationInfo['ware_id'],
								':account_id'	=>	$accountId,
								':sku_id'		=>	$variationInfo['sku_id'],
								':status'		=>	1
							);
							$checkExists = $jdProductPriceRecordModel->getRevisePriceLogRow($recondition, $reparams);
							//如果是处于未恢复，直接跳过
							if($checkExists && $checkExists['restore_status'] == 0){
								continue;
							}

							//利润10%的价格
							$priceCal = new CurrencyCalculate();
							$priceCal->setProfitRate(0.1);//设置利润率
							$priceCal->setCurrency(JdProductAdd::PRODUCT_PUBLISH_CURRENCY);//币种
							$priceCal->setPlatform(Platform::CODE_JD);//设置销售平台
							$priceCal->setSku($variationInfo['online_sku']);//设置sku
							$profitPrice = $priceCal->getSalePrice();//获取卖价

							//商品五折后的价格
							$discountPrice =  sprintf("%.2f", $variationInfo['supply_price'] * 0.5);

							if($profitPrice != false && $profitPrice>=$discountPrice){
								$price = $profitPrice * 100;
							}else{
								$price = $discountPrice * 100;
							}
							$skuUpdateRequestModel = new SkuUpdateRequest;
							//$skuUpdateRequestModel->setVenderId(168302);
							$skuUpdateRequestModel->setSkuId($variationInfo['sku_id']);
							$skuUpdateRequestModel->setWareId($variationInfo['ware_id']);
							$skuUpdateRequestModel->SetRfId($variationInfo['online_sku']);
							$skuUpdateRequestModel->setSupplyPrice($price);
							$reponse = $skuUpdateRequestModel->setAccount($accountId)->setRequest()->sendRequest()->getResponse();
							$this->__print($skuUpdateRequestModel->setAccount($accountId)->setRequest());//die;

							//写入记录表
							$recordData = array(
								'account_id'	=>	$accountId,
								'sku'			=>	$variationInfo['sku'],
								'online_sku'	=>	$variationInfo['online_sku'],
								'ware_id'		=>	$variationInfo['ware_id'],
								'old_price'		=>	$variationInfo['supply_price'],
								'change_price'	=>	$price/100,
								'create_time'	=>	date("Y-m-d H:i:s"),
								'update_time'	=>	date("Y-m-d H:i:s"),
								'sku_id'		=>	$variationInfo['sku_id'],
								'restore_status'	=>	0
							);
							$errormsg = $skuUpdateRequestModel->getErrorMsg();
							if ($errormsg) {
								$jdLogModel->setFailure($logID, $errormsg);
							}

							if($skuUpdateRequestModel->getIfSuccess()){
								$recordData['status'] = 1;
								//$jdProductVariantModel->updateDisStatus($variationInfo['id']);
							}else{
								$recordData['status'] = 2;
								$recordData['last_message'] = $errormsg;
							}
							//$jdProductPriceRecordModel->addRecord($recordData);
						}
					}else{
						$isContinue = false;
					}
				}while($isContinue);
				$jdLogModel->setSuccess($logID);
			} catch (Exception $e) {
				if($logID){
					$jdLogModel->setFailure($logID, $e->getMessage());
				}
				echo $e->getMessage();
			}
		}else{
			$accountList = JdAccount::model()->getAbleAccountList();
			foreach ($accountList as $accountInfo) {
				MHelper::runThreadSOCKET('/'.$this->route.'/account_id/' . $accountInfo['id']);
				sleep(1);
			}
		}
	}

	/**
	 * @desc 恢复价格
	 * @link /jd/jdapirequest/restoreprice
	 */
	public function actionRestoreprice(){
		exit("forbidden");
		set_time_limit(5*3600);
		error_reporting(E_ALL);
		ini_set("display_errors", true);
		$accountId = Yii::app()->request->getParam("account_id");
		$online_sku = Yii::app()->request->getParam("online_sku");

		if($accountId){

			$limit = 1000;
			$offset = 0;
			$jdProductPriceRecordModel = new JdProductPriceRecord();
			try {
				$jdLogModel = new JdLog;
				$eventName = "restore_price_record";
				$logID = $jdLogModel->prepareLog($accountId, $eventName);
				if (!$logID) {
					throw new Exception('LogID Not Insert');
				}
				$checkRunning = $jdLogModel->checkRunning($accountId, $eventName);
				if (!$checkRunning) {
					throw new Exception(Yii::t('systems', 'There Exists An Active Event'));
				}
				$jdLogModel->setRunning($logID);
				do {
					//查询日志表
					$restoreList = $jdProductPriceRecordModel->getRestorePriceList($limit,$offset,$online_sku);
					//var_dump($restoreList);die;
					$offset += $limit;
					if($restoreList) {
						$isContinue = true;
						foreach($restoreList as $key=>$restoreInfo){

							//调用接口
							$skuUpdateRequestModel = new SkuUpdateRequest;
							$skuUpdateRequestModel->setSkuId($restoreInfo['sku_id']);
							$skuUpdateRequestModel->setWareId($restoreInfo['ware_id']);
							$skuUpdateRequestModel->SetRfId($restoreInfo['online_sku']);
							$skuUpdateRequestModel->setSupplyPrice($restoreInfo['old_price']*100);
							$reponse = $skuUpdateRequestModel->setAccount($accountId)->setRequest()->sendRequest()->getResponse();
							$this->__print($reponse);//die;

							$errormsg = $skuUpdateRequestModel->getErrorMsg();

							//更新日志表
							$recordData = array(
								'update_time'=>date("Y-m-d H:i:s"),
								'restore_price'=>$restoreInfo['old_price']
							);
							if($skuUpdateRequestModel->getIfSuccess()){
								$recordData['restore_status'] = 1;//成功
								$recordData['last_message'] = "success";
							}else{
								$recordData['restore_status'] = 2;//失败
								$recordData['last_message'] = $errormsg;
							}
							$jdProductPriceRecordModel->updateRecordByID($restoreInfo['id'], $recordData);
						}
					}else{
						$isContinue = false;
					}
				}while($isContinue);
				$jdLogModel->setSuccess($logID);
			} catch (Exception $e) {
				if($logID){
					$jdLogModel->setFailure($logID, $e->getMessage());
				}
				echo $e->getMessage();
			}
		}else{
			$accountList = JdAccount::model()->getAbleAccountList();
			foreach ($accountList as $accountInfo) {
				MHelper::runThreadSOCKET('/'.$this->route.'/account_id/' . $accountInfo['id']);
				sleep(1);
			}
		}
	}
}