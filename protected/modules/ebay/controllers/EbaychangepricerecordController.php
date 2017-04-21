<?php
/**
 * @desc ebay 调价记录
 * @author qzz
 * @since 2017-03-29
 */
class EbaychangepricerecordController extends UebController
{

    /**
     *列表页
     */
	public function actionList(){
		$model = new EbayChangePriceRecord();
		$this->render("list", array('model'=>$model));
	}

	/**
	 * 上传
	 */
	public function actionUploadPrice(){
		set_time_limit(3600);
		$ids = Yii::app()->request->getParam("ids");

		$errorMsg = "";
		if($ids){
			$idArr = explode(",", $ids);
			$ebayChangePriceModel = new EbayChangePriceRecord();
			foreach ($idArr as $id){

				$res = $ebayChangePriceModel->uploadPrice($id);

				if(!$res){
					$errorMsg .= "<br/>".$ebayChangePriceModel->getErrorMessage();
				}
			}
		}else{
			$errorMsg = "没有选择记录";
		}
		echo $this->successJson(array('message'	=>$errorMsg));
		Yii::app()->end();
	}

	/*
	 * 	添加调价记录
	 * 1、获取接口数据
	 * 2、是否达到调价条件
	 * 3、价格运行规则
	 * 4、进行调价
	 * 5、记录到数据库
	 * /ebay/ebaychangepricerecord/addchangepricerecord/account_id/3/sku/100818.03/limit/10/debug/1
	 */
	public function actionAddChangePriceRecord(){
		set_time_limit(4 * 3600);
		ini_set('display_errors', true);
		error_reporting(E_ALL);

		$accountID = Yii::app()->request->getParam('account_id');
		$sku = Yii::app()->request->getParam('sku');
		$debug = Yii::app()->request->getParam('debug');
		$limit = Yii::app()->request->getParam('limit');
		if($accountID){
			try {
				//如果是海外仓账号则直接退出
				if (in_array($accountID, EbayAccount::$OVERSEAS_ACCOUNT_ID)) {
					throw new Exception("Oversea account");
				}
				$logModel = new EbayLog();
				$eventName = "change_price_record";
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

				$ebayProductModel = new EbayProduct();
				$ebayProductVariantModel = new EbayProductVariation();
				$ebayChangePriceModel = new EbayChangePriceRecord();
				$productFieldChangeStatisticsModel = new ProductFieldChangeStatistics();

				//获取接口数据
				$date = date("Y-m-d", strtotime('-1 day'));//前天数据
				$whereReport = "report_time = '{$date}'";
				if($sku){
					$whereReport .= " and sku = '{$sku}'";
				}
				$report = $productFieldChangeStatisticsModel->getListByCondition('*',$whereReport);

				//判断条件,过滤sku
				$skuNewData = array();
				foreach($report as $k=>$info){
					$conditionType = $ebayChangePriceModel->changePriceCondition($info['type'],$info['new_field'],$info['last_field']);
					if($conditionType>0){
						$skuNewData[$info['sku']] = array(
							'condition_type'=>$conditionType,
							'last_field'=>$info['last_field'],
							'new_field'=>$info['new_field'],
							'change_type'=>$info['type'],
						);
					}
				}
				unset($report);
				if($debug){echo "skuNewData<pre>";print_r($skuNewData);echo "</pre>";}

				if(empty($skuNewData)){
					throw new Exception("no data");
				}

				//过滤出来新的sku，查找在线lisitng
				$command = $ebayProductModel->getDbConnection()->createCommand()
					->from($ebayProductModel->tableName() . " as t")
					->leftJoin($ebayProductVariantModel->tableName() . " p", "t.id=p.listing_id")
					->select("t.account_id,t.site_id,t.category_id,t.category_name,t.shipping_price,p.sku, p.sku_online, p.main_sku,p.item_id,p.sale_price as old_price,p.currency")
					->where("t.account_id='{$accountID}'")
					->andWhere('t.item_status=' . EbayProduct::STATUS_ONLINE)
					->andWhere("t.listing_type in('FixedPriceItem', 'StoresFixedPrice')")//非拍卖下
					->andWhere("p.sku in(" . MHelper::simplode(array_keys($skuNewData)) . ")");
				if($limit){
					$command->limit($limit);
				}
				$skuList = $command->queryAll();
				if($debug){echo "skuList<pre>";print_r($skuList);echo "</pre>";}

				if($skuList){
					foreach($skuList as $skuInfo){
						//海外仓跳过
						if (in_array($skuInfo['account_id'], EbayAccount::$OVERSEAS_ACCOUNT_ID)) {
							if($debug){echo "海外仓帐号<br>";}
							continue;
						}

						//判断今天是否已经运行过
						$isRun = $ebayChangePriceModel->checkHadRunToday($skuInfo['sku_online'],$skuInfo['account_id'],$skuInfo['site_id']);
						if ($isRun && $isRun['status'] == 1) {
							if($debug){echo $skuInfo['sku']."已经执行<br>";}
							continue;
						}

						//计算价格
						$skuInfo['condition_type'] = $skuNewData[$skuInfo['sku']]['condition_type'];
						$priceInfo = $ebayChangePriceModel->calculatePrice($skuInfo);
						if(empty($priceInfo)){
							if($debug){echo $skuInfo['sku']."价格获取失败或没达到改价条件<br>";}
							continue;
						}

						//达到条件调用接口
						$skuInfo['new_price'] = $priceInfo['new_price'];
						$result = $ebayChangePriceModel->changePrice($skuInfo);
						$errorMsg = $ebayChangePriceModel->getErrorMessage();

						$sellerUser = EbayProductSellerRelation::model()->getProductSellerRelationInfoByItemIdandSKU($skuInfo['item_id'], $skuInfo['sku'], $skuInfo['sku_online']);
						$skuInfo['last_field'] = $skuNewData[$skuInfo['sku']]['last_field'];
						$skuInfo['new_field'] = $skuNewData[$skuInfo['sku']]['new_field'];
						$skuInfo['change_type'] = $skuNewData[$skuInfo['sku']]['change_type'];

						//更新数据库

						//记录日志
						$logData = array(
							'item_id'			=>	$skuInfo['item_id'],
							'sku'				=>	$skuInfo['sku'],
							'sku_online'		=>	$skuInfo['sku_online'],
							'main_sku'			=>	$skuInfo['main_sku'],
							'account_id'		=>	$accountID,
							'site_id'			=>	$skuInfo['site_id'],
							'seller_user_id'	=>	$sellerUser ? $sellerUser['seller_id'] : 0,
							'old_price'			=>	$priceInfo['old_price'],
							'old_profit_rate'	=>	$priceInfo['old_profit_rate'],
							'new_price'			=>	$priceInfo['new_price'],
							'new_profit_rate'	=>	$priceInfo['new_profit_rate'],
							'type'				=>  $skuInfo['condition_type'],
							'last_product_cost'	=>  $skuInfo['change_type']==1 ? $skuInfo['last_field'] : 0,
							'new_product_cost'	=>  $skuInfo['change_type']==1 ? $skuInfo['new_field'] : 0,
							'last_product_weight'=> $skuInfo['change_type']==2 ? $skuInfo['last_field'] : 0,
							'new_product_weight' => $skuInfo['change_type']==2 ? $skuInfo['new_field'] : 0,
							'deal_date'			=>	date("Y-m-d"),
							'create_time'		=>	date("Y-m-d H:i:s"),
							'status'			=>	$result,
							'message'			=>	is_null($errorMsg) ? 'success' : $errorMsg,
							'update_user_id'	=>  0,
							'last_response_time'=>	date("Y-m-d H:i:s"),
							'run_count'			=>	isset($skuInfo['run_count']) ? ++$skuInfo['run_count'] : 1
						);

						if(!$isRun){
							$ebayChangePriceModel->saveData($logData);
						}else{
							unset($logData['create_time']);
							$ebayChangePriceModel->updateDataByID($logData, $isRun['id']);
						}
						unset($skuInfo);
						unset($priceInfo);
						unset($logData);
					}
				}
				$logModel->setSuccess($logID);
			} catch (Exception $e) {
				if ($logID) {
					$logModel->setFailure($logID, $e->getMessage());
				}
				echo $e->getMessage() . "<br/>";
			}
		}else{
			$ebayAccounts = EbayAccount::model()->getAbleAccountList();
			foreach($ebayAccounts as $account){
				//排除海外仓帐号
				if (in_array($account['id'], EbayAccount::$OVERSEAS_ACCOUNT_ID)) {
					continue;
				}
				MHelper::runThreadSOCKET('/'.$this->route.'/account_id/'.$account['id']);
				sleep(5);
			}
		}
	}

}