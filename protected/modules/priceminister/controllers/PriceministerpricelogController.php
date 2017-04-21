<?php
/**
 * 
 * @author qzz
 *
 */
class PriceministerpricelogController extends UebController{
	public function actionList(){
		$this->render("list", array(
			"model"	=>	new PriceministerPriceLog()
		));
	}

	/*
	 * 	定时脚本,获取修改listing结果
	 * 	访问价格日志表数据拿到结果，成功更新数据
	 * 	/priceminister/priceministerpricelog/updatePriceLog/account_id/1/file_id/7209737
	 */
	public function actionUpdatePriceLog(){

		set_time_limit(3600);
		error_reporting(E_ALL);
		ini_set("display_errors", true);

		$fileID = Yii::app()->request->getParam('file_id');
		$accountID = Yii::app()->request->getParam('account_id');
		$limit = Yii::app()->request->getParam('limit',1000);
		$offset = 0;

		$pmPriceLogModel = new PriceministerPriceLog();
		$reportRequest = new GenericImportReportRequest();

		if($accountID){
			try{
				$logModel = new PriceministerLog();
				$eventName = "update_price_log";
				$logID = $logModel->prepareLog($accountID, $eventName);
				if(!$logID){
					throw new Exception("Create Log ID Failure");
				}
				//检测是否可以允许
				if(!$logModel->checkRunning($accountID, $eventName)){
					throw new Exception("There Exists An Active Event");
				}
				//设置运行
				$logModel->setRunning($logID);

				do{
					$command = $pmPriceLogModel->getDbConnection()->createCommand()
						->from($pmPriceLogModel->tableName() . " as t")
						->select("t.id, t.sku, t.import_id")
						->where('t.account_id = ' . $accountID)
						->andWhere('t.status = ' . PriceministerStockLog::STATUS_SUBMITTED)
						->andWhere('t.import_id <> 0');
					if($fileID){
						$command->andWhere("t.import_id = '".$fileID."'");
					}
					$command->limit($limit, $offset);
					$productList = $command->queryAll();

					$offset += $limit;
					if($productList){
						$isContinue = true;

						foreach($productList as $info){
							$reportRequest->setFileId($info['import_id']);
							//$reportResponse = $reportRequest->setRequest()->sendRequest()->getResponse();
							$reportResponse = $reportRequest->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
							//$this->print_r($reportResponse);

							if(!isset($reportResponse->response->product)){//上传中
								continue;
							}
							$response = $reportResponse->response->product;
							$productInfo = array();
							if(!isset($response[0])){
								$productInfo[] = $response;

							}elseif(isset($response) && $response){	//返回多个,多属性

								foreach ($response as $feed){
									$productInfo[] = $feed;
								}
							}

							$error_msg = '';
							foreach($productInfo as $k=>$v){
								if($v->status=='Erreur'){
									$errorInfo = $v->errors->error;
									if(!isset($errorInfo[0])){
										$error_key = $errorInfo->error_key;
										$error_code = $errorInfo->error_code;
										$error_text = $errorInfo->error_text;
										$fatal_error = $errorInfo->fatal_error;
										$error_msg .= '错误:'.$error_code.','.'信息:'.$error_key.'—'.$error_text.'—'.$fatal_error.'##';
									}elseif(isset($errorInfo) && $errorInfo){	//返回多个,多属性
										foreach ($errorInfo as $errVal){
											$error_key = $errVal->error_key;
											$error_code = $errVal->error_code;
											$error_text = $errVal->error_text;
											$fatal_error = $errVal->fatal_error;
											$error_msg .= '错误:'.$error_code.','.'信息:'.$error_key.'—'.$error_text.'—'.$fatal_error.'##';
										}
									}

								}else{
									$productId = $v->pid;
									$advertId = $v->aid;
									$sonSku = (string)$v->sku;
								}
							}
							if($error_msg==''){//成功
								$updateData = array(
									'status'=>PriceministerPriceLog::STATUS_SUCCESS,
									'msg'=>'ok',
									'update_time'=>date("Y-m-d H:i:s"),
								);
							}else{
								$updateData = array(
									'status'=>PriceministerPriceLog::STATUS_FAILURE,
									'msg'=>$error_msg,
									'update_time'=>date("Y-m-d H:i:s"),
								);
							}
							//更新日志
							$pmPriceLogModel->getDbConnection()->createCommand()
								->update($pmPriceLogModel->tableName(), $updateData,"id = " . $info['id'] );
						}
					}else{
						$isContinue = false;
					}
				}while($isContinue);
				$logModel->setSuccess($logID);
			} catch (Exception $e) {
				if($logID){
					$logModel->setFailure($logID, $e->getMessage());
				}
				echo $e->getMessage()."<br/>";
			}
		}else{
			$pmAccounts = PriceministerAccount::model()->getAbleAccountList();
			foreach($pmAccounts as $account){
				//echo '/'.$this->route.'/account_id/'.$account['id'];
				MHelper::runThreadSOCKET('/'.$this->route.'/account_id/'.$account['id']);
				sleep(5);
			}
		}
	}
}