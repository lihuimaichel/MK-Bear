<?php
/**
 * @desc pm产品管理
 * @author qzz
 * @since 2017-03-24
 */
class PriceministerproductController extends UebController{

    /**
     * @desc 产品列表
     */
	public function actionIndex(){
    	$model = new PriceministerProduct();
    	$this->render("index", array('model'=>$model));
    }

	/**
	 * @desc 拉取listing
	 * /priceminister/priceministerproduct/getlisting/account_id/1
	 */
	public function actionGetListing(){
		set_time_limit(0);
		ini_set('memory_limit', '2048M');
		ini_set('display_errors', true);
		error_reporting(E_ALL);

		$accountID = trim(Yii::app()->request->getParam('account_id', ''));
		$pmProductModel = new PriceministerProduct();

		if ($accountID) {
			try{
				$logModel = new PriceministerLog();
				$eventName = PriceministerProduct::EVENT_NAME;
				$logID = $logModel->prepareLog($accountID, $eventName);
				if(!$logID){
					throw new Exception("Create Log ID Failure");
				}
				//检测是否可以允许
				if(!$logModel->checkRunning($accountID, $eventName)){
					//throw new Exception("There Exists An Active Event");
				}
				//设置运行
				$logModel->setRunning($logID);

				$isOk = $pmProductModel->setAccountID($accountID)->getPmListing();
				if ( $isOk ) {
					$logModel->setSuccess($logID);
				} else {
					throw new Exception($pmProductModel->getErrorMessage());
				}
				$flag = $isOk ? 'Success' : 'Failure';
				$result = json_encode($_REQUEST).'========'.$flag.'========'.$pmProductModel->getErrorMessage();
				echo $result;
			} catch (Exception $e) {
				if($logID){
					$logModel->setFailure($logID, $e->getMessage());
				}
				echo $e->getMessage()."<br/>";
			}

		} else {
			$pmAccounts = PriceministerAccount::model()->getAbleAccountList();
			foreach($pmAccounts as $account){
				MHelper::runThreadSOCKET('/'.$this->route.'/account_id/'.$account['id']);
				sleep(5);
			}
		}

		Yii::app()->end('finish');
	}

	/**
	 * 产品管理修改价格(单个)
	 */
	public function actionUpdatePrice()
	{
		$pmVariationModel = new PriceministerProductVariation();
		$variationID = Yii::app()->request->getParam('variationID');
		$variationInfo = $pmVariationModel->findByPk($variationID);
		if (empty($variationInfo)) {
			echo $this->failureJson(array('message' => '没有对应子SKU'));
			exit;
		}
		$pmProductArr = Yii::app()->request->getParam('PriceministerProductVariation');
		$salePrice = isset($pmProductArr['sale_price']) ? $pmProductArr['sale_price'] : 0;

		if ($_POST) {

			if (!is_numeric($salePrice) || $salePrice <= 0) {
				echo $this->failureJson(array('message' => '价格必须大于0'));
				exit;
			}

			$data[] = array(
				'listing_id' => $variationInfo['listing_id'],
				'sku' => $variationInfo['sku'],
				'price' => $salePrice
			);

			//1、提交接口
			$pmProductModel = new PriceministerProduct();
			$importID = $pmProductModel->updatePMListing($data);

			//2、记录日志表
			$priceData = array();
			$priceData['product_id'] = $variationInfo['product_id'];
			$priceData['sku'] = $variationInfo['sku'];
			$priceData['account_id'] = $variationInfo['account_id'];
			$priceData['old_price'] = $variationInfo['sale_price'];
			$priceData['new_price'] = $salePrice;
			$priceData['create_user_id'] = (int)Yii::app()->user->id;
			$priceData['create_time'] = date("Y-m-d H:i:s");
			$priceData['status'] = PriceministerPriceLog::STATUS_SUBMITTED;;
			if ($importID >0) {
				$priceData['msg'] = 'success';
				$priceData['import_id'] = $importID;
			} else {
				$priceData['msg'] = $pmProductModel->getErrorMessage();
			}
			$pmPriceLog = new PriceministerPriceLog();
			$pmPriceLog->saveData($priceData);

			$jsonData = array(
				'message' => '操作成功',
				'forward' => '/priceminister/priceministerproduct/list',
				'navTabId' => 'page' . PriceministerProduct::getIndexNavTabId(),
				'callbackType' => 'closeCurrent'
			);
			echo $this->successJson($jsonData);
			Yii::app()->end();
		}

		$this->render(
			"updateprice",
			array(
				'model' => $pmVariationModel,
				'variationID' => $variationID,
				'sku' => $variationInfo['sku'],
				'price' => $variationInfo['sale_price'],
				'accountID' => $variationInfo['account_id'],
			)
		);
	}

	/*
     * 批量修改价格
     */
	public function actionBatchUpdatePrice()
	{
		$ids = trim(Yii::app()->request->getParam('ids', ''));
		$this->render("batchupdateprice",
			array(
				'model' => new PriceministerProductVariation(),
				'ids' => $ids,
			)
		);
	}

	/*
     * 批量修改价格
     */
	public function actionSavePrice()
	{
		set_time_limit(3600);
		ini_set('display_errors', false);
		error_reporting(0);

		$pmVariationModel = new PriceministerProductVariation();
		$ids = trim(Yii::app()->request->getParam('ids', ''));
		$pmProductArr = Yii::app()->request->getParam('PriceministerProductVariation');
		$salePrice = isset($pmProductArr['sale_price']) ? $pmProductArr['sale_price'] : 0;

		try {
			if (trim($ids, ',') == '') {
				throw new Exception("没有选择 variants id");
			}
			if (!is_numeric($salePrice) || $salePrice <= 0) {
				echo $this->failureJson(array('message' => '价格必须大于0'));
				exit;
			}

			$ids = trim($ids, ',');
			$lists = $pmVariationModel->getListByCondition('sku,product_id,listing_id,account_id,sale_price', "id in({$ids})");

			if ($lists) {
				foreach ($lists as $k=>$variationInfo) {
					$lists[$k]['price'] = $salePrice;
				}
				//1、提交接口
				$pmProductModel = new PriceministerProduct();
				$importID = $pmProductModel->updatePMListing($lists);

				foreach ($lists as $variationInfo) {
					//2、记录日志表
					$priceData = array();
					$priceData['product_id'] = $variationInfo['product_id'];
					$priceData['sku'] = $variationInfo['sku'];
					$priceData['account_id'] = $variationInfo['account_id'];
					$priceData['old_price'] = $variationInfo['sale_price'];
					$priceData['new_price'] = $variationInfo['price'];;
					$priceData['create_user_id'] = (int)Yii::app()->user->id;
					$priceData['create_time'] = date("Y-m-d H:i:s");
					$priceData['status'] = PriceministerPriceLog::STATUS_SUBMITTED;;
					if ($importID >0) {
						$priceData['msg'] = 'success';
						$priceData['import_id'] = $importID;
					} else {
						$priceData['msg'] = $pmProductModel->getErrorMessage();
					}
					$pmPriceLog = new PriceministerPriceLog();
					$pmPriceLog->saveData($priceData);
				}
			} else {
				throw new Exception("没有找到符合条件的在线listing！");
			}
			echo $this->successJson(array('message' => '任务添加成功，等待后台执行'));
		} catch (Exception $e) {
			echo $this->failureJson(array('message' => $e->getMessage()));
		}
		Yii::app()->end();
	}

	/**
	 * 产品管理修改库存(单个)
	 */
	public function actionUpdateStock()
	{
		$pmVariationModel = new PriceministerProductVariation();
		$variationID = Yii::app()->request->getParam('variationID');
		$variationInfo = $pmVariationModel->findByPk($variationID);
		if (empty($variationInfo)) {
			echo $this->failureJson(array('message' => '没有对应子SKU'));
			exit;
		}
		$pmProductArr = Yii::app()->request->getParam('PriceministerProductVariation');
		$quantity = isset($pmProductArr['quantity_available']) ? $pmProductArr['quantity_available'] : '';

		if ($_POST) {

			if (!preg_match("/^(0|[1-9][0-9]*)$/", $quantity)) {//验证是否为数字
				echo $this->failureJson(array('message' => '库存数量不正确'));
				exit;
			}

			$data[] = array(
				'listing_id' => $variationInfo['listing_id'],
				'sku' => $variationInfo['sku'],
				'quantity' => $quantity
			);

			//1、提交接口
			$pmProductModel = new PriceministerProduct();
			$importID = $pmProductModel->updatePMListing($data);

			//2、记录日志表
			$stockData = array();
			$stockData['product_id'] = $variationInfo['product_id'];
			$stockData['sku'] = $variationInfo['sku'];
			$stockData['account_id'] = $variationInfo['account_id'];
			$stockData['old_quantity'] = $variationInfo['quantity_available'];
			$stockData['set_quantity'] = $quantity;
			$stockData['create_user_id'] = (int)Yii::app()->user->id;
			$stockData['create_time'] = date("Y-m-d H:i:s");
			$stockData['status'] = PriceministerStockLog::STATUS_SUBMITTED;
			if ($importID >0) {
				$stockData['msg'] = 'pending';
				$stockData['import_id'] = $importID;
			} else {
				$stockData['msg'] = $pmProductModel->getErrorMessage();
			}
			$pmStockLog = new PriceministerStockLog();
			$pmStockLog->saveData($stockData);

			$jsonData = array(
				'message' => '操作成功',
				'forward' => '/priceminister/priceministerproduct/list',
				'navTabId' => 'page' . PriceministerProduct::getIndexNavTabId(),
				'callbackType' => 'closeCurrent'
			);
			echo $this->successJson($jsonData);
			Yii::app()->end();
		}

		$this->render(
			"updatestock",
			array(
				'model' => $pmVariationModel,
				'variationID' => $variationID,
				'sku' => $variationInfo['sku'],
				'quantity' => $variationInfo['quantity_available'],
				'accountID' => $variationInfo['account_id'],
			)
		);
	}

	/*
     * 批量修改库存
     */
	public function actionBatchUpdateStock()
	{
		$ids = trim(Yii::app()->request->getParam('ids', ''));
		$this->render("batchupdatestock",
			array(
				'model' => new PriceministerProductVariation(),
				'ids' => $ids,
			)
		);
	}

	/*
     * 批量修改库存
     */
	public function actionSaveStock()
	{
		set_time_limit(3600);
		ini_set('display_errors', false);
		error_reporting(0);

		$pmVariationModel = new PriceministerProductVariation();
		$ids = trim(Yii::app()->request->getParam('ids', ''));
		$pmProductArr = Yii::app()->request->getParam('PriceministerProductVariation');
		$quantity = isset($pmProductArr['quantity_available']) ? $pmProductArr['quantity_available'] : '';

		try {
			if (trim($ids, ',') == '') {
				throw new Exception("没有选择 variants id");
			}
			if (!preg_match("/^(0|[1-9][0-9]*)$/", $quantity)) {//验证是否为数字
				throw new Exception("库存数量不正确");
			}

			$ids = trim($ids, ',');
			$lists = $pmVariationModel->getListByCondition('sku,product_id,listing_id,account_id,quantity_available', "id in({$ids})");

			if ($lists) {
				foreach ($lists as $k=>$variationInfo) {
					$lists[$k]['quantity'] = $quantity;
				}
				//1、提交接口
				$pmProductModel = new PriceministerProduct();
				$importID = $pmProductModel->updatePMListing($lists);

				foreach ($lists as $variationInfo) {
					//2、记录日志表
					$stockData = array();
					$stockData['product_id'] = $variationInfo['product_id'];
					$stockData['sku'] = $variationInfo['sku'];
					$stockData['account_id'] = $variationInfo['account_id'];
					$stockData['old_quantity'] = $variationInfo['quantity_available'];
					$stockData['set_quantity'] = $variationInfo['quantity'];
					$stockData['create_user_id'] = (int)Yii::app()->user->id;
					$stockData['create_time'] = date("Y-m-d H:i:s");
					$stockData['status'] = PriceministerStockLog::STATUS_SUBMITTED;
					if ($importID >0) {
						$stockData['msg'] = 'pending';
						$stockData['import_id'] = $importID;
					} else {
						$stockData['msg'] = $pmProductModel->getErrorMessage();
					}
					$pmStockLog = new PriceministerStockLog();
					$pmStockLog->saveData($stockData);
				}
			} else {
				throw new Exception("没有找到符合条件的在线listing！");
			}
			echo $this->successJson(array('message' => '任务添加成功，等待后台执行'));
		} catch (Exception $e) {
			echo $this->failureJson(array('message' => $e->getMessage()));
		}
		Yii::app()->end();
	}
} 