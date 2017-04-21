<?php

/**
 * Created by PhpStorm.
 * User: wuyk
 * Date: 2016/12/24
 * Time: 17:22
 *
 */
abstract class Api
{
	/***
	 * @param string $task
	 * @param array $params
	 * @return string
	 *
	 * 返回获取待刊登或者待优化的Api连接
	 */
	private function setApiUrl($task = 'listing', $params = array())
	{
		$task_keys = ConfigFactory::getConfig('taskKeys');
		$page_size = isset($task_keys['page_' . $task . '_size']) ? $task_keys['page_' . $task . '_size'] : $task_keys['page_size'];
		$status = isset($task_keys[$task . '_status']) ? $task_keys[$task . '_status'] : $task_keys['list_status'];
		$params = array_merge($params, array('pagesize' => $page_size, 'status' => $status));
		$api_url = isset($task_keys['api_' . $task . '_url']) ? $task_keys['api_' . $task . '_url'] : $task_keys['api_listing_url'];
		if (!empty($params)) {
			foreach ($params as $k => $v) {
				$newParam[] = "{$k}={$v}";
			}
			$api_url .= "?" . join("&", $newParam);
		}
		return $api_url;
	}

	/**
	 * @param string $task
	 * @param array $params
	 * @return string
	 *
	 * 拉取待刊登列表的数据
	 */
	public function fetchData($task = 'listing', $params = array())
	{
		$debug = $params['debug'];

		//获取总页数
		$page = 1;  //默认当前页（默认第1页）
		$pages = 1; //总页数（默认1页）
		$flag = true;
		$fetchArray = array();
		do {
			if ($debug) {
				echo "<br>==page:{$page}==<br/>";
			}
			$api_url = $this->setApiUrl($task, array_merge($params, array('pagenum' => $page)));
			if ($debug) {
				echo "=================Fetch Url ================<br />";
				echo $api_url, "<br>";
			}
			$output = $this->getApiData($api_url);
			$row = json_decode($output, true);
			if ($debug) {
				echo "<br />====Api result=====<br/>";
				echo "<pre>";
				print_r($row);
			}

			if ('succ' == $row['status']) {
				$pages = (0 < $row['result']['pages']) ? $row['result']['pages'] : 1;
				$array = $row['result']['list'];
				if (!empty($array)) {
					foreach ($array as $data) {
						//如果是拉取待刊登数据，则根据条件判断
						if ('listing' == $task) {
							$check_result = $this->checkListing($data);
							if ($check_result) {
								continue;
							};
						}

						//如果是拉取待优化的数据，则根据条件判断
						if ('optimization' == $task) {
							$optimization_check_result = $this->checkOptimization($data);
							if ($optimization_check_result) {
								continue;
							};
						}

						$diff_key = !empty($data['site']) ? $data['site'] : $data['warehouseid'];
						$arr_key = $data['staffId'] . '-' . $data['accountId'] . '-' . $diff_key . '-' . $data['sku'] . '-' . date('Y-m-d');
						$fetchArray[$arr_key] = $data;
						if (count($fetchArray) >= $params['num']) {
							$flag = false;
							break;
						}
					}
				}
			}

			$page++;
			if ($page > $pages) {
				$flag = false;
			};
		} while ($flag);

		return $fetchArray;
	}

	/**
	 * @return array
	 * 待刊登列表与接口字段之间的映射关系
	 */
	protected function fieldMapping()
	{
		return array(
			'api_id' => 'id',
			'seller_user_id' => 'staffId',
			'account_id' => 'accountId',
			'account_short_name' => 'accountShortName',
			'site_name' => 'site',
			'site_id' => 'site_id',
			'platform_code' => 'platformCode',
			'sku' => 'sku',
			'sku_status' => 'skuStatus',
			'sku_category_id' => 'skuCategoryId',
			'category_name' => 'categoryName',
			'sku_title' => 'skuTitle',
			'cost_price' => 'costPrice',
			'currency' => 'currency',
			'status' => 'status',
			'sku_create_time' => 'skuCreateTime',
			'creator' => 'creator',
			'created_at' => 'createTime',
			'updater' => 'updater',
			'updated_at' => 'updateTime',
			'warehouse_id' => 'warehouseId',
		);
	}


	/**
	 * @return array
	 * 待优化表与接口的映射关系
	 */
	protected function fieldOptimizationMapping()
	{
		return array(
			'api_id' => 'id',
			'seller_user_id' => 'staffId',
			'account_id' => 'accountId',
			'platform' => 'platformCode',
			'sku' => 'sku',
			'listing_status' => 'listingStatus',
			'listing_id' => 'listingid',
			'listing_title' => 'listingTitle',
			'listing_url' => 'listingUrl',
			'sale_price' => 'salePrice',
			'watch_count' => 'watchCount',
			'hit_count' => 'hitCount',
			'sale_count' => 'saleCount',
			'site_name' => 'site',
			'site_id' => 'site_id',
			'status' => 'status',
			'creator' => 'creator',
			'listing_create_time' => 'listingCreateTime',
			'created_at' => 'createTime',
			'updater' => 'updater',
			'updated_at' => 'updateTime',
			'currency' => 'currency',
		);
	}

	/**
	 * @param $field
	 * @param $data
	 * @return mixed|string
	 * 根据字段返回相应的值
	 */
	protected function processFieldMapping($field, $data)
	{
		$waitProcessingField = array(
			'skuTitle',
			'currency',
			'skuCreateTime',
			'createTime',
			'updateTime',
			'listingCreateTime',
			'site_id',
			'accountShortName',
		);

		if (in_array($field, $waitProcessingField)) {
			if (in_array($field, array('listingTitle', 'skuTitle'))) {
				return htmlspecialchars(addslashes($data[$field]));
			} elseif ('currency' == $field) {
				return !empty($data[$field]) ? $data[$field] : 'CNY';
			} elseif ('site_id' == $field) {
				$site_list = $this->siteList($data['platformCode'], true);
				$data['site_id'] = isset($site_list[$data['site']]) ? $site_list[$data['site']] : 0;
				return $data['site_id'];
			} elseif ('accountShortName' == $field) {
				$account_list = $this->accountShortName($data['platformCode']);
				return $data['accountShortName'] = empty($data['accountShortName']) ?
					$account_list[$data['accountId']] : $data['accountShortName'];
			} else {
				$date_time = str_replace("Z", "", str_replace("T", " ", $data[$field]));
				return !empty($date_time) ? $date_time : '0000-00-00 00:00:00';
			}
		} else {
			return $data[$field];
		}
	}


	/**
	 * @param $platform
	 * @param $data
	 * @param $type
	 * @return null
	 *
	 * 发生错误，则记录错误
	 */
	protected function failLogs($type, $data, $platform = 'EB')
	{
		switch ($platform) {
			case Platform::CODE_EBAY:
				$model = new EbayFailLog();
				break;
			case Platform::CODE_ALIEXPRESS:
				$model = new AliexpressFailLog();
				break;
            case Platform::CODE_AMAZON:
                $model = new AmazonFailLog();
                break;
            case Platform::CODE_WISH:
                $model = new WishFailLog();
                break;
            case Platform::CODE_LAZADA:
                $model = new LazadaFailLog();
                break;
                break;
			default:
				$model = new EbayFailLog();
				break;
		}
		$model->saveData(array('type' => $type, 'seller_user_id' => $data['seller_user_id'], 'message' => json_encode($data), 'created_at' => date('Y-m-d H:i:s')));
	}


	/**
	 * @param $platform
	 * @return mixed
	 *
	 * 获取平台名称
	 */
	protected function platformName($platform)
	{
		$platform_class = new Platform();
		$codes = $platform_class->getPlatformCodesAndNames();
		$name = $codes[$platform];
		return $name;
	}


	/**
	 * @param string $platform
	 * @return multitype
	 *
	 * 根据平台获取账号信息
	 */
	private function accountShortName($platform = Platform::CODE_EBAY)
	{
		$platform_name = $this->platformName($platform);
		$class_platform_name = $platform_name.'Account';
		if (class_exists($class_platform_name)) {
			$model = new $class_platform_name();
			return $model::getIdNamePairs();
		}
	}


	/**
	 * @param null $platform
	 * @param bool $flip
	 * @return mixed
	 */
	public function siteList($platform = null, $flip = false)
	{
		if (in_array($platform, array(Platform::CODE_EBAY, Platform::CODE_LAZADA, Platform::CODE_AMAZON))) {
			$platform_name = $this->platformName($platform);
			$class_platform_name = $platform_name.'Site';
			if (class_exists($class_platform_name)) {
				$model = new $class_platform_name();
				return (false == $flip) ? $model->getSiteList() : array_flip($model->getSiteList());
			}
		} else {
			return array();
		}
	}


	/**
	 * @param $params
	 * @return bool
	 *
	 * 先从刊登历史表中查找到销售+账号+站点+sku是否已经刊登了，如果已刊登成功，则不会安排到刊登池中重新刊登
	 * 如果历史表中没有刊登记录，则根据当前时间(1-5号查询最近7天），否则在当月刊登池中查找，如果有，则不安排
	 * 进刊登池，否则进入刊登池
	 */
	private function checkListing($params)
	{
		$seller_user_id = $params['staffId'];
		$account_id = $params['accountId'];
		$sku = $params['sku'];
		$site_name = $params['site'];
		$platform = $params['platformCode'];
		$warehouse_id = $params['warehouseId'];
		$site_list = $this->siteList($platform, true);
        $site_id = isset($site_list[$params['site']]) ? $site_list[$params['site']] : 0;
		switch ($platform) {
			case Platform::CODE_EBAY:
				//$history_model = new EbayHistoryListing();
                $product_model_listing = new EbayProduct();
				$row = $product_model_listing->getOneByCondition("id",
                        "account_id = '{$account_id}' AND site_id = '{$site_id}' AND sku = '{$sku}' AND item_status = 1");
				$wait_model = new EbayWaitListing();
				break;
			case Platform::CODE_ALIEXPRESS:
				//$history_model = new AliexpressHistoryListing();
                $product_model_listing = new AliexpressProduct();
                $row = $product_model_listing->getOneByCondition("id",
                        "account_id = '{$account_id}' AND sku = '{$sku}' AND product_status_type = 'onSelling' ");
				$wait_model = new AliexpressWaitListing();
				break;
            case Platform::CODE_WISH:
                //$history_model = new WishHistoryListing();
                $product_model_listing = new WishProduct();
                $row = $product_model_listing->getOneByCondition("id",
                        "account_id = '{$account_id}' AND sku = '{$sku}' AND enabled = '1' AND warehouse_id = '{$warehouse_id}'");
                $wait_model = new WishWaitListing();
                break;
            case Platform::CODE_AMAZON:
                //$history_model = new AmazonHistoryListing();
                $product_model_listing = new AmazonList();
                $row = $product_model_listing->getOneByCondition("id",
                    "account_id = '{$account_id}' AND sku = '{$sku}' AND seller_status < 3 AND warehouse_id = '{$warehouse_id}'");
                $wait_model = new AmazonWaitListing();
                break;
            case Platform::CODE_LAZADA:
                $product_model_listing = new LazadaProduct();
                $row = $product_model_listing->getOneByCondition("id",
                    "account_id = '{$account_id}' AND sku = '{$sku}' AND status = 1 AND site_id ='{$site_id}'");
                $wait_model = new LazadaWaitListing();
                break;
			default:
				//$history_model = new EbayHistoryListing();
                $product_model_listing = new EbayProduct();
                $row = $product_model_listing->getOneByCondition("id",
                    "account_id = '{$account_id}' AND site_id = '{$site_id}' AND sku = '{$sku}' AND item_status = 1");
				$wait_model = new EbayWaitListing();
				break;
		}

		//根据条件查询数据
        /*
		$row = $history_model->getOneByCondition("id, status",
				"seller_user_id = '{$seller_user_id}' AND account_id = '{$account_id}' 
				 AND site_name = '{$site_name}' AND sku = '{$sku}' AND status = 3");
        */
		//没有刊登或者刊登状态还是未成功状态的
		if (empty($row)) {
			$current_day = date('j');
			$start_date = ($current_day < 6) ? date('Y-m-d', strtotime('-7 days')) : date('Y-m-01');
			$end_date = date('Y-m-d');
			//在待刊登记录中查找最近7天或者当月中是否已存在此sku数据
			$check_row = $wait_model->getOneByCondition('id',
							"seller_user_id = '{$seller_user_id}' AND account_id = '{$account_id}'
							 AND site_name = '{$site_name}' AND sku = '{$sku}' AND date_time BETWEEN '{$start_date}' AND '{$end_date}'
							");

			//如果在待刊登列表中有最近7日或者本月的数据了，则不写入
			if (!empty($check_row)) {
				return true;
			} else {
				return false;
			}
		} else {
			//已经有刊登成功了，不要写入待刊登列表
			return true;
		}
	}

	/**
	 * @param $params
	 * @return bool
	 *
	 * 检查当月（如果是前5日，则检查最近7天）是否已经安排了优化任务
	 */
	private function checkOptimization($params)
	{
		$seller_user_id = $params['staffId'];
		$account_id = $params['accountId'];
		$sku = $params['sku'];
		$site_name = $params['site'];
		$platform = $params['platformCode'];
		switch ($platform) {
			case Platform::CODE_EBAY:
				$model = new EbayWaitOptimization();
				break;
			case Platform::CODE_ALIEXPRESS:
				$model = new AliexpressWaitOptimization();
				break;
            case Platform::CODE_WISH:
                $model = new WishWaitOptimization();
                break;
            case Platform::CODE_AMAZON:
                $model = new AmazonWaitOptimization();
                break;
            case Platform::CODE_LAZADA:
                $model = new LazadaWaitOptimization();
                break;
			default:
				$model = new EbayWaitOptimization();
				break;
		}

		//5号之前的查询最近7天，5号之后的查询当月
		$current_day = date('j');
		$start_date = ($current_day < 6) ? date('Y-m-d', strtotime('-7 days')) : date('Y-m-01');
		$end_date = date('Y-m-d');
		$check_row = $model->getOneByCondition('id',
						"seller_user_id = '{$seller_user_id}' AND account_id = '{$account_id}'
					 	 AND site_name = '{$site_name}' AND sku = '{$sku}' AND date_time BETWEEN '{$start_date}' AND '{$end_date}'
					");

		return !empty($check_row) ? true : false;
	}

	/**
	 * @param $api_url
	 * @return mixed
	 *
	 * 利用 curl 的方法获取接口的数据
	 */
	private function getApiData($api_url)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $api_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		$output = curl_exec($ch);
		curl_close($ch);
		return $output;
	}

}
