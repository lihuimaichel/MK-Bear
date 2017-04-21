<?php
/**
 * @desc aliexpress 断货设置
 * @author hanxy
 * @since 2016-12-07
 */
class AliexpressoutofstockController extends UebController{

	public function actionList(){
		$this->render("list", array('model' => AliexpressOutofstock::model()));
	}


	/**
	 * @desc 设置断货处理,针对listing loction为本地仓发货
	 */
	public function actionSetoutofstock() {
		$ids = Yii::app()->request->getParam('ids');
		if (!$_POST || !$ids) {
			echo $this->failureJson(array('message'=> Yii::t('system', 'Illegal Operation')));
			Yii::app()->end();
		}

		//判断用户是否登录
		if(!isset(Yii::app()->user->id)){
			echo $this->failureJson(array('message'=> '刷新页面，请重新登陆'));
	        Yii::app()->end();
		}

		$aliProductVariationModel = new AliexpressProductVariation();
		$aliProductModel          = new AliexpressProduct();
		$aliOutofstockModel       = new AliexpressOutofstock();
		$aliOutofstockLogModel    = new AliexpressOutofstockLog();
		$skuArr                   = explode(',',$ids);
		$userId                   = (int)Yii::app()->user->id;
		$nowTime                  = date('Y-m-d H:i:s');

		foreach ($skuArr as $sku){
			$command = $aliProductVariationModel->getDbConnection()->createCommand()
                        ->from($aliProductVariationModel->tableName() . " as t")
                        ->leftJoin($aliProductModel->tableName()." as p", "p.id=t.product_id")
                        ->select("t.id, t.sku, t.sku_id, p.aliexpress_product_id, t.ipm_sku_stock as stock, p.is_variation, p.account_id")
                        ->where("p.product_status_type='onSelling'")
                        ->andWhere("t.sku = '".$sku."'")
                        ->queryAll();

            //判断查询数据是否存在
            if(!$command){
            	continue;
            }

            $successNumber = 0;
            $failureNumber = 0;

            foreach ($command as $skuInfo) {
            	$productID   = $skuInfo['aliexpress_product_id'];
            	$skuID       = $skuInfo['sku_id'];
            	$accountID   = $skuInfo['account_id'];
            	$variationID = $skuInfo['id'];
            	$msg = $lastResult = '';
            	$stock = 1;
            	$logArr = array('sku'=>$sku, 'aliexpress_product_id'=>$productID, 'is_outofstock'=>1, 'ack'=>1, 'operator'=>$userId, 'operate_time'=>$nowTime);

            	if($skuInfo['is_variation'] == 1){   //多属性  库存改0，如不能改0修改为1
            		$stock = 0;
            		$editStockResult = $aliProductModel->editSingleSkuStockByParam($productID, $skuID, $stock, $accountID);
            		if($editStockResult){
            			$successNumber += 1;
            			$logArr['message'] = '修改库存为'.$stock.'成功';
            			$aliOutofstockLogModel->getDbConnection()
            			    ->createCommand()
            			    ->insert($aliOutofstockLogModel->tableName(), $logArr);

            			//更新多属性刊登表
            			$aliProductVariationModel->updateVariationById($variationID, array('ipm_sku_stock'=>$stock));
            		}else{
            			$stock = 1;
            			$editStockResult = $aliProductModel->editSingleSkuStockByParam($productID, $skuID, $stock, $accountID);
            			if($editStockResult){
            				$successNumber += 1;
            				$logArr['message'] = '修改库存为'.$stock.'成功';
	            			$aliOutofstockLogModel->getDbConnection()
	            			    ->createCommand()
	            			    ->insert($aliOutofstockLogModel->tableName(), $logArr);

	            			//更新多属性刊登表
	            			$aliProductVariationModel->updateVariationById($variationID, array('ipm_sku_stock'=>$stock));

            			}else{
            				$failureNumber += 1;
            				$logArr['message'] = $aliProductModel->getErrorMessage();
            				$logArr['ack']     = 0;
            				$aliOutofstockLogModel->getDbConnection()
	            			    ->createCommand()
	            			    ->insert($aliOutofstockLogModel->tableName(), $logArr);
            			}
            		}
            	}elseif ($skuInfo['is_variation'] == 0) {   //单品  库存改1
            		$editStockResult = $aliProductModel->editSingleSkuStockByParam($productID, $skuID, $stock, $accountID);
            		if($editStockResult){
            			$successNumber += 1;
        				$logArr['message'] = '修改库存为'.$stock.'成功';
            			$aliOutofstockLogModel->getDbConnection()
            			    ->createCommand()
            			    ->insert($aliOutofstockLogModel->tableName(), $logArr);

            			//更新多属性刊登表
            			$aliProductVariationModel->updateVariationById($variationID, array('ipm_sku_stock'=>$stock));
	            			
        			}else{
        				$failureNumber += 1;
        				$logArr['message'] = $aliProductModel->getErrorMessage();
        				$logArr['ack']     = 0;
        				$aliOutofstockLogModel->getDbConnection()
            			    ->createCommand()
            			    ->insert($aliOutofstockLogModel->tableName(), $logArr);
        			}
            	}else{
            		$failureNumber += 1;
            		$logArr['message'] = 'sku既不是单品也不是多属性';
    				$logArr['ack']     = 0;
    				$aliOutofstockLogModel->getDbConnection()
        			    ->createCommand()
        			    ->insert($aliOutofstockLogModel->tableName(), $logArr);
            	}
            }

			$row  = array('is_outofstock'=>1,'ack'=>1,'message'=>'断货设置成功:'.$successNumber.'个，失败:'.$failureNumber.'个','operator'=>$userId,'operate_time'=>date('Y-m-d H:i:s'));
			$info = $aliOutofstockModel->getOneByCondition('id',"sku='{$sku}'");
			if($info){
				$aliOutofstockModel->getDbConnection()->createCommand()->update($aliOutofstockModel->tableName(),$row,"sku='{$sku}'");
			}else{
				$row['sku'] = $sku;
				$aliOutofstockModel->getDbConnection()->createCommand()->insert($aliOutofstockModel->tableName(),$row);
			}
		}

		$jsonData = array(
			'message' =>Yii::t('system', 'Success Operation'),
			'navTabId'=>'page' . Menu::model()->getIdByUrl('/aliexpress/aliexpressoutofstock/list')
		);
		echo $this->successJson($jsonData);
	}

	/**
	 * @desc 取消断货处理,针对listing
	 */
	public function actionCanceloutofstock() {
		$ids = Yii::app()->request->getParam('ids');
		if (!$_POST || !$ids) {
			echo $this->failureJson(array('message'=> Yii::t('system', 'Illegal Operation')));
			Yii::app()->end();
		}

		//判断用户是否登录
		if(!isset(Yii::app()->user->id)){
			echo $this->failureJson(array('message'=> '刷新页面，请重新登陆'));
	        Yii::app()->end();
		}

		$aliProductVariationModel = new AliexpressProductVariation();
		$aliProductModel          = new AliexpressProduct();
		$aliOutofstockModel       = new AliexpressOutofstock();
		$aliOutofstockLogModel    = new AliexpressOutofstockLog();
		$skuArr                   = explode(',',$ids);
		$userId                   = (int)Yii::app()->user->id;
		$nowTime                  = date('Y-m-d H:i:s');

		foreach ($skuArr as $sku){
			$command = $aliProductVariationModel->getDbConnection()->createCommand()
                        ->from($aliProductVariationModel->tableName() . " as t")
                        ->leftJoin($aliProductModel->tableName()." as p", "p.id=t.product_id")
                        ->select("t.id, t.sku, t.sku_id, p.aliexpress_product_id, t.ipm_sku_stock as stock, p.is_variation, p.account_id")
                        ->where("p.product_status_type='onSelling'")
                        ->andWhere("t.sku = '".$sku."'")
                        ->queryAll();

            //判断查询数据是否存在
            if(!$command){
            	continue;
            }

            $successNumber = 0;
            $failureNumber = 0;

            foreach ($command as $skuInfo) {
            	$productID   = $skuInfo['aliexpress_product_id'];
            	$skuID       = $skuInfo['sku_id'];
            	$accountID   = $skuInfo['account_id'];
            	$variationID = $skuInfo['id'];
            	$msg = $lastResult = '';
            	$stock = 500;
            	$logArr = array('sku'=>$sku, 'aliexpress_product_id'=>$productID, 'is_outofstock'=>1, 'ack'=>1, 'operator'=>$userId, 'operate_time'=>$nowTime);

            	
            		$editStockResult = $aliProductModel->editSingleSkuStockByParam($productID, $skuID, $stock, $accountID);
            		if($editStockResult){
            			$successNumber += 1;
        				$logArr['message'] = '恢复库存为'.$stock.'成功';
            			$aliOutofstockLogModel->getDbConnection()
            			    ->createCommand()
            			    ->insert($aliOutofstockLogModel->tableName(), $logArr);

            			//更新多属性刊登表
            			$aliProductVariationModel->updateVariationById($variationID, array('ipm_sku_stock'=>$stock));
	            			
        			}else{
        				$failureNumber += 1;
        				$logArr['message'] = $aliProductModel->getErrorMessage();
        				$logArr['ack']     = 0;
        				$aliOutofstockLogModel->getDbConnection()
            			    ->createCommand()
            			    ->insert($aliOutofstockLogModel->tableName(), $logArr);
        			}
            }

			$row = array('is_outofstock'=>1,'ack'=>1,'message'=>'恢复断货设置成功:'.$successNumber.'个，失败:'.$failureNumber.'个','operator'=>$userId,'operate_time'=>date('Y-m-d H:i:s'));
			$aliOutofstockModel->getDbConnection()->createCommand()->update($aliOutofstockModel->tableName(),$row,"sku='{$sku}'");
		}

		$jsonData = array(
			'message' =>Yii::t('system', 'Success Operation'),
			'navTabId'=>'page' . Menu::model()->getIdByUrl('/aliexpress/aliexpressoutofstock/list')
		);
		echo $this->successJson($jsonData);
	}

}