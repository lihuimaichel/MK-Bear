<?php

/**
 * Created by PhpStorm.
 * User: wuyk
 * Date: 2016/12/14
 * Time: 14:36
 *
 * 通过APi接口获取每日待刊登的任务以及待优化的任务列表
 */
class TasksynclistingController extends TaskBaseController
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
	 * 拉取每日需刊登的任务
	 * 接收的参数为：平台 platform, 是否调试模式 debug
	 *
	 * 先取出设置的销售人员，然后循环销售人员拉取数据
	 * 数量根据设置的进行获取，不足的将翻页获取
	 *
	 */
	public function actionFetchlisting()
	{
		set_time_limit(3600);
		ini_set("display_errors", true);
		error_reporting(E_ALL);
		echo 'Start:'.date('Y-m-d H:i:s').'<br />';
        $execute_start = date('Y-m-d H:i:s');
        $message = '';
		try {
			$platform = strtoupper(Yii::app()->request->getParam('platform', Platform::CODE_EBAY));
			$seller_user_id = Yii::app()->request->getParam("seller_user_id", 0);
			$params['platform'] = $platform;
			$params['debug'] = Yii::app()->request->getParam("debug", false);
			//根据平台获取相应平台的销售人员账号
			$platform_class = new Platform();
			$class_code = $platform_class->getPlatformCodesAndNames();
			if (isset($class_code[$platform])) {
				$class_name = $class_code[$platform]; //对应Api里面的类
				if (class_exists($class_name)) {
					$model = new $class_name();
					$all_site = !in_array($platform, array(Platform::CODE_LAZADA)) ? true : false;
					$users = (0 == $seller_user_id) ? $this->settingSellerAccountSiteList($platform, $all_site) : $this->getUsersInfo(array($seller_user_id), $platform);
					if (!empty($users)) {
						//if (in_array($platform, $this->platformSite())) {
                        $sites = $this->siteList($platform);
						//}
						foreach ($users as $key => $val) {
							if (0 < $val['listing_num']) {
								$params['staffid'] = $val['seller_user_id'];
								$params['num'] = $val['listing_num'];
								$params['accountid'] = $val['account_id'];
								//if (in_array($platform, $this->platformSite())) {
								$params['site'] = isset($sites[$val['site_id']]) ? $sites[$val['site_id']] : 'US';
								//}
								//保存拉取的数据
								$model->fetchWaitListing($params);
							}
						}
					}
				} else {
                    throw new Exception("Platform: {$platform} was not exists");
				}
			} else {
				throw new Exception("Platform: {$platform} was not exists");
			}
		} catch (Exception $e) {
            $message = $e->getMessage();
			echo $e->getMessage();
		}
		echo "End:".date('Y-m-d H:i:s');

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


	/**
	 * 拉取每日需优化的任务
	 *
	 * 接收的参数为：平台 platform, 是否调试模式 debug
	 *
	 * 先取出设置的销售人员，然后循环销售人员拉取数据
	 * 数量根据设置的进行获取，不足的将翻页获取
	 */
	public function actionFetchoptimization()
	{
		set_time_limit(3600);
		ini_set("display_errors", true);
		error_reporting(E_ALL);
		echo 'Start:'.date('Y-m-d H:i:s');
        $execute_start = date('Y-m-d H:i:s');
        $message = '';
		try {
			$platform = strtoupper(Yii::app()->request->getParam('platform', Platform::CODE_EBAY));
			$seller_user_id = Yii::app()->request->getParam("seller_user_id", 0);

			$params['platform'] = $platform;
			$params['debug'] = Yii::app()->request->getParam("debug", false);
			//根据平台获取相应平台的销售人员账号
			$platform_class = new Platform();
			$class_code = $platform_class->getPlatformCodesAndNames();
			if (isset($class_code[$platform])) {
				$class_name = $class_code[$platform]; //对应Api里面的类
				if (class_exists($class_name)) {
					$model = new $class_name();
					$users = (0 == $seller_user_id) ? $this->settingSellerAccountSiteList($platform) : $this->getUsersInfo(array($seller_user_id), $platform);
					if (!empty($users)) {
						if (in_array($platform, $this->platformSite())) {
							$sites = $this->siteList($platform);
						}
						foreach ($users as $key => $val) {
							if (0 < $val['optimization_num']) {
								$params['staffid'] = $val['seller_user_id'];
								$params['num'] = $val['optimization_num'];
								$params['accountid'] = $val['account_id'];
								if (in_array($platform, $this->platformSite())) {
									$params['site'] = isset($sites[$val['site_id']]) ? $sites[$val['site_id']] : 'US';
								}
								//保存拉取的数据
								$model->fetchWaitOptimization($params);
							}
						}
					}
				} else {
                    throw new Exception("Platform: {$platform} was not exists");
				}
			} else {
                throw new Exception("Platform: {$platform} was not exists");
			}
		} catch (Exception $e) {
            $message = $e->getMessage();
			echo $e->getMessage();
		}
		echo '<br />End:'.date('Y-m-d H:i:s');

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

	/**
	 * 拉取每日已优化的历史任务
	 *
	 * 接收的参数为：平台 platform, 是否调试模式 debug
	 *
	 * 先取出设置的销售人员，然后循环销售人员查询数据
	 * 如果状态有变更，则更改状态
	 */
	public function actionFetchoptimizationhistory()
	{
		set_time_limit(3600);
		ini_set("display_errors", true);
		error_reporting(E_ALL);
        echo 'Start:'.date('Y-m-d H:i:s');
        $execute_start = date('Y-m-d H:i:s');
        $message = '';
		try {
			$platform = strtoupper(Yii::app()->request->getParam('platform', Platform::CODE_EBAY));
			$debug = Yii::app()->request->getParam("debug", false);
			$date = Yii::app()->request->getParam('date', date('Y-m-d', strtotime("-1 days")));

			$current_day = date('j');
			$start_date = ($current_day < 6) ? date('Y-m-d', strtotime("-7 days")) : date('Y-m-01');
			$end_date = date('Y-m-d');

			$platform_class = new Platform();
			$platform_code = $platform_class->getPlatformCodesAndNames();
			if (isset($platform_code[$platform])) {
				//查询昨天所有未优化成功的数据，检查状态是否已经优化完
				$optimization_model = $this->model('optimization', $platform);
				$optimization_data = $optimization_model->getDataByCondition('DISTINCT(listing_id) AS listing_id', "status < 3 AND date_time BETWEEN '{$start_date}' AND '{$end_date}'");
				$total = count($optimization_data);
				if (0 < $total) {
					$taskKeys = ConfigFactory::getConfig('taskKeys');
					$page_size = $taskKeys['page_size'];
					$total_pages = ceil($total / $page_size);
					$flag = true;
					do {
						for ($page = 1; $page <= $total_pages; $page++) {
							$min = ($page - 1) * $page_size;
							$max = ($page * $page_size - 1);
							$arr = array();
							for ($k = $min; $k <= $max; $k++) {
								if ($total <= $k) {
									$flag = false;
									break;
								}
								$arr[] = $optimization_data[$k]['listing_id'];
							}
							if ($debug) {
								echo "<br />============= fetch page: {$page} data: ================<br /><pre>";
								print_r($arr);
								echo "</pre>";
							}

							$post_data = array(
								'platform' => $platform,
								'status' => 3,
								'listingId' => $arr,
							);
							$api_result = $this->postApi($post_data, 'optimization');
							$api_result = json_decode($api_result, true);
							if ('succ' == $api_result['status']) {
								$list = $api_result['result']['list'];
								if (!empty($list)) {
									foreach ($list as $uk => $uv) {
										$seller_user_id = $uv['staffId'];
										$listingid = $uv['listingid'];
										$optimization_model->updateByWhere(array('status' => $uv['status']), " seller_user_id ='{$seller_user_id}' AND listing_id = '{$listingid}'");
									}
								}
							}
						}
					} while ($flag);
				}
				echo '<br />Done<br />';
			} else {
				throw new Exception("Platform: {$platform} was not exists");
			}
		} catch (Exception $e) {
            $message = $e->getMessage();
			echo $e->getMessage();
		}

        echo '<br />End:'.date('Y-m-d H:i:s');
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


	/**
	 * @param string $platform
	 * @return mixed
	 *
	 * 获取待刊登（待优化）的今日会员数据
	 */
	private function settingSellerAccountSiteList($platform = Platform::CODE_EBAY, $all_site = true)
	{
		$model = $this->model('task', $platform);
		return $model->sellerAccountSiteList(array(), $all_site);
	}

	/**
	 * @param $users
	 * @param string $platform
	 * @return mixed
	 */
	private function getUsersInfo($users, $platform = Platform::CODE_EBAY)
	{
		$model = $this->model('task', $platform);
		$rows = $model->sellerAccountSiteList($users);
		return $rows;
	}



}
