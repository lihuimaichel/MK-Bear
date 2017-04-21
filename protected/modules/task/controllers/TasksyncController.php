<?php

/**
 * Created by PhpStorm.
 * User: wuyk
 * Date: 2017/2/2
 * Time: 17:06
 */
class TasksyncController extends TaskBaseController
{
	public function accessRules()
	{
		return array(
			array(
				'allow',
				'users' => array(
					'*'
				),
				'actions' => array()
			)
		);
	}

	/**
	 * 控制面板销售概况的数据汇总
	 */
	public function actionSyncdashboardreport()
	{
		set_time_limit(3600);
		ini_set('memory_limit', '2000M');
		ini_set("display_errors", true);
		error_reporting(E_ALL);
        $execute_start = date('Y-m-d H:i:s');
        $message = '';
		try {
			$platform_class = new Platform();
			$platform_code = $platform_class->getPlatformCodesAndNames();
			$platform = strtoupper(Yii::app()->request->getParam('platform', Platform::CODE_EBAY));
			$user_id = Yii::app()->request->getParam("user_id", 0);
			$debug = Yii::app()->request->getParam("debug", false);
			$tmp_model = $this->model('report_tmp', $platform);
			$rows = $this->settingSellerAccountSiteList($platform, ( 0 < $user_id ? array($user_id) : array()));
			$taskKeys = ConfigFactory::getConfig('taskKeys');
			$page_size = $taskKeys['page_size'];
			$data = array();
			$product_multi_array = array(0 => 'single', 1 => 'sub', 2 => 'main');
			echo 'Start: '.date('Y-m-d H:i:s');
			if (!empty($rows)) {
				$platform_name = strtolower($platform_code[$platform]);
				//清空临时表
				for($k = 0; $k < 10; $k++) {
					//主sku临时表
					$table_name = $tmp_model->getRelationTableName($platform_name, $k, 'main');
					$tmp_model->truncateTable($table_name);

					//子sku临时表
					$table_sub_name = $tmp_model->getRelationTableName($platform_name, $k, 'sub');
					$tmp_model->truncateTable($table_sub_name);

					//单品sku临时表
					$table_sub_name = $tmp_model->getRelationTableName($platform_name, $k, 'single');
					$tmp_model->truncateTable($table_sub_name);
				}

				//销售人员信息
				foreach ($rows as $k => $v) {
					$data[$v['seller_user_id']][] = $v['account_id'];
				}

				if ($debug) {
					echo "<br />=====================================Seller Data ======================<br /><pre>";
					print_r($data);
					echo "<br />======================================================================</pre>";
				}

				//循环销售人员，把销售人员对应的数据更新进来
				foreach ($data as $seller_user_id => $sv) {
					if ($debug) {
						echo "<br />================================Seller Info:{$seller_user_id} ========<br />";
					}

					//清空数据
					$prefix = $seller_user_id % 10;
					//填充数据到临时表（主SKU）
					foreach ($sv as $sek => $account_id) {
						foreach ($product_multi_array as $product_multi => $type) {
							//单品以及主sku
							if (in_array($product_multi, array(0, 2))) {
								$arr = ProductToAccountSellerPlatformSlave::model()->getData($platform, $account_id, $seller_user_id, $product_multi);
								$params = array();
								if (!empty($arr)) {
									foreach ($arr as $pk => $pv) {
										//每50个一组保存
										if (0 == ($pk + 1) % $page_size) {
											$tmp_model->insertData($platform_name, $prefix, $params, $type);
											$params = array();
										}
										$params[] = "'".join("','", array_values($pv))."'";
									}
									//不够一组的存一组
									if (!empty($params)) {
										$tmp_model->insertData($platform_name, $prefix, $params, $type);
									}
								}
							} else {
								//根据主sku取得负责的子sku
								$sub_arr = ProductToAccountSellerPlatformSlave::model()->multiData($platform, $account_id, $seller_user_id);
								$new_params = array();
								if (!empty($sub_arr)) {
									foreach ($sub_arr as $pkey => $pval) {
										//每50个一组保存
										if (0 == ($pkey + 1) % $page_size) {
											$tmp_model->insertData($platform_name, $prefix, $new_params, $type);
											$new_params = array();
										}
										$new_params[] =  "'".join("','", array($pval['sku'], $pval['product_is_multi'], $pval['site'], $pval['account_id'], $pval['seller_user_id'], $pval['product_status']))."'";
									}
									//不够一组的存一组
									if (!empty($new_params)) {
										$tmp_model->insertData($platform_name, $prefix, $new_params, $type);
									}
								}

								//再更新按子sku分配的数据
								$sub_array = ProductToAccountSellerPlatformSlave::model()->getData($platform, $account_id, $seller_user_id, $product_multi);
								$sub_params = array();
								if (!empty($sub_array)) {
									foreach ($sub_array as $skey => $sval) {
										//每50个一组保存
										if (0 == ($skey + 1) % $page_size) {
											$tmp_model->insertData($platform_name, $prefix, $sub_params, $type);
											$sub_params = array();
										}
										$sub_params[] =  "'".join("','", array($sval['sku'], $sval['product_is_multi'], $sval['site'], $sval['account_id'], $sval['seller_user_id'], $sval['product_status']))."'";
									}
									//不够一组的存一组
									if (!empty($sub_params)) {
										$tmp_model->insertData($platform_name, $prefix, $sub_params, $type);
									}
								}
							}
						}
					}

					//从临时表汇总数据到控制面板销售概况
					$this->calculatorProduct($seller_user_id, $platform, $platform_name, $prefix);
				}
			}
			echo '<br />End: '.date('Y-m-d H:i:s');
			echo '<br />Done!!!';
		} catch (Exception $e) {
            $message = $e->getMessage();
			echo $e->getMessage();
		}

        $execute_end = date('Y-m-d H:i:s');
        TaskLogModel::model()->saveData(
            array(
                'file_name' => $_SERVER['REQUEST_URI'],
                'start_time' => $execute_start,
                'end_time' => $execute_end,
                'message' => $message,
            )
        );
	}


	public function actionCreatetable()
	{
		$platform = strtoupper(Yii::app()->request->getParam('platform', Platform::CODE_EBAY));
		$debug = Yii::app()->request->getParam("debug", false);
		$platform_class = new Platform();
		$platform_code = $platform_class->getPlatformCodesAndNames();
		echo "Start:".date('Y-m-d H:i:s')."<br />";
		if (isset($platform_code[$platform])) {
			if ($debug) {
				echo "<br />===========================Platform: {$platform} ===========================<br />";
			}
			$prefix = strtolower($platform_code[$platform]);
			$num = in_array($platform, array(Platform::CODE_EBAY, Platform::CODE_ALIEXPRESS, Platform::CODE_WISH, Platform::CODE_AMAZON, Platform::CODE_LAZADA)) ? 10 : 5;

			//创建临时表
			$model_tmp = $this->model('report_tmp', $platform);
			for ($i = 0; $i < $num; $i++) {
				$result = $model_tmp->createTable($prefix, $i);
				$sub_result = $model_tmp->createSubTable($prefix, $i);
				$single_result = $model_tmp->createSingleTable($prefix, $i);
				if ($debug) {
					echo "<br /> Create Table Result：" . (true == $result && true == $sub_result && true == $single_result) ? "Scucess <br />" : "Fail <br />" . "<br />";
				}
			}
			if ($debug) {
				echo "<br />===========================Platform: {$platform} ===========================<br />";
			}
		}
		echo 'End:'.date('Y-m-d H:i:s')."<br />";
	}

	/**
	 * @param string $platform
	 * @param array $seller_arr
	 * @return mixed
	 *
	 * 获取待刊登（待优化）的今日会员数据
	 */
	private function settingSellerAccountSiteList($platform = Platform::CODE_EBAY, $seller_arr = array())
	{
		$model = $this->model('task', $platform);
		return $model->distinctSellerAccount($seller_arr);
	}


	private function calculatorProduct($seller_user_id, $platform, $platform_name, $prefix)
	{
		$date_time = date('Y-m-d');
		$tmp_model = $this->model('report_tmp', $platform);
		$product_status_arr = array(Product::STATUS_PRE_ONLINE, Product::STATUS_ON_SALE, Product::STATUS_WAIT_CLEARANCE);

		//按单品为纬度计算负责的单品sku数量
		$single_rows = $tmp_model->calculatorProduct($seller_user_id, $platform_name, $prefix, 'single');
		foreach ($product_status_arr as $k => $product_status) {
			if (Product::STATUS_ON_SALE == $product_status) {
				$sales_single_count = isset($single_rows[$product_status]) ? $single_rows[$product_status] : 0;
			} elseif (Product::STATUS_WAIT_CLEARANCE == $product_status) {
				$clean_single_count = isset($single_rows[$product_status]) ? $single_rows[$product_status] : 0;
			} else {
				$pre_single_count = isset($single_rows[$product_status]) ? $single_rows[$product_status] : 0;
			}
		}
		//单品已刊登的数量
		$was_single_listing = $this->calcutorListing($seller_user_id, $platform, $platform_name, $prefix, 'single');

		//按子sku为纬度计算负责的子sku数量
		$rows = $tmp_model->calculatorProduct($seller_user_id, $platform_name, $prefix, 'sub');
		foreach ($product_status_arr as $k => $product_status) {
			if (Product::STATUS_ON_SALE == $product_status) {
				$sales_count = isset($rows[$product_status]) ? $rows[$product_status] : 0;
			} elseif (Product::STATUS_WAIT_CLEARANCE == $product_status) {
				$clean_count = isset($rows[$product_status]) ? $rows[$product_status] : 0;
			} else {
				$pre_count = isset($rows[$product_status]) ? $rows[$product_status] : 0;
			}
		}
		//单品+子sku的数量
		$sku_count = ($sales_count + $clean_count + $pre_count + $sales_single_count + $clean_single_count + $pre_single_count);

		//加上单品已刊登的数量
		$was_listing = $this->calcutorListing($seller_user_id, $platform, $platform_name, $prefix, 'sub') + $was_single_listing;
		//取得预刊登的数量
		$pre_listing = $sku_count - $was_listing;
		$pre_listing = ($pre_listing > 0) ? $pre_listing : 0;


		//按主sku为纬度统计汇总
		$array = $tmp_model->calculatorProduct($seller_user_id, $platform_name, $prefix, 'main');
		foreach ($product_status_arr as $k => $product_status) {
			if (Product::STATUS_ON_SALE == $product_status) {
				$sales_main_count = isset($array[$product_status]) ? $array[$product_status] : 0;
			} elseif (Product::STATUS_WAIT_CLEARANCE == $product_status) {
				$clean_main_count = isset($array[$product_status]) ? $array[$product_status] : 0;
			} else {
				$pre_main_count = isset($array[$product_status]) ? $array[$product_status] : 0;
			}
		}

		$sku_main_count = ($sales_main_count + $clean_main_count + $pre_main_count + $sales_single_count + $clean_single_count + $pre_single_count);
		//加上单品已刊登的数量
		$was_main_listing = $this->calcutorListing($seller_user_id, $platform, $platform_name, $prefix, 'main') + $was_single_listing;
		$pre_main_listing = $sku_main_count - $was_main_listing;
		$pre_main_listing = ($pre_main_listing > 0) ? $pre_main_listing : 0;
		$group_row = ProductsGroupModel::model()->findByAttributes(array('seller_user_id' => $seller_user_id));
		$group_id = !empty($group_row) ? $group_row->group_id : 0;

		$params = array(
			'seller_user_id' => $seller_user_id,
			'group_id' => !empty($group_id) ? $group_id : 0,
			'department_id' => User::model()->getDepIdById($seller_user_id),
			'sku_count' => $sku_count,
			'sku_main_count' => $sku_main_count,
			'pre_count' => $pre_count,
			'pre_main_count' => $pre_main_count,
			'sales_count' => $sales_count + $sales_single_count,
			'sales_main_count' => $sales_main_count + $sales_single_count,
			'clean_count' => $clean_count + $clean_single_count,
			'clean_main_count' => $clean_main_count + $clean_single_count,
			'was_listing' => $was_listing,
			'was_main_listing' => $was_main_listing,
			'pre_listing' => $pre_listing,
			'pre_main_listing' => $pre_main_listing,
			'date_time' => $date_time,
		);
		$model = $this->model('report', $platform);
		$result = $model->getOneByCondition("id", " seller_user_id = '{$seller_user_id}' AND date_time = '{$date_time}'");
		if (!empty($result)) {
			$model->updateDataByID($params, $result['id']);
		} else {
			$params = array_merge($params, array('created_at' => date('Y-m-d H:i:s')));
			$model->saveData($params);
		}
	}

	private function calcutorListing($seller_user_id, $platform, $platform_name, $prefix, $type)
	{
		$model = $this->model('sync', $platform);
		return $model->calcutorListing($seller_user_id, $platform, $platform_name, $prefix, $type);
	}
}