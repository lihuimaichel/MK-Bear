<?php
/**
 * @desc ebay
 * @author lihy
 *
 */
class WishproductsellerrelationController extends UebController {
	
	/** @var object 模型实例 **/
	protected $_model = NULL;
	
	/**
	 * (non-PHPdoc)
	 * @see CController::init()
	 */
	public function init() {
		$this->_model = new WishProductSellerRelation();
	}
	
	/**
	 * @desc 列表页
	 */
	public function actionList() {
		$request = http_build_query($_POST);

		//查询出搜索的总数
		$itemCount = $this->_model->search()->getTotalItemCount();

		$this->render("index", array("model"=>$this->_model, 'request'=>$request, 'itemCount'=>$itemCount));
	}
	
	public function actionImport() {
		set_time_limit(2*3600);
		error_reporting(E_ALL);
		ini_set("display_errors", true);
		if($_POST){
			set_time_limit(2*3600);
			ini_set('display_errors', true);
			ini_set('memory_limit', '256M');
			try{
				if(empty($_FILES['csvfilename']['tmp_name'])){
					throw new Exception("文件上传失败");
				}
				if($_FILES['csvfilename']['error'] != UPLOAD_ERR_OK){
					throw new Exception("文件上传失败, error:".$_FILES['csvfilename']['error']);
				}
				//限制下文件大小
				if($_FILES['csvfilename']['size'] > 2048000){
					echo $this->failureJson(array('message'=>"文件太大，在2M以下"));
					exit();
				}
				$file = $_FILES['csvfilename']['tmp_name'];
				
				
				$PHPExcel = new MyExcel();
				//excel处理
				Yii::import('application.vendors.MyExcel');
				$datas = $PHPExcel->get_excel_con($file);
				if(!empty($datas)){
					$wishProductSellerRelationModel = new WishProductSellerRelation();
					$sellerUserList = User::model()->getWishUserList();
					$sellerUserList = array_flip($sellerUserList);

					//获取账号列表
					$accountList = UebModel::model("WishAccount")->getIdNamePairs();
		
					//做日志
					foreach ($datas as $key=>$data){
						if($key == 1) continue;
						//@TODO 每个平台不一致
						$dataA = str_replace('"', '', $data['A']);
						$dataA = trim($dataA);
						$dataB = str_replace('"', '', $data['B']);
						$dataB = trim($dataB);
						$dataC = str_replace('"', '', $data['C']);
						$dataC = trim($dataC);

						$itemId 	= 	trim($dataA, "'");
						$sku 		= 	trim($dataB, "'");
						$onlineSku 	= 	trim($dataC, "'");
						$accountID 	=	$data['D'];
						$siteID		=	0;
						$sellerName = 	trim($data['F']);

						if(empty($itemId)){
							echo $this->failureJson(array('message'=>"在表格第{$key}行，ItemID为空"));
							exit();
						}

						if(empty($sku)){
							echo $this->failureJson(array('message'=>"在表格第{$key}行，sku为空"));
							exit();
						}

						if(empty($onlineSku)){
							echo $this->failureJson(array('message'=>"在表格第{$key}行，onlineSKU为空"));
							exit();
						}

						$existAccountID = isset($accountList[$accountID]) ? $accountList[$accountID] : '';
						if(empty($existAccountID)){
							echo $this->failureJson(array('message'=>"在表格第{$key}行，账号不存在或者该账号不属于这个平台"));
							exit();
						}

						$newSellerId = isset($sellerUserList[$sellerName]) ? $sellerUserList[$sellerName] : '';
						if(empty($newSellerId)){
							echo $this->failureJson(array('message'=>"在表格第{$key}行，销售人员账号不存在或者该账号不属于这个平台"));
							exit();
						}
					}

					foreach ($datas as $key=>$data){
						if($key == 1) continue;
						//@TODO 每个平台不一致
						$dataA = str_replace('"', '', $data['A']);
						$dataA = trim($dataA);
						$dataB = str_replace('"', '', $data['B']);
						$dataB = trim($dataB);
						$dataC = str_replace('"', '', $data['C']);
						$dataC = trim($dataC);

						$itemId 	= 	trim($dataA, "'");
						$sku 		= 	trim($dataB, "'");
						$onlineSku 	= 	trim($dataC, "'");
						$accountID 	=	$data['D'];
						$siteID		=	0;
						$sellerName = 	trim($data['F']);
						
						try{

							$newSellerId = isset($sellerUserList[$sellerName]) ? $sellerUserList[$sellerName] : '';
							//检测不够四位，不够的话前缀补零
							$sku = encryptSku::skuToFloat($sku);
							$onlineSku = encryptSku::skuToFloat($onlineSku);

							$insertData = array(
								'site_id'		=>	$siteID,
								'account_id'	=>	$accountID,
								'item_id'		=>	$itemId,
								'sku'			=>	$sku,
								'online_sku'	=>	$onlineSku,
							);

							//入库操作
							//检测是否存在
							if($existsId = $wishProductSellerRelationModel->checkUniqueRow($itemId, $sku, $onlineSku, $accountID, $siteID)){
								//存在更新
								$res = $wishProductSellerRelationModel->updateDataById($existsId, array('seller_id'=>$newSellerId));
								if(!$res){
									echo $this->failureJson(array('message'=>'Update Failure'));
									exit();
								}
							}else{//不存在插入
							
								$nowTime = date("Y-m-d H:i:s");
								$insertData['seller_id'] = $newSellerId;
								$insertData['create_time'] = $nowTime;
								$insertData['update_time'] = $nowTime;
								
								//@todo 准备改为批量添加的方式 lihy 0816
								$res = $wishProductSellerRelationModel->saveData($insertData);
								if(!$res){
									echo $this->failureJson(array('message'=>'Insert Into Failure'));
									exit();
								}
							}
						}catch (Exception $e){
							$insertData['status'] = 1;
							$insertData['error_msg'] = $e->getMessage();
							$insertData['seller_id'] = 0;
							$wishProductSellerRelationModel->writeProductSellerRelationLog($insertData);
						}
					}

				}
				echo $this->successJson(array('message'=>'success'));
				Yii::app()->end();
				exit;
			}catch (Exception $e){
				echo $this->failureJson(array('message'=>$e->getMessage()));
				Yii::app()->end();
			}
		}
		
		$this->render("upload");
		exit;
	}
	
	/**
	 * @desc 更改对应账号的销售人员
	 * @throws Exception
	 */
	public function actionBatchchangetoseller(){
		if($_POST){
			error_reporting(E_ALL);
			ini_set("display_errors", true);
			$logModel  = new WishLog();
			$eventName = 'batchchangetoseller';
			try{                
				$oldAccountId = Yii::app()->request->getParam('old_account_id');
				$oldSellerId = Yii::app()->request->getParam('old_seller_id');
				$newSellerId = Yii::app()->request->getParam('WishProductSellerRelation');
				if(empty($oldAccountId)){
					throw new Exception("没有选择原有账号");
				}
				if(empty($oldSellerId)){
					throw  new Exception("没有选择原有的销售人员");
				}
				if(empty($newSellerId)){
					throw  new Exception("没有选择替换的销售人员");
				}

				//写log
                $logID = $logModel->prepareLog($oldAccountId, $eventName);
                if(!$logID){
                    exit('日志写入错误');
                }
                //检测是否可以允许
                if(!$logModel->checkRunning($oldAccountId, $eventName)){
                    $logModel->setFailure($logID, Yii::t('system', 'There Exists An Active Event'));
                    exit('There Exists An Active Event');
                }

                //设置运行
                $logModel->setRunning($logID);

				if(!$this->_model->batchChangeSellerToOtherSeller($oldAccountId, $oldSellerId, $newSellerId['seller_id'])){
					throw new Exception("更改失败！");
				}

				$jsonData = array(
						'message' => '更改成功',
						'forward' =>'/wish/wishproductsellerrelation/list',
						'navTabId'=> 'page' .WishProductSellerRelation::getIndexNavTabId(),
						'callbackType'=>'closeCurrent'
				);

				$createUserId = isset(Yii::app()->user->id)?Yii::app()->user->id:0;
				$logModel->setSuccess($logID, "原销售人员ID:".$oldSellerId.'修改为:'.$newSellerId['seller_id'].'创建人为:'.$createUserId);
				echo $this->successJson($jsonData);

				// echo $this->successJson(array('message'=>'更改成功'));
			}catch (Exception $e){
				if(isset($logID) && $logID){
                    $logModel->setFailure($logID, $e->getMessage());
                }
				echo $this->failureJson(array('message'=>$e->getMessage()));
			}
			Yii::app()->end();
		}
		//获取账号列表
		$accountList = UebModel::model("WishAccount")->getIdNamePairs();
		//获取销售人员列表
		$sellerList = User::model()->getWishUserList();
		$allSellerList = User::model()->getWishUserList(true);
		$this->render("batchchangetoseller", array('model'=>$this->_model, 'accountList'=>$accountList, 'sellerList'=>$sellerList, 'allSellerList'=>$allSellerList));
		exit;
	}
	

	
	/**
	 * @desc 更新
	 * @throws Exception
	 */
	public function actionUpdate(){
		error_reporting(E_ALL);
		ini_set("display_errors", true);
		try{
			$id = Yii::app()->request->getParam("id");
			if(empty($id)) throw new Exception("参数不正确");
			$model = UebModel::model("WishProductSellerRelation")->findByPk($id);
			if(empty($model)){
				throw new Exception("不存在该数据");
			}
			$model->account_name = UebModel::model("WishAccount")->getAccountNameById($model->account_id);
			$this->render("update", array("model"=>$model, 'sellerList'=>User::model()->getWishUserList()));
		}catch (Exception $e){
			echo $this->failureJson(array('message'=>$e->getMessage()));
		}
	}
	
	public function actionSavedata(){
		try{
			$id = Yii::app()->request->getParam("id");
			$wishProductSellerRelation = Yii::app()->request->getParam("WishProductSellerRelation");
			$sellerId = $wishProductSellerRelation['seller_id'];
			$sku = $wishProductSellerRelation['sku'];
			$onlineSku = $wishProductSellerRelation['online_sku'];
			if(empty($id) || empty($sellerId) || empty($sku) || empty($onlineSku)){
				throw new Exception("参数不对");
			}
			$res = UebModel::model("WishProductSellerRelation")->updateDataById($id, array('seller_id'=>$sellerId, 'sku'=>$sku, 'online_sku'=>$onlineSku));
			if(!$res){
				throw new Exception("操作失败");
			}

			$jsonData = array(
					'message' => '更改成功',
					'forward' =>'/lazada/lazadaproductsellerrelation/list',
					'navTabId'=> 'page' .WishProductSellerRelation::getIndexNavTabId(),
					'callbackType'=>'closeCurrent'
			);
			echo $this->successJson($jsonData);

			// echo $this->successJson(array('message'=>'更改成功'));
		}catch(Exception $e){
			echo $this->failureJson(array('message'=>$e->getMessage()));
		}
	}
	
	/**
	 * @desc 删除
	 * @throws Exception
	 */
	public function actionBatchdel(){
		try{
			$ids = Yii::app()->request->getParam("ids");
			$wishProductSellerRelation = Yii::app()->request->getParam("WishProductSellerRelation");
			
			if(empty($ids)){
				throw new Exception("参数不对");
			}
			$idArr = explode(",", $ids);
			$res = UebModel::model("WishProductSellerRelation")->deleteById($idArr);
			if(!$res){
				throw new Exception("操作失败");
			}
			echo $this->successJson(array('message'=>'操作成功'));
		}catch(Exception $e){
			echo $this->failureJson(array('message'=>$e->getMessage()));
		}
	}
	
	/**
	 * @desc 获取未绑定的
	 */
	public function actionUnbindseller(){
		$model = new WishProductUnbindSellerRelation();
		$request = http_build_query($_POST);

		//查询出搜索的总数
		$itemCount = $model->search()->getTotalItemCount();
		
		$this->render("unbindseller", array('model'=>$model, 'request'=>$request, 'itemCount'=>$itemCount));	
	}
	
	/**
	 * @desc 批量未绑定的到某一个人(账号操作)
	 */
	public function actionBatchchangeunbindtoseller(){
		if($_POST){
			error_reporting(E_ALL);
			ini_set("display_errors", true);
			try{
				$oldAccountId = Yii::app()->request->getParam('old_account_id');
				$newSellerId = Yii::app()->request->getParam('WishProductSellerRelation');
				if(empty($oldAccountId)){
					throw new Exception("没有选择原有账号");
				}
	
				if(empty($newSellerId)){
					throw  new Exception("没有选择替换的销售人员");
				}
				if(!$this->_model->batchSetAccountListingToSeller($oldAccountId, $newSellerId['seller_id'])){
					throw new Exception("设置失败，账号不存在！");
				}

				$jsonData = array(
						'message' => '更改成功',
						'forward' =>'/wish/wishproductsellerrelation/unbindseller',
						'navTabId'=> 'page' . WishProductSellerRelation::getUnbindsellerNavTabId(),
						'callbackType'=>'closeCurrent'
				);
				echo $this->successJson($jsonData);

				// echo $this->successJson(array('message'=>'设置成功'));
			}catch (Exception $e){
				echo $this->failureJson(array('message'=>$e->getMessage()));
			}
			Yii::app()->end();
		}
		//获取账号列表
		$accountList = UebModel::model("WishAccount")->getIdNamePairs();
		//获取销售人员列表
		$sellerList = User::model()->getWishUserList();
		$allSellerList = User::model()->getWishUserList(true);
		$this->render("batchchangeunbindtoseller", array('model'=>$this->_model, 'accountList'=>$accountList, 'sellerList'=>$sellerList, 'allSellerList'=>$allSellerList));
		exit;
	}
	
	/**
	 * @desc 批量改sku
	 */
	public function actionBatchchangeunbindskutoseller(){
		
		$ids = Yii::app()->request->getParam('ids');
		
		//获取销售人员列表
		$sellerList = User::model()->getWishUserList();
		$this->render("batchchangeunbindskutoseller", array('model'=>$this->_model, "sellerList"=>$sellerList, "ids"=>rtrim($ids,',')));
	}
	
	/**
	 * 保存批量设置sku给销售人员
	 * @throws Exception
	 */
	public function actionSavebatchsetunbindskutoseller(){
		error_reporting(E_ALL);
		ini_set("display_errors", true);
		try{
			
			$ids = Yii::app()->request->getParam('ids');
			$newSellerId = Yii::app()->request->getParam('WishProductSellerRelation');
			if(empty($ids)){
				throw new Exception("没有选择SKU");
			}
			
			if(empty($newSellerId)){
				throw  new Exception("没有选择替换的销售人员");
			}
			$idArr = explode(",", $ids);
			if(!$this->_model->batchSetSkuListingToSeller($idArr, $newSellerId['seller_id'])){
				$jsonData = array(
					'message' => '账号已经被设置，请重新刷新页面！',
					'forward' =>'/wish/wishproductsellerrelation/unbindseller',
					'navTabId'=> 'page' . WishProductSellerRelation::getUnbindsellerNavTabId(),
					'callbackType'=>'closeCurrent'
				);
				echo $this->failureJson($jsonData);
				exit;
				// throw new Exception("设置失败！");
			}

			$jsonData = array(
				'message' => '更改成功',
				'forward' =>'/wish/wishproductsellerrelation/unbindseller',
				'navTabId'=> 'page' . WishProductSellerRelation::getUnbindsellerNavTabId(),
				'callbackType'=>'closeCurrent'
			);
			echo $this->successJson($jsonData);

		}catch (Exception $e){
			echo $this->failureJson(array('message'=>$e->getMessage()));
		}
		Yii::app()->end();
	}
	// ==================== 以下为临时处理测试 ====================== //
	
	public function actionSaveimportdata(){
		error_reporting(E_ALL);
		set_time_limit(5*3600);
		ini_set('display_errors', true);
		ini_set('memory_limit', '256M');
		try{
			$file = "./uploads/skuseller/wish-0729.xlsx";
			$PHPExcel = new MyExcel();
			//excel处理
			Yii::import('application.vendors.MyExcel');
			$datas = $PHPExcel->get_excel_con($file);
			if(!empty($datas)){
				$wishProductSellerRelationModel = new WishProductSellerRelation();
				$sellerUserList = User::model()->getPairs();
				$sellerUserList = array_flip($sellerUserList);
				foreach ($datas as $key=>$data){
					if($key == 1) continue;
					//@TODO 每个平台不一致
					$itemId 	= 	$data['D'];
					$sku 		= 	$data['G'];
					$onlineSku 	= 	$data['F'];
					$accountID 	=	$data['A'];
					$siteID		=	0;
					$sellerName = 	trim($data['K']);
					$newSellerId = isset($sellerUserList[$sellerName]) ? $sellerUserList[$sellerName] : '';
					if(empty($newSellerId)) continue;
					//入库操作
					//检测是否存在
					if($existsId = $wishProductSellerRelationModel->checkUniqueRow($itemId, $sku, $onlineSku, $accountID, $siteID)){
						//存在更新
						//$wishProductSellerRelationModel->updateSellerIdByItemIdAndSku($newSellerId, $itemId, $sku, $onlineSku);
						$wishProductSellerRelationModel->updateDataById($existsId, array('seller_id'=>$newSellerId));
					}else{//不存在插入
	
						$nowTime = date("Y-m-d H:i:s");
						$insertData = array(
								'site_id'		=>	$siteID,
								'account_id'	=>	$accountID,
								'item_id'		=>	$itemId,
								'sku'			=>	$sku,
								'online_sku'	=>	$onlineSku,
								'seller_id'		=>	$newSellerId,
								'create_time'	=>	$nowTime,
								'update_time'	=>	$nowTime
						);
						$wishProductSellerRelationModel->saveData($insertData);
					}
				}
			}
			echo $this->successJson(array('message'=>'success'));
			Yii::app()->end();
			exit;
		}catch (Exception $e){
			echo $this->failureJson(array('message'=>$e->getMessage()));
			Yii::app()->end();
		}
		
	}
	
	
	public function actionSaveimportcsv(){
		error_reporting(E_ALL);
		set_time_limit(5*3600);
		ini_set('display_errors', true);
		ini_set('memory_limit', '2048M');
		try{
			$wishProductSellerRelationModel = new WishProductSellerRelation();
			$sellerUserList = User::model()->getPairs();
			$sellerUserList = array_flip($sellerUserList);
			//$this->print_r($sellerUserList);
			$file = "./uploads/skuseller/wish-0729.csv";
			$fileHandle = fopen($file,'r');
			$key = 0;
			while ($data = fgetcsv($fileHandle)) {
				//var_dump($data);
				$key++;
				if($key == 1) continue;
				
				//@TODO 每个平台不一致
				$itemId 	= 	$data[3];
				$sku 		= 	$data[6];
				$onlineSku 	= 	$data[5];
				$accountID 	=	$data[0];
				$siteID		=	0;
				$sellerName = 	trim($data[10]);
				//$sellerName = mb_convert_encoding($sellerName, "UTF-8");
				
				echo $sellerName;
				echo "<br/>";
				$newSellerId = isset($sellerUserList[$sellerName]) ? $sellerUserList[$sellerName] : '';
				echo $newSellerId, "<br/>";
				if(empty($newSellerId)){
					echo "Not find Seller<br/>";
				}
				if(empty($newSellerId)) continue;
				//入库操作
				//检测是否存在
				if($existsId = $wishProductSellerRelationModel->checkUniqueRow($itemId, $sku, $onlineSku, $accountID, $siteID)){
					//存在更新
					//$wishProductSellerRelationModel->updateSellerIdByItemIdAndSku($newSellerId, $itemId, $sku, $onlineSku);
					$wishProductSellerRelationModel->updateDataById($existsId, array('seller_id'=>$newSellerId));
				}else{//不存在插入
				
					$nowTime = date("Y-m-d H:i:s");
					$insertData = array(
							'site_id'		=>	$siteID,
							'account_id'	=>	$accountID,
							'item_id'		=>	$itemId,
							'sku'			=>	$sku,
							'online_sku'	=>	$onlineSku,
							'seller_id'		=>	$newSellerId,
							'create_time'	=>	$nowTime,
							'update_time'	=>	$nowTime
					);
					$wishProductSellerRelationModel->saveData($insertData);
				}
				
			}
			
			echo $this->successJson(array('message'=>'success'));
			Yii::app()->end();
			exit;
		}catch (Exception $e){
			echo $this->failureJson(array('message'=>$e->getMessage()));
			Yii::app()->end();
		}		
	}


	/**
	 * @desc 导出产品与销售人员绑定的数据
	 */
	public function actionBindsellerexportxlsajax(){
		set_time_limit(2*3600);
		ini_set('display_errors', true);
		ini_set('memory_limit', '2048M');

		$conditions = 'id>:id';
    	$params[':id'] = 0;
    	$bool = 1;

    	$getParams = $_GET;
		if($getParams){
			if(isset($getParams['sku']) && $getParams['sku']){
				$conditions .= ' and sku LIKE "'.trim($getParams['sku']).'%"';
				// $params[':sku'] = $getParams['sku'];
			}

			if(isset($getParams['online_sku']) && $getParams['online_sku']){
				$conditions .= ' and online_sku LIKE "'.trim($getParams['online_sku']).'%"';
			}

			if(isset($getParams['item_id']) && $getParams['item_id']){
				$conditions .= ' and item_id=:item_id';
				$params[':item_id'] = trim($getParams['item_id']);
			}

			if(isset($getParams['account_id']) && $getParams['account_id']){
				$conditions .= ' and account_id=:account_id';
				$params[':account_id'] = $getParams['account_id'];
			}

			if(isset($getParams['seller_id']) && $getParams['seller_id']){
				$conditions .= ' and seller_id=:seller_id';
				$params[':seller_id'] = $getParams['seller_id'];
			}
		}

    	//从数据库中取出数据
		$datas = $this->_model->getBindSellerListByCondition($conditions,$params);
		if(!$datas){
			$bool = 0;
		}

		$this->render("unbindskutosellerajax", array('bool'=>$bool));
	}


	/**
	 * @desc 导出产品与销售人员绑定的数据
	 */
	public function actionBindsellerexportxls(){
		set_time_limit(2*3600);
		ini_set('display_errors', true);
		ini_set('memory_limit', '2048M');

		$conditions = 'id>:id';
    	$params[':id'] = 0;

    	$getParams = $_GET;
		if($getParams){
			if(isset($getParams['sku']) && $getParams['sku']){
				$conditions .= ' and sku LIKE "'.trim($getParams['sku']).'%"';
				// $params[':sku'] = $getParams['sku'];
			}

			if(isset($getParams['online_sku']) && $getParams['online_sku']){
				$conditions .= ' and online_sku LIKE "'.trim($getParams['online_sku']).'%"';
				// $params[':online_sku'] = $getParams['online_sku'];
			}

			if(isset($getParams['item_id']) && $getParams['item_id']){
				$conditions .= ' and item_id=:item_id';
				$params[':item_id'] = trim($getParams['item_id']);
			}

			if(isset($getParams['account_id']) && $getParams['account_id']){
				$conditions .= ' and account_id=:account_id';
				$params[':account_id'] = $getParams['account_id'];
			}

			if(isset($getParams['seller_id']) && $getParams['seller_id']){
				$conditions .= ' and seller_id=:seller_id';
				$params[':seller_id'] = $getParams['seller_id'];
			}
		}

    	//从数据库中取出数据
		$datas = $this->_model->getBindSellerListByCondition($conditions,$params);
		if(!$datas){
			throw new Exception("无数据");
		}

		$str = "Item ID,SKU,在线SKU,账号ID,站点ID,销售人员,账号名称\n";

		//取出所有销售人员
    	$allSellerList = User::model()->getAllUserName();

    	$accountList = WishAccount::model()->getIdNamePairs();

		foreach ($datas as $key => $value) {
			$sellName = isset($allSellerList[$value['seller_id']])?$allSellerList[$value['seller_id']]:'';
			$accountName = isset($accountList[$value['account_id']])?$accountList[$value['account_id']]:'';

			$str .= "\t".trim($value['item_id']).",\t".$value['sku'].",\t".$value['online_sku'].",".$value['account_id'].",,".$sellName.",".$accountName."\n";
		}

		//导出文档名称
    	$exportName = 'wish_绑定销售人员_sku_导出表'.date('Y-m-dHis').'.csv';

    	$this->export_csv($exportName,$str);
		exit;
	}


	/**
	 * @desc 导出产品与销售人员未绑定的数据
	 */
	public function actionUnbindsellerexportxlsajax(){
		set_time_limit(2*3600);
		ini_set('display_errors', true);
		ini_set('memory_limit', '2048M');

		$conditions = 'ISNULL(s.seller_id) and p.enabled=:enabled and p.product_id IS NOT NULL';
    	$params[':enabled'] = 1;
    	$bool = 1;

    	$getParams = $_GET;
		if($getParams){
			if(isset($getParams['sku']) && $getParams['sku']){
				$conditions .= ' and p.sku LIKE "'.trim($getParams['sku']).'%"';
				// $params[':sku'] = $getParams['sku'];
			}

			if(isset($getParams['online_sku']) && $getParams['online_sku']){
				$conditions .= ' and p.online_sku LIKE "'.trim($getParams['online_sku']).'%"';
				// $params[':online_sku'] = $getParams['online_sku'];
			}

			if(isset($getParams['product_id']) && $getParams['product_id']){
				$conditions .= ' and p.product_id=:product_id';
				$params[':product_id'] = trim($getParams['product_id']);
			}

			if(isset($getParams['account_id']) && $getParams['account_id']){
				$conditions .= ' and p.account_id=:account_id';
				$params[':account_id'] = $getParams['account_id'];
			}
		}

    	$datas = $this->_model->getUnBindSellerListByCondition($conditions,$params);
    	if(!$datas){
			$bool = 0;
		}

		$this->render("unbindskutosellerajax", array('bool'=>$bool));
	}


	/**
	 * @desc 导出产品与销售人员未绑定的数据
	 */
	public function actionUnbindsellerexportxls(){
		set_time_limit(2*3600);
		ini_set('display_errors', true);
		ini_set('memory_limit', '2048M');

		$conditions = 'ISNULL(s.seller_id) and p.enabled=:enabled and p.product_id IS NOT NULL';
    	$params[':enabled'] = 1;

    	$getParams = $_GET;
		if($getParams){
			if(isset($getParams['sku']) && $getParams['sku']){
				$conditions .= ' and p.sku LIKE "'.trim($getParams['sku']).'%"';
				// $params[':sku'] = $getParams['sku'];
			}

			if(isset($getParams['online_sku']) && $getParams['online_sku']){
				$conditions .= ' and p.online_sku LIKE "'.trim($getParams['online_sku']).'%"';
				// $params[':online_sku'] = $getParams['online_sku'];
			}

			if(isset($getParams['product_id']) && $getParams['product_id']){
				$conditions .= ' and p.product_id=:product_id';
				$params[':product_id'] = trim($getParams['product_id']);
			}

			if(isset($getParams['account_id']) && $getParams['account_id']){
				$conditions .= ' and p.account_id=:account_id';
				$params[':account_id'] = $getParams['account_id'];
			}
		}

    	$datas = $this->_model->getUnBindSellerListByCondition($conditions,$params);
    	if(!$datas){
			throw new Exception("无数据");
		}

		$accountList = WishAccount::model()->getIdNamePairs();

		$str = "Item ID,SKU,在线SKU,账号ID,站点ID,销售人员,账号名称\n";

		foreach ($datas as $key => $value) {
			$accountName = isset($accountList[$value['account_id']])?$accountList[$value['account_id']]:'';

			$str .= "\t".trim($value['item_id']).",\t".$value['sku'].",\t".$value['sku_online'].",".$value['account_id'].",,,".$accountName."\n";
		}

		//导出文档名称
    	$exportName = 'wish_未绑定销售人员_sku_导出表'.date('Y-m-dHis').'.csv';

    	$this->export_csv($exportName,$str);
		exit;
	}


	/**
	 * 定时绑定sku与销售人员
	 */
	public function actionSetunbindskutosellerrelation(){
		set_time_limit(3600);
		error_reporting(E_ALL);
		ini_set("display_errors", true);
		$limit 							= Yii::app()->request->getParam('limit', '');
		$wishAccountModel 				= new WishAccount();
		$UnbindSellerRelationModel 		= new WishProductUnbindSellerRelation();
		$wishProductAdd             	= new WishProductAdd();
		$wishProductSellerRelationModel = new WishProductSellerRelation();
		$wishLog 						= new WishLog();
		$productToAccountModel          = new ProductToAccount();

		//取出销售人员信息
		$sellerUserList = User::model()->getWishUserList();

		//获取账号列表
		$accountList = UebModel::model("WishAccount")->getIdNamePairs();

		$wishAccountInfo = $wishAccountModel->findAll('id > 0');
		foreach ($wishAccountInfo as $key => $value) {
			$unBindSkuInfo = $UnbindSellerRelationModel->getUnbindSkuByAccountId($value->id,$limit);
			if(!$unBindSkuInfo){
				continue;
			}

			$eventName = "wish_product_seller_relation";
			$logParams = array(
                'account_id'    => $value->id,
                'event'         => $eventName,
                'start_time'    => date('Y-m-d H:i:s'),
                'response_time' => date('Y-m-d H:i:s'),
                'create_user_id'=> Yii::app()->user->id ? Yii::app()->user->id : User::admin(),
                'status'        => WishLog::STATUS_DEFAULT,
	        );
			$logID = $wishLog->savePrepareLog($logParams);
			if(!$logID) exit("NO CREATE LOG ID");

			if(!$wishLog->checkRunning($value->id, $eventName)){
				$wishLog->setFailure($logID, "EXISTS EVENT");
				continue;
			}

			$wishLog->setRunning($logID);

			//循环插入到wish产品listing与销售人员关联表
			foreach ($unBindSkuInfo as $skuInfo) {
				//通过主sku和账号ID查询刊登记录表里的销售人员ID
				// $conditions = "account_id = :account_id AND parent_sku = :parent_sku";
				// $params = array(':account_id'=>$value->id, ':parent_sku'=>$skuInfo['parent_sku']);
				// $productInfo = $wishProductAdd->getProductAddInfo($conditions,$params);
				// if(!$productInfo){
				// 	continue;
				// }
				
				//通过市场人员与SKU，账号，平台关系绑定
				$accountSku  = $skuInfo['sku'];
				$warehouseId = $skuInfo['warehouse_id'];
				$tableName = 'ueb_product_to_account_seller_platform_kf_'.$value->id;
				$fields    = 'seller_user_id as create_user_id';
				$wheres    = 'sku = \''.$accountSku.'\' AND warehouse_id ='.$warehouseId;
				$productInfo = $productToAccountModel->getOneByCondition($tableName,$fields,$wheres);
				if(!$productInfo){
					$wheres    = 'sku = \''.$skuInfo['parent_sku'].'\' AND warehouse_id ='.$warehouseId;
					$productInfo = $productToAccountModel->getOneByCondition($tableName,$fields,$wheres);
					if(!$productInfo){
						$mainSku = ProductSelectAttribute::model()->getMainSku(null, $accountSku);
						if($mainSku && $accountSku != $mainSku){
							$accountSku = $mainSku;
						}
						$wheres    = 'sku = \''.$accountSku.'\' AND warehouse_id ='.$warehouseId;
						$productInfo = $productToAccountModel->getOneByCondition($tableName,$fields,$wheres);
						if(!$productInfo){
							continue;
						}
					}
				}

				$newSellerId = $productInfo['create_user_id'];

				$itemId 	= 	$skuInfo['product_id'];
				$sku 		= 	$skuInfo['sku'];
				$onlineSku 	= 	$skuInfo['online_sku'];
				$accountID 	=	$value->id;
				$siteID		=	0;
				if(!isset($sellerUserList[$newSellerId])){
					continue;
				}

				if(empty($itemId) || empty($sku) || empty($onlineSku) || !isset($accountList[$accountID])){
					continue;
				}

				//检测不够四位，不够的话前缀补零
				$sku = encryptSku::skuToFloat($sku);
				$onlineSku = encryptSku::skuToFloat($onlineSku);

				$insertData = array(
					'site_id'		=>	$siteID,
					'account_id'	=>	$accountID,
					'item_id'		=>	$itemId,
					'sku'			=>	$sku,
					'online_sku'	=>	$onlineSku,
				);

				try{					
										
					//入库操作
					//检测是否存在
					if($existsId = $wishProductSellerRelationModel->checkUniqueRow($itemId, $sku, $onlineSku, $accountID, $siteID)){
						continue;
					}else{//不存在插入
					
						$nowTime = date("Y-m-d H:i:s");
						$insertData['seller_id'] = $newSellerId;
						$insertData['create_time'] = $nowTime;
						$insertData['update_time'] = $nowTime;

						$res = $wishProductSellerRelationModel->saveData($insertData);
						if(!$res){
							echo $this->failureJson(array('message'=>'Insert Into Failure'));
							exit();
						}
					}
				}catch (Exception $e){
					$insertData['status'] = 1;
					$insertData['error_msg'] = $e->getMessage();
					$insertData['seller_id'] = 0;
					$wishProductSellerRelationModel->writeProductSellerRelationLog($insertData);
				}
			}

			$wishLog->setSuccess($logID, "done");
		}
	}
}