<?php

/**
 * Created by PhpStorm.
 * User: wuyk
 * Date: 2016/12/29
 * Time: 10:32
 */
class TasksyncaccountController extends TaskCommonController
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


	public function actionSynctaskaccount()
	{
		set_time_limit(3600);
		ini_set("display_errors", true);
		error_reporting(E_ALL);
		echo 'Start:'.date('Y-m-d H:i:s');
        $execute_start = date('Y-m-d H:i:s');
        $message = '';
		try {
			//输入参数
			$platform = strtoupper(Yii::app()->request->getParam('platform', Platform::CODE_EBAY));
			$param_key = strtolower(Yii::app()->request->getParam('param_key', 'listing'));
			$debug = Yii::app()->request->getParam('debug', false);

			$task_keys = ConfigFactory::getConfig('taskKeys');
			$platform_arr = Platform::model()->getPlatformCodes();
			if (in_array($platform, $platform_arr)) {
				//先移除账号关系绑定有变动的账号
				$this->remove($param_key, $platform, $debug);
				$rows = SellerUserToAccountSite::model()->getDataByPlatform($platform);
				$total = count($rows);
				if (0 < $total) {
					$page_size = isset($task_keys['page_size']) ? $task_keys['page_size'] : 50;
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
								$arr[] = $rows[$k];
							}
							if ($debug) {
								echo "<br />============= fetch page: {$page} data: ================<br /><pre>";
								print_r($arr);
								echo "</pre>";
							}
							//调用处理函数，处理数据
							$this->process($arr, $platform, $param_key, $debug);
						}
					} while ($flag);

					//执行完毕之后,如果是平台为lazada，则修改不是马来站点的值为0，但是其它地方又使用
                    if (Platform::CODE_LAZADA == $platform) {
                        $model = ('listing' == $param_key) ? $this->model('task', $platform) : $this->model('sales', $platform);
                        $model->executeUpdate();
                    }
				} else {
				    throw new Exception("获取销售账号站点失败");
                }
			} else {
				throw new Exception("{$platform} was not exist");
			}
		} catch (Exception $e) {
            $message = $e->getMessage();
            echo "<br />";
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
	 * @param $rows
	 * @param $platform
	 * @param $param_key
	 * @param bool $debug
	 *
	 * 把销售，账号，站点销售表中绑定的数据，同步过来
	 */
	private function process($rows, $platform, $param_key, $debug = false)
	{
		$taskKeys = ConfigFactory::getConfig('taskKeys');
		$model = ('listing' == $param_key) ? $this->model('task', $platform) : $this->model('sales', $platform);
		$sites = $this->siteList($platform, true);
		$extend_model = $this->model('sales_extend', $platform);
		foreach ($rows as $k => $v) {
			//检查任务设定表中是否存在此记录，如果不存在，则插入
			$site_id = isset($sites[$v['site']]) ? $sites[$v['site']] : 0;
			$seller_user_id = $v['seller_user_id'];
			$account_id = $v['account_id'];
			$result = $model->getoneByCondition("id", "seller_user_id = '{$seller_user_id}' AND account_id = '{$account_id}' AND site_id = '{$site_id}'");
			if (empty($result)) {
				if (empty($v['seller_user_id'])) {
					if ($debug) {
						echo "============Empty Data:=============<br /><pre>";
						print_r($v);
						echo "</pre>";
					}
					continue;
				}
				$department = User::model()->getUserNameById($v['seller_user_id']);
				//没数据，则插入
				$newData = array(
					'seller_user_id' => $v['seller_user_id'],
					'account_id' => $v['account_id'],
					'site_id' => $site_id,
					'platform_code' => $platform,
					'department_id' => isset($department['department_id']) ? $department['department_id'] : 0,
					'created_uid' => 0,
					'created_at' => date('Y-m-d H:i:s')
				);

				if ('listing' == $param_key) {
					$newData = array_merge($newData, array('listing_num' => $taskKeys['list_num'], 'optimization_num' => $taskKeys['optimization_num']));
				} else {
					//计算12个月的总目标及总利润
					$sales_profit_detail = $this->getDefaultValue();
					$sales_target = 0;
					$profit_target = 0;
					foreach ($sales_profit_detail as $sk => $sv) {
						$sales_target += $sv['sales_amount'];
						$profit_target += $sv['profit_amount'];
					}
					$newData = array_merge($newData, array('sales_target' => $sales_target, 'profit_target' => $profit_target));
				}
				if ($debug) {
					echo "============Save Data:=============<br /><pre>";
					print_r($newData);
					echo "</pre>";
				}
				$insertId = $model->saveData($newData);
				//如果是设置销售额，则更新扩展表
				if ('sales' == $param_key) {
					$sales_profit_detail = $this->getDefaultValue();
					foreach ($sales_profit_detail as $spk => $spv) {
						$month = $spv['month'];
						$year = $spv['year'];
						$sales_amount = $spv['sales_amount'];
						$profit_amount = $spv['profit_amount'];
						$ex_row = $extend_model->getoneByCondition('id', " sales_id = '{$insertId}' AND month = '{$month}' AND year = '{$year}'");
						if (!empty($ex_row)) {
							$extend_model->updateData(array('sales_amount' => $sales_amount, 'profit_amount' => $profit_amount, 'seller_user_id' => $seller_user_id), " sales_id = '{$insertId}' AND month = '{$month}' AND year = '{$year}'");
						} else {
							$extend_model->saveData(array('sales_amount' => $sales_amount, 'profit_amount' => $profit_amount, 'sales_id' =>$insertId, 'seller_user_id' => $seller_user_id, 'month' => $month, 'year'=>$year));
						}
					}
				}

				if ($debug) {
					echo "<br />===============Execute Result ===============";
					var_dump($insertId);
					echo "<br />";
				}
			} else {
                //如果是设置销售额，则更新扩展表
                if ('sales' == $param_key) {
                    $sales_id = $result['id'];
                    $sales_profit_detail = $this->getDefaultValue();
                    foreach ($sales_profit_detail as $spk => $spv) {
                        $month = $spv['month'];
                        $year = $spv['year'];
                        $sales_amount = $spv['sales_amount'];
                        $profit_amount = $spv['profit_amount'];
                        $ex_row = $extend_model->getoneByCondition('id', " sales_id = '{$sales_id}' AND month = '{$month}' AND year = '{$year}'");
                        if (empty($ex_row)) {
                            $extend_model->saveData(array('sales_amount' => $sales_amount, 'profit_amount' => $profit_amount, 'sales_id' =>$sales_id, 'seller_user_id' => $seller_user_id, 'month' => $month, 'year'=>$year));
                        }
                    }
                }
            }
		}
	}

	/**
	 * @return array
	 *
	 * 获取默认设置值
	 */
	private function getDefaultValue()
	{
		$configs = ConfigFactory::getConfig('taskKeys');
		//默认值
		for($i=1; $i<=12; $i++) {
			$sales_profit_detail[] = array(
				'year' => date('Y'),
				'month' => ($i < 10) ? '0'.$i : $i,
				'sales_amount' => $configs['sales_amount'],
				'profit_amount' => $configs['profit_amount'],
			);
		}
		return $sales_profit_detail;
	}


	/**
	 * @param $param_key
	 * @param $platform
	 * @param bool $debug
	 *
	 * 把绑定关系中关系变化的数据删除，并标记
	 */
	private function remove($param_key, $platform, $debug = false)
	{
		$model = ('listing' == $param_key) ? $this->model('task', $platform) : $this->model('sales', $platform);
		$delete_model = $this->model('delete_log', $platform);
		$rows = $model->sellerAccountSiteList();
		$sites = $this->siteList($platform);
		if ($debug) {
			echo "<br />====================== Setting Data:=====================<br /><pre>";
			print_r($rows);
			echo "</pre>";
		}

		if (!empty($rows)) {
			$extend_model = $this->model('sales_extend', $platform);
			foreach ($rows as $k => $v) {
				$site = $sites[$v['site_id']];
				$seller_user_id = $v['seller_user_id'];
				$account_id = $v['account_id'];
				$check_result = SellerUserToAccountSite::model()->checkSellerAccountSite($platform, $account_id, $site, $seller_user_id);
				//不符合条件或者被删除了，则移除变动的数据
				if (empty($check_result)) {
					$reStatus = $model->deleteOne($seller_user_id, $account_id, $v['site_id']);
					if ($reStatus) {
						//删除扩展的信息
						$extend_rows = array();
						if ('sales' == $param_key) {
							$extend_rows = $extend_model->getDataByCondition("*", " sales_id = '" . $v['id'] . "'");
							//删除扩展表中的数据
							$extend_model->remove($v['id']);
						}
						//记录一条删除日志
						$delete_model->saveData(
							array(
								'seller_user_id' => $seller_user_id,
								'account_id' => $account_id,
								'site_id' => $v['site_id'],
								'param_key' => $param_key,
								'data' => json_encode(array('data' => $v, 'extend' => $extend_rows)),
								'created_at' => date('Y-m-d H:i:s'),
							)
						);
					}
				}
			}
		}
	}


    /**
     * 清除垃圾数据
     */
	public function actionCleardata()
    {
        $platform = strtoupper(Yii::app()->request->getParam('platform', ''));
        if ('' != $platform) {
            $extend_model = $this->model('sales_extend', $platform);
            $result = $extend_model->clearData();
            if (true == $result) {
                echo 'Sucess';
            } else {
                echo 'fail';
            }
        }
    }
}