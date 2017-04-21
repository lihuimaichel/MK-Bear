<?php
/**
 * @desc ebay产品售价配置
 * @author lihy
 *
 */
class EbaysalepriceconfigController extends UebController{
	
	public function actionIndex(){
		$model = UebModel::model("EbayProductSalePriceConfig");
		$this->render("index", array(
					'model'	=>	$model
		));			
	}
	
	/**
	 * @desc 添加
	 */
	public function actionAdd(){
		$model = UebModel::model("EbayProductSalePriceConfig");
		if(Yii::app()->request->isAjaxRequest && isset($_POST['EbayProductSalePriceConfig'])){
			$model->attributes = $_POST['EbayProductSalePriceConfig'];
			$userId = Yii::app()->user->id;
			$model->setAttribute('opration_id', $userId);
			$model->setAttribute('opration_date', date('Y-m-d H:i:s'));
			//@todo 验证价格区间是否已经存在
			if ($model->validate()) {
				$model->setIsNewRecord(true);
				$flag = $model->save();
				if ($flag) {
					$forward = Yii::app()->createUrl("ebay/ebaysalepriceconfig/index");
					$jsonData = array(
							'message' => Yii::t('system', 'Save successful'),
							'forward' => $forward,
							'navTabId' => '',
							'callbackType' => 'closeCurrent'
					);
					echo $this->successJson($jsonData);
				}
			} else {
				$flag = false;
			}
			if (!$flag) {
				echo $this->failureJson(array(
						'message' => Yii::t('system', 'Save failure')));
			}
			Yii::app()->end();
		}
		$this->render("add", array('model'=>$model));
	}
	
	/**
	 * @desc 更新
	 */
	public function actionUpdate(){
		$id = Yii::app()->request->getParam('id');
		$model = UebModel::model("EbayProductSalePriceConfig")->findByPk($id);
		if(Yii::app()->request->isAjaxRequest && isset($_POST['EbayProductSalePriceConfig'])){
			$model->attributes = $_POST['EbayProductSalePriceConfig'];
			$userId = Yii::app()->user->id;
			$model->setAttribute('opration_id', $userId);
			$model->setAttribute('opration_date', date('Y-m-d H:i:s'));
			//@todo 验证价格区间是否已经存在
			if ($model->validate()) {
				$flag = $model->save();
				if ($flag) {
					$forward = Yii::app()->createUrl("ebay/ebaysalepriceconfig/index");
					$jsonData = array(
							'message' => Yii::t('system', 'Save successful'),
							'forward' => $forward,
							'navTabId' => '',
							'callbackType' => 'closeCurrent'
					);
					echo $this->successJson($jsonData);
				}
			} else {
				$flag = false;
			}
			if (!$flag) {
				echo $this->failureJson(array(
						'message' => Yii::t('system', 'Save failure')));
			}
			Yii::app()->end();
		}
		$this->render("update", array('model'=>$model));
	}


	/**
	 * 插入新的产品利润和利润率----每2小时运行一次
	 * /ebay/ebaysalepriceconfig/createprofit                            插入最新的产品，如果没有数据，从ueb_ebay_product表id>0开始
	 * /ebay/ebaysalepriceconfig/createprofit/startId/495/endId/10000    指定ueb_ebay_product表id开始和结束
	 */
	public function actionCreateprofit(){
		ini_set('memory_limit','2048M');
		set_time_limit(7000);     //2小时
		error_reporting(0);
		ini_set("display_errors", false);
		error_reporting(E_ALL);

		$startId = Yii::app()->request->getParam('startId');
		$endId = Yii::app()->request->getParam('endId');

		$ebayProductModel 			= new EbayProduct();
		$ebayProductVariationModel 	= new EbayProductVariation();
		$ebayProductProfitModel     = new EbayProductProfit();
		$ebaySalePriceConfigModel   = new EbayProductSalePriceConfig();
		$ebayCategoryModel   		= new EbayCategory();
		$getItemRequest 			= new GetItemRequest();

		$id = 0;
		try {

			$productWhere = 'id > 0';
			if($startId && $endId){
				$productWhere = 'id > '.$startId.' AND id < '.$endId;
			}else{
				//取出最大的id值
				// $pConditions = 'id > 0';
				// $pInfo = $ebayProductProfitModel->getOneByCondition('product_id', $pConditions, '', 'id desc');
				// if($pInfo){
				// 	$productWhere = 'id > '.$pInfo['product_id'];
				// }
				$productWhere = 'create_time >= "'.date('Y-m-d 00:00:00',strtotime('-1 day')).'"';
			}

			//取出产品信息
			$fields = 'id,item_id,account_id,sku,category_id,site_id,shipping_price,shipping_price_currency,current_price_currency';
			$ebayProductInfo = $ebayProductModel->getListByCondition($fields,$productWhere,'id asc');
			foreach ($ebayProductInfo as $key => $value) {
				//获取类目名称
				$categoryInfo = $ebayCategoryModel->getCategotyInfoByID($value['category_id'],$value['site_id']);
				if(!isset($categoryInfo['category_name']) || empty($categoryInfo['category_name'])){
					continue;
				}
				$categoryName = $categoryInfo['category_name'];

				//通过运费表获取运费信息
				$shippingPriceArray = array($value['shipping_price']);
				$shippingPriceArr = EbayProductShipping::model()->getShippingPriceByWhere("item_id = '".$value['item_id']."'");
				if($shippingPriceArr){
					$shippingPriceArray = array_unique($shippingPriceArr[$value['item_id']]);
				}

				$conditions = "listing_id={$value['id']}";
				$ebayProductVariationInfo = $ebayProductVariationModel->findAll($conditions);
				foreach ($ebayProductVariationInfo as $k => $v) {
					//根据条件判断利润表是否存在记录
					$profitConditions = "item_id=:item_id AND sku_online=:sku_online";
					$profitParam = array(':item_id'=>$v['item_id'], ':sku_online'=>$v['sku_online']);
					$productProfit = $ebayProductProfitModel->getOneByCondition('*', $profitConditions, $profitParam);

					//插入或者更新数据
					$insertFields = "id,product_id,item_id,category_id,site_id,account_id,main_sku,sku,sku_online,current_price,shipping_price,profit,profit_rate,update_time,create_time";
					if($productProfit){
						if($productProfit['current_price'] == $v['sale_price']) continue;
						$id = $productProfit['id'];
					}

					//计算利润和利润率
					$paramArr = array(
                        'sale_price'                => $v['sale_price'],
                        'sku'                       => $v['sku'],
                        'current_price_currency'    => $value['current_price_currency'],
                        'site_id'                   => $value['site_id'],
                        'account_id'                => $value['account_id'],
                        'category_name'             => $categoryName
                    );
                    $shipping_price = implode(',', $shippingPriceArray);
                    $profitAndProfitRateArr = $ebaySalePriceConfigModel->getProfitAndProfitRateByParam($shippingPriceArray,$paramArr);

                    $profit         = $profitAndProfitRateArr['profit'];
                    $profitRate     = $profitAndProfitRateArr['profit_rate'];

					$times = MHelper::getNowTime();

					$insertValue = "{$id},'{$value['id']}','{$v['item_id']}','{$value['category_id']}',{$value['site_id']},{$v['account_id']},'{$v['main_sku']}','{$v['sku']}','{$v['sku_online']}','{$v['sale_price']}','{$shipping_price}','{$profit}','{$profitRate}','{$times}','{$times}'";

					$ebayProductProfitModel->insertOrUpdate($insertFields,$insertValue);
					echo $value['id'].'<br>';
				}
			}

			echo '成功';

		}catch (Exception $e){
			echo $this->failureJson(array('message'=>$e->getMessage()));
			Yii::app()->end();
		}
			
	}


	/**
	 * 更新已有产品的利润和利润率
	 * /ebay/ebaysalepriceconfig/updateprofit
	 * /ebay/ebaysalepriceconfig/updateprofit/startId/4    从利润表的指定ID开始
	 */
	public function actionUpdateprofit(){
		ini_set('memory_limit','2048M');
		set_time_limit(3600);
		error_reporting(0);
		ini_set("display_errors", false);

		$startId = Yii::app()->request->getParam('startId');

		$ebayProductModel 			= new EbayProduct();
		$ebayProductVariationModel 	= new EbayProductVariation();
		$ebayProductProfitModel     = new EbayProductProfit();
		$ebaySalePriceConfigModel   = new EbayProductSalePriceConfig();
		$ebayCategoryModel   		= new EbayCategory();

		//插入或者更新字段
		$insertFields = "id,product_id,item_id,category_id,site_id,account_id,main_sku,sku,sku_online,current_price,shipping_price,profit,profit_rate,update_time,create_time";

		$productWhere = "f.current_price <> p.sale_price AND t.id <> ''";
		$id = 0;
		if($startId){
			$productWhere .= ' AND f.id > '.$startId;
		}
					
		try {

			//取出产品信息
			$fields = 't.id, p.item_id, p.sku, p.main_sku, p.sale_price, t.category_id, p.sku_online, t.site_id, t.account_id, p.currency,  t.shipping_price';
			$ebayProductVariationInfo = $ebayProductProfitModel->getDifferentPriceByCondition($fields,$productWhere,'','t.id asc');
			if(!$ebayProductVariationInfo){
				exit('没有记录');
			}

			foreach ($ebayProductVariationInfo as $key => $value) {
				$times = MHelper::getNowTime();

				//获取类目名称
				$categoryInfo = $ebayCategoryModel->getCategotyInfoByID($value['category_id'],$value['site_id']);
				if(!isset($categoryInfo['category_name']) || empty($categoryInfo['category_name'])){
					continue;
				}
				$categoryName = $categoryInfo['category_name'];

				//通过运费表获取运费信息
				$shippingPriceArray = array($value['shipping_price']);
				$shippingPriceArr = EbayProductShipping::model()->getShippingPriceByWhere("item_id = '".$value['item_id']."'");
				if($shippingPriceArr){
					$shippingPriceArray = array_unique($shippingPriceArr[$value['item_id']]);
				}

				//根据条件判断利润表是否存在记录
				$profitConditions = "item_id=:item_id AND sku_online=:sku_online";
				$profitParam = array(':item_id'=>$value['item_id'], ':sku_online'=>$value['sku_online']);
				$productProfit = $ebayProductProfitModel->getOneByCondition('id', $profitConditions, $profitParam);
				if($productProfit){
					$id = $productProfit['id'];
				}

				//计算利润和利润率
				$paramArr = array(
                    'sale_price'                => $value['sale_price'],
                    'sku'                       => $value['sku'],
                    'current_price_currency'    => $value['currency'],
                    'site_id'                   => $value['site_id'],
                    'account_id'                => $value['account_id'],
                    'category_name'             => $categoryName
                );
                $shipping_price = implode(',', $shippingPriceArray);
                $profitAndProfitRateArr = $ebaySalePriceConfigModel->getProfitAndProfitRateByParam($shippingPriceArray,$paramArr);

                $profit         = $profitAndProfitRateArr['profit'];
                $profitRate     = $profitAndProfitRateArr['profit_rate'];

				$insertValue = "{$id},'{$value['id']}','{$value['item_id']}','{$value['category_id']}',{$value['site_id']},{$value['account_id']},'{$value['main_sku']}','{$value['sku']}','{$value['sku_online']}','{$value['sale_price']}','{$shipping_price}','{$profit}','{$profitRate}','{$times}','{$times}'";

				$ebayProductProfitModel->insertOrUpdate($insertFields,$insertValue);
			}

			echo '成功';

		}catch (Exception $e){
			echo $this->failureJson(array('message'=>$e->getMessage()));
			Yii::app()->end();
		}
	}


	/**
	 * 根据运费表更新利润和利润率
	 * /ebay/ebaysalepriceconfig/shippingpriceupdateprofit    从利润表的指定ID开始
	 */
	public function actionShippingpriceupdateprofit(){
		ini_set('memory_limit','2048M');
		set_time_limit(3600);
		error_reporting(0);
		ini_set("display_errors", false);

		$where = 'update_status = 0';
		EbayProductShipping::model()->setProfitByShippingWhere($where, 'shipping_update_profit');
        echo '成功';
        
	}

	/*
	 * 更新ebay在线lisitng的利润，
	 * /ebay/ebaysalepriceconfig/updatelistingprofit/account_id/10
	 */
	public function actionUpdateListingProfit(){
		ini_set('memory_limit','2048M');
		set_time_limit(0);
		error_reporting(0);
		ini_set("display_errors", false);

		$ebayProductProfitModel     = new EbayProductProfit();
		$ebaySalePriceConfigModel   = new EbayProductSalePriceConfig();
		$ebayCategoryModel   		= new EbayCategory();
		$accountID = Yii::app()->request->getParam('account_id');
		$limit     = Yii::app()->request->getParam("limit",'1000');
		$offset = 0;
		$eventName = "update_product_profit";

		if($accountID){
			try {
				$logModel = new EbayLog();
				$logID = $logModel->prepareLog($accountID, $eventName);
				if(! $logID) throw new Exception("LOG ID create failure!!!");
				if(! $logModel->checkRunning($accountID, $eventName)){
					throw new Exception("Has a event exists");
				}
				$logModel->setRunning($logID);
				do {
					//查出在线的listing
					$productWhere = "t.item_status = " . EbayProduct::STATUS_ONLINE." AND t.account_id = ".$accountID;
					$fields = 't.id, p.item_id, p.sku, p.main_sku, p.sale_price, t.category_id, p.sku_online, t.site_id, t.account_id, p.currency,  t.shipping_price, f.shipping_price as old_ship_price,f.profit as old_profit,f.profit_rate as old_profit_rate';
					$ebayProductVariationInfo = $ebayProductProfitModel->getDifferentPriceByCondition($fields, $productWhere, '', 't.id asc', $limit, $offset);
					$offset += $limit;

					if ($ebayProductVariationInfo) {
						$isContinue = true;

						foreach ($ebayProductVariationInfo as $key => $value) {
							//获取类目名称
							$categoryInfo = $ebayCategoryModel->getCategotyInfoByID($value['category_id'], $value['site_id']);
							if (!isset($categoryInfo['category_name']) || empty($categoryInfo['category_name'])) {
								continue;
							}
							$categoryName = $categoryInfo['category_name'];

							//通过运费表获取运费信息
							$shippingPriceArray = array($value['shipping_price']);
							$shippingPriceArr = EbayProductShipping::model()->getShippingPriceByWhere("item_id = '".$value['item_id']."'");
							if ($shippingPriceArr) {
								$shippingPriceArray = array_unique($shippingPriceArr[$value['item_id']]);
							}
							$shipping_price = implode(',', $shippingPriceArray);

							//计算利润和利润率
							$paramArr = array(
								'sale_price' => $value['sale_price'],
								'sku' => $value['sku'],
								'current_price_currency' => $value['currency'],
								'site_id' => $value['site_id'],
								'account_id' => $value['account_id'],
								'category_name' => $categoryName
							);
							$profitAndProfitRateArr = $ebaySalePriceConfigModel->getProfitAndProfitRateByParam($shippingPriceArray, $paramArr);

							if($profitAndProfitRateArr['profit'] != false){
								//更新利润
								$updateData = array(
									'current_price' => $value['sale_price'],
									'shipping_price' => $shipping_price,
									'profit' => $profitAndProfitRateArr['profit'],
									'profit_rate' => $profitAndProfitRateArr['profit_rate'],
									'old_ship_price' => $value['old_ship_price'],
									'old_profit' => $value['old_profit'],
									'old_profit_rate' => $value['old_profit_rate'],
									'product_cost' => $profitAndProfitRateArr['product_cost'],//成本价
									'update_time' => date("Y-m-d H:i:s"),
								);
								$updateWhere = "item_id='{$value['item_id']}' AND sku_online='{$value['sku_online']}'";
								$ebayProductProfitModel->getDbConnection()->createCommand()->update($ebayProductProfitModel->tableName(),$updateData,$updateWhere);
							}
							unset($paramArr);
							unset($updateData);
						}
					}else{
						$isContinue = false;
					}
				}while($isContinue);

				$logModel->setSuccess($logID);
			}catch (Exception $e){
				if($logID){
					$logModel->setFailure($logID, $e->getMessage());
				}
				echo $e->getMessage();
			}
		}else{
			$ebayAccounts = EbayAccount::model()->getAbleAccountList();
			foreach($ebayAccounts as $account){
				MHelper::runThreadSOCKET('/'.$this->route.'/account_id/'.$account['id']);
				sleep(5);
			}
		}

	}
}