<?php

/**
 * Created by PhpStorm.
 * User: wuyk
 * Date: 2016/12/24
 * Time: 17:12
 */
class Ebay extends Api
{
	/**
	 * @param $params
	 * 拉取数据，并保存更新到今日待刊登（待优化）列表
	 */
	public function fetchWaitListing($params)
	{
		$data = $this->fetchData('listing', $params);
		$this->save('listing', $data, $params);
	}


	public function fetchWaitOptimization($params)
	{
		$data = $this->fetchData('optimization', $params);
		$this->save('optimization', $data, $params);
	}


	public function fetchWaitOptimizationHistory($params)
	{
		$data = $this->fetchData('optimization_history', $params);
		$this->save('optimization_history', $data, $params);
	}

	/*
	 * 保存今日待刊登（待优化）列表
	 */
	private function save($task, $fetchArray, $params)
	{
		//获取当前用户今天此站点，账号下已存在的数量
		$model = ('listing' == $task) ? EbayWaitListing::model() : EbayWaitOptimization::model();
		$fieldMapping = ('listing' == $task) ? $this->fieldMapping() : $this->fieldOptimizationMapping();
        $string = ('listing' == $task) ? " AND appeal_status = 0 " : " ";
		$debug = $params['debug'];
		$totalResult = $model->getTotalByCondition(
			" seller_user_id = '" . $params['staffid'] . "'" .
			" AND account_id = '" . $params['accountid'] . "'" .
			" AND site_name = '" . $params['site'] . "'" .
            $string.
			" AND date_time = '" . date('Y-m-d') . "'"
		);

		if ($debug) {
			echo '<br />SellerId: ' . $params['staffid'] . ";AccountId: " . $params['accountid'] . ";Date: " .
				date('Y-m-d') . ' Wait Result<br /><pre />';
			print_r($totalResult);
		}

		if ($debug) {
			echo "<br />================== Fetch Result ===================<br />";
			print_r($fetchArray);
		}

		if (0 < $totalResult['total']) {
			if (!empty($fetchArray)) {
				foreach ($fetchArray as $k => $v) {
					$newData = array();
					$updateArr = array();
					try {
						$seller_uid = $v['staffId'];
						$account_id = $v['accountId'];
						$sku = $v['sku'];
						$site_name = $v['site'];
						$date_time = date('Y-m-d');
						$result = $model->getOneByCondition(
							"id",
							"seller_user_id = '{$seller_uid}' 
								AND account_id = '{$account_id}'
								AND sku = '{$sku}'
								AND site_name = '{$site_name}'
								AND date_time = '{$date_time}'
								"
						);

						//如果为空null,则新增一条记录
						if ($debug) {
							echo "<br>======result:======<br/>";
							var_dump($result);
						}
						if (empty($result)) {
							//如果数据大于等于则跳出循环
							if ($totalResult['total'] >= $params['num']) {
								break;
							}
							//插入数据
							foreach ($fieldMapping as $field => $value) {
								$newData[$field] = $this->processFieldMapping($value, $v);
							}
							$newData = array_merge($newData, array('date_time' => date('Y-m-d')));
							if ($debug) {
								echo "<br>======savedata:======<br />";
								print_r($newData);
							}
							$saveResult = $model->saveData($newData);
							if ($debug) {
								echo "<br>======saveResult:======<br />";
								var_dump($saveResult);
							}

							if ($saveResult) {
								$totalResult['total']++;
							}
						} else {
							//更新数据 2016-10-20T00:00:00Z，不要把 status 更新了，status更新是操作了刊登之后更新的
							$update_at = str_replace("Z", "", str_replace("T", " ", $v['updateTime']));//@todo ?
							$updateArr = array(
								'updater' => $v['updater'],
								'updated_at' => $update_at
							);
							switch ($task) {
								case 'listing':
									$updateArr = array_merge($updateArr, array('sku_status' => $v['skuStatus']));
									break;
								case 'optimization':
									$updateArr = array_merge($updateArr, array('listing_status' => $v['listingStatus']));
									break;
								default:
									$updateArr = array_merge($updateArr, array('sku_status' => $v['skuStatus']));
									break;

							}
							if ($debug) {
								echo '<br />=========================Sync Data ===========<br />';
								echo "seller_user_id = {$seller_uid}; account_id = {$account_id}; sku = {$sku}.<br />";
							}
							$saveResult = $model->updateDataByID($updateArr, $result['id']);
						}

						if (!$saveResult) {
							//如果没有执行成功，写一条log
							$this->failLogs($task, array_merge(
								array('seller_user_id' => $seller_uid,
									'platform' => $params['platform']), $updateArr, $newData),
								$params['platform']);
						}
					} catch (Exception $e) {
						echo $e->getMessage() . "<br/>";
					};
				}
			}
		} else {
			//没有数据，则插入
			if (!empty($fetchArray)) {
				foreach ($fetchArray as $k => $v) {
					try {
						//插入数据
						$newData = array();
						foreach ($fieldMapping as $field => $value) {
							$newData[$field] = $this->processFieldMapping($value, $v);
						}
						$newData = array_merge($newData, array('date_time' => date('Y-m-d')));
						if ($debug) {
							echo "<br>======savedata:======<br/>";
							print_r($newData);
						}
						$saveResult = $model->saveData($newData);
						if ($debug) {
							echo "<br>======saveResult:======<br/>";
							var_dump($saveResult);
						}

						if (!$saveResult) {
							//如果没有执行成功，写一条log
							$this->failLogs($task, $newData, $params['platform']);
						}
					} catch (Exception $e) {
						echo $e->getMessage() . "<br/>";
					};
				}
			}
		}
	}
}