<?php
/**
 * @desc lazada 调价记录
 * @author hanxy
 * @since 2017-03-31
 */
class LazadachangepricerecordController extends UebController
{

	/** @var object 模型实例 **/
	protected $_model = NULL;
	
	/**
	 * (non-PHPdoc)
	 * @see CController::init()
	 */
	public function init() {
		$this->_model = new LazadaChangePriceRecord();
	}

    /**
     *列表页
     */
	public function actionList(){
		$this->render("list", array('model'=>$this->_model));
	}

	/*
	 * 手动提交操作
	 */
	public function actionUploadprice(){
		set_time_limit(4 * 3600);
		ini_set('display_errors', true);
		error_reporting(E_ALL);

		$ids = Yii::app()->request->getParam('ids');
		if(!$ids){
			throw new Exception("请选择");			
		}

		//连表查询数据
		$lazadaProductModel = new LazadaProduct();
		$command = $this->_model->getDbConnection()->createCommand()
			->from($this->_model->tableName() . " c")
			->select("c.id,c.sku,c.product_id,c.account_auto_id,c.account_id,c.site_id,c.seller_sku,c.parent_sku,t.sale_start_date,t.sale_end_date,c.type,c.new_price,c.status,c.create_time")
			->leftJoin($lazadaProductModel->tableName() . " t", 'c.product_id = t.product_id')
			->where(array("IN", "c.id", $ids))
			->andWhere("c.status <> 1")
			->order('id desc');
		$changePriceInfo = $command->queryAll();
		if(!$changePriceInfo){
			throw new Exception('没有找到数据');
		}

		$productArr = array();
		foreach ($changePriceInfo as $skuInfo) {
			//达到条件调用修改价格接口
			$createTime = strtotime($skuInfo['create_time']) + 3*24*3600;
			if(time() > $createTime){
				$msg = $skuInfo['sku'].'超过3天不能再次上传了！';
				echo $this->failureJson(array('message'=>$msg));
				Yii::app()->end();
			}

			//如果3天内又有最新的记录，以最新数据为准，原来的未提交成功的数据都不能再次提交
			if(in_array($skuInfo['product_id'], $productArr)){
				continue;
			}

			$errormsg = null;
			$result = $this->_model->changePrice($skuInfo);
			if($result){
				$status = 1;
				$errormsg = '改价成功';
			}else{
				$status = 2;
				$errormsg = $this->_model->getErrorMessage();
			}

			$logData = array(
				'status'             => $status,
				'message'            => $errormsg,
				'last_response_time' => date('Y-m-d H:i:s')
			);
			$this->_model->updateDataByID($logData, $skuInfo['id']);

			$productArr[] = $skuInfo['product_id'];
		}

		echo $this->successJson(array('message'=>'上传完成'));
		Yii::app()->end();
	}

	/*
	 * 	添加调价记录
	 * 1、获取接口数据
	 * 2、是否达到调价条件
	 * 3、价格运行规则
	 * 4、进行调价
	 * 5、记录到数据库
	 * /lazada/lazadachangepricerecord/addchangepricerecord/account_auto_id/31
	 */
	public function actionAddchangepricerecord(){
		set_time_limit(4 * 3600);
		ini_set('display_errors', true);
		error_reporting(E_ALL);

		$accountID = Yii::app()->request->getParam('account_auto_id');
		$sku = Yii::app()->request->getParam('sku');
		if($accountID){
			try {
				//排除掉没有设置自动调价的账号
				$accountInfo = LazadaAccount::model()->findByPk($accountID);
				if($accountInfo && $accountInfo['is_change_price'] == LazadaAccount::CLOSE_CHANGE_PRICE){
					throw new Exception("此账号不进行自动调价");
				}

				$logModel = new LazadaLog();
				$eventName = LazadaLog::CHANGE_PRICE_RECORD;
				$logID = $logModel->prepareLog($accountID, $eventName);
				if (!$logID) {
					throw new Exception("Create Log ID Failure");
				}
				//检测是否可以允许
				if (!$logModel->checkRunning($accountID, $eventName)) {
					throw new Exception("There Exists An Active Event");
				}
				//设置运行
				$logModel->setRunning($logID);

				$lazadaProductModel = new LazadaProduct();
				$productFieldChangeStatisticsModel = new ProductFieldChangeStatistics();

				//获取接口数据
				
				$date = date("Y-m-d", strtotime('-1 day'));    //前一天日期

				//连表查询数据
				$command = $lazadaProductModel->getDbConnection()->createCommand()
					->from($lazadaProductModel->tableName() . " t")
					->select("t.id,t.sku,t.product_id,t.account_auto_id,t.account_id,t.site_id,t.seller_sku,t.parent_sku,t.sale_price,t.sale_start_date,t.sale_end_date,c.type,c.last_field,c.new_field")
					->leftJoin('market_statistics.'. $productFieldChangeStatisticsModel->tableName() . " c", 'c.sku = t.sku')
					->where("t.account_auto_id='{$accountID}'")
					->andWhere("c.report_time = '{$date}'");
				$skuList = $command->queryAll();

				if($skuList){
					foreach($skuList as $skuInfo){
						//自动改价一天运行一次，当天失败的可以允许重新运行一次，记录运行次数，超过2次的不再运行。
						$todayWhere = "sku = '{$skuInfo['sku']}' AND site_id = '{$skuInfo['site_id']}' AND product_id = '{$skuInfo['product_id']}' AND deal_date = '{$date}'";
						$RunTodayInfo = $this->_model->getOneByCondition('id,deal_date,run_count,status', $todayWhere);
						if($RunTodayInfo && $RunTodayInfo['run_count'] >= 2){
							continue;
						}

						//如果有记录，成功的跳过
						if($RunTodayInfo && $RunTodayInfo['status'] == LazadaChangePriceRecord::CHANGE_PRICE_STATUS_SUCCESS){
							continue;
						}
						
						//判断是计算价格还是计算重量，取出价格
						$priceInfo = $this->_model->changePriceCondition($skuInfo['type'], $skuInfo['sku'], $skuInfo['site_id'], $skuInfo['new_field'], $skuInfo['last_field']);
						$skuInfo['new_price'] = isset($priceInfo['price'])?$priceInfo['price']:0;
						$conditionType = isset($priceInfo['conditionType'])?$priceInfo['conditionType']:0;
						
						if($skuInfo['new_price'] <= 0){
							continue;
						}

						$oldPriceInfo = $this->_model->changePriceCondition(LazadaChangePriceRecord::TYPE_OLD_PRICE_NUMS, $skuInfo['sku'], $skuInfo['site_id'], null, $skuInfo['sale_price']);

						//达到条件调用修改价格接口
						$status = 0;
						$errormsg = null;
						$result = $this->_model->changePrice($skuInfo);
						if($result){
							$status = 1;
							$errormsg = '改价成功';
						}else{
							$status = 2;
							$errormsg = $this->_model->getErrorMessage();
						}

						$lastProductWeight = 0;
						$newProductWeight = 0;
						if($skuInfo['type'] == 1){
							$prodcutInfo = Product::model()->getProductBySku($skuInfo['sku'], 'product_weight');
							$lastProductWeight = $prodcutInfo['product_weight'];
							$newProductWeight = $prodcutInfo['product_weight'];
						}elseif ($skuInfo['type'] == 2) {
							$lastProductWeight = $skuInfo['last_field'];
							$newProductWeight = $skuInfo['new_field'];
						}

						//记录日志
						$sellerUser = LazadaProductSellerRelation::model()->getProductSellerRelationInfoByItemIdandSKU($skuInfo['product_id'], $skuInfo['sku'], $skuInfo['seller_sku']);

						$oldPriceRate = isset($oldPriceInfo['profitRate'])?$oldPriceInfo['profitRate']:0;
						$newPriceRate = isset($priceInfo['profitRate'])?$priceInfo['profitRate']:0;

						$logData = array(
							'sku'                 => $skuInfo['sku'],
							'seller_sku'          => $skuInfo['seller_sku'],
							'parent_sku'          => $skuInfo['parent_sku'],
							'product_id'          => $skuInfo['product_id'],
							'account_auto_id'     => $accountID, 
							'account_id'          => $skuInfo['account_id'],
							'site_id'             => $skuInfo['site_id'],
							'seller_user_id'      => $sellerUser ? $sellerUser['seller_id'] : 0,
							'old_price'           => $skuInfo['sale_price'],
							'old_profit_rate'     => $oldPriceRate * 100,
							'new_price'           => $skuInfo['new_price'],
							'new_profit_rate'     => $newPriceRate * 100,
							'type'                => $conditionType,
							'last_product_cost'   => $skuInfo['type']==1 ? $skuInfo['last_field'] : 0,
							'new_product_cost'    => $skuInfo['type']==1 ? $skuInfo['new_field'] : 0,
							'last_product_weight' => $lastProductWeight,
							'new_product_weight'  => $newProductWeight,
							'deal_date'           => $date,
							'create_time'         => date("Y-m-d H:i:s"),
							'status'              => $status,
							'message'             => is_null($errormsg) ? '' : $errormsg,
							'update_user_id'      => 1,
							'last_response_time'  => date("Y-m-d H:i:s"),
							'run_count'           => isset($RunTodayInfo['run_count']) ? ++$RunTodayInfo['run_count'] : 1
						);

						if(!$RunTodayInfo){
							$this->_model->saveData($logData);
						}else{
							unset($logData['create_time']);
							$this->_model->updateDataByID($logData, $RunTodayInfo['id']);
						}
						
						unset($skuInfo);
						unset($priceInfo);
						unset($logData);
					}
				}
				$logModel->setSuccess($logID);
			} catch (Exception $e) {
				if (isset($logID) && $logID) {
					$logModel->setFailure($logID, $e->getMessage());
				}
				echo $e->getMessage() . "<br/>";
			}
		}else{
			$lazadaAccounts = LazadaAccount::model()->getAbleAccountList();
			foreach($lazadaAccounts as $account){
				//排除没有设置自动调价的账号
				if ($account['is_change_price'] != LazadaAccount::OPEN_CHANGE_PRICE) {
					continue;
				}
				MHelper::runThreadSOCKET('/'.$this->route.'/account_auto_id/'.$account['id']);
				sleep(3);
			}
		}

	}
}