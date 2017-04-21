<?php

/**
 * @author cxy
 * 人员平台刊登统计报表
 */
class UserplatformpublishreportsController extends TaskBaseController
{
	/**
	 * Specifies the access control rules.
	 * This method is used by the 'accessControl' filter.
	 * @return array access control rules
	 */
	public function accessRules()
	{
		return array(
			array(
				'allow',
				'users' => array('*'),
				'actions' => array('show', 'timingpublish')
			),
		);
	}

	/**
	 * 人员平台刊登统计报表列表
	 */
	public function actionList()
	{
		$group = $this->group();
		$depList = array();
		if (!empty($group)) {
			if (ProductsGroupModel::GROUP_SALE == $group->job_id) {
				//如果是销售，账号只查自己的
				$role = 'seller';
				$users = User::model()->getUserNameArrById(Yii::app()->user->id);
			} else {
				//如果是组长，列出组员信息以供查询，默认查询所有组员
				$role = 'leader';
				$user_list = $this->groupUsers($group->group_id);
				$users = array();
				if (!empty($user_list)) {
					$rows = User::model()->getUserListByIDs($user_list);
					foreach ($rows as $k => $v) {
						$users[$v['id']] = $v['user_full_name'];
					}
				}
			}
		} else {
			$check_result = AuthAssignment::model()->checkCurrentUserIsAdminister(Yii::app()->user->id, $this->userPlatform());
			if ($check_result) {
				$role = 'manager';
				//如果是主管
				$department_id = User::model()->getDepIdById(Yii::app()->user->id);
				//根据部门Id获取此部门下所有销售人员的Id
				$users = User::model()->getUserNameByDeptID(array($department_id), true);
			} else {
				$role = "admin";
				$uid = Yii::app()->user->id;
				$depList = UebModel::model('Department')->getMarketsDepartmentInfo();
				if (!UserSuperSetting::model()->checkSuperPrivilegeByUserId($uid)) {
					$depId = User::model()->getDepIdById($uid);
					$depList = array(
						$depId => $depList[$depId]
					);
				}
				$users = array();
			}
		}

		$this->render('list',
			array(
				'depList'=>$depList,
				'users' => $users,
				'role' => $role,
			)
		);
	}

	/**
	 * 显示人员刊登的各项数量
	 */
	public function actionShow()
	{
		$sellerId = trim(Yii::app()->request->getParam('seller_user_id', 0));
		$product_status = Yii::app()->request->getParam('product_status', '');
		$rows = array();
		if (!empty($product_status)) {
			$array = explode(",", $product_status);
			$product_status = array();
			foreach ($array as $pk => $pv) {
				if (!empty($pv)) {
					$product_status[] = $pv;
				}
			}
		} else {
			$product_status = array(Product::STATUS_PRE_ONLINE, Product::STATUS_ON_SALE, Product::STATUS_WAIT_CLEARANCE);
		}

		//如果是销售，则不需要 seller_id参数，默认列出自己的报表汇总
		$group = $this->group();
		if (!empty($group)) {
			if (ProductsGroupModel::GROUP_SALE == $group->job_id) {
				$sellerId = Yii::app()->user->id;
			}
		}

		if (0 < $sellerId) {
			$rows = DashboardSellerStats::model()->fetchDataByStatus(array($sellerId), $product_status);
		}

		$this->render('show', array(
			'data' => $rows,
		));
	}

	/**
	 * 根据查询条件导出数据
	 */
	public function actionReport()
	{
		ini_set('memory_limit', '2000M');
		set_time_limit('3600');
		$productStatus = trim($_GET['product_status'], ',');
		$category = UebModel::model('ProductClass')->getCat();

		$filename = date('Y-m-d') . '人员平台刊登统计报表.csv'; //设置文件名
		header("Content-type:text/csv");
		header("Content-Disposition:attachment;filename=" . $filename);
		header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
		header('Expires:0');
		header('Pragma:public');

		$head_arr = array(
			0 => "", 1 => "", "公司分类"
		);
		$head_arr = array_merge($head_arr, $category);
		$fp = fopen('php://output', 'a');
		foreach ($head_arr as $key => $val) {
			$head_arr[$key] = iconv('utf-8', 'gbk', $val);
		}
		@fputcsv($fp, $head_arr);
		$productCountAll = UebModel::model('ProductClass')->getClassToSkuConut($productStatus);
		$skuCountFrist = array('', '', 'SKU数量');
		$skuCount = array_merge($skuCountFrist, $productCountAll);
		foreach ($skuCount as $key => $val) {
			$skuCount[$key] = iconv('utf-8', 'gbk', $val);
		}
		$head_two = array("平台", "账号", "站点");
		for ($i = 0; $i < count($productCountAll); $i++) {
			$head_two[] = '已刊登|未刊登';
		}
		foreach ($head_two as $key => $val) {
			$head_two[$key] = iconv('utf-8', 'gbk', $val);
		}
		@fputcsv($fp, $skuCount);
		@fputcsv($fp, $head_two);

		$userPlatformAll = array();
		$platform = array();//得到用户绑定的平台
		$userpublishCountArr = array();//人员刊登的各项数据
		$userPlatform = UebModel::model('ProductMarketersManager')->getUserPlatformById($_GET['sc_name_id']);
		foreach ($userPlatform as $val) {
			$userPlatformAll[$val['platform_code']][] = $val;
			if (!in_array($val['platform_code'], $platform)) {
				$platform[] = $val['platform_code'];
			}
		}
		//循环公司分类，平台，账号站点 得到刊登数据
		foreach ($userPlatformAll as $platformCode => $list) {
			foreach ($list as $value) {
				foreach (UebModel::model('ProductClass')->getCat() as $key => $values) {
					$userpublishCountArr[$key][$platformCode][$value['account_id']][$value['site']] = UebModel::model('ProductPlatformListing')->getUserpublishCount($_GET['sc_name_id'], $key, $platformCode, $value['site'], $value['account_id'], $productStatus);

				}
			}
		}
		foreach ($platform as $values) {
			$i = 0;
			$row = array();
			foreach ($userPlatformAll[$values] as $k => $v) {
				$row[$i++] = iconv('utf-8', 'gbk', UebModel::model('Platform')->getPlatformList($values));
				$row[$i++] = iconv('utf-8', 'gbk', UebModel::model('ProductToAccountRelation')->getPlatformAccountById($values, $v['account_id']));
				$row[$i++] = iconv('utf-8', 'gbk', $v['site']);

				foreach (UebModel::model('ProductClass')->getCat() as $key => $val) {
					//$row[$i++] = iconv('utf-8','gbk',$userpublishCountArr[$key][$values][$v['account_id']][$v['site']]) ;
					//$row[$i++] = $userpublishCountArr[$key][$values][$v['account_id']][$v['site']] .' 22| 11'.$productCountAll[$key]-$userpublishCountArr[$key][$values][$v['account_id']][$v['site']];
					$count = $productCountAll[$key] - $userpublishCountArr[$key][$values][$v['account_id']][$v['site']];
					$row[$i++] = $userpublishCountArr[$key][$values][$v['account_id']][$v['site']] . ' | ' . $count;
				}
				$i = 0;
				@fputcsv($fp, $row);
				//echo '<pre>';print_r($userpublishCountArr);die;
			}

		}

		@fclose($fp);
		exit();

	}

	/**
	 *
	 */
	public function actionReportXls()
	{
		ini_set('memory_limit', '2000M');
		set_time_limit('3600');
		$sellerId = Yii::app()->request->getParam('seller_user_id');
		$userName = MHelper::getUsername($sellerId);
		$was_listing_total = 0;
		$pre_listing_total = 0;
		$wait_listing_total = 0;
		$collect_amount_total = 0;
		$view_amount_total = 0;
		$y_sales_total = 0;
		$y_earn_total = 0;
		$y_shipped_total = 0;
		$y_orders_total = 0;
		$t_sales_total = 0;
		$t_earn_total = 0;
		$t_orders_total = 0;
		$t_shipped_total = 0;

		//获取总SKU信息
		$suk_amount = 0;
		$amount_row = DashboardStats::model()->getSumData($sellerId);
		if (!empty($amount_row)) {
			foreach ($amount_row as $sid => $sva) {
				$suk_amount += $sva;
			}
		}

		$rows = DashboardStats::model()->findAllByAttributes(
			array(
				'date_time' => date('Y-m-d'),
				'seller_user_id' => $sellerId
			)
		);

		$name = "{$userName}" . '平台刊登统计报表';
		$str = '<table border="1"><tr><td colspan="18" align="center" >人员平台刊登统计报表</td></tr>';
		$str .= '<tr><td colspan="2">SKU数量</td><td colspan="6">' . $suk_amount . '</td><td colspan="5">昨日</td><td colspan="5">30天</td></tr>';
		$str .= '<tr><td>账号</td><td>站点</td><td>已刊登</td><td>刊登率</td><td>预刊登</td><td>未刊登</td><td>收藏量</td>
				 <td>浏览量</td><td>销售额</td><td>动销率</td><td>净利润</td><td>订单量</td><td>已发货订单</td>
				 <td>销售额</td><td>动销率</td><td>净利润</td><td>订单量</td><td>已发货订单</td></tr>';

		foreach ($rows as $k => $v) {
			$was_listing_total += $v['was_listing'];
			$pre_listing_total += $v['pre_listing'];
			$wait_listing_total += $v['wait_listing'];
			$collect_amount_total += $v['collect_amount'];
			$view_amount_total += $v['view_amount'];
			$y_sales_total += $v['y_sales'];
			$y_earn_total += $v['y_earn'];
			$y_orders_total += $v['y_orders'];
			$y_shipped_total += $v['y_shipped'];
			$t_sales_total += $v['t_sales'];
			$t_earn_total += $v['t_earn'];
			$t_orders_total += $v['t_orders'];
			$t_shipped_total += $v['t_shipped'];

			$str .= '<tr><td>' . $v['account_name'] . '</td><td>' . $v['site_name'] . '</td><td>' .
				$v['was_listing'] . '</td><td>' . $v['listing_rate'] . '%</td><td>' . $v['pre_listing'] . '</td><td>' .
				$v['wait_listing'] . '</td><td>' . $v['collect_amount'] . '</td><td>' . $v['view_amount'] . '</td><td>' .
				$v['y_sales'] . '</td><td>' . $v['y_sales_rate'] . '</td><td>' . $v['y_earn'] . '</td><td>' .
				$v['y_orders'] . '</td><td>' . $v['y_shipped'] . '</td><td>' . $v['t_sales'] . '</td><td>' .
				$v['t_sales_rate'] . '</td><td>' . $v['t_earn'] . '</td><td>' . $v['t_orders'] . '</td><td>' .
				$v['t_shipped'] . '</td></tr>';
		}
		$str .= '<tr><td colspan="2">小计</td><td>' . $was_listing_total . '</td><td>0</td><td>' . $pre_listing_total . '</td><td>' .
			$wait_listing_total . '</td><td>' . $collect_amount_total . '</td><td>' . $view_amount_total . '</td><td>' .
			$y_sales_total . '</td><td>0</td><td>' . $y_earn_total . '</td><td>' . $y_orders_total . '</td><td>' .
			$y_shipped_total . '</td><td>' . $t_sales_total . '</td><td>0</td><td>' . $t_earn_total . '</td><td>' .
			$t_orders_total . '</td><td>' . $t_shipped_total . '</td></tr></table>';

		//转换为GBK编码
		//$str = iconv("UTF-8", "GBK", $str);

		//输出
		header('Content-Length: ' . strlen($str));
		header("Content-type:application/vnd.ms-excel");
		header("Content-Disposition:filename={$name}.xls");
		echo $str;
		die;
	}

	/**
	 * @desc 定时刷新人员刊登统计临时表
	 *
	 * @link /products/userplatformpublishreports/timingpublish
	 */
	public function actionTimingpublish()
	{
		ini_set('memory_limit', '2000M');
		set_time_limit('3600');
		error_reporting(E_ALL & ~E_STRICT);
		ini_set("display_errors", true);

		$type = Yii::app()->request->getParam("type");
		if (!is_null($type)) {
			$typeArr = array($type);
		} else {
			$typeArr = null;
		}
		$isOk = UebModel::model('ProductPlatformPublishReportMain')->timeCreateRecord($typeArr);
		var_dump($isOk);
		Yii::app()->end('finish');
	}

	/**
	 * @desc 把预刊登里面的未上传图片的推送到图片服务
	 * @link /products/userplatformpublishreports/pushskuimg/platform_code/EB/account_id/1/limit/10
	 */
	public function actionPushskuimg()
	{
		set_time_limit(3600);
		error_reporting(E_ALL);
		ini_set("display_errors", true);
		//获取预刊登待上传图片的列表，循环操作：
		$platformCode = Yii::app()->request->getParam("platform_code");
		$paramAccountID = Yii::app()->request->getParam("account_id");
		$paramLimit = Yii::app()->request->getParam("limit");
		$bug = Yii::app()->request->getParam("bug");
		if ($platformCode) {
			$logModel = new EbayLog();
			$virAccountID = 9999;
			$eventName = "push_sku_img";
			$logID = $logModel->prepareLog($virAccountID, $eventName);
			if (!$logID) {
				exit("LOG ID 创建失败");
			}
			if (!$logModel->checkRunning($virAccountID, $eventName)) {
				$logModel->setFailure($logID, "Has exists event!");
				exit("Has exists event!");
			}
			$logModel->setRunning($logID);
			$productToAccountRelationModel = new ProductToAccountRelation();
			$where = "is_to_image=" . ProductToAccountRelation::IS_TO_IMAGE_NOT;
			$limit = 500;
			if ($paramAccountID)
				$where .= " AND account_id='{$paramAccountID}'";
			if ($paramLimit) {
				$limit = $paramLimit;
			}
			$productLists = $productToAccountRelationModel->getRecordByselectAll($platformCode, $where, $limit);
			$errorMsg = "";
			if ($bug) {
				echo "<pre>";
				print_r($productLists);
			}

			try {
				if ($productLists) {
					$parentSKUList = array();
					foreach ($productLists as &$list) {
						try {
							$accountID = $list['account_id'];
							$site = $list['site'];
							$sku = $list['sku'];
							$exskus = explode(".", $sku);
							$parentSKU = '';
							if ($exskus[0] != $sku) {
								$parentSKU = $exskus[0];
							}
							if ($bug) {
								echo "=========sku:$sku accountID:$accountID=======<br/>";
							}
							//每个平台处理的site不一样
							$imgSite = "";//供图片那边使用的站点
							$assistantImage = false;//是否需要取我们服务器地址的图，true为是
							switch ($platformCode) {
								case Platform::CODE_ALIEXPRESS:
								case Platform::CODE_EBAY:
									$assistantImage = false;
									break;
								default:
									$assistantImage = true;
							}
							//获取每个sku图片列表
							$images = Product::model()->getImgList($sku, 'ft');
							if ($bug) {
								echo "=========images=======<br/>";
								print_r($images);
							}
							$imageNameList = array();
							if ($images) {
								foreach ($images as $k => $image) {
									$image = "/" . ltrim(parse_url($image, PHP_URL_PATH), "/");
									$imgname = end(explode('/', $image));
									if ($imgname == $sku . ".jpg") {
										continue;
									} else {
										$imageNameList[] = $imgname;
									}

								}
							}
							$uploadImg = false;
							//获取每个sku图片上传情况
							$response = ProductImageAdd::model()->getSkuImageUpload($accountID, $sku, array_values($imageNameList), $platformCode, $imgSite, $assistantImage);
							if ($bug) {
								echo "=========response=======<br/>";
								print_r($response);
							}
							if (empty($response) || empty($response['result']) || empty($response['result']['imageInfoVOs']) || count($imageNameList) != count($response['result']['imageInfoVOs'])) {
								//如果没有上传全，则推送sku过去
								$pushResult = ProductImageAdd::model()->addSkuImageUpload($accountID, $sku, 0, $platformCode, $imgSite);//发送图片上传请求
								if ($bug) {
									echo "=========sku:$sku 推送=======<br/>";
									print_r($pushResult);
								}
							} else {
								$uploadImg = true;
							}

							//推送父SKU
							if ($parentSKU && !isset($parentSKUList[$parentSKU . "-" . $accountID])) {
								$pushResult = ProductImageAdd::model()->addSkuImageUpload($accountID, $parentSKU, 0, $platformCode, $imgSite);//发送图片上传请求
								$parentSKUList[$parentSKU . "-" . $accountID] = $parentSKU;
								if ($bug) {
									echo "=========psku:$parentSKU 推送=======<br/>";
									print_r($pushResult);
								}
							}
							//如果图片上传完全则，把图片上传状态改为已上传
							if ($uploadImg) {
								$updateRes = $productToAccountRelationModel->setUpdateBySelect($platformCode, "id=" . $list['id'], array('is_to_image' => ProductToAccountRelation::IS_TO_IMAGE_YES, 'image_time' => date("Y-m-d H:i:s")));
								if ($bug) {
									echo "=========updateRes 推送=======<br/>";
									print_r($updateRes);
								}
							}
						} catch (Exception $e) {
							if ($e->getMessage())
								echo $errorMsg .= $e->getMessage() . "\r\n";
						}
					}
				}
				if ($errorMsg) {
					throw new Exception($errorMsg);
				}
				$logModel->setSuccess($logID, "done");
			} catch (CException $e) {
				$logModel->setFailure($logID, $e->getMessage());
			} catch (Exception $e) {
				$logModel->setFailure($logID, $e->getMessage());
			}
		} else {
			$platformCodeArr = array(
				Platform::CODE_EBAY,
				//Platform::CODE_NEWFROG,
				//Platform::CODE_ALIEXPRESS,
				//Platform::CODE_AMAZON,
				Platform::CODE_WISH,
				Platform::CODE_LAZADA,
				Platform::CODE_JOOM,
				//Platform::CODE_PM,
				//Platform::CODE_SHOPEE
			);

			foreach ($platformCodeArr as $code) {
				$url = Yii::app()->request->hostInfo . "/" . $this->route . "/platform_code/" . $code;
				MHelper::runThreadBySocket($url);
				//echo $url."<br/>";
				sleep(30);
			}
		}

	}

	/**
	 * @desc 推送到各个平台共有方法
	 * @throws Exception
	 */
	public function actionBatchpushproductadd()
	{
		set_time_limit(3600);
		error_reporting(E_ALL);
		ini_set("display_errors", true);
		$paramAccountID = Yii::app()->request->getParam("account_id");
		$paramLimit = Yii::app()->request->getParam("limit");
		$platformCode = Yii::app()->request->getParam("platform_code");
		if ($platformCode) {
			try {
				switch ($platformCode) {
					case Platform::CODE_EBAY:
						$logModel = new EbayLog();
						$addModel = new EbayProductAdd();
						$addMethod = "addProductByBatch";
						$addType = EbayProductAdd::ADD_TYPE_PRE;
						$getErrMethod = "getErrorMessage";
						$duration = 'GTC';
						$listingType = null;
						$auctionStatus = null;
						$auctionPlanDay = null;
						$auctionRule = null;
						$configType = 0;
						$addLogModel = new EbayProductAddPreLog;
						$addLogMethod = "addLogData";
						break;
					case Platform::CODE_WISH:
						$logModel = new WishLog();
						$addModel = new WishProductAdd();
						$addMethod = "productAddByBatch";
						$addType = WishProductAdd::ADD_TYPE_PRE;
						$getErrMethod = "getErrorMsg";
						$addLogModel = new WishProductAddPreLog;
						$addLogMethod = "addLogData";
						break;
					case Platform::CODE_JOOM://@todo
						$logModel = new JoomLog();
						$addModel = new JoomProductAdd();
						$addMethod = "productAddByBatch";
						$addType = JoomProductAdd::ADD_TYPE_PRE;
						$getErrMethod = "getErrorMsg";
						$addLogModel = new JoomProductAddPreLog;
						$addLogMethod = "addLogData";
						break;
					case Platform::CODE_LAZADA:
						$logModel = new LazadaLog();
						$addModel = new LazadaProductAdd();
						$addMethod = "productAddByCategory";
						$getErrMethod = "getErrorMsg";
						$addType = LazadaProductAdd::ADD_TYPE_PRE;

						$addLogModel = new LazadaProductAddPreLog;
						$addLogMethod = "addLogData";
						break;
					case Platform::CODE_ALIEXPRESS:
						$logModel = new AliexpressLog();
						$addModel = new AliexpressProductAdd();
						$addMethod = "productAddByBatch";
						$addType = AliexpressProductAdd::ADD_TYPE_PRE;
						$getErrMethod = "getErrorMsg";

						$addLogModel = new AliexpressProductAddPreLog;
						$addLogMethod = "addLogData";
						break;
					//...
					default:
						throw new Exception("暂时不支持");
				}
				$virAccountID = 9999;
				$eventName = "batch_push_add";
				$logID = $logModel->prepareLog($virAccountID, $eventName);
				if (!$logID) throw new Exception("LOG ID create failure");
				if (!$logModel->checkRunning($virAccountID, $eventName)) {
					$logModel->setFailure($logID, "Has Exists event");
					throw new Exception("Has Exists event");
				}
				$logModel->setRunning($logID);
				$productToAccountRelationModel = new ProductToAccountRelation();
				//待上传和失败，次数少于3次的
				$where = "is_to_image=" . ProductToAccountRelation::IS_TO_IMAGE_YES . "
    				and online_status IN (" . ProductToAccountRelation::ONLINE_STATUS_IMAGE_NOT . "," . ProductToAccountRelation::ONLINE_STATUS_FAILURE . ")
    				 AND upload_count<4";
				$limit = 200;
				if ($paramAccountID)
					$where .= " AND account_id='{$paramAccountID}'";
				if ($paramLimit) {
					$limit = $paramLimit;
				}
				//@todo 获取待优化处理
				$parentSKUGroup = array();
				$productLists = $productToAccountRelationModel->getRecordByselectAll($platformCode, $where, $limit);
				if ($productLists) {

					foreach ($productLists as $product) {
						$sku = $product['sku'];
						$accountID = $product['account_id'];
						$site = $product['site'];
						//$site => $siteId
						$siteID = $productToAccountRelationModel->getSiteIDFromSite($platformCode, $site);
						$parentSKU = $product['parent_sku'];
						$addSKU = $sku;
						$isMultiAdd = false;
						if ($parentSKU && $product['is_multi']) {
							//如果是多属性，则判断下这个sku对应的其他子sku是否处理过了
							if (isset($parentSKUGroup[$parentSKU])) {
								continue;
							}
							$parentSKUGroup[$parentSKU] = $parentSKU;
							$addSKU = $parentSKU;
							$isMultiAdd = true;
							//检测对应的SKU图片是否上传完成，包含所有对应的子SKU
							//判断主SKU
							$images = Product::model()->getImgList($addSKU, 'ft');
							$imageNameList = array();
							if ($images) {
								foreach ($images as $k => $image) {
									$image = "/" . ltrim(parse_url($image, PHP_URL_PATH), "/");
									$imgname = end(explode('/', $image));
									if ($imgname == $sku . ".jpg") {
										continue;
									} else {
										$imageNameList[] = $imgname;
									}
								}
							}
							//获取每个sku图片上传情况
							$response = ProductImageAdd::model()->getSkuImageUpload($accountID, $addSKU, array_values($imageNameList), $platformCode, null, true);
							if (empty($response) || empty($response['result']) || empty($response['result']['imageInfoVOs']) || count($imageNameList) != count($response['result']['imageInfoVOs'])) {
								continue;
							}
							//检测子SKU是否上传完成
							$subProductLists = $productToAccountRelationModel->getRecordByselectAll($platformCode, "online_status=" . ProductToAccountRelation::ONLINE_STATUS_IMAGE_NOT . " and is_multi = 1 and parent_sku='" . $parentSKU . "'", 100);
							if ($subProductLists) {
								foreach ($subProductLists as $subproduct) {
									if ($subproduct['is_to_image'] == 0) {
										continue 2;//图片还没有上传完整
									}
								}
							}
						}
						switch ($platformCode) {
							case Platform::CODE_EBAY:
								$res = $addModel->$addMethod($addSKU, $accountID, $siteID, $duration, $listingType, $auctionStatus, $auctionPlanDay, $auctionRule, $configType, $addType);
								break;
							case Platform::CODE_WISH:
								$res = $addModel->$addMethod($addSKU, $accountID, $addType);
								break;
							case Platform::CODE_JOOM:
								$res = $addModel->$addMethod($addSKU, $accountID, $addType);
								break;
							case Platform::CODE_LAZADA:
								$res = $addModel->$addMethod($addSKU, $accountID, $siteID, null, $addType);
								break;
							case Platform::CODE_ALIEXPRESS:
								$res = $addModel->$addMethod($addSKU, $accountID, $addType);
								break;
							//....
						}

						$uploadCount = intval($product['upload_count']);
						$uploadCount++;
						if ($res) {
							$updateData = array('upload_count' => $uploadCount, 'online_status' => ProductToAccountRelation::ONLINE_STATUS_YES, 'online_time' => date("Y-m-d H:i:s"));
						} else {
							$updateData = array('upload_count' => $uploadCount, 'online_status' => ProductToAccountRelation::ONLINE_STATUS_FAILURE, 'online_time' => date("Y-m-d H:i:s"));
							//@todo 设置失败log
							echo "=========$sku=======<br/>";
							echo $addModel->$getErrMethod(), "<br/>";
							$addLogModel->$addLogMethod(array(
								'sku' => $sku, 'account_id' => $accountID, 'rid' => $product['id'], 'message' => $addModel->$getErrMethod(), 'create_time' => date("Y-m-d H:i:s"), 'status' => 1
							));
						}

						if ($isMultiAdd) {
							$productToAccountRelationModel->setUpdateBySelect($platformCode, "is_multi = 1 and parent_sku='" . $parentSKU . "'", $updateData);
						} else {
							$productToAccountRelationModel->setUpdateBySelect($platformCode, "id=" . $product['id'], $updateData);
						}
					}
				}
				$logModel->setSuccess($logID, "done!");
			} catch (Exception $e) {
				echo $e->getMessage();
			}
		} else {
			$platformCodeArr = array(
				Platform::CODE_EBAY,
				//Platform::CODE_NEWFROG,
				//Platform::CODE_ALIEXPRESS,
				//Platform::CODE_AMAZON,
				Platform::CODE_WISH,
				Platform::CODE_LAZADA,
				Platform::CODE_JOOM,
				//Platform::CODE_PM,
				//Platform::CODE_SHOPEE
			);

			foreach ($platformCodeArr as $code) {
				$url = Yii::app()->request->hostInfo . "/" . $this->route . "/platform_code/" . $code;
				MHelper::runThreadBySocket($url);
				//echo $url."<br/>";
				sleep(30);
			}
		}
	}

	/**
	 * @desc Ebay批量转换到待刊登里去
	 * @link /products/userplatformpublishreports/batchpushebayproductadd/account_id/1/limit/10
	 */
	public function actionBatchpushebayproductadd()
	{
		set_time_limit(3600);
		error_reporting(0);
		ini_set("display_errors", false);
		$paramAccountID = Yii::app()->request->getParam("account_id");
		$paramLimit = Yii::app()->request->getParam("limit");
		try {
			$logModel = new EbayLog();
			$virAccountID = 9999;
			$eventName = "batch_push_add";
			$platformCode = Platform::CODE_EBAY;
			$logID = $logModel->prepareLog($virAccountID, $eventName);
			if (!$logID) throw new Exception("LOG ID create failure");
			if (!$logModel->checkRunning($virAccountID, $eventName)) {
				$logModel->setFailure($logID, "Has Exists event");
				throw new Exception("Has Exists event");
			}
			$logModel->setRunning($logID);
			$productToAccountRelationModel = new ProductToAccountRelation();
			//待上传和失败，次数少于3次的
			$where = "is_to_image=" . ProductToAccountRelation::IS_TO_IMAGE_YES . " 
    				and online_status IN (" . ProductToAccountRelation::ONLINE_STATUS_IMAGE_NOT . "," . ProductToAccountRelation::ONLINE_STATUS_FAILURE . ")
    				 AND upload_count<3";
			$limit = 200;
			if ($paramAccountID)
				$where .= " AND account_id='{$paramAccountID}'";
			if ($paramLimit) {
				$limit = $paramLimit;
			}
			//@todo 获取待优化处理
			$parentSKUGroup = array();
			$productLists = $productToAccountRelationModel->getRecordByselectAll($platformCode, $where, $limit);
			if ($productLists) {
				$ebayProductModel = new EbayProductAdd();
				$addType = EbayProductAdd::ADD_TYPE_PRE;
				$duration = 'GTC';
				$listingType = null;
				$auctionStatus = null;
				$auctionPlanDay = null;
				$auctionRule = null;
				$configType = 0;
				foreach ($productLists as $product) {
					$sku = $product['sku'];
					$accountID = $product['account_id'];
					$site = $product['site'];
					//$site => $siteId
					$siteID = $productToAccountRelationModel->getSiteIDFromSite($platformCode, $site);
					$parentSKU = $product['parent_sku'];
					$addSKU = $sku;
					$isMultiAdd = false;
					if ($parentSKU && $product['is_multi']) {
						//如果是多属性，则判断下这个sku对应的其他子sku是否处理过了
						if (isset($parentSKUGroup[$parentSKU])) {
							continue;
						}
						$parentSKUGroup[$parentSKU] = $parentSKU;
						$addSKU = $parentSKU;
						$isMultiAdd = true;
						//检测对应的SKU图片是否上传完成，包含所有对应的子SKU
						//判断主SKU
						$images = Product::model()->getImgList($addSKU, 'ft');
						$imageNameList = array();
						if ($images) {
							foreach ($images as $k => $image) {
								$image = "/" . ltrim(parse_url($image, PHP_URL_PATH), "/");
								$imgname = end(explode('/', $image));
								if ($imgname == $sku . ".jpg") {
									continue;
								} else {
									$imageNameList[] = $imgname;
								}
							}
						}
						//获取每个sku图片上传情况
						$response = ProductImageAdd::model()->getSkuImageUpload($accountID, $addSKU, array_values($imageNameList), $platformCode, null, true);
						if (empty($response) || empty($response['result']) || empty($response['result']['imageInfoVOs']) || count($imageNameList) != count($response['result']['imageInfoVOs'])) {
							continue;
						}
						//检测子SKU是否上传完成
						$subProductLists = $productToAccountRelationModel->getRecordByselectAll($platformCode, "online_status=" . ProductToAccountRelation::ONLINE_STATUS_IMAGE_NOT . " and is_multi = 1 and parent_sku='" . $parentSKU . "'", 100);
						if ($subProductLists) {
							foreach ($subProductLists as $subproduct) {
								if ($subproduct['is_to_image'] == 0) {
									continue 2;//图片还没有上传完整
								}
							}
						}
					}
					$res = $ebayProductModel->addProductByBatch($addSKU, $accountID, $siteID, $duration, $listingType, $auctionStatus, $auctionPlanDay, $auctionRule, $configType, $addType);
					$uploadCount = intval($product['upload_count']);
					$uploadCount++;
					if ($res) {
						$updateData = array('upload_count' => $uploadCount, 'online_status' => ProductToAccountRelation::ONLINE_STATUS_YES, 'online_time' => date("Y-m-d H:i:s"));
					} else {
						$updateData = array('upload_count' => $uploadCount, 'online_status' => ProductToAccountRelation::ONLINE_STATUS_FAILURE, 'online_time' => date("Y-m-d H:i:s"));
						//@todo 设置失败log
						echo "=========$sku=======<br/>";
						echo $ebayProductModel->getErrorMessage(), "<br/>";
					}

					if ($isMultiAdd) {
						$productToAccountRelationModel->setUpdateBySelect($platformCode, "is_multi = 1 and parent_sku='" . $parentSKU . "'", $updateData);
					} else {
						$productToAccountRelationModel->setUpdateBySelect($platformCode, "id=" . $product['id'], $updateData);
					}
				}
			}
			$logModel->setSuccess($logID, "done!");
		} catch (Exception $e) {
			echo $e->getMessage();
		}
	}

	/**
	 * @desc Ebay批量转换到待刊登里去
	 * @link /products/userplatformpublishreports/batchpushwishproductadd/account_id/1/limit/10
	 */
	public function actionBatchpushwishproductadd()
	{
		set_time_limit(3600);
		error_reporting(0);
		ini_set("display_errors", false);
		$paramAccountID = Yii::app()->request->getParam("account_id");
		$paramLimit = Yii::app()->request->getParam("limit");
		try {
			$logModel = new WishLog();
			$virAccountID = 9999;
			$eventName = "batch_push_add";
			$platformCode = Platform::CODE_WISH;
			$logID = $logModel->prepareLog($virAccountID, $eventName);
			if (!$logID) throw new Exception("LOG ID create failure");
			if (!$logModel->checkRunning($virAccountID, $eventName)) {
				$logModel->setFailure($logID, "Has Exists event");
				throw new Exception("Has Exists event");
			}
			$logModel->setRunning($logID);
			$productToAccountRelationModel = new ProductToAccountRelation();
			//待上传和失败，次数少于3次的
			$where = "is_to_image=" . ProductToAccountRelation::IS_TO_IMAGE_YES . "
    				and online_status IN (" . ProductToAccountRelation::ONLINE_STATUS_IMAGE_NOT . "," . ProductToAccountRelation::ONLINE_STATUS_FAILURE . ")
    				 AND upload_count<3";
			$limit = 200;
			if ($paramAccountID)
				$where .= " AND account_id='{$paramAccountID}'";
			if ($paramLimit) {
				$limit = $paramLimit;
			}
			//@todo 获取待优化处理
			$parentSKUGroup = array();
			$productLists = $productToAccountRelationModel->getRecordByselectAll($platformCode, $where, $limit);
			if ($productLists) {
				$wishProductAddModel = new WishProductAdd();
				foreach ($productLists as $product) {
					$sku = $product['sku'];
					$accountID = $product['account_id'];

					$parentSKU = $product['parent_sku'];
					$addSKU = $sku;
					$isMultiAdd = false;
					if ($parentSKU && $product['is_multi']) {
						//如果是多属性，则判断下这个sku对应的其他子sku是否处理过了
						if (isset($parentSKUGroup[$parentSKU])) {
							continue;
						}
						$parentSKUGroup[$parentSKU] = $parentSKU;
						$addSKU = $parentSKU;
						$isMultiAdd = true;
						//@todo 等待开启图片服务
						//检测对应的SKU图片是否上传完成，包含所有对应的子SKU
						//判断主SKU
						/* $images = Product::model()->getImgList($addSKU, 'ft');
						$imageNameList = array();
						if($images){
							foreach($images as $k=>$image){
								$image = "/" . ltrim(parse_url($image, PHP_URL_PATH), "/");
								$imgname = end(explode('/', $image));
								if($imgname == $sku.".jpg") {
									continue;
								}else{
									$imageNameList[] = $imgname;
								}
							}
						}
						//获取每个sku图片上传情况
						$response = ProductImageAdd::model()->getSkuImageUpload($accountID, $addSKU, array_values($imageNameList), $platformCode, null, true);
						if (empty($response) || empty($response['result']) || empty($response['result']['imageInfoVOs']) || count($imageNameList) != count($response['result']['imageInfoVOs']) ) {
							continue;
						}
						//检测子SKU是否上传完成
						$subProductLists = $productToAccountRelationModel->getRecordByselectAll($platformCode, "online_status=".ProductToAccountRelation::ONLINE_STATUS_IMAGE_NOT." and is_multi = 1 and parent_sku='".$parentSKU."'", 100);
						if($subProductLists){
							foreach ($subProductLists as $subproduct){
								if($subproduct['is_to_image'] == 0){
									continue 2;//图片还没有上传完整
								}
							}
						} */
					}
					$res = $wishProductAddModel->productAddByBatch($addSKU, $accountID, WishProductAdd::ADD_TYPE_PRE);
					$uploadCount = intval($product['upload_count']);
					$uploadCount++;
					if ($res) {
						$updateData = array('upload_count' => $uploadCount, 'online_status' => ProductToAccountRelation::ONLINE_STATUS_YES, 'online_time' => date("Y-m-d H:i:s"));
					} else {
						$updateData = array('upload_count' => $uploadCount, 'online_status' => ProductToAccountRelation::ONLINE_STATUS_FAILURE, 'online_time' => date("Y-m-d H:i:s"));
						//@todo 设置失败log
						echo "=========$sku=======<br/>";
						echo $wishProductAddModel->getErrorMsg(), "<br/>";
					}

					if ($isMultiAdd) {
						$productToAccountRelationModel->setUpdateBySelect($platformCode, "is_multi = 1 and parent_sku='" . $parentSKU . "'", $updateData);
					} else {
						$productToAccountRelationModel->setUpdateBySelect($platformCode, "id=" . $product['id'], $updateData);
					}
				}
			}
			$logModel->setSuccess($logID, "done!");
		} catch (Exception $e) {
			echo $e->getMessage();
		}
	}
}