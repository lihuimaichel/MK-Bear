<?php
/**
 * @desc 国内仓促销产品符合自动调0数据检测和导出
 * @author qzz
 *
 */
class WishlocalzerostockController extends UebController{


	/**
	 * 列表
	 */
	public function actionList(){
		$request = http_build_query($_POST);
		$model = new WishLocalZeroStock();
		$this->render('list',array('model'=>$model,'request'=>$request));
	}

	/*
	 * 检测数据
	 */
	public function actionCheckLocalZero(){

		$WishLocalZeroStockModel = new WishLocalZeroStock();

		if(empty($_GET['create_time'][0]) && empty($_GET['create_time'][1])){
			echo $this->failureJson(array('message'=>'必须指定时间范围导出，且不能超过5000条'));
			Yii::app()->end();
		}

		$conditions = 't.id>:id';
		$params[':id'] = 0;

		if(isset($_GET['create_time']) && $_GET['create_time']){
			if($_GET['create_time'][0]){
				$conditions .= ' and create_time > :create_time0';
				$params[':create_time0'] = trim($_GET['create_time'][0]." 00:00:00");
			}
			if($_GET['create_time'][1]){
				$conditions .= ' and create_time < :create_time1';
				$params[':create_time1'] = trim($_GET['create_time'][1]." 00:00:00");
			}
		}

		//查询是否查过5000条
		$count = $WishLocalZeroStockModel->count($conditions,$params);
		if($count<=0){
			echo $this->failureJson(array('message'=>'没有符合条件的数据'));
			Yii::app()->end();
		}
		if($count>5000){
			echo $this->failureJson(array('message'=>'数据已经超过5000条'));
			Yii::app()->end();
		}

		echo $this->successJson(array('message'=>'ok'));
		Yii::app()->end();
	}


	/*
	 * 导出数据
	 */
	public function actionExportLocalZero(){
		set_time_limit(3600);
		ini_set('display_errors', true);
		ini_set('memory_limit', '512M');

		$WishLocalZeroStockModel = new WishLocalZeroStock();
		$wishProductVariantModel = new WishVariants();
		$accountList = WishAccount::model()->getIdNamePairs();

		if(empty($_GET['create_time'])){
			echo $this->failureJson(array('message'=>'必须指定时间范围导出，且不能超过5000条'));
			Yii::app()->end();
		}

		$conditions = 't.id>:id';
		$params[':id'] = 0;

		if(isset($_GET['create_time']) && $_GET['create_time']){
			if($_GET['create_time'][0]){
				$conditions .= ' and create_time > :create_time0';
				$params[':create_time0'] = trim($_GET['create_time'][0]." 00:00:00");
			}
			if($_GET['create_time'][1]){
				$conditions .= ' and create_time < :create_time1';
				$params[':create_time1'] = trim($_GET['create_time'][1]." 00:00:00");
			}
		}

		//查询是否查过5000条
		$count = $WishLocalZeroStockModel->count($conditions,$params);
		if($count<=0){
			echo $this->failureJson(array('message'=>'没有符合条件的数据'));
			Yii::app()->end();
		}
		if($count>5000){
			echo $this->failureJson(array('message'=>'数据已经超过5000条'));
			Yii::app()->end();
		}

		//查询记录表
		$list = $WishLocalZeroStockModel->getDbConnection()->createCommand()
			->select('t.*')
			->from($WishLocalZeroStockModel->tableName()." as t")
			->where($conditions, $params)
			->queryAll();

		$str = "账号名称,product_Id,SKU,父在线sku,在线子sku,系统子sku,创建时间\n";
		foreach ($list as $key => $value) {
			$accountName = isset($accountList[$value['account_id']])?$accountList[$value['account_id']]:'';
			$str .= "\t".$accountName.
				",\t".trim($value['product_id']).
				",\t".$value['parent_sys_sku'].
				",\t".$value['parent_sku'].
				",\t".$value['online_sku'].
				",\t".$value['sku'].
				",\t".$value['create_time']."\n";
		}

		//导出文档名称
		$exportName = 'wish_ 国内仓促销产品调0数据_导出表'.date('Y-m-dHis').'.csv';
		$this->export_csv($exportName,$str);
		exit;
	}



	/**
	 * @desc 添加数据（国内仓，促销）
	 * @link /wish/wishlocalzerostock/addlocalzerostock/account_id/xx/sku/xxx/limit/xx/bug/1/norun/1
	 */
	public function actionAddLocalZeroStock(){
		set_time_limit(4*3600);
		ini_set('display_errors', true);
		error_reporting(E_ALL);

		$accountIDs = Yii::app()->request->getParam('account_id');
		$sku = Yii::app()->request->getParam('sku');
		$limit = Yii::app()->request->getParam('limit');
		$bug = Yii::app()->request->getParam('bug');
		$norun = Yii::app()->request->getParam('norun');
		$type = 0;
		if($accountIDs){
			$accountIDArr = explode(",", $accountIDs);
			$allowWarehouse = "41";//国内仓
			foreach ($accountIDArr as $accountID){
				$wareSkuMapModel = new WarehouseSkuMap();
				$wishProductModel = new WishProduct();
				$wishProductVariantModel = new WishVariants();
				$WishLocalZeroStockModel = new WishLocalZeroStock();
				try{
					$beforeTime = date("Y-m-d H:i:s", time()-90*24*3600);
					$fourFiveDayBeforeTime = date("Y-m-d H:i:s", time()-45*24*3600);
					$bakDay = 3;
					$nowTime = time();
					if($nowTime<strtotime("2017-02-15 00:00:00")){	//2月6号前可用库存<1, 调0
						$conditions = "t.available_qty < IFNULL(s.day_sale_num,1) AND p.product_status not in (6,7) AND t.warehouse_id in(".$allowWarehouse.")";
					}else{	//2月6号恢复此规则
						$conditions = "t.available_qty < 1 AND t.warehouse_id in(".$allowWarehouse.") AND p.product_bak_days>{$bakDay} AND (p.create_time<='{$beforeTime}' OR (qe.qe_check_result=1 and qe.qe_check_time<='{$fourFiveDayBeforeTime}'))"; //lihy modify 2016-10-14
					}
					$method = "getSkuListLeftJoinProductAndQERecordByCondition";
					$select = "t.sku";
					if($bug){
						echo "<br>condition:{$conditions}<br/>";
					}
					$logModel = new WishLog();
					$eventName = 'local_zero_stock';
					$logID = $logModel->prepareLog($accountID, $eventName);
					if(!$logID){
						throw new Exception("Create Log ID fail");
					}

					if(!$limit)
						$limit = 1000;
					$offset = 0;
					do{
						$command = $wishProductVariantModel->getDbConnection()->createCommand()
							->from($wishProductVariantModel->tableName() ." AS t" )
							->leftJoin($wishProductModel->tableName() . " AS p", "p.id=t.listing_id")
							->select("t.listing_id, t.sku, t.account_id, t.online_sku, t.inventory as product_stock, t.variation_product_id, p.product_id,p.parent_sku,p.sku as parent_sys_sku")
							->where("t.account_id=".$accountID)
							->andWhere("p.is_promoted=1")//促销
							->andWhere("t.enabled=1")
							->andWhere("t.inventory>0")
							->andWhere("p.warehouse_id in (".$allowWarehouse.")")	//国内仓
							->limit($limit, $offset);
						$offset += $limit;
						if($sku){
							$skus = explode(",", $sku);
							$command->andWhere(array("IN", "t.sku", $skus));
						}
						$variantListing = $command->queryAll();

						if($bug){
							echo "<br/>======variantListing======<br/>";
							print_r($variantListing);
						}
						if($variantListing){
							if($bug){
								$isContinue = false;
							}else{
								$isContinue = true;
							}
							$listing = array();
							foreach ($variantListing as $variant){
								//检测是否海外仓
								if(WishOverseasWarehouse::model()->getWarehouseInfoByProductID($variant['product_id'])){
									continue;
								}
								$listing[] = $variant;
							}
							unset($variantListing);
							$skuMapArr = array();
							$skuMapList = array();
							if($bug){
								echo "<br/>======Listing======<br/>";
								print_r($listing);
							}
							if(!$listing){
								continue;
							}
							foreach ($listing as $list){
								$skuMapArr[] = $list['sku'];
								$key = $list['variation_product_id']."-".$list['online_sku'];
								$skuMapList[$list['sku']][$key] = $list;
							}

							$conditions1 = $conditions;
							$conditions1 .= " AND t.sku in(".MHelper::simplode($skuMapArr).")";
							if($nowTime<strtotime("2017-02-15 00:00:00")){//2月6号前用此规则
								$skuSalesTable = "ueb_sync.ueb_sku_sales";
								$command = $wareSkuMapModel->getDbConnection()->createCommand()
									->from($wareSkuMapModel->tableName() . " as t")
									->leftJoin("ueb_product." . Product::model()->tableName() . " as p", "p.sku=t.sku")
									->leftJoin($skuSalesTable . " as s", "s.sku=t.sku")
									->where($conditions1)
									->select($select)
									->order("t.available_qty asc");
								$skuList = $command->queryAll();
							}else {
								$skuList = $wareSkuMapModel->$method($conditions1, array(), '', $select);
							}
							if($bug){
								echo "<br/>============skuList==========<br/>";
								print_r($skuList);
							}
							if(!$skuList){
								continue;
							}
							$newListing = array();
							foreach ($skuList as $list){
								if(isset($skuMapList[$list['sku']])){
									foreach ($skuMapList[$list['sku']] as $key=>$val){
										$newListing[$key] = $val;
									}

								}
							}
							if($bug){
								echo "<br>=========newListing=========<br/>";
								print_r($newListing);
							}
							if(!$newListing){
								continue;
							}
							if($bug){
								echo "<br/>=========begin:foreach=========<br/>";
							}
							if($norun){
								echo "<br/>======norun========<br/>";
								continue;//不执行运行
							}
							foreach ($newListing as $list){
								if($bug){
									echo "<br/>========list==========<br/>";
									var_dump($list);
								}
								//获取最新记录
								$lastRecord = $WishLocalZeroStockModel->getLastOneByCondition(
									"variation_product_id=:variation_product_id and online_sku=:online_sku and account_id=:account_id",
									array(':variation_product_id'=>$list['variation_product_id'], ':online_sku'=>$list['online_sku'], ':account_id'=>$accountID)
								);
								if($lastRecord){
									continue;
								}
								//写记录
								$addData = array(
									'listing_id'=> $list['listing_id'],
									'product_id'=> $list['product_id'],
									'parent_sku'=> $list['parent_sku'],
									'parent_sys_sku'=> $list['parent_sys_sku'],
									'variation_product_id'=> $list['variation_product_id'],
									'online_sku'=>	$list['online_sku'],
									'sku'		=>	$list['sku'],
									'account_id'=>	$accountID,
									'old_quantity'=>$list['product_stock'],
									'create_time'=>	date("Y-m-d H:i:s"),
									'remark'=>	'',
								);
								$WishLocalZeroStockModel->saveData($addData);
							}
							if($bug){
								echo "<br/>=========end:foreach=========<br/>";
							}
						}else{
							$isContinue = false;
						}
					}while($isContinue);
					$logModel->setSuccess($logID, "success");
				}catch (Exception $e){
					if(isset($logID) && $logID){
						$logModel->setFailure($logID, $e->getMessage());
					}
					if($bug){
						echo "<br/>=====Failuer======<br/>";
						echo $e->getMessage()."<br/>";
					}
				}
			}
		}else{
			//循环每个账号发送一个拉listing的请求
			$accountList = WishAccount::model()->getIdNamePairs();
			foreach($accountList as $accountID=>$accountName){
				MHelper::runThreadSOCKET('/'.$this->route.'/account_id/' . $accountID);
				sleep(1);
			}
		}
	}



	/**
	 * @desc 批量更新
	 * @throws Exception
	 */
	public function actionBatchupdate(){
		try{
			$ids = Yii::app()->request->getParam("ids");

			if(empty($ids)){
				throw new Exception("参数不对");
			}
			$idArr = explode(",", $ids);
			$res = WishLocalZeroStock::model()->updateByIds($idArr);
			if(!$res){
				throw new Exception("操作失败");
			}
			echo $this->successJson(array('message'=>'操作成功'));
		}catch(Exception $e){
			echo $this->failureJson(array('message'=>$e->getMessage()));
		}
	}


	/**
	 * 单个更新
	 */
	public function actionUpdateStatus(){
		$model = new WishLocalZeroStock();
		$id = Yii::app()->request->getParam('id');
		$status = Yii::app()->request->getParam('status');
		$remark = Yii::app()->request->getParam('remark');
		$userID = Yii::app()->user->id;

		if($_POST){
			$updateData = array(
				"status"=>$status,
				"remark"=>$remark,
				"update_user_id"=>$userID,
			);
			$model->getDbConnection()->createCommand()->update($model->tableName(), $updateData, "id=".$id);

			$jsonData = array(
				'message' => '更改成功',
				'forward' =>'/wish/wishlocalzerostock/list',
				'navTabId'=> 'page' .Menu::model()->getIdByUrl('/wish/wishlocalzerostock/list'),
				'callbackType'=>'closeCurrent'
			);
			echo $this->successJson($jsonData);
			exit;
		}

		$info = $model->getDbConnection()->createCommand()
			->select("status,remark")
			->from($model->tableName())
			->where("id = ".$id)
			->queryRow();
		$this->render("updateStatus",array('model' => $model,'id' => $id,'info' => $info));
	}
}